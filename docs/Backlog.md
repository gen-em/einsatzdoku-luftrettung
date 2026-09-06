# Gen-EM NAdoku — Backlog

Bewusst offene Punkte. 

**Nummern sind dauerhaft.** Verweise aus Code und Dokumentation nennen sie
(z. B. „Backlog Nr. 10"). Erledigte Punkte werden deshalb nicht gelöscht,
sondern nach unten in den Abschnitt *Erledigt* verschoben und behalten ihre
Nummer. Neue Punkte hängen sich hinten an.

**Zu den fehlenden Nummern 4, 6 und 7.** Sie waren vergeben und sind ohne
Eintrag verschwunden; ihr Inhalt ist nicht mehr rekonstruierbar. Sie bleiben
deshalb dauerhaft frei — weder werden sie neu vergeben noch nachgetragen. Diese
Notiz steht hier, damit die Frage nicht bei jedem Durchsehen erneut aufkommt.

**Zu den Nummern 59 bis 62 (02.09.2026).** Sie hießen bis dahin 46 bis 49 —
und zwar ein zweites Mal. Zwei Zweige haben nebeneinander angehängt (die
Uhr-Auslieferung nach R47 und das Zwischenpaket S2), beide für sich lückenlos
ab 46; beim Zusammenführen hat diese Datei **keinen Konflikt gemeldet**, weil
die Einträge an verschiedenen Stellen standen. Vier Nummern trugen danach zwei
verschiedene Punkte, zwei davon gleichzeitig unter *Offen* und unter
*Erledigt*.

Umnummeriert wurde die **jüngere** der beiden Reihen, also die aus der
Uhr-Auslieferung. Der Grund ist nüchtern: Ihre Verweise stehen ausschließlich
in `docs/`, die der anderen Reihe auch in `server/` — und jede Änderung unter
`server/` lädt sofort auf den Produktivserver. Jeder der vier Einträge sagt
unten, welche Nummer er vorher trug, damit ein älterer Verweis auflösbar
bleibt. **Für künftige Zusammenführungen:** Wer einen Zweig anlegt, der
Backlog-Punkte vergibt, prüft vor dem Zusammenführen die Nummernvergabe —

    grep -oE '^[0-9]+\.' docs/Backlog.md | tr -d '.' | sort -n | uniq -d

muss leer bleiben.

**Zu den Nummern 63 bis 67 (Rahmenplan Fassung 16, 02.09.2026).** Sie sind
für den S4-Zweig reserviert. Er hat seine fünf Punkte als 59 bis 63
angelegt, bevor die Entdopplung oben auf `main` lag, und nummeriert sie beim
Zusammenführen auf 63 bis 67 um: Sperrvermerke des Schnitts in dem
Konto-Backup, Bedienhöhe der Android-App, Fassungshinweise im
Android-Baulauf, Garmin-Uhrcode in der Wortliste, `csrf_check()` ohne
API-Zweig. Die Punkte 68 bis 79 sind mit Fassung 16 angelegt, 80 bis 83 mit
Fassung 21, 84 bis 88 mit Fassung 22; alle stehen unten; die Zuordnung aller offenen Punkte zu Paketen führt
`docs/Rahmenplan.md`, Abschnitt 5.

**Zu den Nummern 98 bis 113 (Rahmenplan Fassung 26, 03.09.2026).** 98–100
kommen aus der vorgezogenen Planung v1.0 (R65–R67), 101–113 sind die
Problemsammlung für Schritt 8 (S9, R73); ihre Kennungen PS-1 bis PS-10 stehen
in `docs/konzepte/Vorbereitung-S9-Problemsammlung.md`.

**Zu den Nummern 1, 9, 10 und 12.** Sie fehlten ebenfalls, waren aber
rekonstruierbar: Code und Changelog verweisen an neun Stellen namentlich auf
sie („Backlog Nr. 10"), und aus diesen Fundstellen geht eindeutig hervor,
worum es ging und womit es erledigt wurde. Die vier Einträge unten sind aus
genau diesen Fundstellen wiederhergestellt (Web 7.2.0, Paket P0/N6) und als
solche gekennzeichnet. Sie stehen unter *Erledigt*, weil alle vier es sind.

---

## Offen

8. Content-Security-Policy als zusätzliche Verteidigungslinie.
    *Ergänzung 06.09.2026 (Krypto-Review, R74):* Die Bestandsaufnahme
    macht sie enger möglich als hier angenommen — **null**
    Inline-Ereignisbehandler, **ein** `style`-Attribut, alle Skriptblöcke
    über `ui_seite_start()`. Der Bauplan (Nonce je Anfrage, Report-Only
    zuerst, Quellenliste für Kacheln und Photon) steht in
    `docs/konzepte/Vorbereitung-Sicherheitspaket.md`, SP-5. Warum es
    zählt: Daten- und Inhaltsschlüssel liegen als Hex im `sessionStorage`;
    jede XSS-Lücke, auch eine in Leaflet oder SheetJS, liest sie aus.
   Seit Web 5.2.0 eng fassbar: Es wird keine fremde Quelle mehr geladen
   (Nr. 12), die Regel muss also nichts von außen erlauben.
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
19. **`$title` in `einsatz_loeschen.php` wird nie gelesen.** Die Variable wird
    gesetzt, der Titel steht daneben als Literal. Gefunden in P0 (dort F-06).
    Einzeiler, aber bewusst nicht nebenbei erledigt: Er stand nicht auf der
    Freigabeliste.
21. **Die 43 weiteren Funde der A4-Nachlese sichten.** Die Erhebung „toter
    Code" in P0/A4 hat mit einer zweiten, breiteren Methode 43 zusätzliche
    Kandidaten geliefert (Abschnitt 9.3 des P0-Konzepts). Sie sind **nicht**
    freigegeben und **nicht** angefasst: Ein großer Teil berührt
    Antwortverträge (`api/`, Export, Backup), und dort ist „niemand liest es"
    keine hinreichende Begründung — ein Feld kann für ein älteres Backup
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

36. **Ein Prüfmittel für Klassennamen, die JavaScript sucht und niemand mehr
    vergibt.** In P3/O6 fiel auf, dass seit O2 **kein einziger** Filter der
    Suchseite mehr wirkte: Der Zuhörer horchte auf
    `.filterspalte input, .filterspalte select`, und die Klasse
    `filterspalte` war beim Umzug in die gemeinsame Leiste verschwunden
    (F-P3-AG). Nichts hat das gemeldet — ein Selektor, der ins Leere greift,
    ist in JavaScript kein Fehler, sondern eine leere Liste. Drei
    Arbeitspakete lang blieb es unbemerkt, weil Freitext und Sortierung an
    eigenen Zuhörern hingen und die Seite deshalb *fast* richtig aussah.
    `tools/vollstaendigkeit/` kennt bereits beide Seiten dieser Rechnung
    (Klassen im Markup, Klassen im Stylesheet); ihm fehlt die dritte:
    Klassen, die **JavaScript** in einem Selektor nennt. Ein Abgleich gegen
    die im Markup tatsächlich vergebenen Namen würde genau diesen Fall
    melden.
    Zwei Vorbehalte, weshalb es nicht nebenbei entsteht: Klassen, die JS
    selbst vergibt (`classList.add('aktiv')`), sind im Markup zu Recht nicht
    zu finden und dürfen nicht als Fund gelten; und Selektoren, die aus
    Zeichenketten zusammengesetzt werden, sind statisch nicht auflösbar. Das
    Mittel braucht also eine Ausnahmeliste mit Begründung — wie die
    Streichliste — statt einer Ja/Nein-Regel. Sinnvoll gegen Ende von P3,
    wenn die Klassennamen stehen.

    **Derselbe Fall von der anderen Seite, gefunden in S2/AP4:** `ui_plakette()`
    setzt ihre Klasse aus dem Ton zusammen — `'plakette-' . $ton`. Der Ton
    `warn` wurde an drei Stellen übergeben, und `.plakette-warn` gibt es im
    Stylesheet nicht (gültig sind `neutral`, `orange`, `blau`, `rot`). Die
    Plaketten standen dort ohne Hintergrund da, als bloßer Text. Zwei der drei
    Stellen stammten aus AP2 und AP3, also aus derselben Phase; behoben mit
    Web 10.3.0.

    `tools/vollstaendigkeit/` konnte das nicht finden: Es kennt Klassen im
    Markup und Klassen im Stylesheet, aber `plakette-warn` taucht als Literal
    in keinem von beiden auf. Das gesuchte Prüfmittel muss also **beide**
    Richtungen abdecken — Selektoren, die JavaScript nennt, und Klassennamen,
    die PHP oder JS zur Laufzeit zusammensetzen. Für den zweiten Fall gibt es
    einen billigen Sonderweg, der ohne allgemeine Auflösung auskommt: Die
    Bausteine mit geschlossenem Wertevorrat (`ui_plakette` mit vier Tönen,
    `ui_knopf` mit fünf Arten, `ui_meldung_markup`) kennen ihre erlaubten Werte
    selbst — eine Prüfung, die jeden übergebenen Wert gegen diese Liste hält,
    hätte den Fall sofort gemeldet.

37. **Wie verhält sich die Anwendung, wenn ein Konto über Jahre wächst?**
    Aufgeworfen während P3, dort bewusst **nicht** weiterverfolgt — die Frage
    gehört nicht ins Oberflächen-Redesign. Der Bestand ist ausgelegt auf
    „50–80 Einsätze pro Jahr, nach zwei Jahrzehnten unter etwa 1600
    Datensätze"; die Frage ist, was darüber hinaus passiert.

    **Was bereits gemessen ist** (Sondierung ohne Lastdatensatz: die Antworten
    von `api/suchindex.php` und `api/range.php` wurden auf dem Weg in den
    Browser vervielfacht, der Client durchlief damit den echten Weg):

    - **Die Suche skaliert sublinear und ist nicht das Problem.** 85-fache
      Menge (82 → 7000 Einsätze) ergibt 2,2-fache Ladezeit (558 → 1224 ms)
      und 4,8-fache Rechenzeit je Tastendruck (14,5 → 69,9 ms). Grund ist die
      Seitengrenze 200 aus Web 5.9.0: Ab 250 Einsätzen stehen immer genau 200
      Zeilen im DOM, gefiltert und gezählt wird über alles.
    - **Die Entschlüsselung ist billig.** 0,039 ms je Einsatz auf dem Weg, den
      die Anwendung geht (seriell, `importKey` je Datensatz) — bei 3500
      Einsätzen 0,14 s. Die naheliegende Vermutung, die Ende-zu-Ende-
      Verschlüsselung sei der Engpass, ist um etwa Faktor 100 daneben. Ein
      einmaliger Schlüsselimport brächte 0,013 ms je Einsatz; das lohnt den
      Eingriff nicht.
    - **Die Zeitraumübersicht wächst dagegen linear und ungedeckelt.**
      `zeitraum.php` ruft `EdMissionTable.erzeuge` ohne `seite`; bei 3500
      Einsätzen entstehen 3500 `<tr>`, ein Tabwechsel dauert 854 ms statt 191
      (gemessen **ohne** Kartenmarker — die Kacheln waren in der Messumgebung
      nicht erreichbar, der Markeranteil kommt oben drauf).
    - **Der Suchindex überträgt den gesamten Bestand**: 1097 Bytes je Einsatz,
      ohne LIMIT — 3500 Einsätze wären 3,66 MB in einer Antwort. Das ist die
      Folge der Verschlüsselungszusage: Diagnose, Alter und Einsatzort kann
      der Server nicht filtern. Die **übrigen** rund dreißig Filter arbeiten
      auf unverschlüsselten Spalten und könnten serverseitig vorschneiden —
      das ist der eigentliche Hebel, und er ist unangetastet.
    - **Das Backup ist zu 93 % Spur.** 31,9 kB je Einsatz mit Spurpunkten,
      2,2 kB ohne; 3500 Einsätze ergäben rund 109 MB rohes JSON. Die
      `.edbak`-Datei selbst ist gzip-komprimiert und handlich (739 kB für 87
      Einsätze) — beim **Zurückspielen** aber entsiegelt der Browser sie und
      schickt das rohe, unkomprimierte JSON per POST
      (`einstellungen.php:2053` und `:2191`). `crypto.js` bringt mit
      `CompressionStream` bereits alles mit, was ein komprimierter POST
      bräuchte.
    - **Offen und lokal nicht klärbar:** Ob und wo `post_max_size` diesen POST
      abschneidet. Lokal kamen 32 MB durch, obwohl `post_max_size` auf 8M
      steht — der eingebaute PHP-Server verhält sich bei
      `Content-Type: application/json` offenbar anders als ein Webspace mit
      Apache oder nginx. Die Grenze des **Produktivservers** ist damit
      ungemessen; sie entscheidet, ab wie vielen Einsätzen eine
      Wiederherstellung scheitert.

    **Stille Kappungen** — gefährlicher als langsame Seiten, weil eine
    Dokumentation, die Zeilen verschweigt, falsch ist: `dt_liste($userId, 500)`
    in der Diensttage-Leiste (`ui.php:498`), 120 in `api/day.php:130`, 400 im
    Verschiebe-Dialog (`einsatz_verschieben.php:64`). Keine davon sagt der
    Anwenderin, dass sie greift. **Laute Grenzen** dagegen melden sich
    ordentlich mit HTTP 413: Import bei 3000 Einsätzen / 600 Diensttagen
    (`api/import_commit.php:93`), Export bei 5000 (`api/export_data.php:182`).

    **Die zweite Achse — viele KONTEN statt vieler Einsätze — ist seit P3/O9b
    gemessen** und vorerst erledigt (`tools/pruefkonten/`, 300 Konten): Die
    NutzerInnen-Liste kommt mit **einer** Abfrage und **einem**
    Verzeichnisdurchlauf aus (3,3 ms / 3,2 ms bei 304 Konten, 103 ms für den
    ganzen Aufruf), weil der Backup-Stand aller Konten aus je einer kleinen
    `konto.json` kommt statt aus je einem Verzeichnisdurchlauf. Gesucht,
    gefiltert und sortiert wird im Speicher; der Browser bekommt höchstens 50
    Zeilen. **Die Grenze davon:** Bei einigen tausend Konten kippt das
    Verhältnis, und dann braucht der Backup-Stand eine Spalte in der
    Datenbank statt eines Verzeichnisdurchlaufs. Dabei aufgefallen und behoben:
    `edbak_intervall()` fragte je Zeile die Datenbank — 304 Abfragen und
    27,7 ms für eine Subtraktion.

    **Noch nicht auf dieser Achse gemessen:** `admin_sicherungen.php` (die
    Übersicht dort liest weiterhin je Konto ein Verzeichnis **und** eine
    Begleitdatei — F-P3-F, fällig in O9c) und der Sammelvorgang „Alle sichern"
    (222 ms je Konto mit 82 Einsätzen; die Liste begrenzt eine Sammelaktion
    deshalb auf ein Zeitbudget von 20 s und sagt, was übrig blieb).

    **Was noch fehlt:** die Serverseite bei vielen EINSÄTZEN. Sie ist die
    einzige Größe, die die Sondierung nicht erreicht — dafür braucht es echte
    Zeilen. Beschlossen war
    dafür ein Werkzeug `tools/lastdatensatz/`, das den vorhandenen Bestand mit
    neuen Dienstdaten vervielfacht (250 / 500 / 1000 / 3500), in **zwei**
    Verteilungen: realistisch mit rund sechs Einsätzen je Diensttag, und
    verdichtet auf ein Kalenderjahr für die Zeitraumübersicht. Zwei Fallstricke
    stehen dabei fest: Das Demo-Konto scheidet als Ziel aus (es setzt sich alle
    1800 s selbst zurück, `demo_lib.php:52`), und ein `pat_blob` ist zwar 1:1
    kopierbar (AES-GCM ohne `additionalData`, IV im Blob — `crypto.js:109`),
    darf aber niemals mit behaltenem IV und geändertem Klartext geschrieben
    werden: Das bräche die Verschlüsselung für die echten Einsätze desselben
    Kontos gleich mit.

    **Diese Frage wird in der Phase S2 beantwortet, und `tools/lastdatensatz/`
    ist darin zu `tools/messstand/` geworden** (S2/AP0). Der Weg ist ein
    anderer als hier beschrieben — nicht neue Dienstdaten erfinden, sondern
    das Referenz-Backup vervielfältigen und über den **regulären**
    Wiederherstellungsweg einspielen; damit ist der Einspielweg selbst einer
    der Prüflinge. Stand: 5002 Einsätze, 3,2 Mio. Spurpunkte, herstellbar in
    245 s.

    **Was S2 bisher beantwortet hat:**

    - *Speicher.* Spurpunkte sind 93 % des Bestands; als Blob kosten sie
      3,58 statt 62,4 Byte je Punkt (S2/AP1, Web 10.0.0).
    - *Wartung.* Sie lag auf dem Weg einer Anfrage und kostete bei 9,46 Mio.
      Zeilen 4,07 s. Seit S2/AP2 (Web 10.1.0) läuft sie in Häppchen mit
      Zeitbudget, drei Auslösern und Fortsetzungsmarke.
    - *Wachstum gedeckelt.* Seit S2/AP3 (Web 10.2.0) verdichten und dünnen zwei
      Jobs die Spuren aus. Gemessen am Messstand (5345 Einsätze): **1,60 MB
      Blobs je 1000 Einsätze** gegen 3 MB Zielwert aus E-S2-24 — dieselbe Menge
      als Zeilen wären rund 200 MB gewesen.
    - *Ein Fund nebenbei:* Ein gelöschtes Konto ließ seine Spurpunkte liegen —
      am Messstand 6 202 931 verwaiste Punkte aus zwei Konten (F-S2-B,
      behoben in AP1).

    **Was hier offen bleibt,** bis die Phase durch ist: Zeitraumübersicht und
    Nachbearbeitung bei dieser Menge, `admin_sicherungen.php` (siehe oben),
    und die Frage, ob die Zielzahlen aus E-S2-24 (Suche ≤ 5 s, Tagesansicht
    ≤ 3 s, Backup ≤ 5 min) gehalten werden.

38. **`nb_offen_gesamt()` holt Zeilen, um sie zu zählen.**
    *Gefunden in P3/O11.* Der Eintrag „Zuordnung offen" der Diensttage-Leiste
    ruft bei **jedem** Seitenaufruf `nb_offen_gesamt()`. Die Funktion bricht
    zwar sofort ab, wenn `vehicles.base_id` schon `NOT NULL` trägt — auf einer
    Neuinstallation ist das von Anfang an so, dort kostet sie eine einzige
    `information_schema`-Abfrage, die zusätzlich pro Aufruf gemerkt wird.

    Auf einer **migrierten** Installation, deren Nachbearbeitung noch niemand
    abgeschlossen hat, läuft sie dagegen durch: `nb_offene_tage($userId)` holt
    bis zu 500 Diensttage samt einer Unterabfrage je Zeile — nur um
    `count()` darauf anzuwenden —, dazu bis zu zehn weitere Abfragen für die
    Stammdatentabellen. Ein `SELECT COUNT(*)` täte es in allen Fällen.

    Kein Fehler und kein Zustand, der bleiben soll (die Seite existiert, um ihn
    zu beenden) — aber unnötig, und er trifft genau die Installationen, die
    ohnehin am meisten Bestand tragen. Behebung: eine eigene Zählfunktion
    neben `nb_offene_tage()`, die nur `COUNT(*)` fragt.

40. **55 Altklassen ohne Gegenstück.**
    *Aufgenommen in P3/O11, war für O12 vorgesehen, in O12 bewusst
    zurückgestellt.* Die Vollständigkeitsprüfung verlangt für jede der 220
    Klassen des alten Stylesheets entweder eine Regel im neuen oder einen
    Eintrag auf der Streichliste. O11 hat 22 Einträge nachgetragen (die Zahl
    fiel von 78 auf 55); die übrigen stammen aus O1 bis O10 und sind dort mit
    dem Umbau verschwunden, ohne eingetragen zu werden. Die Streichliste ist
    damit unvollständig — sie sagt nicht zu jeder verschwundenen Klasse,
    *warum* sie verschwunden ist, und genau das ist ihr Zweck.

    **Warum nicht in O12 erledigt.** Nr. 39 daneben war Werkzeugarbeit: 29
    Namen, jeder in wenigen Minuten am Fundort zu klären. Dieser Punkt ist
    etwas anderes — er verlangt für 55 Klassen die Rekonstruktion, in welchem
    von zehn Paketen sie verschwunden sind und wodurch sie ersetzt wurden.
    Das ist Archäologie in zehn Commits, und sie **halbherzig** zu machen
    wäre schlimmer als sie zu lassen: Eine Streichliste mit 55 Einträgen
    „ersatzlos entfallen" sieht vollständig aus und sagt nichts. Der Zweck der
    Liste ist die Begründung, nicht die Zeile.

    **Weg dahin** (P4, vor dem ersten CSS-Umbau): Die 55 Namen gruppenweise
    gegen die Konzeptabschnitte O2 bis O10 halten — der Umsetzungsstand nennt
    zu jedem Paket, welcher Baustein welche alte Klasse abgelöst hat. Was sich
    daraus nicht klären lässt, bekommt einen Eintrag „Herkunft nicht mehr
    feststellbar" und wird als solcher gezählt; auch das ist eine ehrliche
    Auskunft, „ersatzlos" wäre eine erfundene.

41. **Sechs Klassen im Markup ohne Regel — offene Gestaltungsfragen.**
    *Aufgenommen in P3/O12 als Rest von Nr. 39.* Nach dem Eintragen der
    begründeten Fälle in `tools/vollstaendigkeit/ohne-regel.md` bleiben sechs
    Namen übrig, bei denen die Frage offen ist, ob sie eine Regel brauchen.
    Sie stehen dort mit dem Vermerk `[offen]` und bleiben deshalb ein Befund:

    - `imp-warn` — „abweichende Crew (…)" in der Kopfzeile einer Tagesgruppe
      der Importvorschau. Ein **Warnhinweis, der wie Fließtext aussieht**;
      von allen sechs der wahrscheinlichste echte Fund.
    - `imp-daygroup` — die Kopfzeile einer Tagesgruppe selbst. Sie trägt ihren
      Text in `<strong>`, sonst nichts: eine Gruppenüberschrift, die aussieht
      wie eine Datenzeile.
    - `rea-kopf`, `rea-beginn` — Kopfzeile und Beschriftung einer
      Reanimationssitzung. Das Aussehen kommt vom Nachbarn `phasen-eingabe`
      bzw. von der Elementregel für `label`; kein Skript liest die Klassen.
      Entweder Reste, oder die Kopfzeile soll sich von einer gewöhnlichen
      Phasenzeile abheben.
    - `rmneu` — der Knopf „neu" in der Rettungsmittelwahl, neben `rmopt`.
    - `phasen-name` — der Name einer Phase in der Einsatzansicht.

    Jedes davon ist eine **Entscheidung**, kein Aufräumen: Entweder die Klasse
    verschwindet, oder sie bekommt eine Regel — und dann ist das eine neue
    Darstellung und braucht nach `docs/Design.md` 1 eine Freigabe. Deshalb
    nicht am Phasenende erledigt.

42. **Drei Unicode-Zeichen stehen noch als Symbol im Markup.**
    *Aufgenommen in P3/O12, Zahl fortgeschrieben in S2/AP3, AP4, AP5, AP5b
    und AP6.* P-P3-03 verlangt null. Die Prüfung meldet **195** Treffer (bei
    Aufnahme 158); 192 davon sind Kommentare oder richtige Typografie (die
    Auslassungspunkte der Fortschrittsmeldungen, die Pfad-Pfeile der Hinweise,
    das Malzeichen in „3× RTW"). Drei sind echte Symbole — dieselben drei wie
    bei der Aufnahme:

    > **Die Zahl wächst mit dem Text, nicht mit dem Problem.** Jeder neue
    > Hinweissatz mit Auslassungspunkten erhöht sie um eins; S2/AP3 hat sie
    > mit einer einzigen neuen Zeile auf der Wartungsseite von 167 auf 168
    > gebracht, S2/AP4 mit den Kopfkommentaren dreier neuer Dateien von 168
    > auf 174 (`?art=…&id=…` allein zählt viermal), S2/AP5 mit den
    > Fortschrittsmeldungen des Backup-Laufs („Teil 2 von 5 …") auf 189
    > S2/AP5b mit drei Auslassungspunkten in **Kommentaren** auf 192 und
    > S2/AP6 mit den Fortschrittsmeldungen des Freigabewegs auf 195 — jedes
    > Mal gemessen gegen den Stand davor, nicht geschätzt.
    > Wer die Zahl als
    > Fortschrittsmaß liest, liest sie falsch — gemeint sind die drei unten.
    > Das Prüfmittel trennt beides nicht, und das gehört hierhin und nicht in
    > eine Fußnote.


    - `server/einsatz_form.php:1416` — `'✕'` als **Rückfall**, wenn
      `edSymbol()` beim synchronen Aufbau noch nicht geladen ist. Mit
      Begründung im Code; das ist kein Fehler, sondern ein Netz, und es
      gehört eher dokumentiert als entfernt.
    - `server/assets/ortsfeld.js:197` — `x.textContent = '×'` am
      Koordinaten-Chip. Der Knopf `.rmx` ist textgroß gebaut und hat keine
      Symbolregel; ein SVG hineinzusetzen heißt, ihn neu zu bemaßen.
    - `server/assets/patient.js:133` — `⚠` für einen nicht entschlüsselbaren
      Datensatz. Das Zeichen steht nicht nur in einer Zelle, sondern **im
      Satz** („… ist mit ⚠ gekennzeichnet"). Ein SVG im Fließtext ist eine
      Gestaltungsfrage, keine Ersetzung.

    Die beiden letzten sind also kein mechanischer Tausch. Solange sie
    stehen, ist der Sollwert von P-P3-03 nicht erreicht — und das steht so im
    Prüfprotokoll, statt die Zahl schönzurechnen.

43. **Ortsdaten: die GPS-Spur ist nicht verschlüsselt.**
    *Entschieden 06.09.2026 (R74):* **Weg C sofort** (Sofortpaket, nur
    Dokumente: die Zusage in `CLAUDE.md` 4, `Technik.md`, README, Handbuch
    und im Datenschutztext auf das eingrenzen, was sie hält), **Weg B als
    eigene Phase S11 nach P6, vor der Öffnung** — mit einem
    Konto-Schlüsselpaar (Nr. 53), Umfang Spur, Phasenkoordinaten,
    Reanimationsereignisse und Zielklinik, Altbestand per Einmalwerkzeug im
    Browser. Die Uhr kann es: ECDH P-256, AES-256-CBC, HMAC-SHA256 ab
    Connect IQ 3.0.0 (geprüft). Skizze in
    `docs/konzepte/Vorbereitung-Sicherheitspaket.md`, SP-9.
    *Aufgenommen 30.08.2026 aus der ersten Rückmeldungsrunde.* Der Einsatzort
    ist mit Adresse und Koordinaten Ende-zu-Ende verschlüsselt — die Spur, die
    dorthin führt, und die Koordinate jeder Phase liegen im Klartext. Der Ort
    ist damit nominell geschützt und faktisch rekonstruierbar.

    Die Bestandsaufnahme steht in `docs/konzepte/Konzept-V1-Ortsdaten.md`: was liegt wo,
    was verrät was, was kosten die drei Wege. Kurzfassung — der Server rechnet
    mit den Koordinaten **nicht** (Strecke und Höhenmeter kommen von der Uhr,
    die Phasenzuordnung über Zeitstempel), aber die **Uhr hat keinen
    Schlüssel** und kann deshalb nur Klartext liefern.

    Empfehlung dort: die Zusage zuerst auf das eingrenzen, was sie hält (ein
    Nachmittag), und die eigentliche Lösung — Schlüssel auf die Uhr — in eine
    eigene Phase zusammen mit der ohnehin anstehenden Uhr-Arbeit.

44. **Sprungliste bei Standorten mit vielen Rettungsmitteln.**
    *Aufgenommen 30.08.2026.* Ein Standort mit neun Rettungsmitteln zwingt zum
    Scrollen, um den zu finden, den man sucht. Vorschlag: eine Zeile runder
    Marken direkt unter der Überschrift „Rettungsmittel", die zum Eintrag
    springen — erst ab sechs Einträgen, darunter sieht man die Liste ohnehin
    ganz.

    Mockup liegt: `docs/mockups/N1-sprungliste.html` mit Bildern für 900 und
    390 px. **Wartet auf Freigabe** — es wäre eine neue Darstellung, und die
    braucht nach `docs/Design.md` 1 eine ausdrückliche Zustimmung.

45. **Dritte Kartengröße zwischen klein und Vollbild.**
    *Aufgenommen 30.08.2026, zurückgestellt.* Die Karte des Diensttags ist im
    Regelfall klein und im Vollbild oft zu groß. Vorschlag aus der Durchsicht:
    eine mittlere Fassung über die volle Breite des Diensttags, über der
    Liste. Kein Mockup, keine Freigabe — bewusst nicht in dieser Runde.

46. **Das Altformat des Backups wird mit NaDoku 1.0 abgeschafft.**
    *Aufgenommen 31.08.2026 (S2/AP5), Entscheidung desselben Tages.* Seit
    Web 11.0.0 schreibt die Anwendung Containerfassung 4; die einteiligen
    Fassungen 2 und 3 werden nur noch **gelesen**. Das ist der Weg, auf dem
    ein vorhandener Bestand einmal herüberkommt — nicht mehr.

    Zu entfernen sind dann: der Lesezweig in `assets/crypto.js`
    (`openBackup`, Fassung 2 und 3), der Punktlisten-Rückweg in
    `backup_lib.php` (`$spurSchreiben`, `$insPoint`), die Annahme von
    Nutzlast 6 und 7 in `api/backup_restore.php`, die Fassungsweiche in
    `tools/referenzdatensatz/vergleich/lesen.py`, die einteilige
    Referenzdatei unter `referenz/altformat/` samt dem Lauf `--art edbak-alt`
    und seiner Ausnahmeliste.

    **Und der Messstand hängt daran.** `tools/messstand/vervielfaeltigen.py`
    baut den 5000er-Bestand, indem es die Referenz-Nutzlast vervielfältigt und
    als einteilige Datei versiegelt; eingespielt wird über den
    Altformat-Lesepfad. Das ist heute die richtige Wahl (S2/AP5 begründet sie:
    Gemessen wird das Sichern und Wiederherstellen, und beides läuft in
    Fassung 4 — der Weg zum Bestand ist selbst ein R11-Prüffall). Zum Stichtag
    braucht er einen anderen Weg: entweder einen Container-Schreiber in Python
    oder ein Herstellen des Bestands über die Uhr-Schnittstelle.

    **Diese Abhängigkeit hat schon einmal zugeschlagen** (31.08.2026, F-S2-E):
    Das Werkzeug erbte die Fassungsnummer aus der Referenz, und seit die
    Fassung 4 mit Nutzlast 8 ist, schrieb es einteilige Dateien, die
    `version: 8` nennen. Der Einspielweg nahm daraufhin den Verweisweg und
    legte 164 Einsätze **ohne Spur** an — 91 208 Punkte weg, Meldung
    „fertig". Behoben durch `nutzlast["version"] = 7` und eine Meldung in
    `edbak_restore()`. Die Notiz oben war richtig; sie war nur nicht laut
    genug, um zu verhindern, dass es passiert.

    > **Warum das hier steht und nicht einfach passiert.** Ein Lesezweig, der
    > niemandem mehr auffällt, wird nicht gepflegt und trotzdem mitgeschleppt
    > — und im Ernstfall verlässt sich jemand darauf. Ein Datum, zu dem er
    > verschwindet, ist ehrlicher als ein „bleibt erstmal".

    Vorher zu klären: Was geschieht mit einer alten Datei nach dem Stichtag?
    Vorschlag: Die Meldung nennt die letzte Fassung, die sie noch einspielen
    konnte — so wie es `version_alt` heute für Nutzlasten unter 6 tut.
47. **Nichts hält das native `confirm()` draußen.**
    *Aufgenommen 31.08.2026 (S2/AP5b, aus F-S2-D).* `assets/confirm.js` gibt
    es, weil Browser bei nativen Dialogen „keine weiteren Dialoge dieser
    Seite anzeigen" anbieten — danach verschwinden Rückfragen stillschweigend. Das
    Handbuch sagt das auch zu: „Alle Rückfragen erscheinen als Fenster
    **innerhalb der Seite**."

    Diese Zusage war zwei Jahre lang falsch. Zwei Aufrufe im Backup-Bereich
    von `einstellungen.php` benutzten weiter `window.confirm`; aufgefallen ist
    es erst, als der Kreislauftest daran hängenblieb — Playwright weist native
    Dialoge stillschweigend ab, und das Einspielen brach ab, ohne dass jemand
    eine Frage gesehen hätte. Beide sind auf `window.edConfirm` umgestellt.

    Was fehlt, ist die Schranke: eine Prüfung, die `confirm(`, `alert(` und
    `prompt(` in `server/**/*.php` und `server/assets/*.js` findet und meldet
    — mit Ausnahmeliste für die eine berechtigte Stelle (`confirm.js` selbst
    benutzt `window.confirm` als Rückfall für Browser ohne `<dialog>`).

    > **Warum eine Prüfung und nicht nur Aufmerksamkeit.** Der Fehler ist
    > unsichtbar: Ein natives `confirm()` funktioniert im Alltag, es fällt
    > erst bei einer abgeschalteten Dialogsorte oder in einem Prüfbrowser auf
    > — also genau dann, wenn niemand hinsieht.

    Naheliegender Ort: `tools/vollstaendigkeit/`, das ohnehin Markup und
    Stylesheet gegeneinanderhält, oder ein eigenes kleines Prüfmittel neben
    `tools/wortliste/`. Verwandt mit Nr. 36 (Klassennamen, die JavaScript
    sucht): beides sind Zusagen, die im Code stehen und die niemand nachzählt.

48. **Aufbewahrung je Konto einstellbar, nicht nur je Installation.**
    *Aufgenommen 01.09.2026 (S2/AP6).* E-S2-14 nennt „Standard 2 je Konto,
    manuell mehr je Konto möglich". Umgesetzt ist die Zahl für die ganze
    Installation (`app_state.adminbackup_aufbewahrung`); ein Wert je Konto
    hätte einen Ablageort gebraucht, den es nicht gibt — weder in `konto.json`
    noch als Spalte in `users`.

    **Wofür es gebraucht wird:** ein Konto, dessen Bestand besonders wertvoll
    oder besonders bewegt ist, und für das man mehr Stände vorhalten will, ohne
    die Zahl für alle anzuheben. Heute geht das nur als Umweg — ein Paket, das
    freigegeben ist, wird von der Verdrängung verschont. Das ist ein
    Nebeneffekt und kein Ersatz: Die Freigabe ist für etwas anderes da, und sie
    endet mit dem Einlösen.

    Naheliegender Ort: ein Feld in `konto.json` (die Begleitdatei ist ohnehin
    das Verzeichnis des Ordners) und ein Zahlenfeld auf der Kontoseite neben
    „Jetzt sichern". `edbak_aufbewahrung()` bekäme dafür einen optionalen
    Parameter; `edbak_verdraengen()` liest ihn.

49. **Aufbewahrung auch auf dem Backup-Ziel.**
    Der Versand (Web 12.1.0, S2/AP7) **ergänzt nur**: Auf der Gegenstelle
    löscht diese Anwendung nie, auch nicht im Sinne der Regel „höchstens zwei
    je Konto", die für die Ablage auf dem eigenen Server gilt. Bei zwei
    Backups je Konto und Monat läuft ein Ziel damit über kurz oder lang
    voll, und niemand merkt es hier.

    Das ist zunächst Absicht und keine Lücke: Der Zweck eines auswärtigen Ziels
    ist, den Ausfall dieses Servers zu überleben — samt eines Fehlers, der
    **hier** zu viel löscht. Ein Versand, der drüben aufräumt, trägt genau
    diesen Fehler mit hinüber.

    **Zu entscheiden** ist deshalb nicht *ob* aufgeräumt wird, sondern wer
    haftet: eine eigene Zahl je Ziel („dort höchstens N je Konto"), die
    ausdrücklich eingeschaltet werden muss und nie die Vorgabe ist — oder eine
    blosse **Anzeige** des Belegten am Ziel, damit die Betreiberin es sieht und
    dort selbst entscheidet. Der zweite Weg löscht nichts und beantwortet die
    Frage vielleicht schon.

50. **Der Versand liest je Konto ein Verzeichnis.**
    `sz_versand_schub()` fragt für jeden Kontoordner die Verzeichnisliste des
    Ziels ab, um zu erkennen, was dort fehlt. Bei 33 Ordnern ist das
    unauffällig (gemessen: SFTP 3,08 s für 64 Dateien einschliesslich aller
    Listen); bei dreihundert Konten sind es dreihundert Abfragen je Lauf und je
    Ziel, über eine Leitung, die kein Loopback ist.

    Der Ausweg ist **nicht** eine Merkliste in der Datenbank — die behauptet
    „schon versandt" auch dann noch, wenn die Datei am Ziel gelöscht oder das
    Ziel neu aufgesetzt wurde, und diese Art Lüge fällt erst auf, wenn man das
    Backup braucht (Begründung in Technik 4.97c). Denkbar ist stattdessen,
    die Liste je Ziel **einmal rekursiv** zu holen, wo das Protokoll es
    hergibt, und nur bei Zweifel nachzufragen. Erst messen, dann bauen.

51. **Die Suchseite verarbeitet 5 000 Einträge, um 200 zu zeigen.**
    Gemessen in S2/AP9 an einem Konto mit 5 002 Einsätzen: Bis die
    geschützten Spalten lesbar sind, vergehen **3,77 s** (Drossel 6×). Davon
    entfallen auf das Entschlüsseln der angezeigten Zeilen rund **0,1 s** —
    der Abstand zwischen „erste Zeile im DOM" (3,67 s) und „lesbar" (3,77 s).
    Die Zeit geht also fast vollständig für das drauf, was VOR der Tabelle
    passiert: die Antwort holen und auswerten, den Heuhaufen je Einsatz bauen,
    filtern, sortieren.

    Und entschlüsselt werden **alle** 5 002 (4 880 mit Block), obwohl nur 200
    angezeigt werden. Das ist nicht ohne Grund: Die Freitextsuche sucht auch
    in Diagnose, Alter und Einsatzort, und die stehen im verschlüsselten
    Block. Ohne Filter braucht es sie aber nicht.

    **Zu entscheiden:** ob die Entschlüsselung auf das verschoben wird, was
    tatsächlich gebraucht wird — beim Aufbau nur die angezeigten Zeilen, der
    Rest im Hintergrund oder erst, wenn ein Filter ihn verlangt. Das ist kein
    Umbau des Suchindex (den schließt E-S2-16 aus), aber eine spürbare
    Änderung im Verhalten: Ein Filter würde dann beim ersten Mal länger
    brauchen. Vorher messen, welcher der drei Posten vor der Tabelle wirklich
    wiegt — die Vermutung „es ist die Krypto" hat sich in AP9 schon einmal
    als falsch erwiesen.

52. **WebDAV als viertes Backup-Ziel.**
    Aus E-S2-22, dort ausdrücklich in den Backlog verwiesen. Die Schnittstelle
    `Zielweg` (`sicherungsziel_lib.php`) ist genau dafür gebaut: verbinden,
    Ordner anlegen, senden, holen, auflisten, löschen — ein vierter Adapter
    berührt weder den Versandjob noch das Komplettbackup.

    **Warum es trotzdem nicht nebenbei geht:** WebDAV ist HTTP, und die
    Anwendung hat für ausgehendes HTTP bisher keinen Weg. Nötig wären
    `ext/curl` (auf Webspace meist da, aber nicht sicher) und eine Entscheidung
    über die Zertifikatsprüfung — bei FTPS prüft `ext/ftp` nichts, bei WebDAV
    über curl **kann** man prüfen, und dann sollte man auch. Das ist eine
    Festlegung und kein Handgriff.

53. **Konto-Schlüsselpaar für versiegelte Serversicherungen.**
    *Ergänzung 06.09.2026 (R74):* Dasselbe Schlüsselpaar ist der Schlüssel
    auf der Uhr für Weg B (Nr. 43): privater Teil unter dem
    Inhaltsschlüssel gehüllt, öffentlicher Teil im Klartext ans Gerät. Die
    offenen Fragen von hier (wo der private Teil lebt, Passwortwechsel)
    sind damit beantwortet — er lebt wie `pat_wrap_rc`, und ein
    Passwortwechsel berührt ihn nicht. Zuordnung damit **S11**, nicht mehr
    „nach v1.0".
    Aus E-S2-19. Nächtliche Backups je Konto ohne Browser sind abgelehnt
    worden, weil der Server den Inhaltsschlüssel nicht hat und ihn nicht
    bekommen soll. Ein **öffentlicher** Schlüssel je Konto würde die Lücke
    schließen: Der Server verschlüsselt damit ohne jedes Geheimnis, öffnen kann
    es nur, wer den privaten Teil hat — die NutzerIn im Browser.

    **Zu entscheiden:** wo der private Teil lebt (unter dem Inhaltsschlüssel
    verpackt wie `pat_wrap_rc`?), was bei einem Passwortwechsel damit
    geschieht, und ob ein Backup, das niemand außer der NutzerIn öffnen
    kann, für die Administration überhaupt eine Rückfallebene ist — bei einem
    verlorenen Konto ist sie es nicht. Genau daran ist E-S2-19 einmal
    gescheitert; der Punkt steht hier, damit die Frage nicht verlorengeht,
    nicht weil die Antwort feststünde.

54. **Der Migrationslauf nach einer Wiederherstellung ist ein zweiter Gang.**
    Aus S2/AP8. Das Konzept sieht in E-S2-20 vor, dass die Wiederherstellung
    „danach einen Migrationslauf" ausführt. `wiederherstellen.php` tut das
    nicht: Es sagt am Ende, ob der Dump aus einer anderen Fassung stammt, und
    schickt zur Wartung. Der Grund ist gut — `update.php` ist seit M6-01
    zweistufig, weil Migrationen Spalten löschen können, und eine Seite ohne
    Anmeldung, die sie nebenbei mitlaufen liesse, nähme genau diese
    Absicherung heraus.

    Damit bleibt der Schritt aber **an einem Menschen hängen**, und zwar an
    dem Tag, an dem er am meisten zu tun hat. Wer ihn vergisst, hat eine
    Installation mit altem Schema und neuem Code — und merkt es an der Stelle,
    an der zuerst eine Spalte fehlt.

    **Zu entscheiden:** Ob `$MIGRATIONS` und der Ausführungsteil aus
    `update.php` in eine eigene Datei wandern (dann liesse sich der Lauf von
    beiden Seiten aufrufen, mit derselben Zweistufigkeit), oder ob
    `wiederherstellen.php` nach dem Einspielen unmittelbar auf `update.php`
    weiterleitet und die Anmeldung dazwischen als das genommen wird, was sie
    ist: die Bestätigung. Die zweite Möglichkeit ist billiger und ändert
    nichts an einer Datei mit 37 Migrationen.

55. **Das Komplett-Backup kennt keinen scharfen Schnappschuss.**
    Aus S2/AP8. Der Dump entsteht über mehrere Anfragen; ein Lesestand über
    den ganzen Lauf (`--single-transaction`) ginge nur innerhalb EINER
    Verbindung. Eine Zeile, die währenddessen entsteht, kann enthalten sein
    oder nicht. Übersprungen wird nichts, was schon dastand — der Cursor läuft
    über den Primärschlüssel —, aber ein Einsatz, der während des Laufs
    angelegt wird, kann mit Phasen und ohne Spur in der Datei landen.

    In der Praxis ist das verschmerzbar: Wer nachts sichert, hat das Problem
    nicht, und die Uhr liefert Fehlendes idempotent nach. **Zu entscheiden:**
    ob es sich lohnt, den Lauf auf eine Anfrage zu zwingen, sobald die
    Datenbank klein genug ist (dann wäre er scharf), und wo diese Grenze
    läge — oder ob stattdessen die Reihenfolge der Tabellen so gewählt wird,
    dass wenigstens Einsatz und Phasen zusammen fallen.

---


57. **Die Tagesübersicht baut ihre Einsatztabelle ein zweites Mal.**
    *Aufgenommen 02.09.2026 als F-S3-A (S3/AP5).*
    `assets/missiontable.js` führt die Spaltendefinitionen der drei
    Einsatztabellen an **einer** Stelle; `index.php` baut seine Zeilen daneben
    noch einmal selbst zusammen (`tr.innerHTML = …`). Die beiden sind
    auseinandergelaufen: Die Dauerspalte trug in `missiontable.js` seit
    F-N1-G die Klasse `zeit-spalte`, in `index.php` nicht — deshalb brach
    „1h 06min" dort um. **Die Folge ist behoben** (die Klasse steht jetzt in
    beiden), **die Ursache nicht**: Solange es zwei Aufbauten gibt, kommt die
    nächste Änderung wieder nur an einem an, und es fällt wieder erst
    jemandem im Browser auf.
    Nicht in S3 gemacht, weil die Vereinheitlichung Sortierung, Sortierblatt
    und die Kachelform berührt — das ist ein eigenes Paket, kein Nachklapp.

58. **Kein Prüfmittel fragt, ob eine Seite ihr Gerüst hat.**
    *Aufgenommen 02.09.2026 als Lehre aus F-S3-C (S3/AP5).*
    `tag_spuren.php` lief zwei Jahre ohne `ui_geruest_start()`: keine
    Diensttag-Leiste, kein `.rahmen`/`.inhalt` und damit kein seitlicher
    Innenabstand — auf 412 px saß die linke Kante bei 0 statt 12 px.
    **Gefunden hat es ein Mensch auf einem Telefon.**
    Kein Werkzeug konnte es finden, und das ist kein Zufall: Der Bilderlauf
    misst waagerechten Überlauf (`scrollWidth > innerWidth`), und eine Seite
    ohne Innenabstand läuft nicht über — sie ist nur randlos. Die
    Vollständigkeitsprüfung fragt nach Klassen ohne Regel, nicht nach Seiten
    ohne Gerüst. Der Stilvergleich misst Markup-Proben.
    **Die Frage ist am Quelltext zu beantworten:** Jede Seite, die
    `require_admin()` oder `auth_guard.php` einbindet und eigenes Markup
    ausgibt, muss `ui_geruest_start()` und `ui_geruest_ende()` aufrufen. Ein
    kleines Prüfmittel dafür wäre ein Nachmittag und fände die ganze Klasse
    von Fehlern statt eines Falls.


62. **Logodateien tragen teilweise wieder die alten Farbwerte.**
    *Bis zum 02.09.2026 trug dieser Punkt die Nummer 49. Sie war durch die
    Verschmelzung zweier Zweige zweimal vergeben (siehe Kopf dieser Datei);
    umnummeriert wurde die jüngere der beiden Reihen.*
    *Aufgenommen 31.08.2026, gefunden bei der S4-Konzeptarbeit (B-S4-01 im
    S4-Konzept).* Der Commit „Update Logos" hat mit den neuen Vektorvorlagen
    alte Werte zurückgebracht: `gen-em_logo_helicopter.svg` führt `#587abc`,
    `#e3322b`, `#f7941d` und Korpus `#1d0e0a` (statt `#4280E5`, `#D63338`,
    `#FF8F1F`, `#1A0500`); die weiße Hubschrauber-Fassung trägt die alten
    Farbelemente, `gen-em_logo_nef.svg` den alten Korpuswert. Nur
    `gen-em_logo_nef_weiss.svg` stimmt. `docs/Design.md` 2.5 („B1 erledigt,
    nachgemessen") trifft auf diesen Stand nicht mehr zu; PNG-Ableitungen,
    Favicons und Uhr-Bilder sind nicht nachgemessen.
    **Entschieden am 31.08.2026: bewusst liegen lassen** — keine Behebung
    vorab; S4/B1 übernimmt den dann aktuellen Stand der Dateien in die App.
    Bei der Behebung `Design.md` 2.5 mitziehen und alle Fassungen samt
    Ableitungen nachmessen.

65. **Vierzehn Fassungshinweise im Android-Baulauf hängen an einer
    Entscheidung.**
    *Aufgenommen 02.09.2026 als Rest aus B-S4-04 (S4/D1).*
    `lintDebug` meldet für `android/handy/` 14 Warnungen, und sie sind nicht
    vierzehn Entscheidungen, sondern **eine**: `androidx.wear.compose` 1.6.2
    und die Compose-BOM 2026.08.00 verlangen einen neueren Compose-Compiler;
    der hängt an Kotlin, Kotlin 2.4 an AGP 9. Dieselbe Kette ziehen
    `core-ktx`, `lifecycle`, `activity-compose` und die vier
    `camera`-Bausteine.
    **AGP 9 ist ein Umbau der Bau-Sprache** und gehört in eine eigene,
    absichtliche Runde — nicht in eine Korrekturfassung. Stummgeschaltet wird
    nichts (CLAUDE.md 6): Die 14 stehen und werden gezählt; sie sind das
    Preisschild an einer aufgeschobenen Entscheidung, und genau das sollen sie
    sein.

67. **`csrf_check()` hat keinen API-Zweig.**
    *Aufgenommen aus einer Gegenprüfung vom 23.08.2026; die Zahlen sind am
    02.09.2026 nachgezählt (S4/D2).*
    `require_admin()` verzweigt daneben nach `ist_api_aufruf()` und antwortet
    einem Endpunkt mit JSON; `csrf_check()` rendert unbedingt eine HTML-Seite.
    Ein Endpunkt, der sie aufriefe, schickte einer `fetch()`-Anfrage also eine
    Fehlerseite statt eines Fehlerobjekts — die Oberfläche zeigte „unerwartete
    Antwort" statt „Sitzung abgelaufen".
    **Bisher folgenlos, weil es diesen Aufrufer nicht gibt.** Von den 15
    Dateien unter `server/api/` ruft **keine** `csrf_check()` auf. Die **elf**,
    die POST annehmen, prüfen jede selbst gegen `HTTP_X_CSRF` — neun davon
    ändern Zustand (acht schreiben in die Datenbank,
    `adminbackup_freigabe.php` in eine Begleitdatei), die beiden anderen
    (`backup_spuren.php`, `export_data.php`) lesen nur und benutzen POST für
    die Nutzlast. Die übrigen **vier** (`backup_data.php`, `mission.php`,
    `range.php`, `suchindex.php`) sind streng GET-only, weisen alles andere
    mit 405 ab und haben kein Schreib-SQL; ihnen fehlt die Prüfung also nicht.
    Es ist damit eine **unausgesprochene Invariante**, keine Störung — und die
    Nachzählung hat keinen ungeschützten schreibenden Endpunkt gefunden.
    **Zwei Einschränkungen an diesen Sätzen**, aus einer Gegenprüfung vom
    02.09.2026, damit die nächste Zählung nicht darauf hereinfällt:
    `kdf_upgrade.php` prüft erst in Zeile 67 — Zeile 66 steigt für das
    Demo-Konto vorher mit `json_out(['ok' => true, …])` aus. Heute folgenlos,
    weil hinter dem Ausstieg nichts steht; kippt aber, sobald dort mehr steht
    als ein `json_out()`. Die beiden Zeilen gehören getauscht. Und „kein
    Schreib-SQL" gilt für die vier **Dateien**, nicht für die vier
    **Endpunkte**: `auth_guard.php` ruft bei *jeder* Anfrage — GET
    eingeschlossen — `run_cleanup_if_due()` und beim Demo-Konto
    `demo_reset_wenn_faellig()`, und `jobs_lauf()` schreibt dabei
    (`INSERT IGNORE INTO jobs`, `UPDATE jobs`). Ein GET auf
    `api/suchindex.php` kann also die tägliche Wartung auslösen. Das ist
    gewollte Huckepack-Bauweise und harmlos, weil ein Angreifer nichts
    gewinnt, was der nächste Seitenaufruf ohnehin auslöst — aber schreibfrei
    ist der Endpunkt nicht.
    **Zu tun:** entweder denselben `ist_api_aufruf()`-Zweig in `csrf_check()`
    ergänzen, oder die Invariante im Kopf der Funktion festhalten, damit der
    nächste Endpunkt sie nicht versehentlich bricht.
    **Und eine Lehre über die Sache hinaus.** Die ursprüngliche Fassung dieses
    Punktes nannte „alle sechs schreibenden Endpunkte". Am 23.08.2026 war das
    **richtig**: Damals lagen zehn Dateien unter `server/api/`, und genau sechs
    prüften gegen `HTTP_X_CSRF` (`adminbackup_freigabe`, `backup_restore`,
    `day`, `export_data`, `import_commit`, `kdf_upgrade`). In den zehn Tagen
    bis zum Eintragen sind fünf dazugekommen — `backup_eintraege_restore`,
    `backup_spuren`, `backup_spuren_restore`, `gpx_import`, `schneiden` —, und
    alle fünf prüfen ebenfalls. Aus sechs wurden elf. **Eine Zahl in einem
    Backlog-Punkt altert also, während der Punkt liegt**, und sie altert
    lautlos: Nichts an ihr sieht falsch aus. Wer diesen Punkt anfasst, zählt
    vorher wieder nach — die Zählung von heute ist morgen genauso alt.

68. **Vorschlagsfelder über `<datalist>` zeigen auf dem Handy nichts an.**
    *Aufgenommen 02.09.2026 aus einer Rückmeldung des Auftraggebers
    (Rahmenplan Fassung 16).* Die Besatzungsfelder des Diensttags
    (`index.php`, `renderCrewFields()`) bieten die hinterlegten
    Crewmitglieder über ein `<datalist>` an, und dasselbe Muster tragen
    weitere Felder — beobachtet ist der Ausfall an den Crew-Feldern **und**
    an der Zielklinik. Mobile Browser zeigen `<datalist>`-Vorschläge nicht
    oder nur nach Tippen und ohne brauchbare Filterung; die Suche in den
    Stammdaten fällt dort stillschweigend aus, ohne Fehler und ohne Hinweis.
    **Zu tun:** zuerst **alle** Vorschlagsfelder erheben (`grep -l datalist
    server/` nennt `index.php`, `einsatz_form.php`, `mission_fields.php`,
    `ui.php`, `assets/ortsfeld.js`), jedes einzeln am Handy prüfen (Chromium
    mobil und WebKit), dann auf einen Baustein umstellen, der mobil trägt.
    Das Ortsfeld sucht seit S3 beim Tippen mit eigener Trefferliste und ist
    das Muster; ob es selbst noch ein `<datalist>` benutzt, ist Teil der
    Erhebung. Ein neuer Baustein braucht Mockup und Freigabe (`Design.md` 1).
    Zuordnung: Backlog-Runde.

69. **Kurzname je Rettungsmittel als Stammdatenfeld.**
    *Zulieferung aus P3; bis Fassung 16 ohne Nummer im Rahmenplan-Abschnitt
    P4 geführt.* Leiste, Kacheln und Plaketten zeigen den vollen Namen des
    Rettungsmittels; auf schmalen Breiten bricht er um oder wird
    abgeschnitten. Ein Kurzname (etwa „RTH 1", „NEF 2") als eigenes
    Stammdatenfeld würde an diesen drei Stellen verwendet, der volle Name
    bleibt in Formularen und Exporten. Schemaänderung, Feldkatalog, Export,
    Import und Backup ziehen nach — deshalb ein eigener Punkt und kein
    Nebenklapp. Zuordnung: Backlog-Runde.

70. **„Auf der Karte setzen" für Standorte in den Einstellungen.**
    *Zulieferung aus P3; bis Fassung 16 ohne Nummer.* Die Position eines
    Standorts wird über die Ortssuche oder von Hand als Koordinate erfasst;
    das Ortsfeld der Einsätze kann seit P3 die Position auch auf der Karte
    wählen. Dieselbe Kartenwahl fehlt in den Stammdaten der Standorte.
    **Zu tun:** den vorhandenen Baustein des Ortsfelds dort einbinden, kein
    neuer Baustein. Zuordnung: Backlog-Runde.

71. **Regionen mit Unteradmins — verworfen, festgehalten.**
    *Aus dem Dienstbetriebs-Gespräch vom 30.08.2026 (R39); Nummer vergeben
    mit Rahmenplan Fassung 16, wie R39 es vorsah.* Das Alternativmodell zu
    den zentralen Stammdaten: Regionen hängen am zentralen Standort und
    vererben auf alle Untertypen; `user_regions` n:m, weil NotärztInnen in
    mehreren Bereichen arbeiten; Unteradmin als Zusatzbefugnis in eigener
    Tabelle, ausdrücklich ohne Kontoeinblick; null Regionen bedeutet das
    heutige Verhalten. **Verworfen**, weil im Dienstbetrieb jede NutzerIn
    ihre Stammdaten selbst pflegt und die zentralen Stammdaten in P5
    entfallen (R39). Wieder aufzunehmen, falls Wachen oder Verbände als
    organisierte Träger auftreten. Zuordnung: nach v1.0.

72. **Die Richtungspfeile auf der Spur zeigen teilweise in die falsche
    Richtung.**
    *Aufgenommen 02.09.2026 aus einer Rückmeldung des Auftraggebers mit
    Bildschirmfoto (Rahmenplan Fassung 16).* Auf einer Spur, die von Nordwest
    nach Südost läuft, zeigt der Pfeil senkrecht nach oben. **Wahrscheinliche
    Ursache, am Code gelesen und nicht im Browser nachgestellt:**
    `pfeilIcon()` in `assets/geo.js` dreht den Pfeil mit
    `style="transform:rotate(…deg)"` auf einem `<span class="geo-pfeil">`;
    die Regel `.geo-pfeil` in `style.css` setzt nur die Farbe, keine
    Anzeigeart, und die SVG darin ist ebenfalls inline. `transform` wirkt
    nach CSS-Regel **nicht** auf nicht ersetzte Inline-Elemente — die
    Drehung wird verworfen, jeder Pfeil steht ungedreht und zeigt nach
    Norden. „Teilweise falsch" passt dazu: Auf Abschnitten Richtung Norden
    stimmt der Pfeil zufällig. Die Winkelrechnung selbst
    (`atan2` plus 90 Grad) ist richtig. **Zu tun:** `.geo-pfeil` auf
    `display:inline-block` (oder `block`) setzen, dann im Browser über
    mehrere Zoomstufen und Laufrichtungen prüfen; falls der Pfeil danach
    immer noch abweicht, die Rechnung gegen die Projektion nachmessen.
    Prüfmittel: `tools/screenshots/` findet das nicht (misst keinen
    Winkel), eine Sichtprüfung ist Pflicht. Zuordnung: Backlog-Runde.

76. **Der Demo-Reset läuft alle 30 Minuten, auch wenn sich nichts geändert
    hat.**
    *Aufgenommen 02.09.2026 als Frage des Auftraggebers (Rahmenplan Fassung
    16).* `demo_reset_wenn_faellig()` in `demo_lib.php` setzt zeitgesteuert
    zurück — 30 Minuten relativ zur letzten Aktivität, angestoßen von der
    nächsten Anfrage —, ohne zu prüfen, ob eine Besucherin etwas verändert
    hat; `demo_zuruecksetzen()` spielt die Fixture neu ein. **Die Frage:**
    Ist das ein Aufwand, den es sich zu vermeiden lohnt, oder kann es
    durchlaufen? **Zu tun:** zuerst messen — Laufzeit und Last eines Resets
    auf der Produktivinstallation (P-12 hat die Laufzeit einmal geprüft,
    die Zahl ist nicht festgehalten) —, dann entscheiden: durchlaufen
    lassen, oder eine Änderungsmarke (Zähler im Schreibweg des Demo-Kontos,
    Reset nur bei gesetzter Marke). Zuordnung: Backlog-Runde (Messung), die
    Entscheidung danach.

77. **Die Wartungsseite `update.php` in Unterseiten aufteilen.**
    *Aufgenommen 02.09.2026 (Rahmenplan Fassung 16).* Die Seite trägt heute
    die Migrationsliste, den Job-Einstieg mit Cron-Zeile und Token-Adresse,
    die Speichergrenze der Backups und weitere Betriebsangaben auf einer
    Fläche. **Zu tun:** Schnitt im S8-Konzept (etwa Serverbetrieb und Jobs,
    Backup, Migrationen), dabei entscheiden, ob die Migrationsliste
    sichtbar bleiben muss — das hängt am Update-Weg ab v1.0 (R60). Handbuch
    und Technik ziehen nach, die alte Adresse bleibt als Weiterleitung, bis
    P6 neu aufsetzt. Zuordnung: S8.
    **Entschieden 05.09.2026 (Konzept S8, E-S8-05):** Die Seite wird nicht
    aufgeteilt, sondern **aufgelöst**. Der neue Menüblock „Betrieb" trägt
    sieben Seiten — Status, Statistik, Updates, Hintergrundjobs,
    Servereinstellungen, Komplett-Backup, Backup-Ziele —, jede mit *einem*
    Anliegen. Wartungsmodus und ausstehende Migrationen liegen zusammen auf
    **Updates**, weil beide zum Deploy gehören (R66: nur Ausstehende mit
    „Ausstehende ausführen", Ausgeführte bis P5 eingeklappt). Die Karte
    „Logo" zieht nach Verwaltung → Installation (sie ist Gestaltung, keine
    Wartung), die Karte „Einsätze ohne Diensttag" entfällt ersatzlos
    (E-S8-17: das ist Nutzersache und steht als „Zuordnung offen" in der
    Diensttage-Leiste). `update.php` wird Weiterleitung bis P6. Umsetzung in
    S8 AP2 und AP4.
    **Stand 05.09.2026:** Der Web-Teil von `update.php` ist seit Web 15.2.0
    eine **302-Weiterleitung** auf Betrieb → Updates (S8/AP3); der Notausgang
    `php update.php` auf der Kommandozeile bleibt. Offen bleibt allein, dass
    die Adresse überhaupt noch existiert — das räumt P6 (Nr. 77 bleibt
    deshalb offen).

78. **Der Wertekasten zeigt Cron-Adresse und Token in der Schriftgröße des
    Kopplungscodes.**
    *Aufgenommen 02.09.2026 aus einer Rückmeldung mit Bildschirmfoto
    (Rahmenplan Fassung 16).* `.codeblock-wert` (`style.css`) setzt
    `--groesse-5`, 600 und gesperrt — gedacht für sechs Zeichen
    Kopplungscode, benutzt aber auch für die Cron-Zeile und die Token-Adresse
    auf der Wartungsseite, den Setz-Link auf der Kontoseite und die
    Serverschlüssel-Zeile der Backup-Ziele. Lange Werte brechen in dieser
    Größe über mehrere Zeilen und wirken unpassend. **Zu tun:** eine zweite
    Stufe des Bausteins für lange Werte (`--schrift-fest` in `--groesse-2`
    oder `-3`, ohne Sperrung), Herkunft in `Design.md` nachtragen; der
    Kopplungscode behält die große Stufe. Darf als Kleinstkorrektur vorab in
    der Backlog-Runde laufen. Zuordnung: S8.
    **Entschieden 05.09.2026 (Konzept S8, E-S8-10):** zweite Stufe
    `codeblock-lang` — `--schrift-fest` in `--groesse-2`, ohne Sperrung, mit
    Umbruch an beliebiger Stelle —, dazu ein leiser Knopf **„Kopieren"** in
    der Kartenecke, weil lange Werte abgeschrieben Fehler machen. **Fünf
    Stellen, nicht vier** (B-S8-13): Cron-Zeile, Token-Adresse, Setz-Link,
    Serverschlüssel-Zeile — und die Geräte-ID samt API-Schlüssel beim
    Anlegen von Hand, die in der Rückmeldung fehlte. Umsetzung in S8 AP2
    (Baustein, Jobs) und AP6 (übrige Stellen).

79. **Backup-Optionen: Begriffe und Optionen sind gewachsen wie
    Wildwuchs.**
    *Aufgenommen 02.09.2026 (Rahmenplan Fassung 16).* P3 hat Backups je
    Konto und Backup-Regeln auf die Kontoseite gelegt, S2 hat
    Speichergrenze, Warnschwellen, Aufbewahrung, Backup-Ziele, Zeitplan
    und Komplett-Backup dazugebaut, S7 stellt den Begriff um. Was wo
    einstellbar ist und wie es heißt, ist nicht mehr aus einem Guss.
    **Zu tun:** Bestandsaufnahme aller Backup-Optionen mit Fundort,
    Begriff und Zielgruppe; dann eine Ordnung (je Konto gegen je
    Installation, NutzerIn gegen Admin gegen Betreiberin) und ein
    Begriffssatz; Handbuch 6 und `Backup-Format.md` nachziehen. Zuordnung:
    S8, als Kern der Sichtung.
    **Entschieden 05.09.2026 (Konzept S8, E-S8-06; Rahmenplan R77):** drei
    Namen, drei Orte, ein Verb je Weg. **Backup** ist die `.edbak`-Datei der
    NutzerIn (Einstellungen → Backup), **Konto-Backup** das Paket je Konto
    auf dem Server (Verwaltung → Konto-Backups), **Komplett-Backup** der
    Dump der Installation (Betrieb → Komplett-Backup); dazu **Backup-Ziele**
    für den Versand und **Speicher** für Grenze und Belegung aller drei
    (Betrieb → Servereinstellungen, was B-S8-06 auflöst: die Grenze stand
    unter „Backups" und wirkte auch auf die Komplett-Stände). Verben:
    *sichern* fürs Erzeugen, *einspielen* für jeden Rückweg in ein Konto —
    für NutzerIn und Verwaltung gleich —, *wiederherstellen* nur für die
    Installation. Kennzahlen und Filter heißen „Konto-Backup überfällig" und
    „nie Konto-Backup", weil sie genau das messen und nichts über die
    Dateien der NutzerInnen wissen (B-S8-07, jetzt Nr. 117). Umsetzung in S8
    AP2 und AP3.
    **Erledigt mit Web 15.2.0 (S8/AP3).** Die drei Namen stehen in Oberfläche,
    Handbuch 6 und 11 sowie `Backup-Format.md`; „Admin-Backup" und „Wartung"
    als Seitenname sind ausgetragen, auch außerhalb der AP3-Seiten. Nummer
    bleibt bis zum Abschluss der Phase stehen und geht dann nach *Erledigt*.

---

80. **Auswertung der Gerätestatistik — und die zweite Hälfte der Frage.**
    *Angelegt 02.09.2026 als Rest von Nr. 59 (erledigt mit Web 12.9.0). Die
    Speicherung steht; ausgewertet ist nichts.*
    **Vorbedingung: Nr. 83.** Was gespeichert wird, überlebt eine
    Gerätelöschung und eine Wiederherstellung nur zur Hälfte — wer vorher
    auswertet, zählt an einem Bestand, der still ausdünnt.
    Zu tun: eine **Geräteverteilung** je Art und je Modell im
    Betriebslage-Dashboard (R38, P5), gezählt über echte Geräte — ohne das
    virtuelle Gerät `manual-%`, ohne das Demo-Konto.
    Dazu gehört die zweite Hälfte der Frage: **Uhr, Handy oder Sonstiges.**
    Die gekoppelten Geräte melden sich seit Web 12.9.0 selbst; **Rechner**
    erscheinen dagegen nur über die Web-Zugriffe, also über den User-Agent der
    Browsersitzung. Beides muss in derselben Statistik zusammenlaufen, sonst
    zählt man zwei Dinge und nennt sie eins. (Seit S4 gibt es eine
    Handy-**App**, die koppelt — die zählt über `geraet_art = 'handy'` mit und
    ist nicht dasselbe wie ein Handy-Browser.)
    **Vor der Umsetzung zu klären, und zwar zwingend:** Ein Gerätemodell ist
    ein schwaches Merkmal, in einer kleinen Gruppe aber möglicherweise
    identifizierend. Die **Datenschutzerklärung muss die Erhebung benennen,
    bevor sie ausgewertet wird** — bei einer Anwendung, deren Versprechen die
    Ende-zu-Ende-Verschlüsselung ist, gehört das nicht als Nebenprodukt
    eingeführt. Der Text entsteht nach Rahmenplan Schritt 10 aus einer
    Bestandsaufnahme des gesamten Projekts, vor v1.0.
    **Eine Lücke, die die Zählung kennen muss:** Eine Wear-OS-Uhr **koppelt
    nicht** — sie hat weder Serveradresse noch Schlüssel (E-S4-11), das Handy
    koppelt für sie. Eine solche Installation erscheint in der Statistik
    ausschließlich als `handy`; die Uhr dahinter wird nie gezählt. Das ist
    keine Datenlücke, sondern die Bauform — wer die Zahlen liest, muss es
    wissen.
    **Nicht mehr zu tun:** Die Spalten sind da, die Teilenummer wird
    aufgelöst, und die Geräteliste zeigt Art und Modell (Nr. 59).
    **Geteilt am 05.09.2026 (Konzept S8, E-S8-05; Rahmenplan Fassung 28):**
    Der Teil, der **keine** Datenschutz-Vorbedingung hat, zieht nach S8 vor
    — die Seite **Betrieb → Statistik** (AP4) zeigt Konten, Geräte, Einsätze
    und eine Tabelle der **Gerätemodelle** (Hersteller abgeleitet, Anteile,
    CSV), alles ohne Demo-Konto. Bei P5 bleibt, was am Einsatz hängt: die
    **Herkunft je Einsatz** (R64-Werte) und das Betriebslage-Dashboard —
    dafür gilt die Vorbedingung unverändert. Auch die Lücke oben bleibt
    wahr: Die Wear-OS-Uhr koppelt nicht und erscheint als `handy`; die
    Statistik-Seite sagt es dazu.
    **Die Modelltabelle steht** (Web 12.9.1): 325 Teilenummern auf 173
    Modelle, davon 28 keine Uhren. Eine Zählung nach `geraet_art` trägt damit
    — aber nur für Geräte, die **nach** dem Füllen gekoppelt haben. Ältere
    Zeilen tragen die ungeprüfte Selbstauskunft; vor der ersten Auswertung
    deshalb `php tools/geraetemodelle/nachaufloesen.php` fahren.

---

81. **Das App-Symbol wird in der Benachrichtigung falsch dargestellt.**
    *Aufgenommen 02.09.2026 vom Auftraggeber, mit Bildschirmfoto (Android,
    laufende Aufzeichnung, 13:16).* In der Benachrichtigungsleiste erscheint
    die Bildmarke **zu groß und angeschnitten** — der weiße Korpus reicht bis
    an den Rand der Kachel, die farbigen Flächen sitzen links.
    **Was geprüft wurde und stimmt** (damit die Suche nicht dort anfängt):
    Das *Meldungssymbol* in der Statusleiste ist richtig — `symbol_meldung.xml`
    ist ein einfarbiger Aufnahmepunkt, wie Android es verlangt. Das Manifest
    zeigt mit `android:icon` und `android:roundIcon` auf `@mipmap/symbol`. Die
    adaptive Kachel setzt die Marke auf 52 dp Breite in einer 108-dp-Kachel,
    mittig, weiß auf `marke_dunkelblau`; die PNG-Vorlagen haben einen sauberen
    Alphakanal (59 % vollständig durchsichtig).
    **Nachgerechnet:** Die Kachel wurde aus den Quelldateien so zusammengesetzt,
    wie Android sie zeichnet (108 dp, Vordergrund 52 × 33 dp mittig, sichtbarer
    Kreis 72 dp) — das Ergebnis ist richtig: weiße Marke auf Dunkelblau, mit
    Luft zum Rand. **Der Fehler ist aus dem heutigen Quellstand also nicht
    nachvollziehbar.**
    **Zu klären, bevor gesucht wird:** Welche App-Fassung liegt auf dem Gerät
    (Einstellungen → Apps → NAdoku)? Ist es eine ältere als 0.7.7, erklärt das
    den Befund und der Punkt erledigt sich mit der nächsten Auslieferung. Ist
    es 0.7.7, liegt es an einem Zeichenweg, der hier nicht nachgebaut wurde —
    Verdacht dann zuerst auf die `<monochrome>`-Ebene (Android 13+, „Themed
    Icons") und auf die Symbolform des Herstellers.
    **Beantwortet am 04.09.2026:** Die App-Fassung auf dem Gerät war **0.7.7**
    — also *nicht älter* als 0.7.7. Der Punkt erledigt sich damit **nicht** von
    selbst mit der nächsten Auslieferung; er bleibt ein echter Fund. Der Ort
    ist der **Kopf der Benachrichtigung** (One UI zeichnet ihn als runde
    Kachel links), nicht das Symbol in der Statusleiste. Ob „Themed Icons"
    eingeschaltet sind, ist noch offen.

    **Nachgemessen am 04.09.2026 — und eine Vermutung damit widerlegt.** Der
    Verdacht lag auf der `<monochrome>`-Ebene: Sie verweist in
    `mipmap/symbol.xml` auf **dasselbe** Drawable wie der Vordergrund, und
    `marke_luft_weiss.png` ist **nicht einfarbig** — sie trägt vier Farbtöne
    (weiß 54,5 %, rot 23,1 %, blau 13,6 %, orange 8,8 %; „weiß" meint nur den
    Korpus). Android nimmt aus einer monochromen Ebene nur den Alphakanal und
    färbt ihn ein, also müsste daraus ein Klumpen ohne Binnenzeichnung werden.

    **Wird es aber nicht.** Der Alphakanal wurde einfarbig gefüllt und
    angesehen: Die Silhouette ist **erkennbar**, weil die Binnenzeichnung des
    Motivs nicht aus den Farben entsteht, sondern aus **durchsichtigen
    Trennlinien** zwischen den Farbflächen. Sie überleben die Einfärbung. Der
    Verweis ist also unsauber, aber nicht die Ursache des gemeldeten Bildes.

    **Was damit weiterhin fehlt:** ein Beleg, wie One UI den
    Benachrichtigungskopf zeichnet. Der Emulator führt AOSP und beantwortet
    das nicht — er kann nur zeigen, dass es *dort* richtig aussieht.

    **Prüfweg:** Am Gerät ansehen, nicht im Emulator — die Symbolform ist eine
    Herstellereinstellung. Zuordnung: **S4-Rest** (Schritt 6), zusammen mit dem
    Gerätetest auf dem S24.

87. **Die Weboberfläche als installierbare Web-App auf Android.**
    *Aufgenommen 02.09.2026 auf Anweisung des Auftraggebers: vor v1.0
    prüfen, was es braucht, damit Android die Seite aus dem Browser heraus
    als App auf dem Startbildschirm ablegt.* **Stand heute, am Code
    gelesen:** kein Web-App-Manifest, kein Service Worker, kein
    `theme-color`; vorhanden ist allein ein `apple-touch-icon` (`db.php`,
    je Logo-Wahl). Chrome bietet „App installieren" nur an, wenn ein
    Manifest mit Name, Startadresse, `display: standalone` und Symbolen in
    192 und 512 px über HTTPS ausgeliefert wird. Ob zusätzlich ein Service
    Worker Pflicht ist, hat sich in den Chrome-Fassungen der letzten Jahre
    geändert — das ist am aktuellen Stand der Chrome-Dokumentation
    nachzusehen, nicht aus dem Gedächtnis zu beantworten; ohne Service
    Worker gibt es jedenfalls keine Offline-Seite, nur den Eintrag auf dem
    Startbildschirm. **Zu prüfen (Erhebung, ein Nachmittag):** Manifest-
    Felder und Symbolsatz aus den vorhandenen Logos (Rezept wie
    `tools/uhr-bilder/`) · Umgang mit der Logo-Wahl je Profil — ein
    Startbildschirm-Symbol ist fest wie das Launcher-Symbol der Uhr (R47),
    also Vorgabe der Installation · Anmeldung und `sessionStorage`-Schlüssel
    im Standalone-Fenster (ein Tab, ein Schlüssel, R44) · Wirkung auf die
    CSP (Nr. 8) und die Zusage „keine fremde Quelle" · Nachweis mit der
    Installierbarkeits-Diagnose in Chrome und am S24. **Abgrenzung:** Das ist
    nicht die Android-App aus S4. Die zeichnet GPS auf und braucht dafür
    einen Vordergrunddienst (E-R45-5: keine PWA für die Aufzeichnung); die
    Web-App zeigt nur die Oberfläche. Beides nebeneinander ist gewollt.
    **Entscheidung** über Umfang und Zeitpunkt in der Planung v1.0
    (Rahmenplan Schritt 10); Umsetzung dann in P6 oder als Kleinpaket davor.
    Zuordnung: Backlog-Runde (Erhebung), Entscheidung in Schritt 10.

88. **Kachel „Einsätze je Gerät" in der Zeitraumübersicht.**
    *Aufgenommen 02.09.2026 aus der Entscheidung zu Nr. 83 (R64).* Jede
    NutzerIn sieht in ihrer Zeitraumübersicht die eigenen Einsätze nach
    Herkunft: Garmin-Uhr, Handy, Handy mit Wear-Fernbedienung, GPX-Import,
    Schnitt, von Hand — als Kachel neben den vorhandenen Kennzahlen, mit
    Modell als Untertitel, wo eines gespeichert ist. **Neue Darstellung:**
    Mockup und Freigabe nach `Design.md` 1 vor der Umsetzung; Baustein
    `ui_kennzahl` aus P3 als Ausgangspunkt. Braucht die Spalten aus R64
    (Nr. 83, S4-Rest). Zuordnung: Backlog-Runde, nach dem S4-Rest.

---

90. **Der Simulator kann keinen Verbindungsabriss herstellen.**
    *Aufgenommen 03.09.2026 aus S5 Paket C.*
    Der Rundlauf der Uhr-Kopplung sollte sechs Fälle belegen; der sechste —
    „Telefon aus der Reichweite" — ist im Simulator **nicht** herstellbar. Wird
    der Server getötet, sieht die App **HTTP 404**, keinen negativen Code
    (dieselbe Eigenschaft, die `tools/netzprobe/` für den CA-Fehler gemessen
    hat: eine fehlgeschlagene TLS-Verbindung erscheint als 404). Der Zweig
    „`Keine Verbindung (n)`, Code bleibt stehen, Abfrage läuft weiter"
    (E-S5-25) bleibt damit ungeprüft, und er ist kein Randfall — er ist der
    häufigste Fehler im Betrieb.
    **Woran es hängt:** `makeWebRequest` liefert negative Codes nur bei
    Bluetooth-Fehlern, und der Simulator hat kein Bluetooth. Der einzige
    gemessene Weg zu einem negativen Code ist blankes `http://` (−1001,
    `SECURE_CONNECTION_REQUIRED`) — aber der trifft schon `start` und kommt
    nie bis zur Kopplungsansicht.
    **Mögliche Wege:** ein Proxy vor dem Simulator, der die Verbindung
    mittendrin abbricht, und die Frage, was `makeWebRequest` daraus macht;
    oder eine Prüf-Einstellung in der App, die einen negativen Code
    einspeist (dann aber als Fremdkörper im ausgelieferten Code).

91. **Die Auswahl in `WatchUi.Confirmation` ist im Bildabzug nicht zu sehen.**
    *Aufgenommen 03.09.2026 aus S5 Paket C.*
    Beim Rundlauf musste „Nein" auf der Rückfrage ausgelöst werden. Welche der
    beiden Schaltflächen gerade gewählt ist, zeigt der Bildabzug **nicht** —
    `Cancel` und `Confirm` stehen ohne erkennbare Hervorhebung nebeneinander,
    und `Up`/`Down` änderten daran nichts Sichtbares. Gemessen: Ein `Return`
    ohne weitere Taste **bestätigt** (die Vorauswahl steht also auf
    `Confirm`), und BACK räumt den Dialog weg, **ohne** `onResponse` zu rufen.
    **Folge für die Prüfmittel:** Ein Rundlauf, der eine Ablehnung im Dialog
    belegen will, kann sie nicht am Bild ablesen — er muss sie an der Wirkung
    messen (Datenbank: kein Gerät, keine Sitzung). Das ist gemacht, aber es
    gehört aufgeschrieben, damit die nächste Instanz nicht wieder eine halbe
    Stunde an der Tastensteuerung sucht.

92. **`pruefstand.sh bildreihe` fotografiert nur den Startbildschirm.**
    *Aufgenommen 03.09.2026 aus S5 Paket C.*
    Für Stufe II verlangt die Abnahme „je Vertreter ein Bild der `PairView`".
    `bildreihe` lädt die App, wartet und fotografiert — es gibt keinen Weg,
    eine Tastenfolge mitzugeben. Paket C hat sich dafür eine eigene Schleife
    gebaut (zweimal `Down`, weil der erste Druck nach dem Laden regelmäßig
    verlorengeht, dann `keydown Return` / `sleep` / `keyup` für den Langdruck;
    `pruefstand.sh halten` kann nur Maus, nicht Taste — und die
    Drei-Tasten-Geräte brauchen stattdessen Wischen, gelesen aus
    `monkey.jungle`).
    **Vorschlag:** `bildreihe <liste> <ziel> [tastenfolge]`, wobei die
    Tastenfolge eine Zeichenkette wie `Down,Down,hold:Return,wait:8` ist. Dann
    braucht die nächste Ansicht keine eigene Schleife.

93. **`AUTH_VERGLEICHSWERT` trägt Kostenfaktor 10, PHP 8.4 legt 12 an.**
    *Aufgenommen 03.09.2026 aus S5, Vorbereitung V-S5-13.*
    Der feste Vergleichswert, gegen den `login.php` und `auth_salt.php` bei
    unbekannter Adresse rechnen, wurde einmal erzeugt und liegt seither als
    Konstante. Er kostet **57 ms**; ein echter Hash unter PHP 8.4 kostet
    **228 ms**. Der Unterschied ist heute verdeckt, weil `rate_gleiche_dauer()`
    ohnehin auf 0,35 s auffüllt — also ist nichts ablesbar. Verdeckt heißt
    aber nicht beseitigt: Wächst die Mindestdauer nicht mit, wenn die Hardware
    langsamer oder der Kostenfaktor höher wird, wird die Lücke wieder sichtbar.
    **Vorschlag:** den Vergleichswert auf den tatsächlichen Kostenfaktor
    ziehen, sobald keine Installation mehr auf PHP 8.3 läuft — oder
    `rate_gleiche_dauer()` an dieser Stelle auf 0,5 s.

94. **„bitgleich" gegen „pixelgleich" in `tools/uhr-bilder/`.**
    *Aufgenommen 03.09.2026 aus S5, Vorbereitung V-S5-05.*
    Der Kopfkommentar von `erzeugen.sh` sagt, die erzeugten Kacheln seien
    **bitgleich**; die `LIESMICH.md` daneben sagt **pixelgleich**. Beides kann
    nicht stimmen: PNG trägt einen Zeitstempel-Chunk, und der ändert sich bei
    jedem Lauf. Wer die Zusage prüft, prüft je nach gelesenem Dokument etwas
    anderes.
    **Vorschlag:** ein Wort ändern — oder `-define png:exclude-chunk=time`
    setzen und die stärkere Zusage tatsächlich einlösen.

95. **Die Rundlauffälle der Android-App lassen Daten im Admin-Konto zurück.**
    *Aufgenommen 03.09.2026 aus der S5-Vorbereitung, Abschnitt 8.2.*
    Gemessen: **9 Diensttage, 5 Einsätze und 14 439 Spurpunkte**, die kein
    Prüffall wieder abräumt. Sie fallen nicht auf, solange niemand das
    Admin-Konto ansieht — und verfälschen jede Zahl, die jemand daraus zieht.
    **Vorschlag:** Aufräumen im `@After` der betroffenen Fälle, oder ein
    eigenes Prüfkonto, das der Lauf am Ende löscht. Gehört zum S4-Rest, weil
    er dieselben Prüffälle anfasst.

96. **Uhr und Handy sagen nicht, dass gewartet wird.**
    *Aufgenommen 03.09.2026 aus S5, Paket W (E-S5W-08).*
    Der Wartungsmodus antwortet mit **503** und einem `Retry-After`. Die
    Clients behandeln das als gewöhnliches 5xx — sie puffern und liefern
    nach, und genau das ist die Zusage des Vertrags. Was sie **nicht** tun:
    es sagen. Auf der Uhr steht dann derselbe Rückstand wie bei einem
    Funkloch, und `Retry-After` wertet niemand aus.
    **Bewusst nicht in S5 gebaut:** Es hätte eine Uhr- und eine
    Android-Auslieferung gekostet, für eine Lage, die wenige Minuten dauert.
    **Nach v1.0** neu abwägen — dann gibt es mehr als eine Uhr.

97. **Die Browser-Skripte zeigen den Wartungstext uneinheitlich.**
    *Aufgenommen 03.09.2026 aus S5, Paket W (E-S5W-10).*
    Die 503-Antwort trägt ein Feld `meldung`. **`export.js`, `import_ui.js`
    und `schneiden.js`** lesen es aus jeder Fehlerantwort und zeigen es an —
    ohne eine Zeile Änderung. **`kopplung.js`** wirft `'HTTP ' + status`,
    **`unlock.js`, `ortsfeld.js` und `ortswahl.js`** zeigen ihre allgemeine
    Meldung. Wer während einer Wartung eine Adresse sucht, liest also je nach
    Stelle etwas anderes.
    **Bewusst so gelassen:** Drei davon sind Komfortwege, der vierte ist der
    Kopplungstakt, der sich nach drei Fehlern selbst beendet — und während
    einer Wartung koppelt ohnehin niemand.

99. **Fassungsprüfung auf Klick.**
    *Aufgenommen 03.09.2026 aus der Planung v1.0 (Rahmenplan R66, Option A2).*
    Ein Knopf „Auf neue Fassung prüfen" auf der Wartungsseite, der einmalig
    die GitHub-Releases-Schnittstelle fragt — kein Hintergrundlauf, kein
    Banner. Nur, wenn Selbsthoster es verlangen; die eigene Installation
    braucht es nicht, weil Betreiberin und Entwicklung dieselben sind.
    **Nach v1.0.**

100. **Play-API-Upload aus der Auslieferungskette.**
    *Aufgenommen 03.09.2026 aus der Planung v1.0 (Rahmenplan R67).*
    Upload-Schlüssel als GitHub-Secret plus Dienstkonto der Play-API; jeder
    grüne Tag landet von selbst auf dem internen Test-Track, die
    Produktionsfreigabe bleibt ein Klick in der Play Console. Vertretbar,
    weil nach Play App Signing der Upload-Schlüssel der zurücksetzbare ist;
    E-S4-16 dann um den Unterschied App-Signaturschlüssel / Upload-Schlüssel
    ergänzen. **Nach v1.0**, wenn die Releases häufiger werden.

101. **Adresssuche im Kartendialog.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-1), Rahmenplan
    Schritt 8 (S9), R73.*
    Im aufploppenden Kartendialog (Transportziel, Einsatzort usw.) kann kein
    Ort per Adresse gesucht werden. Soll: Adress- und Ortssuche im Dialog;
    ein Klick auf einen Treffer **setzt den Pin**, die Übernahme bleibt ein
    eigener, bestätigender Schritt (F1). Zuerst zu prüfen: die
    Geocoding-Quelle — dieselbe wie die heutigen Adressvorschläge oder keine
    (`CLAUDE.md` 4, Datenschutz). Vorbereitung
    `docs/konzepte/Vorbereitung-S9-Problemsammlung.md`.

102. **Weitere Rettungsmittel: die Auswahl wird nicht übernommen.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-2), Schritt 8 (S9).*
    Die Suche im hinterlegten Stand liefert Treffer; ein Klick schließt den
    Dialog, das Rettungsmittel wird aber nicht in den Einsatz übernommen.
    Bug, nur Desktop/Web (F2).

103. **Kompaktere Buttons Einsatzort, Standort, Zielklinik.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-3), Schritt 8 (S9).*
    Die drei Buttons sollen kleiner werden; Prüfidee: die farbige Umrandung
    der Icons von Standort und Zielklinik als Anzeige Einsatzbeginn/-ende
    nutzen und die separate Anzeige sparen — ob das gestalterisch trägt, ist
    offen; Icon-Größe separat justierbar. Liefergegenstand sind Mockups
    mehrerer Optionen **im S9-Konzept** (Fable-Schritt, F8). Hängt an der
    Bedienhöhe am Schreibtisch (Nr. 74, S8). Offen: F3–F6 (Rahmenplan
    Abschnitt 6).

104. **Windenkacheln fehlen bei Nullwert.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-4), Schritt 8 (S9).*
    In Monats- und Jahresansicht fehlen die Windenkacheln, wenn im Zeitraum
    keine Windeneinsätze geflogen wurden. Soll: Sobald ein Hubschrauber mit
    Winde als Einsatzmittel ausgewählt war, erscheinen die Kacheln — auch
    mit „0" (F7).

105. **Hubschrauber-Icon in der linken Leiste.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-5), Schritt 8 (S9).*
    Das Icon neben den Tagesdaten überzeugt nicht; Varianten entstehen im
    S9-Konzept (Fable-Schritt, F8), nicht vorab.

106. **Klinik- und Adressvorschläge überlagern sich.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-6), Schritt 8 (S9).*
    Beide Vorschlagsarten in **einer** Liste: Kliniken oben, visuell
    abgesetzt, darunter die Adressen. Klinikvorschläge nur im
    Zielklinik-Kontext (F9), höchstens zwei (F10).

107. **Zielklinik per Koordinaten und Karte.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-7), Schritt 8 (S9).*
    An beiden Stellen (Vorbelegung bei den Rettungsmitteln,
    Einsatzbearbeitung) zusätzlich Koordinateneingabe und Auswahl über den
    standardisierten Kartendialog (Nr. 101). Koordinaten einheitlich wie in
    den übrigen Feldern (F11); so gewählte Zielkliniken sind Ad-hoc-Einträge
    je Einsatz, kein Stammdateneintrag (F12). Migration; Vertrag prüfen.

108. **Schloss-Icon und Legende für verschlüsselte Felder.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-8.1), Schritt 8
    (S9).*
    Es ist nicht ersichtlich, welche Felder verschlüsselt gespeichert werden.
    Soll: Schloss-Icon am Feld plus Legende (F13). Getrennt von Nr. 109.

109. **Notizfeld verschlüsseln, Suche bleibt.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-8.2), Schritt 8
    (S9).*
    Das Notizfeld soll verschlüsselt werden und **durchsuchbar bleiben**, wie
    in allen anderen Feldern (F14/F18); Filtern ist nicht nötig. **Offener
    Zielkonflikt, im S9-Konzept als Erstes zu prüfen:** Werden die übrigen
    durchsuchbaren Felder im Klartext gehalten und serverseitig durchsucht,
    ist beides nicht ohne Kompromiss zu haben — dann Optionen mit Vor- und
    Nachteilen, bevor entschieden wird. Betrifft Datenmodell und
    Verschlüsselung (Migration); die Antwort geht in das Bedrohungsmodell
    des R17-Reviews ein (Nr. 43, R69). Fable-Schritt.

110. **Kachel „Spur" heißt „GPS-Daten".**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-9), Schritt 8 (S9).*
    Die Kachel neben „editiert" zeigt z. B. „Spur · 852 Punkte"; „Spur" ist
    schwer verständlich. Soll: „GPS-Daten", die Punktzahl entfällt (F15);
    Wortliste nachziehen.

111. **Neue Rettungsmittel-Arten.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-10.1), Schritt 8
    (S9).*
    Bergwachtnotarzt, Veranstaltungsnotarzt, Sonstiges — mit eigenem Icon,
    ohne Rollen-Vorlagen, ein Standort kann eingegeben werden (F16).
    Migration.

112. **Rettungsmittel ohne Stammdateneintrag in der Tageszuordnung.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-10.2), Schritt 8
    (S9).*
    Ein Rettungsmittel kann in der Tageszuordnung manuell definiert werden;
    es gilt nur für den Tag, die dauerhafte Aufnahme in den Stamm bleibt
    manuell über die Einstellungen (F17). Bedingung: Suche und Filter müssen
    für solche Einträge greifen.

113. **Rollen unmittelbar nach der Auswahl bearbeitbar.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-10.3), Schritt 8
    (S9).*
    Heute muss erst gespeichert und erneut „bearbeiten" geklickt werden,
    bevor Rollen editierbar sind. Soll: sofort bearbeitbar; sind Rollen für
    das Rettungsmittel vordefiniert, werden sie nach der Auswahl automatisch
    nachgeladen. Für manuell definierte Rettungsmittel (Nr. 112) und Arten
    ohne Vorlagen (Nr. 111) entfällt die Rollenbearbeitung (F19).

114. **Abgewiesene Pakete sichtbar machen und ausräumen.**
    *Ergänzung 06.09.2026 (Krypto-Review AN-2):* Die Pakete bleiben samt
    GPS-Spur **dauerhaft** liegen — sie überleben Trennen und Neukopplung,
    und `dienst`-Zeilen werden nie gelöscht (`puffer/Puffer.kt:449-514`).
    Das Sofortpaket Android räumt nach 30 Tagen und beim Trennen; der
    Bedienweg von hier bleibt offen.
    *Aufgenommen 03.09.2026 aus S5 Paket E (B-S5Z-06).* Antwortet der Server
    auf ein Paket mit **400**, wird es im Puffer als `fehlerhaft = 1` markiert
    und damit aus der Warteschlange **und** aus der Anzeige genommen: Die App
    sagt „Alles gesendet", während beim Server ein Segment offen bleibt. Paket
    E2 macht die Zahl sichtbar („N Pakete vom Server abgewiesen"); was fehlt,
    ist ein **Weg damit umzugehen** — ansehen, was drinsteht, ausleiten oder
    verwerfen. Ohne ihn bleibt die Zahl für immer stehen und wird zur
    Tapete. **Zu entscheiden:** ob Ausleiten (als Datei, zum Nachreichen von
    Hand) oder nur Verwerfen mit Rückfrage; Ersteres braucht ein Format,
    Letzteres ist Datenverlust auf Knopfdruck. Zuordnung: Backlog-Runde, nach
    S5.

115. **Die Rundlaufprüffälle räumen ihren hochgeladenen Bestand nicht ab.**
    *Aufgenommen 03.09.2026 aus S5 (Vorbereitung 8.2).* `android/LIESMICH.md`
    verspricht: „Die Fälle räumen hinter sich auf: Was sie koppeln, trennen
    sie wieder." Das stimmt für die **Geräte** und nicht für die **Daten**:
    Nach einem Lauf standen 9 Diensttage, 5 Einsätze und 14 439 Spurpunkte
    zusätzlich im Admin-Konto, und jeder weitere Lauf legt dasselbe noch
    einmal dazu. Das ist kein Fehler der Prüffälle — der Sinn des Rundlaufs
    ist gerade, dass die Daten wirklich ankommen —, aber es heißt: Wer die
    Installation als Ausgangsstand braucht, muss sie neu einrichten, und wer
    Zahlen misst, muss sie vor **und** nach dem Lauf notieren. Die
    `day`-Kennungen sind den Fällen bekannt; ein Abräumen am Ende wäre
    machbar. Zuordnung: Backlog-Runde.

116. **Das Kontrastwerkzeug misst nur, was in seiner Paarliste steht.**
    *Aufgenommen 03.09.2026 aus S5 Paket E1 (B-S5Z-13, B-S5Z-15, B6.2).*
    `android/werkzeuge/kontraste.py` führt eine **feste Liste** von
    Farbpaaren. Ein Paar, das dort nicht eingetragen ist, wird nicht gemessen
    — und meldet folglich auch keinen Fehler. Genau so standen zwei Paare
    jahrelang unter dem Zielwert, ohne dass ein grüner Lauf je etwas anderes
    sagte: der orange Punkt der Zeile „Rückstand N Pakete" (2,23 : 1 gegen
    3,0) und die rote Zeile „wartet aufs Handy" auf der Uhr (4,12 : 1 gegen
    4,5). Beide sind mit E1 behoben und beide Paare eingetragen; die **Lücke
    im Werkzeug** bleibt. **Zu entscheiden:** ob das Werkzeug die Paare aus
    dem Quelltext **ableiten** kann (jedes `color = Farbe.x` innerhalb eines
    Bausteins mit bekanntem Grund) oder ob eine Vollständigkeitsprüfung
    genügt, die meldet, welche Token-Kombinationen im Code vorkommen und in
    der Liste fehlen. Das Zweite ist deutlich billiger und fängt denselben
    Fehler. Dieselbe Frage stellt sich für
    `tools/screenshots/kontrast.py` (Web). Zuordnung: Backlog-Runde.

117. **Niemand weiß, ob eine NutzerIn je ein Backup gezogen hat.**
    *Aufgenommen 05.09.2026 aus dem S8-Konzept (B-S8-07).* Die Kennzahlen
    „Backup überfällig" und „nie gesichert", der Filter der
    NutzerInnen-Liste und die Erinnerungsmail messen **ausschließlich** die
    Konto-Backups der Verwaltung — den Stand des jüngsten Pakets im
    Kontoordner (`edbak_konto_stand()`). Ob eine NutzerIn selbst je ein
    Backup heruntergeladen hat, weiß niemand: Die Datei entsteht im Browser
    und der Server sieht sie nie. S8 hat die Begriffe ehrlich gemacht — die
    Kennzahlen heißen jetzt „Konto-Backup überfällig" und „nie
    Konto-Backup" —, aber die Lücke selbst bleibt. **Zu klären:** ob ein
    Zeitstempel „zuletzt Backup erzeugt" je Konto überhaupt gewollt ist. Er
    wäre eine neue Erhebung über eine Handlung der NutzerIn und keine
    Kleinigkeit; die Alternative ist, es dabei zu belassen und im Handbuch
    zu sagen, dass die Anwendung es nicht weiß. Zuordnung: Backlog-Runde
    (Entscheidung), Umsetzung frühestens P5.

118. **Die Hintergrundjobs lassen sich nur auf der Kommandozeile anhalten.**
    *Aufgenommen 05.09.2026 aus dem S8-Konzept (B-S8-16).* `php jobs.php
    --pause <Minuten>` ist die einzige Job-Handlung ohne Oberfläche. Die
    Seite „Hintergrundjobs" (S8 AP2) zeigt den Pausenzustand an und nennt
    den Befehl, kann ihn aber nicht auslösen. Wer keinen Shell-Zugang hat —
    und das ist auf geteiltem Hosting die Regel —, kann die Jobs nicht
    anhalten, wenn etwas schiefläuft. **Zu tun:** ein Knopf „Jobs anhalten"
    mit Dauerwahl auf derselben Seite, serverseitig derselbe
    `app_state`-Schlüssel. Das ist eine **neue Funktion** und deshalb nicht
    Teil von S8. Zuordnung: Backlog-Runde oder P5.

119. **„Import / Export" ist als Sammelpunkt unvollständig.**
    *Aufgenommen 05.09.2026 aus dem S8-Konzept (B-S8-18).* Der Menüpunkt
    verspricht, alle Wege für Daten hinein und hinaus zu tragen — tatsächlich
    liegt der **GPX-Import je Diensttag** auf der Tagesübersicht (neben
    „Spuren als GPX", E-S4-18) und der Backup-Rückweg auf „Backup". Nach dem
    Ordnungsprinzip (R74, Regel 2) ist das für den GPX-Weg sogar richtig — er
    gehört zu *diesem* Diensttag —, aber dann ist der Name des Sammelpunkts
    zu weit. **Zu klären mit S9**, das die Tagesübersicht ohnehin umbaut: ob
    „Import / Export" enger heißt (etwa „Einsatzliste") oder ob die Seite die
    anderen Wege wenigstens nennt. Zuordnung: Backlog-Runde, mit S9 abstimmen.

120. **Eine Testmail aus der Oberfläche senden.**
    *Aufgenommen 05.09.2026 aus dem S8-Konzept (E-S8-16).* Die Statusseite
    (S8 AP4) zeigt für E-Mail nur, ob SMTP **eingerichtet** ist — ob eine
    Zustellung tatsächlich funktioniert, weiß sie nicht, und ob die letzte
    Zustellung aufgezeichnet wird, war beim Bau zu prüfen. Eine Warnmail, die
    nie ankommt, fällt damit erst auf, wenn jemand sie vermisst. **Zu tun:**
    ein Knopf „Testmail an mich" auf der Statusseite, der über den regulären
    Versandweg geht und das Ergebnis in derselben Zeile zeigt. **Neue
    Funktion**, deshalb nicht Teil von S8. Zuordnung: Backlog-Runde.

121. **Vorschau der Rechtstexte beim Tippen.**
    *Aufgenommen 05.09.2026 aus dem S8-Konzept (Mockup 09); Titel und Text
    berichtigt 05.09.2026 in S8/AP3.* **Eine Vorschau gibt es seit Web
    9.11.0** — sie steht unter dem Feld, entsteht auf dem SERVER mit
    `rt_html()` und zeigt den zuletzt **gespeicherten** Stand. Der Mockup-Text
    hatte sie übersehen; sie ist nicht neu zu bauen. Was fehlt, ist das
    Mitlaufen beim Tippen. **Zu tun:** entscheiden, wie — ein zweiter
    Renderer im Browser ist ausgeschlossen (er müsste dieselbe Positivliste
    für Linkziele, dieselbe Maskierreihenfolge und dieselben Zeichenfilter
    führen, und beim nächsten Fund würde einer von beiden vergessen, E-P3-38);
    bliebe ein Abruf gegen den Server beim Innehalten. **Neue Funktion.**
    Zuordnung: Backlog-Runde.

122. **Freie Zeiträume und Diagramme in der Statistik.**
    *Aufgenommen 05.09.2026 aus dem S8-Konzept (Mockup 04).* Die Seite
    Betrieb → Statistik (S8 AP4) rechnet feste Zeiträume — 7 Tage, 30 Tage,
    6 Monate — und zeigt Zahlen in Tabellen. Für den Blick auf einen
    bestimmten Monat oder auf eine Entwicklung über ein Jahr reicht das
    nicht. **Zu tun:** ein frei wählbarer Zeitraum (Von/Bis wie in der
    Einsatzsuche) und eine grafische Darstellung der Entwicklung. Beides
    sind **neue Darstellungen** und brauchen Mockup und Freigabe
    (`CLAUDE.md` 5); die Diagrammfrage berührt außerdem die Zusage „keine
    fremde Quelle zur Laufzeit" — eine Diagrammbibliothek müsste vendoriert
    werden. Zuordnung: Backlog-Runde oder P5 (Dashboard, R38).

124. **Das Aktionsblatt öffnet weit weg von seinem Knopf.**
    *Aufgenommen 05.09.2026, gemeldet mit Bild von der Auftraggeberin
    (Tagesübersicht am Handy).* Das „⋯" steht oben rechts neben dem
    Seitentitel; das Blatt fährt vom **unteren** Bildschirmrand herein
    (`.blatt{position:fixed;inset:auto 0 0 0}`). Dazwischen liegt der halbe
    Bildschirm, und der Zusammenhang zwischen Knopf und Menü ist nicht zu
    sehen — man sucht die Antwort dort, wo man gedrückt hat.
    **Das ist kein Fehler, sondern eine Entscheidung** (E-P3-27, `Design.md`
    9.12): mobil ein Blatt von unten, ab 1024 px dasselbe Markup als
    Aufklappmenü am Knopf. Das Blatt folgt der Plattformkonvention und liegt
    im Daumenbereich — was bei einem Menü am oberen Bildschirmrand gerade
    nicht der Fall ist. Die Meldung ist damit ein Zielkonflikt, kein Defekt.
    **Drei Wege:** (a) auch mobil am Knopf aufklappen — sichtbarer
    Zusammenhang, schlechter erreichbar; (b) Blatt behalten und den
    Zusammenhang zeigen: das „⋯" bleibt hervorgehoben, solange das Blatt
    offen ist, und das Blatt fährt sichtbar aus seiner Richtung auf;
    (c) so lassen. **Empfehlung: (b)** — behält die Erreichbarkeit, behebt
    das Gemeldete und ist die kleinste Änderung. Alle drei ändern die
    Darstellung eines Bausteins und brauchen Mockup und Freigabe
    (`CLAUDE.md` 5); betroffen ist jede Seite mit `ui_aktionen()` (zehn
    Aufrufe). Zuordnung: Backlog-Runde oder P7 (Gesicht v1.0).

125. **`.form-raster` und `.zweispalter` sind dieselbe Regel unter zwei Namen.**
    *Aufgenommen 05.09.2026 bei S8/AP5 (8).* Beide sind ab 1200 px ein Grid
    mit zwei gleichen Spalten und `align-items:start`; der einzige
    Unterschied ist, dass die Kindelemente einmal `.form-spalte` heißen und
    einmal ein blankes `<div>` sind. `.form-raster` steht auf sechs Seiten,
    `.zweispalter` auf einer (`admin_installation.php`). **Zu tun:** eine
    Regel behalten, die andere austragen — die Seite mit dem blanken `<div>`
    ist die, die umzustellen ist. Das ist keine Gestaltungsänderung: Die
    berechneten Werte sind identisch, der Stilvergleich muss null melden.
    **Warum es nicht in S8 erledigt wurde:** AP5 hat mit `.karten-raster`
    eine dritte Klasse hinzugefügt, die etwas anderes tut (der Browser teilt
    auf, nicht die Seite) — die beiden alten zusammenzulegen wäre eine
    Änderung an sechs Seiten außerhalb des Pakets gewesen. Zuordnung:
    Aufräumpaket P6 oder Backlog-Runde.

126. **Von der Wartungsseite führt kein Weg zurück in die Verwaltung.**
    *Aufgenommen 06.09.2026 bei S8/AP8, aus dem Umschreiben von Handbuch 12.3.*
    Wer sich während des Wartungsmodus anmeldet, landet auf der Startseite —
    und die zeigt die **Wartungsseite** (503). Von dort führt **kein Knopf**
    weiter; der einzige Weg ist, `betrieb_updates.php` von Hand in die
    Adresszeile zu tippen. Das Handbuch hat das bis AP8 anders beschrieben
    („dann bist du wieder auf der Wartungsseite" — richtig, aber es fehlte,
    dass es dort aufhört); jetzt steht die Adresse da.
    **Zu bedenken, und deshalb kein Nebenbei-Bau:** Die Wartungsseite ist
    das, was **jeder Besucher** sieht. Sie entsteht **ohne Datenbank** —
    `wartung_tor()` steht in `db.php` vor jeder Verbindung, und
    `wartung_seite_html()` lädt nichts. Sie kann die Rolle also nicht kennen;
    ein Link stünde für alle da. Das ist verkraftbar (die Adresse steht im
    Handbuch, und die Seite dahinter hat ihre eigene Schranke), aber es ist
    eine Entscheidung, keine Selbstverständlichkeit. **Vorschlag:** eine
    unauffällige Zeile „Verwaltung: betrieb_updates.php" am Fuß der
    Wartungsseite. Zuordnung: Backlog-Runde oder P6.

127. **Anmeldeformular ohne CSRF-Token.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-8).* `login.php:246`
    trägt kein Token; eine fremde Seite kann einen abgemeldeten Browser per
    Top-Level-POST in ein Angreiferkonto anmelden. Patientenfelder sind
    nicht betroffen (kein `edk`, fremde Hülle öffnet nicht), aber Eingaben
    landen im fremden Konto. Die Sitzung besteht beim GET schon
    (`login.php:13`), das Token ist also da. Zuordnung: Sofortpaket
    Sicherheit (R74).

128. **E-Mail-Wechsel im Profil ohne Passwortnachweis.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-7).* `einstellungen.php:92-106`
    schreibt die Adresse allein mit CSRF-Token um — kein `old_token`, kein
    `session_epoch`, keine Mail an die alte Adresse; die Verwaltung kann
    sie ebenfalls ändern (`admin_user.php:127-133`). Die Kette endet im
    Reset-Modus, der den Wiederherstellungsschlüssel braucht — keine
    Offenlegung, aber Kontoübernahme für Klartextfelder und Aussperren.
    Sofortpaket: Nachweis per `old_token` wie beim Passwortwechsel, Hinweismail
    an die alte Adresse bei beiden Wegen; Bestätigung der neuen Adresse
    kommt mit R37.6 in P5. Zuordnung: Sofortpaket Sicherheit (R74), Rest P5.

129. **`apk/` und `demo/` liegen ungesperrt im Webroot.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-9).* `apk.php` verlangt
    die Anmeldung, der Ordner selbst nicht (`apk_lib.php:22`, Dateinamen
    vorhersagbar); `demo/fixture.json.gz` trägt das Schlüsselmaterial des
    Demo-Kontos (öffentliches Passwort, also harmlos, aber unnötig). Anders
    als `sicherungen/` legt kein Code eine Sperre an, und eine Datei in
    `apk/` käme wegen der Deploy-Ausnahmeliste nie an. Zwei
    `RewriteRule`-Zeilen in `.htaccess`. Zuordnung: Sofortpaket Sicherheit.

130. **DOCTYPE-Sperre im GPX-Import umgehbar.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-10).* `gpx_lib.php:332`
    prüft `/<!DOCTYPE/i` auf dem Rohtext; ein UTF-16-kodiertes GPX passiert
    die Regex, libxml versteht es. Folge: interne Entitäten trotz Sperre
    (Billion Laughs), XXE nicht (kein `NOENT`, `NONET`). Nur angemeldet,
    12 MB Grenze. Vor der Regex: gültiges UTF-8 und kein Nullbyte —
    GPX aus Geräten ist UTF-8. Zuordnung: Sofortpaket Sicherheit.

131. **`wiederherstellen.php` gibt unangemeldet Auskunft.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-11).* Zeile 530 zeigt
    den Datenbank-Fehlertext (Rechnername, Nutzer möglich), Zeile 538 die
    Kontenzahl jedem Besucher. Fehlerkennung statt Text, „in Betrieb" ohne
    Zahl. Zuordnung: Sofortpaket Sicherheit.

132. **Klartext-Freitextfelder ohne Hinweis.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-12).* `notes` trägt den
    Placeholder „Freitext (keine Patientendaten!)", `bw_info` („Namen /
    Infos"), die Besatzungs-Freitexte und `days.notes` nicht
    (`mission_fields.php:395,426,459`). Bedienfehler tragen Patientendaten
    in den Klartext. Ein Schlüssel `hinweis` im Feldkatalog, ein Text für
    alle; das Symbol dazu bringt Nr. 108. Zuordnung: Sofortpaket Sicherheit.

133. **Klartext-Reste auf dem Server.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-13).* Während des
    Komplettbackup-Baus liegt `dump.sql.gz` unversiegelt in
    `sicherungen/komplett/.bau-*/`, Reste bis zum nächsten Lauf
    (`komplett_lib.php:53-55,454,471`); Reset-Token bis zur Einlösung in der
    PHP-Sitzungsdatei und im Zugriffslog des ersten GET (M1-06 kennt es);
    bei Mailfehler zeigt die Verwaltung den Setz-Link. Sofortpaket: Bauordner
    nach Fehlschlag räumen; der Rest wird in `Technik.md` benannt und
    bleibt. Zuordnung: Sofortpaket Sicherheit.

134. **Verlorene Uhr kann Phasen alter Einsätze ersetzen.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-14).* Der Geräteschlüssel
    liegt auf der Garmin-Uhr im Klartext (`watch/source/Pair.mc:853`; die
    Plattform hat nichts Besseres). Lesen kann ein Finder nichts —
    `ingest.php` ist POST-only —, aber er kann Einsätze hochladen und
    Phasen bestehender Einsätze ersetzen (`ingest.php:361`), bis das Gerät
    im Web getrennt ist. Was schon geschützt ist: Einsätze mit
    `manual = 1` überspringt `ingest.php` ganz (Z. 251), und Phasen werden
    nur ersetzt, wenn der Upload mindestens so viele bringt (Z. 359).
    **Entschieden (R74):** ein **Zeitfenster ab Einsatzbeginn**, innerhalb
    dessen ein Gerät ersetzen darf; danach `ok` ohne Ersetzen (idempotent,
    kein Fehler auf der Uhr); Neuanlage immer. **Entschieden: 72 h**
    (damit ein Freitagsdienst am Montag noch nachkommt) — Konstante in `db.php`,
    `JSON-Vertrag.md` und Handbuch 12 („Uhr verloren: sofort trennen").
    Zuordnung: Sofortpaket Sicherheit.

135. **Kleinigkeiten an Kopfzeilen und Maskierung.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-15).* `json_encode` in
    Inline-Skripten ohne `JSON_HEX_TAG` (Seitenbruch möglich, keine
    Ausführung, weil `\/` maskiert wird — `ui.php:1928-1940` und drei
    weitere Stellen); `csrf_check()` ohne `(string)`-Cast (`csrf[]=x` →
    500, `auth_guard.php:175`); HSTS ohne `includeSubDomains`, keine
    `Permissions-Policy`; `querySelector` mit Wert aus dem URL-Fragment in
    `suche.php:535` (Bruch, kein XSS). Sofortpaket: die `JSON_HEX`-Vorgabe
    und der Cast; die Kopfzeilen mit der CSP (Nr. 8). Zuordnung:
    Sofortpaket Sicherheit / P5.

136. **Rundenzahl und Passwortregeln.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-3).* Gegen den
    Datenbankabzug ist das Passwort die einzige Schranke, und der Server
    kann seine Qualität nach Bauart nicht prüfen. 320 000 Runden liegen
    unter der Empfehlung von 600 000 (OWASP 2023, Bitwarden); gemessen
    165 → 285 ms je Ableitung auf einem CPU-Kern, für den Angreifer die
    halbe Rate. `KDF_ITER_ZIEL = 600000`, Altwert in der Liste, stille
    Anhebung wie M2-01; `pwquality.js` auf Mindestlänge 12 mit
    Passphrasen-Empfehlung, Sperrliste um naheliegende Muster; der Satz
    zur Bauform ins Handbuch 3.1 und aufs Notfallblatt (R37.11).
    Zuordnung: Sofortpaket Sicherheit.

137. **Photon und Kachelserver bekommen den Einsatzort im Klartext.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-6).* Beim Tippen der
    Adresse geht der Text ab drei Zeichen an `photon.komoot.io`
    (`ortsfeld.js:82,360`), die Umkehrsuche schickt die Koordinate
    (`ortswahl.js:34`), die Kachelserver sehen den Ausschnitt. Nicht der
    eigene Server, aber ein Dritter ohne Vertrag — der Wortlaut „keine
    fremde Quelle zur Laufzeit" (`CLAUDE.md` 4) deckt es nicht. Sofortpaket:
    Hinweis am Feld, Nennung im Datenschutztext, Schalter je Installation
    (die Komponente hat `adresssuche` schon, `ortsfeld.js:118`);
    **Entschieden (F-SP-4): Schalter je Installation, Vorgabe „an".** Selbstbetrieb ist
    die Frage von Nr. 101 (S9 PS-1) mit der Hosting-Entscheidung.
    Zuordnung: Sofortpaket Sicherheit, Rest S9.

138. **Weg C: die Zusage auf das eingrenzen, was sie hält.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-1), entschieden R74.*
    Nur Dokumente, keine Versionsstufe: `CLAUDE.md` 4, `Technik.md` 4.98,
    README, Handbuch 5 und der Entwurf des Datenschutztextes sagen, dass
    Spur, Phasenkoordinaten, Zielklinik, Zeiten und Reanimationsereignisse
    im Klartext liegen und der Einsatzort daraus rekonstruierbar ist (Nr. 43,
    `Konzept-V1-Ortsdaten.md` Weg C). Zuordnung: Sofortpaket Sicherheit.

139. **Adminpakete sind unversiegelt und gehen über FTP hinaus.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-4).* Die Teile des
    Admin-Backups sind blankes JSON im ZIP (`adminbackup_lib.php:404,624`)
    mit allen Klartextfeldern, E-Mail, Name und `pat_wrap_rc`; der Versand
    lässt reines `ftp` zu (`schema.sql:512`) und prüft bei FTPS kein
    Zertifikat (`sicherungsziel_lib.php:31-33`). Die Begründung in
    `Backup-Format.md` 5 („kein Schlüssel, ohne ihn zu speichern") ist seit
    dem Serverschlüssel (Web 12.1.0) überholt. Versiegeln mit
    `sk_versiegeln()` wie das Komplettbackup, `ftp` aus der Auswahl,
    bestehende `ftp`-Ziele mit rotem Hinweis. Zuordnung: **S10** (R74).

140. **Push auf `main` ist Deploy — Zugang zum Repositorium ist Zugang zum Schlüssel.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-16).* Die
    FTPS-Action deployt jeden Push mit Klartext-Zugangsdaten in Secrets;
    jedes Konto mit Push-Recht kann `crypto.js` ändern und Passwörter beim
    nächsten Anmelden abgreifen — der eine Angriff, gegen den keine
    Browser-Verschlüsselung hilft. Das Repositorium ist öffentlich;
    Branch-Schutz, 2FA-Zwang und Umgebungs-Freigaben kosten nichts.
    **Entschieden (R74):** Branch-Schutz und 2FA sofort (Zuarbeit), das
    Deploy-Tor erst mit dem Staging-Aufbau (R40 (2)); die
    **Integritätswache** (tägliche Action vergleicht die ausgelieferten
    Skripte mit dem Release) kommt sofort mit dem Sofortpaket (F-SP-9).
    Zuordnung: Zuarbeit sofort, Sofortpaket Sicherheit (Wache), S10
    (Deploy-Tor mit R40 (2)).

141. **Zweitfaktor für alle Konten.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-5).* Passwort ist
    Anmeldung **und** Datenschlüssel; Phishing genügt für alles. R38 sieht
    TOTP nur für Admin-Konten vor. **Entschieden (R74):** für alle Konten
    angeboten, für Admins Pflicht; Geheimnis serverseitig versiegelt
    (`sk_versiegeln()`), `otpauth://`-Text statt QR-Fremdbestandteil, acht
    Ersatzcodes gehasht, „Gerät 30 Tage merken". Schützt die Anmeldung,
    nicht den Offline-Angriff (dafür S10). Zuordnung: **P5** (erweitert
    R38).

142. **Android: HTTP-Ausnahme gilt auch im Release-Build.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (AN-1).*
    `Serveradresse.kt:108,119` lässt `localhost` und IPv4-Adressen mit
    `http` durch und stuft ein ausdrückliches `https://127.0.0.1/` herab
    (Test `oertlicheAdressenBehaltenHttp`); keine
    Release-`network_security_config`. Auf Android 8.0/8.1 ginge
    `X-Api-Key` bei einer Selbsthoster-Adresse per IP im Klartext; der
    Standardbau ist nicht betroffen. Ausnahme an `BuildConfig.DEBUG`,
    Klartextverbot im Release. Zuordnung: Sofortpaket Android (R74).

143. **Android: Verzicht auf Certificate Pinning ist nicht festgehalten.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (AN-3).* Vertretbar bei
    fester Domain mit rotierendem Zertifikat, aber nirgends entschieden
    (`docs/` und `android/`: kein Treffer). Eine Zeile in
    `android/LIESMICH.md`. Zuordnung: Sofortpaket Android.

144. **Android: Data-Layer-Empfang ohne Absender- und Plausibilitätsprüfung.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (AN-4).*
    `HandyHorcher.kt:30-32` und `Uhrannahme.kt:63-92` prüfen keinen
    `sourceNodeId` und keine Zeitstempel; jede `uhr`-Kennung wird als neue
    Uhr geführt. Kein Abflussweg, nur Störung — das Vertrauen ruht auf der
    proprietären Bibliothek (gleiches Paket, gleiche Signatur). Absender
    gegen die verbundenen Knoten, Zeiten gegen Dienstfenster;
    Robolectric-Prüffall mit Attrappe. Zuordnung: Sofortpaket Android.

145. **Android: Gradle-Wrapper ohne Prüfsumme.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (AN-5).*
    `gradle-wrapper.properties:3-5` ohne `distributionSha256Sum` (begründet
    mit dem gesperrten `downloads.gradle.org` — die Summe wird aber nur
    beim Herunterladen geprüft und stört den Container nicht);
    `gradle-wrapper.jar` im Repositorium unvalidiert. R8 bleibt aus,
    Begründung steht. Zuordnung: Sofortpaket Android.

146. **Fragen an das Bedrohungsmodell P6 aus dem Krypto-Review.**
    *Aufgenommen 06.09.2026 (R74).* Drei Fragen, keine Fehler: **Argon2id
    statt PBKDF2** (WASM-Fremdbestandteil gegen GPU-Resistenz; nach S10
    klein, weil der Abzug allein dann nichts mehr nützt) · **Inhaltsschlüssel
    als nicht-extrahierbarer `CryptoKey`** statt Hex im `sessionStorage`
    (ein XSS könnte dann entschlüsseln, den Schlüssel aber nicht mitnehmen;
    anderes Lebensdauermodell, „ein Tab, ein Schlüssel") · **Passkeys** als
    Zweitfaktor (WebAuthn-Serverbibliothek) und **Passkeys mit PRF** als
    Ersatz der Passwortableitung (Bitwarden seit 2024). Dazu die
    Design-Skizze für Weg B (Nr. 43, SP-9) zur Prüfung. Zuordnung: P6,
    R17 Stück 1.

---

## Erledigt


Die Nummern bleiben, damit ältere Verweise aus Code und Dokumentation weiter
zutreffen.

75. **Die Unterpunkte des Admin-Menüs sind fett und nicht einklappbar.**
    *Aufgenommen 02.09.2026 (Rahmenplan Fassung 16).* S3 (Block F) hatte den
    Fettdruck der Seitenleiste auf den ausgewählten Punkt begrenzt; in der
    Administration (`ui_leiste_einstellungen()`, `.leiste-liste`) erscheinen
    die Unterpunkte weiter fett, und die Überschriften der Gruppen heben
    sich nicht ab. **Zu tun:** nachsehen, ob der Admin-Teil von S3
    ausgenommen blieb oder eine eigene Regel trägt; Fettdruck nur für den
    aktiven Punkt; Gruppen ein- und ausklappbar, Zustand je Sitzung merken.
    Gehört zur Menüstruktur, die S8 ohnehin neu ordnet. Zuordnung: S8.
    **Entschieden 05.09.2026 (Konzept S8, E-S8-07):** Fettdruck nur für den
    aktiven Eintrag; die **drei** Blöcke (Einstellungen, Verwaltung, Betrieb)
    werden auf- und zuklappbare Gruppen — kein neuer Baustein, sondern das
    Akkordeon der Diensttage-Leiste. Zustand je Sitzung in `sessionStorage`;
    der Block der aktiven Seite ist offen, „Einstellungen" immer, ab 1024 px
    alle. Umsetzung in S8 AP5.
    **Erledigt 06.09.2026 (Web 15.4.0, S8/AP5):** Beides. Fettdruck: gemessen
    mit `getComputedStyle` über alle Einträge der Leiste — bei einer
    BetreiberIn mit **17** Einträgen ist **genau einer** fett, bei 1280 und
    bei 360 px, in beiden Rollen. Klappen: drei `<details>` aus dem
    Akkordeon-Baustein, Zustand je Sitzung im `sessionStorage`. Die Vorgabe
    ist gegenüber dem Beschluss geändert und **misst sich**: „Einstellungen
    plus der Block der aktiven Seite" gilt in **jeder** Breite, weil „ab
    1024 px alle offen" den Grund für das Klappen nicht löst (bei 1280 × 900
    blieb die Liste 896 px hoch in einer 783 px hohen Leiste). Begründung im
    Konzept, Abschnitt 11.6.

74. **Bedienhöhe am Schreibtisch: müssen es 44 px sein?**
    *Aufgenommen 02.09.2026 (Rahmenplan Fassung 16).* `CLAUDE.md` 5 und
    `Design.md` verlangen eine Höhe für Bedienelemente, mobil wie am
    Schreibtisch. Am Schreibtisch wirken die Knöpfe hoch. **Zu klären im
    S8-Konzept:** eine zweite Stufe für Zeigergeräte (etwa 36 px, nur über
    `pointer:fine`) mit Begründung, Kontrastprüfung und Nachtrag in
    `Design.md` — oder es bleibt bei einer Höhe. Berührt die Messung
    „Knöpfe ≠ 44 px" in `tools/screenshots/`, die dann zwei Sollwerte
    kennen muss. Zuordnung: S8 (Entscheidung).
    **Entschieden 05.09.2026 (Konzept S8, E-S8-09; Rahmenplan R76):** zwei
    Stufen. 44 px bleibt die Vorgabe; für Zeigergeräte
    (`@media (hover: hover) and (pointer: fine)`, ab 1024 px) gilt 36 px für
    Knöpfe, Felder, Listenzeilen und Menüeinträge. Begründung: Die häufigste
    Arbeit — Einsätze nach der Aufzeichnung ausfüllen — ist Formulararbeit am
    Schreibtisch; 36 px liegt über der Mindestzielgröße von WCAG 2.5.8
    (24 px); ein Touch-Laptop mit Maus als Hauptzeiger bekommt 36, ein reines
    Touch-Gerät 44. Der Kontrast ändert sich nicht — es ist eine Höhe, keine
    Farbe. Die Android-Apps bleiben bei 48 dp (R58). Umsetzung in S8 AP7;
    S9 PS-3 baut darauf auf.
    **Erledigt 06.09.2026 (Web 15.5.0, S8/AP7):** Zwei Stufen, wie
    entschieden. `@media (hover: hover) and (pointer: fine) and
    (min-width: 1024px) { :root { --knopf: 36px } }` — alle drei Bedingungen
    müssen gelten. Gemessen an vier Breiten und beiden Eingabearten: Zeiger
    ab 1024 px durchgehend 36 px, Zeiger darunter und Finger überall 44 px.
    Unverändert, weil eigene Token: Kopfleiste 56, Schalter 46 × 26,
    Aktionsblatt 50 (nur mobil), Suchfeld 48, Sprungmarke 28. Der Bilderlauf
    kennt seither zwei Sollwerte und eine Schaltung `--finger`; er meldete in
    beiden Läufen **0** falsche Höhen. Kein Ziel ist unter 24 × 24 px
    gerutscht: Die Zahl der Elemente unter 24 px ist vor und nach der
    Änderung **identisch** (32 bei 1440 px) — es sind durchweg Links in
    Fließtext, die WCAG 2.5.8 ausdrücklich ausnimmt.

123. **Der Schalter steht zu weit von seiner Beschriftung entfernt.**
    *Aufgenommen 05.09.2026, gemeldet mit Bild von der Auftraggeberin.*
    `.schalter-text` trägt `flex:1 1 auto` und drückt den Griff an den
    rechten Rand der Karte. Auf dem Handy sind das wenige Zentimeter; am
    Schreibtisch liegt zwischen „Mein Kontopasswort verwenden" und dem Griff
    die ganze Kartenbreite, und der Schalter ist dort kaum noch als zu
    dieser Zeile gehörig zu erkennen — man sieht ihn schlicht nicht.
    **Gewünscht:** der Griff **links vom Text** oder **unmittelbar rechts
    daneben**. **Zu bedenken:** Das ist der Baustein, nicht eine Seite — er
    steht an neun Stellen in vier Dateien (`admin_sicherungsziele.php` 4 ×,
    `import.php` 3 ×, `einstellungen.php`, `admin_sicherungen.php`). Eine geänderte
    Darstellung eines Bausteins braucht Mockup und Freigabe
    (`CLAUDE.md` 5) und einen Stilvergleich, weil sie eine Flex-Regel
    verschiebt. **Zuordnung: S8/AP7** — dort wird das Stylesheet für die
    zweite Bedienhöhe (R76) ohnehin angefasst, und dieselben Zeilen sind
    betroffen.
    **Erledigt 06.09.2026 (Web 15.5.0, S8/AP7):** `.schalter-text` trägt
    `flex:0 1 auto` statt `1 1 auto`; der Griff steht damit unmittelbar
    rechts neben der Beschriftung. Von den beiden gewünschten Anordnungen
    ist das die kleinere Änderung — die Leserichtung bleibt Beschriftung →
    Schalter. Gemessen an vier Schaltern auf drei Seiten, Abstand vom Ende
    des Textes bis zum Griff:

    | Stelle | vorher | nachher |
    |---|--:|--:|
    | „Mein Kontopasswort verwenden" @ 1440 | 832 px | **12 px** |
    | „Mein Kontopasswort verwenden" @ 1920 | 1072 px | **12 px** |
    | „Personenbezogene Angaben" (Import) | 763 px | **12 px** |
    | „Mit Passwort schützen" (Import) | 833 px | **12 px** |

    Die **Trefferfläche bleibt die ganze Zeile**: Das `<label>` behält seine
    Breite, nur sein Inhalt rückt zusammen. Gemessen mit einem Klick 200 px
    vom rechten Rand — der Schalter kippt.

    Die beiden Auflagen des Eintrags sind erfüllt: Die **Freigabe** liegt als
    Meldung der Auftraggeberin vor, die beide Anordnungen ausdrücklich
    zulässt; der **Stilvergleich** ist gelaufen und meldet für diese Änderung
    genau eine Eigenschaft an genau einem Selektor (`.schalter-text flex:
    1 1 auto → 0 1 auto`), in allen dreizehn Breiten dieselbe.

73. **Die Filterknöpfe der NutzerInnen-Liste brechen in zwei Zeilen.**
    *Aufgenommen 02.09.2026 aus einer Rückmeldung mit Bildschirmfoto
    (Rahmenplan Fassung 16).* Auf `admin_users.php` stehen die Filter „Alle,
    Admins, Backup überfällig, Nie gesichert, Ohne Gerät" rechts neben
    dem Suchfeld; bei üblicher Schreibtischbreite fällt „Ohne Gerät" allein
    in eine zweite Zeile. **Zu tun:** Anordnung im S8-Konzept festlegen —
    Suchfeld über den Filtern, oder Filter in einer Zeile mit Umbruchregel —
    und am Baustein umsetzen, nicht an der Seite; `tools/screenshots/` in
    allen acht Breiten. Zuordnung: S8.
    **Entschieden 05.09.2026 (Konzept S8, E-S8-08):** Suchfeld in eigener
    Zeile in voller Breite (Höchstbreite 36 rem), Filterplaketten darunter
    mit erlaubtem Umbruch und festem Abstand — dann ist der Umbruch Absicht
    und nicht Unfall. Gilt für jede Liste mit Suche und Filtern. Umsetzung
    in S8 AP6.
    **Erledigt 06.09.2026 (Web 15.4.1, S8/AP6):** Das Suchfeld steht in eigener
    Zeile, in jeder Breite, mit der Höchstbreite `--listensuche-breit` (36 rem);
    die Filterreihe darunter bricht mit festem Abstand. Die Regel `.listenkopf`
    wird ab 1024 px nicht mehr zur Reihe — genau das war die Ursache. Gemessen
    an `admin_users.php` mit fünf Filtern (zusammen 789 px breit):

    | Breite | Inhaltsbreite | vorher | nachher |
    |---|--:|---|---|
    | 1920 | 1354 px | 1 Zeile | 1 Zeile |
    | 1440 | 1114 px | **2 Zeilen** | 1 Zeile |
    | 1280 | 954 px | **2 Zeilen** | 1 Zeile |
    | 1024 | 738 px | 2 Zeilen | 2 Zeilen (4+1) |
    | 900 | 834 px | 1 Zeile | 1 Zeile |
    | 768 | 702 px | 2 Zeilen | 2 Zeilen (4+1) |
    | 360 | 302 px | 4 Zeilen | 4 Zeilen |

    Wo der Inhalt breiter ist als die Reihe, steht sie einzeilig; wo er
    schmaler ist (1024 und 768 — beide unter 789 px), ist der Umbruch die
    richtige Antwort und nicht mehr der halb leere erste Rand von vorher.
    **Die Abnahme P-34 nennt 780 px Inhaltsbreite ohne Umbruch; gemessen
    braucht die Reihe 789 px** — neun Pixel mehr. Diese Breite kommt an keiner
    der acht Prüfbreiten vor.

82. **Es fehlt die Warnung, dass die Daueraufzeichnung den Akku leert.**
    *Aufgenommen 02.09.2026 vom Auftraggeber; erledigt am 04.09.2026 im
    S4-Rest, Paket 3 (Android 0.11.1).*
    **Zwei Orte, weil einer nicht reicht** — von den drei Kandidaten sind (a)
    und (b) gebaut, (c) nicht:

    - **(a) Zweiter Absatz im Akku-Dialog.** Dort steht der Mensch ohnehin und
      trifft gerade eine Entscheidung. Der Text sagt jetzt beides: warum die
      App Strom ziehen *darf* — und dass sie es in erheblichem Maß *tut*.
    - **(b) Einmalig nach dem ersten Dienstbeginn.** Der Akku-Dialog erscheint
      **nur, wenn die Freistellung noch nicht steht**; wer sie vorher gesetzt
      hat, sieht ihn nie und bekommt stattdessen diesen. Zwei getrennte Merker,
      damit der eine den anderen nicht miterledigt.
    - **(c) Laufende Meldung: nicht gebaut.** Sie müsste in allen sechs
      Zustandsfassungen stehen, ist schon lang, und der Satz gehört nicht in
      eine Zeile, die zwölf Stunden lang unverändert dasteht.

    **Nach dem Beginnen, nicht davor** — ein Dialog, der den Start aufhält,
    steht im Weg, wenn es losgeht. Ein Knopf statt zweier: Es gibt nichts zu
    entscheiden, die Aufzeichnung läuft bereits.

    **Keine Zahl.** „Etwa X Prozent" wäre hilfreicher, ist aber ohne Messung am
    Gerät nicht zu verantworten, und der Gerätetest steht aus. Ein geratener
    Wert wäre schlimmer als keiner: Er würde geglaubt.

    **Belegt:** (a) am Emulator, im laufenden Dienst
    (`docs/bilder/s4-rest/07-akku-hinweis.png`). (b) durch drei Prüffälle
    (`VerbrauchhinweisTest`) — **nicht** im Bild: Der Bilderlauf kann keine
    Dialoge (1 dp Inhalt gemessen), und am Emulator hätten drei Bedingungen
    zugleich stehen müssen. Wortliste 0/0/0.

    **Erweitert am 04.09.2026 auf Anweisung des Auftraggebers
    (Android 0.12.0).** Die beiden einmaligen Hinweise sagen das Thema, sobald
    die App eingerichtet ist — also im Januar, gebraucht wird es im Juli.
    Dazu kommt ein **Akkuwächter**, der während des Dienstes mitliest und sich
    bei drei Schwellen meldet: **25 %** (nachladen), **15 %** und **10 %**
    (jeweils mit Knopf „Dienst beenden", derselbe wie in der Dauermeldung).

    Je Stufe einmal, nicht je Messung; am Kabel verschwindet die Warnung und
    die Stufe setzt sich zurück. Gemessen alle zwei Minuten über den
    Sticky-Intent — ein angemeldeter Empfänger für `ACTION_BATTERY_CHANGED`
    weckte den Prozess dutzendfach je Stunde.

    **Die App schaltet nichts ab.** Eine automatische Abschaltung bei X %
    stand zur Wahl und ist verworfen: Sie beendete die Aufzeichnung **still**,
    genau dann, wenn niemand aufs Handy sieht, und was fehlt, lässt sich nicht
    nachtragen. Paket E ist gegen diese Art Stille gebaut.

    **Und kein Sparmodus.** Der GPS-Takt zu strecken stand ebenfalls zur Wahl.
    Dagegen: Der Track **ist** schon ausgedünnt (15 m oder 10 s) — das spart
    Speicher, aber keinen Akku, weil das GPS trotzdem durchläuft. Was spart,
    wäre `MINDESTABSTAND_MS`; wie viel, ist **ungemessen**, die Ausdünnung
    braucht Zwischenpunkte (bei 30 s und 80 km/h greift die 15-m-Regel nie),
    und sie ist wortgleich die der Garmin-Uhr — an der Zahl hängen R19 und der
    Messstand aus S2. Zu entscheiden mit zwei Zahlen aus dem Gerätetest.

    **Belegt:** 14 Prüffälle (`AkkuwaechterTest`) über Schwellen, Hysterese,
    Kabel und Grenzwerte. Die Meldung selbst ist **nicht** im Bild — sie ist
    eine Benachrichtigung, und die zeigt weder der Bilderlauf noch ein
    Screenshot der App.

98. **Versionscode-Versatz für das Uhr-Modul.**
    *Aufgenommen 03.09.2026 aus der Planung v1.0 (R65); erledigt am
    04.09.2026 im S4-Rest, Paket 3 (Android 0.11.1).*
    Die Uhr rechnet `+ 1 000 000` auf den gemeinsamen Code. Am APK
    nachgemessen: Handy **1100**, Uhr **1001100**, beide Versionsname
    `0.11.0` — die Zählung bleibt eine (E-S4-02).

    **Nur die Uhr, nicht beide.** Nr. 98 nannte auch eine führende
    Formfaktor-Ziffer; die hätte das Handy mitverschoben, wo kein Sprung nötig
    ist. Der Versatz trifft das Modul, das ihn braucht.

    **Die Uhr bekommt den höheren Code.** Play fordert nur Eindeutigkeit, aber
    die Wahl ist einmalig: Ein Versatz nach unten könnte mit einer künftigen
    Handy-Fassung kollidieren, einer nach oben nie — 1 000 000 entspräche der
    Handy-Version 100.0.0.

84. **Die Android-App kennt nur `nadoku.gen-em.org`.**
    *Aufgenommen 02.09.2026 (Rahmenplan R63); erledigt am 04.09.2026 im
    S4-Rest, Paket 1 (Android 0.11.0).*
    Adressfeld, Adress-QR und Adresswahl sind ersatzlos entfallen. Die Adresse
    steht als `buildConfigField SERVER_BASIS` im **Bauskript** und nicht als
    Konstante im Quelltext — ein Selbsthoster ändert eine Zeile Gradle, keine
    Zeile Kotlin, und derselbe Schalter führt den Prüfstand auf seine örtliche
    Installation. Die Toleranzregeln aus `Serveradresse` bleiben; sie fangen
    jetzt ab, was jemand ins Bauskript schreibt, und ein Fehler fällt beim
    **Bauen** auf statt bei der Kopplung (`BASIS` wirft).

    **Mitgegangen sind vier Fremdbestandteile und eine Berechtigung.** Ohne
    Adress-QR gibt es keinen Verbraucher mehr für ZXing und die vier
    CameraX-Bausteine; die CAMERA-Berechtigung — die einzige, die die App je
    zur Laufzeit erfragt hat — ist aus dem Manifest ausgetragen. Das APK
    schrumpft dadurch um **1,81 MB** (9 658 567 → 7 844 710 B), die Liste in
    `docs/Lizenzen.md` 6a von vier auf zwei.

    **Eine benannte Ausnahme von „nur HTTPS" (E-S4-14):** `localhost` und
    IPv4-Adressen behalten `http`. Sie taugen ohnehin nicht als Adresse einer
    ausgelieferten App, und ohne die Ausnahme liefe der Rundlauf gegen einen
    TLS-Port, den die Prüfinstallation nicht hat — die App müsste dann einem
    selbstsignierten Zertifikat trauen lernen, und genau das darf sie nie.

85. **Der Name der Handy-App wird „Gen-EM NAdoku".**
    *Aufgenommen 02.09.2026 (Rahmenplan R63); erledigt am 04.09.2026 im
    S4-Rest, Paket 1 (Android 0.11.0).*
    `app_name` im Handy-Modul trägt den vollen Namen, das Uhr-Modul bleibt bei
    „NAdoku". Der Unterschied ist kein Versehen: Auf einem Wear-OS-Zifferblatt
    steht der Name unter einem Symbol von wenigen Millimetern, und von
    „Gen-EM NAdoku" bliebe dort „Gen-EM" stehen — gerade der Teil, der nicht
    sagt, welche App das ist.

    **Am Emulator nachgemessen**, weil die Länge nur an einer Stelle zur Frage
    stand: Die Kopfleiste der Dienstansicht führt den Namen mit, und bei
    360 dp steht er einzeilig neben der 28-dp-Bildmarke, ohne Umbruch und ohne
    Kürzung (Bild `docs/bilder/s4-rest/04-gekoppelt.png`).

86. **Die Statusleiste überlappt den oberen Rand der Handy-App.**
    *Aufgenommen 02.09.2026 am Gerät gemeldet; erledigt am 04.09.2026 im
    S4-Rest, Paket 1 (Android 0.11.0).*
    Die Vermutung bei der Aufnahme stimmte: fehlende Fenster-Insets bei
    `targetSdk 36`. Seit Android 15 zeichnet das System randlos, ohne zu
    fragen; die Leisten liegen über der App.

    **Warum es so lange stand:** `themen.xml` setzte `android:statusBarColor`
    und `android:navigationBarColor`. Beide sind seit API 35 wirkungslos — sie
    taten nichts, sahen aber so aus, als sei die Sache geregelt. Genau das ist
    der Grund, warum niemand nachsah. Sie sind ausgetragen; an ihre Stelle
    treten `enableEdgeToEdge()` in der Activity und Inset-Polster an
    Kopfleiste (`statusBars`) und Wurzelfläche (`navigationBars`).

    **Die Reihenfolge der Modifier ist die Lösung**, nicht ihre Anwesenheit:
    `background` VOR `windowInsetsPadding` färbt die volle Höhe einschließlich
    des Streifens unter der Leiste, das Padding danach schiebt nur den Inhalt.
    Andersherum bliebe ein heller Streifen über der dunklen Leiste. Belegt am
    Emulator (`docs/bilder/s4-rest/01-kopplung-bereit.png`).

66. **Der Garmin-Uhrcode lief nicht durch die Wortliste — jetzt schon.**
    *Aufgenommen 02.09.2026 als Bereich `e` aus B-S4-06 (S4/D1); erledigt am
    03.09.2026 in S5, Paket C (E-S5-40, E-S5-61).*
    Bis hierher prüfte `tools/wortliste/` vier Bereiche und ließ den ältesten
    Client aus. Die frühere Begründung — `watch/` „beschreibe die Garmin-Uhr
    als Gegenstand" — trifft auf `docs/Uhr-Layout_Regeln.md` zu, **nicht** auf
    die sichtbaren Texte der App selbst: Die liest dieselbe Person, die auch
    die Weboberfläche liest.

    **Weiter gefasst als die Aufnahme.** Backlog 66 nannte
    `watch/resources/**/*.xml` — das sind vier Zeichenketten (App-Name und die
    drei Namen der Bildmarken-Wahl). Die eigentlichen Texte der Uhr stehen als
    Literale im Quelltext. Bereich `e` umfasst deshalb **XML und Monkey C**
    (`watch/resources*/**/*.xml`, `watch/source*/*.mc`); ein Bereich, der nur
    die XML angesehen hätte, meldete wieder eine Null über etwas, das er nicht
    gelesen hat — der Fall B-S4-06 selbst.

    **Was dabei entstand:** eine Art `monkeyc` im Zerleger (derselbe Weg wie
    JavaScript — gleiche Kommentarformen, keine regulären Ausdrücke), zwei
    zusätzliche Probefälle dafür, und die Möglichkeit, einem Bereich **zwei
    Arten** zu geben (`{".xml": "xml", ".mc": "monkeyc"}`); eine Endung ohne
    Zuordnung bricht den Lauf ab, statt die Datei still zu übergehen.

    **Ergebnis des ersten Laufs:** 34 Dateien, **2 Treffer**, beide dieselbe
    Sache — `"START"` und `"START halten"` in
    `watch/source-tasten5/DeviceProfile.mc`. Das ist der Aufdruck auf dem
    Gehäuse von Fenix und Forerunner; die Venu 3s heißt dort „Action" und
    trifft nicht. Eine Ausnahme, Klasse G (`uhr-tastennamen`) — genau die
    Trennung, die E-P2-02 vorsieht. Danach **0 / 0 / 0** in allen fünf
    Bereichen.

89. **Der Job „Komplett-Backup der Installation" brach ab, bevor er
    anfing — von Web 12.2.0 bis 12.9.2.**
    *Aufgenommen und erledigt am 02.09.2026 (gefunden in S7 als F-S7-06; der
    Fehler selbst war von der Begriffsumstellung unberührt).*
    `job_komplett()` (`jobs_lib.php`) trug `float $reserve = KOMP_RESERVE_S`
    als **Vorgabewert eines Parameters**. Die Konstante steht in
    `komplett_lib.php`, und diese Datei wird erst **im Rumpf** geladen — so,
    wie `jobs_lib.php` es mit allen schweren Abhängigkeiten hält, damit eine
    gewöhnliche Anfrage sie nicht mitschleppt. PHP wertet Vorgabewerte aber
    **beim Aufruf** aus, also vor der ersten Zeile des Rumpfs. Der Aufruf
    ohne viertes Argument — und genau so ruft `jobs.php` — endete deshalb
    immer in `Error: Undefined constant "KOMP_RESERVE_S"`.

    **Was das gekostet hat:** Das geplante Komplett-Backup lief nie. Der Plan
    („täglich", „wöchentlich", „monatlich") auf
    `admin_komplettsicherung.php` war seit S2/AP8 ohne Wirkung. Von Hand
    angestoßen lief der Lauf, weil `komp_schub()` denselben Vorgabewert erst
    nach dem Laden benutzt — deshalb ist es niemandem aufgefallen. Die
    Wartungsseite zeigte den Job als „Fehler"; auch das hat niemand gelesen.

    **Behoben:** Vorgabewert `?float $reserve = null`, Auflösung auf
    `KOMP_RESERVE_S` im Rumpf **nach** dem `require_once`. Das erhält die
    Bauweise der Datei (späte Ladung) und beseitigt die Ursache.
    **Gemessen am 02.09.2026:** vorher Aufruf ohne viertes Argument →
    `Undefined constant`; nachher → Lauf ohne Fehler. Über `php jobs.php`
    gefahren: `komplett fertig · erledigt 57796`, erzeugte Datei
    814 453 Byte, Wartungsseite ohne Fehlerzeile.

    **Die Fehlerklasse ist nachgezählt:** „Vorgabewert aus einer erst im
    Rumpf geladenen Datei" — in `server/` gibt es genau diese eine Stelle.

59. **Serverseite der Gerätestatistik: `pair.php` nimmt den `geraet`-Block
    entgegen.**
    *Bis zum 02.09.2026 trug dieser Punkt die Nummer 46. Sie war durch die
    Verschmelzung zweier Zweige zweimal vergeben (siehe Kopf dieser Datei);
    umnummeriert wurde die jüngere der beiden Reihen. Erledigt am 02.09.2026
    mit Web 12.9.0 (S6, R42).*
    Die Uhr sendet den Block seit 1.9.0, die Handy-App seit 0.2.0
    (JSON-Vertrag 1a); der Server hat ihn stillschweigend verworfen. Jetzt
    landet er in drei Spalten an `devices` — `geraet_art`, `geraet_modell` und
    `geraet_teil` —, die Teilenummer wird über `server/geraetemodelle.php`
    aufgelöst, und beide Gerätelisten zeigen Art und Modell.
    **Zwei Abweichungen von dem, was hier stand, beide bewusst:**
    Erstens **keine Displaymaße, keine Firmware, keine Plattform- und
    App-Fassung.** Sie kommen an und werden verworfen: R36 lässt die
    Gerätekennung als die eine benannte Ausnahme zu, und die Ausnahme ist die
    Frage „welches Gerät", nicht „in welchem Zustand".
    Zweitens **drei Spalten statt zwei** (R42 nennt Art und Modell): Die
    Rohangabe steht daneben, weil die Modelltabelle nur kennen kann, was es
    beim Erzeugen schon gab — ohne sie fiele ein künftiges Gerät dauerhaft
    und unwiederbringlich auf „unbekannt".
    **Der Rest — Auswertung und die User-Agent-Hälfte — steht als Nr. 80.**

64. **Die Bedienhöhe steht auf 44 px, Android verlangt 48 dp.**
    *Aufgenommen 02.09.2026 als B-S4-02 (S4/D1); erledigt am selben Tag mit
    der Entscheidung R58.*
    `CLAUDE.md` 5 sagte: „Eine Höhe für Bedienelemente: **44 px**, mobil wie
    am Schreibtisch." Androids eigene Vorgabe für Berührziele ist **48 dp**.
    Die vier Pixel klingen nach nichts und sind es nicht: Diese App wird **mit
    Handschuhen im Einsatz** bedient, und das ist genau der Fall, für den die
    48 dp gedacht sind. Der eigentliche Befund war nicht die Zahl, sondern die
    **Uneinigkeit**: Die Wear-OS-App hielt längst 48 dp, weil sie die
    Wear-Bausteine benutzt — dasselbe Programm führte an derselben Stelle zwei
    Maße.
    **Entschieden als R58** (02.09.2026): Die 44 px gelten für die
    Weboberfläche, die Android-Module folgen ihrer Plattform. Umgesetzt ist es
    an einer Stelle — `BEDIENHOEHE` in `handy/…/Bausteine.kt` steht auf
    `48.dp`; `UHR_BEDIENHOEHE` stand schon dort. `CLAUDE.md` 5 sagt die
    Unterscheidung jetzt selbst, damit die nächste Instanz nicht dieselbe
    Frage noch einmal aufwirft.

56. **Die zweite Rückmeldungsrunde steht in keinem Konzept.**
    *Aufgenommen 01.09.2026 mit Web 12.2.1; der Prüfteil ist am selben Tag
    erledigt worden, der Konzeptteil mit S3/AP1 (02.09.2026).*
    Beide Oberflächenpunkte (Dateifeld mittig, Dateiname in den
    Abschlussmeldungen) stehen nicht in `ToDo_Layout.pdf` — die Liste hat 19
    Punkte am Stand Web 9.14.1, diese sind neuer. Sie gehören ins S3-Konzept
    nachgetragen, damit S3 sie als erledigt vorfindet und nicht ein zweites
    Mal beschließt; R43 verlangt ausdrücklich, dass eine Einzelkorrektur an
    einer Seite im Konzept benannt wird.
    **Erledigt ist die Bedienprüfung** (01.09.2026): beide Dateifelder,
    Sicherung, Export über alle drei Downloadwege, der `warn`-Fall mit
    Gegenprobe, Bilderlauf (304 Bilder, 0/0/0) und Kontraste (21 Paare, 0
    verfehlt) — Einzelheiten im Changelog zu 12.2.1.
    **Und eine Lehre, die über diesen Punkt hinausgeht:** Die Prüfung war nur
    deshalb offen, weil „ohne MySQL nicht aufrufbar" angenommen statt
    nachgesehen wurde. Das Projekt fährt lokal seit P1 gegen MariaDB 10.11,
    und `tools/referenzdatensatz/einspielen/lokal_starten.sh` setzt die
    Installation in einem Aufruf auf. Wer eine Prüfung für unmöglich hält,
    sehe zuerst in `tools/` nach.
    **Erledigt mit S3:** Beide Punkte stehen als Abschnitt 1.13 im
    Konzept `docs/konzepte/erledigt/Konzept-S3-Oberflaechen-Nacharbeit.md` — die Umsetzung hat
    sie damit als erledigt vorgefunden und nicht ein zweites Mal
    beschlossen.


2. **Serverseitige Track-Vereinfachung (Douglas-Peucker) für die
   Web-Darstellung.**
   *Erledigt mit Web 10.2.0 (S2/AP3).*
   Umgesetzt nicht als Vereinfachung für die Anzeige, sondern als **Ausdünnung
   des Bestands**: Douglas-Peucker dreidimensional, 2 m waagerecht / 3 m
   senkrecht als getrennte Toleranzen, sechs Monate nach Einsatzende, mit
   Schutz des ersten und letzten Punktes und je Phasenzeitpunkt des
   zeitnächsten (E-S2-05).

   Damit ist der Punkt für Bestände ab sechs Monaten gegenstandslos: Es gibt
   nichts mehr zu vereinfachen, die Spur ist es schon. Für **frische**
   Einsätze braucht es keine zusätzliche Vereinfachung — die Tagesansicht
   liegt bei 6 000 bis 10 000 Punkten, unter 1 ms Dekodierzeit, und Leaflet
   ist damit unkritisch (E-S2-09). Der ursprüngliche Anlass, „die Karte wird
   langsam", tritt also nicht ein.

   *Gemessen:* 156 Referenzspuren mit 47 078 Punkten, **0 Verletzungen** der
   Zusage von 2,0 m / 3,0 m, unabhängig gegen den endgültigen Streckenzug
   nachgemessen; am Messstand 4973 Spuren in 15,2 s. Gegenprobe zur
   Notwendigkeit der zweiten Toleranz: rein zweidimensional ausgedünnt liegt
   der schlimmste verworfene Punkt **82,76 m** neben dem Höhenprofil.

3. **GPX-Export** (Datenmodell dafür vorbereitet: lat/lon/ele/ts je `seq`).
   *Erledigt mit Web 10.3.0 (S2/AP4).*
   Umgesetzt als **Abruf je Spur**, nicht als weiteres Exportprofil: ein
   Eintrag im Aktionsmenü der Einsatzansicht und die Seite „Spuren des
   Diensttages" (`tag_spuren.php`), die Einsätze **und Ruhesegmente**
   chronologisch auflistet, mit der Karte verknüpft und einzeln
   herunterladbar macht. Wer mehrere ankreuzt, bekommt sie als **eine** Datei
   mit mehreren `<trk>` — zusammengeklebt würde jedes Kartenprogramm eine
   gerade Linie vom Ende der einen Spur zum Anfang der nächsten ziehen. GPX
   gab es vorher nur als Beiwerk im großen Export, im Browser
   zusammengesetzt.

   Zwei Entscheidungen dabei: Die Datei entsteht **serverseitig** — die erste
   des Projekts, denn alle übrigen Downloads sind Ende-zu-Ende verschlüsselt
   und können nur im Browser entstehen; hier ist es umgekehrt, und ein
   serverseitig gebauter Dateiname kann keine geschützte Angabe tragen. Und
   die Kennzeichnung Original/ausgedünnt (E-S2-09) steht an **drei** Stellen:
   in der Datei, im Dateinamen und auf der Seite.

   *Gemessen:* `tools/gpxprobe/` — 47 Erwartungen, 0 nicht erfüllt; gültig
   gegen das amtliche GPX-1.1-XSD; **174 804 Einzelvergleiche** Punkt für Punkt
   gegen die browsergebauten Referenzdateien, 0 Abweichungen.

39. **Klassen im Markup ohne Regel im Stylesheet — 29 Stück.**
    *Erledigt mit Web 9.13.0 (P3/O12).* Der Punkt war eine Frage nach dem
    **Prüfmittel**, nicht nach dem Stylesheet: Die Gegenprobe „im Markup, aber
    ohne Regel" hatte in O11 einen echten Fund gemacht (der Export-Knopf mit
    `btn-primary`, 23 px statt 44 — F-P3-BA), und dieser eine Fund stand
    zwischen 28 falschen. Eine Liste in diesem Mischungsverhältnis wird nach
    dem dritten Mal nicht mehr gelesen, und dann findet sie auch den echten
    nicht.

    Es gibt jetzt `tools/vollstaendigkeit/ohne-regel.md` nach dem Muster der
    Streichliste: `[bleibt]` für die begründeten Fälle (acht Bruchstücke
    zusammengesetzter Klassennamen, fünfzehn Skriptanker und Behälter — jeder
    mit Begründung und Fundstelle), `[offen]` für die ungeklärten. Die
    Prüfung meldet dadurch **0** ohne eingetragenen Grund statt 29, führt die
    sechs offenen unter eigener Überschrift — und meldet **ihre eigenen
    verwahrlosten Einträge**: Wessen Klasse inzwischen eine Regel hat oder aus
    dem Markup verschwunden ist, steht als „Eintrag ungenutzt" da. Ohne diese
    Rückfrage wäre die Liste in zwei Paketen dasselbe geworden, wogegen sie
    schützt. Die Gesamtzahl der Befunde fiel von 247 auf 224.

    Der Rest — die sechs `[offen]` — steht als **Nr. 41**.

18. **`.btn-link.danger` in `style.css` kann nie greifen.**
    *Erledigt mit Web 9.1.0 (P3/O2).* Die Regel konnte nie greifen, weil
    `btn-link` nur in `install.php` vorkam — und diese Seite lud `style.css`
    gar nicht, sondern brachte ihre Gestaltung im Kopf mit. Der Punkt war als
    Frage gestellt: streichen, oder die Regel dorthin ziehen, wo sie wirken
    würde?

    Beantwortet hat sie das Konzept P3 anders und größer (E-P3-02): Der
    Einrichter bekommt das **gemeinsame Stylesheet**. Sein eigener Stil mit 17
    Hexwerten, zwei Schriftgrößen und vier eigenen Klassen ist entfallen;
    Knöpfe und Meldungen kommen aus den Bausteinen, und er hat zum ersten Mal
    eine Fußzeile. Die Begründung des Sonderwegs — „er soll auch dann bedienbar
    aussehen, wenn am Stylesheet etwas fehlt" — hat der Praxis nicht
    standgehalten: Er war die einzige Seite, die bei einer Farbänderung nicht
    mitzog, und das Stylesheet liegt im selben Verzeichnis. Fällt es aus, ist
    die Anwendung ohnehin nicht eingerichtet.

20. **13 Hexwerte in `style.css` durch das vorhandene Token ersetzen.**
    *Erledigt mit Web 9.0.0 (P3/O1) — und zwar nicht 13, sondern alle 78.*
    Der Punkt war klein gefasst, weil er das Redesign nicht vorwegnehmen
    wollte: 13 Werte hatten ein Token mit exakt demselben Wert, die übrigen 65
    zu benennen wäre eine Gestaltungsentscheidung gewesen. Genau die hat P3
    getroffen. Das Stylesheet ist neu geschrieben; außerhalb von `:root` steht
    **kein einziger Hexwert mehr**, dazu keine `rgb()`-Angabe, keine
    Schriftgröße außerhalb der Skala und kein Pixelmaß außerhalb der Token.
    Nachgezählt von `tools/vollstaendigkeit/pruefen.py`, das dieselbe Zahl
    weiterhin bei jedem Lauf prüft — der Punkt kann also nicht stillschweigend
    zurückkommen. Die markenfremden Familien (Grün, Gelb, die zweite
    Graufamilie) sind dabei ersatzlos entfallen.

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

13. **Kosmetik Uhr-Code: Typprüfer-Warnungen („container access") auflösen.**
    *Erledigt mit Uhr 1.8.1.* Stand bis Web 5.4.0 irrtümlich als zweite
    Nummer 5 in dieser Liste; ältere Verweise auf „Nr. 5b" meinen diesen Punkt.
    Der Bau meldete **29 Warnungen**, davon 28 „Cannot determine if container
    access is using container type" in `ClockView.mc`, `CprView.mc`,
    `Model.mc`, `Track.mc` und `Uploader.mc`, dazu eine nicht erreichbare
    Anweisung. Die Ursache war überall dieselbe und harmloser als der Wortlaut
    vermuten lässt: Arrays waren als `Lang.Array` **ohne Elementtyp**
    deklariert, weshalb der Prüfer bei `items[i][2]` nicht wusste, ob das
    innere Ding überhaupt indizierbar ist. Die Zusicherungen auf den Einzelwert
    (`as Lang.String`) standen längst da — es fehlte nur die Angabe am Behälter.
    Ergänzt wurden `Lang.Array<Lang.Array>` für die Tupellisten
    (Menüeinträge `[Label, Farbe, ID]`, Phasen, Reanimationsereignisse) und
    `Lang.Array<Lang.Dictionary>` für die beiden Warteschlangen in `Model`.
    Zwei Stellen brauchten mehr als eine Zeile. Der Punktpuffer in `Track`
    heißt jetzt `Lang.Array<Lang.Numeric or Null>` und **nicht**
    `<Lang.Number>` — der erste Versuch mit `Number` erzeugte drei
    Übersetzungsfehler, weil dort Breite und Länge als `Double`, die Höhe als
    `Float` und der Zeitstempel als `Number` nebeneinander liegen; die Höhe
    kann fehlen. Und die lokale Variable `chunk` ließ sich nicht annotieren
    („Local variable types are inferred"), weshalb die Zusicherung an die
    Zuweisung aus `Storage.getValue()` wanderte.
    Die nicht erreichbare Anweisung war ein `return true;` hinter
    `System.exit()` in `StartView.actBack()`. Es ist entfallen; ein Kommentar
    hält fest, warum dort keines steht.
    Ergebnis: **0 Warnungen, 0 Fehler** auf allen drei Zielgeräten, und die
    Kompilate sind dabei 16 bis 32 Byte **kleiner** geworden — die Typangaben
    kosten zur Laufzeit nichts. Geprüft mit `tools/uhr-pruefstand`.
    *Fortgesetzt mit Uhr 1.8.2:* Die strenge Typprüfung `-l 3` meldet statt
    **226** noch **4**. Die erste Zahl war irreführend — eine einzelne Zeile
    erzeugt bis zu 16 Meldungen, weil der Prüfer jeden Typ des Sammeltyps
    einzeln durchgeht; nach Fundstelle gezählt waren es **77 Stellen**. Drei
    Muster erklärten fast alles: Zuweisungen aus `Storage.getValue()` (dessen
    Sammeltyp alles Speicherbare umfasst), die Null-Flussanalyse (sie greift
    **nur über lokale Variablen**, nicht über ein Modul-Feld hinweg) und
    fehlende Parametertypen. Anders als der erste Teil kostet das Platz:
    **+448 Byte** (fenix6pro, fr945) bzw. **+480 Byte** (venu3s). Die vier
    verbliebenen Stellen sind alle `Storage.setValue()` mit Dictionary oder
    Array. Mit erledigt: `Input.lPageDown()` und
    `L_PAGE_DOWN` waren toter Code und sind entfallen, und die Wisch-Kommentare
    an `CprView.onPreviousPage/onNextPage` waren vertauscht.
    *Abgeschlossen mit Uhr 1.11.0:* **0 Meldungen bei `-l 3`, auf allen 99
    Geräten.** Die vier galten als „nicht auflösbar, ohne die Datenstruktur zu
    ändern" — das war falsch. Sie brauchen keine neue Struktur, sondern einen
    **Cast auf die gemeinte Alternative des PolyType**: `Storage.setValue()`
    und `makeWebRequest()` nehmen bis zu 16 Typen, ein Literal hat aber einen
    genauen, und die Prüfung sieht den Sonderfall nicht. Eine der vier war
    sogar eine Verwechslung — `Track.mc` **hatte** einen Cast, nur auf
    `Application.PropertyValueType` statt `Storage.ValueType`; ein falscher
    Cast prüft nichts. Kosten: **0 Byte**, gemessen (Casts sind reine
    Übersetzungsangelegenheit). Der Weg war 226 Meldungen / 77 Stellen → 4 → 0.

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
    `docs/konzepte/erledigt/Pruefung-Sofortpaket-22.md`. Dabei fiel allerdings `edk_neu` auf —
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

24. **`export_csv_v1` ist bei führendem `=` nicht verlustfrei — und
    `Export-Format.md` 5.1 sagt, es sei es.**
    *Erledigt mit Web 8.0.0 — dokumentiert, nicht geändert.* Der Vorschlag des
    Eintrags war genau das, und er bleibt richtig: Ein Import, der einen
    führenden Apostroph entfernt, schafft den nächsten stillen Verlust — ein
    echtes `'` am Textanfang verschwände. `Export-Format.md` 5.1 führt den
    Apostroph jetzt als **Ausnahme 6** von sechs, mit Messzahl.
    *Gemessen:* 3 Zellen (zwei `notizen`, ein `other_ema`). Im Bestand des
    Umlaufkontos tragen danach 3 Werte den Apostroph, im Referenzkonto 0.
    Der Kreislauf **sieht** die Abweichung nicht: Der nächste Export fügt
    keinen zweiten Apostroph hinzu, die Datei sieht unverändert aus, während
    der gespeicherte Wert ein Zeichen länger geworden ist. Genau deshalb
    gehört sie in die Dokumentation und nicht in die Ausnahmeliste des
    Vergleichswerkzeugs.

25. **`missions.created_at` wird gesichert, kommt beim Einspielen aber nicht
    zurück.**
    *Erledigt mit Web 8.0.0 — mitschreiben, nicht streichen.* Die Spalte steht
    jetzt als benannte Ausnahme neben `start_src` und `pat_blob` in
    `edbak_restore()`. Ein unbrauchbarer Wert lässt die Spalte **weg** statt
    `NULL` zu schreiben: Dann greift die Vorgabe der Datenbank und die Zeile
    bleibt — ein Komfortwert darf eine Wiederherstellung nicht kosten.
    Die Entscheidung fiel gegen „aus der Sicherung streichen", weil eine
    Sicherung ein Abbild sein soll und `created_at` eine Angabe ist wie jede
    andere, wenn auch keine fachliche.
    *Gemessen:* 87 von 87 Einsätzen tragen nach dem Umlauf denselben Wert wie
    vorher (83 verschiedene Werte auf beiden Seiten). Dazu die Ursache, warum
    es so lange unbemerkt blieb: Das Vergleichswerkzeug normalisierte
    `missions[].created_at` weg — diese Normalisierung ist aufgehoben, und die
    zugehörige Probe aufs Exempel ist von einer **Gegen**probe zu einer
    Hinprobe geworden.

27. **Mehrzeilige Notizen verlieren beim CSV-Import ihre Zeilenumbrüche.**
    *Erledigt mit Web 8.0.0.* Neuer Parser `trimMehrzeilig` für alle drei
    Notizspalten (`notizen` im CSV-Profil, `Notizen` in beiden Excel-Profilen):
    zusammengezogen wird nur **innerhalb** einer Zeile, Zeilenenden werden
    vorher vereinheitlicht. Leerzeilen am Anfang und Ende fallen weg, die in
    der Mitte bleiben — sie sind Gliederung, kein Rest. Bei einzeiligen Werten
    ist das Ergebnis identisch zu `trim`, die Grenze bleibt `max:2000`.
    *Gemessen:* 4 Notizen mit je einem Umbruch, 164/253/119/150 Zeichen, nach
    dem Umlauf wörtlich gleich.

28. **`final = 0` und ein leeres `ende` werden beim CSV-Import
    überschrieben.**
    *Erledigt mit Web 8.0.0.* Beides kommt jetzt aus der Datei — in INSERT und
    UPDATE. Der Kern ist eine Unterscheidung, die es vorher nicht gab: **eine
    fehlende Spalte ist etwas anderes als eine leere Zelle.** Fehlt die Spalte
    im Profil (Jahresliste, Excel), bleibt es beim bisherigen Verhalten
    (`ende = Beginn`, beim Anlegen `final = 1`, beim Überschreiben `final`
    unangetastet); ist die Zelle leer, ist das eine Aussage. Der Browser sendet
    `ended_utc` und `final` deshalb nur, wenn das Profil die Spalte führt —
    welche Zielfelder eine Datei führt, sagt seither `verarbeiteMatrix()`.
    *Gemessen:* Der Referenzfall (2026-07-05, 19:40) übersteht den Umlauf mit
    `final = 0` und leerem Ende, auch im Überschreiben-Modus über alle 82
    Zeilen. Gegenprobe Excel: 82× `final = 1`, 82× `ende = Beginn` — das
    bisherige Verhalten, unverändert.

29. **`docs/Export-Format.md` 5.1 zählt drei Ausnahmen auf; es sind mehr.**
    *Erledigt mit Web 8.0.0.* Der Abschnitt zählt jetzt **sechs**, jede mit
    Messzahl: die drei alten (Kennungen, GPX, Rettungsmittel/Standort) plus
    Ruhesegmente (95 → 0), zweiter Dienst eines Kalendertags (15 → 13
    Diensttage) und der Formelschutz-Apostroph (Nr. 24). Die Überschrift heißt
    jetzt „verlustfrei **für Einsätze**" — sechs Ausnahmen sind kein Grund, das
    Wort zu streichen, aber einer, es einzugrenzen. Dazu in Abschnitt 6: Der
    Papierkorb ist in keinem Exportprofil enthalten, und seit dieser Fassung
    ist das ein Unterschied zur Sicherung.

30. **Den Papierkorb in die Sicherung aufnehmen — NutzerInnen- und
    Admin-Sicherung.**
    *Erledigt mit Web 8.0.0, Phase S1.* Die drei Filter in `edbak_build()` und
    der Parameter `$mitPapierkorb` sind entfallen; die Nutzlast steht auf 7.
    Der Rückweg wertet `deleted_at` und `deleted_with_day` aus und bringt den
    Papierkorb **als Papierkorb** zurück. Die drei offenen Punkte des Eintrags
    sind so entschieden worden:
    - **Frist:** Übernommen wird der Zustand, nicht der Zeitpunkt. Alle
      Einträge eines Einspielvorgangs tragen denselben `deleted_at`, die
      90 Tage beginnen neu. Sonst brächte eine ältere Sicherung Einträge mit
      abgelaufener Frist mit, die der nächste Aufräumjob endgültig entfernt.
    - **D1:** Die Datumsprüfung gegen den Papierkorb des Zielkontos gilt nur
      noch für **aktive** Datei-Tage. Ein in der Datei gelöschter Tag
      durchläuft die normale Wiedererkennung und entsteht, wenn er fehlt, als
      Papierkorbeintrag.
    - **Invariante:** `deleted_with_day = 1` nur, wenn der Eintrag in der
      **Datei** am Tag hing **und** der **Zieltag** selbst im Papierkorb liegt
      — sonst wäre der Eintrag im Papierkorb unsichtbar und über den Tag nicht
      wiederherstellbar. Der Zieltag ist eine notwendige, keine hinreichende
      Bedingung; die erste Fassung prüfte nur ihn und machte damit aus jedem
      einzeln gelöschten Eintrag einen mitgelöschten.
    Als Nebenwirkung ist der Papierkorb-Teil des Demo-Nachlaufs entfallen
    (E-P1-21): Der Reset ist wieder ein Vorgang in einer Transaktion, die
    Fixture steht auf Format 2.
    *Gemessen:* Umlauf 87/100/16, davon 5/5/1 im Papierkorb; 286 739
    Einzelvergleiche, 0 unerklärte Abweichungen; Invariante über alle Konten
    der Prüfinstallation 0 Verstöße.

31. **Der Rückweg der Ruhesegmente läuft ohne Prüfschicht.**
    *Erledigt mit Web 8.0.0.* In
    `edbak_restore()` gehen `started_at` und `ended_at` der Ruhesegmente
    **ungeprüft** ins INSERT — anders als bei den Einsätzen, die seit dem
    Code-Review die `pruef_*`-Funktionen durchlaufen. Die Datei kann aus
    beliebiger Herkunft stammen; ein unbrauchbarer Zeitwert bringt hier nicht
    eine Zeile, sondern die ganze Wiederherstellung zu Fall — genau die
    Richtung, die bei den Einsätzen ausdrücklich umgedreht wurde. **Vorschlag:**
    `pruef_utc_oder_sql()` wie beim Einsatz, Zeile überspringen und zählen.
    Der Zählteil ist mit Web 8.0.0 erledigt (die Ruhesegmente melden ihre
    Überspringgründe jetzt), die Prüfschicht nicht. Gefunden in S1 (dort
    F-S1-A); bewusst nicht nebenbei behoben, weil es ein Schreibweg ist und
    eine Änderung daran eine eigene Abnahme braucht.

    Behoben und dabei weiter gefasst als vorgeschlagen. `client_ref` läuft
    jetzt durch `pruef_text(..., 64, ...)`, `started_at` und `ended_at` durch
    `pruef_utc_oder_sql()` — eine Zeile ohne brauchbaren Beginn wird
    übersprungen und unter `datum_oder_zeit` gezählt statt die Transaktion zu
    kosten. Die Flags gehen auf beiden Wegen über `pruef_flag()` statt über
    `(int)`.

    Die **Spurpunkte** haben dabei EINE gemeinsame Schreibstelle bekommen
    (`$spurSchreiben`). Es waren zwei, und sie waren verschieden: Der Einsatz
    begrenzte die Menge, prüfte den Aufbau und ließ `pruef_breite`/
    `pruef_laenge` laufen; das Ruhesegment schrieb roh, was in der Datei
    stand — `(float)"Unfug"` ist `0.0`, aus einem unbrauchbaren Punkt wurde
    also still eine gültige Koordinate. Zwei Kopien einer Prüfung sind eine zu
    viel; die zweite bleibt zurück. Zusätzlich geprüft werden seither `seq`
    und `ts` gegen den Wertebereich ihrer Spalten und `ele` auf Numerik — auch
    das war auf **beiden** Wegen offen.

    *Gemessen:* Beide Kreisläufe unverändert auf 0 unerklärten Abweichungen;
    die Zahl der geschriebenen Spurpunkte ist dieselbe.

32. **Ein aktiver Datei-Eintrag kann auf einem gelöschten Zieltag landen.**
    *Erledigt mit Web 8.0.0 — abgelehnt und gezählt (E-S1-19).*
    Die Invariante aus S1 schließt den Zombie in einer Richtung aus: kein
    `deleted_with_day = 1` an einem aktiven Tag. Die Gegenrichtung ist offen —
    ein in der Datei **aktiver** Einsatz kann beim Einspielen auf einem
    **gelöschten** Zieltag landen und wird dort als aktiver Eintrag angelegt.
    Er steht dann an einem Tag, den die Tagesliste nicht zeigt. Erreichbar über
    Schritt 1 der Wiedererkennung (ein Einsatz derselben `client_ref` liegt im
    Ziel bereits an einem gelöschten Tag **anderen Datums**); die Datumsprüfung
    greift dann nicht, weil sie Daten vergleicht. Der Fall ist **nicht neu** —
    er ist in derselben Form schon vor Web 8.0.0 erreichbar. **Zu entscheiden:**
    mitlöschen (widerspricht E-S1-04, „ohne `deleted_at` kein
    `deleted_with_day`"), überspringen und zählen, oder als hinnehmbar
    festhalten. Gefunden in S1 (dort F-S1-C).

    Entschieden wurde die zweite der drei vorgeschlagenen Möglichkeiten:
    **ablehnen und zählen**, unter dem Grund `tag_im_papierkorb`. Das ist
    dieselbe Regel wie D1, nur eine Ebene tiefer — was hier im Papierkorb
    liegt, nimmt nichts Neues auf. „Mitlöschen" schied aus, weil es E-S1-04
    widerspricht („ohne `deleted_at` kein `deleted_with_day`"); „hinnehmen"
    schied aus, weil der Eintrag danach halb sichtbar ist: In der Suche steht
    er, in der Tagesübersicht nicht, im Papierkorb auch nicht — und beim
    endgültigen Löschen des Tages bliebe er ohne Diensttag zurück (Nr. 33).

    Der Fall war **nicht neu** (am Stand vor S1 identisch erreichbar), aber
    S1 hat eine zweite Quelle dafür geschaffen: Seit die Wiederherstellung
    selbst Papierkorb-Tage anlegt, kann ein aktiver Datei-Einsatz auch ohne
    Zutun der NutzerIn auf einem gelöschten Zieltag landen.

35. **Ein doppeltes `seq` in der Spur kippt die ganze Wiederherstellung.**
    *Erledigt mit Web 8.0.0.*
    `track_points` hat den Primärschlüssel `(owner_type, owner_id, seq)`. Die
    Prüfschicht sichert seit Web 8.0.0 den Wertebereich von `seq`, nicht seine
    Eindeutigkeit; zwei Punkte mit derselben Nummer lösen deshalb einen
    Schlüsselkonflikt aus, und der reißt über die Transaktion den gesamten
    Lauf mit. Betrifft **beide** Wege (Einsatz und Ruhesegment) und nur Dateien
    fremder oder von Hand bearbeiteter Herkunft — ein eigener Export erzeugt
    keine doppelten Nummern. **Vorschlag:** die schon geschriebenen Nummern je
    Eigentümer mitführen und einen Wiedergänger überspringen statt ihn zu
    schreiben; `INSERT IGNORE` wäre der kürzere, aber stille Weg. Gefunden in
    S1.

    Behoben wie vorgeschlagen: `$spurSchreiben` führt die schon geschriebenen
    Nummern je Eigentümer mit und überspringt einen Wiedergänger. Er wird als
    `…track.seq: Nummer doppelt` gemeldet — `INSERT IGNORE` schied aus, weil
    die Datei sonst einen Fehler behielte, den niemand zu sehen bekommt.

    *Gemessen:* `tools/wiederherstellungs-probe/` Teil 2 — aus einer Spur
    `1, 2, 1, 3` kommen drei Punkte an, aus `5, 5, 6` zwei, und der Lauf
    überlebt; am Stand davor endet er mit
    `SQLSTATE[23000] … Duplicate entry 'mission-<id>-1' for key 'PRIMARY'`,
    ohne dass irgendetwas angekommen wäre. Beide Kreisläufe unverändert
    (286 739 / 0 / 16 und 8 797 / 0 / 859).

33. **`trash_purge_day()` lässt aktive Einsätze verwaist zurück.**
    *Erledigt mit Web 8.0.0.* Die
    Funktion entfernt zuerst die Einsätze des Tages `WHERE deleted_at IS NOT
    NULL` und danach den Diensttag. Ein **aktiver** Einsatz an einem gelöschten
    Tag überlebt den ersten Schritt und verliert im zweiten seinen Diensttag:
    `missions.day_id` trägt `ON DELETE SET NULL`. Er steht danach ohne Tag in
    der Datenbank — in der Suche sichtbar, in der Tagesübersicht nicht, im
    Formular nicht mehr zu öffnen (`einsatz_form.php` bricht ohne Diensttag
    ab). Die Rückfrage vor dem endgültigen Löschen nennt ihn nicht mit, ihre
    Zahl ist also zu klein. **Zu entscheiden:** mitlöschen (dann muss die
    Rückfrage ihn nennen) oder ablehnen, solange aktive Einsätze am Tag
    hängen. Der Zustand entsteht seit Web 8.0.0 nicht mehr über das
    Einspielen (Nr. 32), über `dt_zu_dayref()` beim Uhr-Upload aber weiterhin:
    die Zuordnung dort filtert nicht auf `days.deleted_at`. Gefunden in S1.

    Entschieden wurde **mitlöschen** — und dazu die Ursache abgestellt, an
    allen drei Stellen, an denen ein aktiver Einsatz überhaupt an einen
    gelöschten Diensttag geraten konnte:

    - `trash_restore_mission()` **lehnt ab**, solange der Diensttag im
      Papierkorb liegt, und sagt warum. Den Tag stillschweigend mitzurückzuholen
      wäre die falsche Großzügigkeit: Ein Klick auf einen Einsatz belebte einen
      ganzen Dienst.
    - `dt_zu_dayref()` gibt keinen gelöschten Tag mehr zurück, sondern legt
      einen **neuen** an und biegt die Dienstkennung auf ihn um; dasselbe gilt
      für den schon zugeordneten Tag in `ingest.php`. Verwerfen schied aus: Die
      Uhr sendet nur, bis der Server quittiert — verworfen ist fort, ein
      zusätzlicher Tag dagegen lässt sich zusammenführen.
    - `trash_purge_day()` nimmt **alles** am Tag mit, nicht nur das Gelöschte,
      und die Rückfrage nennt das Aktive vorher einzeln mit Datum, Uhrzeit und
      einem Link zum Verschieben. *Ablehnen* wäre eine Sackgasse gewesen: Die
      betroffenen Einsätze stehen in keiner Liste, man kann sie also nicht
      wegräumen.

    Altbestand wird **gemeldet, nicht angefasst**: `update.php` zählt aktive
    Einsätze ohne Diensttag und listet sie. Als Bericht und nicht als
    Migration, damit die Meldung so lange steht, wie es den Zustand gibt.

    *Gemessen:* `tools/wiederherstellungs-probe/` Teil 3 — acht Erwartungen,
    davon am Stand davor fünf nicht erfüllt (Zurückholen ging durch, die Uhr
    landete auf dem gelöschten Tag, das endgültige Löschen ließ ein Waisenkind
    zurück). Im Browser `papierkorb_misch.mjs`: 14 Einzelprüfungen, 0 Befunde;
    am Stand davor 4 Befunde.

34. **Schritt 1 der Diensttag-Wiedererkennung verhängt den ganzen Datei-Tag.**
    *Erledigt mit Web 8.0.0.*
    `edbak_restore()` erkennt einen Diensttag wieder, sobald **ein einziger**
    seiner Einsätze im Ziel schon liegt — und übernimmt dann dessen `day_id`
    für **alle** Einsätze und Ruhesegmente des Datei-Tags. Liegt dieser eine
    Einsatz im Ziel an einem anderen Tag (weil ihn jemand verschoben hat),
    wandert der ganze Datei-Tag dorthin, auch wenn er im Ziel unverändert
    aktiv daneben steht. Der Papierkorb-Fall aus Nr. 32 ist nur der Sonderfall
    davon. **Zu entscheiden:** Fingerabdruck vor `client_ref` prüfen, beide
    Ergebnisse vergleichen und bei Widerspruch den Fingerabdruck vorziehen —
    oder den Widerspruch melden statt zu raten. Gefunden in S1.

    Umgesetzt wurde die erste der beiden Möglichkeiten in abgewandelter Form:
    **nicht** den Fingerabdruck vorziehen, sondern Schritt 1 belegen. Alle
    Kennungen des Datei-Tags werden nachgeschlagen, und nur auf aktive
    Zieltage. Genau ein Ergebnis gilt; mehrere heißen „Schritt 1 weiß es
    nicht" — dann entscheidet der Fingerabdruck, und der Widerspruch erscheint
    als neuer Überspringgrund `tag_mehrdeutig` in der Rückmeldung.

    Der Fingerabdruck bleibt Schritt 2, weil er der **sprödere** Anker ist: Er
    bricht, sobald jemand am Zieltag Beginn, Ende, Art, Rettungsmittel oder
    Station berichtigt hat, und das ist der häufige Fall. `client_ref` ist
    stabil.

    *Gemessen:* `tools/wiederherstellungs-probe/` Teil 4 — sechs Erwartungen,
    darunter zwei Gegenproben (ein eindeutiger Kandidat greift weiter und wird
    nicht als mehrdeutig gemeldet). Am Stand davor fallen drei durch: Der
    Datei-Tag wurde auf den Tag des ersten Treffers verhängt, es entstand kein
    eigener Diensttag, und gemeldet wurde nichts.


11. **Sync-Seite meldet „Sync vollständig", obwohl die Uhr gar nicht senden
    kann.**
    *Erledigt mit Uhr 1.10.1.* Ohne hinterlegte Server-Adresse zeigte dieselbe
    Anzeige gleichzeitig das grüne „Sync vollständig" mit Haken **und** drei
    Zeilen tiefer „Erst Server-Adresse setzen"; ebenso bei gesetzter Adresse
    ohne Kopplung. Ursache war eine verwechselte Frage:
    `Model.backlogCount()` beantwortet nur „liegen abgeschlossene Pakete zum
    Senden bereit?" — vor dem ersten Dienst zu Recht `0` —, die Seite machte
    daraus eine Aussage über den Übertragungsweg.
    Der grüne Zustand setzt jetzt zusätzlich `hasServer()` **und**
    `hasCredentials()` voraus. Fehlt eines und liegt kein Rückstand vor, tritt
    ein dritter Zustand an seine Stelle: rot „Nicht eingerichtet", darunter
    gedämpft der nächste Schritt. Der bisherige Fußzeilenhinweis wird damit
    zur Hauptaussage und unten nicht wiederholt.
    Bei **Rückstand** behält die Zahl den Vortritt und der Hinweis bleibt in
    der Fußzeile — dort widerspricht sich nichts: Es sind Pakete offen, und
    daneben steht, warum. Betraf nur `watch/source/SyncView.mc`.
    *Geprüft:* fünf Zustände im Simulator mit Bildabzug (fenix6pro alle fünf,
    Venu 3s die beiden mit geänderter Blockhöhe); Rückstand über ein
    Probekompilat mit fest verdrahtetem `backlogCount() == 3`.


14. **Kopplungsablauf der Uhr: bestehende Kopplung vor einer Neukopplung
    abfragen und trennen.**
    *Erledigt mit Uhr 1.11.0 / Web 9.15.0.* Fall: eine geteilt genutzte Uhr.
    Wurde sie neu gekoppelt und schlug der Vorgang fehl, dokumentierte sie
    stillschweigend weiter auf das vorherige Konto — niemand sah es ihr an.
    Die Reihenfolge ist jetzt ausdrücklich abfragen → trennen → neu koppeln.
    `pair.php` kennt dafür ein zweites Anliegen `{"aktion":"trennen"}` mit den
    Kopfzeilen aus JSON-Vertrag Abschnitt 1 (dort neu: Abschnitt 1b). Der
    Server **löscht** das Gerät statt es zu deaktivieren, sonst belegte es
    weiter einen der `MAX_GERAETE` Plätze; hochgeladene Daten bleiben.
    Zwei Entscheidungen dabei: **Ein Rückstand verhindert das Trennen** —
    offene Pakete gehören dem bisherigen Konto und gingen sonst an das neue.
    Und **lokal wird immer getrennt**, auch ohne Antwort vom Server; sonst
    bliebe eine Uhr ohne Telefon in Reichweite dauerhaft an ein Konto
    gebunden, das sie nicht mehr benutzen soll. Die Uhr sagt beides.
    Greift in Nr. 11 (Uhr 1.10.1): Ohne den dritten Zustand „Nicht
    eingerichtet" wäre die getrennte Uhr wieder unsichtbar gewesen.
    *Geprüft:* Rückstandssperre und Endzustand im Simulator mit Bildabzug;
    der Weg Rückfrage → Trennen über einen Konsolenmitschnitt (die Rückfrage
    selbst ließ sich nicht fotografieren, s. Changelog). **Die Serverseite ist
    nicht gegen eine Datenbank gelaufen** — nur `php -l` und die Ableitung aus
    `ingest.php`/`einstellungen.php`.

60. **Die Uhr kennt die Logo-Wahl nicht.**
    *Bis zum 02.09.2026 trug dieser Punkt die Nummer 47. Sie war durch die
    Verschmelzung zweier Zweige zweimal vergeben (siehe Kopf dieser Datei);
    umnummeriert wurde die jüngere der beiden Reihen.*
    *Erledigt mit Uhr 1.10.0.* Die Weboberfläche ließ zwischen Hubschrauber,
    Fahrzeug und „wechselnd" wählen, die Uhr zeigte dagegen immer ein
    Luftfahrzeug — auch im Nachtdienst am Boden. Von den drei erwogenen Wegen
    ist es der zweite geworden: eine **App-Einstellung auf der Uhr** statt einer
    Übertragung vom Server. Die Uhr kennt die Kontoeinstellung nicht, und eine
    Einstellung, die man auf der Uhr sieht, gehört auch dorthin.
    Neu ist die Einstellung „Bildmarke auf dem Startbildschirm" mit den Werten
    *Luftgebunden* (Vorgabe), *Bodengebunden* und *Wechselnd*; die Ressourcen
    heißen `LogoLuft` und `LogoBoden`. Kosten: ein zweites Bild im Kompilat,
    gemessen +5 888 Byte (fenix6pro) und +12 864 Byte (venu3s).
    Beide Motive stammen aus den Vektorvorlagen der Weboberfläche
    (`gen-em_logo_helicopter_weiss.svg`, `gen-em_logo_nef_weiss.svg`). Weil sie
    unterschiedliche Seitenverhältnisse haben — quer gegen quadratisch —, steht
    das NEF auf 78 % der Kachelbreite; so sind beide Motive gleich hoch und
    wirken gleich schwer. Was bleibt, sind die **Größenstufen** für die großen
    Displays: Nr. 48.

61. **Bildmarke und Launcher-Symbol fehlten in den meisten Größen.**
    *Bis zum 02.09.2026 trug dieser Punkt die Nummer 48. Sie war durch die
    Verschmelzung zweier Zweige zweimal vergeben (siehe Kopf dieser Datei);
    umnummeriert wurde die jüngere der beiden Reihen.*
    *Erledigt mit Uhr 1.10.2 (Symbol) und 1.10.3 (Bildmarke).*
    **Das Launcher-Symbol** lag in zwei von neun verlangten Größen vor (35, 36,
    40, 54, 56, 60, 61, 65, 70 px). Die Größe ist keine Wahl, sondern eine
    Vorgabe des Geräts; fehlt sie, skaliert `monkeyc` und meldet es — 42 der 99
    Geräte bauten mit genau dieser einen Warnung. Jetzt sind es 0, und es kostet
    kein Byte: Garmin legt Bitmaps palettiert und in fester Breite ab, der
    Platzbedarf hängt an den Maßen, nicht am Inhalt.
    **Die Bildmarke** wird mit `dc.drawBitmap` 1:1 gezeichnet und war über die
    *Symbolgröße* zugeordnet statt über die Displayhöhe. Spanne über die 99
    Geräte: 15 % bis 34 % der Displayhöhe, wo die Gestaltung 27 % vorsieht —
    Venu 3s und Descent G2 teilen sich dasselbe 390-px-Display und zeigten sie
    in 27 % gegen 18 %. Jetzt vier vorgerasterte Stufen (Kachel 60, 73, 101,
    118), Spanne 25,0–28,8 %.
    Freigegeben am 31.08.2026 mit Mockup (Simulatorabzüge, je heute gegen
    Vorschlag) nach einer Rechnung über 3, 4, 5 und 10 Stufen. Bewusst
    mitentschieden: Bei vier Stufen fällt das Bezugsgerät mit der
    260/280-Gruppe zusammen, die Kachel der fenix6pro wächst von 70 auf 73.
    Das Abnahmekriterium „auf der Fenix verschiebt sich nichts" hat damit eine
    Ausnahme; sie steht im Kopf von `Ui.mc` und in `docs/Uhr-Layout_Regeln.md`
    2.1.
    Neues Werkzeug `tools/uhr-bilder/erzeugen.sh` — das Rezept der Bilder war
    bis dahin nirgends festgehalten. Es ist aus den vorhandenen Dateien
    zurückgerechnet und reproduziert sie bitgleich.
    *Geprüft:* Stufe I 99 übersetzt, 0 fehlgeschlagen, 0 Warnungen. Fünf Geräte
    im Simulator, eines je Stufe plus beide 390er. Speicher auf den beiden
    knappsten Geräten gemessen: fenix6 55,9/123,8 kB, FR 55 52,3/123,8 kB.

83. **Welche Daten von Uhr und Handy wie gespeichert werden, damit sich
    auswerten lässt, wer womit dokumentiert hat — Diskussion, dann Umsetzung.**
    *Aufgenommen 02.09.2026 vom Auftraggeber, nach dem Befund unten. Hängt an
    Nr. 80 (Auswertung) und muss VOR dieser entschieden sein.*
    Nr. 80 fragt, **wie** ausgewertet wird. Dieser Punkt fragt, **ob die Daten
    dafür überhaupt haltbar sind**. Sie sind es nur zur Hälfte.
    **Was trägt** — beides steht als Spalte am Einsatz selbst und ist im
    Backup: `missions.origin` (`watch` / `manual` / `import`, beim Anlegen
    gesetzt, nie geändert) und das **Präfix der `client_ref`** (`m-` Garmin-Uhr,
    `am-`/`ar-`/`ad-` Handy-App, `wm-` Wear, `man-` Formular, `imp-` Import;
    JSON-Vertrag 8, seit Fassung 1.4). Damit ist „wie viele Einsätze mit dem
    Webtool" vollständig und „mit welcher Client-Art" grob zu beantworten,
    ohne eine Zeile Code.
    **Was nicht trägt:** der Verweis `missions.device_id` → `devices`, an dem
    seit Web 12.9.0 Art und Modell hängen. Er steht auf `ON DELETE SET NULL`,
    und drei Wege löschen ein Gerät — einer davon (`pair.php` trennen) ist der
    **vorgesehene Normalfall** bei einer geteilt genutzten Uhr (Nr. 14).
    Ausserdem steht `device_id` **nicht im Backup** (bewusst, als
    interner Verweis).
    **Gemessen am 02.09.2026** an einem Demo-Konto, das über den regulären
    Einspielweg entsteht: **82 von 82 Einsätzen und 95 von 95 Ruhesegmenten
    ohne Geräteverweis** — obwohl 76 davon `origin = 'watch'` tragen. Zum
    Vergleich: **`day_refs` 16 von 16 mit Verweis**, denn dort steht die
    *öffentliche* Gerätekennung im Backup und wird beim Einspielen neu
    verknüpft. Das richtige Muster existiert im Projekt also schon, nur an
    einer Stelle.
    **Warum es eilt:** R60 lässt v1.0 mit einem Neuaufsetzen und **einer
    einmaligen Wiederherstellung** beginnen. Was bis dahin nicht haltbar ist,
    ist für den Altbestand danach nicht mehr herstellbar.
    **Drei Wege, zu entscheiden:**
    (a) **`devices` weich löschen** statt hart — Spalte `geloescht_am`,
    Zugangsdaten beim Trennen leeren, Zeile aus Listen und aus `MAX_GERAETE`
    filtern; dazu den Verweis wie bei `day_refs` über die öffentliche Kennung
    in das Backup. Hält ein bereits erlaubtes Datum am Leben und ist damit
    R36-konform.
    (b) **Art und Modell auf den Einsatz kopieren** (`missions.geraet_art`).
    Überlebt alles, auch die Wiederherstellung — ist aber eine
    Denormalisierung an der größten Tabelle und näher an „etwas Neues
    erfassen", als R36 zulässt.
    (c) **Nichts bauen** und nur über `origin` und das Präfix zählen. Kostet
    nichts, trägt heute, verzichtet aber auf die Modellgenauigkeit.
    **Eine Statistiktabelle wird für die Zählung selbst nicht gebraucht** — die
    ist ein `GROUP BY`. Das Problem ist die Haltbarkeit des Verweises, und eine
    Aggregattabelle löste es nicht, sondern schriebe denselben Verlust nur
    früher fest.
    **Mitzudenken:** Eine Wear-OS-Uhr koppelt nicht selbst (E-S4-11), das Handy
    koppelt für sie — eine solche Installation erscheint ausschließlich als
    `handy`. Und Geräte, die vor Web 12.9.0 gekoppelt haben, tragen gar keine
    Angabe.
    Zuordnung: **Diskussion in der Planung v1.0 (Schritt 10)**, Umsetzung
    danach — jedenfalls vor dem Neuaufsetzen.
    **Entschieden am 02.09.2026 (Rahmenplan R64), früher als hier vorgesehen:**
    **Weg (b)** — `geraet_art` und `geraet_modell` als Momentaufnahme an
    `missions` und `rest_segments`, beim Anlegen aus `devices` kopiert, in das
    Backup aufgenommen (das Muster von `day_refs`), Bestand per Migration
    nachgefüllt, solange die Geräte noch stehen; Trennen bleibt Löschen. Dazu
    **eigene Herkunftswerte** in `origin`: `watch` bleibt für die Garmin-Uhr,
    neu `android`, `wear` und `schnitt` neben `manual` und `import`, gesetzt
    beim Anlegen aus Geräteart und `client_ref`-Präfix. Der Einwand an (b)
    (näher an „etwas Neues erfassen") ist gesehen und so beantwortet: Es sind
    dieselben Werte wie R42, nur festgehalten; die Datenschutzerklärung nennt
    sie (Abschnitt 6 des Rahmenplans). Der Preis (Feldkatalog, Export- und
    Backup-Format, Kreisläufe und Referenz nach R24) ist angenommen und wird
    mit Nr. 63 in **einer** Formatänderung bezahlt. **Sichtbar** im Dashboard
    (Nr. 80) **und** je NutzerIn (Nr. 88). Zuordnung damit: **S4-Rest**
    (Speicherung), P5 (Dashboard), Nr. 88 (Kachel).

    **Erledigt am 04.09.2026** als **R64** mit Web **14.0.0** bis **14.2.1**. Der Verweis `missions.device_id` bleibt, wie er ist — die Haltbarkeit kommt aus einer **Momentaufnahme**: `geraet_art` und `geraet_modell` stehen seit 14.0.0 als eigene Spalten am Einsatz *und* am Ruhesegment, beim Anlegen kopiert und nie nachgezogen. `ON DELETE SET NULL` kann ihnen damit nichts mehr anhaben. Dazu trägt `missions.origin` jetzt sechs Werte statt drei (`watch|android|wear|manual|import|schnitt`), abgeleitet aus dem Präfix der `client_ref`; der Bestand ist per Migration nachgefüllt. Die Momentaufnahme reist in der Konto-Sicherung mit (Nutzlast 9) und steht im CSV-Export in zwei neuen Spalten. **Der Gegenbeleg zur Messung von Fassung 21** („82 von 82 Einsätzen ohne Geräteverweis"): Im erneuerten Referenzbestand tragen **82 von 82** Einsätzen und **100 von 100** Ruhesegmenten die Momentaufnahme, und alle sechs Herkunftswerte sind belegt. Nr. 80 (Auswertung) hat damit eine haltbare Grundlage.

63. **Sperrvermerke des Schnitts überstehen das Konto-Backup nicht.**
    *Aufgenommen 02.09.2026 als B-S4-10 (S4/A2).*
    `track_cuts` (Web 12.5.0) hält den Zeitraum, den `ingest.php` an einer
    geschnittenen Spur nicht mehr annimmt. Die **Komplett-Backup** trägt die
    Tabelle mit — sie findet ihre Tabellen über `SHOW FULL TABLES`. Die
    **Konto-Backup** (`edbak_build()`, Nutzlast 8) hat dagegen einen
    aufgezählten Aufbau und kennt sie nicht.
    **Die Folge nach einem Wiedereinspielen:** Ein Gerät, das Punkte des
    geschnittenen Zeitraums noch im Puffer hat, liefert sie nach, und sie
    landen wieder im Ruhesegment — die Fahrt läge dann in Einsatz *und*
    Segment, also genau der Zustand, den E-S4-53 mit dem Verschieben statt
    Kopieren vermeiden wollte. Der Einsatz selbst kommt vollständig durch;
    beschädigt wird nichts, es fällt nur eine Sperre weg.
    **Das Fenster ist schmal** (Wiedereinspielen ist selten, ein Gerätepuffer
    umfasst Stunden), der Fehler aber echt. Nicht nebenbei behoben, weil die
    Behebung den Nutzlastaufbau **und** beide Rückwege berührt: Der Vermerk
    verweist auf zwei Kennungen (Quelle und Ziel), die das Einspielen erst neu
    vergibt — er muss also wie die Spuren über Verweise laufen, nicht über
    Kennungen. Dazu `docs/Backup-Format.md`, die Kreislaufproben und ein
    Prüffall.

    **Erledigt am 04.09.2026** mit **Web 14.2.0** (Nutzlast 9) und **14.2.1** (Referenzbestand). Die Konto-Sicherung trägt seither je Einsatz eine Liste `schnitte`; der Vermerk verweist über `quelle_ref` auf die **Kennung** der Quelle, nicht auf ihre interne Nummer — genau das Muster, das `day_refs` schon benutzte. Ein Vermerk ohne Ziel wird gezählt und benannt, nicht stillschweigend verworfen. Belegt im **Dauerbetrieb**: Der Referenzbestand enthält seit 14.2.1 einen Schnitt, und weil der Demo-Reset die Fixture alle 30 Minuten einspielt, wird der Vermerk auf dem Produktivserver alle 30 Minuten geprüft. Zahlen: Wiederherstellungsprobe 94/0 (18 neue Erwartungen in Teil 11), edbak-Kreislauf 287 713 Einzelvergleiche / 0 unerklärt, Demo-Konto nach dem Reset 1 Sperrvermerk.
