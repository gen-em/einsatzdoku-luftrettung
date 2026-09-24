# Messstand

Was kosten **5000 Einsätze**? Der Bestand wird hergestellt, gemessen und
gegen die Zielzahlen aus E-S2-24 gehalten.
**Anlass: Nr. 37** — wie verhält sich ein Konto, das über Jahre wächst?

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

Eine laufende Installation **und ein Konto `messstand@gen-em.org`**. Das
Konto ist eine Zuarbeit der Betreiberin und liegt örtlich nicht vor (F-PK-22);
ohne es scheitert der Browserschritt nach der Anmeldung und wartet dann
180 s auf ein Element, das nie kommt.

## Erwartete Zahl

Die Zielzahlen stehen in `ausgangsmessung.md` daneben — **dort und nicht
hier**, weil sie mit jeder Messung fortgeschrieben werden.

## Was es nicht kann

Nichts über Produktiv sagen: Es misst die Maschine, auf der es läuft. Und
es ist **kein Prüfmittel für Richtigkeit** — es misst Kosten, nicht
Verhalten.
