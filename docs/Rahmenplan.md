# Rahmenplan — Programm „Gen-EM NAdoku" bis v1.0

**Fassung 19 (02.09.2026)** — Neustrukturierung (Fassung 16). Dieses Dokument steuert
das Programm: Reihenfolge, Status, programmweite Entscheidungen. Es hält
nur, was für die nächsten Schritte gebraucht wird. Alles, was bis
Fassung 15 hier stand — die Fassungsvermerke, die Phasentexte mit ihren
Umsetzungsblöcken und die 50 Programmentscheidungen im Volltext —, liegt
**wörtlich und eingefroren** in `docs/Rahmenplan-Archiv.md`. Verweise aus
älteren Dokumenten auf „Rahmenplan Abschnitt 5" oder „Fassung 13" meinen
das Archiv; sein Kopf sagt, welcher alte Abschnitt wo weiterlebt.

**Stand am 02.09.2026:** `main` trägt **Web 12.4.2** und **Uhr 2.0.0**
(ausgeliefert; ein Push auf `main` deployt). Der S4-Zweig trägt
**Web 12.8.0** und **Android 0.7.7**; `main` ist in ihn geholt und die
Konflikte sind gelöst — der Push auf `main` **wartet auf die Freigabe**, und
danach ist `update.php` fällig. Darauf aufbauend liegt der S6-Zweig mit
**Web 12.9.1** (Schritt 2, gebaut) — er bringt **zwei weitere** Migrationen
mit; nach dem Deploy ist `update.php` einmal für alle drei fällig.

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
Namen, neuer Oberfläche, Mehrbenutzerfähigkeit und frischen
Repositorien (ob eines oder mehrere, entscheidet die Planung vor v1.0, R59). Betrieben wird es als **offener Dienst mit
Selbstregistrierung** bis 1 000 Konten (R36), mit einem zweiten Client
neben der Garmin-Uhr: Android-Handy und Wear-OS-Uhr (R45).

Feste Zusagen, die keine Phase aufweicht (Einzelheiten `CLAUDE.md` 4):
Ende-zu-Ende-Verschlüsselung der geschützten Felder · keine fremde Quelle
zur Laufzeit · **keine Telemetrie** — Betriebszahlen nur aus vorhandenen
Spalten, einzige Ausnahme die Gerätekennung beim Koppeln (R36, R42) · das
Demo-Konto als einzige benannte E2E-Ausnahme (R25).

**Nicht Ziel:** iOS und watchOS (R46) · Store-Verteilung der Clients vor
v1.0 (Betriebsübergang, R41) · ein Migrationspfad für Bestandsinstallationen
(R11: v1.0 liest die 7.x-Sicherung genau einmal, mehr nicht) ·
Rückwärtskompatibilität ab v1.0, auch bei Updates (R60).

## 2. Regeln der Zusammenarbeit

### 2.1 Konventionen K1–K9

- **K1** Je Phase ein Konzeptdokument im bewährten Format: Befund,
  Entscheidungen (E-Nummern), offene Fragen (F-Nummern), Arbeitspakete mit
  Abnahmekriterien, Prüfprotokoll, gesammelte Fehlerfunde. Es ist die
  Übergabeeinheit an die umsetzende Instanz. **Ablage: `docs/konzepte/`**,
  Mockups in einem Unterordner daneben; Lebenszyklus in 2.2 (R62).
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
  P5-Beginn** deployt `main` nur noch auf Staging; am P6-Schnitt einmaliges
  Neuaufsetzen mit Datenübernahme per edbak (R11).
- **Backlog-Nummern sind dauerhaft.** Vor jedem Merge, der Backlog-Punkte
  mitbringt: `grep -oE '^[0-9]+\.' docs/Backlog.md | tr -d '.' | sort -n |
  uniq -d` muss leer sein. Der Backlog-Kopf sagt, welche Nummern ein
  laufender Zweig reserviert hat.
- **Migrationsregister beim Merge:** `schema.sql` und `update.php` tragen
  je eine Liste der Migrationskennungen; beim Zusammenführen **beide**
  Seiten behalten und gegenzählen (`update.php` schluckt doppelte
  Anlagen still; der Schaden trifft erst die nächste Neuinstallation).
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
| 1 | **S4 — Merge** | Fehlerbehebung abschließen, Backlog-Nummern nachziehen, `main` holen, Merge = Deploy, `update.php` | — | liegt vor | Opus | **in Arbeit** — Zweig fertig (Web 12.8.0, Android 0.7.7), `main` geholt und Konflikte gelöst; **wartet auf die Freigabe zum Push**, `update.php` steht danach aus |
| 2 | **S6 — Gerätekennung und Schlüsselfrist** | Serverseite von R42, Behebung R44 | Schritt 1 | keins; R42 und R44 sind die Spezifikation | Opus | **gebaut** — Web 12.9.1 auf dem Zweig, Modelltabelle gefüllt; wartet nur noch auf die Abnahme |
| 3 | **S5 — Kopplung umgekehrt, Konzept** | E-R49-1 bis E-R49-8 ausarbeiten | Schritt 2 | neu | **Fable** (R14) | offen |
| 4 | **S7 — Backup-Begriff** | Umstellung in einem Zug | Schritt 1; parallel zu 3 | `docs/konzepte/Umstellung-Backup.md` | Opus | offen |
| 5 | **S5 — Umsetzung** | Server, Web, Uhr, Doku | Schritt 3; DNS `nadoku.gen-em.org` | aus Schritt 3 | Opus | offen |
| 6 | **S4 — Rest** | Kopplungsmodul, QR, Gerätetest, Android 1.0.0 | Schritt 5 | Konzept S4, Abschnitt 13 | Opus | offen |
| 7 | **S8 — Einstellungen, Administration und Wartung** | Sichtung und Neuordnung: Sicherungsoptionen, Menüstruktur, Aufteilung der Wartungsseite, Einzelpunkte 73–79 (R61) | Schritte 4 und 6 | neu, mit Mockups | Fable (Konzept) | offen |
| 8 | **Backlog-Runde** | Einzelpunkte nach Abschnitt 5 | ab Schritt 1, parallel | keins | Opus | offen |
| 9 | **P5 — Dienstbetrieb** | Registrierung, Rollen, Administration, Betrieb | Schritte 2, 5 und 7; Hosting-Entscheidung; Staging | neu | Fable (Konzept) | offen |
| 10 | **Planung v1.0** | Konzeptgespräch vor dem Schnitt: Umfang des Code-Reviews, Aufteilung in mehrere Repositorien, Auslieferungskette, Update-Weg (R59, R60); Ergebnis ist das P6-Konzept | Schritt 9 | entsteht hier | Fable (R14) | offen |
| 11 | **P6 — v1.0-Schnitt** | Review, Umbenennung, Doku, Neuaufsetzen, Repositorien | Schritt 10 | aus Schritt 10 | Fable (R17), sonst Opus | offen |
| — | Betriebsübergang | Öffnung in Wellen, Stores | nach v1.0 | — | — | — |

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
Server (Sitzungstabelle statt `pair_codes`, drei Anliegen `start`/`status`/
`bestaetigen`, Ratenschutz je Konto und IP, Aufräumen über den Job-Einstieg)
· Web (Feld „Code vom Gerät", Bestätigungsseite mit Art und Modell,
„Kopplungscode erzeugen" entfällt, manuelle Anlage bleibt) · Uhr
(Code-Anzeige, Rückbestätigung mit maskierter E-Mail, Vorgabeadresse
`nadoku.gen-em.org`, Uhr-Build mit den S3-Kacheln) · Doku. **Abnahme des
Konzepts:** Freigabe durch den Auftraggeber. In die S5-Abnahme geht
**P2-Prüfpunkt 4.1** auf (eine Kopplung mit der Uhr in der Hand, R55).

### Schritt 4 — S7 Backup-Begriff

**Ziel:** „Sicherung" wird überall zu „Backup", in einem Zug (R50).
**Spezifikation:** `docs/konzepte/Umstellung-Backup.md` (Befund, Grenzen,
Arbeitsliste, Prüfwege). **Entschieden (R56):** Verb „sichern" bleibt in
den Knöpfen, Symbolname `sicherung` bleibt, `admin_sicherungen.php` wird
nicht umbenannt. **Grenzen:** `server/sicherungen/` bleibt (Deploy-
Ausnahme), Changelog und abgeschlossene Konzepte bleiben, dieses Dokument
und das Archiv bleiben. **Vor Beginn neu zählen** (Stand 02.09.2026: 451
Treffer in `server/`, 138 sichtbare Fundstellen). **Abnahme:** Gegenprobe
`grep -rc "Sicherung" server/ docs/`, jede Fundstelle gegen die Grenzenliste;
Wortliste 0/0/0; Browserprüfung der vier betroffenen Seiten. Kein Konzept,
kein Prüfdokument.

### Schritt 5 — S5 Umsetzung

**Ziel:** Kopplung ohne Tippen auf der Uhr, für Garmin und Android
dasselbe Protokoll. **Inhalt:** die vier Blöcke aus Schritt 3 · Migration
(Sitzungstabelle) → `update.php` · Uhr-Auslieferung (Kopplung,
Vorgabeadresse, S3-Kacheln; dabei Backlog 66: `watch/` in die Wortliste) ·
Handbuch 12 und 2.x, Technik, `Geraete-Eingabe.md`, Changelog Web und Uhr.
**Preis, angenommen:** Nach dem Server-Umstieg koppelt keine ältere
Uhr-Fassung mehr (Bestand: eine Uhr). **Abnahme:** Uhr-Prüfstand über alle
Geräte, Simulator-Rundlauf gegen lokalen Server (Start → Eingabe im Web →
Status → Bestätigen, dazu Nein-Fall, Fristablauf, Gerätelimit),
Ratenschutz-Proben, Bilderlauf Geräteseite, Wortliste; Gerätetest mit der
Uhr (P2-Punkt 4.1). **Zuarbeit:** DNS und TLS für `nadoku.gen-em.org` vor
dem Uhr-Build.

### Schritt 6 — S4 Rest

**Ziel:** Die Android-App wird benutzbar ausgeliefert (1.0.0).
**Inhalt:** Kopplungsmodul der Handy-App auf Vertragsabschnitt 1a neu
schneiden (Konzept S4, Abschnitt 13: sechs Quelldateien, rund 600 Zeilen,
39 von 220 Prüffällen) · Adress-QR auf dem Geräte-Reiter (E-S4-15, nur die
Server-Adresse) · Hinweis in der Tagesansicht bei zeitlich überlappenden
aktiven Diensttagen samt Handbuchabsatz (R57) · Backlog 63 (Sperrvermerke
des Schnitts in die Konto-Sicherung) · Signaturschlüssel erzeugen und
übergeben, erstes signiertes APK · Gerätetest auf dem S24 (zwei bis drei
Runden) · Changelog-Präfix `Android` mit der ersten verteilten Fassung ·
Prüfdokument S4 fortschreiben, Erledigt-Zeile in Abschnitt 8. **Abnahme:**
Prüfliste 4 und 6 des Prüfdokuments S4 (Telefon, Kreisläufe R24 auf
geschnittenen und importierten Einsätzen), Messstand für das Schneiden.
**Wear-OS-Uhr:** Gerätetest, sobald eine vorliegt; blockiert nichts.

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
S2-Optionen liegen (R31, R32, R37). **Entscheidung im Konzept:** die
Bedienhöhe am Schreibtisch (Nr. 74) — eine Änderung an `CLAUDE.md` 5 und
`Design.md` nur mit Begründung und Kontrastprüfung. **Abnahme:** Bilderlauf
in acht Breiten, Vollständigkeit, Wortliste, Stilvergleich mit
Soll-Ist-Liste, Bedienprüfung jeder umgezogenen Funktion; Handbuch
nachgezogen, verschobene und entfernte Funktionen ausgetragen. **Nicht
Umfang:** neue Verwaltungsfunktionen und Rollen (P5, R38). **Lage:** nach
S4-Rest und S7, weil alle drei `einstellungen.php` und die Admin-Seiten
anfassen.

### Schritt 8 — Backlog-Runde

**Ziel:** Die Einzelpunkte aus Abschnitt 5, die keiner Phase bedürfen.
**Regeln:** je Punkt ein Commit, Buchführung nach `CLAUDE.md` 2, kein
Konzept nach K1; ein Punkt, der eine neue Darstellung braucht, bekommt
vorher ein Mockup und eine Freigabe (`CLAUDE.md` 5). Läuft jederzeit ab
Schritt 1 parallel, auf eigenem Zweig; die Dateiregel aus Abschnitt 4 gilt.

### Schritt 9 — P5 Dienstbetrieb

**Ziel:** Die Anwendung trägt eine größere Nutzerbasis sicher. Baut auf
der Ordnung aus S8 auf. **Inhalt nach R9, R10, R31, R33, R36 bis R41:** Registrierung mit drei Betriebsarten
und Sicherheitspaket · Konto-Lebenszyklus (Bibliothek, Kontostatus bis in
`ingest.php`, Double-Opt-In, Selbstlöschung mit Karenz, E-Mail-Wechsel,
Einwilligungen mit Fassungskennung, Mail-Warteschlange, Geräteschlüssel
auf SHA-256, Mengengrenze je Konto, IP-Grenzwerte für NAT, Onboarding mit
Notfallblatt) · Support-Rolle, Admin-TOTP, Audit-Protokoll,
Ankündigungsbanner, Fehlerprotokoll-Sicht, Health-Endpunkt ·
Betriebslage-Dashboard im festen Minimalumfang samt Geräteverteilung (R42)
· Rückbau der zentralen Stammdaten (R39) · Servicemodell (R33) ·
Admin-Optionen für Support-Adresse (R31), Rechtstexte (R32) und die
S2-Sicherungseinstellungen · Mengenbremse `ingest.php` (R19,
Grundsatzfrage zuerst) · CSP mit HSTS, `frame-ancestors`, `nosniff` (Nr. 8)
· Wartungsmodus-Torwächter für ausstehende Migrationen (R40.4). **Davor:**
Hosting-Entscheidung (R36), Staging-Ziel; mit P5-Beginn endet der
Autodeploy auf Produktiv. **Backlog:** 8, 17, 37, 48, 49, 54, 67. **Demo-
Konto** in jeder Betriebsart mitdenken (R25).

### Schritt 10 — Planung v1.0

**Ziel:** Bevor etwas als v1.0 veröffentlicht wird, noch einmal planen
statt schneiden (R59, Beschluss vom 02.09.2026). Ein Konzeptgespräch mit
vier Gegenständen: **Code-Review** — Umfang, Reihenfolge und Form des
Bug- und Sicherheitsreviews nach R17, was davon vor und was nach dem
Neuaufsetzen läuft, wie Funde entschieden werden · **Aufteilung in mehrere
Repositorien** — ob Web/Server, Garmin-Uhr, Android und Werkzeuge getrennt
oder gemeinsam weiterleben, was das für Vertrag, Versionszählung,
Auslieferung und die Übernahme des Backlogs (dauerhafte Nummern) heißt ·
**Auslieferungskette** nach R40 (Staging, Release-Tag, CI-Prüftor,
Rollback) · **Update-Weg der Installation ab v1.0** (R60): Prüft die
Installation selbst gegen das Repositorium und meldet eine neue Fassung?
Spielt sie das Update selbst ein, oder bleibt es beim Hochladen per FTP?
Muss die Migrationsliste auf der Wartungsseite sichtbar bleiben? Fest
steht: ab v1.0 **keine Rückwärtskompatibilität**, auch nicht bei Updates;
v1.0 beginnt mit dem Neuaufsetzen (R40), und eine ältere Sicherung wird
genau **einmal** über ein dafür gebautes Formular eingespielt, das danach
entfällt. Dazu die Doku-Anforderungen nach R16, wenn das Gespräch dazu
noch aussteht. **Ergebnis:** das P6-Konzept nach K1 mit Paketschnitt und
Abnahmekriterien; bis dahin beginnt kein P6-Paket. **Modell:** Fable (R14).

### Schritt 11 — P6 v1.0-Schnitt

**Ziel:** Neuer Name, neue Repositorien nach dem Schnitt aus Schritt 10,
Version 1.0. **Inhalt:**
Eingangsschritt **Bug- und Sicherheitsreview mit Fable (R17)** —
einschließlich Verschlüsselungsverfahren, Containerfassung 4, SPUR1,
Komplettbackup und Serverschlüssel, Demo-Konstruktion, Schlüsselablage auf
dem Handy, S5-Kopplungsweg, Umgang mit Dumps und Klartext-Koordinaten (R41,
Nr. 43) · Umbenennung überall (Web und Handbuch heißen noch „Einsatzdoku",
die Uhr seit 2.0.0 „NAdoku"), Namensdurchgang nach R31, neues Demo-Passwort
mit dem Produktnamen in der Schwachwortliste (R25) · Kommentare
normalisieren (R13, Liste in Konzept P2 10.3) · R5-Ausnahmeliste
beschließen (zugeliefert: leer) · Vertragsreview und Festschreibung als v1
(R12; Nr. 23) · Doku-Neufassung mit Screenshots und klickbaren Kapiteln
(R16; Anforderungsgespräch vorher) · Changelog neu ab v1.0 (R15) · Backlog
mit dauerhaften Nummern übernehmen · Altformat der Sicherung abschaffen
(Nr. 46) · **Neuaufsetzen** (R40): frische Installation, Übernahme des
Bestandskontos per edbak, Demo-Konto nach Runbook, Probe des
Komplettbackup-Zyklus auf Produktiv; Release-getriggerte Auslieferung mit
CI-Prüftor (R24/R28/R35) und Rollback-Weg · Rechts- und Betreiberunterlagen
zur Öffnung (R41). **Abnahme nach R11:** die frische Installation liest die
Referenz-edbak aus `tools/referenzdatensatz/referenz/`.

### Betriebsübergang (nach v1.0, keine Phase)

Öffnung in Wellen über die Betriebsarten (R41) · Verteilung der Clients
über Connect-IQ-Store und Play Store (setzt Mengenbremse und Mengengrenze
aus P5 voraus, E-R45-6) · halbjährliche Probe-Wiederherstellung.

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
| Konzeptarbeit P5 (Hosting, Gespräche) zu allem | S5-Umsetzung zu S6 und S7 (`pair.php`, `devices`, `einstellungen.php`, Vertrag, Wartungsjob) |
| Wear-OS-Gerätetest zu allem | S4-Rest zu S5 (Kopplungsmodul braucht 1a) |
| — | Backlog 21 (43 Restfunde quer durch `server/`) zu jedem laufenden `server/`-Paket |
| — | S8 zu S4-Rest und S7 (`einstellungen.php`, `admin_*.php`, `update.php`, Handbuch) — S8 erst nach beiden |

**Merge-Reihenfolge auf `main`:** ein Push je Paket nach Freigabe (K7);
nach jeder Migration `update.php`.

## 5. Zuordnung der offenen Backlog-Punkte

Jeder offene Punkt steht genau einmal. Nummern 63–67 sind für den
S4-Zweig reserviert (dort heute 59–63); 68–79 sind mit dieser Fassung
angelegt.

| Nr. | Punkt (kurz) | gehört zu | Bemerkung |
|---|---|---|---|
| 8 | Content-Security-Policy | P5 | mit HSTS, `frame-ancestors`, `nosniff` |
| 17 | Mengenbremse `ingest.php` | P5 | Grundsatzfrage zuerst (R19); Messung liegt |
| 19 | `$title` in `einsatz_loeschen.php` ungenutzt | Backlog-Runde | zusammen mit 21 |
| 21 | 43 A4-Restfunde sichten (mit 18) | Backlog-Runde | Felder mit Vertrags- oder Uhrberührung nur nach Vertragsabgleich (R21) |
| 23 | Vertrag nennt Reanimationsart `beginn`, die keiner annimmt | P6 | mit dem Vertragsreview (R12) |
| 36 | Prüfmittel: Klassennamen, die nur JavaScript sucht | Backlog-Runde | Prüfmittel |
| 37 | Konto, das über Jahre wächst | P5 | S2 hat die Mengen beantwortet; Rest sind Speichergrenzen je Konto (R37.10) |
| 38 | `nb_offen_gesamt()` zählt über Zeilen | Backlog-Runde | kleine Optimierung |
| 40 | 55 Altklassen der Streichliste austragen | Backlog-Runde | vor dem nächsten CSS-Umbau |
| 41 | Sechs Klassen ohne Regel | Backlog-Runde | Gestaltungsentscheidung mit Mockup |
| 42 | Drei Unicode-Symbole im Markup | Backlog-Runde | Gestaltungsentscheidung |
| 43 | GPS-Spur und Phasenkoordinaten im Klartext | P6 (Weg B) und Backlog-Runde (Weg C) | Vorstudie `docs/konzepte/Konzept-V1-Ortsdaten.md`: Weg C (Zusage in `CLAUDE.md` 4, Technik und Datenschutztext ehrlich eingrenzen) jetzt; Weg B (Schlüssel auf das Gerät) entscheidet der R17-Review; drei Fragen an den Auftraggeber in Abschnitt 6 |
| 44 | Sprungliste bei vielen Rettungsmitteln | Backlog-Runde | Mockup `docs/mockups/N1-sprungliste.html` liegt, Freigabe fehlt |
| 45 | Dritte Kartengröße | nach v1.0 | ohne Mockup, ohne Bedarf |
| 46 | Altformat der Sicherung abschaffen | P6 | Stichtag NaDoku 1.0 |
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
| 80 | Auswertung der Gerätestatistik (Rest von 59) | **P5** | Speicherung erledigt mit Web 12.9.0; die Datenschutzerklärung ist Vorbedingung der Auswertung (Schritt 10) |
| 62 | Logodateien mit alten Farbwerten | Backlog-Runde | `Design.md` 2.5 mitziehen |
| 63 | Sperrvermerke des Schnitts in der Konto-Sicherung | S4-Rest | `Backup-Format.md`, Kreisläufe |
| 64 | Bedienhöhe Android | **erledigt** (S4-Merge, Android 0.7.7) | 48 dp in beiden Modulen, `CLAUDE.md` 5 unterscheidet Web und Android (R58, E-S4-77) |
| 65 | 14 Fassungshinweise, AGP 9 | Backlog-Runde | eigene Runde nach dem S4-Rest, nur `android/` |
| 66 | `watch/` läuft nicht durch die Wortliste | S5 | mit der Uhr-Auslieferung |
| 67 | `csrf_check()` ohne API-Zweig | P5 | CSRF-Umfeld (R21) |
| 68 | Vorschlagsfelder über `<datalist>` zeigen mobil nichts (Crew-Felder, Zielklinik, alle weiteren) | Backlog-Runde | neu; alle Felder prüfen, Muster ist das Ortsfeld aus S3 |
| 69 | Kurzname je Rettungsmittel | Backlog-Runde | P3-Zulieferung, jetzt nummeriert |
| 70 | „Auf der Karte setzen" für Standorte | Backlog-Runde | P3-Zulieferung, jetzt nummeriert |
| 71 | Regionen mit Unteradmins | nach v1.0 | verworfen, festgehalten (R39) |
| 72 | Richtungspfeile auf der Spur zeigen teils falsch | Backlog-Runde | neu; wahrscheinlich `transform` auf einem Inline-`<span>` (`geo.js`, `.geo-pfeil`), Sichtprüfung Pflicht |
| 73 | Filterknöpfe der NutzerInnen-Liste brechen in zwei Zeilen | S8 | am Baustein, nicht an der Seite |
| 74 | Bedienhöhe 44 px am Schreibtisch: nötig oder schmaler? | S8 | Entscheidung im S8-Konzept; berührt `CLAUDE.md` 5, `Design.md`, `tools/screenshots/` |
| 75 | Unterpunkte des Admin-Menüs fett und nicht einklappbar | S8 | mit der Menüstruktur |
| 76 | Demo-Reset läuft alle 30 Minuten, auch ohne Änderung | Backlog-Runde | erst messen (Laufzeit, Last), dann entscheiden |
| 77 | Wartungsseite `update.php` in Unterseiten aufteilen | S8 | Schnitt im Konzept; Ort der Migrationsliste hängt an R60 |
| 78 | Wertekasten zeigt Cron-Adresse und Token in Kopplungscode-Größe | S8 | `.codeblock-wert` nutzt `--groesse-5`; zweite Stufe für lange Werte; darf vorab in der Backlog-Runde laufen |
| 79 | Sicherungsoptionen: Begriffe und Optionen gewachsen wie Wildwuchs | S8 | Bestandsaufnahme über Kontoseite, Sicherungsseite, Ziele, Komplettsicherung, Wartung |

## 6. Offene Abnahmen und Zuarbeiten

Was der Auftraggeber tun oder liefern muss. Gestrichen nach R55: die
P0-Bedienprüfung und die P2-Prüfliste bis auf Punkt 4.1.

| Was | Wofür | Wann |
|---|---|---|
| Neues NEF-Logo und -Favicon | P3, Logo-Wahl (Platzhalter liegt) | vor P6 |
| Impressums- und Datenschutztext der Installation über den Editor eintragen | P3 (R32) | vor P6; Datenschutztext dann mit der Grenze der E2E (Nr. 43, Weg C) |
| Sichtprüfung in WebKit und Firefox (Symbole am Dateiverweis) | P3-Abnahme | gelegentlich |
| Prüfliste S2 (12 Punkte), darunter **die Probe-Wiederherstellung der ganzen Installation** auf einem Wegwerf-Webspace | S2-Abnahme, danach halbjährlich | wichtigster offener Punkt; blockiert nichts |
| Zugangsdaten je eines echten FTP-, FTPS- und SFTP-Ziels; ein Klick auf „Verbindung prüfen" | S2 Sicherungsziele | — |
| Bestätigung, dass SMTP auf Produktiv eingerichtet ist | S2 Warnmails | — |
| Bilderlauf für die zweite Logo-Wahl; Autosuche gegen den echten Photon; Bedienzustände | S3-Reste | gelegentlich |
| Prüfliste S4 (1, 2, 3, 5) am echten Diensttag | Schritt 1 | nach dem Merge |
| ~~**Adresse der Connect-IQ-Gerätedateien (`CIQ_GERAETE_URL`)**~~ — **geliefert am 02.09.2026.** `server/geraetemodelle.php` trägt jetzt 325 Teilenummern auf 173 Modelle (Web 12.9.1). Die Adresse selbst steht weiterhin **nicht** im Repositorium — sie gehört in die Umgebungsvariablen der Arbeitsumgebung, nicht in eine Datei | Schritt 2 (S6) | **erledigt** |
| **Abnahme S6:** je eine Kopplung mit Garmin-Uhr und Handy-App (zeigt die Liste Art und Modell?), dazu eine Sitzung über 30 Minuten mit Bedienung (kein Dialog) und ein Leerlauf darüber (Abmeldung) | Schritt 2 (S6) | nach dem Deploy, zusammen mit `update.php` |
| **Datenschutzerklärung um die Gerätekennung ergänzen** — seit Web 12.9.0 wird beim Koppeln Art und Modell erhoben; Backlog Nr. 80 macht die Nennung zur Vorbedingung der Auswertung. Der Text entsteht nach R60 aus einer Bestandsaufnahme des gesamten Projekts | Schritt 10, vor v1.0 | vor jeder Auswertung (P5) |
| **Signaturschlüssel des APK verwahren** — erzeugt am 31.08.2026 (RSA 4096, Zertifikat `078c…ad64`, gültig bis 2056), am 02.09.2026 an den Auftraggeber übergeben; er lag bis dahin nur im Ablagefach der Arbeitssitzung | Schritt 6 und jede spätere Auslieferung | **sofort** — ohne genau diesen Schlüssel ist jede spätere Fassung für Android eine andere App |
| Data Layer Uhr↔Handy auf **echter Hardware** — zwischen zwei Emulatoren nachweislich nicht prüfbar (die Wear-OS-Companion-App des Telefons ist im Baucontainer nicht zu beschaffen) | Schritt 6 | mit der Wear-OS-Uhr |
| Dienst-Test mit der Handy-App auf dem S24 (zwei bis drei Runden) | Schritt 6 | nach dem ersten APK |
| Wear-OS-Uhr für den Gerätetest | Schritt 6 | wenn vorhanden; blockiert nichts |
| DNS-Eintrag und TLS für `nadoku.gen-em.org` | Schritt 5 | vor dem Uhr-Build |
| Freigabe des S5-Konzepts | Schritt 3 | — |
| Drei Fragen aus `Konzept-V1-Ortsdaten.md` (Schutzbedarf der Spur; Passwortwechsel bei nicht synchronisierten Uhr-Daten; Stichtag oder rückwirkend) | Nr. 43, P6 | vor dem R17-Review |
| Freigabe des S8-Konzepts und seiner Mockups; darin die Entscheidung zur Bedienhöhe am Schreibtisch (Nr. 74) | Schritt 7 | — |
| Hosting-Entscheidung (Cron/SSH, DB-Kontingent, `max_user_connections`, DDoS-Schutz, Verschlüsselung at rest) | P5-Konzept | vor Schritt 9 |
| Staging-Installation samt FTP-Zugang | P5-Beginn | vor Schritt 9 |
| SPF/DKIM/DMARC der Versanddomain, Bounce-Postfach | P5 | vor der P5-Abnahme |
| Nutzungsbedingungen, AVV, Datenschutzerklärung des Dienstes, ggf. mit rechtlicher Prüfung | Öffnung (R41) | vor der ersten Welle |
| Planungsgespräch v1.0: Code-Review-Umfang, Aufteilung in Repositorien, Auslieferungskette, Update-Weg (R59, R60) | Schritt 10 | nach P5, vor jedem P6-Paket |
| Anforderungsgespräch Doku-Neufassung | P6 | vor dem P6-Konzept, kann Teil von Schritt 10 sein |
| Wellenplan der Öffnung | Betriebsübergang | vor der Öffnung |
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
| R5 | Gespeicherte Namen bleiben; Ausnahmeliste in P6 beschließen | gilt; Liste zugeliefert und leer |
| R6 | Backlog-Zuordnung (alt) | überholt durch Abschnitt 5 |
| R7 | Ordnerumbau vor P3 | gegenstandslos (E-A6-12) |
| R8 | Gründerfarben präsenter | erledigt in P3 (`Design.md`) |
| R9 | Registrierung in drei Betriebsarten plus Sicherheitspaket | gilt, P5 (konkretisiert in R37) |
| R10 | Rollen- und Sichtbarkeitsmodell, auch was der Admin nicht kann | gilt, P5 (R38) |
| R11 | Kein Migrationspfad; v1.0 liest die 7.x-edbak; Referenzdatei liegt | gilt; Abnahme in P6; seit R60 als einmaliges Einspielen über ein Wegwerf-Formular |
| R12 | Weitere Clients: Basisfähigkeit, Vertragsreview in P6 | gilt; Payloads und Texte erledigt. **Abschnitt 1a in zwei Stufen:** S6 hat ihn auf den heutigen Stand gebracht (Fassung 1.4 — beide Kopplungsformen, was der Server davon speichert, Präfixe der Android-Apps); **S5 schreibt ihn nach E-R49-7 neu**, weil sich der Kopplungsweg selbst umkehrt. Wer 1a liest, liest bis dahin die S6-Fassung |
| R13 | Versionshistorische Kommentare am v1.0-Schnitt ersetzen | gilt, P6 (Liste Konzept P2, 10.3) |
| R14 | Konzepte mit Fable, mechanische Pflege ohne | gilt |
| R15 | Changelog ab v1.0 als Stichpunkte | gilt, P6 |
| R16 | Doku-Neufassung zu v1.0 mit Screenshots; Anforderungsgespräch vorher | gilt, P6 |
| R17 | Bug- und Sicherheitsreview mit Fable vor v1.0 | gilt, Eingang von P6 |
| R18 | Konzept im Projektraum, Umsetzung in Claude Code | gilt |
| R19 | Mengenbremse `ingest.php`: Grundsatzfrage und vier Randbedingungen; Messung liegt | gilt, P5 |
| R20 | Sofortpaket Nr. 22 (Altersfeld maskieren) | erledigt (Web 7.2.1) |
| R21 | Backlog-Zuordnung nach P0 | überholt durch Abschnitt 5 (csrf_check ist Nr. 67) |
| R22 | Papierkorb in beiden Sicherungen | erledigt (S1, Web 8.0.0) |
| R23 | Zwischenpaket S1 | erledigt |
| R24 | Regressionspflicht: beide Kreisläufe je Phase, 0 unerklärt | gilt, dauerhaft |
| R25 | Demo-Konto dauerhaft, einzige E2E-Ausnahme; auf der Kontoseite gesperrt | gilt; P5 und P6 führen es mit |
| R26 | Backlog-Zuordnung nach P1 | überholt durch Abschnitt 5 |
| R27 | Prüfmittel Wiederherstellungsprobe und Papierkorb-Mischfall | gilt, dauerhaft |
| R28 | Prüfmittel Wortliste | gilt, dauerhaft |
| R29 | Uhr-Umbenennung in P6 | erledigt vorzeitig (R48, Uhr 2.0.0) |
| R30 | Nacharbeit zu P2 statt Backlog | erledigt |
| R31 | Support-Adresse konfigurierbar (P5), Namensbeispiele raus (P6), Farbnamen bleiben | gilt |
| R32 | Impressum und Datenschutz als editierbare Seiten | erledigt in P3; Felder in die Admin-Optionen (P5) |
| R33 | Servicemodell mit Abonnements | gilt, P5 |
| R34 | Zwischenpaket S2 | erledigt |
| R35 | Prüfmittel Messstand | gilt, dauerhaft |
| R36 | Zielbild Dienstbetrieb, keine Telemetrie, Hosting-Entscheidung vor P5 | gilt |
| R37 | Konto-Lebenszyklus und Registrierungs-Sicherheitspaket (elf Punkte) | gilt, P5 |
| R38 | Support-Rolle, Admin-TOTP, Audit, Dashboard im Minimalumfang | gilt, P5 |
| R39 | Zentrale Stammdaten entfallen; Regionen-Modell verworfen | gilt, P5; Regionen als Nr. 71 festgehalten |
| R40 | Deploy-Umbau: Staging ab P5, Neuaufsetzen am P6-Schnitt, CI-Prüftor, Torwächter | gilt |
| R41 | Recht und Betreiberorganisation vor der Öffnung; Öffnung in Wellen | gilt |
| R42 | Gerätekennung beim Koppeln | Uhr-Seite erledigt (1.9.0); **Speicherung erledigt (Web 12.9.0, S6)** — drei Spalten statt zwei, begründet im Changelog; Auswertung P5 (Backlog 80) |
| R43 | Zwischenpaket S3 | erledigt |
| R44 | Inhaltsschlüssel führt eine Inaktivitätsfrist wie die Sitzung | **erledigt (Web 12.9.0, S6)** — als Aufräumen, nicht als Behebung des Dialogs (E-S6-4): Der Fristablauf kostete ein stilles Neu-Entpacken, keinen Dialog. Der Dialog kommt vom tabweisen `sessionStorage`, bleibt und steht jetzt im Handbuch |
| R45 | Zwischenpaket S4 mit E-R45-1 bis E-R45-13 | in Arbeit (Schritte 1 und 6) |
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
| R57 | Überlappende aktive Diensttage: Hinweis im Browser (F-S4-D, Weg c) | gilt, S4-Rest |
| R58 | Android-Bedienhöhe 48 dp in beiden Modulen; `CLAUDE.md` 5 ergänzen | gilt, S4-Merge |
| R59 | Vor v1.0 ein Planungsgespräch: Umfang des Code-Reviews, Aufteilung in mehrere Repositorien, Auslieferungskette; Ergebnis ist das P6-Konzept | gilt, Schritt 10 |
| R60 | Ab v1.0 keine Rückwärtskompatibilität, auch bei Updates; v1.0 beginnt mit dem Neuaufsetzen; eine ältere Sicherung wird einmal über ein Wegwerf-Formular eingespielt, danach nie wieder. Der Update-Weg der Installation (Selbstprüfung gegen das Repositorium, Benachrichtigung, Einspielen selbst oder per FTP, Sichtbarkeit der Migrationsliste) wird in der Planung v1.0 entschieden | gilt, Schritt 10 |
| R61 | Zwischenpaket S8 „Einstellungen, Administration und Wartung": ergebnisoffene Sichtung und Neuordnung vor P5, mit Konzept und Mockups; die Sicherungsoptionen, die Menüstruktur und die Aufteilung der Wartungsseite gehören hinein | gilt, Schritt 7 |
| R62 | Konzeptablage `docs/konzepte/` mit Lebenszyklus: Statusblock und Push nach jedem Arbeitspaket, damit andere Instanzen den Stand sehen; nach Freigabe des Abschlusses Erledigt-Zeile hier und Löschung des Konzepts; Prüfdokument bleibt bis zur abgehakten Prüfliste; Bestand bis S3 in `docs/konzepte/erledigt/` | gilt (F16), Regel in 2.2 |

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

## 9. Pflege dieses Dokuments

- **Status** einer Phase: Abschnitt 3 (Tabelle und Block) während der
  Arbeit; nach dem Abschluss eine Zeile in Abschnitt 8 mit Versionen,
  Datum, Dokumenten, wesentlichen Änderungen und Resten; die Reste nach
  Abschnitt 6, die Backlog-Zuordnung in Abschnitt 5 nachziehen.
- **Programmentscheidungen** bekommen die nächste R-Nummer in Abschnitt 7,
  kompakt; die Begründung steht im betroffenen Konzept. Nie umnummerieren.
- **Keine Fassungsvermerke im Kopf.** Jede Änderung ist eine Zeile in
  Abschnitt 10; der Kopf trägt nur die Fassungsnummer und den Stand.
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
| **19** | **02.09.2026** | **Modelltabelle gefüllt** (Web 12.9.1): 325 Teilenummern auf 173 Modelle aus den gelieferten Gerätedateien — die Zuarbeit aus Abschnitt 6 ist erledigt. Die echten Daten haben eine geratene Annahme widerlegt: `geraet_modell` geht von 64 auf 191 Zeichen (E-S6-7, zweite Migration `2026_09_02_geraetemodell_breiter`), weil die Dateien Sammelnamen bis 156 Zeichen führen; gekürzt wird erst für die Anzeige. Dateiweite Wortlisten-Ausnahme für die erzeugte Tabelle (89 Treffer, wie in ihrer LIESMICH vorhergesagt). Register 40 = 40. **Vollständigkeit 266 → 272** — die sechs liegen sämtlich in „Unicode-Zeichen als Symbol im Markup": vier sind Auslassungszeichen in Kommentaren (dieselbe Verwendung wie an drei älteren Stellen in `version.php` und `update.php`), zwei die Kürzungsmarke im Code, die `admin_user.php` schon vor S6 benutzte. Kein neuer Befundtyp; die Kategorie ist Bestand aus P3. |
| **18** | **02.09.2026** | **S6 gebaut** (Schritt 2, Web 12.9.0): drei Spalten an `devices` statt der in R42 genannten zwei (E-S6-1), `pair.php` liest beide Kopplungsformen über die neue `geraete_lib.php`, Modelltabelle als erzeugte Datei mit eigenem Werkzeug samt Nachauflösen (E-S6-6), Art und Modell in beiden Gerätelisten, R44 angeglichen (gleitende Schlüsselfrist) und dabei die Wirkungsaussage des R44-Eintrags berichtigt (E-S6-4, neues Prüfmittel `tools/fristprobe/`: 17 gegen 1 Neu-Entpackung je Schicht); Gerätekennung in beiden Listen gekürzt (E-S6-5, behebt einen Überlauf, den es schon vorher gab); JSON-Vertrag auf Fassung 1.4 (beide Formen, Speicherung, Android-Präfixe — der Nachtrag hing an R42), `Lizenzen.md` 7a für die erzeugte Tabelle; Backlog 59 erledigt, Rest als 80 angelegt; drei Zuarbeiten in Abschnitt 6 (Gerätedateien, S6-Abnahme, Datenschutzerklärung). Migrationsregister gegengezählt (39 = 39). |
