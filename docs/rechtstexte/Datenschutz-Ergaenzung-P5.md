# Datenschutzerklärung — Ergänzungsbausteine für P5

> **Entwurf vom 16.09.2026, nicht anwaltlich geprüft.** Die geltende
> Datenschutzerklärung liegt in `rechtstexte` (Schlüssel `datenschutz`)
> und ist nicht Teil des Repositoriums; diese Bausteine werden dort
> **eingearbeitet**, nicht ersetzt. Jeder Baustein nennt, welche
> Festlegung ihn verlangt. Beim Einspielen `stand_am` neu setzen — die
> Nutzerinnen sehen dann den Hinweis „zur Kenntnis genommen" (E-P5b-05).

## B1 — Registrierung und Konto (E-P5b-01 bis -03, -13, -16)

Bei der Registrierung verarbeiten wir deine E-Mail-Adresse, deinen Namen
und ein Passwort (nur als Hash). Wir senden eine Bestätigungsmail;
unbestätigte Registrierungen werden nach 48 Stunden gelöscht. Ist die
Freischaltung durch den Betreiber eingestellt, bleibt die Registrierung
bis zur Freischaltung oder bis zum Ablauf der auf der Registrierungsseite
genannten Frist gespeichert und wird dann gelöscht; darüber informieren
wir per Mail. Adressen bekannter Wegwerfanbieter werden abgewiesen; die
dafür genutzte Liste liegt in der Anwendung, es findet keine Abfrage bei
Dritten statt. Bei einem Adresswechsel bleibt die alte Adresse bis zur
Bestätigung der neuen hinterlegt; an die alte geht ein Hinweis. Löschst du
dein Konto selbst, wird es sofort gesperrt und nach 30 Tagen endgültig
gelöscht; in dieser Frist kannst du die Löschung durch Anmelden widerrufen.

## B2 — Einwilligungen und Kenntnisnahmen (E-P5b-05)

Wir speichern zu deinem Konto, welche Fassung der Nutzungsbedingungen und
der Vereinbarung zur Auftragsverarbeitung du wann angenommen und welche
Fassung dieser Datenschutzerklärung du wann zur Kenntnis genommen hast
(Fassungsdatum und Zeitpunkt). Rechtsgrundlage: Art. 6 Abs. 1 lit. b und
c DSGVO (Vertrag, Nachweispflicht).

## B3 — Schutz vor Missbrauch: IP-Adressen und Sperren (E-P5a-04 bis -07, E-P5b-06, -13)

Bei Anmeldung, Registrierung, Passwort-Zurücksetzung und beim Empfang von
Gerätedaten zählen wir fehlgeschlagene Versuche je Konto, je IP-Adresse
und je Gerätekennung und sperren bei Häufung zeitweise (10 bis 60
Minuten, gestuft). Dafür verarbeiten wir deine IP-Adresse. **Sperren und
Sperrversuche** speichern wir mit IP-Adresse für **30 Tage**; danach werden
sie gelöscht. Über Sperren der höchsten Stufe wird der Betreiber per
Sammelmeldung informiert. Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO
(Sicherheit des Dienstes). IP-Adressen werden an keiner anderen Stelle
gespeichert.

## B4 — Betriebsprotokoll (V1, E-P5b-06, -12)

Die Anwendung führt ein Protokoll über **Betriebsereignisse**:
Verwaltungshandlungen (Konto angelegt, freigeschaltet, gesperrt, gelöscht;
Rolle oder Adresse geändert; Sicherung eingespielt; Wartung; Schlüsselblatt
bestätigt), versendete Systemmitteilungen (Empfänger, Betreff, Zustellung),
Wartungsläufe, Sicherungen, Fehler des Systems. Einträge zu
Verwaltungshandlungen werden [365] Tage aufbewahrt, alle übrigen 30 Tage.
**Wir führen kein Protokoll darüber, wer wann welchen Einsatz geöffnet,
gelesen oder exportiert hat.** Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO
(Nachvollziehbarkeit des Betriebs, Sicherheit), Art. 5 Abs. 2 DSGVO.

## B5 — Systemmitteilungen (E-P5a-14, E-P5b-02, -16)

Mitteilungen, die für den Betrieb deines Kontos nötig sind
(Bestätigungen, Sicherheits- und Mengenhinweise, Rückfragen zum
Wiederherstellungsschlüssel, Ankündigungen), senden wir an deine
hinterlegte Adresse über einen Mailserver bei [lima-city, Firmierung]
(Auftragsverarbeiter). Nicht zustellbare Mitteilungen werden bis zu 24
Stunden erneut versucht und danach mit Empfänger und Grund für 30 Tage
gespeichert. Werbung senden wir nicht.

## B6 — Mengen je Konto (E-P5b-04)

Wir messen je Konto die Zahl der Einsätze und den belegten Speicher, um
die Grenzen des Dienstes durchzusetzen, und zeigen dir beides auf deiner
Kontoseite. Ab 80 % der Grenze senden wir einen Hinweis.

## B7 — Rückfragen zum Wiederherstellungsschlüssel (E-P5b-09)

Wir speichern, wann wir dich zuletzt gefragt haben, ob du dein Notfallblatt
noch hast, und deine Antwort (Ja / Nein / später) — nicht den Schlüssel
selbst, den der Betreiber nie erhält. Erneuerst du den Schlüssel, geschieht
das in deinem Browser; der Server erhält nur den neu verpackten
Datenschlüssel.

## B8 — Gekoppelte Geräte (R37 (10), E-P5b-17)

Zu jedem gekoppelten Gerät speichern wir eine Gerätekennung, den Typ und
das Modell, den Zeitpunkt der letzten Verbindung und den Geräteschlüssel als
Hash. Gerätedaten (Zeiten, Positionen, Spuren) werden deinem Konto
zugeordnet.

## B9 — Sicherheitsberichte des Browsers (E-P5a-15)

Dein Browser kann Verstöße gegen die Inhaltssicherheitsrichtlinie (CSP)
an die Anwendung melden. Diese Berichte enthalten die betroffene Adresse
und den Regelverstoß, keine IP-Adresse und keine Inhalte; sie werden 30
Tage aufbewahrt.

## B10 — Sicherungen (E-P5a-03, S10)

Der Betreiber erstellt Sicherungen der gesamten Installation; sie sind mit
einem Serverschlüssel versiegelt und können auf auswärtige Sicherungsziele
(SFTP/FTPS) übertragen werden [Anbieter und Ort]. Verschlüsselte
Einsatzdaten bleiben in Sicherungen verschlüsselt. Nach der Löschung deines
Kontos verbleiben Daten in Sicherungen bis zu deren Überschreibung
[Aufbewahrungsregel].

## B11 — Auftragsverarbeiter

Hosting: dataforest [Firmierung, Ort]. Mailversand: lima-city
[Firmierung, Ort]. Mit beiden bestehen Verträge zur Auftragsverarbeitung.
Eine Übermittlung in Drittländer findet nicht statt.

---

*Prüfhinweise:* (a) B4 ist die Fortschreibung der Zusage aus `schema.sql`
und `Technik.md` (V1, 16.09.2026). (b) B3 nennt die Sperrleiter aus P5a;
Zahlen sind Vorgaben, bei anderer Einstellung des Betreibers anpassen.
(c) Rechtsgrundlagen sind Vorschläge. (d) Mit S11 ändert sich die Liste
der Klartextdaten — dann neue Fassung.
