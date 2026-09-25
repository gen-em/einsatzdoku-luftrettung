<?php
declare(strict_types=1);

/* Der eine Leser fuer config.php (Schritt 15 AP2). Laedt selbst nichts. */
require_once __DIR__ . '/konfig_lib.php';

/**
 * UMGEBUNG — das Etikett einer Anlage, die nicht die Produktivanlage ist
 * ===========================================================================
 *
 *     umgebung()           // ['name' => 'Staging', 'farbe' => 'rot', …] oder null
 *     umgebung_praefix()   // '[Staging] ' oder ''
 *
 * WOFUER (P5c/AP1, E-P5c-05, Backlog Nr. 243). Staging und Produktiv sahen
 * bis hierher gleich aus. Wer zwei Reiter offen hat, eines mit jeder Anlage,
 * sieht den Unterschied an der Adresszeile — und nur dort. Das Etikett macht
 * ihn an drei Stellen sichtbar: im Seitentitel, an der Farbe der Kopfleiste
 * und in einer Zeile ueber dem Inhalt.
 *
 * NIE ABGELEITET, SONDERN GESETZT. Kein Blick auf die Domain, den Zweig oder
 * die Kette: Eine Ableitung raet, und eine Anlage, die falsch geraten wird,
 * traegt das falsche Etikett mit voller Ueberzeugung. `config.php` sagt es,
 * oder es gibt kein Etikett — und dann verhaelt sich die Anlage wie die
 * Produktivanlage. Die Statusseite sagt beides (E-P5c-64).
 *
 * DIESE DATEI LAEDT NUR `konfig_lib.php`, und das ist Bedingung: `ui.php`
 * zieht sie in der Seitenhuelle nach, und die Seitenhuelle laeuft auch im
 * Einrichter, VOR `config.php` und ohne `db.php`. Ohne Datei gibt es dort
 * kein Etikett — richtig so, denn es gibt noch nichts, was es tragen koennte.
 */

/**
 * Die Farben, die ein Etikett tragen darf — eine GESCHLOSSENE Liste.
 *
 * Heute eine. Eine zweite braucht eine Regel im Stylesheet, einen Kontrast
 * gegen die Kopfleiste (`kontrast.py`) und eine Freigabe mit Mockup
 * (`CLAUDE.md` 5) — und damit mehr als einen Eintrag hier. Ein unbekannter
 * Wert wird deshalb nicht ignoriert, sondern ROT gezeichnet und auf der
 * Statusseite benannt (E-P5c-55): Wer „lila" eintraegt, will eine Kennzeichnung,
 * und keine Kennzeichnung waere die schlechtere Antwort.
 */
const UMGEBUNG_FARBEN = ['rot'];

/**
 * Das Etikett aus `config.php`, oder `null`, wenn keines gesetzt ist.
 *
 * @return array{name:string, farbe:string, farbe_roh:string, farbe_bekannt:bool}|null
 *
 * `farbe` ist die Farbe, in der gezeichnet wird — immer ein Eintrag aus
 * UMGEBUNG_FARBEN. `farbe_roh` ist, was in der Datei steht; die Statusseite
 * braucht es fuer ihren Satz („mit der Farbe ‚lila'").
 *
 * OHNE NAMEN KEIN ETIKETT. Ein Eintrag `['farbe' => 'rot']` ohne `name` ist
 * ein halber Eintrag; er faerbte die Kopfleiste und liesse den Titel leer.
 * Er zaehlt wie keiner.
 */
function umgebung(): ?array
{
    $roh = konfig('app.umgebung');
    if (!is_array($roh)) { return null; }
    $name = trim((string)($roh['name'] ?? ''));
    if ($name === '') { return null; }

    /* OHNE FARBE GILT DIE ERSTE DER LISTE. Heute gibt es nur eine, und wer
     * sie weglaesst, meint sie — eine Warnung dafuer waere Laerm. */
    $farbeRoh = trim((string)($roh['farbe'] ?? ''));
    if ($farbeRoh === '') { $farbeRoh = UMGEBUNG_FARBEN[0]; }
    $bekannt  = in_array($farbeRoh, UMGEBUNG_FARBEN, true);
    return [
        'name'          => $name,
        'farbe'         => $bekannt ? $farbeRoh : UMGEBUNG_FARBEN[0],
        'farbe_roh'     => $farbeRoh,
        'farbe_bekannt' => $bekannt,
    ];
}

/**
 * Der Vorsatz fuer den Seitentitel: „[Staging] ", sonst leer.
 *
 * Mit dem Leerzeichen, damit die Aufrufer nur verketten und keiner es
 * vergisst — dieselbe Ueberlegung wie bei `mail_praefix()`.
 */
function umgebung_praefix(): string
{
    $u = umgebung();
    return $u === null ? '' : '[' . $u['name'] . '] ';
}
