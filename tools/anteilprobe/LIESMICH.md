# Anteilprobe — rechnet der Server den Server-Anteil richtig?

```bash
php     tools/anteilprobe/probe.php                # Teile A bis C
php     tools/anteilprobe/probe.php --schreiben    # dazu Teil D (config.php)
python3 tools/anteilprobe/endpunkt.py              # Teil E (api/kdf_upgrade.php)
node    tools/anteilprobe/umstellungslauf.mjs      # Teil F (echter Browser)
node    tools/anteilprobe/umstellungslauf.mjs --motor firefox|webkit
node    tools/anteilprobe/betriebslauf.mjs         # Teil G (Oberflaeche, Browser)
node    tools/anteilprobe/betriebslauf.mjs --motor firefox|webkit
python3 tools/anteilprobe/huelle_stellen.py <konto> <passwort> edk1|edka1
```

Rückgabewert 0 = alle Erwartungen erfüllt, 1 = mindestens eine nicht.

> **Drei Teile fassen die Installation an und legen zurück.** `probe.php`
> verstellt `app_state.kdf_anteil_kennung` und — mit `--schreiben` —
> `server/config.php`; `endpunkt.py` tauscht die Schlüsselhülle des
> Admin-Kontos; `betriebslauf.mjs` schreibt **alle drei**: `config.php`
> (byteweise verglichen), beide Marken in `app_state` und vorübergehend die
> Hüllen aller Konten. Zurückgelegt wird jeweils im `finally`, auch bei einem
> Abbruch, und alle drei melden am Ende, worauf sie zurückgestellt haben.
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

## Was jeder Teil voraussetzt

Damit ein Lauf, der rot meldet, nicht mit einer fehlenden Voraussetzung
verwechselt wird — dieselbe Lehre wie F-S10-AP3-03:

| Teil | braucht |
|---|---|
| A–D (`probe.php`) | MariaDB und eine `server/config.php` mit einem eingerichteten `kdf_anteil`. **Ohne Anteil meldet Teil B eine andere Lage**, nicht einen Fehler |
| E (`endpunkt.py`) | zusätzlich einen laufenden Server unter `https://127.0.0.1:8443` (`tools/referenzdatensatz/einspielen/lokal_starten.sh`) und das Admin-Konto mit seinem Kennwort. **E0 stellt die Ausgangslage selbst her** und zählt sie mit |
| F, G (`*.mjs`) | zusätzlich Playwright mit der gewünschten Engine (`tools/sandbox/aufbauen.sh web` beschafft die Bibliotheken und misst nach, dass alle drei starten). **G Abschnitt 8b** braucht zusätzlich den Wiederherstellungsschlüssel aus Abschnitt 8; fehlt er, meldet der Lauf **10 nicht gemessene** Erwartungen statt einer kleineren grünen Zahl |

## Was gemessen wird

| Teil | Was | Erwartungen |
|---|---|---|
| **A** | `schluessel_kennung()` und `konto_anteil()` — die beiden Rechnungen, gegen von Hand nachgerechnete Werte | 11 |
| **A2** | `huelle_anteil_kennung()` — das Präfix `edka1:<kennung>:` lesen, und die sechs Formen, die **keine** Kennung sind | 7 |
| **A3** | `WRAP_RE` nimmt beide Hüllenfassungen, `PAT_BLOB_RE` bleibt eng | 5 |
| **B** | die fünf Lagen aus E-S10-09: nicht eingerichtet · bereit · Rotation · abweichend (zweimal, von beiden Seiten) · Neuanfang | 29 |
| **C** | `kdf_anteil_alt` ohne laufende Rotation | 3 |
| **D** | `config_eintrag_schreiben()` an der echten `config.php` — anlegen, nicht still ersetzen, mit Ansage ersetzen, entfernen, fremder Eintrag, kein Hexwert, Datei hinterher byte-gleich | 14 |
| **E** | `api/kdf_upgrade.php` als Hüllenfassung — über echtes HTTP mit angemeldeter Sitzung; **E0** ist seit S10/AP5 die Ausgangslage selbst (`huelle_stellen.py` hat die Hülle auf `edk1:` gestellt) | 34 |
| **F** | `umstellungslauf.mjs` — stellt der **Browser** von selbst um? Erstes Anmelden, zweites Anmelden (0 Aufrufe), Daten lesbar, HKDF-Dauer, Entsperrdialog, Demo-Konto | 16 |
| **G** | `betriebslauf.mjs` — die **Oberfläche** der Zustände (S10/AP3): drei gleiche Kennungen, das Blatt im Druck bei 210 mm, `abweichend`, Nachtragen falsch und richtig, Rotation, alten Anteil entfernen, Neuanfang und sein zweiter Versuch | 50 |

Gemessen am 14.09.2026: **69 von 69** (A bis D), **33 von 33** (E),
**16 von 16** (F) und **35 von 35** (G) — F und G in **drei Engines**.

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

## Dreizehn Fallen, in die dieser Prüfstand gelaufen ist

Sie stehen hier, weil sie beim nächsten Mal wieder danebenlägen. **Zehn von
ihnen sahen aus wie ein Fehler der Anwendung und waren einer des Prüfmittels**
— die teuerste Sorte, weil man am falschen Ende sucht. Zwei davon haben
obendrein etwas kaputtgemacht: ein Konto ohne Passwort und ein Konto ohne
Schlüssel.

*Die Überschrift sagte bis S10/AP5 „Elf", während zwölf Absätze darunter
standen — eine abgeschriebene Zahl in einem Dokument, das vom Zählen handelt.
Nachgezählt und mit der dreizehnten fortgeschrieben.*

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

**Ein Prüfstand, der an der Anwendung vorbei schreibt, muss ihre Aufräumarbeit
mitmachen.** `betriebslauf.mjs` setzt `config.php` von Hand — und wartete
danach 30 Sekunden auf ein Eingabefeld, das es in der gemessenen Lage nicht
gab. Die Datei war geschrieben, die Anwendung las sie nur nicht: **`php -S`
läuft mit eingeschaltetem OPcache** (`opcache.enable_cli` gilt für die SAPI
`cli`, der eingebaute Server heißt `cli-server`), und der prüft den
Zeitstempel nur alle `opcache.revalidate_freq` Sekunden — Vorgabe 2. Die
Anwendung selbst hat das Problem nicht: `config_eintrag_schreiben()` verwirft
den Zwischenspeicher nach jedem Schreiben (aufgestellt in S2/AP7). Der
Prüfstand sitzt in einem anderen Prozess und kann das nicht; er wartet
deshalb nach jedem Schreiben 2,2 Sekunden. **Das war ein Fehler des
Werkzeugs, nicht der Anwendung** — und er sah zwei Stunden lang wie einer der
Anwendung aus.

**Dieselbe Falle ZWISCHEN zwei Läufen.** `probe.php --schreiben` nimmt
Einträge aus `config.php` und trägt sie wieder ein — über die Anwendung, also
mit Verwerfen des Zwischenspeichers. Nur: Das geschieht im **CLI-Prozess**,
und der Server hat seinen eigenen. Wird `endpunkt.py` unmittelbar danach
gestartet, kann der Server bis zu zwei Sekunden lang eine Fassung ausliefern,
in der der Anteil fehlte: Der Lauf meldete `ANTEIL_STAND 'fehlt'` statt
`'demo'` und **29 von 33**, wenige Sekunden später **33 von 33**. Wer die
Teile hintereinander fährt, lässt zwischen ihnen ein paar Sekunden — oder
liest ein Ergebnis, das nicht die Anwendung beschreibt, sondern den Takt des
Zwischenspeichers.

**`form.requestSubmit()` läuft in `confirm.js`.** Der Betriebslauf drückte
damit die Knöpfe „wechseln", „entfernen" und „Neuanfang" — und nichts
geschah: `requestSubmit()` löst das Ereignis `submit` aus, und die
Rückfrage fängt es ab, um ihren Dialog zu zeigen. Der Aufruf kehrte
klaglos zurück, und die sechs folgenden Erwartungen maßen eine Seite, die
sich nie geändert hatte. `form.submit()` ginge am Zuhörer vorbei, misst dann
aber einen Weg, den niemand geht. Der Lauf **drückt jetzt den Knopf und
beantwortet den Dialog**, wie es eine Betreiberin täte — damit ist die
Rückfrage selbst mitgemessen.

**Eine Voraussetzung, die man herstellen muss, stellt man her.** `endpunkt.py`
misst ab E4 die Umstellung einer `edk1:`-Hülle. Steht das Konto schon auf
`edka1:` — und das tut es nach jedem eigenen Lauf, nach dem Umstellungslauf
und nach jedem Anmelden im Browser —, antwortet der Endpunkt `nicht_noetig`,
und der Lauf meldet **22 von 33**, ohne dass an der Anwendung etwas fehlte.
Genau so gelesen in S10/AP3. `endpunkt.py` ruft die Ausgangslage jetzt selbst
über `huelle_stellen.py` her und sagt in der Kopfzeile, was es vorgefunden
hat. **Ein Prüfmittel, dessen Zahl von der Reihenfolge der Aufrufe abhängt,
ist keines.**

**Ein zweiter Tab ist keine zweite Sitzung.** Der Betriebslauf meldete sich in
einem Tab des Hauptkontexts an, der bereits als Betreiberin angemeldet war;
`login.php` leitet dort sofort weiter, und der Lauf wartete 30 Sekunden auf
ein Anmeldefeld, das es auf der Zielseite nicht gibt. Jede Probeanmeldung
bekommt jetzt einen **eigenen Browserkontext** — eigene Cookies, und zugleich
das, was gemeint ist: ein anderer Mensch, ein anderer Browser.

**Wer aus JavaScript PHP-Quelltext baut, nimmt EINFACHE Anführungszeichen.**
Der Betriebslauf legte sechs Felder des Admin-Kontos über `php -r` zurück und
setzte die Werte mit `JSON.stringify()` ein — also in doppelte
Anführungszeichen. PHP ersetzt darin alles, was wie eine Variable aussieht.
Aus dem bcrypt-Hash `$2y$12$xdD.Dxofamu…` wurde `$2y$12.`, sieben Zeichen:
**Das Konto war danach mit keinem Passwort mehr erreichbar**, und der nächste
Lauf blieb an der Anmeldung stehen, ohne zu sagen, warum. Es gibt jetzt
`phpStr()`, und die Rückgabe zählt am Ende alle sechs Felder gegen den Stand
vom Anfang.

**Ein Dialog, den niemand ruft, erscheint nicht.** Abschnitt 8b wartete auf
den Entsperrdialog des Admin-Kontos — das hat **keine geschützten Angaben**,
also fragt keine Seite von selbst danach. Der Lauf ruft
`EdUnlock.ensureContentKey()` jetzt selbst und geht den Weg zu Ende: Passwort
eintragen, „Entsperren" drücken, lesen, was dasteht. **Die Meldung erscheint
erst nach der Eingabe**, und das ist richtig so: Der Dialog fragt zuerst nach
dem Passwort und sagt erst dann, woran die Ableitung gescheitert ist.

**Eine übersprungene Erwartung, die nicht mitgezählt wird, sieht aus wie
Erfolg.** Abschnitt 8b des Betriebslaufs überspringt seine zehn Erwartungen,
wenn der Wiederherstellungsschlüssel aus Abschnitt 8 fehlt — bis S10/AP5 mit
einer Zeile auf der Konsole und sonst nichts. Die Schlusszeile meldete dann
**„40 von 40 erfüllt, 0 offen"**: grün, rund, und zehn Erwartungen ärmer als
beim Lauf davor. Wer nur auf die letzte Zeile sieht, sieht den Unterschied
nicht. Der Lauf führt jetzt einen Zähler `nichtGemessen` wie
`umstellungslauf.mjs` und trägt ihn bis in die Schlusszeile. *Dieselbe Falle
wie bei `endpunkt.py` (Nr. 8) — und sie ist deshalb zweimal aufgetreten, weil
sie beim ersten Mal als Einzelfall behandelt wurde statt als Muster.*

**Der Schnappschuss gehört vor die ERSTE Handlung, nicht vor die, die man für
die erste hält.** Das ist die teuerste Zeile dieser Datei, denn sie hat echte
Daten gekostet. Abschnitt 6b meldet ein Konto an und lässt die stille
Umstellung laufen — die packt den Inhaltsschlüssel mit dem Datenschlüssel des
**neuen** Anteils neu ein. Der Schnappschuss der Hüllen stand in Abschnitt 7,
also danach; zurückgelegt wurde auf einen bereits umgestellten Stand, und das
`finally` nahm den zugehörigen Anteil gleich darauf wieder aus `config.php`.
`umlauf-csv@gen-em.org` trug anschließend eine Hülle mit der Kennung eines
Anteils, den es nicht mehr gibt — **ausgesperrt**, und der einzige Rückweg
wäre sein Wiederherstellungsschlüssel gewesen, den niemand notiert hatte. Das
Konto musste neu eingerichtet werden. **Eine Umhüllung ist nicht
rückrechenbar:** Wer eine Verschlüsselung anfasst, sichert vorher, was er
sonst nicht wiederbekommt — und zwar bevor irgendetwas geschieht.

## Grenzen

- **Die Teile A bis E stellen nicht um.** Was der *Browser* tut — Hülle lesen,
  Datenschlüssel bilden, neu hüllen, absenden —, misst erst Teil F. Teil E
  baut die Hülle selbst und schickt sie; dass `unlock.js` dasselbe tut, ist
  damit **nicht** gezeigt.
- **Teil G sieht die Oberfläche, aber nicht das Papier.** Er misst das Blatt
  in `media: print` bei 718 px — das ist die *gerechnete* Druckansicht, nicht
  der Ausdruck. Ob ein Drucker die Vierergruppen so setzt, wie Chromium sie
  rechnet, sagt nur ein Ausdruck. (Die frühere Grenze „kein Teil sieht
  `abweichend` und `Rotation` an der Oberfläche" ist mit Teil G gefallen.)
- **Teil G stellt drei Dinge her, die es nicht messen kann.** Die Zahl der
  Konten auf dem alten Anteil setzt er per SQL, statt vier Anmeldungen
  abzuwarten; den Zustand `abweichend` erzeugt er über `app_state`, nicht
  über ein verlorenes `config.php`. Was gemessen wird, ist die **Reaktion**
  der Oberfläche auf die Lage — nicht, dass die Lage auf dem üblichen Weg
  entsteht.
- **Teil D fährt gegen die echte `config.php`**, aber nur gegen den Eintrag
  `kdf_anteil_alt` — den einzigen der drei, der auf einer Installation ohne
  laufende Rotation nicht in Gebrauch ist. Ein Fehlschlag mitten im Lauf
  kostet damit nichts, was gebraucht wird.
- **`endpunkt.py` braucht eine eingerichtete Installation** mit
  Referenzbestand und einem Server-Anteil in `config.php`
  (`tools/referenzdatensatz/einspielen/lokal_einrichten.sh` legt beides an).
  Ohne Anteil meldet E1 sofort, dass nichts ausgeliefert wird — das ist kein
  Fehler der Anwendung, sondern eine nicht erfüllte Voraussetzung.
