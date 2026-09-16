# Zusagen — begründete Ausnahmen

Die Anwendung gibt Versprechen, die bis Web 19.3.1 kein Mittel nachgezählt
hat: „kein natives `confirm()`", „jede Seite hat ihr Gerüst" und — seit dem
13.09.2026, Backlog Nr. 179 — **„keine fremde Quelle zur Laufzeit"**. Ein
Versprechen ohne Prüfmittel hält genau so lange, wie sich jemand daran
erinnert — die Prüfgruppe **5 Zusagen** in `pruefen.py` zählt sie jetzt nach.

**Bei „fremde Quelle" ist die Liste der eigentliche Inhalt.** Das Muster meldet
**jede** absolute Adresse in eigenem Quelltext, nicht nur die Ladekonstrukte —
ein Ausdruck, der nur `src=`, `<link href=`, `fetch(`, `url()` und `@import`
kennt, fand in diesem Bestand **0** Treffer, während fünf echte
Laufzeitquellen im Code standen (gemessen am 13.09.2026): Die Kacheln gehen
über `L.tileLayer(...)`, die Anschrift des Adressdienstes ist eine
PHP-Konstante. Die Spalte „Grund" sagt deshalb auch die **Art** des Treffers,
und es gibt vier: **gewollte Laufzeitquelle**, **Navigationsziel** (ein
`<a href>`, das ein Mensch anklickt — das lädt nichts), **XML-Namensraum**
(eine Kennung, keine Adresse) und **Beispieltext**.

Hier stehen die Stellen, an denen die Zusage **bewusst** nicht gilt. Ein
Eintrag ohne Grund ist keiner: Die Spalte „Grund" sagt, **warum** die Stelle
richtig ist, nicht nur dass sie geduldet wird.

**Die Liste wird in beide Richtungen geprüft.** Ein Treffer ohne Eintrag ist
ein Befund — und ein Eintrag, der nichts mehr erklärt, ebenso („Ausnahme
ungenutzt"). Sonst verwahrlost die Liste so still wie die Sache, gegen die
sie schützt; dieselbe Regel wie bei `ohne-regel.md` (Backlog Nr. 39).

**Die zweite Spalte ist die Datei, nicht die Zeile.** Eine Zeilennummer
altert mit dem nächsten Paket, das die Datei anfasst — genau daran ist der
Eintrag zu `phasen-name` in `ohne-regel.md` gealtert (er zeigte auf
`einsatz.php:631`, es waren 764 und 805).

| Prüfung | Datei | Muster | Grund |
|---|---|---|---|
| fremde Quelle | `kopfzeilen_lib.php` | `tile.openstreetmap.org` | **Weder Laden noch Navigieren: eine ERLAUBNISLISTE.** Die Adresse steht seit Web 20.7.0 ein zweites Mal im Quelltext — in `img-src` der Content-Security-Policy (P5a/AP4, E-P5a-15). Sie lädt dort nichts; sie sagt dem Browser, welche der vier Kacheldomains er laden DARF. Eine Richtlinie, die die Kacheln nicht nennt, macht die Karten grau — genau davor warnt Backlog Nr. 181. Dass die Zusage „keine fremde Quelle" damit zweimal auftaucht, ist richtig so: Sie ist an beiden Stellen dieselbe Entscheidung. |
| fremde Quelle | `kopfzeilen_lib.php` | `tile.openmaps.fr` | **Erlaubnisliste, wie oben** — die Wanderkarte in `img-src`. |
| fremde Quelle | `kopfzeilen_lib.php` | `server.arcgisonline.com` | **Erlaubnisliste, wie oben** — das Luftbild in `img-src`. *(Die vierte Kacheldomain, `https://*.tile.opentopomap.org`, steht dort ebenfalls — das Muster dieses Prüfmittels erkennt die Sternchenform nicht und meldet sie deshalb nicht. Ein Eintrag dafür stünde als „Ausnahme ungenutzt" da, also steht keiner. Wer das Muster einmal um Platzhalter erweitert, trägt ihn nach.)* |
| fremde Quelle | `assets/map_layers.js` | `tile.openstreetmap.org` | **Gewollte Laufzeitquelle: die Standard-Kartenkacheln.** Der Dateikopf nennt Herkunft und Lizenz (OpenStreetMap, ODbL). Karten ohne Kachelserver gibt es nicht; die Alternative wäre ein eigener Kachelserver, und der ist keine Entscheidung dieses Prüfmittels. |
| fremde Quelle | `assets/map_layers.js` | `tile.openmaps.fr` | **Gewollte Laufzeitquelle: die Wanderkarte** (OpenHikingMap). Eine der vier wählbaren Grundkarten, Lizenzhinweis im Dateikopf. |
| fremde Quelle | `assets/map_layers.js` | `tile.opentopomap.org` | **Gewollte Laufzeitquelle: die topografische Karte** (OpenTopoMap, CC-BY-SA). Die Adresse trägt den Platzhalter `{s}` für die Unterbereiche a/b/c — das Muster deckt ihn mit ab. |
| fremde Quelle | `assets/map_layers.js` | `server.arcgisonline.com` | **Gewollte Laufzeitquelle: das Luftbild** (Esri World Imagery). Die vierte Grundkarte; Nennung im Kartenschild, Herkunft im Dateikopf. |
| fremde Quelle | `assets/map_layers.js` | `wiki.openstreetmap.org` | **Navigationsziel, kein Laden.** Ein `<a href>` im Kartenschild der Wanderkarte — der Lizenzhinweis, den CC-BY-SA verlangt. Ein Mensch klickt ihn; die Seite lädt nichts davon. |
| fremde Quelle | `assets/map_layers.js` | `//openmaps.fr` | **Navigationsziel, kein Laden.** Der Spendenhinweis (`/donate`), den der Kachelserver der Wanderkarte als Gegenleistung erbittet. **Das Muster beginnt mit `//`**, damit es nicht auch auf `tile.openmaps.fr` passt — die Kachelzeile darüber ist eine andere Sache und braucht ihren eigenen Eintrag. |
| fremde Quelle | `assets/map_layers.js` | `www.openstreetmap.org` | **Navigationsziel, kein Laden.** Der Verweis auf die Lizenzseite von OpenStreetMap (`/copyright`), von der ODbL verlangt. |
| fremde Quelle | `assets/map_layers.js` | `opentopomap.org` | **Navigationsziel, kein Laden.** Der Lizenzhinweis von OpenTopoMap. |
| fremde Quelle | `assets/map_layers.js` | `www.esri.com` | **Navigationsziel, kein Laden.** Die Nennung, die die Nutzungsbedingungen des Luftbilds verlangen. |
| fremde Quelle | `assets/ortswahl.js` | `tile.openstreetmap.org` | **Gewollte Laufzeitquelle, und ein Rückfall:** Der Kartendialog nimmt `attachBaseLayers()` aus `map_layers.js`, wenn es geladen ist — steht es nicht zur Verfügung, legt er selbst die Standardkachel. Ohne den Rückfall wäre der Dialog eine leere Fläche. |
| fremde Quelle | `geocoder_lib.php` | `photon.komoot.io` | **Gewollte Laufzeitquelle, und ausdrücklich abschaltbar:** `GEOCODER_VORGABE` ist die **Vorgabe** der Adresssuche, nicht ihre Festlegung — seit S9/AP2 steht die Dienstadresse als Einstellung (`app_state.geocoder_url`), und `geocoder_an()` schaltet die Suche je Installation und Konto ab (R79). Der Wert steht hier, damit eine frische Installation etwas tut. |
| fremde Quelle | `betrieb_server.php` | `photon.komoot.io` | **Beispieltext, kein Laden.** Die Adresse steht in der Fehlermeldung des Eingabefelds für die Dienstadresse — „(z. B. `https://photon.komoot.io`)". Ein Text, den ein Mensch liest. |
| fremde Quelle | `config.example.php` | `einsatz.example.de` | **Beispieltext in der Vorlage.** `config.example.php` wird nicht ausgeliefert und nicht ausgeführt; sie zeigt, wie `base_url` aussieht. `example.de` ist eine reservierte Beispieldomäne. |
| fremde Quelle | `gpx_lib.php` | `www.topografix.com` | **XML-Namensraum, keine Adresse.** `GPX_NS` ist die Kennung des GPX-1.1-Formats; sie steht als Zeichenkette im geschriebenen XML und wird **nie abgerufen**. Wer sie ändert, macht die Datei ungültig. |
| fremde Quelle | `ui.php` | `github.com` | **Navigationsziel, kein Laden.** Der Verweis auf die Lizenzdatei des Projekts in der Fußzeile jeder Seite. |
| native Dialoge | `assets/confirm.js` | `window.confirm` | **Der Rückfall der Ersatzfunktion selbst.** `edConfirm()` baut den Dialog aus `<dialog>`; kann ein Browser das nicht, bleibt nur der native. Hier das native `confirm()` zu verbieten hieße, den Rückfall zu streichen und den Browser ohne Rückfrage zu lassen. |
| native Dialoge | `assets/forms.js` | `window.confirm` | **Der Rückfall der Formularrückfrage.** Sie greift, wenn `confirm.js` nicht geladen ist — etwa weil eine Seite es nicht einbindet. Ohne ihn verschwände die Rückfrage ersatzlos, und ein Formular mit Datenverlust schickte sich wortlos ab. |
| Seite ohne Gerüst | `install.php` | `ui_seite_start` | **Vor der ersten Anmeldung.** Der Einrichtungs-Assistent läuft nur, solange weder `config.php` noch `install.lock` existieren — es gibt kein Konto, keine Sitzung und keinen Diensttag, den eine Leiste zeigen könnte. |
| Seite ohne Gerüst | `login.php` | `ui_seite_start` | **Die Anmeldung selbst.** Ein Gerüst zeigt Diensttag-Leiste und Menü; beides setzt die Sitzung voraus, die diese Seite erst herstellt. |
| Seite ohne Gerüst | `reset_request.php` | `ui_seite_start` | **Zugang ohne Sitzung.** Wer sein Passwort vergessen hat, kommt nicht herein — die Seite ist bewusst ohne Anmeldung erreichbar (mit Mengenbegrenzung, M1-08). |
| Seite ohne Gerüst | `wiederherstellen.php` | `ui_seite_start` | **Der Rückweg zum Komplett-Backup**, wenn der Webspace weg ist (E-S2-20). Sie bringt ihre eigene Sitzung mit; `auth_guard.php` gibt es hier nicht, weil es die Installation noch nicht gibt, zu der man sich anmelden könnte. |
| Seite ohne Gerüst | `pw_handling.php` | `ui_seite_start` | **Passwort über einen Einmal-Link setzen.** Der Dateikopf sagt es ausdrücklich: „Die Seite hat keine Sitzung und zeigt den Standard der Installation" (E-P3-20). Sie ist die einzige Stelle, an der ein Passwort vergeben wird — vor jeder Anmeldung. |
| Seite ohne Gerüst | `rechtstext_seite.php` | `ui_seite_start` | **Öffentliche Rechtstexte** (R32). Impressum und Datenschutz müssen ohne Anmeldung erreichbar sein; der Dateikopf nennt genau das als Grund, `auth_guard.php` nicht einzubinden. |
| Seite ohne Gerüst | `session_lib.php` | `ui_seite_start` | **Keine Seite, sondern die Abmeldung.** `session_beenden()` ruft `session_destroy()` und gibt danach die Zwischenseite „Du wirst abgemeldet …" aus. Zum Zeitpunkt der Ausgabe gibt es die Sitzung nicht mehr — ein Gerüst wäre dort nicht nur falsch, es ließe sich nicht bauen. |
