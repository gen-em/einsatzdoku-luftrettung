# Konzept KA — Sicherheitspaket nach dem Audit

**Schritt 28 des Rahmenplans, Sofortpunkt vor Schritt 13 (R92).**
Befundteil: `docs/konzepte/Audit-KA-Sicherheit-Krypto.md` — dort stehen die
43 Befunde F-KA-01 bis F-KA-43 mit Fundstelle, Angriffsweg und Einstufung.
Dieses Konzept sagt, **was daraus gebaut wird und in welcher Reihenfolge**.
Es ändert keinen Code und legt keine Versionsnummer fest; die Stufe je Paket
steht da, die Nummer setzt die Umsetzung.

## Statusblock

| | |
|---|---|
| Stand | **Konzept, zur Freigabe** (10.11.2026) |
| In Arbeit | kein Paket |
| Erledigt | — |
| Hakt | KA-M1 ist bis auf einen Rest erledigt (Abschnitt 3): offen ist, ob die Umgebung `produktion` auf `main` und die Tag-Muster beschränkt ist — hält nichts auf |
| Gelesen gegen | Commit `e8b0fc5` · Web 24.10.1 · Uhr 4.3.3 · Android 0.22.0 |

**Die Umsetzung findet in einer eigenen Sitzung statt** — ein Paket nach
dem anderen, nach jedem Paket Statusblock, Prüfstand, Push
(`CLAUDE.md` 7 und 8).

---

## 1. Aufgabe

Alle 43 Befunde des Audits werden behoben, von hoch bis Hinweis (E-KA-04).
Kein Befund wird als hingenommen ins Bedrohungsmodell verschoben; wo eine
Grenze bauartbedingt bleibt, steht sie am Paket und geht mit KA-07 ins
Modell.

**Was danach gilt, in vier Sätzen:**

1. Kein Geheimnis, das Patientendaten öffnet, verlässt den Browser — auch
   nicht zum Drucken.
2. Jede Verbindung der Kette prüft ihr Gegenüber, und kein freier Text wird
   in ihr zu Code.
3. Ein verschlüsselter Block gehört nachweisbar zu seinem Konto, seinem
   Datensatz und seinem Zweck; Gerätedaten gehören nachweisbar zu einem
   gekoppelten Gerät.
4. Was ein Gerät aufzeichnet, landet im Konto, an das es beim Aufzeichnen
   gekoppelt war, oder nirgends.

**Nicht Teil dieses Schritts:** eine Rotation des Inhaltsschlüssels
(F-R17-10 bleibt Restrisiko R7; Schritt 14 setzt jedes Konto neu auf und
gibt ihm damit einen neuen), Signaturen für Sicherungspakete, ein echter
Safari.

---

## 2. Entscheidungen

| Nr. | Entscheidung | Wann, wer |
|---|---|---|
| E-KA-01 | Das Audit liest alles, unabhängig vom Bedrohungsmodell | 09.11.2026, BetreiberIn |
| E-KA-02 | Gemischte Arbeitsweise; jeder Befund eines Unter-Agenten wird an der Fundstelle nachgeprüft | 09.11.2026, BetreiberIn |
| E-KA-03 | Ergebnis als Dokument im Repositorium, keine Korrekturen im Audit | 09.11.2026, BetreiberIn |
| E-KA-04 | **Alle Befunde werden umgesetzt, von hoch bis niedrig, die Hinweise eingeschlossen** | 10.11.2026, BetreiberIn |
| E-KA-05 | **Zuordnung in beiden Stufen in einem Zug:** Bindung der Chiffretexte **und** Herkunftsnachweis der Gerätedaten — weil der Gerätecode für die Bindung ohnehin angefasst wird (Antwort auf Q-KA-03) | 10.11.2026, BetreiberIn |
| E-KA-06 | Der Bericht geht als **Sofortpunkt mit Konzept** in den Rahmenplan: Schritt 28 vor Schritt 13 (R92). Bedrohungsmodell und Prüfliste werden nachgezogen, wo nötig (Antwort auf Q-KA-04) | 10.11.2026, BetreiberIn |
| E-KA-07 | **Die Wiederherstellungsschlüssel werden erneuert — nach KA-02, nicht davor.** Davor schickte der Druckknopf auch den neuen Schlüssel an den Server. Vollständig heilt erst Schritt 14, weil das Erneuern denselben Inhaltsschlüssel nur neu verpackt (Antwort auf Q-KA-01: ob ein Proxy mitschreibt, ist nicht bekannt) | 10.11.2026, BetreiberIn |
| E-KA-08 | Der Formatwechsel ist ein Paket mit **eigener Entwurfsrunde** (KA-06a) vor der ersten Zeile Code; Format und Vektoren stehen wie bei S11 in einer Referenz, an der alle Umsetzungen gemessen werden | 10.11.2026, Konzept |
| E-KA-09 | **Nach KA-01 und KA-02 wird sofort getaggt** — beide hohen Befunde auf Produktiv, ohne auf KA-03 zu warten (Antwort auf Q-KA-05) | 10.11.2026, BetreiberIn |
| E-KA-10 | **Die Tokens für Jobs, Kette und Health werden nur einmal gezeigt,** direkt nach dem Erzeugen, und gehasht gespeichert; ein verlorenes Token wird erneuert, nicht nachgelesen (Antwort auf Q-KA-06) | 10.11.2026, BetreiberIn |

---

## 3. Was am 10.11.2026 nachgemessen wurde (Antwort auf Q-KA-02)

Gelesen über die Schnittstelle von GitHub, nur lesend.

| Was | Stand |
|---|---|
| Repositorium | **öffentlich**, Forks erlaubt, heute keiner — die Lauf-Protokolle liest jeder |
| `main` | Rulesets „Main Protect" (PR-Pflicht, Pflichtprüfung „Stufe 1" strikt, kein Löschen, kein Force-Push, kein Bypass) und „Main Merge-Recht" (Schreiben nur per PR, Bypass ein Konto) |
| Zeiger `produktion` | Ruleset „Zeiger produktion" seit 06.11.2026 — **nur gegen Löschen**; schreiben und umsetzen darf, wer Schreibrecht hat |
| Tags | **kein Ruleset** — einen `web-v*`-Tag setzt, wer Schreibrecht hat; davor steht dann die Pflichtfreigabe |
| Umgebung `produktion` | aus der Arbeitsumgebung nicht lesbar (der Proxy sperrt den Pfad) — **von der BetreiberIn abgelesen:** eine Pflichtprüferin (ihr Konto), „Prevent self-review“ aus. **Nicht abgelesen:** „Deployment branches and tags“ |
| Freigabe von Fork-Läufen | **von der BetreiberIn abgelesen:** „Require approval for all external contributors“ — die strengste der drei Stufen |
| Rechte des Lauf-Tokens | abgelesen: nur lesend; Läufe dürfen keine Pull Requests anlegen oder freigeben |
| Erlaubte Aktionen | abgelesen: alle; „Require actions to be pinned to a full-length commit SHA“ **aus** |
| Aufbewahrung der Lauf-Protokolle | abgelesen: 90 Tage |

Die abgelesenen Zeilen stammen aus vier Bildschirmfotos der BetreiberIn vom
10.11.2026, nicht aus einer eigenen Messung.

**Folgen:**

- **F-KA-25 wiegt schwerer**, weil das Protokoll öffentlich ist — und was
  die Kette bis zu KA-01 gedruckt hat, bleibt 90 Tage lesbar (KA-M2).
- **Der Nebenbefund zu F-KA-24 ist erledigt:** Ein Lauf aus einem Fork
  startet nie ohne Freigabe.
- **Selbstfreigabe ist erlaubt und muss es bleiben,** solange es eine
  einzige BetreiberIn gibt — sonst gäbe niemand ihren eigenen Tag frei. Der
  Preis: Wer unter ihrem Konto handelt, kann auslösen **und** freigeben.
  Die Arbeitsumgebung kann es nicht; der Proxy sperrt die Pfade unter
  `actions/`, soweit versucht (403). Mit einem zweiten Konto mit
  Freigaberecht wird der Haken gesetzt.
- **Die SHA-Pflicht für Aktionen ist heute nur der eigene Riegel**
  (`tools/kettenaufrufe/`). GitHub kann dasselbe auf seiner Seite
  erzwingen; alle 19 fremden Aufrufe erfüllen es schon. Einschalten mit
  KA-M2, nach einem Probelauf.
- Das Bedrohungsmodell nannte den Zeiger „ungeschützt“, was seit dem
  06.11.2026 nur noch für das Schreiben stimmt (nachgezogen).
- **Offen bleibt eine Einstellung:** die Beschränkung der Umgebung auf
  Zweige und Tags (Rest von KA-M1).

---

## 4. Reihenfolge und Abhängigkeiten

```
KA-01 Kette ──┐
KA-02 Blatt ──┴─► KA-M3 (Tag: beide hohen Befunde auf Produktiv) ─► KA-M4 (Schlüssel erneuern)
KA-03 Verwaltung
KA-04 Geräte: Kontobindung, Sendeweg
KA-05 Mengen, Schreibwege, Fremdbestandteile
KA-06 Bindung und Herkunft (a Entwurf → b Browser → c Geräte → d Server → e Altformat)
KA-07 Modell, Texte, Abschluss
```

- **KA-01 und KA-02 zuerst**, in dieser Reihenfolge: Sie schließen beide
  hohen Befunde. KA-01 fasst nur Kette und Werkzeuge an und stuft keine
  Fassung hoch.
- **KA-03 bis KA-05** hängen nicht voneinander ab; die Reihenfolge folgt dem
  Gewicht. KA-04 fasst `watch/`, `android/` und zwei Serverdateien an und
  darf nach der Faustregel des Rahmenplans (Abschnitt 4) nicht neben einem
  anderen Paket laufen, das `server/` schreibt.
- **KA-06 nach KA-04**, weil beide denselben Gerätecode anfassen und die
  Kontobindung vor dem Formatwechsel stehen muss.
- **KA-07 zuletzt**; das Bedrohungsmodell wird aber **mit jedem Paket**
  fortgeschrieben, das Abschnitt 4 des Modells berührt (`CLAUDE.md` 4) —
  KA-07 liest am Ende das Ganze gegen.

**Buchführung vor dem ersten Paket:** Die Umsetzung reserviert eine
Zehnerspanne im Kopf von `docs/Backlog.md` und pusht sie (E-SD-21). Jede neue
Probe braucht die Zeile „Anlass: Nr. …" (Grundsatz 5 des Prüfablaufs); die
Nummern entstehen aus den Befunden, die eine Probe bekommen.

---

## 5. Arbeitspakete

Je Paket: Ziel, Befunde, Unterpunkte, berührte Auslieferung mit Stufe,
Pflegepflichten, Prüfung, Modell und Fächerung. „Stufe" meint die
Zählweise aus `CLAUDE.md` 2 — ohne Nummer.

### KA-01 Kette — Zertifikat, Eingänge, Riegel

**Ziel:** Die Auslieferung prüft ihr Gegenüber, kein Eingang wird zu Code,
und das Protokoll nennt nichts, was eine Schranke ist.
**Befunde:** F-KA-02, -14, -15, -24, -25, -26, -37, -39.

1. `security: strict` am FTPS-Schritt in `ausliefern-lauf.yml`. **Erst
   messen, dann scharf:** ein Probelauf gegen Staging und einer gegen
   Produktiv (Handlauf der BetreiberIn, KA-M2) — schlägt die Prüfung fehl,
   liefert der Hoster ein Zertifikat, das nicht zum FTP-Namen passt, und der
   Name in `FTP_SERVER` ist zu berichtigen, nicht der Schalter.
2. `probelauf_mengenprobe` über `env:` in den Schritt reichen; dazu die
   Regel als Riegel: `tools/kettenaufrufe/` misst, dass kein
   `${{ inputs.* }}` vom Typ `string` und kein `${{ github.event.* }}` in
   einem `run:` steht.
3. Die Kettenwerkzeuge (`zielprobe.py`, `tor.py`, die Aufrufe in Stufe 2)
   lesen FTP-Passwort, Job-Token und Prüfkonto aus der Umgebung statt aus
   den Argumenten.
4. `tor.py` druckt `komplett.datei` und `wartung.von` nicht mehr; die
   Zusammenfassung des Staging-Laufs ebenso.
5. `.claude/settings.json`: `mcp__github__create_or_update_file` und
   `mcp__github__delete_file` in die Deny-Liste. **Die Datei steht damit in
   der Berührung des Pull Requests — gewollt** (`CLAUDE.md` 8).
6. `bericht.py`: Liefert `git rev-parse` nichts, ist das rot; Selbstprobe um
   die Lage erweitern.
7. `fremdsummen.py` nimmt `server/vendor/` (beide Listen) und
   `gradle-wrapper.jar` (Sollwert im Werkzeug) auf.
8. Dienst-Abbilder in `pruefung.yml` am Digest; der Riegel in
   `tools/kettenaufrufe/` sieht künftig auch `image:`.
9. Zwei Einstellungen der BetreiberIn begleiten das Paket (KA-M2): die
   SHA-Pflicht für Aktionen in GitHub einschalten, und die Protokolle der
   Läufe vor Punkt 4 ansehen — sie tragen `komplett.datei` und
   `wartung.von` noch bis zu 90 Tage.

**Berührt:** `.github/`, `tools/`, `.claude/` — **keine Fassung** (`CLAUDE.md`
2, Punkt 1). **Pflege:** `CLAUDE.md` 3 (Zahl und Wortlaut zu `uses:` bleiben,
der neue Riegel kommt dazu), `docs/Technik.md` 12, `docs/Pruefablauf.md`,
`tools/…/LIESMICH.md`, Bedrohungsmodell (Zeile Kette, R22).
**Prüfung:** Selbstproben der geänderten Werkzeuge mit je einer neuen roten
Lage; Stufe 1 auf dem PR; **nicht örtlich prüfbar:** Punkt 1 — nur ein
echter Lauf belegt ihn (KA-M2).
**Modell:** Opus. **Fächerung:** keine — acht kleine Änderungen, zwei davon
in derselben Workflow-Datei.

### KA-02 Notfallblatt im Browser

**Ziel:** Der Wiederherstellungsschlüssel verlässt den Tab nie.
**Befunde:** F-KA-01, -36.

1. Das Blatt entsteht im Browser: eine Seite ohne Formularziel, der Code per
   `textContent` aus dem Speicher der aufrufenden Seite, Druck über
   `window.print()`. Übergabe an ein neues Fenster über einen Weg, der
   **nicht** über das Netz geht (kein POST, keine Adresszeile; die
   Fragmentkennung wäre der einfachste zulässige Weg — sie erreicht den
   Server nicht, steht aber im Verlauf; die Entscheidung fällt im Paket mit
   Begründung).
2. `notfallblatt.php` nimmt keinen `code` mehr an; ein POST mit dem Feld
   antwortet 400 und schreibt nichts mit. Kontoadresse und Anlagenname
   kommen wie bisher vom Server — sie sind kein Geheimnis.
3. Beide Aufrufer (`pw_handling.php`, `schluessel_teile.php`) umstellen;
   Kopfkommentar und `docs/Technik.md` berichtigen.
4. Derselbe Bau für `codeblatt.php`, soweit es Werte spiegelt (F-KA-36).
5. Ein Riegel: Der Name des Feldes, das den Schlüssel trug, kommt in keinem
   Formular mit `action` mehr vor (`tools/quelltext/`).
6. Seitentext nach der Erneuerung: ein Satz, dass ältere Blätter über den
   Server gedruckt wurden (Vorbereitung von KA-M4).

**Berührt:** Web, Stufe **neben** (ein Weg durch die Anwendung ändert sich).
**Pflege:** `docs/Handbuch.md` (Verschlüsselung, Notfallblatt),
`docs/Technik.md`, `docs/Design.md` nur falls das Druckbild einen Baustein
ändert (dann Mockup und Freigabe), Bedrohungsmodell (Lagen M und O, R21).
**Prüfung:** Browserprobe in drei Engines — der Code erscheint auf dem
Blatt, und **kein Anfragekörper der ganzen Folge enthält ihn** (die Probe
schneidet die Anfragen mit; das ist die Zahl). Druckbild im Bilderlauf.
Handprüfung: Papier, ein echter Safari.
**Modell:** Opus. **Fächerung:** keine.

### KA-03 Verwaltung — Schranken, die halten, was sie sagen

**Ziel:** Der frische Zweitfaktor hat keinen Nachbarweg; Zusagen der
Verwaltung stimmen mit dem Code.
**Befunde:** F-KA-03, -08, -09, -19, -20, -21, -28, -35, -41, -42.

1. `user_add` mit Rolle ≠ `user` verlangt den frischen Code (Eintrag in
   `ZF_FRISCH_HANDLUNGEN`); Mail an alle BetreiberInnen bei jedem neuen
   Konto mit Rechten.
2. Freigabe eines Konto-Backups: Zielkonten mit offener Adressbestätigung
   abweisen; die Herkunft an die Kennung binden, die Adresse nur als
   zweiten Weg mit Frist. `pat_key_check` nicht mehr ausliefern, wenn der
   Browser ihn nicht braucht — sonst den Satz im Modell berichtigen.
3. SFTP: ohne gespeicherten Abdruck sendet der Job nicht; die Meldung sagt
   „erst prüfen".
4. Job-, Ketten- und Health-Token: gehasht speichern, einmalig nach dem
   Erzeugen zeigen (E-KA-10). **Folge für den Betrieb:** Wer ein Token verloren hat,
   erneuert es — ins Runbook und nach KA-M-Liste (Uptime Kuma, Geheimnisse
   der Kette).
5. Betriebs- und Kontaktadresse: nur BetreiberIn, mit Protokoll und Mail an
   die alte Adresse.
6. `admin_sicherungen.php`: derselbe Riegel nach oben wie
   `admin_user.php`; Sollwerte der harten Bestätigung bildet der Server.
7. Dienstadresse der Adresssuche: Host gegen ein strenges Muster; die CSP
   setzt nur, was das Muster erfüllt.
8. `adresse_bestaetigen.php`: der Link zeigt eine Seite mit Knopf, die
   Änderung geschieht per POST.
9. `api/schluessel_erneuern.php`: Demo-Ausstieg.
10. Anmeldesperre (F-KA-42): Ein gemerktes Gerät darf trotz Kontosperre
    weiter versuchen — die Sperre trifft dann den Rater, nicht die
    Inhaberin. Ohne gemerktes Gerät bleibt es wie heute.

**Berührt:** Web, Stufe **neben**. Migration, falls die Token-Spalten ihre
Form wechseln — dann **`update.php` ansagen**.
**Pflege:** Handbuch (Verwaltung, Betrieb), Technik (4.x Zweitfaktor,
Sicherungsziele, 13 Runbook), `docs/Backup-Format.md` falls die Freigabe ihr
Paket ändert, Bedrohungsmodell (Lage I, R25, R27).
**Prüfung:** je Punkt eine Probe mit roter Gegenlage (Anfrage ohne frischen
Code → 303 zur Bestätigung; Freigabe an ein Konto mit offener Adresse →
abgewiesen; Job ohne Abdruck → sendet nicht). Staging-Nachprüfung nach R91.
**Modell:** Opus. **Fächerung:** Punkte 1, 3, 5, 7, 8 und 9 fassen je eigene
Dateien an und können gefächert umgebaut werden; 2, 4, 6 und 10 seriell.
Prüfarbeit nie gefächert.

### KA-04 Geräte — Kontobindung und Sendeweg

**Ziel:** Kein Gerät schickt Aufgezeichnetes in ein anderes Konto als das,
an das es beim Aufzeichnen gekoppelt war; kein Punkt geht still verloren.
**Befunde:** F-KA-05, -06, -07, -18, -27, -31, -32, -33.

1. **Garmin-Uhr:** `Pair.start()` weist bei laufendem Dienst immer ab. Die
   Bindung an den alten Schlüssel deckt künftig auch laufende Pakete, falls
   eine Trennung auf anderem Weg entsteht (`cred` gelöscht).
2. **Handy:** Dienstbeginn von der Wear-Uhr nur bei bestehender Kopplung —
   sonst quittieren, nicht wirken, der Uhr den Zustand melden. Jede neue
   Kopplung bindet vorgefundenen Bestand ohne Bindung als fremd.
   `trennen()` verweigert bei laufendem Dienst. Unlesbarer Tresor: Bestand
   wird gebunden, nicht mitgenommen.
3. **Server, `ingest.php`:** Wird ein Stück nicht abgelegt, weil der
   Schlüssel kollidiert, quittiert der Server nur `spur_naechste_seq()`.
   Gegenprüfen, ob das Handy ebenso stückelt wie die Uhr (im Audit nicht
   geprüft).
4. **Uhr:** `next_seq` höchstens `seq_from + gesendete Punkte`; die Größe
   eines unbestätigten Stücks merken und identisch wiederholen.
5. **Handy:** 400, 401 und 403 nur mit dem `error`-Feld des Vertrags als
   endgültig werten, sonst „später erneut"; vor dem Räumen nach Fristablauf
   ein Sendeversuch.
6. **Server, `pair.php`:** `status` und `trennen` prüfen `active` und den
   Kontostatus wie `ingest.php`.
7. **Android-Kleinigkeiten:** Benachrichtigungsaktion „Dienst beenden" mit
   `setAuthenticationRequired` (ab API 31); untere Zeitgrenze für
   `DIENST_BEGINNEN`; `taskAffinity=""`.

**Berührt:** Uhr **neben**, Android **neben**, Web **Korrektur** (zwei
Endpunkte); `docs/JSON-Vertrag.md` (Quittung, Fehlerfelder).
**Pflege:** Handbuch (Geräte, Kopplung — Zeile 129 stimmt dann),
`android/LIESMICH.md`, Technik 6, `docs/Geraete-Eingabe.md`,
Bedrohungsmodell (Lage H, R24).
**Prüfung:** Android-Prüffälle für die drei Wege (die Module haben Tests);
`tools/proben/ingest/` um die Dublette mit größerer Punktzahl; Uhr im
Simulator (`tools/uhr-pruefstand/`); Emulator nach `Pruefablauf.md` 6.9.
**Nicht maschinell prüfbar:** Übergabe einer echten Uhr im laufenden Dienst,
Sperrbildschirm am echten Handy — Prüfliste.
**Modell:** Opus. **Fächerung:** Uhr, Android und Server sind getrennte
Dateibäume und können gefächert umgebaut werden; der Vertrag wird vorher
seriell festgelegt.

### KA-05 Mengen, Schreibwege, Fremdbestandteile

**Ziel:** Kein Konto trifft die Anlage; jeder Schreibweg prüft; keine
Bibliothek mit bekannter Lücke liest fremde Dateien.
**Befunde:** F-KA-10, -11, -22, -23, -34, -38, -40.

1. Mengengrenze: `konto_fuellstand()` an den Anfang jedes **anlegenden**
   Web-Wegs (sechs Stellen, Audit F-KA-10), `konto_mengen_fortschreiben()`
   danach; Löschen und Bearbeiten bleiben frei. Eigene, enge Grenze für das
   Demo-Konto. **Eine Stelle je Sache:** Der Riegel kommt als eine Funktion,
   und `tools/zaehlung/register.php` bekommt die Zeile.
2. SheetJS auf eine behobene Fassung vom Hersteller-Archiv, lokal
   vendoriert, Herkunft und Summe im Kopf; `docs/Lizenzen.md` nennt die neue
   Quelle (**Klasse `netz` der Textprobe:** die Adresse muss dort stehen).
3. `EdHtml.meldung()` liest Optionen nur als eigene Eigenschaften; dieselbe
   Durchsicht für jeden Baustein in `html.js`, der ein Optionsobjekt nimmt.
4. Wiederherstellung: Katalogspalten durch dieselben Prüfaufrufe wie der
   Import; `notes` nicht mehr als Klartextspalte annehmen.
5. Teil-Vorlagen und Bibliotheken per `.htaccess` sperren; dazu eine
   Wächterkonstante, die auch ohne Apache hält. `migrations/` sperren,
   `Options -Indexes`, `.zustand/` in die Pfadliste der Wache.
6. Regex-Anker: `\z` an allen Stellen, die eine Kennung oder ein
   Weiterleitungsziel prüfen; ein Riegel in `tools/quelltext/` zählt `$/'`
   ohne `D` in `preg_match` über Anfragewerte.
7. Sicherung: Fassung-2-Rückfall abschalten (unversiegelt ist rot),
   Passphrase des Komplett-Downloads auf die Mindestlänge der Konten,
   Protokollarchiv nicht ins gemeinsame Temp-Verzeichnis, Einträge je
   CSP-Bericht deckeln.

**Berührt:** Web, Stufe **neben**. Punkt 7 kann eine Sicherung älterer
Fassung unlesbar machen — **vorher zählen**, wie viele Pakete Fassung 2
tragen, und es im Changelog sagen.
**Pflege:** Lizenzen, Backup-Format, Technik (4.x, 12, 13), Handbuch
(Import, Grenzen), Bedrohungsmodell (G4, R26).
**Prüfung:** Mengenprobe je Weg mit roter Gegenlage; Importprobe mit den
vorhandenen Tabellenfällen in drei Engines (die Bibliothek wechselt);
Kreisläufe `edbak` und CSV; `fremdsummen`. **Nicht nachgestellt wird** die
Lücke selbst — die Probe belegt die neue Fassung und das gehärtete Gadget,
nicht einen gescheiterten Angriff.
**Modell:** Opus. **Fächerung:** Punkte 1, 2+3, 4, 5+6 und 7 fassen getrennte
Dateien an; seriell bleibt Punkt 1 in sich (sechs Endpunkte, eine Funktion).

### KA-06 Bindung und Herkunft — der Formatwechsel

**Ziel:** Zuordnung in beiden Stufen (E-KA-05). Ein Block, der nicht an
seine Stelle gehört, geht nicht auf; Gerätedaten, die kein gekoppeltes Gerät
verschlossen hat, werden als solche erkannt.
**Befunde:** F-KA-04, -12, -13, -16, -17, -29, -30.

Das ist ein Umbau des Datenformats in vier Umsetzungen (Browser, Handy,
Garmin-Uhr, PHP-Formprüfung) mit Altbestand: Stufe **haupt** für Web und
Uhr, **neben** oder **haupt** für Android nach dessen Zählweise.

**KA-06a Entwurf und Referenz** — vor jeder Zeile Anwendungscode. Ergebnis:
ein Formatdokument im Kopf von `tools/proben/huelle/referenz.py` (wie S11),
Prüfvektoren, und eine Freigaberunde (KA-M5). Die Skizze, von der der
Entwurf ausgeht:

- **Chiffretexte unter dem Inhaltsschlüssel (Stufe 1).** Neue Kennung
  (Arbeitsname `edk2:`), AES-256-GCM mit Zusatzdaten aus Zweck
  (`pat`, `kp`, `rw`, `wrap`), Kontokennung und — für `pat` — `client_ref`
  des Einsatzes. Gegen das Zurücklegen ein Zähler je Einsatz im Block, den
  der Browser gegen einen Stand vergleicht, den der Server nicht fälschen
  kann; **wo dieser Stand liegt, ist die erste Entwurfsfrage** (ein
  Verzeichnis unter dem Inhaltsschlüssel kostet einen Abruf je Liste).
  Auffüllen auf Größenklassen (F-KA-30).
- **Gerätehülle Fassung 2 (Stufe 1).** Absolute Zeiten in der Hülle statt
  relativer zu einem Klartextanker; ein Zähler in der Einsatzhülle; die
  Stückfolge einer Spur verkettet, damit ein fehlendes Stück auffällt.
- **Herkunft (Stufe 2).** Ein Schlüsselpaar je Gerät, erzeugt bei der
  Kopplung. **Vorschlag: authentifizierte Hülle statt Signatur** — der
  Schlüssel der Hülle entsteht aus dem flüchtigen **und** dem statischen
  Geräteschlüssel (zwei ECDH gegen den Kontoschlüssel). Das braucht nur
  Verfahren, die alle drei Geräte heute schon benutzen. **Zu messen in
  06a:** ob `Toybox.Cryptography` auf den Zielgeräten ein statisches Paar
  dauerhaft halten kann, und was eine Signatur dort kostete — erst danach
  fällt die Wahl.
- **Der öffentliche Geräteschlüssel braucht einen zweiten Kanal** — sonst
  tauscht ihn, wer den Server beschreibt. Das ist derselbe fehlende Kanal
  wie beim Kontoschlüssel (Modell 6.3) und wie in F-KA-17: **ein kurzer
  Vergleichswert**, abgeleitet aus Kontoschlüssel und Geräteschlüssel,
  den das Gerät **und** die Webseite bei der Kopplung zeigen. Er ersetzt
  die maskierte Adresse als zweites Tor. Der Browser legt den bestätigten
  Geräteschlüssel unter dem Inhaltsschlüssel ab.
- **Ableitung (ohne Formatwechsel der Daten).** Untergrenze der Rundenzahl
  im Browser, Formprüfung des Salzes (F-KA-13); Vormerkfach mit Zeitstempel
  und Räumen auf jedem Weg (F-KA-16); ein PBKDF2-Block plus HKDF für Hälfte
  und Token (F-KA-29) — **das ändert das Anmelde-Token jedes Kontos** und
  läuft deshalb über die stille Umstellung beim Anmelden, wie die Anhebung
  der Rundenzahl.

**KA-06b Browser.** `EdCrypto` schreibt die neue Fassung, liest beide; ein
Anhebelauf nach dem Muster `api/pat_anheben.php` hebt den Altbestand
(Patientenblöcke, beide privaten Teile, die Wiederherstellungshülle beim
nächsten Erneuern). Die drei Punkte zur Ableitung gehen hier mit und können
**vorab als eigenes kleines Paket** ausgeliefert werden (F-KA-13 und -16
brauchen keinen Formatwechsel).

**KA-06c Geräte.** Geräteschlüssel, Hülle Fassung 2, Kopplung mit
Vergleichswert — Garmin-Uhr und Handy; die Wear-Uhr bleibt ohne Schlüssel
(sie sendet nicht). **Gekoppelte Geräte haben keinen Schlüssel:** Sie
koppeln neu oder senden bis dahin Fassung 1, die der Browser als „ohne
Herkunftsnachweis" kennzeichnet. Die Frist dafür entscheidet KA-M5.

**KA-06d Server und Formate.** `validate_lib.php` und `geraetehuelle_lib.php`
prüfen Form und Größe beider Fassungen; `docs/JSON-Vertrag.md`,
`docs/Backup-Format.md` (Nutzlast hebt sich, Altpakete werden beim
Einspielen im Browser gehoben), `docs/Export-Format.md` falls berührt.
**Der Server kann weiterhin nichts öffnen und nichts prüfen außer der Form.**

**KA-06e Altformat.** Was mit Gerätedaten geschieht, die nie einen
Herkunftsnachweis bekommen können (die Geräte von damals verschließen sie
nicht neu): Der Browser verschließt sie im Anhebelauf unter einem eigenen
Kennzeichen „vom Browser übernommen am …". Annahme von Fassung 1 am Server
endet zu einem Stichtag (`400`, wie `klartext` in S11).

**Berührt:** Web, Uhr, Android; Migration (Spalte für Geräteschlüssel,
Zähler) — **`update.php` ansagen**. **Pflege:** `CLAUDE.md` 4 (der Absatz
zur Verschlüsselung nennt dann Bindung und Herkunft), Technik 4.14 und 4.40,
Bedrohungsmodell 3, 4.1, 4.3, 6 vollständig neu für diese Zeilen,
`tools/quelltext/kennzeichnung-soll.md` falls ein neues Zeichen entsteht
(dann Mockup und Freigabe), Handbuch (Kopplung, Verschlüsselung).
**Prüfung:** Hüllenprobe gegen die Referenz in allen vier Umsetzungen,
byte-gleich; je Angriff aus F-KA-04 und F-KA-12 eine Probe, die ihn ausführt
und den Fehler des Browsers misst (vertauschen, zurücklegen, Zeit
verschieben, Stück entfernen, Hülle ohne Geräteschlüssel); Anhebelauf am
Messstand mit dem großen Bestand; voller Prüfstand (Hauptstufe).
**Nicht maschinell prüfbar:** Kopplung mit Vergleichswert an echten Geräten.
**Modell: Fable** für 06a und für den Krypto-Kern von 06b und 06c (K8 des
Rahmenplans) — **vor Beginn darauf hinweisen und pausieren**; Opus für 06d,
06e und die Oberfläche. **Fächerung:** 06a seriell. 06c: Uhr und Handy
gefächert, nachdem die Referenz steht. 06b seriell (eine Bibliothek, viele
Verbraucher).

### KA-07 Modell, Texte, Abschluss

**Ziel:** Was die Dokumente sagen, tut der Code.
**Befunde:** F-KA-43 und die Restpflege aller Pakete.

1. `docs/Bedrohungsmodell.md` einmal ganz gegen den dann erreichten Stand
   lesen; die Zeilen R21 bis R27 gehen als behoben oder als benannte Grenze
   in den Bestand; die Kopfzeile „Nachgelesen gegen" hebt sich.
2. Die fünf Kommentare aus F-KA-43 berichtigen.
3. `CLAUDE.md` 3 und 4, soweit die Pakete es nicht schon getan haben.
4. Prüfdokument nach `CLAUDE.md` 7, danach Aufgehen in der Prüfliste.
5. Abschluss nach Rahmenplan 9: Erledigt-Zeile, Reste nach 6, Backlog-
   Kopfzeilen, Verlaufszeile; dieses Konzept und der Audit-Bericht werden
   gelöscht, die Git-Historie behält beide.

**Berührt:** `docs/`, Kommentare — Stufe **Korrektur** für die Auslieferung,
deren Kommentare sich ändern. **Modell:** Opus. **Fächerung:** keine
(erzählender Text).

---

## 6. Meilensteine der BetreiberIn

| Nr. | Was | Wann |
|---|---|---|
| KA-M1 | In GitHub ansehen: Umgebung `produktion` und Freigabe von Fork-Läufen — **abgelesen am 10.11.2026** (Abschnitt 3). **Rest:** in der Umgebung `produktion` den Abschnitt „Deployment branches and tags“ ansehen und auf `main` und die drei Tag-Muster beschränken | vor KA-01 |
| KA-M2 | Probelauf der Auslieferung mit `security: strict` gegen Staging und Produktiv starten und freigeben; ein Ruleset für Tags setzen (`web-v*`, `android-v*`, `uhr-v*`); danach „Require actions to be pinned to a full-length commit SHA“ einschalten und einen Lauf abwarten; die Protokolle älterer Auslieferungsläufe löschen, soweit sie `wartung.von` nennen | in KA-01 |
| KA-M3 | Tag nach KA-01 und KA-02 — beide hohen Befunde auf Produktiv (E-KA-09) | nach KA-02 |
| KA-M4 | Wiederherstellungsschlüssel erneuern lassen: eine Ankündigung an alle Konten, die ein Notfallblatt gedruckt haben können (E-KA-07) | nach KA-M3 |
| KA-M5 | Freigaberunde zum Formatentwurf: Lage des Zählerstands, Bauart der Herkunft, Frist für Geräte ohne Schlüssel, Stichtag für Fassung 1 | nach KA-06a |
| KA-M6 | Gerätetests: Kopplung mit Vergleichswert, Übergabe im laufenden Dienst, Sperrbildschirm | nach KA-04 und nach KA-06c |

---

## 7. Fragen

| Nr. | Frage | Antwort |
|---|---|---|
| Q-KA-01 | Schreibt ein Proxy oder Hoster Anfragekörper mit? | nicht bekannt — die Schlüssel werden erneuert (E-KA-07) |
| Q-KA-02 | Regeln für `produktion` und Tags, Fork-Läufe? | nachgemessen und abgelesen, Abschnitt 3; eine Einstellung bleibt (Rest von KA-M1) |
| Q-KA-03 | Zuordnung als Zusage oder als Grenze? | Zusage, beide Stufen in einem Zug (E-KA-05) |
| Q-KA-04 | Wie geht der Bericht in die Steuerung ein? | Sofortpunkt mit Konzept (E-KA-06) |
| Q-KA-05 | Soll nach KA-01 und KA-02 sofort getaggt werden, oder erst mit KA-03? | sofort (E-KA-09) |
| Q-KA-06 | Dürfen Token für Jobs, Kette und Health nach KA-03 nicht mehr nachlesbar sein (einmalige Anzeige)? Das ändert den Betrieb | ja (E-KA-10) |

---

## 8. Risiken der Umsetzung

- **KA-01 Punkt 1 kann die Auslieferung anhalten**, wenn das Zertifikat des
  Hosters nicht zum FTP-Namen passt. Deshalb zuerst der Probelauf, und der
  Schalter erst, wenn er grün ist — nicht umgekehrt.
- **KA-03 Punkt 4 und KA-06 ändern den Betrieb** (Token, Kopplung). Beides
  gehört ins Runbook, bevor es ausgeliefert wird.
- **KA-06 ist das einzige Paket, das Daten unlesbar machen kann.** Der
  Anhebelauf schreibt nur, was er vorher gelesen hat, und vergleicht gegen
  den alten Block (`blob_alt`, wie heute); der Messstand fährt ihn am großen
  Bestand, bevor er ausgeliefert wird. Fassung 1 bleibt lesbar, bis KA-06e
  sie ausdrücklich beendet.
- **Der Umfang ist groß:** sieben Pakete, davon eines eine Hauptstufe in
  drei Auslieferungen. Schritt 13 wartet so lange. Wenn das zu lang wird,
  ist die Trennstelle zwischen KA-05 und KA-06 — KA-06 kann nach R90 nicht
  hinter v1.0, weil v1.0 das Format festschreibt.
