# Konzept RP — Die roten Proben der Nebenstufe

**Kürzel:** `RP`. Arbeitspakete `RP-01 …`, Entscheidungen `E-RP-01 …`,
Befunde `F-RP-01 …`, Fragen an die Betreiberin `Q-RP-01 …`, Prüfpunkte
`P-RP-01 …`. Commit-Nachrichten beginnen mit dem Paket (`RP-02: …`).
**Herkunft:** Konzept PK, E-PK-45 (23.09.2026) — ein Korrekturpaket vor P5c
AP1; Zuschnitt vom Auftraggeber bestätigt. Befund F-PK-40, Backlog Nr. 292,
Messprotokoll: `Pruefdokument-PK-Pruefkette.md` 5o.
**Modell:** Opus. **Kein Versionssprung im Konzept** — die Umsetzung vergibt
ihn nach `CLAUDE.md` 2, falls ein Paket `server/` berührt.
**Ablage:** dieses Konzept; Prüfdokument `Pruefdokument-RP-Rote-Proben.md`
entsteht mit RP-01. Zweig `claude/rp-rote-proben`, von `main` `b329ac3`.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **23.09.2026 — RP-01 erledigt.** Versand, Freigabe, Wegprobe grün; GPX findet ihre Referenz; die Kreisläufe laufen wiederholbar; der Prüfstand schreibt die Zeit je Probe. |
> | Entschieden | E-PK-45 (Paket vor P5c, keine Ausnahmeliste), E-PK-46 (nicht gemessen ist rot) — aus Konzept PK. **E-RP-01 bis -04** vom Auftraggeber am 23.09.2026 (Abschnitt 4) |
> | Offen | nichts |
> | Nächstes | **RP-02** — veraltete Erwartungen und die csv-Referenz (E-RP-01) |

---

## 1. Auftrag

Die Nebenstufe des Prüfstands fährt 36 Proben gegen die örtliche Anlage;
am 23.09.2026 waren **9 rot, 1 266 s** (Ziel 15 min). Seit PK-05 macht jede
rote oder nicht gemessene Probe das Tor rot (Lage 5). Der erste Nebensprung —
P5c AP1 — käme so nicht durch. **Ziel:** eine Nebenstufe auf frischer Anlage
mit **0 rot, 0 nicht gemessen**, und eine gemessene Dauer mit Begründung.

**Nicht Ziel:** neue Proben, andere Stufenzuschnitte, eine Ausnahmeliste für
Lage 5 (E-PK-45).

## 2. Befund (gelesen am 23.09.2026, nur Protokoll und Quelltext)

**Acht der neun sind Fehler in `tools/`, nicht in der Anwendung.** Bis PK-05
lief keine davon in einer Stufe, die ein Tor las; deshalb fiel keiner auf.

| Nr. | Probe | Was rot ist | Ursache (gelesen) | Ort |
|---|---|---|---|---|
| F-RP-01 | `gpxprobe` (1 von 3) | „kein *csv*.zip unter tools/referenzdatensatz/referenz/" | Pfad `dirname(__DIR__)` zeigt seit dem Umzug nach `tools/proben/gpx/` (PK-04/2) auf `tools/proben/` — die Datei liegt unter `tools/referenzdatensatz/referenz/` | `tools/` |
| F-RP-02 | `gpxprobe` (2 von 3) | „3 Spuren" und „keine Spur" nicht gefunden | Die Anwendung sagt „3 Aufzeichnungen" (`gpx_lib.php` 245) und „keine GPS-Daten" (`tag_spuren.php` 188, seit E-S9-03 „GPS-Daten" statt „Spur") — Erwartung veraltet | `tools/` |
| F-RP-03 | `wartungsprobe` | „Die Wartungsseite nennt den Grund" | Die Anwendung schreibt „Die BetreiberIn ist informiert" (Hausform, PK-04/5b), die Probe sucht „Betreiberin" | `tools/` |
| F-RP-04 | `mailprobe` | „Pflichtwerte im Beispielsatz": `termin`, `einsaetze`, `speicher` | Der Beispielsatz steht in der Probe (`probe.php` 155) und kennt die Pflichtwerte der P5b-Mails nicht | `tools/` |
| F-RP-05 | `browserprobe-csp` | „frame-ancestors none" | Die Anwendung gibt `frame-ancestors` nur in der **scharfen** CSP aus (Nr. 224, `kopfzeilen_lib.php` 325); die Probe prüft es an der Report-Only-Fassung | `tools/` |
| F-RP-06 | `kreislauf-csv` | 3 unerklärte Abweichungen | Die Referenz vom 15.09.2026 ist älter als die Hausform: „Anderer Notarzt" → „Andere NotärztIn" in zwei Feldbeschreibungen und im LIESMICH der Ausfuhr | `tools/` (Referenz) |
| F-RP-07 | `freigabeprobe` | „Zielkonto nicht gefunden" | Sie arbeitet am Konto `umlauf-edbak@…`, das erst der Kreislauf `edbak` anlegt — und der läuft in der Liste **nach** ihr | `tools/` (Reihenfolge) |
| F-RP-08 | `spaltenregister-wegprobe` | Umgebungswert fehlt | Braucht `WEGWERFKONTO`/`WEGWERFPASSWORT`; niemand setzt sie. Ihr eigener Kopf sagt, die Umlaufkonten des Kreislaufs seien dafür gemacht | `tools/` (Verdrahtung) |
| F-RP-09 | `versandprobe` | „Aufruf: php probe.php <wurzel>" | Braucht laufende Gegenstellen; `gegenstellen.py` stellt FTP, FTPS und SFTP als Nachbau **ohne root** hin, der Prüfstand startet sie nicht | `tools/` (Verdrahtung) |
| F-RP-11 | Kreisläufe im Prüfstand | (gefunden in RP-01) | Der Prüfstand rief beide Kreisläufe **ohne `--frisch`** auf; auf einer Anlage, die nicht frisch ist, bricht der zweite Lauf ab, weil das Umlaufkonto schon besteht | `tools/` (Verdrahtung) |
| F-RP-12 | `gpxprobe` (hinter F-RP-01) | „190 von 204 ohne Gegenstück", „0 von 204 verglichen" | Erst mit dem richtigen Pfad erreicht: Die Referenz vom 15.09. ist älter als der Demo-Bestand — dieselbe Ursache wie F-RP-06 | `tools/` (Referenz, E-RP-01) |
| F-RP-10 | `wiederherstellung` | „Ein knapper Schub sichert wenigstens ein Konto und hört dann auf" (2 erledigt, 0 offen) und „Der Zeiger steht auf dem zuletzt gesicherten Konto" (`cur=—`) | **ungeklärt** — seit PK-03 bekannt (F-PK-18). Entweder ist der „knappe Schub" auf dieser Anlage nicht knapp (Erwartung) oder der Job hört nicht auf (Anwendung) | offen |

**Dauer:** Der Prüfstand schreibt keine Zeit je Probe. Welche Probe die
1 266 s trägt, ist nicht gemessen.

## 3. Arbeitspakete

| Paket | Was | Berührt | Abnahme |
|---|---|---|---|
| **RP-01 Verdrahtung** | F-RP-01, -07, -08, -09, -11: Pfad der GPX-Probe; eigenes Konto der Freigabeprobe (E-RP-02); Wegprobe am Umlaufkonto des Kreislaufs **`edbak`** (im Kurzkonzept stand `csv` — falsch, siehe unten); Gegenstellen-Nachbau starten und stoppen; `--frisch` für die Kreisläufe. Dazu **die Zeit je Probe** in die Laufausgabe | `tools/pruefstand/`, `tools/proben/` | die vier Proben laufen im Prüfstand; keine davon „nicht gemessen" |
| **RP-02 Veraltete Erwartungen** | F-RP-02 bis -06: Texte der GPX- und Wartungsprobe, Beispielsatz der Mailprobe, `frame-ancestors` in der scharfen Fassung prüfen; die csv-Referenz (Q-RP-01) | `tools/proben/`, `tools/referenzdatensatz/` | die fünf Proben grün, jede Berichtigung mit Fundstelle in der Anwendung belegt |
| **RP-03 Wiederherstellung** | F-RP-10 klären: Anwendung oder Erwartung. **Ist es die Anwendung, ist es eine Korrekturstufe Web** — vorher Rückmeldung an die Betreiberin | offen; ggf. `server/` | Ursache mit Beleg; Probe grün |
| **RP-04 Dauer** | Aus der Zeit je Probe (RP-01) die großen Posten benennen; was ohne Verlust schneller geht, umsetzen; sonst die Zahl mit Grund stehen lassen | `tools/` | gemessene Dauer der Nebenstufe mit Aufschlüsselung; Ziel 15 min oder Begründung |
| **RP-05 Abschluss** | Nebenstufe auf frischer Anlage, Dokumente (`Pruefablauf.md` 3, `LIESMICH` der Proben, CHANGELOG, Backlog Nr. 292 nach Erledigt), Prüfdokument, PR | — | **36 Proben, 0 rot, 0 nicht gemessen**; Stufe 1 auf dem PR grün |

**Stand RP-01 (23.09.2026, erledigt; Prüfdokument 2):**

| Teil | Ergebnis |
|---|---|
| `versandprobe` | `proben.sh versand` startet ohne Pfad `gegenstellen.py` in eigener Prozessgruppe und hält es danach an; **135 Erwartungen, 0 nicht erfüllt, 9 s**. Dafür brauchte die Arbeitsumgebung `pyftpdlib`, `paramiko` **und `pyopenssl`** — ohne Letzteres fehlt FTPS, und alle drei Nachbauten brechen ab; `aufbauen.sh web` holt sie jetzt. Beim ersten Versuch liefen die Nachbauten nach der Probe weiter (Kindprozesse); behoben mit `setsid` und `kill -- -PID` |
| `freigabeprobe` | legt `umlauf-freigabe@…` über `pruefkonto.py` an (die Wege aus `kreislauf.py`) und löscht es; **16 / 0, 17 s**, danach 0 Zeilen in `users` |
| `gpxprobe` | Pfad berichtigt; die Referenz wird gefunden — dahinter F-RP-12 |
| `spaltenregister-wegprobe` | Konto und Passwort aus `kreislauf.py` (`umlauf_konto()`, `UMLAUF_PASSWORT`), keine Zugänge in `pruefablauf.json`; `nach` zieht den edbak-Kreislauf mit und stellt ihn davor (`auswahl.py --selbstprobe` **27 / 0**). Gemessen: Kreislauf 328 771 Vergleiche, 0 unerklärt, 31 s; Wegprobe **34 / 0**, 3 s |
| Zeit je Probe | steht in jeder Zeile des Prüfstands und in der Schlusszeile |

**Eigener Fehler, im Konzept:** Das Kurzkonzept wies der Wegprobe das
Umlaufkonto des **csv**-Kreislaufs zu. Gemessen: „Kein Ruhesegment mit genug
GPS-Punkten" — eine csv-Ausfuhr trägt keine Spur. Der Kopf der Wegprobe
nannte von Anfang an das **edbak**-Konto; die Zuordnung im Konzept war nicht
gegen ihn gelesen. Umgestellt auf `kreislauf-edbak`.

**Reihenfolge:** RP-01 → RP-02 → RP-03 → RP-04 → RP-05, je ein Commit mit
Paketpräfix, Push nach jedem Paket (`CLAUDE.md` 7, 8). RP-01 zuerst, weil
seine Zeit je Probe RP-04 speist und weil erst mit richtiger Reihenfolge
feststeht, welche Proben danach noch rot sind.

**Fächerung:** **keine**, in keinem Paket (Vorschlag, Q-RP-03). Die Arbeit
ist Prüfarbeit an einer Anlage (nicht fächerbar, `CLAUDE.md` 7) oder
Einzeländerung in je einer Probe; die Ursachen sind schon gelesen.

## 4. Fragen an die Betreiberin

| Nr. | Frage | Empfehlung |
|---|---|---|
| **Q-RP-01** | `kreislauf-csv`: Die Referenz vom 15.09. ist älter als die Hausform. **Referenz erneuern** (neue Ausfuhr aus dem Demo-Bestand) oder **drei Ausnahmeregeln** mit Grund „Hausform, PK-04/5b"? | **Erneuern.** Die Ausnahmeliste des Kreislaufs sagt selbst, eine Regel für eine veraltete Referenz wäre ein Filter. Preis: Die GPX-Probe liest dieselbe Datei und muss mit ihr gegengeprüft werden |
| **Q-RP-02** | `freigabeprobe`: nur **nach** den Kreisläufen laufen lassen (eine Reihenfolge im Prüfstand) oder **ein eigenes Konto** anlegen lassen (unabhängig, aber mehr Code)? | **Eigenes Konto.** Eine Probe, die nur in der richtigen Reihenfolge grün ist, bricht beim nächsten Umbau der Liste wieder — und `--datei` kann sie allein auswählen |
| **Q-RP-03** | Fächerung in RP: keine? | **Keine** (Begründung in 3) |

**Beantwortet am 23.09.2026, alle wie empfohlen:**

- **E-RP-01** (Q-RP-01): Die csv-Referenz wird **erneuert**, nicht mit Ausnahmeregeln erklärt.
- **E-RP-02** (Q-RP-02): Die Freigabeprobe legt **ihr eigenes Konto** an und räumt es weg.
- **E-RP-03** (Q-RP-03): **Keine Fächerung** in RP.
- **E-RP-04**: Das Kurzkonzept wird so umgesetzt, RP-01 bis RP-05 nacheinander.

## 5. Was dieses Konzept nicht klärt

- Ob `gegenstellen.py` in dieser Arbeitsumgebung alle drei Dienste hochbringt
  (gelesen, nicht gefahren). Wenn nicht: Befund mit Zahl, und die Frage, ob
  `versandprobe` aus der Nebenstufe in ihr eigenes Muster zurückgeht.
- Ob F-RP-10 die Anwendung ist. Davon hängt ab, ob RP eine Versionsstufe hat.
