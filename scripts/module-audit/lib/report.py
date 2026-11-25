#!/usr/bin/env python3
"""module-audit: builds RESULT.md from raw/*/result.json, deps/*/*.json and static.json. Usage: report.py <audit_dir>"""
import glob, json, os, re, sys
from collections import defaultdict

A = sys.argv[1]
S = json.load(open(f'{A}/static.json'))
# Target: the modules of this audit (modules.txt) and those with a dependency result (deps --only);
# all inventoried modules when modules.txt is missing.
target = set(open(f'{A}/modules.txt').read().split()) if os.path.exists(f'{A}/modules.txt') else set(S['modules'])
target |= {os.path.basename(p).split('__')[0] for p in glob.glob(f'{A}/deps/*/*__*.json')}
mods = {m: v for m, v in S['modules'].items() if m in target}
res = {}
for p in glob.glob(f'{A}/raw/*/result.json'):
    try:
        r = json.load(open(p))
    except ValueError:  # truncated by a killed run: shown as not measured, resume measures it again
        print(f'unreadable, ignored: {p}', file=sys.stderr)
        continue
    if r['module'] in mods:
        res[r['module']] = r


def load_deps(kind):
    out = defaultdict(list)
    for p in sorted(glob.glob(f'{A}/deps/{kind}/*__*.json')):
        try:
            r = json.load(open(p))
        except ValueError:
            print(f'unreadable, ignored: {p}', file=sys.stderr)
            continue
        if r['module'] in mods:
            r['_file'] = os.path.relpath(p, A)
            out[r['module']].append(r)
    return out


undec = load_deps('undeclared')
unused = load_deps('unused')
unused_fp = [x for x in json.load(open(f'{A}/unused-false-positives.json')) if x[0] in mods] if os.path.exists(f'{A}/unused-false-positives.json') else []
PROFILE_URL = 'https://github.com/voidagency/vactory_starter_kit'
inst_files = sorted(glob.glob(f'{A}/instances/*.json'))
baseline = json.load(open(inst_files[0])).get('baseline', '?') if inst_files else '?'


def link(text, path, anchor=''):
    return f'[{text}]({path}{anchor})'


def esc(s):
    return str(s).replace('|', '\\|').replace('\n', ' ')


def first_err(lines, tail='', msg=''):
    if msg:
        return esc(msg[:300])
    for l in lines or []:
        if re.search(r'error|exception|not exist|unmet|required|refused|cannot|unable|used in', l, re.I):
            return esc(l[:180])
    return esc((tail or '').splitlines()[-1][:180]) if tail else ''


def h_cell(h, ev):
    if not h:
        return '—'
    bad = [f'{x["path"]}={x["code"]}' for x in h['http_bad']]
    parts = []
    if h['cr_rc'] != 0:
        parts.append('cr FAIL')
    if h['cron_rc'] != 0:
        parts.append('cron FAIL')
    parts += bad
    if h['watchdog_errors']:
        parts.append(f'{len(h["watchdog_errors"])} new watchdog errors')
    if not parts:
        return link('ok', ev)
    return link(('**BROKEN** ' if h['broken'] else '') + esc('; '.join(parts)[:220]), ev)


def count_ents(c):
    return sum(len(v) for v in (c or {}).values())


groups = defaultdict(list)


def info_url(m):
    """The module's .info.yml in the profile at the audited commit (it is not in the results directory)."""
    rel = f'{mods[m]["path"]}/{m}.info.yml'
    commit = S.get('_commit')
    return f'{PROFILE_URL}/blob/{commit}/{rel}' if commit else rel


def dep_cells(m):
    """Dependency cell of module m (measured verdicts); fills the groups."""
    st = mods[m]
    db = []
    for d in undec.get(m, []):
        v = d.get('verdict') or d.get('not_measurable', '')
        if v.startswith(('NEEDED', 'BREAKS')):
            db.append(link(f'uses undeclared **{d["absent"]}**: {v.split(":")[0]}', d['_file']))
            groups['Undeclared dependency, proven (fails without it)'].append((m, f'{d["absent"]} ({d.get("kinds")}): {v}', d['_file']))
    for d in unused.get(m, []):
        d['verdict'] = d.get('verdict') or d.get('not_measurable', '')
        if d['verdict'] == 'no error observed without it':
            db.append(link(f'declares unused **{d["absent"]}**', d['_file']))
            groups['Declared dependency not needed (no error without it)'].append((m, d['absent'], d['_file']))
        elif d['verdict'].startswith(('NEEDED', 'BREAKS')):
            db.append(link(f'{d["absent"]} needed', d['_file']))
    if m in S.get('cycles', []):
        selfdep = m in st['declared']
        db.append('**self-dependency**' if selfdep else '**circular**')
        groups['Self or circular dependency (info.yml)'].append((m, 'declares itself' if selfdep else 'circular via ' + ', '.join(x for x in st['declared'] if m in mods.get(x, {}).get('closure', [])), info_url(m)))
    return db


rows = []
times = []
for m in sorted(mods):
    r = res.get(m)
    ev = f'raw/{m}/summary.md'
    if not r:
        rows.append(f'| {m} | — | not measured | | | | | | | {"<br>".join(dep_cells(m))} | |')
        groups['Not measured'].append(m)
        continue
    f = r['flags']
    if f.get('measurement_invalid'):
        # Site unreachable during the run (ddev router cut): no verdict, the run is redone by resume.
        rows.append(f'| {m} | — | {link("measurement invalid (site unreachable): module-audit resume redoes it", ev)} | | | | | | | {"<br>".join(dep_cells(m))} | |')
        groups['Measurement invalid (site unreachable, redo with resume)'].append((m, 'every page answered 000', ev))
        continue
    times.append((m, r['times'].get('total', 0)))
    mode = 'profile' if r['mode'] == 'enabled' else 'new'
    # 1 install
    if r['mode'] == 'new':
        i = r['install']
        pulled = i.get('modules_pulled', [])
        if i['ok']:
            c1 = link(f'ok, pulls {len(pulled)}', ev, '#1-install-alone-drush-en-' + m + '--y') + f' ({r["times"]["10-install"]} s)'
        else:
            c1 = link('**FAIL**: ' + first_err(i.get('output_errors'), i.get('output_tail'), i.get('message')), ev)
            groups['Install alone fails'].append((m, first_err(i.get('output_errors'), i.get('output_tail'), i.get('message')), ev))
        if len(pulled) >= 15:
            groups['Heavy chain (install pulls ≥ 15 modules)'].append((m, f'{len(pulled)} modules: ' + ', '.join(pulled[:25]) + (' …' if len(pulled) > 25 else ''), ev))
        ca = r.get('content_added', {})
        ea = ca.get('entities_added', {})
        bits = [f'{k} {len(v)}' for k, v in ea.items() if k not in ('content_moderation_state', 'path_alias', 'crop')]
        if ca.get('menu_link_content_added'):
            bits.append(f'menu links {len(ca["menu_link_content_added"])}')
        if ca.get('block_placements_added'):
            bits.append(f'block placements {len(ca["block_placements_added"])}')
        if ca.get('front_page'):
            bits.append('front page changed')
        if ca.get('revisions', {}).get('node'):
            a, b = ca['revisions']['node']
            ex = sum(1 for _ in ea.get('node', {}))
            if b - a > ex:
                bits.append(f'node revisions +{b - a}')
        c2 = link(esc(', '.join(bits)) or 'none', ev, '#2-added-by-install-not-config')
        if f.get('content_added'):
            groups['Install creates content (not config)'].append((m, ', '.join(bits), ev))
        c3 = h_cell(r.get('health_after_install'), ev)
        if f.get('breaks_after_install'):
            groups['Site broken after install'].append((m, c3, ev))
    else:
        c1, c2, c3 = 'enabled by profile', '—', '—'
    # 4 uninstall
    u = r['uninstall']
    if u['ok']:
        c4 = link('ok' + (f', cascade {len(u["cascade"])}' if u.get('cascade') else ''), ev, '#4-uninstall-drush-pmu-' + m + '--y') + f' ({r["times"]["20-uninstall"]} s)'
        if u.get('cascade'):
            groups['Uninstall cascades to other modules'].append((m, f'{len(u["cascade"])}: ' + ', '.join(u['cascade'][:20]) + (' …' if len(u['cascade']) > 20 else ''), ev))
    else:
        msg = first_err(u.get('output_errors'), u.get('output_tail'), u.get('message'))
        c4 = link('**REFUSED**: ' + msg, ev)
        groups['Uninstall refused'].append((m, msg, ev))
    c5 = h_cell(r.get('health_after_uninstall'), ev)
    if f.get('breaks_after_uninstall'):
        groups['Site broken after uninstall'].append((m, c5, ev))
    if f.get('new_log_errors'):
        groups['New log errors (site still works)'].append((m, '; '.join(c for c in (c3, c5) if 'watchdog' in c), ev))
    # 5 leftovers
    lo = {k: v for k, v in r.get('leftovers', {}).items() if not k.startswith('_')}
    lb = []
    if 'content' in lo:
        c = lo['content']
        e = {k: len(v) for k, v in c.get('entities_added', {}).items() if k not in ('content_moderation_state',)}
        if e:
            lb.append('content ' + ', '.join(f'{k} {v}' for k, v in e.items()))
        if c.get('revisions'):
            lb.append('revisions ' + ', '.join(f'{k} +{b - a}' for k, (a, b) in c['revisions'].items() if b > a))
        if c.get('state'):
            lb.append(f'state {len(c["state"].get("added", []))}')
    if lo.get('menu_links_added'):
        lb.append(f'menu links {len(lo["menu_links_added"])}')
    for k, lab in (('tables_added', 'tables'), ('config_added', 'config'), ('config_changed', 'config changed'), ('files_added', 'files'),
                   ('tables_named_after_module', 'tables'), ('config_named_after_module', 'config'), ('modules_added', 'modules')):
        if lo.get(k):
            lb.append(f'{lab} {len(lo[k])}')
    for k in ('key_value', 'key_value_named_after_module'):
        if lo.get(k):
            lb.append('key_value ' + ', '.join(list(lo[k])[:3]))
    if lo.get('role_permissions'):
        lb.append('role permissions')
    c6 = link(esc('; '.join(lb)[:260]) or 'none', ev, '#5-leftovers-after-uninstall')
    if f.get('leftover_content'):
        groups['Content left after uninstall'].append((m, '; '.join(lb), ev))
    elif f.get('leftovers') and u['ok']:
        groups['Other leftovers after uninstall (tables/config/state/key_value/permissions)'].append((m, '; '.join(lb), ev))
    # 6 reinstall
    re_ = r['reinstall']
    if re_['ok']:
        dup = re_.get('duplicates', {})
        c7 = link('ok' + (', **duplicates** ' + ', '.join(f'{k} {len(v)}' for k, v in dup.items()) if dup else ''), ev, '#6-reinstall')
        if dup:
            groups['Reinstall creates duplicates'].append((m, ', '.join(f'{k} {len(v)}: ' + ', '.join(list(v.values())[:3]) for k, v in dup.items()), ev))
    else:
        msg = first_err(re_.get('output_errors'), re_.get('output_tail'), re_.get('message'))
        c7 = link('**FAIL**: ' + msg, ev)
        if u['ok']:
            groups['Reinstall fails after uninstall'].append((m, msg, ev))
    db = dep_cells(m)
    c8 = '<br>'.join(db) or 'ok'
    t = r['times'].get('total', 0)
    rows.append(f'| {m} | {mode} | {c1} | {c2} | {c3} | {c4} | {c5} | {c6} | {c7} | {c8} | {t} |')
    flagged = any(v for k, v in f.items()) or any(x.startswith(('<a', '[uses', '[declares', '**')) for x in db)
    if not flagged:
        groups['Clean'].append(m)

L = ['# Module install / uninstall audit of `vactory_starter_kit`', '']
L += [f'Measured on `stack` instances (profile commit {S.get("_commit", "?")}, baseline DB of {baseline}). '
      'Every row comes from a run of `scripts/module-audit/module-audit` (fresh restore of the `audit-base` snapshot before each module); dependency cells come from `kprobe.py` runs (Kernel probe `php/DepProbeTest.php` in the instance: M installed on a fresh Kernel site without X, then a control run with X; only errors that name X, come from M and disappear with X count). '
      'Static grep (`static.json`) only chose what to measure.', '']
L += [f'Modules: {len(mods)} measured target, {len(res) - len(groups.get("Measurement invalid (site unreachable, redo with resume)", []))} measured, excluded: ' + ', '.join(f'`{e["module"]}` ({e["why"]})' for e in S['excluded']), '']
if times:
    tot = sum(t for _, t in times)
    L += [f'Time per module: mean {tot / len(times):.0f} s, min {min(t for _, t in times):.0f} s, max {max(t for _, t in times):.0f} s ({max(times, key=lambda x: x[1])[0]}); sum {tot / 60:.0f} min.', '']
L += ['Legend: *new* = not enabled by the profile install (install alone measured); *profile* = enabled by the profile (uninstall + reinstall only, '
      'reinstall also re-enables the modules the uninstall cascaded). Health = `drush cr`, `drush cron`, pages (home, login, decoupled router + JSON:API of the home node, 6 admin pages, the module\'s new parameterless routes), new watchdog errors. '
      'A page is *bad* when it answers 5xx/000 or another code than in the control run.', '']
L += ['| Module | Mode | Install alone | Content added | Health after install | Uninstall | Health after uninstall | Leftovers | Reinstall | Dependencies (measured) | Time (s) |',
      '|---|---|---|---|---|---|---|---|---|---|---|'] + rows
L += ['', '## Modules that need a fix, by kind of defect', '']
order = ['Measurement invalid (site unreachable, redo with resume)', 'Install alone fails', 'Site broken after install', 'Uninstall refused', 'Site broken after uninstall', 'New log errors (site still works)', 'Content left after uninstall', 'Reinstall fails after uninstall',
         'Reinstall creates duplicates', 'Install creates content (not config)', 'Uninstall cascades to other modules', 'Other leftovers after uninstall (tables/config/state/key_value/permissions)',
         'Undeclared dependency, proven (fails without it)', 'Declared dependency not needed (no error without it)', 'Heavy chain (install pulls ≥ 15 modules)', 'Self or circular dependency (info.yml)']
for g in order:
    items = groups.get(g, [])
    L += [f'### {g} ({len(items)})', '']
    for m, msg, ev in items:
        L.append(f'- `{m}` — {esc(msg)[:400]} — {link("evidence", ev)}')
    L.append('')
def _cat(v):
    v = v or 'no result'
    if v.startswith(('NEEDED', 'BREAKS', 'no error observed')):
        return None
    for key, label in (('masked', 'not measurable: masked (a dependency of M already fails without X)'),
                       ('enabled anyway', 'not measurable: X pulled in transitively'),
                       ('even with the declaration', 'not measurable: M does not install on a clean Kernel site even with its declaration'),
                       ('does not install on a clean site', 'not measurable: M does not install on a clean Kernel site (other missing config/module)'),
                       ('not in the code base', 'not measurable: X not in the code base'),
                       ('same error happens', 'not proven: same error with X installed'),
                       ('code path not reached', 'not proven: no error naming X (code path not reached by the probe)'),
                       ('weak static evidence', 'not measured: weak static evidence (config/optional only)')):
        if key in v:
            return label
    return re.sub(r':.*$', '', v)
nm = defaultdict(list)
for kind, dd in (('undeclared', undec), ('unused', unused)):
    for m, lst in dd.items():
        for d in lst:
            c = _cat(d.get('verdict') or d.get('not_measurable', ''))
            if c:
                nm[f'{kind}: {c}'].append((m, d['absent'], d.get('verdict') or d.get('not_measurable', ''), d['_file']))
n_und = sum(1 for l in undec.values() for d in l if (d.get('verdict') or '').startswith(('NEEDED', 'BREAKS')))
n_unu = sum(1 for l in unused.values() for d in l if d.get('verdict') == 'no error observed without it')
L += [f'### Dependency candidates: {sum(map(len, undec.values()))} undeclared measured-or-classified, {n_und} proven; {sum(map(len, unused.values()))} declared-unused, {n_unu} proven not needed; not proven / not measured below', '']
for c in sorted(nm):
    L += [f'#### {c} ({len(nm[c])})', '']
    for m, x, v, f in sorted(nm[c]):
        L.append(f'- `{m}` / `{x}` — {esc(v)[:300]} — {link("evidence", f)}')
    L.append('')
if unused_fp:
    L += ['### Static false positives (not measured)', ''] + [f'- `{m}` declares `{d}`: {why}' for m, d, why in unused_fp] + ['']
L += [f'## Clean ({len(groups.get("Clean", []))})', '', ', '.join(f'`{m}`' for m in groups.get('Clean', [])), '']
if groups.get('Not measured'):
    L += ['## Not measured', '', ', '.join(groups['Not measured']), '']
open(f'{A}/RESULT.md', 'w').write('\n'.join(L) + '\n')
print('RESULT.md', len(res), 'measured;', {g: len(groups.get(g, [])) for g in order + ['Clean', 'Not measured']})
