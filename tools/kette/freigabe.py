#!/usr/bin/env python3
"""Darf dieser Stand auf Produktiv? — das Tor der grünen Läufe (AP7, E-KH-15).

WAS DIESES WERKZEUG ENTSCHEIDET. Vor einer Produktiv-Auslieferung fragt die
Kette: Stand dieser Commit schon auf Staging, und war er dort grün? Bis AP7
war die Antwort einfach — ein `push` auf `main`, ein erfolgreicher Job
`staging`, fertig. Der Hotfix-Weg bricht diese Einfachheit auf: Ein
`hotfix/*`-Zweig kommt per HANDLAUF auf Staging, nicht per Push, und ein
Handlauf umginge den Zweigschutz von `main`, den die alte Regel voraussetzt.

E-KH-15 setzt an die Stelle des Zweigschutzes die ABSTAMMUNG: Ein
`hotfix/*`-Commit zählt, wenn er vom Zeiger `produktion` abstammt — also von
genau dem Stand, der heute ausgeliefert ist. Wer einen Hotfix baut, zweigt
vom Ausgelieferten ab; wer etwas anderes unterschieben will, kann das nicht,
ohne die Abstammung zu verlieren.

WARUM DAS HIER STEHT UND NICHT ALS `if` IM ARBEITSLAUF. Dieselbe Begründung
wie beim Backup-Tor (E-P5a-12): Eine Bedingung, die eine Auslieferung
verhindern soll, gehört dorthin, wo eine `--selbstprobe` sie nachweisen kann.
Die Abnahme von AP7 verlangt wörtlich eine Gegenprobe — „lehnt einen
`hotfix/*`-Commit ab, der NICHT vom Zeiger abstammt; nimmt einen an, der
abstammt". Als Bash im Arbeitslauf wäre das nur durch einen echten
Produktivlauf zu belegen, also praktisch nie.

WAS ES NICHT TUT: ins Netz gehen. Die Tatsachen — welche Läufe es gab, wie
sie ausgingen, ob der Commit abstammt — holt der Arbeitslauf mit `gh api`.
Dieses Werkzeug bekommt sie als JSON und fällt das Urteil. Die Trennung ist
Absicht: Abrufen kann man nicht ohne Netz proben, Entscheiden schon.

STUFE 1 NACH BAUM, NICHT NACH COMMIT (Konzept TB, E-TB-02/-03/-05). Bis
dahin zählte das Tor grüne Stufe-1-Läufe mit `head_sha` gleich dem
Tag-Commit. Ein PR-Lauf trägt aber die SHA des Zweigs, nie die des
Merge-Commits — der Tag `web-v20.37.1` wartete deshalb auf einen zweiten
Lauf, der Byte für Byte maß, was der erste schon gemessen hatte (Lauf
35834341392, beide Commits Baum `447793e…`). Jetzt zählt ein Lauf, dessen
Commit denselben BAUM hat wie der Tag-Commit UND in dem der Job `Stufe 1`
selbst grün war. Mit „Require branches to be up to date" im Ruleset ist der
Baum des Zweig-Commits beweisbar der geprüfte (Konzept TB 2.2).

    python3 tools/kette/freigabe.py --selbstprobe
    python3 tools/kette/freigabe.py urteil --laeufe laeufe.json --vergleich ahead \
            --baum <baum-sha> --stufe1-laeufe stufe1.json
"""

from __future__ import annotations

import argparse
import json
import sys

# Der Zweig, auf dem die Entwicklung liegt und für den ein Push die Regel ist.
HAUPTZWEIG = "main"

# Das Präfix der Hotfix-Zweige (E-KH-15). Ein Zweig `hotfixes/…` ist keiner —
# der Schrägstrich gehört dazu, sonst zählte auch `hotfix-versuch-2`.
HOTFIX_PRAEFIX = "hotfix/"

# Die Antworten der GitHub-Vergleichsschnittstelle
# (`GET /repos/{o}/{r}/compare/produktion...{sha}`), die eine Abstammung
# belegen. `ahead` heisst: der Commit liegt VOR dem Zeiger, enthält ihn also.
# `identical` heisst: es ist derselbe Commit — auch das ist eine Abstammung,
# nämlich die kürzeste.
ABSTAMMUNG_JA = ("ahead", "identical")

# Ein Baum-Hash, wie Git ihn nennt: 40 Hexziffern (SHA-1). Alles andere ist
# kein Baum, sondern ein Abruf, der nicht geklappt hat.
BAUM_ZEICHEN = frozenset("0123456789abcdef")


# ----------------------------------------------------------- reine Entscheidung

def abstammt(vergleich: str | None) -> bool:
    """Belegt diese Vergleichsantwort eine Abstammung vom Zeiger?

    **IM ZWEIFEL NEIN.** Das ist die ganze Kunst dieser Funktion. Ein leerer
    Wert, ein unbekanntes Wort, ein `None` aus einem gescheiterten Abruf —
    alles das heisst „ich weiss es nicht", und „ich weiss es nicht" darf hier
    nie zu „ja" werden. Ein Tor, das bei einer misslungenen Abfrage aufgeht,
    ist kein Tor, sondern eine Tür mit einem Schild daneben.

    `behind` und `diverged` sind ausdrücklich NEIN: `behind` heisst, der
    Commit ist älter als das Ausgelieferte (ein Rückschritt, der sich als
    Hotfix ausgibt), `diverged` heisst, er hat den Zeiger nie gesehen.
    """
    if not isinstance(vergleich, str):
        return False
    return vergleich.strip().lower() in ABSTAMMUNG_JA


def lauf_zaehlt(lauf: dict, abstammung: bool) -> tuple[bool, str]:
    """Zählt dieser EINE Staging-Lauf als Nachweis „stand auf Staging"?

    Rückgabe: `(zaehlt, begruendung)` — die Begründung steht auch im NEIN-Fall
    da und ist der eigentliche Wert dieser Funktion. Ein Tor, das nur „nein"
    sagt, schickt jemanden ins Protokoll; eines, das sagt WARUM, nicht.

    Erwartet in `lauf`: `event`, `head_branch`, `staging_ok`.
    """
    zweig = str(lauf.get("head_branch") or "")
    ereignis = str(lauf.get("event") or "")
    kennung = lauf.get("id", "?")

    # ZUERST DAS ERGEBNIS, DANN DIE HERKUNFT. Ein Lauf, in dem der Job
    # `staging` uebersprungen wurde oder rot war, ist kein Nachweis — gleich,
    # von welchem Zweig er kam. Das ist der Fund F-KH-U-35 in Funktionsform.
    if not lauf.get("staging_ok"):
        return False, (f"Lauf {kennung} ({ereignis}, `{zweig}`): der Job "
                       f"`staging` war nicht erfolgreich — übersprungen oder rot "
                       f"ist nicht geprüft")

    if ereignis == "push" and zweig == HAUPTZWEIG:
        return True, f"Lauf {kennung}: Push auf `{HAUPTZWEIG}`, Job `staging` grün"

    if zweig.startswith(HOTFIX_PRAEFIX):
        # DER HANDLAUF IST HIER DIE REGEL UND NICHT DIE AUSNAHME: Ein
        # `hotfix/*`-Zweig loest keinen Push-Lauf aus (`on.push.branches` nennt
        # nur `main`), er kommt per `workflow_dispatch` auf Staging. Deshalb
        # wird das Ereignis hier NICHT geprueft — geprueft wird die Abstammung,
        # und die ist der staerkere Nachweis.
        if abstammung:
            return True, (f"Lauf {kennung} ({ereignis}, `{zweig}`): Hotfix-Zweig, "
                          f"stammt vom Zeiger `produktion` ab, Job `staging` grün")
        return False, (f"Lauf {kennung} ({ereignis}, `{zweig}`): Hotfix-Zweig, "
                       f"aber der Commit stammt NICHT vom Zeiger `produktion` ab")

    # ALLES ANDERE IST NEIN, und das ist eine Verschaerfung gegenueber AP6:
    # Bis dahin zaehlte JEDER Push-Lauf, ohne nach dem Zweig zu fragen. Das
    # ging gut, solange `on.push.branches` nur `main` nennt — aber eine Regel,
    # die nur wegen einer Zeile in einer anderen Datei stimmt, stimmt nicht.
    return False, (f"Lauf {kennung} ({ereignis}, `{zweig}`): weder Push auf "
                   f"`{HAUPTZWEIG}` noch ein Hotfix-Zweig mit Abstammung")


def baum_norm(baum) -> str:
    """Der Baum als 40 kleine Hexziffern — oder leer, wenn es keiner ist.

    IM ZWEIFEL LEER, und leer zählt nie (E-TB-04). Groß geschrieben ist
    derselbe Baum; ein abgeschnittener, ein `null` aus einem gescheiterten
    Abruf oder eine Fehlermeldung von `gh` sind keiner.
    """
    if not isinstance(baum, str):
        return ""
    b = baum.strip().lower()
    if len(b) != 40 or not set(b) <= BAUM_ZEICHEN:
        return ""
    return b


def stufe1_lauf_zaehlt(lauf: dict, zielbaum: str) -> tuple[bool, str]:
    """Belegt dieser EINE `pruefung.yml`-Lauf Stufe 1 für den Zielbaum?

    Zwei Bedingungen, beide nötig:
      1. derselbe Baum — der geprüfte Inhalt ist der auszuliefernde;
      2. der Job `Stufe 1` war selbst `success` (E-TB-05). Ein Lauf ist auch
         dann „success", wenn `Stufe 1` übersprungen wurde — etwa der
         Push-Lauf auf `main`, der auf einen PR-Lauf verweist statt zu messen
         (TB-03). Ein Verweis ist keine Messung (F-TB-06, Lehre aus
         F-KH-U-35).

    Erwartet in `lauf`: `id`, `event`, `head_branch`, `head_sha`, `baum`,
    `stufe1_ok`. Ereignis und Zweig werden NICHT geprüft (E-TB-02): Ein
    PR-Lauf misst `server/` so vollständig wie ein Push-Lauf auf `main`.
    """
    kennung = lauf.get("id", "?")
    ereignis = str(lauf.get("event") or "")
    zweig = str(lauf.get("head_branch") or "")
    sha = str(lauf.get("head_sha") or "")[:7] or "—"
    baum = baum_norm(lauf.get("baum"))
    wo = f"Lauf {kennung} ({ereignis}, `{zweig}`, {sha}, Baum {baum[:7] or '—'})"

    if not baum:
        return False, f"{wo}: Baum nicht ermittelt — zählt nicht"
    if baum != zielbaum:
        return False, f"{wo}: anderer Baum als der Tag-Commit"
    if lauf.get("stufe1_ok") is not True:
        return False, (f"{wo}: gleicher Baum, aber der Job `Stufe 1` war nicht "
                       f"erfolgreich — übersprungen oder rot ist nicht gemessen")
    return True, f"{wo}: gleicher Baum, Job `Stufe 1` grün"


def urteil(laeufe: list, vergleich: str | None, baum,
           stufe1_laeufe) -> tuple[int, list[str]]:
    """Das Gesamturteil: 0 = darf ausliefern, 1 = nein.

    Gibt die Begründungszeilen mit zurück — JEDE geprüfte Lage, nicht nur die,
    die den Ausschlag gab. Wer im Protokoll steht und wissen will, warum sein
    Tag nicht durchkam, findet hier seinen Lauf und den Grund daneben.
    """
    zeilen: list[str] = []
    abst = abstammt(vergleich)
    zielbaum = baum_norm(baum)

    # ---- Stufe 1: nach Baum (Konzept TB) ----------------------------------
    zeilen.append(f"Baum des Tag-Commits:                 "
                  f"{zielbaum or '— nicht ermittelt —'}")
    stufe1 = 0
    if not zielbaum:
        zeilen.append("::error::Der Baum des Tag-Commits ist nicht ermittelt — "
                      "ohne ihn lässt sich kein Lauf zuordnen. Das Tor bleibt zu.")
        return 1, zeilen
    if not isinstance(stufe1_laeufe, list):
        zeilen.append("::error::Die Liste der Stufe-1-Läufe ist keine Liste. Das "
                      "ist ein Fehler im Arbeitslauf, nicht am Stand — das Tor "
                      "bleibt zu.")
        return 1, zeilen
    for lauf in stufe1_laeufe:
        if not isinstance(lauf, dict):
            zeilen.append(f"  [nein] unlesbarer Eintrag: {lauf!r}")
            continue
        zaehlt, grund = stufe1_lauf_zaehlt(lauf, zielbaum)
        # Nur die Läufe mit passendem Baum nennen, dazu die Zahl der übrigen:
        # Im Fenster stehen bis zu 50, und 49 Zeilen „anderer Baum" verdecken
        # die eine, auf die es ankommt.
        if zaehlt or "anderer Baum" not in grund:
            zeilen.append(f"  [{'JA  ' if zaehlt else 'nein'}] {grund}")
        if zaehlt:
            stufe1 += 1
    andere = sum(1 for l in stufe1_laeufe if isinstance(l, dict)
                 and baum_norm(l.get("baum")) and baum_norm(l.get("baum")) != zielbaum)
    zeilen.append(f"Stufe-1-Läufe geprüft: {len(stufe1_laeufe)}, "
                  f"davon mit anderem Baum: {andere}")
    zeilen.append(f"Grüne Stufe-1-Läufe auf diesem Baum:  {stufe1}")

    # ---- Staging: nach Commit und Herkunft (AP7, unverändert) --------------
    zeilen.append(f"Abstammung vom Zeiger `produktion`:    "
                  f"{'ja' if abst else 'nein'} (Vergleich: {vergleich or '— nicht ermittelt —'})")

    if not isinstance(laeufe, list):
        zeilen.append("::error::Die Liste der Läufe ist keine Liste. Das ist ein "
                      "Fehler im Arbeitslauf, nicht am Stand — das Tor bleibt zu.")
        return 1, zeilen

    anerkannt = 0
    for lauf in laeufe:
        if not isinstance(lauf, dict):
            zeilen.append(f"  [nein] unlesbarer Eintrag: {lauf!r}")
            continue
        zaehlt, grund = lauf_zaehlt(lauf, abst)
        zeilen.append(f"  [{'JA  ' if zaehlt else 'nein'}] {grund}")
        if zaehlt:
            anerkannt += 1

    zeilen.append(f"Anerkannte Staging-Läufe: {anerkannt}")

    if stufe1 < 1:
        zeilen.append("::error::Kein grüner Stufe-1-Lauf auf diesem Baum — "
                      "dieser Stand ist nicht freigabefähig. Liegt der grüne "
                      "Lauf außerhalb des Fensters, hilft ein Handlauf von "
                      "`pruefung.yml` auf diesem Stand.")
        return 1, zeilen

    if anerkannt < 1:
        zeilen.append(
            "::error::Kein anerkannter Staging-Lauf auf diesem Commit. Ein Stand "
            "kommt auf Produktiv, wenn er (a) per Push auf `main` auf Staging "
            "stand oder (b) auf einem `hotfix/*`-Zweig steht, der vom Zeiger "
            "`produktion` abstammt (E-KH-15). Ein übersprungener oder roter "
            "`staging`-Job zählt in keinem Fall.")
        return 1, zeilen

    return 0, zeilen


# ---------------------------------------------------------------- Selbstprobe

def selbstprobe() -> int:
    """Geht das Tor zu, wenn es zugehen muss?

    Die interessanten Lagen sind nicht die guten. Ein Hotfix, der durchgeht,
    belegt wenig — ein Hotfix OHNE Abstammung, der durchginge, wäre genau der
    Weg, den E-KH-15 versperren soll: Handlauf auf einem beliebigen Zweig,
    vorbei am Zweigschutz, direkt auf Produktiv.
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

    print("Selbstprobe `freigabe.py` — Abstammung, Zweig, Ergebnis, Baum.\n")

    # ------------------------------------------------ abstammt()
    pruefe(abstammt("ahead") is True,
           "`ahead` belegt die Abstammung (der Commit enthält den Zeiger)")
    pruefe(abstammt("identical") is True,
           "`identical` ebenso — derselbe Commit ist die kürzeste Abstammung")
    pruefe(abstammt("AHEAD") is True,
           "Gross-/Kleinschreibung der Vergleichsantwort ist kein Unterschied")
    pruefe(abstammt("  ahead  ") is True,
           "…und Leerzeichen drumherum auch nicht")
    pruefe(abstammt("diverged") is False,
           "`diverged` ist NEIN — der Commit hat den Zeiger nie gesehen")
    pruefe(abstammt("behind") is False,
           "`behind` ist NEIN — ein Rückschritt, der sich als Hotfix ausgibt")
    pruefe(abstammt(None) is False,
           "EIN GESCHEITERTER ABRUF IST NEIN, nicht ja — das Tor geht nicht auf, "
           "weil die Frage unbeantwortet blieb")
    pruefe(abstammt("") is False, "Eine leere Antwort ist NEIN")
    pruefe(abstammt("ja") is False, "Ein unbekanntes Wort ist NEIN")
    pruefe(abstammt(42) is False, "Eine Zahl statt einer Antwort ist NEIN")

    # ------------------------------------------------ lauf_zaehlt(): der alte Weg
    PUSH_MAIN = {"id": 1, "event": "push", "head_branch": "main",
                 "staging_ok": True}
    pruefe(lauf_zaehlt(PUSH_MAIN, False)[0] is True,
           "Push auf `main` mit grünem `staging` zählt — AUCH OHNE ABSTAMMUNG "
           "(der Zweigschutz von `main` trägt hier, nicht die Abstammung)")
    pruefe(lauf_zaehlt({**PUSH_MAIN, "staging_ok": False}, True)[0] is False,
           "…aber nicht, wenn der Job `staging` übersprungen oder rot war "
           "(F-KH-U-35), und daran ändert auch eine Abstammung nichts")

    # ------------------------------------------------ lauf_zaehlt(): der Hotfix-Weg
    HOTFIX = {"id": 2, "event": "workflow_dispatch",
              "head_branch": "hotfix/20.26.3-anmeldung", "staging_ok": True}
    ja, grund = lauf_zaehlt(HOTFIX, True)
    pruefe(ja is True and "stammt vom Zeiger" in grund,
           "Ein HANDLAUF auf `hotfix/*` MIT Abstammung zählt (E-KH-15)")
    nein, grund = lauf_zaehlt(HOTFIX, False)
    pruefe(nein is False and "NICHT vom Zeiger" in grund,
           "DIE GEGENPROBE, UND SIE IST DER PRÜFWERT: derselbe Lauf OHNE "
           "Abstammung wird abgelehnt")
    pruefe(lauf_zaehlt({**HOTFIX, "staging_ok": False}, True)[0] is False,
           "Ein Hotfix mit Abstammung, aber ohne grünen `staging`-Job, zählt nicht")

    # ------------------------------------------------ die Ränder des Präfixes
    pruefe(lauf_zaehlt({**HOTFIX, "head_branch": "hotfix-schnell"}, True)[0] is False,
           "`hotfix-schnell` ist KEIN Hotfix-Zweig — der Schrägstrich gehört zum "
           "Präfix, sonst zählte jeder Zweig, der so anfängt")
    pruefe(lauf_zaehlt({**HOTFIX, "head_branch": "hotfix/"}, True)[0] is True,
           "`hotfix/` selbst genügt dem Präfix — ein seltsamer Zweigname, aber "
           "die Abstammung ist der Riegel, nicht die Rechtschreibung")
    pruefe(lauf_zaehlt({**HOTFIX, "head_branch": "feature/hotfix/x"}, True)[0] is False,
           "Ein `hotfix/` MITTEN im Namen zählt nicht — geprüft wird der Anfang")

    # ------------------------------------------------ alles andere
    pruefe(lauf_zaehlt({"id": 3, "event": "push", "head_branch": "claude/arbeit",
                        "staging_ok": True}, True)[0] is False,
           "EIN PUSH AUF EINEN ARBEITSZWEIG ZÄHLT NICHT, auch mit Abstammung — "
           "bis AP6 zählte hier jeder Push, ohne nach dem Zweig zu fragen")
    pruefe(lauf_zaehlt({"id": 4, "event": "workflow_dispatch",
                        "head_branch": "main", "staging_ok": True}, True)[0] is False,
           "EIN HANDLAUF AUF `main` ZÄHLT NICHT — er umginge den Zweigschutz, "
           "und der ist laut Z4 noch nicht gesetzt (E-KH-29)")
    pruefe(lauf_zaehlt({"id": 5, "event": "push", "head_branch": "web-v20.26.2",
                        "staging_ok": True}, True)[0] is False,
           "Ein Tag-Push zählt nicht — in ihm ist `staging` ohnehin übersprungen")

    # ------------------------------------------------ baum_norm()
    BAUM = "447793e41c1ce317c8c4e1daa2ba94ff04bffa55"   # der Baum aus dem Anlass
    ANDERER = "0" * 40
    pruefe(baum_norm(BAUM) == BAUM, "baum_norm: ein Baum bleibt ein Baum")
    pruefe(baum_norm("  " + BAUM.upper() + "\n") == BAUM,
           "baum_norm: Großschreibung und Leerraum sind kein anderer Baum")
    pruefe(baum_norm(BAUM[:39]) == "", "baum_norm: 39 Ziffern sind kein Baum")
    pruefe(baum_norm("g" * 40) == "", "baum_norm: Nicht-Hexziffern sind kein Baum")
    pruefe(baum_norm(None) == "" and baum_norm(42) == "",
           "baum_norm: `null` aus einem gescheiterten Abruf ist kein Baum")

    # ------------------------------------------------ stufe1_lauf_zaehlt()
    PR_LAUF = {"id": 35830260215, "event": "pull_request",
               "head_branch": "claude/pk-umsetzung",
               "head_sha": "f356a44" + "0" * 33, "baum": BAUM, "stufe1_ok": True}
    ja, grund = stufe1_lauf_zaehlt(PR_LAUF, BAUM)
    pruefe(ja is True and "gleicher Baum" in grund,
           "DER ANLASS: ein grüner PR-Lauf mit dem Baum des Tag-Commits zählt, "
           "obwohl seine SHA eine andere ist (E-TB-02, E-TB-03)")
    pruefe(stufe1_lauf_zaehlt({**PR_LAUF, "stufe1_ok": False}, BAUM)[0] is False,
           "Gleicher Baum, Job `Stufe 1` übersprungen oder rot → zählt NICHT "
           "(E-TB-05, P-TB-10) — der Verweis-Lauf auf `main` ist keine Messung")
    pruefe(stufe1_lauf_zaehlt({**PR_LAUF, "stufe1_ok": "success"}, BAUM)[0] is False,
           "Nur ein echtes `true` ist grün — eine Zeichenkette ist ein Fehler "
           "im Arbeitslauf, kein Ja")
    pruefe(stufe1_lauf_zaehlt({**PR_LAUF, "baum": ANDERER}, BAUM)[0] is False,
           "Anderer Baum, Job grün → zählt NICHT (E-TB-04, P-TB-09)")
    pruefe(stufe1_lauf_zaehlt({**PR_LAUF, "baum": ""}, BAUM)[0] is False,
           "Baum im Lauf leer (Abruf gescheitert) → zählt NICHT")
    pruefe(stufe1_lauf_zaehlt({k: v for k, v in PR_LAUF.items() if k != "baum"},
                              BAUM)[0] is False,
           "Baum im Lauf fehlt ganz → zählt NICHT")
    pruefe(stufe1_lauf_zaehlt({**PR_LAUF, "baum": BAUM.upper()}, BAUM)[0] is True,
           "Baum im Lauf groß geschrieben → derselbe Baum, zählt")

    # ------------------------------------------------ urteil(): das Ganze
    S1 = [PR_LAUF]
    rc, zeilen = urteil([PUSH_MAIN], "diverged", BAUM, S1)
    pruefe(rc == 0, "urteil: ein Push auf `main` und ein grüner Stufe-1-Lauf → 0")

    rc, zeilen = urteil([PUSH_MAIN], "ahead", BAUM, [])
    pruefe(rc == 1 and any("Stufe-1" in z and "error" in z for z in zeilen),
           "urteil: OHNE grünen Stufe-1-Lauf ist alles andere gleichgültig → 1")

    rc, zeilen = urteil([PUSH_MAIN], "ahead", BAUM, [{**PR_LAUF, "baum": ANDERER}])
    pruefe(rc == 1, "urteil: nur ein grüner Lauf mit ANDEREM Baum → 1")

    rc, zeilen = urteil([PUSH_MAIN], "ahead", BAUM,
                        [{**PR_LAUF, "event": "push", "head_branch": "main",
                          "stufe1_ok": False}])
    pruefe(rc == 1,
           "urteil: nur der Verweis-Lauf auf `main` (gleicher Baum, `Stufe 1` "
           "übersprungen) → 1 — ein Verweis öffnet das Tor nie (E-TB-07)")

    rc, zeilen = urteil([PUSH_MAIN], "ahead", "", S1)
    pruefe(rc == 1 and any("Baum des Tag-Commits ist nicht ermittelt" in z
                           for z in zeilen),
           "urteil: Zielbaum leer → 1, mit eigener Meldung")
    rc, zeilen = urteil([PUSH_MAIN], "ahead", None, S1)
    pruefe(rc == 1, "urteil: Zielbaum `None` → 1")

    rc, zeilen = urteil([PUSH_MAIN], "ahead", BAUM.upper(), S1)
    pruefe(rc == 0, "urteil: Zielbaum groß geschrieben → derselbe Baum, 0")

    rc, zeilen = urteil([PUSH_MAIN], "ahead", BAUM, "keine liste")
    pruefe(rc == 1, "urteil: Stufe-1-Läufe keine Liste → 1")

    rc, zeilen = urteil([PUSH_MAIN], "ahead", BAUM,
                        [{**PR_LAUF, "stufe1_ok": False},
                         {**PR_LAUF, "baum": ANDERER}, PR_LAUF])
    pruefe(rc == 0,
           "urteil: EIN passender grüner Lauf genügt, auch wenn daneben "
           "nicht passende stehen")

    rc, zeilen = urteil([PUSH_MAIN], "ahead", BAUM,
                        [{**PR_LAUF, "baum": ANDERER}] * 3 + [PR_LAUF])
    pruefe(any("davon mit anderem Baum: 3" in z for z in zeilen)
           and sum("anderer Baum" in z for z in zeilen) == 0
           and any("35830260215" in z and "JA" in z for z in zeilen),
           "Protokoll: der passende Lauf steht mit Nummer da, die übrigen "
           "als Zahl statt als Zeilenwand")

    rc, zeilen = urteil([HOTFIX], "ahead", BAUM, S1)
    pruefe(rc == 0, "urteil: Hotfix mit Abstammung → 0")

    rc, zeilen = urteil([HOTFIX], "diverged", BAUM, S1)
    pruefe(rc == 1, "urteil: DERSELBE Hotfix ohne Abstammung → 1 (die Gegenprobe)")

    rc, zeilen = urteil([HOTFIX], None, BAUM, S1)
    pruefe(rc == 1,
           "urteil: Hotfix, aber die Abstammung konnte nicht ermittelt werden → 1")

    rc, zeilen = urteil([], "ahead", BAUM, S1)
    pruefe(rc == 1, "urteil: gar kein Staging-Lauf → 1")

    rc, zeilen = urteil([{**HOTFIX, "staging_ok": False}, PUSH_MAIN], "diverged",
                        BAUM, S1)
    pruefe(rc == 0,
           "urteil: EIN anerkannter Lauf genügt, auch wenn daneben ein nicht "
           "anerkannter steht")

    rc, zeilen = urteil("keine liste", "ahead", BAUM, S1)
    pruefe(rc == 1,
           "urteil: eine kaputte Zuarbeit schliesst das Tor, statt es zu öffnen")

    rc, zeilen = urteil([None, "x", PUSH_MAIN], "ahead", BAUM, S1)
    pruefe(rc == 0 and sum("unlesbarer Eintrag" in z for z in zeilen) == 2,
           "urteil: unlesbare Einträge werden genannt und übergangen, nicht "
           "stillschweigend verschluckt")

    # ------------------------------------------------ die Begründung ist da
    _, zeilen = urteil([HOTFIX], "diverged", BAUM, S1)
    pruefe(any("NICHT vom Zeiger" in z for z in zeilen),
           "Im NEIN-Fall steht der Grund im Protokoll, nicht nur das Nein")
    pruefe(any("Abstammung vom Zeiger" in z and "diverged" in z for z in zeilen),
           "…und die Vergleichsantwort wird im Wortlaut genannt")

    print(f"\n  erfüllt: {erfuellt} · offen: {offen}")
    return 0 if offen == 0 else 1


# ------------------------------------------------------------------ Einstieg

def main(argv: list[str]) -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("befehl", nargs="?", choices=["urteil"])
    p.add_argument("--laeufe",
                   help="JSON-Liste der Staging-Läufe (Datei oder `-` für stdin); "
                        "je Eintrag: id, event, head_branch, staging_ok")
    p.add_argument("--vergleich",
                   help="Antwort von `compare/produktion...<sha>`: "
                        "ahead, identical, behind, diverged")
    p.add_argument("--baum",
                   help="Baum des Tag-Commits (`git rev-parse HEAD^{tree}`)")
    p.add_argument("--stufe1-laeufe",
                   help="JSON-Liste der `pruefung.yml`-Läufe (Datei oder `-`); "
                        "je Eintrag: id, event, head_branch, head_sha, baum, "
                        "stufe1_ok")
    p.add_argument("--selbstprobe", action="store_true")
    a = p.parse_args(argv)

    if a.selbstprobe:
        return selbstprobe()
    if a.befehl is None:
        p.print_help()
        return 2
    if a.laeufe is None or a.stufe1_laeufe is None:
        print("urteil braucht --laeufe und --stufe1-laeufe (Datei oder `-`).",
              file=sys.stderr)
        return 2
    if a.laeufe == "-" and a.stufe1_laeufe == "-":
        print("Nur eine der beiden Listen kann von stdin kommen.", file=sys.stderr)
        return 2

    def lies(quelle: str, was: str):
        roh = sys.stdin.read() if quelle == "-" else open(quelle, encoding="utf-8").read()
        try:
            return json.loads(roh) if roh.strip() else []
        except json.JSONDecodeError as f:
            # NICHT DURCHWINKEN. Eine unlesbare Zuarbeit ist kein „keine
            # Läufe" und erst recht kein „alles in Ordnung".
            print(f"::error::Die Liste der {was} ist kein gültiges JSON ({f}). "
                  f"Das Tor bleibt zu.", file=sys.stderr)
            return None

    laeufe = lies(a.laeufe, "Staging-Läufe")
    stufe1_laeufe = lies(a.stufe1_laeufe, "Stufe-1-Läufe")
    if laeufe is None or stufe1_laeufe is None:
        return 1

    rc, zeilen = urteil(laeufe, a.vergleich, a.baum, stufe1_laeufe)
    for z in zeilen:
        print(z)
    return rc


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
