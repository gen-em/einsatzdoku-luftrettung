package org.genem.nadoku.handy.tresor

import org.genem.nadoku.handy.kopplung.Kopplungswert
import org.genem.nadoku.handy.senden.Geraetehuelle
import org.json.JSONObject
import java.io.File
import java.security.SecureRandom
import java.security.interfaces.ECPublicKey
import java.util.Base64

/**
 * Der Absender einer Hülle der Fassung 2: der statische P-256-Schlüssel
 * dieses Handys, soweit die App ihn zu sehen bekommt (KA-06c, seit KA-06f
 * ohne privaten Teil).
 *
 * WOFÜR. Mit ihm verschließt das Handy Hüllen der Fassung 2
 * ([Geraetehuelle.verschliessen2]); der Browser des Kontos erkennt daran,
 * dass eine Hülle von dem Gerät kommt, das bei der Kopplung über den
 * Vergleichswert bestätigt wurde. Öffnen kann man mit ihm nichts.
 *
 * WAS DIESE SCHNITTSTELLE NICHT HERGIBT: den privaten Teil. Sie nennt den
 * öffentlichen (`spk`, SEC1 unkomprimiert, 65 Byte) und RECHNET mit dem
 * privaten — die Schlüsselvereinbarung `Z_s`, und nur die. Auf dem Gerät
 * steht dahinter ein Eintrag im Android Keystore
 * ([KeystoreSchluesselablage]); die Rechnung geschieht dort, und die App
 * bekommt das Ergebnis.
 *
 * `Z_s` WIRD NICHT AUFBEWAHRT — weder auf der Platte noch in einem Feld
 * über den Aufruf hinaus (E-KA-71). Wer `Z_s` zu einem Kontoschlüssel hat,
 * baut für dieses Konto Hüllen im Namen des Handys, so gut wie mit dem
 * privaten Teil selbst; ein abgelegtes `Z_s` nähme dem Keystore also genau
 * das, wofür er da ist. Deshalb je Hülle eine Vereinbarung.
 */
interface Absender {

    /** Der öffentliche Teil, SEC1 unkomprimiert (65 Byte). */
    val spk: ByteArray

    /** Die Kennung, unter der dieses Handy in Hülle und Gerätebuch steht. */
    fun sid(): ByteArray = Geraetehuelle.sid(spk)

    /**
     * `Z_s` = ECDH(statisch privat, Konto öffentlich), 32 Byte big-endian.
     *
     * @throws Schluesselausfall wenn die Schlüsselablage die Rechnung nicht
     *   hergibt — dann geht in diesem Lauf nichts hinaus, und der nächste
     *   versucht es wieder
     */
    fun vereinbaren(konto: ECPublicKey): ByteArray
}

/**
 * Die Schlüsselablage hat eine Rechnung verweigert.
 *
 * EIN EIGENER NAME, damit der Sender genau das abfangen kann und nichts
 * sonst: Der Keystore ist ein anderer Prozess des Systems und kann
 * ausfallen, wo eine Rechnung im eigenen Speicher es nicht konnte. Ein
 * solcher Ausfall darf den Sendefaden nicht beenden — an dem Prozess hängt
 * die Aufzeichnung. Die Ursache steht NICHT im Text: Er ginge in ein
 * Protokoll.
 */
class Schluesselausfall(ursache: Throwable) : RuntimeException("Schlüsselablage verweigert die Rechnung", ursache)

/**
 * Wo die Schlüssel selbst liegen — getrennt von den Regeln der Kopplung
 * ([AbgelegterGeraeteschluessel]).
 *
 * Auf dem Gerät ist das der Android Keystore ([KeystoreSchluesselablage]);
 * im Prüfstand, der keinen hat, eine Ablage im Speicher mit gewöhnlichen
 * Schlüsseln (nur unter `src/test`). Die Trennung hat denselben Grund wie
 * bei [Tresorschluessel]: Ohne sie wären die Regeln ungeprüft.
 *
 * NAMEN STATT FÄCHER. Der Keystore kann einen Eintrag nicht umbenennen. Es
 * gibt deshalb kein Fach „gültig" und kein Fach „Versuch", sondern je Paar
 * einen eigenen Namen, der nie wiederverwendet wird; WELCHER Name gerade
 * gilt, steht in der Verwaltungsdatei und nur dort.
 */
interface Schluesselablage {

    /** Ein neues Paar unter `name` erzeugen; ergibt den öffentlichen Teil (SEC1, 65 Byte). */
    fun erzeugen(name: String): ByteArray

    /** Der Absender zu `name`, oder `null`: nicht vorhanden oder nicht benutzbar. */
    fun absender(name: String): Absender?

    /** Den Eintrag löschen; ein fehlender ist kein Fehler. */
    fun loeschen(name: String)

    /** Alle Namen, die diese Ablage hält — auch solche, von denen die Verwaltungsdatei nichts weiß. */
    fun namen(): Set<String>
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
 * Der Geräteschlüssel und die laufende Kopplung.
 *
 * ZWEI FÄCHER. Das **gültige** Paar gehört zur fertigen Kopplung und
 * verschließt, was gesendet wird. Der **Versuch** entsteht bei `start` und
 * trägt ein NEUES Paar, `N_g` und — sobald der Server sie nennt —
 * Kontoschlüssel und `N_b`. Erst [uebernehmen] macht aus dem Versuch das
 * gültige Paar. Eine abgebrochene Kopplung lässt das bisherige also stehen,
 * wie auf der Garmin (`Huelle2.kopplungBeginnen`).
 *
 * DER VERSUCH ÜBERDAUERT DEN PROZESS, nicht nur der Schlüssel — anders als
 * auf der Garmin, und aus demselben Grund wie die schwebenden Zugangsdaten im
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

    /**
     * Der Absender zum gültigen Paar, oder `null`: Dieses Handy hat keines.
     * Dann sendet es gar nicht (seit KA-15, E-KA-80, wo der Server
     * `huelle: 2` gesagt hat; seit 0.27.0 ohne Ausnahme, KA-21; [Herkunft]).
     */
    fun liefern(): Absender?

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
 * Die Regeln der Kopplung über zwei Ablagen: die **Schlüssel** in einer
 * [Schluesselablage], die **Verwaltung** — welcher Name gilt, welcher der
 * Versuch ist, `N_g`, Kontoschlüssel, `N_b` — als ein Stück Klartext, das
 * die Unterklasse ablegt ([laden], [ablegen]).
 *
 * WARUM ZWEI. Was ein Schlüssel ist, soll die App nie als Bytes halten
 * (E-KA-71); was keiner ist, kann der Keystore nicht aufnehmen. `N_g` ist
 * bis zum Offenlegen geheim und muss einen Prozesstod überdauern — es liegt
 * deshalb in der Verwaltung, und die liegt auf dem Gerät verschlüsselt
 * ([DateiGeraeteschluessel]).
 *
 * DIE VERWALTUNG ENTSCHEIDET, NICHT DER KEYSTORE. Gültig ist, was in der
 * Verwaltung als gültig steht; ein Eintrag der Schlüsselablage, den sie
 * nicht nennt, ist ein Waise und wird nie benutzt. Jeder Übergang ist damit
 * EIN Schreiben der Verwaltung, und das ist entweder geschehen oder nicht:
 *
 * | Schritt | Reihenfolge | stirbt der Prozess dazwischen |
 * |---|---|---|
 * | [beginnen] | Schlüssel erzeugen · Verwaltung schreiben · Waisen löschen | vor dem Schreiben: ein Waise, sonst alles wie zuvor · danach: der Versuch steht, Waisen bleiben bis zum nächsten Mal |
 * | [festlegen] | Verwaltung schreiben · `N_g` herausgeben | vor dem Schreiben: nichts festgelegt, nichts herausgegeben |
 * | [uebernehmen] | Verwaltung schreiben · bisherigen Schlüssel löschen | vor dem Schreiben: das bisherige Paar gilt, der Versuch steht festgelegt und lässt sich wieder übernehmen · danach: das neue gilt, das bisherige ist ein Waise |
 * | [verwerfen] | Verwaltung schreiben · Schlüssel des Versuchs löschen | danach: ein Waise |
 * | [vergessen] | Verwaltung löschen · alle Schlüssel löschen | danach: Waisen |
 *
 * Nie gilt dabei ein Paar, das nicht festgelegt war, und nie gilt keines,
 * wo vorher eines galt. WAISEN räumt das nächste [beginnen] und jedes
 * [vergessen] ab — nicht der Start der App: Wäre die Verwaltung einmal nur
 * vorübergehend unlesbar, löschte ein Aufräumen beim Start den Schlüssel,
 * den sie nach dem nächsten Start wieder nennte, und ein gelöschter
 * Keystore-Eintrag kommt nicht zurück.
 *
 * DER ÖFFENTLICHE TEIL STEHT ZWEIMAL: in der Verwaltung und am Eintrag der
 * Schlüsselablage. Über den der Verwaltung ist der Vergleichswert gerechnet;
 * [liefern] und [uebernehmen] halten beide gegeneinander und geben nichts
 * her, wenn sie sich unterscheiden.
 */
abstract class AbgelegterGeraeteschluessel(
    private val schluessel: Schluesselablage,
) : Geraeteschluessel {

    /** Die abgelegte Verwaltung im Klartext, oder `null`: nichts da oder unlesbar. */
    protected abstract fun laden(): ByteArray?

    /** Legt die Verwaltung dauerhaft ab — ganz oder gar nicht; `null` löscht. */
    protected abstract fun ablegen(klartext: ByteArray?)

    private class Eintrag(val name: String, val spk: ByteArray)
    private class Versuch(val eintrag: Eintrag, val nG: ByteArray, val konto: ByteArray?, val nB: ByteArray?)
    private class Inhalt(val gueltig: Eintrag?, val versuch: Versuch?)

    private fun b64(b: ByteArray): String = Base64.getEncoder().encodeToString(b)
    private fun aus64(s: String): ByteArray = Base64.getDecoder().decode(s)

    private fun eintragAus(o: JSONObject?): Eintrag? {
        if (o == null) return null
        val name = o.optString(FELD_NAME)
        if (!NAME_FORM.matches(name)) return null
        val spk = aus64(o.optString(FELD_SPK))
        Geraetehuelle.oeffentlich(spk)          // wirft, wenn es kein Punkt der Kurve ist
        return Eintrag(name, spk)
    }

    private fun eintragZu(e: Eintrag): JSONObject = JSONObject().put(FELD_NAME, e.name).put(FELD_SPK, b64(e.spk))

    private fun lesen(): Inhalt {
        val roh = laden() ?: return Inhalt(null, null)
        return try {
            val o = JSONObject(String(roh, Charsets.UTF_8))
            val v = o.optJSONObject(FELD_VERSUCH)?.let { w ->
                val e = eintragAus(w)
                val nG = w.optString(FELD_NG).takeIf { it.isNotEmpty() }?.let(::aus64)
                if (e == null || nG == null || nG.size != Kopplungswert.NONCE_LAENGE) null
                else Versuch(
                    e, nG,
                    w.optString(FELD_KONTO).takeIf { it.isNotEmpty() }?.let(::aus64),
                    w.optString(FELD_NB).takeIf { it.isNotEmpty() }?.let(::aus64),
                )
            }
            Inhalt(eintragAus(o.optJSONObject(FELD_GUELTIG)), v)
        } catch (e: Exception) {
            /* UNLESBAR IST WIE NICHT VORHANDEN, wie im Schluesseltresor. Das
             * Handy sendet dann nichts, bis es neu koppelt ([Herkunft]);
             * eine halbe
             * Verwaltung zu benutzen wäre schlimmer als keine. Nicht
             * protokolliert: Der Text der Ausnahme könnte `N_g` tragen. */
            Inhalt(null, null)
        }
    }

    private fun schreiben(i: Inhalt) {
        if (i.gueltig == null && i.versuch == null) {
            ablegen(null)
            return
        }
        val o = JSONObject()
        i.gueltig?.let { o.put(FELD_GUELTIG, eintragZu(it)) }
        i.versuch?.let { v ->
            o.put(
                FELD_VERSUCH,
                eintragZu(v.eintrag).put(FELD_NG, b64(v.nG)).apply {
                    v.konto?.let { put(FELD_KONTO, b64(it)) }
                    v.nB?.let { put(FELD_NB, b64(it)) }
                },
            )
        }
        ablegen(o.toString().toByteArray(Charsets.UTF_8))
    }

    /**
     * Der Griff auf den Schlüssel eines Eintrags — nur, wenn die
     * Schlüsselablage unter dem Namen DENSELBEN öffentlichen Teil führt wie
     * die Verwaltung.
     */
    private fun griff(e: Eintrag): Absender? =
        schluessel.absender(e.name)?.takeIf { it.spk.contentEquals(e.spk) }

    /**
     * Löschen, was nach einem Übergang niemand mehr nennt. EIN FEHLSCHLAG
     * HÄLT NICHTS AUF: Der Übergang steht schon in der Verwaltung, und ein
     * Waise mehr wird beim nächsten Mal abgeräumt. (Nur `Exception` — ein
     * `Error` geht durch, auch der gespielte Prozesstod der Prüffälle.)
     */
    private fun still(tun: () -> Unit) {
        try {
            tun()
        } catch (e: Exception) {
            // bewusst leer, siehe oben
        }
    }

    private fun waisenLoeschen(i: Inhalt) = still {
        for (n in schluessel.namen()) {
            if (n != i.gueltig?.name && n != i.versuch?.eintrag?.name) schluessel.loeschen(n)
        }
    }

    private fun neuerName(): String =
        ByteArray(NAME_BYTES).also { SecureRandom().nextBytes(it) }.joinToString("") { "%02x".format(it) }

    @Synchronized override fun liefern(): Absender? = lesen().gueltig?.let(::griff)

    @Synchronized override fun beginnen(): Kopplungsbeginn {
        val bisher = lesen()
        /* ERST DER SCHLÜSSEL, DANN DIE VERWALTUNG. Andersherum nennte die
         * Verwaltung nach einem Prozesstod einen Versuch, zu dem es keinen
         * Schlüssel gibt. So herum bleibt schlimmstenfalls ein Waise. */
        val name = neuerName()
        val spk = schluessel.erzeugen(name)
        val v = Versuch(Eintrag(name, spk), Kopplungswert.zufall(), null, null)
        val neu = Inhalt(bisher.gueltig, v)
        schreiben(neu)
        waisenLoeschen(neu)         // der ältere Versuch und was frühere Abbrüche hinterließen
        return Kopplungsbeginn(spk.copyOf(), Kopplungswert.zusage(v.nG, spk))
    }

    @Synchronized override fun schwebend(): Kopplungsbeginn? = lesen().versuch?.let {
        Kopplungsbeginn(it.eintrag.spk.copyOf(), Kopplungswert.zusage(it.nG, it.eintrag.spk))
    }

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
            schreiben(Inhalt(i.gueltig, Versuch(v.eintrag, v.nG, kontoschluessel.copyOf(), nB.copyOf())))
        }
        return Festlegung.Offen(v.nG.copyOf(), Kopplungswert.wert(kontoschluessel, v.eintrag.spk, v.nG, nB))
    }

    @Synchronized override fun uebernehmen(): Boolean {
        val i = lesen()
        val v = i.versuch ?: return false
        if (v.konto == null || v.nB == null) return false
        /* IST DER SCHLÜSSEL NOCH DA, und ist es der, über den der Wert
         * gerechnet wurde? Sonst gälte danach ein Name, unter dem nichts
         * rechnet — und das bisherige Paar wäre dafür aufgegeben. */
        if (griff(v.eintrag) == null) return false
        /* DER EINE SCHRITT. Vor diesem Schreiben gilt das bisherige Paar,
         * danach das neue; dazwischen gibt es nichts. Das Löschen darunter
         * ist Aufräumen. */
        schreiben(Inhalt(v.eintrag, null))
        i.gueltig?.let { alt -> still { schluessel.loeschen(alt.name) } }
        return true
    }

    @Synchronized override fun verwerfen() {
        val i = lesen()
        val v = i.versuch ?: return
        schreiben(Inhalt(i.gueltig, null))
        still { schluessel.loeschen(v.eintrag.name) }
    }

    @Synchronized override fun vergessen() {
        /* ERST DIE VERWALTUNG: Mit ihr ist `N_g` fort und kein Name mehr
         * gültig. Dann JEDER Eintrag der Schlüsselablage, nicht nur die
         * beiden genannten — auch die Waisen früherer Abbrüche. */
        ablegen(null)
        still { for (n in schluessel.namen()) schluessel.loeschen(n) }
    }

    private companion object {
        /* Einbuchstabig wie im Schluesseltresor: Die Datei soll nichts erzählen. */
        const val FELD_GUELTIG = "g"
        const val FELD_VERSUCH = "v"
        const val FELD_NAME = "a"
        const val FELD_SPK = "s"
        const val FELD_NG = "n"
        const val FELD_KONTO = "k"
        const val FELD_NB = "b"

        /** 8 Byte Zufall als 16 Hexzeichen: je Paar ein Name, der nie wiederkehrt. */
        const val NAME_BYTES = 8
        val NAME_FORM = Regex("[0-9a-f]{16}")
    }
}

/**
 * Der Geräteschlüssel auf dem Gerät: die Schlüssel im Android Keystore, die
 * Verwaltung in einer Datei, verschlüsselt mit dem Schlüssel des Tresors.
 *
 * WAS DER KEYSTORE JETZT SCHÜTZT (F-KA-85, E-KA-71, -72; seit KA-06f). Der
 * private Teil des Geräteschlüssels entsteht im Keystore, ist nicht
 * exportierbar und rechnet dort (`PURPOSE_AGREE_KEY`). Er liegt in keiner
 * Datei der App und zu keiner Zeit im Speicher ihres Prozesses. Wer das
 * Dateisystem kopiert, wer ein Speicherabbild zieht, wer mit einem Debugger
 * in den Prozess kommt, findet ihn nicht — bis KA-06f lag er als Geheimtext
 * in `geraet.bin` und zum Rechnen entschlüsselt im Speicher.
 *
 * WAS ER NICHT SCHÜTZT, und das gehört in denselben Absatz:
 *  - **Benutzen ist nicht Besitzen, aber es reicht für den Augenblick.** Wer
 *    in den Prozess der App hineinkommt oder als sie handelt, kann beim
 *    Keystore Vereinbarungen BESTELLEN und damit Hüllen im Namen dieses
 *    Handys bauen — solange er dort ist. Mitnehmen kann er den Schlüssel
 *    nicht: Mit dem Zugriff endet auch das Bestellen.
 *  - **`Z_s` selbst steht für die Dauer einer Rechnung im Speicher.** Es
 *    wird je Hülle neu vereinbart und nicht aufbewahrt ([Absender]); wer es
 *    in diesem Augenblick abgreift, baut für DIESES Konto Hüllen, bis das
 *    Handy im Web ausgetragen ist.
 *  - **Keine Entsperrpflicht.** Der Schlüssel rechnet auch bei gesperrtem
 *    Bildschirm, mit Absicht (wie [KeystoreTresorschluessel]): Gesendet wird
 *    im Hintergrund, im Einsatz, aus der Tasche.
 *  - **Wie hart der Keystore ist, bestimmt das Gerät.** StrongBox wird nicht
 *    verlangt; ob der Eintrag in der gesicherten Umgebung des Geräts liegt
 *    oder vom System in Software gehalten wird, sagt `KeyInfo`, und der
 *    Gerätetest berichtet es, ohne es zu verlangen.
 *  - Öffnen kann der Schlüssel nichts, wie zuvor (Bedrohungsmodell, Lage H).
 *
 * NUR EIN WEG (E-KA-72). `minSdk` ist 31, und erst ab dort rechnet der
 * Keystore ECDH. Es gibt keinen Rückfall auf eine Datei: Ein Handy, dessen
 * Keystore das Paar nicht erzeugt, koppelt nicht.
 *
 * DIE VERWALTUNGSDATEI trägt keinen Schlüssel, aber `N_g` — geheim bis zum
 * Offenlegen — und die Namen, die gelten. Sie liegt deshalb unter demselben
 * AES-Schlüssel aus dem Keystore wie die Zugangsdaten ([Schluesseltresor]),
 * und GCM hält sie unverfälscht: Wer sie umschriebe, um einen anderen
 * Eintrag gültig zu machen, bräuchte den Schlüssel der Datei.
 *
 * EIN NEUER DATEINAME, nicht `geraet.bin`: Unter dem alten lag bis Android
 * 0.24.x der private Teil selbst. Was so heißt, wird beim Start gelöscht
 * ([AlteGeraeteablage]) — und das soll nie eine Datei treffen, die gilt.
 */
class DateiGeraeteschluessel(
    private val datei: File,
    private val dateischluessel: Tresorschluessel,
    schluesselablage: Schluesselablage,
) : AbgelegterGeraeteschluessel(schluesselablage) {

    override fun laden(): ByteArray? = try {
        if (datei.exists()) dateischluessel.entschluesseln(datei.readBytes()) else null
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
        neben.writeBytes(dateischluessel.verschluesseln(klartext))
        if (!neben.renameTo(datei)) {
            datei.writeBytes(neben.readBytes())
            neben.delete()
        }
    }

    companion object {
        const val DATEINAME = "absender.bin"
    }
}

/**
 * Die Ablage bis Android 0.24.x: `geraet.bin`, darin der private Teil des
 * Geräteschlüssels als Geheimtext (KA-06c, F-KA-85).
 *
 * SIE WIRD GELÖSCHT, NICHT ÜBERNOMMEN. Ein Schlüssel, der schon einmal als
 * Bytes in einer Datei und im Speicher lag, wird nicht dadurch besser, dass
 * man ihn in den Keystore legt — und der Keystore nähme ihn als
 * importierten Schlüssel, nicht als einen, der ihn nie verlassen hat. Das
 * Handy koppelt neu und bekommt dabei ein Paar, das im Keystore entstanden
 * ist. Die Datei gab es nur auf Prüfgeräten (seit 0.24.0).
 */
object AlteGeraeteablage {

    const val DATEINAME = "geraet.bin"

    /** Löscht die alte Datei und ihre Nachbarin `.neu`. `true`, wenn danach keine von beiden mehr da ist. */
    fun raeumen(verzeichnis: File): Boolean {
        val alt = File(verzeichnis, DATEINAME)
        val neben = File(verzeichnis, "$DATEINAME.neu")
        alt.delete()
        neben.delete()
        return !alt.exists() && !neben.exists()
    }
}
