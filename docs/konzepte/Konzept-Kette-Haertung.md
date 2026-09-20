# Konzept Kette II — Produktivpfad härten

**Rahmenplan:** R60, R66, R67 (E-PV-3), R68, R81; E-P5a-10 bis -22
(Auslieferungskette, Tag-Muster E-P5a-22); **E-PP-09** (Staging-Ziel — wird
hier in Adresse und Hoster ersetzt, E-KH-04); Abschnitt 6a (Staging-Einrichtung);
`CLAUDE.md` 3, 7, 8 (K3, K7). **Backlog:** Nr. 234 (bleibt offen, wird
verwiesen); neue Nummern vergibt die einspielende Instanz.
**Vorbereitung:** Durchsicht der Kette am 20.09.2026 (Befunde **B1–B7**);
erster Produktivlauf am 20.09.2026 (Befunde **F1–F4**, vom Auftraggeber aus
den Laufprotokollen erhoben); Zweitmeinung einer Opus-Instanz zur Zweigfrage
(gleiches Ergebnis).
**Modell:** Konzept Fable, Umsetzung Opus. **Keine Fable-Schritte** in der
Umsetzung (keine Oberfläche, kein Mockup).
**Ablage:** `docs/konzepte/Konzept-Kette-Haertung.md`; Prüfdokument daneben
(`Pruefdokument-Kette-Haertung.md`, entsteht mit AP1).
**Entscheidungskürzel:** `E-KH-NN`. **Kein Versionssprung im Konzept (K3)** —
Versionen vergibt die Umsetzung je Paket nach `CLAUDE.md` 2.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 20.09.2026 — **AP1 umgesetzt** (Dokumentation; kein Web-Code, keine Versionsstufe). Konzept freigegeben (Auftraggeber, 20.09.2026, ohne Änderungen; Fassung nach der ersten Fortschreibung vom selben Tag: F3 neu gefasst, E-KH-20, AP3/AP4 angepasst, Z1 und Z2 erledigt) |
> | Entschieden | **E-KH-01 bis -08 und -10 bis -20** — die Vorschläge aus Abschnitt 2.2 gelten seit der Freigabe. Dazu **E-KH-21 bis -25** aus der Umsetzung (Abschnitt 2.4; -24 außerhalb der Pakete, -25 aus AP3) — **von der Umsetzung entschieden, zur Kenntnis und zum Widerspruch** |
> | Von außen | **Einschub vom 20.09.2026 eingespielt** (Auftrag der Konzeptinstanz): Backlog 241–249 reserviert und angelegt, drei Konzeptdateien eingecheckt, Rahmenplan **Fassung 85**. Dabei angemeldet: `.sitzungen/` als **achter Schutzlistenpfad** (E-KH-20, einzutragen in AP4 oder AP5). **Der Einschub nannte Fassung 84 — die hatte AP2 schon vergeben und gepusht; er ist auf 85 gerückt** |
> | Zuarbeiten erledigt | **Z1** (alte Staging-Anlage stillgelegt — B2 damit geschlossen), **Z2** (Zeiger `produktion` auf `7150793`) und **Z3** — alle 20.09.2026. Z3 **beide Anlagen**, aber auf zwei Wegen: Produktiv aus *Betrieb → Status*, Staging aus einer `phpinfo()`, weil die Anwendung dort noch nicht läuft. Fünf Zeilen der Staging-Spalte bleiben leer |
> | Offen | **E-KH-09 (Ursache und Abhilfe F3)** — fällt nach der Messung am Ende von AP3; der Nachtrag in Abschnitt 1.4 hat eine der drei Erklärungen verschmälert. **Z3-Rest**: die fünf Zeilen, die nur die Anwendung weiß — sie hängen an Rahmenplan 6a, Schritt 6 |
> | In Arbeit | **AP3 — Tor, Zielprobe, Probelauf: gebaut. Sieben Vermutungen zu F3 sind mit je einer Messung ausgeschlossen, zuletzt die Menge: 80 von 80 Verzeichnissen in EINER FTP-Sitzung** (F-KH-U-22, Lauf 35540252565; die Auslieferung legt 62 an). Davor: Läuferabbild und Node (Z5 Teil 1), Zertifikat (F-KH-U-08), die Bibliothek als solche (lima-city grün), TLS-Sitzungswiederverwendung (F-KH-U-16), `MKD`/`CWD` eines neuen Verzeichnisses (F-KH-U-18), Weg zum Datenkanal (F-KH-U-20). **Dann ein Fund aus dem Quelltext, der eine tragende Annahme kippt (F-KH-U-23): `_openDir` listet NICHT** — es sendet `MKD` und `CWD`, beides Steuerkanal (`basic-ftp` 6.2.1, Z. 686–689). Der Stacktrace „Client **is closed** *because* read ECONNRESET" ist eine **Zustandsmeldung**: Der Client war beim `MKD` schon tot, der Reset kam auf der Datenverbindung **davor**. Beide Dokumente sind an der falschen Stelle berichtigt, nicht stehengelassen. **Daraus folgt eine Lage, die nie gemessen wurde:** Die Aktion holt vor dem ersten `MKD` ihre Zustandsdatei (Datenverbindung) — im Probelauf kommt danach kein Steuerbefehl mehr, ein Reset dort wäre also **unsichtbar**. Genau die beobachtete Lage: Probelauf grün, echter Lauf rot. **Gebaut und über die Kette auslösbar: `--sitzungsprobe`** (Abruf, dann `PWD`, eine Sitzung) — Eingabe `probelauf_sitzungsprobe`, nur zusammen mit `probelauf`, schließt `probelauf_mengenprobe` aus. **Prüfpunkt 19, kostet einen Probelauf**, und er geht Prüfpunkt 18 voraus. **Weg A ist beauftragt, aber NICHT gebaut:** Die Änderung an `auslieferung.yml` (`log-level: verbose` plus eine Eingabe für einen echten Lauf ohne Tag) hat der Wächter der Arbeitsumgebung abgewiesen — „Production Deploy". Das ist sachlich richtig erkannt und wird nicht umgangen; es braucht eine Entscheidung der Betreiberin. Nachgelesen und damit belegt ist immerhin beides, was Weg A voraussetzt: `log-level: verbose` gibt es (`deploy.js` Z. 74) und das Passwort steht nicht im Protokoll (`FtpContext.js` Z. 191/192, `> PASS ###`). Selbstproben: Zielprobe **93 Lagen**, Tor **19**, Wache **38**, alle 0 offen. **E-KH-09 steht.** Einzelheiten im Prüfdokument 1.6 und 2 |
> | Hakt | **Es gibt bis heute keinen erfolgreichen Produktivlauf der Kette** (Abschnitt 1.2). **AP1 ist gebaut, aber nicht abgenommen:** Der erste Kettenlauf gegen lima-city (Lauf 21, 20.09.2026, Handlauf vom Arbeitszweig) brachte `staging` **grün** und in Stufe 2 **zwei von fünf** Messschritten gemessen grün — der dritte ist rot, weil `STAGING_KONTO`/`STAGING_PASS` sich auf der neuen Anlage nicht anmelden. **Ein Push auf `main` ist dafür nicht nötig** (`workflow_dispatch` fährt dieselben Jobs). Einzelheiten im Prüfdokument, Abschnitt 1.4 |
>
> **Stand der Umsetzung**
>
> | Paket | Stand | Version | Commit | Abnahmezahlen |
> |---|---|---|---|---|
> | AP3 — Tor, Zielprobe, Probelauf | **gebaut; PFLICHTSTOPP** | keine (nur `tools/`, `.github/`, `docs/`); Rahmenplan **Fassung 86** | siehe Zweig | Selbstprobe `tor.py` **17 Lagen / 0 offen** (vorher 11) · Selbstprobe `zielprobe.py` **26 Lagen / 0 offen** (neu) · Kettenaufrufe **32 Aufrufe / 0 Befunde** · YAML 3 von 3 · Wortliste 0/0/0. **Z5 Teil 1 gemessen:** Läuferabbild und Node in grünem und rotem Lauf **identisch** → als F3-Ursache ausgeschlossen. **NICHT gemessen:** Probelauf gegen Staging, Trennversuch gegen Produktiv (beide an der Anlage) |
> | AP2 — Zeiger und Wache | **gebaut; Abnahme teilweise** | keine (nur `.github/`, `tools/`, `docs/`, `CLAUDE.md` — E-KH-23); gezählt hat der Rahmenplan: **Fassung 84** | siehe Zweig `claude/fervent-dirac-xirsqw` | Selbstprobe der Wache **38 Erwartungen / 0 offen** (vorher 32) · B6 nachgerechnet: alt **128 Dateien, 121 gleich, 1 abweichend, 6 × 404**; neu **122 / 122 / 0 / 0** · Kettenaufrufe **30 Aufrufe / 0 Befunde** · YAML 3 von 3 · Wortliste 0/0/0. **NICHT gemessen:** Handlauf gegen Produktiv (Proxy 403), Zeiger-Job (läuft erst mit M1) |
> | AP1 — Staging-Umzug nachziehen | **gebaut; Abnahme offen** | keine (nur `docs/`, `CLAUDE.md`, ein Kommentar in `.github/`) | `499e96c` ff. (Zweig `claude/fervent-dirac-xirsqw`) | Wortliste **0/0/0** (11 Dateien Bereich c, 444 Treffer, alle erklärt) · Kettenaufrufe **3 Läufe / 28 Aufrufe / 0 Befunde / 0 ungeprüft** · Selbstproben **21/21** und **10/10** · YAML **3 von 3** gültig · alte Adresse: **13 Fundstellen, alle Historie oder als „damals" gekennzeichnet** (vorher 12, davon 5 aktuelle Aussagen). **Lauf 21 gegen lima-city:** `staging` grün (12 Dateien, 1,13 MB, 12 s), Stufe 2 **2 von 5** gemessen grün, 1 rot, 2 nicht gelaufen. **Z3 eingetragen** — Produktiv vollständig, Staging vorläufig (5 Zeilen offen) |
> | AP2 — Zeiger und Wache | offen | | | |
> | AP3 — Tor, Zielprobe, Probelauf; F3-Messung | offen | | | |
> | AP4 — F3 beheben | offen (wartet auf E-KH-09) | | | |
> | AP5 — Gemeinsame Schrittfolge | offen | | | |
> | AP6 — Abbruchverhalten und Härtung | offen | | | |
> | **M1 — erster grüner Produktivlauf** | offen (Betreiberin) | | | |
> | AP7 — Hotfix-Weg | offen | | | |
> | **M2 — Probe-Hotfix** | offen (Betreiberin) | | | |
> | AP8 — Abschluss | offen | | | |
---

## 0. Auftrag und Umfang

**Anlass.** Die Auslieferungskette aus P5a (main → Staging, Tag → Produktiv
mit Pflichtfreigabe) wurde am 20.09.2026 durchgesehen und am selben Tag zum
ersten Mal gegen Produktiv gefahren. Die Durchsicht ergab sieben Befunde
(B1–B7), der erste echte Lauf vier weitere (F1–F4). Gemeinsamer Nenner der
F-Befunde: **Der Pfad `produktion` war nie gefahren worden** — die Selbstprobe
des Tors prüft Logik ohne Netz, und der Staging-Weg schaltet weder Wartung noch
Backup-Tor. Dieselbe Blindheit wie Backlog Nr. 234 und Nr. 223.

**Leitfrage dieses Konzepts:** Wie wird der Produktiv-Pfad geprobt, ohne
Produktiv anzufassen? Antwort in zwei Hälften (E-KH-17): die **Logik** probt
Staging bei jedem Push, weil es dieselbe Schrittfolge fährt (AP5); die
**Plattform** probt ein **Probelauf** gegen Produktiv, der nichts ausliefert
(AP3).

**Was dieses Konzept liefert:** Befund (nachgemessen an `main` `7150793`,
Web 20.24.2, und über die GitHub-API an den Läufen des 20.09.), Entscheidungen
E-KH-01 bis -19, acht Arbeitspakete und zwei Meilensteine mit Abnahme,
Bedienregeln für die Zeit bis zur Umsetzung, Zuarbeiten der Betreiberin,
Einschübe für Rahmenplan und Backlog.

**Was es nicht liefert:**

- **Keinen Umbau des Zweigmodells** und **keinen Pull-Weg** (E-KH-01, -02).
- **Keine Änderung unter `server/`**, soweit heute absehbar: Alle Abhilfen
  liegen in `.github/workflows/`, `tools/kette/`, `tools/integritaetswache/`
  und der Doku — auch bei einem Transportwechsel in AP4.
- **Nr. 234** (kein Prüfmittel fährt „Deploy → Anmeldung → `update.php`"):
  AP5 liefert den Handweg auf Staging; der automatisierte Weg und die
  Grundsatzfrage dort bleiben Nr. 234.
- **Atomare Auslieferung** (Release-Verzeichnis, Umschalten) — Neubewertung
  bei P8 oder Hosterwechsel (Abschnitt 8).
- **Release-Archiv für Selbsthoster** (R66) — bleibt P8.

---

## 1. Befund (20.09.2026)

### 1.1 Die Kette, wie sie gebaut ist

- `auslieferung.yml`: Push auf `main` → Job `staging` (FTPS-Abgleich) → Job
  `stufe2` (Antwortprobe, Punktdateien, Kreisläufe csv/edbak, Bilderlauf,
  Messstand). Tag `web-v*` → Job `produktion`, wartet in der Umgebung
  `produktion` auf die Pflichtfreigabe. Schritte dort, in dieser Reihenfolge:
  Geheimnisse da? → Tag gegen `WEB_VERSION` → grüner Stufe-1- und
  Staging-Lauf auf diesem Commit? → **Backup-Tor** (`tools/kette/tor.py
  backup`) → **Wartung einschalten** → `docs/` nach `server/doku` kopieren →
  **FTPS-Abgleich** (`SamKirkland/FTP-Deploy-Action@v4.4.0`) → **Zustand**
  (Migration ausstehend? sonst Wartung aus).
- **Kein Schritt des Jobs `produktion` trägt ein `if:` oder eine `id`.**
- `tor.py` spricht mit `jobs.php?aktion=…` (`komplett`, `wartung_an`,
  `wartung_aus`, `zustand`, `pause`), eingeführt mit **Web 20.4.0**.
  `zustand` liefert: `version`, `wartung{aktiv,seit,von}`,
  `komplett{datei,zeit,groesse}`, `migration_ausstehend`.
- `integritaet.yml` (Integritätswache): täglich 04:17 UTC (`schedule`), nach
  jeder Auslieferung (`workflow_run`), von Hand. Checkout **ohne `ref`**, also
  immer `main`-HEAD. Für `workflow_run` gibt es einen Riegel (vergleicht nur,
  wenn der Job `produktion` erfolgreich war); **der `schedule`-Lauf geht daran
  vorbei.**

### 1.2 Stand von Produktiv und der Läufe — gemessen über die GitHub-API

| Lauf | Auslöser | Ref | Ende (UTC) | Ergebnis |
|---|---|---|---|---|
| `35341712345` | push | `main` `7150793` | 18.09. 12:08 | **grün** — Staging + Stufe 2, alle Schritte gemessen (noch gegen die alte Staging-Anlage) |
| `35495399925` | push (Tag) | `web-v20.24.2` | 20.09. 08:02 | **rot am Backup-Tor** (F1); Wartung nie eingeschaltet |
| `35499069515` | Handlauf | `main` | 20.09. 08:15 | abgebrochen nach 18 s |
| `35499103100` | Handlauf | `web-v20.24.2` | 20.09. 08:16 | **rot am FTPS-Abgleich** (F3), *nach* „Wartung einschalten" (F2) |
| `35499422433` | Handlauf | `web-v20.24.2` | 20.09. 08:24 | **rot am FTPS-Abgleich**, wieder nach Sekunden, wieder nach „Wartung einschalten" |

- Tag `web-v20.24.2` sitzt auf `7150793`. **Ein erfolgreicher Job `produktion`
  existiert nicht.** Auf Produktiv gibt es folglich keine
  `.deploy-state-produktion.json`.
- Produktiv läuft auf **Web 20.24.2 durch Hand-Upload** der Betreiberin am
  20.09. (E-KH-03); belegt dadurch, dass das Backup-Tor mit `aktion=komplett`
  sprechen konnte. Davor: Web 20.3.0, Commit `7f334cb` (PR #48), letzter
  Stand der alten `deploy.yml`.
- Die vier grünen Wache-Läufe vom 20.09. (`workflow_run`) **haben nichts
  verglichen** — der Riegel überspringt, solange `produktion` nicht
  erfolgreich war. Grün heißt dort „nicht gemessen".
- **Zeiger-Zweig `produktion` besteht seit 20.09. und steht auf `7150793`**
  (nachgemessen, Z2) — gleich mit `main` und dem Tag.
- Nicht messbar von außen: ob die Wartung nach 08:24 von Hand beendet wurde.

### 1.3 Befunde der Durchsicht (B)

| Nr. | Befund | Beleg |
|---|---|---|
| **B1** | **Kein Hotfix-Weg.** Das Tor verlangt einen grünen `auslieferung.yml`-Lauf mit `event == "push"` auf dem Commit; den erzeugt nur ein Push auf `main`. Ein Fix für Produktiv geht nur zusammen mit allem, was auf `main` liegt. „Hotfix" kommt in der Doku nicht vor. | `auslieferung.yml` Z. 656–659 |
| **B2** | **Staging lief unter demselben Systemnutzer wie Produktiv** (selbes Plesk-Abonnement): Staging-PHP konnte Produktivs `config.php` lesen; die Staging-Zugangsdaten (ohne Freigabe) reichten faktisch bis Produktiv. | Auskunft Auftraggeber; Rahmenplan 6a |
| **B3** | Die Fremd-Aktion für den FTPS-Abgleich ist **per Tag gepinnt** (`@v4.4.0`) und bekommt das Produktiv-Passwort. Tags sind verschiebbar. **Gemessen 20.09.:** `v4.4.0` zeigt auf `3a9c65ae0fc12ac924ccef8afc26941cc43aad0b` (19.04.2026), Laufzeit `node24`, `@samkirkland/ftp-deploy ^1.2.5`, `basic-ftp ^5.0.5`; **`v4.4.0` ist das jüngste Tag — eine neuere Fassung gibt es nicht.** | `auslieferung.yml` Z. 167, 694; Klon der Aktion |
| **B4** | `auslieferung.yml` hat **keine `concurrency`-Gruppe**: zwei schnelle Pushes gleichen parallel gegen dieselbe Zustandsdatei ab; Stufe 2 misst womöglich den falschen Stand. | grep: 0 Treffer |
| **B5** | **„Übersprungen" zählt als grün.** Fünf Stellen in `stufe2` enden bei fehlender Zuarbeit mit `exit 0`; das Produktiv-Tor sieht dann einen grünen Lauf, der nichts gemessen hat. | Z. 286, 346, 381, 392, 462 |
| **B6** | **Die Wache vergleicht gegen `main`, nicht gegen das Ausgelieferte.** Seit 17.09. täglich rot (Befund 18.09.: 128 Dateien, 120 gleich, 2 abweichend, 6 × 404 — schlicht die Dateien aus P5a/P5b). Der Riegel vom 16.09. (`e282659`) deckt nur `workflow_run`. | `integritaet.yml`; Wache-Läufe |
| **B7** | Staging-Umzug auf eigenen Webspace — **von der Betreiberin am 20.09. erledigt** (E-KH-04); alte Anlage am 20.09. stillgelegt (Z1); Nacharbeit in der Doku steht aus (AP1). | Auskunft Auftraggeber |

### 1.4 Befunde des ersten Produktivlaufs (F)

**F1 — Das Backup-Tor kann sich selbst aussperren.** `jobs.php`
(`aktion === 'komplett'`) legt nur dann einen Auftrag an, wenn keiner offen
ist, sonst fährt es den bestehenden zu Ende. Der Dateiname — und damit die
Zeit, die `kette_komplett_stand()` meldet — wird **beim Anlegen** vergeben
(`komp_auftrag_starten()`: `'name' => komp_dateiname()`). `tor.py` bricht ab,
wenn `str(stand["zeit"]) < beginn` (Z. 176), wobei `beginn` die **Uhr des
Runners** ist (`jetzt_utc()`, Z. 76–78). Läuft beim Torstart schon ein
Auftrag, ist der Stand zwangsläufig „zu alt"; Wiederholen hilft nicht.
Beleg Lauf `35495399925`: Auftrag 07:58:34 Z, Torstart 08:02:53 Z, Backup in
1,4 s fertig (27 858 Datensätze, 824 283 Byte), Abbruch wegen
`07:58:34 < 08:02:53`.
*Zwei Ergänzungen aus der Durchsicht:* (a) Der naheliegende Auslöser ist ein
**Hand-Backup kurz vor der Freigabe** — das tut jede sorgfältige Betreiberin;
kein Randfall. (b) Dieselbe Zeile birgt eine **zweite, verdeckte Ursache**:
Sie vergleicht zwei Uhren (Server stempelt den Namen, Runner liefert
`beginn`), sekundengenau, ohne Toleranz. Geht die Serveruhr zwei Sekunden
nach, entsteht dieselbe Aussperrung ohne offenen Auftrag.

**F2 — Ein gescheiterter Upload lässt die Anlage zu, ohne es zu sagen.**
„Wartung einschalten" ist Schritt 7; der einzige Schritt, der sie beendet,
ist der letzte. Jeder Fehlschlag dazwischen hinterlässt eine zugesperrte
Anlage; der Lauf endet rot, sagt aber nicht, was er hinterlassen hat. Belegt
zweimal am 20.09. (08:16, 08:24). Die Aktion v4.4.0 hat laut ihrer
`action.yml` **keine Ausgaben und keine Wiederholung** — die Unterscheidung
„nichts geändert / mittendrin" gibt sie nicht her.

**F3 — Der FTPS-Upload auf Produktiv scheitert an der ersten
Datenoperation** (Neufassung des Auftraggebers aus den Laufprotokollen,
20.09.). Reproduzierbar in zwei Läufen: `ECONNRESET (data socket)` bei
`ensureDir('api/')`, nach 2 bzw. 3 s; **es wurde keine Datei übertragen.** Der
Host beherrscht FTPS (Handeinspielen am 20.09. gelungen). Ein falsches
FTP-Ziel ist ausgeschlossen.
*Wahrscheinlichste Ursache (Auftraggeber):* Der Server verlangt auf dem
Datenkanal die **Wiederverwendung der TLS-Sitzung**, und `basic-ftp` in der
Fassung der Aktion liefert sie nicht — möglicherweise erst unter Node 24.
**Zu prüfen, bevor entschieden wird.**
*Was dagegen steht und die Messung klären muss (Durchsicht, gemessen):*
Dieselbe Aktion — **derselbe Tag-Commit vom 19.04.2026, `node24`,
`basic-ftp ^5.0.5`** — lief am 17./18.09. gegen **denselben FTP-Server** (alte
Staging-Anlage im Produktiv-Webspace), auch als Erstabgleich ohne
Zustandsdatei. Die Laufzeit Node 24 ist von der Aktion selbst erklärt, keine
Umleitung zwischen den Läufen. Eine serverweite Forderung hätte also schon
Staging treffen müssen. Offen bleiben damit drei Erklärungen: (1) die
Forderung gilt, und am 17./18.09. war etwas anders (Runner-Abbild,
Serverkonfiguration); (2) der Unterschied liegt am **Konto** (Hauptkonto statt
eingesperrtem Zusatzkonto) oder am Pfad samt Zustandsdatei oberhalb des
Webroots; (3) ein Fehlerpfad der Bibliothek, den nur diese Anordnung trifft.
Die Vermutung „Erstauslieferung = mehr Verbindungsarbeit" ist vom Tisch (es
wurde nichts übertragen). **Ursache offen (E-KH-09); Trennversuch in AP3.**

> **Nachtrag aus AP1 (20.09.2026) — eine der drei Erklärungen ist schmaler
> geworden, gemessen und nicht geschlossen.** Mit Zuarbeit Z3 hat die
> Betreiberin die Einrichtung von Produktiv genannt: Das FTP-Konto ist
> **auf den Webroot eingesperrt**, `FTP_ZIELPFAD` steht auf **`/`**, die
> Wurzel ist `/var/www/vhosts/luftrettung.net/nadoku-produktion`, und
> **`FTP_STATE_PFAD` ist nicht gesetzt** — es greift also die Vorgabe
> `../.deploy-state-produktion.json`, eine Ebene **über** dem Käfig.
>
> Damit fällt die erste Hälfte von Erklärung (2) weg: **„Hauptkonto statt
> eingesperrtem Zusatzkonto" trifft nicht zu** — Produktiv fährt dieselbe
> Bauform wie die alte Staging-Anlage, die am 17./18.09. mit derselben Aktion
> ausgeliefert hat. Die zweite Hälfte bleibt und ist schärfer als vorher:
> **der Pfad samt Zustandsdatei oberhalb des Webroots.** Auf der alten
> Staging-Anlage wurde dasselbe `../` vertragen; ob der Produktiv-Server es
> ebenso hält, ist ungemessen — eine `.deploy-state-produktion.json` gibt es
> dort bis heute nicht.
>
> **Zwei Folgen für AP3, die dort zu entscheiden sind und hier nur stehen:**
> Schritt 4 des Trennversuchs will „die Anordnung nachstellen, die am
> 17./18.09. lief — FTP-Zusatzkonto, eingesperrt auf den Webroot (Z5)". Diese
> Anordnung **besteht auf Produktiv bereits**; der Schritt hätte in seiner
> jetzigen Fassung nichts zu trennen, und die bedingte Zuarbeit in Z5 („nur
> falls der Trennversuch es verlangt: FTP-Zusatzkonto für Produktiv anlegen")
> geht ins Leere. Was an seine Stelle gehört, ist ein Trennversuch über den
> **Pfad**, nicht über das Konto: derselbe Probelauf einmal mit der Vorgabe
> und einmal mit einem `FTP_STATE_PFAD`, der nach innen zeigt. **Das ist ein
> Vorschlag von AP1 an AP3, keine Änderung an AP3.**

**F4 — Die Kette spricht mit mehreren Zielen und prüft nie, ob es dasselbe
ist.** `tor.py` nutzt `vars.PRODUKTION_URL`, der Abgleich
`secrets.FTP_SERVER` + `vars.FTP_ZIELPFAD`, die Wache `vars.WACHE_BASIS`
(mit eingebautem Vorgabewert `https://nadoku.gen-em.org`). `zustand` liefert
keine Kennung. Der `JOBS_TOKEN` bindet nur die HTTPS-Seite an *eine* Anlage;
die FTP-Seite ist an ein *Konto* gebunden. Landet der Abgleich auf einem
funktionierenden System B, fragt die Kette danach A nach dem Zustand
(„keine Migration") und **endet grün**. Dazu: Die Zielpfade haben
Vorgabewerte (`./staging/`, `./httpdocs/`) — eine fehlende Variable führt
still in ein fremdes Verzeichnis.

### 1.5 Staging nach dem Umzug

- Neue Adresse: **`https://staging-nadoku.gen-em.org`**, bei **lima-city**
  (anderer Hoster als Produktiv). Die GitHub-Umgebung `staging` ist laut
  Auftraggeber bereits umgestellt. **Gegen die neue Anlage lief noch kein
  Kettenlauf** (letzter Staging-Lauf 18.09.).
- Die alte Adresse `staging.nadoku.gen-em.org` steht an **12 Stellen**:
  `docs/Rahmenplan.md` 7, `CLAUDE.md` 1, `Pruefdokument-P5a-…` 1,
  `Pruefdokument-P5b-…` 1, `Vorbereitung-P5-Plattformprofil.md` 1,
  `.github/workflows/auslieferung.yml` 1 (Kommentar). Kein ausgelieferter
  Code betroffen. Ein Teil der Rahmenplan-Treffer ist **Historie**
  (Fassungstabelle) und bleibt.
- **E-PP-09** legte „selber Hoster, selber Tarif" fest, mit der Begründung:
  „Nur dann misst der Messstand die Grenzen, die Produktiv wirklich hat
  (`max_user_connections`, Zeitlimits, Platz)." Diese Eigenschaft entfällt
  (E-KH-04).

---

## 2. Entscheidungen

### 2.1 Aus dem Gespräch vom 20.09.2026

**E-KH-01 — Das Tag-Modell bleibt.** `main` ist Integration und geht auf
Staging; ein Tag ist die Auslieferung. **Kein** Dauerzweig `staging`, **kein**
`main` als Produktionszweig. Gründe: (1) Das Tor prüft, dass *genau dieser
Commit* grün auf Staging stand; ein Merge `staging → main` erzeugt auf GitHub
immer eine neue SHA (Merge, Squash, auch „Rebase and merge"), Stufe 1 müsste
je Auslieferung erneut laufen (~45 min). (2) Zwei Dauerzweige brauchen
Rück-Merges; `version.php`, `CHANGELOG.md`, `Backlog.md` mergen am
schlechtesten (in P5b kostete *ein* paralleler Zweig einen Prüflauf mit 18
Befunden). (3) Tags fielen ohnehin nicht weg. (4) Auch das Zweig-Modell
bräuchte die Auskunft „was wurde wirklich ausgeliefert", solange Freigabe und
Backup-Tor zwischen Merge und Auslieferung stehen.

**E-KH-02 — Der FTPS-Push bleibt; kein Pull, kein Selbst-Update** (bestätigt
R66). Ein Webprozess, der seinen eigenen Code schreiben darf, ist bei einer
`config.php` mit Server-Anteil am Datenschlüssel der falsche Tausch. Die
Plesk-Git-Erweiterung (Pull per Webhook) folgt Zweigen statt Tags, rollt das
ganze Repo aus und verlöre die geordnete Abfolge Wartung → Backup → Abgleich →
Prüfung. Neubewertung bei P8 oder Hosterwechsel.

**E-KH-03 — Erstauslieferung von Hand (Henne-Ei).** Das Backup-Tor braucht
`jobs.php?aktion=…` (ab Web 20.4.0); Produktiv stand auf 20.3.0 und verstand
es nicht. Die Betreiberin hat `server/` von `7150793` am 20.09. von Hand
hochgeladen und die Migrationen ausgeführt. **Produktivstand seither:
`7150793` = Web 20.24.2.** Der Fall tritt nur beim Sprung von einem Stand vor
der Kette auf; bei P8 wird frisch installiert. Er begründet E-KH-19.

**E-KH-04 — Staging liegt bei lima-city unter
`staging-nadoku.gen-em.org`; ersetzt E-PP-09 in Adresse und Hoster.** Damit
ist **B2 geschlossen** — gründlicher als geplant (anderer Hoster statt nur
anderer Systemnutzer); die alte Anlage ist seit 20.09. stillgelegt (Z1).
Preis: Staging belegt kein Plattformverhalten von Produktiv mehr; die
Messstand-Zahlen sind nicht mehr übertragbar. Ausgleich: Probelauf gegen
Produktiv (E-KH-08) und ein dokumentierter Plattformvergleich (AP1). Gewinn:
Die Portabilität nach R81 wird mit jedem Push tatsächlich geprobt. Die übrigen
Punkte von E-PP-09 gelten weiter (eigener Serverschlüssel und Server-Anteil,
Absender `staging@gen-em.org`, Betreff-Präfix „[Staging]", eigenes
SFTP-Sicherungsziel).

**E-KH-10 — B3: Jede `uses:`-Zeile wird auf eine volle Commit-SHA gepinnt**,
mit der Version als Kommentar daneben.

**E-KH-11 — B4: Zwei `concurrency`-Gruppen**, je eine für Staging und
Produktiv. **Ein Produktiv-Lauf wird nie abgebrochen**; auch ein
Staging-Abgleich nicht (halber Abgleich = falsche Zustandsdatei). Bekannte
Folge bei GitHub: Je Gruppe wartet höchstens ein Lauf, ein dritter verdrängt
den zweiten wartenden — für Staging hinnehmbar (der jüngste gewinnt), wird in
`Technik.md` gesagt.

**E-KH-13 — B6: Zeiger-Zweig `produktion`.** Er zeigt auf den Commit, der
auf Produktiv liegt. Ihn bewegt **ein eigener Job** nach erfolgreichem
`produktion`-Job (`contents: write` nur dort, keine Umgebung, keine
Fremd-Aktion; der Job mit den FTP-Geheimnissen bleibt bei `read`). Die Wache
vergleicht **immer** gegen den Zeiger — bei jedem Auslöser; der
`workflow_run`-Riegel entfällt. Das Werkzeug kommt von `main`, der
Vergleichsstand vom Zeiger. Fehlt der Zeiger: **rot mit Ansage**, nie still
grün. Verworfen: „jüngstes `web-v*`-Tag" — das jüngste Tag ist nicht der
ausgelieferte Stand (Freigabe steht aus, Tor gescheitert, Rollback).
Niemand entwickelt auf `produktion`, kein PR dorthin.

**E-KH-15 — B1a: Hotfix-Weg.** Ein Handlauf bringt einen Zweig `hotfix/*` auf
Staging. Das Tor erkennt einen grünen Staging-Lauf auf dem Commit an, wenn der
Commit **auf `main` liegt** *oder* **auf `hotfix/*` liegt und vom Zeiger
`produktion` abstammt**. So ersetzt die Abstammung vom Ausgelieferten den
Zweigschutz von `main`, den ein Handlauf sonst umginge. Nach der Auslieferung
wird der Hotfix per PR nach `main` geholt.

**E-KH-16 — B1b: Zurücksetzen von Staging von Hand.** Kein Fern-Reset: Die
Wiederherstellung bleibt, wie die Anwendung es will, einer angemeldeten
Administratorin vorbehalten (`wiederherstellen.php`). Die Kette stößt bei
jeder Produktiv-Auslieferung zusätzlich ein Komplett-Backup **auf Staging** an
und nennt dessen Dateinamen neben dem Tag in der Laufzusammenfassung; ein
Runbook beschreibt Hin- und Rückweg. Ohne Schemaunterschied zwischen Tag und
`main` entfällt das Zurücksetzen.

**E-KH-18 — Bedienregeln, bis die Pakete ausgeliefert sind** (Abschnitt 5).

### 2.2 Vorschläge der Durchsicht — mit der Freigabe vom 20.09.2026 entschieden

**E-KH-05 — F1 wird allein in `tor.py` behoben, ohne Serveränderung.**
(1) `beginn` ist die **Uhr des Servers**: `tor.py` liest sie aus dem
`Date`-Kopf der ersten Antwort — ein Vergleich, eine Uhr. (2) Ist der
gemeldete Stand älter als `beginn`, **bricht das Tor nicht ab, sondern dreht
eine weitere Runde**: Der fremde offene Auftrag ist damit zu Ende gefahren,
der nächste Aufruf legt einen frischen an. Begrenzt auf zwei Runden, danach
Abbruch wie bisher. Die Regel „jünger als der Torbeginn" bleibt streng.
*Verworfen:* **Toleranzfenster** (lockert die Regel, verdeckt die
Uhrenabweichung nur); **Fertigstellungszeit** (nähme einen Dump an, der vor
Tagen gelesen und erst jetzt versiegelt wurde — Stand `siegel`);
**Laufmarke mit eigenem Auftrag** (dichter, braucht aber eine
Serveränderung und damit eine Übergangsregel nach E-KH-19 — ohne Mehrwert
gegenüber (1)+(2), weil ein fremder Auftrag *nach* Torbeginn den Zweck ebenso
erfüllt).

**E-KH-06 — F2: Nach Uploadbeginn bleibt die Wartung an, und der Lauf sagt es
laut.** Kein automatisches Ausschalten — mittendrin liegt ein gemischter
Stand. Dafür: (1) **Alles, was scheitern kann, ohne den Server zu verändern,
wandert vor den Wartungsschalter** (Geheimnisse, Adressvergleich, Tag, Tor
der grünen Läufe, Zielprobe, `doku`-Kopie, Backup-Tor); unmittelbar nach
„Wartung an" folgt der Abgleich. (2) Ein Schlussschritt, der nur bei
Fehlschlag läuft, fragt `zustand` ab und meldet als Fehlerzeile **und** in der
Zusammenfassung: Wartung an/aus, gemeldete Fassung, gescheiterter Schritt,
„Dateistand unbekannt", und die zwei Bedienwege (Lauf wiederholen / voriger
Tag plus Wiederherstellung; Wartung von Hand unter Betrieb → Updates).

**E-KH-07 — F4: Zielprobe als Rundlauf.** Vor dem Backup-Tor schreibt die
Kette per FTPS eine Datei mit Zufallsnamen und Zufallsinhalt ins
Zielverzeichnis, ruft sie über `PRODUKTION_URL` per HTTPS ab, vergleicht,
löscht sie und prüft das Löschen (danach 404). Das beweist den **lebenden
Weg** — Host, Konto und Pfad zusammen — statt eines Etiketts; eine feste
Kennung wanderte beim Klonen einer Anlage mit. Der Abruf ist öffentlich, der
Inhalt wertlos; dafür braucht die Probe **keine Serverunterstützung** und
gilt ab dem ersten Lauf (E-KH-19). Der Name meidet die gesperrten Muster der
`.htaccess` (Punktdateien, `install-nachweis-*`, `wiederher-nachweis-*`).
Dazu: **nach** dem Abgleich muss `zustand.version` dem Tag entsprechen, sonst
rot und Wartung bleibt an; `PRODUKTION_URL` wird mit `WACHE_BASIS`
verglichen; **Zielpfade und `WACHE_BASIS` verlieren ihre Vorgabewerte** —
fehlend heißt rot. Die Zielprobe ist zugleich der Vorflug für F2/F3: ein
echter Schreibzugriff mit TLS und Datenverbindung, bevor die Anlage zu ist.
Verworfen: voller Abgleich in ein Schattenverzeichnis (teurer, zweite
Codekopie auf dem Konto, deckt dieselbe Fehlerklasse).

**E-KH-08 — Probelauf.** Eine Handauslösung mit Eingabe „Probelauf" fährt den
Job `produktion` **mit Pflichtfreigabe**, aber nur: Geheimnisse,
Adressvergleich, Zielprobe, Abgleich als **Trockenlauf** (`dry-run`). Kein
Tag-Vergleich, kein Backup, keine Wartung, kein Schreiben außer der
Probedatei. Er läuft auch von `main`. Die Zusammenfassung beginnt mit
„PROBELAUF — nichts ausgeliefert".

**E-KH-12 — B5: Überspringen ist auf `main` rot.** Die fünf
`exit 0`-Stellen in `stufe2` stammen aus der Einrichtungsphase; Staging
steht. Fehlt eine Zuarbeit, ist das ein Konfigurationsfehler, kein
hinnehmbarer Zustand. (Betrifft nur `auslieferung.yml`; „Uhr Stufe I
übersprungen" in `pruefung.yml` bleibt unberührt.)

**E-KH-14 — Gemeinsame Schrittfolge; Staging mit ausstehender Migration
bleibt in Wartung.** Staging fährt dieselbe Folge wie Produktiv (Zielprobe,
Backup-Tor, Wartung an, Abgleich, Versions- und Migrationsprüfung, Wartung
aus) aus **einer** Definition; Unterschiede nur als Parameter (Umgebung,
Auslöser, keine Freigabe, kein Tag-/SHA-Tor, Stufe 2 danach). Steht auf
Staging eine Migration aus, bleibt die Wartung an und Stufe 2 meldet genau
das („Migration auf Staging ausführen, Lauf wiederholen"). Das kostet einen
Klick je Push mit Migration und ist das ehrliche Abbild von Produktiv.
*Verworfen:* automatische Migration nur auf Staging — neuer Servercode, der
auch auf Produktiv läge, gegen R66.

**E-KH-17 — Kein Schritt der Kette geht ungeprobt auf Produktiv.** Jeder
Schritt des Produktiv-Jobs existiert im Staging-Job (Logik, AP5);
plattformabhängige Schritte probt der Probelauf (AP3). Wer der Kette einen
Schritt hinzufügt, sagt im selben Zug, wodurch er geprobt wird (`CLAUDE.md` 3).

**E-KH-19 — Die Kette des Tags N spricht beim Ausliefern mit dem Server der
Fassung N−1.** Jede Änderung am Dialog `tor.py` ↔ `jobs.php` muss gegen den
Server der Vorfassung funktionieren oder eine ausdrückliche Übergangsregel
für genau eine Auslieferung mitbringen; die Selbstprobe bekommt dafür eine
Lage „alter Server". Präzedenzfall: E-KH-03.

**E-KH-20 — Die Ausnahmeliste ist eine Schutzliste; bei jedem
Transportwechsel ist sie der gefährlichste Teil** (Warnung des Auftraggebers,
20.09.). Sie nennt, was es **nur auf dem Server** gibt — `config.php`,
`install.lock`, `wartung.lock`, `ueberlast.json`, `sicherungen/`, `apk/` —
und dazu `install.php`, das im Repositorium steht, aber nie hochgeladen
werden soll.

> **ACHTER PFAD ANGEMELDET: `.sitzungen/`** (Schritt 16, Konzept
> Sitzungsablage E-SA-05; angemeldet 20.09.2026). Dort legt die Anwendung
> künftig ihre PHP-Sitzungsdateien ab, mit `0700`. **Eingetragen wird der
> Pfad von dieser Umsetzung, in dem Paket, das den Transport anfasst — AP4
> oder AP5 —, nicht von Schritt 16:** solange es zwei `exclude`-Blöcke gibt,
> in beide; danach in die eine Datei unter `tools/kette/`. Dazu in
> `CLAUDE.md` 3.
>
> **Was passiert, wenn er fehlt:** Beim heutigen Transport nichts — die
> Fremd-Aktion löscht keine Server-eigenen Dateien. Bei einem Spiegel mit
> Löschabgleich **löscht jeder Deploy alle Sitzungen**, und alle
> Angemeldeten fliegen raus. Kein Datenverlust, aber ein Ausfall bei jeder
> Auslieferung — und die Ursache stünde nirgends. Heute heißt sie nur „nicht hochladen": Die
Fremd-Aktion löscht auf dem Server allein, was sie selbst früher hochgeladen
hat (Zustandsdatei) — Server-eigene Dateien kennt sie nicht und fasst sie nie
an (`Deleting: 0 B`). Ein Spiegelwerkzeug mit Löschabgleich (`lftp mirror -R
--delete`) löscht dagegen **alles, was lokal fehlt** — dann ist die Liste das
Einzige, was `config.php` (Zugänge, Serverschlüssel, Server-Anteil),
`sicherungen/` (das Backup, das das Tor eine Zeile vorher sichergestellt hat)
und `install.lock` schützt. Ohne `config.php` ist die Anlage tot und der
Server-Anteil am Datenschlüssel verloren; fehlen `config.php` **und**
`install.lock`, steht `install.php` wieder offen (Z. 136) — dann hält nur noch
der Dateinachweis (M1-11) eine fremde Neuinstallation auf. Dazu wechselt die
Schreibweise der Muster von Werkzeug zu Werkzeug (Glob gegen regulären
Ausdruck) — eine „wortgleich" übertragene Liste kann still nichts treffen.
Deshalb gilt für **jeden** Transport, heute wie künftig:
(1) **Eine Quelle:** Die Liste steht einmal, als Datei unter `tools/kette/`;
Transport, Probelauf und Prüfung lesen sie dort. `CLAUDE.md` 3 verweist
darauf statt „wortgleich in beiden Schritten" zu verlangen.
(2) **Kein Spiegel-Löschen.** Auf dem Server gelöscht wird nur, was die Kette
selbst früher hochgeladen hat (eigene Zustands-/Bestandsliste) — nie „alles,
was lokal fehlt". Für `lftp` heißt das: **ohne `--delete`**.
(3) **Zweite, unabhängige Sicherung:** Vor der Ausführung wird der Plan (was
wird hochgeladen, was gelöscht) von eigenem Code gegen die Schutzliste
geprüft — unabhängig vom Ausschlussmechanismus des Werkzeugs. Ein Treffer
bricht ab, **vor** dem Wartungsschalter.
(4) **Köderprobe auf Staging:** An allen sieben geschützten Pfaden liegen
Köder; nach dem Lauf **7 von 7 unverändert** (Prüfsumme). Pflicht vor dem
ersten Einsatz gegen Produktiv, danach bei jeder Änderung an Liste oder
Transport.
(5) Der Probelauf nennt die Zahl der geplanten Löschungen; gegen Produktiv
ist sie beim ersten Lauf **0**.

### 2.3 Offen

**E-KH-09 — Ursache und Abhilfe von F3.** Fällt nach dem Trennversuch am Ende
von AP3. Schon entschieden ist, was **nicht** in Frage kommt: die geforderte
Sitzungswiederverwendung **serverseitig abzuschalten** — das schwächt den
Transportschutz und passt nicht zur Linie des Hauses. Und gemessen ist: eine
**neuere Fassung der Aktion gibt es nicht** (B3). Richtungen in AP4.

### 2.4 Aus der Umsetzung — von der Umsetzung entschieden

*Diese drei sind in der Umsetzung von AP1 angefallen und nicht vorher
besprochen. Sie stehen hier zur **Kenntnis und zum Widerspruch**; sie ändern
keine Festlegung aus 2.1 bis 2.3.*

**E-KH-21 — Die Schrittnummern in Rahmenplan 6a sind gebunden und werden
nicht umnummeriert.** `auslieferung.yml` nennt sie in seinen
Fehlermeldungen — „Steht die Subdomain schon? (Rahmenplan 6a, Schritte 1 bis
3.)" und „`FTP_ZIELPFAD` prüfen (Rahmenplan 6a, Schritt 4)" —, dazu ein
Kommentar („Weiterleitung auf `install.php` — Schritte 6–8"). Eine
Umnummerierung macht aus einer Fehlermeldung, die den Weg weist, eine, die
in die Irre führt, **ohne dass ein Prüfmittel anschlägt**: `tools/kettenaufrufe/`
prüft Werkzeugschnittstellen, keine Textverweise. Die Neufassung von 6a
behält deshalb die Bedeutung jeder Nummer bei und sagt das im Abschnitt
selbst. Wer die Nummern doch ändern will, ändert die drei Stellen in
`auslieferung.yml` im selben Paket.

**E-KH-22 — Der Plattformvergleich steht in `docs/Technik.md` 6.3a, nicht in
5b.** 5b ist das **Plattformprofil**: was eine Installation von ihrer
Plattform braucht, hosterneutral (R81). Der Vergleich zweier konkreter
Anlagen beantwortet eine andere Frage — **was eine Messung auf Staging über
Produktiv aussagt, nämlich nichts** —, und die gehört zur Kette. Er steht
deshalb hinter 6.3 (Stufe 2), weil Stufe 2 der Ort ist, an dem die Zahlen
entstehen, über die er redet. 5b verweist in einem Satz darauf.

**E-KH-23 — Pakete, die nur `docs/`, `CLAUDE.md` und `.github/` anfassen,
stufen keine Version und schreiben keinen Changelog-Eintrag.** Sie zählen
über die **Fassung des Rahmenplans**. Das ist nicht neu, sondern die
bestehende Linie (`CLAUDE.md` 2; Präzedenz `0f2333e` vom 18.09.2026: „Keine
Versionsstufe und kein Changelog-Eintrag: Die Änderung fasst nur `.github/`
an"), und es steht hier, weil es in diesem Konzept **wiederkehrt**: AP1
ist so ein Paket, AP2 und AP6 werden es sein. Sobald ein Paket `tools/kette/`
oder `tools/integritaetswache/` anfasst, bleibt es dabei — `tools/` stuft
ebenfalls nicht hoch; erst eine Änderung unter `server/` tut es.

**E-KH-24 — `setup-java` im Prüflauf wirkt global; der Uhr-Schritt wird
ausdrücklich zurückgesetzt.** *Gefallen am 20.09.2026, außerhalb der
Arbeitspakete — beim Beheben des Android-Fehlschlags (Android 0.15.1,
Backlog Nr. 240), der `pruefung.yml` anfasst und damit dieses Konzept
berührt.*

`actions/setup-java` schreibt `JAVA_HOME` und `PATH` über `GITHUB_ENV` bzw.
`GITHUB_PATH` und gilt damit für **alle folgenden Schritte** — nicht nur für
den, neben dem es steht. Der Android-Schritt braucht Java 21; der
Uhr-Schritt steht danach, übersetzt rund **35 Minuten** für alle Zielgeräte
und nimmt `java` über `monkeyc` vom PATH. Er liefe damit ungefragt auf einem
JDK, das niemand für ihn geprüft hat.

*Entschieden:* Der Schritt „Fassungen nennen" hält den Standard-JDK des
Läufers in `JAVA_HOME_LAEUFER` fest, **bevor** irgendetwas ihn umstellt; der
Uhr-Schritt setzt `JAVA_HOME` und `PATH` daraus zurück und sagt in einer
Zeile, womit er übersetzt.

*Verworfen:* (a) `setup-java` global ohne Rückstellung — spart zwei Zeilen
und stellt einen 35-Minuten-Schritt blind um; (b) den Android-Block hinter
den Uhr-Schritt schieben — wirkt, hängt aber an der Reihenfolge und geht beim
nächsten eingefügten Schritt still verloren; (c) `JAVA_HOME_21_X64` des
Läuferabbilds statt `setup-java` — kommt ohne Action aus, hängt aber an einer
Variablen, die GitHub ohne Zusage setzt.

*Was daran zu prüfen ist:* Dass die Uhr weiterhin übersetzt. Ein Lauf mit
`android=ja` **und** `uhr=ja` hat das noch nicht gezeigt; bis dahin ist es
eine begründete Vorsichtsmaßnahme, keine Messung.

**E-KH-25 — Die Zielprobe ist eine eigene Datei, kein Unterbefehl von
`tor.py`.** *Gefallen in AP3, 20.09.2026.* Das Konzept sagt „Zielprobe als
Unterbefehl in `tools/kette/`"; sie liegt jetzt als
`tools/kette/zielprobe.py` daneben.

*Grund:* `tor.py` spricht mit `jobs.php` über HTTPS und kennt genau ein
Geheimnis, das **Job-Token**. Die Zielprobe braucht die **FTPS-Zugangsdaten**.
Beides in einer Datei hieße, dass das Werkzeug, das an jedem Tor steht, die
Zugangsdaten zum Dateisystem des Servers kennt — ohne dass es sie braucht.
Zwei Dateien, zwei Geheimnismengen, zwei Selbstproben.

*Was dafür spricht, es trotzdem zusammenzulegen:* ein Aufruf weniger zu
merken. Das wiegt die Vermischung nicht auf.

*Folge für `CLAUDE.md` und `Technik.md`:* keine — beide nennen die Werkzeuge
einzeln. `tools/kettenaufrufe/` prüft die neuen Aufrufe von selbst mit
(gemessen: 32 Aufrufe, 0 Befunde).

---

## 3. Arbeitspakete

### 3.0 Übersicht, Reihenfolge, Abhängigkeiten

| ID | Titel | Befunde | Entscheidungen | nach | berührt | Stopp |
|---|---|---|---|---|---|---|
| AP1 | Staging-Umzug nachziehen | B7, B2 | E-KH-04 | — | Doku, 1 Workflow-Kommentar | nur bei rotem Staging-Lauf |
| AP2 | Zeiger und Wache | B6 | E-KH-13 | Z2 (erledigt) | `integritaet.yml`, `auslieferung.yml`, `tools/integritaetswache/`, Doku | — |
| AP3 | Tor, Zielprobe, Probelauf; F3-Messung | F1, F4, F3 | E-KH-05, -07, -08, -19 | AP1 | `tools/kette/`, `auslieferung.yml`, Doku | **ja — E-KH-09** |
| AP4 | F3 beheben | F3 | E-KH-09, -20 | AP3 | `auslieferung.yml`, `tools/kette/`, Doku — oder nur Variablen | nach Köderprobe: Freigabe vor dem ersten Lauf gegen Produktiv |
| AP5 | Gemeinsame Schrittfolge | F-Nenner | E-KH-14, -17 | AP3 | `auslieferung.yml` (+ ggf. wiederverwendbarer Lauf), Doku | — |
| AP6 | Abbruchverhalten und Härtung | F2, F4-Rest, B3, B4, B5 | E-KH-06, -07, -10, -11, -12 | AP5 | alle drei Workflows, Doku | — |
| **M1** | Erster grüner Produktivlauf | alle | — | AP4, AP6 | Betreiberin | — |
| AP7 | Hotfix-Weg | B1 | E-KH-15, -16 | M1 | `auslieferung.yml`, `Technik.md` | — |
| **M2** | Probe-Hotfix | B1 | — | AP7 | Betreiberin | — |
| AP8 | Abschluss | — | — | M2 | Doku | Freigabe |

Für jedes Paket gilt `CLAUDE.md` 2, 7, 8: eines nach dem anderen; Version,
Changelog, `Technik.md`, `CLAUDE.md` 3 mitziehen und auf Widerspruch prüfen;
Statusblock und Prüfdokument nach jedem Paket fortschreiben, dann pushen.
Arbeitet die Umsetzung in einer Chat-Instanz statt in Claude Code: Ausgabe je
Paket als ZIP in Repo-Struktur, nur geänderte und neue Dateien, dazu das
fortgeschriebene Konzept und das Prüfdokument.

### AP1 — Staging-Umzug nachziehen (E-KH-04)

- Alte Adresse → `staging-nadoku.gen-em.org` an allen **nicht-historischen**
  Fundstellen (Abschnitt 1.5); Historie (Fassungstabelle, Changelog) bleibt.
- **Rahmenplan 6a neu schreiben**: Einrichtung, wie sie beim neuen Hoster
  tatsächlich ist (FTP-Wurzel, Zielpfad, Ort der Zustandsdatei — Werte der
  *Variablen*, keine Geheimnisse; die Betreiberin nennt sie, Z3). E-PP-09 in
  `Vorbereitung-P5-Plattformprofil.md` mit Vermerk „ersetzt durch E-KH-04".
- **Plattformvergleich** in `Technik.md`: die Plattformauskunft beider Anlagen
  (Betrieb → Status) nebeneinander — PHP-Fassung, Speicher- und Zeitgrenzen,
  DB-Fassung, `max_user_connections`, Cron ja/nein, FTPS. Mit dem Satz, dass
  Messstand-Zahlen von Staging **nicht** auf Produktiv übertragbar sind.
- **Abnahme:** Push auf `main` → Staging-Lauf gegen lima-city grün; in
  `stufe2` **alle fünf Messschritte gemessen** (kein „ÜBERSPRUNGEN");
  Kreisläufe 0 unerklärt; Bilderlauf 0/0/0; `grep` auf die alte Adresse zeigt
  nur noch Historie (Zahl nennen).
- **Stopp** nur, wenn Stufe 2 aus Plattformgründen rot ist (Grenzen bei
  lima-city) — dann Befund an die Betreiberin.

#### Umsetzung AP1 (20.09.2026) — was gebaut wurde, was haftet

**Kein Web-Code.** `git status -- server/ watch/ android/` liefert **0
Zeilen**; damit keine Versionsstufe und kein Changelog-Eintrag (E-KH-23).
Gezählt hat stattdessen der Rahmenplan: **Fassung 81**.

**Sechs Dateien:**

| Datei | Was |
|---|---|
| `docs/Rahmenplan.md` | **Abschnitt 6a neu geschrieben**; drei Textstellen außerhalb (Fahrplan, Vorbereitungsblock, Zuarbeitszeile); Kopf nachgemessen; Fassung 81 im Änderungsverlauf |
| `docs/Technik.md` | **neuer Abschnitt 6.3a** „Staging und Produktiv sind zwei Plattformen" samt Plattformvergleich; `FTP_STATE_PFAD` in der Variablentabelle ergänzt; Querverweis aus 5b |
| `docs/konzepte/Vorbereitung-P5-Plattformprofil.md` | E-PP-09 mit Vermerk „ersetzt in Adresse und Hoster durch E-KH-04"; derselbe Vermerk am Empfohlen-Punkt „selber Hoster, selber Tarif"; Kopfzeile |
| `CLAUDE.md` | Abschnitt 3: neue Adresse, anderer Hoster, der Preis in zwei Sätzen |
| `.github/workflows/auslieferung.yml` | **ein Kommentar** — die Messung vom 16.09. als Messung an der *damaligen* Anlage gekennzeichnet |
| `docs/konzepte/Konzept-Kette-Haertung.md` | das Konzept selbst, mit AP1 erstmals im Repositorium |

**Die Adressumstellung in Zahlen.** Vorher **12** Fundstellen der alten
Adresse. Davon waren **5 aktuelle Aussagen** über die heutige Anlage
(Rahmenplan Fahrplan, Zuarbeitszeile, 6a Schritt 1 und Schritt 6,
`CLAUDE.md` 3) — sie sind umgestellt. **4 waren Protokoll**, das sich als
Gegenwart lesen ließ (Rahmenplan Vorbereitungsblock und
Domain-Default-Messung, `auslieferung.yml`, E-PP-09) — sie sind **datiert
und mit Vermerk** versehen, nicht umgeschrieben. **3 sind reine Historie**
und unberührt (Fassungstabelle 70, Prüfdokumente P5a und P5b). Nach AP1:
**13 Fundstellen, alle Historie oder ausdrücklich als „damals"
gekennzeichnet** — die dreizehnte ist das Konzept selbst, das die 12 in
Abschnitt 1.5 aufzählt.

**Zwei Tabellen stehen leer, und das ist Absicht.** Der Plattformvergleich
(`Technik.md` 6.3a) und die Variablenwerte (Rahmenplan 6a) brauchen
**Z3** — die Auskunft der Betreiberin. Sie lag nicht vor. Gebaut ist die
Form samt der Aussage, die sie trägt; die Zahlen fehlen und sind als
fehlend gekennzeichnet (`⬚ Z3`). **Geratene Zahlen wären hier schlimmer
als keine:** Der ganze Zweck des Vergleichs ist, dass man sich auf ihn
berufen kann.

**Problem 1 — alle Haken in 6a galten der alten Anlage.** Schritte 1 bis 6,
8 und 10 waren am 16./17.09.2026 abgehakt, für `staging.nadoku.gen-em.org`
im Produktiv-Webspace. Kein einziger sagt etwas über lima-city. *Gelöst:*
Die Liste steht wieder offen, **mit dem Grund je Zeile** — „Stand nicht
gemeldet (Z3)", „Zuarbeit Z7", „das ist die Abnahme von AP1". Ein Haken, der
stehen bleibt, weil er einmal stimmte, ist schlimmer als ein leeres Kästchen.

**Problem 2 — die Schrittnummern sind gebunden.** `auslieferung.yml` nennt
sie in drei Fehlermeldungen. *Gelöst:* E-KH-21 — die Nummern behalten ihre
Bedeutung, und 6a sagt das im Abschnitt selbst.

**Problem 3 — der Rahmenplan-Kopf war überholt.** Er führte P5b als „liegt
zum Merge bereit", zwei Tage nach PR #57 (`eec41e1`, 18.09.2026), und maß
`main` bei `676780d` / Web 20.16.4 statt `7150793` / Web 20.24.2. Aufgefallen
ist es beim Nachmessen, das Rahmenplan Abschnitt 9 vor **jeder** Fassung
verlangt. *Gelöst, aber nur halb:* Der **Stand** ist berichtigt und der Fund
im Kopf vermerkt; die **Erledigt-Zeile für P5b in Abschnitt 8** und die
Nachzüge in den Abschnitten 3, 5 und 6 sind **nicht** geschrieben — sie
gehören dem Abschluss von P5b, nicht diesem Paket. Das ist eine Fremdaufgabe,
die hier nur benannt wird.


**Nachtrag 20.09.2026 — Z3 kam zur Hälfte, und eine Angabe darin war mehr
wert als die Tabelle.** Die Betreiberin hat die Plattformauskunft und die
Einrichtungswerte von **Produktiv** geliefert; beide Tabellen tragen diese
Spalte jetzt abgelesen, die Staging-Spalte bleibt `⬚ Z3`. Drei Dinge daraus
gehören nicht in eine Tabellenzelle:

1. **Produktiv hat keinen Cron.** Alle elf Hintergrundjobs tragen als Weg
   „anfrage", laufen also huckepack auf einer Seitenanfrage. Zulässig (einer
   der drei Wege aus `Technik.md` 4.97a) und kein Mangel — aber **ohne
   Besucher läuft nichts**, und das trifft **AP5**: Sobald das Backup-Tor
   auch auf Staging fährt, wartet ein Läufer auf einen Job, den nur eine
   Anfrage weiterbringt. Der Job „GPS-Daten verdichten" stand dabei auf
   **Rückstand 69**.
2. **Das Produktiv-FTP-Konto ist auf den Webroot eingesperrt**, `FTP_ZIELPFAD`
   steht auf `/`, `FTP_STATE_PFAD` ist **nicht gesetzt**. Das schmälert eine
   der drei F3-Erklärungen — Einzelheiten als Nachtrag in **Abschnitt 1.4**,
   samt zwei Folgen für AP3 und Z5, die dort zu entscheiden sind.
3. **Zwei Zahlen der Produktiv-Spalte sind keine Messung** — der freie Platz
   (861,7 GB; auf geteiltem Webspace meldet PHP den Datenträger des Hosts)
   und das DB-Kontingent (eine Angabe, 10 GB). Beide tragen den Vorbehalt in
   `Technik.md` 6.3a als eigenen Kasten, damit sich niemand später darauf
   beruft.


**Nachtrag 2 vom 20.09.2026 — die Staging-Hälfte kam nach, und mit ihr der
erste Ertrag von E-KH-04.** Weil die Anwendung auf Staging **noch nicht
installiert ist** (Schritt 6 scheitert, eine andere Instanz arbeitet daran),
kam die Auskunft aus einer **`phpinfo()`-Ausgabe** statt von der Statusseite.
Drei Dinge daraus:

4. **Ein Fehlbefund der eigenen Statusseite — F-KH-U-05.** Auf lima-city
   steht `opcache_get_status` in `disable_functions`; `function_exists()`
   antwortet dafür `false`, und `plattform_pruefen()` meldet **„OPcache: aus"**,
   während der OPcache läuft. Das verletzt 5b.1 („`null` nicht feststellbar —
   wer nichts gemessen hat, darf nichts behaupten"). **Nicht behoben**: Das
   wäre Web-Code und damit nicht AP1 (E-KH-23); der Vorschlag steht im
   Prüfdokument, Abschnitt 4. **Der Fund ist der Beleg für das Versprechen
   von E-KH-04** — sechs Tage lang liefen beide Anlagen beim selben Hoster,
   und dieser Zuschnitt fiel niemandem auf.
5. **Die Nicht-Übertragbarkeit ist jetzt belegt, nicht nur begründet.** Drei
   Weblimits weichen ab: `max_execution_time` 240 s gegen 300 s,
   `post_max_size` und `upload_max_filesize` je 256 MB gegen 500 MB. Damit
   misst Stufe 2 auf Staging nachweislich andere Grenzen, als Produktiv hat.
6. **Beide FTP-Konten sind auf `/` eingesperrt, und in beiden Umgebungen
   fehlt `FTP_STATE_PFAD`.** Die `../`-Frage aus Rahmenplan 6a gilt damit für
   **beide** Anlagen — und **der erste Kettenlauf gegen lima-city beantwortet
   sie für Staging, ohne Produktiv anzufassen.** Genau die Arbeitsteilung,
   die E-KH-17 meint.

**Dazu drei Punkte, die nicht in dieses Konzept gehören, aber gesagt werden
mussten** und in der Prüfliste stehen: `info.php` liegt öffentlich im
Staging-Webroot (4a), drei Geheimnisse der Umgebung `staging` gehören noch
der stillgelegten Anlage (5), und `session.save_path` zeigt bei lima-city
**über** das eigene Verzeichnis hinaus — dieselbe Frage, deren Antwort bei
der alten Anlage **B2** war (5a).

**Problem 4 — was die eigene Durchsicht nicht sah.** Nach dem Bau sind die
geänderten Dokumente von **sieben getrennten Lesern** gegengelesen worden,
je einer pro Dokument. Vier Befunde waren berechtigt und sind behoben:
`docs/Technik.md` trug im Kopf noch *Stand: 17.09.2026*; eine Tabellenzeile
derselben Datei (`| Repositorium | … |`) stand hinter einer Leerzeile und
damit ohne Kopf — **älter als AP1**, aber in der Tabelle, die AP1 ergänzt;
in `Vorbereitung-P5-Plattformprofil.md` stand unkommentiert *„bis dahin
deployt `main` weiter auf Produktiv"* (seit Web 20.4.0 falsch — genau der
Satz, vor dem `CLAUDE.md` 3 warnt); und die Herkunftszeile in Abschnitt 5
derselben Datei nannte E-PP-09 ohne Ersetzungsvermerk. Einzelheiten und die
zwei Befunde, die bewusst liegen bleiben, stehen als **F-KH-U-04** im
Prüfdokument.

**Was E-KH-20 angeht: AP1 fasst den Transport nicht an.** Die Ausnahmeliste
ist unberührt — gemessen: **zwei** `exclude`-Blöcke in `auslieferung.yml`,
je **12 Zeilen**, **wortgleich**, darin die sieben geschützten Pfade. Damit
gilt weiterhin, was E-KH-20 (1) beheben will: Die Liste steht **zweimal**.
Sie zusammenzuführen ist AP5, der Löschabgleich AP4 — AP1 hat daran nichts
geändert und durfte es nicht.

### AP2 — Zeiger und Wache (B6; E-KH-13)

- Voraussetzung **Z2 — erledigt 20.09.**: Der Zweig `produktion` steht auf
  `7150793` (nachgemessen).
- Wache: Werkzeug von `main`, Vergleichsstand vom Zeiger; vergleicht bei
  **jedem** Auslöser; Riegel entfällt; fehlender Zeiger = rot mit Ansage;
  die Zusammenfassung nennt verglichenen Commit, Tag und Dateizahl.
- Zeiger-Job in `auslieferung.yml` nach E-KH-13; Rückwärtsbewegen (Rollback)
  ist erlaubt. `permissions` des Workflows bleiben `read`.
- `CLAUDE.md` 3: Regel zum Zweig `produktion`.
- **Abnahme:** Handlauf der Wache gegen den Zeiger **grün mit Dateizahl**,
  *während* `main` dem Zeiger voraus ist (nach AP1 ist es das) — damit ist der
  tägliche Falschalarm belegt beseitigt. Gegenprobe im Werkzeug ohne Netz:
  ein verändertes Byte im Vergleichsstand → rot. **Der Zeiger-Job ist gebaut,
  nicht gelaufen — gemessen wird er mit M1;** so steht es im Prüfdokument.

#### Umsetzung AP2 (20.09.2026) — was gebaut wurde, was haftet

**Kein Web-Code.** `git diff --name-only -- server/ watch/ android/` gegen den
Stand vor dem Paket liefert **0 Zeilen**; damit keine Versionsstufe und kein
Changelog-Eintrag (E-KH-23). Gezählt hat der Rahmenplan: **Fassung 84**.

**Fünf Dateien:**

| Datei | Was |
|---|---|
| `tools/integritaetswache/wache.py` | `--stand PFAD`, `stand_setzen()`, `stand_kennung()`; Kopfzeile nennt Commit und Tag; sechs neue Fälle in der Selbstprobe |
| `.github/workflows/integritaet.yml` | Riegel gestrichen, Zeiger ausgechecked, `--stand zeiger`, Zusammenfassung mit Commit/Tag/Dateizahl; `actions: read` ausgetragen |
| `.github/workflows/auslieferung.yml` | neuer Job **`zeiger`** mit `contents: write` |
| `CLAUDE.md` | Abschnitt 3: die Regel zum Zweig `produktion` |
| `docs/Technik.md`, `tools/integritaetswache/LIESMICH.md` | neuer Abschnitt **6.6a** bzw. „Wogegen verglichen wird" |

**B6 ist ohne Netz nachgerechnet, und die Zahl weicht von der protokollierten
ab.** Das Konzept nennt für den 18.09.2026 „2 abweichend, 6 × 404". Aus den
Ständen selbst gerechnet (Produktiv `14f99ac`, `main` `eec41e1`) ergibt Teil 1
**1 abweichend** (`assets/style.css`) und **6 × 404**. Die zweite Abweichung
lag in **Teil 2**: `login.php` ist zwischen beiden Ständen um 128 Zeilen
gewachsen. Damit stimmen Protokoll und Nachrechnung überein — die Zahl im
Konzept fasste beide Teile zusammen.

| | Dateien | gleich | abweichend | 404 |
|---|---|---|---|---|
| alt (Vergleichsstand `main`) | 128 | 121 | 1 | 6 |
| neu (Vergleichsstand Zeiger) | 122 | 122 | 0 | 0 |

**Problem 1 — die Abnahme, wie sie im Konzept steht, ist heute nicht
erfüllbar.** Gefordert war: *„Handlauf der Wache gegen den Zeiger grün mit
Dateizahl, während `main` dem Zeiger voraus ist — damit ist der tägliche
Falschalarm belegt beseitigt."* `main` **ist** dem Zeiger voraus (12 Commits,
`7150793` → `862ca7f`), aber **in nichts, was die Wache misst**: Gemessen sind
`server/assets/` und `login.php`; verändert sind zwölf PHP-Dateien, darunter
keine davon. `git diff --name-only origin/produktion origin/main -- server/assets/`
liefert **0**. Ein grüner Handlauf würde heute also nichts über B6 belegen —
er wäre grün, auch ohne die Änderung.
*Gelöst:* Die Nachrechnung oben tritt an seine Stelle. Sie misst genau den
Fall, der B6 war, und braucht kein Netz. **Der Handlauf bleibt trotzdem im
Prüfdokument stehen** — als Punkt für die Betreiberin, zu erledigen beim
nächsten Stand, in dem sich unter `assets/` etwas geändert hat.

**Problem 2 — der Handlauf gegen Produktiv läuft aus diesem Container nicht.**
`https://nadoku.gen-em.org` antwortet dem Egress-Proxy mit
`403 Tunnel connection failed`; gemessen an **128 von 128** Dateien. Die Wache
meldete dabei korrekt „128 nicht erreichbar" und **Rückgabewert 1** — sie hat
also nicht still grün gemeldet, was das Richtige ist.
*Nicht gelöst, sondern festgehalten:* Diese Messung gehört an die Betreiberin
oder an einen Kettenlauf.

**Problem 3 — `needs` allein hätte den Zeiger falsch bewegt.** Ein
übersprungener Job gilt GitHub als erfüllte Abhängigkeit. Ohne
`if: needs.produktion.result == 'success'` hätte **jeder Push auf `main`** —
der `produktion` überspringt — den Zeiger auf einen Stand gesetzt, der nie
ausgeliefert wurde. Die Wache verglänge danach gegen eine Unwahrheit, und zwar
grün. *Gelöst:* die `if`-Zeile, mit dem Grund daneben.

**Problem 4 — ein Rückfall auf das Repositorium wäre der alte Fehler durch die
Hintertür.** `stand_setzen()` war zuerst so gebaut, dass ein fehlender Pfad
eine Warnung gibt und auf `WURZEL/server` zurückfällt. Das ist bequem und
falsch: Nach einem misslungenen Auschecken verglänge die Wache wieder gegen
`main`, ohne dass es jemand merkt. *Gelöst:* Sie wirft, der Aufrufer meldet
rot, und zwei Fälle der Selbstprobe halten das fest.

**Was bewusst NICHT gebaut ist:** ein Schutz des Zweigs `produktion` gegen
Pushes von Hand. Er wäre richtig, gehört aber zu den Repositoriumseinstellungen
und nicht in eine Datei — er steht als Punkt im Prüfdokument.

### AP3 — Tor, Zielprobe, Probelauf; danach F3-Messung (F1, F4; E-KH-05, -07, -08, -19)

- `tor.py`: F1 nach E-KH-05. **Selbstprobe von 11 auf mindestens 16 Lagen**:
  fremder Auftrag offen → zweite Runde → Tor offen; Serveruhr 120 s nach →
  offen; Serveruhr 120 s vor → offen; nach zwei Runden kein frischer Stand →
  Abbruch; Antwort ohne `Date`-Kopf → Abbruch mit klarer Meldung; **„alter
  Server"** (Antwort ohne die erwarteten Felder) → definierter Abbruch statt
  40 Runden Warten (E-KH-19; der Fall vom 20.09.).
- Zielprobe als Unterbefehl in `tools/kette/`. FTPS-Client der Probe ist
  **`curl`** (auf dem Runner vorhanden) — bewusst ein **zweiter** Client neben
  der Fremd-Aktion: Das trennt bei F3 „Plattform" von „Bibliothek". Die Probe
  kennt **zwei Betriebsarten: mit und ohne Wiederverwendung der TLS-Sitzung**
  auf dem Datenkanal; im Normalbetrieb läuft sie *mit*. Ob `curl` die Sitzung
  in der Fassung des Runners tatsächlich wiederverwendet, wird **gemessen,
  nicht angenommen** (ausführliche Ausgabe, Geheimnisse maskiert). Räumt Reste
  früherer Proben weg; löscht auch im Fehlerfall; gibt Servermeldungen
  wörtlich aus. Eigene Selbstprobe ohne Netz.
- Probelauf nach E-KH-08; Zielprobe zusätzlich fest im Job `produktion` vor
  dem Backup-Tor. (Staging erhält sie mit AP5.)
- **Abnahme:** Selbstproben grün mit Lagenzahl; Probelauf **gegen Staging**
  grün (Probedatei geschrieben, abgerufen, gelöscht, danach 404).
- **F3-Trennversuch — mit der Betreiberin, Ergebnis in Abschnitt 1.4:**
  1. Erhoben ist bereits (Auftraggeber): beide Läufe, dieselbe Stelle,
     `ensureDir('api/')`, keine Datei übertragen. **Nachzutragen:** Runner-Abbild
     und Node-Fassung des grünen Staging-Laufs `35341712345` (18.09.) gegen die
     der roten Läufe vom 20.09.
  2. Sicherstellen, dass **keine andere FTP-Sitzung** auf dem Konto offen ist.
  3. Probelauf **gegen Produktiv**, Zielprobe in **beiden Betriebsarten**:
     *mit* Wiederverwendung gelingt und *ohne* scheitert mit Abbruch der
     Datenverbindung → **Forderung des Servers belegt**. Beide gelingen →
     die Forderung ist es nicht; dann Trockenlauf der Aktion ansehen und
     Schritt 4. Beide scheitern → Konto, Passiv-Ports oder Verbindungsgrenze.
  4. Nur wenn 3 die Forderung **nicht** belegt: die Anordnung nachstellen, die
     am 17./18.09. lief — FTP-Zusatzkonto, eingesperrt auf den Webroot (Z5) —
     und den Probelauf damit wiederholen. Trennt „Konto/Pfad" von „Bibliothek".
  5. Jedes Ergebnis zweimal (zweimal gleich = belastbar).
- **STOPP.** Befund und Empfehlung an die Betreiberin → **E-KH-09**.

#### Umsetzung AP3 (20.09.2026) — was gebaut wurde, was haftet

**Kein Web-Code.** Nur `tools/kette/`, `.github/`, `docs/`. Keine
Versionsstufe (E-KH-23); gezählt hat der Rahmenplan: **Fassung 86**.

| Datei | Was |
|---|---|
| `tools/kette/tor.py` | F1 nach E-KH-05: Laufbeginn aus dem `Date`-Kopf (`kopfzeit()`), zweite Runde statt Abbruch, definierter Abbruch beim alten Server (E-KH-19). Selbstprobe **11 → 17 Lagen** |
| `tools/kette/zielprobe.py` | **neu** — Rundlauf FTPS → HTTPS → vergleichen → löschen → 404, zwei Betriebsarten, Selbstprobe **26 Lagen** |
| `.github/workflows/auslieferung.yml` | Zielprobe vor dem Backup-Tor; Probelauf (E-KH-08) als Eingabe `probelauf` am Handauslöser, mit sechs gesperrten Schritten und Trockenlauf |
| `docs/Technik.md`, `tools/kette/LIESMICH.md` | neuer Abschnitt **6.5a** bzw. das zweite Kapitel |

**Die Selbstprobe des Tors, 17 Lagen:** Regelfall · meldet nie fertig ·
falsches Token · kein Stand · **fremder Auftrag → zweite Runde → offen** ·
**nach zwei Runden kein frischer Stand → zu** · **Serveruhr 120 s nach →
offen** · **Serveruhr 120 s vor → offen** · **kein `Date`-Kopf → Abbruch** ·
**Antwort ohne `fertig`/`error` → definierter Abbruch** · **eine solche
Antwort ist ein Schluckauf, kein Nein** · fünf für `pause` · eine für die
400-Antwort.

**Problem 1 — die Selbstprobe hat die eigene Änderung sofort gefangen.**
Nach dem Umbau auf die Serveruhr fiel Fall 1 durch: „Antwort trägt keinen
`Date`-Kopf". Die Attrappe lieferte keine Serverzeit. *Gelöst:* Die Attrappe
hängt jetzt eine an jede Antwort, wie `rufen()` es aus dem Kopf tut — und
`serverzeit=None` stellt ausdrücklich den Fall „ohne `Date`" nach. **Das ist
kein Ärgernis, sondern der Beleg, dass die Probe misst.**

**Problem 2 — die Maskierung der Zielprobe zerschnitt ihre eigene
Auswertung.** `curl_ftp()` gab die Ausgabe maskiert zurück; `aufraeumen()`
liest daraus die Dateiliste. Mit einem kurzen Passwort wurde aus
`.zielprobe-alt.txt` ein `.zielpro***e-alt.txt`, und das Aufräumen fand seine
eigenen Reste nicht mehr. *Gelöst:* Maskiert wird am **Rand** (`sag()`), nicht
in der Mitte; dazu eine Untergrenze von vier Zeichen, mit Gegenprobe. Gefunden
von der Selbstprobe, nicht beim Lesen.

**Problem 3 — zwei Jobs tragen wortgleiche Schrittnamen.** „Handbuch und
‚Was ist NAdoku' nach server/doku kopieren" steht in `staging` **und** in
`produktion`. Ein Ersetzen über die ganze Datei traf beide. *Gelöst:* Alle
Eingriffe laufen auf einem Ausschnitt zwischen `produktion:` und `zeiger:`.
**Das ist eine Warnung für AP5:** Beim Zusammenführen der Schrittfolge ist
die Namensgleichheit gewollt — dann darf kein Werkzeug mehr über den Namen
gehen.

**Z5 Teil 1 ist erledigt, und zwar aus der Umsetzung heraus** (die Zuarbeit
sah die Betreiberin vor; die Läuferangaben stehen im Kopf jedes Jobprotokolls
und sind über die API erreichbar):

| | grün, 18.09., Staging (`35341712345`) | rot, 20.09., Produktiv (`35499422433`) |
|---|---|---|
| Läuferfassung | 2.337.0 | **2.337.0** |
| Abbild | ubuntu-24.04 / 20260907.300.1 | **ubuntu-24.04 / 20260907.300.1** |
| Provisioner | 20260828.587 | **20260828.587** |

**Identisch. Läuferabbild und Node-Fassung scheiden als Erklärung für F3
aus.** Dieselbe Aktionsfassung (v4.4.0), derselbe Läufer, dasselbe Abbild —
der Unterschied liegt auf der Serverseite.

**Und ein Befund dazu, der die Frage weiter verengt.** Das Protokoll des
roten Laufs zeigt die Stelle genauer, als sie bisher beschrieben war:

```
Making changes to 708 files/folders to sync server state
Uploading: 11.7 MB -- Deleting: 0 B -- Replacing: 0 B
creating folder "api/"
Error: Client is closed because read ECONNRESET (data socket)
    at Client.sendIgnoringError (…/index.js:4236:25)
    at Client._openDir (…/index.js:4763:20)
    at Client.ensureDir (…/index.js:4754:24)
```

> **BERICHTIGT am 20.09.2026 — F-KH-U-23 im Prüfdokument.** Der folgende
> Absatz nennt `_openDir` einen Listen-Befehl auf dem Datenkanal. **Das ist
> falsch:** `_openDir` sendet `MKD` und `CWD`, beides Steuerkanal
> (Quelltext `basic-ftp` 6.2.1, Z. 686–689). Der Stacktrace ist eine
> **Zustandsmeldung** — der Client war beim `MKD` schon tot, der Reset kam
> auf der Datenverbindung davor. Der Absatz bleibt als Protokoll stehen;
> gültig ist von ihm nur, dass der Steuerkanal steht.

`_openDir` ist ein **Listen-Befehl auf dem Datenkanal**. Alles davor —
Anmeldung, `PWD`, `CWD`, die ganze Dateiaufzählung aus dem Zustandsvergleich
— läuft über den **Steuerkanal** und gelingt. **Der Steuerkanal steht; die
ERSTE Datenverbindung des Laufs wird abgeschnitten.** Dazu passt
`Deleting: 0 B — Replacing: 0 B`: Die Zustandsdatei war leer, der Lauf wollte
alle 708 Dateien neu hochladen.

Das ist die Signatur von genau zwei Dingen: gesperrte Passiv-Ports oder ein
Server, der die Wiederverwendung der TLS-Sitzung auf dem Datenkanal
**verlangt**. Beides trennt die Zielprobe in ihren zwei Betriebsarten — und
gegen lima-city lief derselbe Client mit Datenkanal durch, was die
Bibliothek weiter entlastet.

#### Der erste Probelauf gegen Produktiv (20.09.2026, Lauf 35531806339)

**Die Betreiberin hat ihn gefahren, und er hat sofort etwas gefunden — nur
nicht das, wonach gesucht wurde.**

**Erst das Erfreuliche: Die Mechanik stimmt.** Gemessen an der Schrittliste
des Laufs:

| Schritt | Ergebnis |
|---|---|
| „PROBELAUF — was dieser Lauf NICHT tut" | gelaufen |
| Tag gegen `WEB_VERSION` | **übersprungen** |
| Tor der grünen Läufe | **übersprungen** |
| Zielprobe (Selbstprobe **26 Lagen, 0 offen**, dann der Lauf) | **rot** |
| Backup-Tor, Wartung, `doku`-Kopie, FTPS-Abgleich, Migrationsabfrage | **übersprungen** |
| Job `zeiger` | **übersprungen** |

Der Zeiger bewegte sich nicht, weil `produktion` rot war — die `if`-Zeile aus
AP2 hat gehalten. **Kein Byte ist auf den Produktivserver gegangen.**

**Der Befund — F-KH-U-08: Das FTPS-Zertifikat von Produktiv passt nicht zum
Hostnamen.** `curl` bricht ab, bevor eine Datei bewegt wird:

```
< 220 ProFTPD Server (ProFTPD)
> AUTH SSL
< 234 AUTH SSL successful
* SSL connection using TLSv1.3 / TLS_AES_256_GCM_SHA384
* Server certificate:
*  subject: CN=<interner Knotenname des Hosters>
*  subjectAltName does not match <FTP_SERVER>
curl: (60) SSL: no alternative certificate subject name matches target host name
```

**Was das heißt.** Die Verbindung ist verschlüsselt, aber **nicht
beglaubigt**: Der Gegenüber weist sich mit einem anderen Namen aus, als
angesprochen wurde. Über genau diese Verbindung gehen die FTPS-Zugangsdaten
und der vollständige Inhalt von `server/`. Wer sich dazwischensetzt, fiele
nicht auf.

**Und es heißt zweitens: Die Auslieferungsaktion prüft das Zertifikat
nicht.** Sie kam bei denselben Zugangsdaten bis `ensureDir('api/')` — also
weit hinter den Punkt, an dem `curl` abbricht. Das ist der erste Ertrag des
zweiten Clients (E-KH-07), und er kommt aus einer Richtung, die niemand
erwartet hat: Nicht die Bibliothek ist auffällig, sondern **das, was sie
nicht prüft**.

**Was der Lauf NICHT beantwortet: F3.** `curl` kam gar nicht bis zum
Datenkanal; der `ECONNRESET` der Aktion sitzt dort. Der Trennversuch steht
also weiter aus — er braucht erst eine Verbindung, die zustande kommt.

**Und eine Grenze, die der Lauf gemessen hat:** Die Zielprobe meldete
„TLS-Sitzung wiederverwendet: **NICHT FESTSTELLBAR**". Die `curl`-Fassung
dieses Läufers sagt nichts darüber. **Damit lassen sich die zwei
Betriebsarten auf diesem Läufer nicht unterscheiden** — ein Lauf *ohne*
Wiederverwendung belegte nicht, dass sie unterblieb. Die Dreiwertigkeit hat
das gesagt, statt es zu behaupten; ein `False` an dieser Stelle hätte den
ganzen Trennversuch auf eine Annahme gestellt.

**Zwei Mängel der Zielprobe, die der Lauf gezeigt hat — beide behoben:**

1. Sie warnte, die Probedatei „liege jetzt im Zielverzeichnis", obwohl nie
   eine entstanden war — der Verbindungsaufbau war ja gescheitert. Sie
   schickte damit jemanden auf die Suche nach nichts. Jetzt löscht sie nur,
   wenn hochgeladen wurde, und sagt sonst „nichts zu löschen".
2. Im Protokoll stand der Fehlschlag **vor** dem Kopf, zu dem er gehört:
   `stdout` ist in einer Kette gepuffert, `stderr` nicht. `sag()` leert den
   Strom jetzt nach jeder Zeile.

Dazu ein dritter Fall in der Selbstprobe: `curl 60` wird als **Namensfehler**
benannt und nicht als Transportfehler, samt der Abhilfe — und ausdrücklich
ohne einen Schalter, der die Prüfung abschaltet. Selbstprobe **26 → 29
Lagen**.

**STOPP.** Was jetzt fehlt, ist eine Messung an der Anlage — Schritte 2 bis 5
des Trennversuchs. Sie steht im Prüfdokument als Prüfpunkte 10 bis 13.

### AP4 — F3 beheben (Inhalt nach E-KH-09; E-KH-20 gilt in jedem Fall)

- **Richtung (a) — Transport auf `lftp mirror -R` umstellen** (wenn der
  Trennversuch die geforderte Sitzungswiederverwendung belegt). Behandelt die
  Wiederverwendung richtig und schwächt nichts. **Bedingungen nach E-KH-20,
  alle fünf:** eine Quelle für die Schutzliste; **kein `--delete`**,
  Löschungen nur aus eigener Bestandsliste; unabhängige Planprüfung vor dem
  Wartungsschalter; Köderprobe auf Staging 7 von 7; Probelauf gegen Produktiv
  mit 0 geplanten Löschungen. Zu klären beim Bau: `lftp` ist auf dem Runner
  nicht vorinstalliert (Bezug aus dem Ubuntu-Archiv, Fassung im Protokoll
  nennen); die Muster der Schutzliste in der Schreibweise von `lftp`, **mit
  einer Probe, die beweist, dass jedes Muster trifft**; die bisherige
  Zustandsdatei der Fremd-Aktion wird nicht weiterverwendet. Nebenwirkung:
  Die Fremd-Aktion mit dem Produktiv-Passwort entfällt (B3 für diesen Schritt
  erledigt); eine eigene Wiederholung wird möglich (F2).
- **Richtung (b) — neuere Fassung der Aktion: entfällt.** Gemessen am 20.09.:
  `v4.4.0` ist das jüngste Tag (B3). Ein Rückgriff auf `v4.3.6` (Node 20)
  hilft nicht, weil die Runner Node 20 ablösen.
- **Richtung (c) — Forderung serverseitig abschalten: verworfen** (E-KH-09).
- **Richtung (d) — Konto/Pfad** (nur wenn Schritt 4 des Trennversuchs dorthin
  zeigt): FTP-Zusatzkonto für Produktiv, eingesperrt auf den Webroot; nur
  Variablen ändern sich, der Transport bleibt — und damit auch das harmlose
  Löschverhalten von heute.
- **Reihenfolge der Wahl:** Zeigt die Messung auf (d), ist (d) vorzuziehen —
  kleinster Eingriff, kein Wechsel der Löschsemantik. Sonst (a).
- **Abnahme:** Köderprobe auf Staging **7 von 7**; Probelauf gegen Produktiv —
  Zielprobe **und** Trockenlauf des (neuen) Transports grün, geplante
  Löschungen 0, **zweimal hintereinander**. **Vor dem ersten echten Lauf gegen
  Produktiv: Ergebnis an die Betreiberin, Freigabe.**

### AP5 — Gemeinsame Schrittfolge (E-KH-14, -17)

- **Eine** Definition der Schrittfolge für beide Umgebungen; Form
  (wiederverwendbarer Arbeitslauf oder zusammengesetzte Aktion) wählt die
  Umsetzung **nach Messung** — Bedingung: Umgebungsgeheimnisse und
  Pflichtfreigabe bleiben erhalten, die Freigabe steht **vor** dem ersten
  Zugriff auf ein Geheimnis. Die Schutzliste steht spätestens jetzt
  **einmal** (E-KH-20 (1); heute zweimal im Workflow).
- **Dabei kommt `.sitzungen/` als achter Pfad hinzu** (Schritt 16, E-SA-05 —
  angemeldet 20.09.2026, siehe E-KH-20). Liegt AP4 vor Schritt 16, trägt AP4
  ihn ein; sonst AP5. Wer die Liste zusammenführt, zählt danach **acht**
  Pfade und nicht sieben — die Zahl ist der Prüfwert.
- Staging fährt damit Zielprobe, Backup-Tor, Wartung an/aus, Versions- und
  Migrationsprüfung. Verhalten bei ausstehender Migration nach E-KH-14.
- Voraussetzung auf Staging (Z7): Komplett-Backup funktioniert
  (Serverschlüssel, Sicherungsziel), `JOBS_TOKEN` gesetzt.
- **Abnahme:** Staging-Lauf zeigt dieselben Schrittnamen wie Produktiv;
  Dauer des Staging-Jobs vorher/nachher genannt; ein Lauf mit ausstehender
  Migration lässt die Wartung an und Stufe 2 sagt genau das; nach
  `update.php` von Hand und Wiederholung grün. **Das ist der Handweg zu
  Nr. 234** — dort vermerken.

### AP6 — Abbruchverhalten und Härtung (F2, F4-Rest, B3, B4, B5)

- Reihenfolge und Schlussschritt nach E-KH-06; Versionsprüfung,
  Adressvergleich und Wegfall der Vorgabewerte nach E-KH-07; Pins nach
  E-KH-10 (alle drei Workflows); `concurrency` nach E-KH-11; B5 nach E-KH-12.
- Das Tor prüft zusätzlich, dass im anerkannten Staging-Lauf der Job
  `staging` **erfolgreich** und nicht übersprungen war.
- **Abnahme:** `grep` auf `uses:` — jede Zeile mit 40-stelliger SHA (Zahl
  nennen); auf **Staging** provozierter Fehlschlag nach „Wartung an" (falsches
  FTP-Passwort in der Umgebung `staging`, Betreiberin): Lauf rot, Schlussschritt
  meldet Wartung an, Fassung, Bedienweg — Wortlaut ins Prüfdokument; danach
  Wartung von Hand aus, Geheimnis zurück, Lauf grün. Fehlende Variable
  `FTP_ZIELPFAD` → rot vor jedem Zugriff.

### M1 — Erster grüner Produktivlauf (Betreiberin)

Tag auf den dann aktuellen, grün geprüften Commit; Freigabe. **Erwartet:**
alle Schritte grün; Zeiger rückt auf den Tag-Commit; Wache vergleicht und ist
grün mit Dateizahl; auf Produktiv liegt `.deploy-state-produktion.json` am
vorgesehenen Ort. Damit erledigt: P3 des P5a-Prüfdokuments
(Pflichtfreigabe hält an). P2 (falscher `JOBS_TOKEN` → Tor rot, kein Upload)
bei dieser Gelegenheit mitfahren.

### AP7 — Hotfix-Weg (B1; E-KH-15, -16)

- Tor-Erweiterung und Staging-Handlauf für `hotfix/*` nach E-KH-15; Umgebung
  `staging` lässt `main` und `hotfix/*` zu (Z4).
- Zusatz-Backup auf Staging bei Produktiv-Auslieferung nach E-KH-16.
  **Zu messen:** wie viele Komplett-Stände Staging aufbewahrt — der Stand des
  letzten Tags darf nicht verdrängt sein, wenn man ihn braucht.
- **Runbook in `Technik.md`:** (1) Schemaunterschied zwischen Tag und `main`
  prüfen; ohne Unterschied weiter bei 3. (2) Staging-Stand des Tags über
  `wiederherstellen.php` einspielen. (3) `hotfix/*` vom Zeiger abzweigen,
  Fix, eigene Patch-Version. (4) Handlauf auf Staging, Stufe 2 grün.
  (5) Tag, Freigabe. (6) PR nach `main` — `main` behält seine höhere Fassung,
  der Changelog nennt den Hotfix. (7) Staging zurück auf `main`: Handlauf,
  Migrationen von Hand.
- **Abnahme:** Tor lehnt einen `hotfix/*`-Commit ab, der **nicht** vom Zeiger
  abstammt (Gegenprobe); nimmt einen an, der abstammt.

### M2 — Probe-Hotfix (Betreiberin)

Eine Textkorrektur über den ganzen Weg aus AP7. Wahlweise im selben Zug den
Rückweg nach E-PV-3 proben (voriger Tag erneut ausliefern, Zeiger geht
zurück, dann wieder vor).

### AP8 — Abschluss

Prüfdokument fertig (`CLAUDE.md` 7: was maschinell, was im Browser, **was
nicht** und warum; Prüfliste mit Bedienweg, Erwartung, Scheitern erkennbar
an); `Technik.md`, `CLAUDE.md` 3, Rahmenplan 6a widerspruchsfrei; Einschübe
aus Abschnitt 7 und 8; Erledigt-Zeile nach Freigabe.

---

## 4. Prüfprotokoll-Soll

| Mittel | Wann | Soll |
|---|---|---|
| Prüftor Stufe 1 | jeder Push | grün |
| Selbstprobe `tor.py` | AP3 | ≥ 16 Lagen, alle grün; Lagen aus AP3 namentlich dabei |
| Selbstprobe Zielprobe | AP3 | grün, Lagenzahl genannt |
| Gegenprobe Wache (ohne Netz) | AP2 | 1 verändertes Byte → rot |
| Staging-Lauf + Stufe 2 | AP1, AP5, AP6 | grün; 5 von 5 Messschritten gemessen; Kreisläufe 0 unerklärt; Bilderlauf 0/0/0 |
| Wache gegen den Zeiger | AP2, M1 | grün mit Dateizahl, bei `main` ≠ Zeiger |
| Probelauf gegen Staging | AP3 | Rundlauf vollständig, danach 404 |
| Probelauf gegen Produktiv | AP3, AP4 | Vierfeldertafel; nach AP4 zweimal grün |
| Köderprobe Schutzliste auf Staging | AP4, danach bei jeder Änderung an Liste oder Transport | 7 von 7 Ködern unverändert (Prüfsumme); jedes Muster trifft nachweislich |
| Provozierter Fehlschlag auf Staging | AP6 | Wortlaut des Schlussschritts im Prüfdokument |
| Tor-Gegenprobe Abstammung | AP7 | 1 abgelehnt, 1 angenommen |

**Nicht prüfbar, und wie damit umgegangen wird:** der Zeiger-Job und der
volle Produktiv-Pfad **vor M1** — gebaut, nicht gelaufen; Staging probt die
Logik (AP5), der Probelauf die Plattform (AP3), M1 misst den Rest. Ein
Abbruch **mitten** in einem großen Abgleich — nicht provozierbar; bleibt der
Fall „Wartung an, laut gesagt" (E-KH-06).

---

## 5. Bedienregeln bis zur Auslieferung der Pakete (E-KH-18)

1. **Kein Hand-Backup in den Minuten vor einer Freigabe** (F1) — gilt, bis
   ein Tag AP3 enthält (`tor.py` läuft aus dem Commit des Tags; eine
   Serverauslieferung braucht es dafür nicht).
2. **Nach jedem roten Produktivlauf:** Betrieb → Updates ansehen, Wartung
   gegebenenfalls von Hand beenden (F2) — bis AP6.
3. **Keine offene FTP-Sitzung auf dem Produktiv-Konto während eines Laufs**
   (F3-Vermutung) — bis E-KH-09.
4. Kein weiterer Tag-Lauf gegen Produktiv vor dem Ergebnis von AP3/AP4 —
   jeder Versuch schaltet die Wartung ein und lässt sie an.

---

## 6. Zuarbeiten der Betreiberin

| Nr. | Was | Wann |
|---|---|---|
| **Z1** | **Alte Staging-Anlage im Produktiv-Webspace stilllegen:** Subdomain `staging.nadoku…`, Verzeichnis, Datenbank samt Nutzer, FTP-Zusatzkonto, Cron-Eintrag, A-Record bei Hoster 1, altes SFTP-Ziel. **Solange sie besteht, ist B2 offen.** | **erledigt 20.09.2026** |
| Z2 | Zweig `produktion` auf `7150793` anlegen: `git push origin 71507932006d1431abe3823b97cf877b18ccb055:refs/heads/produktion` | **erledigt 20.09.2026** (nachgemessen) |
| Z3 | Nicht-geheime Einrichtungswerte der neuen Staging-Anlage und Plattformauskunft beider Anlagen nennen | AP1 |
| Z4 | GitHub: Zweigschutz `main` (PR, Stufe 1 Pflicht — laut Rahmenplan am 16.09. noch offen); Umgebung `staging` auf `main` beschränken, mit AP7 zusätzlich `hotfix/*`; Repositoriums-Variable `WACHE_BASIS` setzen; `FTP_ZIELPFAD` und `FTP_STATE_PFAD` in **beiden** Umgebungen ausdrücklich setzen | bis AP6 / AP7 |
| Z5 | F3: Runner-Abbild/Node-Fassung der Läufe vom 18.09. und 20.09. nachtragen; Probeläufe freigeben; **nur falls der Trennversuch es verlangt:** FTP-Zusatzkonto für Produktiv (eingesperrt auf den Webroot) anlegen | AP3, AP4 |
| Z6 | M1 und M2: Tag, Freigabe | nach AP6 / AP7 |
| Z7 | Neue Staging-Anlage vollständig: **eigener** Serverschlüssel und Server-Anteil, SFTP-Sicherungsziel, Komplett-Backup einmal von Hand gelungen, Demo-Konto, Referenzbestand, Messstand-Konto, Betreff-Präfix | vor AP5 |

---

## 7. Einschub Rahmenplan (Fassungsnummer vergibt die einspielende Instanz)

- **R67:** Zusatz „gehärtet mit Konzept Kette II (E-KH-01 bis -19):
  Zielprobe, Probelauf, gemeinsame Schrittfolge, Zeiger-Zweig, Hotfix-Weg".
- **R66:** unverändert; Verweis auf E-KH-02 (Pull geprüft und verworfen).
- **E-PP-09:** Status „ersetzt in Adresse und Hoster durch E-KH-04
  (20.09.2026)".
- **Abschnitt 6a:** neu (AP1). **Abschnitt 6, Zuarbeiten:** Z1–Z7.
- **Fahrplan:** eigener Schritt „Kette II" vor Schritt 15 — Begründung: Bis
  M1 ist die Kette nicht auslieferungsfähig, und Schritt 15 soll nicht das
  erste Release über eine ungeprobte Kette sein.
- **Programmsatz:** E-KH-17 und E-KH-19 als dauerhafte Regeln.
- **Abschnitt 8/10:** Erledigt-Zeile und eine Zeile nach Abschluss.

## 8. Einschub Backlog (Nummern vergibt die einspielende Instanz)

- **Neu:** Atomare Auslieferung (Release-Verzeichnis, Umschalten statt
  Überschreiben) — Auslöser: P8 oder Hosterwechsel; niedrig.
- **Neu:** Die Wache sieht nur öffentlich abrufbare Dateien (`assets/`,
  Anmeldeseite), keinen PHP-Quelltext — Grenze benennen, Abhilfe offen;
  niedrig.
- **Neu (nur falls AP4 bei der Fremd-Aktion bleibt, Richtung (d)):** Ablösung
  der Fremd-Aktion durch einen eigenen Transport — Auslöser: erneute
  Abbrüche, Bedarf an Wiederaufnahme, oder ein Ende der Pflege der Aktion
  (jüngstes Tag vom 19.04.2026).
- **Nr. 234:** Vermerk „Handweg auf Staging mit Kette II AP5; automatisierter
  Weg und Grundsatzfrage bleiben offen".

---

## 9. Fable-Schritte der Umsetzung

Keine.
