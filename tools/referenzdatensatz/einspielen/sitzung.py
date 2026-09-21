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


def fehlertext(html: str) -> str | None:
    """Die Fehlermeldung einer Seite lesen — an EINER Stelle.

    WARUM HIER UND NICHT VIERMAL VERTEILT. Bis Web 9.14.0 stand die Suche nach
    `alert-danger` an vier Stellen dieser Datei. Diese Klasse ist mit P3
    verschwunden; Meldungen kommen seither aus `ui_meldung_markup()` als
    `<div class="meldung meldung-fehler" role="alert"><svg…><p>…</p></div>`.
    Drei der vier Stellen haben danach schlicht NICHTS mehr gefunden — und
    weil ein nicht gefundener Fehler wie „kein Fehler" aussieht, liefen
    abgelehnte Formulare als Erfolg durch. Genau das ist die stille Variante
    des Fundes F-S2-A, und sie ist die gefaehrlichere.

    Beide Fassungen werden gelesen: die heutige und die alte, damit der Lauf
    auch gegen einen aelteren Stand noch etwas findet.
    """
    m = (re.search(r'meldung-fehler[^>]*>.*?<p[^>]*>(.*?)</p>', html, re.S)
         or re.search(r'alert-danger[^>]*>(.*?)</', html, re.S))
    if not m:
        # DIE STOERUNGS- UND DIE WARTUNGSSEITE TRAGEN DIE KLASSE NICHT
        # (Kette II, F-KH-U-06, 20.09.2026). `stoerung_seite_html()` und
        # `wartung_antwort_seite()` in `server/wartung_lib.php` bauen auf
        # <h1> und <p class="text"> -- gemessen: `grep -c meldung-fehler`
        # ueber jene Datei ergibt 0.
        #
        # `login.php` hat drei Ausgaenge dieser Bauform, und ALLE DREI
        # antworten auf login.php selbst, ohne Umleitung: Kontostatus nicht
        # aktiv (Z. 401), Wartung an und die Rolle darf nicht verwalten
        # (Z. 417), dazu die Verlangsamung des Ratenschutzes. Ohne diese
        # zweite Suche sind sie voneinander und von einem falschen Passwort
        # ununterscheidbar -- jede hiess "unbekannt". Genau davor warnt der
        # Absatz oben, und genau das ist am 20.09.2026 passiert: Zwei
        # Kettenlaeufe gegen Staging meldeten "Anmeldung gescheitert:
        # unbekannt", und die Ursachensuche kostete die Stunde, die diese
        # Funktion sparen soll.
        m = re.search(r'<h1[^>]*>(.*?)</h1>\s*<p class="text"[^>]*>(.*?)</p>',
                      html, re.S)
        if m:
            roh = re.sub(r"<[^>]+>", " ", " \u00b7 ".join(m.groups()))
            return re.sub(r"\s+", " ", roh).strip() or None
        return None
    # Auszeichnung raus, Leerraum zusammenziehen — die Meldung traegt seit P3
    # ein <strong> fuer den Auftakt.
    roh = re.sub(r"<[^>]+>", " ", m.group(1))
    return re.sub(r"\s+", " ", roh).strip() or None


def seitenkennung(antwort) -> str:
    """Woran eine Seite zu erkennen ist, die keine Fehlermeldung traegt.

    WOZU. `fehlertext()` beantwortet "was ist schiefgegangen?". Diese
    Funktion beantwortet die Frage davor: "WAS habe ich ueberhaupt
    bekommen?" -- Status, Umleitungskette, Titel, Ueberschrift, und ob
    ueberhaupt ein Sitzungscookie gesetzt wurde.

    SIE STEHT HIER, WEIL EINE ABWEISUNG OHNE MELDUNG KEIN RANDFALL IST.
    Am 20.09.2026 (Kette II, F-KH-U-06) scheiterten zwei Kettenlaeufe gegen
    Staging an `Anmeldung gescheitert: unbekannt`. Die drei naheliegenden
    Ursachen -- ausstehende Migration, zu schwache Rolle, gesperrtes Konto --
    liessen sich von Hand alle ausschliessen, und danach war der Lauf am
    Ende: Die Meldung sagte nicht, ob die Antwort 200 oder 503 war, ob sie
    ueber eine Umleitung kam oder ob ueberhaupt ein Cookie zurueckkam.

    KEINE GEHEIMNISSE. Ausgegeben werden Status, Adresse, Titel,
    Ueberschrift und die NAMEN der Cookies -- nie ihre Werte: Der Name des
    Sitzungscookies ist eine Auskunft, sein Wert ist die Sitzung selbst.
    """
    teile = [f"HTTP {antwort.status_code}", f"Adresse {antwort.url}"]
    if getattr(antwort, "history", None):
        kette = " -> ".join(str(h.status_code) for h in antwort.history)
        teile.append(f"ueber {kette}")
    t = re.search(r"<title[^>]*>(.*?)</title>", antwort.text, re.S)
    if t:
        teile.append("Titel " + re.sub(r"\s+", " ", t.group(1)).strip())
    h = re.search(r"<h1[^>]*>(.*?)</h1>", antwort.text, re.S)
    if h:
        roh = re.sub(r"<[^>]+>", " ", h.group(1))
        teile.append("Ueberschrift " + re.sub(r"\s+", " ", roh).strip())
    namen = sorted({k.name for k in antwort.cookies}) if antwort.cookies else []
    teile.append("Cookies der Antwort: " + (", ".join(namen) if namen else "keine"))
    return " \u00b7 ".join(teile)


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
        # Das Anmeldeformular traegt seit Web 15.6.0 ein CSRF-Token
        # (Backlog Nr. 127). Ohne einen GET auf login.php gibt es weder Sitzung
        # noch Token, und der POST endet mit "Das Formular ist abgelaufen" --
        # eine Meldung, die wie ein Passwortfehler aussieht und keiner ist.
        seite = self.get("login.php").text
        m = re.search(r'name="csrf"\s+value="([0-9a-f]+)"', seite)
        if not m:
            raise RuntimeError("login.php nennt kein Formular-Token")
        formular_token = m.group(1)

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
            "csrf": formular_token,
            "email": email,
            "tokens": json.dumps(token_nach),
        }, allow_redirects=True)
        if "login.php" in antwort.url and "Abmelden" not in antwort.text:
            # DIE SEITENKENNUNG GEHOERT AN JEDE ABWEISUNG, nicht nur an die
            # ohne Meldungstext: Auch eine gefundene Meldung sagt nicht, ob
            # die Antwort ueber eine Umleitung kam oder ob ein Cookie
            # zurueckkam -- und beides unterscheidet "Passwort falsch" von
            # "die Sitzung haelt nicht".
            raise RuntimeError(
                "Anmeldung gescheitert: "
                + (fehlertext(antwort.text) or "kein Meldungstext auf der Seite")
                + " \u2014 " + seitenkennung(antwort))
        self.email = email

        # Jetzt sagt die angemeldete Seite, welche Rundenzahl dieses Konto
        # fuehrt — damit steht auch fest, welcher dataKey der richtige ist.
        html = self.get("index.php").text
        self.csrf = self.konstante(html, "CSRF")
        self.kdf_iter = int(self.konstante(html, "KDF_ITER") or runden_liste[0])
        self.haelfte = self.ableitungen.get(self.kdf_iter, data_key)

        # ---- Der Server-Anteil (S10, E-S10-04) ---------------------------
        #
        # Seit S10 ist die PBKDF2-Haelfte nicht mehr selbst der
        # Datenschluessel: Traegt die Huelle das Praefix `edka1:<kennung>:`,
        # kommt der Datenschluessel aus HKDF(Haelfte, Konto-Anteil). Den
        # Anteil liefert die angemeldete Seite als `KONTO_ANTEILE` — sie ist
        # dieselbe Quelle, aus der auch der Browser ihn nimmt.
        #
        # DIESES SKRIPT STELLT NICHT UM (E-S10-15). Es liest, was dasteht;
        # ein Konto, das nur ueber den Pruefstand angemeldet war, bleibt auf
        # `edk1:`. Der Umstellungslauf braucht einen echten Browser, und das
        # ist die Grenze dieses Pruefmittels.
        self.anteile = self.konstante(html, "KONTO_ANTEILE") or {}
        self.anteil_kennung = self.konstante(html, "ANTEIL_KENNUNG")
        self.anteil_stand = self.konstante(html, "ANTEIL_STAND") or "fehlt"

        wrap = self.konstante(html, "PAT_WRAP")
        self.wrap = wrap
        self.data_key = krypto.datenschluessel(self.haelfte, wrap, self.anteile)
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
