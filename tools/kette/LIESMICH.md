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

## Was sie hinterlässt: nichts

Sie räumt Reste früherer Proben weg (alles mit dem Präfix `.zielprobe-` im
Zielverzeichnis) und löscht ihre eigene Datei **auch im Fehlerfall** — sonst
liegt nach dem dritten roten Lauf Müll im Webroot, den jeder abrufen kann.
Der Präfix beginnt mit einem Punkt: `.htaccess` (Z. 64) antwortet auf jeden
Pfad mit führendem Punkt mit 403, ein zweiter Riegel neben dem Löschen.

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

`--selbstprobe` fährt **37 Lagen ohne Netz**: Maskierung (4), Adressen (3),
die Dreiwertigkeit der Sitzungsmessung (4), der Rundlauf gegen Attrappen
(11 — darunter „liegt im FTP, ist über HTTPS 404", „Inhalt weicht ab", „nach
dem Löschen weiter abrufbar", „Hochladen scheitert" und jedes Mal die
Gegenprobe, dass **trotzdem gelöscht wird**), das Aufräumen (2) und das
Passwort außerhalb der Befehlszeile (1). Sie läuft in der Kette **vor** jedem
echten Lauf.

## Was sie nicht kann

Sie misst den Weg für eine **statische** Datei. Ob PHP läuft, ob die
Anwendung antwortet, ob `.htaccess` greift — davon sagt sie nichts. Und sie
misst **ein** Verzeichnis: das, auf das `--ftp-pfad` zeigt.
