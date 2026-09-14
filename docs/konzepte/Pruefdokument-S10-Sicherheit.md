# Prüfdokument S10 — Sicherheit: Server-Anteil und Adminpakete

Geführt nach K9, von AP1 an mitgeführt: Was ist geprüft, mit welchem Mittel
und mit welcher **Zahl**; was konnte nicht geprüft werden und warum; welche
Funde sind aufgetreten; und als Kernstück die **Prüfliste für den
Auftraggeber** — alles, was nur auf der Installation geht.

Das Konzept liegt daneben (`Konzept-S10-Sicherheit.md`) und trägt den
Statusblock der Umsetzung. Dieses Dokument bleibt, bis seine Prüfliste
abgehakt ist (R62).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 14.09.2026 — **AP1 erledigt** (Web 19.7.0), AP2 als Nächstes. |
> | Geprüft | AP0: Containeraufbau · AP1: Anteilprobe, Endpunktprobe, Klickprobe, Kreisläufe, Bilderlauf, Wortliste, Linkprobe (Abschnitt 2) |
> | Offen | P-01 bis P-14 |
> | Fragen | keine |
> | Fehlerfunde | keine in der Anwendung; **ein Befund am Prüfstand** (F-S10-U-01, Abschnitt 4) |
> | Prüfumgebung | PHP **8.4.19** (CLI, NTS) · MariaDB **10.11.14** · Python **3.11.15** · Node **22.22.2** · Playwright **1.56.1** mit drei Engines: Chromium **141.0.7390.37**, Firefox **142.0.1**, WebKit **26.0** · lokale Installation über `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` (88 Einsätze, 16 Diensttage, 2 Geräte im Demo-Konto; `admin@gen-em.org` und `demo@gen-em.org` mit den Vorgabekennwörtern) |

---

## 0. Was **nicht** geprüft werden konnte, und warum

Das steht hier oben und nicht in einer Fußnote. *Von der umsetzenden Instanz
je Paket zu füllen.* Erwartbar sind mindestens diese Punkte — jeder mit dem
Grund und dem Weg, auf dem der Auftraggeber ihn nachholt:

- **Die echte `config.php` der Installation** (Beschreibbarkeit, OPcache-
  Verhalten des Hosters): nur auf luftrettung.net prüfbar → P-06.
- **Der Druck des Schlüsselblatts** auf Papier: nur am Drucker → P-07.
- **Versand gegen die echten Backup-Ziele** (FTPS-Zertifikat, SFTP-Hostschlüssel):
  nur mit den Zugängen der Installation → P-12.

**Stand nach AP0:** Nichts von dem, was AP0 tut, ist ungeprüft geblieben — es
ist Buchführung plus eine `tools/`-Änderung, und beides ist in Abschnitt 2 mit
Zahl belegt. Die drei Punkte oben bleiben unverändert stehen; sie hängen an
Paketen, die noch nicht gebaut sind.

**Stand nach AP1 — drei Dinge, die hier nicht geprüft werden konnten:**

1. **Der Browser benutzt den Anteil noch nicht.** Das ist kein Versäumnis,
   sondern der Zuschnitt von AP1: Es gibt in dieser Stufe keinen Weg, auf dem
   `unlock.js` eine `edka1:`-Hülle baut. Die Endpunktprobe schickt eine
   selbstgebaute — dass der *Browser* dasselbe tut, ist damit **nicht**
   gezeigt und wird es erst mit dem Umstellungslauf in AP2.
2. **Die Zustände `abweichend` und `Rotation` sind nur in der Probe
   hergestellt, nicht an der Oberfläche.** Wie die Meldung aussieht und ob sie
   überhaupt erscheint, misst AP2 (Meldungen) und AP3 (Karte, Statuszeile).
   Der Bilderlauf sieht beide Zustände grundsätzlich nicht — das steht in
   Abschnitt 3 und bleibt so.
3. **`config.php` der echten Installation.** Teil D der Anteilprobe schreibt
   gegen die `config.php` des Prüfstands. Ob sie auf luftrettung.net
   beschreibbar ist und wie sich der OPcache des Hosters verhält, ist nur dort
   zu sehen → **P-01** und **P-06**.

---

## 1. Kurzfassung

*Je Paket ein Absatz: was gebaut wurde, welche Stufe, was die Zahlen sagen.*

**AP0 — Ablage und Buchführung (keine Versionsstufe).** Konzept und
Prüfdokument liegen unter `docs/konzepte/`. Die Einträge aus Konzept-Abschnitt
8 sind in die **heute gültige** Fassung eingepflegt statt in die, gegen die das
Konzept gelesen wurde: Rahmenplan **Fassung 60** (Fahrplanzeile 9b, der Block
„Schritt 9b" mit den fünf Entscheidungen und der Abgrenzung, **fünf** Zeilen in
Abschnitt 6, eine Zeile in Abschnitt 10) und Backlog-Vermerke an **drei**
Nummern (46, 139, 155). Dabei berichtigt: Der Rahmenplan führte die
Mockup-Runde 9c noch als „zum Merge stehend" und nannte `main` bei Web 19.3.1;
gemessen steht `origin/main` auf `3886e26` mit **Web 19.6.0**.

**Dazu ein Befund am Prüfstand selbst** (F-S10-U-01): In einem frischen
Container startet **WebKit nicht**, obwohl die Engine im Abbild liegt — vier
Systembibliotheken fehlen. `tools/containeraufbau/` zieht sie nach und misst
seither nach, dass alle drei Engines starten (**3 von 3**). Ohne das wäre jeder
Dreimotorenlauf in S10 stillschweigend ein Zweimotorenlauf gewesen.

**AP1 — Grundlage Server (Web 19.7.0).** Der Server kann den Server-Anteil
lesen, je Konto per HMAC über die Kontonummer ableiten, seine Kennung rechnen
und die fünf Lagen aus E-S10-09 unterscheiden; `auth_guard.php` und
`ui_krypto_bootstrap()` liefern ihn an die angemeldete Sitzung, `pw_handling.php`
an das Konto des eingelösten Einmal-Tokens, und `api/kdf_upgrade.php` ist zur
Hüllenfassung geworden. **Der Browser benutzt davon noch nichts** — jede Hülle
bleibt `edk1:`, und eine bestehende Installation verhält sich nach dem Deploy
Zeile für Zeile wie unter 19.6.0. Deshalb Neben- und nicht Hauptnummer
(E-S10-U-01).

*Was die Zahlen sagen.* Die neue **Anteilprobe** stellt vier Lagen her, die im
Browser praktisch nicht herzustellen sind, misst und stellt zurück: **69 von
69**; `config.php` ist danach byte-gleich. Die **Endpunktprobe** fährt über
echtes HTTP mit angemeldeter Sitzung: **33 von 33**. Zwei Zahlen darin sind die
eigentlichen: Eine Hülle mit **fremder** Anteil-Kennung wird abgewiesen (der
Fehler, der sonst erst auffiele, wenn `kdf_anteil_alt` verschwindet), und der
**Rundlauf** liefert nach der Umstellung denselben Inhaltsschlüssel — wäre er
ein anderer, wären alle Daten des Kontos verloren. Die Regression ist über die
**Klickprobe** (43 von 43) und **beide Kreisläufe** (9120/0 und 287 687/0)
gemessen; beide fassen die Schlüsselkette von einem Ende zum anderen an.

*Zwei Stücke aus AP5 sind vorgezogen* (E-S10-U-03), weil die Probe sonst eine
Attrappe hätte bauen müssen und `sitzung.py` an der ersten `edka1:`-Hülle
gescheitert wäre. HKDF ist gegen Prüfvektor 1 aus RFC 5869 nachgerechnet.

---

## 2. Maschinelle Prüfungen (Mittel und Zahl)

| Paket | Mittel | Was gemessen wurde | Zahl |
|---|---|---|---|
| AP0 | `sh -n` | Syntaxfehler in `tools/containeraufbau/aufbau.sh` | **0** |
| AP0 | `aufbau.sh browser` | Playwright-Engines, die starten | **3 von 3** — Chromium 141.0.7390.37, Firefox 142.0.1, WebKit 26.0 |
| AP0 | dasselbe, Negativprobe (`PLAYWRIGHT_BROWSERS_PATH` auf ein leeres Verzeichnis) | gemeldete Engines / Rückgabewert | **0 von 3** / **1** |
| AP0 | `git fetch origin main` | Stand von `origin/main` | `3886e26` · Web **19.6.0** · Uhr **3.1.0** · Android **0.15.0** |
| AP1 | `php -l` | berührte Dateien | **0 Fehler in 9** |
| AP1 | `tools/anteilprobe/probe.php --schreiben` | Rechnungen, fünf Zustände aus E-S10-09, Schreibweg in `config.php` | **69 von 69**; Datei danach byte-gleich |
| AP1 | `tools/anteilprobe/endpunkt.py` | `api/kdf_upgrade.php` über echtes HTTP | **33 von 33** |
| AP1 | dieselbe, E2/E3 | Hülle mit fremder / ohne Anteil-Kennung wird abgewiesen | **2 von 2** (400 `anteil_kennung`) |
| AP1 | dieselbe, E6 | Umstellung bei `neu_iter === kdf_iter` wird angenommen | **1 von 1**; `pat_key_check` und `kdf_iter` unverändert |
| AP1 | dieselbe, E8 | Rundlauf: Inhaltsschlüssel vor / nach der Umstellung | **gleich** |
| AP1 | dieselbe, E10 | Demo-Konto: `KONTO_ANTEILE` null, Endpunkt `uebersprungen` | **6 von 6** |
| AP1 | Kennung von Hand gegengerechnet | `schluessel_kennung()` gegen SHA-256 über die Hexform | **2 Werte**, gleich |
| AP1 | HKDF gegen RFC 5869 Prüfvektor 1 | `hkdf_sha256()` in `krypto.py` | **1 von 1** |
| AP1 | frische Installation (`lokal_einrichten.sh`) | `install.php` trägt `server_key` **und** `kdf_anteil` | **1 Datei**, beide 64 Hex, verschieden, Zustand `bereit` |
| AP1 | `tools/klickprobe/probe.mjs` | Regression über alle Bedienwege (Chromium) | **43 von 43** |
| AP1 | Kreisläufe (R24) | csv / edbak, unerklärte Abweichungen | **9120/0** und **287 687/0** |
| AP1 | `tools/screenshots/` (5 Seiten × 8 Breiten) | Überlauf / Konsole / Knopfhöhe | 48 Bilder, **0/0/0** |
| AP1 | `tools/linkprobe/` | Verweise / unbekannte Abweichungen | **117 / 0** |
| AP2 | Browserlauf am Referenzbestand | Umstellung `edk1:` → `edka1:`, Aufrufe von `kdf_upgrade.php` beim zweiten Anmelden | / 0 |
| AP2 | HKDF-Dauer im Browser | ms je Ableitung, Gerät nennen | |
| AP2 | Bilderlauf | berührte Seiten × Breiten × Bedienhöhen, Überlauf / Konsole / Knopfhöhe | 0/0/0 |
| AP3 | Browserlauf | Anlegen, Nachtragen falsch/richtig, Rotation, Neuanfang | |
| AP3 | Kontraste | Paare, verfehlt | |
| AP4 | `unzip -p` | Teile mit `edsk1:` / Teile gesamt; `"email"`-Treffer im Rohtext | n/n · 0 |
| AP4 | Paketgröße | Fassung 3 gegen Fassung 2 am 5000er-Bestand, gzip+Siegel vs. Siegel | |
| AP4 | `tools/freigabeprobe/` | Chiffretext anders, Klartext gleich | |
| AP4 | `tools/versandprobe/` | FTPS, SFTP, `ftp`-Negativfall | |
| AP5 | Kreisläufe (R24) | csv und edbak, unerklärte Abweichungen | 0 / 0 |
| AP5 | `sitzung.py` | CK aus `edk1:`- und `edka1:`-Hülle | von 2 |
| AP5 | Wiederherstellungs-, Komplett-, Wartungsprobe | Erwartungen / nicht erfüllt | |
| alle | Wortliste | Treffer außerhalb Ausnahmen / ungenutzte Ausnahmen / Fallen | 0/0/0 |
| alle | Vollständigkeit | Befunde vorher → nachher, Unterschied erklärt | |
| AP6 | Linkprobe | Verweise, unbekannte Abweichungen | / 0 |

---

## 3. Grenzen der Prüfmittel

- Der Bilderlauf sieht keinen Zustand `abweichend` und keine Rotation — beides
  wird im Browserlauf hergestellt (Kennung in `app_state` verstellen, zweiten
  Anteil eintragen) und wieder zurückgestellt; die Zahl sagt, was hergestellt war.
- `sitzung.py` stellt nicht um; ein Konto, das nur über das Prüfmittel angemeldet
  war, bleibt `edk1:`. Der Umstellungslauf braucht einen echten Browser.
- Die Versandprobe prüft FTPS gegen ein Wegwerfzertifikat; dass `ext/ftp` es
  nicht prüft, ist der belegte Befund und kein Fehler des Laufs.
- **Die Anteilprobe stellt nicht um.** Sie baut die Hülle selbst und schickt
  sie an den Endpunkt. Dass `unlock.js` dasselbe tut — Hülle lesen,
  Datenschlüssel bilden, neu hüllen, absenden —, ist damit **nicht** gezeigt;
  das misst der Umstellungslauf in AP2 im echten Browser.
- **Der Server kann eine Hülle nicht öffnen**, also prüft er nur ihr Präfix.
  Ob in einer Hülle wirklich derselbe Inhaltsschlüssel steckt, sieht nur, wer
  sie öffnet. Deshalb misst die Endpunktprobe den Rundlauf (E8) und nicht
  bloß den Statuscode — eine grüne 200 allein wäre hier kein Beleg.

---

## 4. Fehlerfunde während der Umsetzung

*Je mit Beleg und Behebung (Konzept Abschnitt 6 hält die nicht-blockierenden).*

**F-S10-U-01 — WebKit lag im Abbild und startete nicht** (AP0, 14.09.2026,
behoben). *Befund:* Seit AP3b (Backlog Nr. 183) fahren Bilderlauf, Klickprobe
und Stilvergleich wahlweise drei Engines über `tools/motor.mjs`. In einem
frischen Container startete WebKit nicht:

```
chromium  OK  v141.0.7390.37
firefox   OK  v142.0.1
webkit    FEHLER: Host system is missing dependencies to run browsers
```

Es fehlten `libenchant-2-2`, `libsecret-1-0`, `libwayland-server0` und
`libmanette-0.2-0`. `tools/containeraufbau/aufbau.sh` installierte sie nicht,
und seine `LIESMICH.md` führte die Browser gar nicht unter „was fehlt" — der
Kopf sagte noch, der Container bringe „Chromium" mit, was stimmte, als er nur
Chromium fuhr.

*Warum das hierhergehört und nicht in den Backlog:* Es ist kein Fehler der
Anwendung, sondern einer, der **jede Prüfzahl von S10 unbemerkt halbiert
hätte**. Ein Lauf mit `--motor webkit` wäre abgebrochen, nachdem der
Bilderlauf zwei Engines lang gerechnet hat — oder er wäre gar nicht gefahren
worden. Beides meldet am Ende keine Null, sondern gar nichts (`CLAUDE.md` 6).

*Behebung:* Neuer Teil `browser` in `aufbau.sh`. Er installiert die vier
Pakete **und misst nach**: Er startet jede Engine einmal, nennt ihre Fassung
und bricht mit Rückgabewert 1 ab, wenn eine fehlt. Die Gegenprobe mit einem
leeren `PLAYWRIGHT_BROWSERS_PATH` meldet **0 von 3** und Rückgabewert **1**.
`playwright install` ist ausdrücklich **nicht** der Weg — es zöge eine zweite,
abweichende Fassung neben die des Abbilds; das steht als Absatz „Was es nicht
tut" in der `LIESMICH.md`.

---

## 5. Prüfliste für den Auftraggeber

Je Punkt: Bedienweg, erwartetes Ergebnis, **woran ein Scheitern zu erkennen ist**.
Vorher: Merge, **kein** `update.php` (S10 hat keine Migration). Reihenfolge einhalten —
P-01 erzeugt den Zustand, den die übrigen brauchen.

- [ ] **P-01 Anteil anlegen.** Betrieb → Servereinstellungen → Karte „Schlüssel
  des Servers“ → *Server-Anteil anlegen*. Erwartet: Karte zeigt Zustand „bereit“
  mit einer 8-stelligen Kennung; Betrieb → Status hat eine Zeile „Server-Anteil“
  in Blau „Übergang läuft“. **Scheitern:** rote Zeile, oder die Karte sagt
  „config.php nicht beschreibbar“ — dann die gezeigte Zeile von Hand eintragen
  und die Seite neu laden.
- [ ] **P-02 Schlüsselblatt.** Auf der Karte *Schlüsselblatt drucken*. Erwartet:
  eine Druckseite mit Serverschlüssel und Server-Anteil in Vierergruppen, je mit
  Kennung; die Kennungen stimmen mit der Karte überein. Zweimal drucken, zwei
  Orte. **Scheitern:** Kennung auf dem Blatt ≠ Kennung auf der Karte.
- [ ] **P-03 Umstellungslauf (eigenes Konto).** Abmelden, anmelden, eine Seite
  mit Patientendaten öffnen. Erwartet: kein Entsperrdialog, Daten lesbar; Status
  → „Server-Anteil“ zählt ein Konto mehr auf der neuen Hülle. **Scheitern:**
  Entsperrdialog direkt nach dem Anmelden, oder die Zählung bewegt sich nach dem
  zweiten Anmelden nicht.
- [ ] **P-04 Entsperren in neuem Tab.** Angemeldet einen Link in einem neuen Tab
  öffnen → Dialog → Kontopasswort. Erwartet: entsperrt. **Scheitern:** „Passwort
  falsch“ trotz richtigem Passwort.
- [ ] **P-05 Demo-Konto.** Als Demo anmelden, Daten ansehen; Status → Zeile nennt
  das Demo-Konto ausdrücklich als „bleibt ohne Anteil“. Erwartet: lesbar, keine
  Zählung als offen. **Scheitern:** Demo-Konto zählt als „noch alt“ oder
  Demo-Reset schlägt fehl.
- [ ] **P-06 Nachtragen mit falschem Wert.** Karte → *Nachtragen vom Blatt* →
  einen Wert mit einer vertauschten Ziffer einfügen. Erwartet: Meldung nennt
  die Kennung des eingegebenen und die erwartete; nichts geändert.
  **Scheitern:** Meldung „gespeichert“.
- [ ] **P-07 Falsche Kennung (der Ernstfall, absichtlich).** Nur auf Anweisung
  der umsetzenden Instanz mit genauem Rückweg: In `app_state` die
  `kdf_anteil_kennung` auf einen Fremdwert setzen. Erwartet: Status rot mit
  erwarteter Kennung; ein Konto mit neuer Hülle sieht nach dem Anmelden die
  Meldung „Server-Anteil fehlt oder ist nicht der …“ statt „Passwort falsch“;
  zurückstellen → alles wie vorher. **Scheitern:** „Passwort falsch“ oder eine
  leere Seite.
- [ ] **P-08 Reset über Wiederherstellungsschlüssel.** Für ein Probekonto
  „Passwort vergessen“ → Link → Wiederherstellungsschlüssel → neues Passwort.
  Erwartet: Anmeldung mit neuem Passwort, Daten lesbar. **Scheitern:** Daten
  gesperrt oder „Inhaltsschlüssel gehört nicht zu diesem Konto“.
- [ ] **P-09 Passwortwechsel.** Einstellungen → Passwort. Erwartet: neues Passwort
  gilt, Daten lesbar, andere Sitzungen beendet. **Scheitern:** Daten gesperrt.
- [ ] **P-10 Freigabeweg.** Verwaltung → Kontoseite → Paket freigeben → als
  Zielkonto einlösen. Erwartet: Einträge erscheinen, Klartext lesbar nach
  Eingabe des Wiederherstellungsschlüssels. **Scheitern:** „mit einem anderen
  Serverschlüssel gespeichert“ (dann ist der Serverschlüssel auf der
  Installation nicht der, mit dem gesichert wurde — P-06 hilft).
- [ ] **P-11 Adminpaket versiegelt.** Ein frisches Paket von der Kontoseite
  herunterladen, mit einem ZIP-Werkzeug öffnen, `manifest.json` ansehen.
  Erwartet: der Inhalt beginnt mit `edsk1:`; kein lesbarer Name, keine E-Mail.
  **Scheitern:** lesbares JSON.
- [ ] **P-12 Versand.** Einstellungen → Backup-Ziele. Erwartet: die
  Protokollauswahl bietet nur SFTP und FTPS; FTPS trägt den Hinweis zum
  Zertifikat; ein etwaiges `ftp`-Ziel trägt eine rote Plakette und wird beim
  nächsten Versand übersprungen (Ergebniszeile). *Verbindung prüfen* am echten
  Ziel grün. **Scheitern:** `ftp` wählbar, oder ein `ftp`-Ziel wird beschickt.
- [ ] **P-13 Rotation (Probe, nur wenn gewünscht).** Karte → *Server-Anteil
  wechseln* → Blatt neu drucken → anmelden. Erwartet: Status zählt neu/alt/edk1
  als drei Zahlen, das eigene Konto wandert nach dem Anmelden auf „neu“; „alten
  Anteil entfernen“ erscheint erst bei alt = 0. **Scheitern:** eine Zahl bewegt
  sich nicht, oder das Entfernen wird vorher angeboten.
- [ ] **P-14 Wiederanlaufpaket.** Runbook 7 lesen; das eigene Paket enthält
  jetzt vier Stücke (`config.php`, Serverschlüssel, Server-Anteil, Zugang zum
  Ziel). Erwartet: das Blatt liegt an beiden Orten. **Scheitern:** ein Ort.
