// NAdoku Android -- Wurzel des Baulaufs.
//
// Hier stehen keine Abhaengigkeiten und keine Android-Einstellungen; beides
// gehoert in die Module (handy/, uhr/). Die Wurzel tut genau zwei Dinge: Sie
// meldet die Uebersetzer-Zusaetze an, und sie liest die EINE Versionsnummer
// aus version.properties und rechnet daraus den Versionscode. Beide Module
// nehmen sie von hier -- eine Nummer, eine Quelle (E-S4-02).

import java.util.Properties

plugins {
    alias(libs.plugins.android.application) apply false
    alias(libs.plugins.kotlin.android) apply false
    alias(libs.plugins.kotlin.compose) apply false
}

// ---- Die eine Versionsnummer -------------------------------------------------
//
// Sie steht in version.properties, samt der Erzaehlung, wofuer welche Stelle
// steht (dieselbe Rolle wie der Kopfkommentar von server/version.php). Der
// VERSIONSCODE wird daraus GERECHNET und nicht danebengeschrieben: Android
// vergleicht Fassungen ueber die ganze Zahl, und zwei Angaben, die von Hand
// synchron gehalten werden muessen, laufen frueher oder spaeter auseinander --
// hier faellt das erst bei der Installation auf dem Geraet auf.
val versionsDatei = rootProject.file("version.properties")
val versionsEintraege = Properties().apply {
    versionsDatei.inputStream().use { load(it) }
}
val fassungName: String = versionsEintraege.getProperty("version")
    ?: error("version.properties: Der Schluessel 'version' fehlt.")

val fassungTeile = fassungName.trim().split(".")
require(fassungTeile.size == 3 && fassungTeile.all { it.toIntOrNull() != null }) {
    "version.properties: '$fassungName' ist keine Nummer der Form Haupt.Neben.Korrektur."
}
val fassungCode: Int =
    fassungTeile[0].toInt() * 10_000 + fassungTeile[1].toInt() * 100 + fassungTeile[2].toInt()

/* DER VERSATZ DES UHR-MODULS (Backlog Nr. 98, R65).
 *
 * WOZU. Die Play Console verlangt unter EINEM Paketnamen je hochgeladenem APK
 * einen eindeutigen Versionscode. Handy und Uhr tragen dieselbe Anwendungs-ID
 * (E-S4-01) und bisher denselben gerechneten Code -- damit laesst sich das
 * zweite der beiden nicht hochladen, und ohne das gibt es kein Wear-OS-Release.
 *
 * WARUM +1 000 000 UND KEINE FUEHRENDE FORMFAKTOR-ZIFFER. Backlog Nr. 98 nennt
 * beides als moeglich. Die fuehrende Ziffer haette BEIDE Module verschoben
 * (Handy 1xxxxxx, Uhr 2xxxxxx) -- also einen Sprung auch dort, wo keiner
 * noetig ist. Der Versatz trifft nur das Modul, das ihn braucht: Das Handy
 * zaehlt unveraendert weiter, die Uhr springt einmalig.
 *
 * DIE UHR BEKOMMT DEN HOEHEREN CODE, nicht den niedrigeren. Play fordert nur
 * Eindeutigkeit; die Wahl ist einmalig und nicht rueckgaengig zu machen. Der
 * hoehere ist der ungefaehrlichere: Ein Versatz nach UNTEN koennte mit einer
 * kuenftigen Handy-Fassung kollidieren, ein Versatz nach oben nie -- 1 000 000
 * entspraeche der Handy-Version 100.0.0.
 *
 * DIE ZAEHLUNG BLEIBT EINE (E-S4-02). Versionsname und version.properties sind
 * unberuehrt; beide Module tragen weiterhin dieselbe Fassung. Was sich
 * unterscheidet, ist eine Zahl, die nur Play liest und die kein Mensch je zu
 * Gesicht bekommt.
 *
 * PREIS: ein einmaliger Sprung. Auf einer Uhr, auf der die App per
 * Seitenladung liegt, verlangt das kuenftige Update keine Neuinstallation --
 * der Code steigt ja. Umgekehrt liesse sich eine Uhr-Fassung mit Versatz
 * spaeter nicht durch eine ohne ersetzen.
 */
val UHR_VERSATZ = 1_000_000

extra["nadokuVersionName"] = fassungName
extra["nadokuVersionCode"] = fassungCode
extra["nadokuVersionCodeUhr"] = fassungCode + UHR_VERSATZ

// Damit ein Baulauf die Nummern nennt, die er gerade baut -- ohne sie in einer
// Datei nachschlagen zu muessen. BEIDE, seit dem Versatz: Eine Zahl, die man
// nicht sieht, faellt auch nicht auf, wenn sie falsch ist.
println(
    "NAdoku Android $fassungName " +
        "(Versionscode Handy $fassungCode, Uhr ${fassungCode + UHR_VERSATZ})"
)
