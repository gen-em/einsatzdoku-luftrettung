# Gen-EM NAdoku — Backlog, erledigte Punkte

Erledigte Punkte, wörtlich; Nummern werden hier aufgelöst, nicht gepflegt.
Diese Datei ist seit dem 26.09.2026 das Gegenstück zu `docs/Backlog.md`
(Konzept SD, E-SD-18): Ein Punkt, der erledigt ist, wandert mit seinem
vollen Text hierher und behält seine Nummer; ein Verweis „Backlog Nr. 10"
löst in genau einer der beiden Dateien auf (Stufe 1 misst doppelte Nummern
über beide Dateien und ihre Schnittmenge). Ein Eintrag wird hier nicht mehr
fortgeschrieben — was sich nach der Erledigung noch ändert, ist ein neuer
Punkt in `Backlog.md`. Der Abschnitt *Erledigt* unten ist der aus
`docs/Backlog.md@f5bddc2`, Zeile für Zeile; seine Einrückung mit vier
Leerzeichen bleibt, samt ihrer Folge auf GitHub (Nr. 196) — wer hier liest,
liest die Quelle.

## Werdegang der Nummernvergabe

Die folgenden Absätze standen bis zum Schnitt im Kopf von `docs/Backlog.md`
und sind wörtlich übernommen (Stand `f5bddc2`; E-SD-20). Sie erklären, warum
Nummern fehlen, doppelt waren oder gewandert sind. Fortgeschrieben werden
sie nicht mehr; neue Spannen stehen in der Tabelle im Kopf von `Backlog.md`.
Die Nummer 5 fehlt darin: Sie war nie vergeben — in keiner Fassung von
`docs/Backlog.md` seit dem ersten Commit `7154ec5` steht ein Eintrag mit
dieser Nummer (nachgesehen 26.09.2026, E-SD-25). Die Sätze im Changelog zu
Web 7.2.0 („Das Geräte-Limit (Nr. 5) … steht jetzt unter *Erledigt*")
beschreiben einen Stand, der nie im Repositorium lag; 5 bleibt wie 4, 6
und 7 frei.

**Zu den fehlenden Nummern 4, 6 und 7.** Sie waren vergeben und sind ohne
Eintrag verschwunden; ihr Inhalt ist nicht mehr rekonstruierbar. Sie bleiben
deshalb dauerhaft frei — weder werden sie neu vergeben noch nachgetragen. Diese
Notiz steht hier, damit die Frage nicht bei jedem Durchsehen erneut aufkommt.

**Nummernvergabe zwischen Zweigen (Stand 17.09.2026).** 200 und 201
(Rahmenplan Fassung 73) und 202–205 (Fassung 74) sind mit dem Doku-Paket der
Konzeptinstanz vergeben und liegen auf dem P5a-Zweig
`claude/butte-umsetzen-5opi9u`, der bis zu seinem Merge die
Steuerungsdokumente trägt. **206 bis 212 sind in der Umsetzung von P5a auf
demselben Zweig vergeben** (206 Messstand-Schritt, 207 `gen-em.org` in `tools/`,
208 Jobregister von Hand geführt, 209 Bausteintabelle in `Design.md`,
210 Deadlocks in `ingest.php`, 211 `/api/`-Aufruf ohne Sitzung, 212 zwei
Erwartungen der Wiederherstellungsprobe), **213 und 214 aus der
Durchsicht vom 16.09.2026** (Zustandsdatei der Kette im Webroot;
`install.php` in der Auslieferung), **215 und 216 aus der unabhängigen
Durchsicht des P5a-Abschlusses** (16.09.2026, nach dem Merge), **217 und 218
aus der Durchsicht der Werkzeugaufrufe** (17.09.2026), **219 aus dem ersten
Auslieferungslauf nach dem Merge von PR #51** und **220 und 221 aus dem
ersten Bilderlauf gegen Staging mit Demo-Konto** (beide 17.09.2026), **222
aus dem Aufbau des Uhr-Prüfstands**. **223 bis 233 liegen auf dem
P5b-Zweig** `claude/magical-dirac-we2y1z` (223 Anwendung nicht
installierbar, 224 `frame-ancestors` in Report-Only, 225 Profilseite aus dem
Gerüst ausgebrochen, 226 Meldungston ohne Regel, 227 Symbolregel zählt
Typografie, 228 Proof-of-Work gegen Registrierungs-Spam, 229 Uhr-Anzeige bei
`403`, 230 Wegwerfliste nachziehen, 231 Einwilligung bei der Registrierung,
232 Fristen der Rückfragen nie abgelaufen, 233 bisheriger Server-Anteil).

> **Diese elf trugen auf ihrem Zweig die Nummern 215 bis 225** und sind beim
> Merge am 17.09.2026 verschoben worden: `main` hatte 215 bis 222 parallel
> für anderes vergeben. `main` ist vorgelagert, also weicht der Zweig — wie
> schon bei Nr. 214 und bei Web 20.16.0. Wer in einem Commit vor dem Merge
> eine dieser Nummern liest, liest die alte Zählung.

**238 und 239 sind auf `main`** (PR #60, 20.09.2026: reserviertes Wort
`MANUAL` in MySQL 8.4.0–8.4.10; `backup_lib.php` ohne Backticks). **240 liegt
auf dem Zweig `claude/fervent-dirac-xirsqw`** (Kette II, 20.09.2026: der
Rundlauf-Prüffall des Handy-Moduls läuft in der Kette nie).

**241 bis 249 sind für den Einschub vom 20.09.2026 reserviert** — das
Doku-Paket der Konzeptinstanz (P5c, Sitzungsablage, Nachtrag vom 18.09.).
Feste Zuordnung, damit die Instanzen, die auf eigenen Zweigen daran
arbeiten, nicht erneut kollidieren:

| Nr. | Sache | gehört zu |
|---|---|---|
| 241 | Sitzungsablage — PHP-Sitzungen im geteilten Hosterverzeichnis | Schritt 16 |
| 242 | Sitzungsbindung per Cookie-Token | Schritt 18 |
| 243 | Staging-Umgebungsbanner | erledigt 23.09.2026 (Web 20.38.0) |
| 244 | Einstellungen-Übersicht: Bereiche als Gliederung erkennbar | erledigt 25.09.2026 (Web 21.1.0) |
| 245 | Erklärtext-Regel und Überarbeitung aller Texte in Verwaltung und Betrieb | erledigt 25.09.2026 (Web 21.1.0) |
| 246 | Schlüsselblatt und Notfallblatt: Druckseite | erledigt 25.09.2026 (Web 21.1.0) |
| 247 | Serverschlüssel wechseln — als Vorgang | Schritt 18 |
| 248 | Prüftor Stufe 1 zählt die `error_log(`-Aufrufe | erledigt 24.09.2026 (Web 20.40.0) |
| 249 | TOTP-Reset, wenn die einzige BetreiberIn Zweitgerät und Codes verliert | Schritt 18 (der Rest; teilweise gelöst mit Konzept RW) |

> **Warum die Spanne bei 241 beginnt und nicht bei 240.** Das Paket sah 240
> für die Sitzungsablage vor; am selben Tag hat Kette II 240 für den
> Rundlauf-Prüffall vergeben und **gepusht**. Nach der Regel oben weicht,
> wer noch nicht gepusht hat — das war das Paket. Entschieden vom
> Auftraggeber am 20.09.2026.

**250 bis 259 sind für Schritt 15 reserviert** (Zentralisierung,
`docs/konzepte/Konzept-Zentralisierung.md`, eingespielt am 20.09.2026; das
Konzept ist mit dem Abschluss von Schritt 15 am 22.09.2026 gelöscht — die
Git-Historie behält es, die Zusammenfassung steht im Rahmenplan Abschnitt 8).
Vier
Nummern waren vergeben, **sechs blieben frei für Funde der Umsetzung** — nach
derselben Überlegung wie bei 241–249: Ein Paket, das erst beim Bauen neue
Punkte findet, soll sie anhängen können, ohne mit dem nächsten Zweig zu
kollidieren.

| Nr. | Sache | gehört zu |
|---|---|---|
| 250 | Umleiten nach POST auf den Admin-Seiten, die heute nicht umleiten | Schritt 17 |
| 251 | Cookie-Attribut `secure` der Sitzung — zwei Arten HTTPS-abhängig, zwei fest | Schritt 18 |
| 252 | Gelaufene Migrationen fragen das Schema 57× von Hand | P8 (R66) |
| 253 | Datum-Zeit-Trenner vereinheitlichen | erledigt 25.09.2026 (Web 21.1.0) |
| 254 | Ratenprobe erwartet fünf Töpfe mit Leiter — es sind sechs | erledigt 21.09.2026 |
| 255 | `lokal_einrichten.sh` kopiert Handbuch und Bilder nicht nach `server/doku/` | behoben 21.09.2026 |
| 256 | Vier `api/`-Dateien antworten auf eine falsche Methode anders als die übrigen | erledigt 21.09.2026 |
| 257 | Fünf Prüfwerkzeuge hingen an der globalen `$CFG` | erledigt 21.09.2026 |
| 258 | Drei `api/`-Dateien antworten am `json_out()` vorbei | Backlog-Runde / 10c AP6 |
| 259 | GPX-Probe wird durch den Demo-Reset blind (4/95, Kernvergleich 0 von 204) | Backlog-Runde |

**Die Spanne ist damit voll.** Der Fund von AP6 — *die tote Spalte
`missions.other_resources` löschen* — hat deshalb eine Nummer außerhalb der
Spanne bekommen.

> **Und er hat sie beim Merge ein zweites Mal gewechselt, von 267 auf 276.**
> Der Zweig von Schritt 15 hat am 22.09.2026 die Nummern **267 und 268**
> vergeben; `main` hatte sie am 21.09.2026 unabhängig davon ebenfalls
> vergeben (Alias `AS manual` auf MySQL 8.4 und die zwei Staging-Läufe).
> Zwei Zweige, die gleichzeitig „die nächste freie Nummer" nehmen, nehmen
> dieselbe — und gemerkt hat es erst der Prüfschritt „Backlog — keine
> Nummer zweimal" beim Merge. **`main` behält seine beiden**, weil dort die
> Verweise aus Changelog, Technik und den PK-Dokumenten hängen; die beiden
> vom Zweig sind **276** (tote Spalte, AP6) und **277** (`await fetch` ohne
> `catch` in `einstellungen.php`, AP8). Wer eine Nummer vergibt, während ein
> zweiter Zweig offen ist, prüft sie gegen **`origin/main`** und nicht gegen
> den eigenen Stand.

> **Keiner der vier gehört in Schritt 15 selbst**, und das ist kein Versehen:
> 250 ändert Wege durch die Anwendung, 251 und 252 hängen an späteren
> Schritten, 253 ist Gestaltung. Schritt 15 verschiebt Code an eine Stelle;
> was dabei auffällt, aber etwas **anderes** ändert, wird notiert und nicht
> mitgemacht.

Jeder weitere Zweig, der Nummern vergibt, beginnt bei **339** und trägt seine
Spanne hier ein, bevor er pusht. **Vergeben und reserviert (24.09.2026,
nachgesehen auf `origin/main` `ba2ec57`, höchste 318; zusammengeführt
am 26.09.2026, als BV `main` nach dem Merge von P5c aufnahm — `29cf394`,
höchste 329 —, und erneut nach PR #91, `a9d00ea`, höchste 330):** **293 und 304 bis
318** Konzept BR (gemergt, PR #85/#86; 304 bis 313 die nachgetragenen Anlässe
aus E-BR-17 und E-BR-18, 314 bis 317 Funde aus BR-04 und BR-05, 318 ein Fund
des Abschlusses); **294 bis 303 und 319 bis 328** der P5c-Zweig
`claude/p5c-mockups-konzept-4yeomf` — 294 Konzept SD (Sammelpunkt), 295 bis
298 die Anlässe aus der Zuarbeit von Konzept BR (Konzept P5c, E-P5c-86), 299
und 300 Funde aus AP4, 302, 303, 319 und 320 die nachgetragenen Anlässe aus
dem Aufnehmen von BR (E-P5c-116), 301 für AP5b (Konzept RW), 321 ein Fund
und 322 Funde aus AP7 (Stilvergleich, Reihenfolge des Prüfstands), 323 ein Fund aus AP8 (Referenz und Fixture auf Nutzlast 11), 324 ein Auftrag aus AP8 (Einmal-Skript für den Bestand zu 1.0), 325 ein Fund aus AP8 (GPX-Probe auf frischer Anlage), der Rest frei für Funde der Umsetzung. Die zweite Spanne kam mit dem Aufnehmen von `main`
dazu: Die erste reichte nicht mehr, und 304 bis 318 hatte BR inzwischen
vergeben. **329 und 330** vergab der P5c-Zweig über seine Spanne hinaus, in
die von BV: 329 in AP11 (F-P5c-171), 330 mit PR #91 (Stilvergleich,
aufgenommen und erledigt). Die Spanne von BV stand auf den Zweigen von BV
und AR, nicht auf `main`; **331 bis 333** Konzept BV, der Vorgriff auf
Backlog-Runde 4 (Zweig `claude/intelligent-carson-q8f7ag`; alle drei
vergeben — 331 hieß auf dem Zweig bis zum 26.09.2026 329, 333 hieß 330;
beide sind ausgewichen, E-BV-12 und -17); **334 bis
338** die Android-Runde AR (Zweig `claude/affectionate-newton-6pzfkc`; 334
bis 337 vergeben, 338 frei). *(Bis zum 26.09.2026 stand hier auf `main` 329
und auf den Zweigen von BV und AR 339 — die drei hatten den Absatz getrennt
fortgeschrieben. Bis zum 23.09.2026
stand hier 283; 283 bis 285 sind seither auf `main`, **286 und 287** vergibt
Konzept P5c in seiner Fassung 2 vom 23.09.2026, **288 und 289** die
Mockup-Runde M-P5c-02 am selben Tag, **290 und 291** die Korrekturstufe
Web 20.37.3, **292** das Korrekturpaket RP — nachgesehen auf `origin/main`
und auf allen offenen Zweigen.)*

**Und sieht vorher nach — auf `origin/main` UND in die offenen Pull
Requests.** Der Satz darüber beschreibt keinen Riegel, sondern eine Hoffnung:
Zwei Zweige, die gleichzeitig arbeiten, beginnen beide bei derselben Zahl, und
diese Datei meldet dabei **keinen Konflikt**, weil die Einträge an
verschiedenen Stellen stehen. Im September 2026 ist genau das dreimal
passiert — Nr. 268 (PR #72), dann 269 bis 271 gleich dreifach (PR #74); der
Werdegang steht bei Nr. 281. Eine Zahl, die schon auf einem ungemergten Zweig
steht, ist vergeben.

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
in `docs/konzepte/Vorbereitung-S9-Problemsammlung.md` (gelöscht 24.09.2026,
Historie `5e501ae`).

**Zu den Nummern 147 bis 149 (Rahmenplan Fassung 32, 06.09.2026).** 147 ist
die aufgezeichnete Spur im Kartendialog der Einsatzbearbeitung (S9, als
PS-11 der Vorbereitung); 148 und 149 sind zwei Funde am Produktivstand nach
dem S8-Deploy vom 06.09.2026 — ein Knopf, der auf 404 führt, und eine
Migration, die im Web weder ausführbar noch verbuchbar war. Dieselbe Fassung
hat zwölf Verweise „R74" auf den Krypto-Review zu **R78** berichtigt (der
Beschluss ist mit Fassung 31 umnummeriert worden, diese Datei nicht) und
Nr. 115 in Nr. 95 aufgehen lassen — beide beschrieben denselben Fund.

**Zu Nr. 152 und den Vermerken „Konzept S9" (Rahmenplan Fassung 34, 07.09.2026).**
Das Konzept S9 lag vor (`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md`, gelöscht 24.09.2026, Historie `5e501ae`;
E-S9-01 bis -19). Die Einträge 101–113, 147, 44, 68, 69, 70 und 72 tragen
jetzt den Beschluss, der sie erledigt; 152 ist der einzige neue Punkt — die
Standortseiten, aufgekommen bei der Mockup-Freigabe. 132 und 137 (9a) tragen
den Vermerk, dass S9 sie ergänzt.

**Zu Nr. 153 (07.09.2026, Sofortpaket Sicherheit, Web 15.6.0).** Sie ist kein
neuer Fund, sondern ein herausgelöster: Nr. 135 (K-15) bündelte vier
Kleinigkeiten, drei davon sind mit dem Sofortpaket erledigt und die vierte
nicht. Wäre sie mit Nr. 135 nach *Erledigt* gewandert, wäre sie unsichtbar
geworden. Die Kopfzeilen aus demselben Befund gehen mit der CSP (Nr. 8) und
brauchen deshalb keine eigene Nummer.

**Zu den Nummern 150 und 151 (06.09.2026, Korrekturstufe Web 15.5.2).** 150
kommt vom Auftraggeber (der Cron-Befehl mit dem Repositoriumspfad), 151 vom
neuen `tools/linkprobe/`, das in derselben Stufe entstanden ist — es hat den
zweiten Verweis gefunden, der auf einen Parameter zeigt, den seine Zielseite
nicht liest. **148 und 149 stehen seit dieser Stufe unter *Erledigt***; ihre
Nummern bleiben, und Teil (a) von 149 ist als Regel im Runbook abgelegt und
wird in P5 und P6 wieder aufgerufen.

**Zu den Nummern 87, 81, 88 und 175 (Backlog-Durchsicht vom 12.09.2026,
eingearbeitet 13.09.2026).** Die Durchsicht hat alle 61 offenen Punkte gegen
Web 19.3.0 · Uhr 3.1.0 · Android 0.15.0 gehalten und sechzehn Entscheidungen
protokolliert (`docs/konzepte/Backlog-Durchsicht-2026-09-12.md`, gelöscht 24.09.2026, Historie `5e501ae`). Kein Punkt
hatte sich unbemerkt selbst erledigt; gealtert waren Zahlen, Zeilennummern
und sechs Aussagen — alle berichtigt. Drei Punkte verlassen *Offen*: **87**
ist als Erhebung mit R70 beantwortet und stünde sonst neben P7 ein zweites
Mal; **81** ist ohne Beleg am Gerät geschlossen, weil die wahrscheinliche
Ursache seit Android 0.11.1 behoben ist — mit einer Wiederöffnungsbedingung
im Eintrag; **88** ist verworfen und steht wie Nr. 71 als Entscheidung unter
*Erledigt*. Zehn weitere Einträge tragen jetzt ihre Entscheidung im Text
(57, 41, 42, 45, 124, 150, 117, 169, 21, 62); vier davon (41, 42, 45, 124)
bilden die Mockup-Runde, die `docs/Rahmenplan.md` verortet. Die Zahl der
offenen Punkte fällt damit von 61 auf 58 — und steigt mit **175** (ein
Nebenfund der Gegenprüfung, angelegt auf Anweisung vom 13.09.2026) auf
**59**.

**Zu den Nummern 1, 9, 10 und 12.** Sie fehlten ebenfalls, waren aber
rekonstruierbar: Code und Changelog verweisen an neun Stellen namentlich auf
sie („Backlog Nr. 10"), und aus diesen Fundstellen geht eindeutig hervor,
worum es ging und womit es erledigt wurde. Die vier Einträge unten sind aus
genau diesen Fundstellen wiederhergestellt (Web 7.2.0, Paket P0/N6) und als
solche gekennzeichnet. Sie stehen unter *Erledigt*, weil alle vier es sind.

---

## Erledigt


Die Nummern bleiben, damit ältere Verweise aus Code und Dokumentation weiter
zutreffen.

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

    **Nachgemessen am 12.09.2026 (Backlog-Runde 2) — der Punkt ist größer als
    er dasteht, und zwei seiner Angaben stimmen nicht.** Er bleibt offen: Was
    hier zu tun wäre, braucht ein Mockup und eine Freigabe und passt damit
    nicht in eine Backlog-Runde. Damit der nächste Anlauf nicht wieder bei
    null anfängt, steht hier, was gemessen ist.

    *Was am Text oben nicht stimmt:*

    - *„`missiontable.js` führt die Spaltendefinitionen der drei
      Einsatztabellen an einer Stelle."* Es sind **zwei** Tabellen
      (`suche.php`, `zeitraum.php`). Die dritte hat sie **nie** von dort
      bezogen: `index.php` baut Kopf, Zellen und Sortierschlüssel selbst, in
      **drei** getrennten Listen. Von `missiontable.js` holt sie nur
      Zellbausteine. Es sind also zwei Erzeuger und **vier** Spaltenlisten —
      sechs, wenn man `api/range.php` und `api/suchindex.php` mitzählt, die
      `winch`/`bergwacht`/`secondary`/`false_alarm` hart im SELECT führen,
      während `api/day.php` sie aus `mf_tagesspalten()` zieht.
    - *„weil die Vereinheitlichung … die Kachelform berührt."* Tut sie nicht
      mehr. Seit E-P3-32 baut `index.php` seine Kacheln bereits über
      `EdMissionTable.kachel()`, wortgleich zu den anderen beiden. Von den
      drei genannten Hindernissen sind zwei übrig — und eines davon, das
      **Sortierblatt**, ist selbst dreifach vorhanden (`index.php` baut es aus
      den `th`, `suche.php` und `zeitraum.php` je aus `tabelle.spalten()`).

    *Fünf Driften, vier davon sichtbar* (der Eintrag nennt nur die eine
    behobene):

    1. **Spaltensatz:** drei Spalten nur im Modul, eine nur in `index.php`.
    2. **Sekundärtransport:** ein Wort mit weichem Trennstrich gegen zwei
       Zeilen mit hartem `<br>` — Kopfhöhe **42 gegen 64 px**.
    3. **Ausrichtung:** Alter zentriert gegen rechtsbündig, Beginn zentriert
       gegen links. Die Entscheidung dazu fiel in **derselben Sitzung**, in
       der dieser Punkt aufgenommen wurde (S3/AP5 Block I) — sie wurde am
       zweiten Aufbau getroffen und erreichte den ersten nie. Der Punkt hat
       sich beim Aufschreiben also selbst wiederholt.
    4. **Hakenreihenfolge:** Sekundär-Bergwacht-Winde gegen
       Winde-Bergwacht-Sekundär, genau umgekehrt.
    5. Die Dauerspalte — behoben, wie oben beschrieben.

    *Und eine Falle, die „nur den Erzeuger zusammenführen, 0 Pixel bewegen
    sich" widerlegt:* Die beiden Sortierungen behandeln **Gleichstände**
    verschieden. `index.php` multipliziert den Stichentscheid mit der
    Richtung, `missiontable.js` verlässt sich auf die stabile Sortierung.
    Nachgerechnet mit sechs gleichwertigen Zeilen: heute absteigend
    6,5,4,3,2,1 — über das Modul 1,2,3,4,5,6. Ein zweiter Klick auf
    „Sekundärtransport" dreht an einem NEF-Tag heute alle sechs Zeilen und
    täte es danach nicht mehr. Dazu setzt `missiontable.js` `sortable` auf
    **jeden** Kopf, woran `cursor:pointer` und ein Hover hängen.

    *Was daraus folgt:* **Schritt 0 ist eine Freigabe, keine Codearbeit.** Drei
    Fragen müssen vorher beantwortet sein — Beschriftung, Ausrichtung,
    Hakenreihenfolge —, und jede Antwort ändert eine der beiden Seiten
    sichtbar. Danach der Erzeuger (rund 110 Zeilen JS und 21 Zeilen PHP
    entfallen), danach die Liste aus dem Feldkatalog, soweit er sie trägt: Er
    kennt heute **3 von 13** Spalten, und nur für die Tagesübersicht —
    `day_col` heißt wörtlich das. Vier Spalten können gar nicht aus ihm
    kommen, weil sie keine Spalten von `missions` sind. Geschätzt
    zweieinhalb bis drei Tage. Zuordnung: eigenes Paket, nicht Backlog-Runde.

    **Entschieden am 12.09.2026: Das gemeinsame Modul (`assets/missiontable.js`)
    ist die Vorlage.** Beschriftung der Spalte „Sekundärtransport",
    Ausrichtung von Alter und Beginn und die Reihenfolge der Haken folgen
    ihm; `index.php` zieht nach. Die drei Freigabefragen aus Schritt 0 sind
    damit beantwortet; es bleibt ein eigenes Paket.

    > **Bindende Nebenbedingung — am Code belegt und wichtiger, als sie
    > klingt.** Die bedingte Anzeige von Winde und Bergwacht läuft über
    > `cap_gate` im Feldkatalog (`mission_fields.php`): Ein Feld erscheint
    > nur, wenn der Diensttag die passende Fähigkeit trägt. `index.php` holt
    > seine Spalten über `mf_tagesspalten()` und bekommt das Verhalten
    > geschenkt. **Suche und Zeitraumübersicht tun das nicht** —
    > `api/range.php` und `api/suchindex.php` führen `winch`, `bergwacht`,
    > `secondary` und `false_alarm` hart im SELECT.
    >
    > Genau die Eigenschaft, die erhalten bleiben soll, sitzt heute auf der
    > Seite, die weichen soll. Sie muss beim Zusammenführen **ausdrücklich**
    > mitgenommen werden — als erster Prüffall des Pakets. Geht sie verloren,
    > fällt das erst an einem NEF-Tag auf, an dem plötzlich Windenspalten
    > stehen — und dann sieht es aus wie ein neuer Fehler, nicht wie ein
    > verlorener Vertrag.


    **Zuordnung (20.09.2026): Schritt 15 AP9** — mit dem Konzept
    Zentralisierung. Dort kommen zwei Dinge dazu, die hier fehlten:
    **E-ZE-01** (die Gleichstände — was passiert, wenn zwei Einsätze
    dieselbe Sortiergröße haben; `sortable` entscheidet das heute
    stillschweigend anders als der Aufbau in `index.php`) und **F-ZE-6**
    (der **Spaltensatz je Seite** ist nicht derselbe — die drei Tabellen
    zeigen verschiedene Spalten, und das muss eine Vereinheitlichung
    abbilden, statt es einzuebnen).

    **ERLEDIGT am 22.09.2026 — Schritt 15 AP9b, Web 20.37.0.**
    `index.php` bezieht Kopf, Zeilen, Kacheln, Sortierung und Sortierblatt
    aus `assets/missiontable.js`. Gezählt: Zeilenerzeugung `tr.innerHTML`
    **1 → 0**, Spaltenlisten für Einsatztabellen **4 → 1**,
    Sortierblatt-Erzeuger **3 → 1**.

    **Die vier Freigabefragen sind entschieden** (E-ZE-31 bis E-ZE-34):
    Beschriftung, Ausrichtung, Hakenreihenfolge und Gleichstände folgen dem
    Modul; die Spalte „Nr." bleibt und das Modul lernt sie; die Sortierung
    nach „Beginn" bleibt chronologisch; Winde und Bergwacht folgen der
    Fähigkeit und der Betriebsart (AP9a, Web 20.35.0).

    **Der Feldkatalog hat seinen Griff behalten** — der vierte Fund der
    Vermessung. Das Modul gleicht seine Hakenspalten gegen
    `KATALOG_SPALTEN` ab: Der Katalog bestimmt, **welche** es gibt
    (`day_col`), das Modul, **wie** sie aussehen und in welcher Reihenfolge.
    Preis, benannt: `day_col` heißt ab jetzt „Spalte in **jeder**
    Einsatztabelle".

    **Drei Fehler kamen dabei ans Licht, alle älter als das Paket:**
    Das Modul sortierte „Beginn" über die *Zeichenkette* `start_hhmm` und
    stellte damit bei einem Dienst über Mitternacht 01:10 vor 23:50; das
    mobile Sortierblatt von Suche und Zeitraumübersicht stellte um, **ohne
    neu zu zeichnen** (unter 720 px ist es der einzige Weg zu sortieren);
    und dasselbe Blatt zeigte `Sekundär&shy;transport` als rohe Entität.

    **Nachgemessen:** Formvergleich über 56 Seiten — nach Abzug von
    Countdown, Versionszeile und dem einen beabsichtigten Kopfwechsel sind
    **0 von 56** Seiten noch abweichend. Suche und Zeitraumübersicht
    **0/0/0**. Nachtdienst gebaut (der Bestand hat keinen): mit
    `start_sort` 21:10 · 22:30 · 23:50 · 00:20 · 01:10 · 02:40, ohne ihn
    00:20 · 01:10 · 02:40 · 21:10 · 22:30 · 23:50. Gleichstände: zwei
    Einsätze mit 51 min bleiben in **beiden** Richtungen 3 vor 4.
    0 Konsolenfehler.

    **Eine Zwischenfassung hat der Bildvergleich abgefangen**, und sie wäre
    still geblieben: „Datumsspalte nur bei mehreren Tagen" als `nurWenn` im
    Modul nahm der **Zeitraumübersicht** die Spalte, sobald alle Treffer
    eines Monats auf einen Tag fielen — während sie weiter nach ihr
    sortierte. Die Spalte steht jetzt in `opts.ohne` von `index.php`: Eine
    Seite, die eine Spalte nicht will, sagt es; das Modul rät es nicht.

257. **Fünf Prüfwerkzeuge hingen an der globalen `$CFG` — und meldeten nach
    ihrem Wegfall 30 Erwartungen als „nicht erfüllt", ohne dass die
    Anwendung einen Fehler hatte.** *Aufgenommen und erledigt am 21.09.2026
    (Schritt 15, Nachtrag zu AP2).*

    AP2 hat die globale `$CFG` entfernt; die Anwendung liest seither über
    `konfig()` aus der Datei. **Die Registerzeile Z04 bestätigte 46 → 0 und
    der Umbau galt als erledigt** — sie mass aber nur `server/`. Unter
    `tools/` lasen oder setzten fünf Proben dieselbe Globale weiter. Eine
    Zuweisung an `$CFG` scheitert nicht; sie tut nur nichts.

    | Werkzeug | Erwartungen | vorher offen | nachher |
    |---|---|---|---|
    | `ingestprobe` | 83 | 1 | 0 |
    | `anteilprobe` | 55 | 22 | 0 |
    | `versandprobe` | 135 | 5 | 0 |
    | `wiederherstellungs-probe` | 104 → **111** | 1 (+7 übersprungen) | 0 |
    | `komplettprobe` | 64 | 1 | 0 |

    Die Wiederherstellungsprobe zeigt den Schaden am deutlichsten: Sie
    **übersprang Teil 12 ganz** und meldete dafür einen einzelnen Fehlschlag.
    Sieben Erwartungen wurden gar nicht gemessen.

    *Behoben:* `tools/konfig_stellen.php` — eine Stelle, fünf Verbraucher.
    Sie geht denselben Weg wie die Anwendung (`config.php` schreiben,
    `konfig_verwerfen()`) und legt den **Urstand bytegleich** zurück, auch
    bei einem Abbruch. Ausdrücklich **nicht** gebaut wurde eine Hintertür in
    `konfig_lib.php` „nur für Proben": Das wäre ein zweiter Weg in
    Produktionscode.

    *Zwei Fehler beim Bauen des Helfers, beide gemessen und behoben:* Die
    Abbruchsicherung war **je Aufruf** angemeldet — Abschlussfunktionen
    laufen in Anmeldereihenfolge, also legte die äußere den Urstand zurück
    und eine spätere schrieb ihren Zwischenstand darüber. Nach einem Lauf der
    `versandprobe` stand ein **zufälliger `server_key`** in der `config.php`;
    damit wären die versiegelten Sicherungsziele nicht mehr zu öffnen
    gewesen. Und der erste Rückweg schrieb einen `var_export` statt der
    Bytes — der **Kopfkommentar des Installers** („niemals ins Git-Repo
    committen") war danach fort. Jetzt: eine Sicherung je Pfad, und
    zurückgelegt werden die Bytes.

    *Die eigentliche Lehre steht in der Registerzeile:* Z04 misst seit heute
    `server/` **und** `tools/`. Die Zeile zählt eine Globale, die es
    **nirgends** mehr geben darf — ein Bereich, der fehlt, meldet keine Null,
    sondern gar nichts (`CLAUDE.md` 6, in eigener Sache).

256. **Vier Dateien unter `api/` antworten auf eine falsche Methode anders
    als die übrigen siebzehn.** *Aufgenommen 21.09.2026 bei der Vorbereitung
    von Schritt 15 AP3, am selben Tag berichtigt.* **Erledigt am 21.09.2026**
    (Schritt 15 AP3).

    **Der Eintrag stand zuerst falsch hier.** Er behauptete, vier Dateien
    prüften die Anfragemethode **nicht**, und schloss das daraus, dass die
    Registerzeile Z06 sie nicht zählte. Nachgemessen in den Dateien selbst:
    **Alle 21 prüfen die Methode.** Z06 zählte drei davon nur deshalb nicht,
    weil ihr Fehlerschlüssel `methode` heißt statt `method` — deutsche statt
    englischer Schreibung, dieselbe Sache. Ein `GET` ist nirgends in den
    Rumpf gelaufen.

    **Der tatsächliche Befund** war eine Drift in zwei Richtungen:
    `rueckfrage.php`, `schluessel_erneuern.php` und
    `schluesselblatt_pruefen.php` antworteten mit
    `http_response_code(405); echo json_encode(['error' => 'methode']); exit;`
    — anderer Schlüssel, anderer Ausgabeweg. `csp_bericht.php` antwortet mit
    einer stummen **204**, und das ist Absicht: Ein Berichts-Endpunkt sagt
    dem meldenden Browser nichts.

    **Behoben:** Die drei rufen jetzt `api_methode()` wie alle anderen; der
    Schlüssel heißt überall `method`. Kein JavaScript wertet ihn aus
    (nachgemessen über alle 40 Skripte), die Änderung fällt damit unter
    F-ZE-5. `csp_bericht.php` bleibt unberührt — E-ZE-15 nimmt sie
    namentlich aus. Der Rest ihres Ausgabewegs steht als **Nr. 258**.

    *Die Lehre:* Eine Registerzeile, die nicht zählt, belegt **nicht**, dass
    es die Sache nicht gibt — sie belegt, dass das Muster nicht greift. Wer
    aus einer Null auf einen Befund schließt, ohne in die Dateien zu sehen,
    schreibt einen falschen Eintrag, und eine Entscheidung darauf ist
    gegenstandslos.

254. **Die Ratenprobe erwartet fünf Töpfe mit Leiter — es sind sechs.**
    *Aufgenommen 21.09.2026 beim ersten Lauf der Probe gegen eine lokal
    eingerichtete Anlage (Schritt 15 AP2).* **Erledigt am 21.09.2026** (Schritt 15 AP2).

    `tools/ratenprobe/probe.php` prüft `Genau fuenf Toepfe haben eine Leiter`
    gegen die Liste `ingest, ingest_ip, login, login_ip, salt`. Gemessen sind
    **sechs**: `blatt, ingest, ingest_ip, login, login_ip, salt`. Der Topf
    `blatt` (Schlüsselblatt-Prüfung) ist mit **Web 20.24.0** (P5b/AP9)
    dazugekommen, die Erwartung der Probe stammt aus **Web 20.11.0**.

    **Der Befund ist die Probe, nicht der Code.** `RATE_GRENZEN` ist in
    Ordnung; die Probe zählt einen Bestand, der gewachsen ist, gegen eine von
    Hand geführte Zahl — genau die Bauform, vor der diese Datei sonst warnt
    (siehe die Warnung zu gezählten Werten im Kopf). Lauf am 21.09.2026:
    **50 Prüfungen, 1 Befund**, und dieser eine ist es.

    **Warum er einen Monat unbemerkt blieb:** Die Ratenprobe braucht eine
    laufende Installation und hängt nicht in Stufe 1. Zwischen Web 20.24.0
    und heute hat sie niemand gefahren.

    *Behoben am 21.09.2026:* Die Liste `TOEPFE_MIT_LEITER` ist jetzt der
    Sollwert, **und die Zahl im Satz rechnet sich aus ihr** — bis dahin
    stand dieselbe Zahl zweimal da, einmal als Wort und einmal als
    Aufzählung. Wer einen Topf ergänzt, ergänzt die Liste; der Satz stimmt
    von selbst. Lauf danach: **50 Prüfungen, 0 Befunde.**

255. **`lokal_einrichten.sh` richtet eine Anlage ein, deren Handbuch drei
    kaputte Bilder hat.** *Aufgenommen 21.09.2026 (Schritt 15 AP2).*
    **Erledigt am selben Tag** — der Eintrag bleibt wegen der Zahl.

    Der FTPS-Schritt lädt nur `server/` hoch; `docs/` liegt daneben. Deshalb
    kopiert `ausliefern-lauf.yml` `docs/Handbuch.md`,
    `docs/Was-ist-NAdoku.md` und `docs/bilder/` vor dem Sync nach
    `server/doku/`. **Das lokale Einrichten tat das nicht** — es gibt den
    Schritt in der Kette, und nur dort.

    **Was das kostete:** Der Bilderlauf gegen die lokal eingerichtete Anlage
    meldete **48 Konsolenfehler** und ging mit Rückgabe 1 aus — drei fehlende
    Bilder (`tagesuebersicht-desktop.png`, `tagesuebersicht-mobil.png`,
    `schublade-mobil.png`) mal 16 Breiten. Nach dem Kopierschritt: **0**.
    Eine Instanz, die die Zahl nicht zuordnen kann, sucht sie in der eigenen
    Änderung; das hat hier eine Dreiviertelstunde gekostet.

    Der Kommentar in `doku_lib.php` sagt den Fall wörtlich voraus („Wer selbst
    hostet und nur `docs/` neben `server/` legt, bekommt den TEXT und keine
    BILDER"). Er stand nur nicht dort, wo man ihn sucht, wenn der Bilderlauf
    rot wird. Der Kopierschritt steht jetzt als Schritt 6a im Skript, mit der
    Zahl 48 im Kommentar.
268. **Zwei Staging-Läufe zugleich, und der Bilderlauf ohne Demo-Konto —
    kein Tag kam durch die Kette.** *Aufgenommen und erledigt 21.09.2026
    (Vorgriff auf PK-06, Konzept PK).*

    Drei Befunde aus dem ersten Tag durch Kette II. (1) `auslieferung.yml`
    hatte keine `concurrency`-Gruppe; zwei Merges innerhalb einer Minute
    liefen überlappend nach Staging. (2) Die Jobpause des Kreislaufs
    (1800 s) und das Backup-Tor des Nachbarlaufs schlossen einander aus —
    vierzig Aufrufe „angehalten bis …", dann rot ohne eine übertragene
    Datei. (3) Der Bilderlauf meldet sich für 32 Seiten mit dem
    Vorgabekennwort als `demo@gen-em.org` an; auf der neuen Staging-Anlage
    gab es das Konto nicht. Kette II hatte es in Z7 vorgesehen und Z7 mit
    Komplett-Backup und `JOBS_TOKEN` als erfüllt gebucht. Weil das Tor der
    grünen Läufe einen als Ganzes grünen Staging-Lauf verlangt, blieb
    `web-v20.26.3` am Tor hängen (Lauf 35646453443), ohne Produktiv zu
    berühren.

    *Erledigt: eine Gruppe je Umgebung, wartend statt abbrechend; Stufe 2
    auf drei Schritte (E-PK-17), Zeitgrenze 20 min. Der Bilderlauf läuft in
    der Sandbox; auf Staging wird kein Konto mit Vorgabekennwort angelegt.*

267. **Der Alias `AS manual` scheiterte auf MySQL 8.4 — der Exportweg war
    nach Nr. 238 nur zur Hälfte umgestellt.** *Aufgenommen 21.09.2026 aus dem
    ersten Stufe-2-Lauf gegen Staging (Kennung `097D7622`).* **Behoben in Web
    20.26.3.** *Bestätigt am 21.09.2026: Kreislauf edbak gegen Staging
    (MySQL 8.4.10) grün in 104 s, Lauf 35639445224, Versuch 2 — vorher
    dreimal die Fünfzehn-Minuten-Grenze.*

    Nr. 238 benannte die Spalte um und bewahrte den Dateischlüssel über
    `uhr_gesperrt AS manual`. MySQL reserviert MANUAL von 8.4.0 bis 8.4.10
    auch als Alias; Staging läuft auf 8.4.10. `api/backup_data.php` antwortete
    mit 1064 und HTTP 500, der Browser wartete fünfzehn Minuten auf einen
    Download, drei Stufe-2-Läufe blieben ohne Ursache.

    **Gefunden lokal:** Die Anwendung auf einem MySQL-8.4.0-Container fiel
    beim ersten Kreislauf mit derselben Kennung im lokalen Fehlerprotokoll;
    gegen MariaDB 10.11 war sie grün. Das ist P-PK-02 des Konzepts PK.

    **Was daraus folgt:** Die Staging-Datenbank ist MySQL 8.4.10, nicht
    MariaDB — `docs/Technik.md` 6.3a führte sie als unbekannt. Ein zweiter
    Alias auf ein reserviertes Wort kommt im Serverquelltext nicht vor
    (nachgezählt gegen die in 8.4 neu reservierten Wörter).

241. **Die Anwendung überlässt ihre Sitzungen dem Hoster.**
    *Aufgenommen 20.09.2026 aus dem Befund der Kettenhärtung (AP1).*

    *Erledigt am 20.09.2026 in Web 20.26.0 (Schritt 16, PR #61):
    `server/sitzung_lib.php` legt `.sitzungen/` mit 0700 an, gerufen an
    zwei Stellen (`db.php`, `install.php` — **seit Web 20.27.0 nur noch aus
    `sitzung_starten()`**, Schritt 15 AP2/E-ZE-06: Die Ablage wird
    eingerichtet, wenn eine Sitzung startet, und nicht mehr bei jeder
    Anfrage, die `db.php` lädt); Rückfall auf den Hosterpfad
    mit Anzeige; vierter Schreibort (Empfohlen) und Prüfpunkt
    „Sitzungsablage" (Muss, dreiwertig) in `plattform_pruefen()`;
    Aufräumteil „Sitzungsdateien" (nur `sess_*`). Gemessen: 2
    Aufrufstellen, 8 von 9 Sitzungsstarts mit `db.php` davor,
    Dateizählung 1/0/1, Marker 0/1 Probedateien, „1 gelöscht". Offen ist
    die Prüfung durch die Betreiberin — Liste in
    `docs/konzepte/Pruefdokument-Sitzungsablage.md` — und der achte
    Schutzlistenpfad, der zu Kette II gehört.*

    **Der achte Schutzlistenpfad ist mit Kette II/AP3 eingetragen**
    (20.09.2026): `.sitzungen/**` und `.sitzungen/` stehen in beiden
    `exclude`-Blöcken von `auslieferung.yml` und in `CLAUDE.md` 3. Damit
    ist auch dieser Rest erledigt; offen bleibt allein die Prüfung durch
    die Betreiberin.
    Zugeordnet: **Schritt 16**, Konzept
    `docs/konzepte/Konzept-Sitzungsablage.md` (freigegeben 20.09.2026).

    `session.save_path` zeigt dorthin, wohin der Hoster ihn zeigen lässt. Auf
    der Staging-Anlage bei lima-city ist das ein **geteiltes** Verzeichnis:
    `/home/webpages/tmp`, Rechte **0773**, Eigentümer **root**,
    `gc_probability` **0**. Es ist gutgegangen, weil der Hoster das Auflisten
    sperrt — **die Anwendung hätte es nicht gemerkt.** Für Produktiv ist
    derselbe Wert nicht erhoben, für Selbsthoster ist er offen.

    **Was auf dem Spiel steht, nüchtern:** Eine Sitzungsdatei trägt kein
    Schlüsselmaterial, die E2E-Zusage ist nicht berührt. Sie trägt aber die
    Sitzung selbst — der Dateiname *ist* die Sitzungs-ID. Wer sie liest, ist
    angemeldet und sieht die Klartextliste aus `CLAUDE.md` 4; bei
    `role = admin` die Verwaltung.

    **Die Anwendung legt ihre Sitzungen künftig selbst ab** — eigenes,
    nicht auflistbares Verzeichnis `.sitzungen/` mit `0700` (Stufe 2).
    Stufe 3 (Sitzungen in der Datenbank) ist mit vier Gründen verworfen, mit
    Verfallsdatum: Sie wird alternativlos, sobald mehrere Anwendungsserver
    eine Ablage teilen sollen — das steht auf keinem Fahrplan.

    **Gemessen, und es hat das Konzept umgeworfen:** Es gibt **neun**
    `session_start()`-Aufrufe in neun Dateien, nicht drei. Mit der Annahme
    „drei" hätte `login.php` die Sitzung beim Hoster abgelegt und
    `auth_guard.php` sie in `.sitzungen/` gesucht — **niemand hätte sich
    anmelden können.**


235. **`actions/checkout@v4` hängt an einer abgekündigten Laufzeit.**
    *Aufgenommen 18.09.2026 aus den Annotations des Auslieferungslaufs.*

    GitHub meldet bei jedem Lauf: *„Node.js 20 is deprecated. The following
    actions target Node.js 20 but are being forced to run on Node.js 24:
    `actions/checkout@v4`."* Die Zwangsumleitung auf Node 24 ist eine
    Übergangslösung; fällt sie weg, bricht der Schritt **„Code auschecken"** —
    und der ist der **erste jedes Jobs**. Dann brechen Stufe 1, beide
    Deploy-Wege und die Integritätswache **gleichzeitig**. Der Produktionslauf
    ist der, bei dem es am spätesten auffällt, weil er am seltensten läuft.

    **Fünf Fundstellen**, gemessen am 18.09.2026 gegen `7675f9b`: je einmal im
    Schritt „Code auschecken" von `pruefung.yml` und `integritaet.yml`,
    dreimal in `auslieferung.yml` (Jobs `staging`, `Prüfung Stufe 2` und
    `produktion`). `SamKirkland/FTP-Deploy-Action@v4.4.0` (zweimal in
    `auslieferung.yml`) ist **nicht** betroffen — sie taucht in der Warnung
    nicht auf.

    Zu tun: alle fünf Stellen **auf einmal** heben, sonst bleibt eine zurück;
    die zu setzende Fassung nachschlagen statt aus dem Gedächtnis eintragen.
    Danach `tools/kettenaufrufe/pruefen.py --probe` und `pruefen.py`. Keine
    Versionsstufe, kein Changelog — die Änderung fasst nur `.github/` an
    (`CLAUDE.md` 2, Präzedenz `27c7673`).

    ### Erledigt am 18.09.2026 — auf `v7`, und was dabei mitkommt

    Alle **fünf** Fundstellen auf einmal gehoben, `v4` → `v7`. Nachgeschlagen
    statt aus dem Gedächtnis gesetzt:

    | Fassung | `runs.using` in `action.yml` |
    |---|---|
    | `v4` | `node20` — die abgekündigte |
    | `v5`, `v6`, `v7` | `node24` |

    `v7.0.1` ist die neueste; gesetzt ist der wandernde Major-Tag `v7`, wie
    zuvor `v4` (die Linie des Hauses; `SamKirkland/FTP-Deploy-Action@v4.4.0`
    ist die Ausnahme und bleibt festgenagelt).

    **Zwei Brüche kommen mit, und beide berühren Bauformen dieser Anlage.**
    Der Sprung überspringt zwei Hauptversionen; das ist eine bewusste
    Entscheidung des Auftraggebers vom 18.09.2026 gegen die vorsichtigere
    Empfehlung, auf `v5` zu gehen (dort: **null** dokumentierte Brüche).
    Was zu beobachten ist:

    - **v6.0.0 — „Persist creds to a separate file".** Die Zugangsdaten
      liegen nach dem Auschecken anders. Betroffen wäre ein Schritt, der
      nach dem Checkout selbst mit Git spricht; die Kette tut das heute
      nicht. **Wenn ein Schritt mit `git push` oder einem Token aus der
      Git-Konfiguration dazukommt, gehört das hier nachgesehen.**
    - **v7.0.0 — „Block checking out fork PR for `pull_request_target` and
      `workflow_run`".** `integritaet.yml` läuft auf `workflow_run` (und auf
      `schedule`). Sie checkt den **eigenen** Stand aus, keinen Fork-PR —
      die Sperre sollte sie nicht treffen. **Belegt ist das nicht**, es ist
      gelesen, nicht gemessen: Der erste Lauf der Wache nach diesem Paket
      ist die Probe. Bleibt sie still oder wird sie rot, steht die Ursache
      hier.

    **Geprüft:** `tools/kettenaufrufe/pruefen.py --probe` → **10 von 10**;
    `pruefen.py` → **28 Aufrufe, 0 Befunde, 0 ungeprüft**; YAML aller drei
    Läufe weiterhin gültig. Keine Versionsstufe, kein Changelog — die
    Änderung fasst nur `.github/` an (`CLAUDE.md` 2, Präzedenz `27c7673`).

221. **Waagerechter Überlauf auf der Datenschutzseite bei 360 px.**
    *Aufgenommen 17.09.2026 aus demselben Lauf.*

    ```
    05-datenschutz    Überlauf bei 360
    ```

    Eine Seite, eine Breite, in einem Lauf über 50 Seiten und acht Breiten —
    die übrigen 49 sind ohne Befund. 360 px ist die schmalste gemessene
    Breite und damit das kleine Telefon.

    **Noch nicht eingegrenzt:** Welches Element überläuft, steht nicht im
    Protokoll, sondern im Bericht des Laufs
    (`tools/screenshots/ausgabe/bericht.md`), und der liegt auf dem Läufer.
    Nachstellen lässt es sich örtlich mit
    `node tools/screenshots/aufnehmen.mjs --nur 05-datenschutz` gegen eine
    lokale Installation. Verdacht ohne Beleg: ein langer Rechtstext ohne
    Umbruchmöglichkeit (Adresse, E-Mail, URL) oder eine Tabelle.

    ### Nachtrag vom 17.09.2026 — was es NICHT ist

    Örtlich nicht nachstellbar: `aufnehmen.mjs --nur 05-datenschutz` gegen
    eine lokale Installation meldet **kein Überlauf**, bei Maßstab 1× wie 2×.
    Ausgeschlossen, jeweils gemessen:

    | geprüft | Ergebnis |
    |---|---|
    | Markup, das Staging ausliefert | **byteidentisch** mit dem örtlichen: 2104 B, dieselben 17 Klassen, Titel 36 Zeichen, längstes Wort 20 Zeichen, keine Tabelle, kein `<pre>` |
    | `assets/style.css` auf Staging | **byteidentisch** mit dem Repositorium, 200 452 B |
    | die 10 `.woff2` des Stylesheets | **alle vorhanden, alle byteidentisch** |
    | Maßstab | weder 1× noch 2× läuft örtlich über |

    Es liegt also **nicht** am Rechtstext (die Seite trägt auf beiden Seiten
    nur 230 Textzeichen — den leeren Zustand), nicht am Markup, nicht an der
    Gestaltung und nicht an den Schriften.

    **Warum es hier endet:** `aufnehmen.mjs` meldet alle drei Rollen an, bevor
    es das erste Bild macht — auch bei `--nur` auf einer Seite mit
    `"rolle": "aus"`. Ohne die Staging-Zugangsdaten lässt sich der Lauf von
    hier aus nicht gegen Staging fahren. *(Das ist nebenbei eine eigene
    Ungeschicklichkeit des Werkzeugs, aber keine, die dieser Eintrag
    mitbehebt.)*

    **Was stattdessen geändert wurde:** Der Bericht des Laufs trägt eine
    Spalte **`Verursacher`** — das Element, das überläuft. Sie wurde auf dem
    Läufer mit dem Arbeitsverzeichnis weggeräumt. Ab Web 20.16.2 schreibt der
    Kettenschritt `ausgabe/bericht.md` in die Zusammenfassung des Laufs. **Der
    nächste rote Lauf beantwortet diesen Eintrag selbst.**

    *Verbleibender Verdacht, ohne Beleg:* die Chromium-Fassung. Die Kette holt
    `playwright@1.56` (Chromium 141), örtlich steht eine andere.

    ### Nachtrag vom 17.09.2026 — der nächste Lauf war GRÜN

    Der Auslieferungslauf auf `main` (`e5844c4`, 13:38–13:50) meldete
    `05-datenschutz` mit **kein Überlauf**, und den ganzen Bilderlauf mit
    **0 Überlauf** über 400 Bilder. Der Befund hat sich **nicht wiederholt**.

    **Damit ist er nicht erklärt, sondern einmalig geblieben** — und das ist
    ein Unterschied, den dieser Eintrag stehen lässt, statt ihn wegzuräumen:
    Zwischen den beiden Läufen hat niemand etwas an der Seite, am Stylesheet
    oder an den Schriften geändert (alle drei waren schon beim ersten Mal
    byteidentisch mit dem Repositorium). Was bleibt, ist eine Messung, die
    einmal anschlug und beim zweiten Mal nicht.

    **Der Eintrag bleibt deshalb offen, aber ohne Arbeitsauftrag.** Schlägt
    er wieder an, steht der Bericht seit Web 20.16.2 in der Zusammenfassung
    des Laufs und nennt den **Verursacher** — dann ist es in fünf Minuten
    erledigt statt in einer Stunde Ausschlussverfahren. Bleibt er drei
    weitere Läufe still, gehört er nach *Erledigt* mit dem Vermerk „einmalig,
    nicht reproduzierbar".

    *Was dabei offen zutage kam und nicht zu diesem Eintrag gehört:*
    `aufnehmen.mjs` meldet alle drei Rollen an, bevor es das erste Bild
    macht — auch bei `--nur` auf einer Seite mit `"rolle": "aus"`. Genau das
    hat die örtliche Nachstellung gegen Staging verhindert.

    ### Abschluss vom 18.09.2026 — reproduziert, gemessen, behoben

    Der Auslieferungslauf nach dem P5b-Deploy (35314203760, `7675f9b`,
    Web 20.24.1) meldete **zwei** Überläufe bei 360 px: `05-datenschutz`
    **und** `04b-nutzungsbedingungen` — beide Rechtstextseiten, beide
    dieselbe Bauform.

    **Der Verursacher stand NICHT im Bericht.** Die Spalte trug `—`; das
    versprochene „in fünf Minuten erledigt" hat der Lauf nicht eingelöst. Der
    Täter-Finder findet den Täter hier nicht (eigener Befund, siehe unten).

    **Stattdessen von Hand gemessen**, örtlich, mit einem Absatz je
    Verdachtsart in der Datenbank:

    ```
    Fensterbreite 360 · Dokumentbreite 487 · Überlauf 127 px
      <p> in .text   scrollWidth 350 / clientWidth 302
         „A3 Eine Mailadresse: datenschutzbeauftragte@staging.nadoku.g…"
    ```

    Es sind **lange Zeichenketten ohne Umbruchstelle** — eine Mailadresse und
    zwei Adressen im Fließtext. Der `.text`-Baustein hatte kein
    `overflow-wrap`, und sein Inhalt kommt aus der **Datenbank**: Die
    BetreiberIn schreibt ihn und soll eine Adresse hinschreiben dürfen, ohne
    zu wissen, wie breit ein Handy ist.

    **Behoben** mit `overflow-wrap:break-word` an `.text`
    (`server/assets/style.css`). `break-word` und nicht `anywhere` — die
    Begründung steht im Kommentar daneben. Nachgemessen: **127 px → 0 px**,
    Bilderlauf über alle drei Rechtstextseiten **0 Überlauf**.

    **Was damit NICHT erklärt ist, und das bleibt hier ausdrücklich stehen:**
    Der Einzelfall vom 17.09.2026 hatte eine **andere** Lage — die Seite trug
    damals nur den leeren Zustand (230 Textzeichen, oben nachgemessen und
    festgehalten). Ein Rechtstext, der überläuft, kann es damals also nicht
    gewesen sein. Der Eintrag geht trotzdem nach *Erledigt*, weil der
    reproduzierbare Befund behoben ist; der damalige Einzelfall bleibt
    unerklärt und ist seither in keinem Lauf wiedergekehrt.

223. **Die Anwendung ließ sich nicht mehr installieren.**
    *Aufgenommen 16.09.2026 beim Aufbau des Prüfstands für P5b; behoben am
    selben Tag in Web 20.15.3.*
    `install.php` antwortete HTTP 500 mit leerem Rumpf — kein Formular, keine
    Meldung. Die Kette ist kurz und jedes Glied für sich richtig:
    `install.php` lädt `ui.php`, damit ihr Formular aussieht wie die
    Anwendung; `ui_seite_start()` lädt seit P5a/AP4 `kopfzeilen_lib.php`,
    damit die Kopfzeilen vor der ersten Ausgabezeile stehen;
    `kopfzeilen_lib.php` lud `db.php`; `db.php` verlangt `config.php` hart.
    Vor der Einrichtung gibt es keine `config.php`.

    **Warum kein Prüfmittel ihn gesehen hat, und warum das die eigentliche
    Lehre ist.** Der Fehler trifft ausschließlich die Installation, die noch
    nicht stattgefunden hat. Jede bestehende Anlage läuft weiter. Und jedes
    Prüfmittel des Projekts — `ingestprobe`, `spurprobe`, `komplettprobe`,
    `jobprobe`, `screenshots`, `pruefkonten` — *setzt eine laufende
    Installation voraus*, statt eine einzurichten. Der Weg, den eine
    Betreiberin genau einmal geht, ist damit der einzige, den niemand geht.
    Gesehen wurde er, weil `lokal_einrichten.sh` ihn geht.

    **Behoben in `kopfzeilen_lib.php`, nicht in `db.php`.** Dort ist das harte
    `require` richtig. `kopfzeilen_lib.php` dagegen sagt im eigenen Kopf, sie
    komme „ohne Datenbank aus" — sie kam nur nicht ohne `config.php` aus.
    Jetzt `is_file()` vor dem `require` und `function_exists()` vor den vier
    `app_state`-Aufrufen, mit Rückfall auf dieselben Vorgaben wie bei
    fehlender Tabelle.

    **Nicht zu verwechseln mit Nr. 214**, obwohl beide am selben Tag
    entstanden sind und dieselbe Datei betreffen: 214 nimmt `install.php` aus
    der **Auslieferung**, weil das Runbook sie löschen heißt. Dieser Eintrag
    macht sie überhaupt erst wieder **lauffähig**. Die beiden greifen
    ineinander — seit 214 muss die Datei von Hand hinauf, und eine Datei, die
    man von Hand hinauflädt, um genau einmal eine Anlage einzurichten, muss
    beim ersten Aufruf funktionieren. Ohne 215 wäre 214 der Weg in eine
    Sackgasse.

    **Zu tun bleibt die Wache:** ein Prüfschritt, der die Einrichtung selbst
    fährt (`lokal_einrichten.sh` ist der fertige Weg, Stufe 1 könnte ihn gegen
    eine Wegwerf-Datenbank laufen lassen). Ohne ihn fällt dieselbe Lücke beim
    nächsten Umbau der Ladekette wieder auf. *Abnahme:* Ein Prüflauf, der auf
    einem Stand ohne `server/config.php` HTTP 200 und ein Formular-Token von
    `install.php` bekommt. Zuordnung: Backlog-Runde oder P5c.

224. **`frame-ancestors` stand in einer Report-Only-Richtlinie und war dort wirkungslos.**
    *Gefunden 16.09.2026 beim Bilderlauf mit drei Engines (P5b/AP1, auf Rückfrage
    des Auftraggebers); behoben am selben Tag in Web 20.16.5.*
    CSP Level 3 sagt, dass `frame-ancestors` in einer Report-Only-Richtlinie
    **ignoriert** wird. WebKit sagt es laut: „The Content Security Policy
    directive 'frame-ancestors' is ignored when delivered in a report-only
    policy." — **ein Konsolenfehler je Seitenaufruf**, auf jeder Seite der
    Anwendung. Gemessen: **16 Konsolenfehler bei 16 Bildern**; Chromium und
    Firefox melden nichts.

    **Was das gekostet hat, ist nicht der Schutz, sondern das Prüfmittel.**
    Clickjacking wehrt `X-Frame-Options: DENY` ab, und die Zeile steht
    unabhängig davon in beiden Fällen — es gab **kein** Loch. Die
    CSP-Direktive schützte in Report-Only nichts und meldete nichts; was sie
    tat, war, den Bilderlauf mit WebKit auf **jeder** Seite rauschen zu
    lassen. Ein Prüfmittel, das überall meldet, findet nichts mehr: Der echte
    Fehler stünde daneben und fiele nicht auf.

    Behoben: Die Direktive steht jetzt nur in der **scharfen** Fassung, wo sie
    auch wirkt.

    **Die Lehre ist die Engine-Wahl.** Der Fund kam zustande, weil der
    Auftraggeber nachfragte, ob auf drei Browsern geprüft wird — es war
    nur Chromium gelaufen. `tools/screenshots/LIESMICH.md` empfiehlt den
    dreifachen Lauf bei Gestaltungsrunden; die Empfehlung hat sich zum
    zweiten Mal bezahlt gemacht (das erste Mal war Nr. 185).

225. **Die Profilseite brach auf halber Höhe aus ihrem Seitengerüst aus.**
    *Entstanden 07.09.2026 (S9/AP4), gefunden 17.09.2026 beim Ansehen eines
    Bildes zu P5b/AP6, behoben am selben Tag in Web 20.21.1.*
    Auf `einstellungen.php?t=profil` stand ein `ui_karte_ende()` zu viel. Es
    schloss keine Karte, sondern gab ein `</div></section>` ohne Gegenstück
    aus; für ein `</div>` ohne offenes `div` nimmt der Parser das nächste, das
    er findet, und das war `div.rahmen`. Damit endeten `form`, `main.inhalt`
    und `rahmen` mitten auf der Seite, und alles danach — **Datenschutz,
    Passwort ändern, Was dein Konto hält, Konto löschen** und der Knopf
    „Profil speichern" — hing direkt am `body`. Gemessen bei 1440 px: `left` 0
    statt 276, Breite 1440 statt 1148; die Karten liefen unter der
    Seitenleiste hindurch über die volle Fensterbreite.

    **Das Speichern ging weiter**, weil ein Knopf seinen Formularbezug aus dem
    Parsen behält, auch wenn das `form`-Element implizit geschlossen wurde. Der
    Schaden war sichtbar, nicht funktional.

    **Der eigentliche Befund ist, dass kein Prüfmittel angeschlagen hat.**
    `scrollWidth` blieb gleich `innerWidth` — es lief nichts über, es lag nur
    falsch. Die Konsole blieb still, die Knopfhöhen stimmten. Der Bilderlauf
    meldete für diese Seite in allen drei Engines „kein Überlauf, 0
    Konsolenfehler, 0 falsche Knopfhöhen": **drei Nullen neben einer kaputten
    Seite**. Genau der Fall, vor dem `CLAUDE.md` 6 warnt — eine grüne Zahl ist
    erst dann ein Beleg, wenn sie das Gemessene benennt, und „kein Überlauf"
    benennt nicht „liegt an der richtigen Stelle".

    **Gegenprobe:** ein Lauf über 24 Seiten in beiden Rollen, der Karten
    zählt, die nicht in `main.inhalt` hängen — **80 Karten geprüft, 0
    außerhalb** (vorher vier auf der Profilseite).

    **Zu tun:** diese Zählung in den Bilderlauf aufnehmen, damit sie nicht
    beim nächsten Mal wieder von Hand entstehen muss. Sie kostet einen
    `evaluate()`-Aufruf je Aufnahme und braucht keine zweite Sitzung.

226. **Ein Meldungskasten trug einen Ton, den es nicht gibt.**
    *Entstanden und gefunden 17.09.2026 (P5b/AP7 bzw. AP6), behoben am selben
    Tag in Web 20.21.1.*
    In `betrieb_server.php` stand von Hand `<div class="meldung
    meldung-blau">`. Die Töne der Anwendung heißen `fehler`, `warn`, `ok`,
    `info`, `schutz`; **`meldung-blau` hat keine Regel im Stylesheet**. Der
    Kasten stand ungestaltet da — weißer Hintergrund, kein Symbol, keine
    Fehlermeldung.

    `ui_meldung_markup()` **wirft** bei einem unbekannten Ton, und ihr
    Kopfkommentar beschreibt genau diesen Schaden („die Spurenseite trug so
    zwei Jahre lang zwei weiße Meldungen"). Wer von Hand baut — hier nötig,
    weil der Knopf in einem eigenen Formular steckt —, hat diesen Schutz
    nicht.

    Gefunden von `tools/vollstaendigkeit/` („im Markup ohne Regel"), behoben
    zu `meldung-info` samt `role="status"` und Symbol, wie es die anderen
    vier handgebauten Kästen der Anwendung führen.

    **Zu tun:** Die Handbauten zählen — es sind fünf Stellen. Eine Variante
    von `ui_meldung_markup()`, die ein Formular um den Knopf legt, machte alle
    fünf überflüssig und nähme ihnen die Möglichkeit, einen Ton zu erfinden.

231. **Die drei Häkchen der Registrierung werden nicht festgehalten — und
    für Texte verlangt, die nicht in Kraft sind.**
    *Erledigt am 17.09.2026 in Web 20.22.2 (P5b): Die Registrierung hält
    die Einwilligungen jetzt fest und verlangt nur, was in Kraft ist.*

    *Aufgenommen 17.09.2026 beim Nachprüfen der Rückfrage zur AVV
    (E-P5b-25). Zwei Mängel an derselben Stelle, in AP3/AP4 entstanden.*

    **Erledigt am 17.09.2026 mit Web 20.22.2.** Der Eintrag bleibt hier
    stehen, bis er beim Abschluss von P5b nach *Erledigt* wandert — die
    Beschreibung darunter ist der Befund, nicht der offene Stand.
    Entschieden wurde die Frage aus (a): **Festgehalten wird bei der
    Registrierung.** Fünf Prüffälle gemessen, Einzelheiten im Prüfdokument
    unter F11.

    **(a) Kein Eintrag.** `server/registrieren.php` verlangt alle
    Schlüssel aus `RT_EINWILLIGUNG` als Pflichthaken und legt danach das
    Konto über `konto_anlegen()` an — eine Zeile in `konto_einwilligungen`
    entsteht dabei nie. Der einzige Schreibweg ist `einwilligung_setzen()`
    in `server/einwilligung_lib.php`, und diese Funktion wird im ganzen
    Server genau einmal aufgerufen: in `server/einwilligung.php`, also am
    **Tor beim Login**. E-P5b-05 verlangt dagegen „Gespeichert je Konto mit
    Fassungskennung (`stand_am` des Textes) und Zeit".

    **Die Wirkung ist nicht, dass der Nachweis fehlt** — das Tor fasst
    jedes Konto beim ersten Login und schreibt dann. Sie ist, dass er an
    einer anderen Stelle entsteht als gedacht, und dass die Registrierende
    dieselben drei Fragen zweimal beantwortet: einmal im Formular, einmal
    beim ersten Anmelden. Ob das Tor als Nachweispunkt sogar der bessere
    ist — dort ist die Person authentifiziert, bei der Registrierung ist
    die Adresse nur behauptet —, lässt sich vertreten. **Nur steht es
    nirgends.** Solange es nicht entschieden ist, ist es kein Entwurf,
    sondern eine Lücke.

    **(b) Annahme eines leeren Dokuments.** Die Registrierung prüft
    `stand_am` nicht; der Begriff kommt in `registrieren.php` kein einziges
    Mal vor. Das Tor prüft ihn sehr wohl — `einwilligung_lib.php`
    überspringt jeden Text ohne Standdatum, und `docs/Technik.md` begründet
    das: „Ein Text ohne Standdatum verlangt nichts. Sonst sperrte ein leer
    angelegter Platzhalter alle Konten aus." Solange die geprüften Texte
    nicht eingespielt sind — und das ist der geplante Zustand bis
    E-P5b-24 —, muss eine Registrierende also den Haken *„Ich nehme die
    Vereinbarung zur Auftragsverarbeitung (AVV) an"* setzen, während
    `avv.php` anzeigt: *„noch keine Vereinbarung zur Auftragsverarbeitung
    hinterlegt."*

    **Beide Mängel gehören in ein Paket**, weil sie dieselbe Stelle
    anfassen und einander bedingen: Was festgehalten wird, kann nur sein,
    was auch in Kraft ist. Zu klären ist dabei die Frage aus (a) — Eintrag
    schon bei der Registrierung oder bewusst erst am Tor —, und die
    Antwort gehört als Entscheidung ins Konzept, nicht in einen Kommentar.

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

    **Erledigt am 17.09.2026 in P5b/AP6 (Web 20.21.0).** Nicht wie oben
    vorgeschlagen in `konto.json`, sondern als **Spalte `users.backup_pakete`**
    (`NULL` heißt „die Vorgabe der Installation gilt"). Der Grund ist die
    Reihenfolge des Lesens: `edbak_verdraengen()` läuft, während der neue Stand
    geschrieben wird — die Begleitdatei desselben Ordners ist in diesem
    Augenblick die unzuverlässigste Quelle, die zur Verfügung steht. Die
    Datenbank kennt den Wert unabhängig davon, ob der Ordner schon existiert.
    Eingebaut als `edbak_aufbewahrung_konto(string $kennung)`, benutzt von
    `edbak_verdraengen()`; das Zahlenfeld steht in der **Kontoverwaltung**
    (Karte „Mengen und Grenzen") und nicht auf der eigenen Kontoseite — wer
    seine eigene Aufbewahrung hochsetzen kann, hat die Zahl der Installation
    nicht mehr in der Hand.

    **Abnahme: 4 Fälle, 4 bestanden.** Ohne eigene Zahl gilt die
    Installationszahl (2); eigene Zahl 7 schlägt sie; ein Ordner ohne Konto
    fällt auf 2 zurück; eine leere Kennung ebenso.

    **Dieser Eintrag ist zuerst falsch gelesen worden, und das ist der
    lehrreichere Teil.** AP6 trägt im Konzept die Überschrift „Aufbewahrung je
    Konto (Nr. 48)" — dieselben vier Wörter wie hier. Daraus wurde eine
    **Aufbewahrungsfrist für Einsätze**: ein Feld `users.aufbewahrung_tage`,
    nach dessen Ablauf Einsätze verschwinden. Gemeint war die **Zahl der
    Sicherungspakete**, wie der erste Absatz oben sagt. Berichtigt vor dem
    Commit, also nie ausgeliefert (F5 im Prüfdokument P5b). Ein Feld zu viel
    ist ein Fehler; ein Feld, das still löscht und falsch beschriftet ist, wäre
    ein Schaden gewesen — und es hätte unter einer Nummer gestanden, die etwas
    ganz anderes wollte. **Eine Backlog-Nummer im Konzept ist ein Verweis, kein
    Titel:** Wer sie umsetzt, liest den Eintrag.

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

    *Zuordnung 15.09.2026:* **Konzept P5a** (`docs/konzepte/Konzept-P5a-Kette-und-Fundament.md`), AP3 (E-P5a-20: `wiederherstellen.php` setzt den Katalog-Hash zurück, die nächste Anfrage prüft und der Torwächter schaltet die Wartung).

    **Erledigt am 15.09.2026 in P5a/AP3 (Web 20.6.0).** Weder die eine noch die
    andere der beiden zur Wahl gestellten Möglichkeiten — eine dritte, die
    beide überflüssig macht: `wiederherstellen.php` **wirft am Ende des
    Einspielens den Zwischenspeicher des Torwächters weg**
    (`migrationen_tor_zuruecksetzen()`), und die nächste angemeldete Anfrage
    rechnet neu. Findet sie fehlende Migrationen, **schaltet der Torwächter
    die Wartung ein** und führt zur Anmeldung; `update.php` bleibt zweistufig
    und der Mensch bestätigt weiterhin. Der Schritt hängt damit nicht mehr am
    Gedächtnis, sondern an der Anwendung.

    **Warum der Zwischenspeicher überhaupt wegmuss:** Der eingespielte Dump
    bringt `schema_migrations` **und `app_state`** der Quellinstallation mit —
    also auch die gespeicherte Antwort des Torwächters. Passte der
    Katalog-Hash dieser Installation zufällig dazu, behauptete sie einen Stand,
    den es hier nicht gibt: eine unfertige Installation bliebe offen, eine
    fertige bliebe zu. Geworfen statt neu gerechnet, weil das Rechnen 46
    Katalogeinträge kostet und die nächste Anfrage es ohnehin tut.
    Gemessen in `tools/wartungsprobe/` (67 Erwartungen, 0 nicht erfüllt).

67. **`csrf_check()` hat keinen API-Zweig.**
    *Aufgenommen aus einer Gegenprüfung vom 23.08.2026; die Zahlen sind am
    02.09.2026 nachgezählt (S4/D2).*
    `require_admin()` verzweigt daneben nach `ist_api_aufruf()` und antwortet
    einem Endpunkt mit JSON; `csrf_check()` rendert unbedingt eine HTML-Seite.
    Ein Endpunkt, der sie aufriefe, schickte einer `fetch()`-Anfrage also eine
    Fehlerseite statt eines Fehlerobjekts — die Oberfläche zeigte „unerwartete
    Antwort" statt „Sitzung abgelaufen".
    **Bisher folgenlos, weil es diesen Aufrufer nicht gibt.** Von den **17**
    Dateien unter `server/api/` (gemessen 13.09.2026; 15 bei der Zählung vom
    02.09.2026) ruft **keine** `csrf_check()` auf. Die **zwölf**, die POST
    annehmen, prüfen jede selbst gegen `HTTP_X_CSRF` — seit dem 02.09.2026
    ist `pat_anheben.php` dazugekommen; die meisten ändern Zustand, zwei
    (`backup_spuren.php`, `export_data.php`) lesen nur und benutzen POST für
    die Nutzlast. Die übrigen **fünf** (`backup_data.php`,
    `kopplung_stand.php`, `mission.php`, `range.php`, `suchindex.php`) sind
    streng GET-only, weisen alles andere mit 405 ab und haben kein
    Schreib-SQL; ihnen fehlt die Prüfung also nicht — `kopplung_stand.php`
    sagt im Kopf, warum. Die Invariante hält.
    Es ist damit eine **unausgesprochene Invariante**, keine Störung — und die
    Nachzählung hat keinen ungeschützten schreibenden Endpunkt gefunden.
    **Zwei Einschränkungen an diesen Sätzen**, aus einer Gegenprüfung vom
    02.09.2026, damit die nächste Zählung nicht darauf hereinfällt:
    `kdf_upgrade.php` prüft `HTTP_X_CSRF` erst **nach** dem Demo-Ausstieg —
    die Zeile davor steigt für das Demo-Konto mit `json_out(['ok' => true,
    …])` aus (Zeilen 69 und 70, gemessen 13.09.2026; 66 und 67 bei
    Aufnahme). Heute folgenlos,
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
    **Der Unterpunkt ist erledigt (Backlog-Runde 3, AP4, Web 19.3.1, 13.09.2026);
    der Punkt selbst bleibt offen und liegt bei P5.** In
    `server/api/kdf_upgrade.php` steht die CSRF-Prüfung jetzt **vor** dem
    Demo-Ausstieg. Bis dahin kam ein Aufruf ohne Formular-Token für das
    Demo-Konto mit 200 zurück, während jedes andere Konto 403 sah — folgenlos
    nur, weil hinter dem Ausstieg nichts steht. Am Prüfstand gemessen, alter
    gegen neuer Stand bei sonst gleichem Aufbau: **ohne Header vorher 200,
    jetzt 403 `{"error":"csrf"}`; mit Header unverändert 200 mit
    `uebersprungen: demo`.** Dazu falscher und leerer Header, beide 403. Die
    Reihenfolge ist im Kopfkommentar der Datei begründet, der stille Erfolg in
    `docs/Technik.md` als bedingt gekennzeichnet.
    **Offen bleibt der Hauptpunkt:** der `ist_api_aufruf()`-Zweig in
    `csrf_check()` (oder die Invariante im Funktionskopf), damit die Endpunkte
    unter `server/api/` die Prüfung nicht jeder selbst schreiben. Vor dem
    Anfassen neu zählen — siehe die Lehre oben.

    *Zuordnung 15.09.2026:* **Konzept P5a** (`docs/konzepte/Konzept-P5a-Kette-und-Fundament.md`), AP4 (mit den Kopfzeilen, weil beides in `auth_guard.php` wohnt).

    **Erledigt am 15.09.2026 in P5a/AP4 (Web 20.6.0).** Von den beiden unter
    „Zu tun" genannten Wegen der erste: `csrf_check()` hat jetzt denselben
    `ist_api_aufruf()`-Zweig wie `require_admin()` daneben — ein `fetch()`
    bekommt `{"error":"csrf"}` mit 403 statt einer HTML-Seite, an der es sich
    einen Syntaxfehler holt. Die zwölf Endpunkte prüfen nicht mehr jeder für
    sich: `csrf_ok()` nimmt Feld **und** Kopfzeile, und in `csrf_check()` fällt
    die eine Entscheidung, in welcher Sprache das Nein kommt.

    **Und die Lehre oben ist eingehalten worden:** vor dem Anfassen neu
    gezählt. Die Zahl **17 Dateien** vom 13.09.2026 stand noch, die zwölf
    POST-Annehmer auch. Ein ungeschützter schreibender Endpunkt war wieder
    nicht darunter — die Invariante hat drei Zählungen überlebt, und jetzt
    steht sie nicht mehr nur im Kopf der Funktion, sondern im Code.

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

    *Zuordnung 15.09.2026:* **Konzept P5a** (`docs/konzepte/Konzept-P5a-Kette-und-Fundament.md`), AP10 (E-P5a-03: Anzeige je Ziel als Grundlage, Löschregel als Option je Ziel mit drei Sicherungen).

    **Erledigt am 16.09.2026 in P5a/AP10 (Web 20.14.0).** Beide Wege, und in
    dieser Reihenfolge: **Anzeige zuerst** — „Nachsehen, was dort liegt" im
    Menü einer Zielzeile nennt Anzahl, Größe, ältesten und jüngsten Stand und
    **wie viele fremde Dateien** dort liegen; sie löscht nichts und
    beantwortet die Frage in vielen Fällen schon. **Löschregel als Option je
    Ziel**, ausdrücklich einzuschalten, nie Vorgabe, mit den drei Sicherungen
    aus E-P5a-03: Herkunft (Namensmuster **und** Versandprotokoll), Menge (nie
    unter N/M, nur eigene Dateien gezählt) und Lauf (nie nach einem
    gescheiterten Versand). Dazu eine Statuszeile für Ziele **ohne** Regel,
    auf die seit über einem Monat geschickt wird und von denen nie etwas
    entfernt wurde. Belegt in `tools/versandprobe/` Teil 12: fünf fremde
    Dateien, fünf eigene, N = 2 → **3 gelöscht, alle fünf fremden bleiben**.

195. **`geraet_art` kommt auf dem Rückweg der Sicherung ungeprüft durch.**
    *Aufgenommen 14.09.2026 als Nebenfund der Bestandsaufnahme zu R42.*
    Beim Koppeln verengt `geraete_lib.php` die Geräteart auf die drei
    erlaubten Werte — was nicht in `GERAET_ARTEN` steht, wird `NULL`, und
    `docs/Technik.md` führt das ausdrücklich als Zusage („eine Geräteart
    außerhalb der drei erlaubten Werte zu `NULL`"). Auf dem Rückweg der
    Konto-Sicherung gilt sie nicht: `backup_lib.php` prüft
    `missions.geraet_art` und `rest_segments.geraet_art` nur mit
    `pruef_text(…, GERAET_MAX_ART, …)`, also allein auf die Länge von 16
    Zeichen. Jede Zeichenkette bis dahin geht durch. Bei `origin` ist es
    anders — der wird gegen `HERKUNFT_WERTE` gehalten; die Asymmetrie ist im
    Code nicht begründet.

    Heute fällt das nirgends auf, weil die Statistik `devices` liest und
    nicht `missions`. Genau diese beiden Spalten sind aber das, was der
    offene R42-Rest auswerten soll („Herkunft je Einsatz", R64) — eine
    Zählung darüber würde eine eingespielte Sicherung ungefiltert
    übernehmen. Kein Sicherheitsproblem: Der Weg setzt voraus, dass jemand
    seine eigene Sicherung verändert. Aber eine Zählung, die man verunreinigen
    kann, taugt nicht als Betriebszahl.

    *Abnahme:* Ein Sicherungspaket mit `geraet_art: "radcomputer"` landet als
    `NULL` in der Datenbank, nicht als `radcomputer`. Zuordnung: **P5**,
    zusammen mit der Auswertung — vorher hat die Spalte keinen Leser.

    *Zuordnung 15.09.2026:* **Konzept P5a** (`docs/konzepte/Konzept-P5a-Kette-und-Fundament.md`), AP10 (`backup_lib.php` prüft `geraet_art` gegen `GERAETE_ARTEN` wie beim Koppeln).

    **Erledigt am 16.09.2026 in P5a/AP10 (Web 20.14.0).** `backup_lib.php`
    hält `geraet_art` jetzt über `edbak_geraet_art()` gegen `GERAET_ARTEN` —
    dieselbe Verengung wie beim Koppeln, an beiden Stellen (Einsatz und
    Ruhesegment). Und sie **meldet** es: Der Vorgang steht in der Prüfliste
    der Wiederherstellung (`geraet_art: keine bekannte Geräteart — als
    „unbekannt" übernommen`), statt still zu geschehen — ein stilles `NULL`
    sähe aus wie „stand nicht drin". `geraet_modell` bleibt Freitext; ein
    Katalog dafür wäre beim nächsten Modell veraltet.
    *Abnahme erfüllt:* `tools/wiederherstellungs-probe/` Teil 11, fünf neue
    Erwartungen — `geraet_art: "radcomputer"` landet als `NULL`, das Modell
    daneben bleibt stehen, `"HANDY"` kommt als `handy` durch (Gegenprobe),
    dasselbe am Ruhesegment, und beides steht im Prüfprotokoll.

206. **Der Messstand-Schritt der Auslieferungskette bricht bei JEDEM Tag-Lauf
    ab.** `.github/workflows/auslieferung.yml` ruft in Zeile 144
    `python3 tools/messstand/serverprobe.py --basis "$STAGING_URL"` — und
    `serverprobe.py` kennt **kein** `--basis`. Sein `argparse` führt `--konto`,
    `--ausgabe`, `--wartung-fahren` und `--optimieren`; ein unbekanntes
    Argument beendet das Skript mit Code 2. Der Schritt steht unter
    `if: startsWith(github.ref, 'refs/tags/web-v')` und läuft deshalb genau
    dann, wenn ausgeliefert wird — und bisher wurde nach P5a/AP1 kein Tag
    gesetzt, weshalb es niemandem aufgefallen ist.

    **Der Fehler sitzt tiefer als ein fehlendes Argument:** `serverprobe.py`
    misst gegen eine **lokale Datenbank** (PDO, `EXPLAIN`, `OPTIMIZE TABLE`),
    nicht gegen eine Adresse. Gegen Staging über HTTP zu messen ist etwas
    anderes als das, was das Skript tut — die Zeile ist also nicht falsch
    geschrieben, sie ist falsch gedacht. Entweder fällt der Schritt weg, oder
    der Messstand bekommt einen Weg, der über die Leitung geht.

    *Aufgenommen 16.09.2026 in P5a/AP7, gefunden bei der Durchsicht der Kette.
    **Zuständig ist P5a/AP9**, das den Messstand ohnehin anfasst
    (Verbindungsgrenze, drei Messungen aus Nr. 37). Bewusst nicht nebenbei
    geändert: Ein Paket über den Ratenschutz baut die Auslieferungskette nicht
    um.*

    **Erledigt am 16.09.2026 in P5a/AP9 (E-P5a-53): der Schritt ist
    ersatzlos gestrichen.** Von den beiden Möglichkeiten oben ist die erste
    die richtige, und zwar nicht aus Bequemlichkeit: Was `serverprobe.py`
    misst — Tabellengrößen, Speicherspitze von `edbak_build()`, den
    Waisen-Vollscan — ist über HTTP grundsätzlich nicht zu sehen. Ein „Weg
    über die Leitung" hätte einen Endpunkt gebraucht, der einer
    unangemeldeten Kette Innereien der Datenbank ausliefert; genau den soll es
    nicht geben. Der Messstand bleibt, was er ist: ein **manuelles**
    Regressionsmittel (R35), das lokal vor einer Auslieferung läuft. An der
    Stelle des Schrittes steht jetzt eine Zeile in der Laufzusammenfassung,
    die sagt, wo seine Zahlen stehen (`tools/messstand/ausgangsmessung.md`)
    und wo die Grenzen der Zielanlage stehen (Betrieb → Status → Plattform).

17. **`ingest.php` hat als einziger anmeldungsfreier Endpunkt keine
    Mengenbremse.** `RATE_GRENZEN` (`ratelimit_lib.php`) kennt keinen Topf
    `ingest`, und die Datei ruft weder `rate_erlaubt()` noch
    `rate_misserfolg()`. Die übrigen offenen Endpunkte haben ihn — `RATE_GRENZEN`
    führt **zehn** Töpfe (gemessen 15.09.2026: `login`, `salt`, `reset`,
    `pair`, `pair_start`, `pair_code`, `demo`, `demog`, `testmail`, seit
    Web 20.7.0 `csp`; am 13.09.2026 waren es neun, bei Aufnahme
    „die drei übrigen", genannt vier). Gefunden in P0/A6 (dort F-16); die
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

    *Zuordnung 15.09.2026:* **Konzept P5a** (`docs/konzepte/Konzept-P5a-Kette-und-Fundament.md`), AP7 (E-P5a-01: Grundsatzfrage entschieden — die Bremse kommt; E-P5a-02: 30 Fehlversuche je 15 min je Gerätekennung, Leiter 10/20/30/60 min, `429` mit `Retry-After`).

    **Erledigt am 16.09.2026 mit Web 20.11.0 (P5a/AP7).** Zwei Töpfe:
    `ingest` je Gerätekennung (bekannte Kennung, falscher Schlüssel) und
    `ingest_ip` je Adresse (unbekannte Kennung), **je 30 Fehlversuche pro
    15 Minuten**, danach die Sperrleiter aus 20.10.0 — erste Sprosse **15
    Minuten und nicht 10** (E-P5a-43/-49). Antwort `429` mit `Retry-After`.
    Gezählt werden ausschließlich Fehlversuche; `405`, `413`, `403
    device_disabled`, `400` und `500` zählen nicht. Dazu der Vermerk am
    Gerät (`devices.abgewiesen_seit`, `abgewiesen_anzahl`, Migration
    `2026_09_16_geraet_abgewiesen`) auf Kontoseite und Betrieb → Status.

    **Zwei Zahlen oben in diesem Eintrag waren falsch, und die Berichtigung
    gehört hierher, nicht in eine Fußnote.** „Spitze 14 Anfragen an einem
    Auslöser" stand seit P1 — `messprotokoll.json` führt unter
    `spitze_je_dienst` die **3**; die 14 ist `teilstuecke_je_paket.max`, also
    die Zahl der Teilstücke **eines Pakets**, nicht die Zahl der Anfragen an
    einem Zeitpunkt. Und „174 Abstände von 0 Sekunden" stammt aus einem
    älteren Protokollstand (16 Diensttage); der heutige nennt **199** bei 21
    Diensten und 612 Anfragen. **Die Schlussfolgerung bleibt**: Die 30 hängt
    an den 14 Teilstücken eines Schlüsselwechsel-Stoßes, nicht an der Spitze
    je Auslöser — die Begründung ist nur jetzt die richtige.

    **Und die Zahl der Töpfe steht nicht mehr da.** „`RATE_GRENZEN` führt
    **zehn** Töpfe" war am 15.09.2026 richtig und am 16.09.2026 falsch (es
    sind mit `login_ip`, `global`, `ingest` und `ingest_ip` vierzehn). Dieselbe
    Zahl stand in `schema.sql` und in `docs/Technik.md`; an allen drei Stellen
    ist sie jetzt durch den Verweis auf `RATE_GRENZEN` ersetzt. Eine Zahl im
    Fließtext altert genauso still wie eine Aufzählung.

8. **Content-Security-Policy als zusätzliche Verteidigungslinie.**
    *Ergänzung 06.09.2026 (Krypto-Review, R78):* Die Bestandsaufnahme
    macht sie enger möglich als hier angenommen — **null**
    Inline-Ereignisbehandler, **ein** `style`-Attribut, alle Skriptblöcke
    über `ui_seite_start()`. Der Bauplan (Nonce je Anfrage, Report-Only
    zuerst, Quellenliste für Kacheln und Photon) steht in
    `docs/konzepte/Vorbereitung-Sicherheitspaket.md`, SP-5. Warum es
    zählt: Daten- und Inhaltsschlüssel liegen als Hex im `sessionStorage`;
    jede XSS-Lücke, auch eine in Leaflet oder SheetJS, liest sie aus.
   Seit Web 5.2.0 eng fassbar: Es wird keine fremde Quelle mehr geladen
   (Nr. 12), die Regel muss also nichts von außen erlauben.

    **Erledigt am 15.09.2026 mit Web 20.7.0 (P5a/AP4).** `kopfzeilen_lib.php`
    baut die Richtlinie zur Laufzeit; `kopfzeilen_seite()` steht in
    `ui_seite_start()`, `kopfzeilen_json()` in `json_out()`. Die Vermutung von
    2026 hat sich gehalten: **null** Inline-Ereignisbehandler, **null**
    `javascript:`-Adressen — `script-src 'self' 'nonce-…'` ohne
    `'unsafe-inline'` war ohne Umbau erreichbar. Nicht gehalten hat sich „ein
    `style`-Attribut": es waren dreizehn. Drei sind gewichen, die zehn
    übrigen entstehen zur Laufzeit in JavaScript und bleiben unter
    `style-src-attr 'unsafe-inline'` (E-P5a-32, Begründung im Kopf von
    `kopfzeilen_lib.php`). Nachweis: `tools/cspprobe/`, 0 Befunde über 106
    Dateien und 108 Skript-Stellen.

181. **Die Anwendung schickt keine Content-Security-Policy.**
    *Aufgenommen 13.09.2026 als zweite Hälfte von Nr. 179; dort ausdrücklich
    nicht mitgemacht, weil es eine Festlegung ist und kein Nachtrag.* Seit dem
    13.09.2026 zählt `tools/vollstaendigkeit/` die Zusage „keine fremde Quelle
    zur Laufzeit" nach (Prüfung `fremde Quelle`, Nr. 179) — **am Quelltext**.
    Zur Laufzeit hält sie nichts: `grep -rn "Content-Security-Policy" server/`
    ergibt **0**. Ein eingeschleustes Skript, das nicht im Repositorium steht
    — über eine Lücke, ein Fremdpaket, einen kompromittierten Deploy —, lädt
    ungehindert.
    **Was zu entscheiden ist, nicht nur zu bauen.** Die Richtlinie braucht
    Ausnahmen für genau die Quellen, die Nr. 179 als gewollt aufführt: vier
    Kachelserver (`tile.openstreetmap.org`, `tile.openmaps.fr`,
    `{s}.tile.opentopomap.org`, `server.arcgisonline.com`) unter `img-src`,
    und den Adressdienst unter `connect-src` — **dessen Anschrift ist seit
    S9/AP2 eine Einstellung je Installation** (`app_state.geocoder_url`), die
    Richtlinie muss also zur Laufzeit gebaut werden und kann nicht als
    feste Zeichenkette im Code stehen. Dazu die Frage, ob `'unsafe-inline'`
    für `style-src` bleibt oder die Inline-Stile weichen (das entscheidet über
    den Aufwand), und ob `report-only` vorgeschaltet wird, um eine Woche zu
    messen, bevor die Richtlinie greift.
    **Wer sie zu eng setzt, macht die Karten grau** — und das fällt erst im
    Einsatz auf. *Abnahme:* Jede Seite schickt die Richtlinie; die vier
    Kachelserver und der eingestellte Adressdienst funktionieren; ein
    eingeschleustes `<script src="https://cdn.example/x.js">` wird vom Browser
    **blockiert** (Konsolenmeldung im Bilderlauf, der solche Fehler seit
    Nr. 176 wieder zählt); der Bilderlauf bleibt bei 0 Konsolenfehlern.
    Zuordnung: **S10 Sicherheit** (Schritt 9b) oder das Bedrohungsmodell
    (P6, R69) — die Entscheidung gehört in den Rahmenplan.

    **Erledigt am 15.09.2026 mit Web 20.7.0 (P5a/AP4)** — zusammen mit Nr. 8;
    dieser Punkt trug die offenen *Entscheidungen*, und die sind gefallen:

    - **Kachelserver und Adressdienst:** vier Domains unter `img-src`, der
      Adressdienst unter `connect-src` — **aus `geocoder_dienst()` zur
      Laufzeit gelesen**, nicht fest verdrahtet, und weggelassen, wenn die
      Adresssuche aus ist. Genau der Grund, aus dem der Punkt hier stand.
    - **`'unsafe-inline'` für `style-src`:** nein — aber
      `style-src-attr 'unsafe-inline'` (E-P5a-32). Die Trennung gibt es seit
      CSP 3; sie erlaubt Stil**attribute** und verbietet weiterhin
      eingeschleuste `<style>`-Blöcke und fremde Stylesheets.
    - **Report-Only vorgeschaltet:** ja, und nicht „eine Woche", sondern bis
      eine BetreiberIn den Schalter umlegt. Die Meldungen sammelt
      `api/csp_bericht.php`; Betrieb → Servereinstellungen zeigt sie.

197. **Fähigkeiten lassen sich an einem bodengebundenen Bergwacht-Rettungsmittel
    nicht hinterlegen.** *Aufgenommen und erledigt 14.09.2026 (Demo-Ausbau,
    AP0, Web 20.3.0).* E29 erlaubte Winde und Bergwacht ausschließlich an
    luftgebundenen Rettungsmitteln. Ein **Bergwachtnotarzt** fährt aber zum
    Einsatz und wird von dort geflogen: Er braucht die Winde, und seine
    Betriebsart ist Boden. `pruef_rettungsmittel()` verwarf die Häkchen still,
    und der zugehörige Diensttag bekam im Einsatzformular keine Windenfelder
    (`cap_gate` über `day_capabilities`).

    **Erledigt mit einer Spalte statt einer Bedingung.** `VEHICLE_TYPEN` führt
    seither `faehigkeiten` (`'luft'` | `'immer'`), `veh_caps_erlaubt()` wertet
    sie aus, und Prüfschicht, Stammdatendialog und Rückspielweg der Sicherung
    fragen dieselbe Funktion. Nebenbei geschlossen: Das Dialogskript führte
    eine dritte, engere Fassung der Regel (Typ Bergwacht oder Sonstiges mit
    Betriebsart Luft durfte Fähigkeiten führen, sah die Häkchen aber nie), und
    die Karte *Bergwacht-Bereitschaften* erschien nur an einem Standort mit
    luftgebundenem Rettungsmittel.

    **Was offen blieb, steht als Nr. 198:** Die Zeitraumübersicht zählt
    Windendienste weiter nur luftgebunden. Einzelheiten und Begründung im
    Changelog zu Web 20.3.0.

189. **`server/schema.sql` nennt einen Pfad, den es seit dem 02.09.2026 nicht
    mehr gibt.**
    *Aufgenommen 14.09.2026 (S10-Nachlauf, Fund der Vollständigkeitskritik.)*
    Zeile 528 verweist auf `docs/Konzept-S2`; die Datei liegt seit der
    Neuordnung vom 02.09.2026 (`781e624`) unter
    `docs/konzepte/erledigt/Konzept-S2-Mengen-Spuren-Sicherung.md`.

    **Dass es ein Versehen ist und keine Protokollzeile, belegt der Zwilling:**
    `server/migration_lib.php:1770` erklärt **dieselbe** Sache mit **demselben**
    Beleg F-S2-G und ist auf das neue Verzeichnis gezogen worden. Von zwei
    gleichlautenden Kommentaren wurde einer nachgeführt. Beide kürzen den
    Dateinamen auf `Konzept-S2` ab — wer hier anfasst, schreibt ihn an beiden
    Stellen aus, sonst bleibt die Hälfte des Fundes stehen.

    **Warum es nicht im Nachlauf mitging:** Die Datei liegt unter `server/`.
    Eine Änderung dort verlangt eine Versionsstufe (`CLAUDE.md` 2) und löst
    beim Merge einen Deploy aus (`CLAUDE.md` 3) — für einen Kommentar, der
    weder Verhalten noch Schema berührt, ist das der falsche Preis. Gehört in
    das nächste Paket, das `server/` ohnehin anfasst.

    **Ein zweiter Kommentarfehler in derselben Datei** *(nachgetragen
    14.09.2026 aus der Bestandsaufnahme zu R42)*: An `geraet_modell` steht
    „Sammelnamen werden lang, der laengste hat **156** Zeichen". Es sind
    **153** — nachgemessen an `GERAETE_MODELLE`, längster Name
    „fēnix 6X Pro / 6X Sapphire / …". **Und es ist kein Zahlendreher:** Die
    156 war am 02.09.2026 richtig und ist mit **Web 12.9.2** überholt worden,
    als `erzeugen.py` die Marken- und Schutzrechtszeichen aus den Namen nahm
    — der Changelog-Eintrag sagt es wörtlich („schrumpft von 156 auf 153
    Zeichen"). `migration_lib.php` und `docs/Technik.md` sind mitgezogen,
    `schema.sql` nicht. **Zwei weitere lebende Stellen führen die 156**:
    Rahmenplan Abschnitt 3, Schritt 2 (E-S6-7) und
    `docs/konzepte/Konzept-R64-Herkunft-Geraet.md`. **Drei Protokollstellen
    bleiben** — die Fassung-19-Zeile des Rahmenplans und die **beiden**
    Changelog-Einträge zu Web 12.9.1 („der längste Eintrag hat 156 Zeichen")
    und zu Web 12.9.2 („schrumpft von 156 auf 153 Zeichen"); sie beschreiben,
    was damals galt. *(Hier stand „zwei", benannt waren aber schon damals
    nur zwei von drei — nachgezählt am 15.09.2026 beim Zusammenführen.)*

    *Abnahme:* Beide Kommentare nennen denselben, vollständigen Pfad, und die
    Datei dort existiert; die drei lebenden Stellen nennen **153**, die drei
    Protokollzeilen bleiben unberührt.

    *Erledigt 14./15.09.2026 (Demo-Ausbau, AP0 und Nachlauf, Web 20.3.0)* —
    das war das nächste Paket unter `server/`, und es hat beide Funde
    mitgenommen. **Erster Fund:** `schema.sql` und `migration_lib.php` nennen
    `docs/konzepte/erledigt/Konzept-S2-Mengen-Spuren-Sicherung.md`
    ausgeschrieben; die Datei liegt dort. **Zweiter Fund** (aus der
    Bestandsaufnahme zu R42, beim Zusammenführen der beiden Zweige
    übernommen): Die drei lebenden Stellen nennen jetzt **153** —
    `server/schema.sql`, Rahmenplan Abschnitt 3 (Schritt 2, E-S6-7) und
    `docs/konzepte/Konzept-R64-Herkunft-Geraet.md`. Eigens nachgemessen an
    `GERAETE_MODELLE`, nicht abgeschrieben: **153 Zeichen** (154 Bytes), der
    Eintrag „fēnix 6X Pro / 6X Sapphire / … / quatix 6X Dual Power"; von 173
    Modellen liegen **fünf** über 64 Zeichen. Die Fassung-19-Zeile des
    Rahmenplans und die beiden Changelog-Einträge zu Web 12.9.1/12.9.2
    bleiben unberührt — sie beschreiben, was damals galt.

139. **Adminpakete sind unversiegelt und gehen über FTP hinaus.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-4).* Die Teile des
    Admin-Backups sind blankes JSON im ZIP (`adminbackup_lib.php:404,624`)
    mit allen Klartextfeldern, E-Mail, Name und `pat_wrap_rc`; der Versand
    lässt reines `ftp` zu (`backup_targets.protokoll` in `schema.sql`) und
    prüft bei FTPS kein
    Zertifikat (`sicherungsziel_lib.php:31-33`). Die Begründung in
    `Backup-Format.md` 5 („kein Schlüssel, ohne ihn zu speichern") ist seit
    dem Serverschlüssel (Web 12.1.0) überholt. Versiegeln mit
    `sk_versiegeln()` wie das Komplettbackup, `ftp` aus der Auswahl,
    bestehende `ftp`-Ziele mit rotem Hinweis. Zuordnung: **S10** (R78).

    *Konzept S10 (13.09.2026) — nach der Freigabe gelöscht (R62/K9), zuletzt
    unter Commit `a00f6b5`; E-S10-13 und E-S10-14; Umsetzung in **AP4**.*
    Entschieden ist dabei mehr, als der Punkt verlangte, und
    zweierlei anders: **Auch `manifest.json` wird versiegelt**, nicht nur die
    Teile — der Zweck bindet jeden Teil an Konto **und** Teilnamen
    (`adminpaket|<konto>|<teil>`), sodass ein Umhängen an der Prüfsumme
    scheitert; die Fassung erkennt der Leser am **Inhalt** (`sk_versiegelt()`),
    nicht an der Dateiendung. Und **FTPS bleibt** (F-S10-5) statt mitzugehen,
    mit dem Zusatz „prüft das Zertifikat der Gegenstelle nicht — SFTP
    empfohlen" an Formular, Handbuch und Runbook; nur `ftp` fällt.
    **Umzusiegeln ist nichts** (F-S10-4): Auf der Installation gibt es keine
    Altpakete, also kein Job und kein Zähler — der Lesezweig für unversiegelte
    Fassung-2-Teile bleibt als Toleranz und geht mit **Nr. 46**.

    **Erledigt mit Web 20.2.0 (S10/AP4, 14.09.2026).** Umgesetzt wie
    entschieden, mit **drei** Abweichungen, die beim Bauen entstanden sind:

    - **Der Siegelzweck bindet auch den PAKETNAMEN**
      (`adminpaket|<konto>|<paket>|<teil>`, E-S10-U-11). Der Einwand des
      Konzepts — der Stempel müsse beim Lesen bekannt sein, komme also aus dem
      versiegelten Manifest — trägt nicht: Alle vier Leser bekommen den
      Dateinamen als Parameter, bevor sie irgendetwas öffnen. Ohne ihn liesse
      sich ein Teil aus einem älteren Paket **desselben Kontos** unterschieben.
      Der Preis steht in `Backup-Format.md` 5: Wer ein Paket umbenennt, macht
      es unlesbar.
    - **`konto.json` wird mitversiegelt** (E-S10-U-14). Die Begleitdatei neben
      dem Paket trug E-Mail und Namen im Klartext — die Zusage „kein lesbarer
      Name, keine E-Mail" hätte sonst nur für das ZIP gegolten und nicht für
      den Ordner, in dem es liegt.
    - **gzip vor dem Siegel** (E-S10-U-12), gemessen am Referenzkonto:
      Fassung 2 **33 281** Byte, Siegel ohne Vorstufe **201 390** (+505 %),
      gzip davor **45 290** (+36 %). Die 36 Prozent sind der base64-Rahmen
      von `edsk1:`, nicht der Packlauf.

    *Und einer, den der Punkt selbst nicht sah:* `sz_pruefen_eingabe()` prüfte
    gegen `SZ_PORTS`, nicht gegen `SZ_PROTOKOLLE` — `ftp` nur aus dem
    Anzeigekatalog zu streichen hätte gar nichts abgeschafft. Dazu fiel
    `sz_weg()` für jedes **unbekannte oder leere** Protokoll still auf
    Klartext-FTP zurück; geprüft wird jetzt positiv gegen den Katalog.

    Die Toleranz für unversiegelte Fassung-2-Teile bleibt und geht mit
    **Nr. 46** (und **Nr. 187**).

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
    (`CLAUDE.md` 5); betroffen ist jede Seite mit `ui_aktionen()`.
    Zuordnung: Backlog-Runde oder P7 (Gesicht v1.0).
    **Entschieden am 12.09.2026: Weg (b).** Das Blatt bleibt unten; das „⋯"
    bleibt hervorgehoben, solange das Blatt offen ist, und das Blatt fährt
    sichtbar aus seiner Richtung auf. Geht in die **Mockup-Runde** (mit
    Nr. 41, 42 und 45) — als einziger der vier ein Punkt aus einer
    Rückmeldung von außen; gerät die Runde ins Rutschen, darf er sie
    verlassen.
    *Nachgezählt 13.09.2026:* `ui_aktionen()` hat **sechs** Aufrufe auf fünf
    Seiten (`index.php`, `einsatz.php`, `admin_user.php` zweimal,
    `admin_demo.php`, `admin_sicherungen.php`) — hier standen „zehn".
    Dasselbe Blatt baut auch `ui_zeilenaktionen()` (neun Aufrufe:
    Geräteliste, Stammdaten, Papierkorb, Sicherungsziele, Komplettsicherung,
    Einstellungen); beide öffnen über `data-blatt` in `assets/blatt.js`. Die
    Änderung sitzt damit im Baustein und erreicht alle fünfzehn Öffner; ob
    Weg (b) für die Zeilenblätter gleich mitgilt, klärt das Mockup.
    **Erledigt am 14.09.2026 mit Web 19.6.0** (Mockup-Runde 9c, AP4).
    Weg (b), wie entschieden: Das Blatt steht im Ruhezustand um seine eigene
    Höhe unter dem Bildrand und wird beim Öffnen in `--dauer` heraufgeholt
    (`transform:translateY(100%)` → `.blatt-auf`); der Öffner trägt, solange
    sein Blatt offen ist, `--orange-hell` mit `--orange-tief` (Fassung D4,
    F-MR-11). **Am Schreibtisch fährt nichts** — dort ist dasselbe Markup ein
    Aufklappmenü unter dem Knopf, und eine Fahrt „um die eigene Höhe nach
    unten" schöbe es neben die Sache; die Markierung gilt dort trotzdem.
    **Die Markierung hängt am Attribut** `[data-blatt][aria-expanded="true"]`
    und erreicht damit nicht nur die fünfzehn Öffner der beiden Bausteine,
    sondern auch den Pin-Knopf des Ortsfelds und die drei handgeschriebenen
    Sortierblatt-Knöpfe — **vier Bauarten**, nicht zwei; die Zahl „fünfzehn"
    oben ist damit berichtigt (E-MR-25).
    **`--dauer` steht seither auf .24s statt .18s**, für die ganze Anwendung
    (E-MR-22, F-MR-12) — bei 180 ms war die Auffahrt eher ein Aufblitzen.
    *Gemessen:* Klickprobe drei neue Wege, 5 von 5 Öffnern markiert und sauber
    zurückgestellt, Fahrt 240 ms und mitten in der Bewegung nachgewiesen (389
    px unter der Ruhelage), am Schreibtisch 0 ms und 60 ms nach `Escape`
    bereits aus dem Fluss; Stilvergleich 46 202 Elementmessungen, 390
    Abweichungen, sämtlich Fahrtdauer, `.blatt` und der markierte Öffner —
    in allen drei Motoren dieselbe Zahl.

185. **Ein Auswahlfeld schob die Importseite in WebKit um 6 px zur Seite.**
    *Gefunden und behoben am 14.09.2026 in AP3b der Mockup-Runde, beim ersten
    dreifachen Bilderlauf.* `import.php` bei 360 px, angemeldet als Demo-Konto:
    `scrollWidth` 366 gegen `innerWidth` 360 — **nur in WebKit**, Chromium und
    Firefox meldeten 360. Der Bericht nannte keinen Verursacher, und das war
    richtig: **Kein Element** der Seite ragte über 360 px hinaus; das
    Auswahlfeld ist 302 px breit und endet bei 331.
    **Was überlief, war sein Inhalt.** WebKit rechnet den längsten EINTRAG
    eines `<select>` in den Überlauf des Kastens mit, auch wenn der Kasten ihn
    abschneidet. Nachgewiesen durch Kürzen: alle Eintragstexte auf „x" gesetzt
    → 360; zurückgesetzt → wieder 366. Der längste Eintrag hat 53 Zeichen, die
    drei anderen Auswahlfelder derselben Seite 17, 17 und 30 — sie laufen nicht
    über. Ab 390 px verschwindet es.
    **Behoben mit `select.feld-eingabe{contain:paint}`.** `overflow:clip` am
    Feld half nicht (gemessen: 366), `max-width:100%` ebenso wenig,
    `appearance:none` nur zur Hälfte (361). Die Kosten sind nachgemessen, nicht
    geschätzt: der fokussierte Ausschnitt (318 × 60 px) vor und nach der Regel
    ist in Firefox bitgleich, in Chromium und WebKit **ein** Pixel verschieden
    bei einer Abweichung von 6 von 255 — die Rundung des Fokusrings. Der
    Fokusring bleibt stehen; Malbegrenzung schneidet Inhalt, nicht Umriss.
    Web 19.5.1.

186. **Die Klickprobe maß Drehungen mit einem Mittel, das in WebKit nichts sagt.**
    *Gefunden und behoben am 14.09.2026 in AP3b der Mockup-Runde.* Der Weg
    `ap3-pfeile-drehen` las die Drehung der Richtungspfeile aus
    `getScreenCTM()` des inneren `<svg>`. WebKit rechnet die
    CSS-Transformation eines HTML-Vorfahren dort **nicht** hinein und lieferte
    für jeden Winkel 0°; die Probe meldete „1 von 12" und sah aus wie ein
    Anwendungsfehler.
    **Die Pfeile drehen sich.** Gemessen, fünf Winkel, drei Motoren: die
    berechnete Matrix stimmt überall, und der Umriss des Symbols wächst bei
    30° in allen dreien von 16 auf 22 px.
    **Behoben durch zwei motorunabhängige Messungen** statt einer
    motorabhängigen: die berechnete Matrix des drehenden Elements (die Drehung
    gilt) und das Wachsen des Umrisses (sie wird gezeichnet). Gezählt werden
    dabei nur die **echt schrägen** Winkel — ein um 90° gedrehtes Quadrat ist
    genauso breit wie ein ungedrehtes; der erste Entwurf zählte sie mit und
    meldete in allen drei Motoren „8 von 10", was keine Abweichung war,
    sondern Geometrie.

183. **Der Prüfstand kennt nur eine Engine.**
    *Aufgenommen 14.09.2026 bei der Gegenprobe zu Nr. 182.* Jede Browserprüfung
    des Projekts — Bilderlauf, Klickprobe, Stilvergleich, Kopplungsprobe — läuft
    über Playwright, und Playwright hat in dieser Arbeitsumgebung **nur
    Chromium**. Solange die Anwendung sich auf Breitentricks beschränkte, war
    das tragbar; seit Web 19.4.1 hängt eine Darstellung an einer
    Container-Abfrage (`container-type:inline-size`, `100cqi`), und seit P3
    ohnehin an `:has()` (sechsmal im Stylesheet) und `dvh`. Eine Engine, die
    eines davon nicht kann, fällt heute **lautlos** durch jede Prüfung.
    **Zwei von drei sind inzwischen belegt**, und der Weg dahin steht hier,
    weil er wiederverwendbar ist: WebKitGTK 2.52.6 kommt aus den Paketquellen
    (`xvfb`, `python3-gi`, `gir1.2-webkit2-4.1`) und lässt sich über
    `WebKit2.WebView.evaluate_javascript` genauso ausmessen wie Chromium —
    gegen einen Schnappschuss der echten Seite, damit Markup und Stylesheet
    dieselben sind. Das Messskript der Sitzung liegt nicht im Repositorium;
    wer den Punkt baut, macht ein Prüfmittel daraus.
    **Was fehlt, ist Gecko.** Playwrights Browser-Downloads sind gesperrt —
    `cdn.playwright.dev` und `playwright.download.prss.microsoft.com` antworten
    beide mit **403 „request blocked: no rule or allowlist entry allows host"**.
    Ubuntus `firefox` ist nur eine Snap-Hülle und läuft im Container nicht.
    **Die billigste Lösung ist keine Codearbeit**, sondern ein Eintrag in der
    Egress-Allowlist der Arbeitsumgebung für diese beiden Hosts; danach liefert
    `playwright install firefox webkit` beide Engines, und dieselben Skripte
    laufen dreifach.
    *Abnahme:* `tools/screenshots/` und `tools/vollstaendigkeit/` bleiben
    unberührt; ein neues oder erweitertes Mittel fährt mindestens die
    Importvorschau und eine Kartenseite in **drei** Engines und meldet je
    Engine dieselben Zahlen (Überlauf, Konsolenfehler, Knopfhöhen). Zuordnung:
    **Zuarbeit** (Allowlist) plus eine spätere Backlog-Runde für das Mittel.
    **Die erste Hälfte ist am 14.09.2026 erledigt.** Der Auftraggeber hat die
    beiden Adressen freigegeben; `.claude/hooks/session-start.sh` beschafft
    seither **Firefox und WebKit** samt sechs Systembibliotheken und meldet die
    drei Engines **einzeln** („3 Browser da" sagt nicht, welcher fehlt).
    Gemessen: Chromium 141.0.7390.37, Firefox 142.0.1, WebKit 26.0 — alle drei
    starten und laden eine Seite. **Nr. 182 und Nr. 42 sind seither in allen
    dreien gemessen** und stimmen überein (Abweichungen von 1–2 px in der
    Zeilenhöhe, Schriftmetrik).
    **Drei Fallstricke sind dabei gefunden und in `tools/screenshots/LIESMICH.md`
    festgehalten**, damit der Nächste sie nicht sucht: (1) `waitUntil:'load'`
    hängt in Firefox, solange die Kartenkacheln nicht erreichbar sind —
    Chromium nicht; (2) Firefox meldet abgebrochene `latin-ext`-Schriftabrufe
    als Konsolenfehler (`NS_BINDING_ABORTED`), obwohl alle zehn Dateien
    vorhanden sind und bei ruhiger Seite laden — ein Rauschfilter für drei
    Engines muss das kennen, sonst ist „0 Konsolenfehler" nicht mehr zu
    halten; (3) Maße weichen um wenige Pixel ab, ein Vergleich über Engines
    braucht eine Toleranz.
    **Die zweite Hälfte ist am 14.09.2026 in AP3b der Mockup-Runde erledigt.**
    `tools/motor.mjs` hält Motorwahl und Firefox-Voreinstellung an **einer**
    Stelle; Bilderlauf, Klickprobe und Stilvergleich nehmen sie von dort und
    kennen `--motor chromium|firefox|webkit`. Wie oft welches Mittel dreifach
    fährt, steht in `docs/Technik.md` (Prüfstand) und ist nicht für alle
    gleich: Stilvergleich **immer** (14–18 s je Motor), Bilderlauf
    **gestaffelt** (Chromium voll, die beiden anderen `--nur` plus `--risiko`;
    voll wären es 9 Minuten je Motor), Klickprobe **nach Bedarf** (3,5 min je
    Motor, und nur mit frisch eingespieltem Bestand dazwischen).
    **Zwei Dinge mussten dafür gemessen und behoben werden**, und beide hätten
    sonst eine grüne Zahl erzeugt, die nichts wert ist: Headless Firefox
    meldet ohne Voreinstellung `hover:none` und `pointer:none` und misst damit
    den ganzen Media-Block der 36-px-Bedienhöhe **nicht** (`ui.*PointerCapabilities`
    auf 6 = fein + Hover); und `newCDPSession` gibt es nur in Chromium — der
    Aufruf steht in Bilderlauf und Klickprobe und warf in den anderen beiden
    sofort. Gebraucht wird er ohnehin nur dort: Gemessen behalten Firefox und
    WebKit die Eingabeart über den Vollseiten-Screenshot hinweg, nur Chromium
    verliert sie.
    **Der Lauf hat am ersten Tag zwei Befunde geliefert** — Nr. 185
    (WebKit-Überlauf auf `import.php`) und Nr. 186 (die Klickprobe maß
    Drehungen mit einem Mittel, das in WebKit nichts sagt). Beide sind
    erledigt; der Punkt hier ist damit ganz abgeschlossen.

45. **Dritte Kartengröße zwischen klein und Vollbild.**
    *Aufgenommen 30.08.2026, zurückgestellt.* Die Karte des Diensttags ist im
    Regelfall klein und im Vollbild oft zu groß. Vorschlag aus der Durchsicht:
    eine mittlere Fassung über die volle Breite des Diensttags, über der
    Liste. Kein Mockup, keine Freigabe — bewusst nicht in dieser Runde.
    **Entschieden am 12.09.2026: bauen, Mockup zuerst.** Geht in die
    **Mockup-Runde** (mit Nr. 41, 42 und 124) und verlässt damit die Gruppe
    „nach v1.0" im Rahmenplan.

    **Erledigt mit Web 19.5.0** (Mockup-Runde 9c, AP3, 14.09.2026). Ein Knopf
    unter dem für das Vollbild, **nur auf der Tagesübersicht**. Bis 1599 px
    wird die Karte höher (`--karte-gross` = `min(60vh, 520px)`), ab 1600 px —
    wo sie ohnehin hoch in einer eigenen Spalte steht — stattdessen **breit**:
    Das Raster fällt auf eine Spalte, die Karte rückt zwischen Diensttag-Daten
    und Liste. Dieselbe Klasse `.geo-gross` trägt beides; welche Wirkung sie
    hat, entscheidet das Stylesheet, damit die Schwelle 1600 an einer Stelle
    bleibt. Aus demselben Grund trägt der Knopf beide Zeichen und das
    Stylesheet blendet je Breite eines aus (E-MR-19). Die Wahl bleibt erhalten
    (`localStorage`, je Browser und Gerät — der erste der Anwendung).
    *Gemessen in drei Engines* (Chromium 141, Firefox 142, WebKit 26), fünf
    Breiten je Engine, fünfzehn Messungen mit demselben Bild: klein
    160/220/300 px bzw. 820–864 px in der Spalte, groß **520 px** in jeder;
    ab 1600 px wechselt „groß" vom Raster in den Fluss und nimmt die volle
    Inhaltsbreite (1308/1388 px); Symbol wechselt von senkrecht auf quer;
    `aria-pressed` folgt; Liste steht in allen fünfzehn unter der Karte;
    **0** waagerechter Überlauf, **keine** Konsolenfehler; nach dem Neuladen
    wieder 520 px. Kacheln nach dem Umschalten: 10 → **15**, davon 5 am
    Unterrand, **0 px unbedeckt** (schon nach 200 ms). Voller Bilderlauf
    **360 Bilder, 0/0/0**.
    Neu: Token `--karte-gross`, Klasse `.geo-gross`, Symbole
    `karte-gross.svg` und `karte-breit.svg`. Kein neuer Farbwert.

42. **Drei Unicode-Zeichen stehen noch als Symbol im Markup.**
    *Aufgenommen in P3/O12, Zahl fortgeschrieben in S2/AP3, AP4, AP5, AP5b
    und AP6, zuletzt am 13.09.2026.* P-P3-03 verlangt null. Die Prüfung
    meldet **255** Treffer (gemessen 13.09.2026; 195 bei der vorigen
    Fortschreibung, 158 bei Aufnahme); alle bis auf drei sind Kommentare oder
    richtige Typografie (die
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
    > S2/AP6 mit den Fortschrittsmeldungen des Freigabewegs auf 195 und die
    > Pakete bis Web 19.3.0 auf 255 — jedes Mal gemessen gegen den Stand
    > davor, nicht geschätzt. Neu seit der letzten Zählung sind außerdem
    > **8 Emoji**, alle im Kommentar von `assets/pwquality.js`, der erklärt,
    > warum `schriftzeichen()` in Grapheme zerlegt: dieselbe Sorte Rauschen.
    > Wer die Zahl als
    > Fortschrittsmaß liest, liest sie falsch — gemeint sind die drei unten.
    > Das Prüfmittel trennt beides nicht, und das gehört hierhin und nicht in
    > eine Fußnote.


    - `wegKnopf()` in `server/einsatz_form.php` — `'✕'` als **Rückfall**, wenn
      `edSymbol()` beim synchronen Aufbau noch nicht geladen ist. Mit
      Begründung im Code; das ist kein Fehler, sondern ein Netz, und es
      gehört eher dokumentiert als entfernt.
    - `zeichne()` in `server/assets/ortsfeld.js` — `x.textContent = '×'` am
      Koordinaten-Chip. Der Knopf `.rmx` ist textgroß gebaut und hat keine
      Symbolregel; ein SVG hineinzusetzen heißt, ihn neu zu bemaßen.
    - `ZEICHEN_UNLESBAR` in `server/assets/patient.js` — `⚠` für einen nicht entschlüsselbaren
      Datensatz. Das Zeichen steht nicht nur in einer Zelle, sondern **im
      Satz** („… ist mit ⚠ gekennzeichnet"). Ein SVG im Fließtext ist eine
      Gestaltungsfrage, keine Ersetzung.

    Die beiden letzten sind also kein mechanischer Tausch. Solange sie
    stehen, ist der Sollwert von P-P3-03 nicht erreicht — und das steht so im
    Prüfprotokoll, statt die Zahl schönzurechnen.

    **Entschieden am 12.09.2026: beide ersetzen.** Das `×` am
    Koordinaten-Chip — der Knopf `.rmx` ist textgroß gebaut und wird neu
    bemaßt — und das `⚠` im Fließtext, das Grundlinie und eine Größe relativ
    zur Schrift braucht (der fummeligere der beiden Fälle). Beide gehen in
    die **Mockup-Runde** (mit Nr. 41, 45 und 124). Der `✕`-Rückfall in
    `wegKnopf()` **bleibt** und wird als begründete Ausnahme dokumentiert.

    **Erledigt mit Web 19.4.2** (Mockup-Runde 9c, AP2, 14.09.2026). Der
    Entfernen-Knopf im Chip trägt `schliessen` in 12 px, zentriert in einem
    28-px-Ziel (M-MR-02 Variante C, F-MR-6b), 6 px zum Text und 6 px zum
    Chiprand (E-MR-18); das Ziel liegt als Pseudoelement über dem Symbol,
    damit der Chip seine Höhe behält. **Beide** Chip-Erzeuger sind umgestellt
    — `ortsfeld.js` und `einsatz_form.php`, wo dasselbe Zeichen als
    JavaScript-Escape stand (Fehlerfund 1 des Konzepts). Das Warnzeichen im
    Satz von `patient.js` ist dasselbe Symbol wie die Marke in der Tabelle,
    über die neue Klasse `.symbol-text` so groß wie die Schrift und auf der
    Grundlinie.
    **Der `✕`-Rückfall in `wegKnopf()` ist NICHT zur Ausnahme geworden,
    sondern entfallen.** Sein Vermerk („symbol.js lädt erst am Seitenende")
    war falsch — `symbol.js` kommt aus `ui_geruest_ende()` und damit als
    erstes Skript der Seite. Nachgemessen am laufenden Formular: `typeof
    edSymbol` ist `function`, alle acht Entfernen-Knöpfe tragen ein SVG,
    keiner das Zeichen. Der Zweig war seit seiner Entstehung tot. **Nr. 42
    braucht damit überhaupt keine Ausnahme.**
    *Gemessen:* Unicode-Zeichen **255 → 252** (die vier echten Treffer
    namentlich weg; die dreizehn verbliebenen nicht-typografischen stehen in
    Kommentaren oder im Satz), Befunde insgesamt **326 → 323**. Im Browser in
    **drei Engines** (Chromium 141, Firefox 142, WebKit 26): Chip 3/3 mit SVG,
    0 mit Zeichen, Symbol 12 × 12 px, Ziel 28 × 28 px rund, Abstände 6/6 px,
    Chiphöhe 28–28,2 px; Entfernen-Knöpfe 8/8 mit SVG; Meldung mit
    `symbol symbol-text`, 15 × 15 px bei 15 px Schrift, Farbe `--orange-tief`.
    Das Treffziel ist von **17 × 15 px auf 28 × 28 px** gewachsen.

182. **Die Kopfzeile einer Tagesgruppe steht außerhalb des Sichtfensters.**
    *Aufgenommen 13.09.2026 als Fehlerfund 2 der Mockup-Runde 9c (AP1); mit
    Web 19.4.0 ausdrücklich NICHT behoben, weil eine Lösung eine neue
    Darstellung wäre und damit nach `Design.md` 1.2 eine Freigabe braucht.*
    Die Vorschau des CSV-Imports gruppiert ihre Zeilen nach Diensttagen. Der
    Gruppenkopf ist ein `<tr><td colspan>` **in derselben Tabelle** — damit
    ist er so breit wie die Tabelle, nicht wie das Sichtfenster. Gemessen am
    13.09.2026 mit einer Datei aus dem Referenz-Export: Zelle **2677 px**,
    sichtbar **342 px** (bei 400 px Fenster), **654** (720), **954** (1280),
    **1354** (1920). Die Plakette „abweichende Crew" beginnt bei x = 940 bis
    1324 und ist in **keiner** dieser vier Breiten sichtbar, ohne waagerecht
    zu scrollen. Der `flex-wrap` der Kopfzeile greift nie — sie bleibt
    einzeilig (40/44 px), weil in einer 2677 px breiten Zelle nichts umbricht.
    **Kein Rückschritt, aber jetzt ein sichtbarer Widerspruch.** Vor
    Web 19.4.0 stand dort Fließtext an derselben Stelle (gemessen x = 1077 bei
    400 px, x = 1341 bei 1280 px) — ebenso unsichtbar. Neu ist, dass die
    Aussage jetzt eine **Plakette** trägt: die Form, mit der die Anwendung
    „Zustand, der Aufmerksamkeit will" zeigt. Eine Plakette, die man nur
    findet, wenn man sie sucht, ist die falsche Form für ihre Aussage.
    **Drei Wege, alle mit Folgen:**
    **(a) so lassen** — kostet nichts, lässt aber die Warnung dort, wo sie
    niemand sieht; die Zusage des Bausteins gilt dann nur in schmalen
    Tabellen, und das steht seit Web 19.4.0 in `Design.md` 9.34.
    **(b) Kopfzeile am linken Rand festheften** — `position:sticky; left:0`,
    Breite an das Sichtfenster gebunden. Datum, Besatzung und Plakette stehen
    dann immer da, wo gelesen wird; die Datenzeilen scrollen darunter durch.
    **CSS allein reicht nicht:** Die Zelle ist 2677 px breit, und die Breite
    des Sichtfensters kennt das Stylesheet nicht — es braucht eine gemessene
    Zahl (ein Custom Property, das ein Skript bei jeder Größenänderung setzt).
    Erst damit greift auch der Umbruch in zwei Zeilen, den das Konzept am
    Handy erwartet hat.
    **(c) je Tagesgruppe eine eigene Tabelle** mit der Überschrift darüber
    statt darin. Löst es an der Wurzel, verändert aber den Aufbau der Vorschau
    erheblich — und die Spalten der Gruppen fluchten dann nicht mehr
    zwangsläufig untereinander, was der Grund für die eine gemeinsame Tabelle
    war.
    *Abnahme (für b oder c):* Bei 400 px sind Datum, Besatzungszeile und
    Plakette ohne waagerechtes Scrollen sichtbar; der waagerechte Überlauf der
    **Seite** bleibt 0; die Spalten der Datenzeilen fluchten weiterhin über
    alle Gruppen hinweg. Zuordnung: **Mockup-Runde 9c** (wenn die Freigabe
    dort noch fällt) oder eine spätere Runde.
    **Mockup M-MR-05 liegt seit dem 14.09.2026 vor** (`konzept-mockup-runde/
    mockups/`, Freigabefrage F-MR-14). Alle drei Wege sind **in die laufende
    Anwendung eingesetzt und darin gemessen** — nicht gezeichnet; die Bilder
    sind Bildschirmfotos. Ergebnis: **(a)** Kopf bei 400 px nicht sichtbar,
    Zeile 44 px · **(b)** sichtbar, Zeile 231 px bei 400 und 122 px bei
    1280 px, eine Tabelle, Spalten fluchten · **(c)** sichtbar, 233 / 124 px,
    drei Tabellen, **Spalten fluchten nicht** (eine von vierzehn weicht schon
    bei drei Gruppen ab). Waagerechter Überlauf der Seite in allen sechs
    Messungen **0**, Konsolenfehler **keine**.
    **Zwei Kosten von (c) wurden erst am Bestand sichtbar** und standen oben
    noch nicht: die fehlende Spaltenflucht ist messbar, und der
    **Spaltenkopf wiederholt sich je Gruppe** — lässt man ihn weg, hat jede
    Gruppe außer der ersten keine Spaltenbeschriftung. **Empfehlung (b).**
    **Erledigt mit Web 19.4.1** (14.09.2026). **Weg (b)** — freigegeben mit
    F-MR-14, nachdem (c) zuerst gewählt und nach der Kartierung verworfen
    worden war. Und (b) braucht **kein JavaScript**: Der Rollbereich trägt
    `container-type: inline-size` über eine eigene Klasse `.imp-roll`, die
    Kopfzeile nimmt mit `width:100cqi` die **sichtbare** Breite an und heftet
    sich mit `position:sticky; left:0` an den linken Rand. Die Fläche bleibt an
    der Zelle und damit durchgehend; das Polster wandert in die Kopfzeile. Vier
    Deklarationen im Stylesheet, ein Klassenname in `import.php`, null Zeilen
    Skript. Die Annahme im Absatz oben, es brauche eine gemessene Zahl, war
    **falsch**: Beide Fassungen sind bei sechs Fensterbreiten auf den Pixel
    gleich gemessen.
    *Abnahme erfüllt:* Bei 400 px sind Datum, Besatzungszeile **und** Plakette
    ohne waagerechtes Scrollen sichtbar — nachgemessen bei **sieben** Breiten
    (360 bis 1920 px), dazu das Auswahlfeld; waagerechter Überlauf der Seite
    **0** in jeder; die Spalten fluchten weiterhin über alle Gruppen (eine
    Tabelle, unverändert). Waagerecht um 1500 px gescrollt: Kopfzeile bleibt
    stehen. Die drei Bedienwege am delegierten Behandler nachgefahren
    (Zellbearbeitung, Überspringen, Tageswahl) — alle wirken; der getippte
    Zellwert überlebt zwei Neuzeichnungen.
    **Warum (c) verworfen wurde**, obwohl es zuerst gewählt war: Eine
    Kartierung mit fünf Linsen fand **58 Befunde, 22 davon „bricht"**, und alle
    drei Entwürfe wurden von allen drei Skeptikern widerlegt. Vier Befunde
    scheitern **lautlos** — der delegierte Behandler hängt an `$('tabelle')`
    und hätte beim Seitenstart die ganze Importseite mitgerissen (`sperrstatus()`
    läuft dann nie, der Sperrhinweis bleibt versteckt); das Auswahlfeld der
    Tageswahl wäre aus dem Tabellenbaum gefallen und sein Scheitern erst **nach**
    dem Import in den Daten sichtbar geworden; mehrere `id="tabelle"` hätten nur
    die erste Gruppe bedienbar gelassen; `.imp-daygroup td` hätte nichts mehr
    getroffen. Dazu: (c) kann die **dritte Bedingung der Abnahme oben** —
    Spaltenflucht über alle Gruppen — nachweislich nicht erfüllen. Nachgemessen
    ist auch, dass die naheliegende Rettung nicht trägt: `width` auf der einen
    abweichenden Spalte („Aktion", 143/183/64 px je Gruppe) ist in dieser
    Tabelle wirkungslos (183 px mit und ohne Regel, nur `min-width` beißt), und
    `table-layout:fixed` macht das Eingabefeld **52 px** breit und lässt **9 von
    14** Spaltentiteln aus ihrer Zelle laufen.
    **Was bleibt und bewusst nicht behoben ist:** Der Kopf wird am Handy hoch —
    231 px bei 400 px im ungünstigsten Fall (zwei abweichende Rollen mit langen
    Namen), rund 130 px bei einer. Kürzen wäre eine Zeile CSS; der Text ist aber
    der Grund, warum jemand hinsieht.

41. **Fünf Klassen im Markup ohne Regel — Gestaltungsfragen, am 12.09.2026 entschieden.**
    *Aufgenommen in P3/O12 als Rest von Nr. 39.* Nach dem Eintragen der
    begründeten Fälle in `tools/vollstaendigkeit/ohne-regel.md` blieben sechs
    Namen übrig, bei denen die Frage offen war, ob sie eine Regel brauchen;
    seit S9/AP1 sind es **fünf** (gemessen 13.09.2026) — `rmneu` ist dort
    beantwortet, siehe unten. Sie stehen mit dem Vermerk `[offen]` und
    bleiben deshalb ein Befund:

    - `imp-warn` — „abweichende Crew (…)" in der Kopfzeile einer Tagesgruppe
      der Importvorschau. Ein **Warnhinweis, der wie Fließtext aussieht**;
      von allen fünf der wahrscheinlichste echte Fund.
    - `imp-daygroup` — die Kopfzeile einer Tagesgruppe selbst. Sie trägt ihren
      Text in `<strong>`, sonst nichts: eine Gruppenüberschrift, die aussieht
      wie eine Datenzeile.
    - `rea-kopf`, `rea-beginn` — Kopfzeile und Beschriftung einer
      Reanimationssitzung. Das Aussehen kommt vom Nachbarn `phasen-eingabe`
      bzw. von der Elementregel für `label`; kein Skript liest die Klassen.
      Entweder Reste, oder die Kopfzeile soll sich von einer gewöhnlichen
      Phasenzeile abheben.
    - `rmneu` — der Knopf „neu" in der Rettungsmittelwahl, neben `rmopt`.
      **Beantwortet in S9/AP1** und seither auf `streichliste.md` („Ja, sie
      hebt sich ab — sie ist eine Handlung, kein Datensatz").
    - `phasen-name` — der Name einer Phase in der Einsatzansicht.

    Jedes davon ist eine **Entscheidung**, kein Aufräumen: Entweder die Klasse
    verschwindet, oder sie bekommt eine Regel — und dann ist das eine neue
    Darstellung und braucht nach `docs/Design.md` 1 eine Freigabe. Deshalb
    nicht am Phasenende erledigt.

    **Entschieden am 12.09.2026:** `imp-warn` und `imp-daygroup` bekommen
    eine Regel — dort ist tatsächlich etwas schief (ein Warnhinweis, der wie
    Fließtext aussieht, und eine Gruppenüberschrift, die wie eine Datenzeile
    aussieht). `rea-kopf`, `rea-beginn` und `phasen-name` werden gestrichen
    und **mit Begründung** in `tools/vollstaendigkeit/streichliste.md`
    eingetragen. Die zwei neuen Regeln sind zwei **neue Darstellungen** und
    gehen in die **Mockup-Runde** (mit Nr. 42, 45 und 124; Ort im
    Rahmenplan).
    **Die drei Streichungen sind erledigt (Backlog-Runde 3, AP5, Web 19.3.1,
    13.09.2026); der Punkt bleibt offen und liegt bei 9c.** `rea-kopf` und
    `rea-beginn` sind aus `einsatz_form.php`, `phasen-name` zweimal aus
    `einsatz.php`; alle drei stehen mit Begründung auf `streichliste.md`, die
    drei `[offen]`-Zeilen in `ohne-regel.md` sind heraus. Gemessen gegen den
    Stand davor: `[offen]` **5 → 2**, „auf der Streichliste, aber noch im
    Markup" **0**, „`ohne-regel.md`: Eintrag ungenutzt" **0**, Befunde
    insgesamt **334 → 330**. Die vierte weggefallene Meldung hatte der Auftrag
    nicht vorhergesehen: `rea-kopf` stand im alten Stylesheet und war bis
    hierher auch „ohne Gegenstück" (**53 → 52**).
    **Im Browser bewegt sich nichts.** Vier Seiten vor und nach der Änderung:
    im DOM `rea-kopf` 2 → 0, `rea-beginn` 2 → 0, `phasen-name` 9 → 0 und
    22 → 0, `rea-sitzung` und `phasen-eingabe` unverändert. Bildvergleich
    **0 abweichende Bildpunkte** auf allen vier Seiten, nachdem der
    Demo-Zähler geschwärzt war — er zählt bis zum nächsten Reset herunter und
    verursachte auf jeder Seite dieselben 3079 Punkte (Rahmen y 117–126).
    **Beim Austragen berichtigt:** Der Eintrag zu `phasen-name` in
    `ohne-regel.md` nannte `einsatz.php:631`; es sind die Zeilen 764 und 805.
    **Offen bleiben `imp-warn` und `imp-daygroup`** — sie brauchen je eine
    Regel, und das sind zwei neue Darstellungen (Mockup-Runde 9c).

    **Erledigt mit Web 19.4.0** (Mockup-Runde 9c, AP1, 13.09.2026).
    `imp-daygroup` hat eine Regel: Rauch als Fläche, kräftige Oberlinie, das
    Datum in Kopfschrift und Dunkelblau (und **deutsch** statt ISO — F-MR-2),
    der Rest gedämpft. `imp-warn` ist **ersatzlos gestrichen**: Die Warnung
    ist eine `.plakette-orange` mit dem Symbol `warnung` geworden (M-MR-01,
    Variante A, F-MR-1), die Gruppe „Nicht zuordenbar" trägt dieselbe
    Kopfzeile mit `.plakette-rot` (F-MR-3). Gemessen gegen den Stand davor:
    `[offen]` **2 → 0**, „ohne Gegenstück" **52 → 50**, „auf der Streichliste,
    aber noch im Markup" **0**, „Eintrag ungenutzt" **0**, Befunde insgesamt
    **330 → 326**. Im Browser mit einer Datei geprüft, die alle drei Fälle
    auslöst: 400/720/1280 px, **0** waagerechter Überlauf, keine
    Konsolenfehler.
    **Ein Befund ist dabei entstanden und NICHT behoben** (Fehlerfund 2 im
    Konzept, eigener Backlog-Punkt Nr. 182): Die Kopfzeile sitzt in einer
    Zelle, die so breit ist wie die ganze Vorschautabelle — 2677 px gegen
    342 bis 1354 px Sichtfenster. Besatzung und Plakette stehen damit in
    jeder Breite außerhalb des Sichtfensters. Das ist älter als diese Stufe
    (der alte Fließtext stand an derselben Stelle), aber es trifft jetzt eine
    Plakette, die Aufmerksamkeit will.

179. **Die Zusage „keine fremde Quelle zur Laufzeit" zählt kein Prüfmittel
    nach.** *Aufgenommen 13.09.2026 beim Beheben von Nr. 176, gefunden von der
    Gegenprüfung des Nachtrags.* `CLAUDE.md` 4 verspricht: kein CDN, keine
    Google Fonts, kein externes Skript; Schriften und Bibliotheken liegen unter
    `server/assets/`. **Nachgesehen am 13.09.2026: Dieses Versprechen hat kein
    Messmittel.** `tools/vollstaendigkeit/` Gruppe 5 („Zusagen") kennt genau
    zwei — kein natives `confirm()`/`alert()`/`prompt()` und „jede Seite mit
    Hülle hat ihr Gerüst" —, und `pruefen.py` sieht keine einzige Adresse an.
    Kein anderes Werkzeug zählt es. **Auch die Laufzeit hält es nicht:** Die
    Anwendung schickt **keine** Content-Security-Policy (0 Fundstellen für
    `Content-Security-Policy` unter `server/`). Aufgefallen ist es, weil der
    Kommentar zu Nr. 176 die Lücke an dieses Prüfmittel weiterschob — also
    genau der Fehler, den Nr. 176 behoben hat, nur umgekehrt: Der Code behauptet
    eine Prüfung, die es nicht gibt. Beide Behauptungen sind berichtigt (Code,
    LIESMICH, Changelog). **Zu tun:** die Zusage nachzählbar machen, am besten
    als dritte Prüfung in Gruppe 5 — jede `src`/`href`/`url()`-Adresse und jeder
    `fetch`-Aufruf unter `server/` muss entweder relativ sein oder auf der
    Ausnahmeliste stehen. **Die Ausnahmeliste ist der eigentliche Inhalt:** Die
    Kartenkacheln und die Ortssuche sind **gewollte** Laufzeitquellen mit
    dokumentierter Herkunft (`map_layers.js`, `ortsfeld.js`), und seit S9/AP2
    steht die Anschrift des Adressdienstes nicht mehr im ausgelieferten Code,
    sondern in einer Einstellung — die Prüfung muss beides auseinanderhalten,
    sonst meldet sie das Erlaubte. **Eine CSP ist die zweite Hälfte** und
    gehört in dieselbe Überlegung; sie braucht Ausnahmen für genau diese
    Quellen und ist damit keine Nebenzeile, sondern eine Festlegung.
    *Abnahme:* die Prüfung meldet **0** Befunde am heutigen Stand, **1** für
    eine eingeschleuste `https://cdn.example/x.js` in einer Seite, und die
    Ausnahmeliste nennt je Eintrag den Grund. Zuordnung: Backlog-Runde, dem
    Bedrohungsmodell (P6, R69) zuarbeitend.
    **Erledigt 13.09.2026 (Backlog-Runde 3, Nachtrag AP12 — keine
    Versionsstufe, nur `tools/`).** Dritte Prüfung in Gruppe 5 „Zusagen":
    **`fremde Quelle`** in `tools/vollstaendigkeit/pruefen.py`.
    **Das Muster ist mit Absicht grob**, und das ist die Lehre dieses
    Punktes: Ein Ausdruck, der nur die Ladekonstrukte kennt (`src=`,
    `<link href=`, `fetch(`, `url()`, `@import`), meldete am 13.09.2026
    **0 Treffer** — während fünf echte Laufzeitquellen im Code standen. Die
    Kacheln gehen über `L.tileLayer(...)`, die Anschrift des Adressdienstes
    ist eine PHP-Konstante. Gemeldet wird deshalb **jede absolute Adresse**
    in eigenem Quelltext (`.php`, `.js`, `.css` unter `server/`, ohne
    `vendor/`), und die Ausnahmeliste trägt die Begründung.
    **Die Liste ist der Inhalt, nicht der Nebenschluss:** 15 Einträge, und
    die Spalte „Grund" sagt die **Art** — vier gewollte Laufzeitquellen
    (drei Kachelserver plus Luftbild), eine fünfte als Rückfall im
    Kartendialog, der Adressdienst als *Vorgabe* (seit S9/AP2 eine
    Einstellung, je Installation und Konto abschaltbar, R79), sechs
    **Navigationsziele** (Lizenz- und Spendenhinweise, die CC-BY-SA und ODbL
    verlangen — ein `<a href>` lädt nichts), ein **XML-Namensraum**
    (`GPX_NS`, wird nie abgerufen) und zwei **Beispieltexte**.
    **Abnahme erfüllt, mit Gegenprobe:** am heutigen Stand **0 Befunde,
    15 Ausnahmen, 0 ungenutzt**, Gesamtzahl unverändert **330**; mit einer
    eingeschleusten `https://cdn.example/x.js` in `impressum.php` genau
    **1 Befund** mit `Datei:Zeile` und Gesamt **331**, danach zurückgenommen.
    **Eine Grenze steht im Code, nicht in einer Fußnote:** Auf `.css` wendet
    der Kommentar-Abtaster die JS-Lesart an — `/* */` trifft er richtig,
    hielte aber ein unquotiertes `url(//host)` für einen Kommentaranfang.
    Heute gibt es keines (gemessen: 0 absolute Adressen in den Stylesheets,
    die Schriften liegen lokal).
    **Die zweite Hälfte ist nicht mitgemacht:** Eine
    Content-Security-Policy schickt die Anwendung weiterhin nicht. Das ist
    eine Festlegung und kein Nachtrag — sie braucht Ausnahmen für genau
    diese Quellen. Steht als **Nr. 181**.

178. **Die Kopplungsprobe wirft JEDE Meldung „Failed to load resource" weg.**
    *Aufgenommen 13.09.2026 beim Beheben von Nr. 176; nach K4 nicht
    mitbehoben.* Beim Beheben von Nr. 176 sind die Geschwisterwerkzeuge nach
    demselben Fehler durchsucht worden. Zwei sind in Ordnung:
    `tools/referenzdatensatz/browser/papierkorb_misch.mjs` prüft Text **und**
    Fundstelle und führt in seinem Muster nur Gastgebernamen plus zwei Codes,
    die auf `127.0.0.1` nicht vorkommen können (`ERR_TUNNEL_CONNECTION_FAILED`,
    `ERR_NAME_NOT_RESOLVED`); `tools/messstand/browserprobe.mjs` hängt an
    `requestfailed` und hat die Adresse immer dabei. **Eines ist es nicht:**
    `tools/kopplungsprobe/rundlauf.mjs` filtert mit
    `/tile\.openstreetmap\.org|ERR_ABORTED|Failed to load resource/` **nur
    über den Text** — die dritte Alternative verwirft damit *jede*
    Ressourcenmeldung, gleich welcher Herkunft und gleich welchen Grundes. Der
    Kommentar daneben begründet die Kacheln und `ERR_ABORTED`; die dritte
    Alternative begründet er nicht. **Gemessen an acht gebauten Fällen: 4 von 8
    falsch eingestuft, alle vier verschluckte echte Fehler** — Symbol mit
    `ERR_CONNECTION_RESET`, Stylesheet mit **404**, API mit **500**, Skript mit
    `ERR_CONNECTION_REFUSED`. **Der 404 und der 500 sind der scharfe Teil:**
    Für sie feuert `requestfailed` nicht (die Anfrage ist auf Transportebene
    gelungen), sie stehen also nur in der Konsole — und die wird hier
    weggeworfen. Das Werkzeug meldet „0 Konsolenfehler" und könnte einen
    Serverfehler mitten im Kopplungsrundlauf nicht sehen. **Zu tun:** dieselbe
    Trennung wie in `tools/screenshots/aufnehmen.mjs` (Nr. 176) — Gastgeber am
    Namen, Verbindungscodes nur auf fremder Fundstelle, „Failed to load
    resource" **nicht** als Rauschen —, dazu die Fundstelle mitlesen; die
    Kopplungsprobe liest sie heute nicht. *Abnahme:* ein eingeschleuster
    500er-Aufruf auf der eigenen Basis erscheint im Lauf, eine Kachel nicht,
    und der Rundlauf bleibt im Übrigen bei seiner Zahl. Zuordnung:
    Backlog-Runde.
    **Erledigt 13.09.2026 (Backlog-Runde 3, Nachtrag AP12 — keine
    Versionsstufe, nur `tools/`).** Statt eines Ausdrucks für alle drei
    Kanäle jetzt **drei Kanäle, drei Regeln** — die Unterscheidung folgt
    daraus, was der Kanal überhaupt liefert. **`console`** hat Text *und*
    Fundstelle: Rauschen ist eine fremde Quelle am Namen oder ein
    Verbindungsfehler auf nachweisbar fremder Fundstelle; alles andere zählt,
    auch ein Statuscode auf der eigenen Basis. **`requestfailed`** hat die
    Adresse immer dabei und einen Statuscode nie: Rauschen ist eine fremde
    Quelle — und `ERR_ABORTED` auf **jeder** Herkunft, weil dieser Rundlauf
    mehrfach navigiert und eine überholte Anfrage genau das meldet (gemessen
    im Nachtrag zu Nr. 176: 14 solche Abbrüche in der ersten Ladung nach der
    Anmeldung, 0 in den folgenden). **`pageerror`** ist **nie** Rauschen.
    Die Klasse „Statuscode der Seite selbst", die der Bilderlauf braucht,
    fehlt hier mit Absicht: Dieser Rundlauf besucht keine Seite, die
    absichtlich mit 404 oder 409 antwortet.
    **Belegt in vier Richtungen.** (1) Neue **Selbstprobe**
    `node tools/kopplungsprobe/rundlauf.mjs --selbstprobe`: dreizehn Fälle,
    je einer trägt eine Regel — **13 von 13**. (2) **Mutationsprobe**, sechs
    Läufe mit je einer herausgenommenen Regel: **12 von 13** in jedem. Die
    ersten elf Fälle allein hätten das nicht geleistet: Zwei Zweige von
    `herkunft()` blieben grün, weil der Fall mit leerer Fundstelle schon an
    der ersten Zeile herauskommt — deshalb Fall 12 (`<anonymous>`) und 13
    (`data:`). Dieselbe Lehre wie bei der blinden Selbstprobe zu Nr. 176,
    und diesmal vor dem Melden gemessen. (3) Dieselben dreizehn Fälle durch
    die **alte** Regel, wörtlich aus `git show origin/main` geholt:
    **7 von 11** der damals vergleichbaren Fälle, **4 verschluckte echte
    Fehler** — alle vier auf dem Konsolenkanal, darunter der 500er und der
    404. (4) **Am laufenden Stand**, mit einer Wegwerfdatei
    `server/probe178.php` (HTTP 500) und einem Kachelabruf im selben Lauf:
    Der 500er **erscheint** im Protokoll („console: … status of 500 …
    [/probe178.php]"), die Kachel **nicht**, und der Rundlauf bleibt bei
    **25 Erwartungen, 0 nicht erfüllt**. Die Wegwerfdatei ist danach
    gelöscht; `git status` ist sauber.
    **Die LIESMICH behauptet nicht mehr „dieselbe Rauschregel wie der
    Bilderlauf"** — sie nennt die drei Kanäle und den Unterschied.

180. **Die Kopplungsprobe misst die Knopfhöhe gegen einen Sollwert, den es
    seit Web 15.5.0 nicht mehr gibt — und ist seither rot.** *Aufgenommen
    13.09.2026, gefunden beim Nachfahren der Kopplungsprobe für Nr. 178.*
    `tools/kopplungsprobe/rundlauf.mjs:214` prüft
    `mass.knoepfe.every(h => h === 44)` — **ein** Sollwert, fest verdrahtet.
    Seit Web 15.5.0 gelten **zwei** (E-S8-09, R76): 44 px am Fingergerät und
    unter 1024 px, **36 px am Zeigergerät ab 1024 px**. Der Rundlauf öffnet
    seinen Browser mit 1280 px als Zeigergerät, misst also zu Recht 36 px und
    meldet sie als Fehler. **Gemessen am 13.09.2026: 25 Erwartungen, 1 nicht
    erfüllt — „6 Knöpfe, 36 px".** Alles andere grün, Konsolenfehler 0,
    Prüfgerät wieder abgemeldet (2 Geräte wie vorher).
    **Der Fehler liegt im Prüfmittel, nicht in der Anwendung**, und das ist
    belegt: Der Bilderlauf kennt beide Sollwerte und meldet über **360**
    Aufnahmen in acht Breiten **0** Knöpfe falscher Höhe — auch bei 1280 px.
    **Die unangenehme Zahl daran ist das Datum:** Der Sollwert ist am
    06.09.2026 zweigeteilt worden, der Rundlauf ist seither rot, und
    aufgefallen ist es am 13.09.2026 nur, weil ein anderer Punkt zufällig
    dazu führte, ihn zu fahren. Ein Prüfmittel, das niemand fährt, ist kein
    Prüfmittel — dieselbe Lehre wie bei Nr. 172 (Erwartung, die flackert) und
    Nr. 176. **Zu tun:** dieselbe Weiche wie im Bilderlauf — Sollwert aus der
    emulierten Eingabeart und der Fensterbreite ableiten, Ausnahmen benannt —,
    und den Rundlauf in die Reihe der Mittel aufnehmen, die nach einem Paket
    laufen. *Abnahme:* `node tools/kopplungsprobe/rundlauf.mjs` meldet
    **25 Erwartungen, 0 nicht erfüllt** am Zeigergerät, und mit erzwungenem
    Fingergerät ebenfalls 0. Zuordnung: Backlog-Runde.
    **Erledigt 13.09.2026 (Backlog-Runde 3, Nachtrag AP12 — keine
    Versionsstufe, nur `tools/`).** `rundlauf.mjs` leitet den Sollwert jetzt
    aus der emulierten Eingabeart und der Fensterbreite ab, dieselbe Weiche
    wie im Bilderlauf: `(!FINGER && BREITE >= 1024) ? 36 : 44`. Dazu zwei
    neue Schalter — `--finger` (Fingergerät) und `--breite` —, und die
    Eingabeart wird **vor der Messung erneut gesendet**, weil sie sonst nach
    dem ersten Vollseiten-Screenshot zurückfällt (Fund aus S8/AP7; dieser
    Rundlauf macht mehrere Abzüge). Nur im Fingerlauf gesendet: Am
    Zeigergerät kippt `{enabled:false}` beide Medienmerkmale auf
    `none`/`coarse` und misst denselben Fehler spiegelverkehrt.
    **Abnahme erfüllt, beide Richtungen gemessen:** als Zeigergerät
    **25 Erwartungen, 0 nicht erfüllt** („6 Knöpfe, 36 px"), mit `--finger`
    ebenfalls **25 / 0** („6 Knöpfe, 44 px"). Die Beschriftung nennt jetzt
    Sollwert, Eingabeart und Breite, damit die Zeile im Protokoll ohne
    Nachdenken zu lesen ist.

176. **Der Rauschfilter des Bilderlaufs verschluckt auch lokale Fehler.**
    *Aufgenommen 13.09.2026 in Backlog-Runde 3, AP10, beim Aufklären von
    Abrufen, die auf dem Prüfstand scheiterten.* `istRauschen()` in
    `tools/screenshots/aufnehmen.mjs` prüft `KACHELRAUSCHEN` gegen den
    **Meldungstext** und gegen die Fundstelle. Im Muster stehen neben den
    Kartenhosts auch drei Fehlercodes — `ERR_CONNECTION_RESET`,
    `ERR_CONNECTION_CLOSED`, `ERR_ABORTED`. Ein Abruf **auf dem eigenen
    Server**, der mit einem dieser drei scheitert, wird deshalb als
    Kartenrauschen weggeworfen, obwohl seine Fundstelle `127.0.0.1` ist.
    Gemessen am Muster: von fünf gebauten Fällen mit lokaler Fundstelle
    werden **drei von fünf verschluckt** (RESET, CLOSED, ABORTED) und zwei
    gezählt (REFUSED, HTTP 500). Der Bericht meldet dann „0 Konsolenfehler"
    für eine Seite, auf der das Stylesheet nicht angekommen ist — dieselbe
    Falle wie F-P3-AQ, nur eine Ebene tiefer. **Zu tun:** die Codeliste nur
    dann greifen lassen, wenn die Fundstelle **nicht** die eigene Basis ist;
    die Hostliste bleibt wie sie ist. *Abnahme:* ein eingeschleuster
    Verbindungsabbruch auf einer Adresse der eigenen Basis erscheint im
    Bericht, einer von `tile.openstreetmap.org` nicht. Zuordnung:
    Backlog-Runde.
    **Erledigt 13.09.2026 (Backlog-Runde 3, Nachtrag AP11 — keine
    Versionsstufe, nur `tools/` und `docs/`); auf Anweisung des
    Auftraggebers gleich mitbehoben, weil der Punkt das Messmittel
    betrifft, mit dem alle Bildzahlen dieser Stufe belegt sind.**
    `istRauschen()` hat jetzt **drei getrennte Klassen**: fremde Quellen am
    Namen (Gastgeber im Wortlaut oder in der Fundstelle), der Statuscode der
    Seite selbst, und Verbindungsfehler **nur auf einer fremden Fundstelle**.
    Die Herkunft wird über `URL.origin` gegen `BASIS` verglichen, nicht über
    `startsWith` — das verträgt einen abschließenden Schrägstrich in
    `--basis` und rechnet Vorgabeports mit.
    **Drei Entscheidungen bewusst zur lauten Seite hin:** Eine Meldung ohne
    **zuordenbare** Fundstelle wird **gezählt**, nicht verworfen — leer, keine
    Adresse (`<anonymous>`) oder undurchsichtige Herkunft (`data:`, `blob:`, wo
    `URL.origin` die Zeichenkette „null" liefert); der Fehlercode wird nur
    noch im Wortlaut gesucht, nicht auch in der Fundstelle (in einer URL
    kommt er nicht vor — die zweite Suche war ohne Wirkung); und **Klasse 2
    verwirft nur einen Statuscode** — verliert die Seite selbst die
    Verbindung, wird das gezählt, denn ein Verbindungsabbruch ist kein
    Statuscode.
    *Die erste und die dritte dieser drei sind Berichtigungen aus der
    Gegenprüfung des Nachtrags, nicht des ersten Entwurfs; er zählte die leere
    Fundstelle, verwarf aber die unlesbare, und seine Klasse 2 verschluckte
    genau die drei Codes, um die dieser Punkt geht.*
    **Belegt in vier Richtungen.** (1) Neue **Selbstprobe**
    `node tools/screenshots/aufnehmen.mjs --selbstprobe` — **fünfzehn** gebaute
    Fälle mit Sollwert, **15 von 15** erwartungsgemäß; sie braucht **keinen
    laufenden** Browser und keinen Server (das Playwright-Modul muss vorhanden
    sein, weil die Datei es am Kopf lädt) und löscht die Ausgabe nicht.
    (2) **Mutationsprobe:** Jede der drei Klassen einzeln herausgenommen, dazu
    die Schranke an Klasse 2 und beide Zweige der Herkunftsauskunft — **sechs
    Läufe, je 14 von 15**. Ohne sie wäre die Probe blind geblieben: Ihr erster
    Entwurf hatte zehn Fälle und meldete **10 von 10 auch bei gelöschter
    Klasse 1 oder 3**, weil jeder verwerfende Fall einen Kachelgastgeber in der
    URL trug. Das hat die Gegenprüfung gefunden, nicht ich; das Rezept steht in
    der LIESMICH. (3) Die **ersten zehn** Fälle durch die
    **alte** Funktion, wörtlich aus `git show origin/main` geholt statt
    abgeschrieben: **6 von 10** — falsch waren die drei lokalen Abbrüche und
    der Fall ohne Fundstelle. (4) Am laufenden Browser: Seite geladen,
    **PHP-Server angehalten** (socat blieb, die Basis also dieselbe), Symbol
    und API-Aufruf nachgeladen — zwei Konsolenfehler auf der eigenen Basis
    (`ERR_EMPTY_RESPONSE`, `ERR_CONNECTION_RESET`), davon verwarf der alte
    Filter **einen**, der neue **keinen**.
    **Die Abnahme dieses Eintrags ist damit erfüllt**, und zwar genauer als
    gefordert: Der Eintrag verlangte einen eingeschleusten Verbindungsabbruch
    auf der eigenen Basis, der im Bericht erscheint — gemessen ist der
    Abbruch am Browser **und** die Einstufung an der Funktion selbst.
    **Und die Zahl der Runde hält:** Der Abschlusslauf über 45 Seiten meldet
    mit der neuen Regel unverändert **0 Konsolenfehler**. Sie war also
    richtig — sie war nur nicht belegt.
    **Dabei eine falsche Aussage in der Dokumentation berichtigt:** Die
    LIESMICH behauptete, gefiltert werde „über die Fundstelle der Meldung,
    nicht über ihren Wortlaut". Für die Kachelhosts stimmte das, für die drei
    Codes nicht. Der Absatz nennt jetzt die drei Klassen und die Falle.

174. **Der Referenzbestand deckt „Rettungsmittel ohne Standort" nur noch
     zur Hälfte ab.**
    *Aufgenommen 12.09.2026 in Backlog-Runde 2, nachdem die Klickprobe den
    Verlust gemeldet hat.* Beim Neubau des Referenzbestands (Nr. 155,
    Web 19.2.0) haben **zwei** Rettungsmittel ihren leeren Standort
    verloren: „Reserve Talwang" und „Sanitätsdienst Seefest" standen bis
    dahin unter „Ohne Standort" und hingen danach an einem. Die
    **Demo-Fixture ist repariert** (beide wieder ohne Standort, gemessen
    2 von 6, Spurpunkte unverändert 55 861) — die **Referenzdatei** der
    Kreisläufe (`tools/referenzdatensatz/referenz/*.edbak` und `*.zip`)
    aber nicht: Sie stammt aus demselben Neubau und trägt weiterhin sechs
    Rettungsmittel **mit** Standort.

    *Was damit nicht geprüft ist:* ob ein `vehicles.base_id = NULL` einen
    Export und den Import zurück übersteht. Das Format trägt den Standort
    als **Namen** (`base_ref`), nicht als Kennung, und `base_ref: null` ist
    der Fall, der im Kreislauf jetzt nicht mehr vorkommt. Der Standort ist
    seit S9/AP4 freiwillig (E-S9-18) — der Fall ist also kein Sonderfall,
    sondern einer von zweien.

    *Warum es nicht sofort behoben wurde:* Die Referenzdatei neu zu
    erzeugen heißt, die dreistufige Einspielkette noch einmal zu fahren.
    Das ist kein Handgriff am Ende einer Sitzung, und es gehört mit Nr. 173
    zusammen, das dieselben Dateien anfasst.

    *Und die Lehre daneben:* Der Verlust ist **unbemerkt** durch Runde 1
    gegangen, obwohl die Kreisläufe dort grün waren. Gefunden hat ihn die
    **Klickprobe** — das einzige Prüfmittel mit einem Sollmaß, das nicht
    aus dem gebauten Zustand stammt. Dasselbe Muster wie Nr. 170.

    *Abnahme:* Der Kreislauf `edbak` trägt ein Rettungsmittel mit
    `base_ref: null`, und es kommt unverändert zurück.
    **Erledigt 13.09.2026 (Backlog-Runde 3, AP9 — keine Versionsstufe; an der
    Anwendung ist keine Zeile geändert).**
    **Die Ursache war nicht die Referenzdatei, sondern der Einspielweg.**
    `einspielen.py` schickte `ohne_standort=1` **neben** einer echten
    Standortkennung. Bis Web 16.3.0 war das richtig — damals stand im Formular
    ein Haken neben der Standortauswahl. Web 16.3.0 hat ihn durch den ersten
    Eintrag der Auswahlliste ersetzt („Ohne Standort"), mit Begründung: Der
    Haken „schlug eine verborgene Standortkennung; wer ihn setzte, sah nicht,
    WAS er damit überschrieb." Seither las das Feld niemand mehr, die Kennung
    daneben zählte, und **beide Einträge bekamen einen Standort**. Ein Sender,
    der ein Feld schickt, das niemand liest, meldet keinen Fehler — er wird
    still ignoriert. `einspielen.py` schickt jetzt `base_id=0`.
    **Beide Referenzdateien sind über die reguläre Kette neu erzeugt**
    (E-BR3-10), keine Zeile per SQL. Gemessen: vorher 0 von 6 ohne Standort,
    nachher **2 von 6**. Kette: Quelldaten 5 961 Einzelprüfungen / 0 Befunde,
    Generator 283 989 / 0, Ingest 526 Anfragen / 0 Fehler, 16 Diensttage
    zugeordnet, 79 nachgetragen, 2 von Hand, Sperrliste bestanden, 4
    CSV-Einsätze. Export: 188 Einträge (85 geschützt), 182 Aufzeichnungen mit
    55 861 Punkten; CSV 83 Einsätze, 172 GPX.
    **Abnahme gegen die neue Referenz:** edbak 287 687 Einzelvergleiche / 0
    unerklärt / 0 ungenutzt, csv 9 120 / 0 / 0. Das Kriterium — ein
    Rettungsmittel mit `base_ref: null` kommt **unverändert** zurück — an
    beiden Umlaufkonten gemessen: Referenz 2, edbak-Umlauf 2, csv-Umlauf 2.
    **Klickprobe 40 von 40** (sie hatte den Verlust mit 39 gefunden).
    **Nebenbei berichtigt:** Die LIESMICH nannte „3 Rettungsmittel", es sind 6;
    die zwei ohne Standort stehen jetzt ausdrücklich dabei.

173. **Die Umlaufprüfungen führen tote Regeln.**
    *Aufgenommen 12.09.2026 beim Neubau des Referenzbestands (Nr. 155,
    Web 19.2.0).* Beide Kreisläufe erfüllen ihr Abnahmekriterium —
    **edbak 287 687 Einzelvergleiche, 0 unerklärt** (16 erwartet) und
    **csv 9120 Einzelvergleiche, 0 unerklärt** (1021 erwartet) —, melden
    dabei aber **3 bzw. 2 ungenutzte Regeln**. Vorher waren es 0.

    *Die Ursache ist bekannt und harmlos:* Die Regeln beschreiben einen
    **Übergang**, den es nicht mehr gibt. Zwei betreffen
    `missions.notes` („GEMESSEN 143x") aus S9/AP7, als die Notizen halb in
    der Spalte und halb im verschlüsselten Block lagen; eine betrifft
    `kopf.version` („nach 11") aus demselben Paket, als die Nutzlast von 10
    auf 11 stieg. Der neu gebaute Referenzbestand trägt die Notizen von
    Anfang an im Block und beide Seiten dieselbe Nutzlastnummer — also
    keine Abweichung, also keine Regel, die greift.

    *Warum das trotzdem zählt:* Eine Ausnahmeregel, die nichts mehr
    erklärt, ist dasselbe wie eine tote Zeile in
    `tools/linkprobe/ausnahmen.md` — sie sieht aus wie geprüftes Wissen und
    ist keines mehr. Die Linkprobe macht den Lauf dafür rot; der Kreislauf
    nennt die Zahl nur. Das ist der mildere Umgang mit demselben Problem.

    *Zu tun:* Die drei bzw. zwei Regeln aus
    `tools/referenzdatensatz/vergleich/ausnahmen/{edbak,csv}_umlauf.json`
    entfernen, mit einem Satz im Änderungsverlauf der Datei, warum sie
    gegenstandslos geworden sind. **Nicht** am Ende einer langen Sitzung
    gemacht, weil die Dateien die Vergleichsgrundlage sind und ihr Format
    zwischen den beiden Arten abweicht.
    *Abnahme:* Beide Kreisläufe melden 0 unerklärte Abweichungen **und**
    0 ungenutzte Regeln.
    **Erledigt 13.09.2026 (Backlog-Runde 3, AP8 — keine Versionsstufe, nur
    `tools/`).** Vorher gemessen, wie der Auftrag es verlangt: **3 ungenutzte
    Regeln im edbak-Umlauf, 2 im csv-Umlauf** — die erwarteten Zahlen. Erst
    danach gestrichen, namentlich: `missions.notes` (zweimal, als `wert` und
    als `zusaetzlich`) und `kopf.version` „nach 11" aus `edbak_umlauf.json`;
    `felder.beschreibung` „nach pat_blob.notes" und die LIESMICH-Regel aus
    `csv_umlauf.json`. Alle fünf beschrieben den Übergang aus S9/AP7 (Notiz
    in den verschlüsselten Block, Nutzlast 10 → 11), den der heutige
    Referenzbestand nicht mehr kennt.
    **Der Grund steht in der Datei:** Das Feld `beschreibung` beider Listen
    trägt einen datierten Satz, welche Regeln weg sind und warum sie
    gegenstandslos wurden.
    **Gemessen danach:** edbak 287 687 Einzelvergleiche / 0 unerklärt / 16
    erwartet / 0 ungenutzt; csv 9 120 / 0 / 1 021 / 0. **Vorläufig**, weil
    AP9 die Referenzdateien ersetzt — beide Kreisläufe laufen danach erneut.

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
    **Erledigt 13.09.2026 (Backlog-Runde 3, AP7 — Prüfmittel ohne
    Versionsstufe; der Fund darin lief unter Web 19.3.1).** Zweite Prüfung der
    Gruppe „5 Zusagen": **Seite ohne Gerüst**.
    **Das Kriterium ist `ui_seite_start(`, nicht „bindet die Wache ein"**
    (E-BR3-06) — damit die naive Regel nicht wieder vorgeschlagen wird: Sie
    liefert 15 Treffer, und alle 15 sind richtig so. Wer `ui_seite_start()`
    ruft, gibt eine Seite aus; verlangt werden **beide** Hälften des Gerüsts,
    denn ein Gerüst, das nicht geschlossen wird, ist keines. Gezählt wird
    außerhalb von Kommentaren.
    **Gemessen:** 0 Befunde, **7 Ausnahmen** mit je eigenem Grund
    (`install.php`, `login.php`, `reset_request.php`, `wiederherstellen.php`,
    `pw_handling.php`, `rechtstext_seite.php`, `session_lib.php` — letztere
    gibt die Abmeldeseite **nach** `session_destroy()` aus), 0 ungenutzte
    Ausnahmen. `tag_spuren.php`, der Anlass des Punktes, steht **nicht** auf
    der Liste und ist **kein** Befund. Eine eingeschleuste Seite ohne Gerüst
    wird mit Datei und Zeile gemeldet.
    **Die Gegenrichtung hat sich sofort bezahlt gemacht** und ist der
    eigentliche Gewinn: Der Hinweis „Gerüst ohne Seitenhülle" zeigte auf
    `apk.php`. Deren 404-Seite ging ohne `<!doctype>`, ohne Titel und **ohne
    Stylesheet** hinaus (Quirks-Modus, Times New Roman) — erreichbar über
    jeden Verweis auf ein APK, das nicht mehr liegt. Mit einer Zeile behoben
    (Web 19.3.1); auf die Ausnahmeliste kommt die Datei **nicht**, ein Fund
    gehört behoben, nicht erklärt. Der Hinweis steht seither auf 0.
    **Ein stiller Fehler beim Bauen:** Beim ersten Lauf griff keine der
    sieben Ausnahmen — die Beschriftungen in `pruefen.py` sind ASCII („Seite
    ohne Geruest"), die Liste ist Markdown („Gerüst"). Der Vergleich löst
    Umlaute jetzt auf.

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
    — mit Ausnahmeliste für die **zwei** berechtigten Stellen (gemessen
    13.09.2026): `confirm.js` selbst benutzt `window.confirm` als Rückfall
    für Browser ohne `<dialog>`, und `forms.js` tut dasselbe für den Fall,
    dass `edConfirm` nicht geladen ist. *Berichtigt 13.09.2026:* Hier stand
    „die eine berechtigte Stelle" — eine Ausnahmeliste mit einem Eintrag
    wäre beim ersten Lauf rot gewesen.

    > **Warum eine Prüfung und nicht nur Aufmerksamkeit.** Der Fehler ist
    > unsichtbar: Ein natives `confirm()` funktioniert im Alltag, es fällt
    > erst bei einer abgeschalteten Dialogsorte oder in einem Prüfbrowser auf
    > — also genau dann, wenn niemand hinsieht.

    Naheliegender Ort: `tools/vollstaendigkeit/`, das ohnehin Markup und
    Stylesheet gegeneinanderhält, oder ein eigenes kleines Prüfmittel neben
    `tools/wortliste/`. Verwandt mit Nr. 36 (Klassennamen, die JavaScript
    sucht): beides sind Zusagen, die im Code stehen und die niemand nachzählt.
    **Erledigt 13.09.2026 (Backlog-Runde 3, AP6 — keine Versionsstufe, nur
    `tools/`).** Neue Prüfgruppe **„5 Zusagen"** in
    `tools/vollstaendigkeit/pruefen.py` mit der Prüfung **native Dialoge**:
    `confirm(`, `alert(`, `prompt(` — auch als `window.`-Aufruf — in
    `server/**/*.php` und `server/assets/*.js`, **außerhalb von Kommentaren**.
    Kein eigenes Werkzeug: Das Mittel liest diese Dateien ohnehin und hat
    Bericht, Listenleser und Ausnahmemechanik (E-BR3-05).
    **Der Kommentar-Abtaster ist der Kern und bewusst kein regulärer
    Ausdruck:** `//` steht in jeder URL, `#` in jeder Farbe, `/*` in mancher
    Zeichenkette. Er geht Zeichen für Zeichen und merkt sich, ob er in einer
    Zeichenkette steht; Zeilenumbrüche bleiben erhalten, sonst zeigte jede
    Fundstelle daneben. Was er nicht kann (Heredoc, Regex-Literale mit `//`),
    steht an seinem Kopf — im eigenen Code kommt beides nicht vor.
    **Messbar, was das ausmacht:** grob **5** Treffer, drei davon in
    Kommentaren; nach dem Abtaster **2** — und das sind genau die
    berechtigten Rückfälle (`assets/confirm.js` für Browser ohne `<dialog>`,
    `assets/forms.js`, falls `confirm.js` nicht geladen ist). Beide mit Grund
    in der neuen Liste `tools/vollstaendigkeit/zusagen.md`, die **in beide
    Richtungen** geprüft wird.
    **Gegenproben:** Ist-Stand 0 Befunde / 2 Ausnahmen / 0 ungenutzt,
    Gesamtzahl unverändert 330. Eingeschleustes `window.confirm("x")` in einer
    PHP-Datei → **1 Befund** mit Datei und Zeile, Gesamtzahl 331. Dieselben
    Aufrufe als Blockkommentar, Zeilenkommentar und PHP-Raute → **0**.
    Ausnahme auf eine Datei umgebogen, die es nicht gibt → **1 Befund plus
    1 „Ausnahme ungenutzt"**. Alle Proben entfernt.

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
    **Entschieden am 12.09.2026: nicht erheben.** Ein Zeitstempel wäre eine
    neue Erhebung über eine Handlung der NutzerIn; bei einer Anwendung, deren
    Versprechen die Ende-zu-Ende-Verschlüsselung ist, wäre das kein
    Nebenprodukt. Stattdessen sagt das Handbuch, dass die Anwendung es nicht
    weiß und was die Kennzahlen tatsächlich messen. Gegengeprüft 13.09.2026:
    `users` trägt keine solche Spalte, die Kennzahlen messen über
    `edbak_konto_stand()` ausschließlich die Konto-Backups der Verwaltung.
    **Damit ist der Punkt ein Absatz im Handbuch** — die billigste Umsetzung
    im ganzen Backlog. Zuordnung: **Backlog-Runde** (Handbuchsatz).
    **Erledigt 13.09.2026 (Backlog-Runde 3, AP3 — keine Versionsstufe, nur
    `docs/`).** Zwei Absätze in `docs/Handbuch.md` 11.2, direkt hinter den
    vier Zahlen: Der erste sagt, was gemessen wird (die beiden Kennzahlen,
    die beiden Filter, die Spalte je Zeile und die Erinnerungsmail sehen
    alle nur auf das jüngste Paket im Kontoordner — die zweite der drei
    Backup-Bedeutungen aus Kapitel 6); der zweite, was nicht gemessen wird,
    samt der Folge in der Liste: Ein Konto, das zuverlässig eigene Backups
    zieht, steht dort genauso unter „nie Konto-Backup" wie eines, das nichts
    tut.
    **Eine Zahl im Konzept war falsch.** Der Auftrag lautete „alle vier
    messen Konto-Backups der Verwaltung". Die vier Zahlen über der Liste
    sind aber Konten, Admins, Konto-Backup überfällig und nie Konto-Backup —
    nur die letzten zwei haben mit Backups zu tun. Die Vier dieses Eintrags
    meinte etwas anderes: zwei Kennzahlen plus Filter plus Erinnerungsmail.
    Der Absatz sagt deshalb „zwei der vier Zahlen" und zählt die vier
    Stellen einzeln auf.
    **Gegengeprüft am Code (13.09.2026):** `users` hat 16 Spalten, keine
    davon ein Backup-Zeitpunkt; `edbak_konto_stand()` liest
    `edbak_pakete()` und die Begleitdatei, also nur den Kontoordner; die
    Erinnerungsmail filtert über `edbak_faellige_konten()` auf dieselben
    Stände. **Nichts in der Oberfläche** — die Zahlen heißen seit S8 richtig.

94. **„bitgleich" gegen „pixelgleich" in `tools/uhr-bilder/`.**
    *Aufgenommen 03.09.2026 aus S5, Vorbereitung V-S5-05.*
    Der Kopfkommentar von `erzeugen.sh` sagt, die erzeugten Kacheln seien
    **bitgleich**; die `LIESMICH.md` daneben sagt **pixelgleich**. Beides kann
    nicht stimmen: PNG trägt einen Zeitstempel-Chunk, und der ändert sich bei
    jedem Lauf. Wer die Zusage prüft, prüft je nach gelesenem Dokument etwas
    anderes.
    **Vorschlag:** ein Wort ändern — oder `-define png:exclude-chunk=time`
    setzen und die stärkere Zusage tatsächlich einlösen.
    **Erledigt 13.09.2026 (Backlog-Runde 3, AP2 — keine Versionsstufe, nur
    `tools/`).** Gewählt ist **ein Wort**, nicht `-define
    png:exclude-chunk=time` (E-BR3-04): `compare -metric AE` zählt
    Bildpunkte, also ist „pixelgleich" die Zusage, die es deckt — die
    stärkere einzulösen belegte nichts, was hier gebraucht wird. Geändert in
    `tools/uhr-bilder/erzeugen.sh` (Kopfkommentar, mit Verweis auf die
    LIESMICH) und `tools/uhr-bilder/LIESMICH.md` („Warum es dieses Werkzeug
    gibt", mit der Begründung).
    **Der Eintrag war ungenau:** Die Selbstwiderlegung lag nicht zwischen den
    beiden Dateien, sondern **innerhalb der LIESMICH** — an `HEAD` gemessen
    Zeile 27 („bitgleich") gegen Zeile 35 („pixelgleich"), acht Zeilen
    auseinander. `erzeugen.sh` stand zusätzlich auf der stärkeren Seite.
    **Ein Prüfmittel hing daran, und das Konzept nannte es nicht:**
    `tools/s5-anker/anker.py` verankerte die Stelle wörtlich an
    `sie BITGLEICH \(geprueft` und hätte danach „NICHT GEFUNDEN" gemeldet. Der
    Anker heißt jetzt `uhrbilder.wortlaut` und sucht den Teil des Satzes, den
    die Streitfrage nicht berührt; gemessen danach „unveraendert", nicht
    gefundene Anker unverändert **7**.
    **Nachgetragen am 13.09.2026, nach dem Startvorgang aus AP4:** Mit
    ImageMagick und `rsvg-convert` im Container ist `erzeugen.sh` **gelaufen**,
    und damit ist die Zusage nicht nur neu formuliert, sondern **gemessen**:
    17 PNG neu erzeugt, davon **0 bytegleich** mit dem Stand in Git (jede trägt
    einen neuen `tIME`-Block) und **0 mit abweichenden Bildpunkten**
    (`compare -metric AE` gegen `git show HEAD:<pfad>`). „bitgleich" wäre also
    nachweisbar falsch gewesen, „pixelgleich" ist nachweisbar richtig.
    Gegenprobe, dass das Messmittel überhaupt misst: weiß gegen schwarz,
    64 × 64 px, **4096** Bildpunkte. Die 17 Dateien sind danach zurückgesetzt —
    ein Zeitstempel gehört nicht in einen Commit.

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
    **Erledigt 13.09.2026 (Backlog-Runde 3, AP1 — keine Versionsstufe, nur
    `tools/`).** Aufgeschrieben in `tools/uhr-pruefstand/LIESMICH.md`, neuer
    Unterabschnitt „`WatchUi.Confirmation`: die Auswahl ist im Bild nicht zu
    sehen" unter „Bedienung simulieren", direkt hinter „Tasten sind heikler als
    Maus". Er trägt alle vier gemessenen Aussagen und verweist für die Zahlen auf
    den Simulator-Rundlauf in `docs/konzepte/Pruefdokument-S5-Kopplung-umgekehrt.md`
    (Paket C). **Dabei präzisiert:** BACK ist kein *Ersatz* für „Nein" — weil
    `KoppelnDelegate` (`watch/source/PairView.mc`) nur `onResponse` hat, läuft das
    dort stehende `Pair.ablehnen(...)` bei BACK gar nicht; die zwei Fälle sind
    getrennt zu prüfen. **Nicht nachgemessen:** kein Connect-IQ-Simulator im
    Prüfstand dieser Runde; die Aussagen stammen aus dem Messprotokoll vom
    03.09.2026 (siehe Prüfdokument, Abschnitt 0).

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

    **Ausgetragen am 13.09.2026 — beantwortet, nicht erledigt.** *Erhebung
    mit R70 (03.09.2026) beantwortet; die Umsetzung führt der Fahrplan als
    P7. Ausgetragen, weil derselbe Auftrag sonst an zwei Stellen stünde —
    das Muster, das Rahmenplan-Fassung 45 als Ursache von sieben falschen
    Zuordnungen benannt hat.* R70 sagt: Manifest allein, kein Service Worker
    (Chrome auf Android verlangt seit Version 108 keinen), in P7 mit der
    Umbenennung, Name „NAdoku Web", eigenes Symbol, Nachweis am S24 und an
    einem iPhone. Gegengeprüft am 13.09.2026: kein Web-App-Manifest, kein
    Service Worker, kein `theme-color`; `apple-touch-icon` in
    `favicon_tags()` (`db.php`, je Logo-Wahl) und `ui_favicon()` (`ui.php`,
    Rückfall) — der Stand ist derselbe wie oben beschrieben, die Erhebung hat
    ihn nur bereits bewertet.

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

    **Geschlossen am 12.09.2026 ohne Beleg am Gerät.** Die wahrscheinliche
    Ursache ist gefunden und behoben: `android:roundIcon` zeigte auf dasselbe
    adaptive Symbol wie `android:icon` — ein Kategorienfehler. Eine
    Oberfläche, die `roundIcon` bevorzugt und als fertiges Bild behandelt,
    zeichnet den Vordergrund auf die volle Fläche, und das ergibt genau das
    gemeldete Bild. Das Attribut ist seit **Android 0.11.1 (04.09.2026)**
    ausgetragen, die Begründung steht ausführlich im Manifest von
    `android/handy/`. „Themed Icons" waren aus, die `<monochrome>`-Ebene
    also gar nicht im Spiel — ebenfalls am 04.09.2026 geklärt; der Satz „ob
    ‚Themed Icons' eingeschaltet sind, ist noch offen" oben war seither
    überholt. *Die Erklärung im Manifest ist eine Erklärung, kein Beweis —
    der Emulator führt AOSP und beantwortet nicht, wie One UI den
    Benachrichtigungskopf zeichnet.* **Wieder zu öffnen ist der Punkt durch
    genau einen Fund:** ein Bildschirmfoto, auf dem das Symbol unter Android
    0.11.1 oder neuer immer noch angeschnitten erscheint. Der Gerätetest am
    S24 entfällt damit als eigene Abnahme (Rahmenplan Abschnitt 6).

88. **Kachel „Einsätze je Gerät" in der Zeitraumübersicht.**
    *Aufgenommen 02.09.2026 aus der Entscheidung zu Nr. 83 (R64).* Jede
    NutzerIn sieht in ihrer Zeitraumübersicht die eigenen Einsätze nach
    Herkunft: Garmin-Uhr, Handy, Handy mit Wear-Fernbedienung, GPX-Import,
    Schnitt, von Hand — als Kachel neben den vorhandenen Kennzahlen, mit
    Modell als Untertitel, wo eines gespeichert ist. **Neue Darstellung:**
    Mockup und Freigabe nach `Design.md` 1 vor der Umsetzung; Baustein
    `ui_kennzahl` aus P3 als Ausgangspunkt. Braucht die Spalten aus R64
    (Nr. 83, S4-Rest). Zuordnung: Backlog-Runde, nach dem S4-Rest.

    **Verworfen am 12.09.2026.** *Die Daten liegen seit dem S4-Rest vor
    (R64); es fehlte allein die Darstellung, und sie wird nicht gebraucht.
    Steht hier als Entscheidung, nicht als Erledigung* — nach dem Muster von
    Nr. 71. Der Text oben bleibt stehen, damit die Vorlage da ist, falls die
    Kachel doch einmal gewollt wird. Der Verweis aus der Rahmenplan-Zeile zu
    Nr. 80 („die NutzerInnen-Sicht ist Nr. 88") ist mit der Austragung
    entfernt.

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
    organisierte Träger auftreten. Zuordnung: keine.

    **Ausgetragen am 12.09.2026 — nicht gebaut, sondern verworfen.** Der
    Punkt stand zweieinhalb Wochen unter *Offen* und trug seine Entscheidung
    im eigenen Titel: „verworfen, festgehalten". Das ist derselbe Fall wie
    **Nr. 19, 159 und 160**, die Rahmenplan-Fassung 41 aus demselben Grund
    verschoben hat — ein Eintrag, über den entschieden ist, gehört nicht in
    eine Liste offener Arbeit, egal in welche Richtung entschieden wurde.
    Er hat die Übersicht über das, was wirklich aussteht, um eine Zeile
    verfälscht, und er hätte es weiter getan.

    **Er steht hier als Entscheidung, nicht als Erledigung.** Gebaut ist
    nichts und soll nichts werden. Der Text oben bleibt vollständig stehen,
    weil er das Modell beschreibt, das man wieder aufnehmen würde, falls
    Wachen oder Verbände als organisierte Träger auftreten — dann ist dies
    die Vorlage und nicht ein leeres Blatt.

    **Aber die Vorlage passt dann nicht mehr auf das Haus, und das gehört
    dazugesagt.** Die R39-Bestandsaufnahme hat es aufgeschrieben und
    ausdrücklich verlangt, dass es *im Punkt* steht
    (`docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md`, gelöscht
    mit P5c/AP8, Historie in Git): Nr. 71
    ist der **einzige** Backlog-Punkt, der das **zurückgebaute** Modell
    fachlich voraussetzt. Regionen hängen laut R39-Text „am zentralen
    Standort und vererben über E15" — zentrale Stammdaten also. Die gibt es
    nicht mehr: **S9 hat die Tür geschlossen** (Web 18.0.0, AP5b —
    `admin_stammdaten.php` ersatzlos gestrichen), **P5 baut zurück**
    (Nr. 168). Wer diesen Punkt später aufgreift, findet die Grundlage nicht
    mehr vor und muss ihn **neu denken**, nicht nur wieder aufnehmen.

    *Die Bestandsaufnahme selbst führt Nr. 71 weiter im offenen Teil des
    Backlogs — sie ist ein datierter Stand vom 09.09.2026 und wird als
    Protokoll nicht fortgeschrieben.*

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

    **Erledigt mit Web 19.3.0 (12.09.2026, Backlog-Runde 2).** Der Knopf
    **„Jobs anhalten"** steht am Fuß der Karte „Zustand", mit einer
    Dauerwahl aus **15 Min. · 30 Min. · 1 Std. · 2 Std.** als Segmentreihe.
    Serverseitig wie beauftragt: `jobs_pause()` aus `jobs_lib.php`, derselbe
    Schlüssel `jobs_pause_bis`, dieselbe Deckelung auf `JOB_PAUSE_MAX_S` —
    **0 Zeilen** in `jobs_lib.php`, es kommt nur ein zweiter Auslöser für
    dieselbe Funktion dazu. Läuft eine Pause, steht statt des Knopfes die
    orange Meldung mit **„Pause aufheben"**; der Knopf zum Anhalten ist dann
    weg, weil es nichts anzuhalten gibt.

    **Der Eintrag oben nennt die falsche Einheit, und er hat sie nicht
    erfunden.** `php jobs.php --pause <Minuten>` stand so in der Oberfläche
    (`betrieb_jobs.php`, zweimal) — der Code rechnet in **Sekunden**
    (`jobs_pause(int $sekunden)`, und `jobs.php --hilfe` sagt es auch). Der
    Nachbarsatz derselben Karte rechnete `JOB_PAUSE_MAX_S / 3600` und kam auf
    „höchstens 2 Stunden": Das geht nur mit Sekunden auf, 7200 Minuten wären
    120 Stunden. Wer den daneben empfohlenen Befehl `--pause 60` im Glauben
    an Minuten abtippte, bekam **eine Minute** Ruhe. Beide Stellen
    berichtigt, dazu die Statusseite.

    **Zwei Funde nebenbei, beide in einer Zeile sichtbar:**

    - Die Meldung der laufenden Pause enthielt `<code>php jobs.php --pause
      0</code>` als Text — `ui_meldung_markup()` escapt ihren Text, also
      standen die spitzen Klammern auf der Seite. Der Befehl ist jetzt
      unnötig, der Knopf steht daneben.
    - Der Text zählte **drei** der sieben Jobs auf („verdichtet, ausgedünnt
      oder aufgeräumt") und ließ das geplante Komplett-Backup und den
      Mailversand weg. Harmlos, solange niemand danach entscheidet — neben
      einem Knopf ist es die Entscheidungsgrundlage. Rückfrage und Meldung
      nennen den Preis jetzt vollständig.

    **Gemessen im Browser** (angemeldet als BetreiberIn): Anhalten auf
    15 Min. → Plakette **„läuft" → „angehalten"**, `app_state.jobs_pause_bis`
    gesetzt auf **+15 Min.** (18:54 → 19:09 Ortszeit), „Pause aufheben" →
    Zeile **gelöscht**, Plakette zurück auf „läuft". Bedienhöhen **36 px** am
    Zeigergerät (1280 px) und **44 px** am Fingergerät (390 px), Knopf wie
    Segmenttasten; waagerechter Überlauf **0** in beiden, Konsolenfehler
    **0**. Gegenprobe: ein Wert außerhalb der Liste (999 999 s, im Browser
    hineingeschrieben) wird abgewiesen — Meldung „Bitte eine der angebotenen
    Dauern wählen.", **nichts geschrieben**; ein POST ohne Formular-Token
    gibt **403**, ebenfalls nichts geschrieben.

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

    **Erledigt mit Web 19.3.0 (12.09.2026, Backlog-Runde 2)** — als Verweis,
    nicht als Verlegung und nicht als Umbenennung.

    **Zuerst die Zahl, die den Punkt größer macht, als er dasteht.** Eine
    NutzerIn hat **acht** Wege für Daten hinein und hinaus: Einsatzliste
    hinaus und herein (`import.php`), Backup hinaus und herein sowie ein
    freigegebenes Konto-Backup herein (`einstellungen.php?t=backup`), GPX
    herein je Diensttag, GPX hinaus je Diensttag und je Einsatz. Auf dem
    Sammelpunkt liegen **zwei**. Der Eintrag oben liest sich wie eine
    Kleinigkeit an einer Stelle; gemessen fehlten **sechs**.

    **Und der eigentliche Befund stand nicht im Eintrag:** Die Seite trug
    nicht nur die anderen Wege nicht — sie **nannte** sie nicht einmal.
    Gemessen **0 Vorkommen von `href`** in `server/import.php`, ebenso 0 im
    Backup-Reiter. Der Eintrag fragt, „ob die Seite die anderen Wege
    wenigstens nennt", als wäre das der Rückfallplan; tatsächlich war genau
    das das Fehlende.

    **Drei Handgriffe, alle in vorhandenen Bausteinen:**

    - Ein **Untertitel** an der Titelzeile grenzt den zu weiten Namen ein
      (Vorbild `admin_sicherungen.php`, B-S8-08).
    - Eine **zugeklappte Karte „Was hier gilt"** als letzte der Seite nennt
      in vier Absätzen die übrigen Wege *mit Begründung*, warum sie dort und
      nicht hier liegen. Diese Karte stand auf **9** Seiten der Anwendung,
      jetzt auf **10**. Die Adresse des Backups wird aus
      `ui_einstellungen_punkte()` gelesen, nicht abgeschrieben — das war der
      Fehler, den Nr. 151 zwei Tage vorher behoben hat.
    - Der **Rückweg**: Der Backup-Reiter nennt jetzt `import.php`. Vorher
      zeigte kein Verweis in eine der beiden Richtungen.

    **Nicht gemacht, und warum nicht:**

    - *Umbenennen in „Einsatzliste"* (der Vorschlag des Eintrags) würde den
      Export verstecken. „Einsatzliste" sagt nicht, dass man dort eine Datei
      herausbekommt, und der Menüpunkt ist der einzige Ort dafür. Der Name
      ginge von zu weit auf zu eng.
    - *Den GPX-Import auf die Sammelseite holen* verstößt gegen R74 Regel 1
      und 2, verdoppelte entweder das Dialog-Markup oder zerlegte
      `schneiden.js`, und bräuchte eine Weiterleitung zurück in die
      Tagesansicht — ein neuer Weg durch die Anwendung, also Konzeptarbeit,
      für ein schlechteres Ergebnis.

    **Gemessen nach der Änderung** (Browser, angemeldet, 1280 px): Verweise
    im Seiteninhalt **0 → 4**, Karten **4 → 5**, die neue Karte ist ein
    `<details>` und steht **zu**; waagerechter Überlauf **0** auch
    aufgeklappt, Konsolenfehler **0**. Alle drei Ziele geben **200**. Am
    Konto ohne Diensttag (`admin@gen-em.org`, 0 Diensttage) führt der
    GPS-Verweis auf „Noch keine Daten" — deshalb hängt der Satz am Diensttag
    und verspricht kein Menü.

    **Zwei Funde aus derselben Ecke gleich mitgenommen:** Das Handbuch
    führte im Menüblock Einstellungen noch **„Rettungsmittel"**, den es seit
    Web 19.0.0 nicht mehr gibt (S9/AP5) — ausgetragen und durch die
    Geschichte der zwei Umbenennungen ersetzt. Und zwei Codekommentare
    (`index.php`, `gpx.php`) nannten „Spuren als GPX"; der Eintrag heißt seit
    S9 „GPS-Daten als GPX".

120. **Eine Testmail aus der Oberfläche senden.**
    *Aufgenommen 05.09.2026 aus dem S8-Konzept (E-S8-16).* Die Statusseite
    (S8 AP4) zeigt für E-Mail nur, ob SMTP **eingerichtet** ist — ob eine
    Zustellung tatsächlich funktioniert, weiß sie nicht, und ob die letzte
    Zustellung aufgezeichnet wird, war beim Bau zu prüfen. Eine Warnmail, die
    nie ankommt, fällt damit erst auf, wenn jemand sie vermisst. **Zu tun:**
    ein Knopf „Testmail an mich" auf der Statusseite, der über den regulären
    Versandweg geht und das Ergebnis in derselben Zeile zeigt. **Neue
    Funktion**, deshalb nicht Teil von S8. Zuordnung: Backlog-Runde.

    **Erledigt mit Web 19.3.0 (12.09.2026, Backlog-Runde 2).**

    **Der Eintrag ist an einer Stelle eine Fassung hinterher.** „Ob die letzte
    Zustellung aufgezeichnet wird, war beim Bau zu prüfen" — sie wird
    aufgezeichnet, und zwar **seit Web 15.3.0**: `smtp_send()` ruft
    `smtp_versand_vermerken()` in beiden Ausgängen, die Marken `smtp_last`
    und `smtp_last_ok` stehen in `app_state`, und die Zeile „Letzter Versand"
    zeichnet daraus Zeitpunkt, Alter und Ampel. Damit war die halbe Aufgabe
    schon gebaut; offen war allein der **Auslöser**.

    **Die eigentliche Frage war keine technische.** Die Statusseite sagt an
    vier Stellen schriftlich zu, nichts zu ändern — Dateikopf, Unterzeile,
    Karte „Was hier gilt", Handbuch 12.1 — „mit genau einer Ausnahme". Ein
    Testmail-Knopf macht daraus zwei. **Freigegeben am 12.09.2026** mit der
    Begründung, dass beide Ausnahmen keinen Bestand ändern, sondern an Ort
    und Stelle prüfen, und dass es für SMTP überhaupt keine zuständige Seite
    gibt: Der Zugang steht allein in der `config.php`. Alle vier Stellen sind
    mitgeschrieben.

    **Gebaut als Kopfaktion der Karte „E-Mail"**, mit dem neuen Zeichen
    `mail.svg` (Tabler „mail", MIT) — dem **53.** des Vorrats. Der Weg dahin
    war nicht gerade: Zuerst war „Kopfaktion ohne Symbol" vorgesehen. Die
    Gegenprobe hat zwei Messungen dagegengestellt, und beide halten: Eine
    Kopfaktion kennt nur `blau` und `orange` (`.karte-aktion-blau` /
    `-orange`) — `neutral` gäbe eine **Klasse ohne Regel**, also einen
    ungestalteten Knopf, und zwar ohne jede Fehlermeldung, weil die
    Vollständigkeitsprüfung zur Laufzeit zusammengesetzte Klassen nicht sieht.
    Und **alle elf** vorhandenen Kopfaktionen tragen ein Symbol; eine
    textnackte wäre die erste gewesen und damit eine neue Darstellung. Das
    Zeichen ist deshalb der kleinere Eingriff.

    **Vier Dinge, die der Bau gebraucht hat und ohne die er falsch wäre:**

    - `ratelimit_lib.php` muss `betrieb_status.php` **selbst nachladen** —
      weder `auth_guard.php` noch `status_lib.php` tun es. Ohne die Zeile
      gäbe es einen Fatal Error, und zwar erst beim ersten Klick.
    - Der POST-Zweig steht **vor** `status_karten()`, sonst zeigte die Zeile
      „Letzter Versand" den Stand von vor dem Klick.
    - **Erst `smtp_eingerichtet()`, dann versuchen.** `smtp_send()` prüft das
      nicht selbst. Ohne die Vorprüfung machte ein Klick auf einer
      Installation ohne Mailserver aus „nicht eingerichtet" (neutral) ein
      „fehlgeschlagen" (rot, zählt mit).
    - Ratenschutz `testmail` (3 je Stunde, Konto UND IP) und `$zeitlimit = 5`
      statt der Vorgabe 15.

    **Gemessen im Browser** gegen einen SMTP-Auffänger auf dem Prüfstand
    (implizites TLS, eigenes Zertifikat im Vertrauensspeicher — `smtp.php`
    prüft die Gegenstelle):

    - **Erfolg:** Zeile „Letzter Versand" **„kein Versand"/neutral →
      „zugestellt"/blau**, `app_state.smtp_last_ok = 1`, `smtp_last` auf die
      Minute; die Nachricht liegt vollständig im Auffänger (Betreff, To, Body).
      Meldung *in der Karte*, nicht oben neben der Zusammenfassung.
    - **Kein SMTP** (Host in der `config.php` geleert): Meldung „es wurde
      nichts versucht", `app_state` **leer**, Ratenzähler **nicht** verbraucht,
      Zeilen bleiben neutral. Der rote Punkt, den es nicht gibt, entsteht nicht.
    - **Ratenschutz:** der 4. Versuch innerhalb einer Stunde wird abgewiesen
      („Bis 20:17 Uhr geht keine mehr hinaus"); zwei Zeilen in `rate_limits`
      (`ip:127.0.0.1` und `id:1`).
    - **POST ohne Formular-Token: 403**, nichts geschrieben.
    - Bedienhöhen **36 px** (Zeiger, 1280 px) und **44 px** (Finger, 390 px),
      waagerechter Überlauf **0**, Konsolenfehler **0** in beiden.

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

    **Erledigt mit Web 19.3.0 (12.09.2026, Backlog-Runde 2).** `.zweispalter`
    ist gestrichen; `admin_installation.php` trägt `.form-raster` mit zwei
    `.form-spalte`-Kindern wie die fünf anderen Seiten.

    **Zwei Angaben des Eintrags oben stimmen nicht:**

    - *„`.form-raster` steht auf sechs Seiten."* Es waren **fünf**
      (`betrieb_status`, `betrieb_statistik`, `admin_sicherungen`,
      `admin_user`, `einsatz_form`). Mit der Installationsseite sind es
      jetzt sechs.
    - *„Die berechneten Werte sind identisch."* Fast. `.zweispalter` setzte
      `gap: var(--abstand-4)` für **beide** Richtungen, `.form-raster` setzt
      `gap: 0 var(--abstand-4)` — der **Zeilenabstand** ist null.
      Gemessen: rowGap **16 px → 0 px** ab 1200 px. Es fällt nicht auf,
      solange ein Raster genau zwei Kinder in einer Zeile hat, und das ist
      auf allen sechs Seiten so — deshalb **0 abweichende Rechtecke**. Aber
      „identisch" war das nie, und wer es beim nächsten Mal nachschlägt,
      soll den Unterschied kennen.

    **Die Wahl fiel auf `.form-raster`, obwohl der Name schlechter ist.**
    Drei der fünf Seiten sind keine Formulare, und „Zweispalter" sagt, was
    die Klasse tut. Aber: `.form-raster` behalten kostet **1** CSS-Zeile und
    **3** Markup-Zeilen; `.zweispalter` behalten kostete 5 Seiten,
    10 Umbenennungen von `.form-spalte`, zwei Stellen in `assets/menue.js`,
    einen Streichlisteneintrag und die Design.md-Tabelle. Fünf gegen eins
    schlägt den besseren Namen.

    **Die Kinder tragen `.form-spalte`, und das ist nicht kosmetisch.** Die
    Klasse hat **keine** CSS-Regel (nachgezählt: 0 Treffer im Stylesheet) —
    aber `assets/menue.js` liest sie, um in der Leiste je *Spalte* die
    oberste sichtbare Karte zu markieren. Die Hülle nur umzubenennen und die
    Kinder blank zu lassen wäre keine Vereinheitlichung gewesen, sondern
    dieselbe Sonderstellung unter fremdem Namen. **Die eine sichtbare Folge:**
    Die Leiste markiert auf dieser Seite ab 1200 px jetzt **zwei**
    Unterpunkte statt einem („Logo" und „Impressum") — genau wie auf den
    fünf anderen Seiten.

    **Gemessen:**

    - **Kaskadenvergleich:** Regeln **743 → 742**; entfallen **4** (die vier
      Deklarationen von `.zweispalter`), neu **0**, anderer Endwert **0**,
      vertauschte Paare bei gleicher Spezifität **0**.
    - **Berechnete Stile in Chromium**, 13 Breiten: **48 165**
      Elementmessungen über `seiten.html`, `katalog.html` und
      `js_markup.html`, **0 Abweichungen**, 166 Eigenschaften je Element;
      dazu die Pseudoprobe gegen die umgeschriebenen Stylesheets:
      **20 059** Messungen, **0 Abweichungen**.
    - **Geometrie der Seite selbst** (angemeldet, 1100 / 1280 / 1920 px,
      je 90 Nachkommen des Rasters): **0 abweichende Rechtecke**, Kinder
      identisch, `display` block/grid unverändert, Überlauf **0**.
    - **Vollständigkeit 334 → 334**, Wortliste **0/0**.

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

    **Erledigt mit Web 19.3.0 (12.09.2026, Backlog-Runde 2).** Die Seite trug
    **0 Verweise** (gemessen am gerenderten Markup, 913 Bytes); jetzt trägt
    sie einen: den Knopf **„Zur Verwaltung"** auf `betrieb_updates.php`,
    gemessene Höhe **44 px** — die Bedienhöhe, ohne neue Regel.

    **Zwei Annahmen des Eintrags oben waren falsch, und beide in der Sache:**

    - *„Sie kann die Rolle nicht kennen."* Für den Aufrufer in `db.php`
      stimmt das. Es gibt aber einen **zweiten**: `login.php` zeigt dieselbe
      Seite für Konten **ohne** Verwaltungsrecht — dort ist die Rolle bekannt
      und bekannt unzureichend. Genau dort steht der Knopf jetzt **nicht**
      (`wartung_antwort_seite(false)`), denn er führte garantiert auf ein 403.
    - *„Sieh dir das eingebettete CSS an."* Es gibt keines. Die Seite
      **verlinkt** das vollständige Stylesheet — deshalb tragen `.knopf` und
      `.knopf-neutral` hier wie überall, und es brauchte weder eine neue Regel
      noch ein Mockup.

    **Fällig geworden ist der Punkt erst durch Nr. 171** — er ist die zweite
    Hälfte derselben Reparatur: Bis Web 19.1.2 ließ sich das Anmeldeformular
    im Wartungsmodus gar nicht abschicken; wer nicht hereinkam, stand auch
    nicht vor der Sackgasse.

    **Kein `ui_knopf()`:** `wartung_lib.php` lädt nichts — kein `db.php`, kein
    `ui.php` (Eigenschaft 2 ihres Dateikopfs). Der Knopf ist reines Markup.
    **Nicht gebaut:** der Balken mit Zeitpunkt und schaltendem Konto — das
    wäre auf einer Seite, die jeder Besucher sieht, ein Namensleck.
    Wartungsprobe **53 → 55 Erwartungen** (19a neu, 18 um den Parameter
    erweitert).

155. **Die Fixture des Referenzbestands trägt die alte Rundenzahl.**
    *Aufgenommen 07.09.2026 aus der Gegenprüfung des Sofortpakets (Nr. 136).*
    `demo/fixture.json.gz` führt das Demo-Konto mit 320 000 Runden; der
    Demo-Reset spielt es alle 30 Minuten so ein, und die stille Anhebung
    überspringt das Demo-Konto ohnehin (`api/kdf_upgrade.php`, E-P1-19 —
    ein Upgrade passte bis zum nächsten Reset nicht mehr zu den
    öffentlichen Zugangsdaten). Folge: Der Altwert kann nie aus
    `KDF_ITER_LISTE` gestrichen werden, und die Statuszeile
    „Schlüsselableitung" sagt das seit der Nachbesserung ausdrücklich (das
    Demo-Konto zählt dort nicht mehr als „Übergang läuft"). Behebung: den
    Referenzbestand mit `KDF_ITER_ZIEL` neu bauen
    (`tools/referenzdatensatz/`), und `erzeugen.php` soll abbrechen, wenn
    das Demo-Konto nicht auf dem Zielwert steht — damit die Zusage in
    `api/kdf_upgrade.php` eine geprüfte ist. Zuordnung: Backlog-Runde, vor
    dem Streichen des Altwerts.

    **Teilweise erledigt am 12.09.2026 (Backlog-Runde) — die zwei Riegel
    stehen, der Neubau nicht.** Gebaut sind die beiden Stellen, die den
    Fehler überhaupt bemerkbar machen:
    `tools/referenzdatensatz/fixture/erzeugen.php` bricht ab, wenn das
    Demo-Konto nicht auf `KDF_ITER_ZIEL` steht (sonst bleibt die Zusage in
    `api/kdf_upgrade.php` eine unbelegte), und `server/demo_lib.php` weist
    eine Fixture ab, deren Rundenzahl diese Fassung gar nicht mehr anbietet
    — geprüft gegen `KDF_ITER_LISTE`, damit eine ältere, aber bediente
    Fixture weiterläuft. Ohne den zweiten Riegel wäre ein Reset **still
    erfolgreich** und niemand käme mehr herein.

    **Gemessen am 12.09.2026:** `KDF_ITER_ZIEL` 600 000, Liste
    [600 000, 320 000], Fixture **320 000**. Riegel 2 lässt sie durch
    (richtig — der Wert wird noch bedient), Riegel 1 wiese eine
    Neuerzeugung aus dem heutigen Demo-Konto ab (richtig). Nach einem
    vollständigen Neuaufbau steht `admin@gen-em.org` auf **600 000**,
    `demo@gen-em.org` weiterhin auf **320 000** — die Fixture bringt den
    Wert mit.

    **Offen bleiben zwei Schritte, und sie gehören getrennt:**
    *(a)* Demo-Konto anheben und die Fixture neu erzeugen — der Neubau ist
    unvermeidlich, weil die Anhebung drei der neun `konto`-Felder tauscht.
    *(b)* **320 000 aus `KDF_ITER_LISTE` streichen — eigenes Paket, eigene
    Version.** Das ist mindestens eine Nebenstufe. Der Weg ist am Code
    nachgesehen: `auth_salt.php` schickt die **Liste** an den Browser
    (Zeile 91 bzw. 123), der rechnet für **jeden** Wert darin ein Token
    (`login.php:365-368`), und der Server greift den heraus, der zur
    gespeicherten `kdf_iter` des Kontos gehört (`login.php:155-157`). Fehlt
    der Wert in der Liste, entsteht das Token nie, `$token` bleibt leer, und
    **jedes Konto, das noch auf 320 000 steht, kommt nicht mehr herein**.

    *Wie schlimm genau:* **nicht unwiderruflich** — Passwort und Hash
    bleiben unberührt, und der Wert wieder in die Liste zu setzen stellt den
    Zugang her. Aber das ist eine **Code-Änderung samt Deploy**, kein
    Handgriff in der Verwaltung. Für die Betroffenen ist die Sperre so lange
    vollständig. Deshalb: erst alle Konten anheben (Statuszeile
    „Schlüsselableitung" zeigt, wer noch aussteht), dann streichen — und
    nicht als Anhang an (a).

    *Der Text oben nennt `erzeugen.php` ohne Pfad; die Datei liegt unter
    `tools/referenzdatensatz/fixture/`, und daneben gibt es ein völlig
    anderes `generator/erzeugen.py`.*

    **Vollständig erledigt am 12.09.2026 — Web 19.2.0.** Der Neubau ist über
    die **drei Läufe** aus `tools/referenzdatensatz/LIESMICH.md` gefahren,
    nicht über eine Abkürzung: Quelldaten prüfen (99 Marken, keine offene
    Matrixzeile), erzeugen (283 989 Einzelprüfungen, keine Befunde),
    einspielen über die regulären Wege (386 Anfragen), CSV-Einsätze im
    Browser, dann `fixture/erzeugen.php`. **Die neue Fixture trägt 600 000**
    und dieselben Zahlen wie die alte: 16 Diensttage, 88 Einsätze, 2 Geräte,
    55 861 Spurpunkte, Papierkorb 5/1/5.

    **Danach ist 320 000 aus `KDF_ITER_LISTE` entfallen** (Schritt b, den der
    Eintrag oben in ein eigenes Paket verwiesen hat — er ist es geworden).
    Das Tor aus dem Kommentar zu `KDF_ITER_LISTE` ist gefahren:
    `SELECT COUNT(*) FROM users WHERE kdf_iter = 320000` → **0**, auf dem
    Prüfstand wie auf der Produktivinstallation (Statuszeile
    „Schlüsselableitung", bestätigt vom Auftraggeber). Gemessen im Browser,
    Median aus je drei Anmeldungen: **1580 → 1369 ms**, also rund 210 ms je
    Anmeldung; der Salz-Endpunkt liefert jetzt `[600000]` statt zweier Werte,
    und die Anmeldung mit anschließendem Entsperren zeigt weiter **83
    Einsätze**.

    **Zwei Anläufe waren nötig, und der erste ist lehrreich:** Ich habe den
    Neubau zuerst auf eine Installation gesetzt, die bereits Demo-Daten trug.
    `einspielen.py` hält seinen Zustand in `lauf.json` und hielt die Stufen
    für erledigt — `ingest` sendete **0 Anfragen**, und `zuordnen` scheiterte
    danach an einem Diensttag, den es nie gab. Der Weg ist: erst wischen
    (`lokal_einrichten.sh`), dann das Demo-Konto samt `app_state`-Marker
    entfernen, `lauf.json` leeren, **dann** die Stufen. Steht so jetzt nicht
    im LIESMICH — es beschreibt nur den Fall der leeren Installation.

    **S10 legt einen zweiten Riegel derselben Art daneben** (Konzept S10,
    E-S10-06 und E-S10-15, 13.09.2026). Ab S10 hängt der Datenschlüssel am
    **Server-Anteil** aus `config.php`, und Hüllen tragen dann die Kennung
    `edka1:<kennung>:`. Das **Demo-Konto bleibt ausgenommen**: Es bekommt
    keinen Anteil ausgeliefert (`ANTEIL_STAND = 'demo'`), und seine Hülle
    bleibt `edk1:` — aus demselben Grund wie hier die Rundenzahl: Die Fixture
    muss auf **jeder** Installation aufgehen, und eine Hülle, die am Anteil
    dieser einen Installation hängt, täte das nicht.
    `tools/referenzdatensatz/fixture/erzeugen.php` prüft es künftig genauso,
    wie es heute die Rundenzahl prüft — ein Abbruch statt einer Fixture, die
    erst beim nächsten Reset auf einer fremden Installation auffällt.

    **Gebaut in S10/AP5 (14.09.2026) — und zwar als PAAR, wie hier.** Der
    Riegel im Erzeuger allein wäre die halbe Sache gewesen: Er läuft auf der
    Referenzmaschine, die Datei kommt auf dem Produktivserver an. Seit
    **Web 20.2.1** prüft deshalb auch `demo_fixture_laden()` beide Hüllen, mit
    der gemeinsamen Prüfschicht (`huelle_pw_pruefen($wrap, istDemo: true)` und
    `huelle_rc_pruefen()`) statt mit einem eigenen Ausdruck. Die Begründung
    ist wörtlich die dieses Eintrags: *Ohne den zweiten Riegel wäre ein Reset
    still erfolgreich und niemand käme mehr herein.*

    Damit trägt jeder der beiden installationsgebundenen Werte der Fixture ein
    Riegelpaar; die Tafel steht in `docs/Technik.md` 4.99a. Gemessen von
    `tools/referenzdatensatz/fixture/riegelprobe.php`: **10 von 10**, beide
    Riegel in beide Richtungen — denn ein Riegel, der immer zuschlägt, ist so
    kaputt wie einer, der es nie tut — und dazu die Zusage, die das `throw`
    vertretbar macht: Ein Reset mit verbogener Fixture wird abgefangen, das
    Demo-Konto behält seine 88 Einsätze.

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

    **Erledigt mit Web 19.1.2 (12.09.2026, Backlog-Runde).** Gemessen mit
    fünf offenen Punkten (in einer zurückgerollten Transaktion hergestellt):
    alt **1,316 ms / 6 Abfragen**, neu **0,566 ms / 2 Abfragen**, beide Wege
    dieselben Zahlen (3 Diensttage, 2 Stammdatensätze, Summe 5).
    **Der größere Posten stand in diesem Punkt gar nicht:** `nb_moeglich()`
    fragte das Schema je Tabelle einzeln — **1,405 → 0,320 ms** je
    Seitenaufbau. Der Text oben ist an zwei Stellen älter als der Code und
    war beim Austragen zu berichtigen: `vehicles` steht seit Web 16.0.0
    bewusst **nicht** in `NB_NOTNULL` (E-S9-09), und es waren **vier**
    `information_schema`-Abfragen, nicht eine. Die Bedingung „Diensttag
    offen" liegt jetzt in `nb_tage_bedingung()`, damit Liste und Zahl nicht
    auseinanderlaufen können.
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

    **Erledigt mit Web 19.1.2 (12.09.2026, Backlog-Runde).** Gemessen auf
    derselben Maschine, Median aus je 15 Läufen: alt **231,9 gegen 58,2 ms**
    (Abstand 173,7 ms, Faktor 3,98), neu **232,5 gegen 232,7 ms** (0,1 ms,
    0,06 %). **Die neue Zahl allein hätte nicht gereicht** — sie fällt beim
    nächsten Vorgabesprung wieder heraus und kehrt das Leck auf PHP 8.1–8.3
    sogar um. Deshalb prüft `login.php` den Wert jetzt mit
    `password_needs_rehash()` (gemessen 0,0000 ms über 2000 Läufe) und
    rechnet im Bedarfsfall ein `password_hash()`. **Beides zusammen wäre
    falsch:** 465,5 ms, also 234 ms *langsamer* als der Gegenzweig — das
    steht als Warnung am Code. Der Text oben nannte `auth_salt.php` als
    zweiten Verwender; der rechnet überhaupt kein bcrypt (HMAC-Pseudosalt,
    eigene Mindestdauer). Genau ein Verwender: `login.php:161`.
151. **Nach einem Import führt „Ersten Tag öffnen" auf den falschen Diensttag.**
    *Aufgenommen 06.09.2026 aus der Korrekturstufe zu Nr. 148, gefunden vom
    neuen `tools/linkprobe/` an seinem ersten Tag.* `import_ui.js:751` baut
    den Verweis `index.php?day=<Kalendertag>`; `index.php:25` liest
    `$_GET['d']` und erwartet dort eine **Kennung**. Der Kommentar darüber
    sagt es selbst: „NICHT mehr ein Datum: Seit E9 können mehrere Diensttage
    auf einem Kalendertag liegen, ein Datum bestimmt also keinen Tag mehr."
    Zweimal falsch also — der Parametername **und** die Form des Werts.
    **Anders als Nr. 148 scheitert das still:** `index.php` fällt auf
    `dt_neuester()` zurück und zeigt den jüngsten Tag. Wer nach einem Import
    auf den Verweis klickt, landet auf einer plausibel aussehenden Seite, die
    nicht die versprochene ist — und merkt es nur, wenn der importierte Tag
    zufällig nicht der jüngste ist.
    **Zu tun:** `api/import_commit.php` liefert die Tageskennung mit — sie
    liegt dort in `$dayIdByDate[$tag]` bereits vor —, `import_ui.js` verweist
    auf `index.php?d=<Kennung>`. Danach die Zeile aus der Tabelle „Bekannte
    Abweichungen" in `tools/linkprobe/ausnahmen.md` **entfernen**; eine tote
    Zeile dort macht den Lauf rot, und das ist Absicht. Nicht in der
    Korrekturstufe zu Nr. 148/149 mitbehoben, weil die Behebung die
    Import-Schnittstelle berührt und damit mehr ist als ein Name (K4).
    Zuordnung: **Backlog-Runde**.

    **Erledigt mit Web 19.1.2 (12.09.2026, Backlog-Runde).**
    `api/import_commit.php` liefert `first_day_id` (die Kennung);
    `first_day` ist **ersatzlos entfallen** — es hatte genau einen
    Verbraucher, und ein Schlüssel ohne Leser ist die nächste Falle.
    Die Zeile in `tools/linkprobe/ausnahmen.md` ist gestrichen; die Probe
    meldet **116 Verweise, 0 Abweichungen, 0 Ausnahmen, 0 tote Zeilen**
    (vorher 1 bekannte, wiedergefunden). Der Backlog sagte, die Kennung
    liege in `$dayIdByDate[$tag]` bereits vor — das stimmt nur **innerhalb**
    der Einsatzschleife; an der Stelle, an der die Antwort entsteht, ist
    `$tag` nicht mehr im Zugriff.
153. **`querySelector` mit einem Wert aus dem URL-Fragment.**
    *Aufgenommen 07.09.2026 aus Nr. 135 (Krypto-Review K-15), beim Abschluss
    des Sofortpakets herausgelöst.* `suche.php` setzt einen Wert aus dem
    URL-Fragment unmaskiert in einen `querySelector` ein. Kein XSS — der Wert
    landet nicht im Markup —, aber ein Zeichen wie `"` oder `]` bricht die
    Auswahl, und die Seite verhält sich dann anders, als der geteilte Link
    verspricht. Behebung: über `CSS.escape()` oder den Wert vor der Auswahl
    gegen eine Positivliste halten. Die Nummer steht getrennt, weil Nr. 135
    mit Web 15.6.0 nach *Erledigt* gewandert ist und dieser Teil sonst
    unsichtbar würde. Zuordnung: Backlog-Runde.

    **Erledigt mit Web 19.1.2 (12.09.2026, Backlog-Runde) — und die Folge
    war größer als hier beschrieben.** Nicht die Auswahl brach, sondern der
    **Seitenaufbau**: `fragmentLesen()` steht außerhalb des `try`, die
    Ausnahme riss die async-IIFE ab, und danach fehlten Trefferliste und
    Filterzahl; die Freitextsuche griff bis zum Neuladen nicht mehr.
    Gemessen mit zehn Probewerten gegen beide Stände: alt **4 Ausnahmen,
    6/10 mit Trefferliste**, neu **0 Ausnahmen, 10/10** — bei unverändertem
    Verhalten für die gemeinten Werte (6 / 77 / 83 Treffer). Behoben **nicht**
    mit `CSS.escape()`: Die drei Werte stehen als Radios da und lassen sich
    vergleichen; ein Selektor wird gar nicht gebraucht.
156. **Das Prüfstand-Passwort `adminlokal2026` fällt durch die Passwortregel.**
    *Aufgenommen 07.09.2026 aus der Nachbesserung zu Nr. 136.* Seit die
    Sperrliste jeden Eintrag streicht, auch „admin" mit fünf Zeichen, bleibt
    von `adminlokal2026` nur „lokal" (5) — abgewiesen. Betroffen ist allein
    der lokale Prüfstand: `tools/referenzdatensatz/einspielen/lokal_einrichten.sh`
    setzt das Passwort über `passwort_setzen.mjs` durch das Browserformular
    und bricht dort ab; `einspielen.py` (`--admin-passwort`), `kreislauf.py`,
    `demo_bremse.mjs`, `demo_pruefen.mjs`, `komplettprobe/klickweg.mjs`,
    `screenshots/aufnehmen.mjs` und `messstand/messen.py` melden sich damit
    an. `nadokudemo0815` und `umlaufpruefung2026` bleiben gültig. Eine
    bereits eingerichtete Installation ist nicht betroffen — geprüft wird
    beim Setzen, nicht beim Anmelden. Behebung: ein neues Prüfstand-Passwort
    ohne Listenwort wählen und an allen genannten Stellen samt
    `einspielen/LIESMICH.md` und `lokal_einrichten.sh` (Kopfkommentar)
    eintragen; **nicht** die Regel für den Prüfstand lockern. Zuordnung:
    Backlog-Runde, vor dem nächsten Neuaufbau eines Prüfstands.

    **Erledigt am 12.09.2026 (Backlog-Runde) — und „bricht dort ab" war
    falsch; der wahre Fall ist schlimmer.** `lokal_einrichten.sh` rief
    `node passwort_setzen.mjs … | sed`; `/bin/sh` ist dash, und der
    Rückgabewert einer Rohrleitung ist der des **letzten** Glieds. Das
    `set -e` griff nicht: Das Skript lief weiter und druckte am Ende
    Zugangsdaten, **die es nie gesetzt hatte** — ein stiller Durchlauf mit
    falscher Erfolgsmeldung. `set -o pipefail` gibt es in dash nicht;
    Schritt 6 läuft jetzt ohne Rohrleitung, über eine Protokolldatei, mit
    ausdrücklichem Abbruch und Ursachenhinweis. Dazu nennt
    `passwort_setzen.mjs` den **Grund** aus `#state`, statt 30 Sekunden in
    seine Zeitgrenze zu laufen.

    Neues Passwort **`pruefstandzugang2026`**, am Regelcode nachgerechnet
    (Node-`vm` über `pwquality.js`): `adminlokal2026` **abgewiesen**,
    `pruefstandzugang2026` **erlaubt, Stärke 3**; `nadokudemo0815` (1) und
    `umlaufpruefung2026` (2) bleiben gültig. Ersetzt an **11 Stellen in 10
    Dateien** unter `tools/` — die 8 Treffer in `docs/` bleiben, sie sind
    Protokolle von einem Datum. Belegt durch zwei vollständige Neuaufbauten:
    mit dem neuen Passwort **Rückgabewert 0**, 88 Einsätze / 16 Diensttage /
    2 Geräte; mit dem alten **Rückgabewert 1** und der Meldung der Seite im
    Protokoll (vorher: Rückgabewert 0 und falsche Zugangsdaten).
    `tools/klickprobe/probe.mjs` und `tools/containeraufbau/aufbau.sh`
    fehlten in der Liste oben.
167. **Löschen eines Standorts hinterlässt verwaiste Vorbelegungen.**
    *Aufgenommen 09.09.2026 bei der Bestandsaufnahme zu R39.*
    `user_defaults` trägt bewusst **keinen Fremdschlüssel auf `item_id`** —
    die Spalte zeigt je nach `kind` auf `bases.id` oder `vehicles.id`, und
    zwei Zieltabellen lassen keinen zu (`schema.sql:196-208`). Beide
    Löschwege räumen darum von Hand ab, was sie kennen: die Vorbelegung des
    **Standorts** (`einstellungen.php`, `base_del`) und die des einzeln
    gelöschten **Rettungsmittels** (`einstellungen.php`, `veh_del`). Nicht
    abgeräumt
    werden die Vorbelegungen der Rettungsmittel, die mit dem Standort
    **kaskadieren** — und das sind beim Löschen eines Standorts alle.
    Gemessen an der lokalen Anlage in einer zurückgerollten Transaktion:
    Rettungsmittel fort (0 Zeilen), Vorbelegung steht noch (1 Zeile).
    Die Wirkung ist still: `dt_standardwerte()` liefert eine tote Kennung,
    das Auswahlfeld findet dazu nichts und belegt nichts vor — es sieht aus
    wie „keine Vorbelegung gesetzt", und niemand kann die Zeile loswerden.
    Behebung: Im `base_del`-Weg vor dem Löschen des Standorts auch
    `DELETE FROM user_defaults WHERE kind = "vehicle" AND item_id IN
    (SELECT id FROM vehicles WHERE base_id = ?)` — innerhalb derselben
    Transaktion, in der der Standort fällt. Seit Web 17.1.0 löst
    `stammdaten_standort_loesen()` die Rettungsmittel ohne Standortpflicht
    vorher heraus; deren Vorbelegung muss **bleiben**, die Abfrage läuft
    also nach dem Lösen. Nicht dringend, aber ein Rest, der sich mit jedem
    gelöschten Standort vermehrt. *(Nachtrag 09.09.2026, Web 18.0.0: Es gibt
    nur noch EINEN Löschweg — `admin_stammdaten.php` ist mit S9/AP5b
    gestrichen. Der Befund bleibt derselbe, die Behebung ist damit halb so
    groß.)*

    **Erledigt mit Web 19.1.2 (12.09.2026, Backlog-Runde).** Gemessen am
    laufenden Stand, derselbe Bedienweg über die Oberfläche gegen beide
    Fassungen: **verwaiste Vorbelegung alt 1, neu 0** — bei sonst gleichen
    Zahlen (Rettungsmittel und Standort in beiden Fällen fort). Die Stelle
    im Ablauf steht als Kommentar am Code: zwischen
    `stammdaten_standort_loesen()` und `DELETE FROM bases` ist das einzige
    Fenster. Der Haupttext oben sagte „beide Löschwege" — es gibt seit
    Web 18.0.0 nur einen; der Nachtrag vom 09.09.2026 sagte es bereits
    richtig. **Ein Aufräumlauf für den Altbestand ist nicht gebaut:** Er
    bräuchte eine Migration, und der Bestand ist nach Lage der Dinge klein.
171. **Im Wartungsmodus kommt niemand mehr herein — auch die BetreiberIn nicht.**
    *Aufgenommen und behoben am 12.09.2026 (Web 19.1.2), gefunden beim
    Aufklären von Nr. 97.* `login.php` steht seit jeher in
    `WARTUNG_AUSNAHMEN`, ausdrücklich „damit eine abgemeldete
    Administratorin hineinkommt". Die Seite kam auch — mit Balken und
    Formular. Abschicken ließ sie sich trotzdem nicht: Der Browser holt
    vorher Salt und Rundenzahlen aus **`auth_salt.php`**, und ohne sie
    leitet er kein Token ab. Dieser Endpunkt stand **nicht** in der Liste,
    lädt aber `db.php` und liegt nicht unter `/api/` — er bekam also die
    HTML-Wartungsseite mit 503, und die Anmeldeseite schrieb „Anmeldung
    derzeit nicht möglich. Bitte später erneut."

    **Der einzige Ausweg war SSH oder FTP** — also genau die Lage, die die
    Ausnahmeliste verhindern soll. Und `docs/Handbuch.md` 12.3 versprach den
    Weg, den es nicht gab: „Die **Anmeldeseite funktioniert weiter**. Melde
    dich mit einem BetreiberIn-Konto an."

    **Die Wartungsprobe meldete dazu grün.** Erwartung 10 lautete
    „`login.php` → 200 mit Balken **und** Formular" — ein Formular, das nicht
    abgeschickt werden kann, erfüllt das. `auth_salt` kam in der Probe **null
    mal** vor. Das ist der Fall aus `CLAUDE.md` 6: eine grüne Zahl, die nicht
    benennt, was sie gemessen hat; dasselbe Muster wie F-S8-P-04, nur eine
    Ebene tiefer — im Nebenaufruf statt in der Seite.

    **Behoben:** `auth_salt.php` in `WARTUNG_AUSNAHMEN`, mit derselben
    Begründung, die dort bei `login.php` steht. Das Risiko — eine Abfrage auf
    `users` während einer laufenden Migration — ist kein neues: `login.php`
    liest dieselbe Tabelle und steht seit jeher in der Liste. **Und die Probe
    misst es jetzt:** neue Erwartung **10a** (`auth_salt.php` → 200 mit JSON
    und `salt`), Erwartungen **51 → 53**, Ausnahmeliste elf → **zwölf**
    Einträge. Gemessen: 53 Erwartungen, 0 nicht erfüllt.

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

    **Erledigt mit Web 19.1.2 (12.09.2026, Backlog-Runde) — und der Eintrag
    oben stimmte in vier von sechs Aussagen nicht mehr.** Er bleibt stehen,
    weil er das Protokoll ist; hier steht, was der Code dazu sagt:

    - **`ortsfeld.js` und `ortswahl.js` gehören gar nicht dazu.** Beide
      enthalten heute kein `fetch()` mehr (seit Web 15.7.0 läuft alles über
      `EdGeocoder`) — und am Tag der Aufnahme fetchten sie **Photon**, also
      einen fremden Dienst. Der Wartungsmodus hat diese Anfragen nie
      gesehen und konnte nie ein 503 darauf geben. Die Begründung des
      Eintrags („wer während einer Wartung eine Adresse sucht") beschreibt
      einen Vorgang, den es nicht gibt.
    - **`unlock.js` zeigt nicht „seine allgemeine Meldung", sondern gar
      nichts** — an allen drei Stellen, ausdrücklich begründet („Bewusst
      still"). Für eine Hintergrundanhebung ist das richtig, nicht falsch.
    - **`export.js` ist in sich uneinheitlich:** `fetchMeta()` zeigte den
      Text, `fetchTrack()` verwarf die Antwort und meldete „Serverfehler
      beim Laden der Tracks (503)". Das stand nirgends.
    - **Acht Aufrufstellen in sechs PHP-Seiten fehlten in der Liste** —
      darunter die Startseite, also die Seite, die im Reiter offen steht,
      wenn jemand die Wartung einschaltet.

    **Gezählt nach der Behebung:** 20 Aufrufstellen, davon 2 an einen
    Dritten (Adresssuche) → **18 treffen das Tor**. **13 zeigen den Text**,
    **5 schweigen bewusst** (Hintergrund- und Komfortwege, wo eine Meldung
    falsch wäre, nicht fehlend). Geändert wurden genau zwei Zeilen —
    `export.js` (`fetchTrack` liest jetzt den Rumpf wie `fetchMeta`) und
    `kopplung.js` (Wartung ist keine Störung: sofort aussteigen statt nach
    drei Takten „Die Verbindung zum Server ist gerade gestört" zu sagen,
    was inhaltlich falsch ist — die Verbindung steht ja).

    **Kein gemeinsamer Baustein gebaut, und das mit Absicht.** Von 18
    Stellen waren 11 schon richtig und 5 sollen schweigen; ein Helfer hätte
    2 Stellen bedient und 18 anfassen müssen. Wenn später doch einer
    entsteht, ist `assets/html.js` das Muster.

19. **`$title` in `einsatz_loeschen.php` wird nie gelesen.** Die Variable wird
    gesetzt, der Titel steht daneben als Literal. Gefunden in P0 (dort F-06).
    Einzeiler, aber bewusst nicht nebenbei erledigt: Er stand nicht auf der
    Freigabeliste.

    **Überholt — festgestellt am 12.09.2026 beim Sichten der Backlog-Runde.**
    Es gibt in `server/einsatz_loeschen.php` **kein `$title` mehr**, und auch
    keinen Titel als Literal daneben: Die Seite setzt ihren Titel über
    `ui_seite_start(['titel' => 'Einsatz löschen'])` (Zeile 30), wie jede
    andere seit P3. Gemessen: `grep -c '\$title' server/einsatz_loeschen.php`
    → **0**; der Schlüssel des Bausteins heißt `titel`, nicht `title`. Der
    Punkt hat sich mit dem
    Umbau auf die gemeinsamen Bausteine von selbst erledigt und ist nur nie
    ausgetragen worden — festgehalten, weil ein Backlog, der behobene Dinge
    weiterführt, seine eigene Glaubwürdigkeit kostet.

159. **Die Uhr behandelt `400` nicht vertragsgemäß — sie wiederholt endlos.**
    *Aufgenommen 08.09.2026 aus der Gegenprüfung der Zeitregel (Nr. 134).*
    `docs/JSON-Vertrag.md` sagt für `400 {"error":"payload"}`: „nicht
    wiederholen, lokal als fehlerhaft markieren". `watch/source/Uploader.mc`
    tut das Gegenteil: Bei jedem Code außer Erfolg setzt es nur `lastError`
    und `_busy = false` — „später erneut (nächster syncAll-Auslöser)". Ein
    Paket, das der Server dauerhaft ablehnt, blockiert damit die
    Warteschlange, und zwar ohne Ende. Heute fällt das nicht auf, weil
    `ingest.php` fast nie `400` antwortet; genau deshalb ist in dieser Runde
    die Zeitprüfung auch **nicht** als Abweisung gebaut worden, sondern als
    Verwerfen des Werts. Die Handy-App macht es richtig
    (`Sendeantwort.kt`), räumt abgewiesene Pakete aber nach 30 Tagen weg —
    ohne Bedienweg zum Nachreichen (Nr. 114). Behebung: In `Uploader.mc`
    `400` von den übrigen Fehlern trennen, das Paket lokal als fehlerhaft
    kennzeichnen und aus der Warteschlange nehmen; die Uhr zeigt es an.
    Zuordnung: nächste Uhr-Stufe. **Vor jeder künftigen Änderung, die
    `ingest.php` einen neuen `400`-Fall gibt, zuerst dieser Punkt.**

    **Erledigt am 08.09.2026** mit **Uhr 3.1.0** — und mit anderem Zuschnitt,
    als hier stand. `400` war der falsche Fokus: Die Uhr kann ihn kaum
    auslösen. Bedienbar erreichbar sind `401` und `403` (Gerät im Web
    gelöscht oder abgeschaltet), und sie sagen nichts über das Paket,
    sondern über das Gerät — dort wird deshalb nichts geparkt, sondern das
    Senden angehalten. Der schwerere Teil des Fundes war ohnehin ein
    anderer: Weil ein Rückstand das Trennen sperrte, war die Uhr nach einer
    dauerhaften Ablehnung nur noch durch Löschen der App zu retten. Das ist
    behoben; geparkte Pakete zählen nicht mehr im Rückstand.

    *(Ausgetragen am 12.09.2026: Der Punkt trug seine Erledigung seit dem
    08.09.2026 im eigenen Text, stand aber weiter unter „Offen“ —
    gefunden beim Abgleich von `Rahmenplan.md` Abschnitt 5 gegen diese
    Liste. `CLAUDE.md` 2.4 sagt: erledigte Punkte werden verschoben,
    nicht nur vermerkt.)*

160. **Ein fortgesetzter Dienst führt das Handy tagelang unter dem alten
    Datum — und die Anzeige verrät es nicht.** *Aufgenommen 08.09.2026 aus
    derselben Gegenprüfung.* `Dienstklammer.beginnen()` gibt bei laufendem
    Dienst den vorhandenen zurück (E-R45-13, gewollt). Wer den Dienst am
    Freitag nicht beendet und am Montag „Dienst beginnen" drückt, arbeitet
    im Freitagsdienst weiter; jedes Paket trägt weiter `day` = Freitag.
    Die Anzeige sagt „Dienst läuft seit 07:00" — **ohne Datum**, also nicht
    von heute Morgen zu unterscheiden. Für den Server ist das seit dieser
    Runde unschädlich (`day` ist Anzeigedatum, und Zeiten außerhalb des
    Fensters schreiben den Diensttag nur nicht fort), für die Dokumentation
    des Dienstes ist es falsch. Behebung: Läuft der Dienst länger als einen
    Kalendertag, das Datum in der Anzeige mitführen und beim zweiten
    „Dienst beginnen" ausdrücklich fragen, ob fortgesetzt oder neu begonnen
    wird. Zuordnung: nächste Android-Stufe.

    **Erledigt am 08.09.2026** mit **Android 0.15.0** — allerdings anders als
    hier vorgeschlagen. Die Anzeige führt das Datum, sobald der Dienst an
    einem anderen Kalendertag begann, und nach 26 Stunden erinnert die App
    einmal daran, ihn zu beenden. Die Rückfrage beim zweiten „Dienst
    beginnen" ist **nicht** gebaut: Den Startknopf gibt es bei laufendem
    Dienst gar nicht, die Frage müsste an die Uhr, und wer am Montag einfach
    weiterarbeitet, drückt ohnehin nichts. Was offen bleibt, ist das
    Löschen der schon hochgeladenen Aufzeichnung — Nr. 161.

    *(Ausgetragen am 12.09.2026: Der Punkt trug seine Erledigung seit dem
    08.09.2026 im eigenen Text, stand aber weiter unter „Offen“ —
    gefunden beim Abgleich von `Rahmenplan.md` Abschnitt 5 gegen diese
    Liste. `CLAUDE.md` 2.4 sagt: erledigte Punkte werden verschoben,
    nicht nur vermerkt.)*

44. **Sprungliste bei Standorten mit vielen Rettungsmitteln.**
    *Aufgenommen 30.08.2026.* Ein Standort mit neun Rettungsmitteln zwingt zum
    Scrollen, um den zu finden, den man sucht. Vorschlag: eine Zeile runder
    Marken direkt unter der Überschrift „Rettungsmittel", die zum Eintrag
    springen — erst ab sechs Einträgen, darunter sieht man die Liste ohnehin
    ganz.

    Mockup liegt: `docs/mockups/N1-sprungliste.html` mit Bildern für 900 und
    390 px. **Wartet auf Freigabe** — es wäre eine neue Darstellung, und die
    braucht nach `docs/Design.md` 1 eine ausdrückliche Zustimmung.
    *Konzept S9 (07.09.2026): E-S9-14, M-S9-05 (Pille mit Artzeichen, ab sechs; in der Standortseite, AP5).*

    **Erledigt mit Web 16.3.0 am 08.09.2026 (S9/AP5 Teil 3, E-S9-14).** Die Sprungliste erscheint ab sechs Rettungsmitteln. Gemessen: bei 5 keine Liste, bei 6 eine mit 6 Pillen, 6 Artzeichen, 6 gültigen Zielen, Pillenhöhe 36 px.

69. **Kurzname je Rettungsmittel als Stammdatenfeld.**
    *Zulieferung aus P3; bis Fassung 16 ohne Nummer im Rahmenplan-Abschnitt
    P4 geführt.* Leiste, Kacheln und Plaketten zeigen den vollen Namen des
    Rettungsmittels; auf schmalen Breiten bricht er um oder wird
    abgeschnitten. Ein Kurzname (etwa „RTH 1", „NEF 2") als eigenes
    Stammdatenfeld würde an diesen drei Stellen verwendet, der volle Name
    bleibt in Formularen und Exporten. Schemaänderung, Feldkatalog, Export,
    Import und Backup ziehen nach — deshalb ein eigener Punkt und kein
    Nebenklapp. Zuordnung: Backlog-Runde.
    *Konzept S9 (07.09.2026): E-S9-09 (`vehicles.kurz`, `days.vehicle_kurz`, Nutzlast 10; AP4).*
    *Umgesetzt mit Web 16.0.0 (S9/AP4, 07.09.2026): `vehicles.kurz` und
    `days.vehicle_kurz`, bis 16 Zeichen, freiwillig. Die Leiste zeigt ihn,
    Tooltip und Formulare den vollen Namen; Sicherung (Nutzlast 10) und
    Export (`diensttage.csv`, Spalte am Ende) führen ihn NEBEN der
    Bezeichnung mit, die Suche findet beides. **Kacheln und Plaketten
    zeigen ihn noch nicht** — sie zeigen heute gar keinen
    Rettungsmittelnamen (`EdMissionTable.kachel()`), es gibt dort nichts
    zu ersetzen; das wäre eine neue Darstellung und braucht ein Mockup.
    Der Punkt bleibt deshalb offen und wandert erst mit AP8 nach
    Erledigt — oder wird dort auf diesen Rest zurückgeschnitten.*
    *Entschieden am 08.09.2026 nach Mockup M-S9-08: **zurückgeschnitten.**
    Kacheln und Plaketten bekommen den Kurznamen **nicht** — beide nennen
    heute gar kein Rettungsmittel, und die zwei gezeigten Varianten kosten
    mehr, als sie einbringen (Kachel 92 → 124 px, oder eine neue
    Darstellung). Dafür zeigt die Leiste ihn künftig in **jeder** Breite
    (Variante 2: Kurzname sichtbar, Akkordeon 8 px eingerückt) — Nachtrag
    AP4a. Mit dessen Auslieferung ist der Punkt erledigt und wandert im
    Abschluss (AP8) hinüber.*
    *Gebaut mit Web 16.1.0 (S9/AP4a, 08.09.2026): Der Nebentext der Leiste
    bleibt im Band 1024–1199 px stehen, wenn ein Kurzname eingefroren ist
    (`.eintrag-neben.kurz`); das Akkordeon rückt dort je Ebene 8 statt 12 px
    ein — seit Web 16.1.1 je Ebene 4 px, dazu 4 px Abstand in der Zeile
    (Freigabe M-S9-11, Weg 2). Gemessen an der laufenden Anwendung: dem Kurznamen stehen im Band **64 bis 79 px** zur Verfügung (vorher 48 bis 63) — „BW Hoch" (55 px) und „NEF 76/1" (53) stehen damit an **jedem** Datum ganz; gemessen **13 Kurznamen, 0 Ellipsen** (vorher 10).
    Der Punkt ist sachlich erledigt; er wandert
    mit AP8 nach* Erledigt.

    **Erledigt mit Web 16.0.0 am 07.09.2026 (S9/AP4, E-S9-08).** `vehicles.kurz` (16 Zeichen) und der eingefrorene `days.vehicle_kurz`. Der Kurzname steht in Kachel, Plakettenzeile und Leiste; im Band 1024–1199 px ist er das Einzige, was von der Unterzeile bleibt — gemessen 13 Kurznamen, 0 Ellipsen.

108. **Schloss-Icon und Legende für verschlüsselte Felder.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-8.1), Schritt 8
    (S9).*
    Es ist nicht ersichtlich, welche Felder verschlüsselt gespeichert werden.
    Soll: Schloss-Icon am Feld plus Legende (F13). Getrennt von Nr. 109.
    *Konzept S9 (07.09.2026): E-S9-02 (Schloss am Label, Karte „Was hier gilt"; AP7).*

    **Erledigt mit Web 19.1.0 am 10.09.2026 (S9/AP7, E-S9-02).** Das Schloss steht an jedem verschlüsselten Feld des Formulars, die zugeklappte Karte „Was hier gilt“ am Ende erklärt es in drei Sätzen. Gemessen: 8 Schlösser, 9 Kleinzeilen, **0 Felder mit beidem**. Kein neuer Baustein — `docs/Design.md` neu erzeugt und unverändert.

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
    *Konzept S9 (07.09.2026): E-S9-01 (Notizen im pat_blob, Katalogschlüssel `store => pat`, stille Anhebung; AP7).*

    **Erledigt mit Web 19.0.0 bis 19.0.3 am 10.09.2026 (S9/AP7, E-S9-01).** `missions.notes` liegt als Schlüssel `notes` im `pat_blob`; die Suche findet die Notiz nach dem Entsperren wie die Diagnose. Gemessen: gesperrt 0 von 83, entsperrt 1 von 83, Klartextwort gesperrt weiter 31 Treffer. **Der Grund stand vorher nirgends:** `api/suchindex.php` lieferte jede Notiz im Klartext für den gesamten aktiven Bestand, ohne dass jemand entsperrt haben musste (31 Schlüssel je Einsatz vorher, 30 nachher).

111. **Neue Rettungsmittel-Arten.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-10.1), Schritt 8
    (S9).*
    Bergwachtnotarzt, Veranstaltungsnotarzt, Sonstiges — mit eigenem Icon,
    ohne Rollen-Vorlagen, ein Standort kann eingegeben werden (F16).
    Migration.
    *Konzept S9 (07.09.2026): E-S9-09, E-S9-13 (`vehicles.typ`, Betriebsart bleibt `kind`; AP4).*

    **Erledigt mit Web 16.0.0 am 07.09.2026 (S9/AP4, E-S9-09).** `vehicles.typ` mit vier Werten (Standard, Bergwacht, Veranstaltung, Sonstiges); die Betriebsart folgt dem Typ, der Standort ist außerhalb von „Standard“ freiwillig. Prüfschicht 8 von 8 Fällen wie festgelegt.

112. **Rettungsmittel ohne Stammdateneintrag in der Tageszuordnung.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-10.2), Schritt 8
    (S9).*
    Ein Rettungsmittel kann in der Tageszuordnung manuell definiert werden;
    es gilt nur für den Tag, die dauerhafte Aufnahme in den Stamm bleibt
    manuell über die Einstellungen (F17). Bedingung: Suche und Filter müssen
    für solche Einträge greifen.
    *Konzept S9 (07.09.2026): E-S9-10 („Anderes Rettungsmittel …" im Zuordnungsformular; AP6).*
    *Gebaut mit Web 18.1.0 (S9/AP6, 09.09.2026): Der letzte Eintrag der Auswahl
    klappt Bezeichnung, Typ mit Betriebsart und einen Standort auf, der Auswahl
    und Freitext zugleich ist. Gespeichert wird nur in der Momentaufnahme des
    Tages — `vehicle_id` bleibt NULL. **Die Bedingung ist gemessen:** Der Name
    steht in der Tagesliste, die aus der Momentaufnahme kommt und nicht aus den
    Stammdaten (Klickprobe `ap6-adhoc-speichern-und-finden`). Nach *Erledigt*
    wandert der Punkt mit dem Abschluss von S9 (AP8).*

    **Erledigt mit Web 18.1.0 am 09.09.2026 (S9/AP6, E-S9-10).** „Anderes Rettungsmittel …“ am Diensttag: `vehicle_id` bleibt NULL, Bezeichnung, Typ, Betriebsart und Standort stehen in der Momentaufnahme, **kein Stammdatensatz entsteht**. Suche, Filter und Tagesliste finden ihn trotzdem.

113. **Rollen unmittelbar nach der Auswahl bearbeitbar.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-10.3), Schritt 8
    (S9).*
    Heute muss erst gespeichert und erneut „bearbeiten" geklickt werden,
    bevor Rollen editierbar sind. Soll: sofort bearbeitbar; sind Rollen für
    das Rettungsmittel vordefiniert, werden sie nach der Auswahl automatisch
    nachgeladen. Für manuell definierte Rettungsmittel (Nr. 112) und Arten
    ohne Vorlagen (Nr. 111) entfällt die Rollenbearbeitung (F19).
    *Konzept S9 (07.09.2026): E-S9-11 (`api/day.php?vorschau=`; AP6).*
    *Gebaut mit Web 18.1.0 (S9/AP6, 09.09.2026): `api/day.php?vorschau=` liefert
    Rollensatz und Vorlagen zu einer noch nicht gespeicherten Wahl und schreibt
    nichts; das Formular zeichnet die Felder bei `change` neu. Gemessen über
    sechs Rettungsmittel: Rollenzahlen **5/3/0/3/0/0**, die Zuordnung in der
    Datenbank danach unverändert. Getippte Namen bleiben stehen, wo die Rolle
    bleibt. Nach *Erledigt* mit dem Abschluss von S9 (AP8).*

    **Erledigt mit Web 18.1.0 am 09.09.2026 (S9/AP6, E-S9-11).** `api/day.php?vorschau=<vehicle_id>` beantwortet die Frage für eine noch nicht gespeicherte Wahl und **schreibt nichts**; die Rollenfelder erscheinen sofort. Gemessen: Rollenzahlen 5/3/0/3/0/0 über sechs Rettungsmittel, der Diensttag in der Datenbank dabei unverändert.

132. **Klartext-Freitextfelder ohne Hinweis.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-12).* `notes` trägt den
    Placeholder „Freitext (keine Patientendaten!)", `bw_info` („Namen /
    Infos"), die Besatzungs-Freitexte und `days.notes` nicht
    (`mission_fields.php:395,426,459`). Bedienfehler tragen Patientendaten
    in den Klartext. Ein Schlüssel `hinweis` im Feldkatalog, ein Text für
    alle; das Symbol dazu bringt Nr. 108. Zuordnung: Sofortpaket Sicherheit.
    *Konzept S9 (07.09.2026): **ganz nach S9** (E-S9-02, AP7) — `hinweis` an `bw_info`, `other_ema`, `crew_*` und `days.notes`; nicht an `notes`, das wird verschlüsselt (E-S9-01). Zuordnung jetzt: S9.*
    *Umgesetzt 10.09.2026 in S9/AP7 (Web 19.1.0): Der Schlüssel `hinweis` steht
    im Katalog, der Satz „Klartext — keine Patientendaten" einmal als Variable;
    getragen wird er von `bw_info`, `other_ema`, den sieben Besatzungsfeldern
    und — außerhalb des Katalogs — vom Notizfeld des Diensttags. `notes` trägt
    stattdessen das Schloss. **Gemessen: 9 Kleinzeilen, 8 Schlösser, 0 Felder
    mit beidem.** Der Punkt wandert mit dem Abschluss von S9 (AP8) nach
    Erledigt — hier steht er, damit dazwischen niemand zweimal anfängt.*

    **Erledigt mit Web 19.1.0 am 10.09.2026 (S9/AP7, E-S9-02).** Der Katalogschlüssel `hinweis` trägt den einen Satz „Klartext — keine Patientendaten“ an `bw_info`, `other_ema` und den sieben Besatzungsfeldern; das Notizfeld des Diensttags trägt ihn außerhalb des Katalogs. `notes` trägt ihn **nicht** — es ist seit Web 19 verschlüsselt und trägt das Schloss. Gemessen: 9 Kleinzeilen, 0 Widersprüche.

152. **Standortseiten: Standort zuerst, ein Menüpunkt, Kennzahlen, Dialoge, Landung auf der neuen Zeile.**
    *Aufgenommen 07.09.2026 vom Auftraggeber bei der Freigabe der S9-Mockups
    (Rahmenplan Fassung 34).* Zwei Menüpunkte für eine Sache: „Standorte"
    (Name, Lage) und „Rettungsmittel" (je Standort eine zugeklappte Karte
    mit drei Listen — Rettungsmittel, Besatzungsvorlagen, Zielkliniken; alle
    drei hängen am Standort, `crew_presets.base_id`,
    `transport_dests.base_id`, E15). Der Name des zweiten ist falsch, die
    zugeklappten Karten sind leicht zu übersehen, lange Listen zwingen zum
    Scrollen, und die Eingabe (`sd_form()`) klebt unter jeder Liste.
    **Soll:** „Rettungsmittel" entfällt; **„Standorte"** ist Liste (eine
    Zeile je Standort mit drei Zahlen, „Ohne Standort" als letzter Eintrag)
    und **Seite je Standort** mit **sechs** Karten (Standort, Rettungsmittel,
    Besatzung, Zielkliniken, Weitere Rettungsmittel, Bergwacht — letztere nur
    mit luftgebundenem Rettungsmittel, E29, sonst fünf; hier stand bis zum
    08.09.2026 „vier", und das war falsch: Am Standort hängen sechs
    Stammdatenlisten, und die zwei übergangenen wären ohne Karte von der
    Seite aus nicht erreichbar — Entscheidung des Auftraggebers, Frage 7 des
    Prüfdokuments), **Kennzahlen** als Inhaltsverzeichnis
    (`Design.md` 9.10), „Zum Anfang" je Karte, Sprungliste (Nr. 44) in den
    Rettungsmitteln, **Filterfeld** in Besatzung und Zielkliniken; Anlegen
    und Bearbeiten im **Dialog** (9.11); nach dem Anlegen Redirect mit
    `#veh-<id>` und `:target`-Hervorhebung der neuen Zeile, keine
    zusätzliche Meldung. Verwaltung → Stammdaten ebenso. Datenmodell und
    Formate unberührt; Handbuch 6 neu. Konzept: **E-S9-18, E-S9-19**, Mockups
    M-S9-06, M-S9-07. Zuordnung: **S9**, AP5.

---

    **Erledigt mit Web 16.2.0 bis 17.1.1 am 08./09.09.2026 (S9/AP5, E-S9-18 und E-S9-19).** Standort zuerst: ein Menüpunkt, eine Liste, je Standort eine Seite mit sechs Karten; Anlegen und Bearbeiten im Dialog mit Landung auf der neuen Zeile. Gemessen: 5 Dialoge im Markup, davon sichtbar 0; nach dem Öffnen genau einer.

166. **~~Der Referenzbestand kennt keinen systemweiten Standort~~ —
    ZURÜCKGEZOGEN am 09.09.2026, am Tag der Aufnahme.** *Aufgenommen in
    S9/AP5-4 mit dem Vorschlag, dem Generator des Referenzbestands **einen
    systemweiten Standort hinzuzufügen**, damit der Bilderlauf die
    Standortseite der Verwaltung fotografieren kann.* Der Vorschlag ist
    falsch: **Rahmenplan R39** (Beschluss vom 30.08.2026) schafft die
    zentralen Stammdaten ab und baut sie in **P5** zurück; auf dem
    Produktivsystem sind sie am 09.09.2026 bereits gelöscht. Einen Bestand
    aufzubauen, damit ein Prüfmittel eine Seite fotografieren kann, die
    zurückgebaut wird, ist Arbeit in die falsche Richtung.
    **Was bleibt:** Die Standortseite der Verwaltung ist nicht zu
    fotografieren, und das ist ab jetzt ein **Zustand und kein Mangel** —
    der Bilderlauf meldet ihn mit Grund („8× Platzhalter
    `__ADMIN_STANDORT__` nicht auflösbar"), und der Eintrag
    `42a-stammdaten-standortseite` in `tools/screenshots/seiten.json` fällt
    mit dem Rückbau weg. Die Klickprobe deckt die Seite bis dahin ab
    (`ap5-verwaltung-besatzung-anlegen` stellt den Fall selbst her).
    *Diese Nummer bleibt stehen und wird nicht gelöscht — sie ist der
    Beleg dafür, dass der Vorschlag geprüft und verworfen wurde.*

163. **Systemweite Besatzungs-Vorbelegungen ließen sich nicht anlegen.**
    *Nachtrag 09.09.2026 (Web 18.0.0, S9/AP5b): **gegenstandslos** —
    `admin_stammdaten.php` ist ersatzlos gestrichen (Rahmenplan R39, Nr. 168).
    Der Punkt bleibt hier stehen, weil er behoben WAR, bevor die Seite fiel;
    der Klickprobenweg dazu misst dieselbe Zusage jetzt an der Kontoseite
    (`ap5-besatzung-anlegen`).*
    *Aufgenommen und behoben 09.09.2026 in S9/AP5-4, Web 17.0.0.* Das
    Formular in `admin_stammdaten.php` schickte den Schlüssel `role_code`,
    der Schreibweg `crew_save` derselben Datei las `role`. Damit war die
    geprüfte Rolle **immer leer**, die Bedingung
    `!array_key_exists($role, CREW_ROLES)` schlug jedes Mal an, und die
    Verwaltung meldete „Bitte Rolle und Namen angeben." — bei ausgefüllter
    Rolle und ausgefülltem Namen. Anlegen **und** Ändern einer systemweiten
    Vorbelegung waren damit seit **Web 9.10.0** unmöglich, also rund zwei
    Jahre. Die Kontoansicht war nie betroffen: Sie schickt seit jeher `role`.
    Gefunden beim Umbau auf die Dialoge, nicht von einem Prüfmittel — kein
    Bild zeigt eine Fehlermeldung, die erst nach einem Klick erscheint, und
    die Klickprobe fuhr diesen Weg bis dahin nicht. Behoben, indem der
    gemeinsame Dialog `role` schickt (ein Name für eine Sache) und der
    Schreibweg die Rolle beim Ändern **mitschreibt** — als Feld kann sie sich
    jetzt ändern. Der Weg `ap5-verwaltung-besatzung-anlegen` misst ihn: Er
    legt einen systemweiten Standort samt Rettungsmittel an, legt die
    Vorbelegung an, prüft die Landung auf `#crew-<id>` und räumt alles wieder
    ab.

164. **Ein Umbenennen auf einen vorhandenen Namen endete in einer weißen Seite.**
    *Aufgenommen und behoben 09.09.2026 in S9/AP5-4, Web 17.0.0.* Beim
    **Anlegen** fängt `INSERT IGNORE` die Dublette ab; beim **Ändern** gibt es
    kein Gegenstück, und der Eindeutigkeitsschlüssel
    (`uq_user_base_role_name` und Geschwister) warf eine PDOException, die
    niemand fing. Betroffen waren Besatzung, weitere Rettungsmittel,
    Bergwacht und Zielkliniken der Kontoansicht — seit es diese Listen gibt.
    Selten getroffen, solange nur der Name änderbar war; mit der Rolle als
    Feld im Dialog wurde daraus ein wahrscheinlicher Fall. Behoben mit einer
    gemeinsamen Schließung `$sdAendern()`, die `ist_dublettenfehler()`
    auswertet und dieselbe Meldung gibt wie der Anlegen-Weg.

165. **Ein leerer Standortname wurde wortlos verworfen.**
    *Aufgenommen und behoben 09.09.2026 in S9/AP5-4, Web 17.0.0.*
    `base_save` in `einstellungen.php` prüfte `if ($n !== '')` — **ohne**
    Gegenzweig. Wer das Namensfeld leerte und absendete, sah die Seite neu
    geladen, keinen neuen Standort und keine Meldung. Das `required` im
    Markup fängt den Regelfall ab; es ist eine Bequemlichkeit und keine
    Prüfung. Behoben: „Bitte einen Namen eintragen." Dieselbe Bauart von
    Fehler wie F-S9-U-17 bis -19 aus AP5-2 — ein Weg, der nichts tut und
    nichts sagt.

162. **Der kleine Punkt des Abfahrtorts hat keinen Kasten und zeigt seine Farbe nie.**
    *Aufgenommen 07.09.2026 in S9/AP3, gefunden bei der Aufklärung zu Nr. 72.*
    `.geo-punkt` (`assets/geo.js`, `markerPunkt()`) setzt `width` und
    `height` an einem `<span>` — und an einem nicht ersetzten Inline-Element
    wirken beide **nicht**. Gemessen in der Tagesübersicht am 07.09.2026:
    **4 × 18 px statt 12 × 12**; übrig blieben die beiden 2-px-Ränder als
    weißer Strich, und die Spurfarbe des Einsatzes (`background`, aus
    `EdGeo.spurFarbe()`) lag in einem 0 px breiten Inhaltskasten und war nie
    zu sehen. Betroffen: `index.php` und `einsatz.php`, je der manuelle
    Abfahrtort der Luftlinie. **Derselbe Fehler wie Nr. 72**, drei Zeilen
    darüber im Stylesheet, und in keinem Backlog-Punkt.

    **Erledigt mit Web 15.9.0 am 07.09.2026 (S9/AP3).** Eine Zeile:
    `display:block`. Gemessen nachher **12 × 12 px**, Fläche
    `rgb(31, 78, 156)` — die Spurfarbe steht. Der Weg
    `ap3-geo-punkt-sichtbar` der Klickprobe misst es.

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

    **Erledigt mit Web 15.9.0 am 07.09.2026 (S9/AP3, E-S9-12).** Der Verdacht
    im Punkt stimmte, und er war die ganze Ursache: `.geo-pfeil` ist ein
    `<span>`, das Stylesheet setzte daran nur die Farbe — und `transform`
    wirkt an einem nicht ersetzten Inline-Element **nicht**. Die
    Winkelrechnung in `geo.js` war die ganze Zeit richtig; sie kam nur nie an.

    **Gemessen, nicht geschlossen.** Eine Kontrollprobe zeigt es: Ein
    20-px-Kasten mit `rotate(45deg)` misst **28,3 px** (= 20·√2), wenn die
    Drehung greift, und **20 px**, wenn nicht — gemessen waren 20. Die
    Bildschirmmatrix des SVG lautete `a=0,833 b=0 c=0 d=0,833` bei
    behaupteten 90 Grad, also reine Skalierung ohne Drehanteil. Nachher
    treffen **12 von 12 Pfeilen** in 30-Grad-Schritten ihren Sollwinkel auf
    **0,1 Grad** genau, und auf der Spur des Referenzeinsatzes **2 von 2**.

    **Der berechnete Stil verrät den Fehler nicht** — `getComputedStyle`
    meldet die Drehmatrix auch am Inline-Element. Wer ihn in den
    Entwicklerwerkzeugen prüft, bekommt „sieht richtig aus" zurück; nachweisbar
    ist er nur an der Geometrie. Der Weg `ap3-pfeile-drehen` der Klickprobe
    misst seither die Bildschirmmatrix.

    *Der Weg:* `.geo-pfeil` bekommt einen ausdrücklichen Kasten
    (`display:flex` mit `--symbol`), nicht `inline-block` — bei einem
    Inline-Block hinge der Drehpunkt an der Zeilenhöhe der Karte und
    verschöbe sich bei der nächsten Schriftänderung. Die zweite im Konzept
    zugelassene Möglichkeit („die Drehung wandert in das SVG") ist verworfen:
    `pfeil-hoch.svg` ist zugleich die Sortierrichtung von sieben
    Tabellenköpfen, sechs davon über `.symbol-oben` gedreht — ein
    Winkelparameter an `edSymbol()` stellte eine zweite Drehmechanik neben
    die vorhandene.

103. **Kompaktere Buttons Einsatzort, Standort, Zielklinik.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-3), Schritt 8 (S9).*
    Die drei Buttons sollen kleiner werden; Prüfidee: die farbige Umrandung
    der Icons von Standort und Zielklinik als Anzeige Einsatzbeginn/-ende
    nutzen und die separate Anzeige sparen — ob das gestalterisch trägt, ist
    offen; Icon-Größe separat justierbar. Liefergegenstand sind Mockups
    mehrerer Optionen **im S9-Konzept** (Fable-Schritt, F8). Hängt an der
    Bedienhöhe am Schreibtisch (Nr. 74, S8). Offen: F3–F6 (Rahmenplan
    Abschnitt 6).
    *Konzept S9 (07.09.2026): E-S9-12, M-S9-01 V1 (Farbring statt Rand, 30/28/3 px; AP3).*

    **Erledigt mit Web 15.9.0 am 07.09.2026 (S9/AP3, E-S9-12).** Variante V1
    des Mockups M-S9-01, freigegeben am 06.09.2026: **Der Farbring ist jetzt
    der Rand** und liegt nicht mehr darum herum. Nachgemessen im Browser
    (Klickprobe `ap3-schildmasse`): **32 px** ohne Aufzeichnung, 32 mit Start
    oder Ende, **38** mit beidem — vorher 36, 48 und 60. Einsatzort-Kreis
    32 → **28**, Ringpunkt 16 → **14**, mit beidem 28 → **20**. Symbol im
    Schild 20 → **18**, im Kreis 20 → **16**.

    Außen liegt am Schild immer **1 px Schnee** als Trennlinie zur Karte (F6);
    der Einsatzort-Kreis bekommt sie nicht, weil Orange auf keiner der drei
    Kartenebenen vorkommt und die freigegebene Maßleiste 28 px nennt.

    Zwei Dinge fielen dabei auf und sind mit behoben: Ein **beringtes Schild
    hatte keinen Schlagschatten** (die Ringregeln überschrieben `box-shadow`
    vollständig), und der **Ringpunkt ist antippbar** — er öffnet ein Popup.
    E-S9-12 nennt im selben Absatz 24 px als Untergrenze am Finger
    (WCAG 2.5.8), setzt ihn aber auf 14. Beides zugleich geht nur so: Die
    Zeichnung bleibt 14 px und sitzt in einer durchsichtigen 24-px-Fläche.
    Vorher waren es 16 px, also ebenfalls darunter — der Fehler ist älter als
    dieses Paket.

104. **Windenkacheln fehlen bei Nullwert.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-4), Schritt 8 (S9).*
    In Monats- und Jahresansicht fehlen die Windenkacheln, wenn im Zeitraum
    keine Windeneinsätze geflogen wurden. Soll: Sobald ein Hubschrauber mit
    Winde als Einsatzmittel ausgewählt war, erscheinen die Kacheln — auch
    mit „0" (F7).
    *Konzept S9 (07.09.2026): E-S9-04 (Fähigkeit statt Zählung, `api/range.php` liefert `faehigkeiten`; AP3).*

    **Erledigt mit Web 15.9.0 am 07.09.2026 (S9/AP3, E-S9-04).** Kehrt
    E30/A13d ausdrücklich um. `api/range.php` liefert jetzt `faehigkeiten`,
    und die beiden Windenkacheln stehen, sobald ein **Luft**-Diensttag des
    Zeitraums die Winde trägt — auch mit dem Wert 0. Gemessen mit der
    Klickprobe (`ap3-windenkacheln-nach-faehigkeit`), alle drei Fälle über die
    Oberfläche hergestellt und danach zurückgestellt: Januar mit Windeneinsatz
    **2 Kacheln**; Januar **ohne** Windeneinsatz, Fähigkeit steht: **2
    Kacheln mit „0 Winden-Cycles" und „0,0 Ø Winden-Cycles / Flugtag"**;
    November ohne Fähigkeit: **0 Kacheln**.

    **Die Einschränkung auf Luft ist kein Beiwerk.** Die Migration
    `2026_08_17_notarzt_erweiterung` hat seinerzeit jedem bestehenden
    Diensttag beide Fähigkeiten gegeben, ohne nach der Art zu fragen. Auf
    einem gewachsenen Bestand trägt deshalb auch ein NEF-Tag von 2025 die
    Winde — ohne diese Bedingung stünden die Kacheln überall, und die
    Änderung sähe richtig aus, während sie nur die Altlast zeigte.
    Boden und Gemischt sind unberührt, wie das Konzept es verlangt.

105. **Hubschrauber-Icon in der linken Leiste.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-5), Schritt 8 (S9).*
    Das Icon neben den Tagesdaten überzeugt nicht; Varianten entstehen im
    S9-Konzept (Fable-Schritt, F8), nicht vorab.
    *Konzept S9 (07.09.2026): E-S9-13, M-S9-02 — Hubschrauber bleibt Tabler „helicopter"; erledigt sich mit „Ist".*

    **Erledigt mit Web 15.9.0 am 07.09.2026 (S9/AP3, E-S9-13).** Mockup
    M-S9-02, freigegeben am 06.09.2026: **Der Hubschrauber bleibt.** Die
    eigene Strichzeichnung (Variante B) und die Bildmarken (C, C2) sind
    gesehen und verworfen; Tabler „helicopter" und „ambulance" bleiben, wie
    sie sind. Der Punkt ist damit mit „Ist" beantwortet — was er verlangte,
    war die Prüfung, nicht der Wechsel.

    Neu sind statt dessen die **Zeichen der Diensttag-Typen**:
    `bergwacht.svg` (Tabler „mountain"), `veranstaltung.svg`
    („building-stadium"), `sonstiges.svg` („dots-circle-horizontal").
    Symbolvorrat **49 → 52**. `dt_art_symbol()` nimmt den Typ schon entgegen
    und stellt ihn **vor** die Betriebsart — ein Bergwacht-Dienst trägt den
    Berg, gleich ob er fliegt oder fährt; der Tooltip nennt beides
    („Bergwacht, luftgebunden"). Im Datenmodell gibt es den Typ noch nicht,
    er kommt mit AP4; bis dahin ist der Parameter immer `null` und die
    Funktion antwortet unverändert.

110. **Kachel „Spur" heißt „GPS-Daten".**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-9), Schritt 8 (S9).*
    Die Kachel neben „editiert" zeigt z. B. „Spur · 852 Punkte"; „Spur" ist
    schwer verständlich. Soll: „GPS-Daten", die Punktzahl entfällt (F15);
    Wortliste nachziehen.
    *Konzept S9 (07.09.2026): E-S9-03 („GPS-Daten" überall in der NutzerInnen-Sicht; AP3).*

    **Erledigt mit Web 15.9.0 am 07.09.2026 (S9/AP3, E-S9-03).** Umbenannt
    sind **72 sichtbare Zeichenketten in 18 Dateien** und **41 Zeilen im
    Handbuch** — die Plakette der Einsatzansicht, das Aktionsmenü, die ganze
    Seite „GPS-Daten des Diensttages", die Jobnamen im Betrieb, der
    Sicherungs- und Importweg. `grep -c "Spur" docs/Handbuch.md` ist von
    **41 auf 0** gegangen (bis auf ein Zitat des alten Wortlauts mit
    Versionsangabe).

    **Fachbegriff bleibt er, wo er einer ist:** im Code (`spur_lib.php`), in
    `docs/Technik.md`, im JSON-Vertrag und im Sicherungsformat — dort heißt
    die Datei im Archiv „Spurteil", und eine Meldung, die sie anders nennt,
    hilft beim Suchen nicht. Auch der GPX-Fachbegriff bleibt: Eine GPX-Datei
    enthält Spuren, und der Satz, der das erklärt, sagt es weiter so.

    Die **Plakette nennt keine Zahl mehr**: „GPS-Daten" statt
    „Spur · 852 Punkte", „GPS-Daten ausgedünnt" statt „Spur ausgedünnt · 113
    von 443 Punkten". Wie viele Messpunkte eine Aufzeichnung hat, sagt nichts
    über den Einsatz; wer die Zahl braucht, findet sie auf der Seite
    „GPS-Daten des Diensttages".

    Die **Wortliste** hat dafür eine neue Regel bekommen (`spur`,
    großgeschrieben, damit sie das Substantiv trifft und nicht den
    Bezeichner) und sechs begründete Ausnahmen; sie steht auf **0/0/0** bei
    96 Regeln, alle gegriffen.

    **Die Android-App bleibt außen vor.** Fünf ihrer sichtbaren Texte sagen
    noch „Spur". Sie zählt getrennt, braucht einen eigenen APK-Bau und einen
    Emulatorlauf; Schritt 9a arbeitet ohnehin an ihr und nimmt sie dort mit
    (Entscheidung des Auftraggebers, 07.09.2026). Die Wortliste führt das als
    **befristete** Ausnahme in Klasse D — sie wird mit 9a gelöscht.

70. **„Auf der Karte setzen" für Standorte in den Einstellungen.**
    *Zulieferung aus P3; bis Fassung 16 ohne Nummer.* Die Position eines
    Standorts wird über die Ortssuche oder von Hand als Koordinate erfasst;
    das Ortsfeld der Einsätze kann seit P3 die Position auch auf der Karte
    wählen. Dieselbe Kartenwahl fehlt in den Stammdaten der Standorte.
    **Zu tun:** den vorhandenen Baustein des Ortsfelds dort einbinden, kein
    neuer Baustein. Zuordnung: Backlog-Runde.
    *Konzept S9 (07.09.2026): E-S9-06 c (Pin-Knopf in der Nur-Lage-Fassung, Standortkarte der Standortseite; AP2/AP5).*

    **Erledigt mit Web 15.8.0 am 07.09.2026 (S9/AP2, E-S9-06 c).** Kein neuer
    Baustein, wie der Punkt es verlangte: Der Pin-Knopf samt Blatt („Meine
    Position übernehmen" / „Auf der Karte wählen") stand in `ui_ortsfeld()`
    schon — nur im falschen Zweig. Die Funktion hat zwei Formen, und die
    Nur-Lage-Fassung (`feld => false`), die Standorte und Zielkliniken
    benutzen, gab ihn nicht aus. Der Block steht jetzt einmal da und wird
    zweimal ausgegeben. Gemessen mit `tools/klickprobe/` (Weg
    `ap2-dialog-fuenf-einbauorte`): Der Dialog öffnet aus **5 von 5**
    Einbauorten mit Karte darin — Einsatzort, manueller Abfahrtort,
    Transportziel, Standort im Konto (`einstellungen.php`) und Standort
    systemweit (`admin_stammdaten.php`). Ohne Spur, denn zu einem Standort
    gehört kein Einsatz.

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
    *Konzept S9 (07.09.2026): E-S9-05, E-S9-06 (Geocoder-Modul, Suchfeld im Dialog; AP2).*

    **Erledigt mit Web 15.8.0 am 07.09.2026 (S9/AP2, E-S9-05, E-S9-06 a).**
    Die zuerst zu klärende Frage — welche Geocoding-Quelle — ist so
    beantwortet: dieselbe wie bisher (Photon), aber **einstellbar und
    abschaltbar**. `assets/geocoder.js` ist der eine Weg nach draußen; die
    Anschrift steht nicht mehr im ausgelieferten Browserstand
    (`grep -rn "komoot" server/assets/` = **0**), sondern in `app_state` und
    ist unter Betrieb → Servereinstellungen zu ändern. Zwei Schalter davor
    (Installation und Konto) können sie ganz abstellen; nachgemessen am
    Netzwerkprotokoll: eingeschaltet **2** Anfragen auf demselben Weg,
    ausgeschaltet **0**. Das Suchfeld sitzt im Dialogkopf; ein Treffer setzt
    das Kreuz und übernimmt nichts (F1) — gemessen: Feld nach dem Treffer
    leer, 0 Chips, nach „Übernehmen" 1 Chip.

107. **Zielklinik per Koordinaten und Karte.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-7), Schritt 8 (S9).*
    An beiden Stellen (Vorbelegung bei den Rettungsmitteln,
    Einsatzbearbeitung) zusätzlich Koordinateneingabe und Auswahl über den
    standardisierten Kartendialog (Nr. 101). Koordinaten einheitlich wie in
    den übrigen Feldern (F11); so gewählte Zielkliniken sind Ad-hoc-Einträge
    je Einsatz, kein Stammdateneintrag (F12). Migration; Vertrag prüfen.
    *Konzept S9 (07.09.2026): E-S9-06 (Pin-Knopf am Katalogfeld, Ad-hoc-Wert; AP2).*

    **Erledigt mit Web 15.8.0 am 07.09.2026 (S9/AP2, E-S9-06 d).** Kein
    Sonderfall im Formular, sondern ein Schlüssel im Feldkatalog:
    `'ortswahl' => true` an `transport_dest` in `mission_fields.php`, und
    Pin-Knopf, Blatt und Kartendialog kommen von selbst — die Regel
    „Feldkatalog statt Sonderfall" (`CLAUDE.md` 4). Die Koordinateneingabe war
    schon da (F11); ein per Karte gewähltes Ziel bleibt ein **Ad-hoc-Wert des
    Einsatzes** und wird kein Stammdatensatz (F12). Migration und Vertrag
    blieben unberührt: Die Koordinate liegt weiter in `dest_lat/lon` im
    Klartext (bis S11), der JSON-Vertrag ist unverändert (R12).

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
    *Konzept S9 (07.09.2026): **ganz nach S9** (E-S9-05, AP2) — Hinweis am Feld, Datenschutztext, Installationsschalter (Karte „Adresssuche" auf Betrieb → Servereinstellungen, `app_state` `adresssuche`), Kontoschalter (Profil → Datenschutz), Dienstadresse. Zuordnung jetzt: S9.*

    **Erledigt mit Web 15.8.0 am 07.09.2026 (S9/AP2, E-S9-05).** Alle vier
    Teile des Sofortpakets stehen, und der Schalter ist zwei geworden:
    **(1)** Kleinzeile unter dem Ortsfeld, die den Dienst beim Namen nennt —
    einmal je Seite, nicht je Feld, und nur bei eingeschalteter Suche.
    **(2)** Textbaustein zum Kopieren unter Verwaltung → Installation, mit der
    tatsächlich eingetragenen Dienstadresse. Der ursprünglich vorgesehene Weg
    (Vorlage in `rechtstexte_lib.php`) ging nicht: Die Anwendung liefert
    **keinen** Rechtstext mit und kann deshalb keinen Absatz einsetzen; sie
    kann ihn nur bereitlegen. **(3)** Installationsschalter, Karte
    „Adresssuche" auf Betrieb → Servereinstellungen, `app_state` `adresssuche`,
    Vorgabe „an" (F-SP-4). **(4)** Kontoschalter, Profil → Karte
    „Datenschutz", `users.adresssuche`. Dazu der Selbstbetrieb aus Nr. 101:
    Das Feld „Dienst" nimmt die Adresse eines eigenen Photon auf — ohne
    Codeänderung und ohne neue Auslieferung.

    **Die Kachelserver bleiben.** Sie sind die Karte selbst und lassen sich
    nicht abschalten, ohne die Karte abzuschaffen; was sie sehen, steht in
    `docs/Lizenzen.md` 6.1. Damit ist der Adressdienst der einzige
    Laufzeitdienst des Projekts, den man ausschalten kann.

147. **Die aufgezeichnete Spur im Kartendialog der Einsatzbearbeitung zeigen.**
    *Aufgenommen 06.09.2026 vom Auftraggeber (Rahmenplan Fassung 32).* Wer im
    Einsatzformular „Auf der Karte wählen" öffnet (`ortswahl.js`, Fadenkreuz),
    sieht eine leere Karte — obwohl der Einsatz eine GPS-Aufzeichnung hat und
    `api/mission.php` sie längst liefert. Der Ort, der gesucht wird, liegt fast
    immer **auf** der Spur; ohne sie sucht man ihn auf der Karte neu.
    **Soll:** Liegt eine Aufzeichnung vor (`$hatTrack`, dieselbe Schwelle wie
    das Formular: mehr als ein Punkt), zeichnet der Dialog sie in der ersten
    Spurfarbe; ist das Feld noch leer, öffnet die Karte auf der Spur
    (`fitBounds`), sonst wie heute auf der Koordinate. **Nur die Spur, keine
    Luftlinie** — `luftlinie.js` bleibt außen vor; eine gedachte Verbindung
    hilft beim Suchen nicht und wäre im Auswahldialog eine Falschaussage. Gilt
    für **jeden** Kartendialog des Einsatzformulars: heute den Einsatzort,
    mit PS-7 (Nr. 107) auch die Zielklinik — der manuelle Abfahrtort erscheint
    ohne Spur ohnehin nicht. An Photon geht weiterhin nur die Koordinate
    (Umkehrsuche), nie ein Spurpunkt. **Ort nach R74:** der vorhandene
    Pin-Knopf am Feld — kein neuer Menüpunkt, keine neue Darstellung, der
    Dialog bekommt eine Ebene mehr. Verträglich mit S11 (Weg B): Der Dialog
    läuft im Browser, wo die Spur nach S11 entschlüsselt vorliegt.
    Zuordnung: **S9**, als PS-11 der Vorbereitung und Ergänzung zu PS-1
    (Nr. 101, gemeinsamer Kartendialog) — der Dialog entsteht dort ohnehin
    neu.
    *Konzept S9 (07.09.2026): E-S9-06 b (Spur im Dialog, fitBounds bei leerem Feld; AP2).*

    **Erledigt mit Web 15.8.0 am 07.09.2026 (S9/AP2, E-S9-06 b).** Der Dialog
    zeichnet die aufgezeichnete Spur in der ersten Spurfarbe, mit Ringpunkt
    an Anfang und Ende und einer Legende darunter; **keine Luftlinie**, wie
    verlangt. Er wartet nicht auf sie: Der Dialog steht sofort, die Spur kommt
    über `api/mission.php` nach. Ist das Ortsfeld leer, passt sich die Karte
    beim Eintreffen auf die Spur ein (`fitBounds`, 24 px Rand) — aber nur,
    wenn niemand inzwischen selbst geschoben oder gezoomt hat; sonst risse es
    die Karte unter dem Kreuz weg. Steht schon eine Koordinate, bleibt sie der
    Mittelpunkt. Gemessen mit `tools/klickprobe/` (Weg `ap2-spur-im-dialog`)
    an einem Einsatz mit 309 Punkten: **1 Linie, 4 Ringpunkte** (2 auf der
    Karte, 2 in der Legende), Legende sichtbar, **0 Pfeile**.

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
    *Konzept S9 (07.09.2026): E-S9-07 (kein `<datalist>` mehr, Baustein Vorschlagsliste; AP1, Besatzung AP6).*

    **Erledigt mit Web 15.7.0 am 07.09.2026 (S9/AP1, E-S9-07).** Die Erhebung
    hat fünf Dateien genannt — `ui.php` (Ortsfeld), `einsatz_form.php`
    (Transportziel und Besatzung des Einsatzes), `index.php`
    (`renderCrewFields()`), `mission_fields.php` und `assets/ortsfeld.js`, die
    beiden letzten nur im Kommentar. Alle sind umgestellt: `grep -rn datalist
    server/` findet **0** Treffer außerhalb von Kommentaren (vorher 12 in
    sechs Dateien, davon 8 im gerenderten Markup einer Einsatzseite). An die
    Stelle tritt `assets/vorschlagsliste.js` — ein Baustein mit Gruppen,
    Symbolen, Herkunftszeile, Pfeiltasten, Enter und Escape (`Design.md`
    9.28, Mockup M-S9-03, freigegeben 07.09.2026). Gemessen mit
    `tools/klickprobe/` am Besatzungsfeld eines Einsatzes: vorher **keine
    eigene Liste und 8 `<datalist>`**, nachher der Wert im Feld und **0
    `<datalist>`**. Der Punkt verlangte, jedes Feld einzeln am Handy zu
    prüfen (Chromium mobil und WebKit) — das steht als Punkt auf der
    Prüfliste des Auftraggebers; der Prüfstand hat weder WebKit noch einen
    Finger.

102. **Weitere Rettungsmittel: die Auswahl wird nicht übernommen.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-2), Schritt 8 (S9).*
    Die Suche im hinterlegten Stand liefert Treffer; ein Klick schließt den
    Dialog, das Rettungsmittel wird aber nicht in den Einsatz übernommen.
    Bug, nur Desktop/Web (F2).
    *Konzept S9 (07.09.2026): E-S9-08 (mousedown statt click, Ursache Blur-Verzögerung; AP1).*

    **Erledigt mit Web 15.7.0 am 07.09.2026 (S9/AP1, E-S9-08).** Die Ursache
    steht im Konzept mit Zeilenangabe: Die Trefferliste übernahm auf `click`
    (`einsatz_form.php:1921`), das Eingabefeld versteckte sie 150 ms nach
    `blur` (`:1957`). Ein Mausklick ist `mousedown` → `blur` → `mouseup` →
    `click`; dauert er länger als 150 ms, ist der Knopf beim `mouseup` schon
    `hidden`, und der Browser feuert kein `click`. Ein Fingertipp ist
    schneller — deshalb war der Fehler auf Desktop beschränkt (F2). Die Liste
    ist jetzt eine Verwendung des gemeinsamen Bausteins und übernimmt auf
    `mousedown` mit `preventDefault()`. Gemessen mit `tools/klickprobe/` bei
    **300 ms gehaltener Maus, dieselbe Fassung der Probe gegen beide
    Stände**: vorher **0 von 3** Übernahmen, nachher **3 von 3**. Nebenbefund
    aus dem Vorher-Lauf: Es blieb nicht beim Nichtstun — im ersten Durchgang
    verschwand ein bereits gewähltes Rettungsmittel, weil nach dem Verstecken
    der Liste das Kreuz eines Chips unter dem Zeiger lag und den Klick bekam.
    Dass `locator.click()` von Playwright den Fehler **nicht** findet (es
    hält die Taste rund 10 ms), ist der Grund, warum die Probe
    `mouse.down()`, warten und `mouse.up()` von Hand fährt.

106. **Klinik- und Adressvorschläge überlagern sich.**
    *Aufgenommen 03.09.2026 aus der Problemsammlung (PS-6), Schritt 8 (S9).*
    Beide Vorschlagsarten in **einer** Liste: Kliniken oben, visuell
    abgesetzt, darunter die Adressen. Klinikvorschläge nur im
    Zielklinik-Kontext (F9), höchstens zwei (F10).
    *Konzept S9 (07.09.2026): E-S9-07, M-S9-03 (eine Vorschlagsliste mit Gruppen; AP1).*

    **Erledigt mit Web 15.7.0 am 07.09.2026 (S9/AP1, E-S9-07).** Es gibt jetzt
    **eine** Liste. Oben unter „Zielkliniken" höchstens zwei Stammdatentreffer
    (F10) — sie erscheinen bei **Teilübereinstimmung**, nicht mehr erst bei
    genauer Namensgleichheit (F-S9-K-01) —, darunter unter „Adressen" die
    Vorschläge der Adresssuche. Ein Stammdatentreffer setzt Name und
    Koordinate, ein Adresstreffer nur die Koordinate. Stammdaten stehen
    **nur im Zielklinik-Kontext** (F9): Einsatzort und Abfahrtort zeigen
    allein Adressen, und dort entfällt die Gruppenzeile. Gemessen mit
    `tools/klickprobe/`, Tipp „Klin" am Transportziel: vorher **1 eigene
    Liste ohne Gruppen neben 8 `<datalist>`**, nachher **1 Liste mit 2
    Gruppen, 2 Stammdaten- und 4 Adresstreffern und 0 `<datalist>`** (die
    Adressen aus der Attrappe des Prüfstands, `limit=6` wie im Betrieb).

127. **Anmeldeformular ohne CSRF-Token.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-8).* `login.php:246`
    trägt kein Token; eine fremde Seite kann einen abgemeldeten Browser per
    Top-Level-POST in ein Angreiferkonto anmelden. Patientenfelder sind
    nicht betroffen (kein `edk`, fremde Hülle öffnet nicht), aber Eingaben
    landen im fremden Konto. Die Sitzung besteht beim GET schon
    (`login.php:13`), das Token ist also da. Zuordnung: Sofortpaket
    Sicherheit (R78).

    **Erledigt mit Web 15.6.0 am 07.09.2026.** `csrf_token()`, `csrf_field()`
    und das neue `csrf_ok()` stehen jetzt in `session_lib.php` statt in
    `auth_guard.php` — die eine Seite, die den Schutz am nötigsten braucht, lädt
    `auth_guard.php` nicht. Die Prüfung steht **vor** allen Zählern (ein
    abgelaufenes Formular ist kein Fehlversuch), antwortet mit der Anmeldeseite
    statt einer 403, und nach erfolgreicher Anmeldung wird das Token neu gezogen
    wie die Sitzungskennung. Gemessen: **2 von 2** im Browser, dazu drei
    HTTP-Fälle. Zwei Prüfmittel melden sich ohne Browser an und schicken das
    Feld seither selbst (`sitzung.py`, `tools/gpxprobe/`).

128. **E-Mail-Wechsel im Profil ohne Passwortnachweis.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-7).* `einstellungen.php:92-106`
    schreibt die Adresse allein mit CSRF-Token um — kein `old_token`, kein
    `session_epoch`, keine Mail an die alte Adresse; die Verwaltung kann
    sie ebenfalls ändern (`admin_user.php:127-133`). Die Kette endet im
    Reset-Modus, der den Wiederherstellungsschlüssel braucht — keine
    Offenlegung, aber Kontoübernahme für Klartextfelder und Aussperren.
    Sofortpaket: Nachweis per `old_token` wie beim Passwortwechsel, Hinweismail
    an die alte Adresse bei beiden Wegen; Bestätigung der neuen Adresse
    kommt mit R37.6 in P5. Zuordnung: Sofortpaket Sicherheit (R78), Rest P5.

    **Erledigt mit Web 15.6.0 am 07.09.2026.** `old_token` wie beim
    Passwortwechsel, **nur beim tatsächlichen Wechsel** — Name und Logo gehen
    ohne. Die Hinweismail an die **alte** Adresse geht auf beiden Wegen (Profil
    und Verwaltung); sie ist die einzige, die im Missbrauchsfall noch der
    Besitzerin gehört. `session_epoch` bleibt unverändert. Gemessen: **4 von 4**
    im Browser. Die Bestätigung der **neuen** Adresse (Double-Opt-In) bleibt
    R37.6 in P5.

129. **`apk/` und `demo/` liegen ungesperrt im Webroot.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-9).* `apk.php` verlangt
    die Anmeldung, der Ordner selbst nicht (`apk_lib.php:22`, Dateinamen
    vorhersagbar); `demo/fixture.json.gz` trägt das Schlüsselmaterial des
    Demo-Kontos (öffentliches Passwort, also harmlos, aber unnötig). Anders
    als `sicherungen/` legt kein Code eine Sperre an, und eine Datei in
    `apk/` käme wegen der Deploy-Ausnahmeliste nie an. Zwei
    `RewriteRule`-Zeilen in `.htaccess`. Zuordnung: Sofortpaket Sicherheit.

    **Erledigt mit Web 15.6.0 am 07.09.2026.** Eine `RewriteRule`-Zeile für beide Ordner hinter
    dem HTTPS-Zwang. Beide Ordner werden ausschließlich vom PHP-Code gelesen —
    die Sperre kostet die Anwendung nichts. Gemessen unter einem echten Apache
    (die lokale Installation läuft auf PHPs eingebautem Server und liest keine
    `.htaccess`): **vier Aufrufe → 403**, `login.php` und `assets/style.css`
    unverändert 200.

130. **DOCTYPE-Sperre im GPX-Import umgehbar.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-10).* `gpx_lib.php:332`
    prüft `/<!DOCTYPE/i` auf dem Rohtext; ein UTF-16-kodiertes GPX passiert
    die Regex, libxml versteht es. Folge: interne Entitäten trotz Sperre
    (Billion Laughs), XXE nicht (kein `NOENT`, `NONET`). Nur angemeldet,
    12 MB Grenze. Vor der Regex: gültiges UTF-8 und kein Nullbyte —
    GPX aus Geräten ist UTF-8. Zuordnung: Sofortpaket Sicherheit.

    **Erledigt mit Web 15.6.0 am 07.09.2026.** Vor der Regex stehen jetzt zwei
    Prüfungen: kein Nullbyte, gültiges UTF-8. Am Stand davor gemessen: das
    UTF-16-Dokument **ging durch**, zwei Punkte, Entität expandiert. Der Preis:
    Eine GPX-Datei in Latin-1 mit Umlauten wird abgewiesen, mit einem Satz, der
    sagt, was zu tun ist. `tools/gpxprobe/` bekam **Teil 8** — acht
    Umgehungsversuche, **0 durch**, und eine saubere Datei geht weiterhin durch.

131. **`wiederherstellen.php` gibt unangemeldet Auskunft.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-11).* Zeile 530 zeigt
    den Datenbank-Fehlertext (Rechnername, Nutzer möglich), Zeile 538 die
    Kontenzahl jedem Besucher. Fehlerkennung statt Text, „in Betrieb" ohne
    Zahl. Zuordnung: Sofortpaket Sicherheit.

    **Erledigt mit Web 15.6.0 am 07.09.2026.** Fehlerkennung statt Fehlertext
    (`fehler_kennung()`), „in Betrieb“ ohne Zahl. Gemessen: vorher
    `Access denied for user 'nadoku'@'localhost' to database …` und „stehen 2
    Konten“, nachher die Kennung und kein Zahlwert; der volle Text steht unter
    der Kennung im Fehlerprotokoll des Webspace.

133. **Klartext-Reste auf dem Server.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-13).* Während des
    Komplettbackup-Baus liegt `dump.sql.gz` unversiegelt in
    `sicherungen/komplett/.bau-*/`, Reste bis zum nächsten Lauf
    (`komplett_lib.php:53-55,454,471`); Reset-Token bis zur Einlösung in der
    PHP-Sitzungsdatei und im Zugriffslog des ersten GET (M1-06 kennt es);
    bei Mailfehler zeigt die Verwaltung den Setz-Link. Sofortpaket: Bauordner
    nach Fehlschlag räumen; der Rest wird in `Technik.md` benannt und
    bleibt. Zuordnung: Sofortpaket Sicherheit.

    **Erledigt mit Web 15.6.0 am 07.09.2026 — der Bauordner.** `komp_schub()`
    fängt jetzt, räumt und setzt den Zustand auf `abgebrochen`; weil ein Absturz
    kein `catch` sieht, räumt zusätzlich **jeder** Aufräumlauf die Reste, auch
    der ohne Fälligkeit. Gemessen: **1 auf 0** in beiden Fällen. Der Preis:
    „Fortsetzen“ nimmt einen gescheiterten Lauf nicht mehr auf — Rechenzeit,
    keine Daten. **Der Rest bleibt und steht jetzt in `Technik.md` 4.98 in einer
    Tabelle**: Reset-Token in Sitzungsdatei und Zugriffslog, angezeigter
    Setz-Link bei Mailfehler.

134. **Verlorene Uhr kann Phasen alter Einsätze ersetzen.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-14).* Der Geräteschlüssel
    liegt auf der Garmin-Uhr im Klartext (`watch/source/Pair.mc:853`; die
    Plattform hat nichts Besseres). Lesen kann ein Finder nichts —
    `ingest.php` ist POST-only —, aber er kann Einsätze hochladen und
    Phasen bestehender Einsätze ersetzen (`ingest.php:361`), bis das Gerät
    im Web getrennt ist. Was schon geschützt ist: Einsätze mit
    `manual = 1` überspringt `ingest.php` ganz (Z. 251), und Phasen werden
    nur ersetzt, wenn der Upload mindestens so viele bringt (Z. 359).
    **Entschieden (R78):** ein **Zeitfenster ab Einsatzbeginn**, innerhalb
    dessen ein Gerät ersetzen darf; danach `ok` ohne Ersetzen (idempotent,
    kein Fehler auf der Uhr); Neuanlage immer. **Entschieden: 72 h**
    (damit ein Freitagsdienst am Montag noch nachkommt) — Konstante in `db.php`,
    `JSON-Vertrag.md` und Handbuch 12 („Uhr verloren: sofort trennen").
    Zuordnung: Sofortpaket Sicherheit.

    **Erledigt mit Web 15.6.0 am 07.09.2026.** `INGEST_ERSETZFENSTER_H = 72` in
    `db.php`, gerechnet ab dem **gespeicherten** `started_at` — nicht ab dem
    gesendeten, den bestimmt der Absender. Danach `ok` ohne Ersetzen, ohne
    Anhängen, ohne Fehler, benannt über `kept_phases`, `kept_resus` und neu
    `kept_points`. Neuanlage bleibt immer möglich; der Weg gegen eine verlorene
    Uhr bleibt das Trennen (Handbuch 10). `tools/ingestprobe/` bekam **Teil 9**
    (**1 angenommen, 1 abgewiesen**) und stellte dabei ihre Zeitstempel von
    festen März-Daten auf `time()` um — zehn ihrer Erwartungen prüften sonst
    einen Fall, den es im Betrieb nicht gibt.

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

    **Erledigt mit Web 15.6.0 am 07.09.2026 — die JSON_HEX-Vorgabe und der
    Cast.** `json_js()` in `db.php` steht an allen **44** Stellen, die in einen
    `<script>`-Block schreiben; die **35** außerhalb bleiben unverändert, weil
    dort Bytes an Prüfsummen hängen (maschinell eingeteilt, nachgezählt: 79
    Aufrufe gesamt). Der `(string)`-Cast kam mit Nr. 127 über `csrf_ok()`.
    Gemessen mit einem Profilnamen `<!--<script>` auf `import.php`: vorher
    fehlten `KONTO_NAME`, `APP_TZ` **und** `WEB_VERSION` — der ganze Block war
    verschluckt; nachher stehen alle drei. **Nicht mitbehoben:** HSTS und
    `Permissions-Policy` gehen mit der CSP (Nr. 8), der `querySelector` mit
    einem Wert aus dem URL-Fragment steht als **Nr. 153** neu im Backlog.

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

    **Erledigt mit Web 15.6.0 am 07.09.2026.** `KDF_ITER_ZIEL = 600000`,
    `KDF_ITER_LISTE = [600000, 320000]`; gemessen 298 → 551 ms je Ableitung, im
    Übergang 849 ms. Mindestlänge 12 als `PW_MIN_LAENGE` an einer Stelle.
    **Zwei Funde, die erst der Sprung sichtbar gemacht hat:** Die stille
    Anhebung lief nicht beim nächsten Anmelden — sie braucht `CSRF`, und das gab
    `ui_krypto_bootstrap()` nur auf Anfrage aus; die erste Seite ohne CSRF
    verwarf das Vormerkfach und damit die Anhebung für die ganze Sitzung. Und
    die Wartungsseite meldete nur *verwaiste* Rundenzahlen, nicht, wer noch auf
    dem Altwert steht — also nicht die Zahl, die sagt, wann der Altwert weg
    darf. Beides behoben. **Die Sperrliste rechnet jetzt den Anteil statt des
    Vorkommens**, sonst widerspräche die Passphrasen-Empfehlung der eigenen
    Prüfung („Anker-Winter-Regen-Glas“ scheiterte an „winter“).

138. **Weg C: die Zusage auf das eingrenzen, was sie hält.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-1), entschieden R78.*
    Nur Dokumente, keine Versionsstufe: `CLAUDE.md` 4, `Technik.md` 4.98,
    README, Handbuch 5 und der Entwurf des Datenschutztextes sagen, dass
    Spur, Phasenkoordinaten, Zielklinik, Zeiten und Reanimationsereignisse
    im Klartext liegen und der Einsatzort daraus rekonstruierbar ist (Nr. 43,
    `Konzept-V1-Ortsdaten.md` Weg C). Zuordnung: Sofortpaket Sicherheit.

    **Erledigt mit Web 15.6.0 am 07.09.2026.** Nur Dokumente, keine Zeile Code.
    `CLAUDE.md` 4, `README.md`, `Technik.md` 4.98 und `Handbuch.md` 5 zählen
    jetzt **beide** Seiten auf; dazu ein übernehmbarer Textbaustein für die
    Datenschutzerklärung in Handbuch 11.5 — die Anwendung liefert weiterhin
    keinen Rechtstext mit, aber die technische Tatsache dahinter kann nur sie
    kennen.

142. **Android: HTTP-Ausnahme gilt auch im Release-Build.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (AN-1).*
    `Serveradresse.kt:108,119` lässt `localhost` und IPv4-Adressen mit
    `http` durch und stuft ein ausdrückliches `https://127.0.0.1/` herab
    (Test `oertlicheAdressenBehaltenHttp`); keine
    Release-`network_security_config`. Auf Android 8.0/8.1 ginge
    `X-Api-Key` bei einer Selbsthoster-Adresse per IP im Klartext; der
    Standardbau ist nicht betroffen. Ausnahme an `BuildConfig.DEBUG`,
    Klartextverbot im Release. Zuordnung: Sofortpaket Android (R78).

    **Erledigt mit Android 0.14.0 am 07.09.2026.** Die Ausnahme in
    `Serveradresse` hängt an `BuildConfig.DEBUG`; `handy/src/release/` bringt
    eine Netzsicherheitsregel mit `cleartextTrafficPermitted="false"` — zwei
    Böden. Der Prüffall `oertlicheAdressenBehaltenHttp` läuft nur im
    Debug-Buildtyp, sein Gegenstück nur im Release (je Bauart 11 Fälle,
    1 übersprungen). Belegt an der zusammengeführten Release-Manifestdatei:
    `networkSecurityConfig="@xml/netzsicherheit"`.

143. **Android: Verzicht auf Certificate Pinning ist nicht festgehalten.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (AN-3).* Vertretbar bei
    fester Domain mit rotierendem Zertifikat, aber nirgends entschieden
    (`docs/` und `android/`: kein Treffer). Eine Zeile in
    `android/LIESMICH.md`. Zuordnung: Sofortpaket Android.

    **Erledigt mit Android 0.14.0 am 07.09.2026.** Abschnitt „Warum kein
    Certificate Pinning" in `android/LIESMICH.md` — feste Domain,
    rotierendes Zertifikat, niemand, der Ersatzschlüssel pflegte; Android
    traut benutzerinstallierten Wurzeln seit Fassung 7 ohnehin nicht — und
    ein Verweis im Kopf von `HttpNetzweg`.

144. **Android: Data-Layer-Empfang ohne Absender- und Plausibilitätsprüfung.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (AN-4).*
    `HandyHorcher.kt:30-32` und `Uhrannahme.kt:63-92` prüfen keinen
    `sourceNodeId` und keine Zeitstempel; jede `uhr`-Kennung wird als neue
    Uhr geführt. Kein Abflussweg, nur Störung — das Vertrauen ruht auf der
    proprietären Bibliothek (gleiches Paket, gleiche Signatur). Absender
    gegen die verbundenen Knoten, Zeiten gegen Dienstfenster;
    Robolectric-Prüffall mit Attrappe. Zuordnung: Sofortpaket Android.

    **Erledigt mit Android 0.14.0 am 07.09.2026.**
    `WearNachrichtenweg.verbundeneKnoten()` liefert die Knotenliste (`null`
    = nicht lesbar), `Uhrannahme.absenderBekannt()` verlangt `sourceNodeId`
    darunter — sonst weder Wirkung noch Quittung; die Zeit der Uhr darf
    höchstens fünf Minuten in der Zukunft und höchstens fünf Minuten vor dem
    laufenden Dienst liegen, sonst quittiert, nicht gewirkt.
    `UhrannahmeTest` 12 → 19 Fälle (Robolectric, echtes SQLite). Die
    Schnittstelle `Nachrichtenweg` ist unverändert; die neue Methode steht
    nur an der Umsetzung, weil ihr einziger Aufrufer (`HandyHorcher`) den
    Data Layer ohnehin kennt.

145. **Android: Gradle-Wrapper ohne Prüfsumme.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (AN-5).*
    `gradle-wrapper.properties:3-5` ohne `distributionSha256Sum` (begründet
    mit dem gesperrten `downloads.gradle.org` — die Summe wird aber nur
    beim Herunterladen geprüft und stört den Container nicht);
    `gradle-wrapper.jar` im Repositorium unvalidiert. R8 bleibt aus,
    Begründung steht. Zuordnung: Sofortpaket Android.

    **Erledigt mit Android 0.14.0 am 07.09.2026.**
    `distributionSha256Sum=bd711022…f3531` in `gradle-wrapper.properties`;
    die Zahl aus `services.gradle.org` und am frisch geladenen Archiv
    nachgerechnet (137 393 837 Bytes). LIESMICH 2.1 erzählt die Sperre als
    Vergangenheit; R8 bleibt aus, mit Begründung.

148. **Der Knopf „Diensttage zusammenführen" in der Überschneidungswarnung führt auf 404.**
    *Aufgenommen 06.09.2026 vom Auftraggeber (Rahmenplan Fassung 32).* Laufen
    zwei Diensttage zeitgleich, zeigt `index.php` seit Web 13.3.0 die
    R57-Warnung mit dem Ausweg „Diensttage zusammenführen". Der Knopf
    verlinkt `diensttag_zusammenfuehren.php?ziel=<id>` (`index.php:173`); die
    Seite liest aber `$_GET['d']` (`diensttag_zusammenfuehren.php:26`) —
    wie der zweite Link auf derselben Seite im Aktionsmenü (`?d=`, Zeile 201)
    und `daymergelink` in `loadDay()`. `$zielId` ist damit 0, `dt_laden()`
    liefert null, `ui_abbruch(404, 'Diensttag nicht gefunden.')`. **Genau
    der Fall, für den die Warnung gebaut ist, endet auf einer Fehlerseite.**
    Kein Prüfmittel deckt das: Der Bilderlauf fotografiert die Warnung, klickt
    aber nichts; die Prüfliste S4 nennt R57 nicht. **Zu tun:** `?ziel=` →
    `?d=` (eine Zeile); dazu ein Prüffall, der die Ziele aller `href` auf
    `index.php` gegen die gelesenen Parameter der Zielseiten hält — oder
    wenigstens ein Browserlauf mit Klick. Zuordnung: **Backlog-Runde,
    sofort**, eine Korrekturstufe mit Nr. 149.
    **Erledigt mit Web 15.5.2 am 06.09.2026.** `?ziel=` → `?d=` in
    `index.php`, eine Zeile, mit der Begründung im Kommentar daneben.
    Browserlauf mit zwei zeitlich überlappenden aktiven Diensttagen im
    Prüfkonto: vorher **HTTP 404** „Diensttag nicht gefunden", nachher
    **HTTP 200** „Diensttag aufnehmen" mit dem geöffneten Tag in der
    Unterzeile („Aufnehmender Diensttag: 05.09.2026 08:00 … — dieser
    bleibt"). Dazu das Prüfmittel, das der Punkt verlangt hat:
    **`tools/linkprobe/`** hält jede Adresse `<seite>.php?<name>=` unter
    `server/` gegen die Parameter, die die Zielseite liest — 99 Zielseiten,
    132 Verweise, 0 unbekannte Abweichungen; gegen den Stand vor der
    Behebung gefahren meldet es die eine Zeile mit Datei und Zeilennummer.
    Es hat dabei einen zweiten Fall gleicher Art gefunden, der **nicht**
    mitbehoben ist: Nr. 151.

149. **Die Seite Updates zählt anders als Status und Menü — und die Rollenmigration war ohne die Rolle nicht ausführbar.**
    *Aufgenommen 06.09.2026 am Produktivstand, unmittelbar nach dem
    S8-Deploy (Rahmenplan Fassung 32).* Zwei Funde, eine Ursache: Der
    Erstdeploy von S8 ist nie durchgespielt worden.
    **(a) Die Sperre.** `2026_09_05_rolle_betreiberin` macht Admins zu
    BetreiberInnen. Ausführen lässt sie sich nur über Betrieb → Updates, und
    `betrieb_updates.php` beginnt mit `require_betreiberin()` — der Rolle,
    die es vor der Migration nicht gibt. `update.php` lässt einen Admin
    durch und leitet per 302 auf dieselbe Seite (403). Der Notausgang
    `php update.php` braucht eine Kommandozeile, die es auf dem Webspace
    nicht gibt (`Technik.md`: kein `exec()`). S8 hatte das Aussperren der
    **letzten** BetreiberIn bedacht (R75), nicht das Fehlen der **ersten**;
    das Prüfdokument S8 sagt zu P-02 „Betrieb → Updates aufrufen" und hat
    den Wächter aus AP5 nicht gegen den Erstlauf gehalten. Ausweg am
    06.09.2026: die beiden SQL-Anweisungen der Migration über phpMyAdmin.
    **(b) Der Phantomzähler.** Danach kennt das Schema die Rolle; die
    Migration steht ohne Register-Vermerk da. `migrationen_lauf()` stuft
    sie als „nicht nötig — wird beim Ausführen als erledigt vermerkt" ein
    und zählt sie **trotzdem als offen** (`offen = 1`) — das lesen Status
    („1 Migration steht aus") und der Menüzähler (`status_lib.php`). Die
    Seite Updates dagegen sortiert nach Status `ok`, legt dieselbe Migration
    unter *Ausgeführt* ab, meldet „Alles aktuell" und **zeigt den Knopf
    „Ausstehende ausführen" nicht**. Im Web lässt sich der Vermerk also
    nicht nachholen; der Zähler bleibt, bis die nächste echte Migration
    läuft. Zwei Zählweisen für denselben Sachverhalt — der Fall, den S8
    abschaffen wollte (E-S8-16: die eine zählende Meldung).
    **Zu tun:** (b) zuerst — die Seite Updates zählt mit `offen` wie Status
    und Menü, listet eine „nicht nötig"-Migration unter *Ausstehend* mit
    neutraler Plakette („nicht nötig") und bietet den Knopf an; die
    Wartungsprobe bekommt eine Erwartung für genau diesen Zustand
    (Register ohne die jüngste Kennung, Schema aktuell). Danach ist auf dem
    Produktivserver einmal „Ausstehende ausführen" zu drücken (Rahmenplan
    Abschnitt 6). (a) als **Regel:** Eine Migration, die Rechte einführt,
    muss ohne diese Rechte ausführbar sein — für diese Installation
    erledigt; für die nächste Rollenmigration (Support-Rolle, R38, P5) und
    für das Bedrohungsmodell (R69) festhalten, und der phpMyAdmin-Notweg
    gehört ins Runbook (`Technik.md` 7). Zuordnung: **Backlog-Runde,
    sofort** (b), eine Korrekturstufe mit Nr. 148; (a) Runbook jetzt, Regel
    P5/P6.
    **Erledigt mit Web 15.5.2 am 06.09.2026 — Teil (b).** Die Vorschauzeile
    bekommt den eigenen Anzeigestatus `skip` und zählt weiter als offen;
    `betrieb_updates.php` führt sie unter *Ausstehend* mit der neutralen
    Plakette „nicht nötig" und zeigt den Knopf. Zwei Stellen dort, nicht
    eine: der Filter **und** die Plakettenauswahl, deren `default`-Zweig die
    Zeile sonst als roten „Fehler" gezeigt hätte. `status_lib.php` und der
    Menüzähler blieben unberührt — sie lesen `offen` und nie den Status.
    **Dazu ein Fund, den der Auftrag nicht nannte:** Nach dem Knopfdruck
    meldete die Seite „Es war nichts anzuwenden", obwohl der Registervermerk
    gerade geschrieben wurde; ein geschriebener Vermerk gilt jetzt als
    geschehen. Nebenbei richtig geworden: „Ausgeführt" nannte 43, der
    Datenbankstand daneben 42 — jetzt beide 42.
    **Prüfung:** `tools/wartungsprobe/` Teil 6, sieben Erwartungen, Sollwert
    damit **50 statt 43**, 0 nicht erfüllt; gegen den Stand vor der Behebung
    sind 4 der 7 rot. Der Fall baut den Produktivzustand nach, sucht seine
    Kennung statt sie hinzuschreiben, drückt nicht, wenn ohnehin etwas offen
    steht, und legt die Registerzeile mit ihrem ursprünglichen Zeitpunkt
    zurück.
    **Teil (a) ist keine Codeänderung und bleibt aufgerufen.** Der
    phpMyAdmin-Notweg und die Regel — *eine Migration, die Rechte einführt,
    muss ohne diese Rechte ausführbar sein* — stehen jetzt im Runbook
    (`docs/Technik.md`, Abschnitt 7) samt den beiden SQL-Anweisungen und der
    Reihenfolge, in der sie laufen müssen. Die Regel gilt für die nächste
    Rollenmigration (**Support-Rolle, R38, P5**) und gehört ins
    **Bedrohungsmodell (P6, R69)**; dort wird sie wieder aufgerufen.
    **Offen bleibt eine Handlung des Auftraggebers:** nach dem Deploy einmal
    Betrieb → Updates öffnen und „Ausstehende ausführen" drücken, damit
    `2026_09_05_rolle_betreiberin` im Register steht (Rahmenplan
    Abschnitt 6).
    **Die Regel angewandt mit Web 20.41.0 (P5c/AP4):** Die Support-Rolle
    hängt einen Wert an, den zum Ausführen niemand braucht; nachgestellt mit
    dem neuen Code auf dem alten Schema — Anmeldung und
    `betrieb_updates.php` antworten, `update.php` läuft durch (`Technik.md`
    7, Notweg).

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
    **Zusammengelegt mit Rahmenplan Fassung 32:** Nr. 95 (aus S5, Vorbereitung
    8.2) beschreibt denselben Fund mit denselben Zahlen; er wird dort
    weitergeführt (Backlog-Runde, Android). Diese Nummer bleibt als Verweis.

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
    **Nach *Erledigt* mit Rahmenplan Fassung 32** — die Phase S8 ist am
    06.09.2026 abgeschlossen; der Rest (Kennzahl „nie Konto-Backup") ist
    Nr. 117.

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
    **Erledigt mit Web 15.1.0 und 15.4.1 (S8/AP2 und AP6).** Die zweite
    Stufe `codeblock-lang` steht an allen fünf Stellen, der Kopieren-Knopf
    daneben (`Design.md` 9); die Phase ist am 06.09.2026 abgeschlossen
    (Rahmenplan Abschnitt 8), die Nummer geht mit Fassung 32 hierher.

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

204. **`smtp.php` schreibt bei Fehlschlag die Empfängeradresse ins
    Fehlerprotokoll.** *Aufgenommen 16.09.2026; Zuordnung P5a AP5
    (Nachtrag).* `error_log('SMTP: Versand an ' . $toEmail . '
    fehlgeschlagen')`, obwohl der Kopf derselben Datei und
    `betrieb_status.php` zusagen, dass kein Protokoll über Mailempfänger
    geführt wird. Es war die einzige Stelle mit Personenbezug im
    Fehlerprotokoll.

    **Erledigt am 16.09.2026 mit Web 20.8.0/20.9.0 (P5a/AP5, E-P5a-37).**
    Die Zusage gilt — entschieden vom Auftraggeber am selben Tag. Die
    Meldung lautet jetzt `[<Kennung>] SMTP: Versand fehlgeschlagen:
    <Grund>`; der Empfänger steht in `mail_warteschlange` und verfällt dort
    nach 30 Tagen (E-P5a-09).

    **Die Kennung ist der Punkt, nicht das Weglassen.** Eine Zusage, die
    einem die Fehlersuche nimmt, tauscht ein Problem gegen ein anderes.
    Die Warteschlange schreibt dieselbe Kennung in ihre Fehlerspalte: Wer
    einem Fehlschlag nachgeht, hat dort den Empfänger und im Protokoll des
    Webspace den technischen Grund. Das Protokoll allein sagt nicht, wer
    gemeint war — und genau das war die Zusage.

    *Nachgemessen:* `grep -rn "toEmail" server/smtp.php` trifft **keinen**
    `error_log()`-Aufruf mehr; `tools/mailprobe/` Abschnitt 13 löst einen
    Fehlschlag aus und prüft das erzeugte Protokoll (**108 Byte, 1 Zeile,
    kein „@", eine Kennung**). Die übrigen `error_log()`-Aufrufe in
    `email_lib.php`, `pair.php` und `reset_request.php` sind durchgesehen —
    sie nennen weder Adresse noch Token („Hinweismail an die alte Adresse
    ging nicht weg").

    *Ein Fund am selben Ort:* `smtp_letzter_fehler()` konnte den Grund des
    **vorigen** Versuchs liefern — der Merker wurde erst nach der
    Adressprüfung geleert. Eine Kennung, die auf eine andere Nachricht
    zeigt, ist schlimmer als gar keine; behoben in Web 20.9.0.

203. **`api/export_data.php` gibt JSON roh aus — ohne `Cache-Control:
    no-store`.** *Aufgenommen 16.09.2026 (Nebenfund der
    Zentralisierungsanalyse); Zuordnung P5a AP4a (Nachtrag).* Zwei Stellen
    geben mit `header('Content-Type: application/json')` + `echo` aus, ohne
    den Kopf, den `json_out()` (M3-11) begründet zentral setzt. Der Export
    liefert **Spurpunkte** — ein Zwischenspeicher darf sie nicht behalten.

    **Erledigt am 16.09.2026 mit Web 20.9.1 (P5a/AP4a, E-P5a-38).** Drei
    Funktionen in `db.php`, eine Stelle: `json_kopf()` setzt den Satz,
    `json_roh_out()` gibt fertigen Text aus, `json_out()` ruft
    `json_roh_out()`.

    **Es waren sieben Stellen, nicht drei.** Der Punkt nannte
    `api/export_data.php` (2×), `api/backup_data.php` und
    `api/adminbackup_freigabe.php`. Beim Nachzählen kamen `auth_salt.php`,
    `jobs.php` und `pair.php` dazu — **derselbe Mangel**, und bei zweien
    wiegt er schwerer als beim Ausgangspunkt: `auth_salt.php` liefert das
    Salt der Schlüsselableitung **je Konto** und ist unangemeldet
    erreichbar, `pair.php` nennt die maskierte Adresse des Kontos. Beiden
    fehlten außerdem `nosniff` und `Referrer-Policy`.

    **`json_kopf()` gibt es, weil `pair.php` an zwei Stellen antwortet und
    dann weiterarbeitet** — es schließt die Antwort ab und reiht erst danach
    die Hinweismail ein, weil die Uhr auf das `ok` wartet. Ein `never`
    schließt diese Stelle aus.

    **Eine Ausnahme, benannt:** `wartung_lib.php` setzt seinen Satz weiter
    selbst; die Wartungsseite ist ausdrücklich ohne Datenbank gebaut und darf
    `db.php` nicht laden. `no-store` steht dort trotzdem.

    *Nachgemessen im Browser gegen die lokale Installation:*
    `api/export_data.php` mit drei echten Einsatz-IDs → **HTTP 200, 68 820
    Byte Spurpunkte**, `cache-control: no-store`, `nosniff`;
    `api/backup_data.php?teil=kopf` → **200, 19 603 Byte**, dieselben
    Kopfzeilen; `auth_salt.php`, `jobs.php` und `pair.php` je in ihrem
    Fehler- **und** Erfolgszweig, alle mit vollem Satz. Und:
    `grep -rn "Content-Type: application/json" server/` trifft genau **zwei**
    Codezeilen — `db.php` und `wartung_lib.php`.

    *Eine Nebenwirkung, ausgeschrieben:* `json_out()` schickt jetzt
    `application/json; charset=utf-8` statt `application/json`. RFC 8259
    definiert für `application/json` keinen charset-Parameter; kein Client
    bricht daran, vier der sieben Stellen schickten ihn ohnehin, und die Uhr
    übergeht ihn ganz (`:responseType => HTTP_RESPONSE_CONTENT_TYPE_JSON`).

205. **`session.use_strict_mode` fehlt auf den Anmeldewegen.** *Aufgenommen
    16.09.2026; Zuordnung P5a AP4a (Nachtrag).* Gesetzt nur in
    `install.php` und `wiederherstellen.php`, nicht in `auth_guard.php`,
    `login.php` und `session_lib.php` — also nicht auf den Wegen, die eine
    echte Anmeldesitzung tragen. Der Schutz gegen Session-Fixation hing
    damit an der `php.ini` des Hosters.

    **Erledigt am 16.09.2026 mit Web 20.9.1 (P5a/AP4a, E-P5a-38).** Die Zeile
    steht jetzt vor **allen sieben** `session_start()`-Aufrufen; dazu kamen
    `pw_handling.php` (die Sitzung, die das Passwort-Token trägt) und
    `rechtstext_seite.php` (die fragt nur, erzwingt nicht — die Zeile steht
    trotzdem, damit die Regel keine Ausnahme hat, an der sie später jemand
    aufhängt).

    **Kein `sitzung_starten()`-Helfer** — der ist Schritt 15 (Nr. 202
    Paket 3). Hier steht nur die Zeile.

    *Nachgemessen, mit Gegenprobe:* Auf dem Prüfstand steht
    `session.use_strict_mode` in der `php.ini` auf **Off**. **Ohne** die
    Zeile nimmt `login.php` eine frisch erfundene Kennung an und schickt
    **gar kein `Set-Cookie`** zurück; **mit** ihr verwirft es sie und vergibt
    eine neue. Beide Läufe mit jeweils frischer Zufallskennung — eine schon
    benutzte ist dem Server bekannt und wird auch mit der Härtung
    angenommen, und der zweite Lauf maß deshalb beim ersten Versuch das
    Gegenteil des ersten.

    **Neu dazu: `tools/sitzungshaertung/`, in Stufe 1.** Die Zeile ist
    unscheinbar und steht neben dem Aufruf, den sie schützt; ein neuer Weg,
    der sie vergisst, sieht genauso aus wie einer, der sie hat. Gemessen mit
    dem Tokenizer, nicht mit `grep` — die erste Fassung meldete zwei
    Befunde, und beide waren Kommentarzeilen über das Werkzeug selbst.
    **Selbstprobe 8/8; im Lauf 108 Dateien, 7 echte Aufrufe, 0 ohne
    Härtung.**

217. **Ein Schritt der Kette, der nie gelaufen ist, ist ungeprüfter Code.**
    *Aufgenommen 17.09.2026 aus einer unabhängigen Durchsicht aller
    Werkzeugaufrufe der drei Arbeitsläufe (25 Befunde geprüft, 14 bestätigt);
    erledigt am selben Tag.*
    Am 16./17.09.2026 sind **drei** Aufrufe beim jeweils ERSTEN echten Lauf
    gescheitert — und alle drei hatten gültiges YAML und saubere
    Shell-Syntax:

    - `kreislauf.py` ohne das Pflichtargument `--art`, mit einem `--passwort`,
      das es nicht gibt, und als **ein** Aufruf, obwohl der Schrittname zwei
      verspricht.
    - `pruefstand.sh aufbau` ruft `apt-get` **ohne `sudo`** — im Container ist
      man root, auf einem Läufer nicht.
    - `aufnehmen.mjs` mit `--konto`/`--passwort`, die es dort nie gab; das
      Werkzeug verwarf sie **still** und meldete sich mit den eingebauten
      Vorgaben an.

    **Alle drei behoben**, zwei davon an der Wurzel: `aufnehmen.mjs` bricht
    jetzt mit Rückgabewert 2 ab, wenn ein Schalter unbekannt ist, und
    `pruefstand.sh` hat mit `aufbau-uebersetzen` einen Weg ohne `sudo`.

    **Dazu das Prüfmittel, das der Eintrag vorgeschlagen hat:
    `tools/kettenaufrufe/`.** Es liest jeden `run:`-Block der drei
    Arbeitsläufe, findet darin die aufgerufenen Werkzeuge, liest deren
    Schnittstelle aus dem Quelltext (`add_argument`, die Handparser über
    `wert('--x'`/`flag('--x'`, `BEKANNT`-Mengen, `case`-Zweige, `$argv`) und
    hält Aufruf gegen Schnittstelle. **Es führt kein Werkzeug aus** — deshalb
    hängt es in Stufe 1 und kostet nichts.

    *Gemessen beim Einbau:* **3 Arbeitsläufe, 25 Aufrufe geprüft, 0 Befunde,
    0 ungeprüft**; Selbstprobe **10 von 10** (fünf Fälle, die anschlagen
    müssen, fünf, die es nicht dürfen). Gegen den Stand von **vor** den
    Behebungen oben hätte es alle drei Fehler genannt — das ist die
    Gegenprobe, ohne die die Null nichts sagt.

    **Was offen bleibt, steht in der `LIESMICH.md` des Werkzeugs und ist
    keine Nachlässigkeit, sondern seine Grenze:** Es prüft Schnittstellen,
    nicht Verhalten. Ein Aufruf mit lauter gültigen Schaltern, der das
    Falsche tut, kommt durch. Und was im Quelltext nicht steht — ein Schalter,
    den ein Werkzeug erst zur Laufzeit aus einer Datei liest —, kann es nicht
    wissen; solche Werkzeuge zählt es als **ungeprüft** und sagt die Zahl
    dazu, statt sie als Null auszuweisen.

218. **Die Integritätswache war für den Fall blind, für den es sie gibt.**
    *Aufgenommen und behoben am 17.09.2026 (Fund 27); gefunden von ihrer
    eigenen Selbstprobe, die „30 Erwartungen, 2 nicht erfuellt" meldete.*
    `FORM_RE` las den Tag-Rumpf als `[^>]*` und endete am ersten `>`. Seit
    Web 20.10.0 (P5a/AP6) trägt das Anmeldeformular
    `data-sperre-rest="<?= (int)$sperreRest ?>"` — der Tag brach mitten im
    PHP-Ausdruck ab.

    **Der Schaden war nicht der zerschnittene Tag.** Was übrigblieb, enthielt
    `<?=`, galt damit als **unbestimmt**, und für jedes unbestimmte Stück der
    Quelle darf die Auslieferung eines haben, das die Quelle nicht kennt. In
    genau diesen Freiraum passte ein `action="https://boese.example/"` am
    **Anmeldeformular** — der Fall „jemand leitet die Passwörter um", für den
    diese Wache gebaut wurde. Die Umlenk-Prüfung fängt ihn nicht mit ab: Sie
    sieht `formaction|formmethod|formtarget|formenctype`, nicht das `action`
    am `<form>` selbst.

    **Behoben:** `FORM_RE` nutzt jetzt `TAG_REST` (wie `SKRIPT_RE` seit
    Fund 23), und `form_paare()` maskiert nur die Attributwerte, die in der
    Quelle wirklich aus PHP kommen — auf beiden Seiten. Der Rest des Tags
    bleibt Wort für Wort vergleichbar. Selbstprobe danach: **30 von 30**.

    ### Nachtrag vom 17.09.2026 — und eine Berichtigung an diesem Eintrag

    **Dieser Eintrag hat sich selbst auf eine Regel berufen, die es nicht
    gab.** Er schrieb: *„`CLAUDE.md` 6 nennt bereits zwei und schreibt vor,
    dass ein Werkzeug, das Markup aus Quelldateien liest, den Tag-Rumpf als
    `(?:<\?(?:php\b|=).*?\?>|[^>])*` lesen muss."* Nachgesehen am 17.09.2026:
    In `CLAUDE.md` stand davon **kein Wort**, und in der Git-Historie der
    Datei auch nie (`git log -S 'Tag-Rumpf' -- CLAUDE.md` → leer). Die Regel
    existierte nur im Kopf dessen, der sie zweimal angewandt hatte. Genau
    deshalb wurde sie beim dritten Muster übersehen — sie stand nirgends, wo
    man sie liest, bevor man ein Muster schreibt. **Jetzt steht sie in
    `CLAUDE.md` 6**, ausdrücklich für *jedes* Tag-Muster.

    Die übrigen Muster sind nachgezogen, und zwar mit der Angabe, was die
    Umstellung jeweils wert war — das ist bei den vieren nicht dasselbe:

    - **`SRC_RE` war eine echte Zeitbombe.** `[^>]*?` kommt am `?>` nicht
      vorbei; `<script<?= kopf_nonce_attr() ?> src="a.js">` hätte **keinen**
      Treffer gegeben, und `SKRIPT_RE` hätte den Tag über `TAG_REST` richtig
      als Fremdskript erkannt und übersprungen — der Verweis wäre weder Block
      noch Fremdskript gewesen: **unsichtbar**. Gemessen: 117 `<script>`-Tags,
      82 davon mit `src`, **0** davon mit PHP vor dem `src` — die Zeile gibt
      es heute nicht, sie ist nur jederzeit schreibbar. Neue Selbstprobe dazu,
      und die Gegenprobe zeigt: Mit dem alten Muster fällt sie um.
    - **`BASE_RE`, `META_RE`, `EINBETT_RE` waren es nicht.** Ein `[^>]*` endet
      am `>` des PHP-Schlusses, also **nach** dem `<?=` — das abgeschnittene
      Stück trägt den PHP-Anfang mit sich, gilt weiter als unbestimmt und wird
      durchgelassen. Nachgemessen, mit beiden Mustern, an `<base>` mit PHP im
      Rumpf: gleiches Ergebnis. Umgestellt wurden sie trotzdem, damit niemand
      nachmessen muss, welches der acht Muster dieser Datei die kurze Form
      verträgt.
    - **`tools/stilvergleich/proben.py`** las `<script>` aus der rohen
      PHP-Quelle. Von 110 Blöcken begannen die aus 12 Dateien mit einem
      überzähligen `>`, und aus `<script src="<?= asset(…) ?>"></script>`
      wurde ein **Scheinblock mit dem Inhalt `">`**. Für die
      Zeichenketten-Ernte war das folgenlos — aber nur, weil dort niemand ein
      `>` am Blockanfang braucht.
    - **`tools/vollstaendigkeit/pruefen.py`** (`<svg`): 2 Treffer, mit beiden
      Mustern dieselben. Vorsorge, kein Fund.
    - **`tools/wortliste/zerlegen.py`** bleibt bei `[^>]*` — **und das ist
      richtig**: `_BLOCK` läuft über `nur_html`, den Text, aus dem
      `_html_bereiche()` die PHP-Inseln vorher durch Leerzeichen ersetzt hat.
      Dort gibt es kein `?>`. Der Grund steht jetzt als Kommentar daneben,
      damit die Zeile beim nächsten Durchgang nicht „mitkorrigiert" wird.

    Muster über **gelieferte** Antworten (`tools/referenzdatensatz/`) sind
    nicht betroffen: Dort ist das PHP ausgeführt. Selbstprobe der
    Integritätswache danach: **32 von 32**.

219. **Der Kreislauftest hielt die Jobs über die KOMMANDOZEILE an — gegen eine
    ferne Installation geht das nicht.**
    *Aufgenommen und behoben am 17.09.2026, gefunden vom ersten
    Auslieferungslauf nach dem Merge von PR #51.*

    Der Lauf kam bis `Kreislauf edbak — Zielkonto umlauf-edbak@gen-em.org` und
    brach dann ab:

    ```
    RuntimeError: jobs.php --pause 1800 fehlgeschlagen:
      require_once(.../server/config.php): Failed to open stream
    ```

    `kreislauf.py` fährt `php server/jobs.php --pause 1800`, also die
    **lokale** Kommandozeile. Auf einem GitHub-Läufer gibt es dort keine
    `config.php` und keine Datenbank. **Der Aufruf war nicht falsch
    geschrieben** — das Werkzeug nahm an, `--basis` sei derselbe Rechner, auf
    der es läuft. Diese Annahme stimmte, solange nur von Hand gemessen wurde.

    **Die Pause ist nicht verzichtbar.** Ohne sie dünnt der Verdichtungsjob
    die wiederhergestellten Spuren aus — die Einsätze sind alt, der Job hält
    sie für reif —, und der Vergleich misst „hat der Job dazwischen
    zugeschlagen" statt „kommt zurück, was hineinging". Nachgemessen steht es
    seit S2/AP3 im Kopf von `kreislauf.py`: ein Lauf ohne Pause verdichtete
    **125 Spuren** des Umlaufkontos. Der Schritt einfach ohne Pause laufen zu
    lassen, hätte eine grüne Zahl ohne Aussage ergeben.

    **Behoben mit Web 20.16.0**, auf drei Ebenen:

    - `jobs.php` nimmt die Aktion `pause` (`sekunden=N`, 0 hebt auf) — hinter
      demselben `jobs_pause()` wie die Kommandozeile und die beiden Knöpfe
      unter Betrieb → Hintergrundjobs. Kein vierter Mechanismus, ein vierter
      Aufrufer.
    - `tools/kette/tor.py` bekommt den vierten Unterbefehl `pause`. Dort und
      nicht im Kreislauftest, weil diese Datei **der eine Client** von
      `jobs.php?aktion=…` ist; eine zweite `urllib`-Zeile wäre ein zweiter
      Weg, den niemand pflegt.
    - `kreislauf.py` bekommt `--jobs-token`. **Mit Token über HTTP, ohne Token
      weiter über die Kommandozeile** — wer auf seinem Rechner misst, merkt
      nichts. Scheitert der lokale Weg, nennt die Fehlermeldung jetzt den
      Schalter und diese Nummer, statt nur „Failed to open stream" zu zeigen.

    **Zuarbeit:** Die Umgebung `staging` trägt dafür `JOBS_TOKEN`, denselben
    Namen wie `produktion`, aber den Wert **dieser** Installation. Fehlt er,
    wird der Schritt übersprungen und gesagt — nicht still auf den lokalen Weg
    zurückgefallen.

    **Was daran für die Prüfmittel bleibt.** `tools/kettenaufrufe/` konnte das
    nicht fangen, und das ist kein Versäumnis: Es prüft **Schnittstellen,
    nicht Verhalten**, und sagt in seiner `LIESMICH.md` ausdrücklich, dass es
    nicht weiß, ob ein Pfad auf dem Läufer existiert. Die Lehre ist
    dieselbe wie bei Nr. 217, eine Stufe tiefer: Ein Aufruf mit lauter
    gültigen Schaltern kann trotzdem eine Annahme über seine Umgebung
    mitbringen, die dort nicht gilt. Dagegen hilft kein Muster über den
    Quelltext, sondern nur der Lauf — und deshalb ist es richtig, dass Stufe 2
    ihn fährt.

    *Nachgemessen beim Beheben:* `tor.py --selbstprobe` **10 von 10** (vorher
    5), und die Gegenprobe des neuen Prüfmittels zeigt, dass ein Tippfehler im
    neuen Schalter auffällt: `--jobs-tokn` → **1 Befund**, mit der Liste der
    bekannten Schalter.

    ### Nachtrag vom 17.09.2026 — was eine unabhängige Durchsicht an der
    ### Behebung gefunden hat

    Fünf Blickwinkel über den Diff, jeder Befund danach von einem eigenen
    Durchgang zu **widerlegen** versucht: **19 haben standgehalten**. Drei
    davon brechen Zusagen, die Web 20.16.0 selbst aufgestellt hat. Behoben mit
    **Web 20.16.1**.

    **Die Ziffernprüfung war die falsche.** `!is_numeric($roh) || (int)$roh < 0`
    ließ `sekunden=-0.5` durch — numerisch ja, `(int)"-0.5"` ist 0, 0 ist nicht
    kleiner als 0. Der Aufruf hob eine laufende Pause auf und quittierte es mit
    `ok`, also genau das, wogegen der Absatz darüber stand. Nachgemessen gegen
    eine echte Installation: HTTP 200, Pause weg. Jetzt `^\d+$`.

    *Warum die eigenen Proben es nicht fanden:* geprüft waren `-5` und `abc`.
    **Beide scheitern schon an der vorherigen Bedingung** — die Lücke lag
    zwischen ihnen. Zwei Proben an den Rändern sagen nichts über die Mitte.

    **`rufen()` verschluckte jede Fehlerantwort, und das ist älter als diese
    Änderung.** Eine `HTTPError` ist eine `URLError` und fiel in den
    Netzfehler-Zweig; aus einer 400 mit Begründung wurde `_fehler`. Damit war
    der Abbruchzweig in `backup_tor()` **nie erreichbar**: Der Kommentar dort
    sagt „ein falsches Token … wird beim vierzigsten Mal nicht anders" und
    bricht bei `error` ab — `error` kam nie an. Das Tor fragte vierzigmal, gut
    dreizehn Minuten, und meldete „kein fertig" statt „falsches Token".

    **Der neue Selbstprobenfall bewies nichts.** Er rief `adresse_bauen()`
    unmittelbar mit einem von Hand geschriebenen Feld auf und maß `urlencode`,
    nicht den Aufrufweg. Streicht man `felder=` in `main()` oder reicht
    `rufen()` es nicht weiter, blieb die Probe grün — beides nachgemessen.
    Jetzt fährt der Fall den ganzen Weg und fällt bei beiden Mutationen um
    (11 → 10 erfüllt, 1 offen).

    **Dazu:** Die Selbstprobe läuft jetzt in **Stufe 1** und nicht mehr nur im
    Produktionslauf — ihre fünf neuen Fälle bewachen `pause`, und das läuft in
    Stufe 2. Der `JOBS_TOKEN`-Riegel steht vor `pip` und dem
    Chromium-Download. `--jobs-token` liest nicht mehr ersatzweise die
    Umgebungsvariable (sonst ginge ein exportiertes Produktiv-Token gegen die
    lokale Installation). Die Kopfzeile meldete „fünf Lagen" und fuhr elf. Die
    Geheimnis-Tabelle in `docs/Technik.md` war von einem eingeschobenen Absatz
    zerrissen. `tools/kette/LIESMICH.md`, `docs/Rahmenplan.md` 6a (Schritt 10)
    und Prüfpunkt P6 sind nachgezogen.

    **Und eine Grenze, benannt statt geschlossen:** `tools/kettenaufrufe/`
    sieht nur Aufrufe in `run:`-Blöcken. Der neue Aufruf `kreislauf.py` →
    `tor.py` steht in Python und liegt außerhalb seiner Reichweite; wer
    `--sekunden` umbenennt und den Aufrufer vergisst, bekommt von ihm weiter
    „0 Befunde". Gedeckt ist diese eine Stelle stattdessen von Fall 6 der
    Selbstprobe. Das steht in beiden LIESMICH-Dateien.

    **Die Lehre, und sie ist dieselbe wie bei Nr. 217 und 218, eine Stufe
    tiefer:** Eine Probe, die ich selbst schreibe, prüfe ich mit einer
    Mutation — sonst weiß ich nicht, ob sie misst oder nur grün ist. Alle
    drei neuen Fälle sind jetzt so belegt.

220. **`aufnehmen.mjs` schaltet den Wartungsmodus über eine LOKALE Datei — und
    das ist der dritte Fall derselben Annahme.**
    *Aufgenommen 17.09.2026 aus dem ersten Bilderlauf gegen Staging, der ein
    Demo-Konto hatte.*

    Der Lauf hat alle 50 Seiten fotografiert — 400 Einzelbilder, 50
    Kontaktbögen, alle drei Rollen trugen. Rot wurde er unter anderem hier:

    ```
    07-wartungsseite    kein Überlauf  ·  8 Konsolenfehler
    OHNE BILD: 8 Aufnahmen — 8× Seite leitete auf die Anmeldung um
    ```

    `seiten.json` führt diesen Eintrag mit `"wartung": true` und
    `"status": 503`: Das Werkzeug soll den Wartungsmodus einschalten,
    `index.php` in acht Breiten aufnehmen und ihn wieder ausschalten.
    Eingeschaltet wird er in `aufnehmen.mjs:908` so:

    ```js
    const WARTUNGSDATEI = join(WURZEL, 'server', 'wartung.lock');
    ```

    Also durch **Anlegen einer Datei im eigenen Arbeitsbaum**. Auf einem
    GitHub-Läufer entsteht damit eine Datei im Checkout; Staging bleibt
    offen, `index.php` leitet den nicht angemeldeten Aufruf zur Anmeldung um,
    und acht Aufnahmen bleiben ohne Bild.

    **Das ist dieselbe Annahme wie in Nr. 219**, nur an einer anderen Stelle:
    Ein Werkzeug nimmt an, `--basis` sei der Rechner, auf dem es läuft. Bei
    `kreislauf.py` war es die Job-Pause, hier ist es der Wartungsschalter.
    Beim dritten Mal ist es keine Einzelheit mehr, sondern ein Muster.

    *Zu tun, und der Weg liegt schon da:* `tools/kette/tor.py` kann
    `wartung-an` und `wartung-aus` über `jobs.php?aktion=…`, und seit Web
    20.16.0 steht `JOBS_TOKEN` auch in der Umgebung `staging`. `aufnehmen.mjs`
    bräuchte also nur ein `--jobs-token` und denselben Zweig wie
    `kreislauf.py`: mit Token über HTTP, ohne Token weiter über die lokale
    Datei. **Und einen Riegel:** Ist `--basis` nicht local und kein Token da,
    darf der Eintrag nicht still als „umgeleitet" durchlaufen, sondern muss
    sagen, dass er nicht gemessen werden konnte.

    *Zu prüfen wäre dabei auch, ob es weitere solche Stellen gibt* — ein
    Werkzeug, das gegen `--basis` misst und dabei in `server/` schreibt oder
    liest, ist immer verdächtig.

    ### Behoben am 17.09.2026 mit Web 20.16.2

    **Zwei Wege, wie bei `kreislauf.py`:** mit `--jobs-token` über
    `jobs.php?aktion=wartung_an`, gefahren von `tools/kette/tor.py`; ohne
    Token weiter über die Datei. Wer örtlich misst, merkt nichts.

    **Dazu ein Riegel:** Ist die Basis nicht diese Maschine und fehlt das
    Token, bricht der Lauf **vorher** ab und nennt beide betroffenen Seiten.
    Ohne ihn liefe er weiter und legte Bilder der Anmeldeseite ab.

    **Es sind zwei Seiten, nicht eine** — das kam erst beim Beheben heraus,
    weil der Riegel sie aufzählt: `07-wartungsseite` **und**
    `46a-betrieb-updates-wartung`. Die zweite ist die unangenehmere: Ihre acht
    Bilder entstehen, zeigen aber den Wartungsbalken nicht, und der Lauf
    meldet dafür „kein Überlauf". Eine stille Fehlmessung fällt nicht auf;
    acht fehlende Bilder schon.

    *Gemessen, vorher und nachher:*

    | | vorher (Kette, 17.09.) | nachher (örtlich, mit Token) |
    |---|---|---|
    | `07-wartungsseite` | **0 Bilder**, 8 Konsolenfehler, „leitete auf die Anmeldung um" | **8 Bilder**, 0 Konsolenfehler, RC 0 |

    Dazu der Beleg auf HTTP-Ebene: `index.php` antwortet **ohne** Wartung mit
    **302**, **mit** Wartung mit **503** — und 503 ist, was `seiten.json` für
    diesen Eintrag erwartet.

    **In der Kette belegt am 17.09.2026**, Auslieferungslauf auf `main`
    (`e5844c4`, Stufe 2 vollständig grün): **400 Einzelbilder, 50
    Kontaktbögen, 0 Überlauf, 0 Konsolenfehler, 0 falsche Knopfhöhen** — und
    **kein „OHNE BILD"** mehr. Beide Wartungsseiten stehen mit „kein
    Überlauf" im Protokoll (`46a-betrieb-updates-wartung` 13:49:29,
    `07-wartungsseite` 13:50:17). Der Wartungsmodus ist danach wieder aus;
    ein hängender Schalter hätte den Lauf seit Web 20.16.3 selbst rot
    gefärbt. Der Rückfallweg ohne Token ist gegengeprüft (8
    Bilder, `wartung.lock` sauber aufgeräumt), und nach dem Lauf steht die
    Installation wieder offen (`zustand.wartung.aktiv = false`).

    ### Nachtrag vom 17.09.2026 — was die Durchsicht an der Behebung fand

    Zehn Befunde, jeder von einem zweiten Durchgang zu widerlegen versucht.
    **Der erste wiegt schwerer als der Fehler, für den die Behebung
    geschrieben war.**

    **Ein misslungenes Ausschalten hätte Staging geschlossen — und der Lauf
    hätte grün gemeldet.** Der `catch` setzte `wartungVonUns` auf falsch und
    entwaffnete damit jeden weiteren Versuch: `wartungAus()` läuft nach jeder
    Seite und noch einmal am Prozessende, beide kehrten danach sofort um. Der
    Rückgabewert kannte den Fehlschlag nicht, der Bericht auch nicht. Der
    Kommentar darüber versprach das Gegenteil („das muss auffallen"). Behoben:
    Die Merkung bleibt stehen, und ein hängender Wartungsmodus färbt den Lauf
    rot.

    **Die Merkung stand hinter dem Einschalten.** Über eine Datei ist das
    gleichgültig — `writeFileSync` schreibt oder wirft. Über HTTP gibt es
    einen dritten Ausgang: ausgeführt, aber nicht bestätigt. Sie steht jetzt
    davor.

    **Der Abbruch bei fehlendem Token war die bequemere und schlechtere
    Zeile.** Zwei von fünfzig Seiten hängen am Wartungsmodus; ein Abbruch
    würfe achtundvierzig messbare weg. Jetzt fallen die zwei aus, mit Grund,
    und der Lauf endet rot.

    *Gegengeprüft gegen eine echte Installation:* mit gültigem Token 8 Bilder,
    RC 0, Wartung danach aus; mit falschem Token 0 Bilder, RC 1, Grund bei der
    Seite („Zustand nicht abfragbar … `error: token`"), Installation
    unangetastet.

    **Und zum zweiten Mal habe ich in `docs/Technik.md` einen Absatz
    zerrissen**, indem ich neuen Text mitten hineinschob — diesmal las sich
    „Sie halten die Hintergrundjobs an" als Aussage über den Bilderlauf. Beim
    ersten Mal war es die Geheimnis-Tabelle (Nr. 219). *Merkposten für die
    nächste Einfügung: erst den Absatz zu Ende lesen, dann einfügen.*

215. **Fünf Werkzeuge begründen ihre Arbeitsweise mit der gelöschten
    `deploy.yml`.**
    *Aufgenommen 16.09.2026 aus einer unabhängigen Durchsicht des
    P5a-Abschlusses.*
    `deploy.yml` ist mit Web 20.4.0 gelöscht worden; die Kette heißt seither
    `auslieferung.yml` und hat **zwei** FTPS-Schritte statt einem. In
    `docs/Technik.md` sind die Erwähnungen berichtigt — in den Werkzeugen nicht:
    `tools/integritaetswache/wache.py` erklärt ihren Zweck mit „Der Deploy
    (`.github/workflows/deploy.yml`) synchronisiert `server/` byteweise per
    FTPS", und vier weitere Stellen ähnlich.

    **Folgenlos für den Lauf** — es sind Kommentare, kein Code. Aber sie sind
    die Erklärung, warum es das Werkzeug gibt, und wer sie liest, sucht eine
    Datei, die es nicht mehr gibt. Dazu stimmt die **Einzahl** nicht mehr: Wer
    „der Deploy" liest, denkt an einen Weg, und es sind zwei mit
    unterschiedlichen Toren.

    *Zu tun:* Die fünf Stellen auf `auslieferung.yml` umschreiben und dabei die
    Zweiwegigkeit nennen. **Kein eigenes Paket** — Beifang, sobald jemand das
    jeweilige Werkzeug ohnehin anfasst (R83-Muster).

    **Erledigt am 17.09.2026 mit Web 20.16.4.** Es waren **sechs** Stellen und
    nicht fünf — beim Nachzählen kam `tools/wartungsprobe/LIESMICH.md` dazu:
    `server/adminbackup_lib.php`, `tools/wortliste/LIESMICH.md`,
    `tools/integritaetswache/wache.py`, `tools/integritaetswache/LIESMICH.md`,
    `tools/wartungsprobe/probe.php` und dessen `LIESMICH.md`. Jede nennt jetzt
    `auslieferung.yml`, und wo es auf die **Zweiwegigkeit** ankommt, steht sie
    dabei — in `adminbackup_lib.php` ausdrücklich: „beider FTPS-Schritte …
    Seit der Kette gilt das zweimal: für Staging und für Produktiv." Die
    Herkunft bleibt als „bis Web 20.3.0: `deploy.yml`" daneben stehen, in
    derselben Schreibweise wie in `docs/Technik.md`.

    Die Stelle in `adminbackup_lib.php` ist der Grund für die Versionsstufe:
    Sie liegt unter `server/`, und damit ist die Änderung nicht mehr eine, die
    nur `tools/` und `docs/` anfasst (`CLAUDE.md` 2).

    *Nicht angefasst, mit Absicht:* die Erwähnungen in `docs/konzepte/`, in
    diesem Backlog und in `docs/CHANGELOG.md` — das sind Protokolle eines
    Standes, und wer sie umschreibt, fälscht die Geschichte, statt sie zu
    berichtigen. Ebenso bleiben die drei Stellen in den Arbeitsläufen selbst,
    die den alten Namen als Historie nennen („Bis zu dieser Fassung hiess
    diese Datei `deploy.yml`").

208. **Der Job-Katalog und das Jobregister in `docs/Technik.md` beschreiben
    den Aufräumjob nicht mehr.** Berichtigt am 16.09.2026 in P5a/AP8 — der
    Eintrag steht hier trotzdem, weil die **Ursache** bleibt: `job_aufraeumen()`
    hat inzwischen **zwölf** Schritte, und jede der beiden Beschreibungen ist
    eine von Hand gepflegte Aufzählung, die bei jedem neuen Schritt
    mitgeschrieben werden muss. Sie hinkte in AP5 schon einmal drei Pakete
    hinterher (der Kommentar im Katalog sagt es selbst), und jetzt wieder: Das
    Register in `docs/Technik.md` nannte **sechs von zwölf**.

    *Erledigt am 20.09.2026 in Web 20.26.0 (Schritt 16, Sitzungsablage): Die
    sichtbare Beschreibung im Katalog wird aus den Schlüsseln der Schritte
    erzeugt, und `tools/jobregister/pruefen.php` zählt das Register in
    `docs/Technik.md` nach — beides genau das, was der Vorschlag unten
    verlangt. Der Befundtext darunter bleibt stehen; er ist der Befund, nicht
    der offene Stand.*

    **Was beim Beheben gemessen wurde** (20.09.2026, gegen `862ca7f`): **11
    Jobs** im Katalog gegen **9** im Register — `mail` und `konto_verfall`
    fehlten, und die Fußnote an jener Stelle behauptete seit P5a/AP11, `mail`
    sei nachgetragen. **16 Aufräumschritte** im Code gegen „dreizehn" im
    Register, und die **sichtbare** Beschreibung nannte **15 von 16**
    („Sperrliste gelöschter Kennungen" fehlte). Damit war der Punkt beim
    dritten Anlauf, und zweimal davor waren nur die Zahlen berichtigt worden.

    **Zwei Hälften, und die zweite ist die, auf die es ankommt.** Erzeugt wird
    die sichtbare Beschreibung aus `array_keys(job_aufraeumen_schritte())` —
    sie kann nicht mehr altern. Die Registerzeile in `docs/Technik.md` bleibt
    Prosa, weil sie mehr sagt als eine Liste; sie wird nachgezählt, mit dem
    Tokenizer und ohne Installation, damit der Lauf in Stufe 1 hängen kann.
    Erster Lauf: **7 Befunde**, danach **0**; Selbstprobe **9 von 9**.

    **Eine Handbreit bleibt offen, und sie wird hier genannt statt verschwiegen:**
    Der Eintrag in `.github/workflows/pruefung.yml` gehört zu Kette II —
    Schritt 16 fasst `.github/` nicht an. Bis der Lauf dort hängt, zählt das
    Werkzeug nur, wenn es jemand fährt. Die zwei Zeilen dafür stehen in
    `docs/Technik.md` 6.2, zusammen mit diesem Vorbehalt.

    **Beim Gegenlesen fielen zwei Listen derselben Art auf.** Der
    **Werkzeugbaum** führte 46 Ordner, auf der Platte lagen 48 —
    `tools/wegwerfdomains/` fehlte seit Web 20.22.0, also genau der Fall, den
    der Nachtrag unten für `tools/containerprobe/` beschreibt. Und die
    Ausnahmeliste des Transports in `docs/Technik.md` 6.5 nannte fünf
    Einträge, während die Kette sieben führte (`ueberlast.json`,
    `install.php`). Beides nachgetragen; für beide Listen gibt es weiterhin
    kein Prüfmittel.

    **Vorschlag:** Die sichtbare Beschreibung aus den Schlüsseln des
    Schrittarrays erzeugen, statt sie danebenzuschreiben — `job_aufraeumen()`
    kennt seine Schritte, sie heißen dort bereits „Kopplungssitzungen",
    „Sperrereignisse", „Geraetevermerke". Dann kann sie nicht mehr altern.
    Betrifft `jobs_lib.php` (Katalog) und `docs/Technik.md` (Jobregister);
    für das Dokument wäre ein Prüfmittel nötig, das die Zahl nachzählt — sonst
    wandert das Problem nur eine Ebene weiter.

    *Aufgenommen 16.09.2026 in P5a/AP8. Gezählt: zwölf Schritte in
    `job_aufraeumen()`, sechs in der Registerzeile, zehn in der Beschreibung
    des Katalogs (beide inzwischen berichtigt).*

    **Nachtrag 16.09.2026 (P5a/AP12): dieselbe Ursache, dritte Stelle.** Der
    **Werkzeugbaum** in `docs/Technik.md` ist ebenso eine von Hand geführte
    Aufzählung, und beim Abschluss von P5a fiel auf, dass `tools/containerprobe/`
    darin fehlte — seit **Web 12.0.0** (S2/AP6), also fünf Monate lang.
    Nachgetragen. Gezählt mit einem Fünfzeiler, der die Ordner unter `tools/`
    gegen die Einträge im Baum hält: **44 auf der Platte, 43 im Baum**, danach
    44 zu 44. Genau so ein Fünfzeiler ist das Prüfmittel, das dieser Punkt
    oben verlangt — er gehört in Stufe 1 der Kette, nicht in eine Sitzung, die
    zufällig hinsieht.

282. **Das Spaltenregister ging rot in Stufe 1.**
    *Gemessen 22.09.2026 auf dem Stand von Schritt 15 selbst (PR #74), vor
    dem Merge. **Erledigt 23.09.2026** — von Schritt 15 selbst behoben.*
    `tools/spaltenregister/pruefen.php` hängt seit Schritt 15 AP6 als
    Prüfschritt in `pruefung.yml` und meldete dort zwei Dinge:

    - **1 Befund.** `start_sort` stand in der Abbildung von
      `api/suchindex.php`, aber nicht in `mf_missions_register()` — genau
      das Feld, das AP9 für die Sortierung des Nachtdienstes eingeführt
      hatte.
    - **Selbstprobe 15 von 16.** Der Fall „mf_spalten: Alias an" erwartete
      `uhr_gesperrt AS manual` **ohne Backticks**, während der Merge den
      Fix aus Web 20.26.3 nach `mf_spalten()` gezogen hatte. Der Fall hat
      damit getan, wofür es ihn gibt: angeschlagen, als sich die erzeugte
      Zeichenkette änderte.

    **Behoben von Schritt 15 selbst** (`f1bc9e6`) — in einem Commit nach
    dem Stand `1513615`, den PK-04 für seinen vorweggenommenen Merge
    benutzt hatte. Genau deshalb sah PK-04 den Befund: Wer einen Merge
    vorwegnimmt, nimmt den Stand eines **Zeitpunkts** vorweg, nicht den
    Endstand des Zweigs. Nach `git merge origin/main` nachgemessen:
    **Selbstprobe 16 von 16, Lauf 0 Befunde**, beide Rückgabewert 0.

    *Die Lehre steht nicht im Befund, sondern im Weg dorthin:* Ein
    vorweggenommener Merge findet echte Fehler, aber er findet auch
    Fehler, die die andere Seite gerade selbst behebt. Wer so misst, sieht
    vor dem Melden noch einmal nach, ob der Zweig weitergelaufen ist.

278. **Achtzehn Klassen des alten Stylesheets hatten weder eine Regel noch
    einen Eintrag auf der Streichliste.**
    *Gemessen 22.09.2026 mit PK-04/1b, **erledigt 23.09.2026 mit PK-04/5e**.*
    Sie waren der letzte Grund, warum die Vollständigkeitsprüfung gegen eine
    Schwelle lief statt gegen null.

    **Je Klasse ein Agent, danach eine unabhängige Gegenprobe** (E-PK-35).
    Das Ergebnis war nicht eine Sorte, sondern vier:

    | Urteil | Zahl | was daraus folgte |
    |---|---|---|
    | toter Rest | **8** | aus dem Markup entfernt, nichts sieht anders aus |
    | Skriptanker / Bezeichner | **6** | Streichliste mit `[bleibt]` — eine Regel wäre falsch |
    | ersatzlos ersetzt | **3** | Streichliste mit dem Baustein, der sie ablöst |
    | fehlende Regel | **1** | `.feld-gesperrt{color:var(--gedaempft)}` nachgetragen |

    **Meine erste Einschätzung war zu sechs Neunteln falsch**, und der Grund
    ist lehrreich: Ein naives `grep` findet keine Klasse, die zur **Laufzeit**
    zusammengebaut wird. `imp-dupe`, `imp-skipped`, `loc-inline` und
    `phase-marker` sah ich als „nirgends mehr" — sie entstehen aber aus
    `klasse += ' imp-dupe'`, `'klasse' => 'loc-inline'` und
    `className: 'phase-marker'`. `patfields` und `unlockbtn` leben als **ID**,
    nicht als Klasse.

    **Die eine nachgetragene Regel ist die kleinste mögliche:**
    `suche.php` schaltet `feld-gesperrt` an die Beschriftung des
    Altersfilters, solange die Patientendaten gesperrt sind — und es gab
    keine Regel, der Schalter tat nichts. Kaputt war nichts (der Zustand
    steht schon im `disabled`-Feld und im Hinweis `alterlock`), aber eine
    Beschriftung, die anders aussieht als ihr eigenes Feld, ist eine
    Ungereimtheit. Ein **vorhandenes** Token, keine neue Darstellung.

    **Die Vollständigkeit misst seither gegen null**, ohne `--hoechstens`
    (E-PK-16). Der Weg: 398 → 18 (PK-04/1b) → **0**.

279. **Vierzehn Zeichen standen im Markup, wo ein Symbol hingehört.**
    *Gemessen 22.09.2026 mit PK-04/1b, **erledigt 23.09.2026 mit PK-04/5** —
    und zwar durch Nachsehen, nicht durch Ändern.*
    **Der Punkt war falsch formuliert.** Alle vierzehn sind einzeln im Satz
    gelesen worden:

    | Sorte | Zahl | Beispiel |
    |---|---|---|
    | Kommentar, der ein Symbol **beschreibt** | **9** | `suche.php` „Plaketten mit ✕"; `version.php` „das Kennzeichen der Vorbelegung (★)" |
    | **Multiplikationszeichen** in sichtbarem Text | **5** | „≥ 2× größtes Komplett-Backup", „(3×)" |
    | Zeichen, das **statt** eines Symbols steht | **0** | — |

    Die fünf `×` sind typografisch richtig — das Multiplikationszeichen ist
    nicht der Buchstabe x. **Die acht Emoji ebenso:** Sie stehen in
    `pwquality.js` 146/147 in einem Kommentar, der erklärt, warum nach
    **Graphemen** statt nach UTF-16-Einheiten gezählt wird („Passwort😀😀😀😀x
    ging als „gut" durch"). Ohne sie erklärt der Absatz nichts mehr.

    **22 von 22 Hinweisen sind begründet.** Das ist genau Nr. 184 („die
    Prüfung kann Prosa nicht von einem Symbol unterscheiden") und der Grund,
    warum PK-04/1b die Zählung vom **Befund** zum **Hinweis** gemacht hat.
    Der Beleg dafür stand bis dahin aus.

285. **Das Produktionstor zählt Stufe-1-Läufe nach SHA und wartet deshalb
    auf eine Messung, die es schon gibt.**
    *Aufgenommen 23.09.2026; Konzept TB (mit dem Abschluss gelöscht, Historie `a72c7df`).*
    Der Tag `web-v20.37.1` auf dem Merge-Commit `10a942c` ist am Tor
    gescheitert (Lauf 35834341392: „Kein grüner Stufe-1-Lauf auf diesem
    Commit"), obwohl Stufe 1 auf dem Zweig-Commit `f356a44` grün war und
    **beide Commits denselben Baum haben** (`447793e4…`). Das Tor fragt
    `…/runs?head_sha=…`, ein PR-Lauf trägt aber die SHA des Zweigs, nie die
    des Merge-Commits; der Push-Lauf auf `main` misst dann 42 bis 56 Minuten
    lang noch einmal, was gemessen ist. Weg: Baum statt SHA im Tor
    (`freigabe.py`), der Push-Lauf auf `main` verweist bei gleichem Baum
    statt zu messen, und „Require branches to be up to date" im Ruleset
    (gesetzt 23.09.2026) macht den Vergleich beweisbar. Entscheidungen
    E-TB-01 bis -09.

    **Erledigt 23.09.2026 mit Konzept TB (TB-01 bis TB-04).** Das Tor
    (`freigabe.py`) zählt einen Lauf, dessen Commit denselben Baum hat wie der
    Tag-Commit und in dem der Job `Stufe 1` grün war — Selbstprobe 52/0,
    vorher 32/0. Der Push-Lauf auf `main` verweist mit dem Job
    `Schon gemessen?` auf den PR-Lauf gleichen Baums, statt noch einmal zu
    messen. **Offen, weil nur die echte Anlage es zeigt:** P-TB-05 (der erste
    Verweis auf `main` nach dem Merge) und P-TB-06 (der erste Tag ohne
    Warten) — `docs/konzepte/Pruefdokument-TB-Tor-nach-Baum.md`.
    **P-TB-05 belegt am 23.09.2026** (Lauf 35844072753, 18 s statt rund
    41 min). **P-TB-06 belegt am 23.09.2026** mit Web 20.37.2 (Lauf
    35852217360): Das Tor hat den PR-Lauf gleichen Baums anerkannt und
    ausgeliefert, ohne auf `main` zu warten.

288. **Eine neue Anlage lässt sich seit Web 20.30.0 nicht einrichten.**
    *Aufgenommen 23.09.2026 beim Bau der Mockup-Runde M-P5c-02 (F-P5c-62),
    gemessen.* `install.php` legt die erste BetreiberIn über
    `konto_anlegen()` an. Das ruft seit c3b5bff (Schritt 15, AP5)
    `db_transaktion()` auf — `konto_lib.php` lädt `db.php` aber nur, wenn
    `config.php` schon da ist (Z. 82), und während der Einrichtung ist sie es
    nicht, weil sie erst danach geschrieben wird. Die Funktion fehlt, der
    Einrichter fängt die Ausnahme und zeigt „Call to undefined function
    db_transaktion()" (`hochfahren.sh` fasst das als „Einrichten
    gescheitert" zusammen). Der Kopf
    von `konto_lib.php` begründet das bedingte Laden ausgerechnet damit, dass
    der Einrichter die Bibliothek ohne Konfiguration laden können muss.

    **Wen es trifft:** jede Neueinrichtung — eine neue Anlage, ein
    Hosterwechsel, ein Neuanfang nach Verlust — und Station B der
    Prüfkette: `hochfahren.sh --neu` scheitert in Schritt 4 („Einrichtungslink
    nicht gefunden"). **Warum es niemand gemerkt hat:** Stufe 1 richtet keine
    Anlage ein, und eine vorhandene örtliche Installation überspringt den
    Schritt.

    *Behebung:* `db_transaktion()` dort verfügbar machen, wo der Einrichter es
    braucht, ohne `db()` zu laden — etwa `db.php` ohne Konfiguration ladbar
    machen oder die Transaktionsklammer in eine eigene Datei ziehen, die
    `konto_lib.php` immer lädt. *Abnahme:* `bash tools/sandbox/hochfahren.sh
    --neu` grün, danach die Anmeldung über den Einrichtungslink. *Fehlschlag:*
    „Einrichten gescheitert" auf einer leeren Datenbank. **Zuordnung: eigene
    Korrekturstufe, vor P5c** — sie hält Station B auf, die jedes Paket von
    P5c braucht.

    **Erledigt 23.09.2026 mit Web 20.37.3.** `db_transaktion()` steht in
    `transaktion_lib.php`, die nichts lädt; `konto_lib.php` und `db.php`
    binden sie ein, Registerzeile Z16 nimmt die neue Datei aus (Decke 9).
    Vorher nachgestellt: `hochfahren.sh --neu` RC 1 in Schritt 4, die Seite
    meldet „Call to undefined function db_transaktion()". Nachher: RC 0 bis
    zum Ende — Einrichtungslink, Passwort im Browser gesetzt, Demo-Konto mit
    106 Einsätzen eingespielt. Den Riegel in der Kette führt Nr. 290, das
    halbe Schema nach einem Fehlschlag Nr. 291. Prüfliste:
    `docs/konzepte/Pruefdokument-Korrektur-288-289.md`.

289. **Die Anmeldeseite stellt die vier Verweise neben die Karte statt
    darunter.**
    *Aufgenommen 23.09.2026 beim Bau der Mockup-Runde M-P5c-02 (F-P5c-63),
    im Bild gemessen.* `.anmeldung` ist ein Flex-Behälter in
    Zeilenrichtung, und `nav.fuss-anmeldung` steht darin hinter der Karte
    (`login.php` Z. 553). Der Kommentar dort will die Verweise „direkt unter
    das Anmeldeformular". Am
    Rechner stehen sie rechts neben der Karte, bei 390 px drücken sie die
    Karte auf rund 200 px zusammen. Seit d3832e4 (Web 20.23.0).

    *Behebung:* `.anmeldung` in Spaltenrichtung, die Verweise unter der
    Karte. *Abnahme:* Anmeldeseite bei 390 und 1440 px — Karte in voller
    Breite (`--anmeldekarte`), Verweise darunter; Bilderlauf ohne Überlauf.
    *Fehlschlag:* ein Verweis steht auf gleicher Höhe wie die Karte. Die
    Mockup-Runde M-P5c-02 (a) zeigt den berichtigten Stand. **Zuordnung:
    eigene Korrekturstufe, zusammen mit Nr. 288**, spätestens P5c AP1 (die
    Anmeldeseite bekommt dort die Streifen).

    **Erledigt 23.09.2026 mit Web 20.37.3.** `.anmeldung` in
    Spaltenrichtung (`flex-direction:column`); `Design.md` 10.1 sagt es.
    Gemessen im Prüfdokument `docs/konzepte/Pruefdokument-Korrektur-288-289.md`.

292. **Neun von 36 Proben der Nebenstufe sind rot — und seit PK-05 macht
    jede davon das Tor rot.**
    *Aufgenommen 23.09.2026 mit PK-05 (Konzept PK, F-PK-40), gemessen.* Die
    Stufe „neben" gibt es erst seit PK-05; vorher maß jede Stufe wie „klein"
    (F-PK-30). Gefahren auf frischer örtlicher Anlage (`hochfahren.sh --neu`,
    Web 20.37.3), `pruefen.sh --stufe neben --datei server/index.php`:
    **36 Proben, 27 grün, 9 rot, 0 nicht gemessen, 1 266 s** (Ziel 15 min).
    Mit Lage 5 (E-PK-44) ist jeder Bericht mit einer roten Probe ein rotes
    Tor — **der erste Nebensprung, P5c AP1, käme so nicht durch.** Die neun,
    grob eingeordnet am Ende ihres Protokolls:

    - **Verdrahtung des Prüfstands** (3): `versandprobe` braucht den
      Wurzelpfad der Gegenstellen als Argument (bekannt, P-PK-23);
      `spaltenregister-wegprobe` braucht `WEGWERFKONTO`/`WEGWERFPASSWORT`
      (seit PK-05 „nicht gemessen" statt Absturz, aber weiter nicht grün);
      `freigabeprobe` endet mit „Zielkonto nicht gefunden".
    - **Referenz veraltet** (1): `kreislauf-csv` — die Hausform aus PK-04/5b
      („Andere NotärztIn") steht in der Anwendung, nicht in der Referenz.
    - **Inhaltlich, ungeklärt** (5): `wiederherstellung` 110/2 (F-PK-18,
      seit PK-03 bekannt), `gpxprobe` 92/3, `wartungsprobe` 67/1,
      `mailprobe` 41/1 (Pflichtwerte im Beispielsatz), `browserprobe-csp`.
      Je Probe zu klären: Fehler der Anwendung oder veraltete Erwartung
      (P-PK-20).

    *Weg (entschieden, E-PK-45):* ein eigenes Korrekturpaket **vor P5c AP1**,
    auf eigenem Zweig nach dem PR von PK-05. Eine Liste bekannter roter
    Proben, die Lage 5 ausnimmt, war die Gegenoption und ist nicht gewählt — sie
    wäre ein Filter vor dem Riegel. **Zuordnung: Korrekturpaket vor P5c.**

    **Erledigt 23.09.2026 mit Konzept RP** (Zweig `claude/rp-rote-proben`,
    ohne Versionsstufe — keiner der neun war ein Fehler der Anwendung).
    Verdrahtung (RP-01): Versandprobe startet ihre Gegenstellen selbst,
    Freigabeprobe bringt ihr eigenes Konto mit, Wegprobe am edbak-Umlaufkonto
    über `nach`, Kreisläufe mit `--frisch`, GPX-Pfad. Erwartungen (RP-02):
    Texte, Beispielsatz, CSP nur scharf, csv-Referenz erneuert. RP-03: Die
    Wiederherstellungsprobe hatte zu wenige Konten. **Nebenstufe auf frischer
    Anlage: 36 grün, 0 rot, 0 nicht gemessen, 1 239 s**; das Ziel ist seither
    rund 21 min (E-RP-05). Prüfliste: `docs/konzepte/Pruefdokument-RP-Rote-Proben.md`.

243. **Ein Umgebungsbanner, damit Staging nicht für Produktiv gehalten wird.**
    *Aufgenommen 20.09.2026 (E-P5c-05).* Zugeordnet: **10c, AP1**.
    **Erledigt 23.09.2026 mit Web 20.38.0 (P5c/AP1)** — siehe unten.

    `config.php` bekommt `app.umgebung = ['name' => 'Staging',
    'farbe' => 'rot']`, Vorgabe leer. Ist es gesetzt: Kopfleiste in
    **Newroz-Rot** statt Dunkelblau, darunter die Zeile „Staging —
    Testdaten, kein Echtbetrieb", Seitentitel mit Präfix „[Staging]".

    **Nie abgeleitet** — nicht aus Domain, Zweig oder Kette. Eine Ableitung
    wäre bequem und falsch: Sie stimmte genau so lange, bis jemand eine
    zweite Anlage unter derselben Domain aufsetzt. Die Statusseite warnt,
    wenn `mail.betreff_praefix` gesetzt ist und `app.umgebung` nicht — das
    ist der Fall, in dem die Mails schon „Staging" sagen und die Oberfläche
    noch nicht.

    **Derselbe Baustein** trägt das Ankündigungsbanner (E-P5c-13); zwei
    Banner wären zwei Stellen, die auseinanderlaufen.

    **Erledigt mit Web 20.38.0.** Gebaut wie beschrieben, mit drei
    Abweichungen, die die Mockup-Runde M-P5c-02 entschieden hat: Die Zeile
    steht **nicht unter der Kopfleiste**, sondern in der Reihe der Streifen an
    der Stelle des Demo-Hinweises (unter der Kopfleiste verschob ein Streifen
    die klebende Leiste, F-P3-G). Die Farbe heißt `--rot` (der Name
    „Newroz-Rot" steht nur noch im Rahmenplan-Archiv). Und der aktive
    Kopfpunkt wird auf Rot in `--orange-hell` gestrichen, weil das gewohnte
    Orange dort 2,10 : 1 hätte (E-P5c-59). Die Statusseite zeigt die Zeile
    „Umgebung" **immer**, nicht nur als Warnung (E-P5c-64). Der eine
    Baustein trägt auch die Ankündigung (E-P5c-13). Gemessen: Titelvorsatz,
    rote Kopfleiste und Streifen auf Anmelde-, Start-, Status- und
    Servereinstellungsseite; alle vier Fälle der Statuszeile; Kontraste
    **25 Paare, 0 verfehlt**. Auf Staging ist die Zeile in der `config.php`
    nachzutragen (Rahmenplan 6).

286. **Ein Admin erreicht Komplett-Backup und Backup-Ziele — samt
    Klartext-Dump der ganzen Datenbank.**
    *Aufgenommen 23.09.2026 im Abgleich des Konzepts P5c (F-P5c-15), von Hand
    nachgeprüft.* R75 und der Kopf der Rollen in `db.php` behalten
    Komplett-Backup und Backup-Ziele der BetreiberIn vor, und das Menü zeigt
    beide Seiten nur ihr. **Die Seiten selbst fragen aber nur
    `require_admin()`** (`admin_komplettsicherung.php` und
    `admin_sicherungsziele.php`, je Z. 4; das Wort „betreiberin" kommt in
    beiden Dateien nicht vor). Per Direktaufruf liefert
    `action=herunterladen` jedem Admin über `komp_ausgeben_klar()` den
    Klartext-Dump — mit Passwort-Hashes und versiegelten Zugängen; dazu
    kommen die sieben Handlungen der Backup-Ziele. `admin_sicherungen.php`
    verlinkt Admins sogar dorthin.

    **Vorhergesagt und nie nachgemessen:** Das Prüfdokument S8 führt P-01
    als „teilweise" mit der Auflage, nach AP5 zu wiederholen — „dann muss ein
    Admin dort 403 bekommen". Die Wiederholung hat nie stattgefunden.

    **Warum es heute nicht brennt:** Es gibt kein Konto mit der Rolle admin
    (Auskunft der BetreiberIn, 23.09.2026; die Migration von S8 hat alle
    Admins zu BetreiberInnen gemacht). **Bis zur Behebung legt niemand ein
    Admin-Konto an.**

    *Behebung:* `require_betreiberin()` an beiden Stellen, der Verweis in
    `admin_sicherungen.php` nur für die BetreiberIn, Nachtrag in
    `Technik.md`. *Abnahme:* die Rollenprobe (`tools/proben/rollen/`, Anlass
    dieser Punkt) — 13 Handlungen, Admin 403, BetreiberIn 200. *Fehlschlag:*
    ein Admin bekommt auf einer der beiden Seiten 200. **Zuordnung: 10c AP2**
    (E-P5c-31).

    **Erledigt mit Web 20.39.0 am 24.09.2026 (P5c/AP2).** Beide Seiten
    fragen jetzt `require_betreiberin()` — vor jeder Handlung, auch vor dem
    Token; der Verweis in `admin_sicherungen.php` steht nur noch für die
    BetreiberIn. **Gemessen mit der Rollenprobe** (`tools/proben/rollen/`,
    die Matrix steht in `docs/Technik.md` 4.99p): **15** Zellen je Rolle für
    die beiden Seiten — die Seite selbst und sechs Handlungen beim
    Komplett-Backup, die Seite und sieben Handlungen bei den Zielen; hier
    standen „13 Handlungen", gezählt ohne die beiden Seitenaufrufe. Admin
    **403** überall, BetreiberIn **200** bzw. **durch** (die Handlung
    erreicht, mit absichtlich falschem Token, damit nichts ausgeführt wird).
    Probe gesamt **87 Erwartungen, 0 offen**. **Gegenprobe:** das alte Tor an
    `admin_sicherungsziele.php` kurz zurückgesetzt → **8** Zellen der
    Admin-Spalte rot (Seite und sieben Handlungen), danach wieder grün.

238. **`missions.manual` bricht die Einrichtung auf MySQL 8.4.0–8.4.10.**
    *Aufgenommen 20.09.2026 aus dem Fehlversuch auf dem neuen
    Staging-Webspace.* **Umgesetzt in Web 20.25.0**, Zweig
    `claude/festive-fermi-el0avv`. Offen war allein die Prüfung durch die
    Betreiberin — Liste im Prüfdokument `Pruefdokument-uhr_gesperrt.md`
    (gelöscht 24.09.2026, Historie `5e501ae`).

    `SQLSTATE[42000] … 1064 … near 'manual TINYINT(1) NOT NULL DEFAULT 0` —
    MySQL führt **MANUAL von 8.4.0 bis 8.4.10 als reserviertes Wort**, ab
    8.4.11 wieder nicht. `schema.sql` legte die Spalte ungequotet an; die
    Staging-Datenbank ist 8.4.x.

    **Gemessen:** 788 Bezeichner-Vorkommen in elf DDL-führenden Dateien gegen
    284 reservierte Wörter → **zehn betroffene Stellen**, nicht eine. Neben
    der Einrichtung der Uhr-Eingang, GPX- und Datei-Import, der Schnitt,
    beide Zweige des Einsatzformulars und beide Richtungen der Sicherung.
    `PARALLEL`, `QUALIFY`, `TABLESAMPLE` (in 8.4 ebenfalls neu reserviert)
    kommen nicht vor.

    **Entscheidung (Philipp, 20.09.2026): umbenennen in `uhr_gesperrt`, nicht
    quoten.** Eine übersehene Stelle scheitert dann auf jeder Version sofort
    statt nur auf elf im Betrieb. Der Dateischlüssel in Sicherung und Export
    bleibt `manual`.

    **Dahinter lag ein zweiter Blocker, und er war der größere:**
    `DEFAULT UTC_TIMESTAMP()` ohne Klammern wird von MySQL auf **jeder**
    Fassung abgewiesen (vier Stellen). Die Anwendung ließ sich damit **seit
    Web 20.16.5 auf MySQL überhaupt nicht einrichten**; gemerkt hat es
    niemand, weil der Fehler am reservierten Wort schon vorher kam. Beides
    hatte dieselbe Ursache — entwickelt und geprüft wird gegen MariaDB,
    ausgeliefert wird gegen MySQL. Dagegen steht jetzt
    `tools/schemaprobe/` in Stufe 1, mit einer **Matrix** über MySQL 8.4.0
    und MariaDB 10.6.

    **Nachtrag 21.09.2026 (Web 20.26.3):** Der bewahrte Alias `AS manual`
    war selbst betroffen — MySQL 8.4.0 bis 8.4.10 reserviert das Wort auch
    als Alias. Export und Sicherung scheiterten auf Staging (MySQL 8.4.10)
    mit 1064. Behoben mit Backticks um den Alias, Nr. 267.

    *Erledigt 24.09.2026:* Prüfung durch die Betreiberin erfolgt — erklärt beim
    Aufräumen der Konzeptablage (Rahmenplan Fassung 110); damit ist nichts
    mehr offen. Aus Rahmenplan Abschnitt 5 herausgenommen.

296. **Eine Rundmail hätte den Seitenaufruf bis zu 200 Sekunden aufgehalten.**
    *Aufgenommen 24.09.2026 aus Konzept P5c (F-P5c-29), als Anlass nach der
    Zuarbeit von Konzept BR (E-BR-07).* `mail_einreihen()` versuchte jede
    Nachricht sofort, bis zu fünf Sekunden je Stück; eine Rundmail an
    vierzig Konten hätte die Seite so lange warten lassen.
    **Erledigt mit Web 20.38.0 am 23.09.2026 (P5c/AP1):** `mail_einreihen()`
    kann nur einreihen; die Mailprobe misst es in Abschnitt 14 gegen einen
    schweigenden Server (6 Zeilen in 0,01 s statt bis zu 30 s). Die Nummer
    ist der Anlass dieses Abschnitts.

248. **Das Prüftor Stufe 1 zählt die `error_log(`-Aufrufe.**
    *Aufgenommen 20.09.2026 (Konzept P5c, Abschnitt 8).* Zugeordnet:
    **10c, AP3** — dort erledigt, hier als Prüfmittel-Vermerk.

    Mit 10c wandern die `error_log()`-Aufrufe auf den Protokollreiter
    System. Damit sie nicht nach und nach zurückkehren, zählt Stufe 1 sie
    nach: **Soll ≤ 2**, und zwar ohne Kommentare und ohne Zeichenketten —
    sonst zählt das Prüfmittel seine eigene Dokumentation mit.
    Mitgeprüft wird der `set_exception_handler()`-Behandler.

    **Berichtigt am 20.09.2026 (E-ZE-03):** Hier stand, das Zählmittel
    entstehe mit 10c AP3. Es **besteht seit Schritt 15 AP1**
    (`tools/zaehlung/`); 10c AP3 setzt nur die Registerzeile **Z38** auf
    **Decke 2**. Zwei Pakete, die dasselbe Werkzeug bauen, hätten es zweimal
    gebaut.

    **Die Ausgangszahl ist gemessen:** 42 am 16.09.2026, nachgemessen am
    20.09.2026 an `862ca7f` **77 Aufrufe in 32 Dateien**. Die Zahl ist in
    vier Tagen um 35 gestiegen — genau deshalb braucht es einen Zähler und
    keine Vorsatzerklärung.

    **Berichtigt 23.09.2026 (Konzept P5c, E-P5c-58):** Die Übergabezahl aus
    Schritt 15 ist **75** (Tokenizer, ohne Kommentare und Zeichenketten), die
    Decke nach AP3 **2** (Helfer-Rückfall und `protokoll_fehler_vermerken()`).
    **Der Behandler wird nicht von einer Registerzeile geprüft**, sondern von
    einer Regel in `tools/quelltext/`: genau ein `set_exception_handler(` und
    ein `set_error_handler(` in `db.php`. Das Register kennt nur Decken — ein
    entfernter Behandler bliebe dort grün.

    **Erledigt mit Web 20.40.0 am 24.09.2026 (P5c/AP3, E-P5c-58).** Die 75
    Aufrufe gehen über `system_melden()` in den Reiter System; Z38 zählt
    **2** (der Rückfall in `systemmeldung_lib.php` und
    `protokoll_fehler_vermerken()`), `decke_jetzt` und `decke_ziel` stehen
    auf 2. Die Behandler prüft `tools/quelltext/behandler.php` — ein Riegel
    in Stufe 1: genau ein `set_exception_handler()`, ein
    `set_error_handler()` und ein `register_shutdown_function()` in `db.php`,
    kein zweiter Ausnahme-Behandler anderswo. Gegenprobe: Behandler
    entfernt → Rückgabewert 1. Die Wirkung misst die Protokollprobe
    (Teil 7) und die Ingestprobe (Teil 11).

141. **Zweitfaktor für alle Konten.**
    *Aufgenommen 06.09.2026 aus dem Krypto-Review (K-5).* Passwort ist
    Anmeldung **und** Datenschlüssel; Phishing genügt für alles. R38 sieht
    TOTP nur für Admin-Konten vor. **Entschieden (R78):** für alle Konten
    angeboten, für Admins Pflicht; Geheimnis serverseitig versiegelt
    (`sk_versiegeln()`), `otpauth://`-Text statt QR-Fremdbestandteil, acht
    Ersatzcodes gehasht, „Gerät 30 Tage merken". Schützt die Anmeldung,
    nicht den Offline-Angriff (dafür S10). Zuordnung: **P5** (erweitert
    R38).

    **Zuordnung (20.09.2026): 10c, AP5** — dort wird der Zweitfaktor konkretisiert: Pflicht für Admin, BetreiberIn und Support, Angebot für alle übrigen (E-P5c-15, F-P5c-1).

    **Berichtigt 23.09.2026 (E-P5c-41, -42):** **QR-Code statt
    `otpauth://`-Text** — aus der vendorierten Bibliothek `qrcode-generator`,
    das SVG baut die Anwendung selbst; der Text steht daneben. **Zehn**
    Ersatzcodes statt acht, gehasht, unabhängig vom Serverschlüssel.
    **„Gerät 30 Tage merken" kommt nicht mit 10c**, sondern mit dem
    Cookie-Token aus Nr. 242 in Schritt 18 — zwei Cookie-Mechanismen werden nur
    einmal gebaut. Zuordnung bleibt **10c AP5**.

    **Erledigt mit Web 20.42.0 am 24.09.2026 (P5c/AP5).** TOTP nach RFC 6238,
    Pflicht für Support, Admin und BetreiberIn, Angebot für alle übrigen,
    gesperrt im Demo-Konto; die Code-Abfrage steht vor der Sitzung
    (E-P5c-53), Pflichtrollen ohne Zweitfaktor landen im Einrichtungstor
    `zweitfaktor.php`. QR-Code aus `qrcode-generator` 2.0.4, zehn
    Wiederherstellungscodes mit `password_hash()`, nicht am
    Serverschlüssel; Zurücksetzen durch die Verwaltung. Nachweis:
    Zweitfaktorprobe 44 / 0 (RFC-Vektoren 6 / 6), zwei Bedienwege.
    **Zwei Stücke stehen noch aus, beide woanders:** der Rückweg über den
    Wiederherstellungsschlüssel kommt mit dem Einschubkonzept RW
    (E-P5c-104) — die Fassung gegen `pat_key_check` war fälschbar
    (F-P5c-106) —, und „Gerät 30 Tage merken" mit dem Cookie-Token aus
    Nr. 242 in Schritt 18. **Nachtrag 24.09.2026:** Rückweg über den
    Wiederherstellungsschlüssel → Konzept RW, 10c AP5b — gebaut mit
    Web 20.43.0 bis 20.45.0.

211. **Ein `/api/`-Aufruf ohne Sitzung bekommt eine Weiterleitung statt
    einer JSON-Antwort.** `auth_guard.php` prüft in Zeile 33
    `empty($_SESSION['user_id'])` und antwortet mit
    `header('Location: login.php')` — **vor** jeder Unterscheidung, ob das
    Gegenüber JSON erwartet. `ist_api_aufruf()` gibt es, aber es wird erst
    weiter unten benutzt, für die Fälle „Sitzung abgelaufen" und „Rolle reicht
    nicht" (dort korrekt: 401 bzw. 403 als JSON).

    **Die Folge ist klein, aber sie ist eine Unwahrheit:** Ein Werkzeug oder
    ein Skript, das einen Endpunkt kalt aufruft, bekommt HTTP 302 und danach
    die HTML-Anmeldeseite — und wird daran hängenbleiben, statt „nicht
    angemeldet" zu lesen. Im Betrieb tritt das selten auf: Die Aufrufe des
    Browsers kommen aus einer angemeldeten Seite, und eine **ablaufende**
    Sitzung fängt der richtige Zweig ab.

    **Zu tun:** Die Weiterleitung in Zeile 33 an `ist_api_aufruf()` vorbei
    nicht mehr unbedingt machen, sondern denselben JSON-Weg nehmen wie
    `sitzung_beenden_passend()` — 401 mit einem lesbaren Grund.
    *Abnahme:* `curl -s -o /dev/null -w '%{http_code}' <basis>/api/day.php?day=2026-01-01`
    liefert **401** und `{"error":…}` statt 302.
    *Aufgenommen 16.09.2026 in P5a/AP9, gefunden beim Bau der
    Verbindungsprobe: Sie wollte einen `/api/`-Endpunkt unter Überlast messen
    und bekam eine 302, weil die Anfrage die Datenbank nie erreichte.*

    **Erledigt mit Web 20.42.0 am 24.09.2026 (P5c/AP5, E-P5c-106,
    F-P5c-108).** Mit dem Code-Schritt des Zweitfaktors wurde der Randfall
    zum Normalfall: Eine halbe Anmeldung hat eine Sitzung, aber keine
    `user_id`. `auth_guard.php` antwortet einem Aufruf unter `api/` jetzt
    mit 401 und `{"error":"session_ende","grund":"nicht_angemeldet",…}`,
    einer Seite weiter mit der Weiterleitung. Abnahme wie oben:
    `curl …/api/day.php?day=2026-01-01` → **401**.

298. **Kein Prüfmittel liest den QR-Code, den die Anwendung zeigt.**
    *Aufgenommen 24.09.2026 aus Konzept P5c (AP5), als Anlass des Decoders
    nach der Zuarbeit von Konzept BR (E-BR-07).* 10c AP5 zeigt das Geheimnis
    des Zweitfaktors als QR-Code, gezeichnet aus der Modulmatrix einer
    vendorierten Bibliothek. Ob der Code die angezeigte otpauth-Adresse
    trägt, sieht niemand: Ein falsch kodierter Code sähe auf jedem Bild
    richtig aus und ließe das Einrichten am Handy scheitern. Die
    Mockup-Runde M-P5c-02 hat ihn von Hand gelesen (2 von 2). *Weg:* der
    Bedienweg „Zweitfaktor einrichten" in `tools/bedienprobe/` liest einen
    Abzug des Codes mit `jsqr` (Apache-2.0, vendoriert in
    `tools/bedienprobe/vendor/` mit Herkunft und SHA-256, `Lizenzen.md`) und
    vergleicht ihn mit der angezeigten Adresse (E-P5c-87). **Zuordnung: 10c
    AP5.**

    **Erledigt mit Web 20.42.0 am 24.09.2026 (P5c/AP5).** Der Bedienweg
    `zweitfaktor-einrichten` (`tools/bedienprobe/wege/zweitfaktor.mjs`)
    nimmt einen Abzug des `svg.qr`, liest ihn mit jsQR 1.4.0 auf einer
    leeren Seite — die Inhaltsrichtlinie der Anwendung lässt kein fremdes
    Skript zu, und das bleibt so — und vergleicht Zeichen für Zeichen mit
    dem `otpauth://`-Verweis daneben: gleich, in Chromium unter PHP 8.4 und
    8.3.

304. **Die Rundlaufprüfung der Spuren hielt `int` gegen `float` — 175 von
    181 Spuren sahen verändert aus.** *Nachgetragen 24.09.2026 mit Konzept
    BR (E-BR-17); gefunden und behoben am 31.08.2026 in S2/AP1, Web 10.0.0.*
    PHP rechnet `7800 / 10` als `int(780)`, die Rückrechnung aus dem Blob
    `round(780.0*10)/10` ergibt `float(780.0)`, und `!==` prüft den Typ mit.
    Keine Koordinate war anders, und trotzdem meldete der erste Lauf 175
    Abweichungen. Im Betrieb wäre das ein Verdichtungsjob gewesen, der nie
    eine Zeile löscht. Fundstelle: `docs/konzepte/erledigt/
    Konzept-S2-Mengen-Spuren-Sicherung.md`, AP1. Nachweis:
    `tools/proben/spur/probe.php`.

305. **Die Stufe der Sperrleiter wäre nie zurückgefallen.** *Nachgetragen
    24.09.2026 mit Konzept BR (E-BR-17); gefunden und behoben am 16.09.2026
    in P5a/AP6, Web 20.10.0 (Commit `214bc04`).* Der Verfall `stufe_bis`
    wurde nur in dem Zweig aufgefrischt, in dem **nicht** gesperrt wurde —
    wer einmal auf eine höhere Stufe kam, blieb dort. Dazu hätte Klopfen
    während einer Sperre die laufende Sperre gelöscht. Gefunden von der
    Ratenprobe beim Bau. Nachweis: `tools/proben/raten/probe.php`.

306. **Der Neuanlauf des Komplett-Backups lief in ein `count(null)` — genau
    im Zweig nach einer Wiederherstellung.** *Nachgetragen 24.09.2026 mit
    Konzept BR (E-BR-17); gefunden und behoben am 01.09.2026 in S2/AP8, Web
    12.2.0 (F-S2-I).* Fehlte der Baustand, lief die Erstbelegung des
    Fortsetzungszustands in einen `TypeError`. Das ist der Zweig, der nach
    einer Wiederherstellung greift — der Augenblick, in dem ein Backup am
    dringendsten gebraucht wird. Gefunden von Teil 8 der Komplettprobe; der
    Kommentar in `komplett_lib.php` sagt es. Nachweis:
    `tools/proben/komplett/probe.php`.

307. **Die Prüfung der Wiederherstellungshülle nahm auch `edka1:` an.**
    *Nachgetragen 24.09.2026 mit Konzept BR (E-BR-17); gefunden am
    14.09.2026 im Gegenlesen von S10/AP2, behoben mit Web 20.0.0 (Commit
    `5a2ef0e`, dort F-2).* `WRAP_RE` prüfte beide Hüllen mit einer Regel und
    ließ seit Web 19.7.0 das Anteil-Format auch für `pat_wrap_rc` zu. Eine
    solche Wiederherstellungshülle hinge am Server-Anteil — der Verlust genau
    des Rückwegs, den `CLAUDE.md` 4 zusagt, und man sähe es dem Feld nicht
    an. Seither `WRAP_PW_RE` und `WRAP_RC_RE`. Gefunden hat es das Gegenlesen;
    **fangen müssen** hätte es Teil A3 der Anteilprobe. Nachweis:
    `tools/proben/anteil/probe.php`.

308. **Die Freigabe eines Backups war für niemanden zu sehen.** *Nachgetragen
    24.09.2026 mit Konzept BR (E-BR-17); gefunden und behoben am 01.09.2026
    in S2/AP6, Web 12.0.0 (F-S2-F).* `getElementById('freigabecodelabel')`
    lieferte `null`, weil die Kennung im Markup fehlte, und der `TypeError`
    verschwand im leeren `catch` von `freigabeLaden()`. Damit war der einzige
    Weg tot, ein Backup mit geschützten Angaben in ein neu aufgesetztes Konto
    zu bringen (E20). Nachweis: `tools/proben/freigabe/probe.mjs`.

309. **Der alte Base64-Wandler brach bei einem Teil von 2 MB ab.**
    *Nachgetragen 24.09.2026 mit Konzept BR (E-BR-17); gefunden und behoben
    am 31.08.2026 in S2/AP5, Web 11.0.0.*
    `btoa(String.fromCharCode(...bytes))` warf ab etwa 2 MB „Maximum call stack
    size exceeded" — ein Backup-Teil dieser Größe ließ sich nicht schreiben.
    Die Containerprobe hält den Fall seither fest, zusammen mit der Bindung
    jedes Teils an sein Backup (vertauschte, fremde, verfälschte Teile).
    Nachweis: `tools/proben/container/probe.mjs`.

310. **Die Frist des Inhaltsschlüssels lief ab dem Entpacken, nicht ab der
    letzten Bedienung.** *Nachgetragen 24.09.2026 mit Konzept BR (E-BR-17);
    behoben am 02.09.2026 in S6, Web 12.9.0 (R44, E-S6-4).* Die Frist in
    `keyguard.js` war absolut: Wer ohne Pause bediente, bekam in acht
    Stunden Dienst **17** stille Neu-Entpackungen statt einer. Die im
    Rahmenplan zu R44 vorgeschriebene Abnahme war vor und nach der Änderung
    grün und belegte deshalb nichts; die Fristprobe zählt den Unterschied
    (17 gegen 1). Nachweis: `tools/proben/frist/pruefe.mjs`.

311. **`rt_html()` ist der eine Weg, auf dem aus einer Eingabe HTML wird.**
    *Aufgenommen 24.09.2026 mit Konzept BR (E-BR-18, Q-BR-10) — ein Risiko,
    kein Fund.* Alles andere in der Anwendung geht durch `e()` und erscheint
    als Text. Eine Lücke im Rechtstext-Renderer wäre ein eingeschleustes
    Skript auf den öffentlichen Rechtstextseiten. Die Angriffsprobe ist mit
    dem Renderer in P3/O10 entstanden (Web 9.11.0) — 81 Proben und eine
    Positivliste erlaubter Tags — und läuft seither als Riegel in jeder
    Stufe und im Tor. Gefangen hat sie nichts; **fangen muss sie** genau
    diese Lücke. Nachweis: `tools/proben/rechtstexte/pruefen.php`.

312. **Ein Umbau des Stylesheets ändert einen berechneten Stil, den niemand
    ändern wollte.** *Aufgenommen 24.09.2026 mit Konzept BR (E-BR-18,
    Q-BR-11) — ein Risiko, kein Fund.* Wer Regeln verschiebt, zusammenführt
    oder entfernt, ändert die Kaskade; ob danach ein Element anders aussieht,
    zeigt kein Bild zuverlässig. Der Stilvergleich (seit P0/A3, spätestens
    Web 7.2.0) hält die berechneten Stile zweier Stylesheets an denselben
    Elementen gegeneinander, und die Liste gegen die geplanten Änderungen
    (`Pruefablauf.md` 6.10). Ein ungeplanter Fund ist nicht verbucht;
    **fangen muss er** genau diesen. Nachweis: `tools/stilvergleich/`.

313. **Die Fehlerzweige der Kopplung dürfen nicht verraten, welche Kennungen
    es gibt.** *Aufgenommen 24.09.2026 mit Konzept BR (E-BR-18, Q-BR-12) —
    ein Risiko, kein Fund.* Die Kopplung ist der eine Weg, auf dem ein Gerät
    ohne Anmeldung Zugangsdaten zu einem Konto bekommt. Unterscheiden sich
    ihre Fehlerzweige in Länge, Aufbau oder **Dauer**, beantwortet die
    Antwort die Frage, welche Kennungen es gibt. Die Kopplungsprobe (S5,
    Web 13.0.0) prüft `pair.php` gegen den JSON-Vertrag und die
    Antwortgleichheit — beide 401-Zweige 0,351 s, Rümpfe byteweise gleich.
    Nachweis: `tools/proben/kopplung/probe.php`.

293. **Der Werkzeugbestand unter `tools/` hat keinen Riegel.**
    *Aufgenommen 24.09.2026 mit Konzept BR, gemessen an `main` `f4fe4a0`.*
    Konzept PK hat den Durchlauf einer Änderung als Riegel gebaut und den
    Bestand als Regel gelassen: Grundsatz 5 (die Anlass-Zeile), die Form der
    Anleitung mit fünf Abschnitten und höchstens 40 Zeilen
    (`Pruefablauf.md` 6.2), die Streichliste. Kein Mittel maß eine davon,
    und drei Tage nach PK-04 standen die Zahlen darunter: vier Anleitungen
    über 40 Zeilen, acht Unteranleitungen mit zusammen 1 120 Zeilen, die
    PK-04 nie gezählt hat, ein Werkzeug ohne Anleitung — und keine der
    zwanzig Proben trägt die Anlass-Zeile, die `tools/proben/LIESMICH.md`
    ihrem Kopfkommentar zuschreibt. Die Handzählung im Konzept fand eine;
    sie war ein Prüffall, der das Wort „Anlass" im Text trägt. Der erste
    Lauf des Riegels fand **57 Befunde** in sechs Regeln.

    *Weg (Konzept BR):* `tools/quelltext/bestand.py` als neunte
    Quelltextprüfung im Tor (BR-01), der Altbestand auf null im selben Pull
    Request (BR-02; E-BR-01: keine Decke, keine Ausnahmeliste). *Abnahme:*
    `bestand` meldet 0 Befunde, die Selbstprobe baut je Regel einen Fehler
    ein und findet ihn. *Fehlschlag:* eine Anleitung mit 41 Zeilen oder eine
    Probe ohne Anlass-Zeile, und Stufe 1 bleibt grün. **Zuordnung: Konzept
    BR.**

    **Erledigt 24.09.2026 mit Konzept BR** (Zweig `claude/br-bestandsriegel`,
    ohne Versionsstufe — berührt sind nur `tools/`, `docs/`, `.github/` und
    ein Satz in `CLAUDE.md`). BR-01: `tools/quelltext/bestand.py`, die
    neunte Quelltextprüfung, mit Selbstprobe je Regel. BR-02: der
    Altbestand von **57 Befunden auf 0** — zwanzig Anlass-Zeilen in den
    Probenköpfen, 25 Anleitungen mit zusammen 972 statt 2 144 Zeilen, zehn
    nachgetragene Anlässe (Nr. 304 bis 313), `konfig_stellen.php` nach
    `tools/sandbox/`. BR-03: die drei Tor-Schritte, die Station B nie fuhr,
    als Quelltextprüfungen (Regel `backlog`, `pysyntax`, `handbuch`;
    **17 Riegel statt 14**), eine Baumsuche statt zweier
    (`tools/kette/baumsuche.py`), der Selbstüberspringer der
    Wiederherstellungsprobe rot. BR-04: der Weg für ein neues Prüfmittel
    (`Pruefablauf.md` 6.12) und der Satz „Ein Anlass ist eine
    Backlog-Nummer" (6.1); die Gegenlesung des Weges durch Befolgen fand
    Nr. 314 und 315. Prüfliste:
    `docs/konzepte/Pruefdokument-BR-Bestandsriegel.md`.

314. **Der Prüfstand meldete grün, wenn sein Bericht nicht entstand.**
    *Aufgenommen und behoben 24.09.2026 mit Konzept BR (BR-04, F-BR-18),
    gefunden von der Gegenlesung des Runbooks.* `tools/pruefstand/pruefen.sh`
    rief `bericht.py schreiben` auf und wertete den Rückgabewert nicht aus
    (kein `set -e`). Stürzte der Bericht ab, endete der Lauf mit rc 0 und
    „0 rot, 17 grün" — ohne Beleg. Der Absturz selbst kam aus `bericht.py`:
    Der eigene Index lag fest unter `WURZEL/.git/`, und in einem Worktree ist
    `.git` eine Datei. Im Tor wäre es aufgefallen (kein Bericht → rot);
    örtlich hielt sich eine Instanz für fertig. *Behoben:* Ohne Bericht ist
    der Lauf rot („KEIN BERICHT — bericht.py endete mit rc …"), und den Ort
    des Index nennt Git (`rev-parse --git-path`). *Nachweis:* im Worktree
    vorher rc 0 ohne Bericht, nachher Bericht mit Baum und rc 0; mit einem
    absichtlich scheiternden `bericht.py` rc 1.

316. **Die Uhr-Probe des Prüfstands lief seit PK-03 nie.** *Aufgenommen und
    behoben 24.09.2026 mit Konzept BR (BR-04, F-BR-20); als F-PK-21 seit
    PK-03 bekannt, nie behoben.* `pruefablauf.json` rief für `uhr-stufe1`
    nur `pruefstand.sh reihe` auf; `reihe` verlangt die Geräteliste, die
    erst `geraeteklassen.py` schreibt, und brach nach 0 s ab („Listendatei
    fehlt"). Aufgefallen ist es erst jetzt, weil seit PK-05 kein Pull
    Request `tools/uhr-pruefstand/` berührt hatte — das Tor verlangt dann
    `uhr=gebaut`, und der Prüfstand konnte es nie liefern. `kettenaufrufe`
    sah es nicht: Es prüft Namen und Schalter, keine Positionsargumente
    (F-PK-21). *Behoben:* Der Aufruf ist die Kette aus der Anleitung des
    Werkzeugs — Liste schreiben, dann `reihe`. *Nachweis:* von Hand
    99 übersetzt, 0 fehlgeschlagen, 0 ohne Gerätedatei, rc 0, rund 10 min.
    Der Lauf im Prüfstand steht im Bericht des Kopf-Commits von BR.

315. **Vier Schritte beim Einhängen eines Prüfmittels misst niemand.**
    *Aufgenommen 24.09.2026 mit Konzept BR (BR-04, F-BR-19), gemessen von
    der Gegenlesung des Runbooks `Pruefablauf.md` 6.12.* Eine unabhängige
    Instanz hat zwei Attrappen eingehängt und je einen Schritt weggelassen.
    Rot wurden fehlende Anlass-Zeile, `RUF`-Zeile, Backlog-Nummer, Name in
    `NAMEN` oder `starter()` und das `--riegel` im Tor. **Grün blieb alles**
    bei vier anderen: (1) eine Quelltextprüfung mit Selbstprobe, die nicht in
    `SELBST` steht — ihre Selbstprobe läuft nirgends; (2) eine
    Quelltextprüfung ohne Zeile in der Tabelle von
    `tools/quelltext/LIESMICH.md` — ihr Anlass steht nirgends; (3) eine
    Probe unter `proben` in `pruefablauf.json`, die in keinem Muster und
    keinem Riegel steht — sie läuft nie; (4) die Tabelle in
    `Pruefablauf.md` 4, nicht neu erzeugt — nichts vergleicht sie mit
    `erzeugen-doku`, obwohl sie „nicht von Hand ändern" trägt.

    *Weg (zu entscheiden):* die vier als Regeln in `bestand` (1, 2, 3) und
    als Vergleich in `bericht.py` oder `bestand` (4). Jede ist eine Zeile
    Zählung gegen eine Liste, die es schon gibt. Bis dahin nennt 6.12 sie
    als Schritte, die man gegenliest. *Abnahme:* je Lücke ein Fall in der
    Selbstprobe, der rot wird. **Zuordnung: Frage an die Betreiberin (Q-BR-13) —
    entschieden am 24.09.2026: in BR mitbauen.**

    **Erledigt 24.09.2026 mit Konzept BR, Paket BR-05** (Zweig
    `claude/br-bestandsriegel`, ohne Versionsstufe). Vier Regeln mehr in
    `tools/quelltext/bestand.py`, elf statt sieben: `selbst` (Selbstprobe im
    Code ⇔ Name in `SELBST`; die Datei, die `starter()` startet, gibt es),
    `zeile` (je Name genau eine Tabellenzeile mit Anlass, keine Zeile ohne
    Namen), `ablauf` (jede Probe hängt an Muster, Riegel oder `nach`, jeder
    genannte Name existiert, jedes Muster hat seine fünf Felder und eine
    Stufe als `ab`) und `tabelle` (Abschnitt 4 ist die Ausgabe von
    `erzeugen-doku`; der Riegel ruft den Erzeuger, statt ihn nachzubauen).
    Am Bestand ein Befund, und der war echt: Die Selbstprobe der Textprobe
    (21 Fälle hinter `--probe`) lief nirgends — jetzt `--selbstprobe`, in
    `SELBST`, 9 von 9. Die erste Fassung der Regeln hat eine adversariale
    Gegenprobe durch unabhängige Instanzen nicht bestanden (29 echte
    Mängel, F-BR-22); die zweite liest Code, Tabellen, Bash-Listen und die
    Ablaufdatei so, wie ihre Verbraucher sie lesen — seit der vierten
    Fassung mit den echten Werkzeugen (`cmark-gfm`, `token_get_all`,
    `bash … --liste`, `auswahl.passt()`). Die vierte Runde (21 Mängel) hat
    den Umfang geschlossen: Wird `pruefen.sh --selbstprobe` überhaupt
    gerufen, wählt jede Datei einer Fläche des Berichts deren Bauprobe aus,
    steht eine veraltete Kopie der Tabelle irgendwo. Selbstprobe 140 Fälle,
    jede der 85 Befundstellen fällt (Konzept BR, Protokoll BR-05). Nebenbei gefunden:
    Nr. 317. `Pruefablauf.md` 6.12 nennt bei jedem
    Schritt das Mittel, das ihn meldet — „niemand" steht dort nicht mehr.

317. **Ein Pfad in der Zuordnung des Prüfstands traf seit PK-03 keine
    Datei.** *Aufgenommen und behoben 24.09.2026 mit Konzept BR (BR-05,
    F-BR-24), gefunden von der Gegenprobe des Bestandsriegels.* Das Muster
    `spur` in `tools/pruefstand/pruefablauf.json` nannte
    `server/api/spur*.php`; eine solche Datei gab es nie (`git log --all`).
    Die Spur-Endpunkte der Schnittstelle heißen
    `server/api/backup_spuren*.php` und lösen keine Spurprobe aus. Die
    Selbstprobe von `auswahl.py` prüfte den Pfad gegen eine erfundene Datei
    und blieb grün. *Behoben:* Pfad gestrichen — die Auswahl ändert sich
    nicht (`--abdeckung` vorher wie nachher); der Selbstprobenfall prüft
    einen Glob gegen eine versionierte Datei; `bestand` hält seither jeden
    Pfad gegen den Baum (Regel `ablauf`). *Entschieden 24.09.2026 von der
    Betreiberin (Konzept BR, Q-BR-14):* `server/api/backup_spuren*.php`
    steht im Muster `spur` — PK-03 hatte die Endpunkte gemeint und den
    Dateinamen verfehlt. Eine Berührung von `backup_spuren.php` oder
    `backup_spuren_restore.php` löst seither `spurprobe` und
    `containerprobe` aus. Die Selbstprobe von `auswahl.py` hat dafür einen
    Fall, der ohne den Pfad fehlschlägt (28 Lagen, gegengeprobt: 1 rot).

302. **Das Protokollarchiv hätte die 30-Tage-Zusage für IP-Adressen
    gebrochen, und ein Archiv in einem Guss hätte den Speicher gesprengt.**
    *Nachgetragen 24.09.2026 als Anlass der Protokollprobe (Konzept P5c,
    E-P5c-116; E-BR-07); gefunden in der Fassung 2 von Konzept P5c
    (F-P5c-18, -19), gebaut in P5c/AP2, Web 20.39.0.*
    `sicherheit_ereignisse` hat keine eigene IP-Spalte — die Adresse steht in
    `merkmal`, die E-Mail-Adresse in `wer`, und beide verfallen bewusst nach
    30 Tagen (E-P5a-09). Das Archiv liegt 365 Tage und geht außer Haus;
    hätte es die Spalten mitgenommen, wäre die Zusage mit dem ersten Archiv
    gebrochen, und niemand hätte es gesehen, denn das Archiv ist versiegelt.
    Dazu: `sk_versiegeln()` liefert eine Zeichenkette — ein Archiv in einem
    Guss hätte bei 64 MB den Speicher gesprengt; der Job schreibt deshalb in
    Häppchen. Nachweis: `tools/proben/protokoll/probe.php` (entsiegelt,
    0 Treffer für Adressen in Sicherheit und E-Mail).

303. **Ein Zweitfaktor hinter der Sitzung hätte API und sechs weitere
    Stellen mit dem bloßen Passwort offen gelassen.** *Nachgetragen
    24.09.2026 als Anlass der Zweitfaktorprobe (Konzept P5c, E-P5c-116;
    E-BR-07); gefunden in der Fassung 2 von Konzept P5c (F-P5c-31), gebaut
    in P5c/AP5, Web 20.42.0.* Setzt die Anmeldung `user_id`, bevor der Code
    gefragt ist, gilt das Konto überall als angemeldet, wo nur die Sitzung
    zählt — die API, die Downloads und die Seiten, die ein Tor danach nicht
    kennen; das Vormerkfach im `sessionStorage` hielte den Schlüssel länger.
    Die Code-Abfrage steht deshalb **vor** der Sitzung (E-P5c-53), in einer
    halben Sitzung mit fünf Minuten Frist. Nachweis:
    `tools/proben/zweitfaktor/probe.php` (nach dem Passwort keine Sitzung,
    API 401; RFC-Vektoren 6 / 6).

319. **Der Rückweg beim Zweitfaktor prüfte gegen einen Wert, den jeder
    Datenbankabzug enthält.** *Nachgetragen 24.09.2026 als Anlass der
    Rückwegprobe (Konzept P5c, E-P5c-116; E-BR-07); gefunden vor P5c/AP5
    (F-P5c-106), gelöst mit Konzept RW (10c AP5b), Web 20.43.0 bis 20.45.0.*
    E-P5c-42 sah vor, den Zweitfaktor mit dem Wiederherstellungsschlüssel
    zurückzusetzen, und prüfte dafür gegen `pat_key_check` —
    `SHA-256('edk-ckchk:' + ck)`, gespeichert in der Datenbank. Wer einen
    Abzug hatte, legte den Wert vor; mit Zugang zum Postfach hätte das
    genügt, Passwort **und** Zweitfaktor zurückzusetzen. Gebaut wurde
    stattdessen ein Schlüsselpaar je Konto: Der private Teil liegt unter dem
    Inhaltsschlüssel, der Server prüft eine Signatur über eine eigene
    Herausforderung. Nachweis: `tools/proben/rueckweg/probe.sh`
    (Abzug-Gegenprobe: 73 Versuche, 0 Erfolge; die alte Fassung mit
    `pat_key_check` abgewiesen, 4 / 4).

320. **Ohne bekanntes Geheimnis wäre jedes Werkzeug nach der Anmeldung im
    Einrichtungstor gelandet.** *Nachgetragen 24.09.2026 als Anlass von
    `tools/zweitfaktor/` (Konzept P5c, E-P5c-116; E-BR-07); gefunden in der
    Fassung 2 von Konzept P5c (F-P5c-33), gebaut in P5c/AP5, Web 20.42.0.*
    30 Werkzeugdateien melden sich an, 15 davon mit dem Prüfkonto
    `admin@gen-em.org` — einer BetreiberIn, für die der Zweitfaktor Pflicht
    ist. Mit einem unbekannten Geheimnis käme keines durch den Code-Schritt,
    und Stufe 2 würde rot. Gelöst ohne Schalter, der die Pflicht abschaltet:
    Das Prüfkonto hat einen echten Zweitfaktor mit einem Geheimnis, das ein
    Rechner je Sprache kennt (E-P5c-43). Nachweis: jeder Lauf, der sich
    anmeldet; die Rechner gegen den RFC-Vektor in der Zweitfaktorprobe.

190. **Die Statistikseite lässt das virtuelle Gerät stehen — „Ohne Gerät"
    zählt zu niedrig.**
    *Aufgenommen 14.09.2026 bei der Bestandsaufnahme zu R42.*
    `server/db.php` führt die Konstante `GERAETE_ECHT_SQL`
    (`device_id NOT LIKE 'manual-%'`), damit das virtuelle Gerät der
    Handeinträge an **einer** Stelle beschrieben ist. Fünf Abfragen benutzen
    sie — zweimal `db.php`, dazu `einstellungen.php`, `admin_demo.php` und
    `tools/referenzdatensatz/fixture/erzeugen.php`. Drei schreiben das `LIKE`
    von Hand (`admin_users.php`, `admin_user.php` und die Geräteabfrage in
    `betrieb_statistik.php`), und **eine hat gar keine Bedingung**: die
    Kontenabfrage derselben Datei, aus der die Zeile „Ohne Gerät" kommt. Sie
    fragt schlicht `NOT EXISTS (SELECT 1 FROM devices …)`.

    **Das ist eine falsche Zahl, kein Schönheitsfehler.** Das virtuelle Gerät
    ist eine echte `devices`-Zeile (Bezeichnung „Manuelle Einträge",
    `active = 0`) und entsteht an **vier** Stellen: beim ersten Handeintrag
    (`einsatz_form.php`), beim CSV-Import (`api/import_commit.php`), beim
    Schneiden (`api/schneiden.php`) und beim GPX-Import
    (`api/gpx_import.php`) — viermal derselbe `$devKey`. (`db.php` sagt es
    **nicht**: Der Kopf der Konstanten ist eine Zeile, und der Kopf von
    `geraete_des_kontos()` nennt zwei Anlässe — „von Hand anlegt oder
    importiert" —, das Schneiden gar nicht.)
    Wer ausschließlich von Hand dokumentiert oder auch nur einmal eine
    GPX-Datei einliest, hat damit eine Gerätezeile und fällt aus „Ohne Gerät"
    heraus. Ausgerechnet aus der Gruppe, deren Kleinzeile „sie tragen von
    Hand nach" genau diese Menschen meint.

    **Weg:** `GERAETE_ECHT_SQL` in beide Abfragen der Statistikseite; die
    beiden handgeschriebenen Zwillinge in `admin_users.php` und
    `admin_user.php` filtern zwar richtig, gehören aber in denselben Griff.

    **Zuordnung (23.09.2026): 10c AP7** — als Beifang, weil AP7 die
    Statistikseite ohnehin umbaut (E-P5c-46).
    Danach steht das Muster an einer Stelle statt an vieren. *Abnahme:* Ein
    Konto ohne gekoppeltes Gerät, aber mit einem Handeintrag steht in „Ohne
    Gerät"; die Kachel „Geräte" ändert sich dabei nicht. Zuordnung:
    **Backlog-Runde.**

    **Erledigt mit Web 20.47.0 (P5c/AP7, 24.09.2026).** „Ohne Gerät" zählt
    nur noch echte Geräte (`geraete_echt_sql('d')`); die Geräteabfrage der
    Seite benutzt denselben Helfer statt des handgeschriebenen `LIKE`. Die
    beiden Zwillinge in `admin_users.php` und `admin_user.php` bleiben — sie
    filtern richtig, und AP7 baut sie nicht um. Nachweis im Prüfdokument P5c,
    Abschnitt 1f (ein Konto mit Handeintrag, aber ohne Gerät, steht unter
    „Ohne Gerät"; die Kachel „Geräte" bleibt).

191. **Der von R38 bestellte Index auf `missions(started_at)` ist nie gelegt
    worden.**
    *Aufgenommen 14.09.2026 bei der Bestandsaufnahme zu R42.*
    R38 bestellt für die Einsatzzählung des Betriebslage-Dashboards wörtlich
    einen Index auf `missions(started_at)` und begründet ihn: „der vorhandene
    führt mit `user_id` und trägt die kontenübergreifende Zählung nicht"
    (`docs/Rahmenplan-Archiv.md`, R38). `server/schema.sql` führt an
    `missions` genau `uq_dev_ref`, `idx_user_started (user_id, started_at)`
    und `idx_day` — mehr nicht.

    **Warum das bis heute niemandem auffiel:** Die Statistikseite aus S8
    zählt nach **Diensttag** (`days.day`) und kommt ohne ihn aus. Das
    Dashboard nach R38 zählt nach `started_at` und braucht ihn — er ist die
    einzige Schemaarbeit, die der Minimalumfang überhaupt vorschreibt.
    Vorziehen muss man ihn nicht: Ohne die Zählung, für die er da ist, kostet
    er nur Schreiblast. Er hängt außerdem an **Nr. 192** — nötig ist er nur,
    wenn dort `started_at` gewinnt. *Abnahme:* Die Migration liegt, und
    `EXPLAIN` zeigt den Index an einer kontenübergreifenden Zeitraumzählung.
    Zuordnung: **P5**, mit dem Dashboard.

    **Zuordnung (20.09.2026): 10c, AP7** — der Index wird mit der Betriebslage gelegt (E-P5c-18).

    **Erledigt mit Web 20.47.0 (P5c/AP7, 24.09.2026).** Migration
    `2026_09_24_statistik_beginn` legt `idx_missions_started (started_at)`
    und `idx_missions_deleted`, wo er fehlt — den hatten bis dahin nur
    migrierte Anlagen (F-P5c-39, -124); `schema.sql` führt beide für frische
    Anlagen. `EXPLAIN` der Abfrage aus `statistik_lib.php` misst der
    Messstand (Schritt `statistik`). **Nach dem Deploy `update.php`, die
    Wartung bleibt an.**

192. **R38 und die Statistikseite aus S8 zählen Verschiedenes — „aktiv", die
    Fenster und die Zählgröße.**
    *Aufgenommen 14.09.2026 bei der Bestandsaufnahme zu R42.*
    Der feste Minimalumfang des Betriebslage-Dashboards (R38) legt drei Dinge
    fest, und die gebaute Seite macht alle drei anders. Das ist zunächst kein
    Fehler: Die Seite **setzt R38 nicht um** — sie ist der nach E-S8-05
    vorgezogene Teil von Nr. 80 und beantwortet eine andere Frage („was trägt
    diese Installation"). Entsteht das Dashboard aber, stehen zwei Zählweisen
    nebeneinander, und das wären zwei Wahrheiten.

    | R38 verlangt | Die Seite tut |
    |---|---|
    | „aktiv" = `users.last_login` **oder** `devices.last_seen` im Fenster | zwei getrennte Zeilen in zwei Karten, nie verodert |
    | Konten in **24 h / 7 T / 30 T**, Einsätze in **24 h / 7 T / 30 T / 6 M / 1 J** | drei Fenster, **7 / 30 / 180 Tage** (`STAT_ZEITRAEUME`) |
    | Einsätze nach `started_at`, **nicht** `created_at` — „ein Alt-Import verzerrte sonst die Aktivität" | nach **Diensttag** (`days.day`), im Kopfkommentar ausdrücklich begründet |

    Die dritte Zeile ist die unangenehmste: Hier stehen sich **zwei
    ausformulierte Begründungen** gegenüber, nicht eine Vorgabe und ein
    Versehen. Und die erste hat eine Wirkung, die R38 ausdrücklich verhindern
    wollte — wer nur mit der Uhr arbeitet und sich nie anmeldet, erscheint
    unter „Zuletzt angemeldet" als tot.

    **Nr. 122 berührt dieselben drei Fenster** („Freie Zeiträume und
    Diagramme in der Statistik") — verlangt aber etwas anderes, nämlich frei
    wählbare Zeiträume, und nennt den Widerspruch zu R38 nicht. Zu
    entscheiden, **bevor** das Dashboard gebaut wird: ob die Seite nachzieht
    oder R38 berichtigt wird. Beides ist vertretbar, beides nebeneinander
    stehen zu lassen nicht. *Abnahme:* Die Entscheidung steht im Rahmenplan,
    und R38 und die Seite beschreiben dieselbe Zählung. Zuordnung:
    **Entscheidung in einer Backlog-Runde, Umsetzung P5** — wie bei Nr. 122.

    **Zuordnung (20.09.2026): 10c, AP7** — die Zählung heißt dort sichtbar „Bestand" (E-P5c-18).
    *(Überholt, vermerkt 23.09.2026: Die Zählung heißt nicht „Bestand" — der
    Absatz darunter gilt.)*


    **Zuordnung (20.09.2026): erledigt sich mit 10c AP7.** Dort entsteht
    eine **Zählung ab Beginn des Einsatzes** (E-P5c-18) — damit ist die
    Zählgröße entschieden, und die beiden Zählweisen stehen nicht mehr
    nebeneinander.

    **Erledigt mit Web 20.47.0 (P5c/AP7, 24.09.2026).** Die Seite zählt jetzt
    so, wie R38 es verlangt: „aktiv" als `last_login` **oder**
    `devices.last_seen` eines echten Geräts, Fenster 24 h / 7 T / 30 T (Konten)
    und 24 h / 7 T / 30 T / 6 M / 1 J (Einsätze), Einsätze ab `started_at` —
    mit Obergrenze. Die Zählung nach Diensttag ist auf dieser Seite entfallen;
    es gibt keine zwei Zählweisen mehr nebeneinander (E-P5c-18).

168. **Zentrale Stammdaten vollständig zurückbauen — damit kein
    Überbleibsel bleibt.** *Aufgenommen 09.09.2026, zugeordnet **P5**
    (Rahmenplan R39, Beschluss vom 30.08.2026).* **Die Tür ist zu seit
    Web 18.0.0** (S9/AP5b): `admin_stammdaten.php` ist ersatzlos gestrichen,
    die Karte „Vordefinierte Standorte" und der Schreibweg `ub_toggle` mit
    ihr — **kein Schema, keine Migration**. Damit kann keine neue Zeile mit
    `user_id IS NULL` mehr entstehen, und die Vorbedingung unten hält von
    selbst. Der eigentliche
    Rückbau steht aus, und ohne ihn bleibt das Modell im Schema, in den
    Sicherungsformaten und in der Dokumentation stehen, obwohl es keine Daten
    mehr trägt. Die Fundstellen sind aufgenommen:
    `docs/konzepte/Bestandsaufnahme-R39-Zentrale-Stammdaten.md`, **208
    Befunde** auf sechs Flächen (23 Schema, 71 Code, 83 Dokumentation, 22
    Prüfmittel, 9 Daten) — das Dokument bleibt bis P5 liegen und wird
    danach gelöscht wie ein Konzept.

    *Was der Rückbau umfasst:* **(1)** `user_id` in `bases`, `vehicles`,
    `crew_presets`, `resources`, `bw_units` und `transport_dests` auf
    `NOT NULL` ziehen; **(2)** `user_bases` samt Auswahlweg entfernen (E16);
    **(3)** das Feld `stammdaten.user_bases` aus der Nutzlast der
    Kontosicherung nehmen, Nutzlastversion heben, den Import ältere Pakete
    still darüber hinweglesen lassen; **(4)** `admin_stammdaten.php` samt
    Menüeintrag entfernen; **(5)** die Abfragen entschlacken, die heute
    „eigen ODER zentral" fragen (`dt_base_erlaubt()`, `dt_bases()`,
    `dt_vehicles()`, die Dublettenprüfung, der Einspielweg); **(6)**
    Dokumentation austragen (`docs/Technik.md` Datenmodell,
    `docs/Backup-Format.md`, `docs/Handbuch.md`); **(7)** die Prüfmittel
    nachziehen (Platzhalter `__ADMIN_STANDORT__` des Bilderlaufs, der
    Klickprobenweg zu Nr. 163, die Umlaufausnahmen des Referenzbestands).

    *Vorbedingung, die vor dem `ALTER TABLE` zu messen ist:* **0 Zeilen mit
    `user_id IS NULL`** in allen sechs Tabellen. Steht auch nur eine da,
    bricht die Änderung ab, und MySQL kennt kein Zurückrollen von
    Schemaänderungen — die Installation bliebe auf halbem Weg stehen. Die
    geschlossene Tür aus S9 sorgt dafür, dass diese Null von da an hält;
    ~~vorhandene Einträge lassen sich über die Verwaltung noch löschen.~~
    **Berichtigt 23.09.2026 (E-P5c-48):** Das stimmt seit S9/AP5b nicht mehr —
    die Seite dafür ist gestrichen. Der Fall **tritt aber nicht auf**: Auf der
    einen laufenden Anlage sind alle zentralen Einträge gelöscht (Auskunft des
    Auftraggebers). Die Migration **zählt trotzdem vorher** und blockiert mit
    Torwächter-Meldung, wenn sie etwas findet — das schützt eine Anlage, in
    die jemand eine alte Sicherung einspielt. Die Vorzählung ist eine eigene
    Vorbedingung auf `user_id IS NULL`; `migrationen_inhalt_zaehlen()` zählt
    das Gegenteil. Ein eigener Runbook-Abschnitt entfällt.

    *Abnahme („keine Überbleibsel"):* `grep -rn "zentral" server/` nennt
    keine Stammdatenstelle mehr; `grep -rn "user_bases" server/ docs/` ist
    **0**; Register und `SHOW CREATE TABLE` sind zwischen frischer
    Installation und migrierter Datenbank strukturgleich; die Kreisläufe
    `edbak`, `edbak-alt` und `csv` laufen mit **0 unerklärten** Abweichungen;
    eine Sicherung im alten Format spielt weiterhin ein.

    **Zuordnung (20.09.2026): 10c, AP8** (R39-Rest, E-P5c-19).

    **Erledigt mit Web 21.0.0 (P5c/AP8, 25.09.2026).** Die sieben Punkte:
    **(1)** `user_id NOT NULL` in allen sechs Tabellen — Migration
    `2026_09_25_zentrale_stammdaten`, mit einer **Vorbedingung**, die vorher
    zählt und ohne Freigabe sperrt (E-P5c-125); **(2)** die Auswahltabelle
    ist in derselben Migration gefallen, der Auswahlweg schon mit S9/AP5b;
    **(3)** Nutzlast 12 ohne das Feld, ältere Pakete überlesen es still
    (`EDBAK_NUTZLAST`; die neue Toleranz steht in Nr. 46); **(4)** schon mit
    S9/AP5b (Web 18.0.0); **(5)** `dt_base_erlaubt()`, `dt_vehicle_erlaubt()`,
    `dt_bases()`, `dt_vehicles()`, die Vorlagen in Einsatzformular und
    `api/day.php`, Standortseiten, Nachbearbeitung und Einspielweg fragen
    `user_id = ?`, `stammdaten_dup_global()` ist mit ihren dreizehn Aufrufen
    fort; **(6)** `Technik.md`, `Backup-Format.md`, `Handbuch.md`, `Design.md`;
    **(7)** der Platzhalter und der Klickprobenweg schon mit S9/AP5b, die
    Umlaufausnahmen jetzt (je zwei Übergangsregeln, siehe Nr. 323).
    **Die Abnahme ist berichtigt** (F-P5c-38): `grep -rn user_bases server/
    docs/` ist ohne Geschichtsfälschung nicht 0 — CHANGELOG, Backlog,
    Konzepte und die zwei gelaufenen Migrationen nennen die Tabelle zu Recht.
    Gemessen wurde die engere Fassung aus dem Konzept: in `server/` ohne
    `version.php` und die gelaufenen Migrationen **13 → 0** (hier stand
    bis Web 21.1.0 „15", F-P5c-129; außer der neuen
    Löschmigration), in `docs/` ohne Changelog, Backlog und `konzepte/`
    **3 → 0**. Kreisläufe und Schemaprobe siehe Rahmenplan 8.
    **Eine Lehre, damit sie nicht nur in der gelöschten Bestandsaufnahme
    steht:** Das S9-Konzept hat R39 nie aufgenommen (`grep -c R39` über
    `docs/konzepte/` ergab am 09.09.2026 **0**), und in S9/AP5-4 ist deshalb
    `admin_stammdaten.php` neu gebaut worden — eine Seite für ein Modell, das
    seit dem 30.08.2026 abgeschafft werden sollte; S9/AP5b hat sie wieder
    gestrichen. Ein Programmbeschluss, der nicht im Konzept des Schritts
    steht, der ihn berührt, wird dort nicht umgesetzt, sondern umgangen.

169. **Ein Diensttag mit „Anderem Rettungsmittel" kann keine Besatzung
    festhalten.** *Aufgenommen 09.09.2026 beim Beantworten von Frage 11
    (S9/AP6, Web 18.1.1).* Ein Rettungsmittel nur für den Tag führt keine
    Besatzungsrollen (E-S9-10, F19). Das gilt seit Web 18.1.1
    **gleichmäßig** — vorher bot ein aus einer früheren Zuordnung
    umgestellter Tag die alten Rollen an, ein frisch angelegter keine. Die
    Gleichmäßigkeit legt die Lücke frei: Es gibt an einem solchen Tag
    **keinen** Weg, einen Besatzungsnamen einzutragen, weder am Tag noch am
    einzelnen Einsatz — beide fragen denselben Rollensatz.

    *Warum das nicht nebenbei zu schließen ist:* Der Rollensatz kommt aus
    `vehicle_roles` des Stammdatensatzes, und einen solchen gibt es hier
    gerade nicht. Drei Wege sind denkbar, und sie unterscheiden sich in dem,
    was sie versprechen:
    **(a)** Der Adhoc-Dialog bekommt Rollenhaken wie das
    Stammdatenformular — ehrlich, aber er wächst um sieben Felder und wird
    damit zu dem Formular, das er nicht sein wollte.
    **(b)** Der Tag bietet die Rollen an, die zu seiner **Betriebsart**
    passen (luft/boden) — billig, aber es ist geraten, und E26 sagt
    ausdrücklich: geraten wird nicht.
    **(c)** So lassen und im Text sagen (heutiger Stand): Wer die Besatzung
    braucht, legt das Rettungsmittel an. Kostet einen Stammdatensatz, den
    F17 gerade ersparen wollte.

    *Bis zur Entscheidung gilt (c).* Hinweis im Tagesformular und Handbuch
    sagen es seit Web 18.1.1 zutreffend; vorher verwiesen beide auf die
    abweichende Besatzung am Einsatz, wo dieselbe Sperre greift.
    **Am 12.09.2026 vertagt auf P5** — dort wird über die Rettungsmittel
    ohnehin entschieden. Bis dahin gilt (c), und Hinweis und Handbuch sagen
    es zutreffend; kein dritter Zustand. Zuordnung: **P5**.
    *Abnahme:* Ein Diensttag mit „Anderem Rettungsmittel" erlaubt einen
    Besatzungsnamen — oder der Text sagt weiterhin richtig, dass er es nicht
    tut. Kein dritter Zustand.

    **Zuordnung (20.09.2026): 10c, AP8** (R39-Rest, E-P5c-19).

    **Entschieden 23.09.2026 (E-P5c-47): Weg (b)** — der Tag bietet alle
    Rollen der gewählten Betriebsart an. Geraten ist das nicht: Die
    Betriebsart wird im Dialog ausdrücklich gewählt (`adhoc_kind` in
    `index.php`), und `CREW_ROLES` trägt `kind` air/ground/both. Abnahme etwa:
    „Tag Luft zeigt p1, p2, hems, fr, other". Zuordnung bleibt **10c AP8**.

    **Erledigt mit Web 21.0.0 (P5c/AP8, 25.09.2026), Weg (b).** Der Tag
    bietet die Rollen seiner Betriebsart an — in der Luft p1, p2, hems, fr,
    other; am Boden driver, trainee, other, **gleich welcher Typ**
    (E-P5c-126: ein gespeichertes Rettungsmittel vom Typ Bergwacht,
    Veranstaltung oder Sonstiges hat keine Rollen-Vorlagen, das
    Tagesrettungsmittel hat keine Vorlage, aus der man wählen könnte) — beim Zuordnen
    (`dt_zuordnen()`), in der Vorschau (`api/day.php?vorschau=adhoc`) und im
    Einsatzformular, über **eine** Funktion (`dt_tagesrettungsmittel_rollen()`).
    Tage von vorher bekommen den Satz per Migration
    (`2026_09_25_tagesrettungsmittel_rollen`, E-P5c-123); eine
    Wiederherstellung legt weiter an, was in der Datei steht (E8). Gemessen
    von der Bedienprobe: `p5c-ap8-adhoc-tag-in-der-luft` (dreimal
    p1, p2, hems, fr, other) und vier umgedrehte Wege aus S9/AP6. Die
    Abnahme „erlaubt einen Besatzungsnamen" ist erfüllt; kein dritter Zustand.

325. **Die GPX-Probe vergleicht auf frischer Anlage nichts, bis der
    Nachlauf gelaufen ist.** *Aufgenommen 25.09.2026 in P5c/AP8
    (F-P5c-137), gemessen.* Teil 2 hält die serverseitig gebauten GPX-Dateien
    des Demo-Kontos gegen den Referenzexport aus dem Browser und überspringt
    jede Spur, die `spur_stand()` nicht als Stufe 2 meldet. Nach
    `hochfahren.sh --neu` liegen aber **alle** Demo-Spuren als Zeilen vor
    (Stufe 1, 0 Blobs), und keine der Proben davor fährt den Nachlauf — der
    Huckepack-Weg kommt höchstens alle 300 s. Ergebnis im Prüfstand:
    „0 von 204 Dateien verglichen; übersprungen: 204 verdichtet", rot.
    **Mit dem Code von AP7 (`d519fac`) dasselbe**, auf frischer Anlage in
    derselben Abfolge nachgestellt — kein Fehler der Anwendung. Allein
    gefahren, nachdem der Nachlauf gepackt hat: 95 / 0, 115 von 204
    Dateien, 137 860 Einzelvergleiche, 0 Abweichungen.

    *Warum nicht einfach Stufe 1 mitvergleichen:* Versucht und
    zurückgenommen — **42 Abweichungen**. Der Referenzexport trägt ältere
    Spuren **ausgedünnt** (`mission_000001`: 113 Punkte gegen 443 roh); ob
    eine Spur vergleichbar ist, hängt am Nachlauf, nicht an der Stufe
    allein.

    *Weg:* Die Probe stellt ihre Lage selbst her (R84: die Lage herstellen,
    nicht auf sie hoffen) — vor Teil 2 `verdichtung` und `ausduennen` bis
    zum Rückstand 0, über `jobs_lib.php`, wie es der Nachlauf täte. Danach
    ist die Zahl der Vergleiche fest und kann als Untergrenze dastehen.
    Verwandt mit Nr. 322 (Reihenfolge gegen den Demo-Reset): beide Male hängt
    eine Probe am Zustand des Demo-Kontos, den sie nicht selbst herstellt.
    *Zuordnung:* **vor dem Pull Request von P5c** — dessen Bericht muss
    grün sein. *Abnahme:* Prüfstand auf frischer Anlage, GPX-Probe grün
    mit mindestens 100 verglichenen Dateien; Gegenprobe ohne den
    Vorlauf rot.

    **Erledigt 25.09.2026 (P5c, zwischen AP8 und AP9; nur `tools/`, keine
    Versionsstufe).** Die Probe hat einen **Vorlauf** vor ihrer eigenen
    Job-Pause: `jobs_lauf('cli', ['verdichtung', 'ausduennen'])`, bis beide
    Jobs in einer Runde fertig melden und nichts erledigen. Zwei Dinge hat
    erst der Bau gezeigt: **Die Probe hält die Jobs selbst an**
    (`jobs_pause(900)`, für ihre Probedaten) — ein Vorlauf dahinter meldete
    nur „angehalten" und packte nichts; und **`rueckstand` wird nicht null**
    (55 und 10, Runde für Runde bei 0 erledigt), weil er auch Spuren zählt,
    die noch nicht dran sind. Gemessen auf frischer Anlage in der Abfolge
    des Prüfstands: Vorlauf 2 Runden, 247 Spuren; **96 / 0**, 115 von 204
    Dateien, 137 860 Einzelvergleiche, 0 Abweichungen. Gegenprobe mit 0
    Runden: rot, 0 von 204. Eine feste Untergrenze steht bewusst **nicht**
    da: Mit dem Alter der Demo-Einsätze werden mehr Spuren ausgedünnt, die
    Zahl sinkt legitim; `$dateien > 0` bleibt der Riegel gegen „0
    Vergleiche".

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

    **Zuordnung (20.09.2026): 10c, AP9** — die Rechtstextseiten werden dort ohnehin angefasst (E-P5c-20).

    **Erledigt mit Web 21.1.0 (P5c/AP9, 25.09.2026, E-P5c-28).** Die
    Rechtstexte sind wieder eine eigene Seite (`admin_rechtstexte.php`), ein
    Reiter je Text, ab 1200 px die Vorschau neben dem Feld. Sie läuft beim
    Tippen mit: 0,4 s nach dem letzten Tastendruck fragt
    `assets/rechtstext_vorschau.js` den Endpunkt `api/rechtstext_vorschau.php`,
    der mit `rt_html()` rendert — derselbe Renderer wie die öffentliche Seite,
    kein zweiter im Browser. Nur Admin und BetreiberIn, Token, eigener Topf
    `rt_vorschau` (nur das Konto, F-P5c-155). Ohne Skript bleibt der
    gespeicherte Stand. Belegt: Rollenprobe 304 von 304 (12 neue Zellen),
    Bedienwege `admin-rechtstexte-vorschau` und `-rueckfrage` 2 von 2, die
    Gegenprobe ohne Token rot.

244. **Einstellungen-Übersicht: die drei Bereiche sind als Gliederung nicht
    erkennbar.** *Aufgenommen 18.09.2026, präzisiert 20.09.2026
    (Auftraggeber).* Zugeordnet: **10c, AP9**.

    In der Übersicht (`ui_einstellungen_uebersicht()`) gehen die
    Bereichsnamen Einstellungen / Verwaltung / Betrieb als oberste Ebene
    unter.

    **Berichtigt am 20.09.2026 (M-P5c-01, E-P5c-29):** Gemeint war vor allem
    das **linke Menü** (`ui_leiste_einstellungen()`), nicht die Übersicht —
    dort fällt die fehlende Gliederung zuerst auf, weil man es auf jeder
    Seite sieht. **Entschieden und freigegeben:** Die **Leiste** bekommt
    Bereichsüberschriften nach **Option 1 „Linie"**; die **Übersicht**
    bekommt je Bereich eine **eigene Karte** mit Bereichszeichen und mittigem
    Kopf. **Der Fable-Schritt ist damit erledigt** — es braucht kein weiteres
    Mockup.

    *(Überholt, vermerkt 23.09.2026 — der Absatz „Berichtigt am 20.09.2026"
    darüber gilt; einen Fable-Schritt gibt es nicht mehr:)* Entschieden:
    zuerst ein Mockup mit einer klaren
    **Überschriftenzeile je Bereich** (Bricolage, Abstand davor, Linie);
    trägt das nicht, bekommt jeder Bereich eine **eigene Karte** mit seiner
    Liste. Fable-Schritt (Mockup), Umsetzung klein; das Mockup läuft mit den
    übrigen 10c-Mockups in einer Runde.

    **Erledigt mit Web 21.1.0 (P5c/AP9, 25.09.2026, E-P5c-29).** Die
    Übersicht zeigt jeden Bereich als eigene Karte mit Zeichen und Zahl im
    Kopf (Bereichskarten), die Leiste ihre Blöcke als Überschriften mit dem
    Winkel rechts und einer Linie dazwischen (Option 1). Gemessen bei 1440 und
    390 px gegen das Mockup. **Die Leiste, ohne Rollen erreichbar** (18
    Einträge, 14 Seiten): bei 1280 × 720 mit den Sprungmarken auf 6 Seiten
    vollständig — sie fallen deshalb unter 800 px Fensterhöhe weg
    (E-P5c-131), dann auf 14 von 14; bei 1280 × 900 auf 13 von 14, die
    Servereinstellungen (acht Sprungmarken) bei 16 von 18. **Mit Web 21.1.3
    liegt die Schwelle bei 950 px** (F-P5c-164, Entscheidung der
    Betreiberin): bei 800 bis 1000 px Höhe überall 14 von 14.

245. **Erklärtext-Regel — und danach alle Texte in Verwaltung und Betrieb.**
    *Aufgenommen 18.09.2026, präzisiert 20.09.2026.* Zugeordnet:
    **10c, AP9**.

    Gemeint ist der **gesamte** Bereich Verwaltung und Betrieb unter
    Einstellungen: viel Erklärtext, viele neue Funktionen seit S8–S10 und
    P5a.

    **Entschieden als Grundregel für `docs/Design.md`:** In der Oberfläche
    steht je Karte **höchstens ein Satz**, der sagt, was hier passiert;
    alles Erklärende steht im **Handbuch**, die Karte trägt den Verweis auf
    die Sprungmarke (`hilfe.php#abschnitt`, E-P5b-08). Warnungen bleiben als
    Meldung, Feldhinweise bleiben eine Zeile.

    **Folge:** Alle Texte des Bereichs werden nach der Regel überarbeitet;
    der ausgelagerte Text **wandert ins Handbuch und wird nicht gelöscht**.
    Voraussetzung: 10b AP8 (Handbuch aus der Anwendung erreichbar).
    Abnahme: ein Textpaket mit Wortliste und Bilderlauf; gezählt werden
    Sätze je Karte (Ziel ≤ 1) und Handbuch-Verweise (Ziel ≥ 1 je Karte mit
    ausgelagertem Text).

    **Erledigt mit Web 21.1.0 (P5c/AP9, 25.09.2026, E-P5c-06, -49, -128).**
    Alle Seiten unter Verwaltung und Betrieb folgen der Ein-Satz-Regel; die
    Karten „Was hier gilt" sind fort, ihr Inhalt steht im Handbuch unter
    eigenen Sprungmarken, und die Ankerprüfung im Tor hält jeden Verweis
    `hilfe.php#…` gegen das gerenderte Handbuch. Die Endzählung nach der
    Zählregel Fassung 2 und ihre unabhängige Gegenprobe stehen im Konzept P5c
    und im Prüfdokument.

246. **Schlüsselblatt und Notfallblatt: eine Druckseite, die eine ist.**
    *Aufgenommen 18.09.2026, präzisiert 20.09.2026.* Zugeordnet:
    **10c, AP9**.

    Drei Vorgaben für `betrieb_schluesselblatt.php` (S10) und das
    Notfallblatt (P5b AP9):

    (a) Der Schlüssel steht **abgesetzt in einer Kachel** — Rahmen, Rauch,
    Vierergruppen, Feste Schrift.
    (b) Der Druck passt **genau auf eine A4-Seite** (`@page A4`, Ränder,
    kein Umbruch). Abnahme: PDF-Druck aus Chromium **und** Firefox hat je
    eine Seite.
    (c) Oben stehen **Marke und „NAdoku"** (das Wort-Bild-Logo) und die
    Überschrift, damit sofort klar ist, worum es geht.

    **Das ändert E-P5b-09** („ohne Logo" → mit Logo). Beide Blätter
    bekommen denselben Baustein (`.blatt-druck`); 10b AP9 ist gebaut, das
    Notfallblatt wird dort nachgezogen.


    **Berichtigt am 20.09.2026 (M-P5c-01f, E-P5c-08/-30):** Oben steht
    **nicht** „Marke + NAdoku", sondern **Bildmarke + Kurzname der
    Installation** (`instanz_kurz()`, Vorgabe „Gen-EM NAdoku"). Ein fest
    eingebautes „NAdoku" wäre auf einer umbenannten Installation schlicht
    falsch. Die **Webversion steht in der Fußzeile** des Blattes.
    **Freigegeben am Bild M-P5c-01f** (`docs/konzepte/konzept-p5c/mockups/`,
    gelöscht mit dem Abschluss von P5c, Historie `ae829e6`),
    beide Blätter mit demselben Baustein `.blatt-druck`.

    **Erledigt mit Web 21.1.0 (P5c/AP9, 25.09.2026, E-P5c-08, -30, -50).**
    Schlüssel- und Notfallblatt stehen auf dem Druckblatt `.blatt-druck` des
    Codeblatts: nummerierte Vierergruppen, Umgebungszeile auf Staging, genau
    eine A4-Seite. Der Härtefall (drei Werte, Staging) misst 1013 von 1017 px
    Satzhöhe (F-P5c-152), Produktiv 978, das Notfallblatt 711.

253. **Datum-Zeit-Trenner vereinheitlichen.** *Aufgenommen 20.09.2026
    (Konzept Zentralisierung, F-ZE-3/FF-5).* Zugeordnet: **10c AP9**.

    Zwischen Datum und Uhrzeit steht mal ein Komma, mal ein Gedankenstrich,
    mal nur ein Leerzeichen. Nach Schritt 15 AP7 steht die Formatierung an
    einer Stelle (`format_lib.php`) — dann ist es eine Zeile statt einer
    Suche, und deshalb wartet es bis dahin.

    **Berichtigt 23.09.2026:** Gemeint ist ` · ` (Mittelpunkt mit
    Leerzeichen), kein Gedankenstrich. **Entschieden: das Komma** (Konzept
    P5c, E-P5c-37). Es ist auch nicht „eine Zeile": rund 18 Stellen in 11
    Dateien (F-P5c-11). Zuordnung: **10c AP2** (die neue Protokollzeile
    schreibt schon mit Komma) **und AP9** (die Vorgabe und die übrigen
    Stellen).

    **Erledigt mit Web 21.1.0 (P5c/AP9, 25.09.2026, E-P5c-37).** Datum und
    Uhrzeit trennt überall ein Komma (`datum_zeit_text()` ohne zweites
    Argument). Zwei Stellen übergeben bewusst einen anderen Trenner: die
    Mailtexte („ um ") und der GPX-Spurname, weil `Export-Format.md` ihn
    festlegt (E-P5c-129). Register Z26 nennt sie.

269. **`assets/schluesselblatt.js` — bei Netzausfall eine stille Sackgasse.**
    Gefunden bei derselben Vermessung. Der Prüfknopf setzt `disabled = true`
    **vor** dem Senden, und die Wiederfreigabe liegt im `.then`. Wirft das
    `fetch`, fängt niemand: Der Knopf bleibt tot, das Fehlerfeld leer, der
    Dialog offen. Die Person kann weder weiter noch erkennen, warum.
    Zwei Aufrufer sind betroffen (Prüfen und Antworten). **Nicht in
    Schritt 15 behoben.** Beim Anfassen mitzudenken: `EdApi.postForm()` aus
    AP8c liefert im Netzfehler ein `{ ok: false, status: 0 }` statt zu
    werfen — damit ist die Stelle danach mit drei Zeilen zu heilen.

    **Zuordnung (23.09.2026): 10c AP9** — als Beifang (E-P5c-46).

    **Erledigt mit Web 21.1.0 (P5c/AP9, 25.09.2026).** Seit Schritt 15
    sendet die Datei über `EdApi.postForm()`; übrig war, dass ein
    Netzfehler beim Prüfen die vier Felder leerte wie eine falsche Eingabe.
    Jetzt bleibt bei `status: 0` alles stehen, und der Knopf wird wieder
    frei. Belegt im Browser mit abgebrochenem Abruf, für Prüfen und für die
    Rückfrage.

326. **Bleibt die Karte „Ausgeführt" auf Betrieb → Updates?** *Aufgenommen
    25.09.2026 in P5c/AP9 (F-P5c-146), berichtigt in P5c/AP11.* Die Karte
    versprach, sie stehe „bis P5" dort, danach führe das Audit-Protokoll die
    ausgeführten Kennungen (R66), und sie entfalle. AP9 hat den Satz
    gestrichen und diesen Eintrag mit der Begründung angelegt, das Protokoll
    schreibe keine Migrationen. **Das war falsch** (Gegenlesung AP11): Seit
    P5c/AP2 schreibt `migrationen_lauf()` je ausgeführter Kennung einen
    Eintrag `migration_ausgefuehrt` in den Reiter Verwaltung (E-P5c-38) —
    für `update.php` wie für die Seite. Offen ist damit nur noch die Frage,
    die R66 schon beantwortet hatte: **Entfällt die Karte jetzt, oder bleibt
    sie**, weil sie als Einzige die Fassung („Web") je Kennung nennt? Die
    Dauer eines Laufs steht an keiner der beiden Stellen. *Zuordnung:* die
    Entscheidung der Betreiberin im Abschluss von P5c.

    **Erledigt 25.09.2026 (P5c/AP11, Q-P5c-53): Die Karte bleibt.** Sie
    ist die einzige Stelle, die zu jeder Kennung die Fassung („Web") nennt;
    das Protokoll sagt, wer wann ausgeführt hat. Beides steht in Handbuch
    12.3. Das Versprechen aus R66, die Karte entfalle, ist damit
    zurückgenommen.


328. **Ein Komplett-Backup, über zwei Häppchen versiegelt, lässt sich nicht
    öffnen.** *Aufgenommen und erledigt 25.09.2026 in P5c/AP11 (F-P5c-170),
    gefunden vom letzten Prüfstand des Abschlusses.* `komp_siegel_schub()`
    merkte sich als gültige Länge der Datei, was `ftell()` auf dem Handle im
    Anhängemodus meldete — und das zählt ab null, also ohne Kopf und ohne die
    Blöcke früherer Häppchen. Das nächste Häppchen schnitt die Datei darauf
    zurück, mitten in einen Block; die Datei ging aufs Backup-Ziel und ließ
    sich nie mehr öffnen. Die Komplettprobe sah es nicht, weil die Laufzeit
    entschied, ob die Häppchengrenze ins Siegeln fiel.

    **Erledigt mit Web 21.1.2 (P5c/AP11, 25.09.2026).** Die Länge wird nach
    jedem vollständig geschriebenen Block mitgezählt; ist die Datei kürzer
    als gemerkt oder fort, beginnt die Versiegelung von vorn. Die
    Komplettprobe erzwingt beides in Teil 4 (drei Häppchen zu je einem
    Block, dazu eine verschwundene Zieldatei) — gegen die alte Bibliothek
    2 von 69 offen, gegen die neue 0 von 69. **Offen bleibt nur die Frage
    an die Betreiberin**, ob ein vorhandener Stand auf Produktiv betroffen
    ist (P-P5c-45 im Prüfdokument P5c).

330. **Die Liste der geplanten Stilabweichungen musste nach jedem Merge von
    Hand geleert werden.** *Aufgenommen und erledigt 26.09.2026, nach dem
    Merge von Web 21.1.3 (PR #90), auf Nachfrage der Betreiberin.* `tools/stilvergleich/geplant.txt`
    muss im Pull Request stehen — ohne sie ist der Stilvergleich rot und der
    Bericht ungültig — und ist nach dem Merge auf `main` falsch: Der nächste
    Lauf misst keine ihrer Abweichungen mehr und meldete sie als „geplant,
    aber nicht gemessen", rot. Der Handgriff „nach dem Merge leeren" stand
    deshalb im Rahmenplan 6 und in `Pruefablauf.md` 6.10.

    **Erledigt 26.09.2026** (nur Werkzeug, keine Versionsstufe). `gegen.sh`
    übergibt die Liste des
    Vergleichsstands als `--geerbt`; eine Zeile, die dort wortgleich steht
    und nicht gemessen wird, zählt nicht und steht mit Zahl im Protokoll.
    Alles andere bleibt streng. Gegenproben: nach dem Merge ohne
    `--geerbt` rot (13 nicht gemessen), mit grün (13 geerbt); eigene Zeile
    ohne Messung rot (1); leere Liste gegen `main` rot (13 ungeplant); der
    Pull-Request-Fall grün (13 / 13).

184. **Der Kommentar-Abtaster der Prüfmittel verliert in PHP-Dateien mit HTML die Spur.**
    *Aufgenommen 14.09.2026 in AP2 der Mockup-Runde, als die Symbolprüfung ihn
    benutzen wollte; **erledigt 24.09.2026 mit Konzept BV (BV-01).*** `ohne_php_js_kommentare()` in
    `tools/vollstaendigkeit/pruefen.py` (Backlog-Runde 3, Nr. 47/58) geht
    zeichenweise durch die Datei und merkt sich, ob es gerade in einer
    Zeichenkette steht. In einer **PHP-Datei mit HTML** trifft es dabei auf
    Anführungszeichen im Fließtext, die kein String sind — und ein einzelnes
    ungepaartes `"` schickt es in den Zeichenketten-Modus, aus dem es erst
    beim nächsten herauskommt.
    **Gemessen** an `server/einsatz_form.php`: ab Zeile 1547 verschluckt es
    **rund 800 Zeilen am Stück**; der Kommentar in Zeile 1613 wird nicht mehr
    erkannt. Von 2350 Zeilen werden 853 geleert — der Rest bleibt stehen, ohne
    dass irgendetwas meldet.
    **Die Folge sind falsche NEGATIVE, und die sind teurer als falsche
    positive:** Die drei Zusagen-Prüfungen (`native Dialoge`, `Seite ohne
    Gerüst`, `fremde Quelle`) suchen ihre Muster in genau diesem Text. Was im
    verschluckten Bereich steht, wird nicht gefunden — und die Gruppe meldet
    trotzdem **0**. Genau die Sorte grüner Zahl, gegen die `CLAUDE.md` 6
    warnt.
    *Warum AP2 ihn nicht benutzt hat:* Mit Ausblenden fiele die Symbolzahl von
    252 auf 108. Eine kleinere Zahl, die durch Wegsehen entsteht, ist
    schlechter als eine große, die alles zeigt — deshalb zählt die
    Symbolprüfung weiterhin den ganzen Quelltext.
    **Weg:** Für `.php`-Dateien nur **innerhalb** der Bereiche abtasten, die
    wirklich Code sind — `<?php … ?>`, `<?= … ?>` und `<script> … </script>`;
    alles dazwischen ist HTML, dort gibt es keine Zeichenketten und keine
    `//`-Kommentare. *Abnahme:* An `einsatz_form.php` werden die Kommentare ab
    Zeile 1547 wieder erkannt (geleerte Zeilen deutlich über 853); die drei
    Zusagen-Prüfungen bleiben bei 0 Befunden **und** finden eine testweise
    eingeschleuste `confirm(`-Stelle im bisher verschluckten Bereich.
    Zuordnung: **Backlog-Runde**.

    **Erledigt (BV-01, 24.09.2026).** Der Abtaster liest eine PHP-Datei jetzt
    so, wie PHP und der Browser sie lesen: HTML bleibt unberührt, abgetastet
    werden `<?php … ?>`, `<?= … ?>`, `<script>` und `<style>`, jeder nach den
    Regeln seiner Sprache (ein PHP-Zeilenkommentar endet auch am `?>`, ein
    Skriptblock am `</script`); in JS und CSS enden `'`- und `"`-Ketten am
    Zeilenende. **Die Beschreibung oben war nur zur Hälfte richtig.** Am
    heutigen Stand verschluckte der alte Abtaster keine 800 Zeilen am Stück —
    in `einsatz_form.php` leert der neue 877 statt 851 Zeilen, über alle 179
    Dateien 47 856 statt 47 555, in 21 Dateien anders. Die falschen
    **Negative** kamen aus zwei Quellen, die der Eintrag nicht nannte: `#`
    und `//` im **HTML** galten als Kommentaranfang (`href="#…"`, `http://` im
    Text — 7 Zeilen, alle einzeln gelesen), und ein `"` in einem
    **Regex-Literal** (`/[;"\r\n]/` in `export.js`) brachte ihn in JS aus dem
    Tritt. Gefunden hat das der erste Lauf: Die Zusage „fremde Quelle" meldete
    `export.js` mit der GPX-Namensraumadresse, die bis dahin verschluckt war
    (eingetragen in `vollstaendigkeit-zusagen.md`, 18 → 19 Ausnahmen).
    *Abnahme:* Zusagen 0 Befunde; eingeschleustes
    `<a href="#" onclick="return confirm(…)">` — alt 3, neu 4 von 4 gefunden
    (nicht eingecheckt); derselbe Lauf auf dem P5c-Stand `3576a97` mit
    zusammengeführter Ausnahmeliste 0 Befunde. **Dazu genommen:** Die
    Symbolprüfung blendet Kommentare jetzt aus (Unicode 14 → 5, Emoji 8 → 0;
    alle 17 weggefallenen standen in Kommentaren) — der Grund, es nicht zu
    tun, war dieser Punkt.

274. **Die Vollständigkeitsprüfung kann eine zusammengesetzte Klasse nicht
    sehen — und das trifft jetzt zwei Meldungstöne.** Seit Web 20.34.0 baut
    `EdHtml.meldung()` die Tonklasse zusammen (`'meldung meldung-' + ton`),
    wie es `ui_meldung_markup()` in PHP seit jeher tut. `tools/vollstaendigkeit/`
    meldet `.meldung-ok` und `.meldung-schutz` deshalb als „Regel im
    Stylesheet, im Markup nicht gefunden". Beide Regeln werden benutzt; das
    Werkzeug kann es nur nicht belegen. `meldung-schutz` stand aus demselben
    Grund schon vorher in der Liste. **Kein Befund, aber ein blinder Fleck:**
    Verschwände eine der beiden Regeln aus `style.css`, meldete es niemand.
    Der Kommentar in `ui_meldung_markup()` sagt genau das („das kann nur
    diese Stelle selbst prüfen"). Möglicher Weg: Das Werkzeug liest die
    Tonliste aus der Funktion und trägt die daraus gebildeten Klassen als
    belegt ein.

    **Erledigt 24.09.2026 mit Konzept BV (BV-02).** Die Prüfung liest die
    Töne aus `ui_meldung_markup()` (`$symbole`) und `EdHtml.meldung()`
    (`MELDUNG_SYMBOLE`), zählt `meldung-<ton>` als belegt und meldet
    zweierlei als Befund: eine dieser Klassen ohne Regel im Stylesheet und
    Töne, die nur in einer der beiden Listen stehen. Findet sie eine Liste
    nicht, ist das ebenfalls ein Befund. Hinweis „im Markup nicht gefunden"
    **64 → 62**, auf dem P5c-Stand 74 → 72; 0 Befunde. *Gegenprobe* (Kopie,
    nicht eingecheckt): `.meldung-schutz` umbenannt und ein Ton nur in JS —
    3 neue Befunde, dazu der bestehende am Aufruf.
    **Der blinde Fleck war kleiner als oben beschrieben.** „Verschwände eine
    der beiden Regeln, meldete es niemand" stimmte nicht: Die Tonprüfung am
    Aufruf („Ton ohne Regel im Stylesheet") hätte jede der fünf Regeln
    gemeldet, weil jeder Ton mindestens einmal als Literal übergeben wird —
    gemessen je Ton mit entfernter Regel, alte und neue Fassung gleich
    viele Treffer an den Aufrufen. Für `schutz` hing das allerdings an
    **einem einzigen** Aufruf (`tag_spuren.php`). Seit BV-02 hängt es an
    keinem.

40. **Altklassen ohne Gegenstück — 53 (gemessen 13.09.2026), 55 bei Aufnahme.**
    *Aufgenommen in P3/O11, war für O12 vorgesehen, in O12 bewusst
    zurückgestellt.* Die Vollständigkeitsprüfung verlangt für jede der 220
    Klassen des alten Stylesheets entweder eine Regel im neuen oder einen
    Eintrag auf der Streichliste. O11 hat 22 Einträge nachgetragen (die Zahl
    fiel von 78 auf 55, seither auf 53); die übrigen stammen aus O1 bis O10 und sind dort mit
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

    **Erledigt 24.09.2026 mit Konzept BV (BV-03).** Die Zahl oben war
    überholt: Seit PK-04/1b (22.09.2026) stand jede Altklasse auf der
    Streichliste, 25 davon aber nur mit dem Grund „Ersatzlos entfallen.
    Gemessen am 22.09.2026: Die Klasse steht in keiner … Datei mehr" —
    genau die Zeile, vor der dieser Eintrag warnt. BV-03 hat für alle 25 in
    der vollständigen Geschichte den Commit gesucht, in dem die letzte
    Verwendung verschwand (dafür musste der Klon erst vertieft werden: 381 →
    1 040 Commits), und den Ersatz am Diff gelesen: **25 von 25
    rekonstruiert, keine „Herkunft nicht mehr feststellbar"** — O1 4, O2 2,
    O4 1, O7 2, O8a 1, O9a 1, O9b 2, O9c 12. Die Vollständigkeitsprüfung
    bleibt bei 0 Befunden.
    **Gegengeprüft am 25.09.2026 (P-BV-02), von einer Instanz, die die
    Zeilen nicht geschrieben hat: 12 trugen, 12 nur teilweise, 1 nicht.**
    Die Commits und Pakete stimmten in allen 25; ungenau war der **Ersatz** —
    Stellenzahlen (`btn-yellow` sechs, nicht vier), Bausteine, die es an der
    Stelle nicht gab (`ui_feld()`, `stammdaten_ui.php` bei `neu-*`), Farben
    (`pwquality`: drei, kein Gelb) und ein Paket (`map`: O2, nicht O1). Alle
    15 Zeilen sind nach den belegten Angaben berichtigt, die zehn übrigen als
    bestätigt vermerkt. **Hier stand bis dahin, vier `c-dc-*`-Klassen seien
    nicht ersatzlos, weil `mission_fields_lib.php` sie weiter erzeuge — das war
    falsch:** `c-dc-false_alarm` hat seit Web 6.3.0 keine Spalte mehr, und die
    drei übrigen erreichen seit Schritt 15 AP9b kein Element, weil
    `missiontable.js` diese Spalten selbst führt. PK-04/1b lag mit „ersatzlos"
    richtig. Nebenfund: Nr. 333 (bis zum 26.09.2026 als 330 geführt).

214. **`install.php` wurde bei jedem Lauf wieder ausgeliefert.**
    *Angewiesen von der Betreiberin am 16.09.2026, umgesetzt am selben Tag in
    Web 20.15.2.*
    `docs/Technik.md` sagt zur Neuinstallation seit jeher: „Nach Erfolg sperrt
    `install.lock`; `install.php` danach löschen." Die Auslieferungskette hat
    das bei jedem Lauf rückgängig gemacht — die Datei stand nicht in der
    Ausnahmeliste (dort steht `install.lock`, nicht `install.php`) und wurde
    mitgeschickt. Wer sie von Hand entfernte, fand sie nach dem nächsten Lauf
    wieder vor.

    **Es war nie eine Lücke.** `install.php:136` verweigert sich selbst,
    solange `config.php` **oder** `install.lock` existiert. Der Punkt ist, dass
    zwei Anweisungen desselben Projekts einander widersprachen.

    **Der Preis, und er bleibt bestehen:** Eine leere Anlage lässt sich nicht
    mehr allein über die Kette einrichten — `server/install.php` muss einmal
    von Hand hinauf, dann einrichten, dann wieder löschen. Ein Fehler **im**
    Einrichter erreicht über die Kette ebenfalls keinen Server mehr. Stufe 2
    fängt den Fall ab und nennt ihn beim Namen, damit niemand den
    `FTP_ZIELPFAD` verdächtigt.

    **Wie es aufgefallen ist, und was daran lehrreich bleibt.** Gemeldet wurde
    zuerst „install.php wird nicht gesynct". Die Ursache war eine andere und
    liegt weiter offen: Die Aktion vergleicht die lokalen Dateien gegen ihre
    **State-Datei**, nie gegen den Server (`deploy.ts:84`, `:150`, `:155`).
    Wer auf dem Server von Hand löscht, bekommt die Datei **nie** zurück —
    gemessen an der Live-State-Datei von Staging, die `install.php` mit Hash
    führte, während der Lauf 14 Sekunden später belegte, dass sie dort fehlte.
    Für `install.php` ist das jetzt gegenstandslos, weil sie ausgenommen ist.
    **Für jede andere Datei gilt es weiter** — siehe den nächsten Absatz.

    *Offen daraus:* Ein Hinweis im Runbook, dass eine von Hand auf dem Server
    gelöschte Datei nur zurückkommt, wenn man die State-Datei mitlöscht. Noch
    nicht geschrieben.

    **Erledigt 24.09.2026 mit Konzept BV (BV-04):** Der Hinweis steht in
    `Technik.md` 6.5b, am Ende — nicht in Abschnitt 7, weil er zur
    Zustandsdatei gehört. Er nennt drei Wege, den schonenden zuerst: die eine
    Datei von Hand per FTPS hinauflegen; die Datei im Repositorium ändern;
    die Zustandsdatei löschen — dann überträgt der nächste Lauf alles (688
    Dateien in rund acht Minuten, gemessen 20.09.2026), und zwar bei
    eingeschalteter Wartung.

222. **Der Aufbau des Uhr-Prüfstands wird bei jedem Lauf neu geholt.**
    *Aufgenommen 17.09.2026 bei der Selektion der Stufe-1-Schritte.*
    Der Schritt „Uhr Stufe I" brauchte im gemessenen Lauf **34 min 46 s** und
    ist damit mit Abstand der teuerste der ganzen Kette — der Android-Bau
    daneben 7:15, die übrigen dreizehn Schritte zusammen 23 Sekunden. Seit
    derselben Fassung läuft er nur noch, wenn `watch/` oder
    `tools/uhr-pruefstand/` berührt ist. Wer an der Uhr arbeitet, wartet
    allerdings weiterhin jedes Mal die volle Zeit ab.

    **Vermutung, ausdrücklich keine Messung:** Der größere Teil davon dürfte
    der Aufbau sein — `pruefstand.sh aufbau-uebersetzen` holt SDK und
    Gerätedateien bei jedem Lauf neu —, nicht das Übersetzen selbst. Träfe
    das zu, spräche ein `actions/cache` auf `~/.Garmin/ConnectIQ` den größten
    Teil der Zeit an, und zwar auch dann, wenn tatsächlich an der Uhr
    gearbeitet wird.

    *Zu tun, in dieser Reihenfolge:* zuerst die Verteilung zwischen Holen und
    Übersetzen im Protokoll eines Laufs **nachmessen** — ohne diese Zahl ist
    alles Weitere Spekulation, und genau davor warnt die Hausregel; erst wenn
    der Aufbau überwiegt, einen Cache-Schritt einziehen, dessen Schlüssel an
    der SDK-Fassung und an `CIQ_ZIELE` hängt. **Kein eigenes Paket** —
    Beifang, sobald jemand den Prüfstand ohnehin anfasst (R83-Muster).
    **Erledigt 24.09.2026 mit Konzept BV (BV-05) — durch Wegfall, nicht durch
    einen Cache.** Seit PK-05/3 (`17fdd32`) baut die Kette die Uhr gar nicht
    mehr: `pruefung.yml` sagt im Kopf „Android und Uhr baut Station B", und in
    `.github/workflows/` steht kein Aufruf des Uhr-Prüfstands, kein ConnectIQ
    und kein `actions/cache` (0 Treffer, nachgesehen 24.09.2026). Die Stufe I
    läuft örtlich im Prüfstand (`uhr-stufe1`), dort auf einer einmal
    aufgebauten Anlage — gemessen rund 10 min für 99 Geräte (Nr. 316). Die
    Frage, ob Holen oder Übersetzen die Zeit frisst, hat damit keinen Ort
    mehr.

270. **`assets/import_ui.js` — eine 500 mit wohlgeformtem JSON gilt als
    Erfolg.** Gefunden bei derselben Vermessung (bei Aufnahme Zeile 258).
    Der Bestandsabgleich vor dem Import prüft nur `d.error`, nicht `res.ok`.
    Antwortet der Server mit Status 500 und einem JSON-Rumpf ohne
    `error`-Schlüssel, läuft der Dublettenabgleich **wortlos** gegen einen
    leeren Bestand weiter — und meldet keine einzige Dublette, obwohl der
    Bestand voll ist. Der Fehler ist still: Die Vorschau sieht richtig aus.
    **Nicht in Schritt 15 behoben.** Von den 15 JSON-Sendestellen prüfen
    **acht** `res.ok` nicht; diese hier ist die mit der schlimmsten Folge,
    weil ihr Ergebnis eine Entscheidung der Person trägt.
    **Erledigt 24.09.2026 mit Konzept BV (BV-05) — behoben war es schon mit
    Web 20.34.0** (`0bee2fb`, Schritt 15 AP8b bis AP8f): `bestandPruefen()`
    sendet über `EdApi.postJson()`, und dessen `ok` ist
    `antwort.ok && daten !== null && daten.error == null && daten.ok !== false`.
    Eine 500 mit wohlgeformtem JSON ergibt `ok = false`, und der Import meldet
    „… Dubletten werden deshalb nicht erkannt." — sichtbar, nicht still. Der
    Eintrag ist damals nicht nachgezogen worden.

281. **Stufe 1 lief bei jedem Push auf einem Arbeitszweig doppelt.**
    *Aufgenommen 21.09.2026 (PK-01), erledigt mit dem Vorgriff auf PK-05.*
    `pruefung.yml` trug `push: branches: ['**']` **und** `pull_request`;
    jeder Push auf einen Arbeitszweig erzeugte zwei Läufe desselben Namens
    `Stufe 1` — einen über `pull_request` (rund 1 min, vergleicht gegen den
    gemeinsamen Vorfahren) und einen über `push` (rund 56 min, ohne
    Vergleichsstand misst er alles). Der Zweigschutz wartet auf den Namen,
    also auf den langsameren. Gemessen an PR #69 (Läufe 192, 193) und
    PR #70 (Lauf 186).

    **Erledigt** mit PR #71 (21.09.2026): `branches: [ main ]`. Die Abnahme
    — nur noch **ein** Lauf je Arbeitszweig-Push — steht als **P-PK-17**
    offen und gehört ins Prüfdokument, nicht hierher.

    *Diese Nummer war zweimal in Bewegung: zuerst die 268 (Kollision mit
    PR #72), dann die 269 (Kollision mit Schritt 15, PR #74). Beide Male
    hatte ein paralleler Zweig die Nummer in `Backlog.md` stehen, während
    sie hier nur im Konzept stand. Der Satz „Jeder weitere Zweig, der
    Nummern vergibt, beginnt bei 267" beschreibt keinen Riegel, sondern eine
    Hoffnung.*
    **Ausgetragen 24.09.2026 mit Konzept BV (BV-05).** Die Abnahme P-PK-17 ist
    inzwischen belegt: Für den Zweig `claude/br-bestandsriegel` gab es drei
    Läufe von `pruefung.yml` zu drei Köpfen, alle mit dem Ereignis
    `pull_request`, keinen über `push` (Läufe 35983100731, 36037964804,
    36040164523). Gegenprobe am Zweig von Konzept BV: vier Pushes ohne Pull
    Request, **null** Läufe von `pruefung.yml` (beide über die
    Schnittstelle abgefragt am 24.09.2026). **P-PK-17 ist am 25.09.2026 im
    Prüfdokument PK als belegt eingetragen** (5.6): Der Bedienweg im Wortlaut
    — Push bei offenem PR — ist Lauf 281 (`7f4106a`, PR #85).

212. **Zwei Erwartungen der Wiederherstellungsprobe sind auf einer leeren
    Installation rot — ohne dass etwas kaputt ist.** *Aufgenommen 16.09.2026
    in P5a/AP10, nachgemessen gegen den unveränderten Stand: dieselben zwei.*
    Teil 10 („Der Auftrag Alle sichern") gibt dem Sammelvorgang ein enges
    Zeitbudget und erwartet, dass er **wenigstens ein Konto sichert und dann
    aufhört** — also dass danach etwas offen bleibt und der Zeiger auf dem
    zuletzt gesicherten Konto steht. Auf einer Installation mit zwei fast
    leeren Konten passen beide in das Budget: `2 erledigt, 0 von 2 offen`,
    `cur=—`.

    **Das ist ein Mangel des Prüfmittels, nicht der Anwendung** — und der
    unangenehmere von beiden Sorten: Er meldet Rot, wo nichts ist, und
    gewöhnt damit jeden, der die Probe fährt, an zwei rote Zeilen. Genau so
    verschwindet später ein echter Befund darin.

    **Zu tun:** Die Erwartung an einen Bestand binden, statt an eine Zeit —
    etwa, indem der Prüffall zwei Konten mit genug Inhalt herstellt, oder
    indem das Budget aus der gemessenen Dauer des ersten Backups abgeleitet
    wird statt fest zu stehen. Ein drittes Konto anzulegen wäre die billigste
    Fassung und verschöbe das Problem nur auf die nächste schnellere Maschine.
    *Abnahme:* `php tools/wiederherstellungs-probe/probe.php` meldet **110 von
    110** auf einer frisch aufgesetzten Installation. Zuordnung: Backlog-Runde.
    **Erledigt 24.09.2026 mit Konzept BV (BV-05) — behoben war es mit RP-03**
    (23.09.2026): Teil 10 legt zwei Zusatzkonten an und fährt eine gestellte
    Uhr, die Erwartung hängt damit am Bestand, nicht an der Geschwindigkeit
    der Maschine. Die Sorge oben, ein drittes Konto verschiebe das Problem
    nur auf die nächste schnellere Maschine, trifft deshalb nicht. Nachgemessen
    auf frisch eingerichteter Anlage (`hochfahren.sh --neu`): **111
    Erwartungen, 0 nicht erfüllt**, Teil 10 „2 erledigt, 2 von 4 offen". Der
    Kopfkommentar der Probe, der noch „110 von 110" und „zwei davon rot"
    sagte, ist berichtigt.

194. **Das Handbuch nennt den Verschlüsselungsumfang dreimal ohne die
    Notizen — einmal davon als Textbaustein für die Datenschutzerklärung.**
    *Aufgenommen 14.09.2026 als Nebenfund der Bestandsaufnahme zu R42.*
    Seit **Web 19.0.0** (S9/AP7) sind die **Notizen des Einsatzes**
    Ende-zu-Ende-verschlüsselt; `mission_fields.php` führt sie mit
    `'store' => 'pat'`, `docs/Technik.md` 4.98 und `CLAUDE.md` 4 nennen sie
    im Katalog. Das Handbuch weiß es in **Abschnitt 4.3**, und dort gleich
    viermal: in der Kartenliste („Notizen — seit Web 19 **verschlüsselt** wie
    die Patientendaten"), im Absatz „Zwei Zeichen sagen dir, wer mitliest",
    im Absatz zum Schloss am Kartentitel und im Merkkasten zu den beiden
    Notizfeldern. An **drei** anderen Stellen weiß es das Gegenteil oder
    nichts:

    - **Der Einstieg** sagt das Gegenteil: „Notizen und Freitextfelder sind
      davon **nicht** erfasst — dort gehören keine Patientendaten hinein."
      Das ist für die Notizen des **Einsatzes** schlicht falsch; wahr ist es
      nur noch für die des **Diensttags**.
    - **Kapitel 5** („Verschlüsselung der Patientendaten (Pflicht)") zählt
      die Felder auf und lässt die Notizen aus.
    - **Der Textbaustein zum Übernehmen** (Abschnitt 11.5, Backlog Nr. 138)
      tut dasselbe — und der ist keine Beschreibung, sondern ein Absatz, der
      **in eine Rechtserklärung kopiert werden soll.**

    **Das ist die schwerere Hälfte.** Der Baustein untertreibt den Schutz,
    nennt also nicht zu viel, sondern zu wenig — die Erklärung wäre nicht
    falsch zugunsten des Betreibers, sondern veraltet. Unangenehm ist etwas
    anderes: `CLAUDE.md` 4 verlangt, dass wer die Zusage zitiert, sie
    **vollständig oder gar nicht** zitiert. Drei Handbuchstellen zitieren sie
    unvollständig, und eine widerspricht der vierten offen.

    *Abnahme:* `CLAUDE.md` 4, `docs/Technik.md` 4.98, die vier Passagen in
    Abschnitt 4.3 und die drei berichtigten Stellen nennen **dieselben**
    Felder; der Satz „Notizen und Freitextfelder sind davon nicht erfasst"
    ist auf die Notizen des Diensttags eingegrenzt. Zuordnung: **vor 1.0** — es ist ein Rechtstext,
    kein Feinschliff; spätestens mit der Doku-Neufassung in P7 (R72).
    **Zum Teil erledigt 24.09.2026 mit Konzept BV (BV-04).** Der Einstieg
    und beide Stellen in Kapitel 5 nennen jetzt dieselben Felder wie
    `CLAUDE.md` 4 und `Technik.md` 4.98 — die Notizen des Einsatzes
    verschlüsselt, die des Diensttags im Klartext; die Klartextliste in
    Kapitel 5 ist um die Notizen des Diensttags, die Höhe des Einsatzorts
    und die Koordinate des Transportziels ergänzt. Mitgezogen: `README.md`
    (Einleitung und Kasten) und die Zeile `missions` im Datenmodell von
    `Technik.md`. **Offen bleibt der Textbaustein in 11.5** — den überarbeitet
    P5c/AP9 mit allen Texten in Verwaltung und Betrieb, und ein Absatz, der
    in eine Rechtserklärung kopiert wird, bekommt nicht zwei Hände
    gleichzeitig (Konzept BV, E-BV-06). Nach dem Merge von P5c dort
    nachziehen, dann ist der Punkt erledigt.
    **Erweitert am 25.09.2026 (Konzept BV, BV-06, nach der Gegenprüfung
    P-BV-03).** Der **manuelle Abfahrtort** (`start`) liegt seit Web 6.2.0 im
    verschlüsselten Block und fehlte in `CLAUDE.md` 4 und in der Tabelle von
    `Technik.md` 4.98 — nachgetragen auf Weisung der Betreiberin, dazu in allen
    oben genannten Aufzählungen. Zwölf weitere Stellen, die den Umfang ohne die
    Notizen des Einsatzes nannten oder als vollständige Liste lasen, sind
    nachgezogen (Technik: Schlüsselliste, Suche, Import, Wiederherstellung,
    Endpunkt; Handbuch: Einsatzansicht, Suche, Entsperren, Import; README,
    `Lizenzen.md`, `Was-ist-NAdoku.md` mit der Höhe des Einsatzorts). **Offen
    bleiben** außer 11.5 noch Handbuch Abschnitt 3 (die Zeile „den Schlüssel
    ab, mit dem Diagnose, Alter und Einsatzort verschlüsselt werden" — sie
    liegt direkt neben einer Stelle, die P5c ändert) und **zwei Rechtstexte,
    die der Betreiberin gehören:** `AVV.md` nennt in der Klartextliste die
    Höhe des Einsatzorts nicht (Anlage II Nr. 2 führt sie), und
    `Nutzungsbedingungen.md` 2.6 zählt die freiwilligen Angaben ohne die
    Notizen des Einsatzes und ohne den Abfahrtort auf.
    **Stand nach dem Merge von P5c (26.09.2026, Konzept BV, F-BV-15):**
    P5c/AP9 hat den Textbaustein nach **11.5a** verschoben und die Notizen
    des Einsatzes dort schon ergänzt. Es fehlt noch der manuelle
    Abfahrtort. Handbuch Abschnitt 3 ist unverändert. Ob beides in BV oder
    in Schritt 17 kommt, fragt Q-BV-04.
    **Erledigt 26.09.2026 mit Konzept BV (BV-07, Q-BV-04, E-BV-18).** Der
    Textbaustein in 11.5a nennt den Abfahrtort (Adresse und Koordinate),
    seine Klartextliste die Höhe des Einsatzorts und das Transportziel samt
    Koordinate — dieselben Felder wie `CLAUDE.md` 4 und `Technik.md` 4.98.
    Abschnitt 3 zählt nicht mehr auf, sondern verweist auf Kapitel 5. Die
    Abnahme oben ist damit erfüllt. **Nicht darin:** die zwei Rechtstexte
    (`AVV.md`, `Nutzungsbedingungen.md` 2.6) — sie kamen erst mit der
    Gegenprüfung P-BV-03 dazu, gehören der Betreiberin und stehen als
    P-BV-07 im Prüfdokument BV, das bis zum Abhaken bleibt. Und der Satz
    „der Schlüssel wird aus dem Passwort des Kontos abgeleitet" im Baustein
    ist seit S10 unvollständig (der Server-Anteil fehlt) — eine Frage an
    die Betreiberin, weil der Text in eine Rechtserklärung geht, nicht Teil
    dieses Punkts.

65. **Vierzehn Fassungshinweise im Android-Baulauf hängen an einer
    Entscheidung.**
    *Aufgenommen 02.09.2026 als Rest aus B-S4-04 (S4/D1).*
    `lintDebug` meldete für `android/handy/` 14 Warnungen (Stand Android
    0.11.x; die Zahl ist am 13.09.2026 vier Android-Stufen alt und ohne SDK
    nicht nachzählbar — gemessen sind AGP 8.13.2, Kotlin 2.1.21, Compose-BOM
    2025.06.01, wear-compose 1.4.1, `abortOnError = true`, nichts
    stummgeschaltet), und sie sind nicht vierzehn Entscheidungen, sondern
    **eine**: `androidx.wear.compose` 1.6.2
    und die Compose-BOM 2026.08.00 verlangen einen neueren Compose-Compiler;
    der hängt an Kotlin, Kotlin 2.4 an AGP 9. Dieselbe Kette ziehen
    `core-ktx`, `lifecycle`, `activity-compose` und die vier
    `camera`-Bausteine.
    **AGP 9 ist ein Umbau der Bau-Sprache** und gehört in eine eigene,
    absichtliche Runde — nicht in eine Korrekturfassung. Stummgeschaltet wird
    nichts (CLAUDE.md 6): Die 14 stehen und werden gezählt; sie sind das
    Preisschild an einer aufgeschobenen Entscheidung, und genau das sollen sie
    sein.

    **Erledigt 24.09.2026 mit Konzept AR (Android 0.16.0).** Gradle 9.7.1,
    AGP 9.4.1 mit eingebautem Kotlin, Kotlin 2.4.20; `compileSdk` und
    `targetSdk` 37 (E-AR-08), Compose-BOM 2026.09.00, `wear-compose` 1.7.0,
    `core-ktx` 1.19.1, `lifecycle` 2.11.0, `activity-compose` 1.13.0.
    **Zwei Angaben dieses Eintrags waren überholt:** `camera` gehörte seit
    R63 (Android 0.11.0) nicht mehr zur Kette, und die Kette verlangte
    inzwischen auch `compileSdk` 37 (`minCompileSdk=37` in den
    AAR-Metadaten). Gezählt am unveränderten Stand: 14 Warnungen, davon
    zehn Fassungshinweise; danach **0 Lint-Warnungen in beiden Modulen**,
    nichts stummgeschaltet (auch die drei Plurale und eine unbenutzte
    Zeichenkette sind behoben). Nebenbei gefunden: ein Prüffall, der seinen
    Rückstand nie übergab (Kotlin 2.4), und zwei Kontraste unter AA
    (Nr. 116). 73 von 78 Bildern des Bilderlaufs byteweise gleich, die
    übrigen fünf nur in der Fassungszeile.

284. **Eine ausgelieferte Android-Zeile ohne Versionsstufe, ohne
    Changelog-Zeile und ohne Emulatorlauf.**
    *Aufgenommen 23.09.2026 auf Nachfrage des Auftraggebers zu PK-04/5b;
    die Zeile bleibt stehen, die drei Pflichten werden nachgezogen (E-PK-40).*
    `android/handy/src/main/res/values/strings.xml` trägt seit PK-04/5b
    `recht_hinweis` in der Hausform („von der BetreiberIn des Servers"), und
    das ist **ausgelieferter Code des Handy-Moduls**. Damit greift
    `CLAUDE.md` 2 — „drei Zählungen, drei Auslieferungen" —, und drei Dinge
    sind unterblieben:

    - **`android/version.properties` steht unverändert auf `0.15.1`.**
      Fällig wäre 0.15.2 gewesen; den Präzedenzfall führt die Datei selbst
      mit 0.14.1 („fünf Sätze, keine Funktion").
    - **Der Changelog hat keine Zeile mit dem Präfix `Android`**, nur
      `[Web 20.37.1]`. Die Regel steht im Kopf von `version.properties`.
    - **Der Emulator ist nicht gelaufen.** `docs/Pruefablauf.md` 6.9
      verlangt ihn bei **jeder** Änderung an einem der beiden Module,
      angesehen, bedient und mit Bildern belegt. Pflicht ist dort der
      Versuch, nicht der Erfolg — auch ein gescheiterter Start wäre ein
      Befund mit Zahl gewesen.

    **Wie es passiert ist:** Die Fächerung von 5b lief über
    Wort-Eimer, nicht über Auslieferungsbereiche. Ein Agent hat die Datei
    als eine von 434 Fundstellen behandelt, und keine Stufe danach hat
    gefragt, welchem der drei Auslieferungsstränge sie angehört. Die
    Textprobe misst Bereich `d` (Android-Strings) mit und ist grün — sie
    prüft das Wort, nicht die Versionspflicht.

    **Der Preis, solange es offen ist:** Zwei verschiedene Stände des
    Handy-Moduls tragen dieselbe Nummer `0.15.1` — genau das, was
    `version.properties` „zwei Wahrheiten über denselben Stand" nennt. Ein
    APK aus diesem Stand ist von einem APK aus dem vorherigen nicht an der
    Fassung zu unterscheiden.

    **Nachzuziehen beim nächsten Android-Paket**, zusammen: Nummer,
    Kopfabsatz, Changelog-Zeile und ein Emulatorlauf, der die
    Rechtstexte-Seite zeigt. Abnahme als **P-PK-28**.

    **Erledigt 24.09.2026 mit Konzept AR (E-AR-04):** Die Zeile trägt
    Android **0.16.0**, zusammen mit Nr. 65 — keine eigene Korrekturnummer.
    Kopfabsatz in `version.properties`, Changelog `[Android 0.16.0]`,
    Emulatorlauf mit Bild der Seite Einstellungen → Rechtliches: siehe
    `docs/konzepte/Pruefdokument-AR-Android-Runde.md` (P-PK-28). Der Eintrag
    P-PK-28 in `Pruefdokument-PK-Pruefkette.md` ist dort noch abzuhaken
    (E-AR-02: AR fasst das PK-Dokument nicht an).

337. **Emulator 37.1.11 und die Abbilder mit API 37: SurfaceFlinger bricht ab.**
    *Aufgenommen 24.09.2026 mit Konzept AR (AR-05, F-AR-14).* Auf
    `system-images;android-37.0;google_apis;x86_64` (ohne KVM, `-accel off
    -gpu swiftshader_indirect`) bricht SurfaceFlinger im Faden
    `RegionSampling` ab — `Assertion failed:
    !rcEnc->featureInfo()->hasReadColorBufferDma` in `mapper.ranchu.so` —,
    und die Oberfläche startet neu: drei Abstürze in 13 Minuten nach dem
    Boot. `screencap` scheitert an derselben Stelle (72 Bytes Fehlertext
    statt PNG). *Umgangen* in `android/werkzeuge/emulator.sh`: Drei-Tasten-
    statt Gestennavigation (danach über neun Minuten kein Absturz mehr) und
    Abzug von der Wirtsseite (`adb emu screenrecord screenshot`). Das ist ein
    Fehler des Emulators, nicht der App. **Nachtrag 25.09.2026 (F-AR-18):**
    Auf dem Wear-Abbild mit API 37 kam derselbe Abbruch aus `system_server`,
    und die Ursache war nicht der Emulator, sondern die AVD — `avdmanager`
    aus `cmdline-tools` 12.0 schreibt `target=android-0`; mit
    `target=android-37.0` blieb er aus. **Vermutlich gilt das auch hier;
    nicht nachgemessen.** Emulator 37.3.1 änderte am Abbruch nichts.
    *Weg:* zuerst die AVD mit richtigem `target` nachmessen, dann mit einer neueren Fassung des
    Emulators nachmessen und die Umgehung austragen, sobald sie nicht mehr
    nötig ist. *Abnahme:* ein Boot auf API 37 mit Gestennavigation, 15
    Minuten ohne Eintrag im Absturzpuffer. ~~**Zuordnung: nächste
    Android-Runde, sobald `sdkmanager` einen neueren Emulator führt.**~~
    **Erledigt 25.09.2026 mit Konzept AR (Nachtrag AR-05).** Ursache war
    `target=android-0`, nicht der Emulator: `handy37`, angelegt mit
    `cmdline-tools` 23.0 (`target=android-37.0`), nach dem Boot auf
    Gestennavigation gestellt — **15 Minuten, 0 Einträge im
    Absturzpuffer**, SurfaceFlinger 44 Minuten ohne Neustart, `screencap`
    liefert PNG. Abweichung von der Abnahme: gebootet mit Drei-Tasten, erst
    danach auf Gesten umgestellt (die Umgehung stand beim Boot noch im
    Werkzeug). Beide Umgehungen sind aus `emulator.sh` ausgetragen; `bild`
    meldet ein fehlendes PNG seither als Fehler, statt auszuweichen.

177. **Der Änderungsverlauf des Rahmenplans führt sechs Fassungsnummern doppelt.** · gehört zu: SD · Stand: erledigt · seit 13.09.2026
     Befund (Backlog-Runde 3, AP10): In Abschnitt 10 stehen die Nummern 35,
     36, 37, 39, 38, 37 zwischen „30" und „47" ein zweites Mal mit anderem
     Inhalt — mehrere Sitzungen schrieben am selben Tag. Ein Verweis auf
     „Fassung 38" ist nicht auflösbar; zwei Konzeptdokumente zitieren
     betroffene Nummern (Sofortpaket „Fassung 36", S9 „Fassung 38").
     Zurückgestellt 13.09.2026 auf Anweisung („nur historisch"): keine
     Wirkung auf Code, Daten oder Oberfläche.
     Erledigt sich mit Konzept SD (Sammelnummer 294): Der alte Verlauf liegt
     eingefroren in `Rahmenplan-Archiv-2.md`, der neue
     (`Rahmenplan-Verlauf.md`) beginnt bei Fassung 125 mit eindeutigen
     Nummern; die Doppelungen werden nicht umnummeriert. Abnahme: jede
     Fassung im neuen Verlauf genau einmal.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 177.
     Erledigt 26.09.2026 mit Konzept SD (PR #92, `056781c`): Der neue
     Verlauf `Rahmenplan-Verlauf.md` beginnt bei Fassung 125 mit
     eindeutigen Nummern; der alte liegt eingefroren in `Rahmenplan-
     Archiv-2.md` 10.

193. **Register und Doku führen die R42-Auswertung als offen, obwohl sie seit Web 15.3.0 läuft.** · gehört zu: SD · Stand: erledigt · seit 14.09.2026
     Befund (Bestandsaufnahme zu R42, 14.09.2026): R42 verlangt eine
     Geräteverteilung je Kategorie und Bezeichnung ohne `manual-%` und ohne
     Demo-Konto; sie steht seit S8/AP4 in `betrieb_statistik.php`. Fünf
     Stellen sagten das Gegenteil: die Registerzeilen R42 und R64 des
     Rahmenplans, `Technik.md` („Die Auswertung ist P5"), `Handbuch.md` 10
     („Bevor eine Auswertung entsteht, wird sie in der Datenschutzerklärung
     benannt") und die Kopfzeile von Nr. 80. Der Handbuchsatz ist eine
     Zusage an die NutzerIn und steht zwei Kapitel vor 12.2, das die
     Statistik mitsamt Gerätemodell-Tabelle beschreibt. Für die
     Momentaufnahme am Einsatz (`missions.geraet_art`) bleibt er wahr; die
     Datenschutz-Frage steht bei Nr. 80. Zweiter Beleg für Nr. 188.
     Erledigt mit Konzept SD (E-SD-26): R42 und R64 tragen seit Fassung 125
     ihren Statussatz, Nr. 80 trägt seit SD-02 eine Kopfzeile ohne
     „ausgewertet ist nichts". Offen sind die zwei Sätze in `Technik.md`
     und `Handbuch.md` 10 (26.09.2026 nachgemessen: beide stehen noch);
     SD-04 zieht sie nach. Abnahme: Die fünf Stellen sagen dasselbe wie
     die Registerzeile R42.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 193.
     Erledigt 26.09.2026 mit SD-04 (PR #92): Die zwei Sätze in `Technik.md`
     und `Handbuch.md` 10 sind nachgezogen — 0 Treffer für „Die Auswertung
     ist P5" und „Bevor eine Auswertung entsteht" (nachgemessen
     26.09.2026).

196. **68 von 195 Backlog-Einträgen rendern auf GitHub als grauer Kasten.** · gehört zu: SD · Stand: erledigt · seit 15.09.2026
     Befund (15.09.2026, cmark-gfm): Ab Nr. 100 ist der Listenmarker fünf
     Zeichen breit, die Datei rückte mit vier ein — bei dreistelligen Nummern
     endet der Listenpunkt nach dem ersten Absatz, der Rest wird zum
     eingerückten Codeblock. 195 Einträge, 68 als Codeblock, vier davon mit
     Tabelle (123, 187, 192, 193). Der Rahmenplan war schlimmer dran:
     Abschnitt 10 brach als Tabelle nach 8 `<tr>` ab (Leerzeile in der Zeile
     zu Fassung 61), die Fahrplanzeile 9c hatte neun Zellen durch drei
     ungeschützte Pipes in einem Code-Span.
     Weg: ein Leerzeichen mehr bei allen Fortsetzungszeilen, in einem Zug —
     nicht in Teilen.
     Erledigt sich mit Konzept SD (Sammelnummer 294): SD-02 rückt jeden
     Eintrag mit fünf Leerzeichen ein und misst mit cmark-gfm (`<pre>` 0,
     `<li>` = Einträge); der alte Rahmenplan liegt eingefroren im Archiv-2,
     der neue Verlauf ist eine Tabelle ohne Leerzeilen; SD-03 hängt die
     Probe an Stufe 1. Abnahme: 0 Einträge mit `<pre>`, Tabellen als
     `<table>`, jede Verlaufszeile ein `<tr>`.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 196.
     Erledigt 26.09.2026 mit SD-02 und SD-03 (PR #92): 0 `<pre>`, 105
     `<li>` gegen 105 Einträge (cmark-gfm), als Decke in Stufe 1. Dass
     GitHub die Liste fortzählt (F-SD-08), ist ein eigener Punkt von
     Schritt 17.

199. **Die Nummer 5 fehlt im Backlog, obwohl der Changelog sie unter Erledigt verortet.** · gehört zu: SD · Stand: erledigt · seit 15.09.2026
     Befund (Gegenlesen des Merges von PR #47): `grep -cE '^5\. '` liefert
     0 — weder unter Offen noch unter Erledigt gibt es die Nummer, und die
     Kopfnotiz nennt als frei nur 4, 6 und 7. Der Changelog zu Web 7.2.0
     sagt zweimal das Gegenteil („Das Geräte-Limit (Nr. 5) … steht jetzt
     unter Erledigt"). Ein Verweis auf „Nr. 5" löst ins Leere; dritter
     Beleg für Nr. 188.
     Zwei Wege, nur einer richtig: den Eintrag aus der Historie unter
     Erledigt wiederherstellen, oder die 5 als dauerhaft frei führen und den
     Changelog-Satz als überholt kennzeichnen.
     Erledigt sich mit Konzept SD (Sammelnummer 294): Die Nummer steht in
     keiner Fassung von `docs/Backlog.md` (gemessen 26.09.2026 über die
     ganze Historie ab `7154ec5`) — der Changelog-Satz beschreibt einen
     Stand, der nie im Repositorium lag. E-SD-25: 5 ist dauerhaft frei und
     steht so in der Reservierungstabelle des Kopfes.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 199.
     Erledigt 26.09.2026 mit Konzept SD (E-SD-25, PR #92): 5 ist dauerhaft
     frei und steht so im Kopf von `Backlog.md` und in dieser Datei; der
     Changelog-Satz zu Web 7.2.0 bleibt als überholt stehen.

294. **Steuerungsdokumente schneiden (Konzept SD).** · gehört zu: SD · Stand: erledigt · seit 24.09.2026
     *Aufgenommen 24.09.2026 mit SD-00 (Rahmenplan Fassung 111).* Anlass:
     Rahmenplan und Backlog sind auf 3 700 bzw. 10 000 Zeilen gewachsen,
     weil Steuerung, Register und Protokoll in denselben Dateien stehen und
     jede Fassung Erzähltext nachzieht. Konzept:
     `docs/konzepte/Konzept-SD-Steuerungsdokumente.md` (SD-00 bis SD-04; die
     Schreibregeln aus SD-00 gelten seit Fassung 111). Zeitpunkt: SD-01 bis
     SD-04 nach dem Merge des 10c-PR, auf eigenem Zweig von `main`.
     **Zuordnung: SD.** Mit ihm erledigen sich Nr. 177, 193, 196 und 199.
     *Stand 26.09.2026:* **SD-01 erledigt** — der Rahmenplan ist geschnitten
     (Fassungen 125 bis 127; Fassung 124 wörtlich in
     `docs/Rahmenplan-Archiv-2.md`, Verlauf in `docs/Rahmenplan-Verlauf.md`),
     Zweig `claude/serene-tesla-sqeno2`. **SD-M1 und SD-02 erledigt** (26.09.2026):
     Durchsicht der Betreiberin (E-SD-34 bis E-SD-36), dieser Backlog geschnitten
     (Kopfzeilen, `docs/Backlog-Erledigt.md`). Als Nächstes SD-03, das Werkzeug
     `tools/steuerung/`.
     Erledigt 26.09.2026: SD-01 bis SD-04 gemergt (PR #92, `056781c`),
     Erledigt-Zeile in Rahmenplan 8, Konzept gelöscht; damit sind Nr. 177,
     193, 196 und 199 erledigt.

140. **Push auf `main` ist Deploy — Zugang zum Repositorium ist Zugang zum Schlüssel.** · gehört zu: 17 · Stand: erledigt · seit 06.09.2026
     Befund (Krypto-Review K-16): Die FTPS-Action deployte jeden Push mit
     Zugangsdaten aus Secrets; jedes Konto mit Push-Recht konnte `crypto.js`
     ändern und Passwörter beim nächsten Anmelden abgreifen — der eine
     Angriff, gegen den keine Browser-Verschlüsselung hilft. Seit S10 wiegt
     er schwerer: Wer `server/` beschreiben kann, liefert den Server-Anteil
     aus `config.php` mit aus (R78 (1)).
     Entschieden (R78): Zweigschutz und 2FA sofort als Zuarbeit, das
     Deploy-Tor mit dem Staging-Aufbau (R40 (2), nicht S10), die
     Integritätswache im Sofortpaket.
     Erledigt: die Wache (Web 15.6.0, `tools/integritaetswache/`,
     `integritaet.yml`, täglich und nach jedem Deploy; 112 Dateien, ein
     Inline-Block, ohne eingecheckte Prüfsummen); das Deploy-Tor (Web 20.4.0,
     R67: Push auf `main` geht auf Staging, Produktiv nur über Tag und
     Pflichtfreigabe); der Zweigschutz für `main` (21.09.2026, Rahmenplan
     6b) und der 2FA-Zwang in der GitHub-Organisation (Durchsicht der
     Betreiberin 26.09.2026, E-SD-34).
     Offen ist damit nichts mehr als die Verschiebung nach Erledigt, die
     Konzept SD nicht vornimmt; die Backlog-Runde holt sie nach.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 140.
     Erledigt 26.09.2026 mit R4-01 (Konzept R4, F-R4-07): Wache, Deploy-Tor,
     Zweigschutz und 2FA-Zwang stehen (F-SD-06); offen war nur noch diese
     Verschiebung.

172. **Eine Erwartung der Wartungsprobe flackert.** · gehört zu: 17 · Stand: erledigt · seit 12.09.2026
     Befund (Web 19.1.2): Erwartung 15 („das 503 kommt schneller als die
     Antwort ohne Wartung") vergleicht zwei Einzelmessungen mit `<` ohne
     Spielraum; örtlich liegen beide bei rund 71 ms, und drei Läufe ergaben
     0, 1, 0 nicht erfüllte Erwartungen (71,7 gegen 71,6 ms). Eine Probe,
     die jeden dritten Lauf grundlos rot wird, liest nach dem dritten Mal
     niemand mehr — und sie ist die einzige rote Zahl eines grünen Laufs.
     Weg: mehrfach messen und Mediane vergleichen, oder die Erwartung
     strukturell stellen — `wartung_tor()` steht in `db.php` vor jedem
     Verbindungsaufbau, und das prüft man am Code, nicht mit der Stoppuhr.
     Abnahme: zehn Läufe hintereinander, zehnmal dieselbe Zahl.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 172.
     Erledigt 26.09.2026 mit R4-01 (Konzept R4, F-R4-07): Die Wartungsprobe
     vergleicht seit P5c/AP9 den Median aus fünf Messungen (F-P5c-165,
     `3258916`); die Nummer wurde damals nicht genannt.

216. **Zwei Trennlinien hintereinander an vier Stellen des P5a-Prüfdokuments.** · gehört zu: 17 · Stand: erledigt · seit 16.09.2026
     *Aufgenommen 16.09.2026, gleiche Durchsicht.*
     Rein kosmetisch: `---` gefolgt von `---` erzeugt in manchen
     Markdown-Darstellungen eine doppelte Linie, in anderen eine Überschrift.
     Beim Abhaken der Prüfliste mit wegräumen, nicht dafür eigens anfassen — das
     Dokument verschwindet ohnehin, sobald seine 33 Punkte abgehakt sind.
     Erledigt 26.09.2026 mit R4-01 (Konzept R4, F-R4-07): Das
     P5a-Prüfdokument ist mit `f6cb5fd` gelöscht; in `docs/` stehen 0
     doppelte Trennlinien (nachgemessen am 26.09.2026).

295. **Der Messstand hat keinen Schritt für die Statistik.** · gehört zu: 17 · Stand: erledigt · seit 24.09.2026
     *Aufgenommen 24.09.2026 aus Konzept P5c (F-P5c-40), als Anlass nach der
     Zuarbeit von Konzept BR (E-BR-07).* `tools/messstand/` misst die Seiten,
     die es kennt; die Statistik ist keine davon, und das Konto `messstand@…`
     fehlte auf der örtlichen Anlage (seit AP4 legt der Prüfstand es über
     `messen.py --frisch` selbst an, F-P5c-104). 10c AP7 baut die Statistik auf eine
     Zählung mit Obergrenze und einen neuen Index `missions(started_at)` um —
     ohne Messstand-Schritt gäbe es für die Zeiten keinen Beleg, nur ein
     `EXPLAIN` von Hand. *Weg:* Schritt `statistik` im Messstand (drei Reiter
     und `EXPLAIN`, Sitzung der BetreiberIn; fehlt das Konto, legt AP7 es nach
     E-PK-27 an). **Zuordnung: 10c AP7.**
     Erledigt 26.09.2026 mit R4-01 (Konzept R4, F-R4-07): Der Messstand hat
     seit P5c/AP7 den Schritt `statistik` (`schritt_statistik()` in
     `tools/messstand/messen.py`, `d519fac`); die Nummer wurde damals nicht
     genannt.

301. **`Sandbox-Setup.md` 1 sagt, Firefox und WebKit starten im Container nicht — sie starten.** · gehört zu: 17 · Stand: erledigt · seit 24.09.2026
     Befund (Konzept RW, F-RW-02; Playwright 1.56): Firefox 142 und WebKit
     26 starten, laden eine Seite über `localhost` und rechnen WebCrypto;
     RW-04 hat die Rückwegprobe in beiden gefahren. Die Tabelle „Nicht im
     Abbild" nennt ihre Systembibliotheken als fehlend (Nr. 183); das gilt
     nicht mehr. Dabei gemessen (F-RW-23): Gegen `php -S` mit einem Arbeiter
     blieb WebKit in 2 von 4 Läufen beim zweiten Anmelden 90 s ohne
     Navigation, mit `PHP_CLI_SERVER_WORKERS=4` 3 von 3 grün.
     Erledigt (P5c/AP11, 25.09.2026): `hochfahren.sh` startet den Server
     mit vier Arbeitern (`lokal_starten.sh`, `lokal_einrichten.sh`, auch im
     Behälter für PHP 8.3) — Anlass war die Bedienprobe
     (`net::ERR_TOO_MANY_RETRIES`), nicht WebKit.
     Offen: die Zeile in `Sandbox-Setup.md` 1 berichtigen und datieren,
     Nr. 183 nachsehen; ob der Bilderlauf in `haupt` alle drei Motoren
     fahren kann, gehört zu Nr. 300.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 301.
     Erledigt 26.09.2026 mit R4-01: Die Zeile in `Sandbox-Setup.md` 1 nennt
     jetzt die vier Bibliotheken für WebKit — nachgemessen: ohne sie startet
     Firefox, WebKit nicht; mit ihnen alle drei. Nr. 183 ist erledigt; drei
     Motoren im Bilderlauf der Stufe haupt bleiben bei Nr. 300.

329. **Der Prüfstand fährt die Quelltextprüfungen mit eingerichteter Anlage, Stufe 1 ohne.** · gehört zu: 17 · Stand: erledigt · seit 25.09.2026
     Befund (P5c/AP11, F-P5c-171): Die Ankerprüfung lud `doku_lib.php`, die
     lud `db.php`, das ohne `config.php` abbricht — im Prüfstand grün, im
     ersten Lauf auf dem Pull Request rot, nach sechs Läufen des
     Prüfstands. Zweiter Fall derselben Lücke (F-P5c-172): `anker` stand
     als Riegel in `pruefablauf.json`, aber nicht als `--riegel` im Schritt
     „Prüfbericht gegenlesen" von `pruefung.yml`; `--alle-riegel` war im Tor
     rot, örtlich fährt den Schritt niemand.
     Erledigt: die eine Stelle (`doku_lib.php` braucht `db.php` nicht,
     Web 21.1.2). Offen ist die Lücke: Jedes künftige Quelltextwerkzeug,
     das eine Serverbibliothek mit `db.php` lädt, ist örtlich grün und im
     Tor rot.
     Weg: Der Prüfstand fährt `quelltext pruefen.sh --selbstprobe` und
     `alle` mit beiseitegelegter `config.php` — so, wie das Tor sie sieht —
     und `bericht.py lesen --alle-riegel` mit genau den `--riegel`, die
     `pruefung.yml` übergibt. Abnahme: mit dem Stand vor 21.1.2 zweimal rot
     (`anker`, fehlende Übergabe), mit dem heutigen grün.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 329.
     Erledigt 26.09.2026 mit R4-02 (Konzept R4): Der Bestandsriegel hat die
     Regeln `anlage` (keine Probe ohne Anlage lädt db.php oder config.php)
     und `tor` (jeder Riegel steht als --riegel im Tor); mit dem Stand vor
     Web 21.1.2 beide rot, heute 0; Selbstprobe 155 / 0.

332. **Nach dem Aufnehmen fremder Migrationen misst der Prüfstand gegen das alte Schema.** · gehört zu: 17 · Stand: erledigt · seit 26.09.2026
     Befund (Konzept BV, F-BV-17, beim Aufnehmen von `main` nach P5c):
     `Pruefablauf.md` 5.3 misst nur, was die Arbeit gegen `main` ändert,
     nicht, was `main` mitbringt (F-PK-39); ohne eigene Migration ist die
     Stufe `klein`, und die Anlage wird nur gestartet, nicht nachgezogen.
     Mit P5c kamen Migrationen aus fünf Paketen, die Anlage stand auf dem
     Schema davor; `login.php` antwortete 200 mit „Fassung v21.1.2", der
     Nachweis sah nichts. Gemessen: Rollenprobe rot mit `Data truncated for
     column 'role'` (Rolle `support` fehlte im `ENUM`), nach
     `hochfahren.sh --neu` 20 grün, 0 rot. Diesmal laut — eine Probe, die
     die neuen Spalten nicht berührt, misst still gegen einen Stand, den es
     nirgends gibt, und ein grüner Bericht trägt einen Baum, den die Anlage
     nie hatte.
     Weg: `pruefen.sh` fragt vor den Proben, ob die Anlage offene
     Migrationen hat (dieselbe Frage wie der Torwächter), und ist bei
     Rückstand rot mit dem Weg (`hochfahren.sh --neu`) — oder richtet neu
     ein; dazu ein Satz in `Pruefablauf.md` 5.3, Schritt 2. Abnahme: Anlage
     auf `ba2ec57`, Baum mit `main` von `29cf394` → rot vor der ersten
     Probe, mit Ansage; nach `--neu` grün.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 332.
     Erledigt 26.09.2026 mit R4-02 (Konzept R4): `hochfahren.sh` fragt nach
     dem Start migrationen_lauf() ohne Ausführen und ist bei jeder offenen
     Migration rot, mit Kennung und Weg (--neu oder update.php); gestellt
     mit einer entfernten Registerzeile: rot, rc 1; zurückgesetzt: 0 offen.
     Ein Satz in Pruefablauf.md 5.3, Schritt 2.

335. **Der Prüfstand erkennt die Ausbaustufe `android` an der falschen Plattform.** · gehört zu: 17 · Stand: erledigt · seit 24.09.2026
     *Aufgenommen 24.09.2026 mit Konzept AR (F-AR-07, E-AR-12).*
     `tools/pruefstand/pruefen.sh` prüft vor dem `android-bau`, ob
     `platforms/android-36` liegt. Seit Android 0.16.0 baut die App gegen
     **37.0**; die Zeile ist grün, wenn 36 liegt und 37.0 fehlt, und rot, wenn
     es umgekehrt ist. `tools/sandbox/aufbauen.sh android` installiert
     deshalb beide Plattformen, damit die Erkennung nicht fehlschlägt — das
     ist eine Krücke, keine Messung.
     *Weg:* die Erkennung an die Plattform hängen, die der Bau braucht (am
     besten aus `compileSdk` gelesen statt fest geschrieben), dann Plattform
     36 aus `aufbauen.sh` streichen. *Abnahme:* ohne `android-37.0` meldet
     der Prüfstand „Ausbaustufe android fehlt". **Zuordnung: nach dem Merge
     von P5c**, zusammen mit Nr. 334.
     Erledigt 26.09.2026 mit R4-02 (Konzept R4): `pruefen.sh` liest
     compileSdk aus android/handy/build.gradle.kts; `aufbauen.sh android`
     holt nur noch 37.0. Mit beiseitegelegter 37.0 meldet der Prüfstand
     „Ausbaustufe android fehlt (Plattform android-37 aus compileSdk)".

339. **Eine Nummernspanne, die nur auf dem eigenen Zweig steht, sieht niemand — der Prüfstand soll neue Nummern gegen alle offenen Zweige halten.** · gehört zu: 17 · Stand: erledigt · seit 26.09.2026
     *Aufgenommen 26.09.2026 mit dem Abschluss von Konzept BV (Q-BV-05,
     E-BV-19).* Zweimal an einem Tag ist eine Nummer aus der Spanne von BV
     auf `main` kollidiert (F-BV-14: 329 durch P5c/AP11; F-BV-18: 330 durch
     PR #91), ohne dass jemand eine sichtbare Regel verletzt hätte: Der
     Kopf des Backlogs verlangt, die Spanne vor dem ersten Push einzutragen
     (E-SD-21) — eingetragen wird sie aber auf dem eigenen Zweig, und „auf
     allen offenen Zweigen nachsehen" ist ein Blick, den kein Werkzeug
     abnimmt. Jede Kollision kostet das Umnummerieren samt Verweisen in
     Konzept, Prüfdokument und Werkzeugen, und sie fällt erst beim
     Aufnehmen von `main` auf, also spät.
     *Weg:* Der Prüfstand (`tools/pruefstand/pruefen.sh`) ermittelt die
     Backlog-Nummern, die der Zweig gegen `origin/main` neu anlegt, holt
     `docs/Backlog.md` aller offenen Remote-Zweige (`git fetch`, dann
     `git show <zweig>:docs/Backlog.md`) und ist bei einer Überschneidung
     rot — örtlich, weil Stufe 1 die anderen Zweige nicht sieht.
     *Abnahme:* Eine Nummer, die ein zweiter Zweig schon trägt, färbt den
     örtlichen Lauf rot und nennt Zweig und Nummer; eine Selbstprobe stellt
     die Doppelung nach.
     Erledigt 26.09.2026 mit R4-03 (Konzept R4): tools/steuerung/nummern.py
     hält die neuen Nummern des Arbeitsbaums gegen origin/main und alle
     Remote-Zweige (je gegen den Vorfahren mit main, beide Backlog-Dateien)
     und nennt Zweig und Nummer; Probe nummern im Muster steuerung, nur
     örtlich; Selbstprobe 8 / 0 mit gestellter Doppelung.

322. **Welche Probe der Demo-Reset trifft, entscheidet die Reihenfolge der Muster.** · gehört zu: 17 · Stand: erledigt · seit 24.09.2026
     *Aufgenommen 24.09.2026 in P5c/AP7 (F-P5c-127), gemessen.* Der
     Demo-Bestand wird alle 30 Minuten neu eingespielt, und seine Einsätze
     bekommen neue Kennungen. Ein Prüfstand der Hauptstufe dauert rund 39
     Minuten; der Reset fällt also in jeden Lauf. Wen er trifft, hängt davon
     ab, welche Probe um diese Zeit läuft — und die Reihenfolge der Proben ist
     die ihres ersten Auftretens in den Mustern von `pruefablauf.json`. Ein
     neues Muster weit vorn mit Messstand und Bilderlauf (AP7) schob die
     Bedienprobe (51 / 55) und die GPX-Probe (204 von 204 ohne Gegenstück)
     hinter die Marke; am Ende eingereiht, liefen beide grün. **Die
     Reihenfolge ist damit eine Voraussetzung, die nirgends steht**, und wer
     ein Muster ergänzt, verschiebt sie, ohne es zu merken.

     *Weg:* entweder der Prüfstand hält den Demo-Reset für die Dauer des
     Laufs an (eine Marke, die der Job liest, wie die Sperre des Demo-Resets
     in F-P5c-117), oder `pruefablauf.json` bekommt eine ausdrückliche
     Reihenfolge der demo-empfindlichen Proben (`nach`), und `auswahl.py
     --selbstprobe` prüft sie. *Abnahme:* ein Muster mit Messstand ganz vorn,
     der Prüfstand bleibt grün. **Zuordnung: Backlog-Runde** (Prüfmittel).
     Erledigt 26.09.2026 mit R4-04 (Konzept R4): Proben, die das Demo-Konto
     anmelden oder Demo-Daten über ihre Kennungen lesen, tragen in
     pruefablauf.json "demo": true (sieben, F-R4-24); der Prüfstand schiebt
     vor jeder die Marke des letzten Resets und stellt sie am Ende zurück —
     die Reihenfolge der Muster ist keine Voraussetzung mehr. Abnahmelauf
     Stufe neben: Zahlen im Prüfdokument R4.

334. **Die Prüfwerkzeuge der Android-App hängen an keinem Lauf.** · gehört zu: 17 · Stand: erledigt · seit 24.09.2026
     *Aufgenommen 24.09.2026 mit Konzept AR (F-AR-05, E-AR-12).*
     `android/werkzeuge/kontraste.py`, `farbabgleich.py`, `bildmarken.sh` und
     `stroeme.py` werden von keinem Workflow, keinem Aufruf in
     `tools/pruefstand/pruefen.sh` und keiner Zeile in
     `tools/pruefstand/pruefablauf.json` gerufen — gezählt am 24.09.2026:
     0 Treffer außerhalb von `android/werkzeuge/`. Der Riegel `kontraste` im
     Tor ist das Web-Werkzeug `tools/screenshots/kontrast.py`. Was Nr. 116 an
     `kontraste.py` verbessert hat (Vollständigkeit je Modul und Rolle, mit
     Selbstprobe), läuft also nur, wenn jemand daran denkt.
     *Weg:* die vier unter das Muster `android/**` in `pruefablauf.json`
     hängen (sie brauchen nur Python und die Quellen, `braucht: nichts`),
     `kontraste.py --selbstprobe` dazu. Nicht in AR, weil
     `tools/pruefstand/` während P5c dessen Gebiet ist.
     *Abnahme:* eine Berührung unter `android/` wählt die vier im Prüfstand
     aus, der Bericht nennt ihre Zahlen. **Zuordnung: nach dem Merge von P5c**,
     von der Instanz, die `main` aufnimmt, oder der nächsten Backlog-Runde.
     Erledigt 26.09.2026 mit R4-04 (Konzept R4): android-kontraste (mit
     Selbstprobe), -farbabgleich, -bildmarken und -stroeme am Ende des
     Musters android, die letzten zwei im Prüfmodus; farbabgleich und
     bildmarken dazu im Muster android-quellen (style.css, Bildmarken im
     Web). Von Hand gefahren: alle vier grün, git status android/ danach
     leer.

36. **Ein Prüfmittel für Klassennamen, die JavaScript sucht und niemand mehr vergibt.** · gehört zu: 17 · Stand: erledigt · seit 30.08.2026
     Befund: Ein Selektor, der ins Leere greift, ist in JavaScript kein
     Fehler, sondern eine leere Liste — in P3/O6 wirkte deshalb drei Pakete
     lang kein Filter der Suchseite (`.filterspalte` war beim Umzug in die
     Leiste verschwunden, F-P3-AG). Dieselbe Lücke von der anderen Seite:
     Töne, die PHP zur Klasse zusammensetzt (`'plakette-' . $ton`), gab es
     viermal ohne Regel im Stylesheet (`warn`, zweimal `ok`, `info`).
     Wirkung: Die Seite sieht fast richtig aus; kein Mittel meldet es.
     Weg: Die billige Hälfte ist gebaut (P5b/AP9, Web 20.24.0):
     `tools/quelltext/vollstaendigkeit.py` hält die Werte von `ui_plakette`,
     `ui_knopf`, `ui_kennzahl` und `ui_meldung_markup` gegen das Stylesheet,
     Gegenprobe mit beiden Fehlern gefahren. Offen ist die andere Hälfte:
     Klassennamen, die JavaScript in Selektoren nennt, und Töne, die als
     Variable übergeben werden — dafür braucht es eine Ausnahmeliste mit
     Begründung (Klassen, die JS selbst vergibt; zusammengesetzte Selektoren
     sind statisch nicht auflösbar), keine Ja/Nein-Regel.
     Werdegang bis 26.09.2026: `docs/Backlog.md@f5bddc2`, Nr. 36.
     Erledigt 26.09.2026 mit R4-05 (Konzept R4, Web 21.1.4):
     vollstaendigkeit.py hat die sechste Prüfung Selektoren (59 Klassen in
     27 Dateien, 0 Befunde, 2 Leaflet-Klassen mit Grund in
     vollstaendigkeit-selektoren.md) und liest bei Tonübergaben auch die
     Zweige einer Bedingung und die Vorgabe im Baustein (383 geprüft, 12
     ohne Literal als Hinweis). Gegenproben: erfundener Selektor rot,
     entfernte Regel kennzahl-orange rot (über eine Bedingung übergeben).

331. **Zusammengesetzte Klassen der übrigen Bausteine sieht die Vollständigkeitsprüfung nicht.** · gehört zu: 17 · Stand: erledigt · seit 24.09.2026
     *Aufgenommen 24.09.2026 in Konzept BV (BV-03, F-BV-11), notiert und nicht
     mitgemacht.* BV-02 hat die Meldungstöne aufgelöst (Nr. 274): Ihre Liste
     ist geschlossen und steht in zwei Bausteinen. Dieselbe Grenze trifft
     andere Klassen, die zur Laufzeit entstehen. *(Hier stand als Beispiel
     `c-dc-<spalte>` — die Gegenprüfung P-BV-02 hat gezeigt, dass diese Klasse
     zwar erzeugt, aber an kein Element gesetzt wird; ein Mittel müsste genau
     das unterscheiden.)* Von den 62 Hinweisen „Regel im Stylesheet, im Markup
     nicht gefunden" (gemessen 24.09.2026) tragen **28** das Präfix eines
     Bausteins (`symbol-` 9, `pwq-` 5, `kennzahl-` 4, `karte-` 3, `blatt-` 2,
     `knopf-` 2, `zaehler-` 2, `plakette-` 1) — vermutlich zusammengesetzt,
     nicht einzeln geprüft. Verschwände eine dieser Regeln, meldete es nur die
     Tonprüfung am Aufruf — und die kennt vier Bausteine, und auch die nur
     dort, wo der Wert als Literal übergeben wird. *Weg:* je Baustein prüfen,
     ob sein Wertevorrat geschlossen ist; wo ja, wie in BV-02 lesen, wo nein,
     bleibt es beim Hinweis. **Zuordnung: Backlog-Runde** (Prüfmittel).
     Erledigt 26.09.2026 mit R4-05 (Konzept R4): pruefung_klassen() liest
     Klassen an Klassen-Stellen (Option klasse, Symbol-Zusatzklasse,
     Leaflet-Behälter, Zweig einer Bedingung u. a.) und zusammengesetzte aus
     geschlossenem Vorrat (Tonübergaben, pwq- aus STUFEN); Hinweise 73 → 13,
     jeder benannt, die sechs ohne Verwender als Nr. 341. Gegenprobe:
     entfernte Regel pwq-2 rot.
