# Prüfdokument P1 — was **du** noch prüfen musst

Das Prüfprotokoll im Konzept beantwortet „ist es belegt?". Dieses Dokument
beantwortet die andere Frage: **was steht noch aus, und auf welchem Weg?**

---

## 1. Was NICHT geprüft werden konnte — und warum

Das steht hier oben und nicht in einer Fußnote.

### 1.1 Der Produktivlauf (P-12)

**Alles unten Gemessene stammt aus einer lokalen Installation.** Ich habe
keinen Zugang zum Produktivserver: MariaDB 10.11 und PHP 8.4 lokal, Schema
über `install.php` regulär aufgesetzt, Browserschritte in Chromium.

Was das offenlässt:

- **Der FTPS-Deploy.** Ob `server/demo/fixture.json.gz` (744 KB) sauber
  hochgeht, ob die Ausnahmeliste greift, ob Dateirechte passen.
- **Die PHP-Fassung des Hosters.** Lokal 8.4. Weicht sie ab, kann
  `gzdecode()`, `PDO::inTransaction()` oder eine Typdeklaration anders
  reagieren.
- **`post_max_size` / `memory_limit`.** Das Einspielen der Fixture hält rund
  2,5 MB JSON und 55 861 Spurpunkte gleichzeitig im Speicher. Lokal
  unproblematisch, auf einem knapp konfigurierten Webspace nicht zwingend.
- **Laufzeit unter Last.** Der Reset dauert lokal 6,0 s. Auf gemeinsam
  genutztem Webspace kann er länger dauern — und er läuft **innerhalb** einer
  Web-Anfrage. Bei `max_execution_time = 30` ist das eng.

**Das ist der wichtigste Punkt dieser Liste.** Bitte nach dem ersten Deploy
gezielt nachsehen (Prüfliste 4.1).

### 1.2 E-Mail-Versand

Lokal ist kein SMTP eingerichtet. Dass `reset_request.php` für die
Demo-Adresse **keinen** Token anlegt, ist in der Datenbank nachgezählt (0
Zeilen) — dass auch keine Mail rausgeht, folgt daraus, ist aber nicht
beobachtet.

### 1.3 Straßentreue der Bodenspuren

Die Bodenstrecken kommen aus echten OSRM-Routen (einmalig abgerufen, als
GeoJSON eingecheckt). Ob sie plausibel *aussehen*, habe ich an Stichproben im
Browser gesehen, nicht systematisch. Geschwindigkeit und Höhe sind dagegen
maschinell begrenzt und geprüft.

### 1.4 Die Uhr

Der gesamte Bestand ist über `ingest.php` eingespielt, aber **nicht von einer
echten Uhr**. Der Sendeplan folgt dem Muster aus `watch/source/Uploader.mc`;
ob eine reale Uhr sich genauso verhält, ist damit nicht belegt.

### 1.5 Bedienzustände

Der Stilvergleich misst statisches Markup. Aufgeklappte Menüs, Fokusrahmen,
Hover- und Aktivzustände sind **nicht** gemessen — nur das, was die Proben
hergeben.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Was | Mittel | Zahl |
|---|---|---|
| Quelldaten: Schema, Sachlogik, Abdeckung | `quelldaten/pruefen.py` | **5 680** Einzelprüfungen, 78 Matrixzeilen, 0 offen |
| Zeitstempel auf Existenz und Eindeutigkeit | dieselbe | **1 124** geprüft |
| Ingest-Payloads gegen `JSON-Vertrag.md` 3.2 | `generator/pruefen.py` | **283 984** Einzelprüfungen über **alle** 526 Anfragen |
| Determinismus des Generators | zwei Läufe, Byte-Vergleich | **692** Dateien identisch |
| Einspiellauf | `einspielen/einspielen.py` | **526** Anfragen, **0** Fehlversuche |
| Importierter Bestand gegen die Quelldatei | eigener Abgleich | **184** Einzelprüfungen, 0 Befunde |
| Kreislauf Sicherung (P-09) | `vergleich/kreislauf.py` | **269 439** Vergleiche, **0 unerklärt**, 15 erwartet |
| Kreislauf CSV (P-08) | dieselbe | **8 797** Vergleiche, **6 unerklärt**, 858 erwartet (vor Web 7.3.1: 8 617 / 9 / 844) |
| Probe aufs Exempel des Vergleichswerkzeugs | `--testabweichung` | **10/10** je Format |
| Demo-Konto gegen die Referenz (P-10) | Vergleichswerkzeug | **279 028** Vergleiche, **0** Abweichungen |
| Reset nach Änderung (P-13) | dieselbe | **279 028** Vergleiche, **0** Abweichungen |
| Kaskadenvergleich `style.css` | `tools/stilvergleich/kaskade.py` | 6 neue Deklarationen, **0** entfallen, **0** Reihenfolgewechsel |
| Berechnete Stile, 9 Fensterbreiten | `stilvergleich.js` | **28 881** Elementmessungen, 144 Abweichungen — alle dem Banner zuzuordnen |

---

## 3. Was im Browser geprüft wurde

| Was | Skript | Ergebnis |
|---|---|---|
| Passwortvergabe, Schlüsselerzeugung | `einspielen/passwort_setzen.mjs` | Wiederherstellungsschlüssel erzeugt, 0 Konsolenfehler |
| Sichtprüfung des Bestands | `einspielen/sichtpruefung.mjs` | Spuren auf der Karte, Phasen, geschützte Angaben lesbar |
| CSV-Import | `browser/csv_import.mjs` | 4 Einsätze, 0 Hinweise, 0 Fehler |
| Angriffswerte in den Tabellen (P-07) | `browser/angriffswerte.mjs` | **42** Einzelprüfungen über 6 Seiten, 0 Dialoge, 0 injizierte Elemente |
| Referenz-Exporte | `browser/referenz_export.mjs` | 82 Einsätze, 171 GPX, 0 Konsolenfehler |
| Demo-Funktion (P-10/11/13/14) | `browser/demo_pruefen.mjs` | **16** Einzelprüfungen, 0 Befunde |
| Mengenbremse (P-15) | `browser/demo_bremse.mjs` | erste Abweisung bei Anmeldung **21**, Gegenprobe sauber |

---

## 4. Prüfliste — abhaken

Je Punkt: der Bedienweg, das erwartete Ergebnis, und **woran ein Scheitern zu
erkennen ist**.

### 4.1 Nach dem Deploy — zuerst

- [ ] **Fixture ist angekommen.**
  Adminbereich → **Demo-Konto**. Erwartet: kein roter Kasten.
  *Scheitern:* „Es liegt keine Fixture unter `server/demo/fixture.json.gz`" —
  dann hat der Deploy sie nicht mitgenommen (Ausnahmeliste prüfen) oder sie
  liegt am falschen Ort.

- [ ] **Version stimmt.**
  Fußzeile einer beliebigen Seite. Erwartet: **7.3.1**.
  *Scheitern:* eine ältere Nummer — der Browser sieht alte Dateien, hart neu
  laden; steht sie auch dann nicht da, ist der Deploy nicht durch.

- [ ] **Keine Migration nötig.**
  `update.php` als Admin aufrufen. Erwartet: keine offenen Migrationen.
  *Scheitern:* eine offene Zeile — dann stimmt etwas anderes nicht, diese
  Fassung bringt keine mit.

### 4.2 Demo-Konto anlegen

- [ ] **Anlegen.**
  Adminbereich → Demo-Konto → **„Demo-Konto anlegen"**.
  Erwartet: Meldung „angelegt", darunter die Zahlen **15 Diensttage,
  82 Einsätze, 95 Ruhesegmente, 5 im Papierkorb, 3 Geräte**, und im Bericht
  `missions_skipped: 0`, `rejected: []`.
  *Scheitern:* Zahlen weichen ab → der Bericht nennt unter `skipped_reasons`
  den Grund. `nicht_gefunden` im Papierkorb-Block heißt: Die Fixture wurde
  ohne Papierkorb erzeugt.
  *Scheitern (Laufzeit):* Zeitüberschreitung der Anfrage → siehe 1.1;
  `max_execution_time` prüfen.

- [ ] **Anmelden und lesen.**
  Abmelden, dann `demo@gen-em.org` / `nadokudemo0815`.
  Erwartet: Übersicht mit Einsätzen, **Banner** oben, und in der Einsatzansicht
  eine lesbare **Diagnose** neben dem Schloss-Symbol.
  *Scheitern:* „Diagnose 🔒" ohne Wert oder ⚠ in der Tabelle → das
  Schlüsselmaterial passt nicht zum Chiffretext. Dann stammen Fixture und
  Bestand aus verschiedenen Läufen.

- [ ] **Banner nur dort.**
  Mit einem eigenen Konto anmelden. Erwartet: **kein** Banner.
  *Scheitern:* Banner auch dort → `demo_ist_demo()` trifft das falsche Konto.

### 4.3 Der Reset

- [ ] **Von Hand.**
  Als Demo-Konto einen Einsatz löschen und einen Standort anlegen. Dann
  Adminbereich → **„Auf Standard zurücksetzen"**.
  Erwartet: wieder 82 Einsätze, 5 im Papierkorb, der zusätzliche Standort fort.
  *Scheitern:* Zahlen bleiben verändert → Bericht lesen; bleibt der Papierkorb
  leer, ist der Nachlauf gescheitert (er läuft hinter dem Commit, der Bestand
  ist dann trotzdem vollständig).

- [ ] **Von selbst.**
  Nach dem Reset **30 Minuten warten**, dann eine beliebige Seite des
  Demo-Kontos aufrufen — vorher etwas verändern.
  Erwartet: Die Änderung ist fort, bevor die Seite sie zeigt.
  *Scheitern:* Änderung noch da → im Fehlerprotokoll nach „demo: Reset
  fehlgeschlagen" sehen.
  *Abkürzung für Ungeduldige:* `app_state.demo_letzter_reset` auf einen Wert
  vor 30 Minuten setzen, dann eine Seite aufrufen.

- [ ] **Geschützte Angaben danach.**
  Nach dem Reset erneut eine Einsatzansicht öffnen.
  Erwartet: Diagnose weiterhin lesbar.
  *Scheitern:* ⚠ → der Reset hat das Schlüsselmaterial nicht mit
  zurückgeschrieben.

**Nebenwirkung, die man einmal gesehen haben sollte — sie ist kein Fehler.**
Fällt der Reset **in einen laufenden Besuch**, sieht die Besucherin für einen
Moment nichts: Die Diensttage bekommen beim Einspielen **neue Kennungen**, ein
offener Link `index.php?d=<alte Kennung>` zeigt danach ins Leere, und eine
gerade laufende API-Anfrage kann mit **401** abgewiesen werden. Beobachtet
beim Nachmessen zu Web 7.3.1: Ein Lauf von `browser/angriffswerte.mjs` lief
genau in einen Reset und meldete **0 Kandidaten und 3 Konsolenfehler**; der
unmittelbar folgende Lauf meldete **42 Einzelprüfungen, 0 Befunde**.
Wer prüft, wiederholt in diesem Fall — und wer eine Zahl aus einem Lauf
übernimmt, sieht vorher nach, ob `kandidaten` darin größer als 0 ist. Genau
dafür hat das Prüfmittel seine Gegenprobe.

### 4.4 Die Sperren

- [ ] **E-Mail und Passwort.**
  Als Demo-Konto: Einstellungen → Profil → Adresse ändern → speichern.
  Erwartet: Hinweis, dass beides im Demo-Konto nicht änderbar ist; die Adresse
  bleibt.
  *Scheitern:* „Profil gespeichert" → die Sperre greift nicht; dann ist das
  Konto bis zum nächsten Reset unerreichbar.

- [ ] **Passwort vergessen.**
  Abmelden → „Passwort vergessen" → `demo@gen-em.org`.
  Erwartet: die übliche Antwort („falls die Adresse registriert ist …") und
  **keine E-Mail**.
  *Scheitern:* Eine Mail kommt an → `reset_request.php` weist nicht ab.

- [ ] **Mengenbremse.**
  21-mal hintereinander als Demo-Konto anmelden (jeweils abmelden).
  Erwartet: ab dem 21. Versuch „Das Demo-Konto wird gerade sehr häufig
  genutzt …" mit Uhrzeit.
  *Scheitern:* Es geht immer weiter → der Topf `demo` zählt nicht.
  **Gegenprobe, nicht vergessen:** Das eigene Konto muss weiterhin hereinkommen.

- [ ] **Was offen bleiben soll.**
  Als Demo-Konto ein Gerät anlegen und einen Kopplungscode erzeugen.
  Erwartet: funktioniert.
  *Scheitern:* abgewiesen → dann ist zu viel gesperrt; die Anwendung soll
  ausprobierbar sein.

### 4.5 Der Regressionslauf

- [ ] **Vor dem Vergleich zurücksetzen.**
  Sonst misst der Vergleich Besucheränderungen und nennt sie Regression.

- [ ] **Beide Kreisläufe.**
  `python3 vergleich/kreislauf.py --art edbak --frisch` und `--art csv`.
  Erwartet: edbak **0 unerklärte** Abweichungen; CSV **6** — und zwar genau
  die zwei bekannten Befunde (siehe 5). Keine ungenutzte Regel: Meldet der
  Lauf „ungenutzte Regeln", beschreibt eine Ausnahme etwas, das es nicht mehr
  gibt — das ist ebenso ein Befund wie eine Abweichung zu viel.
  *Scheitern:* mehr oder andere → der Bericht nennt Bereich, Schlüssel und
  Feld. Alles, was nicht in Abschnitt 5 steht, ist neu.

- [ ] **Die Probe aufs Exempel mitlaufen lassen.**
  `--testabweichung`. Erwartet: **10/10** je Format.
  *Scheitern:* Eine Hinprobe schlägt fehl → das Werkzeug findet Unterschiede
  nicht mehr. Eine **Gegen**probe schlägt fehl → die Normalisierung greift
  nicht mehr, und der Bericht meldet ab jetzt Rauschen.

---

## 5. Bekannte offene Befunde — kein Grund zur Beunruhigung, aber zu wissen

Diese sechs Abweisungen im CSV-Kreislauf sind **erwartet**, solange die
zugehörigen Backlog-Punkte offen sind. Alles darüber hinaus ist neu.

| Befund | Wirkung | gemessen | Backlog |
|---|---|---|---|
| **F-P1-L** | mehrzeilige Notizen verlieren ihre Zeilenumbrüche | 4 Notizen, je 1 Umbruch | Nr. 27 |
| **F-P1-M** | `final = 0` wird zu 1, leeres `ende` zur Startzeit | je 1 | Nr. 28 |

**Der dritte Befund ist weg.** F-P1-K (Einsätze über Mitternacht wandern beim
CSV-Import 24 Stunden zurück, 2 Einsätze, 4 Meldungen) ist mit **Web 7.3.1**
behoben — er war kein Formatverlust, sondern eine stille Datenverfälschung,
und die Angabe, die ihn behebt, stand die ganze Zeit in der Datei (Spalte
`datum`). Wer eine ältere Fassung prüft, erwartet dort weiterhin 9
Abweisungen.

**Eine Zahl hat sich dadurch geändert, nicht nur die Summe:** F-P1-L war mit
3 Notizen gemessen, es sind **4**. Die vierte hing an einem Einsatz, den
F-P1-K aus dem Vergleich gehoben hatte. Wer Befunde zählt, zählt immer nur,
was die anderen Befunde durchlassen — beim Nachprüfen einer Behebung lohnt
deshalb der Blick auf die *anderen* Zahlen, nicht nur auf die behobene.

Weiter offen, ohne Wirkung auf die Kreisläufe:

- **F-P1-F** — `docs/JSON-Vertrag.md` 3.3 nennt eine Reanimationsart, die kein
  Schreibweg annimmt (Backlog Nr. 23)
- **F-P1-G** — Formelschutz-Apostroph beim Rückimport (Nr. 24)
- **Nr. 25** — `missions.created_at` wird gesichert, kommt nicht zurück
- **F-P1-N** — `Export-Format.md` 5.1 zählt drei Ausnahmen auf; es sind mehr
  (Nr. 29)
- **Nr. 30** — der Papierkorb steht in keiner Sicherung. Entschieden ist, dass
  er mitsoll; ausgearbeitet als Paketvorschlag in `Konzept-P1.md`,
  Abschnitt 10. Bis dahin gilt: Eine Wiederherstellung leert den Papierkorb
  endgültig.

---

## 6. Grenzen der benutzten Prüfmittel

**Das Vergleichswerkzeug** vergleicht, was in den Exportdateien steht. Was in
**beiden** fehlt, sieht es nicht — der Papierkorb und die Geräte etwa mussten
getrennt in der Datenbank gezählt werden. Es normalisiert außerdem
flüchtige Anteile; eine Änderung an `created_at` oder einer internen Kennung
kann es deshalb grundsätzlich nicht melden. Das ist gewollt und die Gegenprobe
im `--testabweichung`-Lauf prüft genau das.

**Der Stilvergleich** misst statisches Markup bei neun Fensterbreiten. Er
ersetzt die Browserprüfung nicht: Bedienzustände kommen darin nicht vor.

**`generator/pruefen.py`** liest seine Regeln aus `server/assets/` statt sie
abzuschreiben. Wo das nicht ging (Rollenkatalog), liest es `db.php`. Eine
abgeschriebene Regel prüfte nur, ob der Generator mit sich selbst einig ist.

**Die Browserskripte** laufen headless in Chromium. Sie sehen, was im DOM
steht und was die Konsole meldet — nicht, ob etwas *gut aussieht*. Die
Bildschirmfotos unter `/tmp/…` sind zum Nachsehen da, nicht zum Vergleichen.

**Alle Zahlen dieses Dokuments stammen aus einer lokalen Installation.**
Siehe Abschnitt 1.1.
