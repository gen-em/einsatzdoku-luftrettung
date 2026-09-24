# Zweitfaktor der Prüfkonten

Seit Web 20.42.0 haben Support, Admin und BetreiberIn einen **Pflicht-
Zweitfaktor** (TOTP, P5c/AP5). Das Prüfkonto der Sandbox, `admin@gen-em.org`,
ist eine BetreiberIn — ohne Zweitfaktor landete jedes Werkzeug nach der
Anmeldung im Einrichtungstor, mit einem unbekannten nicht über den
Code-Schritt. **Anlass: F-P5c-33** (E-P5c-43).

**Kein Schalter, der die Pflicht abschaltet.** Das Prüfkonto hat einen echten
Zweitfaktor, nur mit einem Geheimnis, das die Rechner hier kennen. Die
Werkzeuge gehen durch denselben Code-Schritt wie ein Mensch.

## Die Dateien

| Datei | Wozu |
|---|---|
| `pruefkonto.php` | richtet einem Konto der Sandbox den Zweitfaktor mit dem Geheimnis der Sandbox ein, dazu zehn feste Wiederherstellungscodes (`PRFA2345` … `PRFK2345`). Läuft nur, wenn die Anlage auf `127.0.0.1` oder `localhost` zeigt. `lokal_einrichten.sh` ruft es in Schritt 6b |
| `totp.php` | der Rechner für PHP — `pruef_totp_naechster()`; von der Kommandozeile `php tools/zweitfaktor/totp.php` |
| `totp.mjs` | der Rechner für Node — `await naechsterCode()` |
| `totp.py` | der Rechner für Python — `naechster_code()`; von der Kommandozeile `python3 tools/zweitfaktor/totp.py` |

**Ein Rechner je Sprache, nicht einer je Werkzeug** (E-P5c-43). Alle drei
rechnen dasselbe — gegengeprüft am Prüfvektor aus RFC 6238 (Geheimnis
`12345678901234567890`, Zeitschritt 1 → `287082`) und untereinander am
Geheimnis der Sandbox.

## Das Geheimnis

`NADOKU_TOTP` (Base32) aus der Umgebung, sonst das der Sandbox
(`PRUEFSTANDZWEITFAKTORNADOKU23456`). Gegen Staging reicht die Kette das
Secret **`STAGING_TOTP`** durch (`auslieferung.yml`, Job `stufe2`,
`kreislauf.py --admin-totp`). Die BetreiberIn liest es beim Einrichten des
Prüfkontos auf Staging aus dem Einrichtungstor („Oder von Hand eintragen").

## Der Zähler — und warum es ihn gibt

Der Server nimmt **keinen Code zweimal** an: Er merkt sich den zuletzt
angenommenen Zeitschritt und nimmt nur einen späteren (E-P5c-54). Zwei
Anmeldungen im selben 30-Sekunden-Fenster mit demselben Code — die zweite
scheitert. Die Rechner führen deshalb einen gemeinsamen Zähler in
`$TMPDIR/nadoku-totp-<Kennung>.schritt` und nehmen den kleinsten Schritt über
dem zuletzt benutzten, frühestens den jetzigen, höchstens den nächsten. Liefe
er darüber hinaus, **warten** sie bis zum nächsten Fenster: Mehr als zwei
Anmeldungen je 30 Sekunden kosten also Zeit, nicht einen roten Lauf.

Gesperrt wird mit einer Sperrdatei (`O_EXCL`), nicht mit `flock` — Node kennt
kein `flock`, und alle drei Rechner müssen denselben Riegel sehen.

**Was der Zähler nicht weiß:** Anmeldungen, die an ihm vorbeigehen (ein
Mensch im Browser, ein anderer Rechner gegen Staging). Die Anmeldehilfen
wiederholen deshalb **einmal** mit dem nächsten Code, wenn der Code-Schritt
abweist.
