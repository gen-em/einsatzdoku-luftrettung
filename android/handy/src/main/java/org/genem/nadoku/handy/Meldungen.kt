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
 * Sätze, ohne Umlaute („Es sind bereits 5 Geraete …"). Sie sollen sichtbar
 * sein, aber nicht den eigenen Text verdrängen.
 */
object Meldungen {

    @StringRes
    fun text(art: Abweisung): Int = when (art) {
        Abweisung.CODE_UNBRAUCHBAR -> R.string.fehler_code_unbrauchbar
        Abweisung.CODE_UNGUELTIG -> R.string.fehler_code_ungueltig
        Abweisung.ZU_VIELE_GERAETE -> R.string.fehler_zu_viele_geraete
        Abweisung.ZU_VIELE_VERSUCHE -> R.string.fehler_zu_viele_versuche
        Abweisung.KEINE_VERBINDUNG -> R.string.fehler_keine_verbindung
        Abweisung.SERVERFEHLER -> R.string.fehler_serverfehler
        Abweisung.UNBEKANNT -> R.string.fehler_unbekannt
    }

    @StringRes
    fun hinweis(art: Abweisung): Int = when (art) {
        Abweisung.CODE_UNBRAUCHBAR -> R.string.fehler_code_unbrauchbar_hinweis
        Abweisung.CODE_UNGUELTIG -> R.string.fehler_code_ungueltig_hinweis
        Abweisung.ZU_VIELE_GERAETE -> R.string.fehler_zu_viele_geraete_hinweis
        Abweisung.ZU_VIELE_VERSUCHE -> R.string.fehler_zu_viele_versuche_hinweis
        Abweisung.KEINE_VERBINDUNG -> R.string.fehler_keine_verbindung_hinweis
        Abweisung.SERVERFEHLER -> R.string.fehler_serverfehler_hinweis
        Abweisung.UNBEKANNT -> R.string.fehler_unbekannt_hinweis
    }
}
