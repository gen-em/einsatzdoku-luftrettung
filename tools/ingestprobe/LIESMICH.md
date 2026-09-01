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

## Über echtes HTTP

Geprüft wird ein **Endpunkt**: Kopfzeilen, Authentifizierung, JSON-Antwort. Ein
Funktionsaufruf umginge die Hälfte davon. Die Probe spricht deshalb mit
`ingest.php` so, wie die Uhr es tut.

## Was sie am Bestand ändert

Sie legt ihr **eigenes Konto** (`ingestprobe@gen-em.org`) samt Gerät an und
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

- **Den vollständigen Referenz-Sendeplan** (526 Anfragen, 182 Pakete). Dafür
  ist `tools/referenzdatensatz/einspielen/` da; diese Probe fährt gezielte
  Grenzfälle, nicht die Menge.
- **Nebenläufigkeit.** Ob ein Upload, der genau während eines Verdichtungslaufs
  eintrifft, richtig behandelt wird, lässt sich hier nicht herstellen — einen
  Nebenläufigkeitsprüfstand gibt es im Repositorium nicht. Die Vorkehrung
  dagegen ist die `seq`-Obergrenze in `spur_loeschen_nur_zeilen()`.
- **Die Uhr selbst.** Dass sie auf `next_seq` so reagiert, wie hier
  vorausgesetzt, steht in `watch/source/Uploader.mc` und wird vom
  Uhr-Prüfstand geprüft, nicht hier.

## Voraussetzungen

Eine laufende Installation (der Entwicklungsserver genügt) und die Migrationen
bis `2026_09_01_letzter_punkt_am`.
