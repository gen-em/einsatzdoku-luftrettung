// NAdoku Handy -- die App, die aufzeichnet und sendet (Block B).
//
// Sie ist der eigentliche Client des JSON-Vertrags: Vordergrunddienst mit
// GPS, Puffer, Warteschlange, Kopplung. Die Uhr (Modul uhr/) ist nur
// Fernbedienung und spricht ausschliesslich mit dieser App (E-R45-1, E-S4-11).

import java.util.Properties

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
}

android {
    /* DIESELBE ANWENDUNGS-ID WIE DAS UHR-MODUL, und das ist keine Wahl
     * (E-S4-01): Der Wear Data Layer stellt Nachrichten nur zwischen Apps
     * gleichen Pakets UND gleicher Signatur zu. Waeren die IDs verschieden,
     * kaeme keine einzige Nachricht von der Uhr an -- und zwar ohne
     * Fehlermeldung, die Zustellung bliebe einfach aus.
     *
     * Der Bindestrich aus gen-em.org entfaellt: In einem Paketnamen ist er
     * nicht zulaessig.
     *
     * ENDGUELTIG. Eine spaetere Aenderung waere fuer jede Installation eine
     * andere App: neue Installation, verlorene Daten, verlorene Kopplung.
     * Dieselbe Ansage steht im Manifest der Garmin-Uhr seit Uhr 2.0.0. */
    namespace = "org.genem.nadoku"
    compileSdk = 36

    defaultConfig {
        applicationId = "org.genem.nadoku"

        /* Android 8.0 (E-S4-03, bestaetigt am 31.08.2026 als F-S4-A).
         * Ab hier verhalten sich Vordergrunddienste mit Benachrichtigungs-
         * kanal stabil; aeltere Geraete sind im Zielkreis nicht zu erwarten. */
        minSdk = 26
        targetSdk = 36

        versionCode = rootProject.extra["nadokuVersionCode"] as Int
        versionName = rootProject.extra["nadokuVersionName"] as String

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    /* Doppelte Lizenzdateien aus den Compose-Bibliotheken. Ohne die Zeile
     * bricht das Zusammenpacken mit "duplicate resource" ab -- die Dateien
     * selbst haben im APK keinen Zweck. */
    @Suppress("UnstableApiUsage")
    packaging {
        resources.excludes += "/META-INF/{AL2.0,LGPL2.1}"
    }

    signingConfigs {
        /* DER SIGNATURSCHLUESSEL LIEGT NICHT IM REPOSITORIUM (E-S4-16).
         *
         * Er wird einmal erzeugt, dem Auftraggeber zur Verwahrung uebergeben
         * und ueber signatur.properties eingebunden -- eine Datei, die in
         * .gitignore steht, wie server/config.php. Fehlt sie, baut dieses
         * Projekt ein UNSIGNIERTES Release; genau so laeuft es im Container
         * und spaeter im CI-Prueftor nach R40.4 (E-R45-9: signiert wird
         * ausserhalb der CI, weil der Schluessel dort nichts verloren hat).
         *
         * Jede spaetere Fassung MUSS mit demselben Schluessel signiert sein.
         * Android erkennt eine App an Paketname UND Signatur; ein Wechsel
         * bedeutet fuer jedes Geraet Deinstallation samt Datenverlust. */
        val signaturDatei = rootProject.file("signatur.properties")
        if (signaturDatei.exists()) {
            val s = Properties().apply { signaturDatei.inputStream().use { load(it) } }
            create("auslieferung") {
                storeFile = rootProject.file(s.getProperty("speicherDatei"))
                storePassword = s.getProperty("speicherPasswort")
                keyAlias = s.getProperty("schluesselName")
                keyPassword = s.getProperty("schluesselPasswort")
            }
        }
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(getDefaultProguardFile("proguard-android-optimize.txt"), "proguard-rules.pro")
            signingConfig = signingConfigs.findByName("auslieferung")
        }
        debug {
            applicationIdSuffix = ".pruef"
            versionNameSuffix = "-pruef"
        }
    }

    /* JDK 17 als Sprachstand (E-S4-02). Der Baulauf selbst laeuft im Container
     * auf JDK 21 -- das ist kein Widerspruch: Die Zahl hier legt fest, welchen
     * Bytecode und welchen Sprachumfang die App enthaelt, nicht, womit
     * uebersetzt wird. Siehe LIESMICH.md, Abschnitt "Werkzeugstaende". */
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    /* GEMEINSAMER QUELLTEXT OHNE DRITTES MODUL.
     *
     * E-S4-02 legt ZWEI Module fest, handy/ und uhr/. Beide brauchen dieselben
     * Dinge: die Farb-Token, die Bildmarken, die Phasenliste, das Format der
     * Kennungen, das Nachrichtenformat des Data Layer. Ein drittes Modul waere
     * der uebliche Gradle-Weg dafuer -- es waere aber auch eine Entscheidung,
     * die das Konzept nicht getroffen hat.
     *
     * Stattdessen wird der gemeinsame Quelltext in BEIDE Module EINGEBUNDEN.
     * Er wird damit zweimal uebersetzt; das kostet Bauzeit und sonst nichts.
     * Was es einspart, ist eine Modulgrenze mit eigener Versionierung,
     * eigenem Manifest und eigener Lint-Auswertung fuer rund tausend Zeilen. */
    sourceSets["main"].java.srcDir("../gemeinsam/quelle")
    sourceSets["main"].res.srcDir("../gemeinsam/res")

    lint {
        /* Ein Lint-FEHLER haelt den Baulauf an; Warnungen werden gezaehlt und
         * genannt, nicht versteckt (Abnahme B1). Der Textbericht ist die
         * Zaehlgrundlage -- die HTML-Fassung liest im Container niemand. */
        abortOnError = true
        warningsAsErrors = false
        checkDependencies = false
        textReport = true
        htmlReport = false
        xmlReport = true
    }

    testOptions {
        unitTests {
            isIncludeAndroidResources = true
            isReturnDefaultValues = true

            /* DER SERVER-RUNDLAUF IST NICHT TEIL DES NORMALEN LAUFS.
             *
             * `KopplungRundlaufTest` spricht mit einer echten Installation
             * (tools/referenzdatensatz/einspielen/lokal_starten.sh). Wo keine
             * läuft — auf einem Arbeitsplatz, im CI-Prüftor —, überspringt
             * sich der Fall selbst statt fehlzuschlagen: Ein Prüffall, der
             * ohne Zutun rot ist, wird nach der dritten Woche ignoriert.
             *
             * Angeschaltet wird er über eine Gradle-Eigenschaft:
             *   ./gradlew :handy:test -Pnadoku.rundlauf=http://127.0.0.1:8080/
             *
             * Sichtbar bleibt er trotzdem: Der Fall meldet sich als
             * "übersprungen", nicht als "bestanden". */
            all {
                it.systemProperty(
                    "nadoku.rundlauf",
                    (project.findProperty("nadoku.rundlauf") as String?) ?: "",
                )
                it.testLogging {
                    events("skipped", "failed")
                    showStandardStreams = true
                }
            }
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget.set(org.jetbrains.kotlin.gradle.dsl.JvmTarget.JVM_17)
    }
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.lifecycle.service)
    implementation(libs.androidx.activity.compose)

    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.graphics)
    implementation(libs.androidx.compose.foundation)
    implementation(libs.androidx.compose.material3)
    implementation(libs.androidx.compose.ui.tooling.preview)
    debugImplementation(libs.androidx.compose.ui.tooling)

    // Kamera fuer den QR-Scan der Kopplung (E-S4-15). CameraX gehoert zu
    // AndroidX und faellt damit unter den ersten der drei zugelassenen
    // Fremdbestandteile (E-S4-04).
    implementation(libs.androidx.camera.core)
    implementation(libs.androidx.camera.camera2)
    implementation(libs.androidx.camera.lifecycle)
    implementation(libs.androidx.camera.view)

    // ZXing: die QR-Erkennung selbst. NUR der Kern -- das Android-Beiwerk
    // (zxing-android-embedded) braechte eine eigene Activity und eine eigene
    // Oberflaeche mit; gebraucht wird der Dekodierer.
    implementation(libs.zxing.core)

    // Der Wear Data Layer (E-S4-10): der EINZIGE Grund fuer diese
    // proprietaere Bibliothek. Beide Module brauchen sie -- die Uhr sendet,
    // das Handy empfaengt und quittiert. Eintrag in docs/Lizenzen.md folgt in
    // Block D (E-S4-04); bis dahin fuehrt gradle/libs.versions.toml die Liste.
    implementation(libs.play.services.wearable)

    testImplementation(libs.junit)
    testImplementation(libs.robolectric)
    testImplementation(libs.androidx.test.core)
    testImplementation(libs.androidx.test.ext.junit)

    /* INSTRUMENTIERT, also auf einem Android-System und nicht in der JVM
     * (S4/D2). Daran haengt, was Robolectric nicht erreicht: der echte
     * `AndroidKeyStore` (`KeyStoreException: AndroidKeyStore not found`) und
     * die Erreichbarkeit der Wearable-API.
     *
     * NICHTS DAVON LIEGT IM APK der Anwendung: Der Laeufer wird in ein
     * eigenes Test-APK gepackt. Aufgerufen wird es hier NICHT ueber
     * `connectedAndroidTest` -- das scheitert auf einem softwareemulierten
     * Geraet an einer ddmlib-Zeitgrenze --, sondern ueber
     * `adb shell am instrument`; die Befehlsfolge steht in
     * `android/LIESMICH.md`. */
    androidTestImplementation(libs.androidx.test.ext.junit)
    androidTestImplementation(libs.androidx.test.runner)
}
