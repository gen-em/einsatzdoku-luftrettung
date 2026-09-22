/* Gen-EM NAdoku — gemeinsame Einsatztabelle fuer zeitraum.php und suche.php.
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
 * Erwartet aus der Seite: EdHtml (assets/html.js) und EdFormat
 * (assets/format.js) -- Letzteres steht seit Schritt 15 AP8d im Kopf JEDER
 * Seite und muss deshalb nirgends mehr eingebunden werden. Sonst reine
 * DOM-Arbeit.
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
   * Inhaltsschluessel nicht mehr passt — und erstellt als Naechstes ein
   * Backup.
   *
   * Jetzt:  –  keine Angaben      ⚠  vorhanden, aber nicht lesbar
   *
   * MASKIERT WIRD HIER, NICHT IN DER FORMATIERUNG (Backlog Nr. 22, F-20).
   * Bis Web 7.2.0 war das Maskieren Sache der aufrufenden Seite: Einsatzort
   * und Diagnose kamen mit `v => esc(v)`, das Alter mit `v => v` — weil es
   * eine Zahl ist. Aus dem Formular ist es das auch (parseInt) — aber das
   * FELD ist keine Zahl: `age` liegt im pat_blob, und der ist freies JSON,
   * das der Server nie im Klartext sieht und deshalb nicht prüfen kann. Die
   * Zelle wird per innerHTML gesetzt; Markup darin lief in dem Fenster los,
   * in dem der Inhaltsschlüssel liegt.
   *
   * DER WEG HINEIN IST DIE WIEDERHERSTELLUNG EINER BACKUP, nicht der
   * Import. Bis Web 7.3.1 stand hier das Gegenteil („import.js übernimmt
   * pat.age als rohen Zellenwert"); das ist nachgemessen falsch —
   * import_profiles.js bildet `pat_alter` mit parse:['alterJahre'] ab, und
   * PARSERS.ganzzahl verlangt /^-?\d+$/. `47<img …>` wird verworfen, nicht
   * übernommen. api/backup_restore.php dagegen übernimmt den inneren
   * Chiffretext unverändert, wie es sein muss — und im Adminbereich
   * schreibt „Einspielen" ein FREMDES Backup in ein Konto.
   *
   * Die Entscheidung, eine Angabe zu maskieren, darf deshalb nicht an der
   * Aufrufstelle liegen: Sie war an zwei von sechs Stellen falsch getroffen,
   * und die nächste neue Spalte hätte dieselbe Wahl noch einmal gehabt.
   * `formatiere` bekommt den Wert jetzt BEREITS MASKIERT und darf ihn nur
   * noch umschichten — wer rohes Markup braucht, kann diese Funktion nicht
   * benutzen (heute braucht das niemand).
   */
  function zelleGeschuetzt(m, wert, formatiere, klassen) {
    const kl = klassen ? klassen + ' ' : '';
    if (m._patFehler) {
      /* Das Warnzeichen kommt aus dem Symbolvorrat (E-P3-18), nicht als
         Unicode-Zeichen — es faerbt sich mit und sieht ueberall gleich aus. */
      return `<td class="${kl}patfehler" title="Diese Angaben liegen verschlüsselt vor, `
           + `lassen sich mit dem aktuellen Schlüssel aber nicht lesen.">`
           + edSymbol('warnung', '', 'nicht lesbar') + `</td>`;
    }
    const leer = wert == null || wert === '';
    const text = leer ? '–' : (formatiere ? formatiere(esc(wert)) : esc(wert));
    return `<td class="${kl}${leer ? 'dash' : ''}">${text}</td>`;
  }
  /* Tag, Dauer und Strecke rechnet seit Schritt 15 AP8d die Zentrale
   * `EdFormat` (assets/format.js). Hier stehen nur noch die drei
   * Weiterleitungen -- sie halten `EdMissionTable.fmtTag`/`.fmtDur`/
   * `.fmtKmZahl` gueltig (zeitraum.php und index.php nehmen sie als Alias)
   * und machen zugleich sichtbar, dass es NUR NOCH EINE Rechnung gibt.
   * Dieselbe Bauform wie bei esc/escape zwei Absaetze weiter oben.
   *
   * DER LEERWERT STEHT HIER, NICHT IN DER ZENTRALE: Jede Aufrufstelle hat
   * ihren eigenen, und `EdFormat` hat bewusst keinen festen. */

  /* Leerwert: der Leerstring.
   *
   * EINE BENANNTE AENDERUNG. Die alte Fassung hatte keine Wache -- sie warf
   * bei `null` eine TypeError, und aus einem ISO-Zeitstempel machte sie
   * still '14T10:00:00Z.08.2026'. Einen Leerwert gab es also gar nicht, den
   * man haette uebernehmen koennen. Die Formpruefung der Zentrale liefert
   * statt des Abbruchs nichts: Aus "Seite bricht ab" wird "Angabe fehlt".
   * Erreichbar ist das ueber `api/suchindex.php`, das `day` aus
   * `fmt_local(started_at, 'Y-m-d')` bildet -- fehlt `started_at`, steht
   * dort ein Gedankenstrich statt eines Datums. */
  function fmtTag(iso) { return EdFormat.tag(iso, ''); }
  /* Leerwert: 'kein Ende' -- NICHT der Leerstring. Der Text ist der
   * Vertrag dieser Weiterleitung nach aussen; heute faengt zwar jede
   * Aufrufstelle den Leerfall selbst ab (zelleDauer zeichnet daraus eine
   * rote Plakette), aber die Funktion wird exportiert, und wer sie ohne
   * Wache ruft, bekommt weiterhin 'kein Ende'.
   *
   * BENANNTE AENDERUNG: '60min' gibt es nicht mehr. Die alte Fassung
   * rechnete Stunden und Minuten getrennt und schrieb bei 3599 s '60min',
   * bei 7199 s '1h 60min'; die Zentrale rundet zuerst auf ganze Minuten und
   * teilt dann -- 3599 s ergibt '1h 00min'. Die zweistellige Minute hatte
   * diese Fassung schon, sie war die Vorlage dafuer. */
  function fmtDur(s) { return EdFormat.dauer(s, 'kein Ende'); }
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
   * Notloesung, nicht die Quelle.
   *
   * DER TYP GEHT VOR DER BETRIEBSART (E-S9-13, Web 16.0.0) — dieselbe Regel
   * wie in dt_art_symbol() auf der Serverseite, und aus demselben Grund: Das
   * Typzeichen sagt, WORUM es geht, die Betriebsart sagt, WOMIT. Ohne diese
   * zweite Liste zeichnete die Einsatztabelle die Betriebsart, waehrend die
   * Leiste daneben den Typ zeichnet — zwei Zeichen fuer denselben Tag.
   * TYP_SYMBOLE kommt aus dt_typ_symbole() und kennt 'standard' bewusst
   * nicht: Dessen Zeichen IST das der Betriebsart. */
  const ART_FALLBACK = {
    air:    { symbol: 'hubschrauber',   text: 'luftgebunden' },
    ground: { symbol: 'fahrzeug',       text: 'bodengebunden' },
    '':     { symbol: 'ohne-zuordnung', text: 'ohne Zuordnung' }
  };
  function artSymbol(kind, typ) {
    const alle = (typeof ART_SYMBOLE !== 'undefined' && ART_SYMBOLE) ? ART_SYMBOLE : ART_FALLBACK;
    const art  = alle[kind || ''] || alle[''] || ART_FALLBACK[''];
    if (!typ || typ === 'standard') { return art; }
    const typen = (typeof TYP_SYMBOLE !== 'undefined' && TYP_SYMBOLE) ? TYP_SYMBOLE : {};
    if (!typen[typ]) { return art; }
    return { symbol: typen[typ].symbol, text: typen[typ].text + ', ' + art.text };
  }

  /* ---- Bausteine der Zellen (P3/O3) ------------------------------------- */

  /** Haken aus dem Symbolvorrat, dunkelblau (E-P3-32). */
  function HAKEN() { return edSymbol('haken', 'tabelle-haken', 'ja'); }

  /** Dauer-Zelle: „kein Ende" ist eine rote Plakette, keine Zahl (E-P3-32). */
  function zelleDauer(s) {
    return s == null
      ? '<span class="plakette plakette-rot">kein Ende</span>'
      : fmtDur(s);
  }

  /** Kilometer ohne Einheit, eine Nachkommastelle, deutsches Komma.
   *
   *  Leerwert: ein fertiges <span class="dash">. Es bleibt HIER und wandert
   *  nicht in die Zentrale -- die Zelle setzt ihren Inhalt per innerHTML,
   *  andere Aufrufstellen von EdFormat.km() schieben ihr Ergebnis durch
   *  esc(), und dort stuende das Markup buchstaeblich auf dem Bildschirm. */
  function fmtKmZahl(m) {
    return EdFormat.km(m, { einheit: false, leer: '<span class="dash">–</span>' });
  }

  /* ---- Die Kachel (E-P3-32, Mockup 10) ----------------------------------
   *
   * Unter 720 px gibt es keine Einsatztabelle: Bei 360 px bekamen Einsatzort
   * und Diagnose — die beiden wichtigsten Spalten — null Pixel Breite und
   * verschwanden ohne Hinweis, waehrend sieben Zahlenspalten stehen blieben
   * (Befund B-P3-03). Die Kachel dreht das um: Zeile 1 Ort und km, Zeile 2
   * Diagnose, Zeile 3 Dauer, Alter und Plaketten.
   *
   * EIN ERZEUGER FUER ALLE DREI SEITEN. opts:
   *   farbe     Farbstreifen (Spurfarbe des Einsatzes; Tagesuebersicht)
   *   artDatum  true = Artzeichen, Datum und Beginn als erstes Element
   *             (Suche und Zeitraum, E-P3-32)
   *   knapp     true = Diagnose einzeilig (Suche und Zeitraum)
   *   hervor    Funktion(maskiert) -> HTML; hebt die Suchwoerter hervor
   *             (Suche, E-P3-36)
   */
  function kachel(m, opts) {
    opts = opts || {};
    var k = 'kachel' + (opts.knapp ? ' kachel-knapp' : '');
    var h = '<a class="' + k + '" href="einsatz.php?id=' + encodeURIComponent(m.id) + '">';
    h += '<span class="kachel-streifen"'
       + (opts.farbe ? ' style="background:' + esc(opts.farbe) + '"' : '') + '></span>';
    /* Artzeichen, Datum und Beginn stehen in EINER Zeile (Mockup 27):
       „<Zeichen> 22.08.2026 · 07:42". Getrennte Bloecke haetten das Zeichen
       unter das Datum geschoben, sobald die Kachel schmal wird. */
    h += '<span class="kachel-zeit">';
    if (opts.artDatum) {
      var s = artSymbol(m.kind, m.day_typ);
      h += '<span class="kachel-art">'
         + (typeof edSymbol === 'function' ? edSymbol(s.symbol, '', s.text) : esc(s.text))
         + '</span>';
    }
    h += (opts.artDatum && m.day ? '<span class="kachel-datum">' + fmtTag(m.day) + '</span> · ' : '')
       + esc(m.start_hhmm || '–') + '</span>';

    h += '<span class="kachel-rumpf">';
    h += '<span class="kachel-kopf">'
       + '<span class="kachel-ort">' + kachelGeschuetzt(m, m._ort, opts.hervor) + '</span>'
       + '<span class="kachel-km">' + (m.distance_m == null ? ''
           : Math.round(m.distance_m / 1000) + ' km') + '</span></span>';
    h += '<span class="kachel-dx">' + kachelGeschuetzt(m, m._dx, opts.hervor) + '</span>';

    var fuss = [];
    if (m.duration_s != null) { fuss.push(esc(fmtDur(m.duration_s))); }
    if (m._age != null && m._age !== '' && !m._patFehler) { fuss.push(esc(m._age) + ' J.'); }
    var plaketten = [];
    function pl(ton, text) {
      plaketten.push('<span class="plakette plakette-' + ton + '">' + text + '</span>');
    }
    if (m.winch)          { pl('orange', 'Winde'); }
    if (m.bergwacht)      { pl('orange', 'Bergwacht'); }
    if (m.secondary)      { pl('blau',   'Sekundär'); }
    if (m.false_alarm)    { pl('rot',    'Fehleinsatz'); }
    if (m.duration_s == null) { pl('rot', 'kein Ende'); }
    h += '<span class="kachel-fuss">'
       + fuss.join(' · ')
       + (fuss.length && plaketten.length ? ' ' : '')
       + plaketten.join('') + '</span>';
    h += '</span></a>';
    return h;
  }

  /** Geschuetzte Angabe in der Kachel: – fuer „keine", Warnzeichen fuer
   *  „vorhanden, aber nicht lesbar" — dieselbe Unterscheidung wie in der
   *  Tabelle (zelleGeschuetzt). */
  function kachelGeschuetzt(m, wert, hervor) {
    if (m._patFehler) {
      return '<span class="patfehler" title="Diese Angaben liegen verschlüsselt vor, '
           + 'lassen sich mit dem aktuellen Schlüssel aber nicht lesen.">'
           + edSymbol('warnung', '', 'nicht lesbar') + '</span>';
    }
    if (wert == null || wert === '') { return '–'; }
    /* hervor() bekommt den Wert BEREITS MASKIERT und darf ihn nur noch
     * umschichten — dieselbe Arbeitsteilung wie bei zelleGeschuetzt. */
    return hervor ? hervor(esc(wert)) : esc(wert);
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
   *
   * WINDE UND BERGWACHT FOLGEN SEIT SCHRITT 15 AP9 DER FAEHIGKEIT, NICHT DEM
   * BESTAND (E-ZE-31). `nurWenn` bekommt dafuer ein ZWEITES Argument: die
   * Faehigkeiten der Ansicht, `{winch: bool, bergwacht: bool}`. Die Seite
   * setzt sie mit setFaehigkeiten() -- WELCHE Tage sie zusammenfasst, ist
   * ihre Sache und nicht die dieses Moduls: Die Zeitraumuebersicht zaehlt
   * nur LUFT-Diensttage (api/range.php filtert auf `d.kind = 'air'`), die
   * Suche zaehlt Luft UND Boden. Beides ist so entschieden.
   *
   * OHNE setFaehigkeiten() GILT WEITER DER BESTAND. Das ist kein Vergessen,
   * sondern der Rueckfall: Eine Seite, die das Modul einbindet und die
   * Faehigkeiten nicht kennt, soll ihre Spalten nicht stillschweigend
   * verlieren. Der Unterschied zwischen beiden Regeln ist gerade der Fall
   * „Faehigkeit ja, Einsatz nein" -- die Spalte steht dann leer da, statt zu
   * fehlen, und wer nachtragen will, sieht, dass es sie gibt.
   */
  const SPALTEN = [
    /* Die Art als Symbolspalte. In der Tagesleiste steht sie am Namen des
       Rettungsmittels (E27) — den fuehrt diese Tabelle nicht, und ein
       Textkuerzel „luftgebunden" waere die breiteste Spalte fuer die
       schmalste Auskunft. Sie erscheint nur, wenn im Bestand ueberhaupt mehr
       als eine Art vorkommt; bei reiner Luftrettung bliebe in jeder Zeile
       dasselbe Zeichen stehen. */
    /* Farbstreifen wie in der Tagesuebersicht (E-P3-36, Mockup 28). Die
       Farbe steht als `_col` an der Zeile; wer sie nicht setzt, bekommt die
       Spalte gar nicht erst. Sie traegt dieselbe Bedeutung wie dort: die
       Spurfarbe des Einsatzes an SEINEM Diensttag — derselbe Einsatz hat in
       Suche und Tageskarte dieselbe Farbe. Nicht sortierbar im Sinn einer
       Ordnung, aber der Kopf bleibt anklickbar wie alle (er sortiert dann
       nach Farbe, was niemandem schadet). */
    { key: 'col',   kopf: '',                      thClass: 'streifen-spalte',
      nurWenn: liste => liste.some(m => m._col),
      wert: m => m._col || '',
      zelle: m => '<td class="streifen-spalte"><span class="streifen"'
        + (m._col ? ' style="background:' + esc(m._col) + '"' : '') + '></span></td>' },
    { key: 'art',   kopf: 'Art',                   thClass: '',
      nurWenn: liste => new Set(liste.map(m => m.kind || '')).size > 1,
      wert: m => m.kind || '',
      zelle: m => {
        const s = artSymbol(m.kind, m.day_typ);
        /* Seit P3/O2 kommt das Zeichen aus dem Symbolvorrat statt als Emoji
           (E-P3-18). edSymbol() erzeugt dieselbe Zeichenkette wie ui_symbol()
           in PHP; faellt assets/symbol.js aus, bleibt das Wort. */
        const zeichen = (typeof edSymbol === 'function')
          ? edSymbol(s.symbol, '', s.text)
          : esc(s.text);
        return `<td>${zeichen}</td>`;
      } },
    /* DIE EINSATZNUMMER DES TAGES (E-ZE-33, Schritt 15 AP9b). Sie kam mit
       der Tagesuebersicht hinzu und ist dort keine Zierspalte, sondern die
       VERBINDUNG ZUR KARTE: Pins und Popups heissen „Einsatz <Nr.>". Faellt
       sie weg, steht die Nummer im Kartenpopup und sonst nirgends mehr.

       `_no` vergibt die Seite, nicht der Server -- api/day.php liefert
       aufsteigend nach Beginn, index.php zaehlt durch. Suche und
       Zeitraumuebersicht setzen sie nicht und bekommen die Spalte deshalb
       gar nicht erst: Eine laufende Nummer ueber mehrere Diensttage hinweg
       waere keine Nummer, sondern eine Zeilenzahl. */
    { key: 'no',    kopf: 'Nr.',                   thClass: 'mitte-spalte',
      nurWenn: liste => liste.some(m => m._no != null),
      wert: m => m._no == null ? -1 : m._no,
      zelle: m => `<td class="mitte-spalte">${m._no == null ? '' : m._no}</td>` },
    /* DAS DATUM HAT KEIN `nurWenn`, UND DAS IST EINE KORREKTUR.
       In AP9b stand hier zuerst „nur zeigen, wenn es mehrere Tage gibt" --
       die Tagesuebersicht hat einen, und die Spalte waere von selbst
       weggefallen. Der Bildvergleich hat gezeigt, was das anrichtet: Im
       Monat Januar des Referenzbestands liegen beide Einsaetze auf DEMSELBEN
       Tag, und die Zeitraumuebersicht verlor dort ihre Datumsspalte --
       waehrend sie weiter nach ihr sortierte. Die Beschriftung las sich dann
       „älteste zuerst" ohne Spaltennamen: sortiert nach etwas, das nicht
       dasteht.
       Die Artspalte darf das (ein Zeichen, das sich nie aendert, sagt
       nichts), das Datum nicht (es sagt, WELCHER Tag). Wer die Spalte nicht
       will, nennt sie in `opts.ohne` -- so macht es index.php. */
    { key: 'day',   kopf: 'Datum',                 thClass: '',
      wert: m => m.day,
      zelle: m => `<td>${fmtTag(m.day)}</td>` },
    /* SORTIERT WIRD CHRONOLOGISCH, NICHT ALPHABETISCH (E-ZE-32).
       `start_hhmm` ist eine Zeichenkette, und bei einem Dienst ueber
       Mitternacht -- laut Handbuch „der klassische Fall" -- stuende damit
       01:10 vor 23:50. Still: keine Meldung, keine Luecke, nur eine falsche
       Reihenfolge, die richtig aussieht. `start_sort` kommt seit Schritt 15
       AP9b aus allen drei Endpunkten und traegt 'Y-m-d H:i' in Ortszeit.
       Der Rueckfall haelt die Datei fuer sich lauffaehig; er ist NICHT die
       Loesung, sondern das Verhalten von vorher. */
    { key: 'start', kopf: 'Beginn',                thClass: '',
      wert: m => m.start_sort || ((m.day || '') + ' ' + (m.start_hhmm || '')),
      zelle: m => `<td>${m.start_hhmm}</td>` },
    /* NOWRAP STATT UMBRUCH (F-N1-G). „1h 06min" passte nicht in die Spalte
       und brach nach der Stunde um — eine Dauer auf zwei Zeilen liest sich
       wie zwei Angaben. Die Spalte richtet sich jetzt nach ihrem laengsten
       Wert; das kostet in der Breite weniger als der Umbruch an Klarheit. */
    { key: 'dur',   kopf: 'Dauer',                 thClass: 'zahl-spalte',
      wert: m => m.duration_s == null ? -1 : m.duration_s,
      zelle: m => `<td class="zahl-spalte zeit-spalte">${zelleDauer(m.duration_s)}</td>` },
    { key: 'site',  kopf: 'Einsatzort',            thClass: '',
      wert: m => (m._ort || '').toLowerCase(),
      zelle: (m, ctx) => zelleGeschuetzt(m, m._ort, ctx && ctx.hervor) },
    { key: 'age',   kopf: 'Alter',                 thClass: 'zahl-spalte',
      wert: m => m._age == null ? -1 : m._age,
      zelle: m => zelleGeschuetzt(m, m._age, null, 'zahl-spalte') },
    { key: 'dx',    kopf: 'Diagnose',              thClass: '',
      wert: m => (m._dx || '').toLowerCase(),
      zelle: (m, ctx) => zelleGeschuetzt(m, m._dx, ctx && ctx.hervor) },
    /* Winde und Bergwacht sind FAEHIGKEITEN einzelner Rettungsmittel (E29)
       — und seit Schritt 15 AP9 entscheidet genau das ueber die Spalte, nicht
       mehr der Bestand. Siehe den Kopfkommentar oben; `f` ist null, solange
       die Seite keine Faehigkeiten gesetzt hat. */
    { key: 'winch', kopf: 'Winde',                 thClass: 'haken-spalte',
      katalog: 'winch',
      nurWenn: (liste, f) => f ? !!f.winch : liste.some(m => m.winch),
      wert: m => m.winch ? 1 : 0,
      zelle: m => `<td class="haken-spalte">${m.winch ? HAKEN() : ''}</td>` },
    { key: 'bw',    kopf: 'Bergwacht',             thClass: 'haken-spalte',
      katalog: 'bergwacht',
      nurWenn: (liste, f) => f ? !!f.bergwacht : liste.some(m => m.bergwacht),
      wert: m => m.bergwacht ? 1 : 0,
      zelle: m => `<td class="haken-spalte">${m.bergwacht ? HAKEN() : ''}</td>` },
    /* WEICHES TRENNZEICHEN STATT <br> (F-N1-G). „Sekundaertransport" ist EIN
       Wort; das harte <br> trennte es ohne Bindestrich in zwei
       („Sekundaer Transport"), und genau so las es sich auch. `&shy;` laesst
       den Browser entscheiden: Passt es in eine Zeile, steht es in einer;
       muss er trennen, setzt er den Bindestrich dazu. Dasselbe bei
       „Fehleinsatz". */
    { key: 'sec',   kopf: 'Sekundär&shy;transport', thClass: 'haken-spalte',
      katalog: 'secondary',
      wert: m => m.secondary ? 1 : 0,
      zelle: m => `<td class="haken-spalte">${m.secondary ? HAKEN() : ''}</td>` },
    /* Fehleinsatz (E17, seit Web 6.1.0 erfassbar). Wie Winde und Bergwacht
       datengetrieben: Der Haken steht beiden Arten offen, gesetzt ist er
       selten, und eine Spalte voller leerer Zellen liest sich als Mangel. */
    { key: 'fehl',  kopf: 'Fehl&shy;einsatz',       thClass: 'haken-spalte',
      nurWenn: liste => liste.some(m => m.false_alarm),
      wert: m => m.false_alarm ? 1 : 0,
      zelle: m => `<td class="haken-spalte">${m.false_alarm ? HAKEN() : ''}</td>` },
    /* Neutral beschriftet, nicht „Flug km" (Abschnitt 3.9/3.7.3). Diese
       Tabelle wird von zeitraum.php UND suche.php gemeinsam erzeugt; in der
       Suche stehen luft- und bodengebundene Einsaetze NEBENEINANDER, ein
       artabhaengiger Spaltenkopf ist dort gar nicht darstellbar. Die
       Flugterminologie bleibt allein den Kacheln vorbehalten (E32). */
    /* Die Zelle nennt nur die ZAHL — die Einheit steht im Spaltenkopf, und
       „38,4 km" in jeder Zeile unter einem Kopf „km" sagt sie doppelt
       (Mockup 04). Wer die Einheit im Fliesstext braucht, ruft
       EdFormat.km() ohne `einheit: false`. Die eigene Fassung mit Einheit
       (`fmtKm`) hatte keinen einzigen Aufrufer und ist in AP8d entfallen. */
    { key: 'km',    kopf: 'km',                    thClass: 'zahl-spalte',
      wert: m => m.distance_m == null ? -1 : m.distance_m,
      zelle: m => `<td class="zahl-spalte">${fmtKmZahl(m.distance_m)}</td>` }
  ];

  /* ---- Der Feldkatalog behaelt seinen Griff auf die Tabelle -------------
   *
   * Drei Spalten oben tragen `katalog`: Sie gehoeren zu einem Feld mit
   * 'day_col' in mission_fields.php. Bis Schritt 15 AP9b galt dieser
   * Schluessel NUR fuer die Tagesuebersicht, und die baute ihre Zeilen
   * selbst; Suche und Zeitraum fuehrten dieselben drei Spalten ein zweites
   * Mal, von Hand. Jetzt gibt es einen Erzeuger -- und damit die Frage, wer
   * ueber die Spalten bestimmt.
   *
   * DIE ANTWORT IST GETEILT, und beide Haelften stehen hier:
   *
   *   Der KATALOG bestimmt, WELCHE Spalten es gibt. Faellt ein 'day_col'
   *   weg, faellt die Spalte -- auf allen drei Seiten. Kommt eines hinzu,
   *   erscheint die Spalte, ohne dass hier jemand etwas eintraegt
   *   (Backlog Nr. 10, „Feldkatalog statt Sonderfall").
   *
   *   DIESE DATEI bestimmt, WIE sie aussehen und in welcher REIHENFOLGE sie
   *   stehen. Das ist E-ZE-34: Beschriftung, Ausrichtung und
   *   Hakenreihenfolge folgen dem Modul. Deshalb heisst die Spalte hier
   *   „Sekundär&shy;transport" mit weichem Trennzeichen und nicht
   *   „Sekundär<br>Transport" wie im Katalog, und deshalb stehen Winde und
   *   Bergwacht VOR dem Sekundaertransport, obwohl der Katalog sie
   *   dahinter fuehrt.
   *
   * WAS DAS KOSTET, und es gehoert gesagt: 'day_col' heisst ab jetzt „Spalte
   * in JEDER Einsatztabelle", nicht mehr „Spalte in der Tagesuebersicht".
   * Wer den Schluessel an einem Feld entfernt, nimmt die Spalte auch aus
   * Suche und Zeitraumuebersicht. Das ist die Folge davon, dass es nur noch
   * einen Erzeuger gibt.
   *
   * OHNE `KATALOG_SPALTEN` bleibt alles, wie es ist. Eine Seite, die die
   * Liste nicht setzt, bekommt die drei Spalten unveraendert -- derselbe
   * benannte Rueckfall wie bei setFaehigkeiten(). Eine Zentrale, die bei
   * fehlender Zuarbeit still etwas WEGLAESST, ist schlimmer als gar keine.
   */
  function katalogListe() {
    return (typeof KATALOG_SPALTEN !== 'undefined' && Array.isArray(KATALOG_SPALTEN))
      ? KATALOG_SPALTEN : null;
  }

  /* Ein Spaltenkopf ALS REINER TEXT -- fuer das Sortierblatt und fuer
   * `data-label`. Beides zeigte bis Schritt 15 AP9b die ENTITAET roh:
   * „Sekundär&shy;transport" und „Fehl&shy;einsatz" standen so im mobilen
   * Blatt von suche.php und zeitraum.php, seit es das Blatt gibt. Die alte
   * Zeile streifte nur die TAGS ab (`/<[^>]*>/g`), und ein `&shy;` ist kein
   * Tag; die Seite maskierte den Text danach mit esc(), und aus dem `&`
   * wurde `&amp;`.
   *
   * Der Umweg ueber ein Wegwerf-Element loest jede Entitaet, nicht die drei,
   * die einem gerade einfallen. Die Tags werden VORHER durch ein Leerzeichen
   * ersetzt: textContent wuerde `Sekundär<br>Transport` sonst zu
   * „SekundärTransport" zusammenziehen. Das weiche Trennzeichen bleibt als
   * U+00AD stehen — unsichtbar, und es erlaubt dem Blatt dieselbe Trennung
   * wie dem Tabellenkopf.
   *
   * Der Text stammt aus dieser Datei oder aus dem Feldkatalog, nie aus einer
   * Eingabe; dieselbe Zeichenkette geht zwei Zeilen weiter ohnehin als
   * innerHTML in den Kopf. */
  function nurText(html) {
    const el = document.createElement('div');
    el.innerHTML = String(html == null ? '' : html).replace(/<[^>]*>/g, ' ');
    return el.textContent.replace(/\s+/g, ' ').trim();
  }

  /** Eine Spalte, die der Katalog kennt und diese Datei nicht: generisch. */
  function katalogSpalte(d) {
    const haken = d.art === 'check';
    return {
      key: 'dc:' + d.col,
      kopf: d.label || d.col,
      thClass: (haken ? 'haken-spalte ' : '') + (d.klasse || ''),
      katalog: d.col,
      /* Haengt das Feld an einer Faehigkeit ('cap_gate'), folgt die Spalte
         ihr -- genau wie Winde und Bergwacht oben (E-ZE-31). */
      nurWenn: d.cap
        ? (liste, f) => f ? !!f[d.cap] : liste.some(m => m[d.col])
        : null,
      wert: m => haken ? (m[d.col] ? 1 : 0) : String(m[d.col] == null ? '' : m[d.col]).toLowerCase(),
      zelle: m => {
        if (haken) {
          return `<td class="haken-spalte ${d.klasse || ''}">${m[d.col] ? HAKEN() : ''}</td>`;
        }
        const t = (m[d.col] == null || m[d.col] === '') ? '' : String(m[d.col]);
        return `<td class="${d.klasse || ''}${t ? '' : ' dash'}">${t ? esc(t) : '–'}</td>`;
      },
    };
  }

  /** SPALTEN, abgeglichen mit dem Feldkatalog. */
  function spaltenSatz() {
    const kat = katalogListe();
    if (!kat) { return SPALTEN; }
    const bekannt = new Set(kat.map(d => d.col));
    const behalten = SPALTEN.filter(sp => !sp.katalog || bekannt.has(sp.katalog));
    const gedeckt = new Set(behalten.filter(sp => sp.katalog).map(sp => sp.katalog));
    const neue = kat.filter(d => !gedeckt.has(d.col)).map(katalogSpalte);
    if (!neue.length) { return behalten; }
    /* Hinter die LETZTE gedeckte Katalogspalte, nicht ans Ende: Dort stuende
       sie hinter „km", und die Kilometer sind die Schlussspalte aller drei
       Tabellen. */
    let i = behalten.length;
    for (let k = behalten.length - 1; k >= 0; k--) {
      if (behalten[k].katalog) { i = k + 1; break; }
    }
    return behalten.slice(0, i).concat(neue, behalten.slice(i));
  }

  /**
   * Baut eine Tabelle auf dem uebergebenen <table>-Element auf.
   *
   * opts.table        <table> mit <thead> und <tbody> (beide duerfen leer sein)
   * opts.kacheln      Behaelter fuer die Kachelform (unter 720 px); ohne ihn
   *                   entsteht nur die Tabelle
   * opts.kachelOpts   Zusatzangaben an kachel() (artDatum, knapp)
   * opts.hervor       Funktion(maskiert) -> HTML fuer die Suchwoerter
   * opts.sortKey      Voreinstellung, Standard 'day'
   * opts.sortAsc      Voreinstellung, Standard true
   *                   (zeitraum.php tut das historisch nicht — dort false)
   * opts.seite        Zeilen je Seite; 0 oder fehlend = alle auf einmal.
   *                   Siehe den Abschnitt „Seitengroesse" unten.
   * opts.sortblatt    Behaelter des mobilen Sortierblatts (`.blatt-liste`);
   *                   ohne ihn entsteht keins
   * opts.sortlabel    Element fuer „Datum, neueste zuerst"; wahlfrei
   * opts.onSortChange wird nach einem Umstellen gerufen: (sortKey, sortAsc)
   * opts.ohne         Spaltenschluessel, die diese Seite NICHT fuehrt
   * opts.onAfterDraw  wird nach jedem Zeichnen gerufen: (gesamt, gezeigt)
   *                   'gesamt'  Zeilen, die dem Filter entsprechen
   *                   'gezeigt' davon tatsaechlich gezeichnete, 'zeilen'
   *                   die vollstaendige sortierte Trefferliste
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
    let datenGesetzt = false;
    /* Bestand fuer die Spaltensichtbarkeit (siehe SPALTEN oben). null heisst
     * „noch nicht gesetzt" — dann gilt die Trefferliste selbst. Das ist fuer
     * zeitraum.php richtig (dort ist die Liste der Zeitraum) und fuer
     * suche.php nicht (dort ist sie das Suchergebnis); die Suche setzt ihn
     * deshalb einmal beim Laden auf den Gesamtbestand. */
    let bestand = null;
    /* null = „die Seite hat nichts gesagt" und NICHT `{}`: Ein leeres Objekt
     * hiesse „keine Faehigkeit vorhanden" und liesse die beiden Spalten
     * verschwinden. Der Unterschied ist der ganze Zweck des Rueckfalls. */
    let faehig = null;

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
      /* `knopf knopf-neutral`, nicht mehr `btn-plain` (F-P3-AL). Die alte
       * Klasse hat im neuen Stylesheet keine Regel mehr — die beiden Knoepfe
       * waren seit dem Redesign in der Grundform des Browsers. Aufgefallen ist
       * es niemandem, weil sie erst ab 200 Treffern erscheinen und der
       * Referenzbestand 106 Einsaetze hat. */
      mehrKnopf = document.createElement('button');
      mehrKnopf.type = 'button';
      mehrKnopf.className = 'knopf knopf-neutral';
      mehrKnopf.addEventListener('click', () => mehrZeigen(sichtbar + seite));
      mehrAlleKnopf = document.createElement('button');
      mehrAlleKnopf.type = 'button';
      mehrAlleKnopf.className = 'knopf knopf-neutral';
      mehrAlleKnopf.addEventListener('click', () => mehrZeigen(Infinity));
      mehrZeile.appendChild(mehrKnopf);
      mehrZeile.appendChild(mehrAlleKnopf);
      table.insertAdjacentElement('afterend', mehrZeile);
    }

    /* Spalten, die diese Seite ausdruecklich NICHT fuehrt. Eine Liste mit
     * heute genau einem Eintrag, und der steht in index.php: Die
     * Tagesuebersicht zeigt den Fehleinsatz bewusst nicht (Handbuch 4.1 —
     * „er steht im Einsatz selbst und auf der Kachel"). Alles andere
     * entscheidet `nurWenn` von selbst: Die Nummer gibt es nur, wo `_no`
     * gesetzt ist, das Datum nur bei mehreren, die Art nur bei mehreren. */
    const ohne = new Set(opts.ohne || []);

    /** Die Spalten, die dieser Bestand rechtfertigt (A13d). */
    function sichtbareSpalten() {
      const basis = bestand !== null ? bestand : daten;
      return spaltenSatz().filter(sp => !ohne.has(sp.key)
        && (!sp.nurWenn || sp.nurWenn(basis, faehig)));
    }

    function zeichneKopf(spalten) {
      const tr = document.createElement('tr');
      spalten.forEach(sp => {
        const th = document.createElement('th');
        th.className = 'sortable' + (sp.thClass ? ' ' + sp.thClass : '');
        th.dataset.key = sp.key;
        /* Beschriftung ohne Auszeichnung — fuer das Sortierblatt, das
         * dieselben Spalten fuehrt wie der Kopf (E-P3-32). */
        th.dataset.label = nurText(sp.kopf);
        th.innerHTML = sp.kopf;
        if (sp.key === sortKey) {
          const pfeil = document.createElement('span');
          pfeil.className = 'arrow';
          pfeil.innerHTML = ' ' + edSymbol('pfeil-hoch', sortAsc ? '' : 'symbol-oben',
            sortAsc ? 'aufsteigend' : 'absteigend');
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
      /* Der Kontext geht an JEDE Zelle und an jede Kachel: Er traegt heute
       * nur die Hervorhebung der Suchwoerter, aber er traegt sie an EINER
       * Stelle — sonst braeuchte jede neue Zeilenart ihren eigenen Weg. */
      const ctx = { hervor: opts.hervor };
      /* Sortiert wird ueber ALLE Spalten, nicht nur die sichtbaren: Ein
       * geteilter Link kann nach einer Spalte sortieren, die der eigene
       * Bestand nicht zeigt. Die Reihenfolge stimmt dann trotzdem, nur der
       * Pfeil hat keinen Kopf, an dem er stehen koennte. */
      const alle = spaltenSatz();
      const sp = alle.find(s => s.key === sortKey) || alle[0];
      /* GLEICHSTAENDE: die Einsatznummer entscheidet, wo es eine gibt
       * (Schritt 15 AP9b). Die Tagesuebersicht hatte das schon -- sechs
       * gleich lange Einsaetze standen dort nach einem zweiten Klick wieder
       * 1-6 und nicht in der Reihenfolge, die der Sortierer gerade uebrig
       * liess. Ohne sie waere das eine stille Verschlechterung: Array.sort
       * ist zwar stabil, aber die vorige Reihenfolge ist nach dem ersten
       * Sortieren nicht mehr die urspruengliche.
       * Die Nummer geht NICHT in die Richtung ein: Sie ordnet den
       * Gleichstand, sie kehrt ihn nicht um. Wo es keine gibt (Suche,
       * Zeitraum), bleibt es bei der stabilen Sortierung von vorher. */
      const sortiert = daten.slice().sort((a, b) => {
        const x = sp.wert(a), y = sp.wert(b);
        const r = (x > y) - (x < y);
        if (r !== 0) { return sortAsc ? r : -r; }
        if (a._no != null && b._no != null) { return a._no - b._no; }
        return 0;
      });
      const gezeigt = seite > 0 ? sortiert.slice(0, sichtbar) : sortiert;
      tbody.innerHTML = '';
      gezeigt.forEach(m => {
        const tr = document.createElement('tr');
        tr.className = 'clickable';
        tr.dataset.mid = m.id;
        tr.innerHTML = spalten.map(s => s.zelle(m, ctx)).join('');
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
      /* DIE KACHELN AUS DEMSELBEN ZEILENBESTAND (E-P3-32/36). Sortierung,
       * Spaltensichtbarkeit und Seitengrenze gelten fuer beide Formen —
       * welche zu sehen ist, entscheidet allein das Stylesheet. Ohne
       * `kacheln` bleibt alles beim Alten. */
      if (opts.kacheln) {
        const ko = Object.assign({ hervor: opts.hervor }, opts.kachelOpts || {});
        opts.kacheln.innerHTML = gezeigt
          .map(m => kachel(m, Object.assign({ farbe: m._col }, ko))).join('');
      }
      zeichneSortblatt(spalten);
      zeichneMehr(sortiert.length, gezeigt.length);
      /* Dritter Wert: die vollstaendige Trefferliste. Die Suche rechnet
       * daraus ihre km-Summe — ueber ALLE Treffer, nicht nur die
       * gezeichneten (E-P3-36). */
      if (opts.onAfterDraw) { opts.onAfterDraw(sortiert.length, gezeigt.length, sortiert); }
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

    /* ---- DAS MOBILE SORTIERBLATT -------------------------------------
     *
     * Unter 720 px zeigen alle drei Seiten Kacheln statt einer Tabelle, und
     * eine Kachel hat keine Spaltenkoepfe zum Anklicken. Das Blatt ist dort
     * der EINZIGE Weg zu sortieren (E-P3-32).
     *
     * SEIT SCHRITT 15 AP9b STEHT ES HIER. Bis dahin baute es jede Seite
     * selbst: 22 Zeilen in `suche.php`, dieselben 22 in `zeitraum.php`
     * (zeichengleich bis auf einen Aufruf) und 20 in `index.php`. Alle drei
     * lasen ihre Spalten schon aus `tabelle.spalten()` — die LISTE war also
     * gemeinsam, die SCHLEIFE dreifach. Und der Unterschied kostete: In
     * suche.php und zeitraum.php stellte ein Klick im Blatt um, ohne dass
     * sich etwas bewegte, weil setSort() nicht zeichnete und die Seite es
     * auch nicht tat.
     *
     * DIE BESCHRIFTUNG IST WAHLFREI. `sortlabel` bekommt „Datum, neueste
     * zuerst"; index.php hat kein solches Feld und uebergibt keins. Die
     * Sonderworte fuer den Datumsschluessel stehen hier, weil „absteigend"
     * bei einem Datum niemandem sagt, was oben steht. */
    function zeichneSortblatt(spalten) {
      const liste = opts.sortblatt;
      const richtung = sortKey === 'day'
        ? (sortAsc ? 'älteste zuerst' : 'neueste zuerst')
        : (sortAsc ? 'aufsteigend' : 'absteigend');
      const sichtbareSp = spalten.filter(sp => sp.kopf !== '');

      if (opts.sortlabel) {
        const sp = sichtbareSp.find(x => x.key === sortKey);
        opts.sortlabel.innerHTML = sp
          ? esc(nurText(sp.kopf)) + '<span class="nur-ab-720">, ' + esc(richtung) + '</span>'
          : esc(richtung);
      }
      if (!liste) { return; }
      liste.innerHTML = '';
      sichtbareSp.forEach(sp => {
        const b = document.createElement('button');
        b.type = 'button';
        const aktiv = sp.key === sortKey;
        b.className = 'blatt-zeile' + (aktiv ? ' aktiv' : '');
        b.innerHTML = '<span>' + esc(nurText(sp.kopf)) + '</span>'
          + (aktiv ? edSymbol('pfeil-hoch', sortAsc ? '' : 'symbol-oben', richtung) : '');
        b.addEventListener('click', () => {
          if (aktiv) { sortAsc = !sortAsc; } else { sortKey = sp.key; sortAsc = true; }
          zeichne();
          if (opts.onSortChange) { opts.onSortChange(sortKey, sortAsc); }
          if (window.edBlatt) { edBlatt.zu(); }
        });
        liste.appendChild(b);
      });
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
      datenGesetzt = true;
      // Neue Liste, neue erste Seite (siehe „Seitengroesse" oben).
      sichtbar = seite;
      zeichne();
    }

    /* ZEICHNET SEIT SCHRITT 15 AP9b SELBST. Bis dahin setzte diese Funktion
     * nur Schluessel und Richtung -- das mobile Sortierblatt der Suche und
     * der Zeitraumuebersicht stellte also um, ohne dass sich etwas bewegte,
     * und funktionierte nur dort, wo die Seite hinterher von Hand
     * nachzeichnete. Wer eine Sortierfunktion ruft, will sortiert sehen. */
    function setSort(key, asc) {
      if (!spaltenSatz().some(s => s.key === key)) { return; }
      sortKey = key; sortAsc = !!asc;
      /* Nur, wenn es schon etwas zu zeichnen gab. `fragmentLesen()` in
       * suche.php ruft setSort VOR dem ersten setData -- dort waere ein
       * Zeichnen eine leere Tabelle, die der naechste Zug sofort ersetzt. */
      if (datenGesetzt) { zeichne(); }
    }

    /* Bestand fuer die Spaltensichtbarkeit setzen. Zeichnet NICHT selbst: Die
     * Aufrufer setzen ihn unmittelbar vor oder nach setData(), und zweimal
     * zeichnen waere zweimal dieselbe Tabelle. */
    function setSpaltenBestand(liste) { bestand = liste || []; }

    /* Faehigkeiten der Ansicht setzen, `{winch, bergwacht}`. Zeichnet
     * ebenfalls nicht selbst -- derselbe Grund. */
    function setFaehigkeiten(f) { faehig = f || null; }

    return {
      setData, zeichne, setSort, setSpaltenBestand, setFaehigkeiten,
      /* Die sichtbaren Spalten mit ihrer schlichten Beschriftung — fuer ein
       * Sortierblatt, das nicht den Tabellenkopf abklauben muss. Spalten
       * ohne Kopftext (der Farbstreifen) bleiben draussen: Nach ihnen
       * sortiert niemand. */
      spalten: () => sichtbareSpalten()
        .filter(sp => sp.kopf !== '')
        .map(sp => ({ key: sp.key, label: nurText(sp.kopf) })),
      get sortKey() { return sortKey; },
      get sortAsc() { return sortAsc; }
    };
  }

  /* zelleGeschuetzt ist seit Web 7.2.0 mit dabei: index.php baut seine Zeile
     selbst (sie fuehrt die Katalogspalten aus DAY_COLS, die diese Tabelle
     nicht kennt), soll die drei geschuetzten Spalten aber genauso zeigen wie
     Suche und Zeitraum-Uebersicht — Warnzeichen statt Gedankenstrich, wenn
     die Angaben da, aber nicht lesbar sind. Seit Web 7.2.1 bringt sie auch
     die Maskierung mit — beide Zeilen sind damit an derselben einen Stelle
     gegen Markup aus einer Importdatei abgesichert (Backlog Nr. 22). */
  return { erzeuge, SPALTEN, esc, escape, fmtTag, fmtDur, fmtKmZahl,
           extractOrt, artSymbol, zelleGeschuetzt, zelleDauer, kachel };
})();
