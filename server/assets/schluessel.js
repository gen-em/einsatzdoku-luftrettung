/**
 * Wiederherstellungsschlüssel erneuern — EINE Funktion, zwei Aufrufer
 * (P5b/AP9, E-P5b-20).
 *
 * ===========================================================================
 * WAS SIE TUT, IN DER REIHENFOLGE, IN DER ES WICHTIG IST
 * ===========================================================================
 *
 *   1. Aus dem Passwort den Datenschlüssel ableiten
 *   2. Damit `pat_wrap_pw` öffnen -> der INHALTSSCHLÜSSEL (ck)
 *   3. **ck gegen `pat_key_check` halten** — die Wache, siehe unten
 *   4. Neuen Wiederherstellungscode erzeugen, ck damit neu verpacken
 *   5. Beides an `api/schluessel_erneuern.php`
 *   6. Den Code zurückgeben, damit ihn jemand aufschreiben kann
 *
 * **Der Inhaltsschlüssel ändert sich nicht.** Er ist der Schlüssel, mit dem
 * jeder Datensatz verschlüsselt ist; was sich ändert, ist allein das Schloss,
 * das der Zettel öffnet. Deshalb öffnet der alte Zettel danach nichts mehr
 * und der neue alles — und deshalb bleibt jedes gespeicherte Datum lesbar.
 *
 * ===========================================================================
 * SCHRITT 3 IST DIE STELLE, AN DER DATEN VERLOREN GEHEN KÖNNTEN
 * ===========================================================================
 *
 * `pat_key_check` ist die Prüfsumme des Inhaltsschlüssels, gesetzt beim
 * ersten Passwort und seither der Maßstab. Öffnete Schritt 2 aus irgendeinem
 * Grund einen ANDEREN Schlüssel — ein Fehler in der Ableitung, ein halb
 * geschriebener Wrap, ein Konto in einer Umstellung —, dann verpackte
 * Schritt 4 diesen falschen Schlüssel, der Server nähme ihn an, und der neue
 * Zettel öffnete danach **nichts**. Der alte wäre schon überschrieben.
 *
 * Das ist der einzige Weg, auf dem dieses Paket Daten unzugänglich machen
 * könnte, und deswegen steht die Prüfung hier und wird bei einer Abweichung
 * abgebrochen, bevor irgendetwas gesendet wird.
 *
 * ALTBESTAND OHNE PRÜFSUMME: Konten von vor Web 10 tragen `pat_key_check` als
 * NULL. Dort kann nicht geprüft werden — dann wird auch nicht erneuert,
 * sondern gesagt, warum. Lieber ein Konto, das diese Funktion nicht hat, als
 * eines, das sie ungeprüft benutzt.
 *
 * ===========================================================================
 * WARUM DAS PASSWORT UND NICHT DER SCHLÜSSEL AUS DER SITZUNG
 * ===========================================================================
 *
 * Eine entsperrte Sitzung hätte den ck bereits (`EdKeyGuard.contentKey()`).
 * Das Passwort wird trotzdem verlangt, weil der SERVER es verlangt: Wer eine
 * fremde Sitzung übernimmt, könnte sonst den Zettel des Opfers ungültig
 * machen — kein Datenklau, aber der lautlose Verlust des Rückwegs. Die
 * Begründung steht ausführlich im Kopf des Endpunkts.
 *
 * Nebeneffekt, der die Sache einfacher macht: Diese Funktion arbeitet auch in
 * einer GESPERRTEN Sitzung, weil sie nichts aus `sessionStorage` braucht.
 */
(function () {
  'use strict';

  /**
   * @param {object} o
   *   pw          das eingegebene Passwort
   *   salt, iter  Ableitungsparameter dieses Kontos
   *   wrapPw      `pat_wrap_pw`
   *   keyCheck    `pat_key_check` (null bei Altbestand)
   *   anteile     { kennung: hex } — die ausgelieferten Anteile. Welcher
   *               davon gebraucht wird, sagt das Präfix von `wrapPw`; diese
   *               Funktion bekommt deshalb KEINE Zielkennung.
   *   csrf        Token für den Endpunkt
   * @returns {Promise<string>} der neue Wiederherstellungscode
   * @throws {Error} mit einer Meldung, die man anzeigen kann
   */
  async function erneuern(o) {
    if (!o.keyCheck) {
      /* Siehe Kopf: ohne Maßstab keine Erneuerung. */
      throw new Error('Für dieses Konto ist keine Prüfsumme des '
                    + 'Inhaltsschlüssels hinterlegt. Die Erneuerung ist hier '
                    + 'nicht möglich — bitte an den Betreiber wenden.');
    }

    /* ---- 1 + 2: Passwort -> Datenschlüssel -> Inhaltsschlüssel ------------ */
    const k  = await EdCrypto.deriveKeys(o.pw, o.salt, o.iter);
    /* `datenschluessel()` UND NICHT `datenschluesselZu()`.
     *
     * Der Unterschied ist eine Zeile und entscheidet über eine ganze
     * Betriebslage: `datenschluessel()` liest die Kennung aus dem PRÄFIX DER
     * HÜLLE, die geöffnet werden soll; `datenschluesselZu()` erzwingt eine
     * genannte. Zum ÖFFNEN ist nur die erste richtig.
     *
     * Ich hatte hier zuerst die zweite stehen. Im Normalfall fällt das nicht
     * auf — beide liefern dasselbe. Während einer ANTEILSROTATION aber trägt
     * die alte Hülle noch den alten Anteil, und die Erneuerung wäre für jedes
     * Konto gescheitert, das sich seither nicht angemeldet hat. Also genau
     * dann, wenn jemand sie am ehesten braucht. */
    const dk = await EdCrypto.datenschluessel(k.haelfteHex, o.wrapPw, o.anteile);

    let ck;
    try {
      ck = await EdCrypto.huelleOeffnen(dk, o.wrapPw);
    } catch (e) {
      /* Das ist der NORMALE Fehlerfall: falsches Passwort. Er sieht hier
       * genauso aus wie ein kaputter Wrap, und das ist in Ordnung — der
       * Server prüft das Passwort ohnehin noch einmal und sagt es dann
       * genauer. Hier wird nur nicht weitergerechnet. */
      throw new Error('Das Passwort ist nicht korrekt.');
    }

    /* ---- 3: die Wache --------------------------------------------------- */
    const chk = await EdCrypto.contentKeyCheck(ck);
    if (chk !== o.keyCheck) {
      throw new Error('Der entpackte Schlüssel passt nicht zur hinterlegten '
                    + 'Prüfsumme. Es wurde NICHTS geändert — bitte die Seite '
                    + 'neu laden und den Betreiber verständigen.');
    }

    /* ---- 4: neuer Code, neue Hülle -------------------------------------- */
    const rc = EdCrypto.newRecoveryCode();
    const rk = await EdCrypto.recoveryKeyHex(rc);
    /* `encrypt()` UND NICHT `huelleBauen()` — die Wiederherstellungs-Hülle
     * bekommt KEINEN Anteil (E-S10-04). Sie ist der Rückweg für den Fall,
     * dass der Anteil verloren ist; hinge sie selbst daran, gäbe es keinen.
     * Der Server weist eine `edka1:`-Hülle hier ohnehin ab — aber die
     * richtige Stelle, sie gar nicht erst zu bauen, ist diese. */
    const wrapRc = await EdCrypto.encrypt(rk, ck);

    /* ---- 5: senden ------------------------------------------------------ */
    const leib = new URLSearchParams();
    leib.set('csrf', o.csrf);
    leib.set('token', k.authToken);
    leib.set('wrap_rc', wrapRc);

    const antw = await fetch('api/schluessel_erneuern.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: leib.toString(),
    });
    let daten = {};
    try { daten = await antw.json(); } catch (e) { /* gleich Fehler unten */ }

    if (!antw.ok || !daten.ok) {
      throw new Error(daten.text || 'Die Erneuerung ist fehlgeschlagen ('
                                  + antw.status + ').');
    }

    /* ---- 6 ------------------------------------------------------------- */
    return rc;
  }

  window.EdSchluessel = { erneuern: erneuern };
})();
