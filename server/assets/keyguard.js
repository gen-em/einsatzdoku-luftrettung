/* Gen-EM NAdoku — Bindung und Lebensdauer des Inhaltsschlüssels (Baustein B5).
 *
 * WARUM ES DIESEN BAUSTEIN GIBT
 * Ein abgelegter Inhaltsschlüssel darf nur zurückkommen, wenn er zur Hülle
 * gehört, nach der die Seite fragt. Sonst hängt die Richtigkeit allein
 * daran, dass jeder Weg, auf dem das Konto wechseln könnte, vorher aufräumt
 * — vier Stellen taten das, eine nicht, und das reichte: Ein Schlüssel aus
 * Konto A entschlüsselt in Konto B nichts, aber der Fehlschlag sieht dann
 * aus wie „keine Angaben vorhanden".
 *
 * Deshalb trägt der Eintrag in der Ablage neben dem Schlüssel die Kennungen
 * der Hüllen, aus denen er stammt, und einen Zeitstempel:
 *
 *   Kennung passt nicht  -> null, der Eintrag BLEIBT (der Aufrufer fragt nach
 *                           dem Passwort; ein neues Entsperren überschreibt ihn)
 *   zu alt               -> löschen, null
 *
 * WARUM EIN FREMDER EINTRAG NICHT GELÖSCHT WIRD. Alle Tabs teilen sich den
 * Eintrag: Ein Tab, der vor einem
 * Passwortwechsel geladen wurde, trägt die alte Hülle und hielte den
 * gültigen Eintrag für fremd — löschte er ihn, sperrte er alle anderen Tabs
 * gleich mit. Ein Konto, das wirklich wechselt, meldet sich vorher ab und
 * an, und beides räumt die Ablage.
 *
 * Der Eintrag liegt nicht im `sessionStorage` des Tabs, sondern als nicht
 * extrahierbarer `CryptoKey` in IndexedDB (`EdCrypto.inhaltAblegen()`,
 * Begründung und Preis dort). Zwei Folgen für diese Datei:
 *
 *   KEIN STILLES NEU-ENTPACKEN. Der Datenschlüssel (`edk`) liegt nicht
 *   neben dem Inhaltsschlüssel; nach dem Entsperren gibt es ihn nicht mehr.
 *   Ein verworfener Eintrag lässt sich deshalb nicht ohne Passwort aus der
 *   Hülle neu entpacken. Wer ihn braucht, fragt nach dem Passwort.
 *
 *   EIN EINTRAG FÜR ALLE TABS. Ein neuer Tab und ein wiederhergestellter
 *   Browser finden den Schlüssel, solange die Frist läuft.
 *
 * ZUR LEBENSDAUER
 * Die Anmeldung hängt am PHP-Sitzungscookie (30 Minuten Inaktivität). Der
 * Schlüssel hat dieselbe Frist und misst dasselbe: Jeder Gebrauch zählt sie
 * von vorn, nicht absolut ab dem Entsperren. Bewusst gleich und nicht
 * kürzer: Ein Schlüssel, der VOR der
 * Sitzung abläuft, erzeugt einen Entsperrdialog mitten in der Arbeit und
 * keinen Sicherheitsgewinn. Die Gegenrichtung — die Sitzung ebenfalls absolut
 * zu befristen — hätte aktive NutzerInnen mitten in der Arbeit abgemeldet.
 *
 * WER RÄUMT. Die Ablage überlebt das Schließen des Browsers. Darum prüft
 * jede Seite mit dieser Datei beim Laden die Frist und löscht einen
 * abgelaufenen Eintrag, ohne dass jemand nach dem Schlüssel fragt; dazu
 * räumen Abmelden, Ablauf und Anmelden (`EdCrypto.clearSession()`).
 *
 * Erwartet aus der Seite: EdCrypto.
 */
'use strict';
const EdKeyGuard = (() => {

  // Muss zu SESSION_TIMEOUT_S in auth_guard.php passen (siehe Kopf).
  const MAX_ALTER_MS = 1800 * 1000;

  /* Die Frist nicht bei JEDEM Gebrauch schreiben. Eine Liste mit 200
   * Einsätzen fragt 200-mal; ein Schreibvorgang in IndexedDB je Frage wäre
   * Arbeit ohne Wirkung. Eine Minute Unschärfe an einer Frist von 30 ist
   * keine. */
  const ANFASSEN_ALLE_MS = 60 * 1000;

  function frisch(eintrag) {
    return eintrag && eintrag.zeit > 0 && (Date.now() - eintrag.zeit) < MAX_ALTER_MS;
  }

  /**
   * Liefert den Inhaltsschlüssel (`CryptoKey`) zur übergebenen Hülle — oder
   * null. Ein Eintrag, der nicht zu dieser Hülle gehört oder zu alt ist, wird
   * gelöscht, nicht weitergereicht.
   */
  async function contentKey(wrap) {
    if (!wrap) { return null; }
    const eintrag = await EdCrypto.inhaltLesen();
    if (!eintrag) { return null; }
    const erwartet = await EdCrypto.wrapFingerprint(wrap);
    if (!frisch(eintrag)) { await EdCrypto.inhaltLoeschen(); return null; }
    if (eintrag.bindungen.indexOf(erwartet) < 0) { return null; }
    // Benutzt heißt in Gebrauch: Die Frist zählt ab jetzt neu.
    if (Date.now() - eintrag.zeit > ANFASSEN_ALLE_MS) { await EdCrypto.inhaltAnfassen(eintrag); }
    return eintrag.schluessel;
  }

  /**
   * Einen eben entpackten Inhaltsschlüssel (Hex) ablegen und an seine
   * Hüllen binden. Liefert den `CryptoKey`.
   *
   * @param {string}          ckHex
   * @param {string|string[]} huellen  die Hülle(n), zu denen er gehört —
   *        nach einer stillen Umstellung die alte der Seite UND die neue in
   *        der Datenbank, sonst hielte die nächste Seite den Schlüssel für
   *        fremd und fragte nach dem Passwort
   */
  async function ablegen(ckHex, huellen) {
    const liste = Array.isArray(huellen) ? huellen : [huellen];
    const bindungen = [];
    for (const h of liste) { if (h) { bindungen.push(await EdCrypto.wrapFingerprint(h)); } }
    return EdCrypto.inhaltAblegen(ckHex, bindungen);
  }

  /** Alles räumen. Liefert ein Versprechen; wer danach die Seite wechselt,
   *  wartet darauf. */
  function beenden() { return EdCrypto.clearSession(); }

  /* Beim Laden: einen abgelaufenen Eintrag löschen, auch wenn diese Seite nie
   * nach dem Schlüssel fragt (siehe Kopf, „Wer räumt"). */
  (async () => {
    try {
      const eintrag = await EdCrypto.inhaltLesen();
      if (eintrag && !frisch(eintrag)) { await EdCrypto.inhaltLoeschen(); }
    } catch (e) { /* keine Ablage */ }
  })();

  return { contentKey, ablegen, beenden, MAX_ALTER_MS };
})();
