# Messstand

Was kosten **5000 Einsätze**? Der Bestand wird hergestellt, gemessen und
gegen die Zielzahlen aus E-S2-24 gehalten. **Anlass: F-S2-E** — die Zahlen
aus S2 werden nicht einmal geglaubt, sondern nachgerechnet.

## Aufruf

```bash
cd tools/messstand && python3 messen.py --frisch   # --schritte · --einsaetze
```

Sechs Schritte: `konto` `einspielen` `vervielfaeltigen` `server` `browser`
`edbak`. `--schritte server` fährt einen einzelnen.

## Was es misst

Antwortzeiten und Speicher der Serverwege, dieselben im Browser (Halde,
JSON, PBKDF2), und den `.edbak`-Umlauf bei voller Größe. Der Bestand
entsteht aus dem Referenzdatensatz und wird vervielfältigt — **nicht
erfunden**, damit die Verteilung der Daten stimmt.

## Was es braucht

Eine laufende Installation mit dem Prüfkonto (`admin@gen-em.org`). Das
Konto `messstand@gen-em.org` legt der Schritt `konto` selbst an; **der
Prüfstand ruft deshalb `messen.py --frisch` auf, nicht `browserprobe.mjs`
allein** (seit P5c/AP4, F-P5c-104). Ohne das Konto scheitert der
Browserschritt nach der Anmeldung und wartet dann 180 s auf ein Element,
das nie kommt — so geschehen im ersten Lauf der Hauptstufe: 1086 s, sieben
Zeitgrenzen. Bis dahin stand hier, das Konto sei eine Zuarbeit der
Betreiberin (F-PK-22); für die örtliche Anlage stimmte das nicht.

**Gemessen 24.09.2026** (frische Anlage, `messen.py --frisch`): 9 min 38 s,
5050 Einsätze eingespielt, sieben Browsermessungen ohne Fehler,
Rückgabewert 0.

## Erwartete Zahl

Die Zielzahlen stehen in `ausgangsmessung.md` daneben — **dort und nicht
hier**, weil sie mit jeder Messung fortgeschrieben werden.

## Was es nicht kann

Nichts über Produktiv sagen: Es misst die Maschine, auf der es läuft. Und
es ist **kein Prüfmittel für Richtigkeit** — es misst Kosten, nicht
Verhalten.
