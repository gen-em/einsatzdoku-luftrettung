# Prüfdokument — Sofortpaket Sicherheit (Rahmenplan Schritt 9a, R78)

**Stand:** 07.09.2026 · **Web 15.6.0** · Zweig
`claude/sofortpaket-sicherheit-1t70p1` · Android-Teil **offen**.

Dieses Dokument beantwortet die Frage „**was muss ich noch tun?**" — nicht die
Frage „ist es belegt?"; die beantworten die Commit-Nachrichten und der
Changelog. Es steht neben der Spezifikation
`docs/konzepte/Vorbereitung-Sicherheitspaket.md`.

---

## 0. Was **nicht** geprüft werden konnte — und warum

Das steht am Anfang, nicht in einer Fußnote.

| Was | Warum nicht | Wer es prüfen muss |
|---|---|---|
| **Der Wortlaut der Hinweismail** beim E-Mail-Wechsel (Nr. 128) | Im Prüfcontainer ist **kein SMTP eingerichtet**. Belegt ist nur, dass der Versand **versucht** wird und sein Fehlschlag den Wechsel nicht zurückrollt — die Protokollzeile `Adresswechsel: Hinweismail an die alte Adresse ging nicht weg` steht im Serverprotokoll | Auftraggeber, Prüfliste P-7 |
| **Die Integritätswache gegen die Produktivinstallation** (Nr. 140) | Es gibt hier keine. Gemessen ist sie gegen die **lokale** Installation (112 Dateien) und gegen eine künstliche Manipulation | Auftraggeber, P-9 — beim ersten Lauf der Action |
| **Ein GPX aus einem echten Gerät** (Nr. 130) | Im Container liegt kein Gerätemitschnitt. Geprüft ist gegen erzeugte Dateien und den Referenzexport | Auftraggeber, P-4 |
| **Die Uhr selbst** am Ersetzfenster (Nr. 134) | Kein Gerät. Geprüft ist `ingest.php` über echtes HTTP, nicht das Verhalten der Uhr auf `kept_points` | Auftraggeber, P-6 |
| **`/apk/` und `/demo/` auf dem Produktivserver** (Nr. 129) | Gemessen unter einem **im Container aufgesetzten Apache** mit derselben `.htaccess`. Ob der Produktiv-Webspace `AllowOverride` erlaubt und mod_rewrite lädt, sagt nur der Produktivserver | Auftraggeber, P-3 |
| **Der Android-Teil** (Nr. 142–145, Räumteil 114) | Noch nicht gebaut — er ist Teil 2 dieses Schritts | — |

**Eine Bemerkung zur Umgebung, weil sie für jede Zahl hier gilt:** Der
Container brachte weder Datenbank noch Android-SDK mit. Beides holt
`tools/containeraufbau/aufbau.sh` nach (MariaDB 10.11.14, Android-SDK 36);
die Anwendung selbst steht über
`tools/referenzdatensatz/einspielen/lokal_einrichten.sh` — **88 Einsätze,
16 Diensttage, 2 Geräte**. Für Nr. 129 kam ein **Apache 2.4 mit mod_ssl,
mod_rewrite und einer Durchreichung der PHP-Anfragen an den eingebauten
Server** dazu: Der PHP-Entwicklungsserver liest **keine `.htaccess`** — ohne
diesen Umweg hätte der Punkt keine Zahl.

---

## 1. Je Punkt: Mittel, Soll, Ist

| Nr. | Mittel | Soll | Ist (gemessen 07.09.2026) |
|---|---|---|---|
| **136** SP-1 | Browserlauf gegen die lokale Installation, DB-Abfrage | Anmeldung vor/nach der Anhebung, Rundenzahl steigt still | Anmeldung mit Konto auf 320 000 → `api/kdf_upgrade.php` antwortet `{"ok":true,"iter":600000}`, `users.kdf_iter` **320000 → 600000**; Inhaltsschlüssel danach weiterhin entpackbar (Prüfsumme `66303b53…`). Ableitung **298 ms** (320 000) gegen **551 ms** (600 000), Übergang **849 ms** — Median aus je 5 Läufen im Browser |
| **136** SP-2 | Browserlauf, Regelprobe | Mindestlänge 12, Passphrasen möglich | `minlength` der Passwortfelder **12**, `EdPwQuality.MIN_LAENGE` 12, `MIN_REST` 8. „Anker-Winter-Regen-Glas" **angenommen**, „Winterurlaub2026" **abgewiesen**, „Rettung2026Notarzt" **abgewiesen**, „elfzeichen1" **abgewiesen**. Passwörter aller Prüfmittel bleiben gültig (8 geprüft) |
| **136** Fund F-9a-01 | Browserlauf mit Netzverfolgung | stille Anhebung läuft beim nächsten Anmelden | **Vorher: lief nicht** — Konto auf 320 000, Anmeldung, `suche.php`, Rundenzahl unverändert, Vormerkfach verworfen. Nachher: siehe oben |
| **136** Fund F-9a-02 | Browserlauf auf `betrieb_status.php` | Wartungsseite sagt, wann der Altwert weg darf | Zeile „Schlüsselableitung" nennt jetzt „**1 Konto/Konten stehen noch unter dem Zielwert 600000**", Plakette „Übergang läuft" |
| **127** | Browserlauf **und** HTTP | ohne Token abgewiesen, mit Token angemeldet (2 von 2) | **2 von 2**: mit Token → `/index.php`; Feld aus dem Formular entfernt → abgewiesen mit „Das Formular ist abgelaufen." Dazu HTTP: ohne Feld und mit falschem Feld je 200 + dieselbe Meldung, mit richtigem Feld der gewohnte Fehlerzweig. Token vor der Anmeldung `69018804ce9d…`, danach `bf304fc953d5…` — **gewechselt** |
| **128** | Browserlauf | falsches Passwort → abgewiesen (1 von 1) | **4 von 4**: nur Name ändern, Feld leer → gespeichert · Adresse mit falschem Passwort → abgewiesen, Adresse unverändert · Adresse ohne Passwort → Seite hält an · Adresse mit richtigem Passwort → gespeichert, Mailversuch im Protokoll |
| **129** | Apache mit der echten `.htaccess` | zwei Aufrufe → 403 | **vier Aufrufe → 403** (`apk/`, `apk/<datei>.apk`, `demo/`, `demo/fixture.json.gz`); `login.php` 200, `assets/style.css` 200, `index.php` 302. Mitgemessen: `config.php`, `db.php`, `auth_guard.php`, `schema.sql` je **403** |
| **130** | `tools/gpxprobe/` Teil 8 | n Proben, 0 durch | **8 Proben, 0 durch**, saubere Datei geht durch. Am Stand davor: UTF-16LE mit DOCTYPE **ging durch, 2 Punkte**. Probe insgesamt **88 Erwartungen, 2 nicht erfüllt** (beide vorbestehend, siehe Abschnitt 2) |
| **131** | HTTP, unangemeldet, mit absichtlich falschem DB-Namen | keine Zahl, kein Fehlertext | Vorher: `Access denied for user 'nadoku'@'localhost' to database 'gibtesnicht'` und „stehen **2** Konten". Nachher: Kennung `798FF2B9`, **keiner** der Begriffe `nadoku`, `SQLSTATE`, `gibtesnicht`, `127.0.0.1`, `Unknown database` in der Antwort; volle Zeile im Protokoll |
| **133** | PHP-Probe gegen die Installation | Bauordner nach Fehlschlag weg | **1 auf 0** in beiden Fällen: Aufräumlauf ohne Fälligkeit, und ein Lauf, der wirft (Zustand `abgebrochen`, `geraeumt=true`, Protokollzeile) |
| **134** | `tools/ingestprobe/` Teil 9 | 1 angenommen, 1 abgewiesen | **1 angenommen** (Phasen ersetzt, 5 Punkte angehängt, keine `kept_*`-Felder), **1 abgewiesen** (HTTP 200 `ok`, `kept_phases` 2, `kept_points` 5, Phasen unverändert bei lat 40.0 statt gesendeter 10.0, Zeilen 10 vorher / 10 nachher, `next_seq` trotzdem 15). Neuer Einsatz entsteht weiterhin. Probe **47 Erwartungen, 0 nicht erfüllt** |
| **135** | maschinelle Einteilung + Browserlauf | Stellen vorher/nachher | **79** `json_encode()`-Aufrufe unter `server/`, davon **44 in einem `<script>`** (alle umgestellt) und **35 außerhalb** (unverändert). Gegenprobe: 0 verbliebene in einem Skriptblock, 44 `json_js()`. Wirkung mit Profilnamen `<!--<script>` auf `import.php`: vorher fehlten `KONTO_NAME`, `APP_TZ` **und** `WEB_VERSION`; nachher stehen alle drei |
| **138** | Lesen | vier Dokumente sagen dasselbe | `CLAUDE.md` 4, `README.md`, `Technik.md` 4.98, `Handbuch.md` 5 — dazu der Textbaustein in Handbuch 11.5. Keine Codeänderung, keine Versionsstufe für sich |
| **140** | Werkzeuglauf + Manipulation | Wartungsprobe um eine Erwartung (Abweichung erkannt) | Selbstprobe **7 Erwartungen, 0 nicht erfüllt**, davon zweimal ausdrücklich „Abweichung erkannt". Lauf gegen die Installation: **112 Dateien, 112 gleich, 1 Inline-Block gleich, 0 nicht vergleichbar** → „Kein Unterschied". Gegenprobe mit **einer** veränderten Kennung in `crypto.js`: **111 gleich, 1 abweichend**, Rückgabewert 1. `tools/wartungsprobe/` **12a** neu → **51 statt 50 Erwartungen, 0 nicht erfüllt** |

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Mittel | Zahl | Bemerkung |
|---|---|---|
| `php -l` | **114 Dateien, 0 Syntaxfehler** | `server/`, `server/api/`, `tools/*/` |
| `node --check` | **32 Dateien, 0 Syntaxfehler** | `server/assets/*.js` |
| `tools/wortliste/` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** | 87 Regeln (eine kam dazu, siehe F-SP-P-01) |
| `tools/vollstaendigkeit/` | **300 → 301 Befunde** | Der eine neue ist eine **Ellipse in einem PHP-Kommentar** (`gpx_lib.php:328`) — dieselbe Rauschklasse wie die 227 vorhandenen „Unicode-Zeichen als Symbol im Markup". Gegen den Abzweigpunkt `4b442de` gemessen, nicht gegen den lokalen `main` (der stand auf PR #27) |
| `tools/linkprobe/` | **99 Zielseiten, 132 Verweise, 0 unbekannte Abweichungen, 1 bekannt mit Nummer (151), 0 tote Zeilen** | unverändert gegenüber Web 15.5.2 |
| `tools/gpxprobe/` | **88 Erwartungen, 2 nicht erfüllt** | Beide vorbestehend: Der Referenzexport im Repositorium ist älter als die frisch eingespielte Datenbank. **Gegengeprüft am Stand vor der Änderung: 77 Erwartungen, dieselben 2** |
| `tools/ingestprobe/` | **47 Erwartungen, 0 nicht erfüllt** | vorher 39; Teil 9 neu, Zeitstempel auf `time()` umgestellt |
| `tools/wartungsprobe/` | **51 Erwartungen, 0 nicht erfüllt** | vorher 50; 12a neu |
| `tools/integritaetswache/` | **7 Erwartungen Selbstprobe, 0 nicht erfüllt** · Lauf **112/112**, Gegenprobe **1 abweichend** | neu |
| `tools/screenshots/` (Zeiger) | **112 Einzelbilder, 14 Kontaktbögen, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe falscher Höhe** | 14 berührte Seiten × 8 Breiten |
| `tools/screenshots/ --finger` | **dieselben Zahlen bei 44 px** | beide Bedienhöhen |
| Gegenprobe des Bilderlaufs | **112 Bilder, 112 verschiedene Prüfsummen, 0 Doppelte** | Die Falle aus F-P3-AQ (176 Bilder zeigten die Anmeldeseite) ist damit ausgeschlossen |
| `tools/screenshots/kontrast.py` | **21 Paare, 0 verfehlt** | `style.css` unverändert |

**Was der Bilderlauf gemessen hat**, damit die Zahl etwas bedeutet: die 14
Seiten `01-anmeldung`, `06-wiederherstellen`, `10-tagesuebersicht`,
`12-einsatzansicht`, `13-einsatzformular`, `14-zeitraum`, `15-suche`,
`30-einstellungen-profil`, `34-einstellungen-backup`, `35-import-export`,
`41-kontoseite`, `42-stammdaten-systemweit`, `43c-komplettsicherung`,
`45-betrieb-status` — je acht Breiten von 360 bis 1920 px.

---

## 3. Was im Browser geprüft wurde

Gegen die lokale Installation (`https://127.0.0.1:8443`, Apache mit TLS,
PHP-Anfragen durchgereicht), angemeldet als Admin und als Demo-Konto:

- **Nr. 136:** Anmeldung, stille Anhebung, `minlength`, die vier
  Passwortfälle, Wartungsseite.
- **Nr. 127:** Anmeldung mit und ohne Formularfeld, Tokenwechsel.
- **Nr. 128:** die vier Fälle des Profilformulars.
- **Nr. 135:** `import.php` und `einstellungen.php` mit einem bösartigen
  Profilnamen.
- **Alle Krypto-Seiten** nach der CSRF-Änderung: `index.php`, `suche.php`,
  `zeitraum.php`, `einsatz_form.php`, `import.php`,
  `einstellungen.php?t=konto`, `?t=backup` — je **CSRF vorhanden**,
  **0 Seitenfehler**.

> **Die Konsolenfehler im Bilderlauf sind Kachelabrufe.** Der Prüf-Browser
> kommt in dieser Umgebung nicht an `tile.openstreetmap.org`
> (`ERR_CONNECTION_RESET`); der Bilderlauf des Repositoriums fängt genau diese
> Abrufe ab und zählt sie nicht — deshalb steht dort 0. In meinen eigenen
> Wegwerfläufen erscheinen sie und sind **keine** Befunde.

---

## 4. Funde beim Bauen

| Kennung | Fund | Erledigt |
|---|---|---|
| **F-9a-01** | Die stille Anhebung der Rundenzahl lief **nicht beim nächsten Anmelden**. Sie ruft `api/kdf_upgrade.php` und braucht `CSRF`; `ui_krypto_bootstrap()` gab das nur auf Anfrage aus, und drei von sieben Seiten fragten. Die erste Seite ohne CSRF **verwarf das Vormerkfach** und nahm der Anhebung damit für die ganze Sitzung die Grundlage — beim nächsten Anmelden dasselbe | ja, in Nr. 136: CSRF steht immer |
| **F-9a-02** | Die Wartungsseite meldete nur **verwaiste** Rundenzahlen — den Fall, dass jemand den Altwert zu früh gestrichen hat. Die Frage davor, wann er gestrichen werden **darf**, beantwortete sie nicht; SP-1 nimmt an, sie täte es | ja, in Nr. 136 |
| **F-SP-P-01** | Die Wortliste schlug beim neuen Kasten „Uhr verloren? Sofort trennen" auf **„Garmin"** an. Hier ist die Plattform aber die Sache selbst: Für die **Wear-OS**-Uhr gilt der Satz gerade **nicht**, sie kennt weder Serveradresse noch Schlüssel. Ein gerätefreies „Uhr" wäre keine Neutralität, sondern eine falsche Warnung | ja: Ausnahme `handbuch-geraete-verlust-garmin` mit Begründung |
| **F-SP-P-02** | Die **Ingestprobe** stand auf festen März-Daten. Mit dem Ersetzfenster prüften zehn ihrer Erwartungen zweite Pakete an Datensätzen außerhalb des Fensters — ein Fall, den es im Betrieb nicht gibt | ja, in Nr. 134: Zeitstempel an `time()` |
| **F-SP-P-03** | Der Seitenbruch aus K-15 entsteht **nicht** über `</script>` — `json_encode()` schreibt `<\/script>`, ein schließendes Tag kann aus einem Wert gar nicht entstehen. Der Weg ist `<!--<script>` | in Nr. 135 gemessen und behoben |

---

## 5. Entscheidungen, die beim Bauen gefallen sind

1. **Die Sperrliste der Passwortprüfung rechnet den Anteil statt des
   Vorkommens.** SP-2 empfiehlt Passphrasen **und** will die Liste erweitern;
   beides zusammen ging nicht, weil „Anker-Winter-Regen-Glas" an „winter"
   scheiterte. **Diese Entscheidung braucht Ihre Bestätigung** — sie ist eine
   Regeländerung, keine Zahl. Rückgängig ist sie mit einem Commit.
2. **Die Ortsnamen des eigenen Standorts kommen nicht in die Liste.** Sie
   stehen in den Stammdaten; sie an die Passwortseite auszugeben hieße, sie
   auch der **unangemeldeten** Seite auszugeben. Der Satz steht stattdessen im
   Handbuch.
3. **`kept_points` ist ein neues Feld im JSON-Vertrag.** Der Auftrag sah dort
   „nur die Nennung des Ersetzfensters" vor. Ohne das Feld wäre ein
   abgewiesenes Paket von einem übernommenen nicht zu unterscheiden — genau
   der Fehler, gegen den die übrigen `kept_*` gebaut sind.
4. **Der Bauordner wird auch bei Fehlschlag geräumt, und „Fortsetzen" fällt
   damit weg.** Der Preis ist Rechenzeit, nicht Daten.
5. **Handbuch 10 statt 12** für „Uhr verloren → sofort trennen": Das
   Gerätekapitel ist seit S8 Abschnitt 10; 12 ist der Betrieb.
6. **Nr. 153 neu**, damit der `querySelector`-Teil von Nr. 135 nicht
   unsichtbar wird.

---

## 6. Prüfliste für den Auftraggeber

Was nur am Gerät, am Produktivserver oder mit echtem Postfach geht. Je Punkt:
Weg, erwartetes Ergebnis, **woran ein Scheitern zu erkennen ist**.

### P-1 · Anmeldung nach der Rundenanhebung (Nr. 136) — **zuerst**
1. Nach dem Deploy an **Ihrem** Konto anmelden.
2. Die Anmeldung dauert einmalig spürbar länger (etwa doppelt).
3. Danach **Betrieb → Status**, Zeile „Schlüsselableitung".

**Erwartet:** Die Anmeldung gelingt. Nach der ersten Seite, die geschützte
Angaben braucht, steht dort „Alle Konten rechnen mit einer Rundenzahl, die
diese Fassung anbietet (600000, 320000)" — die Zahl der Konten unter dem
Zielwert sinkt mit jedem Konto, das sich anmeldet.
**Scheitern erkennt man an:** „Anmeldung fehlgeschlagen" trotz richtigem
Passwort (dann fehlt **320000** in `KDF_ITER_LISTE` — sofort melden, nichts
weiter tun), oder die Zahl unter dem Zielwert bleibt nach mehreren Anmeldungen
stehen (dann greift die stille Anhebung nicht).

### P-2 · Passwortregel im Alltag (Nr. 136)
Ein neues Passwort setzen (Profil → Passwort ändern) und dabei **eine
Passphrase aus vier Wörtern** probieren.
**Erwartet:** Sie wird angenommen, der Balken zeigt „gut" oder „stark".
**Scheitern:** Die Seite weist eine vernünftige Passphrase ab — dann ist die
Anteilsrechnung zu streng, und Entscheidung 1 aus Abschnitt 5 gehört
zurückgenommen. **Bitte auch dann melden, wenn es funktioniert** — die Regel
steht unter Ihrer Bestätigung.

### P-3 · `/apk/` und `/demo/` auf `nadoku.gen-em.org` (Nr. 129)
Im Browser aufrufen: `https://nadoku.gen-em.org/apk/` und
`https://nadoku.gen-em.org/demo/fixture.json.gz`.
**Erwartet:** beide **403 Forbidden**. Danach **Einstellungen → Geräte**
öffnen und eine APK herunterladen — sie muss weiterhin kommen.
**Scheitern:** 200 mit Inhalt oder Verzeichnisliste (dann wertet der Webspace
die `.htaccess` nicht aus — melden, die Sperre muss dann anders gesetzt
werden); oder der APK-Download bricht (dann greift die Regel zu weit).

### P-4 · GPX-Import aus dem echten Gerät (Nr. 130)
Eine GPX-Datei aus Ihrer Uhr oder einer anderen Software importieren
(Tagesübersicht → „···" → GPX importieren).
**Erwartet:** Sie geht durch wie bisher.
**Scheitern:** „Die Datei ist nicht in UTF-8 kodiert" oder „enthält ein
Nullbyte". Dann bitte **die Datei aufheben und melden** — dann schreibt Ihr
Gerät eine andere Kodierung, und die Grenze gehört überdacht.

### P-5 · Profil: E-Mail-Wechsel (Nr. 128)
Im Profil den **Namen** ändern (Passwortfeld leer) → speichern. Danach die
**E-Mail-Adresse** ändern, Passwortfeld erst leer, dann falsch, dann richtig.
**Erwartet:** Name geht ohne Passwort; Adresse ohne/mit falschem Passwort wird
abgewiesen und **bleibt unverändert**; mit richtigem Passwort wird sie
gespeichert.
**Scheitern:** Die Adresse ändert sich ohne Passwort — dann greift die Prüfung
nicht.

### P-6 · Uhr-Kopplung und Ersetzfenster (Nr. 134) — **nach dem Deploy**
1. Einen Dienst mit der Uhr fahren und normal hochladen lassen.
2. Danach einen **älteren, unbearbeiteten** Einsatz (> 72 h) von der Uhr
   erneut senden lassen, falls das ohne Aufwand geht.

**Erwartet:** Der laufende Dienst kommt vollständig an. Der alte Einsatz
bleibt unverändert, und die Uhr meldet **keinen Fehler** und wiederholt nicht.
**Scheitern:** Die Uhr hängt in einer Wiederholschleife (dann antwortet der
Server nicht mit `ok`), oder ein **aktueller** Nachtrag kommt nicht an (dann
ist das Fenster falsch gerechnet — bitte mit Uhrzeit melden).

### P-7 · Die Hinweismail (Nr. 128) — braucht ein echtes Postfach
Nach P-5 in das Postfach der **alten** Adresse sehen.
**Erwartet:** Eine Mail „Anmeldeadresse geändert" mit alter und neuer Adresse
und dem Satz „Warst du das nicht, handle bitte sofort".
**Scheitern:** keine Mail (dann im Serverprotokoll nach `Adresswechsel:
Hinweismail` sehen — steht sie dort, klemmt der Mailweg, nicht der Code); oder
die Mail geht an die **neue** Adresse (das wäre ein Fehler und gehört
gemeldet).

### P-8 · Nach dem Deploy: die Wartungsseite (alle Punkte)
**Betrieb → Status** durchsehen.
**Erwartet:** keine rote Zeile. „Schlüsselableitung" darf „Übergang läuft"
zeigen, solange Konten unter dem Zielwert stehen.
**Scheitern:** „Anmeldung blockiert" in der Zeile Schlüsselableitung — dann
sofort melden.

### P-9 · Die Integritätswache im Postfach (Nr. 140)
1. Nach dem Merge auf `main` im **Actions**-Tab den Lauf „Integritätswache"
   ansehen (er startet nach dem Deploy von selbst).
2. Sicherstellen, dass GitHub Ihnen Fehlschläge schickt: Profil →
   Notifications → **Actions** → „Only notify for failed workflows" reicht.
3. Steht die Installation nicht unter `https://nadoku.gen-em.org`, die
   Repository-Variable **`WACHE_BASIS`** setzen (Settings → Secrets and
   variables → Actions → Variables).

**Erwartet:** grüner Lauf mit „Kein Unterschied" und der Zeile
„112 Dateien … 112 gleich".
**Scheitern:** rot. Dann **zuerst** prüfen, ob gerade deployt wurde und ob
`main` weiter ist als die Auslieferung — die Reihenfolge steht in
`tools/integritaetswache/LIESMICH.md`. Erst wenn beides nicht passt, ist es
eine Manipulation: **nichts überschreiben**, die abweichende Datei per FTPS
herunterladen und beiseitelegen, FTPS-Zugangsdaten wechseln, und jedes
Passwort als möglicherweise mitgelesen behandeln.

### P-10 · Zuarbeit, die nicht im Repositorium erledigt werden kann (Nr. 140)
- **Branch-Schutz** auf `main` mit Review-Pflicht.
- **2FA-Zwang** in der Organisation.

Beides steht seit R78 aus und ist der Teil von Nr. 140, den die Wache
ausdrücklich **nicht** ersetzt: Sie erkennt einen Angreifer mit Push-Recht
nicht.

---

## 7. Grenzen der benutzten Prüfmittel

- **Der Bilderlauf klickt keinen Knopf.** Er fotografiert und misst Überlauf,
  Konsolenfehler und Knopfhöhen. Die Bedienwege dieser Stufe (Anmeldung,
  Profilformular) sind deshalb **einzeln** im Browser gefahren worden, nicht
  vom Bilderlauf.
- **Die Vollständigkeitsprüfung zählt auch Kommentare.** Ihre Kategorie
  „Unicode-Zeichen als Symbol im Markup" trifft Ellipsen in PHP-Kommentaren;
  daher 301 statt 300.
- **Die Integritätswache misst, was HTTP ausliefert**, nicht, was auf der
  Platte liegt, und sie sieht keinen PHP-Code außer dem Inline-Block der
  Anmeldeseite.
- **`tools/gpxprobe/` Teil 8 ruft `gpx_lesen()` unmittelbar auf**, nicht über
  HTTP. Die Funktion ist die Abwehr und hat genau einen Aufrufer
  (`api/gpx_import.php:115`).
- **Die Ingestprobe fährt Grenzfälle, nicht die Menge.** Der vollständige
  Sendeplan ist `tools/referenzdatensatz/einspielen/`.
- **Kein Nebenläufigkeitsprüfstand.** Ein Upload genau während eines
  Verdichtungslaufs lässt sich hier nicht herstellen.

---

## 8. Wenn die Prüfliste abgehakt ist

Dieses Dokument wird gelöscht (CLAUDE.md 7). Vorher gehört in
`docs/Rahmenplan.md` Abschnitt 8 die Erledigt-Zeile für Schritt 9a — sie steht
noch aus, weil der **Android-Teil** offen ist.
