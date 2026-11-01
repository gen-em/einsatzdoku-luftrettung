// NAdoku Uhr -- die Wear-OS-Fernbedienung.
//
// Sie zeichnet NICHTS auf und spricht NIE mit dem Server: kein GPS,
// keine Kopplung, keine Zugangsdaten. Sie setzt Zeitstempel und schickt sie
// ueber den Wear Data Layer ans Handy, das quittiert. Ein
// gestohlener Uhr-Speicher gibt damit nichts preis.
//
// BLIND GEBAUT. Es gibt im Container keinen Emulator und bislang
// keine Uhr. Rundung, Schriftgroessen, Beruehrziele, Haltedauer und
// Sperrfrist sind gewaehlt und am Geraet nachzumessen.

import java.util.Properties

plugins {
    /* KEIN `kotlin.android`: AGP 9 uebersetzt Kotlin selbst
     * ("built-in Kotlin"), und das alte Plugin daneben bricht den Bau ab.
     * Welche Kotlin-Fassung das ist, legt die Wurzel fest. */
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.compose)
}

android {
    // Dieselbe Anwendungs-ID und derselbe Namensraum wie das Handy-Modul --
    // Begruendung in handy/build.gradle.kts. Die beiden APK sind
    // getrennte Pakete auf getrennten Geraeten; der Data Layer verlangt
    // trotzdem Namens- UND Signaturgleichheit.
    namespace = "org.genem.nadoku"
    /* 37, weil Compose 1.12, wear-compose
     * 1.7 und core-ktx 1.19 verlangen es (aar-metadata: minCompileSdk=37). */
    compileSdk = 37

    defaultConfig {
        applicationId = "org.genem.nadoku"

        /* Wear OS 3. Galaxy Watch4 und aufwaerts; aeltere
         * Tizen-Modelle fuehren gar keine Android-Apps aus, ein niedrigerer
         * Stand gewaenne also kein einziges Geraet. */
        minSdk = 30
        /* 37: Es gelten die Regeln von Android 17; seine
         * Verhaltensaenderungen sind durchgesehen. */
        targetSdk = 37

        /* Der Versatz: Play verlangt je APK unter
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
        // stellt nur zwischen Apps GLEICHER SIGNATUR zu. Beide
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
    // UEBER `kotlin`, NICHT UEBER `java`: Das eingebaute Kotlin von AGP 9
    // nimmt Kotlin-Dateien aus einem `java`-Verzeichnis nicht an.
    sourceSets["main"].kotlin.directories += "../gemeinsam/quelle"
    sourceSets["main"].res.directories += "../gemeinsam/res"

    lint {
        abortOnError = true
        warningsAsErrors = false
        checkDependencies = false
        // Keine Berichtsschalter -- Begruendung im Handy-Modul.
    }

    testOptions {
        unitTests {
            isIncludeAndroidResources = true
            isReturnDefaultValues = true

            /* BEIDE MODULE REDEN GLEICH.
             *
             * Ohne `testLogging` hinterliesse ein Fehlschlag im Uhr-Modul im
             * Protokoll nur "FAILED" der Gradle-Aufgabe — ohne Fall, ohne
             * Grund.
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

/* KEIN `kotlin { compilerOptions { jvmTarget = 17 } }`: Mit dem eingebauten
 * Kotlin von AGP 9 folgt `jvmTarget` von selbst
 * `compileOptions.targetCompatibility` (oben, 17) -- eine zweite Angabe
 * derselben Zahl waere eine, die auseinanderlaufen kann. */

/* DAS ROBOLECTRIC-ABBILD KOMMT ÜBER GRADLE, NICHT ÜBER ROBOLECTRIC.
 *
 * WAS SONST GESCHIEHT. Robolectric holt sein Android-Archiv (`android-all-
 * instrumented`, 145 MB) von sich aus **während des Testlaufs** von Maven —
 * über seinen eigenen `MavenArtifactFetcher`, an Gradle vorbei: ohne
 * Wiederholung, ohne Prüfsumme über den Abhängigkeitsauflöser, und ohne den
 * Zwischenspeicher, den ein Läufer aufbauen könnte. Der Ablageort ist
 * `~/.m2`, nicht der Gradle-Cache.
 *
 * Bricht dieser Download auf einem Läufer ab
 * (`SocketException: Connection reset by peer`), färbt er Stufe 1 rot:
 *
 *     HandyBildTest > classMethod FAILED
 *       java.lang.AssertionError at MavenArtifactFetcher.java:129
 *
 * `classMethod` ist der Klassenaufbau und kein Prüffall — es ist also nie
 * ein Testfehler. Örtlich bleibt es unsichtbar, weil das Archiv dort längst
 * im Zwischenspeicher liegt; ein Läufer ist jedes Mal kalt.
 *
 * WARUM NICHT EINFACH DEN CACHE DES LÄUFERS AUFHEBEN. Das macht den Abbruch
 * seltener, nicht unmöglich: Beim ersten Lauf und nach jedem Ablauf des
 * Zwischenspeichers lädt Robolectric wieder. „Seltener" ist genau die Sorte
 * Lösung, die `CLAUDE.md` als Flake-Behandlung zurückweist.
 *
 * WIE ES LÄUFT. Das Archiv ist eine gewöhnliche Abhängigkeit in einer
 * eigenen Konfiguration; Gradle löst sie auf wie jede andere. `Sync` legt
 * genau diese eine Datei in einen Ordner, und Robolectric läuft **offline**
 * und nimmt sie von dort. Zur Testlaufzeit wird nichts geholt.
 *
 * DER PREIS steht im Versionskatalog: Die Abbildkennung ist fest verdrahtet
 * und gehört bei einer Änderung von `sdk=` oder einem Robolectric-Update
 * mitgezogen. Vergisst man es, bricht der Lauf mit einer klaren Meldung —
 * das ist der bessere Tausch gegen einen stillen Download. */
// `create` statt `by configurations.creating`: Gradle 9.6 meldet den
// Delegaten als veraltet.
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

    // WearableButtons: fragt ab, ob das Geraet eine freie Zusatztaste
    // meldet. Auf der Galaxy-Watch-Linie ist keine zu erwarten -- das
    // Bedienbild haengt deshalb NICHT daran.
    implementation(libs.androidx.wear.input)

    // Der Wear Data Layer: der EINZIGE Grund fuer diese proprietaere
    // Bibliothek. Beide Module brauchen sie -- die Uhr sendet, das Handy
    // empfaengt und quittiert. Eintrag in docs/Lizenzen.md 6a; die Liste
    // selbst fuehrt gradle/libs.versions.toml.
    implementation(libs.play.services.wearable)

    testImplementation(libs.junit)
    testImplementation(libs.robolectric)
    robolectricAbbild(libs.robolectric.abbild)
    testImplementation(libs.androidx.test.core)
    testImplementation(libs.androidx.test.ext.junit)
}
