# Messstand

Was kosten **5000 Einsätze**? Der Bestand wird hergestellt, gemessen und
gegen die Zielzahlen aus E-S2-24 gehalten.
**Anlass: Nr. 37** — wie verhält sich ein Konto, das über Jahre wächst?

## Aufruf

```bash
cd tools/messstand && python3 messen.py --frisch   # --schritte · --einsaetze
```

Sieben Schritte: `konto` `bestand` `einspielen` `browser` `server`
`statistik` `protokoll`. `--schritte server` fährt einen einzelnen.

## Was es misst

Antwortzeiten und Speicher der Serverwege, dieselben im Browser (Halde,
JSON, PBKDF2), und den `.edbak`-Umlauf bei voller Größe. `statistik`
(**Anlass: Nr. 295**): Betrieb → Statistik, drei Reiter unter 1 s — seit
R4-23 (Nr. 122) je einmal ohne und einmal mit eigenem Zeitraum über 90 Tage,
seit R4-24 samt der Abfrage der Diagramme —, und `EXPLAIN` der Abfragen aus `statistik_lib.php` — **rot**, wenn eines verfehlt. Ebenso **rot** (Nr. 37, seit R4-17): mehr als 200 Zeilen in der Zeitraumübersicht, 500 Tagesverweise ohne den Hinweis der Leiste. Seit S11-01 (**Anlass: Nr. 43**, E-S11-15) im Schritt `browser` drei weitere Wege: **Einsatz mit Spur öffnen** und **Diensttag mit Ruhe-Spuren öffnen** — je der ungünstigste Fall des Bestands (meiste Spur- bzw. Ruhe-Punkte, gewählt von `messen.py` über `spur_zahlen()`), je `--wiederholungen` Läufe (Vorgabe 5), gezählt der Median —, die **Zeitraumübersicht für einen Monat** und **GPX über einen Zeitraum** (Export Profil B mit GPX, ein Jahr, mit und ohne Passwort, in Abschnitte zerlegt); das Protokoll nennt den Prozessor. Das Backup ist der Schritt „Backup erstellen" daneben. Der Bestand
entsteht aus dem Referenzdatensatz und wird vervielfältigt — **nicht
erfunden**, damit die Verteilung der Daten stimmt.

## Was es braucht

Eine laufende Installation mit dem Prüfkonto (`admin@example.invalid`). Das Konto `messstand@example.invalid` legt der Schritt `konto` selbst an; **der Prüfstand ruft deshalb `messen.py --frisch` auf, nicht `browserprobe.mjs` allein** (seit P5c/AP4, F-P5c-104).
Ohne das Konto scheitert der Browserschritt nach der Anmeldung und wartet 180 s auf ein Element, das nie kommt — so geschehen im ersten Lauf der Hauptstufe: 1086 s, sieben Zeitgrenzen. Bis dahin stand hier, das Konto sei eine Zuarbeit der Betreiberin (F-PK-22); für die örtliche Anlage stimmte das nicht.

## Erwartete Zahl

Die Zielzahlen stehen in `ausgangsmessung.md` daneben — **dort und nicht hier**, weil sie mit jeder Messung fortgeschrieben werden.
**Gemessen 11.10.2026** (S11-01, Grundlinie vor S11): 5096 Einsätze eingespielt, zehn Browsermessungen ohne Fehler (die zwei Einzelansichten je fünfmal), Rückgabewert 0; der Schritt `browser` allein rund 7 min bei Drossel 6×, 2 min ungedrosselt.

## Was es nicht kann

Nichts über Produktiv sagen: Es misst die Maschine, auf der es läuft. Und
es ist **kein Prüfmittel für Richtigkeit** — es misst Kosten, nicht
Verhalten.
