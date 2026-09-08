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
 *
 * SEIT WEB 16.0.0 GEHOERT `vehicles` NICHT MEHR ZUR ZWEITEN STUFE (E-S9-09).
 * `vehicles.base_id` ist wieder NULLBAR, und zwar dauerhaft und mit Absicht:
 * Die Typen ausser 'standard' brauchen keinen Standort. Die Auskunft
 * „Bedingung steht im Schema" gilt fuer diese eine Tabelle damit nicht mehr —
 * sie wuerde das Gegenteil dessen sagen, was sie sagen soll.
 *
 * DAS WAR EIN BEINAHE-SCHADEN, und er steht hier, damit ihn niemand
 * wiederholt: `nb_moeglich()` hing ALLEIN an der Nullbarkeit von
 * `vehicles.base_id`. Mit der Migration `2026_09_07_rettungsmittel_typ`
 * sprang die Auskunft in JEDER Installation von „abgeschlossen" zurueck auf
 * „offen" — die laengst erledigte Seite waere in der Seitenleiste
 * wiederauferstanden, haette die standortlosen Rettungsmittel als offene
 * Punkte gemeldet, und ihr Knopf haette mit `ALTER TABLE vehicles MODIFY
 * base_id ... NOT NULL` die Migration rueckgaengig gemacht. Gemessen am
 * 07.09.2026 an der laufenden Installation: `nb_moeglich()` false -> true,
 * `nb_stammdaten_offen_gesamt()` {"vehicles":2}.
 *
 * DIE LOESUNG IN ZWEI TEILEN: Die zweite Stufe (`NB_NOTNULL`) kennt nur noch
 * die VIER uebrigen Tabellen, und `nb_moeglich()` fragt sie. Gefunden werden
 * Rettungsmittel ohne Standort weiterhin — aber nur die des Typs 'standard',
 * denn nur bei ihnen ist der fehlende Standort ein Mangel.
 */

require_once __DIR__ . '/db.php';

/**
 * Die fuenf Stammdatentabellen mit Standortbezug (E15) und ihre Beschriftung.
 * Sie werden ANGEZEIGT, wenn eine Zeile ohne Standort offen ist.
 */
const NB_STAMMDATEN = [
    'vehicles'        => 'Rettungsmittel',
    'crew_presets'    => 'Besatzungs-Vorbelegungen',
    'transport_dests' => 'Zielkliniken',
    'resources'       => 'Weitere Rettungsmittel',
    'bw_units'        => 'Bergwacht-Bereitschaften',
];

/**
 * Die Tabellen, deren `base_id` am Ende NOT NULL tragen soll — die zweite
 * Stufe aus A12.
 *
 * SEIT WEB 16.0.0 SIND ES VIER, NICHT FUENF. `vehicles` fehlt hier bewusst:
 * Seine Spalte ist dauerhaft nullbar (E-S9-09), und ein `ALTER TABLE ... NOT
 * NULL` darauf naehme genau das zurueck, was die Migration
 * `2026_09_07_rettungsmittel_typ` hergestellt hat. Die ausfuehrliche
 * Begruendung steht im Kopf dieser Datei.
 */
const NB_NOTNULL = ['crew_presets', 'transport_dests', 'resources', 'bw_units'];

/**
 * Diensttage ohne Standort oder ohne Rettungsmittel.
 *
 * Mit Datum, Zeitraum und Einsatzzahl — ohne die drei Angaben liesse sich nicht
 * entscheiden, welcher Dienst gemeint war. Papierkorb-Eintraege bleiben aussen
 * vor: Sie sind geloescht, und eine Zuordnung an ihnen waere Arbeit fuer nichts.
 *
 * EIN TAG OHNE STANDORT IST NICHT MEHR ZWANGSLAEUFIG OFFEN (E-S9-09,
 * Web 16.0.0). Traegt er ein Rettungsmittel eines Typs ohne Standortpflicht —
 * Bergwacht, Veranstaltung, Sonstiges —, dann IST er vollstaendig zugeordnet;
 * ein Standort fehlt ihm nicht, es gibt keinen. Ohne diese Bedingung stuende er
 * dauerhaft in der Liste und im Zaehler „Zuordnung offen" der Seitenleiste, und
 * niemand koennte ihn dort abarbeiten: Die Seite boete an, einen Standort zu
 * waehlen, den das Rettungsmittel gar nicht haben soll.
 *
 * `vehicle_typ IS NULL` zaehlt weiter als offen: Das sind Bestandstage, deren
 * Rettungsmittel geloescht wurde oder die nie eines hatten — dort ist der
 * fehlende Standort tatsaechlich eine Luecke.
 */
function nb_offene_tage(int $userId, int $limit = 500): array
{
    $ohneStandort = [];
    foreach (VEHICLE_TYPEN as $typ => $regeln) {
        if (!$regeln['standort']) { $ohneStandort[] = $typ; }
    }
    $platzhalter = implode(',', array_fill(0, count($ohneStandort), '?'));

    $q = db()->prepare('SELECT d.id, d.day, d.started_at, d.ended_at, d.kind,
                               d.base_id, d.vehicle_id, d.base_name, d.vehicle_name,
                               d.vehicle_typ, d.vehicle_kurz,
                               (SELECT COUNT(*) FROM missions m
                                 WHERE m.day_id = d.id AND m.deleted_at IS NULL) AS einsaetze
                          FROM days d
                         WHERE d.user_id = ? AND d.deleted_at IS NULL
                           AND (d.vehicle_id IS NULL
                                OR (d.base_id IS NULL
                                    AND (d.vehicle_typ IS NULL
                                         OR d.vehicle_typ NOT IN (' . $platzhalter . '))))
                         ORDER BY d.day DESC, d.started_at DESC
                         LIMIT ' . (int)$limit);
    $q->execute(array_merge([$userId], $ohneStandort)); return $q->fetchAll();
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
        /* BEI RETTUNGSMITTELN IST DER FEHLENDE STANDORT NUR BEIM TYP 'standard'
         * EIN MANGEL (E-S9-09, Web 16.0.0). Bergwacht, Veranstaltung und
         * Sonstiges duerfen ohne bestehen; sie hier zu melden hiesse, eine
         * Aufgabe zu stellen, die niemand erledigen kann und die nie kleiner
         * wird. Offen bleibt der Fall, den A12 hinterlassen haben kann: ein
         * Bestandsrettungsmittel ohne Standort, das die Migration auf
         * 'standard' gesetzt hat. */
        $nur = $tabelle === 'vehicles' ? " AND typ = 'standard'" : '';
        $q  = db()->prepare("SELECT id, name FROM `$tabelle`
                             WHERE base_id IS NULL AND $wo$nur ORDER BY name");
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
 * Tragen die vier Tabellen der zweiten Stufe schon NOT NULL, ist die
 * Nachbearbeitung abgeschlossen oder es war eine Neuinstallation — in beiden
 * Faellen gibt es nichts zu tun, und die Abfragen oben brauchen gar nicht zu
 * laufen.
 *
 * GEFRAGT WIRD `NB_NOTNULL`, NICHT `vehicles` (Web 16.0.0). Bis Web 15.9.0
 * hing die ganze Auskunft an der Nullbarkeit von `vehicles.base_id` — was
 * richtig war, solange diese Spalte nur waehrend der Nachbearbeitung nullbar
 * sein konnte. Seit E-S9-09 ist sie es dauerhaft, und die Frage haette
 * dauerhaft „ja" geantwortet. Die vier uebrigen Tabellen beantworten sie
 * weiterhin richtig: Stehen sie auf NOT NULL, ist A12 durch — und ein
 * Standard-Rettungsmittel ohne Standort kann seither nicht mehr entstehen,
 * weil `pruef_rettungsmittel()` es auf allen drei Schreibwegen ablehnt.
 *
 * Das Ergebnis wird gemerkt: Es aendert sich innerhalb eines Seitenaufrufs
 * nicht, und die Frage kommt in der Seitenleiste bei JEDEM Aufruf.
 */
function nb_moeglich(): bool
{
    static $moeglich = null;
    if ($moeglich !== null) { return $moeglich; }
    foreach (NB_NOTNULL as $tabelle) {
        if (nb_spalte_nullbar($tabelle)) { return $moeglich = true; }
    }
    return $moeglich = false;
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
 * Gefragt werden die vier Tabellen von `NB_NOTNULL`, nicht die fuenf der
 * Anzeige: `vehicles` bekommt die Bedingung nicht mehr, also ist dort auch
 * nichts zu zaehlen.
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
    foreach (NB_NOTNULL as $tabelle) {
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
 * Laeuft nur, wenn in KEINER der vier Tabellen noch eine Zeile ohne Standort
 * steht. Danach stimmen migrierte Datenbank und Neuinstallation in genau den
 * vier Spalten ueberein, in denen sie sich bis dahin unterschieden (P6).
 * `vehicles` ist seit Web 16.0.0 nicht mehr dabei — dort ist die Nullbarkeit
 * der gewollte Endzustand (E-S9-09).
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
    foreach (NB_NOTNULL as $tabelle) {
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
