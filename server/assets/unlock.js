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
 *   1. EdCrypto.deriveKeys(passwort, kdfSalt, kdfIter) -> dataKeyHex
 *   2. EdCrypto.decrypt(dataKeyHex, wrap) versuchen.
 *      Gelingt es, war das Passwort richtig — die Echtheitspruefung steckt
 *      bereits in AES-GCM, ein zusaetzlicher Abgleich waere ueberfluessig.
 *   3. EdCrypto.setDataKey(dataKeyHex), danach EdCrypto.getContentKey(wrap).
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
        try {
          const abgeleitet = await EdCrypto.deriveKeys(pw, kdfSalt, kdfIter);
          // Gelingt das Entpacken, war das Passwort richtig.
          await EdCrypto.decrypt(abgeleitet.dataKeyHex, wrap);
          EdCrypto.setDataKey(abgeleitet.dataKeyHex);
          ck = await EdCrypto.getContentKey(wrap);
          // Frisch entpackt: an die Huelle binden, aus der er stammt.
          if (ck) { await EdKeyGuard.binden(wrap); }
        } catch (e) { ck = null; }

        beschaeftigt = false;
        if (ck) { done(ck); return; }

        ok.disabled = nein.disabled = feld.disabled = false;
        melde('Passwort falsch — bitte erneut versuchen.', 'err');
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
    const dk = vor.dk[String(kdfIter)];
    if (!dk) {
      // Die Rundenzahl des Kontos stand nicht in der Liste — dann gehört das
      // Fach zu einer anderen Anmeldung und ist wertlos.
      EdCrypto.vergissAbleitungen();
      return null;
    }
    EdCrypto.setDataKey(dk);

    /* ---- Stille Anhebung (Schritt 4) ----------------------------------
     *
     * Jetzt liegt alles beisammen, was sie braucht, und zwar nur jetzt: das
     * Passwort ist zwar längst weg, aber seine beiden Ableitungen sind da,
     * und die Schlüsselhülle liefert diese Seite mit.
     *
     * Der Inhaltsschlüssel wird mit dem ALTEN Datenschlüssel entpackt und mit
     * dem NEUEN wieder verpackt. Er selbst ändert sich nicht — deshalb bleibt
     * die Prüfsumme gleich, und deshalb bleiben alle verschlüsselten Angaben
     * unangetastet.
     */
    const ziel = (typeof KDF_ITER_ZIEL !== 'undefined') ? KDF_ITER_ZIEL : null;
    if (ziel && ziel !== kdfIter && vor.dk[String(ziel)] && vor.tk[String(kdfIter)]
        && vor.tk[String(ziel)] && typeof CSRF !== 'undefined') {
      try {
        const nutzlast = {
          alt_token: vor.tk[String(kdfIter)],
          neu_token: vor.tk[String(ziel)],
          neu_iter:  ziel
        };
        let ck = null;
        if (wrap) {
          ck = await EdCrypto.decrypt(dk, wrap);
          nutzlast.wrap_pw   = await EdCrypto.encrypt(vor.dk[String(ziel)], ck);
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
          EdCrypto.setDataKey(vor.dk[String(ziel)]);
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

  async function ensureContentKey(wrap, kdfSalt, kdfIter) {
    if (!wrap) { return null; }

    /* Zuerst das Vormerkfach: Direkt nach einer Anmeldung während einer
     * Umstellung liegt der Datenschlüssel dort und nirgends sonst. Ohne
     * diesen Schritt erschiene der Entsperrdialog unmittelbar nach dem
     * Anmelden — und zwar bei jedem Anmelden. */
    if (!EdCrypto.getDataKey()) {
      const ausVormerkung = await loeseVormerkung(wrap, kdfIter);
      if (ausVormerkung) { return ausVormerkung; }
    }

    // NICHT EdCrypto.getContentKey: Jene Fassung liefert einen
    // zwischengespeicherten Schluessel zurueck, ohne zu pruefen, ob er zu
    // DIESER Huelle gehoert. Die Richtigkeit haengt dann allein daran, dass
    // jeder Weg, auf dem das Konto wechseln koennte, vorher aufraeumt — vier
    // Stellen tun das, eine nicht. EdKeyGuard prueft es selbst und verwirft
    // einen fremden oder zu alten Schluessel.
    const vorhanden = await EdKeyGuard.contentKey(wrap);
    if (vorhanden) { return vorhanden; }

    // Ohne Salt laesst sich nichts ableiten; sehr alte Browser ohne <dialog>
    // bekommen bewusst keinen window.prompt (Passwort im Klartext sichtbar).
    if (!kdfSalt || !kdfIter || typeof HTMLDialogElement === 'undefined') { return null; }

    if (laufend) { return laufend; }
    laufend = frage(wrap, kdfSalt, kdfIter).finally(() => { laufend = null; });
    return laufend;
  }

  return { ensureContentKey };
})();
