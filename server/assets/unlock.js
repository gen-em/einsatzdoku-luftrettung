/* Gen-EM NAdoku — Entsperren der geschuetzten Angaben an Ort und Stelle.
 *
 * Warum es diesen Baustein gibt:
 *   Die Anmeldung haengt am PHP-Sitzungscookie (30 min Inaktivitaet), der
 *   Inhaltsschluessel dagegen am sessionStorage des Tabs. Beide Lebensdauern
 *   laufen auseinander — ein Link in einem neuen Tab, ein Browser-Neustart
 *   oder ein Passwort-Reset ohne Wiederherstellungsschluessel fuehren
 *   regelmaessig zu "angemeldet, aber geschuetzte Angaben gesperrt".
 *   Bisher half nur vollstaendiges Ab- und Neuanmelden.
 *
 * Verfahren (vollstaendig im Browser, das Passwort verlaesst ihn nie):
 *   1. EdCrypto.deriveKeys(passwort, kdfSalt, kdfIter) -> haelfteHex
 *   2. EdCrypto.datenschluessel(haelfteHex, wrap, KONTO_ANTEILE) -> dk.
 *      Seit S10 haengt der Datenschluessel am SERVER-ANTEIL, und welcher
 *      gilt, steht im Praefix der Huelle (E-S10-04).
 *   3. EdCrypto.huelleOeffnen(dk, wrap) versuchen.
 *      Gelingt es, war das Passwort richtig — die Echtheitspruefung steckt
 *      bereits in AES-GCM, ein zusaetzlicher Abgleich waere ueberfluessig.
 *   4. EdCrypto.setDataKey(dk), danach EdCrypto.getContentKey(wrap).
 *
 * Erwartet aus der Seite: EdCrypto UND EdKeyGuard (assets/keyguard.js), das
 * vor dieser Datei geladen werden muss.
 *
 * Sicherheit: Wer eine offene Sitzung uebernimmt, bekommt den Wrap ohnehin
 * mit jeder ausgelieferten Seite. Der Dialog eroeffnet also keinen neuen
 * Angriffsweg; er macht bequem zugaenglich, was die Seite schon enthaelt.
 * Ein Rateangriff gegen den Wrap ist durch die PBKDF2-Runden teuer; ihre Zahl
 * steht je Konto in der Datenbank und kommt als KDF_ITER aus auth_guard.php.
 *
 * Verwendung:
 *   const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
 *   if (!ck) { ...bisheriges Verhalten bei gesperrtem Schluessel... }
 *
 * Erwartet aus der Seite: EdCrypto.
 */
'use strict';
const EdUnlock = (() => {

  // Solange ein Dialog offen ist, haengen sich weitere Aufrufe an dasselbe
  // Versprechen. Ohne das oeffnet z. B. import_ui.js mit seinen drei
  // Aufrufstellen mehrere Dialoge uebereinander.
  let laufend = null;

  /* ---- Die Meldung, wenn der Server-Anteil nicht passt (S10, E-S10-09) ---
   *
   * DREI LAGEN, ZWEI SAETZE — UND KEINER DAVON HEISST „Passwort falsch".
   *
   * Die Huelle dieses Kontos traegt eine Anteil-Kennung, zu der die Seite
   * keinen Anteil ausliefert. Das hat genau drei Ursachen, und die NutzerIn
   * kann bei zweien nichts tun ausser Bescheid geben:
   *
   *   'abweichend'  In config.php steht ein anderer Anteil als der, mit dem
   *                 die Huellen gebaut wurden — oder gar keiner. Die
   *                 Administration traegt ihn vom Schluesselblatt nach.
   *   'fehlt'       Es wird ueberhaupt keiner ausgeliefert, das Konto hat
   *                 aber eine `edka1:`-Huelle. Derselbe Fall aus Sicht der
   *                 NutzerIn, dieselbe Meldung.
   *   Neuanfang     Ein Anteil WIRD ausgeliefert, aber die Kennung der Huelle
   *                 gehoert zu keinem. Der Anteil ist erneuert worden; hier
   *                 hilft der Wiederherstellungsschluessel, und nur hier.
   *
   * DIE ERWARTETE KENNUNG STEHT IN DER MELDUNG. Sie ist kein Geheimnis — ein
   * Fingerabdruck, aus dem nichts zurueckzurechnen ist — und sie ist die eine
   * Angabe, mit der die Administration in zwei Minuten weiss, welcher Wert
   * fehlt. Ohne sie stuende dort „irgendetwas stimmt nicht".
   */
  function anteilMeldung(kennung) {
    const stand = (typeof ANTEIL_STAND !== 'undefined') ? ANTEIL_STAND : 'fehlt';
    if (stand === 'bereit') {
      /* Ein Anteil ist da, aber ein anderer als der dieser Huelle: Neuanfang.
         Der Wiederherstellungsschluessel haengt nicht am Anteil und traegt. */
      return 'Der Server-Anteil wurde erneuert — bitte das Passwort über den '
           + 'Wiederherstellungsschlüssel neu setzen.';
    }
    return 'Der Server-Anteil der Verschlüsselung fehlt oder ist nicht der, '
         + 'mit dem die Hüllen gebaut wurden (Kennung ' + kennung + ' erwartet). '
         + 'Bitte die Administration verständigen.';
  }

  function baueDialog() {
    const d = document.createElement('dialog');
    /* Markup und Klassen des Dialog-Bausteins aus P3/O2 (.dialog, .feld,
     * .knopf, .meldung) — dieselben wie serverseitig in ui.php. Die
     * Meldungszeile benutzt den Meldungs-Baustein ohne Symbol: Sie wechselt
     * zwischen Hinweis („Schlüssel wird abgeleitet …") und Fehler, und das
     * sagt hier die Farbe samt Text. */
    d.className = 'dialog';
    d.innerHTML =
      '<div class="dialog-kopf"><h2>Geschützte Angaben entsperren</h2></div>' +
      '<div class="dialog-inhalt">' +
      '  <p>Die verschlüsselten Angaben sind in dieser Sitzung' +
      ' gesperrt. Zum Entsperren bitte das Kontopasswort eingeben — es wird' +
      ' nur im Browser verwendet und nicht übertragen.</p>' +
      '  <div class="feld"><label class="feld-label">Kontopasswort' +
      '    <input class="feld-eingabe" type="password" name="password"' +
      ' autocomplete="current-password">' +
      '  </label></div>' +
      '  <p class="meldung" data-msg hidden></p>' +
      '</div>' +
      '<div class="dialog-fuss">' +
      '  <button type="button" class="knopf knopf-leise" data-act="no">Abbrechen</button>' +
      '  <button type="button" class="knopf knopf-primaer" data-act="yes">Entsperren</button>' +
      '</div>';
    document.body.appendChild(d);
    return d;
  }

  /** Zeigt den Dialog; liefert den Inhaltsschluessel oder null bei Abbruch. */
  function frage(wrap, kdfSalt, kdfIter) {
    const d = baueDialog();
    const feld = d.querySelector('input');
    const ok = d.querySelector('[data-act="yes"]');
    const nein = d.querySelector('[data-act="no"]');
    const msg = d.querySelector('[data-msg]');

    return new Promise(resolve => {
      let erledigt = false;      // gegen nachlaufende close-Ereignisse
      let beschaeftigt = false;  // gegen Doppelklick waehrend der Ableitung

      function done(v) {
        if (erledigt) { return; }
        erledigt = true;
        if (d.open) { d.close(); }
        d.remove();
        resolve(v);
      }

      function melde(text, art) {
        msg.textContent = text;
        msg.className = 'meldung ' + (art === 'err' ? 'meldung-fehler' : 'meldung-info');
        msg.hidden = false;
      }

      async function pruefe() {
        if (beschaeftigt) { return; }
        const pw = feld.value;
        if (pw === '') { melde('Bitte das Kontopasswort eingeben.', 'err'); feld.focus(); return; }

        // Die Ableitung dauert je nach Geraet 0,3–1 s. Ohne sichtbare
        // Rueckmeldung wirkt die Oberflaeche in dieser Zeit eingefroren.
        beschaeftigt = true;
        ok.disabled = nein.disabled = feld.disabled = true;
        melde('Schlüssel wird abgeleitet …', '');

        let ck = null;
        let anteilFehlt = null;
        try {
          const abgeleitet = await EdCrypto.deriveKeys(pw, kdfSalt, kdfIter);
          /* Erst der Datenschluessel — er haengt seit S10 an der Huelle
           * (E-S10-08). Fehlt der Anteil dazu, wirft das hier, und die
           * Meldung unten sagt etwas anderes als „Passwort falsch". */
          const dk = await EdCrypto.datenschluessel(abgeleitet.haelfteHex, wrap,
                                                    typeof KONTO_ANTEILE !== 'undefined' ? KONTO_ANTEILE : null);
          // Gelingt das Entpacken, war das Passwort richtig.
          await EdCrypto.huelleOeffnen(dk, wrap);
          EdCrypto.setDataKey(dk);
          ck = await EdCrypto.getContentKey(wrap);
          // Frisch entpackt: an die Huelle binden, aus der er stammt.
          if (ck) { await EdKeyGuard.binden(wrap); }
        } catch (e) {
          ck = null;
          if (e && e.name === 'AnteilFehlt') { anteilFehlt = e.kennung; }
        }

        beschaeftigt = false;
        if (ck) { done(ck); return; }

        ok.disabled = nein.disabled = feld.disabled = false;
        /* DIE MELDUNG MUSS DIE LAGE TREFFEN (E-S10-09). „Passwort falsch" bei
         * einem richtigen Passwort ist die teuerste Auskunft, die diese
         * Anwendung geben kann: Sie schickt die NutzerIn in den Reset und die
         * Administration auf die falsche Faehrte — und zwar alle gleichzeitig,
         * denn ein fehlender Anteil trifft jedes Konto zugleich. */
        melde(anteilFehlt !== null ? anteilMeldung(anteilFehlt)
                                   : 'Passwort falsch — bitte erneut versuchen.', 'err');
        feld.select();
        feld.focus();
      }

      // Escape waehrend der Ableitung ignorieren: sonst laeuft die Rechnung
      // weiter, waehrend die aufrufende Seite bereits "abgebrochen" annimmt.
      d.addEventListener('cancel', ev => { if (beschaeftigt) { ev.preventDefault(); } });
      d.addEventListener('close', () => done(null));
      ok.onclick = pruefe;
      nein.onclick = () => { if (!beschaeftigt) { done(null); } };
      feld.addEventListener('keydown', ev => {
        if (ev.key === 'Enter') { ev.preventDefault(); pruefe(); }
      });

      d.showModal();
      feld.focus();
    });
  }

  /**
   * Liefert den Inhaltsschluessel. Ist er in der Sitzung vorhanden, kommt er
   * sofort zurueck; sonst erscheint der Entsperrdialog. Bei Abbruch — oder
   * wenn ueberhaupt nichts zu entsperren ist — kommt null zurueck, die
   * aufrufende Seite verhaelt sich dann wie bisher im gesperrten Zustand.
   */
  /* ---- Vormerkfach der Anmeldung auflösen (M2-01, Schritt 3+4) ---------
   *
   * Die Anmeldung konnte den Datenschlüssel nicht setzen: Sie hat für mehrere
   * Rundenzahlen abgeleitet und wusste nicht, welche gilt. DIESE Seite weiß
   * es — KDF_ITER kommt aus der Nutzerzeile.
   *
   * Läuft still. Ein Fehlschlag kostet nichts: Dann bleibt der Schlüssel
   * gesperrt, und der Entsperrdialog fragt wie eh und je nach dem Passwort.
   *
   * Liefert den Inhaltsschlüssel zurück, wenn die Anhebung gelaufen ist —
   * siehe unten, warum er in diesem Fall nicht mehr aus der Hülle der Seite
   * zu holen wäre. Sonst null; dann geht der normale Weg weiter.
   */
  async function loeseVormerkung(wrap, kdfIter) {
    const vor = EdCrypto.holeAbleitungen();
    if (!vor) { return null; }
    const haelfte = vor.hf[String(kdfIter)];
    if (!haelfte) {
      // Die Rundenzahl des Kontos stand nicht in der Liste — dann gehört das
      // Fach zu einer anderen Anmeldung und ist wertlos.
      EdCrypto.vergissAbleitungen();
      return null;
    }

    /* ---- Der Datenschlüssel hängt seit S10 an der Hülle (E-S10-04) ------
     *
     * Bis Web 19.7.0 stand hier `setDataKey(vor.dk[…])` — die Hälfte WAR der
     * Datenschlüssel. Jetzt entscheidet das Präfix der Hülle: `edka1:<k>:`
     * verlangt HKDF mit dem Anteil zu <k>, alles andere die Hälfte
     * unverändert.
     *
     * Lässt sich der Schlüssel nicht bilden, ist das Fach wertlos: Der Anteil
     * zu dieser Hülle wird nicht ausgeliefert. Dann räumt diese Funktion ab
     * und überlässt es dem Dialog, die Lage zu erklären — er nennt die
     * erwartete Kennung, statt „Passwort falsch" zu behaupten (E-S10-09). */
    const anteile = (typeof KONTO_ANTEILE !== 'undefined') ? KONTO_ANTEILE : null;
    let dk;
    try {
      dk = await EdCrypto.datenschluessel(haelfte, wrap, anteile);
    } catch (e) {
      EdCrypto.vergissAbleitungen();
      return null;
    }
    EdCrypto.setDataKey(dk);

    /* ---- Stille Umstellung (Schritt 4; seit S10 mit zwei Anlässen) -----
     *
     * Jetzt liegt alles beisammen, was sie braucht, und zwar nur jetzt: das
     * Passwort ist zwar längst weg, aber seine beiden Ableitungen sind da,
     * und die Schlüsselhülle liefert diese Seite mit.
     *
     * Der Inhaltsschlüssel wird mit dem ALTEN Datenschlüssel entpackt und mit
     * dem NEUEN wieder verpackt. Er selbst ändert sich nicht — deshalb bleibt
     * die Prüfsumme gleich, und deshalb bleiben alle verschlüsselten Angaben
     * unangetastet.
     *
     * ZWEI ANLÄSSE, EIN AUFRUF (E-S10-07):
     *
     *   RUNDENZAHL  Das Konto rechnet unter dem Zielwert. Dann ändert sich
     *               die Hälfte — und mit ihr Token und Datenschlüssel.
     *   HÜLLE       Die Hülle trägt nicht die aktuelle Anteil-Kennung: keine
     *               (Altbestand) oder die eines vorherigen Anteils
     *               (Rotation). Dann bleibt die Hälfte, und nur der
     *               Datenschlüssel wechselt.
     *
     * Stehen beide an, erledigt ein einziger Aufruf sie zusammen. Steht
     * keiner an, ist hier nichts zu tun — das ist der Regelfall ab dem
     * zweiten Anmelden, und deshalb ruft ein umgestelltes Konto den Endpunkt
     * NIE wieder (Abnahmezahl: 0 Aufrufe beim zweiten Anmelden).
     */
    const ziel    = (typeof KDF_ITER_ZIEL !== 'undefined') ? KDF_ITER_ZIEL : null;
    const kennung = (typeof ANTEIL_KENNUNG !== 'undefined') ? ANTEIL_KENNUNG : null;

    const rundenAnheben  = !!(ziel && ziel !== kdfIter && vor.hf[String(ziel)]);
    /* Ohne ausgelieferten Anteil wird NICHT umgestellt — weder zurück noch
     * hin. Das deckt drei Lagen mit derselben Zeile ab: `fehlt` (es gibt
     * keinen), `abweichend` (es wird keiner herausgegeben) und `demo` (das
     * Demo-Konto bleibt auf `edk1:`, E-P1-19). */
    const huelleUmstellen = !!(wrap && kennung
                               && EdCrypto.huelleKennung(wrap) !== kennung);
    const neuIter = rundenAnheben ? ziel : kdfIter;

    if ((rundenAnheben || huelleUmstellen) && vor.tk[String(kdfIter)]
        && vor.tk[String(neuIter)] && typeof CSRF !== 'undefined') {
      try {
        const nutzlast = {
          alt_token: vor.tk[String(kdfIter)],
          neu_token: vor.tk[String(neuIter)],
          neu_iter:  neuIter
        };
        let ck = null;
        let dkNeu = dk;
        if (wrap) {
          ck = await EdCrypto.huelleOeffnen(dk, wrap);
          /* Der NEUE Datenschlüssel: neue Hälfte (falls die Rundenzahl
           * steigt) und aktueller Anteil. Beides kann sich unabhängig
           * geändert haben. */
          dkNeu = await EdCrypto.datenschluesselZu(vor.hf[String(neuIter)],
                                                   kennung, anteile);
          nutzlast.wrap_pw   = await EdCrypto.huelleBauen(dkNeu, ck, kennung);
          nutzlast.key_check = await EdCrypto.contentKeyCheck(ck);
        }
        const r = await fetch('api/kdf_upgrade.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
          body: JSON.stringify(nutzlast)
        });
        const d = await r.json().catch(() => ({}));
        if (d && d.ok) {
          /* Erst JETZT den neuen Datenschlüssel übernehmen — der Server hat
           * bestätigt. Andersherum stünde im Browser ein Schlüssel, zu dem
           * die gespeicherte Hülle nicht passt (M2-07). */
          EdCrypto.setDataKey(dkNeu);
          EdCrypto.vergissAbleitungen();
          if (ck) {
            /* DIE HÜLLE DIESER SEITE IST JETZT VERALTET.
             *
             * PAT_WRAP wurde gerendert, bevor die Anhebung lief — in der
             * Datenbank steht seit einer Zeile eine andere. Mit dem neuen
             * Datenschlüssel lässt sich die alte Hülle nicht mehr öffnen, und
             * der Entsperrdialog erschiene unmittelbar nach dem Anmelden.
             * Genau das soll eine STILLE Anhebung nicht tun.
             *
             * Der Inhaltsschlüssel ist aber bekannt — er wurde oben entpackt.
             * Er wird abgelegt und an die Hülle DIESER Seite gebunden. Beim
             * nächsten Seitenaufbau trägt die Seite die neue Hülle; die
             * Bindung passt dann nicht mehr, EdKeyGuard verwirft den
             * zwischengespeicherten Schlüssel und entpackt ihn neu — diesmal
             * aus der neuen Hülle mit dem neuen Datenschlüssel. */
            EdCrypto.setContentKey(ck);
            await EdKeyGuard.binden(wrap);
            return ck;
          }
        }
      } catch (e) {
        /* Bewusst still. Die Anhebung ist eine Verbesserung, kein Vorgang,
         * dessen Scheitern jemanden aufhalten dürfte — beim nächsten Anmelden
         * wird es erneut versucht. Eine Meldung an dieser Stelle wäre für die
         * Person weder verständlich noch handhabbar.
         *
         * Der Datenschlüssel bleibt in diesem Fall der ALTE, und die Hülle
         * der Seite passt weiterhin zu ihm. */
      }
    }
    EdCrypto.vergissAbleitungen();
    return null;
  }

  /* ---- Stille Anhebung der Einsatz-Notizen (S9/AP7, E-S9-01) -------------
   *
   * Bis Web 19.0.0 lagen die Notizen des Einsatzes im KLARTEXT in der Spalte
   * `missions.notes`. Sie gehören in den `pat_blob` — und dorthin bringen kann
   * sie nur der Browser, denn nur er hat den Schlüssel. Sobald einer da ist,
   * läuft das hier im Hintergrund.
   *
   * WARUM HIER UND NICHT BEI `loeseVormerkung`: Die KDF-Anhebung hängt dort,
   * weil sie die Ableitungen des Passworts braucht, die es nur unmittelbar
   * nach der Anmeldung gibt. Diese hier braucht den INHALTSSCHLÜSSEL — und den
   * gibt es nach jedem Entsperren, auch wenn es Stunden später über den Dialog
   * geschieht. Sie hängt deshalb an allen drei Wegen, nicht an einem.
   *
   * NIEMAND WARTET DARAUF. Kein `await` beim Aufrufer, kein Fehler nach außen:
   * Die Seite soll sich nicht anders verhalten, weil im Hintergrund ein
   * Altbestand umzieht. Scheitert es, bleibt der Klartext stehen und der
   * nächste Entsperrvorgang versucht es erneut.
   */
  let anhebenLief = false;

  async function notizenAnheben(ck) {
    if (anhebenLief || !ck || typeof CSRF === 'undefined') { return; }
    anhebenLief = true;                       // je Seitenaufruf nur einmal
    try {
      /* In Runden, weil der Endpunkt höchstens 200 Einsätze je Aufruf
         liefert: Ein Konto mit tausend Altnotizen soll nicht eine einzige
         riesige Anfrage bauen. */
      for (let runde = 0; runde < 50; runde++) {
        const r = await fetch('api/pat_anheben.php', { credentials: 'same-origin' });
        if (!r.ok) { return; }
        const j = await r.json();
        const liste = (j && j.missions) || [];
        if (!liste.length) { return; }

        const posten = [];
        for (const m of liste) {
          let o = {};
          if (m.pat_blob) {
            try { o = JSON.parse(await EdCrypto.decrypt(ck, m.pat_blob)) || {}; }
            catch (e) {
              /* NICHT ANFASSEN. Ein Blob, der sich mit diesem Schlüssel nicht
                 öffnen lässt, gehört zu einem anderen Schlüssel — ihn zu
                 ersetzen hieße, fremde Angaben zu löschen. Der Klartext bleibt
                 dann in der Spalte stehen; das ist der ehrlichere Zustand. */
              continue;
            }
          }
          /* Steht im Blob schon eine Notiz, GEWINNT SIE. Sie ist der neuere
             Stand — die Spalte ist dann ein Rest, den ein Speichern hätte
             leeren sollen. */
          if (o.notes == null || String(o.notes).trim() === '') { o.notes = m.notes; }
          posten.push({
            id: m.id,
            pat_blob: await EdCrypto.encrypt(ck, JSON.stringify(o)),
            blob_alt: m.pat_blob || null
          });
        }
        if (!posten.length) { return; }       // nur Unlesbare — weitere Runden bringen nichts

        const a = await fetch('api/pat_anheben.php', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
          body: JSON.stringify({ missions: posten })
        });
        if (!a.ok) { return; }
        const erg = await a.json();
        /* Kein Fortschritt trotz Posten: Dann greift die Wache je Zeile (ein
           anderes Fenster war schneller) — eine weitere Runde liefe endlos. */
        if (!erg || !erg.angehoben) { return; }
      }
    } catch (e) {
      /* still: siehe Kopf */
    }
  }

  async function ensureContentKey(wrap, kdfSalt, kdfIter) {
    if (!wrap) { return null; }

    /* Zuerst das Vormerkfach: Direkt nach einer Anmeldung während einer
     * Umstellung liegt der Datenschlüssel dort und nirgends sonst. Ohne
     * diesen Schritt erschiene der Entsperrdialog unmittelbar nach dem
     * Anmelden — und zwar bei jedem Anmelden. */
    if (!EdCrypto.getDataKey()) {
      const ausVormerkung = await loeseVormerkung(wrap, kdfIter);
      if (ausVormerkung) { notizenAnheben(ausVormerkung); return ausVormerkung; }
    }

    // NICHT EdCrypto.getContentKey: Jene Fassung liefert einen
    // zwischengespeicherten Schluessel zurueck, ohne zu pruefen, ob er zu
    // DIESER Huelle gehoert. Die Richtigkeit haengt dann allein daran, dass
    // jeder Weg, auf dem das Konto wechseln koennte, vorher aufraeumt — vier
    // Stellen tun das, eine nicht. EdKeyGuard prueft es selbst und verwirft
    // einen fremden oder zu alten Schluessel.
    const vorhanden = await EdKeyGuard.contentKey(wrap);
    if (vorhanden) { notizenAnheben(vorhanden); return vorhanden; }

    // Ohne Salt laesst sich nichts ableiten; sehr alte Browser ohne <dialog>
    // bekommen bewusst keinen window.prompt (Passwort im Klartext sichtbar).
    if (!kdfSalt || !kdfIter || typeof HTMLDialogElement === 'undefined') { return null; }

    if (laufend) { return laufend; }
    laufend = frage(wrap, kdfSalt, kdfIter).finally(() => { laufend = null; });
    /* Der dritte Weg: über den Dialog. `then` statt `await`, damit der
       Aufrufer seinen Schlüssel sofort bekommt und die Anhebung daneben
       läuft. */
    laufend.then(ck => { if (ck) { notizenAnheben(ck); } });
    return laufend;
  }

  return { ensureContentKey };
})();
