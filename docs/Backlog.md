# Einsatzdoku — Backlog

Bewusst offene Punkte. 

**Nummern sind dauerhaft.** Verweise aus Code und Dokumentation nennen sie
(z. B. „Backlog Nr. 10"). Erledigte Punkte werden deshalb nicht gelöscht,
sondern nach unten in den Abschnitt *Erledigt* verschoben und behalten ihre
Nummer. Neue Punkte hängen sich hinten an.

**Zu den fehlenden Nummern 4, 6 und 7.** Sie waren vergeben und sind ohne
Eintrag verschwunden; ihr Inhalt ist nicht mehr rekonstruierbar. Sie bleiben
deshalb dauerhaft frei — weder werden sie neu vergeben noch nachgetragen. Diese
Notiz steht hier, damit die Frage nicht bei jedem Durchsehen erneut aufkommt.

**Zu den Nummern 1, 9, 10 und 12.** Sie fehlten ebenfalls, waren aber
rekonstruierbar: Code und Changelog verweisen an neun Stellen namentlich auf
sie („Backlog Nr. 10"), und aus diesen Fundstellen geht eindeutig hervor,
worum es ging und womit es erledigt wurde. Die vier Einträge unten sind aus
genau diesen Fundstellen wiederhergestellt (Web 7.2.0, Paket P0/N6) und als
solche gekennzeichnet. Sie stehen unter *Erledigt*, weil alle vier es sind.

---

## Offen

2. Serverseitige Track-Vereinfachung (Douglas-Peucker) für die Web-Darstellung
3. GPX-Export (Datenmodell dafür vorbereitet: lat/lon/ele/ts je `seq`)
8. Content-Security-Policy als zusätzliche Verteidigungslinie.
   Seit Web 5.2.0 eng fassbar: Es wird keine fremde Quelle mehr geladen
   (Nr. 12), die Regel muss also nichts von außen erlauben.
11. **Sync-Seite meldet „Sync vollständig", obwohl die Uhr gar nicht senden
    kann.** Beobachtet ohne hinterlegte Server-Adresse: Die Seite zeigt
    gleichzeitig das grüne „Sync vollständig" mit Haken **und** unten den
    gelben Hinweis „Erst Server-Adresse setzen". Dasselbe tritt auf, wenn die
    Adresse gesetzt, das Gerät aber noch nicht gekoppelt ist.
    Ursache: `SyncView.onUpdate` wertet zwei voneinander unabhängige Größen
    aus und stellt sie unverbunden nebeneinander. `Model.backlogCount()`
    beantwortet ausschließlich die Frage „liegen abgeschlossene Pakete zum
    Senden bereit?" — vor dem ersten Dienst ist das zu Recht `0`. Daraus wird
    im Text aber „vollständig" und damit eine Aussage über den Übertragungsweg,
    den die Uhr zu diesem Zeitpunkt nie benutzt hat. `Uploader.lastError`
    bleibt dabei `null`, weil `SyncView.refresh()` `syncAll()` nur bei
    vorhandenem Rückstand anstößt — es gibt also nicht einmal eine Fehlerzeile,
    die den Widerspruch auflösen würde.
    Reine Anzeigefrage, kein Datenverlust: Wird ohne Einrichtung dokumentiert,
    puffert die Uhr korrekt und der Rückstand erscheint.
    Richtung der Auflösung: Der grüne Zustand setzt zusätzlich
    `Uploader.hasServer()` **und** `hasCredentials()` voraus. Fehlt eines von
    beidem, tritt an seine Stelle ein neutraler Einrichtungs-Zustand, und der
    heute unten stehende gelbe Hinweis wird zur Hauptaussage der Seite statt
    zur Fußnote. Betrifft nur `watch/source/SyncView.mc`; die Reihenfolge der
    Einrichtungsschritte (erst Adresse, dann Kopplung) ist dort bereits
    abgebildet und bleibt.
13. **Kosmetik Uhr-Code: Typprüfer-Warnungen („container access") auflösen.**
    Stand bis Web 5.4.0 irrtümlich als zweite Nummer 5 in dieser Liste — die
    5 gehört dem Geräte-Limit (siehe *Erledigt*). Inhalt unverändert, nur die
    Nummer ist neu vergeben; ältere Verweise auf „Nr. 5b" meinen diesen Punkt.
14. **Kopplungsablauf der Uhr: bestehende Kopplung vor einer Neukopplung
    abfragen und trennen.** Fall: eine geteilt genutzte Uhr. Wird sie neu
    gekoppelt und schlägt der Vorgang fehl, dokumentiert sie stillschweigend
    weiter auf das vorherige Konto. Gewünscht ist die ausdrückliche Reihenfolge
    abfragen → trennen → neu koppeln. Betrifft `watch/source/Pair.mc` und
    `server/pair.php`.
17. **`ingest.php` hat als einziger anmeldungsfreier Endpunkt keine
    Mengenbremse.** `RATE_GRENZEN` (`ratelimit_lib.php`) kennt keinen Topf
    `ingest`, und die Datei ruft weder `rate_erlaubt()` noch
    `rate_misserfolg()`. Die drei übrigen offenen Endpunkte (`login`, `salt`,
    `reset`, `pair`) haben ihn. Gefunden in P0/A6 (dort F-16); die
    Konzeptarbeit dazu ist an **Phase P5** übergeben (Rahmenplan R19), weil
    die richtige Grenze von der Uhr-Seite her zu bestimmen ist — eine Uhr, die
    einen Tag Rückstand nachliefert, darf nicht ausgesperrt werden. **P1 misst
    nur das Aufrufverhalten** und legt keine Grenze fest; die frühere Zuordnung
    „an P1/P2 übergeben" war überholt und ist mit Web 7.2.1 berichtigt.
    **Stand nach P1:** Die Messgrundlage liegt jetzt vor. Der Referenzlauf hat
    das Sendeverhalten der Uhr über 16 Diensttage nachgestellt und protokolliert
    (`tools/referenzdatensatz/einspielen/messprotokoll.md`): Spitze **14
    Anfragen an einem Auslöser**, **174 Abstände von 0 Sekunden**, Median
    1 020 s. Eine Grenze muss also den Stoß zulassen und über die Zeit deckeln —
    ein fester Abstand je Anfrage wäre falsch. Das Demo-Konto ist mit
    abgedeckt, sobald der Topf existiert (E-P1-09 führt es als benanntes
    Restrisiko).
18. **`.btn-link.danger` in `style.css` kann nie greifen.** `btn-link` kommt im
    ganzen Projekt nur in `install.php` vor, und diese Seite lädt `style.css`
    gar nicht — sie bringt ihre Gestaltung im Kopf mit (`'stil' => false`).
    Gefunden in P0/N2 (dort F-19) als Nachlese zu einem Blindfleck der
    A4-Erhebung, die eine Klasse schon dann als benutzt zählte, wenn ihr Name
    irgendwo auftauchte. **Nicht entfernt**, weil nicht auf der Freigabeliste
    von A4. Zu entscheiden: streichen, oder die Regel in den Kopf von
    `install.php` ziehen, wo sie wirken würde.
19. **`$title` in `einsatz_loeschen.php` wird nie gelesen.** Die Variable wird
    gesetzt, der Titel steht daneben als Literal. Gefunden in P0 (dort F-06).
    Einzeiler, aber bewusst nicht nebenbei erledigt: Er stand nicht auf der
    Freigabeliste.
20. **13 Hexwerte in `style.css` durch das vorhandene Token ersetzen.** Von 93
    Hex-Farben stehen 78 außerhalb von `:root` (nachgezählt Web 7.2.0); für 13
    davon gibt es bereits ein Token mit **exakt demselben** Wert — sie ließen
    sich ohne jede Gestaltungsfrage ersetzen. Die übrigen 65 zu benennen ist
    dagegen eine Gestaltungsentscheidung. Gefunden in P0/A6 (dort C6).
    Ausdrücklich dem Oberflächen-Redesign (P3) zugeordnet, weil die Palette
    dort ohnehin angefasst wird; die Einzelwerte stehen in `docs/Branding.md`,
    Abschnitt 5 (B3).
21. **Die 43 weiteren Funde der A4-Nachlese sichten.** Die Erhebung „toter
    Code" in P0/A4 hat mit einer zweiten, breiteren Methode 43 zusätzliche
    Kandidaten geliefert (Abschnitt 9.3 des P0-Konzepts). Sie sind **nicht**
    freigegeben und **nicht** angefasst: Ein großer Teil berührt
    Antwortverträge (`api/`, Export, Backup), und dort ist „niemand liest es"
    keine hinreichende Begründung — ein Feld kann für eine ältere Sicherung
    oder eine künftige Uhr-Fassung gebraucht werden. Eigenes Paket mit eigener
    Freigabe.
23. **`docs/JSON-Vertrag.md` 3.3 nennt eine Reanimationsart, die kein
    Schreibweg annimmt.** Der Vertrag führt `beginn` unter den gültigen Werten
    von `events[].type`; `ingest.php:299` speichert das Ereignis **still
    nicht**, `einsatz_form.php:317` weist es ab. Beide begründen es gleich und
    richtig: Der Reanimationsbeginn steckt in `started_at` der Sitzung. Der
    Vertrag sagt von sich, eine Abweichung sei „ein Fehler in der Umsetzung,
    nicht im Vertrag" — hier spricht die Sache für den Code. **Vorschlag:
    Vertrag berichtigen** (3.3 nennt neun Ereignisarten, `beginn` steht als
    Sitzungsbeginn daneben). Gefunden in P1/B3 (dort F-P1-F); bewusst nicht
    nebenbei geändert, weil der Vertrag die führende Quelle ist und eine
    Änderung an ihm eine Entscheidung wäre, keine Korrektur.
24. **`export_csv_v1` ist bei führendem `=` nicht verlustfrei — und
    `Export-Format.md` 5.1 sagt, es sei es.** Der Export neutralisiert
    Formel-Anfangszeichen (`=`, `+`, `-`, `@`) mit einem vorangestellten `'`;
    der Rückweg entfernt es nicht wieder. Eine Zelle, die ohne diesen Schutz
    mit `=` beginnt, liest SheetJS als Formel — der Wert kommt **leer** an.
    5.1 zählt drei bewusste Ausnahmen auf; dies ist eine vierte,
    undokumentierte. **Vorschlag: Ausnahme dokumentieren**, nicht den Import
    ändern — ein Import, der Zeichen entfernt, schafft den nächsten stillen
    Verlust (ein echtes `'` am Textanfang verschwände). Gefunden in P1/B4
    (dort F-P1-G).
25. **`missions.created_at` wird gesichert, kommt beim Einspielen aber nicht
    zurück.** Die Spalte steht in der Sicherung (`backup_lib.php`,
    Spaltenliste der Einsätze); die Einspielroutine schreibt die Felder aus
    `mission_fields.php` plus `pat_blob` und `start_src` — `created_at` steht
    dort nicht. Nach einer Wiederherstellung tragen alle Einsätze den
    Zeitpunkt des Einspielens. Gemessen am Referenzdatensatz: 79 verschiedene
    Werte vorher, 5 danach. Folgenlos für die Dokumentation (`started_at` ist
    die fachliche Zeit), aber eine Asymmetrie, die bis Web 7.2.3 nirgends
    stand. **Zu entscheiden:** mitschreiben (dann ist es keine Ausnahme mehr)
    oder aus der Sicherung streichen. In Web 7.2.3 wurde nur die Beschreibung
    nachgezogen. Gefunden in P1/B5.
27. **Mehrzeilige Notizen verlieren beim CSV-Import ihre Zeilenumbrüche.** Der
    Parser `trim` (`assets/import.js`) ersetzt jede Folge von Leerraum durch
    ein Leerzeichen, Umbrüche eingeschlossen, und wird auf alle Textspalten
    angewandt. `notes` ist das einzige mehrzeilige Feld. Der Export quotet die
    Umbrüche korrekt; der Verlust entsteht beim Lesen. Gemessen: **4** Notizen,
    je genau ein Umbruch, Zeichenzahl unverändert (164/253/119/150).
    **Vorschlag:** ein Parser `trimMehrzeilig` für die Notizspalten. Gefunden
    in P1/B5 (dort F-P1-L).
    *Zur Zahl:* Bis Web 7.3.0 waren es 3. Der vierte Fall hing an einem
    Einsatz, den Nr. 26 aus dem Vergleich gehoben hatte — ein Fehler hatte die
    Messung eines zweiten verdeckt. Sichtbar wurde er erst, als Nr. 26 behoben
    war.
28. **`final = 0` und ein leeres `ende` werden beim CSV-Import
    überschrieben.** `final` steht als Literal `1` im INSERT von
    `api/import_commit.php`; ein leeres `ende` wird auf `started_at` gesetzt.
    Anders als bei `manual` und `herkunft` sind das keine Aussagen über die
    Entstehung, sondern über den Zustand — und die Datei widerspricht ihnen
    ausdrücklich. Ein nicht abgeschlossener Einsatz gilt nach dem Umlauf als
    abgeschlossen und bekommt eine erfundene Endzeit. Gemessen: je 1x.
    **Vorschlag:** `final` aus der Datei übernehmen, wenn das Profil die
    Spalte führt; beim `ende` „Spalte fehlt" von „Zelle leer" unterscheiden.
    Gefunden in P1/B5 (dort F-P1-M).
29. **`docs/Export-Format.md` 5.1 zählt drei Ausnahmen auf; es sind mehr.**
    Nicht genannt sind: Ruhesegmente kommen nicht zurück (gemessen 95 → 0, es
    gibt keinen Importweg für sie), und der zweite Dienst eines Kalendertags
    geht verloren (gemessen 15 → 13 Diensttage) — `gruppiere()` bündelt nach
    Kalendertag, obwohl seit E9 zwei Dienste an einem Datum zulässig sind und
    die Datei mit `diensttag_id` den unterscheidenden Schlüssel mitführt.
    Dazu der Formelschutz-Apostroph (Nr. 24). **Vorschlag:** Abschnitt 5.1
    ergänzen. Gefunden in P1/B5 (dort F-P1-N).
30. **Den Papierkorb in die Sicherung aufnehmen — NutzerInnen- und
    Admin-Sicherung.** Heute steht er in keiner: `edbak_build()` filtert
    `deleted_at IS NULL`, eine Wiederherstellung leert ihn endgültig (gemessen
    am Referenzdatensatz: 5 Einsätze, 5 Ruhesegmente, 1 Diensttag → nichts).
    **Entschieden ist, dass er mitsoll**; offen ist die Umsetzung, und sie ist
    nicht klein: Der Rückspielweg wertet `deleted_at` und `deleted_with_day`
    nicht aus (ein Abschalten des Filters allein brächte den Papierkorb als
    aktiven Bestand zurück), die D1-Regel muss „Tag im Zielkonto im
    Papierkorb" von „Tag in der Datei im Papierkorb" unterscheiden, und die
    30-Tage-Frist braucht eine Entscheidung. Ausgearbeitet als Paketvorschlag
    in `tools/referenzdatensatz/Konzept-P1.md`, Abschnitt 10 — von dort in
    den Gesamtplan. Als Nebenwirkung entfällt der Papierkorb-Teil des
    Demo-Nachlaufs (E-P1-21).

---

## Erledigt

Die Nummern bleiben, damit ältere Verweise aus Code und Dokumentation weiter
zutreffen.

1. **Reanimationen im Einsatzformular erfassen.**
    *Erledigt mit Web 5.5.0.* Bis dahin konnten Reanimationen nur von der Uhr
    kommen; ein nachgetragener oder von Hand bearbeiteter Einsatz hatte keine.
    Das Formular führt sie jetzt in derselben Struktur wie die Uhr — je
    Reanimation ein Beginn und beliebig viele Ereignisse — und schreibt in
    dieselben Tabellen über denselben Weg. In der Einsatzansicht sind die so
    eingetragenen Zeiten von denen der Uhr nicht zu unterscheiden. Ein über das
    Formular gespeicherter Einsatz trägt `manual = 1`; eine nachliefernde Uhr
    überschreibt die Eingaben also nicht.
    *(Eintrag rekonstruiert in Web 7.2.0 — s. Kopfnotiz. Fundstellen:
    `server/einsatz_form.php:249`, Changelog Web 5.5.0.)*

9. **`asset()` hängt die globale Versionsnummer an jede Datei-Adresse.**
    *Erledigt mit Web 5.4.0.* Folge war, dass jede Versionserhöhung den
    Zwischenspeicher **aller** Dateien entwertete — auch derer, die sich nicht
    geändert hatten. Bei einer Korrekturfassung, die eine einzige Zeile im
    Stylesheet anfasst, luden Besucher trotzdem sämtliche Skripte erneut.
    Jetzt steht dort der Zeitstempel der jeweiligen Datei; `WEB_VERSION` ist
    nur noch der Rückfall, wenn eine Datei nicht gefunden wird.
    *(Eintrag rekonstruiert in Web 7.2.0 — s. Kopfnotiz. Fundstellen:
    `server/db.php:100`, `server/version.php:12`, `docs/Technik.md`,
    Changelog Web 5.4.0.)*

10. **Die Spalten der Tagestabelle stehen fest im Code statt im Feldkatalog.**
    *Erledigt mit Web 5.4.0.* `winch`, `bergwacht` und `secondary` standen im
    SELECT von `api/day.php`, noch einmal im Aufbau der Antwort und ein drittes
    Mal im Zeilenaufbau von `index.php`. Der Katalogschlüssel `day_col` war
    damit reine Dokumentation: Die Spalte „abw. Crew" stand seit Web 2.6.0 im
    Katalog und erschien trotzdem nie. Seither wertet `mf_tagesspalten()` als
    einzige Stelle den Katalog für die Tagestabelle aus; ein neuer Eintrag mit
    `day_col` erscheint ohne weitere Codeänderung. Die Gegenprobe lief in
    Web 5.10.0 rückwärts: „abw. Crew" wurde wieder abbestellt, und dafür
    genügte es, zwei Schlüssel im Katalog zu streichen.
    **Offen bleibt die getrennte Frage**, ob die Tagesübersicht auch die
    Spaltenmechanik aus `missiontable.js` übernimmt — dagegen spricht, dass
    sie die Katalogspalten führt, die die anderen beiden Tabellen nicht haben
    (zuletzt geprüft und verneint in P0/A6, Web 7.2.0).
    *(Eintrag rekonstruiert in Web 7.2.0 — s. Kopfnotiz. Fundstellen:
    `server/api/day.php:246`, `server/mission_fields.php:446`,
    `server/mission_fields_lib.php:24`, `docs/Technik.md`,
    Changelog Web 5.4.0 und 5.10.0.)*

12. **Schriften und Leaflet werden zur Laufzeit von fremden Servern geladen.**
    *Erledigt mit Web 5.2.0.* Jeder Seitenaufruf meldete die IP-Adresse an
    Google (Schriften) beziehungsweise unpkg (Leaflet) — in einer Anwendung,
    deren ganzer Zweck darin besteht, dass Patientendaten den Browser nicht
    unverschlüsselt verlassen, der letzte verbliebene Bruch in der Linie. Und
    bei blockiertem Abruf fiel die Karte vollständig aus. Beides liegt jetzt
    unter `server/assets/fonts/` bzw. `server/assets/vendor/`, mit Herkunft und
    Prüfsumme im Dateikopf. Seitdem lädt die Anwendung **keine fremde Quelle
    mehr** — die Voraussetzung dafür, dass sich Nr. 8
    (Content-Security-Policy) eng formulieren lässt.
    *(Eintrag rekonstruiert in Web 7.2.0 — s. Kopfnotiz. Fundstellen:
    Backlog Nr. 8, `docs/Technik.md`, Changelog Web 5.2.0.)*

15. **`api/suchindex.php` liefert das Feld `edited`, das niemand liest.**
    *Erledigt mit Web 7.0.0.* Das Feld ist aus SELECT und Antwort entfernt.
    Der Befund war zutreffend und unverändert: `suche.php` ist der einzige
    Abnehmer des Endpunkts und hat den Wert nirgends ausgewertet. Der
    Bearbeitungsstand steht weiterhin in der Einsatzansicht
    (`api/mission.php`), wo er auch angezeigt wird.

16. **Zeilen der Tagesübersicht sind nicht mit der Tastatur erreichbar.**
    *Erledigt mit Web 7.0.0.* `index.php` setzt jetzt dieselben drei Zeilen
    wie `assets/missiontable.js`: `tabIndex = 0`, `role="link"` und einen
    `keydown`-Handler für Enter und Leertaste (mit `preventDefault`, sonst
    scrollt die Leertaste die Seite weg). Damit sind alle drei
    Einsatztabellen — Tagesübersicht, Suche und Zeitraum-Übersicht — ohne
    Maus bedienbar.

    Die Frage nach der Zusammenführung beider Tabellen (Nr. 10) ist damit
    nicht beantwortet und bleibt offen; die drei Zeilen haben nicht darauf
    gewartet.

22. **Das Alter ging unmaskiert in die Einsatztabellen.**
    *Erledigt mit Web 7.2.1.* `zelleGeschuetzt()` in
    `server/assets/missiontable.js` maskierte Einsatzort und Diagnose über
    `esc()`, das Alter aber nicht (`v => v`) — und die Zelle wird per
    `innerHTML` gesetzt. Über das Formular war der Weg zu (`parseInt()` in
    `einsatz_form.php`); das **Feld** ist trotzdem keine Zahl, denn `age` liegt
    im `pat_blob` und der ist freies JSON. Der Weg hinein ist die
    **Wiederherstellung einer Sicherung** (`api/backup_restore.php` übernimmt
    den inneren Chiffretext unverändert), im Adminbereich sogar die einer
    *fremden*. Markup dort führte Skript in genau dem Fenster aus, in dem der
    Inhaltsschlüssel liegt; der Server konnte nichts prüfen, er sieht nur
    Chiffretext. Die Lücke bestand seit Web 5.2.0. Gefunden in P0 (dort F-20,
    Konzept Abschnitt 8 und 9.3; Prüfdokument P0, Abschnitt 4.4).

    Maskiert wird jetzt in `zelleGeschuetzt()` **selbst** statt an der
    Aufrufstelle: Die Entscheidung war an zwei von sechs Aufrufstellen falsch
    getroffen, und die nächste neue Spalte hätte sie erneut treffen müssen.
    Damit sind alle drei Einsatztabellen an einer Stelle abgesichert.

    Die Durchsicht des gesamten Importpfads (32 Ausgabestellen mit
    `innerHTML` o. ä. in 23 eigenen Skriptdateien und allen Seiten unter
    `server/`) ergab **keinen weiteren Fund**; die Liste steht in
    `docs/Pruefung-Sofortpaket-22.md`. Dabei fiel allerdings `edk_neu` auf —
    das Vormerkfach des Passwortwechsels trug den neuen Datenschlüssel über
    das Abmelden hinaus, was Punkt V-10 des Prüfdokuments P0 verbietet. Auch
    das ist mit dieser Version behoben (eine Zeile in
    `EdCrypto.clearSession()`).

    Die Keyguard-Einträge `pckb`/`pckt` bleiben beim Abmelden **bewusst**
    liegen: Sie tragen kein Schlüsselmaterial — `pckb` ist ein gekürzter
    SHA-256 über die ohnehin öffentlich ausgelieferte Schlüsselhülle, `pckt`
    ein Zeitstempel. Die toten Exporte `EdKeyGuard.beenden()`/`raeumen()`
    bleiben unberührt (Nr. 21).

    Vorher/Nachher-Proben unter `tools/maskierungs-probe/` und
    `tools/abmelde-probe/`; die erste darf als Vorlage für den ständigen
    Regressionsfall in P1 liegen bleiben (R20).

26. **Der CSV-Import verschiebt Einsätze über Mitternacht um 24 Stunden
    zurück.**
    *Erledigt mit Web 7.3.1.* Die Spalte `datum` wird ausgewertet und als
    `date_local` mitgesendet; `api/import_commit.php` nimmt sie als Bezugstag
    der Alarmzeit statt des Diensttags. Für die Gruppierung bleibt es beim
    Diensttag — `day_id` hängt an ihm, nicht am Einsatzdatum. Zwei Quellen für
    zwei verschiedene Aufgaben; die Sorge des alten Kommentars, es wären zwei
    Quellen für dieselbe, war der Grund, warum die Spalte auf `target: null`
    stand.
    Dazu eine Plausibilitätsschranke: Übernommen wird das Datum nur, wenn es
    der Diensttag ist oder der Tag darauf — mehr kann es nicht sein, die
    Anwendung kennt für den Tageswechsel genau einen Schritt. Dateien ohne die
    Spalte und Dateien mit unsinnigem Wert fallen auf das bisherige Verhalten
    zurück. Der zweite denkbare Weg (Formularregel: Uhrzeit vor Dienstbeginn
    heißt Folgetag) wurde verworfen, weil beim Import in ein leeres Konto der
    Dienstbeginn zum Zeitpunkt der Entscheidung noch nicht feststeht.
    *Gemessen:* Kreislauf CSV, unerklärte Abweichungen 9 → 6, Einzelvergleiche
    8 617 → 8 797 (die beiden Einsätze werden jetzt überhaupt erst verglichen).
