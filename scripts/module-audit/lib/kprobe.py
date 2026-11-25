#!/usr/bin/env python3
"""module-audit: dependency proofs by execution (Kernel probe php/DepProbeTest.php run by phpunit in the instance).

Why a Kernel probe: on the audit site, a module X enabled by the profile (views, comment,
content_translation...) cannot be uninstalled to see what breaks without it. Here M is installed
on a fresh Kernel site (prefixed tables, no install profile applied, only system+user) through
the real module installer: X is simply never there.

  kprobe.py <out_dir> undeclared <pairs.tsv> <instance>...   lines "X<TAB>M<TAB>kinds"
  kprobe.py <out_dir> unused <pairs.tsv> <instance>...       lines "M<TAB>D"
                                                              (D removed from the instance's copy of M.info.yml during run A)

Per pair, run A = install [W..., M] without X (W = modules M's install reports missing — UnmetDependencies on a
module that is not X — added round by round, so the run reaches the part that needs X), then control run
B = same with X (undeclared) / with the original info.yml (unused). An undeclared dependency is reported only when
A shows an error that names X, is attributable to M (its class/service/plugin/route/config/cron, a file of M, or
"provided by M"), and is absent from B. An unused dependency is reported only when A installs M and shows no
error that B does not show. Results: deps/undeclared|unused/<M>__<X>.json; runs cached in deps/kruns/<M>/.
Resumable: existing result files are skipped (FORCE=1 redoes). Env: STACK (path of the stack command).
"""
import hashlib, json, os, queue, re, shutil, subprocess, sys, threading, time

A = os.path.abspath(sys.argv[1])
PHP = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'php')
STACK = os.environ['STACK']
S = json.load(open(f'{A}/static.json'))['modules']
ALL = set(open(f'{A}/all-modules.txt').read().split())
RUNS = f'{A}/deps/kruns'
FORCE = os.environ.get('FORCE') == '1'
WORK = '_module_audit'
lock = threading.Lock()
# Empty profile whose path (profiles/contrib/vactory) is a prefix of profiles/contrib/vactory_starter_kit:
# makes the profile modules discoverable in the Kernel probe without the profile's own config. Written in the
# instance only (an .info.yml in the repository would be discovered by every site using the profile).
PROBE_PROFILE = """name: 'module-audit dependency probe profile'
type: profile
description: 'Written by scripts/module-audit in a disposable instance. Empty profile for the Kernel dependency probe.'
core_version_requirement: ^10
hidden: true
"""


def inst_path(inst, key):
    v = json.load(open(f'{A}/instances/{inst}.json'))
    for k in key.split('.'):
        v = v[k]
    return v


def setup(inst):
    """Probe profile and probe test in the instance's site."""
    root = inst_path(inst, 'drupal.path')
    d = f'{root}/profiles/contrib/vactory'
    f = f'{d}/vactory.info.yml'
    if os.path.isdir(d) and (not os.path.exists(f) or open(f).read() != PROBE_PROFILE):
        sys.exit(f'{d} exists and is not the probe profile: not overwritten')
    os.makedirs(d, exist_ok=True)
    open(f, 'w').write(PROBE_PROFILE)
    os.makedirs(f'{root}/{WORK}/out', exist_ok=True)
    shutil.copy(f'{PHP}/DepProbeTest.php', f'{root}/{WORK}/')


def teardown(inst):
    """Removes the probe profile: the instance's site is used again for module runs."""
    shutil.rmtree(f"{inst_path(inst, 'drupal.path')}/profiles/contrib/vactory", ignore_errors=True)


def log(*a):
    with lock:
        print(time.strftime('%H:%M:%S'), *a, flush=True)


def run(inst, m, w, tag):
    """One Kernel probe; cached by (M, W, tag). Returns the JSON result (+ _file, _rc)."""
    key = hashlib.sha1(json.dumps([m, sorted(w), tag]).encode()).hexdigest()[:10]
    d = f'{RUNS}/{m}'
    os.makedirs(d, exist_ok=True)
    out = f'{d}/{tag}-{key}.json'
    if os.path.exists(out):
        return json.load(open(out))
    dd = f"{inst_path(inst, 'drupal.path')}/{WORK}"
    name = f'k-{m}-{key}.json'
    host_out = f'{dd}/out/{name}'
    if os.path.exists(host_out):
        os.remove(host_out)
    t0 = time.time()
    cmd = [STACK, 'exec', inst, 'env', 'COLUMNS=1000', f'DEP_MODULE={m}', f'DEP_WITH={",".join(w)}',
           f'DEP_OUT=/var/www/html/{WORK}/out/{name}', 'vendor/bin/phpunit', '-c', 'phpunit.stack.xml', f'{WORK}/DepProbeTest.php']
    try:
        p = subprocess.run(cmd, capture_output=True, text=True, timeout=1200, stdin=subprocess.DEVNULL)
        txt, rc = p.stdout + p.stderr, p.returncode
    except subprocess.TimeoutExpired as e:
        txt, rc = f'TIMEOUT 1200s\n{e.stdout or ""}', -9
    open(f'{d}/{tag}-{key}.phpunit.txt', 'w').write(txt)
    if os.path.exists(host_out):
        r = json.load(open(host_out))
        os.remove(host_out)
    else:
        # No probe output (timeout, failed `stack exec`): not a measurement. Not cached, so a rerun retries it.
        r = {'module': m, 'with': w, 'enabled': False, 'modules': [], 'no_output': True,
             'errors': [{'where': 'phpunit', 'error': 'no probe output (rc %s): %s' % (rc, txt.strip()[-1500:]), 'at': ''}]}
    r.update({'_file': os.path.relpath(out, A), '_rc': rc, '_inst': inst, '_wall': round(time.time() - t0, 1), '_tag': tag})
    if not r.get('no_output'):
        json.dump(r, open(out, 'w'), indent=1, ensure_ascii=False)
    return r


NO_OUTPUT = 'not measured: probe run without output'


def no_output(res, *runs):
    """Sets the verdict when a run gave no probe output; True if so (its errors prove nothing)."""
    bad = [r for r in runs if r.get('no_output')]
    if bad:
        res['verdict'] = f"{NO_OUTPUT} (rc {bad[0]['_rc']}, see {bad[0]['_file'][:-5]}.phpunit.txt); rerun redoes it"
    return bool(bad)


def norm(e):
    return re.sub(r'\b(test\d+|[0-9a-f]{8,}|\d+)\b', '#', e['where'] + ' | ' + e['error'])


def mentions(x):
    return re.compile(r'(Drupal\\\\?' + re.escape(x) + r'\\|(?<![\w\\.-])' + re.escape(x) + r'(?![\w-]))')


def owner_of(at):
    """Module (of the profile) owning the file `at` (longest module path contained in it)."""
    best = None
    for n, v in S.items():
        if '/' + v['path'] + '/' in at and (best is None or len(v['path']) > len(S[best]['path'])):
            best = n
    return best


def attributable(e, m):
    w, err, at = e['where'], e['error'], e.get('at', '')
    if w.startswith(('class Drupal\\' + m + '\\', 'service ', 'plugin ', 'config ', 'hook_cron', 'route ')):
        return True
    if re.search(r'provided by (<em[^>]*>)?' + re.escape(m) + r'(</em>)?\s', err):
        return True
    return owner_of(at) == m


def missing_modules(r, exclude):
    """Modules (existing in the code base, not enabled) reported missing by an UnmetDependencies error."""
    found = []
    for e in r.get('errors', []):
        if '| unmet:' not in e['error']:
            continue
        for item in re.findall(r'\(([^)]*)\)', e['error'].split('| unmet:', 1)[1]):
            for y in (s.strip() for s in item.split(',')):
                if y in ALL and y not in exclude and y not in r.get('modules', []) and y not in found:
                    found.append(y)
    return found


def unmet_has(r, m, x):
    """True if r has an UnmetDependencies error for config provided by m whose missing list contains module x."""
    for e in r.get('errors', []):
        if '| unmet:' in e['error'] and re.search(r'provided by (<em[^>]*>)?' + re.escape(m) + r'(</em>)?\s', e['error']):
            for item in re.findall(r'\(([^)]*)\)', e['error'].split('| unmet:', 1)[1]):
                if x in [y.strip() for y in item.split(',')]:
                    return True
    return False


def converge(inst, m, exclude, tag, w0=(), max_rounds=8):
    """Rounds: W grows with missing modules other than `exclude`. Returns (last run, W, chain of run files)."""
    w, chain = list(w0), []
    for _ in range(max_rounds):
        r = run(inst, m, w, tag)
        chain.append(r['_file'])
        add = missing_modules(r, set(exclude) | {m})
        if not add:
            break
        w = w + add
    return r, w, chain


def first(es):
    e = es[0]
    return f"{e['where']}: {e['error'][:600]} (at {e.get('at', '')})"


def undeclared(inst, x, m, kinds):
    out = f'{A}/deps/undeclared/{m}__{x}.json'
    evidence = (S.get(m, {}).get('undeclared_used', {}).get(x, {}) or {}).get('files', [])
    res = {'module': m, 'absent': x, 'kinds': kinds, 'method': 'kernel probe (php/DepProbeTest.php, kprobe.py)',
           'static_evidence': evidence}
    t0 = time.time()
    if x not in ALL:
        res['verdict'] = f'not measurable: {x} is not in the code base'
    elif kinds == 'config' and evidence and all('/config/optional/' in f for f in evidence):
        res['verdict'] = 'not measured: weak static evidence (only config/optional, which Drupal skips when the module is absent)'
    else:
        a, w, chain = converge(inst, m, {x}, 'u')
        res.update({'with_extra': w, 'run_without': a['_file'], 'rounds': chain, 'enabled_without': a.get('enabled')})
        res['exercised_without'] = {k: a.get(k) for k in ('classes', 'services', 'plugins', 'routes', 'steps')}
        rx = mentions(x)
        xe = [e for e in a['errors'] if rx.search(e['where'] + ' ' + e['error'])]
        mine = [e for e in xe if attributable(e, m) and ('| unmet:' not in e['error'] or unmet_has({'errors': [e]}, m, x))]
        if no_output(res, a):
            pass
        elif x in a.get('modules', []):
            res['verdict'] = f'not measurable: {x} is enabled anyway (pulled through ' + (', '.join(w) or "M's declared dependencies") + ')'
        elif mine:
            b = run(inst, m, w + [x], 'c')
            res['run_with'] = b['_file']
            if not no_output(res, b):
                bn = {norm(e) for e in b['errors']}
                # UnmetDependencies: compare the parsed missing lists (the message itself always changes with X present).
                proven = [e for e in mine if (not unmet_has(b, m, x)) if '| unmet:' in e['error']] + \
                         [e for e in mine if '| unmet:' not in e['error'] and norm(e) not in bn]
                res['errors_naming_absent'] = mine
                res['control_errors'] = b['errors'][:10]
                res['control_enabled'] = b.get('enabled')
                if proven:
                    res['proven_errors'] = proven
                    res['verdict'] = ('NEEDED: install fails without it: ' if not a.get('enabled') else 'NEEDED: errors naming it when absent: ') + first(proven)
                    if not b.get('enabled'):
                        res['verdict'] += f' [with {x} the missing item disappears, but M still does not install on a clean site: ' + (first(b['errors'])[:300] if b['errors'] else '?') + ']'
                else:
                    res['verdict'] = 'not proven: the same error happens with ' + x + ' installed'
        elif xe:
            ups = sorted({mm for e in xe for mm in re.findall(r'provided by (?:<em[^>]*>)?(\w+)', e['error'])} | {owner_of(e.get('at', '')) or '' for e in xe})
            ups = [u for u in ups if u and u != m]
            res['errors_naming_absent'] = xe
            res['verdict'] = f'not measurable: masked, a dependency of M ({", ".join(ups) or "?"}) already fails without {x}: ' + first(xe)
        elif not a.get('enabled'):
            res['verdict'] = 'not measurable: M does not install on a clean site: ' + (first(a['errors']) if a['errors'] else 'unknown')
        else:
            res['verdict'] = f'not proven: no error naming {x} when M runs without it (code path not reached by the probe)'
            res['other_errors'] = a['errors'][:10]
    res['time'] = round(time.time() - t0, 1)
    json.dump(res, open(out, 'w'), indent=1, ensure_ascii=False)
    log(inst, m, 'without', x, '->', res['verdict'][:160])


def unused(inst, m, dd):
    out = f'{A}/deps/unused/{m}__{dd}.json'
    res = {'module': m, 'absent': dd, 'method': 'kernel probe (php/DepProbeTest.php, kprobe.py), dependency removed from the instance copy of the .info.yml'}
    t0 = time.time()
    rel = f'{S[m]["path"]}/{m}.info.yml'
    prof = inst_path(inst, 'profile.path')
    info = f'{prof}/{rel}'
    orig = open(info).read()
    # list item (last line may have no trailing newline) or scalar `dependencies: D`
    s2 = re.sub(r'(?m)^[ \t]*-[ \t]*[\'"]?([a-z0-9_]+:)?' + re.escape(dd) + r'\b[^\n]*(\n|\Z)', '', orig)
    s2 = re.sub(r'(?m)^dependencies:[ \t]*[\'"]?([a-z0-9_]+:)?' + re.escape(dd) + r'\b[^\n]*$', 'dependencies: []', s2)
    if s2 == orig:
        res['verdict'] = f'not measurable: {dd} not found in {m}.info.yml'
    else:
        def without(w0):
            try:
                open(info, 'w').write(s2)
                return converge(inst, m, {dd}, f'n-{dd}', w0)
            finally:
                open(info, 'w').write(orig)
                subprocess.run(['git', '-C', prof, 'checkout', '--', rel], capture_output=True)
        a, wa, chain = without(())
        b, wb, chainb = converge(inst, m, set(), 'base')  # control: original info.yml
        w = sorted((set(wa) | set(wb)) - {dd})
        if set(w) != set(wa):
            a, wa, more = without(w)
            chain += more
        if set(w) != set(wb):
            b, wb, more = converge(inst, m, set(), 'base', w)
            chainb += more
        res.update({'with_extra': wa, 'with_extra_control': wb, 'run_without': a['_file'], 'run_with': b['_file'],
                    'rounds': chain, 'rounds_control': chainb, 'enabled_without': a.get('enabled'), 'enabled_with': b.get('enabled'),
                    'info_diff': f'- {dd}'})
        bn = {norm(e) for e in b['errors']}
        new = [e for e in a['errors'] if norm(e) not in bn]
        rx = mentions(dd)
        named = [e for e in new if rx.search(e['where'] + ' ' + e['error'])]
        res['new_errors'] = new[:10]
        res['exercised_without'] = {k: a.get(k) for k in ('classes', 'services', 'plugins', 'routes', 'steps')}
        if no_output(res, a, b):
            pass
        elif dd in a.get('modules', []):
            res['verdict'] = f'not measurable: {dd} is enabled anyway without the declaration (pulled through ' + (', '.join(wa) or 'another declared dependency') + ')'
        elif not b.get('enabled'):
            res['verdict'] = 'not measurable: M does not install on a clean site even with the declaration: ' + (first(b['errors']) if b['errors'] else '?')
        elif not a.get('enabled'):
            res['verdict'] = 'NEEDED: install fails without it: ' + first(new or a['errors'])
        elif named:
            res['verdict'] = 'NEEDED: errors naming it when absent: ' + first(named)
        elif new:
            res['verdict'] = 'BREAKS without it: ' + first(new)
        else:
            res['verdict'] = 'no error observed without it'
    res['time'] = round(time.time() - t0, 1)
    json.dump(res, open(out, 'w'), indent=1, ensure_ascii=False)
    log(inst, m, 'without declared', dd, '->', res['verdict'][:160])


def judged(path):
    """A pair is done when it has a result, except a result from a run without probe output."""
    return os.path.exists(path) and not json.load(open(path)).get('verdict', '').startswith(NO_OUTPUT)


def main():
    mode, lst, insts = sys.argv[2], sys.argv[3], sys.argv[4:]
    if mode not in ('undeclared', 'unused') or not insts:
        sys.exit(__doc__)
    for inst in insts:
        setup(inst)
    os.makedirs(f'{A}/deps/{mode}', exist_ok=True)
    groups = {}
    for line in open(lst):
        f = line.rstrip('\n').split('\t')
        if len(f) < 2 or not f[0]:
            continue
        if mode == 'undeclared':
            x, m, k = f[0], f[1], (f[2] if len(f) > 2 else '')
            if not FORCE and judged(f'{A}/deps/undeclared/{m}__{x}.json'):
                continue
            groups.setdefault(m, []).append((x, m, k))
        else:
            m, dd = f[0], f[1]
            if not FORCE and judged(f'{A}/deps/unused/{m}__{dd}.json'):
                continue
            groups.setdefault(m, []).append((m, dd))
    q = queue.Queue()
    for m in sorted(groups, key=lambda k: -len(groups[k])):
        q.put(groups[m])
    log(mode, sum(len(v) for v in groups.values()), 'pairs,', len(groups), 'modules, on', insts)

    failed = []

    def worker(inst):
        while True:
            try:
                g = q.get_nowait()
            except queue.Empty:
                return
            for item in g:
                try:
                    (undeclared if mode == 'undeclared' else unused)(inst, *item)
                except Exception as e:  # keep going; the pair stays without result -> rerun picks it up
                    log(inst, 'ERROR', item, repr(e))
                    failed.append(item)
    ts = [threading.Thread(target=worker, args=(i,)) for i in insts]
    for t in ts:
        t.start()
    for t in ts:
        t.join()
    for inst in insts:
        teardown(inst)
    log(mode, lst, 'done')
    if failed:
        sys.exit(f'{len(failed)} pair(s) without result after an error, see the log above')


if __name__ == '__main__':
    main()
