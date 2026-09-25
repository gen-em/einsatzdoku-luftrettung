"""DER CODE-RECHNER FÜR PYTHON (P5c/AP5, E-P5c-43). Einer je Sprache — die
anderen sind `totp.php` und `totp.mjs` daneben; alle drei rechnen dasselbe
und teilen denselben Zähler. Warum es den Zähler gibt und wie er sperrt,
steht im Kopf von `totp.php`.

Anlass: F-P5c-33.

    sys.path.insert(0, str(WURZEL / 'tools' / 'zweitfaktor'))
    from totp import naechster_code
    code = naechster_code()          # NADOKU_TOTP oder Sandbox
    code = naechster_code(base32)    # ausdrücklich

Von der Kommandozeile: `python3 tools/zweitfaktor/totp.py` gibt den
nächsten Code aus.
"""
from __future__ import annotations

import base64
import hashlib
import hmac
import os
import struct
import time

SANDBOX = 'PRUEFSTANDZWEITFAKTORNADOKU23456'


def geheimnis() -> str:
    e = (os.environ.get('NADOKU_TOTP') or '').strip()
    return e or SANDBOX


def _norm(b32: str) -> str:
    return ''.join(b32.split()).replace('=', '').upper()


def roh(b32: str) -> bytes:
    n = _norm(b32)
    return base64.b32decode(n + '=' * (-len(n) % 8))


def code(geheim: bytes, schritt: int) -> str:
    h = hmac.new(geheim, struct.pack('>Q', schritt), hashlib.sha1).digest()
    o = h[19] & 0x0F
    n = (int.from_bytes(h[o:o + 4], 'big') & 0x7FFFFFFF) % 1_000_000
    return f'{n:06d}'


def zaehlerdatei(b32: str) -> str:
    tmp = (os.environ.get('TMPDIR') or '/tmp').rstrip('/')
    kennung = hashlib.sha256(_norm(b32).encode()).hexdigest()[:12]
    return f'{tmp}/nadoku-totp-{kennung}.schritt'


def naechster_code(b32: str | None = None) -> str:
    b32 = b32 or geheimnis()
    datei = zaehlerdatei(b32)
    sperre = datei + '.sperre'
    bis = time.time() + 60
    while True:
        try:
            os.close(os.open(sperre, os.O_CREAT | os.O_EXCL | os.O_WRONLY))
            break
        except FileExistsError:
            try:
                if time.time() - os.stat(sperre).st_mtime > 10:
                    os.unlink(sperre)
                    continue
            except FileNotFoundError:
                continue
            if time.time() > bis:
                raise RuntimeError(f'Zählersperre hängt: {sperre}')
            time.sleep(0.05)
    try:
        try:
            with open(datei, encoding='utf-8') as f:
                letzter = int(f.read().strip() or 0)
        except (FileNotFoundError, ValueError):
            letzter = 0
        while True:
            jetzt = int(time.time() // 30)
            kandidat = max(letzter + 1, jetzt)
            if kandidat <= jetzt + 1:
                break
            time.sleep((kandidat - 1) * 30 - time.time() + 0.2)
        with open(datei, 'w', encoding='utf-8') as f:
            f.write(str(kandidat))
        return code(roh(b32), kandidat)
    finally:
        try:
            os.unlink(sperre)
        except FileNotFoundError:
            pass


if __name__ == '__main__':
    print(naechster_code())
