/* Einsatzdoku — Ende-zu-Ende-Krypto für das PatientInnendaten-Modul.
 *
 * Prinzip (angelehnt an Bitwarden):
 *  - Aus dem Login-Passwort leitet der Browser per PBKDF2 (310 000 Runden,
 *    SHA-256, nutzerspezifisches Salt) 512 Bit ab und teilt sie:
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

  const ITER = 310000;
  const te = new TextEncoder(), td = new TextDecoder();

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

  /* ---- Schlüsselableitung aus dem Passwort ---------------------------- */
  async function deriveKeys(password, saltHex) {
    const base = await crypto.subtle.importKey(
      'raw', te.encode(password), 'PBKDF2', false, ['deriveBits']);
    const bits = await crypto.subtle.deriveBits(
      { name: 'PBKDF2', hash: 'SHA-256', salt: fromHex(saltHex), iterations: ITER },
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
  const clearSession = () => { sessionStorage.removeItem(S_DK); sessionStorage.removeItem(S_CK); };

  /* ---- Backup-Container (.edbak v2) -----------------------------------
   * Aufbau:  "EDBAK2" 0x00 0x02 | Flag(1) | Salt(16) | IV(12) | AES-GCM
   * Flag:    1 = Inhalt gzip-komprimiert, 0 = roh
   * Schlüssel: PBKDF2-SHA256(Backup-Passwort, Salt, 310 000, 256 Bit)
   * AAD:     die ersten 9 Bytes (Magie + Flag) — Kopfmanipulation fliegt auf.
   * Der Inhalt ist bereits KLARTEXT: Der Browser entschlüsselt vor dem
   * Versiegeln, damit sich das Backup in jedes Konto einspielen lässt.
   */
  const MAGIC2 = new Uint8Array([69, 68, 66, 65, 75, 50, 0, 2]);   // "EDBAK2"

  async function fileKey(password, salt) {
    const base = await crypto.subtle.importKey('raw', te.encode(password),
      'PBKDF2', false, ['deriveBits']);
    const bits = await crypto.subtle.deriveBits(
      { name: 'PBKDF2', salt, iterations: ITER, hash: 'SHA-256' }, base, 256);
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

  async function sealBackup(password, jsonText) {
    const raw = te.encode(jsonText);
    const packed = await gzip(raw);
    const flag = packed ? 1 : 0;
    const body = packed || raw;
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const head = new Uint8Array(9);
    head.set(MAGIC2, 0); head[8] = flag;
    const key = await fileKey(password, salt);
    const ct = new Uint8Array(await crypto.subtle.encrypt(
      { name: 'AES-GCM', iv, additionalData: head }, key, body));
    const out = new Uint8Array(9 + 16 + 12 + ct.length);
    out.set(head, 0); out.set(salt, 9); out.set(iv, 25); out.set(ct, 37);
    return out;
  }

  async function openBackup(password, bytes) {
    const head = bytes.slice(0, 9);
    for (let i = 0; i < 8; i++) {
      if (head[i] !== MAGIC2[i]) throw new Error('Keine .edbak-Datei (Version 2).');
    }
    const key = await fileKey(password, bytes.slice(9, 25));
    let body;
    try {
      body = new Uint8Array(await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: bytes.slice(25, 37), additionalData: head },
        key, bytes.slice(37)));
    } catch (e) { throw new Error('Passwort falsch oder Datei beschädigt.'); }
    if (head[8] === 1) { body = await gunzip(body); }
    return JSON.parse(td.decode(body));
  }

  /** Ist das eine Backup-Datei dieses Programms? */
  function isBackupFile(bytes) {
    if (!bytes || bytes.length < 40) return false;
    for (let i = 0; i < 8; i++) { if (bytes[i] !== MAGIC2[i]) return false; }
    return true;
  }

  return { deriveKeys, encrypt, decrypt, randomHex,
           newRecoveryCode, recoveryKeyHex,
           pruefeRecoveryCode, recoveryCodeMeldung, RC_CHARS, RC_LEN,
           contentKeyCheck, wrapFingerprint,
           setDataKey, getDataKey, getContentKey, clearSession,
           sealBackup, openBackup, isBackupFile };
})();
