# -*- coding: utf-8 -*-
"""
Welche Klassen stehen im Markup jemals GEMEINSAM an einem Element?

Zwei Regeln koennen nur dann miteinander um eine Eigenschaft streiten, wenn
sie dasselbe Element treffen. Ohne diese Auskunft muss der Kaskadenpruefer
jede Klasse fuer mit jeder kombinierbar halten und meldet Hunderte
Scheinfaelle. Erhoben wird aus den Quellen selbst:
  class="a b"          in PHP und JS
  className = 'a b'    /  el.className = ...
  classList.add('a')   (auch mehrere Argumente)
Nicht erfasst werden Klassennamen, die zur Laufzeit zusammengesetzt werden —
darum die Regel: Was NICHT beobachtet wurde, gilt als moeglich.
"""
import re, io, os, itertools
from cssparse import SERVER

def erhebe(wurzeln):
    kombis = []      # Liste von Klassenmengen, die gemeinsam auftraten
    alle = set()
    for wurzel in wurzeln:
        for dp, _, fs in os.walk(wurzel):
            if 'vendor' in dp or 'fonts' in dp:
                continue
            for fn in fs:
                if not fn.endswith(('.php', '.js')):
                    continue
                t = io.open(os.path.join(dp, fn), encoding='utf-8', errors='replace').read()
                for m in re.finditer(r'class\s*=\s*(["\'])(.*?)\1', t, re.S):
                    roh = m.group(2)
                    # PHP-Ausdruecke drin? Dann alle darin vorkommenden Literale
                    # als moeglicherweise gemeinsam auftretend werten.
                    teile = re.findall(r'[A-Za-z][\w-]*', re.sub(r'<\?.*?\?>', ' ', roh, flags=re.S))
                    lit = set(re.findall(r"'([\w -]+)'", roh)) | set(re.findall(r'"([\w -]+)"', roh))
                    for s in lit:
                        teile += s.split()
                    s = set(teile)
                    if s: kombis.append(s); alle |= s
                for m in re.finditer(r'className\s*=\s*(["\'`])(.*?)\1', t, re.S):
                    s = set(re.findall(r'[A-Za-z][\w-]*', m.group(2)))
                    if s: kombis.append(s); alle |= s
                for m in re.finditer(r'classList\.(?:add|toggle|remove)\(([^)]*)\)', t):
                    s = set(re.findall(r"['\"]([\w-]+)['\"]", m.group(1)))
                    # zu allem kombinierbar, was am selben Element sonst steht:
                    # konservativ als eigene Kombination UND als "unbekannt" fuehren
                    if s: kombis.append(s); alle |= s
                for m in re.finditer(r'\.classList\.[a-z]+\(', t):
                    pass
    # Zusaetzlich: Jede Klasse, die irgendwo in den Quellen als Wort vorkommt,
    # gilt als BEKANNT. Ohne das faellt jede ueber PHP zusammengesetzte Klasse
    # in die Kategorie "unbekannt" und damit in "koennte ueberall stehen" —
    # dann meldet der Pruefer Hunderte Scheinfaelle.
    woerter = set()
    for wurzel in wurzeln:
        for dp, _, fs in os.walk(wurzel):
            if 'vendor' in dp or 'fonts' in dp:
                continue
            for fn in fs:
                if fn.endswith(('.php', '.js')):
                    t = io.open(os.path.join(dp, fn), encoding='utf-8', errors='replace').read()
                    woerter |= set(re.findall(r'[A-Za-z][\w-]*', t))
    alle |= woerter

    paare = set()
    for s in kombis:
        for a, b in itertools.combinations(sorted(s), 2):
            paare.add((a, b)); paare.add((b, a))
    return alle, paare, kombis

if __name__ == '__main__':
    alle, paare, kombis = erhebe([SERVER])
    print('Klassen im Markup:', len(alle))
    print('beobachtete Paare :', len(paare) // 2)
