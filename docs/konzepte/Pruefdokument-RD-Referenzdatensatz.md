# Prüfdokument RD — Referenzdatensatz und Demo: alle Spurfälle

Zum Konzept `Konzept-RD-Referenzdatensatz.md` (Schritt 24, Web 23.8.0 und
23.8.1). Was maschinell gemessen wurde, steht mit Mittel und Zahl im
Prüfprotokoll des Konzepts (Abschnitt 7) und im Prüfbericht der Commits.
Dieses Dokument sagt, was **noch zu tun** ist.

Alle Daten unten sind Daten des Demo-Kontos nach einem Reset; der Reset
verschiebt keine Daten.

## 1. Was nicht geprüft werden konnte, und warum

1. **Nichts auf Staging oder Produktiv.** Gemessen ist alles an der einen
   örtlichen Anlage (MariaDB, PHP der Sandbox). Ob der Reset mit Stufe 2/3
   auf Staging in der Zeitgrenze bleibt (örtlich 3,17 s) und ob das neue
   Schlüsselpaar dort ohne Mengenbremse entsteht (F-RD-25), zeigt erst die
   Prüfung nach dem Merge (R91, P-RD-01, -02 und -05).
2. **Kein Vergleichsbild eines Einsatzes der Stufe 3.** Der Bilderlauf nimmt
   als Einsatzansicht den ersten Einsatz des jüngsten Diensttags auf, und der
   liegt auf Stufe 1. Belegt ist nur die Zahl: `demo_pruefen.mjs` zählt im
   Stufe-3-Einsatz eine Linie (Konzept 7, RD-07). Ob die ausgedünnte Linie
   so aussieht wie die volle, bleibt Handprüfung (P-RD-02).
3. **Die 30-Minuten-Frist des Demo-Resets** nicht abgewartet — die Proben
   setzen die Marke und warten nicht. P-RD-05.
4. **Ein echter Safari** — WebKit ist nicht Safari (Grundsatz 8). Die neue
   Segmentzeile mit „⋯" (F-RD-24) ist nur in Chromium, Firefox und WebKit
   gemessen. P-RD-09.
5. **Produktiv mit dem alten Schlüsselpaar.** Auf Produktiv liegt das
   Demo-Paar unter dem Inhaltsschlüssel der alten Fixture. Der Wechsel ist
   örtlich dreimal gefahren (F-RD-25), auf Produktiv erst mit dem Tag.
   P-RD-10.

## 2. Grenzen der benutzten Prüfmittel

- **`fixture/abgleich.py`** vergleicht Zahlen, keine Punkte: Ein Punkt an
  falscher Stelle bei gleicher Zahl fällt ihm nicht auf. `n_original`
  vergleicht er nur bei Stufe 3, Stufe 1 und 2 unterscheidet er nicht
  (F-RD-27). In Stufe 1 hält er die Fixture gegen `umriss.json` und diese
  gegen die SHA-256 der `.edbak`; ob `umriss.json` zur `.edbak` passt,
  rechnet nur `--tief` im Prüfstand nach.
- **Die Kreisläufe** messen den Umlauf durch das Prüfkonto, nicht den
  Demo-Reset; der Reset ist über den Rundlauf Fixture → Reset → Fixture
  gemessen (RD-04, 0 Abweichungen).
- **`demo_pruefen.mjs`** zählt Linien und Punkte auf der Karte, es sieht
  nicht, ob eine Linie richtig liegt.
- **Die Bedienprobe** liest Sollfolgen aus der Fixture; ein Fehler, der
  Fixture und Anzeige gleich verbiegt, bleibt ihr verborgen.

## 3. Handprüfliste

Wer: **Claude** prüft auf Staging nach dem Merge mit dem eigenen Konto
bzw. dem Demo-Konto (R91); **Betreiberin** auf Produktiv nach dem Tag.

| Punkt | Wer | Bedienweg | Erwartet | Gescheitert, wenn |
|---|---|---|---|---|
| P-RD-01 | Claude | Staging: Verwaltung → Demo-Konto „Zurücksetzen", die Kacheln lesen | Reset in wenigen Sekunden; **21** Diensttage, **106** Einsätze, **118** Ruhesegmente, **2** Geräte | alte Zahlen (21 / 104 / 118) — die Fixture ist nicht mitgegangen; Zeitgrenze; Fehlermeldung |
| P-RD-02 | Claude | **P-S11-31 neu:** als Demo anmelden, Startseite eine Minute offen, dann Diensttag 08.02.2026 → Einsatz 07:34 (Stufe 3) öffnen | Karte mit Linie und Phasenmarkern; die Linie folgt dem Weg ohne Sprünge | Karte leer, „lassen sich nicht öffnen", Linie springt quer oder fehlt; Konsolenfehler 429 an `kontopaar` |
| P-RD-03 | Claude | **P-S11-30 neu:** Demo, 16.08.2026, Ruhesegment 14:20–16:40 (Übung, Stufe 2) → „Schneiden" 14:40–16:10 → Einsatz anlegen, dann „Schnitt zurücknehmen" | Einsatz mit Plakette „Schnitt" erscheint; nach der Rücknahme ist das Segment wieder ganz | „inzwischen zusammengefasst" beim Schneiden; Linie nach der Rücknahme fort |
| P-RD-04 | Claude | **P-S11-30 neu, Teil 2:** dasselbe Segment → „Schneiden" → Zeitraum 15:00–15:10 → „Zeitraum löschen" | Rückfrage, dann „Gelöscht — … Punkte"; die Linie hat dort eine Lücke | jede Meldung „zusammengefasst"; die ganze Linie ist danach fort |
| P-RD-05 | Claude | Nach P-RD-04 nichts mehr ändern; 31 Minuten nach der ersten Änderung (P-RD-03) die Seite neu laden | Der Bestand ist zurückgesetzt: der Schnitt aus P-RD-03 und die Lücke aus P-RD-04 sind fort | Lücke steht 35 Minuten nach P-RD-03 noch; oder sie ist schon vor Ablauf von 30 Minuten fort |
| P-RD-06 | Claude | Demo, 18.04.2026: den geschnittenen Einsatz 16:05–17:10 (Plakette „Schnitt") zurücknehmen | Meldung „… seitdem zusammengefasst worden …, über den Papierkorb löschen"; nichts ändert sich | Rücknahme gelingt (Punkte verloren, F-RD-22) |
| P-RD-07 | Claude | Demo, 14.06.2026: den geschnittenen Einsatz 16:10–17:15 zurücknehmen, danach dieselbe Stelle neu schneiden | Rücknahme gelingt (65 Punkte zurück ins Segment), neuer Schnitt ebenso | Meldung „altschnitt" oder „zusammengefasst" |
| P-RD-08 | Claude | Demo, Diensttag 19.06.2026 (Nachtdienst Luft): Tagesübersicht, Ruhesegment über Mitternacht aufklappen | Gesperrter Bereich 00:20–01:05 am Folgetag; Einsätze 19:09 · 22:20 · 00:20 · 01:40 | Bereich fehlt oder liegt am 19.06. um 00:20; andere Reihenfolge |
| P-RD-09 | Betreiberin | Safari (iPhone und Mac), Demo, Diensttag 19.06.2026: Ruhesegment über Mitternacht (mit Schnitt) aufklappen | Knopfreihe bzw. „⋯" mit Blatt, nichts läuft über den Rand | Zeile ragt hinaus, „⋯" öffnet nichts |
| P-RD-10 | Betreiberin | Produktiv nach dem Tag: Demo zurücksetzen, als Demo einen Einsatz mit Karte öffnen | Karte mit Linie, keine Fehlermeldung | „lassen sich nicht öffnen" — das alte Paar ist stehen geblieben (F-RD-25) |
| P-RD-11 | Claude | Demo, 14.06.2026: Segment 21:20–22:05 (ganz geschnitten) aufklappen | Segment zeigt den gesperrten Bereich über die ganze Länge; keine Fehlermeldung | Fehlermeldung, leere Karte mit Fehler |
| P-RD-12 | Claude | Demo, 07.03.2026: Ruhesegment 16:36–19:00 (Sequenzlücke, Stufe 2) und 08.02.2026, 15:58–17:00 (Zeitlücke) öffnen | Linie mit einer sichtbaren Unterbrechung, sonst durchgehend | Linie springt gerade über die Lücke ohne Unterbrechung **und** die Punktzahl ist kleiner als vorher; Fehlermeldung |
| P-RD-13 | Claude | Demo, 27.09.2026: Ruhesegment 14:44–15:18 öffnen | Linie mit Lücke 15:00–15:08 (Löschvermerk) | keine Lücke |
| P-RD-14 | Claude | Demo: Einsatz 07:34 am 08.02.2026 (Stufe 3) → Aktionsmenü → „GPS-Daten als GPX" | Datei mit Trackpunkten, Zeitraum 07:34–08:26 | Eintrag fehlt im Menü, leere Datei, Fehlermeldung |
