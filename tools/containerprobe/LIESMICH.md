# Containerprobe — hält Fassung 4 gegen drei unabhängige Umsetzungen

```
php -S 127.0.0.1:8080 -t server        # oder die übliche Testinstallation
node tools/containerprobe/probe.mjs [basisadresse]
```

Rückgabewert `0` = alle Erwartungen erfüllt, `1` = mindestens eine nicht.

## Wozu

Fassung 4 zerlegt die Sicherung in versiegelte Teile (`docs/Backup-Format.md`,
Konzept S2 3.2). Drei Dinge daran gehen schief, ohne dass es auffällt:

1. **Die Teile gehen nur dort wieder auf, wo sie geschrieben wurden.** Ein
   Werkzeug zum Handöffnen gibt es dann nicht mehr — und eine Sicherung, die
   nur die eine Anwendung lesen kann, ist genau in dem Fall wertlos, für den
   man sie aufbewahrt.
2. **Die Spur kommt verändert zurück.** Ein SPUR1-Blob ist Binärinhalt; eine
   um eine Stelle verschobene Zahl sieht darin aus wie jede andere.
3. **Die Bindung der Teile hält nicht.** Ein fremdes oder vertauschtes Teil
   entsiegelt dann klaglos — dasselbe Passwort genügt ja —, und der Bestand
   eines anderen Kontos landet hier.

## Drei Umsetzungen, eine Wahrheit

Dieselbe Linie wie bei der GPX-Probe (S2/AP4): Kein Weg darf seinen eigenen
Fehler bestätigen.

| Wer | Was |
|---|---|
| **PHP** | `server/spur_lib.php` kodiert echte SPUR1-Blobs (`spuren_bauen.php`) |
| **Browser** | `assets/crypto.js` + `assets/vendor/zipjs.min.js` versiegeln und packen — im **echten Chromium** über Playwright, nicht in einem Node-Nachbau |
| **Python** | `tools/referenzdatensatz/vergleich/lesen.py` öffnet, entsiegelt und dekodiert wieder |

**Warum Chromium und kein Nachbau.** `crypto.subtle`, `CompressionStream` und
das Verhalten von `String.fromCharCode.apply` bei großen Feldern sind
Eigenschaften der Laufzeit. Ein Shim in Node prüfte den Shim.

**Warum das Prüffutter aus PHP kommt.** Die Probe will belegen, dass eine Spur
durch Fassung 4 hindurch unverändert ankommt. Baute sie ihre Blobs selbst,
prüfte sie ihren eigenen Nachbau.

## Die Teile

| Teil | Frage |
|---|---|
| 1 | Versiegeln und Öffnen im Browser: eine PBKDF2 je Vorgang, Fassung 4 im Teilkopf, Base64 für 2 MB, Formaterkennung, ZIP ohne Kompression |
| 1 | **Die Bindung:** vertauscht · falsche Nummer · fremde Sicherung · verfälschtes Byte · falsches Passwort — jeder Fall muss auffallen, und die Meldung darf nicht nur „Passwort falsch" sagen |
| 2 | Dieselbe Datei in Python: Rundlauf, Prüfsummen, Punkt für Punkt gegen das, was PHP kodiert hat |
| 2 | **Die Schadensfälle am Archiv:** fehlendes Teil · vertauschte Teile · fremdes Teil · verfälschtes Teil · überzählige Datei · kein Manifest |

## Zwei Sicherungen, und jede trägt für sich

Ein verändertes Teil fällt zweimal auf, und das ist Absicht:

- an der **SHA-256** aus dem Manifest — sie schlägt zuerst zu und sagt, *welches*
  Teil nicht stimmt;
- an den **Zusatzdaten** (AAD) der Verschlüsselung — sie binden Sicherungs­kennung,
  Teilname und Nummer.

Die Probe zeigt beide **einzeln**: Für die AAD zieht sie das Manifest passend
nach, sodass die Prüfsummen stimmen — dann bleibt nur die Bindung. Ohne diesen
Fall hinge in Wahrheit alles an einer Prüfsumme, die jeder mitschreiben kann,
der das Passwort hat.

## Was sie nicht prüft

- **Den Weg durch die Anwendung.** Sie baut die Datei selbst; ob
  `einstellungen.php` sie genauso baut, prüfen der Kreislauf `edbak` und die
  Wiederherstellungsprobe.
- **Große Dateien.** Sie fährt sechs Spuren zu 300 Punkten. Die Mengen misst
  der Messstand.
- **Andere Browser als Chromium.** WebKit und Gecko stehen in dieser Umgebung
  nicht zur Verfügung.
- **Den Ratenschutz und die Rechte.** Sie fasst keinen Endpunkt an.

## Voraussetzungen

Eine laufende Installation (der Entwicklungsserver genügt), Playwright mit
Chromium, Python mit `cryptography`, PHP mit `zlib`.
