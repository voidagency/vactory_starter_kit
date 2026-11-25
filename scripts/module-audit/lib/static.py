#!/usr/bin/env python3
"""module-audit, static pass: module inventory and dependency candidates (grep).

  static.py inventory <profile_dir> <drupal_root> <out.json>
  static.py pairs <static.json> <profile_dir> <base-modules.txt> <out_dir>

Static findings are CANDIDATES only: kprobe.py measures them. `pairs` writes the
candidate lists measured by kprobe.py (pairs-undeclared.tsv, pairs-unused.tsv)
and the unused candidates left aside (unused-false-positives.json).
"""
import json, os, re, subprocess, sys
import yaml  # PyYAML

# Core modules every site has: an undeclared use of them is not measured.
ALWAYS_PRESENT = {'system', 'user', 'field', 'filter', 'text', 'node', 'path_alias', 'file', 'image'}


def pairs(static_json, profile, base_file, out_dir):
    """Candidate pairs. Undeclared: M uses X (namespace or config dependency), X not
    in M's dependency closure, X not always present. Unused: D declared by M, never
    named in M's files, not enabled by the profile, not pulled anyway by another
    declared dependency of M (profile modules); default_content is used implicitly
    by a module with a content/ dir."""
    s = json.load(open(static_json))['modules']
    base = set(open(base_file).read().split())
    und = []
    for m, v in sorted(s.items()):
        for x, i in sorted(v['undeclared_used'].items()):
            if not i['in_closure'] and x not in ALWAYS_PRESENT:
                und.append((x, m, ','.join(i['kinds'])))
    un, fp = [], []
    for m, v in sorted(s.items()):
        for d in v['unused_declared_candidates']:
            if d in base:
                continue
            others = [o for o in v['declared'] if o != d]
            if any(d in s.get(o, {}).get('closure', []) for o in others):
                continue
            if d == 'default_content' and os.path.isdir(os.path.join(profile, v['path'], 'content')):
                fp.append((m, d, 'has content/ dir: default_content imports it implicitly'))
                continue
            un.append((m, d))
    open(f'{out_dir}/pairs-undeclared.tsv', 'w').write(''.join(f'{x}\t{m}\t{k}\n' for x, m, k in sorted(und)))
    open(f'{out_dir}/pairs-unused.tsv', 'w').write(''.join(f'{m}\t{d}\n' for m, d in un))
    json.dump(fp, open(f'{out_dir}/unused-false-positives.json', 'w'), indent=1)
    print('pairs: undeclared', len(und), 'unused', len(un), 'left aside', len(fp))


if sys.argv[1] == 'pairs':
    pairs(*sys.argv[2:6])
    sys.exit(0)

profile, root, out = sys.argv[2:5]

def info_files(base):
    for dp, dn, fn in os.walk(base):
        dn[:] = [d for d in dn if d not in ('node_modules', 'vendor', '.git')]
        for f in fn:
            if f.endswith('.info.yml'):
                yield os.path.join(dp, f)

def load(p):
    try:
        with open(p) as fh:
            return yaml.safe_load(fh) or {}
    except Exception as e:
        return {'__error': str(e)}

def as_list(v):
    if v is None:
        return []
    return [v] if isinstance(v, str) else list(v)

def dep_name(d):
    d = d.split('(')[0].strip()
    return d.split(':')[-1].strip()

# All modules known to the code base (core, contrib, profile).
universe = {}
for base in (os.path.join(root, 'core', 'modules'), os.path.join(root, 'modules'), profile):
    for p in info_files(base):
        i = load(p)
        if i.get('type') != 'module':
            continue
        name = os.path.basename(p)[:-9]
        universe.setdefault(name, {'path': os.path.dirname(p), 'deps': [dep_name(d) for d in as_list(i.get('dependencies'))]})

profile_info = load(os.path.join(profile, 'vactory_starter_kit.info.yml'))
profile_install = set(dep_name(d) for d in (profile_info.get('install') or []))
profile_deps = set(dep_name(d) for d in (profile_info.get('dependencies') or []))

def closure(m, seen=None):
    seen = set() if seen is None else seen
    for d in universe.get(m, {}).get('deps', []):
        if d not in seen:
            seen.add(d)
            closure(d, seen)
    return seen

mods, excluded = {}, []
for p in sorted(info_files(os.path.join(profile, 'modules'))):
    rel = os.path.relpath(p, profile)
    i = load(p)
    name = os.path.basename(p)[:-9]
    if i.get('type') != 'module':
        excluded.append({'module': name, 'path': rel, 'why': 'type=%s' % i.get('type')})
        continue
    if re.search(r'/tests?/', rel) or str(i.get('package', '')).lower() == 'testing':
        excluded.append({'module': name, 'path': rel, 'why': 'test module (tests/ dir or package Testing)'})
        continue
    mods[name] = {'path': os.path.dirname(rel), 'info': i}

# Map nested sub-module dirs so a module's own files exclude them.
all_dirs = {n: os.path.join(profile, m['path']) for n, m in mods.items()}

def own_files(n):
    d = all_dirs[n]
    others = [v for k, v in all_dirs.items() if k != n and v.startswith(d + os.sep)]
    for dp, dn, fn in os.walk(d):
        if any(dp == o or dp.startswith(o + os.sep) for o in others):
            continue
        dn[:] = [x for x in dn if x not in ('node_modules', '.git')]
        for f in fn:
            yield os.path.join(dp, f)

ns_re = re.compile(r'\\?Drupal\\([a-z0-9_]+)\\')
res = {}
for n, m in mods.items():
    declared = [dep_name(d) for d in as_list(m['info'].get('dependencies'))]
    clos = closure(n)
    ns_used, cfg_deps, text = {}, {}, {}
    for f in own_files(n):
        relf = os.path.relpath(f, profile)
        if f.endswith(('.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.ttf', '.ico', '.pdf', '.zip', '.webp', '.mp4')):
            continue
        try:
            s = open(f, encoding='utf-8', errors='ignore').read()
        except Exception:
            continue
        if f.endswith(('.php', '.module', '.install', '.inc', '.theme', '.profile')):
            for mm in ns_re.finditer(s):
                x = mm.group(1)
                if x in ('Core', 'Component') or x == n:
                    continue
                ns_used.setdefault(x, set()).add(relf)
        if '/config/install/' in f or '/config/optional/' in f:
            try:
                y = yaml.safe_load(s) or {}
                for x in ((y.get('dependencies') or {}).get('module') or []):
                    cfg_deps.setdefault(x, set()).add(relf + ('' if '/config/install/' in f else ' (optional)'))
                for x in ((y.get('dependencies') or {}).get('enforced') or {}).get('module') or []:
                    cfg_deps.setdefault(x, set()).add(relf + ' (enforced)')
            except Exception:
                pass
        text[relf] = s
    # declared deps never mentioned anywhere outside the .info.yml
    unused = {}
    for d in declared:
        hits = [f for f, s in text.items() if not f.endswith('.info.yml') and re.search(r'(?<![A-Za-z0-9_])' + re.escape(d) + r'(?![A-Za-z0-9_])', s)]
        if not hits:
            unused[d] = 'no occurrence of the word %r in any file of the module except its .info.yml' % d
    undeclared = {}
    for x, files in list(ns_used.items()) + [(k, v) for k, v in cfg_deps.items()]:
        if x not in universe or x == n or x in ('core', 'system'):
            continue
        kind = 'namespace' if x in ns_used and files is ns_used[x] else 'config'
        if x in declared:
            continue
        undeclared.setdefault(x, {'kinds': set(), 'files': set(), 'in_closure': x in clos})
        undeclared[x]['kinds'].add(kind)
        undeclared[x]['files'].update(sorted(files)[:6])
    res[n] = {
        'path': m['path'],
        'enabled_by_profile': n in profile_install or n in profile_deps,
        'profile_dependency': n in profile_deps,
        'declared': declared,
        'closure': sorted(clos),
        'closure_size': len(clos),
        'closure_not_in_profile_install': sorted(c for c in clos if c not in profile_install and c not in profile_deps),
        'unused_declared_candidates': unused,
        'undeclared_used': {k: {'kinds': sorted(v['kinds']), 'files': sorted(v['files'])[:6], 'in_closure': v['in_closure']} for k, v in undeclared.items()},
        'has_hook_uninstall': any(re.search(r'function\s+' + n + r'_uninstall\s*\(', s) for f, s in text.items() if f.endswith('.install')),
        'has_hook_install': any(re.search(r'function\s+' + n + r'_install\s*\(', s) for f, s in text.items() if f.endswith('.install')),
    }

# circular / crossed deps among profile modules
cycles = []
for n in res:
    if n in closure(n):
        cycles.append(n)

json.dump({'_commit': subprocess.run(['git', '-C', profile, 'rev-parse', 'HEAD'], capture_output=True, text=True).stdout.strip(),
           'modules': res, 'excluded': excluded, 'profile_install': sorted(profile_install), 'profile_dependencies': sorted(profile_deps),
           'cycles': cycles, 'missing_in_codebase': sorted({d for r in res.values() for d in r['declared'] if d not in universe})},
          open(out, 'w'), indent=1, sort_keys=True)
print('modules', len(res), 'excluded', len(excluded), 'enabled_by_profile', sum(r['enabled_by_profile'] for r in res.values()))
