# Die Tore der Auslieferungskette

```
python3 tools/kette/tor.py --selbstprobe                              # ohne Netz
python3 tools/kette/tor.py backup      --basis https://… --token …
python3 tools/kette/tor.py wartung-an  --basis https://… --token …
python3 tools/kette/tor.py wartung-aus --basis https://… --token …
python3 tools/kette/tor.py zustand     --basis https://… --token … [--frage migration]
python3 tools/kette/tor.py pause       --basis https://… --token … --sekunden 1800
```

Rückgabewert: `0` = Tor offen · `1` = Tor zu (der Grund steht darüber) ·
`2` = die Prüfung kam nicht zustande (Angabe fehlt).

Nur Python 3, keine Abhängigkeiten. Gegenstelle ist `server/jobs.php` mit dem
Parameter `aktion` (E-P5a-12); das Token ist dasselbe wie für den
Zeitplandienst und steht im Wartungsbereich.

**Dies ist der eine Client dieser Schnittstelle.** Er kennt Adresse, Token,
Zeitgrenze und den Umgang mit einer unlesbaren Antwort. Wer daran vorbei eine
eigene `urllib`-Zeile schreibt, baut einen zweiten Weg, den niemand pflegt —
`kreislauf.py` ruft deshalb dieses Werkzeug auf, statt selbst zu sprechen.

## `pause` — die Jobs anhalten (seit Web 20.16.0)

`--sekunden N` hält an, `--sekunden 0` gibt wieder frei. Es gibt den
Unterbefehl, weil ein Prüfmittel, das gegen eine **ferne** Installation misst,
die Jobs sonst nicht stillstellen kann: `php jobs.php --pause` braucht eine
`config.php` auf demselben Rechner. Genau daran ist Stufe 2 der Kette
gescheitert (Backlog Nr. 219).

**`--sekunden` hat keine Vorgabe, und das ist Absicht.** `0` *hebt die Pause
auf*; ein vergessener Schalter, der als 0 durchginge, gäbe die Jobs frei und
meldete dafür `ok`. Ohne Angabe: Rückgabewert 2.

**Nicht zu verwechseln:** `--pause` ist die Wartezeit *zwischen* zwei
Backup-Aufrufen. Die Sekunden der Job-Pause stehen in `--sekunden`.

## Warum das nicht drei Zeilen im Arbeitslauf sind

Das Backup-Tor ist die eine Stelle der Kette, an der etwas Unwiderrufliches
verhindert wird: ein Deploy auf Produktiv ohne frisches Komplett-Backup. Eine
Bedingung, die das leisten soll, gehört nicht in ein YAML-Feld, in dem sie
niemand liest und niemand ausprobieren kann — sie gehört dorthin, wo eine
Selbstprobe sie nachweisen kann. Genau das verlangt die Abnahme von AP1:

> das Backup-Tor bricht **nachweislich** ab, wenn `komplett` nicht fertig
> meldet

## Zwei Bedingungen, nicht eine

`fertig` allein genügt nicht. Ein Backup, das schon gestern fertig wurde,
meldet ebenfalls `fertig` — und schützt diesen Deploy nicht. Der jüngste
Stand muss deshalb **jünger sein als der Laufbeginn**. Das ist der Unterschied
zwischen „es gibt ein Backup" und „es gibt ein Backup von diesem Stand".

## Warum eine Schleife

Der Token-Einstieg hat 20 s Budget je Aufruf (`JOB_BUDGET_TOKEN`); ein
Komplett-Backup von 10 GB braucht mehr. Ein Lauf arbeitet in Häppchen und
meldet je Aufruf, ob er fertig ist. Wer einmal ruft und das `fertig` glaubt,
hat bei kleinen Beständen recht und bei großen unrecht — und merkt den
Unterschied erst, wenn er das Backup braucht.

Vorgabe: **40 Aufrufe, 20 s Pause** (`--versuche`, `--pause`).

## Ein Nein ist kein Warten

Antwortet die Installation mit `error` — falsches Token, unbekannte Aktion,
kein Serverschlüssel, Speichergrenze erreicht —, bricht das Tor **sofort** ab.
Vierzigmal zu fragen hieße dreizehn Minuten zu warten auf eine Antwort, die
nach dem ersten Aufruf feststand. Ein **Netzfehler** oder eine unlesbare
Antwort ist etwas anderes: Die werden wiederholt.

## Selbstprobe

`--selbstprobe` ersetzt den Abruf an `jobs.php` durch eine Attrappe mit
vorgeschriebenen Antworten und prüft **elf Lagen** — fünf für das Backup-Tor,
sechs für `pause` und den Umgang mit einer Fehlerantwort:

| # | Lage | Erwartung |
|---|---|---|
| 1 | fertig nach zwei Häppchen, frischer Stand | Tor **offen** |
| 2 | meldet nie `fertig` | Tor **zu** |
| 3 | falsches Token | Tor **zu**, sofort |
| 4 | fertig, aber Stand älter als der Laufbeginn | Tor **zu** |
| 5 | fertig, aber gar kein Stand vorhanden | Tor **zu** |
| 6 | `pause --sekunden 1800` | `sekunden` steht in der **abgerufenen** Adresse |
| 7 | `--sekunden 0` | `sekunden=0` wird nicht als leer weggelassen |
| 8 | `zustand` | **kein** `sekunden` in der Adresse |
| 9 | Schrägstrich am Ende der Basis | ändert die Adresse nicht |
| 10 | `pause` ohne `--sekunden` | Rückgabewert **2**, kein stilles Freigeben |
| 11 | Server antwortet **400** | die Begründung kommt an, nicht `_fehler` |

Das ist keine Bequemlichkeit: Die interessanten Lagen — „meldet nie fertig",
„Stand ist von gestern" — lassen sich gegen eine echte Installation nicht
herstellen, ohne sie zu beschädigen.

**Fall 6 misst die ABGERUFENE Adresse und nicht `adresse_bauen()`**, und das
ist der Unterschied zwischen einer Prüfung und einer Zierde: In seiner ersten
Fassung rief er `adresse_bauen()` unmittelbar mit einem von Hand
geschriebenen Feld auf und prüfte, ob `urlencode` es wiedergibt. Streicht man
dann `felder=` in `main()` oder reicht `rufen()` es nicht weiter, bleibt die
Probe grün — gemessen, beides. Jetzt läuft der ganze Weg, nur der Abruf ist
ersetzt; beide Mutationen ergeben **11 → 10 erfüllt, 1 offen**.

Die Selbstprobe läuft in **Stufe 1** (jeder Push) und noch einmal im
Produktionslauf **vor** dem Tor. Ein Tor, das immer aufgeht, sieht von außen
aus wie eines, das geprüft hat.

Stand 17.09.2026: **11 von 11 erfüllt.**

## Was es nicht prüft

Ob die Gegenstelle wirklich der Produktivserver ist, ob das Backup lesbar ist
und ob der Serverschlüssel der richtige ist. Das erste ist Sache der Umgebung
(`PRODUKTION_URL`), das zweite und dritte Sache der
Wiederherstellungsprobe (`tools/wiederherstellungs-probe/`).

---

# Zielprobe — liegt eine hochgeladene Datei hinterher wirklich im Web?

```
python3 tools/kette/zielprobe.py --basis https://nadoku.example \
        --ftp-server HOST --ftp-konto NAME --ftp-pass … [--ftp-pfad /]
python3 tools/kette/zielprobe.py … --ohne-sitzungswiederverwendung
python3 tools/kette/zielprobe.py --selbstprobe
```

Rückgabewert 0 = Rundlauf gelungen, 1 = nicht, 2 = Bedienfehler.

## Warum es sie gibt (F4, E-KH-07)

Die Kette hat bis zum 20.09.2026 geglaubt, was ihr die FTP-Aktion sagte. Ein
grüner Upload-Schritt heißt aber nur: **Die Bibliothek hat keinen Fehler
gemeldet.** Er heißt nicht, dass die Dateien unter der Adresse liegen, die
`PRODUKTION_URL` nennt. Ein falscher Zielpfad, ein zweiter Webspace, ein
Konto, das woandershin eingesperrt ist — von innen sehen alle drei aus wie
Erfolg.

Die Probe schreibt eine Datei mit Zufallsnamen und Zufallsinhalt ins
Zielverzeichnis, holt sie über **HTTPS** zurück, vergleicht Byte für Byte,
löscht sie und prüft das Löschen (danach 404). Das belegt den **lebenden**
Weg vom FTP-Konto bis zur öffentlichen Adresse.

## Zwei Rundläufe, und der zweite ist der wichtige

| | was er misst |
|---|---|
| **1 — flach** | Datei in das **bestehende** Zielverzeichnis, HTTPS zurück, löschen |
| **2 — durch ein neues Verzeichnis** | Verzeichnis **anlegen** (`--ftp-create-dirs`), hineinschreiben, **auflisten**, HTTPS zurück, Datei und Verzeichnis entfernen |

**Warum es den zweiten gibt** (gemessen 20.09.2026): Die Auslieferungsaktion
stirbt an dieser Stelle —

```
creating folder "api/"
  at Client._openDir → Client.ensureDir → ECONNRESET (data socket)
```

— also beim **Auflisten eines eben angelegten Verzeichnisses** über den
Datenkanal. Der flache Rundlauf berührt diese Folge nie: Er schreibt in ein
Verzeichnis, das schon da ist.

**Das hat vier Läufe gekostet.** Der Trennversuch zur TLS-Sitzung war viermal
grün, während der echte Upload viermal rot war — die Probe hatte die kranke
Stelle gar nicht angefasst. Ein Prüfmittel, das den Weg misst, den die
Auslieferung **nicht** geht, ist eine grüne Zahl ohne Aussage.

**Beide laufen immer**, auch wenn der erste scheitert. Gelingt Rundlauf 1 und
scheitert Rundlauf 2, sagt die Probe genau das — und nennt `_openDir` beim
Namen.

## Warum `curl` und nicht die Auslieferungsaktion

Nicht weil er da ist, sondern weil er ein **zweiter** FTPS-Client ist.

Scheitert der Upload in `SamKirkland/FTP-Deploy-Action` und die Zielprobe
gelingt → es liegt an der Bibliothek. Scheitern beide an derselben Stelle →
es liegt an der Plattform. **Das ist der Trennschnitt, den F3 braucht.** Ein
Werkzeug, das denselben Client benutzt, könnte diese Frage nicht beantworten.

## Die zwei Betriebsarten

| Schalter | `curl`-Option | wofür |
|---|---|---|
| (Vorgabe) | — (Wiederverwendung ist `curl`s Verhalten ohne Zutun) | Normalbetrieb |
| `--ohne-sitzungswiederverwendung` | `--no-sessionid` | der Trennversuch (F3) |

`--ssl-reqd` steht in **beiden** Betriebsarten: Es verlangt TLS und hat mit
der Wiederverwendung nichts zu tun. Bis zum 20.09.2026 stand es hier so, als
wäre es der Schalter für „mit" — das war falsch beschriftet.

> **Hier stand `--no-ssl-session-reuse`, und den gibt es nicht.** Gemessen im
> ersten Trennversuch (Lauf 35534784406): `curl: option
> --no-ssl-session-reuse: is unknown`. Der ganze Lauf hat damit **nichts**
> gemessen. Die Selbstprobe hatte geprüft, dass die Zeichenkette im
> ausgeführten Befehl **landet** — nicht, dass `curl` sie **kennt**. Seither
> fragt `curl_kennt()` das Werkzeug selbst (`curl --help all`), in der
> Selbstprobe **und** vor jedem echten Lauf in dieser Betriebsart.

Viele FTPS-Server verlangen die Wiederverwendung; wer sie nicht bietet,
bekommt die Datenverbindung abgeschnitten — **das sieht aus wie ein
`ECONNRESET`**. Gelingt die Probe *mit* und scheitert *ohne*, ist die
Forderung des Servers belegt.

**Ob `curl` die Sitzung in der Fassung dieses Läufers tatsächlich
wiederverwendet, wird gemessen und nicht angenommen.** Der Schalter kann
fehlen oder still ignoriert werden — dann belegte ein gelungener Lauf gar
nichts. Die Ausgabe ist **dreiwertig**, wie `plattform_pruefen()`
(`Technik.md` 5b.1): `JA (gemessen)`, `NEIN (gemessen)` oder **`NICHT
FESTSTELLBAR`**. Das dritte ist kein Nein.

## Was sie über den Datenkanal sagt

Neben „TLS-Sitzung wiederverwendet" steht in **jedem** Rundlauf eine Zeile
**`Datenkanal:`** — `EPSV`, `PASV`, oder **`PASV — NACH einem
EPSV-Fehlschlag`**. Dreiwertig wie die Sitzungszeile: Was nicht zu sehen
war, heißt „nicht feststellbar" und nicht „nein".

**Warum das zählt:** `curl` versucht `EPSV` und fällt bei Fehlschlag
selbsttätig auf `PASV` zurück. Eine Bibliothek, die das nicht tut, bliebe an
derselben Stelle hängen — und das sähe aus wie ein `ECONNRESET` auf der
ersten Datenverbindung. Bis zum 20.09.2026 gab die Probe die ausführliche
Ausgabe nur **im Fehlerfall** aus; vier grüne Läufe haben die Auskunft
verschluckt, auf die es ankam.

## Was sie hinterlässt: nichts

Sie räumt Reste früherer Proben weg (alles mit dem Präfix `zielprobe-` im
Zielverzeichnis, **und** alles mit dem alten Präfix `.zielprobe-`) und löscht
ihre eigene Datei **auch im Fehlerfall** — sonst liegt nach dem dritten roten
Lauf Müll im Webroot, den jeder abrufen kann. Beim zweiten Rundlauf gehört
das Probeverzeichnis dazu: erst die Datei darin, dann `RMD`.

**Der Präfix beginnt ausdrücklich NICHT mit einem Punkt**, und das ist eine
Behebung vom 20.09.2026. Er tat es einmal, als zweiter Riegel neben dem
Löschen: `.htaccess` (Z. 64) antwortet auf jeden Pfad mit führendem Punkt mit
403. Genau das hat die Probe ausgesperrt — sie ruft ihre Datei ja selbst über
HTTPS ab und bekam 403 statt 200. Schlimmer als der Fehlschlag war die
Diagnose: Sie meldete „FTPS-Ziel und HTTPS-Basis zeigen nicht auf dasselbe
Verzeichnis", und das stimmte nicht. Seither ist 403 eine eigene Meldung
(**GESPERRT**) und nie ein falsches Ziel.

## Mengenprobe — EINE Sitzung, viele Verzeichnisse

`--mengenprobe N` (1–500) fährt **statt** der beiden Rundläufe einen einzigen
`curl`-Aufruf, der `N` Verzeichnisse anlegt und in jedes eine Datei schreibt
— über **eine** Steuerverbindung, eine Anmeldung, einen TLS-Aufbau.

**Warum es sie gibt.** Nach fünf Trennversuchen zum `ECONNRESET` auf
Produktiv (F3) waren Läuferabbild, Node-Fassung, Zertifikat, die
TLS-Sitzungswiederverwendung, das Anlegen eines Verzeichnisses und der Weg
zum Datenkanal ausgeschlossen — alle gemessen, alle grün. Übrig blieb ein
Unterschied, den die Zielprobe **bauartbedingt nicht messen kann**: Sie ruft
`curl` je Operation einmal auf und bekommt jedes Mal eine frische Sitzung.
Die Auslieferungsaktion hält **eine** Verbindung offen und fährt 688 Dateien
und 62 Verzeichnisse darüber.

Ein Server, der die zweite oder dritte Datenverbindung **einer** Sitzung
abweist — Zeitgrenze, erschöpfter Portbereich, `MaxConnectionsPerHost`, ein
Ratenschutz —, sieht in der Zielprobe wie ein gesunder Server aus. Er wird
ja jedes Mal neu gefragt.

**Was sie ausgibt.** Den Weg zum Datenkanal, die Zahl der abgeschlossenen
Übertragungen (`226`) gegen die Zahl der verlangten — **„2 von 5" ist das
Ergebnis, auf das es ankommt** —, und bei Abbruch die letzten 40 Zeilen der
Servermeldung wörtlich.

**Sie läuft nie von selbst**, und zwar hinter zwei Riegeln. Der Arbeitslauf
„Auslieferung" hat dafür das Feld **`probelauf_mengenprobe`**: Es wirkt nur
zusammen mit dem Häkchen `probelauf` (sonst bricht der Schritt mit einer
Fehlermeldung ab) und nur mit einer Zahl von 1 bis 500. Dann tritt die
Mengenprobe **an die Stelle** der beiden Rundläufe. Ein Tag-Lauf hat das Feld
gar nicht; ein Push hat es gar nicht.

**Warum überhaupt über die Kette und nicht von Hand:** Die drei Geheimnisse
liegen dort und sonst nirgends. Eine Prüfliste, deren Punkt niemand ausführen
kann, ist keine Prüfliste.

Aufgeräumt wird in einem `finally`, auch nach Abbruch, und was übrigbleibt,
wird gezählt und benannt — der nächste Lauf der Zielprobe nimmt es mit.

## Geheimnisse

Das Passwort geht über `--config -` und **nicht** über die Befehlszeile —
`/proc/<pid>/cmdline` ist lesbar. Jede Ausgabe läuft durch `sag()`, das
maskiert; `curl --verbose` schreibt die Adresse samt Passwort mit.

**Unter vier Zeichen wird nicht maskiert**, und das ist eine Behebung: Mit
einem einzeichigen „Geheimnis" zerschnitt die Maskierung die Dateiliste, die
`aufraeumen()` danach auswerten muss — aus `.zielprobe-alt.txt` wurde
`.zielpro***e-alt.txt`, und das Aufräumen fand seine eigenen Reste nicht
mehr (gefunden von der Selbstprobe am 20.09.2026). Maskiert wird seither am
**Rand**, nicht in der Mitte.

## Selbstprobe

`--selbstprobe` fährt **67 Lagen ohne Netz**: Maskierung (4), Adressen (3),
der Weg zum Datenkanal (5), die Dreiwertigkeit der Sitzungsmessung (4), der
flache Rundlauf gegen Attrappen (13 — darunter „liegt im FTP, ist über HTTPS
404", „Inhalt weicht ab", „nach dem Löschen weiter abrufbar", „Hochladen
scheitert" und jedes Mal die Gegenprobe, dass **trotzdem gelöscht wird**),
die Betriebsarten und die Frage an `curl` selbst, ob er den Schalter kennt
(6), das Aufräumen (4), 403 als Sperre statt als falsches Ziel (2), das
Passwort außerhalb der Befehlszeile (1), der Rundlauf durch ein neues
Verzeichnis samt Gegenprobe am Auflisten (8) und die Mengenprobe (17).

Die Lage, die dort am wichtigsten ist: **alle Ziele stehen in EINEM
`curl`-Aufruf.** Zerfiele die Mengenprobe in viele Aufrufe, wäre sie eine
teurere Fassung des Rundlaufs und könnte den Unterschied, für den es sie
gibt, nie zeigen.

Die Selbstprobe läuft in der Kette **vor** jedem echten Lauf.

## Was sie nicht kann

Sie misst den Weg für eine **statische** Datei. Ob PHP läuft, ob die
Anwendung antwortet, ob `.htaccess` greift — davon sagt sie nichts. Und sie
misst das Verzeichnis, auf das `--ftp-pfad` zeigt, samt **einem** darunter
neu angelegten; ein tiefer Baum wie der der Auslieferung entsteht nur in der
Mengenprobe, und auch dort flach.
