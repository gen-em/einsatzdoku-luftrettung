# Prüfdokument — Backlog-Runde 2 (Web 19.3.0)

*Erstellt am 12.09.2026, Zweig `claude/backlog-runde-2`. Das Prüfprotokoll in
Abschnitt 2 und 3 beantwortet „ist es belegt?"; die **Prüfliste** in
Abschnitt 4 beantwortet „was muss **ich** noch tun?".*

> | | |
> |---|---|
> | Stufe | **Web 19.3.0** — Nebenstufe. **Keine Migration**, `update.php` muss nach dem Deploy **nicht** laufen. Uhr und Android unberührt (drei Zählungen, drei Auslieferungen) |
> | Punkte | Backlog **Nr. 126** (Rückweg aus der Wartungsseite), **Nr. 119** (Import / Export nennt die anderen Wege), **Nr. 118** (Jobs anhalten), **Nr. 120** (Testmail), **Nr. 125** (`.zweispalter` gestrichen) |
> | Offen geblieben | **Nr. 57** — Eintrag mit den Messungen berichtigt, Punkt bleibt stehen: Er braucht ein Mockup und eine Freigabe |
> | Neu entstanden | Symbol **`mail.svg`** (53. im Vorrat); zwei Zusagen geändert (siehe 1.); Backlog **Nr. 174** (siehe 2a) |
> | Prüfumgebung | Wegwerf-Container: PHP 8.4.19 (CLI und `php -S`), MariaDB 10.11.14, Chromium 141.0.7390.37 über Playwright; lokale Installation über **https://127.0.0.1:8443**, Bestand 259 Einsätze / 45 Diensttage / 4 Konten |
> | Ergebnis | Alles Maschinelle grün; **sieben Punkte bleiben für die Auftraggeberin** (Abschnitt 4) |

---

## 0a. Der Merge bringt ZWEI Stufen, und die erste hat eine Bedingung

**Gemessen am 12.09.2026:** `origin/main` trägt **Web 19.1.2**. PR #40 hat die
erste Backlog-Runde bis dorthin gemergt — **Web 19.2.0 ist nicht auf `main`**
und geht mit diesem Zweig zusammen hinaus.

Das zählt, weil 19.2.0 die Nummer mit Backlog **Nr. 155** ist: Sie streicht
die alte Rundenzahl **320 000** aus `KDF_ITER_LISTE`. **Ein Konto, das noch
auf 320 000 steht, kann sich danach nicht mehr anmelden** — Passwort und Hash
bleiben zwar unberührt, und der Wert wieder einzutragen stellt den Zugang her,
aber das ist eine Codeänderung plus Deploy.

**Deshalb vor dem Merge, nicht danach:** Betrieb → Status aufrufen und die
Zeile **„Schlüsselableitung"** lesen. Sie muss sagen, dass **kein Konto im
Übergang** steht. Am 12.09.2026 hast du das bestätigt; wenn seither ein
Bestandskonto dazugekommen ist oder eines noch nie angemeldet war, gilt die
Bestätigung nicht mehr.

---

## 0. Was NICHT geprüft werden konnte — und warum

Steht bewusst vor der Prüfliste.

- **Der echte Mailversand (Nr. 120).** Die Testmail ist gegen einen
  **selbstgebauten SMTP-Auffänger** auf dem Prüfstand belegt: implizites TLS
  auf 127.0.0.1:2525, eigenes Zertifikat, in den Vertrauensspeicher des
  Containers gelegt, weil `smtp.php` die Gegenstelle mit
  `verify_peer => true` prüft. Der Auffänger nimmt an, er stellt nicht zu.

  **Was das nicht sagt:** ob die Zugangsdaten der Produktivinstallation
  stimmen, ob ein echtes Relais den Absender annimmt, ob die Nachricht in
  einem Postfach ankommt oder im Spam landet. Der Weg durch `smtp_send()` ist
  vollständig gelaufen — AUTH, MAIL FROM, RCPT TO, DATA, Betreff und Rumpf
  liegen im Auffänger —, aber die letzte Meile fehlt. **Punkt 1 der
  Prüfliste ist deshalb kein Formalakt.**

  Und: Der Vertrauensspeicher des Containers ist für diese Prüfung
  **erweitert** worden. Auf dem Produktivserver ist das weder der Fall noch
  nötig — dort steht ein Zertifikat, dem das System ohnehin traut. Wer diesen
  Prüfstand nachbaut, muss den Schritt wiederholen, sonst scheitert der
  Versand an der Zertifikatsprüfung und sieht aus wie ein Fehler der
  Anwendung.

- **Der Wartungsmodus auf dem Produktivserver (Nr. 126).** Geprüft wurde mit
  `server/wartung.lock` auf der lokalen Anlage, angemeldet als BetreiberIn,
  danach wieder ausgeschaltet. Der Weg ist derselbe — `wartung_tor()` steht
  in `db.php` vor jeder Verbindung —, aber es ist nicht dieselbe
  Installation.

- **Der Bilderlauf über alle 45 Seiten.** Gefahren wurde er über die **fünf
  berührten** Seiten (34, 35, 43a, 45, 47), als Zeiger- **und** als
  Fingergerät. Ein voller Lauf hätte 40 unveränderte Seiten mitgemessen und
  die Aussage verwässert: „248 Bilder, 0 Überlauf" hat in O9c schon einmal
  eine Zahl geliefert, die nichts über die Änderung sagte (F-P3-AQ).

- **Die Wartungsprobe, Erwartung 15** ist als **flatternd** bekannt (Backlog
  **Nr. 172**): Sie misst eine Zeitdifferenz und schlug in drei Läufen
  einmal an (71,7 gegen 71,6 ms). Im Abschlusslauf dieser Runde war sie
  grün — das ist kein Beleg dafür, dass sie stabil ist.

- **Uhr und Android.** Nicht angefasst, also auch nicht geprüft: kein
  Prüfstand, kein Simulator, kein Emulator. Die Runde berührt ausschließlich
  `server/`, `docs/` und `tools/`.

---

## 1. Zwei Zusagen haben sich geändert

Das gehört an den Anfang, weil es der einzige Teil der Runde ist, der eine
schriftliche Festlegung der Anwendung verschiebt.

**Betrieb → Status hat jetzt zwei Ausnahmen statt einer.** Die Seite sagte an
vier Stellen — Dateikopf, Unterzeile, Karte „Was hier gilt", Handbuch 12.1 —,
sie ändere nichts, „mit genau einer Ausnahme" (dem fehlenden
Serverschlüssel). Der Knopf „Testmail an mich" ist die zweite.
**Freigegeben am 12.09.2026.** Die Begründung steht an allen vier Stellen:
Beide Ausnahmen ändern keinen Bestand, sondern prüfen an Ort und Stelle — und
für SMTP gibt es überhaupt keine zuständige Seite, auf die zu verweisen wäre,
weil der Zugang allein in der `config.php` steht.

**„Import / Export" beansprucht nicht mehr, alle Datenwege zu führen.** Der
Menüpunkt heißt weiter so, aber Untertitel und Karte sagen jetzt, was dort
liegt (die Einsatzliste in beide Richtungen) und wo die übrigen sechs Wege
sind. Der Name wurde **nicht** enger gefasst: „Einsatzliste" hätte den Export
versteckt, und der Menüpunkt ist der einzige Ort dafür.

Was **nicht** angetastet wurde: die Ende-zu-Ende-Verschlüsselung und ihr
Feldkatalog, `validate_lib.php`, `spur_lib.php`, das Datenmodell, die
Zusage „keine fremde Quelle zur Laufzeit" (das neue Symbol liegt lokal, wie
die 52 anderen).

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Mittel | Zahl | Ergebnis |
|---|---|---|
| **Wortliste** (`tools/wortliste/`) | 5 Bereiche, 96 Ausnahmeregeln | **0** Treffer außerhalb der Ausnahmen, **0** ungenutzte Ausnahmen, **0** durchgerutschte Fallen |
| **Vollständigkeit** (`tools/vollstaendigkeit/`) | 330 → **334** Befunde | Die vier neuen sind **alle** „Unicode-Zeichen als Symbol": ein Pfeil in deutschem Fließtext, dieselbe Sorte wie die 250 bereits stehenden. **0** Symbole im Markup, **0** Emoji, **0** fehlende Symboldateien, **0** Zeichen ohne Anker |
| **Kontraste** (`tools/screenshots/kontrast.py`) | 22 Paare | **0** verfehlt |
| **Bilderlauf** (`tools/screenshots/`) | 5 Seiten × 8 Breiten, **zweimal** (Zeiger und Finger) = **80** Bilder | **0** waagerechter Überlauf, **0** Konsolenfehler, **0** Knöpfe falscher Höhe (44/36 px als Zeiger, 44 px als Finger) |
| **Stilvergleich, Kaskade** (`tools/stilvergleich/kaskade.py`) | Regeln 743 → **742** | entfallen **4** (die vier Deklarationen von `.zweispalter`), neu **0**, anderer Endwert **0**, vertauschte Paare bei gleicher Spezifität **0** |
| **Stilvergleich, Browser** | 13 Breiten, vier Proben | `seiten/katalog/js_markup`: **48 165** Elementmessungen, **0** Abweichungen, 166 Eigenschaften je Element · Pseudoprobe gegen die umgeschriebenen Stylesheets: **20 059** Messungen, **0** Abweichungen. Zusammen **68 224** |
| **Wartungsprobe** (`tools/wartungsprobe/probe.php`) | 51 → **55** Erwartungen | **0** nicht erfüllt (Erwartung 15 flattert, siehe 0.) |
| **Linkprobe** (`tools/linkprobe/probe.py`) | 117 Verweise, 100 Zielseiten | **0** Abweichungen, **0** Ausnahmen, **0** tote Zeilen |
| **Klickprobe** (`tools/klickprobe/probe.mjs`) | 40 Wege | **40 von 40** erfüllt — nach der Reparatur der Fixture (siehe 2a). Der erste Lauf meldete **39 von 40** |
| **Design-Tabellen** (`tools/design/tabellen.py alle`) | 4 Tabellen | alle vier stimmen mit dem Generator überein; Token `--abstand-4` 55 → **54** Nennungen (weil `.zweispalter` entfällt), Symbole 52 → **53** Dateien |

**Zwei Erwartungen sind zur Wartungsprobe dazugekommen** (51 → 55, die
übrigen zwei stammen aus Runde 1): **19a** prüft, dass die Wartungsseite den
Knopf „Zur Verwaltung" trägt **und** dass der Weg über `login.php` ihn
**nicht** trägt; **18** prüft den Schalter `wartung_antwort_seite(false)` mit.

---

## 2a. Ein Prüfmittel hat einen Fehler aus Runde 1 gefunden

**Das ist der wichtigste Befund dieser Runde, und er betrifft nicht den
ausgelieferten Code, sondern den Prüfstand.**

Die Klickprobe meldete **39 von 40** Wegen: `ap4-ohne-standort-sichtbar`
erwartete **2** Rettungsmittel in der Karte „Ohne Standort" und fand **0**.

**Ursache, gemessen:** Beim Neubau des Referenzbestands in Web 19.2.0
(Backlog Nr. 155) haben zwei der sechs Rettungsmittel ihren *leeren* Standort
verloren. Der Nachweis steht in den Fixtures selbst — das Sicherungsformat
trägt den Standort als **Namen** (`base_ref`), nicht als Kennung:

| Fixture | `Reserve Talwang` | `Sanitätsdienst Seefest` | ohne Standort |
|---|---|---|--:|
| vor Nr. 155 | `null` | `null` | **2** |
| nach Nr. 155 | `'Notarztstandort Talwang'` | `'Luftrettungsstation Hochkreuth'` | **0** |

Seit S9/AP4 ist der Standort **freiwillig** (E-S9-18), und diese beiden waren
die einzige Abdeckung dieses Falls im Bestand.

**Behoben** über die Oberfläche, nicht per SQL: beide Rettungsmittel im
Dialog auf „Ohne Standort" gestellt, Fixture neu erzeugt, Demo-Konto daraus
zurückgesetzt. Danach **40 von 40** Wegen erfüllt.

**Beim Nachbauen ist ein zweiter Fehler aufgefallen.** Der erste
Erzeugungslauf lieferte **47 576** statt **55 861** Spurpunkten — die
Hintergrundjobs laufen huckepack auf jeder Anfrage mit und hatten die
Aufzeichnungen während der Arbeit ausgedünnt. **8285 Punkte**, ohne jede
Meldung. Mit `php server/jobs.php --pause 1800` vorher stimmt die Zahl auf den
Punkt. Der Dateikopf von `tools/referenzdatensatz/fixture/erzeugen.php` sagt
das jetzt, mit der Zahl.

**Was daran zählt, über den Einzelfall hinaus:** Beide Kreisläufe waren in
Runde 1 grün, und der Verlust ist trotzdem durchgegangen. Gefunden hat ihn
das einzige Prüfmittel mit einem **Sollmaß, das nicht aus dem gebauten
Zustand stammt**. Dasselbe Muster wie Backlog Nr. 170.

**Nicht behoben, und deshalb als Backlog Nr. 174 aufgenommen:** Die
**Referenzdatei** der Kreisläufe (`tools/referenzdatensatz/referenz/`) stammt
aus demselben Neubau und trägt weiterhin sechs Rettungsmittel **mit**
Standort. Ob ein `base_ref: null` einen Export und den Import zurück
übersteht, ist damit nicht geprüft. Sie neu zu erzeugen heißt, die
dreistufige Einspielkette noch einmal zu fahren — das gehört mit Nr. 173
zusammen, das dieselben Dateien anfasst, und nicht ans Ende einer Sitzung.

---

## 3. Was im Browser geprüft wurde

Alles angemeldet auf der lokalen Anlage, Chromium über Playwright, je Fall
mit einer Gegenprobe an der Datenbank.

**Nr. 126 — Rückweg aus der Wartungsseite.** Schalter an, Seite geladen:
Titel „Wartung", **ein** Verweis (`betrieb_updates.php`), Knopfhöhe **44 px**,
Stylesheet geladen, **0** Skripte. Schalter wieder aus.

**Nr. 119 — Import / Export.** Verweise im Seiteninhalt **0 → 4**, Karten
**4 → 5**, die neue Karte ist ein `<details>` und steht **zu**; Überlauf
**0** auch aufgeklappt, Konsolenfehler **0**. Alle drei Zieladressen geben
**200**. Am Konto **ohne** Diensttag (`admin@gen-em.org`, 0 Diensttage)
landet der GPS-Verweis auf „Noch keine Daten" — deshalb hängt der Satz am
Diensttag und verspricht kein Menü. Das Aktionsmenü öffnet und trägt den
Eintrag „GPX importieren", 36 px hoch.

**Nr. 118 — Jobs anhalten.** 15 Min. gewählt, Rückfrage bestätigt: Plakette
„läuft" → **„angehalten"**, `app_state.jobs_pause_bis` auf **+15 Min.**
(18:54 → 19:09 Ortszeit). „Pause aufheben": Zeile **gelöscht**, Plakette
zurück auf „läuft". Bedienhöhen **36 px** (Zeiger, 1280 px) und **44 px**
(Finger, 390 px), Knopf wie Segmenttasten; Überlauf **0**, Konsolenfehler
**0**. **Gegenproben:** ein Wert außerhalb der Liste (999 999 s, im Browser
hineingeschrieben) wird abgewiesen und schreibt **nichts**; ein POST ohne
Formular-Token gibt **403** und schreibt ebenfalls nichts.

**Nr. 120 — Testmail.** Erfolg: Zeile „kein Versand"/neutral →
**„zugestellt"/blau**, `smtp_last_ok = 1`, die Nachricht liegt vollständig im
Auffänger. Meldung **in der Karte**, nicht oben neben der Zusammenfassung.
**Kein SMTP** (Host in der `config.php` geleert, danach zurückgesetzt):
„es wurde nichts versucht", `app_state` **leer**, Ratenzähler **nicht**
verbraucht, Zeilen bleiben neutral. **Ratenschutz:** der vierte Versuch
innerhalb einer Stunde wird abgewiesen, zwei Zeilen in `rate_limits`
(`ip:…` und `id:…`). **POST ohne Formular-Token: 403.** Bedienhöhen 36/44 px,
Überlauf 0, Konsolenfehler 0.

**Nr. 125 — `.zweispalter`.** Geometrie der Installationsseite in **drei**
Breiten (1100 / 1280 / 1920 px), je **90** Nachkommen des Rasters:
**0 abweichende Rechtecke**, Kinder identisch, `display` block/grid
unverändert, Überlauf 0. Die **eine** beabsichtigte Änderung ist gemessen:
Marken in der Leiste **1 → 2** ab 1200 px („Logo" und „Impressum").

---

## 4. Prüfliste — was die Auftraggeberin noch tun muss

Je Punkt: der Bedienweg, das erwartete Ergebnis, und **woran ein Scheitern
zu erkennen ist**.

- [ ] **0. VOR dem Merge: die Zeile „Schlüsselableitung".** Betrieb → Status.
      *Erwartet:* kein Konto im Übergang.
      *Scheitern erkennt man an:* der Plakette „Übergang läuft" mit einer Zahl
      über null. Dann **nicht mergen** — sonst sperrt 19.2.0 diese Konten aus
      (Backlog Nr. 155, siehe 0a). Der Ausweg ist, die betroffenen Konten sich
      einmal anmelden zu lassen; sie ziehen dabei still nach.

- [ ] **1. Die Testmail an der echten Installation.** Nach dem Deploy
      Betrieb → Status öffnen, im Kopf der Karte „E-Mail" auf **„Testmail an
      mich"** klicken.
      *Erwartet:* grüne Meldung „Die Testmail ist an … hinausgegangen", die
      Zeile „Letzter Versand" wird blau/**zugestellt** — **und die Mail liegt
      im Postfach**.
      *Scheitern erkennt man an:* roter Meldung („Der Versand ist
      gescheitert") oder daran, dass die Meldung grün ist und **trotzdem
      nichts ankommt** — dann nimmt das Relais an und stellt nicht zu, und
      die Ursache steht beim Mailanbieter, nicht in der Anwendung. Bitte
      auch den **Spam-Ordner** ansehen: Absender ist `smtp.from` aus der
      `config.php`.

- [ ] **2. Der Ratenschutz.** Viermal hintereinander klicken.
      *Erwartet:* die ersten drei gehen hinaus, der vierte wird mit
      „Zu viele Testmails in kurzer Zeit. Bis HH:MM Uhr …" abgewiesen.
      *Scheitern:* der vierte geht auch noch hinaus (dann greift der Topf
      nicht) — oder schon der zweite wird abgewiesen (dann zählt er
      falsch).

- [ ] **3. Der Rückweg aus der Wartung.** Betrieb → Updates, Wartungsmodus
      **einschalten**, eine beliebige Seite aufrufen.
      *Erwartet:* die Wartungsseite mit dem Knopf **„Zur Verwaltung"**, der
      auf `betrieb_updates.php` führt. Dort den Modus wieder **ausschalten**.
      *Scheitern:* kein Knopf (dann ist eine alte Datei im Browser — dann
      einmal hart neu laden), oder der Knopf führt auf ein 403 (dann ist die
      Rolle nicht `betreiberin`).
      **Achtung:** Diesen Punkt zuerst zu Ende führen. Wer den Modus
      einschaltet und die Seite schließt, kommt nur über den genannten Knopf
      oder die von Hand getippte Adresse zurück.

- [ ] **4. Jobs anhalten.** Betrieb → Hintergrundjobs, Karte „Zustand": Dauer
      **15 Min.** wählen, „Jobs anhalten", Rückfrage bestätigen.
      *Erwartet:* Plakette **„angehalten"**, orange Meldung mit dem
      Zeitpunkt, der Knopf zum Anhalten ist weg, stattdessen steht
      **„Pause aufheben"** da. Danach aufheben.
      *Scheitern:* die Plakette bleibt auf „läuft" (dann ist der Zweig nicht
      gelaufen), oder die Zeit stimmt nicht (dann ist die Zeitzone der
      Installation eine andere als erwartet).

- [ ] **5. Import / Export.** Einstellungen → Import / Export: den
      Untertitel lesen, unten die Karte **„Was hier gilt"** aufklappen, alle
      vier Verweise anklicken.
      *Erwartet:* jeder führt auf die genannte Seite. Danach
      Einstellungen → Backup: dort steht jetzt ein Verweis zurück.
      *Scheitern:* ein 404, oder eine Seite, auf der das beschriebene Menü
      nicht steht.

- [ ] **6. Die Installationsseite am breiten Bildschirm.** Verwaltung →
      Installation bei **mindestens 1200 px** Fensterbreite.
      *Erwartet:* zwei Spalten wie bisher, **und in der Leiste links zwei
      fette Unterpunkte** statt einem („Logo" und „Impressum", je nachdem,
      wo man steht). Das ist die einzige beabsichtigte sichtbare Änderung
      der Runde.
      *Scheitern:* die Spalten stehen untereinander (dann fehlt die
      Rasterklasse), oder die Seite sieht anders aus als vorher — dann bitte
      melden, denn 68 224 Messungen sagen, dass sie es nicht tut.

- [ ] **7. Das neue Zeichen.** Im Kopf der Karte „E-Mail" steht ein
      Briefumschlag (Tabler „mail"). Wenn es dir nicht gefällt, ist es
      **eine Datei** — der Tausch kostet Minuten.

**Nicht nötig:** `update.php` aufrufen. Es gibt **keine** Migration in dieser
Runde.

---

## 5. Grenzen der benutzten Prüfmittel

- **Der Stilvergleich misst statisches Markup**, keine Bedienzustände. Was
  erst durch Klicken entsteht — aufgeklappte Karten, geöffnete Blätter,
  gefüllte Tabellen —, sieht er nur, soweit es in den Vorlagen steht. Die
  Pseudoprobe deckt Hover, Fokus, Aktiv und Sperrung ab, und nur diese.
- **Der Bilderlauf drückt keinen Knopf.** Er zeigt den Ruhezustand einer
  Seite in acht Breiten. Alles, was nach einem Klick passiert — die Meldung
  der Testmail, die Pause, die aufgeklappte Karte —, ist von Hand belegt
  (Abschnitt 3) und nicht im Bilderlauf.
- **Die Vollständigkeitsprüfung sieht keine zur Laufzeit zusammengesetzten
  Klassen.** Das ist keine Kleinigkeit: `karte-aktion-<art>` entsteht so, und
  ein falscher Wert hätte eine Klasse ohne Regel ergeben — einen
  ungestalteten Knopf, ohne Fehlermeldung. Gefunden hat das keine Maschine,
  sondern eine Gegenprobe am Stylesheet.
- **Die Wortliste zählt Wörter, nicht Bedeutung.** Sie hat in dieser Runde
  vier Stellen gefunden, an denen „Spur" wieder in die Oberfläche geraten
  wäre (seit S9/AP3 heißt es dort „Aufzeichnung" oder „GPS-Daten") — das ist
  genau ihr Zweck. Ob ein Satz *stimmt*, sagt sie nicht.
- **Alle Messungen stammen aus einem Wegwerf-Container**, nicht vom
  Produktivserver. Siehe Abschnitt 0.

---

## 6. Was aus der Runde offen bleibt

- **Nr. 57** (Tagesübersicht baut ihre Tabelle selbst). Der Backlog-Eintrag
  ist mit den Messungen berichtigt — fünf Driften statt einer, zwei Erzeuger
  und vier Spaltenlisten, ein dreifaches Sortierblatt, und eine Sortierfalle,
  die „0 Pixel bewegen sich" widerlegt. Der Punkt braucht ein Mockup und
  eine Freigabe und ist damit ein eigenes Paket.
- **Nr. 172** (Wartungsprobe, Erwartung 15 flattert) und **Nr. 173**
  (ungenutzte Regeln in den Kreisläufen) stammen aus Runde 1 und stehen
  weiter offen.
- **Nr. 170** (eine Zählung, die ihr eigenes Sollmaß setzt, bestätigt sich
  selbst) — aus Runde 1, weiter offen.
- **Nr. 174** (die Referenzdatei der Kreisläufe deckt „Rettungsmittel ohne
  Standort" nicht mehr ab) — in dieser Runde aufgenommen, siehe 2a.
