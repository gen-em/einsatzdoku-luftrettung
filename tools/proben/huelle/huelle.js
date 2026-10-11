/* Die Gerätehülle im Browser, Fassung 1 — VORLAGE für `server/assets/crypto.js`.
 *
 * Anlass: Nr. 43 — die zweite, unabhängige Umsetzung neben `referenz.py`.
 *
 * WARUM HIER UND NICHT IN crypto.js. S11-02 legt das Format erst fest; das
 * Ergebnis der Uhrprobe kann es noch ändern (Stückgröße, Innenform, ein
 * flüchtiges Paar je Hülle). Ausgeliefert wird erst mit S11-03 — dann zieht
 * dieser Code nach `crypto.js` um, und `probe.mjs` lädt ihn von dort. Bis
 * dahin ist diese Datei kein Teil der Anwendung.
 *
 * NUR WebCrypto (E-S11-21): ECDH P-256, HKDF-SHA256, AES-CBC (die Polsterung
 * PKCS#7 macht WebCrypto selbst), HMAC-SHA256. Das Format steht im Kopf von
 * `referenz.py` und nur dort.
 */
(function (global) {
  'use strict';
  const FASSUNG = 1;
  const KOPF = 1 + 65 + 16;
  const TAG = 32;
  const INFO = new TextEncoder().encode('nadoku-huelle-1');
  const s = crypto.subtle;

  function fehler(grund) { const e = new Error('Hülle geht nicht auf: ' + grund); e.grund = grund; return e; }

  function verbinde(...teile) {
    const n = teile.reduce((a, t) => a + t.length, 0);
    const aus = new Uint8Array(n);
    let o = 0;
    for (const t of teile) { aus.set(t, o); o += t.length; }
    return aus;
  }

  function bindung(art, ref, platz) {
    const teile = [];
    for (const feld of [art, ref, platz || '']) {
      const b = new TextEncoder().encode(feld);
      if (b.length > 0xffff) { throw fehler('bindung-zu-lang'); }
      teile.push(new Uint8Array([b.length >> 8, b.length & 0xff]), b);
    }
    return verbinde(...teile);
  }

  function b64url(b) {
    let t = '';
    for (const x of b) { t += String.fromCharCode(x); }
    return btoa(t).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  }

  /** Öffentlicher Schlüssel (SEC1, 65 Byte) als ECDH-Schlüssel. */
  async function oeffentlich(roh) {
    if (roh.length !== 65 || roh[0] !== 4) { throw fehler('schluessel'); }
    try {
      return await s.importKey('raw', roh, { name: 'ECDH', namedCurve: 'P-256' }, false, []);
    } catch (e) { throw fehler('schluessel'); }
  }

  /** Privater Schlüssel aus d (32 Byte) und dem öffentlichen Punkt — nur für
   *  Prüfvektoren und den Empfänger; im Betrieb kommt er aus seiner Hülle. */
  async function privat(d, pubRoh, verwendung) {
    const jwk = { kty: 'EC', crv: 'P-256', d: b64url(d), x: b64url(pubRoh.slice(1, 33)),
                  y: b64url(pubRoh.slice(33, 65)), ext: true };
    return s.importKey('jwk', jwk, { name: 'ECDH', namedCurve: 'P-256' }, true, verwendung || ['deriveBits']);
  }

  async function ableiten(privKey, pubKey, epk, empfPub) {
    const z = new Uint8Array(await s.deriveBits({ name: 'ECDH', public: pubKey }, privKey, 256));
    const ikm = await s.importKey('raw', z, 'HKDF', false, ['deriveBits']);
    const okm = new Uint8Array(await s.deriveBits(
      { name: 'HKDF', hash: 'SHA-256', salt: epk, info: verbinde(INFO, empfPub) }, ikm, 512));
    return { z, kEnc: okm.slice(0, 32), kMac: okm.slice(32) };
  }

  async function hmac(k, daten) {
    const key = await s.importKey('raw', k, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
    return new Uint8Array(await s.sign('HMAC', key, daten));
  }

  /**
   * Verschließen. `fest` nur für Prüfvektoren: { fluechtigD, fluechtigPub, iv }.
   * @returns {Promise<Uint8Array>} die Hülle
   */
  async function verschliessen(klar, empfPub, art, ref, platz, fest) {
    const empf = await oeffentlich(empfPub);
    let eph, epk;
    if (fest) {
      eph = await privat(fest.fluechtigD, fest.fluechtigPub);
      epk = fest.fluechtigPub;
    } else {
      const paar = await s.generateKey({ name: 'ECDH', namedCurve: 'P-256' }, false, ['deriveBits']);
      eph = paar.privateKey;
      epk = new Uint8Array(await s.exportKey('raw', paar.publicKey));
    }
    const iv = fest ? fest.iv : crypto.getRandomValues(new Uint8Array(16));
    const k = await ableiten(eph, empf, epk, empfPub);
    const aes = await s.importKey('raw', k.kEnc, 'AES-CBC', false, ['encrypt']);
    const ct = new Uint8Array(await s.encrypt({ name: 'AES-CBC', iv }, aes, klar));
    const kopf = verbinde(new Uint8Array([FASSUNG]), epk, iv);
    const tag = await hmac(k.kMac, verbinde(kopf, ct, bindung(art, ref, platz)));
    return verbinde(kopf, ct, tag);
  }

  /** Öffnen: erst den Prüfwert, dann entschlüsseln. Wirft mit `.grund`. */
  async function oeffnen(huelle, empfPriv, empfPub, art, ref, platz) {
    if (huelle.length < KOPF + 16 + TAG || (huelle.length - KOPF - TAG) % 16) { throw fehler('laenge'); }
    if (huelle[0] !== FASSUNG) { throw fehler('fassung'); }
    const epk = huelle.slice(1, 66), iv = huelle.slice(66, KOPF);
    const ct = huelle.slice(KOPF, huelle.length - TAG), tag = huelle.slice(huelle.length - TAG);
    const k = await ableiten(empfPriv, await oeffentlich(epk), epk, empfPub);
    const soll = await hmac(k.kMac, verbinde(huelle.slice(0, huelle.length - TAG), bindung(art, ref, platz)));
    let d = 0;
    for (let i = 0; i < TAG; i++) { d |= soll[i] ^ tag[i]; }   // feste Zeit
    if (d) { throw fehler('pruefwert'); }
    const aes = await s.importKey('raw', k.kEnc, 'AES-CBC', false, ['decrypt']);
    try {
      return new Uint8Array(await s.decrypt({ name: 'AES-CBC', iv }, aes, ct));
    } catch (e) { throw fehler('polsterung'); }
  }

  global.EdHuelle = { verschliessen, oeffnen, privat, bindung, ableiten };
})(typeof window !== 'undefined' ? window : globalThis);
