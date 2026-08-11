/* Einsatzdoku — gemeinsame Einsatztabelle fuer zeitraum.php und suche.php.
 *
 * Beide Seiten zeigen dieselbe Liste: gleiche Spalten, gleiche Sortierung,
 * gleicher Zeilenaufbau, Klick auf die Zeile oeffnet den Einsatz. Der Code
 * dafuer steht deshalb genau einmal hier — sonst driften die beiden Tabellen
 * bei der naechsten Aenderung auseinander.
 *
 * Die Spalten Einsatzort, Alter und Diagnose stammen aus dem entschluesselten
 * pat_blob und werden von der aufrufenden Seite als `_ort`, `_age` und `_dx`
 * an die Einsatzobjekte geschrieben. Ist der Inhaltsschluessel gesperrt,
 * fehlen sie schlicht — die Tabelle zeigt dann Gedankenstriche.
 *
 * Erwartet aus der Seite: nichts. Reine DOM-Arbeit, keine Abhaengigkeiten.
 */
'use strict';
const EdMissionTable = (() => {

  /* ---- Formatierung (auch nach aussen gegeben, s. u.) ------------------ */

  /* Maskierung fuer HTML (Baustein B7) — die kanonische Fassung.
   *
   * WARUM ES SIE GIBT: Dieselbe Aufgabe wird im Browser an drei Stellen
   * wortgleich geloest, und alle drei maskieren nur DREI Zeichen. Die
   * serverseitige Entsprechung (e() in db.php) maskiert FUENF: zusaetzlich
   * beide Anfuehrungszeichen. Zwei Bausteine mit demselben Zweck und
   * unterschiedlichem Umfang, ohne dass der Unterschied irgendwo steht.
   *
   * Fuer Textpositionen reichen drei Zeichen, und heute gibt es keine
   * Attributposition — der Unterschied ist also derzeit folgenlos. Genau
   * deshalb ist er gefaehrlich: Wer das naechste Mal einen Wert in ein
   * title="…" schreibt, hat keinen Anhaltspunkt, dass diese Fassung dafuer
   * nicht taugt.
   *
   * Diese Fassung deckt beide Positionen ab. Die drei Kopien werden in einem
   * folgenden Schritt hierauf umgestellt.
   */
  function escape(t) {
    return String(t == null ? '' : t)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function esc(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
  function fmtTag(iso) { const [y, m, d] = iso.split('-'); return `${d}.${m}.${y}`; }
  function fmtDur(s) {
    if (s == null) return 'kein Ende';
    const h = Math.floor(s / 3600), m = Math.round(s % 3600 / 60);
    return h ? `${h}h ${String(m).padStart(2, '0')}min` : `${m}min`;
  }
  function fmtKm(m) {
    return m == null ? '<span class="dash">–</span>' : (m / 1000).toFixed(1).replace('.', ',') + ' km';
  }
  /* Ortsanteil aus der Adresse: letzter Bestandteil ohne fuehrende PLZ.
   *
   * Die Zerlegung greift nur, wenn der letzte Teil nach dem Komma ueberhaupt
   * Buchstaben enthaelt — also nach einer Adresse mit Ortsteil aussieht. Sonst
   * wird der Text unveraendert durchgereicht: Eine von Hand eingetragene
   * Bezeichnung bleibt vollstaendig stehen, und ein Altdatensatz mit
   * Koordinatentext in addr ("47.72800, 10.31600") zeigt die ganze Koordinate
   * statt des Fragments "10.31600" (E11). */
  function extractOrt(addr) {
    const parts = addr.split(',');
    const last = parts[parts.length - 1].trim();
    if (!/\p{L}/u.test(last)) { return addr.trim(); }
    return last.replace(/^\d{4,5}\s+/, '');
  }

  /* ---- Spaltendefinition ----------------------------------------------
   * Eine Zeile je Spalte: Sortierschluessel, Kopftext, Klassen und die
   * beiden Funktionen fuer Sortierwert und Zellinhalt. Neue Spalte = ein
   * Eintrag hier, und sie erscheint auf beiden Seiten.
   */
  const SPALTEN = [
    { key: 'day',   kopf: 'Datum',                 thClass: 'c-date',
      wert: m => m.day,
      zelle: m => `<td class="mono c-date">${fmtTag(m.day)}</td>` },
    { key: 'start', kopf: 'Beginn',                thClass: 'c-mid',
      wert: m => m.start_hhmm,
      zelle: m => `<td class="mono c-mid">${m.start_hhmm}</td>` },
    { key: 'dur',   kopf: 'Dauer',                 thClass: 'c-mid',
      wert: m => m.duration_s == null ? -1 : m.duration_s,
      zelle: m => `<td class="c-mid">${fmtDur(m.duration_s)}</td>` },
    { key: 'site',  kopf: 'Einsatzort',            thClass: '',
      wert: m => (m._ort || '').toLowerCase(),
      zelle: m => `<td${m._ort ? '' : ' class="dash"'}>${m._ort ? esc(m._ort) : '–'}</td>` },
    { key: 'age',   kopf: 'Alter',                 thClass: 'c-mid',
      wert: m => m._age == null ? -1 : m._age,
      zelle: m => `<td class="mono c-mid${m._age != null ? '' : ' dash'}">${m._age != null ? m._age : '–'}</td>` },
    { key: 'dx',    kopf: 'Diagnose',              thClass: '',
      wert: m => (m._dx || '').toLowerCase(),
      zelle: m => `<td${m._dx ? '' : ' class="dash"'}>${m._dx ? esc(m._dx) : '–'}</td>` },
    { key: 'winch', kopf: 'Winde',                 thClass: 'c-winde',
      wert: m => m.winch ? 1 : 0,
      zelle: m => `<td class="checkcol c-winde">${m.winch ? '✓' : ''}</td>` },
    { key: 'bw',    kopf: 'Bergwacht',             thClass: 'c-bw',
      wert: m => m.bergwacht ? 1 : 0,
      zelle: m => `<td class="checkcol c-bw">${m.bergwacht ? '✓' : ''}</td>` },
    { key: 'sec',   kopf: 'Sekundär<br>Transport', thClass: 'c-sek',
      wert: m => m.secondary ? 1 : 0,
      zelle: m => `<td class="checkcol c-sek">${m.secondary ? '✓' : ''}</td>` },
    { key: 'km',    kopf: 'Flug&nbsp;km',          thClass: 'c-mid',
      wert: m => m.distance_m == null ? -1 : m.distance_m,
      zelle: m => `<td class="mono c-mid">${fmtKm(m.distance_m)}</td>` }
  ];

  /**
   * Baut eine Tabelle auf dem uebergebenen <table>-Element auf.
   *
   * opts.table        <table> mit <thead> und <tbody> (beide duerfen leer sein)
   * opts.sortKey      Voreinstellung, Standard 'day'
   * opts.sortAsc      Voreinstellung, Standard true
   * opts.pfeilInitial Sortierpfeil schon vor dem ersten Klick zeigen
   *                   (zeitraum.php tut das historisch nicht — dort false)
   * opts.onAfterDraw  wird nach jedem Zeichnen mit der Zeilenzahl gerufen
   *
   * Rueckgabe: { setData, zeichne, sortKey, sortAsc, setSort }
   */
  function erzeuge(opts) {
    const table = opts.table;
    let sortKey = opts.sortKey || 'day';
    let sortAsc = opts.sortAsc !== false;
    let pfeilZeigen = !!opts.pfeilInitial;
    let daten = [];

    let thead = table.tHead;
    if (!thead) { thead = table.createTHead(); }
    let tbody = table.tBodies[0];
    if (!tbody) { tbody = table.createTBody(); }

    function zeichneKopf() {
      const tr = document.createElement('tr');
      SPALTEN.forEach(sp => {
        const th = document.createElement('th');
        th.className = 'sortable' + (sp.thClass ? ' ' + sp.thClass : '');
        th.dataset.key = sp.key;
        th.innerHTML = sp.kopf;
        if (pfeilZeigen && sp.key === sortKey) {
          const pfeil = document.createElement('span');
          pfeil.className = 'arrow';
          pfeil.textContent = sortAsc ? ' ▲' : ' ▼';
          th.appendChild(pfeil);
        }
        th.addEventListener('click', () => {
          if (sortKey === sp.key) { sortAsc = !sortAsc; } else { sortKey = sp.key; sortAsc = true; }
          pfeilZeigen = true;
          zeichne();
          if (opts.onSortChange) { opts.onSortChange(sortKey, sortAsc); }
        });
        tr.appendChild(th);
      });
      thead.innerHTML = '';
      thead.appendChild(tr);
    }

    function zeichne() {
      zeichneKopf();
      const sp = SPALTEN.find(s => s.key === sortKey) || SPALTEN[0];
      const sortiert = daten.slice().sort((a, b) => {
        const x = sp.wert(a), y = sp.wert(b);
        const r = (x > y) - (x < y);
        return sortAsc ? r : -r;
      });
      tbody.innerHTML = '';
      sortiert.forEach(m => {
        const tr = document.createElement('tr');
        tr.className = 'clickable';
        tr.dataset.mid = m.id;
        tr.innerHTML = SPALTEN.map(s => s.zelle(m)).join('');
        tr.addEventListener('click', () => { location.href = 'einsatz.php?id=' + m.id; });
        tbody.appendChild(tr);
      });
      if (opts.onAfterDraw) { opts.onAfterDraw(sortiert.length); }
    }

    function setData(liste) { daten = liste || []; zeichne(); }

    function setSort(key, asc) {
      if (!SPALTEN.some(s => s.key === key)) { return; }
      sortKey = key; sortAsc = !!asc; pfeilZeigen = true;
    }

    return {
      setData, zeichne, setSort,
      get sortKey() { return sortKey; },
      get sortAsc() { return sortAsc; }
    };
  }

  return { erzeuge, SPALTEN, esc, escape, fmtTag, fmtDur, fmtKm, extractOrt };
})();
