/* Gen-EM NAdoku — Standbuch und Gerätebuch.
 *
 * ZWECK DIESER DATEI. Das Standbuch ist der Zählerstand, gegen den der
 * Browser einen Patientenblock, eine Einsatzhülle und eine Spur hält, um zu
 * merken, dass jemand einen älteren Stand zurückgelegt hat. Das Gerätebuch
 * führt die Geräteschlüssel, die das Konto bei der Kopplung bestätigt hat.
 * Beide liegen unter dem Inhaltsschlüssel (`edk2:`, inhalt2.js). Format und
 * Prüfvektoren: `tools/proben/huelle/referenz_inhalt.py`.
 *
 * WAS DAS PROBLEM WAR (F-KA-04). Die Bindung allein (inhalt2.js) verhindert
 * das Vertauschen, nicht das Zurücklegen: Der Block von gestern gehört an
 * dieselbe Stelle wie der von heute. Dagegen hilft nur ein Stand, den der
 * Server nicht fälschen kann — und die erste Entwurfsfrage war, wo er liegt
 * (KA-M5, 11.11.2026): IM BROWSER (IndexedDB, je Konto, übersteht das
 * Abmelden); der Server hält eine verschlossene Abschrift in 16 Blättern,
 * damit der nächste Browser nicht bei null anfängt.
 *
 *     Hash = SHA-256("nadoku-stand-1" ‖ client_ref)
 *     Blatt eines Datensatzes = Hash[0] >> 4     (fest, nicht vom Server)
 *     Kennung im Blatt        = Base64url(Hash[1:10]), 12 Zeichen
 *     Blatt  = edk2, Zweck "stand", Bezug = Blattnummer, Z = seine Version
 *              { "fassung": 1, "t": <Unix>, "zu": false, "e": { "<Kennung>": {…} } }
 *     Eintrag (jedes Feld darf fehlen):
 *       p  Zähler des Patientenblocks
 *       o  Zähler der zuletzt abgenommenen Einsatzhülle;  k: 1 = vom Konto
 *       s  [gedeckt bis, Stufe] der abgenommenen Spur
 *       d  Versatz in Sekunden (Umdatierung): Klartextzeit − Zeit in der Hülle
 *       x  Löschvermerke [[von, bis], …] in der Zeit der Hülle
 *     zu   der Anhebelauf ist durch: Fassung 1 in diesem Blatt ist ein Befund
 *     Gerätebuch = edk2, Zweck "geraete", Bezug leer, Z = seine Version
 *              { "fassung": 1, "geraete": [ { sid, spk, geraet, seit, bis } ] }
 *
 * GRÜNDE BEIM LESEN (`.grund`, wie inhalt2.js):
 *   blatt-zurueckgelegt  der Server liefert ein Blatt mit kleinerer Version,
 *                        als dieser Browser schon gesehen hat (der Anker)
 *   buch-zurueckgelegt   dasselbe am Gerätebuch
 *   zurueckgelegt        Z des Blocks < p — oder Fassung 1, wo schon ein
 *                        Zähler steht
 *   altformat            Fassung 1 in einem Blatt mit "zu"
 *   Z > p oder kein Eintrag ist KEIN Fehler: Das Blatt hinkt (ein verlorener
 *   Schreibvorgang), der Browser trägt nach. Fälschen kann das niemand — ein
 *   höheres Z entsteht nur mit dem Schlüssel.
 *
 * WO ES NICHT HÄLT. Ein Browser ohne Anker (neu, Speicher geleert) glaubt
 * der Abschrift des Servers — einmal. Und was ein ANDERER Browser
 * geschrieben hat, kennt dieser erst, wenn er dessen Blatt bekommt.
 *
 * ========================================================================
 * DIE SCHNITTSTELLE FÜR DEN TRANSPORT DER BLÄTTER
 * ========================================================================
 *
 * Diese Datei spricht den Server nur über einen ADAPTER an — drei
 * Funktionen. `apiTransport()` ist der für die Anwendung,
 * `speicherTransport()` einer ohne Server (Hüllenprobe). `wer` ist die
 * Blattnummer 0…15 oder 'buch' (das Gerätebuch).
 *
 *   stand()                → { blaetter: [16 Zahlen], buch: Zahl }
 *       Die Version je Blatt, wie der Server sie führt; 0 = gibt es nicht.
 *       Nur ein Hinweis, ob sich das Holen lohnt: Die Wahrheit ist das Z im
 *       Blatt, und das prüft der Browser.
 *   holen(wer)             → { version, text } | null
 *   ablegen(wer, { version_alt, version, text })
 *                          → { ok: true }
 *                          → { ok: false, konflikt: true, version, text }
 *       Der Server nimmt an, wenn er `version_alt` führt (0 = noch keins)
 *       UND `version` > `version_alt`. Sonst Konflikt, mit SEINEM Blatt.
 *       Alles andere (Netz, 400, 500) WIRFT.
 *
 * DER ENDPUNKT, den `apiTransport()` erwartet (zu bauen, KA-06d) —
 * `api/standbuch.php`, POST, JSON, CSRF wie überall:
 *
 *   { "aktion": "stand" }
 *       200 { "blaetter": [v0, …, v15], "buch": v }
 *   { "aktion": "holen", "wer": 0…15 | "buch" }
 *       200 { "version": n, "text": "edk2:…" }   bzw.   { "version": 0, "text": null }
 *   { "aktion": "ablegen", "wer": …, "version_alt": n, "version": m, "text": "edk2:…" }
 *       200 { "ok": true }
 *       409 { "error": "konflikt", "version": n, "text": "edk2:…" | null }
 *       400 { "error": "form" }     wer, Zahlen oder Text haben nicht die Form
 *   Der Server prüft nur FORM und GRÖSSE (`edk2:` + strenges Base64, Länge
 *   12 + Größenklasse + 16) und die Versionsregel — atomar, in EINER
 *   Anweisung (`UPDATE … WHERE version = :alt`), sonst gewinnen zwei Tabs
 *   beide. Er kann nicht prüfen, ob `version` das Z im Blatt ist; das tut
 *   der lesende Browser (`blatt-version`).
 *
 * MIT DEM DATENSATZ IN EINER ANFRAGE. Wer einen Block speichert, schickt
 * sein Blatt mit (`vormerken()` unten liefert `{ wer, version_alt, version,
 * text }`); der Server schreibt beide in einer Transaktion oder keins und
 * antwortet bei einem Konflikt am Blatt mit 409 und seinem Blatt.
 *
 * Braucht: EdCrypto (crypto.js), EdInhalt (inhalt2.js); `geraetEintragen()`
 * dazu EdHuelle2 (huelle2.js); `apiTransport()` EdApi (api.js) — zur
 * Aufrufzeit gelesen. Legt `EdStandbuch` an.
 */
'use strict';
const EdStandbuch = (() => {

  const T = EdCrypto.teile;
  const te = new TextEncoder();
  const fehler = EdInhalt.fehler;
  const STAND_INFO = te.encode('nadoku-stand-1');
  const BLAETTER = 16;
  const BUCH = 'buch';

  /* ---- Blatt und Kennung eines Datensatzes ----------------------------- */

  async function hash(ref) {
    return new Uint8Array(await crypto.subtle.digest('SHA-256',
      T.ghVerbinde(STAND_INFO, te.encode(String(ref)))));
  }
  const nrAus = h => h[0] >> 4;
  const kennungAus = h => btoa(String.fromCharCode(...h.slice(1, 10)))
    .replace(/\+/g, '-').replace(/\//g, '_');

  /** In welchem Blatt steht dieser Datensatz? 0…15 — fest, nicht vom Server. */
  async function blattNr(ref) { return nrAus(await hash(ref)); }
  /** Unter welcher Kennung steht er dort? 12 Zeichen. */
  async function kennung(ref) { return kennungAus(await hash(ref)); }

  /* ---- Blatt: bauen, öffnen -------------------------------------------- */

  /* Ein Eintrag in fester Feldfolge und ohne leere Felder: Zwei Browser, die
   * dasselbe wissen, schreiben dieselben Bytes. */
  const FELDER = ['p', 'o', 'k', 's', 'd', 'x'];
  function eintragOrdnen(e) {
    const aus = {};
    for (const f of FELDER) {
      const w = e[f];
      if (w == null || (f === 'x' && !w.length) || (f !== 's' && f !== 'x' && w === 0)) { continue; }
      aus[f] = w;
    }
    return aus;
  }

  const json = d => te.encode(JSON.stringify(d));

  /**
   * Ein Blatt verschließen — immer Fassung 2 (ein Standbuch der Fassung 1
   * gibt es nicht). Die Einträge gehen, wie sie kommen: Wer byte-gleich
   * gegen die Referenz baut, gibt sie in deren Folge.
   * @param o  { zu: boolean, fest: { nonce } }
   */
  function blattBauen(schluessel, nr, version, t, eintraege, o) {
    o = o || {};
    return EdInhalt.verschliessenBytes(schluessel, 'stand', String(nr),
      json({ fassung: 1, t: t, zu: !!o.zu, e: eintraege }), version, o.fest);
  }

  function ausJson(bytes) {
    try { return JSON.parse(EdInhalt.alsText(bytes)); }
    catch (e) { throw fehler('innen'); }
  }
  const istObjekt = d => d !== null && typeof d === 'object' && !Array.isArray(d);

  /**
   * Ein Blatt öffnen. `anker`: die höchste Version, die dieser Browser von
   * diesem Blatt gesehen hat, oder null.
   * @returns {Promise<{blatt: object, version: number}>}
   */
  async function blattOeffnen(schluessel, text, nr, anker) {
    const o = await EdInhalt.oeffnenBytes(schluessel, text, 'stand', String(nr));
    if (anker != null && o.z < anker) { throw fehler('blatt-zurueckgelegt'); }
    const d = ausJson(o.nutzlast);
    if (!istObjekt(d) || d.fassung !== 1 || !istObjekt(d.e)) { throw fehler('innen'); }
    return { blatt: d, version: o.z };
  }

  /**
   * EINEN PATIENTENBLOCK GEGEN SEIN (schon geöffnetes) BLATT LESEN — beide
   * Fassungen. `blatt` darf null sein (es gibt noch keins).
   * @returns {Promise<{nutzlast: Uint8Array, z: number, gebunden: boolean,
   *                    blatt_hinkt: boolean}>}
   *   `gebunden: false`  Fassung 1 — Altbestand, an nichts gebunden
   *   `blatt_hinkt`      der Block ist weiter als das Blatt: nachtragen
   */
  async function patLesen(schluessel, text, ref, blatt) {
    const eintrag = ((blatt && blatt.e) || {})[await kennung(ref)] || {};
    const stand = eintrag.p || 0;
    if (!EdInhalt.istGebunden(text)) {
      const klar = await EdInhalt.oeffnen1Bytes(schluessel, text);
      if (stand) { throw fehler('zurueckgelegt'); }        // Fassung 1, wo schon Fassung 2 lag
      if (blatt && blatt.zu) { throw fehler('altformat'); }
      return { nutzlast: klar, z: 0, gebunden: false, blatt_hinkt: false };
    }
    const o = await EdInhalt.oeffnenBytes(schluessel, text, 'pat', ref);
    if (o.z < stand) { throw fehler('zurueckgelegt'); }
    return { nutzlast: o.nutzlast, z: o.z, gebunden: true, blatt_hinkt: o.z > stand };
  }

  /* ---- Gerätebuch: bauen, öffnen --------------------------------------- */

  function geraetebuchBauen(schluessel, version, geraete, fest) {
    return EdInhalt.verschliessenBytes(schluessel, 'geraete', '',
      json({ fassung: 1, geraete: geraete }), version, fest);
  }

  const SID_FORM = /^[0-9a-f]{16}$/;

  /**
   * Das Gerätebuch öffnen.
   * @returns {Promise<{geraete: object, liste: Array, version: number}>}
   *   `geraete` in der Form, die `EdHuelle2.leser()` nimmt:
   *   { <sid hex>: { spk: Uint8Array(65), bis: number|null, geraet, seit } };
   *   `liste` wie gespeichert (zum Fortschreiben).
   */
  async function geraetebuchOeffnen(schluessel, text, anker) {
    const o = await EdInhalt.oeffnenBytes(schluessel, text, 'geraete', '');
    if (anker != null && o.z < anker) { throw fehler('buch-zurueckgelegt'); }
    const d = ausJson(o.nutzlast);
    if (!istObjekt(d) || d.fassung !== 1 || !Array.isArray(d.geraete)) { throw fehler('innen'); }
    const geraete = {};
    for (const g of d.geraete) {
      let spk = null;
      try { spk = istObjekt(g) && typeof g.spk === 'string' ? T.fromB64(g.spk) : null; } catch (e) { spk = null; }
      if (!spk || spk.length !== 65 || spk[0] !== 4 || !SID_FORM.test(g.sid)
          || !(g.bis == null || Number.isSafeInteger(g.bis))) { throw fehler('innen'); }
      geraete[g.sid] = { spk: spk, bis: g.bis == null ? null : g.bis, geraet: g.geraet, seit: g.seit };
    }
    return { geraete: geraete, liste: d.geraete, version: o.z };
  }

  /* ====================================================================
   * DIE ABLAGE IM BROWSER — je Konto, übersteht das Abmelden
   * ====================================================================
   *
   * EINE EIGENE DATENBANK, nicht das Fach des Inhaltsschlüssels
   * (`nadoku-schluessel`, crypto.js): Das wird beim Abmelden und beim
   * Anmelden geräumt, und ein Anker, der das Abmelden nicht übersteht,
   * schützt genau einen Arbeitstag.
   *
   * WAS DARIN LIEGT: je Blatt `{ version, text, offen }` — der Anker als
   * Zahl, der Stand VERSCHLOSSEN (`edk2:`, wie auf dem Server), und ob
   * dieser Browser mehr weiß als die Abschrift. Im Klartext liegt hier
   * nichts als die Versionsnummer.
   *
   * OHNE INDEXEDDB (gesperrter Speicher, manche privaten Fenster) lebt die
   * Ablage im Speicher der Seite: Jede Seite fängt dann ohne Anker an und
   * glaubt der Abschrift — der Schutz gegen das Zurücklegen ist dort so
   * schwach wie in einem neuen Browser, und `ablage().dauerhaft` sagt es. */
  const DB = 'nadoku-stand', FACH = 'blatt';
  let dbVersprechen = null;

  function dbOeffnen() {
    if (dbVersprechen) { return dbVersprechen; }
    dbVersprechen = new Promise((gut, schlecht) => {
      let anfrage;
      try { anfrage = indexedDB.open(DB, 1); } catch (e) { schlecht(e); return; }
      anfrage.onupgradeneeded = () => { anfrage.result.createObjectStore(FACH); };
      anfrage.onsuccess = () => gut(anfrage.result);
      anfrage.onerror = () => schlecht(anfrage.error);
      anfrage.onblocked = () => schlecht(new Error('Ablage blockiert'));
    }).catch(e => { dbVersprechen = null; throw e; });
    return dbVersprechen;
  }

  async function dbVorgang(art, arbeit) {
    const db = await dbOeffnen();
    return new Promise((gut, schlecht) => {
      const tx = db.transaction(FACH, art);
      const anfrage = arbeit(tx.objectStore(FACH));
      tx.oncomplete = () => gut(anfrage ? anfrage.result : undefined);
      tx.onerror = () => schlecht(tx.error);
      tx.onabort = () => schlecht(tx.error || new Error('Ablage abgebrochen'));
    });
  }

  /** Die Ablage: `lesen(id)`, `schreiben(id, wert)`, `loeschen(id)`, `dauerhaft`. */
  function ablage() {
    const speicher = new Map();
    const a = {
      dauerhaft: true,
      async lesen(id) {
        if (!a.dauerhaft) { return speicher.get(id) || null; }
        try { return (await dbVorgang('readonly', f => f.get(id))) || null; }
        catch (e) { a.dauerhaft = false; return speicher.get(id) || null; }
      },
      async schreiben(id, wert) {
        speicher.set(id, wert);
        if (!a.dauerhaft) { return; }
        try { await dbVorgang('readwrite', f => f.put(wert, id)); }
        catch (e) { a.dauerhaft = false; }
      },
      async loeschen(id) {
        speicher.delete(id);
        if (!a.dauerhaft) { return; }
        try { await dbVorgang('readwrite', f => f.delete(id)); } catch (e) { a.dauerhaft = false; }
      },
    };
    return a;
  }

  /* ---- Die Adapter für den Transport ----------------------------------- */

  /** Ein Server im Speicher — für die Probe. Dieselbe Versionsregel. */
  function speicherTransport() {
    const lager = new Map();
    const v = wer => (lager.has(String(wer)) ? lager.get(String(wer)).version : 0);
    return {
      lager: lager,
      async stand() {
        const blaetter = [];
        for (let i = 0; i < BLAETTER; i++) { blaetter.push(v(i)); }
        return { blaetter: blaetter, buch: v(BUCH) };
      },
      async holen(wer) {
        const b = lager.get(String(wer));
        return b ? { version: b.version, text: b.text } : null;
      },
      async ablegen(wer, b) {
        if (v(wer) !== b.version_alt || !(b.version > b.version_alt)) {
          const da = lager.get(String(wer));
          return { ok: false, konflikt: true, version: v(wer), text: da ? da.text : null };
        }
        lager.set(String(wer), { version: b.version, text: b.text });
        return { ok: true };
      },
    };
  }

  /** Der Adapter für `api/standbuch.php` (Schnittstelle im Kopf). */
  function apiTransport(url) {
    url = url || 'api/standbuch.php';
    const ruf = async (nutzlast) => {
      const a = await EdApi.postJson(url, nutzlast);
      if (!a.ok && !(a.status === 409 && a.daten)) {
        const e = new Error('Standbuch: ' + (a.meldung || ('HTTP ' + a.status)));
        e.status = a.status;
        throw e;
      }
      return a;
    };
    return {
      async stand() {
        const d = (await ruf({ aktion: 'stand' })).daten;
        if (!Array.isArray(d.blaetter) || d.blaetter.length !== BLAETTER) { throw fehler('form'); }
        return { blaetter: d.blaetter.map(Number), buch: Number(d.buch) || 0 };
      },
      async holen(wer) {
        const d = (await ruf({ aktion: 'holen', wer: wer })).daten;
        return d.text ? { version: Number(d.version), text: d.text } : null;
      },
      async ablegen(wer, b) {
        const a = await ruf({ aktion: 'ablegen', wer: wer, version_alt: b.version_alt,
                              version: b.version, text: b.text });
        if (a.ok) { return { ok: true }; }
        return { ok: false, konflikt: true, version: Number(a.daten.version) || 0, text: a.daten.text || null };
      },
    };
  }

  /* ====================================================================
   * DAS BUCH EINES KONTOS — Stand lesen, vergleichen, fortschreiben
   * ====================================================================
   *
   * `oeffnen({ konto, schluessel, transport })` liefert das Buch.
   *   konto       eine Kennung des Kontos für die Ablage — gedacht ist
   *               PAT_KEY_CHECK (die Prüfsumme des Inhaltsschlüssels): Wer
   *               einen anderen Inhaltsschlüssel hat, hat ein anderes Buch
   *   schluessel  der Inhaltsschlüssel (CryptoKey)
   *   transport   ein Adapter (oben)
   *
   * WAS DIESER BROWSER WEISS, VERGISST ER NICHT. Kommt vom Server ein
   * neueres Blatt, wird es übernommen — aber `p`, `o` und `s` eines
   * Eintrags fallen dabei nie: Es gilt je Feld der höhere Wert. Was der
   * Browser dann mehr weiß als die Abschrift, merkt er als `offen` und
   * trägt es nach (`nachtragen()`). `d` und `x` sind keine Zähler; sie
   * kommen aus dem neueren Blatt.
   *
   * SOLANGE DIE WEICHE AUS STEHT (`EdInhalt.schreibt2()`), schreibt das Buch
   * NICHTS an den Server: `fortschreiben()`, `vormerken()`, `nachtragen()`
   * und die Gerätewege liefern null, ohne zu senden. Lesen und Vergleichen
   * gehen. */
  function oeffnen(o) {
    const ck = o.schluessel, transport = o.transport, lager = o.ablage || ablage();
    const id = wer => o.konto + '|' + wer;
    const zustand = new Map();       // wer → Versprechen auf { version, d, offen }
    const schlange = new Map();      // wer → das letzte Schreiben in die Ablage
    const jetzt = () => (o.jetzt ? o.jetzt() : Math.floor(Date.now() / 1000));
    const leer = wer => (wer === BUCH ? { fassung: 1, geraete: [] } : { fassung: 1, t: 0, zu: false, e: {} });
    const klon = d => JSON.parse(JSON.stringify(d));

    const versiegeln = (wer, version, d) => (wer === BUCH
      ? geraetebuchBauen(ck, version, d.geraete)
      : EdInhalt.verschliessenBytes(ck, 'stand', String(wer), json(d), version));

    async function entsiegeln(wer, text, anker) {
      if (wer === BUCH) {
        const b = await geraetebuchOeffnen(ck, text, anker);
        return { version: b.version, d: { fassung: 1, geraete: b.liste } };
      }
      const b = await blattOeffnen(ck, text, wer, anker);
      return { version: b.version, d: b.blatt };
    }

    /** Der Stand dieses Browsers zu einem Blatt — aus dem Speicher der
     *  Seite, sonst aus der Ablage, sonst leer (Version 0). */
    function lade(wer) {
      /* Im Fach liegt das VERSPRECHEN: Eine Liste öffnet zweihundert Blöcke
       * gleichzeitig, und alle sollen dasselbe Blatt in der Hand haben —
       * nicht jeder seine eigene Abschrift, von denen die letzte gewinnt. */
      if (!zustand.has(wer)) {
        const v = (async () => {
          const da = await lager.lesen(id(wer));
          if (!da || !da.text) { return { version: 0, d: leer(wer), offen: false }; }
          const auf = await entsiegeln(wer, da.text, null);
          return { version: da.version, d: auf.d, offen: !!da.offen };
        })();
        v.catch(() => zustand.delete(wer));
        zustand.set(wer, v);
      }
      return zustand.get(wer);
    }

    /* In die Ablage geht EINER NACH DEM ANDEREN, und jeder schreibt den
     * Stand, der dann gilt — sonst könnte ein früher begonnenes Versiegeln
     * später fertig werden und einen neueren Stand überschreiben. */
    function merke(wer, z) {
      zustand.set(wer, Promise.resolve(z));
      const dran = (schlange.get(wer) || Promise.resolve()).catch(() => {}).then(async () => {
        const gilt = await zustand.get(wer);
        await lager.schreiben(id(wer), { version: gilt.version, offen: gilt.offen,
                                         text: await versiegeln(wer, gilt.version, gilt.d) });
      });
      schlange.set(wer, dran);
      return dran;
    }

    /** Je Feld der höhere Zähler; liefert true, wenn `ziel` dabei wuchs. */
    function hoeher(ziel, quelle) {
      let gewachsen = false;
      for (const k of Object.keys(quelle.e || {})) {
        const q = quelle.e[k], z = ziel.e[k] || {};
        const neu = Object.assign({}, z);
        if ((q.p || 0) > (z.p || 0)) { neu.p = q.p; }
        if ((q.o || 0) > (z.o || 0)) { neu.o = q.o; neu.k = q.k; }
        else if ((q.o || 0) === (z.o || 0) && q.k && !z.k) { neu.k = q.k; }
        if (q.s && (!z.s || q.s[0] > z.s[0] || q.s[1] > z.s[1])) {
          neu.s = z.s ? [Math.max(q.s[0], z.s[0]), Math.max(q.s[1], z.s[1])] : q.s;
        }
        if (!ziel.e[k]) { if (q.d != null) { neu.d = q.d; } if (q.x) { neu.x = q.x; } }
        const fertig = eintragOrdnen(neu);
        if (JSON.stringify(fertig) !== JSON.stringify(eintragOrdnen(z))) { gewachsen = true; }
        if (Object.keys(fertig).length) { ziel.e[k] = fertig; }
      }
      if (quelle.zu && !ziel.zu) { ziel.zu = true; gewachsen = true; }
      return gewachsen;
    }

    /**
     * Ein Blatt des Servers übernehmen. Wirft `blatt-zurueckgelegt` bzw.
     * `buch-zurueckgelegt`, wenn es älter ist als der Anker, und
     * `blatt-version`, wenn der Server eine andere Version nennt, als im
     * Blatt steht.
     */
    async function uebernehmen(wer, srv) {
      const hier = await lade(wer);
      const auf = await entsiegeln(wer, srv.text, hier.version);
      if (auf.version !== srv.version) { throw fehler('blatt-version'); }
      let offen = false;
      if (wer !== BUCH) { offen = hoeher(auf.d, hier.d); }
      const z = { version: auf.version, d: auf.d, offen: offen };
      await merke(wer, z);
      return z;
    }

    /**
     * MIT DEM SERVER ABGLEICHEN: je Blatt, dessen Version dort höher ist
     * als hier, das Blatt holen und übernehmen. Wirft nicht für ein
     * einzelnes Blatt — die Befunde kommen zurück:
     * [{ wer, grund, hier, dort }], `grund` z. B. 'blatt-zurueckgelegt'.
     * Ein Befund heißt: Diesem Blatt des Servers traut der Browser nicht;
     * er arbeitet mit seinem eigenen Stand weiter.
     */
    async function abgleichen() {
      const s = await transport.stand();
      const befunde = [];
      const alle = s.blaetter.map((v, i) => [i, v]).concat([[BUCH, s.buch]]);
      for (const [wer, dort] of alle) {
        const hier = await lade(wer);
        if (dort === hier.version) { continue; }
        if (dort < hier.version) {
          befunde.push({ wer: wer, grund: wer === BUCH ? 'buch-zurueckgelegt' : 'blatt-zurueckgelegt',
                         hier: hier.version, dort: dort });
          continue;
        }
        try {
          const srv = await transport.holen(wer);
          if (!srv) { throw fehler('blatt-version'); }
          await uebernehmen(wer, srv);
        } catch (e) {
          befunde.push({ wer: wer, grund: e.grund || 'transport', hier: hier.version, dort: dort });
        }
      }
      return befunde;
    }

    /** Das geöffnete Blatt eines Datensatzes (für `patLesen()` in Listen). */
    async function blattZu(ref) { return (await lade(await blattNr(ref))).d; }

    /** Der Eintrag eines Datensatzes — {} wenn es keinen gibt. */
    async function eintrag(ref) {
      return klon((await blattZu(ref)).e[await kennung(ref)] || {});
    }

    /**
     * EINEN PATIENTENBLOCK LESEN UND GEGEN DEN STAND HALTEN (beide
     * Fassungen). Hinkt das Blatt, merkt sich der Browser den höheren
     * Zähler sofort — von da an fällt auch dieser Block nicht mehr zurück —
     * und trägt ihn mit `nachtragen()` in die Abschrift.
     * @returns {Promise<{text: string, z, gebunden, blatt_hinkt}>}
     */
    async function patLesenHier(text, ref) {
      const nr = await blattNr(ref);
      const z = await lade(nr);
      const erg = await patLesen(ck, text, ref, z.d);
      if (erg.blatt_hinkt) {
        const k = await kennung(ref);
        z.d.e[k] = eintragOrdnen(Object.assign({}, z.d.e[k], { p: erg.z }));
        z.offen = true;
        await merke(nr, z);
      }
      return { text: EdInhalt.alsText(erg.nutzlast), z: erg.z, gebunden: erg.gebunden,
               blatt_hinkt: erg.blatt_hinkt };
    }

    /* Die nächste Fassung eines Blatts: Änderung anwenden, Version + 1. */
    async function naechste(wer, aendern) {
      const z = await lade(wer);
      const d = klon(z.d);
      if (aendern) { await aendern(d); }
      if (wer !== BUCH) {
        d.t = jetzt();
        for (const k of Object.keys(d.e)) { d.e[k] = eintragOrdnen(d.e[k]); }
      }
      const version = z.version + 1;
      return { wer: wer, version_alt: z.version, version: version, d: d,
               text: await versiegeln(wer, version, d) };
    }

    /* Schreiben mit Wiederholung: Bei einem Konflikt das Blatt des Servers
     * übernehmen und die Änderung auf DESSEN Stand noch einmal anwenden —
     * nicht den eigenen Stand darüberlegen. Vier Versuche; danach ist es
     * kein Zufall mehr, und der Aufrufer erfährt es. */
    async function schreibe(wer, aendern) {
      if (!EdInhalt.schreibt2()) { return null; }
      for (let versuch = 0; versuch < 4; versuch++) {
        const n = await naechste(wer, aendern);
        const a = await transport.ablegen(wer, { version_alt: n.version_alt, version: n.version, text: n.text });
        if (a.ok) {
          await merke(wer, { version: n.version, d: n.d, offen: false });
          return n.version;
        }
        if (!a.text) { throw fehler('blatt-version'); }
        await uebernehmen(wer, { version: a.version, text: a.text });
      }
      const e = fehler('konflikt');
      e.wer = wer;
      throw e;
    }

    const amEintrag = (ref, aendern) => async (d) => {
      const k = await kennung(ref);
      const e = Object.assign({}, d.e[k]);
      await aendern(e);
      const fertig = eintragOrdnen(e);
      if (Object.keys(fertig).length) { d.e[k] = fertig; } else { delete d.e[k]; }
    };

    /**
     * DEN EINTRAG EINES DATENSATZES FORTSCHREIBEN UND SOFORT ABLEGEN.
     * `aendern(e)` bekommt den Eintrag ({p, o, k, s, d, x}) und ändert ihn.
     * @returns {Promise<number|null>} die neue Version des Blatts; null,
     *          solange die Weiche aus steht
     */
    async function fortschreiben(ref, aendern) {
      return schreibe(await blattNr(ref), amEintrag(ref, aendern));
    }

    /**
     * DASSELBE, ABER DAS BLATT FÄHRT MIT DEM DATENSATZ IN EINER ANFRAGE.
     * Liefert das Blatt zum Mitschicken und zwei Wege danach:
     *   { wer, version_alt, version, text,
     *     bestaetigen()      der Server hat beides angenommen
     *     konflikt(srv)      409 am Blatt: `srv` = { version, text } des
     *                        Servers; liefert eine NEUE Vormerkung auf
     *                        dessen Stand — damit noch einmal senden }
     * Wird keins von beiden gerufen, ist nichts geschehen.
     * @returns null, solange die Weiche aus steht
     */
    async function vormerken(ref, aendern) {
      if (!EdInhalt.schreibt2()) { return null; }
      const wer = await blattNr(ref);
      const n = await naechste(wer, amEintrag(ref, aendern));
      return {
        wer: wer, version_alt: n.version_alt, version: n.version, text: n.text,
        bestaetigen: () => merke(wer, { version: n.version, d: n.d, offen: false }),
        konflikt: async (srv) => {
          if (!srv || !srv.text) { throw fehler('blatt-version'); }
          await uebernehmen(wer, srv);
          return vormerken(ref, aendern);
        },
      };
    }

    /**
     * VIELE DATENSÄTZE IN EINER ANFRAGE (KA-06b-3): Import, Einspielen,
     * Notizen-Anhebung. Je Blatt EINE Vormerkung, in der alle Änderungen an
     * diesem Blatt stehen — zwei Vormerkungen desselben Blatts nennten
     * dieselbe `version_alt`, und die zweite verlöre die erste.
     * @param liste  [{ ref, aendern(e) }]
     * @returns {Promise<Array|null>} je Blatt { wer, version_alt, version,
     *          text, refs, bestaetigen() }; null, solange die Weiche aus steht
     */
    async function vormerkenViele(liste) {
      if (!EdInhalt.schreibt2()) { return null; }
      const je = new Map();
      for (const x of liste) {
        const wer = await blattNr(x.ref);
        if (!je.has(wer)) { je.set(wer, []); }
        je.get(wer).push(x);
      }
      const aus = [];
      for (const [wer, xs] of je) {
        const n = await naechste(wer, async (d) => {
          for (const x of xs) { await amEintrag(x.ref, x.aendern)(d); }
        });
        aus.push({ wer: wer, version_alt: n.version_alt, version: n.version, text: n.text,
                   refs: xs.map(x => x.ref),
                   bestaetigen: () => merke(wer, { version: n.version, d: n.d, offen: false }) });
      }
      return aus;
    }

    /** Der nächste Zähler für den Patientenblock dieses Datensatzes. */
    async function naechsterZaehler(ref) { return ((await eintrag(ref)).p || 0) + 1; }

    /** Alles ablegen, was dieser Browser mehr weiß als die Abschrift.
     *  @returns {Promise<Array|null>} die geschriebenen Blätter */
    async function nachtragen() {
      if (!EdInhalt.schreibt2()) { return null; }
      const aus = [];
      for (let nr = 0; nr < BLAETTER; nr++) {
        if ((await lade(nr)).offen) { await schreibe(nr, null); aus.push(nr); }
      }
      return aus;
    }

    /** Der Anhebelauf ist für dieses Blatt durch: Fassung 1 ist dort von
     *  nun an ein Befund (`altformat`). Nicht umkehrbar. */
    function schliessen(nr) { return schreibe(nr, (d) => { d.zu = true; }); }

    /** Ist dieses eine Blatt geschlossen (Anhebelauf, KA-06b-4)? */
    async function blattZuNr(nr) { return !!(await lade(nr)).d.zu; }

    /** Sind alle 16 Blätter geschlossen? Dann ist Altformat überall ein Befund. */
    async function alleZu() {
      for (let nr = 0; nr < BLAETTER; nr++) { if (!(await lade(nr)).d.zu) { return false; } }
      return true;
    }

    /* ---- Das Gerätebuch ------------------------------------------------ */

    /** Die bestätigten Geräte, in der Form für `EdHuelle2.leser()`. */
    async function geraete() {
      const z = await lade(BUCH);
      const aus = {};
      for (const g of z.d.geraete) {
        aus[g.sid] = { spk: T.fromB64(g.spk), bis: g.bis == null ? null : g.bis, geraet: g.geraet, seit: g.seit };
      }
      return aus;
    }

    /**
     * Ein Gerät eintragen — NACH dem Klick „Wert stimmt" und nie davor
     * (`EdHuelle2.kopplungWert()`).
     * @param g  { spk: Uint8Array(65), geraet: string, seit: number }
     * @returns {Promise<string|null>} die SID (hex); null bei Weiche aus
     */
    async function geraetEintragen(g) {
      if (!g.spk || g.spk.length !== 65 || g.spk[0] !== 4) { throw fehler('innen'); }
      const s = T.toHex(await EdHuelle2.sid(g.spk));
      const v = await schreibe(BUCH, (d) => {
        d.geraete = d.geraete.filter(x => x.sid !== s);
        d.geraete.push({ sid: s, spk: T.toB64(g.spk), geraet: String(g.geraet || ''),
                         seit: g.seit == null ? jetzt() : g.seit, bis: null });
      });
      return v === null ? null : s;
    }

    /** Ein Gerät austragen: Was es nach `bis` verschlossen haben will, gilt
     *  nicht mehr (`absender-gesperrt`). Der Eintrag bleibt — ältere Daten
     *  dieses Geräts müssen weiter aufgehen. */
    function geraetAustragen(sidHex, bis) {
      return schreibe(BUCH, (d) => {
        const g = d.geraete.find(x => x.sid === sidHex);
        if (!g) { throw fehler('absender'); }
        if (g.bis == null) { g.bis = bis == null ? jetzt() : bis; }
      });
    }

    /**
     * DEN EIGENEN STAND ÜBER DEN DES SERVERS LEGEN — für den einen Fall,
     * dass der Server nachweislich zurückgesetzt wurde (eine eingespielte
     * Sicherung) und die BetreiberIn das bestätigt hat. Sonst nie: Es ist
     * der Weg, auf dem ein Befund `blatt-zurueckgelegt` verschwindet.
     */
    async function zurueckschreiben(wer) {
      if (!EdInhalt.schreibt2()) { return null; }
      const z = await lade(wer);
      const srv = await transport.holen(wer);
      const version = z.version + 1;
      const text = await versiegeln(wer, version, z.d);
      const a = await transport.ablegen(wer, { version_alt: srv ? srv.version : 0, version: version, text: text });
      if (!a.ok) { throw fehler('konflikt'); }
      await merke(wer, { version: version, d: z.d, offen: false });
      return version;
    }

    return { abgleichen, eintrag, blattZu, patLesen: patLesenHier, naechsterZaehler,
             fortschreiben, vormerken, vormerkenViele, nachtragen, schliessen, alleZu, blattZuNr,
             /* 409 `blatt` aus einer Sammelanfrage: das Blatt des Servers übernehmen */
             blattUebernehmen: uebernehmen,
             geraete, geraetEintragen, geraetAustragen, zurueckschreiben,
             /* für die Probe */
             anker: async wer => (await lade(wer)).version, ablage: lager };
  }

  /* ====================================================================
   * FÜR DIE VERBRAUCHER — ein Buch je Seite (KA-06b-3)
   * ====================================================================
   *
   * Kein Verbraucher ruft `oeffnen()` selbst. Zwei Bücher desselben Kontos
   * auf einer Seite hätten zwei Stände im Speicher, und das zweite schriebe
   * über, was das erste eben vorgemerkt hat.
   *
   * DIE KENNUNG DES KONTOS FÜR DIE ABLAGE kommt aus dem Inhaltsschlüssel
   * selbst und nicht vom Server: der Prüfwert von AES-GCM über nichts, mit
   * fester Nonce und eigenen Zusatzdaten, davon SHA-256. Gedacht war
   * `PAT_KEY_CHECK`; das liefert der Server, steht nur auf Seiten mit
   * `keycheck` und fehlt im Altbestand. Eine Kennung, die der Server nennt,
   * könnte er wechseln — und ein Browser, der unter einer neuen Kennung
   * nachsieht, findet keinen Anker und glaubt der Abschrift. Die feste Nonce
   * ist hier unbedenklich: Sie verschließt genau eine Nachricht (die leere,
   * unter diesen Zusatzdaten), und das Ergebnis verlässt den Browser nicht.
   *
   * SOLANGE DIE WEICHE AUS STEHT, fasst ein Leseweg das Buch nur für einen
   * gebundenen Block an — Fassung 1 geht wie bisher über `EdCrypto.decrypt()`,
   * ohne Ablage und ohne Anfrage —, und kein Schreibweg sendet ein Blatt. */
  const KONTO_AAD = te.encode('nadoku-stand-konto-1');
  const buecher = new Map();          // Kennung → Versprechen auf das Buch

  /** Die Kennung des Kontos für die Ablage — aus dem Inhaltsschlüssel. */
  async function kontoKennung(ck) {
    const key = await T.aesSchluessel(ck, ['encrypt']);
    const tag = await crypto.subtle.encrypt(
      { name: 'AES-GCM', iv: new Uint8Array(12), additionalData: KONTO_AAD }, key, new Uint8Array(0));
    const h = new Uint8Array(await crypto.subtle.digest('SHA-256', tag));
    return 'k1-' + T.toHex(h).slice(0, 32);
  }

  /**
   * DAS BUCH DIESER SEITE für diesen Inhaltsschlüssel, über `apiTransport()`.
   * Steht die Weiche an, gleicht es beim ersten Öffnen mit dem Server ab;
   * die Befunde stehen in `buch.befunde` (der Knopf „zurückschreiben" kommt
   * mit E-KA-50 und braucht ein Mockup).
   */
  async function seitenbuch(ck) {
    const konto = await kontoKennung(ck);
    if (!buecher.has(konto)) {
      const v = (async () => {
        const buch = oeffnen({ konto: konto, schluessel: ck, transport: apiTransport() });
        buch.befunde = [];
        if (EdInhalt.schreibt2()) {
          try { buch.befunde = await buch.abgleichen(); }
          catch (e) { buch.befunde = [{ wer: null, grund: e.grund || 'transport' }]; }
        }
        return buch;
      })();
      v.catch(() => buecher.delete(konto));
      buecher.set(konto, v);
    }
    return buecher.get(konto);
  }

  /**
   * EINEN PATIENTENBLOCK LESEN — der Weg für jeden Verbraucher. Beide
   * Fassungen; ein gebundener Block geht gegen das Buch.
   * @returns {Promise<string>} der Klartext (JSON)
   */
  async function patOeffnen(ck, text, ref) {
    if (!EdInhalt.istGebunden(text) && !EdInhalt.schreibt2()) {
      return EdCrypto.decrypt(ck, text);
    }
    return (await (await seitenbuch(ck)).patLesen(text, ref)).text;
  }

  /**
   * PATIENTENBLÖCKE VERSCHLIESSEN, samt den Blättern für dieselbe Anfrage.
   * @param posten  [{ ref, klartext }] — `ref` die client_ref des Einsatzes
   * @param o       { ohne: Set } Kennungen, die kein Blatt bekommen (der
   *                Server hat sie übersprungen, 409 `uebersprungen`)
   * @returns {Promise<{texte: string[], blaetter: Array|null, bestaetigen}>}
   *   `blaetter` ist null, solange die Weiche aus steht — dann geht KEIN
   *   Feld mit, und `texte` sind `edk1:` wie aus `EdCrypto.encrypt()`.
   *   Ein Blatt trägt neben `wer`, `version_alt`, `version`, `text` die
   *   Kennungen, deren Zähler es hebt (`refs`): Daran prüft der Server, dass
   *   er jeden dieser Blöcke wirklich geschrieben hat — ein Blatt, das einem
   *   Block vorausläuft, ließe den alten Block als zurückgelegt erscheinen.
   */
  async function patVerschliessenViele(ck, posten, o) {
    o = o || {};
    if (!EdInhalt.schreibt2()) {
      const texte = [];
      for (const p of posten) { texte.push(await EdInhalt.schreiben(ck, 'pat', p.ref, p.klartext, 0)); }
      return { texte: texte, blaetter: null, bestaetigen: async () => {} };
    }
    const buch = await seitenbuch(ck);
    try { await buch.abgleichen(); } catch (e) { /* mit dem eigenen Stand weiter */ }
    const z = [];
    for (const p of posten) {
      if (typeof p.ref !== 'string' || p.ref === '') { throw fehler('bezug'); }
      z.push(await buch.naechsterZaehler(p.ref));
    }
    const texte = await Promise.all(posten.map((p, i) =>
      EdInhalt.schreiben(ck, 'pat', p.ref, p.klartext, z[i])));
    const ohne = o.ohne || new Set();
    const vm = await buch.vormerkenViele(posten
      .map((p, i) => ({ ref: p.ref, aendern: (e) => { e.p = z[i]; } }))
      .filter(x => !ohne.has(x.ref)));
    return {
      texte: texte,
      blaetter: vm.map(b => ({ wer: b.wer, version_alt: b.version_alt, version: b.version,
                               text: b.text, refs: b.refs })),
      bestaetigen: async () => { for (const b of vm) { await b.bestaetigen(); } },
    };
  }

  /**
   * VERSCHLIESSEN UND SENDEN, mit Wiederholung bei 409 `blatt`.
   * `senden(texte, blaetter)` baut die Anfrage — `blaetter` als Feld nur,
   * wenn es nicht null ist — und liefert die Antwort von `EdApi`.
   *   409 { error: 'blatt', grund: 'version', wer, version, text }
   *       ein anderer Tab war schneller: sein Blatt übernehmen, neu zählen,
   *       neu verschließen, noch einmal senden;
   *   409 { error: 'blatt', grund: 'uebersprungen', refs }
   *       der Server hat diese Einsätze nicht geschrieben (schon da, nicht
   *       mehr da): ohne ihre Zähler noch einmal senden.
   * Vier Versuche; danach `konflikt`.
   * @returns die letzte Antwort von `senden()`
   */
  async function patSchreibenViele(ck, posten, senden) {
    const ohne = new Set();
    for (let versuch = 0; versuch < 4; versuch++) {
      const v = await patVerschliessenViele(ck, posten, { ohne: ohne });
      const a = await senden(v.texte, v.blaetter);
      if (a.ok) { await v.bestaetigen(); return a; }
      const d = a.daten;
      if (!v.blaetter || a.status !== 409 || !d || d.error !== 'blatt') { return a; }
      if (d.grund === 'uebersprungen') {
        for (const r of (d.refs || [])) { ohne.add(r); }
        continue;
      }
      if (!d.text) { throw fehler('blatt-version'); }
      await (await seitenbuch(ck)).blattUebernehmen(d.wer, { version: Number(d.version), text: d.text });
    }
    throw fehler('konflikt');
  }

  return { blattNr, kennung, blattBauen, blattOeffnen, patLesen,
           geraetebuchBauen, geraetebuchOeffnen,
           oeffnen, ablage, speicherTransport, apiTransport,
           /* für die Verbraucher (KA-06b-3) */
           seitenbuch, kontoKennung, patOeffnen, patVerschliessenViele, patSchreibenViele,
           BLAETTER, BUCH };
})();
