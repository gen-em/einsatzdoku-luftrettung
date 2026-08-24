# Wiederherstellungsprobe

Vorher/Nachher-Beleg zu **E-S1-04**, **E-S1-19** und **Backlog Nr. 31/35**
(Web 8.0.0). `edbak_restore()` hat zwei Sorten von Grenzfällen, die sich im
Browser nur mühsam herstellen lassen und die man dem Ergebnis nicht ansieht.

## Teil 1 — Papierkorb aus der Datei

Seit Nutzlast 7 trägt die Sicherungsdatei den Papierkorb mit, und
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

## Warum eine eigene Probe und nicht der Kreislauf

Der Kreislauf (`tools/referenzdatensatz/`) fährt eine **echte** Sicherung
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

Erwartet: **16 von 16**, Rückgabe `0`.

Der Vorher-Vergleich braucht eine Kopie von `server/` mit einem älteren
`backup_lib.php` darin:

    cp -r server /tmp/vorher
    git show <stand>:server/backup_lib.php > /tmp/vorher/backup_lib.php
    php tools/wiederherstellungs-probe/probe.php /tmp/vorher

Gegen den Stand vor der Korrektur (`d078494`) fallen **12 von 16** durch:
in Teil 1 beide `deleted_with_day = 0`-Erwartungen, beide Ablehnungen, der
Zähler und die Papierkorbliste (sie ist dort leer); in Teil 2 alle sechs — der
Lauf endet dort mit
`SQLSTATE[23000] … Duplicate entry 'mission-<id>-1' for key 'PRIMARY'`, und
damit ist nichts angekommen.

## Was die Probe anfasst

Sie legt in der Datenbank aus `config.php` zwei Wegwerfkonten unterhalb von
`@example.invalid` an und löscht sie am Ende wieder — samt allem, was daran
hängt. Sie rührt kein anderes Konto an. Trotzdem: **gegen eine
Testinstallation fahren, nicht gegen den Produktivserver.**

## Grenzen

- Sie misst den Zustand in der Datenbank, nicht die Anzeige. Dass der
  Papierkorb `p-m2` auch *zeigt*, folgt aus `trash_list_missions()` — der
  Funktion, die die Seite benutzt —, ist aber nicht im Browser nachgesehen.
- Sie prüft zwei Diensttage mit einer Handvoll Einträgen, nicht den Bestand.
  Mengen- und Reihenfolgefragen beantwortet der Kreislauf.
- Sie sagt nichts über die Frist: Dass der Löschzeitpunkt aus dem Lauf stammt,
  ist geprüft; dass der Aufräumjob ihn nach `TRASH_DAYS` einsammelt, nicht.
- Teil 2 prüft zwei Sorten kaputter Angaben. Er ist **kein** vollständiger
  Beleg dafür, dass keine dritte übrig ist — dafür wäre die Prüfschicht Feld
  für Feld gegen das Schema zu halten.
