<?php
declare(strict_types=1);
/**
 * Nutzungsbedingungen dieser Installation.
 *
 * Der Inhalt steht in der Datenbank und wird unter Verwaltung -> Installation
 * gepflegt; die Anwendung liefert keinen Text mit. Entwuerfe zum
 * Einspielen liegen unter `docs/rechtstexte/` — sie sind NICHT anwaltlich
 * geprueft, und das steht in ihrem Kopf.
 *
 * Die Seite selbst ist rechtstext_seite.php — sie traegt alle vier
 * Rechtstexte, weil sie sich nur im Schluessel unterscheiden.
 */
$rtSchluessel = 'nutzungsbedingungen';
require __DIR__ . '/rechtstext_seite.php';
