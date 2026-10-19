# Bilder für den Connect IQ Store

Bildschirmfotos der Garmin-App (Uhr 4.0.0) für den Eintrag im Connect IQ
Store. Sieben Ansichten auf drei Uhren, die die drei Bauformen der Zielgeräte
vertreten:

| Ordner | Uhr | Bauform |
|---|---|---|
| `uhr/fenix7/` | fēnix 7 | rund, 260 px, MIP, fünf Tasten und Touch |
| `uhr/fr965/` | Forerunner 965 | rund, 454 px, AMOLED, fünf Tasten und Touch |
| `uhr/venu3/` | Venu 3 | rund, 454 px, AMOLED, drei Tasten und Touch |

| Datei | Ansicht |
|---|---|
| `1_start.png` | Startbildschirm „Dienst beginnen?“ |
| `2_einsatz.png` | Einsatzuhr, Phase 5 „Ankunft PatientIn“ |
| `3_zeiten.png` | Einsatzübersicht mit den Zeiten der Phasen |
| `4_rea_timer.png` | laufende Reanimation, 2-Minuten-Zyklus |
| `5_rea_menue.png` | Reanimationsmenü, Adrenalin gewählt |
| `6_rea_uebersicht.png` | Reanimationsübersicht mit den Ereignissen |
| `7_statistik.png` | Tagesstatistik „Heute 2 Einsätze“ |

Format: 800 × 800 px, PNG, weißer Grund, die Uhr mit Gehäuse mittig. Das
Gehäuse ist das Gerätebild aus dem Simulator des Connect IQ SDK, also
**Bildmaterial von Garmin**.

**Diese Bilder liegen bewusst nicht unter `docs/bilder/`.** Die
Auslieferungskette kopiert `docs/bilder/` auf den Webserver
(`ausliefern-lauf.yml`); Store-Bilder haben dort nichts zu suchen.

## Wie sie entstanden sind (19.10.2026)

Ein einmaliger Lauf, kein Werkzeug. Was davon wiederkehren soll, gehört nach
`tools/erzeugen/` und braucht dann eine Backlog-Nummer.

1. **Simulator:** `tools/uhr-pruefstand/pruefstand.sh aufbau` (SDK 9.2.0,
   1332 Schriften, 99 von 99 Geräten, 0 fehlende Bibliotheken).
2. **Kopplung nur vorgetäuscht:** Übersetzt wurde eine **Kopie** von
   `watch/`, in deren `properties.xml` `serverUrl = bildstrecke.invalid`
   und `deviceId = apiKey = demo` standen. So fehlt der rote
   Einrichtungshinweis, und die App kann keinen Server erreichen — schon gar
   nicht Produktiv. Der Quelltext im Repositorium ist unverändert.
3. **Uhrzeit:** Der Simulator lief unter `faketime -f "+<Versatz> x6"` mit
   `FAKETIME_DONT_FAKE_MONOTONIC=1`, Start 13:38, sechsfach beschleunigt.
   Die Form `@<Zeitpunkt>` friert die Uhr ein; dann laufen die Zeitgeber der
   App nicht.
4. **Bedienung** über `xdotool` (Folge je Gerät: Dienst beginnen, zwei
   Einsätze durchklicken und abschließen, dritter Einsatz mit Alarm 14:06,
   Ausrücken 14:08, Ankunft 14:19 und 14:22, Reanimation ab 14:25 mit
   Rhythmuskontrolle, Defibrillation, Adrenalin, dann „Timer neu starten“).
   Die Venu blättert mit Wischen statt mit UP/DOWN. **Die ersten
   Tastendrücke nach dem Laden gehen verloren** (Kopf von `bildreihe()` im
   Prüfstand) — vorweg zwei Wegwerf-Drücke.
5. **Zuschnitt:** Bildabzug des X-Servers, das Gerätebild ohne Fensterrand
   ausgeschnitten (Breite und Höhe des Gerätebilds je 2 px weniger, ab
   Fensterpunkt 1/26), auf ein Quadrat mit 8 % Rand gesetzt und mit Lanczos
   auf 800 × 800 skaliert. Die fēnix 7 wird dabei vergrößert (Gerätebild
   409 × 560), die beiden anderen verkleinert.

## Was nicht dabei ist und warum

- **Tempo-Seite:** zeigt ohne GPS 0 km/h. Das Simulationsmenü des Simulators,
  über das eine Fahrt abzuspielen wäre, bleibt unter Xvfb schwarz.
- **Sync-Seite:** zeigt ohne echte Kopplung „Kein Schlüssel“ und
  „GPS zu schwach“ in Rot.
