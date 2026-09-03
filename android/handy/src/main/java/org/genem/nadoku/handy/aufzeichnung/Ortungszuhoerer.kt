package org.genem.nadoku.handy.aufzeichnung

import android.location.Location
import android.location.LocationListener
import android.location.LocationManager
import android.os.Bundle

/**
 * Der Zuhörer der Ortung — **ausgeschrieben, nicht als Lambda** (E-S5Z-05).
 *
 * WARUM DAS EINE EIGENE KLASSE IST UND KEIN `LocationListener { … }`. Ein
 * SAM-Lambda setzt genau **eine** Methode der Schnittstelle um. Auf Android 8
 * bis 10 (API 26–29) — und `minSdk` ist 26 — hat `LocationListener` vier
 * abstrakte Methoden; die anderen drei bekamen ihre Vorgabe erst mit API 30.
 * Ruft das System dort `onProviderDisabled`, endet das in einem
 * `AbstractMethodError`: Der Vordergrunddienst stirbt in genau dem
 * Augenblick, in dem jemand den Standort ausschaltet (B-S5Z-01). Abgeleitet
 * aus der Plattform-API, nicht beobachtet — es gibt in diesem Container kein
 * solches Gerät (Prüfliste 9.2 Punkt 16).
 *
 * `OrtungszuhoererTest` zählt per Reflexion nach, dass alle vier Methoden
 * hier **selbst deklariert** sind. Das ist der billigste Schutz gegen ein
 * späteres „Vereinfachen" zurück zum Lambda.
 *
 * ES WIRD NUR AUF DEN GPS-ANBIETER GEHÖRT. `requestLocationUpdates` ist auf
 * ihn angemeldet; ein Rückruf zu einem anderen Anbieter sagt über die
 * Aufzeichnung nichts aus, und das Netzwerk-Ortungssignal wäre ohnehin zu
 * grob (E-S5Z-02).
 */
class Ortungszuhoerer(
    private val aufFund: (Location) -> Unit,
    private val aufAnbieterAn: () -> Unit,
    private val aufAnbieterAus: () -> Unit,
) : LocationListener {

    override fun onLocationChanged(ort: Location) {
        aufFund(ort)
    }

    override fun onProviderEnabled(anbieter: String) {
        if (anbieter == LocationManager.GPS_PROVIDER) aufAnbieterAn()
    }

    override fun onProviderDisabled(anbieter: String) {
        if (anbieter == LocationManager.GPS_PROVIDER) aufAnbieterAus()
    }

    /**
     * Seit API 29 wirkungslos — das System ruft sie nicht mehr. Sie steht
     * hier trotzdem, weil sie auf API 26–29 abstrakt ist und ihr Fehlen dort
     * den Dienst umbringt (siehe Klassenkommentar). Leer ist richtig: Was sie
     * meldete, misst der [Ortungswaechter] an den Fristen selbst.
     */
    @Deprecated("Ab API 29 ruft das System sie nicht mehr; auf API 26-29 muss sie da sein.")
    override fun onStatusChanged(anbieter: String?, status: Int, extras: Bundle?) {
        // Absichtlich leer.
    }
}
