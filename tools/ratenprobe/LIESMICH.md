# Ratenprobe — die Sperrleiter, die Verlangsamung und die Sammelmail

Entstanden in P5a/AP6. Sie prüft `server/ratelimit_lib.php` — Leiter, Verfall,
zwei Schwellen, globale Verlangsamung, Sammelmail, „Sperre aufheben" und den
Weg zurück nach einem Passwort-Reset.

```
php tools/ratenprobe/probe.php
```

Rückgabe 0 = keine Befunde, 1 = Befunde, 2 = die Migration ist nicht gelaufen.

## Warum es sie gibt

Das Konzept nennt als Abnahme von AP6 einen **„Prüfkonten-Lauf
(`tools/pruefkonten/`)"**. Jenes Werkzeug kann das nicht: Es legt 300 Konten an
und misst die NutzerInnen-Liste — Seitenwechsel, Sortierung, Laufzeit. Mit dem
Ratenschutz hat es nichts zu tun. Die Abnahmezeile war eine Annahme, keine
Beschreibung.

## Sie greift die Bibliothek unmittelbar an, nicht über HTTP

Der Grund ist die **Uhr**. „Nach 24 Stunden ohne Fehlversuch fällt die Stufe"
lässt sich über HTTP nur prüfen, indem man 24 Stunden wartet. Hier wird
stattdessen `stufe_bis` zurückdatiert — dieselbe Wirkung, in einer
Millisekunde. Dasselbe gilt für „die Sperre ist abgelaufen, die Stufe steht
noch" und für die vier Schwellen der Verlangsamung.

## Was sie nicht misst

- **Den Weg durch `login.php`.** Ob die drei Zählungen an der richtigen Stelle
  stehen, ob die Meldung erscheint und ob der Countdown läuft, sagt nur der
  Browser. Die Messungen dazu stehen im Prüfdokument.
- **Echte Gleichzeitigkeit.** Zwei Fehlversuche in derselben Millisekunde sind
  nicht nachgestellt. Die Leiter ist deshalb als **ein** Statement gebaut —
  belegt ist das hier aber nicht.
- **Ob eine Mail ankommt.** Gemessen wird, dass genau eine Zeile in die
  Warteschlange geht und die zweite nicht.
- **Die Wirkung auf einen echten Angriff.** 200 Fehlversuche in einer Schleife
  sind keine 200 Anfragen aus 50 Netzen.

## Was sie gefunden hat

**Die Stufe fiel nie zurück.** Der Verfall (`stufe_bis`) wurde nur in dem
Zweig aufgefrischt, in dem *nicht* gesperrt wurde — also bei den ersten neun
Fehlversuchen. Jeder von ihnen schob die Frist um 24 Stunden vor, sodass sie
beim zehnten nie abgelaufen war. Ein Konto, das vor einem halben Jahr einmal
die vierte Sprosse erreicht hatte, bekam beim nächsten Tippfehler sofort
wieder 60 Minuten.

**Im Betrieb wäre das niemandem aufgefallen.** Nichts bricht, nichts wird rot,
und die Sperre „funktioniert" ja. Behoben: Der Verfall läuft jetzt bei
**jedem** Fehlversuch, vor der Sprosse.

## Eine Falle, die die Probe selbst gestellt hat

`rate_verlangsamung()` merkt sich ihre Antwort **je Anfrage** — im Betrieb
richtig, denn die Lage ändert sich während einer Seitenanfrage nicht. Eine
Probe, die in *einem* Prozess nacheinander vier Lagen herstellt, bekommt
sonst viermal die erste: Der erste Lauf meldete „8,00 s" für Stufe 1, weil der
Merker noch die Stufe 4 aus der Schleife darüber trug.

Deshalb gibt es `rate_verlangsamung(true)` (nur für diese Probe), und deshalb
läuft die **Zeitmessung in einem eigenen PHP-Prozess** — so, wie es im Betrieb
auch ist.

## Sie räumt hinter sich auf

Angelegt werden Zeilen in `rate_limits`, `sicherheit_ereignisse` und
`mail_warteschlange` unter eigenen Merkmalen (Präfix `probe-`) sowie im Topf
`global`. Am Ende sind sie weg; Abschnitt 11 zählt vorher und nachher und
meldet die Zahlen.

> **Der Topf `global` wird vollständig geleert**, nicht nur die eigenen
> Zeilen — er hat nur eine. Wer die Probe auf einer Installation unter
> Beobachtung fährt, verliert damit den laufenden Zählerstand der
> Verlangsamung. Auf einem Prüfstand ist das richtig; auf einem Produktivserver
> hat diese Probe ohnehin nichts zu suchen.
