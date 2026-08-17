<?php
declare(strict_types=1);
/**
 * Einmalige Nachbearbeitung nach dem Umbau auf Diensttage (E24, A12).
 *
 * WARUM ES DIESE SEITE GIBT. Die Migration erledigt den mechanischen Teil, aber
 * zwei Zuordnungen lassen sich nicht ableiten, und Raten waere hier schlimmer
 * als Fragen:
 *
 *   1. STANDORT UND RETTUNGSMITTEL JE DIENSTTAG. Ein Flugtag ohne Verknuepfung
 *      hat keine Art, keine Rollen und keine artabhaengigen Felder (E26). Er
 *      funktioniert so — Zeiten, Phasen, Track und Reanimation sind vollstaendig
 *      —, aber er bleibt unvollstaendig dokumentiert.
 *   2. STANDORTZUORDNUNG DER STAMMDATEN. Der Standortbezug ist verbindlich
 *      (E15), Bestandsdaten haben aber keinen. Hatte eine NutzerIn genau einen
 *      Standort, hat die Migration zugeordnet; bei mehreren oder keinem blieb
 *      `base_id` leer und die Spalte NULLBAR — eine Neuinstallation traegt an
 *      derselben Stelle NOT NULL (Problem P6).
 *
 * DIE SEITE VERSCHWINDET, SOBALD BEIDE LISTEN LEER SIND, und erst dann wird die
 * Bedingung angezogen. Das ist die zweistufige Regel aus A12: Erst wenn keine
 * Zuordnung mehr offen ist, gleichen sich migrierte Datenbank und
 * Neuinstallation vollstaendig.
 *
 * KEINE EIGENE BUCHFUEHRUNG. Ob die Bedingung schon gezogen ist, steht im
 * Schema selbst und wird dort nachgesehen (information_schema). Ein Merker in
 * einer Tabelle koennte davon abweichen — und der Merker waere dann die
 * Auskunft, die man glaubt, waehrend das Schema die ist, die gilt.
 */

require_once __DIR__ . '/db.php';

/** Die fuenf Stammdatentabellen mit Standortbezug (E15) und ihre Beschriftung. */
const NB_STAMMDATEN = [
    'vehicles'        => 'Rettungsmittel',
    'crew_presets'    => 'Besatzungs-Vorbelegungen',
    'transport_dests' => 'Zielkliniken',
    'resources'       => 'Weitere Rettungsmittel',
    'bw_units'        => 'Bergwacht-Bereitschaften',
];

/**
 * Diensttage ohne Standort oder ohne Rettungsmittel.
 *
 * Mit Datum, Zeitraum und Einsatzzahl — ohne die drei Angaben liesse sich nicht
 * entscheiden, welcher Dienst gemeint war. Papierkorb-Eintraege bleiben aussen
 * vor: Sie sind geloescht, und eine Zuordnung an ihnen waere Arbeit fuer nichts.
 */
function nb_offene_tage(int $userId, int $limit = 500): array
{
    $q = db()->prepare('SELECT d.id, d.day, d.started_at, d.ended_at, d.kind,
                               d.base_id, d.vehicle_id, d.base_name, d.vehicle_name,
                               (SELECT COUNT(*) FROM missions m
                                 WHERE m.day_id = d.id AND m.deleted_at IS NULL) AS einsaetze
                          FROM days d
                         WHERE d.user_id = ? AND d.deleted_at IS NULL
                           AND (d.base_id IS NULL OR d.vehicle_id IS NULL)
                         ORDER BY d.day DESC, d.started_at DESC
                         LIMIT ' . (int)$limit);
    $q->execute([$userId]);
    return $q->fetchAll();
}

/**
 * Stammdatensaetze ohne Standortzuordnung, je Tabelle.
 *
 * $zentral === true liefert die zentralen Eintraege (`user_id IS NULL`). Sie
 * gehoeren den Admins; die Seite zeigt sie nur diesen. Solange EIN zentraler
 * Eintrag offen ist, kann die NOT-NULL-Bedingung nicht gezogen werden — auch
 * dann nicht, wenn jede NutzerIn ihre eigenen bereits zugeordnet hat.
 *
 * @return array<string,list<array>>
 */
function nb_offene_stammdaten(int $userId, bool $zentral = false): array
{
    $offen = [];
    foreach (array_keys(NB_STAMMDATEN) as $tabelle) {
        // Die Tabellennamen stammen aus der Konstante oben, nicht aus einer
        // Anfrage. Ein Platzhalter ist fuer Tabellennamen ohnehin nicht moeglich.
        $wo = $zentral ? 'user_id IS NULL' : 'user_id = ?';
        $q  = db()->prepare("SELECT id, name FROM `$tabelle`
                             WHERE base_id IS NULL AND $wo ORDER BY name");
        $q->execute($zentral ? [] : [$userId]);
        $zeilen = $q->fetchAll();
        if ($zeilen) { $offen[$tabelle] = $zeilen; }
    }
    return $offen;
}

/**
 * Zahl der offenen Punkte fuer diese NutzerIn — Grundlage dafuer, ob die Seite
 * ueberhaupt erscheint.
 *
 * Zentrale Stammdaten zaehlen nur fuer Admins mit: Wer sie nicht bearbeiten
 * kann, bekommt sonst einen Hinweis auf eine Aufgabe, die er nicht erledigen
 * kann — und der bliebe dann dauerhaft stehen.
 */
function nb_offen_gesamt(int $userId): int
{
    if (!nb_moeglich()) { return 0; }

    $n = count(nb_offene_tage($userId));
    foreach (nb_offene_stammdaten($userId) as $zeilen) { $n += count($zeilen); }
    if (ist_admin()) {
        foreach (nb_offene_stammdaten($userId, true) as $zeilen) { $n += count($zeilen); }
    }
    return $n;
}

/**
 * Kann diese Installation ueberhaupt offene Zuordnungen haben?
 *
 * Traegt `vehicles.base_id` schon NOT NULL, ist die Nachbearbeitung
 * abgeschlossen oder es war eine Neuinstallation — in beiden Faellen gibt es
 * nichts zu tun, und die Abfragen oben brauchen gar nicht zu laufen.
 *
 * Das Ergebnis wird gemerkt: Es aendert sich innerhalb eines Seitenaufrufs
 * nicht, und die Frage kommt in der Seitenleiste bei JEDEM Aufruf.
 */
function nb_moeglich(): bool
{
    static $moeglich = null;
    if ($moeglich !== null) { return $moeglich; }
    return $moeglich = nb_spalte_nullbar('vehicles');
}

/** Ist `base_id` dieser Tabelle noch nullbar? */
function nb_spalte_nullbar(string $tabelle): bool
{
    $q = db()->prepare("SELECT is_nullable FROM information_schema.columns
                        WHERE table_schema = DATABASE()
                          AND table_name = ? AND column_name = 'base_id'");
    $q->execute([$tabelle]);
    $w = $q->fetchColumn();
    return $w !== false && strtoupper((string)$w) === 'YES';
}

/**
 * Ist bei NIEMANDEM mehr ein Stammdatensatz ohne Standort offen?
 *
 * Bewusst ueber alle Konten hinweg und ohne Nutzerbedingung: Die Bedingung
 * `NOT NULL` gilt fuer die Tabelle, nicht fuer eine Zeilenmenge. Ein einziger
 * offener Eintrag eines anderen Kontos liesse das ALTER TABLE scheitern — und
 * zwar mit einem Datenbankfehler statt mit einer lesbaren Meldung.
 *
 * Die Diensttage zaehlen hier NICHT mit: `days.base_id` und `days.vehicle_id`
 * bleiben dauerhaft nullbar, weil ein neutraler Diensttag der vorgesehene
 * Zustand ist (E26) und die Uhr staendig neue anlegt.
 *
 * @return array<string,int> Tabelle => Zahl offener Zeilen; leer heisst fertig
 */
function nb_stammdaten_offen_gesamt(): array
{
    $offen = [];
    foreach (array_keys(NB_STAMMDATEN) as $tabelle) {
        if (!nb_spalte_nullbar($tabelle)) { continue; }
        $n = (int)db()->query("SELECT COUNT(*) FROM `$tabelle` WHERE base_id IS NULL")
                      ->fetchColumn();
        if ($n > 0) { $offen[$tabelle] = $n; }
    }
    return $offen;
}

/**
 * Die zweite Stufe aus A12: `base_id` auf NOT NULL setzen.
 *
 * Laeuft nur, wenn in KEINER der fuenf Tabellen noch eine Zeile ohne Standort
 * steht. Danach stimmen migrierte Datenbank und Neuinstallation in genau den
 * fuenf Spalten ueberein, in denen sie sich bis dahin unterschieden (P6).
 *
 * Idempotent: Eine Tabelle, deren Spalte schon NOT NULL traegt, wird
 * uebersprungen. Ein ALTER TABLE laeuft ausserhalb einer Transaktion — MySQL
 * kennt kein Zurueckrollen von Schemaaenderungen —, deshalb wird JEDE Tabelle
 * einzeln geaendert und der Erfolg einzeln gemeldet.
 *
 * @return array{ok:bool,meldung:string,geaendert:list<string>}
 */
function nb_notnull_ziehen(): array
{
    $offen = nb_stammdaten_offen_gesamt();
    if ($offen) {
        $teile = [];
        foreach ($offen as $tabelle => $n) {
            $teile[] = $n . '× ' . (NB_STAMMDATEN[$tabelle] ?? $tabelle);
        }
        return ['ok' => false, 'geaendert' => [],
                'meldung' => 'Es sind noch Stammdatensätze ohne Standort offen ('
                           . implode(', ', $teile) . '). Die Bedingung wird erst '
                           . 'gesetzt, wenn keiner mehr offen ist. Es wurde nichts '
                           . 'geändert.'];
    }

    $pdo = db();
    $geaendert = [];
    foreach (array_keys(NB_STAMMDATEN) as $tabelle) {
        if (!nb_spalte_nullbar($tabelle)) { continue; }
        $pdo->exec("ALTER TABLE `$tabelle`
                    MODIFY base_id INT UNSIGNED NOT NULL");
        $geaendert[] = $tabelle;
    }

    return ['ok' => true, 'geaendert' => $geaendert,
            'meldung' => $geaendert
                ? 'Der Standortbezug ist jetzt verbindlich — die Bedingung NOT NULL '
                  . 'steht in ' . count($geaendert) . ' Tabellen. Damit stimmen '
                  . 'aktualisierte Installation und Neuinstallation überein.'
                : 'Die Bedingung stand bereits in allen Tabellen. Es gab nichts zu tun.'];
}
