"""Der Messstand — Klammer über alle Schritte (E-S2-23, R35).

WOFUER. S2 gibt Zielzahlen aus (E-S2-24): Suche unter 5 s, Tagesansicht unter
3 s, Backup unter 5 min, Wiederherstellung unter 15 min, Backup-Datei
unter 25 MB, Spuren unter 3 MB je 1000 Einsätzen. Diese Zahlen brauchen zwei
Dinge: einen Bestand, an dem sie gemessen werden können, und einen
Ausgangswert, gegen den sich die Verbesserung halten lässt. Beides stellt
dieses Werkzeug her — reproduzierbar, ohne Handarbeit.

DIE SCHRITTE

  konto        Messstandkonto anlegen (bzw. mit --frisch neu anlegen)
  bestand      Großbestand erzeugen (vervielfaeltigen.py)
  einspielen   Bestand über den REGULAEREN Weg einspielen (einspielen.mjs)
  browser      Browserprobe unter CPU-Drossel (browserprobe.mjs)
  server       Serverprobe: Tabellengrößen, Speicherspitzen (serverprobe.py)
  statistik    Betrieb -> Statistik bei vollem Bestand: drei Reiter, EXPLAIN
  protokoll    Alles zu einem Messprotokoll zusammenfassen

    python3 messen.py                       # alle Schritte
    python3 messen.py --schritte server     # einzeln
    python3 messen.py --frisch              # Konto vorher leeren

KEINE EIGENEN WEGE. Konto anlegen und löschen erledigen die geprüften
Bausteine des Referenzdatensatzes (`einspielen/passwort_setzen.mjs`,
`vergleich/kreislauf.py`). Der Messstand schreibt sich dafür nichts Eigenes —
ein zweiter Weg, der Konten anlegt oder löscht, ist genau der, den niemand
pflegt.

DER RIEGEL LIEGT IN DEN TEILWERKZEUGEN, nicht hier. `einspielen.mjs` weigert
sich, ein anderes als das Messstandkonto zu füllen, und `konto_loeschen()`
weigert sich, ein Konto ohne das Messstand-Präfix zu löschen. Beides schließt
nach innen: Wer nicht positiv feststellen kann, dass nichts kaputtgeht,
bricht ab.
"""
from __future__ import annotations

import argparse
import json
import os
import pathlib
import subprocess
import sys
import time

HIER = pathlib.Path(__file__).resolve().parent
WERKZEUGE = HIER.parent
sys.path.insert(0, str(WERKZEUGE / "referenzdatensatz" / "vergleich"))
sys.path.insert(0, str(WERKZEUGE / "referenzdatensatz" / "einspielen"))

PLAYWRIGHT = os.environ.get(
    "PLAYWRIGHT_MODUL", "/opt/node22/lib/node_modules/playwright/index.mjs")

KONTO = os.environ.get("MESSSTAND_KONTO", "messstand@example.invalid")
KONTO_PW = os.environ.get("MESSSTAND_PASSWORT", "messstandpruefung2026")
BACKUP_PW = os.environ.get("MESSSTAND_BACKUP_PASSWORT", "nadokudemo0815")
PRAEFIX = "messstand"

ALLE_SCHRITTE = ["konto", "bestand", "einspielen", "browser", "server", "statistik",
                 "protokoll"]


def melde(t: str) -> None:
    print(t, flush=True)


def lauf(befehl: list[str], **kw) -> subprocess.CompletedProcess:
    e = subprocess.run(befehl, text=True, **kw)
    if e.returncode != 0:
        raise RuntimeError(f"{' '.join(befehl[:2])} endete mit {e.returncode}")
    return e


# ------------------------------------------------------------------ Schritte

def schritt_konto(a) -> None:
    import kreislauf
    # Drei Stuecke: Adresse, Passwort, Geheimnis des Zweitfaktors (leer =
    # Umgebung/Sandbox) -- die Form, die `kreislauf.py` erwartet.
    admin = (a.admin_email, a.admin_passwort, a.admin_totp)
    if a.frisch:
        weg = kreislauf.konto_loeschen(a.basis, admin, KONTO, praefix=PRAEFIX)
        melde(f"  Vorhandenes Messstandkonto {'entfernt' if weg else 'nicht vorhanden'}.")
    import sitzung as sitzungsmodul
    s = sitzungsmodul.Sitzung(a.basis).anmelden(*admin)
    if KONTO in s.get("admin_users.php").text:
        melde(f"  Konto {KONTO} besteht bereits.")
        return
    kreislauf.konto_anlegen(a.basis, admin, KONTO, KONTO_PW)


def schritt_bestand(a) -> None:
    lauf([sys.executable, str(HIER / "vervielfaeltigen.py"),
          "--einsaetze", str(a.einsaetze),
          "--runden-je-datei", str(a.runden_je_datei),
          "--passwort", BACKUP_PW,
          "--ziel", str(pathlib.Path(a.ausgabe) / "bestand")])


def schritt_einspielen(a) -> None:
    ordner = str(pathlib.Path(a.ausgabe) / "bestand")
    lauf(["node", str(HIER / "einspielen.mjs"), a.basis, ordner,
          str(pathlib.Path(a.ausgabe) / "einspielprotokoll.json")],
         env={**os.environ, "PLAYWRIGHT_MODUL": PLAYWRIGHT,
              "MESSSTAND_KONTO": KONTO, "MESSSTAND_PASSWORT": KONTO_PW,
              "MESSSTAND_BACKUP_PASSWORT": BACKUP_PW})


def schritt_browser(a) -> None:
    lauf(["node", str(HIER / "browserprobe.mjs"), a.basis,
          str(pathlib.Path(a.ausgabe) / "browserprobe.json"), str(a.drossel)],
         env={**os.environ, "PLAYWRIGHT_MODUL": PLAYWRIGHT,
              "MESSSTAND_KONTO": KONTO, "MESSSTAND_PASSWORT": KONTO_PW,
              "MESSSTAND_BACKUP_PASSWORT": BACKUP_PW})


def schritt_server(a) -> None:
    lauf([sys.executable, str(HIER / "serverprobe.py"),
          "--ausgabe", str(pathlib.Path(a.ausgabe) / "serverprobe.json")])


# Ziel des Schritts `statistik` (P5c/AP7, Konzept P5c, AP7 Abnahme).
STATISTIK_ZIEL_S = 1.0


def schritt_statistik(a) -> None:
    """Betrieb -> Statistik bei vollem Bestand (P5c/AP7). Anlass: Nr. 295.

    WAS. Die drei Reiter der Seite, je dreimal abgerufen als BetreiberIn;
    gezaehlt wird der Median. Ziel: jeder unter einer Sekunde bei 5000
    Einsaetzen (Konzept P5c, AP7). Dazu `EXPLAIN` fuer die beiden Abfragen,
    die ueber alle Konten laufen -- aus `statistik_lib.php`, also GENAU die
    der Seite, nicht eine nachgeschriebene.

    WARUM HIER UND NICHT IN DER SERVERPROBE. Die Seite zaehlt ueber ALLE
    Konten; erst der Grossbestand des Messstandkontos macht aus ihr eine
    Frage nach den Kosten. Ohne ihn misst der Schritt 200 Einsaetze.

    ROT, NICHT NUR NOTIERT. Ein Reiter ueber der Zielzahl, ein Reiter, der
    nicht der verlangte ist (Anmeldeseite statt Statistik), oder ein Plan
    ohne den Index `idx_missions_started` bricht ab -- sonst stuende eine
    gruene Zahl ohne Gegenstand im Bericht.

    WELCHER PLAN VERLANGT WIRD, HAENGT AM ANTEIL (F-P5c-125). Die Herkunft
    liest 30 Tage -- dort MUSS der Index gewaehlt sein. Die Fensterabfrage
    liest ein ganzes Jahr, und der Messstandbestand liegt fast ganz darin
    (gemessen 24.09.2026: 4832 von 5366, 90 %); ein Tabellenscan ist dann
    billiger, und MariaDB waehlt ihn zu Recht. Verlangt wird dort nur, dass
    der Index zur Wahl steht (`possible_keys`); der Anteil steht im Bericht.

    SEIT R4-23 AUCH DER EIGENE ZEITRAUM (Nr. 122 a). Die drei Reiter noch
    einmal mit `von`/`bis` ueber die letzten 90 Tage -- dieselbe Zielzahl --,
    und `EXPLAIN` fuer die zwei Abfragen, die dann laufen
    (`statistik_zeitraum_sql()`, `statistik_herkunft_sql()` mit Grenzen). Dort
    MUSS der Index gewaehlt sein: 90 Tage sind ein Viertel des Jahres, und
    ein Tabellenscan waere der Fehler, den der Index verhindern soll.
    """
    import sitzung as sitzungsmodul
    s = sitzungsmodul.Sitzung(a.basis).anmelden(a.admin_email, a.admin_passwort,
                                                a.admin_totp)
    aus: dict = {"ziel_s": STATISTIK_ZIEL_S, "reiter": {}, "explain": {}}
    fehler: list[str] = []
    heute = time.strftime("%Y-%m-%d")
    von = time.strftime("%Y-%m-%d", time.localtime(time.time() - 89 * 86400))
    eigen = f"&von={von}&bis={heute}"
    for r, text in (("nutzer", "NutzerInnen"), ("einsaetze", "Einsätze"),
                    ("geraete", "Geräte")):
        for name, zusatz in ((r, ""), (f"{r}+zeitraum", eigen)):
            zeiten = []
            for _ in range(3):
                t0 = time.monotonic()
                antwort = s.get(f"betrieb_statistik.php?r={r}{zusatz}")
                zeiten.append(time.monotonic() - t0)
                # Der Reiter traegt den Zeitraum in seiner Adresse (R4-23).
                aktiv = (f'class="reiter-punkt aktiv" href="?r={r}{zusatz.replace("&", "&amp;")}"'
                         f' aria-current="page">{text}<')
                gemeint = not zusatz or 'Einsätze im Zeitraum' in antwort.text
                if antwort.status_code != 200 or aktiv not in antwort.text or not gemeint:
                    fehler.append(f"Reiter {name}: HTTP {antwort.status_code}, "
                                  f"{'Reiter aktiv' if aktiv in antwort.text else 'nicht der Reiter'}"
                                  f"{'' if gemeint else ', kein eigener Zeitraum'}")
                    break
            median = sorted(zeiten)[len(zeiten) // 2]
            aus["reiter"][name] = {"median_s": round(median, 3),
                                   "zeiten_s": [round(z, 3) for z in zeiten]}
            melde(f"  Reiter {name}: {median:.3f} s (Median aus {len(zeiten)})")
            if median >= STATISTIK_ZIEL_S:
                fehler.append(f"Reiter {name}: {median:.3f} s, Ziel unter {STATISTIK_ZIEL_S} s")

    skript = r"""
$wurzel = getenv("EDOKU_WURZEL");
require_once $wurzel . "/config.php";
require_once $wurzel . "/db.php";
require_once $wurzel . "/demo_lib.php";
require_once $wurzel . "/statistik_lib.php";
$pdo = db();
$demo = (int)(demo_id() ?? 0);
$aus = ["einsaetze_im_bestand" => (int)$pdo->query("SELECT COUNT(*) FROM missions")->fetchColumn(),
        "im_laengsten_fenster" => (int)$pdo->query("SELECT COUNT(*) FROM missions
            WHERE started_at >= UTC_TIMESTAMP() - INTERVAL " . max(array_keys(STAT_FENSTER_EINSAETZE)) . " DAY
              AND started_at <= UTC_TIMESTAMP()")->fetchColumn()];
$fest = statistik_zeitraum([]);
$eigen = statistik_zeitraum(["von" => getenv("EDOKU_VON"), "bis" => getenv("EDOKU_BIS")]);
if ($eigen["art"] !== "eigen") { fwrite(STDERR, "Zeitraum ungueltig\n"); exit(1); }
foreach (["fenster" => [statistik_einsaetze_sql(), $fest],
          "herkunft" => [statistik_herkunft_sql($fest), $fest],
          "zeitraum" => [statistik_zeitraum_sql($eigen), $eigen],
          "herkunft_zeitraum" => [statistik_herkunft_sql($eigen), $eigen]] as $n => [$sql, $z]) {
    $st = $pdo->prepare("EXPLAIN " . $sql);
    $st->execute(array_merge([$demo], statistik_bedingung_werte($z)));
    $aus[$n] = $st->fetchAll(PDO::FETCH_ASSOC);
}
echo json_encode($aus, JSON_UNESCAPED_UNICODE), "\n";
"""
    server = WERKZEUGE.parent / "server"
    e = subprocess.run(["php", "-r", skript], capture_output=True, text=True,
                       env={**os.environ, "EDOKU_WURZEL": str(server),
                            "EDOKU_VON": von, "EDOKU_BIS": heute})
    if e.returncode != 0:
        raise RuntimeError(f"EXPLAIN gescheitert: {e.stderr.strip()[:400]}")
    erkl = json.loads(e.stdout)
    aus["einsaetze_im_bestand"] = erkl["einsaetze_im_bestand"]
    aus["im_laengsten_fenster"] = erkl["im_laengsten_fenster"]
    # Herkunft und eigener Zeitraum: der Index muss GEWAEHLT sein; Fenster:
    # er muss zur Wahl stehen (siehe oben).
    for name, feld in (("fenster", "possible_keys"), ("herkunft", "key"),
                       ("zeitraum", "key"), ("herkunft_zeitraum", "key")):
        zeilen = erkl[name]
        schluessel = [str(z.get("key") or "") for z in zeilen]
        moeglich = ",".join(str(z.get("possible_keys") or "") for z in zeilen)
        aus["explain"][name] = zeilen
        melde(f"  EXPLAIN {name}: key={','.join(schluessel) or '—'}, "
              f"rows={','.join(str(z.get('rows')) for z in zeilen)}, possible_keys={moeglich or '—'}")
        gefunden = ",".join(str(z.get(feld) or "") for z in zeilen)
        if "idx_missions_started" not in gefunden:
            fehler.append(f"EXPLAIN {name}: idx_missions_started nicht in {feld} ({gefunden or 'leer'})")
    anteil = aus["im_laengsten_fenster"] * 100 // max(1, aus["einsaetze_im_bestand"])
    melde(f"  Bestand: {aus['einsaetze_im_bestand']} Einsätze in der Anlage, "
          f"{aus['im_laengsten_fenster']} im längsten Fenster ({anteil} %)")
    ziel = pathlib.Path(a.ausgabe) / "statistik.json"
    ziel.write_text(json.dumps(aus, ensure_ascii=False, indent=2) + "\n", "utf-8")
    if fehler:
        raise RuntimeError("Statistik: " + "; ".join(fehler))


def schritt_protokoll(a) -> None:
    ordner = pathlib.Path(a.ausgabe)
    zusammen: dict = {"gemessen_am": time.strftime("%Y-%m-%dT%H:%M:%S%z"),
                      "basis": a.basis, "konto": KONTO, "drossel": a.drossel}
    for name, datei in (("bestand", "bestand/verzeichnis.json"),
                        ("einspielen", "einspielprotokoll.json"),
                        ("browser", "browserprobe.json"),
                        ("server", "serverprobe.json"),
                        ("statistik", "statistik.json")):
        p = ordner / datei
        zusammen[name] = json.loads(p.read_text("utf-8")) if p.exists() else None
        if zusammen[name] is None:
            melde(f"  {datei} fehlt — dieser Teil steht im Protokoll als null.")
    ziel = ordner / "messprotokoll.json"
    ziel.write_text(json.dumps(zusammen, ensure_ascii=False, indent=2) + "\n", "utf-8")
    melde(f"  Messprotokoll: {ziel}")


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--basis", default="https://127.0.0.1:8443")
    p.add_argument("--schritte", default=",".join(ALLE_SCHRITTE))
    p.add_argument("--ausgabe", default="/tmp/messstand")
    p.add_argument("--einsaetze", type=int, default=5000)
    p.add_argument("--runden-je-datei", type=int, default=3)
    p.add_argument("--drossel", type=int, default=6)
    p.add_argument("--frisch", action="store_true",
                   help="Messstandkonto vorher löschen (nur mit Präfix "
                        f"'{PRAEFIX}')")
    p.add_argument("--admin-email", default="admin@example.invalid")
    p.add_argument("--admin-passwort", default="pruefstandzugang2026")
    # Das Geheimnis des Zweitfaktors (P5c/AP5, E-P5c-43) -- derselbe Schalter
    # wie in `kreislauf.py`: leer heisst `NADOKU_TOTP`, sonst das der Sandbox.
    # Der Schritt `konto` meldet sich bis zu dreimal als Admin an; jede
    # Anmeldung verbraucht einen Zeitschritt, die dritte wartet deshalb
    # womoeglich bis zu 30 s (Kopf von `tools/zweitfaktor/totp.php`).
    p.add_argument("--admin-totp", default="",
                   help="Geheimnis des Zweitfaktors des Admin-Kontos (Base32)")
    a = p.parse_args()

    pathlib.Path(a.ausgabe).mkdir(parents=True, exist_ok=True)
    schritte = [x.strip() for x in a.schritte.split(",") if x.strip()]
    unbekannt = [x for x in schritte if x not in ALLE_SCHRITTE]
    if unbekannt:
        p.error(f"Unbekannte(r) Schritt(e): {', '.join(unbekannt)}")

    funktionen = {"konto": schritt_konto, "bestand": schritt_bestand,
                  "einspielen": schritt_einspielen, "browser": schritt_browser,
                  "server": schritt_server, "statistik": schritt_statistik,
                  "protokoll": schritt_protokoll}
    for name in schritte:
        melde(f"[{name}]")
        t0 = time.monotonic()
        funktionen[name](a)
        melde(f"  ({time.monotonic() - t0:.1f} s)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
