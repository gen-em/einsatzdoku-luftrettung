# CSP-Probe im Browser — trägt die Richtlinie zur Laufzeit?

    node tools/cspprobe/browserprobe.mjs

**Die Quelltexthälfte ist mit PK-04 nach `tools/quelltext/` gezogen**
(`bash tools/quelltext/pruefen.sh csp`); hier bleibt, was einen Browser
braucht. In PK-04/2 geht auch das nach `tools/proben/`.

## Wogegen

Seit Web 20.7.0 schickt jede Seite eine Content-Security-Policy mit
`script-src 'self' 'nonce-…'` und **ohne** `'unsafe-inline'`. Ein
Inline-Skript ohne Nonce führt der Browser dann nicht mehr aus. Das ist der
Zweck der Sache; der Preis ist, dass ein **vergessener** Nonce eine Seite
still lahmlegt — kein PHP-Fehler, kein Protokolleintrag, keine rote Seite.
Der Knopf tut einfach nichts, und die Meldung steht in der Konsole
derjenigen, der es passiert.

Der Fehler ist nicht selten, sondern der Normalfall: Wer eine neue Seite
anlegt, schreibt `<script>` — so steht es in jedem Beispiel der Welt. Die
Schreibweise dieses Projekts ist

    <script<?= kopf_nonce_attr() ?>>

und daran denkt man beim dritten Mal nicht mehr.

## Die fünf Regeln

| # | Regel | Warum |
|---|---|---|
| 1 | Inline-`<script>` braucht einen Nonce | `script-src` ohne `'unsafe-inline'` |
| 2 | Kein `<style>`-Block im Dokument | `style-src 'self'` lässt keinen zu |
| 3 | Keine Ereignis-Attribute (`onclick=` …) | Sie sind Skript im Markup; ein Nonce hilft ihnen nicht, nur `'unsafe-hashes'` — und das steht nicht in der Richtlinie |
| 4 | Keine `javascript:`-Adressen | dasselbe in Grün |
| 5 | Keine fremde Herkunft in `src`/`href` | Zusage „keine fremde Quelle zur Laufzeit“ (CLAUDE.md 4) und `default-src 'none'` |

`style="…"`-Attribute sind **kein** Befund: `style-src-attr 'unsafe-inline'`
lässt sie zu (E-P5a-32). Sie werden nur gezählt, damit ein Wachsen auffällt.
Erwartet: **0** in PHP, **10** in `assets/*.js` (Leaflet-divIcons,
Zeilenvorlagen per `innerHTML`, die Balken der Schnittleiste).

## Wie gemessen wird — und warum nicht mit grep

Mit `token_get_all()`. Zwei Gründe, und beide haben im Bau zugeschlagen:

1. **Kommentare.** Dieses Projekt erklärt sich in langen Kommentaren, und
   sieben davon reden über `<script>`-Blöcke (`db.php`, `ui.php`,
   `version.php`, `kopfzeilen_lib.php`). Ein `grep` fände sie alle. Eine
   Prüfung mit falschem Alarm wird nach dem zweiten Lauf abgeschaltet.
2. **Zerfallende Tags.** Der erste Entwurf sammelte die
   `T_INLINE_HTML`-Stücke einzeln ein und meldete **null** Skript-Stellen bei
   108 tatsächlichen — denn

       <script src="<?= asset('assets/html.js') ?>"></script>

   zerfällt in drei Stücke, und keines davon ist ein vollständiges Tag. Eine
   Prüfung, die null meldet, weil sie nichts ansieht, sieht aus wie eine, die
   nichts gefunden hat.

Deshalb baut die Probe je Datei ein **Markup-Bild**: zeichengenau so lang wie
die Quelle (damit Zeilennummern stimmen), Markup und Zeichenketten verbatim,
Kommentare geleert, `<?php`/`<?=`/`?>` zu Leerzeichen, und im übrigen
PHP-Code `<` und `>` zu `_`. So kann ein `=>` in einem Feldliteral kein Tag
vorzeitig schließen, während `kopf_nonce_attr` als Wort erhalten bleibt.

`server/vendor/` bleibt draußen: phpseclib3 bringt HTML-Hilfen mit
(`File/ANSI.php` malt ein Terminal mit `style="color: white"`), die diese
Anwendung nie aufruft und nie ausliefert.

## Grenzen

Die Probe druckt sie bei **jedem** Lauf mit, nicht nur hier:

- Markup, das zur Laufzeit in `assets/*.js` entsteht. Ein per `innerHTML`
  eingesetztes `<script>` führt der Browser allerdings ohnehin nicht aus.
- **Ob der Nonce wirkt.** Dass `kopf_nonce_attr()` dasteht, heißt nicht, dass
  `kopfzeilen_seite()` vorher lief. Das sieht nur der Browser — dafür sind
  der Bilderlauf und `api/csp_bericht.php` da.
- Ereignisse per `addEventListener` — der richtige Weg, kein Befund.
- Die Liste der Ereignis-Attribute (28 Stück) ist nicht abschließend.

## Die zweite Hälfte: `browserprobe.mjs`

    node tools/cspprobe/browserprobe.mjs
    node tools/cspprobe/browserprobe.mjs --basis https://127.0.0.1:8443

Braucht eine **laufende lokale Installation**
(`sh tools/referenzdatensatz/einspielen/lokal_starten.sh`). 33 Erwartungen,
Rückgabewert 0, wenn alle erfüllt sind; legt nebenbei `kopfzeilen.png` ab.

`tools/quelltext/csp.php` sieht, ob ein `<script>` im Quelltext einen Nonce **trägt**. Ob
er **wirkt**, sieht nur ein Browser: Dass `kopf_nonce_attr()` dasteht, heißt
nicht, dass `kopfzeilen_seite()` vorher lief. Gemessen wird deshalb am
laufenden Server — Kopfzeilen jeder Seite, Nonce je Anfrage neu, JSON-Antwort
mit `nosniff` und bewusst ohne CSP, Umschalten auf scharf und zurück.

**Sie prüft in beide Richtungen, und das ist der Punkt.** Dass die vier
Kachelserver durchkommen, belegt für sich genommen nichts — es könnte auch
heißen, dass die Richtlinie gar nicht greift. Neben jeder Erlaubnis steht
deshalb eine Gegenprobe: ein Host, der *nicht* in der Liste steht; ein
Inline-Skript *ohne* Nonce; ein eingeschleustes `<script src>` von außen.

> **Die wichtigste Zeile ist die Gegenprobe des Meldewegs.** Sie löst
> absichtlich einen Verstoß aus und sieht nach, ob er in `csp_berichte`
> ankommt. Ohne sie wäre „0 Berichte nach dem Bilderlauf" ein wertloser
> Satz — er sähe genauso aus, ob die Richtlinie sitzt **oder** der Meldeweg
> kaputt ist. Am 15.09.2026 war er kaputt: Die Richtlinie trug
> `report-to csp`, die Gruppe `csp` war aber nie auflösbar definiert, und
> Chromium **bevorzugt `report-to` und verwirft den Bericht dann ersatzlos**.
> Der Bilderlauf hatte zuvor „0 CSP-Berichte" gemeldet — bei zwei Verstößen,
> die in der Konsole standen.

**Was sie nicht messen kann:** ob eine Kachel wirklich **ankommt**. Der
Wegwerf-Container hat keine Freigabe zu den Kachelservern; die Anfragen enden
in `net::ERR_ABORTED`. Ob die Richtlinie sie *durchlässt*, ist trotzdem
messbar — über das Ausbleiben einer Konsolenmeldung „Refused to load the
image", gegen eine, die bei `kachel.invalid` erscheint.

## Selbstprobe

Acht Fälle, vier müssen anschlagen, vier dürfen es nicht — darunter ein
Kommentar, der `<script>` nennt, und `data-onload="1" name="onlineform"`
(Wörter, die mit `on` anfangen). Ein grüner Lauf einer Prüfung, die immer
grün meldet, sieht genauso aus wie einer, der nichts gefunden hat.
