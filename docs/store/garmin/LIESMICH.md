# Eintrag im Connect IQ Store

Alles, was der Eintrag der Garmin-App im Connect IQ Store braucht: Cover,
Hero-Bild, Bildschirmfotos, Beschreibung und die übrigen Formularwerte.
Der Eintrag ist **nur deutsch**; eine englische Fassung gibt es bewusst nicht
(Entscheidung der Betreiberin, 19.10.2026).

*Bis zum 19.10.2026 lag dieser Ordner als `docs/store/` ohne `garmin/`, und
er enthielt nur die Bildschirmfotos.*

| Datei | Store-Feld | Format |
|---|---|---|
| `cover.png` | Cover Image (Web/Mobile) | 500 × 500 px, PNG, 24 KB (Grenze 300 KB) |
| `hero.png` | Hero Image | 1440 × 720 px, PNG, 76 KB (Grenze 2048 KB) |
| `uhr-store/…/*.png` | Screen Images — **diese hochladen** | 800 × 800 px, PNG mit 256 Farben, 63 bis 113 KB (Grenze 150 KB) |
| `uhr/…/*.png` | — | Vorlage in voller Farbtiefe, für den Store zu groß |
| `quellen/*.html` | — | Vorlagen für Cover und Hero |

## Cover und Hero

Dunkelblau (`--dunkelblau`), die beiden weißen Bildmarken
(`server/assets/images/gen-em_logo_*_weiss.svg`), die Farbstreifen Blau,
Orange, Rot und die Projektschriften (Bricolage Grotesque 600 für „NAdoku",
Open Sans für den Rest). Alles liegt im Repositorium; die Vorlagen ziehen es
über relative Pfade aus `server/assets/`.

Der Satz im Hero-Bild lautet „Einsatztracking am Handgelenk – verschlüsselt
direkt auf der Uhr. In Ruhe im Web ansehen und nacharbeiten." **„Direkt auf
der Uhr" und nicht „sofort":** Die Uhr verschließt beim Senden; bis dahin
liegt der Puffer im Klartext (`CLAUDE.md` Abschnitt 4, Grenze 1).
Verschlossen werden GPS-Spur, Phasenkoordinaten und Reanimation, nicht
Zeiten und Phasennummern — die Beschreibung sagt es ausführlich.

**Neu erzeugen** (ein Rezept, kein Werkzeug): die Vorlage in Chromium in der
Bildgröße öffnen, `document.fonts.ready` abwarten, Bildschirmabzug — etwa mit
Playwright aus `/opt/node-tools`, Viewport 500 × 500 bzw. 1440 × 720.
Gegenprobe am 19.10.2026: beide aus den Vorlagen im Repositorium neu
erzeugt, `compare -metric AE` gegen die abgelegten Bilder **0 abweichende
Pixel**.

## Beschreibung (Feld „Description", rund 2 100 von 4 000 Zeichen)

```
NAdoku ist die Einsatzdokumentation für Notärztinnen und Notärzte – luftgebunden wie bodengebunden (RTH, NEF, NAW). Die Uhr hält fest, was im Einsatz passiert; ausgearbeitet wird später in Ruhe in der NAdoku-Weboberfläche.

AUF DER UHR
• Dienst mit einem Tastendruck beginnen und beenden
• Einsatzphasen mit Zeitstempel: Alarmierung, Ausrücken, Ankunft Einsatzort, Ankunft PatientIn, Transportbeginn, Ankunft Klinik, Übergabe, Einsatzende
• GPS-Spur jedes Einsatzes mit Strecke, Anstieg und Geschwindigkeit
• Reanimationsmodus mit 2-Minuten-Zyklus: Rhythmuskontrolle, Defibrillation, Adrenalin, Amiodaron, Zugang, Intubation, Sonographie, ROSC – jede Maßnahme mit Uhrzeit, danach als Übersicht
• Funktioniert ohne Verbindung: Die Daten bleiben auf der Uhr und werden über dein Handy (Garmin-Connect-App) gesendet, sobald eine Verbindung besteht

NUR MIT NADOKU-KONTO
Die App arbeitet nicht für sich allein. Sie überträgt an einen NAdoku-Server – voreingestellt ist die öffentliche Installation nadoku.gen-em.org. Betreibst du einen eigenen Server, trägst du seine Adresse in den App-Einstellungen ein. Konten auf der öffentlichen Installation gibt es derzeit nur auf Einladung.
Koppeln: Öffne auf der Uhr die Sync-Seite, halte START gedrückt und gib den sechsstelligen Code in der Weboberfläche unter Einstellungen → Geräte ein.

DATENSCHUTZ
GPS-Spur, Phasenkoordinaten und Reanimationsverlauf verschlüsselt die Uhr mit dem öffentlichen Schlüssel deines Kontos, bevor sie das Gerät verlassen; öffnen kann sie nur dein Browser. Patientendaten (Name, Diagnose, Adresse …) werden auf der Uhr nicht erfasst – sie kommen in der Weboberfläche hinzu und sind dort Ende-zu-Ende-verschlüsselt. Bis zum Senden liegen die Daten auf der Uhr unverschlüsselt im Zwischenspeicher.

KEIN MEDIZINPRODUKT
NAdoku ist ein Dokumentationshilfsmittel. Die App gibt keine diagnostischen oder therapeutischen Hinweise und ersetzt nicht das offizielle Einsatzprotokoll.

Bedienung über Tasten, auf Uhren mit Touch wahlweise auch per Touch. Unterstützt werden Uhren mit fünf Tasten sowie Venu, vivoactive 5 und Approach S70. Sprachen: Deutsch, Englisch.
```

Phasennamen und Reanimationsereignisse stammen aus `watch/source/Const.mc`
(`PHASE_LABELS`) und `watch/source/CprView.mc`. **Wer dort etwas umbenennt,
zieht diesen Text nach** — der Store liest nicht aus dem Repositorium.
Ebenso, wenn die öffentliche Installation die Registrierung öffnet: Dann
fällt „derzeit nur auf Einladung".

## Übrige Formularwerte

| Feld | Wert | Warum |
|---|---|---|
| Title | `Gen-EM NAdoku` | |
| What's New | leer | erste Store-Fassung |
| Category | Medical | eine Unterkategorie bietet der Store dort nicht an |
| Privacy Policy | Ja, `https://nadoku.gen-em.org/datenschutz.php` | die App erfasst und überträgt Standortdaten |
| ANT+ Profiles | Nein | |
| Regional Limits | Nein | |
| Device Icons | Nein | die Icons kommen aus dem Paket (`watch/resources-icon*`) |
| Preview Video | leer | |
| Email Address | `dev@gen-em.org` | öffentlich sichtbar |
| Source Code URL | `https://github.com/gen-em/einsatzdoku-luftrettung` | |
| Review Notification | Ja | |
| App Migration | **Nein** | Sonst schaltet Garmin neue Uhren ungemessen frei. Ohne langen Tastendruck ist die App nicht bedienbar, und eine neue Geräteklasse ist immer eine Messung wert (Kopf der Geräteliste in `watch/manifest.xml`). |
| Monetization | Nein | |
| Companion App | leer | Die Garmin-App spricht über Garmin Connect; die Android-App ist nicht ihr Begleiter und steht nicht bei Google Play. |
| Additional Hardware | leer | |

## Bildschirmfotos

**Hochgeladen wird `uhr-store/`, nicht `uhr/`.** Der Store nimmt je Bild
höchstens 150 KB (153 600 Byte). Die Vorlagen unter `uhr/` liegen alle
darüber (155 967 bis 266 938 Byte); `uhr-store/` hält dieselben 21 Bilder in
derselben Größe, auf 256 Farben reduziert:

```
convert uhr/<geraet>/<datei>.png -dither FloydSteinberg -colors 256 -strip \
        PNG8:uhr-store/<geraet>/<datei>.png
```

Gemessen am 19.10.2026: 21 von 21 unter der Grenze (63 048 bis 112 644
Byte), alle 800 × 800 px, kleinster Abstand zur Vorlage **PSNR 43,1 dB**
(`fr965/4_rea_timer.png`); dieses und `fr965/3_zeiten.png` neben der
Vorlage angesehen, kein Unterschied sichtbar. Ein JPG wäre kleiner, aber
schlechter (PSNR 40,8 dB an `fr965/3_zeiten.png`) — Schrift auf Schwarz
franst darin aus. **Wer eine Vorlage unter `uhr/` ersetzt, erzeugt die
Store-Fassung neu.**

Sieben Ansichten auf drei Uhren, die die drei Bauformen der Zielgeräte
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

## Wie die Bildschirmfotos entstanden sind (19.10.2026)

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
