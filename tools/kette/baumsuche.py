#!/usr/bin/env python3
"""Welche grünen Läufe von `pruefung.yml` haben diesen Baum? — die eine Stelle.

    python3 tools/kette/baumsuche.py --baum <sha> --fenster 30 --ereignis pull_request --erster
    python3 tools/kette/baumsuche.py --baum <sha> --fenster 50 --ausgabe stufe1.json
    python3 tools/kette/baumsuche.py --selbstprobe

Rückgabewert mit `--erster`: 0 = ein Lauf mit diesem Baum und grünem Job
`Stufe 1` (auf stdout: `<lauf> <kopf-sha> <fertig>`), 1 = keiner im Fenster,
2 = nicht feststellbar (kein eigener Baum, Liste nicht abrufbar).
Ohne `--erster`: 0, sobald die Liste geschrieben ist — auch leer. Das Urteil
fällt dort `freigabe.py`, und eine leere Liste hält dessen Tor zu.

WOFÜR (Konzept BR, E-BR-09). Die Frage stand bis BR-03 zweimal als Bash: in
`pruefung.yml` („Schon gemessen?", Fenster 30, nur PR-Läufe, erster Treffer)
und in `ausliefern-lauf.yml` (Produktionstor, Fenster 50, alle Läufe, Liste
für `freigabe.py`) — die eine mit, die andere ohne Selbstprobe (F-BR-05 (a)).
Die Unterschiede sind Schalter geblieben; die Suche ist eine.

WAS SIE TUT (Konzept TB). Die grünen Läufe von `pruefung.yml` holen —
höchstens `--fenster` viele, wahlweise nur eines Ereignisses —, je Kopf-Commit
den Baum aus der API, und NUR bei gleichem Baum die Jobs des Laufs: Gezählt
wird der Job `Stufe 1` mit `success`, nicht der Lauf. Ein Lauf ist auch grün,
wenn `Stufe 1` übersprungen wurde — der Push-Lauf auf `main`, der auf einen
PR-Lauf verweist (F-TB-06).

DAS FENSTER IST EINE KOSTENGRENZE, KEIN SICHERHEITSWERT (E-TB-09), und
deshalb Pflicht: Jeder Aufrufer nennt seine. Ein leerer Baum trifft nie
(E-TB-04). Der Jobname ist zugleich die Pflichtprüfung des Rulesets — wer
ihn umbenennt, ändert `JOB` mit, sonst zählt die Suche still null.

GESPROCHEN WIRD ÜBER `gh api`, wie vorher im Bash; `GH_TOKEN` und
`GITHUB_REPOSITORY` kommen aus der Umgebung des Laufs.
"""
import argparse
import json
import os
import subprocess
import sys

JOB = 'Stufe 1'
LAUF = 'pruefung.yml'


def melde(t):
    print(t, file=sys.stderr, flush=True)


def gh_api(pfad):
    """JSON einer API-Antwort, oder None — ein Fehlschlag ist keine leere Liste."""
    r = subprocess.run(['gh', 'api', pfad], capture_output=True, text=True)
    if r.returncode != 0:
        return None
    try:
        return json.loads(r.stdout)
    except json.JSONDecodeError:
        return None


def norm(sha):
    return str(sha or '').strip().lower()


def suchen(baum, fenster, ereignis, erster, api, repo):
    """Die Suche. `api` ist austauschbar — die Selbstprobe reicht einen Nachbau."""
    baum = norm(baum)
    aus = {'laeufe': [], 'geholt': 0, 'treffer': 0, 'gescheitert': 0,
           'erster': None, 'abrufbar': True}
    pfad = f'repos/{repo}/actions/workflows/{LAUF}/runs?status=success&per_page={int(fenster)}'
    if ereignis:
        pfad += f'&event={ereignis}'
    liste = api(pfad)
    if not isinstance(liste, dict):
        aus['abrufbar'] = False
        return aus
    baeume = {}
    for lauf in liste.get('workflow_runs', [])[:int(fenster)]:
        aus['geholt'] += 1
        kopf = norm(lauf.get('head_sha'))
        if kopf not in baeume:
            c = api(f'repos/{repo}/git/commits/{kopf}') if kopf else None
            baeume[kopf] = norm((c or {}).get('tree', {}).get('sha')) if isinstance(c, dict) else ''
            if not baeume[kopf]:
                aus['gescheitert'] += 1
        lb = baeume[kopf]
        ok, fertig = False, ''
        if baum and lb == baum:                      # nur bei Treffer die Jobs (E-TB-09)
            aus['treffer'] += 1
            jobs = api(f"repos/{repo}/actions/runs/{lauf.get('id')}/jobs")
            for j in (jobs or {}).get('jobs', []) if isinstance(jobs, dict) else []:
                if j.get('name') == JOB and j.get('conclusion') == 'success':
                    ok, fertig = True, j.get('completed_at') or ''
                    break
            if not ok:
                melde(f"Lauf {lauf.get('id')}: gleicher Baum, aber Job `{JOB}` nicht grün — zählt nicht")
        eintrag = {'id': lauf.get('id'), 'event': lauf.get('event'),
                   'head_branch': lauf.get('head_branch'), 'head_sha': kopf,
                   'baum': lb, 'stufe1_ok': ok}
        if ok:
            eintrag['fertig'] = fertig
        aus['laeufe'].append(eintrag)
        if ok and erster:
            aus['erster'] = eintrag
            break
    return aus


def lauf(a):
    repo = a.repo or os.environ.get('GITHUB_REPOSITORY', '')
    if a.ausgabe:                                    # eine Liste steht immer da, auch beim Absturz davor
        with open(a.ausgabe, 'w', encoding='utf-8') as f:
            f.write('[]\n')
    if not norm(a.baum):
        melde('Eigener Baum nicht ermittelt — die Suche trifft nie (E-TB-04).')
        if a.erster:
            return 2
    if not repo:
        melde('GITHUB_REPOSITORY fehlt und --repo ist nicht gesetzt.')
        return 2
    e = suchen(a.baum, a.fenster, a.ereignis, a.erster, gh_api, repo)
    if not e['abrufbar']:
        melde(f'Liste der Läufe von {LAUF} nicht abrufbar.')
        return 2 if a.erster else 0
    melde(f"Stufe 1: {e['geholt']} Läufe geholt, {e['treffer']} mit Baum {norm(a.baum)[:7] or '—'}, "
          f"{e['gescheitert']} Baum-Abrufe gescheitert (Fenster {a.fenster})")
    if a.ausgabe:
        with open(a.ausgabe, 'w', encoding='utf-8') as f:
            json.dump(e['laeufe'], f, ensure_ascii=False)
    if a.erster:
        if e['erster'] is None:
            return 1
        t = e['erster']
        print(f"{t['id']} {t['head_sha']} {t['fertig']}")
    return 0


# ------------------------------------------------------------ Selbstprobe

BAUM = 'a' * 40
FREMD = 'b' * 40


def _nachbau(laeufe, baeume, jobs, liste_da=True):
    """Eine API aus drei Tabellen; zählt, was gefragt wurde."""
    anfragen = []

    def api(pfad):
        anfragen.append(pfad)
        if '/runs?' in pfad:
            return {'workflow_runs': laeufe} if liste_da else None
        if '/git/commits/' in pfad:
            s = baeume.get(pfad.rsplit('/', 1)[1])
            return {'tree': {'sha': s}} if s else None
        if pfad.endswith('/jobs'):
            return {'jobs': jobs.get(int(pfad.split('/')[-2]), [])}
        return None
    return api, anfragen


def _l(i, kopf, ereignis='pull_request'):
    return {'id': i, 'event': ereignis, 'head_branch': f'z{i}', 'head_sha': kopf}


GRUEN = [{'name': JOB, 'conclusion': 'success', 'completed_at': '2026-09-24T10:00:00Z'}]
UEBERSPRUNGEN = [{'name': JOB, 'conclusion': 'skipped'}]


def selbstprobe():
    k1, k2, k3 = '1' * 40, '2' * 40, '3' * 40
    faelle = []

    api, anf = _nachbau([_l(1, k1)], {k1: BAUM}, {1: GRUEN})
    e = suchen(BAUM, 30, 'pull_request', True, api, 'o/r')
    faelle.append(('gleicher Baum, Job grün — gefunden', e['erster'] is not None and e['erster']['id'] == 1))
    faelle.append(('das Fenster und das Ereignis stehen in der Anfrage',
                   'per_page=30' in anf[0] and 'event=pull_request' in anf[0]))

    api, _ = _nachbau([_l(1, k1, 'push')], {k1: BAUM}, {1: UEBERSPRUNGEN})
    e = suchen(BAUM, 30, None, True, api, 'o/r')
    faelle.append(('gleicher Baum, Job übersprungen — zählt nicht (F-TB-06)', e['erster'] is None))

    api, anf = _nachbau([_l(1, k1), _l(2, k2)], {k1: FREMD, k2: FREMD}, {1: GRUEN, 2: GRUEN})
    e = suchen(BAUM, 30, None, True, api, 'o/r')
    faelle.append(('anderer Baum — keiner, und keine Jobs abgefragt (E-TB-09)',
                   e['erster'] is None and not any(p.endswith('/jobs') for p in anf)))

    api, _ = _nachbau([], {}, {}, liste_da=False)
    e = suchen(BAUM, 30, None, True, api, 'o/r')
    faelle.append(('Liste nicht abrufbar — nicht feststellbar, nicht „keiner"', e['abrufbar'] is False))

    api, _ = _nachbau([_l(1, k1)], {k1: ''}, {1: GRUEN})
    e = suchen('', 30, None, True, api, 'o/r')
    faelle.append(('leerer eigener Baum trifft nie, auch keinen leeren (E-TB-04)', e['erster'] is None and e['treffer'] == 0))

    api, _ = _nachbau([_l(1, k1)], {}, {1: GRUEN})
    e = suchen(BAUM, 30, None, True, api, 'o/r')
    faelle.append(('Baum-Abruf gescheitert — gezählt, kein Treffer', e['gescheitert'] == 1 and e['erster'] is None))

    api, _ = _nachbau([_l(1, k1)], {k1: BAUM.upper()}, {1: GRUEN})
    e = suchen(BAUM, 30, None, True, api, 'o/r')
    faelle.append(('groß geschriebener Baum ist derselbe', e['erster'] is not None))

    api, anf = _nachbau([_l(1, k1, 'push'), _l(2, k1), _l(3, k3)], {k1: BAUM, k3: FREMD},
                        {1: UEBERSPRUNGEN, 2: GRUEN})
    e = suchen(BAUM, 50, None, False, api, 'o/r')
    faelle.append(('ohne --erster: alle Läufe, stufe1_ok nur beim grünen Job',
                   [x['stufe1_ok'] for x in e['laeufe']] == [False, True, False]))
    faelle.append(('ein Kopf, zwei Läufe — ein Baum-Abruf',
                   sum(1 for p in anf if '/git/commits/' in p) == 2))

    api, _ = _nachbau([_l(i, str(i) * 40) for i in range(1, 8)], {}, {})
    e = suchen(BAUM, 5, None, False, api, 'o/r')
    faelle.append(('mehr Läufe als das Fenster — nur das Fenster zählt', e['geholt'] == 5))

    fehl = 0
    for name, ok in faelle:
        fehl += not ok
        print(f"  [{'ok  ' if ok else 'FEHL'}] {name}")
    print(f'{len(faelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('--baum', help='der eigene Baum (git rev-parse HEAD^{tree})')
    p.add_argument('--fenster', type=int, help='höchstens so viele Läufe — die Kostengrenze des Aufrufers')
    p.add_argument('--ereignis', help='nur Läufe dieses Ereignisses, etwa pull_request')
    p.add_argument('--erster', action='store_true', help='beim ersten Treffer aufhören und ihn nennen')
    p.add_argument('--ausgabe', help='die Liste als JSON für freigabe.py')
    p.add_argument('--repo', help='Vorgabe: GITHUB_REPOSITORY')
    p.add_argument('--selbstprobe', action='store_true')
    a = p.parse_args()
    if a.selbstprobe:
        return selbstprobe()
    if not a.fenster or a.fenster < 1:
        melde('--fenster fehlt: Jeder Aufrufer nennt seine Kostengrenze (E-TB-09).')
        return 2
    return lauf(a)


if __name__ == '__main__':
    sys.exit(main())
