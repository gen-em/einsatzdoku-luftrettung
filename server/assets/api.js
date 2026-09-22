/* API — die eine Stelle im Browser, an der eine Anfrage an den Server geht
 * ==========================================================================
 *
 * Entstanden in Schritt 15 AP8b (Zentralisierung, R83).
 *
 * WAS VORHER WAR. Zwanzig Sendestellen in neun Dateien, und der AUFRUF war
 * an allen zeichengleich: POST, genau zwei Kopfzeilen, `JSON.stringify`
 * (bzw. `URLSearchParams` bei den vier Formularstellen), Token aus der
 * globalen `CSRF`. Auseinander gingen sie erst HINTER dem `fetch`, und zwar
 * auf sieben Achsen. Gemessen am 22.09.2026:
 *
 *   - VIER Regeln dafuer, was als Erfolg gilt. ACHT von 15 JSON-Stellen
 *     pruefen `res.ok` ueberhaupt nicht: Eine 500 mit wohlgeformtem JSON
 *     gilt dort als Erfolg.
 *   - ZWEI Politiken bei einer Antwort, die kein JSON ist: neun Stellen
 *     werfen, vier liefern null.
 *   - SECHS Vorrangketten fuer die Meldung. Nur VIER von 15 lasen das Feld
 *     `hinweis` -- und genau dort steht der Satz zu `post_max_size`, den der
 *     Server bei einem zu grossen Upload schickt (`api_rumpf()` in db.php).
 *     Derselbe zu grosse POST zeigte an einer Stelle den vollen Hinweis und
 *     an einer anderen „HTTP 400".
 *   - FUENF Satzbauten und SIEBEN Anzeigewege.
 *   - VIERZEHN von 15 zeigten im Netzfehler `e.message`, also den englischen
 *     Browsertext „Failed to fetch".
 *
 * WAS DIESE DATEI ENTSCHEIDET -- und was nicht. Sie entscheidet den Aufruf,
 * die Erfolgsregel, die Vorrangkette und den Satzbau. Sie entscheidet NICHT,
 * WO und OB der Satz erscheint: Das bleibt beim Aufrufer, weil die
 * Anzeigewege fachlich verschieden sind. Drei Stellen zeigen ausdruecklich
 * NICHTS (die KDF-Anhebung und das Nachziehen der Notizen in unlock.js;
 * deren Kopfkommentar sagt „NIEMAND WARTET DARAUF"), und das bleibt so --
 * sie lesen `meldung` einfach nicht.
 *
 * DER SATZBAU. `<Vorgang> ist fehlgeschlagen: <Grund>` -- mit `o.vorgang`
 * als Satzanfang, ohne ihn nur der Grund. Die Vorrangkette des Grundes:
 *
 *   1. `meldung` aus der Antwort      -- der Satz, den der Endpunkt schickt
 *   2. `hinweis` aus der Antwort      -- der Satz zu post_max_size u. a.
 *   3. ein Ersatzsatz mit Kennung     -- siehe `grund()`
 *
 * `error` STEHT NICHT IN DER KETTE, und das ist Absicht. Es traegt
 * MASCHINENWOERTER (`'leer'`, `'format'`, `'zu_gross'`, `'method'`), keine
 * Saetze. Sechs Stellen setzten es bis heute unverandert in den Fliesstext;
 * „Der Import ist fehlgeschlagen: format" sagt niemandem etwas. Es geht
 * stattdessen als KENNUNG in die Klammer des Ersatzsatzes -- diagnostisch
 * erhalten, aber nicht als Satz ausgegeben.
 *
 * KEIN MODUL, KEIN IMPORT. Klassisches <script>, legt `window.EdApi` an --
 * wie `EdFormat`, `EdKarte` und die uebrigen.
 *
 * DIE DATEI STEHT IM <head>, auf jeder Seite (`ui_seite_start()` in
 * server/ui.php). Sie stand einen Nachmittag lang in der Immer-Liste von
 * `ui_geruest_ende()`, und hier stand daneben, das trage schon, weil jeder
 * Aufruf in einem Zuhoerer stecke und erst nach dem Laden laufe.
 *
 * DIESER SATZ WAR FALSCH. Ein Gegenleser hat ihn mit Zeilennummern
 * widerlegt: Auf `einstellungen.php` und `import.php` kommt
 * `ui_geruest_ende()` NACH den Seitenskripten, und auf genau diesen beiden
 * laeuft `unlock.js` seinen Sendeweg zur LADEZEIT -- ueber `ck()` bzw.
 * `sperrstatus()` nach `ensureContentKey()` und `loeseVormerkung()`.
 * `EdApi` waere dort undefiniert gewesen, und der ReferenceError waere in
 * einen absichtlich stillen catch gefallen. Die KDF-Anhebung haette auf
 * zwei Seiten aufgehoert zu laufen, wortlos.
 *
 * Die Lehre steht hier, weil sie sich wiederholen wird: Eine Zusage ueber
 * die Ladereihenfolge ist nur so viel wert wie die Liste der Aufrufer, die
 * man dafuer durchgegangen ist. Im Kopf braucht man sie nicht.
 */
(function () {
  'use strict';

  /* Das Token steht seit Backlog Nr. 136 bedingungslos auf jeder Seite, die
   * `ui_krypto_bootstrap()` zieht (`const CSRF = ...`). Die Wache ist
   * trotzdem da: Zwei Stellen im Bestand trugen sie, und eine Datei, die
   * einmal ohne Bootstrap eingebunden wird, soll keinen ReferenceError
   * werfen, sondern eine 403 bekommen -- die ist lesbar. */
  function token() {
    return typeof CSRF === 'string' ? CSRF : '';
  }

  /* Der Grund, ein Satz. Siehe Kopf: `error` ist kein Satz und steht
   * deshalb nur in der Klammer. */
  function grund(status, daten) {
    if (daten && typeof daten.meldung === 'string' && daten.meldung !== '') {
      return daten.meldung;
    }
    /* `text` IST DASSELBE WIE `meldung`, NUR AN ZWEI ENDPUNKTEN ANDERS
     * BENANNT: `api/schluessel_erneuern.php` und
     * `api/schluesselblatt_pruefen.php` schicken ihren Satz in `text`
     * (9 Stellen), alle uebrigen in `meldung` (32 Stellen, gezaehlt am
     * 22.09.2026). Keiner schickt beides.
     *
     * Ohne diese Zeile griffe `schluesselblatt.js` an EdApi vorbei -- es
     * hatte dafuer einen eigenen Helfer `fehlersatz()`, der `daten.text`
     * bevorzugte und damit den Vorgangsnamen verlor. Genau das soll die
     * Zentrale abschaffen. Die zwei Namen auf der Serverseite
     * zusammenzufuehren waere die sauberere Loesung und gehoert nicht in
     * dieses Paket (E-ZE-10). */
    if (daten && typeof daten.text === 'string' && daten.text !== '') {
      return daten.text;
    }
    if (daten && typeof daten.hinweis === 'string' && daten.hinweis !== '') {
      return daten.hinweis;
    }
    if (status === 0) {
      return 'Die Verbindung zum Server ist abgebrochen.';
    }
    if (!daten) {
      return 'Die Antwort des Servers war nicht lesbar (HTTP ' + status + ').';
    }
    const kennung = (daten && daten.error) ? String(daten.error) : 'HTTP ' + status;
    return 'Der Server hat den Vorgang abgelehnt (' + kennung + ').';
  }

  function satz(vorgang, g) {
    return vorgang ? vorgang + ' ist fehlgeschlagen: ' + g : g;
  }

  /* Der gemeinsame Rumpf beider Sender: schicken, Antwort lesen, Ergebnis
   * bauen. Wirft NIE -- ein Netzfehler wird zu `status: 0`. */
  async function senden(url, optionen, vorgang) {
    let antwort = null;
    try {
      antwort = await fetch(url, optionen);
    } catch (e) {
      return { ok: false, status: 0, daten: null,
               meldung: satz(vorgang, grund(0, null)) };
    }
    let daten = null;
    try {
      daten = await antwort.json();
    } catch (e) {
      daten = null;
    }
    /* `daten` muss ein OBJEKT sein. Ein Endpunkt, der `null` oder eine
     * nackte Zahl schickt, ist so wenig brauchbar wie gar keine Antwort --
     * und `daten.error` auf einer Zahl zu lesen ergibt undefined, also
     * still „kein Fehler". */
    if (daten === null || typeof daten !== 'object') { daten = null; }

    /* `ok` IST EIN TRANSPORT-URTEIL, KEIN FACHLICHES.
     *
     * Beachte `daten.ok !== false` und NICHT `daten.ok === true`: Eine
     * Antwort OHNE das Feld besteht die Pruefung. Das muss so sein, weil
     * die lesenden Endpunkte gar kein `ok` schicken, sondern ihre Nutzdaten.
     *
     * FOLGE, und sie ist benannt: Wo ein Aufrufer bisher `daten.ok` GELESEN
     * hat, ist `ok` allein SCHWAECHER als seine alte Pruefung -- bei einer
     * 200 mit einem Rumpf ohne `ok` sagt EdApi "in Ordnung", der alte Code
     * sagte "Fehler". Ein solcher Aufrufer prueft `a.daten.ok` weiter mit.
     * Gefunden hat das ein Gegenleser an `schluesselblatt.js`, wo die
     * Rueckfrage sonst einen bedienbaren Knopf ohne Felder hinterlassen
     * haette. */
    const ok = antwort.ok && daten !== null
            && daten.error == null && daten.ok !== false;
    return {
      ok: ok,
      status: antwort.status,
      daten: daten,
      meldung: ok ? '' : satz(vorgang, grund(antwort.status, daten)),
    };
  }

  /**
   * POST mit JSON-Rumpf. Haengt das CSRF-Token als Kopfzeile an.
   *
   * @param {string} url
   * @param {Object} daten    wird mit JSON.stringify verschickt
   * @param {Object} [o]
   * @param {string} [o.vorgang] Satzanfang der Meldung, z. B. 'Der Export'
   * @returns {Promise<{ok: boolean, status: number, daten: ?Object, meldung: string}>}
   */
  async function postJson(url, daten, o) {
    return senden(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': token() },
      body: JSON.stringify(daten),
    }, (o && o.vorgang) || '');
  }

  /**
   * POST mit Formular-Kodierung. Haengt das CSRF-Feld in den Rumpf an.
   *
   * DIE ARRAY-REGEL IST NICHT KOSMETIK. Ein Feld, dessen Wert ein Array
   * ist, geht als `name[]` hinaus -- sonst liest PHP eine Zeichenkette,
   * wo `is_array()` geprueft wird. Bei `api/schluesselblatt_pruefen.php`
   * heisst das: `$antworten = []`, alle vier Gruppen gegen '' verglichen,
   * ein Fehlversuch ueber `rate_misserfolg('blatt', ...)` gezaehlt und ins
   * Sicherheitsprotokoll geschrieben. Wer die Regel weglaesst, sperrt die
   * Betreiberin aus ihrer eigenen Rueckfrage aus -- ohne Fehlermeldung,
   * mit richtig aussehendem Code.
   *
   * @param {string} url
   * @param {Object} felder   Werte; Arrays gehen als `name[]` hinaus
   * @param {Object} [o]
   * @param {string} [o.vorgang] Satzanfang der Meldung
   * @returns {Promise<{ok: boolean, status: number, daten: ?Object, meldung: string}>}
   */
  async function postForm(url, felder, o) {
    const leib = new URLSearchParams();
    leib.set('csrf', token());
    const f = felder || {};
    for (const k of Object.keys(f)) {
      if (Array.isArray(f[k])) {
        f[k].forEach(function (w) { leib.append(k + '[]', w); });
      } else {
        leib.set(k, f[k]);
      }
    }
    return senden(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: leib.toString(),
    }, (o && o.vorgang) || '');
  }

  window.EdApi = { postJson, postForm };
})();
