<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/speicher_lib.php';
require_once __DIR__ . '/wartung_lib.php';
require_once __DIR__ . '/geocoder_lib.php';
require_once __DIR__ . '/serverkrypto_lib.php';   // Karte „Schlüssel des Servers" (S10)

/**
 * BETRIEB → SERVEREINSTELLUNGEN (S8/AP2, E-S8-05, E-S8-18; Mockup 07 Fassung 2).
 *
 * WAS HIER STEHT UND WARUM. Die Speichergrenze und die Warnschwellen lagen
 * unter „Backups", zwischen der Erinnerung und der Aufbewahrung. Sie gehoeren
 * nicht dorthin: Sie wirken auf die Konto-Backups UND auf die Komplett-Staende
 * (B-S8-06), und die Komplett-Seite verwies bisher mit einem Satz auf sie. Das
 * war einer der Gruende fuer Nr. 79.
 *
 * Jetzt liegen Grenze, Schwellen und Belegung an EINER Stelle — und zwar im
 * Betrieb, wo die uebrigen Einstellungen der Installation stehen. Was je Konto
 * gilt (Erinnerung, Aufbewahrung, Admin-Mail), bleibt bei den Konto-Backups.
 *
 * DIE SEITE IST EINSPALTIG AUF LESEBREITE, auch am Schreibtisch: Sie traegt
 * zwei Karten, und ein zweispaltiges Raster fuer zwei Karten waere Raster um
 * des Rasters willen (E-S8-18 — Zweispaltigkeit gilt ab mehr als vier Karten).
 */

$pdo = db();
$notice = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'speicher') {
    csrf_check();
    $teile = [];

    /* ---- Speichergrenze (E-S2-15, zieht mit S8 hierher) ---------------- */
    $gb = str_replace(',', '.', trim((string)($_POST['grenze'] ?? '')));
    if (!is_numeric($gb) || (float)$gb <= 0 || (float)$gb > 10000) {
        $error = 'Bitte eine Speichergrenze zwischen 0,1 und 10000 GB angeben.';
    } elseif (abs((float)$gb * 1024 * 1024 * 1024 - edbak_grenze_bytes()) > 1) {
        edbak_marke_setzen('adminbackup_grenze_gb', (string)(float)$gb);
        /* DIE GEMELDETEN SCHWELLEN VERGESSEN. Eine neue Grenze macht aus
         * denselben Bytes einen anderen Prozentsatz — was bei der alten Grenze
         * gemeldet war, ist bei der neuen eine andere Aussage. Ohne dieses
         * Zuruecksetzen bliebe eine Warnung aus, die nach der Änderung fällig
         * wäre. */
        edbak_marke_setzen('adminbackup_schwellen_gemeldet', '');
        edbak_marke_setzen('adminbackup_schwellen_offen', '');
        $teile[] = 'Speichergrenze ' . $gb . ' GB';
    }

    /* ---- Warnschwellen ------------------------------------------------- */
    if ($error === null) {
        $roh = trim((string)($_POST['schwellen'] ?? ''));
        $neu = [];
        foreach (explode(',', $roh) as $t) {
            $t = trim($t);
            if ($t === '') { continue; }
            if (!ctype_digit($t) || (int)$t < 1 || (int)$t > 100) {
                $error = 'Warnschwellen sind ganze Zahlen zwischen 1 und 100, '
                       . 'durch Komma getrennt (z. B. „70, 90").';
                break;
            }
            $neu[(int)$t] = true;
        }
        if ($error === null) {
            $neu = array_keys($neu); sort($neu);
            if ($neu !== edbak_schwellen()) {
                edbak_marke_setzen('adminbackup_schwellen', implode(',', $neu));
                edbak_marke_setzen('adminbackup_schwellen_gemeldet', '');
                edbak_marke_setzen('adminbackup_schwellen_offen', '');
                $teile[] = $neu ? 'Warnschwellen ' . implode(' / ', $neu) . ' %'
                                : 'Warnschwellen aus';
            }
        }
    }

    /* ---- Webspace laut Hosting (neu, E-S8-18) --------------------------- */
    if ($error === null) {
        $roh = str_replace(',', '.', trim((string)($_POST['webspace'] ?? '')));
        if ($roh === '') {
            if (speicher_webspace_bytes() > 0) {
                speicher_webspace_setzen(0);
                $teile[] = 'Webspace-Angabe entfernt';
            }
        } elseif (!is_numeric($roh) || (float)$roh <= 0 || (float)$roh > 100000) {
            $error = 'Der Webspace ist eine Zahl in GB (oder leer, wenn die Angabe fehlt).';
        } elseif (abs((float)$roh * 1024 * 1024 * 1024 - speicher_webspace_bytes()) > 1) {
            speicher_webspace_setzen((float)$roh);
            $teile[] = 'Webspace ' . $roh . ' GB';
        }
    }

    /* ---- Kontingent der Datenbank (P5a/AP2, E-P5a-11) -------------------
     *
     * ANDERS ALS DER WEBSPACE HAT ES EINE VORGABE (10 GB): Das ist die
     * Untergrenze Z2, die diese Anwendung tragen muss — eine Zusage des
     * Projekts, keine Vermutung ueber den Hoster. Leer setzt sie zurueck.
     *
     * Es gehoert in DIESES Formular und nicht in ein eigenes: Es steht neben
     * dem Webspace, wird von denselben Schwellen gewarnt und hat dieselbe
     * Frage im Ruecken. */
    if ($error === null) {
        $roh = str_replace(',', '.', trim((string)($_POST['db_gb'] ?? '')));
        $istGb = speicher_db_kontingent_bytes() / (1024 * 1024 * 1024);
        if ($roh === '') {
            if (edbak_marke_lesen(SPEICHER_K_DB_GB) !== null
                && (string)edbak_marke_lesen(SPEICHER_K_DB_GB) !== '') {
                speicher_db_kontingent_setzen(0);
                $teile[] = 'DB-Kontingent auf die Vorgabe zurückgesetzt';
            }
        } elseif (!is_numeric($roh) || (float)$roh < 0.01 || (float)$roh > 100000) {
            $error = 'Das DB-Kontingent ist eine Zahl in GB, mindestens 0,01 (oder '
                   . 'leer für die Vorgabe ' . SPEICHER_DB_GB_VORGABE . ' GB).';
        } elseif (abs((float)$roh - $istGb) > 0.001) {
            speicher_db_kontingent_setzen((float)$roh);
            $teile[] = 'DB-Kontingent ' . $roh . ' GB';
        }
    }

    if ($error === null) {
        $notice = $teile ? implode(', ', $teile) . ' gespeichert.'
                         : 'Es gab nichts zu ändern.';
    }
}

/* ---- Adresssuche (S9/AP2, E-S9-05, R79; Backlog Nr. 137) ------------------
 *
 * EIGENE HANDLUNG, EIGENES FORMULAR. Sie hat mit dem Speicher nichts zu tun,
 * und ein gemeinsames „Speichern" ueber zwei Karten hinweg hiesse, dass ein
 * Tippfehler in der Speichergrenze die Dienstadresse mit abweist.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'geocoder') {
    csrf_check();
    $adresse = geocoder_adresse_pruefen((string)($_POST['dienst'] ?? ''));
    if ($adresse === null) {
        $error = 'Die Dienstadresse muss mit „https://" beginnen und einen '
               . 'Rechnernamen nennen — ohne Abfrageteil, höchstens 180 Zeichen '
               . '(z. B. „https://photon.komoot.io").';
    } else {
        $teile = geocoder_installation_setzen(!empty($_POST['adresssuche']), $adresse);
        $notice = $teile ? implode(', ', $teile) . ' gespeichert.'
                         : 'Es gab nichts zu ändern.';
    }
}

/* ---- Die Schlüssel des Servers (S10, E-S10-10 bis E-S10-12) -------------
 *
 * SECHS HANDGRIFFE, EIN FORMULARZWEIG. Sie stehen hier und nicht auf
 * „Backup-Ziele", weil sie die INSTALLATION betreffen und nicht die
 * Sicherung: Der Server-Anteil geht in den Datenschlüssel jedes Kontos ein,
 * mit Backups hat er nichts zu tun. Das Ordnungsprinzip ist R74/E-S8-12 —
 * was alle trifft, steht im Betrieb.
 *
 * JEDER GRIFF IST EINE EIGENE HANDLUNG mit eigenem `action`, so wie Speicher
 * und Adresssuche getrennte Formulare haben. Ein gemeinsames „Speichern" über
 * sechs Handlungen hinweg wäre die Stelle, an der ein Klick auf „Wechseln"
 * nebenbei etwas anderes tut.
 *
 * DIE ARBEIT STECKT IN `serverkrypto_lib.php`, nicht hier. Jeder Griff
 * besteht aus zwei Schritten, die zusammengehören — `config.php` schreiben
 * und die Marke nachziehen —, und diese Seite soll keine Krypto-Logik tragen.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && str_starts_with((string)($_POST['action'] ?? ''), 'schluessel_')) {
    csrf_check();
    $aktion   = (string)$_POST['action'];
    $ersetzen = !empty($_POST['ersetzen']);

    if ($aktion === 'schluessel_sk_anlegen') {
        [$ok, $was] = serverschluessel_eintragen();
        $notice = $ok ? 'Der Serverschlüssel steht jetzt in config.php (Kennung '
                      . schluessel_kennung($was) . '). Bitte das Schlüsselblatt '
                      . 'drucken — zwei Ausdrucke, zwei Orte.' : null;
        $error  = $ok ? null : $was;
        serverschluessel_zustand(true);

    } elseif ($aktion === 'schluessel_anteil_anlegen') {
        [$ok, $was] = anteil_anlegen();
        $notice = $ok ? 'Der Server-Anteil steht jetzt in config.php (Kennung '
                      . $was . '). Die Konten stellen beim nächsten Anmelden '
                      . 'still um. Bitte das Schlüsselblatt drucken — zwei '
                      . 'Ausdrucke, zwei Orte.' : null;
        $error  = $ok ? null : $was;

    } elseif ($aktion === 'schluessel_sk_nachtragen') {
        [$ok, $was] = serverschluessel_nachtragen(
            (string)($_POST['wert'] ?? ''), $ersetzen);
        $notice = $ok ? 'Der Serverschlüssel ist nachgetragen (Kennung ' . $was
                      . ') — er stimmt mit dem überein, mit dem versiegelt '
                      . 'wurde.' : null;
        $error  = $ok ? null : $was;
        serverschluessel_zustand(true);

    } elseif ($aktion === 'schluessel_anteil_nachtragen') {
        [$ok, $was] = anteil_nachtragen((string)($_POST['wert'] ?? ''), $ersetzen);
        $notice = $ok ? 'Der Server-Anteil ist nachgetragen (Kennung ' . $was
                      . ') — er stimmt mit dem überein, mit dem die Hüllen '
                      . 'gebaut wurden. Die Anmeldung geht wieder.' : null;
        $error  = $ok ? null : $was;

    } elseif ($aktion === 'schluessel_anteil_wechseln') {
        [$ok, $was] = anteil_wechseln();
        $notice = $ok ? 'Der Server-Anteil ist gewechselt (neue Kennung ' . $was
                      . '). Der bisherige bleibt als kdf_anteil_alt stehen, bis '
                      . 'kein Konto mehr auf ihm steht. Jetzt ein neues '
                      . 'Schlüsselblatt drucken.' : null;
        $error  = $ok ? null : $was;

    } elseif ($aktion === 'schluessel_anteil_alt_entfernen') {
        [$ok, $was] = anteil_alt_entfernen();
        $notice = $ok ? 'Der alte Server-Anteil ist aus config.php entfernt. '
                      . 'Die Rotation ist abgeschlossen.' : null;
        $error  = $ok ? null : $was;

    } elseif ($aktion === 'schluessel_anteil_neuanfang') {
        [$ok, $was] = anteil_neuanfang();
        $notice = $ok ? 'Ein neuer Server-Anteil ist eingetragen (Kennung ' . $was
                      . '). Alle Konten mit umgestellter Hülle müssen ihr '
                      . 'Passwort jetzt über den Wiederherstellungsschlüssel neu '
                      . 'setzen — die Daten selbst sind davon nicht betroffen. '
                      . 'Bitte das Schlüsselblatt neu drucken.' : null;
        $error  = $ok ? null : $was;
    }
}

$sp = speicher_uebersicht();
$skZustand  = serverschluessel_zustand();
$anZustand  = anteil_zustand();
$anZaehlung = anteil_zaehlung();

/**
 * Ein Balken mit Segmenten und Legende (Baustein `.speicher-balken`).
 *
 * DIE BREITE STEHT INLINE, DIE FARBE NICHT. Die Breite ist ein gerechneter
 * Wert und kann gar nicht anders als am Element stehen; die Farbe kommt aus
 * einer Klasse, damit kein Hexwert und kein Token in das Markup wandert
 * (`CLAUDE.md` 5).
 *
 * OHNE BEZUG KEINE ANTEILE. Fehlt die Webspace-Angabe, werden die Segmente
 * anteilig ZUEINANDER gezeichnet und die Legende nennt nur die Summe — der
 * Balken zeigt dann die Zusammensetzung, nicht die Fuellung. Alles andere
 * hiesse, eine Bezugsgroesse zu erfinden.
 *
 * @param list<array{klasse:string,bytes:int,text:string}> $teile
 */
function speicher_balken(array $teile, int $bezug, array $schwellen): string
{
    $summe = 0;
    foreach ($teile as $t) { $summe += (int)$t['bytes']; }
    $nenner = $bezug > 0 ? $bezug : max(1, $summe);
    $frei   = $bezug > 0 ? max(0, $bezug - $summe) : 0;

    $h = '<div class="speicher-balken">';
    foreach ($teile as $t) {
        $p = $nenner > 0 ? (float)$t['bytes'] * 100 / $nenner : 0.0;
        if ($p <= 0) { continue; }
        $h .= '<span class="' . ui_e($t['klasse']) . '" style="width:'
            . number_format(min(100, $p), 3, '.', '') . '%"></span>';
    }
    /* Der Schwellenstrich sitzt als leeres Segment an seiner Stelle — ohne
     * absolute Positionierung und ohne zweite Ebene: Er ist ein Punkt AUF dem
     * Balken, und der Balken ist ein Flex-Behälter. */
    if ($bezug > 0 && $schwellen) {
        $erste  = (int)min($schwellen);
        $bisher = $nenner > 0 ? $summe * 100 / $nenner : 0;
        if ($erste > $bisher) {
            $h .= '<span class="speicher-luecke" style="width:'
                . number_format(max(0, $erste - $bisher), 3, '.', '') . '%"></span>';
            $h .= '<span class="speicher-marke"></span>';
        }
    }
    $h .= '</div>';

    $h .= '<div class="speicher-legende">';
    foreach ($teile as $t) {
        if ((int)$t['bytes'] <= 0) { continue; }
        $h .= '<span><i class="' . ui_e($t['klasse']) . '"></i>'
            . ui_e($t['text']) . ' ' . edbak_groesse_text((int)$t['bytes']) . '</span>';
    }
    if ($bezug > 0) {
        $h .= '<span><i class="sb-frei"></i>frei ' . edbak_groesse_text($frei) . '</span>';
        if ($schwellen) {
            $h .= '<span>Warnschwelle ' . (int)min($schwellen) . ' %</span>';
        }
    }
    return $h . '</div>';
}

ui_seite_start(['titel' => 'Servereinstellungen']);
?>

<?php /* Lesespalte: zwei Karten, viel Erklärtext (E-S8-18, Mockup 07). */ ?>
<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_server', 'lesespalte' => true]); ?>

  <?php ui_titelzeile(['titel' => 'Servereinstellungen']); ?>
  <?= wartung_balken() ?>

  <?php if ($notice !== null): ?><?= ui_meldung_markup('ok', $notice) ?><?php endif; ?>
  <?php if ($error !== null): ?><?= ui_meldung_markup('fehler', $error) ?><?php endif; ?>


  <?php /* ---- Die Schlüssel des Servers (S10, E-S10-12) -------------------
       SIE STEHT ZUERST, VOR DEM SPEICHER. Die übrigen Karten dieser Seite
       melden Einstellungen; diese hier kann melden, dass sich niemand mehr
       anmelden kann. Eine rote Zeile unter einem Speicherbalken wird später
       gesehen als eine darüber.

       GEZEIGT WIRD NIE DER WERT, immer nur die KENNUNG (acht Hexzeichen aus
       SHA-256). Sie ist ein Vergleichsmerkmal, kein Geheimnis: Damit lässt
       sich prüfen, ob Blatt, Karte und Datenbank dasselbe meinen, ohne dass
       der Wert je auf dem Bildschirm steht. Wer ihn braucht, druckt das
       Blatt. */ ?>
  <?php
    /* VIER TOENE, UND „ok" IST KEINER DAVON. Das Stylesheet kennt
     * `.plakette-blau`, `-neutral`, `-orange` und `-rot` — nachgezaehlt am
     * 14.09.2026. Ein `'ton' => 'ok'` ergibt eine Klasse ohne Regel, also
     * eine ungestaltete Plakette, und zwar ohne jede Fehlermeldung. Zwei
     * Stellen im Bestand tun das schon (F-15). */
    $skTon = ['bereit' => 'blau', 'fehlt' => 'rot',
              'abweichend' => 'rot'][$skZustand['stand']] ?? 'neutral';
    /* „fehlt" ist NEUTRAL, nicht rot (Design.md 9.23): Ohne Anteil arbeitet
     * alles wie vorher, es geht nichts verloren. Rot bleibt der Lage
     * vorbehalten, in der niemand mehr an seine Angaben kommt. */
    $anTon = ['bereit' => 'blau', 'rotation' => 'blau', 'fehlt' => 'neutral',
              'abweichend' => 'rot'][$anZustand['stand']] ?? 'neutral';
    $anOffen = $anZaehlung['ohne'] + $anZaehlung['alt'];
  ?>
  <?php ui_karte_start(['titel' => 'Schlüssel des Servers', 'id' => 'k-schluessel',
      'plakette' => ($skZustand['stand'] === 'bereit' && $anZustand['stand'] === 'bereit')
          ? ui_plakette('in Ordnung', ['ton' => 'blau'])
          : (($skZustand['stand'] === 'abweichend' || $anZustand['stand'] === 'abweichend')
              ? ui_plakette('abweichend', ['ton' => 'rot'])
              : ($skZustand['stand'] === 'fehlt'
                  ? ui_plakette('unvollständig', ['ton' => 'rot'])
                  : ($anZustand['stand'] === 'fehlt'
                      ? ui_plakette('nicht eingerichtet', ['ton' => 'neutral'])
                      : ui_plakette('Übergang läuft', ['ton' => 'blau']))))]); ?>

    <p class="feld-hinweis">Diese Installation hat <strong>zwei Geheimnisse</strong>,
       und beide stehen in <code>config.php</code> — nicht in der Datenbank. Der
       <strong>Serverschlüssel</strong> versiegelt, was der Server ohne Browser
       lesen können muss: Zugangsdaten der Backup-Ziele, Komplett-Backup,
       Konto-Backups. Der <strong>Server-Anteil</strong> geht in den
       Datenschlüssel <em>jedes Kontos</em> ein; der Server kann damit trotzdem
       nichts öffnen, aber ein Datenbankabzug allein reicht nicht mehr, um ein
       Passwort durchzuprobieren.</p>

    <?php
      ui_zeile([
        'text'  => 'Serverschlüssel',
        'klein' => $skZustand['stand'] === 'bereit'
            ? 'Kennung ' . $skZustand['kennung'] . ' — versiegelt Backup-Ziele, '
              . 'Komplett-Backup und Konto-Backups'
            : ($skZustand['stand'] === 'fehlt'
                ? 'Fehlt. Ohne ihn entsteht kein Komplett-Backup, kein '
                  . 'Konto-Backup und kein Versand auf ein Backup-Ziel'
                : 'In config.php steht Kennung '
                  . ($skZustand['kennung'] ?? '—') . ', versiegelt wurde mit '
                  . $skZustand['erwartet'] . '. Versiegeltes lässt sich nicht '
                  . 'mehr öffnen, bis der richtige Wert nachgetragen ist'),
        'plaketten' => ui_plakette(
            ['bereit' => 'vorhanden', 'fehlt' => 'fehlt',
             'abweichend' => 'abweichend'][$skZustand['stand']] ?? '?',
            ['ton' => $skTon]),
      ]);

      ui_zeile([
        'text'  => 'Server-Anteil',
        'klein' => $anZustand['stand'] === 'bereit'
            ? 'Kennung ' . $anZustand['kennung'] . ' — geht in den '
              . 'Datenschlüssel jedes Kontos ein'
            : ($anZustand['stand'] === 'rotation'
                ? 'Rotation läuft: neu ' . $anZustand['kennung'] . ', alt '
                  . $anZustand['kennung_alt'] . '. Beide werden ausgeliefert, '
                  . 'bis kein Konto mehr auf dem alten steht'
                : ($anZustand['stand'] === 'fehlt'
                    ? 'Nicht eingerichtet. Alles läuft wie vor S10 — der Schutz '
                      . 'gegen einen Datenbankabzug fehlt aber'
                    : 'In config.php steht Kennung '
                      . ($anZustand['kennung'] ?? '—') . ', gebaut wurden die '
                      . 'Hüllen mit ' . $anZustand['erwartet'] . '. Solange das '
                      . 'so ist, kommt niemand mit umgestellter Hülle an seine '
                      . 'geschützten Angaben')),
        'plaketten' => ui_plakette(
            ['bereit' => 'bereit', 'rotation' => 'Rotation',
             'fehlt' => 'nicht eingerichtet',
             'abweichend' => 'abweichend'][$anZustand['stand']] ?? '?',
            ['ton' => $anTon]),
      ]);

      /* DIE ZÄHLUNG STEHT ALS EIGENE ZEILE, nicht als drei Zahlen in einer
         Plakette: Sie beantwortet eine andere Frage als der Zustand — nicht
         „ist der Anteil richtig", sondern „wie weit ist die Umstellung". */
      if ($anZustand['stand'] !== 'fehlt') {
          $teile = [$anZaehlung['neu'] . ' auf dem aktuellen Anteil'];
          if ($anZustand['kennung_alt'] !== null) {
              $teile[] = $anZaehlung['alt'] . ' auf dem alten';
          } elseif ($anZaehlung['alt'] > 0) {
              $teile[] = $anZaehlung['alt'] . ' auf einem unbekannten';
          }
          if ($anZaehlung['ohne'] > 0) { $teile[] = $anZaehlung['ohne'] . ' noch ohne'; }
          if ($anZaehlung['demo'] > 0) {
              $teile[] = 'das Demo-Konto bleibt ohne Anteil und zählt nicht mit';
          }
          ui_zeile([
            'text'  => 'Umstellung der Konten',
            'klein' => implode(' · ', $teile) . '. Jedes Konto stellt beim '
                     . 'nächsten Anmelden von selbst um; niemand muss etwas tun.',
            'plaketten' => ui_plakette($anOffen === 0 ? 'vollständig'
                                                      : $anOffen . ' offen',
                ['ton' => 'blau']),
          ]);
      }
    ?>

    <?php /* ---- GENAU EIN PRIMAERER KNOPF, UND ZWAR DER, DER DRAN IST ----
             `Design.md` fuehrt „zwei primaere Knoepfe auf einer Seite" als
             Anti-Muster: Keiner ist dann mehr die Haupthandlung. Welche das
             ist, haengt hier an der Lage — fehlt ein Wert, ist es das
             Anlegen; weicht einer ab, das Nachtragen; sonst das Blatt. Die
             uebrigen Knoepfe sind `neutral`. */ ?>
    <?php
      $hauptAnlegenSk = $skZustand['stand'] === 'fehlt';
      $hauptAnlegenAn = !$hauptAnlegenSk && $anZustand['stand'] === 'fehlt';
      $abweichend = $skZustand['stand'] === 'abweichend'
                 || $anZustand['stand'] === 'abweichend';
      $hauptBlatt = !$hauptAnlegenSk && !$hauptAnlegenAn && !$abweichend;
    ?>
    <?php if ($skZustand['stand'] === 'fehlt' || $anZustand['stand'] === 'fehlt'): ?>
      <div class="listen-form-fuss">
        <?php if ($skZustand['stand'] === 'fehlt'): ?>
          <form method="post" action="betrieb_server.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_sk_anlegen">
            <?= ui_knopf(['text' => 'Serverschlüssel anlegen', 'symbol' => 'schloss',
                          'art' => $hauptAnlegenSk ? 'primaer' : 'neutral']) ?>
          </form>
        <?php endif; ?>
        <?php if ($anZustand['stand'] === 'fehlt'): ?>
          <form method="post" action="betrieb_server.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anteil_anlegen">
            <?= ui_knopf(['text' => 'Server-Anteil anlegen', 'symbol' => 'schloss',
                          'art' => $hauptAnlegenAn ? 'primaer' : 'neutral']) ?>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php /* ---- Blatt, Wechseln, Entfernen ------------------------------ */ ?>
    <div class="listen-form-fuss">
      <?php if ($skZustand['kennung'] !== null || $anZustand['kennung'] !== null): ?>
        <?php /* KEIN `target="_blank"`. Der Bestand oeffnet nur FREMDE Adressen
                 in einem neuen Tab (Play-Store, Connect IQ, Lizenztext), und
                 `rechtstexte_lib.php` begruendet ausdruecklich, warum eine
                 eigene Seite es nicht tut: Sie nimmt dem Browser den
                 Zurueck-Weg. Das Blatt hat einen eigenen. */ ?>
        <?= ui_knopf(['text' => 'Schlüsselblatt drucken', 'symbol' => 'schloss',
                      'art' => $hauptBlatt ? 'primaer' : 'neutral',
                      'href' => 'betrieb_schluesselblatt.php']) ?>
      <?php endif; ?>
      <?php if (in_array($anZustand['stand'], ['bereit', 'rotation'], true)
                && $anZustand['kennung_alt'] === null): ?>
        <form method="post" action="betrieb_server.php"
              data-confirm="Den Server-Anteil wechseln? Der bisherige bleibt stehen, bis kein Konto mehr auf ihm steht — die Umstellung läuft je Konto beim nächsten Anmelden. Danach ist ein NEUES Schlüsselblatt zu drucken; das alte gilt nicht mehr."
              data-confirm-ok="Wechseln" data-confirm-tone="normal">
          <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anteil_wechseln">
          <?= ui_knopf(['text' => 'Server-Anteil wechseln', 'symbol' => 'tausch']) ?>
        </form>
      <?php endif; ?>
      <?php if ($anZustand['kennung_alt'] !== null && $anZaehlung['alt'] === 0): ?>
        <form method="post" action="betrieb_server.php"
              data-confirm="Den alten Server-Anteil aus config.php entfernen? Kein Konto steht mehr auf ihm — danach ist die Rotation abgeschlossen und das alte Schlüsselblatt gegenstandslos."
              data-confirm-ok="Entfernen" data-confirm-tone="normal">
          <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anteil_alt_entfernen">
          <?= ui_knopf(['text' => 'Alten Anteil entfernen', 'symbol' => 'korb']) ?>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($anZustand['kennung_alt'] !== null && $anZaehlung['alt'] > 0): ?>
      <p class="feld-hinweis">Der alte Anteil lässt sich entfernen, sobald
         <strong>kein Konto</strong> mehr auf ihm steht — derzeit sind es
         <strong><?= (int)$anZaehlung['alt'] ?></strong>. Bis dahin bleibt er in
         <code>config.php</code>, sonst kämen diese Konten nicht mehr an ihre
         geschützten Angaben.</p>
    <?php endif; ?>

    <?php /* ---- Nachtragen vom Blatt (E-S10-10) -------------------------- */ ?>
    <?php if ($skZustand['stand'] === 'abweichend' || $anZustand['stand'] === 'abweichend'): ?>
      <p class="feld-hinweis"><strong>Nachtragen vom Blatt.</strong> Den Wert vom
         Schlüsselblatt einfügen — Leerzeichen und Bindestriche dürfen
         drinbleiben, Groß- und Kleinschreibung ist gleich. Der Server rechnet
         die Kennung und <strong>schreibt nur bei Übereinstimmung</strong>:
         Ein falsch abgeschriebener Wert, der stillschweigend landet, macht aus
         einer behebbaren Lage eine unbehebbare.</p>
      <?php foreach ([
            ['anteil', 'Server-Anteil', $anZustand, 'schluessel_anteil_nachtragen'],
            ['sk', 'Serverschlüssel', $skZustand, 'schluessel_sk_nachtragen'],
          ] as [$kurz, $name, $z, $aktion]): ?>
        <?php if ($z['stand'] !== 'abweichend') { continue; } ?>
        <form method="post" action="betrieb_server.php">
          <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($aktion) ?>">
          <?php /* `.feld-fest` — die Schreibmaschinenschrift, die es dafür
                   gibt (Stylesheet, `.feld-fest .feld-eingabe`). 64 Hexzeichen
                   in einer Proportionalschrift sind beim Vergleichen mit dem
                   Blatt genau die Zeile, in der man verrutscht. */ ?>
          <?php ui_feld(['name' => 'wert', 'label' => $name . ' vom Blatt',
              'klasse' => 'feld-fest',
              'attr' => ' autocomplete="off" spellcheck="false" inputmode="latin"',
              'klein' => 'Erwartet wird der Wert mit Kennung '
                       . ($z['erwartet'] ?? '—') . '.']); ?>
          <?php if ($z['kennung'] !== null): ?>
            <?php ui_schalter(['name' => 'ersetzen',
                'label' => 'Den vorhandenen Eintrag ersetzen',
                'an' => false,
                'klein' => 'In config.php steht bereits ein gültiger Wert '
                         . '(Kennung ' . $z['kennung'] . '). Ohne diesen Haken '
                         . 'wird nichts überschrieben.']); ?>
          <?php endif; ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => $name . ' nachtragen', 'symbol' => 'haken',
                          'art' => 'primaer']) ?><?php /* die Haupthandlung
                          dieser Lage — siehe oben */ ?>
          </div>
        </form>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php /* ---- Neuanfang (E-S10-09, Zustand „Neuanfang") ---------------- */ ?>
    <?php if ($anZustand['stand'] === 'abweichend'): ?>
      <p class="feld-hinweis"><strong>Wenn der Wert unwiederbringlich weg ist</strong>
         — <code>config.php</code> verloren und beide Ausdrucke des Blatts dazu —,
         bleibt der Neuanfang. Danach setzt <em>jede NutzerIn</em> ihr Passwort
         über den <strong>Wiederherstellungsschlüssel</strong> neu. Das ist
         <strong>kein Datenverlust</strong>: Die Wiederherstellungs-Hülle hängt
         nicht am Server-Anteil. Es ist ein Vorgang für alle — und der letzte
         Ausweg, nicht der erste.</p>
      <div class="listen-form-fuss">
        <form method="post" action="betrieb_server.php"
              data-confirm="Einen NEUEN Server-Anteil erzeugen? Jede NutzerIn mit umgestellter Hülle muss danach ihr Passwort über den Wiederherstellungsschlüssel neu setzen. Die Daten selbst bleiben unversehrt. Dieser Schritt lässt sich nicht zurücknehmen — der bisherige Wert ist danach gegenstandslos."
              data-confirm-ok="Neu erzeugen" data-confirm-tone="gefahr">
          <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anteil_neuanfang">
          <?= ui_knopf(['text' => 'Server-Anteil neu erzeugen', 'symbol' => 'warnung',
                        'art' => 'gefahr']) ?>
        </form>
      </div>
    <?php endif; ?>

    <?php if (isset($anZaehlung['fehler'])): ?>
      <?= ui_meldung_markup('fehler', 'Die Konten ließen sich nicht zählen: '
          . $anZaehlung['fehler']) ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Speicher', 'id' => 'k-speicher',
      'zahl' => $sp['stand'] !== null
          ? 'Stand ' . fmt_local((string)$sp['stand'], 'd.m.Y H:i')
          : 'noch nicht gemessen',
      'plakette' => $sp['backups']['bezug'] > 0
          ? ui_plakette($sp['backups']['prozent'] . ' %',
                        ['ton' => speicher_ton($sp['backups']['prozent'], $sp['schwellen'])])
          : '']); ?>

    <?php if ($sp['stand'] === null): ?>
      <?= ui_meldung_markup('info', 'Datenbank und Dateien sind noch nicht gemessen. '
          . 'Die Messung läuft einmal täglich im Aufräumjob mit; bis dahin zeigt '
          . '„Installation gesamt" nur die Backups.') ?>
    <?php endif; ?>

    <h3 class="listen-form-titel">Backups
      <span class="feld-klein-inline"><?= edbak_groesse_text($sp['backups']['summe']) ?>
        von <?= edbak_groesse_text($sp['backups']['bezug']) ?> Grenze ·
        <?= (int)$sp['backups']['prozent'] ?> %</span></h3>
    <?= speicher_balken([
          ['klasse' => 'sb-konto',    'bytes' => $sp['backups']['konto'],
           'text' => 'Konto-Backups'],
          ['klasse' => 'sb-komplett', 'bytes' => $sp['backups']['komplett'],
           'text' => 'Komplett-Backups'],
        ], $sp['backups']['bezug'], $sp['schwellen']) ?>

    <h3 class="listen-form-titel">Installation gesamt
      <span class="feld-klein-inline"><?= edbak_groesse_text($sp['gesamt']['summe']) ?><?php
        if ($sp['gesamt']['bezug'] > 0): ?> von
        <?= edbak_groesse_text($sp['gesamt']['bezug']) ?> Webspace ·
        <?= (int)$sp['gesamt']['prozent'] ?> %<?php
        else: ?> — ohne Webspace-Angabe kein Anteil<?php endif; ?></span></h3>
    <?= speicher_balken([
          ['klasse' => 'sb-db',       'bytes' => $sp['gesamt']['datenbank'],
           'text' => 'Datenbank'],
          ['klasse' => 'sb-dateien',  'bytes' => $sp['gesamt']['dateien'],
           'text' => 'Dateien'],
          ['klasse' => 'sb-konto',    'bytes' => $sp['gesamt']['konto'],
           'text' => 'Konto-Backups'],
          ['klasse' => 'sb-komplett', 'bytes' => $sp['gesamt']['komplett'],
           'text' => 'Komplett-Backups'],
        ], $sp['gesamt']['bezug'], $sp['schwellen']) ?>

    <p class="feld-klein">Datenbank aus <code>information_schema</code> (Daten und
       Indizes), Dateien aus dem Verzeichnislauf über das Anwendungsverzeichnis
       (Code, Symbole, Logos, APK) — ohne <code>sicherungen/</code>, die zählen
       bei den Backups. Der freie Webspace wird <strong>nicht gemessen</strong>:
       <code>disk_free_space()</code> zeigt auf geteiltem Hosting den Datenträger
       des Hosts, nicht die Quota. Versendete Pakete auf Backup-Zielen zählen
       nirgends mit — sie liegen außerhalb dieses Webspace.</p>

    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="speicher">
      <div class="fld-reihe">
        <?php ui_feld(['name' => 'grenze', 'label' => 'Speichergrenze Backups',
            'wert' => rtrim(rtrim(number_format(
                          edbak_grenze_bytes() / (1024 * 1024 * 1024), 2, '.', ''), '0'), '.'),
            'klein' => 'GB für Konto-Backups und Komplett-Backups zusammen. Ist sie '
                     . 'erreicht, wird nicht mehr gesichert; gelöscht wird nichts.']); ?>
        <?php ui_feld(['name' => 'schwellen', 'label' => 'Warnschwellen',
            'wert' => implode(', ', edbak_schwellen()),
            'klein' => 'Prozent, durch Komma getrennt — gelten für beide Balken. '
                     . 'Je Schwelle einmal eine Meldung.']); ?>
      </div>
      <div class="fld-reihe">
        <?php ui_feld(['name' => 'webspace', 'label' => 'Webspace laut Hosting',
            'label_zusatz' => 'optional',
            'wert' => speicher_webspace_bytes() > 0
                ? rtrim(rtrim(number_format(
                      speicher_webspace_bytes() / (1024 * 1024 * 1024), 2, '.', ''), '0'), '.')
                : '',
            'klein' => 'GB, aus dem Hosting-Tarif abgelesen. Ohne Angabe zeigt '
                     . '„Installation gesamt" nur die Summe — ohne Anteil und ohne '
                     . 'Warnung.']); ?>
        <?php ui_feld(['name' => 'db_gb', 'label' => 'Kontingent der Datenbank',
            'wert' => rtrim(rtrim(number_format(
                          speicher_db_kontingent_bytes() / (1024 * 1024 * 1024),
                          2, '.', ''), '0'), '.'),
            'klein' => 'GB, aus dem Hosting-Tarif abgelesen. Kein Hoster macht es '
                     . 'abfragbar, deshalb eine Angabe — leer setzt auf die Vorgabe '
                     . SPEICHER_DB_GB_VORGABE . ' GB zurück. Gewarnt wird mit '
                     . 'denselben Schwellen wie oben.']); ?>
      </div>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>

    <?php
      /* ---- Ablage und Reste: rein lesend (AB-08, zieht mit S8 hierher) --- */
      ui_zeile([
        'text'  => 'Ablage',
        'klein' => (string)$sp['ablage']['pfad'],
        'plaketten' => $sp['ablage']['ok']
            ? ui_plakette('beschreibbar', ['ton' => 'blau'])
            : ui_plakette('nicht beschreibbar', ['ton' => 'rot']),
      ]);
      if (!$sp['ablage']['ok'] && $sp['ablage']['grund'] !== null) {
          echo ui_meldung_markup('fehler', (string)$sp['ablage']['grund']);
      }
      ui_zeile([
        'text'  => 'Reste abgebrochener Läufe',
        'klein' => 'Bauordner und .tmp-Dateien — werden vom Aufräumjob entfernt',
        'plaketten' => ui_plakette((string)$sp['reste'],
            ['ton' => $sp['reste'] > 0 ? 'orange' : 'neutral']),
      ]);
      ui_zeile([
        'text'  => 'Pakete in der Ablage',
        'klein' => $sp['ordner'] . ' Konten mit mindestens einem Konto-Backup',
        'plaketten' => ui_plakette((string)$sp['pakete'], ['ton' => 'neutral']),
      ]);
    ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Adresssuche (S9/AP2, E-S9-05, R79) ------------------------
           Sie steht im Betrieb und nicht in den Kontoeinstellungen, weil sie
           die Installation betrifft: Wer sie hier abschaltet, schaltet sie
           fuer alle ab (R74 (1) — BetreiberIn, trifft alle). Der Kontoschalter
           daneben steht im Profil und kann nur noch einschraenken. */ ?>
  <?php ui_karte_start(['titel' => 'Adresssuche', 'id' => 'k-adresssuche',
      'plakette' => geocoder_installation_an()
          ? ui_plakette('an', ['ton' => 'ok'])
          : ui_plakette('aus', ['ton' => 'neutral'])]); ?>
    <p class="feld-hinweis">Beim Tippen in einem Ortsfeld und nach jeder Wahl auf
       der Karte fragt die Anwendung einen <strong>Adressdienst</strong> —
       vorwärts nach Vorschlägen zum getippten Text, rückwärts nach der Adresse
       zu einer Koordinate. Der getippte Text und die Koordinate verlassen dabei
       das Gerät. Alles Übrige bleibt hier: Koordinaten, Plus Codes, „Meine
       Position" und die Karte selbst brauchen den Dienst nicht.</p>

    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="geocoder">
      <?php ui_schalter(['name' => 'adresssuche', 'label' => 'Adresssuche im Internet',
          'an' => geocoder_installation_an(),
          'klein' => 'Aus heißt: keine Vorschläge, keine Umkehrsuche, kein '
                   . 'Suchfeld im Kartendialog — für alle Konten dieser '
                   . 'Installation.']); ?>
      <?php ui_feld(['name' => 'dienst', 'label' => 'Dienst',
          'wert' => geocoder_dienst(),
          'attr' => ' maxlength="180" inputmode="url"',
          'klein' => 'Adresse eines Photon-Dienstes, mit „https://". Vorgabe ist '
                   . 'der frei betriebene Gemeinschaftsdienst ' . e(GEOCODER_VORGABE)
                   . '. Wer einen eigenen betreibt, trägt ihn hier ein — dann '
                   . 'verlassen die Anfragen das eigene Haus nicht.']); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt',
                        'vorschau' => 'Schlüssel · Grenze · Schwellen · Webspace · Adresssuche']); ?>
    <p class="feld-hinweis"><strong>Die Grenze gilt nur für Backups.</strong> Die
       Datenbank wächst mit jedem Einsatz und wird nie angehalten — eine Grenze
       darauf hieße, die Anwendung anzuhalten. Ist die Grenze erreicht, wird
       <em>nicht mehr gesichert</em>; gelöscht wird nie von selbst.</p>
    <p class="feld-hinweis"><strong>Warnschwellen</strong> melden einmal je
       Schwelle, für beide Balken. Mit eingerichtetem SMTP geht die Meldung
       zusätzlich an alle mit Verwaltungsrechten. Wer Grenze oder Schwellen
       ändert, setzt die Meldungen zurück: Dieselben Bytes sind bei einer
       anderen Grenze eine andere Aussage.</p>
    <p class="feld-hinweis"><strong>Der Webspace ist eine Angabe, keine
       Messung.</strong> Er steht im Hosting-Tarif und lässt sich von hier aus
       nicht ermitteln. Ohne ihn bleibt der zweite Balken eine Zusammensetzung
       ohne Füllstand — das ist ehrlicher als eine erfundene Bezugsgröße.</p>
    <p class="feld-hinweis"><strong>Gemessen wird einmal täglich</strong>, im
       Aufräumjob. Der Stand steht im Kartenkopf. Die Backups werden dagegen bei
       jedem Aufruf gewogen — ihr Verzeichnis ist klein genug dafür, und ihre
       Zahl entscheidet, ob noch gesichert werden darf.</p>
    <p class="feld-hinweis"><strong>Die beiden Schlüssel sind verschiedene
       Dinge.</strong> Der <em>Serverschlüssel</em> versiegelt, was das Haus
       verlässt — Backup-Ziele, Komplett-Backup, Konto-Backups. Der
       <em>Server-Anteil</em> geht in den Datenschlüssel jedes Kontos ein und
       verlässt das Haus nie. Beide stehen in <code>config.php</code> und
       nirgends sonst; angezeigt wird hier immer nur ihre <strong>Kennung</strong>
       (acht Hexzeichen), nie der Wert. Wer den Wert braucht, druckt das
       Schlüsselblatt — das ist die einzige Seite, die ihn zeigt.</p>
    <p class="feld-hinweis"><strong>Der Verlust des Anteils ist kein
       Datenverlust.</strong> Die Wiederherstellungs-Hülle hängt nicht an ihm:
       Jede NutzerIn kommt über ihren Wiederherstellungsschlüssel wieder herein
       und setzt dabei ihr Passwort neu. Es ist ein Vorgang für alle — und
       genau deshalb gehört das Schlüsselblatt an zwei Orte.</p>
    <p class="feld-hinweis"><strong>Die Adresssuche ist zweimal abschaltbar</strong>
       — hier für die Installation und im Profil je Konto. Diese Einstellung ist
       die Obergrenze: Ist sie aus, ist der Kontoschalter ausgegraut und die
       Suche für alle aus. Der Datenschutztext nennt den eingetragenen Dienst;
       wer ihn wechselt, sollte den Text gegenlesen (Verwaltung →
       Rechtstexte).</p>
  <?php ui_karte_ende(true); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
