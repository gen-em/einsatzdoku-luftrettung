/**
 * DER CODE-RECHNER FÜR NODE (P5c/AP5, E-P5c-43). Einer je Sprache — die
 * anderen sind `totp.php` und `totp.py` daneben; alle drei rechnen dasselbe
 * und teilen denselben Zähler. Warum es den Zähler gibt und wie er sperrt,
 * steht im Kopf von `totp.php`.
 *
 * Anlass: F-P5c-33.
 *
 *   import { naechsterCode } from '../zweitfaktor/totp.mjs';
 *   const code = await naechsterCode();          // NADOKU_TOTP oder Sandbox
 *   const code = await naechsterCode(base32);    // ausdrücklich
 */
import { createHmac, createHash } from 'node:crypto';
import { openSync, closeSync, unlinkSync, statSync, readFileSync, writeFileSync, existsSync } from 'node:fs';

export const SANDBOX = 'PRUEFSTANDZWEITFAKTORNADOKU23456';

export function geheimnis() {
  const e = (process.env.NADOKU_TOTP || '').trim();
  return e !== '' ? e : SANDBOX;
}

function norm(b32) { return String(b32).replace(/[\s=]+/g, '').toUpperCase(); }

export function base32(b32) {
  const a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  let bits = '';
  for (const z of norm(b32)) {
    const i = a.indexOf(z);
    if (i < 0) throw new Error(`Kein Base32: ${z}`);
    bits += i.toString(2).padStart(5, '0');
  }
  const out = [];
  for (let i = 0; i + 8 <= bits.length; i += 8) out.push(parseInt(bits.slice(i, i + 8), 2));
  return Buffer.from(out);
}

export function code(roh, schritt) {
  const b = Buffer.alloc(8);
  b.writeBigUInt64BE(BigInt(schritt));
  const h = createHmac('sha1', roh).update(b).digest();
  const o = h[19] & 0x0f;
  const n = (((h[o] & 0x7f) << 24) | (h[o + 1] << 16) | (h[o + 2] << 8) | h[o + 3]) % 1000000;
  return String(n).padStart(6, '0');
}

export function zaehlerdatei(b32) {
  const tmp = (process.env.TMPDIR || '/tmp').replace(/\/$/, '');
  const kennung = createHash('sha256').update(norm(b32)).digest('hex').slice(0, 12);
  return `${tmp}/nadoku-totp-${kennung}.schritt`;
}

const schlafe = (ms) => new Promise((r) => setTimeout(r, ms));

export async function naechsterCode(b32 = geheimnis()) {
  const datei = zaehlerdatei(b32);
  const sperre = `${datei}.sperre`;
  const bis = Date.now() + 60000;
  for (;;) {
    try { closeSync(openSync(sperre, 'wx')); break; } catch {
      try { if (Date.now() - statSync(sperre).mtimeMs > 10000) { unlinkSync(sperre); continue; } } catch {}
      if (Date.now() > bis) throw new Error(`Zählersperre hängt: ${sperre}`);
      await schlafe(50);
    }
  }
  try {
    const letzter = existsSync(datei) ? parseInt(readFileSync(datei, 'utf8').trim(), 10) || 0 : 0;
    let kandidat;
    for (;;) {
      const jetzt = Math.floor(Date.now() / 30000);
      kandidat = Math.max(letzter + 1, jetzt);
      if (kandidat <= jetzt + 1) break;
      await schlafe((kandidat - 1) * 30000 - Date.now() + 200);
    }
    writeFileSync(datei, String(kandidat));
    return code(base32(b32), kandidat);
  } finally {
    try { unlinkSync(sperre); } catch {}
  }
}
