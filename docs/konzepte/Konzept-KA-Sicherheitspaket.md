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
| Stand | **Umsetzung** seit 10.11.2026, Zweig `claude/affectionate-darwin-vqocxe`, Backlog-Spanne 560–569 |
| In Arbeit | **KA-03** — bestätigt 10.11.2026, im Bau. **Garmin-Anteile ausgegliedert an KG** (E-KA-21, Abschnitt 11) |
| Erledigt | **KA-01** (PR #165, gemergt 10.11.2026; Lauf 191 auf `main` grün, FTPS gegen Staging mit `security: strict`) · **KA-02** (PR #166, gemergt 10.11.2026, Web 24.11.0; Lauf 192 grün, auf Staging nachgeprüft — Abschnitt 9) |
| Hakt | nichts im Code. **Offen bei der BetreiberIn:** der Probelauf gegen Produktiv mit `security: strict` (KA-M2, P-KA-02) — Staging ist belegt |
| Gelesen gegen | Commit `e8b0fc5` · Web 24.10.1 · Uhr 4.3.3 · Android 0.22.0; die Befunde von KA-01 am 10.11.2026 gegen `3e06f50` nachgelesen — alle acht halten (Abschnitt 9) |

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
| E-KA-11 | **Der Rückfallstand auf Staging wird mit seinem Zeitstempel genannt, ohne Zufallsteil** (KA-01 Punkt 4). Der Job schrieb den Dateinamen bewusst neben den Tag, damit klar ist, auf welchen Stand Staging zurückgesetzt wird; die acht Hexzeichen sind aber die zweite Schranke des Ordners. Der Zeitstempel nennt den Stand eindeutig | 10.11.2026, BetreiberIn (Rückfrage vor KA-01) |
| E-KA-12 | **Der Schlussschritt „Wenn etwas schiefging" läuft auch bei Abbruch** (`failure() \|\| cancelled()`). Ein Job, der in sein Zeitlimit läuft, gilt als abgebrochen, nicht als gescheitert — und gerade dann gehört die Ansage dazu, ob die Wartung an ist | 10.11.2026, BetreiberIn |
| E-KA-13 | **`integritaet.yml` bekommt in F-KA-44 ebenfalls ein Zeitlimit** — dieselbe Lücke wie die übrigen Arbeitsläufe | 10.11.2026, BetreiberIn |
| E-KA-14 | **Riegel 3 sperrt jeden Eingang im Skript, nicht nur die vom Typ `string`.** Ein `boolean` kann nichts einschleusen, aber eine Regel ohne Typ-Nachschlagen hat keine Lücke, wenn jemand den Typ eines Eingangs ändert; die vier `boolean`-Stellen kosteten je eine `env:`-Zeile | 10.11.2026, Umsetzung |
| E-KA-15 | **Geheimnisse der Stufe-2-Werkzeuge über `--…-aus NAME`**, der Schalter nennt die Umgebungsvariable. `kreislauf.py` liest das Kettentoken bewusst **nicht** still aus `JOBS_TOKEN` (Kommentar vom 17.09.2026: ein vergessenes `export` schickte den Kreislauf sonst gegen die falsche Anlage); der Schalter bleibt deshalb ausdrücklich, nur der Wert verlässt die Befehlszeile. Die reinen Kettenwerkzeuge (`tor.py`, `zielprobe.py`, `zustand.py`, `apkablage.py`) lesen fest `JOBS_TOKEN` bzw. `FTP_PASSWORD`; `--token` und `--ftp-pass` gibt es nicht mehr | 10.11.2026, Umsetzung |
| E-KA-16 | **Es gibt eine Nutzerin, die BetreiberIn — keine Benachrichtigung, keine Rückwärtsverträglichkeit.** Daraus: KA-02 Punkt 6 (Satz nach der Erneuerung) entfällt; KA-M4 schrumpft auf „die BetreiberIn erneuert ihren Schlüssel nach dem Tag"; ein alter Aufrufer der Blätter bekommt 400 statt eines Übergangs. Für KA-05 Punkt 7 und KA-06e heißt das: kein Rückfall und keine Übergangsfrist für alte Formate — **aber** die eigenen Daten der BetreiberIn im Altformat müssen lesbar umziehen; das wird vor KA-06 noch einmal gefragt | 10.11.2026, BetreiberIn |
| E-KA-17 | **Übergabe an das Druckfenster per `postMessage`**, Ursprung und Fenster geprüft auf beiden Seiten. Die Fragmentkennung (`#code`) erreicht den Server nicht, steht aber im Verlauf, und viele Browser gleichen ihn zwischen Geräten ab; ein Druck im selben Fenster hätte den Bedienweg geändert (kein Blatt am Bildschirm) | 10.11.2026, BetreiberIn (Vorschlag der Umsetzung) |
| E-KA-18 | **Die Kontoadresse geht bei der Erstvergabe mit dem Code ans Blatt** — sie ist kein Geheimnis, und ohne Sitzung kennt das Blatt sie sonst nicht; mit Sitzung geht die aus der Sitzung vor | 10.11.2026, BetreiberIn |
| E-KA-19 | **Nr. 540 ohne Mockup:** Blätter und Druck stellen die Rollen auf ihre hellen Werte zurück — vorhandene Token, kein neuer Farbwert, kein neuer Baustein; das Druckbild bleibt das gedachte | 10.11.2026, BetreiberIn |
| E-KA-20 | **Der Riegel aus KA-02 Punkt 5 misst Formulare und Abrufe mit einem Druckblatt als Ziel, nicht den Feldnamen.** `code` heißt auch das Feld der Zweitfaktor-Formulare; ein Riegel über den Namen schlüge falsch an oder würde ausgenommen | 10.11.2026, BetreiberIn |
| E-KA-21 | **Die Garmin-Anteile gehen an eine eigene Instanz, Kürzel KG** (Abschnitt 11): KA-04 Punkt 1 und 4, der Garmin-Teil von KA-06c samt der Messung für 06a, die Garmin-Kommentare aus F-KA-43 und der Garmin-Teil von KA-M6. KG zählt eigene Nummern (`KG-NN`, `F-KG-NN`, `E-KG-NN`, `P-KG-NN`), führt ein eigenes Konzept und schreibt den Rahmenplan nicht — das tut diese Instanz | 10.11.2026, BetreiberIn |

---

## 3. Was am 10.11.2026 nachgemessen wurde (Antwort auf Q-KA-02)

Gelesen über die Schnittstelle von GitHub, nur lesend.

| Was | Stand |
|---|---|
| Repositorium | **öffentlich**, Forks erlaubt, heute keiner — die Lauf-Protokolle liest jeder |
| `main` | Rulesets „Main Protect" (PR-Pflicht, Pflichtprüfung „Stufe 1" strikt, kein Löschen, kein Force-Push, kein Bypass) und „Main Merge-Recht" (Schreiben nur per PR, Bypass ein Konto) |
| Zeiger `produktion` | Ruleset „Zeiger produktion" seit 06.11.2026 — **nur gegen Löschen**; schreiben und umsetzen darf, wer Schreibrecht hat |
| Tags | **drei Rulesets seit 10.11.2026**, gemessen über die Schnittstelle: aktiv, Ziel `refs/tags/web-v*`, `refs/tags/android-v*`, `refs/tags/uhr-v*`; Anlegen, Ändern und Löschen gesperrt, kein Force-Push; Bypass ein Konto (das der BetreiberIn) |
| Umgebung `produktion` | aus der Arbeitsumgebung nicht lesbar (der Proxy sperrt den Pfad) — **von der BetreiberIn abgelesen:** eine Pflichtprüferin (ihr Konto), „Prevent self-review“ aus, Umgehen durch Administratoren aus. „Deployment branches and tags“ stand auf „No restriction“ und ist **seit 10.11.2026 beschränkt** auf den Zweig `main` und die Tags `web-v*`, `android-v*`, `uhr-v*` („1 branch and 21 tags allowed“ — 13, 4 und 4 Tags, mit dem Repositorium abgeglichen) |
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
- **F-R17-04 ist damit für fremde Konten geschlossen:** Ein Lauf von einem
  beliebigen Zweig erreicht die Umgebung nicht mehr, und einen Tag der drei
  Muster legt nur die BetreiberIn an. `main` bleibt zugelassen, weil die
  Probeläufe von dort starten.
- **Was bleibt:** Der Handlauf auf `main` mit freiem Text im Eingang und
  einem Freigabeklick (F-KA-14, KA-01) — und alles, was unter dem Konto
  der BetreiberIn handelt; der Bypass der Rulesets gilt auch dafür.
- **Zwei Anläufe brauchte es:** Die Tag-Muster der Umgebung standen zuerst
  als Zweig-Regeln da (kein Tag hätte ausgeliefert), und die Rulesets
  hatten zuerst kein Ziel. Der Name einer Regel ist nicht ihr Ziel — der
  Bedienweg gehört ins Runbook (KA-01, Punkt 9).

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
**Befunde:** F-KA-02, -14, -15, -24, -25, -26, -37, -39, dazu F-KA-44 (neu, Abschnitt 10).

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
   `wartung.von` noch bis zu 90 Tage. Dazu ins Runbook (`docs/Technik.md`
   13), wie die Umgebung und die Tag-Rulesets eingerichtet sind und woran
   man eine wirkungslose Regel erkennt („0 tags allowed“, „does not target
   any resources“) — wer die Anlage in Schritt 14 neu aufsetzt, braucht es.
10. **Zeitlimits (F-KA-44, Auftrag der BetreiberIn vom 10.11.2026):**
    `timeout-minutes` an jedem Job in `pruefung.yml`, Werte aus den
    Laufzeiten der letzten grünen Läufe hergeleitet; prüfen, ob
    `auslieferung.yml` und `ausliefern-lauf.yml` dieselbe Lücke haben, dazu
    `integritaet.yml` (E-KA-13).

**Berührt:** `.github/`, `tools/`, `.claude/` — **keine Fassung** (`CLAUDE.md`
2, Punkt 1). **Pflege:** `CLAUDE.md` 3 (Zahl und Wortlaut zu `uses:` bleiben,
der neue Riegel kommt dazu), `docs/Technik.md` 12, `docs/Pruefablauf.md`,
`tools/…/LIESMICH.md`, Bedrohungsmodell (Zeile Kette, R22).
**Prüfung:** Selbstproben der geänderten Werkzeuge mit je einer neuen roten
Lage; Stufe 1 auf dem PR; **nicht örtlich prüfbar:** Punkt 1 — nur ein
echter Lauf belegt ihn (KA-M2).
**Modell:** Opus. **Fächerung:** keine — acht kleine Änderungen, zwei davon
in derselben Workflow-Datei.

**Umgesetzt (10.11.2026)** — Einzelheiten in Abschnitt 9 und 10:

| Punkt | Was gebaut ist |
|---|---|
| 1 | `security: strict` am FTPS-Schritt; Riegel 6 in `tools/kettenaufrufe/` |
| 2 | Alle Eingänge in `run:` über `env:` (Zielprobe, Gesprächslauf, Schlussschritt, `zeiger`); Riegel 3 (E-KA-14) |
| 3 | `tor.py` liest `JOBS_TOKEN`, `zielprobe.py`/`zustand.py`/`apkablage.py` lesen `FTP_PASSWORD`; `kreislauf.py`, `pruefkonto.py`, Rückwegprobe mit `--…-aus NAME` (E-KA-15); `kreislauf.py` und `aufnehmen.mjs` reichen das Token an `tor.py` über die Umgebung; Riegel 5 |
| 4 | `fuers_protokoll()` in `tor.py`: ohne Zufallsteil, ohne `wartung.von`; `--frage komplett` gibt den Zeitstempel (E-KA-11) |
| 5 | Deny-Liste um `create_or_update_file` und `delete_file` |
| 6 | `bericht.py`: leerer Baum ist rot, Lage 1b |
| 7 | `fremdsummen.py`: beide Listen unter `server/vendor/` (jede Zeile, keine Datei daneben), `Parsedown.php`, Wrapper-JAR |
| 8 | Abbilder am Digest; Riegel 4 |
| 9 | Runbook `docs/Technik.md` 13 („Umgebung `produktion` und Tag-Rulesets"); die zwei Einstellungen der BetreiberIn bleiben KA-M2 |
| 10 | Zeitlimits an allen Jobs aller vier Arbeitsläufe (Abschnitt 10, F-KA-44); Schlussschritt auch bei Abbruch (E-KA-12) |

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
7. **Backlog Nr. 540 mitnehmen:** Notfall- und Codeblatt nehmen im Dunkeln
   Farben des Bildschirms mit (Nachprüfung MA, 08.11.2026). Das Blatt wird
   hier ohnehin neu gebaut; die Abnahme aus Nr. 540 gilt für das neue.

**Umgesetzt (10.11.2026, Web 24.11.0)** — Einzelheiten in Abschnitt 9 und 10:

| Punkt | Was gebaut ist |
|---|---|
| 1 | `assets/blatt-uebergabe.js`: Knöpfe mit `data-blatt-ziel` öffnen das leere Blatt; das Blatt meldet sich beim Ursprungsfenster und bekommt Code und Adresse per `postMessage` (E-KA-17, -18), gesetzt per `textContent` nach Formatprüfung |
| 2 | `notfallblatt.php` liest keinen Anfragewert; alles außer GET bekommt 400 ohne Protokoll |
| 3 | `pw_handling.php`, `schluessel_teile.php`, `rueckfrage.js` ohne Formular; Kopfkommentare, `docs/Technik.md` |
| 4 | `codeblatt.php` und `zweitfaktor_teile.php` ebenso; das Blatt nimmt nur mit Sitzung etwas an |
| 5 | Riegel `druckblatt` in `tools/quelltext/` (E-KA-20), Anlass Nr. 560 |
| 6 | **entfällt** (E-KA-16) |
| 7 | Nr. 540: `.blatt-seite` und `@media print` stellen jede Rolle des dunklen Modus hell (E-KA-19) |

Dazu die Notfallblattprobe (`tools/proben/notfallblatt/`, drei Engines), ein
Hinweis unter dem Knopf, wenn der Browser das Fenster blockiert
(`.rf-druck-hinweis`), und `pruefkonto.py einladen` (Konto ohne gesetztes
Passwort, für die Probe).

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

1. **→ KG-01 (E-KA-21).** **Garmin-Uhr:** `Pair.start()` weist bei laufendem Dienst immer ab. Die
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
4. **→ KG-01 (E-KA-21).** **Uhr:** `next_seq` höchstens `seq_from + gesendete Punkte`; die Größe
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
Vergleichswert — Garmin-Uhr (**→ KG-03, E-KA-21**) und Handy; die Wear-Uhr bleibt ohne Schlüssel
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
2. Die fünf Kommentare aus F-KA-43 berichtigen — die zwei in `watch/`
   (`Huelle.mc`, `Pair.mc`) mit KG-01 (E-KA-21).
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
| KA-M1 | In GitHub ansehen und setzen: Umgebung `produktion` und Freigabe von Fork-Läufen — **erledigt 10.11.2026** (Abschnitt 3): Umgebung auf `main` und die drei Tag-Muster beschränkt | erledigt |
| KA-M2 | Tag-Rulesets — **erledigt 10.11.2026**, gemessen. **Mit KA-01:** Probelauf der Auslieferung mit `security: strict` gegen Staging und Produktiv starten und freigeben; danach „Require actions to be pinned to a full-length commit SHA“ einschalten und einen Lauf abwarten; die Protokolle älterer Auslieferungsläufe löschen, soweit sie `wartung.von` nennen | in KA-01 |
| KA-M3 | Tag nach KA-01 und KA-02 — beide hohen Befunde auf Produktiv (E-KA-09) | nach KA-02 |
| KA-M4 | Wiederherstellungsschlüssel erneuern — seit E-KA-16 nur der eigene der BetreiberIn (es gibt keine weiteren Konten); unter Einstellungen → Profil, danach das alte Blatt vernichten (E-KA-07) | nach KA-M3 |
| KA-M5 | Freigaberunde zum Formatentwurf: Lage des Zählerstands, Bauart der Herkunft, Frist für Geräte ohne Schlüssel, Stichtag für Fassung 1 | nach KA-06a |
| KA-M6 | Gerätetests: Kopplung mit Vergleichswert, Übergabe im laufenden Dienst, Sperrbildschirm | nach KA-04 und nach KA-06c |

---

## 7. Fragen

| Nr. | Frage | Antwort |
|---|---|---|
| Q-KA-01 | Schreibt ein Proxy oder Hoster Anfragekörper mit? | nicht bekannt — die Schlüssel werden erneuert (E-KA-07) |
| Q-KA-02 | Regeln für `produktion` und Tags, Fork-Läufe? | nachgemessen, abgelesen und am 10.11.2026 gesetzt, Abschnitt 3 |
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

---

## 9. Prüfprotokoll

### KA-01 (10.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **`security: strict` gegen den Hoster** (Punkt 1). Örtlich gibt es weder
   den FTP-Zugang noch einen Läufer; der Riegel misst die Zeile, nicht die
   Verbindung. Belegt wird es durch den ersten Lauf auf `main` (Staging) und
   den Probelauf der BetreiberIn gegen Produktiv (KA-M2, P-KA-02). Ein
   Zertifikatsfehler dort ist der erwartete Befund, wenn der Name in
   `FTP_SERVER` nicht zum Zertifikat passt.
2. **Die geänderten Arbeitsläufe sind nicht gelaufen.** Geprüft sind YAML
   (alle vier Dateien laden), Shell-Syntax (55 `run:`-Blöcke mit `bash -n`,
   0 Fehler, Ausdrücke durch einen Platzhalter ersetzt) und die Aufrufe gegen
   die Werkzeuge (Riegel `kettenaufrufe`). Dass `timeout-minutes` und die
   `env:`-Übergaben auf GitHub tun, was sie sollen, zeigt erst Stufe 1 auf
   dem PR und der nächste Auslieferungslauf.
3. **Stufe 2 mit den `--…-aus`-Schaltern** (Kreislauf, Rückwegprobe gegen
   Staging) läuft erst auf `main`. Örtlich gefahren sind die Werkzeuge mit
   ihren Selbstproben und dem Prüfstand; der Weg „Variable der Umgebung
   `staging` → `--admin-passwort-aus STAGING_PASS`" ist nur gelesen.
4. **`actionlint` ist im Container nicht vorhanden** (`which actionlint`
   leer); die Ausdruckssyntax prüft erst GitHub.

**Befunde nachgelesen** gegen `3e06f50`: F-KA-02 (`ausliefern-lauf.yml`
FTPS-Schritt ohne `security:`), -14 (Zeile 279 `ZAHL="${{ inputs… }}"`),
-15 (Deny-Liste mit vier Einträgen), -24 (`bericht.py` `if ist and …`),
-25 (`tor.py` druckt `zustand` ungefiltert, `komplett_lib.php` nennt den
Namen die zweite Schranke), -26 (`fremdsummen.py` nimmt `server/vendor/`
aus), -37 (`--ftp-pass`, `--token` in den Aufrufen), -39 (`image: mysql:8.4.0`,
`mariadb:10.6`). Alle halten; keiner war hinfällig.

**Gemessen** (jede Zahl mit ihrem Gegenstand):

| Mittel | Ergebnis | Was es belegt |
|---|---|---|
| `kettenaufrufe --probe` | **67 von 67** (davon 27 neu: Riegel 3 sieben, 4 sieben, 5 sieben, 6 vier, je mit Gegenprobe; dazu bestehende) | jede neue Lage wird gemeldet, jede Gegenprobe nicht |
| `kettenaufrufe` gegen diesen Stand | **0 Befunde**; 147 Aufrufe, 59 `run:`-Blöcke ohne Eingang, 2 Abbilder am Digest, 0 Geheimnisschalter, 1 FTPS-Schritt strikt | die Behebung hält in allen vier Arbeitsläufen |
| `kettenaufrufe` (neue Fassung) gegen die Arbeitsläufe von `main` (`3e06f50`) | **32 Befunde**: 11 Eingänge im Skript, 2 Abbilder ohne Digest, 18 Geheimnisse auf der Befehlszeile, 1 FTPS ohne `strict` | die Riegel wären ohne die Behebung rot |
| `tor.py --selbstprobe` | **43/0** (vorher 36; neu: Token nur aus der Umgebung, `--token` scheitert laut, Protokoll ohne Zufallsteil und ohne `wartung.von`, Name ohne Zufallsteil nicht unverändert) | F-KA-25, -37 |
| `zielprobe.py --selbstprobe` | **95/0** (neu: ohne `FTP_PASSWORD` → 2 mit Namen der Variablen; `--ftp-pass` scheitert laut) | F-KA-37 |
| `zustand.py`, `apkablage.py --selbstprobe` | **28/0**, **25/0** | unverändert grün nach dem Umbau |
| `bericht.py lesen --selbstprobe` | **52 Lagen, 0 Fehlschläge** (neu Lage 1b: leerer Baum ist rot) | F-KA-24 |
| `fremdsummen.py --selbstprobe` | **14/0** (neu sieben: Datei geändert, Datei neben der Liste, gelistete fehlt, Parsedown geändert, JAR getauscht, JAR fehlt, Gegenprobe) | F-KA-26 |
| `fremdsummen.py` gegen diesen Stand | 6 + **351** Dateien (338 + 11 aus den Listen, `Parsedown.php`, JAR), 0 Befunde | die Summen stimmen heute |
| `fremdsummen.py` mit verändertem `Crypt/AES.php` und JAR (Wegwerfbaum) | **2 Befunde**, rc 1 | der Riegel schlägt an echten Dateien an |
| Laufzeiten für F-KA-44 | 30 Läufe `pruefung.yml` bis Lauf 500: höchstens **3,0 min** je Lauf; 50 Läufe `integritaet.yml`: höchstens **1,8 min**; Auslieferung: `ausliefern` höchstens rund 3 min (Produktion 23.6.0), `Rückfallstand` und `zeiger` je unter 15 s | Herleitung der Limits (Abschnitt 10) |

Der Prüfbericht des Commits trägt den Lauf von `tools/pruefstand/pruefen.sh`.

**Nach dem Merge (Lauf 191 auf `main`, 10.11.2026):** alle Jobs grün. Der
Schritt „server/ per FTPS synchronisieren" gegen Staging lief mit
`security: strict` durch — das Zertifikat von Staging passt zum Namen in
`FTP_SERVER`. Stufe 2 lief mit den `--…-aus`-Schaltern: Kreislauf `edbak`
(18 min) und Rückwegprobe grün. **Produktiv belegt erst der Probelauf der
BetreiberIn** (KA-M2).

### KA-02 (10.11.2026)

**Was nicht geprüft werden konnte — zuerst.**

1. **Papier und ein echter Safari.** Der Druck ist am Bildschirm und mit
   `emulateMedia('print')` gemessen, nicht auf Papier; WebKit ist nicht
   Safari. Prüfliste.
2. **Der dunkle Modus ist nur in Chromium gegen den hellen gehalten**, mit
   einem Werkzeug aus der Sitzung, nicht mit einer Probe des Bestands: Der
   Bilderlauf nimmt den dunklen Modus nicht auf (Nr. 521). Die Druckvorschau
   der **Tagesübersicht** aus Nr. 540 ist nicht nachgestellt; gemessen ist
   stattdessen eine Seite mit Fehlermeldung im Druck — dieselben Rollen.
3. **Ein Browser, der `window.open` blockiert:** Der Hinweis unter dem Knopf
   ist gebaut, aber nicht ausgelöst gemessen.

**Gemessen** (jede Zahl mit ihrem Gegenstand):

| Mittel | Ergebnis | Was es belegt |
|---|---|---|
| Notfallblattprobe, Chromium, örtlich | **16 von 16**: Erstvergabe 0 Treffer in 22 Anfragen bis zum Blatt und 31 bis zum Abschluss; Erneuerung 0 in 99; Codeblatt 0 in 9; Blatt zeigt Schlüssel, Adresse (ohne und mit Sitzung) und zehn Codes; POST an beide Blätter 400 ohne Wiedergabe; ohne Ursprungsfenster kein Blatt | F-KA-01, -36 behoben |
| dieselbe Probe gegen `server/` von `main` | **4 von 16**: der Schlüssel in 1 von 20 Anfragen (Erstvergabe) und 1 von 97 (Erneuerung), die Codes 10-mal in 8 Anfragen; POST 200 | sie wäre ohne die Behebung rot |
| Riegel `druckblatt` | Selbstprobe **9/0**; gegen diesen Stand 226 Dateien, **0 Befunde**; gegen `server/` von `main` **8 Befunde** (drei Formulare, drei Lesestellen, zwei fehlende Abweisungen) | der Riegel hält den alten Weg auf |
| hell gegen dunkel, Chromium, fünf Ansichten (Notfallblatt leer, gefüllt, gefüllt im Druck, Codeblatt, Anmeldung mit Fehlermeldung im Druck) | **0 von 158 Elementen** weichen ab (Farbe, Fläche, Rand); mit `style.css` von `main` **49** | Nr. 540 |

**Nach dem Merge (Lauf 192, 10.11.2026):** alle Jobs grün; Staging meldet
24.11.0, ein POST an `notfallblatt.php` bekommt dort 400. **R91 auf
Staging:** Notfallblattprobe gegen Staging in drei Engines je 16 von 16
(Wegwerfkonto, angelegt und gelöscht mit dem BetreiberIn-Konto der
Arbeitsumgebung); hell gegen dunkel 0 von 173 Elementen abweichend.
Offen bleiben Papier und ein echter Safari (P-KA-03).

Prüfstand und Bericht: im Commit von KA-02. Der volle Lauf (`39a9420`,
Stufe neben, drei Engines, zehn Breiten, 8 852 s) war **64 grün, 1 rot** —
die Wartungslaufprobe, unabhängig von KA-02 (F-KA-54); der Kopf des PR erbt
die 64 und misst die berichtigte Probe neu.

---

## 10. Befunde der Umsetzung

| Nr. | Befund | Wie gelöst |
|---|---|---|
| F-KA-44 | **Kein Job der vier Arbeitsläufe hatte ein Zeitlimit außer fünf in `auslieferung.yml`.** Am 10.11.2026 hing Lauf 499 im ersten Versuch 45 Minuten im Schritt „PHP-Syntax über server/ und tools/", endete ohne abrufbares Protokoll als fehlgeschlagen und war im zweiten Versuch nach zwei Minuten grün — am Commit hatte sich nichts geändert (gemeldet von der BetreiberIn). Ohne Limit gilt die Vorgabe von GitHub, sechs Stunden. Dieselbe Lücke hatten `ausliefern` (`ausliefern-lauf.yml`), `Rückfallstand`, `zeiger` und `wache` | `timeout-minutes` an jedem Job: `Schon gemessen?` 5, `Stufe 1` 15, `Schema` 10 (gemessen höchstens 3,0 min je ganzer Lauf, rund 40 s je Datenbank); `wache` 15 (höchstens 1,8 min); `zeiger` 10 (rund 10 s); `ausliefern` 150 und `Rückfallstand` 120 — **über** der Grenze des Backup-Tors (2 Runden × 40 Aufrufe × bis 60 s Antwort + 20 s Pause ≈ 107 min), damit das Tor seinen Abbruch selbst meldet; darüber Luft für einen vollen Abgleich. `staging` und `produktion` rufen einen wiederverwendbaren Lauf auf und nehmen kein `timeout-minutes`; das Limit steht an dessen Job. Der Schlussschritt läuft auch bei Abbruch (E-KA-12) |
| F-KA-45 | **`tools/kettenaufrufe/LIESMICH.md` und `tools/kette/LIESMICH.md` gingen beim Nachtragen über 40 Zeilen** (`bestand` misst die Decke) | beide neu gefasst, je 40 Zeilen; die Zahlengeschichte steht hier, nicht dort |
| F-KA-46 | **`docs/Pruefablauf.md` 6.8 nannte die Ausnahmeliste „von acht Pfaden"** — seit Web 23.3.1 sind es sieben (`CLAUDE.md` 3) | an Ort und Stelle berichtigt |
| F-KA-47 | **Ein Satz in `android/LIESMICH.md`, dass `fremdsummen` die Wrapper-JAR jetzt misst, hätte den Android-Bau ausgelöst** (Muster `android/**`), und das Android-SDK fehlt in dieser Arbeitsumgebung (`/opt/android-sdk` nicht vorhanden) — der Prüfstand wäre für eine Zeile Text rot | Satz zurückgenommen; **geht mit KA-04 hinein**, das `android/` ohnehin anfasst. Bis dahin sagen es `Lizenzen.md` 3a, `Technik.md` 12.14 und der Kopf von `fremdsummen.py` (`WRAPPER_SOLL`) |
| F-KA-48 | **Der Container wurde neu gestartet, während die Sitzung auf die Antwort der BetreiberIn wartete** (`uptime` 44 min um 12:21; die örtliche Anlage antwortete nicht mehr) | `hochfahren.sh` wieder gefahren, die Anlage stand ohne `--neu`; nichts verloren, weil nichts lief. Bestätigt `docs/Sandbox-Setup.md` 6.1a |
| F-KA-49 | **Der erste Schlusssatz zu Nr. 540 sagte „abgenommen im Bilderlauf"** — der Bilderlauf nimmt den dunklen Modus aber nicht auf (Nr. 521) | nachgemessen mit einem Vergleich hell gegen dunkel (Abschnitt 9, KA-02), Satz berichtigt, bevor er committet war |
| F-KA-50 | **Der Schritt in `pruefung.yml` hieß „Quelltext — vierzehn Prüfungen"**, es waren schon sechzehn, mit `druckblatt` siebzehn; ebenso der Kopf von `tools/quelltext/pruefen.sh` („fünfzehn") | beide berichtigt; niemand liest den Schrittnamen (gesucht) |
| F-KA-51 | **Der Bilderlauf baute das gefüllte Notfall- und Codeblatt über den alten POST** (`tools/screenshots/aufnehmen.mjs`, Schritte `notfallblatt` und `codeblatt`) — im ersten Prüfstand von KA-02 rot mit 20 Konsolenfehlern („Stylesheet nicht angewendet": die 400-Antwort ist Text) | `blattUeberFenster()`: ein Helferfenster derselben Sitzung öffnet das Blatt und beantwortet dessen Meldung wie der Druckknopf; das gefüllte `<main>` geht in die aufgenommene Seite. Einzeln nachgefahren: 9 Bilder, 0 Konsolenfehler |
| F-KA-52 | **Der Stilvergleich war rot mit 19 ungeplanten Signaturen je Engine** — alle aus `.rf-druck` (Umbruch, neue Zeile `.rf-druck-hinweis`) und der um 4 px längeren Katalogseite; keine weitere | geplante Listen neu geschrieben (`gegen.sh --schreiben`), je 19 Signaturen; die drei geerbten Zeilen des vorigen Schritts fallen weg |
| F-KA-53 | **Bei 360 px stoßen die fünf Schlüsselgruppen des Notfallblatts aneinander** (gesehen im Bilderlauf, Baustein unverändert seit Web 21.1.0) | **in KA-02 behoben** (Wunsch der BetreiberIn, statt eines Backlog-Punkts; die zuerst angelegte Nr. 561 ist nie gepusht worden und entfällt): unter 480 px am Bildschirm drei Gruppen je Zeile mit Spaltenabstand, auf Papier weiter fünf; Bilderlauf 02f bei 360 px ohne Überlauf, Gruppen getrennt |
| F-KA-54 | **Die Wartungslaufprobe war seit OF-07 (Web 24.8.0) rot, ohne dass es auffiel** — Teil C rief die Tagesansicht ohne `#ruheliste` auf, und seit OF-07 steht die Karte „Ruhesegmente" nur auf Abruf da; der Knopf blieb verborgen, der Klick lief in die Zeitgrenze. Grün zuletzt am 07.11.2026 (MA-18); seither lief sie in keinem Bericht, weil kein Paket ihr Muster berührte. Im Prüfstand von KA-02 rot in drei Engines, mit `server/` von `main` ebenso | Probe ruft `index.php?d=…#ruheliste` auf und klickt nur, wenn der Bereich nicht schon offen ist; danach 24 ok, 0 fehlen. Zwischenstand `39a9420` trägt den roten Bericht, der nächste Commit den grünen (Erbe nach `Pruefablauf.md` 5.4) |
| F-KA-55 | **Die Notfallblattprobe baute ihren Browserkontext von Hand** (`ignoreHTTPSErrors` nur örtlich) statt über `kontextMachen()` aus `tools/motor.mjs`; gegen Staging scheiterte Firefox an der Zertifizierungsstelle des Proxys der Arbeitsumgebung (`SEC_ERROR_UNKNOWN_ISSUER`, `Sandbox-Setup.md` 5.2). Örtlich und in Chromium/WebKit fiel es nicht auf | `kontextMachen(browser, { basis })`; danach Firefox gegen Staging 16 von 16, örtlich unverändert 16 von 16. Geht mit dem nächsten PR |
| F-KA-56 | **Der Container wurde ein zweites Mal neu gestartet**, diesmal in der Wartezeit zwischen PR und Merge (`uptime` 20 min um 18:35); die örtliche Anlage stand nicht mehr | `hochfahren.sh`, ohne `--neu`; wie F-KA-48 |

**Keine neue Probe, keine neue Backlog-Nummer:** KA-01 erweitert vorhandene
Riegel (`kettenaufrufe`, Anlass Nr. 217; `fremdsummen`, F-R17-50/-51;
`bericht.py`) um Lagen mit F-KA-Nummern. Die Spanne 560–569 bleibt für die
Pakete ab KA-02.

---

## 11. Garmin-Teil — eigene Instanz KG (seit 10.11.2026, E-KA-21)

Was die Instanz KG baut. Sie führt dazu ein eigenes Konzept
(`docs/konzepte/Konzept-KG-Garmin-Sicherheit.md`, legt sie selbst an) mit
Statusblock und Prüfprotokoll; **dieser Abschnitt ist nur der Auftrag**, sein
Stand steht dort.

| Paket | Inhalt | Befunde | Hängt an | Modell |
|---|---|---|---|---|
| KG-01 | `Pair.start()` weist bei laufendem Dienst immer ab; die Bindung an den alten Schlüssel deckt auch laufende Pakete (`cred` gelöscht); `next_seq` höchstens `seq_from + gesendete Punkte`; ein unbestätigtes Stück wird mit derselben Größe wiederholt; Kommentare `Huelle.mc` (Kurvenprüfung) und `Pair.mc` (Schulterblick) berichtigt | F-KA-05, -07 (Uhr-Seite), -18 (Uhr-Seite), -43 (zwei Stellen) | nichts — die Server-Seite von F-KA-07 (KA-04 Punkt 3) ist mit der Uhr-Deckelung verträglich | Opus |
| KG-02 | **Messung für KA-06a:** Hält `Toybox.Cryptography` auf den Zielgeräten ein statisches P-256-Paar dauerhaft (Speicher, Neustart, Update der App)? Was kostet dort eine ECDSA-Signatur (Zeit, Speicher), und gibt es sie überhaupt? Ergebnis als Befund mit Zahlen für die Entwurfsrunde | — | nichts | Opus |
| KG-03 | Garmin-Teil von KA-06c: Geräteschlüssel, Gerätehülle Fassung 2, Kopplung mit Vergleichswert — byte-gleich zur Referenz aus KA-06a | F-KA-04, -12, -17 (Garmin) | KA-06a fertig und KA-M5 freigegeben | **Fable** für den Krypto-Kern — vorher pausieren |
| KG-04 | Garmin-Teil von KA-M6: Übergabe einer echten Uhr im laufenden Dienst, Kopplung mit Vergleichswert — als Prüfliste für die BetreiberIn | — | KG-01, KG-03 | — |

**Regeln der Zusammenarbeit:**

- KG fasst `watch/`, `tools/uhr-pruefstand/`, die Garmin-Abschnitte von
  `docs/Handbuch.md`, `docs/Technik.md`, `docs/Geraete-Eingabe.md` und die
  Garmin-Zeilen des Bedrohungsmodells an — **nicht** `server/`, `android/`,
  `.github/` und nicht den Rahmenplan. Braucht KG eine Serveränderung, sagt
  sie es der BetreiberIn; diese Instanz baut sie.
- Eigene Backlog-Spanne, vor der ersten Nummer reserviert und gepusht
  (E-SD-21); eigene Uhr-Fassung (`watch/source/Const.mc`), eigener Tag
  `uhr-v*`.
- Changelog-Einträge mit Präfix `Uhr`. Merkonflikte im Changelog, im
  Bedrohungsmodell oder in diesem Konzept löst, wer als Zweiter mergt, nach
  `Pruefablauf.md` 5.3.
- Diese Instanz trägt Beginn und Ende von KG in den Rahmenplan ein, sobald
  KG es meldet.

