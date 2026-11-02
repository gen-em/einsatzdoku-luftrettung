# Prüfdokument PR — Prüfmittel

*Zum Konzept `Konzept-PR-Pruefmittel.md`. Was maschinell gemessen wurde,
steht mit Mittel und Zahl im Prüfprotokoll des Konzepts (Abschnitt 4) und
im Prüfbericht des Commits — nicht hier (CLAUDE.md 7, E-PU-08). Geht nach
dem Merge in `Pruefliste.md` 12 auf.*

## 1. Was nicht geprüft werden konnte, und warum

- **Die Freigabeprobe steht nicht im Prüfbericht des Commits.** Sie
  benutzt `konfig_stellen.php` nicht und hängt deshalb nicht am neuen
  Muster (E-PR-03); ihr Nachweis sind die zwei Abnahmeläufe mit benannter
  Berührung (`--datei`, Prüfprotokoll PR-03) — aus der Hand des
  Prüfstands, nicht vom Tor gegengelesen. Die Proben, die den Rückweg
  gehen, und die Zweitfaktorprobe stehen seit Nr. 460 im Bericht.
- **Andere PHP-Fassung als Webserver.** Die Wartezeit rechnet mit
  `max(2, opcache.revalidate_freq) + 1`. Unter `hochfahren.sh --php 8.3`
  liest die Kommandozeile die Einstellung des Containers, nicht die des
  Abbilds; nicht gemessen, ob das Abbild ein größeres Intervall setzt.
- **Ob Nr. 403 die einzige Ursache war.** Nachgestellt ist eine Ursache
  (Anteilprobe unmittelbar davor, 3 von 3). Ob die Abbrüche vom 27. bis
  28.10.2026 alle diese Folge hatten, lässt sich nicht mehr lesen — die
  Lauf-Protokolle lagen in `/tmp` und sind fort; die Berichte nennen nur
  `rot:chromium`.

## 2. Grenzen der benutzten Prüfmittel

- `nummern.py --selbstprobe` baut seine Zweige in einem Wegwerf-Ursprung;
  ein Merge mit Konflikt ist nicht unter den Fällen (die Nummern in
  Konfliktmarken zählen trotzdem, weil das Muster zeilenweise liest — nicht
  gemessen).
- Die Gegenprobe am echten Repositorium für Nr. 352 lief mit
  `--ohne-holen` gegen die örtlich vorhandenen Zweige (2 Zweige).
- Die Nachstellung von Nr. 403 lief über `proben.sh` einzeln, nicht im
  Prüfstand; die zwei Gesamtläufe ordnen die Proben nach Mustern, nicht in
  der Reihenfolge von `pruefablauf.json` — dort lief die Ratenprobe (auch
  `konfig_stellen`) zwei Proben vor der Freigabeprobe, die Anteilprobe
  früher.

## 3. Handprüfliste

| Nr. | Wer | Bedienweg | Erwartet | Scheitern erkennbar an | Wann |
|---|---|---|---|---|---|
| P-PR-01 | Claude | Beim nächsten Prüfstandlauf, in dem `anteilprobe` und `freigabeprobe` beide laufen (Stufe `haupt` oder Berührung von `krypto`/`schluesselwechsel` und `server/**`): Zeile der Freigabeprobe lesen. | `ok freigabeprobe [chromium]`; die Anteilprobe rund 3 s länger als bisher. | `rot:chromium` mit „Kontoanlage ohne Einrichtungslink" — dann die Seitenkennung lesen; steht dort nicht `login.php`, ist es eine zweite Ursache. | beim nächsten passenden Lauf |
| P-PR-02 | Claude | Beim nächsten Aufnehmen von `main` nach `Pruefablauf.md` 5.3, wenn `main` neue Backlog-Nummern mitbringt: Prüfstand im offenen Merge. | `nummern=0`; der Merge-Commit trägt den Bericht selbst, kein Folge-Commit. | `nummern` rot mit Nummern, die `main` vergeben hat. | beim nächsten passenden Merge |
| P-PR-03 | Claude | Nach einem Lauf der Zweitfaktorprobe: `SELECT COUNT(*) FROM sicherheit_ereignisse WHERE merkmal LIKE '%zweitfaktor%'`. | 0 | eine Zeile oder mehr — dann räumt ein neuer Teil der Probe nicht ab. | beim nächsten Lauf der Probe |
