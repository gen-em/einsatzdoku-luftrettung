# Kette — die Werkzeuge der Auslieferung

Sieben Befehle der Kette, die niemand von Hand fährt.
**Anlass: Nr. 219** (Kettenschritt nur gegen die eigene Anlage), **Nr. 100** (Apps von Hand).

## Aufruf

```bash
python3 tools/kette/tor.py backup|pause|zustand|wartung-an|wartung-aus --basis <url> --token <t>
python3 tools/kette/freigabe.py            # --selbstprobe | urteil --laeufe … --baum … --stufe1-laeufe …
python3 tools/kette/zielprobe.py <url>  ·  python3 tools/kette/zustand.py   # je --selbstprobe
python3 tools/kette/baumsuche.py --baum <sha> --fenster <n> [--ereignis pull_request] [--erster | --ausgabe f.json]
bash tools/kette/appbau.sh android|uhr <fassung|datei> <ausgabe>   # --selbstprobe
python3 tools/kette/apkablage.py --art handy|uhr --datei <apk> --ftp-server … [--probelauf]  # --selbstprobe
```

## Was es misst

`tor.py` wartet auf ein **fertiges und neues** Komplett-Backup; `freigabe.py`:
darf ein Stand auf Produktiv (Stufe 1 **nach Baum**, Staging **nach Commit**);
`zielprobe.py`: liegt / fehlt / nicht feststellbar; `zustand.py` legt die
fehlende Zustandsdatei hin; `baumsuche.py`: grüne Stufe-1-Läufe desselben
Baums. `appbau.sh` baut und signiert (Android über `apksigner` nach dem Bau,
Zertifikat gegen `APK_ZERTIFIKAT_SHA256` und `078c…ad64`; Garmin `monkeyc -e`);
`apkablage.py` lädt hoch, vergleicht, benennt um, **erst dann** löscht sie.

## Was es braucht

Anlage und `JOBS_TOKEN`; `gh` und `GH_TOKEN`; Android-SDK bzw. `monkeyc` und
die Schlüssel als Umgebungswerte (`appbau.sh`); `curl` (Zielprobe, Ablage).

## Erwartete Zahl

Selbstproben: `freigabe.py` **52 / 0**, `baumsuche.py` **11 / 0**, `appbau.sh`
**23 / 0**, `apkablage.py` **25 / 0**; Stufe 1 oder die Kette fährt jede.

## Was es nicht kann

Nichts über den **Inhalt** sagen (dafür die Integritätswache); `appbau.sh`
prüft, **womit** signiert ist, nicht, ob die App funktioniert.
