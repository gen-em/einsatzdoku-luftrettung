# Ingestprobe — nimmt die Uhr-Schnittstelle noch das Richtige an?

`php tools/ingestprobe/probe.php [basisadresse]`
(Vorgabe `http://127.0.0.1:8080`)

Rückgabewert 0 = alle Erwartungen erfüllt, 1 = mindestens eine nicht.

## Wozu

AP3 ändert `ingest.php` an der gefährlichsten Stelle, die es gibt: Punkte, die
die Uhr schickt, werden unter bestimmten Umständen **verworfen** — und dann so
quittiert, dass die Uhr sie löscht. Ein Fehler dabei ist stiller, endgültiger
Datenverlust, und er fällt niemandem auf, weil die Antwort „ok" lautet.

Drei Fälle sind zu unterscheiden, und die Grenze zwischen ihnen ist die
**Stufe** der Spur, nicht ihre Punktzahl:

| Lage | Verhalten |
|---|---|
| Stufe 1 oder 2, `seq >= n_original` | annehmen (Nachzügler, E-S2-08) |
| Stufe 3, `seq >= n_original` | verwerfen **und quittieren** |
| jede Stufe, `seq < n_original` | still übergehen (Wiederholung) |

Der zweite Fall darf den ersten nicht verschlucken. Wer statt der Stufe prüfte,
ob überhaupt ein Blob dasteht, wirft bei Stufe 2 genau die Punkte weg, die der
nächste Verdichtungslauf einarbeiten soll — und quittiert sie, so dass die Uhr
sie löscht. Genau dafür ist Teil 3 da.

## Teil 10 — die Mengenbremse (seit Web 20.11.0, P5a/AP7)

Seit Web 20.11.0 zählt `ingest.php` Fehlversuche (Backlog Nr. 17, R19). Teil 10
prüft **beides**: dass die Bremse greift **und** dass sie nichts bremst, was
sie nicht bremsen soll — der zweite Teil ist der wichtigere, denn eine Bremse,
die gelungene Uploads mitnimmt, kostet Einsatzdaten.

| Was | Erwartet |
|---|---|
| 14 Fehlversuche in einem Stoß | alle `401`, **keine** Sperre — das ist ein Schlüsselwechsel |
| Versuche 15 bis 30 | noch immer `401`; der *sperrende* Versuch selbst wird nicht abgewiesen |
| Versuch 31 | `429` mit `{"error":"zu_viele_versuche"}` und `Retry-After: 900` |
| die Antwort | nennt **nicht**, welcher Topf gegriffen hat |
| `403 device_disabled`, `400 payload`, `413 too_large` | zählen **nicht** |
| zweites Gerät an derselben Adresse | lädt weiter hoch |
| gelungener Upload | leert Topf **und** Gerätevermerk |
| 30 erfundene Kennungen | dieselbe Schwelle wie eine bekannte (E-P5a-47) |

**Sie legt dafür drei eigene Geräte an** (`bremse`, `nachbar`,
`abgeschaltet`). Das Uhr-Gerät der Teile 1 bis 9 bleibt unberührt — eine
Sperre auf dessen Kennung machte den nächsten Lauf unbrauchbar.

**Der letzte Abschnitt sperrt die ADRESSE**, und das lässt sich nicht trennen:
`ip:127.0.0.1` ist dieselbe für alle Geräte. Deshalb steht Teil 10 am Ende,
und deshalb leert die Probe `rate_limits` **am Anfang und am Ende**. Bricht ein
Lauf mitten in Teil 10 ab, bliebe die Adresse sonst 15 Minuten gesperrt — und
der nächste Lauf fiele in *jedem* Teil um, mit `429` statt `200`, ohne dass an
der Sache etwas falsch wäre.

**`senden()` liest seit AP7 die Kopfzeilen.** Vorher lieferte die Funktion nur
Code und Rumpf; damit ließ sich `Retry-After` — die halbe Zusage des Pakets —
gar nicht nachweisen.

## Über echtes HTTP

Geprüft wird ein **Endpunkt**: Kopfzeilen, Authentifizierung, JSON-Antwort. Ein
Funktionsaufruf umginge die Hälfte davon. Die Probe spricht deshalb mit
`ingest.php` so, wie die Uhr es tut.

## Die Zeitstempel liegen in der Gegenwart (seit Web 15.6.0)

Bis dahin standen in der Probe **feste März-Daten**. Mit dem **Ersetzfenster**
(Backlog Nr. 134) ist das kein Detail mehr: Ein zweites Paket an einen
Datensatz, dessen `started_at` älter als 72 Stunden ist, wird nicht mehr
übernommen — und die halbe Probe prüft genau solche zweiten Pakete. Zehn
Erwartungen kippten daran, **keine davon zu Recht**: Gemessen wurde ein Fall,
den es im Betrieb nicht gibt, denn eine Uhr lädt hoch, während der Dienst
läuft.

Die Zeitpunkte hängen deshalb an `time()` (`$zeitpunkt(tageZurück, 'HH:MM')`)
und liegen höchstens 66 Stunden zurück. Das Fenster selbst prüft **Teil 9** mit
einem eigenen Datensatz, dessen `started_at` nach dem ersten Paket per SQL
zurückdatiert wird — ausnahmsweise per SQL, weil ein Uhr-Paket `started_at`
eines bestehenden Datensatzes gar nicht mehr ändern kann. Genau das ist der
Punkt.

Gemessen: **1 Paket angenommen, 1 abgewiesen**, dazu die Gegenprobe, dass ein
**neuer** Einsatz weiterhin entsteht.

## Was sie am Bestand ändert

Sie legt ihr **eigenes Konto** (`ingestprobe@gen-em.org`, Backlog Nr. 207)
samt fünf Geräten an und
räumt beides am Ende wieder ab — auch bei einem Abbruch (`finally`), und
ausdrücklich einschließlich der Spuren: Die hängen an keinem Fremdschlüssel
(F-S2-B). Bestehende Daten fasst sie nicht an.

**Die Hintergrundjobs hält sie an**, solange sie läuft (`jobs_pause()`). Sonst
verdichtete oder dünnte der Job mitten in der Probe aus, und die Stufe, die
gerade gilt, wäre nicht mehr die, die die Probe hergestellt hat.

**Konto und Gerät entstehen per SQL**, nicht über die Oberfläche. Das ist eine
bewusste Abkürzung: Geprüft wird `ingest.php`, nicht die Geräteverwaltung. Für
den Weg über die Oberfläche gibt es `tools/referenzdatensatz/einspielen/`.

## Was sie nicht prüft

- **Den vollständigen Referenz-Sendeplan** (612 Anfragen, 212 Pakete — die
  Zahlen stehen in `tools/referenzdatensatz/einspielen/messprotokoll.json`;
  hier stand bis Web 20.11.0 „526 Anfragen, 182 Pakete" aus einem älteren
  Stand). Dafür ist `tools/referenzdatensatz/einspielen/` da; diese Probe
  fährt gezielte Grenzfälle, nicht die Menge.
- **Das Ablaufen einer Sperre.** Teil 10 hebt sie von Hand auf, statt
  15 Minuten zu warten; dass sie von selbst abläuft und die Stufe nach 24 h
  verfällt, prüft `tools/ratenprobe/` an der Datenbank.
- **Nebenläufigkeit.** Ob ein Upload, der genau während eines Verdichtungslaufs
  eintrifft, richtig behandelt wird, lässt sich hier nicht herstellen — einen
  Nebenläufigkeitsprüfstand gibt es im Repositorium nicht. Die Vorkehrung
  dagegen ist die `seq`-Obergrenze in `spur_loeschen_nur_zeilen()`.
- **Die Uhr selbst.** Dass sie auf `next_seq` so reagiert, wie hier
  vorausgesetzt, steht in `watch/source/Uploader.mc` und wird vom
  Uhr-Prüfstand geprüft, nicht hier.

## Voraussetzungen

Eine laufende Installation (der Entwicklungsserver genügt) und die Migrationen
bis `2026_09_16_geraet_abgewiesen` — ohne sie fällt Teil 10 an den beiden
Vermerk-Erwartungen um (die Bremse selbst zählt auch ohne die Spalten).
