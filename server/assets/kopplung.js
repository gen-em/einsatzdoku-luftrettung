/* Gen-EM NAdoku — die Geraeteseite laedt nach, wenn das Geraet Ja gesagt hat.
 *
 * WOFUER. Nach der Eingabe des Codes steht die Karte „Am Geraet bestaetigen"
 * und wartet — auf eine Handlung, die woanders geschieht: Die Person schaut
 * auf die Uhr, drueckt dort Ja, schaut zurueck. Ohne diese Datei zeigte der
 * Bildschirm dann noch immer den alten Stand, und die einzige Auskunft waere
 * ein Satz gewesen, der zum Neuladen auffordert. Das ist die einzige Stelle
 * des ganzen Ablaufs, an der die Person nichts tun kann und trotzdem nicht
 * weiss, ob es geklappt hat.
 *
 * OHNE DIESE DATEI BLEIBT DER WEG VOLLSTAENDIG. Sie nimmt der Person einen
 * Handgriff ab, sie ersetzt keinen: Die Karte sagt auch ohne JavaScript, was
 * am Geraet zu tun ist und was danach passiert, und ein Neuladen von Hand
 * fuehrt zum selben Ergebnis. Deshalb steht hier auch nichts, was die Seite
 * erst aufbaut — sie ist fertig, wenn diese Datei laedt.
 *
 * SEIT WEB 24.18.0 (KA-06d) DAZU DER VERGLEICHSWERT, und der ist keine
 * Bequemlichkeit: Ein Gerät mit Geräteschlüssel koppelt nur, wenn Browser
 * und Gerät dieselbe Zahl zeigen und die Person es hier bestätigt. Die
 * Rechnung steht in `huelle2.js` (`kopplungWert()`), die Reihenfolge hier:
 *   1. Rückfrage (Formular `data-kopplung-start`): SPK und Zusage zur
 *      Gerätekennung in die Sitzungsablage des Tabs, DANN N_b ziehen und
 *      mitschicken.
 *   2. Warten, bis das Gerät N_g offenlegt (`n_g` im Stand).
 *   3. Zusage prüfen und den Wert rechnen — gegen die Werte aus 1, nicht
 *      gegen eine neue Abschrift des Servers; den Kontoschlüssel erst, wenn
 *      er zum eigenen privaten Teil passt.
 *   4. „Wert stimmt“: das Gerät ins Gerätebuch (nur bei umgelegter Weiche),
 *      dann dem Server melden. „Wert ist anders“ oder eine Zusage, die nicht
 *      passt: melden, abbrechen.
 * Und beim Laden der Geräteseite der ABGLEICH DES GERÄTEBUCHS: Ein Eintrag
 * ohne Gerät bekommt `bis` (`#geraetebuch-abgleich`).
 *
 * SECHS BEDINGUNGEN, damit aus der Bequemlichkeit kein zweites Problem wird.
 * Sie stehen unten je an ihrer Stelle noch einmal:
 *   1. Ein Endpunkt, der genau eine Frage beantwortet und KEINE Eingabe nimmt.
 *   2. Gefragt wird nach dem Vorgang der eigenen Sitzung, nicht nach „hat sich
 *      irgendetwas geaendert".
 *   3. Die Abfrage endet von selbst: Erfolg, Ablehnung, Fristende, drei
 *      Fehlversuche in Folge.
 *   4. Sie ruht, solange der Reiter im Hintergrund liegt.
 *   5. Ohne JavaScript bleibt der Weg vollstaendig (siehe oben).
 *   6. Der Sprung am Ende ist eine GET-Navigation OHNE Fragment, kein
 *      reload(). Warum beides so sein muss, steht unten bei 'gekoppelt' —
 *      es ist gemessen, nicht geraten.
 */
'use strict';
(() => {

  const ABLAGE = 'nadoku-kopplung:';
  const kasten = document.getElementById('kopplung-warten');

  /* ---- 1. Die Rückfrage: merken, DANN N_b ---------------------------- */
  const startForm = document.querySelector('form[data-kopplung-start]');
  if (startForm) {
    startForm.addEventListener('submit', () => {
      try {
        const T = EdCrypto.teile;
        const nB = T.toB64(EdHuelle2.kopplungNonce());
        sessionStorage.setItem(ABLAGE + startForm.dataset.geraet, JSON.stringify({
          spk: startForm.dataset.spk, zusage: startForm.dataset.zusage, n_b: nB }));
        startForm.querySelector('input[name="n_b"]').value = nB;
      } catch (e) {
        /* Ohne N_b weist der Server ab und sagt, warum. */
      }
    });
  }

  /* ---- Abgleich des Gerätebuchs -------------------------------------- */
  (async () => {
    const el = document.getElementById('geraetebuch-abgleich');
    if (!el || typeof EdInhalt === 'undefined' || !EdInhalt.schreibt2()
        || typeof PAT_WRAP === 'undefined') { return; }
    try {
      const da = new Set(JSON.parse(el.dataset.geraete || '[]'));
      if (kasten && kasten.dataset.geraet) { da.add(kasten.dataset.geraet); }
      const ck = await EdKeyGuard.contentKey(PAT_WRAP);   // still: ohne Nachfrage
      if (!ck) { return; }
      const buch = await EdStandbuch.seitenbuch(ck);
      const geraete = await buch.geraete();
      for (const sid of Object.keys(geraete)) {
        const g = geraete[sid];
        if (g.bis == null && !da.has(g.geraet)) { await buch.geraetAustragen(sid); }
      }
    } catch (e) {
      /* still — beim nächsten Laden wieder */
    }
  })();

  if (!kasten) { return; }   // andere Seite, anderer Zustand — nichts zu tun

  const anzeige = document.getElementById('kopplung-restzeit');
  const quelle  = kasten.dataset.quelle;
  const ziel    = kasten.dataset.ziel;
  if (!quelle || !ziel) { return; }

  /* DER TAKT. Fuenf Sekunden waeren zu traege fuer jemanden, der auf den
   * Bildschirm sieht; eine waere Betriebsamkeit. Drei Sekunden heissen im
   * Regelfall zwei bis vier Anfragen — so lange dauert es, an der Uhr Ja zu
   * druecken. Die Obergrenze zieht ohnehin die Frist: Nach zehn Minuten ist
   * Schluss, ganz gleich, was hier steht. */
  const TAKT_MS = 3000;
  const FEHLER_MAX = 3;

  /* ---- 2. bis 4. Der Vergleichswert ---------------------------------- */
  const herkunft = kasten.dataset.herkunft === '1';
  const karte = kasten.closest('.karte');
  const titel = karte ? karte.querySelector('.karte-titel') : null;
  let wertGezeigt = false;
  let gemerkt = null;   // { spk, nB, ck } für „Wert stimmt“

  function schritt(name) {
    if (!karte) { return; }
    for (const el of karte.querySelectorAll('[data-kopplung-schritt]')) {
      el.hidden = el.dataset.kopplungSchritt !== name;
    }
    if (name === 'geraet' && titel) { titel.textContent = 'Am Gerät bestätigen'; }
  }
  function fehlerZeigen(text) {
    anhalten();
    if (text && karte) {
      const p = karte.querySelector('[data-kopplung-schritt="fehler"] .meldung p');
      if (p) { p.textContent = text; }
    }
    schritt('fehler');
    kasten.hidden = true;
  }
  async function melden(daten) {
    return EdApi.postJson(kasten.dataset.antwort, daten);
  }

  /* Passt der ausgelieferte Kontoschlüssel zum eigenen privaten Teil? Über
   * ECDH mit einem Wegwerfpaar: ECDH(privat, X) = ECDH(x, K) gilt nur für
   * das passende K. So braucht es nichts, was `kontopaar.js` nicht schon
   * hergibt, und nichts davon verlässt den Browser. */
  async function kontoPubGeprueft(ck) {
    const K = EdKontopaar.oeffentlich();
    const privat = await EdKontopaar.privat(ck);
    if (!K || !privat) { return null; }
    const alg = { name: 'ECDH', namedCurve: 'P-256' };
    const weg = await crypto.subtle.generateKey(alg, false, ['deriveBits']);
    const kKey = await crypto.subtle.importKey('raw', K, alg, false, []);
    const a = new Uint8Array(await crypto.subtle.deriveBits({ name: 'ECDH', public: weg.publicKey }, privat, 256));
    const b = new Uint8Array(await crypto.subtle.deriveBits({ name: 'ECDH', public: kKey }, weg.privateKey, 256));
    return a.every((x, i) => x === b[i]) ? K : null;
  }

  async function wertZeigen(nGText) {
    wertGezeigt = true;
    let ablage = null;
    try { ablage = JSON.parse(sessionStorage.getItem(ABLAGE + kasten.dataset.geraet) || 'null'); } catch (e) { ablage = null; }
    if (!ablage) {
      fehlerZeigen('Diese Kopplung wurde in einem anderen Fenster begonnen. Mach dort weiter '
                 + 'oder hol am Gerät einen neuen Code.');
      return;
    }
    try {
      const T = EdCrypto.teile;
      const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
      if (!ck) { wertGezeigt = false; return; }   // Dialog abgebrochen — beim nächsten Takt wieder
      const K = await kontoPubGeprueft(ck);
      if (!K) {
        fehlerZeigen('Der Schlüssel deines Kontos passt nicht zu dem, den der Server ausliefert. '
                   + 'Bitte kein Gerät koppeln und die BetreiberIn informieren.');
        return;
      }
      const spk = T.fromB64(ablage.spk);
      const nB = T.fromB64(ablage.n_b);
      let wert;
      try {
        wert = await EdHuelle2.kopplungWert(K, spk, T.fromB64(nGText), nB, T.fromB64(ablage.zusage));
      } catch (e) {
        if (e && e.grund === 'zusage') {
          await melden({ antwort: 'nein', grund: 'zusage' }).catch(() => {});
          fehlerZeigen(null);
          return;
        }
        throw e;
      }
      gemerkt = { spk: spk, ck: ck };
      const zahl = karte.querySelector('[data-kopplung-zahl]');
      if (zahl) { zahl.textContent = wert.slice(0, 3) + ' ' + wert.slice(3); }
      schritt('wert');
    } catch (e) {
      fehlerZeigen('Der Vergleichswert ließ sich nicht rechnen. Hol am Gerät einen neuen Code.');
    }
  }

  if (karte) {
    for (const knopf of karte.querySelectorAll('[data-kopplung-antwort]')) {
      knopf.addEventListener('click', async () => {
        for (const k of karte.querySelectorAll('[data-kopplung-antwort]')) { k.disabled = true; }
        if (knopf.dataset.kopplungAntwort === 'nein') {
          await melden({ antwort: 'nein', grund: 'wert' }).catch(() => {});
          fehlerZeigen(null);
          return;
        }
        try {
          /* ERST DAS BUCH, DANN DER SERVER: Ein Gerät, das der Server
           * anlegt, ohne im Buch zu stehen, sendete Daten, die nicht
           * aufgehen; ein Eintrag ohne Gerät räumt der Abgleich auf. */
          let imBuch = false;
          if (EdInhalt.schreibt2()) {
            const buch = await EdStandbuch.seitenbuch(gemerkt.ck);
            imBuch = (await buch.geraetEintragen({ spk: gemerkt.spk, geraet: kasten.dataset.geraet,
                                                    seit: Math.floor(Date.now() / 1000) })) !== null;
          }
          const a = await melden({ antwort: 'ja', im_buch: imBuch });
          if (!a.ok) {
            fehlerZeigen(a.daten && a.daten.meldung ? a.daten.meldung
              : 'Die Bestätigung kam nicht an. Hol am Gerät einen neuen Code.');
            return;
          }
          try { sessionStorage.removeItem(ABLAGE + kasten.dataset.geraet); } catch (e) { /* egal */ }
          if (a.daten && a.daten.zustand === 'gekoppelt') {
            anhalten();
            window.location.assign(kasten.dataset.ziel);
            return;
          }
          schritt('geraet');
        } catch (e) {
          fehlerZeigen('Das Gerät ließ sich nicht eintragen. Hol am Gerät einen neuen Code.');
        }
      });
    }
  }

  let rest = parseInt(kasten.dataset.rest || '0', 10);
  let fehler = 0;
  let laeuft = true;
  let zeitgeber = null;

  /* Dieselbe Regel wie pair_restzeit_text() in kopplung_lib.php: volle
   * Minuten, unter einer Minute Sekunden. Zwei Fassungen derselben Regel, und
   * das ist hier nicht zu vermeiden — die eine rechnet beim Aufbau der Seite,
   * die andere waehrend sie steht. Wer eine aendert, aendert beide. */
  function restText(s) {
    if (s <= 0) { return 'abgelaufen'; }
    if (s < 60) { return s + ' Sekunden'; }
    const min = Math.floor(s / 60);
    return min === 1 ? 'eine Minute' : min + ' Minuten';
  }

  /* Der Text der Karte wird ERSETZT, nicht ergaenzt: Ein „Am Geraet
   * bestaetigen" neben einem „Am Geraet abgelehnt" waere ein Widerspruch auf
   * einem Bildschirm. Gebaut wird mit textContent, nicht mit innerHTML —
   * hier entsteht kein Markup, und wo keines entsteht, kann auch keines
   * eingeschleust werden. */
  function schlussText(text) {
    kasten.textContent = text;
  }

  function anhalten() {
    laeuft = false;
    if (zeitgeber !== null) { clearTimeout(zeitgeber); zeitgeber = null; }
  }

  function planen() {
    if (!laeuft) { return; }
    if (zeitgeber !== null) { clearTimeout(zeitgeber); }
    zeitgeber = setTimeout(fragen, TAKT_MS);
  }

  async function fragen() {
    if (!laeuft) { return; }

    /* BEDINGUNG 4: Im Hintergrund wird nicht gefragt. Ein Reiter, der seit
     * Stunden offen liegt, soll den Server nicht alle drei Sekunden anrufen;
     * und niemand sieht die Antwort. Wird der Reiter wieder sichtbar, holt
     * das Ereignis unten die Abfrage sofort nach — die Person erwartet dann
     * einen aktuellen Stand, nicht einen in drei Sekunden. */
    if (document.hidden) { planen(); return; }

    let antwort;
    try {
      const a = await fetch(quelle, { credentials: 'same-origin' });
      if (!a.ok) {
        /* WARTUNG IST KEINE STOERUNG. Wer den Rumpf verwirft, laesst die
           Person nach drei Takten „Die Verbindung zum Server ist gerade
           gestört" lesen — inhaltlich falsch, die Verbindung steht ja,
           der Server hat bewusst geantwortet. Waehrend einer Wartung wird
           die Kopplung ohnehin nicht fertig; also sofort aussteigen statt
           dreimal nachzufragen. Der Dreifehler-Rueckzug bleibt fuer alles
           andere. */
        const d = await a.json().catch(() => null);
        if (d && d.error === 'maintenance') {
          anhalten();
          schlussText(d.meldung || 'NAdoku wird gerade aktualisiert. Bitte später erneut.');
          return;
        }
        throw new Error('HTTP ' + a.status);
      }
      antwort = await a.json();
      fehler = 0;
    } catch (e) {
      /* BEDINGUNG 3, erster Teil: Drei Fehlversuche in Folge, dann Ruhe. Ein
       * Skript, das gegen einen unerreichbaren Server endlos weiterfragt,
       * beschaeftigt das Geraet und sagt der Person nichts. Der Vorgang selbst
       * laeuft davon unberuehrt weiter — die Sitzung lebt auf dem Server, und
       * ein Neuladen von Hand zeigt den Stand. */
      fehler += 1;
      if (fehler >= FEHLER_MAX) {
        anhalten();
        schlussText('Die Verbindung zum Server ist gerade gestört. '
                  + 'Bestätige am Gerät wie gewohnt und lade diese Seite danach neu.');
        return;
      }
      planen();
      return;
    }

    switch (antwort.zustand) {
      case 'wartet':
        rest = parseInt(antwort.rest_s, 10) || 0;
        if (anzeige) { anzeige.textContent = restText(rest); }
        if (herkunft && antwort.wert_ok) {
          schritt('geraet');
        } else if (herkunft && antwort.n_g && !wertGezeigt) {
          await wertZeigen(antwort.n_g);
          if (!laeuft) { return; }
        }
        if (rest <= 0) {
          /* BEDINGUNG 3, zweiter Teil: Die Frist ist die harte Grenze. Der
           * Server sagt dasselbe beim naechsten Mal von selbst; hier wird
           * nicht darauf gewartet. */
          anhalten();
          schlussText('Die Zeit ist abgelaufen. Hol dir am Gerät einen neuen Code.');
          return;
        }
        planen();
        return;

      case 'gekoppelt':
        /* BEDINGUNG 6, und beide Haelften sind gemessen.
         *
         * KEIN reload(): Diese Karte steht im Regelfall auf einer Seite, die
         * aus einer Umleitung kam — dort waere Neuladen harmlos. Sie steht
         * aber AUCH auf der Antwort eines POST, wenn jemand waehrend des
         * Wartens ein anderes Geraet umbenennt, deaktiviert oder loescht.
         * Ein reload() haette dann „Formular erneut senden?" gefragt und im
         * schlimmsten Fall das Loeschen wiederholt.
         *
         * UND DAS ZIEL TRAEGT KEIN FRAGMENT: Die wartende Seite steht unter
         * `…?t=geraete#koppeln`. Eine Navigation, die nur das Fragment
         * aendert, ist KEINE Navigation — der Browser scrollt und fragt den
         * Server nicht (nachgemessen: „#koppeln → #geraeteliste" laedt nicht,
         * „#koppeln → ohne Fragment" laedt). Die Karte haette weiter auf ein
         * Geraet gewartet, das schon in der Liste stand. */
        anhalten();
        window.location.assign(ziel);
        return;

      case 'verworfen':
        anhalten();
        schlussText('Am Gerät wurde die Kopplung abgelehnt. Es ist nichts geschehen — '
                  + 'wenn du es doch verbinden willst, hol dir dort einen neuen Code.');
        return;

      case 'abgelehnt':
        fehlerZeigen(null);
        return;

      case 'abgelaufen':
        anhalten();
        schlussText('Die Zeit ist abgelaufen. Hol dir am Gerät einen neuen Code.');
        return;

      default:
        /* 'keine' und alles Unbekannte: Diese Seite wartet nicht mehr auf
         * etwas, das der Server kennt. Stillschweigend aufhoeren — die Karte
         * sagt beim naechsten Aufbau ohnehin das Richtige. */
        anhalten();
        return;
    }
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && laeuft) { fragen(); }
  });

  /* Der erste Ruf kommt nach dem vollen Takt, nicht sofort: Die Seite wurde
   * gerade vom Server gebaut, ihre Auskunft ist keine Sekunde alt. Ein Ruf
   * beim Laden fragte dasselbe noch einmal. */
  planen();

})();
