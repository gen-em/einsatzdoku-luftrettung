# Wiederherstellungsprobe

Vorher/Nachher-Beleg zu **E-S1-04**, **E-S1-19** und **Backlog Nr. 31/33/34/35**
(Web 8.0.0). Der Papierkorb und der Rückweg eines Backups haben Grenzfälle,
die sich im Browser nur mühsam herstellen lassen und die man dem Ergebnis nicht
ansieht. Vier Teile.

## Teil 1 — Papierkorb aus der Datei

Seit Nutzlast 7 trägt die Backup-Datei den Papierkorb mit, und
`edbak_restore()` bringt ihn als Papierkorb zurück. Zwei Angaben dabei sind
leicht falsch herum gebaut, und beide fallen erst auf, wenn jemand Wochen
später etwas im Papierkorb sucht und es nicht findet.

**E-S1-04 — `deleted_with_day` ist eine UND-Verknüpfung.** Der Wert aus der
*Datei* sagt, ob der Eintrag am Tag hing; der *Zieltag* sagt, ob das hier
überhaupt gelten kann. Wer nur den Zieltag prüft, macht aus jedem einzeln
gelöschten Einsatz einen mitgelöschten: Er verschwindet aus
`trash_list_missions()` — das ist die Liste, die der Papierkorb zeigt — und
wird beim Wiederherstellen des Diensttags ungewollt wieder aktiv. Zwei
Fehler in einem, und der zweite fällt womöglich nie auf.

**E-S1-19 — aktiver Eintrag an gelöschtem Zieltag wird abgelehnt.** Landet
ein in der Datei aktiver Einsatz auf einem Zieltag, der hier im Papierkorb
liegt, stünde er an einem Tag, den die Tagesliste nicht zeigt: in der Suche
sichtbar, in der Übersicht nicht, im Papierkorb auch nicht. Beim endgültigen
Löschen des Tages bliebe er ohne Diensttag zurück. Das ist dieselbe Regel wie
D1, eine Ebene tiefer.

## Teil 2 — eine kaputte Datei darf nur sich selbst kosten

Der Lauf hängt an **einer** Transaktion: Was eine Ausnahme auslöst, reißt
alles mit — auch die neunzig heilen Einsätze daneben. Zwei Stellen taten das
bis Web 8.0.0:

- **Nr. 31** — im Ruhesegment fehlte die Prüfschicht ganz. Ein unbrauchbarer
  Zeitwert lief roh gegen `DATETIME NOT NULL`.
- **Nr. 35** — `track_points` hat den Primärschlüssel
  `(owner_type, owner_id, seq)`. Der Wertebereich von `seq` war geprüft, seine
  **Eindeutigkeit** nicht; zwei Punkte mit derselben Nummer lösten einen
  Schlüsselkonflikt aus. Ein eigener Export erzeugt keine Wiedergänger, eine
  von Hand bearbeitete oder fremde Datei kann es.

Beides muss die eine Zeile beziehungsweise den einen Punkt kosten, nicht den
Lauf — und in der Ablehnungsliste stehen, nicht im Nichts.

## Teil 3 — der halb sichtbare Einsatz hat keinen Weg mehr

Ein **aktiver** Einsatz an einem **gelöschten** Diensttag ist derselbe Zustand,
den Teil 1 beim Einspielen ablehnt (E-S1-19) — und die Anwendung selbst konnte
ihn herstellen. Drei Wege führten hin; alle drei werden hier abgeklopft
(Backlog Nr. 33):

- **Papierkorb → „Wiederherstellen"** beim einzeln gelöschten Einsatz, dessen
  Diensttag ebenfalls im Papierkorb liegt. Wird jetzt abgelehnt; nach dem
  Zurückholen des Tages geht es.
- **Die Uhr** über eine Dienstkennung in `day_refs`, die auf einen gelöschten
  Tag zeigt. Löst jetzt einen **neuen** Tag aus, und die Kennung wird auf ihn
  umgebogen.
- **Das endgültige Löschen** eines Diensttags, an dem noch etwas Aktives hängt
  (aus älteren Ständen). Nimmt jetzt alles mit statt ein Waisenkind ohne
  `day_id` zurückzulassen — und sperrt es für die Uhr.

Der dritte Fall wird von Hand hergestellt: Über die regulären Wege lässt er
sich seit Web 8.0.0 nicht mehr erzeugen.

## Teil 4 — Schritt 1 der Wiedererkennung rät nicht mehr

Ein Diensttag wird beim Einspielen zuerst über die Einsatzkennungen
wiedererkannt. Bisher gewann der **erste** Treffer und verhängte seinen
Diensttag über den ganzen Datei-Tag (Backlog Nr. 34). Die Probe stellt den
Widerspruch her — zwei Einsätze desselben Datei-Tags liegen im Ziel an
verschiedenen Tagen — und erwartet: `tag_mehrdeutig` wird gemeldet, keiner der
beiden Tage wird verhängt, ein eigener entsteht, und die vorhandenen Einsätze
bleiben, wo sie sind.

Dazu zwei **Gegenproben**: Ein eindeutiger Kandidat muss weiterhin greifen
(auch wenn der Fingerabdruck nicht mehr passt), und dabei darf nichts als
mehrdeutig gemeldet werden. Ohne sie bewiese Teil 4 nur, dass die
Wiedererkennung nichts mehr findet.

## Warum eine eigene Probe und nicht der Kreislauf

Der Kreislauf (`tools/referenzdatensatz/`) fährt eine **echte** Backup
durch den Browser und vergleicht das Ergebnis. Er belegt damit, dass der
Papierkorb als Papierkorb zurückkommt — aber der Referenzbestand enthält
keinen Diensttag, an dem gleichzeitig ein mitgelöschter, ein einzeln
gelöschter und ein aktiver Eintrag hängen, und er enthält erst recht keine
kaputte Datei. Genau diese Fälle sind die, in denen sich die Regeln
unterscheiden.

Die Probe baut die Nutzlast deshalb von Hand und ruft `edbak_restore()`
unmittelbar auf — denselben Weg, den `api/backup_restore.php` und der
Demo-Reset nehmen. **Nicht** geprüft ist damit der Weg davor (Entschlüsseln
im Browser, Hochladen) und die Anzeige danach; dafür ist der Kreislauf da.

## Aufruf

    php tools/wiederherstellungs-probe/probe.php

Erwartet: **30 von 30**, Rückgabe `0`.

Der Vorher-Vergleich braucht eine **ganze** Kopie von `server/` aus dem
Vergleichsstand — die Änderungen liegen in mehreren Dateien:

    mkdir /tmp/vorher
    git archive <stand> server | tar -x -C /tmp/vorher --strip-components=1
    cp server/config.php /tmp/vorher/
    php tools/wiederherstellungs-probe/probe.php /tmp/vorher

Gegen **`5e68024`** (vor Nr. 33/34) fallen **11 von 30** durch, genau in Teil 3
und 4. Gegen **`d078494`** (vor Nr. 31/35 und der `deleted_with_day`-Korrektur)
fielen von den damaligen 16 Erwartungen **12** durch; Teil 2 endete dort mit
`SQLSTATE[23000] … Duplicate entry 'mission-<id>-1' for key 'PRIMARY'`, es kam
also gar nichts an.

Fehlt in einem älteren Stand eine Funktion, die die Probe aufruft, **stirbt sie
nicht**, sondern lässt die betroffene Erwartung durchfallen und sagt warum. Ein
Vorher-Vergleich, der mit einem Fatal endet, sagt nichts über die Erwartungen
dahinter.

## Was die Probe anfasst

Sie legt in der Datenbank aus `config.php` fünf Wegwerfkonten unterhalb von
`@example.invalid` an und löscht sie am Ende wieder — samt allem, was daran
hängt. Sie rührt kein anderes Konto an. Trotzdem: **gegen eine
Testinstallation fahren, nicht gegen den Produktivserver.**

## Grenzen

- Sie misst den Zustand in der Datenbank, nicht die Anzeige. Dass der
  Papierkorb `p-m2` auch *zeigt*, folgt aus `trash_list_missions()` — der
  Funktion, die die Seite benutzt —, ist aber nicht im Browser nachgesehen.
- Sie prüft eine Handvoll Diensttage mit wenigen Einträgen, nicht den Bestand.
  Mengen- und Reihenfolgefragen beantwortet der Kreislauf.
- Sie sagt nichts über die Frist: Dass der Löschzeitpunkt aus dem Lauf stammt,
  ist geprüft; dass der Aufräumjob ihn nach `TRASH_DAYS` einsammelt, nicht.
- Teil 2 prüft zwei Sorten kaputter Angaben. Er ist **kein** vollständiger
  Beleg dafür, dass keine dritte übrig ist — dafür wäre die Prüfschicht Feld
  für Feld gegen das Schema zu halten.
- Teil 3 prüft den Uhr-Weg an `dt_zu_dayref()`, nicht an einer echten
  Nachlieferung: Es gibt hier kein Gerät. Die Bedingung in `ingest.php`, die
  denselben Fall für den schon zugeordneten Diensttag abfängt, ist gelesen und
  nicht gemessen.
