# Browserschritte des Referenzdatensatzes (Arbeitspaket B4)

Was es **nur** im Browser gibt. Die beiden Schritte des Aufbaus (oben in der
Tabelle) sind zusätzlich als nummerierte Klickstrecke beschrieben — das Skript
ist die wiederholbare Fassung, die Klickstrecke die prüfbare.

| Skript | Wofür |
|---|---|
| `csv_import.mjs` | CSV-Import der vier nachträglich erfassten Einsätze (Aufbau) |
| `angriffswerte.mjs` | P-07 — die Angriffswerte stehen inert in allen Einsatztabellen |
| `referenz_export.mjs` | zieht die eingecheckten Referenzdateien aus dem Referenzkonto |
| `kreislauf_edbak.mjs` | Umlauf des Backups: einspielen, erneut sichern (von `kreislauf.py` gerufen) |
| `kreislauf_csv.mjs` | dasselbe für den CSV-Weg |
| `papierkorb_misch.mjs` | E-S1-04 und Backlog Nr. 33: ein Diensttag mit **einzeln** und **mit dem Tag** gelöschten Einsätzen übersteht den Umlauf, die Papierkorbseite zeigt den Unterschied, und das Zurückholen wird abgelehnt, solange der Diensttag selbst im Papierkorb liegt |
| `demo_pruefen.mjs` | Abnahme der Demo-Funktion (E-P1-08) — **verändert das Konto, gegen das es läuft** |
| `demo_bremse.mjs` | die Mengenbremse greift auch für das Demo-Konto |
| `download_lib.mjs` | **kein Schritt, sondern die gemeinsame Wartestelle**: auf einen Download warten und sagen, was war, wenn keiner kommt |

Zwei davon fassen Daten an und haben deshalb einen Riegel:
`demo_pruefen.mjs` bricht ab, wenn unter der Demo-Adresse ein Konto liegt, das
nicht als Demo-Konto gekennzeichnet ist; `papierkorb_misch.mjs` arbeitet
ausschließlich auf Konten, deren Adresse mit `umlauf-` beginnt. Beide Riegel
brechen **hart** ab (Rückgabe 2) — sie melden nicht bloß.

### `demo_pruefen.mjs` — erwartet: 24 Einzelprüfungen, 0 Befunde

Stand 14.09.2026 (S10/AP5). Zwei Zahlen, nicht eine: Die Schlusszeile nennt
**Einzelprüfungen**, **Konsolenfehler** und — wenn es welche gab — **nicht
gemessene** Erwartungen mit Grund.

> **„Nicht gemessen" ist ein regulärer Ausgang, kein Fehler.** Zwei Gründe
> kommen vor, und beide sind Eigenschaften der Installation, nicht der
> Anwendung:
>
> - **Es gibt schon ein Demo-Konto.** Dann steht der Knopf „Demo-Konto
>   anlegen" nicht auf der Seite (`admin_demo.php` zeigt entweder ihn **oder**
>   „Zurücksetzen"). Abschnitt 1 zählt als nicht gemessen; die Zahlen darunter
>   werden trotzdem geprüft, denn sie gelten für den Sollstand.
> - **Die Mengenbremse des Demo-Kontos greift** (E-P1-20: zwanzig Anmeldungen
>   je Stunde). Dieses Skript meldet sich bis zu achtmal an — zwei Läufe
>   hintereinander reichen. Der Lauf liest den Grund von der Anmeldeseite
>   („wieder ab HH:MM Uhr") und überspringt den Abschnitt, statt einen
>   Stapel roter Zeilen zu melden. Dieselbe Lösung wie in
>   `tools/anteilprobe/umstellungslauf.mjs` (F-11).
>
> Wer sofort weitermessen muss, leert den Topf über den Weg der Anwendung:
> `rate_erfolg('demo')` und `rate_erfolg('demog')` mit gesetztem
> `$_SERVER['REMOTE_ADDR']`.

**Vier Reparaturen in S10/AP5**, alle vier Probenfehler und keiner in der
Anwendung — die Probe lief zuvor überhaupt nicht durch:

| Was | Der Fehler |
|---|---|
| Kennzahlen fehlten | `zustand()` teilte an **einem** Umbruch, die Kachel liefert `"83\n\nEinsätze"`. Die vier Kennzahlen fielen weg, und jede Erwartung darauf verglich `undefined` gegen '83' |
| Der Reset lief nie | Der Knopf heißt **„Zurücksetzen"**, gesucht wurde „Auf Standard zurücksetzen". Der ganze Zweig wurde übersprungen — sichtbar nur daran, dass der Papierkorb des Demo-Kontos mit jedem Lauf wuchs |
| „Diagnose nicht lesbar" | Der Ausdruck suchte das Schloss als **Emoji**; `dtGeschuetzt()` setzt es seit Langem als `<svg>`, und `innerText` liefert dafür nichts. Der Klartext stand die ganze Zeit gut lesbar daneben |
| `kdf_upgrade` → 403 | Das CSRF-Token steht als `const CSRF`, nicht auf `window`. Gemessen wurde die CSRF-Sperre statt der Zusage, dass der Endpunkt das Demo-Konto überspringt |

Gegriffen wird seither am `form`-Attribut (`f-demo-anlegen`, `f-demo-reset`)
und am `id` des Profilformulars (`pfform`) — nicht am Beschriftungstext. Ein
Text ist eine Gestaltungsfrage; ein Formularbezug ist eine Aussage über den
Aufbau der Seite.

**Dazugekommen sind drei Erwartungen**, die es vorher nicht gab und die den
Anlass überdauern:

- **Das Schloss an den geschützten Feldern wird gezählt** (`.symbol-schutz`,
  heute 5 auf der Einsatzseite). `CLAUDE.md` 4 verlangt es an jedem
  verschlüsselten Feld; an dieser Stelle hat es bis S10/AP5 niemand
  nachgesehen.
- **`api/kdf_upgrade.php` überspringt das Demo-Konto** — jetzt eine
  Erwartung statt einer Protokollzeile. Das ist die tragende Zusage seit S10:
  Das Demo-Konto bekommt keinen Server-Anteil, seine Hülle bleibt `edk1:`.
- **Die E-Mail-Adresse steht nach der Abweisung noch da.** Eine Meldung ist
  eine Meldung; die Zusage ist, dass sich nichts geändert hat.

**Kartenkacheln zählen nicht mit, aber mit Beleg.** Der Prüfstand hat keinen
Weg ins Netz, jede Kachel scheitert. Gefiltert wurde bislang am *Text* der
Konsolenmeldung — der trägt die Adresse aber nicht immer: Nach mehreren
Versuchen meldet Chromium nur noch `Failed to load resource:
net::ERR_TOO_MANY_RETRIES`. Ein Lauf stand deshalb auf „1 Konsolenfehler".
Jetzt werden die Adressen der gescheiterten Anfragen mitgeschrieben, und eine
Meldung ohne Adresse gilt nur dann als Kachelrauschen, wenn im selben Lauf
tatsächlich eine fremde Kachel gescheitert ist. Scheitert etwas unter
`127.0.0.1`, bleibt der Fehler stehen.

**Der Rückgabewert folgt der Linie von `umstellungslauf.mjs`:** 0, solange es
keine Befunde und keine Konsolenfehler gibt — auch dann, wenn Abschnitte nicht
gemessen wurden. Die Zahl steht in der Schlusszeile; wer sie übersieht, liest
einen halb gefahrenen Lauf für einen ganzen.

Aufruf jeweils:

```
PLAYWRIGHT_MODUL=/opt/node22/lib/node_modules/playwright/index.mjs \
  node csv_import.mjs [basis] [email] [passwort] [csv] [ausgabeordner]
```

---

## Warum der Import nicht im Einspielskript steht

`import.php` enthält **keine** Verarbeitungslogik (so steht es im
Kopfkommentar der Datei, und so ist es auch). Die Datei wird nicht
hochgeladen: Der Browser liest sie, prüft sie, verschlüsselt die
geschützten Angaben und schickt erst das Ergebnis an
`api/import_commit.php`. Der Server bekommt Diagnose, Alter und Einsatzort
ausschließlich als Chiffretext zu sehen.

Ein Skript, das diesen Weg serverseitig nachbaute, prüfte deshalb gar nicht
den Weg, um den es geht. Es prüfte einen zweiten, den es nicht geben soll.

---

## Klickstrecke 1 — CSV-Import

Voraussetzung: Der Einspiellauf (B3) ist durch, die Datei
`generator/ausgabe/import/einsaetze.csv` liegt vor.

1. **Anmelden** unter `login.php` mit `demo@gen-em.org` / `nadokudemo0815`.
   Der Inhaltsschlüssel wird bei der Anmeldung entsperrt und liegt für die
   Dauer des Tabs bereit — ohne ihn kann der Import nicht verschlüsseln.
2. **Einstellungen → Import / Export** öffnen (`import.php`).
   Steht dort die Warnung „Verschlüsselung gesperrt", ist die Sitzung zwar
   gültig, der Schlüssel aber nicht im Tab: ab- und neu anmelden.
3. **Datei wählen**: `einsaetze.csv`.
   Die Seite erkennt das Format selbst. Erwartet: **CSV (Standard)** — das
   ist das Profil `export_csv_v1`, der verlustfreie Rückweg des eigenen
   Exports.
4. **Prüftabelle lesen.** Erwartet beim ersten Lauf in ein leeres Konto:
   `6 Zeilen — 2 Diensttage, 4 Einsätze, 0 Hinweise, 0 Fehler, 0 Dubletten`.
   **Woran ein Scheitern zu erkennen ist:** Steht dort eine Zahl bei
   „Fehler", ist die Schaltfläche unten gesperrt und nennt den Grund.
   Steht dort eine Zahl bei „Hinweise", auf die Zeilen klappen und den Text
   lesen — „Zeitstempel nicht lesbar" heißt, der Zonenversatz in der Datei
   stimmt nicht (siehe unten). Ein Hinweis sperrt den Import **nicht**: Die
   betroffenen Werte fallen still weg, und die Bilanz meldet trotzdem
   „0 Fehler".
5. **„Import ausführen"** klicken. Erwartet:
   `4 Einsätze angelegt, 0 überschrieben, 0 übersprungen`.
   Ein zweiter Lauf derselben Datei meldet **4 Dubletten** und legt nichts
   an — das ist richtig so und keine Fehlfunktion.
6. **Ersten Tag öffnen** über den Verweis in der Rückmeldung. Die vier
   Einsätze tragen in der Datenbank `origin = 'import'` und einen
   `client_ref` mit dem Präfix `imp-`.

### Zwei Fallen, beide erlebt

**Der Zonenversatz braucht einen Doppelpunkt.** `PARSERS.isoTs` in
`assets/import.js` prüft gegen `[+-]\d{2}:\d{2}`. Pythons `%z` liefert
`+0200` ohne Doppelpunkt — damit fallen die Endzeit und **alle acht
Phasenzeiten** durch, und zwar als *Hinweis*, nicht als Fehler. Der Import
meldet trotzdem „0 Fehler" und legt die Einsätze ohne Zeiten an. Der
Generator schreibt den Versatz deshalb von Hand zusammen
(`erzeugen.iso_offset`), und `generator/pruefen.py` hält die ganze Datei
gegen die Parser der Anwendung (Prüfung 5).

**Eine Zelle mit `=` am Anfang kommt leer an.** SheetJS liest sie als
Formel; der Wert ist danach weg. Der Exportweg schützt solche Zellen mit
einem vorangestellten `'` — der Importweg entfernt ihn nicht wieder. Die
Formel-Anfangszeichen des Referenzdatensatzes stehen deshalb auf dem
Formularweg, nicht auf dem CSV-Weg (Fund F-P1-G).

---

## Klickstrecke 2 — P-07, Angriffswerte

Fünf geschützte Felder des Referenzdatensatzes tragen absichtlich Markup,
das ein Browser ausführen würde (R20). Alle liegen am **Diensttag
21.11.2026**.

| Feld | Wert |
|---|---|
| Diagnose | `<img src=x onerror="alert('R20-dx')">Thoraxtrauma …` |
| Ortsbeschreibung | `"><script>alert('R20-ort')</script>Baustelle …` |
| Einsatznummer | `<svg/onload=alert('R20-nr')>2026-0335` |
| Einsatzort-Adresse | `<b onmouseover="alert('R20-adr')">Talstraße 7</b>, …` |
| Alter | `<img src=x onerror="alert('R20-alter')">` |

1. **Anmelden** wie oben.
2. **Tagesübersicht** des 21.11.2026 öffnen (`index.php?d=<Kennung>`).
   Erwartet: In der Spalte *Alter* steht der Text `<img src=x onerror=…>`
   sichtbar da, in *Diagnose* der Text mit dem `<img …>` davor.
   **Scheitern:** ein Dialogfenster, ein leeres Bild-Symbol in der Zelle,
   oder eine Zelle, die den Wert gar nicht zeigt.
3. **Einsatzsuche** (`suche.php`) öffnen, unten **„Alle N anzeigen"**
   klicken. Ohne diesen Klick steht nur die erste Seite der Trefferliste da
   und die betroffenen Einsätze sind gar nicht gerendert — dann sagt
   „nichts passiert" nichts aus.
4. **Zeitraum-Übersicht** (`zeitraum.php?y=2026&m=11`) öffnen, ebenfalls
   „Alle N anzeigen".
5. **Einsatzseite** des Einsatzes mit der Nummer `2026-0335` öffnen.
   Erwartet: Einsatznummer und Adresse stehen als Text da, mit Markup.
6. **Einsatzformular** desselben Einsatzes öffnen. Erwartet: dieselben
   Werte in den Eingabefeldern, unverändert.

In allen sechs Schritten gilt: **kein Dialogfenster, keine Konsolenmeldung,
kein Element aus der Nutzlast im Dokument.**

Das Skript prüft genau das und ersetzt dafür `window.alert`, `confirm` und
`prompt` **vor** dem ersten Seitenskript — ein `alert()` aus einem
`onerror`-Attribut liefe sonst gegen Playwrights stillen Dialog-Handler und
bliebe unbemerkt. Die Gegenprobe läuft mit: Mindestens eine Seite muss den
Wert tatsächlich anzeigen, sonst wäre die Prüfung gegenstandslos.

**Hier wurde ein echter Fehler gefunden** (F-P1-I, ausgeliefert als Web 7.2.1):
Die Spalte *Alter* gab ihren Wert unmaskiert aus. Gegen den Stand vor der
Korrektur meldet das Skript sechs Befunde über drei Seiten.

---

## `download_lib.mjs` — warten auf einen Download (21.09.2026)

```
node tools/referenzdatensatz/browser/download_lib.mjs --selbstprobe
```

**Erwartet: 10 Lagen, 0 offen.** Läuft in Stufe 1 bei jedem Push — ohne
Browser, ohne Anlage, ohne Netz.

### Warum es sie gibt

Am 21.09.2026 ist der Kreislauf `edbak` gegen Staging nach **fünfzehn
Minuten** gescheitert, und das Protokoll sagte genau einen Satz:

```
page.waitForEvent: Timeout 900000ms exceeded while waiting for event "download"
```

Mehr nicht. Nicht, ob der Export überhaupt angelaufen war; nicht, wie weit er
kam; nicht, ob der Browser einen Fehler geworfen hatte. **Fünfzehn Minuten
Messung, und als Ergebnis die Auskunft „es kam nichts".** Die Frage, die man
danach stellt — *lief er langsam oder hing er?* —, beantwortete das Protokoll
nicht, und ohne diese Antwort ist die nächste Messung ein Ratespiel.

Dieser Fehler stand vorher hinter dem **Botschutz von lima-city**: Der Lauf
kam nie bis zum Export, also hat ihn nie jemand gesehen.

### Was sie tut

Sie liest das Zustandsfeld alle drei Sekunden mit und legt im Fehlerfall
**Zustand, Verlauf und Konsolenfehler** in die Meldung:

```
Kein Download innerhalb der Zeitgrenze.
  Zustand von #expstate: Schritt 2 von 3
  Verlauf:            Schritt 1 von 3  →  Schritt 2 von 3
  Konsolenfehler:     TypeError: x ist undefined
  Ursprung:           page.waitForEvent: Timeout 900000ms exceeded …
```

**Der Verlauf ist der Punkt.** Ein Export, der bis „182 von 182 Dateien" kam
und dann stehenblieb, ist ein anderer Befund als einer, der nie eine Zeile
gemeldet hat — und für den zweiten Fall steht dort ausdrücklich
**`KEIN Fortschritt gemeldet`** statt einer leeren Liste.

### Wo sie gerufen wird

**Fünf Stellen in vier Skripten** — `kreislauf_edbak.mjs`,
`kreislauf_csv.mjs`, `papierkorb_misch.mjs` und `referenz_export.mjs`
(zweimal). Die halbe Fassung, die `referenz_export.mjs` als eigene Funktion
`mitFortschritt()` mitbrachte, ist damit abgelöst: Sie las den Fortschritt
zwar mit, **fing den Abbruch aber nicht ab**, und die drei anderen Skripte
hatten sie gar nicht.

### Was der Aufrufer weiterhin selbst tun muss

**Das Warten vor dem Klick anmelden.** `waitForEvent()` horcht erst ab dem
Aufruf. Wer es hinter das Bestätigen der Rückfragen setzt, verliert das
Rennen, sobald der Export schneller fertig ist als die Schleife ihre letzten
Leerläufe abwartet — der Download kommt, niemand hört zu, und das Skript
wartet bis zum Zeitlimit auf ein Ereignis, das längst vorbei ist. **Das sah
schon einmal aus wie ein Fehler der Anwendung und war einer des
Prüfmittels.**
