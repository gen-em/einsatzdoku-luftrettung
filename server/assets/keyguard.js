/* Einsatzdoku — Bindung und Lebensdauer des Inhaltsschlüssels (Baustein B5).
 *
 * WARUM ES DIESEN BAUSTEIN GIBT
 * Der Zwischenspeicher liefert den Inhaltsschlüssel heute zurück, OHNE zu
 * prüfen, ob er überhaupt zur übergebenen Hülle gehört. Die Richtigkeit hängt
 * damit allein daran, dass jeder Weg, auf dem das Konto wechseln könnte,
 * vorher aufräumt. Vier Stellen tun das, eine nicht — und das reicht: Ein
 * Schlüssel aus Konto A entschlüsselt in Konto B nichts, aber der Fehlschlag
 * sieht dann aus wie „keine Angaben vorhanden".
 *
 * Statt fünf Aufrufer zur Disziplin zu erziehen, korrigiert sich der
 * Zwischenspeicher hier selbst: Er merkt sich neben dem Schlüssel eine kurze
 * Kennung der Hülle, aus der er stammt, und einen Zeitstempel.
 *
 *   Kennung passt nicht  -> verwerfen und neu entpacken
 *   zu alt               -> verwerfen
 *
 * ZUR LEBENSDAUER
 * Die Anmeldung hängt am PHP-Sitzungscookie (30 Minuten Inaktivität), der
 * Inhaltsschlüssel dagegen am sessionStorage des Tabs. Beide Lebensdauern
 * liefen bisher auseinander. Hier laufen sie zusammen: Nach derselben Frist
 * ist auch der Schlüssel weg.
 *
 * Erwartet aus der Seite: EdCrypto.
 *
 * Diese Datei ändert von sich aus nichts — sie wird von den Anzeigeseiten
 * schrittweise anstelle des direkten EdCrypto.getContentKey() verwendet.
 */
'use strict';
const EdKeyGuard = (() => {

  const S_BIND = 'pckb';                 // Kennung der Hülle, aus der CK stammt
  const S_TIME = 'pckt';                 // Zeitpunkt des Entpackens (ms)

  // Muss zu SESSION_TIMEOUT_S in auth_guard.php passen. Bewusst gleich und
  // nicht kürzer: Ein Schlüssel, der VOR der Sitzung abläuft, erzeugt einen
  // Entsperrdialog mitten in der Arbeit und keinen Sicherheitsgewinn.
  const MAX_ALTER_MS = 1800 * 1000;

  function ablegen(bindung) {
    try {
      sessionStorage.setItem(S_BIND, bindung || '');
      sessionStorage.setItem(S_TIME, String(Date.now()));
    } catch (e) { /* Speicher nicht verfügbar — dann gilt der Schlüssel als ungebunden */ }
  }

  /**
   * Verwirft NUR den zwischengespeicherten Inhaltsschlüssel samt Bindung.
   *
   * Der Datenschlüssel bleibt bewusst liegen: Er gehört zur laufenden
   * Anmeldung und wird gebraucht, um die Hülle gleich neu zu entpacken. Ihn
   * hier mitzuräumen war ein Fehler — die Folge war, dass ein nicht passender
   * Zwischenspeicher nicht zu einem neuen Entpacken führte, sondern zu gar
   * keinem Schlüssel.
   */
  function verwerfeInhalt() {
    try {
      sessionStorage.removeItem('pck');
      sessionStorage.removeItem(S_BIND);
      sessionStorage.removeItem(S_TIME);
    } catch (e) { /* Speicher nicht verfügbar */ }
  }

  /** Alles räumen — Daten- UND Inhaltsschlüssel. Nur beim Sitzungsende. */
  function raeumen() {
    verwerfeInhalt();
    EdCrypto.clearSession();
  }

  /** Ist der abgelegte Schlüssel noch jung genug? */
  function frisch() {
    const t = parseInt(sessionStorage.getItem(S_TIME) || '0', 10);
    return t > 0 && (Date.now() - t) < MAX_ALTER_MS;
  }

  /**
   * Liefert den Inhaltsschlüssel zur übergebenen Hülle — oder null.
   *
   * Anders als EdCrypto.getContentKey() prüft diese Fassung, dass der
   * zwischengespeicherte Schlüssel auch WIRKLICH zu dieser Hülle gehört, und
   * verwirft ihn sonst. Ein Kontowechsel im selben Tab kann damit keinen
   * fremden Schlüssel mehr durchreichen.
   */
  async function contentKey(wrap) {
    if (!wrap) { return null; }
    const erwartet = await EdCrypto.wrapFingerprint(wrap);

    const vorhanden = sessionStorage.getItem('pck');
    if (vorhanden) {
      const gebunden = sessionStorage.getItem(S_BIND);
      if (gebunden === erwartet && frisch()) { return vorhanden; }
      // Passt nicht oder zu alt: verwerfen statt weiterreichen. NUR den
      // Inhaltsschlüssel — der Datenschlüssel wird gleich zum Entpacken
      // gebraucht.
      verwerfeInhalt();
    }

    const ck = await EdCrypto.getContentKey(wrap);
    if (ck) { ablegen(erwartet); }
    return ck;
  }

  /**
   * Nach einem frisch entpackten Schlüssel aufrufen (z. B. aus dem
   * Entsperrdialog), damit die Bindung gesetzt ist.
   */
  async function binden(wrap) {
    ablegen(await EdCrypto.wrapFingerprint(wrap));
  }

  /** Alles räumen — Daten- und Inhaltsschlüssel samt Bindung. */
  function beenden() { raeumen(); }

  return { contentKey, binden, beenden, MAX_ALTER_MS };
})();
