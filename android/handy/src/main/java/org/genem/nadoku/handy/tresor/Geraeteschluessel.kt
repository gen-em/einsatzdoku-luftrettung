package org.genem.nadoku.handy.tresor

import org.genem.nadoku.handy.kopplung.Kopplungswert
import org.genem.nadoku.handy.senden.Geraetehuelle
import org.json.JSONObject
import java.io.File
import java.security.PrivateKey
import java.security.interfaces.ECPrivateKey
import java.security.interfaces.ECPublicKey
import java.util.Base64

/**
 * Das Gerätepaar: der statische P-256-Schlüssel dieses Handys (KA-06c).
 *
 * WOFÜR. Mit ihm verschließt das Handy Hüllen der Fassung 2
 * ([Geraetehuelle.verschliessen2]); der Browser des Kontos erkennt daran,
 * dass eine Hülle von dem Gerät kommt, das bei der Kopplung über den
 * Vergleichswert bestätigt wurde. Öffnen kann man mit ihm nichts.
 *
 * `privat` ist `d`, 32 Byte big-endian; `spk` der öffentliche Teil, SEC1
 * unkomprimiert (65 Byte). Beide werden zusammen abgelegt: Aus `d` allein
 * ließe sich `spk` nur mit einer Punktmultiplikation gewinnen, und die
 * geben die Bordmittel nicht her.
 *
 * KEIN `toString()` MIT INHALT, wie bei [Zugangsdaten], und aus demselben
 * Grund.
 */
class Geraetepaar(privat: ByteArray, spk: ByteArray) {

    val privat: ByteArray = privat.copyOf()
    val spk: ByteArray = spk.copyOf()

    init {
        require(this.privat.size == 32) { "kein privater P-256-Schlüssel" }
        Geraetehuelle.oeffentlich(this.spk)     // wirft, wenn es kein Punkt der Kurve ist
    }

    /** Der private Schlüssel für die Schlüsselvereinbarung. */
    fun schluessel(): PrivateKey = Geraetehuelle.privat(privat)

    /** Die Kennung, unter der dieses Handy in Hülle und Gerätebuch steht. */
    fun sid(): ByteArray = Geraetehuelle.sid(spk)

    override fun toString(): String = "Geraetepaar(Schlüssel verborgen)"

    companion object {
        /** Ein neues Paar aus dem Zufallsgenerator der Plattform. */
        fun erzeugen(): Geraetepaar {
            val paar = Geraetehuelle.neuesPaar()
            return Geraetepaar(
                Geraetehuelle.fest32((paar.private as ECPrivateKey).s),
                Geraetehuelle.sec1(paar.public as ECPublicKey),
            )
        }
    }
}

/** Was bei `start` im Block `schluessel` mitgeht (JSON-Vertrag 1a.7, Schritt 1). */
class Kopplungsbeginn(val spk: ByteArray, val zusage: ByteArray)

/** Was [Geraeteschluessel.festlegen] ergibt. */
sealed class Festlegung {
    /**
     * Kontoschlüssel und `N_b` sind festgehalten. Jetzt — und erst jetzt —
     * geht `nG` mit `offenlegen` an den Server, und `wert` steht auf dem
     * Bildschirm.
     */
    class Offen(val nG: ByteArray, val wert: String) : Festlegung()

    /** Es bleibt bei nichts: Die Kopplung ist abzubrechen ([Geraeteschluessel.verwerfen]). */
    class Abbruch(val grund: Grund) : Festlegung()

    enum class Grund {
        /** Es läuft keine Kopplung mit Schlüssel (kein [Geraeteschluessel.beginnen], oder verworfen). */
        KEIN_VERSUCH,

        /** Kontoschlüssel ist kein Punkt der Kurve, oder `N_b` hat nicht 16 Byte. */
        FORM,

        /**
         * Eine frühere Antwort nannte einen anderen Kontoschlüssel oder ein
         * anderes `N_b`. Nach dem Offenlegen von `N_g` darf sich nichts mehr
         * ändern, was in den Wert eingeht — sonst rechnete, wer den Server
         * beschreibt, sich in Ruhe ein passendes Paar aus.
         */
        ANDERES_PAAR,
    }
}

/**
 * Die Ablage des Gerätepaars und der laufenden Kopplung.
 *
 * ZWEI FÄCHER. Das **gültige** Paar gehört zur fertigen Kopplung und
 * verschließt, was gesendet wird. Der **Versuch** entsteht bei `start` und
 * trägt ein NEUES Paar, `N_g` und — sobald der Server sie nennt —
 * Kontoschlüssel und `N_b`. Erst [uebernehmen] macht aus dem Versuch das
 * gültige Paar. Eine abgebrochene Kopplung lässt das bisherige also stehen,
 * wie auf der Garmin (`Huelle2.kopplungBeginnen`).
 *
 * DER VERSUCH LIEGT AUF DER PLATTE, nicht nur im Speicher — anders als auf
 * der Garmin, und aus demselben Grund wie die schwebenden Zugangsdaten im
 * [Schluesseltresor]: Zwischen `start` und dem Ja liegen Minuten, in denen
 * Android den Prozess beenden darf. Ein Versuch, der dabei sein `N_g`
 * verlöre, könnte nie mehr offenlegen, wozu er sich mit der Zusage
 * verpflichtet hat.
 *
 * WER HIER DIE REIHENFOLGE HÄLT. `N_g` kommt aus dieser Ablage nur über
 * [festlegen] heraus, also erst, wenn Kontoschlüssel und `N_b` festgehalten
 * sind — und dieselben bleiben. Das ist die halbe Sicherheit des
 * Vergleichswerts ([Kopplungswert]); die andere Hälfte ist der Mensch, der
 * die sechs Ziffern vergleicht.
 */
interface Geraeteschluessel {

    /** Das gültige Paar, oder `null`: Dieses Handy hat keines und sendet Fassung 1. */
    fun liefern(): Geraetepaar?

    /** Der öffentliche Teil des gültigen Paars. */
    fun spk(): ByteArray? = liefern()?.spk

    /**
     * Schritt 1, vor `start`: ein neues Paar und ein neues `N_g` erzeugen und
     * als Versuch ablegen. Ein älterer Versuch ist damit verworfen; das
     * gültige Paar bleibt.
     */
    fun beginnen(): Kopplungsbeginn

    /** Läuft ein Versuch (etwa nach einem Prozesstod)? Dann sein Block für `start`, sonst `null`. */
    fun schwebend(): Kopplungsbeginn?

    /**
     * Schritt 3, bei `status` im Zustand `beansprucht`: sich auf
     * Kontoschlüssel und `N_b` festlegen und dafür `N_g` und den Wert
     * bekommen. **Die erste Antwort gilt.** Derselbe Aufruf mit denselben
     * Werten ergibt wieder dasselbe (die Abfrage wiederholt sich, und
     * `offenlegen` ist wiederholbar); mit anderen ist es ein Abbruch.
     */
    fun festlegen(kontoschluessel: ByteArray, nB: ByteArray): Festlegung

    /**
     * Die Kopplung ist abgeschlossen (`gekoppelt`): Aus dem Versuch wird das
     * gültige Paar, das bisherige ist ersetzt. `false`, wenn kein
     * festgelegter Versuch da ist — ein Paar, dessen Wert nie auf dem
     * Bildschirm stand, wird nicht gültig.
     */
    fun uebernehmen(): Boolean

    /** Den Versuch fallen lassen (Nein, im Web abgelehnt, Frist abgelaufen). Das gültige Paar bleibt. */
    fun verwerfen()

    /**
     * Alles vergessen — beim Trennen. Der Zähler der Einsatzhüllen gehört
     * NICHT dazu; er liegt woanders und bleibt
     * ([org.genem.nadoku.handy.senden.Huellenzaehler]).
     */
    fun vergessen()
}

/**
 * Die Regeln der Ablage, ohne zu wissen, wo die Bytes liegen.
 *
 * Darunter gibt es zwei Umsetzungen: [TresorGeraeteschluessel] für das Gerät
 * und eine im Speicher für die Prüffälle. Die Trennung hat denselben Grund
 * wie bei [Tresorschluessel]: Der `AndroidKeyStore` fehlt im Prüfstand, und
 * ohne sie wären die Regeln — erste Antwort gilt, Versuch neben gültigem
 * Paar — ungeprüft.
 */
abstract class AbgelegterGeraeteschluessel : Geraeteschluessel {

    /** Der abgelegte Klartext, oder `null`: nichts da oder unlesbar. */
    protected abstract fun laden(): ByteArray?

    /** Legt den Klartext dauerhaft ab; `null` löscht. */
    protected abstract fun ablegen(klartext: ByteArray?)

    private class Versuch(val paar: Geraetepaar, val nG: ByteArray, val konto: ByteArray?, val nB: ByteArray?)
    private class Inhalt(val gueltig: Geraetepaar?, val versuch: Versuch?)

    private fun b64(b: ByteArray): String = Base64.getEncoder().encodeToString(b)
    private fun aus64(s: String): ByteArray = Base64.getDecoder().decode(s)

    private fun paarAus(s: String): Geraetepaar? {
        if (s.isEmpty()) return null
        val b = aus64(s)
        return if (b.size == 97) Geraetepaar(b.copyOfRange(0, 32), b.copyOfRange(32, 97)) else null
    }

    private fun lesen(): Inhalt {
        val roh = laden() ?: return Inhalt(null, null)
        return try {
            val o = JSONObject(String(roh, Charsets.UTF_8))
            val v = o.optJSONObject(FELD_VERSUCH)?.let { w ->
                val paar = paarAus(w.optString(FELD_PAAR))
                val nG = w.optString(FELD_NG).takeIf { it.isNotEmpty() }?.let(::aus64)
                if (paar == null || nG == null || nG.size != Kopplungswert.NONCE_LAENGE) null
                else Versuch(
                    paar, nG,
                    w.optString(FELD_KONTO).takeIf { it.isNotEmpty() }?.let(::aus64),
                    w.optString(FELD_NB).takeIf { it.isNotEmpty() }?.let(::aus64),
                )
            }
            Inhalt(paarAus(o.optString(FELD_PAAR)), v)
        } catch (e: Exception) {
            /* UNLESBAR IST WIE NICHT VORHANDEN, wie im Schluesseltresor. Das
             * Handy sendet dann Fassung 1 oder koppelt neu; ein halbes Paar
             * zu benutzen wäre schlimmer als keines. Nicht protokolliert:
             * Der Text der Ausnahme könnte Schlüsselmaterial tragen. */
            Inhalt(null, null)
        }
    }

    private fun schreiben(i: Inhalt) {
        if (i.gueltig == null && i.versuch == null) {
            ablegen(null)
            return
        }
        val o = JSONObject()
        i.gueltig?.let { o.put(FELD_PAAR, b64(it.privat + it.spk)) }
        i.versuch?.let { v ->
            o.put(
                FELD_VERSUCH,
                JSONObject().put(FELD_PAAR, b64(v.paar.privat + v.paar.spk)).put(FELD_NG, b64(v.nG)).apply {
                    v.konto?.let { put(FELD_KONTO, b64(it)) }
                    v.nB?.let { put(FELD_NB, b64(it)) }
                },
            )
        }
        ablegen(o.toString().toByteArray(Charsets.UTF_8))
    }

    @Synchronized override fun liefern(): Geraetepaar? = lesen().gueltig

    @Synchronized override fun beginnen(): Kopplungsbeginn {
        val v = Versuch(Geraetepaar.erzeugen(), Kopplungswert.zufall(), null, null)
        schreiben(Inhalt(lesen().gueltig, v))
        return Kopplungsbeginn(v.paar.spk, Kopplungswert.zusage(v.nG, v.paar.spk))
    }

    @Synchronized override fun schwebend(): Kopplungsbeginn? =
        lesen().versuch?.let { Kopplungsbeginn(it.paar.spk, Kopplungswert.zusage(it.nG, it.paar.spk)) }

    @Synchronized override fun festlegen(kontoschluessel: ByteArray, nB: ByteArray): Festlegung {
        val i = lesen()
        val v = i.versuch ?: return Festlegung.Abbruch(Festlegung.Grund.KEIN_VERSUCH)
        val formGut = nB.size == Kopplungswert.NONCE_LAENGE &&
            runCatching { Geraetehuelle.oeffentlich(kontoschluessel) }.isSuccess
        if (!formGut) return Festlegung.Abbruch(Festlegung.Grund.FORM)
        if (v.konto != null && v.nB != null) {
            if (!v.konto.contentEquals(kontoschluessel) || !v.nB.contentEquals(nB)) {
                return Festlegung.Abbruch(Festlegung.Grund.ANDERES_PAAR)
            }
        } else {
            /* ERST ABLEGEN, DANN HERAUSGEBEN. Stürzte der Prozess zwischen
             * Herausgeben und Ablegen ab, wüsste der nächste Lauf nicht mehr,
             * worauf er sich festgelegt hat, und nähme ein zweites Paar an —
             * mit einem N_g, das der Server schon kennt. */
            schreiben(Inhalt(i.gueltig, Versuch(v.paar, v.nG, kontoschluessel.copyOf(), nB.copyOf())))
        }
        return Festlegung.Offen(v.nG.copyOf(), Kopplungswert.wert(kontoschluessel, v.paar.spk, v.nG, nB))
    }

    @Synchronized override fun uebernehmen(): Boolean {
        val v = lesen().versuch ?: return false
        if (v.konto == null || v.nB == null) return false
        schreiben(Inhalt(v.paar, null))
        return true
    }

    @Synchronized override fun verwerfen() {
        val i = lesen()
        if (i.versuch != null) schreiben(Inhalt(i.gueltig, null))
    }

    @Synchronized override fun vergessen() = ablegen(null)

    private companion object {
        /* Einbuchstabig wie im Schluesseltresor: Die Datei soll nichts erzählen. */
        const val FELD_PAAR = "g"
        const val FELD_VERSUCH = "v"
        const val FELD_NG = "n"
        const val FELD_KONTO = "k"
        const val FELD_NB = "b"
    }
}

/**
 * Das Gerätepaar in einer Datei, verschlüsselt mit dem Schlüssel des Tresors.
 *
 * WAS DAS HEISST, UND WAS NICHT (F-KA-85). Der private Teil `d` liegt als
 * Geheimtext im app-privaten Verzeichnis, unter demselben AES-Schlüssel aus
 * dem Android Keystore wie die Zugangsdaten ([Schluesseltresor], dieselben
 * drei Schichten). Wer das Dateisystem kopiert, hat ihn nicht. **Aber zum
 * Rechnen wird er entschlüsselt und steht dann im Speicher des Prozesses**
 * — auslesbar für jeden, der in diesen Prozess hineinkommt (Root, ein
 * Debugger, ein Speicherabbild), genau wie der API-Schlüssel daneben. Es ist
 * KEIN Hardware-Schutz: Der Keystore bewacht den Schlüssel der Datei, nicht
 * den Geräteschlüssel selbst.
 *
 * WARUM NICHT GLEICH IM KEYSTORE. Ein EC-Schlüssel, der den Keystore nie
 * verlässt und dort ECDH rechnet (`PURPOSE_AGREE_KEY`), geht erst ab
 * API 31; `minSdk` ist 26. Diese Ablage arbeitet auf jeder Stufe gleich —
 * ein Weg, ein Prüfstand, kein Verhalten, das vom Alter des Handys abhängt.
 *
 * WAS EIN AUSGELESENER SCHLÜSSEL KANN: für dieses Konto Hüllen im Namen
 * dieses Handys bauen, bis es im Web ausgetragen ist. Öffnen kann er nichts
 * (Bedrohungsmodell, Lage H).
 *
 * EINE EIGENE DATEI neben `tresor.bin`, weil die beiden verschieden leben:
 * Die Zugangsdaten kennen „schwebend" und „gültig" als EINEN Satz; hier
 * liegt ein Versuch NEBEN einem gültigen Paar, und ein abgebrochener
 * Versuch darf das gültige nicht anfassen.
 */
class TresorGeraeteschluessel(
    private val datei: File,
    private val schluessel: Tresorschluessel,
) : AbgelegterGeraeteschluessel() {

    override fun laden(): ByteArray? = try {
        if (datei.exists()) schluessel.entschluesseln(datei.readBytes()) else null
    } catch (e: Exception) {
        null
    }

    override fun ablegen(klartext: ByteArray?) {
        val neben = File(datei.parentFile, datei.name + ".neu")
        if (klartext == null) {
            datei.delete()
            neben.delete()
            return
        }
        datei.parentFile?.mkdirs()
        // Erst daneben schreiben, dann umbenennen — wie Schluesseltresor.schreiben().
        neben.writeBytes(schluessel.verschluesseln(klartext))
        if (!neben.renameTo(datei)) {
            datei.writeBytes(neben.readBytes())
            neben.delete()
        }
    }

    companion object {
        const val DATEINAME = "geraet.bin"
    }
}
