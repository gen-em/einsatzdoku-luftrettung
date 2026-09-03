package org.genem.nadoku.handy.senden

import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import androidx.core.app.NotificationCompat
import org.genem.nadoku.R
import org.genem.nadoku.handy.HauptActivity
import org.genem.nadoku.handy.aufzeichnung.AufzeichnungsDienst

/**
 * Die Meldung nach dem Dienstende (ID 2, E-S5Z-07).
 *
 * WOZU SIE DA IST. Nach „Dienst beenden" ist die App zu, das Handy in der
 * Tasche, und der Mensch geht nach Hause. Bleibt etwas ungesendet liegen, ist
 * diese Meldung die **einzige** Auskunft darüber — sonst erfährt es niemand
 * bis zum nächsten Öffnen der App, und im Web steht der Diensttag derweil
 * ohne Ende da.
 *
 * SIE IST STILL UND WEGWISCHBAR, anders als die Warnung des Wächters: Hier
 * ist nichts zu tun. Der Nachsende-Job arbeitet von selbst, sobald Netz da
 * ist; die Meldung sagt nur, dass er es noch vor sich hat, und verschwindet,
 * wenn er fertig ist. Eine Vibration dafür wäre eine Aufforderung ohne
 * Handlung.
 *
 * SIE LIEGT AUF DEM KANAL „AUFZEICHNUNG" und nicht auf „Warnungen": Der
 * Warnkanal vibriert, und dieser Hinweis soll das gerade nicht.
 */
object Sendehinweis {

    /** Z-S5Z-10: 1 Dauermeldung · 2 Hinweis Dienstende · 3 Warnung. */
    const val MELDUNG_ID = 2

    fun stellen(kontext: Context, text: String) {
        val oeffnen = PendingIntent.getActivity(
            kontext, 2,
            Intent(kontext, HauptActivity::class.java)
                .addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP),
            PendingIntent.FLAG_IMMUTABLE,
        )
        val meldung = NotificationCompat.Builder(kontext, AufzeichnungsDienst.KANAL)
            .setSmallIcon(R.drawable.symbol_meldung)
            .setContentTitle(kontext.getString(R.string.app_name))
            .setContentText(text)
            .setStyle(NotificationCompat.BigTextStyle().bigText(text))
            .setContentIntent(oeffnen)
            /* NICHT `setOngoing`: Wer sie wegwischt, hat sie zur Kenntnis
             * genommen — der Job läuft davon unberührt weiter. Eine Meldung,
             * die sich nicht wegwischen lässt, obwohl es nichts zu tun gibt,
             * ist eine Zumutung (und war genau der Fehler B-S5Z-03: eine
             * „andauernde" Meldung ohne Dienst dahinter). */
            .setAutoCancel(true)
            .setSilent(true)
            .setCategory(NotificationCompat.CATEGORY_STATUS)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()
        melder(kontext).notify(MELDUNG_ID, meldung)
    }

    /** Zurückziehen — es liegt nichts mehr an. */
    fun loeschen(kontext: Context) = melder(kontext).cancel(MELDUNG_ID)

    private fun melder(kontext: Context) =
        kontext.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
}
