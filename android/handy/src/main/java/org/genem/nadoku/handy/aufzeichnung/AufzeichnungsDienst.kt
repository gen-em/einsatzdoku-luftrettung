package org.genem.nadoku.handy.aufzeichnung

import android.Manifest
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.content.pm.ServiceInfo
import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Build
import android.os.Handler
import android.os.IBinder
import android.util.Log
import androidx.core.app.NotificationCompat
import androidx.core.app.ServiceCompat
import androidx.core.content.ContextCompat
import androidx.lifecycle.LifecycleService
import org.genem.nadoku.R
import org.genem.nadoku.handy.HauptActivity
import org.genem.nadoku.handy.NAdokuApp
import org.genem.nadoku.handy.senden.Sendetakt
import java.time.Instant

/**
 * Der Vordergrunddienst, der über den ganzen Dienst aufzeichnet (E-S4-05).
 *
 * WARUM EIN VORDERGRUNDDIENST UND KEINE ACTIVITY. Ein Dienst dauert zwölf
 * Stunden; in dieser Zeit ist der Bildschirm aus, das Handy in der Tasche,
 * eine andere App im Vordergrund. Alles außer einem Vordergrunddienst wird
 * von Android in dieser Lage beendet — und zwar ohne Meldung.
 *
 * DIE DAUERHAFTE BENACHRICHTIGUNG IST KEIN BEIWERK, sondern die Bedingung:
 * Android verlangt sie, und sie ist zugleich die einzige Auskunft darüber,
 * dass aufgezeichnet wird, solange die App nicht offen ist. Sie trägt deshalb
 * den Dienststand und die laufende Phase — nicht bloß „NAdoku läuft".
 *
 * `FOREGROUND_SERVICE_TYPE_LOCATION` ist ab Android 14 Pflicht und wird beim
 * Start mitgegeben; ohne ihn wirft das System `SecurityException`.
 *
 * **DIE ORTUNG LÄUFT ÜBER DEN `LocationManager`, NICHT ÜBER PLAY-DIENSTE**
 * (E-S4-04). Damit funktioniert die Kernfunktion — aufzeichnen und senden —
 * auch auf einem Gerät ohne Google-Dienste; nur die Uhr-Anbindung braucht sie.
 *
 * WAS HIER NICHT GEPRÜFT WERDEN KANN: alles, was diese Klasse ausmacht. Es
 * gibt keinen Emulator (E-R45-8), kein GPS und keine Möglichkeit,
 * Samsungs „Apps im Tiefschlaf" nachzustellen. Geprüft ist die Logik
 * dahinter — [Ausduenner] und
 * [org.genem.nadoku.handy.dienst.Dienstklammer] — gegen synthetische
 * Positionsströme. Der Rest gehört auf die Prüfliste des Gerätetests.
 */
class AufzeichnungsDienst : LifecycleService() {

    private lateinit var app: NAdokuApp
    private var ortung: LocationManager? = null

    private val takt = Sendetakt()
    private var letzterVersuch: Instant? = null
    private val taktgeber = Handler(android.os.Looper.getMainLooper())

    private val zuhoerer = LocationListener { ort -> aufnehmen(ort) }

    override fun onCreate() {
        super.onCreate()
        app = NAdokuApp.von(this)
        kanalAnlegen()
    }

    override fun onStartCommand(intent: Intent?, flags: Int, startId: Int): Int {
        super.onStartCommand(intent, flags, startId)

        if (intent?.action == BEENDEN) {
            beenden()
            return START_NOT_STICKY
        }

        starteImVordergrund()
        ortungAnfordern()
        taktStarten()

        /* START_STICKY: Räumt Android den Dienst bei Speicherknappheit ab,
         * startet es ihn wieder — mit `intent == null`, deshalb prüft der
         * Zweig oben auf die Aktion und nicht auf ihr Fehlen. Der Zustand
         * kommt ohnehin aus dem Puffer und nicht aus dem Intent; die
         * Aufzeichnung setzt damit fort, wo sie stand. */
        return START_STICKY
    }

    override fun onBind(intent: Intent): IBinder? {
        super.onBind(intent)
        return null
    }

    override fun onDestroy() {
        ortung?.removeUpdates(zuhoerer)
        taktgeber.removeCallbacksAndMessages(null)
        super.onDestroy()
    }

    // ---- Senden ------------------------------------------------------------

    /**
     * Der 15-Minuten-Takt (E-S4-07).
     *
     * Er läuft im Dienst und nicht in der Oberfläche: Gesendet werden muss
     * auch dann, wenn niemand hinsieht — das ist der ganze Sinn eines
     * Vordergrunddienstes.
     */
    private fun taktStarten() {
        taktgeber.removeCallbacksAndMessages(null)
        taktgeber.postDelayed(object : Runnable {
            override fun run() {
                sendeWenn(Sendetakt.Ausloeser.TAKT)
                taktgeber.postDelayed(this, Sendetakt.ABSTAND_S * 1000)
            }
        }, Sendetakt.ABSTAND_S * 1000)
    }

    private fun sendeWenn(ausloeser: Sendetakt.Ausloeser) {
        val jetzt = Instant.now()
        if (!takt.faellig(ausloeser, jetzt, letzterVersuch)) return
        letzterVersuch = jetzt
        /* Netz gehört nicht auf den Anzeigefaden. Ein eigener Faden je
         * Sendelauf ist hier richtig: Die Läufe überlappen nicht (der nächste
         * kommt frühestens in 15 Minuten), und ein Fadenvorrat für einen Lauf
         * je Viertelstunde wäre Aufwand ohne Gegenwert. */
        Thread {
            val bericht = app.sender.sendeAlles()
            if (bericht.anfragen > 0) {
                Log.i(
                    MARKE,
                    "Sendelauf: ${bericht.anfragen} Anfragen, " +
                        "${bericht.gesendetePunkte} Punkte, " +
                        "${bericht.fertigePakete} Pakete fertig, sauber=${bericht.sauber}",
                )
            }
            taktgeber.post { meldungAuffrischen() }
        }.start()
    }

    private fun starteImVordergrund() {
        val typ = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION
        } else {
            0
        }
        ServiceCompat.startForeground(this, MELDUNG_ID, meldung(), typ)
    }

    private fun ortungAnfordern() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION)
            != PackageManager.PERMISSION_GRANTED
        ) {
            // Ohne Freigabe gibt es nichts aufzuzeichnen. Der Dienst bleibt
            // trotzdem stehen: Die Benachrichtigung sagt dann, dass die
            // Ortung fehlt — das ist mehr, als ein stilles Beenden sagt.
            Log.w(MARKE, "Ortungsfreigabe fehlt — es wird nichts aufgezeichnet.")
            return
        }
        val m = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        ortung = m
        try {
            /* 1 s und 0 m: Die Ausdünnung entscheidet, was übernommen wird,
             * nicht der Abstandsfilter des Systems (E-S4-05). Ein
             * Systemfilter von 15 m sähe anders aus als die Regel der Uhr —
             * er kennt die 10-s-Bedingung nicht. */
            m.requestLocationUpdates(
                LocationManager.GPS_PROVIDER, TAKT_MS, 0f, zuhoerer, mainLooper,
            )
        } catch (e: SecurityException) {
            Log.w(MARKE, "Ortung abgelehnt: ${e.message}")
        } catch (e: IllegalArgumentException) {
            Log.w(MARKE, "Kein GPS-Anbieter auf diesem Gerät: ${e.message}")
        }
    }

    private fun aufnehmen(ort: Location) {
        val punkt = Rohpunkt(
            breite = ort.latitude,
            laenge = ort.longitude,
            hoehe = if (ort.hasAltitude()) ort.altitude else null,
            zeit = ort.time / 1000L,
            genauigkeitM = if (ort.hasAccuracy()) ort.accuracy else null,
        )
        if (app.klammer.positionsfund(punkt)) {
            meldungAuffrischen()
        }
    }

    private fun beenden() {
        ortung?.removeUpdates(zuhoerer)
        taktgeber.removeCallbacksAndMessages(null)
        // Dienstende ist ein Auslöser (E-S4-07): Was jetzt noch liegt, soll
        // nicht bis zum nächsten Dienst warten.
        sendeWenn(Sendetakt.Ausloeser.DIENSTENDE)
        ServiceCompat.stopForeground(this, ServiceCompat.STOP_FOREGROUND_REMOVE)
        stopSelf()
    }

    // ---- Benachrichtigung --------------------------------------------------

    /** Kanäle gibt es ab Android 8.0 — und das IST unser minSdk (E-S4-03). */
    private fun kanalAnlegen() {
        val kanal = NotificationChannel(
            KANAL, getString(R.string.dienst_kanal),
            /* NICHT `HIGH`: Der Kanal soll sichtbar sein, nicht hörbar. Ein
             * Ton bei jedem Auffrischen wäre im Einsatz das Gegenteil von
             * hilfreich. `LOW` zeigt die Meldung dauerhaft und schweigt. */
            NotificationManager.IMPORTANCE_LOW,
        ).apply {
            description = getString(R.string.dienst_kanal_zweck)
            setShowBadge(false)
        }
        (getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager)
            .createNotificationChannel(kanal)
    }

    private fun meldung(): Notification {
        val oeffnen = PendingIntent.getActivity(
            this, 0,
            Intent(this, HauptActivity::class.java)
                .addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP),
            PendingIntent.FLAG_IMMUTABLE,
        )

        val dienst = app.klammer.laufenderDienst()
        val text = if (dienst == null) {
            getString(R.string.dienst_meldung_ohne)
        } else {
            getString(
                R.string.dienst_meldung_laeuft,
                org.genem.nadoku.handy.dienst.Zeit.hhmm(
                    java.time.Instant.parse(dienst.begonnenAt)
                ),
            )
        }

        return NotificationCompat.Builder(this, KANAL)
            .setSmallIcon(R.drawable.symbol_meldung)
            .setContentTitle(getString(R.string.app_name))
            .setContentText(text)
            .setContentIntent(oeffnen)
            .setOngoing(true)
            .setSilent(true)
            .setCategory(NotificationCompat.CATEGORY_SERVICE)
            .setPriority(NotificationCompat.PRIORITY_LOW)
            .build()
    }

    private fun meldungAuffrischen() {
        (getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager)
            .notify(MELDUNG_ID, meldung())
    }

    companion object {
        private const val MARKE = "NAdoku"
        const val KANAL = "aufzeichnung"
        const val MELDUNG_ID = 1
        const val BEENDEN = "org.genem.nadoku.BEENDEN"

        /** 1-s-Abtastung wie die Uhr (E-S4-05). */
        const val TAKT_MS = 1000L

        fun starten(kontext: Context) {
            ContextCompat.startForegroundService(
                kontext, Intent(kontext, AufzeichnungsDienst::class.java),
            )
        }

        fun beenden(kontext: Context) {
            kontext.startService(
                Intent(kontext, AufzeichnungsDienst::class.java).setAction(BEENDEN),
            )
        }
    }
}
