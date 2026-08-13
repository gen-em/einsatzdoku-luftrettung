<?php
declare(strict_types=1);
/**
 * Weiterleitung aus Web 3.x: Geraete sind seit damals ein Reiter der
 * Einstellungen (M4-12).
 *
 * WARUM DIESE DATEI EIN ABLAUFDATUM BRAUCHT
 * Ein Rest wie dieser bleibt sonst fuer immer liegen: Er tut nichts Falsches,
 * also faellt er nie auf, und niemand traut sich ihn zu loeschen, weil
 * unklar ist, ob noch jemand darauf zeigt. Deshalb steht das Datum hier —
 * nicht als Automatismus, sondern als Notiz an die naechste Person, die
 * aufraeumt.
 *
 * ENTFERNEN AB: Web 5.0.0 (P9). Bis dahin ist jede Seite, die noch auf
 * geraete.php verweist, mehrfach ueberarbeitet worden; die Weiterleitung
 * faengt nur noch alte Lesezeichen ab.
 *
 * Dauerhaft (301) waere falsch: Browser merken sich das unbegrenzt, und wenn
 * die Datei spaeter einmal etwas anderes tun soll, kommt niemand mehr an sie
 * heran. 302 laesst diese Tuer offen.
 */
header('Location: einstellungen.php?t=geraete', true, 302);
exit;
