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
19. **`$title` in `einsatz_loeschen.php` wird nie gelesen.** Die Variable wird
    gesetzt, der Titel steht daneben als Literal. Gefunden in P0 (dort F-06).
    Einzeiler, aber bewusst nicht nebenbei erledigt: Er stand nicht auf der
    Freigabeliste.
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
    - **Die Sicherung ist zu 93 % Spur.** 31,9 kB je Einsatz mit Spurpunkten,
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
    ganzen Aufruf), weil der Sicherungsstand aller Konten aus je einer kleinen
    `konto.json` kommt statt aus je einem Verzeichnisdurchlauf. Gesucht,
    gefiltert und sortiert wird im Speicher; der Browser bekommt höchstens 50
    Zeilen. **Die Grenze davon:** Bei einigen tausend Konten kippt das
    Verhältnis, und dann braucht der Sicherungsstand eine Spalte in der
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
    die Referenzsicherung vervielfältigen und über den **regulären**
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
    ≤ 3 s, Sicherung ≤ 5 min) gehalten werden.

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
    *Aufgenommen in P3/O12, Zahl fortgeschrieben in S2/AP3 und S2/AP4.*
    P-P3-03 verlangt null. Die Prüfung meldet **174** Treffer (bei Aufnahme
    158); 171 davon sind Kommentare oder richtige Typografie (die
    Auslassungspunkte der Fortschrittsmeldungen, die Pfad-Pfeile der Hinweise,
    das Malzeichen in „3× RTW"). Drei sind echte Symbole — dieselben drei wie
    bei der Aufnahme:

    > **Die Zahl wächst mit dem Text, nicht mit dem Problem.** Jeder neue
    > Hinweissatz mit Auslassungspunkten erhöht sie um eins; S2/AP3 hat sie
    > mit einer einzigen neuen Zeile auf der Wartungsseite von 167 auf 168
    > gebracht, S2/AP4 mit den Kopfkommentaren dreier neuer Dateien von 168
    > auf 174 (`?art=…&id=…` allein zählt viermal). Wer die Zahl als
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
    *Aufgenommen 30.08.2026 aus der ersten Rückmeldungsrunde.* Der Einsatzort
    ist mit Adresse und Koordinaten Ende-zu-Ende verschlüsselt — die Spur, die
    dorthin führt, und die Koordinate jeder Phase liegen im Klartext. Der Ort
    ist damit nominell geschützt und faktisch rekonstruierbar.

    Die Bestandsaufnahme steht in `docs/Konzept-V1-Ortsdaten.md`: was liegt wo,
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

46. **Das Altformat der Sicherung wird mit NaDoku 1.0 abgeschafft.**
    *Aufgenommen 31.08.2026 (S2/AP5), Entscheidung desselben Tages.* Seit
    Web 11.0.0 schreibt die Anwendung Containerfassung 4; die einteiligen
    Fassungen 2 und 3 werden nur noch **gelesen**. Das ist der Weg, auf dem
    ein vorhandener Bestand einmal herüberkommt — nicht mehr.

    Zu entfernen sind dann: der Lesezweig in `assets/crypto.js`
    (`openBackup`, Fassung 2 und 3), der Punktlisten-Rückweg in
    `backup_lib.php` (`$spurSchreiben`, `$insPoint`), die Fassungsweiche in
    `tools/referenzdatensatz/vergleich/lesen.py` und die einteilige
    Referenzdatei samt dem Lauf, der sie fährt.

    > **Warum das hier steht und nicht einfach passiert.** Ein Lesezweig, der
    > niemandem mehr auffällt, wird nicht gepflegt und trotzdem mitgeschleppt
    > — und im Ernstfall verlässt sich jemand darauf. Ein Datum, zu dem er
    > verschwindet, ist ehrlicher als ein „bleibt erstmal".

    Vorher zu klären: Was geschieht mit einer alten Datei nach dem Stichtag?
    Vorschlag: Die Meldung nennt die letzte Fassung, die sie noch einspielen
    konnte — so wie es `version_alt` heute für Nutzlasten unter 6 tut.

---

## Erledigt

Die Nummern bleiben, damit ältere Verweise aus Code und Dokumentation weiter
zutreffen.

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
    Array; sie aufzulösen hieße, die Datenstruktur zu ändern — für vier
    Meldungen der falsche Preis. Mit erledigt: `Input.lPageDown()` und
    `L_PAGE_DOWN` waren toter Code und sind entfallen, und die Wisch-Kommentare
    an `CprView.onPreviousPage/onNextPage` waren vertauscht.

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
