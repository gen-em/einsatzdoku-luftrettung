# Freigabeprobe — der Weg mit Wiederherstellungsschlüssel

```
sh tools/referenzdatensatz/einspielen/lokal_starten.sh
node tools/freigabeprobe/probe.mjs [basis] [ziel-email] [ziel-passwort]
```

Rückgabewert `0` = alle Erwartungen erfüllt, `1` = mindestens eine nicht.

Vorgaben: `https://127.0.0.1:8443`, `umlauf-edbak@gen-em.org`,
`umlaufpruefung2026`. **Das Zielkonto muss bestehen und dieses Passwort
haben** — die Probe legt es nicht an, sondern sagt es, wenn die Anmeldung
scheitert. Es entsteht über den Kreislauf (`vergleich/kreislauf.py`) oder von
Hand über den Einladungsweg.

## Wozu

Eine Administration darf eine Sicherung mit geschützten Angaben **nicht**
unmittelbar in ein neu aufgesetztes Konto spielen (E20): Die Angaben sind mit
einem Inhaltsschlüssel verschlüsselt, den nur der Wiederherstellungsschlüssel
öffnet — und der liegt ausschliesslich bei der NutzerIn. Sie gibt die
Sicherung deshalb frei, und das Umschlüsseln geschieht in ihrem Browser.

**Dieser Weg war bis Web 12.0.0 nie geprüft, und er hat auch nie
funktioniert.** Der Kasten, in dem der Schlüssel einzugeben ist, wurde von
einem stillen `TypeError` verschluckt (F-S2-F): `freigabeLaden()` sprach eine
Kennung an, die es im Markup nicht gab, und der leere `catch` nahm den Fehler
mitsamt der Zeile mit, die den Kasten sichtbar macht.

Nach der Behebung blieb eine Lücke: Belegt war der Fensterweg mit einer Quelle
**ohne** geschützte Angaben. Der Zweig mit Schlüssel liess sich nicht prüfen,
weil die vorhandenen Prüfkonten ihren Wiederherstellungsschlüssel nicht
aufbewahrt haben — er wird bei der Ersteinrichtung genau einmal angezeigt.

Diese Probe stellt sich ein Konto her, dessen Schlüssel sie kennt.

## Was sie belegt

| | |
|---|---|
| Der Kasten **erscheint** | F-S2-F: er tat es nie |
| Er fragt nach dem Schlüssel | nur, wenn das Paket geschützte Angaben trägt |
| Ein **falscher** Schlüssel wird abgewiesen | und es wird **nichts** geschrieben |
| Der richtige läuft durch | Einsatz und Spur kommen an |
| Der Chiffretext ist ein **anderer** | umgeschlüsselt, nicht durchgereicht |
| Der **Klartext** ist derselbe | geöffnet mit dem Schlüssel des Zielkontos |

## Alle Krypto kommt aus der Anwendung

Hülle (`pat_wrap_rc`), Prüfsumme (`pat_key_check`) und Chiffretext
(`pat_blob`) entstehen **im Browser** über `assets/crypto.js` —
`newRecoveryCode()`, `recoveryKeyHex()`, `contentKeyCheck()`, `encrypt()`.
`vorbereiten.php` legt sie nur ab und rechnet nichts.

Das ist der Punkt: Ein zweiter Rechenweg in PHP wäre eine zweite Umsetzung
derselben Krypto, und die Probe prüfte dann sich selbst. Dieselbe Linie wie
bei der Containerprobe (S2/AP5) und der GPX-Probe (S2/AP4) — kein Weg darf
seinen eigenen Fehler bestätigen.

## Was sie hinterlässt

Nichts. Das Quellkonto `probe-freigabe-quelle@example.invalid` wird am Ende
gelöscht, auch wenn die Probe scheitert (`finally`). Das **Zielkonto** wird
dabei geleert und trägt danach den eingespielten Einsatz — es ist ein
Prüfkonto, und der Kreislauf setzt es ohnehin neu auf.

## Grenzen

- Sie prüft **einen** Einsatz mit geschützten Angaben, nicht viele über
  mehrere Fenster. Die Umschlüsselung läuft je Fenster über dieselbe
  Funktion; die Zahl ändert daran nichts.
- Sie prüft **nicht**, was geschieht, wenn sich einzelne Einsätze mit einem
  ansonsten richtigen Schlüssel nicht öffnen lassen (beschädigter
  Chiffretext). Bei Fassung 2 werden sie am Ende genannt statt vorher
  abgefragt — begründet in `docs/CHANGELOG.md`, Web 12.0.0.
