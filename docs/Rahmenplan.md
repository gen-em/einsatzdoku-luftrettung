# Rahmenplan — Programm „Gen-EM NAdoku" bis v1.0

**Fassung 43 (12.09.2026)** — Neustrukturierung (Fassung 16). Dieses Dokument steuert
das Programm: Reihenfolge, Status, programmweite Entscheidungen. Es hält
nur, was für die nächsten Schritte gebraucht wird. Alles, was bis
Fassung 15 hier stand — die Fassungsvermerke, die Phasentexte mit ihren
Umsetzungsblöcken und die 50 Programmentscheidungen im Volltext —, liegt
**wörtlich und eingefroren** in `docs/Rahmenplan-Archiv.md`. Verweise aus
älteren Dokumenten auf „Rahmenplan Abschnitt 5" oder „Fassung 13" meinen
das Archiv; sein Kopf sagt, welcher alte Abschnitt wo weiterlebt.

**Stand am 12.09.2026:** `main` trägt **Web 19.1.1**, **Uhr 3.1.0** und
**Android 0.15.0**. **Es steht nichts mehr zum Merge an** — 9a, S9 und der
Nachtrag sind alle drei gemergt und ausgeliefert: **9a am 08.09.2026**
(PR #37, `f299bbf`, Web 15.6.0 / Android 0.14.0, dazu Uhr 3.1.0 und
Android 0.15.0 aus den Punkten 159/160), **S9 am 10.09.2026** (PR #38,
`0143df3`, Web 15.7.0–19.1.0) und **Web 19.1.1 am selben Tag** (PR #39,
`015b26f`, das Schloss an Einsatznummer und Notizen-Karte). Die
Korrekturstufe **Nr. 148 und 149** (Web 15.5.2) liegt seit dem
**06.09.2026** auf `main` (PR #36).

**Was daraus für den Betrieb folgt, ist fällig und nicht erledigt:**
**`update.php`** (drei Migrationen — `rest_segments.created_at` aus 9a sowie
`2026_09_07_adresssuche_konto` und `2026_09_07_rettungsmittel_typ` aus S9),
**einmal anmelden und entsperren** (startet den Anhebelauf, der die Notizen
des Einsatzes aus der Klartextspalte in den verschlüsselten Block zieht,
Web 19.0.3) und die drei Prüflisten in `docs/konzepte/` (S9 mit 32 Punkten,
9a mit P-1 bis P-12, Korrektur 148/149 mit zweien). Alles Weitere steht in
Abschnitt 6. Seit Fassung 25 sind gemergt: **Paket E** des
S5-Zusatzes (PR #31, 03.09., Android 0.8.0–0.10.1), **Schritt 6 — S4-Rest**
Teile A bis C (PR #33, 04.09., Web 13.3.0–14.2.2, Android 0.11.0–0.13.0),
zwei vom Gerät gemeldete Uhr-Korrekturen (PR #34, 05.09., Uhr 3.0.1/3.0.2)
und **S8** (PR #35, 06.09., Web 15.0.0–15.5.1).

**Alle Migrationen sind angewendet.** `update.php` ist am 04.09.2026 um
23:15 gelaufen und hat die vier aus S4, S6 und S5 sowie
`2026_09_04_herkunft_geraet` verbucht. Die sechste,
`2026_09_05_rolle_betreiberin`, ist am 06.09.2026 **von Hand über
phpMyAdmin** ausgeführt worden — der Web-Weg war versperrt (Nr. 149): Die
Seite, die Migrationen ausführt, verlangt die Rolle, die erst diese
Migration vergibt. **Ihr Register-Vermerk fehlt noch.** Nr. 149 ist mit
Web 15.5.2 gebaut (Fassung 33): Die Migration steht auf der Seite Updates
jetzt unter *Ausstehend* mit der Plakette „nicht nötig", und der Knopf ist
da. **Sobald 15.5.2 auf `main` ist, einmal „Ausstehende ausführen" drücken**
— dann verschwindet der Zähler an Status und Menü (Abschnitt 6). Der
phpMyAdmin-Notweg und die Regel dahinter stehen jetzt im Runbook.

**Als Nächstes:** Die Korrekturstufe 148/149 ist **gemergt** (PR #36,
Web 15.5.2 auf `main`); offen ist die zweiteilige Prüfliste des
Auftraggebers in `docs/konzepte/Pruefdokument-Korrektur-148-149.md`
(„Ausstehende ausführen", Zusammenführen-Knopf). **Die S9-Umsetzung läuft**
(Opus, Claude Code, Zweig `claude/go-bwucrx`): **AP1 ist gebaut und geprüft**
(Web 15.7.0 und Korrekturstufe 15.7.1; Backlog 68, 102, 106 erledigt), **AP2
ebenso** (Web 15.8.0; Backlog 70, 101, 107, 137, 147 erledigt — mit einer
Migration, die nach dem Merge `update.php` verlangt) und **AP3** (Web 15.9.0;
Backlog 72, 103, 104, 105, 110 erledigt, dazu **Nr. 162** neu aufgenommen und
im selben Zug behoben). AP4 wartet auf die
Freigabe des Auftraggebers. **Schritt 9a läuft parallel** auf eigenem Zweig ohne Nr. 137
und 132; die beiden Zweige berühren sich in keiner Anwendungsdatei, nur in
der Buchführung (K7) — **wer zweiter mergt, zieht nach**, und die
Versionsstufen 15.7.0 bis 15.8.0 verschieben sich dann.
(„Ausstehende ausführen", Zusammenführen-Knopf). **Beschluss 07.09.2026:**
Nr. 137 und 132 sind ganz nach S9 gewandert — **die S9-Umsetzung beginnt
jetzt** (Opus, Claude Code, Auftrag liegt vor), **Schritt 9a läuft
parallel** auf eigenem Zweig ohne diese beiden Punkte; die beiden berühren
sich in keiner Anwendungsdatei mehr, nur in der Buchführung (K7).

**Stand 07.09.2026 abends:** **Schritt 9a ist gebaut und geprüft** — Web
15.6.0 (elf Punkte in vierzehn Commits, drei davon Nachbesserungen) und Android 0.14.0 (Nr. 142–145 und der
Räumteil von 114, fünf Commits), Zweig `claude/sofortpaket-sicherheit-1t70p1`
gepusht. **Danach die adversarische Gegenprüfung des Web-Teils** (93
Agenten, 22 von 29 Funden hielten) und ihre Nachbesserungen, je Punkt ein
Commit (Nr. 134, 130, 136 zweimal, 140 zweimal, Dokumentation), dazu eine
zweite Gegenprüfung auf die Nachbesserungen selbst; Backlog 154–161 neu.
**Der Merge braucht jetzt `update.php`** (Migration
`rest_segments.created_at`). **Android 0.14.1** (07.09.2026, Auftrag zu
E-S9-03/Nr. 110): fünf Texte des Handy-Moduls sagen „GPS-Daten" statt
„Spur"; die befristete Wortlisten-Ausnahme `spur-android-wartet-auf-9a`
liegt auf dem S9-Zweig und ist beim Merge zu streichen (K7). **Gemergt am
08.09.2026** (PR #37, `f299bbf`); S9 folgte zwei Tage später.
Das Prüfdokument liegt als
`docs/konzepte/Pruefdokument-Sofortpaket-Sicherheit.md`; seine Prüfliste
P-1 bis P-12 ist die des Auftraggebers.

**Die Planung v1.0 (Schritt 11) ist vorgezogen und entschieden** — R65 bis
R73, Konzept `docs/konzepte/Konzept-Planung-v1.0.md`; der v1.0-Schnitt ist
in drei Phasen geteilt (P6, P7, P8, R71), die Problemsammlung vom
03.09.2026 ist Schritt 8 (S9, R73). Was daraus sofort am Auftraggeber
liegt, steht in Abschnitt 6 — zuvorderst die **D-U-N-S-Nummer** für das
Play-Console-Organisationskonto (R65).

> **Berichtigt mit Fassung 32.** Der Kopf nannte bis Fassung 31 „Web 13.2.0
> auf `main`", einen ausstehenden Push für Schritt 6, ein ungemergtes
> Paket E und vier wartende Migrationen — alles seit dem 04.09.2026
> überholt, während Abschnitt 10 die Merges längst führte. Dieselbe Sorte
> Fehler wie die, die Fassung 24 berichtigt hatte: Der Kopf wird beim
> Abschluss einer Phase nicht mitgelesen. Wer eine Erledigt-Zeile
> schreibt, liest ab jetzt den Kopf gegen (Abschnitt 9).

**So wird gelesen:** Abschnitt 3 sagt, was als Nächstes dran ist und in
welcher Reihenfolge. Abschnitt 5 sagt, wohin jeder offene Backlog-Punkt
gehört. Abschnitt 6 sagt, was vom Auftraggeber gebraucht wird. Abschnitt 8
sagt, was schon gebaut ist und was es gebracht hat. Die Kennungen (P5, S6,
…) sind Namen, keine Reihenfolge — die Reihenfolge steht in Abschnitt 3.

---

## 1. Ziel und Nicht-Ziele

Die bestehende Einsatzdokumentation wird von einem Luftrettungs-Werkzeug
mit bodengebundener Erweiterung zu **Gen-EM NAdoku v1.0**: ein
Notarzt-Dokumentationswerkzeug für Land und Luft gleichrangig, mit neuem
Namen, neuer Oberfläche, Mehrbenutzerfähigkeit und einem frischen
Repositorium `gen-em/nadoku` (R68). Betrieben wird es als **offener Dienst mit
Selbstregistrierung** bis 1 000 Konten (R36), mit einem zweiten Client
neben der Garmin-Uhr: Android-Handy und Wear-OS-Uhr (R45).

Feste Zusagen, die keine Phase aufweicht (Einzelheiten `CLAUDE.md` 4):
Ende-zu-Ende-Verschlüsselung der geschützten Felder · keine fremde Quelle
zur Laufzeit · **keine Telemetrie** — Betriebszahlen nur aus vorhandenen
Spalten, einzige Ausnahme die Gerätekennung beim Koppeln (R36, R42) · das
Demo-Konto als einzige benannte E2E-Ausnahme (R25).

**Nicht Ziel:** iOS und watchOS (R46) · **Produktionsfreigabe** der Clients
in den Stores vor v1.0 (Betriebsübergang, R41, R65 — der interne
Play-Test-Track ab Schritt 6 ist Ziel, nicht Nicht-Ziel) · ein Migrationspfad für Bestandsinstallationen
(R11: v1.0 liest die 7.x-Sicherung genau einmal, mehr nicht) ·
Rückwärtskompatibilität ab v1.0, auch bei Updates (R60).

## 2. Regeln der Zusammenarbeit

### 2.1 Konventionen K1–K9

- **K1** Je Phase ein Konzeptdokument im bewährten Format: Befund,
  Entscheidungen (E-Nummern), offene Fragen (F-Nummern), Arbeitspakete mit
  Abnahmekriterien, Prüfprotokoll, gesammelte Fehlerfunde. Es ist die
  Übergabeeinheit an die umsetzende Instanz. **Ablage: `docs/konzepte/`**,
  Mockups in einem Unterordner daneben; Lebenszyklus in 2.2 (R62).
  **Ein Konzept benennt für jede neue Funktion ihren Ort** nach dem
  Ordnungsprinzip (R74); ohne benannten Ort kein Merge.
- **K2** Konzepte nennen keine Modellempfehlung je Arbeitspaket.
  **Standardmodell der Umsetzung ist Opus.** Schritte, die Fable erfordern,
  sind im Konzept ausdrücklich als Fable-Schritt markiert.
- **K3** Konzepte legen keine Versionsnummern fest; das tut die Umsetzung.
- **K4** Fehlerfunde während einer Phase werden gesammelt, nicht sofort
  behoben — außer der Fund blockiert die laufende Arbeit.
- **K5** Jede Phase endet mit lauffähigem Stand, fortgeschriebenem
  Konzept, fortgeschriebenem Prüfprotokoll und dem Statuseintrag in
  diesem Dokument (Abschnitt 3 während der Arbeit, Abschnitt 8 danach).
  **Nach jedem Arbeitspaket** sagt ein Statusblock am Kopf des Konzepts,
  welches Paket in Arbeit ist, welche erledigt sind und wo es hakt (R62).
- **K6** F-Fragen werden vor Umsetzungsbeginn des betroffenen Pakets
  entschieden und als E-Eintrag ins Konzept überführt.
- **K7** Je Arbeitspaket ein Commit (deutsche Nachricht), **und der
  Arbeitszweig wird nach jedem Arbeitspaket gepusht**, damit andere
  Instanzen Stand und laufendes Paket sehen — das deployt nichts, solange
  es nicht `main` ist. **Auf `main` kommt eine Phase einmal, am Ende, nach
  ausdrücklicher Bestätigung**; dieser Push deployt sofort auf den
  Produktivserver.
- **K8** Nur vor einem **Fable-Schritt** pausiert die umsetzende Instanz
  und weist darauf hin; alles Übrige läuft ohne Modellnachfrage mit Opus.
- **K9** Jede Phase liefert ein **Prüfdokument**, getrennt vom Konzept:
  Kurzfassung, maschinelle Prüfungen mit Zahlen, das Nicht-Prüfbare an
  erster Stelle, und eine abhakbare Prüfliste, in der jeder Punkt
  Bedienweg, erwartetes Ergebnis und die Bedeutung eines Fehlschlags nennt
  (Muster: `docs/konzepte/erledigt/Pruefdokument-S3-Oberflaechen-Nacharbeit.md`).

### 2.2 Dauerpflichten, die aus Entscheidungen erwachsen sind

- **Regressionspflicht (R24):** vor jedem Phasenabschluss beide Kreisläufe
  (`tools/referenzdatensatz/vergleich/kreislauf.py`, `csv` und `edbak`),
  Sollstand **0** unerklärte Abweichungen; Zahlen ins Prüfdokument.
- **Prüfmittel laufen mit** — Wiederherstellungsprobe und Papierkorb-Mischfall
  (R27) bei jeder Berührung von Papierkorb, Rückspielweg oder
  Diensttag-Zuordnung; **Wortliste** `tools/wortliste/` (R28) bei jeder
  sichtbaren Text- oder Doku-Änderung, Soll 0/0/0; **Messstand**
  `tools/messstand/` (R35) bei Spurspeicherung, Sicherungsformat, Suche und
  anderen Mengenpfaden; Vollständigkeit und Bilderlauf nach `CLAUDE.md` 6.
- **Prüfmittel laufen zuletzt**, nach der letzten Änderung, und jede grüne
  Zahl benennt, was sie gemessen hat (`CLAUDE.md` 6).
- **Modell (R14):** Konzepte mit Fable, hohe Denktiefe; mechanische Pflege
  ohne. Umsetzung nach K2/K8.
- **Deploy (R40):** bis einschließlich S4 Autodeploy auf Produktiv; **mit
  P5-Beginn** deployt `main` nur noch auf Staging; am P8-Schnitt einmaliges
  Neuaufsetzen mit Datenübernahme per edbak (R11).
- **Backlog-Nummern sind dauerhaft.** Vor jedem Merge, der Backlog-Punkte
  mitbringt: `grep -oE '^[0-9]+\.' docs/Backlog.md | tr -d '.' | sort -n |
  uniq -d` muss leer sein. Der Backlog-Kopf sagt, welche Nummern ein
  laufender Zweig reserviert hat.
- **Migrationsregister beim Merge:** `server/migration_lib.php`
  (`migrationen_katalog()`) und `schema.sql` tragen je eine Liste der
  Migrationskennungen; beim Zusammenführen **beide** Seiten behalten und
  gegenzählen (der Lauf schluckt doppelte Anlagen still; der Schaden trifft
  erst die nächste Neuinstallation). *Berichtigt mit Fassung 33:* Bis dahin
  stand hier `update.php` als zweite Liste — seit Web 15.1.0 (S8/AP2) liegt
  der Katalog in `migration_lib.php`, und `update.php` ruft ihn nur noch
  auf. Wer die Regel wörtlich befolgte, zählte gegen eine Liste, die es
  nicht mehr gibt.
- **Pflegepflichten** je Änderung nach `CLAUDE.md` 2 und 9 (Version,
  Changelog, Doku, Backlog, Design, Lizenzen).
- **Lebenszyklus eines Konzepts (R62).** Es entsteht in `docs/konzepte/`
  (Prüfdokument daneben, Mockups in einem Unterordner). Während der
  Umsetzung wird es nach **jedem Arbeitspaket** fortgeschrieben — Statusblock
  am Kopf mit laufendem Paket, erledigten Paketen und Stand — und der Zweig
  gepusht (K7). Nach der **Freigabe des Abschlusses** trägt die umsetzende
  Instanz die Erledigt-Zeile in Abschnitt 8 ein (Versionen, Datum,
  wesentliche Änderungen, Prüfzahlen, **letzter Commit des Konzepts**), die
  Reste nach Abschnitt 6, den Backlog nach Abschnitt 5, eine Zeile nach
  Abschnitt 10 — und **löscht das Konzept**. Die Git-Historie behält es; die
  Erledigt-Zeile nennt den Commit, unter dem es zuletzt lag. Das
  **Prüfdokument bleibt, bis seine Prüfliste abgehakt ist**, und wird dann
  ebenso gelöscht; offene Reste wandern vorher nach Abschnitt 6. Der
  Bestand bis S3 liegt als Protokoll in `docs/konzepte/erledigt/` und wird
  nicht mehr fortgeschrieben.

## 3. Fahrplan — die nächsten Schritte

| Schritt | Kennung | Inhalt | Voraussetzung | Konzept | Modell | Status |
|---|---|---|---|---|---|---|
| 1 | **S4 — Merge** | Fehlerbehebung abschließen, Backlog-Nummern nachziehen, `main` holen, Merge = Deploy, `update.php` | — | liegt vor | Opus | **gemergt** (Web 12.8.0, Android 0.7.7 auf `main`); `update.php` für `2026_09_02_schnitte` vom Auftraggeber zu bestätigen; Prüfliste S4 (1, 2, 3, 5) offen |
| 2 | **S6 — Gerätekennung und Schlüsselfrist** | Serverseite von R42, Behebung R44 | Schritt 1 | keins; R42 und R44 sind die Spezifikation | Opus | **gemergt und ausgeliefert** (Web 12.9.0 bis 12.9.2 auf `main`); `update.php` für `2026_09_02_geraetekennung` und `…_geraetemodell_breiter` zu bestätigen; Abnahme nach Abschnitt 6 offen |
| 3 | **S5 — Kopplung umgekehrt, Konzept** | E-R49-1 bis E-R49-8 ausarbeiten | Schritt 2 | neu | **Fable** (R14) | erledigt 03.09.2026 — Freigabe mit E-S5-32 bis -47 |
| 4 | **S7 — Backup-Begriff** | Umstellung in einem Zug | Schritt 1; parallel zu 3 | gelöscht nach R62 | Opus | **erledigt, Web 12.9.3/12.9.4** (Abschnitt 8); PR gegen `main` offen — der Merge deployt; Prüfliste S7 offen |
| 5 | **S5 — Umsetzung** | Server, Web, Uhr, Doku | Schritt 3; DNS `nadoku.gen-em.org` | aus Schritt 3 | Opus | **erledigt und gemergt** (Web 13.0.0–13.2.0, Uhr 3.0.0, PR #28/#29; **Paket E** Android 0.8.0–0.10.1, PR #31); Prüfliste S5 und Freigabe des Abschlusses offen (Abschnitt 6) |
| 6 | **S4 — Rest** | Kopplungsmodul, feste Server-Adresse, App-Name, Insets, Herkunft je Einsatz (R64), Gerätetest, **Play Console nach R65** (interner Test-Track für Handy und Uhr, Versionscode-Versatz, Signaturweg), Android 1.0.0 | Schritt 5 | Konzept S4, Abschnitt 13 | Opus | **gemergt** (PR #33, 04.09.2026; Web 13.3.0–14.2.2, Android 0.11.0–0.13.0): Teil A, Teil B (R64 und Nr. 63) und Teil C (Play-Console-Vorbereitung) sind auf `main`, `update.php` ist gelaufen. **Offen:** Gerätetest am S24 und mit der Wear-OS-Uhr (Backlog 81, Abschnitt 6), Android 1.0.0 nach E-R45-7, D-U-N-S und Signaturschlüssel für die Play Console |
| 7 | **S8 — Einstellungen, Administration und Wartung** | Sichtung und Neuordnung: Backup-Optionen, Menüstruktur, Aufteilung der Wartungsseite, Einzelpunkte 73–79 (R61); dazu die Rolle **BetreiberIn** (R75), der Block **Betrieb** mit sieben Seiten und das **Ordnungsprinzip** (R74) | Schritte 4 und 6 | **liegt vor** (Fable, 05.09.2026), `docs/konzepte/Konzept-S8-Einstellungen-Administration-Wartung.md`, zwölf Mockups | Fable (Konzept), Opus (Umsetzung) | **erledigt, Web 15.0.0 bis 15.5.1** (Abschnitt 8) — acht Arbeitspakete, Konzept nach R62 gelöscht, Prüfdokument bleibt |
| 8 | **S9 — Einsatzbearbeitung und Rettungsmittel** | Problemsammlung vom 03.09.2026 (Nr. 101–113): Adresssuche und gemeinsamer Kartendialog, Rettungsmittel-Übernahme, kompaktere Kartenschilder, Windenkacheln, Artzeichen, Vorschlagsliste, Transportziel ad hoc, Schloss-Kennzeichnung, Notizfeld verschlüsselt, „GPS-Daten", neue Rettungsmittel-Typen, Tageszuordnung, Rollen; dazu **Nr. 147** (Spur im Kartendialog), **Nr. 44, 68, 69, 70, 72** (06.09.2026) und **Nr. 152** (PS-12 Standortseiten, 07.09.2026) | Schritt 7 **erfüllt**; F3–F6 **beantwortet 06.09.2026**; **keine weitere** — Nr. 137/132 sind in S9 (07.09.2026) | **Konzept liegt vor und ist freigegeben** (Fable, 06./07.09.2026): `docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md`, E-S9-01 bis -19, sieben Mockups in `konzept-s9/mockups/` | Umsetzung Opus, acht Arbeitspakete, kein Fable-Schritt | **Gebaut und geprüft** (Zweig `claude/go-bwucrx`): **AP1 bis AP8 erledigt** (AP4a und AP5b eingeschlossen) 07.–10.09.2026, Web 15.7.0 bis **19.1.1**, Migrationen `2026_09_07_adresssuche_konto` und `2026_09_07_rettungsmittel_typ`. **Am 10.09.2026 auf `main` gemergt** (PR #38, `0143df3`), der Nachtrag Web 19.1.1 am selben Tag (PR #39, `015b26f`). **Fällig ist jetzt `update.php`** (die beiden Migrationen aus AP2 und AP4 — AP7 braucht keine) und die 32-Punkte-Prüfliste. Siehe Abschnitt 8 |
| 9 | **Backlog-Runde** | Einzelpunkte nach Abschnitt 5 | ab Schritt 1, parallel | keins | Opus | offen — die **Korrekturstufe mit Nr. 148 und 149** ist gebaut, geprüft und **am 06.09.2026 auf `main` gemergt** (Web 15.5.2, PR #36). Offen ist allein die **zweiteilige Prüfliste** in `docs/konzepte/Pruefdokument-Korrektur-148-149.md`; sie ist seit dem Merge **fällig**. Die übrigen Einzelpunkte aus Abschnitt 5 sind unangetastet |
| 9a | **Sofortpaket Sicherheit** (R78) | Web: Nr. 127–131, 133–136, 138 (Rundenzahl 600 000, Login-CSRF, E-Mail-Nachweis, Ordner, GPX, `wiederherstellen.php`, Bauordner, Ersetzfenster der Uhr, Maskierung, Weg C, Integritätswache aus Nr. 140) — **ohne 132 und 137, die sind seit dem 07.09.2026 in S9**; Android: Nr. 142–145 und der Räumteil von 114 | Web sofort; Android nach Schritt 6 auf `main` | keins; `docs/konzepte/Vorbereitung-Sicherheitspaket.md` ist die Spezifikation (Muster R42, Prüfdokument mit Zahlen) | Opus | **gebaut, geprüft und gegengeprüft (Web 15.6.0 und Android 0.14.0, 07.09.2026)** — Nachbesserungen nach der adversarischen Gegenprüfung committet, Zweig gepusht; **am 08.09.2026 auf `main` gemergt** (PR #37, Merge-Commit `f299bbf`), **danach `update.php`** |
| 9b | **S10 — Sicherheit** (R78) | Server-Anteil am Datenschlüssel mit Schlüsselblatt, Kennung und Rotation (SP-3); Adminpakete versiegeln, `ftp` abschaffen (Nr. 139) | Schritt 9a; **vor P5** (Hauptstufe, Umstellung aller Hüllen) | neu, nach K1 aus der Vorbereitung | Fable (Konzept), Opus | offen |
| 10 | **P5 — Dienstbetrieb** | Registrierung, Rollen, Administration, Betrieb; Zweitfaktor für alle Konten (Nr. 141) und CSP nach Bauplan SP-5 (Nr. 8) | Schritte 2, 5, 7 und 9b; Hosting-Entscheidung; Staging | neu | Fable (Konzept) | offen |
| 11 | **Planung v1.0** | Festlegungen vor dem Schnitt: Store-Verteilung (R65), Update-Weg (R66), Auslieferungskette (R67), Repositorium (R68), Code-Review (R69), Web-App auf Android (R70), Phasenschnitt (R71), Doku-Anforderungen (R72), Problemsammlung (R73); Ergebnis sind die Konzepte der Phasen P6–P8 mit je eigenem Paketschnitt | Festlegungen: keine (vorgezogen); Paketschnitte: die jeweilige Vorphase, P6 nach der Freigaberunde des Reviews | `docs/konzepte/Konzept-Planung-v1.0.md` | Fable (R14) | **Festlegungen entschieden** 03.09.2026 (R65–R73); offen nur die Paketschnitte je Phasenkonzept |
| 12 | **P6 — Review und Bereinigung** | Bedrohungsmodell (Eingang: `Review-Krypto-Sicherheit.md`, R78); Bug- und Sicherheitsreview in zwölf Stücken (R17, R69); Freigaberunde; Sofortpaket; Pflicht- und Aufräumpakete; Kommentardurchgang (R13, R31); Fragen Nr. 146; R5-Ausnahmeliste | Schritte 8 und 10; Nr. 43-Fragen beantwortet (R78) | `docs/konzepte/Review-R17.md`, Paketschnitt nach der Freigaberunde | Fable (Review, Kryptographie), sonst Opus | offen |
| 12a | **S11 — Ortsdaten verschlüsselt (Weg B)** (R78) | Konto-Schlüsselpaar (Nr. 53); Uhr und Handy verschlüsseln Spur, Phasenkoordinaten, Reanimationsereignisse und Zielklinik vor dem Upload; serverseitige Spurfunktionen wandern in den Browser; Altbestand per Einmalwerkzeug (Nr. 43) | Schritt 12; **vor der Öffnung** — die Entscheidung zum Altbestand setzt ein einziges Konto voraus | neu, nach K1 (Skizze SP-9 in `Vorbereitung-Sicherheitspaket.md`) | Fable (Konzept), Opus | offen |
| 13 | **P7 — Gesicht v1.0** | Umbenennung überall, neues Demo-Passwort (R25); Vertrag v1 (R12, Nr. 23); Doku-Neufassung (R16, R72); Web-App-Manifest (Nr. 87, R70); Changelog neu (R15); Backlog-Übernahme; Altformat der Sicherung abschaffen (Nr. 46); Kommentarregel `CLAUDE.md` (R69) | Schritt 12 | eigenes Konzept nach K1 | Opus | offen |
| 14 | **P8 — Schnitt** | Neuaufsetzen (R40 (3)); Migrationsregister neu (R66); Repo-Umzug und Inventur (R68); Kette im neuen Repositorium (R67, R40 (4)); Rechts- und Betreiberunterlagen (R41); Abnahme nach R11; Erklärung v1.0 | Schritt 13 | eigenes Konzept nach K1 | Opus | offen |
| — | Betriebsübergang | Öffnung in Wellen; Produktionsfreigabe in den Stores (R65) | nach v1.0 | — | — | — |

Reihenfolge und Begründung: S4 zuerst, weil der Zweig fertig gebaut ist
und `schema.sql`/`update.php` hält, die S6 anfassen muss. S6 vor S5, weil
E-R49-1 die Gerätespalten voraussetzt und Vertragsabschnitt 1a dort
geschrieben wird. S7 in das Fenster, in dem das S5-Konzept entsteht (dort
entsteht kein Code). Der S4-Rest nach S5, weil sein Kopplungsmodul nach
dem alten Modell gebaut ist und das neue Protokoll braucht. P5 nach S5,
weil die App den Dienstbetrieb nicht braucht, ihre öffentliche Verteilung
schon (R37.10, R19). S8 vor P5, weil P5 in genau diesen Seiten weitere
Optionen anlegt und die Ordnung vorher stehen soll — dasselbe Argument, mit
dem S3 vor P5 lag.

### Schritt 1 — S4 Merge

**Ziel:** Der S4-Zweig kommt auf `main`, ohne die Nummerierung des
Backlogs zu brechen. **Inhalt:** letzte Fehlerbehebung · Backlog-Nummern
des Zweigs von 59–63 auf **63–67** und die alten Verweise 46/49 auf 59/62
· `main` in den Zweig holen, `docs/Backlog.md` mit beiden Reihen lösen,
Migrationsregister gegenzählen · R58 (48 dp, eine Zeile) · Merge, Deploy,
danach **`update.php`** (Migration `2026_09_02_schnitte`) · Konzept und
Prüfdokument S4 beim Merge nach `docs/konzepte/` verschieben (R62). **Was der
Zweig bereits enthält:** Schneidewerkzeug (Web 12.5.0/12.6.0), GPX-Import
(12.7.0), APK-Weg und Downloadseite (12.8.0), Handy-App und Wear-OS-App
(Android 0.1.0–0.7.7: Kopplung nach altem Modell, Aufzeichnung, Senden,
Phasen, Uhr-Bedienbild, Nachrichtenweg mit Quittung, Emulator-Prüfung,
Bedienhöhe 48 dp nach R58), Doku und Lizenzen. **Am 02.09.2026 dazu
gekommen:** Backlog-Umnummerierung auf 63–67 mit neun nachgezogenen
Verweisen, R57 als E-S4-76 und R58 als E-S4-77 im Konzept, Konzept und
Prüfdokument nach `docs/konzepte/` verschoben (R62) samt Statusblock am
Kopf, `main` geholt und drei Konflikte gelöst (Backlog, Changelog, Konzept),
Migrationsregister gegengezählt (38 = 38). **Und ein Fund, der nicht warten
konnte:** Der Signaturschlüssel war seit B1 erzeugt, aber nie übergeben — er
lag allein im Ablagefach der Arbeitssitzung; übergeben am 02.09.2026. **Abnahme:** Prüfdokument S4, Prüflisten 1 bis 3 und 5
(Schneiden am echten Diensttag, Sperrvermerk, fremde GPX, APK-Ablage nach
dem Deploy). **Was in Schritt 6 wartet:** Kopplungsmodul, Adress-QR,
Signaturschlüssel, Gerätetest, Backlog 63.

### Schritt 2 — S6 Gerätekennung und Schlüsselfrist

**Ziel:** Der Server nimmt an, was die Geräte seit einem Jahr senden, und der
Entsperrdialog erscheint nicht mehr mitten in der Arbeit. **Kein Konzept, kein
Prüfdokument** (Muster R20) — dieser Block ist die Spezifikation und zugleich
das Protokoll.

**Gebaut am 02.09.2026, Web 12.9.0** (Zweig `claude/s6-rahmenplan-umsetzung`):

- **Drei Spalten an `devices`** — `geraet_art`, `geraet_modell`, `geraet_teil`;
  Migration `2026_09_02_geraetekennung`, Register gegengezählt (39 = 39).
- **`pair.php` liest den Block** über die neue `geraete_lib.php`: die Uhr-Form
  (Teilenummer, Auflösung auf dem Server) **und** die Handy-Form nach E-S4-28.
  Eine Kopplung scheitert nie an einer Statistikangabe.
- **Auflösung Teilenummer → Modell** in der erzeugten `geraetemodelle.php`,
  Erzeuger `tools/geraetemodelle/` — samt `nachaufloesen.php`, das bestehende
  Zeilen nachträglich auflöst, wenn die Tabelle später wächst (E-S6-6).
- **Anzeige** in beiden Gerätelisten (Einstellungen und Adminbereich), in der
  vorhandenen Kleinzeile — kein neuer Baustein.
- **R44 angeglichen:** `keyguard.js` erneuert den Zeitstempel beim Treffer im
  Zwischenspeicher; damit messen Sitzung und Schlüssel beide Inaktivität.
  **Nicht mehr, als das ist** — siehe E-S6-4.
- **Nachträge:** JSON-Vertrag (Fassung 1.4), `Technik.md` (Datenmodell,
  Verzeichnisstruktur, Kopplung, Abschnitt 5a, Bausteine, Entsperren,
  Runbook), `Handbuch.md` (Geräteliste, „ein Tab, ein Schlüssel"),
  `Geraete-Eingabe.md`, `Lizenzen.md` 7a, `android/LIESMICH.md`.
- **Prüfmittel:** `tools/geraeteprobe/` (neu, 39 Erwartungen), `tools/fristprobe/`
  (neu, der fehlende Beleg zu R44) und `tools/geraetemodelle/` (neu).

**Drei Entscheidungen sind dabei gefallen, alle als Abweichung von R42 zu
lesen und deshalb hier festgehalten:**

- **E-S6-1 — drei Spalten statt zwei.** R42 nennt Art und Modell. Die dritte
  hält die **Rohangabe** des Geräts. Grund: Der Modellname entsteht aus einer
  erzeugten Tabelle, und die kennt nur, was es beim Erzeugen gab. Ein künftiges
  Garmin-Gerät fiele sonst dauerhaft und **unwiederbringlich** auf „unbekannt",
  weil die Teilenummer nirgends mehr stünde.
- **E-S6-2 — keine weiteren Felder.** Backlog Nr. 59 nannte zusätzlich
  Displaymaße, Firmware, Plattform- und App-Fassung. Sie kommen an und werden
  verworfen: R36 lässt die Gerätekennung als die eine benannte Ausnahme zu, und
  die Ausnahme ist „welches Gerät", nicht „in welchem Zustand".
- **E-S6-3 — der Vorgabename folgt der Art.** Beim Koppeln stand der Name fest
  auf „Uhr"; seit der Handy-App war das falsch. Ein Fehler aus S4, hier
  mitgenommen, weil S6 die Geräteart überhaupt erst kennt.
- **E-S6-4 — R44 wird als Aufräumen ausgeliefert, nicht als Behebung des
  Dialogs.** Der R44-Eintrag schreibt dem Fristablauf den Entsperrdialog zu.
  Das trifft nicht zu, und das Archiv hat es am 01.09.2026 bereits berichtigt;
  bei der Umsetzung ist es am Code nachgelesen worden: `verwerfeInhalt()` lässt
  `edk` liegen, `getContentKey()` entpackt ohne Passwort neu. Der Ablauf
  kostete ein **stilles Neu-Entpacken**. Changelog, Handbuch, `Technik.md` und
  der Dateikopf sagen das jetzt so — die erste Fassung dieses Pakets hatte den
  Irrtum wortreich weitergeschrieben.
- **E-S6-6 — das Nachauflösen wird gebaut, nicht nur versprochen.** Die
  Begründung für die dritte Spalte (E-S6-1) trägt nur, wenn es ein Programm
  gibt, das sie später auswertet — `pair.php` löst ausschließlich im Moment
  der Kopplung auf. Das wiegt schwerer als der fehlende Modellname: Bis dahin
  steht in `geraet_art` die **ungeprüfte Selbstauskunft** des Geräts, und die
  Garmin-App sendet dort fest „uhr“. `tools/geraetemodelle/nachaufloesen.php`
  räumt beides nach; die Rohangabe selbst rührt es nie an. Grenze: Es braucht
  Shell-Zugriff.
- **E-S6-5 — die Gerätekennung wird in beiden Listen gekürzt.** Die volle
  36-Zeichen-Kennung hat keine Umbruchstelle und drückte als Plakette den Text
  daneben auf ein Wort je Zeile zusammen — bei jedem frisch gekoppelten Gerät,
  dessen Bezeichnung kurz ist. Im Bilderlauf gesehen, **auch am Stand vor S6**
  (samt +1 px Überlauf bei 360); die längere Kleinzeile hat es nur sichtbar
  gemacht. Die Kürzung des Adminbereichs (8 + … + 2) gilt jetzt für beide
  Listen und steht an einer Stelle.

**Nachgetragen am 02.09.2026 (Web 12.9.1):** Die Zuarbeit ist geliefert, die
Modelltabelle trägt **325 Teilenummern auf 173 Modelle** — dieselbe Zahl, die
der JSON-Vertrag seit der Uhr-Seite nennt, und damit unabhängig bestätigt. 28
der 173 sind keine Uhren (20 Edge, 8 Outdoor-Handgeräte); der Vorrang der
Tabelle vor der Selbstauskunft ist damit kein Randfall, sondern betrifft ein
Sechstel des Katalogs.

- **E-S6-7 — `geraet_modell` geht auf 191 Zeichen.** Die 64 waren geraten, als
  die Gerätedateien noch nicht vorlagen. Sie führen je Teilenummer die
  **Hardware**, und Garmin verkauft dieselbe Hardware unter mehreren Namen: Der
  längste Eintrag hat 156 Zeichen, fünf der 173 Modelle liegen über 64.
  Gespeichert wird der volle Name (die Zählung in P5 soll Hardwaregruppen
  zählen), gekürzt wird erst für die Anzeige. **Zweite Migration statt
  Änderung der ersten:** Die erste ist gepusht, und `update.php` führt jede
  Kennung genau einmal aus — eine Installation, die sie schon gefahren hätte,
  sähe eine Änderung an ihrem Rumpf nie.

**Abnahme:** eine Kopplung je Gerätetyp zeigt Art und Modell in der
Geräteliste; ein Leerlauf über 30 Minuten führt zur Abmeldung. **Der
Dialog-Teil der ursprünglichen Abnahme entfällt** (E-S6-4): Er ist vor und nach
der Änderung grün und belegt nichts. An seine Stelle tritt `tools/fristprobe/`
— acht Stunden Dienst durchgespielt, vorher 17 Neu-Entpackungen, nachher 1. **Nach dem Deploy muss eine
Administratorin `update.php` aufrufen.** **Backlog:** 59 erledigt, Rest als 80.

### Schritt 3 — S5 Konzept

**Ziel:** Das Konzept nach K1 für den umgekehrten Kopplungsweg (das Gerät
zeigt den Code, das Web nimmt ihn entgegen, das Gerät bestätigt das Konto).
Die acht Beschlüsse E-R49-1 bis E-R49-8 sind gefallen; offen sind Zahlen
des Ratenschutzes und der Sitzungsobergrenze, Abfragetakt der Uhr, Wortlaute
der Uhr-Anzeigen und der Geräteseite, Paketschnitt mit Abnahmekriterien,
Vertragsabschnitt 1a im Wortlaut (1b „trennen" bleibt). **Vier Blöcke:**
Server (Sitzungstabelle statt `pair_codes`, vier Anliegen `start`/`status`/
`bestaetigen`/`trennen` — das vierte übernommen und auf schwebende
Zugangsdaten erweitert, Ratenschutz je Konto und IP, Aufräumen über den
Job-Einstieg)
· Web (Feld „Code vom Gerät", Bestätigungsseite mit Art und Modell,
„Kopplungscode erzeugen" entfällt, manuelle Anlage bleibt) · Uhr
(Code-Anzeige, Rückbestätigung mit maskierter E-Mail, Vorgabeadresse
`nadoku.gen-em.org`, Uhr-Build mit den S3-Kacheln) · Doku. Für die
Android-App gilt R63: feste Adresse ohne Adresswahl; die Garmin-Uhr behält
Vorgabewert und Einstellung. **Abnahme des
Konzepts:** Freigabe durch den Auftraggeber. In die S5-Abnahme geht
**P2-Prüfpunkt 4.1** auf (eine Kopplung mit der Uhr in der Hand, R55).

### Schritt 4 — S7 Backup-Begriff · **erledigt**

Gebaut und geprüft am 02./03.09.2026, Web 12.9.3 und 12.9.4. Was es
gebracht hat, steht in Abschnitt 8; was noch am Auftraggeber liegt, in
Abschnitt 6 und im Prüfdokument
`docs/konzepte/Pruefdokument-S7-Backup-Begriff.md`. Das Konzept ist nach
R62 gelöscht; die Git-Historie behält es.

### Schritt 5 — S5 Umsetzung · **erledigt (Pakete A–E und W)**

Gebaut und geprüft am 03.09.2026 — **Web 13.0.0 bis 13.2.0** und **Uhr
3.0.0**, gemergt als PR #28 und #29. Was es gebracht hat, steht in
Abschnitt 8; was noch am Auftraggeber liegt, in Abschnitt 6 und im
Prüfdokument `docs/konzepte/Pruefdokument-S5-Kopplung-umgekehrt.md`
(zwölf Punkte).

**Die Migration `2026_09_03_kopplungssitzungen` ist am 04.09.2026
gelaufen** (mit `update.php`, zusammen mit den übrigen aus S4 und S6) —
Punkt 1 der Prüfliste ist damit erledigt; die Uhr koppelt wieder.

**Paket E** (Android-Ortung und Dienstende, eigenes Zusatzkonzept) ist am
03.09.2026 gemergt (PR #31, Android 0.8.0–0.10.1); es hing an keinem der
übrigen Pakete. Was es gebracht hat, steht in Abschnitt 8.

**Die drei Konzepte stehen noch** (`Konzept-S5-Kopplung-umgekehrt.md`,
`…-Zusatz-Wartungsmodus.md`, `…-Zusatz-Android-Ortung-Dienstende.md`). Nach
R62 werden sie mit der Freigabe des Abschlusses gelöscht — die steht aus,
und das Zusatzkonzept zu E gehört ohnehin einem noch offenen Paket.

### Schritt 6 — S4 Rest

**Ziel:** Die Android-App wird benutzbar ausgeliefert (1.0.0).
**Inhalt:** Kopplungsmodul der Handy-App auf Vertragsabschnitt 1a neu
schneiden (Konzept S4, Abschnitt 13: sechs Quelldateien, rund 600 Zeilen,
39 von 220 Prüffällen) · **feste Server-Adresse `nadoku.gen-em.org`** in der
Android-App, Adressfeld und Adress-QR entfallen (R63, Nr. 84) · App-Name
„Gen-EM NAdoku" am Handy, Uhr bleibt „NAdoku" (Nr. 85) · Fenster-Insets
gegen die Statusleisten-Überlappung (Nr. 86) · **Herkunft und Gerät je
Einsatz nach R64** (Nr. 83): Momentaufnahme `geraet_art`/`geraet_modell` an
`missions` und `rest_segments`, `origin` um `android`, `wear`, `schnitt`
erweitert, Migration füllt den Bestand aus `devices` nach, Feldkatalog,
Export- und Backup-Format und Kreisläufe (R24) ziehen zusammen mit Nr. 63
nach — eine Formatänderung, ein Kreislauf · Hinweis in der Tagesansicht bei zeitlich überlappenden
aktiven Diensttagen samt Handbuchabsatz (R57) · Backlog 63 (Sperrvermerke
des Schnitts in die Konto-Sicherung) · Signaturschlüssel erzeugen und
übergeben, erstes signiertes APK · Gerätetest auf dem S24 (zwei bis drei
Runden) · **Backlog 81** (App-Symbol in der Benachrichtigung) und **82**
(Warnung vor dem Akkuverbrauch der Daueraufzeichnung), beide am 02.09.2026 vom
Auftraggeber gemeldet · Changelog-Präfix `Android` mit der ersten verteilten
Fassung · Prüfdokument S4 fortschreiben, Erledigt-Zeile in Abschnitt 8 ·
**Play Console nach R65** (Vorbereitung liegt vor:
`docs/konzepte/Vorbereitung-Play-Console.md` — alles, was ohne D-U-N-S und
Signaturschlüssel geht, samt ausgefülltem Datensicherheitsformular,
Deklarationstext und Video-Drehbuch)**:** Organisationskonto der Gen-EM GbR ist
eingerichtet (Zuarbeit, Abschnitt 6) · Versionscode-Versatz für das
Uhr-Modul (Backlog 98; E-S4-02 bleibt eine Zählung) · vorhandener
Signaturschlüssel als App-Signaturschlüssel bei Play App Signing,
Upload-Schlüssel erzeugt und übergeben · Deklarationen, soweit der interne
Track sie verlangt (Vordergrunddienst/Standort mit Demo-Video,
Datensicherheitsformular) · **erstes Release auf dem internen Test-Track**
für Handy und Uhr, Testerliste = der bekannte Kreis · `android/LIESMICH.md`
und Handbuch 10.1 nachgezogen — **die Karte „NAdoku für Android" bleibt als
Rückfall bis zur Produktionsfreigabe** (R65), Handbuch 10.1 nennt den Track
als Regelweg. **Abnahme:**
Prüfliste 4 und 6 des Prüfdokuments S4 (Telefon, Kreisläufe R24 auf
geschnittenen und importierten Einsätzen), Messstand für das Schneiden;
dazu Installation von Handy- und Uhr-App aus dem internen Track auf dem
S24 und einer Wear-OS-Uhr, Update von der Seitenladungs-Fassung auf die
Track-Fassung **ohne Neuinstallation** (gleiche Signatur).
**Wear-OS-Uhr:** Gerätetest, sobald eine vorliegt; für die
Wear-OS-Prüfrunde des Tracks gebraucht.

**Stand 06.09.2026:** Teile A, B und C sind seit dem 04.09.2026 auf `main`
(PR #33), `update.php` ist gelaufen (`2026_09_04_herkunft_geraet` verbucht
um 23:15). Erledigt-Zeile in Abschnitt 8. **Was den Schritt noch offen
hält, liegt nicht am Code:** der Gerätetest am S24 und mit einer
Wear-OS-Uhr, Android 1.0.0 nach E-R45-7, D-U-N-S und Signaturschlüssel
(Abschnitt 6). Die Konzepte S4 und R64 bleiben nach R62 bis zur Freigabe
des Abschlusses liegen. **Ein Fund am Produktivstand:** Der Knopf
„Diensttage zusammenführen" in der R57-Warnung führt ins Leere (Nr. 148,
Backlog-Runde) — kein Prüfmittel klickt Knöpfe.

### Schritt 7 — S8 Einstellungen, Administration und Wartung

**Ziel:** Die Einstellungs-, Verwaltungs- und Wartungsseiten werden einmal
**ergebnisoffen** gesichtet und neu geordnet, bevor P5 dort weitere
Optionen anlegt (R61, Beschluss vom 02.09.2026). **Anlass, aus den
Rückmeldungen vom 02.09.2026:** Begriffe und Optionen der Sicherung sind
über P3 und S2 gewachsen und wirken wie Wildwuchs (Kontoseite,
Sicherungsseite, Sicherungsziele, Komplettsicherung, Wartungsseite); die
Wartungsseite `update.php` trägt Migrationsliste, Job-Einstieg mit Cron
und Token, Speichergrenze und mehr auf einer Seite; die Filterknöpfe der
NutzerInnen-Liste brechen in zwei Zeilen; die Unterpunkte des Admin-Menüs
sind fett und nicht einklappbar; die Bedienhöhe 44 px wirkt am
Schreibtisch hoch; der Wertekasten zeigt Cron-Adresse und Token in der
Schriftgröße des Kopplungscodes. **Inhalt** (Konzept nach K1, Sichtung mit
Fable nach R14, Mockup und Freigabe je neuer Darstellung nach `CLAUDE.md`
5): (1) Bestandsaufnahme jeder Einstellung und jeder Verwaltungshandlung
mit Fundort, Begriff und Zielgruppe (NutzerIn, Admin, Betreiberin) · (2)
Neuordnung: welche Seite trägt was, Menüstruktur der Einstellungen und
der Administration, Aufteilung der Wartungsseite (etwa Serverbetrieb und
Jobs, Sicherung, Migrationen), Ort der Migrationsliste · (3) die
Sicherungsoptionen vereinheitlicht (Begriffe nach S7; Aufbewahrung,
Speichergrenze, Ziele, Zeitplan, je Konto gegen je Installation) · (4) die
Einzelpunkte 73, 74, 75, 77, 78, 79 · (5) eine Vorgabe für P5, wo
Support-Adresse, Rechtstexte, Betriebsart der Registrierung und die
S2-Optionen liegen (R31, R32, R37) · **(6) die Rolle „BetreiberIn"**
(E-S8-02, R75) — dritte Rolle neben `user` und `admin`, Hierarchie
BetreiberIn ⊇ Admin ⊇ NutzerIn, Migration macht alle heutigen Admins zu
BetreiberInnen · **(7) die Seiten Status und Statistik** im neuen Block
„Betrieb" (E-S8-16, E-S8-05) — die Wartungsseite wird aufgelöst · **(8) das
Ordnungsprinzip** als Programmregel (E-S8-01, R74). **Entscheidung im
Konzept:** die Bedienhöhe am Schreibtisch (Nr. 74) — entschieden als **zwei
Stufen** (44 px, am Zeigergerät ab 1024 px 36 px; R76), Nachtrag in
`CLAUDE.md` 5 und `Design.md`. **Abnahme:** Bilderlauf
in acht Breiten, Vollständigkeit, Wortliste, Stilvergleich mit
Soll-Ist-Liste, Bedienprüfung jeder umgezogenen Funktion; Handbuch
nachgezogen, verschobene und entfernte Funktionen ausgetragen. **Nicht
Umfang:** neue Verwaltungsfunktionen und Rollen (P5, R38) — **außer** der
Rolle BetreiberIn (R75). **Lage:** nach
S4-Rest und S7, weil alle drei `einstellungen.php` und die Admin-Seiten
anfassen.

**Konzept:** liegt vor (Fable, 05.09.2026),
`docs/konzepte/Konzept-S8-Einstellungen-Administration-Wartung.md`; Mockups
01 und 03–12 freigegeben, 02 verworfen (Ablage
`docs/konzepte/konzept-s8/mockups/`). Prüfdokument daneben (K9).
**Acht Arbeitspakete** nach Konzept Abschnitt 7: AP1 Rolle BetreiberIn ·
AP2 Betrieb Teil 1 (Updates, Hintergrundjobs, Servereinstellungen) · AP3
Verwaltung (Installation, Konto-Backups, Kontoseite) · AP4 Betrieb Teil 2
(Status, Statistik) · AP5 Menü und Leiste · AP6 Einstellungen (Geräte,
Wertekasten, Filterreihe) · AP7 Bedienhöhe · AP8 Abschluss.

### Schritt 8 — S9 Einsatzbearbeitung und Rettungsmittel

**Ziel:** Die Problemsammlung vom 03.09.2026 wird in einem Zug analysiert,
konzipiert und umgesetzt — dreizehn Punkte (Nr. 101–113) zur
Einsatzbearbeitung, zur Ortsauswahl und zu den Rettungsmitteln; die
Sammlung mit ihren neunzehn Entscheidungen liegt in
`docs/konzepte/Vorbereitung-S9-Problemsammlung.md`. **Inhalt** (Konzept
nach K1 mit Fable, R14): (1) **zuerst der Zielkonflikt** PS-8.2 — Notizfeld
verschlüsselt und trotzdem durchsuchbar wie die übrigen Felder; Optionen
mit Preis, bevor entschieden wird; die Antwort geht in das
Bedrohungsmodell des Reviews ein (Nr. 43, R69) · (2) der **gemeinsame
Kartendialog** mit Adresssuche als eine Komponente (PS-1, Voraussetzung für
PS-7); erste Prüffrage die Geocoding-Quelle — dieselbe wie die heutigen
Adressvorschläge oder keine (`CLAUDE.md` 4) · (3) die Bugs PS-2, PS-4,
PS-6, PS-10.3 · (4) die Erweiterungen PS-7 und PS-10 mit ihren Migrationen
und der Prüfung des Vertrags (R12) · (5) die Gestaltung PS-3, PS-5, PS-8.1,
PS-9 — **Mockups zu PS-3 und PS-5 sind Fable-Schritte** (K2), Freigabe je
Darstellung (`CLAUDE.md` 5); PS-3 setzt die Entscheidung zu Nr. 74 aus dem
S8-Konzept voraus. **Reihenfolge:** Konzept nach dem S8-Konzept; Umsetzung
parallel zur S8-Umsetzung zulässig, wenn beide Konzepte ihre Berührungen
benennen (Stylesheet, Stammdaten- und Einstellungsseiten). **Vorgabe aus
S8** (05.09.2026): das Ordnungsprinzip (R74) gilt auch für die
Tagesübersicht — S8 5.7 sagt, was dort eine Ebene tiefer gehört (Spuren,
Ruhezeiten, Schneiden, GPX einfügen, Spuren als GPX, Zusammenführen,
Zuordnung); S8 fasst `index.php` nicht an (E-S8-03). Die **Bedienhöhe ist
entschieden** (44/36, R76) und wird in S8 AP7 umgesetzt — PS-3 baut darauf
auf und wartet darauf. P5 setzt S9
nicht voraus, **P6 schon**. **Abnahme:** Bilderlauf in acht Breiten,
Kreisläufe csv und edbak (Datenmodell), Wortliste, Bedienprüfung je Punkt
auf dem Auftraggeber-Client, Handbuch nachgezogen, Register gegengezählt.

**Zuarbeit F3–F6 liegt vor (06.09.2026), und sie stellt PS-3 richtig:**
Gemeint sind die **Schilder auf der Karte** der Einsatzansicht — die
Kästchen für Standort und Zielklinik (`--geo-schild`, 36 px), der Kreis des
Einsatzorts (`--geo-kreis`) und die Start/Ende-Ringe (`--geo-ring`, 3 px) —,
**nicht Formularknöpfe**. Die Abhängigkeit von Nr. 74 ist damit schwächer
als angenommen (Kartenschild, nicht Bedienhöhe; R76 gilt trotzdem als
Untergrenze am Finger). **F3:** zwei Screenshots (Desktop-Vollbild und der
kleine Kartenausschnitt der Einsatzansicht) in
`docs/konzepte/vorbereitung-s9/`; Befund: Schilder teils zu groß, vor
allem die Umrandung — der Standort trägt Rahmen, Doppelring und
Weißraum übereinander. **F4:** alle Lagen, vor allem Desktop und Handy.
**F5:** die Beginn/Ende-Anzeige zeigt keine Uhrzeiten — ein Ring kann sie
tragen. **F6:** kein zweites Merkmal neben der Farbe, aber eine schmale
Trennlinie zwischen den Farben. **Dazu Nr. 147** (Fassung 32) als PS-11
der Vorbereitung: die aufgezeichnete Spur im Kartendialog der
Einsatzbearbeitung — nur die Spur über den Spur-Weg, **keine Luftlinie**,
in jedem Kartendialog des Einsatzformulars (heute Einsatzort, mit PS-7
auch Zielklinik), Zoom auf die Spur, solange das Feld leer ist; Ergänzung
zu PS-1, verträglich mit S11 (der Dialog bleibt im Browser). **Verhältnis
zu Schritt 9a (Beschluss 07.09.2026):** Nr. 137 (Photon: Hinweis,
Datenschutztext, Installationsschalter) und Nr. 132 (Klartext-Hinweis) sind
ganz nach S9 gewandert, weil 9a nicht begonnen hatte und S9 sonst gewartet
hätte; S9 und 9a berühren sich in keiner Anwendungsdatei mehr und laufen
parallel (Abschnitt 4).

**Konzept: liegt vor und ist freigegeben (07.09.2026).** E-S9-01 bis -19;
acht Arbeitspakete (Bausteine · Geocoder und Dialog · Karte und Zeichen ·
Rettungsmittel mit Formatänderung Nutzlast 10 · Standortseiten ·
Tageszuordnung · Verschlüsselung der Notizen · Abschluss), kein
Fable-Schritt in der Umsetzung. Der Zielkonflikt PS-8.2 ist aufgelöst — die
Suche läuft im Browser, Notizen werden verschlüsselt wie die Diagnose
(E-S9-01, Katalogschlüssel `store => 'pat'`, stille Anhebung des
Altbestands). **Dazu genommen** (06.09.2026): Nr. 44, 68, 69, 70, 72, weil
sie in denselben Dateien liegen; **PS-12 Standortseiten** (07.09.2026,
Nr. 152 — Fassung 34 nannte hier irrtümlich Nr. 150, das ist der
Cron-Befehl aus Fassung 33): Der Menüpunkt „Rettungsmittel" entfällt, „Standorte" wird Liste
Nr. 152): Der Menüpunkt „Rettungsmittel" entfällt, „Standorte" wird Liste
und Seite je Standort mit Kennzahlen als Inhaltsverzeichnis, Anlegen im
Dialog und Landung auf der neuen Zeile. Sieben Mockups freigegeben: Schilder
(V1: Farbring statt Rand, 30/28/3 px), Artzeichen (Hubschrauber und
Fahrzeug bleiben; Bergwacht „mountain", Veranstaltung „building-stadium",
Sonstiges „dots-circle-horizontal"), Vorschlagsliste mit Gruppen,
Kartendialog mit Suchfeld und Spur, Sprungliste, Standortseiten, die drei
Anlegen-Dialoge. Neues Prüfmittel `tools/klickprobe/` (E-S9-16), weil PS-2
und Nr. 148 beide ein Klick waren, den niemand getan hat. **Umsetzung
beauftragt am 07.09.2026** (Auftrag `Prompt-Umsetzung-S9.md`, außerhalb
des Repositoriums; Opus, Claude Code, eigener Zweig).

**Umsetzung, Stand 07.09.2026** (Zweig `claude/go-bwucrx`; Statusblock und
Zahlen im Konzept, Prüfprotokoll in
`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md`):

**AP1 — Vorschlagsliste und Klickprobe · erledigt, Web 15.7.0 und 15.7.1.** Eine Liste
statt dreier und einer vierten vom Browser: `assets/vorschlagsliste.js`
(`EdVorschlaege`) mit Gruppen, Symbol und Herkunftszeile je Eintrag,
Pfeiltasten, Enter und Escape — und Übernahme auf `mousedown`, nie auf
`click`. Damit sind **Backlog 68, 102 und 106** erledigt. Neuer Baustein
`Design.md` 9.28; `loc-suggest`, `rmlist`, `rmopt` und `rmneu` auf der
Streichliste. **Neues Prüfmittel `tools/klickprobe/`** (E-S9-16), das erste
im Projekt, das ein Element *bedient*: `mouse.down()` — 300 ms halten —
`mouse.up()`, weil `locator.click()` die Taste rund 10 ms hält und Nr. 102
deshalb **nicht** findet. Zahlen: PS-2 **0 von 3 → 3 von 3** (dieselbe
Fassung der Probe gegen beide Stände), `datalist` **12 → 0** außerhalb von
Kommentaren, Transportziel **1 Liste · 2 Gruppen · 2 Stammdaten · 4
Adressen · 0 `<datalist>`**, Bedienhöhe einzeilig **44/36/44** (390 px,
1280 px Zeiger, 1280 px Finger), Klickprobe **12 von 12** Wegen über zwei
Breiten × zwei Bedienhöhen, Bilderlauf sieben Seiten **0/0/0** in beiden
Bedienhöhen, Wortliste **0/0/0**, Vollständigkeit **300 → 298** (Differenz
erklärt), Kontraste **21/0**, Linkprobe **132/0**. Fünf Funde, alle behoben —
drei davon im Prüfmittel selbst.

**Korrekturstufe 15.7.1** (07.09.2026, vom Auftraggeber am Bild gemeldet):
Die Liste lag mit `z-index: 20` **hinter** der klebenden Speichern-Leiste (30)
und verdeckte deren unterste Trefferzeilen — gemessen 61 bis 69 px
Überlappung, `elementFromPoint` traf die Leiste. Ebene jetzt **35**, zwischen
Speichern-Leiste und Kopfleiste (40); die Klickprobe misst sie in beide
Richtungen und zählt damit **16 von 16** Wegen. Der Befund dahinter:
Bilderlauf und Klickprobe meldeten beide Null und hatten beide recht — sie
haben etwas anderes gemessen.

**Von den drei Fragen an den Auftraggeber sind zwei entschieden**
(07.09.2026): Die Besatzungsfelder des Diensttags gehören zu **AP1** (so
gebaut), und im Abschluss wandern **zwanzig** Backlog-Punkte nach *Erledigt*
— also auch **Nr. 132 und Nr. 137**, je mit dem Vermerk „aus dem Sofortpaket
übernommen" (das Konzept sagte achtzehn; diese Entscheidung geht vor). Offen
ist die dritte („`<datalist>` in der Streichliste"), Prüfdokument
Abschnitt 4.

**AP2 — Geocoder und Kartendialog · erledigt, Web 15.8.0.** Die Anschrift
des Adressdienstes stand zweimal fest im ausgelieferten Code; jetzt steht sie
nirgends dort. `assets/geocoder.js` (`EdGeocoder`) ist der eine Weg nach
draußen, `server/geocoder_lib.php` die eine Quelle der Einstellungen, und
`ui_ortsfeld()` bringt sie selbst ins Dokument — wo ein Ortsfeld steht,
stehen seine Einstellungen. **Zwei Schalter davor:** einer je Installation
(Betrieb → Servereinstellungen, Karte „Adresssuche", dazu das Feld „Dienst" —
wer einen eigenen Photon betreibt, trägt ihn ein und hält die Anfragen im
eigenen Haus), einer je Konto (Profil → Karte „Datenschutz",
`users.adresssuche`). **Der Kartendialog** bekommt ein Suchfeld im Kopf (ein
Treffer setzt nur das Kreuz, F1), die aufgezeichnete Spur mit Start- und
Endring samt Legende und `fitBounds` bei leerem Feld; den Pin-Knopf tragen
jetzt **fünf** Felder statt zweier. Damit sind **Backlog 70, 101, 107, 137
und 147** erledigt und der Adressdienst ist der einzige Laufzeitdienst des
Projekts, den man abschalten kann. Neuer Baustein `Design.md` 9.29.

Zahlen: `grep -rn "komoot" server/assets/` **2 → 0**; Dialog aus **5 von 5**
Einbauorten; Treffer im Suchfeld **Feld leer / 0 Chips**, nach „Übernehmen"
**1 Chip**; Spur **1 Linie · 4 Ringpunkte · Legende sichtbar · 0 Pfeile** bei
309 Punkten; Kontoschalter **an → 2 Anfragen, aus → 0** an
`photon.komoot.io` (mit Gegenprobe, sonst belegt die Null nichts);
Installationsschalter aus → Kontoschalter gesperrt mit Grund; Hinweis am
Ortsfeld **1 bei 3 Ortsfeldern**; Klickprobe **40 von 40** Wegen, 36 Bilder;
Bilderlauf **zehn berührte Seiten 0/0/0** in beiden Bedienhöhen; Wortliste
**0/0/0**; Vollständigkeit **298 → 304** (Differenz erklärt: sechs
Menüpfeile „→" in deutschen Sätzen, dazu ein eingetragener Anker); Kontraste
**21/0**; Linkprobe **134/0**; Migrationsregister **44 = 44**.

**Drei Funde, alle behoben — und keiner davon im Browser zu sehen.** Der
Bootstrap schrieb `const`, `assets/geocoder.js` las `window`: Ein `const` auf
oberster Ebene liegt im globalen lexikalischen Bereich, wird aber keine
Eigenschaft von `window` — der Dialog kam ohne Suchfeld, während die
Hinweiszeile daneben sagte, die Suche sei an, und die Konsole die erwartete
Antwort gab (F-S9-P-08). Die Betriebsseite zeigte nach dem Speichern den
**alten** Schalterstand, weil der Zwischenspeicher der laufenden Anfrage vor
dem Schreiben gefüllt und danach nicht nachgezogen wurde (F-S9-P-09). Und das
**Demo-Konto konnte seine eigene Adresssuche nicht abschalten**: Der Schalter
stand im Profilformular, und der Demo-Wächter verwirft dieses ganz, damit die
öffentlichen Zugangsdaten stehen bleiben — die Karte „Datenschutz" hat jetzt
ihr eigenes Formular (F-S9-P-10). Gefunden hat alle drei die Klickprobe.

**Zwei neue Fragen an den Auftraggeber** (Prüfdokument, Abschnitt 4):
**Pfeile auf der Spur im Kartendialog?** — Konzepttext E-S9-06 (b) sagt
„keine Pfeile", Anmerkung 3 des Mockups M-S9-04 sagt „Pfeile wie in der
Einsatzansicht", und das Bild zeichnet einen; gebaut ist **ohne**, eine
Gegenentscheidung kostet vier Zeilen. Und: Der **Kopf des Kartendialogs** ist
jetzt eine Überschrift wie in jedem anderen Dialog (bisher als einziger
nackter Text) — das Mockup zeigt es so, das Konzept schweigt.

**Seit dem Merge (10.09.2026) muss eine Administratorin `update.php`
aufrufen** — AP2 bringt die Migration `2026_09_07_adresssuche_konto`
(Spalte `users.adresssuche`). Bis dahin gilt für jedes Konto die Vorgabe
„an", und die Anwendung läuft weiter; beide Leser vertragen die fehlende
Spalte.

**AP3 — Karte und Zeichen · erledigt, Web 15.9.0.** Vier Dinge an derselben
Karte und denselben Wörtern.

**Die Zeichen werden kleiner** (M-S9-01 V1): Der Farbring ist jetzt der
**Rand** und liegt nicht mehr darum herum — ein Standort mit Doppelring maß
60 px und deckte auf der Handykarte mehr als ein Drittel der Höhe.
Nachgemessen im Browser: **32 / 32 / 38 / 28 / 14 / 20 px** gegen vorher
36 / 48 / 60 / 32 / 16 / 28.

**Die Richtungspfeile haben sich nie gedreht** (Nr. 72). `transform` wirkt
nicht an einem Inline-`<span>`; die Winkelrechnung war die ganze Zeit
richtig, sie kam nur nie an. Gemessen an der Bildschirmmatrix des SVG:
vorher `a=0,833 b=0 c=0 d=0,833` bei behaupteten 90 Grad, nachher **12 von
12** Pfeilen in 30-Grad-Schritten auf **0,1 Grad** genau. Derselbe Fehler
steckte drei Zeilen darüber im Punkt des Abfahrtorts — **Nr. 162**, in keinem
Backlog-Punkt, 4 × 18 px statt 12 × 12 und die Spurfarbe nie sichtbar.

**Die Windenkacheln folgen der Fähigkeit** (Nr. 104), nicht mehr der
Zählung: „null Windeneinsätze" ist eine Aussage über den Dienst, „Winde nicht
eingerichtet" eine über die Stammdaten. `api/range.php` liefert
`faehigkeiten`; die Abfrage ist auf **Luft** eingeschränkt, weil eine
Migration von 2026 jedem damals bestehenden Diensttag beide Fähigkeiten gab.

**„Spur" heißt für die NutzerIn „GPS-Daten"** (Nr. 110): 72 sichtbare
Zeichenketten in 18 Dateien, 41 Zeilen im Handbuch. Fachbegriff bleibt er im
Code, in `Technik.md`, im JSON-Vertrag und im Sicherungsformat. Die
**Android-App** bleibt außen vor — sie zählt getrennt und braucht einen
Emulatorlauf; **Schritt 9a nimmt sie mit** (Entscheidung des Auftraggebers,
07.09.2026, Handzettel im Prüfdokument Abschnitt 6).

Dazu **drei neue Zeichen** (Bergwacht, Veranstaltung, Sonstiges; Vorrat
49 → 52) und `dt_art_symbol()`, das den Diensttag-Typ schon entgegennimmt —
im Datenmodell steht er erst mit AP4.

Zahlen: Schildmaße **7 von 7** getroffen; Pfeile **12 von 12** und **2 von 2**
auf der Spur; Windenkacheln **2 / 2 mit „0" / 0**; Artzeichen **6 von 6**;
Wortliste **0/0/0** bei 96 Regeln mit neuer Regel `spur`; `grep -c "Spur"
docs/Handbuch.md` **41 → 0**; Klickprobe **6 von 6** Wegen; Vollständigkeit
**304 = 304**; Kontraste **21/0**; Linkprobe **134/0**; Bilderlauf zehn berührte Seiten **80 Bilder, 0/0/0** in beiden Bedienhöhen.

**Eine Frage aus AP3 ist entschieden, eine bleibt.** Die Lesbarkeit von
`veranstaltung.svg` bei 18 px hat der Auftraggeber am 07.09.2026 mit dem
Tausch auf Tabler „ticket" beantwortet (Messung: „ticket" hält seine
Binnenfläche von 96 px bis 16 px, „building-stadium" verliert bei 18 px zwei
seiner vier auf einen einzelnen Pixel). Offen bleibt die alte Frage 2 aus AP1.

**AP4 — Rettungsmittel: Typ, Kurzname, Standort optional** (07.09.2026,
Web **16.0.0**, Migration `2026_09_07_rettungsmittel_typ`, Nutzlast 9 → 10).
Zwei Achsen statt einer: `kind` bleibt die Betriebsart, `typ` ist die Art des
Dienstes (Standard, Bergwacht, Veranstaltung, Sonstiges). Kurzname bis 16
Zeichen; `vehicles.base_id` wieder NULL-fähig, Pflicht nur bei „Standard";
`days` friert Typ und Kurznamen ein. Alle drei Schreibwege laufen jetzt über
eine gemeinsame `pruef_rettungsmittel()` — vorher prüften Konto, Verwaltung
und Sicherung dieselbe Sache dreimal ungleich, das Einspielen am
schwächsten. **Hauptnummer**, weil Datenmodell, Dateiformat und eine feste
Zusage (E15) zugleich betroffen sind. **Drei stille Stellen gefunden und
behoben:** `nb_moeglich()` hätte die abgeschlossene Nachbearbeitung in jeder
Installation wiederbelebt und ihr Knopf die Migration zurückgenommen;
`dt_vehicle_erlaubt()` hätte ein zentrales Rettungsmittel ohne Standort beim
Speichern wortlos verworfen; beide Stammdatenseiten hätten es unsichtbar
gemacht. **Zahlen:** Kreisläufe **edbak 287 771 / csv 9 118 / edbak-alt
287 781 Einzelvergleiche, je 0 unerklärt** (16 / 1 021 / 653 erwartet);
Klickprobe **6 von 6** neuen Wegen; Register **45 = 45**; frische Installation
und migrierte Datenbank strukturgleich; Referenzbestand **3 → 6
Rettungsmittel** (je einer der vier Typen, zwei ohne Standort, zwei mit
Kurznamen), Demo-Fixture neu, 55 861 Spurpunkte unverändert; Wortliste
**0/0/0** bei 96 Regeln über fünf Bereiche (178 Dateien); Vollständigkeit
**304 = 304**; Kontraste **21 Paare, 0 verfehlt**; Linkprobe **140 Verweise,
0 unbekannte Abweichungen**; Bilderlauf **11 berührte Seiten, 88 Einzelbilder
je Lauf, 0/0/0 in beiden Bedienhöhen**. `veranstaltung.svg` trägt jetzt Tabler
„ticket" (Entscheidung des Auftraggebers vom 07.09.2026, mit Messung
begründet).

**AP4a — die Nachträge aus den Freigaben vom 08.09.2026** (Web **16.1.0**,
keine Migration). Die vier Fragen aus AP4 sind entschieden: Kachel und
Plakettenzeile bleiben, wie sie sind (der Kurzname gilt allein für die
Leiste, E-S9-09 ist entsprechend zurückgeschnitten); die Leiste zeigt ihn
dafür auch im Band 1024–1199 px, in dem sie 220 px schmal ist; der
Vergleichsdialog des Zusammenführens nennt den Typ; und Rettungsmittel ohne
Standortpflicht sollen das Löschen ihres Standorts überleben — das gehört zu
AP5. Gebaut sind die ersten drei Punkte, also zwei Änderungen: zwei Regeln in
`style.css` samt einer Klasse in `ui.php`, und eine Zeile plus zwei Zusätze
im Vergleichsdialog. Dabei zeigte sich, dass die Begründung „unter 1200 px
entfällt der Rettungsmittelname" an drei Stellen falsch stand — sie gilt nur
im Band 1024–1199 px, in der Schublade stand der Name immer; berichtigt.
Prüfzahlen: Klickprobe **10 von 10** Wegen über fünf Breiten (390, 1024,
1100, 1199, 1280 px) in beiden Bedienhöhen, 0 Rückstände; dem Kurznamen bleiben im Band je nach Datum **48 bis 55 px** — „BW Hoch" braucht 55, also tragen ihn **4 von 13** Datumsangaben ganz und **9 mit Auslassungszeichen**; ohne die Einrückung wäre es **keine einzige** (40 bis 47 px).
Diese Zahl ist die **Berichtigung** einer ersten Messung, die 57 px meldete
und dabei zufällig das schmalste Datum getroffen hatte — gefunden von der
adversarischen Gegenprobe, zusammen mit zwei weiteren Fehlern (der Kurzname
verschwand am mehrfachen Tag auf 3 px; die Zeile „Typ" behauptete einen
Wert, den die Wahl ändert). Mit **Web 16.1.1** ist die Frage beantwortet: Das Mockup
**M-S9-11** hat drei Wege an der laufenden Anwendung gemessen, gewählt ist
Weg 2 — im Band je Ebene 4 px Einrückung und 4 px Abstand in der Zeile.
Danach stehen dem Nebentext **64 bis 79 px** statt 48 bis 63 zur Verfügung,
und von dreizehn Kurznamen trägt **keiner** mehr ein Auslassungszeichen
(vorher zehn). Weg 3 — das Jahr im schmalen Band aus dem Datum nehmen —
ist verworfen; das Datum behält sein Jahr in jeder Breite. Die Gewinnerregel
des Zusammenführens steht seither an **einer** Stelle statt an zweien
(`dt_merge_rm_gewinner()`).

**AP5 bis AP8 — offen.** AP5 beginnt nach dem Wort des Auftraggebers (K7).

### Schritt 9 — Backlog-Runde

**Ziel:** Die Einzelpunkte aus Abschnitt 5, die keiner Phase bedürfen.
**Regeln:** je Punkt ein Commit, Buchführung nach `CLAUDE.md` 2, kein
Konzept nach K1; ein Punkt, der eine neue Darstellung braucht, bekommt
vorher ein Mockup und eine Freigabe (`CLAUDE.md` 5). Läuft jederzeit ab
Schritt 1 parallel, auf eigenem Zweig; die Dateiregel aus Abschnitt 4 gilt.

**Erledigt als eine Korrekturstufe, Web 15.5.2 (06.09.2026, Auftrag vom
selben Tag).** **Nr. 148** — der Knopf „Diensttage zusammenführen" in der
R57-Warnung verlinkte `diensttag_zusammenfuehren.php?ziel=`, die Seite liest
`d`; Ergebnis 404 genau in dem Fall, für den die Warnung gebaut ist. Behoben
mit einer Zeile; dazu **`tools/linkprobe/`** (neu), das jede Adresse
`<seite>.php?<name>=` gegen die Parameter der Zielseite hält. **Nr. 149** —
die Seite Updates zählte anders als Status und Menü: Eine Migration, die das
Schema schon kennt, zählte als offen, lag aber unter *Ausgeführt* und bekam
keinen Knopf. Behoben mit einem eigenen Anzeigestatus (`skip`, Plakette
„nicht nötig", unter *Ausstehend*, Knopf da); die Wartungsprobe misst den
Zustand jetzt in **Teil 6** (50 statt 43 Erwartungen).

**Teil (a) von Nr. 149 ist keine Codeänderung und bleibt aufgerufen:** Die
Regel — **eine Migration, die Rechte einführt, muss ohne diese Rechte
ausführbar sein** — steht mit dem phpMyAdmin-Notweg im Runbook
(`docs/Technik.md` 7) und gilt für die nächste Rollenmigration
(**Support-Rolle, R38, P5**) und im **Bedrohungsmodell (P6, R69)**.

**Zwei neue Nummern sind dabei entstanden:** **Nr. 150** (der Cron-Befehl mit
dem Repositoriumspfad, gemeldet vom Auftraggeber) und **Nr. 151** (`?day=`
statt `?d=` nach dem Import — gefunden von der neuen Linkprobe, nach K4
nicht mitbehoben). Beide stehen in Abschnitt 5.

**Gemergt am 06.09.2026** (PR #36). **Offen bleiben** die zwei Punkte der
Prüfliste in `docs/konzepte/Pruefdokument-Korrektur-148-149.md`; sie sind
seither fällig.

**Die Runde geht weiter (12.09.2026).** Nach S9 sind die Einzelpunkte aus
Abschnitt 5 wieder an der Reihe; die Auswahl steht im Prüfdokument der
Runde. Dazu die Buchführung, die dieselbe Fassung mitbringt: Abschnitt 5
war aus dem Tritt geraten (24 erledigte Zeilen, neun fehlende offene), und
drei Backlog-Punkte trugen ihre Erledigung im eigenen Text, standen aber
weiter unter *Offen* — Nr. 19, 159 und 160.

### Schritt 9a — Sofortpaket Sicherheit

**Nr. 132 und 137 sind seit dem 07.09.2026 nicht mehr Teil von 9a** — sie
sind ganz nach S9 gewandert (E-S9-02, E-S9-05), damit beide Schritte sich
in keiner Anwendungsdatei berühren. 9a fasst `ortsfeld.js`, `ortswahl.js`,
`mission_fields.php` und `betrieb_server.php` **nicht** an; SP-12 (a) und
K-12 in der Vorbereitung tragen den Vermerk. Wer von beiden zweiter mergt,
zieht `CHANGELOG.md`, `Rahmenplan.md`, `Backlog.md` und `version.php` nach
(K7).

**Ziel:** die kleinen Befunde des Krypto-Reviews (R78) schließen, bevor
irgendetwas Größeres beginnt. **Inhalt:** Web Nr. 127–131, 133–136, 138 und
140, Android Nr. 142–145 samt dem Räumteil von Nr. 114 — je Punkt ein
Commit, kein Konzept nach K1, Prüfdokument mit Zahlen (Muster R42: eigener
Zweig, eigene Versionsstufe, Deploy nach Freigabe). Die Spezifikation ist
`docs/konzepte/Vorbereitung-Sicherheitspaket.md` (SP-1, SP-2, SP-8, SP-13,
SP-14) und die Integritätswache (SP-6, Nr. 140). **Entschieden am
06.09.2026:** Ersetzfenster 72 h ab Einsatzbeginn (F-SP-8),
Integritätswache jetzt (F-SP-9); der Photon-Schalter (F-SP-4) ist seit dem
07.09.2026 Teil von S9. **Umsetzung beauftragt am 07.09.2026** (Auftrag
`Prompt-Umsetzung-9a.md`, außerhalb des Repositoriums; parallel zu S9).
**Abnahme:** Bilderlauf der berührten Seiten, Wortliste 0/0/0,
`tools/gpxprobe/` für Nr. 130, ein Browserlauf mit Zahl für Nr. 127 und
128, Emulatorbilder für Android.

**Stand 07.09.2026: Der Web-Teil ist gebaut und geprüft** — Web 15.6.0, elf
Punkte in vierzehn Commits (drei Nachbesserungen zu 136 und 140), Zweig
`claude/sofortpaket-sicherheit-1t70p1` gepusht,
Prüfdokument `docs/konzepte/Pruefdokument-Sofortpaket-Sicherheit.md`.
Abnahme erfüllt: Bilderlauf 14 Seiten in acht Breiten in **beiden**
Bedienhöhen (je 112 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe falscher
Höhe), Wortliste **0/0/0**, `tools/gpxprobe/` **88 Erwartungen** mit Teil 8
(8 Umgehungsproben, 0 durch), Nr. 127 **2 von 2** und Nr. 128 **4 von 4** im
Browser.

**Stand 07.09.2026, später: Der Android-Teil ist gebaut und geprüft** —
Android 0.14.0, fünf Punkte, fünf Commits (Nr. 142, 114 Räumteil, 143, 144,
145). Abnahme: `./gradlew build` `./gradlew build` grün — Handy **261 Prüffälle je Bauart** (Debug und Release; vorher 247), **0 Fehlschläge**, 15 übersprungen (14 Rundlauf ohne Installation und der jeweils bauartfremde Fall aus Nr. 142); Uhr **71 Prüffälle**, 0 übersprungen; Lint **0 Fehler** (Handy 13 Warnungen, unverändert die `libs.versions.toml`-Hinweise; Uhr 0); Release-APK Handy **7 867 394 B** (+332 B gegen 0.13.0), Uhr **19 574 406 B** (unverändert); Bilderlauf 72 Bilder wie zuvor; Emulator Stufe II erreicht im fünften Anlauf: Boot 715 s, Kopplung, Einstellungen und Trennen mit acht Bildern, Räumlauf am echten Android-SQLite 1 → 0 in allen vier Tabellen, Gerät am Server gelöscht; vier Anläufe ohne Boot wegen des Android-Watchdogs unter TCG, Gegenmittel `ro.hw_timeout_multiplier` als Root (F-SP-P-07); Wear-Emulator nicht gefahren;
Wortliste **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** (87 Regeln, alle fünf Bereiche einschließlich d = Android). Zwei Zahlen sind gewählt, nicht gemessen, und
stehen mit Begründung am Code: 30 Tage Räumfrist, fünf Minuten Zeitspiel.
Die Schnittstelle `Nachrichtenweg` ist unverändert. Damit ist 9a vollständig
gebaut; die Erledigt-Zeile steht in Abschnitt 8. **Am 08.09.2026 auf `main`
gemergt** (PR #37, `f299bbf`).

**Zwei Funde beim Bauen, beide in Nr. 136 behoben** und beide von derselben
Art: Eine Sicherheitsmaßnahme, die nie ausgelöst hat, ist nicht geprüft. Die
stille Anhebung der Rundenzahl lief **nicht** beim nächsten Anmelden (sie
braucht `CSRF`, und das gab `ui_krypto_bootstrap()` nur auf Anfrage aus), und
die Wartungsseite meldete nur *verwaiste* Rundenzahlen statt der Zahl, die
sagt, wann der Altwert weg darf. Beides schlief, solange `KDF_ITER_LISTE`
einen Eintrag hatte.

**Eine Regeländerung mit Ansage:** Die Sperrliste der Passwortprüfung rechnet
jetzt den **Anteil** statt des Vorkommens. SP-2 empfiehlt Passphrasen und will
zugleich die Liste erweitern — beides zusammen ging nicht, weil
„Anker-Winter-Regen-Glas" an „winter" scheiterte. Der Auftraggeber ist im
Zwischenbericht darauf hingewiesen.

**Stand 07.09.2026, Gegenprüfung: Der Web-Teil ist adversarisch gegengeprüft
und nachgebessert.** Ein Workflow aus 93 Agenten (sechs Blickwinkel, jeder
Fund dreimal zu widerlegen versucht, gegen den heutigen Stand reproduziert)
fand 29 Funde, 22 hielten: sechs zur Dokumentation (`bf5a506`), sechzehn zum
Code, behoben je Punkt als eigener Commit — **134** (Anker des Ersetzfensters
serverseitig, `kept_meta`, Migration `rest_segments.created_at`; `839d317`),
**130** (UTF-7 über die Kodierungsdeklaration; `a395455`), **136** Statuszeile
(`fd29866`) und Passwortregel (Sonderzeichen, Listenreihenfolge; `6699b58`),
**140** (`<base>`, `formaction`, Selbstprobe ohne Ankerwort; `72a4268`) — und
beim Nachprüfen der Wache **17 von 27 Angriffsvarianten noch grün**, geschlossen
in einem zweiten Commit (`4e30b26`). Danach eine zweite Gegenprüfung auf die
Nachbesserungen selbst — im ersten Anlauf **nur zu Nr. 134 gelaufen** (2 von
10 Angreifern, keine Skeptiker; Sitzungsgrenze des API-Kontingents): zwei
Löcher, beide behoben (`15b9881`) — die Migration scheiterte an Randdaten
und galt danach als erledigt, ein eingeholtes Zukunfts-`started_at` öffnete
das Fenster erneut; Anker ist jetzt `created_at` allein, die Migration läuft
dreischrittig mit Kappung. **Wiederaufnahme auf Anweisung** (07.09.2026,
spät): alle zehn Angreifer, 30 Funde (21 verschiedene), alle behoben, je Punkt ein
Commit — 134 (`76eaea4`: Tageszeitraum folgt dem Fenster des Tages,
Rückfall vor der Migration greift, Anker nie kleiner als Sekunde 1,
Papierkorb-Tag verliert seinen Einsatz nicht), 130 (`9f51078`:
Latin-1-Deklaration über UTF-8-Bytes erlaubt und umgeschrieben — die erste
Fassung hatte den Dateidialog-Import gebrochen), 136 (`a8ee900`: Statuszeile
mit richtigem Grund, Demo-Satz nur mit Listenwert, verwaiste Konten kein
Übergang; `caf0cea` und `b42ad1d`: Sonderzeichen je Schriftzeichen, fünf
Reihenformen, angehängte Ziffern an der Eingabe — 8832 gefuzzte Fälle
8064 → 0 Regressionen, Zufall 0/0/0), 140 (`2c61524`: Tag endet nicht im
Attributwert, alle Steuerzeichen vor dem Schema; Selbstprobe 30).
**Eine vierte Nachbesserung kam beim Nachfahren des Skeptiker-Skripts
gegen den eigenen Stand heraus** (`d4eb0a3`): Die dritte hatte den
nachgelieferten Dienst gebrochen — ein Dienst, der später als 72 h nach
seinem Datum hochgeladen wird, bekam keinen Tageszeitraum mehr —, und die
eigentliche Ursache stand noch offen: Der Einsatz selbst wurde mit 96 Jahren
Dauer gespeichert. Jetzt prüfen `pruef_zeit_zum_tag()` und
`pruef_ende_nach_beginn()` in der gemeinsamen Prüfschicht, ob die Zeiten zu
ihrem `day` gehören und ob das Ende nach dem Beginn liegt — **verworfen wird
dabei der Wert, nicht der Upload** (`b978b7d`, berichtigt in `c158a6e`: eine
erste Fassung wies mit `400` ab und hätte damit die falsch gestellte Uhr
ausgesperrt). Die Bremse fragt nach dem jüngsten `created_at` der übrigen
Datensätze des Tages. **Zwei Agenten lasen unabhängig nach, wie Uhr und
Handy `day` bilden** — daraus die Weite des Fensters (der Abstand ist nach
oben unbegrenzt), die Berichtigung des JSON-Vertrags (er beschrieb einen
Uhr-Code, den es seit `52f0191` nicht mehr gibt) und Backlog 159 und 160
(die Uhr wiederholt ein `400` endlos; ein fortgesetzter Dienst läuft unter
dem alten Datum weiter). Skeptiker: je Fund ein Skeptiker, der ihn zu **widerlegen** versuchte und dafür gegen den unveränderten Stand `448ce9f` reproduzierte — **24 von 30 hielten**, sechs wurden widerlegt (Vorbestand oder Randfall ohne beobachtbare Folge: der Diensttag im Papierkorb bei offenem Fenster; drei Zählfälle der Statuszeile, die nur über einen von Hand gesetzten Rundenwert entstehen; die Sonderzeichen-Tastaturreihe, die als Entscheidung dokumentiert ist; die quadratische Laufzeit, die keine Zusage verletzt). **Behoben sind alle dreißig** — auch die sechs, weil jede Behebung für sich mit einer Zahl belegt ist. Prüfzahlen danach: ingestprobe
**62/0**, gpxprobe **95/2** (vorbestehend), wartungsprobe **51/0**, Wache
**30/0** und 112/112, Passwortregel 0 % Zufallsabweisung, Wortliste
**0/0/0**, linkprobe 132/0/1/0, `php -l` 463/0. **Backlog 154–161 neu**
(Handy liest `kept_meta` nicht; Fixture mit alter Rundenzahl;
Prüfstand-Passwort fällt durch). **Der Merge braucht `update.php`.**

**Gemergt am 08.09.2026** (PR #37, `f299bbf`). **Offen und fällig:**
`update.php` (Migration `rest_segments.created_at`) und die Prüfliste
P-1 bis P-12. Nr. 140 bleibt im Backlog offen — die Wache steht,
Branch-Schutz und 2FA sind Zuarbeit, das Deploy-Tor ist S10. Aus Nr. 135 ist
**Nr. 153** herausgelöst (`querySelector` mit einem Wert aus dem
URL-Fragment).

### Schritt 9b — S10 Sicherheit

**Ziel:** Der Datenbankabzug allein reicht nicht mehr für einen
Offline-Angriff auf das Passwort, und nichts verlässt das Haus
unversiegelt. **Inhalt (R78):** **Server-Anteil am Datenschlüssel** —
ein zweites Geheimnis in `config.php`, je Konto per HMAC abgeleitet, nur an
die angemeldete Sitzung ausgeliefert, per HKDF in den Datenschlüssel
gemischt; `pat_wrap_rc` bleibt unabhängig; Umstellung still beim nächsten
Anmelden über den verallgemeinerten Anhebungsweg; dazu **Schlüsselblatt**
(Wartungsseite druckt beide Geheimnisse mit Kennung), **Kennung in
`app_state`** gegen stille Aussperrung, Nachtragen-Weg mit Prüfung,
Rotation von Anfang an · **Adminpakete versiegeln** mit `sk_versiegeln()`,
Protokoll `ftp` abschaffen (Nr. 139). **Rang:** Web Haupt. **Konzept**
nach K1 aus SP-3 und SP-10 der Vorbereitung; Fable. **Voraussetzung:** Schritt 9a;
**vor P5**, weil die Umstellung jede Hülle berührt und vor der Registrierung
gelaufen sein soll. **Abnahme:** Umstellungslauf mit dem Referenzbestand,
Reset-Weg, Freigabeweg, Demo-Reset (bleibt ohne Anteil), Wartungsseite mit
falscher Kennung, Prüfdokument.

### Schritt 10 — P5 Dienstbetrieb

**Ziel:** Die Anwendung trägt eine größere Nutzerbasis sicher. Baut auf
der Ordnung aus S8 auf. **Inhalt nach R9, R10, R31, R33, R36 bis R41:** Registrierung mit drei Betriebsarten
und Sicherheitspaket · Konto-Lebenszyklus (Bibliothek, Kontostatus bis in
`ingest.php`, Double-Opt-In, Selbstlöschung mit Karenz, E-Mail-Wechsel,
Einwilligungen mit Fassungskennung, Mail-Warteschlange, Geräteschlüssel
auf SHA-256, Mengengrenze je Konto, IP-Grenzwerte für NAT, Onboarding mit
Notfallblatt) · Support-Rolle, Admin-TOTP, Audit-Protokoll,
Ankündigungsbanner, Fehlerprotokoll-Sicht, Health-Endpunkt ·
Betriebslage-Dashboard im festen Minimalumfang samt Geräteverteilung (R42)
· Rückbau der zentralen Stammdaten (R39, **Backlog Nr. 168**) — die Bestandsaufnahme dazu liegt vor (`docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md`, 208 Befunde), S9 hat die Tür mit Web 18.0.0 bereits geschlossen · Servicemodell (R33) ·
Admin-Optionen für Support-Adresse (R31), Rechtstexte (R32) und die
S2-Sicherungseinstellungen · Mengenbremse `ingest.php` (R19,
Grundsatzfrage zuerst) · CSP mit HSTS, `frame-ancestors`, `nosniff` (Nr. 8)
· Torwächter für ausstehende Migrationen (R40.4) — er setzt den
**Wartungsmodus aus Paket W** (`server/wartung.lock`, Web 13.2.0)
automatisch, zwei Auslöser, ein Mechanismus. **Davor:**
Hosting-Entscheidung (R36), Staging-Ziel; mit P5-Beginn endet der
Autodeploy auf Produktiv — **Aufbau der Auslieferungskette nach R67**:
Staging automatisch, Prüftor Stufen 1 und 2, Umgebung „produktion" mit
Freigabe- und Backup-Tor. **Backlog:** 8, 17, 37, 48, 49, 54, 67. **Demo-
Konto** in jeder Betriebsart mitdenken (R25).

**Wo das alles liegt — Vorgabe aus S8** (E-S8-12, 05.09.2026; P5 erfindet
keine neuen Orte, sondern ergänzt Karten auf vorhandenen Seiten):

| Kommt in P5 | Seite |
|---|---|
| Support-Adresse (R31), Rechtstexte (R32), Betriebsart der Registrierung und Einwilligungen mit Fassung (R37), Ankündigungsbanner (R38) | Verwaltung → **Installation** |
| Konto-Lebenszyklus, Kontostatus, Selbstlöschung (R37) | Verwaltung → **Kontoseite** und **NutzerInnen** |
| Support-Rolle (R38) — vierter Rollenwert neben `user`, `admin`, `betreiberin` (R75) | Verwaltung → **NutzerInnen** (Rollenauswahl) |
| Admin-TOTP (R38) | Einstellungen → **Profil**, Karte „Sicherheit" (nur Admin und BetreiberIn) |
| Audit-Protokoll, Fehlerprotokoll-Sicht, Health-Endpunkt (R38) | Betrieb → **Status** (Health, Fehlerprotokoll); Audit als eigene Seite oder Unterseite von Status |
| Betriebslage-Dashboard, Geräteverteilung nach R64-Herkunft (R38, R42, Nr. 80) | Betrieb → **Statistik** — die Seite entsteht in S8 (AP4) mit Modellen und Nutzung; Status bleibt reine Ampel |
| Torwächter für ausstehende Migrationen (R40.4, R66) | Betrieb → **Updates** |
| Mengenbremse `ingest.php`, IP-Grenzwerte (R19, R37) | Betrieb → **Servereinstellungen** |
| S2-Sicherungseinstellungen | bereits verortet (R77): **Konto-Backups** und **Servereinstellungen** |

### Schritt 11 — Planung v1.0 · **Festlegungen entschieden 03.09.2026**

**Ziel:** Bevor etwas als v1.0 veröffentlicht wird, noch einmal planen
statt schneiden (R59, Beschluss vom 02.09.2026). Der Schritt wurde am
03.09.2026 **vorgezogen** und in `docs/konzepte/Konzept-Planung-v1.0.md`
Punkt für Punkt entschieden: **Store-Verteilung** — R65 (E-PV-1) ·
**Update-Weg der Installation ab v1.0** (R60) — entschieden als **R66**
(E-PV-2): keine Selbstprüfung, kein Selbst-Update, Produktion nur auf
Handauslösung, nur ausstehende Migrationen sichtbar; fest steht weiterhin:
ab v1.0 **keine Rückwärtskompatibilität**, auch nicht bei Updates, v1.0
beginnt mit dem Neuaufsetzen (R40), eine ältere Sicherung wird genau
**einmal** über ein Wegwerf-Formular eingespielt · **Auslieferungskette**
nach R40 — **R67** (E-PV-3) · **Aufteilung in Repositorien** — **R68**
(E-PV-4): eines, frisch, öffentlich, `gen-em/nadoku` · **Code-Review**
(R17) — **R69** (E-PV-5): alles, in Stücken, Eingang von P6, zwei Wege für
Funde · **Web-App auf Android** (Nr. 87) — **R70** (E-PV-6): Manifest
allein, in P7 · **Phasenschnitt** — **R71** (E-PV-7): P6, P7, P8 · die
**Doku-Anforderungen** nach R16 — **R72** (E-PV-8) · die
**Problemsammlung** als Schritt 8 — **R73** (E-PV-9).

Die Haltbarkeit der Gerätestatistik (Nr. 83), mit Fassung 21 hierher
gelegt, ist mit Fassung 22 als **R64** entschieden (Momentaufnahme am
Einsatz, Umsetzung im S4-Rest) und steht hier nicht mehr an.

**Ergebnis:** je Phase ein Konzept nach K1 mit Paketschnitt und
Abnahmekriterien (P6 nach der Freigaberunde des Reviews, R69); bis dahin
beginnt kein Paket der Phase. **Modell:** Fable (R14).

### Schritt 12 — P6 Review und Bereinigung

**Ziel:** sauberer Code, Verhalten unverändert außer bei Funden.
**Inhalt:** Eingangsschritt **Bug- und Sicherheitsreview mit Fable (R17,
Umfang und Form nach R69)** — alles, in zwölf Stücken; Stück 1 ist das
**Bedrohungsmodell** als eigener Abschnitt; gesucht werden Bugs,
Sicherheitslücken, ungebrauchter Code, Karteileichen und Probleme,
einschließlich Verschlüsselungsverfahren, Containerfassung 4, SPUR1,
Komplettbackup und Serverschlüssel, Demo-Konstruktion (R25), Schlüsselablage
auf dem Handy, S5-Kopplungsweg und Adress-QR, Umgang mit Dumps und
Klartext-Koordinaten (R41, Nr. 43 — **Weg B ist entschieden, R78; der
Review prüft die Skizze SP-9 und die Fragen aus Nr. 146**),
Signaturschlüssel bei Google (R65), Geheimnisse der Kette (R67), die
Antwort auf den Notizfeld-Zielkonflikt aus S9 (Nr. 109) ·
**Kommentardurchgang:** keine Verweise auf Beschlüsse, Backlog-Nummern,
Fassungen oder Konzepte mehr im Code — Kommentare normalisieren (R13) und
Namensdurchgang (R31) gehen darin auf · **Freigaberunde:** der
Auftraggeber entscheidet je Fund; danach der **Paketschnitt** ·
**Sofortpaket** für Kritisches vor allem anderen · **Pflicht- und
Aufräumpakete** (je Codebasis gebündelt) für alles andere — v1.0 wird nicht
erklärt, solange ein Fund offen ist · R5-Ausnahmeliste beschließen
(zugeliefert: leer). **Voraussetzung:** die drei Fragen aus
`Konzept-V1-Ortsdaten.md` beantwortet (R78); P5 und S9 gemergt. **Abnahme:**
Review-Dokument vollständig (zwölf Stücke, jeder Fund entschieden),
Sofort- und Pflichtpakete abgenommen, Prüfmittel unverändert grün,
Wortliste 0/0/0.

### Schritt 12a — S11 Ortsdaten verschlüsselt (Weg B)

**Ziel:** Der Einsatzort ist nicht mehr aus der Datenbank rekonstruierbar.
**Inhalt (R78, Nr. 43 und 53):** ein **Konto-Schlüsselpaar** (ECDH P-256),
privater Teil unter dem Inhaltsschlüssel gehüllt, öffentlicher Teil ans
Gerät; Uhr und Handy verschlüsseln **Spur, Phasenkoordinaten,
Reanimationsereignisse und Zielklinik** vor dem Upload (Garmin: ECDH,
AES-256-CBC, HMAC-SHA256 ab Connect IQ 3.0.0 — geprüft 06.09.2026; kein
GCM); `seq` und Zeitstempel bleiben Klartext, damit Reihenfolge,
Nachlieferung und Phasenzuordnung serverseitig bleiben; ein verlorenes
Gerät kann nichts entschlüsseln, ein Passwortwechsel berührt nichts.
**Preis:** Ausdünnung Stufe 3, serverseitiger GPX-Abruf, Schneiden,
Verschieben, Ortshöhe und Zusammenführung wandern in den Browser oder
entfallen; Statistik „Reanimationen je Jahr" und „Fahrten je Klinik" zählt
der Browser; der Klinik-Pin erscheint erst nach dem Entsperren; SPUR2 als
Liste versiegelter Stücke, weiter nur über `spur_lib.php`; Vertrag, Uhr-
und Android-Code, Backup Fassung 4. **Altbestand:** ein **Einmalwerkzeug
im Browser** (dort liegt der Schlüssel), für das eine Konto vor der
Öffnung, danach entfernt — kein dauerhafter Produktweg. **Rang:** Web,
Uhr, Android Haupt. **Konzept** nach K1, Fable. **Voraussetzung:**
Schritt 12; **vor der Öffnung** (Betriebsübergang) — mit mehreren Konten
gilt die Altbestand-Entscheidung nicht mehr.

### Schritt 13 — P7 Gesicht v1.0

**Ziel:** v1.0 sieht aus wie v1.0. **Inhalt:** Umbenennung überall — **die
Wortmarke ist mit Web 15.3.2 vorgezogen worden** (Auftrag vom 05.09.2026):
Kopfleiste, Schublade, Anmeldeseite, Einrichter, Tab-Titel, GPX- und
CSV-Urheber, E-Mail-Absendername und die Titel der Dokumentation heißen
**Gen-EM NAdoku**, wie die Uhr seit 2.0.0. Offen bleibt hier die **Langform
„Gen-EM Einsatzdokumentation Notarzt"** in den Texten der System-E-Mails
(Betreff, Anrede, Grußformel, 20 Stellen in sechs Dateien) — sie ist der
beschreibende Name, nicht die Marke, und wird mit der Doku-Neufassung
entschieden. Dazu neues Demo-Passwort mit dem Produktnamen in der
Schwachwortliste (R25) ·
Vertragsreview und Festschreibung als v1 (R12; Nr. 23) · **Doku-Neufassung
nach R72** (Handbuch, Betreiberhandbuch, Installation und Selbsthosting,
Technik; Screenshots erzeugt, Sprungmarken; das Handbuch als statisches
HTML in der Kette nach `server/hilfe/` gerendert und mit dem Release
ausgeliefert, Link „Hilfe" in Fußzeile und Anmeldeseite) ·
**Web-App-Manifest** (Nr. 87, R70: Manifest allein, „NAdoku Web", Symbole
für Web-App und Handy-App aus dem Entwurf) · Changelog neu ab v1.0 (R15) ·
Backlog mit dauerhaften Nummern übernehmen · Altformat der Sicherung
abschaffen (Nr. 46) · Kommentarregel in `CLAUDE.md` („Grund ja, Nummer
nein", R69). **Konzept** nach K1, Paketschnitt dort. **Abnahme:** kein
„Einsatzdoku" mehr außer im Archiv und Changelog; Vertrag v1
festgeschrieben; Handbuch-Screenshots aus dem Bilderlauf; Manifest am S24
(Chrome, Samsung Internet, Firefox) und am iPhone nachgewiesen; Wortliste
0/0/0. Für „kein ‚Einsatzdoku' mehr" gilt: **Die Wortmarke ist erledigt** —
in `server/` steht der alte Name nur noch dort, wo `version.php` erzählt, was
wann hieß. Zu entscheiden bleibt allein die E-Mail-Langform.

### Schritt 14 — P8 Schnitt

**Ziel:** die frische Installation, das frische Repositorium, Version 1.0.
**Inhalt:** **Neuaufsetzen** (R40 (3)): frische Installation, Übernahme des
Bestandskontos per edbak über das Wegwerf-Formular (R60), Demo-Konto nach
Runbook, Probe des Komplettbackup-Zyklus und des Rollbacks auf Produktiv
(R67) · **Migrationsregister beginnt neu** — die bis dahin gelaufenen
Migrationen gehen in die Grundfassung von `schema.sql` (R66) ·
**Repo-Umzug und Inventur** (R68): Durchsicht von `tools/`, `docs/`,
`.github/`, `CLAUDE.md` — was in den ersten Commit von `gen-em/nadoku`
kommt, mit Begründung je Weglassung; Umgebungen und Zweigschutz;
Altrepositorium archivieren mit Verweis · **Auslieferungskette nach R67 im
neuen Repositorium** (R40 (4)) · Rechts- und Betreiberunterlagen zur
Öffnung (R41; MDR-Abgrenzung bereits vor Welle 1, R65) · **Abnahme nach
R11:** die frische Installation liest die Referenz-edbak aus
`tools/referenzdatensatz/referenz/` · Erklärung v1.0: Tags `web-v1.0.0`,
`uhr-v…`, `android-v1.0.0`; danach der Betriebsübergang, Welle 1 (R65).

### Betriebsübergang (nach v1.0, keine Phase)

Öffnung in Wellen über die Betriebsarten (R41) · **Produktionsfreigabe im
Play Store mit Welle 1** (R65; setzt Mengenbremse und Mengengrenze aus P5,
die MDR-Abgrenzung und die Rechtsunterlagen nach R41 voraus — der interne
Test-Track läuft seit Schritt 6) · **mit Welle 1 entfällt die
Seitenladung**: Karte „NAdoku für Android", `apk.php`, Handbuch 10.1 und die
Deploy-Ausnahme `apk/` (R65) · Verteilung der Garmin-Uhr über den
Connect-IQ-Store (R41) · halbjährliche Probe-Wiederherstellung.

## 4. Parallelität und Sperren

**Faustregel:** Ein Paket, das nur `android/` oder nur `watch/` anfasst,
kann immer laufen. Alles, was `server/`, `docs/JSON-Vertrag.md`,
`schema.sql` oder `update.php` schreibt, wartet auf das Paket davor.
Gemeinsam ist immer die Buchführung (`version.php`, `CHANGELOG.md`,
`Backlog.md`) — dort sind Konflikte mechanisch, aber die Migrationsliste
und die Backlog-Nummern verlangen die Gegenproben aus Abschnitt 2.2.

| jetzt parallel möglich | nicht parallel |
|---|---|
| S4-Merge und Backlog-Runde (verschiedene Punkte, eigene Zweige) | S6 zu S4 (`schema.sql`, `update.php`, Vertrag) — S6 erst nach dem Merge |
| S5-Konzept und S7 (Konzept schreibt keinen Code) | S7 zu S4 (`einstellungen.php`, `admin_user.php`, Handbuch, Technik) — S7 erst nach dem Merge |
| Konzeptarbeit P5 (Hosting, Gespräche) zu allem | ~~S5-Umsetzung zu S6 und S7~~ — **entfällt, S5 ist gemergt** (Fassung 25) |
| Wear-OS-Gerätetest zu allem | ~~S4-Rest zu **Paket E**~~ — **erfüllt** (E am 03.09., S4-Rest am 04.09. gemergt, Fassung 32). Die alte Sperre „S4-Rest zu S5“ war schon erfüllt: Vertragsabschnitt 1a steht |
| — | Backlog 21 (43 Restfunde quer durch `server/`) zu jedem laufenden `server/`-Paket |
| — | ~~S8 zu S4-Rest und S7~~ — **erfüllt** (beide gemergt, Fassung 27); S8 läuft seit 05.09.2026 |
| S9-Konzept zu Schritt 9a (das Konzept schreibt keinen Code) | ~~S8 und S9~~ — **erfüllt** (S8 gemergt, Fassung 32). ~~**S9-Umsetzung zu 9a**~~ — **aufgehoben 07.09.2026:** Nr. 137 und 132 sind in S9, die gemeinsamen Dateien gehören S9 allein; beide laufen parallel, nur die Buchführung zieht nach (K7) |
| S9 zu S11: `store => 'pat'` (E-S9-01) ist der Katalogschlüssel, den S11 für die Zielklinik benutzt — S11 baut darauf auf, nicht daneben | — |
| Korrekturstufe Nr. 148/149 zu 9a (`index.php`, `betrieb_updates.php`, `migration_lib.php` gegen die Sicherheitsdateien; Buchführung mechanisch) | — die Korrekturstufe ist klein und geht **zuerst** auf `main` |

**Merge-Reihenfolge auf `main`:** ein Push je Paket nach Freigabe (K7);
nach jeder Migration `update.php`.

## 5. Zuordnung der offenen Backlog-Punkte

Jeder offene Punkt steht genau einmal. Nummern 63–67 sind für den
S4-Zweig reserviert (dort heute 59–63); 68–79 sind mit Fassung 16, 80–83 mit
Fassung 21 und 84–88 mit Fassung 22 angelegt; **89–92 kamen aus S7 und S5/C,
93–97 mit Fassung 25 aus S5** (Pakete A, W und der Vorbereitung); **98–113
mit Fassung 26** (98–100 aus der Planung v1.0, 101–113 die Problemsammlung
für S9); **114–116 aus S5 Paket E** (gemergt 03.09.2026);
**117–126 mit Fassung 28** aus S8 (Konzept und Umsetzung);
**127–146 mit Fassung 30** aus dem Krypto-Review (R78); **147–149 mit
Fassung 32** (Spur im Kartendialog für S9; zwei Funde am Produktivstand
nach dem S8-Deploy); **150 und 151 mit Fassung 33** aus der Korrekturstufe (Cron-Befehl, Import-Verweis), **152 mit Fassung 34** (Standortseiten, S9)
Web 15.5.2 — 148 und 149 sind damit erledigt und stehen nicht mehr in
dieser Tabelle. Mit Fassung 32 sind die dreizehn erledigten Nummern aus
der Tabelle genommen (63, 66, 73–75, 78, 79, 82–86, 98 — S4-Rest und S8) und die
sechs fehlenden offenen ergänzt (90–92 aus S5/C, 114–116 aus Paket E);
Nr. 115 ist in Nr. 95 aufgegangen. **153–161 sind in 9a und seinen
Gegenprüfungen entstanden, 162–170 in S9** (162 in AP3, 163–166 in AP5,
167 bei der Bestandsaufnahme zu R39, 168 mit R39, 169 in AP6, 170 nach
Web 19.1.0).

**Mit Fassung 41 ist die Tabelle wieder deckungsgleich mit dem Backlog.**
Sie war es nicht mehr: **24 Zeilen** nannten Punkte, die längst erledigt
sind (S9 und 9a — 68, 70, 72, 101–107, 110, 127–138, 147, dazu Nr. 19),
und **neun offene Punkte fehlten ganz** (153–158, 161, 169, 170). Dabei
sind drei Backlog-Punkte aufgefallen, die ihre Erledigung im eigenen Text
trugen, aber weiter unter *Offen* standen: **Nr. 19** (mit P3 gegenstandslos
geworden), **Nr. 159** (Uhr 3.1.0) und **Nr. 160** (Android 0.15.0) — alle
drei jetzt verschoben. Gezählt wird das seither maschinell: Die Zahl der
Zeilen hier und die Zahl der offenen Punkte im Backlog müssen gleich sein,
heute **72 = 72**.

> Die drei aus Paket E standen dort zunächst als 90–92 und mussten beim
> Zusammenführen mit Fassung 26 weichen: 89–92 waren schon an S7 und S5/C
> vergeben. Wer auf einem Zweig eine Nummer vergibt, ohne den Stand des
> Rahmenplans zu kennen, vergibt sie zweimal — die Nummer steht hier, nicht
> im Zweig.

| Nr. | Punkt (kurz) | gehört zu | Bemerkung |
|---|---|---|---|
| 8 | Content-Security-Policy | P5 | mit HSTS, `frame-ancestors`, `nosniff`; **Bauplan SP-5** (Nonce, Report-Only zuerst) in `Vorbereitung-Sicherheitspaket.md` (R78) |
| 17 | Mengenbremse `ingest.php` | P5 | Grundsatzfrage zuerst (R19); Messung liegt |
| 21 | 43 A4-Restfunde sichten (mit 18) | Backlog-Runde | Felder mit Vertrags- oder Uhrberührung nur nach Vertragsabgleich (R21) |
| 23 | Vertrag nennt Reanimationsart `beginn`, die keiner annimmt | P7 | mit dem Vertragsreview (R12, R71) |
| 36 | Prüfmittel: Klassennamen, die nur JavaScript sucht | Backlog-Runde | Prüfmittel |
| 37 | Konto, das über Jahre wächst | P5 | S2 hat die Mengen beantwortet; Rest sind Speichergrenzen je Konto (R37.10) |
| 40 | 55 Altklassen der Streichliste austragen | Backlog-Runde | vor dem nächsten CSS-Umbau |
| 41 | Sechs Klassen ohne Regel | Backlog-Runde | Gestaltungsentscheidung mit Mockup |
| 42 | Drei Unicode-Symbole im Markup | Backlog-Runde | Gestaltungsentscheidung |
| 43 | GPS-Spur und Phasenkoordinaten im Klartext | P6 (Weg B) und Backlog-Runde (Weg C) | Vorstudie `docs/konzepte/Konzept-V1-Ortsdaten.md`: Weg C (Zusage in `CLAUDE.md` 4, Technik und Datenschutztext ehrlich eingrenzen) jetzt; Weg B (Schlüssel auf das Gerät) entscheidet der R17-Review (R69, Stück 2; eigene R-Nummer nach der Freigaberunde); drei Fragen an den Auftraggeber in Abschnitt 6 — spätestens mit dem Abschluss von P5; die Frage hängt mit dem Notizfeld aus S9 zusammen (Nr. 109) |
| 45 | Dritte Kartengröße | nach v1.0 | ohne Mockup, ohne Bedarf |
| 46 | Altformat der Sicherung abschaffen | P7 | Stichtag NaDoku 1.0 (R71) |
| 47 | Natives `confirm()` fernhalten | Backlog-Runde | Prüfmittel |
| 48 | Aufbewahrung je Konto | P5 | Admin-Optionen |
| 49 | Aufbewahrung auf dem Sicherungsziel | P5 | Admin-Optionen, Entscheidung |
| 50 | Versand liest je Konto ein Verzeichnis | nach v1.0 | erst messen |
| 51 | Suche verarbeitet 5 000 für 200 | nach v1.0 | Zielzahl gehalten (3,81 s) |
| 52 | WebDAV als Sicherungsziel | nach v1.0 | Bedarf abwarten |
| 53 | Konto-Schlüsselpaar für versiegelte Serversicherungen | nach v1.0 | R17 darf es ansehen |
| 54 | Migrationslauf nach Wiederherstellung | P5 | mit dem Wartungsmodus-Torwächter (R40.4) |
| 55 | Komplettsicherung ohne scharfen Schnappschuss | nach v1.0 | — |
| 57 | Tagesübersicht baut ihre Tabelle zweimal | Backlog-Runde | Vereinheitlichung |
| 58 | Prüfmittel: Seite ohne Gerüst | Backlog-Runde | Prüfmittel, ein Nachmittag |
| 80 | Auswertung der Gerätestatistik (Rest von 59) | **P5** (Rest) | Gerätemodelle und Nutzung sind **gebaut** (S8 AP4, Betrieb → Statistik). Offen: **Herkunft je Einsatz (R64-Werte) und Betriebslage-Dashboard**, mit der Datenschutzerklärung als Vorbedingung (Abschnitt 6); die NutzerInnen-Sicht ist Nr. 88 |
| 81 | App-Symbol in der Benachrichtigung zu groß und angeschnitten | **S4-Rest** | am Gerät gemeldet; aus dem heutigen Quellstand nicht nachvollziehbar — zuerst die installierte App-Fassung klären |
| 87 | Weboberfläche als installierbare Web-App auf Android | P7 (R70) | Manifest allein, kein Service Worker; Name „NAdoku Web", eigenes Symbol; Erhebung erledigt (R70) |
| 88 | Kachel „Einsätze je Gerät" in der Zeitraumübersicht der NutzerIn | Backlog-Runde, nach S4-Rest | neue Darstellung: Mockup und Freigabe (R64) |
| 90 | Der Simulator kann keinen Verbindungsabriss herstellen | nach v1.0 | aus S5/C; Prüfmittel der Uhr, nur am Gerät nachweisbar (Prüfliste S5) |
| 91 | Auswahl in `WatchUi.Confirmation` im Bildabzug nicht sichtbar | Backlog-Runde | aus S5/C; Prüfmittel `tools/uhr-bilder/` |
| 92 | `pruefstand.sh bildreihe` fotografiert nur den Startbildschirm | Backlog-Runde | aus S5/C; Tastenfolge als Parameter |
| 62 | Logodateien mit alten Farbwerten | Backlog-Runde | `Design.md` 2.5 mitziehen |
| 65 | 14 Fassungshinweise, AGP 9 | Backlog-Runde | eigene Runde nach dem S4-Rest, nur `android/` |
| 67 | `csrf_check()` ohne API-Zweig | P5 | CSRF-Umfeld (R21) |
| 71 | Regionen mit Unteradmins | nach v1.0 | verworfen, festgehalten (R39) |
| 76 | Demo-Reset läuft alle 30 Minuten, auch ohne Änderung | Backlog-Runde | erst messen (Laufzeit, Last), dann entscheiden |
| 77 | Wartungsseite `update.php` in Unterseiten aufteilen | **P6** (Rest) | **Konzept liegt vor** (E-S8-05): die Seite wird **aufgelöst**; der Block Betrieb trägt Status, Statistik, Updates, Hintergrundjobs, Servereinstellungen, Komplett-Backup und Backup-Ziele. Wartungsmodus **und** ausstehende Migrationen liegen zusammen auf „Updates" (R66: nur Ausstehende mit „Ausstehende ausführen", ausgeführte bis P5 eingeklappt, danach im Audit-Protokoll); `update.php` wird Weiterleitung bis P6. AP2 und AP4 |
| 94 | „bitgleich" gegen „pixelgleich" in `tools/uhr-bilder/` | Backlog-Runde | aus S5 (V-S5-05); ein Wort — oder `-define png:exclude-chunk=time` |
| 95 | Die Android-Rundlauffälle lassen Daten im Admin-Konto zurück | Backlog-Runde (Android) | 9 Diensttage, 5 Einsätze, 14 439 Punkte; Aufräumen im `@After` oder eigenes Prüfkonto; **Nr. 115 (Paket E) sagte dasselbe und ist hier aufgegangen** (Fassung 32); Schritt 6 hat es nicht mitgenommen |
| 96 | Eigene Wartungsmeldung auf Uhr und Handy, `Retry-After` auswerten | nach v1.0 | aus S5/W (E-S5W-08); heute behandeln die Clients das 503 als gewöhnliches 5xx, und das genügt |
| 99 | Fassungsprüfung auf Klick der Administratorin (GitHub-Releases) | nach v1.0 | R66, Option A2; nur wenn Selbsthoster es verlangen; kein Hintergrundlauf |
| 100 | Play-API-Upload aus der Auslieferungskette | nach v1.0 | R67; Upload-Schlüssel als GitHub-Secret plus Dienstkonto, wenn die Releases häufiger werden; E-S4-16 dann ergänzen |
| 114 | Abgewiesene Pakete sichtbar machen und ausräumen | **Backlog-Runde** | Räumteil (30 Tage, beim Trennen) **erledigt mit Android 0.14.0** (R78); der Bedienweg zum Sichtbarmachen bleibt |
| 116 | Kontrastwerkzeug misst nur seine Paarliste | Backlog-Runde | Android-Prüfmittel `android/werkzeuge/kontraste.py`; Paare aus dem Code ableiten |
| 117 | Kein Vermerk, ob eine NutzerIn je ein Backup gezogen hat (B-S8-07) | Backlog-Runde | Spalte an `users`, Zeile auf der Kontoseite |
| 121 | Vorschau der Rechtstexte beim Tippen (Mockup 09) | Backlog-Runde | heute nur der gespeicherte Stand |
| 122 | Freie Zeiträume und Diagramme in der Statistik (Mockup 04) | P5 / Backlog-Runde | Diagrammbibliothek müsste vendoriert werden |
| 124 | Aktionsblatt öffnet weit weg von seinem Knopf | Backlog-Runde | mit Bild gemeldet, Tagesübersicht am Handy |
| 125 | `.form-raster` und `.zweispalter` sind dieselbe Regel | P6 / Backlog-Runde | eine behalten, die andere austragen |
| 139 | Adminpakete unversiegelt, `ftp` (K-4) | S10 | `sk_versiegeln()`; `Backup-Format.md` 5 neu |
| 140 | Push auf `main` ist Deploy (K-16) | Zuarbeit / S10 | Branch-Schutz und 2FA sofort; Deploy-Tor mit Staging (R40 (2)); Integritätswache im Sofortpaket (F-SP-9) |
| 141 | Zweitfaktor für alle Konten (K-5) | P5 | erweitert R38 |
| 146 | Fragen an das Bedrohungsmodell (Argon2id, `CryptoKey`, Passkeys/PRF) | P6 | R17 Stück 1; dazu Skizze SP-9 |
| 150 | Cron-Befehl für `jobs.php` mit dem Repositoriumspfad dokumentiert | Backlog-Runde | vier Stellen (`server/jobs.php` 13, `Technik.md` 2424 und 5220, `CHANGELOG.md` 6210); der Deploy legt den Inhalt von `server/` nach `httpdocs/` — abgetippt ergibt das „Could not open input file". Die Karte „Auslöser" ist **nicht** betroffen (baut über `__DIR__`). Zwei Fragen offen: ob der Changelog angefasst wird, und welche Schreibweise künftig gilt |
| 168 | **Zentrale Stammdaten vollständig zurückbauen** (R39) | **P5** | S9 hat nur die Tür geschlossen (Weg c, kein Schema). Was bleibt, steht mit Fundstelle in `docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md` — **208 Befunde**, davon 23 am Schema. Vorbedingung des `ALTER TABLE`: **0 Zeilen mit `user_id IS NULL`** in allen sechs Tabellen |
| 154 | Handy-App liest `kept_points` und `kept_meta` nicht | nächste Android-Stufe | aus der Gegenprüfung des Sofortpakets (Nr. 134); `Sendeantwort.kt` nimmt nur `kept_phases` und `kept_resus` in den Sendebericht |
| 157 | Handy-App: Sackgasse zwischen „Schlüssel abgewiesen" und „Gerät trennen" | nächste Android-Stufe | aus dem Emulatorlauf zu Android 0.14.1; dieselbe Bauart wie Nr. 159 auf der Uhr, die mit Uhr 3.1.0 behoben ist |
| 173 | Die Umlaufprüfungen führen tote Regeln | Backlog-Runde | 3 bzw. 2 ungenutzte Ausnahmeregeln nach dem Neubau des Referenzbestands (vorher 0); sie beschreiben den Übergang aus S9/AP7, den es nicht mehr gibt. Beide Kreisläufe bleiben bei 0 unerklärten Abweichungen |
| 172 | Eine Erwartung der Wartungsprobe flackert | Backlog-Runde | Erwartung 15 vergleicht zwei Einzelmessungen ohne Spielraum (beide rund 71 ms); gemessen 0/1/0 nicht erfüllte in drei Läufen. Eine Probe, die grundlos rot wird, wird nicht mehr gelesen |
| 158 | `days` trägt kein `created_at` | Backlog-Runde | Migration; führt `ingest_tag_offen()` auf einen Spaltenwert zurück statt auf eine Abfrage über zwei Tabellen. Keine Fehlerbehebung, eine Vereinfachung |
| 161 | Aus einer Aufzeichnung ein Stück löschen können | Backlog-Runde, gemeinsam mit Nr. 43 | aus Nr. 160: Ein vergessener Dienst zeichnet den Heimweg mit auf, und heute geht nur alles oder nichts |
| 169 | Ein Diensttag mit „Anderem Rettungsmittel" kann keine Besatzung festhalten | **Entscheidung zuerst**, danach Backlog-Runde oder P5 | freigelegt in S9/AP6 (Web 18.1.1). Drei Wege stehen im Backlog, heute gilt (c) „so lassen und im Text sagen"; Hinweis und Handbuch sagen es zutreffend. Die Entscheidung steht **auch in Abschnitt 6** |
| 170 | Kein Prüfmittel misst, ob die Kennzeichnung vollständig ist | Backlog-Runde | aus Web 19.1.1: AP7 zählte, **wie viele** Schlösser stehen, und konnte deshalb nicht sehen, dass zwei fehlen. Sollmaß wäre der Feldkatalog (`'store' => 'pat'` bzw. `'hinweis'`) |

## 6. Offene Abnahmen und Zuarbeiten

Was der Auftraggeber tun oder liefern muss. Gestrichen nach R55: die
P0-Bedienprüfung und die P2-Prüfliste bis auf Punkt 4.1.

| Was | Wofür | Wann |
|---|---|---|
| ~~**`update.php` aufrufen — VIER Migrationen**~~ | Schritt 5, Prüfliste S5 Punkt 1 | **erledigt 04.09.2026, 23:15** — alle vier und `2026_09_04_herkunft_geraet` verbucht; `2026_09_05_rolle_betreiberin` am 06.09.2026 per phpMyAdmin (Nr. 149) |
| ~~**Entscheidung zur Reihenfolge 9a / S9**~~ | Schritt 8 / 9a | **entschieden 07.09.2026: Nr. 137 und 132 ganz nach S9**, beide Schritte parallel; S9-Umsetzung beauftragt |
| **Nach dem Merge von Web 15.5.2: einmal Betrieb → Updates öffnen und „Ausstehende ausführen" drücken** — die Migration steht dort seit 15.5.2 unter *Ausstehend* mit der Plakette „nicht nötig"; der Knopf verbucht `2026_09_05_rolle_betreiberin` als `skipped`, und der Zähler „1" an Updates und Status verschwindet. **Der Menüzähler hängt bis zu 60 Sekunden nach** (Zwischenspeicher) — das ist kein Fehlschlag. Danach die NutzerInnen-Liste ansehen: jedes frühere Admin-Konto heißt „BetreiberIn" (Prüfliste S8, P-02). Bedienweg und Fehlerbilder: `docs/konzepte/Pruefdokument-Korrektur-148-149.md`, Punkt 1 | Nr. 149, Prüfliste S8 | **fällig** — 15.5.2 ist seit dem 06.09.2026 auf `main` (PR #36) |
| **Nach dem Merge von S9: `update.php` aufrufen** — AP2 bringt die Migration `2026_09_07_adresssuche_konto` (Spalte `users.adresssuche`, Kontoschalter der Adresssuche). Bis dahin gilt für jedes Konto die Vorgabe „an", und die Anwendung läuft weiter — beide Leser vertragen die fehlende Spalte, **belegt ist das aber nur durch Lesen des Codes**, nicht durch Messen (Prüfdokument S9, Abschnitt 0 und Prüfliste Punkt 8). **Mitgemeint sind die beiden anderen:** `rest_segments.created_at` aus 9a (PR #37) und `2026_09_07_rettungsmittel_typ` aus AP4 — ein Aufruf verbucht alle drei | S9/AP2, 9a | **fällig** — S9 ist seit dem 10.09.2026 auf `main` (PR #38/#39), 9a seit dem 08.09.2026 (PR #37) |
| **Auf einem Diensttag mit Überschneidungswarnung den Knopf „Diensttage zusammenführen" drücken** — er führte bis Web 15.5.1 auf eine 404-Seite (Nr. 148). Erwartet: die Seite „Diensttag aufnehmen" mit dem geöffneten Tag in der Unterzeile. Punkt 2 desselben Prüfdokuments | Nr. 148 | **fällig** — 15.5.2 ist seit dem 06.09.2026 auf `main` (PR #36) |
| **Nach dem Merge von S9: die Prüfliste im Prüfdokument abarbeiten (32 Punkte)** — darunter drei, die auf dem Prüfstand **nicht** hergestellt werden konnten und deshalb ausdrücklich benannt sind: ein Konto **ohne Schlüsselhülle** (Punkt 31 — dort ist das Notizfeld gesperrt wie jedes geschützte Feld), der **Anhebelauf über mehr als eine Runde** (mehr als 200 Einsätze mit Klartext) und **zwei Browser gleichzeitig** gegen die Wache je Zeile. Dazu Punkt 32: nach dem Deploy einmal anmelden und entsperren, danach je Konto `SELECT COUNT(*) FROM missions WHERE notes IS NOT NULL AND notes <> ''` zählen — für jedes entsperrte Konto **0**; Konten, die sich nie entsperren, behalten ihren Klartext, und das ist kein Fehler | S9-Abnahme | **fällig** — seit dem 10.09.2026 (PR #38/#39) |
| **Freigabe des S9-Abschlusses** — danach löscht K9 das Konzept (`Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md`); das Prüfdokument bleibt, bis seine Prüfliste abgehakt ist. **Ebenfalls zu löschen:** `docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md` erst nach P5, sie wird dort gebraucht | Schritt 8 | — |
| **Prüfliste S5 (12 Punkte)**, darunter: die Bestandsuhr **einmal neu koppeln** (E-S5-42, vorher den Sync leerlaufen lassen), beide Kopplungsmails im Postfach sichten, Antwortgleichheit auf Produktiv nachmessen, die Geräteseite **ohne JavaScript**, drei Punkte nur am Gerät (Verbindungsabriss, Tastensperre, Oberfläche auf zwei Geräteklassen), **ein Update mit Wartungsmodus** und **eine Kopplung mit dem Handbuch in der Hand** (P2-Punkt 4.1) | S5-Abnahme | nach `update.php` |
| **Freigabe des S5-Abschlusses** — danach löscht R62 die beiden Konzepte (`Konzept-S5-Kopplung-umgekehrt.md`, `…-Zusatz-Wartungsmodus.md`); das Prüfdokument bleibt bis zur abgehakten Prüfliste | Schritt 5 | — |
| ~~**Merge von Paket E**~~ | Schritt 5 / Schritt 6 | **erledigt 03.09.2026** (PR #31); der S4-Rest folgte am 04.09.2026 (PR #33) |
| Neues NEF-Logo und -Favicon | P3, Logo-Wahl (Platzhalter liegt) | vor P7 (R71) |
| Impressums- und Datenschutztext der Installation über den Editor eintragen | P3 (R32) | vor P7; für das Datensicherheitsformular der Play Console schon vor dem ersten Track-Release (R65); Datenschutztext dann mit der Grenze der E2E (Nr. 43, Weg C) |
| Sichtprüfung in WebKit und Firefox (Symbole am Dateiverweis) | P3-Abnahme | gelegentlich |
| Prüfliste S2 (12 Punkte), darunter **die Probe-Wiederherstellung der ganzen Installation** auf einem Wegwerf-Webspace | S2-Abnahme, danach halbjährlich | wichtigster offener Punkt; blockiert nichts |
| Zugangsdaten je eines echten FTP-, FTPS- und SFTP-Ziels; ein Klick auf „Verbindung prüfen" | S2 Sicherungsziele | — |
| Bestätigung, dass SMTP auf Produktiv eingerichtet ist | S2 Warnmails | — |
| Bilderlauf für die zweite Logo-Wahl; Autosuche gegen den echten Photon; Bedienzustände | S3-Reste | gelegentlich |
| Prüfliste S4 (1, 2, 3, 5) am echten Diensttag | Schritt 1 | nach dem Merge |
| ~~**Adresse der Connect-IQ-Gerätedateien (`CIQ_GERAETE_URL`)**~~ — **geliefert am 02.09.2026.** `server/geraetemodelle.php` trägt jetzt 325 Teilenummern auf 173 Modelle (Web 12.9.1/12.9.2). Die Adresse selbst steht weiterhin **nicht** im Repositorium — sie liegt seit dem 03.09.2026 in den **Umgebungsvariablen der Arbeitsumgebung**, nicht in einer Datei. Jede neue Sitzung findet sie dort von selbst; eine laufende erbt sie nicht nach, weil Umgebungsvariablen beim Start des Containers hereinkommen | Schritt 2 (S6) | **erledigt** |
| **Abnahme S6:** je eine Kopplung mit Garmin-Uhr und Handy-App (zeigt die Liste Art und Modell?), dazu eine Sitzung über 30 Minuten mit Bedienung (kein Dialog) und ein Leerlauf darüber (Abmeldung) | Schritt 2 (S6) | `update.php` ist gelaufen — Abnahme offen |
| **Datenschutzerklärung um die Gerätekennung ergänzen** — seit Web 12.9.0 wird beim Koppeln Art und Modell erhoben; Backlog Nr. 80 macht die Nennung zur Vorbedingung der Auswertung. Der Text entsteht nach R60 aus einer Bestandsaufnahme des gesamten Projekts | Schritt 11, vor v1.0 | vor jeder Auswertung (P5) |
| **Datenschutzerklärung: die beiden Kopplungs-Mails nennen.** Aufgefallen bei der Play-Console-Vorbereitung (Schritt 6, Teil C) und hier vermerkt, damit es beim Schreiben des Textes nicht durchrutscht: `pair.php` verschickt **nach jeder erfolgreichen Kopplung** eine Nachricht an die Kontoadresse — mit **Gerätebezeichnung** (Hersteller und Modell), Zeitpunkt und dem Weg, das Gerät wieder zu entfernen — und **beim Trennen** eine zweite. Das ist eine Verarbeitung personenbezogener Daten (Kontoadresse) mit einer Geräteangabe darin; sie geschieht auf dem Server, nicht in der App, und taucht deshalb im Datensicherheitsformular der Play Console **nicht** auf. In der Datenschutzerklärung gehört sie genannt. Die Mails sind gewollt und sollen bleiben: Sie sind die einzige Stelle, an der eine unbemerkte Fremdkopplung auffiele | Schritt 11, vor v1.0 | mit dem Datenschutztext |
| **Signaturschlüssel des APK verwahren** — erzeugt am 31.08.2026 (RSA 4096, Zertifikat `078c…ad64`, gültig bis 2056), am 02.09.2026 an den Auftraggeber übergeben; er lag bis dahin nur im Ablagefach der Arbeitssitzung; **wird nach R65 als App-Signaturschlüssel bei Play App Signing hochgeladen** — dazu kommt ein Upload-Schlüssel, gleich verwahrt | Schritt 6 und jede spätere Auslieferung | **sofort** — ohne genau diesen Schlüssel ist jede spätere Fassung für Android eine andere App |
| Data Layer Uhr↔Handy auf **echter Hardware** — zwischen zwei Emulatoren nachweislich nicht prüfbar (die Wear-OS-Companion-App des Telefons ist im Baucontainer nicht zu beschaffen) | Schritt 6 | mit der Wear-OS-Uhr |
| Dienst-Test mit der Handy-App auf dem S24 (zwei bis drei Runden) | Schritt 6 | nach dem ersten APK |
| Wear-OS-Uhr für den Gerätetest — jetzt auch für die Wear-OS-Prüfrunde und den Installationstest aus dem Track (R65) | Schritt 6 | vor dem ersten Uhr-Release |
| **DNS-Eintrag und TLS für `nadoku.gen-em.org`** — die Uhr trägt die Adresse seit Uhr 3.0.0 als **Vorgabewert** (E-R49-8). Ohne DNS und Zertifikat läuft jede frisch aufgesetzte Uhr ins Leere, und zwar ohne dass sie sagen kann, warum | Schritt 5 | **fällig — die Uhr ist ausgeliefert** |
| ~~Freigabe des S5-Konzepts~~ | Schritt 3 | **erledigt 03.09.2026** — Umsetzung ist gebaut und gemergt |
| ~~Drei Fragen aus `Konzept-V1-Ortsdaten.md` (Schutzbedarf der Spur; Passwortwechsel bei nicht synchronisierten Uhr-Daten; Stichtag oder rückwirkend)~~ | Nr. 43, P6 | **beantwortet 06.09.2026 (R78):** Spur, Phasen, Reanimation und Zielklinik sind schutzbedürftig und werden verschlüsselt; der Passwortwechsel berührt das Konto-Schlüsselpaar nicht; Altbestand per Einmalwerkzeug vor der Öffnung |
| **GitHub: Branch-Schutz auf `main`** (Pull Request und Review Pflicht, keine Umgehung für Admins, keine Force-Pushes) und **2FA-Zwang in der Organisation** — das Repositorium ist öffentlich, beides kostet nichts (Nr. 140, SP-4) | Schritt 9a | sofort |
| ~~**Entscheidung F-SP-4** — Umfang des Photon-Schalters~~ (Nr. 137) | Schritt 9a | **entschieden 06.09.2026: (a)** — Hinweis, Datenschutztext, Schalter je Installation mit Vorgabe „an" |
| ~~**Entscheidung F-SP-8** — Zahl des Ersetzfensters der Uhr~~ (Nr. 134) | Schritt 9a | **entschieden 06.09.2026: 72 h** ab Einsatzbeginn |
| ~~**Entscheidung F-SP-9** — Integritätswache~~ (Nr. 140) | Schritt 9a | **entschieden 06.09.2026: (a)** — sofort, im Sofortpaket |
| **Datenschutzerklärung: Photon (`photon.komoot.io`) und die vier Kachelanbieter nennen**, dazu die Grenze der Verschlüsselung nach Weg C (Nr. 137, 138; R41) | Schritt 9a | mit dem Text |
| **Passwort des eigenen Kontos prüfen** — mindestens zwölf Zeichen oder eine Passphrase, nirgends wiederverwendet; der Server kann es nicht prüfen (Nr. 136) | Krypto-Review | sofort |
| **Schlüsselblatt der Installation ablegen** (Betriebsakte und Passwortmanager), sobald S10 es druckt; danach Ablageort in der Betriebsakte vermerken | Schritt 9b | nach S10 |
| ~~Freigabe des S8-Konzepts und seiner Mockups; darin die Entscheidung zur Bedienhöhe am Schreibtisch (Nr. 74)~~ | Schritt 7 | **erledigt 05.09.2026** — E-S8-01 bis -18 bestätigt, Mockups 01 und 03–12 freigegeben (02 verworfen), Bedienhöhe als zwei Stufen entschieden (R76) |
| **Play-Store-Beitrittslink des internen Tests** — für die Karte „App installieren" auf der Geräte-Seite (S8 AP6). Ohne ihn steht dort die Zeile ohne Knopf; die Adresse ist danach an **einer** Stelle nachzutragen (Konstante `PLAY_TEST_URL`) | Schritt 7 (S8 AP6), R65 | vor der Produktionsfreigabe |
| **Adresse der Uhr-App im Connect-IQ-Store**, falls sie dort veröffentlicht ist — dieselbe Karte, dieselbe Mechanik (Konstante `CONNECT_IQ_URL`) | Schritt 7 (S8 AP6) | wenn die Uhr-App im Store steht |
| **Prüfliste des S8-Prüfdokuments abarbeiten** — Bedienwege, die keine Maschine fahren kann: die Migration auf dem Produktivserver (Rollen vorher/nachher), der Kopieren-Knopf in einem **zweiten Browser**, Mengen und Laufzeiten an echten Daten (Status, Statistik, Speichermessung), der Fall „Freigabe läuft, Zielkonto gelöscht" | Schritt 7 (S8) | nach dem Ausrollen; danach wird auch das Prüfdokument gelöscht (R62) |
| Hosting-Entscheidung (Cron/SSH, DB-Kontingent, `max_user_connections`, DDoS-Schutz, Verschlüsselung at rest) | P5-Konzept | vor Schritt 10 |
| Staging-Installation samt FTP-Zugang; **samt Demo-Konto, Referenzdatensatz und Messstand-Konto — Staging ist die Prüfumgebung (R67)** | P5-Beginn | vor Schritt 10 |
| GitHub-Umgebung „produktion" mit Pflichtfreigabe (Betreiberin) und den FTPS-Zugangsdaten der Produktion als Umgebungsgeheimnisse; GitHub-App auf dem Handy mit Push-Nachrichten; prüfen, ob `CIQ_GERAETE_URL` als CI-Secret taugt (Stufe 1) | R67, Freigabe-Tor | mit dem Aufbau der Kette in P5 |
| SPF/DKIM/DMARC der Versanddomain, Bounce-Postfach | P5 | vor der P5-Abnahme |
| Nutzungsbedingungen, AVV, Datenschutzerklärung des Dienstes, ggf. mit rechtlicher Prüfung | Öffnung (R41) | vor der ersten Welle |
| ~~Planungsgespräch v1.0~~ — Festlegungen entschieden als R65–R73 (`docs/konzepte/Konzept-Planung-v1.0.md`); Paketschnitte je Phase (R71) | Schritt 11 | **erledigt 03.09.2026** |
| ~~Anforderungsgespräch Doku-Neufassung~~ | P7 | **erledigt 03.09.2026 (R72)** |
| **D-U-N-S-Nummer für die Gen-EM GbR** bei Dun & Bradstreet beantragen (kostenlos, bis zu vier Wochen); dabei klären, ob die GbR als eGbR im Gesellschaftsregister steht — sonst Gesellschaftsvertrag oder Gewerbeanmeldung bereithalten | R65, Play-Console-Organisationskonto | **sofort** — längster Vorlauf im Programm |
| Google-Konto der GbR als Kontoinhaber (keine private Adresse), Play-Console-Organisationskonto anlegen (25 USD), Identitätsprüfung; Entwicklername und öffentliche Kontaktadresse festlegen | R65 | nach D-U-N-S, vor Schritt 6 |
| Vorhandenen Signaturschlüssel bei Play App Signing hochladen, Upload-Schlüssel erzeugen und außerhalb des Repositoriums verwahren | R65, Schritt 6 | mit dem ersten Track-Release |
| Demo-Video des Vordergrunddienstes (Dauer-GPS) **auf echtem Gerät** für die Standort-Deklaration; wer es dreht, ist zu klären | R65, Schritt 6 | vor dem ersten Track-Release, falls der interne Track die Deklaration verlangt (beim Einrichten prüfen) |
| Datensicherheitsformular der Play Console — setzt die Datenschutzerklärung voraus | R65 | vor dem ersten Release, das es verlangt |
| **MDR-Abgrenzung nach R41 vorziehen:** vor der Produktionsfreigabe (Welle 1), nicht erst in P8; für den internen Track nach heutiger Einschätzung nicht nötig — beim Einrichten prüfen | R41, R65 | vor Welle 1 |
| GitHub: `gen-em/nadoku` anlegen (öffentlich, AGPL-3.0), Umgebungen `staging` und `produktion` mit Pflichtfreigabe, Zweigschutz für `main`; nach dem Umzug `gen-em/einsatzdoku-luftrettung` archivieren | R68, P8 | mit dem Umzug in P8 |
| Fable-Instanz mit Repositoriumszugriff (Claude Code) für den Review in zwölf Sitzungen; `docs/konzepte/Review-R17.md` als Sammelstelle | R17, R69 | Eingang von P6 |
| Wahl der Symbole für Handy-App und Web-App (gleicher Hubschrauber, zwei Hintergrundfarben, GPS-Nadel / Browser-Marke) aus dem Entwurf im P7-Konzept; iPhone für den Safari-Nachweis | R70, P7 | mit dem P7-Konzept |
| Drei repräsentative Uhr-Darstellungen benennen (welche Bildschirme) und Handy-Screenshots aus dem Gerätetest mit dem Demo-Konto | R72, P7 | mit dem P7-Konzept |
| Betriebsakte der eigenen Installation ausfüllen (Hoster, Domain, Mail, Aufsichtsbehörde, zweiter Admin, Ablageort des Wiederanlaufpakets, Play Console) — außerhalb des Repositoriums | R41, R72 | vor der Öffnung |
| ~~F3–F6 zu PS-3~~ | S9, Nr. 103 | **beantwortet 06.09.2026** — zwei Screenshots in `docs/konzepte/vorbereitung-s9/`, alle Lagen (vor allem Desktop und Handy), keine Uhrzeiten in der Anzeige, kein zweites Merkmal, aber Trennlinie zwischen den Farben; PS-3 meint die Kartenschilder (Schritt 8) |
| Wellenplan der Öffnung | Betriebsübergang | vor der Öffnung |
| **Prüfliste S7** (`docs/konzepte/Pruefdokument-S7-Backup-Begriff.md`), sechs offene Punkte. Der wichtigste ist **Nummer 4: ein Komplett-Backup aus der Zeit VOR diesem Deploy einspielen** — die Kopfzeile des Dumps ist zugleich Erkennungsmarke, und ob die Vorsorge am echten Bestand trägt, lässt sich nur dort sehen. Dazu die beiden Warnmails (SMTP), die Bedienzustände der Dialoge, der Wiederanlaufweg in einer leeren Datenbank und ein Backup-Umlauf in dasselbe Konto | Schritt 4 (S7) | nach dem Deploy |
| **Das geplante Komplett-Backup einmal im Betrieb sehen** — Plan auf „täglich", einen Tag warten, danach steht auf der Wartungsseite ein Zeitpunkt und keine Fehlerzeile. Erster Betriebsnachweis für Backlog Nr. 89: Dieser Job lief von Web 12.2.0 bis 12.9.2 nie | Schritt 4 (S7) | nach dem Deploy |
| **Entscheidung zu Backlog Nr. 169** — ein Diensttag mit „Anderem Rettungsmittel" kann **keine Besatzung** festhalten, weder am Tag noch am Einsatz (freigelegt in S9/AP6). Drei Wege stehen im Backlog: **(a)** der Adhoc-Dialog bekommt Rollenhaken wie das Stammdatenformular — ehrlich, aber er wächst um sieben Felder; **(b)** der Tag bietet die Rollen an, die zur Betriebsart passen — billig, aber geraten, und E26 sagt: geraten wird nicht; **(c)** so lassen und im Text sagen — wer die Besatzung braucht, legt das Rettungsmittel an. **Heute gilt (c)**, Hinweis und Handbuch sagen es seit Web 18.1.1 zutreffend. Ohne Entscheidung bleibt es dabei, und das ist ein tragfähiger Zustand — die Zeile steht hier, damit er ein gewählter bleibt und kein vergessener | Nr. 169, S9/AP6 | wenn es stört |
| Freigabe je Konzept und je F-Entscheidung | alle | laufend |

## 7. Programmentscheidungen — Register

R1 bis R50 im Volltext: `docs/Rahmenplan-Archiv.md`, Abschnitt 3. Neue
Entscheidungen werden hier kompakt angehängt; die Begründung steht im
betroffenen Konzept oder, wenn es keins gibt, im Archiv-Anhang. Nummern
werden nie neu vergeben.

| Nr. | Kern | Status |
|---|---|---|
| R1 | Rahmenplan plus Phasenkonzepte statt eines Großdokuments | gilt; seit F16 mit Archiv (R51) |
| R2 | Phasenfolge P0 → … → P6 mit Zwischenpaketen | überholt durch Abschnitt 3 |
| R3 | Luftbegriffe nur ersetzen, wo sie Allgemeines meinen; Luftfahrt-Fachfelder bleiben | gilt; Wortliste in Konzept P2, 5; Prüfmittel R28 |
| R4 | Referenzdatensatz wird generiert, über reguläre Wege eingespielt | erledigt (P1: 16 Diensttage, 87 Einsätze) |
| R5 | Gespeicherte Namen bleiben; Ausnahmeliste in P7 beschließen (R71) | gilt; Liste zugeliefert und leer |
| R6 | Backlog-Zuordnung (alt) | überholt durch Abschnitt 5 |
| R7 | Ordnerumbau vor P3 | gegenstandslos (E-A6-12) |
| R8 | Gründerfarben präsenter | erledigt in P3 (`Design.md`) |
| R9 | Registrierung in drei Betriebsarten plus Sicherheitspaket | gilt, P5 (konkretisiert in R37) |
| R10 | Rollen- und Sichtbarkeitsmodell, auch was der Admin nicht kann | gilt, P5 (R38) |
| R11 | Kein Migrationspfad; v1.0 liest die 7.x-edbak; Referenzdatei liegt | gilt; Abnahme in P8 (R71); seit R60 als einmaliges Einspielen über ein Wegwerf-Formular |
| R12 | Weitere Clients: Basisfähigkeit, Vertragsreview in P7 (R71) | gilt; Payloads und Texte erledigt. **Abschnitt 1a in zwei Stufen:** S6 hat ihn auf den heutigen Stand gebracht (Fassung 1.4 — beide Kopplungsformen, was der Server davon speichert, Präfixe der Android-Apps); **S5 schreibt ihn nach E-R49-7 neu**, weil sich der Kopplungsweg selbst umkehrt. Wer 1a liest, liest bis dahin die S6-Fassung |
| R13 | Versionshistorische Kommentare am v1.0-Schnitt ersetzen | gilt, P6 — im Kommentardurchgang des R17-Reviews (R69) |
| R14 | Konzepte mit Fable, mechanische Pflege ohne | gilt |
| R15 | Changelog ab v1.0 als Stichpunkte | gilt, P7 (R71) |
| R16 | Doku-Neufassung zu v1.0 mit Screenshots; Anforderungsgespräch vorher | gilt; Anforderungen R72, Umsetzung P7 |
| R17 | Bug- und Sicherheitsreview mit Fable vor v1.0 | gilt, Eingang von P6; Umfang und Form nach **R69** |
| R18 | Konzept im Projektraum, Umsetzung in Claude Code | gilt |
| R19 | Mengenbremse `ingest.php`: Grundsatzfrage und vier Randbedingungen; Messung liegt | gilt, P5 |
| R20 | Sofortpaket Nr. 22 (Altersfeld maskieren) | erledigt (Web 7.2.1) |
| R21 | Backlog-Zuordnung nach P0 | überholt durch Abschnitt 5 (csrf_check ist Nr. 67) |
| R22 | Papierkorb in beiden Sicherungen | erledigt (S1, Web 8.0.0) |
| R23 | Zwischenpaket S1 | erledigt |
| R24 | Regressionspflicht: beide Kreisläufe je Phase, 0 unerklärt | gilt, dauerhaft |
| R25 | Demo-Konto dauerhaft, einzige E2E-Ausnahme; auf der Kontoseite gesperrt | gilt; P5, P6 (Review prüft die Konstruktion) und P7 (neues Demo-Passwort mit der Umbenennung) führen es mit |
| R26 | Backlog-Zuordnung nach P1 | überholt durch Abschnitt 5 |
| R27 | Prüfmittel Wiederherstellungsprobe und Papierkorb-Mischfall | gilt, dauerhaft |
| R28 | Prüfmittel Wortliste | gilt, dauerhaft |
| R29 | Uhr-Umbenennung in P6 | erledigt vorzeitig (R48, Uhr 2.0.0) |
| R30 | Nacharbeit zu P2 statt Backlog | erledigt |
| R31 | Support-Adresse konfigurierbar (P5), Namensbeispiele raus (P6, im Review R69), Farbnamen bleiben | gilt |
| R32 | Impressum und Datenschutz als editierbare Seiten | erledigt in P3; Felder in die Admin-Optionen (P5) |
| R33 | Servicemodell mit Abonnements | gilt, P5 |
| R34 | Zwischenpaket S2 | erledigt |
| R35 | Prüfmittel Messstand | gilt, dauerhaft |
| R36 | Zielbild Dienstbetrieb, keine Telemetrie, Hosting-Entscheidung vor P5 | gilt |
| R37 | Konto-Lebenszyklus und Registrierungs-Sicherheitspaket (elf Punkte) | gilt, P5 |
| R38 | Support-Rolle, Admin-TOTP, Audit, Dashboard im Minimalumfang | gilt, P5 |
| R39 | Zentrale Stammdaten entfallen; Regionen-Modell verworfen | gilt, P5; Regionen als Nr. 71 festgehalten. **Zweistufig seit 09.09.2026:** In der laufenden Anlage sind alle zentralen Standorte gelöscht (Auskunft des Auftraggebers); **S9 hat die Tür geschlossen** (Web 18.0.0, AP5b: `admin_stammdaten.php` ersatzlos gestrichen, Karte „Vordefinierte Standorte“ und `ub_toggle` mit ihr — kein Schema, keine Migration), **P5 baut zurück** (Nr. 168). Bestandsaufnahme mit 208 Befunden: `docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md` — sie bleibt bis P5 liegen |
| R40 | Deploy-Umbau: Staging ab P5, Neuaufsetzen am P8-Schnitt, CI-Prüftor, Torwächter | gilt; (1) läuft, (2) ab P5-Beginn, (3) und (4) in P8 — (4) präzisiert durch R67 |
| R41 | Recht und Betreiberorganisation vor der Öffnung; Öffnung in Wellen | gilt; Prüfung in P8 (R71); MDR-Abgrenzung vor Welle 1 (R65); Betreiberhandbuch generisch mit Notfall-FAQ, Zugänge in der Betriebsakte außerhalb des Repositoriums (R72) |
| R42 | Gerätekennung beim Koppeln | Uhr-Seite erledigt (1.9.0); **Speicherung erledigt (Web 12.9.0, S6)** — drei Spalten statt zwei, begründet im Changelog; Auswertung P5 (Backlog 80) |
| R43 | Zwischenpaket S3 | erledigt |
| R44 | Inhaltsschlüssel führt eine Inaktivitätsfrist wie die Sitzung | **erledigt (Web 12.9.0, S6)** — als Aufräumen, nicht als Behebung des Dialogs (E-S6-4): Der Fristablauf kostete ein stilles Neu-Entpacken, keinen Dialog. Der Dialog kommt vom tabweisen `sessionStorage`, bleibt und steht jetzt im Handbuch |
| R45 | Zwischenpaket S4 mit E-R45-1 bis E-R45-13 | Schritt 1 gemergt, Schritt 6 Teile A–C gemergt (04.09.2026); offen Gerätetest und Android 1.0.0 (E-R45-7); **E-R45-6 ersetzt durch R65** |
| R46 | Keine Apple Watch; P7 entfällt | gilt |
| R47 | Garmin-Uhr-Auslieferung vorgezogen | erledigt (Uhr 1.10.1 bis 1.11.1, Web 9.15.0) |
| R48 | Uhr heißt NAdoku, echte Anwendungs-ID | erledigt (Uhr 2.0.0) |
| R49 | Zwischenpaket S5 „Kopplung umgekehrt" mit E-R49-1 bis E-R49-8 | gilt, Schritte 3 und 5 |
| R50 | „Sicherung" wird „Backup", in einem Zug, nach S3 | gilt, **S7**; Grenzen in der Vorlage |
| R51 | Rahmenplan in zwei Dateien: Steuerung und wörtliches Archiv | gilt (F16) |
| R52 | Kennungen bleiben; S6 und S7 für die beiden R-Pakete; der Fahrplan trägt die Reihenfolge | gilt (F16) |
| R53 | P4 aufgelöst; Reste als Backlog-Runde ohne Konzept | gilt (F16) |
| R54 | R-Einträge nur als Kurzregister, Volltext im Archiv | gilt (F16) |
| R55 | P0-Bedienprüfung und P2-Prüfliste überholt; P2-Punkt 4.1 geht in S5 | gilt (F16) |
| R56 | S7: Verb „sichern", Symbolname und `admin_sicherungen.php` bleiben | gilt (F16) |
| R57 | Überlappende aktive Diensttage: Hinweis im Browser (F-S4-D, Weg c) | **umgesetzt** (Web 13.3.0, Handbuch 4.5b); der Knopf der Warnung führt auf 404 — Nr. 148 |
| R58 | Android-Bedienhöhe 48 dp in beiden Modulen; `CLAUDE.md` 5 ergänzen | gilt, S4-Merge |
| R59 | Vor v1.0 ein Planungsgespräch: Umfang des Code-Reviews, Aufteilung in mehrere Repositorien, Auslieferungskette; Ergebnis sind die Konzepte P6–P8 (R71) | vorgezogen und entschieden: R65–R73 (Fassung 26); Schritt 11 |
| R60 | Ab v1.0 keine Rückwärtskompatibilität, auch bei Updates; v1.0 beginnt mit dem Neuaufsetzen; eine ältere Sicherung wird einmal über ein Wegwerf-Formular eingespielt, danach nie wieder. Der Update-Weg der Installation (Selbstprüfung gegen das Repositorium, Benachrichtigung, Einspielen selbst oder per FTP, Sichtbarkeit der Migrationsliste) ist mit **R66** entschieden | gilt; Update-Weg entschieden (R66) |
| R61 | Zwischenpaket S8 „Einstellungen, Administration und Wartung": ergebnisoffene Sichtung und Neuordnung vor P5, mit Konzept und Mockups; die Sicherungsoptionen, die Menüstruktur und die Aufteilung der Wartungsseite gehören hinein | gilt, Schritt 7 |
| R62 | Konzeptablage `docs/konzepte/` mit Lebenszyklus: Statusblock und Push nach jedem Arbeitspaket, damit andere Instanzen den Stand sehen; nach Freigabe des Abschlusses Erledigt-Zeile hier und Löschung des Konzepts; Prüfdokument bleibt bis zur abgehakten Prüfliste; Bestand bis S3 in `docs/konzepte/erledigt/` | gilt (F16), Regel in 2.2 |
| R63 | Die Android-App kennt nur `nadoku.gen-em.org`, fest und nicht änderbar: Adressfeld, Adress-QR (E-S4-15) und Adresswahl entfallen, Selbsthoster bauen ein eigenes APK. E-R45-2 und E-R49-8 gelten für Android insoweit nicht mehr; die Garmin-Uhr behält Vorgabewert und Einstellung. Dazu: Handy-App heißt „Gen-EM NAdoku", Wear-OS-Uhr bleibt „NAdoku" | **erledigt** (Android 0.11.0, Nr. 84–86) |
| R64 | **Herkunft und Gerät je Einsatz** (Beschluss 02.09.2026 zu Nr. 83, Weg b): **(1)** Geräteart und Modell werden beim Anlegen als Momentaufnahme an `missions` und `rest_segments` kopiert, in die Sicherung aufgenommen, der Bestand per Migration aus `devices` nachgefüllt; Trennen bleibt Löschen (R47). **(2)** `origin` bekommt eigene Werte: `watch` bleibt für die Garmin-Uhr, neu `android`, `wear` und `schnitt` neben `manual` und `import`, gesetzt beim Anlegen aus Geräteart und `client_ref`-Präfix; Feldkatalog, Export- und Backup-Format, Kreisläufe (R24) und Referenz ziehen nach. **(3)** Sichtbar im Betriebslage-Dashboard je Installation (Nr. 80, P5) **und** je NutzerIn als Kachel der Zeitraumübersicht (Nr. 88). Keine neue Erhebung über R42 hinaus — dieselben Werte, festgehalten; die Datenschutzerklärung nennt es (R41, Abschnitt 6) | gilt; **Speicherung erledigt** (Web 14.0.0, Konzept R64, Nutzlast 9), Dashboard P5, Kachel Nr. 88 |
| R65 | **Store-Verteilung in zwei Stufen** (Beschluss 03.09.2026, E-PV-1; ersetzt E-R45-6): Play-Console-Organisationskonto der Gen-EM GbR (D-U-N-S); **interner Test-Track ab Schritt 6** als Regelweg für den bekannten Kreis, Handy und Uhr unter einem Eintrag; **Produktionsfreigabe erst als Welle 1** des Betriebsübergangs (R41), nach P5 und MDR-Abgrenzung; Versionscode je Modul mit Versatz (E-S4-02 bleibt eine Zählung, Nr. 98); vorhandener Signaturschlüssel wird App-Signaturschlüssel bei Play App Signing, getrennter Upload-Schlüssel — der Schlüssel liegt danach auch bei Google (R17); Seitenladung bleibt bis zur Produktionsfreigabe und entfällt mit Welle 1; Connect IQ unverändert. Begründung in `docs/konzepte/Konzept-Planung-v1.0.md`, E-PV-1 | gilt; Konto sofort, Track in Schritt 6, Produktion Betriebsübergang |
| R66 | **Update-Weg ab v1.0** (Beschluss 03.09.2026, E-PV-2; beantwortet R60): keine Selbstprüfung auf neue Fassungen, kein Selbst-Update — die Installation ändert ihren Code nie selbst, neuer Code kommt nur über die Auslieferungskette (R40, R67); **Produktion nur auf ausdrückliche Auslösung der Betreiberin, nie automatisch**, damit vorher Backups geprüft werden können; Wartungsseite zeigt nur ausstehende Migrationen mit „Ausstehende ausführen", der Torwächter liest dasselbe Register, ausgeführte Kennungen ab P5 im Audit-Protokoll; Migrationsregister beginnt bei v1.0 neu. Selbsthoster: Release-Archiv, FTP, Migrationen von Hand (Betreiberhandbuch). Fassungsprüfung auf Klick als Nr. 99 nach v1.0. Begründung E-PV-2 | gilt; Unterseite in S8 (Nr. 77), Audit in P5, Neubeginn des Registers in P8 |
| R67 | **Auslieferungskette** (Beschluss 03.09.2026, E-PV-3; präzisiert R40 (4)): `main` deployt automatisch auf Staging, das zugleich Prüfumgebung ist (Demo-Konto, Referenzdatensatz, Messstand-Konto); ein Release-Tag startet den Produktiv-Lauf, der in der GitHub-Umgebung „produktion" auf die **Freigabe der Betreiberin** wartet — die Produktiv-Zugangsdaten liegen nur dort; der freigegebene Lauf **stößt zuerst das Komplett-Backup an und bricht ohne Erfolg ab**, dann Deploy, Migrationen von Hand (R66); mit dem Wartungsmodus aus Paket W: Wartung an → Backup → Deploy → Migrationen → Wartung aus; **Rollback** = voriger Tag plus Wiederherstellung; **Prüftor in drei Stufen** (je Push statisch und Android-Build, rot = kein Merge; nach Staging-Deploy Kreisläufe, Bilderlauf, Messstand bei Tags, rot = nicht freigabefähig; Produktion nach Freigabe); Android in der CI unsigniert, Signatur und Play-Upload auf dem Rechner der Betreiberin (E-S4-16 bleibt), Play-API-Upload nach v1.0 (Nr. 100). Pflichtfreigaben setzen ein **öffentliches** Repositorium voraus (R68). Begründung E-PV-3 | gilt; gebaut in P5 (R40 (2)), vollständig ab dem neuen Repositorium (R40 (4)) |
| R68 | **Ein Repositorium, frisch, öffentlich** (Beschluss 03.09.2026, E-PV-4; beantwortet den Repositorien-Teil von R59): v1.0 lebt in **`gen-em/nadoku`** (öffentlich, AGPL-3.0) ohne Git-Historie; `gen-em/einsatzdoku-luftrettung` wird archiviert und verweist weiter. Drei Zählungen bleiben, Tags mit Präfix je Zählung, Pfadfilter in der Kette (R67). `main` nur über Pull-Request mit grüner Stufe 1. Der Umzug ist das letzte P8-Paket **„Repo-Umzug und Inventur"**: Durchsicht von `tools/`, `docs/`, `.github/`, `CLAUDE.md` — was wandert, mit Begründung je Weglassung; `docs/konzepte/erledigt/` bleibt im Archiv. Begründung E-PV-4 | gilt; Umzug in P8, zusammen mit dem Neuaufsetzen |
| R69 | **Umfang und Form des R17-Reviews** (Beschluss 03.09.2026, E-PV-5): der Review liest **alles** — `server/`, `watch/`, `android/`, `tools/`, `.github/`, Doku — **in zwölf Stücken** mit Fable, als Eingang von P6; Stück 1 ist ein **Bedrohungsmodell** als eigener Abschnitt; gesucht werden Bugs, Sicherheitslücken, ungebrauchter Code, Karteileichen, Probleme; dazu der **Kommentardurchgang** — keine Verweise auf Beschlüsse, Backlog-Nummern, Fassungen oder Konzepte mehr im Code (R13 und R31 gehen darin auf). Funde in `docs/konzepte/Review-R17.md`, zwei Wege: **kritisch → Sofortpaket, alles andere → Pflichtpaket in P6**; der Auftraggeber entscheidet je Fund in einer Freigaberunde; der P6-Paketschnitt folgt ihr. Vorbedingungen: Nr. 43-Fragen beantwortet, P5 und S9 gemergt. Begründung E-PV-5 | gilt; Eingang von P6 |
| R70 | **Web-App-Manifest** (Beschluss 03.09.2026, E-PV-6; erledigt die Erhebung zu Nr. 87): die Weboberfläche wird als installierbare Web-App ausgeliefert — **Manifest allein, kein Service Worker** (Chrome auf Android verlangt seit Version 108 keinen; kein Cache, keine alten Dateien), **in P7 mit der Umbenennung**, Name „NAdoku Web", eigenes Symbol (gleicher Hubschrauber wie die Handy-App, andere Hintergrundfarbe, Browser-Marke; der Tracker bekommt eine GPS-Nadel); Entwurf im P7-Konzept. Nachweis am S24 mit Chrome, Samsung Internet und Firefox sowie auf einem iPhone (Safari) — für iPhone-NutzerInnen die einzige App-Form. R44 gilt unverändert. Begründung E-PV-6 | gilt; P7 |
| R71 | **Drei Phasen vor v1.0** (Beschluss 03.09.2026, E-PV-7): **P6 Review und Bereinigung** (Fable-Eingang; Sofort-, Pflicht- und Aufräumpakete, Kommentardurchgang, Weg B) · **P7 Gesicht v1.0** (Umbenennung, Vertrag v1, Doku-Neufassung, Manifest, Changelog, Backlog, Altformat, Kommentarregel) · **P8 Schnitt** (Neuaufsetzen, Register neu, Repo-Umzug mit Inventur, Kette im neuen Repositorium, Rechtsunterlagen, Abnahme R11, Erklärung v1.0). Je Phase ein Konzept nach K1 mit eigenem Paketschnitt; P6 → P7 → P8, nichts parallel. Frühere „P6"-Nennungen sind nach `docs/konzepte/Konzept-Planung-v1.0.md` 6.2.8.5 zugeordnet | gilt; Schritte 12–14 |
| R72 | **Anforderungen an die Doku-Neufassung** (Beschluss 03.09.2026, E-PV-8; beantwortet das Anforderungsgespräch aus R16): vier Dokumente nach Zielgruppe — Handbuch (NutzerIn), Betreiberhandbuch mit Notfall-FAQ und Betriebsakte-Vorlage (generisch, ohne Zugänge), Installation und Selbsthosting, Technik mit Bedrohungsmodell — dazu der Vertrag; Markdown mit Sprungmarken; **das Handbuch reist als statisches HTML mit jedem Release** in die Installation (Link „Hilfe"), nicht von GitHub zur Laufzeit; Screenshots erzeugt (1920×1080, 414×896), Uhr drei Simulatorbilder, Handy aus dem Gerätetest; kurz und prägnant — je Aufgabe ein Bild, Referenz im Anhang, keine Fassungsgeschichte; Abnahmemaß höchstens ein Drittel des heutigen Umfangs. Begründung E-PV-8 | gilt; Umsetzung P7 |
| R79 | **Geocoding abschaltbar je Installation und je Konto, Dienstadresse als Einstellung** (Beschluss 06.09.2026, E-S9-05): Photon bleibt die eine Quelle für Vorschläge, Umkehrsuche und Dialogsuche; Installationsschalter (9a, Nr. 137) als Obergrenze, Kontoschalter unter Profil → „Datenschutz"; die Dienstadresse steht als Servereinstellung, damit ein eigener Geocoder nach der Hosting-Entscheidung (R36) eine Eingabe ist, kein Code. Aus heißt: keine Anfrage — Koordinaten, Plus Codes, „Meine Position" und Karte bleiben | gilt; S9 |
| R78 | **Krypto- und Sicherheitsreview vorgezogen, Befunde entschieden** (Beschlüsse 06.09.2026; Befunde in `docs/konzepte/Review-Krypto-Sicherheit.md`, Vorschläge und Entscheidungen in `docs/konzepte/Vorbereitung-Sicherheitspaket.md`; beide gehen in R17 Stück 1 ein): **(1)** Das Verfahren steht (PBKDF2, zufälliger Inhaltsschlüssel, AES-256-GCM, zwei Hüllen; kein serverseitiger Weg kennt den Schlüssel); die Zusage hält gegen den Datenbankabzug so lange wie das Passwort, nicht gegen einen Angreifer, der Code ausliefert, und nicht für die Klartext-Ortsdaten (Nr. 43). **(2) Sofortpaket Sicherheit** (Schritt 9a): Web Nr. 127–138, Android Nr. 142–145 und 114, Muster R42, kein K1. **(3) S10 — Sicherheit** (Schritt 9b, vor P5, Hauptstufe): Server-Anteil am Datenschlüssel in `config.php`, je Konto per HMAC, nur an die angemeldete Sitzung, per HKDF in den Datenschlüssel; `pat_wrap_rc` unabhängig; Schlüsselblatt mit Kennung, Kennung in `app_state`, Nachtragen-Weg mit Prüfung, Rotation — der Verlust von `config.php` ist damit ein Griff in die Betriebsakte, kein Reset für alle · Adminpakete versiegeln, `ftp` abschaffen (Nr. 139). **(4) Zweitfaktor für alle Konten**, Admins Pflicht (Nr. 141, erweitert R38, P5). **(5) CSP** nach Bauplan SP-5 (Nr. 8, P5). **(6) Weg B entschieden — S11** (Schritt 12a, nach P6, **vor der Öffnung**): Konto-Schlüsselpaar (löst Nr. 53 mit), Umfang Spur, Phasenkoordinaten, Reanimation **und** Zielklinik (kehrt die Klartext-Entscheidung zur Zielklinik in `mission_fields.php` um), Altbestand per Einmalwerkzeug im Browser für das eine Konto, danach entfernt; Weg C sofort (Nr. 138). **(7) Deploy-Tor** erst mit dem Staging-Aufbau (R40 (2)) — bestätigt; Branch-Schutz und 2FA sofort (Nr. 140). **(8)** Am selben Tag entschieden: Photon-Schalter je Installation mit Vorgabe „an" (F-SP-4), Ersetzfenster 72 h ab Einsatzbeginn (F-SP-8), Integritätswache sofort im Sofortpaket (F-SP-9); die P6-Fragen als Nr. 146. Modell: Review und Konzepte Fable, Umsetzung Opus | gilt |
| R73 | **Problemsammlung als S9** (Beschluss 03.09.2026, E-PV-9): Schritt 8 „S9 — Einsatzbearbeitung und Rettungsmittel", Konzept nach K1 mit Fable (Mockups PS-3/PS-5, Zielkonflikt PS-8.2 als Fable-Schritte), Backlog 101–113, Vorbereitung `docs/konzepte/Vorbereitung-S9-Problemsammlung.md`; Konzept nach dem S8-Konzept (Nr. 74), Umsetzung parallel zulässig; P5 setzt S9 nicht voraus, P6 schon; Zielkonflikt PS-8.2 geht in das Bedrohungsmodell ein (Nr. 43, R69); erste Prüffrage Geocoding-Quelle (PS-1). Schritte 8–13 → 9–14 | gilt; Konzept nach Go des Auftraggebers |
| R74 | **Ordnungsprinzip — programmweit** (Beschluss 05.09.2026, E-S8-01): Jede Funktion hat **genau einen Ort**, und der folgt aus drei Fragen, nie aus dem Zeitpunkt des Baus. **(1) Wer** (Zielgruppe) → der Menübereich: Einstellungen (NutzerIn), Verwaltung (Admin), Betrieb (BetreiberIn); ein Bereich ist nur sichtbar, wer ihn benutzen darf. **(2) Woran** (Objekt) → die Seite: Handlungen an einem Diensttag oder Einsatz liegen bei diesem Objekt; Kontoeinstellungen unter Einstellungen; Installationseinstellungen unter Verwaltung (Inhalt, Konten, Texte) oder Betrieb (Server, Speicher, Updates). **(3) Wie oft** (Häufigkeit) → die Ebene auf der Seite: Die Primärfläche trägt nur den Regelweg; **Ausnahmen liegen eine Ebene tiefer** (Aktionsmenü, zugeklappte Karte, Unterseite) und bekommen **nie** einen eigenen Hauptmenüpunkt. Dazu drei Regeln der Darstellung: **(4)** Diagnosekarten erscheinen oben und nur, wenn es etwas zu tun gibt · **(5)** Erklärtext einheitlich als **eine** zugeklappte Karte „Was hier gilt" am Seitenende, sonst Verweis ins Handbuch · **(6)** ein Begriff je Ding, ein Ding je Begriff, über alle Zielgruppen. Und die Regel, die den Wildwuchs künftig verhindert: **(7) Wer eine Funktion baut, benennt ihren Ort** nach 1–6 im Konzept oder im Backlog-Punkt; ein Paket ohne benannten Ort wird nicht gemergt (**K1 ergänzt**). Begründung: Konzept S8, Abschnitte 1.3 und 4 | gilt, dauerhaft; erste Anwendung S8, Vorgabe an S9 (5.7) und P5 (E-S8-12) |
| R75 | **Rolle „BetreiberIn"** (Beschluss 05.09.2026, E-S8-02; Ausnahme zu R38, die Support-Rolle bleibt dort): dritte Rolle `betreiberin` in `users.role`. **Rechte:** BetreiberIn ⊇ Admin ⊇ NutzerIn — die BetreiberIn kann alles, was ein Admin kann, und sieht als Einzige den Block „Betrieb" (Server, Speicher, Updates, Jobs, Komplett-Backup, Backup-Ziele). **Bestand:** die Migration macht **alle** vorhandenen Admins zu BetreiberInnen — niemand verliert Zugriff, Rückstufung von Hand. **Vergabe:** nur eine BetreiberIn vergibt oder entzieht die Rolle; das **letzte** BetreiberIn-Konto lässt sich weder zurückstufen noch löschen; `install.php` legt das erste Konto als BetreiberIn an. Die Rechteänderung geht in das Bedrohungsmodell (R69, P6); Support-Rolle, TOTP und Audit bleiben P5 | gilt; umgesetzt in S8 AP1 |
| R76 | **Bedienhöhe in zwei Stufen** (Beschluss 05.09.2026, E-S8-09; beantwortet Nr. 74): **44 px bleibt die Vorgabe**; für Zeigergeräte (`@media (hover: hover) and (pointer: fine)`, ab 1024 px) gilt eine **dichte Stufe von 36 px** für Knöpfe, Felder, Listenzeilen und Menüeinträge. Begründung: Die häufigste Arbeit — Einsätze nach der Aufzeichnung ausfüllen — ist Formulararbeit am Schreibtisch; 36 px liegt über der Mindestzielgröße von WCAG 2.5.8 (24 px); Touch-Laptops mit Maus als Hauptzeiger bekommen 36, reine Touch-Geräte 44. Kontrast ändert sich nicht (Höhe, keine Farbe). `CLAUDE.md` 5 und `Design.md` tragen beide Stufen, `tools/screenshots/` misst zwei Sollwerte. Die Android-Apps bleiben bei 48 dp (R58) | gilt; umgesetzt in S8 AP7, Voraussetzung für S9 PS-3 |
| R77 | **Drei Backup-Begriffe** (Beschluss 05.09.2026, E-S8-06; beantwortet Nr. 79, setzt R50/R56 fort): **Backup** = die `.edbak`-Datei der NutzerIn (Einstellungen → Backup) · **Konto-Backup** = das Paket je Konto auf dem Server (Verwaltung → Konto-Backups) · **Komplett-Backup** = der Dump der Installation (Betrieb → Komplett-Backup). Dazu **Backup-Ziele** (Versand von Konto-Backups) und **Speicher** (Grenze und Belegung aller drei, Betrieb → Servereinstellungen). Verben: **sichern** für das Erzeugen (R56), **einspielen** für jeden Rückweg in ein Konto — für NutzerIn wie Verwaltung gleich —, **wiederherstellen** nur für die Installation. Kennzahlen und Filter heißen „Konto-Backup überfällig" und „nie Konto-Backup", weil sie genau das messen (B-S8-07). „Admin-Backup" und „Sicherung" als Substantiv sind gestrichen | gilt; umgesetzt in S8 AP2 und AP3 |

## 8. Erledigt — Kurzübersicht

Was gebaut ist, was es gebracht hat, wo es dokumentiert ist. Die
ausführlichen Phasentexte stehen im Archiv, Abschnitt 4.

### P0 — Aufräumen · Web 7.1.0 und 7.2.0 · 23.08.2026
Konzept „Aufräumen vor Mobilumbau" (nicht im Repositorium); Ergebnisse im
Changelog. Toter Code entfernt, Seitenhülle an einer Stelle, Stylesheet
entdoppelt und gegliedert · Befundpakete A4 (toter Code) und A6
(Strukturreview) als Listen, daraus die Nacharbeit N1–N6 · **neues
Prüfmittel `tools/stilvergleich/`** (rechnerischer Nachweis unveränderten
Erscheinungsbilds) · kein Ordnerumbau (E-A6-12). *Reste:* 43 Restfunde als
Nr. 21.

### Sofortpaket Nr. 22 · Web 7.2.1 · 23.08.2026 (R20)
`docs/konzepte/erledigt/Pruefung-Sofortpaket-22.md`. Altersfeld in den Einsatztabellen
maskiert (Skriptausführung über den Import war möglich) · Importpfad
durchgesehen · Keyguard-Einträge geklärt · Dauer-Regressionsfall mit
Angriffswert im Referenzdatensatz.

### P1 — Referenzdatensatz und Demo-Konto · Web 7.2.2 bis 7.3.1 · 23.08.2026
`docs/konzepte/erledigt/Konzept-P1.md`, `…/Pruefdokument-P1.md`; Werkzeuge
und Quelldaten unter `tools/referenzdatensatz/`.
Generierter Datensatz mit **16 Diensttagen und 87 Einsätzen** aus
JSON-Quelldaten, eingespielt über die regulären Wege (526 Ingest-Anfragen)
· **Demo-Konto** mit Anlegen, Zurücksetzen, 30-Minuten-Reset und
Anmelde-Mengenbremse · **Kreislaufvergleich** importieren → exportieren →
vergleichen (CSV und edbak) als Regressionsnetz · Messung des
Uhr-Sendeverhaltens für R19 · drei Anwendungsfehler behoben (CSV-Rückimport
verlor sechs Felder; Einsätze nach Mitternacht 24 h zu früh).

### S1 — Sicherung und Import · Web 8.0.0 · 24.08.2026 (R23)
`docs/konzepte/erledigt/Konzept-S1-Sicherung-Import.md`, `…/Pruefdokument-S1-…md`.
**Papierkorb in NutzerInnen- und Admin-Sicherung** (Nutzlast 7, kommt als
Papierkorb mit frischer Frist zurück) · CSV-Kreislauf auf **0**: mehrzeilige
Notizen und `final`/`ende` überleben den Rückimport · `created_at` wird
mitgeschrieben · eine kaputte Datei kostet ihre Zeile, nicht den Lauf ·
aktiver Einsatz an gelöschtem Diensttag ausgeschlossen · **Prüfmittel
`tools/wiederherstellungs-probe/` und `papierkorb_misch.mjs`** (R27) ·
Backlog 24, 25, 27–35 erledigt.

### P2 — Terminologie · Web 8.0.1 · 24.08.2026
`docs/konzepte/erledigt/Konzept-P2-Terminologie.md`, `…/Pruefdokument-P2-…md`. Wortlaut
Land/Luft neutral in Oberfläche und Dokumentation (sieben Stellen in der
Oberfläche, deutlich mehr in README, Handbuch und Formatbeschreibungen,
darunter Sachfehler) · Kopplungstexte gerätefrei, Garmin-Tastenweg als
Zusatz · **Prüfmittel `tools/wortliste/`** (R28): 53 Treffer vorher, 0
nachher · Nacharbeit mit vier Funden (R30). *Rest:* Punkt 4.1 der Prüfliste
(Kopplung mit Uhr) → S5.

### P3 — Oberflächen-Redesign · Web 9.0.0 bis 9.13.0, Rückmeldungsrunde 9.14.0 · 26.–30.08.2026
`docs/konzepte/erledigt/Konzept-P3-Oberflaeche.md`, `…/Pruefdokument-P3-…md`,
Mockups in `…/konzept-p3/`. Mobil-first-Oberfläche mit Gestaltungsrichtlinie
**`docs/Design.md`** (Token, Skalen, Bausteine; ersetzt `Branding.md`) ·
Symbole aus Tabler Icons, vendoriert · **Fußzeile auf jeder Seite, Impressum
und Datenschutz als editierbare Seiten** (R32) · **Logo-Wahl je Profil**
(Luft, Boden, wechselnd) · Ortsfeld mit Positions- und Kartenwahl,
Standort- und Zielpins · Kachelsätze der Zeitraumübersicht · **Kontoseite
als Drehscheibe der Administration** (NutzerInnen-Liste mit Suche,
Sicherungen je Konto, Sicherungsregeln) · drei Migrationen (Standorte,
Kontoseite, Rechtstexte) · `docs/Lizenzen.md`, Pflegepflichten in
`CLAUDE.md` 9 · **Prüfmittel `tools/vollstaendigkeit/`, `tools/screenshots/`,
`kontrast.py`** · 9.14.0: 14 Rückmeldungen, 4 Fehler behoben. *Reste:*
Nr. 38, 40, 41, 42, 69, 70; Zuarbeiten in Abschnitt 6.

### Uhr-Auslieferung · Uhr 1.8.1 bis 2.0.0, Web 9.15.0 · 30.–31.08.2026 (R42-Uhrseite, R47, R48)
Kein Konzept; Changelog und R47/R48 sind die Spezifikation. Uhr-Code
übersetzt ohne Warnung, strenge Typprüfung sauber (Nr. 13) · **99 statt
drei Geräte**, `geraet`-Block beim Koppeln (1.9.0) · **Logo-Wahl auf der
Uhr** als App-Einstellung (Nr. 60) · Sync-Seite mit drittem Zustand „Nicht
eingerichtet" (Nr. 11) · Launcher-Symbol und Bildmarke in allen Größen
(Nr. 61) · **abfragen → trennen → neu koppeln** mit Server-Anliegen
„trennen", Vertragsabschnitt 1b (Nr. 14, Web 9.15.0) · Beispieldomain und
Kommentare bereinigt (R29) · **Name NAdoku, Einstiegsklasse `NAdokuApp`,
echte Anwendungs-ID** (2.0.0) · **Prüfmittel `tools/uhr-pruefstand/`**
(99 Geräte) und `tools/uhr-bilder/`. Web 9.14.1: sieben Bildverweise
repariert.

### S2 — Mengen, Spurspeicherung und Sicherung · Web 10.0.0 bis 12.2.0 · 31.08.–01.09.2026 (R34)
`docs/konzepte/erledigt/Konzept-S2-Mengen-Spuren-Sicherung.md`, `…/Pruefdokument-S2-…md`.
**Messstand `tools/messstand/`** mit 5 000-Einsätze-Konto (R35) ·
**Spurpunkte als Blob SPUR1** statt Zeilen (62,4 → 3,58 Byte je Punkt),
Verdichtung nach 14 Tagen, Ausdünnung nach sechs Monaten; **alle Zugriffe
über `spur_lib.php`** · **Job-Einstieg `jobs.php`** mit drei Auslösern,
Wartung ohne Vollscan · **GPX-Abruf** je Einsatz, Ruhesegment und Auswahl
(Nr. 3) · **Sicherungscontainer Fassung 4**: mehrteilig, Kern in Fenstern,
versiegeltes Manifest; Altformat bleibt lesbar (R11) · Admin-Sicherung
mehrteilig, Speichergrenze mit Warnschwellen · **Sicherungsziele FTP, FTPS,
SFTP** (phpseclib vendoriert) · Suche mit einmaligem Schlüsselimport ·
**Komplettsicherung der Installation** mit Wiederanlaufweg · vier
Migrationen · Zielzahlen gehalten (Spuren 1,10 MB je 1 000 Einsätze,
Tagesansicht 1,17 s) · zehn Fehler behoben, darunter ein Messfehler des
eigenen Prüfmittels · Backlog 46–55 neu. *Reste:* Prüfliste, Zuarbeiten in
Abschnitt 6.

### Zweite Rückmeldungsrunde · Web 12.2.1 · 01.09.2026
Auswählen-Knopf des Dateifelds mittig am Baustein · Dateiname in den
Abschlussmeldungen von Sicherung und Export · Warnzeichen für den Ton
`warn` · Backlog 56 erledigt; Bedienprüfung nachgeholt (Lehre: wer eine
Prüfung für unmöglich hält, sehe zuerst in `tools/` nach).

### S3 — Oberflächen-Nacharbeit und vertikaler Rhythmus · Web 12.2.2 bis 12.4.2 · 01.–02.09.2026 (R43)
`docs/konzepte/erledigt/Konzept-S3-Oberflaechen-Nacharbeit.md`, `…/Pruefdokument-S3-…md`.
**Anwendungsregel für Abstände** in `Design.md` 6, an Bausteinen umgesetzt
(13 Regeln eingestellt) · Sammelleiste in Kartenform, Knopf rechts ·
Leistenüberschrift größer und kräftiger · Menü fett nur für den aktiven
Punkt · NutzerInnen-Liste zentriert · Logo-Wahl als schlichte Liste,
Platzhalter mit Phantasienamen · **Ortsfeld sucht beim Tippen** · **Filter
der Suche nur bei Bestand**, aus dem Feldkatalog · **Demo-Konto auf der
Kontoseite gesperrt** · Höhe nur bei Luftrettung, beschriftet · Karte ohne
Marker-Beschriftung, **Markerversatz behoben** (51,7 → 0,0 px) · Bodenlogo
vom leeren Rand befreit, Uhr-Kacheln neu gerastert · `tag_spuren.php` mit
Seitengerüst · Backlog 57, 58 neu. *Reste:* Uhr-Kacheln reisen mit S5;
Abschnitt 6.

### S5 — Kopplung umgekehrt · Web 13.0.0 bis 13.2.0, Uhr 3.0.0 · 03.09.2026 (R49)
Konzepte `docs/konzepte/Konzept-S5-Kopplung-umgekehrt.md` und
`…-Zusatz-Wartungsmodus.md` — **stehen noch**, Löschung nach R62 mit der
Freigabe des Abschlusses. Prüfdokument
`docs/konzepte/Pruefdokument-S5-Kopplung-umgekehrt.md` **bleibt**, bis
seine zwölf Punkte abgehakt sind. Letzter Commit vor dem Merge: `4caf1ff`;
auf `main` als `771808c` (PR #28) und `076579b` (PR #29).

**Die Kopplung läuft andersherum.** Bis Uhr 2.0.0 erzeugte das Web einen
Sechs-Zeichen-Code, und die Trägerin tippte ihn **auf dem Uhrendisplay**
ein — die unangenehmste Bedienung der ganzen Anwendung, für eine Uhr
erträglich, für 500 Konten und einen zweiten Client nicht. Jetzt zeigt das
**Gerät** den Code, ein Mensch gibt ihn im Web ein, und das **Gerät
bestätigt** das Konto. Der `WatchUi.TextPicker` ist ersatzlos weg; auf der
Venu 3s war er der einzige Weg, der eine Bildschirmtastatur brauchte.

**Zwei Tore statt eines** (E-R49-5): Die Web-Seite sieht, **wer eingibt**,
die Uhr sieht, **wessen Konto** es wäre. Wer den Code abliest, hat nichts —
er kann am Gerät nichts auslösen (E-R49-3). Wer jemanden dazu bringt, einen
fremden Code einzugeben, bekommt das Ja nicht. Das Bedrohungsmodell steht
als eigener Abschnitt in `Technik.md` 4.99b: **zwölf Angriffe**, jeder mit
dem, was ihn aufhält, und mit dem Restrisiko, wo eines bleibt.

**Schwebende Zugangsdaten statt schwebender Geräte** (E-R49-2): `start`
liefert Kennung und Schlüssel sofort mit — aber in `pair_sessions`, nicht
in `devices`. Bis zum Ja gibt es das Gerät nicht, und `ingest.php` weist
die Daten ab. Deshalb darf der Schlüssel schon im ersten Schritt über die
Leitung: Er ist ohne Bestätigung wertlos.

**Ein Verfahrenswechsel, der nicht im Plan stand** (E-S5-42): Geräte- und
Sitzungsschlüssel liegen jetzt als **SHA-256**, nicht mehr als bcrypt.
bcrypt bremst das Raten eines schwachen Geheimnisses; bei 192 Bit Zufall
bremst es nur den Server — **228 ms je Upload**, und beim Abfragetakt der
neuen Kopplung 27 s je Sitzung. Das Anmeldetoken bleibt bcrypt, weil es
gestrecktes Passwort ist; die Regel steht seither in `db.php` bei
`GERAET_VERGLEICHSWERT`. **Preis, bewusst gezahlt:** Die eine Bestandsuhr
trägt einen bcrypt-Hash, der nie mehr passt, und koppelt einmal neu.

**Der Wartungsmodus kam als Zusatz dazu** (Paket W, Web 13.2.0): ein
Schalter auf der Wartungsseite, der die Installation für alle außer der
Verwaltung mit **503** schließt. Der Unterschied zu einem 500 ist der, auf
den es ankommt — der JSON-Vertrag sagt zu 5xx „später unverändert erneut",
und Uhr wie Handy halten sich daran. **Kein Client wurde dafür geändert.**
Der Zustand ist eine **Datei** (`server/wartung.lock`), keine Zeile in der
Datenbank: Er wird gerade dann gebraucht, wenn die Datenbank umgebaut wird.

**Ein Fehler, der älter ist als S5, und bei der Gegenlesung auffiel**
(Web 13.0.1, Befund B5.3): Der Upsert in `ingest.php` schrieb `ended_at`,
`distance_m` und `ascent_m` bedingungslos aus dem eintreffenden Paket —
genau die drei Spalten, die ein **nicht-finales** Paket nicht trägt. Kam
eines nach dem finalen an, blieb ein abgeschlossener Einsatz **ohne Ende,
ohne Strecke und ohne Anstieg** zurück. Die Antwort lautete „ok".

**Was der Simulator fand und der Code nicht zeigte** (Paket C): BACK auf
einer `WatchUi.Confirmation` ruft `onResponse` **nicht** auf (E-S5-67), und
„→" trägt in den Geräteschriften nicht — es erschiene als leeres Kästchen
(E-S5-63, deshalb „Einstellungen, Geräte"). Eine Gegenlesung in fünf
Dimensionen fand **32 Befunde**, 16 hielten der Widerlegung stand, alle
behoben; darunter eine Antwort ohne Sitzungszuordnung, die fremde
Zugangsdaten gespeichert hätte.

*Prüfzahlen:* Kopplungsprobe **76 Erwartungen, 0 nicht erfüllt** ·
Wartungsprobe **40 / 40** (neu) · Ingestprobe **30 / 30** · Geräteprobe
**39 / 39** · Browser-Rundlauf **25 / 25, 0 Konsolenfehler** · Uhr-Prüfstand
Stufe I **99 übersetzt / 0 fehlgeschlagen / 0 Warnungen / 0 Fehler**,
Stufe II **20 Vertreter / 0 Abstürze** (18 mit `PairView`) ·
Simulator-Rundlauf **5 von 6 Fällen belegt** · Wortliste **0 / 0 / 0** über
**fünf** Bereiche und 164 Dateien (Bereich `e` — `watch/` samt Monkey C —
kam mit diesem Paket dazu, Backlog 66) · Vollständigkeit **278** (272 + 6,
jedes einzeln benannt) · Bilderlauf **40 Bilder** der berührten Seiten,
0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px · Migrationsregister
**41 = 41**, auf Bestand **und** frisch gefahren · Konsistenzlesung K3
**7 → 0**, K4 **4 → 0** · `php -l` 0 Fehler.

*Nicht belegt, und das steht im Prüfdokument an erster Stelle:* der Text
beider Kopplungsmails (kein Mailserver im Prüfstand — nur Wortlaut und
Versandweg), das Verhalten des FTPS-Deploys gegenüber `wartung.lock`
(**der Merge von PR #28 wäre der Nachweis gewesen und lief ohne
Wartungsmodus — die Messung ist auf den nächsten Deploy verschoben**), der
negative Verbindungszweig auf der Uhr (der Simulator zeigt eine tote
Verbindung als HTTP 404), und eine Kopplung mit Handbuch und Uhr in der
Hand (P2-Prüfpunkt 4.1). *Reste:* Prüfliste in Abschnitt 6.

**Paket E — Android-Ortung und Dienstende · Android 0.8.0 bis 0.10.2 ·
03.09.2026, gemergt als PR #31** (Zusatzkonzept
`Konzept-S5-Zusatz-Android-Ortung-Dienstende.md`, Prüfdokument
`Pruefdokument-S5-Paket-E.md`, beide bleiben bis zur Freigabe). Der
**Ortungswächter** misst, was ankommt, statt zu melden, was freigegeben ist
(0.8.0); **Dienstende und Nachsenden** — der Sendelauf lief bis dahin in
einem Prozess ohne Dienst, den Android abräumen durfte, der Diensttag blieb
dann im Web ohne Ende (0.9.0); die **Uhr erfährt den Zustand** mit jeder
Quittung (0.10.0); Phasenliste gekürzt, „Einsatz abschließen" wieder
erreichbar (0.10.1). Bilderlauf für beide Module. *Reste:* Backlog 114
(Räumteil im Sofortpaket, Bedienweg Backlog-Runde), 115 (in 95
aufgegangen), 116.

### S4-Rest, Teile A bis C · Web 13.3.0 bis 14.2.2, Android 0.11.0 bis 0.13.0 · 04.09.2026, gemergt als PR #33 (R57, R63, R64, R65)
Konzepte `Konzept-S4-Handy-Uhr-Client.md` (Abschnitt 13) und
`Konzept-R64-Herkunft-Geraet.md` mit Prüfdokumenten — **bleiben** nach R62,
weil der Schritt erst mit dem Gerätetest abgeschlossen ist (Abschnitt 3).
Erledigt-Zeile nachgetragen mit Fassung 32; der Werdegang steht in
Abschnitt 10, Fassung 27.

**Teil A:** Kopplungsmodul auf Vertragsabschnitt 1a — die App konnte sich
seit Web 13.0.0 nicht mehr koppeln (Android 0.11.0); feste Server-Adresse,
App-Name „Gen-EM NAdoku", Fenster-Insets (R63, Nr. 84–86); das APK um
1,81 MB kleiner, weil Adress-QR, ZXing und die CAMERA-Berechtigung
entfallen · **Überschneidungshinweis** für zeitlich überlappende Diensttage
(R57, Web 13.3.0, Handbuch 4.5b) · Akku-Hinweis und Akkuwarnung bei
25/15/10 % (Nr. 82, Android 0.11.1/0.12.0), Versionscode-Versatz der Uhr
(Nr. 98). **Teil B — R64 und Nr. 63 als eine Formatänderung** (Web
14.0.0–14.2.2): `origin` mit sechs Werten aus dem `client_ref`-Präfix,
Momentaufnahme `geraet_art`/`geraet_modell` an Einsatz und Ruhesegment,
Konto-Backup auf Nutzlast 9, Referenzbestand neu gebaut mit zwei Geräten
über den echten Kopplungsweg. **Teil C:** Play-Console-Vorbereitung ohne
D-U-N-S und Signaturschlüssel (`Vorbereitung-Play-Console.md`, Android
0.13.0). *Drei Funde am Code, alle behoben* (CSV, die die Anwendung selbst
nicht einlesen konnte; GPX-Probe, die eine Datei statt 172 verglich; „kein
Ende" an abgeschlossenen Einsätzen ohne Phase 9).

*Prüfzahlen (Prüfdokument R64):* Kreisläufe `edbak-alt` **287 743
Einzelvergleiche, 0 unerklärt**, `edbak` **287 713, 0 unerklärt** (die 88
aus AP2 ohne eine Ausnahmeregel verschwunden), `csv` 8 965 · Wortliste
**0/0/0** (79 Regeln) · Vollständigkeit **278 → 280**, beide in Kommentaren
· Migrationsregister **42 = 42**. *Reste:* Gerätetest S24 und Wear-OS,
Android 1.0.0, Backlog 81 und 95 (Abschnitt 5 und 6); **Nr. 148** (der
Knopf der R57-Warnung, gefunden am 06.09.2026).

### Uhr-Korrekturen vom Gerät · Uhr 3.0.1 und 3.0.2 · 05.09.2026, gemergt als PR #34
Zwei Meldungen vom Gerät, nicht vom Prüfstand: Die Sync-Seite ließ sich
mit UP nicht verlassen (3.0.1), und nach einem Blick darauf ließ sich mit
START kein Dienst mehr beginnen — `_fremdKey` in `Input.mc` wurde beim
Loslassen nicht aufgeräumt, die App war in ihrer Hauptfunktion tot bis zum
Neustart (3.0.2). Beides älter als S5. Was der Simulator davon zeigen kann,
steht im Changelog; der Rest ist Prüfliste S5 am Gerät.

### S7 — Backup-Begriff · Web 12.9.3 und 12.9.4 · 02.–03.09.2026 (R50, R56)
Konzept `docs/konzepte/Umstellung-Backup.md` — nach R62 **gelöscht**;
zuletzt unter Commit `7057e7b`. Prüfdokument
`docs/konzepte/Pruefdokument-S7-Backup-Begriff.md` **bleibt**, bis seine
Prüfliste abgehakt ist.

**„Sicherung" heißt überall „Backup"**, in einem Zug. Anlass war die
Rückmeldung zur Seite selbst: Die Karte hieß „Backup erstellen", der Knopf
darin „Sicherung erstellen" · **Das Genus zieht mit** — Artikel, Possessiv,
Adjektivendung, Relativpronomen und die Pronomen im Folgesatz; Komposita
mit Bindestrich (E-S7-1): Komplett-Backup, Backup-Ziel, Backup-Datei ·
**Kommentare gehen mit** (E-S7-2), ausgenommen die Versionsgeschichte in
`version.php` · **Backlog: offene Punkte ja, erledigte nein** (E-S7-3) ·
**`tools/` zieht mit** (E-S7-4), ausgenommen die Quelldaten des
Referenzdatensatzes und die Seitennamen des Bilderlaufs — beides
Messgrundlagen, keine Begriffe.

**Fünf Funde, die eine mechanische Ersetzung zerstört hätte:** „Sicherung"
stand **fünfmal für *Absicherung*** · die **Kopfzeile des
Komplett-Backup-Dumps** ist zugleich Text und Erkennungsmarke — hätte die
Umstellung nur die neue Schreibweise gesucht, gälte jeder ältere Dump als
fremd und ein abgebrochener Stand wäre klaglos eingespielt worden; der
Leser kennt jetzt beide Schreibweisen (bis v1.0, R60) · Wortgruppen laufen
über **Zeichenketten-Grenzen** · **Versalien-Überschriften** (30) und
**Pronomen im Folgesatz** (17 echte aus 78 geprüften).

**Und ein Fehler, der seit Web 12.2.0 unbemerkt lag** (Backlog Nr. 89,
eigene Korrekturstufe 12.9.4): `job_komplett()` trug eine erst im Rumpf
geladene Konstante als Parameter-Vorgabewert — PHP wertet die beim Aufruf
aus. **Das geplante Komplett-Backup lief nie**; der Plan
„täglich/wöchentlich/monatlich" war seit S2/AP8 wirkungslos, und die
Wartungsseite zeigte den Job als „Fehler". Nachgezählt: In `server/` gibt
es genau diese eine Stelle.

**Die Vorzählung lag dreimal daneben, jedes Mal zu niedrig** — und das
gehört ins Protokoll, weil es die Regel belegt, die die Vorlage selbst
aufgestellt hat: eine Messung gegen einen laufenden Zweig hat ein
Verfallsdatum. R50 zählte 272 (Web 9.15.0), Fassung 15 zählte 451
(Web 12.4.2), tatsächlich waren es **642** (Web 12.9.2). Der Zuwachs kam
aus S2: `komplett_lib.php`, `admin_komplettsicherung.php`,
`wiederherstellen.php`, `admin_sicherungsziele.php`, `sicherungsziel_lib.php`
und `jobs_lib.php` standen auf keiner Zeile der Arbeitsliste — es gab sie
noch nicht.

*Zahlen, vorher → nachher:* `server/` ohne `vendor/` **642 → 167** (51
Versionsgeschichte, 116 Bezeichner, Pfade, Formatkennungen und falsche
Freunde — jeder einzeln zugeordnet) · normative Doku **272 → 48**
(Handbuch **78 → 0**) · offene Backlog-Punkte **45 → 7** · `tools/`
**188 → 40** · Historie **734 → 734**, 0 gelöschte Zeilen.
*Prüfzahlen:* Wortliste **0/0/0** (77 Regeln, 77 gegriffen) ·
Vollständigkeit **272** (unverändert) · Kontraste **21 Paare, 0 verfehlt** ·
Bilderlauf **304 Bilder**, Überlauf/Konsole/Knopfhöhen **0** · Kreisläufe
(R24) **252 882** und **8 797** Einzelvergleiche, je **0** unerklärt ·
Sichtprobe im Browser: 29 Seiten, „Backup" **83×**, „Sicherung" **2×** —
beide sind die dokumentierten Grenzen. *Reste:* Prüfliste in Abschnitt 6.

### S8 — Einstellungen, Verwaltung und Betrieb · Web 15.0.0 bis 15.5.1 · 05.–06.09.2026 (R61, R74–R77)
Konzept `docs/konzepte/Konzept-S8-Einstellungen-Administration-Wartung.md` —
nach R62 **gelöscht**; zuletzt unter Commit `fc470b0`. Prüfdokument
`docs/konzepte/Pruefdokument-S8-Einstellungen-Administration-Wartung.md`
**bleibt**, bis seine Prüfliste abgehakt ist.

**Die Einstellungen haben drei Blöcke statt einer Liste** (E-S8-04):
*Einstellungen* für alle, *Verwaltung* ab Admin, *Betrieb* für die
BetreiberIn — eine Quelle in `ui_einstellungen_punkte()`, aus der Leiste und
Übersicht lesen · **Dritte Rolle „BetreiberIn"** (R75) mit Migration und
Hierarchie über `ist_admin()`; das letzte Konto dieser Rolle lässt sich weder
zurückstufen noch löschen · **Die Seite „Wartung" ist aufgelöst** (E-S8-05):
Status, Statistik, Updates, Hintergrundjobs, Servereinstellungen —
je Seite ein Anliegen, die alte Adresse leitet weiter · **Betrieb → Status**
als Ampel mit vier Tönen und einer zählenden Meldung; sie ist seither die
Prüfstelle nach jedem Deploy (`Technik.md` 7, Schritt 8) · **Zwei
Bedienhöhen** (E-S8-09, R76): 44 px am Finger, 36 px am Zeiger ab 1024 px,
an drei Medienmerkmalen zugleich · **Ordnungsprinzip** als Programmregel
(R74): jede Funktion hat genau einen Ort.

*Neue Bausteine:* Wertekasten zweite Stufe (`codeblock-lang`),
Speicherbalken, Zähler am Menüpunkt, Sprungmarken unter dem aktiven Eintrag,
Übersicht in drei Spalten · *Fünf neue Zeichen* (Mockup 13), Symbolvorrat
44 → 49.

*Fünfzehn Fehlerfunde, alle behoben* (F-S8-P-01 bis -15) — darunter eine seit
einem Merge unbrauchbare `ausnahmen.json` der Wortliste, ein Wartungsmodus,
der die Seite mit dem eigenen Ausschalter aussperrte, eine Kachel, die den
ganzen Ablagebaum wog statt der Pakete (Faktor 3,8), ein selbst verursachter
HTTP 500 auf `index.php`, und zuletzt eine Ausnahmeseite ohne Wartungsbalken,
die **das Handbuch** fand und kein Prüfmittel.

*Prüfzahlen:* Bilderlauf **zweimal 368 Bilder** (Zeiger und Finger),
Überlauf/Konsole/Knopfhöhen/Ausfälle **0/0/0/0** · Stilvergleich zum ersten
Mal seit P3 wieder gelaufen: Kaskade **0 entfallen, 4 neu, 1 geändert,
0 vertauscht**, berechnete Stile **64 948 Elementmessungen, 6204 Abweichungen
in 18 Eigenschaften**, alle auf die geplanten Änderungen zurückgeführt ·
Wortliste **0/0/0** (86 Regeln) · Vollständigkeit **300 = 300** ·
Wartungsprobe **43 Erwartungen, 0 nicht erfüllt** · Tabelle 2.3 des Konzepts:
**94 von 94 Kennungen verortet** (2 entfallen, 3 umbenannt) · elf Mockups
gegen die Seiten: **acht deckungsgleich**, drei Abweichungen begründet.
*Reste:* Abschnitt 6 (Play-Store- und Connect-IQ-Adresse, Prüfliste des
Prüfdokuments), Backlog 117–122 und 124–126.

### Backlog-Runde · Web 19.1.2 · 12.09.2026 (Schritt 9)

Einzelpunkte aus Abschnitt 5, kein Konzept nach K1, je Punkt ein Commit;
Zweig `claude/go-bwucrx`. **Sieben Punkte erledigt** (38, 93, 97, 151, 153,
156, 167), **einer teilweise** (155 — die zwei Riegel stehen, der Neubau der
Fixture nicht), **zwei neu** (171, 172).

**Was sie verbindet: Keiner dieser Fehler meldet sich.** Drei zeigen sogar
etwas Plausibles — den jüngsten Diensttag statt des importierten, eine leere
Trefferliste statt 83 Einsätzen, eine Vorbelegung, die aussieht, als wäre
keine gesetzt. Deshalb standen sie im Backlog und nicht in einem
Fehlerbericht.

**Nr. 171 ist der schwerste Fund und war keiner der geplanten:** Im
Wartungsmodus kam **niemand mehr herein**, auch die BetreiberIn nicht.
`login.php` war von der Wartung ausgenommen, der Nebenaufruf `auth_salt.php`
nicht — ohne Salt und Rundenzahlen leitet der Browser kein Token ab, und die
Anmeldeseite schrieb „Anmeldung derzeit nicht möglich". Der einzige Ausweg war
SSH oder FTP, also genau die Lage, die die Ausnahmeliste verhindern soll; das
Handbuch (12.3) versprach den Weg, den es nicht gab. **Die Wartungsprobe
meldete dazu grün** — Erwartung 10 sah das Formular, nicht seinen Weg. Sie
misst ihn jetzt (Erwartung 10a, 51 → 53).

**Vier Backlog-Einträge stimmten nicht mehr** und sind beim Austragen
berichtigt worden: Nr. 97 in vier von sechs Aussagen (zwei genannte Dateien
rufen einen fremden Dienst, den die Wartung nie sieht), Nr. 38 in zweien
(`vehicles` steht seit Web 16.0.0 bewusst nicht in `NB_NOTNULL`; vier
Schemaabfragen statt einer), Nr. 93 in der Fundstelle (`auth_salt.php` rechnet
kein bcrypt), Nr. 156 im Kern — es hieß „bricht dort ab", tatsächlich lief das
Aufbauskript **durch** und druckte Zugangsdaten, die es nie gesetzt hatte.

*Prüfzahlen:* Linkprobe **116 Verweise, 0 Abweichungen, 0 Ausnahmen, 0 tote
Zeilen** (vorher 1 bekannte); Wartungsprobe **53 Erwartungen** (vorher 51),
0 nicht erfüllt bis auf die flackernde Nr. 15; Wortliste **0** außerhalb der
Ausnahmen; Vollständigkeit **330 = 330**; Kontraste **22/0**; `php -l` 10
Dateien, 0 Fehler. Einzelmessungen je Punkt stehen im Changelog und am
Backlog-Eintrag. *Keine Migration.*

### S9 — Einsatzbearbeitung und Rettungsmittel · Web 15.7.0 bis 19.1.1 · 07.–10.09.2026 (R73)
`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md` (Fable,
freigegeben 06./07.09.2026, E-S9-01 bis -19, sieben Mockups), Prüfdokument
daneben. **Acht Arbeitspakete plus zwei Nachträge (AP4a, AP5b)**, je ein
Commit, Zweig `claude/go-bwucrx`; letzter inhaltlicher Commit `3e9849c`.

**Was gebaut ist.** *AP1* Vorschlagsliste statt `<datalist>` (102) und das
Prüfmittel `tools/klickprobe/` · *AP2* Geocoder mit Kontoschalter und
gemeinsamer Kartendialog (101, 106, 107, 137, 147), Migration
`2026_09_07_adresssuche_konto` · *AP3* kleinere Kartenschilder, Artzeichen
aus einer Hand, Richtungspfeile richtig gedreht, „Spur" heißt sichtbar
„GPS-Daten" (72, 103, 105, 110) · *AP4/AP4a* Rettungsmittel bekommen **Typ**
und **Kurznamen**, der Standort wird außerhalb von „Standard" freiwillig
(69, 111), Migration `2026_09_07_rettungsmittel_typ`, Sicherungsnutzlast
9 → 10 · *AP5* **Standort zuerst**: ein Menüpunkt, eine Liste, je Standort
eine Seite, Anlegen und Bearbeiten im Dialog (44, 152) · *AP5b* die
zentralen Stammdaten verlieren ihre Oberfläche (R39, Web 18.0.0) · *AP6*
Rollen erscheinen ohne Speichern, und ein Dienst darf auf einem Fahrzeug
stattfinden, das es als Stammdatensatz nicht gibt (112, 113) · *AP7* die
**Notizen des Einsatzes werden Ende-zu-Ende-verschlüsselt** (109), Schloss
und Klartext-Hinweis kennzeichnen jedes Feld (108, 132), Sicherungsnutzlast
10 → 11; ein **Nachtrag** (19.1.1) setzt das Schloss an die zwei Stellen, an
denen es fehlte — die Einsatznummer in der Leseansicht und den Titel der
Karte „Notizen“ im Formular.

**Was AP7 aufgedeckt hat, und es stand vorher nirgends:** `api/suchindex.php`
lieferte **jede Notiz im Klartext** für den gesamten aktiven Bestand, bei
jedem Aufruf der Suchseite, **ohne dass jemand entsperrt haben musste** —
während der Kopfkommentar derselben Datei aufzählte, was der Server angeblich
nicht sieht. Und die Zusage **E6 „Administration sieht keinen Klartext"**
stimmte nicht: `notes` stand in der Spaltenliste des Adminpakets. Beides ist
zu. `CLAUDE.md` Abschnitt 4 und `docs/Technik.md` 4.98 sind nachgezogen —
dort fehlte `notes` bis dahin in **beiden** Listen.

**Prüfzahlen zum Abschluss:** Klickprobe **80 von 80 als Zeiger- und 80 von 80
als Fingergerät** (40 Wege × 390/1280 px) · Bilderlauf **360 Einzelbilder und
45 Kontaktbögen je Bedienhöhe, 0 Überlauf / 0 Konsolenfehler / 0 falsche
Knopfhöhen** · Kreislauf CSV **9120 Einzelvergleiche, 0 unerklärt** ·
Kreislauf Sicherung **287 842 Einzelvergleiche, 0 unerklärt** · Referenzbestand
**283 989** Einzelprüfungen im Generator und **5961** in den Quelldaten, je
ohne Befund, 0 offene Matrixzeilen · Wortliste **0 Treffer** bei 96 Regeln, 96
gegriffen · Kontraste **22 Paare, 0 verfehlt** · Linkprobe **116 Verweise, 0
unbekannte Abweichungen** · Vollständigkeit **330**, jeder Unterschied
benannt · `docs/Design.md` neu erzeugt und unverändert.

*Funde beim Bauen:* F-S9-P-01 bis -07 und F-S9-U-01 bis -35, alle behoben —
darunter drei, die **Daten gekostet hätten**: der stille Verlust von
Besatzungsnamen (F-S9-U-34), `readField()`, das eine verschlüsselte Notiz
beim Speichern gelöscht hätte, und der fehlende `case` in `import.js`, der im
CSV-Umlauf **114 Notizen** verschluckte. *Reste:* Backlog **163**
(gegenstandslos), **166** (zurückgezogen), **167**, **168** (P5, Schemarückbau
zu R39) und **169** (Besatzung am Tagesrettungsmittel) neu; Prüfliste im
Prüfdokument, 32 Punkte. *Gemergt am 10.09.2026* (PR #38, `0143df3`), der
Nachtrag Web 19.1.1 am selben Tag (PR #39, `015b26f`). *Seither fällig:*
**`update.php`** (die beiden Migrationen aus AP2 und AP4).

### 9a — Sofortpaket Sicherheit · Web 15.6.0, Android 0.14.0 · 07.09.2026 (R78)
`docs/konzepte/Vorbereitung-Sicherheitspaket.md` als Spezifikation (kein
Konzept nach K1, Muster R42), Prüfdokument
`docs/konzepte/Pruefdokument-Sofortpaket-Sicherheit.md`. **Web, elf Punkte in
vierzehn Commits (drei Nachbesserungen):** Rundenzahl 600 000 mit stiller Anhebung und Passwortregeln
nach Anteil (136), Login-CSRF (127), E-Mail-Wechsel mit Passwortnachweis und
Hinweismail (128), `apk/` und `demo/` gesperrt (129), DOCTYPE-Sperre gegen
UTF-16 (130), `wiederherstellen.php` ohne Auskunft (131), Bauordner geräumt
(133), **Ersetzfenster 72 h** (134), `json_js()` an 44 Stellen (135), **Weg
C** in vier Dokumenten (138), **Integritätswache** als tägliche Action (140,
`tools/integritaetswache/`). **Android, fünf Punkte, fünf Commits:**
HTTP-Ausnahme nur im Prüf-APK und Klartextverbot im Release (142),
abgewiesene Pakete nach 30 Tagen und beim Trennen geräumt, `dienst`-Zeilen
mit ihnen (114 Räumteil), Entscheidung gegen Pinning festgehalten (143),
Data-Layer-Empfang prüft Absender und Zeit (144), Prüfsumme der
Gradle-Verteilung (145). *Funde beim Bauen:* F-9a-01/-02 (stille Anhebung
lief nicht; Wartungsseite nannte die falsche Zahl), F-SP-P-01 bis -06 — alle
behoben. *Prüfzahlen:* Web — Bilderlauf 14 Seiten × 8 Breiten in beiden
Bedienhöhen (je 112 Bilder, 0/0/0), Wortliste 0/0/0, Vollständigkeit
300 → 301 (erklärt), gpxprobe 88 (2 vorbestehend), ingestprobe 47/0,
wartungsprobe 51/0, linkprobe 99/132/0, Wache 112/112 und Gegenprobe
1 abweichend; Android — `./gradlew build` grün — Handy **261 Prüffälle je Bauart** (Debug und Release; vorher 247), **0 Fehlschläge**, 15 übersprungen (14 Rundlauf ohne Installation und der jeweils bauartfremde Fall aus Nr. 142); Uhr **71 Prüffälle**, 0 übersprungen; Lint **0 Fehler** (Handy 13 Warnungen, unverändert die `libs.versions.toml`-Hinweise; Uhr 0); Release-APK Handy **7 867 394 B** (+332 B gegen 0.13.0), Uhr **19 574 406 B** (unverändert); Bilderlauf 72 Bilder wie zuvor, Emulator Stufe II erreicht im fünften Anlauf: Boot 715 s, Kopplung, Einstellungen und Trennen mit acht Bildern, Räumlauf am echten Android-SQLite 1 → 0 in allen vier Tabellen, Gerät am Server gelöscht; vier Anläufe ohne Boot wegen des Android-Watchdogs unter TCG, Gegenmittel `ro.hw_timeout_multiplier` als Root (F-SP-P-07); Wear-Emulator nicht gefahren, Wortliste
**0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** (87 Regeln, alle fünf Bereiche einschließlich d = Android). *Reste:* Backlog 140 (Branch-Schutz/2FA als Zuarbeit,
Deploy-Tor in S10), 114 (Bedienweg), 153 neu; Prüfliste P-1 bis P-12 beim
Auftraggeber. *Gegenprüfung (07.09.2026):* 93 Agenten, 29 Funde, 22 hielten
— alle behoben, je Punkt ein Commit (`bf5a506`, `839d317`, `a395455`,
`fd29866`, `6699b58`, `72a4268`, `4e30b26`); zweite Gegenprüfung auf die
Nachbesserungen im ersten Anlauf nur zu Nr. 134 gelaufen (Kontingent), zwei
Löcher, behoben (`15b9881`: Anker `created_at` allein, Migration
dreischrittig); **Wiederaufnahme** mit allen zehn Angreifern: 30 Funde (21
verschiedene), alle behoben in `76eaea4`, `9f51078`, `a8ee900`, `caf0cea`,
`b42ad1d`, `2c61524`, `bcd6c89` (Tageszeitraum folgt dem Fenster des Tages,
Rückfall vor der Migration, Papierkorb im offenen Fenster; Latin-1-
Deklaration erlaubt; Statuszeile mit richtigem Grund; Passwortregel mit
Schriftzeichen, fünf Reihenformen und Ziffern an der Eingabe — 8832 gefuzzte
Fälle 8064 → 0 Regressionen; Wache mit Tag-Ende außerhalb zitierter Werte);
dazu eine vierte Nachbesserung aus dem eigenen Nachfahren (`d4eb0a3`:
Zeiten müssen zum `day` passen, Tagesregel an der Serverzeit — die dritte
hatte den nachgelieferten Dienst um seinen Tageszeitraum gebracht).
Skeptiker: je Fund ein Skeptiker, der ihn zu **widerlegen** versuchte und dafür gegen den unveränderten Stand `448ce9f` reproduzierte — **24 von 30 hielten**, sechs wurden widerlegt (Vorbestand oder Randfall ohne beobachtbare Folge: der Diensttag im Papierkorb bei offenem Fenster; drei Zählfälle der Statuszeile, die nur über einen von Hand gesetzten Rundenwert entstehen; die Sonderzeichen-Tastaturreihe, die als Entscheidung dokumentiert ist; die quadratische Laufzeit, die keine Zusage verletzt). **Behoben sind alle dreißig** — auch die sechs, weil jede Behebung für sich mit einer Zahl belegt ist. Danach ingestprobe 62/0, gpxprobe
95/2, Wache 30/0 und 112/112, Passwortregel 0 % Zufallsabweisung;
Backlog 154–161 neu. **Die beiden Client-Punkte (159, 160) sind auf Wunsch
noch vor dem Merge gebaut** — Uhr 3.1.0 und Android 0.15.0, Zeile 39 in
Abschnitt 10. **Der Merge braucht `update.php`** (Migration
`rest_segments.created_at`). *Letzter Commit:* `c3949b8` (Android 0.14.1) plus der Buchführungs-Commit dieser Fassung. **Gemergt am 08.09.2026**
(PR #37, Merge-Commit `f299bbf`).

## 9. Pflege dieses Dokuments

- **Status** einer Phase: Abschnitt 3 (Tabelle und Block) während der
  Arbeit; nach dem Abschluss eine Zeile in Abschnitt 8 mit Versionen,
  Datum, Dokumenten, wesentlichen Änderungen und Resten; die Reste nach
  Abschnitt 6, die Backlog-Zuordnung in Abschnitt 5 nachziehen.
- **Programmentscheidungen** bekommen die nächste R-Nummer in Abschnitt 7,
  kompakt; die Begründung steht im betroffenen Konzept. Nie umnummerieren.
- **Keine Fassungsvermerke im Kopf.** Jede Änderung ist eine Zeile in
  Abschnitt 10; der Kopf trägt nur die Fassungsnummer und den Stand.
  **Wer eine Erledigt-Zeile schreibt oder einen Merge einträgt, liest den
  Kopf gegen** — zweimal (Fassung 24 und 32) nannte er einen Stand, den
  Abschnitt 3 und 10 längst überholt hatten.
- **Der Stand von `main` wird GEMESSEN, nicht fortgeschrieben** (Fassung 42).
  Vor jeder Fassung: `git fetch origin main`, dann die drei Versionsnummern
  aus `server/version.php`, `watch/source/Const.mc` und
  `android/version.properties` **auf `origin/main`** ablesen und
  `git log origin/main --merges` gegen die Merge-Aussagen des Dokuments
  halten. **Grund:** Der Merge geschieht außerhalb der Sitzung, die dieses
  Dokument schreibt — die Sitzung sieht ihn nicht und schreibt getrost
  „offen ist der Merge" weiter. Dreimal in drei Tagen ist genau das passiert
  (Fassungen 39, 41 und 42); das dritte Mal führte das Dokument **drei**
  gemergte Pakete als ausstehend und `main` zwei Client-Stufen zu niedrig.
  Eine Aussage über `main` ohne vorherigen `fetch` ist eine Vermutung.
- **Das Archiv wird nicht fortgeschrieben.** Was dorthin gehört, ist der
  Volltext einer abgeschlossenen Phase oder einer neuen Entscheidung ohne
  Konzept; er wird angehängt, nie verändert.
- Backlog-Nummern, Kennungen und R-Nummern bleiben, wie sie sind; neue
  Kennungen für Pakete vergibt nur eine Fassung dieses Dokuments.
- **Konzepte** kommen und gehen nach 2.2 (R62): Sie liegen in
  `docs/konzepte/`, werden je Arbeitspaket fortgeschrieben und gepusht, und
  nach Freigabe durch die umsetzende Instanz hier eingetragen und gelöscht.
  Wer ein Konzept löscht, ohne die Erledigt-Zeile mit Commit zu schreiben,
  hat die Phase nicht abgeschlossen.

## 10. Änderungsverlauf

| Fassung | Datum | Was |
|---|---|---|
| 1–6 | 22.–27.08.2026 | Programm aufgesetzt: Phasen P0–P7, R1–R33; P0, Sofortpaket, P1, S1, P2 und P3 nacheinander eingetragen |
| 7 | 30.08.2026 | S2 eingefügt (R34, R35) |
| 8 | 30.08.2026 | Dienstbetriebs-Gespräch: R36–R41; R42 Gerätekennung |
| 9 | 31.08.2026 | S3 eingefügt (R43); R44 Schlüsselfrist |
| 10 | 31.08.2026 | S4 eingefügt (R45); Apple Watch entfällt (R46); Parallelübersicht |
| 11 | 31.08.2026 | Uhr-Auslieferung abgeschlossen (R47) |
| 12 | 31.08.2026 | Uhr-Umbenennung abgeschlossen (R48) |
| 13 | 01.09.2026 | S5 eingefügt (R49) |
| 14 | 01.09.2026 | R50 Backup-Begriff; zwei Berichtigungen zu Merge-Aussagen |
| 15 | 02.09.2026 | S2 als ausgeliefert; Backlog 46–49 entdoppelt (→ 59–62); zweite Rückmeldungsrunde; R50 fällig |
| **16** | **02.09.2026** | **Neustrukturierung:** Archiv abgetrennt (R51), Fahrplan nach Ausführungsreihenfolge, S6 und S7 benannt (R52), P4 aufgelöst (R53), Kurzregister (R54), Prüflisten bereinigt (R55), R56–R58 entschieden, Planungsgespräch vor v1.0 als Schritt 10 (R59), Update-Weg und Ende der Rückwärtskompatibilität ab v1.0 (R60), Zwischenpaket S8 Einstellungen, Administration und Wartung als Schritt 7 (R61), Konzeptablage `docs/konzepte/` mit Lebenszyklus und Push je Arbeitspaket (R62, K7 geändert), Bestand nach `docs/konzepte/erledigt/` verschoben; Statusfehler berichtigt (Kleinstpaket nicht begonnen, S3 ausgeliefert, S4 auf dem Zweig gebaut); Backlog 68–79 angelegt, 63–67 für S4 reserviert |
| **17** | **02.09.2026** | **S4-Merge vorbereitet** (Schritt 1): Backlog des S4-Zweigs auf 63–67 umnummeriert und beide Reihen konfliktfrei zusammengeführt (44 offene Nummern, 0 doppelt); R58 umgesetzt (48 dp, Backlog 64 erledigt), R57 als E-S4-76 eingetragen; Konzept und Prüfdokument nach `docs/konzepte/` verschoben (R62) mit Statusblock; Migrationsregister gegengezählt (38 = 38); Signaturschlüssel des APK an den Auftraggeber übergeben — er war seit B1 erzeugt, aber nie ausgehändigt. Der Push auf `main` steht aus. |
| **18** | **02.09.2026** | **S6 gebaut** (Schritt 2, Web 12.9.0): drei Spalten an `devices` statt der in R42 genannten zwei (E-S6-1), `pair.php` liest beide Kopplungsformen über die neue `geraete_lib.php`, Modelltabelle als erzeugte Datei mit eigenem Werkzeug samt Nachauflösen (E-S6-6), Art und Modell in beiden Gerätelisten, R44 angeglichen (gleitende Schlüsselfrist) und dabei die Wirkungsaussage des R44-Eintrags berichtigt (E-S6-4, neues Prüfmittel `tools/fristprobe/`: 17 gegen 1 Neu-Entpackung je Schicht); Gerätekennung in beiden Listen gekürzt (E-S6-5, behebt einen Überlauf, den es schon vorher gab); JSON-Vertrag auf Fassung 1.4 (beide Formen, Speicherung, Android-Präfixe — der Nachtrag hing an R42), `Lizenzen.md` 7a für die erzeugte Tabelle; Backlog 59 erledigt, Rest als 80 angelegt; drei Zuarbeiten in Abschnitt 6 (Gerätedateien, S6-Abnahme, Datenschutzerklärung). Migrationsregister gegengezählt (39 = 39). |
| **19** | **02.09.2026** | **Modelltabelle gefüllt** (Web 12.9.1): 325 Teilenummern auf 173 Modelle aus den gelieferten Gerätedateien — die Zuarbeit aus Abschnitt 6 ist erledigt. Die echten Daten haben eine geratene Annahme widerlegt: `geraet_modell` geht von 64 auf 191 Zeichen (E-S6-7, zweite Migration `2026_09_02_geraetemodell_breiter`), weil die Dateien Sammelnamen bis 156 Zeichen führen; gekürzt wird erst für die Anzeige. Dateiweite Wortlisten-Ausnahme für die erzeugte Tabelle (89 Treffer, wie in ihrer LIESMICH vorhergesagt). Register 40 = 40. **Vollständigkeit 266 → 272** — die sechs liegen sämtlich in „Unicode-Zeichen als Symbol im Markup": vier sind Auslassungszeichen in Kommentaren (dieselbe Verwendung wie an drei älteren Stellen in `version.php` und `update.php`), zwei die Kürzungsmarke im Code, die `admin_user.php` schon vor S6 benutzte. Kein neuer Befundtyp; die Kategorie ist Bestand aus P3. |
| **20** | **02.09.2026** | **Marken- und Schutzrechtszeichen aus den Modellnamen** (Web 12.9.2): 171 der 173 Namen trugen ® oder ™, 194 Vorkommen. Sie gehören nicht uns, sie stören die Zählung (ein Wechsel ® → ™ ergäbe zwei Geräte) und sie kosten Platz. Entfernt wird im Erzeuger, nicht in der erzeugten Datei; `í`, `ē` und der Halbgeviertstrich bleiben — sie sind Bestandteil der Namen. Gegengeprüft: weiterhin 325 Teilenummern auf 173 verschiedene Namen, 0 Zusammenfälle, 0 doppelte Leerzeichen. Keine Migration. |
| **21** | **02.09.2026** | **Drei Punkte aufgenommen** (Backlog 81–83): App-Symbol in der Benachrichtigung zu groß und angeschnitten (am Gerät gemeldet, aus dem Quellstand nicht nachvollziehbar — die Kachel wurde nachgerechnet, sie stimmt), fehlende Warnung vor dem Akkuverbrauch der Daueraufzeichnung (der vorhandene Akku-Dialog sagt das Gegenteil), und die **Haltbarkeit der Gerätestatistik** als Diskussionspunkt für Schritt 10 — gemessen: 82 von 82 Einsätzen ohne Geräteverweis, weil `ON DELETE SET NULL` gilt und `device_id` nicht in der Sicherung steht. 81 und 82 in den S4-Rest, 83 vor den Neuaufsetzen-Beschluss (R60). |
| **22** | **02.09.2026** | **Android-Rückmeldungen und Gerätestatistik entschieden:** feste Server-Adresse und Name der Android-App (R63); Nr. 83 als R64 entschieden — Momentaufnahme am Einsatz, eigene `origin`-Werte, Umsetzung im S4-Rest zusammen mit Nr. 63; Backlog 84–88 angelegt (feste Adresse, App-Name, Statusleiste, Web-App-Erhebung vor v1.0, NutzerInnen-Kachel); Schritt 6 und Schritt 10 ergänzt; Änderungsverlauf wieder aufsteigend |
| **23** | **02.09.2026** | **Statuszeilen 1 und 2 auf den Stand von `main`:** S4-Merge und S6 sind gemergt (Web 12.9.2, Android 0.7.7); beide Migrationen warten auf `update.php`. Als Nächstes laufen Schritt 3 (S5-Konzept, Fable) und Schritt 4 (S7, Opus) parallel |
| **24** | **03.09.2026** | **S7 erledigt** (Schritt 4, Web 12.9.3/12.9.4): „Sicherung“ heißt überall „Backup“ — 642 → 167 Fundstellen in `server/`, Handbuch 78 → 0, Historie unberührt; Entscheidungen E-S7-1 bis E-S7-4 (Bindestrich-Komposita, Kommentare gehen mit, offene Backlog-Punkte ja, `tools/` mit zwei Messgrundlagen als Ausnahme). Fünf Funde, die eine mechanische Ersetzung zerstört hätte, darunter die Kopfzeile des Komplett-Backup-Dumps, die zugleich Erkennungsmarke ist. Dazu **Backlog Nr. 89**: Das geplante Komplett-Backup lief von Web 12.2.0 bis 12.9.2 nie — eigene Korrekturstufe. Konzept nach R62 gelöscht, Prüfdokument bleibt. Zwei Zuarbeiten in Abschnitt 6. **Berichtigt:** die Standzeile im Kopf, die seit Fassung 23 „Web 12.4.2“ nannte, während Abschnitt 3 schon 12.9.2 sagte |
| **25** | **03.09.2026** | **S5 gebaut und gemergt** (Schritt 5, Web 13.0.0–13.2.0, Uhr 3.0.0; PR #28 und #29): Die Kopplung läuft umgekehrt — das Gerät zeigt den Code, das Web nimmt ihn entgegen, das Gerät bestätigt. Dazu ein Verfahrenswechsel, der nicht im Plan stand (Geräteschlüssel bcrypt → SHA-256, E-S5-42, die Bestandsuhr koppelt einmal neu), der **Wartungsmodus** als Zusatzpaket W (Web 13.2.0, 503 statt 500 während eines Updates) und ein stiller Datenverlust im Upload, der älter ist als S5 (Web 13.0.1). Backlog 66 erledigt (`watch/` läuft durch die Wortliste), 89–92 aus S7 und S5/C, **93–97 neu**. **Vier Migrationen warten auf `update.php`** — die aus S5 ist die dringende, ohne sie endet jede Kopplung in einem 500. **Paket E** (Android 0.10.1) ist gebaut, aber nicht gemergt; es geht vor den S4-Rest. Die Freigabe des Abschlusses und damit die Löschung der Konzepte nach R62 steht aus. |
| **26** | **03.09.2026** | **Schritt 11 (Planung v1.0) vorgezogen und entschieden** (Konzept `docs/konzepte/Konzept-Planung-v1.0.md`): **R65** Store-Verteilung in zwei Stufen — interner Play-Test-Track ab Schritt 6, Produktion mit Welle 1; Organisationskonto der Gen-EM GbR, Versionscode-Versatz (Nr. 98), Signaturschlüssel zu Play App Signing, Seitenladung bis zur Produktionsfreigabe; sieben Zuarbeiten, D-U-N-S sofort; E-R45-6 ersetzt; Abschnitt 1 und Betriebsübergang angepasst · **R66** Update-Weg: keine Selbstprüfung, kein Selbst-Update, Produktion nur auf Handauslösung, nur ausstehende Migrationen sichtbar (Nr. 77 damit für S8 beantwortet), Register beginnt bei v1.0 neu (Nr. 99) · **R67** Auslieferungskette: Staging automatisch und Prüfumgebung, Freigabe- und Backup-Tor, Rollback, Prüftor in drei Stufen, Android-Signatur außerhalb der CI (Nr. 100) · **R68** Repositorium: eines, frisch, öffentlich, `gen-em/nadoku`; P8-Paket „Repo-Umzug und Inventur" · **R69** Review-Umfang: alles in zwölf Stücken, Bedrohungsmodell zuerst, Kommentardurchgang ohne Beschluss- und Fassungsverweise, zwei Wege für Funde (Sofortpaket / Pflichtpaket P6), Paketschnitt nach der Freigaberunde; R13 und R31 gehen darin auf · **R70** Web-App-Manifest: Manifest allein, in P7, „NAdoku Web" mit eigenem Symbol; Nr. 87 als Erhebung erledigt · **R71** Phasenschnitt: P6 Review und Bereinigung, P7 Gesicht v1.0, P8 Schnitt — Schritt 11 (alt) in drei Schritte geteilt, alle P6-Nennungen zugeordnet · **R72** Doku-Anforderungen: vier Dokumente nach Zielgruppe, Handbuch reist mit dem Release als HTML, erzeugte Screenshots, kurz und prägnant; Betreiberhandbuch generisch mit Notfall-FAQ und Betriebsakte · **R73** Problemsammlung vom 03.09.2026 als **Schritt 8 „S9 — Einsatzbearbeitung und Rettungsmittel"** (Nr. 101–113, Vorbereitung `docs/konzepte/Vorbereitung-S9-Problemsammlung.md`, Konzept mit Fable nach dem S8-Konzept); **Schrittnummern 8–11 → 9–12, dazu die neuen Schritte 13 und 14**. Torwächter in P5 hängt am Wartungsmodus aus Paket W. **Berichtigt:** der Absatz zu Nr. 83 im Planungsschritt nannte den Punkt als offen, obwohl Fassung 22 ihn als R64 entschieden hatte. Abschnitte 1, 2, 3, 4, 5, 6, 7 entsprechend; Backlog 98–113 angelegt. |
| **27** | **04.09.2026** | **Schritt 6 gebaut** (Zweig `claude/rahmenplan-schritt-6-ewm0kx`, Web 14.0.0–14.2.2, Android 0.11.0–0.13.0). **Teil A:** Kopplungsmodul auf Vertrag 1a, feste Server-Adresse und App-Name (R63, Backlog 84/85/86), R57 (Überschneidungshinweis), Akkuwarnung und Verbrauchshinweis (Backlog 82/98). **Teil B — R64 und Backlog Nr. 63 als *eine* Formatänderung** (eigenes Konzept `Konzept-R64-Herkunft-Geraet.md`, AP1–AP5): `origin` trägt sechs Werte statt drei, abgeleitet aus dem `client_ref`-Präfix; **Momentaufnahme** `geraet_art`/`geraet_modell` am Einsatz *und* am Ruhesegment, beim Anlegen kopiert — damit ist Nr. 83 gegen `ON DELETE SET NULL` gefeit; Konto-Sicherung auf **Nutzlast 9** (Sperrvermerke des Schnitts **und** Momentaufnahme), womit Nr. 63 erledigt ist; **der Referenzbestand ist neu gebaut**: zwei Geräte über den echten Kopplungsweg (eine Uhr, ein Handy), alle sechs Herkunftswerte belegt, ein Schnitt darin — und weil der Demo-Reset die Fixture alle 30 Minuten einspielt, prüft der Produktivserver Nr. 63 seither dauerhaft. **Teil C:** Play-Console-Vorbereitung, soweit sie ohne D-U-N-S und Signaturschlüssel geht (`Vorbereitung-Play-Console.md`); vier ihrer fünf Befunde abgestellt (Uhr ohne jede Berechtigung, keine Umleitungen für den Geräteschlüssel, Rechtstexte in der App, Kopplungs-Mails als Zuarbeit vermerkt). **Drei Funde am Code, alle behoben:** Die Anwendung schrieb eine CSV-Datei, die sie selbst nicht einlesen konnte (`uhrzeit_ortszeit` aus Phase 2, vom Import als Startzeit verlangt); die GPX-Probe verglich nach dem Neuaufbau eine Datei statt 172 und meldete grün; die Einsatztabelle zeigte „kein Ende" an abgeschlossenen Einsätzen ohne Phase 9 (Entscheidung des Auftraggebers: Dauer = Beginn bis Ende). **Backlog 63 und 83 erledigt.** Der Push auf `main` steht aus. |
| **28** | **05.09.2026** | **S8-Konzept eingetroffen und Umsetzung begonnen** (Schritt 7, Zweig `claude/umsetzung-buuvfq`). Das Konzept liegt als `docs/konzepte/Konzept-S8-Einstellungen-Administration-Wartung.md` mit Prüfdokument und zwölf Mockups (01 und 03–12 freigegeben, 02 verworfen). **Vier neue Programmentscheidungen:** **R74** Ordnungsprinzip — jede Funktion hat genau einen Ort nach Zielgruppe, Objekt und Häufigkeit, Ausnahmen eine Ebene tiefer, ein Paket ohne benannten Ort wird nicht gemergt (K1 ergänzt) · **R75** dritte Rolle **BetreiberIn** mit Hierarchie BetreiberIn ⊇ Admin ⊇ NutzerIn; die Migration macht alle heutigen Admins zu BetreiberInnen, das letzte solche Konto ist geschützt — eine **Ausnahme zu „Nicht Umfang: Rollen"** in Schritt 7, die Support-Rolle bleibt bei R38 · **R76** Bedienhöhe in **zwei Stufen** (44 px, am Zeigergerät ab 1024 px 36 px) — beantwortet Nr. 74, auf die S9 PS-3 wartet · **R77** drei Backup-Begriffe (Backup, Konto-Backup, Komplett-Backup) mit einem Verb je Rückweg. Dazu: die **Wartungsseite wird aufgelöst** (Nr. 77) und der Block **Betrieb** trägt sieben Seiten, darunter die neuen **Status** und **Statistik**; **Backlog Nr. 80 ist geteilt** — Gerätemodelle und Nutzung nach S8, Herkunft je Einsatz und Dashboard bleiben P5; sechs neue Backlog-Kandidaten (117–122). Schritt 10 nennt jetzt je P5-Option ihre Seite (E-S8-12), Schritt 8 die Vorgabe für die Tagesübersicht. Zwei Zuarbeiten in Abschnitt 6 (Play-Beitrittslink, Connect-IQ-Adresse); die Freigabe des Konzepts ist erledigt. Die Sperre „S8 zu S4-Rest und S7" ist erfüllt |
| **29** | **05.09.2026** | **Die Wortmarke ist vorgezogen** (Web 15.3.2, Auftrag vom 05.09.2026). Schritt 13 (P7) sah „Umbenennung überall" vor; ausgelöst hat es der Tab-Titel, der in Web 15.3.1 auf **Gen-EM NAdoku** ging und die Kopfleiste mit „Gen-EM Einsatzdoku" daneben stehen ließ. Jetzt heißt der Name überall dort so, wo er als Name auftritt: Kopfleiste, Schublade, Anmeldeseite, Passwortseiten, Einrichter, Absendername der System-E-Mails, Urheberfeld von GPX (beide Wege) und CSV, die Markierungsdateien von Einrichtung und Wiederherstellung, neun Skript-Dateiköpfe und die Titel von README, Handbuch, Technik, Backlog, Changelog, Geräte-Eingabe und Uhr-Layoutregeln. **Bei P7 bleibt** die Langform „Gen-EM Einsatzdokumentation Notarzt" in den Texten der System-E-Mails (20 Stellen in sechs Dateien) — sie ist der beschreibende Name und steht in Betreffzeilen, die Bestandsnutzerinnen wiederfinden; und die Historie bleibt unberührt (`version.php`, ältere Changelog-Einträge, `Design.md`, Archiv). Damit ist die Abnahme „kein ‚Einsatzdoku' mehr" in Schritt 13 auf eine Frage zusammengeschrumpft. Dazu zwei gemeldete Fehler behoben (Web 15.3.1): die Karte nutzte ab 1600 px nur ihren oberen Teil (Leaflet rechnete mit 400 × 324 statt 400 × 840 px), und der Tab-Titel |
| **30** | **06.09.2026** | **Krypto- und Sicherheitsreview vorgezogen und entschieden (R78).** Auf die Frage „ist das sicher?" liegt der Befund in `docs/konzepte/Review-Krypto-Sicherheit.md`: Verfahren richtig gebaut, kein kritischer Fund; drei Wege bleiben — schwaches Passwort plus Datenbankabzug, Angreifer mit Codezugang, Klartext-Ortsdaten. Entschieden: **Sofortpaket Sicherheit** als Schritt 9a (Nr. 127–138, 142–145), **S10 — Sicherheit** als Schritt 9b vor P5 (Server-Anteil am Datenschlüssel mit Schlüsselblatt, Adminpakete versiegeln), Zweitfaktor für alle in P5 (Nr. 141), CSP-Bauplan zu Nr. 8, **Weg B als S11** (Schritt 12a, nach P6, vor der Öffnung; Nr. 43 und 53 zusammengeführt; die drei Fragen aus `Konzept-V1-Ortsdaten.md` beantwortet), Deploy-Tor mit Staging. Backlog 127–146 angelegt; 8, 43, 53, 114 ergänzt. Am selben Tag nachentschieden: Photon-Schalter mit Vorgabe „an" (F-SP-4), Ersetzfenster 72 h (F-SP-8), Integritätswache sofort (F-SP-9). Vorschläge und Entscheidungen in `docs/konzepte/Vorbereitung-Sicherheitspaket.md`. |
| **35** | **07.09.2026** | **S9/AP1 gebaut und geprüft** (Web 15.7.0, Zweig `claude/go-bwucrx`): eine Vorschlagsliste statt dreier und einer vierten vom Browser — `assets/vorschlagsliste.js` mit Gruppen, Tastatur und Übernahme auf `mousedown`; **Backlog 68, 102, 106 erledigt**, neuer Baustein `Design.md` 9.28, vier Klassen auf der Streichliste. Dazu das **erste Prüfmittel des Projekts, das ein Element bedient** (`tools/klickprobe/`, E-S9-16): PS-2 **0 von 3 → 3 von 3** bei 300 ms gehaltener Maus, und der Nebenbefund, dass `locator.click()` den Fehler gar nicht findet. `datalist` **12 → 0** außerhalb von Kommentaren; Klickprobe 12 von 12 Wegen über zwei Breiten × zwei Bedienhöhen; Bilderlauf, Wortliste, Vollständigkeit und Kontraste mit Zahl im Prüfdokument. Vier Funde, alle behoben — **drei davon im Prüfmittel selbst**, darunter `fill('')`, das kein neutrales Leeren ist und dem Lauf einen Chip gelöscht hat. **Berichtigt:** Der Block Schritt 8 nannte PS-12 als „Nr. 150"; es ist **Nr. 152** (150 ist der Cron-Befehl aus Fassung 33). **Korrekturstufe 15.7.1 am selben Tag**, vom Auftraggeber am Bild gemeldet: Die Liste lag mit `z-index: 20` hinter der klebenden Speichern-Leiste (30) und verdeckte deren unterste Trefferzeilen (61–69 px, mit `elementFromPoint` nachgewiesen) — Ebene jetzt **35**, zwischen Leiste und Kopfleiste (40); die Klickprobe misst sie seither in beide Richtungen (16 von 16 Wegen). Bilderlauf und Klickprobe hatten beide Null gemeldet und beide recht: Sie messen etwas anderes. **Zwei der drei Fragen entschieden** (Diensttags-Besatzung → AP1; **zwanzig** Backlog-Punkte im Abschluss, also auch Nr. 132 und 137), die dritte offen — Prüfdokument, Abschnitt 4 |
| **36** | **07.09.2026** | **S9/AP2 gebaut und geprüft** (Web **15.8.0**, Zweig `claude/go-bwucrx`): Die Anschrift des Adressdienstes stand zweimal fest im ausgelieferten Code — jetzt steht sie **nirgends** dort (`grep -rn "komoot" server/assets/` **2 → 0**); `assets/geocoder.js` ist der eine Weg nach draußen, `server/geocoder_lib.php` die eine Quelle der Einstellungen. **Zwei Schalter davor** — Installation (Betrieb → Servereinstellungen, Karte „Adresssuche" mit dem Feld „Dienst") und Konto (Profil → Karte „Datenschutz", `users.adresssuche`, Migration `2026_09_07_adresssuche_konto`): **an → 2 Anfragen, aus → 0**, gemessen am Netzwerkprotokoll **mit Gegenprobe**, denn eine Null ohne sie belegt nichts. Der **Kartendialog** bekommt Suchfeld im Kopf (Treffer setzt nur das Kreuz, F1), die aufgezeichnete Spur mit Ringen und Legende und `fitBounds` bei leerem Feld; der Pin-Knopf steht jetzt an **5 von 5** Einbauorten statt an zweien. **Backlog 70, 101, 107, 137, 147 erledigt**, neuer Baustein `Design.md` 9.29. Klickprobe **40 von 40** Wegen (36 Bilder), Bilderlauf zehn berührte Seiten **0/0/0**, Wortliste **0/0/0**, Vollständigkeit **298 → 304** (Differenz erklärt), Kontraste **21/0**, Linkprobe **134/0**, Register **44 = 44**. **Drei Funde, alle behoben und keiner im Browser zu sehen**, alle drei von der Klickprobe: `const` im Bootstrap wird keine Eigenschaft von `window` (Dialog ohne Suchfeld, Konsole antwortete trotzdem richtig); nach dem Speichern zeigte die Betriebsseite den **alten** Schalterstand; und das **Demo-Konto konnte seine eigene Adresssuche nicht abschalten**, weil der Schalter im Profilformular stand, das der Demo-Wächter ganz verwirft. **Zwei neue Fragen** (Pfeile auf der Spur — Konzepttext und Mockup widersprechen einander; Überschrift im Dialogkopf), Prüfdokument Abschnitt 4. **Nach dem Merge muss `update.php` laufen.** |
| **37** | **07.09.2026** | **S9/AP3 gebaut und geprüft** (Web **15.9.0**, Zweig `claude/go-bwucrx`): Die Kartenzeichen werden kleiner — der Farbring ist jetzt der **Rand** und liegt nicht mehr darum herum; nachgemessen **32 / 32 / 38 / 28 / 14 / 20 px** gegen vorher 36 / 48 / 60 / 32 / 16 / 28 (M-S9-01 V1). **Die Richtungspfeile haben sich nie gedreht** (Nr. 72): `transform` wirkt nicht an einem Inline-`<span>`, die Winkelrechnung war die ganze Zeit richtig. Gemessen an der Bildschirmmatrix des SVG — vorher `a=0,833 b=0 c=0 d=0,833` bei behaupteten 90 Grad, nachher **12 von 12** Pfeilen in 30-Grad-Schritten auf **0,1 Grad** genau. **Derselbe Fehler drei Zeilen darüber** am Punkt des Abfahrtorts, in keinem Backlog-Punkt: 4 × 18 px statt 12 × 12, Spurfarbe nie sichtbar — als **Nr. 162** aufgenommen und behoben. **Windenkacheln nach Fähigkeit** statt nach Zählung (Nr. 104), `api/range.php` liefert `faehigkeiten`, auf Luft eingeschränkt wegen einer Migrationsaltlast von 2026. **„Spur" heißt „GPS-Daten"** (Nr. 110): 72 sichtbare Zeichenketten in 18 Dateien, 41 Handbuchzeilen, neue Wortlisten-Regel und sechs Ausnahmen; die Android-Texte gehen an **Schritt 9a** (Entscheidung des Auftraggebers). Drei neue Zeichen, Vorrat **49 → 52**. **Backlog 72, 103, 104, 105, 110 erledigt.** Neues Kapitel `Design.md` 9.30, Markersatz in `Technik.md` erstmals vollständig. Klickprobe **6 von 6**, Wortliste **0/0/0** (96 Regeln), Vollständigkeit **304 = 304**, Kontraste **21/0**, Linkprobe **134/0**. Zwei Funde stammen aus einer **Gegenprobe der Aufklärung**, die niemand beauftragt hatte: dass E-S9-13 gar keine Abnahme besaß, und dass der antippbare Ringpunkt unter die 24-px-Grenze fällt, die derselbe Beschluss nennt. |
| **39** | **08.09.2026** | **Die beiden Client-Punkte aus der Gegenprüfung — Uhr 3.1.0 und Android 0.15.0** (Backlog Nr. 159 und 160, auf Wunsch vor dem Merge). **Nr. 159 lag nicht dort, wo der Backlog ihn vermutete:** Er nannte `400`, und den kann die Uhr kaum auslösen; bedienbar erreichbar sind `401` und `403` (Gerät im Web gelöscht oder abgeschaltet). Beide fielen in denselben Zweig wie eine Störung und wurden endlos wiederholt — das Paket blockierte die Warteschlange, und weil ein Rückstand zugleich das Trennen sperrte, war die Uhr nur noch durch Löschen der App zu retten (dieselbe Sackgasse wie Nr. 157 beim Handy). Jetzt drei Fälle: `401`/`403` halten das Senden an und nennen den Grund (**nichts wird verworfen**), ein `400` **mit** Fehlerschlüssel parkt das eine Paket, alles Übrige bleibt Störung. Geparkte zählen nicht im Rückstand, das Trennen ist frei, und ein **kurzer START** verwirft sie nach Rückfrage. **Nr. 160:** Die Dienstanzeige führt das Datum, sobald der Dienst an einem anderen Kalendertag begann, und nach **26 Stunden** erinnert die App einmal ans Beenden (Zahl gewählt, Begründung am Code: regulär bis 24 h). Der Vorschlag, `day` je Paket zu bilden, wurde geprüft und **verworfen** — er ändert am Fall nichts Sichtbares und verschlechtert den Offline-Fall. **Mockups vorab freigegeben** (CLAUDE.md 5). **Backlog 161 neu** (aus einer Aufzeichnung ein Stück löschen können — heute geht nur alles oder nichts). Prüfzahlen: `./gradlew build` grün, Handy **264** Prüffälle je Bauart (261 + 3 in `ZeitTest`), Uhr 71, 0 Fehlschläge, Lint 0 Fehler / **14** Warnungen (eine neue `PluralsCandidate`, nicht stummgeschaltet), APK Handy 7 868 398 B, Uhr 19 574 402 B; Wortliste **0/0/0** (87 Regeln); Uhr-Prüfstand **Stufe I: 99 von 99 Geräten übersetzt, 0 Fehler, 0 Warnungen** (mit dem endgültigen Stand gefahren, nachdem die Wortliste eine Korrektur erzwang), **Stufe II erreicht** — die App startet im Simulator (fenix6pro, 65,9 kB), die Sync-Seite ließ sich dort **nicht ansteuern**: Weder Tastendruck noch Mausklick erreichten den Simulator im virtuellen Bildschirm; **Emulator: fünf Bilder** (`android/emulator-bilder/0150-*.png`), beide Änderungen im laufenden Programm gesehen — „Dienst läuft seit Di. 08.09., 20:53" und die Erinnerung „Dienst läuft seit 27 Stunden" samt Knopf. **Drei Prüfmittel fanden je einen echten Fehler:** die strenge Typprüfung drei ungecastete Wörterbuchwerte, die Wortliste einen fest geschriebenen Tastennamen (auf der Venu 3s heißt die Taste „Action"), und der Emulator einen englischen Wochentag mitten im deutschen Satz („Tue 08.09." statt „Di. 08.09.") — die Sprache des Datums ist jetzt fest deutsch, wie alle Texte der App |
| **38** | **07.09.2026** | **Android 0.14.1 — „GPS-Daten" statt „Spur" in fünf Texten des Handy-Moduls** (Auftrag des Auftraggebers, E-S9-03, Backlog Nr. 110): Akkuwarnung, Zweck des Benachrichtigungskanals, Hinweis im Modus „nur aufzeichnen", Ortungshinweis, Standortwarnung. Dieselbe Person liest Browser und Handy; die Weboberfläche sagt seit Web 15.8.0 (S9/AP3) „GPS-Daten". Die Uhr sagt „Spur" nur in einem Kommentar. Eigene Fassung, weil die Android-Apps getrennt zählen, einen APK-Bau und nach CLAUDE.md 6 einen Emulatorlauf brauchen. **K7-Vermerk:** Die befristete Wortlisten-Ausnahme `spur-android-wartet-auf-9a` liegt auf dem S9-Zweig, nicht auf diesem — wer zweiter mergt, streicht sie. Prüfzahlen: `./gradlew build` grün, Handy 261 Prüffälle je Bauart, Uhr 71, 0 Fehlschläge, Lint 0 Fehler (13 Warnungen, unverändert), APK Handy 7 867 430 B, Uhr 19 574 402 B; Wortliste 0/0/0 (87 Regeln); Emulator Stufe II erreicht — Boot 502 s, fünf Bilder, alle fünf Texte im laufenden Programm gesehen (Dienstansicht, laufender Dienst, zwei Warnmeldungen, Kanalseite). Dazu am selben Tag die **zweite Nachbesserung zu Nr. 134** (`15b9881`, siehe Zeile 37) |
| **37** | **07.09.2026** | **Schritt 9a, Web-Teil adversarisch gegengeprüft und nachgebessert.** Ein Workflow aus 93 Agenten griff den fertigen Web-Teil aus sechs Blickwinkeln an, jeder Fund wurde dreimal zu widerlegen versucht und gegen den heutigen Stand reproduziert: 29 Funde, **22 hielten**. Sechs zur Dokumentation berichtigt, sechzehn zum Code behoben, je Punkt ein Commit: **134** — der Anker des Ersetzfensters war das vom Gerät gesendete `started_at` (eine falsch gestellte Uhr schloss das Fenster sofort und verlor Punkte), jetzt `max(started_at, created_at)` serverseitig, Zukunft zählt nicht, Diensttag bleibt, Abschlusspaket wird als `kept_meta` genannt, kein leerer Tag; Migration `rest_segments.created_at` — **`update.php` nach dem Merge**; **130** — UTF-7 über die Kodierungsdeklaration umging die DOCTYPE-Sperre, jetzt nur UTF-8/ASCII; **136** — die Statuszeile hätte das Demo-Konto für immer als „Übergang" gezählt, und die Anteilsregel maß den Rest ohne Sonderzeichen (2–15 % aller Zufallspasswörter abgewiesen, jetzt 0 %), strich Listenwörter in Listenreihenfolge und nicht unter sechs Zeichen; **140** — die Wache sah `<base href>` und `formaction` nicht, ihre Selbstprobe hing an Bezeichnern, ein Dateiname mit Leerzeichen brach den Lauf ab; beim Nachprüfen 27 Angriffsvarianten, **17 noch grün** (unzitiertes `src=`, Ereignisattribute, `meta refresh`, Einbettungen, `javascript:`), geschlossen im zweiten Commit, Selbstprobe 12 → 28. Zweite Gegenprüfung auf die Nachbesserungen: im ersten Anlauf nur zu Nr. 134 gelaufen (2 von 10 Angreifern, keine Skeptiker — Sitzungsgrenze des API-Kontingents), zwei Löcher — die Migration scheiterte an Randdaten und galt danach als erledigt, ein eingeholtes Zukunfts-`started_at` öffnete das Fenster erneut —, behoben (`15b9881`: Anker `created_at` allein, Migration dreischrittig mit Kappung). **Wiederaufnahme auf Anweisung, alle zehn Angreifer: 30 Funde (21 verschiedene), alle behoben, je Punkt ein Commit** — 134: ein neuer `client_ref` schrieb den Zeitraum eines alten Diensttags um, der Rückfall vor der Migration lief in ein 500, ein Anker 1970-01-01 00:00:00 galt als keiner, ein Papierkorb-Tag verlor seinen Einsatz (`76eaea4`); 130: die Deklarationsprüfung brach den Dateidialog-Import einer Latin-1-Datei (`9f51078`); 136: Statuszeile mit falschem Grund und zwei Zählfehlern (`a8ee900`), Passwortregel mit Emoji als zwei Zeichen, füllbarem Rest, quadratischer Laufzeit und Ziffern-Symbol-Zufall zu 46–91 % abgewiesen (`caf0cea`, `b42ad1d`); 140: `TAG_RE` endete im Attributwert, `lstrip` nahm eine Menge statt eines Bereichs (`2c61524`). **Eine vierte Nachbesserung fand die Sitzung selbst** (`d4eb0a3`): Die dritte hatte den nachgelieferten Dienst um seinen Tageszeitraum gebracht, und der Einsatz mit den absurden Zeiten stand weiter in der Datenbank — jetzt weist die gemeinsame Prüfschicht ein Paket ab, dessen Zeiten nicht zu seinem `day` gehören, und die Tagesregel hängt an der Serverzeit (Ingestprobe 56 → 62/0, davor 62/4). Skeptiker: je Fund ein Skeptiker, der ihn zu **widerlegen** versuchte und dafür gegen den unveränderten Stand `448ce9f` reproduzierte — **24 von 30 hielten**, sechs wurden widerlegt (Vorbestand oder Randfall ohne beobachtbare Folge: der Diensttag im Papierkorb bei offenem Fenster; drei Zählfälle der Statuszeile, die nur über einen von Hand gesetzten Rundenwert entstehen; die Sonderzeichen-Tastaturreihe, die als Entscheidung dokumentiert ist; die quadratische Laufzeit, die keine Zusage verletzt). **Behoben sind alle dreißig** — auch die sechs, weil jede Behebung für sich mit einer Zahl belegt ist. **Backlog 154–161 neu.** Prüfzahlen: ingestprobe 47 → 62/0, gpxprobe 88 → 95/2, wartungsprobe 51/0, Wache 30/0 und 112/112, Wortliste 0/0/0, linkprobe 132/0/1/0. Offen: der Merge auf `main` nach Freigabe, danach `update.php` |
| **43** | **12.09.2026** | **Backlog-Runde gebaut (Schritt 9, Web 19.1.2, Zweig `claude/go-bwucrx`).** Sieben Punkte erledigt (38, 93, 97, 151, 153, 156, 167), einer teilweise (155), zwei neu (171, 172). Erledigt-Zeile in Abschnitt 8, Abschnitt 5 nachgezogen — die Tabelle bleibt deckungsgleich (**66 = 66**). **Nr. 171 war keiner der geplanten und ist der schwerste:** Im Wartungsmodus kam niemand mehr herein, weil `auth_salt.php` nicht in der Ausnahmeliste stand — `login.php` schon, aber ohne den Nebenaufruf ist die Seite nicht zu benutzen; die Wartungsprobe meldete dazu grün, weil sie das Formular sah und nicht seinen Weg. **Vier Backlog-Einträge stimmten nicht mehr** und sind beim Austragen berichtigt (97, 38, 93, 156). **Nr. 172 neu und bewusst offen gelassen:** Erwartung 15 der Wartungsprobe flackert (0/1/0 in drei Läufen, Zeitvergleich über 0,1 ms) — nicht stillschweigend gelockert, sondern mit Zahlen eingetragen |
| **42** | **12.09.2026** | **`main` ist weiter, als dieses Dokument sagte — elf Stellen berichtigt.** Gefunden beim Anlegen der Backlog-Runde: Ein Push auf `claude/go-bwucrx` legte den Zweig **neu** an, weil er nach dem Merge serverseitig gelöscht war. **Es steht nichts mehr zum Merge an.** Gemergt sind **9a am 08.09.2026** (PR #37, `f299bbf`), **S9 am 10.09.2026** (PR #38, `0143df3`) und **Web 19.1.1** am selben Tag (PR #39, `015b26f`). Der Kopf nannte `main` bei **Web 19.1.0, Uhr 3.0.2, Android 0.14.0** — nachgezählt am Stand: **19.1.1, 3.1.0, 0.15.0**; die Client-Stufen kamen mit 9a (Backlog 159/160) und waren nie eingetragen. Sechs weitere Stellen führten einen Merge als offen, der längst erfolgt ist (Fahrplan Zeile 8, die Prosa zu Schritt 8, 9 und zweimal 9a, die 9a-Zeile in Abschnitt 8), zwei Zeilen in Abschnitt 6 waren „fällig nach dem Merge von S9" statt schlicht **fällig**, und der Statusblock des S9-Konzepts sagte „offen ist nur der Merge". **Was wirklich offen ist, steht jetzt an einer Stelle:** `update.php` mit **drei** Migrationen (`rest_segments.created_at` aus 9a, dazu die beiden aus S9 — ein Aufruf verbucht alle), einmal Entsperren für den Anhebelauf, und drei Prüflisten (S9 mit 32 Punkten, 9a mit P-1 bis P-12, Korrektur 148/149 mit zweien). **Das ist die dritte Runde dieser Art in drei Tagen** (Fassungen 39, 41, 42), und die Ursache ist jedes Mal dieselbe: Der Merge geschieht außerhalb der Sitzung, die das Dokument schreibt. Gegenmittel ab sofort: **vor jeder Fassung den Stand von `origin/main` messen** — Versionsnummern aus `version.php`, `Const.mc` und `version.properties`, Merge-Commits aus `git log --merges` — statt ihn fortzuschreiben |
| **41** | **12.09.2026** | **Abschnitt 5 wieder deckungsgleich mit dem Backlog — und drei Punkte, die ihre Erledigung im eigenen Text trugen.** Beim Sichten der Backlog-Runde (Schritt 9) abgeglichen: Die Zuordnungstabelle führte **24 Zeilen** zu Punkten, die S9 und 9a längst erledigt haben (68, 70, 72, 101–107, 110, 127–138, 147 und Nr. 19), und **neun offene Punkte fehlten ganz** (153–158, 161, 169, 170) — die Nummern aus 9a und den späten S9-Paketen waren nie eingetragen worden. Beides berichtigt, die Zahlen stimmen jetzt maschinell überein (**72 = 72**). **Drei Backlog-Punkte standen unter *Offen*, obwohl ihr eigener Text die Erledigung nennt:** **Nr. 19** (`$title` in `einsatz_loeschen.php` — mit P3 gegenstandslos geworden, `grep -c '$title'` → **0**), **Nr. 159** (Uhr behandelt `400` nicht vertragsgemäß — erledigt mit **Uhr 3.1.0** am 08.09.2026) und **Nr. 160** (fortgesetzter Dienst ohne Datum — erledigt mit **Android 0.15.0** am selben Tag). Alle drei nach *Erledigt* verschoben; `CLAUDE.md` 2.4 verlangt das Verschieben, nicht nur den Vermerk. **Neu in Abschnitt 6:** die Entscheidung zu **Nr. 169** (Besatzung an einem Tag mit „Anderem Rettungsmittel") — heute gilt Weg (c), und die Zeile steht dort, damit das ein gewählter Zustand bleibt und kein vergessener. Dazu eine Kleinigkeit: Nr. 8 war der einzige Eintrag der offenen Liste ohne Fettauszeichnung und fiel deshalb aus jeder Zählung — jetzt gesetzt |
| **40** | **10.09.2026** | **Nachtrag zu S9/AP7 — das Schloss an zwei Stellen** (Web **19.1.1**, Zweig `claude/go-bwucrx`, noch nicht auf `main`). Beide Lücken hat der Auftraggeber am Bildschirm gemeldet, **kein Prüfmittel**: In der Leseansicht trugen **7 von 8** Zeilen des entschlüsselten Blocks das Schloss, die **Einsatznummer** nicht (sie liegt seit Web 2.9.0 im `pat_blob`); im Formular trug die Karte „Notizen“ nur die Kartenzahl, weil AP7 das dort freistehende Zeichen entfernt hatte — ein Text ist aber kein Zeichen, wo „PatientIn“ daneben acht Schlösser zeigt. Behoben mit `dtGeschuetzt()` an der achten Zeile und einem neuen Schlüssel `geschuetzt` an `ui_karte_start()`, der das Schloss an den **Kartentitel** hängt (im `<h2>`, kein neuer Baustein). **Die Lehre steht in `CLAUDE.md` 4 und als Backlog Nr. 170:** AP7 hat gezählt, wie viele Zeichen stehen — eine Zählung ohne Sollmaß bestätigt sich selbst. Prüfzahlen: Leseansicht „PatientIn“ **2 von 2** mit Schloss (vorher 1 von 2), Formular **1 von 9** Kartentiteln (vorher 0 von 9) und 8 Feldbeschriftungen unverändert; Bilderlauf **360 Bilder / 45 Kontaktbögen, 0/0/0** je Bedienhöhe; Wortliste **0** bei 96 Regeln; Vollständigkeit **330 = 330**; Kontraste **22/0**; Linkprobe **116/0**. **Dabei gefunden:** Die Symboltabelle in `docs/Design.md` war seit längerem stale (`hinweis` 24 statt 30, `schloss` 9 statt 13, `standort` 22 statt 23) — alle vier erzeugten Tabellen sind neu eingesetzt |
| **39** | **10.09.2026** | **Stand nachgezogen, drei veraltete Aussagen berichtigt.** `main` trägt seit dem Merge von S9 (PR #38, `0143df3`) **Web 19.1.0**; der Kopf nannte noch 15.5.1 und einen ausstehenden Merge der Korrekturstufe. **Die Korrekturstufe Nr. 148/149 ist seit dem 06.09.2026 auf `main`** (PR #36) — Schritt 9 und zwei Zeilen in Abschnitt 6 sagten weiterhin „offen ist der Merge" bzw. „fällig, sobald 15.5.2 auf `main` ist". Beides steht jetzt richtig: offen ist allein die zweiteilige Prüfliste, und sie ist **fällig**. Gefunden auf Nachfrage des Auftraggebers, nicht von mir — Abschnitt 8 hatte ich in AP8 geschrieben, ohne die Zeile zu Schritt 9 gegenzulesen |
| **38** | **10.09.2026** | **S9 gebaut und geprüft (Schritt 8, Web 15.7.0 bis 19.1.0, Zweig `claude/go-bwucrx`).** Acht Arbeitspakete plus zwei Nachträge (AP4a, AP5b), Erledigt-Zeile in Abschnitt 8. **AP7** verschlüsselt die **Notizen des Einsatzes** — und deckte dabei zwei Stellen auf, an denen die Zusage schon vorher nicht stimmte: `api/suchindex.php` lieferte jede Notiz **im Klartext** für den gesamten aktiven Bestand, ohne Entsperren (31 Schlüssel je Einsatz vorher, 30 nachher), und **E6 „Administration sieht keinen Klartext"** war unwahr, weil `notes` in der Spaltenliste des Adminpakets stand. `CLAUDE.md` Abschnitt 4 und `docs/Technik.md` 4.98 nennen die Notizen jetzt; in 4.98 fehlten sie bis dahin in **beiden** Listen. **AP5b** hat die zentralen Stammdaten ihrer Oberfläche beraubt (R39, Web 18.0.0) — der Schemarückbau bleibt P5 (Nr. 168). **Backlog:** neun Punkte nach *Erledigt* (44, 69, 108, 109, 111, 112, 113, 132, 152) — das Konzept sagte „zwanzig", elf davon hatten AP1 bis AP7 unterwegs schon verschoben; Abschnitt 5 bereinigt, dabei eine **Dopplung von Nr. 69** entfernt. Neu: 163 (gegenstandslos), 166 (zurückgezogen), 167, 168, 169. **Prüfzahlen:** Klickprobe 80/80 als Zeiger- und 80/80 als Fingergerät, Bilderlauf 360 Bilder und 45 Kontaktbögen je Bedienhöhe mit 0/0/0, Kreisläufe CSV 9120/0 und Sicherung 287 842/0, Referenzbestand 283 989 und 5961 Einzelprüfungen ohne Befund, Wortliste 0 bei 96 Regeln, Kontraste 22/0, Linkprobe 116/0, Vollständigkeit 330. **Drei Funde hätten Daten gekostet** und sind behoben: F-S9-U-34 (stiller Verlust von Besatzungsnamen), `readField()` (hätte eine verschlüsselte Notiz beim Speichern gelöscht) und der fehlende `case` in `import.js` (114 Notizen im CSV-Umlauf). Offen: der **Merge auf `main`** nach Freigabe, danach **`update.php`** |
| **37** | **09.09.2026** | **Zwischenstand S9** (AP1 bis AP6, Web 15.7.0 bis 18.1.0): Frage 10 entschieden (Weg c — die Tür zu den zentralen Stammdaten wird geschlossen, der Schemarückbau bleibt P5), Frage 11 entschieden (Weg a — die Leseansicht berichtet den Bestand; das Einsatzformular zieht nach). Fahrplan-Dopplung der Schritte 8, 9 und 9a entfernt |
| **36** | **07.09.2026** | **Schritt 9a, Android-Teil gebaut und geprüft (Android 0.14.0)** — damit ist 9a vollständig gebaut, Erledigt-Zeile in Abschnitt 8. Fünf Punkte, fünf Commits: HTTP-Ausnahme nur im Prüf-APK, Klartextverbot im Release (142); abgewiesene Pakete nach 30 Tagen und beim Trennen geräumt, `dienst`-Zeilen mit ihnen (114 Räumteil); Entscheidung gegen Certificate Pinning in `android/LIESMICH.md` (143); Data-Layer-Empfang prüft `sourceNodeId` gegen die verbundenen Knoten und die Zeit der Uhr gegen Dienstfenster und Gegenwart (144); `distributionSha256Sum` im Gradle-Wrapper, aus zwei Wegen belegt (145). **Zwei gewählte Zahlen** mit Begründung am Code: 30 Tage, fünf Minuten; die Schnittstelle `Nachrichtenweg` bleibt unverändert. **Backlog:** 142–145 nach *Erledigt*, 114 bleibt offen mit dem Vermerk „Räumteil erledigt"; Abschnitt 5 bereinigt. Prüfzahlen `./gradlew build` grün — Handy **261 Prüffälle je Bauart** (Debug und Release; vorher 247), **0 Fehlschläge**, 15 übersprungen (14 Rundlauf ohne Installation und der jeweils bauartfremde Fall aus Nr. 142); Uhr **71 Prüffälle**, 0 übersprungen; Lint **0 Fehler** (Handy 13 Warnungen, unverändert die `libs.versions.toml`-Hinweise; Uhr 0); Release-APK Handy **7 867 394 B** (+332 B gegen 0.13.0), Uhr **19 574 406 B** (unverändert); Bilderlauf 72 Bilder wie zuvor; Emulator Stufe II erreicht im fünften Anlauf: Boot 715 s, Kopplung, Einstellungen und Trennen mit acht Bildern, Räumlauf am echten Android-SQLite 1 → 0 in allen vier Tabellen, Gerät am Server gelöscht; vier Anläufe ohne Boot wegen des Android-Watchdogs unter TCG, Gegenmittel `ro.hw_timeout_multiplier` als Root (F-SP-P-07); Wear-Emulator nicht gefahren. Offen: der Merge auf `main` nach Freigabe |
| **35** | **07.09.2026** | **Schritt 9a, Web-Teil gebaut und geprüft (Web 15.6.0, Zweig `claude/sofortpaket-sicherheit-1t70p1`).** Elf Punkte aus dem Krypto-Review in vierzehn Commits (drei Nachbesserungen): Rundenzahl 600 000 und Passwortregeln (136), Login-CSRF (127), E-Mail-Wechsel mit Nachweis samt Hinweismail an die alte Adresse (128), `apk/` und `demo/` gesperrt (129), DOCTYPE-Sperre des GPX-Imports gegen UTF-16 (130), `wiederherstellen.php` ohne Auskunft (131), Bauordner des Komplettbackups (133), **Ersetzfenster 72 h** (134), `json_js()` an 44 Stellen (135), **Weg C** in vier Dokumenten (138) und die **Integritätswache** als tägliche Action (140). **Zwei Funde beim Bauen**, beide in 136 behoben und beide von derselben Art — eine Maßnahme, die nie ausgelöst hat, ist nicht geprüft: Die stille Anhebung lief nicht beim nächsten Anmelden (sie braucht `CSRF`, das nur drei von sieben Seiten ausgaben), und die Wartungsseite meldete nur verwaiste Rundenzahlen statt der Zahl, die sagt, wann der Altwert weg darf. **Eine Regeländerung mit Ansage:** Die Sperrliste der Passwortprüfung rechnet den Anteil statt des Vorkommens, sonst widerspräche die Passphrasen-Empfehlung der eigenen Prüfung. **Neu:** `tools/integritaetswache/`, `tools/gpxprobe/` Teil 8, `tools/ingestprobe/` Teil 9 (dazu ihre Zeitstempel von festen März-Daten auf `time()`), `tools/wartungsprobe/` 12a. **Backlog:** zehn Punkte nach *Erledigt*, 140 bleibt offen (Wache steht; Branch-Schutz/2FA sind Zuarbeit, Deploy-Tor ist S10), **153 neu** (`querySelector` aus 135 herausgelöst). Offen: der Android-Teil und danach der Merge. |
| **34** | **07.09.2026** | **S9-Konzept liegt vor und ist freigegeben; 148/149 gemergt.** Korrekturstufe Web 15.5.2 am 06.09.2026 als PR #36 auf `main` (Prüfliste des Auftraggebers offen); Schritt 9a noch nicht begonnen. **S9-Konzept:** Fable im Projektraum, 06./07.09.2026: `docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md` mit E-S9-01 bis -19, acht Arbeitspaketen, 33 Prüfpunkten mit Sollzahl und sieben freigegebenen Mockups (`konzept-s9/mockups/`, mit LIESMICH zur Herkunft der Zeichen und Kacheln). Der Zielkonflikt PS-8.2 ist am Code aufgelöst (Suche im Browser, Notizen in den `pat_blob`); PS-2 hat eine Ursache (`click` gegen 150-ms-Blur), PS-3 eine Zahl (Doppelring 60 px → 38), Nr. 72 ist belegt. **Dazu genommen:** Nr. 44, 68, 69, 70, 72 (06.09.) und **PS-12 Standortseiten** als **Nr. 152** (07.09.): „Rettungsmittel" entfällt als Menüpunkt, „Standorte" wird Liste und Seite je Standort mit Kennzahlen, Dialogen und Landung auf der neuen Zeile. **R79** (Geocoding abschaltbar je Installation und Konto, Dienstadresse als Einstellung). Vorgabe an 9a zu Nr. 137 und 132 im Block Schritt 9a; Abschnitt 4 um die Berührung S9/S11 ergänzt; Backlog: 152 neu, Vermerke „Konzept S9" an achtzehn Einträgen. **Beschluss am selben Tag:** Nr. 137 und 132 ganz nach S9 (E-S9-05, E-S9-02), weil 9a nicht begonnen hatte — Sperre S9/9a aufgehoben, beide parallel; S9-Umsetzung beauftragt (`Prompt-Umsetzung-S9.md`), 9a um zwei Punkte leichter |
| **33** | **06.09.2026** | **Korrekturstufe Nr. 148 und 149 gebaut (Web 15.5.2, Zweig `claude/backlog-148-149-web-md24ve`).** Nr. 148: `?ziel=` → `?d=` in `index.php` — eine Zeile —, dazu **`tools/linkprobe/`** als das Prüfmittel, das der Punkt verlangt hat (99 Zielseiten, 132 Verweise, 0 unbekannte Abweichungen; die Gegenprobe gegen den alten Stand meldet die Zeile). Nr. 148 ist damit im Browser belegt: vorher HTTP 404, nachher HTTP 200. Nr. 149 (b): eigener Anzeigestatus `skip`, Plakette „nicht nötig", die Zeile steht unter *Ausstehend*, der Knopf ist da — Status, Menü und Seite nennen dieselbe Zahl; `tools/wartungsprobe/` bekommt **Teil 6** (50 statt 43 Erwartungen, gegen den alten Stand 4 von 7 rot). Zwei Funde, die der Auftrag nicht nannte und die mitbehoben sind: Die Seite meldete nach dem Knopfdruck „Es war nichts anzuwenden", obwohl der Vermerk gerade geschrieben wurde, und die Karte „Ausgeführt" zählte 43 gegen 42 verbuchte. Nr. 149 (a) ist **keine Codeänderung**: Der phpMyAdmin-Notweg und die Regel — *eine Migration, die Rechte einführt, muss ohne diese Rechte ausführbar sein* — stehen im Runbook (`Technik.md` 7) und werden in P5 (Support-Rolle) und P6 (Bedrohungsmodell) wieder aufgerufen. **Zwei neue Backlog-Nummern:** 150 (Cron-Befehl mit dem Repositoriumspfad, vom Auftraggeber) und 151 (`index.php?day=` nach dem Import — von der neuen Linkprobe gefunden, nach K4 nicht mitbehoben). **Berichtigt:** Abschnitt 2.2 nannte `update.php` als zweite Migrationsliste; sie liegt seit Web 15.1.0 in `server/migration_lib.php`. Offen: der Merge auf `main` (deployt sofort) und die zwei Punkte der Prüfliste in `docs/konzepte/Pruefdokument-Korrektur-148-149.md` |
| **32** | **06.09.2026** | **Stand auf `main` gezogen, zwei Funde, S9 frei.** Der Kopf nannte seit Fassung 25 „Web 13.2.0", einen ausstehenden Push für Schritt 6, ein ungemergtes Paket E und vier wartende Migrationen — tatsächlich sind Paket E (PR #31, 03.09.), Schritt 6 Teile A–C (PR #33, 04.09.), Uhr 3.0.1/3.0.2 (PR #34, 05.09.) und S8 (PR #35, 06.09.) gemergt, `update.php` lief am 04.09. um 23:15, und `main` trägt Web 15.5.1, Uhr 3.0.2, Android 0.13.0. Erledigt-Zeilen für Paket E, den S4-Rest und die Uhr-Korrekturen nachgetragen (Abschnitt 8), Sperren in Abschnitt 4 erfüllt, Abschnitt 5 bereinigt (dreizehn erledigte Nummern raus, 90–92 und 114–116 rein, 115 in 95 aufgegangen), R45/R57/R63/R64 im Register nachgeführt. **Zwei Funde am Produktivstand:** die Rollenmigration aus S8 war im Web nicht ausführbar — Betrieb → Updates verlangt die Rolle, die sie erst vergibt; gelöst per phpMyAdmin, und seither zählen Updates-Seite und Status die Migration verschieden (**Nr. 149**, Regel: eine Migration, die Rechte einführt, muss ohne sie laufen); der Knopf „Diensttage zusammenführen" in der R57-Warnung führt auf 404 (**Nr. 148**, `?ziel=` statt `?d=`). Beide als eine Korrekturstufe vor Schritt 9a, Auftrag liegt vor. **Nr. 147** angelegt: die aufgezeichnete Spur im Kartendialog der Einsatzbearbeitung, S9 (PS-11). **Zuarbeit F3–F6 zu PS-3 erledigt** — und PS-3 richtiggestellt: gemeint sind die Kartenschilder, nicht Formularknöpfe; Screenshots in `docs/konzepte/vorbereitung-s9/`. Backlog: zwölf Verweise „R74" auf den Krypto-Review zu **R78** berichtigt (Rest der Umnummerierung aus Fassung 31), 78 und 79 nach *Erledigt*, S8-Prüfdokument um einen Nachtrag ergänzt. Nächster Schritt: Korrekturstufe, dann 9a; parallel das S9-Konzept |
| **31** | **06.09.2026** | **S8 erledigt** (Schritt 7, Web 15.0.0 bis 15.5.1, Zweig `claude/umsetzung-buuvfq`): drei Menüblöcke aus einer Quelle, dritte Rolle **BetreiberIn** (R75), die Seite „Wartung" aufgelöst in fünf Betriebsseiten, **Betrieb → Status** als Ampel und Prüfstelle nach dem Deploy, **zwei Bedienhöhen** (R76), Handbuch neu gegliedert (Kapitel 11 Verwaltung, Kapitel 12 Betrieb). Fünfzehn Fehlerfunde, alle behoben. Konzept nach R62 gelöscht, Prüfdokument bleibt. **Zugleich der Merge des Sicherheitszweigs** `claude/crypto-security-review-eee2et`: Backlog **117–136 → 127–146** und **R74 → R78** umnummeriert, weil beide Zweige dieselben Nummern vergeben hatten und die S8-Nummern bereits in ausgelieferten Changelog-Einträgen und zwölf Server-Dateien stehen. Der Auftrag für Schritt 9a lautet damit **Backlog 127 bis 138 und 140** |
