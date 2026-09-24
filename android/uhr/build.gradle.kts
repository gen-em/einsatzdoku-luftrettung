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
    /* KEIN `kotlin.android` MEHR (AR-02): AGP 9 uebersetzt Kotlin selbst
     * ("built-in Kotlin"), und das alte Plugin daneben bricht den Bau ab.
     * Welche Kotlin-Fassung das ist, legt die Wurzel fest. */
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.compose)
}

android {
    // Dieselbe Anwendungs-ID und derselbe Namensraum wie das Handy-Modul --
    // Begruendung in handy/build.gradle.kts (E-S4-01). Die beiden APK sind
    // getrennte Pakete auf getrennten Geraeten; der Data Layer verlangt
    // trotzdem Namens- UND Signaturgleichheit.
    namespace = "org.genem.nadoku"
    /* 37 seit Android 0.16.0 (Konzept AR, E-AR-08): Compose 1.12, wear-compose
     * 1.7 und core-ktx 1.19 verlangen es (aar-metadata: minCompileSdk=37). */
    compileSdk = 37

    defaultConfig {
        applicationId = "org.genem.nadoku"

        /* Wear OS 3 (E-S4-03). Galaxy Watch4 und aufwaerts; aeltere
         * Tizen-Modelle fuehren gar keine Android-Apps aus, ein niedrigerer
         * Stand gewaenne also kein einziges Geraet. */
        minSdk = 30
        /* 37 seit Android 0.16.0 (E-AR-08): Es gelten die Regeln von Android 17.
         * Durchsicht der 17 Verhaltensaenderungen: Konzept AR, AR-03. */
        targetSdk = 37

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
    //
    // SEIT AGP 9 UEBER `kotlin`, NICHT UEBER `java` (AR-02, F-AR-08). Das
    // eingebaute Kotlin nimmt Kotlin-Dateien aus einem `java`-Verzeichnis
    // nicht mehr an; bis Android 0.15.1 stand hier `java.srcDir(…)`.
    sourceSets["main"].kotlin.directories += "../gemeinsam/quelle"
    sourceSets["main"].res.directories += "../gemeinsam/res"

    lint {
        abortOnError = true
        warningsAsErrors = false
        checkDependencies = false
        // Keine Berichtsschalter mehr -- Begruendung im Handy-Modul (AR-02).
    }

    testOptions {
        unitTests {
            isIncludeAndroidResources = true
            isReturnDefaultValues = true

            /* BEIDE MODULE REDEN GLEICH (Backlog Nr. 240, 20.09.2026).
             *
             * Hier stand bis dahin gar kein `testLogging`. Ein Fehlschlag im
             * Uhr-Modul hätte im Protokoll nur "FAILED" der Gradle-Aufgabe
             * hinterlassen — ohne Fall, ohne Grund. Dass es nie aufgefallen
             * ist, heisst nur, dass hier noch keiner rot war.
             *
             * Ohne `showStandardStreams`: Das Uhr-Modul hat keinen
             * Rundlauf-Prüffall, der eine Ausgabe braucht. */
            all {
                it.testLogging {
                    events("skipped", "failed")
                    exceptionFormat =
                        org.gradle.api.tasks.testing.logging.TestExceptionFormat.FULL
                    showExceptions = true
                    showCauses = true
                    showStackTraces = true
                }
            }
        }
    }
}

/* HIER STAND BIS ANDROID 0.15.1 `kotlin { compilerOptions { jvmTarget = 17 } }`.
 * Mit dem eingebauten Kotlin von AGP 9 folgt `jvmTarget` von selbst
 * `compileOptions.targetCompatibility` (oben, 17) -- eine zweite Angabe
 * derselben Zahl waere eine, die auseinanderlaufen kann (AR-02). */

/* DAS ROBOLECTRIC-ABBILD KOMMT ÜBER GRADLE, NICHT ÜBER ROBOLECTRIC
 * (Backlog Nr. 240, 20.09.2026).
 *
 * WAS VORHER GESCHAH. Robolectric holt sein Android-Archiv (`android-all-
 * instrumented`, 145 MB) von sich aus **während des Testlaufs** von Maven —
 * über seinen eigenen `MavenArtifactFetcher`, an Gradle vorbei: ohne
 * Wiederholung, ohne Prüfsumme über den Abhängigkeitsauflöser, und ohne den
 * Zwischenspeicher, den ein Läufer aufbauen könnte. Der Ablageort ist
 * `~/.m2`, nicht der Gradle-Cache.
 *
 * Am 20.09.2026 brach dieser Download auf einem Läufer ab
 * (`SocketException: Connection reset by peer`) und färbte Stufe 1 rot:
 *
 *     HandyBildTest > classMethod FAILED
 *       java.lang.AssertionError at MavenArtifactFetcher.java:129
 *
 * `classMethod` ist der Klassenaufbau und kein Prüffall — es war also nie
 * ein Testfehler. Örtlich blieb es unsichtbar, weil das Archiv dort längst
 * im Zwischenspeicher lag; ein Läufer ist jedes Mal kalt.
 *
 * WARUM NICHT EINFACH DEN CACHE DES LÄUFERS AUFHEBEN. Das macht den Abbruch
 * seltener, nicht unmöglich: Beim ersten Lauf und nach jedem Ablauf des
 * Zwischenspeichers lädt Robolectric wieder. „Seltener" ist genau die Sorte
 * Lösung, die `CLAUDE.md` als Flake-Behandlung zurückweist.
 *
 * WIE ES JETZT LÄUFT. Das Archiv ist eine gewöhnliche Abhängigkeit in einer
 * eigenen Konfiguration; Gradle löst sie auf wie jede andere. `Sync` legt
 * genau diese eine Datei in einen Ordner, und Robolectric läuft **offline**
 * und nimmt sie von dort. Zur Testlaufzeit wird nichts mehr geholt.
 *
 * DER PREIS steht im Versionskatalog: Die Abbildkennung ist fest verdrahtet
 * und gehört bei einer Änderung von `sdk=` oder einem Robolectric-Update
 * mitgezogen. Vergisst man es, bricht der Lauf mit einer klaren Meldung —
 * das ist der bessere Tausch gegen einen stillen Download. */
// `create` statt `by configurations.creating`: Gradle 9.6 meldet den
// Delegaten als veraltet (AR-02).
val robolectricAbbild: Configuration = configurations.create("robolectricAbbild")

val robolectricAbbildOrdner = layout.buildDirectory.dir("robolectric-abbild")

val robolectricAbbildBereitstellen = tasks.register<Sync>("robolectricAbbildBereitstellen") {
    from(robolectricAbbild)
    into(robolectricAbbildOrdner)
}

tasks.withType<Test>().configureEach {
    dependsOn(robolectricAbbildBereitstellen)
    /* `offline` OHNE `dependency.dir` wäre ein Abbruch mit einer Meldung
     * über einen fehlenden Ordner — beides gehört zusammen. */
    systemProperty("robolectric.offline", "true")
    systemProperty(
        "robolectric.dependency.dir",
        robolectricAbbildOrdner.get().asFile.absolutePath,
    )
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
    robolectricAbbild(libs.robolectric.abbild)
    testImplementation(libs.androidx.test.core)
    testImplementation(libs.androidx.test.ext.junit)
}
