# Zusagen — begründete Ausnahmen

Die Anwendung gibt Versprechen, die bis Web 19.3.1 kein Mittel nachgezählt
hat: „kein natives `confirm()`" und „jede Seite hat ihr Gerüst". Ein
Versprechen ohne Prüfmittel hält genau so lange, wie sich jemand daran
erinnert — die Prüfgruppe **5 Zusagen** in `pruefen.py` zählt sie jetzt nach.

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
| native Dialoge | `assets/confirm.js` | `window.confirm` | **Der Rückfall der Ersatzfunktion selbst.** `edConfirm()` baut den Dialog aus `<dialog>`; kann ein Browser das nicht, bleibt nur der native. Hier das native `confirm()` zu verbieten hieße, den Rückfall zu streichen und den Browser ohne Rückfrage zu lassen. |
| native Dialoge | `assets/forms.js` | `window.confirm` | **Der Rückfall der Formularrückfrage.** Sie greift, wenn `confirm.js` nicht geladen ist — etwa weil eine Seite es nicht einbindet. Ohne ihn verschwände die Rückfrage ersatzlos, und ein Formular mit Datenverlust schickte sich wortlos ab. |
| Seite ohne Gerüst | `install.php` | `ui_seite_start` | **Vor der ersten Anmeldung.** Der Einrichtungs-Assistent läuft nur, solange weder `config.php` noch `install.lock` existieren — es gibt kein Konto, keine Sitzung und keinen Diensttag, den eine Leiste zeigen könnte. |
| Seite ohne Gerüst | `login.php` | `ui_seite_start` | **Die Anmeldung selbst.** Ein Gerüst zeigt Diensttag-Leiste und Menü; beides setzt die Sitzung voraus, die diese Seite erst herstellt. |
| Seite ohne Gerüst | `reset_request.php` | `ui_seite_start` | **Zugang ohne Sitzung.** Wer sein Passwort vergessen hat, kommt nicht herein — die Seite ist bewusst ohne Anmeldung erreichbar (mit Mengenbegrenzung, M1-08). |
| Seite ohne Gerüst | `wiederherstellen.php` | `ui_seite_start` | **Der Rückweg zum Komplett-Backup**, wenn der Webspace weg ist (E-S2-20). Sie bringt ihre eigene Sitzung mit; `auth_guard.php` gibt es hier nicht, weil es die Installation noch nicht gibt, zu der man sich anmelden könnte. |
| Seite ohne Gerüst | `pw_handling.php` | `ui_seite_start` | **Passwort über einen Einmal-Link setzen.** Der Dateikopf sagt es ausdrücklich: „Die Seite hat keine Sitzung und zeigt den Standard der Installation" (E-P3-20). Sie ist die einzige Stelle, an der ein Passwort vergeben wird — vor jeder Anmeldung. |
| Seite ohne Gerüst | `rechtstext_seite.php` | `ui_seite_start` | **Öffentliche Rechtstexte** (R32). Impressum und Datenschutz müssen ohne Anmeldung erreichbar sein; der Dateikopf nennt genau das als Grund, `auth_guard.php` nicht einzubinden. |
| Seite ohne Gerüst | `session_lib.php` | `ui_seite_start` | **Keine Seite, sondern die Abmeldung.** `session_beenden()` ruft `session_destroy()` und gibt danach die Zwischenseite „Du wirst abgemeldet …" aus. Zum Zeitpunkt der Ausgabe gibt es die Sitzung nicht mehr — ein Gerüst wäre dort nicht nur falsch, es ließe sich nicht bauen. |
