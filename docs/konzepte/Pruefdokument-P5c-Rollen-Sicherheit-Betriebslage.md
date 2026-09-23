# Prüfdokument P5c — Rollen, Sicherheit, Betriebslage

Gehört zu `Konzept-P5c-Rollen-Sicherheit-Betriebslage.md`. Nach `CLAUDE.md`
7: was maschinell geprüft wurde (Mittel **und** Zahl), was im Browser, was
nicht und warum, und eine abhakbare Prüfliste — je Punkt der Bedienweg, das
erwartete Ergebnis und woran ein Scheitern zu erkennen ist. Angelegt mit
AP1; jedes Paket schreibt seinen Abschnitt fort.

## 0. Was nicht geprüft werden konnte

*Steht vorn, weil es das ist, was noch jemand tun muss.*

| Was | Warum nicht | Wann dann |
|---|---|---|
| **Staging in Rot, Produktiv in Blau** | Beides sind echte Anlagen (Station D und E). Örtlich ist das Etikett gestellt und gemessen (1), aber ob die `config.php` von Staging den Eintrag trägt, sieht nur, wer Staging aufruft. | nach dem Merge (Staging) und nach dem Tag (Produktiv): P-P5c-01, -02 |
| **Eine Rundmail in einem echten Postfach** | Die Arbeitsumgebung erreicht keinen Mailserver; die Mailprobe spricht mit einem SMTPS-Nachbau, der annimmt und wegwirft. Gemessen ist der Weg bis „250 angenommen", nicht die Zustellung. | P-P5c-03 |
| **Firefox und WebKit** | Bilderlauf und Bedienwege sind in Chromium gefahren (Stufe „neben"); die drei Engines fährt erst die Hauptstufe. | mit dem ersten Paket in Stufe „haupt" (AP4) |
| **Die Ankündigung auf Staging unter Last des Huckepack-Jobs** | Örtlich gibt es kein Cron und keine fremden Aufrufe; die Rundmail wird hier mit `mail_job()` von Hand hinausgetragen. Wie lange vierzig Mails auf Staging brauchen, hängt davon ab, wer dort Seiten aufruft. | P-P5c-03 (Zeit notieren) |

## 1. Messprotokoll AP1 (23.09.2026, Web 20.38.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| Mailprobe, erweitert um Abschnitt 14 | `bash tools/proben/proben.sh mail` | **50 Prüfungen, 0 Befunde** (vorher 41). Neu: 6 erreichbare Konten → 6 Zeilen, **0,01 s gegen einen schweigenden Server** (sofort versucht: bis 30 s), 6 von 6 zugestellt in 1 Joblauf, zweite Rundmail abgewiesen, genau 1 Protokolleintrag; ohne Passwort, unbestätigt, gesperrt und Demo-Konto **nicht** darunter |
| Bedienwege der Seite | `node tools/bedienprobe/probe.mjs --nur betrieb-server` | **2 von 2 erfüllt**: gesetzt → sichtbar · weggeklickt → fort, auch auf Status · neu angemeldet → wieder da · entfernt → fort; die Rückfrage nennt „3 erreichbare Konten", Abbrechen schreibt nichts (Protokoll 0 → 0) |
| Kontraste | `python3 tools/screenshots/kontrast.py` | **25 Paare, 0 verfehlt** — neu: Weiß auf Rot 4,78, Schnee auf Rot 4,68, Orange hell auf Rot **4,12** (Soll 3,0) |
| Register | `php tools/zaehlung/zaehlen.php` | **38 Zeilen, 0 über der Decke** (Z29 csrf 2/2, Z37 Meldung 4/4, Z10 app_state 2/2, Z38 `error_log(` 75/77 — AP1 hat keinen Aufruf dazugebracht) |
| Quelltext | `bash tools/quelltext/pruefen.sh alle` | **8 von 8 grün**, Textprobe 0 außerhalb der Ausnahmen |
| Abdeckung | `python3 tools/pruefstand/auswahl.py --abdeckung` | 0 ohne Muster; `ankuendigung_lib.php` löst die Mailprobe aus |
| Statuszeile „Umgebung", vier Fälle | eigener Lauf, `config.php` gestellt über `tools/konfig_stellen.php` | Präfix ohne Etikett → orange „Präfix ohne Etikett" · Farbe `lila` → orange „Farbe unbekannt", Kopfleiste rot · Staging ohne Präfix → blau „Staging … Mails ohne Präfix" · ohne Eintrag → blau „Produktiv … Kopfleiste blau, Mails ohne Präfix". `config.php` danach unverändert (0 Zeilen mit `umgebung`) |
| **Bilderlauf mit Etikett** (Station B, Stufe „neben", 8 Breiten) | `php mit_etikett.php node tools/screenshots/aufnehmen.mjs --stufe neben --etikett Staging` — der Umschalter stellt `app.umgebung` und `mail.betreff_praefix` über `tools/konfig_stellen.php`, wartet 3 s (F-P5c-69) und stellt danach zurück; dazu eine gesetzte Ankündigung (Ton Warnung, 2 h) | **496 Einzelbilder aus 62 Seiten** (Anmelde- und Wartungsseite eingeschlossen), 13 min 8 s: **Überlauf 0 · Konsolenfehler 0 · Knöpfe falscher Höhe 0 · Karten außerhalb von `main.inhalt` 0 von 163 · gleiche Bilder über Breiten 0**. Etikett: **496 Titel und 424 Kopfleisten geprüft, 24 Abweichungen** — alle auf Notfallblatt (zwei Seiten) und Schlüsselblatt, deren Titel den Vorsatz nicht trug (F-P5c-70). Behoben und die drei Seiten nachgefahren: **24 Titel, 0 Abweichungen**. `config.php` danach: 0 Zeilen mit `umgebung` |
| **Bilderlauf ohne Etikett** (Gegenrichtung) | im Prüfstand (Stufe „neben") | kein Titel mit „[", keine Kopfleiste mit `kopf-umgebung` — der Bilderlauf misst die Gegenrichtung ohne Schalter mit; grün im ersten Prüfstand (745 s), Zahl im Prüfbericht des Commits |
| **Prüfstand, erster Lauf** (frische Anlage, `hochfahren.sh --neu`) | `bash tools/pruefstand/pruefen.sh` → Stufe „neben" (20.37.3 → 20.38.0), 37 Proben | **36 grün, 1 rot, 0 nicht gemessen**, 1 221 s. Rot: der Stilvergleich — 41 483 Elementmessungen, 546 Abweichungen, alle aus AP1, aber keine Liste, gegen die sie zu halten gewesen wären (F-P5c-72). Bilderlauf 745 s grün, Bedienprobe **50 von 50**, beide Kreisläufe grün. Nicht committet — der Baum änderte sich danach |
| **Stilvergleich mit Liste** | `bash tools/stilvergleich/gegen.sh` mit `geplant.txt` (30 Signaturen, geschrieben mit `--schreiben` und Zeile für Zeile gelesen: 21 aus den neuen Regeln, 9 Folgen der gewachsenen Katalogprobe — `height` an `html`/`body`, `top`/`bottom`/`inset` an absolut gesetzten Elementen) | **30 gemessen, 30 geplant, 0 ungeplant, 0 nicht gemessen** → grün. Gegenproben: eine Zeile gestrichen → rot („UNGEPLANT … kopf-umgebung : background"); eine erfundene Zeile dazu → rot („GEPLANT, ABER NICHT GEMESSEN") |
| Schnelltest im Browser (Chromium, 1440 px) | eigener Lauf, ohne und mit Etikett | Anmeldung: Streifen über der Karte, Kreuz schickt ab und kommt zurück (kein `CSRF` auf der Seite); Startseite: Kreuz schließt **ohne Neuladen** (URL gleich), nach dem Neuladen fort, **0 leere Behälter**; nach Abmelden und Anmelden wieder da; mit Etikett: Titel „[Staging] …", `kopf kopf-umgebung`, Reihenfolge Umgebung → Ankündigung; 0 Konsolenfehler |

**Vier Fehler auf dem Weg**; die ersten zwei beim ersten Lauf gemessen und behoben:
Das Skript der Ankündigung stand **in** der Reihe `.hinweise` und hielt sie
nach dem Schließen am Leben (F-P5c-65). Und die Prüfwerkzeuge klickten beim
Anmelden den ersten Absendeknopf der Seite — mit einer Ankündigung deren Kreuz
(F-P5c-64, 24 Stellen in 20 Dateien umgestellt).

**Ein dritter Fehler, gefunden vom Bilderlauf mit Etikett** (F-P5c-70):
Notfall- und Schlüsselblatt bauen ihre Hülle selbst, und ihr Titel trug den
Vorsatz nicht. Das Konzept hatte ihn auf AP9 verschoben (E-P5c-70, erste
Fassung); eine gemessene Lücke, die eine Zeile je Datei kostet, bleibt nicht
drei Pakete stehen.

**Ein vierter, gefunden vom Einrichten der frischen Anlage** (F-P5c-71):
`hochfahren.sh --neu` scheiterte in Schritt 2 mit 500, weil `install.php`
seit AP1 die Streifen zeigt und der Demo-Streifen `db.php` lud, bevor es eine
`config.php` gibt — dieselbe Art Fehler wie Nr. 288. Keiner der Läufe auf der
eingerichteten Anlage konnte ihn sehen. Behoben; `hochfahren.sh --neu` danach
rc 0.

**Und eine Lücke der Prüfkette** (F-P5c-72): Der erste Prüfstand war 36
grün, 1 rot — der Stilvergleich, weil es die Liste der geplanten
Abweichungen, gegen die `Pruefablauf.md` 6.10 eine Gestaltungsänderung
halten will, nur als Satz gab. Gebaut als `tools/stilvergleich/geplant.txt`
(E-P5c-74). **Nach dem Merge ist die Datei zu leeren** (Rahmenplan 6).

**Eine Falle des Prüfmittels, kein Fehler der Anwendung** (F-P5c-69): Wer
`config.php` schreibt und die Anlage in derselben Sekunde fragt, bekommt den
alten Stand — der OPcache prüft Zeitstempel höchstens alle zwei Sekunden. Der
Umschalter wartet deshalb drei Sekunden, bevor er misst.

## 2. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-P5c-01 | **Staging ist rot** | nach dem Merge und dem Eintrag `app.umgebung` in die `config.php` von Staging (Rahmenplan 6): Staging aufrufen, anmelden, Betrieb → Status | Reiter im Browser „[Staging] …"; Kopfleiste rot, der aktive Punkt mit hellem Strich; Streifen „Staging — Testdaten, kein Echtbetrieb" über dem Inhalt und über der Anmeldung; Statuszeile „Umgebung" blau „Staging" | Kopfleiste blau (Eintrag fehlt oder OPcache — einige Sekunden warten); Statuszeile orange „Präfix ohne Etikett" (Eintrag fehlt, Präfix steht) oder „Farbe unbekannt" (Tippfehler bei `farbe`) | offen |
| P-P5c-02 | **Produktiv bleibt blau** | nach dem Tag: Produktiv aufrufen, Betrieb → Status | Kopfleiste dunkelblau, kein Streifen, kein „[…]" im Titel; Statuszeile blau „Produktiv" | irgendein Etikett auf Produktiv — dann steht `app.umgebung` in der falschen `config.php` | offen |
| P-P5c-03 | **Eine Rundmail kommt an** | auf Staging: Servereinstellungen → Ankündigung setzen (Ende morgen) → „Als Rundmail senden …" → Rückfrage lesen → senden; danach einige Seiten aufrufen (der Job läuft huckepack) und das Postfach eines eigenen Kontos ansehen | Die Rückfrage nennt die Zahl der erreichbaren Konten; Meldung „Rundmail an N Konten eingereiht"; im Postfach eine Mail „[Staging] Ankündigung — …" mit dem Text; Betrieb → Status, Karte E-Mail, zeigt die Zeilen als zugestellt; ein zweiter Versuch am selben Tag ist gesperrt | keine Mail nach einer Stunde mit Seitenaufrufen (Warteschlange ansehen: offen? unzustellbar?); zwei Mails; ein Betreff ohne „[Staging]" | offen — **Zeit bis zur Zustellung notieren** |
| P-P5c-04 | **Die Ankündigung auf dem Handy** | auf Staging am Handy: eine zweizeilige Ankündigung setzen, Startseite und Anmeldung ansehen, das × tippen | Das × steht oben rechts und bricht nicht in eine eigene Zeile; nach dem Tippen ist der Streifen fort; nach Ab- und Anmelden wieder da | das × unten links allein in einer Zeile; oder der Streifen bleibt nach dem Tippen | offen |
| P-P5c-05 | **Bis 10c ausgeliefert ist, entsteht kein Konto mit der Rolle admin** (E-P5c-31) | Verwaltung → NutzerInnen, Filter Rolle | 0 Konten mit der Rolle admin, bis der 10c-PR gemergt und ausgeliefert ist | ein Admin-Konto — es erreicht heute Komplett-Backup und Backup-Ziele (Nr. 286, behoben mit AP2) | offen, **laufend** |

## 3. Grenzen der benutzten Prüfmittel

- **Die Mailprobe misst bis „250 angenommen".** Ob eine Mail ankommt, sagt
  nur ein Postfach (P-P5c-03).
- **Die Bedienwege laufen in Chromium, in einer Breite (1280 px).** Das ×
  am Handy (P-P5c-04) misst der Bilderlauf nur als Bild und Überlauf, nicht
  als Bedienung.
- **Der erste Bilderlauf mit Etikett lief zehnmal langsamer** (rund eine
  Minute je Seite statt 7 bis 20 s) und wurde abgebrochen; Einzelmessungen
  derselben Seiten mit und ohne Etikett und Ankündigung lagen danach bei
  12 bis 15 s, der zweite Lauf brauchte 13 min für 62 Seiten. Die Ursache
  des ersten ist **nicht geklärt** — sie lag nicht an der Ankündigung und
  nicht am Etikett (beides einzeln nachgemessen).
- **Das Wegklicken je Sitzung ist an einem Browser gemessen.** Dass zwei
  Geräte derselben Person je eine eigene Sitzung haben und die Ankündigung je
  Gerät geschlossen wird, folgt aus der Bauart (Sitzungsmarke), ist aber
  nicht nachgestellt.
