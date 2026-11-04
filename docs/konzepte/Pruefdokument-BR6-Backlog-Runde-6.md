# Prüfdokument BR6 — Backlog-Runde 6 (Schritt 23)

*Prüfliste zu `Konzept-BR6-Backlog-Runde-6.md`: Web 23.7.4 bis 23.7.7, Uhr
4.3.2, Android 0.21.0 und der Prüfaufbau. Was maschinell gemessen wurde,
steht mit Mittel und Zahl im Prüfprotokoll des Konzepts (Abschnitt 6) und im
Prüfbericht des Commits. Nach dem Merge geht diese Liste in
`docs/konzepte/Pruefliste.md` auf (CLAUDE.md 7).*

## 1. Was nicht geprüft werden konnte, und warum

- **Staging (MySQL 8.4.10) für Nr. 458.** Der Fehler ist auf MySQL 8.4.0 und
  8.0 nachgestellt und behoben (Schemaprobe, Fall 8). Ob der Wartungslauf auf
  Staging wieder durchläuft, zeigt erst der Merge (P-BR6-01).
- **Die Seitentexte zu Nr. 481 im Browser.** Die Rückgabe von
  `mail_einreihen()` ist mit der Gegenstelle der Mailprobe gemessen; die
  Meldungen auf den Seiten (Einladung, Bestätigung, Testmail) nur gelesen.
  Der Fall `zu_spaet` beim ersten Versuch kommt mit keinem Katalogeintrag
  vor und ist nur gelesen (P-BR6-03).
- **Echte Garmin-Hardware** für Nr. 470 — nur Simulator (Fenix 6 Pro,
  FR 945, Venu 3s) (P-BR6-04).
- **Ein echtes Telefon** für Nr. 471 — nur Robolectric und Bilderlauf. Das
  Räumen nach 30 Tagen selbst ist am Gerät nicht in vertretbarer Zeit zu
  sehen; die Zeile mit dem Datum schon (P-BR6-05, -06).
- **Ein echter Safari** für Nr. 480 — gemessen in Chromium, Firefox und
  WebKit; WebKit ist nicht Safari (P-BR6-07).
- **`hochfahren.sh --php 8.3`** mit der neuen Wurzelprüfung (Nr. 490): Im
  Behälter heißt die Wurzel `/app`, aufgelöst über `docker inspect`; gelesen,
  nicht gefahren.

## 2. Grenzen der benutzten Prüfmittel

- **Schemaprobe, Fall 8:** Sie stellt die Abfrage über `db()`, also mit
  echt vorbereiteten Anweisungen wie die Anwendung. Andere Abfragen misst
  sie nicht; die Gegenprobe über alle 16 `GROUP BY` war einmalig.
- **Demo-Messung (Nr. 459):** gemessen sind Startseite, Einstellungen und
  ein Einsatz. Eine Seite, die beim Laden eine andere POST schickt, setzt die
  Marke weiter — gewollt, solange die Ausnahmeliste nur Gemessenes trägt.
- **Bildprobe der Sync-Seite:** Die Breite misst sie gegen die Sehne der
  App (`Ui.kanteW`). Liegt die Rechnung der App falsch, liegt die Probe mit;
  das Bild bleibt der Beleg. Zwei Venu-Messungen sind Ausnahmen (E-BR6-17).
- **Robolectric (`SenderTest`):** stellt die Uhr und den Server; die
  Ablage der Frist (`SharedPreferences`) ist die echte, das Telefon nicht.

## 3. Handprüfliste

| Nr. | Wer | Bedienweg | Erwartet | Scheitern erkennbar an | Wann |
|---|---|---|---|---|---|
| P-BR6-01 | Claude | **Wartungslauf auf Staging (Nr. 458).** Mit dem eigenen BetreiberIn-Konto anmelden, Startseite öffnen; Verwaltung → Reiter System; Einstellungen → „GPS-Daten verdichten“ | keine neue Zeile „wartungslauf … 1055“ seit dem Merge; die Karte nennt eine Zahl statt „Fällig unbekannt“; danach P-S11-30 fahren | eine neue 1055-Zeile, oder „Fällig unbekannt“ | nach dem Merge, Auslieferungslauf grün |
| P-BR6-02 | Claude | **Demo-Konto ansehen (Nr. 459).** Verwaltung → Demo-Konto → „Zurücksetzen“; als Demo anmelden, Startseite, Einstellungen, einen Einsatz ansehen; Verwaltung → Demo-Konto | „Geändert: nein“ | „Geändert seit …“ ohne Änderung | wie P-BR6-01 |
| P-BR6-03 | Claude | **Endgültige Mail-Absage (Nr. 481).** Verwaltung → Konten → neues Konto für eine nicht vorhandene Adresse der eigenen Domain | „Konto angelegt — die E-Mail konnte NICHT verschickt werden.“ und der Link; im Reiter E-Mail die Zeile „unzustellbar“ | „steht in der Warteschlange“ | wie P-BR6-01; das Konto danach löschen |
| P-BR6-04 | Betreiberin | **Sync-Seite auf der Venu 3s (Nr. 470).** Uhr 4.3.2 aufspielen, ohne Kopplung mit zwei offenen Paketen (Telefon aus) die Sync-Seite öffnen; wenn möglich mit einer Fehlermeldung dazu | „Koppeln: Action halten“ ganz lesbar, kein Buchstabe am Rand abgeschnitten | ein Ende der Zeile fehlt — bitte mit Foto melden | vor dem Tag `uhr-v4.3.2` |
| P-BR6-05 | Betreiberin | **Frist am Telefon (Nr. 471).** Android 0.21.0, an Staging gekoppelt; im Web das Telefon löschen; einen kurzen Dienst aufzeichnen und beenden | Ergebniszeile „Schlüssel abgewiesen …“, darunter „Ungesendete Pakete bleiben bis TT.MM. auf dem Telefon“ mit einem Datum 30 Tage nach heute | keine Datumszeile, oder ein Datum, das nicht 30 Tage entfernt ist | vor dem Tag `android-v0.21.0` |
| P-BR6-06 | Betreiberin | **Frist nach dem Update (Nr. 471).** Ein Telefon, das unter 0.20.0 schon „Schlüssel abgewiesen“ zeigte, auf 0.21.0 heben und die App öffnen | Datumszeile mit dem Tag des Updates plus 30 Tage | ein Datum in der Vergangenheit, oder die Pakete sind sofort fort | wie P-BR6-05, nur wenn ein solches Telefon da ist |
| P-BR6-07 | Betreiberin | **Zwei Formulare in Safari (Nr. 480)** — freiwillig. Nur wenn die Lage „beide abweichend“ ohnehin einmal entsteht: Betrieb → Servereinstellungen, „Nachtragen vom Blatt“ | zwischen „Server-Anteil nachtragen“ und „Serverschlüssel vom Blatt“ sichtbar Luft | der Knopf klebt an der Beschriftung | bei Gelegenheit |
