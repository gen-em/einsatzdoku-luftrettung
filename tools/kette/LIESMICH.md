# Kette — die Werkzeuge der Auslieferung

Die Befehle der Kette, die niemand von Hand fährt.
**Anlass: Nr. 219** (Kettenschritt nur gegen die eigene Anlage), **Nr. 100** (Apps von Hand).

## Aufruf

```bash
python3 tools/kette/tor.py backup|pause|zustand|wartung-an|wartung-aus --basis <url> --token <t>
python3 tools/kette/freigabe.py urteil|app-urteil …   # --selbstprobe; Schalter im Dateikopf
python3 tools/kette/zielprobe.py <url> · zustand.py · baumsuche.py --baum <sha> --fenster <n>
bash tools/kette/appbau.sh android-bauen|uhr <fassung|datei> <aus> · android-signieren <f> <ein> <aus>
python3 tools/kette/apkablage.py --art handy|uhr --datei <apk> --ftp-server … [--probelauf]  # --selbstprobe
```

## Was es misst

`tor.py`: ein **fertiges und neues** Komplett-Backup, Kettentoken im POST-Rumpf.
`freigabe.py`: Produktiv (Stufe 1 nach Baum, Staging nach Commit), seit R17-19
auch App-Tags (Stufe 1, Tag auf `main`). `zielprobe.py`, `zustand.py`,
`baumsuche.py`: Ziel, Zustandsdatei, grüne Läufe desselben Baums. `appbau.sh`:
Android seit R17-19 in zwei Jobs — `android-bauen` ohne Schlüssel, Gradle nur
mit Positivliste; `android-signieren` mit `apksigner`, ohne Gradle (F-R17-03);
Garmin `monkeyc -e`. `apkablage.py` lädt hoch, vergleicht, **erst dann** löscht sie.

## Was es braucht

Anlage und `JOBS_TOKEN` (das Kettentoken); `gh` und `GH_TOKEN`; Android-SDK
bzw. `monkeyc`, die Schlüssel als Umgebungswerte — `android-bauen` baut nicht,
wenn ein `APK_*` in seiner Umgebung steht; `curl` (Zielprobe, Ablage).

## Erwartete Zahl

Selbstproben (25.10.2026): `freigabe.py` **63/0**, `baumsuche.py` **11/0**, `appbau.sh` **30/0**, `apkablage.py` **25/0**, `tor.py` **36/0**.

## Was es nicht kann

Nichts über den **Inhalt** (dafür die Wache); `appbau.sh` prüft, **womit**
signiert ist — nicht, ob die App funktioniert oder eine gepinnte Abhängigkeit
böswillig ist.
