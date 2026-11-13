# Prüfdokument KG — Garmin-Teil des Sicherheitspakets

Teil von KG-04, angelegt am 11.11.2026 für KG-01, KG-05, KG-02 und KG-06
(Uhr 4.5.1, PR #168), seit KG-07 für Uhr 4.5.2 und seit KG-08 für Uhr 4.6.0. Was maschinell gemessen wurde, steht mit Mittel und
Zahl im Prüfprotokoll von `Konzept-KG-Garmin-Sicherheit.md` und im
Prüfbericht der Commits. Die Punkte zu KG-03 (Geräteschlüssel, Kopplung mit
Vergleichswert) kommen dazu, wenn KG-03 gebaut ist.

**Gerät:** Fenix 6 Pro mit Uhr 4.6.0, gekoppelt an das eigene Konto, Telefon
in Reichweite. Punkt 14 braucht die Venu 3s, Punkt 16 VS Code mit SDK;
Punkte 9 und 13 zusätzlich auf der FR 945, an der F-KG-18 gemeldet wurde.

---

## 1. Was nicht geprüft werden konnte, und warum

- **Alles, was nur die echte Uhr zeigt.** Der Simulator bildet die
  Reihenfolge von Tastendruck und Seitenwechsel nicht ab
  (`Geraete-Eingabe.md` 6). Den Fehler, dass kurz UP/DOWN nach einem
  Seitenwechsel ein Ereignis buchte, hat er nie erzeugt, und seine Behebung
  belegt er deshalb nicht. Das ist Punkt 1.
- **Wie sich Vibrationen anfühlen.** Der Simulator schreibt die Muster mit,
  spürt sie aber nicht. Ob die Uhr einen Puls von 2 s am Stück hält oder
  still kürzt, ist offen. Das sind Punkte 5 bis 8.
- **Ob die echte Uhr den Rumpf einer 401/403 durchreicht.** Im Simulator
  kommt er an. Auf der Uhr ist es nicht gemessen, und KG-06 baut darauf.
  Das ist Punkt 15.
- **Ob ein Update den Speicher der App hält.** Ein Update aus dem
  Connect-IQ-Store lässt sich hier nicht nachstellen. Punkt 16 stellt es per
  USB nach.
- **Eine gesperrte Uhr ohne Kontoschlüssel nach dem Wiedereinschalten**
  (Backlog Nr. 572). Diesen Zustand stellt man an einem echten Konto kaum
  her: Die Uhr müsste gekoppelt sein, bevor das Konto ein Schlüsselpaar hat.
  Belegt ist er nur durch den Rundlauf im Simulator.
- **Ein echter Funkabriss mitten in einer Anfrage, und die 30-Tage-Frist.**
  Beides lässt sich am Gerät nicht gezielt auslösen; die Probe `sendeweg`
  spielt es nach.
- **Ob die echte Uhr das Loslassen von START an das eben geöffnete Menü
  schickt** (seit Uhr 4.6.0 öffnet es beim Halten). Im Simulator tut sie
  es, und das Menü verwirft es; schickt die Uhr es woanders hin, verwirft
  das Menü den nächsten echten Druck **nicht** — der hebt den Merker auf.
  Das sind Punkte 9 und 13.
- **Die Venu 3s nur in Punkt 14.** Die Probe `rea` drückt UP und DOWN, und
  die Venu hat beide nicht.

## 2. Grenzen der benutzten Prüfmittel

- **Simulator:** Seine Zeiten sind die des Rechners. Er legt den Speicher
  je App ab, nicht je Gerät. Seine Konsole meldet mit einigen Sekunden
  Verzug.
- **`sendeweg`:** hält jede Anfrage vor dem Absenden an. Was der Server mit
  einem wiederholten Stück tut, zeigt `uhr-rundlauf`, und auch dort nur
  gegen die örtliche Anlage.
- **`rea`:** verkürzt den Countdown auf 40 s und prüft nur, was die App in
  die Konsole schreibt, nicht was die Uhr anzeigt.
- **`uhr-rundlauf`:** hält das Senden für Neukoppeln und Fremdkonto in der
  Prüffassung an. Die echte App kennt diesen Haken nicht.

## 3. Handprüfliste

| Nr. | Bedienweg | Erwartet | Gescheitert, wenn |
|---|---|---|---|
| **P-KG-03** | Dienst beginnen, Einsatz beginnen, auf die Rea-Seite, kurz START (Rea beginnt). Dann je einmal **kurz UP** und **kurz DOWN**, mit 15 s Abstand. Danach lang START → Übersicht | Die Seite bleibt die Rea-Seite. Die Übersicht zeigt genau ein Adrenalin und eine Defibrillation | die Seite wechselt; oder etwa 1 s später kommt eine weitere Vibration und ein zweiter Eintrag (F-KG-01) |
| **P-KG-04** | Rea läuft: **kurz START** zweimal kurz hintereinander, nach 7 s ein drittes Mal (Sperre seit Uhr 4.6.0 5 s) | Erster und dritter Druck je Rhythmuskontrolle mit **2 kurzen Pulsen**, der zweite tut nichts. Der Countdown springt beim ersten und dritten auf 2:00 | der zweite Druck schreibt einen Eintrag (Sperre); oder der Countdown springt nicht |
| **P-KG-05** | Rea läuft: **DOWN** (Defibrillation) | 2 kurze Pulse, beide spürbar, Countdown auf 2:00 | nur ein Puls spürbar, oder einer ist abgeschnitten (F-KG-02) |
| **P-KG-06** | Rea läuft: **UP** (Adrenalin) | 1 kurzer Puls | zwei Pulse oder keiner |
| **P-KG-07** | Rea läuft, nichts drücken, Countdown ablaufen lassen | Bei 0:30 **1 × 2 s**, bei 0:10 **2 × 2 s**, bei 0:00 **3 × 2 s**; die drei sind am Gefühl zu unterscheiden. Bei 0:00 bleibt der Countdown rot stehen | ein Muster fehlt; die langen Pulse sind spürbar kürzer als 2 s; 1, 2 und 3 lassen sich nicht unterscheiden |
| **P-KG-08** | Wie P-KG-07, aber vor 0:30 mit BACK zur Hauptanzeige und dort bleiben; einmal auch eine andere App (z. B. Uhrzeit) öffnen | Die Erinnerungen kommen auch dort; der rote Ring zeigt die laufende Rea | eine Erinnerung bleibt aus |
| **P-KG-09** | Rea läuft: **lang START** halten, 2 s warten, loslassen; dann noch einmal **kurz START** | Das Menü öffnet nach 1 s, **noch während des Haltens**, auf **Amiodaron**; das Loslassen bucht nichts. Der neue kurze Druck bucht Amiodaron | das Menü erscheint erst beim Loslassen (F-KG-18) oder gar nicht; ein Eintrag steht nach dem Loslassen in der Übersicht (F-KG-08); der neue Druck bucht nichts |
| **P-KG-10** | Im Menü „Rea BEENDEN“, dann mit BACK zurück. Dann UP, DOWN, kurz START | Pause: UP und DOWN buchen nichts, kurz START öffnet die Übersicht mit „Rea fortsetzen“ / „Rea beenden“; „Rea fortsetzen“ gibt 2 kurze Pulse | UP/DOWN buchen oder blättern; kurz START bucht eine Rhythmuskontrolle |
| **P-KG-11** | Rea läuft: **BACK**, auf der Hauptanzeige **kurz UP** | BACK führt zur Hauptanzeige, kurz UP zurück auf die Rea-Seite | kurz UP landet woanders |
| **P-KG-12** | Rea läuft: die Uhr wie gewohnt **sperren** (Tastensperre) und wieder entsperren | Kein Eintrag, keine Vibration | ein Adrenalin oder eine Defibrillation steht in der Übersicht |
| **P-KG-13** | Hauptanzeige ohne Rea: **lang START** halten, 2 s warten, loslassen (Schnellmenü); dann kurz START | Das Schnellmenü öffnet **während des Haltens**; nach dem Loslassen steht es offen, nichts ist ausgeführt. Der neue Druck führt den markierten Eintrag aus | das Menü erscheint erst beim Loslassen (FR 945, F-KG-18); beim Loslassen wird ein Eintrag ausgeführt (F-KG-08); der neue Druck wirkt nicht |
| **P-KG-14** | **Venu 3s**, Rea läuft: lang Action halten und loslassen; dann lang Zurück; dann nach oben und unten wischen | Beide langen Drücke öffnen das Menü; Wischen blättert nicht | ein Systemmenü statt des App-Menüs; Wischen verlässt die Seite |
| **P-KG-15** | Im Web das Gerät **deaktivieren**, auf der Uhr die Sync-Seite öffnen und kurz warten. Danach wieder aktivieren, Sync-Seite verlassen und neu öffnen | Erst „Gerät im Web deaktiviert“, danach geht der Rückstand hinaus | „Upload 403“ statt „Gerät im Web deaktiviert“: Die Uhr bekommt den Rumpf nicht, KG-06 braucht eine andere Regel (P-KG-02) |
| **P-KG-16** | Krypto-Probe aus `tools/uhr-pruefstand/krypto-probe/` per VS Code „Build for Device“ auf die Fenix; zweimal starten. Dann `FASSUNG` auf 2, neu bauen, `.prg` ersetzen, **ohne die App zu löschen**, starten | Lauf 1 `GERAETEPAAR erzeugt`, Lauf 2 `gleich, Lauf 2`, nach dem Ersetzen `gleich, Lauf 3`, „erzeugt in Fassung 1, jetzt 2“. Dazu die Zeilen `T-PAAR`, `T-STATISCH` notieren | `erzeugt` statt `gleich` nach dem Ersetzen (Speicher ging verloren), `ANDERS`, oder ein Absturz (P-KG-01) |
| **P-KG-17** | Dienst läuft, Uhr gekoppelt: Sync-Seite, **START halten** | „Erst Dienst beenden“, kein Code | ein Kopplungscode oder die Rückfrage „Kopplung trennen …“ (F-KA-05) |
| **P-KG-18** | Einen Einsatz mit Spur beenden, während des Sendens kurz das Bluetooth des Telefons aus- und wieder einschalten; danach im Web die Spur ansehen | Alle Punkte liegen im Web, keine Lücke im Zeitraum | eine Lücke in der Spur (F-KA-07) |
| **P-KG-19** | Nach P-KG-20 (getrennt, **nicht** neu gekoppelt) mit BACK zum Startbildschirm: rot „Nicht gekoppelt“, **kurz START**. Danach koppeln und noch einmal kurz START | Ungekoppelt: kurze Vibration, die App bleibt auf dem Startbildschirm, kein Dienst. Gekoppelt: der Dienst beginnt wie gewohnt. (Eine nur im Web gelöschte Uhr hat noch Zugangsdaten und beginnt — gewollt, E-KG-30) | ungekoppelt erscheint die Hauptanzeige; gekoppelt beginnt kein Dienst |
| **P-KG-20** | Kein Dienst, nichts offen: Sync-Seite, **START halten**, „Kopplung trennen?“ mit Ja. Danach noch einmal START halten | Erst „Trenne…“, dann grün „Getrennt“, kein Code; die Uhr ist aus der Web-Geräteliste verschwunden. Das zweite Halten zeigt einen neuen Code | nach dem Ja erscheint sofort ein Code; „Getrennt“ fehlt; das Gerät steht noch im Web |
