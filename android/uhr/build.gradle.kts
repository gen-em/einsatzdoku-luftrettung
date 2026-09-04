// NAdoku Uhr -- die Wear-OS-Fernbedienung (Block C).
//
// Sie zeichnet NICHTS auf und spricht NIE mit dem Server (E-S4-11): kein GPS,
// keine Kopplung, keine Zugangsdaten. Sie setzt Zeitstempel und schickt sie
// ueber den Wear Data Layer ans Handy, das quittiert (E-S4-10). Ein
// gestohlener Uhr-Speicher gibt damit nichts preis.
//
// BLIND GEBAUT. Es gibt im Container keinen Emulator (E-R45-8) und bislang
// keine Uhr (E-R45-7). Rundung, Schriftgroessen, Beruehrziele, Haltedauer und
// Sperrfrist sind gewaehlt und am Geraet nachzumessen.

import java.util.Properties

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
}

android {
    // Dieselbe Anwendungs-ID und derselbe Namensraum wie das Handy-Modul --
    // Begruendung in handy/build.gradle.kts (E-S4-01). Die beiden APK sind
    // getrennte Pakete auf getrennten Geraeten; der Data Layer verlangt
    // trotzdem Namens- UND Signaturgleichheit.
    namespace = "org.genem.nadoku"
    compileSdk = 36

    defaultConfig {
        applicationId = "org.genem.nadoku"

        /* Wear OS 3 (E-S4-03). Galaxy Watch4 und aufwaerts; aeltere
         * Tizen-Modelle fuehren gar keine Android-Apps aus, ein niedrigerer
         * Stand gewaenne also kein einziges Geraet. */
        minSdk = 30
        targetSdk = 36

        /* Der Versatz aus Backlog Nr. 98: Play verlangt je APK unter
         * derselben Anwendungs-ID einen eindeutigen Code. Begruendung des
         * Schemas im Wurzel-Bauskript (`UHR_VERSATZ`). */
        versionCode = rootProject.extra["nadokuVersionCodeUhr"] as Int
        versionName = rootProject.extra["nadokuVersionName"] as String

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    @Suppress("UnstableApiUsage")
    packaging {
        resources.excludes += "/META-INF/{AL2.0,LGPL2.1}"
    }

    signingConfigs {
        // Wortgleich zum Handy-Modul und aus demselben Grund: Der Data Layer
        // stellt nur zwischen Apps GLEICHER SIGNATUR zu (E-S4-01). Beide
        // Module aus derselben signatur.properties zu signieren ist deshalb
        // keine Bequemlichkeit, sondern Bedingung.
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

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    // Derselbe gemeinsame Quelltext wie im Handy-Modul -- Begruendung dort.
    sourceSets["main"].java.srcDir("../gemeinsam/quelle")
    sourceSets["main"].res.srcDir("../gemeinsam/res")

    lint {
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
    implementation(libs.androidx.activity.compose)

    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.graphics)
    implementation(libs.androidx.compose.foundation)
    implementation(libs.androidx.compose.ui.tooling.preview)
    debugImplementation(libs.androidx.compose.ui.tooling)

    // Compose for Wear OS -- eigene Bausteine (runde Bildschirme, Scaling
    // Lazy List). Material3 des Handys passt hier nicht.
    implementation(libs.androidx.wear.compose.material)
    implementation(libs.androidx.wear.compose.foundation)

    // WearableButtons: fragt ab, ob das Geraet eine freie Zusatztaste meldet
    // (E-S4-21a). Auf der Galaxy-Watch-Linie ist keine zu erwarten -- das
    // Bedienbild haengt deshalb NICHT daran.
    implementation(libs.androidx.wear.input)

    // Der Wear Data Layer (E-S4-10): der EINZIGE Grund fuer diese
    // proprietaere Bibliothek. Beide Module brauchen sie -- die Uhr sendet,
    // das Handy empfaengt und quittiert. Eintrag in docs/Lizenzen.md folgt in
    // Block D (E-S4-04); bis dahin fuehrt gradle/libs.versions.toml die Liste.
    implementation(libs.play.services.wearable)

    testImplementation(libs.junit)
    testImplementation(libs.robolectric)
    testImplementation(libs.androidx.test.core)
    testImplementation(libs.androidx.test.ext.junit)
}
