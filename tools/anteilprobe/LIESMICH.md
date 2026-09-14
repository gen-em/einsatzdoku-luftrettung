# Anteilprobe — rechnet der Server den Server-Anteil richtig?

```bash
php    tools/anteilprobe/probe.php                # Teile A bis C
php    tools/anteilprobe/probe.php --schreiben    # dazu Teil D (config.php)
python3 tools/anteilprobe/endpunkt.py             # Teil E (api/kdf_upgrade.php)
python3 tools/anteilprobe/endpunkt.py https://127.0.0.1:8443
```

Rückgabewert 0 = alle Erwartungen erfüllt, 1 = mindestens eine nicht.

> **Beide Teile fassen die Installation an und legen zurück.** `probe.php`
> verstellt `app_state.kdf_anteil_kennung` und — mit `--schreiben` —
> `server/config.php`; `endpunkt.py` tauscht die Schlüsselhülle des
> Admin-Kontos. Zurückgelegt wird jeweils im `finally`, auch bei einem
> Abbruch, und beide melden am Ende, worauf sie zurückgestellt haben.
> **Auf einer Installation mit Betrieb nicht fahren:** Für die Dauer des Laufs
> steht in `app_state` eine fremde Kennung, und angemeldete Sitzungen bekommen
> in dieser Zeit keinen Anteil ausgeliefert. Dieselbe Ansage wie bei
> `tools/wartungsprobe/`.

## Wozu — ein falscher Anteil sieht aus wie ein falsches Passwort

Der Server-Anteil (S10, E-S10-02 bis E-S10-04) ist das zweite Geheimnis der
Installation. Er geht per HKDF in den Datenschlüssel jedes Kontos ein; der
Server kann damit nichts öffnen, aber ein Datenbankabzug allein reicht seither
nicht mehr für einen Offline-Angriff auf das Passwort.

Er hat eine unangenehme Eigenschaft: **Wenn er falsch ist, scheitert das
Öffnen der Hülle — und zwar bei jeder NutzerIn gleichzeitig und mit derselben
Fehlerform wie ein falsch getipptes Passwort.** Genau dagegen steht die
Zustandsmaschine in `anteil_zustand()`: Sie vergleicht den Wert in
`config.php` mit der Kennung in `app_state` und **liefert im Zweifel gar
nichts aus**, statt einen Schlüssel herzugeben, der nicht passt. Die
Oberfläche sagt dann, was Sache ist, mit der erwarteten Kennung.

Vier ihrer Lagen entstehen aber erst, wenn man `config.php` oder `app_state`
von Hand verstellt — im Browser sind sie praktisch nicht herzustellen. Hier
werden sie hergestellt, gemessen und zurückgestellt.

## Was gemessen wird

| Teil | Was | Erwartungen |
|---|---|---|
| **A** | `schluessel_kennung()` und `konto_anteil()` — die beiden Rechnungen, gegen von Hand nachgerechnete Werte | 13 |
| **A2** | `huelle_anteil_kennung()` — das Präfix `edka1:<kennung>:` lesen, und die sechs Formen, die **keine** Kennung sind | 7 |
| **A3** | `WRAP_RE` nimmt beide Hüllenfassungen, `PAT_BLOB_RE` bleibt eng | 5 |
| **B** | die fünf Lagen aus E-S10-09: nicht eingerichtet · bereit · Rotation · abweichend (zweimal, von beiden Seiten) · Neuanfang | 24 |
| **C** | `kdf_anteil_alt` ohne laufende Rotation | 3 |
| **D** | `config_eintrag_schreiben()` an der echten `config.php` — anlegen, nicht still ersetzen, mit Ansage ersetzen, entfernen, fremder Eintrag, kein Hexwert, Datei hinterher byte-gleich | 14 |
| **E** | `api/kdf_upgrade.php` als Hüllenfassung — über echtes HTTP mit angemeldeter Sitzung | 33 |

Gemessen am 14.09.2026: **69 von 69** (A bis D) und **33 von 33** (E).

## Drei Entscheidungen, die den Wert dieser Probe ausmachen

**Die Zahlen stehen in der Datei, nicht im Zufall.** Die beiden Probeanteile
`A_HEX` und `B_HEX` sind feste Werte. Eine Probe, die bei jedem Lauf andere
Zahlen misst, kann ihre Erwartungen nicht nennen — und prüft die Rechnung
dann gegen sich selbst.

**Die Hüllen in Teil E sind echt, keine Attrappen.** Jede dort gebaute Hülle
enthält den tatsächlichen Inhaltsschlüssel des Kontos, mit dem tatsächlichen
Datenschlüssel aus HKDF verpackt. Eine Attrappe wäre billiger — der Server
kann eine Hülle ohnehin nicht öffnen und prüft nur das Präfix. Sie ließe aber
im Fehlerfall etwas Unlesbares in der Datenbank stehen, und sie könnte E8
nicht messen: **anmelden, öffnen, derselbe Inhaltsschlüssel.** Wäre er ein
anderer, wären alle Daten des Kontos verloren.

**Teil E prüft den Fehler, der spät auffällt.** Eine Hülle, die auf den
**alten** Server-Anteil zurückgestellt wird, funktioniert — bis
`kdf_anteil_alt` aus `config.php` verschwindet, also genau dann, wenn die
Statusseite meldet, es stehe niemand mehr auf dem alten Anteil. Zwei Zeilen
Prüfung im Endpunkt schließen das aus; E2 und E3 messen sie in beide
Richtungen.

## Grenzen

- **Sie stellt nicht um.** Was der *Browser* tut — Hülle lesen, Datenschlüssel
  bilden, neu hüllen, absenden —, misst der Umstellungslauf von AP2 im echten
  Browser. Diese Probe baut die Hülle selbst und schickt sie; dass `unlock.js`
  dasselbe tut, ist damit **nicht** gezeigt.
- **Sie sieht keine Oberfläche.** Ob die Meldung im Zustand „abweichend"
  erscheint und wie sie aussieht, misst der Browserlauf von AP2 und AP3.
- **Teil D fährt gegen die echte `config.php`**, aber nur gegen den Eintrag
  `kdf_anteil_alt` — den einzigen der drei, der auf einer Installation ohne
  laufende Rotation nicht in Gebrauch ist. Ein Fehlschlag mitten im Lauf
  kostet damit nichts, was gebraucht wird.
- **`endpunkt.py` braucht eine eingerichtete Installation** mit
  Referenzbestand und einem Server-Anteil in `config.php`
  (`tools/referenzdatensatz/einspielen/lokal_einrichten.sh` legt beides an).
  Ohne Anteil meldet E1 sofort, dass nichts ausgeliefert wird — das ist kein
  Fehler der Anwendung, sondern eine nicht erfüllte Voraussetzung.
