package org.genem.nadoku.handy.tresor

import javax.crypto.KeyGenerator
import javax.crypto.SecretKey

/**
 * Derselbe Umschlag, ein anderer Schlüsselhalter.
 *
 * WAS DAMIT GEPRÜFT IST UND WAS NICHT — und das gehört an diese Stelle, nicht
 * in eine Fußnote:
 *
 * GEPRÜFT ist der ganze [GcmTresorschluessel]: AES-256-GCM, frischer
 * Zufallswert je Schreibvorgang, Länge und Lage des Zufallswerts, die
 * Prüfsumme, der Rundlauf und die Abwesenheit von Klartext auf der Platte. Das
 * ist dieselbe Klasse, die auf dem Gerät läuft — nicht eine Nachbildung.
 *
 * NICHT GEPRÜFT ist die eine überschriebene Methode von
 * [KeystoreTresorschluessel]: dass der Schlüssel im `AndroidKeyStore` entsteht
 * und ihn nicht verlässt. Robolectric bringt keinen `AndroidKeyStore` mit
 * (`KeyStoreException: AndroidKeyStore not found`), und ein Emulator steht
 * nicht zur Verfügung (E-R45-8). Diese eine Zeile steht auf der Prüfliste des
 * Gerätetests.
 */
class PruefTresorschluessel : GcmTresorschluessel() {

    private val geheim: SecretKey =
        KeyGenerator.getInstance("AES").apply { init(SCHLUESSEL_BITS) }.generateKey()

    override fun schluessel(): SecretKey = geheim
}
