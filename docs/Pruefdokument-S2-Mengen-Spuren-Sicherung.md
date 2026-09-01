# Prüfdokument S2 — was **du** noch prüfen musst

Zur Phase S2 („Mengen, Spurspeicherung und Sicherung"). Das Prüfprotokoll im
Konzept beantwortet *„ist es belegt?"*; dieses Dokument beantwortet *„was muss
ich noch tun?"*.

> **Stand: AP0 bis AP9 sind gebaut**, AP10 (Abschluss) läuft. Was hier steht,
> ist damit vollständig für die Sache — offen sind Doku-Nachträge und die
> Statuszeile im Rahmenplan, nicht Funktionen.
>
> **Der wichtigste Punkt dieses Dokuments ist Nummer 11 der Prüfliste:** den
> Wiederanlauf einmal wirklich durchspielen. Alles andere ist hier belegt; das
> nicht, und es lässt sich hier auch nicht belegen. Kapitel 1.1 sagt, warum.

---

## 1. Was NICHT geprüft werden konnte — und warum

Das steht an erster Stelle und nicht in einer Fußnote.

### 1.1 Der Wiederanlauf ist nie an einem echten Ausfall geprobt worden (AP8)

Das Komplettbackup **gibt es** seit Web 12.2.0, und der ganze Zyklus ist
maschinell gefahren (Abschnitt 2.7). Was fehlt, ist die eine Prüfung, die
zählt: **ein Wiederanlauf unter den Bedingungen, unter denen er stattfindet.**

Alles hier ist auf einer Maschine gemessen worden, auf der die Datei, der
Schlüssel und die Datenbank griffbereit lagen. Der Ernstfall sieht anders aus:
neuer Webspace, andere Datenbankzugangsdaten, die `config.php` aus einem
Ordner, den seit Monaten niemand geöffnet hat — und niemand, der nachsehen
kann, wie es gemeint war. **Prüfliste Punkt 11** führt genau das durch; er ist
der wichtigste Punkt dieses Dokuments.

Ebenfalls offen: **`wiederherstellen.php` ist nicht angeklickt worden.** Die
Seite ist über `curl` gegen einen echten PHP-Server gefahren — mit Formularen,
Sitzung, Nachweis, Mehrfachdurchgang und allen Sonderfällen — und in acht
Breiten fotografiert. Ein Klickweg dafür bräuchte eine **leere** Datenbank und
liesse sich deshalb nicht neben der laufenden Installation bauen.

### 1.2 Kein echtes Sicherungsziel im Internet

Aus dem Behälter, in dem gearbeitet wurde, gehen **nur Verbindungen auf Port
443** hinaus. Nachgemessen mit `github.com:22` als Gegenkontrolle — ein Port,
der ganz sicher offen ist, und auch er wird abgewiesen. Es ist eine
Portsperre, keine Eigenschaft eines Ziels.

Geprüft wurde deshalb gegen Server auf 127.0.0.1, und zwar gegen **zwei**
Sätze: pyftpdlib/paramiko und **vsftpd/OpenSSH**. Das deckt viel ab (siehe
6.2), aber nicht: dein Ziel, deinen Hoster, deine Firewall, dein Zertifikat.
**Prüfliste Punkt 3.**

### 1.3 Keine unbelastete Maschine für die Zeitmessungen

Alle Zeiten aus AP9 und die Werte des Messstands entstanden, während im selben
Behälter mehrere Agenten liefen. Der **Vergleich** trägt (beide Stände
unmittelbar nacheinander, gleiche Bedingungen), die **absoluten** Zahlen sind
nach oben verzerrt. Die Zielwerte aus E-S2-24 sind gehalten — aber mit
unbekannter Reserve. **Prüfliste Punkt 8.**

### 1.4 Weiteres, das offen bleibt

| Was | Warum nicht |
|---|---|
| Die Warnmail bei erreichter Speichergrenze | Ohne eingerichtetes SMTP ist der Hinweisweg geprüft, der Versandweg nicht |
| Eine wirklich volle Platte | Geprüft ist der nächstliegende Fall: ein Verzeichnis ohne Schreibrecht. Derselbe Weg durch den Adapter, andere Ursache |
| Eine abreissende oder langsame Leitung | Alles lief über Loopback. Der Abbruch mitten in der Datei ist *nachgestellt* (gekürzte Datei am Ziel), nicht erlebt |
| Der Versand am Cron-Auslöser | Über die Befehlszeile gefahren, am eingerichteten Zeitdienst nicht |
| Ein Freitextfilter über 5 000 entschlüsselte Einträge | Gemessen wurde der Aufbau der Suche ohne Filter (Backlog Nr. 51) |
| Z2-Menge (500 Konten × 600 Einsätze) | Bereits in AP0 als nicht messbar vermerkt |

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

### 2.1 Die drei Kreisläufe (R24) — vollständig

| Kreislauf | Mittel | Einzelvergleiche | unerklärt | erwartet |
|---|---|---:|---:|---:|
| `edbak` (Fassung 4 → frisches Konto → Fassung 4) | `kreislauf.py` | **252 882** | **0** | 16 |
| `edbak-alt` (7.x-Datei hinein, Fassung 4 heraus) | `kreislauf.py` | **287 282** | **0** | 560 |
| `csv` | `kreislauf.py` | **8 797** | **0** | 859 |

### 2.2 Die Proben

| Probe | Was sie prüft | Erwartungen | offen |
|---|---|---:|---:|
| `wiederherstellungs-probe` | Grenzfälle von `edbak_restore()`, Adminpaket Fassung 2, Speichergrenze, Auftrag | **76** | 0 |
| `versandprobe` (neu, AP7) | Die drei Sicherungsziel-Adapter, gegen **beide** Gegenstellensätze | **115** je Lauf | 0 |
| `gpxprobe` | GPX gegen das amtliche XSD, Punkt für Punkt gegen Referenzdateien | **75** | 0 |
| `containerprobe` | Containerfassung 4 über PHP → Chromium → Python | **32** | 0 |
| `spurprobe` | Rundlauf des Blob-Formats SPUR1 über den ganzen Bestand | **25** | 0 |
| `jobprobe` | Job-Rahmen: drei Auslöser, Rückstand, Sperre, Huckepack | **24** | 0 |
| `ingestprobe` | Uhr-Schnittstelle nach der Ausdünnung, über echtes HTTP | **24** | 0 |
| `freigabeprobe` (neu, AP6) | Der Freigabeweg **mit** Wiederherstellungsschlüssel | **14** | 0 |
| `papierkorb_misch` (R27) | Papierkorb-Mischfall über Sicherung und Rückspielung | **15** Einzelprüfungen | 0 Befunde |
| `rechtstexte` | Angriffsprobe des Markdown-Renderers | **81** Proben + 65 Ausgaben | 0 |
| `maskierungs-probe` | Angriffswerte werden Text, legitime Werte bleiben gleich | 3 Kriterien | 0 |

### 2.3 Die Prüfmittel der Oberfläche und der Sprache

| Mittel | Zahl |
|---|---|
| `tools/wortliste/` (R28) | **0 Treffer** ausserhalb der Ausnahmen, **0 ungenutzte** Ausnahmen, **0 durchgerutschte** Fallen |
| `tools/vollstaendigkeit/` | **260 Befunde** — unverändert über AP6, AP7, AP9 hinweg |
| `tools/screenshots/kontrast.py` | **21 Paare** gerechnet, **0 verfehlt** |
| `tools/screenshots/` (berührte Seiten) | 16 Bilder, **16 verschiedene Prüfsummen**, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |
| `tools/design/tabellen.py` | alle **vier** erzeugten Tabellen zeichengleich mit der Ausgabe |

### 2.4 Die Mengen (R35, E-S2-24) — Drossel 6×

Am Konto mit **5 002 Einsätzen**. Die Spalte „AP0" ist die Ausgangsmessung;
sie war an zwei Stellen zu hoch, weil das Prüfmittel ein Zeitlimit
mitgemessen hat (siehe 6.1).

| Größe | AP0 | jetzt | Ziel |
|---|---|---|---|
| Startseite, 500 Tagesverweise | 1,36 s | 1,39 s | — |
| Tagesansicht bis zur gezeichneten Spur | ~~4,81 s~~ | **1,17 s** | ≤ 3 s ✓ |
| Suche bis zur ersten Trefferanzeige | ~~4,53 s~~ | **3,81 s** | ≤ 5 s ✓ |
| Sicherung erstellen | 109,8 s | **42,21 s** | ≤ 5 min ✓ |

Und die Speichergrößen gegen das Z3-Budget von **64 MB**:

| Vorgang | vorher | jetzt |
|---|---|---|
| Nutzersicherung, 5000er-Konto | 58 MB Halde · 9,39 MB JSON | **45 MB · 2,30 MB** |
| Admin-Sicherung, 5000er-Konto | **1 077,6 MB** · Abbruch bei 64M | **24,0 MB · läuft durch** |
| Admin-Sicherung, Demokonto | 28,1 MB · 2,14 MB Datei | **4,0 MB · 0,22 MB** |
| Versand von 64 Paketen (63,9 MB) | — | **2,0 MB** (FTP/FTPS), **8,0 MB** (SFTP) |

### 2.4a Die Serverseite (R35, Serverprobe)

| Größe | gemessen | Einordnung |
|---|---|---|
| `edbak_build()` **am Stück**, mit Punktlisten | 7,37 s · 94,28 MB Paket · **1 077,6 MB Spitze** | der alte Weg, zum Vergleich mitgemessen |
| `edbak_build()` **in Fenstern** zu 250 | 1,05 s · 44 Fenster · grösstes **0,44 MB** · **10 MB von 64** | der Weg seit Web 11.1.0 |
| Waisen-Vollscan (B-S2-05) | **0,109 s** über die ganze Tabelle, 0 Waisen | vorher Minuten befürchtet |
| Datenbank gesamt | 187,3 MB | alle Konten des Prüfstands |

**Zur Spurgrösse eine Klarstellung, weil die Rohzahl in die Irre führt.** Die
Serverprobe meldet „8,89 MB je 1000 Einsätze" und warnt selbst dazu: Das ist
**belegter Tabellenplatz** einschliesslich der Seiten, die frühere Löschungen
freigegeben haben. Über die Blobs gezählt:

| Stufe | Spuren | Punkte | Blob |
|---|---:|---:|---:|
| 2 (verlustfrei) | 1 854 | 910 617 | 3,14 MB |
| 3 (ausgedünnt) | 2 281 | 361 927 | 2,37 MB |

**1,10 MB je 1000 Einsätze** — gegen den Zielwert **≤ 3 MB** aus E-S2-24, und
zeichengleich mit der Rechnung aus AP3 (1,09 MB). Dazu 242 155 Punkte, die
noch als Zeilen im Eingangspuffer liegen und auf die Verdichtung warten; das
ist der vorgesehene Zustand und kein Rückstand.

### 2.5 Der Versand (AP7)

64 Pakete zu zusammen 63,9 MB aus 33 Kontoordnern:

| | Nachbauten | vsftpd / OpenSSH |
|---|---|---|
| FTP | 0,13 s | **0,35 s** |
| FTPS | 0,68 s | **1,85 s** |
| SFTP | 3,08 s | **0,68 s** |

Alle angekommenen Dateien byteweise mit dem Original verglichen: **192 gegen
die Nachbauten, 64 gegen OpenSSH, 0 Abweichungen.** Ein zweiter Lauf sandte
**0** Dateien (0,19 s). Eine am Ziel auf 1 000 Byte gekürzte Datei wurde beim
nächsten Lauf **einzeln** erneut geschickt (1 von 64).

Der Fingerabdruck-Riegel gegen einen **echten** zweiten OpenSSH-Hostschlüssel:
Verbindung bricht ab, Meldung nennt beide Abdrücke, und das Anmeldeprotokoll
des Servers steht **vorher wie nachher auf 46 Zeilen** — es ging kein Passwort
hinaus. Der errechnete Fingerabdruck ist zeichengleich mit dem von
`ssh-keygen -lf`.

### 2.6 Die Suche (AP9)

| | vorher | nachher |
|---|---|---|
| `crypto.subtle.importKey` je Suchlauf | 4 880 | **1** |
| `entschluessleListe` | 1 954 ms | **958 ms** |
| geschützte Spalten lesbar | 4,11 s | **3,77 s** |
| entschlüsselte Einträge | 5 002 (4 880 mit Block) | unverändert |
| PBKDF2 auf der Suchseite | 0 | **0** |

---

### 2.7 Die Komplettsicherung (AP8)

Gemessen am 5000er-Bestand: **1 121 802 Zeilen in 34 Tabellen**, ohne
Drosselung.

| | Wert | Ziel |
|---|---|---|
| Erzeugen | 8,5 s in **14 Häppchen** | in Häppchen, nie am Stück (E-S2-20) |
| PHP-Speicherspitze je Häppchen | **26 MB** | ≤ 64 MB (Z3) |
| SQL / versiegelt | 122,5 MB → **43,7 MB** | — |
| Längste Zeile | **1 048 566 Byte** | ≤ 1 MB je INSERT-Stapel |
| Öffnen (175 Blöcke) | 0,05 s, Spitze 4 MB | — |
| Auspacken über den Rückweg | 1,24 s | — |
| Einspielen | 784 Anweisungen in 6,0 s | — |

**Der Rundlauf ist auf drei Wegen gefahren** und dreimal verglichen —
`mysql` von Hand, die Probe zeilenweise, `wiederherstellen.php` gegen einen
echten PHP-Server:

| Vergleich Quelle ↔ Rückspielung | Ergebnis |
|---|---|
| Tabellen | **34 von 34** |
| Zeilenzahlen | **34 von 34** |
| `SHOW CREATE TABLE` zeichengleich | **34 von 34** |
| `CHECKSUM TABLE … EXTENDED` gleich | **34 von 34** |
| Sammelprüfsumme aller Spur-Blobs | gleich |
| Sammelprüfsumme aller `lat`/`lon`/`ele` | gleich |

Über `wiederherstellen.php` mit einem Budget von 4 s: **6 Durchgänge**, jeder
setzte dort auf, wo der vorige aufhörte (48 → 58 → 69 → 78 → 90 → 100 %).

**Was die Prüfmittel sagen:**

| Mittel | Zahl | was es gemessen hat |
|---|---|---|
| `tools/komplettprobe/probe.php` | **76 Erwartungen, 0 offen** | Dump, Siegel, Passphrase, Form, Rückspielung, Wiederanlauf, Aufbewahrung, Versand |
| `tools/komplettprobe/klickweg.mjs` | **17 Prüfungen, 0 Befunde** | Adminseite im Browser: Dialog, Lauf, beide Downloads mit Inhaltsprüfung, kurze Passphrase, Zeitplan |
| `tools/versandprobe/probe.php` | **115 Erwartungen, 0 offen** | Regression nach den Änderungen an `sicherungsziel_lib.php` |
| Bilderlauf, 2 Seiten × 8 Breiten | **16 Bilder, 0 Überlauf** | nach einem Fund, siehe unten |
| Wortliste | **0 / 0 / 0** | Treffer, ungenutzte Ausnahmen, Fallen |
| Vollständigkeit | **260 Befunde** | unverändert gegenüber dem Stand vor AP8 |
| Kontraste | **21 Paare, 0 verfehlt** | — |

**Vier Fehler, die die Prüfmittel gefunden haben** und nicht das Lesen:

1. Ein abgebrochenes Häppchen hätte beim nächsten Lauf ein **zweites
   `DROP TABLE`** derselben Tabelle geschrieben; beim Einspielen wäre
   weggeworfen worden, was das erste Häppchen eingefügt hat (F-S2-H).
2. Der Neuanlauf bei verschwundenem Baustand lief in ein `count(null)` — und
   das ist genau der Zweig, der **nach einer Wiederherstellung** greift
   (F-S2-I).
3. Die Schranke „leere Datenbank" galt vor **jedem** Durchgang; leer ist die
   Datenbank aber nur vor dem ersten. Abbruch bei **91 %** (F-S2-J).
4. Der Knopf „Versiegelt herunterladen" stand in einer `.fld-reihe` statt im
   Fussbereich und schob die Seite bei 360, 390 und 420 px auf
   (**+74 / +59 / +44 px**).

Dazu zwei Funde ausserhalb der Proben: `gzdecode()` und `inflate_add()` lesen
nur das **erste** gzip-Glied und melden dabei nichts (gemessen: 13 573 234
statt 122 469 394 Byte) — die Anwendung benutzt deshalb ausschliesslich
`gzopen()`/`gzread()`. Und der Nachweis der Wiederherstellung entstand bei
**jedem** Aufruf, auch auf laufenden Installationen, und fehlte in
`server/.htaccess`.

**Eine Zahl, die erklärt gehört:** Der Klickweg meldet 1 082 238 statt
1 121 802 Zeilen. Der Unterschied sind **39 690 Spurpunkte**, die der
Verdichtungsjob während des Laufs aus `track_points` in `track_blobs`
geschoben hat (+125 Blobs) — er läuft huckepack an den Anfragen mit. Verloren
ist nichts; die Zahl ist kleiner, weil dieselben Punkte jetzt in weniger
Zeilen stehen. Genau das ist der Zweck von AP1 und AP3.

---

## 3. Was im Browser geprüft wurde

- **Sichern und Wiederherstellen** über den regulären Weg, Fassung 4, am
  5000er-Konto und am Referenzbestand.
- **Der Freigabeweg mit Wiederherstellungsschlüssel:** Kasten erscheint,
  falscher Schlüssel wird abgewiesen und schreibt **nichts**, richtiger
  schlüsselt um — der Chiffretext im Ziel ist ein **anderer** und öffnet sich
  mit dem Schlüssel des Zielkontos.
- **„Alle sichern"** über 31 Konten: 31 von 31 in 18,3 s, 0 Konsolenfehler.
- **Sicherungsziele (AP7):** Serverschlüssel anlegen → Ziel anlegen →
  Verbindung prüfen (6 Schritte) → falsches Passwort → versenden (64 Dateien,
  63,9 MB) → Rückstand danach 0. 0 px waagerechter Überlauf, 0 Seitenfehler,
  Bedienelemente 44 px.
- **Der Wartungslauf** über die Befehlszeile führt `versand` mit auf
  (`fertig · erledigt 64 · Rückstand 0`).
- **Komplettsicherung (AP8):** „Jetzt sichern" mit Bestätigungsdialog, Lauf
  bis zur Meldung, beide Downloads (Inhalt geprüft: gzip-Magie bzw. `EDKOMP1`
  und `"pbkdf2"` im Kopf), Abweisung einer zu kurzen Passphrase, Zeitplan
  setzen und zurückstellen. **17 Prüfungen, 0 Befunde, 0 Konsolenfehler**
  (`tools/komplettprobe/klickweg.mjs`).
- **`wiederherstellen.php`** gegen einen eigenen PHP-Server auf einer leeren
  Datenbank — über `curl`, nicht angeklickt: Nachweis richtig und falsch,
  Auspacken, Einspielen in 6 Durchgängen, abgeschnittene Datei, Fassung mit
  Passphrase (richtig und falsch), und die Schranke „ist in Betrieb" auf einer
  gefüllten Datenbank.

---

## 4. Prüfliste — was du selbst tun musst

Je Punkt: der Bedienweg, das erwartete Ergebnis, und **woran ein Scheitern zu
erkennen ist**.

### ☐ 1. Nach dem Deploy: `update.php` aufrufen

**Weg:** Einstellungen → Administration → Wartung → *Updates jetzt anwenden*.

**Erwartet:** Die Migration `2026_09_01_sicherungsziele` steht auf „erledigt";
darüber die Migrationen aus AP1 bis AP3 (`track_blobs`, `jobs`,
`letzter_punkt_am`), falls noch nicht gelaufen.

**Scheitern erkennst du daran:** Die Seite *Sicherungsziele* zeigt statt der
Liste einen roten Hinweis „Die Tabelle für die Sicherungsziele fehlt noch".
Ohne die Migration tut die ganze Seite nichts — sie sagt es aber.

### ☐ 2. Den Serverschlüssel anlegen und ins Wiederanlaufpaket legen

**Weg:** Einstellungen → Administration → **Sicherungsziele**. Ist kein
Schlüssel eingetragen, steht dort die Karte *Serverschlüssel fehlt* mit einem
Knopf.

**Erwartet:** Nach dem Klick verschwindet die Karte, und die Meldung sagt, dass
der Schlüssel jetzt in `config.php` steht. Ist die Datei nicht beschreibbar,
zeigt die Seite stattdessen die fertige Zeile — **genau eine** davon eintragen;
bei jedem Neuladen steht dort eine andere.

**Danach — und das ist der eigentliche Punkt:** Die Zeile
`'server_key' => '…'` aus `config.php` **getrennt** aufbewahren, zusammen mit
einer Kopie von `config.php` selbst. Ohne ihn sind die Zugangsdaten der Ziele
neu einzutragen (verschmerzbar) und ein versiegeltes Komplettbackup nicht mehr
zu öffnen (nicht verschmerzbar, sobald es AP8 gibt).

**Scheitern erkennst du daran:** Die Karte *Serverschlüssel fehlt* ist nach dem
Klick immer noch da. Dann ist `config.php` nicht beschreibbar — die Zeile von
Hand einfügen, gleich hinter `return [` bzw. `return array (`.

### ☐ 3. Ein echtes Sicherungsziel einrichten und prüfen

**Das ist der Punkt aus 1.2 — er lässt sich nur bei dir prüfen.**

**Weg:** *Sicherungsziele* → **Ziel anlegen**. **SFTP wählen, wenn dein Hoster
es anbietet.** Rechnername, Port, Nutzer, Pfad, Passwort (oder privaten
Schlüssel vollständig einfügen, mit den BEGIN- und END-Zeilen). Dann
**Verbindung prüfen**.

**Erwartet:** Eine grüne Meldung und darunter die Karte *Was die Prüfung getan
hat* mit sechs Schritten: verbunden und angemeldet · Hostschlüssel `SHA256:…`
(nur SFTP) · Probedatei geschrieben · Verzeichnis gelesen · zurückgelesen und
Byte für Byte verglichen · Probedatei wieder gelöscht.

**Scheitern erkennst du daran:** Die Meldung ist rot und nennt den Schritt, bei
dem es aufhörte. Die häufigen Fälle sind auf Deutsch benannt:

| Meldung | Bedeutung |
|---|---|
| „Nutzername oder Passwort stimmt nicht" | genau das |
| „Rechnername, Port und Firewall prüfen" | die Verbindung kam nicht zustande |
| „Der Pfad … ist auf dem Ziel nicht zu erreichen" | Pfad falsch, oder er existiert dort nicht |
| „Das Ziel verweigert den Zugriff … Ist das Verzeichnis beschreibbar, und ist noch Platz?" | Anmeldung ging, Schreiben nicht |
| „Der Server meldet sich mit einem ANDEREN Hostschlüssel" | **erst klären, dann handeln** — siehe Punkt 4 |

**Ein Sonderfall, der dich überraschen kann:** Bei **SFTP** ist `/` die Wurzel
des **Dateisystems**, bei FTP meist dein Heimverzeichnis (der Server sperrt
dich hinein). Trage bei SFTP den vollen Pfad ein, nicht `/`.

### ☐ 4. Den Hostschlüssel bewusst übernehmen

**Weg:** Beim ersten *Verbindung prüfen* eines SFTP-Ziels erscheint unten der
Hinweis „Der Hostschlüssel wurde übernommen".

**Erwartet:** Der angezeigte Abdruck (`SHA256:…`) stimmt mit dem überein, den
dein Hoster nennt — oder mit dem, den du selbst ermittelst:

```sh
ssh-keyscan -p <port> <host> | ssh-keygen -lf -
```

**Woran du merkst, dass du dich nicht darum gekümmert hast:** gar nichts, bis
zu dem Tag, an dem sich dort jemand anderes meldet. Genau dagegen ist der
Abdruck da; er nützt nur, wenn er einmal gegen eine zweite Quelle gehalten
wurde.

### ☐ 5. Den Versand einschalten und einen Lauf zusehen

**Weg:** *Sicherungsziele* → Karte **Versand** → Schalter *Sicherungen
automatisch versenden* → Speichern. Dann oben **Jetzt versenden**.

**Erwartet:** „N Dateien an M Ziele gesendet (… MB)." Danach zeigt die Karte
*Wartet auf den nächsten Lauf* die Zahl **0**.

**Scheitern erkennst du daran:** Die Zeile des Ziels trägt die Plakette
*zuletzt gescheitert* und darunter steht der Grund im Klartext — er bleibt
stehen, bis ein Lauf gelingt.

**Und dann sieh auf dem Ziel nach.** Dort muss je Konto ein Ordner mit der
Kontokennung liegen, darin die `.zip`-Pakete. Auf dem Ziel wird **nie** etwas
gelöscht (Backlog Nr. 49) — plane den Platz entsprechend.

### ☐ 6. Eine Sicherung zurückspielen, die du nicht selbst erzeugt hast

**Der Prüfpunkt, den niemand gern macht und jeder braucht.**

**Weg:** Ein Paket vom **Sicherungsziel** holen (nicht aus
`server/sicherungen/`), in ein Wegwerfkonto einspielen: Einstellungen →
Backup → Wiederherstellen.

**Erwartet:** „N Einsätze übernommen, … In den Papierkorb übernommen: …" — und
die Zahlen stimmen mit denen des Quellkontos überein.

**Scheitern erkennst du daran:** „0 Einsätze übernommen … bereits vorhanden".
Dann war das Zielkonto nicht leer — die Wiederherstellung **ergänzt und
ersetzt nicht**. Nimm ein frisches Konto.

**Wiederholen:** einmal je Halbjahr. Eine Sicherung, die nie zurückgespielt
wurde, ist eine Vermutung.

### ☐ 7. Die Wartung einrichten und einmal zusehen

**Weg:** Einstellungen → Administration → Wartung. Dort stehen die drei
Auslöser (Cron, Token-Adresse, huckepack).

**Erwartet:** Nach einem Lauf zeigt jeder Job „fertig" und einen Rückstand.
`verdichtung` und `ausduennen` tragen die Spurmenge über Wochen ab; das ist
normal und soll so aussehen.

**Scheitern erkennst du daran:** Ein Job trägt dauerhaft einen *letzten
Fehler*. Der steht auf der Wartungsseite und verschwindet erst mit einem
erfolgreichen Lauf.

### ☐ 8. Die Zeiten auf deiner Maschine nachmessen

**Der Punkt aus 1.3.**

**Weg:** An einem Konto mit vielen Einsätzen die Suche und die Tagesansicht
öffnen und die Zeit bis zur ersten Anzeige stoppen — von Hand genügt.

**Erwartet:** Suche unter 5 s, Tagesansicht unter 3 s (E-S2-24), auf einem
Gerät der Klasse, die du tatsächlich benutzt.

**Scheitern erkennst du daran:** Es dauert spürbar länger. Dann ist der Punkt
Backlog Nr. 51 — die Suche verarbeitet 5 000 Einträge, um 200 zu zeigen — und
gehört gemeldet, nicht ertragen.

### ☐ 9. Speichergrenze und Schwellen setzen

**Weg:** Einstellungen → Administration → **Sicherungen** → Karte *Regeln*.
Vorgabe: 2 GB, Schwellen 70 und 90 Prozent.

**Erwartet:** Die Karte *Ablage* zeigt „Belegt: … von …" mit einer blauen
Plakette.

**Scheitern erkennst du daran:** Wird die Grenze erreicht, wird **nicht mehr
gesichert** — und zwar mit einer roten Meldung oben auf der Seite. Es wird
nichts gelöscht und nichts überschrieben. Ohne eingerichtetes SMTP steht die
Warnung **nur** dort; die Mail (1.4) ist ungeprüft.

### ☐ 10. Die erste Komplettsicherung erzeugen und den Zeitplan setzen

**Weg:** Einstellungen → Administration → **Komplettsicherung** →
*Jetzt sichern*. Danach unter *Regeln* den Plan auf **wöchentlich** stellen
(oder täglich, wenn die Datenbank es hergibt) und speichern.

**Erwartet:** Nach dem Klick steht in der Meldung „Die Komplettsicherung ist
fertig: … Zeilen aus … Tabellen, … MB". Auf einem grossen Bestand kann statt
dessen „Der Durchgang ist zu Ende, der Lauf noch nicht" erscheinen — dann
*Fortsetzen* drücken, bis der Lauf fertig ist. Unter *Stände* steht danach
ein Eintrag mit Plakette **jüngster**.

**Scheitern erkennst du daran:**
- **„Es gibt noch keinen Serverschlüssel"** → erst Punkt 2 erledigen. Ohne ihn
  wird bewusst nicht gesichert; unversiegelt wird eine Abschrift jeder Tabelle
  nicht abgelegt.
- **„Die Speichergrenze für Sicherungen ist erreicht"** → Punkt 9. Es wurde
  nichts gelöscht.
- Der Lauf bleibt bei „Fortsetzen" stehen und kommt nicht weiter → die
  Fehlermeldung nennt die Stelle; sie gehört gemeldet.

### ☐ 11. Den Wiederanlauf einmal wirklich durchspielen — der wichtigste Punkt

**Der Punkt aus 1.1.** Er kostet einen Nachmittag und ist der einzige, der
beantwortet, ob die Sicherung etwas taugt. Eine Sicherung, die nie
zurückgespielt wurde, ist eine Vermutung.

**Weg** — auf einem **Wegwerf-Webspace** oder örtlich, **niemals** auf der
Produktion:

1. Leere Datenbank anlegen.
2. Die Anwendungsdateien hochladen.
3. Die `config.php` aus dem Wiederanlaufpaket daneben legen; Datenbankzugang
   anpassen, **`server_key` unverändert lassen**.
4. Eine `.edk`-Datei nach `server/sicherungen/eingang/` legen — am besten
   eine, die du vom **Sicherungsziel** geholt hast, nicht vom alten Server.
5. `wiederherstellen.php` aufrufen, die Kennung aus der Nachweisdatei
   eintragen, *Auspacken und prüfen*, dann *Einspielen* bis 100 %.
6. Anmelden. Dann **Wartung** aufrufen und den Migrationslauf ausführen.
7. Auf `wiederherstellen.php` *Aufräumen* drücken.

**Erwartet:** Nach Schritt 5 „Die Installation steht wieder". Nach Schritt 6
zeigt die Wartungsseite keine offenen Migrationen mehr. Deine Konten,
Diensttage und Einsätze sind da; die Patientendaten öffnen sich mit den
gewohnten Passwörtern.

**Scheitern erkennst du daran** — und die Meldung sagt jeweils, woran es liegt:

| Meldung | was zu tun ist |
|---|---|
| „Diese Installation ist noch nicht eingerichtet" | Schritt 3 fehlt |
| „Die Datenbank antwortet nicht" | Zugangsdaten in `config.php`, oder die Datenbank existiert nicht |
| „Diese Installation ist in Betrieb" | die Datenbank ist nicht leer — Schritt 1 |
| „falscher Schlüssel, falsche Passphrase — oder der Dateikopf ist verändert" | der `server_key` ist nicht der, mit dem versiegelt wurde |
| „Diese Sicherung ist unvollständig — die Endmarke fehlt" | die Datei ist beim Erzeugen abgebrochen; einen älteren Stand nehmen |
| „gescheitert an Anweisung *n*" | halb eingespielt, **nichts zurückgenommen** — Datenbank leeren und von vorn |

**Was du dabei mitprüfst, ohne es zu merken:** ob dein Wiederanlaufpaket
vollständig ist. Fehlt der Serverschlüssel, merkst du es hier — und nicht an
dem Tag, an dem es darauf ankommt.

### ☐ 12. Einen Stand herunterladen und von Hand einspielen

**Weg:** Einstellungen → **Komplettsicherung** → *Herunterladen*. Die Datei
heisst `einsatzdoku-komplett-….sql.gz` und ist **unverschlüsselt**. Dann:

```sh
gunzip -c einsatzdoku-komplett-….sql.gz | mysql -uNUTZER -p LEEREDATENBANK
```

**Erwartet:** Rückgabewert 0, keine Ausgabe. Danach stehen alle Tabellen in
der Zieldatenbank.

**Scheitern erkennst du daran:** `mysql` bricht mit einer Fehlermeldung ab und
nennt die Zeile. Dann gehört die Datei gemeldet — sie sollte sich einspielen
lassen, das ist ihr Zweck (E-S2-20).

**Zusätzlich einmal:** *Versiegelt herunterladen* mit einer Passphrase, die
Datei auf einen anderen Rechner legen und mit dem Python-Weg aus
`docs/Backup-Format.md` 6.6 öffnen. Damit ist belegt, dass die Datei auch ohne
diese Anwendung zu öffnen ist — der Fall, in dem es sie nicht mehr gibt.

---

## 5. Bekannte offene Punkte

Kein Grund zur Beunruhigung, aber zu wissen:

| Nr. | Was |
|---|---|
| **Wiederanlauf** | Nie an einem echten Ausfall geprobt (1.1) — Prüfliste Punkt 11 |
| Backlog **54** | Der Migrationslauf nach einer Wiederherstellung ist ein zweiter Gang |
| Backlog **55** | Die Komplettsicherung kennt keinen scharfen Schnappschuss |
| Backlog **49** | Auf dem Sicherungsziel wird nie aufgeräumt |
| Backlog **50** | Der Versand liest je Konto ein Verzeichnis — bei hunderten Konten zu messen |
| Backlog **51** | Die Suchseite verarbeitet 5 000 Einträge, um 200 zu zeigen |
| Backlog **52** | WebDAV als viertes Sicherungsziel |
| Backlog **53** | Konto-Schlüsselpaar für versiegelte Serversicherungen |
| Backlog **47** | Zwei native `confirm()` sind noch nicht abgelöst |
| Backlog **48** | Aufbewahrung je Konto (heute eine Zahl für die Installation) |
| F-S2-G | „Transportziel" heißt hier **Sicherungsziel** — der Name war vergeben |

**FTPS prüft kein Zertifikat.** Das ist keine Lücke, die noch geschlossen
wird, sondern eine Eigenschaft von `ext/ftp` — nachgemessen. Wer Schutz gegen
einen untergeschobenen Server will, nimmt SFTP.

---

## 6. Grenzen der benutzten Prüfmittel

### 6.1 Fünf Prüfmittel haben etwas anderes gemessen, als draufstand

Das ist der unangenehmste Befund dieser Phase und gehört an diese Stelle,
weil er die Aussagekraft aller übrigen Zahlen betrifft.

| Mittel | Was es tat |
|---|---|
| `browserprobe.mjs` | wartete **4 s** auf einen Entsperr-Dialog, der bei entsperrter Sitzung nie kommt — mitten im gemessenen Abschnitt. Gemessen wurde `max(4 s, tatsächliche Dauer)`. Die Ausgangsmessung nannte deshalb 4,81 s für die Tagesansicht; sie liegt bei **1,17 s** |
| `maskierungs-probe` | starb seit Web 9.12.0 an `edSymbol is not defined` — Zeitlimit, **kein einziger Befund**, kein roter Zähler |
| dieselbe | wertete danach eine **gewollte** Gestaltungsänderung als Maskierungsverletzung |
| `jobprobe` | meldete **zehn Scheinfehler**, wenn gleichzeitig ein Kreislauf lief (der hält die Jobs an) |
| `papierkorb_misch` | verglich den Papierkorb des Zielkontos **absolut**, ohne zu prüfen, ob das Konto leer ist. Beim zweiten Lauf: **sechs Scheinbefunde**, darunter der schwerste, den es kennt |

Alle fünf sind behoben, und jedes sagt jetzt, wenn seine Voraussetzung nicht
erfüllt ist. **Die Lehre für dich:** Eine plausible Zahl wird nicht
hinterfragt. Wo eine Messung dicht an einem runden Zeitlimit liegt, lohnt der
zweite Blick.

### 6.2 Was die Versandprobe nicht abdeckt

Geprüft gegen pyftpdlib/paramiko **und** vsftpd/OpenSSH — beide Sätze werden
gebraucht, weil vsftpd kein `MLSD` kennt und damit als einziges den
Rückfallzweig der Verzeichnisliste fährt. Nicht abgedeckt: ProFTPD, IIS-FTP,
ein Server hinter NAT mit gesperrtem Portbereich, ein Pfad mit ungewöhnlichen
Zeichen.

### 6.3 Was die Drossel nicht drosselt

Der Faktor 6 bremst die **Rechenzeit** — nicht den Speicher, nicht die Grafik,
nicht die Leitung und nicht den langsameren Flash eines echten Geräts. Eine
Zahl knapp unter dem Zielwert ist damit kein Beleg.

### 6.3a Was die Komplettprobe nicht abdeckt

Sie arbeitet in einer **Kopie** des Serververzeichnisses unter `/tmp`, liest
aber aus der **echten** Datenbank — ein Dump gegen einen Spielbestand prüfte
nichts. Daraus folgen ihre Grenzen:

- **Die Oberfläche nur zur Hälfte.** `klickweg.mjs` deckt die Adminseite ab;
  `wiederherstellen.php` bräuchte eine leere Datenbank und ist deshalb nur
  über `curl` gefahren.
- **Keine volle Platte.** Die Speichergrenze ist als Rechnung geprüft.
- **Kein echter Absturz mitten in der Anfrage.** Nachgestellt ist er (der
  Zustand wird zurückgedreht, die Datei behält, was das Häppchen schrieb),
  erlebt nicht.
- **Kein Migrationslauf.** Er gehört einer angemeldeten Administration.
- **Nur MariaDB 10.11.** Ob ein anderer Server dieselben `SHOW CREATE TABLE`
  zurückgibt, ist damit nicht gesagt.
- **Die Zeiten sind ungedrosselt** und stammen von der
  Entwicklungsmaschine. Die **Speicherspitze** dagegen ist eine Aussage: Sie
  hängt an der Häppchengrösse, nicht an der Maschine.

### 6.4 Was der Bilderlauf nicht sieht

Er fotografiert **statisches Markup**. Bedienzustände (offene Dialoge,
Fehlermeldungen, gefüllte Formulare) sieht er nicht. Und er ist selbst schon
einmal auf 176 Bilder der Anmeldeseite hereingefallen (F-P3-AQ) — die
Gegenprobe über die Prüfsummen steht in seiner `LIESMICH.md` und wurde gefahren.
