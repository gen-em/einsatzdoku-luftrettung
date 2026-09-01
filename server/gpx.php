<?php
declare(strict_types=1);

/**
 * Spuren als GPX-Datei — je Einsatz, je Ruhesegment, und mehrere ausgewaehlte
 * eines Diensttages in EINER Datei (S2/AP4, E-S2-09).
 *
 * DIE ERSTE DATEI, DIE DIESER SERVER AUSLIEFERT. Warum das hier richtig und
 * anderswo falsch ist, steht in `gpx_lib.php`: Alle uebrigen Dateien der
 * Anwendung tragen Ende-zu-Ende verschluesselten Inhalt, den der Server gar
 * nicht zusammensetzen KANN. Spurpunkte liegen im Klartext, und die Stufe,
 * die E-S2-09 sichtbar verlangt, kennt ohnehin nur er.
 *
 * GET UND OHNE CSRF, wie die uebrigen lesenden Endpunkte (M3-11): Was nichts
 * aendert, beantwortet auch kein POST. Der Schutz ist die Sitzung.
 *
 * WARUM NICHT UNTER `api/`. Diese Datei stand dort zuerst, und das war
 * falsch: `ist_api_aufruf()` (auth_guard.php) entscheidet allein am Pfad —
 * enthaelt er `/api/`, gilt die Anfrage als `fetch()` eines Skripts und
 * bekommt bei abgelaufener Sitzung JSON 401 statt der Anmeldeseite. Diese
 * Annahme stimmte, solange nichts in der Oberflaeche nach `api/` VERLINKTE.
 * Der GPX-Abruf ist ein `<a href>`, den eine Nutzerin anklickt: Nach einer
 * Mittagspause haette sie `{"error":"session_ende"}` im Browserfenster
 * gesehen statt der Anmeldeseite. Also gehoert er neben die anderen Seiten.
 *
 * KEINE SCHRANKE WIE IM EXPORT — und das ist begruendet, nicht vergessen.
 * `api/export_data.php` verweigert Spurpunkte, solange der Haken
 * „personenbezogene Angaben" fehlt (A9): Ein Export OHNE diese Angaben ist
 * eine Datei zum Weitergeben, und eine Spur endet am Einsatzort. Hier gibt es
 * diese anonyme Fassung gar nicht — es gibt nur den einen Abruf, und der ist
 * die personenbezogene Fassung. Es gaebe also keinen Haken zu umgehen.
 *
 * KEINE SPERRE AUF DEN INHALTSSCHLUESSEL. Die Einsatzansicht zeichnet dieselbe
 * Spur bereits auf ihre Karte, ohne dass jemand entsperrt haben muss — die
 * Punkte sind Klartext (Backlog Nr. 43). Eine Sperre hier waere Theater: Sie
 * verweigerte die Datei und zeigte den Weg daneben weiter an. Dass die Spur
 * ueberhaupt unverschluesselt liegt, ist ein bekannter offener Punkt und
 * gehoert dorthin, nicht in eine halbe Massnahme an dieser Stelle.
 *
 * ZWEI ABRUFE, EIN WEG. `?art=…&id=…` liefert eine Spur, `?tag=…&auswahl=…`
 * mehrere eines Diensttages. Beide bauen ueber dieselbe Funktion
 * (`gpx_bauen_viele()`), und beide gehen durch dieselbe Datentrennung — sonst
 * waere die Auswahl ein zweiter, schwaecherer Weg an denselben Bestand.
 *
 * WARUM DIE AUSWAHL AN EINEN DIENSTTAG GEBUNDEN IST. Sie koennte auch eine
 * freie Liste von Kennungen sein; der Filter auf `user_id` traegt die
 * Trennung ohnehin. Der Diensttag ist trotzdem richtig: Er gibt der Datei
 * einen wahren Namen und eine Reihenfolge (die Spuren stehen chronologisch,
 * wie sie gefahren wurden), und die Seite, von der die Auswahl kommt, ist
 * ohnehin die eines Tages.
 *
 * DIE DATENTRENNUNG STEHT HIER UND NUR HIER. `spur_lib.php` prueft kein
 * Eigentum — es nimmt owner_type und owner_id und liest, was da ist. Wer
 * diesen Filter vergisst, liefert fremde Spuren aus. Dasselbe Muster wie in
 * `api/export_data.php` und `api/mission.php`: erst gegen `user_id` und
 * `deleted_at` filtern, DANN lesen.
 */

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/gpx_lib.php';
require_once __DIR__ . '/ratelimit_lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { json_out(['error' => 'method'], 405); }

/* MENGENBREMSE (S2/AP4). Ein Abruf je Anfrage, die Kennung frei durchzaehlbar
 * — wer den ganzen eigenen Bestand abziehen will, braucht dafuer nur Zeit. Das
 * ist kein Einbruch (es sind die eigenen Daten), aber eine Anwendung, die
 * tausend Dateien in einer Minute ausliefert, hat etwas anderes vor sich als
 * eine Nutzerin, die eine Spur mitnimmt. Derselbe Topf wie beim Koppeln, mit
 * derselben Wirkung: Nach zehn Fehlgriffen zehn Minuten Ruhe. Gezaehlt wird
 * hier NUR das Scheitern — ein gelungener Abruf geht nicht aufs Kontingent,
 * sonst traefe die Bremse die Spurenseite eines Tages mit zwoelf Eintraegen. */
if (!rate_erlaubt('pair')) {
    json_out(['error' => 'zu_viele_versuche'], 429);
}

/**
 * Die fertige Datei mit ihren Kopfzeilen — fuer beide Abrufe dieselbe Stelle.
 *
 * `attachment` und nicht `inline`: Eine Spur ist eine Datei zum Behalten,
 * keine Seite zum Ansehen — und ein XML-Dokument, das der Browser selbst
 * darstellt, laedt zum Verwechseln mit einer Anwendungsseite ein.
 *
 * Der Dateiname ist bereits auf [A-Za-z0-9._-] beschraenkt (`gpx_dateiname()`,
 * `gpx_dateiname_tag()`); ein Anfuehrungszeichen oder Zeilenumbruch koennte
 * die Kopfzeile sonst aufbrechen.
 */
function gpx_ausliefern(string $xml, string $datei): void
{
    header('Content-Type: application/gpx+xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $datei . '"');
    header('Content-Length: ' . strlen($xml));
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $xml;
}

try {
    $pdo = db();

    /* Wie eine Spur in der Datei heisst. EINE Stelle fuer beide Abrufe: Wer
     * dieselbe Spur einmal einzeln und einmal in einer Auswahl herunterlaedt,
     * soll sie in beiden Dateien unter demselben Namen wiederfinden.
     *
     * Die KENNUNG und nicht die laufende Nummer des Tages („Einsatz 3"): Die
     * laufende Nummer gilt nur innerhalb eines Tages, die Kennung steht auf
     * der Spurenseite in der Kleinzeile jeder Zeile daneben. */
    $spurname = static function (string $art, int $id, ?string $von): string {
        $n = ($art === 'mission' ? 'Einsatz ' : 'Ruhezeit ') . $id;
        return $von ? $n . ' — ' . fmt_local($von, 'd.m.Y H:i') : $n;
    };

    /* ---- Mehrere Spuren eines Diensttages als EINE Datei ------------------
     *
     * MEHRERE `<trk>`, KEIN ZUSAMMENGEKLEBTES `<trkseg>` — die Begruendung
     * steht bei `gpx_bauen_viele()`: Ein einziges Segment liesse jedes
     * Kartenprogramm eine gerade Linie vom Ende der einen Spur zum Anfang der
     * naechsten ziehen, einen Weg, den niemand gefahren ist.
     */
    if (isset($_GET['tag'])) {
        $dayId = (int)$_GET['tag'];
        /* Das Formular schickt `auswahl[]` (ein Kaestchen je Zeile), von Hand
         * getippt geht auch `auswahl=mission-12,rest-7`. Beides fuehrt in
         * dieselbe Pruefung. */
        $roh   = $_GET['auswahl'] ?? '';
        $teile = is_array($roh) ? $roh : explode(',', (string)$roh);
        $teile = array_values(array_filter(array_map(
            static fn($t) => trim((string)$t), $teile), static fn($t) => $t !== ''));

        if ($dayId <= 0 || !$teile) {
            rate_misserfolg('pair');
            json_out(['error' => 'payload'], 400);
        }
        /* MENGENGRENZE. Nicht wegen der Rechte — es sind die eigenen Spuren —,
         * sondern wegen des Speichers: Die Datei entsteht vollstaendig im
         * Arbeitsspeicher, weil ihre Laenge in die Kopfzeile gehoert. Bei der
         * groessten gemessenen Spur des Referenzbestands (1063 Punkte, 9581
         * Spuren) sind hundert Spuren rund 11 MB — im Budget von 64 MB (Z3),
         * und weit ueber dem, was ein Diensttag traegt. */
        if (count($teile) > GPX_AUSWAHL_MAX) {
            rate_misserfolg('pair');
            json_out(['error' => 'zu_viele'], 400);
        }

        $gewaehlt = ['mission' => [], 'rest' => []];
        foreach ($teile as $t) {
            /* STRENG BEI DER FORM, NACHSICHTIG BEIM BESTAND — und der
             * Unterschied hat einen Grund.
             *
             * Was nicht genau `mission-<Zahl>` oder `rest-<Zahl>` ist, kommt
             * nicht von dieser Seite; das ist ein Fehler und keine Auswahl,
             * also 400.
             *
             * Eine wohlgeformte Kennung dagegen, die zu diesem Tag und diesem
             * Konto nicht gehoert, faellt unten beim Lesen heraus, ohne dass
             * die ganze Datei scheitert: Sie kann von einem Tab stammen, der
             * seit einer Loeschung offen steht. Wie viele Spuren tatsaechlich
             * drin sind, sagen der Dateiname und das `<desc>` im Kopf — das
             * Fehlende ist also sichtbar. Und ausgeforscht wird dabei nichts:
             * Die Abfrage filtert auf `user_id` UND `day_id`, eine fremde
             * Kennung liefert also nie einen Treffer, gleich ob es sie gibt.
             * Bleibt gar nichts uebrig, ist es doch ein Fehlgriff — dann 404,
             * und er zaehlt. */
            if (!preg_match('/^(mission|rest)-([0-9]{1,10})$/', $t, $m)) {
                rate_misserfolg('pair');
                json_out(['error' => 'payload'], 400);
            }
            $gewaehlt[$m[1]][(int)$m[2]] = true;
        }

        /* DATENTRENNUNG, erster Teil: der Diensttag. */
        $st = $pdo->prepare('SELECT day FROM days
                              WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
        $st->execute([$dayId, $userId]);
        $tagDatum = $st->fetchColumn();
        if ($tagDatum === false) {
            rate_misserfolg('pair');
            json_out(['error' => 'not_found'], 404);
        }

        /* DATENTRENNUNG, zweiter Teil: die Eintraege. Gefragt wird nicht „gibt
         * es die Kennung", sondern „welche Eintraege DIESES Tages in DIESEM
         * Konto sind gewaehlt" — eine fremde oder erfundene Kennung ist damit
         * nicht dabei, ohne dass es dafuer eine eigene Pruefung braeuchte. */
        $liste = [];
        foreach (['mission' => 'missions', 'rest' => 'rest_segments'] as $art => $tabelle) {
            if (!$gewaehlt[$art]) { continue; }
            $st = $pdo->prepare("SELECT id, started_at FROM `$tabelle`
                                  WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL");
            $st->execute([$userId, $dayId]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                if (isset($gewaehlt[$art][(int)$r['id']])) {
                    $liste[] = ['art' => $art, 'id' => (int)$r['id'],
                                'von' => (string)($r['started_at'] ?? '')];
                }
            }
        }
        /* CHRONOLOGISCH ueber beide Arten hinweg — so, wie der Tag verlaufen
         * ist, und in derselben Folge wie die Liste in `tag_spuren.php`, aus
         * der die Auswahl kommt. Derselbe Schluessel dort wie hier: Beginn,
         * Art, Kennung. */
        usort($liste, static fn($a, $b) => [$a['von'], $a['art'], $a['id']]
                                       <=> [$b['von'], $b['art'], $b['id']]);

        if (!$liste) {
            rate_misserfolg('pair');
            json_out(['error' => 'not_found'], 404);
        }

        /* WELCHE TRAGEN UEBERHAUPT PUNKTE — ohne sie zu lesen. `spur_zahlen()`
         * nimmt die Zahl aus dem Blobkopf und zaehlt nur Nachzuegler; ein
         * Eintrag ohne Spur faellt hier heraus und nicht erst beim Bauen. */
        $zahlen = [];
        foreach (['mission', 'rest'] as $art) {
            $ids = array_map(static fn($e) => $e['id'],
                             array_filter($liste, static fn($e) => $e['art'] === $art));
            $zahlen[$art] = $ids ? spur_zahlen($pdo, $art, array_values($ids)) : [];
        }
        $mitSpur = array_values(array_filter($liste,
            static fn($e) => ($zahlen[$e['art']][$e['id']] ?? 0) > 0));
        if (!$mitSpur) {
            json_out(['error' => 'keine_spur'], 404);
        }

        $stufen = [];
        foreach ($mitSpur as $i => $e) {
            $stand = spur_stand($pdo, $e['art'], $e['id']);
            $mitSpur[$i]['stand'] = $stand;
            $stufen[] = (int)$stand['stufe'];
        }

        /* EIN GENERATOR UND KEIN FELD. Eine dekodierte Spur kostet rund 4 MB
         * (S2/AP3, gemessen); hundert gleichzeitig sprengten das Budget. So
         * lebt immer nur eine — die vorige ist frei, sobald ihr `<trk>`
         * geschrieben ist. */
        $folge = static function () use ($pdo, $mitSpur, $spurname) {
            foreach ($mitSpur as $e) {
                $punkte = spur_lesen($pdo, $e['art'], $e['id']);
                if (!$punkte) { continue; }
                yield [
                    'punkte'     => $punkte,
                    'name'       => $spurname($e['art'], $e['id'], $e['von'] ?: null),
                    'stufe'      => (int)$e['stand']['stufe'],
                    'n_original' => (int)$e['stand']['n_original'],
                ];
            }
        };

        $datum = (string)$tagDatum;
        $xml = gpx_bauen_viele($folge(),
                               'Diensttag ' . fmt_local($datum . ' 12:00:00', 'd.m.Y'));
        $datei = gpx_dateiname_tag($datum, count($mitSpur), $stufen);
        gpx_ausliefern($xml, $datei);
        exit;
    }

    /* ---- Eine einzelne Spur ---------------------------------------------- */

    $art = (string)($_GET['art'] ?? 'mission');
    $id  = (int)($_GET['id'] ?? 0);
    if (!in_array($art, ['mission', 'rest'], true) || $id <= 0) {
        rate_misserfolg('pair');
        json_out(['error' => 'payload'], 400);
    }

    $tabelle = $art === 'mission' ? 'missions' : 'rest_segments';

    /* DATENTRENNUNG (I3, I4). Ein Eintrag im Papierkorb ist fuer diesen Weg
     * nicht vorhanden — dieselbe Regel wie im Export. Und die Antwort auf
     * „gehoert nicht mir" ist dieselbe wie auf „gibt es nicht": 404. Ein
     * eigener Fehlercode verriete, dass es die Kennung anderswo gibt. */
    $st = $pdo->prepare("SELECT id, started_at FROM `$tabelle`
                          WHERE id = ? AND user_id = ? AND deleted_at IS NULL");
    $st->execute([$id, $userId]);
    $eintrag = $st->fetch(PDO::FETCH_ASSOC);
    if (!$eintrag) {
        /* Auch ein Fehlgriff auf eine fremde Kennung zaehlt: Genau das ist der
         * Weg, auf dem jemand den Bestand eines anderen Kontos abtasten
         * wuerde. */
        rate_misserfolg('pair');
        json_out(['error' => 'not_found'], 404);
    }

    $stand  = spur_stand($pdo, $art, $id);
    $punkte = spur_lesen($pdo, $art, $id);
    if (!$punkte) {
        /* KEIN LEERES GPX. Eine Datei mit null Punkten sieht aus wie eine
         * Spur, die es gibt — und in einem Kartenprogramm wie ein Fehler des
         * Programms. Die Oberflaeche bietet den Abruf gar nicht erst an; wer
         * die Adresse von Hand aufruft, bekommt eine Auskunft. */
        json_out(['error' => 'keine_spur'], 404);
    }

    $name = $spurname($art, $id, ((string)($eintrag['started_at'] ?? '')) ?: null);
    $xml  = gpx_bauen($punkte, $name, $stand['stufe'], $stand['n_original']);
    gpx_ausliefern($xml,
        gpx_dateiname($art, $id, (string)($eintrag['started_at'] ?? ''), $stand['stufe']));
} catch (Throwable $e) {
    error_log('gpx: ' . $e->getMessage());
    json_out(['error' => 'server'], 500);
}
