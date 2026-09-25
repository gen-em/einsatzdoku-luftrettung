<?php
declare(strict_types=1);

/**
 * DIE TEILE DER ZWEITFAKTOR-EINRICHTUNG (P5c/AP5, M-P5c-02b) — zwei
 * Verwender: das Einrichtungstor `zweitfaktor.php` (Anmeldehülle, Bild 4)
 * und die Karte „Zweitfaktor" unter Einstellungen → Profil (Bild 2).
 *
 * EINE STELLE, DAMIT TOR UND KARTE NICHT AUSEINANDERLAUFEN — dieselbe
 * Überlegung wie bei `schluessel_teile.php`. Wer die Einrichtung einmal im
 * Tor und einmal im Profil sieht, soll dieselben drei Wege in die App
 * wiedererkennen: scannen, am Handy öffnen, abtippen.
 *
 * WAS DER AUFRUFER SELBST MACHT: das Formular darum, die Knöpfe darunter.
 * Im Tor ist es ein breiter Knopf „Einschalten", in der Karte stehen
 * „Einschalten" und „Abbrechen" nebeneinander (Mockup, Bild 2 a).
 *
 * ZWEI SKRIPTE GEHÖREN DAZU: `assets/vendor/qrcode.js` und `assets/qr.js`
 * (QR-Code), dazu `assets/zweitfaktor.js` (Haken vor „Weiter"). Der
 * Aufrufer nimmt sie in `ui_seite_ende(['skripte' => …])` mit;
 * `ZF_SKRIPTE` nennt sie einmal.
 */

require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/totp_lib.php';
require_once __DIR__ . '/instanz_lib.php';

const ZF_SKRIPTE = ['assets/vendor/qrcode.js', 'assets/qr.js', 'assets/zweitfaktor.js'];

/**
 * Schritt 1 „In der App hinzufügen" und das Codefeld von Schritt 2.
 *
 * `$roh` ist das Geheimnis (20 Byte), `$konto` die Adresse des Kontos — sie
 * steht in der App neben dem Namen der Installation.
 */
function zf_einrichtung(string $roh, string $konto): void
{
    $adresse = totp_otpauth($roh, $konto, instanz_kurz());
    ?>
    <h3 class="listen-form-titel">1 · In der App hinzufügen</h3>
    <div class="zweitfaktor-einrichtung">
      <?php /* DER QR-CODE ENTSTEHT IM BROWSER (assets/qr.js): Der Server gibt
               ein leeres, verborgenes SVG mit der Adresse aus. Ohne
               JavaScript bleibt es verborgen — die beiden Wege daneben
               brauchen keins. */ ?>
      <svg class="qr" role="img" aria-label="QR-Code für die Authenticator-App"
           shape-rendering="crispEdges" data-qr="<?= ui_e($adresse) ?>" hidden></svg>
      <div>
        <p>Mit der Authenticator-App den Code scannen — oder am Handy direkt öffnen:</p>
        <p><?= ui_knopf(['text' => 'In der Authenticator-App öffnen', 'art' => 'neutral',
                         'href' => $adresse]) ?></p>
        <div class="codeblock">
          <p class="codeblock-titel">Oder von Hand eintragen</p>
          <p class="codeblock-wert"><?= ui_e(totp_base32_gruppen($roh)) ?></p>
          <p class="feld-klein">Zeitbasiert · <?= TOTP_STELLEN ?> Ziffern · <?= TOTP_SCHRITT_S ?> Sekunden</p>
        </div>
      </div>
    </div>
    <h3 class="listen-form-titel">2 · Code bestätigen</h3>
    <?php ui_feld(['name' => 'code', 'id' => 'f-totp', 'label' => 'Code aus der App',
                   'klasse' => 'feld-code', 'pflicht' => true,
                   'attr' => ' inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]{6,7}"']);
}

/**
 * Die zehn Wiederherstellungscodes, einmal sichtbar — mit dem Haken
 * „gesichert" und dem Druckknopf fürs Codeblatt.
 *
 * DAS DRUCKFORMULAR STEHT IM CODEBLOCK und trägt die Codes in versteckten
 * Feldern zu `codeblatt.php` — POST und neues Fenster, wie beim
 * Notfallblatt: Die Codes gehören in keine Adresszeile, und die Seite mit
 * den Codes soll stehen bleiben. Der Codeblock darf deshalb in keinem
 * anderen Formular stehen; verschachtelte Formulare gibt es in HTML nicht.
 *
 * @param list<string> $codes
 */
function zf_codes(array $codes): void
{
    ?>
    <div class="codeblock">
      <p class="codeblock-titel">Deine zehn Wiederherstellungscodes — nur jetzt sichtbar</p>
      <ol class="codeblock-liste"><?php foreach ($codes as $c): ?><li><?= ui_e(totp_code_anzeige($c)) ?></li><?php endforeach; ?></ol>
      <p class="feld-klein">Jeder Code gilt einmal, wenn das Handy fehlt.</p>
      <label><input type="checkbox" data-zf-gesichert> Ich habe die Codes gesichert.</label>
      <form method="post" action="codeblatt.php" target="_blank" rel="noopener" class="rf-druck">
        <?php foreach ($codes as $c): ?><input type="hidden" name="codes[]" value="<?= ui_e($c) ?>"><?php endforeach; ?>
        <?= ui_knopf(['text' => 'Codeblatt drucken', 'art' => 'neutral', 'symbol' => 'drucken']) ?>
      </form>
    </div>
    <?php
}
