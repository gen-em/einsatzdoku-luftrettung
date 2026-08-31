package org.genem.nadoku.handy

import android.content.Context
import androidx.core.content.edit
import org.genem.nadoku.handy.kopplung.Serveradresse

/**
 * Die Einstellungen der App — alles, was **kein Geheimnis** ist.
 *
 * DIE TRENNUNG VOM TRESOR IST ABSICHT. `device_id` und `api_key` liegen
 * verschlüsselt in einer eigenen Datei (E-S4-13); hier steht, was ohne Schaden
 * im Klartext stehen darf: die Server-Adresse, später die Logo-Wahl und die
 * Sperre der Uhr. Beides in eine Ablage zu werfen hieße, den Schutz der
 * strengeren an der schwächeren auszurichten oder umgekehrt Einstellungen zu
 * verschlüsseln, die auf jedem Werbebanner stehen könnten.
 *
 * Die Server-Adresse wird **normalisiert gespeichert** (Basis mit
 * abschließendem `/`), nicht so, wie sie getippt wurde: Sonst müsste jede
 * lesende Stelle die Toleranzregeln kennen.
 */
class Einstellungen(kontext: Context) {

    private val ablage =
        kontext.applicationContext.getSharedPreferences(ABLAGE, Context.MODE_PRIVATE)

    /** Basisadresse des Servers, `null` wenn keine gesetzt ist. */
    var serverBasis: String?
        get() = ablage.getString(SERVER, null)
        set(wert) {
            val sauber = Serveradresse.normalisiere(wert)
            ablage.edit {
                if (sauber == null) remove(SERVER) else putString(SERVER, sauber)
            }
        }

    private companion object {
        const val ABLAGE = "nadoku"
        const val SERVER = "server_basis"
    }
}
