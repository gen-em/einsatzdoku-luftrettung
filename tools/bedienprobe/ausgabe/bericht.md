# Klickprobe — Bericht

Lauf gegen `https://127.0.0.1:8443` — 1280 px als Zeigergerät (Sollhöhe 36 px).
**1 von 1 Wegen erfüllt**, 0 verfehlt.

| Weg | Paket | Prüfpunkt | Breite | Soll | Ist | |
|---|---|---|--:|---|---|---|
| `p5c-ap8-adhoc-tag-in-der-luft` | P5c/AP8 | Nr. 169, E-P5c-47 | 1280 px (36) | Dreimal p1, p2, hems, fr, other: in der Vorschau, nach dem Neuladen und im Einsatzformular | **Vorschau p1, p2, hems, fr, other · nach dem Neuladen („adhoc") p1, p2, hems, fr, other · Einsatzformular p1, p2, hems, fr, other** | erfüllt |

## Grenzen dieses Laufs

- Nur Chromium; WebKit und Gecko stehen im Prüfstand nicht zur Verfügung.
- Die Adressabfrage lief gegen die Attrappe (`attrappe.mjs`), nicht gegen
  den echten Dienst — der Prüfstand hat dorthin keinen Netzzugang.
- Gemessen wurde bei **1280 px** als **Zeigergerät** — nicht mit Handschuhen und
  nicht an einem echten Gerät.
