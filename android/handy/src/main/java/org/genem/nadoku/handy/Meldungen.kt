package org.genem.nadoku.handy

import androidx.annotation.StringRes
import org.genem.nadoku.R
import org.genem.nadoku.handy.kopplung.Abweisung

/**
 * Von der Ursache zum Text — zweizeilig: **was** ist los, **was** hilft.
 *
 * WARUM DIE TEXTE NICHT IM DIENST STEHEN. [Abweisung] ist die Auskunft der
 * Sachschicht; ein Text ist eine Frage der Oberfläche und gehört in
 * `strings.xml`. Der Dienst bleibt damit ohne Android-Umgebung prüfbar.
 *
 * WARUM ZWEI ZEILEN. Übernommen von der Garmin-Uhr (`Pair.mc`): Dort fiel auf,
 * dass eine einzeilige Meldung („Zu viele Geräte gekoppelt, erst eines im Web
 * löschen") genau um den Teil gekürzt wurde, der sagt, was zu tun ist. Am
 * Handy ist der Platz da — die Trennung bleibt, weil sie den Text zwingt,
 * beide Fragen zu beantworten.
 *
 * DIE SERVERMELDUNG ERSETZT NIE DIE ERSTE ZEILE, sie kommt als dritte dazu:
 * Die Meldungen von `pair.php` sind für die Weboberfläche geschrieben — ganze
 * Sätze („Es sind bereits 5 Geräte mit diesem Konto verbunden …"). Sie sollen
 * sichtbar sein, aber nicht den eigenen Text verdrängen.
 *
 * HIER STAND „ohne Umlaute", und das war seit S5 falsch. Der Satz stammte aus
 * der Zeit, als `pair.php` seine Meldungen umlautfrei schrieb; Web 13.0.0 hat
 * das geändert, ohne dass es jemandem auffiel — die drei Stellen, die es
 * behaupteten, standen in Dateien, die niemand deswegen ansah. Gefunden hat
 * es ein Prüffall, der auf den umlautlosen Text prüfte (KopplungRundlaufTest,
 * `geraeteGrenzeGreiftUndIstErklaert`) und dabei nur deshalb durchging, weil
 * er gegen einen Server vom alten Stand lief.
 */
object Meldungen {

    @StringRes
    fun text(art: Abweisung): Int = when (art) {
        Abweisung.SITZUNG_UNGUELTIG -> R.string.fehler_sitzung_ungueltig
        Abweisung.SITZUNG_ABGELAUFEN -> R.string.fehler_sitzung_abgelaufen
        Abweisung.ZU_VIELE_GERAETE -> R.string.fehler_zu_viele_geraete
        Abweisung.ZU_VIELE_VERSUCHE -> R.string.fehler_zu_viele_versuche
        Abweisung.KEINE_VERBINDUNG -> R.string.fehler_keine_verbindung
        Abweisung.SERVERFEHLER -> R.string.fehler_serverfehler
        Abweisung.UNBEKANNT -> R.string.fehler_unbekannt
    }

    @StringRes
    fun hinweis(art: Abweisung): Int = when (art) {
        Abweisung.SITZUNG_UNGUELTIG -> R.string.fehler_sitzung_ungueltig_hinweis
        Abweisung.SITZUNG_ABGELAUFEN -> R.string.fehler_sitzung_abgelaufen_hinweis
        Abweisung.ZU_VIELE_GERAETE -> R.string.fehler_zu_viele_geraete_hinweis
        Abweisung.ZU_VIELE_VERSUCHE -> R.string.fehler_zu_viele_versuche_hinweis
        Abweisung.KEINE_VERBINDUNG -> R.string.fehler_keine_verbindung_hinweis
        Abweisung.SERVERFEHLER -> R.string.fehler_serverfehler_hinweis
        Abweisung.UNBEKANNT -> R.string.fehler_unbekannt_hinweis
    }
}
