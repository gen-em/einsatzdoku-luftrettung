<?php
declare(strict_types=1);
/**
 * EINWILLIGUNGEN JE KONTO (P5b/AP4, E-P5b-05, -15).
 *
 * ---------------------------------------------------------------------------
 * DREI HAEKCHEN, ZWEI WIRKUNGEN
 * ---------------------------------------------------------------------------
 *
 *   Nutzungsbedingungen   „angenommen"            sperrt den naechsten Login
 *   Auftragsverarbeitung  „angenommen"            sperrt den naechsten Login
 *   Datenschutzerklaerung „zur Kenntnis genommen" zeigt einen Hinweis
 *
 * **Der Wortlaut traegt den rechtlichen Unterschied, die Form ist gleich**
 * (E-P5b-05). Eine Datenschutzerklaerung wird nicht „angenommen" — sie
 * informiert, und Widerspruch dagegen ist kein Vertragsschluss, sondern ein
 * Recht. Ein Vertrag dagegen kommt durch Annahme zustande, und ohne sie darf
 * er nicht weiterlaufen.
 *
 * DESHALB SPERRT DAS EINE UND DAS ANDERE NICHT. Das ist keine Abstufung nach
 * Wichtigkeit, sondern nach Rechtsnatur.
 *
 * ---------------------------------------------------------------------------
 * WAS „AKTUELLE FASSUNG" HEISST
 * ---------------------------------------------------------------------------
 *
 * `rechtstexte.stand_am`. Seit P5b/AP4 ist das ein `DATETIME` und nicht mehr
 * ein `DATE` — der Fehlerfund F3 des Konzepts: Zwei Aenderungen am selben Tag
 * waeren sonst EINE Fassung, und wer die erste angenommen hat, gaelte als
 * Annehmer der zweiten.
 *
 * `konto_einwilligungen.stand_am` haelt, WELCHE Fassung angenommen wurde. Der
 * Vergleich der beiden ist die ganze Pruefung.
 *
 * EIN TEXT OHNE `stand_am` VERLANGT NICHTS. Solange die Betreiberin kein
 * Standdatum gesetzt hat, gilt der Text als nicht in Kraft — sonst sperrte
 * ein leer angelegter Platzhalter alle Konten aus. Das ist der Zustand
 * zwischen dem Einspielen der Mechanik (AP4) und dem Einspielen der
 * geprueften Texte (R41, Zuarbeit).
 *
 * ---------------------------------------------------------------------------
 * DER BESTAND HOLT NACH
 * ---------------------------------------------------------------------------
 *
 * Kein Konto hat vor P5b eine Einwilligung abgegeben, weil es nichts gab,
 * wozu. Beim ersten Login nach dem Einspielen der Texte landen deshalb ALLE
 * am Tor — und das ist richtig und kein Versehen: Es gibt keinen Weg, eine
 * Annahme rueckwirkend anzunehmen.
 *
 * WAS AM TOR ERREICHBAR BLEIBT (E-P5b-05): Abmelden, Export, Konto loeschen.
 * Wer nicht zustimmen will, muss an seine Daten kommen und gehen koennen —
 * ein Tor, das auch den Ausgang versperrt, waere Noetigung.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/rechtstexte_lib.php';

/**
 * Was dieses Konto noch einwilligen muss.
 *
 * @return array{sperrt: array<string>, hinweis: array<string>}
 *         `sperrt`  — Schluessel, die den Login sperren (Art `annahme`)
 *         `hinweis` — Schluessel, die nur einen Hinweis zeigen (Art `kenntnis`)
 *
 * Beide Listen sind leer, wenn alles vorliegt. Sie sind **auch dann leer**,
 * wenn ein Text gar nicht hinterlegt ist oder kein Standdatum traegt — siehe
 * Kopf.
 */
function einwilligung_offen(int $userId): array
{
    $aus = ['sperrt' => [], 'hinweis' => []];

    try {
        /* EIN SELECT FUER ALLE, nicht einer je Schluessel. `rt_alle()` macht
         * es andersherum (ein SELECT je Text), und das ist dort in Ordnung —
         * dort laeuft es einmal je Seitenaufbau des Editors. Hier laeuft es
         * in `auth_guard.php`, also bei JEDER angemeldeten Anfrage. */
        $stand = [];
        foreach (db()->query('SELECT schluessel, stand_am FROM rechtstexte')
                     ->fetchAll(PDO::FETCH_ASSOC) as $z) {
            $stand[(string)$z['schluessel']] = $z['stand_am'];
        }

        $st = db()->prepare('SELECT schluessel, stand_am FROM konto_einwilligungen
                              WHERE user_id = ?');
        $st->execute([$userId]);
        $hat = $st->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Throwable $ex) {
        /* Tabelle fehlt (Migration noch nicht gelaufen) — dann verlangt
         * niemand etwas. Eine Anwendung, die nach einem Deploy alle
         * aussperrt, WEIL die Migration noch aussteht, waere das Gegenteil
         * von dem, was dieses Tor soll. */
        return $aus;
    }

    foreach (RT_EINWILLIGUNG as $schluessel => $art) {
        $aktuell = $stand[$schluessel] ?? null;
        if ($aktuell === null || $aktuell === '') { continue; }  // nicht in Kraft
        if (($hat[$schluessel] ?? null) === $aktuell) { continue; }  // liegt vor
        $aus[$art['art'] === 'annahme' ? 'sperrt' : 'hinweis'][] = $schluessel;
    }

    return $aus;
}

/**
 * Welche Einwilligungstexte sind IN KRAFT? Schluessel => `stand_am`.
 *
 * Gebraucht von `registrieren.php` (P5b, Backlog Nr. 223): Die Registrierung
 * darf nur verlangen, was es gibt. `einwilligung_offen()` beantwortet
 * dieselbe Frage fuer ein bestehendes Konto — hier gibt es noch keins, und
 * deshalb ist das eine eigene Funktion und kein Sonderfall der anderen.
 *
 * WARUM DAS NOETIG WAR. Bis Web 20.22.1 verlangte `registrieren.php` alle
 * Schluessel aus `RT_EINWILLIGUNG` als Pflichthaken, ohne `stand_am` auch nur
 * anzusehen. Solange die geprueften Texte nicht eingespielt sind — der
 * geplante Zustand bis E-P5b-24 —, musste eine Registrierende damit den Haken
 * „Ich nehme die Vereinbarung zur Auftragsverarbeitung (AVV) an" setzen,
 * waehrend `avv.php` „noch keine Vereinbarung zur Auftragsverarbeitung
 * hinterlegt." anzeigte. Sie nahm ein leeres Dokument an.
 *
 * Das Tor macht es seit jeher richtig (`einwilligung_offen()`, Zeile mit
 * „nicht in Kraft"); `docs/Technik.md` begruendet es: „Ein Text ohne
 * Standdatum verlangt nichts." Die Registrierung folgt dieser Regel jetzt
 * auch.
 *
 * FEHLT DIE TABELLE, ist nichts in Kraft — dieselbe Antwort wie bei
 * `einwilligung_offen()` und aus demselben Grund: Eine Anwendung, die nach
 * einem Deploy niemanden mehr registrieren laesst, WEIL die Migration noch
 * aussteht, waere das Gegenteil von dem, was dieses Tor soll.
 *
 * @return array<string, string> nur Schluessel mit gesetztem Standdatum
 */
function einwilligung_in_kraft(): array
{
    try {
        $stand = [];
        foreach (db()->query('SELECT schluessel, stand_am FROM rechtstexte')
                     ->fetchAll(PDO::FETCH_ASSOC) as $z) {
            $stand[(string)$z['schluessel']] = $z['stand_am'];
        }
    } catch (Throwable $ex) {
        return [];
    }

    $aus = [];
    foreach (RT_EINWILLIGUNG as $schluessel => $art) {
        $a = $stand[$schluessel] ?? null;
        if ($a === null || $a === '') { continue; }
        $aus[$schluessel] = (string)$a;
    }

    return $aus;
}

/**
 * Eine Einwilligung vermerken.
 *
 * Schreibt die Fassung, die GERADE gilt — nicht die, die die Seite beim
 * Anzeigen gelesen hat. Zwischen Anzeige und Klick kann die Betreiberin den
 * Text geaendert haben; dann hat die Nutzerin etwas anderes gesehen, als sie
 * annimmt. Der Ausweg ist nicht, die alte Fassung zu schreiben (dann stuende
 * eine Annahme fuer einen Text, den es nicht mehr gibt), sondern die neue zu
 * verlangen: Sie landet beim naechsten Aufruf wieder am Tor und sieht den
 * neuen Text.
 *
 * @return bool `false`, wenn der Text gar nicht in Kraft ist.
 */
function einwilligung_setzen(int $userId, string $schluessel): bool
{
    if (!isset(RT_EINWILLIGUNG[$schluessel])) { return false; }

    $st = db()->prepare('SELECT stand_am FROM rechtstexte WHERE schluessel = ?');
    $st->execute([$schluessel]);
    $stand = $st->fetchColumn();
    if ($stand === false || $stand === null || $stand === '') { return false; }

    db()->prepare('INSERT INTO konto_einwilligungen (user_id, schluessel, stand_am, zeit)
                   VALUES (?, ?, ?, UTC_TIMESTAMP())
                   ON DUPLICATE KEY UPDATE stand_am = VALUES(stand_am), zeit = VALUES(zeit)')
        ->execute([$userId, $schluessel, $stand]);

    return true;
}

/**
 * Der Stand je Dokument fuer die Anzeige (Kontoseite, Kontoverwaltung).
 *
 * @return array<string, array{stand: ?string, angenommen: ?string, zeit: ?string, offen: bool}>
 */
function einwilligung_stand(int $userId): array
{
    $aus = [];
    try {
        $stand = [];
        foreach (db()->query('SELECT schluessel, stand_am FROM rechtstexte')
                     ->fetchAll(PDO::FETCH_ASSOC) as $z) {
            $stand[(string)$z['schluessel']] = $z['stand_am'];
        }
        $st = db()->prepare('SELECT schluessel, stand_am, zeit FROM konto_einwilligungen
                              WHERE user_id = ?');
        $st->execute([$userId]);
        $hat = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
            $hat[(string)$z['schluessel']] = $z;
        }
    } catch (Throwable $ex) {
        return $aus;
    }

    foreach (RT_EINWILLIGUNG as $schluessel => $art) {
        $aktuell = $stand[$schluessel] ?? null;
        $aus[$schluessel] = [
            'stand'      => $aktuell,
            'angenommen' => $hat[$schluessel]['stand_am'] ?? null,
            'zeit'       => $hat[$schluessel]['zeit'] ?? null,
            'offen'      => $aktuell !== null && $aktuell !== ''
                            && ($hat[$schluessel]['stand_am'] ?? null) !== $aktuell,
        ];
    }
    return $aus;
}
