"""Serverprobe des Messstands — was die Anwendung auf der Serverseite kostet.

WOFUER. Die Browserprobe misst, was die Nutzerin merkt. Diese hier misst, was
der geteilte Webspace merkt: wie viel Platz der Bestand belegt, wie lange die
Wege der Anwendung laufen und wie hoch PHP dabei im Speicher steigt. Die
Grenzen stehen in Z3 (Konzept 3.5): Laufzeit ≤ 30 s je Anfrage,
PHP-Speicherspitze ≤ 64 MB, POST ≤ 2 MB.

DIE SPEICHERSPITZE WIRD NICHT GESCHAETZT, SONDERN GEMESSEN. `messwerte.php`
ruft die betroffenen Bausteine im selben Prozess auf und liest danach
`memory_get_peak_usage(true)`. Das ist die Zahl, gegen die `memory_limit`
tatsächlich prüft — eine aus der Datenmenge hochgerechnete wäre eine
Vermutung.

WARUM DAS UEBER EIN PHP-SKRIPT LAEUFT UND NICHT UEBER DIE WEBSEITE. Über die
Weboberfläche kommt nur heraus, ob eine Anfrage durchging; wie nahe sie an der
Grenze war, sieht man ihr nicht an. Der Weg hier ruft dieselben Funktionen der
Anwendung auf — `edbak_build()` und die Abfragen der Ansichten — nur eben so,
dass sich die Spitze ablesen lässt.

Aufruf:
    python3 serverprobe.py [--ausgabe datei.json] [--konto messstand@gen-em.org]
"""
from __future__ import annotations

import argparse
import json
import pathlib
import subprocess
import sys

HIER = pathlib.Path(__file__).resolve().parent
SERVER = HIER.parents[1] / "server"


def php_messen(konto: str, optimieren: bool = False,
               wartung: bool = False) -> dict:
    """Die Anwendung im CLI-Prozess anfassen und die Spitzen ablesen."""
    skript = r'''<?php
declare(strict_types=1);
$wurzel = getenv("EDOKU_WURZEL");
$konto  = getenv("EDOKU_KONTO");
require_once $wurzel . "/config.php";
require_once $wurzel . "/db.php";

$pdo = db();
$q = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$q->execute([$konto]);
$userId = (int)$q->fetchColumn();
if (!$userId) { fwrite(STDERR, "Konto $konto gibt es nicht\n"); exit(2); }

function zahl(PDO $pdo, string $sql, array $p = []): int {
    $s = $pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
}

$aus = ["konto" => $konto, "user_id" => $userId];
$aus["einsaetze"]     = zahl($pdo, "SELECT COUNT(*) FROM missions WHERE user_id=?", [$userId]);
$aus["ruhesegmente"]  = zahl($pdo, "SELECT COUNT(*) FROM rest_segments WHERE user_id=?", [$userId]);
$aus["diensttage"]    = zahl($pdo, "SELECT COUNT(*) FROM days WHERE user_id=?", [$userId]);
$aus["spurpunkte"]    = zahl($pdo,
    "SELECT COUNT(*) FROM track_points tp
      WHERE (tp.owner_type='mission' AND tp.owner_id IN (SELECT id FROM missions WHERE user_id=?))
         OR (tp.owner_type='rest'    AND tp.owner_id IN (SELECT id FROM rest_segments WHERE user_id=?))",
    [$userId, $userId]);

/* Tabellengroessen — die Zahl, an der das 10-GB-Kontingent aus Z2 haengt.
 *
 * ZWEI FALLEN, BEIDE GEMESSEN UND BEIDE UMGANGEN:
 *
 * 1. `information_schema.tables.table_rows` ist bei InnoDB eine SCHAETZUNG aus
 *    Stichproben. Sie lag hier um den Faktor 2,8 daneben (9 215 509 statt
 *    3 257 385). Gezaehlt wird deshalb mit COUNT(*) — das kostet bei
 *    Millionen Zeilen ein paar Sekunden und ist die einzige Zahl, die stimmt.
 *
 * 2. `data_length` ist der BELEGTE Platz, nicht der benutzte. InnoDB gibt
 *    Seiten geloeschter Zeilen nicht an das Dateisystem zurueck; nach zwei
 *    verworfenen Aufbauten stand die Tabelle auf 501 MB fuer 3,2 Mio. Punkte
 *    — also 57 B/Zeile, wo 62 B richtig sind. Wer die Bytes je Zeile
 *    braucht, laesst `--optimieren` mitlaufen; der Lauf baut die Tabelle
 *    dann neu auf und misst danach. Ohne das steht in der Ausgabe
 *    ausdruecklich, dass die Zahl den freien Platz mitzaehlt.
 */
// OPTIMIZE und ANALYZE liefern eine ERGEBNISZEILE. Mit exec() bleibt sie
// unabgeholt stehen, und die naechste Abfrage scheitert mit "Cannot execute
// queries while other unbuffered queries are active" — die Fehlermeldung
// zeigt dabei auf die naechste Zeile, nicht auf die Ursache.
if (getenv("EDOKU_OPTIMIEREN") === "ja") {
    $t0 = microtime(true);
    $pdo->query("OPTIMIZE TABLE track_points")->fetchAll();
    $aus["optimize_s"] = round(microtime(true) - $t0, 1);
}
$pdo->query("ANALYZE TABLE track_points")->fetchAll();

// fetchAll, NICHT ueber das Ergebnis iterieren: In der Schleife steht eine
// weitere Abfrage (COUNT), und PDO/MySQL laesst neben einem offenen,
// ungepufferten Ergebnis keine zweite zu ("Cannot execute queries while other
// unbuffered queries are active").
$tabellen = $pdo->query("SELECT table_name, table_rows,
                                data_length, index_length
                           FROM information_schema.tables
                          WHERE table_schema = DATABASE()
                          ORDER BY data_length DESC")->fetchAll(PDO::FETCH_ASSOC);
$aus["tabellen"] = [];
foreach ($tabellen as $r) {
    if ((int)$r["data_length"] === 0) { continue; }
    $name = (string)$r["table_name"];
    // Genau zaehlen, wo die Zahl gebraucht wird; sonst die Schaetzung.
    $genau = null;
    if ($name === "track_points") {
        $genau = zahl($pdo, "SELECT COUNT(*) FROM track_points");
    }
    $aus["tabellen"][] = [
        "name" => $name,
        "zeilen" => $genau ?? (int)$r["table_rows"],
        "zeilen_gezaehlt" => $genau !== null,
        "daten_mb"  => round($r["data_length"] / 1048576, 2),
        "index_mb"  => round($r["index_length"] / 1048576, 2)];
}
$aus["optimiert"] = getenv("EDOKU_OPTIMIEREN") === "ja";

/* Der Vollscan der Wartung (B-S2-05) — WIE LANGE dauert er hier?
 * Nur lesend nachgestellt: derselbe Anti-Join, aber als SELECT COUNT(*).
 * Ein DELETE waere eine Aenderung, und eine Messung soll nichts kaputtmachen. */
$t0 = microtime(true);
$waisen = zahl($pdo, "SELECT COUNT(*) FROM track_points tp
                       LEFT JOIN missions m ON m.id = tp.owner_id
                      WHERE tp.owner_type = 'mission' AND m.id IS NULL");
$aus["wartung_vollscan"] = ["waisen" => $waisen,
                            "dauer_s" => round(microtime(true) - $t0, 3)];

/* Der Sicherungsbau (B-S2-03) — Laufzeit und Speicherspitze.
 * Das ist die Stelle, von der das Konzept sagt, dass sie memory_limit
 * sprengt. Hier steht die Zahl. */
require_once $wurzel . "/backup_lib.php";
$vorher = memory_get_peak_usage(true);
$t0 = microtime(true);
$fehler = null; $laenge = 0;
try {
    $paket = edbak_build($userId);
    $laenge = strlen($paket);
    unset($paket);
} catch (Throwable $ex) {
    $fehler = get_class($ex) . ": " . $ex->getMessage();
}
$aus["edbak_build"] = [
    "dauer_s"        => round(microtime(true) - $t0, 2),
    "paket_mb"       => round($laenge / 1048576, 2),
    "spitze_vorher_mb" => round($vorher / 1048576, 1),
    "spitze_mb"      => round(memory_get_peak_usage(true) / 1048576, 1),
    "fehler"         => $fehler];

/* DERSELBE BAU, ABER SO, WIE DIE ANWENDUNG IHN SEIT WEB 11.1.0 GEHT
 * (S2/AP5b): Kopf und Eintragsfenster einzeln.
 *
 * WARUM BEIDE ZAHLEN. Die obige misst den Weg mit Punktlisten am Stueck —
 * den gehen nur noch die Admin-Sicherungen (AP6), und dort ist die Spitze
 * die Auskunft. Wer nur sie liest, haelt die Sicherung fuer einen
 * Gigabyten-Vorgang; das ist sie fuer die Nutzerin seit AP5b nicht mehr.
 * Eine Zahl, die nicht dazusagt, WELCHEN Weg sie gemessen hat, ist keine.
 *
 * ES IST EIN ZWEITER PROZESS, kein zweiter Aufruf: `memory_get_peak_usage()`
 * kennt nur ein Maximum je Prozess, und der Bau am Stueck hat es gerade auf
 * ueber ein Gigabyte gesetzt. Im selben Prozess gemessen kaeme fuer die
 * Fenster dieselbe Zahl heraus — und zwar eine, die nichts ueber sie sagt. */
$aus["edbak_fenster"] = ["hinweis" => "eigener Prozess, s. serverprobe.py"];

/* Der WARTUNGSJOB, echt gefahren (nur mit --wartung-fahren).
 *
 * Nicht nachgebaut, sondern die Funktion der Anwendung selbst:
 * run_cleanup_if_due() ist seit Web 10.1.0 der HUCKEPACK-Ausloeser des
 * Job-Rahmens (jobs_lib.php) — und genau der ist hier die Messgroesse, denn
 * was er kostet, wartet eine Nutzerin ab.
 *
 * ZURUECKGESETZT WIRD DIE FAELLIGKEIT, nicht der Fortschritt: `letzter_lauf`
 * auf NULL, damit sowohl die Tagesgrenze des Aufraeumjobs als auch der
 * Mindestabstand des Huckepack-Wegs (JOB_ANFRAGE_PAUSE_S) neu greifen. Das
 * ist derselbe Zustand wie am naechsten Morgen. Die Fortsetzungsmarke
 * (`zustand`) bleibt stehen — sie ist Teil dessen, was gemessen werden soll.
 *
 * WAS DIE ZAHL BEDEUTET: die Dauer EINES HAEPPCHENS am Huckepack-Weg, nach
 * oben durch JOB_BUDGET_ANFRAGE begrenzt. Sie ist nicht die Dauer eines
 * vollstaendigen Durchlaufs; dafuer ist `php jobs.php` da.
 */
if (getenv("EDOKU_WARTUNG") === "ja") {
    $vorher = zahl($pdo, "SELECT COUNT(*) FROM track_points");
    try { $pdo->exec("UPDATE jobs SET letzter_lauf = NULL, laeuft_seit = NULL"); }
    catch (Throwable $ex) { /* vor der Migration gibt es die Tabelle nicht */ }
    $t0 = microtime(true);
    run_cleanup_if_due();
    $aus["wartungsjob"] = [
        "dauer_s" => round(microtime(true) - $t0, 2),
        "zeilen_vorher" => $vorher,
        "zeilen_nachher" => zahl($pdo, "SELECT COUNT(*) FROM track_points"),
        "spitze_mb" => round(memory_get_peak_usage(true) / 1048576, 1)];
    $aus["wartungsjob"]["entfernt"] =
        $aus["wartungsjob"]["zeilen_vorher"] - $aus["wartungsjob"]["zeilen_nachher"];
    try {
        $aus["wartungsjob"]["jobs"] = $pdo->query(
            "SELECT job, rueckstand, letzter_ausloeser, erledigt_zuletzt, letzter_fehler
               FROM jobs ORDER BY job")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $ex) { $aus["wartungsjob"]["jobs"] = null; }
}

$aus["memory_limit"]      = ini_get("memory_limit");
$aus["post_max_size"]     = ini_get("post_max_size");
$aus["max_execution_time"] = ini_get("max_execution_time");
$aus["php"]               = PHP_VERSION;

echo json_encode($aus, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
'''
    e = subprocess.run(
        ["php", "-d", "memory_limit=-1", "-r", skript.replace("<?php\n", "", 1)],
        capture_output=True, text=True,
        env={**__import__("os").environ,
             "EDOKU_WURZEL": str(SERVER), "EDOKU_KONTO": konto,
             "EDOKU_OPTIMIEREN": "ja" if optimieren else "nein",
             "EDOKU_WARTUNG": "ja" if wartung else "nein"})
    if e.returncode != 0:
        raise RuntimeError(f"PHP-Messung gescheitert: {e.stderr.strip()[:400]}")
    return json.loads(e.stdout)


def php_fenster_messen(konto: str, fenster: int = 250) -> dict:
    """Der Sicherungsbau so, wie die Anwendung ihn seit Web 11.1.0 geht.

    EIGENER PROZESS, und das ist der Punkt. `memory_get_peak_usage()` kennt
    nur EIN Maximum je Prozess; nach dem Bau am Stueck stuende dort schon ueber
    ein Gigabyte, und die Fenster bekaemen eine Zahl, die nichts ueber sie
    sagt.

    UND MIT `memory_limit=64M`, nicht mit `-1`. Das ist die Z3-Grenze. Eine
    Messung ohne Deckel sagt, wie viel gebraucht wurde; eine mit Deckel sagt
    zusaetzlich, dass es reicht — und faellt auf, wenn es das nicht mehr tut.
    """
    skript = r'''<?php
declare(strict_types=1);
$wurzel = getenv("EDOKU_WURZEL");
$konto  = getenv("EDOKU_KONTO");
$fenster = max(1, (int)getenv("EDOKU_FENSTER"));
require_once $wurzel . "/config.php";
require_once $wurzel . "/db.php";
require_once $wurzel . "/backup_lib.php";
$pdo = db();
$st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$st->execute([$konto]);
$userId = (int)$st->fetchColumn();
if (!$userId) { fwrite(STDERR, "Konto $konto nicht gefunden\n"); exit(2); }

$t0 = microtime(true);
$kopf = edbak_build($userId, true, ["kopf" => true]);
$gesamt = (int)(json_decode($kopf, true)["eintraege_gesamt"] ?? 0);
$kopfMb = strlen($kopf) / 1048576;
unset($kopf);

$groesstes = 0; $summe = 0; $teile = 0;
for ($ab = 0; $ab < $gesamt; $ab += $fenster) {
    $t = edbak_build($userId, true, ["ab" => $ab, "anzahl" => $fenster]);
    $groesstes = max($groesstes, strlen($t));
    $summe += strlen($t);
    $teile++;
    unset($t);
}
echo json_encode([
    "fenster"          => $fenster,
    "eintraege_gesamt" => $gesamt,
    "teile"            => $teile,
    "dauer_s"          => round(microtime(true) - $t0, 2),
    "kopf_mb"          => round($kopfMb, 2),
    "groesstes_mb"     => round($groesstes / 1048576, 2),
    "summe_mb"         => round($summe / 1048576, 2),
    "spitze_mb"        => round(memory_get_peak_usage(true) / 1048576, 1),
    "memory_limit"     => ini_get("memory_limit"),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
'''
    e = subprocess.run(
        ["php", "-d", "memory_limit=64M", "-r", skript.replace("<?php\n", "", 1)],
        capture_output=True, text=True,
        env={**__import__("os").environ,
             "EDOKU_WURZEL": str(SERVER), "EDOKU_KONTO": konto,
             "EDOKU_FENSTER": str(fenster)})
    if e.returncode != 0:
        # EIN ABBRUCH IST HIER DAS ERGEBNIS, kein Werkzeugfehler: Er heisst,
        # dass der Fensterweg das Budget Z3 nicht mehr haelt.
        return {"fenster": fenster, "fehler": (e.stderr.strip() or e.stdout.strip())[:400],
                "memory_limit": "64M"}
    return json.loads(e.stdout)


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--konto", default="messstand@gen-em.org")
    p.add_argument("--ausgabe", default="/tmp/messstand/serverprobe.json")
    p.add_argument("--wartung-fahren", action="store_true",
                   help="run_cleanup_if_due() wirklich laufen lassen und messen "
                        "(setzt die Fälligkeit der Jobs zurück, LÖSCHT verwaiste "
                        "Zeilen); misst EIN Häppchen am Huckepack-Weg")
    p.add_argument("--optimieren", action="store_true",
                   help="track_points vorher neu aufbauen (OPTIMIZE TABLE) — "
                        "nur so sind die Bytes je Zeile aussagekräftig")
    a = p.parse_args()

    werte = php_messen(a.konto, a.optimieren, a.wartung_fahren)
    pfad = pathlib.Path(a.ausgabe)
    pfad.parent.mkdir(parents=True, exist_ok=True)

    print(f"Konto {werte['konto']}: {werte['einsaetze']} Einsätze, "
          f"{werte['spurpunkte']} Spurpunkte")
    sp = werte["tabellen"][0] if werte["tabellen"] else None
    if sp:
        print(f"Größte Tabelle: {sp['name']} — {sp['daten_mb']} MB Daten, "
              f"{sp['index_mb']} MB Index, {sp['zeilen']} Zeilen")
    if werte["einsaetze"]:
        gesamt = sum(t["daten_mb"] + t["index_mb"] for t in werte["tabellen"])
        spur = next((t for t in werte["tabellen"] if t["name"] == "track_points"), None)
        if spur and spur["zeilen"] and werte["spurpunkte"]:
            # NICHT die Tabellengroesse durch die Einsaetze DIESES Kontos
            # teilen. Die Tabelle traegt alle Konten der Installation; die
            # erste Fassung dieser Zeile rechnete 14,55 MB der ganzen Tabelle
            # gegen 87 Einsaetze des Referenzkontos und meldete 167 MB je
            # 1000 Einsaetzen — viermal zu viel. Eine Zahl, die nicht sagt,
            # was sie gemessen hat, ist keine Auskunft (CLAUDE.md 6).
            #
            # Richtig ist der Umweg ueber die Zeilenkosten: Was eine Zeile
            # wiegt, sagt die Tabelle; wie viele Zeilen auf einen Einsatz
            # dieses Kontos kommen, sagt das Konto.
            je_zeile = (spur["daten_mb"] + spur["index_mb"]) / spur["zeilen"]
            je_einsatz = werte["spurpunkte"] / werte["einsaetze"]
            werte["spur_bytes_je_zeile"] = round(je_zeile * 1048576, 1)
            werte["spur_mb_je_1000_einsaetze"] = round(je_zeile * je_einsatz * 1000, 2)
            print(f"Spurzeile: {je_zeile * 1048576:.1f} B "
                  f"(gezählt über {spur['zeilen']} Zeilen der ganzen Tabelle"
                  + (", Tabelle vorher neu aufgebaut)" if werte.get("optimiert")
                     else " — ACHTUNG: belegter Platz, enthält von früheren "
                          "Löschungen freigegebene Seiten; für die Bytes je "
                          "Zeile --optimieren nutzen)"))
            print(f"Punkte je Einsatz in diesem Konto: {je_einsatz:.0f}")
            print(f"→ Spuren je 1000 Einsätze DIESES Kontos: "
                  f"{je_zeile * je_einsatz * 1000:.2f} MB "
                  f"(Zielwert E-S2-24 nach Ausdünnung: ≤ 3 MB)")
        print(f"Datenbank gesamt (alle Konten): {gesamt:.1f} MB")
    b = werte["edbak_build"]
    print(f"edbak_build() am Stück, MIT Punktlisten (Admin-Sicherung, AP6): "
          f"{b['dauer_s']} s, Paket {b['paket_mb']} MB, "
          f"Speicherspitze {b['spitze_mb']} MB (Z3-Grenze: 64 MB)"
          + (f" — FEHLER {b['fehler']}" if b["fehler"] else ""))

    # DER WEG, DEN DIE ANWENDUNG SEIT WEB 11.1.0 GEHT — eigener Prozess mit
    # memory_limit=64M, s. php_fenster_messen(). Ohne diese Zeile las sich das
    # Protokoll so, als brauche jede Sicherung ein Gigabyte; das gilt nur noch
    # fuer die Admin-Sicherung.
    f = php_fenster_messen(a.konto)
    werte["edbak_fenster"] = f
    if f.get("fehler"):
        print(f"edbak_build() in Fenstern: ABGEBROCHEN bei memory_limit=64M "
              f"— {f['fehler']}")
    else:
        print(f"edbak_build() in Fenstern zu {f['fenster']} (Sicherung der "
              f"NutzerIn, Web 11.1.0): {f['dauer_s']} s, "
              f"{f['eintraege_gesamt']} Einträge in {f['teile']} Fenstern, "
              f"Kopf {f['kopf_mb']} MB, größtes Fenster {f['groesstes_mb']} MB, "
              f"Speicherspitze {f['spitze_mb']} MB von 64")
    w = werte["wartung_vollscan"]
    print(f"Waisen-Vollscan (B-S2-05): {w['dauer_s']} s über die ganze Tabelle, "
          f"{w['waisen']} verwaiste Einsatzpunkte gefunden")
    if werte.get("wartungsjob"):
        j = werte["wartungsjob"]
        print(f"Wartung huckepack (run_cleanup_if_due → jobs_lauf('anfrage')): "
              f"{j['dauer_s']} s für EIN Häppchen "
              f"(Budget: JOB_BUDGET_ANFRAGE, jobs_lib.php), "
              f"{j['entfernt']} Zeilen entfernt, "
              f"Speicherspitze {j['spitze_mb']} MB — so viel trägt die Anfrage "
              f"einer Nutzerin mit")
        for jj in (j.get("jobs") or []):
            print(f"    {jj['job']:<12} erledigt {jj['erledigt_zuletzt']}"
                  + (f", Rückstand {jj['rueckstand']}" if jj['rueckstand'] is not None else "")
                  + (f", FEHLER: {jj['letzter_fehler']}" if jj['letzter_fehler'] else ""))

    # ERST JETZT schreiben. Die abgeleiteten Werte oben (Zeilenkosten, MB je
    # 1000 Einsaetze) entstehen beim Ausgeben; eine Datei, die vorher
    # geschrieben wird, kennt sie nicht — und niemand sieht ihr das an.
    pfad.write_text(json.dumps(werte, ensure_ascii=False, indent=2) + "\n", "utf-8")
    print(f"\nProtokoll: {pfad}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
