# Sitzungshärtung — steht vor jedem `session_start()` die Härtung?

> **Nicht zu verwechseln mit
> `tools/referenzdatensatz/einspielen/sitzungsprobe.py`.** Jene misst, dass
> `sitzung.py` beide Hüllenfassungen öffnet (`edk1:` und `edka1:`, S10). Der
> Name „Sitzungsprobe" war schon vergeben; dieses Werkzeug heißt deshalb
> **Sitzungshärtung**.

Entstanden in P5a/AP4a (Backlog Nr. 205). Sie prüft **eine** Zeile:

```php
ini_set('session.use_strict_mode', '1');
```

```
php tools/sitzungshaertung/pruefen.php --selbstprobe    # 8 Fälle
php tools/sitzungshaertung/pruefen.php                  # der Lauf
```

Rückgabe 0 = keine Befunde, 1 = Befunde, 2 = die Probe kam nicht los.
Sie läuft in **Stufe 1** (`.github/workflows/pruefung.yml`), bei jedem Push.

## Warum eine Zeile ein Werkzeug rechtfertigt

Ohne `use_strict_mode` übernimmt PHP eine Sitzungskennung, die der Browser
mitbringt, **auch wenn es sie nie vergeben hat**. Wer eine Kennung setzen kann
— über einen Link, eine fremde Seite auf derselben Domain, ein gesetztes
Cookie —, kennt damit die Sitzung, in der sich gleich jemand anmeldet. Das ist
Session-Fixation.

Bis Web 20.9.1 stand die Zeile an genau **zwei** Stellen: `install.php` und
`wiederherstellen.php` — ausgerechnet den beiden Wegen, die **keine**
Anmeldesitzung tragen. Auf den fünf, die eine tragen, fehlte sie, und der
Schutz hing damit an der `php.ini` des Hosters. Auf dem Prüfstand steht dort
`Off`.

Die Zeile ist unscheinbar und steht neben dem Aufruf, den sie schützt. Ein
neuer Weg, der `session_start()` aufruft und sie vergisst, sieht genauso aus
wie einer, der sie hat — und es passiert nichts, was auffiele. Genau dafür
ist Stufe 1 da.

## Mit dem Tokenizer, nicht mit `grep`

Ein `grep` über `session_start` findet jede Erwähnung in jedem Kommentar. Die
erste Fassung dieser Prüfung meldete deshalb **zwei** Befunde, und beide waren
Kommentarzeilen, die das Werkzeug selbst beschrieben. Der Tokenizer sieht nur
echte Aufrufe — kein `function session_start()`, kein `"session_start();"` in
einer Zeichenkette, kein `session_started()`.

Die Härtung darf **bis zu zwölf Zeilen** vor dem Aufruf stehen; dazwischen
liegt in der Anwendung regelmäßig ein Kommentarblock und der Aufruf von
`session_set_cookie_params()`.

## Was sie nicht messen kann

- **Ob die Einstellung wirkt.** `ini_set()` kann scheitern — `session.*` lässt
  sich nach `session_start()` nicht mehr setzen, und manche Hoster sperren
  einzelne Direktiven. Das misst nur eine laufende Installation: eine
  vorgegebene Kennung anbieten und nachsehen, ob eine andere zurückkommt.
  ```
  ID=$(head -c16 /dev/urandom | od -An -tx1 | tr -d ' \n')
  curl -sk -D - -o /dev/null -H "Cookie: PHPSESSID=$ID" https://DEINE-INSTALLATION/login.php \
    | grep -i '^set-cookie: PHPSESSID='
  ```
  **Kommt eine andere Kennung zurück, greift die Härtung. Kommt gar keine
  Set-Cookie-Zeile, wurde die vorgegebene übernommen.** Gemessen am
  16.09.2026 auf dem Prüfstand: ohne die Zeile keine Set-Cookie-Zeile, mit
  ihr eine neue Kennung.
  > Dabei eine Falle: Eine Kennung, die schon einmal **benutzt** wurde, ist
  > dem Server bekannt und wird auch mit `use_strict_mode` angenommen — das
  > ist richtig so. Die Probe braucht jedes Mal eine **frische** Kennung,
  > sonst misst der zweite Lauf das Gegenteil des ersten.
- **Die Reihenfolge innerhalb der zwölf Zeilen.** Steht die Zeile in einem
  `if`, das nie zutrifft, zählt sie hier trotzdem.
- **`session_start()` in einer Bibliothek Dritter.** `vendor/` ist
  ausgenommen; `phpseclib3/Crypt/Random.php` startet eine eigene Sitzung und
  wird nicht von uns gepflegt.
