/* Einsatzdoku — Passwortgüte (Baustein B9).
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
   *  Sicherungsdatei. Beide Stellen schützen dieselben Angaben; zwei
   *  verschiedene Mindestlängen (10 und 8) waren nur historisch begründet. */
  const MIN_LAENGE = 10;

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

       KEINE Kuerzel wie "nef", "rth", "naw": Der Vergleich unten ist
       `k === h || (h.length >= 6 && k.includes(h))`. Woerter unter sechs
       Zeichen treffen also nur, wenn das GANZE normalisierte Passwort genau
       so lautet — und ein dreibuchstabiges Passwort scheitert schon an der
       Mindestlaenge. Ein Kuerzel in der Liste braeuchte einen Teilstring-
       Vergleich, und der traefe massenhaft brauchbare Passwoerter.

       KEIN "nadoku". Der kuenftige Produktname war vorgesehen, ist aber
       wieder herausgenommen: Der Vergleich ist ein Teilstring-Vergleich, und
       das Demo-Passwort dieser Anwendung lautet `nadokudemo0815`. Mit
       "nadoku" in der Liste laesst es sich nicht mehr setzen (pw_handling)
       und nicht mehr als Backup- oder Exportpasswort verwenden
       (einstellungen.php, import.php pruefen `guete.erlaubt`) — gemessen: der
       Kreislauftest scheiterte daran, weil die erneute Sicherung gar nicht
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
   * Enthält das Passwort ein bekanntes Allerweltswort?
   *
   * Geprüft werden BEIDE Normalisierungen. Nur die gekürzte zu prüfen wäre ein
   * Loch: Aus „1234567890" bliebe eine leere Zeichenkette, und ausgerechnet
   * die Zahlenreihen — die in jeder Liste ganz oben stehen — kämen durch.
   */
  function istHaeufig(pw) {
    for (const k of [normal(pw), kern(pw)]) {
      if (k === '') { continue; }
      if (HAEUFIG.some(h => k === h || (h.length >= 6 && k.includes(h)))) { return true; }
    }
    // Reine Ziffernfolge: unter 16 Stellen zu wenig, um von Hand gewählt
    // ausreichend zu sein — der Suchraum ist dort schlicht zu klein.
    return /^\d+$/.test(String(pw)) && String(pw).length < 16;
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

  /**
   * Vollständige Prüfung.
   * @returns {{erlaubt:boolean, staerke:number, stufe:string, meldung:string}}
   */
  function pruefe(pw) {
    const s = String(pw == null ? '' : pw);
    if (s.length < MIN_LAENGE) {
      return { erlaubt: false, staerke: 0, stufe: STUFEN[0],
               meldung: `Mindestens ${MIN_LAENGE} Zeichen.` };
    }
    if (istHaeufig(s)) {
      return { erlaubt: false, staerke: 0, stufe: STUFEN[0],
               meldung: 'Dieses Passwort ist zu geläufig — es steht in jeder Liste, '
                      + 'die beim Durchprobieren zuerst versucht wird.' };
    }
    if (istMuster(s)) {
      return { erlaubt: false, staerke: 0, stufe: STUFEN[0],
               meldung: 'Eine reine Zeichenfolge ist kein Passwort.' };
    }
    const st = staerke(s);
    return { erlaubt: true, staerke: st, stufe: STUFEN[st],
             meldung: st <= 1
               ? 'Das geht — länger wäre deutlich besser. Die Stärke des Passworts '
                 + 'ist unmittelbar die Stärke der Verschlüsselung.'
               : '' };
  }

  /**
   * Schreibt das Ergebnis in ein Element. Erwartet ein beliebiges Element;
   * die Klasse pwq-N erlaubt der Seite eine farbliche Darstellung.
   */
  function anzeige(el, ergebnis) {
    if (!el) { return; }
    el.className = 'pwquality pwq-' + ergebnis.staerke;
    el.textContent = 'Stärke: ' + ergebnis.stufe
                   + (ergebnis.meldung ? ' — ' + ergebnis.meldung : '');
  }

  /**
   * Hängt die Anzeige an ein Eingabefeld. Liefert eine Funktion, die den
   * aktuellen Prüfstand abfragt — zum Aufruf beim Absenden.
   */
  function beobachte(feld, anzeigeEl) {
    let letzte = pruefe('');
    const lauf = () => {
      letzte = pruefe(feld.value);
      if (feld.value === '') {
        if (anzeigeEl) { anzeigeEl.textContent = ''; anzeigeEl.className = 'pwquality'; }
      } else {
        anzeige(anzeigeEl, letzte);
      }
    };
    feld.addEventListener('input', lauf);
    return () => letzte;
  }

  return { MIN_LAENGE, pruefe, staerke, anzeige, beobachte };
})();
