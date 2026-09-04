# Vorbereitung Play Console — alles, was ohne Schlüssel und D-U-N-S geht

**Zu Rahmenplan Schritt 6, Teil C (R65).** Der Schritt ist am Auftraggeber
blockiert: Die D-U-N-S-Nummer beantragt sich noch, und der Signaturschlüssel
liegt außerhalb des Repositoriums. Dieses Dokument sammelt **alles andere** —
damit das Ausfüllen der Konsolenformulare später Abschreiben ist und kein
Nachdenken.

| | |
|---|---|
| Stand | 04.09.2026, erhoben am Zweig `claude/rahmenplan-schritt-6-ewm0kx` (Android 0.12.0); **drei Befunde daraus sind mit 0.13.0 behoben** (Abschnitt 8) |
| Erhoben aus | dem Repositorium allein — Quelltext beider Module, Manifeste, Bauskripte, `server/`, Rahmenplan, Konzept Planung v1.0 |
| Nicht erhoben | die Formulare der Play Console selbst. Was dort **wirklich** gefragt wird, sieht erst, wer davorsitzt. Dieses Dokument beantwortet die Fragen, die aus dem Code beantwortbar sind |

> **Jede Angabe unten ist am Code belegt, nicht geschätzt.** Wo etwas nicht
> belegbar war, steht das ausdrücklich da. Eine falsche Angabe im
> Datensicherheitsformular ist eine Falschangabe gegenüber Google **und**
> gegenüber den Nutzerinnen — hier ist Nichtwissen billiger als Raten.

---

## 1. Was nur **du** beantworten kannst

Das steht zuerst, weil es die Liste ist, an der es hängt. Alles Übrige in
diesem Dokument ist fertig.

### 1.1 Blockiert (bekannt, im Rahmenplan als Zuarbeit geführt)

| Was | Wofür |
|---|---|
| **D-U-N-S-Nummer** der Gen-EM GbR | Organisationskonto. Nur damit entfällt die 12-Tester-Pflicht über 14 Tage, die persönliche Konten trifft |
| **Signaturschlüssel** hochladen (Play App Signing) | Der vorhandene Schlüssel (RSA 4096, Alias `nadoku`, SHA-256 `078c…ad64`) wird **App-Signaturschlüssel**; ein **getrennter Upload-Schlüssel** wird erzeugt. Folge, die R65 festhält: der Schlüssel liegt danach auch bei Google |
| **DNS und TLS für `nadoku.gen-em.org`** | Die Adresse ist seit R63 **fest ins APK gebacken**. Daran hängen gleichzeitig: die Datenschutz-URL (Play prüft sie maschinell), die Kopplung und jeder Upload. Der Rahmenplan führt das als „fällig" |
| **Wear-OS-Uhr** | Für die Wear-Prüfrunde und den Installationstest aus dem Track. Bis heute ist **kein Teil der Android-Apps je auf echter Hardware gelaufen** |

### 1.2 Nicht blockiert, aber im Repositorium **nirgends entschieden**

Das ist der Teil, den die Bestandsaufnahme neu gefunden hat — er steht in
keiner Zuarbeitsliste:

| Frage | Warum sie kommt | Vorschlag |
|---|---|---|
| **„Zugriff auf die App"** (Anmeldedaten für die Google-Prüfung) | Die App ist **ohne NAdoku-Konto funktionslos**, und ein Konto legt ausschließlich eine Administratorin an — eine Selbstregistrierung gibt es nicht. Google kann die App also nicht prüfen, ohne dass du ihr ein Konto gibst | Ein eigenes Prüfkonto anlegen, dessen Zugangsdaten im Formular hinterlegen, und dazu schreiben, dass die Kopplung am Gerät beginnt |
| **Inhaltsbewertung (IARC-Fragebogen)**, Store-Kategorie, Zielgruppe | Pflicht für jeden Eintrag; im Repositorium steht dazu **nichts**, auch nicht als offene Zuarbeit | Aus dem Code beantwortbar sind Werbung (**keine**), In-App-Käufe (**keine**), Analyse (**keine**) — die übrigen Fragen sind deine |
| **Aufbewahrungsdauer der Daten** | Datensicherheitsformular und Datenschutzerklärung fragen danach. Aus dem Code lautet die Antwort **unbegrenzt**: Der tägliche Aufräumjob löscht nur Kopplungssitzungen, Sperrlisten, Ratenzähler und Passwort-Rücksetzungen; der Papierkorb hat 90 Tage (`TRASH_DAYS`), der aktive Bestand keine Frist | Entweder so angeben („unbegrenzt, Löschung auf Verlangen") oder eine Frist festlegen |
| **Sprache und Länder** des Eintrags | Beide Apps sind ausdrücklich **einsprachig deutsch** — es gibt kein einziges `values-<Sprache>`-Verzeichnis | Deutsch als Standardsprache, Länderauswahl deine Entscheidung |
| **Testerliste** | Im Repositorium steht dazu genau ein Satz, zweimal: „Testerliste = der bekannte Kreis". Es sind in Wahrheit **zwei** Listen: ein **Google-Konto** je Testperson (für die Einladung) **und** ein **NAdoku-Konto** (sonst ist die App funktionslos) | Namen und beide Kontoarten zusammenstellen. Der interne Track lässt bis 100 Tester zu |
| **Website- und Quellangabe** | Die Fußzeile nennt heute `github.com/gen-em/einsatzdoku-luftrettung`. R68 verlegt v1.0 nach `gen-em/nadoku` und **archiviert das alte** | Entweder die neue Adresse schon eintragen oder das Feld zunächst leer lassen |

### 1.3 Eine Entscheidung, die ich dir nicht abnehmen will

**Das App-Symbol wird in P7 ohnehin neu entworfen** (R70), und die
Vektorvorlagen tragen bis heute die alten Markenfarben. Wer jetzt ein
512×512-Store-Symbol schneidet, schneidet das, das planmäßig ersetzt wird.
Für einen **internen** Track ist das folgenlos — für die Produktionsfreigabe
nicht. Vorschlag: jetzt das vorhandene Symbol nehmen und den Austausch mit
P7 einplanen.

---

## 2. Kennungen und Zahlen — zum Abschreiben

| Feld | Wert | Beleg |
|---|---|---|
| Anwendungs-ID (beide Module) | `org.genem.nadoku` | `android/handy/build.gradle.kts:32`, `android/uhr/build.gradle.kts:29` |
| Anwendungs-ID der Prüf-Bauart | `org.genem.nadoku.pruef` | `applicationIdSuffix` |
| Sichtbarer Name, Handy | **Gen-EM NAdoku** | `handy/…/values/strings.xml` |
| Sichtbarer Name, Uhr | **NAdoku** | `uhr/…/values/strings.xml` |
| Berechtigungen der Uhr | **keine** (seit 0.13.0) | `uhr/src/main/AndroidManifest.xml` |
| `minSdk` Handy / Uhr | **26** (Android 8.0) / **30** (Wear OS 3) | Bauskripte |
| `targetSdk` / `compileSdk` | **36** (Android 16), beide Module | Bauskripte |
| Versionsname | **0.13.0**, eine Nummer für beide Module | `android/version.properties` |
| Versionscode Handy | **1300** — gerechnet: Haupt·10 000 + Neben·100 + Korrektur | `android/build.gradle.kts` |
| Versionscode Uhr | **1 001 300** — derselbe Wert **+ 1 000 000** (`UHR_VERSATZ`, Backlog Nr. 98) | ebd. |
| Verschleierung | `isMinifyEnabled = false` im Release | Bauskripte |

**Warum ein Versatz und nicht zwei Anwendungs-IDs:** Der Wear Data Layer
stellt Nachrichten **nur zwischen Apps gleichen Pakets und gleicher Signatur**
zu (E-S4-01). Bei verschiedenen IDs käme keine Nachricht von der Uhr an — und
zwar **ohne Fehlermeldung**. Play verlangt aber je hochgeladenem APK unter
einer ID einen eindeutigen Versionscode. Der Versatz löst genau diesen
Widerspruch, und er trifft nur das Modul, das ihn braucht.

**Beim ersten Track-Release soll die Fassung 1.0.0 sein** (Rahmenplan).

---

## 3. Das Datensicherheitsformular — ausgefüllt

Google fragt je Datenart: **erhoben? übertragen? verschlüsselt übertragen?
erforderlich oder optional? löschbar? geteilt?**

### 3.1 Standortdaten — *ja, erhoben und übertragen*

| Frage | Antwort |
|---|---|
| Genauigkeit | **Genauer Standort.** Die App meldet sich ausschließlich beim `GPS_PROVIDER` an; `ACCESS_COARSE_LOCATION` steht nur daneben, weil der Freigabedialog seit Android 12 sonst abgelehnt wird |
| Wann | **Durchgehend, solange ein Dienst läuft** — nicht nur während eines Einsatzes. Zeiten ohne Einsatz gehen als Ruhesegment mit vollständiger Spur an denselben Endpunkt |
| Im Hintergrund? | **`ACCESS_BACKGROUND_LOCATION` wird ausdrücklich nicht verlangt.** Die Aufzeichnung läuft in einem Vordergrunddienst (`foregroundServiceType="location"`), der sichtbar in der Leiste steht |
| Dichte | Ausgedünnt: ein Punkt, wenn seit dem letzten mindestens **15 m** oder **10 s** vergangen sind, nie öfter als einmal je Sekunde. Funde mit geschätztem Fehler über 100 m werden verworfen |
| Übertragen | Ja, an **einen einzigen Server** (`nadoku.gen-em.org`), über **HTTPS/TLS**. Getaktet alle 15 min, dazu sofort bei Phasenwechsel, Einsatzabschluss und Dienstende |
| Erforderlich? | **Erforderlich.** Es gibt keinen Schalter, mit dem sich die Aufzeichnung abwählen ließe — ohne Ortungsfreigabe zeichnet die App nichts auf und sagt das |
| Wessen Standort | **Der der Nutzerin selbst** — die Bewegung des Geräts, das die Notärztin im Dienst bei sich trägt |

> **Das gehört dazu und ist unbequem:** Über den Endpunkt der Spur und die
> Phasenkoordinaten fällt der **Einsatzort** mittelbar mit an. Die
> Patientenangaben (Diagnose, Alter, Adresse) sind Ende-zu-Ende-verschlüsselt
> — die Spur ist es **nicht**; sie liegt im Klartext auf dem Server. Das
> Projekt führt das selbst als offenen Befund (Backlog Nr. 43): „Der
> Einsatzort ist damit nominell geschützt und faktisch aus der Spur
> rekonstruierbar, die dort endet." Wer das Formular ausfüllt, sollte es
> wissen; ob es dort hineingehört, ist eine Frage an die Datenschutzerklärung.

### 3.2 App-Aktivität und Kennungen

| Datenart | Antwort |
|---|---|
| **Dienst- und Einsatzzeiten** (Beginn, Ende, Phasen 2–9, Strecke, Höhenmeter) | erhoben, übertragen, verschlüsselt, erforderlich |
| **Gerätekennung** | Übertragen wird `X-Device-Id` — eine **vom Server** bei der Kopplung vergebene Kennung (`dev-<32 Hex>`), **nicht aus dem Gerät abgeleitet** |
| **Dauerhafte Gerätekennungen** (ANDROID\_ID, IMEI, Seriennummer, MAC) | **Nein.** Mit `grep` über beide Module belegt: kein Aufruf von `Settings.Secure`, `TelephonyManager`, `Build.SERIAL`, `WifiManager` oder `BluetoothAdapter`. Die einzigen Treffer auf diese Wörter stehen in einem Kommentar, der ihre Abwesenheit begründet |
| **Werbe-ID (AAID/GAID), App-Set-ID** | **Nein.** Kein Treffer im ganzen Verzeichnis `android/` |
| **Geräteangaben bei der Kopplung** (einmalig) | Hersteller, Modell, Displaymaße, Android-Fassung, SDK-Stufe, App-Fassung. Der Server **speichert davon nur drei**: Art, aufgelöstes Modell, Rohangabe |
| **Namen, Kontaktdaten, E-Mail** | **Nein.** Die einzige personenbezogene Zeichenkette ist die vom Server gelieferte, **maskierte** Kontoadresse (`ph***@gen-em.org`) im Kopplungsdialog — nur angezeigt, nirgends gespeichert |
| **Gesundheitsdaten** | **Nein — an beiden Enden belegt.** Der Nachrichtenkörper der App kennt Diagnose, Alter und Einsatzort nicht, und `ingest.php` liest sie nicht aus dem Rumpf. Sie liegen ausschließlich im Ende-zu-Ende-verschlüsselten `pat_blob`, der im Browser gebaut wird. Auch die Reanimationsdokumentation sendet das Handy **nicht** — sie bleibt bei der Garmin-Uhr |
| **Fotos, Dateien, Mikrofon, Kalender, Kontakte** | **Nein**, keine Berechtigung dafür |

### 3.3 Weitergabe an Dritte — *nein*

**Keine Analyse-, Absturz- oder Werbebibliothek.** Ein `grep` über beide
Module und alle Bauskripte nach firebase, crashlytics, analytics, sentry,
admob, facebook, appcenter, bugsnag, mixpanel, amplitude, matomo, maps,
osmdroid liefert **keinen einzigen Treffer**. Die App lädt zur Laufzeit
nichts nach.

Zur Laufzeit stecken genau zwei Blöcke im APK: **AndroidX/Compose**
(Apache-2.0) und **`play-services-wearable`** (proprietär) — Letzteres
ausschließlich für den Wear Data Layer, der Nachrichten **zwischen den beiden
Apps auf demselben Gerätepaar** zustellt, nicht ins Netz. HTTP, JSON und
Datenbank laufen über Android-Bordmittel.

### 3.4 Verschlüsselung bei der Übertragung — *ja*

HTTPS/TLS über `HttpURLConnection`. **Das Schema wird nicht aus einer Eingabe
übernommen**, sondern aus dem Rechnernamen abgeleitet: Für jeden echten
Rechnernamen entsteht `https://`; ein eingetragenes `http://` wird
umgeschrieben. Ein TLS-Fehler bekommt einen eigenen Fehlerzweig und führt
**nicht** zu einem Klartext-Rückfall.

**Die eine Klartext-Ausnahme betrifft das Release-APK nicht:** Sie liegt
unter `src/debug/` und geht damit nur in die Prüf-Bauart
`org.genem.nadoku.pruef` ein — Klartext für `127.0.0.1`, `10.0.2.2` und
`localhost`.

### 3.5 Löschung — *möglich, aber nicht in Selbstbedienung*

| Weg | Was er tut |
|---|---|
| In der App: **„Gerät trennen"** | Löscht die Kopplung. Lokal **immer** (die Tresordatei wird gelöscht, auch ohne Serverantwort), serverseitig wird das Gerät **gelöscht**, nicht deaktiviert. Bereits hochgeladene Daten bleiben — das steht wörtlich im Bedientext |
| Im Web: Einsätze und Diensttage | Löscht die Nutzerin selbst; Papierkorb mit 90 Tagen Frist, „Endgültig löschen" jederzeit möglich und unwiderruflich |
| **Konto samt allem** | **Nur eine Administratorin.** Es gibt keine Selbstbedienung |

Das letzte ist für das Formular relevant: Google fragt, ob Nutzer die
Löschung ihrer Daten **verlangen** können. Die Antwort ist ja — über die
Administratorin, nicht über einen Knopf in der App.

### 3.6 Was lokal auf dem Gerät liegt

Drei Ablagen, alle app-privat: **`tresor.bin`** (Gerätekennung und
Schlüssel, AES-256-GCM, Schlüssel im AndroidKeyStore erzeugt und **nicht
exportierbar**), **`puffer.db`** (SQLite mit Diensten, Paketen, Spurpunkten,
Phasen — unverschlüsselt) und **SharedPreferences** (ausdrücklich nur
Nicht-Geheimes).

**Nichts davon wandert in eine Google-Sicherung.** Beide Module setzen
`allowBackup="false"` und `fullBackupContent="false"`, und die
Auszugsregeln schließen für Cloud-Sicherung **und** Geräteübertragung
`root`, `database`, `sharedpref` und `file` aus.

**Im Logbuch (logcat) stehen keine Koordinaten und keine Schlüssel.** Die
`Log.*`-Aufrufe protokollieren Zahlen und Zustände; `Zugangsdaten.toString()`
ist ausdrücklich überschrieben, damit der Schlüssel nicht beiläufig in eine
Zeile gerät.

---

## 4. Berechtigungen und Deklarationen

### 4.1 Die acht des Handys

`INTERNET` · `ACCESS_NETWORK_STATE` · `ACCESS_FINE_LOCATION` ·
`ACCESS_COARSE_LOCATION` · `FOREGROUND_SERVICE` ·
`FOREGROUND_SERVICE_LOCATION` · `POST_NOTIFICATIONS` ·
`RECEIVE_BOOT_COMPLETED`

Dazu `uses-feature android.hardware.location.gps` mit `required="false"`.

**Ausdrücklich nicht dabei** — und das ist die stärkste Aussage, die man vor
einem Prüfer machen kann, weil sie begründet ist:

| Fehlt | Begründung im Manifest |
|---|---|
| `ACCESS_BACKGROUND_LOCATION` | Ein Vordergrunddienst vom Typ `location`, der sichtbar in der Leiste steht, braucht sie nicht — und sie ist die Freigabe, für die Android einen eigenen Bildschirm mit Warnung zeigt |
| `CAMERA` | Mit R63 gestrichen. Sie diente nur dem Kopplungs-QR; der Code trug die Serveradresse, und die App kennt jetzt genau eine |
| `REQUEST_IGNORE_BATTERY_OPTIMIZATIONS` | **Verstößt gegen die Inhaltsrichtlinie des Play Store**, die dafür nur wenige benannte Fälle zulässt (Wecker, VoIP, Gerätesuche). Die App führt stattdessen zur allgemeinen Liste |
| Speicher, Telefon, Kontakte, Mikrofon | nie deklariert |

**Beim Bauen kommt nichts dazu** außer einer selbst erzeugten
Signatur-Berechtigung (`…DYNAMIC_RECEIVER_NOT_EXPORTED_PERMISSION`,
`protectionLevel="signature"`, von AndroidX). Eine Signatur-Berechtigung mit
eigenem Paketpräfix ist nichts, was in der Konsole erklärt wird.

### 4.2 Die eine der Uhr

**`WAKE_LOCK` — und sonst nichts.** Kein `INTERNET`, keine Ortung, keine
Kamera. Der Data Layer braucht keine eigene Berechtigung.

> ✅ **Erledigt mit Android 0.13.0: `WAKE_LOCK` ist gestrichen.** Sie war im
> Quelltext unbenutzt — kein Aufruf von `PowerManager` oder `newWakeLock` im
> ganzen Modul. **Die Uhr verlangt damit gar keine Berechtigung mehr**, und
> das ist im Store-Eintrag die stärkste Aussage, die eine App über sich
> machen kann. Der Vermerk im Manifest sagt, warum sie weg ist und unter
> welcher Bedingung sie zurückkäme.

### 4.3 Die Deklaration des Vordergrunddienstes

Google verlangt für `FOREGROUND_SERVICE_LOCATION` **zwei** Dinge: einen
geschriebenen Text und ein Demo-Video vom echten Gerät. Der Rahmenplan nennt
bisher nur das Video.

**Textvorschlag** (aus dem Code abgeleitet, nicht erfunden):

> Die App dokumentiert Notarzteinsätze. Während eines Dienstes zeichnet sie
> durchgehend die GPS-Spur des Fahrzeugs auf — auch zwischen den Einsätzen,
> weil die Ruhezeiten Teil der Dokumentation sind. Der Dienst läuft über
> zwölf Stunden, während das Gerät in der Tasche liegt und der Bildschirm
> aus ist; ohne Vordergrunddienst bricht die Aufzeichnung ab, und was fehlt,
> fehlt unwiederbringlich — ein Dienst lässt sich nicht nachfahren. Die
> Aufzeichnung beginnt und endet ausschließlich auf ausdrückliche Handlung
> der Nutzerin („Dienst beginnen" / „Dienst beenden"); solange sie läuft,
> steht eine dauerhafte Benachrichtigung in der Leiste. Eine
> Hintergrundortung wird nicht verlangt.

**Drehbuch für das Demo-Video** (der Ablauf ist so im Code):

1. App öffnen, Zustand „gekoppelt" zeigen.
2. **„Dienst beginnen"** drücken — der Freigabedialog für den genauen
   Standort erscheint, Freigabe erteilen.
3. Die **dauerhafte Benachrichtigung** in der Leiste zeigen (Bildschirm von
   oben herunterziehen): Sie nennt den laufenden Dienst und trägt den Knopf
   „Dienst beenden".
4. Bildschirm sperren, kurz warten, wieder entsperren — die Meldung steht
   noch, die Aufzeichnung läuft.
5. Einen **Einsatz** beginnen und eine Phase setzen, damit sichtbar ist,
   wozu die Spur dient.
6. **„Dienst beenden"** — die Meldung verschwindet, die Aufzeichnung endet.

Punkt 4 ist der, auf den es ankommt: Er zeigt, dass der Dienst weiterläuft,
und Punkt 3 zeigt, dass er dabei sichtbar bleibt.

---

## 5. Der Store-Eintrag

### 5.1 Was da ist

| Baustein | Stand |
|---|---|
| App-Symbol (adaptiv, im APK) | vorhanden — für ein **512×512-Store-Symbol** muss daraus eines geschnitten werden |
| Grafik **1024×500** | **nicht vorhanden** |
| Screenshots | fünf Handy-Abzüge vom Emulator (1080×2340) unter `docs/bilder/s4-rest/` — davon zeigen **vier den Kopplungsweg**, samt Kopplungscode und maskierter Kontoadresse. Als Store-Bilder taugen sie so nicht |
| Wear-OS-Screenshots | **keine** |
| Texte für Kurz- und Langbeschreibung | **keine fertigen.** Grundlage: `README.md`, `docs/Handbuch.md`, `android/LIESMICH.md` |
| Datenschutz-URL | `https://nadoku.gen-em.org/datenschutz.php` — **erreichbar, sobald DNS und TLS stehen und der Text eingetragen ist.** Die Anwendung liefert keinen Text mit; Impressum und Datenschutzerklärung sind Betreibertexte, die unter Einstellungen → Rechtstexte eingetragen werden. **Seit Android 0.13.0 verweist die App selbst darauf** (Einstellungen → Rechtliches) |

**Für Screenshots braucht es einen Lauf mit Beispieldaten**, nicht den
Kopplungsweg: Dienstansicht mit laufendem Dienst, Einsatzansicht mit Phasen,
die Uhr-Ansicht. Das ist mit dem Emulator herstellbar
(`android/werkzeuge/emulator.sh`).

### 5.2 Fragen, die aus dem Code beantwortet sind

| Frage der Konsole | Antwort |
|---|---|
| Enthält die App Werbung? | **Nein** |
| In-App-Käufe? | **Nein** |
| Nutzt sie Analyse-SDKs? | **Nein** |
| Lädt sie zur Laufzeit Code nach? | **Nein** |
| Gibt es Texteingabefelder (nutzergenerierte Inhalte)? | **Nein** — es gibt in beiden Modulen kein einziges benutztes Eingabefeld mehr |

---

## 6. Wear OS

| Frage | Antwort |
|---|---|
| Ein Eintrag oder zwei? | **Einer.** Wear OS ist darin ein eigener Formfaktor mit eigenem Track — so sagt es das Konzept Planung v1.0 (E-PV-1). **Diese Aussage steht dort ohne Quelle**; sie ist im Repositorium nicht belegt und sollte beim Einrichten nachgeprüft werden, bevor der erste Upload läuft |
| Kennzeichnung als Uhr-App | `uses-feature android.hardware.type.watch required="true"` und `meta-data com.google.android.wearable.standalone = false` — die App ist ausdrücklich **keine eigenständige** Uhr-App, sie braucht das Handy |
| Was die Uhr an das Handy schickt | `uhr` (zufällige App-Installationskennung), `nr`, `art`, `zeit`, optional `phase` und `einsatz_ref`. **Kein `api_key`, keine `device_id`, keine Serveradresse** — ein Prüffall zählt die Schlüssel nach, statt es zu behaupten |
| Verlassen Daten der Uhr das Gerät? | **Ja — zum gekoppelten Handy**, nicht ins Netz. Für das Datensicherheitsformular des Wear-Teils ist das die genaue Formulierung: Die Uhr überträgt Dienstereignisse an die Begleit-App, und diese überträgt an den Server |
| Was die Uhr lokal speichert | Eine Datei mit dem Ereignispuffer: Uhr-Kennung, Ereignisnummer, „quittiert bis", Kennungszähler, unquittierte Nachrichten. Keine Datenbank, kein Geheimnis |
| Zusätzliche Prüfung | Google prüft den Wear-Teil gegen eigene Qualitätsrichtlinien; das Konzept rechnet ausdrücklich **„mit einer Ablehnungsrunde"**. Die Richtlinien selbst stehen nicht im Repositorium |

---

## 7. Reihenfolge

1. **D-U-N-S** → Organisationskonto einrichten.
2. **DNS und TLS** für `nadoku.gen-em.org` → Datenschutztext eintragen →
   URL prüfen. *(Ohne das schlägt die maschinelle Prüfung der
   Datenschutz-URL fehl, und die App kann sich ohnehin nicht koppeln.)*
3. **Play App Signing**: vorhandenen Schlüssel als App-Signaturschlüssel
   hochladen, Upload-Schlüssel erzeugen und außerhalb des Repositoriums
   verwahren.
4. **Eintrag anlegen**: Kennungen aus Abschnitt 2, Texte, Symbol,
   Screenshots.
5. **Datensicherheitsformular** aus Abschnitt 3 ausfüllen.
6. **Inhaltsbewertung**, Zielgruppe, Kategorie, „Zugriff auf die App"
   (Abschnitt 1.2).
7. **Vordergrunddienst-Deklaration** aus Abschnitt 4.3, Video nach dem
   Drehbuch.
8. **App Bundle bauen** — *das ist noch nie geschehen*, siehe Abschnitt 8.
9. **Internen Track** anlegen, Tester einladen, hochladen.
10. **Abnahme**: Installation beider Apps aus dem Track auf dem S24 und
    einer Wear-OS-Uhr; Update von der Seitenladungs-Fassung auf die
    Track-Fassung **ohne Neuinstallation** (gleiche Signatur).

---

## 8. Befunde aus der Erhebung

Fünf Dinge, die dabei aufgefallen sind und vorher niemand aufgeschrieben hatte:

1. **Es wurde noch nie ein App Bundle gebaut.** R67 verweist auf „die
   Anleitung in `android/LIESMICH.md`" — dort steht zur Auslieferung nur der
   Abschnitt über den Signaturschlüssel. Play nimmt für neue Apps **kein
   APK** mehr, sondern ein **AAB**. Das ist ein `./gradlew bundleRelease`,
   den es so noch nie gab; er sollte **vor** dem ersten Upload einmal laufen.
2. ~~`WAKE_LOCK` der Uhr ist unbenutzt.~~ **Erledigt (Android 0.13.0):
   gestrichen.** Die Uhr verlangt keine Berechtigung mehr.
3. **Der Server verschickt bei Kopplung und Trennung E-Mails**, die die
   Gerätebezeichnung tragen. Das ist eine Datenverarbeitung, die in die
   Datenschutzerklärung gehört — im Datensicherheitsformular ist sie nicht
   gefragt (sie geschieht auf dem Server, nicht in der App). **Vermerkt als
   Zuarbeit im Rahmenplan, Abschnitt 6**, damit es beim Schreiben des Textes
   nicht durchrutscht. Die Mails bleiben: Sie sind die einzige Stelle, an der
   eine unbemerkte Fremdkopplung auffiele.
4. ~~Umleitungen werden nicht abgeschaltet.~~ **Erledigt (Android 0.13.0):
   `instanceFollowRedirects = false`.** Eine Umleitung gilt seither als
   Serverfehler statt als „unbekannt". Der Sendeweg war nie betroffen —
   nachgesehen, nicht angenommen: `Sendeantwort.lese()` behandelt alles außer
   200/400/401/413 als „später erneut".
5. ~~Die App führt zu keinem Rechtstext.~~ **Erledigt (Android 0.13.0):**
   Unter den Einstellungen steht die Karte „Rechtliches" mit Verweisen auf
   Datenschutzerklärung und Impressum. Beide öffnen den Browser; ein WebView
   bekommt diese App nicht.

---

## 9. Prüfprotokoll der Umsetzung (Android 0.13.0)

Vier der fünf Befunde aus Abschnitt 8 sind umgesetzt (2, 3, 4, 5); Befund 1
bleibt offen, weil er einen Signaturschlüssel braucht.

### 9.1 Was nicht geprüft werden konnte, und warum

- **Der Rechtstext selbst im Browser.** Der Sprung dorthin ist belegt (9.3),
  die **Darstellung** der Seite nicht: Der einzige Browser des Prüfabbilds
  ist `org.chromium.webview_shell` (WebView Browser Tester 113.0.5672.136),
  und der antwortet unter QEMU-TCG nicht mehr — `WebView Shell isn't
  responding`, zweimal gemessen, auch nach „Wait" und 45 s Wartezeit. Das
  ist eine Eigenschaft des Prüfstands (ein einziger emulierter Kern rechnet
  Chromium), nicht der App: Die Adresse steht in der Adressleiste, und der
  Server hat geantwortet. Wo es zu prüfen ist: auf einem echten Gerät
  (Backlog Nr. 81), zusammen mit dem übrigen Gerätetest.
- **Das App Bundle.** `./gradlew bundleRelease` ist nie gelaufen und läuft
  auch hier nicht — er braucht den Schlüssel aus `android/signatur.properties`
  (Befund 1).
- **Die Uhr im Emulator.** Die Änderung an der Uhr ist eine **gestrichene
  Zeile im Manifest**; sichtbar ist daran nichts. Belegt ist sie durch den
  Bau (`:uhr:lint` — *No issues found*) und durch das Manifest selbst.

### 9.2 Maschinell geprüft

| Mittel | Zahl |
|---|---|
| `./gradlew --offline build` (beide Module) | **BUILD SUCCESSFUL in 4 m 17 s** |
| Lint Handy | **0 Fehler**, 13 Warnungen |
| Lint Uhr | **0 Fehler**, 0 Warnungen |
| Prüffälle Handy | **494**, davon **0 Fehlschläge**, **0 übersprungen** — mit `-Pnadoku.rundlauf=http://127.0.0.1:8080/` gegen die laufende örtliche Installation, also inklusive der 28 Rundlauffälle, die sonst übersprungen werden (16 Kopplung, 6 Senden, 6 Einsatz). Genau sie gehen durch den geänderten `HttpNetzweg` |
| Prüffälle Uhr | **142**, davon **0 Fehlschläge**, **0 übersprungen** |
| `./gradlew --offline projects` | `NAdoku Android 0.13.0 (Versionscode Handy 1300, Uhr 1001300)` |
| `tools/wortliste/` | **0 Treffer / 0 ungenutzte Ausnahmen** bei 79 Regeln, alle fünf Bereiche (a–e) |

### 9.3 Im Emulator geprüft (Stufe II)

Abbild `system-images;android-34;google_apis;x86_64`, AVD `handy34`, ohne KVM
(`-accel off`). Boot **621 s**, Aufspielen des Handy-APK **60 s**. Gebaut
gegen die örtliche Installation
(`-Pnadoku.serverBasis=http://127.0.0.1:8080/`, `adb reverse tcp:8080
tcp:8080`).

| Schritt | Ergebnis |
|---|---|
| Kopplung gestartet | `pair_sessions` Nr. 60, Code `Y7B3Q5`, `geraet_art = handy`, `geraet_modell = unknown Android SDK built for x86_64` |
| Code im Web bestätigt | 302 auf `einstellungen.php?t=geraete#koppeln` |
| Am Gerät mit „Ja, koppeln" bestätigt | `devices` Nr. 68, `geraet_art = handy`, `geraet_modell = unknown Android SDK built for x86_64` — die Momentaufnahme aus R64/AP1, am laufenden Gerät entstanden |
| Einstellungen geöffnet | Karte **„Rechtliches"** mit beiden Knöpfen und Hinweistext, darunter unverändert `Fassung 0.13.0-pruef` und „Zurück" — Beleg: `android/mockups/S4-handy-emulator-0.13.0-rechtliches.png` |
| „Datenschutzerklärung" gedrückt | `org.chromium.webview_shell` kam nach vorn, Adressleiste `http://127.0.0.1:8080/datenschu…`, Server: `[200]: GET /datenschutz.php` um 12:56:55 |
| „Impressum" gedrückt | Server: `[200]: GET /impressum.php` um 13:00:58 |

Der Sprung in den Browser ist damit an **drei** unabhängigen Stellen belegt:
am Fensterwechsel des Systems, an der Adressleiste und an der Antwort des
Servers. Dass die Seite danach nicht rendert, steht in 9.1.

### 9.4 Was der Emulatorlauf nebenbei mitgeprüft hat

Kopplung **und** Trennung laufen über `Netzweg` — also über die Datei, in der
`instanceFollowRedirects = false` neu steht (Befund 4). Beide Wege wurden im
Lauf gegangen: die Kopplung in 9.3, die Trennung am Ende („Gerät trennen" →
„Kopplung trennen?" → „Trennen"), Server `[200]: POST /pair.php` um 13:04:29,
danach ist `devices` Nr. 68 fort und das Konto steht wieder bei zwei Geräten.
Die Änderung hat den Kopplungsweg also nicht gebrochen.

Nebenbei ist damit auch **Befund 3** an einer Zahl belegt: Beim Trennen
schreibt der Server `SMTP connect: Connection refused` ins Protokoll — er
**versucht** die Mail. Auf dieser Prüfinstallation steht kein Mailserver;
auf einer echten geht sie hinaus, mit der Gerätebezeichnung darin.

---

## 10. Grenzen dieses Dokuments

- **Es kennt die Formulare nicht.** Was die Play Console 2026 wirklich fragt,
  steht nicht im Repositorium und war nicht abrufbar. Dieses Dokument
  beantwortet, was aus dem Code beantwortbar ist — die Zuordnung zu den
  tatsächlichen Formularfeldern macht, wer davorsitzt.
- **Es ersetzt keine Rechtsprüfung.** Die Datenschutzerklärung ist ein
  Betreibertext; hier stehen die technischen Tatsachen, aus denen sie
  entstehen kann.
- **Die Zahlen gelten für Android 0.13.0.** Steigt die Fassung, ändern sich
  Versionsname und -code; die Rechenregel bleibt.
