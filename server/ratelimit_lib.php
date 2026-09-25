<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Ratenschutz (Baustein B3).
 *
 * WARUM ES DIESE DATEI GIBT
 * Der bisherige Rateschutz bei der Anmeldung lag in der SITZUNG des
 * Aufrufers — wer das Cookie wegwirft, hat wieder fuenf Versuche frei. Das ist
 * kein Schutz, sondern eine Bequemlichkeitsbremse fuer versehentliche
 * Tippfehler. Die Bremse bei der Kopplung war eine feste Verzoegerung je
 * Anfrage; sie behindert parallele Anfragen ueberhaupt nicht.
 *
 * Diese Zaehlung liegt in der Datenbank und haengt an Kontokennung UND
 * IP-Adresse. Der Aufrufer kann sie nicht zuruecksetzen.
 *
 * ZWEI EIGENSCHAFTEN, DIE NICHT VERHANDELBAR SIND
 *
 *  1. Die Sperre greift, BEVOR eine teure Pruefung laeuft (bcrypt, PBKDF2).
 *     Sonst bleibt der Rechenaufwand als Angriffsflaeche fuer Ueberlastung
 *     offen: Wer gesperrt ist, kann den Server trotzdem rechnen lassen.
 *
 *  2. Die Antwortzeit bei Misserfolg ist konstant. Ein Zeitunterschied
 *     zwischen "Konto gibt es nicht" und "Passwort falsch" verraet dasselbe
 *     wie eine unterschiedliche Meldung, nur leiser.
 *
 * VERHALTEN, WENN DIE TABELLE FEHLT
 * Zwischen dem Aufspielen dieser Fassung und dem Lauf der Migration gibt es
 * ein Zeitfenster ohne Tabelle. In diesem Fenster laesst der Schutz durch und
 * schreibt eine Zeile ins Fehlerprotokoll. Die Gegenrichtung — Anmeldung fuer
 * alle sperren, bis jemand die Wartungsseite oeffnet — waere ein
 * selbstgebauter Ausfall.
 *
 * DASSELBE FENSTER GIBT ES JETZT FUER ZWEI SPALTEN (Web 20.10.0, P5a/AP6).
 * `stufe` und `stufe_bis` kommen mit einer Migration; zwischen Deploy und
 * `update.php` gibt es sie nicht. Deshalb steht die Leiter in einem ZWEITEN,
 * eigen gefangenen Statement: Die Grundzaehlung unten nennt die neuen Spalten
 * mit keinem Wort und laeuft in diesem Fenster unveraendert weiter. Naehme man
 * sie in das vorhandene INSERT auf, wuerde es dort werfen — und weil
 * `rate_misserfolg()` alles in EINEM try/catch faengt und still zurueckkehrt,
 * zaehlte der Ratenschutz gar nicht mehr. Fuer ALLE vierzehn Toepfe, nicht nur
 * fuer die Leiter, und ohne dass irgendetwas rot wuerde.
 *
 * ---------------------------------------------------------------------------
 * DIE SPERRLEITER (E-P5a-04, E-P5a-43)
 * ---------------------------------------------------------------------------
 *
 * Eine Sperre dauert nicht mehr fest, sondern nach STUFE: 0 heisst „nie
 * gesperrt gewesen", 1 bis 4 sind die Sprossen. Mit jeder Sperre steigt die
 * Stufe; 24 h ohne Fehlversuch setzen sie zurueck (`stufe_bis`).
 *
 * DIE ERSTE SPROSSE IST 15 MINUTEN UND NICHT 10, und das ist eine benannte
 * Abweichung vom Konzept (E-P5a-43). Das Konzept nennt 10/20/30/60 — und der
 * Topf `login` sperrt heute fest 900 s, also 15 Minuten. Mit 10 als erster
 * Sprosse waere der ERSTE Verstoss nach dem Update MILDER als davor, und ein
 * Sicherheitspaket, das eine Schranke senkt, ohne es zu sagen, ist genau die
 * Art Fehler, die niemandem auffaellt. Die Leiter ist ausserdem eine
 * Einstellung; wer 10 will, traegt 10 ein.
 *
 * NICHT JEDER TOPF BEKOMMT EINE LEITER. `login`, `login_ip`, `salt` und seit
 * P5a/AP7 `ingest` und `ingest_ip` — sonst keiner:
 *
 *   `reset` NICHT, obwohl das Konzept ihn nennt. Er sperrt heute 3600 s; jede
 *   Sprosse unterhalb der vierten waere SCHWAECHER als das. Dazu kommt das
 *   Schwerwiegendere: `reset_request.php` antwortet im gesperrten Fall
 *   wortgleich wie im erlaubten („falls es ein Konto gibt, ist eine Mail
 *   unterwegs"). Eine Leiter dort streckt ein Fenster, in dem jemand fuenfmal
 *   klickt, fuenfmal dieselbe Zusage liest und keine Mail bekommt.
 *
 *   Die Kopplungstoepfe (`pair`, `pair_start`, `pair_code`) NICHT.
 *   `demo`/`demog`/`testmail`/`csp` NICHT: Die zaehlen MENGE, nicht
 *   Fehlversuche — es gibt dort niemanden, der eskaliert.
 *
 * DIE TRENNLINIE STAND BIS WEB 20.11.0 FALSCH DA (E-P5a-48). Sie lautete: bei
 * den Kopplungstoepfen „steht dahinter ein Geraet, das nicht lesen kann, was
 * auf der Seite steht". Auf `ingest.php` trifft das woertlich genauso zu — die
 * Regel haette also gegen die Leiter entschieden, die AP7 dort einbaut. Sie
 * war nicht falsch gemeint, sondern falsch formuliert.
 *
 * DIE TRAGFAEHIGE TRENNLINIE IST: Unterbricht die laengere Sperre einen
 * Vorgang, der GERADE LAEUFT? Bei der Kopplung ja — jemand steht am Geraet
 * mit einem sechsstelligen Code, der in zehn Minuten verfaellt; eine Stunde
 * Sperre schreckt dort keinen Automaten ab, sie beendet die Kopplung fuer den
 * Menschen. Bei `ingest.php` nein: Die Daten liegen in der Warteschlange des
 * Geraets und kommen spaeter an. Die Sperre kostet den legitimen Fall nichts
 * als Zeit — und Zeit ist genau das, was sie den illegitimen kosten soll.
 *
 * UND SIE SPERRT KEIN REPARIERTES GERAET AUS: Die Abhilfe bei veraltetem
 * Schluessel ist Neukopplung, und die erzeugt eine NEUE Kennung (`pair.php`);
 * der alte Topf bleibt zurueck und laeuft ab.
 *
 * ---------------------------------------------------------------------------
 * DIE VERLANGSAMUNG (E-P5a-05)
 * ---------------------------------------------------------------------------
 *
 * Alle Fehlversuche der Installation zusammen bilden ein Fenster (Topf
 * `global`, Merkmal `alle`). Ab 200 je 15 min antwortet jede FEHLGESCHLAGENE
 * Anmeldung erst nach 1 s, ab 400 nach 2, ab 800 nach 4, ab 1600 nach 8.
 *
 * EINE GLOBALE SPERRE WAERE EIN SCHALTER, DEN JEDER VON AUSSEN UMLEGT. Eine
 * Verlangsamung ist es nicht: Wer das richtige Passwort hat, kommt durch.
 *
 * WO SIE ANSETZT — und das ist die Stelle, an der man sie falsch einbaut:
 * NICHT als `usleep()` vor der Antwort, sondern als erhoehte Mindestdauer
 * DURCH `rate_gleiche_dauer()`. Jene stellt die Antwortzeit des Fehlerzweigs
 * auf einen festen Wert; ein zusaetzliches Warten daneben zerstoerte genau
 * die Gleichheit, die sie herstellt (Eigenschaft 2 oben).
 *
 * WER SCHON GESPERRT IST, WIRD NICHT VERLANGSAMT. Das ist kein Entgegenkommen,
 * sondern Selbstschutz: Jede wartende Anfrage haelt einen PHP-Arbeitsprozess
 * und eine Datenbankverbindung. Bei 1600 Fehlversuchen je 15 Minuten und 8 s
 * Wartezeit warteten dauerhaft rund 14 Anfragen gleichzeitig — auf einem
 * Webspace mit zehn Arbeitern waere die Bremse die Ueberlastung, die sie
 * verhindern soll. Gesperrte Anfragen kosten nichts und werden sofort
 * abgewiesen; damit ist die Zahl der Wartenden durch die Sperrrate gedeckelt
 * und nicht durch die Flutrate. Verraten wird dabei nichts, was der Aufrufer
 * nicht ohnehin liest: Die Meldung sagt ihm, dass er gesperrt ist.
 */

/**
 * Grenzen je Anwendungsfall.
 *
 *   max     Versuche, bis gesperrt wird
 *   fenster Beobachtungszeitraum in Sekunden
 *   sperre  Dauer der Sperre in Sekunden
 *
 * Die Werte sind so gewaehlt, dass eine Person mit Tippfehlern sie im Alltag
 * nicht bemerkt, ein Durchprobieren aber aussichtslos wird. Beispiel Kopplung
 * (Topf `pair_code`, die Eingabe des Codes im Web): Sechs Zeichen aus einem
 * Alphabet von 32 sind 30 Bit, also rund 1,07 Milliarden Moeglichkeiten. Mit
 * 10 Versuchen je 10 Minuten und einer Gueltigkeit von 10 Minuten bleibt der
 * Coderaum praktisch unerreichbar — frueher war er mit 5 Zeichen, 60 Minuten
 * Gueltigkeit und ohne Ratenschutz in rund 1,4 Stunden vollstaendig
 * durchlaufbar.
 */
const RATE_GRENZEN = [
    /* `leiter => true` heisst: Die Sperrdauer kommt aus `rate_leiter()` und
     * steigt mit jeder Sperre (E-P5a-04). `sperre` bleibt trotzdem stehen —
     * als Rueckfall fuer das Fenster zwischen Deploy und Migrationslauf, in
     * dem es die Spalte `stufe` noch nicht gibt. */
    'login' => ['max' => 10, 'fenster' =>  900, 'sperre' =>  900, 'leiter' => true],
    'salt'  => ['max' => 30, 'fenster' =>  900, 'sperre' =>  900, 'leiter' => true],

    /* `reset` OHNE LEITER, obwohl das Konzept ihn nennt — Begruendung im Kopf
     * dieser Datei: Er sperrt heute eine Stunde, jede Sprosse unterhalb der
     * vierten waere schwaecher, und sein Scheitern ist absichtlich still. */
    'reset' => ['max' =>  5, 'fenster' => 3600, 'sperre' => 3600],

    /* DIE ANMELDUNG HAT ZWEI SCHWELLEN, UND DESHALB ZWEI TOEPFE (E-P5a-04).
     *
     * 10 Fehlversuche je Konto sind richtig — ein Mensch vertippt sich nicht
     * zehnmal. 10 je ADRESSE sind es nicht: Hinter einem Klinik-NAT teilen
     * sich zwanzig Leute eine IP, und die zehnte Vertipperin sperrt die
     * uebrigen neunzehn aus. Deshalb 50 je Adresse.
     *
     * WARUM ZWEI TOEPFE UND NICHT ZWEI ZAHLEN IN EINEM: Diese Tabelle haengt
     * am TOPF, nicht am Merkmal, und `rate_misserfolg()` bindet fuer alle
     * Merkmale eines Aufrufs dieselben Parameter. Zwei Grenzen in einem Topf
     * hiesse, die Schleife umzubauen — und damit die Bauart aufzugeben, die
     * hier zweimal ausgeschrieben begruendet ist (siehe unten bei
     * RATE_DEMO_GLOBAL). Der hauseigene Weg ist ein zweiter Topf mit
     * ausdruecklicher Merkmalsliste; `login` zaehlt dann nur noch das Konto.
     *
     * EINE FOLGE, die man leicht uebersieht: `login.php` muss bei einer
     * gelungenen Anmeldung BEIDE Toepfe leeren. Sonst laeuft eine Klinik-NAT
     * ueber den Tag in ihre 50 hinein, ohne dass irgendjemand etwas falsch
     * gemacht hat. */
    'login_ip' => ['max' => 50, 'fenster' => 900, 'sperre' => 900, 'leiter' => true],

    /* DIE MENGENBREMSE VON `ingest.php` (P5a/AP7, E-P5a-01/-02, -47, -48;
     * R19, Backlog Nr. 17).
     *
     * SIE WAR DIE LETZTE ASYMMETRIE: `ingest.php` war der einzige Endpunkt
     * ohne Ratenschutz — und zugleich der, an dem die Clients aus den Stores
     * haengen (E-R45-6 nennt genau diese Kombination als Flutungsgefahr).
     *
     * GEZAEHLT WERDEN NUR FEHLVERSUCHE (E-P5a-01 (1)). Ein gelungener Upload
     * geht nie auf das Kontingent — eine Uhr, die einen Dienst nachliefert,
     * darf beliebig viele Stuecke senden.
     *
     * 30 UND NICHT 10, UND DAS IST DIE WICHTIGSTE ZAHL DES PAKETS. Gemessen
     * am Referenzlauf (`tools/referenzdatensatz/einspielen/messprotokoll.json`,
     * Backlog Nr. 17): 612 Anfragen, Spitze 3 an einem einzigen Ausloeser,
     * 199 Abstaende von 0 s — ein Dienst kommt in STOESSEN, nicht gleichmaessig.
     * Der Stoss, der die Grenze bestimmt, ist aber ein anderer: ein
     * SCHLUESSELWECHSEL. Danach liegen die Pakete eines ganzen Dienstes in der
     * Warteschlange des Geraets und laufen der Reihe nach in die Abweisung;
     * 14 Teilstuecke in einem Paket sind gemessen
     * (`teilstuecke_je_paket.max`). 30 laesst zwei solche Stoesse durch, bevor
     * gesperrt wird. Wer die Zahl auf 10 senkt, sperrt Uhren im Dienst aus,
     * und zwar genau dann, wenn jemand gerade neu gekoppelt hat.
     *
     * ZWEI TOEPFE, WEIL ES ZWEI LAGEN SIND (E-P5a-01 (3)):
     *
     *   `ingest`     je GERAETEKENNUNG, wenn es die Kennung GIBT. Dahinter
     *                steht ein echtes Geraet mit einem veralteten Schluessel.
     *   `ingest_ip`  je ADRESSE, wenn es die Kennung NICHT gibt. Eine
     *                erfundene Kennung laesst sich beliebig oft neu erfinden
     *                — ein Zaehler je Kennung waere dort wertlos.
     *
     * BEIDE HABEN DIESELBE ZAHL, UND ZWAR ABSICHTLICH (E-P5a-47). Das Konzept
     * nannte 50 fuer die Adresse. Verschiedene Schwellen sind ein
     * EXISTENZORAKEL: Wer dieselbe geratene Kennung haemmert, bekaeme sie bei
     * existierender Kennung ab dem 31. Versuch abgewiesen, bei nicht
     * existierender erst ab dem 51. Genau diese Auskunft hat M4-07 weiter
     * unten in `ingest.php` mit einem Blindvergleich beseitigt; sie als
     * Zaehlunterschied wieder einzubauen waere ein Rueckschritt durch die
     * Hintertuer. Mit 30 gegen 30 kommt die Antwort beide Male beim 31.
     * Versuch und mit demselben Rumpf.
     *
     * DIE ABSENKUNG KOSTET KEINEN LEGITIMEN VERKEHR. In den Adresstopf zaehlen
     * ausschliesslich UNBEKANNTE Kennungen; ein gekoppeltes Geraet sendet nie
     * eine unbekannte. Der NAT-Einwand — ein Mobilfunkanbieter teilt EINE
     * Adresse unter Tausenden auf — trifft deshalb nur Geraete, deren Eintrag
     * im Web GELOESCHT wurde (R47) und die weitersenden; die sollen aufhoeren.
     *
     * WAS BLEIBT, STEHT IN E-P5a-47 und wird nicht beschoenigt: Eine
     * zweistufige Probe unterscheidet weiterhin. Sie zu schliessen hiesse,
     * auch Fehlversuche bekannter Kennungen in den Adresstopf zu zaehlen —
     * dann sperrt ein einziges Geraet mit veraltetem Schluessel seine ganze
     * Adresse, einschliesslich des soeben neu gekoppelten Geraets, das die
     * Abhilfe ist. */
    'ingest'    => ['max' => 30, 'fenster' => 900, 'sperre' => 900, 'leiter' => true],
    'ingest_ip' => ['max' => 30, 'fenster' => 900, 'sperre' => 900, 'leiter' => true],

    /* DIE BETREIBER-RUECKFRAGE ZUM SCHLUESSELBLATT (P5b/AP9, E-P5b-10, -21).
     *
     * DREI VERSUCHE, NICHT ZEHN. Die Anmeldung laesst zehn zu, weil ein
     * Passwort getippt wird und Tippfehler dazugehoeren. Hier wird von einem
     * BLATT ABGELESEN, vier Gruppen zu vier Zeichen, Gross/Klein und
     * Leerzeichen sind egal. Wer dreimal danebenliegt, hat das Blatt nicht
     * vor sich — und genau das soll die Frage herausfinden.
     *
     * JE KONTO UND NICHT JE ADRESSE. Es gibt in einer Installation eine
     * Handvoll BetreiberInnen; ein NAT-Problem wie bei `login_ip` kann hier
     * nicht entstehen. Der Zaehler haengt an der Kontoadresse, damit eine
     * gesperrte BetreiberIn die andere nicht mitsperrt — sie soll nachsehen
     * koennen, wo das Blatt liegt.
     *
     * MIT LEITER, UND DIE LEITER BESTIMMT DIE DAUER — nicht die Zahl in
     * `sperre`. Ich hatte hier zuerst 600 stehen und im Dialog „für 10
     * Minuten" gemeldet; gemessen wurden **15**, denn die erste Sprosse von
     * `RATE_LEITER_VORGABE` ist 900 s und `rate_leiter_anwenden()` ueberholt
     * `sperre`. Die Zahl steht jetzt auf 900, damit Rueckfall und Sprosse
     * dasselbe sagen — und der Dialogtext rechnet nicht mehr selbst, sondern
     * fragt `rate_stufe_dauer()`.
     *
     * Wer nach einer Viertelstunde wiederkommt und wieder dreimal
     * danebenliegt, raet; dann greift die naechste Sprosse.
     *
     * KEINE SPERRE DES ZUGANGS. Gesperrt ist dieser eine Weg; die
     * Anmeldung, die Anwendung und das Schluesselblatt selbst bleiben offen.
     * Wer die Frage nicht beantworten kann, soll sie nachsehen koennen — das
     * ist der Sinn des Hinweises „Betrieb -> Schluesselblatt neu drucken". */
    'blatt' => ['max' => 3, 'fenster' => 900, 'sperre' => 900, 'leiter' => true],

    /* DER CODE-SCHRITT DER ANMELDUNG (P5c/AP5, E-P5c-53).
     *
     * FUENF VERSUCHE JE KONTO, MIT LEITER. Sechs Ziffern sind eine Million
     * Moeglichkeiten; mit drei gueltigen Fenstern trifft ein Rateversuch mit
     * 3 zu 1 000 000. Fuenf Versuche je Viertelstunde, danach die Leiter —
     * bei der zweiten Sprosse eine Stunde —, machen das Raten aussichtslos,
     * ohne jemanden auszusperren, der sich zweimal vertippt oder eine
     * Minute zu spaet schaut.
     *
     * JE KONTO UND NICHT JE ADRESSE. Wer hier steht, hat das Passwort schon;
     * gezaehlt wird, wie oft an DIESEM Konto der zweite Faktor fehlt. Ein
     * gesperrter Code-Schritt sperrt die Anmeldung dieses Kontos, nicht die
     * der Klinik hinter demselben NAT. Wiederherstellungscodes zaehlen in
     * denselben Topf — sonst waere er mit ihnen zu umgehen. */
    'totp' => ['max' => 5, 'fenster' => 900, 'sperre' => 900, 'leiter' => true],

    /* DER GLOBALE ZAEHLER SPERRT NIE (E-P5a-05). `max` steht auf der
     * groesstmoeglichen Zahl, damit die Sperrbedingung in `rate_misserfolg()`
     * fuer diesen Topf niemals wahr wird — eine globale Sperre waere ein
     * Schalter, den jeder von aussen umlegt. Gelesen wird ausschliesslich
     * `versuche`, und was daraus folgt, steht in `rate_verlangsamung()`. */
    'global' => ['max' => PHP_INT_MAX, 'fenster' => 900, 'sperre' => 900],

    /* KOPPLUNG: DREI ZAEHLUNGEN, DREI TOEPFE (Web 13.0.0, S5 E-S5-16), weil
     * diese Tabelle am Topf haengt und nicht am Merkmal — drei verschiedene
     * Grenzen brauchen drei Eintraege.
     *
     *   pair        401 an status/bestaetigen/trennen, je IP — Kennung oder
     *               Schluessel unbekannt. DIESER TOPF HAT DREI VERBRAUCHER,
     *               und wer an ihm dreht, dreht an allen dreien (B-S5-04,
     *               berichtigt in Web 13.1.1 — der Fund nannte nur die ersten
     *               beiden):
     *                 pair.php   401 an den drei Anliegen
     *                 jobs.php   das Token des Wartungseinstiegs (99, 127)
     *                 gpx.php    die Freigabelinks der Spuren, sieben
     *                            Zaehlstellen (73, 131, 141, 165, 177, 206,
     *                            262, 280)
     *               Deshalb ruft KEINER von ihnen mehr `rate_erfolg('pair')`:
     *               Ein Erfolg im einen Verbraucher darf die Fehlversuche der
     *               anderen nicht loeschen.
     *   pair_start  JEDE Anfrage `start`, je IP (Muster rate_zaehlen(): Menge
     *               begrenzen, wo es kein Scheitern gibt). 20 je 10 Minuten:
     *               Der Kursfall — zwoelf Uhren hinter einer Adresse — passt
     *               hinein; hundert machten den Topf wertlos gegen das
     *               Fuellen der Obergrenze (5000 Sitzungen aus 50 Adressen).
     *   pair_code   Fehlgriffe bei der Code-Eingabe im Web, je Konto UND IP.
     *               Dieselben Zahlen wie `pair` (E-S5-06). Ein Code, der das
     *               Muster PAIR_RE nicht erfuellt, zaehlt NICHT — die
     *               Datenbank wurde nicht gefragt, es war nichts zu erraten
     *               (E-S5-17).
     *
     * Die Obergrenze offener Sitzungen ist KEIN Topf, sondern eine
     * SQL-Zaehlung ueber unverfallene Zeilen (PAIR_SITZUNGEN_MAX, db.php). */
    'pair'       => ['max' => 10, 'fenster' =>  600, 'sperre' =>  600],
    'pair_start' => ['max' => 20, 'fenster' =>  600, 'sperre' =>  600],
    'pair_code'  => ['max' => 10, 'fenster' =>  600, 'sperre' =>  600],

    /* DEMO ZAEHLT ANDERS ALS DIE VIER DARUEBER: nicht Fehlversuche, sondern
     * GELUNGENE Anmeldungen am Demo-Konto (E-P1-20).
     *
     * Warum ueberhaupt: Die Zugangsdaten dieses Kontos sind oeffentlich. Ein
     * Fehlversuchszaehler laeuft dort nie an — es gibt nichts zu erraten.
     * Begrenzt werden soll deshalb die MENGE der Nutzung: Das Konto ist zum
     * Ausprobieren da, nicht als Rechenzeit fuer Fremde.
     *
     * Die Werte sind so gewaehlt, dass sie im Alltag nicht auffallen. 20
     * Anmeldungen je Stunde und Adresse deckt jedes Ausprobieren ab,
     * einschliesslich mehrfachem Abmelden; wer sie ueberschreitet, laesst ein
     * Skript laufen. Die globale Grenze von 300 je Stunde greift erst, wenn
     * viele Adressen gleichzeitig kommen — dann ist der Server gemeint, nicht
     * eine Person. Beide Sperren dauern eine Stunde, so lang wie das Fenster:
     * laenger waere Strafe, kuerzer waere wirkungslos. */
    'demo'  => ['max' => 20, 'fenster' => 3600, 'sperre' => 3600],
    'demog' => ['max' => 300, 'fenster' => 3600, 'sperre' => 3600],

    /* TESTMAIL ZAEHLT WIE DEMO: die MENGE, nicht Fehlversuche (Backlog
     * Nr. 120). Auf Betrieb -> Status steht seit Web 19.3.0 ein Knopf
     * „Testmail an mich"; er ist nur BetreiberInnen zugaenglich, es gibt also
     * nichts zu erraten. Begrenzt wird der Verbrauch am Mailrelais — und die
     * Zeit: Der Versand laeuft SYNCHRON in der Seitenanfrage, weil sein
     * Ergebnis gezeigt werden soll, und ein haengender Mailserver haelt
     * dabei einen PHP-Arbeitsprozess.
     *
     * Drei je Stunde reichen fuer den Zweck: Man prueft nach dem Einrichten
     * einmal, nach einer Aenderung noch einmal, und wenn es dann immer noch
     * nicht geht, liegt es nicht an der Zahl der Versuche. Merkmal ist Konto
     * UND IP (rate_merkmale()) — die Kontokennung, nicht die Adresse, aus
     * demselben Grund wie in einstellungen.php. */
    'testmail' => ['max' => 3, 'fenster' => 3600, 'sperre' => 3600],

    /* CSP-BERICHTE ZAEHLEN WIE DEMO: die MENGE (P5a/AP4, E-P5a-15).
     *
     * `api/csp_bericht.php` nimmt Meldungen des Browsers entgegen und
     * braucht dafuer KEINE Anmeldung — ein Verstoss auf der Anmeldeseite ist
     * der interessanteste von allen. Damit ist der Endpunkt von aussen
     * erreichbar, und jeder kann ihn fuellen.
     *
     * 200 je Stunde und Adresse: Eine Seite mit einem echten Verstoss meldet
     * ihn ein- bis zweimal je Aufruf; wer darueber liegt, laesst ein Skript
     * laufen. Die zweite, wirksamere Schranke ist nicht diese Zahl, sondern
     * die ZUSAMMENFASSUNG in der Tabelle: Tausend gleiche Meldungen werden
     * eine Zeile mit einem Zaehler. */
    'csp' => ['max' => 200, 'fenster' => 3600, 'sperre' => 3600],

    /* HEALTH ZAEHLT EBENSO DIE MENGE (P5c/AP6, E-P5c-17, -52).
     *
     * `api/health.php` ist ohne Sitzung erreichbar; der Token schuetzt die
     * Auskunft, nicht den Aufwand. 60 je Minute und Adresse: Ein Monitoring
     * fragt einmal je Minute, und eine Minute Sperre ist genau ein
     * ausgefallener Abruf. OHNE LEITER — eine wachsende Sperre traefe das
     * Monitoring der BetreiberIn, nicht einen Angreifer, der den Token nicht
     * kennt; der liest aus der 403 ohnehin nichts. */
    'health' => ['max' => 60, 'fenster' => 60, 'sperre' => 60],

    /* DIE VORSCHAU DER RECHTSTEXTE ZAEHLT DIE MENGE (P5c/AP9, E-P5c-28).
     *
     * `api/rechtstext_vorschau.php` rendert, was im Feld steht — ohne zu
     * speichern. Das Skript fragt 0,4 s nach dem letzten Tastendruck; wer
     * tippt, macht Pausen, und daraus werden ein paar Abrufe je Minute.
     * 120 je fuenf Minuten und Konto liegen weit darueber und fangen ein
     * Skript ab, das den Renderer als Rechenknecht benutzt. EINE MINUTE
     * SPERRE, OHNE LEITER: Getroffen wuerde eine angemeldete Verwaltung, und
     * die soll nicht laenger warten als noetig — der Text selbst ist nie
     * betroffen, nur die Vorschau. Merkmal ist das Konto, nicht die Adresse. */
    'rt_vorschau' => ['max' => 120, 'fenster' => 300, 'sperre' => 60],

    /* DIE REGISTRIERUNG HAT DREI TOEPFE, UND JEDER SCHUETZT ETWAS ANDERES
     * (P5b/AP3, E-P5b-13, R37 (4)).
     *
     * `reg` JE IP, 10 je Stunde. Wer sich registriert, tut das einmal. Zehn
     * Versuche decken Vertipper, Abbrueche und ein zweites Konto fuer die
     * Kollegin ab; darueber laeuft ein Skript. Merkmal ist die IP und nicht
     * die Zieladresse — die waere bei jedem Versuch eine andere und zaehlte
     * nie hoch.
     *
     * `regg` GLOBAL, 100 je Stunde. Dieselbe Ueberlegung wie bei `demog`:
     * Kommen die Versuche aus vielen Netzen, ist der Server gemeint und
     * nicht eine Person. Das Merkmal wird ausdruecklich uebergeben
     * (RATE_REG_GLOBAL), damit nicht je IP eine nutzlose Zeile entsteht.
     *
     * `regz` JE ZIELADRESSE, 3 in 24 Stunden — und das ist der wichtigste
     * der drei. OHNE IHN IST DIE REGISTRIERUNG EINE MAILBOMBEN-SCHLEUDER:
     * Die Seite verschickt an JEDE eingegebene Adresse eine Mail, ohne dass
     * der Absender sie besitzen muss. Ein Skript mit wechselnden IPs schickt
     * damit beliebig viele Nachrichten an ein fremdes Postfach, und zwar
     * mit dem guten Namen dieser Installation im Absender. Drei in 24 h
     * reichen fuer den ehrlichen Fall (Mail nicht angekommen, zweiter
     * Versuch, dritter); alles darueber ist keine Registrierung mehr.
     *
     * DAS MERKMAL IST EIN HASH DER ADRESSE, NIE DIE ADRESSE SELBST
     * (rate_reg_ziel()). Die Tabelle `rate_limits` ist sonst ein Verzeichnis
     * fremder Postfaecher — und sie liegt in derselben Datenbank, die auch
     * ein Angreifer abzieht. Dasselbe Verfahren, aus demselben Grund, wie
     * bei den Kontomerkmalen: die Kennung, nicht der Klartext.
     *
     * KEINE LEITER bei allen dreien. Der Grund ist derselbe wie bei `reset`:
     * Das Scheitern ist absichtlich still (die Seite antwortet IMMER
     * gleich), und eine steigende Sperre, die niemand sieht, ist keine
     * Abschreckung, sondern nur eine laengere Stoerung fuer den, der sich
     * vertippt hat. */
    'reg'  => ['max' =>  10, 'fenster' => 3600,  'sperre' => 3600],
    'regg' => ['max' => 100, 'fenster' => 3600,  'sperre' => 3600],
    'regz' => ['max' =>   3, 'fenster' => 86400, 'sperre' => 86400],
];

/**
 * IP-Adresse des Aufrufers, in der Form, in der sie als Merkmal taugt.
 *
 * BIS WEB 20.6.0 STAND HIER: „Kopfzeilen von Zwischenstationen
 * (X-Forwarded-For und Verwandte) werden BEWUSST NICHT ausgewertet: Sie
 * stammen vom Aufrufer und liessen sich zum Zuruecksetzen des Zaehlers frei
 * erfinden." Der Satz war richtig und hat die falsche Antwort gegeben: Hinter
 * einem Reverse Proxy ist `REMOTE_ADDR` die Adresse des Proxys, und dieser
 * Baustein zaehlt dann ALLE Nutzerinnen als eine — und sperrt sie gemeinsam
 * aus. Die IP-Grenzwerte fuer Klinik-NAT (R37) rechnen mit der Client-Adresse.
 *
 * SEIT WEB 20.7.0 (E-P5a-17) entscheidet eine LISTE: `netz_client_ip()`
 * wertet `X-Forwarded-For` genau dann aus, wenn `REMOTE_ADDR` in
 * `config.php` unter `netz.vertrauenswuerdige_proxys` steht. Die Vorgabe ist
 * **leer**, und leer heisst: exakt das Verhalten von vorher. Die Begruendung
 * oben gilt also unveraendert fuer jede Installation, die nichts eintraegt —
 * und wer eintraegt, trifft eine Aussage ueber seine eigene Netztopologie.
 */
function rate_ip(): string
{
    require_once __DIR__ . '/netz_lib.php';
    return netz_client_ip();
}

/**
 * Die Merkmale, unter denen gezaehlt wird.
 * Immer die IP-Adresse; zusaetzlich die Kontokennung, wo es eine gibt.
 * @return array<int, string>
 */
function rate_merkmale(?string $konto = null): array
{
    $m = [rate_merkmal_ip()];
    if ($konto !== null && $konto !== '') {
        $m[] = rate_merkmal_kennung($konto);
    }
    return $m;
}

/**
 * Das Adressmerkmal allein (P5a/AP7).
 *
 * `ingest.php` braucht die beiden Merkmale EINZELN und in verschiedenen
 * Toepfen: die Kennung im Topf `ingest`, die Adresse im Topf `ingest_ip`.
 * `rate_merkmale()` liefert immer beide zusammen; wer sie dort abgreift und
 * dem Topf `ingest` uebergibt, legt eine `ip:`-Zeile in einem Topf an, der
 * nur Kennungen zaehlen soll. Deshalb zwei Bausteine statt einer Zerlegung
 * beim Aufrufer — die Normalisierung steht weiter an EINER Stelle.
 */
function rate_merkmal_ip(): string
{
    return 'ip:' . rate_ip();
}

/**
 * Das Kennungsmerkmal allein — Konto-Adresse oder Geraetekennung.
 *
 * Kleinschreibung, damit "A@b.de" und "a@b.de" denselben Zaehler treffen —
 * sonst waere die Sperre mit der Umschalttaste zu umgehen. FUER DIE
 * GERAETEKENNUNG IST DAS NICHT NUR KOSMETIK: Die Spalte `devices.device_id`
 * steht auf einer Kollation ohne Gross-/Kleinunterscheidung, ein `SELECT`
 * findet die Zeile also auch zu `DEV-AB...`. Ohne die Kleinschreibung haette
 * dieselbe Kennung so viele Zaehler wie Schreibweisen.
 */
function rate_merkmal_kennung(string $kennung): string
{
    return 'id:' . mb_substr(mb_strtolower(trim($kennung)), 0, 180);
}

/* ===========================================================================
 * DIE LEITER, DIE VERLANGSAMUNG UND IHRE EINSTELLUNGEN (P5a/AP6)
 * ======================================================================== */

/** Sprossen der Sperrleiter in Sekunden, wenn nichts eingestellt ist. */
const RATE_LEITER_VORGABE = [900, 1200, 1800, 3600];   // 15 · 20 · 30 · 60 min

/** Schwellen der Verlangsamung: ab so vielen Fehlversuchen je Fenster. */
const RATE_BREMSE_VORGABE = [200, 400, 800, 1600];

/** Wartezeiten dazu, in Sekunden. Vier Stufen, gedeckelt. */
const RATE_BREMSE_SEKUNDEN = [1.0, 2.0, 4.0, 8.0];

/** Merkmal des globalen Zaehlers. Ohne Praefix — kollidiert mit keinem
 *  `ip:`/`id:` und ist derselbe Kniff wie RATE_DEMO_GLOBAL. */
const RATE_GLOBAL_MERKMAL = 'alle';

/** Schluessel in `app_state`. */
const RATE_K_LEITER   = 'ratenschutz_leiter';
const RATE_K_LOGIN    = 'ratenschutz_login_max';
const RATE_K_LOGIN_IP = 'ratenschutz_login_ip_max';
const RATE_K_BREMSE   = 'ratenschutz_bremse_ab';

/**
 * Eine Liste positiver, aufsteigender Ganzzahlen aus einer Einstellung lesen.
 *
 * STRENG UND MIT RUECKFALL. Steht dort Unsinn — eine leere Zeichenkette, drei
 * statt vier Werte, eine absteigende Folge —, gilt die Vorgabe. Eine Leiter,
 * die rueckwaerts laeuft, waere eine Sperre, die beim zweiten Mal KUERZER
 * dauert; das faellt niemandem auf, weil nichts bricht.
 */
function rate_liste(string $schluessel, array $vorgabe): array
{
    static $merker = [];
    if (isset($merker[$schluessel])) { return $merker[$schluessel]; }

    $werte = $vorgabe;
    $roh = function_exists('app_state_lesen') ? app_state_lesen($schluessel) : null;
    if (is_string($roh) && trim($roh) !== '') {
        $teile = array_map('trim', explode(',', $roh));
        if (count($teile) === count($vorgabe)) {
            $zahlen = [];
            $gut = true;
            $vorher = 0;
            foreach ($teile as $t) {
                if (!preg_match('/^[0-9]+$/', $t)) { $gut = false; break; }
                $n = (int)$t;
                if ($n <= 0 || $n <= $vorher) { $gut = false; break; }
                $zahlen[] = $n;
                $vorher = $n;
            }
            if ($gut) { $werte = $zahlen; }
        }
    }
    $merker[$schluessel] = $werte;
    return $werte;
}

/** Die Sperrleiter in Sekunden, Sprosse 1 bis 4. */
function rate_leiter(): array
{
    return rate_liste(RATE_K_LEITER, RATE_LEITER_VORGABE);
}

/** Die Schwellen der Verlangsamung. */
function rate_bremse_schwellen(): array
{
    return rate_liste(RATE_K_BREMSE, RATE_BREMSE_VORGABE);
}

/**
 * Die Grenze eines Topfes — Vorgabe aus RATE_GRENZEN, ueberlagert von den
 * Einstellungen.
 *
 * `RATE_GRENZEN` BLEIBT EINE KONSTANTE und wird NICHT durch eine Funktion
 * ersetzt. `tools/proben/kopplung/probe.php` liest sie an acht Stellen
 * unmittelbar; sie prueft damit die VORGABEN, und das soll sie auch. Wer die
 * Konstante wegnimmt, bricht die Probe still.
 *
 * NUR ZWEI ZAHLEN SIND EINSTELLBAR, und beide gehoeren zur Anmeldung. Die
 * uebrigen zwoelf Toepfe haben Zahlen mit einer Begruendung (oben je Topf
 * ausgeschrieben) — sie einstellbar zu machen hiesse, die Begruendung gegen
 * ein Eingabefeld zu tauschen.
 */
function rate_grenze(string $topf): ?array
{
    $g = RATE_GRENZEN[$topf] ?? null;
    if ($g === null) { return null; }

    if ($topf === 'login' || $topf === 'login_ip') {
        $k = $topf === 'login' ? RATE_K_LOGIN : RATE_K_LOGIN_IP;
        $roh = function_exists('app_state_lesen') ? app_state_lesen($k) : null;
        if (is_string($roh) && preg_match('/^[0-9]+$/', trim($roh))) {
            $n = (int)trim($roh);
            if ($n >= 3 && $n <= 10000) { $g['max'] = $n; }
        }
    }
    return $g;
}

/**
 * Wie lange sperrt Stufe N? Stufe 0 gibt es nicht — sie heisst „nie gesperrt".
 *
 * UEBER DER LETZTEN SPROSSE BLEIBT ES BEI DER LETZTEN. Die Leiter endet, sie
 * waechst nicht ins Unendliche: Ein Konto, das dauerhaft zu ist, ist kein
 * Schutz mehr, sondern ein Ausfall — und der Weg zurueck (Passwort
 * zuruecksetzen) muss in derselben Sitzung noch gangbar sein.
 */
function rate_stufe_dauer(int $stufe): int
{
    $leiter = rate_leiter();
    if ($stufe < 1) { return $leiter[0]; }
    return $leiter[min($stufe, count($leiter)) - 1];
}

/** Die hoechste Sprosse — fuer die Sammelmail (E-P5a-07). */
function rate_stufe_hoechste(): int
{
    return count(rate_leiter());
}

/**
 * Der globale Zaehler: eine Anfrage verbuchen.
 *
 * NUR AN ECHTEN FEHLVERSUCHEN. Nicht in `rate_zaehlen()`, denn die drei
 * Toepfe dort kennen kein Scheitern — ein Salz-Abruf ist kein Angriff, und
 * der globale Zaehler soll die Lage messen, nicht den Verkehr.
 */
function rate_global_misserfolg(): void
{
    rate_misserfolg('global', null, [RATE_GLOBAL_MERKMAL]);
}

/**
 * Wie stark wird gerade verlangsamt?
 *
 * @return array{stufe:int, sekunden:float, versuche:int}
 *         stufe 0 = gar nicht.
 *
 * `$frisch` UMGEHT DEN MERKER — nur fuer `tools/proben/raten/`. Der Merker ist
 * je Anfrage richtig (die Lage aendert sich in einer Seitenanfrage nicht),
 * aber eine Probe, die in einem Prozess zehn Lagen nacheinander herstellt,
 * bekaeme sonst zehnmal die erste.
 *
 * MIT FENSTERPRUEFUNG, und das ist keine Feinheit: `rate_misserfolg()` setzt
 * `versuche` erst beim NAECHSTEN Fehlversuch zurueck. Ohne die Pruefung auf
 * `fenster_start` bliebe eine Installation nach einem Angriff verlangsamt,
 * bis zufaellig wieder jemand ein Passwort falsch eintippt — im
 * schlechtesten Fall wochenlang, und niemand faende den Grund.
 */
function rate_verlangsamung(bool $frisch = false): array
{
    static $merker = null;
    if ($merker !== null && !$frisch) { return $merker; }

    $aus = ['stufe' => 0, 'sekunden' => 0.0, 'versuche' => 0];
    $g = RATE_GRENZEN['global'];
    try {
        $st = db()->prepare(
            'SELECT versuche FROM rate_limits
              WHERE topf = ? AND merkmal = ?
                AND fenster_start >= DATE_SUB(NOW(), INTERVAL ? SECOND)');
        $st->execute(['global', RATE_GLOBAL_MERKMAL, $g['fenster']]);
        $n = $st->fetchColumn();
        if ($n === false) { return $merker = $aus; }
        $aus['versuche'] = (int)$n;
    } catch (Throwable $ex) {
        /* Tabelle oder Zeile fehlt — keine Verlangsamung. Ein Ratenschutz,
         * der bei einem Datenbankfehler ALLE bremst, waere der
         * selbstgebaute Ausfall aus dem Kopfkommentar. */
        return $merker = $aus;
    }

    foreach (rate_bremse_schwellen() as $i => $schwelle) {
        if ($aus['versuche'] >= $schwelle) {
            $aus['stufe']    = $i + 1;
            $aus['sekunden'] = RATE_BREMSE_SEKUNDEN[$i] ?? end(RATE_BREMSE_SEKUNDEN);
        }
    }
    return $merker = $aus;
}

/**
 * Eine Sperre von Hand aufheben (E-P5a-04, Knopf in AP8).
 *
 * EIGENE FUNKTION UND NICHT `rate_erfolg()`, und der Grund ist ein Fehler,
 * der ohne Meldung durchginge: `rate_erfolg()` bildet die Merkmale aus dem
 * AUFRUFER. Auf der Betriebsseite waere das die IP-Adresse der
 * Administratorin — der Knopf loeschte ihre eigene Zeile, meldete Erfolg, und
 * die Sperre bliebe stehen.
 *
 * @return bool true, wenn eine Zeile weg ist.
 */
function rate_sperre_aufheben(string $topf, string $merkmal, ?string $wer = null): bool
{
    try {
        $pdo = db();
        $st = $pdo->prepare('DELETE FROM rate_limits WHERE topf = ? AND merkmal = ?');
        $st->execute([$topf, $merkmal]);
        $weg = $st->rowCount() > 0;
        if ($weg) {
            rate_ereignis('aufgehoben', $topf, $merkmal, 0, null, null, $wer);
        }
        return $weg;
    } catch (Throwable $ex) {
        system_melden('ratenschutz', 'Aufheben gescheitert (' . $topf . ')', $ex);
        return false;
    }
}

/**
 * Alle Toepfe eines Kontos leeren — nach einem gesetzten Passwort.
 *
 * WOGEGEN (E-P5a-45). `pw_handling.php` ruft heute keine einzige
 * `rate_*`-Funktion. Wer sein Passwort ueber den Link zuruecksetzt, hat
 * gerade nachgewiesen, dass ihm das Postfach gehoert — und bleibt an der
 * Anmeldung trotzdem gesperrt. Bisher bis zu 15 Minuten; mit der Leiter bis
 * zu einer Stunde, und die Stufe stuende danach noch 24 h, sodass die
 * naechste Vertipperin sofort wieder eine Stunde draussen waere.
 *
 * NUR DAS KONTO, NICHT DIE ADRESSE. Die IP-Zeile bleibt stehen: Wer ein
 * Postfach uebernommen hat, soll damit nicht die Sperre einer ganzen Klinik
 * aufheben koennen.
 */
function rate_konto_freigeben(string $konto): void
{
    $konto = trim($konto);
    if ($konto === '') { return; }
    $merkmal = 'id:' . mb_substr(mb_strtolower($konto), 0, 180);
    foreach (['login', 'salt'] as $topf) {
        rate_sperre_aufheben($topf, $merkmal, 'Passwort gesetzt');
    }
}

/**
 * Ein Sicherheitsereignis vermerken (E-P5a-46).
 *
 * EIGENES try/catch UND AUSSERHALB DER ZAEHLUNG. Die Tabelle ist jung; sie
 * fehlt zwischen Deploy und Migrationslauf. Ein Protokoll, das seinen
 * Gegenstand kaputtmacht, ist schlechter als keines — dieselbe Ueberlegung
 * wie bei `job_laeufe`.
 */
function rate_ereignis(string $art, ?string $topf, ?string $merkmal,
                       int $stufe = 0, ?int $versuche = null,
                       ?string $bis = null, ?string $wer = null): void
{
    try {
        db()->prepare('INSERT INTO sicherheit_ereignisse
                         (art, topf, merkmal, stufe, versuche, zeitpunkt, bis, wer)
                       VALUES (?, ?, ?, ?, ?, UTC_TIMESTAMP(), ?, ?)')
            ->execute([$art, $topf, $merkmal, $stufe, $versuche, $bis, $wer]);
    } catch (Throwable $ex) {
        /* Tabelle fehlt (Migration noch nicht gelaufen) — ohne Folgen. */
    }
}

/**
 * Ist der Zugriff erlaubt? VOR jeder teuren Pruefung aufrufen.
 *
 * Liefert true, wenn weitergemacht werden darf, und false, wenn eine Sperre
 * greift. Der Zaehler wird hier NICHT erhoeht — das geschieht erst bei einem
 * Misserfolg (rate_misserfolg), damit gelungene Anmeldungen nicht auf das
 * Kontingent gehen.
 */
function rate_erlaubt(string $topf, ?string $konto = null,
                      ?array $merkmale = null): bool
{
    $grenze = rate_grenze($topf);
    if ($grenze === null) { return true; }

    try {
        $pdo = db();
        $st = $pdo->prepare(
            'SELECT 1 FROM rate_limits
             WHERE topf = ? AND merkmal = ? AND gesperrt_bis IS NOT NULL
               AND gesperrt_bis > NOW() LIMIT 1');
        foreach ($merkmale ?? rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal]);
            if ($st->fetchColumn() !== false) { return false; }
        }
        return true;
    } catch (Throwable $ex) {
        system_melden('ratenschutz', 'nicht verfügbar (' . $topf . ')', $ex);
        return true;   // s. Kopfkommentar: durchlassen statt selbstgebauter Ausfall
    }
}

/**
 * Einen Fehlversuch verbuchen. Erreicht ein Merkmal seine Grenze, wird es
 * gesperrt.
 *
 * Das Zeitfenster wandert nicht mit: Nach Ablauf beginnt die Zaehlung von
 * vorn. Das ist die einfache Variante und fuer den Zweck ausreichend — sie
 * erlaubt im schlechtesten Fall die doppelte Zahl an Versuchen ueber eine
 * Fenstergrenze hinweg, was gegenueber "unbegrenzt" keine Rolle spielt.
 */
function rate_misserfolg(string $topf, ?string $konto = null,
                         ?array $merkmale = null): void
{
    $grenze = rate_grenze($topf);
    if ($grenze === null) { return; }

    try {
        $pdo = db();
        // REIHENFOLGE DER ZUWEISUNGEN IST WESENTLICH: MySQL wertet sie von
        // links nach rechts aus. 'versuche' und 'gesperrt_bis' muessen den
        // ALTEN 'fenster_start' sehen, deshalb wird dieser zuletzt gesetzt.
        // Andersherum verglichen sie gegen den soeben gesetzten Wert — die
        // Bedingung waere nie wahr und eine Sperre liefe nie ab.
        /* EINE LAUFENDE SPERRE UEBERLEBT DEN FENSTERWECHSEL (P5a/AP6).
         *
         * Hier stand `gesperrt_bis = IF(<Fenster abgelaufen>, NULL, <alt>)` ohne
         * weitere Bedingung. Das war folgenlos, solange bei ALLEN zehn
         * Toepfen `sperre == fenster` galt: Wenn das Fenster abgelaufen war,
         * war es die Sperre auch.
         *
         * MIT DER LEITER GILT DAS NICHT MEHR. Sie waechst auf 60 Minuten, das
         * Fenster bleibt bei 15. Ein einziger Fehlversuch nach Fensterablauf
         * haette damit die LAUFENDE Sperre geloescht — der Gesperrte haette
         * sich durch Klopfen befreit, und zwar ohne Fehlermeldung, ohne
         * Protokollzeile, ohne dass irgendetwas rot wuerde.
         *
         * Der Zaehler wird trotzdem zurueckgesetzt: Das neue Fenster faengt
         * bei 1 an, die Sperre haelt ueber `gesperrt_bis`. */
        $st = $pdo->prepare(
            'INSERT INTO rate_limits (topf, merkmal, versuche, fenster_start)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE
               versuche = IF(fenster_start < DATE_SUB(NOW(), INTERVAL ? SECOND),
                             1, versuche + 1),
               gesperrt_bis = IF(fenster_start < DATE_SUB(NOW(), INTERVAL ? SECOND)
                                 AND (gesperrt_bis IS NULL OR gesperrt_bis <= NOW()),
                                 NULL, gesperrt_bis),
               fenster_start = IF(fenster_start < DATE_SUB(NOW(), INTERVAL ? SECOND),
                                  NOW(), fenster_start)');

        /* UND DIE SPERRE VERLAENGERT SICH NICHT DURCH KLOPFEN. Ohne die
         * letzte Bedingung setzte jeder weitere Fehlversuch `gesperrt_bis`
         * neu — aus 15 Minuten wuerde eine Sperre, die so lange haelt, wie
         * jemand dagegen klopft. Bisher war auch das folgenlos, weil der
         * Zaehler beim Fensterwechsel auf 1 fiel und die Bedingung
         * `versuche >= max` damit nicht mehr griff. */
        $sperren = $pdo->prepare(
            'UPDATE rate_limits SET gesperrt_bis = DATE_ADD(NOW(), INTERVAL ? SECOND)
             WHERE topf = ? AND merkmal = ? AND versuche >= ?
               AND (gesperrt_bis IS NULL OR gesperrt_bis <= NOW())');

        $hatLeiter = !empty($grenze['leiter']);

        foreach ($merkmale ?? rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal,
                          $grenze['fenster'], $grenze['fenster'], $grenze['fenster']]);

            /* DIE LEITER ZUERST, DER RUECKFALL DANACH. Greift die Leiter,
             * steht `gesperrt_bis` in der Zukunft und `$sperren` findet
             * nichts mehr. Fehlt die Spalte `stufe` (Fenster zwischen Deploy
             * und Migrationslauf), tut die Leiter nichts und `$sperren`
             * sperrt wie vor Web 20.10.0. */
            $neu = $hatLeiter ? rate_leiter_anwenden($pdo, $topf, $merkmal, $grenze) : null;

            $sperren->execute([$grenze['sperre'], $topf, $merkmal, $grenze['max']]);

            if ($neu !== null) {
                rate_ereignis('sperre', $topf, $merkmal, $neu['stufe'],
                              $neu['versuche'], $neu['bis']);
                /* DIE HOECHSTE SPROSSE MELDET SICH (E-P5a-07). Nicht jede
                 * Sperre — eine Vertipperin am Montagmorgen ist keine
                 * Meldung wert; die vierte Sperre desselben Merkmals
                 * innerhalb von 24 h ist es. */
                if ($neu['stufe'] >= rate_stufe_hoechste()) {
                    sicherheit_melden_pruefen();
                }
            }
        }
    } catch (Throwable $ex) {
        system_melden('ratenschutz', 'konnte nicht zählen (' . $topf . ')', $ex);
    }
}

/**
 * Die Sperrleiter auf ein Merkmal anwenden — in EINEM Statement und in einem
 * EIGENEN try/catch.
 *
 * @return array{stufe:int, bis:string, versuche:int}|null
 *         null = es wurde nichts gesperrt (Schwelle nicht erreicht, schon
 *         gesperrt, oder die Spalten gibt es noch nicht).
 *
 * WARUM EIN STATEMENT UND NICHT LESEN-RECHNEN-SCHREIBEN: Zwei gleichzeitige
 * Fehlversuche laesen sonst beide dieselbe Stufe und schrieben beide dieselbe
 * naechste — die Leiter bliebe stehen, wo zwei Leute gleichzeitig klopfen,
 * also genau im Angriffsfall.
 *
 * DIE REIHENFOLGE DER ZUWEISUNGEN IST WESENTLICH, wie oben: MariaDB wertet
 * von links nach rechts aus. `gesperrt_bis` liest mit `ELT(stufe, ...)` die
 * SOEBEN gesetzte Stufe — stuende es davor, griffe es die alte.
 *
 * `stufe_bis` WIRD ZULETZT GESETZT, und zwar bei JEDEM Fehlversuch, nicht nur
 * beim Sperren: Die Stufe faellt „nach 24 h ohne Fehlversuch" (E-P5a-04), und
 * das ist etwas anderes als „24 h nach der letzten Sperre".
 */
function rate_leiter_anwenden(PDO $pdo, string $topf, string $merkmal, array $grenze): ?array
{
    $leiter = rate_leiter();
    $hoechste = count($leiter);
    try {
        /* ---- SCHRITT 1: DER VERFALL, UND ZWAR BEI JEDEM FEHLVERSUCH -------
         *
         * „Die Stufe faellt nach 24 h ohne Fehlversuch auf 0" (E-P5a-04) —
         * gemessen wird das im Augenblick des NAECHSTEN Fehlversuchs. Ist
         * `stufe_bis` bis dahin verstrichen, faengt die Leiter von vorn an;
         * sonst laeuft die Frist von hier neu.
         *
         * DIESER SCHRITT STAND ZUERST IM ZWEITEN ZWEIG, und das war falsch.
         * Er lief nur, wenn NICHT gesperrt wurde — also bei den ersten neun
         * Fehlversuchen. Jeder von ihnen schob `stufe_bis` um 24 h vor, und
         * beim zehnten war die Frist deshalb nie abgelaufen: Die Stufe fiel
         * NIE zurueck, und ein Konto, das vor einem halben Jahr einmal die
         * vierte Sprosse erreicht hatte, bekam beim naechsten Tippfehler
         * sofort wieder 60 Minuten. Gefunden von `tools/proben/raten/`,
         * Abschnitt 4 — im Betrieb waere es niemandem aufgefallen, weil
         * nichts bricht und die Sperre ja „funktioniert".
         *
         * REIHENFOLGE: `stufe_bis` liest die SOEBEN zurueckgesetzte `stufe`.
         * MariaDB wertet von links nach rechts aus. */
        $pdo->prepare('UPDATE rate_limits
                          SET stufe = IF(stufe_bis IS NULL OR stufe_bis < NOW(), 0, stufe),
                              stufe_bis = IF(stufe > 0,
                                             DATE_ADD(NOW(), INTERVAL 86400 SECOND), NULL)
                        WHERE topf = ? AND merkmal = ?')
            ->execute([$topf, $merkmal]);

        /* ---- SCHRITT 2: DIE SPROSSE -------------------------------------
         *
         * ELT() ist 1-basiert und nimmt genau so viele Werte, wie die Leiter
         * Sprossen hat. Die Platzhalter werden deshalb aus der Leiter gebaut
         * und nicht fest geschrieben — wer eine fuenfte Sprosse einstellt,
         * bekommt sie.
         *
         * `stufe` ist hier schon verfallen oder nicht; addiert wird nur noch. */
        $platz = implode(', ', array_fill(0, $hoechste, '?'));
        $sql = 'UPDATE rate_limits
                   SET stufe = LEAST(?, stufe + 1),
                       gesperrt_bis = DATE_ADD(NOW(), INTERVAL ELT(stufe, ' . $platz . ') SECOND),
                       stufe_bis = DATE_ADD(NOW(), INTERVAL 86400 SECOND)
                 WHERE topf = ? AND merkmal = ? AND versuche >= ?
                   AND (gesperrt_bis IS NULL OR gesperrt_bis <= NOW())';
        $st = $pdo->prepare($sql);
        $st->execute(array_merge([$hoechste], $leiter,
                                 [$topf, $merkmal, $grenze['max']]));

        if ($st->rowCount() < 1) { return null; }

        $lese = $pdo->prepare('SELECT stufe, versuche, gesperrt_bis FROM rate_limits
                                WHERE topf = ? AND merkmal = ?');
        $lese->execute([$topf, $merkmal]);
        $z = $lese->fetch(PDO::FETCH_ASSOC);
        if (!$z) { return null; }
        return ['stufe'    => (int)$z['stufe'],
                'versuche' => (int)$z['versuche'],
                'bis'      => (string)$z['gesperrt_bis']];
    } catch (Throwable $ex) {
        /* Die Spalten gibt es noch nicht (Migration steht aus). Dann sperrt
         * `$sperren` gleich danach mit der festen Dauer — der Ratenschutz
         * faellt NICHT aus, er ist nur wieder so streng wie vorher. */
        return null;
    }
}

/**
 * Eine Anfrage verbuchen, ohne dass es einen Misserfolg gaebe.
 *
 * Drei Toepfe kennen kein Scheitern: Der Salz-Endpunkt antwortet jeder
 * Adresse — das ist gerade der Sinn des Pseudo-Salts. Die
 * Zuruecksetzen-Anforderung antwortet immer gleich, egal ob es das Konto gibt.
 * Und `start` an der Kopplung (Topf `pair_start`) legt jedem Geraet eine
 * Sitzung an. An allen drei Stellen ist die MENGE der Anfragen das, was
 * begrenzt werden soll, nicht ein Fehlversuch.
 *
 * Technisch dasselbe wie rate_misserfolg(); der eigene Name steht hier, damit
 * an der Aufrufstelle nicht "Misserfolg" steht, wo es keinen gibt.
 */
function rate_zaehlen(string $topf, ?string $konto = null,
                      ?array $merkmale = null): void
{
    rate_misserfolg($topf, $konto, $merkmale);
}

/**
 * Wie viele Fehlversuche stehen im laufenden Fenster? (P5b/AP9)
 *
 * GEZAEHLT WIRD DAS KONTOMERKMAL, NICHT DIE ADRESSE. Die Zahl geht in eine
 * MELDUNG — „noch 2 Versuche" —, und die soll dem Menschen vor dem Bildschirm
 * gelten, nicht allen hinter derselben NAT. Wer die Adresse zaehlte, sagte
 * einer BetreiberIn, sie habe noch einen Versuch, weil eine andere im selben
 * Haus welche verbraucht hat.
 *
 * ABGELAUFENES FENSTER ZAEHLT NICHT MIT — dieselbe Bedingung wie in
 * `rate_verlangsamung()`. Ohne sie stuende nach einer Stunde Ruhe noch die
 * alte Zahl da, und die Meldung waere falsch, ohne falsch auszusehen.
 *
 * FUER ANZEIGE, NICHT FUER ENTSCHEIDUNGEN. Ob etwas erlaubt ist, sagt
 * `rate_erlaubt()`; diese Zahl ist eine Auskunft. Bei einem Datenbankfehler
 * kommt 0 zurueck — die Meldung ist dann ungenau, und das ist harmloser als
 * eine Seite, die daran scheitert.
 */
function rate_versuche(string $topf, string $konto): int
{
    $g = rate_grenze($topf);
    if ($g === null) { return 0; }
    try {
        $st = db()->prepare(
            'SELECT versuche FROM rate_limits
              WHERE topf = ? AND merkmal = ?
                AND fenster_start >= DATE_SUB(NOW(), INTERVAL ? SECOND)');
        $st->execute([$topf, rate_merkmal_kennung($konto), $g['fenster']]);
        $n = $st->fetchColumn();
        return $n === false ? 0 : (int)$n;
    } catch (Throwable $ex) {
        return 0;
    }
}

/**
 * Nach einem Erfolg die Zaehler der beteiligten Merkmale leeren.
 *
 * Bewusst auch fuer die IP-Adresse: Wer sich erfolgreich anmeldet, ist mit
 * hoher Wahrscheinlichkeit kein Angreifer, und mehrere Personen hinter einer
 * gemeinsamen Adresse sollen sich nicht gegenseitig aussperren.
 *
 * `$merkmale` UEBERSTIMMT DAS (P5a/AP7). `ingest.php` raeumt nach einem
 * gelungenen Upload NUR den Kennungstopf und ausdruecklich NICHT die Adresse:
 * Im Topf `ingest_ip` stehen ausschliesslich Fehlversuche mit UNBEKANNTEN
 * Kennungen, und ein gueltiger Upload sagt ueber die nichts aus. Wer ihn
 * dort mitraeumen liesse, gaebe jedem, der ein einziges gueltiges Geraet
 * besitzt, den Rueckstellknopf fuer seine ganze Adresse.
 */
function rate_erfolg(string $topf, ?string $konto = null,
                     ?array $merkmale = null): void
{
    try {
        $st = db()->prepare('DELETE FROM rate_limits WHERE topf = ? AND merkmal = ?');
        foreach ($merkmale ?? rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal]);
        }
    } catch (Throwable $ex) {
        system_melden('ratenschutz', 'konnte nicht zurücksetzen (' . $topf . ')', $ex);
    }
}

/**
 * Konstante Antwortzeit bei Misserfolg.
 *
 * Aufruf am ENDE des Fehlerzweigs mit dem Zeitpunkt vom Anfang der Anfrage.
 * Die Funktion wartet, bis die Mindestdauer erreicht ist. Damit dauert eine
 * abgewiesene Anfrage immer gleich lang — ob das Konto existiert, ob eine
 * bcrypt-Pruefung lief oder ob die Sperre sofort gegriffen hat.
 *
 * Das ist eine NOTLOESUNG und als solche gekennzeichnet: Sie verlangsamt jede
 * abgewiesene Anfrage. Wo ein Zeitunterschied aus einem MAILVERSAND stammt,
 * hilft sie nicht zuverlaessig — dort gehoert der Versand aus der Anfrage
 * heraus in eine Warteschlange.
 */
function rate_gleiche_dauer(float $beginn, float $mindestSekunden = 0.35): void
{
    $verstrichen = microtime(true) - $beginn;
    $rest = $mindestSekunden - $verstrichen;
    if ($rest > 0) { usleep((int)round($rest * 1000000)); }
}

/**
 * Dasselbe, aber mit der globalen Verlangsamung darin (E-P5a-05).
 *
 * WARUM EIN EIGENER NAME UND KEIN DRITTER PARAMETER MIT VORGABE: Die
 * Verlangsamung darf NUR dort greifen, wo tatsaechlich gerechnet wurde. Ein
 * Vorgabewert waere die Einladung, sie irgendwann versehentlich auch in den
 * Zweig „ist gesperrt" zu bekommen — und dann waere der gesperrte Zweig
 * langsamer als der ungesperrte, also eine Auskunft. Wer verlangsamen will,
 * schreibt es hin.
 *
 * DIE VERLANGSAMUNG ERSETZT DIE MINDESTDAUER NICHT, sie hebt sie an. Sonst
 * verlöre der Fehlerzweig bei aktiver Bremse seine Gleichheit — 1 s statt
 * 0,35 s waere zwar langsamer, aber eben auch eine andere Zahl fuer jeden
 * Zweig, der schon laenger gerechnet hat.
 *
 * @return int Die Stufe der Verlangsamung, damit der Aufrufer sie melden kann.
 */
function rate_gleiche_dauer_gebremst(float $beginn, float $mindestSekunden = 0.35): int
{
    $v = rate_verlangsamung();
    rate_gleiche_dauer($beginn, max($mindestSekunden, $v['sekunden']));
    return $v['stufe'];
}

/**
 * Wann laeuft die Sperre ab? Fuer eine Meldung, die nicht raten laesst.
 * Liefert null, wenn nichts gesperrt ist.
 */
function rate_gesperrt_bis(string $topf, ?string $konto = null,
                           ?array $merkmale = null): ?string
{
    $s = rate_sperre($topf, $konto, $merkmale);
    return $s === null ? null : $s['bis'];
}

/**
 * Die laufende Sperre mit allem, was eine Meldung braucht (P5a/AP6).
 *
 * @return array{bis:string, rest:int, stufe:int, merkmal:string, art:string}|null
 *         `art` ist 'konto' oder 'adresse' — WELCHES Merkmal greift.
 *
 * WARUM DAS MERKMAL MITKOMMT. `rate_erlaubt()` laeuft ueber IP UND Konto und
 * sagt nur ja/nein; die alte `rate_gesperrt_bis()` nannte nur das spaeteste
 * Ende. „Anmeldung fuer diesen Namen gesperrt" waere damit schlicht falsch,
 * wenn in Wahrheit die Adresssperre greift — und die Betreiberin faende die
 * Zahl auf der Sicherheitsseite nicht wieder.
 *
 * `rest` IST EINE GANZE ZAHL SEKUNDEN und kommt aus der DATENBANK, nicht aus
 * einer Differenz in PHP. Der Grund ist eine Falle, die still danebengeht:
 * `gesperrt_bis` ist ein roher DATETIME, und die Verbindung steht auf
 * `+00:00` (db.php). Wer den String in ein `data`-Attribut schreibt, bekommt
 * ihn im Browser als ORTSZEIT zurueck — der Countdown stuende je nach Zone
 * ein bis zwei Stunden daneben.
 *
 * SIE SCHWEIGT NICHT MEHR BEI EINEM FEHLER. Die alte Fassung fing jede
 * Ausnahme und gab `null` zurueck; als Grundlage eines Countdowns heisst das
 * „keine Sperre", obwohl gesperrt ist.
 */
function rate_sperre(string $topf, ?string $konto = null,
                     ?array $merkmale = null): ?array
{
    try {
        $st = db()->prepare(
            'SELECT gesperrt_bis, stufe, TIMESTAMPDIFF(SECOND, NOW(), gesperrt_bis) AS rest
               FROM rate_limits
              WHERE topf = ? AND merkmal = ? AND gesperrt_bis > NOW()');
        $beste = null;
        foreach ($merkmale ?? rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal]);
            $z = $st->fetch(PDO::FETCH_ASSOC);
            if (!$z) { continue; }
            $eintrag = [
                'bis'     => (string)$z['gesperrt_bis'],
                'rest'    => max(0, (int)$z['rest']),
                'stufe'   => (int)($z['stufe'] ?? 0),
                'merkmal' => $merkmal,
                'art'     => str_starts_with($merkmal, 'id:') ? 'konto' : 'adresse',
            ];
            if ($beste === null || $eintrag['bis'] > $beste['bis']) { $beste = $eintrag; }
        }
        return $beste;
    } catch (Throwable $ex) {
        system_melden('ratenschutz', 'Sperrstand nicht lesbar (' . $topf . ')', $ex);
        return null;
    }
}

/**
 * Die laengste laufende Sperre ueber MEHRERE Toepfe — in EINER Abfrage
 * (P5a/AP7).
 *
 * WARUM NICHT ZWEIMAL `rate_sperre()`. `ingest.php` fragt bei JEDEM Upload,
 * bevor irgendetwas anderes geschieht; das ist der heisseste Weg der
 * Anwendung. Zwei Aufrufe sind zwei Umlaeufe zur Datenbank je Upload, und der
 * Referenzlauf schickt sechshundert. Eine Abfrage mit zwei ODER-Zweigen
 * trifft denselben Schluessel `uq_topf_merkmal` und kostet einen.
 *
 * @param list<array{0:string,1:string}> $paare  je [Topf, Merkmal]
 * @return array{topf:string, bis:string, rest:int, stufe:int, merkmal:string}|null
 */
function rate_sperre_paare(array $paare): ?array
{
    if ($paare === []) { return null; }
    try {
        $wo = [];
        $args = [];
        foreach ($paare as $p) {
            $wo[]   = '(topf = ? AND merkmal = ?)';
            $args[] = (string)$p[0];
            $args[] = (string)$p[1];
        }
        $st = db()->prepare(
            'SELECT topf, merkmal, gesperrt_bis, stufe,
                    TIMESTAMPDIFF(SECOND, NOW(), gesperrt_bis) AS rest
               FROM rate_limits
              WHERE gesperrt_bis > NOW() AND (' . implode(' OR ', $wo) . ')
              ORDER BY gesperrt_bis DESC LIMIT 1');
        $st->execute($args);
        $z = $st->fetch(PDO::FETCH_ASSOC);
        if (!$z) { return null; }
        return [
            'topf'    => (string)$z['topf'],
            'merkmal' => (string)$z['merkmal'],
            'bis'     => (string)$z['gesperrt_bis'],
            'rest'    => max(0, (int)$z['rest']),
            'stufe'   => (int)($z['stufe'] ?? 0),
        ];
    } catch (Throwable $ex) {
        /* Wie `rate_erlaubt()`: durchlassen statt selbstgebauter Ausfall.
         * Die Tabelle fehlt genau dann, wenn `update.php` noch nicht lief. */
        system_melden('ratenschutz', 'Sperrstand nicht lesbar (mehrere Töpfe)', $ex);
        return null;
    }
}

/**
 * Alle laufenden Sperren — fuer die Statusseite und AP8.
 *
 * @return list<array{topf:string, merkmal:string, art:string, stufe:int,
 *                    versuche:int, bis:string, rest:int}>
 *         `art` ist 'konto' oder 'adresse' — wie bei `rate_sperre()`.
 */
function rate_sperren_aktiv(int $grenze = 50): array
{
    try {
        $st = db()->query(
            'SELECT topf, merkmal, stufe, versuche, gesperrt_bis,
                    TIMESTAMPDIFF(SECOND, NOW(), gesperrt_bis) AS rest
               FROM rate_limits
              WHERE gesperrt_bis > NOW()
           ORDER BY gesperrt_bis DESC
              LIMIT ' . max(1, min(500, $grenze)));
        $aus = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
            $merkmal = (string)$z['merkmal'];
            $aus[] = ['topf'     => (string)$z['topf'],
                      'merkmal'  => $merkmal,
                      /* `art` KOMMT SEIT P5a/AP8 MIT (E-P5a-08 nennt die Spalte
                       * ausdruecklich). Die Ableitung stand bis dahin dreimal
                       * im Bestand nachgebaut — in `rate_sperre()`, in
                       * `sicherheit_melden_pruefen()` und auf der Statusseite.
                       * Drei Stellen, drei Gelegenheiten, die Woerter
                       * auseinanderlaufen zu lassen. */
                      'art'      => str_starts_with($merkmal, 'id:') ? 'konto' : 'adresse',
                      'stufe'    => (int)($z['stufe'] ?? 0),
                      'versuche' => (int)$z['versuche'],
                      'bis'      => (string)$z['gesperrt_bis'],
                      'rest'     => max(0, (int)$z['rest'])];
        }
        return $aus;
    } catch (Throwable $ex) {
        return [];
    }
}

/* ===========================================================================
 * WAS DIE SICHERHEITSSEITE LIEST (P5a/AP8, E-P5a-08)
 *
 * DREI LESEFUNKTIONEN UND KEINE VIERTE. `betrieb_sicherheit.php` zeichnet und
 * rechnet nicht — dieselbe Trennung wie zwischen `status_lib.php` und
 * `betrieb_status.php`, und aus demselben Grund: Eine Seite, die ihre eigenen
 * Abfragen stellt, sagt frueher oder spaeter etwas anderes als die Zahl am
 * Menuepunkt.
 * ======================================================================== */

/**
 * Die Sicherheitsereignisse der letzten 30 Tage (E-P5a-09).
 *
 * DIE FRIST STEHT HIER UND IST KEINE EINSTELLUNG — sie ist dieselbe, mit der
 * der Job `aufraeumen` loescht. Eine Seite, die weiter zurueckblickt als der
 * Job aufhebt, zeigt eine Luecke, die wie ein ruhiger Monat aussieht.
 *
 * SIE LIEFERT DIE GESAMTZAHL MIT, nicht nur die Zeilen. `LIMIT 200` ist unter
 * Beschuss schnell erreicht — 30 Tage mit einem laufenden Angriff bringen
 * mehr. Eine Karte, die dann zweihundert Zeilen zeigt und schweigt, sagt
 * „das war alles"; sie soll „die juengsten 200 von 1 384" sagen koennen.
 *
 * @param list<string> $arten   leer = alle ('sperre', 'verlangsamung', 'aufgehoben')
 * @param list<string>|null $toepfe  null = alle
 * @return array{zeilen: list<array{art:string, topf:?string, merkmal:?string,
 *                    stufe:int, versuche:?int, zeitpunkt:string, bis:?string,
 *                    wer:?string}>, gesamt:int}
 */
function sicherheit_ereignisse(array $arten = [], ?array $toepfe = null,
                               int $grenze = 200): array
{
    try {
        $wo   = ['zeitpunkt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)'];
        $args = [];
        if ($arten !== []) {
            $wo[] = 'art IN (' . implode(',', array_fill(0, count($arten), '?')) . ')';
            foreach ($arten as $a) { $args[] = $a; }
        }
        if ($toepfe !== null && $toepfe !== []) {
            $wo[] = 'topf IN (' . implode(',', array_fill(0, count($toepfe), '?')) . ')';
            foreach ($toepfe as $t) { $args[] = $t; }
        }
        $st = db()->prepare(
            'SELECT art, topf, merkmal, stufe, versuche, zeitpunkt, bis, wer
               FROM sicherheit_ereignisse
              WHERE ' . implode(' AND ', $wo) . '
           ORDER BY zeitpunkt DESC
              LIMIT ' . max(1, min(500, $grenze)));
        $st->execute($args);
        $aus = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
            $aus[] = ['art'       => (string)$z['art'],
                      'topf'      => $z['topf'] !== null ? (string)$z['topf'] : null,
                      'merkmal'   => $z['merkmal'] !== null ? (string)$z['merkmal'] : null,
                      'stufe'     => (int)($z['stufe'] ?? 0),
                      'versuche'  => $z['versuche'] !== null ? (int)$z['versuche'] : null,
                      'zeitpunkt' => (string)$z['zeitpunkt'],
                      'bis'       => $z['bis'] !== null ? (string)$z['bis'] : null,
                      'wer'       => $z['wer'] !== null ? (string)$z['wer'] : null];
        }

        $zaehl = db()->prepare('SELECT COUNT(*) FROM sicherheit_ereignisse
                                 WHERE ' . implode(' AND ', $wo));
        $zaehl->execute($args);
        return ['zeilen' => $aus, 'gesamt' => (int)$zaehl->fetchColumn()];
    } catch (Throwable $ex) {
        /* Tabelle fehlt (Migration steht aus) — eine leere Liste ist hier die
         * richtige Antwort: Die Seite zeigt dann ihren Leerzustand und nicht
         * eine Fehlerseite. */
        return ['zeilen' => [], 'gesamt' => 0];
    }
}

/**
 * Geraete mit abgewiesenen Anmeldungen — die Treffer der Mengenbremse
 * (P5a/AP7).
 *
 * SIE STEHT HIER UND NICHT IN `status_lib.php`, obwohl die Statusseite
 * dieselbe Frage stellt: Zwei Abfragen auf dieselben zwei Spalten laufen
 * auseinander, sobald eine von beiden eine Bedingung dazubekommt. Die
 * Statuszeile nimmt seit P5a/AP8 diese Funktion.
 *
 * @return list<array{id:int, name:string, anzahl:int, seit:?string}>
 */
function sicherheit_bremse_geraete(int $grenze = 50): array
{
    try {
        $st = db()->query(
            'SELECT id, label, device_id, abgewiesen_anzahl, abgewiesen_seit
               FROM devices
              WHERE abgewiesen_anzahl > 0
           ORDER BY abgewiesen_anzahl DESC
              LIMIT ' . max(1, min(200, $grenze)));
        $aus = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
            $name = trim((string)($z['label'] ?? ''));
            $aus[] = ['id'     => (int)$z['id'],
                      'name'   => $name !== '' ? $name : (string)$z['device_id'],
                      'anzahl' => (int)$z['abgewiesen_anzahl'],
                      'seit'   => $z['abgewiesen_seit'] !== null
                                  ? (string)$z['abgewiesen_seit'] : null];
        }
        return $aus;
    } catch (Throwable $ex) {
        /* Die beiden Spalten gibt es erst nach der Migration aus P5a/AP7. */
        return [];
    }
}

/**
 * Der Stand der Mailregel — an/aus, wann zuletzt, an wen (E-P5a-07, R83).
 *
 * @return array{an:bool, zuletzt:?string, ziele:list<string>, stufe:int}
 */
function sicherheit_mailregel(): array
{
    $marke = function_exists('app_state_lesen') ? app_state_lesen(RATE_K_MAIL_MARK) : null;
    $ziele = [];
    try {
        require_once __DIR__ . '/mail_lib.php';
        $ziele = mail_betriebsziele();
    } catch (Throwable $ex) {
        /* Ohne Datenbank keine Empfaengerliste — die Karte sagt dann nur
         * an/aus, und das ist mehr als nichts. */
    }
    return [
        'an'      => rate_mail_an(),
        'zuletzt' => is_string($marke) && $marke !== '' ? $marke : null,
        'ziele'   => $ziele,
        'stufe'   => rate_stufe_hoechste(),
    ];
}

/* ===========================================================================
 * DIE SAMMELMAIL (E-P5a-07)
 * ======================================================================== */

/** Schluessel in `app_state`. */
const RATE_K_MAIL      = 'ratenschutz_mail';        // '0' schaltet sie ab
const RATE_K_MAIL_MARK = 'ratenschutz_mail_last';   // Zeitpunkt der letzten
const RATE_K_BREMSE_ST = 'ratenschutz_bremse_stufe';// zuletzt vermerkte Stufe

/** Ist die Sammelmail eingeschaltet? Vorgabe: ja. */
function rate_mail_an(): bool
{
    $v = function_exists('app_state_lesen') ? app_state_lesen(RATE_K_MAIL) : null;
    return $v !== '0';
}

/**
 * Die Stufe der Verlangsamung protokollieren, wenn sie GESTIEGEN ist
 * (P5a/AP8).
 *
 * WARUM EIGENSTAENDIG UND NICHT IN `sicherheit_melden_pruefen()`: Jene
 * kehrt zurueck, wenn die Sammelmail abgeschaltet ist — das Protokoll haengt
 * aber nicht am Versand. Eine Betreiberin, die keine Mail will, will
 * trotzdem auf der Sicherheitsseite sehen, dass die Anmeldung heute Nacht
 * eine Stunde lang verlangsamt war.
 *
 * NUR BEIM STEIGEN. Ein Ereignis je Fehlversuch waere ein Protokoll, das
 * seinen eigenen Gegenstand zudeckt: Unter Beschuss laeuft dieser Weg
 * mehrmals je Sekunde.
 *
 * DAS FALLEN WIRD NICHT PROTOKOLLIERT, nur vermerkt. „Die Bremse ist wieder
 * aus" ist kein Sicherheitsereignis; es ist das Ende eines Ereignisses, das
 * schon dasteht. Ohne den Vermerk aber koennte dieselbe Stufe nie wieder
 * anschlagen — deshalb wird `RATE_K_BREMSE_ST` in beide Richtungen
 * fortgeschrieben.
 */
function sicherheit_verlangsamung_vermerken(): void
{
    try {
        $v = rate_verlangsamung();
        $zuletzt = (int)(app_state_lesen(RATE_K_BREMSE_ST) ?? '0');
        if ($v['stufe'] > $zuletzt) {
            rate_ereignis('verlangsamung', 'global', RATE_GLOBAL_MERKMAL,
                          $v['stufe'], $v['versuche']);
            app_state_setzen(RATE_K_BREMSE_ST, (string)$v['stufe']);
        } elseif ($v['stufe'] < $zuletzt) {
            app_state_setzen(RATE_K_BREMSE_ST, (string)$v['stufe']);
        }
    } catch (Throwable $ex) {
        /* Wie unten: ein Protokoll, das nicht geschrieben werden kann, darf
         * die Anmeldung nicht mitreissen. */
        system_melden('ratenschutz', 'Verlangsamungsstufe nicht vermerkbar', $ex);
    }
}

/**
 * Melden, wenn etwas zu melden ist — hoechstens eine Mail je Stunde.
 *
 * AUFGERUFEN AN ZWEI STELLEN: wenn eine Sperre die hoechste Sprosse erreicht
 * (`rate_misserfolg()`), und wenn die Verlangsamung greift (`login.php`).
 * Beide Wege enden hier, damit die Stundenregel EINE ist.
 *
 * DIE MARKE STEHT VOR DEM VERSAND. Bei der Sicherungserinnerung
 * (`adminbackup_lib.php`) ist das damit begruendet, dass die doppelte Mail
 * der teurere Fehler ist. Hier waere es umgekehrt — eine Sicherheitsmeldung,
 * die nicht ankommt, ist schlimmer als zwei —, und trotzdem steht die Marke
 * vorn: Seit Web 20.9.0 geht der Versand ueber die WARTESCHLANGE. Ein
 * gescheiterter Versuch ist nicht verloren, sondern eingereiht. Damit gibt es
 * keinen Grund mehr, das Risiko einer Mailflut einzugehen — unter Beschuss
 * liefe diese Funktion sonst mehrmals je Sekunde.
 *
 * DIE VERLANGSAMUNGSSTUFE WIRD VERMERKT, und zwar nur, wenn sie STEIGT.
 * Sonst stuende nach einer Stunde Angriff ein Ereignis je Fehlversuch in der
 * Tabelle — das waere ein Protokoll, das seinen eigenen Gegenstand zudeckt.
 *
 * DAS PROTOKOLL HAENGT SEIT WEB 20.12.0 NICHT MEHR AN DER MAIL (P5a/AP8).
 * Bis dahin stand `if (!rate_mail_an()) { return; }` als ERSTE Zeile dieser
 * Funktion — und damit hing das Vermerken der Verlangsamungsstufe am
 * Mailschalter. Wer die Sammelmail abschaltete, weil er sie nicht braucht,
 * schaltete stillschweigend auch das Protokoll ab: Die Karte
 * „Verlangsamungsphasen" auf der Sicherheitsseite waere auf einer solchen
 * Installation dauerhaft leer geblieben, ohne dass irgendwo stuende, warum.
 * Protokollieren und Melden sind zwei Dinge; sie stehen jetzt in zwei
 * Funktionen.
 */
function sicherheit_melden_pruefen(): void
{
    sicherheit_verlangsamung_vermerken();
    if (!rate_mail_an()) { return; }

    try {
        $pdo = db();

        /* Hoechstens eine Mail je Stunde. */
        $marke = app_state_lesen(RATE_K_MAIL_MARK);
        if (is_string($marke) && $marke !== '') {
            $stempel = strtotime($marke . ' UTC');
            if ($stempel !== false && (time() - $stempel) < 3600) { return; }
        }

        /* 3. Was gab es in der letzten Stunde? */
        $hoechste = rate_stufe_hoechste();
        $st = $pdo->prepare(
            "SELECT art, topf, merkmal, stufe, versuche, zeitpunkt
               FROM sicherheit_ereignisse
              WHERE zeitpunkt >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR)
                AND ((art = 'sperre' AND stufe >= ?) OR art = 'verlangsamung')
           ORDER BY zeitpunkt");
        $st->execute([$hoechste]);
        $zeilen = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$zeilen) { return; }

        $konten = 0; $adressen = 0; $bremse = null;
        foreach ($zeilen as $z) {
            if ($z['art'] === 'verlangsamung') { $bremse = $z; continue; }
            if (str_starts_with((string)$z['merkmal'], 'id:')) { $konten++; } else { $adressen++; }
        }
        $sperren = $konten + $adressen;
        if ($sperren === 0 && $bremse === null) { return; }

        /* 4. Marke, dann Versand. */
        app_state_setzen(RATE_K_MAIL_MARK, gmdate('Y-m-d H:i:s'));

        $teile = [];
        if ($sperren > 0) {
            $teile[] = $sperren . ($sperren === 1 ? ' Sperre' : ' Sperren')
                     . ' der höchsten Stufe: '
                     . ($adressen > 0 ? $adressen . ' Adresse' . ($adressen === 1 ? '' : 'n') : '')
                     . ($adressen > 0 && $konten > 0 ? ', ' : '')
                     . ($konten > 0 ? $konten . ' Konto' . ($konten === 1 ? '' : 'nkennung') : '');
        }
        if ($bremse !== null) {
            $teile[] = 'Verlangsamung Stufe ' . (int)$bremse['stufe']
                     . ' seit ' . fmt_local((string)$bremse['zeitpunkt'], 'H:i') . ' Uhr'
                     . ' (' . (int)$bremse['versuche'] . ' Fehlversuche je 15 Minuten)';
        }

        $kern = "In der letzten Stunde:\n\n  " . implode("\n  ", $teile) . "\n\n"
              . "Das heißt nicht, dass jemand hereingekommen ist — es heißt, dass\n"
              . "es jemand versucht. Die Anwendung hat die Versuche abgewiesen und\n"
              . "antwortet langsamer, solange es anhält.\n\n"
              . "Wer betroffen ist und wie lange die Sperren noch laufen, steht unter\n"
              . "Betrieb → Status. Diese Meldung kommt höchstens einmal je Stunde und\n"
              . "lässt sich unter Betrieb → Servereinstellungen abschalten.";

        require_once __DIR__ . '/mail_lib.php';
        foreach (mail_betriebsziele() as $ziel) {
            mail_einreihen('sicherheit_sammel', $ziel, ['kern' => $kern]);
        }
    } catch (Throwable $ex) {
        /* Die Tabelle fehlt (Migration steht aus) oder die Datenbank ist weg.
         * Eine Meldung, die nicht hinausgeht, darf die Anmeldung nicht
         * mitreissen — sie ist ein Hinweis, nicht der Vorgang. */
        system_melden('ratenschutz', 'Sicherheitsmeldung nicht möglich', $ex);
    }
}

/* ------------------------------------------------- Demo-Konto (E-P1-20) --
 *
 * Zwei Toepfe, weil zwei verschiedene Fragen dahinterstehen:
 *
 *   demo   je IP-Adresse  — "nutzt EINE Stelle das Konto uebermaessig?"
 *   demog  global         — "wird das Konto insgesamt ueberrannt?"
 *
 * Ein einziger Topf mit beiden Merkmalen ginge nicht: Die Grenzen sind
 * verschieden (20 gegen 300), und RATE_GRENZEN haengt am Topf, nicht am
 * Merkmal.
 *
 * Das globale Merkmal ist eine feste Zeichenkette. Sie kann mit keinem
 * IP-Merkmal kollidieren, weil jene mit 'ip:' beginnen.
 */

/* Das globale Merkmal wird AUSDRUECKLICH uebergeben, nicht ueber
 * rate_merkmale() gebildet: Jene Funktion haengt die IP-Adresse immer an. Fuer
 * den globalen Topf hiesse das eine zweite, nutzlose Zeile je Adresse — ein
 * Zaehler, der bei 300 je IP sperren wuerde und damit nie vor dem Topf `demo`
 * greift, der schon bei 20 sperrt. Er stuende nur in der Tabelle herum. */
const RATE_DEMO_GLOBAL = ['alle'];

/** Darf sich jetzt jemand am Demo-Konto anmelden? */
function rate_demo_erlaubt(): bool
{
    return rate_erlaubt('demo') && rate_erlaubt('demog', null, RATE_DEMO_GLOBAL);
}

/**
 * Eine GELUNGENE Anmeldung am Demo-Konto verbuchen.
 *
 * Aufruf NACH der erfolgreichen Pruefung — anders als bei den uebrigen
 * Toepfen, wo gezaehlt wird, was scheitert. Deshalb steht hier auch kein
 * rate_erfolg(): Ein Erfolg leert den Zaehler nicht, er fuellt ihn.
 */
function rate_demo_zaehlen(): void
{
    rate_zaehlen('demo');
    rate_zaehlen('demog', null, RATE_DEMO_GLOBAL);
}


/* ---- Registrierung (P5b/AP3) --------------------------------------------- */

/* Wie RATE_DEMO_GLOBAL, und aus demselben Grund ausdruecklich uebergeben. */
const RATE_REG_GLOBAL = ['alle'];

/**
 * Das Merkmal fuer den Topf je Zieladresse.
 *
 * NIE DIE ADRESSE IM KLARTEXT. `rate_limits` steht in derselben Datenbank
 * wie alles andere; eine Spalte mit fremden Postfaechern waere ein
 * Verzeichnis, das es ohne diesen Topf nicht gaebe. Der Hash reicht
 * vollstaendig aus — gezaehlt wird Gleichheit, nicht Inhalt.
 *
 * Kleingeschrieben und getrimmt, damit `Name@Klinik.de` und `name@klinik.de`
 * denselben Zaehler treffen; sonst waere der Topf mit einem Grossbuchstaben
 * zu umgehen.
 */
function rate_reg_ziel(string $email): array
{
    return ['ziel:' . hash('sha256', mb_strtolower(trim($email)))];
}

/** Darf jetzt ueberhaupt jemand registrieren — unabhaengig von der Adresse? */
function rate_reg_erlaubt(): bool
{
    return rate_erlaubt('reg') && rate_erlaubt('regg', null, RATE_REG_GLOBAL);
}

/** Darf an DIESE Adresse noch eine Registrierungsmail gehen? */
function rate_reg_ziel_erlaubt(string $email): bool
{
    return rate_erlaubt('regz', null, rate_reg_ziel($email));
}

/**
 * Einen Registrierungsversuch verbuchen.
 *
 * NACH dem Versuch, wie bei der Demo-Anmeldung und anders als bei den
 * Fehlversuchstoepfen: Gezaehlt wird die MENGE, nicht das Scheitern. Der
 * Zieltopf zaehlt nur, wenn tatsaechlich eine Mail hinausgeht — sonst
 * koennte man ein fremdes Postfach sperren, indem man es dreimal eintippt,
 * und der Besitzer kaeme selbst nicht mehr durch.
 */
function rate_reg_zaehlen(string $email, bool $mailGeht): void
{
    rate_zaehlen('reg');
    rate_zaehlen('regg', null, RATE_REG_GLOBAL);
    if ($mailGeht) {
        rate_zaehlen('regz', null, rate_reg_ziel($email));
    }
}

/** Bis wann ist gesperrt? Fuer die Meldung an der Anmeldeseite. */
function rate_demo_gesperrt_bis(): ?string
{
    return rate_gesperrt_bis('demo') ?? rate_gesperrt_bis('demog', null, RATE_DEMO_GLOBAL);
}
