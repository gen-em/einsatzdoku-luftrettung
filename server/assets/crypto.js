/* Einsatzdoku — Ende-zu-Ende-Krypto für das PatientInnendaten-Modul.
 *
 * Prinzip (angelehnt an Bitwarden):
 *  - Aus dem Login-Passwort leitet der Browser per PBKDF2 (Rundenzahl je
 *    Konto, SHA-256, nutzerspezifisches Salt) 512 Bit ab und teilt sie:
 *      · dataKey  (256 Bit): bleibt IM BROWSER, verschlüsselt Daten
 *      · authToken (256 Bit): geht statt des Passworts zum Server
 *    Der Server sieht das Passwort damit nie und kann nichts entschlüsseln.
 *  - Ein zufälliger Inhaltsschlüssel (CK) verschlüsselt die eigentlichen
 *    Daten (AES-256-GCM). CK liegt doppelt verpackt auf dem Server:
 *    einmal mit dem dataKey, einmal mit dem Wiederherstellungsschlüssel.
 *  - Passwort ändern = CK neu verpacken; die Daten bleiben unangetastet.
 */
'use strict';
const EdCrypto = (() => {

  /* Rundenzahl der Ableitung — KEINE Konstante mehr (M2-01).
   *
   * Sie stand hier fest verdrahtet, und damit war ihre Aenderung eine
   * Aussperrung aller Bestandskonten: Aus demselben Passwort entstuende ein
   * anderes Token, und der gespeicherte Hash passte nicht mehr. Der Wert
   * kommt jetzt je Konto vom Server (users.kdf_iter).
   *
   * ITER_ALT ist nur noch fuer EINEN Zweck da: Sicherungsdateien im
   * Containerformat 2 tragen die Rundenzahl nicht im Kopf, dort galt immer
   * dieser Wert. Fuer die Kontoableitung darf er NICHT verwendet werden. */
  const ITER_ALT = 310000;
  const te = new TextEncoder(), td = new TextDecoder();

  /** Rundenzahl pruefen. Wirft statt zu raten — siehe deriveKeys. */
  function pruefeRunden(iter, wofuer) {
    if (!Number.isInteger(iter) || iter < 1000 || iter > 10000000) {
      throw new Error('Rundenzahl fehlt oder ist unbrauchbar (' + wofuer + '): ' + iter);
    }
    return iter;
  }

  /* ---- Helfer: hex / base64 ------------------------------------------- */
  const toHex = buf => [...new Uint8Array(buf)]
    .map(b => b.toString(16).padStart(2, '0')).join('');
  const fromHex = hex => new Uint8Array(
    (hex.match(/../g) || []).map(h => parseInt(h, 16)));
  const toB64 = buf => btoa(String.fromCharCode(...new Uint8Array(buf)));
  const fromB64 = s => Uint8Array.from(atob(s), c => c.charCodeAt(0));

  function randomHex(nBytes) {
    return toHex(crypto.getRandomValues(new Uint8Array(nBytes)));
  }

  /* ---- Schlüsselableitung aus dem Passwort ----------------------------
   *
   * DIE RUNDENZAHL IST PFLICHT UND HAT KEINEN VORGABEWERT (M2-01).
   *
   * Das ist die wichtigste Sicherung dieses Umbaus. Ein Vorgabewert würde
   * jede vergessene Aufrufstelle stillschweigend mit der alten Zahl rechnen
   * lassen — und weil heute fast alle Konten noch die alte Zahl tragen, fiele
   * das NICHT AUF. Es fiele erst an dem Tag auf, an dem jemand den Zielwert
   * anhebt, und dann als „Passwort falsch" bei Konten, die ihr Passwort
   * richtig eingegeben haben.
   *
   * Lieber ein lauter Fehler beim Entwickeln als ein leiser im Betrieb.
   */
  async function deriveKeys(password, saltHex, iter) {
    pruefeRunden(iter, 'deriveKeys');
    const base = await crypto.subtle.importKey(
      'raw', te.encode(password), 'PBKDF2', false, ['deriveBits']);
    const bits = await crypto.subtle.deriveBits(
      { name: 'PBKDF2', hash: 'SHA-256', salt: fromHex(saltHex), iterations: iter },
      base, 512);
    const all = new Uint8Array(bits);
    return {
      dataKeyHex: toHex(all.slice(0, 32)),     // bleibt lokal
      authToken:  toHex(all.slice(32, 64))     // ersetzt das Passwort zum Server
    };
  }

  /* ---- AES-256-GCM ----------------------------------------------------- */
  async function aesKey(keyHex, usages) {
    return crypto.subtle.importKey('raw', fromHex(keyHex),
      { name: 'AES-GCM' }, false, usages);
  }

  // Klartext (String) -> base64(iv || ciphertext)
  async function encrypt(keyHex, plaintext) {
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const key = await aesKey(keyHex, ['encrypt']);
    const ct = await crypto.subtle.encrypt({ name: 'AES-GCM', iv },
      key, te.encode(plaintext));
    const out = new Uint8Array(iv.length + ct.byteLength);
    out.set(iv); out.set(new Uint8Array(ct), iv.length);
    return toB64(out);
  }

  // base64(iv || ciphertext) -> Klartext; wirft bei falschem Schlüssel
  async function decrypt(keyHex, blobB64) {
    const raw = fromB64(blobB64);
    const key = await aesKey(keyHex, ['decrypt']);
    const pt = await crypto.subtle.decrypt(
      { name: 'AES-GCM', iv: raw.slice(0, 12) }, key, raw.slice(12));
    return td.decode(pt);
  }

  /* ---- Wiederherstellungsschlüssel ------------------------------------ */
  // 20 Zufallsbytes als Gruppen à 4 (Base32 ohne 0/O/1/I) — einmalig zeigen!
  const RC_CHARS = 'ABCDEFGHJKMNPQRSTVWXYZ23456789';
  function newRecoveryCode() {
    const raw = crypto.getRandomValues(new Uint8Array(20));
    let s = '';
    for (let i = 0; i < 20; i++) {
      s += RC_CHARS[raw[i] % RC_CHARS.length];
      if (i % 4 === 3 && i < 19) s += '-';
    }
    return s;
  }
  const RC_LEN = 20;   // 20 Zeichen aus RC_CHARS, Gruppen à 4 (rund 98 Bit)

  /* Die im Alphabet ausgelassenen Zeichen, aus RC_CHARS abgeleitet statt
   * danebengeschrieben — sonst stimmt die Meldung nach der nächsten Änderung
   * des Alphabets nicht mehr. Ergibt heute: 0, 1, I, L, O und U. */
  const RC_UNGENUTZT = (() => {
    const fehlt = [];
    for (const c of 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
      if (RC_CHARS.indexOf(c) < 0) { fehlt.push(c); }
    }
    return fehlt.length > 1
      ? fehlt.slice(0, -1).join(', ') + ' und ' + fehlt[fehlt.length - 1]
      : fehlt.join('');
  })();

  /**
   * Prüft einen eingegebenen Wiederherstellungsschlüssel (M2-06).
   *
   * WARUM DAS NÖTIG IST
   * Normalisiert und gehasht wurde bisher alles, was ankam — ohne zu prüfen,
   * ob die Länge stimmt oder die Zeichen überhaupt aus dem Alphabet stammen.
   * Das Alphabet lässt die klassischen Verwechslungszeichen bewusst weg
   * (0/O, 1/I/L, U). Tippt jemand eines davon, bleibt es nach der
   * Normalisierung stehen, ergibt einen anderen Hashwert und damit einen
   * falschen Schlüssel. Die Meldung lautete dann „passt nicht" — dieselbe
   * Meldung wie bei einem falschen Zettel.
   *
   * Und das passiert in genau der Lage, in der jemand ohnehin unter Druck
   * steht: Er hat sein Passwort verloren und tippt seinen letzten Zettel ab.
   *
   * NICHT nötig ist eine Streckung der Ableitung: 30^20 sind rund 98 Bit;
   * die Verzerrung durch die Restklassenbildung in newRecoveryCode() kostet
   * davon rechnerisch 0,0 Bit. Ein einfacher Hash genügt.
   *
   * @returns {{ok:boolean, code:string, grund:string}} `grund` unterscheidet
   *          'leer', 'zeichen', 'kurz', 'lang' — der Aufrufer kann daraus
   *          eine Meldung machen, die dem Menschen weiterhilft.
   */
  function pruefeRecoveryCode(code) {
    // Normalisierung wie in recoveryKeyHex: Großschreibung, Trennzeichen und
    // Leerraum weg. Sie ist gewollt großzügig — Bindestriche und Leerzeichen
    // sind eine Lesehilfe, kein Teil des Schlüssels.
    const norm = String(code == null ? '' : code).toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (norm === '') { return { ok: false, code: norm, grund: 'leer' }; }
    for (const c of norm) {
      if (RC_CHARS.indexOf(c) < 0) {
        return { ok: false, code: norm, grund: 'zeichen', zeichen: c };
      }
    }
    if (norm.length < RC_LEN) { return { ok: false, code: norm, grund: 'kurz' }; }
    if (norm.length > RC_LEN) { return { ok: false, code: norm, grund: 'lang' }; }
    return { ok: true, code: norm, grund: '' };
  }

  /** Meldung zu einem Prüfergebnis — an einer Stelle formuliert, damit
   *  Sofortprüfung im Feld und Prüfung beim Absenden dasselbe sagen. */
  function recoveryCodeMeldung(pruef) {
    if (pruef.ok) { return ''; }
    switch (pruef.grund) {
      case 'leer':
        return 'Bitte den Wiederherstellungsschlüssel eingeben.';
      case 'zeichen':
        /* WELCHES Zeichen fehlt, ist die eigentliche Auskunft. Welches
         * stattdessen gemeint ist, sagen wir bewusst NICHT: Das Alphabet
         * lässt beide Seiten jeder klassischen Verwechslung weg (0 UND O,
         * 1 UND I UND L, dazu U) — ein Rateversuch führte hier eher in die
         * Irre, als dass er hülfe. Die Liste stammt aus RC_CHARS, damit sie
         * nicht bei der nächsten Änderung des Alphabets falsch wird. */
        return `Das Zeichen „${pruef.zeichen}" kommt im Wiederherstellungsschlüssel `
             + `nicht vor — ${RC_UNGENUTZT} werden nicht verwendet, weil sie zu leicht `
             + 'zu verwechseln sind. Bitte diese Stelle auf dem Zettel noch einmal ansehen.';
      case 'kurz':
        return `Der Schlüssel ist unvollständig: ${pruef.code.length} von ${RC_LEN} `
             + 'Zeichen (Bindestriche zählen nicht mit).';
      case 'lang':
        return `Der Schlüssel ist zu lang: ${pruef.code.length} statt ${RC_LEN} `
             + 'Zeichen (Bindestriche zählen nicht mit).';
      default:
        return 'Der Wiederherstellungsschlüssel ist nicht lesbar.';
    }
  }

  /* Aus dem (normalisierten) Code einen AES-Schlüssel machen.
   *
   * Prüft VOR der Ableitung (M2-06) und wirft bei einer unbrauchbaren
   * Eingabe — sonst entsteht klaglos ein Schlüssel, der nur eben nicht
   * passt, und der Unterschied zwischen Tippfehler und falschem Zettel geht
   * verloren. Wer die Unterscheidung selbst anzeigen will, ruft vorher
   * pruefeRecoveryCode() auf. */
  async function recoveryKeyHex(code) {
    const p = pruefeRecoveryCode(code);
    if (!p.ok) {
      const fehler = new Error(recoveryCodeMeldung(p));
      fehler.name = 'RecoveryCodeFehler';
      fehler.grund = p.grund;
      throw fehler;
    }
    const d = await crypto.subtle.digest('SHA-256', te.encode('edk-rc:' + p.code));
    return toHex(d);
  }

  /* ---- Prüfsumme des Inhaltsschlüssels (Baustein B4) ------------------
   * Ein Baustein für drei Zwecke:
   *   (a) Passwortwechsel: Stimmt die mitgesendete Prüfsumme nicht mit der
   *       gespeicherten überein, wird abgelehnt und nichts geändert. Ohne das
   *       kann ein Fehler im Browser eine Hülle speichern, die einen ANDEREN
   *       Inhaltsschlüssel enthält — danach ist jeder vorhandene Datensatz
   *       unlesbar, und zwar endgültig.
   *   (b) Bindung des zwischengespeicherten Schlüssels (siehe keyguard.js).
   *   (c) Einspielen einer Sicherung: erkennt, ob ein mitgeführter
   *       Chiffretext aus demselben Konto stammt.
   *
   * WAS DER SERVER DADURCH LERNT: nichts. Der Inhaltsschlüssel ist 256 Bit
   * Zufall; aus seinem Hashwert lässt er sich nicht zurückrechnen, und
   * Durchprobieren scheidet bei 256 Bit aus. Der Server gewinnt ausschließlich
   * die Fähigkeit, den einen Fehler zu erkennen, der alles kostet.
   *
   * 128 Bit (32 Hexzeichen) genügen: Es geht um das Erkennen einer
   * Verwechslung, nicht um eine Signatur.
   */
  async function contentKeyCheck(ckHex) {
    if (!ckHex) return null;
    const d = await crypto.subtle.digest('SHA-256', te.encode('edk-ckchk:' + ckHex));
    return toHex(d).slice(0, 32);
  }

  /* Kurze Kennung einer Schlüsselhülle — für die Bindung in keyguard.js.
   * Sie steht nur im Browser und geht nie zum Server. */
  async function wrapFingerprint(wrap) {
    if (!wrap) return null;
    const d = await crypto.subtle.digest('SHA-256', te.encode('edk-wrap:' + wrap));
    return toHex(d).slice(0, 16);
  }

  /* ---- Sitzung: dataKey / Inhaltsschlüssel ---------------------------- */
  const S_DK = 'edk', S_CK = 'pck';
  const setDataKey = hex => sessionStorage.setItem(S_DK, hex);
  const getDataKey = () => sessionStorage.getItem(S_DK);
  /* Einen bereits entpackten Inhaltsschlüssel ablegen.
   *
   * Sonst entsteht er ausschließlich in getContentKey() aus Hülle und
   * Datenschlüssel. Genau das geht in einem Fall nicht: Direkt nach der
   * stillen Anhebung (M2-01) hat der Browser den neuen Datenschlüssel, die
   * Seite trägt aber noch die ALTE Hülle — sie wurde gerendert, bevor die
   * Anhebung lief. Ein Entpacken müsste dort scheitern, und der
   * Entsperrdialog erschiene unmittelbar nach dem Anmelden.
   *
   * Der Schlüssel ist in diesem Moment aber bekannt: Er wurde eine Zeile
   * vorher mit dem alten Datenschlüssel entpackt, um ihn neu zu verpacken. */
  const setContentKey = hex => sessionStorage.setItem(S_CK, hex);
  async function getContentKey(wrapPw) {
    let ck = sessionStorage.getItem(S_CK);
    if (ck) return ck;
    const dk = getDataKey();
    if (!dk || !wrapPw) return null;
    try {
      ck = await decrypt(dk, wrapPw);          // CK liegt als Hex im Wrap
      sessionStorage.setItem(S_CK, ck);
      return ck;
    } catch (e) { return null; }               // Wrap passt nicht (z. B. nach Reset)
  }
  /* ---- Vormerkfach für die Anmeldung (M2-01) ---------------------------
   *
   * WARUM ES DAS BRAUCHT
   * Die Anmeldung leitet für mehrere Rundenzahlen ab, weiß aber nicht, welche
   * für dieses Konto gilt — das darf der Salz-Endpunkt nicht verraten. Erst
   * die nächste, angemeldete Seite kennt den Wert. Zwischen beiden liegt ein
   * Seitenwechsel, und der löscht alles, was nur im Speicher stand.
   *
   * WAS DARIN LIEGT
   * Je Rundenzahl der Datenschlüssel UND das Auth-Token. Der Datenschlüssel
   * liegt dort ohnehin schon (S_DK) — er entschlüsselt alle geschützten
   * Angaben. Das Token kommt hinzu, weil die stille Anhebung es als Nachweis
   * braucht: Ohne ihn könnte, wer eine offene Sitzung übernimmt, ein
   * beliebiges neues Token setzen — das wäre nichts anderes als eine
   * Passwortänderung ohne Kenntnis des Passworts.
   *
   * WIE LANGE
   * Einen Seitenwechsel. Die erste Seite, die den Inhaltsschlüssel braucht,
   * räumt das Fach ab (unlock.js). Beim Abmelden und bei jedem Anmeldeversuch
   * wird es geleert.
   */
  const S_VOR = 'edkvor';

  function merkeAbleitungen(datenschluessel, tokens) {
    sessionStorage.setItem(S_VOR, JSON.stringify({ dk: datenschluessel, tk: tokens }));
  }
  function holeAbleitungen() {
    try {
      const o = JSON.parse(sessionStorage.getItem(S_VOR) || 'null');
      return (o && o.dk && o.tk) ? o : null;
    } catch (e) { return null; }
  }
  const vergissAbleitungen = () => sessionStorage.removeItem(S_VOR);

  const clearSession = () => {
    sessionStorage.removeItem(S_DK);
    sessionStorage.removeItem(S_CK);
    vergissAbleitungen();
  };

  /* ---- Backup-Container ------------------------------------------------
   *
   * FASSUNG 3 (seit Web 5.0.0, S7)
   *   "EDBAK2" 0x00 0x03 | Flag(1) | Runden(4, big endian) | Salt(16) | IV(12) | AES-GCM
   *   AAD: die ersten 13 Bytes (Magie + Flag + Runden)
   *
   * FASSUNG 2 (bis Web 4.7.0) — wird weiterhin GELESEN
   *   "EDBAK2" 0x00 0x02 | Flag(1) | Salt(16) | IV(12) | AES-GCM
   *   AAD: die ersten 9 Bytes. Rundenzahl: immer 310 000, nirgends vermerkt.
   *
   * Flag: 1 = Inhalt gzip-komprimiert, 0 = roh
   * Schlüssel: PBKDF2-SHA256(Backup-Passwort, Salt, Runden, 256 Bit)
   *
   * WARUM DIE RUNDENZAHL IN DEN KOPF MUSSTE (S7)
   * Sie stand nur als Konstante im Code. Wer sie anhebt, macht damit JEDE
   * bereits erzeugte Sicherungsdatei unlesbar — und zwar ohne Fehlermeldung,
   * die den Grund nennt: Es sähe aus wie ein falsches Passwort. Sicherungen
   * werden aber gerade für den Fall aufbewahrt, dass etwas schiefgeht; eine
   * Datei, die genau dann nicht mehr aufgeht, ist keine.
   *
   * Die Rundenzahl steht deshalb in der Datei und wird von dort gelesen. Alte
   * Dateien bleiben lesbar, weil ihre Fassungsnummer die fehlende Angabe
   * ersetzt.
   *
   * Der Inhalt ist bereits KLARTEXT: Der Browser entschlüsselt vor dem
   * Versiegeln, damit sich das Backup in jedes Konto einspielen lässt.
   */
  const MAGIC_PRAEFIX = new Uint8Array([69, 68, 66, 65, 75, 50]);   // "EDBAK2"
  const CONTAINER_VERSION = 3;

  async function fileKey(password, salt, iter) {
    pruefeRunden(iter, 'fileKey');
    const base = await crypto.subtle.importKey('raw', te.encode(password),
      'PBKDF2', false, ['deriveBits']);
    const bits = await crypto.subtle.deriveBits(
      { name: 'PBKDF2', salt, iterations: iter, hash: 'SHA-256' }, base, 256);
    return crypto.subtle.importKey('raw', bits, 'AES-GCM', false, ['encrypt', 'decrypt']);
  }

  async function gzip(bytes) {
    if (typeof CompressionStream === 'undefined') return null;
    const s = new Blob([bytes]).stream().pipeThrough(new CompressionStream('gzip'));
    return new Uint8Array(await new Response(s).arrayBuffer());
  }
  async function gunzip(bytes) {
    /* Verfuegbarkeit pruefen wie beim Packen (M2-11).
     *
     * gzip() oben fragt seit jeher nach, ob CompressionStream existiert, und
     * legt die Sicherung sonst ungepackt an. gunzip() fragte nicht — auf
     * einem Browser ohne DecompressionStream endete das Oeffnen einer
     * gepackten Sicherung in einem ReferenceError, der weiter oben als
     * "Passwort falsch oder Datei beschaedigt" ankam.
     *
     * Das ist die denkbar irrefuehrendste Auskunft: Die Datei ist in Ordnung,
     * das Passwort stimmt, und die betroffene Person tippt es zehnmal neu.
     * Der Fall trifft ausserdem genau die aelteren Browser, auf denen die
     * Sicherung urspruenglich ungepackt entstanden waere — beim Wechsel des
     * Geraets ist er also nicht abwegig. */
    if (typeof DecompressionStream === 'undefined') {
      throw new Error('Dieser Browser kann gepackte Sicherungen nicht öffnen. '
                    + 'Bitte einen aktuellen Browser verwenden — die Datei ist in Ordnung.');
    }
    const s = new Blob([bytes]).stream().pipeThrough(new DecompressionStream('gzip'));
    return new Uint8Array(await new Response(s).arrayBuffer());
  }

  /** Erzeugt eine Sicherungsdatei in der aktuellen Fassung (3). */
  async function sealBackup(password, jsonText, iter) {
    pruefeRunden(iter, 'sealBackup');
    const raw = te.encode(jsonText);
    const packed = await gzip(raw);
    const flag = packed ? 1 : 0;
    const body = packed || raw;
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const iv = crypto.getRandomValues(new Uint8Array(12));
    // Kopf: 6 Magie + 2 Fassung + 1 Flag + 4 Runden = 13
    const head = new Uint8Array(13);
    head.set(MAGIC_PRAEFIX, 0);
    head[6] = 0; head[7] = CONTAINER_VERSION;
    head[8] = flag;
    new DataView(head.buffer).setUint32(9, iter, false);   // big endian
    const key = await fileKey(password, salt, iter);
    const ct = new Uint8Array(await crypto.subtle.encrypt(
      { name: 'AES-GCM', iv, additionalData: head }, key, body));
    const out = new Uint8Array(13 + 16 + 12 + ct.length);
    out.set(head, 0); out.set(salt, 13); out.set(iv, 29); out.set(ct, 41);
    return out;
  }

  /** Liest Fassung 2 UND 3. Die Fassungsnummer bestimmt Kopflänge und Runden. */
  async function openBackup(password, bytes) {
    for (let i = 0; i < 6; i++) {
      if (bytes[i] !== MAGIC_PRAEFIX[i]) throw new Error('Keine .edbak-Datei.');
    }
    const version = (bytes[6] << 8) | bytes[7];
    let kopfLen, iter;
    if (version === 2) {
      kopfLen = 9;  iter = ITER_ALT;      // Fassung 2 vermerkt sie nicht
    } else if (version === 3) {
      kopfLen = 13; iter = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength)
                            .getUint32(9, false);
    } else {
      // Eine NEUERE Fassung als diese Installation kennt. Die Meldung sagt
      // das auch — sonst sucht jemand den Fehler beim Passwort.
      throw new Error('Diese Sicherungsdatei stammt aus einer neueren Fassung '
                    + '(Format ' + version + '). Bitte die Anwendung aktualisieren.');
    }
    const head = bytes.slice(0, kopfLen);
    const key = await fileKey(password, bytes.slice(kopfLen, kopfLen + 16), iter);
    let body;
    try {
      body = new Uint8Array(await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: bytes.slice(kopfLen + 16, kopfLen + 28),
          additionalData: head },
        key, bytes.slice(kopfLen + 28)));
    } catch (e) { throw new Error('Passwort falsch oder Datei beschädigt.'); }
    if (head[8] === 1) { body = await gunzip(body); }
    return JSON.parse(td.decode(body));
  }

  /** Ist das eine Backup-Datei dieses Programms? Beide Fassungen. */
  function isBackupFile(bytes) {
    if (!bytes || bytes.length < 40) return false;
    for (let i = 0; i < 6; i++) { if (bytes[i] !== MAGIC_PRAEFIX[i]) return false; }
    const version = (bytes[6] << 8) | bytes[7];
    // Auch eine unbekannte Fassung ist eine .edbak-Datei. Sie hier abzuweisen
    // hiesse, sie als "fremde Datei" zu melden statt als "zu neu" — und die
    // erste Meldung schickt die suchende Person in die falsche Richtung.
    return version >= 2 && version <= 99;
  }

  return { deriveKeys, encrypt, decrypt, randomHex,
           newRecoveryCode, recoveryKeyHex,
           pruefeRecoveryCode, recoveryCodeMeldung, RC_CHARS, RC_LEN,
           contentKeyCheck, wrapFingerprint,
           setDataKey, getDataKey, getContentKey, setContentKey, clearSession,
           merkeAbleitungen, holeAbleitungen, vergissAbleitungen,
           sealBackup, openBackup, isBackupFile };
})();
