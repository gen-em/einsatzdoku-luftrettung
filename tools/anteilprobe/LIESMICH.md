# Anteilprobe — rechnet der Server den Server-Anteil richtig?

```bash
php     tools/anteilprobe/probe.php                # Teile A bis C
php     tools/anteilprobe/probe.php --schreiben    # dazu Teil D (config.php)
python3 tools/anteilprobe/endpunkt.py              # Teil E (api/kdf_upgrade.php)
node    tools/anteilprobe/umstellungslauf.mjs      # Teil F (echter Browser)
node    tools/anteilprobe/umstellungslauf.mjs --motor firefox|webkit
python3 tools/anteilprobe/huelle_stellen.py <konto> <passwort> edk1|edka1
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
| **F** | `umstellungslauf.mjs` — stellt der **Browser** von selbst um? Erstes Anmelden, zweites Anmelden (0 Aufrufe), Daten lesbar, HKDF-Dauer, Entsperrdialog, Demo-Konto | 16 |

Gemessen am 14.09.2026: **69 von 69** (A bis D), **33 von 33** (E) und
**16 von 16** (F, in drei Engines).

## Warum Teil F trotz Teil E nötig ist

Teil E baut die neue Hülle **selbst** und schickt sie an den Endpunkt. Damit
ist gezeigt, dass der **Server** sie richtig annimmt und richtig abweist — und
sonst nichts. Ob `unlock.js` sie beim Anmelden von selbst baut, steht damit
nicht fest. Das ist der eigentliche Vorgang von S10 („stille Umstellung"), und
er läuft nur in einem echten Browser.

Teil F stellt seine Voraussetzung selbst her: Die Umstellung lässt sich je
Konto genau **einmal** beobachten, danach ist sie gelaufen. `huelle_stellen.py`
setzt die Hülle des Prüfkontos vorher auf `edk1:` zurück — es öffnet die
vorhandene Hülle mit dem Schlüssel, den ihr Präfix verlangt, und verpackt
**denselben** Inhaltsschlüssel neu. Ohne diesen Schritt misst der zweite
Aufruf etwas anderes als der erste, und das Prüfmittel bestätigte sich selbst.

Gemessen wird an `umlauf-csv@gen-em.org`, nicht am Admin-Konto: Der
Referenzbestand liegt dort (83 Einsätze, davon 80 mit verschlüsseltem Block),
und die Frage dieses Pakets ist nicht „läuft es durch", sondern „sind die
geschützten Angaben danach noch lesbar". Das Admin-Konto hat 0 Einsätze und
könnte die Frage nicht beantworten.

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

## Vier Fallen, in die dieser Prüfstand gelaufen ist

Sie stehen hier, weil sie beim nächsten Mal wieder danebenlägen. Drei von
ihnen sahen aus wie ein Fehler der Anwendung und waren einer des Prüfmittels
— die teuerste Sorte, weil man am falschen Ende sucht.

**`php -S` bedient EINE Anfrage zur Zeit.** Teil F öffnet in Schritt 5 einen
zweiten Tab für den Entsperrdialog. Blieb der offen, wartete Schritt 6
**minutenlang** auf eine Seite, die einzeln gemessen in **1,7 Sekunden** da
ist: Der zweite Tab hielt mit seiner Karte die Leitung besetzt. Der Lauf
schließt den Tab jetzt sofort. Wer diesem Prüfstand einen weiteren Tab
hinzufügt, denkt an dieselbe Zeile.

**Die Mengenbremse des Demo-Kontos** (E-P1-20: 20 Anmeldungen je Stunde).
Wer Teil F mehrfach hintereinander fährt, löst sie aus — und dann blieb der
Lauf drei Minuten in der Zeitgrenze stehen und meldete „Timeout exceeded",
während auf der Anmeldeseite der wahre Grund stand. Teil F liest die Meldung
jetzt und zählt den Demo-Teil als **nicht gemessen, mit Grund**, statt einen
roten Haken für etwas zu setzen, das die Anwendung richtig macht. Auf einer
Testinstallation lässt sich die Bremse mit
`DELETE FROM rate_limits WHERE topf IN ('demo','demog')` lösen.

**`waitUntil: 'networkidle'`.** Die Kartenkacheln liegen bei
`tile.openstreetmap.org` und werden vom Egress-Filter des Containers
abgewiesen; Firefox und WebKit versuchen es weiter, und „das Netz ist ruhig"
tritt nie ein. Der Lauf lief dort in die Zeitgrenze, während Chromium
durchkam — ein Unterschied, den man ohne die drei Motoren nie gesehen hätte.
Gewartet wird jetzt darauf, dass `EdCrypto` und `EdUnlock` geladen sind.

**Jede Engine nennt den Abbruch anders.** Chromium sagt `ERR_ABORTED`, WebKit
`Load request cancelled`, Firefox `NS_BINDING_ABORTED`. Ein Filter, der nur
die Chromium-Schreibweise kennt, meldet in den anderen beiden eine rote Zahl
für dasselbe harmlose Verhalten. Teil F zählt deshalb zwei Töpfe getrennt —
Fehler der **Anwendung** (die zählen) und Fehler des **Prüfstands** (die
werden genannt, nicht gezählt).

## Grenzen

- **Die Teile A bis E stellen nicht um.** Was der *Browser* tut — Hülle lesen,
  Datenschlüssel bilden, neu hüllen, absenden —, misst erst Teil F. Teil E
  baut die Hülle selbst und schickt sie; dass `unlock.js` dasselbe tut, ist
  damit **nicht** gezeigt.
- **Kein Teil sieht die Zustände `abweichend` und `Rotation` an der
  Oberfläche.** Teil B stellt sie in der Zustandsmaschine her, aber ob die
  Meldung erscheint und wie sie aussieht, misst der Browserlauf von AP3.
- **Teil D fährt gegen die echte `config.php`**, aber nur gegen den Eintrag
  `kdf_anteil_alt` — den einzigen der drei, der auf einer Installation ohne
  laufende Rotation nicht in Gebrauch ist. Ein Fehlschlag mitten im Lauf
  kostet damit nichts, was gebraucht wird.
- **`endpunkt.py` braucht eine eingerichtete Installation** mit
  Referenzbestand und einem Server-Anteil in `config.php`
  (`tools/referenzdatensatz/einspielen/lokal_einrichten.sh` legt beides an).
  Ohne Anteil meldet E1 sofort, dass nichts ausgeliefert wird — das ist kein
  Fehler der Anwendung, sondern eine nicht erfüllte Voraussetzung.
