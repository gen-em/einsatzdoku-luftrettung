# Klassen im Markup, die keine Regel im Stylesheet brauchen

Die Vollständigkeitsprüfung meldet jede Klasse, die im Markup steht und im
Stylesheet keine Regel hat. Das ist die Gegenprobe, die in O11 den
ungestalteten Export-Knopf gefunden hat (F-P3-BA) — eine Klasse ohne Regel
kann ein Element ohne Gestaltung sein.

Nur: Die meisten Treffer sind keine. Nach O11 standen 29 Namen in der Liste,
davon 21 Skriptanker und 8 Bruchstücke aus zusammengesetzten Klassennamen.
Eine Liste, in der ein echter Fund neben 28 falschen steht, wird nach dem
dritten Mal nicht mehr gelesen — und dann findet sie auch den echten nicht.

**Deshalb diese Liste** (Backlog Nr. 39, beschlossen in O11, umgesetzt in
O12). Sie funktioniert wie die Streichliste:

- **`[bleibt]`** — die Klasse braucht zu Recht keine Regel. Der Treffer
  verschwindet aus dem Befund und wird nur noch gezählt.
- **`[offen]`** — die Klasse hat keine Regel, und ob sie eine braucht, ist
  eine offene Frage. Der Treffer **bleibt ein Befund**, steht aber unter
  eigener Überschrift, damit er nicht in der Menge untergeht.

Ein Eintrag ohne einen dieser beiden Vermerke wird nicht anerkannt: Die
Prüfung meldet ihn als „Eintrag ohne Vermerk". Und ein Eintrag, dessen Klasse
inzwischen eine Regel hat oder aus dem Markup verschwunden ist, wird als
„ungenutzt" gemeldet — sonst verwahrlost die Liste so still wie die Sache,
gegen die sie schützt.

| Klasse | Grund |
|---|---|
| `art` | [bleibt] Kein Klassenname, sondern eine **Variable**: `geo.js:81` baut `'geo-ringpunkt-' + art`. Das Werkzeug liest Zeichenketten, nicht ausgeführten Code, und nimmt das Bruchstück vor dem `+` für einen Namen. |
| `k` | [bleibt] Dasselbe: `geo.js:49` und `missiontable.js:160` bauen `'<span class="' + k + '">'`. |
| `klasse` | [bleibt] Dasselbe: `import_ui.js:359` und `:459` setzen `class="' + klasse + '"`. |
| `ton` | [bleibt] Dasselbe: `'meldung meldung-' + ton` (import_ui.js:48, missiontable.js:188, einstellungen.php:1776). Die Regeln heißen `.meldung-info`, `.meldung-ok`, `.meldung-warn`, `.meldung-fehler` und sind alle da. |
| `kennzahl-raster-` | [bleibt] Bruchstück von `'kennzahl-raster-' + SPALTEN_JE_SATZ[ansicht]` (zeitraum.php:368). Die vollständigen Namen `.kennzahl-raster-2` bis `-4` haben Regeln. |
| `plakette-` | [bleibt] Bruchstück von `` `plakette plakette-${ton}` `` (einsatz.php:417). |
| `pwq-` | [bleibt] Bruchstück von `'pwstaerke pwq-' + ergebnis.staerke` (pwquality.js:206). |
| `nb-veh` | [bleibt] Skriptanker am Rettungsmittel-Auswahlfeld der Nachbearbeitung; das Aussehen kommt aus `.feld-eingabe`, das am selben Element steht. Im Quelltext ausdrücklich als solcher vermerkt (`nachbearbeitung.php:259`). |
| `showif` | [bleibt] Skriptanker: `einsatz_form.php:1642` sammelt `.showif`, um abhängige Felder ein- und auszublenden. Ein Behälter ohne eigenes Aussehen. |
| `parentcheck` | [bleibt] Skriptanker am Elternkästchen einer Feldgruppe; das Aussehen kommt aus `.schalter-box` am selben Element. |
| `feld-gesperrt` | [bleibt] Zustandsmarke, die `suche.php:960` per `classList.toggle` setzt und wieder nimmt. Sie steuert kein Aussehen, sondern merkt sich eines. |
| `filtergruppen` | [bleibt] Behälter der Filterleiste; das Aussehen kommt aus `.leiste-liste` am selben Element (`suche.php:76`). |
| `wochentage` | [bleibt] Zusatzname an einer Segmentgruppe; das Aussehen kommt aus `.segment .segment-mehrfach` am selben Element (`suche.php:103`). |
| `tag-form` | [bleibt] Kennzeichnung des Diensttag-Formulars für die Skripte; die Gestaltung liegt bei `.tag-form-fuss` und den Feldbausteinen darin. |
| `fld` | [bleibt] Beschriftung im Einsatzformular (`<label class="fld">`); das Aussehen kommt aus der Elementregel für `label` in Abschnitt 17 (Grundformen). Der Name ist der Rest einer Namensfamilie, deren übrige Glieder (`fld-reihe`) Regeln haben. |
| `karte-block-phasen` | [bleibt] Skriptanker an der Phasenkarte (`einsatz.php:128`); das Aussehen kommt aus `.karte` am selben Element. |
| `imp-cell` | [bleibt] Skriptanker; `import_ui.js:811` unterscheidet über `classList.contains` die Zelltypen der Importtabelle. Das Aussehen kommt aus `.tabelle` und den Eingabe-Grundformen. |
| `imp-skip` | [bleibt] Wie `imp-cell` — Beschriftung um das Überspringen-Kästchen. |
| `imp-skipbox` | [bleibt] Wie `imp-cell` — das Kästchen selbst, gelesen in `import_ui.js:814`. |
| `imp-dup` | [bleibt] Wie `imp-cell` — Auswahlfeld für Dubletten, gelesen in `import_ui.js:819`. |
| `imp-daymode` | [bleibt] Wie `imp-cell` — Auswahlfeld für den Umgang mit abweichender Besatzung, gelesen in `import_ui.js:824`. |
| `imp-param` | [bleibt] Skriptanker an den Importparametern; `import_ui.js:89` sammelt `.imp-param`, um die eingestellten Werte einzulesen. |
| `rea-kopf` | [offen] Kopfzeile einer Reanimationssitzung. Sie steht neben `phasen-eingabe`, und **von dort** kommt das Aussehen — die Klasse selbst tut nichts, und kein Skript liest sie. Entweder ist sie ein Rest und gehört weg, oder die Kopfzeile soll sich von einer gewöhnlichen Phasenzeile unterscheiden und braucht eine Regel. Das ist eine Gestaltungsfrage, keine Aufräumarbeit. |
| `rea-beginn` | [offen] Beschriftung „Reanimationsbeginn" in derselben Kopfzeile; dasselbe Bild — Aussehen aus der Elementregel für `label`, kein Skript liest die Klasse. |
| `rmneu` | [offen] Der Knopf „neu" in der Rettungsmittelwahl. Er steht neben `rmopt`, und von dort kommt sein Aussehen; ob sich der Neu-Knopf von den übrigen Möglichkeiten abheben soll, ist offen. |
| `phasen-name` | [offen] Der Name einer Phase in der Einsatzansicht (`einsatz.php:631`). Ohne Regel steht er in der Textschrift der Zeile; ein Name neben einer Zeitangabe könnte eine eigene wollen. |
| `imp-warn` | [offen] „abweichende Crew (…)" in der Kopfzeile einer Tagesgruppe der Importvorschau — ein **Warnhinweis, der wie Fließtext aussieht**. Von allen Einträgen dieser Liste der wahrscheinlichste echte Fund. |
| `imp-daygroup` | [offen] Die Kopfzeile einer Tagesgruppe in der Importvorschau. Sie trägt ihren Text in `<strong>`, sonst nichts — eine Gruppenüberschrift, die aussieht wie eine Datenzeile. |
