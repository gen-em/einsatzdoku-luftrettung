// NAdoku Android -- ein Gradle-Projekt, zwei Module (E-S4-02).
//
// FREMDBESTANDTEILE NUR ZUR BAUZEIT. Die Bezugsquellen unten liefern
// AndroidX/Compose, ZXing und play-services-wearable (E-S4-04). Zur Laufzeit
// laedt die App nichts nach -- der Grundsatz "keine fremde Quelle zur
// Laufzeit" (CLAUDE.md 4) gilt fuer sie wie fuer die Weboberflaeche.
//
// FAIL_ON_PROJECT_REPOS: Ein Modul darf sich keine eigene Bezugsquelle
// dazunehmen. Sonst stuende irgendwann eine Abhaengigkeit im Bau, die in
// docs/Lizenzen.md nicht auftaucht -- und genau das soll die Liste dort
// verhindern.
pluginManagement {
    repositories {
        google {
            content {
                includeGroupByRegex("com\\.android.*")
                includeGroupByRegex("com\\.google.*")
                includeGroupByRegex("androidx.*")
            }
        }
        mavenCentral()
        gradlePluginPortal()
    }
}

dependencyResolutionManagement {
    repositoriesMode.set(RepositoriesMode.FAIL_ON_PROJECT_REPOS)
    repositories {
        google()
        mavenCentral()
    }
}

rootProject.name = "NAdoku"
include(":handy")
include(":uhr")
