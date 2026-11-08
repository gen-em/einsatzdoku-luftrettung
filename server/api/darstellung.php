<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';        // liefert $userId

/**
 * api/darstellung.php — der Klick auf Mond/Sonne in der Kopfleiste.
 *
 * Konzept OF, OF-08 (E-OF-09, -43, -79). Der Browser schaltet `data-theme`
 * sofort selbst um; dieser Endpunkt legt die Wahl nur in der SITZUNG ab
 * (`darstellung_klick_setzen()`), damit `ui_seite_start()` sie auf der
 * naechsten Seite schon im Markup ausgibt und nichts hell aufblitzt.
 *
 * NICHT IM KONTO, mit Absicht (E-OF-79): Der Klick gilt bis zur Abmeldung.
 * Was dauerhaft gelten soll, waehlt man im Profil.
 *
 * DESHALB REICHT DIE SITZUNG, wie bei `api/rueckfrage.php`: Wer eine
 * Sitzung uebernommen hat, kann hier nur die Farbe dieser Sitzung wechseln.
 *
 * Antworten: `{ok: true}`; `{ok: false}` mit 409, wenn das Konto keinen Knopf
 * hat (Immer hell / Immer dunkel) oder der Wert unbekannt ist.
 */

api_methode();
csrf_check();

if (!darstellung_klick_setzen(post_text('wahl'))) {
    json_out(['ok' => false, 'fehler' => 'darstellung'], 409);
}
json_out(['ok' => true]);
