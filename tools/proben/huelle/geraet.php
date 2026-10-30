<?php
/* Die Gerätehilfe der Proben — senden wie ein Gerät.
 *
 * Anlass: Nr. 43 — seit S11 nimmt `ingest.php` Spur, Phasenkoordinaten und
 * Reanimation nur noch verschlossen an. Die Proben, die den Eingang wie ein
 * Gerät bedienen, schrieben ihre Pakete im alten Format; diese Datei
 * verwandelt ein solches Paket in das neue, an EINER Stelle, statt dass jede
 * Probe ihr eigenes Verschließen baut.
 *
 *   gh_probe_paar()          ein Schlüsselpaar für die Probe (privat, SEC1)
 *   gh_probe_koerper()       altes Paket -> neues: `track.points` wird ein
 *                            verschlossenes Stück, Koordinaten der Phasen und
 *                            die Reanimation wandern in `ort_huelle`
 *   gh_probe_oeffnen()       eine Hülle öffnen — NUR HIER, im Prüfwerkzeug.
 *                            Der Server hat diese Funktion mit Absicht nicht
 *                            (`server/geraetehuelle_lib.php`).
 *   gh_probe_einsatz()       die Einsatzhülle eines Einsatzes öffnen und als
 *                            Feld zurückgeben
 *
 * DIE INNENFORM DER EINSATZHÜLLE steht im JSON-Vertrag 1b und hier
 * als Code: {"fassung":1, "phasen":[{"phase","t","lat","lon"}],
 * "rea":[{"t","ereignisse":[{"art","t"}]}]} — alle Zeiten in Sekunden seit
 * dem Einsatzbeginn, damit ein Datumswechsel des Diensttags die
 * Hülle nicht ungültig macht.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../server/geraetehuelle_lib.php';

/** @return array{privat:mixed, sec1:string} */
function gh_probe_paar(): array
{
    $k = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    return ['privat' => $k, 'sec1' => gh_sec1_von($k)];
}

function gh_probe_iso_zu_s(string $iso): int
{
    return (int)strtotime($iso);
}

/**
 * Ein Paket im alten Format so umbauen, wie ein Gerät sendet.
 * Ohne Punkte fällt `track` weg; ohne Koordinaten und Reanimation entsteht
 * trotzdem eine Einsatzhülle, sobald Phasen dabei sind — das tut auch das
 * Gerät, damit die Hülle zu den Phasen passt.
 */
function gh_probe_koerper(array $k, string $sec1): array
{
    $ref = (string)($k['client_ref'] ?? '');
    $art = ($k['kind'] ?? '') === 'rest_segment' ? 'ruhe' : 'spur';
    if (isset($k['track']) && is_array($k['track']) && array_key_exists('points', $k['track'])) {
        $pts = $k['track']['points'];
        $seq = (int)($k['track']['seq_from'] ?? 0);
        if (!$pts) {
            unset($k['track']);
        } else {
            $ts = array_map(fn($p) => (int)$p[3], $pts);
            $von = min($ts);
            $n = count($pts);
            $k['track'] = ['seq_from' => $seq, 'punkte' => $n, 'ts_von' => $von, 'ts_bis' => max($ts),
                           'huelle' => base64_encode(gh_verschliessen(gh_spur_innen($pts, $von), $sec1,
                                                                     $art, $ref, $seq . ':' . $n))];
        }
    }
    if (($k['kind'] ?? '') === 'mission') {
        $beginn = gh_probe_iso_zu_s((string)($k['started_at'] ?? 'now'));
        $phasen = [];
        $hatPhasen = isset($k['phases']) && is_array($k['phases']);
        foreach ($hatPhasen ? $k['phases'] : [] as $i => $p) {
            $phasen[] = ['phase' => (int)($p['phase'] ?? 0),
                         't' => gh_probe_iso_zu_s((string)($p['at'] ?? '')) - $beginn,
                         'lat' => $p['lat'] ?? null, 'lon' => $p['lon'] ?? null];
            unset($k['phases'][$i]['lat'], $k['phases'][$i]['lon']);
        }
        $sitzungen = $k['resus_sessions'] ?? (!empty($k['resus']) ? [$k['resus']] : []);
        $rea = [];
        foreach ($sitzungen as $s) {
            $ev = [];
            foreach ($s['events'] ?? [] as $e) {
                $ev[] = ['art' => (string)($e['type'] ?? ''), 't' => gh_probe_iso_zu_s((string)($e['at'] ?? '')) - $beginn];
            }
            $rea[] = ['t' => gh_probe_iso_zu_s((string)($s['started_at'] ?? '')) - $beginn, 'ereignisse' => $ev];
        }
        unset($k['resus_sessions'], $k['resus']);
        if ($hatPhasen || $rea) {
            $innen = json_encode(['fassung' => 1, 'phasen' => $phasen, 'rea' => $rea],
                                 JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $k['ort_huelle'] = base64_encode(gh_verschliessen($innen, $sec1, 'einsatz', $ref, ''));
        }
    }
    return $k;
}

/** Eine Hülle öffnen (Fassung 1). Wirft mit dem Grund wie `referenz.py`. */
function gh_probe_oeffnen(string $h, $privat, string $art, string $ref, string $platz = ''): string
{
    if (strlen($h) < GH_KOPF + 16 + GH_TAG || (strlen($h) - GH_KOPF - GH_TAG) % 16) {
        throw new RuntimeException('laenge');
    }
    if (ord($h[0]) !== GH_FASSUNG) { throw new RuntimeException('fassung'); }
    $epk = substr($h, 1, 65);
    $iv = substr($h, 66, 16);
    $ct = substr($h, GH_KOPF, -GH_TAG);
    $tag = substr($h, -GH_TAG);
    $z = openssl_pkey_derive(gh_oeffentlich_laden($epk), $privat, 32);
    if ($z === false) { throw new RuntimeException('schluessel'); }
    $empf = gh_sec1_von($privat);
    $okm = hash_hkdf('sha256', $z, 64, GH_INFO . $empf, $epk);
    $soll = hash_hmac('sha256', substr($h, 0, -GH_TAG) . gh_bindung($art, $ref, $platz), substr($okm, 32), true);
    if (!hash_equals($soll, $tag)) { throw new RuntimeException('pruefwert'); }
    $klar = openssl_decrypt($ct, 'aes-256-cbc', substr($okm, 0, 32), OPENSSL_RAW_DATA, $iv);
    if ($klar === false) { throw new RuntimeException('polsterung'); }
    return $klar;
}

/** Die Einsatzhülle eines Einsatzes, geöffnet — oder null, wenn keine dasteht. */
function gh_probe_einsatz(PDO $pdo, int $missionId, $privat): ?array
{
    $q = $pdo->prepare('SELECT client_ref, ort_huelle FROM missions WHERE id = ?');
    $q->execute([$missionId]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    if (!$r || $r['ort_huelle'] === null) { return null; }
    return json_decode(gh_probe_oeffnen((string)$r['ort_huelle'], $privat, 'einsatz', (string)$r['client_ref']), true);
}
