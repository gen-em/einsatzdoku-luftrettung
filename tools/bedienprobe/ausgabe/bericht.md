# Klickprobe — Bericht

Lauf gegen `https://127.0.0.1:8443` — 1280 px als Zeigergerät (Sollhöhe 36 px).
**1 von 1 Wegen erfüllt**, 0 verfehlt.

| Weg | Paket | Prüfpunkt | Breite | Soll | Ist | |
|---|---|---|--:|---|---|---|
| `registrieren` | SR-08 | Nr. 228 | 1280 px (36) | Lösung ungedrosselt im Median unter 1 s; abgeschickt → Danke, Konto unbestaetigt; schneller als der Worker → Knopf gesperrt, „Sicherheitsprüfung läuft …", dann von selbst ab; keine Konsolenfehler | **Lösung nach Median 516 ms, höchstens 1199 ms (9 Läufe: 328, 196, 516, 1199, 332, 632, 285, 746, 633); abgeschickt → Danke, Konto unbestaetigt; zu schnell → „Sicherheitsprüfung läuft …", Knopf gesperrt, danach Konto unbestaetigt; 0 Konsolenfehler** | erfüllt |

## Achtung: der Demo-Reset lief WÄHREND dieses Laufs

Das Demo-Konto hat sich um **05:28:10 UTC** zurückgesetzt (`DEMO_RESET_SEKUNDEN` = 1800, `demo_lib.php`). Dabei verschwindet der Bestand kurz und die Sitzungs-Epoche steigt.

**Jeder verfehlte Weg oben ist damit zunächst verdächtig**, und zwar besonders die mit „Bestand leer?", „Kein Diensttag im Bestand" oder einer Zeitgrenze. Die Sitzungswache fängt nur die verlorene Sitzung ab, nicht den fehlenden Bestand. **Vor dem Melden einer Zahl den Lauf wiederholen** — er dauert weniger als das 30-Minuten-Fenster.

## Grenzen dieses Laufs

- Nur Chromium; WebKit und Gecko stehen im Prüfstand nicht zur Verfügung.
- Die Adressabfrage lief gegen die Attrappe (`attrappe.mjs`), nicht gegen
  den echten Dienst — der Prüfstand hat dorthin keinen Netzzugang.
- Gemessen wurde bei **1280 px** als **Zeigergerät** — nicht mit Handschuhen und
  nicht an einem echten Gerät.
