<?php
declare(strict_types=1);
/**
 * Was fuer ein Geraet hat sich gekoppelt (R42, Zwischenpaket S6).
 *
 * WOZU ES DIESE DATEI GIBT. Beim Koppeln schickt das Geraet einen Block
 * `geraet` mit (JSON-Vertrag, Abschnitt 1a). Er ist FREIWILLIG, er kommt in
 * ZWEI Formen, und er ist eine Auskunft des Geraets ueber sich selbst — keine
 * gepruefte Wahrheit. Alle drei Eigenschaften wollen an einer Stelle behandelt
 * sein und nicht in `pair.php` zwischen Ratenschutz und Mailversand:
 *
 *   Freiwillig   Ein fehlender, halber oder unsinniger Block darf die
 *                Kopplung NIE zum Scheitern bringen. Es ist eine
 *                Statistikangabe; die Uhr steht derweil in der Hand einer
 *                Notaerztin, die koppeln will.
 *   Zwei Formen  Die Garmin-Uhr kennt ihren Modellnamen nicht und sendet die
 *                Teilenummer; das Handy kennt ihn und sendet Hersteller und
 *                Modell. Beides muss auf dieselben drei Spalten fallen.
 *   Auskunft     Was ankommt, wird zugeschnitten und gefiltert, nicht
 *                geglaubt: Laenge, Zeichenvorrat, erlaubte Werte.
 *
 * DIE DREI SPALTEN an `devices` (Migration 2026_09_02_geraetekennung):
 *
 *   geraet_art     'uhr' | 'handy' | 'sonstiges' | NULL
 *   geraet_modell  aufgeloester Klarname ('Venu 3S', 'Google Pixel 8') | NULL
 *   geraet_teil    Rohangabe des Geraets, unveraendert                 | NULL
 *
 * WARUM DIE ROHANGABE DANEBEN STEHENBLEIBT. `geraet_modell` entsteht bei der
 * Uhr aus einer Tabelle (`geraetemodelle.php`), und die kennt nur, was es beim
 * Erzeugen schon gab. Ohne die Rohangabe fiele jedes kuenftige Garmin-Geraet
 * dauerhaft auf "unbekannt" — und zwar unwiederbringlich, weil die Teilenummer
 * dann nirgends mehr stuende. Mit ihr laesst sich jede Zeile spaeter erneut
 * aufloesen — und zwar wirklich:
 * `php tools/geraetemodelle/nachaufloesen.php` tut genau das (E-S6-6). Ohne
 * dieses Werkzeug waere die dritte Spalte eine Zusage ohne Programm; besonders
 * `geraet_art` haengt daran, denn dort steht bis zum Nachaufloesen die
 * ungepruefte Selbstauskunft. Sie ist ausserdem die einzige Spalte, die die BEHAUPTUNG des
 * Geraets festhaelt; `geraet_modell` ist bereits eine Auslegung des Servers.
 *
 * WAS HIER BEWUSST NICHT ANKOMMT: `uniqueIdentifier` (Uhr) und `ANDROID_ID`,
 * IMEI, Seriennummer (Handy). Die Clients senden sie gar nicht erst — fuer
 * eine Stueckzahl-Statistik nicht noetig, und in einer kleinen Gruppe ein
 * Personenbezug mehr, als die Frage rechtfertigt (JSON-Vertrag 1a). Auch
 * Displaymasse, Firmware und Plattformfassung werden hier NICHT gespeichert,
 * obwohl die Clients sie senden: R36 laesst die Geraetekennung als die eine
 * benannte Ausnahme zu — "welches Geraet", nicht "in welchem Zustand".
 */
/* Die erzeugte Modelltabelle. Die Abfrage davor ist kein Zierrat: Sie laesst
 * eine Probe eine EIGENE Tabelle setzen, bevor diese Datei geladen wird
 * (tools/geraeteprobe/). Ohne sie liefe jede Probe der Aufloesung gegen den
 * jeweils ausgelieferten Bestand — und was heute gruen ist, waere nach dem
 * naechsten Lauf des Erzeugers rot, ohne dass sich am Code etwas geaendert
 * haette. */
if (!defined('GERAETE_MODELLE')) { require_once __DIR__ . '/geraetemodelle.php'; }

/**
 * Die erlaubten Geraetearten (R42).
 *
 * Eine Whitelist und kein ENUM in der Datenbank: Ein ENUM braucht fuer jede
 * neue Art eine Migration. Was hier nicht drinsteht, wird zu NULL — es wird
 * NICHT durchgereicht. Sonst stuende in der Spalte, was ein Client
 * hineinschreiben mag, und die spaetere Auswertung zaehlte Schreibweisen.
 */
const GERAET_ARTEN = ['uhr', 'handy', 'sonstiges'];

/** Laengengrenzen der drei Spalten (schema.sql). */
const GERAET_MAX_ART    = 16;
/* 191 UND NICHT 64. Die 64 stand hier zuerst und war geraten — die
 * Gerätedateien waren noch nicht da. Sie liefern SAMMELNAMEN: Eine Teilenummer
 * bezeichnet die Hardware, und Garmin verkauft dieselbe Hardware unter
 * mehreren Namen ("fēnix® 6X Pro / 6X Sapphire / … / quatix® 6X Dual Power",
 * 156 Zeichen). 5 der 173 Modelle sind länger als 64. Der volle Name wird
 * gespeichert, weil er die Auskunft der Gerätedateien IST und die spätere
 * Zählung (P5) genau diese Hardwaregruppen zählen soll; gekürzt wird für die
 * ANZEIGE, siehe geraet_bezeichnung(). */
const GERAET_MAX_MODELL = 191;
const GERAET_MAX_TEIL   = 64;

/**
 * Liest den Block `geraet` einer Kopplungsanfrage.
 *
 * @param  mixed $block  Was `json_decode` geliefert hat — irgendetwas.
 * @return array{art: ?string, modell: ?string, teil: ?string}
 *
 * WIRFT NIE UND GIBT IMMER DREI SCHLUESSEL ZURUECK. Der Aufrufer soll die
 * Rueckgabe ohne Fallunterscheidung in die Spalten schreiben koennen; jede
 * Pruefung, die er selbst noch machen muesste, waere eine, die er vergessen
 * kann. Ist nichts Brauchbares dabei, sind alle drei null — und "unbekannt"
 * ist dann eine Sache der Anzeige, nicht der Spalte.
 */
function geraet_block_lesen(mixed $block): array
{
    $leer = ['art' => null, 'modell' => null, 'teil' => null];
    if (!is_array($block)) { return $leer; }

    /* ---- Art ---------------------------------------------------------- */
    $art = _geraet_text($block['art'] ?? null, GERAET_MAX_ART);
    if ($art !== null) {
        $art = strtolower($art);
        if (!in_array($art, GERAET_ARTEN, true)) { $art = null; }
    }

    /* ---- Rohangabe: die Uhr-Form geht vor -----------------------------
     *
     * `teil` ist die Teilenummer der Garmin-Uhr ("006-B4261-00") und der
     * eigentliche Schluessel — sie ist eindeutig und aufloesbar. Das Handy
     * sendet dort ausdruecklich `null` und stattdessen `hersteller` und
     * `modell` (E-S4-28). Die Reihenfolge ist deshalb: erst `teil`, dann der
     * Handy-Zuschnitt. Ein Geraet, das beides schickt, ist keine der beiden
     * bekannten Formen; dann gilt die Teilenummer, weil sie die praezisere
     * Angabe ist.
     *
     * HERSTELLER UND MODELL WERDEN ZUSAMMENGEZOGEN und nicht getrennt
     * gespeichert. Zwei Gruende: Die Spalte haelt die Rohangabe EINES
     * Geraets, gleich welcher Art — eine Spalte `hersteller`, die bei jeder
     * Uhr leer bliebe, waere eine Spalte fuer den Sonderfall. Und
     * `Build.MANUFACTURER` ist ohne `Build.MODEL` wertlos ("google" allein
     * beantwortet nichts). */
    $teil = _geraet_text($block['teil'] ?? null, GERAET_MAX_TEIL);
    $ausHandyForm = false;
    if ($teil === null) {
        $ausHandyForm = true;
        $hersteller = _geraet_text($block['hersteller'] ?? null, GERAET_MAX_TEIL);
        $modellRoh  = _geraet_text($block['modell'] ?? null, GERAET_MAX_TEIL);
        /* Doppelung vermeiden: Xiaomi und Samsung tragen den Herstellernamen
         * teils schon im Modellnamen ("Xiaomi 14"). "Xiaomi Xiaomi 14" waere
         * in der spaeteren Auswertung eine eigene Zeile. */
        if ($hersteller !== null && $modellRoh !== null
            && stripos($modellRoh, $hersteller) === 0) {
            $hersteller = null;
        }
        $teil = _geraet_kuerzen(trim(($hersteller ?? '') . ' ' . ($modellRoh ?? '')),
                                GERAET_MAX_TEIL);
    }

    /* ---- Modell: aufloesen, sonst leer lassen --------------------------
     *
     * NICHT die Rohangabe hier hineinschreiben. `geraet_modell` sagt "so
     * heisst das Geraet"; eine Teilenummer sagt das nicht. Wer die Spalte
     * spaeter zaehlt, bekaeme sonst eine Statistik, in der "Venu 3S" und
     * "006-B4261-00" zwei Geraete sind. Die Anzeige faellt fuer den einzelnen
     * Fall auf `geraet_teil` zurueck (geraet_bezeichnung()) — das ist etwas
     * anderes als eine gezaehlte Zeile. */
    $modell = null;
    if ($teil !== null) {
        $treffer = geraet_modell_aufloesen($teil);
        if ($treffer !== null) {
            $modell = _geraet_kuerzen($treffer['modell'], GERAET_MAX_MODELL);
            /* DIE TABELLE SCHLAEGT DIE SELBSTAUSKUNFT — aber nur bei der Art,
             * und nur wenn sie den Teil kennt. Die Garmin-App sendet `art`
             * fest als "uhr" (Pair.mc), weil eine Connect-IQ-App nur auf
             * Garmin-Geraeten laeuft; unterscheiden kann sie Uhr und
             * Radcomputer nicht. Die Geraetedateien koennen es. Ein Edge, der
             * sich "uhr" nennt, wuerde die Statistik sonst still verfaelschen. */
            if ($treffer['art'] !== null) { $art = $treffer['art']; }
        } elseif ($ausHandyForm) {
            /* Das Handy kennt seinen Modellnamen selbst — dort ist die
             * Rohangabe zugleich der Klarname, und es gibt nichts
             * aufzuloesen. Genommen wird die zusammengezogene Fassung, damit
             * "Pixel 8" und "Google Pixel 8" nicht zwei Zeilen werden.
             *
             * NUR IN DIESEM ZWEIG. Eine unaufgeloeste TEILENUMMER darf hier
             * nicht landen: Sie ist kein Modellname, und in der spaeteren
             * Zaehlung staende "006-B4261-00" dann neben "Venu 3S" als
             * eigenes Geraet. */
            $modell = _geraet_kuerzen($teil, GERAET_MAX_MODELL);
        }
    }

    return ['art' => $art, 'modell' => $modell, 'teil' => $teil];
}

/**
 * Loest eine Garmin-Teilenummer auf Modellnamen und Geraeteart auf.
 *
 * @return array{modell: string, art: ?string}|null  null = nicht in der Tabelle
 *
 * Die Tabelle steht in `geraetemodelle.php` und ist ERZEUGT
 * (`tools/geraetemodelle/`). Sie ist absichtlich stumpf: ein Schluessel, ein
 * Wert, keine Musterzerlegung der Teilenummer. Die Struktur "006-BXXXX-00"
 * sieht nach einem Muster aus, ist aber keines, aus dem sich ein Modellname
 * herleiten liesse — nur die Gerätedateien wissen es.
 */
function geraet_modell_aufloesen(string $teil): ?array
{
    $schluessel = strtoupper(trim($teil));
    $eintrag = GERAETE_MODELLE[$schluessel] ?? null;
    if ($eintrag === null) { return null; }
    return ['modell' => $eintrag[0], 'art' => $eintrag[1] ?? null];
}

/**
 * Die Geraeteart als sichtbarer Text — oder null, wenn nichts bekannt ist.
 *
 * Eine Stelle fuer beide Geraetelisten (Einstellungen und Adminbereich).
 * Standen die Woerter dort zweimal, hiesse dasselbe Geraet frueher oder
 * spaeter an zwei Stellen verschieden.
 */
function geraet_art_text(?string $art): ?string
{
    return match ($art) {
        'uhr'       => 'Uhr',
        'handy'     => 'Handy',
        'sonstiges' => 'Sonstiges',
        default     => null,
    };
}

/**
 * Der Name, unter dem ein frisch gekoppeltes Geraet in der Liste steht.
 *
 * BIS WEB 12.9.0 STAND DORT IMMER "Uhr". Das war richtig, solange nur
 * Garmin-Uhren koppeln konnten; seit der Handy-App (S4) hiess ein Handy in
 * der Geraeteliste "Uhr", bis jemand es umbenannte. Der Name ist frei
 * waehlbar und bleibt es — das hier ist nur die Vorgabe.
 *
 * OHNE ANGABE BLEIBT ES "Uhr", mit Absicht: Ein Geraet, das keinen Block
 * schickt, ist eine Uhr-Fassung vor 1.9.0 — etwas anderes konnte damals nicht
 * koppeln. "Geraet" waere hier nicht vorsichtiger, sondern nur unschaerfer.
 */
function geraet_vorgabename(?string $art): string
{
    return match ($art) {
        'handy'     => 'Handy',
        'sonstiges' => 'Gerät',
        default     => 'Uhr',
    };
}

/**
 * Die Zeile "Art · Modell" fuer die Geraetelisten.
 *
 * DIE ROHANGABE ERSCHEINT, WENN DER KLARNAME FEHLT. Eine Teilenummer ist eine
 * magere Auskunft, aber sie ist eine — und sie sagt der Betrachterin
 * ausserdem, dass hier etwas gekoppelt hat, das die Modelltabelle noch nicht
 * kennt. "Modell unbekannt" haette das verschwiegen.
 */
function geraet_bezeichnung(?string $art, ?string $modell, ?string $teil): string
{
    $artText = geraet_art_text($art);
    $name    = ($modell !== null && $modell !== '') ? geraet_modell_kurz($modell)
             : (($teil !== null && $teil !== '') ? $teil : null);

    if ($artText !== null && $name !== null) { return $artText . ' · ' . $name; }
    if ($artText !== null)                   { return $artText . ' · Modell unbekannt'; }
    if ($name !== null)                      { return $name; }
    return 'Gerät unbekannt';
}

/**
 * Die oeffentliche Geraetekennung, gekuerzt fuer eine Listenzeile.
 *
 * WARUM GEKUERZT. Die Kennung ist seit Web 4.5.1 36 Zeichen lang
 * ("dev-" + 32 Hexziffern) und enthaelt kein Trennzeichen, an dem ein Browser
 * umbrechen koennte. Als Plakette in einer Zeile setzt sie damit eine
 * Mindestbreite durch, die dem Text daneben nichts uebrig laesst: Bei einem
 * kurz benannten Geraet ("Uhr", "Handy" — genau die Vorgabe nach dem Koppeln)
 * fiel die Kleinzeile auf ein Wort je Zeile zusammen. Im Bilderlauf zu S6 bei
 * 1024 px gesehen, und zwar auch am Stand VOR S6 — die laengere Kleinzeile hat
 * es nur sichtbar gemacht.
 *
 * ACHT ZEICHEN GENUEGEN. Wozu die Kennung in der Liste da ist: zwei Geraete
 * mit derselben Bezeichnung auseinanderhalten und eine Zeile in einer
 * Rueckfrage benennen. Dafuer reichen 32 Bit; die letzten beiden Zeichen
 * stehen dahinter, damit ein Vergleich mit der Kopplungs-E-Mail an beiden
 * Enden anfassen kann. Dieselbe Form benutzt der Adminbereich seit Web 9.7.2
 * (Mockup 40) — sie stand dort nur als Rechnung im Seitenquelltext und
 * gehoert an EINE Stelle, damit beide Listen dieselbe Kennung zeigen.
 *
 * Kurze Kennungen aus dem Altbestand (4 Zufallsbytes, 12 Zeichen) bleiben
 * unveraendert stehen — sie sind bereits kurz genug.
 */
function geraet_kennung_kurz(string $kennung): string
{
    if (mb_strlen($kennung) <= 12) { return $kennung; }
    return mb_substr($kennung, 0, 8) . '…' . mb_substr($kennung, -2);
}

/**
 * Ein Sammelname, gekuerzt auf sein erstes Glied.
 *
 * WAS EIN SAMMELNAME IST. Die Gerätedateien fuehren je Teilenummer die
 * HARDWARE, und Garmin verkauft dieselbe Hardware unter mehreren Namen. Aus
 * einer Kopplung kommt deshalb "fēnix® 6X Pro / 6X Sapphire / 6X Pro Solar /
 * tactix® Delta Sapphire / … / quatix® 6X Dual Power" — 156 Zeichen fuer EIN
 * Geraet. In einer Listenzeile ist das unbrauchbar; in der Spalte ist es
 * richtig, weil die spaetere Zaehlung Hardwaregruppen zaehlen soll und nicht
 * Verkaufsnamen.
 *
 * DAS ERSTE GLIED UND NICHT DAS KUERZESTE: Die Datei nennt die Namen in
 * Garmins Reihenfolge, und die beginnt mit dem gelaeufigsten. Das Auslassungs-
 * zeichen sagt, dass mehr dahintersteht — ohne es lese sich "fēnix® 6X Pro"
 * wie eine genaue Angabe, und das waere sie nicht.
 */
function geraet_modell_kurz(string $modell): string
{
    $erstes = explode(' / ', $modell, 2);
    return count($erstes) === 2 ? rtrim($erstes[0]) . ' …' : $modell;
}

/**
 * Zuschneiden einer Angabe des Geraets: Zeichenkette, getrimmt, ohne
 * Steuerzeichen, auf die Spaltenbreite gekuerzt — oder null.
 *
 * STEUERZEICHEN FLIEGEN RAUS, nicht weil ein Zeilenumbruch gefaehrlich waere
 * (die Ausgabe geht durch e()), sondern weil er eine Listenzeile zerreisst und
 * in einer Auswertung zwei scheinbar gleiche Werte ungleich macht.
 */
function _geraet_text(mixed $wert, int $max): ?string
{
    if (!is_string($wert) && !is_int($wert)) { return null; }
    return _geraet_kuerzen((string)$wert, $max);
}

/** Wie _geraet_text(), fuer bereits zusammengesetzte Zeichenketten. */
function _geraet_kuerzen(string $wert, int $max): ?string
{
    $wert = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $wert) ?? '';
    $wert = trim(preg_replace('/\s+/u', ' ', $wert) ?? '');
    if ($wert === '') { return null; }
    /* mb_substr und nicht substr: Ein Modellname darf Umlaute tragen, und ein
     * an der falschen Stelle abgeschnittenes UTF-8-Zeichen macht die Spalte
     * unlesbar. */
    return mb_substr($wert, 0, $max);
}
