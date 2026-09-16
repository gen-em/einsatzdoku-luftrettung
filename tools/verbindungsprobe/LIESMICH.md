# Verbindungsprobe — was tut die Anwendung, wenn kein Platz mehr frei ist?

Entstanden in **P5a/AP9** (E-P5a-18). Sie stellt die Lage her, die man sonst
nur im Betrieb erlebt und dort nicht messen kann: Das Datenbankkonto der
Anwendung hat seine gleichzeitigen Verbindungen ausgeschöpft.

## Warum es sie gibt

MySQL/MariaDB weist eine Verbindung ab, wenn eine von drei Grenzen erreicht
ist. Die Fehlernummern sind verschieden, und das ist der Punkt:

| Nummer | Grenze | wer sie setzt |
|---|---|---|
| **1040** | `max_connections` — der ganze Datenbankserver | der Hoster, serverweit |
| **1203** | Systemvariable `max_user_connections` | der Hoster, für alle Konten gleich |
| **1226** | `ALTER USER … WITH MAX_USER_CONNECTIONS n` | der Hoster, **für dieses eine Datenbankkonto** |

Das Konzept nannte 1040 und 1203. **Gemessen am 16.09.2026 ist der Fall, den
ein geteilter Webspace herstellt, die dritte Zeile** — eine GRANT-Grenze am
Konto, also **1226**. Wer nur die ersten beiden abfängt, hat genau den Fall
nicht abgedeckt, für den die Sache gebaut ist, und merkt es nicht: Die
Anwendung antwortet dann weiter mit 500 und dem ungefilterten Ausnahmetext,
in dem Hostname und Benutzername der Datenbank stehen.

Diese Probe stellt **1226** her, weil sie sich ohne Nebenwirkung herstellen
lässt. Die beiden anderen behandelt der Code in derselben Liste
(`UEBERLAST_CODES` in `server/wartung_lib.php`).

## Aufruf

```
php tools/verbindungsprobe/probe.php
php tools/verbindungsprobe/probe.php --grenze 10 --pakete 20 --arbeiter 8 --frei 2
php tools/verbindungsprobe/probe.php --basis http://127.0.0.1:8080
```

Rückgabewert 0 = alle Erwartungen erfüllt, 1 = mindestens eine offen.

Ohne `--basis` startet die Probe **ihren eigenen PHP-Server** mit
`PHP_CLI_SERVER_WORKERS` und räumt ihn wieder weg. Das ist kein Luxus: Der
eingebaute Server bedient ohne diese Variable genau eine Anfrage nach der
anderen — dann gibt es keine Gleichzeitigkeit zu messen, und Teil 2 wäre eine
Behauptung.

## Der Riegel

Die Probe **ändert eine Berechtigung in der Datenbank**
(`ALTER USER … WITH MAX_USER_CONNECTIONS`). Deshalb schließt sie nach innen:

- Nur gegen `127.0.0.1` oder `localhost` — sowohl die Datenbankadresse aus
  `server/config.php` als auch `--basis` werden geprüft.
- Nur mit Wurzelzugang über den **Unix-Socket**; über TCP meldet MariaDB
  heute `Access denied` (Voreinstellung `unix_socket`-Authentisierung).
- Der Ausgangswert wird gemerkt und im `finally` zurückgeschrieben — auch
  wenn die Probe mittendrin abbricht. Gelingt das nicht, sagt sie es auf
  STDERR mit Konto und Host.
- Das Prüfkonto `verbindungsprobe@gen-em.org` wird am Anfang gelöscht und am
  Ende wieder.

## Die beiden Teile

**Teil 1 — die Antwort.** Alle Plätze belegt. Gemessen wird über echtes HTTP:

- `login.php` → 503, `Retry-After: 5`, `Cache-Control: no-store`, der Satz
  „Der Server ist gerade ausgelastet", **kein** Skript, und **kein Wort** über
  die Datenbank (geprüft gegen sieben Begriffe: `SQLSTATE`, `1040`, `1203`,
  `PDO`, den Datenbankbenutzer, `max_user_connections`, `Stack trace`).
- `ingest.php` → 503 mit `{"error":"ausgelastet"}` als JSON.
- `auth_salt.php` → dasselbe. Es ist der zweite JSON-Weg außerhalb von
  `/api/` und der Grund, warum `JSON_SKRIPTE_AUSSERHALB_API` in AP9 von zwei
  auf vier Einträge gewachsen ist.
- `index.php` → **302**, und das ist richtig: `auth_guard.php` leitet ohne
  Sitzung um, bevor das erste `db()` läuft. Eine Anfrage, die die Datenbank
  nie berührt, kann von ihrer Überlast auch nichts merken. Die Erwartung steht
  ausdrücklich drin, damit die Zahl nicht später als Lücke gelesen wird.
- Der Zähler in `server/ueberlast.json` trägt **genau so viele** Vorfälle, wie
  es Abweisungen gab. Jede höhere Zahl hieße, dass eine Anfrage mehrfach
  zählt — und das wäre der Beleg, dass der Riegel in `db()` gegen die
  Rückkopplung über `app_state_lesen()` nicht greift.

**Teil 2 — null verlorene Uploads.** Ein Teil der Plätze bleibt frei, `--pakete`
Uploads laufen gleichzeitig. Gemessen wird:

- Es kommt **nichts außer 200 und 503** heraus. Eine 500 wäre für die Uhr ein
  Defekt statt eines „gleich noch einmal".
- Wie viele der 503 aus der **Verbindungsgrenze** kamen (Differenz am Zähler)
  und wie viele aus dem **Gedrängel** um dieselbe `days`-Zeile. Zwei Ursachen,
  ein Statuscode — ohne diese Trennung wäre „12 × 503" eine Zahl, die nicht
  sagt, was sie gemessen hat.
- Jedes abgewiesene Paket kommt bei der Wiederholung an, und am Ende stehen
  **alle** Einsätze **und alle Spurpunkte** in der Datenbank.

## Was dabei herauskam (16.09.2026, Web 20.13.0)

Lauf mit `--grenze 10 --arbeiter 8 --pakete 20 --frei 2`, MariaDB 10.11,
PHP 8.4, eingebauter PHP-Server:

```
Teil 1: 10 Verbindungen belegt, dann SQLSTATE[HY000] [1226]
        login.php 503 · ingest.php 503 · auth_salt.php 503 · index.php 302
        Zähler: 3 Abweisungen, 3 gezählt
Teil 2: 8 × 200, 12 × 503, 0 anderes
        davon 10 aus der Verbindungsgrenze, 2 aus Gedrängel
        20 von 20 Einsätzen und 400 von 400 Spurpunkten in der Datenbank
24 von 24 Erwartungen erfüllt.
```

**Der Nebenfund war der teurere.** Vor der Korrektur ergab derselbe Lauf
**12 × HTTP 500** — `SQLSTATE[40001] 1213 Deadlock found when trying to get
lock`, weil alle Uploads eines Diensttags dieselbe `days`-Zeile anfassen
(`dt_zeitraum_fortschreiben()`). Seit AP9 antwortet auch dieser Fall mit 503
`ausgelastet` statt mit 500; die eigentliche Abhilfe — die Transaktion
wiederholen, statt sie zurückzugeben — steht als **Backlog Nr. 210** an.

## Was sie **nicht** misst

- **Z2-Last.** Das Konzept nennt „gegen Z2-Last" (500 Konten à 600 Einsätze).
  Das sind 300 000 Einsätze und ein Tag Rechenzeit. Diese Probe misst das
  **Verhalten an der Grenze**, nicht das Verhalten unter Bestandsgröße; der
  Bestand ist Sache von `tools/messstand/`.
- **1040 und 1203.** Die serverweite Grenze lässt sich auf einer Maschine, auf
  der noch etwas anderes läuft, nicht gefahrlos herstellen; die Systemvariable
  hinter 1203 lässt sich in MariaDB nicht zur Laufzeit setzen, wenn der Server
  mit `--max-user-connections=0` gestartet ist. Beide Nummern sind in
  `UEBERLAST_CODES` und im Code nicht von 1226 unterschieden — geprüft ist
  die Erkennung an 1226, geprüft ist **nicht** die Erkennung an 1040 und 1203.
  Was dazu belegt ist: Beide wurden am 16.09.2026 einzeln herbeigeführt und
  ihre Ausnahme untersucht (`getCode()` = 1040 bzw. 1226, `errorInfo[1]`
  gesetzt, Meldung mit `[1040]` bzw. `[1226]`) — die drei Wege in
  `ueberlast_erkannt()` treffen also alle drei.
- **Den echten Webspace.** Gemessen wird der eingebaute PHP-Server. Ein
  Apache mit PHP-FPM hält eigene Prozessgrenzen, und die liegen möglicherweise
  vor der Datenbankgrenze.
- **Wie die Seite aussieht.** Sie misst, dass sie kommt und was darin steht;
  ob sie bei 360 px überläuft, misst der Bilderlauf.
