"""Angemeldete Sitzung gegen eine Installation — ueber die regulaeren Wege.

KEIN SONDERZUGANG. Dieses Modul meldet sich so an, wie der Browser es tut:
Salz und Rundenzahl vom Salz-Endpunkt holen, daraus PBKDF2 ableiten, das
ABGELEITETE TOKEN an login.php schicken. Das Passwort verlaesst das Skript
nie -- genauso wenig, wie es den Browser verlaesst (R4, B-02).

Danach steht der Inhaltsschluessel zur Verfuegung: Er liegt passwortverpackt
in `users.pat_wrap_pw` und wird von jeder angemeldeten Seite als Konstante
`PAT_WRAP` mitgegeben (ui.php). Das Skript packt ihn mit dem dataKey aus --
derselbe Weg, den crypto.js im Browser geht.
"""
from __future__ import annotations

import json
import re
import sys
import pathlib

import requests

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent.parent / "generator"))
import krypto  # noqa: E402


class Sitzung:
    def __init__(self, basis: str) -> None:
        self.basis = basis.rstrip("/")
        self.s = requests.Session()
        self.s.trust_env = False          # kein Proxy fuer localhost
        # Selbstsigniertes Zertifikat der lokalen Installation. Vertretbar,
        # weil die Gegenstelle 127.0.0.1 ist; gegen die Produktivinstallation
        # bleibt die Pruefung an (dort ist das Zertifikat echt).
        self.s.verify = not basis.startswith("https://127.0.0.1")
        if not self.s.verify:
            import urllib3
            urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
        self.email: str | None = None
        self.csrf: str | None = None
        self.data_key: str | None = None
        self.inhaltsschluessel: str | None = None
        self.anfragen = 0
        # dataKey je Rundenzahl. Welche fuer dieses Konto gilt, sagt erst die
        # angemeldete Seite (KDF_ITER) -- vorher weiss es nur der Server.
        self.ableitungen: dict[int, str] = {}

    # ---- Grundlagen ----------------------------------------------------
    def get(self, pfad: str, **kw) -> requests.Response:
        self.anfragen += 1
        return self.s.get(f"{self.basis}/{pfad.lstrip('/')}", timeout=60, **kw)

    def post(self, pfad: str, daten=None, **kw) -> requests.Response:
        self.anfragen += 1
        return self.s.post(f"{self.basis}/{pfad.lstrip('/')}", data=daten, timeout=60, **kw)

    @staticmethod
    def konstante(html: str, name: str):
        """`const NAME = <json>;` aus einer Seite lesen (ui.php schreibt sie)."""
        m = re.search(r"const\s+" + name + r"\s*=\s*(.+?);\s*$", html, re.M)
        if not m:
            return None
        try:
            return json.loads(m.group(1))
        except json.JSONDecodeError:
            return m.group(1).strip()

    # ---- Anmelden -------------------------------------------------------
    def anmelden(self, email: str, passwort: str) -> "Sitzung":
        salz = self.post("auth_salt.php", json={"email": email},
                         headers={"Content-Type": "application/json"}).json()
        if "salt" not in salz:
            raise RuntimeError(f"auth_salt.php: {salz}")
        # `iter` ist eine LISTE, kein Wert. Der Salz-Endpunkt nennt jeder
        # Adresse dieselbe Liste moeglicher Rundenzahlen -- sonst verriete er
        # ueber die Antwort, welche Konten es gibt und mit welcher Zahl sie
        # rechnen. Der Browser leitet deshalb fuer JEDE ab und schickt alle
        # Token; der Server greift sich das passende heraus und macht genau
        # EINE bcrypt-Pruefung (login.php, M2-01).
        roh = salz.get("iter") or [310000]
        runden_liste = [int(r) for r in (roh if isinstance(roh, list) else [roh])]
        token_nach = {}
        data_key = None
        for r in runden_liste:
            dk, tk = krypto.ableiten(passwort, salz["salt"], r)
            token_nach[str(r)] = tk
            if data_key is None:
                data_key = dk
            self.ableitungen[r] = dk

        antwort = self.post("login.php", {
            "email": email,
            "tokens": json.dumps(token_nach),
        }, allow_redirects=True)
        if "login.php" in antwort.url and "Abmelden" not in antwort.text:
            fehler = re.search(r'class="alert alert-danger"[^>]*>(.*?)<', antwort.text, re.S)
            raise RuntimeError("Anmeldung gescheitert: "
                               + (fehler.group(1).strip() if fehler else "unbekannt"))
        self.email = email

        # Jetzt sagt die angemeldete Seite, welche Rundenzahl dieses Konto
        # fuehrt — damit steht auch fest, welcher dataKey der richtige ist.
        html = self.get("index.php").text
        self.csrf = self.konstante(html, "CSRF")
        self.kdf_iter = int(self.konstante(html, "KDF_ITER") or runden_liste[0])
        self.data_key = self.ableitungen.get(self.kdf_iter, data_key)
        wrap = self.konstante(html, "PAT_WRAP")
        if wrap:
            self.inhaltsschluessel = krypto.entpacken(wrap, self.data_key)
        return self

    def csrf_auffrischen(self, pfad: str = "index.php") -> str:
        """CSRF-Token von einer Seite holen. Es steckt entweder als Konstante
        in der Seite (ui.php) oder als verstecktes Formularfeld."""
        html = self.get(pfad).text
        wert = self.konstante(html, "CSRF")
        if not wert:
            m = re.search(r'name="csrf"\s+value="([0-9a-f]{16,})"', html)
            wert = m.group(1) if m else None
        if wert:
            self.csrf = wert
        return self.csrf

    # ---- Bequemlichkeiten ----------------------------------------------
    def formular(self, pfad: str, daten: dict, csrf_von: str | None = None) -> requests.Response:
        """POST mit CSRF-Feld — der Weg, den jedes Formular der Anwendung geht."""
        if csrf_von or not self.csrf:
            self.csrf_auffrischen(csrf_von or pfad)
        return self.post(pfad, {**daten, "csrf": self.csrf})

    def json_post(self, pfad: str, koerper: dict) -> requests.Response:
        """POST mit JSON-Koerper und X-CSRF — der Weg der api/-Endpunkte."""
        if not self.csrf:
            self.csrf_auffrischen()
        self.anfragen += 1
        return self.s.post(f"{self.basis}/{pfad.lstrip('/')}", json=koerper, timeout=60,
                           headers={"X-CSRF": self.csrf, "Content-Type": "application/json"})
