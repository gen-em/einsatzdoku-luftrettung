<?php
declare(strict_types=1);
/**
 * Teilenummer -> [Modellname, Geraeteart]. ERZEUGT — NICHT VON HAND AENDERN.
 *
 *     python3 tools/geraetemodelle/erzeugen.py ~/.Garmin/ConnectIQ/Devices
 *
 * Wozu: Die Garmin-Uhr kennt ihren Modellnamen nicht und sendet beim Koppeln
 * ihre Teilenummer (JSON-Vertrag 1a, R42). `geraete_lib.php` loest sie hier
 * auf. Wer diese Datei von Hand ergaenzt, ergaenzt sie an der falschen Stelle
 * — der naechste Lauf des Erzeugers wirft es weg.
 *
 * Herkunft: noch keine — mit --leer erzeugt, weil die
 *           Geraetedateien nicht vorlagen. Jede Teilenummer
 *           bleibt bis zum naechsten Lauf unaufgeloest und steht
 *           dann in devices.geraet_teil.
 * Bestand:  0 Teilenummern auf 0 Modelle, davon 0 als Uhr eingestuft.
 *
 * Die Gerätedateien selbst liegen NICHT im Repositorium (sie gehoeren Garmin);
 * was hier steht, ist die Zuordnung oeffentlicher Teilenummern zu
 * oeffentlichen Produktnamen. Einordnung: docs/Lizenzen.md.
 */
const GERAETE_MODELLE = [
    // leer
];
