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
 * Erwartet aus der Seite: EdHtml (assets/html.js). Sonst reine DOM-Arbeit.
 */
'use strict';
const EdMissionTable = (() => {

  /* ---- Formatierung (auch nach aussen gegeben, s. u.) ------------------ */

  /* Maskierung fuer HTML: Baustein B7, seit Web 4.6.0 in assets/html.js
   * (M6-03, M6-05). Hier steht nur noch die Weiterleitung — sie haelt die
   * vorhandenen Aufrufe EdMissionTable.escape/.esc gueltig und macht zugleich
   * sichtbar, dass es NUR NOCH EINE Fassung gibt.
   *
   * esc() war bis dahin eine zweite, eigene Umsetzung ueber ein Hilfselement
   * (textContent -> innerHTML). Sie maskierte drei Zeichen, escape() fuenf —
   * zwei Namen fuer dieselbe Aufgabe mit unterschiedlichem Umfang, und an der
   * Aufrufstelle war nicht zu sehen, welche der beiden man erwischt hatte.
   * Beide zeigen jetzt auf denselben Baustein.
   */
  const escape = EdHtml.escape;
  const esc    = EdHtml.escape;

  /* Zelle fuer eine Angabe aus dem verschluesselten Block.
   *
   * DER GRUND FUER DIESE FUNKTION: Bisher zeigten diese Spalten einen
   * Gedankenstrich, und zwar in ZWEI voellig verschiedenen Faellen — der
   * Einsatz hat keine Angaben, oder er hat welche, die sich nicht
   * entschluesseln lassen. Der zweite Fall ist ein Alarmzeichen und sah aus
   * wie der erste. Wer den Unterschied nicht sieht, merkt nicht, dass sein
   * Inhaltsschluessel nicht mehr passt — und erstellt als Naechstes eine
   * Sicherung.
   *
   * Jetzt:  –  keine Angaben      ⚠  vorhanden, aber nicht lesbar
   */
  function zelleGeschuetzt(m, wert, formatiere, klassen) {
    const kl = klassen ? klassen + ' ' : '';
    if (m._patFehler) {
      return `<td class="${kl}patfehler" title="Diese Angaben liegen verschlüsselt vor, `
           + `lassen sich mit dem aktuellen Schlüssel aber nicht lesen.">⚠</td>`;
    }
    const leer = wert == null || wert === '';
    return `<td class="${kl}${leer ? 'dash' : ''}">${leer ? '–' : formatiere(wert)}</td>`;
  }
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

  /* Artsymbol eines Einsatzes (E27, A7c). Die Liste kommt aus
   * dt_art_symbole() (diensttag_lib.php) und wird von der einbindenden Seite
   * als ART_SYMBOLE gesetzt — dieselbe Loesung wie bei CREW_ROLLEN (Befund
   * P9). Der Rueckfall haelt die Datei fuer sich lauffaehig; er ist die
   * Notloesung, nicht die Quelle. */
  const ART_FALLBACK = {
    air:    { zeichen: '🚁', text: 'luftgebunden' },
    ground: { zeichen: '🚑', text: 'bodengebunden' },
    '':     { zeichen: '◌',  text: 'ohne Zuordnung' }
  };
  function artSymbol(kind) {
    const alle = (typeof ART_SYMBOLE !== 'undefined' && ART_SYMBOLE) ? ART_SYMBOLE : ART_FALLBACK;
    return alle[kind || ''] || alle[''] || ART_FALLBACK[''];
  }

  /* ---- Spaltendefinition ----------------------------------------------
   * Eine Zeile je Spalte: Sortierschluessel, Kopftext, Klassen und die
   * beiden Funktionen fuer Sortierwert und Zellinhalt. Neue Spalte = ein
   * Eintrag hier, und sie erscheint auf beiden Seiten.
   *
   * SPALTEN NACH BESTAND (ab Web 6.2.0, A13d). `nurWenn` bekommt den Bestand
   * und entscheidet, ob die Spalte ueberhaupt erscheint. Der Gedanke ist
   * derselbe wie bei den Filterbloecken der Suche (GRUPPE_NUR_WENN) und bei
   * den Kacheln der Zeitraum-Uebersicht (E30): Eine Spalte, die im ganzen
   * Bestand leer bleibt, kostet auf schmalen Geraeten Platz und sagt nichts.
   *
   * MASSGEBLICH IST DER BESTAND, NICHT DIE TREFFERLISTE. Sonst verschwaende
   * eine Spalte, sobald ein Filter die betreffenden Einsaetze gerade
   * ausschliesst, und die Tabelle spraenge beim Tippen. Welche Liste der
   * Bestand ist, sagt die Seite mit setSpaltenBestand(); ohne diesen Aufruf
   * sind Bestand und Trefferliste dasselbe.
   */
  const SPALTEN = [
    /* Die Art als Symbolspalte. In der Tagesleiste steht sie am Namen des
       Rettungsmittels (E27) — den fuehrt diese Tabelle nicht, und ein
       Textkuerzel „luftgebunden" waere die breiteste Spalte fuer die
       schmalste Auskunft. Sie erscheint nur, wenn im Bestand ueberhaupt mehr
       als eine Art vorkommt; bei reiner Luftrettung bliebe in jeder Zeile
       dasselbe Zeichen stehen. */
    { key: 'art',   kopf: 'Art',                   thClass: 'c-art',
      nurWenn: liste => new Set(liste.map(m => m.kind || '')).size > 1,
      wert: m => m.kind || '',
      zelle: m => {
        const s = artSymbol(m.kind);
        return `<td class="c-art"><span class="artzeichen" title="${esc(s.text)}"`
             + ` aria-label="${esc(s.text)}">${esc(s.zeichen)}</span></td>`;
      } },
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
      zelle: m => zelleGeschuetzt(m, m._ort, v => esc(v)) },
    { key: 'age',   kopf: 'Alter',                 thClass: 'c-mid',
      wert: m => m._age == null ? -1 : m._age,
      zelle: m => zelleGeschuetzt(m, m._age, v => v, 'mono c-mid') },
    { key: 'dx',    kopf: 'Diagnose',              thClass: '',
      wert: m => (m._dx || '').toLowerCase(),
      zelle: m => zelleGeschuetzt(m, m._dx, v => esc(v)) },
    /* Winde und Bergwacht sind FAEHIGKEITEN einzelner Rettungsmittel (E29).
       Wer nie windet, sah bisher zwei dauerhaft leere Spalten — dieselbe
       Ueberlegung, die in der Suche schon die Filterbloecke ausblendet. */
    { key: 'winch', kopf: 'Winde',                 thClass: 'c-winde',
      nurWenn: liste => liste.some(m => m.winch),
      wert: m => m.winch ? 1 : 0,
      zelle: m => `<td class="checkcol c-winde">${m.winch ? '✓' : ''}</td>` },
    { key: 'bw',    kopf: 'Bergwacht',             thClass: 'c-bw',
      nurWenn: liste => liste.some(m => m.bergwacht),
      wert: m => m.bergwacht ? 1 : 0,
      zelle: m => `<td class="checkcol c-bw">${m.bergwacht ? '✓' : ''}</td>` },
    { key: 'sec',   kopf: 'Sekundär<br>Transport', thClass: 'c-sek',
      wert: m => m.secondary ? 1 : 0,
      zelle: m => `<td class="checkcol c-sek">${m.secondary ? '✓' : ''}</td>` },
    /* Fehleinsatz (E17, seit Web 6.1.0 erfassbar). Wie Winde und Bergwacht
       datengetrieben: Der Haken steht beiden Arten offen, gesetzt ist er
       selten, und eine Spalte voller leerer Zellen liest sich als Mangel. */
    { key: 'fehl',  kopf: 'Fehl<br>einsatz',       thClass: 'c-fehl',
      nurWenn: liste => liste.some(m => m.false_alarm),
      wert: m => m.false_alarm ? 1 : 0,
      zelle: m => `<td class="checkcol c-fehl">${m.false_alarm ? '✓' : ''}</td>` },
    /* Neutral beschriftet, nicht „Flug km" (Abschnitt 3.9/3.7.3). Diese
       Tabelle wird von zeitraum.php UND suche.php gemeinsam erzeugt; in der
       Suche stehen luft- und bodengebundene Einsaetze NEBENEINANDER, ein
       artabhaengiger Spaltenkopf ist dort gar nicht darstellbar. Die
       Flugterminologie bleibt allein den Kacheln vorbehalten (E32). */
    { key: 'km',    kopf: 'km',                    thClass: 'c-mid',
      wert: m => m.distance_m == null ? -1 : m.distance_m,
      zelle: m => `<td class="mono c-mid">${fmtKm(m.distance_m)}</td>` }
  ];

  /**
   * Baut eine Tabelle auf dem uebergebenen <table>-Element auf.
   *
   * opts.table        <table> mit <thead> und <tbody> (beide duerfen leer sein)
   * opts.sortKey      Voreinstellung, Standard 'day'
   * opts.sortAsc      Voreinstellung, Standard true
   *                   (zeitraum.php tut das historisch nicht — dort false)
   * opts.seite        Zeilen je Seite; 0 oder fehlend = alle auf einmal.
   *                   Siehe den Abschnitt „Seitengroesse" unten.
   * opts.onAfterDraw  wird nach jedem Zeichnen gerufen: (gesamt, gezeigt)
   *                   'gesamt'  Zeilen, die dem Filter entsprechen
   *                   'gezeigt' davon tatsaechlich gezeichnete
   *                   Ohne Seitengroesse sind beide Zahlen gleich — die
   *                   bisherigen Aufrufer lesen nur die erste und bleiben
   *                   damit richtig.
   *
   * SEITENGROESSE (Web 5.10.0).
   *
   * Bis dahin zeichnete diese Tabelle IMMER jeden Treffer. Auf der Suchseite
   * heisst das beim Oeffnen den gesamten Bestand: Jede Zeile ist ein <tr> mit
   * zehn Zellen, und der Aufbau geschieht bei jedem Tastendruck im Suchfeld
   * erneut. Bei einigen tausend Einsaetzen wird daraus eine spuerbare Pause
   * zwischen Anschlag und Anzeige — bezahlt fuer Zeilen, die niemand ansieht;
   * gesucht wird ueber die Filter, nicht durch Scrollen.
   *
   * Begrenzt wird nur die ANZEIGE. Gefiltert, sortiert und gezaehlt wird
   * weiterhin ueber den vollstaendigen Bestand — die Zeile ueber der Tabelle
   * nennt deshalb unveraendert die wahre Trefferzahl, und die Sortierung
   * bestimmt, welche Treffer oben stehen. Ein Nachladen auf Knopfdruck haengt
   * unter der Tabelle; es entsteht nur, wenn tatsaechlich etwas fehlt.
   *
   * Beim Sortieren bleibt eine erweiterte Ansicht erweitert: Wer 600 Zeilen
   * aufgeklappt hat und dann die Spalte wechselt, will sie nicht erneut
   * aufklappen. Neue Daten (setData) fangen wieder bei der ersten Seite an —
   * das ist ein anderer Filter und damit eine andere Liste.
   *
   * Rueckgabe: { setData, zeichne, sortKey, sortAsc, setSort }
   */
  function erzeuge(opts) {
    const table = opts.table;
    let sortKey = opts.sortKey || 'day';
    let sortAsc = opts.sortAsc !== false;
    const seite = Math.max(0, opts.seite || 0);   // 0 = ohne Begrenzung
    let sichtbar = seite;
    /* Der Sortierpfeil wird IMMER gezeigt (M6-10).
     *
     * Vorher gab es dafuer einen Schalter: Die Suche zeigte ihn sofort, die
     * Zeitraum-Uebersicht erst nach dem ersten Klick. Beide Tabellen sind
     * beim Oeffnen sortiert — die eine sagte es, die andere liess es raten.
     * Der Unterschied war historisch gewachsen, nicht gewollt. */
    let daten = [];
    /* Bestand fuer die Spaltensichtbarkeit (siehe SPALTEN oben). null heisst
     * „noch nicht gesetzt" — dann gilt die Trefferliste selbst. Das ist fuer
     * zeitraum.php richtig (dort ist die Liste der Zeitraum) und fuer
     * suche.php nicht (dort ist sie das Suchergebnis); die Suche setzt ihn
     * deshalb einmal beim Laden auf den Gesamtbestand. */
    let bestand = null;

    let thead = table.tHead;
    if (!thead) { thead = table.createTHead(); }
    let tbody = table.tBodies[0];
    if (!tbody) { tbody = table.createTBody(); }

    /* Die Nachladezeile gehoert zur Tabelle, nicht zur Seite: Sie entsteht
     * hier und haengt unmittelbar hinter dem <table>. Sonst muesste jede
     * Seite, die eine Seitengroesse setzt, auch noch ein Element dafuer
     * vorsehen — und die erste, die es vergisst, begrenzt still. */
    let mehrZeile = null, mehrKnopf = null, mehrAlleKnopf = null;
    if (seite > 0) {
      mehrZeile = document.createElement('p');
      mehrZeile.className = 'mehrzeile';
      mehrZeile.hidden = true;
      mehrKnopf = document.createElement('button');
      mehrKnopf.type = 'button';
      mehrKnopf.className = 'btn-plain';
      mehrKnopf.addEventListener('click', () => mehrZeigen(sichtbar + seite));
      mehrAlleKnopf = document.createElement('button');
      mehrAlleKnopf.type = 'button';
      mehrAlleKnopf.className = 'btn-plain';
      mehrAlleKnopf.addEventListener('click', () => mehrZeigen(Infinity));
      mehrZeile.appendChild(mehrKnopf);
      mehrZeile.appendChild(mehrAlleKnopf);
      table.insertAdjacentElement('afterend', mehrZeile);
    }

    /** Die Spalten, die dieser Bestand rechtfertigt (A13d). */
    function sichtbareSpalten() {
      const basis = bestand !== null ? bestand : daten;
      return SPALTEN.filter(sp => !sp.nurWenn || sp.nurWenn(basis));
    }

    function zeichneKopf(spalten) {
      const tr = document.createElement('tr');
      spalten.forEach(sp => {
        const th = document.createElement('th');
        th.className = 'sortable' + (sp.thClass ? ' ' + sp.thClass : '');
        th.dataset.key = sp.key;
        th.innerHTML = sp.kopf;
        if (sp.key === sortKey) {
          const pfeil = document.createElement('span');
          pfeil.className = 'arrow';
          pfeil.textContent = sortAsc ? ' ▲' : ' ▼';
          th.appendChild(pfeil);
        }
        th.addEventListener('click', () => {
          if (sortKey === sp.key) { sortAsc = !sortAsc; } else { sortKey = sp.key; sortAsc = true; }
          zeichne();
          if (opts.onSortChange) { opts.onSortChange(sortKey, sortAsc); }
        });
        tr.appendChild(th);
      });
      thead.innerHTML = '';
      thead.appendChild(tr);
    }

    function zeichne() {
      const spalten = sichtbareSpalten();
      zeichneKopf(spalten);
      /* Sortiert wird ueber ALLE Spalten, nicht nur die sichtbaren: Ein
       * geteilter Link kann nach einer Spalte sortieren, die der eigene
       * Bestand nicht zeigt. Die Reihenfolge stimmt dann trotzdem, nur der
       * Pfeil hat keinen Kopf, an dem er stehen koennte. */
      const sp = SPALTEN.find(s => s.key === sortKey) || SPALTEN[0];
      const sortiert = daten.slice().sort((a, b) => {
        const x = sp.wert(a), y = sp.wert(b);
        const r = (x > y) - (x < y);
        return sortAsc ? r : -r;
      });
      const gezeigt = seite > 0 ? sortiert.slice(0, sichtbar) : sortiert;
      tbody.innerHTML = '';
      gezeigt.forEach(m => {
        const tr = document.createElement('tr');
        tr.className = 'clickable';
        tr.dataset.mid = m.id;
        tr.innerHTML = spalten.map(s => s.zelle(m)).join('');
        /* Die Zeile ist die Schaltflaeche. Ohne tabindex und Tastenbehandlung
         * waere das Oeffnen eines Einsatzes eine reine Mausfunktion — die
         * Hervorhebung beim Ueberfahren gaebe es dann fuer die Tastatur zwar
         * per :focus-visible, aber nichts zum Ausloesen (Auftragspunkt 9).
         * role="link" statt "button", weil die Handlung ein Seitenwechsel ist
         * und der Screenreader das ansagen soll. */
        tr.tabIndex = 0;
        tr.setAttribute('role', 'link');
        const oeffne = () => { location.href = 'einsatz.php?id=' + m.id; };
        tr.addEventListener('click', oeffne);
        tr.addEventListener('keydown', ev => {
          /* Leertaste bewusst mit: Sie ist die uebliche Ausloesung fuer
           * fokussierte Bedienelemente, und ohne preventDefault scrollt die
           * Seite stattdessen weg. */
          if (ev.key === 'Enter' || ev.key === ' ' || ev.key === 'Spacebar') {
            ev.preventDefault();
            oeffne();
          }
        });
        tbody.appendChild(tr);
      });
      zeichneMehr(sortiert.length, gezeigt.length);
      if (opts.onAfterDraw) { opts.onAfterDraw(sortiert.length, gezeigt.length); }
    }

    /* Nachladen. Der Fokus wandert NUR DANN in die erste neue Zeile, wenn die
     * Zeile mit den Schaltflaechen dabei verschwindet — sonst stuende er nach
     * dem letzten Klick auf einem Element, das es nicht mehr gibt, und faellt
     * an den Seitenanfang zurueck. Bleibt der Knopf stehen, bleibt auch der
     * Fokus dort: Wer mit der Tastatur weiterladen will, muesste sich sonst
     * durch zweihundert Zeilen zurueckarbeiten. */
    function mehrZeigen(neu) {
      const vorher = tbody.children.length;
      sichtbar = neu;
      zeichne();
      const naechste = tbody.children[vorher];
      if (mehrZeile.hidden && naechste) { naechste.focus(); }
    }

    /* Nachladezeile beschriften. Sie verschwindet, sobald nichts mehr fehlt —
     * eine Schaltflaeche, die nichts mehr zu tun hat, ist eine Frage an die
     * NutzerIn, die sie nicht beantworten kann. Der zweite Knopf („alle")
     * erscheint nur, wenn er mehr bewirkt als der erste; bei 40 fehlenden
     * Zeilen taeten beide dasselbe. */
    function zeichneMehr(gesamt, gezeigt) {
      if (!mehrZeile) { return; }
      const fehlend = gesamt - gezeigt;
      mehrZeile.hidden = fehlend <= 0;
      if (fehlend <= 0) { return; }
      mehrKnopf.textContent = 'Weitere ' + Math.min(seite, fehlend) + ' anzeigen';
      mehrAlleKnopf.hidden = fehlend <= seite;
      mehrAlleKnopf.textContent = 'Alle ' + gesamt + ' anzeigen';
    }

    function setData(liste) {
      daten = liste || [];
      // Neue Liste, neue erste Seite (siehe „Seitengroesse" oben).
      sichtbar = seite;
      zeichne();
    }

    function setSort(key, asc) {
      if (!SPALTEN.some(s => s.key === key)) { return; }
      sortKey = key; sortAsc = !!asc;
    }

    /* Bestand fuer die Spaltensichtbarkeit setzen. Zeichnet NICHT selbst: Die
     * Aufrufer setzen ihn unmittelbar vor oder nach setData(), und zweimal
     * zeichnen waere zweimal dieselbe Tabelle. */
    function setSpaltenBestand(liste) { bestand = liste || []; }

    return {
      setData, zeichne, setSort, setSpaltenBestand,
      get sortKey() { return sortKey; },
      get sortAsc() { return sortAsc; }
    };
  }

  return { erzeuge, SPALTEN, esc, escape, fmtTag, fmtDur, fmtKm, extractOrt, artSymbol };
})();
