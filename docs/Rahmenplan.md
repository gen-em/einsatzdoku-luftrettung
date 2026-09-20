# Rahmenplan — Programm „Gen-EM NAdoku" bis v1.0

**Fassung 90 (20.09.2026)** — dieses Dokument steuert das Programm:
Reihenfolge, Status, programmweite Entscheidungen. Es hält nur, was für die
nächsten Schritte gebraucht wird. Der eingefrorene Altbestand — die
Fassungsvermerke, die Phasentexte mit ihren Umsetzungsblöcken und die 50
Programmentscheidungen im Volltext — liegt **wörtlich und unverändert** in
`docs/Rahmenplan-Archiv.md`. Verweise aus älteren Dokumenten auf „Rahmenplan
Abschnitt 5" oder „Fassung 13" meinen das Archiv; sein Kopf sagt, welcher
alte Abschnitt wo weiterlebt.

**Stand am 20.09.2026, gemessen an `origin/main` (`7150793`):** **Web
20.24.2**, **Uhr 3.1.0**, **Android 0.15.0**. **Schritt 10b (P5b) ist
gemergt** (PR #57, `eec41e1`, 18.09.2026 — alle zehn Arbeitspakete, Web
20.15.3 bis 20.24.0); darauf folgten auf `main` noch **Web 20.24.1** (PR #58,
Anmeldung im Migrationsfenster) und **Web 20.24.2** (PR #59, zwei
Rechtstext-Überläufe und die Kettenwartung an `actions/checkout`).
**Schritt 10a (P5a) war am 16.09.2026 gemergt** (PR #50, `14f99ac` — zwölf
Arbeitspakete, Uhr und Android unberührt); zuvor PR #49 (`ee6d0b2`), der die
Auslieferungskette aus AP1 vorgezogen hat — seither geht ein Push auf `main`
auf **Staging** und nicht mehr auf Produktiv. Davor: **Schritt 9d
(Demo-Ausbau)** gemergt (PR #48, `7f334cb`, 15.09.2026 — Web 20.3.0, keine
Migration).

> **Zwei Zweige tragen den Rahmenplan, und `main` ist der ältere.** Solange
> Kette II nicht gemergt ist, liegen die Steuerungsdokumente auf
> `claude/fervent-dirac-xirsqw`; **wer von `main` aus arbeitet — die Schritte
> 16, 15 und 10c — liest Rahmenplan und Backlog vom Zweig**, nicht von
> `main`. Dort steht Fassung **80**. Derselbe Fall wie beim P5a-Zweig am
> 16.09.2026. Fassungen und Backlog-Nummern vergibt bis zum Merge **nur die
> Kette-II-Instanz**; die Konzeptinstanz liefert Änderungslisten.

> **Dieser Absatz stand bis Fassung 80 auf „Schritt 10b liegt zum Merge
> bereit" — zwei Tage nach dem Merge.** Aufgefallen ist es beim Nachmessen
> für Fassung 81 (Abschnitt 9: *„Der Stand von `main` wird GEMESSEN, nicht
> fortgeschrieben"*); es ist derselbe Fall wie in den Fassungen 39, 41 und
> 42, den die Regel dort beschreibt. **Gemessen ist nur der Stand.** Nicht
> geschrieben sind die **Erledigt-Zeile für P5b in Abschnitt 8** und die
> Nachzüge in den Abschnitten 3, 5 und 6 — die gehören zum Abschluss von
> P5b, nicht zu dem Arbeitspaket, das diesen Stand gemessen hat (Fassung 81
> ist der Staging-Umzug, Kette II/AP1). **Wer P5b abschließt, fängt hier an.**

**Produktiv läuft seit dem 20.09.2026 auf Web 20.24.2** — von Hand
hochgeladen, nicht über die Kette (E-KH-03). Einen erfolgreichen Lauf des
Jobs `produktion` gibt es bis heute nicht; was dem im Weg steht, arbeitet
**Konzept Kette II** ab (`docs/konzepte/Konzept-Kette-Haertung.md`).

**`update.php` ist nach dem Merge fällig** — P5a bringt sieben Migrationen
mit (`csp_berichte`, `job_laeufe`, `mail_warteschlange`,
`ratenschutz_stufen`, `sicherheit_ereignisse`, `geraet_abgewiesen`,
`sicherungsziel_aufbewahrung`), P5b sechs weitere
(`protokoll_ereignisse`, `konto_lebenszyklus`, `einwilligungen`,
`adresswechsel_bestaetigt`, `konto_grenzen`, `erststart_rueckfragen`).

> **Drei Nummernkollisionen hat dieser Merge auflösen müssen**, und alle drei
> nach derselben Regel: `main` ist vorgelagert, also weicht der Zweig.
> **Web 20.16.0** hatte der Zweig am 16.09. für P5b/AP1 vergeben, `main` am
> 17.09. für die Job-Pause — AP1 heißt jetzt **20.16.5**. **Backlog 215 bis
> 222** hatten beide Seiten parallel vergeben; die elf Punkte des Zweigs
> tragen jetzt **223 bis 233**. **Rahmenplan-Fassung 77** ebenso: `main`
> behält sie, die beiden Fassungen des Zweigs sind **78 und 79**. Wer einen
> Commit vor diesem Merge liest, liest die alten Zahlen.

**Der Kopf dieses Dokuments stand bis Fassung 77 auf Fassung 74, während
Abschnitt 10 bereits bis 76 reichte** — und der Stand-Absatz nannte `main`
bei `7f334cb` / Web 20.3.0, also zwölf Auslieferungen zurück. Drei Angaben
im Steuerungsdokument, alle überholt, alle an der Stelle, an der eine neue
Instanz zuerst hinsieht. Nachgemessen und berichtigt am 16.09.2026
(Abschnitt 9, Regel aus Fassung 42). **Wer eine Zeile in Abschnitt 10
schreibt, schreibt die Fassungsnummer im Kopf mit**; sonst ist die Zahl dort
das Alter der letzten Instanz, die daran gedacht hat.

Fassung 67
maß `main` noch bei `99d318a` / Web 20.2.1 und führte 9d als „steht zum
Merge"; nachgemessen am 15.09.2026 und hier berichtigt (Abschnitt 9, Regel
aus Fassung 42). **Der R42-Nachlauf ist gemergt** (PR #47, `99d318a`,
Fassung 66 — nur `docs/`, keine Versionsstufe, keine Migration); er hat die
Backlog-Nummern **190 bis 196** vergeben.
Die Backlog-Runde 3 ist **gemergt** (PR #43, `8f1712c`), und die
**Mockup-Runde 9c** ebenfalls (PR #44, `3886e26`, Web 19.4.0–19.6.0, Uhr
und Android unberührt, keine Migration) — Fassung 59 führte sie noch als
„zum Merge stehend" und nannte `main` bei Web 19.3.1; beides ist am
14.09.2026 an `origin/main` **nachgemessen** und hier berichtigt (Abschnitt
9, Regel aus Fassung 42). **Schritt 9b (S10 Sicherheit) ist am 14.09.2026
gemergt** (PR #45, `965ec11`, Web **19.7.0 bis 20.2.1**, Uhr und Android
unberührt) — **ohne Migration**, `update.php` ist also *nicht* fällig;
nachgemessen am Umfang des Merges, der keine Datei unter `migrationen/` und
weder `update.php` noch `schema.sql` anfasst. **Fällig sind dagegen die fünf
Betriebsposten und die Prüfliste P-01 bis P-14** (Abschnitt 6) — ohne den
ersten Posten, das Anlegen des Server-Anteils, arbeitet die Anwendung
unverändert ohne Anteil weiter.

**Was der Merge von 9d bringt:** Der Referenzbestand wächst von 16 auf 21
Diensttage und von 88 auf 106 Einsätze und zeigt damit die drei
Rettungsmittel-Typen aus S9 im Betrieb; die eine Regeländerung in `server/`
erlaubt Fähigkeiten beim Typ Bergwacht **in beiden Betriebsarten** (R80).
Der Merge bringt `server/demo/fixture.json.gz` mit — das Demo-Konto zeigt
den neuen Bestand erst nach einem **Zurücksetzen** im Adminbereich; dieser
Griff, die Prüfliste und die Freigabe des Abschlusses sind seit dem Merge
**fällig** (Abschnitt 6). Der zusammengeführte Stand ist vor dem Merge
unabhängig gegengelesen worden (21 Befunde, 15 bestätigt, elf behoben; drei
ältere ins Backlog, darunter **Nr. 199**) — Zahlen in Abschnitt 8.

**Beide Zweige haben dieselben Nummern vergeben, und das ist beim
Zusammenführen aufgefallen, nicht danach** (15.09.2026): Der Demo-Ausbau
hatte **190** und **191** belegt und diese Fassung **66** genannt — dieselben
drei Nummern wie der R42-Nachlauf, der zuerst auf `main` war. Der Demo-Ausbau
ist deshalb auf **197**, **198** und Fassung **67** nachgezogen worden, an
allen Fundstellen in Code und Dokumentation. **Das ist die zweite Kollision
dieser Art innerhalb von zwei Tagen** (die erste steht in der Fassung-66-Zeile
in Abschnitt 10, dort noch vor dem Commit bemerkt) und derselbe Grund wie
immer: Wer auf einem Zweig eine Nummer vergibt, ohne den Stand von `main` zu
kennen, vergibt sie zweimal. Die Nummer steht hier, nicht im Zweig — und
„hier" heißt **`origin/main`**, nicht der eigene Arbeitsstand.

Die jüngsten Merges in der Reihenfolge, in der sie liefen: die
Korrekturstufe **Nr. 148 und 149** am 06.09.2026 (PR #36, Web 15.5.2),
**Schritt 9a** am 08.09.2026 (PR #37, `f299bbf`, Web 15.6.0 / Android
0.14.0, dazu Uhr 3.1.0 und Android 0.15.0 aus den Punkten 159/160), **S9**
am 10.09.2026 (PR #38, `0143df3`, Web 15.7.0–19.1.0) und **Web 19.1.1** am
selben Tag (PR #39, `015b26f`, das Schloss an Einsatznummer und
Notizen-Karte), dann die beiden **Backlog-Runden** am 12.09.2026: Runde 1
(PR #40, `1c9f826`, Web 19.1.2), Runde 2 (PR #41, `6f316ee`, Web 19.2.0 und
19.3.0) und deren zwei Doku-Nachträge ohne Stufe (PR #42, `dabd7a3`), dann
die **Backlog-Runde 3** am 13.09.2026 (PR #43, `8f1712c`, Web 19.3.1),
die **Mockup-Runde 9c** am 14.09.2026 (PR #44, `3886e26`, Web 19.6.0),
**S10** am selben Tag (PR #45, `965ec11`, Web 19.7.0–20.2.1) mit seinem
Doku-Nachtrag ohne Stufe (PR #46, `98d677d`, Fassungen 63 bis 65), dann am
15.09.2026 der **R42-Nachlauf** (PR #47, `99d318a`, nur `docs/`) und der
**Demo-Ausbau 9d** (PR #48, `7f334cb`, Web 20.3.0).

**Was daraus für den Betrieb folgt, ist fällig und nicht erledigt** — vier
Dinge; alles Weitere steht in Abschnitt 6:

**`update.php` aufrufen.** Sechs Migrationen sind verbucht: die vier aus S4,
S6 und S5 sowie `2026_09_04_herkunft_geraet` am 04.09.2026 um 23:15, und
`2026_09_05_rolle_betreiberin` am 06.09.2026 **von Hand über phpMyAdmin** —
der Web-Weg war versperrt (Nr. 149: Die Seite, die Migrationen ausführt,
verlangt die Rolle, die erst diese Migration vergibt). Notweg und Regel
dahinter stehen im Runbook. **Drei stehen offen:**
`rest_segments.created_at` aus 9a sowie `2026_09_07_adresssuche_konto` und
`2026_09_07_rettungsmittel_typ` aus S9.

**Einmal „Ausstehende ausführen" drücken** (Betrieb → Updates). Der
Handeingriff vom 06.09.2026 hat keinen Register-Vermerk hinterlassen; seit
Web 15.5.2 steht `2026_09_05_rolle_betreiberin` dort unter *Ausstehend* mit
der Plakette „nicht nötig", und der Knopf verbucht sie als `skipped`. Danach
verschwindet der Zähler an Status und Menü.

**Einmal anmelden und entsperren.** Das startet den Anhebelauf, der die
Notizen des Einsatzes aus der Klartextspalte in den verschlüsselten Block
zieht (Web 19.0.3).

**Die vier Prüflisten der zuletzt gemergten Pakete abarbeiten**, alle in
`docs/konzepte/`: **S9** mit 32 Punkten, **9a** mit P-1 bis P-12,
**Korrektur 148/149** mit zweien und **Backlog-Runde 2** mit sieben. Die
älteren Prüflisten (S2, S4, S5, S7, S8) stehen ebenfalls offen; sie hängen
an keinem frischen Merge und deshalb nur in Abschnitt 6.

**Als Nächstes** steht in Abschnitt 3 der **Schritt 10 (P5
Dienstbetrieb)** an — seit dem 15.09.2026 **in drei Teilen** (R82: 10a
Kette und Fundament, 10b Konto und Registrierung, 10c Rollen, Sicherheit
und Betriebslage), und zwar zuerst das **Konzept zu 10a** (Fable) — es
ist am 15.09.2026 freigegeben worden und **seit dem
16.09.2026 umgesetzt und abgenommen** (das Konzept ist nach der Freigabe
gelöscht, Einzelheiten in **Abschnitt 8**): zwölf Arbeitspakete plus den Nachtrag AP4a,
Web 20.4.0 bis **20.15.0**, auf dem Zweig `claude/butte-umsetzen-5opi9u`.
**Offen sind der Merge nach `main` und der Auslieferungs-Tag** — beides
braucht die ausdrückliche Freigabe (K7) — **und die Zuarbeiten aus
Abschnitt 6a**: Staging steht und ist eingerichtet, die Umgebung
`produktion` ist angelegt — **offen ist der Zweigschutz** (`main` trägt
`protected: false`, gemessen am 16.09.2026) und das Repositoriums-Secret
`CIQ_GERAETE_URL`, ohne das Stufe 1 die Uhr überspringt. Bis dahin ist die
Kette gebaut, aber nur zur Hälfte gelaufen. Seine
Voraussetzungen im Fahrplan — die Schritte 2, 5, 7 und 9b — sind erfüllt,
und die Runden 9, 9c und 9d, die vor P5 lagen, sind gemergt. **Zwei
Zuarbeiten gehören davor** (Abschnitt 6, „vor Schritt 10"): Die
**Hosting-Entscheidung** ist am 15.09.2026 gefallen — **R81**, der Betrieb
bleibt beim jetzigen Hoster, die Anforderungen werden aber **hosterneutral**
als Plattformprofil in zwei Stufen festgelegt
(`docs/konzepte/Vorbereitung-P5-Plattformprofil.md`). Das **Staging-Ziel**
(R67) ist am selben Tag festgelegt worden — damals
`staging.nadoku.gen-em.org`, gleicher Hoster und Tarif, Absender
`staging@gen-em.org` (Vorbereitung PP-9, E-PP-09, eigenes SFTP-Backup-Ziel).
**Seit dem 20.09.2026 gilt E-KH-04:** Staging liegt unter
`staging-nadoku.gen-em.org` bei **lima-city**, also bei einem **anderen**
Hoster als Produktiv — E-PP-09 ist damit in Adresse und Hoster ersetzt, die
alte Anlage stillgelegt. Die Einrichtung steht in **Abschnitt 6a**. Damit
steht dem P5-Konzept keine Zuarbeit mehr im Weg. Die
Festlegungen für v1.0 sind entschieden (R65 bis R73; der Schnitt ist in
die drei Phasen P6, P7 und P8 geteilt, R71). Von den Zuarbeiten hat die
**D-U-N-S-Nummer** für das Play-Console-Organisationskonto die längste
Vorlaufzeit — bis zu vier Wochen (R65, Abschnitt 6).

**Seit dem 16.09.2026 dazu, dreierlei.** Erstens ein neuer **Schritt 15 —
Zentralisierung: eine Stelle je Sache** — rund 20 Muster verstreuten Codes
im Web-Teil, in sechs Paketen, mit der Programmentscheidung **R83**
(zentralisiert wird beim zweiten Verbraucher; Bibliothek statt Seite); er
läuft **zwischen 10a und 10b**, weil 10b und 10c sonst genau die Muster
neu kopierten, die er entfernt (Begründung in Abschnitt 3). Zweitens liegt
für **10c** eine **Vorbereitung zur Protokollierung** vor
(`docs/konzepte/Vorbereitung-P5c-Protokollierung.md`, auf dem P5a-Zweig)
mit neun Fragen V1–V9; **V1 ist am 16.09.2026 entschieden: Die Zusage
„kein Zugriffsprotokoll" bleibt** — der Bereich heißt Protokoll und führt
Betriebsereignisse, keine Datenzugriffe. Den Schreibweg (Tabelle,
`protokoll()`, Ereignisformat) legt das **10b-Konzept** als erstes Paket
fest, weil 10b vor 10c läuft und dessen Verwaltungsereignisse erzeugt; 10c
baut Reiter, Archiv und Download darauf. Drittens hat
die P5a-Instanz drei **Nachträge** bekommen (Mailrahmen und `app_url()` in
AP5, kein Empfänger im SMTP-Log, AP4a mit `use_strict_mode` und
`json_roh_out()` — Backlog 203–205), weil sie die betroffenen Dateien
ohnehin gerade umbaut. **Der P5a-Zweig ist seither der Zweig, auf dem die
Steuerungsdokumente gepflegt werden**, bis er auf `main` geht.

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

Schrittnummern sind **Namen**, keine Reihenfolge (Kopf, „Was hier steht");
sie werden nie umvergeben, weil Konzepte, Register und Erledigt-Zeilen sie
nennen. **Die Reihenfolge der offenen Schritte ist:** **Kette II** (läuft)
→ **16** → **15** → **10c** → **17** → **18** → 12 → 12a → 13 → 14.
Schritt 11 ist erledigt, 10a und 10b sind gemergt.

**Diese Reihenfolge ist mit dem Einschub vom 20.09.2026 neu gefasst.** Bis
dahin stand hier „10a (läuft) → 15 → 10b → 10c → …" — überholt, denn **10b
ist vor Schritt 15 gebaut worden** (PR #57, 18.09.2026). Schritt 15 misst
deshalb beim Konzept **10a und 10b gemeinsam** nach.

**Begründung der Lage der neuen Schritte:** **16** ist klein, und sein
Anlass liegt offen auf Staging — er steht **vor 15**, damit die
Sitzungsablage nicht mitten durch die Zentralisierung wandert. **15** steht
vor **10c**, damit 10c keine neuen Kopien der Muster aus Nr. 202 erzeugt.
**17** und **18** stehen vor P6 (12), damit der Review aufgeräumte und
gehärtete Seiten liest — wer P6 lieber früher will, zieht 17 und 18 hinter
12a. **Kette II** steht ganz vorn: Bis M1 ist die Kette nicht
auslieferungsfähig, und Schritt 15 soll nicht das erste Release über eine
ungeprobte Kette sein.

| Schritt | Kennung | Inhalt | Voraussetzung | Konzept | Modell | Status |
|---|---|---|---|---|---|---|
| 1 | **S4 — Merge** | Fehlerbehebung abschließen, Backlog-Nummern nachziehen, `main` holen, Merge = Deploy, `update.php` | — | liegt vor | Opus | **gemergt** (Web 12.8.0, Android 0.7.7 auf `main`); `update.php` für `2026_09_02_schnitte` vom Auftraggeber zu bestätigen; Prüfliste S4 (1, 2, 3, 5) offen |
| 2 | **S6 — Gerätekennung und Schlüsselfrist** | Serverseite von R42, Behebung R44 | Schritt 1 | keins; R42 und R44 sind die Spezifikation | Opus | **gemergt und ausgeliefert** (Web 12.9.0 bis 12.9.2 auf `main`); `update.php` für `2026_09_02_geraetekennung` und `…_geraetemodell_breiter` zu bestätigen; Abnahme nach Abschnitt 6 offen |
| 3 | **S5 — Kopplung umgekehrt, Konzept** | E-R49-1 bis E-R49-8 ausarbeiten | Schritt 2 | neu | **Fable** (R14) | erledigt 03.09.2026 — Freigabe mit E-S5-32 bis -47 |
| 4 | **S7 — Backup-Begriff** | Umstellung in einem Zug | Schritt 1; parallel zu 3 | gelöscht nach R62 | Opus | **erledigt, Web 12.9.3/12.9.4** (Abschnitt 8); **gemergt** (PR #28 `771808c` und PR #30 `b850871`, 03.09.2026 — berichtigt mit Fassung 47: hier stand zehn Tage nach dem Merge noch „PR gegen `main` offen"); Prüfliste S7 offen, sechs Punkte |
| 5 | **S5 — Umsetzung** | Server, Web, Uhr, Doku | Schritt 3; DNS `nadoku.gen-em.org` | aus Schritt 3 | Opus | **erledigt und gemergt** (Web 13.0.0–13.2.0, Uhr 3.0.0, PR #28/#29; **Paket E** Android 0.8.0–0.10.2, PR #31); Prüfliste S5 und Freigabe des Abschlusses offen (Abschnitt 6) |
| 6 | **S4 — Rest** | Kopplungsmodul, feste Server-Adresse, App-Name, Insets, Herkunft je Einsatz (R64), Gerätetest, **Play Console nach R65** (interner Test-Track für Handy und Uhr, Versionscode-Versatz, Signaturweg), Android 1.0.0 | Schritt 5 | Konzept S4, Abschnitt 13 | Opus | **gemergt** (PR #33, 04.09.2026; Web 13.3.0–14.2.2, Android 0.11.0–0.13.0): Teil A, Teil B (R64 und Nr. 63) und Teil C (Play-Console-Vorbereitung) sind auf `main`, `update.php` ist gelaufen. **Offen:** Gerätetest am S24 und mit der Wear-OS-Uhr (Backlog 81, Abschnitt 6), Android 1.0.0 nach E-R45-7, D-U-N-S und Signaturschlüssel für die Play Console |
| 7 | **S8 — Einstellungen, Administration und Wartung** | Sichtung und Neuordnung: Backup-Optionen, Menüstruktur, Aufteilung der Wartungsseite, Einzelpunkte 73–79 (R61); dazu die Rolle **BetreiberIn** (R75), der Block **Betrieb** mit sieben Seiten und das **Ordnungsprinzip** (R74) | Schritte 4 und 6 | **gelöscht nach R62** (Fable, 05.09.2026; E-S8-01 bis -18; die zwölf Mockups bleiben unter `docs/konzepte/konzept-s8/mockups/`, ein dreizehntes kam in der Umsetzung dazu; Historie `fc470b0`) | Fable (Konzept), Opus (Umsetzung) | **erledigt, Web 15.0.0 bis 15.5.1** (Abschnitt 8) — acht Arbeitspakete, Konzept nach R62 gelöscht, Prüfdokument bleibt |
| 8 | **S9 — Einsatzbearbeitung und Rettungsmittel** | Problemsammlung vom 03.09.2026 (Nr. 101–113): Adresssuche und gemeinsamer Kartendialog, Rettungsmittel-Übernahme, kompaktere Kartenschilder, Windenkacheln, Artzeichen, Vorschlagsliste, Transportziel ad hoc, Schloss-Kennzeichnung, Notizfeld verschlüsselt, „GPS-Daten", neue Rettungsmittel-Typen, Tageszuordnung, Rollen; dazu **Nr. 147** (Spur im Kartendialog), **Nr. 44, 68, 69, 70, 72** (06.09.2026) und **Nr. 152** (PS-12 Standortseiten, 07.09.2026) | Schritt 7 **erfüllt**; F3–F6 **beantwortet 06.09.2026**; **keine weitere** — Nr. 137/132 sind in S9 (07.09.2026) | **Konzept liegt vor und ist freigegeben** (Fable, 06./07.09.2026): `docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md`, E-S9-01 bis -19, sieben Mockups in `konzept-s9/mockups/` | Umsetzung Opus, acht Arbeitspakete, kein Fable-Schritt | **Gebaut und geprüft** (Zweig `claude/go-bwucrx`): **AP1 bis AP8 erledigt** (AP4a und AP5b eingeschlossen) 07.–10.09.2026, Web 15.7.0 bis **19.1.1**, Migrationen `2026_09_07_adresssuche_konto` und `2026_09_07_rettungsmittel_typ`. **Am 10.09.2026 auf `main` gemergt** (PR #38, `0143df3`), der Nachtrag Web 19.1.1 am selben Tag (PR #39, `015b26f`). **Fällig ist jetzt `update.php`** (die beiden Migrationen aus AP2 und AP4 — AP7 braucht keine) und die 32-Punkte-Prüfliste. Siehe Abschnitt 8 |
| 9 | **Backlog-Runde** | Einzelpunkte nach Abschnitt 5 | ab Schritt 1, parallel | keins | Opus | offen — die **Korrekturstufe mit Nr. 148 und 149** ist gebaut, geprüft und **am 06.09.2026 auf `main` gemergt** (Web 15.5.2, PR #36). Ihre **zweiteilige Prüfliste** in `docs/konzepte/Pruefdokument-Korrektur-148-149.md` ist seit dem Merge **fällig**. **Zwei Runden sind seither gebaut und gemergt** (berichtigt mit Fassung 47 — hier stand „offen ist allein die zweiteilige Prüfliste" und „die übrigen Einzelpunkte sind unangetastet", beides überholt): **Runde 1** am 12.09.2026 (PR #40, Web 19.1.2, sieben Punkte — 38, 93, 97, 151, 153, 156, 167; Abschnitt 8) und **Runde 2** am selben Tag (PR #41, Web 19.2.0 und 19.3.0, fünf Punkte — 118, 119, 120, 125, 126; Prüfdokument `Pruefdokument-Backlog-Runde-2.md` mit **sieben** offenen Punkten, ebenfalls fällig). **Runde 3 ist konzipiert** (13.09.2026, `docs/konzepte/Konzept-Backlog-Runde-3.md`, Prüfdokument-Vorlage daneben): Block A Nr. 91, 94, 117, 67-Unterpunkt, 41-Streichungen · Block B Nr. 47, 58 (Prüfgruppe „Zusagen" in `tools/vollstaendigkeit/`) · Block C Nr. 173, 174 (Referenzdateien neu) — keine Fable-Schritte, Umsetzung Opus |
| 9a | **Sofortpaket Sicherheit** (R78) | Web: Nr. 127–131, 133–136, 138 (Rundenzahl 600 000, Login-CSRF, E-Mail-Nachweis, Ordner, GPX, `wiederherstellen.php`, Bauordner, Ersetzfenster der Uhr, Maskierung, Weg C, Integritätswache aus Nr. 140) — **ohne 132 und 137, die sind seit dem 07.09.2026 in S9**; Android: Nr. 142–145 und der Räumteil von 114 | Web sofort; Android nach Schritt 6 auf `main` | keins; `docs/konzepte/Vorbereitung-Sicherheitspaket.md` ist die Spezifikation (Muster R42, Prüfdokument mit Zahlen) | Opus | **gebaut, geprüft und gegengeprüft (Web 15.6.0 und Android 0.14.0, 07.09.2026)** — Nachbesserungen nach der adversarischen Gegenprüfung committet, Zweig gepusht; **am 08.09.2026 auf `main` gemergt** (PR #37, Merge-Commit `f299bbf`), **danach `update.php`** |
| 9b | **S10 — Sicherheit** (R78) | Server-Anteil am Datenschlüssel mit Schlüsselblatt, Kennung und Rotation (SP-3); Adminpakete versiegeln, `ftp` abschaffen (Nr. 139) | Schritt 9a; **vor P5** (Hauptstufe, Umstellung aller Hüllen) | **gelöscht nach R62** (Fable, 13.09.2026; E-S10-01 bis -18, keine Mockups; Historie `a00f6b5`) | Fable (Konzept), Opus | **erledigt und gemergt (Web 19.7.0 bis 20.2.1, 14.09.2026, PR #45 `965ec11`)** — sechs Arbeitspakete, kein Fable-Schritt, **keine Migration**, `update.php` nicht fällig; Zahlen in Abschnitt 8. Offen sind die fünf Betriebsposten und die Prüfliste P-01 bis P-14 (Abschnitt 6) |
| 9c | **Mockup-Runde** | Vier Gestaltungsaufgaben, eine Freigaberunde statt vier (Entscheidung 15 vom 12.09.2026): **Nr. 41** zwei Regeln für die Importvorschau (`imp-warn`, `imp-daygroup`), **Nr. 42** `×` am Koordinaten-Chip (`.rmx` neu bemaßen) und `⚠` im Fließtext, **Nr. 45** dritte Kartengröße, **Nr. 124** Aktionsblatt nach Weg (b) — **vier Bauarten** von Öffnern: sechs `ui_aktionen()`, neun `ui_zeilenaktionen()`, der Pin-Knopf des Ortsfelds und drei handgeschriebene Sortierblatt-Knöpfe (nachgezählt 13.09.2026, berichtigt in AP4 — die Regel hängt am Attribut und erreicht alle) | keine; **ab jetzt, parallel** — die Runde braucht eine Freigabe und läuft deshalb früh, damit sie nicht am Ende wartet | keins; Mockups nach `Design.md` 1, Freigabe je Mockup | Fable (Mockups), Opus (Umsetzung) | **Freigegeben am 13.09.2026** (`docs/konzepte/Konzept-Mockup-Runde.md`, vier Mockups in vier Fassungen, F-MR-1 bis F-MR-13 beantwortet; darunter `--dauer` fuer die ganze Anwendung auf 240 ms). **AP1 erledigt** (Web 19.4.0, 13.09.2026): Nr. 41 ist beantwortet — `imp-daygroup` hat eine Regel, `imp-warn` ist gestrichen, `pruefen.py` meldet **0** `[offen]` statt 2. Dabei **Nr. 182** entstanden und mit **Web 19.4.1 gleich erledigt** (M-MR-05, F-MR-14 = Weg B): Die Kopfzeile nimmt ueber eine Container-Abfrage die sichtbare Breite an — **ohne JavaScript**. Weg C war zuerst gewaehlt und ist nach einer Kartierung mit **58 Befunden, 22 davon „bricht"**, verworfen worden. **Erledigt am 14.09.2026, Web 19.6.0.** AP2 (Nr. 42, 19.4.2) — die beiden Chips und der Satz der Meldung tragen Symbole, das Treffziel waechst von 17 x 15 auf 28 x 28 px; **AP3** (Nr. 45, 19.5.0) — die dritte Kartengroesse, ein Zustand mit zwei Wirkungen je Breite; **AP3b** (Nr. 183, 19.5.1) — Bilderlauf, Klickprobe und Stilvergleich fahren seither `--motor chromium|firefox|webkit`, und der erste dreifache Lauf brachte gleich zwei Befunde (Nr. 185, Nr. 186, beide erledigt); **AP4** (Nr. 124, 19.6.0) — das Blatt faehrt auf, der offene Oeffner ist orange hinterlegt, `--dauer` steht auf 240 ms. Zahlen in Abschnitt 8. **Drei Rueckfragen vor Beginn beantwortet** (13.09.2026): die Unicode-Pruefung bekommt eine Ausnahmeliste ueber `zusagen.md` statt des wirkungslosen Eintrags in `ausnahmen.md` (E-MR-24), die orange Markierung gilt fuer **alle** Blatt-Oeffner und nicht nur fuer die beiden Bausteine (E-MR-25), und Fehlerfund 1 laeuft in AP2 mit statt als eigener Backlog-Punkt. **Nr. 124 ist der einzige der vier aus einer Rueckmeldung von aussen** und darf die Runde verlassen, wenn sie ins Rutschen geraet. Ort begruendet in Fassung 46: vor P5, nicht in P7 |
| 9d | **Demo-Ausbau** | Referenzbestand und Demo-Konto um die S9-Typen im Betrieb erweitern: fünf Diensttage (Bergwachtnotarzt ×2, VEF Talwang, zwei Veranstaltungen), 16 Einsätze + 2 Schnitte, Standorte mit Koordinaten, Tage ohne Standort, Fußwege; dazu die Regeländerung **Fähigkeiten bei Typ Bergwacht in beiden Betriebsarten** (AP0, `server/`, keine Migration) | **Schritt 9b gemergt** — erfüllt am 14.09.2026; parallel zum P5-Konzept | `docs/konzepte/Konzept-Demo-Ausbau.md` (Fable, 15.09.2026; E-DA-01 bis E-DA-32) | Opus, kein Fable-Schritt | **Gebaut und geprüft** (Zweig `claude/umsetzung-ohne-pausen-2vfppr`): **AP0 bis AP4 erledigt** 14./15.09.2026, **Web 20.3.0**. Der Haltepunkt **H-DA-1 ist entfallen** — `router.project-osrm.org` war erreichbar, die Umsetzung hat die Strecken selbst geholt und eingecheckt (E-DA-22); **H-DA-3 nicht ausgelöst** (beide Kreisläufe 0 unerklärt). Zahlen in Abschnitt 8. **Am 15.09.2026 auf `main` gemergt** (PR #48, `7f334cb`; berichtigt mit Fassung 68 — hier stand „Offen: Merge"). **Offen:** Freigabe des Abschlusses, die Prüfliste (`docs/konzepte/Pruefdokument-Demo-Ausbau.md`) und der Demo-Reset (Abschnitt 6) |
| 10a | **P5a — Kette und Fundament** (R82) | `deploy.yml` nach R67 (Staging automatisch, Prüftor Stufen 1–2, Produktion mit Freigabe- und Backup-Tor); Plattformprüfung in `install.php` und Status (R81); Torwächter mit Wartungsmodus (R40.4, Nr. 54); Kopfzeilen aus PHP nach SP-5 (Nr. 8); Mail-Warteschlange; Mengenbremse `ingest.php` (R19, Nr. 17 — Grundsatzfrage zuerst); IP-Grenzwerte und vertrauenswürdige Proxys; 503-Weg an der Verbindungsgrenze; Job für Nr. 80; Aufbewahrung auf dem Sicherungsziel (Nr. 49); Nr. 37, 67, 195 | Schritte 2, 5, 7 und 9b **erfüllt**; Hosting-Entscheidung **gefallen 15.09.2026 (R81)**; Staging-Ziel **festgelegt 15.09.2026** (Einrichtung offen) | **nach der Freigabe gelöscht** (Fable, 15.09.2026, E-P5a-01 bis -58 — 37 davon in der Umsetzung dazugekommen, AP1–AP12 plus AP4a; Historie `bcbb04f`). **Das Prüfdokument bleibt**: `docs/konzepte/Pruefdokument-P5a-Kette-und-Fundament.md`, 33 Punkte. Vorbereitung `Vorbereitung-P5-Plattformprofil.md` (PP-1 bis PP-9, E-PP-01 bis -09) | Fable (Konzept), Umsetzung Opus (K2) | **ERLEDIGT UND GEMERGT — PR #50 am 16.09.2026** (`14f99ac`) (Web 20.4.0 bis **20.15.2**, zwölf Pakete plus den Nachtrag AP4a und zwei Nachträge aus der Durchsicht). Einzelheiten in **Abschnitt 8**. Der **Tag steht noch aus** — er ist die Auslieferung auf Produktiv und wird gesondert gesetzt. F-P5a-1 ist E-P5a-22 (Tag-Muster `web-vX.Y.Z`). **Zuarbeiten offen** (Abschnitt 6a): GitHub-Umgebung `produktion`, Zweigschutz `main`, Staging-Installation samt Prüfkonto — ohne sie ist die Kette gebaut, aber nicht gelaufen |
| Kette II | **Härtung der Auslieferungskette** | Zeiger-Zweig und Integritätswache (AP2), Tor/Zielprobe/Probelauf (AP3), F3 beheben (AP4), gemeinsame Schrittfolge (AP5), Abbruchverhalten und Härtung (AP6), Hotfix-Weg (AP7); Meilensteine **M1** (erster grüner Produktivlauf) und **M2** (Probe-Hotfix) | — | **liegt vor:** `docs/konzepte/Konzept-Kette-Haertung.md` (freigegeben 20.09.2026, E-KH-01 bis -24) | Opus | **läuft** — AP1 gebaut (Abnahme offen, hängt am Botschutz von lima-city), AP2 gebaut, **AP3 gebaut und am Pflichtstopp** (E-KH-09): Fünf Trennversuche gegen Produktiv haben F3 nicht benannt, aber sechs Vermutungen ausgeschlossen (Fassung 88). **Sieben Vermutungen zu F3 sind mit je einer Messung ausgeschlossen**, zuletzt die Menge (80 von 80 Verzeichnissen in EINER Sitzung, Fassung 90). Der Vorrat, den ein zweiter Client prüfen kann, ist erschöpft; der nächste Schritt ist eine **Entscheidung** — die Aktion selbst reden lassen (echter Auslieferungslauf) oder die Frage an den Hoster. **Prüfpunkt 18** des Prüfdokuments |
| 10b | **P5b — Konto und Registrierung** (R82) | Registrierung mit drei Betriebsarten und Sicherheitspaket; Konto-Lebenszyklus (Bibliothek, Kontostatus bis `ingest.php`, Double-Opt-In, Selbstlöschung mit Karenz, E-Mail-Wechsel, Einwilligungen mit Fassungskennung); Onboarding mit Notfallblatt; Geräteschlüssel auf SHA-256; Mengengrenze je Konto; Aufbewahrung je Konto (Nr. 48); Demo-Konto je Betriebsart (R25) | Schritt 10a **erfüllt** (am 16.09.2026 auf `main` gemergt, PR #50, `14f99ac`); **Schritt 15** war als zweite Voraussetzung der Umsetzung gesetzt und **ist offen** — die Umsetzung läuft seit dem 16.09.2026 trotzdem. Von Schritt 15 hat AP2 allein den **Token-Teil von Paket 1** (Nr. 202) mit erledigt; die übrigen Pakete stehen, die Abweichung von der Reihenfolge 10a → 15 → 10b bleibt also bestehen und ist in der Statusspalte begründet. Das Konzept durfte ohnehin parallel entstehen | **liegt vor und ist freigegeben** (Fable, 16.09.2026; Freigabe des Auftraggebers am selben Tag, **ohne Änderungen**): `docs/konzepte/Konzept-P5b-Konto-und-Registrierung.md`, **E-P5b-01 bis -24**, **AP1–AP10**, zwei Fable-Schritte (M-P5b-01 Dokumentseite, M-P5b-02 Registrierung/Erststart/Rückfrage/Notfallblatt). **Legt den Protokoll-Schreibweg als erstes Paket fest** (V1 entschieden 16.09.2026: Betriebsereignisse, keine Zugriffe). Prüfdokument daneben (`Pruefdokument-P5b-Konto-und-Registrierung.md`); Mockups in `docs/konzepte/konzept-p5b/mockups/` — **fünf Darstellungen** (M-P5b-01, -02a bis -02d), vier davon zusätzlich bei 376 px: **9 HTML, 9 PNG** und `LIESMICH.md` | Fable (Konzept), Umsetzung Opus; **die beiden Mockup-Schritte ebenfalls Fable.** Hier stand bis Fassung 78 „mit Opus statt Fable" — das galt für zwei Entwürfe, die dieser Zweig am 16.09.2026 auf Weisung selbst gebaut hat. Der Auftraggeber hat am 17.09.2026 die Fable-Fassung aus der Konzeptsitzung nachgereicht; die Opus-Entwürfe sind abgelöst und entfernt | **ERLEDIGT UND GEMERGT — PR #57, `eec41e1`, 18.09.2026** (Web 20.24.0, alle zehn Arbeitspakete, sechs Migrationen). Danach auf `main`: PR #58 (`7675f9b`, 20.24.1, Anmeldung im Migrationsfenster), PR #59 (`7150793`, 20.24.2, zwei Rechtstext-Überläufe und `actions/checkout@v7`, Nr. 235), PR #60 (`862ca7f`, **20.25.0**, Nr. 238 und 239: Schemaprobe gegen MySQL 8.4.0 und MariaDB 10.6, `missions.manual` → `uhr_gesperrt`, **mit Migration**). **`update.php` ist damit für sieben Migrationen fällig** (Abschnitt 6) |
| 10c | **P5c — Rollen, Sicherheit, Betriebslage** (R82) | Support-Rolle; **Zweitfaktor: Pflicht für Admin, BetreiberIn und Support, Angebot für alle übrigen** (Nr. 141, E-P5c-15, F-P5c-1); Protokoll-Oberfläche mit Reitern, versiegeltem Archiv, Download und Rechten — **Ort: Verwaltung → Protokoll** (V1–V9 entschieden, F-P5c-7); Ankündigungsbanner **und Umgebungsbanner** (Nr. 243, AP1); Fehlerprotokoll (`error_log()` → Reiter System, **77 Aufrufe in 32 Dateien**, nachgemessen 20.09.2026 an `862ca7f`; dazu Nr. 248); Health-Endpunkt; Betriebslage mit Geräteverteilung nach R64-Herkunft (R38, R42, Nr. 80 Rest, 191, 192 — **Nr. 122 NICHT**, sie geht nach Schritt 17, E-P5c-23); R39-Rest (Nr. 168, 169); **Aufräumen der Einstellungen** (Nr. 244, 245, 246, 121, 198 — eine Mockup-Runde als Fable-Schritt); Bounce-Postfach (Nr. 200) | **Schritte 16 und 15** | **liegt vor:** `docs/konzepte/Konzept-P5c-Rollen-Sicherheit-Betriebslage.md` (Fable, 20.09.2026, E-P5c-01 bis -24, F-P5c-1 bis -7, AP1–AP11, ein Fable-Schritt M-P5c-01) | Fable (Konzept), Opus (Umsetzung) | **Konzept freigegeben 20.09.2026**, Umsetzung offen |
| 11 | **Planung v1.0** | Festlegungen vor dem Schnitt: Store-Verteilung (R65), Update-Weg (R66), Auslieferungskette (R67), Repositorium (R68), Code-Review (R69), Web-App auf Android (R70), Phasenschnitt (R71), Doku-Anforderungen (R72), Problemsammlung (R73); Ergebnis sind die Konzepte der Phasen P6–P8 mit je eigenem Paketschnitt | Festlegungen: keine (vorgezogen); Paketschnitte: die jeweilige Vorphase, P6 nach der Freigaberunde des Reviews | `docs/konzepte/Konzept-Planung-v1.0.md` | Fable (R14) | **Festlegungen entschieden** 03.09.2026 (R65–R73); offen nur die Paketschnitte je Phasenkonzept |
| 12 | **P6 — Review und Bereinigung** | Bedrohungsmodell (Eingang: `Review-Krypto-Sicherheit.md`, R78); Bug- und Sicherheitsreview in zwölf Stücken (R17, R69); Freigaberunde; Sofortpaket; Pflicht- und Aufräumpakete; Kommentardurchgang (R13, R31); Fragen Nr. 146; R5-Ausnahmeliste | Schritte 8 und 10; Nr. 43-Fragen beantwortet (R78) | neu; `docs/konzepte/Review-R17.md` entsteht erst im Review als Sammelstelle der Funde, Paketschnitt nach der Freigaberunde | Fable (Review, Kryptographie), sonst Opus | offen |
| 12a | **S11 — Ortsdaten verschlüsselt (Weg B)** (R78) | Konto-Schlüsselpaar (Nr. 53); Uhr und Handy verschlüsseln Spur, Phasenkoordinaten, Reanimationsereignisse und Zielklinik vor dem Upload; serverseitige Spurfunktionen wandern in den Browser; Altbestand per Einmalwerkzeug (Nr. 43) | Schritt 12; **vor der Öffnung** — die Entscheidung zum Altbestand setzt ein einziges Konto voraus | neu, nach K1 (Skizze SP-9 in `Vorbereitung-Sicherheitspaket.md`) | Fable (Konzept), Opus | offen |
| 13 | **P7 — Gesicht v1.0** | Umbenennung überall, neues Demo-Passwort (R25); Vertrag v1 (R12, Nr. 23); Doku-Neufassung (R16, R72); Web-App-Manifest (R70; die Erhebung Nr. 87 ist seit 13.09.2026 ausgetragen); Changelog neu (R15); Backlog-Übernahme; Altformat der Sicherung abschaffen (Nr. 46); Kommentarregel `CLAUDE.md` (R69) | Schritt 12 | eigenes Konzept nach K1 | Opus | offen |
| 14 | **P8 — Schnitt** | Neuaufsetzen (R40 (3)); Migrationsregister neu (R66); Repo-Umzug und Inventur (R68); Kette im neuen Repositorium (R67, R40 (4)); Rechts- und Betreiberunterlagen (R41); Abnahme nach R11; Erklärung v1.0 | Schritt 13 | eigenes Konzept nach K1 | Opus | offen |
| 15 | **Zentralisierung — eine Stelle je Sache** (R83) | Rund 20 Muster verstreuten Codes im Web-Teil (`server/`, ohne `assets/vendor/`) nach dem Vorbild von `mission_fields.php` an je eine Stelle: sechs Pakete — Marke/Mail/Link/Token · Datenzugriff (`app_state`, `manual-<userId>`, Einsatz laden, `missions`-Spaltenlisten, Transaktionen, Kindtabellen) · API-Eingang/Sitzung/Flash · JavaScript (JSON-POST, Meldungs-Markup, Dauer/Datum, Karten-Präambel) · Zeit/Zahl/Migration · Beifang nur mit Arbeit an der Datei. Backlog **Nr. 202** (Sammelnummer) | **nach Kette II (bis M1)** und **nach Schritt 16**; misst beim Konzept **10a UND 10b gemeinsam** nach — 10b ist vor 15 gebaut worden | neu, nach K1 — misst nach dem Merge von 10a neu, weil die Zahlen Stand `main` 16.09.2026 sind und der P5a-Zweig Teile schon erledigt hat | Fable (Konzept), Opus (Umsetzung, kein Mockup) | offen — aufgenommen 16.09.2026; Lage mit dem Einschub vom 20.09.2026 neu gefasst. Dazu **Nr. 57** (die Tagesübersicht baut ihre Tabelle zweimal — eine Kopie, also Zentralisierung) |
| 16 | **Sitzungsablage** (Stufe 2, R81) | Die Anwendung legt ihre Sitzungsdateien selbst ab (`.sitzungen/`, `0700`), Rückfall mit Anzeige, vierter Schreibort und Prüfpunkt „Ablage auflistbar?“ in `plattform_pruefen()`, Aufräumjob, **achter Schutzlistenpfad** (bei Kette II angemeldet, wird dort in AP4 oder AP5 eingetragen). Backlog **Nr. 241** | keine; **parallel zu Kette II** auf eigenem Zweig von `main`, Merge nach `main` nur auf Ansage | **liegt vor:** `docs/konzepte/Konzept-Sitzungsablage.md` (Fable, 20.09.2026, E-SA-01 bis -09, ein Paket; E-SA-02 am selben Tag neu gefasst — neun Sitzungsstarts statt drei) | Opus, Nebennummer | **ERLEDIGT UND GEMERGT — PR #61 und #62, `1822d38`, 20.09.2026** (Web **20.26.0**). `server/sitzung_lib.php` legt `.sitzungen/` mit `0700` an, gerufen aus `db.php` und `install.php`; Rückfall auf den Hosterpfad **mit Anzeige**; vierter Schreibort und dreiwertiger Prüfpunkt „Sitzungsablage" in `plattform_pruefen()`; Aufräumteil „Sitzungsdateien" (nur `sess_*`). **Der achte Schutzlistenpfad ist mit Kette II/AP3 eingetragen.** PR #62 hat fünf Sätze berichtigt, die den Löschabgleich des Transports zu stark beschrieben hatten. **Offen: die Prüfung durch die Betreiberin** — `docs/konzepte/Pruefdokument-Sitzungsablage.md` |
| 17 | **Backlog-Runde 4** | Die kleinen Punkte, die seit Runde 3 liegen (Liste in Abschnitt 5) — Prüfmittel, Doku-Konsistenz, Streichlisten, `days.created_at`, Demo-Reset-Takt, Statistik-Rest (**Nr. 122**), Rahmenplan-Verlauf (Nr. 177, 196, 199 zusammen) | Merge von 10c | nach K1 wie Runde 3: kurzes Konzept mit Paketschnitt, kein Fable-Schritt | Opus | offen |
| 18 | **Sicherheitsrunde II** | Sitzungsbindung per Cookie-Token (**Nr. 242**), Serverschlüssel wechseln als Vorgang (**Nr. 247**), TOTP-Reset der einzigen BetreiberIn als Wiederanlauf-Fall (**Nr. 249**), Rest der Betreiber-Rückfrage (Nr. 233), `ingest.php`-Deadlock bei gleichzeitigen Uploads (Nr. 210); Nr. 228 (PoW im Browser) bleibt „nur auf Anlass“ und wird dort nur genannt | Merge von 17 | nach K1, Fable | Opus | offen |
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
  längste Eintrag hat 153 Zeichen, fünf der 173 Modelle liegen über 64.
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
03.09.2026 gemergt (PR #31, Android 0.8.0–0.10.2); es hing an keinem der
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

**Konzept:** abgearbeitet und nach der Freigabe gelöscht (06.09.2026,
R62/K9; in der Historie zuletzt unter Commit `fc470b0`, die Kurzfassung in
Abschnitt 8). Es kam von Fable am 05.09.2026 und trug die Entscheidungen
E-S8-01 bis E-S8-18. **Die Mockups bleiben:** 01 und 03–12 freigegeben, 02
verworfen, Ablage `docs/konzepte/konzept-s8/mockups/` — dort liegt seit AP5
auch ein dreizehntes (Symbole). Prüfdokument daneben (K9).
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

**Runde 3 ist gebaut (13.09.2026, Web 19.3.1, Zweig
`claude/backlog-runde-3-umsetzung-woqxjm`).** Erstmals mit Konzept — zehn
Arbeitspakete in drei Blöcken —, weil neun Punkte in einer Stufe zu viele
sind, um sie in einer Auftragszeile zu ordnen; die Erledigt-Zeile steht in
Abschnitt 8. **Sieben Punkte sind weg** (91, 94, 117, 47, 58, 173, 174),
**zwei bleiben mit Vermerk offen**: von **Nr. 67** ist der Unterpunkt
erledigt (die CSRF-Prüfung in `kdf_upgrade.php`), der Hauptpunkt gehört nach
P5; von **Nr. 41** sind drei der fünf Klassen gestrichen, die zwei
verbleibenden brauchen Regeln und damit die Mockup-Runde 9c.

**Die Runde bleibt offen.** Abschnitt 5 führt weiter Punkte, die keiner Phase
bedürfen; die nächste Runde nimmt sich die nächsten. Was diese Runde gelehrt
hat, steht in `CLAUDE.md` nicht als neue Regel, sondern als Zahl im
Prüfdokument: Ein Prüfmittel, das eine Zusage nachzählt, findet beim ersten
Lauf einen echten Fehler (`apk.php` lieferte seine 404-Seite ohne Seitenhülle
aus) — eine Zusage, die nur im Kopf steht, ist keine.

**Und der Gegensatz dazu, aus demselben Abschluss:** Ein Prüfmittel, dessen
Dokumentation das Richtige sagt, tut deshalb noch nicht das Richtige. Die
LIESMICH des Bilderlaufs beschrieb seit P3 zutreffend, dass über die
**Fundstelle** gefiltert werde — der Code prüfte für drei Fehlercodes den
**Wortlaut** und warf damit auch Fehler des eigenen Servers weg (Nr. 176,
behoben im Nachtrag AP11). Gefunden wurde das nicht von einem Prüfmittel,
sondern beim Nachfragen einer Zahl, die nicht zusammenpasste.

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

**Das Konzept ist abgearbeitet und nach der Freigabe gelöscht** (14.09.2026,
R62/K9; in der Historie unter `a00f6b5:docs/konzepte/Konzept-S10-Sicherheit.md`,
die Kurzfassung in Abschnitt 8). Es kam von Fable am 13.09.2026 und trug die
Entscheidungen E-S10-01 bis E-S10-18, fünf Fragen am 13.09.2026 mit dem
Auftraggeber entschieden, keine offene Frage, **keine Mockups** (S10 baut
keinen neuen Baustein und keine neue Darstellung). Fünf Entscheidungen in je einem Halbsatz: der
**Konto-Anteil kommt aus der Kontonummer** per HMAC, nicht aus dem Salz
(F-S10-1, E-S10-03 — Passwortwechsel und Reset würfeln das neue Salz im
Browser, der Anteil dazu wäre in dem Augenblick unbekannt) · die
**Hüllenkennung ist `edka1:<kennung>:`** und nicht `edk2:` (F-S10-2,
E-S10-05 — an ihr liest der Browser ab, mit welchem Anteil er öffnet, und
die Statusseite zählt, ohne eine Hülle zu öffnen) · die
**Schlüsselfunktionen liegen unter Betrieb → Servereinstellungen**, Karte
„Schlüssel des Servers" (F-S10-3, E-S10-12, Ordnungsprinzip R74/E-S8-12) ·
**Altpakete umzusiegeln ist gegenstandslos** — es gibt keine (F-S10-4,
E-S10-13; Lesetoleranz bis Nr. 46, kein Job, kein Zähler) · **FTPS bleibt
mit Hinweis**, nur `ftp` geht (F-S10-5, E-S10-14).

**Nicht in S10** (E-S10-01, abgegrenzt): Deploy-Tor (mit dem Staging,
P5-Beginn; Nr. 140 bleibt offen), Zweitfaktor (Nr. 141, P5), Argon2id /
`CryptoKey` / Passkeys (Nr. 146, P6), Weg B (S11, Nr. 43/53), CSP (Nr. 8,
P5). Wer eines davon beim Bauen „mitnehmen" will, hält an (K4).

**Umsetzung:** sechs Arbeitspakete, eines nach dem anderen (AP1 Grundlage
Server · AP2 Browser mit HKDF und stiller Umstellung · AP3 Betrieb mit
Karte, Schlüsselblatt, Nachtragen, Rotation und Status · AP4 Adminpakete
Fassung 3 und `ftp` weg · AP5 Prüfmittel und Referenzbestand · AP6
Abschluss). **Kein Fable-Schritt.** **Keine Schemaänderung und keine
Migration** — `update.php` muss nach dem Merge **nicht** laufen (E-S10-16);
was stattdessen fällig ist, steht in Abschnitt 6.

### Schritt 10 — P5 Dienstbetrieb (in drei Teilen, R82)

**Schnitt (15.09.2026, R82):** Der Block unten beschreibt P5 als Ganzes;
umgesetzt wird er in **drei Teilkonzepten**, jedes für sich nach K1
geschrieben, freigegeben und gebaut, in dieser Reihenfolge — **10a Kette
und Fundament** (Auslieferungskette, Plattformprüfung, Torwächter,
Kopfzeilen, Mail-Warteschlange, Mengenbremse, Proxys, 503-Weg, Nr.-80-Job),
**10b Konto und Registrierung** (Betriebsarten, Konto-Lebenszyklus,
Onboarding, Geräteschlüssel, Mengengrenze, Demo je Betriebsart), **10c
Rollen, Sicherheit und Betriebslage** (Support-Rolle, Zweitfaktor, Audit,
Banner, Fehlerprotokoll, Health, Dashboard, R39-Rest). Die Fahrplanzeilen
10a bis 10c tragen den Zuschnitt samt Backlog-Nummern; Abschnitt 5 ordnet
jeden P5-Punkt einem Teil zu. Grund: Ein Konzept über den ganzen Block
wäre zu groß, um es in einer Sitzung freizugeben — 10a hat kaum
Oberfläche und kann laufen, während 10b und 10c ihre Gespräche brauchen.

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

**Die Hosting-Entscheidung ist gefallen** (15.09.2026, **R81**): Der
Dienstbetrieb bleibt beim jetzigen Hoster, die Anwendung wird aber nicht
auf ihn zugeschnitten. `docs/konzepte/Vorbereitung-P5-Plattformprofil.md`
legt neun Eckdaten (PP-1 bis PP-9) hosterneutral als **Muss** und
**Empfohlen** fest — PHP, Datenbank, Jobs, Shell, Dateisystem,
HTTP-Schicht, Mail, nicht Vorausgesetztes, Staging — und sagt, was davon
ins P5-Konzept geht (dort Abschnitt 3): eine Prüffunktion der Muss-Stufe
für `install.php` und die Statusseite, ein 503-Weg an der
Verbindungsgrenze, Kopfzeilen aus PHP (Nr. 8), vertrauenswürdige Proxys,
eine Mail-Warteschlange mit synchronem erstem Versuch und ein Job für
Nr. 80. Vier Festlegungen stehen darin zum Gegenlesen (F-PP-1 bis
-4); sie gelten mit der Freigabe der Vorbereitung — F-PP-2 ist auf
Rückfrage anders entschieden als zuerst gesetzt (automatischer Job mit
Hash-Auslöser statt Knopf, läuft auch ohne Cron). **Das Staging-Ziel ist
festgelegt** (E-PP-09): `staging.nadoku.gen-em.org` im selben Tarif,
Absender `staging@gen-em.org` mit Betreff-Präfix „[Staging]" als
Einstellung; die Umstellung von `deploy.yml` ist das erste Code-Paket von
P5; Staging bekommt ein eigenes SFTP-Backup-Ziel. Offen: die Einrichtung
(Abschnitt 6). — **Nachtrag 20.09.2026: Adresse und Hoster sind durch
E-KH-04 ersetzt** (`staging-nadoku.gen-em.org` bei lima-city). Der Satz oben
bleibt als Stand vom 15.09.2026 stehen; was heute gilt, steht in
Abschnitt 6a.

**Vorbereitung zu 10c — Protokollierung (16.09.2026).** Auf Anweisung des
Auftraggebers ist während P5a/AP5
`docs/konzepte/Vorbereitung-P5c-Protokollierung.md` entstanden (P5a-Zweig,
Commit `8fa3101`): Auftrag wörtlich (Bereich „Log" mit Reitern je
Ereignisart, mit dem Serverschlüssel versiegeltes Archiv in Fristen, Download;
IPs nur bei Sperren und Angriffen), gemessener Befund (Zustände statt
Verläufe; in 36 Tabellen keine Spalte, die den Urheber festhält; 42
`error_log()`-Aufrufe ohne Sicht; kein globaler Ausnahmebehandler), drei
Grenzen, die das Konzept nicht übergehen darf (die Zusage „kein
Zugriffsprotokoll" in `schema.sql` und `Technik.md`; R36 „keine
Telemetrie"; der schmale Wirkungsbereich der Verschlüsselung), neun Fragen
**V1–V9** und die Liste dessen, was P5a schon anlegt und 10c **übernimmt
statt neu baut** (Mail-Warteschlange, `sicherheit_ereignisse`, Status →
Sicherheit, Lösch-Protokoll der Ziele, `csp_berichte`). **V1 ist am
16.09.2026 entschieden — gehalten:** Der Bereich heißt **Protokoll** und
führt **Betriebsereignisse** (Sperren, Mails, Jobs, Sicherungen,
Verwaltungshandlungen, Fehler), **keine Datenzugriffe**; Lesen, Exportieren
und Herunterladen von Einsätzen bleiben ungeloggt, der Download des
Protokolls selbst wird protokolliert. Zwei Folgen für die Reihenfolge: 10b
läuft vor 10c und erzeugt die Verwaltungsereignisse (Konto angelegt, Rolle
geändert, Adresse geändert), die 10c zeigen will — deshalb legt **das
10b-Konzept den Schreibweg als erstes Paket fest** (Tabelle,
`protokoll($reiter, $art, $text, …)`, Ereignisformat, nach V1, V2 und
E-P5a-09), und 10c baut Reiter, Archiv und Download darauf. Und der
**Log-Helfer** aus der Zentralisierungsanalyse (Schritt 15, Paket 3) ist
genau dieser Schreibweg — er gehört nicht in Schritt 15 und wird nicht
doppelt gebaut. Offen für das 10c-Konzept bleiben V4, V5, V8 und V9; V6
bleibt offen, ist aber halb beantwortet, und V2, V3 und V7 sind seither
in 10b entschieden (die drei nächsten Absätze).

**Umsetzung 10b — Stand 17.09.2026.** Das Konzept
`docs/konzepte/Konzept-P5b-Konto-und-Registrierung.md` ist am 16.09.2026
**ohne Änderungen freigegeben** (E-P5b-01 bis -24, zehn Arbeitspakete).
Die beiden Mockups, die es als Fable-Schritte vorsah, liegen seit dem
17.09.2026 vor und sind **freigegeben**: **neun HTML-Dateien** mit den
gerenderten Bildern unter `docs/konzepte/konzept-p5b/mockups/` samt
`LIESMICH.md` — fünf Darstellungen (Dokumentseite, Registrierung,
Erststart, Rückfrage, Notfallblatt), vier davon zusätzlich bei 376 px.
Damit ist die Pause vor AP3, AP8 und AP9 aufgehoben (K8). Vier
**Gestaltungsvorgaben** des Auftraggebers vom selben Tag gelten dabei für
die Umsetzung und nicht nur für die Mockups: Zeilenaktionen und Plaketten
rechtsbündig in einer Spalte, Kartenfuß mit dem Häkchen links und
„Später" rechts, vertikale Zentrierung als Abnahmekriterium, und die
Bezeichnung „Vereinbarung zur Auftragsverarbeitung (AVV)" bleibt, weil
„Datenschutzvereinbarung" mit der Datenschutzerklärung verwechselbar
wäre.

**Alle zehn Pakete sind gebaut**, auf `claude/magical-dirac-we2y1z`,
**Web 20.15.3 bis 20.24.0**, 16. und 17.09.2026: AP1 Protokoll-Schreibweg
(20.16.5) · AP2 Lebenszyklus-Bibliothek (20.17.0, Backlog Nr. 202 Paket 1
mit erledigt) · AP7 Demo-Anmeldung als Einstellung (20.18.0) · AP4
Einwilligungen (20.19.0) · AP5 Selbstlöschung mit Karenz und
Adresswechsel mit Bestätigung (20.20.0 — AP4 und AP5 teilen sich den
Commit `edc3040`, die übrigen haben je einen) · AP6 Mengengrenze und
Aufbewahrung je Konto (20.21.0; **Nr. 48** erledigt, von **Nr. 37** nur der Speichergrenzen-Teil) · AP3 Selbstregistrierung (20.22.0, berichtigt in 20.22.1 und 20.22.2) · AP8 Handbuch und „Was ist NAdoku" als Seiten (20.23.0) · AP9 Onboarding und die beiden Rückfragen (20.24.0) · **AP10** ist der Merge von `main` mit drei Nummernkollisionen. Daneben stehen
**fünf Funde außerhalb der Paketaufträge, Nr. 223 bis 227**: Nr. 223 —
die Anwendung ließ sich nicht mehr installieren — ist noch vor AP1
behoben worden und trägt die **20.15.3**, weil `main` inzwischen eine
eigene 20.15.2 hatte und der Merge die Kollision auflösen musste; Nr. 224
(`frame-ancestors` in einer Report-Only-Richtlinie, also wirkungslos)
kam mit AP1 in 20.16.5; Nr. 225 (Profilseite aus dem Seitengerüst
ausgebrochen) und Nr. 226 (Meldungston ohne Regel) in 20.21.1. **Nr. 227
ist aufgenommen und nicht behoben**: Die Unicode-Prüfung der
Vollständigkeit zählt Satzzeichen mit — 299 von 319 Befunden sind
Hausstil —, weshalb ihre Schwelle mit jeder Phase steigt, statt zu
fallen; das ist eine Entscheidung über ein Prüfmittel und wurde deshalb
nicht nebenbei getroffen. Dabei sind **sechs Migrationen** entstanden: die
Tabellen `protokoll_ereignisse` (AP1) und `konto_einwilligungen` (AP4,
dazu `rechtstexte.stand_am` von DATE auf DATETIME), dazu **viermal** `users`
erweitert — Lebenszyklus (AP2, fünf Spalten und ein Index), Adresswechsel
(AP5), Mengengrenzen (AP6) und Erststart samt Rückfragen (AP9, vier
Spalten). **`update.php` ist nach dem Merge fällig.**
Der Code liegt auf dem Zweig, nicht auf `main`.

**V2 ist gebaut, aber nicht abgenommen** (E-P5b-06). Beide Hälften der
Frage haben eine Antwort. Die IP-Adresse bleibt, wo P5a sie hingelegt
hat: in `sicherheit_ereignisse`, mit 30 Tagen fest (E-P5a-09). Die neue
Tabelle `protokoll_ereignisse` hat **keine IP-Spalte** — neun Spalten,
`reiter` als ENUM über sechs Werte, *Sicherheit* ist keiner davon —, und
ihr Reiter *Verwaltung* trägt als einzigen Personenbezug Konto-Id und
Kontoadresse, weil das Audit sonst nicht lesbar wäre. Dessen Frist steht
auf **365 Tagen, einstellbar zwischen 90 und 1 095**
(`PROTOKOLL_FRIST_VORGABE`), die fünf übrigen Reiter verfallen nach 30
Tagen. Was fehlt, ist nicht die Zahl, sondern die Bestätigung: V2
verlangt in seiner eigenen Begründung eine **juristische, keine
technische** Antwort auf die Fristlänge, und R41 will die Speicherdauer
in der Datenschutzerklärung sehen — deren Entwurf
(`docs/rechtstexte/Datenschutz-Ergaenzung-P5.md`, B3 und B4) nennt sie,
ist aber **nicht anwaltlich geprüft** und führt die Audit-Frist als
„[365] Tage" **in eckigen Klammern** (E-P5b-24). **V2 gilt deshalb als
betrieblich entschieden und rechtlich offen.**

Dieselbe Entscheidung beantwortet der Sache nach auch **V3** — Fristen je
Reiter, die Einstellbarkeit auf das Audit begrenzt, also genau die
Einschränkung, die die Frage verlangt hat; das Konzept nennt V3 nirgends
beim Namen, die Zahlen decken sie aber vollständig. **V7 ist ausdrücklich
entschieden und gebaut** (E-P5b-12): Scheitert das Schreiben, scheitert
die Handlung nicht — `error_log()` mit Kennung, Zähler `protokoll_fehler`
in `app_state`, roter Hinweis auf der Statusseite; die Abnahme von AP1
belegt alle **drei** Stufen. **V6 bleibt offen, aber nur noch halb:** Der
Schreibweg ist als **Tabelle** gebaut, offen ist allein das versiegelte
Archiv als Datei. Daneben hängt an derselben Nummer die Frage, ob
`sicherheit_ereignisse` später in dieselbe Tabelle wandert — Konzept und
Migrationskommentar führen sie unter „V6", die Vorbereitung formuliert V6
dagegen als „Datei oder Tabelle?". Zwei Fragen unter einer Nummer: Wer
das 10c-Konzept schreibt, trennt sie, statt sie weiterzuschleppen.

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
Funde · **Web-App auf Android** (aus der Erhebung Nr. 87, seit 13.09.2026 ausgetragen) — **R70** (E-PV-6): Manifest
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
**Web-App-Manifest** (R70 — die Erhebung Nr. 87 ist ausgetragen: Manifest allein, „NAdoku Web", Symbole
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

### Schritt 15 — Zentralisierung: eine Stelle je Sache

**Anlass.** Eine eigene Sitzung hat am 16.09.2026 den Web-Teil (`server/`,
ohne `assets/vendor/`) auf Code untersucht, der nach dem Vorbild von
`mission_fields.php` an eine Stelle gehört — Android und Uhr ausdrücklich
nicht. Der Auftraggeber hat entschieden: **alles wird angegangen, in
Paketen.** Der Befund liegt im Backlog (**Nr. 202**, mit Dateien und
Zahlen); dieser Block hält, was die Steuerung braucht. Kein Code, kein
Konzept in dieser Fassung — das Konzept entsteht nach K1 in einer eigenen
Sitzung, **nach dem Merge von 10a**, und misst dann neu.

**Ziel.** Jede Sache hat im Web-Teil eine Stelle: Marke, Mailrahmen, Link,
Token; `app_state`, virtuelles Gerät, Einsatz laden, `missions`-Spalten,
Transaktion, Kindtabellen; API-Eingang, Sitzungsstart, Flash;
JSON-POST, Meldungs-Markup, Dauer, Datum, Karte im JavaScript; Zeit, Zahl,
Bytes, relative Zeit, Migrationshelfer. Danach findet ein Review (P6)
weniger Stellen, und 10b, 10c und S11 treffen je Sache eine Stelle statt
vier.

**Ausgangslage — bereits zentral, geprüft am 16.09.2026:** Phasen,
Reanimationsarten, Besatzungsrollen, Gerätearten, Sicherungsziele
(`Zielweg`), Jobs, Papierkorb, Spuren (`spur_lib.php`), Escaping,
Meldungszeile, Seitenhülle, Knöpfe, Aufräumlogik (`job_aufraeumen()`),
ZIP und Prüfsummen (je eine Stelle), Tab- und Formularaufbau (`ui.php`),
POST-Auftakt der HTML-Seiten (`csrf_check()`), die Entschlüsselungsschleife
(`patient.js`). Die PHP-JS-Spiegelungen von `PHASE_LABELS`, `RESUS_LABELS`
und `dt_art_symbole()` sind als bewusste Spiegelung mit Quellenverweis
kommentiert und **bleiben**.

**Entscheidung R83** (Abschnitt 7): Zentralisiert wird beim **zweiten
echten Verbraucher**, nicht vorher; ein Helfer mit einem Verbraucher liegt
in einer `_lib.php`, nicht in einer Seite. Beleg: `edbak_groesse_text()`
steckt in `adminbackup_lib.php` und wird von `admin_sicherungsziele.php` und
`betrieb_updates.php` nur dafür geladen. Anwendungsfall vorgemerkt: die
vier `ZipArchive`-Stellen in `adminbackup_lib.php` werden herausgelöst,
sobald ein zweiter Verbraucher kommt (Log-Dateien als ZIP, 10c).

**Die sechs Pakete** (Reihenfolge = Reihenfolge; Zahlen Stand `main`
16.09.2026, Einzelheiten in Backlog Nr. 202):

1. **Marke, Mail, Link, Token** — Mailrahmen 7 Versandstellen von Hand,
   Testmail weicht ab; `base_url`-Verkettung 5× ohne `rtrim`;
   Passwort-Setz-Link 4× mit zwei Fassungen der Regel „ein gültiger Token
   je Konto" und vier SQL-Literalen für die Laufzeit. **Mailrahmen und
   `app_url()` werden in P5a AP5 vorgezogen** (Nachtrag 16.09.2026); es
   bleibt `reset_token_ausstellen()`.
2. **Datenzugriff** — `app_state` 24 Stellen, fünf Wrapper-Paare;
   `manual-<userId>` 4× zeichengleich; Einsatz per ID mit Besitzprüfung 11
   Stellen in 9 Dateien ohne Gegenstück zu `dt_laden()`; **acht
   Handlisten der `missions`-Spalten** trotz Feldkatalog (Export,
   Suchindex, Import, Backup — das sorgfältigste Paket, berührt Export- und
   Backup-Format); Transaktionsrahmen in 21 Dateien; Kindtabellen eines
   Einsatzes in vier Schreibwegen.
3. **API-Eingang, Sitzung, Flash** — Eingangsgatter in 12 API-Dateien
   (der CSRF-Teil ist **auf dem P5a-Zweig erledigt**, AP4/Nr. 67; übrig
   Methode, JSON-Rumpf, Fehlerschlüssel); Sitzungsstart 7× in drei
   Varianten; Flash-Meldung 3×; verzögerte JSON-Antwort der unangemeldeten
   Endpunkte 7× inline. **Nicht hier:** der Log-Helfer — er ist der
   Schreibweg des 10c-Protokolls (oben).
4. **JavaScript** — JSON-POST mit CSRF-Kopf 15× in 6 Dateien, vier
   Fehlerschemata; Meldungs-Markup 6× nachgebaut; Dauer in drei
   Schreibweisen, Datum 6× definiert, Kilometer 7×; Karten-Präambel in
   vier Seiten mit zwei Fallback-Ausschnitten; Rahmen um
   `EdPat.entschluessleListe()` 3×.
5. **Zeit, Zahl, Migration** — ISO-UTC-Marke 21× schreiben, 9× lesen;
   Datumsformate 45× in vier Trennervarianten, drei `date()` in
   Server-Zeitzone; Tausendertrennung 22×, Bytes lesbar in zwei
   Funktionen, GB-Umrechnung 7×, relative Zeit 3×, Prozent 5 Dateien;
   `migration_lib.php` mit 40 Inline-Abfragen gegen `information_schema`
   trotz eigener Helfer. **R83 gilt für P5a AP8–AP10 sofort**: Bytes und
   relative Zeit werden dort gehoben, nicht kopiert.
6. **Beifang — nur zusammen mit Arbeit an der jeweiligen Datei, kein
   Termin:** Stammdaten-CRUD in `einstellungen.php` (4+4 Zweige);
   Verwaltungsseiten-Auftakt; Umfangsliste der Bestätigungsseiten 3×;
   Nachweisdatei-Mechanik in `install.php` und `wiederherstellen.php`;
   Ablage und Zeitstempel-Dateiname 3×; kopierte `asset()`/`e()` in
   `betrieb_schluesselblatt.php`.

**Drei Nebenfunde sind Fehler, keine Aufräumarbeit** und stehen mit
eigenen Nummern: **203** (`api/export_data.php` ohne `Cache-Control:
no-store`), **204** (`smtp.php` protokolliert die Empfängeradresse gegen
die eigene Zusage), **205** (`session.use_strict_mode` fehlt auf den
Anmeldewegen). Alle drei gehen als **Nachträge in P5a** (AP4a, AP5), weil
die Dateien dort gerade umgebaut werden; Schritt 15 findet sie erledigt
vor.

**Voraussetzung:** Merge von 10a. **Konzept:** Fable, nach K1, mit
Neumessung. **Umsetzung:** Opus, ohne Mockup. **Status:** offen. **Warum
vor 10b und nicht vor P6**, wie die analysierende Instanz empfahl: 10b
(Registrierung, Konto-Lebenszyklus) und 10c (Audit, Dashboard) bauen
Sitzungsstart, `app_state`-Zugriff, Flash-Meldungen und Zahl-/Zeitformate
— jede Kopie, die vorher weg ist, entsteht dort nicht neu; und S11
(12a) schreibt die Export- und Backup-Pfade um, die Paket 2 auf eine Stelle
zieht. Preis: Die 10b-Umsetzung wartet auf sechs Pakete; das 10b-Konzept
entsteht parallel.

## 4. Parallelität und Sperren

**Faustregel:** Ein Paket, das nur `android/` oder nur `watch/` anfasst,
kann immer laufen. Alles, was `server/`, `docs/JSON-Vertrag.md`,
`schema.sql` oder `update.php` schreibt, wartet auf das Paket davor.
Gemeinsam ist immer die Buchführung (`version.php`, `CHANGELOG.md`,
`Backlog.md`) — dort sind Konflikte mechanisch, aber die Migrationsliste
und die Backlog-Nummern verlangen die Gegenproben aus Abschnitt 2.2.

| jetzt parallel möglich | nicht parallel |
|---|---|
| **Schritt 16 (Sitzungsablage) zu Kette II** — eigener Zweig von `main`; Kette II fasst `server/` nicht an, Schritt 16 fasst die Kette nicht an. Gemeinsam ist nur die Buchführung. **Merge nach `main` nur auf Ansage** — er löst einen Staging-Deploy aus (Einschub 20.09.2026) | — |
| S4-Merge und Backlog-Runde (verschiedene Punkte, eigene Zweige) | S6 zu S4 (`schema.sql`, `update.php`, Vertrag) — S6 erst nach dem Merge |
| S5-Konzept und S7 (Konzept schreibt keinen Code) | S7 zu S4 (`einstellungen.php`, `admin_user.php`, Handbuch, Technik) — S7 erst nach dem Merge |
| Konzeptarbeit P5 (Hosting, Gespräche) zu allem | ~~S5-Umsetzung zu S6 und S7~~ — **entfällt, S5 ist gemergt** (Fassung 25) |
| Wear-OS-Gerätetest zu allem | ~~S4-Rest zu **Paket E**~~ — **erfüllt** (E am 03.09., S4-Rest am 04.09. gemergt, Fassung 32). Die alte Sperre „S4-Rest zu S5“ war schon erfüllt: Vertragsabschnitt 1a steht |
| — | Backlog 21 (43 Restfunde quer durch `server/`) zu jedem laufenden `server/`-Paket |
| — | ~~S8 zu S4-Rest und S7~~ — **erfüllt** (beide gemergt, Fassung 27); S8 läuft seit 05.09.2026 |
| S9-Konzept zu Schritt 9a (das Konzept schreibt keinen Code) | ~~S8 und S9~~ — **erfüllt** (S8 gemergt, Fassung 32). ~~**S9-Umsetzung zu 9a**~~ — **aufgehoben 07.09.2026:** Nr. 137 und 132 sind in S9, die gemeinsamen Dateien gehören S9 allein; beide laufen parallel, nur die Buchführung zieht nach (K7) |
| S9 zu S11: `store => 'pat'` (E-S9-01) ist der Katalogschlüssel, den S11 für die Zielklinik benutzt — S11 baut darauf auf, nicht daneben | — |
| Korrekturstufe Nr. 148/149 zu 9a (`index.php`, `betrieb_updates.php`, `migration_lib.php` gegen die Sicherheitsdateien; Buchführung mechanisch) | — die Korrekturstufe ist klein und geht **zuerst** auf `main` |
| Demo-Ausbau (9d) zum P5-Konzept und zum R42-Rest (`betrieb_statistik.php`) — keine gemeinsamen Dateien außer der Buchführung | ~~**Demo-Ausbau zu S10**~~ — **erfüllt** (S10 am 14.09.2026 gemergt, 9d begann danach). Der Grund war doppelt: AP0 schreibt `einstellungen.php`, und S10 nimmt den **Referenzbestand** als Messlatte — ein Bestand, der sich unter der Messung ändert, ist keine |

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
Web 19.1.0); **171 und 172 mit Fassung 43, 174 mit Fassung 44** (die
beiden Backlog-Runden), **175 mit Fassung 46** (Nebenfund der
Backlog-Durchsicht vom 12.09.2026, angelegt 13.09.2026); **176 und 177 mit
Fassung 48** (zwei Funde aus dem Abschlusspaket der Backlog-Runde 3 — der
Rauschfilter des Bilderlaufs und die doppelten Fassungsnummern in
Abschnitt 10; **176 ist mit Fassung 49 erledigt**, 177 ist zurückgestellt und
steht weiter in der Tabelle); **178, 179 und 180 mit Fassung 49** — alle drei
beim Beheben von Nr. 176 und bei dessen Gegenprüfung gefunden: die
Kopplungsprobe in den Geschwisterwerkzeugen (178), die Zusage ohne Messmittel
(179) und der Sollwert, den es seit Web 15.5.0 nicht mehr gibt (180); **alle
drei mit Fassung 50 erledigt**, und aus 179 ist **181** hervorgegangen (die
Content-Security-Policy, die zweite Hälfte und eine Festlegung);
**188 mit Fassung 63 und 189 mit Fassung 65** aus dem S10-Nachlauf;
**190–195 mit Fassung 66** aus der Bestandsaufnahme zu R42 vom
14.09.2026 — zwei Funde an der Statistikseite (190, 192), der fehlende
Index (191), die Buchführung zu R42 selbst (193) und zwei Nebenfunde
(194 Handbuch, 195 Sicherungs-Rückweg); **196 mit derselben Fassung** aus
dem Gegenlesen dieser sechs; **197 und 198 mit Fassung 67** aus dem
Demo-Ausbau (Schritt 9d) — sie hießen auf dem Zweig zunächst 190 und 191 und
sind beim Zusammenführen nachgezogen worden, weil der R42-Nachlauf dieselben
Nummern zuerst auf `main` hatte; **199 ebenfalls mit Fassung 67**, aus dem
Gegenlesen dieses Merges; **200 und 201 mit Fassung 73** aus dem
P5a-Konzept (Bounce-Postfach, `Retry-After`); **202–205 mit Fassung 74**
aus der Zentralisierungsanalyse vom 16.09.2026 (202 das Vorhaben als
Sammelnummer, 203–205 drei Nebenfunde, die Fehler sind).

**Mit Fassung 41 ist die Tabelle wieder deckungsgleich mit dem Backlog.**
Sie war es nicht mehr: **24 Zeilen** nannten Punkte, die längst erledigt
sind (S9 und 9a — 68, 70, 72, 101–107, 110, 127–138, 147, dazu Nr. 19),
und **neun offene Punkte fehlten ganz** (153–158, 161, 169, 170). Dabei
sind drei Backlog-Punkte aufgefallen, die ihre Erledigung im eigenen Text
trugen, aber weiter unter *Offen* standen: **Nr. 19** (mit P3 gegenstandslos
geworden), **Nr. 159** (Uhr 3.1.0) und **Nr. 160** (Android 0.15.0) — alle
drei jetzt verschoben. Gezählt wird das seither maschinell: Die Zahl der
Zeilen hier und die Zahl der offenen Punkte im Backlog müssen gleich sein —
bei **Fassung 46** waren es 59 = 59 (61 − 3 + 1). **Hier steht absichtlich
keine heutige Zahl mehr:** Sie veraltet mit jeder Zeile, die jemand einträgt,
und stand nach Fassung 47 bis 49 vier Fassungen lang falsch da (gefunden am
13.09.2026 von der Gegenprüfung des Nr.-176-Nachtrags). Die gültige Zahl nennt
die jeweils jüngste Zeile in Abschnitt 10; gemessen wird sie mit dem Einzeiler
im Absatz darunter.

**Mit Fassung 46 hat die Backlog-Durchsicht vom 12.09.2026 drei Zeilen
ausgetragen** — **Nr. 87** (Erhebung mit R70 beantwortet; stand hier neben
P7 ein zweites Mal), **Nr. 81** (geschlossen ohne Beleg am Gerät, mit
Wiederöffnungsbedingung im Backlog) und **Nr. 88** (verworfen, steht wie
Nr. 71 als Entscheidung unter *Erledigt*) — und **Nr. 175** ergänzt. Zehn
weitere Zeilen tragen die Entscheidungen jener Sitzung; vier davon (41, 42,
45, 124) sind seither die **Mockup-Runde** (Schritt 9c), und Nr. 45 hat
damit „nach v1.0" verlassen — dort stehen noch **acht**: 50, 51, 52, 55,
90, 96, 99, 100. Das Protokoll liegt in
`docs/konzepte/Backlog-Durchsicht-2026-09-12.md`.

> **Diese Zahl altert, und sie hat es getan.** Sie stand bis zum 12.09.2026
> auf „72 = 72" — dem Stand von Fassung 41 — und war da bereits durch drei
> Fassungen überholt (43 nennt 66 = 66, 44 erledigt fünf und legt einen an,
> 45 trägt Nr. 71 aus). Eine Selbstprüfzahl, die selbst veraltet, ist
> schlimmer als keine: Die nächste Instanz misst gegen sie und meldet eine
> Lücke, die es nicht gibt. **Wer eine Zeile hinzufügt oder entfernt,
> rechnet sie im selben Zug nach** — Fassung 45 hat das verlangt, den
> Einzeiler aber nicht hingeschrieben; hier ist er (Fassung 46):
>
>     awk '/^## 5\./{f=1} /^## 6\./{f=0} f' docs/Rahmenplan.md | grep -cE '^\| [0-9]+ \|'
>     awk '/^## Offen/{f=1} /^## Erledigt/{f=0} f' docs/Backlog.md | grep -cE '^[0-9]+\. '
>
> Beide Zahlen müssen gleich sein.

> Die drei aus Paket E standen dort zunächst als 90–92 und mussten beim
> Zusammenführen mit Fassung 26 weichen: 89–92 waren schon an S7 und S5/C
> vergeben. Wer auf einem Zweig eine Nummer vergibt, ohne den Stand des
> Rahmenplans zu kennen, vergibt sie zweimal — die Nummer steht hier, nicht
> im Zweig.

| Nr. | Punkt (kurz) | gehört zu | Bemerkung |
|---|---|---|---|
| 21 | 43 A4-Restfunde sichten (mit 18) | **P6** (R69) | **Entschieden 12.09.2026: an den P6-Review übergeben.** Die Quellliste (Abschnitt 9.3 des P0-Konzepts) existiert nicht mehr — das Konzept ist nicht im Repositorium; der Review geht ohnehin alles durch. Felder mit Vertrags- oder Uhrberührung nur nach Vertragsabgleich (R21) |
| 23 | Vertrag nennt Reanimationsart `beginn`, die keiner annimmt | P7 | mit dem Vertragsreview (R12, R71) |
| 36 | Prüfmittel: Klassennamen, die nur JavaScript sucht | Backlog-Runde | Prüfmittel |
| 37 | Konto, das über Jahre wächst | **P5a/AP9 gefahren** (16.09.2026) / **Speichergrenzen erledigt P5b/AP6** (17.09.2026, Web 20.21.0) / **Rest** `post_max_size`: **Prüfpunkt P29 in `docs/konzepte/Pruefdokument-P5a-Kette-und-Fundament.md`** | S2 hat die Mengen beantwortet; AP9 hat die drei Messungen gefahren. **Zwei beantwortet:** Zeitraumübersicht **42,61 s bei 3983 Einsätzen** (Befund — die einzige Ansicht ohne Seitengrenze), Nachbearbeitung 2,83 s; von den Zielzahlen E-S2-24 sind **fünf von sechs** gehalten (Spuren 3,66 gegen 3 MB je 1000, knapp verfehlt). **Eine bleibt offen:** `post_max_size` der Zielanlage — sie braucht eine laufende Installation und ist dann ein Seitenaufruf auf der Plattformkarte, keine Messung. **Die Speichergrenzen je Konto sind gebaut** (R37.10, E-P5b-04, Web 20.21.0) — Abnahme 6 von 6. **Der Backlog-Eintrag führt** und hält die Zahlen; diese Zeile verweist nur (Fassung 46). Das ist mit Fassung 78 zurechtgerückt worden: Die volle Abnahme stand kurzzeitig hier statt dort, während derselbe Satz sagte, der Backlog führe |
| 40 | 55 Altklassen der Streichliste austragen | Backlog-Runde | vor dem nächsten CSS-Umbau |
| 43 | GPS-Spur und Phasenkoordinaten im Klartext | **S11** (Schritt 12a) | **Zuordnung berichtigt 12.09.2026.** Die Zeile sagte „P6 (Weg B) … entscheidet der R17-Review“ — **er hat entschieden**, am 06.09.2026 mit R78 (6): Weg B wird **S11**, Schritt 12a, nach P6 und **vor der Öffnung**; Umfang Spur, Phasenkoordinaten, Reanimation und Zielklinik, Altbestand per Einmalwerkzeug. So steht es im Fahrplan (Schritt 12a) und in `CLAUDE.md` 4. **Und „Backlog-Runde (Weg C)" stand ebenfalls zu Unrecht da:** Weg C ist am 06.09.2026 als eigener Punkt **Nr. 138** herausgelöst und **mit Web 15.6.0 am 07.09.2026 erledigt** worden — die Zusage ist in `CLAUDE.md` 4, `README.md`, `Technik.md` 4.98 und Handbuch 5 eingegrenzt, dazu ein Textbaustein für die Datenschutzerklärung. Von den beiden Wegen ist damit **einer gebaut und einer terminiert**; der Punkt gehört ganz zu S11. Vorstudie `docs/konzepte/Konzept-V1-Ortsdaten.md`; die drei Fragen aus Abschnitt 6 sind mit R78 beantwortet. Die Frage hängt mit dem Notizfeld aus S9 zusammen (Nr. 109) |
| 46 | Altformat der Sicherung abschaffen | P7 | Stichtag NaDoku 1.0 (R71) |
| 50 | Versand liest je Konto ein Verzeichnis | nach v1.0 | erst messen |
| 51 | Suche verarbeitet 5 000 für 200 | nach v1.0 | Zielzahl gehalten (3,81 s) |
| 52 | WebDAV als Sicherungsziel | nach v1.0 | Bedarf abwarten |
| 53 | Konto-Schlüsselpaar für versiegelte Serversicherungen | **S11** (Schritt 12a) | **Zuordnung berichtigt 12.09.2026.** Stand bis dahin auf „nach v1.0“ — R78 (6) hat am 06.09.2026 entschieden: Dasselbe Schlüsselpaar ist der Schlüssel auf dem Gerät für Weg B, S11 „löst Nr. 53 mit“; Fassung 30 nennt 43 und 53 ausdrücklich **zusammengeführt**, und der Backlog-Eintrag schreibt seither wörtlich „Zuordnung damit S11, nicht mehr ‚nach v1.0‘“. Nur diese Zeile hatte es nicht mitbekommen |
| 55 | Komplettsicherung ohne scharfen Schnappschuss | nach v1.0 | — |
| 57 | Tagesübersicht baut ihre Tabelle zweimal | **Schritt 15** (Zentralisierung) | **Entschieden 12.09.2026: das gemeinsame Modul `missiontable.js` gewinnt** — Beschriftung, Ausrichtung, Hakenreihenfolge folgen ihm. **Nebenbedingung `cap_gate`:** die bedingte Anzeige von Winde und Bergwacht sitzt heute in `index.php` (über `mf_tagesspalten()`), Suche und Zeitraum führen die Spalten hart im SELECT — beim Zusammenführen ausdrücklich mitnehmen, erster Prüffall des Pakets — Umverteilt mit dem Einschub 20.09.2026: eine Kopie ist ein Muster, also Zentralisierung. |
| 80 | Auswertung der Gerätestatistik (Rest von 59) | **P5a** (Nachlöse-Job, E-PP-06) / **P5c** (Herkunft und Dashboard) | Gerätemodelle und Nutzung sind **gebaut** (S8 AP4, Betrieb → Statistik). Offen: **Herkunft je Einsatz (R64-Werte) und Betriebslage-Dashboard**, mit der Datenschutzerklärung als Vorbedingung (Abschnitt 6) |
| 90 | Der Simulator kann keinen Verbindungsabriss herstellen | nach v1.0 | aus S5/C; Prüfmittel der Uhr, nur am Gerät nachweisbar (Prüfliste S5) |
| 92 | `pruefstand.sh bildreihe` fotografiert nur den Startbildschirm | Backlog-Runde | aus S5/C; Tastenfolge als Parameter |
| 62 | Logodateien mit alten Farbwerten | **Zuarbeit** (Abschnitt 6) | **Entschieden 12.09.2026: neue Vorlagen anfordern** — auch die PNG tragen die alten Werte, es gibt keine korrigierte Quelle. `Design.md` 2.5 ist am 13.09.2026 berichtigt; bis zur Lieferung passiert am Code nichts |
| 65 | 14 Fassungshinweise, AGP 9 | Backlog-Runde | eigene Runde nach dem S4-Rest, nur `android/` |
| 76 | Demo-Reset läuft alle 30 Minuten, auch ohne Änderung | Backlog-Runde | erst messen (Laufzeit, Last), dann entscheiden |
| 77 | Wartungsseite `update.php` in Unterseiten aufteilen | **P6** (Rest) | **Konzept liegt vor** (E-S8-05): die Seite wird **aufgelöst**; der Block Betrieb trägt Status, Statistik, Updates, Hintergrundjobs, Servereinstellungen, Komplett-Backup und Backup-Ziele. Wartungsmodus **und** ausstehende Migrationen liegen zusammen auf „Updates" (R66: nur Ausstehende mit „Ausstehende ausführen", ausgeführte bis P5 eingeklappt, danach im Audit-Protokoll); `update.php` wird Weiterleitung bis P6. AP2 und AP4 |
| 95 | Die Android-Rundlauffälle lassen Daten im Admin-Konto zurück | **P7** (Store-Fassung der Apps) | 9 Diensttage, 5 Einsätze, 14 439 Punkte; Aufräumen im `@After` oder eigenes Prüfkonto; **Nr. 115 (Paket E) sagte dasselbe und ist hier aufgegangen** (Fassung 32); Schritt 6 hat es nicht mitgenommen — Umverteilt 20.09.2026: wartet auf einen Anlass, und P7 ist er. |
| 96 | Eigene Wartungsmeldung auf Uhr und Handy, `Retry-After` auswerten | **P7** (Store-Fassung der Apps) | aus S5/W (E-S5W-08); heute behandeln die Clients das 503 als gewöhnliches 5xx, und das genügt — Umverteilt 20.09.2026: wartet auf einen Anlass, und P7 ist er. |
| 99 | Fassungsprüfung auf Klick der Administratorin (GitHub-Releases) | nach v1.0 | R66, Option A2; nur wenn Selbsthoster es verlangen; kein Hintergrundlauf |
| 100 | Play-API-Upload aus der Auslieferungskette | nach v1.0 | R67; Upload-Schlüssel als GitHub-Secret plus Dienstkonto, wenn die Releases häufiger werden; E-S4-16 dann ergänzen |
| 114 | Abgewiesene Pakete sichtbar machen und ausräumen | **Backlog-Runde** | Räumteil (30 Tage, beim Trennen) **erledigt mit Android 0.14.0** (R78); der Bedienweg zum Sichtbarmachen bleibt |
| 116 | Kontrastwerkzeug misst nur seine Paarliste | Backlog-Runde | Android-Prüfmittel `android/werkzeuge/kontraste.py`; Paare aus dem Code ableiten |
| 121 | Vorschau der Rechtstexte beim Tippen (Mockup 09) | **10c, AP9** | heute nur der gespeicherte Stand — Umverteilt 20.09.2026 (E-P5c-20) — die Rechtstextseiten werden in 10c ohnehin angefasst. |
| 122 | Freie Zeiträume und Diagramme in der Statistik (Mockup 04) | **Schritt 17** (Backlog-Runde 4) | Diagrammbibliothek müsste vendoriert werden — Umverteilt 20.09.2026: **nicht 10c** (E-P5c-23, F-P5c-4). |
| 140 | Push auf `main` ist Deploy (K-16) | **Zuarbeit** / R40 (2) | Branch-Schutz und 2FA sofort; Deploy-Tor mit dem Staging — **nicht** S10 (R78 (7)); Integritätswache im Sofortpaket (F-SP-9) |
| 141 | Zweitfaktor für alle Konten (K-5) | **P5c** | erweitert R38 |
| 187 | Alle „Anhebungs"-Wege werden mit 1.0 abgeschafft | **vor 1.0** | aufgenommen 14.09.2026 (S10/AP3) auf Anweisung; ab 1.0 gibt es nur noch neue Konten, also keinen Altbestand, der gehoben werden müsste. Drei Wege plus ihr Beiwerk; **nicht** mitgehen: die Formatkennung selbst und die Anteil-Rotation |
| 146 | Fragen an das Bedrohungsmodell (Argon2id, `CryptoKey`, Passkeys/PRF) | P6 | R17 Stück 1; dazu Skizze SP-9 |
| 150 | Cron-Befehl für `jobs.php` mit dem Repositoriumspfad dokumentiert | Backlog-Runde | vier Stellen (Kopfkommentar `server/jobs.php`; `Technik.md` 4.97a „Die drei Auslöser" und Runbook „Hintergrundjobs einrichten"; Changelog-Eintrag Web 10.1.0); der Deploy legt den Inhalt von `server/` nach `httpdocs/` — abgetippt ergibt das „Could not open input file". Die Karte „Auslöser" ist **nicht** betroffen (baut über `__DIR__`). **Beide Fragen entschieden 12.09.2026:** Changelog wird rückwirkend berichtigt; künftig Platzhalter ohne `server/` plus Verweis auf den Kopier-Knopf |
| 168 | **Zentrale Stammdaten vollständig zurückbauen** (R39) | **P5c** | S9 hat nur die Tür geschlossen (Weg c, kein Schema). Was bleibt, steht mit Fundstelle in `docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md` — **208 Befunde**, davon 23 am Schema. Vorbedingung des `ALTER TABLE`: **0 Zeilen mit `user_id IS NULL`** in allen sechs Tabellen |
| 154 | Handy-App liest `kept_points` und `kept_meta` nicht | **P7** (Store-Fassung der Apps) | aus der Gegenprüfung des Sofortpakets (Nr. 134); `Sendeantwort.kt` nimmt nur `kept_phases` und `kept_resus` in den Sendebericht — Umverteilt 20.09.2026: wartet auf einen Anlass, und P7 ist er. |
| 157 | Handy-App: Sackgasse zwischen „Schlüssel abgewiesen" und „Gerät trennen" | **P7** (Store-Fassung der Apps) | aus dem Emulatorlauf zu Android 0.14.1; dieselbe Bauart wie Nr. 159 auf der Uhr, die mit Uhr 3.1.0 behoben ist — Umverteilt 20.09.2026: wartet auf einen Anlass, und P7 ist er. |
| 172 | Eine Erwartung der Wartungsprobe flackert | Backlog-Runde | Erwartung 15 vergleicht zwei Einzelmessungen ohne Spielraum (beide rund 71 ms); gemessen 0/1/0 nicht erfüllte in drei Läufen. Eine Probe, die grundlos rot wird, wird nicht mehr gelesen |
| 158 | `days` trägt kein `created_at` | Backlog-Runde | Migration; führt `ingest_tag_offen()` auf einen Spaltenwert zurück statt auf eine Abfrage über zwei Tabellen. Keine Fehlerbehebung, eine Vereinfachung |
| 161 | Aus einer Aufzeichnung ein Stück löschen können | Backlog-Runde, gemeinsam mit Nr. 43 | aus Nr. 160: Ein vergessener Dienst zeichnet den Heimweg mit auf, und heute geht nur alles oder nichts |
| 169 | Ein Diensttag mit „Anderem Rettungsmittel" kann keine Besatzung festhalten | **P5c** | **Vertagt 12.09.2026 auf P5** — dort wird über die Rettungsmittel ohnehin entschieden. Bis dahin gilt (c) „so lassen und im Text sagen"; Hinweis und Handbuch sagen es seit Web 18.1.1 zutreffend, kein dritter Zustand. Drei Wege stehen im Backlog |
| 170 | Kein Prüfmittel misst, ob die Kennzeichnung vollständig ist | Backlog-Runde | aus Web 19.1.1: AP7 zählte, **wie viele** Schlösser stehen, und konnte deshalb nicht sehen, dass zwei fehlen. Sollmaß **muss neu bestimmt werden** (13.09.2026): Der Feldkatalog trägt genau ein `'store' => 'pat'` und drei `'hinweis'`; die acht Schlösser der Karte „PatientIn" stehen handgeschrieben in `einsatz.php` — das vorgeschlagene Sollmaß deckte 1 von 8 ab |
| 175 | `edbak_uebersicht()` hat keinen Aufrufer mehr | Backlog-Runde | Nebenfund 13.09.2026 bei der Gegenprüfung zu Nr. 37: die Funktion ist durch `edbak_konto_stand()`, `edbak_staende()` und `edbak_verwaiste()` abgelöst und wird nur noch in `Technik.md` als Begründung genannt. Austragen, Satz anpassen |
| 177 | Der Änderungsverlauf des Rahmenplans führt sechs Fassungsnummern doppelt | Backlog-Runde | Fund 13.09.2026 in Runde 3/AP10: Abschnitt 10 trägt **35, 36, 37, 39, 38, 37** zweimal, mit verschiedenem Inhalt. **Zurückgestellt am 13.09.2026** („nur historisch"). Nachgemessen: **zwei** Dokumente zitieren betroffene Nummern (Prüfdokument 9a → „Fassung 36", Konzept S9 → „Fassung 38"), und die Bestandsaufnahme zu R39 hat den Fund am **09.09.2026** schon verzeichnet, dazu eine **fehlende** Zeile für denselben Tag. Keine Wirkung auf Code, Daten oder Oberfläche; mit der nächsten größeren Rahmenplan-Pflege in einem Zug zu machen |
| 184 | Kommentar-Abtaster verliert in PHP mit HTML die Spur | Backlog-Runde | Aufgenommen 14.09.2026 in AP2. `ohne_php_js_kommentare()` (Backlog-Runde 3) taktet in einer PHP-Datei mit HTML an einem ungepaarten `"` im Fliesstext aus und verschluckt alles bis zum naechsten — in `einsatz_form.php` **rund 800 Zeilen am Stueck**. Die Folge sind **falsche Negative** in den drei Zusagen-Pruefungen, die denselben Text durchsuchen: Was im verschluckten Bereich steht, wird nicht gefunden, und die Gruppe meldet trotzdem 0. Weg: fuer `.php` nur innerhalb `<?php>`/`<?=>`/`<script>` abtasten |
| 188 | Kein Prüfmittel misst Verweise zwischen Dokumenten | **P6** (R69), früher wenn vorher ein Konzept gelöscht wird | Aufgenommen 14.09.2026 im S10-Nachlauf. Nach der Löschung des S10-Konzepts wurden Wortliste und Linkprobe als Beleg genannt; **keine von beiden misst diese Klasse** — die Linkprobe liest `<seite>.php?…` in `server/` und sieht `docs/` gar nicht. Der Beleg, dass das etwas kostet: Die Fahrplanzeile zu Schritt 7 sagte **acht Tage lang** „Konzept liegt vor" und nannte einen Pfad, den es seit dem S8-Abschluss nicht mehr gab — in derselben Zeile, die zwei Spalten weiter „nach R62 gelöscht" trug. Eine Probe nach dem Muster der Linkprobe, die relative Pfade auflöst, eingefrorene Dokumente auslässt und eine begründete Ausnahmeliste führt |
| 190 | Statistikseite lässt das virtuelle Gerät stehen, „Ohne Gerät" zählt zu niedrig | Backlog-Runde | Fund 14.09.2026 (Bestandsaufnahme R42): Die Kontenabfrage hat **gar keine** `manual-%`-Bedingung. Das virtuelle Gerät entsteht an vier Stellen (Handeintrag, CSV-Import, Schnitt, GPX-Import) als echte `devices`-Zeile — wer nur von Hand dokumentiert, fällt aus genau der Gruppe heraus, die die Kleinzeile „sie tragen von Hand nach" meint. `GERAETE_ECHT_SQL` steht in `db.php`; fünf Abfragen benutzen sie, drei schreiben das `LIKE` von Hand |
| 191 | Der von R38 bestellte Index auf `missions(started_at)` fehlt | **P5c** | Fund 14.09.2026: nie gelegt (`schema.sql` führt nur `uq_dev_ref`, `idx_user_started`, `idx_day`). Die S8-Seite zählt nach Diensttag und braucht ihn nicht; das Dashboard zählt nach `started_at` und braucht ihn — die einzige Schemaarbeit des Minimalumfangs. Hängt an Nr. 192: nötig nur, wenn dort `started_at` gewinnt |
| 192 | R38 und die S8-Statistikseite zählen Verschiedenes | **Entscheidung Backlog-Runde, Umsetzung P5c** | Fund 14.09.2026, drei Abweichungen: „aktiv" als ODER gegen zwei getrennte Zeilen · 7/30/180 Tage gegen 24 h/7 T/30 T (die Fenster stehen schon als **Nr. 122**) · Zählung nach `days.day` gegen `started_at`. Die Seite setzt R38 nicht um — sie ist der vorgezogene Teil von Nr. 80; entsteht das Dashboard, stünden zwei Zählweisen nebeneinander |
| 193 | Register und Doku führen die R42-Auswertung als offen | Backlog-Runde, mit **Nr. 177** | Fund 14.09.2026: Abschnitt 5 und Backlog Nr. 80 sind nachgezogen, die Zeilen R42 und R64 in Abschnitt 7, `Technik.md`, `Handbuch.md` 10 nicht. Der Handbuchsatz ist eine **Zusage**, keine Statusangabe. Zweiter Beleg für **Nr. 188**: vier Stellen, neun Tage lang falsch, neben grünen Zahlen |
| 194 | Handbuch nennt den Verschlüsselungsumfang dreimal ohne die Notizen | **vor 1.0** (R72, P7) | Nebenfund 14.09.2026: Seit Web 19.0.0 sind die Einsatz-Notizen verschlüsselt (`mission_fields.php`, `Technik.md` 4.98, `CLAUDE.md` 4). Handbuch **4.3** weiß es (viermal), Einstieg, Kapitel 5 und der **Textbaustein zum Übernehmen** in 11.5 (Nr. 138) nicht — der Einstieg sagt sogar das Gegenteil. Ein Absatz, der in eine Rechtserklärung kopiert werden soll |
| 196 | 68 von 195 Backlog-Einträgen rendern auf GitHub als grauer Kasten — und der Rahmenplan schlimmer | Backlog-Runde, mit **Nr. 188** und **Nr. 199** | Fund 15.09.2026, gemessen mit cmark-gfm: Ab Nr. 100 ist der Listenmarker ein Zeichen breiter, die Datei rückt aber durchgehend mit vier Leerzeichen ein — bei dreistelligen Nummern endet der Listenpunkt nach dem ersten Absatz, alles Weitere wird Codeblock. **Vier verlieren dabei eine Tabelle** (123, 187, 192, 193). Nachgemessen nach dem Merge des Demo-Ausbaus: **68 von 195**. **Der Rahmenplan ist seither mitgemessen und schlimmer dran** — Abschnitt 10 rendert **8 von 58** Fassungszeilen als Tabelle (eine Leerzeile im Eintrag zu Fassung 61 beendet sie), und die Fahrplanzeile 9c verliert ihren ganzen Statustext an drei ungeschützte Pipes in einem Code-Span. Beides älter als der Demo-Ausbau. Mechanisch in einem Zug, nicht in Teilen |
| 198 | Die Zeitraumübersicht zählt Winde und Bergwacht nur luftgebunden | **10c, AP9** | Aufgenommen 14.09.2026 in 9d/AP0 (F-DA-4). Seit R80 darf ein bodengebundenes Rettungsmittel vom Typ Bergwacht Fähigkeiten führen; `api/range.php` beantwortet `faehigkeiten` aber weiter über `d.kind = 'air'`, und die beiden Windenkacheln stehen nur im Luft-Kachelsatz. Ein Bergwacht-Diensttag am Boden zeigt seine Windenfelder im Formular und fehlt in der Auswertung. **Warum nicht gleich mit:** zwei Kacheln mehr im Bodensatz wären zehn Kacheln in vier Spalten — eine Gestaltungsentscheidung, die eine Freigabe mit Mockup braucht. Der Kommentar an der Abfrage sagt seither, dass die Zeile eine Lücke ist und keine Herleitung — Umverteilt 20.09.2026 (E-P5c-20) — Kacheln nach Typ, Mockup mit der 10c-Runde. |
| 199 | Die Backlog-Nummer 5 fehlt, obwohl der Changelog sie unter *Erledigt* verortet | Backlog-Runde, mit **Nr. 196** | Aufgenommen 15.09.2026 beim Gegenlesen des Merges von PR #47. `grep -cE '^5\. '` liefert **0**; die Kopfnotiz des Backlogs führt als fehlend nur 4, 6 und 7, der Changelog zu Web 7.2.0 sagt zweimal, Nr. 5 stehe unter *Erledigt*. Das verletzt die Hausregel „Nummern bleiben, Erledigtes wird verschoben statt gelöscht": Ein Verweis auf Nr. 5 löst ins Leere, und weil die Kopfnotiz sie nicht als frei führt, sieht das niemand. Dritter Beleg für **Nr. 188**. Entweder Eintrag wiederherstellen oder die 5 als dauerhaft frei führen; beides zugleich geht nicht |
| 200 | Bounce-Postfach per IMAP (PP-7 Empfohlen) | **10c, AP10** | Aufgenommen 15.09.2026 (Konzept P5a, E-P5a-14): Die Warteschlange zählt Versuche und führt Unzustellbares; was die Gegenstelle später zurückschickt, sieht sie nicht — Umverteilt 20.09.2026 (E-P5c-21, F-P5c-3) — die Unzustellbar-Liste bekommt dort ihre Reiter. |
| 201 | `Retry-After` in Uhr und Handy auswerten | **P7** (Store-Fassung der Apps) | Aufgenommen 15.09.2026 (Konzept P5a, Befund 1.7): beide Clients wiederholen zum nächsten eigenen Anlass; genügt für 10–60 min — Umverteilt 20.09.2026: wartet auf einen Anlass, und P7 ist er. |
| 202 | **Zentralisierung Web — eine Stelle je Sache** (Sammelnummer, sechs Pakete) | **Schritt 15** | Aufgenommen 16.09.2026 aus der Analyse einer eigenen Sitzung; Paket 3 (CSRF-Teil) auf dem P5a-Zweig vorgezogen, der Log-Helfer geht nach 10c. **Paket 1 ist ganz durch** — die Zeile sagte bis hierher „teils": Mailrahmen, `app_url()` und Empfängerliste in P5a/AP5, der Token-Teil in **P5b/AP2** (16.09.2026, Web 20.17.0, E-P5b-11) mit `konto_lib.php`, `konto_anlegen()`, `reset_token_ausstellen()` und den Laufzeiten als `TOKEN_EINLADUNG_S` / `TOKEN_RESET_S`; alle vier Stellen sind umgezogen, `grep -rn "INSERT INTO password_resets" server/` trifft nur noch die Bibliothek (2 Zeilen). **Hier statt in Schritt 15**, weil die Selbstregistrierung aus AP3 sonst die fünfte Fassung geworden wäre — die einzige, die von außen erreichbar ist. Die Pakete 2 und 4 bis 6 stehen, die Sammelnummer bleibt deshalb bei Schritt 15 |
| 228 | Proof-of-Work im Browser für die Registrierung | niedrig; nur auf Anlass | Aufgenommen 17.09.2026 (Konzept P5b, R37 (4) „notfalls"): bewusst **nicht** gebaut. Honeypot, Mindestausfülldauer und der Topf `regz` (drei Mails je Zieladresse in 24 h) sollen die Last zuerst nehmen; ein Proof-of-Work kostet jede ehrliche Registrierung Rechenzeit. **Der Auslöser ist eine Zahl, kein Gefühl** — Spam, der trotz dieser drei durchkommt |
| 229 | Uhr zeigt bei `403` den Grund aus dem Rumpf statt einheitlich „abgemeldet" | **P7** (Store-Fassung der Apps) | Aufgenommen 17.09.2026 (Konzept P5b, AP2). Seit Web 20.17.0 antwortet `ingest.php` auf ein Konto, das nicht `aktiv` ist, mit `403` und dem Rumpf `{"error":"konto","grund":…}` — `unbestaetigt`, `wartet` oder `gesperrt`. Die Uhr puffert in allen drei Fällen richtig und verliert nichts; sie sagt nur dreimal dasselbe Falsche, und wer gesperrt ist, sucht den Fehler bei der Kopplung. Eine eigene Uhr-Stufe lohnt dafür nicht — Umverteilt 20.09.2026: wartet auf einen Anlass, und P7 ist er. |
| 230 | Wegwerfdomain-Liste mit jeder Auslieferung nachziehen | Pflegeaufgabe; vor dem Tag `web-vX.Y.Z` | Aufgenommen 17.09.2026 (Konzept P5b, E-P5b-23). **Nichts davon liegt heute im Repositorium** — `tools/wegwerfdomains/aktualisieren.py` und `server/wegwerfdomains.txt` entstehen erst mit AP3 (gemessen 17.09.2026: beide fehlen, ebenso `registrieren.php`); der Schalter „Wegwerfadressen abweisen" steht seit Web 20.21.0 da und weist nichts ab. Geplant: Das Werkzeug holt `disposable-email-domains` (CC0, bei der Auswahl am 16.09.2026 mit 8 870 Zeilen gemessen), zeigt den Unterschied und schreibt die Zieldatei — von Hand und nie zur Laufzeit (R36). Eine Liste, die einmal erzeugt und nie nachgezogen wird, wird mit jedem Monat durchlässiger, und niemand merkt es: Die Registrierung läuft weiter fehlerfrei |
| 232 | Fristen der beiden Rückfragen sind nie im Betrieb abgelaufen | niedrig; wenn die nächste Frist dazukommt | Aufgenommen 17.09.2026 (P5b/AP9). Geprüft wurde mit **gestelltem `rueckfrage_naechste`**, nicht mit gestellter Uhr: Die Runden 0 → 1 → 2 → 2 und das Datum 2027-03-17 sind belegt, ein Zeitzonenfehler von einem Tag fiele so **nicht** auf. Zu schließen wäre es, indem `einstieg_lib.php` eine Zeit hereingereicht bekommt, statt sie zu holen — für zwei Fristen ist der Umbau teurer als der Fehler |
| 233 | Betreiber-Rückfrage fragt nie nach dem bisherigen Server-Anteil | niedrig; gehört zu S10c | Aufgenommen 17.09.2026 (P5b/AP9). Während einer Anteilsrotation steht `kdf_anteil_alt` mit auf dem Schlüsselblatt; die Rückfrage fragt ihn nicht ab, weil eine Frage mit je nach Betriebslage vier oder sechs Feldern mehr verwirrt, als sie prüft. **Der Rotationsvorgang selbst** ist die richtige Stelle, das Neudrucken des Blattes anzusagen — er weiß, ob ein alter Wert noch gebraucht wird |
| 207 | `gen-em.org` steht 96× in `tools/` | **Schritt 17** | Einschub 20.09.2026 |
| 209 | Bausteintabelle in `Design.md` | **Schritt 17** | Einschub 20.09.2026 |
| 210 | Deadlocks in `ingest.php` bei gleichzeitigen Uploads | **Schritt 18** | Einschub 20.09.2026 |
| 211 | `/api/`-Aufruf ohne Sitzung | **Schritt 17** | Einschub 20.09.2026 |
| 212 | Zwei Erwartungen der Wiederherstellungsprobe | **Schritt 17** | Einschub 20.09.2026 |
| 213 | Zustandsdatei der Kette im Webroot | **Kette II** (AP2–AP8) | aus der Kette geboren; Einschub 20.09.2026 |
| 214 | `install.php` in der Auslieferung | **Kette II** (AP2–AP8) | aus der Kette geboren; Einschub 20.09.2026 |
| 216 | aus der Durchsicht des P5a-Abschlusses | **Schritt 17** | Einschub 20.09.2026 |
| 222 | Aufbau des Uhr-Prüfstands | **Kette II** (AP2–AP8) | Einschub 20.09.2026 |
| 227 | Symbolregel zählt Typografie | **Schritt 17** | Einschub 20.09.2026 |
| 234 | aus der Kette | **Kette II** (AP2–AP8) | Einschub 20.09.2026 |
| 236 | aus der Kette | **Kette II** (AP2–AP8) | Einschub 20.09.2026 |
| 237 | Täter-Finder des Bilderlaufs findet den Täter nicht | **Kette II** (AP2–AP8) | Einschub 20.09.2026 |
| 238 | `missions.manual` bricht die Einrichtung auf MySQL 8.4.0–8.4.10 | **Schritt 17** | **mit PR #60 umgesetzt** (Web 20.25.0); offen ist die Prüfung durch die Betreiberin. Fällt heraus, sobald die Erledigt-Zeile steht |
| 239 | `backup_lib.php` baut sein `INSERT` ohne Backticks | **Schritt 17** | mit PR #60 behandelt; siehe Nr. 238 |
| 240 | Der Rundlauf-Prüffall des Handy-Moduls läuft in der Kette nie | **Kette II** (AP2–AP8) | am 20.09.2026 von der Kette-II-Instanz selbst vergeben — **bestätigt: Kette II** |
| 242 | Sitzungsbindung per Cookie-Token | **Schritt 18** | Einschub 20.09.2026 (E-SA-09) |
| 243 | Staging-Umgebungsbanner | **10c, AP1** | Einschub 20.09.2026 (E-P5c-05) |
| 244 | Einstellungen-Übersicht: Bereiche als Gliederung erkennbar | **10c, AP9** | Einschub 20.09.2026; Mockup mit der 10c-Runde |
| 245 | Erklärtext-Regel und Überarbeitung aller Texte in Verwaltung und Betrieb | **10c, AP9** | Einschub 20.09.2026; Regel kommt in `docs/Design.md` — geschrieben von 10c, nicht hier |
| 246 | Schlüsselblatt und Notfallblatt: Druckseite | **10c, AP9** | Einschub 20.09.2026; ändert E-P5b-09 |
| 247 | Serverschlüssel wechseln — als Vorgang | **Schritt 18** | Einschub 20.09.2026 (aus V4 der P5c-Vorbereitung) |
| 248 | Prüfschritt Stufe 1 zählt die `error_log(`-Aufrufe | **10c, AP3** | Einschub 20.09.2026; entsteht und erledigt sich mit AP3 |
| 249 | TOTP-Reset der einzigen BetreiberIn | **Schritt 18** | Einschub 20.09.2026; Wiederanlauf-Fall fürs S10-Runbook |

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
| **Prüfliste 9a — P-1 bis P-12** (`docs/konzepte/Pruefdokument-Sofortpaket-Sicherheit.md`). **Nachgetragen mit Fassung 47:** Der Kopf nannte sie seit dem Merge als fällig und verwies für „alles Weitere" auf diesen Abschnitt — hier stand sie nicht. Zwölf Punkte zum Sofortpaket Sicherheit, darunter die Rundenzahl 600 000 am echten Konto, der E-Mail-Nachweis und die Integritätswache | Schritt 9a | **fällig seit dem 08.09.2026** (PR #37) |
| **Prüfliste Backlog-Runde 2 — sieben Punkte** (`docs/konzepte/Pruefdokument-Backlog-Runde-2.md`). **Nachgetragen mit Fassung 47** aus demselben Grund; die Runde ist am 12.09.2026 gemergt (PR #41, Web 19.2.0 und 19.3.0) | Schritt 9, Runde 2 | **fällig seit dem 12.09.2026** |
| **Prüfliste Backlog-Runde 3 — fünf Punkte** (`docs/konzepte/Pruefdokument-Backlog-Runde-3.md`): Demo-Konto am Produktivstand anmelden (Punkt 1 — das Risiko ist kleiner, als es in AP4 aussah: `KDF_ITER_LISTE` hat seit Nr. 155 einen Eintrag, und dann kann `unlock.js` den geänderten Weg gar nicht auslösen; der Punkt bleibt für das nächste Anheben des Zielwerts stehen), einen Einsatz mit Reanimation ansehen, Handbuch 11.2 lesen, den neuen `Confirmation`-Abschnitt der Uhr-LIESMICH gegen die eigene Erinnerung halten (die Runde hat ihn **nicht** nachgemessen — kein Simulator), und die Freigabe des Abschlusses | Schritt 9, Runde 3 | **fällig nach dem Merge** |
| **Prüfliste Mockup-Runde 9c — sieben Punkte** (`docs/konzepte/Pruefdokument-Mockup-Runde.md`): die Bewegung des Blattes und der Schublade am Gerät (Punkt 1 und 1a — fühlen sich 240 ms richtig an?), die dritte Kartengröße am Handy (2), der Import mit abweichender Besatzung (3), das Treffziel des Chip-`×` (4), und **zwei Punkte, die nur echtes Safari zeigt** (6a, 6b) — der Prüfstand fährt Playwrights WebKit, gleicher Kern, anderer Unterbau | Schritt 9c | nach dem Merge |
| **Prüfliste S10 — P-01 bis P-14** (`docs/konzepte/Pruefdokument-S10-Sicherheit.md`). Die fünf Betriebsposten oben sind die ersten Punkte davon; dazu kommen der **Ausdruck auf Papier** (P-02 — gemessen ist die *gerechnete* Druckansicht bei 210 mm, ob ein Drucker die Vierergruppen so setzt, sagt nur ein Ausdruck), die echte `config.php` des Hosters (P-06, Beschreibbarkeit und OPcache), der **Versand gegen die echten Gegenstellen** (P-12, FTPS-Zertifikat und SFTP-Hostschlüssel) und ein bestehendes `ftp`-Ziel, falls es eines gibt. **Abschnitt 0 des Prüfdokuments** sagt zuerst, was auf dem Prüfstand *nicht* herzustellen war | Schritt 9b | **fällig** — seit dem Merge am 14.09.2026 (`965ec11`) |
| ~~**Freigabe des S10-Abschlusses**~~ | Schritt 9b | **erteilt 14.09.2026** — das Konzept ist gelöscht (Historie: `a00f6b5`), das Prüfdokument bleibt bis zum Abhaken seiner Prüfliste |
| **Freigabe des Abschlusses der Mockup-Runde 9c** — danach löscht K9 das Konzept (`Konzept-Mockup-Runde.md`); das Prüfdokument bleibt, bis seine Prüfliste abgehakt ist | Schritt 9c | vor dem Merge |
| ~~**OSRM-Routen für die neuen Bodeneinsätze zuliefern**~~ (H-DA-1) | Schritt 9d | **gegenstandslos 15.09.2026** — `router.project-osrm.org` antwortete der Umsetzungssitzung mit HTTP 200; die 24 neuen `strecke_*.geojson` sind selbst geholt und eingecheckt (E-DA-22) |
| **Nach dem Merge von 9d: im Adminbereich unter Demo-Konto einmal „Auf Standard zurücksetzen" drücken** — der Deploy legt nur die neue `fixture.json.gz` ab; das bestehende Demo-Konto zeigt bis zum nächsten Reset den alten Bestand. Der Reset läuft ohnehin spätestens 30 Minuten nach der nächsten Anfrage, aber dann unangekündigt bei einer Besucherin (rund 6,6 s Wartezeit, Backlog Nr. 76). **`update.php` ist nicht fällig** — 9d bringt keine Migration | Schritt 9d | **fällig** — seit dem Merge am 15.09.2026 (PR #48, `7f334cb`) |
| **Prüfliste Demo-Ausbau 9d** (`docs/konzepte/Pruefdokument-Demo-Ausbau.md`) und die **Freigabe des Abschlusses** — danach löscht K9 das Konzept (`Konzept-Demo-Ausbau.md`), das Prüfdokument bleibt bis zum Abhaken | Schritt 9d | **fällig** — seit dem Merge am 15.09.2026 (PR #48, `7f334cb`) |
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
| **`tools/geraetemodelle/nachaufloesen.php` auf dem Produktivserver fahren — und zuerst sagen, ob er dort je gelaufen ist.** Backlog Nr. 80 macht den Lauf zur Bedingung **vor der ersten Auswertung**; die erste Auswertung ist mit **S8/AP4** gebaut und seit Web 15.3.0 ausgeliefert — die Bedingung ist also nicht künftig fällig, sondern **überschritten**. Ohne ihn tragen alle Geräte, die vor dem Füllen der Modelltabelle gekoppelt haben, die **ungeprüfte Selbstauskunft**: Ein Radcomputer, der sich „uhr" nennt, steht in Betrieb → Statistik als Uhr, und die Zahl sieht richtig aus. **Zwei Hürden, nicht eine:** Der Deploy lädt **nur `server/`** hoch (`deploy.yml`) — die Datei liegt unter `tools/` und ist auf dem Server gar nicht vorhanden, sie müsste mit; und sie braucht **Shell-Zugriff**, hängt also an der Hosting-Entscheidung weiter unten. Gibt es keine Shell, bleibt nur der langsame Weg, den `Technik.md` 7 nennt: Die Geräte holen die Angabe bei der **nächsten Kopplung** nach — nicht in einem Zug und nicht für getrennte Geräte | Nr. 80, R42 | **fällig seit dem S8-Deploy** (aufgefallen 14.09.2026) |
| **Datenschutzerklärung um die Gerätekennung ergänzen** — seit Web 12.9.0 wird beim Koppeln Art und Modell erhoben; Backlog Nr. 80 macht die Nennung zur Vorbedingung der Auswertung — **und die ist mit S8/AP4 bereits entstanden** (Befund 14.09.2026, Nr. 80; die Zeile stand bis dahin auf „vor jeder Auswertung (P5)"). Der Text entsteht nach R60 aus einer Bestandsaufnahme des gesamten Projekts | Schritt 11, vor v1.0 | **überschritten** — die erste Auswertung läuft seit Web 15.3.0; der Text steht weiter aus |
| **Datenschutzerklärung: die beiden Kopplungs-Mails nennen.** Aufgefallen bei der Play-Console-Vorbereitung (Schritt 6, Teil C) und hier vermerkt, damit es beim Schreiben des Textes nicht durchrutscht: `pair.php` verschickt **nach jeder erfolgreichen Kopplung** eine Nachricht an die Kontoadresse — mit der **Gerätebezeichnung** (Art und gekürzter Modellname, etwa „Uhr · fēnix 6X Pro …"; einen **Hersteller speichert die Anwendung nirgends**, sie leitet ihn erst in der Statistik ab — hier stand bis zum 14.09.2026 „Hersteller und Modell"), Zeitpunkt und dem Weg, das Gerät wieder zu entfernen — und **beim Trennen** eine zweite, die allerdings **nur Geräte-ID und Zeitpunkt** nennt, keine Gerätebezeichnung (nachgemessen 14.09.2026 an `pair.php`; hier stand bis dahin, beide trügen sie). Das ist eine Verarbeitung personenbezogener Daten (Kontoadresse) mit einer Geräteangabe darin; sie geschieht auf dem Server, nicht in der App, und taucht deshalb im Datensicherheitsformular der Play Console **nicht** auf. In der Datenschutzerklärung gehört sie genannt. Die Mails sind gewollt und sollen bleiben: Sie sind die einzige Stelle, an der eine unbemerkte Fremdkopplung auffiele | Schritt 11, vor v1.0 | mit dem Datenschutztext |
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
| **Server-Anteil anlegen** — Betrieb → Servereinstellungen, Karte „Schlüssel des Servers" → *Server-Anteil anlegen*. **Ohne diesen Griff tut S10 nichts:** Die Hüllen bleiben `edk1:`, die Anwendung läuft wie vorher, und die Statuszeile steht rot (E-S10-09, Zustand „nicht eingerichtet") | Schritt 9b | nach dem Merge von S10 |
| **Schlüsselblatt drucken — zwei Ausdrucke, zwei Orte** (Betriebsakte und Passwortmanager der Betreiberin); danach Ablageort in der Betriebsakte vermerken. Es trägt **beide** Geheimnisse: Serverschlüssel und Server-Anteil, je mit Kennung | Schritt 9b | unmittelbar nach dem Anlegen |
| **Einmal anmelden und auf Status nachsehen** — die Zeile „Server-Anteil" zählt die stille Umstellung `edk1:` → `edka1:` je Konto mit. Bewegt sie sich beim zweiten Anmelden nicht mehr, ist das Konto umgestellt | Schritt 9b | nach dem Anlegen |
| **Ein `ftp`-Ziel, falls vorhanden, auf SFTP oder FTPS umstellen** — Einstellungen → Backup-Ziele. Bestehende `ftp`-Ziele tragen ab S10 eine rote Plakette und werden **nicht mehr beschickt** (E-S10-14) | Schritt 9b | nach dem Merge von S10 |
| **Wiederanlaufpaket um den Server-Anteil ergänzen** — es hat ab S10 **vier** Stücke: `config.php`, Serverschlüssel, Server-Anteil, Zugang zum Backup-Ziel. `config.php` ist damit Schlüsselträger **aller** Konten; das Blatt ist Pflicht, nicht Empfehlung (E-S10-17). Der Wiederherstellungsschlüssel öffnet weiterhin **ohne** Anteil | Schritt 9b | mit dem Blatt |
| ~~Freigabe des S8-Konzepts und seiner Mockups; darin die Entscheidung zur Bedienhöhe am Schreibtisch (Nr. 74)~~ | Schritt 7 | **erledigt 05.09.2026** — E-S8-01 bis -18 bestätigt, Mockups 01 und 03–12 freigegeben (02 verworfen), Bedienhöhe als zwei Stufen entschieden (R76) |
| **Play-Store-Beitrittslink des internen Tests** — für die Karte „App installieren" auf der Geräte-Seite (S8 AP6). Ohne ihn steht dort die Zeile ohne Knopf; die Adresse ist danach an **einer** Stelle nachzutragen (Konstante `PLAY_TEST_URL`) | Schritt 7 (S8 AP6), R65 | vor der Produktionsfreigabe |
| **Adresse der Uhr-App im Connect-IQ-Store**, falls sie dort veröffentlicht ist — dieselbe Karte, dieselbe Mechanik (Konstante `CONNECT_IQ_URL`) | Schritt 7 (S8 AP6) | wenn die Uhr-App im Store steht |
| **Prüfliste des S8-Prüfdokuments abarbeiten** — Bedienwege, die keine Maschine fahren kann: die Migration auf dem Produktivserver (Rollen vorher/nachher), der Kopieren-Knopf in einem **zweiten Browser**, Mengen und Laufzeiten an echten Daten (Status, Statistik, Speichermessung), der Fall „Freigabe läuft, Zielkonto gelöscht" | Schritt 7 (S8) | nach dem Ausrollen; danach wird auch das Prüfdokument gelöscht (R62) |
| ~~Hosting-Entscheidung (Cron/SSH, DB-Kontingent, `max_user_connections`, DDoS-Schutz, Verschlüsselung at rest)~~ | P5-Konzept | **entschieden 15.09.2026 (R81):** der Betrieb bleibt beim jetzigen Hoster; die fünf Punkte sind **hosterneutral** als Plattformprofil in zwei Stufen festgelegt (`docs/konzepte/Vorbereitung-P5-Plattformprofil.md`, PP-1 bis PP-9). **Offen:** Freigabe der Vorbereitung — darin vier Festlegungen zum Gegenlesen (F-PP-1 bis -4) |
| GitHub im **jetzigen** Repositorium, vor P5a AP1: Umgebungen `staging` (drei FTP-Geheimnisse) und `produktion` (drei FTP-Geheimnisse plus `JOBS_TOKEN`, **Pflichtfreigabe** durch die Betreiberin), Zweigschutz `main` mit `pruefung` als Pflichtprüfung (**Abschnitt 6b** — sie greift nur zusammen mit „Require a pull request“), ein Prüfkonto auf Staging als Umgebungsgeheimnis (Konzept P5a, E-P5a-10, -12, -13). **Schritt für Schritt: Abschnitt 6a.** *Stand 16.09.2026, abends:* **Die abhakbare Liste ist Abschnitt 6a — dort steht der Stand, nicht hier.** Belegt durch den Lauf vom 16.09.2026: `FTP_ZIELPFAD` trägt (647 Einträge synchronisiert), `STAGING_URL`, `STAGING_KONTO` und `STAGING_PASS` sind gesetzt, die Umgebung `produktion` ist angelegt. **Offen und am 16.09.2026 gemessen: der Zweigschutz** — `main` trägt `protected: false`. **Nicht gemessen** ist, ob in `produktion` die Pflichtfreigabe, `JOBS_TOKEN` und `PRODUKTION_URL` wirklich stehen; das zeigt erst der erste Tag-Lauf (P2, P3) | P5a AP1 | vor dem Merge von AP1 |
| ~~V1 und V2 aus der P5c-Vorbereitung entscheiden~~ **V1 entschieden 16.09.2026: gehalten** — Betriebsereignisse, keine Datenzugriffe. **V2 beantwortet mit dem P5b-Konzept (E-P5b-06):** IP-Adressen nur im Reiter *Sicherheit* (Sperren, Angriffe), Frist **30 Tage fest** (= E-P5a-09); der Reiter *Verwaltung* hält **365 Tage, einstellbar 90–1 095**, die übrigen fünf Reiter 30 Tage fest. Gebaut in 10b/AP1 (Web 20.16.5), nachgemessen an drei Fristfällen: 31 d E-Mail weg, 31 d Verwaltung bleibt, 366 d Verwaltung weg. **Damit ist V3** (Aufbewahrung je Reiter) **der Sache nach mit beantwortet** — alle sieben Reiter haben ihre Frist; offen bleibt sie für das versiegelte Archiv, das an V4 und V6 hängt. **Die Fristlänge selbst braucht nach der Vorbereitung eine juristische, keine technische Bestätigung** — sie gehört in dieselbe Vorlage wie die drei Rechtstext-Entwürfe (Zeile weiter unten). **V4–V9 bleiben** | 10c-Konzept (V3-Rest, V4–V9); V1 und V2 wirken schon im Schreibweg aus 10b | V1 und V2 erledigt, V3 bis auf das Archiv; der Rest vor dem 10c-Konzept |
| Nachträge an die P5a-Instanz übergeben: Doku-Paket (Fassungen 73–74, Backlog 200–205) auf den Zweig; Mailrahmen und `app_url()` in AP5; kein Empfänger im SMTP-Log (Nr. 204); AP4a mit `use_strict_mode` und `json_roh_out()` (Nr. 203, 205); R83 für AP8–AP10 | P5a | **übergeben 16.09.2026** (Anweisung `Prompt-P5a-Nachtraege-2026-09-16.md`) |
| **P5a-Reste nach dem Merge:** die zwölf in Abschnitt 5 als erledigt gekennzeichneten Nummern aus der Tabelle nehmen (wie Fassung 32 für dreizehn); die **33 Punkte der Prüfliste** abarbeiten — zehn davon (P1–P8, P12, P33) gehen erst, wenn Umgebungen, Pflichtfreigabe und Zweigschutz stehen (6a) | P5a | nach dem Merge |
| Staging-Installation samt FTP-Zugang; **samt Demo-Konto, Referenzdatensatz und Messstand-Konto — Staging ist die Prüfumgebung (R67)**. **Ziel seit 20.09.2026 (E-KH-04): `staging-nadoku.gen-em.org` bei lima-city** — einem **anderen** Hoster als Produktiv, weil beide Anlagen zuvor unter demselben Systemnutzer liefen und Staging-PHP Produktivs `config.php` lesen konnte. Damit ist E-PP-09 in Adresse und Hoster ersetzt; Absender `staging@gen-em.org`, Betreff-Präfix „[Staging]", eigener Serverschlüssel und Server-Anteil sowie ein eigenes **SFTP-Sicherungsziel** gelten unverändert. **Die alte Anlage ist am 20.09.2026 stillgelegt** (Zuarbeit Z1 des Konzepts Kette II). **Offen:** die Einrichtung der neuen Anlage — **die abhakbare Liste steht in Abschnitt 6a**, der Stand dort und nicht hier | P5-Beginn | Einrichtung bis zum ersten Code-Paket von P5 (`deploy.yml`-Umstellung); die Prüfkonten danach. **Neu seit dem Umzug:** vor Kette II/AP5 (Zuarbeit Z7) |
| GitHub-Umgebung „produktion" mit Pflichtfreigabe (Betreiberin) und den FTPS-Zugangsdaten der Produktion als Umgebungsgeheimnisse; GitHub-App auf dem Handy mit Push-Nachrichten; prüfen, ob `CIQ_GERAETE_URL` als CI-Secret taugt (Stufe 1) | R67, Freigabe-Tor | mit dem Aufbau der Kette in P5 |
| SPF/DKIM/DMARC der Versanddomain, Bounce-Postfach | P5 | vor der P5-Abnahme |
| **Anwaltliche Prüfung der drei Rechtstext-Entwürfe** (E-P5b-24) — `docs/rechtstexte/Nutzungsbedingungen.md`, `AVV.md` (übernimmt die EU-Standardvertragsklauseln nach Durchführungsbeschluss (EU) 2021/915 unverändert durch Verweis und füllt die Anlagen I–IV aus) und `Datenschutz-Ergaenzung-P5.md` (elf Bausteine B1–B11 zum Einarbeiten in die bestehende Erklärung). Darin stecken die Festlegungen vom 16.09.2026: Gen-EM GbR als Vertragspartner, unentgeltlich ohne Verfügbarkeitszusage, Haftung nur für Vorsatz und grobe Fahrlässigkeit mit den gesetzlichen Ausnahmen, Nutzerkreis ärztlich im Rettungsdienst mit Zusicherung der Berechtigung, deutsches Recht, Subauftragsverarbeiter dataforest (Hosting) und lima-city (Mail). **Mitprüfen lassen: die 30-Tage-Frist für IP-Adressen** (E-P5b-06) — die P5c-Vorbereitung verlangt dafür eine juristische, keine technische Bestätigung. **Geschrieben sind die Texte, geprüft nicht** — deshalb hat AP4 (Web 20.19.0) die Mechanik gebaut und die Texte weggelassen: Ein Text ohne `stand_am` gilt als „nicht in Kraft" (`einwilligung_lib.php`, Zeile 98), das Tor in `auth_guard.php` steht still. **Das Einspielen ist der Schalter:** Mit der ersten Fassung landet jedes bestehende Konto beim nächsten Login am Tor — Nutzungsbedingungen und AVV sperren, die Datenschutzerklärung zeigt nur einen Hinweis (E-P5b-05) **Stand 17.09.2026:** Die Entwürfe sind nach E-P5b-25 auf die **private Zweckbestimmung** umgestellt (Nutzung nur für sich selbst, nicht im Auftrag eines Trägers), § 203 StGB ist in der AVV ergänzt (A.7) und die Klartextliste ehrlich gestellt — **geprüft sind sie damit nicht**. Mitzuklären: die Rechtsform der Betreiberin (GbR-Haftung). | Schritt 10b (AP4), R41 | **vor dem Einspielen in `rechtstexte`** — spätestens mit dem Umschalten der Registrierung auf *offen* (E-P5b-01), vor Welle 1. Mit **S11** eine zweite Runde: Dort verschiebt sich die Grenze der Verschlüsselung, und alle drei Texte ändern sich |
| **Datenschutzerklärung des Dienstes** — den Text selbst gibt es weiterhin nicht. Die elf Bausteine aus P5b werden in ihn **eingearbeitet**, nicht an seine Stelle gesetzt, und die drei Nachtragszeilen weiter oben gehören in denselben Text: Gerätekennung (Nr. 80), die beiden Kopplungsmails, Photon samt den vier Kachelanbietern und die Grenze der Verschlüsselung nach Weg C (Nr. 137, 138). **Nutzungsbedingungen und AVV sind aus dieser Zeile ausgezogen** — für sie liegen seit dem 16.09.2026 Entwürfe, sie stehen in der Zeile darüber (E-P5b-24); „ggf. mit rechtlicher Prüfung" stand hier, solange es nichts zu prüfen gab | Öffnung (R41) | vor der ersten Welle |
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
| ~~**Entscheidung zu Backlog Nr. 169**~~ — ein Diensttag mit „Anderem Rettungsmittel" kann **keine Besatzung** festhalten, weder am Tag noch am Einsatz (freigelegt in S9/AP6). Drei Wege stehen im Backlog: **(a)** der Adhoc-Dialog bekommt Rollenhaken wie das Stammdatenformular — ehrlich, aber er wächst um sieben Felder; **(b)** der Tag bietet die Rollen an, die zur Betriebsart passen — billig, aber geraten, und E26 sagt: geraten wird nicht; **(c)** so lassen und im Text sagen — wer die Besatzung braucht, legt das Rettungsmittel an. **Heute gilt (c)**, Hinweis und Handbuch sagen es seit Web 18.1.1 zutreffend. Ohne Entscheidung bleibt es dabei, und das ist ein tragfähiger Zustand — die Zeile steht hier, damit er ein gewählter bleibt und kein vergessener | P5 | **vertagt 12.09.2026 auf P5** — bis dahin gilt (c); Hinweis und Handbuch sagen es zutreffend |
| **Korrigierte Logovorlagen in den Markenfarben** (Nr. 62) — SVG und PNG; die vorliegenden Vorlagen tragen die alten Werte, es gibt keine korrigierte Quelle. Bis dahin passiert am Code nichts, `Design.md` 2.5 sagt den Stand | Nr. 62; `Design.md` 2.5 | vor P7 — zusammen mit dem NEF-Logo (R71) |
| Freigabe je Konzept und je F-Entscheidung | alle | laufend |
| **Nach dem Merge von P5b: `update.php` aufrufen — SECHS Migrationen** (`2026_09_16_protokoll_ereignisse`, `…_konto_lebenszyklus`, `…_einwilligungen`, `…_adresswechsel_bestaetigt`, `…_konto_grenzen`, `2026_09_17_erststart_rueckfragen`). Ein Aufruf verbucht alle sechs. Bleibt er aus, stehen die neuen Karten leer da oder melden „Tabelle fehlt" — **kein Datenverlust**, aber nichts von dem, was P5b bringt, ist dann zu sehen | Schritt 10b | **fällig nach dem Merge** |
| **`betrieb.health_token` und gegebenenfalls `mail.postfach` in `config.php` beider Anlagen** eintragen — der Health-Endpunkt und das Bounce-Postfach lesen sie; ohne sie bleiben 10c AP6 und AP10 ohne Gegenstand | Betreiberin | **vor 10c AP6 und AP10** (Einschub 20.09.2026) |
| **Prüfliste P5b — die Punkte aus `docs/konzepte/Pruefdokument-P5b-Konto-und-Registrierung.md`**, darunter **P9.4 und P9.5** als die beiden wichtigsten: einen neuen Wiederherstellungsschlüssel erzeugen und danach **an einem Prüfkonto** über „Passwort vergessen" belegen, dass der **neue** Schlüssel die Daten öffnet und der **alte** nicht mehr. Dazu die Registrierung in allen drei Betriebsarten, das Einwilligungstor, die Selbstlöschung mit Karenz und die Betreiber-Rückfrage zum Schlüsselblatt. **Abschnitt 0 des Prüfdokuments** sagt zuerst, was auf dem Prüfstand *nicht* herzustellen war — vor allem: die Fristen der Rückfragen sind nie abgelaufen, sondern gestellt worden (Nr. 232) | Schritt 10b | **fällig nach dem Merge** |
| **Freigabe des P5b-Abschlusses** — danach ist das Konzept gelöscht (K9); das **Prüfdokument bleibt**, bis seine Prüfliste abgehakt ist | Schritt 10b | **vorgelegt 17.09.2026** |

### 6a. Staging einrichten — die Reihenfolge, in der es geht

*Aufgenommen 16.09.2026, nachdem in der Umsetzung von P5a die Annahme
aufkam, die Auslieferungskette richte die Instanz selbst ein. **Neu
geschrieben am 20.09.2026** (Konzept Kette II, AP1), nachdem Staging den
Hoster gewechselt hat.*

**Sie tut es nicht.** `auslieferung.yml` überträgt `server/` per FTPS in ein
Verzeichnis, **das es schon geben muss**. Sie legt weder Subdomain noch
Datenbank an, und vor allem legt sie **`config.php` nicht an** — die Datei
steht auf der Ausnahmeliste des Uploads *und* in `.gitignore`. Das ist keine
Lücke, sondern die Zusage: `config.php` trägt den DB-Zugang, das
SMTP-Passwort, den Serverschlüssel und seit S10 den **Server-Anteil am
Datenschlüssel**. Sie darf nie aus dem Repositorium kommen.

> **Staging ist am 20.09.2026 umgezogen — und alle Haken dieser Liste sind
> damit verfallen.** Bis zum 19.09.2026 lag Staging als
> `staging.nadoku.gen-em.org` im **selben Plesk-Abonnement** wie Produktiv,
> mit eigener Datenbank und eigenem FTPS-Konto, aber unter **demselben
> Systemnutzer**: Staging-PHP konnte Produktivs `config.php` lesen, und damit
> reichten die Staging-Zugangsdaten an Pflichtfreigabe und Backup-Tor vorbei
> bis Produktiv (Befund B2 der Kettendurchsicht vom 20.09.2026). Seither liegt
> Staging unter **`staging-nadoku.gen-em.org` bei lima-city**, also bei einem
> **anderen Hoster**; die alte Anlage ist am selben Tag stillgelegt worden
> (Zuarbeit Z1). Die Entscheidung ist **E-KH-04** und ersetzt **E-PP-09** in
> Adresse und Hoster.
>
> **Die Schritte 1 bis 10 waren am 16./17.09.2026 bis auf 7 und 9 abgehakt —
> für die alte Anlage.** Kein Haken davon sagte etwas über lima-city; sie sind
> deshalb alle wieder aufgegangen. Wer den alten Stand sucht, findet ihn in
> der Git-Historie dieses Dokuments (Fassung 80).
>
> **Drei sind am 20.09.2026 neu gesetzt**, jeder mit seinem eigenen Beleg:
> Schritt 1 (die Anlage antwortet über HTTPS), Schritt 3 (FTPS-Konto,
> eingesperrt) und Schritt 4 zur Hälfte (Geheimnisse und `STAGING_URL`
> umgestellt). **Die übrigen stehen offen, mit dem Grund je Zeile** — und
> Schritt 6 **scheitert gerade**, woran alles hängt, was die Anwendung selbst
> wissen müsste.
>
> **Was der Umzug kostet**, steht in `docs/Technik.md` 6.3a: Staging belegt
> kein Plattformverhalten von Produktiv mehr. **Was er bringt**, steht
> daneben: Die Portabilitätszusage aus R81 wird seither mit jedem Push
> geprobt.

Die ersten vier Schritte und die letzten drei sind **einmalig von Hand**.
Erst ab Schritt 5 synchronisiert die Kette. **Die Nummern bleiben, wie sie
sind** — `auslieferung.yml` nennt sie in seinen Fehlermeldungen („Steht die
Subdomain schon? Rahmenplan 6a, Schritte 1 bis 3", „`FTP_ZIELPFAD` prüfen,
Schritt 4"); wer hier umnummeriert, macht aus einer Fehlermeldung einen
Irrweg.

| # | Schritt | Wo | fertig? |
|---|---|---|---|
| 1 | Subdomain `staging-nadoku.gen-em.org` mit **eigenem Verzeichnis** anlegen; HTTPS über den Hoster | Hoster | ☑ *20.09.2026 — belegt: Die Anlage antwortet über **HTTPS** (Apache 2.4, Port 443), `SERVER_NAME` ist `staging-nadoku.gen-em.org`, das Dokumentenwurzelverzeichnis ist ein **eigenes** (`…/nadoku-staging`). Gemessen an einer `phpinfo()`-Ausgabe, nicht von der Kette* |
| 2 | **Leere** Datenbank samt eigenem DB-Nutzer anlegen. `install.php` spielt `schema.sql` selbst ein — die Datenbank legt es **nicht** an | Hoster | ☑ *20.09.2026 — belegt durch Schritt 6: `install.php` ist durchgelaufen, `login.php` liefert die Anmeldeseite mit der Fußzeile der Anwendung (Lauf 21)* |
| 3 | FTPS-Konto anlegen, das **nur** das Staging-Verzeichnis sieht — das ist die Sicherung für `FTP_ZIELPFAD` (siehe Kasten unten) | Hoster | ☑ *20.09.2026 — Auskunft der Betreiberin: **FTPS**, Konto auf `/` eingesperrt, dort liegt der Inhalt von `server/`* |
| 4 | Umgebung **`staging`** anlegen, darin die drei **Environment secrets** `FTP_SERVER` (bloßer Hostname — kein `ftps://`, kein Pfad, kein `:21`), `FTP_USERNAME`, `FTP_PASSWORD`; dazu die **Environment variables** `FTP_ZIELPFAD` und `FTP_STATE_PFAD` (Werte in der Tabelle darunter) sowie `STAGING_URL` | GitHub | ☑ *teilweise, 20.09.2026 — die drei FTP-Geheimnisse und `STAGING_URL` sind auf die neue Anlage umgestellt; `FTP_ZIELPFAD` steht auf `/`. **`FTP_STATE_PFAD` fehlt** (Z4). **Und zwei Geheimnisse sind älter als der Umzug:** `JOBS_TOKEN`, `STAGING_KONTO`, `STAGING_PASS` stammen von der alten Anlage — siehe Schritt 10* |
| 5 | Push auf `main` — **ab hier synchronisiert die Kette** | — | ☐ **Das ist die Abnahme von Kette II/AP1** — und sie ist am 20.09.2026 als **Handlauf** halb gelungen (Lauf 21): Der Abgleich lief **grün** (12 Dateien, 1,13 MB, 12 s), Stufe 2 ist an den **Kreisläufen** rot (Anmeldung des Prüfkontos). **Ein Push auf `main` ist dafür nicht nötig** — `workflow_dispatch` fährt dieselben Jobs, und `produktion` kann dabei nicht anspringen |
| 6 | `https://staging-nadoku.gen-em.org/install.php` im Browser: schreibt `config.php`, legt die BetreiberIn an, setzt `install.lock`. **Eigener Serverschlüssel und eigener Server-Anteil — nie die von Produktiv** | Browser | ☑ *20.09.2026 — gemessen von der Kette selbst (Lauf 21): `login.php` antwortet **HTTP 200** und trägt die Fußzeile der Anwendung. Die Einrichtung war zwischenzeitlich in einen Fehler gelaufen; sie ist behoben* |
| 7 | In `config.php` nachtragen: `smtp` auf `staging@gen-em.org`, dazu `'mail' => ['betreff_praefix' => '[Staging]']` (E-PP-09, gilt weiter) | FTP | ☐ *Zuarbeit Z7 — vor Kette II/AP5; hängt an Schritt 6* |
| 8 | **Demo-Konto aus der Fixture anlegen:** auf Staging als Administratorin anmelden → **Verwaltung → Demo-Konto** → Knopf „Demo-Konto anlegen". Mehr ist es nicht — `server/demo/fixture.json.gz` liegt dort schon, die Kette liefert sie mit | Browser | ☐ *Zuarbeit Z7 — vor Kette II/AP5; hängt an Schritt 6* |
| 9 | Eigenes **SFTP-Sicherungsziel** für Staging eintragen — damit Staging-Stände nie neben Produktiv-Sicherungen liegen | Anwendung | ☐ *Zuarbeit Z7 — vor Kette II/AP5. **Mit dem Hosterwechsel ist das kein Komfort mehr:** Kette II/AP5 fährt das Backup-Tor auch auf Staging, und ohne Ziel gibt es kein Komplett-Backup, das es prüfen könnte* |
| 10 | **`JOBS_TOKEN`** als Environment secret der Umgebung `staging` eintragen — der Wert steht auf Staging unter **Betrieb → Hintergrundjobs** hinter `jobs.php?token=` (Web 20.16.0, Backlog Nr. 219). **Derselbe Name wie in `produktion`, anderer Wert:** Das Token gehört der Installation, nicht dem Repositorium | GitHub | ☑ *20.09.2026 — **gemessen**: Die Job-Pause hat im Lauf 21 zweimal mit `{"ok": true, …}` geantwortet, der Wert gehört also der neuen Anlage. **`STAGING_KONTO` und `STAGING_PASS` dagegen nicht** — die Anmeldung scheitert (Prüfdokument, Prüfpunkt 5)* |

**Die Werte der Variablen — nicht die Geheimnisse.** Sie stehen hier, weil
sie kein Geheimnis sind und weil ein falscher Pfad der teuerste
Einrichtungsfehler dieser Kette ist (Kasten unten). `FTP_SERVER`,
`FTP_USERNAME` und `FTP_PASSWORD` stehen **nirgends im Repositorium**.

| Variable | Staging (lima-city) | Produktiv (Plesk) |
|---|---|---|
| FTP-Wurzel, die das Konto sieht | **`/`, eingesperrt** — dort liegt der Inhalt von `server/` | **`/`, eingesperrt** — dort liegt der Inhalt von `server/` |
| `FTP_ZIELPFAD` | **`/`** (gesetzt) | **`/`** (gesetzt) |
| `FTP_STATE_PFAD` (Ort der Zustandsdatei) | **nicht gesetzt** → Vorgabe `../.deploy-state-staging.json` | **nicht gesetzt** → Vorgabe `../.deploy-state-produktion.json` |
| `STAGING_URL` / `PRODUKTION_URL` | `https://staging-nadoku.gen-em.org` | `https://nadoku.gen-em.org` (`WACHE_BASIS` muss denselben Wert tragen) |
| Anwendungswurzel | `…/nadoku-staging` im lima-city-Webspace | `…/nadoku-produktion` unter einem Plesk-Vhost |

Beide Spalten sind am 20.09.2026 von der Betreiberin genannt (Z3). **Die
Geheimnisse stehen nirgends im Repositorium** — `FTP_SERVER`, `FTP_USERNAME`
und `FTP_PASSWORD` sind hier nur dem Namen nach erwähnt.

> **Beide Anlagen tragen dasselbe Muster — und damit dieselbe unbeantwortete
> Frage: Verträgt ein auf `/` eingesperrtes Konto das `../` der
> Zustandsdatei?** `FTP_STATE_PFAD` ist **in keiner der beiden Umgebungen**
> gesetzt, also gilt beidemal die Vorgabe mit `../` — **eine Ebene über der
> Wurzel, die das Konto überhaupt sehen darf.** Abschnitt 4.97g von
> `docs/Technik.md` sieht genau diesen Fall vor: *„Erlaubt der Käfig des
> FTP-Zugangs kein `../`, bricht der Lauf; dann trägt man die Variable
> `FTP_STATE_PFAD` ein und zeigt wieder nach innen."*
>
> **Ein Alarm ist es nicht, und der Grund gehört dazu:** Die **alte**
> Staging-Anlage fuhr dieselbe Kombination und lieferte aus (Lauf vom
> 18.09.2026, 647 Einträge synchronisiert). Auf **jenem** Server wurde das
> `../` also vertragen oder auf die Wurzel zurückgefaltet. Ob lima-city und
> der Produktiv-Server es ebenso halten, ist **ungemessen**.
>
> **Für Staging ist es am 20.09.2026 beantwortet: lima-city verträgt es.**
> Der Abgleich im Lauf 21 meldet wörtlich
> `Saving current server state to "/../.deploy-state-staging.json"` und endet
> grün. **Für Produktiv bleibt die Frage offen** — dort hat noch kein Lauf so
> weit gereicht; AP3 beantwortet sie mit der Zielprobe. Genau die
> Arbeitsteilung, die E-KH-17 meint: Staging probt, was es proben kann, ohne
> dass jemand Produktiv anfasst.
>
> **Was der Lauf NICHT sagt:** ob die Datei wirklich über dem Webroot liegt
> oder ob der Server `/..` auf `/` zurückfaltet. Der Punktdatei-Schritt
> entscheidet das nicht — `RewriteRule [F]` antwortet **403, ob die Datei da
> ist oder nicht**. `FTP_STATE_PFAD` wird deshalb weiterhin **nicht**
> vorsorglich nach innen gestellt: Das veränderte etwas, das gerade
> funktioniert, und beantwortete die offene Hälfte trotzdem nicht.

> **Was noch offen ist:** Solange `FTP_ZIELPFAD` und `FTP_STATE_PFAD` nicht in
> **beiden** Umgebungen ausdrücklich gesetzt sind, greifen die **Vorgabewerte**
> aus `auslieferung.yml` — und eine fehlende Variable führt still in ein
> fremdes Verzeichnis, statt den Lauf anzuhalten. `FTP_ZIELPFAD` steht in
> beiden Umgebungen, `FTP_STATE_PFAD` in keiner (nachgesehen am 20.09.2026).
> Die Vorgaben fallen mit Kette II/AP6 (E-KH-07) weg; bis dahin ist Zuarbeit
> **Z4** die einzige Sicherung.

**`STAGING_URL`, `STAGING_KONTO` und `STAGING_PASS`** gehören in dieselbe
Umgebung. Sie dürfen früh eingetragen werden — **aber dann ist Stufe 2 rot,
bis Schritt 8 durch ist**, und das ist so gewollt: Ein Stand, der auf Staging
nicht läuft, ist nicht freigabefähig. Solange sie leer sind, überspringt
Stufe 2 und sagt es. *(Mit Kette II/AP6 ist „übersprungen" auf `main` rot,
E-KH-12 — ein Konfigurationsfehler ist kein hinnehmbarer Zustand.)*

> **Warum Schritt 8 NICHT der Einspiellauf ist, und warum das hier steht.**
> Bis zum 17.09.2026 verwies dieser Schritt auf
> `tools/referenzdatensatz/einspielen/`. Das ist der Weg, auf dem die Fixture
> **erzeugt** wird — zehn Stufen, rund vier Minuten Ingest, ein Browserschritt
> fürs Passwort, dazu `demo_kennzeichnen.php`, das `db()` benutzt und deshalb
> **auf dem Rechner der Installation** laufen muss. Für eine ZWEITE
> Installation ist das der falsche Weg.
>
> Der richtige steht im Runbook (`docs/Technik.md`, Abschnitt 7, „Demo-Konto
> einrichten (einmalig)"): Fixture erzeugen, mit ausrollen, im Adminbereich
> anlegen. `demo_anlegen()` nimmt das Konto **vollständig** aus der Fixture —
> Adresse, Passwort-Hash, `kdf_salt`, beide Schlüsselhüllen, `account_key` —
> und spielt den Bestand in einer Transaktion ein.
>
> **Das geht auf einer fremden Installation nur deshalb auf**, weil die Hülle
> in der Fixture `edk1:` ist, also ohne Server-Anteil, und weil
> `demo_anlegen()` die Kennzeichnung in derselben Transaktion setzt —
> `api/kdf_upgrade.php` überspringt das Konto damit beim ersten Anmelden
> (`demo_ist_demo()`). Ohne beides käme das Demo-Konto herein und sähe nichts.
>
> **Das Messstand-Konto aus der alten Fassung dieses Schrittes braucht Stufe 2
> nicht:** Der Messstand-Schritt ist in P5a/AP9 ersatzlos aus der Kette
> gestrichen worden (Backlog Nr. 206) — er ist ein manuelles
> Regressionsmittel und läuft lokal vor einer Auslieferung. **Seit E-KH-04
> misst er ohnehin eine andere Plattform als Produktiv** (`docs/Technik.md`
> 6.3a).

**Dasselbe gilt für `JOBS_TOKEN` aus Schritt 10** (Web 20.16.0): Ohne ihn
können die Kreisläufe die Hintergrundjobs auf Staging nicht anhalten, und ein
Kreislauf ohne Pause misst „hat der Verdichtungsjob dazwischen zugeschlagen"
statt „kommt zurück, was hineinging". Der Schritt wird deshalb **übersprungen
und gesagt**, statt still auf den lokalen Weg zurückzufallen — der scheitert
auf einem Läufer ohnehin an der fehlenden `config.php`.

> **Der teuerste Einrichtungsfehler ist `FTP_ZIELPFAD`.** Steht dort `/` und
> ist das FTPS-Konto **nicht** auf das Staging-Verzeichnis eingesperrt, lädt
> die Kette `server/` in die Wurzel des Webspace — neben oder über alles
> andere, was dort liegt. Schritt 3 ist deshalb kein Komfort, sondern die
> Sicherung von Schritt 4: **ein FTPS-Konto, das nur dieses eine Verzeichnis
> sieht.** Ist es eingesperrt, ist `/` genau richtig.
>
> *Bis zum 19.09.2026 stand hier „neben oder über die Produktivanlage" — und
> das war wörtlich gemeint: Beide lagen im selben Webspace. Seit E-KH-04
> können die beiden Anlagen einander nicht mehr überschreiben, weil sie auf
> verschiedenen Rechnern liegen. **Der Satz bleibt trotzdem stehen**, denn
> ein falscher Zielpfad überschreibt dann eben, was bei lima-city daneben
> liegt — und die Zusage, die Schritt 3 gibt, ist nicht „Produktiv ist
> sicher", sondern „die Kette kann nur dieses eine Verzeichnis anfassen".*

**Und für `produktion` spiegelbildlich:** eine zweite Umgebung mit
**Pflichtfreigabe** („required reviewers“), darin **dieselben drei Namen** mit
den Produktiv-Werten, dazu `JOBS_TOKEN` (Betrieb → Hintergrundjobs) und die
Variable `PRODUKTION_URL`.

> **Warum beide Umgebungen dieselben drei Namen tragen.** Die **Umgebung**
> entscheidet, welcher Wert ankommt — genau dafür gibt es sie. Damit kann ein
> Job die Zugangsdaten der falschen Seite **nicht** erwischen: In `staging`
> gibt es die Produktiv-Werte gar nicht. Mit sprechenden Namen
> (`NADOKU_STAGING_*` gegen `NADOKU_PRODUKTION_*`) wäre ein kopierter Job, der
> den falschen Präfix stehen lässt, ein Deploy auf die falsche Anlage — und
> niemand sähe es, bis er es sieht.
>
> **Und warum Environment secrets und nicht Organisationsgeheimnisse.** Ein
> Organisationsgeheimnis mit „All repositories" ist von **jedem Arbeitslauf in
> jedem Repositorium der Organisation** lesbar. Für die Produktiv-Zugangsdaten
> wäre die Pflichtfreigabe damit eine Formalie: Wer irgendwo im Org eine
> Workflow-Datei anlegen darf, hätte sie. Deshalb gehören sie an die Umgebung,
> hinter das Tor.
>
> **`FTP_SERVER` heißt so, weil der Wert ein Hostname ist.** Ein Feld namens
> `…_URL` lädt dazu ein, `ftps://…/staging` einzutragen; die Aktion setzt den
> Wert unverändert als Server ein, und die Namensauflösung gelingt nie.

**Zwei Riegel prüfen das seit dem 16.09.2026 selbst.**

**Vor dem Deploy:** Beide Deploy-Jobs brechen ab, wenn eines der drei
Geheimnisse fehlt oder der Host ein Schema, einen Pfad oder einen Port trägt.
Grund: **GitHub setzt ein Geheimnis, das es nicht gibt, auf leer und bricht
nicht ab** — die FTPS-Aktion lief damit mit leerem Benutzernamen los und
scheiterte erst an der Gegenstelle, mit einer Meldung über die Anmeldung statt
über den fehlenden Eintrag. Im Produktions-Job steht der Riegel **ganz oben**,
vor dem Backup-Tor: weiter unten hätte der Lauf schon die Wartung
eingeschaltet, und ein vertippter Name ließe die Anwendung zu.

**Vor Stufe 2:** Ein Griff auf `login.php`. Er unterscheidet vier Lagen und
sagt zu jeder, was zu tun ist — Subdomain antwortet nicht (Schritte 1–3),
`404` (die Dateien liegen im **falschen Verzeichnis** — `FTP_ZIELPFAD`,
Schritt 4), Weiterleitung auf `install.php` (Schritte 6–8), oder eine fremde
Seite mit `200`. Die letzte Lage ist der Grund für den Griff: Eine frische
Subdomain liefert beim Hoster eine **„Domain Default page" mit HTTP 200** aus
— gemessen am 16.09.2026 an `staging.nadoku.gen-em.org`, der damaligen
Anlage. Wer nur den Code prüft, hält eine leere Subdomain für eine laufende
Anwendung. **Der Befund überlebt den Hosterwechsel**, die Messung nicht: Ob
lima-city dieselbe Standardseite mit 200 ausliefert, ist **ungemessen** — der
Griff fängt sie ohnehin ab, weil er nicht auf den Statuscode, sondern auf die
Fußzeile dieser Anwendung sieht.

**Ein dritter Riegel kommt mit Kette II** (E-KH-07, AP3): eine **Zielprobe**
als Rundlauf — eine Datei mit Zufallsnamen per FTPS hinein, über die
HTTPS-Adresse wieder heraus, vergleichen, löschen, das Löschen prüfen. Sie
beweist, dass FTP-Konto, Zielpfad und Webadresse **dieselbe Anlage** meinen.
Heute prüft das niemand: `tor.py` spricht mit `PRODUKTION_URL`, der Abgleich
mit `FTP_SERVER` + `FTP_ZIELPFAD`, die Wache mit `WACHE_BASIS` — drei Zeiger
auf drei Namen, und keiner vergleicht sie (Befund F4).

### 6b. Zweigschutz für `main` — und was er wirklich leistet

*Aufgenommen 16.09.2026. Gemessen am selben Tag: `main` trägt
`protected: false`, es gibt also keinen.*

**Warum überhaupt.** Ohne ihn ist Stufe 1 eine **Auskunft** und keine
Schranke: Der Lauf färbt sich rot, und der Stand liegt trotzdem auf `main` —
und damit, seit Web 20.4.0, auf Staging.

> **Der Satz, an dem die meisten vorbeilesen: Eine Pflichtprüfung greift nur
> bei Pull Requests.** Ein direkter Push auf `main` lässt sich nicht von einer
> Prüfung aufhalten, die es zum Zeitpunkt des Pushes noch gar nicht gibt —
> GitHub kann einen Commit erst prüfen, wenn er da ist. Wer „required status
> checks" einschaltet und weiter direkt pusht, hat einen Schalter umgelegt und
> nichts gewonnen.
>
> **Die Schranke ist deshalb die Kombination:** *Require a pull request* **und**
> *Require status checks*. Erst dann heißt „rot" auch „kommt nicht rein".

**Das ändert den Weg ans Ende einer Phase** (`CLAUDE.md` 8). Bisher: Push auf
`main` nach ausdrücklicher Bestätigung. Danach: **Pull Request** vom
Arbeitszweig, Stufe 1 grün abwarten, mergen. Für die umsetzende Instanz heißt
das, sie pusht nie mehr nach `main`, sondern öffnet einen PR — der Merge
bleibt bei der Betreiberin, wie bisher die Bestätigung.

**Einrichtung** — *Settings → Rules → Rulesets → New ruleset → New branch
ruleset*:

| Feld | Wert |
|---|---|
| Name | `main geschützt` |
| Enforcement status | **Active** (nicht „Evaluate" — das misst nur) |
| Target branches | *Add target* → **Include default branch** |
| Restrict deletions | ☑ |
| Block force pushes | ☑ |
| Require a pull request before merging | ☑ |
| — Required approvals | **0** |
| Require status checks to pass | ☑ → *Add checks* → **`Stufe 1`** |
| Bypass list | **leer lassen** |

Drei Fallen dabei:

1. **Der Prüfname ist `Stufe 1`, nicht `Prüfung`.** GitHub listet den Namen
   des **Jobs**, nicht den des Arbeitslaufs. Er taucht in der Auswahl erst
   auf, nachdem er mindestens einmal gelaufen ist — das ist er, `pruefung.yml`
   läuft bei jedem Push auf jeden Zweig.
2. **Required approvals auf 0.** Bei 1 kann die Betreiberin ihren eigenen Pull
   Request nicht mehr mergen und braucht eine zweite Person. In einem
   Ein-Personen-Betrieb ist das keine Sicherung, sondern eine Sperre.
3. **Die Bypass-Liste hebt alles auf.** Wer sich dort einträgt — auch als
   „Organization admin" —, kann weiterhin direkt auf `main` pushen. Dann ist
   der Zweigschutz eine Absichtserklärung.

**Der klassische Weg** (*Settings → Branches → Add branch protection rule*)
tut dasselbe und bleibt gültig; Rulesets sind das, was GitHub heute anbietet,
und sie zeigen auf einer Seite, was gilt.

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
| R4 | Referenzdatensatz wird generiert, über reguläre Wege eingespielt | erledigt (P1: 16 Diensttage, 87 Einsätze); **seit 9d 21 Diensttage und 103 Einsätze in den Quelldaten**, 106 im Bestand |
| R5 | Gespeicherte Namen bleiben; Ausnahmeliste in P7 beschließen (R71) | gilt; Liste zugeliefert und leer |
| R6 | Backlog-Zuordnung (alt) | überholt durch Abschnitt 5 |
| R7 | Ordnerumbau vor P3 | gegenstandslos (E-A6-12) |
| R8 | Gründerfarben präsenter | erledigt in P3 (`Design.md`) |
| R9 | Registrierung in drei Betriebsarten plus Sicherheitspaket | gilt, P5 (konkretisiert in R37). **Vorgabe und Ort seit 10b entschieden** (E-P5b-01): *offen* · *offen mit Freischaltung* · *nur auf Einladung*, Vorgabe **nur auf Einladung** — die sicherste Grundstellung für eine Selbsthosterin, die diese Seite nie aufschlägt, und nicht die, die nadoku fahren wird. Die Einstellung steht seit **Web 20.16.5** (10b AP1) in Betrieb → Servereinstellungen, Karte „Konten" (E-P5b-14); ihr Verbraucher, die Registrierungsseite (E-P5b-13), ist mit **10b AP3 (Web 20.22.0)** gebaut — `server/registrieren.php`, beschrieben in `docs/Technik.md` 4.99m. Die Öffnung in Wellen (R41) fährt über genau diese drei Werte |
| R10 | Rollen- und Sichtbarkeitsmodell, auch was der Admin nicht kann | gilt, P5 (R38) |
| R11 | Kein Migrationspfad; v1.0 liest die 7.x-edbak; Referenzdatei liegt | gilt; Abnahme in P8 (R71); seit R60 als einmaliges Einspielen über ein Wegwerf-Formular |
| R12 | Weitere Clients: Basisfähigkeit, Vertragsreview in P7 (R71) | gilt; Payloads und Texte erledigt. **Abschnitt 1a in zwei Stufen:** S6 hat ihn auf den heutigen Stand gebracht (Fassung 1.4 — beide Kopplungsformen, was der Server davon speichert, Präfixe der Android-Apps); **S5 schreibt ihn nach E-R49-7 neu**, weil sich der Kopplungsweg selbst umkehrt. Wer 1a liest, liest bis dahin die S6-Fassung |
| R13 | Versionshistorische Kommentare am v1.0-Schnitt ersetzen | gilt, P6 — im Kommentardurchgang des R17-Reviews (R69) |
| R14 | Konzepte mit Fable, mechanische Pflege ohne | gilt |
| R15 | Changelog ab v1.0 als Stichpunkte | gilt, P7 (R71) |
| R16 | Doku-Neufassung zu v1.0 mit Screenshots; Anforderungsgespräch vorher | gilt; Anforderungen R72, Umsetzung P7 |
| R17 | Bug- und Sicherheitsreview mit Fable vor v1.0 | gilt, Eingang von P6; Umfang und Form nach **R69** |
| R18 | Konzept im Projektraum, Umsetzung in Claude Code | gilt |
| R19 | Mengenbremse `ingest.php`: Grundsatzfrage und vier Randbedingungen; Messung liegt | gilt; **Grundsatzfrage entschieden 15.09.2026: ja** (E-P5a-01, Randbedingungen in E-P5a-01/-02); Umsetzung P5a AP7 |
| R20 | Sofortpaket Nr. 22 (Altersfeld maskieren) | erledigt (Web 7.2.1) |
| R21 | Backlog-Zuordnung nach P0 | überholt durch Abschnitt 5 (csrf_check ist Nr. 67) |
| R22 | Papierkorb in beiden Sicherungen | erledigt (S1, Web 8.0.0) |
| R23 | Zwischenpaket S1 | erledigt |
| R24 | Regressionspflicht: beide Kreisläufe je Phase, 0 unerklärt | gilt, dauerhaft |
| R25 | Demo-Konto dauerhaft, einzige E2E-Ausnahme; auf der Kontoseite gesperrt | gilt; P5, P6 (Review prüft die Konstruktion) und P7 (neues Demo-Passwort mit der Umbenennung) führen es mit. **In 10b umgesetzt** (E-P5b-07; 10b AP7, Web 20.18.0): Abschaltbar ist die **Anmeldung**, nicht das Konto — Vorgabe an; bei *aus* antwortet die Seite wie auf eine unbekannte Adresse, auch beim richtigen Passwort (im Browser gemessen 1241 / 1270 / 1263 ms, Spanne 29 ms), Bestand und Selbst-Reset bleiben unberührt. **Einen Demo-Knopf auf der Anmeldeseite gibt es bewusst nicht**, auch bei nadoku nicht; die Zugangsdaten stehen in README und Handbuch |
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
| R37 | Konto-Lebenszyklus und Registrierungs-Sicherheitspaket (elf Punkte) | gilt; **(8) und (9) in P5a** (E-P5a-04 bis -07, -14), **(1)–(7), (10) und (11) in 10b**. Stand dort am 17.09.2026 (Web 20.21.1): **(1)** und **(2)** erledigt (10b AP2, Web 20.17.0, E-P5b-11/-12) · **(7)** erledigt (AP4, Web 20.19.0, E-P5b-05/-15) — die Mechanik, nicht die Texte: die sind bis zur anwaltlichen Prüfung Platzhalter (E-P5b-24) · **(5)** und **(6)** erledigt (AP5, Web 20.20.0, E-P5b-16) · **(10)** erledigt (AP6, Web 20.21.0, E-P5b-04/-18), seine SHA-256-Hälfte (E-P5b-17) dagegen **gegenstandslos** — sie steht seit **Web 13.0.0** (S5, E-S5-42), am Code nachgemessen statt angenommen · **(3)** und **(4)** in AP3 (E-P5b-02/-03/-13, Web 20.22.0), **(11)** in AP9 (E-P5b-09/-19/-20, Web 20.24.0) — **beide gebaut am 17.09.2026**. Der Proof-of-Work aus (4) („notfalls") wird nicht gebaut und geht als Backlog-Punkt mit Auslöser ab |
| R38 | Support-Rolle, Admin-TOTP, Audit, Dashboard im Minimalumfang | gilt, P5 **(alle Punkte) in 10c**, E-P5c-13 bis -18; der **Zweitfaktor ist mit E-P5c-15 konkretisiert**: Pflicht für Admin, BetreiberIn und Support, Angebot für alle übrigen (Nr. 141, F-P5c-1). |
| R39 | Zentrale Stammdaten entfallen; Regionen-Modell verworfen | gilt, P5; Regionen als Nr. 71 festgehalten. **Zweistufig seit 09.09.2026:** In der laufenden Anlage sind alle zentralen Standorte gelöscht (Auskunft des Auftraggebers); **S9 hat die Tür geschlossen** (Web 18.0.0, AP5b: `admin_stammdaten.php` ersatzlos gestrichen, Karte „Vordefinierte Standorte“ und `ub_toggle` mit ihr — kein Schema, keine Migration), **P5 baut zurück** (Nr. 168). Bestandsaufnahme mit 208 Befunden: `docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md` — sie bleibt bis P5 liegen **Rest in 10c AP8** (Nr. 168 und 169, E-P5c-19). |
| R40 | Deploy-Umbau: Staging ab P5, Neuaufsetzen am P8-Schnitt, CI-Prüftor, Torwächter | gilt; (1) läuft, (2) ab P5-Beginn, (3) und (4) in P8 — (4) präzisiert durch R67 |
| R41 | Recht und Betreiberorganisation vor der Öffnung; Öffnung in Wellen | gilt; Prüfung in P8 (R71); MDR-Abgrenzung vor Welle 1 (R65); Betreiberhandbuch generisch mit Notfall-FAQ, Zugänge in der Betriebsakte außerhalb des Repositoriums (R72) |
| R42 | Gerätekennung beim Koppeln | Uhr-Seite erledigt (1.9.0); **Speicherung erledigt (Web 12.9.0, S6)** — drei Spalten statt zwei, begründet im Changelog; Auswertung P5 (Backlog 80) **Auswertung in 10c AP7** (Betriebslage, E-P5c-18). |
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
| R80 | **Ob ein Rettungsmittel Fähigkeiten führen darf, entscheidet sein Typ mit** — nicht mehr die Betriebsart allein (Beschluss 15.09.2026, E-DA-06; schränkt E29 ein). Winde und Bergwacht sind seit Web 20.3.0 auch an **bodengebundenen** Rettungsmitteln vom Typ **Bergwacht** erlaubt — ein Bergwachtnotarzt fährt zum Einsatz und wird von dort geflogen. Die Regel steht als **Spalte** in `VEHICLE_TYPEN` (`faehigkeiten`: `'luft'` \| `'immer'`) und wird an einer Stelle beantwortet, `veh_caps_erlaubt()`; Prüfschicht, Rückspielweg der Sicherung, Markup und Dialogskript fragen sie. Für den Typ **Veranstaltung** bleibt es bei „keine" — das folgt schon aus der festen Betriebsart. **Nicht mitgeändert:** `pruef_tagesrettungsmittel()` kennt weiterhin keine Fähigkeiten (F19, E-S9-10), und die **Zeitraumübersicht** zählt Winde und Bergwacht weiter nur luftgebunden — das wäre eine Gestaltungsentscheidung mit Mockup und steht als Backlog Nr. 198 | gilt; umgesetzt in 9d AP0 |
| R81 | **Plattformprofil hosterneutral, in zwei Stufen** (Beschluss 15.09.2026, E-PP-01 bis E-PP-04; beantwortet die Hosting-Zuarbeit aus R36). Der Dienstbetrieb bleibt beim jetzigen Hoster (Plesk, geteilter Webspace), die Anwendung wird aber **nicht auf ihn zugeschnitten**: Ein Hosterwechsel ändert `config.php`, keine Codezeile. Die Anforderungen an die Plattform stehen als Profil mit genau zwei Stufen — **Muss** (Untergrenze Z2/Z3; `install.php` prüft es vor der Einrichtung, die Statusseite im Betrieb, Ausfall ist rot) und **Empfohlen** (wird genutzt, wenn vorhanden; Abweichung ist ein Hinweis, keine Ampelfarbe). Jede P5-Funktion nennt ihre Stufe und ihren Rückfall auf Muss. Für Versionen gilt die dauerhafte **Regel** „vom Hersteller mit Sicherheitskorrekturen versorgt"; die Zahl im Code ist ein datierter Stand an einer Stelle. **Nicht vorausgesetzt:** DDoS-Schutz des Hosters und Verschlüsselung at rest — beides Empfehlung an den Betreiber, weil auch nach S11 der Betrieb (wer, wann, womit) im Klartext liegt, nur der Einsatz nicht. Volltext: `docs/konzepte/Vorbereitung-P5-Plattformprofil.md` | gilt |
| R82 | **Schritt 10 (P5) wird in drei Teilkonzepten umgesetzt** (Beschluss 15.09.2026): **10a Kette und Fundament**, **10b Konto und Registrierung**, **10c Rollen, Sicherheit und Betriebslage** — in dieser Reihenfolge, jedes nach K1 mit eigenem Paketschnitt, eigener Freigabe und eigenem Prüfdokument; 10b setzt 10a voraus, 10c setzt 10b voraus. Die Ortsvorgabe aus E-S8-12 und der Inhalt nach R9, R10, R31, R33, R36 bis R41 bleiben; nur der Schnitt ist neu. Verworfen: ein Konzept mit Paketschnitt wie S9/S10 — die Freigabe hätte auf alles gewartet | gilt |
| R83 | **Zentralisiert wird beim zweiten echten Verbraucher, nicht vorher.** Ein Helfer mit einem Verbraucher ist keine Zentralisierung, sondern eine Schnittstelle, die gegen einen Fall entworfen wird. Was vorab zählt, ist der Ort: **Bibliotheksdatei statt Seite**, damit der zweite Verbraucher ohne Umbau zugreifen kann. (Beschluss 16.09.2026, Anlass Schritt 15.) Begründung: `edbak_groesse_text()` steckt in `adminbackup_lib.php` und wird von `admin_sicherungsziele.php` und `betrieb_updates.php` nur dafür geladen — der zweite Verbraucher kam, und die Funktion lag auf der falschen Seite. Gilt ab sofort für jede laufende Umsetzung (P5a AP8–AP10: Bytes und relative Zeit heben, nicht kopieren) | gilt |

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

### Backlog-Runde 2 · Web 19.2.0 und 19.3.0 · 12.09.2026 (Schritt 9)

**Nachgetragen mit Fassung 47.** Die Runde war gebaut, geprüft und gemergt,
hatte aber keine Erledigt-Zeile — Abschnitt 9 verlangt sie, und ohne sie
fehlten die beiden Merges PR #41 und #42 im ganzen Dokument (gemessen am
13.09.2026: null Fundstellen). Einzelpunkte aus Abschnitt 5, kein Konzept
nach K1, je Punkt ein Commit; Zweig `claude/backlog-runde-2`.

**Fünf Punkte erledigt** (118 Jobs aus der Oberfläche anhalten, 119 „Import /
Export" nennt die anderen Wege, 120 Testmail auf Betrieb → Status, 125
`.zweispalter` gestrichen, 126 Rückweg aus der Wartungsseite), **einer
vermessen statt gebaut** (57 — fünf Driften statt einer, zwei Erzeuger, vier
Spaltenlisten, ein dreifaches Sortierblatt; braucht Mockup und Freigabe),
**einer neu** (174). Dazu **Nr. 155** mit Web 19.2.0: die alte Rundenzahl ist
weg.

**Gefunden hat Nr. 174 ein Prüfmittel, nicht ein Mensch:** Beim Neubau des
Referenzbestands in 19.2.0 hatten zwei der sechs Rettungsmittel ihren
*leeren* Standort verloren — die einzige Abdeckung eines Falls, den S9/AP4
ausdrücklich erlaubt. Die Klickprobe meldete 39 von 40 Wegen. Die
Demo-Fixture ist repariert (2 von 6 ohne Standort, Spurpunkte unverändert
55 861), die Referenzdatei der Kreisläufe nicht — das ist Nr. 174 und liegt
in Backlog-Runde 3. Dabei fiel auf, dass die Hintergrundjobs während der
Arbeit **8285 Spurpunkte** ausgedünnt hatten; wer eine Fixture erzeugt, hält
vorher die Jobs an, und der Dateikopf sagt es jetzt.

**Zwei Zusagen geändert, beide ausdrücklich freigegeben:** Betrieb → Status
hatte „genau eine Ausnahme" von „rein lesend" und hat jetzt zwei (Testmail);
„Import / Export" beansprucht nicht mehr, alle Datenwege zu führen, sondern
nennt die sechs, die anderswo liegen. Dazu **ein neues Zeichen im
Symbolvorrat** (`mail.svg`, Tabler „mail", 52 → 53) — eine Kopfaktion ohne
Symbol wäre die erste von zwölf gewesen und damit eine neue Darstellung.

*Prüfzahlen:* Stilvergleich **68 224** Elementmessungen ohne Abweichung,
Kaskade 743 → 742 Regeln mit **0** geänderten Endwerten, Klickprobe **40/40**,
Wartungsprobe **55/0**, Linkprobe **117/0**, Wortliste **0/0**,
Vollständigkeit **330 → 334** (vier Pfeile in Fließtext), Kontraste **22/0**,
Bilderlauf **96** Bilder über sechs Seiten in beiden Bedienhöhen ohne
Überlauf. *Keine Migration.* *Prüfdokument:*
`docs/konzepte/Pruefdokument-Backlog-Runde-2.md`, sieben Punkte für die
Auftraggeberin (Abschnitt 6). *Merges:* PR #41 (`6f316ee`, die Stufen) und
PR #42 (`dabd7a3`, zwei Doku-Nachträge ohne Stufe).

### Mockup-Runde 9c · Web 19.4.0 bis 19.6.0 · 13.–14.09.2026 (Schritt 9c)

**Vier Gestaltungsaufgaben, eine Freigaberunde statt vier** (Entscheidung 15
vom 12.09.2026). Konzept `Konzept-Mockup-Runde.md` mit fünf Mockups, F-MR-1
bis F-MR-14 beantwortet; Zweig `claude/jolly-planck-vexxpd`, je Paket ein
Commit und ein Push. **Sieben Versionsstufen**, weil zwei Befunde unterwegs
entstanden und gleich behoben wurden.

**Die vier Punkte der Runde:** **Nr. 41** (Web 19.4.0) — die Kopfzeile der
Tagesgruppe in der Importvorschau hat eine Regel, `imp-warn` ist gestrichen,
`pruefen.py` meldet **0** `[offen]` statt 2. **Nr. 42** (19.4.2) — beide
Chips und der Satz der Meldung tragen Symbole statt Unicode-Zeichen; das
Treffziel des Entfernen-Knopfes wächst von 17 × 15 auf **28 × 28 px**.
**Nr. 45** (19.5.0) — die dritte Kartengröße: ein Zustand, zwei Wirkungen je
Breite (bis 1599 px höher, darüber breit), der Zustand wird je Gerät gemerkt.
**Nr. 124** (19.6.0) — das Aktionsblatt fährt auf, und der offene Öffner ist
orange hinterlegt; `--dauer` steht seither auf **240 ms** für die ganze
Anwendung.

**Drei Punkte sind unterwegs entstanden und miterledigt worden.** **Nr. 182**
(19.4.1): Die Kopfzeile aus AP1 stand außerhalb des Sichtfensters — die Zelle
ist so breit wie die Tabelle (gemessen 2653 px gegen 342 px am Handy), nicht
wie das Bild. Gelöst mit einer **Container-Abfrage ohne JavaScript**; Weg C
(eine Tabelle je Gruppe) war zuerst gewählt und ist nach einer Kartierung mit
**58 Befunden, 22 davon „bricht"** verworfen worden, weil er die Spaltenflucht
gebrochen hätte, die in seiner eigenen Abnahmezeile steht. **Nr. 185**
(19.5.1): `import.php` lief bei 360 px **nur in WebKit** um 6 px über, ohne
dass ein Element hinausragte — WebKit rechnet den längsten Eintrag eines
`<select>` in den Überlauf des Kastens. **Nr. 186**: Die Klickprobe maß
Drehungen mit `getScreenCTM()`, das in WebKit die CSS-Transformation eines
HTML-Vorfahren nicht enthält; ein Fehler des Prüfmittels, der wie einer der
Anwendung aussah.

**Nr. 183 ist damit ganz erledigt** (AP3b, 19.5.1): Bilderlauf, Klickprobe und
Stilvergleich fahren seither `--motor chromium|firefox|webkit`; Motorwahl und
Firefox-Voreinstellung liegen an **einer** Stelle (`tools/motor.mjs`). Wie oft
welches Mittel dreifach fährt, steht in `docs/Technik.md` und ist gemessen
begründet: Stilvergleich immer (14–18 s je Motor), Bilderlauf gestaffelt
(Chromium voll, die anderen `--nur` plus `--risiko`), Klickprobe nach Bedarf
und nur mit frisch eingespieltem Bestand dazwischen. **Beide Befunde oben
stammen aus dem allerersten dreifachen Lauf** — der Punkt hat sich am Tag
seiner Fertigstellung bezahlt gemacht.

**Prüfzahlen:** Kreisläufe (R24) **csv 9120 Einzelvergleiche, 0 unerklärt**
(1021 erwartet) und **edbak 287 687, 0 unerklärt** (16 erwartet);
Vollständigkeit **323 Befunde**, `[offen]` **0**, Hexfarben außerhalb `:root`
**0**, Pixelmaße **0**, Symboldateien **55**; Stilvergleich je Arbeitspaket in
**drei Motoren** mit identischen Zahlen und ohne unerklärte Abweichung;
Klickprobe **43 von 43** (drei Wege neu); Bilderlauf Chromium voll **360
Bilder, 0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen**, Firefox,
WebKit und der Fingerlauf je 80 Bilder **0/0/0**; Wortliste **0/0**, Linkprobe
**117/0**, Kontraste **22 Paare, 0 verfehlt**, Wartungsprobe **55
Erwartungen, 0 nicht erfüllt**. Selbstprüfzahl **51 = 51**.

*Reste:* die Prüfliste des Auftraggebers im Prüfdokument — was nur am Gerät zu
beurteilen ist (fühlen sich 240 ms richtig an?) und was nur echtes Safari
zeigt; dazu **Nr. 184** (der Kommentar-Abtaster der Prüfmittel), neu
aufgenommen und einer Backlog-Runde zugeordnet.

### Backlog-Runde 3 · Web 19.3.1 · 13.09.2026 (Schritt 9)

**Die erste Backlog-Runde mit Konzept** (`Konzept-Backlog-Runde-3.md`, zehn
Arbeitspakete in drei Blöcken) — neun Punkte in einer Stufe sind zu viele, um
sie in einer Auftragszeile zu ordnen; K1 verlangt für diese Größe ein Konzept.
Zweig `claude/backlog-runde-3-umsetzung-woqxjm`, je Paket ein Commit und ein
Push. **Eine Korrekturstufe für die ganze Runde:** Von den neun Punkten fassen
genau **zwei** `server/` an, beide Fehlerbehebung — sonst liegt alles in
`tools/` und `docs/`. **Keine Migration**, Uhr und Android unberührt
(`git diff --stat origin/main -- watch android` leer).

**Sieben Punkte erledigt** (91 die Lehre zu `WatchUi.Confirmation`, 94
„pixelgleich" statt „bitgleich", 117 was die Kennzahlen der NutzerInnen-Liste
**nicht** messen, 47 kein natives `confirm()`/`alert()`/`prompt()`, 58 jede
Seite mit eigener Hülle hat ihr Gerüst, 173 fünf Ausnahmeregeln ohne
Gegenstand, 174 das Rettungsmittel ohne Standort im Referenzbestand),
**zwei mit Vermerk offen**: **Nr. 67** — der Unterpunkt ist erledigt (die
CSRF-Prüfung in `api/kdf_upgrade.php` steht jetzt **vor** dem Demo-Ausstieg),
der Hauptpunkt gehört nach P5; **Nr. 41** — drei der fünf Klassen sind aus dem
Markup gestrichen, die zwei übrigen brauchen Regeln und damit 9c.

**Zwei neue Prüfungen, und die erste fand sofort einen echten Fehler.** Die
Gruppe „5 Zusagen" in `tools/vollstaendigkeit/pruefen.py` zählt zwei Regeln
nach, die vorher nur im Kopf standen; die Ausnahmen samt Grund stehen in
`tools/vollstaendigkeit/zusagen.md` (2 + 7). Beim ersten Lauf meldete sie,
dass `apk.php` Gerüst und `ui_seite_ende()` ruft, aber nie `ui_seite_start()`
— **die 404-Seite ging ohne Doctype, Titel und Stylesheet hinaus**, Quirks-
Modus, Times New Roman. Belegt am Prüfstand, behoben mit einer Zeile und
**nicht** auf die Ausnahmeliste gesetzt. Das ist der Sinn des Haltepunkts
H-BR3-1: Ein Fund gehört behoben, nicht erklärt.

**Der zweite Haltepunkt hat gehalten — und meine erste Diagnose war falsch.**
H-BR3-2: Die Vormessung zu Nr. 174 ergab 0 statt 2 Rettungsmittel ohne
Standort; ich habe angehalten, gemeldet und behauptet, die Anwendung sei seit
Web 17.0.0 kaputt. Beim Einbauen der freigegebenen Wiederherstellung zeigte
ein Kommentar in `stammdaten_ui.php`, dass der Haken „ohne Standort" in Web
16.3.0 **absichtlich** durch den ersten Eintrag der Auswahlliste ersetzt wurde.
Die Serveränderung ist zurückgenommen; der Fehler saß allein in
`tools/referenzdatensatz/einspielen/einspielen.py`, das ein seit 16.3.0 totes
Feld neben einer echten Kennung schickte. **Die Lehre steht im Konzept:** Ich
habe aus der Historie geschlossen, statt zuerst zu prüfen, wie die Sache heute
gebaut ist.

**Ein Beinahe-Verlust im Backlog (F-BR3-01)** und die Dauerprüfung, die daraus
folgt: Mein erstes Austragungsskript suchte das Ende eines Eintrags an der
nächsten Nummer — steht die schon unter *Erledigt*, reicht der Löschbereich
rund 300 Zeilen zu tief. Aufgefallen ist es nur, weil danach ein Lookup ins
Leere lief. Seither wird nach jedem Austragen die **Nummernmenge gegen
`dabd7a3`** verglichen (0 verloren, 0 doppelt); die Selbstprüfzahl allein
hätte es nicht gefunden, denn sie zählt die offenen Punkte.

**Der Prüfstand ist in dieser Runde neu entstanden** und bleibt: Der
Wegwerf-Container brachte keinen Datenbankserver mit, und das kostete AP4 eine
Stunde. `.claude/hooks/session-start.sh` beschafft ihn jetzt beim Start
(MariaDB, ImageMagick, `librsvg`, `jsonschema`) — was er mitbringt und was
nicht, steht in `docs/Technik.md` 2a.

*Prüfzahlen:* Wortliste **0/0**, Vollständigkeit **330** Befunde (`[offen]`
**2**, Gruppe 5 **0/2/0** und **0/7/0**), Linkprobe **117/0**, Wartungsprobe
**55/0**, Spurprobe **45/0**, Kontraste **22/0**, Klickprobe **40/40**,
Kreisläufe **287 687/0** und **9 120/0** gegen die **neue** Referenz,
Bilderlauf **360 Bilder / 45 Kontaktbögen, 0 Überlauf / 0 Konsolenfehler / 0
Knopfhöhen** (Gegenprobe: 356 verschiedene Bilder, die vier Doppel sind die
Schublade ab 1024 px), `php -l` **100/0**, Selbstprüfzahl **54 = 54**.
*Keine Migration.* *Prüfdokument:*
`docs/konzepte/Pruefdokument-Backlog-Runde-3.md`, fünf Punkte für die
Auftraggeberin.

**Zwei neue Nummern aus dem Abschlusspaket — und eine davon ist auf Anweisung
sofort behoben worden.** **Nr. 176:** Der Rauschfilter des Bilderlaufs
(`istRauschen()`) prüfte drei Fehlercodes gegen den Meldungstext, ohne die
Fundstelle anzusehen; ein Abruf auf dem **eigenen** Server fiel damit unter
das Kartenrauschen. Der Auftraggeber hat die Behebung angewiesen, weil der
Punkt genau das Messmittel betrifft, mit dem alle Bildzahlen dieser Stufe
belegt sind — **Nachtrag AP11**, drei Klassen statt einer Musterzeile, Herkunft
über `URL.origin`. Belegt in drei Richtungen: neue **Selbstprobe**
`--selbstprobe` **10 von 10**, dieselben zehn Fälle durch die alte Funktion
(wörtlich aus `git show`) **6 von 10**, und am laufenden Browser mit
angehaltenem PHP-Server zwei Konsolenfehler auf der eigenen Basis, davon
verwarf der alte Filter **einen** und der neue **keinen**. Der Abschlusslauf
meldet mit der neuen Regel unverändert **0 Konsolenfehler** — die Zahl war
richtig, sie war nur nicht belegt. **Nr. 177** (sechs doppelte Fassungsnummern
in Abschnitt 10 dieses Dokuments) ist **zurückgestellt**: rein
dokumentarisch, keine Wirkung auf Code, Daten oder Oberfläche. Nachgemessen
sind trotzdem zwei Zitate betroffener Nummern in anderen Dokumenten und ein
älterer Fund vom 09.09.2026 in der R39-Bestandsaufnahme; die Zeile in
Abschnitt 5 sagt es.

**Der Nachtrag ist gegengeprüft worden — und das hat fünf eigene Fehler
gefunden, keinen davon durch ein Prüfmittel.** Der schwerste: Die neue
Selbstprobe war **blind**. Sie meldete zehn von zehn auch dann, wenn man die
Klasse löschte, um die der ganze Nachtrag geht — alle verwerfenden Fälle trugen
einen Kachelgastgeber in der URL, also fing sie die erste Klasse, und fiel die
weg, die dritte. Jetzt trägt jede Klasse einen Fall (fünfzehn statt zehn), und
eine **Mutationsprobe** hält es fest: sechs Läufe, in jedem eine Klasse
herausgenommen, jedes Mal **14 von 15**. Dazu drei Berichtigungen am Code und
zwei an Zahlen; alles in `Pruefdokument-Backlog-Runde-3.md` Abschnitt 2 und 6.
**Drei neue Nummern sind dabei entstanden** — **178**, **179** und **180** —,
und die dritte war die unangenehmste: Die Kopplungsprobe verlangte 44 px für
jeden Knopf, seit Web 15.5.0 gelten zwei Sollwerte, und sie war deshalb **seit
dem 06.09.2026 rot**. Gefahren wurde sie erst am 13.09.2026, weil ein anderer
Punkt dazu führte. Ein Prüfmittel, das niemand fährt, ist kein Prüfmittel.

**Alle drei sind am selben Tag abgearbeitet worden (AP12), auf Anweisung.**
Die Kopplungsprobe leitet ihren Sollwert jetzt ab (25/0 in beiden
Bedienhöhen); ihre Rauschregel ist in **drei Kanäle** getrennt und mit
Selbstprobe, Mutationsprobe und einem eingeschleusten 500er belegt; und die
Zusage „keine fremde Quelle zur Laufzeit" hat mit der Prüfung `fremde Quelle`
in Gruppe 5 endlich ein Messmittel — **15 Ausnahmen mit Grund**, jede mit ihrer
Art. **Was daraus offen bleibt, ist eine Entscheidung und keine Arbeit:**
**Nr. 181**, die Content-Security-Policy. Sie ist die Laufzeitseite derselben
Zusage, braucht Ausnahmen für vier Kachelserver und den Adressdienst — dessen
Anschrift eine Einstellung ist, die Richtlinie muss also zur Laufzeit gebaut
werden —, und wer sie zu eng setzt, macht die Karten grau. Ob das nach **S10**
(Schritt 9b) oder in das Bedrohungsmodell (P6, R69) gehört, steht in
Abschnitt 5 als Frage.

*Merge:* **am 13.09.2026 auf `main`** (PR #43, `8f1712c`) — nachgemessen
mit Fassung 68; die Zeile sagte seit Fassung 48 „steht an", obwohl der Kopf den
Merge seit Fassung 60 kannte.


### S10 — Sicherheit · Web 19.7.0 bis 20.2.1 · 14.09.2026 (Schritt 9b, R78)

**Der Datenbankabzug allein reicht nicht mehr.** Konzept
`Konzept-S10-Sicherheit.md` (Fable, 13.09.2026; nach der Freigabe gelöscht,
in der Historie unter `a00f6b5`), Umsetzung Opus in **sechs
Arbeitspaketen** auf `claude/konzept-umsetzen-uszccc`, je Paket ein Commit und
ein Push. **Fünf Versionsstufen**, eine davon Haupt. **Keine Schemaänderung,
keine Migration** — `update.php` muss nach dem Merge *nicht* laufen.

**Der Server-Anteil** (`kdf_anteil` in `config.php`, 19.7.0/20.0.0). Aus ihm
wird je Konto per HMAC über die Kontonummer ein Wert abgeleitet, und der geht
per **HKDF-SHA256** zusammen mit der PBKDF2-Hälfte in den Datenschlüssel ein —
die Hälfte ist seither nicht mehr selbst der Schlüssel. Der Server gewinnt
dabei nichts: Er kennt den Anteil, nicht die Hälfte aus dem Passwort. Was sich
ändert, ist die Rechnung des Angreifers. **`pat_wrap_rc` hängt nicht daran**,
und deshalb ist der Verlust des Anteils kein Datenverlust, sondern ein
Passwort-Reset für alle. Die Umstellung läuft **still beim nächsten Anmelden**;
Hüllen tragen danach `edka1:<kennung>:` statt `edk1:`.

**Der Betrieb** (20.1.0). Karte „Schlüssel des Servers" unter Betrieb →
Servereinstellungen: Anlegen, **Nachtragen vom Blatt** (der Server rechnet die
Kennung des eingegebenen Werts und schreibt nur bei Übereinstimmung),
Rotation, alten Anteil entfernen, Neuanfang. Dazu das **Schlüsselblatt** zum
Ausdrucken — die einzige Seite, die die Geheimnisse zeigt, ohne Gerüst, mit
`no-store` — und eine Statuszeile, die die Umstellung je Konto mitzählt.
**Das erste `@media print` des Projekts**; nach `CLAUDE.md` 5 freigabepflichtig
und am 14.09.2026 nach Vorlage der Bilder abgenommen.

**Die Adminpakete sind versiegelt** (20.2.0, Nr. 139). Jedes Teil gzip-gepackt
und mit dem Serverschlüssel versiegelt (`edsk1:`), der Zweck bindet **Konto,
Paket und Teil** — ein umbenanntes Paket und ein untergeschobenes Teil werden
abgewiesen. **`ftp` ist fort**: nicht mehr wählbar, nicht mehr speicherbar,
nicht mehr beschickt; ein bestehendes Ziel wird **übergangen**, nicht gelöscht.
Das ENUM behält den Wert (Rückbau mit Nr. 168/46).

**Prüfzahlen.** Umstellungslauf **16 von 16** in drei Engines, je zweimal,
darin **80 von 80** Blöcken nach der Umstellung lesbar und **0** Aufrufe von
`kdf_upgrade.php` beim zweiten Anmelden · Betriebslauf **50 von 50** in drei
Engines · Anteilprobe **69/69**, Endpunktprobe **34/34**, Wartungsprobe
**57/57**, Wiederherstellungsprobe **106/106**, Komplettprobe **72/72**,
Versandprobe **116/116**, Freigabeprobe **16/16**, Jobprobe **27/27**,
Riegelprobe **10/10**, Sitzungsprobe **2/2**, Demo-Probe **24/0** ·
Klickprobe **43/43** · Kreisläufe **9120/0** und **287 687/0** · Bilderlauf
**0/0/0** · Kontraste **22/0** · Wortliste **0/0/0** · Linkprobe **117/0** ·
Vollständigkeit **340**, Hexfarben **0** · Design-Tabellen **237/0**.
**Die zwei gemessenen Zahlen, die das Konzept verlangt:** HKDF im Browser
**0,023–0,154 ms** je Ableitung, und die Paketgröße der Fassung 3 in **drei**
Zahlen — 33 281 Byte offen, 201 390 (+505 %) versiegelt ohne Vorstufe,
**45 290 (+36 %)** mit gzip davor; die verbleibenden 36 % sind der
base64-Rahmen von `edsk1:`, nicht die Packung.

**Drei Funde in der Anwendung**, alle im Gegenlesen gefunden und in AP2
behoben — keiner wäre von einem Prüfmittel gefunden worden. Der teuerste: Die
Prüfung, ob eine Hülle zum aktuellen Anteil gehört, stand nur an **einem** von
**vier** Schreibwegen; sie liegt jetzt als `huelle_pw_pruefen()` an einer
Stelle, wie es `CLAUDE.md` 4 für die gemeinsame Prüfschicht verlangt.

**Und achtundzwanzig am Prüfstand.** Die Fundtabelle des Konzepts zählt
**47** Funde; **28** davon nennen als Ort einen `tools/`-Pfad (Abschnitt 6,
nachgezählt am Stand unter `a00f6b5`). AP5 war das Paket, in dem die
Prüfmittel nachziehen, und es hat dabei **dreizehn** Dinge gefunden — **kein
einziges davon ein Fehler der Anwendung**. Die drei, die über den Tag hinaus
gelten:
Die **Wartungsprobe stand seit AP3 auf „1 nicht erfüllt"**, weil eine
Ausnahmeliste wuchs und die Erwartung nicht mitgezogen wurde — bemerkt hat es
niemand, weil sie nicht zum Standardsatz nach einer Oberflächenänderung
gehört. **Sieben Zahlen in Anleitungen waren Abschriften**, darunter zweimal
*verschiedene* Zahlen für dieselbe Probe in einem Dokument; dass sie
nebeneinander stehen konnten, ist der Beleg, dass beide abgeschrieben waren.
Und **zwei Prüfmittel lasen `server/config.php`** — die Datei mit beiden
Geheimnissen; ausgetreten war nichts, aber die Zahl hing davon ab, wo das
Werkzeug lief.

**Eine Frage ist unterwegs gestellt und entschieden worden** (F-S10-6,
14.09.2026): Der Riegel gegen eine Demo-Fixture mit Server-Anteil gehört an
**beide** Enden — in den Erzeuger *und* in `demo_fixture_laden()`. Weg (a),
gebaut als **20.2.1**. Dieselbe Paarung, die Backlog Nr. 155 für die
Rundenzahl aufgestellt hat, mit derselben Begründung.

*Reste:* die fünf Betriebsposten in Abschnitt 6 (Anteil anlegen, Blatt
drucken, einmal anmelden, `ftp`-Ziel umstellen, Wiederanlaufpaket ergänzen)
und die Prüfliste des Prüfdokuments. **Nr. 139 ist erledigt**; Nr. 140 bleibt
offen und hängt allein an R40 (2) — das Deploy-Tor war nie S10-Arbeit, R78 (7)
sagt es ausdrücklich.

*Merge:* **am 14.09.2026 auf `main`** (PR #45, `965ec11`) — nachgemessen
mit Fassung 68; die Zeile sagte seit Fassung 61 „steht an", obwohl Fassung 64
den Merge im Kopf und in der Fahrplanzeile eintrug — diese Zeile ließ sie aus.

### Demo-Ausbau 9d · Web 20.3.0 · 14.–15.09.2026 (Schritt 9d)

**Der Referenzbestand zeigt die drei Rettungsmittel-Typen aus S9 jetzt im
Betrieb.** Bis dahin kannte er sie nur als Stammdatenzeilen — kein Diensttag,
kein Einsatz; wer das Demo-Konto öffnete, sah davon nichts, und das
Regressionsnetz (R24) prüfte davon nichts. Konzept `Konzept-Demo-Ausbau.md`
(Fable, 15.09.2026, E-DA-01 bis E-DA-32); Zweig
`claude/umsetzung-ohne-pausen-2vfppr`, fünf Arbeitspakete, je ein Commit und
ein Push. **Eine Versionsstufe**, weil nur AP0 `server/` anfasst.

**AP0 — die eine Regeländerung** (Web 20.3.0, **R80**): Winde und Bergwacht
sind seit S9 an Typ **Bergwacht** auch **bodengebunden** erlaubt. Ein
Bergwachtnotarzt fährt zum Einsatz und wird von dort geflogen; die Kopplung
von Winde und Luft war eine Regel über Hubschrauber, nicht über Bergwacht.
Die Regel ist eine **Spalte** in `VEHICLE_TYPEN` geworden und wird an einer
Stelle beantwortet (`veh_caps_erlaubt()`) — sie wird an **vier** Stellen
gebraucht, und genau daran war die alte Fassung gescheitert: Das Skript des
Stammdatendialogs führte eine dritte, **engere** Fassung (F-DA-1), und die
Karte „Bergwacht-Bereitschaften" hing am selben zu engen Merkmal (F-DA-2 —
sie hätte AP1 stumm beschädigt). Mitgenommen: **Backlog Nr. 189**.

**AP1 bis AP3 — der Bestand.** Fünf neue Diensttage D17–D21, 16 von Hand
geschriebene Einsätze und zwei weitere Schnitte: Bergwacht am Boden mit Winde
und **Fußweg** (der Wagen hält am Zustieg, die Besatzung geht), ein
Verlegungsfahrzeug mit Sekundärtransporten, und zwei
**Veranstaltungsdienste ohne Standort** am Abend eines Tages, an dem tagsüber
schon ein anderer Dienst lief — einer davon vollständig am Formular
nachgetragen, also mit Koordinaten und ohne Spur (gestrichelte Luftlinien).
Dazu ein dritter Standort, vier neue Rettungsmittel und Koordinaten für
Talwang. **16 → 21 Diensttage, 88 → 106 Einsätze, 100 → 119 Ruhesegmente,
55 861 → 63 752 Spurpunkte.**

*Prüfzahlen:* `quelldaten/pruefen.py` **21 Dienste / 103 Einsätze / 7042
Einzelprüfungen / 97 Matrixzeilen, 0 offen / 0 Sachfehler** ·
`generator/pruefen.py` **321 799 Einzelprüfungen, 0 Befunde** · Einspiellauf
**612 Anfragen, 0 Fehlversuche** · edbak-Kreislauf **328 771 Einzelvergleiche,
0 unerklärt, 0 ungenutzt**, Selbstproben **15/15** · CSV-Kreislauf **10 922 /
0 / 0**, Selbstproben **10/10** · Klickprobe **47/47** · Bilderlauf
**Zeiger 49 Seiten / 392 Bilder 0/0/0**, **Finger 16 Seiten /
128 Bilder 0/0/0** · Papierkorb-Mischfall **15/0** · Linkprobe **117/0** ·
Wartungsprobe **57/0** · Wortliste **0/0/0** · Vollständigkeit
**340, unverändert** · Demo-Reset **5859 ms → 6610 ms**. *Keine Migration.*

**Vier Funde unterwegs, alle behoben.** Der Riegel auf der Schlüsselhülle
(S10) hielt den Aufbau an, weil die dokumentierte Reihenfolge gar nicht
ausführbar war — der Adminbereich kann ein Demo-Konto erst **mit** Fixture
anlegen, und die entsteht am Ende; `einspielen/demo_kennzeichnen.php` löst
das von der anderen Seite (F-DA-7). Die **Klickprobe** hat D21 einen Standort
verpasst, weil sie ihn aus einem Auswahlfeld las, das für einen Tag ohne
Standort seine erste Option liefert — seit S9 vorhanden, erst mit diesem
Bestand auslösbar (F-DA-8). Und eine **Wildcard-Ausnahme** verschluckte eine
Selbstprobe des CSV-Vergleichs; der Fund ist älter als das Paket und gegen
die alte Referenz nachgewiesen (F-DA-9). Und beim Zusammenführen mit `main`
hat **`git merge` `docs/Backlog.md` ohne Konflikt falsch zusammengesetzt**:
mains sieben neue Punkte landeten hinter der frisch eingefügten Überschrift
`## Erledigt`, zwei Nummern waren doppelt vergeben, eine Erledigt-Zeile
verwaist — kein Marker, kein Hinweis (F-DA-10). Von Hand blockweise neu
gebaut und mit zwei Zählungen gegengeprüft.

**Der zusammengeführte Stand ist danach unabhängig gegengelesen worden** —
fünf Blickwinkel, jeder Befund adversarisch widerlegt oder bestätigt:
**21 gemeldet, 15 bestätigt, 6 widerlegt.** Elf gehörten diesem Paket und
sind behoben (darunter: `version.php` kündigte zwei nebenbei geschlossene
Lücken an und beschrieb nur eine; R80 überschrieb sich mit dem Gegenteil
seiner eigenen Regel; „E-DA-01 bis E-DA-30", es sind 32). **Drei sind älter
als der Zweig** und stehen jetzt im Backlog statt in diesem Paket:
**Nr. 196** ist um die Messung am Rahmenplan gewachsen (Abschnitt 10 rendert
**8 von 58** Fassungszeilen als Tabelle, die Fahrplanzeile 9c verliert ihren
Statustext an drei ungeschützte Pipes), und **Nr. 199** ist neu — die
Backlog-Nummer **5** fehlt, obwohl der Changelog sie unter *Erledigt*
verortet.

*Reste:* **Backlog Nr. 198** (die Zeitraumübersicht zählt Winde und Bergwacht
weiter nur luftgebunden — zwei Kacheln mehr wären zehn in vier Spalten, also
eine Gestaltungsentscheidung mit Mockup) und **Nr. 76**, die jetzt ihre
Messung hat, aber noch keine Entscheidung. Die Prüfliste steht in
`docs/konzepte/Pruefdokument-Demo-Ausbau.md`, zehn Punkte.

**Mitgenommen beim Zusammenführen mit `main`:** die zweite Hälfte von
**Nr. 189**, die der R42-Nachlauf am 14.09.2026 nachgetragen hatte — der
Kommentar an `geraet_modell` nannte 156 Zeichen statt 153. Die drei lebenden
Stellen (`server/schema.sql`, Rahmenplan Abschnitt 3, Konzept R64) nennen
jetzt die nachgemessene **153**; die Protokollzeilen bleiben. Nr. 189 ist
damit ganz erledigt statt halb.

*Merge:* **am 15.09.2026 auf `main`** (PR #48, `7f334cb`) — nachgemessen
mit Fassung 68; Fassung 67 führte ihn noch als ausstehend.

### P5b — Konto und Registrierung · Web 20.15.3 bis 20.24.0 · 16.–17.09.2026 (Schritt 10b, R82)

**Ein Konto entsteht jetzt von selbst, lebt einen Zyklus und geht von selbst
wieder.** Konzept `Konzept-P5b-Konto-und-Registrierung.md` (Fable,
16.09.2026, E-P5b-01 bis -25, AP1–AP10; **liegt noch** — es wird erst
nach der Freigabe des Abschlusses gelöscht, K9),
Umsetzung Opus in **zehn Arbeitspaketen** auf
`claude/magical-dirac-we2y1z`. **Dreizehn Versionsstufen**, keine davon
Haupt. **Sechs Schemaerweiterungen** — `update.php` muss nach dem Merge
**laufen**.

**Gemergt: PR #57, `eec41e1`, 18.09.2026** (nachgetragen mit Fassung 85 —
der Kopf dieses Dokuments stand bis Fassung 80 zwei Tage lang auf „liegt zum
Merge bereit"). Danach auf `main`: **PR #58** (`7675f9b`, Web 20.24.1 —
Anmeldung im Migrationsfenster), **PR #59** (`7150793`, Web 20.24.2 — zwei
Rechtstext-Überläufe und `actions/checkout@v7`, Nr. 235), **PR #60**
(`862ca7f`, Web **20.25.0** — Nr. 238 und 239: Schemaprobe gegen MySQL 8.4.0
und MariaDB 10.6, `missions.manual` → `uhr_gesperrt`, **mit Migration**).
**`update.php` ist damit für sieben Migrationen fällig**, nicht für sechs.

**Der Lebenszyklus.** `protokoll_lib.php` als Schreibweg des
Betriebsprotokolls mit sechs Reitern und zwei Fristen (AP1, 20.16.5; V1
gilt — Betriebsereignisse, keine Zugriffe) · `konto_lib.php` als einzige
Stelle für Statusübergänge, Übergangstabelle statt Verzweigungen, vier
Tokenanlagen auf eine zusammengeführt (AP2, 20.17.0; Nr. 202 Paket 1) ·
Selbstregistrierung in drei Betriebsarten mit Honeypot, Mindestausfülldauer
und Wegwerfliste (AP3, 20.22.0) · Einwilligungen mit Fassungskennung und
Tor, das drei Wege offen lässt (AP4, 20.19.0) · Selbstlöschung mit 30 Tagen
Karenz und Adresswechsel mit Bestätigung (AP5, 20.20.0) · Mengengrenze je
Konto und Aufbewahrung je Konto (AP6, 20.21.0; Nr. 48 erledigt) ·
Demo-Anmeldung abschaltbar, ohne dass die Anmeldeseite verrät, dass es das
Konto gibt (AP7, 20.18.0).

**Die Oberfläche.** Handbuch und „Was ist NAdoku" als Seiten der Anwendung,
gerendert aus dem Repositorium, **ohne Anmeldung** erreichbar (AP8, 20.23.0;
Parsedown 1.7.4 vendoriert) · Erststart als Karte über der Tagesübersicht,
Konto-Rückfrage nach dem Notfallblatt, Betreiber-Rückfrage zum
Schlüsselblatt, Schlüsselerneuerung als eine Komponente mit zwei Verbrauchern
(AP9, 20.24.0; R83).

**Zwei Zusagen sind dabei nicht angetastet worden.** Der Server kennt den
Wiederherstellungsschlüssel nicht — deshalb speichert das Notfallblatt
nichts und lässt sich später nicht erneut drucken. Und die Erneuerung hält
den entpackten Inhaltsschlüssel gegen `pat_key_check`, **bevor** sie neu
verpackt: der einzige Weg, auf dem diese Phase Daten hätte unzugänglich
machen können.

**Prüfzahlen des Abschlusses:** Wortliste **0 Treffer** in fünf Bereichen ·
Vollständigkeit **398** auf der Schwelle · Migrationsregister **0** ·
Kontraste **22 Paare, 0 verfehlt** · Kettenaufrufe **0 Befunde, 0
ungeprüft** · Tore der Kette **11 erfüllt, 0 offen** · CSP **0** ·
Sitzungshärtung **0** · `php -l` **0 Fehler**. Die Abnahme von AP9 ist am
Schlüsselwechsel gemessen: nach zweimaliger Erneuerung öffnet der neue Code
die neue Hülle, der alte nicht mehr — und der alte öffnet weiterhin seine
eigene alte Hülle, womit der Fehlschlag die Ersetzung belegt und nicht einen
kaputten Erzeuger.

**Drei Nummernkollisionen** hat der Merge auflösen müssen, jede nach
derselben Regel (`main` ist vorgelagert): Web **20.16.0 → 20.16.5** für AP1,
Backlog **215–225 → 223–233** für die elf Punkte des Zweigs,
Rahmenplan-Fassung **77 → 78 und 79**.

*Reste:* Nr. **232** (die Fristen der Rückfragen sind nie im Betrieb
abgelaufen — geprüft mit gestelltem Datum, nicht mit gestellter Uhr) und
Nr. **233** (die Betreiber-Rückfrage fragt nie nach dem bisherigen
Server-Anteil). **Zuarbeit offen:** die anwaltliche Prüfung der drei
Entwürfe unter `docs/rechtstexte/` (E-P5b-24, -25) — AP4 baut so lange mit
Platzhaltern, und das ist der geplante Zustand, kein Rest. Das
**Prüfdokument bleibt liegen**, bis seine Prüfliste abgehakt ist.

### P5a — Kette und Fundament · Web 20.4.0 bis 20.15.2 · 15.–16.09.2026 (Schritt 10a, R82)

**Ein Push auf `main` geht seither auf Staging und nicht mehr auf Produktiv.**
Konzept `Konzept-P5a-Kette-und-Fundament.md` (Fable, 15.09.2026; nach dieser
Freigabe gelöscht, in der Historie unter `bcbb04f`), Umsetzung Opus in
**zwölf Arbeitspaketen plus dem Nachtrag AP4a** auf
`claude/butte-umsetzen-5opi9u`, je Paket ein Commit und ein Push. **Zwölf
Versionsstufen**, keine davon Haupt. **Eine Schemaerweiterung** (AP10,
`2026_09_16_sicherungsziel_aufbewahrung`) — `update.php` muss nach dem Merge
**laufen**.

**Die Kette** (AP1, 20.4.0). `pruefung.yml` als Stufe 1 bei jedem Push,
`auslieferung.yml` mit den Jobs `staging`, `stufe2` und `produktion`;
`deploy.yml` gelöscht. Produktiv wird nur noch über den Tag `web-vX.Y.Z`
beschrieben, und zwar nach **Pflichtfreigabe** und hinter dem **Backup-Tor**
(`tools/kette/tor.py`, Selbstprobe 5/5, Logik außerhalb des Arbeitslaufs, weil
eine Bedingung, die einen Deploy verhindern soll, nachweisbar sein muss).

**Das Fundament.** Plattformprüfung in `install.php` und Status (AP2) ·
Torwächter mit Wartungsmodus, der eine ausstehende Migration auch ohne Kette
erkennt (AP3, Nr. 54) · Kopfzeilen nach SP-5 mit Report-Only, HTTPS-Zwang,
Proxy-Liste und CSRF-API-Zweig (AP4, Nr. 8 und 67) · `use_strict_mode` und
`json_roh_out()` (AP4a, Nr. 203 und 205) · Mail-Warteschlange mit synchronem
erstem Versuch (AP5, Nr. 204) · Sperrleiter **15**/20/30/60 min und globale
**Verlangsamung statt Sperre** (AP6) · Mengenbremse `ingest.php` (AP7, R19,
Nr. 17) · Status → Sicherheit (AP8) · 503-Weg an der Verbindungsgrenze samt
den drei Messungen aus Nr. 37 (AP9) · Aufbewahrung auf dem Sicherungsziel
(AP10, Nr. 49 und 195) · Nachlöse-Job für die Gerätemodelle (AP11, Nr. 80
Teil 1).

**37 Entscheidungen sind in der Umsetzung dazugekommen** (E-P5a-22 bis -58).
Fünf davon sind Befunde, die sonst still ausgeliefert worden wären: der
Zähler, der bei unerreichbarer Datenbank selbst die Datenbank gebraucht hätte
(E-P5a-50) · die Fehlernummer **1226**, die GRANT-Grenze, die ein Hoster
tatsächlich setzt — das Konzept nannte nur 1040 und 1203 (E-P5a-51) ·
Deadlocks als 500 statt 503 (E-P5a-52, Nr. 210) · der verborgene
Einladungslink bei Konten im Zustand `wartet` (E-P5a-54) · ein
Sende-Lösch-Kreis auf dem Sicherungsziel, der täglich gelaufen wäre
(E-P5a-57).

**Prüfzahlen des Abschlusses** (AP12): Kreisläufe **edbak 328 771**, **csv
10 922** und **edbak-alt 287 852 Einzelvergleiche, je 0 unerklärt**,
Selbstproben **15/15** und **10/10** · Bilderlauf **400 Bilder auf 50 Seiten in
8 Breiten, 0 Überlauf / 0 Konsolenfehler / 0 falsche Knöpfe**, Gegenprobe
**400 verschiedene Prüfsummen** · Wortliste **0/0/0** (99 Regeln, 190 Dateien
in fünf Bereichen) · Vollständigkeit **377 = unverändert** · Kontraste
**22/0** · PHP-Syntax **485 Dateien, 0 Fehler**. Der **Stilvergleich entfällt
begründet**: `server/assets/style.css` ist in der ganzen Phase nicht angefasst
worden (leerer Diff gegen `main`) — jede Oberflächenänderung läuft über
vorhandene Bausteine.

**Zwei Nachträge aus einer Durchsicht der Betreiberin am 16.09.2026**, beide
an der Kette: Die **Zustandsdatei** der FTP-Aktion lag im Webroot und war per
HTTP lesbar — auf Staging mit **HTTP 200 gemessen** (20.15.1, Nr. 213; jetzt
`state-name` über dem Webroot, dazu eine Punktdatei-Sperre in `.htaccess` mit
Ausnahme für `.well-known/` und ein Stufe-2-Schritt, der beide Richtungen
misst). Und **`install.php` wurde bei jedem Lauf wieder ausgeliefert**, obwohl
das Runbook seit jeher „danach löschen" sagt (20.15.2, Nr. 214).

*Reste:* Die **Prüfliste mit 33 Punkten** in
`docs/konzepte/Pruefdokument-P5a-Kette-und-Fundament.md` — sie bleibt, bis sie
abgehakt ist. **Zehn davon (P1–P8, P12, P33) betreffen die Kette selbst und
sind hier nie gelaufen**: Sie brauchen GitHub-Umgebungen, eine Pflichtfreigabe
und einen Zweigschutz (Abschnitt 6a). **Die Kette ist gebaut, nicht erprobt** —
wer diese Phase für abgenommen hält, weil die Proben grün sind, verwechselt
„gebaut" mit „läuft". Dazu **Nr. 214** (die FTP-Aktion vergleicht gegen ihre
State-Datei, nie gegen den Server — wer dort von Hand löscht, bekommt die
Datei nie zurück), **Nr. 206 bis 212** aus der Umsetzung und **Nr. 80 Rest**
sowie **Nr. 37 Rest** (`post_max_size` der Zielanlage, R37.10) nach P5b und
P5c.

*Merge:* **am 16.09.2026 auf `main`** (PR #50, `14f99ac`) — die Auslieferungskette aus AP1 war zuvor mit PR #49 (`ee6d0b2`) vorgezogen worden. **`update.php` ist danach fällig** (Migration aus AP10).


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
| **90** | **20.09.2026** | **80 von 80 Verzeichnissen in EINER FTP-Sitzung — auch die Menge ist nicht die Ursache von F3, und damit ist der Vorrat an Vermutungen erschöpft, den ein zweiter Client prüfen kann.** Kette II/AP3, Lauf 35540252565: 80 Verzeichnisse angelegt, 80 Dateien hochgeladen, 80 eigene Datenverbindungen, `Datenkanal: EPSV, Antwort 229, Port 55595`, keine Abweisung, 80 wieder weggeräumt, Schritt 2:49. Die Auslieferung legt **62** Verzeichnisse an. **Sieben Vermutungen sind jetzt mit je einer Messung ausgeschlossen** (Läuferabbild/Node, Zertifikat, die Bibliothek als solche, TLS-Sitzungswiederverwendung, `ensureDir`/`_openDir`, Weg zum Datenkanal, Menge und Sitzungslänge) — und jede zeigt dasselbe: `curl` kann, woran die Auslieferungsaktion stirbt. **Der nächste Schritt ist deshalb eine Entscheidung, keine Messung:** entweder die Aktion selbst reden lassen (`log-level: verbose`), was einen **echten** Auslieferungslauf kostet — im Trockenlauf legt sie kein Verzeichnis an und erreicht `ensureDir` nie —, oder die Frage an den Hoster, die durch die sieben Messungen beantwortbar geworden ist: „Ein `curl` legt 80 Verzeichnisse in einer FTPS-Sitzung an, sauberes EPSV, ohne Abweisung. Ein Node-Client bekommt bei `ensureDir` auf das erste Verzeichnis einen `ECONNRESET` auf dem Datenkanal. Was unterscheidet die beiden auf Ihrer Seite?" Das ist **Prüfpunkt 18** des Prüfdokuments. Im selben Lauf nachgemessen sind die fünf Behebungen aus Fassung 89 (wachsende Zeitgrenze `670 s (30 + 8 je Ziel)`, Abbruch als Befund, keine Falschdiagnose, nachgemessene Restwarnung, gebündeltes Aufräumen — 80 Stück in einem Schritt, der kürzer war als der Vorlauf für 21). **Prüfpunkt 17 ist abgehakt. E-KH-09 steht: F3 ist so weit eingegrenzt, wie es von außen geht, und weiter nicht benannt.** **Nur `docs/`** — keine Versionsstufe (E-KH-23) |
| **89** | **20.09.2026** | **Die erste Mengenprobe gegen Produktiv ist gefahren — sie hat ihr eigenes Messgerät erschlagen, und das ist ein Befund über die Probe, nicht über den Server.** Kette II/AP3, Lauf 35539722380. **Was sie beantwortet hat: 21 Verzeichnisse in EINER FTP-Sitzung gingen durch**, jedes mit eigener Datenverbindung, ohne eine Abweisung. Die naheliegende Form der Sitzungsvermutung — ein Server, der die zweite oder dritte Datenverbindung einer Sitzung abweist — ist damit **widerlegt**; eine Grenze bei 30 oder 50 bleibt möglich. **Was sie nicht beantwortet hat, und warum:** `FTP_ZEITGRENZE_S` galt fest je `curl`-Aufruf, und die Mengenprobe macht EINEN Aufruf für alle Ziele — nach 60 s war Schluss, bei 21 von 80. Gemessen: rund 2,9 s je Ziel. **Vier Fehler, alle in der Probe, keiner im Server:** die feste Minute (jetzt wächst die Grenze mit der Zahl, 30 s + 8 s je Ziel, und `curl` bekommt ein eigenes `--max-time` darunter); der Abbruch war ein Absturz statt eines Befunds (die Ausnahme druckte die Befehlszeile mit achtzig Adressen — alles außer der Antwort des Servers; jetzt gefangen, mit Teilausgabe und der Zahl der abgeschlossenen Übertragungen); die Meldung sah aus wie ein Abbruch DURCH den Server (jetzt ausdrücklich „ABGEBROCHEN VON DER PROBE SELBST … NICHT vom Server" — das ist die eine Falschdiagnose, die einen falschen Befund zum Hoster schicken würde); und „59 Verzeichnisse konnten nicht entfernt werden" war falsch, weil 59 davon nie angelegt worden waren — jetzt wird nachgemessen statt gerechnet. **Dazu die Ursache der drei Minuten Laufzeit:** Das Aufräumen schickte einen `curl`-Aufruf je Befehl — bei 500 Verzeichnissen tausend TLS-Aufbauten und der Job in der Zeitgrenze, mit genau dem Müll im Webroot, den er wegräumen soll. Jetzt alle Löschbefehle in einem Aufruf, danach wird neu aufgelistet und zurückgegeben, was **tatsächlich** verschwunden ist. Selbstprobe der Zielprobe **67 → 81 Lagen, 0 offen**; drei davon halten die Attrappe selbst fest, die seit heute Buch über das Zielverzeichnis führt — vorher sah ein Aufräumen, das nichts tut, genauso aus wie eines, das alles wegräumt. **Prüfpunkt 17 ist zu wiederholen. E-KH-09 steht.** **Kein Code außerhalb von `tools/`** — keine Versionsstufe (E-KH-23) |
| **88** | **20.09.2026** | **Fünf Trennversuche an F3, fünfmal nicht gefunden — und jedes Mal eine Vermutung ausgeschlossen. Übrig bleibt die Sitzung selbst.** Kette II/AP3 hat den `ECONNRESET` der Auslieferung gegen Produktiv fünfmal eingekreist. Ausgeschlossen, jedes mit einer **Messung** statt mit einem Argument: Läuferabbild und Node-Fassung (beide Läufe 2.337.0 / ubuntu-24.04 20260907.300.1), das FTPS-Zertifikat (behoben von der Betreiberin), die Bibliothek als solche (lima-city läuft mit derselben Aktion grün), die **TLS-Sitzungswiederverwendung** (vier Läufe, beide Betriebsarten gelingen), das **Anlegen und Auflisten eines Verzeichnisses** (`ensureDir`/`_openDir`, beide Rundläufe gelingen) und der **Weg zum Datenkanal** (zweimal sauberes `EPSV` mit Port, kein Rückfall auf `PASV`). **Was übrig bleibt, ist die Bauform der Probe:** Sie ruft `curl` je Operation einmal auf und bekommt jedes Mal eine frische Sitzung; die Auslieferungsaktion hält **eine** Verbindung für 688 Dateien und 62 Verzeichnisse offen. Ein Server, der die zweite oder dritte Datenverbindung **einer** Sitzung abweist — Zeitgrenze, Portbereich, `MaxConnectionsPerHost`, Ratenschutz —, **sieht in der Zielprobe wie ein gesunder Server aus**, weil sie ihn jedes Mal neu fragt. Das stand bis heute nirgends. **Gebaut: `--mengenprobe N`** (1–500) in `tools/kette/zielprobe.py` — **ein** `curl`-Aufruf, N Verzeichnisse, eine Sitzung; gemeldet werden der Weg zum Datenkanal, „N von M abgeschlossen" und bei Abbruch der Servertext wörtlich. Sie läuft nur über die neue Eingabe **`probelauf_mengenprobe`** des Arbeitslaufs „Auslieferung", hinter dem Häkchen `probelauf` und der Pflichtfreigabe, und tritt dann an die Stelle der Rundläufe; ein Tag-Lauf und ein Push haben das Feld nicht. Sie ist **noch nie gegen einen echten Server gelaufen** — das ist **Prüfpunkt 17** und der Punkt, an dem AP3 hängt. Selbstprobe der Zielprobe **50 → 67 Lagen, 0 offen**; sie hat dabei zwei eigene Fehler gefunden (`--mengenprobe 0` hätte still die Rundläufe gefahren, ein früher Abbruch einen `NameError` statt eines Befunds gegeben). Prüfmittel: Wortliste 0/0/0, Kettenaufrufe 34/0/0, Tor 19, Wache 38, Jobregister 0 Befunde. **E-KH-09 (Pflichtstopp) steht weiter: F3 ist eingegrenzt, nicht benannt.** **Kein Code außerhalb von `tools/` und `.github/`** — keine Versionsstufe (E-KH-23) |
| **87** | **20.09.2026** | **Schritt 16 ist gemergt, und Kette II hat seine vier Zuarbeiten eingetragen.** **Schritt 16** (Sitzungsablage) steht auf `main` als Web **20.26.0** (PR #61 und #62, `1822d38`): Die Anwendung legt ihre PHP-Sitzungen jetzt selbst unter `.sitzungen/` mit `0700` ab, statt sie dort liegen zu lassen, wohin der Hoster `session.save_path` zeigen lässt — auf lima-city war das ein **geteiltes** Verzeichnis mit `0773` und Eigentümer root. **Vier Dinge sind daraus auf diesen Zweig gekommen:** **(1)** Der **achte Schutzlistenpfad** — `.sitzungen/**` und `.sitzungen/` in **beiden** `exclude`-Blöcken von `auslieferung.yml` und in `CLAUDE.md` 3. Je zwei Zeilen, weil die Aktion Datei- und Verzeichnismuster getrennt prüft; **acht Pfade sind der Prüfwert**. Beim heutigen Transport schützt die Liste nichts (die Fremd-Aktion listet das Fernverzeichnis nie, sondern liest nur ihre eigene Zustandsdatei — nachgemessen im Quelltext von `@samkirkland/ftp-deploy` 1.2.3 bis 1.2.5), bei einem Spiegel mit Löschabgleich schützt sie alle Sitzungen. **(2)** `tools/jobregister/` hängt jetzt in **Stufe 1**, ohne Bedingung — es hält die Tabelle „Der Katalog" in `docs/Technik.md` 4.97a gegen `server/jobs_lib.php` und rechnet in Millisekunden. Gemessen: **11 Jobs, 11 im Register, 17 Räumschritte, 0 Befunde, Selbstprobe 9 von 9.** Vor der Behebung standen 11 Jobs im Code gegen 9 im Register und 16 Räumschritte gegen „dreizehn" — eine Dokumentation, die in der Zahl irrt, wird geglaubt. **(3)** Backlog **Nr. 241** nach *Erledigt*; **Nr. 208** kam mit dem Merge bereits erledigt und ist nicht zurückgeholt worden. **Abschnitt 5 nachgemessen: 85 Zeilen = 85 offene Einträge**, dieselben Nummern, keine steht allein. **(4)** Der Merge brachte die zwei vorhergesagten Konflikte: In `docs/Technik.md` hängen beide Zweige eine Zeile an dieselbe Stufe-1-Tabelle — **beide behalten**, und der Vorbehalt „noch nicht eingehängt" ist gestrichen, weil es jetzt hängt. Bei `docs/konzepte/Konzept-Sitzungsablage.md` (add/add) gilt die Fassung von `main`, weil dort Statusblock und Umsetzungsabschnitt fortgeschrieben sind. **Dazu aus Kette II/AP3:** Der Handauslöser hat eine zweite Eingabe **`probelauf_ohne_sitzung`** — die Zielprobe fährt damit **ohne** Wiederverwendung der TLS-Sitzung auf dem Datenkanal. Das ist die letzte fehlende Messung für **E-KH-09**: Gelingt der Rundlauf *mit* und scheitert er *ohne*, verlangt der Server sie, und das erklärt den `ECONNRESET` der Auslieferungsaktion. Ein Schalter und kein zweiter Schritt — zwei Schritte wären zwei Wege durch die Kette, und der eine wäre nie gefahren worden. **Kein Code** — nur `.github/`, `tools/`, `docs/`, `CLAUDE.md`; keine Versionsstufe (E-KH-23) |
| **86** | **20.09.2026** | **AP3 der Kettenhärtung: Das Tor rechnet mit der Uhr des Servers, und die Kette glaubt der FTP-Aktion nicht mehr.** Drei Dinge. **(1) F1 behoben** (E-KH-05): Der Laufbeginn kommt jetzt aus dem `Date`-Kopf der ersten Antwort statt von der Uhr des GitHub-Läufers — **eine Uhr, ein Vergleich**; ein Stand, der älter ist als der Laufbeginn, bricht nicht mehr ab, sondern dreht **eine zweite Runde** (das ist das Bild eines fremden Komplett-Auftrags, der beim Aufruf schon lief); und eine Antwort ohne `fertig` und ohne `error` führt zu einem **definierten Abbruch** statt zu vierzig Runden Warten (E-KH-19). Selbstprobe des Tors **von 11 auf 17 Lagen**, 0 offen. **(2) Die Zielprobe** (E-KH-07, neu: `tools/kette/zielprobe.py`) schreibt per FTPS eine Datei mit Zufallsnamen ins Zielverzeichnis, holt sie über HTTPS zurück, vergleicht sie, löscht sie und prüft das Löschen. Sie steht im Job `produktion` **vor dem Backup-Tor** — solange noch nichts verändert ist. `curl` ist dabei **bewusst ein zweiter FTPS-Client** neben der Auslieferungsaktion: Das ist der Trennschnitt, den F3 braucht. Zwei Betriebsarten (mit und ohne Wiederverwendung der TLS-Sitzung), und ob `curl` sie wirklich wiederverwendet, meldet sie **dreiwertig** — `JA`, `NEIN` oder `NICHT FESTSTELLBAR`. Selbstprobe **26 Lagen**, 0 offen. **(3) Der Probelauf** (E-KH-08): Eine Handauslösung mit der Eingabe `probelauf` fährt denselben Job mit derselben Pflichtfreigabe, aber ohne Tag-Vergleich, ohne Tor der grünen Läufe, ohne Backup, ohne Wartung und mit dem Abgleich als Trockenlauf; geschrieben wird nichts außer der Probedatei. **Z5 Teil 1 ist aus der Umsetzung heraus erledigt und ist ein Ausschluss:** Der grüne Lauf vom 18.09. und der rote vom 20.09. liefen auf **demselben** Läufer (2.337.0) und **demselben** Abbild (ubuntu-24.04 / 20260907.300.1) — Läuferabbild und Node scheiden als F3-Ursache aus. Dazu ein Befund, der die Frage verengt: Der Abbruch steht bei `creating folder "api/"` → `_openDir` → `ECONNRESET (data socket)`, also bei der **ersten Datenverbindung** des Laufs; der Steuerkanal steht. **PFLICHTSTOPP (E-KH-09):** Der Probelauf gegen Staging und der Trennversuch gegen Produktiv brauchen die Betreiberin — Prüfpunkte 10 bis 12. Und der Staging-Probelauf hängt am selben Nagel wie AP1: Die Zielprobe liest über HTTPS zurück, und genau das weist lima-citys Botprüfung ab. **Kein Code** — nur `tools/`, `.github/`, `docs/`; keine Versionsstufe (E-KH-23) |
| **85** | **20.09.2026** | **Einschub vom 20.09.2026 eingespielt: P5b-Nachmessung, Reihenfolge, Schritte 16–18, Backlog 241–249, Abschnitt 5 bereinigt, V1–V9, Konzept P5c (freigegeben), Konzept Sitzungsablage.** Die Änderungsliste kam von der Konzeptinstanz; Fassungs- und Backlog-Nummern vergibt bis zum Merge von Kette II nur dieser Zweig, und das steht jetzt im Kopf. **Der Einschub nannte Fassung 84 — die war zu diesem Zeitpunkt schon von AP2 der Kettenhärtung vergeben und gepusht; nach derselben Regel, nach der das Paket bei der Backlog-240 gewichen ist, rückt es hier auf 85.** Im Einzelnen: **10b ist gemergt** (PR #57, `eec41e1`, 18.09.2026), danach PR #58 bis #60 — `update.php` ist damit für **sieben** Migrationen fällig, nicht für sechs. **Die Reihenfolge ist neu gefasst:** Kette II (läuft) → 16 → 15 → 10c → 17 → 18 → 12 → 12a → 13 → 14; die alte („10a → 15 → 10b → …") war überholt, weil 10b vor Schritt 15 gebaut wurde — Schritt 15 misst deshalb 10a **und** 10b gemeinsam nach. **Drei neue Schritte:** 16 Sitzungsablage (Konzept freigegeben, Umsetzung läuft auf eigenem Zweig von `main`), 17 Backlog-Runde 4, 18 Sicherheitsrunde II. **Kette II steht jetzt als eigene Fahrplanzeile** — sie fehlte, obwohl sie läuft. **10c ist ausgeschrieben** (Zweitfaktor als Pflicht für Admin, BetreiberIn und Support; Protokoll unter Verwaltung; ohne Nr. 122, mit 243–246 und 248). **Abschnitt 5 ist bereinigt und gemessen:** elf Zeilen raus (8, 17, 48, 49, 54, 67, 181, 195, 203, 204, 205 — P5a und P5b haben sie erledigt), siebzehn Zeilen rein (207–214, 216, 222, 227, 234, 236–240), neun für 241–249, elf umverteilt (122 → Schritt 17, 121/198/200 → 10c, 57 → Schritt 15, die Uhr- und Android-Punkte → P7). **Zeilen in Abschnitt 5 = offene Backlog-Einträge = 87**, und es sind dieselben Nummern — beide Mengen gegeneinander gehalten, keine steht allein. **Zwei Fallen sind gemeldet und NICHT behoben**, weil sie zu Nr. 177 (Schritt 17) gehören: ein freier Absatz mitten in der Tabelle dieses Abschnitts, und die doppelt vergebenen Fassungsnummern 35–39 im alten Teil. **Kein Code** — nur `docs/`; keine Versionsstufe |
| **84** | **20.09.2026** | **AP2 der Kettenhärtung: Die Integritätswache vergleicht ab jetzt gegen das Ausgelieferte und nicht mehr gegen `main`.** Sie war seit dem 17.09.2026 **täglich rot**, und zwar grundlos — sie hielt den Produktivserver gegen den Zweig, auf dem sie lief. Neu ist der **Zeiger `produktion`**: ein Zweig, der auf den ausgelieferten Commit zeigt, bewegt allein vom neuen Job **`zeiger`** in `auslieferung.yml` und nur nach einem erfolgreichen `produktion`-Job. Das Werkzeug kommt weiter von `main`, der Vergleichsstand vom Zeiger (`wache.py … --stand zeiger`); die Zusammenfassung nennt Commit, Tag und Dateizahl. Der `workflow_run`-Riegel ist **entfallen** — er war nur nötig, solange gegen `main` verglichen wurde, und ließe die Wache jetzt nach einem Staging-Deploy grundlos schweigen. **`contents: write` steht ausschließlich im Job `zeiger`;** der Job mit den FTPS-Geheimnissen bleibt bei `read`, und `actions: read` ist mit dem Riegel ausgetragen. **Belegt ohne Netz:** Selbstprobe der Wache **38 Erwartungen, 0 offen** (vorher 32; sechs neue zum Vergleichsstand, darunter „fehlender Zeiger fällt auf" und „ein gekipptes Byte im Vergleichsstand ergibt eine andere Summe"), und der Befund B6 **aus den Ständen nachgerechnet**: Am 18.09.2026 hätte die alte Anordnung **128 Dateien, 121 gleich, 1 abweichend, 6 × 404** gemeldet, die neue **122 von 122 gleich**. **Zwei Messungen fehlen und stehen als Prüfpunkte 8 und 9:** Der Handlauf gegen Produktiv läuft aus dem Container nicht (Proxy `403`, gemessen an 128 von 128 Dateien) — und er belegte heute ohnehin nichts, weil sich zwischen Zeiger und `main` unter `server/assets/` **0 Dateien** unterscheiden. Der Job `zeiger` ist **gebaut, nicht gelaufen**; er misst sich mit M1. Dazu: Der Zweig `produktion` braucht noch einen Schutz gegen Pushes von Hand — das sind Repositoriumseinstellungen, keine Datei. **`CLAUDE.md` 3** trägt die Regel dazu. **Kein Code** — nur `.github/`, `tools/`, `docs/`, `CLAUDE.md`; keine Versionsstufe (E-KH-23) |
| **83** | **20.09.2026** | **Der erste Kettenlauf gegen lima-city — halb gelungen, und die gelungene Hälfte beantwortet drei offene Fragen.** Handlauf (`workflow_dispatch`) auf dem Arbeitszweig, **kein Push auf `main`**: Die Kette lässt sich von jedem Zweig auslösen, und `produktion` kann dabei nicht anspringen (gemessen: übersprungen). **Job `staging` grün** — 12 Dateien, `Uploading: 0 B · Deleting: 0 B · Replacing: 1,13 MB`, 12 Sekunden. **Stufe 2: zwei von fünf Messschritten gelaufen und gemessen grün** (`login.php` HTTP 200 mit der Fußzeile der Anwendung; vier Punktpfade 403, `.well-known/` 404), der dritte **rot** (Kreisläufe), die letzten zwei deshalb nicht gelaufen. **(1) lima-city verträgt das `../` der Zustandsdatei** — der Abgleich meldet `Saving current server state to "/../.deploy-state-staging.json"`; für Produktiv bleibt die Frage offen, und was der Lauf NICHT sagt, ist, ob die Datei über dem Webroot liegt oder der Server `/..` zurückfaltet. **(2) F3 ist eingegrenzt:** Dieselbe Fremd-Aktion, dasselbe Muster (Konto auf `/` eingesperrt, `FTP_ZIELPFAD = /`, `../`-Zustandsdatei) läuft gegen lima-city **ohne `ECONNRESET`** — der Abbruch gegen Produktiv ist also kein Fehler der Bibliothek und keine Eigenschaft dieser Anordnung. Eingabe für AP3 und **E-KH-09**. **(3) „Übersprungen zählt als grün" (B5) ist für die zwei gelaufenen Schritte ausgeschlossen** — beide haben Zahlen ausgegeben. Dazu ein Nebenbefund zu **E-KH-20**: `Deleting: 0 B` belegt am laufenden Lauf, dass die Fremd-Aktion nichts Server-eigenes anfasst. **Abschnitt 6a: vier Haken mehr, alle mit Beleg** — Schritt 2 (Datenbank, belegt durch die gelungene Einrichtung), Schritt 6 (`install.php` ist durch, die Anlage liefert die Anmeldeseite), Schritt 10 (`JOBS_TOKEN` der neuen Anlage — die Job-Pause hat zweimal mit `{"ok": true, …}` geantwortet), und Schritt 5 als halb gelungen vermerkt. **Der rote Schritt:** `STAGING_KONTO` und `STAGING_PASS` melden sich nicht an — `Anmeldung gescheitert: unbekannt`, und „unbekannt" heißt, dass die Anmeldeseite **ohne Fehlermeldung** zurückkam (`sitzung.py`:130). Bei falschen Zugangsdaten stünde dort eine; belegt ist die Ursache nicht. Bedienweg mit drei Ausgängen im Prüfdokument, Prüfpunkt 5. **AP1 ist damit gebaut, aber weiterhin nicht abgenommen.** **Kein Code** — nur `docs/`, keine Versionsstufe |
| **82** | **20.09.2026** | **Zuarbeit Z3 eingetragen — die beiden Tabellen aus Fassung 81 sind gefüllt, und eine davon hat sofort einen Fehler der Anwendung gezeigt.** Die Betreiberin hat beide Anlagen genannt. **Produktiv** aus *Betrieb → Status* und *Betrieb → Hintergrundjobs*: PHP 8.3.33, `memory_limit` 512 MB, `max_execution_time` 240 s, MariaDB 10.11.14, `max_user_connections` nicht gesetzt (es gilt `max_connections` = 151), **OPcache aus**, **kein Cron** — alle elf Jobs laufen huckepack, „GPS-Daten verdichten" mit **Rückstand 69**. **Staging** aus einer `phpinfo()`, weil die Anwendung dort **noch nicht läuft**: PHP 8.3.33 (dieselbe), `max_execution_time` **300 s**, `post_max_size` und `upload_max_filesize` je **500 MB** statt 256. **Damit ist die Kernaussage von 6.3a belegt und nicht mehr nur begründet:** drei Weblimits weichen ab, Stufe 2 misst auf Staging andere Grenzen, als Produktiv hat. **Fünf Zeilen der Staging-Spalte bleiben leer** — Datenbank, Verbindungsgrenze, Kontingent, Platz, Cron weiß nur die Anwendung, und Schritt 6 der Einrichtung **scheitert gerade**. **Abschnitt 6a** trägt jetzt drei belegte Haken (1, 3 und 4 zur Hälfte), die Variablentabelle beide Spalten, und eine offene Frage, die aus ihr folgt: **Beide FTP-Konten sind auf `/` eingesperrt, in beiden Umgebungen fehlt `FTP_STATE_PFAD`** — ob die Server das `../` der Zustandsdatei vertragen, beantwortet der erste Kettenlauf gegen lima-city für Staging und AP3 für Produktiv; vorsorglich umgestellt wird nichts. **Der Fund des Tages steht im Prüfdokument als F-KH-U-05:** Auf lima-city steht `opcache_get_status` in `disable_functions`, `function_exists()` antwortet dafür `false`, und `plattform_pruefen()` meldet „OPcache: aus", während er läuft — ein Verstoß gegen die eigene Regel aus `Technik.md` 5b.1 („`null` nicht feststellbar"). **Nicht behoben**, weil das Web-Code wäre und AP1 keinen anfasst; Backlog-Vorschlag im Prüfdokument. **Er ist der erste Ertrag von E-KH-04:** ein Zuschnitt auf den einen Hoster, der sechs Tage niemandem auffiel, weil beide Anlagen derselbe Hoster waren. Drei Punkte für die Betreiberin sind dazugekommen: `info.php` liegt öffentlich im Staging-Webroot, drei Geheimnisse der Umgebung `staging` gehören noch der stillgelegten Anlage, und `session.save_path` zeigt bei lima-city über das eigene Verzeichnis hinaus. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **81** | **20.09.2026** | **Staging ist umgezogen — Kette II/AP1 zieht es in der Dokumentation nach (E-KH-04).** Staging lag bis zum 19.09.2026 als `staging.nadoku.gen-em.org` im **selben Plesk-Abonnement** wie Produktiv und unter **demselben Systemnutzer**: Staging-PHP konnte Produktivs `config.php` lesen, und damit reichten die Staging-Zugangsdaten an Pflichtfreigabe und Backup-Tor vorbei bis Produktiv (Befund B2 der Kettendurchsicht vom 20.09.2026). Seit dem 20.09.2026 liegt es als **`staging-nadoku.gen-em.org` bei lima-city**, also bei einem **anderen Hoster**; die alte Anlage ist am selben Tag stillgelegt (Zuarbeit Z1). **E-PP-09 ist damit in Adresse und Hoster ersetzt** — in `docs/konzepte/Vorbereitung-P5-Plattformprofil.md` vermerkt, der Wortlaut bleibt als Herkunft stehen. **Abschnitt 6a ist neu geschrieben:** Alle Haken galten der alten Anlage und stehen wieder offen, mit dem Grund je Zeile; die Schrittnummern bleiben, weil `auslieferung.yml` sie in seinen Fehlermeldungen nennt („Rahmenplan 6a, Schritte 1 bis 3"); dazu eine Tabelle der nicht-geheimen Variablenwerte (FTP-Wurzel, `FTP_ZIELPFAD`, `FTP_STATE_PFAD`) — **noch leer, Zuarbeit Z3**. **Der Preis des Umzugs steht in `docs/Technik.md` 6.3a** (neuer Abschnitt): Staging belegt kein Plattformverhalten von Produktiv mehr, die Zahlen des Messstands sind nicht mehr übertragbar; dafür wird die Portabilität nach R81 seither mit **jedem Push** geprobt statt nur behauptet. Der Plattformvergleich beider Anlagen steht dort als Tabelle, **ebenfalls noch leer (Z3)** — geratene Zahlen wären schlimmer als keine. Dazu `CLAUDE.md` 3 und der Kommentar in `auslieferung.yml`, der die Messung vom 16.09. als Messung an der **damaligen** Anlage kennzeichnet. **Beim Nachmessen für diese Fassung aufgefallen:** Der Kopf führte P5b noch als „liegt zum Merge bereit", zwei Tage nach PR #57 — derselbe Fall wie in den Fassungen 39, 41 und 42. Der Stand ist berichtigt (`origin/main` `7150793`, Web 20.24.2, Uhr 3.1.0, Android 0.15.0); die **Erledigt-Zeile für P5b fehlt weiterhin** und gehört dem Abschluss von P5b, nicht diesem Paket. **Kein Code** — nur `docs/`, `CLAUDE.md` und ein Kommentar in `.github/`, keine Versionsstufe (Präzedenz `0f2333e`) |
| **80** | **17.09.2026** | **P5b abgeschlossen — AP8, AP9 und AP10, und der Merge von `main` (Web 20.23.0 und 20.24.0, Zweig `claude/magical-dirac-we2y1z`).** **AP8:** Handbuch und „Was ist NAdoku" sind Seiten der Anwendung, gerendert aus dem Repositorium und **ohne Anmeldung** erreichbar; Parsedown 1.7.4 vendoriert, `DokuMarkdown` setzt drei Hausregeln durch (Sprungmarken, `rel="noopener"`, Bilder nur relativ). **AP9:** Erststart als **Karte** über der Tagesübersicht (kein Dialog — „wer sie ignoriert, arbeitet trotzdem"), Konto-Rückfrage nach 30 Tagen/6 Monaten/jährlich, Betreiber-Rückfrage mit vier zufällig gewählten Vierergruppen vom Schlüsselblatt, Notfallblatt, Schlüsselerneuerung als **eine** Komponente mit zwei Verbrauchern (R83). **Damit sind alle zehn Arbeitspakete gebaut.** **AP10** ist der Merge: `main` war seit dem P5a-Abschluss um Web 20.16.0 bis 20.16.4 weitergelaufen. **Drei Nummernkollisionen aufgelöst**, jede nach derselben Regel — `main` ist vorgelagert, also weicht der Zweig: Web **20.16.0 → 20.16.5** (AP1), Backlog **215–225 → 223–233** (die elf Punkte des Zweigs; `main` behält 215–222), Rahmenplan-Fassung **77 → 78 und 79**. **Prüfzahlen:** Wortliste 0 in fünf Bereichen, Vollständigkeit 398 auf der Schwelle, Migrationsregister 0, Kontraste 22/0, Kettenaufrufe 0/0, Tore 11/0, CSP 0, Sitzungshärtung 0, `php -l` 0. **Zwei neue Reste:** Nr. 232 (Fristen nie im Betrieb abgelaufen) und Nr. 233 (bisheriger Server-Anteil wird nie abgefragt). Das Konzept **liegt noch** und wird erst nach der Freigabe gelöscht (K9); das **Prüfdokument bleibt** ohnehin, bis seine Prüfliste abgehakt ist |
| **79** | **17.09.2026** | **P5b-Paket eingespielt; AP2, AP4, AP5, AP6 und AP7 gebaut (Web 20.17.0 bis 20.21.1, Zweig `claude/magical-dirac-we2y1z`).** Der Auftraggeber hat das vollständige Paket aus der Konzeptsitzung nachgereicht: das **freigegebene** Konzept (16.09.2026, ohne Änderungen), den **Mockup-Ordner** und die drei Rechtstext-Entwürfe. Die Mockups sind am **17.09.2026 freigegeben** — fünf Darstellungen (Dokumentseite, Registrierung, Erststart, Rückfrage, Notfallblatt), vier davon zusätzlich bei 376 px, **9 HTML und 9 PNG** mit `LIESMICH.md`, gegen das echte `style.css` gebaut. **Damit fällt die Pause vor AP3, AP8 und AP9** (K8); dieser Zweig hatte zuvor zwei eigene Entwürfe mit **Opus statt Fable** gebaut (Weisung vom 16.09.2026) — sie sind abgelöst und entfernt, die Gestaltung kommt also doch aus dem vorgesehenen Modell. **Vier Gestaltungsvorgaben** vom 17.09.2026 gelten für die **Umsetzung** und nicht nur für die Mockups (Konzept Abschnitt 6): Zeilenaktionen **und Plaketten** rechtsbündig in einer Spalte, Kartenfuß mit Häkchen links und „Später" rechts, vertikale Zentrierung als Abnahmekriterium, und „Vereinbarung zur Auftragsverarbeitung (AVV)" bleibt. **Die Einschübe aus Konzept Abschnitt 7 und 8 sind hier eingearbeitet:** Fahrplanzeile 10b, Schritt-10-Block (mit **V2, V3 und V7** als beantwortet — V2 betrieblich entschieden und **rechtlich offen**, V6 nur noch halb), Abschnitt 5 (Nr. 37 **Speichergrenzen-Teil** und Nr. 48 erledigt, Nr. 202 Paket 1 ganz durch), Abschnitt 6 (zwei Zuarbeiten neu: `update.php` nach dem Merge mit **fünf** Migrationen, anwaltliche Prüfung der drei Entwürfe), Register **R9, R25, R37**. Backlog **228 bis 230** vergeben (Proof-of-Work, Uhr-Grund bei `403`, Pflege der Wegwerfliste); **Nr. 48 nach *Erledigt* verschoben**. **Gebaut in diesem Abschnitt:** AP2 Lebenszyklus (20.17.0), AP7 Demo-Anmeldung abschaltbar (20.18.0), AP4 Einwilligungen (20.19.0), AP5 Selbstlöschung mit 30 Tagen Karenz und Adresswechsel mit Bestätigung (20.20.0), AP6 Mengengrenze je Konto mit `507` (20.21.0). Dazu **Web 20.21.1** mit drei Funden, die **kein Prüfmittel gemeldet hat** (Nr. 225, 226, 227): Die Profilseite lag **zehn Tage** außerhalb ihres Seitengerüsts, während der Bilderlauf für sie in allen drei Engines drei Nullen meldete — gefunden beim **Ansehen** eines Bildes. Der Lauf zählt seither Karten außerhalb von `main.inhalt`: **53 Seiten, 149 Karten, 0 außerhalb**, mit wieder eingebautem Fehler **6 geprüft, 4 außerhalb**. **Prüfzahlen:** Vollständigkeit **387** (auf der Schwelle, von 377 angehoben und begründet — Nr. 227), Wortliste **0/0/0** über fünf Bereiche, Kontraste **22 Paare, 0 verfehlt**, Mockups gegen die Sperrliste **0 Treffer** und in Chromium **0 Konsolenfehler, 0 Ladefehler**. **Offen bleiben AP3, AP8, AP9 und AP10**; außerhalb die anwaltliche Prüfung der Rechtstexte. **Selbstprüfzahl Abschnitt 5 nachgerechnet** (Einzeiler aus dem Kasten dort): **70 gegen 72** — vorher 67 gegen 70, die Lücke ist also von drei auf zwei geschrumpft und nicht neu. Sie hat zwei bekannte Ursachen: Elf Zeilen des Rahmenplans führen Nummern, die der Backlog längst unter *Erledigt* hat und die nach dem Merge herausfallen (Fassung 76), und die Punkte **207 bis 227** aus den Zweigen P5a und P5b haben in Abschnitt 5 noch gar keine Zeile. Beides gehört ins nächste Doku-Paket, nicht hierher. **Code auf dem Zweig, nicht auf `main`** |
| **78** | **16.09.2026** | **P5b begonnen — AP1: der Schreibweg des Protokolls (Web 20.16.5, Zweig `claude/magical-dirac-we2y1z`).** Konzept und die drei Rechtstext-Entwürfe aufgenommen (`docs/konzepte/Konzept-P5b-Konto-und-Registrierung.md`, `docs/rechtstexte/`), Prüfdokument angelegt. **AP1** legt `protokoll_ereignisse` an (sechs Reiter, zwei Fristen — Verwaltung 365 Tage einstellbar, übrige 30 fest; der siebte Reiter *Sicherheit* bleibt in seiner P5a-Tabelle, V6 entscheidet später), dazu `protokoll_lib.php`, `konten_einstellungen_lib.php`, die Karte „Konten“ in den Servereinstellungen und die Zählkarte auf Status. **V1 gilt: kein Zugriffsprotokoll.** **Der `app_state`-Teil von AP1 entfiel** — `app_state_lesen()`/`_setzen()` stehen seit Web 20.7.0 in `db.php` (das Konzept sah beides vor, Abschnitt 3.0). **Zwei Befunde vor dem ersten Paket:** **Nr. 223** — nach P5a/AP4 ließ sich die Anwendung **nicht mehr installieren** (`install.php` → HTTP 500, weil `ui_seite_start()` über `kopfzeilen_lib.php` das harte `require config.php` in `db.php` zog); behoben in Web 20.15.3. Kein Prüfmittel konnte ihn sehen: alle setzen eine laufende Installation voraus, statt eine einzurichten. Und **der Kopf dieses Dokuments** stand auf Fassung 74, während Abschnitt 10 bis 76 reichte, und maß `main` bei `7f334cb` / Web 20.3.0 — zwölf Auslieferungen zurück; berichtigt (Regel aus Fassung 42). **Nummernkollision aufgelöst:** `main` hatte Web 20.15.2 und Nr. 214 parallel vergeben; dieser Zweig führte damals **215 und aufwärts** — seit dem Merge vom 17.09.2026 sind daraus **223 und aufwärts** geworden, weil `main` 215 bis 222 parallel belegt hatte. **Prüfzahlen AP1:** Bereinigung 3 von 3 Fristfällen richtig (31 d E-Mail weg, 31 d Verwaltung bleibt, 366 d weg), Fehlfall V7 belegt (Handlung läuft, Zähler +1, rote Plakette), Bilderlauf 16 Bilder in 8 Breiten 0/0/0. **Code auf dem Zweig, nicht auf `main`** |
| **77** | **16.09.2026** | **Nachlese zum P5a-Abschluss — eine unabhängige Durchsicht, 22 bestätigte Befunde.** Nach dem Merge gefahren; fünf Befunde waren durch den Abschluss schon erledigt, der Rest ist hier behoben. **Die unangenehmsten waren eigene Zahlen:** Die Erledigt-Zeile und Fassung 75 nannten die Sperrleiter **10**/20/30/60 — gebaut ist **15**/20/30/60, und `ratelimit_lib.php` sagt im Kopfkommentar ausdrücklich, dass 15 eine benannte Abweichung vom Konzept ist (E-P5a-43); der Kopf des Dokuments stand auf Fassung 74, während der Verlauf schon 76 führte; die Standzeile maß `main` noch bei `7f334cb` (Web 20.3.0) statt bei `14f99ac` (Web 20.15.2); und „Code auf dem Zweig, nicht auf `main`" stimmte nicht mehr. **Abschnitt 6 und 6a widersprachen sich** über dieselben Zuarbeiten am selben Datum — der Stand steht jetzt an EINER Stelle (6a), Abschnitt 6 verweist dorthin und nennt nur noch, was **gemessen** offen ist: der Zweigschutz (`main` trägt `protected: false`). Dieselbe veraltete Annahme stand in einer Zeile, die der Abschluss selbst geschrieben hatte. **In `docs/Technik.md`** führte der Verzeichnisbaum die in AP1 gelöschte `deploy.yml` und keinen der drei Arbeitsläufe; zwei weitere Stellen nannten sie als lebende Datei. Der Serverbaum war um **sieben** Dateien unvollständig — jetzt vollständig, nachgezählt gegen `os.listdir`. **Im Prüfdokument** hießen fünf Wortlisten-Bereiche zweimal „sechs", und „458 Dateien" maß `server/` allein, während die Nachbarzahlen `server/` **und** `tools/` messen — keine falsche Zahl, aber eine, die nicht sagte, was sie misst. **In `CLAUDE.md` und der Bilderlauf-Anleitung** standen 30 bzw. 49 Seiten; gemessen sind **50** (400 Bilder). **Kein Code** — nur `docs/`, `CLAUDE.md` und `tools/*/LIESMICH.md`, keine Versionsstufe |
| **76** | **16.09.2026** | **P5a abgeschlossen — Freigabe erteilt.** Erledigt-Zeile in **Abschnitt 8** (Web 20.4.0 bis 20.15.2, zwölf Pakete plus AP4a und zwei Nachträge; Prüfzahlen des Abschlusses; fünf Befunde, die still ausgeliefert worden wären). **Das Konzept ist gelöscht** (`CLAUDE.md` 7), die Historie behält es unter `bcbb04f`; **das Prüfdokument bleibt**, bis seine 33 Punkte abgehakt sind. Fahrplanzeile 10a auf **ERLEDIGT**. **Abschnitt 5 nachgezählt und richtiggestellt:** zwölf der 67 Zeilen führten einen Punkt als offen, den der Backlog längst unter *Erledigt* hat (8, 17, 49, 54, 67, 181, 195, 203, 204, 205 — dazu die zwei schon gekennzeichneten); sie sind jetzt so gekennzeichnet und werden nach dem Merge aus der Tabelle genommen, wie es Fassung 32 für dreizehn Nummern vorgemacht hat. **Abschnitt 6a: Schritte 1 bis 6 abgehakt** — Staging steht und ist eingerichtet (gemessen: `login.php` HTTP 200, Titel „Anmelden — Gen-EM NAdoku"); offen bleiben 7 (SMTP und Betreff-Präfix in `config.php`), 8 (Demo, Referenzdatensatz, Messstand-Konto) und 9 (eigenes SFTP-Backup-Ziel). Abschnitt 6 um die **P5a-Reste nach dem Merge** ergänzt. **Der Tag steht weiterhin aus** — er ist die Auslieferung auf Produktiv. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **75** | **16.09.2026** | **P5a umgesetzt — zwölf Pakete, Web 20.4.0 bis 20.15.0.** Auf dem Zweig `claude/butte-umsetzen-5opi9u`, ein Commit je Arbeitspaket: **AP1** Auslieferungskette (`pruefung.yml` Stufe 1, `auslieferung.yml` mit Staging, Stufe 2 und Produktion hinter Pflichtfreigabe und Backup-Tor; `deploy.yml` gelöscht — damit ist die Zeit vorbei, in der ein Push auf `main` sofort produktiv ging) · **AP2** Plattformprüfung in `install.php` und Status (R81) · **AP3** Torwächter mit Wartungsmodus (Nr. 54) · **AP4** Kopfzeilen nach SP-5 mit Report-Only, HTTPS-Zwang, Proxy-Liste, CSRF-API-Zweig (Nr. 8, 67) · **AP4a** `use_strict_mode` und `json_roh_out()` (Nr. 203, 205) · **AP5** Mail-Warteschlange mit synchronem erstem Versuch (Nr. 204) · **AP6** Sperrleiter **15**/20/30/60 min und globale Verlangsamung (15 und nicht 10 — benannte Abweichung vom Konzept, E-P5a-43: `login` sperrt heute fest 900 s, mit 10 wäre der erste Verstoß nach dem Update **milder** als davor) · **AP7** Mengenbremse `ingest.php` (R19, Nr. 17) · **AP8** Status → Sicherheit · **AP9** 503-Weg an der Verbindungsgrenze und die drei Messungen aus Nr. 37 · **AP10** Aufbewahrung auf dem Sicherungsziel (Nr. 49, 195) · **AP11** Nachlöse-Job für die Gerätemodelle (Nr. 80 Teil 1). **37 Entscheidungen sind in der Umsetzung dazugekommen** (E-P5a-22 bis -58) — darunter fünf Befunde, die sonst still ausgeliefert worden wären: der Zähler, der bei unerreichbarer Datenbank selbst die Datenbank gebraucht hätte, die Fehlernummer **1226** (die GRANT-Grenze, die ein Hoster tatsächlich setzt — das Konzept nannte nur 1040 und 1203), Deadlocks als 500 statt 503, der verborgene Einladungslink in der Nutzerverwaltung und ein Sende-Lösch-Kreis auf dem Sicherungsziel. **Prüfzahlen des Abschlusses** stehen in der Erledigt-Zeile (Abschnitt 8), die mit der Freigabe geschrieben wird. **Merge nach `main` und Tag stehen aus** (K7). Fahrplanzeile 10a und Kopf fortgeschrieben; Backlog **Nr. 54 und 67 nach Erledigt** (Nr. 8, 17, 49, 195 lagen dort schon), Nr. 80 auf „Teil 1 erledigt"; neu aufgenommen **Nr. 206 bis 212** aus der Umsetzung. **Code auf dem Zweig** — AP1 liegt seit PR #49 (`ee6d0b2`) allerdings bereits auf `main`; der Rest folgt mit PR #50 |
| **74** | **16.09.2026** | **Schritt 15 Zentralisierung, R83, P5c-Vorbereitung, Nachträge an P5a.** Aus der Analyse einer eigenen Sitzung (16.09.2026, Stand `main`; zwei Fassungen desselben Befunds, die ausführlichere ist maßgeblich): neuer **Schritt 15 — Zentralisierung: eine Stelle je Sache** mit sechs Paketen, Ausgangslage („bereits zentral"), R83 und Backlog **Nr. 202** als Sammelnummer; Einordnung **zwischen 10a und 10b** statt vor P6 (Begründung im Block); Abschnitt 3 bekommt den Satz zur Reihenfolge der offenen Schritte (Nummern sind Namen). Drei Nebenfunde als Fehler **203–205**, alle als Nachträge an P5a (AP4a, AP5), weil die Dateien dort gerade umgebaut werden — Anweisung an die Instanz übergeben (Abschnitt 6). Nachgemessen am P5a-Zweig: Marke (E-P5a-35) und CSRF-Gatter (Nr. 67) sind dort schon zentral, die Analyse ist insoweit überholt. **P5c-Vorbereitung** (`Vorbereitung-P5c-Protokollierung.md`, Zweig, `8fa3101`) im Schritt-10-Block und in Fahrplanzeile 10c; **V1 am selben Tag entschieden: die Zusage „kein Zugriffsprotokoll" bleibt** (Betriebsereignisse, keine Datenzugriffe); der Schreibweg kommt als erstes Paket ins 10b-Konzept, der Log-Helfer wandert aus Schritt 15 dorthin; V2–V9 bleiben Zuarbeit vor dem 10c-Konzept. Abschnitt 5: 200 und 201 nachgetragen (Fassung 73 hatte sie im Backlog, nicht hier), 202–205 neu — Tabelle 67 Zeilen = 67 offene Punkte — **das galt für Fassung 74.** Mit Fassung 76 sind **12 der 67 erledigt** und als solche gekennzeichnet (nachgezählt gegen den Erledigt-Teil des Backlogs, 16.09.2026): 55 offen. Sie bleiben vorerst in der Tabelle stehen, weil P5a noch nicht auf `main` ist; herausgenommen werden sie nach dem Merge, wie es Fassung 32 für dreizehn Nummern vorgemacht hat. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **73** | **15.09.2026** | **Konzept P5a freigegeben.** Ohne Änderungen; F-P5a-1 wird E-P5a-22 (Tag `web-vX.Y.Z` gleich `WEB_VERSION`). Fahrplanzeile 10a und Kopf auf „freigegeben — Umsetzung kann beginnen". Backlog: **Nr. 200** (Bounce-Postfach, aus PP-7 Empfohlen) und **Nr. 201** (`Retry-After` in Uhr und Handy) angelegt; Nr. 8, 17, 49, 54, 67, 80, 195 tragen ihre Zuordnung zu P5a-Paketen. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **72** | **15.09.2026** | **Konzept P5a liegt vor.** `docs/konzepte/Konzept-P5a-Kette-und-Fundament.md` (Fable) — Befund an `7f334cb` nachgemessen, E-P5a-01 bis -09 aus dem Gespräch vom 15.09.2026 (Mengenbremse ja; Aussperrung nach Schlüsselwechsel hinnehmbar; Sicherungsziel Anzeige plus Option; **eine Sperrleiter 10/20/30/60 min** für Konto und IP mit 24-h-Rückfall; global **Verlangsamung** 1/2/4/8 s statt Sperre; Hinweise mit Countdown; Sammelmail bei Stufe 4; Unterseite Status → Sicherheit; Betriebsdaten 30 Tage fest), E-P5a-10 bis -21 aus dem Nachmessen (Kette in zwei Läufen mit Backup-Tor über `jobs.php?aktion=…`, Prüftor Stufen 1 und 2, Warteschlange mit synchronem erstem Versuch, Kopfzeilen nach SP-5 mit Report-Only, HTTPS-Zwang, Proxy-Liste, 503-Weg, Plattformprüfung als Funktion, Torwächter mit Katalog-Hash, Nachlöse-Job). Zwölf Arbeitspakete, ein Fable-Schritt (M-P5a-01), eine offene Frage F-P5a-1 (Tag-Muster). **Zwei Zahlen der Vorbereitung berichtigt** (E-P5a-11: DB-Kontingent-Schwellen 70/90 wie Webspace, Komplett-Aufbewahrung Vorgabe 2). Register: R19 „entschieden", R37 „(8) und (9) in P5a". Abschnitt 5: Nr. 37 geteilt. Abschnitt 6: Zuarbeiten vor AP1 (GitHub-Umgebungen im jetzigen Repositorium, Zweigschutz, Prüfkonto). Fahrplanzeile 10a auf „Konzept zur Freigabe". Gemessen und **nicht** nötig: Uhr und Handy behandeln `429` schon als „später erneut" — keine Client-Stufe. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **71** | **15.09.2026** | **P5 in drei Teilen — R82.** Der Auftraggeber hat den Schnitt von Schritt 10 entschieden: **10a Kette und Fundament**, **10b Konto und Registrierung**, **10c Rollen, Sicherheit und Betriebslage**, in dieser Reihenfolge, je eigenes Konzept nach K1. Die Fahrplanzeile 10 ist durch drei Zeilen 10a bis 10c ersetzt, die den Zuschnitt samt Backlog-Nummern tragen; der Schritt-10-Block bekommt den Schnitt als Vorspann und beschreibt P5 weiter als Ganzes; Abschnitt 5 ordnet die 15 P5-Punkte den Teilen zu (8, 17, 37, 49, 54, 67, 195 → 10a; 48 → 10b; 122, 141, 168, 169, 191, 192 → 10c; 80 geteilt: Nachlöse-Job 10a, Herkunft und Dashboard 10c). Kopf nachgezogen: Als Nächstes das Konzept zu 10a. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **70** | **15.09.2026** | **Staging-Ziel festgelegt, F-PP-2 entschieden.** Der Auftraggeber nennt `staging.nadoku.gen-em.org` beim selben Hoster im selben Tarif und `staging@gen-em.org` als Absender; die Vorbereitung trägt es als **E-PP-09** (PP-9) mit Betreff-Präfix „[Staging]" als Einstellung und der Regel, dass die `deploy.yml`-Umstellung das erste Code-Paket von P5 ist. Staging bekommt ein eigenes SFTP-Backup-Ziel (zugesagt); offen bleibt die Einrichtung (Abschnitt 6, Zeile nachgezogen). **F-PP-2** ist auf Rückfrage anders entschieden als in Fassung 69 gesetzt: kein Knopf, sondern ein **Job mit Hash-Auslöser**, der Nr. 80 von selbst nachlöst — mit Cron binnen Minuten, ohne Cron Huckepack; E-PP-06 umgeschrieben, Abschnitt-3-Tabelle der Vorbereitung um drei Zeilen ergänzt. Kopf, Fahrplanzeile 10 und Schritt-10-Block nachgezogen. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **69** | **15.09.2026** | **Hosting-Entscheidung gefallen — R81, Plattformprofil in zwei Stufen.** Der Auftraggeber hat entschieden: Der Betrieb bleibt beim jetzigen Hoster, die Anwendung wird nicht auf ihn zugeschnitten. Die fünf Punkte der R36-Zuarbeit (Cron/SSH, DB-Kontingent, `max_user_connections`, DDoS-Schutz, Verschlüsselung at rest) sind hosterneutral beantwortet in `docs/konzepte/Vorbereitung-P5-Plattformprofil.md` (Fable): neun Eckdaten PP-1 bis PP-9 als **Muss** und **Empfohlen**, acht Festlegungen E-PP-01 bis -08, eine Prüfliste für `install.php` und Status, eine Tabelle dessen, was ins P5-Konzept geht, und vier Festlegungen zum Gegenlesen (F-PP-1 bis -4: PHP-Untergrenze 8.2, Browser-Weg für Nr. 80, vertrauenswürdige Proxys in P5, Platzwarnung gegen das größte Komplett-Backup). Neu **R81** im Register; Fahrplanzeile 10 auf „Konzeptarbeit läuft", Schritt-10-Block und Kopf nachgezogen, Abschnitt-6-Zeile zur Hosting-Entscheidung gestrichen mit Verweis. Das Staging-Ziel bleibt offen. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **68** | **15.09.2026** | **Schritt 9d ist gemergt — und der Kopf sagte es nicht.** PR #48 ist am 15.09.2026 als `7f334cb` auf `main` gegangen; Fassung 67 maß `main` noch bei `99d318a` / Web 20.2.1 und führte 9d als „steht zum Merge". Nachgemessen steht dort **Web 20.3.0**, Uhr 3.1.0 und Android 0.15.0 unverändert, keine Migration. Berichtigt sind die Standzeile, der Absatz zum anstehenden Paket, die Liste der jüngsten Merges (sie endete bei PR #44 und kannte weder S10 noch #46, #47 und #48), die Fahrplanzeile 9d und die beiden Abschnitt-6-Zeilen zu 9d (jetzt **fällig** statt „nach dem Merge"). **In Abschnitt 8 standen drei Erledigt-Zeilen auf „Merge: steht an"** — 9d, aber auch **Backlog-Runde 3** (gemergt 13.09., PR #43) und **S10** (gemergt 14.09., PR #45): Die Fassungen 60 und 64 hatten den Merge im Kopf und im Fahrplan eingetragen, die Merge-Zeile am Ende der Erledigt-Zeile aber nicht; alle drei sind berichtigt. **Dazu ein Widerspruch im Kopf, den vier Fassungen mitgetragen haben:** Der Absatz „Als Nächstes läuft nur noch eines: der Schritt 9b" stammt aus Fassung 60 und war dort richtig; seit Fassung 64, die den Merge von S10 eintrug, stand er drei Zeilen unter dem Satz, S10 sei gemergt — und blieb in 65, 66 und 67 unberührt, weil jede dieser Fassungen nur die Zeile berichtigte, die ihr Anlass war. Er ist ersetzt: Als Nächstes steht **Schritt 10, das P5-Konzept**, mit den beiden Zuarbeiten Hosting-Entscheidung und Staging davor (Abschnitt 6). **Das ist die sechste Runde dieser Art** (Fassungen 39, 41, 42, 60, 64, 68); die Ursache aus Fassung 42 gilt unverändert — diesmal lag der Merge zwischen dem Ende der Umsetzungssitzung und dem Beginn der nächsten. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **67** | **15.09.2026** | **Schritt 9d (Demo-Ausbau) gebaut — Web 20.3.0 — und mit `main` zusammengeführt.** Neue Fahrplanzeile **9d** nach 9c, Erledigt-Zeile in Abschnitt 8 mit allen Prüfzahlen, **R80** im Register (ob ein Rettungsmittel Fähigkeiten führen darf, entscheidet sein Typ mit — schränkt E29 ein), eine Zeile in der Sperrtabelle (Abschnitt 4) und **drei** in Abschnitt 6: die OSRM-Zuarbeit ist gegenstandslos geworden (der Host antwortete der Umsetzungssitzung), der Demo-Reset nach dem Merge ist neu, und die Prüfliste mit der Freigabe. **R4** trägt jetzt beide Stände (P1: 16/87 · seit 9d: 21/103). **Diese Fassung hieß auf dem Zweig zuerst 66, und die Backlog-Punkte hießen 190 und 191** — dieselben drei Nummern hatte der R42-Nachlauf (PR #47, `99d318a`) bereits auf `main`. Bemerkt beim Zusammenführen, nicht danach: Der Auto-Merge hatte `docs/Backlog.md` **ohne Konflikt** zusammengesetzt und dabei mains Nummern 190–196 in den **Erledigt**-Abschnitt gelegt und 190/191 doppelt vergeben. Nachgezogen sind Fassung **67**, Backlog **197** (erledigt) und **198** (offen), an allen Fundstellen in Code und Dokumentation (`api/range.php`, `version.php`, Changelog, Technik, Konzept, Prüfdokument). **Dabei mitgenommen: die zweite Hälfte von Nr. 189** aus dem R42-Nachlauf — der Kommentar an `geraet_modell` nannte **156** Zeichen, nachgemessen an `GERAETE_MODELLE` sind es **153** (154 Bytes); berichtigt in `server/schema.sql`, Abschnitt 3 (Schritt 2) und `Konzept-R64-Herkunft-Geraet.md`, Protokollzeilen unberührt. Nr. 189 ist damit **ganz** erledigt und steht nicht mehr in Abschnitt 5. Selbstprüfzahl **61 = 61**, mit `diff` gegengeprüft (0 Zeilen Unterschied) — 60 waren es nach dem Merge, die 61. ist **Nr. 199** aus der Gegenlesung. **Der zusammengeführte Stand ist unabhängig gegengelesen worden** (fünf Blickwinkel, jeder Befund adversarisch geprüft): 21 gemeldet, 15 bestätigt, 6 widerlegt; elf davon gehörten diesem Paket und sind behoben, drei sind älter und stehen als Nr. 196 (erweitert) und Nr. 199 (neu) im Backlog. **Code:** eine Versionsstufe aus AP0 (Web 20.3.0, noch nicht ausgeliefert — die beiden Kommentarberichtigungen zu Nr. 189 laufen in ihr mit); alles Weitere liegt in `tools/`, `docs/`, `server/demo/fixture.json.gz` und **zwei Zahlenkommentaren** in `server/assets/missiontable.js` und `server/betrieb_statistik.php` (AP4, ohne Wirkung auf Verhalten). **Keine Migration** — `update.php` ist nach dem Merge *nicht* fällig; fällig ist ein **Reset des Demo-Kontos**, sonst zeigt es bis zu 30 Minuten den alten Bestand |
| **66** | **14.09.2026** | **Bestandsaufnahme zu R42 — sechs Backlog-Punkte, zwei Nachträge, eine Zuarbeit, kein Code.** Anlass war der Auftrag „R42 umsetzen". **Befund: Der Inhalt von R42 ist gebaut** — der Volltext (Archiv, R42) verlangt unter „Auswertung" eine Geräteverteilung je Kategorie und je Bezeichnung, über echte Geräte, ohne `manual-%`, ohne Demo-Konto, und das steht seit **S8/AP4** in `betrieb_statistik.php`. Aber: **an einem anderen Ort** als beauftragt (R42 nennt das Betriebslage-Dashboard, und dessen Minimalumfang ist eine **R38**-Zusage), **ohne die Vorbedingung aus Nr. 80** (die Datenschutzerklärung der Installation liegt in der Tabelle `rechtstexte` und ist von hier aus nicht einsehbar) und **mit dem Vorbehalt aus Nr. 190** (eine der beiden Abfragen hält die `manual-%`-Regel nicht ein). Die Herkunft je Einsatz ist **R64**, nicht R42. Neu: **190** (virtuelles Gerät in „Ohne Gerät"), **191** (fehlender Index auf `missions(started_at)`), **192** (R38 gegen die S8-Seite: „aktiv", Fenster, Zählgröße), **193** (Register und Doku führen die Auswertung als offen — zweiter Beleg für Nr. 188), **194** (Handbuch nennt den Verschlüsselungsumfang dreimal ohne die Notizen, darunter der Textbaustein für die Datenschutzerklärung), **195** (`geraet_art` auf dem Sicherungs-Rückweg ungeprüft). Nachgetragen: **Nr. 189** bekommt den zweiten Kommentarfehler derselben Datei (156 gegen gemessene 153 — kein Zahlendreher, sondern ein Rest aus der Zeit vor Web 12.9.2); **Nr. 80** drei Befunde (Wortlautkonflikt der Datenschutz-Vorbedingung, `nachaufloesen.php` beim S8-Deploy durchgerutscht, User-Agent-Hälfte gegen R36). In Abschnitt 6 die Zuarbeit zum Nachauflösen und eine Berichtigung: Die Trennen-Mail nennt **keine** Gerätebezeichnung. Zeilen in Abschnitt 5 und offene Backlog-Punkte nachgezählt: **60 = 60**. **Gemessen an `origin/main` (Regel aus Fassung 42): `98d677d`, Web 20.2.1, Uhr 3.1.0, Android 0.15.0.** **Diese Fassung hieß zuerst 63:** Sie entstand gegen `965ec11`, während PR #46 die Fassungen 63–65 und die Backlog-Nummern 188 und 189 vergab. Der Zweig ist auf `main` nachgezogen und die fünf Punkte auf 190 ff. umnummeriert worden, bevor etwas committet wurde — Nr. 177 hätte sonst eine siebte doppelte Fassungsnummer bekommen. **Nach dem Gegenlesen berichtigt** (15.09.2026, vor dem Merge): Nr. 194 nannte Handbuch **11.5** als die Stelle, die es weiß — es ist **4.3**, und dort steht es viermal (11.5 ist ausgerechnet das Kapitel mit dem fehlerhaften Textbaustein); Nr. 193 zählte vier Gegenstellen statt **fünf** (die Kopfzeile von Nr. 80 sagt selbst „ausgewertet ist nichts"); Nr. 190 berief sich auf einen Kommentar in `db.php`, der nichts dergleichen sagt; zwei Zitate in Nr. 80 waren nicht wörtlich; die Zuarbeit in Abschnitt 6 widersprach der Zeile unter ihr, und die Zeile zu den Kopplungs-Mails schrieb der Mail einen **Hersteller** zu, den die Anwendung nirgends speichert. Neu dabei: **196** — mit cmark-gfm gemessen rendern **65 von 192** Backlog-Einträgen auf GitHub als Codeblock, vier davon verlieren eine Tabelle. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **65** | **14.09.2026** | **Nr. 189 angelegt** (S10-Nachlauf): `server/schema.sql` Zeile 528 nennt `docs/Konzept-S2` — ein Verzeichnis von vor der Neuordnung am 02.09.2026 (`781e624`). Der Zwillingskommentar in `migration_lib.php:1770` trägt denselben Beleg F-S2-G und ist damals nachgezogen worden; einer von zweien blieb stehen. **Bewusst nicht im Nachlauf mitgemacht** — die Datei liegt unter `server/`, das verlangt eine Versionsstufe und löst beim Merge einen Deploy aus, und dafür ist ein Kommentar ohne Wirkung auf Verhalten oder Schema der falsche Anlass; entschieden vom Auftraggeber am 14.09.2026. Selbstprüfzahl **53 = 53**. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **64** | **14.09.2026** | **S10 ist gemergt — und der Kopf sagte es wieder nicht.** PR #45 ist am 14.09.2026 als `965ec11` auf `main` gegangen, während die Sitzung, die dieses Dokument schreibt, noch daran arbeitete. Der Kopf nannte `main` weiterhin bei `3886e26` / Web 19.6.0 und führte S10 als „zum Merge stehend“; nachgemessen steht dort **Web 20.2.1**. Berichtigt sind die Standzeile, die Fahrplanzeile 9b und die Abschnitt-6-Zeile zur Prüfliste (jetzt **fällig** statt „nach dem Merge“). **`update.php` ist nicht fällig** — am Umfang des Merges nachgemessen: keine Datei unter `migrationen/`, weder `update.php` noch `schema.sql` angefasst. **Das ist die fünfte Runde dieser Art** (Fassungen 39, 41, 42, 60, 64), und die Ursache ist unverändert die aus Fassung 42: Der Merge geschieht außerhalb der Sitzung, die das Dokument schreibt — diesmal sogar *während* ihrer Arbeit. Das zeigt, dass ein Nachlesen am Anfang der Sitzung nicht genügt; es gehört ans Ende. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **63** | **14.09.2026** | **Tote Verweise nachgezogen — und zwei eigene Zahlenfehler berichtigt.** Der Anlass war klein (drei Konzeptnamen ohne Datei), der Befund nicht: **Die Fahrplanzeile zu Schritt 7 widersprach sich acht Tage lang selbst** — Konzeptspalte „liegt vor" samt Pfad, Statusspalte zwei Spalten weiter „Konzept nach R62 gelöscht". Berichtigt sind jetzt die Fahrplanzeile 7 und der Fließtext zu Schritt 7 (beide nach dem Muster von S7: „gelöscht nach R62", Historie `fc470b0`), dazu die Konzeptspalte zu Schritt 12, die einen Dateinamen nannte, **ohne zu sagen, dass es die Datei noch gar nicht gibt** (`Review-R17.md` entsteht erst im Review — geprüft: `git log --all --full-history` liefert null Zeilen). **Zwei Zahlen aus Fassung 61 waren falsch, beide von der Umsetzung selbst geschrieben:** Abschnitt 8 sagte „dreiundzwanzig am Prüfstand" und das S10-Prüfdokument „die dreiundzwanzig Fehlerfunde" — nachgezählt am Konzeptstand unter `a00f6b5` sind es **47** Funde, davon **28** mit einem `tools/`-Pfad als Ort; die 23 hatte keine Grundlage. Ebenso berichtigt: der Statusblock des Prüfdokuments stand noch vor der Freigabe, und **`docs/Backlog.md` sagte „Konzept S10 liegt vor"** — ohne Backticks, weshalb keine Suche nach `docs/konzepte/…` sie fand. **Neu: Nr. 188** — kein Prüfmittel misst Verweise zwischen Dokumenten, und genau deshalb konnten die S8-Verweise acht Tage neben lauter grünen Zahlen falsch stehen. Selbstprüfzahl **52 = 52**, mit `comm` gegengeprüft (0/0). **Kein Code** — nur `docs/`, keine Versionsstufe. *Unberührt geblieben und begründet:* der Kopf des S7-Prüfdokuments (er sagt bereits „gelöscht" samt Commit — eine richtige Stelle wird nicht umformuliert) und `server/schema.sql:528`, dessen Pfadangabe zwar veraltet ist, aber unter `server/` liegt und damit eine Versionsstufe und einen Deploy auslösen würde |
| **62** | **14.09.2026** | **Freigabe des S10-Abschlusses erteilt — das Konzept ist gelöscht.** `docs/konzepte/Konzept-S10-Sicherheit.md` ist nach K9 aus dem Repositorium entfernt; die Git-Historie behält es vollständig (`git show a00f6b5:docs/konzepte/Konzept-S10-Sicherheit.md`). **Das Prüfdokument bleibt**, bis seine Prüfliste P-01 bis P-14 abgehakt ist, und sagt an seinem Kopf, wo das Konzept geblieben ist — denn ein Prüfdokument, das auf eine gelöschte Datei verweist, schickt ins Leere. Dieselbe Berichtigung an den **vier** übrigen Fundstellen im Rahmenplan (Fahrplanzeile 9b — jetzt „gelöscht nach R62" nach dem Muster von S7 —, Schritt 9b, Abschnitt 6, Erledigt-Zeile in Abschnitt 8); die Fassung-60-Zeile in diesem Abschnitt nennt den Dateinamen weiter, weil sie beschreibt, was damals geschah. **Kein Code** — nur `docs/`, keine Versionsstufe |
| **61** | **14.09.2026** | **Schritt 9b (S10 Sicherheit) abgeschlossen — Web 19.7.0 bis 20.2.1.** Erledigt-Zeile in Abschnitt 8 mit allen Prüfzahlen, Fahrplanzeile 9b auf gebaut, Kopf nachgezogen (S10 steht zum Merge), zwei Zeilen nach Abschnitt 6 (Prüfliste P-01 bis P-14 und die Freigabe des Abschlusses), Abschnitt 5 berichtigt. **Kein Code** — AP6 fasst nur `docs/` an und stuft nichts hoch. |

**Die Selbstprüfzahl stand auf „51 = 51" und war trotzdem falsch** — durch **zwei** Fehler, die sich aufhoben: Abschnitt 5 führte **Nr. 139** noch als offen (seit AP4 unter *Erledigt* im Backlog) und **Nr. 187** gar nicht (dort seit AP3 aufgenommen). Beide Listen zählten 51, und keine zwei davon waren dieselben 51. Genau davor warnt der Kasten über der Tabelle: Er verlangt **nachrechnen**, nicht vergleichen — und der Einzeiler, den Fassung 46 dort hinterlegt hat, zählt nur. Gegengeprüft mit `comm`: **0 Nummern Unterschied** in beide Richtungen. Nr. 140 trägt jetzt außerdem die richtige Zuordnung (**R40 (2)**, nicht S10). Dazu drei Nachträge, die S10 offen gelassen hatte: `docs/Technik.md` 4.97c beschreibt jetzt, dass **`ftp` abgeschafft** ist und was mit einem bestehenden Ziel geschieht (es wird *übergangen*, nicht gelöscht) statt es weiter als Protokoll zu führen; drei Zahlen im Verzeichnisbaum und in 4.97c berichtigt (Versandprobe 115 → **116**, Komplettprobe 76 → **72**, „drei Protokolle" → zwei); und **Backlog Nr. 140** trägt den Vermerk, dass das Deploy-Tor nie S10-Arbeit war (R78 (7) sagt es ausdrücklich) — der offene Rest hängt allein an R40 (2). *Was S10 an Nr. 140 trotzdem verändert hat: Der Angriff „wer pushen darf, liefert Code aus" wiegt seither schwerer, weil in `config.php` zusätzlich der Server-Anteil liegt* |
| **60** | **14.09.2026** | **Schritt 9b (S10 Sicherheit) begonnen — Konzept abgelegt, Buchführung nachgezogen.** `docs/konzepte/Konzept-S10-Sicherheit.md` und sein Prüfdokument liegen im Repositorium; die Einträge aus Konzept-Abschnitt 8 sind in die **heute gültige** Fassung eingepflegt (Fahrplanzeile 9b, der Block „Schritt 9b" mit den fünf Entscheidungen und der Abgrenzung, **fünf** Zeilen in Abschnitt 6, Vermerke an Backlog 46, 139, 155). **Und der Kopf war wieder falsch:** Fassung 59 führte die Mockup-Runde 9c als „zum Merge stehend" und nannte `main` bei Web 19.3.1 — gemessen an `origin/main` steht dort **`3886e26`, Web 19.6.0**, 9c ist als PR #44 gemergt. Das ist die vierte Runde dieser Art (Fassungen 39, 41, 42, 60), und die Ursache ist unverändert die aus Fassung 42: Der Merge geschieht außerhalb der Sitzung, die das Dokument schreibt. **Kein Code, keine Versionsstufe** — bis auf eine `tools/`-Änderung ohne Stufe: `tools/containeraufbau/` zieht die vier WebKit-Bibliotheken nach und **misst nach**, dass alle drei Engines starten (`3 von 3`; vorher startete WebKit in einem frischen Container nicht, und ein Dreimotorenlauf wäre stillschweigend ein Zweimotorenlauf gewesen) |
| **59** | **14.09.2026** | **Mockup-Runde 9c abgeschlossen (Web 19.6.0).** Erledigt-Zeile in Abschnitt 8 mit allen Pruefzahlen, Schritt 9c auf erledigt, die vier Backlog-Zeilen aus Abschnitt 5 ausgetragen (Selbstpruefzahl **51 = 51**), zwei Zeilen nach Abschnitt 6 (Pruefliste und Freigabe des Abschlusses). Kopf gegengelesen und **gemessen**: `origin/main` steht auf `8f1712c` mit **Web 19.3.1**, die Backlog-Runde 3 ist gemergt — der Kopf nannte sie noch als ausstehend. Die Oeffnerzahl in der 9c-Zeile ist berichtigt: **vier Bauarten**, nicht zwei Bausteine |
| **58** | **14.09.2026** | **AP4 der Mockup-Runde gebaut (Web 19.6.0, Backlog Nr. 124).** Das Aktionsblatt faehrt von unten auf statt dazustehen, und der Oeffner traegt `--orange-hell` mit `--orange-tief`, solange sein Blatt offen ist (Fassung D4). Die Markierung haengt am **Attribut** `[data-blatt][aria-expanded="true"]` und erreicht damit alle vier Bauarten von Oeffnern — auch die naechste. Am Schreibtisch faehrt ausdruecklich nichts; `blatt.js` fragt dafuer die **gerechnete** Fahrtdauer, statt eine zu kennen. `--dauer` steht seither auf **240 ms** fuer die ganze Anwendung (E-MR-22). Gemessen: drei neue Klickprobe-Wege, 3/3 in allen drei Motoren; Stilvergleich 46 202 Elementmessungen / 390 Abweichungen, saemtlich erklaert; Bilderlauf 360 Bilder 0/0/0. **Nachgezogen, was AP2 und AP3 offen gelassen hatten:** die vier erzeugten Tabellen in `Design.md` standen auf einem alten Stand — `tabellen.py` schreibt nichts, es gibt nur aus |
| **57** | **14.09.2026** | **AP3b: die Pruefmittel fahren drei Engines (Web 19.5.1, Backlog Nr. 183 ganz erledigt).** `tools/motor.mjs` haelt Motorwahl und Firefox-Voreinstellung an einer Stelle; Bilderlauf, Klickprobe und Stilvergleich kennen `--motor`. Die Staffelung ist gemessen begruendet und steht in `docs/Technik.md`. **Zwei Befunde aus dem allerersten dreifachen Lauf**, beide erledigt: **Nr. 185** (`import.php` lief bei 360 px nur in WebKit um 6 px ueber — WebKit rechnet den laengsten `<select>`-Eintrag in den Ueberlauf) und **Nr. 186** (die Klickprobe mass Drehungen mit `getScreenCTM()`, das in WebKit die CSS-Transformation eines HTML-Vorfahren nicht enthaelt — ein Fehler des Pruefmittels, der wie einer der Anwendung aussah). **Ein Satz zurueckgenommen:** Firefox meldet die `latin-ext`-Schriften NICHT als Konsolenfehler; die Abbrueche stammten von einem Messskript |
| **56** | **14.09.2026** | **AP3 der Mockup-Runde gebaut (Web 19.5.0, Backlog Nr. 45).** Die Karte der Tagesuebersicht bekommt einen Zwischenzustand zwischen 300 px und Vollbild: bis 1599 px wird sie hoeher (`min(60vh, 520px)`), ab 1600 px — wo sie ohnehin hoch in einer eigenen Spalte steht — stattdessen **breit**, dann faellt das Raster auf eine Spalte. Dieselbe Klasse traegt beides, damit die Schwelle 1600 an EINER Stelle bleibt; aus demselben Grund traegt der Knopf beide Zeichen und das Stylesheet blendet je Breite eines aus. Die Wahl bleibt erhalten (erster `localStorage` der Anwendung, je Browser). **In drei Engines gemessen**, fuenf Breiten je Engine: gross ueberall 520 px, ab 1600 px volle Inhaltsbreite, Symbol wechselt, `aria-pressed` folgt, 0 Ueberlauf, keine Konsolenfehler, nach Neuladen wieder gross; Kacheln 10 → 15, 0 px unbedeckt. Voller Bilderlauf 360 Bilder, 0/0/0. Neu: Token `--karte-gross`, Klasse `.geo-gross`, zwei Symbole (54./55.). Abschnitt 5: eine Zeile raus, Selbstpruefzahl **53 = 53** |
| **55** | **14.09.2026** | **AP2 der Mockup-Runde gebaut (Web 19.4.2, Backlog Nr. 42) und der Pruefstand auf drei Engines gebracht.** Der Entfernen-Knopf im Chip traegt jetzt ein Symbol in einem **28-px-Ziel** statt eines Malzeichens mit 17 x 15 px Trefferflaeche; das Warnzeichen im Satz ist dasselbe Symbol wie die Marke in der Tabelle. **Eine Ausnahme weniger, nicht eine mehr:** Der `x`-Rueckfall in `wegKnopf()` sollte laut Konzept als begruendete Ausnahme bleiben — sein Vermerk war falsch, der Zweig seit jeher tot (nachgemessen: 8 von 8 Knoepfen tragen ein SVG), und er ist fort. **Nr. 183 ist zur Haelfte erledigt:** Nach der Freigabe der beiden Downloadadressen liegen Chromium 141, Firefox 142 und WebKit 26 im Pruefstand; Nr. 182 und Nr. 42 sind in allen dreien gemessen und stimmen ueberein. Offen bleibt das Mittel, das sie benutzt. **Nr. 184 neu:** Der Kommentar-Abtaster aus Runde 3 verschluckt in PHP mit HTML rund 800 Zeilen am Stueck — falsche Negative in drei Zusagen-Pruefungen. Deshalb blendet die Symbolpruefung Kommentare NICHT aus: 252 statt geschoenter 108. Selbstpruefzahl **54 = 54** |
| **54** | **14.09.2026** | **Nr. 183 neu: der Pruefstand kennt nur eine Engine** — aufgefallen bei der Gegenprobe zu Nr. 182. Punkt 6 der Mockup-Runden-Pruefliste ist damit zur Haelfte selbst erledigt: **WebKitGTK 2.52.6** aus den Paketquellen (xvfb + python3-gi) misst dieselbe Kopfzeile wie Chromium — 400 px Kopf 342 = Sicht 342, 720 → 654 = 654, 1280 → 1214 = 1214, `container-type` loest auf, alle vier Teile im Sichtfenster. **Gecko fehlt**: Playwrights Downloads sind mit 403 gesperrt, Ubuntus `firefox` ist eine Snap-Huelle. Nur `docs/`, keine Versionsstufe. Selbstpruefzahl **54 = 54** |
| **53** | **14.09.2026** | **Backlog Nr. 182 erledigt (Web 19.4.1), Weg B statt C.** Die Kopfzeile der Importvorschau nimmt ueber `container-type:inline-size` an einer eigenen Klasse `.imp-roll` und `width:100cqi` die **sichtbare** Breite an statt der Tabellenbreite (2653 px) und heftet sich mit `position:sticky;left:0` an den linken Rand. **Kein JavaScript** — die Annahme im Mockup, es brauche eine gemessene Zahl, war falsch: beide Fassungen bei sechs Fensterbreiten auf den Pixel gleich. **Weg C war am 13.09. gewaehlt und ist nach der Kartierung verworfen worden** (fuenf Linsen, 17 Agenten): **58 Befunde, 22 „bricht"**, alle drei Entwuerfe von allen drei Skeptikern widerlegt. Vier Befunde scheitern lautlos — u. a. haette `$('tabelle')` beim Seitenstart die ganze Importseite mitgerissen, und das Auswahlfeld der Tageswahl waere aus dem Tabellenbaum gefallen (sichtbar erst **nach** dem Import). Dazu kann C die dritte Bedingung der eigenen Abnahme (Spaltenflucht) nicht erfuellen. Gemessen: 7 Breiten von 360 bis 1920 px, Datum/Besatzung/Plakette/Auswahl in **jeder** im Sichtfenster, 0 waagerechter Ueberlauf, keine Konsolenfehler; drei Bedienwege am delegierten Behandler nachgefahren. Abschnitt 5: eine Zeile raus, Selbstpruefzahl **53 = 53** |
| **52** | **14.09.2026** | **Mockup M-MR-05 zu Backlog Nr. 182** (Fehlerfund 2 aus AP1), Freigabefrage **F-MR-14** — nur `docs/`, keine Versionsstufe. **Das Mockup ist gemessen, nicht gezeichnet** (E-MR-26): Die drei Wege sind in die laufende Anwendung eingesetzt und darin fotografiert, weil genau dieser Befund daraus entstand, dass M-MR-01 eine Tabelle mit fuenf Spalten zeigte und die Anwendung vierzehn hat. **Zwei Kosten von Weg (c) wurden erst dadurch sichtbar** und standen im Backlog noch nicht: Die Spalten fluchten messbar nicht mehr (eine von vierzehn weicht schon bei drei Gruppen ab), und der Spaltenkopf wiederholt sich je Gruppe. Gemessen: Kopf bei 400 px sichtbar — (a) nein, (b) ja, (c) ja; Kopfhoehe 400/1280 px — (a) 44/40, (b) 231/122, (c) 233/124; waagerechter Ueberlauf der Seite in allen sechs Messungen **0**, Konsolenfehler **keine**. Empfehlung **B**. Abschnitt 5 unberuehrt, Selbstpruefzahl **54 = 54** |
| **51** | **13.09.2026** | **Mockup-Runde 9c freigegeben und AP1 gebaut** (Web 19.4.0). Die Freigabe vom 13.09.2026 ist in Schritt 9c nachgetragen — sie lag im Lieferpaket auf **Fassung 46** und ist nicht mit uebernommen worden, weil Fassung 47 den Kopf inzwischen neu gefasst hatte; nachgetragen wurde allein die Statusspalte. **AP1 (Backlog Nr. 41):** `imp-daygroup` hat eine Regel, `imp-warn` ist ersatzlos gestrichen und die Warnung eine `.plakette-orange` (M-MR-01 Variante A). Gemessen `[offen]` **2 -> 0**, „ohne Gegenstueck" **52 -> 50**, Befunde **330 -> 326**; im Browser 400/720/1280 px, 0 waagerechter Ueberlauf, keine Konsolenfehler; Wortliste 0/0. **Nr. 41 nach *Erledigt*, Nr. 182 neu** — der Gruppenkopf sitzt in einer 2677 px breiten Zelle und steht damit in jeder Breite ausserhalb des Sichtfensters; nach K4 nicht mitbehoben, weil eine Loesung eine neue Darstellung waere. Abschnitt 5: eine Zeile raus, eine rein, Selbstpruefzahl unveraendert **54 = 54**. **Drei Rueckfragen vor Beginn beantwortet** (E-MR-24, E-MR-25, Fehlerfund 1 ohne eigene Nummer) — die erste hat einen Fehler im Konzept aufgedeckt: Die dort vorgesehene Ausnahmeliste `ausnahmen.md` wird von der Unicode-Pruefung gar nicht gelesen |
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
| **50** | **13.09.2026** | **Die drei Funde des Nachtrags abgearbeitet (AP12, keine Versionsstufe: nur `tools/`).** **Nr. 180** — `tools/kopplungsprobe/rundlauf.mjs` leitet den Sollwert der Knopfhöhe jetzt aus Eingabeart und Breite ab, dieselbe Weiche wie im Bilderlauf; neue Schalter `--finger` und `--breite`, und die Eingabeart wird vor der Messung erneut gesendet (sonst fällt sie nach dem ersten Vollseiten-Abzug zurück). Gemessen **25/0 als Zeigergerät** (36 px) und **25/0 mit `--finger`** (44 px). **Nr. 178** — statt eines Ausdrucks für alle Kanäle jetzt **drei Kanäle, drei Regeln**: `console` mit Text und Fundstelle, `requestfailed` mit der Adresse und `ERR_ABORTED` als einziger Code (dieser Rundlauf navigiert mehrfach), `pageerror` **nie** Rauschen. Belegt mit einer neuen **Selbstprobe** (13 von 13), einer **Mutationsprobe** (sechs Läufe, je 12 von 13), der alten Regel zum Vergleich (7 von 11, **4 verschluckte echte Fehler**) und einer Wegwerfdatei mit HTTP 500 am laufenden Stand: Der 500er **erscheint**, die Kachel **nicht**, der Rundlauf bleibt bei 25/0. **Nr. 179** — dritte Prüfung in Gruppe 5, `fremde Quelle`. Das Muster ist mit Absicht grob: Ein Ausdruck, der nur die Ladekonstrukte kennt, meldete **0** Treffer, während fünf echte Laufzeitquellen im Code standen (Kacheln über `L.tileLayer(...)`, der Adressdienst als PHP-Konstante). Gemeldet wird jede absolute Adresse; die **Ausnahmeliste mit 15 Einträgen ist der Inhalt** und nennt je Eintrag die Art — gewollte Laufzeitquelle, Navigationsziel, XML-Namensraum, Beispieltext. Gemessen **0 Befunde, 15 Ausnahmen, 0 ungenutzt**, Gesamtzahl unverändert **330**; Gegenprobe mit eingeschleuster `cdn.example` genau **1 Befund** und **331**. **Neu: Nr. 181** — eine Content-Security-Policy schickt die Anwendung weiterhin nicht (0 Fundstellen), und das ist eine Festlegung: Sie braucht Ausnahmen für vier Kachelserver und den Adressdienst, dessen Anschrift eine Einstellung ist. Selbstprüfzahl **54 = 54**, Backlog **177 Einträge, 0 verloren, 0 doppelt** |
| **49** | **13.09.2026** | **Nr. 176 behoben, Nr. 177 zurückgestellt — beides auf Anweisung des Auftraggebers (Nachtrag AP11 der Backlog-Runde 3, keine Versionsstufe: nur `tools/` und `docs/`).** **Nr. 176:** Der Rauschfilter des Bilderlaufs prüfte `ERR_CONNECTION_RESET`, `ERR_CONNECTION_CLOSED` und `ERR_ABORTED` gegen den Meldungstext, ohne die Fundstelle anzusehen — ein Abruf auf dem **eigenen** Server fiel damit unter das Kartenrauschen, und der Bericht konnte „0 Konsolenfehler" melden für eine Seite ohne Stylesheet. `istRauschen()` hat jetzt drei getrennte Klassen; die Codeliste greift nur noch, wenn die Fundstelle nicht die eigene Herkunft ist (`URL.origin` gegen `BASIS`). **Zunächst** zwei Entscheidungen bewusst zur lauten Seite: eine Meldung ohne Fundstelle wird gezählt, und der Code wird nur im Wortlaut gesucht — die **dritte** (Klasse 2 verwirft nur einen Statuscode) kam mit der Gegenprüfung, siehe unten. Belegt **zunächst** in drei Richtungen: neue **Selbstprobe** (`--selbstprobe`, damals zehn Fälle) **10 von 10** — diese Zahl ist die, die sich als blind erwies; heute fünfzehn Fälle und **15 von 15**. Dazu dieselben zehn Fälle durch die **alte** Funktion, wörtlich aus `git show origin/main` statt abgeschrieben, **6 von 10**; und am laufenden Browser mit **angehaltenem PHP-Server** zwei Konsolenfehler auf der eigenen Basis (`ERR_EMPTY_RESPONSE`, `ERR_CONNECTION_RESET`), davon verwarf der alte Filter **einen**, der neue **keinen**. Der Abschlusslauf über 45 Seiten meldet mit der neuen Regel unverändert **360 Bilder, 0/0/0** — die Zahl der Runde war richtig, sie war nur nicht belegt. **Dabei eine falsche Aussage berichtigt:** Die LIESMICH behauptete seit P3, gefiltert werde „über die Fundstelle der Meldung, nicht über ihren Wortlaut"; für die drei Codes stimmte das nicht. **Nr. 177** bleibt offen und zurückgestellt — rein dokumentarisch, ohne Wirkung auf Code, Daten oder Oberfläche; nachgemessen sind zwei Zitate betroffener Nummern in anderen Dokumenten und ein älterer Fund vom 09.09.2026 in der R39-Bestandsaufnahme. **Der Nachtrag ist gegengeprüft worden, und das war der wertvollste Teil:** Vier Blickwinkel (Code, Zahlen, Buchführung, Konsistenz) haben **fünf eigene Fehler** gefunden, alle behoben — (1) die Selbstprobe war **blind**: zehn Fälle, die auch bei gelöschter Klasse 1 oder 3 grün blieben, weil jeder verwerfende Fall einen Kachelgastgeber in der URL trug; jetzt **fünfzehn** Fälle und eine **Mutationsprobe** (je eine Klasse herausgenommen, sechs Läufe, **14 von 15** in jedem). (2) **Klasse 2 verwarf Verbindungsabbrüche** auf der Seitenadresse selbst — also genau die drei Codes von Nr. 176; sie greift jetzt nur bei einem Statuscode. (3) Eine **unlesbare** Fundstelle fiel zur stillen Seite, während die **leere** gezählt wurde — gegen den eigenen Grundsatz; `herkunft()` gibt jetzt drei Antworten (`eigen`/`fremd`/`keine`). (4) Zwei **Buchführungszahlen** waren nicht nachgemessen (53 statt 55, 173 statt 175) — und die Berichtigung „55" veraltete noch im selben Nachtrag, weil Nr. 180 dazukam; richtig war **56**, heute nach AP12 **54**. Die Lehre steht im Prüfdokument: Eine gezählte Zahl gehört an **eine** Stelle. (5) Zwei Sätze waren **zu weit gefasst** — die Blindstelle sei von `tools/vollstaendigkeit/` gemessen (falsch, jetzt **Nr. 179**) und `--selbstprobe` laufe „ohne Browser" (sie braucht das Playwright-Modul). **Drei neue Nummern:** **178** (dieselbe Lücke, breiter, in `tools/kopplungsprobe/` — 4 von 8 Fällen falsch, darunter ein 404 und ein 500 auf der eigenen Basis), **179** (die Zusage „keine fremde Quelle zur Laufzeit" hat **kein** Messmittel, und die Anwendung schickt keine CSP) und **180** (die Kopplungsprobe verlangt 44 px, seit Web 15.5.0 gelten zwei Sollwerte — sie ist **seit dem 06.09.2026 rot** und wurde erst heute gefahren). Alle drei nach K4 nicht mitbehoben. Bilderlauf zweimal gemessen, vor und nach den Berichtigungen: beide Male 360 Bilder, 0/0/0. Selbstprüfzahl **56 = 56** |
| **48** | **13.09.2026** | **Backlog-Runde 3 gebaut und geprüft (Schritt 9, Web 19.3.1, Zweig `claude/backlog-runde-3-umsetzung-woqxjm`).** Erste Backlog-Runde mit Konzept — zehn Arbeitspakete, neun Punkte, eine Korrekturstufe; Erledigt-Zeile in Abschnitt 8. **Sieben Punkte erledigt** (91, 94, 117, 47, 58, 173, 174), **zwei mit Vermerk offen** (67 Unterpunkt, 41 drei von fünf Streichungen). **Die neue Prüfgruppe „5 Zusagen" fand beim ersten Lauf einen echten Fehler:** `apk.php` lieferte seine 404-Seite ohne Seitenhülle aus — ohne Doctype, Titel und Stylesheet; behoben, **nicht** auf die Ausnahmeliste gesetzt (H-BR3-1). **Der zweite Haltepunkt hat gehalten, meine erste Diagnose dazu war falsch:** Ich hielt die Anwendung für kaputt (Haken „ohne Standort" seit Web 17.0.0 weg), tatsächlich ist er in Web 16.3.0 **absichtlich** durch den ersten Eintrag der Auswahlliste ersetzt worden; der Fehler saß allein in `einspielen.py`. Serveränderung zurückgenommen. **Zwei neue Nummern aus dem Abschlusspaket: 176** (der Rauschfilter des Bilderlaufs prüft drei Fehlercodes gegen den Meldungstext und verschluckt damit auch lokale Fehler — **3 von 5** gebauten Fällen) **und 177** (Abschnitt 10 dieses Dokuments führt **sechs** Fassungsnummern doppelt: 35, 36, 37, 39, 38, 37 — ein Verweis auf „Fassung 38" ist nicht auflösbar; Umnummerieren ist eine Festlegung, deshalb nicht mitbehoben). **Dazu die Buchführung des Kopfes:** Der Kopf sagt jetzt, dass die Runde zum Merge steht, und nennt sie nicht mehr „seit dem 13.09.2026 konzipiert". Prüfzahlen: Wortliste 0/0, Vollständigkeit 330, Linkprobe 117/0, Wartungsprobe 55/0, Spurprobe 45/0, Kontraste 22/0, Klickprobe 40/40, Kreisläufe 287 687/0 und 9 120/0, Bilderlauf **360 Bilder, 0/0/0** (Gegenprobe: 356 verschiedene — die vier Doppel sind die Schublade ab 1024 px), `php -l` 100/0, Selbstprüfzahl **54 = 54**. Offen: der Merge auf `main` nach Freigabe |
| **47** | **13.09.2026** | **Der Kopf ist auf das zurückgeführt, was Abschnitt 9 ihm zugesteht: Fassungsnummer, gemessener Stand, Fälliges, Leseanleitung.** Gemeldet von Fable für die Zeilen 48–75 („erzählt noch, die S9-Umsetzung laufe und AP4 warte auf Freigabe" — Stand vom 07.09.); ein Audit mit sechs Linsen fand **82 Rohbefunde, 43 nach Zusammenlegung**, davon 13 von 14 gegengeprüften haltend. Der Kopf schrumpft von 98 auf 61 Zeilen (107 auf 70 bis zum Beginn von Abschnitt 1). **Ausgetragen:** die Erzählung der abgeschlossenen Pakete (S9 laufend mit AP1–AP3, 9a als Parallelzweig, die Merge-Rückschau „seit Fassung 25", der Werdegang von 9a mit Commits und Agentenzahlen) — sie steht vollständig in Abschnitt 3, 8 und 10; dazu drei **Fassungsvermerke im Kopf**, die Abschnitt 9 ausdrücklich verbietet (Fassung 16 samt Rückschau auf Fassung 15, Fassung 33, und der Kasten „Berichtigt mit Fassung 32"). **Vier Sachfehler behoben:** „Alle Migrationen sind angewendet" widersprach dreizehn Zeilen weiter oben drei offenen Migrationen; „sobald 15.5.2 auf `main` ist" stellte eine seit dem 06.09. erfüllte Bedingung als künftige dar; der Halbsatz „(„Ausstehende ausführen", Zusammenführen-Knopf)." stand ein zweites Mal ohne Bezugssatz im Absatz (Spur eines misslungenen Edits); und Paket E endete bei **Android 0.10.2**, nicht 0.10.1 (`docs/CHANGELOG.md` führt 0.10.2 vom 03.09.2026, und Abschnitt 8 nennt sie richtig) — die falsche Spanne stand an drei Stellen: im Kopf und zweimal in Abschnitt 3 (Schritt 5 und der Parallelitätsabsatz). **Drei Stellen außerhalb des Kopfes nachgezogen,** alle vom Audit gefunden: Abschnitt 3 führte für S7 zehn Tage nach dem Merge noch „PR gegen `main` offen"; Abschnitt 3 sagte für Schritt 9 „offen ist allein die zweiteilige Prüfliste" und „die übrigen Einzelpunkte sind unangetastet", obwohl zwei Runden zwölf Punkte erledigt hatten; und Abschnitt 6 führte zwei der vier fälligen Prüflisten nicht (9a mit P-1 bis P-12, Backlog-Runde 2 mit sieben) — während der Kopf für „alles Weitere" dorthin verwies. **Abschnitt 8 hat jetzt eine Erledigt-Zeile für Backlog-Runde 2;** ohne sie kamen die Merges **PR #41 und #42 im ganzen Dokument nicht vor** (gemessen: null Fundstellen). Abschnitt 5 unberührt, Selbstprüfzahl bleibt **59 = 59**. Kein Code, keine Versionsstufe |
| **46** | **13.09.2026** | **Backlog-Durchsicht vom 12.09.2026 eingearbeitet** (Protokoll `docs/konzepte/Backlog-Durchsicht-2026-09-12.md`; sechzehn Entscheidungen, alle 61 offenen Punkte gegen Web 19.3.0 · Uhr 3.1.0 · Android 0.15.0 gehalten, am 13.09.2026 nachgemessen). **Drei Austragungen:** Nr. 87 (Erhebung mit R70 beantwortet, stand neben P7 doppelt), Nr. 81 (geschlossen ohne Beleg am Gerät — `roundIcon` seit Android 0.11.1 ausgetragen; Wiederöffnungsbedingung: ein Bildschirmfoto unter 0.11.1 oder neuer), Nr. 88 (verworfen, als Entscheidung unter *Erledigt*; der Verweis in Zeile 80 ist mit heraus). **Zehn Zuordnungen mit Entscheidung:** 57 (Modul gewinnt, Nebenbedingung `cap_gate`, eigenes Paket), 41/42/45/124 (**Mockup-Runde, neuer Schritt 9c** — ab jetzt parallel, vor P5; 124 darf die Runde verlassen), 150 (beide Fragen beantwortet, Changelog rückwirkend), 117 (nicht erheben, Handbuchsatz), 169 (vertagt auf P5), 21 (an P6, Quellliste existiert nicht mehr), 62 (Zuarbeit: neue Logovorlagen, Zeile in Abschnitt 6; `Design.md` 2.5 berichtigt). **Nr. 175 neu** (`edbak_uebersicht()` ohne Aufrufer, Nebenfund). **Zwei Zahlen nachgerechnet:** Selbstprüfzahl **59 = 59** (61 − 3 + 1; der Einzeiler steht jetzt wirklich im Absatz — Fassung 45 hatte ihn angekündigt, nicht hingeschrieben), „nach v1.0" **8** (45 verlässt die Gruppe). Nr. 37: der Backlog-Eintrag ist die führende Fassung, die Zeile hier verweist. Nr. 170 und 58 nach den Gegenprüfungen berichtigt; drei Fahrplan-Nennungen von Nr. 87 auf R70 umgeformt. **Entscheidung 16 (Textpflege):** komplett im selben Zug im Backlog erledigt, kein eigener Block. Kopf: Fassung und `main`-Stand (19.3.0) nachgezogen — der Kopf nannte seit Fassung 43 noch 19.1.1. **Am selben Tag Backlog-Runde 3 konzipiert** (K1, `Konzept-Backlog-Runde-3.md`; Prüfdokument-Vorlage nach K9): neun Punkte in drei Blöcken, Nr. 173/174 als eigener Block, weil die Referenzdateien neu erzeugt werden müssen; Textpflege war mit dieser Fassung bereits erledigt (Entscheidung 16) |
| **45** | **12.09.2026** | **Sieben Zuordnungen berichtigt — vier davon zeigten auf etwas, das es so nicht mehr gibt.** Aufgefallen bei der Frage der Auftraggeberin „Ist S4-Rest nicht schon durch? Nach v1.0 soll doch nichts mehr offen sein" — beides traf zu. **Nr. 53** stand auf „nach v1.0", obwohl **R78 (6)** am 06.09.2026 entschieden hat, dass das Konto-Schlüsselpaar mit **S11** kommt („löst Nr. 53 mit", Fassung 30: „43 und 53 zusammengeführt") und der Backlog-Eintrag seither wörtlich schreibt: „Zuordnung damit S11, nicht mehr ‚nach v1.0‘". Nur die Tabellenzeile hatte es nicht mitbekommen. **Nr. 43** behauptete, über Weg B „entscheidet der R17-Review" — er hat entschieden, ebenfalls mit R78: Weg B ist **S11**, Schritt 12a, und so steht es im Fahrplan und in `CLAUDE.md` 4; offen bleibt allein Weg C. **Nr. 81** trug „S4-Rest" — diese Phase ist seit dem 04.09.2026 gemergt (PR #33), und der Punkt ist keine Codearbeit mehr, sondern die **Zuarbeit**, die der Fahrplan ausdrücklich als ihren Rest nennt: ein Blick auf dem echten S24, weil der Emulator AOSP führt und One UI nicht beantwortet. **Nr. 88** trug den Zusatz „nach S4-Rest", dessen Vorbedingung seit demselben Tag erfüllt ist. **Nr. 71** schließlich stand unter *Offen* und trug seine Entscheidung im Titel — „Regionen mit Unteradmins — **verworfen**, festgehalten"; ausgetragen nach dem Muster von Fassung 41 (Nr. 19, 159, 160), mit dem ausdrücklichen Vermerk, dass er als **Entscheidung** dort steht und nicht als Erledigung. **Die Zahlen danach: 62 → 61 offene Punkte**, Abschnitt 5 und Backlog wieder deckungsgleich (**61 = 61**); „nach v1.0" **11 → 9**, und diese neun sind sämtlich begründete Zurückstellungen mit Messwert oder Bedingung („erst messen", „Bedarf abwarten", „nur wenn Selbsthoster es verlangen") — kein Arbeitsvorrat, sondern ein Entscheidungsprotokoll. **Die Ursache ist dieselbe wie in Fassung 42:** Eine Entscheidung fällt an einer Stelle (hier R78 im Registerabschnitt) und wird in der Zuordnungstabelle nicht nachgezogen. Gegenmittel bleibt, was dort steht — messen statt fortschreiben; dazu jetzt: **wer eine R-Nummer beschließt, die einen Backlog-Punkt verschiebt, ändert dessen Zeile in Abschnitt 5 im selben Zug**. **Zwei Berichtigungen kamen am selben Tag nach**, gefunden von einer Vollständigkeitskritik über alle Punkte: **(6)** Nr. 43 trug außer S11 noch „und Backlog-Runde (Weg C)" — **Weg C ist als eigener Punkt Nr. 138 herausgelöst und mit Web 15.6.0 am 07.09.2026 erledigt**; von den beiden Wegen ist einer gebaut und einer terminiert, der Punkt gehört ganz zu S11. **(7)** Die **Selbstprüfzahl** dieses Abschnitts stand auf „72 = 72" — dem Stand von Fassung 41, und damit durch drei Fassungen überholt. Eine Zahl, die die Deckungsgleichheit belegen soll und selbst veraltet, ist schlimmer als keine: Die nächste Instanz misst gegen sie und meldet eine Lücke, die es nicht gibt. Jetzt **61 = 61**, mit einem Absatz daneben, der das Nachrechnen zur Pflicht jeder Zeilenänderung macht |
| **44** | **12.09.2026** | **Backlog-Runde 2 gebaut (Schritt 9, Web 19.3.0, Zweig `claude/backlog-runde-2`).** Fünf Punkte erledigt (118, 119, 120, 125, 126), einer berichtigt und offen gelassen (57), einer neu (174). **`origin/main` gemessen, nicht fortgeschrieben** (die Regel aus Fassung 42): Es trägt **Web 19.1.2, Uhr 3.1.0** — PR #40 hat Runde 1 **bis 19.1.2** gemergt. **Web 19.2.0 (Nr. 155, die alte Rundenzahl) liegt damit noch NICHT auf `main`** und geht mit diesem Zweig zusammen hinaus; vor dem Merge ist noch einmal zu bestätigen, dass kein Konto im Übergang steht, sonst sperrt es sich aus. **Zwei Zusagen geändert, beide ausdrücklich freigegeben:** Betrieb → Status hatte „genau eine Ausnahme" von „rein lesend" und hat jetzt zwei (Testmail); „Import / Export" beansprucht nicht mehr, alle Datenwege zu führen, sondern nennt die sechs, die anderswo liegen. Dazu **ein neues Zeichen im Symbolvorrat** (`mail.svg`, Tabler „mail", 52 → 53) — eine Kopfaktion ohne Symbol wäre die erste von zwölf gewesen und damit eine neue Darstellung, und `art => 'neutral'` gibt es dort nicht (nur `blau`/`orange`, sonst eine Klasse ohne Regel). **Nr. 57 nicht gebaut, sondern vermessen:** fünf Driften statt einer, zwei Erzeuger und vier Spaltenlisten, ein dreifaches Sortierblatt — und eine Falle, die „nur den Erzeuger zusammenführen, 0 Pixel bewegen sich" widerlegt (die beiden Sortierungen ordnen Gleichstände verschieden). Der Punkt braucht ein Mockup und eine Freigabe. **Nr. 174 neu, und gefunden hat ihn ein Prüfmittel:** Beim Neubau des Referenzbestands in 19.2.0 hatten zwei der sechs Rettungsmittel ihren *leeren* Standort verloren — die einzige Abdeckung eines Falls, den S9/AP4 ausdrücklich erlaubt. Die Klickprobe meldete 39 von 40 Wegen; die Demo-Fixture ist repariert (2 von 6 ohne Standort, Spurpunkte unverändert 55 861), die Referenzdatei der Kreisläufe nicht. Dabei fiel auf, dass die Hintergrundjobs während der Arbeit **8285 Spurpunkte** ausgedünnt hatten — wer eine Fixture erzeugt, hält vorher die Jobs an; der Dateikopf sagt es jetzt. Prüfzahlen: Stilvergleich **68 224** Elementmessungen ohne Abweichung, Kaskade 743 → 742 Regeln mit **0** geänderten Endwerten, Klickprobe **40/40**, Wartungsprobe **55/0**, Linkprobe **117/0**, Wortliste **0/0**, Vollständigkeit **330 → 334** (vier Pfeile in Fließtext), Kontraste **22/0**, Bilderlauf **96** Bilder über sechs Seiten in beiden Bedienhöhen ohne Überlauf. Prüfdokument: `docs/konzepte/Pruefdokument-Backlog-Runde-2.md`, sieben Punkte für die Auftraggeberin |
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
