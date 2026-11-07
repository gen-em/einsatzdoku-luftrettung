# Barrierefreiheit

Kann ein Screenreader jede Seite lesen und die Tastatur jedes Element erreichen?
**Anlass: Nr. 524** — die Oberfläche war nie darauf gemessen; axe-core fand 11 verletzte Regeln, in drei Engines gleich.

## Aufruf

```bash
MOTOR=chromium node tools/barrierefreiheit/probe.mjs   # firefox, webkit ebenso
node tools/barrierefreiheit/probe.mjs --nur index.php  # nur Seiten, deren Adresse oder Name das enthält
```

## Was es misst

axe-core 4.14.0 (`vendor/axe.min.js`, MPL-2.0, Herkunft in `docs/Lizenzen.md` 7.0a) mit den Regeln WCAG 2.0 bis 2.2, A und AA, dazu „best-practice". 28 Seiten: 5 abgemeldet und 13 als Demo-Konto je bei 1440 und 390 px hell und 1440 px dunkel, 10 Verwaltungs- und Betriebsseiten als BetreiberIn bei 1440 px hell und dunkel. Tagesübersicht, Einsatz und Formular löst es aus dem Bestand auf. Vor dem Lauf rechnet es die SHA-256 der Bibliothek nach.

## Was es braucht

Die örtliche Anlage mit Demo-Konto und Prüfkonto `admin@example.invalid` (Zweitfaktor über `codeSchritt()`), Playwright. Der Wartungslauf wird wie in der Bedienprobe beantwortet, Kartenkacheln und Adresssuche gehen nicht hinaus.

## Erwartete Zahl

**0 Befunde** in jeder Engine, Rückgabewert 0. Gemessen 07.11.2026 (Web 23.11.2): Chromium 74 Aufrufe, 0 Befunde, 292 Knoten unentschieden, 197 s. Gegenprobe: „Aktionen" ohne `aria-label` → 1 Befund (`button-name`), Rückgabewert 1. Vor MA-14 waren es 11 Regeln an 409 Knoten (Chromium).

## Was es nicht kann

Es prüft nur, was eine Regel fassen kann — rund ein Drittel der WCAG-Kriterien. Ob eine Beschriftung **verständlich** ist, ob die Tabfolge Sinn ergibt, ob ein Dialog den Fokus richtig führt, sieht es nicht; das bleibt Handprüfung mit Tastatur und Screenreader. „Unentschieden" (meist Kontrast auf Bild, Verlauf oder durchscheinender Fläche) wird nur gezählt; die Paare rechnet `tools/screenshots/kontrast.py`. Geschlossene Dialoge, Blätter und aufgeklappte Zustände misst es nicht — nur die Seite, wie sie nach dem Laden steht.
