/**
 * Der Rückweg beim Zweitfaktor — das Schlüsselpaar (Konzept RW, RW-02;
 * E-RW-02, -03, -06).
 *
 * ===========================================================================
 * WAS HIER ENTSTEHT
 * ===========================================================================
 *
 * Ein ECDSA-Paar auf P-256, im Browser, über WebCrypto (E-RW-03):
 *
 *   öffentlicher Teil  SPKI, Base64            → users.rw_oeffentlich
 *   privater Teil      PKCS8, Base64, verpackt → users.rw_privat
 *                      mit dem INHALTSSCHLÜSSEL (`EdCrypto.encrypt(ck, …)`,
 *                      Kennung `edk1:`)
 *
 * Beim Rückweg (RW-03) läuft es rückwärts: Der Wiederherstellungsschlüssel
 * öffnet die Wiederherstellungs-Hülle und liefert den Inhaltsschlüssel, der
 * öffnet den privaten Teil, und der signiert die Herausforderung des
 * Servers. Der Server sieht davon nur den öffentlichen Teil und eine
 * Signatur.
 *
 * `encrypt()` UND NICHT `huelleBauen()`: Der private Teil hängt am
 * Inhaltsschlüssel, nicht am Server-Anteil. Er muss mit dem
 * Wiederherstellungsschlüssel ALLEIN aufgehen, wie `pat_wrap_rc`; der Server
 * weist `edka1:` hier ab (`RW_PRIVAT_RE`, E-RW-05).
 *
 * ===========================================================================
 * DREI AUFRUFER
 * ===========================================================================
 *
 *   anlegen()   still, nach der Anmeldung — `unlock.js` ruft es, wenn das
 *               Konto kein Paar hat (`RW_STAND === 'fehlt'`, E-RW-02). Das
 *               Token stammt aus dem Vormerkfach der Anmeldung. Wirft nie:
 *               Ein Fehlschlag kostet nichts, der nächste Entsperrvorgang
 *               nach einer Anmeldung versucht es erneut.
 *   erneuern()  der Knopf „Rückweg erneuern" unter Einstellungen → Profil
 *               (E-RW-06). Fragt das Passwort ab, öffnet den
 *               Inhaltsschlüssel über `EdSchluessel.oeffnen()` — dieselbe
 *               Wache gegen `pat_key_check` wie „Neuen
 *               Wiederherstellungsschlüssel erzeugen" — und ersetzt.
 *   signieren() der Rückweg selbst, am Code-Schritt der Anmeldung (RW-03,
 *               `login.php?weg=schluessel`): Zettel → Wiederherstellungs-
 *               Hülle → Inhaltsschlüssel → privater Teil → Signatur über die
 *               Nachricht, die der SERVER gebaut hat (`RW_NACHRICHT`,
 *               E-RW-04, -18). Der Zettel verlässt den Browser nie; passt er
 *               nicht, wird NICHTS gesendet.
 *
 * Erwartet: EdCrypto (crypto.js), EdApi (api.js, im Kopf jeder Seite); für
 * erneuern() zusätzlich EdSchluessel (schluessel.js). Alle werden zur
 * AUFRUFZEIT gelesen, nie beim Laden.
 */
(function () {
  'use strict';

  function base64(puffer) {
    const b = new Uint8Array(puffer);
    let s = '';
    for (let i = 0; i < b.length; i++) { s += String.fromCharCode(b[i]); }
    return btoa(s);
  }

  /**
   * Ein neues Paar, der private Teil mit `ck` verpackt.
   * @param {string} ck  der Inhaltsschlüssel (hex)
   * @returns {Promise<{oeffentlich: string, privat: string}>}
   */
  async function paarErzeugen(ck) {
    const kp = await crypto.subtle.generateKey({ name: 'ECDSA', namedCurve: 'P-256' },
                                               true, ['sign', 'verify']);
    const spki  = base64(await crypto.subtle.exportKey('spki', kp.publicKey));
    const pkcs8 = base64(await crypto.subtle.exportKey('pkcs8', kp.privateKey));
    return { oeffentlich: spki, privat: await EdCrypto.encrypt(ck, pkcs8) };
  }

  /**
   * Das Paar an den Server.
   * @returns {Promise<{ok: boolean, status: number, daten: ?Object, meldung: string}>}
   */
  function paarSenden(paar, token, ersetzen, vorgang) {
    return EdApi.postJson('api/rueckweg_anlegen.php', {
      token:       token,
      oeffentlich: paar.oeffentlich,
      privat:      paar.privat,
      ersetzen:    ersetzen ? 1 : 0,
    }, vorgang ? { vorgang: vorgang } : undefined);
  }

  /** Still, nach der Anmeldung. Liefert true, wenn ein Paar abgelegt ist. */
  async function anlegen(ck, token) {
    try {
      const antw = await paarSenden(await paarErzeugen(ck), token, false);
      return antw.ok;
    } catch (e) {
      /* Bewusst still — siehe Kopf. Was hier landet, sind die
       * Krypto-Schritte: `EdApi` selbst wirft nicht. */
      return false;
    }
  }

  /**
   * „Rückweg erneuern".
   * @param {object} o  wie EdSchluessel.oeffnen(): pw, salt, iter, wrapPw,
   *                    keyCheck, anteile
   * @throws {Error} mit einer Meldung, die man anzeigen kann
   */
  async function erneuern(o) {
    const { ck, token } = await EdSchluessel.oeffnen(o);
    const antw = await paarSenden(await paarErzeugen(ck), token, true,
                                  'Die Erneuerung des Rückwegs');
    if (!antw.ok) { throw new Error(antw.meldung); }
  }

  /**
   * Den Rückweg gehen: die Nachricht des Servers signieren (RW-03).
   *
   * @param {object} o
   *   schluessel  der Wiederherstellungsschlüssel, wie getippt
   *   wrapRc      `pat_wrap_rc` — der Inhaltsschlüssel unter dem Zettel
   *   privat      `rw_privat` — der private Teil unter dem Inhaltsschlüssel
   *   nachricht   `RW_NACHRICHT`, gebaut von `rw_nachricht()` auf dem Server
   * @returns {Promise<string>} die Signatur, 64 Byte `r‖s`, Base64
   * @throws {Error} `grund` 'form' (Tippfehler — die Meldung sagt welcher),
   *   'passt' (der Zettel öffnet die Hülle nicht) oder 'paar' (die Hülle
   *   geht auf, der private Teil nicht — ein Paar aus einer früheren
   *   Einrichtung)
   */
  async function signieren(o) {
    const p = EdCrypto.pruefeRecoveryCode(o.schluessel);
    if (!p.ok) {
      const f = new Error(EdCrypto.recoveryCodeMeldung(p));
      f.grund = 'form';
      throw f;
    }
    const rk = await EdCrypto.recoveryKeyHex(o.schluessel);
    let ck;
    try {
      ck = await EdCrypto.decrypt(rk, o.wrapRc);
    } catch (e) {
      const f = new Error('passt');
      f.grund = 'passt';
      throw f;
    }
    let pkcs8;
    try {
      pkcs8 = await EdCrypto.decrypt(ck, o.privat);
    } catch (e) {
      const f = new Error('paar');
      f.grund = 'paar';
      throw f;
    }
    const der = Uint8Array.from(atob(pkcs8), c => c.charCodeAt(0));
    const schluessel = await crypto.subtle.importKey('pkcs8', der,
      { name: 'ECDSA', namedCurve: 'P-256' }, false, ['sign']);
    const sig = await crypto.subtle.sign({ name: 'ECDSA', hash: 'SHA-256' }, schluessel,
                                         new TextEncoder().encode(o.nachricht));
    return base64(sig);
  }

  window.EdRueckweg = { paarErzeugen: paarErzeugen, paarSenden: paarSenden,
                        anlegen: anlegen, erneuern: erneuern, signieren: signieren };
})();
