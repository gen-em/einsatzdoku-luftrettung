# Prüfdokument GR — Geräterunde (Schritt 21)

*Prüfliste zu `Konzept-GR-Geraeterunde.md`, Uhr 4.2.0 bis 4.3.1 und Android
0.19.0 bis 0.20.0. Was maschinell gemessen wurde, steht mit Mittel und Zahl
im Prüfprotokoll des Konzepts (Abschnitt 6) und im Prüfbericht des Commits.
Nach dem Merge geht diese Liste in `docs/konzepte/Pruefliste.md` auf
(CLAUDE.md 7).*

## 1. Was nicht geprüft werden konnte, und warum

- **Echte Garmin-Hardware.** Alles an der Uhr lief im Simulator (SDK 9.2.0,
  Fenix 6 Pro, FR 945, Venu 3s). Ob eine echte Uhr den **Körper einer 403**
  durchreicht, ist offen; ohne ihn steht „Gerät oder Konto gesperrt" statt
  des Grundes (P-GR-01).
- **Echtes Telefon.** Die Handy-Änderungen sind durch 299 Prüffälle je
  Bauart belegt, die Oberfläche (Ergebniszeilen, rote Zeile „Pakete gehören
  dem bisherigen Konto", Rückfragen) **nicht**: Es lief kein Emulator und
  kein Bilderlauf des Handys in GR (P-GR-04 bis -07).
- **Der Handy-Rundlauf gegen die örtliche Anlage** (Gerät löschen, neu
  koppeln, Pakete kommen an) ist nur als Prüffall mit einem Prüfserver
  gebaut, nicht gegen `ingest.php` und `pair.php` gefahren (P-GR-05, -06).
- **Mengenbremse, Wartung und volles Konto** (429, 503, 507) hat kein
  Rundlauf gestellt. Uhr: nur gelesen; Handy: Prüffälle mit Prüfserver
  (P-GR-03).
- **Wear-OS-Uhr:** nicht berührt, nicht geprüft (E-GR-03).

## 2. Grenzen der benutzten Prüfmittel

- **Uhr-Rundlauf** (`tools/uhr-pruefstand/rundlauf/`): stellt Löschen,
  Deaktivieren und einen anderen Kontoschlüssel per SQL nach, nicht über die
  Weboberfläche. Der „andere Schlüssel" ist ein gültig versiegelter
  Zufallsschlüssel am Demo-Konto, kein zweites Konto. Venu 3s fährt er nicht.
- **Bildprobe der Sync-Seite** (`tools/uhr-pruefstand/syncseite/`): misst
  Höhen, keine Breiten — ein Text, der am Kreisrand abgeschnitten wird,
  fällt nur im Bild auf (Nr. 470).
- **Handy-Prüffälle:** Robolectric mit einem Prüfserver auf der
  Rückschleife; `Retry-After` und 403-Körper kommen von dort, nicht von
  `ingest.php`.

## 3. Handprüfliste

| Nr. | Wer | Bedienweg | Erwartet | Scheitern erkennbar an | Wann |
|---|---|---|---|---|---|
| P-GR-01 | Betreiberin | **Garmin, Gerät deaktivieren.** Uhr 4.3.1 auf Fenix 6 Pro, gekoppelt an ein Staging-Konto, einen kurzen Dienst aufzeichnen; vor dem Dienstende im Web Einstellungen → Geräte die Uhr deaktivieren; Dienst beenden, Sync-Seite öffnen | „Gerät im Web deaktiviert / Im Web klären, bis TT.MM." und „N Pakete offen" | „Abgemeldet" oder „Neu koppeln"; „Gerät oder Konto gesperrt" heißt: Die Uhr bekommt den Körper nicht (Befund melden) | nach dem Merge, vor `uhr-v…` |
| P-GR-02 | Betreiberin | **Garmin, wieder aktivieren.** Fortsetzung von P-GR-01: im Web aktivieren, auf der Uhr die Sync-Seite verlassen und wieder öffnen | Pakete gehen ohne Neustart der App hinaus, „Sync vollständig"; im Web der Dienst | Rückstand bleibt, bis die App neu gestartet wird | wie P-GR-01 |
| P-GR-03 | Betreiberin | **Garmin, gelöscht mit Rückstand.** Uhr mit einem ungesendeten Dienst (Telefon aus), im Web die Uhr löschen, Telefon an; Sync-Seite → „Abgemeldet"; START halten | Rückfrage „Trennen? N Pakete bleiben und gehen nur ans selbe Konto"; nach Ja ein Code; an **dasselbe** Konto koppeln → die Pakete kommen im Web an | „Erst N Pakete senden", oder die Pakete kommen nicht an, oder „Altes Gerät im Web löschen" unter „Gekoppelt" | wie P-GR-01 |
| P-GR-04 | Betreiberin | **Handy, Gerät deaktivieren.** Android 0.20.0, gekoppelt an Staging; im Web das Telefon deaktivieren; einen kurzen Dienst aufzeichnen und beenden | Benachrichtigung „Dienst beendet · Senden angehalten – Gerät oder Konto im Web gesperrt"; in der App „Gerät im Web deaktiviert · dort wieder aktivieren" | „Keine Verbindung · wird nachgeholt" oder „Schlüssel abgewiesen" | nach dem Merge, vor `android-v…` |
| P-GR-05 | Betreiberin | **Handy, gelöscht mit Rückstand, dasselbe Konto.** Telefon im Web löschen, einen Dienst aufzeichnen und beenden („Schlüssel abgewiesen"); Einstellungen → Gerät trennen | Rückfrage nennt die Pakete und „an dasselbe Konto"; nach dem Trennen „Getrennt" mit „N Pakete warten auf dem Telefon"; an dasselbe Konto koppeln → „Gesendet", der Dienst im Web | Trennen verweigert („Sie gehören dem bisherigen Konto"), oder nach der Kopplung bleibt der Rückstand stehen | wie P-GR-04 |
| P-GR-06 | Betreiberin | **Handy, anderes Konto.** Wie P-GR-05, aber an ein **zweites** Testkonto koppeln | rote Zeile „N Pakete gehören dem bisherigen Konto", Knopf „Pakete des bisherigen Kontos verwerfen …"; im zweiten Konto erscheint **nichts**; ein neuer Dienst geht ins zweite Konto; Verwerfen mit Rückfrage leert die Zeile | ein Einsatz des ersten Kontos im zweiten — **sofort melden, das ist der Datenabfluss, gegen den E-GR-01 gebaut ist** | wie P-GR-04 |
| P-GR-07 | Betreiberin | **Handy, Konto gesperrt.** Als Administratorin ein Testkonto sperren, mit dessen Telefon „Jetzt senden" | „Konto gesperrt · Senden angehalten"; nach dem Entsperren und „Jetzt senden" „Gesendet" | „Keine Verbindung" oder ein Neukoppeln-Rat | wie P-GR-04 |
| P-GR-08 | Claude | **Bilder der Sync-Seite ansehen.** Nach dem Prüfstand die zwölf Bilder unter `$CIQ_BASIS/syncseite/` | keine zwei Texte übereinander auf Fenix, FR 945, Venu 3s | Überlappung trotz grüner Rechnung (dann misst die Probe daneben) | vor dem Pull Request — **erledigt 02.11.2026** (Konzept, Prüfprotokoll GR-06) |
