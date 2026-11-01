/**
 * Das Schlüsselpaar des Kontos — im Browser. Server: `kontopaar_lib.php`,
 * `api/kontopaar.php`.
 *
 * ===========================================================================
 * WAS HIER ENTSTEHT
 * ===========================================================================
 *
 * Ein ECDH-Paar auf P-256, über WebCrypto:
 *
 *   öffentlicher Teil  SPKI, Base64            → users.kp_oeffentlich
 *                                                (der Server versiegelt ihn)
 *   privater Teil      PKCS8, Base64, verpackt → users.kp_privat
 *                      mit dem INHALTSSCHLÜSSEL (`EdCrypto.encrypt(ck, …)`,
 *                      Kennung `edk1:`) — nicht `huelleBauen()`: Er muss mit
 *                      dem Wiederherstellungsschlüssel allein aufgehen.
 *
 * Uhr und Handy bekommen den öffentlichen Teil bei der Kopplung und
 * verschließen damit Spur, Phasenkoordinaten und Reanimation
 * (`EdCrypto.geraetehuelleVerschliessen`); geöffnet wird mit dem privaten.
 *
 * ===========================================================================
 * DREI AUFRUFER, alle aus `unlock.js`, sobald der Inhaltsschlüssel da ist
 * ===========================================================================
 *
 *   anlegen(ck, token)  still, wenn das Konto keins hat (`KP_STAND ===
 *                       'fehlt'`). Das Token ist der Passwortnachweis aus dem
 *                       Vormerkfach der Anmeldung — es gibt ihn nur dort,
 *                       also entsteht das Paar beim ersten Entsperren nach
 *                       einer Anmeldung. Wirft nie.
 *   abgleichen(ck)      bei jedem Entsperren, wenn eins da ist: Leitet aus
 *                       dem privaten Teil den öffentlichen ab und vergleicht
 *                       ihn mit dem, den der Server ausliefert (zweiter
 *                       Riegel). Weicht er ab, sagt es die Seite, und
 *                       der Server meldet es (Reiter System) — EINMAL je
 *                       Seite.
 *   privat(ck)          der private Teil als CryptoKey (nicht extrahierbar),
 *                       zum Öffnen der Gerätehüllen. Lebt so
 *                       lange wie die Seite und damit nie länger als der
 *                       Inhaltsschlüssel, aus dem er kommt.
 *
 * Erwartet: EdCrypto (crypto.js), EdApi (api.js), EdHtml (html.js) — zur
 * AUFRUFZEIT gelesen; die Konstanten KP_STAND, KP_OEFFENTLICH, KP_PRIVAT
 * aus `ui_krypto_bootstrap()`.
 */
(function () {
  'use strict';

  let privatSchluessel = null;
  let abgeglichen = false;
  /* DAS PAAR DIESER SEITE, WENN ES NACH IHREM AUFBAU ENTSTAND.
     KP_OEFFENTLICH und KP_PRIVAT sind Konstanten vom Aufbau der
     Seite; ein Paar, das danach angelegt wird — hier oder auf einer Seite, die
     kurz vorher geladen war —, kennen sie nicht. `neu` hält es fest, `laufend`
     das Anlegen, auf das `bereit()` wartet. */
  let neu = null;
  let laufend = null;

  function base64(puffer) {
    const b = new Uint8Array(puffer);
    let s = '';
    for (let i = 0; i < b.length; i++) { s += String.fromCharCode(b[i]); }
    return btoa(s);
  }

  function b64urlZuBytes(t) {
    const s = atob(t.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice((t.length + 3) % 4));
    const out = new Uint8Array(s.length);
    for (let i = 0; i < s.length; i++) { out[i] = s.charCodeAt(i); }
    return out;
  }

  /** Ein neues Paar, der private Teil mit `ck` verpackt. */
  async function paarErzeugen(ck) {
    const kp = await crypto.subtle.generateKey({ name: 'ECDH', namedCurve: 'P-256' },
                                               true, ['deriveBits']);
    const spki  = base64(await crypto.subtle.exportKey('spki', kp.publicKey));
    const pkcs8 = base64(await crypto.subtle.exportKey('pkcs8', kp.privateKey));
    return { oeffentlich: spki, privat: await EdCrypto.encrypt(ck, pkcs8) };
  }

  /** Still: das Paar anlegen. Liefert true, wenn eines abgelegt ist. */
  function anlegen(ck, token) {
    laufend = (async () => {
      try {
        const paar = await paarErzeugen(ck);
        const antw = await EdApi.postJson('api/kontopaar.php', {
          aktion: 'anlegen', token: token, oeffentlich: paar.oeffentlich, privat: paar.privat,
        });
        if (antw.ok) { neu = paar; }
        return antw.ok;
      } catch (e) {
        return false;   // bewusst still — der nächste Versuch kommt mit der nächsten Anmeldung
      }
    })();
    return laufend;
  }

  /**
   * Wartet, bis die Seite den Schlüssel kennt, wenn es ihn gibt: auf ein
   * laufendes Anlegen, und — kennt die Seite danach noch
   * keinen, obwohl sie beim Aufbau „fehlt" hieß — fragt den Server, ob
   * inzwischen eines da ist (die Anmeldung legt es auf der ersten Seite an,
   * und eine zweite kann vorher geladen worden sein). Wirft nie.
   */
  async function bereit() {
    if (laufend) { await laufend; }
    if (spki() || typeof KP_STAND === 'undefined' || KP_STAND !== 'fehlt') { return; }
    try {
      const a = await EdApi.postJson('api/kontopaar.php', { aktion: 'zustand' });
      if (a.ok && a.daten && a.daten.stand === 'da' && a.daten.oeffentlich) {
        neu = { oeffentlich: a.daten.oeffentlich, privat: a.daten.privat };
      }
    } catch (e) { /* bleibt ohne — die Aufrufer melden „kein Schlüsselpaar" */ }
  }

  /** Der öffentliche Teil als SPKI (Base64): vom Aufbau der Seite oder neu. */
  function spki() {
    if (typeof KP_OEFFENTLICH !== 'undefined' && KP_OEFFENTLICH) { return KP_OEFFENTLICH; }
    return neu ? neu.oeffentlich : null;
  }

  /** Der private Teil als PKCS8-Bytes — oder null, wenn er nicht aufgeht. */
  async function pkcs8(ck) {
    const chiffre = (typeof KP_PRIVAT !== 'undefined' && KP_PRIVAT) ? KP_PRIVAT : (neu ? neu.privat : null);
    if (!chiffre) { return null; }
    try {
      const der = await EdCrypto.decrypt(ck, chiffre);
      return Uint8Array.from(atob(der), c => c.charCodeAt(0));
    } catch (e) { return null; }
  }

  /**
   * Der private Teil als CryptoKey (ECDH, nicht extrahierbar) — oder null.
   * @param {string} ck  der Inhaltsschlüssel (hex)
   */
  async function privat(ck) {
    if (privatSchluessel) { return privatSchluessel; }
    const der = await pkcs8(ck);
    if (!der) { return null; }
    privatSchluessel = await crypto.subtle.importKey('pkcs8', der,
      { name: 'ECDH', namedCurve: 'P-256' }, false, ['deriveBits']);
    return privatSchluessel;
  }

  /** Der öffentliche Teil, wie der Server ihn ausliefert, als SEC1 — oder null. */
  function oeffentlich() {
    const s = spki();
    return s ? EdCrypto.spkiZuSec1(s) : null;
  }

  /**
   * Passt der private Teil zum ausgelieferten öffentlichen? Liefert true,
   * false (Abweichung — gemeldet) oder null (nichts zu prüfen).
   */
  async function abgleichen(ck) {
    if (abgeglichen || typeof KP_STAND === 'undefined' || KP_STAND !== 'da') { return null; }
    abgeglichen = true;
    const soll = oeffentlich();
    const der = await pkcs8(ck);
    let passt = false;
    if (soll && der) {
      try {
        const k = await crypto.subtle.importKey('pkcs8', der,
          { name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveBits']);
        const jwk = await crypto.subtle.exportKey('jwk', k);
        const ist = new Uint8Array(65);
        ist[0] = 4;
        ist.set(b64urlZuBytes(jwk.x), 1);
        ist.set(b64urlZuBytes(jwk.y), 33);
        passt = ist.every((b, i) => b === soll[i]);
      } catch (e) { passt = false; }
    }
    if (!passt) { abweichungMelden(); }
    return passt;
  }

  /* DIE ABWEICHUNG SAGT DIE SEITE — oben im Inhalt, mit dem vorhandenen
   * Meldungsbaustein — und der Server meldet sie. Weiterarbeiten geht; was
   * fehlt, ist das Vertrauen in den Schlüssel, den die Geräte bekommen. */
  function abweichungMelden() {
    const ort = document.querySelector('main');
    if (ort && typeof EdHtml !== 'undefined') {   // `const` in html.js: global, aber keine Eigenschaft von window
      const box = document.createElement('div');
      box.dataset.kontopaar = 'abweichung';
      box.innerHTML = EdHtml.meldung('fehler',
        'Der Schlüssel, mit dem Uhr und Handy für dieses Konto verschlüsseln, passt nicht zu '
        + 'deinem privaten Schlüssel. Bitte keine neuen Geräte koppeln und die BetreiberIn '
        + 'informieren — die Abweichung ist gemeldet.');
      ort.insertBefore(box, ort.firstChild);
    }
    EdApi.postJson('api/kontopaar.php', { aktion: 'abweichung' }).catch(() => {});
  }

  window.EdKontopaar = { anlegen: anlegen, abgleichen: abgleichen, privat: privat,
                         oeffentlich: oeffentlich, paarErzeugen: paarErzeugen, bereit: bereit };
})();
