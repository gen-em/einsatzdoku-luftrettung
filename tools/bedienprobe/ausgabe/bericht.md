# Klickprobe — Bericht

Lauf gegen `https://127.0.0.1:8443` — 1280 px als Zeigergerät (Sollhöhe 36 px).
**4 von 4 Wegen erfüllt**, 0 verfehlt.

| Weg | Paket | Prüfpunkt | Breite | Soll | Ist | |
|---|---|---|--:|---|---|---|
| `ka06b2-standbuch` | KA-06 | F-KA-04 | 1280 px (36) | 16 Blätter und Buch; Blatt abgelegt und gleich zurück; veraltete Version → Konflikt mit dem Blatt des Servers (auch Buch); 400 für Text außerhalb der Klasse, nicht steigende Version, wer 16 | **Stand in Form, Blatt gleich zurück, veraltete Version Konflikt mit Serverblatt, Buch wie Blatt, Formfehler 400:text 400:version 400:wer** | erfüllt |
| `ka06b2-fremdkonto` | KA-06 | F-KA-04 | 1280 px (36) | das Blatt des Prüfkontos ist im Demo-Konto nicht zu sehen | **Prüfkonto hat ein Blatt, Demo-Konto sieht es nicht** | erfüllt |
| `ka06b3-patient-weiche-an` | KA-06 | F-KA-04 | 1280 px (36) | mit der Weiche an: edk2: gespeichert, Blatt gestiegen, Block geht auf; dieselbe Anfrage noch einmal → 409, Block unverändert | **gespeichert edk2:, Blätter 16 → 17, Feld blaetter mitgeschickt, Block geht auf, Wiederholung HTTP 409, Block danach unverändert** | erfüllt |
| `ka06b4-anhebelauf` | KA-06 | F-KA-04 | 1280 px (36) | Weiche aus: nichts zieht um; Weiche an: kein Altbestand mehr, kp/rw edk2:, 16 Blätter zu; zweiter Lauf ändert nichts; falsches blob_alt wird übersprungen | **Weiche aus: Altbestand 104 → gleich; Weiche an: Altbestand 0, kp neu, rw neu, 16 von 16 Blättern zu; zweiter Lauf ohne Änderung; falsches blob_alt: angehoben 0, Block unverändert** | erfüllt |

## Achtung: der Demo-Reset lief WÄHREND dieses Laufs

Das Demo-Konto hat sich um **21:25:52 UTC** zurückgesetzt (`DEMO_RESET_SEKUNDEN` = 1800, `demo_lib.php`). Dabei verschwindet der Bestand kurz und die Sitzungs-Epoche steigt.

**Jeder verfehlte Weg oben ist damit zunächst verdächtig**, und zwar besonders die mit „Bestand leer?", „Kein Diensttag im Bestand" oder einer Zeitgrenze. Die Sitzungswache fängt nur die verlorene Sitzung ab, nicht den fehlenden Bestand. **Vor dem Melden einer Zahl den Lauf wiederholen** — er dauert weniger als das 30-Minuten-Fenster.

## Grenzen dieses Laufs

- Gefahren in chromium; die anderen beiden Engines haben je einen eigenen Lauf
  und Bericht (`ausgabe-firefox/`, `ausgabe-webkit/`). Ein echter Safari ist es nicht.
- Die Adressabfrage lief gegen die Attrappe (`attrappe.mjs`), nicht gegen
  den echten Dienst — der Prüfstand hat dorthin keinen Netzzugang.
- Gemessen wurde bei **1280 px** als **Zeigergerät** — nicht mit Handschuhen und
  nicht an einem echten Gerät.
