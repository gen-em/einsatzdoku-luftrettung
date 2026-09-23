# -*- coding: utf-8 -*-
"""Wegprobe zum Spaltenregister — die zwei Anweisungen, die kein Kreislauf faehrt.

`pruefen.php` daneben misst STATISCH: Register gegen Schema, Abbildungen gegen
Register. Es kann nicht sagen, ob die erzeugten Anweisungen auch das Richtige
TUN. Fuer vier der sechs Zwecke sagt das der Referenzkreislauf
(`tools/referenzdatensatz/vergleich/kreislauf.py`): `export` und `import_neu`
im CSV-Umlauf, `backup_restore` im edbak-Umlauf, `ingest_neu` in der
Ingestprobe. Zwei bleiben uebrig:

  * `schnitt_neu`    — api/schneiden.php legt einen Einsatz aus einem
                       Ruhesegment an. Drei Werte stehen als Literal im Satz
                       (final, uhr_gesperrt, origin), und ob sie nach der
                       Umstellung noch an IHRER Spalte stehen, sieht man nur
                       an der entstandenen Zeile.
  * `import_aendern` — der UPDATE-Zweig von api/import_commit.php. Vier
                       Spalten tragen COALESCE, zwei ein Literal. Sitzt die
                       Schranke eine Spalte daneben, leert ein Rueckimport
                       eine Angabe, die im Bestand stand — und zwar still.

ACHTUNG, DIESE PROBE SCHREIBT. Sie schneidet einen Einsatz (die GPS-Punkte
WANDERN und stehen beim zweiten Lauf nicht mehr zur Verfuegung) und
ueberschreibt einen bestehenden Einsatz zweimal. Sie gehoert deshalb an ein
WEGWERFKONTO — die Umlaufkonten des Kreislaufs sind dafuer gemacht — und
niemals an das Demo- oder ein echtes Konto.

    python3 tools/spaltenregister/wegprobe.py \\
        --konto umlauf-edbak@gen-em.org --passwort umlaufpruefung2026

Rueckgabe 0 = alle Erwartungen erfuellt, 1 = mindestens eine nicht,
2 = nicht gelaufen (kein Segment mit GPS-Punkten, kein Einsatz).
"""
import argparse
import datetime
import json
import pathlib
import subprocess
import sys
import zoneinfo

WURZEL = pathlib.Path(__file__).resolve().parents[2]
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "einspielen"))
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "vergleich"))
import sitzung as sitzungsmodul   # noqa: E402
import kreislauf                  # noqa: E402 — Umlaufkonto und Passwort, EINE Stelle (RP-01)

ok = 0
fehl = 0


def pruefe(was: str, ist, soll) -> None:
    global ok, fehl
    gut = ist == soll
    print(f"  [{'ok  ' if gut else 'FEHL'}] {was:<56} ist {ist!r}, soll {soll!r}")
    if gut:
        ok += 1
    else:
        fehl += 1


def sql(abfrage: str):
    """Lesen an der Anwendung vorbei — die Probe soll die ZEILE sehen, nicht
    das, was eine API daraus macht."""
    php = ('$c = require "server/config.php";'
           '$p = new PDO($c["db"]["dsn"],$c["db"]["user"],$c["db"]["pass"],'
           '[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);'
           f'echo json_encode($p->query({json.dumps(abfrage)})->fetchAll(PDO::FETCH_ASSOC));')
    r = subprocess.run(["php", "-r", php], cwd=str(WURZEL),
                       capture_output=True, text=True)
    if r.returncode != 0:
        raise RuntimeError(f"SQL fehlgeschlagen: {r.stderr.strip()[:300]}")
    return json.loads(r.stdout)


def punktreichstes_segment(uid: int) -> dict | None:
    """Das Ruhesegment mit den meisten GPS-Punkten.

    UEBER `spur_lib.php`, NICHT per SQL (CLAUDE.md 4): Die Punkte liegen je
    nach Alter als Zeilen in `track_points` ODER als Blob in `track_blobs`.
    Ein COUNT(*) auf die Zeilentabelle findet die halbe Wahrheit.
    """
    php = ('$c = require "server/config.php";'
           '$p = new PDO($c["db"]["dsn"],$c["db"]["user"],$c["db"]["pass"],'
           '[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);'
           'require "server/spur_lib.php";'
           f'$s = $p->query("SELECT id, day_id FROM rest_segments WHERE user_id = {uid} '
           'AND deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);'
           '$z = spur_zahlen($p, "rest", array_map(fn($r)=>(int)$r["id"], $s));'
           'arsort($z); $b = array_key_first($z);'
           'echo json_encode($b === null ? null : ["id" => $b, "punkte" => $z[$b],'
           '  "day_id" => (int)array_values(array_filter($s, fn($r)=>(int)$r["id"]===$b))[0]["day_id"]]);')
    r = subprocess.run(["php", "-r", php], cwd=str(WURZEL),
                       capture_output=True, text=True)
    if r.returncode != 0:
        raise RuntimeError(f"spur_zahlen fehlgeschlagen: {r.stderr.strip()[:300]}")
    return json.loads(r.stdout)


def teil_schnitt(s, uid: int) -> None:
    print("1. api/schneiden.php — INSERT aus dem Register (Zweck schnitt_neu)")
    seg = punktreichstes_segment(uid)
    if seg is None or int(seg["punkte"]) < 40:
        print("  Kein Ruhesegment mit genug GPS-Punkten — Teil 1 nicht gelaufen.")
        sys.exit(2)
    tag = s.get(f"api/day.php?d={seg['day_id']}").json()
    treffer = [r for r in tag.get("rest_segments", []) if int(r["id"]) == int(seg["id"])]
    if not treffer:
        print("  Segment steht nicht in api/day.php — Teil 1 nicht gelaufen.")
        sys.exit(2)
    beginn = treffer[0]["start_hhmm"]
    print(f"  Segment {seg['id']} am Diensttag {seg['day_id']}, "
          f"{seg['punkte']} Punkte, Beginn {beginn} Ortszeit")

    hh, mm = (int(x) for x in beginn.split(":"))
    bis = hh * 60 + mm + 20
    ende = f"{bis // 60:02d}:{bis % 60:02d}"
    a = s.json_post("api/schneiden.php", {
        "action": "schneiden", "rest_id": int(seg["id"]),
        "beginn": beginn, "ende": ende, "beginn_tag": 0, "ende_tag": 0,
        "phasen": {"3": beginn, "4": ende},
    })
    pruefe("HTTP-Code des Schnitts", a.status_code, 200)
    if a.status_code != 200:
        print("  Rumpf:", a.text[:400])
        return
    o = a.json()
    mid = int(o["mission_id"])
    z = sql(f"SELECT * FROM missions WHERE id = {mid}")[0]
    pruefe("origin",                     z["origin"], "schnitt")
    pruefe("final",                      int(z["final"]), 1)
    pruefe("uhr_gesperrt",               int(z["uhr_gesperrt"]), 1)
    pruefe("user_id",                    int(z["user_id"]), uid)
    pruefe("day_id",                     int(z["day_id"]), int(seg["day_id"]))
    pruefe("keine verworfenen Angaben",  o.get("rejected") or [], [])
    pruefe("GPS-Punkte gewandert",       int(o.get("genommen", 0)) > 0, True)
    dev = sql(f"SELECT device_id FROM devices WHERE id = {int(z['device_id'])}")[0]
    pruefe("Geraet ist das virtuelle",   dev["device_id"].startswith("manual-"), True)
    # Gegenprobe: Der Schnitt fuellt KEINE Einsatzfelder. Stuende hier etwas,
    # haette die erzeugte Liste eine Spalte zu viel erwischt.
    pruefe("transport_dest leer",        z["transport_dest"], None)
    pruefe("pat_blob leer",              z["pat_blob"], None)
    phasen = sql(f"SELECT phase FROM mission_phases WHERE mission_id = {mid} ORDER BY phase")
    pruefe("Phasen angelegt",            [int(p["phase"]) for p in phasen], [3, 4])


def teil_import(s, uid: int) -> None:
    print()
    print("2. api/import_commit.php — UPDATE aus dem Register (Zweck import_aendern)")
    reihen = sql(f"""SELECT m.id, m.day_id, d.day, m.started_at
                     FROM missions m JOIN days d ON d.id = m.day_id
                     WHERE m.user_id = {uid} AND m.deleted_at IS NULL
                       AND d.deleted_at IS NULL AND m.origin <> 'schnitt'
                     ORDER BY m.id LIMIT 1""")
    if not reihen:
        print("  Kein Einsatz zum Ueberschreiben — Teil 2 nicht gelaufen.")
        sys.exit(2)
    z = reihen[0]
    ortszeit = (datetime.datetime.fromisoformat(z["started_at"] + "+00:00")
                .astimezone(zoneinfo.ZoneInfo("Europe/Berlin")).strftime("%H:%M"))
    print(f"  Einsatz {z['id']}, Diensttag {z['day']}, Beginn {ortszeit} Ortszeit")

    def commit(felder: dict):
        return s.json_post("api/import_commit.php", {
            "action": "commit",
            "days": [{"day": z["day"], "mode": "keep"}],
            "missions": [dict({"day": z["day"], "started_local": ortszeit,
                               "dup": "overwrite", "overwrite_id": int(z["id"])},
                              **felder)],
        })

    # Durchgang A — alles gesetzt, auch die vier Felder unter der Schranke.
    a = commit({"transport_dest": "Klinik Probe A", "winch": 1, "crew_override": 0,
                "site_ele_m": 777, "distance_m": 12345, "ascent_m": 234,
                "schockraum": 1, "secondary": 1, "winch_cycles": 3,
                "winch_cycles_pat": 2, "winch_airload": 1, "bergwacht": 1,
                "bw_unit": "BW Probe", "bw_info": "Info A", "other_ema": "EMA A",
                "transport_mode": "ground", "na_escort": 1, "false_alarm": 0,
                "dest_lat": 47.5, "dest_lon": 11.5, "start_src": "base", "final": 0})
    pruefe("Durchgang A — HTTP", a.status_code, 200)
    if a.status_code != 200:
        print("  Rumpf:", a.text[:400])
        return
    r = sql(f"SELECT * FROM missions WHERE id = {int(z['id'])}")[0]
    for feld, soll in [("transport_dest", "Klinik Probe A"), ("bw_unit", "BW Probe"),
                       ("bw_info", "Info A"), ("other_ema", "EMA A")]:
        pruefe(f"A: {feld}", r[feld], soll)
    for feld, soll in [("site_ele_m", 777), ("distance_m", 12345), ("winch", 1),
                       ("winch_cycles", 3), ("final", 0),
                       ("uhr_gesperrt", 1), ("edited", 1)]:
        pruefe(f"A: {feld}", int(r[feld]), soll)

    # Durchgang B — die VIER Felder unter der Schranke fehlen in der Datei.
    # COALESCE muss genau diese vier halten und KEIN weiteres (A9, P10).
    a = commit({"transport_dest": "Klinik Probe B", "winch": 0, "crew_override": 0,
                "distance_m": 999, "ascent_m": 1, "schockraum": 0, "secondary": 0,
                "winch_cycles": None, "winch_cycles_pat": None, "winch_airload": 0,
                "bergwacht": 0, "transport_mode": "air", "na_escort": 0,
                "false_alarm": 1, "start_src": "manual", "final": 1})
    pruefe("Durchgang B — HTTP", a.status_code, 200)
    r = sql(f"SELECT * FROM missions WHERE id = {int(z['id'])}")[0]
    pruefe("B: transport_dest geaendert",        r["transport_dest"], "Klinik Probe B")
    pruefe("B: winch geaendert",                 int(r["winch"]), 0)
    pruefe("B: distance_m geaendert",            int(r["distance_m"]), 999)
    pruefe("B: false_alarm geaendert",           int(r["false_alarm"]), 1)
    pruefe("B: bw_unit geleert (KEIN COALESCE)", r["bw_unit"], None)
    pruefe("B: site_ele_m gehalten (COALESCE)",  int(r["site_ele_m"]), 777)
    pruefe("B: bw_info gehalten (COALESCE)",     r["bw_info"], "Info A")
    pruefe("B: other_ema gehalten (COALESCE)",   r["other_ema"], "EMA A")
    pruefe("B: pat_blob gehalten (COALESCE)",    r["pat_blob"] is not None, True)


def main() -> int:
    p = argparse.ArgumentParser(description="Wegprobe zum Spaltenregister")
    p.add_argument("--basis", default="https://127.0.0.1:8443")
    # OHNE ANGABE das Umlaufkonto des csv-Kreislaufs, das der Pruefstand
    # vorher faehrt (`nach` in pruefablauf.json, F-RP-08). Bis RP-01 kamen
    # Konto und Passwort aus zwei Umgebungswerten, die niemand setzte.
    p.add_argument("--konto", default=kreislauf.umlauf_konto("csv"),
                   help="WEGWERFKONTO, nicht die Demo")
    p.add_argument("--passwort", default=kreislauf.UMLAUF_PASSWORT)
    a = p.parse_args()

    if a.konto.startswith("demo@"):
        print("Diese Probe schreibt. Nicht gegen das Demo-Konto fahren.")
        return 2

    s = sitzungsmodul.Sitzung(a.basis).anmelden(a.konto, a.passwort)
    reihen = sql(f"SELECT id FROM users WHERE email = {json.dumps(a.konto)}")
    if not reihen:
        print(f"Konto {a.konto} nicht gefunden. Das Umlaufkonto entsteht im "
              f"Kreislauf csv — erst den fahren.")
        return 2
    uid = int(reihen[0]["id"])
    print(f"Wegprobe Spaltenregister — Konto {a.konto} (id {uid})\n")

    teil_schnitt(s, uid)
    teil_import(s, uid)

    print()
    print(f"  -> {ok + fehl} Erwartungen, {fehl} nicht erfuellt")
    return 1 if fehl else 0


if __name__ == "__main__":
    sys.exit(main())
