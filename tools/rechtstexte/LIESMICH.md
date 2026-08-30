# Angriffsprobe für den Rechtstext-Renderer

Entstanden in P3/O10 zusammen mit `server/rechtstexte_lib.php`.

## Warum es sie gibt

`rt_html()` ist die **einzige** Stelle des Projekts, an der aus einer Eingabe
HTML wird. Alles andere geht durch `e()` und erscheint als Text. Diese eine
Funktion trägt damit die ganze Last — und weil sie rein ist (keine Datenbank,
keine Sitzung, kein Browser), lässt sie sich einzeln und vollständig
durchspielen.

Das Konzept verlangt für O10 genau eine Probe: „Markdown-Probe (Überschrift,
Absatz, Liste, Link, HTML-Versuch `<script>` wird als Text gezeigt)". Diese
fünf sind darin enthalten; die übrigen 76 sind die Fälle, die man beim
Nachdenken über einen Renderer findet, bevor jemand anders sie findet.

## Aufruf

```
php tools/rechtstexte/pruefen.php
php tools/rechtstexte/pruefen.php --ausfuehrlich    # zeigt jede Ausgabe
```

Rückgabewert ≠ 0, sobald eine Probe fehlschlägt.

## Wie sie aufgebaut ist

| Gruppe | Frage |
|---|---|
| **A Umfang** | Kann er, was E-P3-38 verlangt? Überschriften, Absätze, Listen, Links |
| **B Rohes HTML** | Kommt irgendein Tag durch? `<script>`, `<img onerror>`, `<iframe>`, `<base>`, `<meta refresh>`, `<style>`, Kommentare, `</textarea>` |
| **C Linkziele** | `javascript:` in allen Schreibweisen, `data:`, `vbscript:`, `file:`, `blob:`, protokollrelative Adressen |
| **D Attribute** | Ausbruch aus `href`, HTML im Linktext, Titel-Zusatz, automatische `id` |
| **E Nicht unterstützt** | Autolinks, Bilder, Referenzlinks, fett/kursiv — bleiben Text |
| **F Zeichen** | CRLF, U+2028, Bidi-Steuerung (Trojan Source), Zero-Width, NUL, Doppelmaskierung |
| **G Ränder** | Leer, nur Leerraum, sehr lang, offene Klammer, verschachtelt |
| **H Bibliothek** | `rt_leer()`, `rt_pruefen()`, `rt_stand_markup()`, `rt_ziel_erlaubt()` |

## Die scharfe Schranke (Gruppe Z)

Die Gruppen A bis H prüfen jeweils **eine** Zeichenkette — und jede einzelne
kann etwas übersehen, weil sie nach dem sucht, woran der Prüfende gedacht hat.

Gruppe Z dreht die Frage um: Sie geht durch die Ausgabe **jeder** Probe und
verlangt, dass darin ausschließlich diese sieben Tags vorkommen —

```
h2  h3  p  br  ul  ol  li  a
```

— und genau **ein** Attribut: `href` am `<a>`.

Damit steht das, was der Renderer je erzeugen darf, in einer einzigen Liste.
Ein neues Tag müsste dort eingetragen werden, bevor es durchginge; ein
`onerror`, `style`, `srcset` oder `formaction` in irgendeiner Ausgabe wäre
ein Treffer, gleich an welchem Tag.

**Diese Prüfung ist die eigentliche.** Die benannten Einzelproben sagen, was
schiefgehen kann; die Schranke sagt, dass nichts anderes herauskommt.

## Was sie nicht leistet

- **Sie prüft die Funktion, nicht die Seite.** Ob `impressum.php` das Ergebnis
  auch wirklich unmaskiert ausgibt und den Leerzustand richtig zeigt, prüft
  der Browser (`tools/screenshots/`, Seiten `04-impressum` und
  `05-datenschutz`).
- **Sie prüft nicht den Editor.** Ob `admin_rechtstexte.php` den Rohtext
  maskiert ins Textfeld legt, steht dort im Markup und ist eine Sichtprüfung.
- **Sie ersetzt keine Content-Security-Policy.** Backlog Nr. 8 bleibt offen;
  eine CSP wäre die zweite Verteidigungslinie hinter dieser hier.
