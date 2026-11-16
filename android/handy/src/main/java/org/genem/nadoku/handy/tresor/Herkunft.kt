package org.genem.nadoku.handy.tresor

/**
 * Kann dieses Handy belegen, dass seine Daten von ihm stammen?
 *
 * DREI LAGEN, und die Ansicht zeigt für jede etwas anderes:
 *
 * | Lage | Server hat `huelle: 2` gesagt | gültiges Gerätepaar | das Handy |
 * |---|---|---|---|
 * | [OHNE] | nein | gleich | sendet Fassung 1 wie bisher, keine Zeile |
 * | [GESICHERT] | ja | ja | sendet Fassung 2, „Herkunft gesichert" |
 * | [FEHLT] | ja | nein | **sendet nichts**, „Herkunft fehlt — neu koppeln" |
 */
enum class Herkunftsstand {
    /** Der Server verlangt keinen Nachweis: Fassung 1. */
    OHNE,

    /** Verlangt und vorhanden: Fassung 2 mit dem Geräteschlüssel als Absender. */
    GESICHERT,

    /** Verlangt, aber das Paar ist nicht da: Es geht nichts hinaus, bis neu gekoppelt ist. */
    FEHLT,
}

/**
 * Die eine Stelle, die entscheidet, ob die Herkunft fehlt (seit KA-15,
 * E-KA-80) — der Sender fragt sie vor dem ersten Paket, die Ansicht für
 * ihre Zeile, das Trennen für seine Sperre. Drei Stellen mit je eigener
 * Rechnung zeigten früher oder später „gesichert", während nichts hinausgeht.
 *
 * WARUM KEIN RÜCKFALL AUF FASSUNG 1. `huelle: 2` sagt der Server nur einem
 * Gerät, dessen öffentlichen Schlüssel der Browser ins Gerätebuch
 * eingetragen hat (Vertrag 1a.7). Ein solches Handy, das ohne Schlüssel
 * weitersendete, lieferte Daten ohne Herkunftsnachweis unter einer Kennung,
 * für die der Browser einen erwartet — bis zum Stichtag nähme der Server sie
 * an, und die Einsatzansicht zeigte sie als „ohne Herkunft" nicht an. Die
 * Daten bleiben deshalb im Puffer, bis das Handy wieder ein Paar hat.
 *
 * WARUM „KEIN PAAR" UND „KEYSTORE ANTWORTET GERADE NICHT" HIER DASSELBE
 * SIND. [Geraeteschluessel.liefern] gibt in beiden Fällen `null`: Die
 * Schlüsselablage fängt jede Ausnahme und meldet „nicht vorhanden"
 * ([KeystoreSchluesselablage.absender]), und ob ein Eintrag fehlt oder der
 * Dienst des Systems nur nicht antwortet, lässt sich von hier nicht sicher
 * trennen — manche Geräte melden auch den Ausfall als „kein Eintrag". Die
 * Lage wird deshalb NICHT ABGELEGT, sondern bei jeder Frage neu gerechnet:
 * Kommt der Keystore zurück, ist sie beim nächsten Lauf [GESICHERT], und das
 * Handy sendet Fassung 2, ohne dass jemand etwas getan hat. Bis dahin zeigt
 * die App „neu koppeln" — im Zweifel einmal zu oft, aber nie eine Hülle der
 * Fassung 1 zu viel. Etwas anderes ist der Ausfall WÄHREND der Rechnung
 * ([Schluesselausfall]): Dort ist das Paar nachweislich da, und der Sender
 * meldet nur „später erneut".
 */
object Herkunft {

    /**
     * @param zugang die Kopplung aus dem Tresor; `null` (nicht gekoppelt) ist [Herkunftsstand.OHNE]
     * @param geraeteschluessel die Ablage des Paars; `null` heißt: Dieses
     *   Handy hat keine — mit `huelle: 2` fehlt die Herkunft dann ebenso
     */
    fun stand(zugang: Zugangsdaten?, geraeteschluessel: Geraeteschluessel?): Herkunftsstand = when {
        zugang == null || !zugang.huelle2 -> Herkunftsstand.OHNE
        geraeteschluessel?.liefern() != null -> Herkunftsstand.GESICHERT
        else -> Herkunftsstand.FEHLT
    }
}
