/* Gen-EM NAdoku — Chiffretexte unter dem Inhaltsschlüssel, Fassung 2 (`edk2:`).
 *
 * ZWECK DIESER DATEI. Sie schreibt und liest den GEBUNDENEN Chiffretext und
 * die gebundenen Schlüsselhüllen. Format und Prüfvektoren stehen in
 * `tools/proben/huelle/referenz_inhalt.py` und nur dort; die Hüllenprobe
 * (`tools/proben/huelle/probe.mjs`) hält diese Datei byte-gleich dagegen.
 *
 * WAS DAS PROBLEM WAR (Audit KA, F-KA-04, Nr. 563). Ein Chiffretext der
 * Fassung 1 (`edk1:`, bis Web 26.x in crypto.js) war an nichts gebunden: AES-GCM ohne
 * Zusatzdaten. Wer die Datenbank beschreiben kann, hängt den Patientenblock
 * eines Einsatzes an einen anderen, legt `kp_privat` an die Stelle von
 * `rw_privat` oder spielt den Block von gestern zurück — und alles geht
 * auf, ohne Fehler. Dazu verriet die Länge, wie viel in einem Block steht
 * (F-KA-30).
 *
 * WAS FASSUNG 2 TUT.
 *
 *     "edk2:" ‖ Base64( NONCE 12 ‖ CT ‖ TAG 16 )          AES-256-GCM
 *     Zusatzdaten = "edk2" ‖ Feld(Zweck) ‖ Feld(Bezug)
 *                   Feld = 2 Byte Länge (big-endian) ‖ UTF-8
 *     Innen       = Z (uint32) ‖ L (uint32) ‖ Nutzlast (L Byte) ‖ 0x00 …
 *                   aufgefüllt bis zur Größenklasse
 *
 *     Zweck     Bezug                     Z
 *     pat       client_ref des Einsatzes  Zähler des Blocks (standbuch.js)
 *     kp        leer                      0   privater Teil des Kontopaars
 *     rw        leer                      0   privater Teil des Rückwegs
 *     wrap      "rc"                      0   Inhaltsschlüssel unter dem
 *                                             Wiederherstellungsschlüssel
 *               "pw:<Anteil-Kennung>"     0   unter dem Datenschlüssel; steht
 *                                             als "edka2:<Kennung>:<Base64>"
 *               "pw"                      0   ohne Anteil (Demo-Konto)
 *     stand     Nummer des Blatts         Version des Blatts
 *     geraete   leer                      Version des Gerätebuchs
 *
 * Ein Block an der falschen Stelle bricht am Prüfwert (`pruefwert`). Gegen
 * das Zurücklegen steht der Zähler Z; ihn hält `standbuch.js` gegen einen
 * Stand, den der Server nicht fälschen kann.
 *
 * DER RÜCKWEG BLEIBT FREI VON SERVER UND ANTEIL. `kp`, `rw` und `wrap`/"rc"
 * brauchen zum Öffnen nur ihren Schlüssel und feste Zeichenketten. Eine
 * `edka2:`-Hülle für "rc" baut diese Datei nicht und öffnet sie nicht
 * (`kennung`) — sie wäre der Verlust genau dieses Rückwegs (CLAUDE.md 4).
 *
 * FASSUNG 1 IST NICHT MEHR LESBAR (seit Web 27.0.0, KA-07): `lesen()` und
 * `huelleOeffnen()` weisen alles ohne `edk2:`/`edka2:` mit `kennung` ab.
 *
 * FEHLER tragen `.grund`, in dieser Reihenfolge geprüft:
 *   zweck      der Zweck steht nicht in der Liste
 *   kennung    keine oder eine andere Kennung, als verlangt ist
 *   form       kein Base64, zu kurz, keine Größenklasse, unter der
 *              Mindestklasse
 *   pruefwert  falscher Schlüssel, falsche Stelle oder verändert
 *   polster    Länge oder Auffüllung innen stimmen nicht
 *   innen      die Nutzlast ist nicht, was der Zweck verlangt
 *
 * Braucht: EdCrypto (crypto.js), davor geladen. Legt `EdInhalt` an.
 */
'use strict';
const EdInhalt = (() => {

  /* DIE WEICHE IST ZURÜCKGEBAUT (Web 26.0.0, 06e, KA-20). Von Web 24.13 bis
   * 24.19 stand hier `SCHREIBT_FASSUNG_2 = false`, mit Web 25.0.0 wurde sie
   * umgelegt (KA-14). Seitdem schreibt jeder Weg Fassung 2; seit Web 27.0.0
   * (KA-07) liest auch niemand mehr das alte Format (`edk1:`, `edka1:`, Text
   * ohne Kennung) — auf keiner Anlage liegt noch eines (E-KA-104). Die Begründung,
   * warum es eine Konstante im Skript war und kein Wert vom Server, steht in
   * der Git-Geschichte dieser Datei. */

  const T = EdCrypto.teile;
  const te = new TextEncoder();
  const KENNUNG = 'edk2:';
  const HUELLE_KENNUNG = 'edka2:';
  const ZWECKE = ['pat', 'kp', 'rw', 'wrap', 'stand', 'geraete'];
  const KLASSEN = [128, 256, 512, 1024, 2048, 4096];
  /* Ein Patientenblock hat mindestens 512 Byte innen: Ein fast leerer sieht
   * aus wie ein gewöhnlicher (F-KA-30). */
  const MINDESTKLASSE = { pat: 512 };
  const NONCE = 12, TAG = 16, KOPF = 8;

  function fehler(grund) {
    const e = new Error('Chiffretext geht nicht auf: ' + grund);
    e.name = 'InhaltFehler';
    e.grund = grund;
    return e;
  }

  /** 2 Byte Länge (big-endian) ‖ UTF-8 — wie die Bindung der Gerätehülle. */
  function feld(text) {
    const b = te.encode(String(text));
    if (b.length > 0xffff) { throw fehler('form'); }
    return T.ghVerbinde(new Uint8Array([b.length >> 8, b.length & 0xff]), b);
  }

  function zusatzdaten(zweck, bezug) {
    if (ZWECKE.indexOf(zweck) < 0) { throw fehler('zweck'); }
    return T.ghVerbinde(te.encode('edk2'), feld(zweck), feld(bezug == null ? '' : bezug));
  }

  /** Die Größenklasse für eine Innenlänge von `n` Byte. */
  function klasse(n, zweck) {
    n = Math.max(n, MINDESTKLASSE[zweck] || KLASSEN[0]);
    for (const k of KLASSEN) { if (n <= k) { return k; } }
    return Math.ceil(n / 4096) * 4096;
  }

  function istKlasse(n) {
    return KLASSEN.indexOf(n) >= 0 || (n > 4096 && n % 4096 === 0);
  }

  /* STRENGES BASE64. `atob()` verzeiht Leerraum und fehlende Füllzeichen;
   * die Referenz (`b64decode(validate=True)`) tut es nicht. Ein Text, den
   * zwei Umsetzungen verschieden lesen, ist eine Stelle, an der die
   * Formprüfung des Servers und das Öffnen im Browser auseinandergehen. */
  const B64 = /^(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)?$/;

  function roh(text, kennung) {
    if (typeof text !== 'string' || !text.startsWith(kennung)) { throw fehler('kennung'); }
    const b = text.slice(kennung.length);
    if (!B64.test(b)) { throw fehler('form'); }
    return T.fromB64(b);
  }

  function uint32(dv, o, wert) {
    if (!Number.isInteger(wert) || wert < 0 || wert > 0xffffffff) { throw fehler('form'); }
    dv.setUint32(o, wert);
  }

  /**
   * Verschließen, Fassung 2. `nutzlast` als Bytes.
   * @param schluessel  CryptoKey (der Inhaltsschlüssel) oder Hex
   * @param {number} z  der Zähler; 0, wo der Zweck keinen führt
   * @param fest        NUR für Prüfvektoren: { nonce: Uint8Array(12) }
   * @returns {Promise<string>} "edk2:" + Base64
   */
  async function verschliessenBytes(schluessel, zweck, bezug, nutzlast, z, fest) {
    const aad = zusatzdaten(zweck, bezug);
    const innen = new Uint8Array(klasse(KOPF + nutzlast.length, zweck));
    const dv = new DataView(innen.buffer);
    uint32(dv, 0, z || 0);
    uint32(dv, 4, nutzlast.length);
    innen.set(nutzlast, KOPF);
    const nonce = fest ? fest.nonce : crypto.getRandomValues(new Uint8Array(NONCE));
    const key = await T.aesSchluessel(schluessel, ['encrypt']);
    const ct = new Uint8Array(await crypto.subtle.encrypt(
      { name: 'AES-GCM', iv: nonce, additionalData: aad }, key, innen));
    return KENNUNG + T.toB64(T.ghVerbinde(nonce, ct));
  }

  /**
   * Öffnen, Fassung 2. Reihenfolge: Zweck, Kennung, Form, Prüfwert, Polster.
   * @returns {Promise<{nutzlast: Uint8Array, z: number}>}
   */
  async function oeffnenBytes(schluessel, text, zweck, bezug) {
    const aad = zusatzdaten(zweck, bezug);
    const r = roh(text, KENNUNG);
    const n = r.length - NONCE - TAG;
    if (r.length < NONCE + TAG || !istKlasse(n) || n < (MINDESTKLASSE[zweck] || 0)) {
      throw fehler('form');
    }
    const key = await T.aesSchluessel(schluessel, ['decrypt']);
    let innen;
    try {
      innen = new Uint8Array(await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: r.slice(0, NONCE), additionalData: aad }, key, r.slice(NONCE)));
    } catch (e) { throw fehler('pruefwert'); }
    const dv = new DataView(innen.buffer);
    const z = dv.getUint32(0), l = dv.getUint32(4);
    if (l > innen.length - KOPF) { throw fehler('polster'); }
    for (let i = KOPF + l; i < innen.length; i++) {
      if (innen[i] !== 0) { throw fehler('polster'); }
    }
    return { nutzlast: innen.slice(KOPF, KOPF + l), z: z };
  }

  /** UTF-8 streng: Eine Nutzlast, die kein Text ist, ist `innen`. */
  function alsText(bytes) {
    try { return new TextDecoder('utf-8', { fatal: true }).decode(bytes); }
    catch (e) { throw fehler('innen'); }
  }

  const istGebunden = text => typeof text === 'string' && text.startsWith(KENNUNG);

  /**
   * LESEN — der Weg für `kp`, `rw` und jeden Text unter dem Inhaltsschlüssel,
   * der keinen Zähler führt. Ein Patientenblock geht über `EdStandbuch`
   * (`patLesen()`), weil erst das Standbuch sagt, ob er zurückgelegt wurde.
   * Ein Text ohne `edk2:` ist `kennung` — das alte Format liest seit Web
   * 27.0.0 niemand mehr.
   * @returns {Promise<{text: string, z: number, gebunden: boolean}>}
   */
  async function lesen(schluessel, text, zweck, bezug) {
    if (!istGebunden(text)) { zusatzdaten(zweck, bezug); throw fehler('kennung'); }
    const o = await oeffnenBytes(schluessel, text, zweck, bezug);
    return { text: alsText(o.nutzlast), z: o.z, gebunden: true };
  }

  /**
   * SCHREIBEN — immer Fassung 2 (`edk2:`).
   * @returns {Promise<string>}
   */
  async function schreiben(schluessel, zweck, bezug, klartext, z) {
    return verschliessenBytes(schluessel, zweck, bezug, te.encode(klartext), z || 0);
  }

  /* ---- Schlüsselhüllen (Zweck `wrap`) ----------------------------------
   *
   * Zwei Arten: "rc" (unter dem Wiederherstellungsschlüssel) und "pw" (unter
   * dem Datenschlüssel). Nur "pw" kennt eine Anteil-Kennung; sie steht im
   * Präfix `edka2:<Kennung>:` UND in den Zusatzdaten. In Fassung 1 stand sie
   * nur im Präfix: Wer es umschrieb, bekam die Meldung „Anteil fehlt" statt
   * eines Fehlers am Chiffretext.
   *
   * DIE KENNUNG HAT EINE FORM (acht Hexzeichen, wie `huelleKennung()` in
   * crypto.js). Die Referenz nimmt jede Zeichenkette; hier ist die Form
   * Pflicht, weil eine leere Kennung sonst dieselben Zusatzdaten ergäbe wie
   * „ohne Anteil" und `edka2::…` als Demo-Hülle aufginge. */
  const KENNUNG_FORM = /^[0-9a-f]{8}$/;
  const CK_FORM = /^[0-9a-f]{64}$/;

  function huelleArt(art) {
    if (art !== 'rc' && art !== 'pw') { throw fehler('zweck'); }
  }

  /**
   * Eine Hülle der Fassung 2 bauen — unabhängig von der Weiche; über sie
   * entscheidet der Aufrufer (`EdCrypto.huelleBauen()` für "pw",
   * `huelleSchreiben()` unten für beide).
   * @param schluesselHex  Daten- oder Wiederherstellungsschlüssel
   * @param {string} art   'rc' | 'pw'
   * @param {string|null} kennung  Anteil-Kennung, nur bei 'pw'
   */
  async function huelleBauen(schluesselHex, ckHex, art, kennung, fest) {
    huelleArt(art);
    if (!CK_FORM.test(String(ckHex))) { throw fehler('innen'); }
    if (kennung != null && kennung !== '') {
      if (art === 'rc' || !KENNUNG_FORM.test(kennung)) { throw fehler('kennung'); }
      const text = await verschliessenBytes(schluesselHex, 'wrap', 'pw:' + kennung,
                                            te.encode(ckHex), 0, fest);
      return HUELLE_KENNUNG + kennung + ':' + text.slice(KENNUNG.length);
    }
    return verschliessenBytes(schluesselHex, 'wrap', art, te.encode(ckHex), 0, fest);
  }

  /**
   * Eine Hülle öffnen (`edk2:`, `edka2:<k>:`). Liefert den Inhaltsschlüssel (Hex).
   * Den Schlüssel zur Anteil-Kennung hat der Aufrufer schon gebildet
   * (`EdCrypto.datenschluessel()`); hier wird sie nur gebunden geprüft.
   */
  async function huelleOeffnen(schluesselHex, text, art) {
    huelleArt(art);
    text = String(text == null ? '' : text);
    let ck;
    if (text.startsWith(HUELLE_KENNUNG)) {
      if (art === 'rc') { throw fehler('kennung'); }   // der Rückweg hängt nie am Anteil
      const ende = text.indexOf(':', HUELLE_KENNUNG.length);
      const kennung = ende < 0 ? '' : text.slice(HUELLE_KENNUNG.length, ende);
      if (!KENNUNG_FORM.test(kennung)) { throw fehler('kennung'); }
      ck = (await oeffnenBytes(schluesselHex, KENNUNG + text.slice(ende + 1),
                               'wrap', 'pw:' + kennung)).nutzlast;
    } else if (text.startsWith(KENNUNG)) {
      ck = (await oeffnenBytes(schluesselHex, text, 'wrap', art)).nutzlast;
    } else {
      throw fehler('kennung');               // erste Fassung: seit Web 27.0.0 nicht mehr
    }
    const hex = alsText(ck);
    if (!CK_FORM.test(hex)) { throw fehler('innen'); }
    return hex;
  }

  /** Eine Hülle SCHREIBEN — gebunden, für beide Arten. */
  async function huelleSchreiben(schluesselHex, ckHex, art, kennung) {
    huelleArt(art);
    if (art === 'rc' && kennung) { throw fehler('kennung'); }
    return huelleBauen(schluesselHex, ckHex, art, kennung || null);
  }

  return { lesen, schreiben, istGebunden,
           huelleBauen, huelleOeffnen, huelleSchreiben,
           /* die Bausteine einzeln — für standbuch.js und die Probe */
           verschliessenBytes, oeffnenBytes, alsText,
           zusatzdaten, klasse, istKlasse, fehler,
           KENNUNG, HUELLE_KENNUNG };
})();
