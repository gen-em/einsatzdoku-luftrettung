# -*- coding: utf-8 -*-
"""
Baut die Proben (Fixtures) fuer stilvergleich.js.

Vier Stueck, jede beantwortet eine andere Frage:

  seiten.html    Das Markup ALLER Seiten, aus den PHP-Dateien mit entferntem
                 PHP-Anteil. Alle Zweige bleiben stehen — auch die von
                 if/else —, was hier erwuenscht ist: Es erhoeht die Abdeckung,
                 und verglichen wird ohnehin dieselbe DOM gegen sich selbst.
  js_markup.html Das Markup, das ERST IM BROWSER entsteht: HTML-Zeichenketten
                 aus den JS-Modulen und den Inline-Skripten der Seiten.
  katalog.html   Fuer JEDEN Selektor aus style.css ein Element, das ihn trifft.
                 Faengt Regeln, die im echten Markup nie vorkommen.
  pseudo.html    Dasselbe fuer die Zustaende: In beiden Stylesheets werden
                 :hover/:focus/:focus-visible/:focus-within/:active durch echte
                 Klassen ersetzt (gleiche Spezifitaet), damit sie messbar
                 werden. Erzeugt dafuer auch pseudo_alt.css / pseudo_neu.css.

Aufruf:  python3 proben.py <alt.css> [<neu.css>] [<ausgabeordner>]
         alt.css  ist der Vergleichsstand (z. B. aus `git show <ref>:...`)
         neu.css  ist der aktuelle Stand (Vorgabe: server/assets/style.css)
"""
import io, os, re, sys, glob
from cssparse import SERVER, CSS
import cssparse


def entphp(t):
    t = re.sub(r'<\?php.*?\?>', '', t, flags=re.S)
    t = re.sub(r'<\?=.*?\?>', 'X', t, flags=re.S)
    t = re.sub(r'<\?php.*', '', t, flags=re.S)          # offener Block am Ende
    return t


TAGS = (r'<(tr|td|th|li|div|span|button|a|p|dt|dd|details|summary|section|aside|'
        r'nav|main|header|footer|form|h1|h2|h3|h4|table|thead|tbody|label|input|'
        r'select|option|textarea|dialog|svg|use|fieldset|legend)\b')


def php_zeichenketten(text):
    """HTML-Schnipsel aus PHP-ZEICHENKETTEN — der blinde Fleck bis O12.

    `entphp()` schneidet alles zwischen <?php und ?> heraus. Das war richtig,
    solange das Markup einer Seite ZWISCHEN den PHP-Bloecken stand. Seit P3
    kommt es zum groessten Teil aus den Bausteinen in ui.php, und die bauen es
    mit `echo '<div class="zeile">'` — also INNERHALB eines PHP-Blocks. Genau
    davor warnte die LIESMICH: „mit jedem weiteren Baustein waechst der blinde
    Fleck." Nach O11 war er die halbe Oberflaeche.

    Geholt wird jede Zeichenkette, die wie Markup aussieht; PHP-Ausdruecke
    darin werden zu X, wie in jsprobe(). Doppelt gezaehlte Schnipsel schaden
    nicht — verglichen wird dieselbe DOM gegen sich selbst.
    """
    st = []
    for muster in (r"'((?:[^'\\\n]|\\.)*)'", r'"((?:[^"\\\n]|\\.)*)"'):
        st += [m.group(1) for m in re.finditer(muster, text, re.S)]
    h = [x for x in st if re.search(TAGS, x)]
    h = [x.replace("\\'", "'").replace('\\"', '"') for x in h]
    return h


def seitenprobe():
    teile = []
    for pfad in sorted(glob.glob(SERVER + '/*.php')):
        t = io.open(pfad, encoding='utf-8', errors='replace').read()
        if '<' not in t:
            continue
        h = entphp(t)
        h = re.sub(r'<script\b.*?</script>', '', h, flags=re.S | re.I)
        h = re.sub(r'<style\b.*?</style>', '', h, flags=re.S | re.I)
        h = re.sub(r'<!doctype[^>]*>|</?html[^>]*>|<head>.*?</head>|</?body[^>]*>', '',
                   h, flags=re.S | re.I)
        # Dazu das Markup, das in PHP-Zeichenketten steckt (siehe oben).
        aus_php = php_zeichenketten(t)
        if aus_php:
            h += '\n<div data-quelle="%s (Zeichenketten)">%s</div>' % (
                os.path.basename(pfad), '\n'.join(aus_php))
        if h.strip():
            teile.append('<div data-quelle="%s">%s</div>' % (os.path.basename(pfad), h))
    return '\n'.join(teile)


def jsprobe():
    teile = []
    for pfad in sorted(glob.glob(SERVER + '/assets/*.js')) + sorted(glob.glob(SERVER + '/*.php')):
        if 'vendor' in pfad:
            continue
        t = io.open(pfad, encoding='utf-8', errors='replace').read()
        if pfad.endswith('.php'):
            t = '\n'.join(re.findall(r'<script\b[^>]*>(.*?)</script>', t, re.S | re.I))
        st = []
        for muster in (r'`([^`]*)`', r"'((?:[^'\\\n]|\\.)*)'", r'"((?:[^"\\\n]|\\.)*)"'):
            st += [m.group(1) for m in re.finditer(muster, t, re.S)]
        h = [x for x in st
             if re.search(r'<(tr|td|th|li|div|span|button|a|p|dt|dd|details|summary|'
                          r'table|thead|tbody|label|input|select|option)\b', x)]
        h = [re.sub(r'\$\{[^}]*\}', 'X', x) for x in h]
        if h:
            teile.append('<div data-quelle="%s">%s</div>' % (os.path.basename(pfad), '\n'.join(h)))
    return '\n'.join(teile)


# `:disabled` STEHT MIT IN DIESER LISTE, obwohl es kein Bedienzustand im Sinne
# von :hover ist: Es laesst sich an einem `<div>` des Katalogs nicht herstellen,
# und der Katalog baut aus jedem Selektor ein `<div>`, wenn der Selektor keinen
# Tag nennt. Die Regel `.feld-eingabe:disabled` (S8/AP7) waere damit in KEINER
# Probe gemessen worden — der Stilvergleich haette zu ihr geschwiegen und das
# wie ein „unveraendert" ausgesehen. Mit der Ersetzung traegt der Katalog ein
# `div.feld-eingabe.pcdisabled`, und beide Staende werden daran gemessen.
PSEUDO = [(':focus-visible', '.pcfocusvis'), (':focus-within', '.pcfocuswithin'),
          (':focus', '.pcfocus'), (':hover', '.pchover'), (':active', '.pcactive'),
          (':disabled', '.pcdisabled')]


def ohne_pseudo(sel):
    sel = re.sub(r'::[a-z-]+', '', sel)
    return re.sub(r':(hover|focus-visible|focus-within|focus|active|first-of-type|'
                  r'last-child|first-child|empty|fullscreen|-webkit-full-screen|'
                  r'not\([^)]*\)|nth-child\([^)]*\))', '', sel)


def element(k, innen):
    tag = re.match(r'^([a-zA-Z][\w-]*)', k)
    name = tag.group(1) if tag else 'div'
    cls = ' '.join(re.findall(r'\.([\w-]+)', k))
    ids = re.findall(r'#([\w-]+)', k)
    idp = (' id="%s"' % ids[0]) if ids else ''
    attrs = ' '.join('%s="%s"' % (a, (b or '').strip('"\''))
                     for a, b in re.findall(r'\[([^\]=]+)(?:=([^\]]+))?\]', k))
    if name in ('input', 'img', 'br'):
        return '<%s%s class="%s" %s>' % (name, idp, cls, attrs)
    return '<%s%s class="%s" %s>%s</%s>' % (name, idp, cls, attrs, innen or 'x', name)


def katalog(css_pfad):
    sels = sorted({r.sel for r in cssparse.lies(css_pfad) if not r.sel.startswith('@')})
    zeilen = ['<div data-quelle="katalog">']
    for s in sels:
        teile = [p for p in re.split(r'\s*>\s*|\s+', ohne_pseudo(s).strip()) if p]
        if not teile:
            continue
        innen = ''
        for k in reversed(teile):
            innen = element(k, innen)
        zeilen.append('<div data-sel="%s">%s</div>' % (s.replace('"', "'"), innen))
    zeilen.append('</div>')
    return '\n'.join(zeilen)


def main():
    alt = sys.argv[1]
    neu = sys.argv[2] if len(sys.argv) > 2 else CSS
    aus = sys.argv[3] if len(sys.argv) > 3 else os.path.join(os.getcwd(), 'pruefstand')
    fix = os.path.join(aus, 'fixtures')
    os.makedirs(fix, exist_ok=True)

    io.open(fix + '/seiten.html', 'w', encoding='utf-8').write(seitenprobe())
    io.open(fix + '/js_markup.html', 'w', encoding='utf-8').write(jsprobe())
    io.open(fix + '/katalog.html', 'w', encoding='utf-8').write(katalog(neu))

    def wandle(p):
        s = io.open(p, encoding='utf-8').read()
        for a, b in PSEUDO:
            s = s.replace(a, b)
        return s
    io.open(aus + '/pseudo_alt.css', 'w', encoding='utf-8').write(wandle(alt))
    io.open(aus + '/pseudo_neu.css', 'w', encoding='utf-8').write(wandle(neu))
    io.open(fix + '/pseudo.html', 'w', encoding='utf-8').write(katalog(aus + '/pseudo_neu.css'))

    print('Proben in %s' % fix)
    for f in sorted(os.listdir(fix)):
        print('  %-18s %7d Byte' % (f, os.path.getsize(os.path.join(fix, f))))


if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(2)
    main()
