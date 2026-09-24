# Zweitfaktor der Prüfkonten

Richtet den Prüfkonten der Sandbox den Pflicht-Zweitfaktor mit bekanntem Geheimnis ein und rechnet die Codes (P5c/AP5, E-P5c-43).
**Anlass: Nr. 320** — ohne Zweitfaktor landete jedes Werkzeug nach der Anmeldung im Einrichtungstor (F-P5c-33).

## Aufruf

```bash
php tools/zweitfaktor/pruefkonto.php [adresse]   # Vorgabe admin@gen-em.org; lokal_einrichten.sh, Schritt 6b
php tools/zweitfaktor/totp.php                   # der nächste Code; ebenso python3 tools/zweitfaktor/totp.py
```

In Werkzeugen: `pruef_totp_naechster()` (PHP), `await naechsterCode()` (`totp.mjs`), `naechster_code()` (`totp.py`) — **ein Rechner je Sprache, nicht einer je Werkzeug**.

## Was es misst

Nichts — es stellt her. `pruefkonto.php` richtet einen **echten** Zweitfaktor ein, über dieselben Funktionen wie das Tor, dazu zehn feste Wiederherstellungscodes (`PRFA2345` … `PRFK2345`). **Kein Schalter schaltet die Pflicht ab:** Die Werkzeuge gehen durch denselben Code-Schritt wie ein Mensch.
Der Server nimmt **keinen Code zweimal** (E-P5c-54). Die Rechner teilen deshalb einen Zähler in `$TMPDIR/nadoku-totp-<Kennung>.schritt` (Sperrdatei mit `O_EXCL`, weil Node kein `flock` kennt) und nehmen den kleinsten Schritt über dem zuletzt benutzten — sonst **warten** sie bis zum nächsten Fenster.

## Was es braucht

`NADOKU_TOTP` (Base32) aus der Umgebung, sonst das Geheimnis der Sandbox (`PRUEFSTANDZWEITFAKTORNADOKU23456`).
Gegen Staging reicht die Kette das Secret **`STAGING_TOTP`** durch (`auslieferung.yml`, Job `stufe2`); die BetreiberIn liest es beim Einrichten des Prüfkontos auf Staging aus dem Einrichtungstor.
`pruefkonto.php` läuft nur, wenn die Anlage auf `127.0.0.1` oder `localhost` zeigt.

## Erwartete Zahl

Alle drei Rechner geben am Prüfvektor aus RFC 6238 (Geheimnis `12345678901234567890`, Zeitschritt 1) **`287082`** und untereinander am Geheimnis der Sandbox denselben Code; die RFC-Vektoren **6 / 6** misst die Zweitfaktorprobe.

## Was es nicht kann

Anmeldungen, die am Zähler vorbeigehen (ein Mensch im Browser, ein anderer Rechner gegen Staging), kennt es nicht — die Anmeldehilfen wiederholen deshalb **einmal** mit dem nächsten Code, wenn der Code-Schritt abweist.
Auf Staging richtet es nichts ein; dort trägt die BetreiberIn das Konto im Browser ein.
