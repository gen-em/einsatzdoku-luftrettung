# Jobregister — passt `docs/Technik.md` noch zum Jobkatalog?

Hält die Tabelle „Der Katalog" in `docs/Technik.md` 4.97a gegen
`server/jobs_lib.php`: **Jobnamen**, **Zahl der Aufräumschritte** und
**Namen der Aufräumschritte**. Ohne Installation, ohne Datenbank, in
Millisekunden — der Lauf hängt deshalb in Stufe 1 der Kette.

```
php tools/jobregister/pruefen.php
php tools/jobregister/pruefen.php --selbstprobe
```

Rückgabewert `0` = Register und Code stimmen überein · `1` = Befund ·
`2` = eine der beiden Dateien fehlt oder ist unlesbar.

## Warum es das gibt

**Backlog Nr. 208.** Der Jobkatalog in `jobs_lib.php` ist die Quelle; das
Register in der Dokumentation war eine von Hand geführte Aufzählung daneben.
Sie ist dreimal hinterhergehinkt:

| Wann | Was stand da | Was war im Code |
|---|---|---|
| P5a/AP5 | sechs Aufräumschritte | zwölf |
| P5a/AP11 | vier von acht Jobs fehlten | acht |
| 20.09.2026 | 9 Jobs, „dreizehn Schritte" | **11 Jobs, 16 Schritte** |

Zweimal sind die Zahlen von Hand berichtigt worden, zweimal wuchs der Abstand
wieder. Nr. 208 verlangt deshalb ausdrücklich ein Prüfmittel — *„sonst wandert
das Problem nur eine Ebene weiter."*

Die zweite Hälfte der Abhilfe steckt im Code und nicht hier: Die **sichtbare**
Beschreibung unter Betrieb → Hintergrundjobs wird seit Web 20.26.0 aus
`array_keys(job_aufraeumen_schritte())` **erzeugt**. Sie kann nicht mehr
altern, und deshalb prüft dieses Werkzeug sie auch nicht.

## Wie gemessen wird

**Mit dem Tokenizer, nicht mit `grep` — und nicht durch Ausführen.**

`jobs_lib.php` lädt in Zeile 37 `db.php` und damit `config.php`. In Stufe 1
der Kette gibt es keine Installation; ein Werkzeug, das dort nicht laufen
kann, läuft nirgends. `token_get_all()` sieht dagegen genau das, was der
Übersetzer sieht — ein `grep` über `'name' =>` träfe jeden Kommentar und jede
gleichnamige Zeichenkette.

Gelesen werden zwei Array-Literale: `$katalog = [ … ]` in `jobs_katalog()`
und `return [ … ]` in `job_aufraeumen_schritte()`. Genommen wird nur, was auf
**Klammertiefe 1** steht und ein `=>` hinter sich hat — sonst zählte
`'titel' => 'Aufräumen'` eine Ebene tiefer mit.

Aus der Dokumentation kommen die Jobnamen aus der ersten Spalte jeder
Tabellenzeile unter `#### Der Katalog` und das **Zahlwort** aus der
`aufraeumen`-Zeile (`**siebzehn Schritte**`). Ein Zahlwort und keine Ziffer,
weil die Prosa dieses Projekts so schreibt; die Tabelle `JR_ZAHLWORT` deckt
1 bis 25 ab.

## Was es NICHT sieht

- **Ob ein Job tut, was danebensteht.** Das ist eine Frage an
  `tools/jobprobe/`.
- **Einen Job, der nicht als Zeichenkettenschlüssel im Katalogliteral steht**,
  sondern zur Laufzeit hineingerechnet wird. Es gibt heute keinen, und der
  Kopf von `jobs_katalog()` sagt, dass es keinen geben soll.
- **Die Prosa der Registerzeile** jenseits der Namen und der Zahl. Dass dort
  „als einziger Schritt mit ZWEI Fristen" steht, prüft niemand — und das ist
  richtig so: Diese Sätze sind der Grund, warum das Register nicht selbst
  erzeugt wird.

## Selbstprobe

Neun Fälle gegen den Leser: verschachtelte Literale, Werte ohne Pfeil,
tiefere Ebenen, ein Literal in einem Kommentar, eine unbekannte Funktion und
drei Zahlwörter. Erwartet werden **9 von 9**.
