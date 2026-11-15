package org.genem.nadoku.handy.tresor

import org.genem.nadoku.handy.senden.Geraetehuelle
import java.security.interfaces.ECPrivateKey
import java.security.interfaces.ECPublicKey
import javax.crypto.KeyAgreement

/**
 * Der Geräteschlüssel im Speicher — für Prüffälle.
 *
 * Dieselben Regeln wie auf dem Gerät ([AbgelegterGeraeteschluessel]), nur
 * ohne Datei und ohne Keystore, den Robolectric nicht mitbringt. `inhalt`
 * (die Verwaltung) und `ablage` (die Schlüssel) sind offen, damit ein
 * Prüffall „Neustart" spielen kann: eine zweite Instanz auf demselben Inhalt
 * und derselben Ablage — so, wie Datei und Keystore einen Prozess überdauern.
 */
class SpeicherGeraeteschluessel(
    var inhalt: ByteArray? = null,
    val ablage: SpeicherSchluesselablage = SpeicherSchluesselablage(),
) : AbgelegterGeraeteschluessel(ablage) {
    override fun laden(): ByteArray? = inhalt
    override fun ablegen(klartext: ByteArray?) {
        ablage.draht.schritt()
        inhalt = klartext
    }
}

/**
 * Ein Absender mit dem privaten Teil in der Hand — NUR IM PRÜFFALL.
 *
 * Die App hat so etwas seit KA-06f nicht mehr: Dort steht hinter dem
 * [Absender] der Keystore. Die Prüffälle brauchen es zweimal: als Schlüssel
 * der Software-Ablage, und für die Vektoren der Referenz, deren Absender
 * ein FESTER Schlüssel ist (`tools/proben/huelle/vektoren-2.json`).
 *
 * `vereinbarungen` zählt mit — ein Prüffall kann daran ablesen, dass `Z_s`
 * je Hülle neu bestellt und nicht aufbewahrt wird.
 */
class SoftwareAbsender(privat: ByteArray, spk: ByteArray) : Absender {

    val privat: ByteArray = privat.copyOf()
    override val spk: ByteArray = spk.copyOf()
    var vereinbarungen = 0
        private set

    init {
        Geraetehuelle.oeffentlich(this.spk)     // wirft, wenn es kein Punkt der Kurve ist
    }

    override fun vereinbaren(konto: ECPublicKey): ByteArray {
        vereinbarungen++
        return KeyAgreement.getInstance("ECDH").run {
            init(Geraetehuelle.privat(privat)); doPhase(konto, true); generateSecret()
        }
    }

    companion object {
        /** Ein neues Paar aus dem gewöhnlichen Erzeuger der Plattform. */
        fun erzeugen(): SoftwareAbsender {
            val paar = Geraetehuelle.neuesPaar()
            return SoftwareAbsender(
                Geraetehuelle.fest32((paar.private as ECPrivateKey).s),
                Geraetehuelle.sec1(paar.public as ECPublicKey),
            )
        }
    }
}

/** Der gespielte Prozesstod. Ein `Error`, damit ihn kein `catch (e: Exception)` der App schluckt. */
class Prozesstod : Error("Prozess beendet (Prüffall)")

/**
 * Zählt die schreibenden Schritte an Verwaltung und Schlüsselablage und
 * lässt den Prozess VOR einem bestimmten sterben.
 *
 * Anlass: Nr. 563 — der Keystore kann keinen Eintrag umbenennen; das
 * Übernehmen besteht deshalb aus mehreren Schritten an zwei Ablagen, und
 * zwischen je zweien darf der Prozess enden, ohne dass das gültige Paar
 * verloren geht oder ein ungeprüftes gültig wird.
 */
class Stolperdraht {
    /** Schritte seit dem letzten [scharf] oder [aus]. */
    var schritte = 0
        private set
    private var todVor = 0

    /** Der `n`-te schreibende Schritt von jetzt an findet nicht mehr statt (ab 1). */
    fun scharf(n: Int) {
        schritte = 0
        todVor = n
    }

    fun aus() {
        schritte = 0
        todVor = 0
    }

    fun schritt() {
        schritte++
        if (todVor != 0 && schritte >= todVor) throw Prozesstod()
    }
}

/**
 * Die Schlüsselablage im Speicher — gewöhnliche Schlüssel aus dem
 * `KeyPairGenerator`, wo auf dem Gerät der Keystore steht
 * ([KeystoreSchluesselablage]).
 *
 * GEPRÜFT IST DAMIT: alles, was [AbgelegterGeraeteschluessel] mit einer
 * Schlüsselablage tut — die Reihenfolge, das Aufräumen, der Vergleich der
 * öffentlichen Teile. NICHT GEPRÜFT: dass ein Schlüssel im Keystore entsteht,
 * ihn nicht verlässt und dort rechnet. Das belegt nur der Gerätetest
 * (`GeraeteschluesselGeraetTest`).
 */
class SpeicherSchluesselablage : Schluesselablage {

    val draht = Stolperdraht()
    private val eintraege = LinkedHashMap<String, SoftwareAbsender>()

    override fun erzeugen(name: String): ByteArray {
        draht.schritt()
        return SoftwareAbsender.erzeugen().also { eintraege[name] = it }.spk
    }

    override fun absender(name: String): Absender? = eintraege[name]

    override fun loeschen(name: String) {
        draht.schritt()
        eintraege.remove(name)
    }

    override fun namen(): Set<String> = eintraege.keys.toSet()

    /** Nur im Prüffall: einen FESTEN Schlüssel unter einen Namen legen (oder einen vorhandenen vertauschen). */
    fun einlegen(name: String, absender: SoftwareAbsender) {
        eintraege[name] = absender
    }

    /** Nur im Prüffall: der private Teil zu einem Namen — um zu zeigen, dass er NICHT in der Verwaltung steht. */
    fun privat(name: String): ByteArray? = eintraege[name]?.privat
}
