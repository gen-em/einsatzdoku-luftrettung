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

extra["nadokuVersionName"] = fassungName
extra["nadokuVersionCode"] = fassungCode

// Damit ein Baulauf die Nummer nennt, die er gerade baut -- ohne sie in einer
// Datei nachschlagen zu muessen.
println("NAdoku Android $fassungName (Versionscode $fassungCode)")
