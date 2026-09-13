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
