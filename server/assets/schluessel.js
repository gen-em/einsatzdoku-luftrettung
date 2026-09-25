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
 * Die Schritte 1 bis 3 stehen seit Konzept RW (RW-02) als `oeffnen()` für
 * sich: „Rückweg erneuern" (`assets/rueckweg.js`) braucht denselben
 * Inhaltsschlüssel mit derselben Wache, und eine zweite Fassung davon wäre
 * eine zweite Stelle, an der sie vergessen werden kann.
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
   *   csrf        WIRD NICHT MEHR GELESEN -- und bleibt trotzdem stehen.
   *               Seit Schritt 15 (AP8) geht Schritt 5 ueber
   *               `EdApi.postForm()`, und das setzt das Feld `csrf` selbst,
   *               aus der globalen `CSRF`. Genau daher nehmen ihn auch die
   *               beiden Aufrufer -- dieselbe Quelle, derselbe Wert, also
   *               zeichengleich. Gestrichen wird der Parameter dennoch
   *               nicht: Das waere eine Schnittstellenaenderung an einer
   *               Komponente mit zwei Verbrauchern, und dieses Paket aendert
   *               kein Verhalten. Wer ihn weiter uebergibt, liegt nicht
   *               falsch, sondern wirkungslos.
   * @returns {Promise<string>} der neue Wiederherstellungscode
   * @throws {Error} mit einer Meldung, die man anzeigen kann
   */
  async function erneuern(o) {
    const { ck, token } = await oeffnen(o);

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
    /* UEBER EdApi (Schritt 15, AP8). Das Feld `csrf` haengt EdApi selbst an,
     * die Erfolgsregel (HTTP UND Fachschluessel) liegt dort, und ein
     * Netzfehler kommt als `status: 0` zurueck statt als Wurf -- der Aufrufer
     * sieht dadurch einen deutschen Satz, wo bisher der Browsertext
     * „Failed to fetch" stand.
     *
     * EdApi NICHT beim Laden in eine Variable nehmen: Auf einstellungen.php
     * kommt `api.js` spaeter als diese Datei (siehe Kopf von assets/api.js).
     * Hier steht der Zugriff in der Funktion und damit erst beim Klick. */
    const antw = await EdApi.postForm('api/schluessel_erneuern.php', {
      token:   token,
      wrap_rc: wrapRc,
    }, { vorgang: 'Die Erneuerung' });

    if (!antw.ok) {
      /* DER SATZ KOMMT FERTIG AUS EdApi. Dieser Endpunkt nennt sein
       * Satzfeld `text` statt `meldung`; die Vorrangkette in
       * `grund()` (assets/api.js) liest es seit AP8 mit, und der
       * Vorgangsname steht davor. Hier stand bis zum Gegenlesen ein
       * eigener Griff auf `antw.daten.text`, der genau diesen
       * Vorgangsnamen wieder verlor. */
      throw new Error(antw.meldung);
    }

    /* ---- 6 ------------------------------------------------------------- */
    return rc;
  }

  /**
   * Die Schritte 1 bis 3: Passwort → Inhaltsschlüssel, gehalten gegen
   * `pat_key_check`. Seit Konzept RW (RW-02) eine eigene Funktion, weil ein
   * zweiter Vorgang sie braucht: „Rückweg erneuern" (`assets/rueckweg.js`)
   * verpackt mit diesem Inhaltsschlüssel ein neues Schlüsselpaar. Die Wache
   * steht damit EINMAL, für beide.
   *
   * @param {object} o  wie erneuern()
   * @returns {Promise<{ck: string, token: string}>} der Inhaltsschlüssel und
   *   das Anmelde-Token — der Nachweis des Passworts für den Server
   * @throws {Error} mit einer Meldung, die man anzeigen kann
   */
  async function oeffnen(o) {
    if (!o.keyCheck) {
      /* Siehe Kopf: ohne Maßstab keine Erneuerung. */
      throw new Error('Für dieses Konto ist keine Prüfsumme des '
                    + 'Inhaltsschlüssels hinterlegt. Die Erneuerung ist hier '
                    + 'nicht möglich — bitte an die BetreiberIn wenden.');
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
                    + 'neu laden und die BetreiberIn verständigen.');
    }

    return { ck: ck, token: k.authToken };
  }

  window.EdSchluessel = { erneuern: erneuern, oeffnen: oeffnen };
})();
