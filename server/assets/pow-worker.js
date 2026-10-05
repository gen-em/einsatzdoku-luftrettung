/* pow-worker.js — die Rechenaufgabe der Registrierung (Schritt 18, SR-08;
 * E-SR-26, E-SR-91).
 *
 * GESUCHT IST EINE ZAHL `loesung`, für die SHA-256 über die Zeichen
 * `aufgabe + loesung` mit mindestens `bits` Nullbits beginnt. Der Server
 * prüft genau das mit EINER Rechnung (`pow_ok()` in `registrieren.php`);
 * hier sind es im Mittel 2^bits Versuche. Das ist der ganze Zweck: Wer ein
 * Formular von Hand ausfüllt, merkt davon nichts, weil die Rechnung läuft,
 * während er tippt — wer es tausendfach abschickt, zahlt tausendmal.
 *
 * EIN WORKER, damit die Seite nicht steht: Auf dem Hauptfaden blockierte die
 * Schleife das Tippen auf genau dem alten Handy, für das die Bitzahl gewählt
 * ist. Gesteuert wird er von `pow.js`; eine Nachricht hinein
 * (`{aufgabe, bits}`), eine heraus (`{loesung, versuche, ms}` oder
 * `{fehler}`).
 *
 * IN BÜNDELN: `crypto.subtle.digest()` antwortet mit einem Promise, und ein
 * einzelnes `await` je Versuch kostet mehr als der Hash selbst. Deshalb
 * gehen BUENDEL Versuche zugleich hinaus; gewertet wird in der Reihenfolge
 * der Zahlen, damit die Lösung die kleinste ihres Bündels ist.
 *
 * KEIN FREMDBESTANDTEIL, KEIN `importScripts()`: WebCrypto ist alles, was es
 * braucht. Die Zahl wird als Dezimalziffern an die Aufgabe gehängt — ohne
 * `TextEncoder` je Versuch, weil Aufgabe (Hex) und Ziffern ohnehin ASCII
 * sind.
 */
'use strict';

const BUENDEL = 64;

/** Beginnt `h` mit mindestens `bits` Nullbits? */
function nullbits(h, bits) {
  let i = 0;
  for (; bits >= 8; bits -= 8, i++) {
    if (h[i] !== 0) { return false; }
  }
  return bits === 0 || (h[i] >> (8 - bits)) === 0;
}

/** `aufgabe` + Dezimalziffern von `n` als Bytes. */
function eingabe(kopf, n) {
  const ziffern = String(n);
  const b = new Uint8Array(kopf.length + ziffern.length);
  b.set(kopf);
  for (let i = 0; i < ziffern.length; i++) { b[kopf.length + i] = ziffern.charCodeAt(i); }
  return b;
}

async function suchen(aufgabe, bits) {
  const kopf = new Uint8Array(aufgabe.length);
  for (let i = 0; i < aufgabe.length; i++) { kopf[i] = aufgabe.charCodeAt(i) & 0x7f; }
  const beginn = performance.now();
  for (let n = 0; ; n += BUENDEL) {
    const auftraege = [];
    for (let k = 0; k < BUENDEL; k++) {
      auftraege.push(crypto.subtle.digest('SHA-256', eingabe(kopf, n + k)));
    }
    const hashes = await Promise.all(auftraege);
    for (let k = 0; k < BUENDEL; k++) {
      if (nullbits(new Uint8Array(hashes[k]), bits)) {
        return { loesung: String(n + k), versuche: n + k + 1,
                 ms: Math.round(performance.now() - beginn) };
      }
    }
  }
}

self.onmessage = async ev => {
  const d = ev.data || {};
  if (!self.crypto || !self.crypto.subtle) {
    self.postMessage({ fehler: 'webcrypto' });
    return;
  }
  if (typeof d.aufgabe !== 'string' || !/^[0-9a-f]+$/.test(d.aufgabe)
      || !Number.isInteger(d.bits) || d.bits < 1 || d.bits > 32) {
    self.postMessage({ fehler: 'auftrag' });
    return;
  }
  try {
    self.postMessage(await suchen(d.aufgabe, d.bits));
  } catch (e) {
    self.postMessage({ fehler: 'rechnung' });
  }
};
