# Kette — die Werkzeuge der Auslieferung

Die Befehle der Kette, die niemand von Hand fährt.
**Anlass: Nr. 219** (Kettenschritt nur gegen die eigene Anlage), **Nr. 100** (Apps von Hand).

## Aufruf

```bash
JOBS_TOKEN=… python3 tools/kette/tor.py backup|pause|zustand|wartung-an|wartung-aus --basis <url>
python3 tools/kette/freigabe.py urteil|app-urteil …   # --selbstprobe; Schalter im Dateikopf
python3 tools/kette/zielprobe.py <url> · zustand.py · baumsuche.py --baum <sha> --fenster <n>
bash tools/kette/appbau.sh android-bauen|uhr <fassung|datei> <aus> · android-signieren <f> <ein> <aus>
python3 tools/kette/apkablage.py --art handy|uhr --datei <apk> --ftp-server … [--probelauf]  # --selbstprobe
```

## Was es misst

`tor.py`: ein **fertiges und neues** Komplett-Backup, Kettentoken im POST-Rumpf,
im Protokoll ohne Zufallsteil des Namens und ohne `wartung.von` (F-KA-25).
`freigabe.py`: Produktiv (Stufe 1 nach Baum, Staging nach Commit) und App-Tags.
`zielprobe.py`, `zustand.py`, `baumsuche.py`: Ziel, Zustandsdatei, grüne Läufe
desselben Baums. `appbau.sh`: Android in zwei Jobs — `android-bauen` ohne
Schlüssel, `android-signieren` ohne Gradle (F-R17-03); Garmin `monkeyc -e`.
`apkablage.py` lädt hoch, vergleicht, **erst dann** löscht sie.

## Was es braucht

Anlage; `JOBS_TOKEN`, `FTP_PASSWORD` **nur als Umgebungswerte** (KA-01,
F-KA-37); `gh`, `GH_TOKEN`, `curl`; Android-SDK bzw. `monkeyc`, Schlüssel als
Umgebungswerte (`android-bauen` baut nicht mit `APK_*`). Playwright für Stufe 2
aus `stufe2-npm/package-lock.json`, Fassung = `PW_FASSUNG` in `aufbauen.sh`.

## Erwartete Zahl

Selbstproben (25.10.2026): `freigabe.py` **63/0**, `baumsuche.py` **11/0**, `appbau.sh` **30/0**, `apkablage.py` **25/0**; seit KA-01 (10.11.2026) `tor.py` **43/0**, `zielprobe.py` **95/0**, `zustand.py` **28/0**.

## Was es nicht kann

Nichts über den **Inhalt** (dafür die Wache); `appbau.sh` prüft, **womit** signiert
ist — nicht, ob die App läuft oder eine gepinnte Abhängigkeit böswillig ist.
