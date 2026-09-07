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

  /** Wie viel vom Passwort übrig bleiben muss, wenn man die geläufigen Wörter,
   *  die angehängten Ziffern und die Reihen herausstreicht — siehe `zerlege()`
   *  und `warumHaeufig()`. Sonderzeichen zählen dabei mit. Die Schranke gilt,
   *  wenn ein Listenwort gestrichen wurde — und ohne Listenwort nur dann,
   *  wenn Buchstaben und Ziffern restlos in Reihen und angehängten Ziffern
   *  aufgegangen sind. Ein Zufallspasswort wird nie am Rest gemessen. */
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

       KEINE Kuerzel wie "nef", "rth", "naw". Seit der Nachbesserung zu SP-2
       (Backlog Nr. 136) wird JEDER Eintrag gestrichen, auch "root" und
       "test" mit vier Zeichen — die fruehere Schranke von sechs Zeichen ist
       gefallen, weil unter der Anteilsregel ein NICHT gestrichenes kurzes
       Wort dem Passwort gutgeschrieben wurde: "password-admin-admin" ging
       mit Rest "adminadmin" durch, "Passwort-Admin-Login!" mit Rest
       "adminlogin" als "stark". Ein Kuerzel mit drei Zeichen gehoert
       trotzdem nicht hierher: "rth" steckt in "Arthur" und "Fuerth", und
       jeder Treffer zerlegt den Rest in Bruchstuecke, die sich beim
       Zusammenfuegen zu neuen Reihen oder Listenwoertern verbinden koennen.
       Die Liste hat keinen Eintrag unter vier Zeichen; wer einen aufnimmt,
       misst vorher nach.
       "wache", "koeln", "sonne" und "blume" fehlen aus einem aelteren
       Grund: Fuenfstellige Eintraege konnten bis zur Nachbesserung nie
       greifen. Das gilt nicht mehr — ob sie aufgenommen werden, ist eine
       offene Entscheidung, keine dieser Datei.

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

       "rettungswagen", "rettungsdienst", "notarztwagen",
       "notfallsanitaeter" und "einsatzdoku" enthalten kuerzere Eintraege
       ("rettung", "notarzt", "sanitaeter", "einsatz"). Unter dem alten
       Vorkommensvergleich waren sie deshalb wirkungslos; unter der
       Anteilsregel sind sie es NICHT. `zerlege()` streicht laengste zuerst,
       und nur so verschwindet "rettungswagen" ganz — striche man erst
       "rettung", bliebe "swagen" stehen und zaehlte als Rest. Gemessen vor
       der Nachbesserung: "Rettungswagen-Notarztwagen" ging mit Rest
       "swagenwagen" durch, Stufe "stark". */
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

  /** Dieselbe Liste, laengste Eintraege zuerst — die Reihenfolge, in der
   *  `zerlege()` streicht. Gleich lange behalten ihre Listenreihenfolge. */
  const HAEUFIG_LAENGSTE_ZUERST = HAEUFIG.slice().sort((a, b) => b.length - a.length);

  /** Was als Sonderzeichen zaehlt: alles ausser Buchstaben, Ziffern, Umlauten
   *  und den vier Trennern Bindestrich, Unterstrich, Punkt, Leerzeichen. */
  const SONDERZEICHEN = /[^A-Za-z0-9äöüÄÖÜß\-_. ]/g;

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
   * Was vom Passwort übrig bleibt, wenn man jedes geläufige Wort, die
   * angehängten Ziffern und die Reihen herausstreicht — und wie viele
   * Sonderzeichen es außerdem trägt.
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
   * Beiwerk. „Winterurlaub2026" behält nichts (auch „urlaub" steht in der
   * Liste) und wird abgewiesen, „Anker-Winter-Regen-Glas" behält
   * „ankerregenglas" (14) und geht durch.
   *
   * DREI SCHRITTE: erst die Listenwörter, dann die angehängten Ziffern, dann
   * Reihen und Wiederholungen (`ohneReihen()` weiter unten) — sonst füllte
   * „abcdefgh" die geforderten acht Zeichen, ohne einen Gedanken zu kosten.
   *
   * LÄNGSTE ZUERST, UND NACH JEDEM TREFFER VON VORN. Die erste Fassung lief
   * die Liste einmal in ihrer Reihenfolge durch und strich nur Einträge ab
   * sechs Zeichen. Die Gegenprüfung fand darin drei Löcher mit einer
   * Ursache: „rettung" stand vor „rettungswagen" und ließ „swagen" als Rest
   * stehen; Wörter unter sechs Zeichen wurden nicht gestrichen und dem Rest
   * GUTGESCHRIEBEN („password-admin-admin" → Rest „adminadmin"); und was das
   * Streichen aus den Bruchstücken neu zusammensetzte, sah niemand mehr an
   * („adminiadministratorstrator" → Rest „administrator"). Deshalb: die
   * Liste nach Länge absteigend, jeder Eintrag, und nach jedem Treffer wieder
   * beim längsten beginnen, bis keiner mehr trifft. Der Neustart nach JEDEM
   * Treffer ist nötig, nicht erst am Listenende — gemessen: mit Neustart am
   * Ende blieb von „adminiadministratorstrator" noch „istrator" (8) stehen.
   *
   * ZIFFERN MITTENDRIN ZÄHLEN MIT, angehängte nicht. „xy7qw2zt4$" ist nicht
   * schlechter als „xyqwzt" — würde man alle Ziffern streichen, fiele
   * ausgerechnet ein gut gewürfeltes Passwort durch. Angehängte Ziffern sind
   * die Jahreszahl hinter dem Wort und deshalb kein Beitrag.
   *
   * SONDERZEICHEN ZÄHLEN MIT, EINS ZU EINS. `normal()` wirft sie weg, und die
   * erste Fassung maß den Rest erst danach: Ein Passwort aus dem
   * Passwortverwalter mit fünf Sonderzeichen auf zwölf Stellen fiel unter
   * MIN_REST — gemessen je nach Zeichenvorrat 2 bis 25 % aller zufälligen
   * Zwölfsteller, vorher keiner — und bekam dazu gesagt, es bestehe aus
   * geläufigen Wörtern. Für
   * den Rateangriff ist ein Sonderzeichen so viel wert wie ein Buchstabe,
   * also zählt es wie einer. Trenner — Bindestrich, Unterstrich, Punkt,
   * Leerzeichen — zählen NICHT: „Winter-Urlaub-2026" ist kein besseres
   * Passwort als „Winterurlaub2026". Und was sich unter den Sonderzeichen
   * wiederholt oder aufreiht („!!!!!!!!"), ist eine Reihe wie „aaaaaaaa"
   * und fällt wie sie heraus — sonst wäre „Passwort!!!!!!!!xy" ein
   * Passwort mit zehn Zeichen Rest.
   * Die Reihe wird über die Sonderzeichen in ihrer Reihenfolge gesucht,
   * gleich was dazwischen steht — genauso, wie `normal()` für die
   * Buchstaben alles Übrige entfernt.
   *
   * DIE SONDERZEICHEN SELBST STEHEN NICHT IM ERGEBNIS, nur ihre Zahl. `rest`
   * bleibt [a-z0-9] und darf deshalb in der Meldung zitiert werden;
   * `warumHaeufig()` und `anzeige()` verlassen sich darauf.
   */
  function zerlege(pw) {
    let k = normal(pw);
    const woerter = [];
    let getroffen = true;
    while (getroffen) {
      getroffen = false;
      for (const h of HAEUFIG_LAENGSTE_ZUERST) {
        if (k.includes(h)) {
          if (!woerter.includes(h)) { woerter.push(h); }
          k = k.split(h).join('');
          getroffen = true;
          break;
        }
      }
    }
    const ziffern = (k.match(/\d+$/) || [''])[0];
    k = k.replace(/\d+$/, '');
    let [rest, reihen] = ohneReihen(k);
    /* Ein Rest, der als Ganzes ein Muster ist („aa"), ist eine Reihe, die
       `ohneReihen()` zu kurz war — er zählt wie eine. */
    if (rest !== '' && istMuster(rest)) { reihen.push(rest); rest = ''; }
    const sonderAlle = (String(pw).match(SONDERZEICHEN) || []).join('');
    const [sonderBleibt, sonderReihen] = ohneReihen(sonderAlle);
    return { woerter, ziffern, reihen, rest,
             sonder: sonderBleibt.length,
             sonderReihen: sonderReihen.map(r => r.length) };
  }

  /**
   * Streicht Reihen und Wiederholungen aus einer Zeichenkette.
   *
   * WARUM DAS DAZUGEHÖRT (gemessen beim Bauen). Ohne diesen Schritt füllt
   * „abcdefgh" oder „aaaaaaaa" die geforderten acht Zeichen, ohne einen
   * Gedanken zu kosten: „Passwortabcdefgh" und „passwort2026aaaaaaaa" gingen
   * durch, obwohl der alte Vorkommensvergleich sie abgewiesen hatte. Die neue
   * Regel darf an KEINER Stelle schwächer sein als die alte — sie soll nur an
   * einer Stelle großzügiger sein, nämlich bei Passphrasen.
   *
   * `istMuster()` reicht dafür nicht: Es prüft die GANZE Zeichenkette, und
   * „2026aaaaaaaa" ist als Ganzes keine Reihe.
   *
   * Gestrichen wird eine Folge, in der der Abstand zwischen aufeinander
   * folgenden Zeichen gleich bleibt und 0, +1 oder −1 beträgt: dreimal
   * dasselbe Zeichen, oder vier aufsteigende bzw. absteigende. Was
   * dazwischenliegt, bleibt stehen — „ankerregenglas" verliert nichts.
   */
  function ohneReihen(k) {
    const bleibt = [];
    const weg = [];
    let i = 0;
    while (i < k.length) {
      let j = i + 1;
      let d = null;
      if (j < k.length) {
        const dd = k.charCodeAt(j) - k.charCodeAt(i);
        if (dd === 0 || dd === 1 || dd === -1) {
          d = dd;
          while (j + 1 < k.length && k.charCodeAt(j + 1) - k.charCodeAt(j) === d) { j++; }
          j++;
        }
      }
      const laenge = j - i;
      const reihe = (d === 0 && laenge >= 3) || ((d === 1 || d === -1) && laenge >= 4);
      (reihe ? weg : bleibt).push(k.slice(i, j));
      i = j;
    }
    return [bleibt.join(''), weg];
  }

  /**
   * Warum das Passwort „im Wesentlichen geläufig" ist — oder null, wenn nicht.
   * Liefert `{ text, rat }`: die Erklärung und ob der Passphrasen-Rat dazu
   * passt.
   *
   * Geprüft werden zuerst BEIDE Normalisierungen auf ein ganzes Listenwort.
   * Nur die gekürzte zu prüfen wäre ein Loch: Aus „1234567890" bliebe eine
   * leere Zeichenkette, und ausgerechnet die Zahlenreihen — die in jeder
   * Liste ganz oben stehen — kämen durch.
   *
   * DIE MELDUNG SAGT, WAS GESTRICHEN WURDE UND WAS BLIEB. Eine Ablehnung, die
   * nur „zu geläufig" sagt, lässt jemanden dreimal dasselbe Passwort mit einem
   * anderen Ausrufezeichen probieren. Hier steht stattdessen: welches Wort,
   * welche Jahreszahl, welche Reihe — und wie viele Zeichen übrig sind.
   *
   * DIE ANTEILSREGEL GILT, WENN EIN LISTENWORT GESTRICHEN WURDE. Die erste
   * Fassung maß jeden Rest gegen MIN_REST — und wies damit ein gewürfeltes
   * „#7!qX@2%mZ$4" ab, mit der Begründung, es bestehe aus geläufigen
   * Wörtern, und dem Rat, lieber Wörter zu nehmen. Beides war falsch. Ohne
   * Listenwort gibt es keinen Anteil zu messen; was dann noch greift, ist
   * allein die Reihen-Prüfung: Sind Buchstaben und Ziffern RESTLOS in Reihen
   * und angehängten Ziffern aufgegangen („aaaaaaaaaaa1", „abcdefgh2026!@#$",
   * „Aaaaaaaaaaaa1!") und füllen die Sonderzeichen allein die acht nicht,
   * ist das Passwort eine Zeichenfolge mit Beiwerk und wird als solche
   * benannt — ohne den Passphrasen-Rat, der hier nichts erklärt. Ein
   * Zufallspasswort erfüllt diese Bedingung nie: Dazu müssten ALLE seine
   * Buchstaben und Ziffern Reihen sein. Bleibt auch nur ein Buchstabe
   * stehen, wird ohne Listenwort nichts gemessen — „XAAAhkCvim18" mit der
   * zufälligen Dreiergruppe ist ein Zufallspasswort und kein Muster.
   *
   * WAS IN DER MELDUNG STEHT, IST SICHER FÜR innerHTML, und zwar nach Bauart:
   * Die Wörter kommen aus HAEUFIG (fest in dieser Datei), die Ziffern aus
   * `\d+`, Reihen und Rest aus dem normalisierten Text, der nur [a-z0-9]
   * enthält; Sonderzeichen werden gezählt und nie zitiert. Kein Zeichen der
   * Eingabe kommt ungefiltert durch — `anzeige()` verlässt sich darauf.
   */
  function warumHaeufig(pw) {
    const s = String(pw);
    for (const k of [normal(s), kern(s)]) {
      if (k !== '' && HAEUFIG.some(h => k === h)) {
        return { text: '„' + k + '" steht in jeder Liste, die beim Durchprobieren zuerst versucht wird.',
                 rat: true };
      }
    }
    // Reine Ziffernfolge: unter 16 Stellen zu wenig, um von Hand gewählt
    // ausreichend zu sein — der Suchraum ist dort schlicht zu klein.
    if (/^\d+$/.test(s) && s.length < 16) {
      return { text: 'Nur Ziffern — unter 16 Stellen ist der Suchraum zu klein.', rat: true };
    }
    const z = zerlege(s);
    const uebrig = z.rest.length + z.sonder;
    const gelaeufig = z.woerter.length > 0;
    if (uebrig >= MIN_REST || (!gelaeufig && z.rest !== '')) { return null; }

    const gestrichen = [];
    for (const w of z.woerter) { gestrichen.push('„' + w + '" (geläufig)'); }
    if (z.ziffern !== '') { gestrichen.push('„' + z.ziffern + '" (angehängte Ziffern)'); }
    for (const r of z.reihen) { gestrichen.push('„' + r + '" (Reihe)'); }
    for (const n of z.sonderReihen) { gestrichen.push('eine Reihe aus ' + n + ' Sonderzeichen'); }
    let m;
    if (gestrichen.length === 0) {
      /* Nichts gestrichen und nichts übrig: Das Passwort besteht aus den
         vier Trennern, die nicht zählen. */
      m = 'Bindestrich, Punkt, Unterstrich und Leerzeichen zählen nicht — es bleibt nichts übrig.';
    } else if (uebrig === 0) {
      m = 'Ohne ' + gestrichen.join(', ') + ' bleibt nichts übrig.';
    } else if (z.rest === '') {
      m = 'Ohne ' + gestrichen.join(', ') + (z.sonder === 1 ? ' bleibt nur 1 Sonderzeichen' : ' bleiben nur ' + z.sonder + ' Sonderzeichen')
        + '; mindestens ' + MIN_REST + ' Zeichen müssen es sein.';
    } else {
      const was = '„' + z.rest + '"' + (z.sonder > 0 ? ' und ' + z.sonder + ' Sonderzeichen' : '');
      m = 'Ohne ' + gestrichen.join(', ') + (uebrig === 1 ? ' bleibt nur 1 Zeichen' : ' bleiben nur ' + uebrig + ' Zeichen')
        + ' (' + was + '); mindestens ' + MIN_REST + ' müssen es sein.';
    }
    return { text: m, rat: gelaeufig };
  }

  function istHaeufig(pw) { return warumHaeufig(pw) !== null; }

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
     jeder Meldung, die zu wenig Substanz bemängelt — zu kurz, ein
     Listenwort, nur Ziffern —, statt nur im Handbuch: Wer hier abgewiesen
     wird, hängt sonst ein Ausrufezeichen an und probiert es nochmal. Länge
     schlägt Zeichenvielfalt — das ist die Rechnung eines Rateangriffs und
     der Grund, warum `staerke()` unten die Länge dreimal zählt und die
     Zeichenarten einmal. NICHT steht er bei einer Reihe: Wer „aaaaaaaaaaa1"
     tippt, braucht keinen Rat zu Wörtern, sondern die Auskunft, dass das
     eine Reihe ist (`warumHaeufig()` entscheidet das über `rat`). */
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
    const warum = warumHaeufig(s);
    if (warum !== null) {
      return { erlaubt: false, staerke: 0, stufe: STUFEN[0],
               meldung: warum.text + (warum.rat ? ' ' + RAT_PASSPHRASE : '') };
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
       beide aus STUFEN und festen Zeichenketten dieser Datei stammen. Was
       `warumHaeufig()` aus der Eingabe zitiert, ist vorher auf [a-z0-9]
       normalisiert (siehe dort); Sonderzeichen erscheinen nur als Zahl, das
       Passwort selbst steht nie darin. */
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
