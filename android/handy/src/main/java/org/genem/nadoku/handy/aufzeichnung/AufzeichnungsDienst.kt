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
import android.location.LocationManager
import android.net.ConnectivityManager
import android.net.Network
import android.os.Build
import android.os.Handler
import android.os.IBinder
import android.os.SystemClock
import android.provider.Settings
import android.util.Log
import androidx.core.app.NotificationCompat
import androidx.core.app.ServiceCompat
import androidx.core.content.ContextCompat
import androidx.lifecycle.LifecycleService
import org.genem.nadoku.R
import org.genem.nadoku.handy.HauptActivity
import org.genem.nadoku.handy.NAdokuApp
import org.genem.nadoku.handy.senden.Nachsenden
import org.genem.nadoku.handy.senden.Sendebericht
import org.genem.nadoku.handy.senden.Sendehinweis
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
 * **DER DIENST BEHAUPTET NICHT MEHR, ER ZEICHNE AUF** (E-S5Z-01). Bis 0.7.7
 * sagte die Meldung „Aufzeichnung läuft", sobald die Freigabe erteilt war —
 * auch bei ausgeschaltetem Standort, ohne Empfang und bei zu grosser
 * Streuung, also in drei Lagen, in denen im Puffer nichts landet (B-S5Z-07).
 * Der [Ortungswaechter] misst jetzt, was tatsächlich ankommt; die Meldung und
 * die Zustandszeile folgen ihm, und bei einer Lücke wird gewarnt (E-S5Z-04).
 *
 * WAS HIER NICHT GEPRÜFT WERDEN KANN: alles, was diese Klasse ausmacht. Es
 * gibt keinen Emulator (E-R45-8), kein GPS und keine Möglichkeit,
 * Samsungs „Apps im Tiefschlaf" nachzustellen. Geprüft ist die Logik
 * dahinter — [Ausduenner], [Ortungswaechter], [Ortungszuhoerer] und
 * [org.genem.nadoku.handy.dienst.Dienstklammer] — gegen synthetische
 * Positionsströme und eingespeiste Zeit. Der Rest gehört auf die Prüfliste
 * des Gerätetests.
 */
class AufzeichnungsDienst : LifecycleService() {

    private lateinit var app: NAdokuApp
    private var ortung: LocationManager? = null

    private val takt = Sendetakt()
    private var letzterVersuch: Instant? = null
    private val taktgeber = Handler(android.os.Looper.getMainLooper())

    /**
     * Läuft der 15-Minuten-Takt schon? (B-S5Z-14)
     *
     * `onStartCommand` kommt öfter als einmal: `HandyHorcher` startet den
     * Dienst bei **jeder** Uhrnachricht. Wer den Takt dabei jedes Mal neu
     * postet, setzt seine Frist zurück — ein von der Uhr geführter Dienst mit
     * Ereignissen dichter als 15 Minuten sendete bis zum Dienstende **gar
     * nicht**.
     */
    private var taktLaeuft = false

    /** Der Ortungswächter des laufenden Dienstes; `null` = keiner läuft. */
    private var waechter: Ortungswaechter? = null

    /** Der zuletzt gestellte Meldungstext — damit nicht ohne Anlass neu gepostet wird. */
    private var letzterMeldungstext: String? = null

    /**
     * Läuft das Dienstende gerade? (E-S5Z-07)
     *
     * Ab hier wird die Dauermeldung **nicht mehr** vom Wächter oder von einem
     * Punkt überschrieben: Sie sagt „Dienst beendet · sende …", bis der Lauf
     * zurück ist. Und danach wird sie gar nicht mehr gestellt — genau das war
     * B-S5Z-03, eine „andauernde" Meldung „Kein Dienst" ohne Dienst dahinter.
     */
    private var beendetGerade = false

    /**
     * Der Rückruf des Netzes (E-S5Z-10, B-S5Z-05).
     *
     * `onAvailable` kommt auf einem eigenen Faden des Systems; der Sprung auf
     * den Hauptfaden ist deshalb kein Stil, sondern nötig — [sendeWenn] fasst
     * [letzterVersuch] an.
     */
    private val netzRueckruf = object : ConnectivityManager.NetworkCallback() {
        override fun onAvailable(netz: Network) {
            taktgeber.post { sendeWenn(Sendetakt.Ausloeser.WIEDERVERBINDUNG) }
        }
        /* `onLost` bleibt leer: Der nächste Lauf stellt von selbst fest, dass
         * nichts geht, und meldet `spaeterErneut`. Etwas zu tun, wenn das
         * Netz WEG ist, hiesse, ohne Netz zu handeln. */
    }
    private var netzAngemeldet = false

    /**
     * Ausgeschrieben statt als Lambda, und das ist kein Stil (E-S5Z-05,
     * B-S5Z-01): Auf API 26–29 hat `LocationListener` vier abstrakte
     * Methoden. Begründung in [Ortungszuhoerer].
     */
    private val zuhoerer = Ortungszuhoerer(
        aufFund = { ort -> aufnehmen(ort) },
        aufAnbieterAn = { waechter?.let { waechterFolge(it.anbieterAn(jetztMs())) } },
        aufAnbieterAus = { waechter?.let { waechterFolge(it.anbieterAus(jetztMs())) } },
    )

    override fun onCreate() {
        super.onCreate()
        app = NAdokuApp.von(this)
        steht = true
        kanalAnlegen()
        netzRueckrufAnmelden()
    }

    /**
     * Die Zeitquelle des Wächters: **monoton**, nicht die Wanduhr.
     *
     * `ort.time` kommt vom GPS und die Systemzeit kann durch NTP oder von
     * Hand springen. Eine Frist, die einen Sprung mitmacht, meldet entweder
     * eine Lücke, die es nicht gab, oder verschweigt eine, die es gab.
     */
    private fun jetztMs(): Long = SystemClock.elapsedRealtime()

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
        steht = false
        ortung?.removeUpdates(zuhoerer)
        taktgeber.removeCallbacksAndMessages(null)
        netzRueckrufAbmelden()
        waechterAbraeumen()
        super.onDestroy()
    }

    private fun netzRueckrufAnmelden() {
        if (netzAngemeldet) return
        try {
            (getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager)
                .registerDefaultNetworkCallback(netzRueckruf)
            netzAngemeldet = true
        } catch (e: SecurityException) {
            Log.w(MARKE, "Netzrückruf nicht anmeldbar: ${e.message}")
        }
    }

    private fun netzRueckrufAbmelden() {
        if (!netzAngemeldet) return
        netzAngemeldet = false
        try {
            (getSystemService(Context.CONNECTIVITY_SERVICE) as ConnectivityManager)
                .unregisterNetworkCallback(netzRueckruf)
        } catch (e: IllegalArgumentException) {
            // War nicht angemeldet — dann ist nichts zu tun.
        }
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
        /* NUR EINMAL, UND MIT EIGENEM TOKEN (E-S5Z-23, B-S5Z-14). Vorher stand
         * hier `removeCallbacksAndMessages(null)` — ohne Token, also die ganze
         * Warteschlange des einen Handlers. Zwei Fehler in einer Zeile:
         *
         * 1. Sie löschte alles, was sonst noch am Handler hängt. Der
         *    10-Sekunden-Wächter wäre beim ersten Uhrereignis still gestorben.
         * 2. Sie setzte die 15-Minuten-Frist bei jedem `onStartCommand`
         *    zurück — und `HandyHorcher` ruft den Dienst bei jeder
         *    Uhrnachricht. Ein von der Uhr geführter Dienst sendete damit bis
         *    zum Dienstende gar nicht. */
        if (taktLaeuft) return
        taktLaeuft = true
        spaeter(TOKEN_TAKT, Sendetakt.ABSTAND_S * 1000, object : Runnable {
            override fun run() {
                sendeWenn(Sendetakt.Ausloeser.TAKT)
                spaeter(TOKEN_TAKT, Sendetakt.ABSTAND_S * 1000, this)
            }
        })
    }

    /**
     * Einen Rückruf unter einem **Token** einplanen.
     *
     * WARUM NICHT `postDelayed(r, token, ms)`: Die gibt es erst ab API 28,
     * `minSdk` ist 26 (Lint `NewApi`, gefunden im Baulauf). `postAtTime` mit
     * Token gibt es seit API 1 und tut dasselbe — sie nimmt nur einen
     * absoluten Zeitpunkt statt einer Spanne, und der Bezug dafür ist
     * `uptimeMillis()`, dieselbe Uhr, an der der Handler ohnehin hängt.
     */
    private fun spaeter(token: Any, inMs: Long, was: Runnable) {
        taktgeber.postAtTime(was, token, SystemClock.uptimeMillis() + inMs)
    }

    /**
     * Einen Sendelauf anstoßen, wenn der Auslöser fällig ist.
     *
     * ÜBER DEN SENDEAUSFÜHRER (E-S5Z-11) und nicht mehr über einen eigenen
     * `Thread` je Anlass. Der alte Kommentar sagte, die Läufe überlappten
     * nicht — er galt nur für den Takt; Oberfläche und Dienst starteten
     * längst nebeneinander (B-S5Z-11).
     *
     * UND OHNE NACHPOSTEN DER DAUERMELDUNG. Hier stand
     * `taktgeber.post { meldungAuffrischen() }`, und das lief auch dann noch,
     * wenn der Dienst inzwischen gestoppt war: eine „andauernde" Meldung
     * „Kein Dienst" ohne Dienst dahinter, die niemand wegbekam (B-S5Z-03).
     * Der Text der Meldung folgt ohnehin dem Wächter — er hat sich durch
     * einen Sendelauf nie geändert.
     */
    private fun sendeWenn(ausloeser: Sendetakt.Ausloeser) {
        val jetzt = Instant.now()
        if (!takt.faellig(ausloeser, jetzt, letzterVersuch)) return
        letzterVersuch = jetzt
        app.sendelauf { bericht -> protokolliere(ausloeser, bericht) }
    }

    private fun protokolliere(ausloeser: Sendetakt.Ausloeser, bericht: Sendebericht) {
        if (bericht.anfragen == 0) return
        Log.i(
            MARKE,
            "Sendelauf ($ausloeser): ${bericht.anfragen} Anfragen, " +
                "${bericht.gesendetePunkte} Punkte, " +
                "${bericht.fertigePakete} Pakete fertig, sauber=${bericht.sauber}",
        )
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
        val freigegeben = ContextCompat.checkSelfPermission(
            this, Manifest.permission.ACCESS_FINE_LOCATION,
        ) == PackageManager.PERMISSION_GRANTED

        val m = getSystemService(Context.LOCATION_SERVICE) as LocationManager
        ortung = m

        /* DER ANBIETER WIRD SELBST GELESEN und nicht auf den Rückruf gewartet.
         * Ob `requestLocationUpdates` bei ausgeschaltetem Anbieter sofort
         * `onProviderDisabled` liefert, ist je Plattformfassung verschieden
         * dokumentiert; darauf zu bauen hiesse, den häufigsten Fall dem
         * Zufall zu überlassen. Der Rückruf ist Zusatz, nicht Grundlage.
         *
         * ABSICHTLICH DER GPS-ANBIETER und nicht `isLocationEnabled()`: Im
         * Modus „Stromsparen" ist der Standort an und GPS aus — aufgezeichnet
         * wird aber nur mit GPS. */
        val anbieterAn = try {
            m.isProviderEnabled(LocationManager.GPS_PROVIDER)
        } catch (e: SecurityException) {
            Log.w(MARKE, "Anbieterzustand nicht lesbar: ${e.message}")
            false
        } catch (e: IllegalArgumentException) {
            Log.w(MARKE, "Kein GPS-Anbieter auf diesem Gerät: ${e.message}")
            false
        }

        waechterStarten(freigegeben, anbieterAn)

        if (!freigegeben) {
            // Ohne Freigabe gibt es nichts aufzuzeichnen. Der Dienst bleibt
            // trotzdem stehen: Meldung und Warnung sagen jetzt, dass die
            // Ortung fehlt — das ist mehr, als ein stilles Beenden sagt, und
            // seit E1 stimmt es auch (B-S5Z-02).
            Log.w(MARKE, "Ortungsfreigabe fehlt — es wird nichts aufgezeichnet.")
            return
        }
        try {
            /* 1 s und 0 m: Die Ausdünnung entscheidet, was übernommen wird,
             * nicht der Abstandsfilter des Systems (E-S4-05). Ein
             * Systemfilter von 15 m sähe anders aus als die Regel der Uhr —
             * er kennt die 10-s-Bedingung nicht.
             *
             * `MINDESTABSTAND_MS` ist ein MINDESTabstand, kein Takt
             * (B-S5Z-12): Mehr als 1 Hz kommt nie, weniger jederzeit. Genau
             * deshalb arbeitet der Wächter mit Fristen und nicht mit
             * Zählungen — eine ausbleibende Messung ist von einer
             * verlangsamten nicht zu unterscheiden.
             *
             * Eine erneute Anmeldung desselben Zuhörers ersetzt die
             * vorhandene; `onStartCommand` darf also mehrfach hier
             * vorbeikommen. */
            m.requestLocationUpdates(
                LocationManager.GPS_PROVIDER, MINDESTABSTAND_MS, 0f, zuhoerer, mainLooper,
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

        /* DER WÄCHTER ZÄHLT, WAS DIE AUSDÜNNUNG ANNEHMEN WÜRDE, nicht was der
         * Sensor meldet (E-S5Z-02) — sonst stünde „GPS ok", während jeder
         * Fund wegen Streuung verworfen wird. `brauchbar()` ist dieselbe
         * Schwelle, die `nimm()` gleich anwendet. */
        val jetzt = jetztMs()
        waechter?.let { w ->
            waechterFolge(
                if (Ausduenner.brauchbar(punkt)) w.brauchbarerFund(jetzt) else w.roherFund(jetzt)
            )
        }

        if (app.klammer.positionsfund(punkt)) {
            meldungAuffrischen()
        }
    }

    // ---- Ortungswächter ----------------------------------------------------

    /**
     * Den Wächter aufsetzen oder den vorhandenen nachziehen (E-S5Z-06).
     *
     * ES WIRD NICHT BEI JEDEM `onStartCommand` EIN NEUER GEBAUT. Der Dienst
     * wird bei jeder Uhrnachricht erneut gestartet; ein neuer Wächter setzte
     * dabei die Erstfix-Frist zurück und stellte die Warnung noch einmal —
     * eine Vibration je Knopfdruck am Handgelenk.
     */
    private fun waechterStarten(freigegeben: Boolean, anbieterAn: Boolean) {
        val jetzt = jetztMs()
        val vorhanden = waechter
        if (vorhanden == null) {
            val neu = Ortungswaechter(jetzt, freigegeben, anbieterAn)
            waechter = neu
            waechterFolge(neu.anfangsbefehl(jetzt))
        } else {
            waechterFolge(vorhanden.freigabe(freigegeben, jetzt))
            waechterFolge(
                if (anbieterAn) vorhanden.anbieterAn(jetzt) else vorhanden.anbieterAus(jetzt)
            )
        }
        waechtertaktStarten()
    }

    /**
     * Der Wächtertakt (Z-S5Z-03, 10 s) — **mit eigenem Token** (E-S5Z-23).
     *
     * Er misst die Stille: Ohne ihn fiele ein Signalverlust erst auf, wenn
     * wieder etwas ankommt, und das ist genau der Fall, der nie eintritt.
     */
    private fun waechtertaktStarten() {
        taktgeber.removeCallbacksAndMessages(TOKEN_WAECHTER)
        spaeter(TOKEN_WAECHTER, Ortungswaechter.TAKT_MS, object : Runnable {
            override fun run() {
                waechter?.let { waechterFolge(it.tick(jetztMs())) }
                spaeter(TOKEN_WAECHTER, Ortungswaechter.TAKT_MS, this)
            }
        })
    }

    /**
     * Was aus einer Entscheidung des Wächters folgt: der geteilte Zustand,
     * die Dauermeldung und die Warnung.
     *
     * Der Zustand liegt in [NAdokuApp.ortung] und nicht im Puffer — er ist
     * ein Augenblickswert und überlebt einen Neustart bewusst nicht
     * (E-S5Z-01). Nach einem Neustart wird er neu gemessen, nicht
     * wiederhergestellt; eine wiederhergestellte Aussage über den GPS-Empfang
     * von vorhin wäre wertlos.
     */
    private fun waechterFolge(befehl: Warnbefehl) {
        val w = waechter ?: return
        app.ortung = Ortungslage(w.stand, w.seitMs, w.letzterFundMs)
        meldungAuffrischen()
        when (befehl) {
            Warnbefehl.POSTEN -> warnungStellen(w.stand, w.seitMs)
            Warnbefehl.LOESCHEN -> warnungLoeschen()
            Warnbefehl.NICHTS -> Unit
        }
    }

    private fun waechterAbraeumen() {
        waechter = null
        taktgeber.removeCallbacksAndMessages(TOKEN_WAECHTER)
        warnungLoeschen()
        app.ortung = null
    }

    /** Volle Minuten seit einem Zeitpunkt der monotonen Uhr. */
    private fun minutenSeit(seitMs: Long): Int =
        ((jetztMs() - seitMs) / 60_000L).toInt().coerceAtLeast(1)

    /**
     * **Das Dienstende hält den Vordergrunddienst, bis der Lauf zurück ist**
     * (E-S5Z-07, Ablauf 5.1).
     *
     * DAS IST DER BELEGTE FEHLER DIESES PAKETS. Bis 0.8.1 stand hier:
     * Sendefaden starten, `stopForeground`, `stopSelf` — in dieser
     * Reihenfolge, ohne dazwischen zu warten. Der Lauf lief dann in einem
     * Prozess **ohne Vordergrunddienst** weiter, und den darf Android
     * jederzeit abräumen; Samsung tut es besonders gern. Wer die App direkt
     * nach „Beenden" wegwischte, verlor den Abschluss-Upload — und weil es
     * keinen zweiten Versuch gab, blieb der Diensttag im Web ohne Ende
     * stehen, bis der nächste Dienst lief. Die Diagnose des Vorfalls vom
     * 02.09.2026 hat genau diesen Weg bestätigt (H2, Kette B1/B2).
     *
     * Die Zeitlimits des Netzwegs begrenzen die Wartezeit (15 s Verbindung,
     * 30 s Lesen je Anfrage, `Netzweg.kt`); ein Dienstende braucht rund
     * zwanzig Anfragen, und im schlechten Fall bricht es früh mit
     * `spaeterErneut` ab. Ein Typwechsel des Dienstes auf `dataSync` für
     * diese Sekunden wäre eine weitere Berechtigung für nichts.
     */
    private fun beenden() {
        if (beendetGerade) return          // Doppeltes „Beenden" ist ein Klick zu viel, kein Zustand.
        beendetGerade = true

        ortung?.removeUpdates(zuhoerer)
        /* Der Wächter geht als Erstes: Ohne ihn kann keine Warnung mehr
         * hereinkommen, während der Dienst schon abgebaut wird. */
        waechterAbraeumen()
        taktgeber.removeCallbacksAndMessages(TOKEN_TAKT)
        taktLaeuft = false
        netzRueckrufAbmelden()

        // Die Meldung sagt jetzt, was geschieht — der Dienst steht noch.
        meldungStellen(getString(R.string.dienst_meldung_beendet_sendet))

        // Dienstende ist ein Auslöser (E-S4-07): Was jetzt noch liegt, soll
        // nicht bis zum nächsten Dienst warten.
        letzterVersuch = Instant.now()
        app.sendelauf { bericht ->
            protokolliere(Sendetakt.Ausloeser.DIENSTENDE, bericht)
            taktgeber.post { nachDemDienstende(bericht) }
        }
    }

    /**
     * Was nach dem Abschluss-Lauf übrig bleibt — auf dem Hauptfaden.
     *
     * Drei Ausgänge, und jeder sagt der NutzerIn etwas anderes:
     *
     * | Ausgang | Was bleibt |
     * |---|---|
     * | alles gesendet | **keine** Meldung. Es gibt nichts zu tun. |
     * | Rückstand, kein Netz | Nachsende-Job **und** ein stiller Hinweis |
     * | 401 | Hinweis „Gerät neu koppeln" — **kein** Job |
     *
     * Der 401-Fall bekommt bewusst keinen Job: Wiederholen hilft nicht, es
     * hilft nur eine neue Kopplung, und die kann nur ein Mensch. Ein Job, der
     * es trotzdem alle 30 Sekunden versucht, verbrennt Akku für ein sicheres
     * Nein.
     */
    private fun nachDemDienstende(bericht: Sendebericht) {
        val rest = app.puffer.rueckstand()
        when {
            bericht.pausiert ->
                Sendehinweis.stellen(this, getString(R.string.hinweis_schluessel_abgewiesen))

            Nachsenden.planen(bericht, rest, dienstLaeuft = false) -> {
                Nachsenden.einplanen(this)
                Sendehinweis.stellen(
                    this,
                    resources.getQuantityString(R.plurals.hinweis_warten, rest, rest),
                )
            }

            /* Rückstand ohne `spaeterErneut`: Der Lauf hat getan, was ging,
             * und etwas ist trotzdem liegen geblieben — abgewiesene Pakete
             * etwa. Ein Job hülfe nicht; die Ansicht zeigt es (E-S5Z-12). */
            rest > 0 -> Sendehinweis.stellen(
                this,
                resources.getQuantityString(R.plurals.hinweis_warten, rest, rest),
            )

            else -> Sendehinweis.loeschen(this)
        }

        Log.i(MARKE, "Dienstende abgeschlossen: Rückstand $rest, pausiert=${bericht.pausiert}")
        ServiceCompat.stopForeground(this, ServiceCompat.STOP_FOREGROUND_REMOVE)
        stopSelf()
    }

    // ---- Benachrichtigung --------------------------------------------------

    /**
     * Kanäle gibt es ab Android 8.0 — und das IST unser minSdk (E-S4-03).
     *
     * ZWEI KANÄLE, UND DAS IST DER PUNKT (E-S5Z-04). Android überlässt die
     * Einstellungen eines Kanals nach dem Anlegen der NutzerIn; „Aufzeichnung"
     * ist bewusst `LOW` und stumm und soll es bleiben. Eine Warnung, die
     * spürbar sein muss, kann deshalb nicht auf demselben Kanal liegen —
     * sonst hinge sie an einer Einstellung, die für die Dauermeldung gemacht
     * wurde.
     */
    private fun kanalAnlegen() {
        val melder = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager

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

        val warnkanal = NotificationChannel(
            KANAL_WARNUNG, getString(R.string.warnung_kanal),
            /* `DEFAULT` und nicht `HIGH`: sichtbar und spürbar, aber ohne
             * Vollbildmeldung. Der Auftraggeber hat es am 02.09.2026 so
             * bestellt — sehen und fühlen, nicht hören: Ein Ton im Einsatz
             * hilft niemandem, eine Vibration in der Jackentasche schon. */
            NotificationManager.IMPORTANCE_DEFAULT,
        ).apply {
            description = getString(R.string.warnung_kanal_zweck)
            enableVibration(true)
            setSound(null, null)
        }

        melder.createNotificationChannel(kanal)
        melder.createNotificationChannel(warnkanal)
    }

    /**
     * Der Text, den Dauermeldung **und** Zustandszeile tragen (4.3).
     *
     * Ein Wortlaut für beide, weil zwei Wortlaute für denselben Zustand
     * früher oder später auseinanderlaufen — und weil die Dauermeldung die
     * einzige Auskunft ist, solange die App nicht offen steht.
     *
     * „AUFZEICHNUNG LÄUFT" STEHT NUR BEI [Ortungsstand.OK]. In allen anderen
     * Zuständen „Dienst läuft", weil das wahr ist und das andere nicht.
     */
    private fun meldungstext(): String {
        val dienst = app.klammer.laufenderDienst() ?: return getString(R.string.dienst_meldung_ohne)
        val seit = org.genem.nadoku.handy.dienst.Zeit.hhmm(
            java.time.Instant.parse(dienst.begonnenAt)
        )
        val lage = app.ortung
        return when (lage?.stand) {
            Ortungsstand.OK -> getString(R.string.dienst_laeuft_ok, seit)
            Ortungsstand.KEIN_SIGNAL ->
                getString(R.string.dienst_laeuft_kein_signal, seit, minutenSeit(lage.seitMs))
            Ortungsstand.UNGENAU -> getString(R.string.dienst_laeuft_ungenau, seit)
            Ortungsstand.STANDORT_AUS -> getString(R.string.dienst_laeuft_standort_aus, seit)
            Ortungsstand.FREIGABE_FEHLT -> getString(R.string.dienst_laeuft_freigabe_fehlt, seit)
            /* `SUCHT` und „noch nichts gemessen" fallen zusammen, und das ist
             * richtig: Beides heisst, dass die App es nicht weiss. */
            Ortungsstand.SUCHT, null -> getString(R.string.dienst_laeuft_sucht, seit)
        }
    }

    private fun meldung(text: String = meldungstext()): Notification {
        val oeffnen = PendingIntent.getActivity(
            this, 0,
            Intent(this, HauptActivity::class.java)
                .addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP),
            PendingIntent.FLAG_IMMUTABLE,
        )

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

    /**
     * Neu stellen — aber nur, wenn sich der Text geändert hat.
     *
     * Der Wächtertakt kommt alle zehn Sekunden vorbei und jeder übernommene
     * Punkt ruft ebenfalls hier an; ohne diesen Vergleich stellte die App die
     * Meldung stündlich einige hundert Mal neu, ohne dass ein Zeichen anders
     * aussähe.
     */
    private fun meldungAuffrischen() {
        /* AB DEM DIENSTENDE NICHT MEHR (B-S5Z-03). Ab hier steht „Dienst
         * beendet · sende …" und soll stehen bleiben, bis der Dienst geht;
         * danach darf gar nichts mehr gestellt werden. */
        if (beendetGerade) return
        meldungStellen(meldungstext())
    }

    private fun meldungStellen(text: String) {
        if (text == letzterMeldungstext) return
        letzterMeldungstext = text
        (getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager)
            .notify(MELDUNG_ID, meldung(text))
    }

    // ---- Warnung (ID 3, Kanal „Warnungen") ---------------------------------

    /**
     * Die Warnung: sichtbar in der Leiste, spürbar am Bein, stumm (E-S5Z-04).
     *
     * ANTIPPEN FÜHRT DORTHIN, WO ES ZU BEHEBEN IST. Bei ausgeschaltetem
     * Standort sind das die Systemeinstellungen — der Weg über die App wäre
     * ein Umweg mit einer Zwischenstation. Sonst in die App.
     *
     * GRENZE, DIE NICHT ZU UMGEHEN IST: „Nicht stören" kann die Vibration
     * dieses Kanals unterdrücken. Das ist die Entscheidung der NutzerIn und
     * bleibt es; die sichtbare Meldung und die rote Zustandszeile bleiben
     * davon unberührt. Auf die Prüfliste (9.2 Punkt 4, einmal mit „Nicht
     * stören").
     */
    private fun warnungStellen(stand: Ortungsstand, seitMs: Long) {
        val text = when (stand) {
            Ortungsstand.STANDORT_AUS -> getString(R.string.warnung_standort_aus)
            Ortungsstand.KEIN_SIGNAL -> getString(R.string.warnung_kein_signal, minutenSeit(seitMs))
            Ortungsstand.UNGENAU -> getString(R.string.warnung_ungenau)
            Ortungsstand.FREIGABE_FEHLT -> getString(R.string.warnung_freigabe_fehlt)
            Ortungsstand.OK, Ortungsstand.SUCHT -> return
        }

        val ziel = if (stand == Ortungsstand.STANDORT_AUS) {
            Intent(Settings.ACTION_LOCATION_SOURCE_SETTINGS)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        } else {
            Intent(this, HauptActivity::class.java)
                .addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP)
        }

        val meldung = NotificationCompat.Builder(this, KANAL_WARNUNG)
            .setSmallIcon(R.drawable.symbol_meldung)
            .setContentTitle(getString(R.string.warnung_titel))
            .setContentText(text)
            .setStyle(NotificationCompat.BigTextStyle().bigText(text))
            .setContentIntent(
                PendingIntent.getActivity(this, 1, ziel, PendingIntent.FLAG_IMMUTABLE)
            )
            .setAutoCancel(true)
            .setCategory(NotificationCompat.CATEGORY_ERROR)
            .setPriority(NotificationCompat.PRIORITY_DEFAULT)
            .build()

        (getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager)
            .notify(WARNUNG_ID, meldung)
        Log.w(MARKE, "Ortung: $stand — gewarnt.")
    }

    private fun warnungLoeschen() {
        (getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager)
            .cancel(WARNUNG_ID)
    }

    companion object {
        private const val MARKE = "NAdoku"
        const val KANAL = "aufzeichnung"
        const val KANAL_WARNUNG = "warnungen"

        /** Z-S5Z-10: 1 Dauermeldung · 2 Hinweis Dienstende (E2) · 3 Warnung. */
        const val MELDUNG_ID = 1
        const val WARNUNG_ID = 3

        const val BEENDEN = "org.genem.nadoku.BEENDEN"

        /**
         * Wie oft der Sensor **höchstens** melden soll (E-S4-05).
         *
         * Hiess bis 0.7.7 `TAKT_MS` mit dem Kommentar „1-s-Abtastung wie die
         * Uhr". Das war halb richtig und deshalb irreführend (B-S5Z-12):
         * `requestLocationUpdates` nimmt einen **Mindest**abstand, keinen
         * Takt. Mehr als 1 Hz kommt nie, weniger jederzeit — und genau
         * deshalb misst der [Ortungswaechter] Fristen statt Zählungen.
         */
        const val MINDESTABSTAND_MS = 1000L

        /**
         * Zwei Token an einem Handler (E-S5Z-23).
         *
         * Sie sind kein Beiwerk: `removeCallbacksAndMessages(null)` löscht die
         * **ganze** Warteschlange. Ohne getrennte Token nähme der eine Takt
         * dem anderen den Rückruf weg, und zwar ohne Fehlermeldung.
         *
         * Eingeplant wird über [spaeter] und nicht über `postDelayed` mit
         * Token — die gibt es erst ab API 28, und `minSdk` ist 26.
         */
        private val TOKEN_TAKT = Any()
        private val TOKEN_WAECHTER = Any()

        /**
         * **Läuft der Vordergrunddienst gerade?** (E-S5Z-08)
         *
         * Die Marke wird gebraucht, bevor irgendjemand `startService()` oder
         * `stopService()` ruft: Aus dem Hintergrund — und `HandyHorcher` ist
         * Hintergrund — wirft `startService` ab Android 8
         * `IllegalStateException`, und ein Stopp-Befehl an einen Dienst, den
         * es nicht gibt, **startet ihn erst**, um ihn dann zu beenden.
         *
         * `@Volatile`, weil sie im Dienst (Hauptfaden) gesetzt und im
         * `HandyHorcher` (Dienstfaden des Data Layer) gelesen wird.
         */
        @Volatile
        var steht: Boolean = false
            private set

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
