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
                  mit `--frage` nur EINE Auskunft, zum Weiterverarbeiten:
                  `migration` -> `ja`/`nein`, `version` -> die Fassung,
                  `wartung` -> `an`/`aus`. Kennt der Server das Feld nicht,
                  lautet die Antwort `unbekannt` und der Rueckgabewert 1 --
                  nie eine erfundene Null (E-KH-19: die Kette des Tags N
                  spricht mit dem Server der Fassung N-1).
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
    python3 tools/kette/tor.py zustand     --basis https://… --token … [--frage migration|version|wartung]
    python3 tools/kette/tor.py pause       --basis https://… --token … --sekunden 1800
    python3 tools/kette/tor.py --selbstprobe

Rückgabewert: 0 = Tor offen · 1 = Tor zu (und der Grund steht davor) ·
2 = die Prüfung selbst kam nicht zustande (Angabe fehlt, Netz tot).
"""

from __future__ import annotations

import argparse
import email.utils
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

# ZWEI RUNDEN, NICHT MEHR (E-KH-05 (2)). Eine zweite faengt den fremden
# Auftrag ab, der beim Aufruf schon lief. Eine dritte faenge nichts mehr ab,
# was die zweite nicht schon hat -- sie verdoppelte nur die Wartezeit.
RUNDEN = 2

# DREI FREMDE ANTWORTEN, DANN SCHLUSS (E-KH-19). Eine waere zu wenig: Eine
# einzelne Fehlerseite des Hosters ist ein Schluckauf, kein alter Server.
# Vierzig waren zu viel -- genau das war der Fall vom 20.09.2026.
FREMDE_ANTWORTEN = 3


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


def kopfzeit(antwort) -> str | None:
    """Die Uhr des SERVERS aus dem `Date`-Kopf, als `YYYY-MM-DDTHH:MM:SSZ`.

    WARUM NICHT DIE UHR DES LAEUFERS (F1, E-KH-05 (1)). Das Tor vergleicht
    zwei Zeiten: den Laufbeginn und den Zeitstempel des Komplett-Stands. Der
    Stand kommt vom Server; der Laufbeginn kam bis zum 20.09.2026 von
    `datetime.now(UTC)` auf dem GitHub-Laeufer. **Das sind zwei Uhren.**
    Geht die des Servers auch nur eine Sekunde nach, ist ein Backup, das
    NACH dem Laufbeginn fertig wurde, mit seinem Zeitstempel davor -- und
    das Tor weist einen gueltigen Stand ab, mit einer Meldung, die niemand
    versteht, weil beide Zahlen richtig aussehen.

    Jetzt kommt auch der Laufbeginn aus dem `Date`-Kopf der ersten Antwort:
    **eine Uhr, ein Vergleich.** Fehlt der Kopf, wird das gesagt und
    abgebrochen -- ein Rueckfall auf die Laeuferuhr waere genau der Fehler,
    nur wieder still.

    `Date` ist nach RFC 9110 in jeder Antwort Pflicht und steht immer in GMT.
    """
    roh = antwort.headers.get("Date") if getattr(antwort, "headers", None) else None
    if not roh:
        return None
    try:
        t = email.utils.parsedate_to_datetime(roh)
    except (TypeError, ValueError):
        return None
    if t.tzinfo is None:
        t = t.replace(tzinfo=timezone.utc)
    return t.astimezone(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")


def rufen(basis: str, token: str, aktion: str, zeitgrenze: int = ZEITGRENZE_S,
          felder: dict | None = None) -> dict:
    """Einen Aufruf an `jobs.php` — und das Ergebnis als Feld.

    Ein nicht auswertbarer Körper ist KEIN Abbruch, sondern ein Feld mit
    `_roh`: Die Schleife oben entscheidet, ob sie es noch einmal versucht.
    Ein Abbruch hier machte aus einem Schluckauf des Servers ein Nein.
    """
    adresse = adresse_bauen(basis, token, aktion, felder)
    serverzeit = None
    try:
        with urllib.request.urlopen(adresse, timeout=zeitgrenze) as antwort:
            roh = antwort.read().decode("utf-8", "replace")
            serverzeit = kopfzeit(antwort)
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
        if not isinstance(d, dict):
            d = {"_roh": roh}
    except json.JSONDecodeError:
        d = {"_roh": roh[:400]}
    if serverzeit:
        d["_serverzeit"] = serverzeit
    return d


def ohne_eigenes(d: dict) -> dict:
    """Die Antwort des Servers — ohne die Felder, die wir selbst angehaengt haben.

    `rufen()` haengt seit dem 20.09.2026 `_serverzeit` an jede Antwort (die
    Uhr aus dem `Date`-Kopf, E-KH-05). Das ist fuer das Tor noetig und fuer
    den Leser des Protokolls irrefuehrend: Die Ausgabe von `zustand` und
    `pause` ist als "die Antwort der Installation" dokumentiert, und wer dort
    ein Feld sieht, das der Server nie geschickt hat, sucht es in `jobs.php`.
    Gemessen am 20.09.2026 im Kettenlauf 35532390449, wo es zum ersten Mal
    sichtbar wurde.
    """
    return {k: v for k, v in d.items() if not k.startswith("_serverzeit")}


def backup_tor(basis: str, token: str, versuche: int, pause: int,
               ruf=rufen, schlafen=time.sleep, runden: int = RUNDEN) -> int:
    """Das Tor. Rückgabewert 0 = Deploy erlaubt, 1 = nicht.

    DREI DINGE HABEN SICH AM 20.09.2026 GEÄNDERT (F1, E-KH-05 und -19):

    (1) **Der Laufbeginn ist die Uhr des Servers**, gelesen aus dem
        `Date`-Kopf der ersten Antwort. Vorher kam er von der Uhr des
        Läufers — zwei Uhren für einen Vergleich. Siehe `kopfzeit()`.

    (2) **Ein Stand, der älter ist als der Laufbeginn, bricht nicht mehr
        ab, sondern dreht eine weitere Runde.** Der Grund ist der Fall,
        der F1 ausgelöst hat: Läuft beim Aufruf bereits ein FREMDER
        Komplett-Auftrag, meldet `fertig` dessen Abschluss — und sein
        Zeitstempel liegt vor unserem Laufbeginn. Die erste Runde fährt
        ihn damit zu Ende; der nächste Aufruf legt einen frischen an. Die
        Regel „jünger als der Torbeginn" bleibt streng, sie bekommt nur
        einen zweiten Anlauf. Begrenzt auf zwei Runden — danach Abbruch
        wie bisher, denn dann ist es kein fremder Auftrag mehr.

    (3) **Ein Server, der die Aktion nicht kennt, führt zu einem
        definierten Abbruch** statt zu vierzig Runden Warten. Eine Antwort
        ohne `fertig` und ohne `error` ist keine Antwort auf diese Frage
        (E-KH-19, der Fall vom 20.09.). Drei solche hintereinander und der
        Lauf endet mit einer Meldung, die sagt, was stattdessen kam. Drei
        und nicht eine: Ein einzelner Schluckauf soll kein Nein werden —
        dieselbe Linie, die `rufen()` im Kopfkommentar zieht.
    """
    beginn = None
    for runde in range(1, runden + 1):
        if runde > 1:
            print(f"\n— Runde {runde} von {runden} —")
        fertig = False
        fremd = 0                      # Antworten, die die Frage nicht kennen
        for i in range(1, versuche + 1):
            antwort = ruf(basis, token, "komplett")
            kurz = json.dumps(ohne_eigenes(antwort), ensure_ascii=False)[:200]
            print(f"  Aufruf {i:>2}: {kurz}")

            if beginn is None:
                beginn = antwort.get("_serverzeit")
                if beginn:
                    print(f"Laufbeginn (Uhr des Servers, `Date`): {beginn}")
                elif "_fehler" not in antwort:
                    # Eine Antwort OHNE Netzfehler und OHNE `Date` gibt es
                    # nach RFC 9110 nicht. Ein Rückfall auf die Läuferuhr
                    # wäre genau der Fehler, den (1) behebt — nur wieder
                    # still. Also: sagen und abbrechen.
                    print("ABBRUCH: Die Antwort trägt keinen `Date`-Kopf. Ohne die Uhr "
                          "des Servers lässt sich nicht entscheiden, ob ein Stand jünger "
                          "ist als dieser Lauf. Es wird nicht ausgeliefert.",
                          file=sys.stderr)
                    return 1

            if antwort.get("fertig") is True:
                fertig = True
                break
            # EIN NEIN DES SERVERS IST KEIN WARTEN. Ein falsches Token, eine
            # unbekannte Aktion, ein Auftrag, der nicht zustande kommt (kein
            # Serverschlüssel, Speichergrenze) — das wird beim vierzigsten Mal
            # nicht anders. Vierzig Aufrufe mit 20 s Pause sind gut dreizehn
            # Minuten, in denen niemand etwas erfährt, was nach dem ersten
            # Aufruf schon feststand. Ein NETZFEHLER (`_fehler`) ist etwas
            # anderes: Der wird wiederholt.
            if "error" in antwort:
                print(f"ABBRUCH: Die Installation antwortet mit '{antwort['error']}'"
                      + (f" — {antwort['meldung']}" if antwort.get("meldung") else "")
                      + ". Es wird nicht ausgeliefert.", file=sys.stderr)
                return 1

            # (3) KENNT DIESER SERVER DIE FRAGE ÜBERHAUPT? `fertig` fehlt,
            # `error` fehlt — dann ist das keine Antwort auf `aktion=komplett`,
            # sondern die einer älteren Installation (oder eine Fehlerseite des
            # Hosters, die als `_roh` hereinkommt).
            if "fertig" not in antwort:
                fremd += 1
                if fremd >= FREMDE_ANTWORTEN:
                    print(f"ABBRUCH: {fremd} Antworten hintereinander ohne `fertig` und "
                          f"ohne `error` — diese Installation beantwortet "
                          f"`aktion=komplett` nicht. Zuletzt kam: {kurz}. Wahrscheinlich "
                          f"ist sie älter als diese Kette. Es wird nicht ausgeliefert.",
                          file=sys.stderr)
                    return 1
            else:
                fremd = 0

            if i < versuche:
                schlafen(pause)

        if not fertig:
            print(f"ABBRUCH: Das Komplett-Backup meldete nach {versuche} Aufrufen kein "
                  f"'fertig'. Es wird nicht ausgeliefert.", file=sys.stderr)
            return 1

        zustand = ruf(basis, token, "zustand")
        print("Zustand: " + json.dumps(ohne_eigenes(zustand),
                                       ensure_ascii=False)[:400])
        stand = zustand.get("komplett")
        if not isinstance(stand, dict) or not stand.get("zeit"):
            print("ABBRUCH: Es liegt kein Komplett-Stand vor. Es wird nicht ausgeliefert.",
                  file=sys.stderr)
            return 1

        if str(stand["zeit"]) >= beginn:
            print(f"Tor offen: Komplett-Stand {stand['zeit']} "
                  f"({stand.get('groesse', 0)} Byte), jünger als der Laufbeginn.")
            return 0

        # (2) ZU ALT — aber nicht zwangsläufig falsch. Noch eine Runde?
        if runde < runden:
            print(f"Der jüngste Komplett-Stand ist von {stand['zeit']} und damit älter "
                  f"als der Laufbeginn {beginn}. Das ist das Bild eines FREMDEN "
                  f"Auftrags, der beim Aufruf schon lief — er ist jetzt zu Ende "
                  f"gefahren. Noch eine Runde.")
            continue
        print(f"ABBRUCH: Der jüngste Komplett-Stand ist von {stand['zeit']} und damit "
              f"älter als der Laufbeginn {beginn} — auch nach {runden} Runden. Er "
              f"schützt diesen Deploy nicht. Es wird nicht ausgeliefert.",
              file=sys.stderr)
        return 1

    return 1                                   # unerreichbar, aber kein `None`


# ---------------------------------------------------------------- Selbstprobe

def zustand_auskunft(antwort: dict, frage: str):
    """Beantwortet EINE Frage an die Zustandsantwort -- ohne Netz, ohne Zustand.

    Rueckgabe: `(text, rueckgabewert)`.

    WARUM DAS EINE EIGENE FUNKTION IST UND KEIN `if` IN `main()`: So laesst
    sie sich von der Selbstprobe durchrechnen. Die interessanten Faelle sind
    nicht die guten, sondern die halben -- eine Antwort ohne `ok`, ein Server,
    der das Feld noch nicht kennt, ein `wartung`, das mal ein Objekt und mal
    ein blosser Wahrheitswert ist. Jeder davon hat genau eine richtige
    Antwort, und die heisst im Zweifel `unbekannt`.

    **NIE EINE ERFUNDENE NULL.** Ein Schlussschritt, der „Wartung: aus" meldet,
    weil er die Antwort nicht lesen konnte, schickt jemanden schlafen, waehrend
    die Anlage zusteht.
    """
    if not antwort.get("ok"):
        return "unbekannt", 1

    if frage == "migration":
        return ("ja" if antwort.get("migration_ausstehend") else "nein"), 0

    if frage == "version":
        fassung = antwort.get("version")
        if not isinstance(fassung, str) or not fassung.strip():
            return "unbekannt", 1
        return fassung.strip(), 0

    if frage == "wartung":
        w = antwort.get("wartung")
        # ZWEI FORMEN, UND BEIDE KOMMEN VOR: `zustand` liefert ein Objekt
        # (`{'aktiv': …, 'seit': …, 'von': …}`), `wartung_an`/`wartung_aus`
        # einen blossen Wahrheitswert. Wer nur die eine Form kennt, meldet
        # gegen die andere „aus".
        if isinstance(w, dict):
            if "aktiv" not in w:
                return "unbekannt", 1
            return ("an" if w["aktiv"] else "aus"), 0
        if isinstance(w, bool):
            return ("an" if w else "aus"), 0
        return "unbekannt", 1

    return "unbekannt", 1


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
    # NUN ist die Uhr des Servers in diesen Faellen; `gleich_danach` ein Stand,
    # der eine Sekunde spaeter fertig wurde. Beide kommen aus DERSELBEN Uhr —
    # genau das ist der Punkt von E-KH-05 (1).
    NUN = "2026-09-20T12:00:00Z"

    def attrappe(folge, zustand, serverzeit=NUN):
        """Liefert der Reihe nach `folge`, danach immer den letzten Eintrag.

        `zustand` darf eine LISTE sein — dann liefert der n-te Abruf den
        n-ten Eintrag. Das braucht die zweite Runde (E-KH-05 (2)): Runde 1
        sieht den fremden, alten Stand, Runde 2 den frischen.

        `serverzeit` hängt an jeder Antwort, wie es `rufen()` aus dem
        `Date`-Kopf tut. `None` stellt eine Antwort OHNE `Date` nach.
        """
        zaehler = {"n": 0, "z": 0}
        zust = zustand if isinstance(zustand, list) else [zustand]

        def ruf(_basis, _token, aktion):
            if aktion == "zustand":
                i = min(zaehler["z"], len(zust) - 1)
                zaehler["z"] += 1
                d = dict(zust[i]) if isinstance(zust[i], dict) else {"komplett": zust[i]}
            else:
                i = min(zaehler["n"], len(folge) - 1)
                zaehler["n"] += 1
                d = dict(folge[i])
            if serverzeit is not None:
                d["_serverzeit"] = serverzeit
            return d
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

    # 4. Fertig, aber es gibt gar keinen Stand.
    rc = backup_tor("http://attrappe", "t", 3, 0,
                    ruf=attrappe([{"fertig": True}], {"komplett": None}),
                    schlafen=lambda _s: None)
    pruefe(rc == 1, "Fertig, aber kein Komplett-Stand vorhanden → Tor zu")

    # ------------------------------------------------------------------
    # 5. bis 11.: F1 — die Uhr des Servers und die zweite Runde
    #    (E-KH-05, E-KH-19; alle neu am 20.09.2026).
    print()

    # 5. DER FALL, DER F1 AUSGELÖST HAT. Beim Aufruf lief bereits ein FREMDER
    #    Komplett-Auftrag; `fertig` meldet dessen Abschluss, und sein
    #    Zeitstempel liegt VOR unserem Laufbeginn. Runde 1 fährt ihn zu Ende,
    #    Runde 2 legt einen frischen an.
    rc = backup_tor("http://attrappe", "t", 3, 0,
                    ruf=attrappe([{"fertig": True}],
                                 [{"komplett": {"zeit": gestern}},
                                  {"komplett": {"zeit": morgen, "groesse": 7}}]),
                    schlafen=lambda _s: None)
    pruefe(rc == 0, "Fremder Auftrag offen → zweite Runde → Tor offen")

    # 6. Die Gegenprobe dazu: Bleibt der Stand auch nach der zweiten Runde
    #    alt, ist es KEIN fremder Auftrag mehr — dann bricht es ab wie eh.
    rc = backup_tor("http://attrappe", "t", 3, 0,
                    ruf=attrappe([{"fertig": True}], {"komplett": {"zeit": gestern}}),
                    schlafen=lambda _s: None)
    pruefe(rc == 1, "Nach zwei Runden kein frischer Stand → Tor zu")

    # 7./8. EINE UHR, EIN VERGLEICH. Geht die Serveruhr zwei Minuten nach oder
    #    vor, ändert das nichts: Laufbeginn UND Stand kommen aus ihr. Vorher
    #    kam der Laufbeginn vom Läufer — dann wies Fall 7 einen gültigen Stand
    #    ab, mit zwei Zahlen, die beide richtig aussahen.
    for versatz, wie in (("2026-09-20T11:58:00Z", "nach"),
                         ("2026-09-20T12:02:00Z", "vor")):
        danach = versatz.replace(":00Z", ":01Z")
        rc = backup_tor("http://attrappe", "t", 3, 0,
                        ruf=attrappe([{"fertig": True}],
                                     {"komplett": {"zeit": danach}},
                                     serverzeit=versatz),
                        schlafen=lambda _s: None)
        pruefe(rc == 0, f"Serveruhr geht 120 s {wie} → Tor offen (beide Zeiten "
                        f"aus derselben Uhr)")

    # 9. KEIN `Date`-KOPF → ABBRUCH MIT ANSAGE. Ein Rückfall auf die Läuferuhr
    #    wäre genau der Fehler, den F1 beschreibt — nur wieder still.
    rc = backup_tor("http://attrappe", "t", 3, 0,
                    ruf=attrappe([{"fertig": True}],
                                 {"komplett": {"zeit": morgen}},
                                 serverzeit=None),
                    schlafen=lambda _s: None)
    pruefe(rc == 1, "Antwort ohne `Date`-Kopf → Abbruch, kein Rückfall auf die Läuferuhr")

    # 10. ALTER SERVER (E-KH-19, der Fall vom 20.09.). Die Installation
    #     antwortet — aber weder mit `fertig` noch mit `error`. Vorher waren
    #     das vierzig Runden Warten auf etwas, das nie kommt.
    rc = backup_tor("http://attrappe", "t", 40, 0,
                    ruf=attrappe([{"ok": True}], {"komplett": {"zeit": morgen}}),
                    schlafen=lambda _s: None)
    pruefe(rc == 1, "Antwort ohne `fertig` und ohne `error` → definierter Abbruch")

    # 11. UND DIE GEGENPROBE: EINE solche Antwort ist ein Schluckauf, kein
    #     alter Server. Sie darf den Lauf nicht töten.
    rc = backup_tor("http://attrappe", "t", 5, 0,
                    ruf=attrappe([{"_roh": "<html>502</html>"}, {"fertig": True}],
                                 {"komplett": {"zeit": morgen}}),
                    schlafen=lambda _s: None)
    pruefe(rc == 0, "EINE fremde Antwort, dann fertig → Tor offen (kein Nein aus "
                    "einem Schluckauf)")

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

    # ------------------------------------------------------------------
    # `--frage` (AP6): version und wartung, und was bei halben Antworten
    # herauskommen MUSS.
    # ------------------------------------------------------------------
    #
    # DIE GUTEN FAELLE SIND NICHT DIE INTERESSANTEN. Ein Schlussschritt, der
    # "Wartung: aus" meldet, weil er die Antwort nicht lesen konnte, schickt
    # jemanden schlafen, waehrend die Anlage zusteht. Deshalb steht hier fuer
    # jede unlesbare Form eine Lage.
    VOLL = {"ok": True, "version": "20.26.0",
            "wartung": {"aktiv": True, "seit": "x", "von": "kette"},
            "migration_ausstehend": False}

    pruefe(zustand_auskunft(VOLL, "version") == ("20.26.0", 0),
           "--frage version gibt die Fassung und 0")
    pruefe(zustand_auskunft(VOLL, "wartung") == ("an", 0),
           "--frage wartung liest `wartung.aktiv` = true als 'an'")
    pruefe(zustand_auskunft({**VOLL, "wartung": {"aktiv": False}}, "wartung") == ("aus", 0),
           "…und `aktiv` = false als 'aus'")
    pruefe(zustand_auskunft({**VOLL, "wartung": True}, "wartung") == ("an", 0),
           "Die ANDERE Form -- ein blosser Wahrheitswert -- wird auch gelesen")
    pruefe(zustand_auskunft(VOLL, "migration") == ("nein", 0),
           "--frage migration antwortet unveraendert")

    # E-KH-19: Die Kette des Tags N spricht mit dem Server der Fassung N-1.
    # Ein Server, der ein Feld noch nicht kennt, darf keine Null erfinden.
    pruefe(zustand_auskunft({"ok": True}, "version") == ("unbekannt", 1),
           "ALTER SERVER: kein `version`-Feld -> 'unbekannt' und 1, nicht ''")
    pruefe(zustand_auskunft({"ok": True}, "wartung") == ("unbekannt", 1),
           "ALTER SERVER: kein `wartung`-Feld -> 'unbekannt' und 1, nicht 'aus'")
    pruefe(zustand_auskunft({"ok": True, "wartung": {}}, "wartung") == ("unbekannt", 1),
           "…und ein `wartung`-Objekt OHNE `aktiv` ebenso")
    pruefe(zustand_auskunft({"ok": True, "version": "   "}, "version") == ("unbekannt", 1),
           "Eine leere Fassung ist keine Fassung")
    pruefe(zustand_auskunft({"ok": False, "version": "20.26.0"}, "version") == ("unbekannt", 1),
           "Eine Antwort ohne `ok` wird gar nicht erst ausgeschlachtet")

    # Die Antwort der Installation bleibt die Antwort der Installation.
    pruefe(ohne_eigenes({"ok": True, "_serverzeit": "x"}) == {"ok": True},
           "`_serverzeit` steht nicht in der ausgegebenen Serverantwort")
    pruefe(ohne_eigenes({"ok": True}) == {"ok": True},
           "…und eine Antwort ohne das Feld bleibt unveraendert")

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
    p.add_argument("--frage", choices=["migration", "version", "wartung"],
                   help="zustand: nur diese eine Auskunft — migration: ja/nein, "
                        "version: die Fassung, wartung: an/aus; unlesbar: unbekannt")
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
        print(json.dumps(ohne_eigenes(antwort), ensure_ascii=False))
        return 0 if antwort.get("ok") else 1

    if a.befehl in ("wartung-an", "wartung-aus"):
        aktion = "wartung_an" if a.befehl == "wartung-an" else "wartung_aus"
        antwort = rufen(a.basis, a.token, aktion)
        print(json.dumps(ohne_eigenes(antwort), ensure_ascii=False))
        return 0 if antwort.get("ok") else 1

    antwort = rufen(a.basis, a.token, "zustand")
    if a.frage:
        text, rc = zustand_auskunft(antwort, a.frage)
        print(text)
        return rc
    print(json.dumps(ohne_eigenes(antwort), ensure_ascii=False, indent=2))
    return 0 if antwort.get("ok") else 1


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
