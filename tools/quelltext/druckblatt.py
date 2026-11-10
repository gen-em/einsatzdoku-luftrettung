#!/usr/bin/env python3
"""Druckblätter ohne Netz — schickt keine Seite einen Wert an ein Druckblatt?

    python3 tools/quelltext/druckblatt.py
    python3 tools/quelltext/druckblatt.py --selbstprobe

Rückgabewert 0 = keine Befunde · 1 = Befunde · 2 = gemessen wurde nichts
(eines der beiden Blätter fehlt).

WOFÜR (KA-02, F-KA-01, -36; Anlass Nr. 560). Bis Web 24.10.1 schickte der
Druckknopf den Wiederherstellungsschlüssel per POST an `notfallblatt.php`
— durch das Netz und jedes Anfrageprotokoll dazwischen —, und
`codeblatt.php` gab jeden geschickten Wert auf einem echt aussehenden Blatt
wieder. Seit KA-02 öffnen die Knöpfe das leere Blatt, und
`assets/blatt-uebergabe.js` reicht die Werte von Fenster zu Fenster. Dieser
Riegel hält, dass niemand den alten Weg wieder einbaut.

WAS ER MISST:

  formular  kein `<form …action="…notfallblatt.php|codeblatt.php">` in
            `server/` (PHP und JS)
  abruf     kein `fetch(`, `postForm(`, `postJson(` oder
            `XMLHttpRequest`-Aufruf mit einem der beiden Ziele in `server/`
  lesen     die beiden Blätter lesen keinen Anfragewert (`$_POST`,
            `$_REQUEST`, `$_GET`, `post_text(`, `get_text(`, `php://input`)
  abweisen  beide Blätter antworten auf alles außer GET mit 400

WAS NICHT. Ob der Code wirklich nicht im Netz steht, misst die Browserprobe
`notfallblatt` (sie schneidet jede Anfrage mit); dieser Riegel liest nur
Quelltext. Den Feldnamen `code` misst er nicht: Den führen auch die
Formulare des Zweitfaktors.
"""
import os
import re
import sys
import tempfile

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
BLAETTER = ('server/notfallblatt.php', 'server/codeblatt.php')
ZIEL = r'(?:notfallblatt|codeblatt)\.php'
FORM_RE = re.compile(r'<form\b[^>]*\baction\s*=\s*["\']?[^"\'>\s]*' + ZIEL, re.I | re.S)
ABRUF_RE = re.compile(r'\b(?:fetch|postForm|postJson|open)\s*\(\s*[`"\'][^`"\']*' + ZIEL)
LESEN_RE = re.compile(r'\$_(?:POST|REQUEST|GET)\b|\bpost_text\s*\(|\bget_text\s*\(|php://input')
ABWEISEN_RE = re.compile(r"REQUEST_METHOD[^;]*!==\s*'GET'[\s\S]{0,200}?http_response_code\(\s*400\s*\)")


def quellen(wurzel):
    for ordner, _, namen in os.walk(os.path.join(wurzel, 'server')):
        if '/vendor' in ordner.replace(os.sep, '/'):
            continue
        for n in namen:
            if n.endswith(('.php', '.js')):
                yield os.path.join(ordner, n)


def messen(wurzel):
    """(Zahl der gelesenen Dateien, Befunde als [(Datei, Regel, Text)])."""
    befunde = []
    n = 0
    for pfad in quellen(wurzel):
        rel = os.path.relpath(pfad, wurzel).replace(os.sep, '/')
        with open(pfad, encoding='utf-8', errors='replace') as f:
            text = f.read()
        n += 1
        for m in FORM_RE.finditer(text):
            zeile = text.count('\n', 0, m.start()) + 1
            befunde.append((f'{rel}:{zeile}', 'formular',
                            'ein Formular schickt an ein Druckblatt (F-KA-01)'))
        if rel != 'server/assets/blatt-uebergabe.js':
            for m in ABRUF_RE.finditer(text):
                zeile = text.count('\n', 0, m.start()) + 1
                befunde.append((f'{rel}:{zeile}', 'abruf',
                                'ein Abruf mit einem Druckblatt als Ziel (F-KA-01)'))
    gefunden = 0
    for rel in BLAETTER:
        pfad = os.path.join(wurzel, rel)
        if not os.path.isfile(pfad):
            continue
        gefunden += 1
        with open(pfad, encoding='utf-8') as f:
            text = f.read()
        # Kommentare zählen nicht: Der Kopf erzählt, wie es bis KA-02 war.
        code = re.sub(r'/\*[\s\S]*?\*/|(?<![:"\'])//[^\n]*', '', text)
        for m in LESEN_RE.finditer(code):
            befunde.append((rel, 'lesen', f'liest einen Anfragewert ({m.group(0)}) (F-KA-36)'))
        if not ABWEISEN_RE.search(code):
            befunde.append((rel, 'abweisen', 'weist einen POST nicht mit 400 ab'))
    return (n if gefunden == len(BLAETTER) else 0), befunde


def lauf(wurzel):
    n, befunde = messen(wurzel)
    for ort, regel, text in befunde:
        print(f'  ! {ort} [{regel}]: {text}')
    if n == 0:
        print('Eines der beiden Druckblätter fehlt — gemessen wurde nichts.')
        return 2
    print(f'{n} Dateien unter server/ gelesen, {len(BLAETTER)} Druckblätter, {len(befunde)} Befunde')
    return 1 if befunde else 0


GUT_BLATT = ("<?php\nif (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {\n"
             "    http_response_code(400);\n    exit;\n}\n"
             "/* bis KA-02: $code = post_text('code'); */\n?><main data-blatt-empfang></main>\n")


def selbstprobe():
    """Die alten Wege rot, der neue grün."""
    def baum(**ueber):
        b = {'server/notfallblatt.php': GUT_BLATT, 'server/codeblatt.php': GUT_BLATT,
             'server/pw_handling.php': '<button data-blatt-ziel="notfallblatt.php">x</button>\n',
             'server/assets/blatt-uebergabe.js': "window.open(ziel, '_blank'); open('codeblatt.php');\n"}
        b.update(ueber)
        return {k: v for k, v in b.items() if v is not None}

    faelle = [
        ('GEGENPROBE: Knopf, Blätter ohne Anfragewert, Abweisung da', baum(), 0),
        ('ein Formular an notfallblatt.php (F-KA-01)',
         baum(**{'server/pw_handling.php': '<form method="post" action="notfallblatt.php" target="_blank">'}), 1),
        ('ein Formular an codeblatt.php, Attribut ohne Anführungszeichen',
         baum(**{'server/zweitfaktor_teile.php': '<form method=post action=codeblatt.php>'}), 1),
        ('ein fetch an notfallblatt.php',
         baum(**{'server/assets/x.js': "fetch('notfallblatt.php', {method:'POST'})"}), 1),
        ('EdApi.postForm an codeblatt.php',
         baum(**{'server/assets/y.js': "EdApi.postForm('codeblatt.php', {codes})"}), 1),
        ('notfallblatt.php liest post_text (F-KA-01)',
         baum(**{'server/notfallblatt.php': GUT_BLATT + "<?php $c = post_text('code');"}), 1),
        ('codeblatt.php liest $_POST (F-KA-36)',
         baum(**{'server/codeblatt.php': GUT_BLATT + "<?php $c = $_POST['codes'];"}), 1),
        ('ein Blatt ohne Abweisung',
         baum(**{'server/codeblatt.php': '<?php echo 1;'}), 1),
        ('eines der Blätter fehlt — gemessen wurde nichts',
         baum(**{'server/codeblatt.php': None}), 2),
    ]
    fehl = 0
    for name, b, soll in faelle:
        with tempfile.TemporaryDirectory(prefix='druckblatt-') as d:
            for rel, inhalt in b.items():
                os.makedirs(os.path.dirname(os.path.join(d, rel)), exist_ok=True)
                with open(os.path.join(d, rel), 'w', encoding='utf-8') as f:
                    f.write(inhalt)
            n, befunde = messen(d)
            ist = 2 if n == 0 else (1 if befunde else 0)
        ok = ist == soll
        fehl += not ok
        print(f"  [{'ok  ' if ok else 'FEHL'}] {name}  (erwartet {soll}, gemessen {ist})")
    print(f'{len(faelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


if __name__ == '__main__':
    sys.exit(selbstprobe() if '--selbstprobe' in sys.argv[1:] else lauf(WURZEL))
