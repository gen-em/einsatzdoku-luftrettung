# Umsetzung des Code-Reviews — Stand

Dieses Dokument hält fest, welche Befunde des Code-Reviews bereits behoben
sind. Es wird bei **jeder** Auslieferung fortgeschrieben und ist die Antwort
auf die Frage „ist das schon erledigt?", ohne dass jemand den Changelog
rückwärts lesen muss.

Grundlage: `Konzept-Behebung-Review-Befunde.md` (nicht im Repository — es ist
das Arbeitsdokument der Umsetzung). Ausgangsstand `e457b2d`, 117 Befunde,
davon 94 zu beheben und 23 als bewusst richtig bestätigt.

## Fortschritt

| Paket | Inhalt | Version | Stand |
|---|---|---|---|
| P0 | Gemeinsame Bausteine und Migration | Web 4.0.0 | **erledigt** |
| P1 | Sofortmaßnahmen | Web 4.1.0 | **erledigt** |
| P7 | Dokumentation und Verträge | Web 4.1.1 | **erledigt** |
| P2 | Kette „unlesbarer Schlüssel" schließen | Web 4.1.2 | **erledigt** |
| P3 | Gemeinsame Prüfschicht anwenden | Web 4.2.0 | **erledigt** |
| P5 | Papierkorb und gelöschte Flugtage | Web 4.3.0 | **erledigt** |
| P4 | Ratenschutz und unangemeldete Endpunkte | Web 4.4.0 | **erledigt** |
| P6 | Sitzung, Rollen, Konten | Web 4.5.0 | **erledigt** |
| P8a | Aufräumen, Bündel 1+2 | Web 4.5.1 | **erledigt** |
| P8b | Aufräumen, Bündel 3+4 | Web 4.5.2 | **erledigt** |
| — | Nachträge aus dem Betrieb | Web 4.5.3 | **erledigt** |
| P8c | Aufräumen, Bündel 5 und 6 | Web 4.6.0 | **erledigt** |
| P9a | Einrichtung, Migrationen, Passwortgüte | Web 4.7.0 | **erledigt** |
| P9b | Ableitungsrunden (M2-01, S7) | Web 5.0.0 | **erledigt** |
| P9c | Formatkennung (M2-10), Uhr-App | — | offen |

---

## P9b — Ableitungsrunden (Web 5.0.0)

M2-01 Schritte 2 bis 4 und S7. Keine Schemaänderung; die Spalte `users.kdf_iter`
gibt es seit P0 und wird jetzt erstmals gelesen.

### Entschieden: Zielwert 320 000, nicht 600 000 und nicht 310 000

Zunächst war beschlossen, nur den Mechanismus zu bauen und den Wert bei 310 000
zu belassen. Beim Schreiben von Schritt 4 zeigte sich die Folge: Der Pfad liefe
dann **nie**. Toter Code, der in der Produktion noch nie ausgeführt wurde, ist
genau das, was bricht, wenn man ihn Jahre später zum ersten Mal scharf schaltet
— und zwar an der einzigen Stelle des Projekts, die sich nicht reparieren lässt.

320 000 ist deshalb kein Sicherheitswert, sondern ein Prüfwert: Der Mechanismus
läuft einmal für jedes Konto wirklich durch, bei etwa drei Prozent mehr
Wartezeit. Ein späterer Sprung auf 600 000 ist danach eine Zeile auf einem
erprobten Weg.

### Entschieden: der Salz-Endpunkt nennt eine Liste

Der Kommentar in `auth_salt.php` hatte diesen Fall wörtlich vorweggenommen: Die
Rundenzahl muss für unbekannte Adressen dieselbe sein wie für echte Konten.
Nennte der Endpunkt den Wert **des Kontos**, wäre während der Umstellung jede
Adresse mit dem alten Wert nachweislich ein echtes, seither nicht benutztes
Konto.

Die Liste löst das vollständig: Die Antwort ist für alle Adressen nicht bloß
ununterscheidbar, sondern **buchstäblich gleich**. Welche Zahl gilt, entscheidet
der Server bei der Anmeldung — also nach dem Nachweis, dass jemand das Passwort
kennt. Es bleibt bei genau einer bcrypt-Prüfung.

Der Preis: doppelte Ableitungszeit bei der Anmeldung, solange die Liste zwei
Einträge hat. In `db.php` steht, wann ein Wert entfernt werden darf — erst,
wenn kein Konto ihn mehr trägt. Vorher wäre das Entfernen eine unwiderrufliche
Aussperrung genau dieser Konten.

### Entschieden: die Rundenzahl ist ein Pflichtparameter

`deriveKeys()` hat keinen Vorgabewert. Ein Vorgabewert ließe jede vergessene
Aufrufstelle stillschweigend mit 310 000 rechnen, und weil heute alle Konten
diesen Wert tragen, fiele das nicht auf — sondern erst bei der nächsten
Anhebung, und dann als „Passwort falsch" bei richtiger Eingabe. Lieber ein
lauter Fehler beim Entwickeln als ein leiser im Betrieb.

### Der Weg der stillen Anhebung

Passwort und Schlüsselhülle liegen nie gleichzeitig vor: Bei der Anmeldung hat
der Browser das Passwort, aber nicht die Hülle; auf der ersten angemeldeten
Seite ist es umgekehrt. Gelöst über das Vormerkfach, dasselbe Verfahren wie
beim Passwortwechsel seit Web 4.5.0 (M2-07).

Der Endpunkt `api/kdf_upgrade.php` ist funktional eine Passwortänderung.
Deshalb: altes Token als Nachweis (sonst wäre er ein Weg, aus einer übernommenen
Sitzung ein beliebiges Passwort zu setzen), nur Werte aus der Liste, nur nach
oben, unveränderte Prüfsumme — und **kein** erhöhter Sitzungszähler, denn das
Passwort hat sich nicht geändert.

### Ein Fehler, den erst die Ende-zu-Ende-Prüfung gezeigt hat

Nach der Anhebung passte der neue Datenschlüssel nicht mehr zu der Hülle, die
die Seite mitgebracht hatte — `PAT_WRAP` wird gerendert, bevor die Anhebung
läuft. Der Entsperrdialog wäre unmittelbar nach jedem Anmelden erschienen: das
Gegenteil einer stillen Anhebung, und die Art Fehler, die im Betrieb niemand
meldet, weil man sich an die Abfrage gewöhnt.

Der Inhaltsschlüssel wird deshalb direkt abgelegt (`EdCrypto.setContentKey()`,
neu) und an die Hülle der laufenden Seite gebunden; beim nächsten Seitenaufbau
verwirft `EdKeyGuard` ihn wegen der abweichenden Bindung und entpackt ihn aus
der neuen Hülle.

Die Einzelprüfungen konnten das nicht sehen — sie prüften den Endpunkt, nicht
den Weg dorthin.

### S7 — Containerformat 3

Kopf von 9 auf 13 Byte, Rundenzahl als 4 Byte big endian, in der AAD gebunden.
Fassung 2 wird weiterhin gelesen (dort galt immer 310 000, die Fassungsnummer
ersetzt die fehlende Angabe). Eine neuere Fassung wird als solche benannt statt
als falsches Passwort.

### Nachweis

79 automatische Prüfungen, alle bestanden.

* **43 zu Endpunkt und Anmeldung**, echter HTTP-Verkehr gegen den PHP-Server,
  echte Ableitung mit WebCrypto: identische Antwort des Salz-Endpunkts für
  bekannte und erfundene Adressen (Felder, Werte, Salzlänge); Serverauswahl des
  passenden Tokens; sechs Abwehrfälle mit anschließender Prüfung, dass
  Rundenzahl, Hülle und Sitzungszähler unverändert sind; Anmeldung vor und nach
  der Anhebung; Konto ohne Schlüsselhülle.
* **18 zum vollständigen Browserweg**: Anmeldung mit Vormerkfach, Auflösung,
  Anhebung, Entsperren ohne Dialog, zweite Anmeldung mit dem neuen Wert,
  Verwerfen eines fremden Vormerkfachs.
* **18 zum Containerformat**: Fassung 3 schreiben und lesen, von Hand gebaute
  Fassung-2-Datei, gefälschte Rundenzahl im Kopf, zu neue Fassung, Pflichtwert.
  Zusätzlich wurde das Python-Beispiel aus `Backup-Format.md` gegen eine echte
  Fassung-3-Datei laufen gelassen.

**Nicht ohne Testinstallation prüfbar:** die tatsächliche Dauer der Ableitung
auf den benutzten Geräten und das Verhalten in mehreren offenen Tabs.

---

## P9a — Einrichtung, Migrationen, Passwortgüte (Web 4.7.0)

Die vier Befunde aus P9, die weder die Schlüsselableitung noch das Format des
Chiffretexts berühren. Keine Schemaänderung.

### Entschieden: P9 wird aufgeteilt

P9 enthält die beiden gefährlichsten Änderungen des ganzen Reviews — die
Rundenzahl der Schlüsselableitung (ein Fehler sperrt alle Konten gleichzeitig
aus) und eine Formatkennung vor jedem Chiffretext (berührt jeden `pat_blob`).
Sie zusammen mit vier harmlosen Befunden auszuliefern hieße, im Fehlerfall
nicht zu wissen, woran es lag.

Dazu kommt: Sechs Pakete (P3 bis P8c) sind auf der Testinstallation noch
ungeprüft. Nach der Reihenfolge P9a → Testinstallation nachziehen → P9b → P9c
steht im Fehlerfall jeweils eine überschaubare Menge zur Auswahl.

| Paket | Inhalt | Version |
|---|---|---|
| P9a | M6-01, M1-11, M2-02, M2-03 | 4.7.0 |
| P9b | M2-01 Schritte 2–4, S7 | 5.0.0 |
| P9c | M2-10, M7-03, Pair.mc 409 | 5.1.0 + Uhr 1.7.0 |

### Entschieden: nicht jede destruktive Migration prüft den Inhalt

Der Befund verlangt, dass die Sprungprüfung destruktiver Migrationen nach dem
Inhalt fragt statt nach der Struktur. Wörtlich umgesetzt hätte das zwei
Migrationen dauerhaft blockiert, bei denen das Löschen der **Zweck** ist:
`phase10_entfernen` (die Phase gibt es nicht mehr, ihre Zeitstempel sind
bedeutungslos) und die Zeitzonen-Migration (kurzlebige Ratenschutz-Zähler).

Deshalb zwei getrennte Angaben:

* `zerstoert` — Klartext, was verlorenginge. Hebt die Zeile in der Vorschau
  hervor. Tragen **alle** destruktiven Migrationen.
* `inhalt` — die Spalten, deren Inhalt die Migration vernichten würde. Tragen
  nur die, deren Spalten von Hand eingegebene Daten hielten: `loc_addr`,
  `loc_lat`, `loc_lon`, `mission_no`, `site_desc`.

Die Strukturprüfung in `skip` bleibt daneben bestehen — sie beantwortet die
andere Frage, nämlich ob die Änderung überhaupt noch aussteht. Eine bereits
gelöschte Spalte hat keinen Inhalt mehr und wäre sonst von einer leeren nicht
zu unterscheiden.

### Entschieden: eine blockierte Migration hält die Kette nicht an

Ein **Fehler** bricht die Schleife ab, weil er auf halbem Weg stehengeblieben
sein kann. Eine **blockierte** Migration hat dagegen nichts getan; die
Datenbank steht exakt wie zuvor, als gäbe es sie nicht.

Der Unterschied ist nicht akademisch: Hielte eine blockierte Migration die
Kette an, käme auf einer Installation mit Altbestand in `site_desc` keine
spätere Migration mehr durch — darunter die Sicherheitsbausteine aus
`2026_08_08`. Ein Datenschutz, der die Sicherheitsupdates blockiert, wäre ein
schlechter Tausch.

Dazu ein **Ausweg**, den der Befund nicht verlangt: je blockierter Migration
ein eigenes Häkchen. Ohne ihn säße der Betreiber fest — die Daten lassen sich
nicht automatisch in den verschlüsselten Block überführen, er entsteht
ausschließlich im Browser. Auf der Kommandozeile gibt es die Stufe bewusst
nicht.

### Entschieden: die Nachweiskennung steht im Dateinamen

Ein Nachweis, der nur im **Inhalt** einer Datei mit festem Namen steht, ist bei
Einfachhosting keiner: Dort liegt das Anwendungsverzeichnis im
Web-Wurzelverzeichnis, und die Datei wäre über die Adresszeile abrufbar. Die
Kennung steht deshalb im Dateinamen — 128 Bit Zufall, die nur nennen kann, wer
das Verzeichnis sieht. Die `.htaccess` sperrt die Datei zusätzlich, als zweite
Schranke.

### Ein Fehler, den erst die Prüfung gezeigt hat

Der erste Entwurf band die Kennung an die **Sitzung**. Die Prüfung im Container
förderte zutage, dass damit jeder Aufruf der Seite eine weitere Datei liegen
lässt — auch der eines Neugierigen, auch ein Vorschau-Abruf des Browsers. Wer
danach ins Verzeichnis sieht, findet mehrere und weiß nicht, welche seine ist.

Die Sitzungsbindung braucht es auch gar nicht: Die Kennung ist geheim, weil man
das Verzeichnis sehen muss, um sie zu lesen. Eine vorhandene Datei wird jetzt
übernommen statt ersetzt. Das schließt zugleich eine Lücke, die die erste
Fassung geöffnet hätte: Wer die Datei bei jedem Aufruf neu schreiben ließe,
könnte einem Betreiber mitten in der Einrichtung die Kennung unter den Händen
wegziehen.

### Entschieden: das Kontopasswort nur für die Sicherung, nicht für den Export

Beide Dateipasswörter steigen von 8 auf 10 Zeichen und laufen über B9. Das
Angebot, stattdessen das Kontopasswort zu verwenden, gilt aber nur für die
Sicherung: Sie ist für einen selbst. Die Exportdatei ist ausdrücklich zum
Weitergeben gedacht — wer sie mit seinem Kontopasswort verschlüsselt, gibt es
dem Empfänger mit.

Geprüft wird das Kontopasswort im Browser, ohne den Server zu fragen: Aus
Passwort und Salz entsteht der Datenschlüssel, und mit dem muss sich die
gespeicherte Hülle öffnen lassen.

### Nachweis

74 automatische Prüfungen, alle bestanden.

* **M6-01 gegen eine echte Altinstallation:** Spalte `site_desc`
  wiederhergestellt und teilweise gefüllt, dann die Wartungsseite über echten
  HTTP-Verkehr bedient. Vorschau nennt Spalte und Zeilenzahl und ändert nichts;
  Ausführen ohne Häkchen lässt Spalte, Daten und Verbuchung unangetastet und
  lässt die **spätere** Migration trotzdem durchlaufen; Ausführen mit Häkchen
  löscht und protokolliert die Freigabe; der zweite Lauf ist unauffällig.
  Leere Zeichenketten zählen nicht als Inhalt, eine Spalte voller NULL ist eine
  leere Spalte, eine fehlende Spalte wird übergangen.
* **M1-11 als echte Ersteinrichtung:** eigener Webserver auf einer Kopie des
  Verzeichnisses, Datenbank mit einer Fremdtabelle. Ohne Nachweis und mit
  falschem Nachweis passiert nichts — und es gibt auch keine Auskunft darüber,
  ob die Datenbankverbindung stünde. Mit richtigem Nachweis läuft die
  Einrichtung durch, die Fremdtabelle bleibt unangetastet, die Nachweisdatei
  wird entfernt, der erneute Aufruf ist gesperrt. Mehrfache Aufrufe aus
  verschiedenen Sitzungen erzeugen genau eine Datei.
* **B9:** Mindestlänge, Allerweltswörter (auch „Luftrettung1“ und
  „Passwort123!“), Muster, Stärkestufen.

**Nicht ohne Testinstallation prüfbar:** Darstellung der Stärkeanzeige, das
Häkchen „Kontopasswort verwenden“ im Zusammenspiel mit dem Entsperrdialog, das
Verhalten der `.htaccess` auf einem echten Apache.

---

## P8c — Aufräumen, Bündel 5 und 6 (Web 4.6.0)

Zehn Befunde: die beiden Leistungsbefunde und die sechs Befunde vom Grad M ohne
Abhängigkeit zu anderen Paketen. Keine Schemaänderung. **Damit ist P8
abgeschlossen.**

| Befund | Änderung | Baustein |
|---|---|---|
| M3-15 | Spurpunkte der Tagesansicht gebündelt statt je Datensatz | `sql_in_bloecken()` |
| M5-12 | Sicherungsaufbau gebündelt: 226 → 16 Abfragen bei 43 Einsätzen | `sql_in_bloecken()` |
| M5-04 | Formelzeichen im CSV-Export neutralisiert, Zahlen ausgenommen | — |
| M4-02 | Leere und zu kurze Listen löschen nichts mehr, `kept_*` in der Antwort | — |
| M5-05 | Höhenberechnung beim Wiedereinspielen aus der Transaktion genommen | — |
| M5-13 | Herkunftskonto der Sicherung wird beim Einspielen angezeigt | — |
| M6-03 | Maskierung vereinheitlicht | B7 |
| M6-05 | Vier Kopien durch eine ersetzt, jetzt in `assets/html.js` | B7 |
| M6-06 | Fünf Entschlüsselungsschleifen durch `entschluessleListe()` ersetzt | B8 |
| M2-06 | Wiederherstellungsschlüssel wird vor der Ableitung geprüft | — |

### Entschieden: M4-02 geht bewusst weiter als der Befund

Der Befund beschreibt die **leere** Liste. Umgesetzt ist die allgemeinere
Regel: Übergangen wird jede Liste, die weniger gültige Einträge enthält als der
gespeicherte Stand.

Der Grund ist derselbe wie beim leeren Fall, nur weniger sichtbar. Eine halb
aufgebaute Nachricht kommt mit drei Phasen an, wo acht stehen — und der Verlust
fällt niemandem auf, weil die Antwort „ok“ lautet.

**Für den einzigen vorhandenen Client kostet das nichts.** Nachgesehen in
`watch/source/Model.mc`: `setPhase()` hängt an, ein erneutes Setzen erzeugt
einen weiteren Zeitstempel statt einen bestehenden zu ändern (das ist die
Korrektur-Regel aus JSON-Vertrag 3). Eine kürzere Liste kann auf der Uhr nicht
entstehen.

Gezählt wird **nach** der Prüfung, nicht davor: Zehn Einträge, von denen neun
gegen die Wertegrenzen verstoßen, sind ein Eintrag. Sonst könnte eine Nachricht
voller unbrauchbarer Werte einen guten Stand verdrängen.

Der JSON-Vertrag ist entsprechend geändert: Abschnitt 3.1 hat eine vierte
Zeile bekommen, `track.points` ist ausdrücklich ausgenommen (Spurpunkte werden
angehängt, nie ersetzt), und zwei Zeilen der Tabelle „Stand der Durchsetzung“
stehen jetzt auf „durchgesetzt“. Offen bleibt dort nur noch der Zufallsanteil
in der Client-Kennung (P9).

### Entschieden: M5-13 zeigt an, statt zu fragen

Eine Sicherung in ein fremdes Konto einzuspielen ist ein **vorgesehener**
Vorgang — dafür verschlüsselt der Browser die Angaben neu. Eine Rückfrage wäre
eine Warnung vor etwas Erlaubtem und würde nach dem dritten Mal weggeklickt;
danach wirkt sie auch dort nicht mehr, wo sie nötig ist.

Deshalb: Herkunftskonto und Erstellungszeitpunkt stehen nach dem Öffnen der
Datei als Zeile über der Statusanzeige. Die vorhandene Rückfrage bleibt dem
Fall vorbehalten, in dem mitgeführter Chiffretext im Zielkonto **unlesbar**
bliebe — sie nennt jetzt zusätzlich die Herkunftsadresse.

### Entschieden: B7 wandert aus der Tabellenkomponente heraus

Das Konzept sagt, die Maskierung solle „aus der vorhandenen gemeinsamen
Tabellenkomponente bereitgestellt“ werden. Beim Umstellen der Aufrufer zeigte
sich, dass das nicht trägt: `missiontable.js` wird von `suche.php` und
`zeitraum.php` geladen, gebraucht wird die Maskierung aber auf fünf Seiten.
Die drei übrigen hätten die vollständige Tabellenkomponente laden müssen, um an
eine Funktion mit fünf Zeilen zu kommen.

Sie steht deshalb jetzt in `assets/html.js`. `EdMissionTable.escape` und `.esc`
bleiben als Weiterleitung bestehen — vorhandene Aufrufe bleiben gültig, und an
der Aufrufstelle ist zu sehen, dass es nur noch eine Fassung gibt.

**Nicht vereinheitlicht:** `xmlEscape()` in `export.js`. Es erzeugt GPX, also
XML, und benutzt `&apos;` statt `&#39;`. Zwei Aufgaben, die sich ähneln, sind
nicht dieselbe Aufgabe; das steht als Kommentar dabei, damit es nicht beim
nächsten Aufräumen doch zusammengelegt wird.

### Ein Fund, der nicht im Review steht

Beim Umstellen des Exports auf B8: **Der Export ließ unlesbare Angaben
stillschweigend leer.** Wer mit einem nicht passenden Schlüssel exportiert,
bekam eine Datei, deren Patientenspalten leer sind — sie sieht vollständig aus
und ist es nicht. Das ist dieselbe Kette wie bei M6-02, nur einen Schritt
weiter: Dort ging es um die Anzeige, hier um eine Datei, die weitergegeben
wird.

Jetzt kommt vorher eine Rückfrage mit der Zahl der betroffenen Einsätze.

### Nachweis

75 automatische Prüfungen gegen MariaDB 10.11 und echten HTTP-Verkehr, alle
bestanden.

* **M5-12 gemessen, nicht geschätzt:** 43 Einsätze mit je einer
  Reanimationssitzung — alte Fassung 226 Abfragen, neue Fassung 16. Bei drei
  Einsätzen sind es ebenfalls 16; die Zahl hängt nicht mehr am Bestand.
* **M3-15:** Spuren landen beim richtigen Einsatz, in `seq`-Reihenfolge (auch
  bei verdrehter Einfügereihenfolge), ein Ruhesegment ohne Punkte erscheint
  nicht in der Antwort.
* **M4-02:** leere Liste, kürzere Liste, gleich lange Liste (ersetzt), längere
  Liste (ersetzt, mehrfache Phasennummern bleiben erhalten), Liste mit vier von
  fünf unbrauchbaren Einträgen (übergangen, Ursachen in `rejected`), fehlender
  Schlüssel (unverändert, kein `kept_*`), neuer Einsatz mit leerer Liste (kein
  `kept_*`, weil nichts behalten wurde).
* **M5-05 nachgestellt:** Bei erzwungenem Fehler in der Höhenberechnung bleiben
  alle Einsätze gespeichert, `hoehe_fehler` nennt die Zahl. Vorher hätte
  derselbe Fehler die gesamte Wiederherstellung zurückgerollt.
* **M5-04:** `=1+1`, `+49 170`, `@user` und `-abc` bekommen den Apostroph,
  `-5`, `-5.5` und `0` nicht.
* **M2-06:** leere Eingabe, zu kurz, zu lang, Zeichen außerhalb des Alphabets
  — jeweils mit eigener Ursache; Bindestriche und Kleinschreibung stören nicht.

**Nicht ohne Testinstallation prüfbar:** Darstellung, Karten-Popups, Dialoge
und das Zusammenspiel der Skripte im Browser — insbesondere, dass `html.js` auf
allen fünf Seiten vor den abhängigen Skripten geladen wird.

---

## Nachträge aus dem Betrieb (Web 4.5.3)

Zwei Dinge, die beim Durchprüfen von 4.5.1 und 4.5.2 auf der laufenden
Installation auffielen — keine Review-Befunde, sondern Funde aus der Benutzung.

### Teilaspekt zu M1-02: die Sperrmeldung nannte 2 h 15 min

Beobachtet: „Zu viele Anmeldeversuche … frühestens ab 11:32 Uhr", während es
09:17 Uhr war. Vorgesehen sind 15 Minuten.

**Die Sperre selbst war korrekt.** `rate_erlaubt()` vergleicht
`gesperrt_bis > NOW()` — beide Seiten in derselben Zone. Falsch war die
Anzeige: `gesperrt_bis` wurde bis 4.5.1 in der Zone des Datenbankservers
geschrieben (Ortszeit), und `fmt_local()` liest jeden Wert als UTC und rechnet
den Versatz ein zweites Mal drauf.

Nachgemessen:

| | gespeichert | angezeigt | tatsächlich |
|---|---|---|---|
| bis 4.5.1 (Server auf Europe/Berlin) | `09:32:34` | „ab **11:32** Uhr" | 09:17 Uhr |
| ab 4.5.2 (Verbindung auf UTC) | `07:32:34` | „ab **09:32** Uhr" | 09:17 Uhr |

Dieselbe Verschiebung betraf vier weitere Anzeigen: `devices.last_seen` und
`devices.created_at` (Geräte-Reiter und Admin-Nutzerseite), den Hinweis auf
neue Geräte auf der Startseite und `users.created_at` in der Nutzerliste.

**Das ist die Wirkung von M5-09 in der Praxis** — der Befund war als
Einheitlichkeitsproblem beschrieben, war aber ein sichtbarer Fehler.

### Was an M5-09 nachzutragen war

Der Übergang wurde nicht bedacht: Zeilen aus der Zeit vor 4.5.2 tragen noch
Ortszeit, während `NOW()` danach UTC liefert. Sie wirken um den Zonenversatz in
der Zukunft. Eine beim Umstieg laufende Anmeldesperre hielt entsprechend
länger.

**Die Analyse ergab eine wichtige Unterscheidung, die vorher nirgends stand:**

| Typ | Verhalten | Betroffene Spalten | War die Speicherung falsch? |
|---|---|---|---|
| `TIMESTAMP` | MySQL rechnet beim Schreiben in UTC um, beim Lesen zurück | `pair_codes`, `devices.last_seen`/`created_at`, `users.created_at`, `missions.created_at`, `deleted_refs` | **nein**, nur die Anzeige |
| `DATETIME` | speichert unverändert, was dasteht | `rate_limits.fenster_start`/`gesperrt_bis`, `password_resets.expires_at` | **ja** |

Die Einsatzzeiten (`local_to_utc()`) und der Papierkorb (`UTC_TIMESTAMP()`)
sind ebenfalls `DATETIME`, wurden aber nie aus der Sitzungszone gefüllt und
waren nie betroffen.

Es blieben also zwei Stellen. Die Migration `2026_08_13_zeitzonen_umstellung`
räumt `rate_limits` mit der Bedingung `fenster_start > NOW()` — ein
Beobachtungszeitraum kann nicht in der Zukunft beginnen, die Bedingung trifft
also genau die Altzeilen und lässt eine laufende, korrekte Sperre stehen.
`password_resets` bleibt bewusst unberührt: Ein Einladungslink, der jemandem
unter den Händen ungültig wird, wäre der größere Schaden als einer, der ein bis
zwei Stunden zu lange lebt.

Der Zustand heilt sich ohnehin von selbst, sobald der Zonenversatz verstrichen
ist. Wer 4.5.3 später aufspielt, findet nichts mehr vor — das ist der
Normalfall, kein Fehler.

### Die Wartungsseite war nicht erreichbar

`update.php` hatte keinen Menüeintrag und keine Seitenleiste. Die Auskunft aus
M3-05 war damit wertlos: Sie meldet einen dauerhaft scheiternden Aufräumjob auf
einer Seite, die niemand öffnet. Beides ist ergänzt; am Verhalten ändert sich
nichts (A19 gilt unverändert).

---

## P8b — Aufräumen, Bündel 3 und 4 (Web 4.5.2)

Siebzehn Stellen, an denen der Code von einer Regel abwich, die er sonst
überall befolgt. Keine Schemaänderung.

| Befund | Änderung |
|---|---|
| M3-11 | `Cache-Control: no-store` in `json_out()`; Methodenprüfung bei den lesenden Endpunkten |
| M3-13 | Prüfung auf Wahrheitswert → Vergleich auf die leere Zeichenkette |
| M5-06 | Vier Werteinsetzungen ins SQL auf vorbereitete Anweisungen |
| M5-09 | Zeitzone der Verbindung ausdrücklich auf UTC |
| M1-19 | Cookie-Parameter und `use_strict_mode` im Einrichter |
| M1-14 | `config.php` einmal statt zweimal; CR/LF im Empfänger abgewiesen |
| M2-07 | Schlüsseltausch erst nach bestätigtem Erfolg |
| M1-17 | Leeres `<section>` in der Nutzerverwaltung |
| M1-20 | `SELECT *` → benannte Spalten in `auth_guard.php` |
| M2-09 | Kommentarkopf von `patient.js` mit den tatsächlichen sechs Einbindern |
| M2-11 | `DecompressionStream` prüfen wie schon `CompressionStream` |
| M2-12 | Zahlenumwandlung nur bei numerischem Ergebnis |
| M3-14 | Platzhalterbau ohne Formatzeichenkette |
| M4-12 | Weiterleitungsrest mit Ablaufdatum (Web 5.0.0) |
| M5-07 | Spalten der Sicherung aufgezählt |
| M6-10 | Sortierpfeil-Sonderfall abgeschafft |
| D13 | Befüllte, nie gelesene Variable im Import |

### M5-09 ist ein Fehler, kein Schönheitsproblem

Gemessen: Mit `SET time_zone = '+02:00'` laufen `NOW()` und `UTC_TIMESTAMP()`
um den Zonenversatz auseinander. Der Papierkorb schreibt in UTC, Ratenschutz
und Token in der Serverzeit. Auf einem Server mit Ortszone wäre ein
Ratenschutz-Fenster ein bis zwei Stunden zu früh oder zu spät abgelaufen —
und das hing allein an einer Einstellung des Hosters.

Die Verbindung setzt jetzt UTC. Der Unterschied zwischen den beiden Funktionen
im Code bleibt: Er sagt, was gemeint ist, und überlebt damit eine künftige
Änderung dieser Zeile.

### M5-07 hat eine tote Altspalte ans Licht gebracht

`missions.other_resources` wird seit der Migration `2026_07` von niemandem
mehr gefüllt — die weiteren Rettungsmittel liegen als einzelne Zeilen in
`mission_resources`. Die Spalte wurde damals nur nicht gelöscht. Mit `SELECT *`
ging sie trotzdem in jede Sicherung: ein Feld, das seit Monaten leer ist und
beim Einspielen verworfen wird. Sie ist jetzt draußen.

**Vorgefunden und hier nicht geändert:** `site_ele_m` steht in der Sicherung,
kommt beim Einspielen aber nicht zurück. Der Einspielweg schreibt die Spalten
aus `mission_fields.php` plus `pat_blob`; die Einsatzort-Höhe steht dort nicht,
weil sie beim Uhr-Upload gerechnet und nicht eingegeben wird. Das Aufzählen hat
die Asymmetrie sichtbar gemacht — sie zu beheben hieße, den Einspielweg zu
ändern, und das ist ein eigener Vorgang.

### M2-07: warum ein Vormerkfach und kein zweiter Aufruf

Der Wechsel läuft als gewöhnliches Formular mit anschließendem Neuladen. Nach
dem Neuladen kann der Browser den neuen Datenschlüssel nicht erneut ableiten —
das Passwort ist fort. Er legt ihn deshalb vor dem Absenden in ein Vormerkfach
im `sessionStorage` (gleiche Lebensdauer wie der Schlüssel, den es ersetzt) und
löst es nach dem Neuladen auf: bei Erfolg übernehmen, sonst verwerfen.

### M1-17: nur eine kaputte Stelle, nicht zwei

Der Befund nennt zwei. Geprüft wurde die Tag-Bilanz aller dreizehn gerenderten
Seiten samt der Reihenfolge von Fußzeile und Abschluss-Tags; gefunden wurde ein
verwaistes, leeres `<section>` in `admin_users.php`. Die zweite Stelle ist
offenbar in einem früheren Paket beiläufig mit repariert worden.

---

## P8a — Aufräumen, Bündel 1 und 2 (Web 4.5.1)

Fünfzehn Befunde ohne gemeinsames Thema außer diesem: An jeder dieser Stellen
fing die Fehlerbehandlung zu viel oder zu wenig. Keine Schemaänderung.

| Befund | Änderung |
|---|---|
| M3-05 | Aufräumjob: sieben Schritte einzeln abgesichert, Protokollspur, zweite Marke für den letzten **vollständigen** Lauf |
| M1-18 | Einrichtungs-Assistent maskiert an der Ausgabestelle |
| M3-06 | Monat muss 01–12 sein |
| M3-10 | Neun Endpunkte: Kennung statt Ausnahmetext |
| M4-06 | Spurpunkte: nur noch der Schlüsselkonflikt wird übergangen |
| M4-09 | Kopplungscode: neuer Versuch nur bei echter Kollision |
| M5-11 | Existenzprüfung am Zugriff auf die erste Phase |
| M6-08 | Migrationsbericht unterscheidet ausgeführt / bereits erledigt |
| M3-12, M6-09 | Virtuelles Gerät über Kennung **und** Nutzerkennung |
| M4-08 | Gerätekennung 128 statt 32 Bit |
| M5-08 | Endgültiges Löschen prüft wie das Wiederherstellen |
| M3-07 | Halber Zeitraum beim Export wird abgelehnt |
| M2-08 | Nicht greifendes Abschneiden der Schlüsselhülle entfernt |
| M4-11 | **Bereits durch P3 erledigt** — hier nur nachgewiesen (`ist_liste()`) |

### Warum der Aufräumjob der schwerwiegendste dieser Befunde war

Die Marke stand vor der Arbeit, der Fehlerblock war leer. Ein scheiternder
Schritt brach den gemeinsamen Block ab und ließ alle folgenden entfallen — für
diesen Tag, und am nächsten Tag wieder, weil die Ursache dieselbe blieb. Nichts
davon war irgendwo ablesbar.

Die Marke bleibt bewusst **vor** der Arbeit: Sie danach zu setzen hieße, dass
zwei gleichzeitige Anfragen beide aufräumen. Das ist der teurere Fehler. Die
Abschottung der Schritte gegeneinander löst das Problem an der richtigen
Stelle.

### Warum bei den Spurpunkten jetzt abgebrochen wird

Ein verworfener Punkt ist nicht wiederzubeschaffen: Die Fortsetzungsmarke ist
`MAX(seq)+1`, sie springt über die Lücke, und die Uhr sendet den Punkt nie
wieder. Deshalb bricht jeder Fehler außer dem Schlüsselkonflikt den Upload ab
und rollt zurück. Das ist strenger als vorher — ein Upload, der bisher „Erfolg"
meldete und dabei einen Punkt verlor, scheitert jetzt sichtbar.

Punkte, die an der **Werteprüfung** scheitern, bleiben ein anderer Fall: Sie
werden gezählt und in `rejected` benannt (seit Web 4.2.0). Sie erneut zu senden
brächte nichts — sie würden wieder abgelehnt.

### Was bei M3-10 bewusst ausgenommen ist

`install.php` und `update.php` zeigen ihre Ausnahmen weiterhin im Klartext.
Beide laufen nur für Verwaltende, beide in Lagen — Ersteinrichtung, Migration —
in denen der genaue Text die eigentliche Auskunft ist. Bei `install.php` gibt
es zu diesem Zeitpunkt zudem noch kein Fehlerprotokoll, in dem man nachsehen
könnte.

### Ein Fund bei der Prüfung

Ein NULL-Wert in einer Spalte ohne Nullwert-Erlaubnis trägt ebenfalls SQLSTATE
23000, aber Treibercode 1048. Hätte `ist_dublettenfehler()` nur die Klasse
geprüft, wäre bei M4-06 genau dieser Fall als „schon vorhanden" durchgegangen
und der Punkt spurlos verschwunden — dasselbe Verhalten wie vorher, nur mit
mehr Code. Der Fall ist als eigene Prüfung festgehalten.

---

## P6 — Sitzung, Rollen, Konten (Web 4.5.0)

Bis hierher endete eine Sitzung nur durch Abmelden oder Zeitablauf. Weder ein
Rollenentzug noch ein gelöschtes Konto noch ein Passwortwechsel erreichten sie
— alle drei sind aber genau die Handgriffe, mit denen man jemandem den Zugang
nimmt.

| Befund | Änderung |
|---|---|
| M1-05 | Rolle und Existenz des Kontos kommen bei **jeder** Anfrage aus der Datenbank; `$_SESSION['role']` entfällt |
| M1-09 | Passwortwechsel erhöht `session_epoch` und entwertet alle offenen Links zum Zurücksetzen |
| M1-06 | Zurücksetzen-Token wird beim ersten Öffnen aus der Adresszeile in eine eigene Sitzung getauscht |
| M1-10 | Nutzer anlegen: Dublette abgefangen, Konto und Token in einer Transaktion, Mailversand ausgewertet |
| M1-13 | Eine Fassung für Normalisierung und Prüfung von E-Mail-Adressen (`email_lib.php`) |
| M1-15 | Eine Rollenprüfung (`ist_admin()`/`require_admin()`) statt fünf unabhängiger Formulierungen |
| M1-16 | „Adresse bereits verwendet" nur noch beim tatsächlichen Schlüsselkonflikt |

Keine Migration nötig: `session_epoch` (S3) und die Sortierregel der
E-Mail-Spalte (S6) liegen seit P0 im Schema.

### Warum die eigene Sitzung den Passwortwechsel überlebt

Der erhöhte Zähler beendet jede Sitzung des Kontos, die noch den alten Stand
trägt. Die Sitzung, die den Wechsel auslöst, zieht den neuen Stand mit und
bleibt bestehen. Das ist Absicht: Wer sein Passwort wechselt, will die
**anderen** draußen haben, nicht sich selbst. Beim Weg über „Passwort
vergessen" ist ohnehin niemand angemeldet — dort fallen alle Sitzungen.

Sitzungen aus der Zeit vor 4.5.0 führen den Stand noch nicht mit. Sie werden
beim ersten Zugriff übernommen, statt beim Aufspielen alle Angemeldeten
auszusperren.

### Warum der Token-Tausch einen eigenen Cookie braucht

Der Link aus der E-Mail wird im Mailprogramm angeklickt, also von einer
**fremden** Seite aus. Ein Cookie mit `SameSite=Strict` käme bei der
Weiterleitung nicht zurück; die Seite wäre eine Sackgasse. Deshalb `Lax` — und
deshalb ein **eigener Cookie-Name** (`EDPWSESS`): Würde hier der Sitzungscookie
der Anwendung mit `Lax` neu gesetzt, verlöre eine parallel offene, angemeldete
Sitzung im selben Browser ihren `Strict`-Schutz.

Wer Cookies für die Seite blockiert, kommt diesen Weg nicht — das wird benannt
(„Cookie nötig") statt als „Link ungültig" zu erscheinen und die Person einen
zweiten, ebenso wirkungslosen Link anfordern zu lassen. Die Anmeldung selbst
braucht ohnehin einen Cookie.

### Sitzungsende bei Datenabrufen

Endet die Sitzung mitten in einem Abruf unter `server/api/`, antwortet der
Server jetzt mit **401 und JSON** statt mit der HTML-Seite, die die Schlüssel
im Browser räumt. Ein `fetch()`, das JSON erwartet, sah vorher einen
Syntaxfehler beim Auswerten und meldete irgendetwas Allgemeines. Die Schlüssel
räumt die nächste Seitenanfrage, die ohnehin auf der Anmeldeseite landet.

### Bestandsadressen werden nicht angefasst

`email_lib.php` normalisiert beim Schreiben und beim Suchen. Vorhandene Zeilen
bleiben, wie sie sind: Die Spalte trägt seit P0 `utf8mb4_unicode_ci`, der
Vergleich trifft also ohnehin. Eine Datenänderung ohne Wirkung wäre Risiko ohne
Gegenwert.

### Nebenbefund, mit behoben

`login.php` meldete den Erfolg an den Zähler des Salz-Endpunkts mit der Adresse
**wie getippt**, während `auth_salt.php` unter der kleingeschriebenen Fassung
zählt. Wer „Max@…" tippte, leerte seinen Salz-Zähler nie — jede Anmeldung
verbrauchte dort einen Versuch, ohne ihn je zurückzugeben. Mit der
gemeinsamen Normalisierung fällt das weg.

---

## P4 — Ratenschutz und unangemeldete Endpunkte (Web 4.4.0)

Fünf Endpunkte sind ohne Anmeldung erreichbar: Anmeldung, Salz-Abfrage,
Zurücksetzen-Anforderung, Kopplung und Upload der Uhr. An allen fünf ließ sich
etwas ablesen oder etwas beliebig oft wiederholen.

| Befund | Änderung |
|---|---|
| M1-02 | Anmeldung: Sitzungszähler ersetzt durch den Ratenschutz aus P0 |
| M1-08 | Salz-Abfrage und Zurücksetzen-Anforderung mit Ratenschutz; höchstens ein gültiger Token je Konto |
| M1-07 | Antwort wird abgeschlossen, bevor der Mailversand beginnt |
| M4-07 | Unbekannte Gerätekennung prüft gegen einen festen Vergleichswert |
| M4-10 | Fünf Geräte je Konto; E-Mail und Oberflächenhinweis bei neuen Geräten |
| M4-01 | bereits in P1 vollständig erledigt — hier nur nachgewiesen (A14) |

### Warum kein Sitzungszähler

Die alte Bremse zählte in `$_SESSION`. Wer das Cookie wegwarf, hatte wieder
fünf Versuche frei; ein Programm, das gar kein Cookie annimmt, verbrauchte nie
eines. Nachgewiesen: Zwölf Anmeldeversuche ohne Cookie werden jetzt ab dem
elften abgewiesen — vorher liefen alle zwölf durch.

Die Kontosperre als Nebenwirkung ist bewusst hingenommen: Wer eine Adresse
kennt, kann das Konto fünfzehn Minuten lang aussperren. Die Alternative — nur
nach IP zählen — ließe ein über viele Rechner verteiltes Durchprobieren einer
einzelnen Adresse völlig ungebremst.

### Warum keine Warteschlange für den Mailversand

Auf dieser Installation gibt es keinen Cronjob; die Wartung läuft huckepack auf
Anfragen, höchstens einmal täglich. Eine Warteschlange hätte den Link zum
Zurücksetzen genau so lange liegen lassen, bis zufällig jemand eine Seite
aufruft. Stattdessen wird die Antwort abgeschlossen, bevor der Versand beginnt
— über `fastcgi_finish_request()` bzw. `litespeed_finish_request()`, sonst über
Längenangabe und angekündigtes Verbindungsende.

**Der Preis:** Auf Hosts ohne FPM oder LiteSpeed bleibt der PHP-Arbeitsprozess
nach dem Abschluss der Antwort noch bis zum Zeitlimit des Mailversands belegt.
Bei fünf Anforderungen je Stunde und Konto ist das klein, aber nicht null.
Welcher Weg auf der eigenen Installation greift, steht auf der Wartungsseite
unter **Umgebung**.

### Nachweis — 116 Prüfungen gegen MariaDB und echten HTTP-Verkehr

**M1-07, der entscheidende Fall.** Gegen einen Mailserver, der die Verbindung
annimmt und nie antwortet (Zeitlimit 15 s):

| Zweig | vorher zu erwarten | gemessen |
|---|---|---|
| vorhandenes Konto | ~15 s | **0,51 s** |
| unbekannte Adresse | ~0,5 s | **0,51 s** |

Abweichung 0,0 %. Gemessen auf einem Webserver **ohne**
`fastcgi_finish_request` — also über den schwächeren der beiden Wege. Die
Kopplung der Uhr kam unter denselben Bedingungen in 0,07 s zurück.

**M4-07.** Bekannte Kennung mit falschem Schlüssel gegen unbekannte Kennung:
75 ms zu 76 ms, Abweichung 1,1 %. Eine einzelne Passwortprüfung kostet auf
derselben Maschine 113 ms — genau der Betrag, der vorher fehlte.

**M1-02.** Fehlerdauer bei bekannter und unbekannter Adresse: 351 ms zu 351 ms.
Auch das richtige Token wird während einer Sperre abgewiesen (die Sperre greift
vor der Prüfung). Eine erfolgreiche Anmeldung leert die Zähler beider Töpfe.

**M1-08.** Salz-Abfrage: Sperre greift beim 31. Aufruf, Antwortlängen beider
Zweige identisch (43 Zeichen), Dauer 51 ms zu 51 ms. Zurücksetzen: nach
beliebig vielen Anforderungen bleibt genau **ein** gültiger Token; die alte
Fassung hätte drei gehabt. Eine gesperrte Anforderung zeigt dieselbe
Antwortseite und legt nichts an.

**M4-10.** Obergrenze greift auf beiden Wegen. Das virtuelle Gerät
`manual-<konto>` zählt nicht mit. Fünf deaktivierte Geräte sperren das sechste
— Deaktivieren gibt keinen Platz frei, Löschen schon. An der Grenze wird gar
kein Kopplungscode erzeugt; wird trotzdem einer eingelöst, kommt 409
`device_limit` und der Code **bleibt verbraucht** (M4-03 bleibt gewahrt).

### Zwei Prüffälle, die erst falsch waren

Beim Fensterablauf hatte nur die Konto-Zeile zurückdatiert werden sollen —
`rate_erlaubt()` prüft aber beide Merkmale, und die IP trug die Fehlversuche
der vorherigen Fälle. Der Fehlschlag war korrektes Verhalten aus einem anderen
Grund als dem gemessenen.

Ein zweiter Prüffall behauptete „deaktivierte Geräte geben keinen Platz frei",
maß aber nur, dass ein viertes Gerät durchgeht. Neu gefasst: fünf deaktivierte
Geräte sperren das sechste, und die Meldung nennt den Grund.

### Offen aus diesem Paket

Die Uhr zeigt bei 409 „Kopplung fehlgeschlagen (409)", weil `Pair.mc` nur 200
und 404 unterscheidet. Eine eigene Meldung gehört nach P9, wo alle
Uhr-Änderungen liegen.

---

## P5 — Papierkorb und gelöschte Flugtage (Web 4.3.0)

Eine Entscheidung (D1: ablehnen und melden, nicht still wiederherstellen), vier
Stellen.

| Befund | Änderung |
|---|---|
| M3-01 | Tages-Schnittstelle lehnt ab und meldet; auch das Lesen nennt den Zustand |
| M3-16 | Import holt gelöschte Tage nicht mehr zurück, überspringt und zählt |
| M5-10 | Wiedereinspielen lehnt ab und benennt den Fall |
| M4-04 | Sperrliste und Papierkorbprüfung gelten für **beide** Arten |

### Nachweis gegen MariaDB

- Alte Fassung belegt: `ON DUPLICATE KEY UPDATE` ohne Bedingung auf den
  Löschzustand überschreibt den Tag und lässt ihn gelöscht — die Eingabe ist
  weg, die Antwort lautet „ok".
- Neue Vorabprüfung findet den Tag im Papierkorb (1 Treffer) und lehnt ab.
- Import-`UPDATE` mit `deleted_at IS NULL` ändert 0 Zeilen statt den Tag
  zurückzuholen.
- Sperrliste trennt sauber: `r-123` als Ruhesegment gesperrt (1 Treffer), als
  Einsatz nicht (0 Treffer).

### Umsetzungsdetail zu M4-04

Beide Prüfungen (Sperrliste, Papierkorb) sind aus dem Einsatz-Zweig **vor** die
Fallunterscheidung gezogen. Damit ist die Lücke nicht nur geschlossen, sondern
strukturell nicht wiederholbar: Ein künftiger dritter Datensatztyp bekäme sie
automatisch.

---

## P3 — Gemeinsame Prüfschicht anwenden (Web 4.2.0)

Die aufwendigste und wirkungsvollste strukturelle Änderung: Alle vier
Schreibwege rufen jetzt `validate_lib.php` auf.

| Befund | Änderung | Weg |
|---|---|---|
| M5-02 | Wiedereinspielen prüft überhaupt erst — vorher 0 von 9 Prüfungen | Sicherung |
| M4-05 | Koordinatenbereiche und Mengenbegrenzungen ergänzt | Uhr |
| M3-04 | Eine Grenze für den Patientenblock (40…60000) statt dreier | alle |
| M2-04 | Musterverletzung wird gemeldet statt übergangen | Formular |
| M3-02 | Kalendertagsprüfung angewendet | alle |
| M6-04 | Kalendertagsprüfung bei der Flugtag-Anlage | Formular |
| M7-02 | Entdoppelung der Phasen entfernt, Mengengrenze stattdessen | Import |
| M3-03 | Zugehörigkeit direkt abfragen statt aus der Zeilenzahl erschließen | Import |
| M5-14 | Übersprungene Datensätze nach Ursache aufschlüsseln | Import, Sicherung |

### Zwei Grundsätze, die die Umsetzung geprägt haben

**Ein schlechter Wert verwirft den Wert, nicht den Vorgang.** Auf dem Uhr-Weg,
weil die Uhr nichts nachliefern kann, was sie gelöscht hat — ein Abbruch wegen
einer krummen Koordinate könnte einen ganzen Einsatz kosten. Beim
Wiedereinspielen, weil wer eine Wiederherstellung startet, meist keinen zweiten
Versuch hat.

**Was verworfen wird, wird genannt.** Sonst wäre die Prüfung nur eine leisere
Art des Datenverlusts. Uhr: `rejected` in der Antwort. Import und
Wiedereinspielen: Aufschlüsselung nach Ursache in der Meldung.

### Ein Fehler aus P0, den erst dieser Einsatz gezeigt hat

Die Meldung zu Koordinaten lautete „außerhalb von ±9" statt „±90" — die
Formatierung schnitt nachlaufende Nullen ab. Aufgefallen an der Testausgabe,
nicht beim Lesen. Behoben.

### Nachweis

Eine bösartige Nutzlast mit je einem Fehler pro Kategorie gegen die Prüfschicht:
unmöglicher Kalendertag verworfen, gültiger Zeitstempel erhalten, zu kurzer
Chiffretext verworfen, `"viel"` wird NULL statt 0, Phase 10 verworfen,
**drei Einträge derselben Phase 5 bleiben erhalten**, Koordinate 91 verworfen
ohne die Phase zu verlieren, unbekannte Reanimationsart verworfen — und jede
Ursache einzeln benannt.

Zu M3-03 gegen echte Datenbank: Eine Aktualisierung mit unveränderten
Kopfdaten liefert nachweislich 0 geänderte Zeilen; die neue Abfrage auf
Kennung, Nutzerkennung und Löschzustand liefert dagegen korrekt 1.

Zusätzlich 9 Fälle für die neue Funktion `pruef_utc_oder_sql`, die beide
Zeitformate annimmt (Uhr mit `Z`, Sicherung in Datenbankschreibweise).

### Der Vertrag ist ehrlicher geworden

Drei Zeilen der Tabelle „Stand der Durchsetzung" im JSON-Vertrag stehen jetzt
auf „durchgesetzt": Kalendertag, Wertebereiche und Mengen, Antwortfeld
`rejected`. Offen blieben damals das Verhalten bei leeren Listen samt `kept_*`
(mit P8c erledigt) und der Zufallsanteil in der Client-Kennung (P9).

---

## P2 — Die Kette „unlesbarer Schlüssel" schließen (Web 4.1.2)

| Befund | Änderung | Baustein |
|---|---|---|
| M1-12 | Prüfsumme beim Neuverpacken prüfen — passt sie nicht, wird nichts geändert | B4, S2 |
| M2-05 | Zwischengespeicherter Schlüssel an seine Hülle gebunden, läuft mit der Sitzung ab | B5 |
| M6-02 | Unlesbare Datensätze mit ⚠ statt – , Hinweis über der Liste | B8 |
| M1-03 | Sitzungsablauf räumt die Schlüssel im Browser | B6 |
| M1-04 | Grund des Sitzungsendes wird angezeigt | B6 |
| M5-01 (2+3) | Sicherung trägt ihr Herkunftskonto, Einspielen entscheidet und fragt | B4 |

### Ein Fehler, den erst der Test gezeigt hat

Der in P0 angelegte Baustein B5 räumte beim Verwerfen eines nicht passenden
Schlüssels **auch den Datenschlüssel** mit weg. Der wird aber unmittelbar
danach gebraucht, um die Hülle neu zu entpacken. Die Folge wäre nicht ein
neuer Entsperrvorgang gewesen, sondern **gar kein Schlüssel** — also genau der
Zustand, den der Baustein verhindern soll, und noch dazu nur beim Kontowechsel,
dem seltensten Fall.

Aufgefallen ist das an zwei fehlgeschlagenen Prüfungen, nicht beim Lesen des
Codes. Behoben: `verwerfeInhalt()` räumt nur den Inhaltsschlüssel samt Bindung,
`beenden()` räumt beides.

### Entschieden: Der Sicherungslauf bricht NICHT ab, wenn alles unlesbar ist

Das Konzept stellt es zur Erwägung. Dagegen spricht seit Web 4.1.0 ein starkes
Argument: Die Sicherung **nimmt den Chiffretext jetzt mit**. Sie ist damit
genau das Richtige, was jemand mit einem nicht passenden Schlüssel tun sollte —
sie bewahrt die Daten, statt sie zu verlieren. Ein Abbruch nähme ihm das
einzige Mittel aus der Hand, das hilft.

Stattdessen: deutliche Meldung mit Zahl und Ursache (seit Web 4.1.0) und der
Hinweis, vor weiteren Schritten den Wiederherstellungsschlüssel bereitzuhalten.

### Nachweis

- **Kontowechsel im selben Tab:** Konto B bekommt seinen eigenen Schlüssel; die
  ungeprüfte Fassung lieferte im Gegentest nachweislich den fremden.
- **Ablauf:** Ein Schlüssel jenseits der 30-Minuten-Frist wird verworfen und
  neu entpackt.
- **M1-12 gegen echte Datenbank:** Bestandskonto ohne Prüfsumme wird angenommen
  und bekommt sie; richtige Prüfsumme angenommen; falsche, fehlende und
  unbrauchbare abgelehnt, ohne etwas zu ändern.
- **M6-02:** Die drei Zustände (`ok`, `leer`, `unlesbar`) werden getrennt
  gezählt, `_patFehler` steht nur am unlesbaren Datensatz.

---

## P7 — Dokumentation und Verträge (Web 4.1.1)

Das einzige Paket, das fast keinen Code berührt — und trotzdem nicht ans Ende
gehört: Eine falsche Zusicherung im Vertrag richtet Schaden an, solange sie
dort steht.

| Befund | Änderung |
|---|---|
| M7-01 | Phase 10 aus dem JSON-Vertrag entfernt (drei Stellen), Abschluss über `final` + `ended_at` beschrieben |
| M3-09 | Beschriftung für Phase 10 aus `PHASE_LABELS` entfernt |
| M7-05 | Reanimationsarten: der Vertrag ist die führende Liste |
| M7-06 | Format und Präfixe der Client-Kennung festgelegt, samt daran hängendem Verhalten |
| M4-02 | Fehlende gegen leere Liste ausdrücklich geregelt |
| M6-11 | Festgehalten, welche Felder im verschlüsselten Block liegen und welche nicht |
| M7-04 | Zwei Zusicherungen richtiggestellt (Kopplungscode-Kommentar in `schema.sql`, Rettungsseiten-Verweis in `update.php`) |
| M7-09 | Gerätebezug in `Uhr-Layout.md` ergänzt, Prüfgeräte und Konvention benannt |
| M5-17 | Aufbewahrungsfrist der Sperrliste (90 Tage) im Handbuch genannt |
| — | Grenzen und Mengen aus B1 in den Vertrag übernommen |

### Der neue Abschnitt „Stand der Durchsetzung"

Der Vertrag beschreibt jetzt Regeln, die der Server **noch nicht** durchsetzt —
Kalendertagsprüfung, Wertebereiche und Mengen auf dem Uhr-Weg (P3), das
Verhalten bei leeren Listen und die Antwortfelder `kept_*`/`rejected` (P8), der
Zufallsanteil in der Client-Kennung (Uhr-App). Bis auf die letzte Zeile sind
alle inzwischen eingelöst.

Das ist genau der Fehler, den M7-04 rügt — außer man legt ihn offen. Deshalb
steht in Abschnitt 0 des Vertrags eine Tabelle, die je Regel sagt, ob sie
durchgesetzt ist. Sie verschwindet, wenn alle Zeilen „durchgesetzt" lauten.

Die Alternative wäre gewesen, den Vertrag erst nach P3 und P8 zu ergänzen.
Dagegen spricht die Reihenfolge des Konzepts: P7 steht bewusst vorn, weil die
falschen Angaben *jetzt* Schaden anrichten und nicht erst am Ende.

### Bewusst nicht geändert

- **Das Label der Migration `2026_07_20_kopplung`** („5 Zeichen, 60 Minuten
  gültig") beschreibt korrekt, was diese Migration damals tat. Ein
  Migrationslabel ist ein historischer Eintrag; es nachträglich umzuschreiben
  wäre dasselbe wie das Umschreiben eines Changelog-Eintrags.
- **`docs/archiv/Anforderungen_v1.2.md`** nennt weiterhin Phase 10 und den
  Bereich 2–10. Das Archiv gibt den Stand von damals wieder und wird nicht
  fortgeschrieben.

---

## P1 — Sofortmaßnahmen (Web 4.1.0)

Sieben Änderungen, die drei der vier Befundketten an je einer Stelle
unterbrechen. Wenn nur ein Paket umgesetzt würde, dann dieses.

| Befund | Änderung | Datei |
|---|---|---|
| M1-01 | Pseudo-Salt von 64 auf 32 Zeichen — die Antwortlänge verriet, ob ein Konto existiert | `auth_salt.php` |
| M4-01 | Kopplungscode 6 Zeichen, 10 Minuten, Ratenschutz, Prüfmuster auf das tatsächliche Alphabet, höchstens ein offener Code je Konto | `pair.php`, `einstellungen.php`, `db.php` |
| M4-03 | Entwerten vor dem Prüfen — der Code war nicht wirklich einmalig | `pair.php` |
| M5-01 (1) | Chiffretext bei fehlgeschlagener Entschlüsselung behalten (Formatänderung S8) | `einstellungen.php` |
| M5-03 | Antwortstatus prüfen, bevor eine Sicherungsdatei entsteht | `einstellungen.php` |
| M6-01 (1) | Wartungsseite zweistufig, Sicherungsrat vor den Lauf | `update.php` |
| M2-02 (3) | Passwortstärke = Verschlüsselungsstärke, in Worten | `pw_handling.php` |

### Zahlen zur Kopplung

| | vorher | jetzt |
|---|---|---|
| Coderaum | 5 Zeichen aus 32 = **25 Bit** (33,5 Mio.) | 6 Zeichen aus 32 = **30 Bit** (1,07 Mrd.) |
| Gültigkeit | 60 Minuten | 10 Minuten |
| Bremse | 0,3 s je Anfrage, **nicht parallelisierungsfest** | Ratenschutz je IP, greift vor jeder Arbeit |
| voller Durchlauf | **1,4 Stunden** bei 2000 parallelen Anfragen | praktisch unerreichbar |
| Prüfmuster | `[A-Z0-9]{4,8}` — ließ 0/O/1/I zu, die es nie gibt | genau das Alphabet, genau 6 Zeichen |

### Nachweis

Gegen MariaDB 10.11 geprüft: Zwei gleichzeitige Einlösungen desselben Codes —
die erste ändert eine Zeile und gewinnt, die zweite ändert null und wird
abgewiesen. Ein 30 Minuten alter Code wird abgelehnt (vorher wäre er noch
gültig gewesen). Ein neuer Code entwertet den alten.

Zur Wartungsseite: Bei ausstehender Migration ändert der Aufruf nachweislich
nichts — `schema_migrations` bleibt bei 25 Einträgen, die Spalte entsteht
nicht. Erst der Knopf legt sie an und verbucht die Migration.

Zur Sicherung: Ein Einsatz mit fremdem Schlüssel behält seinen Chiffretext und
ist nach dem Zurückspielen ins Ursprungskonto wieder lesbar; die alte
Reihenfolge (Entfernen hinter dem Fehlerblock) verlor ihn nachweislich.

### Was in diesem Paket bewusst offen bleibt

* **M5-01 Teile 2 und 3** (Meldung getrennt zählen, Chiffretext beim
  Einspielen über die Prüfsumme dem Konto zuordnen) gehören zu P2. Der
  mitgeführte Chiffretext wird beim Einspielen heute schon unverändert
  übernommen — in ein *fremdes* Konto gespielt bleibt er unlesbar, ohne
  Hinweis. Das ist gegenüber dem bisherigen Zustand (Daten weg) die bessere
  Richtung, aber noch nicht der Zielzustand.
* **M4-01 Ratenschutz beim Koppeln** ist hier vollständig; die übrigen
  Endpunkte (Anmeldung, Salt, Zurücksetzen) folgen in P4.
* **M6-01 Teil 2** (Inhaltsprüfung destruktiver Migrationen) gehört zu P9.
* **M2-02 Teile 1 und 2** (Prüfung im Skript, Stärkeanzeige) gehören zu P9;
  der Baustein dafür liegt seit P0 bereit.

---

## P0 — Gemeinsame Bausteine und Migration (Web 4.0.0)

**Grundsatz dieser Auslieferung: anlegen, noch nicht benutzen.** Die Bausteine
existieren und sind einsatzbereit; das Verhalten der Anwendung ändert sich
nicht. Einzige Ausnahme ist der Ratenschutz, der ab P1 gebraucht wird.

### Bausteine

| | Datei | Behandelt |
|---|---|---|
| B1 | `server/validate_lib.php` | M2-04, M3-04, M4-05, M5-02, M3-02, D11 |
| B2 | `server/validate_lib.php` (`pruef_kalendertag`) | M3-02, M6-04 |
| B3 | `server/ratelimit_lib.php` | M1-02, M1-08, M4-01, M4-10 |
| B4 | `server/assets/crypto.js` (`contentKeyCheck`) | M1-12, M2-16, M2-05, M5-01 |
| B5 | `server/assets/keyguard.js` | M2-05, M1-03 |
| B6 | `server/session_lib.php` | M1-03, M1-04 |
| B7 | `server/assets/missiontable.js` (`escape`) | M6-03, M6-05 |
| B8 | `server/assets/patient.js` (`entschluessleListe`) | M6-02, M6-06 |
| B9 | `server/assets/pwquality.js` | M2-02, M2-03 |

### Schema

| | Änderung | Benutzt ab |
|---|---|---|
| S1 | `users.kdf_iter`, Bestand auf 310000 | P9b (M2-01) — seit Web 5.0.0 gelesen |
| S2 | `users.pat_key_check`, Bestand bleibt leer | P2 (M1-12) |
| S3 | `users.session_epoch`, Vorgabe 0 | **P6** (M1-09) |
| S4 | Tabelle `rate_limits` | **P1** (M4-01) |
| S5 | `deleted_refs.owner_type`, Schlüssel erweitert | P5 (M4-04) |
| S6 | Sortierregel `users.email` festgelegt | **P6** (M1-13) |
| S9 | Ratenschutz-Tabelle im Aufräumjob (Teil) | sofort |

S7 und S8 (Sicherungsformat) sind Formatänderungen ohne Schemaanteil; sie
entstehen dort, wo sie gebraucht werden — S8 in P1/P2, S7 in P9.

### Zur Rundenzahl (S1)

Die heikelste Änderung der gesamten Umsetzung: Ein Fehler an der
Schlüsselableitung sperrt nicht ein Konto aus, sondern **alle gleichzeitig**.
Sie ist deshalb auf zwei Auslieferungen verteilt.

```
P0   Schritt 1  Spalte anlegen, Bestand auf den heutigen Wert setzen.
                Kein Code liest sie. Der Salt-Endpunkt bleibt unverändert.
P9   Schritt 2  Salt-Endpunkt liefert die Rundenzahl mit.
     Schritt 3  Browser rechnet mit dem gelieferten Wert.
     Schritt 4  Stille Anhebung bei der nächsten Anmeldung.
```

Ab Schritt 2 gilt: Für unbekannte Adressen muss **dieselbe** Rundenzahl
genannt werden wie für echte Konten — sonst wird die in P1 geschlossene
Auskunftslücke (M1-01) an neuer Stelle wieder geöffnet. In P1 besteht diese
Wechselwirkung noch nicht; dort ist M1-01 eine reine Längenkorrektur.

Der Salt-Endpunkt wird insgesamt in drei Paketen angefasst: P1 (Länge des
Pseudo-Salts), P4 (Ratenschutz), P9 (Rundenzahl).

### Prüfung

Nachgewiesen gegen eine echte MariaDB 10.11 mit Altbestand:

* Migration läuft fehlerfrei; `kdf_iter` = 310000, `pat_key_check` = NULL,
  `session_epoch` = 0, vorhandene Sperrlisteneinträge erhalten
  `owner_type = 'mission'` ohne Datenverlust.
* Neuinstallation über `schema.sql` legt alles an und verbucht die Migration
  als „nicht nötig".
* Ratenschutz: nach 10 Fehlversuchen gesperrt; nach Ablauf des Zeitfensters
  Zähler zurück auf 1 und Sperre aufgehoben.
* Aufräumjob entfernt abgelaufene Zähler und lässt aktive Sperren stehen.

Prüfschicht und Browser-Bausteine über 40 Einzelfälle, darunter: 30. Februar
wird abgelehnt statt auf den 2. März verschoben, 29.02.2024 bleibt gültig,
Patientenblock unter 40 Zeichen abgelehnt, Phase 10 abgelehnt, „1234567890"
und „Passwort123!" als Passwort abgelehnt, unlesbare Datensätze getrennt von
leeren gezählt.

### Noch nicht Teil dieser Auslieferung

Der Aufräumjob wurde nur um die neue Tabelle ergänzt. Der eigentliche Umbau
(Schritte gegeneinander abschotten, Fehler protokollieren, zweiter
Zustandsschlüssel für den letzten erfolgreichen Lauf) gehört zu M3-05 und
folgt in P8.

Die Migration läuft noch über den bisherigen Ablauf der Wartungsseite, deren
Umbau (M6-01) erst in P1 erfolgt. Das ist vertretbar, weil der Aufruf hier
eine bewusste Handlung des Betreibers ist.
