# Messstand — 5000 Einsätze, und was sie kosten

Entstanden in S2 (Konzept, E-S2-23; Regressionspflicht R35). Er bleibt
dauerhaft im Repositorium: Die Zielzahlen aus E-S2-24 werden nicht einmal
abgenommen und dann vergessen, sondern nach jeder Änderung an Spuren,
Sicherung oder Suche erneut gemessen.

## Warum es ihn gibt

S2 verspricht, dass ein Konto mit **5000 Einsätzen** trägt — Suche, Ansichten,
Sicherung und Wiederherstellung, auf einem fünf Jahre alten Gerät (Z1/Z3). Ein
solches Versprechen lässt sich nicht durch Nachdenken einlösen. Es braucht
einen Bestand dieser Größe, und den muss jemand **herstellen** können:
reproduzierbar, ohne Handarbeit, auf jeder Entwicklungsmaschine.

Und es braucht einen **Ausgangswert**. „Die Sicherung ist jetzt schneller" ist
keine Aussage, solange niemand weiß, wie langsam sie vorher war und wo genau
sie aufgehört hat zu funktionieren. Der Messstand hält den heutigen Stand fest,
**einschließlich der Stelle, an der er bricht** — das ist die eigentliche
Auskunft, denn B-S2-03 sagt zwar, dass der Sicherungsweg lange vor 5000
Einsätzen bricht, aber nicht, wo.

**Der Bestand entsteht über die regulären Wege.** Der Vervielfältiger baut
`.edbak`-Dateien; eingespielt werden sie im Browser über
`einstellungen.php?t=backup`, so wie eine NutzerIn es täte. Kein SQL, kein
Sonderendpunkt (Geist von R4). Das kostet Zeit — und es ist der Punkt: Der
Einspielweg ist selbst einer der Prüflinge.

## Voraussetzung

Eine laufende lokale Installation mit dem Referenzdatensatz:

```
sh tools/referenzdatensatz/einspielen/lokal_starten.sh
```

Wie sie entsteht, steht in `tools/referenzdatensatz/LIESMICH.md`. Der
Messstand braucht daraus genau eines: die **Referenz-`.edbak`** unter
`tools/referenzdatensatz/referenz/`. Sie ist die Vorlage, aus der der
Großbestand entsteht.

Dazu Python mit `cryptography` (für den Container) und Playwright (für den
Browser) — beides wie im Referenzdatensatz.

## Aufruf

```
cd tools/messstand
python3 messen.py --frisch                    # alles, von vorn
python3 messen.py --schritte server           # nur die Serverprobe
python3 messen.py --einsaetze 1000            # kleinerer Bestand
```

Die sechs Schritte einzeln:

| Schritt | Was er tut |
|---|---|
| `konto` | legt `messstand@gen-em.org` über den Einladungsweg an (`--frisch` löscht ein vorhandenes vorher) |
| `bestand` | `vervielfaeltigen.py` — aus der Referenz eine Folge `.edbak`-Dateien |
| `einspielen` | `einspielen.mjs` — über den regulären Weg im Browser, mit Zeit- und Haldenmessung je Datei |
| `browser` | `browserprobe.mjs` — Suche, Tagesansicht, Sichern unter CPU-Drossel |
| `server` | `serverprobe.py` — Tabellengrößen, `edbak_build()` auf **beiden** Wegen, Speicherspitze, Waisen-Vollscan |
| `protokoll` | fasst alles zu `messprotokoll.json` zusammen |

Die Ausgabe liegt unter `/tmp/messstand` (`--ausgabe` ändert das); das
festgehaltene Ergebnis des heutigen Stands steht daneben in
`ausgangsmessung.md`.

### Warum die Serverprobe `edbak_build()` zweimal misst (seit S2/AP5b)

Es gibt seit Web 11.1.0 zwei Wege, und sie liegen um zwei Größenordnungen
auseinander:

| Weg | wer geht ihn | gemessen am Messstand |
|---|---|---|
| am Stück, **mit** Punktlisten | die Admin-Sicherungen (noch; AP6) | 6,95 s · 94,28 MB · **1077,6 MB** Spitze |
| Kopf + Fenster zu 250 | die Sicherung der NutzerIn | 1,12 s · größtes Fenster 0,44 MB · **10,0 MB** Spitze |

Stünde nur die erste Zeile da, läse sich das Protokoll so, als brauche jede
Sicherung ein Gigabyte. Das stimmt für die Admin-Sicherung und ist dort die
Auskunft — für die Nutzerin stimmt es seit AP5b nicht mehr. **Eine Zahl, die
nicht dazusagt, welchen Weg sie gemessen hat, ist keine.**

Die zweite Messung läuft in einem **eigenen PHP-Prozess** und mit
`memory_limit=64M`. Beides mit Grund: `memory_get_peak_usage()` kennt nur ein
Maximum je Prozess — im selben Lauf gemessen käme für die Fenster die Spitze
des Baus am Stück heraus. Und der Deckel macht aus „so viel wurde gebraucht"
ein „es reicht": Bricht der Fensterweg eines Tages ab, steht das im
Protokoll, statt in einer Zahl unterzugehen.

## Der Riegel

**Dieses Werkzeug füllt ein Konto mit tausenden Einsätzen.** In einem
Prüfkonto ist das der Zweck; in einem echten Konto wäre es ein Schaden, den
niemand mehr von Hand aufräumt, und auf der Referenzinstallation wäre der
Referenzstand hin. Genau das ist dort schon einmal passiert — die Lehre steht
in `tools/referenzdatensatz/browser/demo_pruefen.mjs`.

Der Riegel schließt deshalb **nach innen**: Wer nicht positiv feststellen
kann, dass hier nichts kaputtgeht, bricht ab.

- `einspielen.mjs` füllt nur ein Konto, dessen Adresse mit `messstand@` oder
  `messstand+` beginnt. `demo@gen-em.org` ist ausdrücklich ausgeschlossen,
  auch wenn jemand es über die Umgebung setzt.
- Eine Installation, die nicht auf dieser Maschine läuft, verlangt ein
  ausdrückliches `MESSSTAND_FREMDE_INSTALLATION=ja`. Damit steht die
  Entscheidung im Aufruf und nicht in einem Vorgabewert.
- Gelöscht wird über `kreislauf.konto_loeschen()` mit dem Präfix `messstand` —
  derselbe geprüfte Weg, den der Kreislauf benutzt, nur mit eigenem Riegel.

## Was der Messstand **nicht** tut

- **Kein SQL.** Weder zum Anlegen des Bestands noch zum Aufräumen. Ein per SQL
  erzeugter Bestand beantwortete die Frage nicht, die hier gestellt wird: Er
  käme nie durch den Einspielweg, und der ist der interessantere Prüfling.
- **Keinen eigenen Weg zum Anlegen oder Löschen eines Kontos.** Beides
  erledigen die geprüften Bausteine des Referenzdatensatzes.
- **Keine echte Hardware.** Siehe unten.

## Wie der Bestand entsteht

Die Referenz (87 Einsätze, 100 Ruhesegmente, 55 861 Spurpunkte, 16 Diensttage
über 345 Tage) wird **r-mal** kopiert. Jede Runde verschiebt alle Zeitangaben
um `-runde × 3` Tage **und** um `+runde` Minuten:

- Die **Tage** lassen den Bestand in die Vergangenheit wachsen. Das ist kein
  Schönheitsgrund: Die Ausdünnung (E-S2-03) greift sechs Monate nach
  Einsatzende, und ein Bestand, der nur in der Zukunft liegt, gäbe AP3 nichts
  zu tun.
- Die **Minute** verhindert, dass zwei Runden einander auffressen. Die
  Wiedererkennung des Einspielwegs (`backup_lib.php`) erkennt einen Diensttag
  in Schritt 2 an einem Fingerabdruck aus Datum, Beginn, Ende, Art,
  Rettungsmittel und Station. Ohne den Minutenversatz verschmölzen zwei Runden
  auf demselben Datum zu einem Tag — aus 5000 Einsätzen würden stillschweigend
  weniger.

Kennungen (`client_ref`, `day_ref`) bekommen je Runde den Zusatz `-vNNN`;
Stammdaten und Kontoangaben werden **nicht** vervielfältigt (ein Konto mit
5000 Einsätzen hat nicht 5000 Standorte). Die Koordinaten bleiben, wo sie
sind — ein über die halbe Alpenkette verstreuter Bestand misst nichts Besseres
und ließe die Ausdünnung an einer Geometrie rechnen, die es nicht gibt.

**Wie viele Einsätze in eine Datei passen, entscheidet `post_max_size`.** Die
Nutzlast wiegt rund **28 KB je Einsatz** (gemessen: 2,42 MB für 87 Einsätze
mit 55 861 Spurpunkten). Bei den verbreiteten 8 MB sind das rund 280 Einsätze
— die im Konzept genannten 400–500 gehen sich dort **nicht** aus. Der
Vorgabewert von drei Runden je Datei (261 Einsätze) richtet sich danach;
`--runden-je-datei` hebt ihn an, wenn der Server mehr zulässt.

## Grenzen dieses Prüfmittels

**Das Referenzgerät ist nachgestellt, nicht vorhanden.** Die CPU-Drossel
(`Emulation.setCPUThrottlingRate`, Faktor 6) verlangsamt die Rechenzeit — und
nur die. Speicher, Grafik, Leitung und der langsamere Flash eines fünf Jahre
alten Handys bleiben unberührt. Ein Wert knapp unter der Zielzahl ist deshalb
**kein** Beleg; er gehört an echter Hardware nachgeprüft, und das steht so im
Prüfdokument.

**Die Spuren sind synthetisch und glatt.** Der Referenzdatensatz ist erfunden;
echte Uhrspuren rauschen. Für die Blobgröße und die Ausdünnung heißt das:
Beide Zahlen fallen an echten Gerätespuren **schlechter** aus, weil die
Ausdünnung dort mehr Punkte behält. Der Vorbehalt steht schon im Befund
(Konzept 1) und gilt hier weiter.

**Die Halde wird abgetastet, nicht integriert.** `browserprobe.mjs` liest
`JSHeapUsedSize` alle 500 ms. Eine kurze Spitze dazwischen entgeht ihr. Die
gemessene Zahl ist damit eine **Untergrenze** der tatsächlichen Spitze — was
darüber liegt, ist schlimmer als gemessen, nie besser.

**Ein Konto, viele Konten.** Gemessen wird ein Konto mit 5000 Einsätzen (Z1).
Das Zielmaß Z2 (500 Konten × 600 Einsätze je Installation) misst der Messstand
**nicht**; dafür bräuchte es 300 000 Einsätze und einen Tag Rechenzeit. Was zu
Z2 gesagt werden kann, ist hochgerechnet — und als solches gekennzeichnet.
