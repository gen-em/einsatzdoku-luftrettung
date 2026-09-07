/* Gen-EM NAdoku — Passwortgüte (Baustein B9).
 *
 * WARUM DIESE PRÜFUNG NUR IM BROWSER STATTFINDEN KANN
 * Der Server sieht das Passwort nie. Er bekommt ausschließlich das daraus
 * abgeleitete Auth-Token — das ist der Kern des Verfahrens und keine
 * Nachlässigkeit. Die Kehrseite: Er KANN die Stärke prinzipiell nicht prüfen.
 * Jede Prüfung hier lässt sich von jemandem umgehen, der es darauf anlegt.
 *
 * Das ist trotzdem kein Grund, sie wegzulassen. Sie hält niemanden auf, der
 * bewusst umgeht, verhindert aber das VERSEHENTLICHE Umgehen — und genau das
 * war der Zustand: Die Mindestlänge stand an einer der beiden Stellen nur als
 * HTML-Attribut, das jede Browsererweiterung und jeder Klick in den
 * Entwicklerwerkzeugen aushebelt.
 *
 * WAS AN DER PASSWORTWAHL HÄNGT
 * Die Stärke des Passworts IST die Stärke der Verschlüsselung. Wer die Daten
 * gegen jemanden schützen will, der Zugriff auf die Ablaufumgebung hat
 * (Hoster, Datenbank, Protokolle), hat außer dem Passwort nichts. Deshalb
 * steht der Hinweis nicht klein am Rand, sondern gehört an die Stelle, an der
 * das Passwort gewählt wird.
 *
 * Verwendung:
 *   const r = EdPwQuality.pruefe(pw);
 *   if (!r.erlaubt) { melde(r.meldung); return; }
 *   EdPwQuality.anzeige(elem, r);
 */
'use strict';
const EdPwQuality = (() => {

  /** Mindestlänge — für das Kontopasswort UND das Passwort einer
   *  Backup-Datei. Beide Stellen schützen dieselben Angaben; zwei
   *  verschiedene Mindestlängen (10 und 8) waren nur historisch begründet.
   *
   *  12 STATT 10 seit dem Sofortpaket Sicherheit (Backlog Nr. 136, SP-2).
   *  Gegen einen Datenbankabzug ist das Passwort die einzige Schranke — der
   *  Server sieht es nie und kann seine Güte nach Bauart nicht prüfen. Zwei
   *  Zeichen mehr sind dort mehr wert als jede Regel über Zeichenarten.
   *
   *  DIE ZWEITE STELLE IST `PW_MIN_LAENGE` in `db.php`; von dort kommen
   *  `minlength` und die Zeile unter dem Feld. Beide Zahlen müssen gleich
   *  sein. Damit ein Auseinanderlaufen keine SCHWÄCHERE Prüfung ergibt,
   *  setzt `beobachte()` unten `minLength` des Feldes auf diesen Wert. */
  const MIN_LAENGE = 12;

  /** Wie viel vom Passwort übrig bleiben muss, wenn man die geläufigen Wörter
   *  und alle Ziffern herausstreicht — siehe `istHaeufig()`. */
  const MIN_REST = 8;

  /* Kompakte Liste besonders häufiger Passwörter und Muster.
   *
   * BEWUSST KURZ: Eine Liste mit Millionen Einträgen gehört nicht in eine
   * Seite, die jemand über eine Mobilfunkverbindung lädt. Diese Auswahl deckt
   * das ab, was in Auswertungen geleakter Zugangsdaten oben steht, sowie die
   * naheliegenden deutschsprachigen Varianten. Der Abgleich ignoriert
   * Groß-/Kleinschreibung und angehängte Ziffern. */
  const HAEUFIG = [
    'password', 'passwort', 'kennwort', 'geheim', 'secret', 'qwertz', 'qwerty',
    'asdfgh', 'yxcvbn', '123456', '1234567', '12345678', '123456789', '1234567890',
    '111111', '000000', 'abc123', 'admin', 'administrator', 'root', 'letmein',
    'welcome', 'willkommen', 'monkey', 'dragon', 'sunshine', 'iloveyou',
    'princess', 'football', 'baseball', 'starwars', 'master', 'login', 'test',
    'hallo', 'hallowelt', 'schatz', 'sommer', 'winter', 'fruehling', 'herbst',
    'deutschland', 'bayern', 'muenchen', 'berlin', 'hamburg',
    'hubschrauber', 'rettung', 'notarzt', 'einsatz', 'christoph', 'luftrettung',
    'klinik', 'krankenhaus', 'medizin', 'sanitaeter',
    /* Bodengebundene Gegenstuecke (Web 8.0.1). Die Liste nannte bis dahin
       nur die luftgebundenen Woerter — an einem NEF-Standort fehlte damit
       genau das, was dort naheliegt.

       KEINE Kuerzel wie "nef", "rth", "naw": Gestrichen wird unten nur, was
       MINDESTENS SECHS ZEICHEN hat; kuerzere Woerter treffen nur, wenn das
       GANZE normalisierte Passwort genau so lautet — und ein
       dreibuchstabiges Passwort scheitert schon an der Mindestlaenge. Ein
       Kuerzel als Teilstring traefe massenhaft brauchbare Passwoerter.
       Aus demselben Grund stehen "wache", "koeln", "sonne" und "blume"
       NICHT in der Erweiterung unten: Sie haben fuenf Zeichen und koennten
       neben der Mindestlaenge nie greifen — ein Eintrag, der nie trifft,
       sieht aus wie Schutz und ist keiner.

       KEIN "nadoku". Der kuenftige Produktname war vorgesehen, ist aber
       wieder herausgenommen: Der Vergleich ist ein Teilstring-Vergleich, und
       das Demo-Passwort dieser Anwendung lautet `nadokudemo0815`. Mit
       "nadoku" in der Liste laesst es sich nicht mehr setzen (pw_handling)
       und nicht mehr als Backup- oder Exportpasswort verwenden
       (einstellungen.php, import.php pruefen `guete.erlaubt`) — gemessen: der
       Kreislauftest scheiterte daran, weil das erneute Backup gar nicht
       erst erzeugt wurde. Das Passwort steht im README, im Handbuch 3.2 und
       in saemtlichen Pruefmitteln. Wenn der Produktname kommt (P6), gehoert
       "nadoku" hierher — zusammen mit einem neuen Demo-Passwort.

       Mehrere Eintraege sind durch den Teilstring-Vergleich bereits von
       kuerzeren abgedeckt: "rettungswagen" und "rettungsdienst" von
       "rettung", "notarztwagen" von "notarzt", "notfallsanitaeter" von
       "sanitaeter", "einsatzdoku" von "einsatz". Sie stehen trotzdem hier:
       Die Liste ist auch die Stelle, an der man nachsieht, WELCHE Woerter
       gemeint sind. Sicherheitsgewinn bringt keiner von ihnen — der stand
       schon vorher da. */
    'notarztwagen', 'rettungswagen', 'notfallsanitaeter', 'rettungsdienst',
    'einsatzdoku',
    /* Erweiterung Sofortpaket Sicherheit (Backlog Nr. 136, SP-2). Zwei
       Gruppen, beide aus der Umgebung dieser Anwendung:

       DIENSTLICHE WOERTER, die auf einer Rettungswache naheliegen und in der
       Liste fehlten. "notarzt" und "rettung" standen schon da; was danebenlag,
       stand nicht.

       ORTE UND LAENDER. Die Liste nannte fuenf; die groesseren deutschen
       Staedte und die Nachbarlaender kommen dazu. WAS SIE NICHT KANN: den
       ORTSNAMEN DES EIGENEN STANDORTS. Der steht in den Stammdaten und
       waere nur ueber eine Ausgabe der Standortnamen an die Passwortseite
       zu haben -- auch an die UNANGEMELDETE (pw_handling.php im
       Reset-Modus). Das waere eine neue Auskunft an jeden Besucher fuer
       einen Gewinn, den die Mindestlaenge besser holt. Der Satz gehoert
       stattdessen ins Handbuch 3.1: der eigene Standort ist ein schlechtes
       Passwort, und niemand kann das hier nachpruefen. */
    'feuerwehr', 'malteser', 'johanniter', 'sanitaet', 'notfall', 'leitstelle',
    'ambulanz', 'intensiv', 'schockraum', 'dienst', 'station',
    'frankfurt', 'stuttgart', 'duesseldorf', 'dortmund', 'leipzig',
    'dresden', 'hannover', 'nuernberg', 'bremen', 'augsburg', 'regensburg',
    'wuerzburg', 'oesterreich', 'schweiz', 'allgaeu',
    'geburtstag', 'familie', 'urlaub', 'fussball',
  ];

  /** Kleinschreibung, Umlaute aufgelöst, Satzzeichen entfernt. */
  function normal(pw) {
    return String(pw).toLowerCase()
      .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
      .replace(/[^a-z0-9]/g, '');
  }

  /** Zusätzlich ohne angehängte Ziffern: „Passwort123!" und „passwort" sollen
   *  dieselbe Warnung erzeugen. */
  function kern(pw) { return normal(pw).replace(/\d+$/, ''); }

  /**
   * Was vom Passwort übrig bleibt, wenn man jedes geläufige Wort und die
   * angehängten Ziffern herausstreicht.
   *
   * WARUM ES DIESE FUNKTION GIBT (Sofortpaket Sicherheit, Backlog Nr. 136).
   * Bis dahin wies die Prüfung JEDES Passwort ab, in dem irgendwo eines der
   * Wörter vorkam. Das war mit der Empfehlung, die dieselbe Stufe aufstellt,
   * nicht vereinbar: „Vier zufällige Wörter" ist der beste Rat, den man zur
   * Passwortwahl geben kann — und „Anker-Winter-Regen-Glas" scheiterte
   * daran, dass „winter" in der Liste steht. Eine Empfehlung, die die eigene
   * Prüfung abweist, ist schlimmer als keine.
   *
   * Gemessen wird deshalb nicht mehr das VORKOMMEN, sondern der ANTEIL: Was
   * bleibt übrig, wenn man die geläufigen Teile wegnimmt? Bleiben weniger als
   * MIN_REST Zeichen, war das Passwort im Wesentlichen ein Listenwort mit
   * Beiwerk. „Winterurlaub2026" behält „urlaub" (6) und wird abgewiesen,
   * „Anker-Winter-Regen-Glas" behält „ankerregenglas" (14) und geht durch.
   *
   * ZIFFERN MITTENDRIN ZÄHLEN MIT, angehängte nicht. „xy7qw2zt4$" ist nicht
   * schlechter als „xyqwzt" — würde man alle Ziffern streichen, fiele
   * ausgerechnet ein gut gewürfeltes Passwort durch. Angehängte Ziffern sind
   * die Jahreszahl hinter dem Wort und deshalb kein Beitrag.
   */
  function restwort(pw) {
    let k = normal(pw);
    for (const h of HAEUFIG) {
      if (h.length >= 6 && k.includes(h)) { k = k.split(h).join(''); }
    }
    return k.replace(/\d+$/, '');
  }

  /**
   * Enthält das Passwort ein bekanntes Allerweltswort?
   *
   * Geprüft werden BEIDE Normalisierungen. Nur die gekürzte zu prüfen wäre ein
   * Loch: Aus „1234567890" bliebe eine leere Zeichenkette, und ausgerechnet
   * die Zahlenreihen — die in jeder Liste ganz oben stehen — kämen durch.
   */
  function istHaeufig(pw) {
    for (const k of [normal(pw), kern(pw)]) {
      if (k !== '' && HAEUFIG.some(h => k === h)) { return true; }
    }
    // Reine Ziffernfolge: unter 16 Stellen zu wenig, um von Hand gewählt
    // ausreichend zu sein — der Suchraum ist dort schlicht zu klein.
    if (/^\d+$/.test(String(pw)) && String(pw).length < 16) { return true; }
    return restwort(pw).length < MIN_REST;
  }

  /** Nur eine Zeichenart in Folge, z. B. „aaaaaaaaaa" oder „1234567890". */
  function istMuster(pw) {
    const s = String(pw);
    if (/^(.)\1+$/.test(s)) { return true; }
    // aufsteigende oder absteigende Folge über die gesamte Länge
    let auf = true, ab = true;
    for (let i = 1; i < s.length; i++) {
      const d = s.charCodeAt(i) - s.charCodeAt(i - 1);
      if (d !== 1) { auf = false; }
      if (d !== -1) { ab = false; }
    }
    return s.length >= 4 && (auf || ab);
  }

  /**
   * Grobe Schätzung der Stärke, 0 bis 4.
   *
   * Bewusst KEINE Entropierechnung mit Nachkommastellen: Die Zahl wäre
   * genauer, als sie sein kann, und würde Sicherheit vortäuschen. Es geht um
   * „zu dünn" gegen „in Ordnung".
   */
  function staerke(pw) {
    const s = String(pw);
    if (s.length < MIN_LAENGE) { return 0; }
    if (istHaeufig(s) || istMuster(s)) { return 0; }

    let arten = 0;
    if (/[a-z]/.test(s)) arten++;
    if (/[A-Z]/.test(s)) arten++;
    if (/[0-9]/.test(s)) arten++;
    if (/[^A-Za-z0-9]/.test(s)) arten++;

    // Länge zählt mehr als Zeichenvielfalt — das entspricht der Wirklichkeit
    // eines Rateangriffs und führt zu besseren Passwörtern als die Forderung
    // nach einem Sonderzeichen an dritter Stelle.
    let punkte = 0;
    if (s.length >= 12) punkte++;
    if (s.length >= 16) punkte++;
    if (s.length >= 20) punkte++;
    if (arten >= 3) punkte++;
    return Math.max(1, Math.min(4, punkte));
  }

  const STUFEN = ['zu schwach', 'schwach', 'brauchbar', 'gut', 'stark'];

  /* Der eine Rat, der wirklich hilft (SP-2, Backlog Nr. 136). Er steht in
     JEDER Meldung, die etwas auszusetzen hat, statt nur im Handbuch: Wer hier
     abgewiesen wird, hängt sonst ein Ausrufezeichen an und probiert es
     nochmal. Länge schlägt Zeichenvielfalt — das ist die Rechnung eines
     Rateangriffs und der Grund, warum `staerke()` unten die Länge dreimal
     zählt und die Zeichenarten einmal. */
  const RAT_PASSPHRASE = 'Am besten vier zufällige Wörter, die nichts '
                       + 'miteinander zu tun haben.';

  /**
   * Vollständige Prüfung.
   * @returns {{erlaubt:boolean, staerke:number, stufe:string, meldung:string}}
   */
  function pruefe(pw) {
    const s = String(pw == null ? '' : pw);
    if (s.length < MIN_LAENGE) {
      return { erlaubt: false, staerke: 0, stufe: STUFEN[0],
               meldung: `Mindestens ${MIN_LAENGE} Zeichen. ` + RAT_PASSPHRASE };
    }
    if (istHaeufig(s)) {
      return { erlaubt: false, staerke: 0, stufe: STUFEN[0],
               meldung: 'Dieses Passwort besteht im Wesentlichen aus geläufigen '
                      + 'Wörtern — genau die werden beim Durchprobieren zuerst '
                      + 'versucht. ' + RAT_PASSPHRASE };
    }
    if (istMuster(s)) {
      return { erlaubt: false, staerke: 0, stufe: STUFEN[0],
               meldung: 'Eine reine Zeichenfolge ist kein Passwort.' };
    }
    const st = staerke(s);
    return { erlaubt: true, staerke: st, stufe: STUFEN[st],
             meldung: st <= 1
               ? 'Das geht — länger wäre deutlich besser. Die Stärke des Passworts '
                 + 'ist unmittelbar die Stärke der Verschlüsselung. ' + RAT_PASSPHRASE
               : '' };
  }

  /**
   * Schreibt das Ergebnis in ein Element — als BALKEN aus vier Segmenten mit
   * der Stufe daneben (E-P3-16, Mockup 11; ab Web 9.7.0).
   *
   * Vorher stand hier eine Textzeile in einer von fünf Farben, darunter Grün
   * und Gelb — zwei Töne, die es in der Marke nicht gibt. Der Balken sagt
   * dasselbe ohne fremde Farbe: Wie viele Segmente gefüllt sind, ist die
   * Auskunft; die Farbe (rot / orange / dunkelblau) verstärkt sie nur.
   *
   * VIER SEGMENTE bei fünf Stufen (0..4): Stufe 0 füllt eines und färbt es
   * rot. Null gefüllte Segmente wären ein leerer Kasten — von „noch nichts
   * eingegeben" nicht zu unterscheiden, und genau dort steht die Anzeige gar
   * nicht.
   *
   * Der Balken ist `aria-hidden`: Er wiederholt, was der Text daneben sagt,
   * und vier leere Elemente vorzulesen hilft niemandem. Die Zeile selbst ist
   * `role="status"`, damit die Stufe beim Tippen angesagt wird.
   */
  function anzeige(el, ergebnis) {
    if (!el) { return; }
    const gefuellt = Math.max(1, ergebnis.staerke);
    el.className = 'pwstaerke pwq-' + ergebnis.staerke;
    el.setAttribute('role', 'status');
    let balken = '<span class="pwstaerke-balken" aria-hidden="true">';
    for (let i = 0; i < 4; i++) {
      balken += '<span' + (i < gefuellt ? ' class="an"' : '') + '></span>';
    }
    balken += '</span>';
    /* Stufe und Hinweis als Text — EdHtml.escape ist hier nicht nötig, weil
       beide aus STUFEN und festen Zeichenketten dieser Datei stammen; ein
       Passwort steht nie darin. */
    el.innerHTML = balken
      + '<span class="pwstaerke-text">' + ergebnis.stufe + '</span>'
      + (ergebnis.meldung ? '<span class="pwstaerke-hinweis">' + ergebnis.meldung + '</span>' : '');
  }

  /**
   * Hängt die Anzeige an ein Eingabefeld. Liefert eine Funktion, die den
   * aktuellen Prüfstand abfragt — zum Aufruf beim Absenden.
   */
  function beobachte(feld, anzeigeEl) {
    /* Das HTML-Attribut auf denselben Wert ziehen. `minlength` kommt aus
       PW_MIN_LAENGE (db.php) und ist damit die zweite Stelle, an der diese
       Zahl steht; laufen die beiden auseinander, soll wenigstens die
       STRENGERE gelten und nicht die, die zufällig im Markup stand. */
    if (feld && typeof feld.minLength === 'number') { feld.minLength = MIN_LAENGE; }
    let letzte = pruefe('');
    const lauf = () => {
      letzte = pruefe(feld.value);
      if (feld.value === '') {
        if (anzeigeEl) { anzeigeEl.innerHTML = ''; anzeigeEl.className = 'pwstaerke'; }
      } else {
        anzeige(anzeigeEl, letzte);
      }
    };
    feld.addEventListener('input', lauf);
    return () => letzte;
  }

  return { MIN_LAENGE, MIN_REST, pruefe, staerke, anzeige, beobachte };
})();
