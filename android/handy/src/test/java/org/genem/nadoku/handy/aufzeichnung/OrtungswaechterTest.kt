package org.genem.nadoku.handy.aufzeichnung

import org.genem.nadoku.gemeinsam.Ortungscode
import org.junit.Assert.assertEquals
import org.junit.Test

/**
 * Der Ortungswächter (E-S5Z-06) — **die Entscheidungen, nicht die Syntax**.
 *
 * WARUM DIESE FÄLLE DAS GEWICHT TRAGEN. Was Paket E baut, ist am Gerät zu
 * sehen und sonst nirgends: Es gibt in diesem Container keinen Emulator
 * (arm64 übersetzt der heutige QEMU2 nicht, x86_64 braucht KVM und
 * `/dev/kvm` fehlt), also kein `adb emu geo fix`, kein Abschalten des
 * Standorts, keine Vibration. Übrig bleibt der Gerätetest — und diese Fälle.
 * Sie prüfen deshalb die **Regel**: Zeit wird eingespeist, nicht gewartet,
 * und jede Frist wird auf beiden Seiten ihrer Grenze belegt.
 */
class OrtungswaechterTest {

    /** Eine bequeme Sekundenrechnung — die Fristen stehen in Millisekunden. */
    private fun s(sekunden: Long) = sekunden * 1000L

    private fun waechter(
        freigegeben: Boolean = true,
        anbieterAn: Boolean = true,
        startMs: Long = 0,
    ) = Ortungswaechter(startMs, freigegeben, anbieterAn)

    // ---- Start -------------------------------------------------------------

    @Test
    fun start_ohne_anbieter_meldet_standort_aus_und_warnt() {
        val w = waechter(anbieterAn = false)
        assertEquals(Ortungsstand.STANDORT_AUS, w.stand)
        assertEquals(Warnbefehl.POSTEN, w.anfangsbefehl(0))
    }

    @Test
    fun start_ohne_freigabe_meldet_freigabe_fehlt_und_warnt() {
        val w = waechter(freigegeben = false, anbieterAn = false)
        assertEquals(Ortungsstand.FREIGABE_FEHLT, w.stand)
        assertEquals(Warnbefehl.POSTEN, w.anfangsbefehl(0))
    }

    /** Die Freigabe hat Vorfahrt: Ohne sie ist der Anbieter gleichgültig. */
    @Test
    fun ohne_freigabe_bleibt_es_dabei_auch_wenn_der_anbieter_laeuft() {
        val w = waechter(freigegeben = false, anbieterAn = true)
        assertEquals(Ortungsstand.FREIGABE_FEHLT, w.stand)
    }

    @Test
    fun start_mit_anbieter_sucht_und_warnt_nicht() {
        val w = waechter()
        assertEquals(Ortungsstand.SUCHT, w.stand)
        assertEquals(Warnbefehl.NICHTS, w.anfangsbefehl(0))
    }

    // ---- Erstfix-Frist (Z-S5Z-01, 120 s) -----------------------------------

    @Test
    fun nach_119_s_ohne_fund_sucht_er_noch() {
        val w = waechter()
        assertEquals(Warnbefehl.NICHTS, w.tick(s(119)))
        assertEquals(Ortungsstand.SUCHT, w.stand)
    }

    @Test
    fun nach_120_s_ohne_fund_ist_kein_signal_und_es_warnt() {
        val w = waechter()
        assertEquals(Warnbefehl.POSTEN, w.tick(s(120)))
        assertEquals(Ortungsstand.KEIN_SIGNAL, w.stand)
    }

    // ---- Brauchbare Funde --------------------------------------------------

    @Test
    fun brauchbarer_fund_macht_ok_und_zieht_die_warnung_zurueck() {
        val w = waechter()
        assertEquals(Warnbefehl.POSTEN, w.tick(s(120)))
        assertEquals(Warnbefehl.LOESCHEN, w.brauchbarerFund(s(121)))
        assertEquals(Ortungsstand.OK, w.stand)
    }

    @Test
    fun ein_fund_je_sekunde_haelt_ok_ueber_die_frist_hinaus() {
        val w = waechter()
        w.brauchbarerFund(s(10))
        for (t in 11..200) assertEquals(Warnbefehl.NICHTS, w.brauchbarerFund(s(t.toLong())))
        assertEquals(Ortungsstand.OK, w.stand)
    }

    // ---- Betriebsfrist (Z-S5Z-02, 60 s) ------------------------------------

    @Test
    fun aus_ok_heraus_macht_60_s_stille_kein_signal() {
        val w = waechter()
        w.brauchbarerFund(s(10))
        assertEquals(Warnbefehl.NICHTS, w.tick(s(70)))
        assertEquals(Ortungsstand.OK, w.stand)
        assertEquals(Warnbefehl.POSTEN, w.tick(s(71)))
        assertEquals(Ortungsstand.KEIN_SIGNAL, w.stand)
    }

    /**
     * Der Fall, für den `brauchbar()` überhaupt aus der Ausdünnung
     * herausgezogen wurde: Der Empfänger arbeitet, aber jeder Fund streut zu
     * weit — im Puffer landet trotzdem nichts.
     */
    @Test
    fun rohe_funde_ohne_brauchbare_machen_nach_60_s_ungenau() {
        val w = waechter()
        for (t in 1..59) w.roherFund(s(t.toLong()))
        assertEquals(Ortungsstand.SUCHT, w.stand)
        assertEquals(Warnbefehl.POSTEN, w.roherFund(s(60)))
        assertEquals(Ortungsstand.UNGENAU, w.stand)
    }

    /**
     * Ein einzelner ungenauer Fund kurz nach dem Start ist noch kein
     * `UNGENAU` — sonst meldete jede Anfahrt in der ersten Minute eine
     * Warnung, obwohl der Empfänger sich gerade erst einfängt.
     */
    @Test
    fun der_erste_ungenaue_fund_meldet_noch_nicht_ungenau() {
        val w = waechter()
        assertEquals(Warnbefehl.NICHTS, w.roherFund(s(5)))
        assertEquals(Ortungsstand.SUCHT, w.stand)
    }

    @Test
    fun aus_ungenau_heraus_macht_stille_kein_signal() {
        val w = waechter()
        for (t in 1..60) w.roherFund(s(t.toLong()))
        assertEquals(Ortungsstand.UNGENAU, w.stand)
        w.tick(s(121))
        assertEquals(Ortungsstand.KEIN_SIGNAL, w.stand)
    }

    // ---- Anbieter an und aus -----------------------------------------------

    @Test
    fun anbieter_aus_wirkt_sofort_und_nicht_erst_nach_einer_frist() {
        val w = waechter()
        w.brauchbarerFund(s(10))
        assertEquals(Warnbefehl.POSTEN, w.anbieterAus(s(11)))
        assertEquals(Ortungsstand.STANDORT_AUS, w.stand)
    }

    @Test
    fun anbieter_wieder_an_setzt_die_erstfix_frist_neu() {
        val w = waechter()
        w.tick(s(200))
        assertEquals(Ortungsstand.KEIN_SIGNAL, w.stand)
        assertEquals(Warnbefehl.POSTEN, w.anbieterAus(s(200)))
        assertEquals(Warnbefehl.LOESCHEN, w.anbieterAn(s(300)))
        assertEquals(Ortungsstand.SUCHT, w.stand)

        // Ab hier laufen die 120 s von vorn, nicht seit dem Dienststart.
        w.tick(s(300 + 119))
        assertEquals(Ortungsstand.SUCHT, w.stand)
        w.tick(s(300 + 120))
        assertEquals(Ortungsstand.KEIN_SIGNAL, w.stand)
    }

    @Test
    fun die_freigabe_kann_waehrend_des_dienstes_dazukommen() {
        val w = waechter(freigegeben = false)
        assertEquals(Ortungsstand.FREIGABE_FEHLT, w.stand)
        assertEquals(Warnbefehl.POSTEN, w.anfangsbefehl(0))
        assertEquals(Warnbefehl.LOESCHEN, w.freigabe(erteilt = true, jetztMs = s(30)))
        assertEquals(Ortungsstand.SUCHT, w.stand)
    }

    // ---- Warnung und Erinnerung (Z-S5Z-04, 600 s) --------------------------

    @Test
    fun die_erinnerung_kommt_nach_600_s_und_nicht_nach_599() {
        val w = waechter(anbieterAn = false)
        assertEquals(Warnbefehl.POSTEN, w.anfangsbefehl(0))
        assertEquals(Warnbefehl.NICHTS, w.tick(s(599)))
        assertEquals(Warnbefehl.POSTEN, w.tick(s(600)))
        // Und danach wieder von vorn, nicht bei jedem Takt.
        assertEquals(Warnbefehl.NICHTS, w.tick(s(601)))
        assertEquals(Warnbefehl.POSTEN, w.tick(s(1200)))
    }

    /**
     * Zwischen zwei Warnzuständen wird sofort erneut gewarnt: Der Grund ist
     * ein anderer, und der Text der Meldung auch.
     */
    @Test
    fun der_wechsel_zwischen_zwei_warnzustaenden_warnt_sofort() {
        val w = waechter()
        assertEquals(Warnbefehl.POSTEN, w.tick(s(120)))
        assertEquals(Ortungsstand.KEIN_SIGNAL, w.stand)
        assertEquals(Warnbefehl.POSTEN, w.anbieterAus(s(121)))
        assertEquals(Ortungsstand.STANDORT_AUS, w.stand)
    }

    @Test
    fun ohne_gestellte_warnung_wird_auch_nichts_geloescht() {
        val w = waechter()
        assertEquals(Warnbefehl.NICHTS, w.brauchbarerFund(s(5)))
        assertEquals(Warnbefehl.NICHTS, w.tick(s(6)))
    }

    // ---- Was die Anzeige daraus macht --------------------------------------

    @Test
    fun genau_ein_zustand_zeichnet_auf() {
        assertEquals(
            listOf(Ortungsstand.OK),
            Ortungsstand.entries.filter { it.zeichnetAuf },
        )
    }

    @Test
    fun gewarnt_wird_vor_allem_ausser_ok_und_sucht() {
        assertEquals(
            listOf(
                Ortungsstand.FREIGABE_FEHLT,
                Ortungsstand.STANDORT_AUS,
                Ortungsstand.KEIN_SIGNAL,
                Ortungsstand.UNGENAU,
            ),
            Ortungsstand.entries.filter { it.warnt },
        )
    }

    // ---- Die Bruecke zur Uhr (E3) ------------------------------------------

    @Test
    fun jede_stufe_hat_genau_einen_code() {
        val codes = Ortungsstand.entries.map { it.code }
        assertEquals("sechs Stufen, sechs Codes", 6, codes.toSet().size)
        assertEquals(Ortungscode.OK, Ortungsstand.OK.code)
        assertEquals(Ortungscode.STANDORT_AUS, Ortungsstand.STANDORT_AUS.code)
    }

    /**
     * **Handy und Uhr sind sich einig, wann nichts aufgezeichnet wird.**
     *
     * Links entscheidet [Ortungsstand.warnt], ob das Handy vibriert; rechts
     * entscheidet `Ortungscode.OHNE_AUFZEICHNUNG`, ob die Uhr rot wird. Das
     * sind zwei Listen an zwei Orten, und sie müssen dieselbe sein — sonst
     * vibriert das Handy, während die Uhr schweigt, oder umgekehrt. Wer eine
     * siebte Stufe einführt, wird von dieser Zeile daran erinnert, beide zu
     * pflegen.
     */
    @Test
    fun was_das_handy_warnt_zeigt_die_uhr_als_keine_ortung() {
        assertEquals(
            Ortungsstand.entries.filter { it.warnt }.map { it.code }.toSet(),
            Ortungscode.OHNE_AUFZEICHNUNG,
        )
    }

    @Test
    fun genau_die_stufe_die_aufzeichnet_laesst_die_uhr_still() {
        val still = Ortungsstand.entries.filter {
            Ortungscode.anzeige(it.code) == Ortungscode.Anzeige.STILL
        }
        assertEquals(listOf(Ortungsstand.OK), still)
    }

    @Test
    fun seit_wann_der_zustand_gilt_wird_mitgefuehrt() {
        val w = waechter()
        w.tick(s(120))
        assertEquals(s(120), w.seitMs)
        w.brauchbarerFund(s(121))
        assertEquals(s(121), w.seitMs)
        assertEquals(s(121), w.letzterFundMs)
    }
}
