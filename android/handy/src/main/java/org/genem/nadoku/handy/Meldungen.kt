package org.genem.nadoku.handy

import androidx.annotation.StringRes
import org.genem.nadoku.R
import org.genem.nadoku.handy.kopplung.Abweisung
import org.genem.nadoku.handy.senden.Sperrgrund

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

    /**
     * Die Ergebniszeile nach einer 403. Eine Zeile, nicht
     * zwei: Was hilft, steht hinter dem Punkt — und nie „neu koppeln", denn
     * das Gerät ist bekannt, es darf nur gerade nicht.
     */
    @StringRes
    fun sperrtext(grund: Sperrgrund?): Int = when (grund) {
        Sperrgrund.GERAET_DEAKTIVIERT -> R.string.sync_ergebnis_geraet_deaktiviert
        Sperrgrund.KONTO_GESPERRT -> R.string.sync_ergebnis_konto_gesperrt
        Sperrgrund.KONTO_WARTET -> R.string.sync_ergebnis_konto_wartet
        Sperrgrund.KONTO_UNBESTAETIGT -> R.string.sync_ergebnis_konto_unbestaetigt
        Sperrgrund.UNBEKANNT, null -> R.string.sync_ergebnis_gesperrt
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
