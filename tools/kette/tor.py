#!/usr/bin/env python3
"""Die Tore der Auslieferungskette — Backup, Wartung, Zustand (P5a/AP1).

WOZU ES DIESE DATEI GIBT UND NICHT DREI SHELL-ZEILEN IM ARBEITSLAUF.
Das Backup-Tor (E-P5a-12) ist die eine Stelle der Kette, an der etwas
Unwiderrufliches verhindert wird: ein Deploy auf Produktiv ohne frisches
Komplett-Backup. Eine Bedingung, die das leisten soll, gehört nicht in ein
YAML-Feld, in dem sie niemand liest und niemand probieren kann — sie gehört
dorthin, wo eine `--selbstprobe` sie nachweisen kann. Genau das verlangt die
Abnahme von AP1: „das Backup-Tor bricht **nachweislich** ab, wenn `komplett`
nicht fertig meldet".

DIE VIER UNTERBEFEHLE

    backup        ruft `jobs.php?aktion=komplett` in einer Schleife, bis der
                  Job `fertig` meldet UND der jüngste Stand jünger ist als der
                  Laufbeginn. Sonst: Rückgabewert 1, kein Deploy.
    wartung-an    schaltet den Wartungsmodus ein (`wartung_einschalten('kette')`)
    wartung-aus   schaltet ihn aus
    zustand       holt den Zustand und schreibt ihn als JSON nach stdout;
                  mit `--frage migration` nur `ja` oder `nein`
    pause         hält die Hintergrundjobs an (`--sekunden N`) oder gibt sie
                  wieder frei (`--sekunden 0`)

WARUM `pause` HIER STEHT UND NICHT IM KREISLAUFTEST (Web 20.16.0). Diese
Datei ist der eine Client für `jobs.php?aktion=…` — sie kennt die Adresse,
das Token, die Zeitgrenze und den Umgang mit einer unlesbaren Antwort. Ein
zweiter Aufrufer mit eigener `urllib`-Zeile wäre ein zweiter Weg, den niemand
pflegt; `kreislauf.py` ruft deshalb dieses Werkzeug auf, so wie es auch
`einspielen.py` und `passwort_setzen.mjs` aufruft.

ACHTUNG, ZWEI DINGE HEISSEN HIER „PAUSE": `--pause` ist seit jeher die
Wartezeit ZWISCHEN zwei Backup-Aufrufen. Die Sekunden der Job-Pause stehen
deshalb in `--sekunden`.

ZWEI BEDINGUNGEN, NICHT EINE. `fertig` allein genügt nicht: Ein Backup, das
schon gestern fertig wurde, meldet ebenfalls `fertig` — und schützt diesen
Deploy nicht. Der jüngste Stand muss deshalb **jünger sein als der
Laufbeginn**. Das ist der Unterschied zwischen „es gibt ein Backup" und „es
gibt ein Backup von diesem Stand".

WARUM EINE SCHLEIFE UND NICHT EIN AUFRUF. Der Token-Einstieg hat 20 s Budget
je Aufruf (`JOB_BUDGET_TOKEN`), ein Komplett-Backup von 10 GB braucht mehr.
Ein Lauf arbeitet in Häppchen und meldet je Aufruf, ob er fertig ist. Wer
einmal ruft und das `fertig` glaubt, hat bei kleinen Beständen recht und bei
großen unrecht — und merkt den Unterschied erst, wenn er das Backup braucht.

Aufruf:
    python3 tools/kette/tor.py backup      --basis https://… --token …
    python3 tools/kette/tor.py wartung-an  --basis https://… --token …
    python3 tools/kette/tor.py wartung-aus --basis https://… --token …
    python3 tools/kette/tor.py zustand     --basis https://… --token … [--frage migration]
    python3 tools/kette/tor.py pause       --basis https://… --token … --sekunden 1800
    python3 tools/kette/tor.py --selbstprobe

Rückgabewert: 0 = Tor offen · 1 = Tor zu (und der Grund steht davor) ·
2 = die Prüfung selbst kam nicht zustande (Angabe fehlt, Netz tot).
"""

from __future__ import annotations

import argparse
import json
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone

VERSUCHE_VORGABE = 40
PAUSE_VORGABE_S = 20
ZEITGRENZE_S = 60


def jetzt_utc() -> str:
    """Der Laufbeginn, in derselben Schreibweise wie `komp_zeit_aus_name()`."""
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")


def adresse_bauen(basis: str, token: str, aktion: str,
                  felder: dict | None = None) -> str:
    """Die Abrufadresse — eigene Funktion, damit die Selbstprobe sie prüfen kann.

    Sie stand bis Web 20.16.0 mitten in `rufen()`. Dort war sie ohne Netz
    nicht zu messen, und genau das brauchte der Unterbefehl `pause`: Ob
    `sekunden` überhaupt in der Adresse landet, entscheidet, ob die Jobs
    stillstehen oder weiterlaufen — und ein `ok` bekäme man in beiden Fällen.
    """
    d = {"token": token, "aktion": aktion}
    d.update({k: str(v) for k, v in (felder or {}).items()})
    return basis.rstrip("/") + "/jobs.php?" + urllib.parse.urlencode(d)


def rufen(basis: str, token: str, aktion: str, zeitgrenze: int = ZEITGRENZE_S,
          felder: dict | None = None) -> dict:
    """Einen Aufruf an `jobs.php` — und das Ergebnis als Feld.

    Ein nicht auswertbarer Körper ist KEIN Abbruch, sondern ein Feld mit
    `_roh`: Die Schleife oben entscheidet, ob sie es noch einmal versucht.
    Ein Abbruch hier machte aus einem Schluckauf des Servers ein Nein.
    """
    adresse = adresse_bauen(basis, token, aktion, felder)
    try:
        with urllib.request.urlopen(adresse, timeout=zeitgrenze) as antwort:
            roh = antwort.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as ex:
        # EINE 4xx-ANTWORT IST EIN NEIN DES SERVERS, KEIN NETZFEHLER — und sie
        # hat einen KOERPER, in dem steht, warum. `urlopen` wirft dafuer eine
        # HTTPError, und die ist eine URLError: Ohne diesen Zweig fiel sie in
        # den naechsten und wurde zu `{"_fehler": "HTTP Error 400: ..."}`.
        #
        # Was das gekostet hat, war mehr als eine unschoene Meldung. Der
        # Kommentar in `backup_tor()` sagt ausdruecklich: „Ein falsches Token,
        # eine unbekannte Aktion ... das wird beim vierzigsten Mal nicht
        # anders", und bricht bei `error` sofort ab. Der Zweig war aber nie
        # erreichbar, weil `error` nie ankam — das Tor fragte vierzigmal, gut
        # dreizehn Minuten lang, und meldete dann „kein fertig" statt „falsches
        # Token". Gefunden am 17.09.2026 von einer unabhaengigen Durchsicht,
        # angestossen durch die neue 400-Antwort von `aktion=pause`.
        try:
            roh = ex.read().decode("utf-8", "replace")
        except Exception:                          # noqa: BLE001 — Koerper weg
            return {"_fehler": f"HTTP {ex.code}"}
    except (urllib.error.URLError, OSError, TimeoutError) as ex:
        return {"_fehler": str(ex)}
    try:
        d = json.loads(roh)
        return d if isinstance(d, dict) else {"_roh": roh}
    except json.JSONDecodeError:
        return {"_roh": roh[:400]}


def backup_tor(basis: str, token: str, versuche: int, pause: int,
               ruf=rufen, schlafen=time.sleep) -> int:
    """Das Tor. Rückgabewert 0 = Deploy erlaubt, 1 = nicht."""
    beginn = jetzt_utc()
    print(f"Laufbeginn (UTC): {beginn}")

    fertig = False
    for i in range(1, versuche + 1):
        antwort = ruf(basis, token, "komplett")
        kurz = json.dumps(antwort, ensure_ascii=False)[:200]
        print(f"  Aufruf {i:>2}: {kurz}")
        if antwort.get("fertig") is True:
            fertig = True
            break
        # EIN NEIN DES SERVERS IST KEIN WARTEN. Ein falsches Token, eine
        # unbekannte Aktion, ein Auftrag, der nicht zustande kommt (kein
        # Serverschlüssel, Speichergrenze) — das wird beim vierzigsten Mal
        # nicht anders. Vierzig Aufrufe mit 20 s Pause sind gut dreizehn
        # Minuten, in denen niemand etwas erfährt, was nach dem ersten
        # Aufruf schon feststand. Ein NETZFEHLER (`_fehler`) oder eine
        # unlesbare Antwort (`_roh`) ist etwas anderes: Der wird
        # wiederholt.
        if "error" in antwort:
            print(f"ABBRUCH: Die Installation antwortet mit '{antwort['error']}'"
                  + (f" — {antwort['meldung']}" if antwort.get("meldung") else "")
                  + ". Es wird nicht ausgeliefert.", file=sys.stderr)
            return 1
        if i < versuche:
            schlafen(pause)

    if not fertig:
        print(f"ABBRUCH: Das Komplett-Backup meldete nach {versuche} Aufrufen kein "
              f"'fertig'. Es wird nicht ausgeliefert.", file=sys.stderr)
        return 1

    zustand = ruf(basis, token, "zustand")
    print("Zustand: " + json.dumps(zustand, ensure_ascii=False)[:400])
    stand = zustand.get("komplett")
    if not isinstance(stand, dict) or not stand.get("zeit"):
        print("ABBRUCH: Es liegt kein Komplett-Stand vor. Es wird nicht ausgeliefert.",
              file=sys.stderr)
        return 1
    if str(stand["zeit"]) < beginn:
        print(f"ABBRUCH: Der jüngste Komplett-Stand ist von {stand['zeit']} und damit "
              f"älter als der Laufbeginn {beginn} — er schützt diesen Deploy nicht. "
              f"Es wird nicht ausgeliefert.", file=sys.stderr)
        return 1

    print(f"Tor offen: Komplett-Stand {stand['zeit']} "
          f"({stand.get('groesse', 0)} Byte), jünger als der Laufbeginn.")
    return 0


# ---------------------------------------------------------------- Selbstprobe

def selbstprobe() -> int:
    """Bricht das Tor auch wirklich ab, und landet `sekunden` in der Adresse?

    Elf Lagen, ohne Netz — fünf für das Backup-Tor, fünf für `pause`, eine
    für die Fehlerantwort von `rufen()`.

    DIESE ZAHL IST ZUM ZWEITEN MAL FALSCH GEWESEN, und daraus folgt die Form
    der Kopfzeile unten. Bis Web 20.16.1 meldete sie „fünf Lagen" und fuhr
    zehn; danach stand „fünf und fünf" da, und mit dem elften Fall stimmte
    auch das nicht mehr. Wer eine handgepflegte Zahl abschreibt, trägt sie
    ins Prüfprotokoll. **Die Kopfzeile nennt deshalb nur noch die Gruppen;
    die einzige Zahl, die hier ausgegeben wird, ist die GEZÄHLTE am Ende.**

    Der Aufruf an `jobs.php` wird durch eine Attrappe ersetzt, die
    vorgeschriebene Antworten liefert. Das ist keine Bequemlichkeit: Die
    interessanten Lagen — „meldet nie fertig", „Stand ist von gestern" — lassen
    sich gegen eine echte Installation nicht herstellen, ohne sie zu
    beschädigen.
    """
    erfuellt = 0
    offen = 0

    def pruefe(bedingung: bool, was: str) -> None:
        nonlocal erfuellt, offen
        if bedingung:
            erfuellt += 1
        else:
            offen += 1
        print(f"  [{'ok ' if bedingung else 'FEHL'}] {was}")

    gestern = "2000-01-01T00:00:00Z"
    morgen = "2999-01-01T00:00:00Z"

    def attrappe(folge, zustand):
        """Liefert der Reihe nach `folge`, danach immer den letzten Eintrag."""
        zaehler = {"n": 0}

        def ruf(_basis, _token, aktion):
            if aktion == "zustand":
                return zustand
            i = min(zaehler["n"], len(folge) - 1)
            zaehler["n"] += 1
            return folge[i]
        return ruf

    # KEINE GESAMTZAHL IN DIESER ZEILE (Web 20.16.4). Sie ist zweimal
    # veraltet, ohne dass ein Lauf es gemerkt hätte — gezählt wird unten.
    print("Selbstprobe von tor.py — Backup-Tor, Unterbefehl `pause` und die "
          "Fehlerantwort, ohne Netz\n")

    # 1. Der Regelfall: zweites Häppchen meldet fertig, Stand ist frisch.
    rc = backup_tor("http://attrappe", "t", 5, 0,
                    ruf=attrappe([{"fertig": False}, {"fertig": True}],
                                 {"komplett": {"zeit": morgen, "groesse": 42}}),
                    schlafen=lambda _s: None)
    pruefe(rc == 0, "Regelfall: fertig nach zwei Häppchen, frischer Stand → Tor offen")

    # 2. Meldet nie fertig — die Lage, die die Abnahme von AP1 verlangt.
    rc = backup_tor("http://attrappe", "t", 3, 0,
                    ruf=attrappe([{"fertig": False}],
                                 {"komplett": {"zeit": morgen}}),
                    schlafen=lambda _s: None)
    pruefe(rc == 1, "Meldet nie 'fertig' → Tor zu (KEIN DEPLOY)")

    # 3. Falsches Token: der Endpunkt antwortet mit einem Fehler, nie mit fertig.
    rc = backup_tor("http://attrappe", "t", 3, 0,
                    ruf=attrappe([{"error": "token"}],
                                 {"error": "token"}),
                    schlafen=lambda _s: None)
    pruefe(rc == 1, "Falsches Token → Tor zu, und zwar sofort (kein 40-maliges Fragen)")

    # 4. Fertig, aber der Stand ist von gestern — genau der Fall, den ein
    #    Tor mit nur einer Bedingung durchliesse.
    rc = backup_tor("http://attrappe", "t", 3, 0,
                    ruf=attrappe([{"fertig": True}],
                                 {"komplett": {"zeit": gestern}}),
                    schlafen=lambda _s: None)
    pruefe(rc == 1, "Fertig, aber Stand älter als der Laufbeginn → Tor zu")

    # 5. Fertig, aber es gibt gar keinen Stand.
    rc = backup_tor("http://attrappe", "t", 3, 0,
                    ruf=attrappe([{"fertig": True}], {"komplett": None}),
                    schlafen=lambda _s: None)
    pruefe(rc == 1, "Fertig, aber kein Komplett-Stand vorhanden → Tor zu")

    # ------------------------------------------------------------------
    # 6. bis 10.: der Unterbefehl `pause` (Web 20.16.0).
    #
    # WARUM DIE ADRESSE UND NICHT DIE ANTWORT GEMESSEN WIRD. Ob die Jobs
    # stillstehen, entscheidet sich daran, ob `sekunden` in der Adresse
    # landet — und ein `ok` bekäme der Aufrufer in beiden Fällen. Genau diese
    # Sorte Fehler hat am 16./17.09.2026 drei Kettenschritte gekostet: ein
    # Schalter, der still verworfen wurde.
    print()

    # DIESER FALL HAT IN SEINER ERSTEN FASSUNG NICHTS BEWIESEN. Er rief
    # `adresse_bauen()` UNMITTELBAR mit einem von Hand geschriebenen
    # `{"sekunden": 1800}` auf und prüfte, ob `urlencode` es wieder ausgibt.
    # Die beiden Glieder, die im Betrieb entscheiden — `main()` reicht
    # `felder=` an `rufen()`, `rufen()` an `adresse_bauen()` —, kamen darin
    # nicht vor. Nachgemessen (17.09.2026, unabhängige Durchsicht): Streicht
    # man `felder=` in `main()` ODER reicht `rufen()` es nicht weiter, meldet
    # die Selbstprobe weiter „erfüllt: 10 · offen: 0".
    #
    # Das war genau die Fehlerklasse, gegen die dieser Fall geschrieben
    # wurde: ein Schalter, der still verworfen wird. Jetzt läuft der GANZE
    # Weg, nur der Abruf selbst ist ersetzt.
    gemerkt: dict = {}

    class _Antwort:
        def __enter__(self): return self
        def __exit__(self, *_): return False
        def read(self): return b'{"ok": true}'

    _echt = urllib.request.urlopen
    urllib.request.urlopen = lambda adresse, timeout=None: (
        gemerkt.__setitem__("adresse", adresse), _Antwort())[1]
    try:
        rc = main(["pause", "--basis", "https://x/", "--token", "geheim",
                   "--sekunden", "1800"])
    finally:
        urllib.request.urlopen = _echt
    a = gemerkt.get("adresse", "")
    pruefe(rc == 0 and "aktion=pause" in a and "sekunden=1800" in a,
           f"pause: `sekunden` steht in der ABGERUFENEN Adresse "
           f"({a.split('?', 1)[1] if '?' in a else '— gar kein Abruf —'})")

    a0 = adresse_bauen("https://x", "geheim", "pause", {"sekunden": 0})
    pruefe("sekunden=0" in a0,
           "pause: `sekunden=0` steht da und wird nicht als leer weggelassen")

    az = adresse_bauen("https://x", "geheim", "zustand")
    pruefe("sekunden" not in az,
           "zustand: kein `sekunden` in der Adresse (die alten Aufrufe bleiben, wie sie waren)")

    pruefe(adresse_bauen("https://x/", "g", "zustand")
           == adresse_bauen("https://x", "g", "zustand"),
           "Ein Schrägstrich am Ende der Basis ändert die Adresse nicht")

    # Und der Riegel: ein vergessenes `--sekunden` darf die Pause NICHT
    # aufheben, sondern muss abbrechen. Rückgabewert 2 = „kam nicht zustande".
    rc = main(["pause", "--basis", "https://x", "--token", "g"])
    pruefe(rc == 2, "pause ohne --sekunden → Rückgabewert 2, KEIN stilles Freigeben")

    # 11. EINE 4xx-ANTWORT MUSS MIT IHRER BEGRÜNDUNG ANKOMMEN. Bis Web 20.16.1
    # fing `rufen()` die HTTPError im URLError-Zweig und machte daraus
    # `{"_fehler": "HTTP Error 400: ..."}` — der Körper mit `error` und
    # `meldung` ging verloren, und `backup_tor()` hielt ein falsches Token für
    # einen Netzschluckauf und fragte vierzigmal.
    class _HTTPFehler(urllib.error.HTTPError):
        def __init__(self):
            super().__init__("https://x/jobs.php", 400, "Bad Request", {}, None)

        def read(self):
            return b'{"ok": false, "error": "sekunden", "meldung": "Parameter fehlt."}'

    def _wirft(_adresse, timeout=None):
        raise _HTTPFehler()

    urllib.request.urlopen = _wirft
    try:
        antwort = rufen("https://x", "g", "pause", felder={"sekunden": 1})
    finally:
        urllib.request.urlopen = _echt
    pruefe(antwort.get("error") == "sekunden" and "_fehler" not in antwort,
           f"Eine 400-Antwort kommt mit ihrer Begründung an, nicht als Netzfehler "
           f"({list(antwort)})")

    print(f"\n  erfüllt: {erfuellt} · offen: {offen}")
    return 0 if offen == 0 else 1


# ------------------------------------------------------------------ Einstieg

def main(argv: list[str]) -> int:
    p = argparse.ArgumentParser(add_help=True, description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("befehl", nargs="?",
                   choices=["backup", "wartung-an", "wartung-aus", "zustand",
                            "pause"])
    p.add_argument("--basis", help="Adresse der Installation, z. B. https://nadoku.example")
    p.add_argument("--token", help="Job-Token aus dem Wartungsbereich")
    p.add_argument("--versuche", type=int, default=VERSUCHE_VORGABE)
    p.add_argument("--pause", type=int, default=PAUSE_VORGABE_S,
                   help="backup: Wartezeit ZWISCHEN zwei Aufrufen")
    p.add_argument("--sekunden", type=int, default=None,
                   help="pause: so lange anhalten (0 = Pause aufheben)")
    p.add_argument("--frage", choices=["migration"],
                   help="zustand: nur diese eine Auskunft, als 'ja' oder 'nein'")
    p.add_argument("--selbstprobe", action="store_true",
                   help="ohne Netz prüfen, ob das Tor überhaupt zugeht")
    a = p.parse_args(argv)

    if a.selbstprobe:
        return selbstprobe()
    if a.befehl is None:
        p.print_help()
        return 2
    if not a.basis or not a.token:
        print("--basis und --token sind Pflicht.", file=sys.stderr)
        return 2

    if a.befehl == "backup":
        return backup_tor(a.basis, a.token, a.versuche, a.pause)

    if a.befehl == "pause":
        # KEIN VORGABEWERT, und das ist derselbe Grund wie auf der Serverseite:
        # `--sekunden 0` HEBT die Pause auf. Ein vergessener Schalter, der als
        # 0 durchginge, gäbe die Jobs frei und meldete dafür `ok`.
        if a.sekunden is None:
            print("pause braucht --sekunden (0 = Pause aufheben).", file=sys.stderr)
            return 2
        if a.sekunden < 0:
            print("--sekunden darf nicht negativ sein.", file=sys.stderr)
            return 2
        antwort = rufen(a.basis, a.token, "pause",
                        felder={"sekunden": a.sekunden})
        print(json.dumps(antwort, ensure_ascii=False))
        return 0 if antwort.get("ok") else 1

    if a.befehl in ("wartung-an", "wartung-aus"):
        aktion = "wartung_an" if a.befehl == "wartung-an" else "wartung_aus"
        antwort = rufen(a.basis, a.token, aktion)
        print(json.dumps(antwort, ensure_ascii=False))
        return 0 if antwort.get("ok") else 1

    antwort = rufen(a.basis, a.token, "zustand")
    if a.frage == "migration":
        if not antwort.get("ok"):
            print("unbekannt")
            return 1
        print("ja" if antwort.get("migration_ausstehend") else "nein")
        return 0
    print(json.dumps(antwort, ensure_ascii=False, indent=2))
    return 0 if antwort.get("ok") else 1


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
