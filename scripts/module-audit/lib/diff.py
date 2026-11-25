#!/usr/bin/env python3
"""module-audit: snapshot diffs and per-module result (stdlib only).

  diff.py routes  <s0> <s1>                 new parameterless routes (paths to check)
  diff.py cascade <s1> <s2> <module>        modules removed by the uninstall besides <module>
  diff.py result  <raw/module dir> <module> <control dir>   -> result.json + summary.md
"""
import json, os, re, sys

CONTENT_TYPES_SKIP = {'user'}  # users listed separately anyway


def load(p):
    try:
        s = json.load(open(p))
    except Exception:
        return None
    # PHP json_encode writes an empty array as [] : normalise maps.
    for k in ('config', 'config_collections', 'role_permissions', 'key_value', 'key_value_expire', 'queue', 'entities', 'revisions', 'menu_tree', 'routes'):
        if isinstance(s.get(k), list):
            s[k] = {}
    for t, v in list(s.get('entities', {}).items()):
        if isinstance(v, list):
            s['entities'][t] = {}
    return s


def rd(p, default=''):
    try:
        return open(p).read().strip()
    except Exception:
        return default


def setdiff(a, b):
    a, b = set(a or []), set(b or [])
    return sorted(b - a), sorted(a - b)


def snapdiff(a, b, noise=None):
    """What changed from snapshot a to snapshot b; noise = diff of the control run (ignored)."""
    noise = noise or {}
    n = lambda *k: set(_get(noise, *k) or [])
    d = {}
    d['modules_added'], d['modules_removed'] = setdiff(a['modules'], b['modules'])
    d['tables_added'], d['tables_removed'] = setdiff(a['tables'], b['tables'])
    # Tables Drupal creates lazily on first use (not owned by the module under test).
    lazy = re.compile(r'^(sessions|semaphore|flood|batch|queue|cache_[a-z0-9_]+|cachetags|key_value_expire)$')
    d['tables_added'] = [t for t in d['tables_added'] if t not in n('tables_added') and not lazy.match(t)]
    ca, cb = a['config'], b['config']
    d['config_added'] = sorted(set(cb) - set(ca) - n('config_added'))
    d['config_removed'] = sorted(set(ca) - set(cb) - n('config_removed'))
    d['config_changed'] = sorted(k for k in set(ca) & set(cb) if ca[k] != cb[k] and k not in n('config_changed'))
    perms = {}
    for role in sorted(set(a['role_permissions']) | set(b['role_permissions'])):
        ad, rm = setdiff(a['role_permissions'].get(role), b['role_permissions'].get(role))
        if ad or rm:
            perms[role] = {'added': ad, 'removed': rm}
    d['role_permissions'] = perms
    kv = {}
    for c in sorted(set(a['key_value']) | set(b['key_value'])):
        ad, rm = setdiff(a['key_value'].get(c), b['key_value'].get(c))
        nz = _get(noise, 'key_value', c) or {}
        ad = [x for x in ad if x not in set(nz.get('added', []))]
        rm = [x for x in rm if x not in set(nz.get('removed', []))]
        if ad or rm:
            kv[c] = {'added': ad, 'removed': rm}
    d['key_value'] = kv
    d['queue'] = {q: (a['queue'].get(q, 0), b['queue'].get(q, 0)) for q in set(a['queue']) | set(b['queue'])
                  if a['queue'].get(q, 0) != b['queue'].get(q, 0) and q not in n('queue')}
    ents = {}
    for t in sorted(set(a['entities']) | set(b['entities'])):
        ea, eb = a['entities'].get(t) or {}, b['entities'].get(t) or {}
        if '__error' in ea or '__error' in eb:
            if ea.get('__error') != eb.get('__error'):
                ents[t] = {'error_before': ea.get('__error'), 'error_after': eb.get('__error')}
            continue
        ad = {k: eb[k] for k in eb if k not in ea}
        rm = {k: ea[k] for k in ea if k not in eb}
        nz = _get(noise, 'entities', t) or {}
        for k in list(ad):
            if k in (nz.get('added') or {}):
                del ad[k]
        if ad or rm:
            ents[t] = {'added': ad, 'removed': rm, 'count_before': len(ea), 'count_after': len(eb)}
    d['entities'] = ents
    d['revisions'] = {t: (a['revisions'].get(t, 0), b['revisions'].get(t, 0)) for t in set(a['revisions']) | set(b['revisions'])
                      if a['revisions'].get(t, 0) != b['revisions'].get(t, 0) and t not in n('revisions')}
    ma, mb = a['menu_tree'], b['menu_tree']
    d['menu_links_added'] = {k: mb[k] for k in mb if k not in ma}
    d['menu_links_removed'] = {k: ma[k] for k in ma if k not in mb}
    ra, rb = a['routes'], b['routes']
    d['routes_added'] = {k: rb[k] for k in rb if k not in ra and k not in n('routes_added')}
    d['routes_removed'] = {k: ra[k] for k in ra if k not in rb and k not in n('routes_removed')}
    d['cron_hooks_added'], d['cron_hooks_removed'] = setdiff(a['cron_hooks'], b['cron_hooks'])
    fa, fb = setdiff(a['public_files'], b['public_files'])
    d['files_added'] = [f for f in fa if f not in n('files_added')]
    d['files_removed'] = [f for f in fb if f not in n('files_removed')]
    if a.get('front_page') != b.get('front_page'):
        d['front_page'] = [a.get('front_page'), b.get('front_page')]
    return d


def _get(d, *keys):
    for k in keys:
        if not isinstance(d, dict) or k not in d:
            return None
        d = d[k]
    return d


def noise_of(control):
    s0, s1 = load(os.path.join(control, 's0.json')), load(os.path.join(control, 's1.json'))
    if not s0 or not s1:
        return {}
    nz = snapdiff(s0, s1)
    # Always-volatile entries (cron, caches, css/js aggregation, flood, sessions).
    nz['queue'] = list(nz.get('queue', {}).keys())
    nz['revisions'] = list(nz.get('revisions', {}).keys())
    nz['routes_added'] = list(nz.get('routes_added', {}).keys())
    nz['routes_removed'] = list(nz.get('routes_removed', {}).keys())
    return nz


VOLATILE_STATE = re.compile(r'^(system\.cron_last|system\.css_js_query_string|system\.js_cache_files|system\.css_cache_files|'
                            r'drupal_css_cache_files|drupal_js_cache_files|update\.last_check|node\.min_max_update_time|'
                            r'routing\.non_admin_routes|router\.path_roots|system\.theme\.data|system\.module\.files|'
                            r'ultimate_cron|scheduler|search_api_|xmlsitemap|locale\.|views\.view_route_names|'
                            r'twig_extension_hash_prefix|system\.private_key|asset\.css_js_query_string|'
                            r'theme\.active_theme|webform\.element\.message|webform\.version|system\.javascript_parsed|update\.last_email_notification|system\.maintenance_mode|entity_type_tracker|.*last_run.*|.*cron.*)')


def drop_volatile(d):
    kv = d.get('key_value', {})
    for c in list(kv):
        if c == 'state':
            kv[c]['added'] = [x for x in kv[c]['added'] if not VOLATILE_STATE.match(x)]
            kv[c]['removed'] = [x for x in kv[c]['removed'] if not VOLATILE_STATE.match(x)]
        if c.startswith(('pre_existing_config', 'update_fetch_task')):
            del kv[c]
            continue
        if not kv[c]['added'] and not kv[c]['removed']:
            del kv[c]
    for t in ('watchdog', 'flood', 'sessions', 'cache_'):
        pass
    d['files_added'] = [f for f in d.get('files_added', []) if not f.startswith(('css/', 'js/', 'styles/', 'php/'))]
    return d


def content_summary(d):
    """Only non-configuration things: entities, menu links, files, queues, state."""
    out = {}
    for t, v in d.get('entities', {}).items():
        if 'added' in v and v['added']:
            out.setdefault('entities_added', {})[t] = v['added']
        if 'removed' in v and v['removed']:
            out.setdefault('entities_removed', {})[t] = v['removed']
        if 'error_after' in v:
            out.setdefault('entity_errors', {})[t] = v
    ml = {k: v for k, v in d.get('menu_links_added', {}).items() if k.startswith('menu_link_content:')}
    if ml:
        out['menu_link_content_added'] = ml
    if d.get('revisions'):
        out['revisions'] = d['revisions']
    if d.get('files_added'):
        out['files_added'] = d['files_added'][:50]
    if d.get('queue'):
        out['queue'] = d['queue']
    if d.get('key_value', {}).get('state'):
        out['state'] = d['key_value']['state']
    blocks = [c for c in d.get('config_added', []) if c.startswith('block.block.')]
    if blocks:
        out['block_placements_added'] = blocks
    if d.get('front_page'):
        out['front_page'] = d['front_page']
    return out


def http(p):
    rows = []
    try:
        for line in open(p):
            line = line.strip()
            if line:
                rows.append(json.loads(re.sub(r'"code":0+(\d)', r'"code":\1', line)))
    except Exception:
        pass
    return rows


def wd(p):
    """Errors listed by php/wd.php: the JSON line, even after drush warnings that also start with "["."""
    try:
        lines = open(p).read().splitlines()
    except OSError:
        return []
    for line in reversed(lines):
        if line.startswith(('[{', '[]')):
            try:
                return json.loads(line)
            except ValueError:
                return []
    return []


def health(dirp, prefix, control_http, control_wd_msgs):
    """Broken = cr/cron failure, a core page answering 5xx/000 (or another code than in the control run), a module page 5xx.

    New log errors are kept apart (watchdog_errors, flag new_log_errors): the site still works, the module still needs a look.
    """
    h = {'cr_rc': int(rd(f'{dirp}/{prefix}-cr.rc', '-1') or -1), 'cron_rc': int(rd(f'{dirp}/{prefix}-cron.rc', '-1') or -1)}
    rows = http(f'{dirp}/{prefix}-http.jsonl')
    # Lines starting with "_" are markers, not pages (_router_wait: seconds waited for the ddev router).
    h['router_wait'] = sum(x['time'] for x in rows if x['check'] == '_router_wait')
    rows = [x for x in rows if not x['check'].startswith('_')]
    ctl = {r['path']: r['code'] for r in control_http if not r['check'].startswith('_')}
    bad = []
    for r in rows:
        if r['check'] == 'module_page':
            if r['code'] >= 500 or r['code'] == 0:
                bad.append(r)
        elif r['code'] != ctl.get(r['path'], r['code']) or r['code'] >= 500 or r['code'] == 0:
            bad.append(r)
    h['unreachable'] = bool(rows) and all(x['code'] == 0 for x in rows if x['check'] != 'module_page')
    h['http'] = rows
    h['http_bad'] = bad
    errs = [e for e in wd(f'{dirp}/{prefix}-health-wd.json') if _norm(e['message']) not in control_wd_msgs]
    h['watchdog_errors'] = errs
    if h['cr_rc'] != 0:
        h['cr_out'] = tail(f'{dirp}/{prefix}-cr.txt')
    if h['cron_rc'] != 0:
        h['cron_out'] = tail(f'{dirp}/{prefix}-cron.txt')
    h['broken'] = bool(bad) or h['cr_rc'] != 0 or h['cron_rc'] != 0
    return h


def _norm(m):
    return re.sub(r'\d+', 'N', m)[:200]


def tail(p, n=12):
    try:
        lines = [l.rstrip() for l in open(p) if l.strip()]
    except Exception:
        return ''
    return '\n'.join(lines[-n:])


def drush_msg(p):
    """The message of a drush failure: the block after 'In X.php line N:' (unwrapped), else the first [error] line."""
    try:
        lines = open(p, errors='ignore').read().splitlines()
    except Exception:
        return ''
    for i, l in enumerate(lines):
        if re.match(r'^\s*In \S+ line \d+:\s*$', l):
            blk = []
            for x in lines[i + 1:]:
                if not x.strip():
                    if blk:
                        break
                    continue
                blk.append(x.rstrip())
            if blk:
                w = max(len(x) for x in blk)
                out = ''
                for x in blk:
                    c = x[2:] if x.startswith('  ') else x.strip()
                    out += c if len(x) >= w - 1 else c + ' '
                return re.sub(r'\s+', ' ', out).strip()[:600]
    for l in lines:
        if re.search(r'\[error\]|error:|exception', l, re.I):
            return re.sub(r'\s+', ' ', re.sub(r'\x1b\[[0-9;]*m', '', l)).strip()[:600]
    return ''


def err_lines(p):
    try:
        return [l.strip() for l in open(p) if re.search(r'error|exception|fatal|warning|\[warning\]|cannot|unable|not exist|refused|required', l, re.I)][:15]
    except Exception:
        return []


def cmd_result(d, m, control):
    s0, s1, s2, s3 = (load(f'{d}/s{i}.json') for i in range(4))
    mode = rd(f'{d}/mode.txt')
    nz = noise_of(control)
    ctl_http = http(f'{control}/http1.jsonl')
    ctl_wd = {_norm(e['message']) for e in wd(f'{control}/wd.json')}
    r = {'module': m, 'mode': mode, 'times': {k: float(rd(f'{d}/{k}.time', '0') or 0) for k in ('00-restore', '10-install', '20-uninstall', '30-reinstall', 'total')}}

    if mode == 'new':
        rc = int(rd(f'{d}/10-install.rc', '-1') or -1)
        inst = {'rc': rc, 'ok': rc == 0 and s1 is not None and m in s1['modules']}
        if s1 and s0:
            inst['modules_pulled'] = [x for x in setdiff(s0['modules'], s1['modules'])[0] if x != m]
        inst['output_tail'] = tail(f'{d}/10-install.txt')
        inst['output_errors'] = err_lines(f'{d}/10-install.txt')
        inst['message'] = drush_msg(f'{d}/10-install.txt')
        inst['watchdog_errors'] = [e for e in wd(f'{d}/10-install-wd.json') if _norm(e['message']) not in ctl_wd]
        r['install'] = inst
        if s0 and s1:
            d01 = drop_volatile(snapdiff(s0, s1, nz))
            r['install_diff'] = d01
            r['content_added'] = content_summary(d01)
            r['install_counts'] = {'tables': len(d01['tables_added']), 'config': len(d01['config_added']), 'config_changed': len(d01['config_changed']),
                                   'key_value_collections_added': [c for c in d01['key_value'] if c not in s0['key_value']],
                                   'routes': len(d01['routes_added'])}
        r['health_after_install'] = health(d, '11', ctl_http, ctl_wd)
    else:
        r['install'] = {'skipped': 'enabled by the profile install (base)'}

    rc = int(rd(f'{d}/20-uninstall.rc', '-1') or -1)
    un = {'rc': rc, 'ok': rc == 0 and s2 is not None and m not in s2['modules']}
    un['output_tail'] = tail(f'{d}/20-uninstall.txt')
    un['output_errors'] = err_lines(f'{d}/20-uninstall.txt')
    un['message'] = drush_msg(f'{d}/20-uninstall.txt')
    if s1 and s2:
        un['cascade'] = [x for x in setdiff(s1['modules'], s2['modules'])[1] if x != m]
    un['watchdog_errors'] = [e for e in wd(f'{d}/20-uninstall-wd.json') if _norm(e['message']) not in ctl_wd]
    r['uninstall'] = un
    r['health_after_uninstall'] = health(d, '21', ctl_http, ctl_wd)

    sD = load(f'{d}/sD.json')
    if sD:
        r['leftover_reference'] = 'base + pulled dependencies only (sD.json)'
    if s0 and s2:
        d02 = drop_volatile(snapdiff(sD or s0, s2, nz))
        r['after_uninstall_vs_before'] = d02
        lo = {}
        if mode == 'new':
            for k in ('tables_added', 'config_added', 'config_changed', 'files_added', 'cron_hooks_added', 'modules_added'):
                if d02.get(k):
                    lo[k] = d02[k]
            if d02['key_value']:
                lo['key_value'] = d02['key_value']
            if d02['role_permissions']:
                lo['role_permissions'] = d02['role_permissions']
            c = content_summary(d02)
            if c:
                lo['content'] = c
            mlc = {k: v for k, v in d02.get('menu_links_added', {}).items() if k.startswith('menu_link_content:')}
            if mlc:
                lo['menu_links_added'] = mlc
            other = [k for k in d02.get('menu_links_added', {}) if not k.startswith('menu_link_content:')]
            if other:
                lo['menu_tree_static_links_added'] = other
        else:
            # Enabled by the profile: list what still carries the module's name after the uninstall.
            pat = re.compile(r'(^|[._:])' + re.escape(m) + r'($|[._:])')
            lo_t = [t for t in s2['tables'] if t == m or t.startswith(m + '_')]
            lo_c = [c for c in s2['config'] if c.startswith(m + '.')]
            lo_kv = {c: [x for x in v if pat.search(x)] for c, v in s2['key_value'].items() if c not in ('system.schema',)}
            lo_kv = {c: v for c, v in lo_kv.items() if v}
            if m in s2['key_value'].get('system.schema', []):
                lo_kv.setdefault('system.schema', []).append(m)
            for k, v in (('tables_named_after_module', lo_t), ('config_named_after_module', lo_c), ('key_value_named_after_module', lo_kv)):
                if v:
                    lo[k] = v
            if un['ok']:
                rem = d02.get('entities', {})
                lo['_info_entities_removed_by_uninstall'] = {t: len(v.get('removed', {})) for t, v in rem.items() if v.get('removed')}
        r['leftovers'] = lo

    rc = int(rd(f'{d}/30-reinstall.rc', '-1') or -1)
    re_ = {'rc': rc, 'ok': rc == 0 and s3 is not None and m in s3['modules'], 'output_tail': tail(f'{d}/30-reinstall.txt'),
           'output_errors': err_lines(f'{d}/30-reinstall.txt'), 'message': drush_msg(f'{d}/30-reinstall.txt')}
    if os.path.exists(f'{d}/30-cascade-modules.txt'):
        re_['cascade_reinstalled'] = rd(f'{d}/30-cascade-modules.txt').split()
        re_['cascade_rc'] = int(rd(f'{d}/31-reinstall-cascade.rc', '-1') or -1)
        re_['cascade_output_errors'] = err_lines(f'{d}/31-reinstall-cascade.txt')
    re_['watchdog_errors'] = [e for e in wd(f'{d}/30-reinstall-wd.json') if _norm(e['message']) not in ctl_wd]
    if s1 and s3 and s0:
        ref = s1  # state after first install (or base for enabled modules)
        d13 = drop_volatile(snapdiff(ref, s3, nz))
        first = drop_volatile(snapdiff(s0, s1, nz)) if mode == 'new' else None
        dup = {}
        for t, v in d13.get('entities', {}).items():
            ad = v.get('added') or {}
            if not ad:
                continue
            if mode == 'new':
                same = set((first.get('entities', {}).get(t, {}) or {}).get('added', {}).values())
            else:
                same = set((s0['entities'].get(t) or {}).values())
            dd = {k: lab for k, lab in ad.items() if lab in same}
            if dd:
                dup[t] = dd
        mdup = {k: v for k, v in d13.get('menu_links_added', {}).items() if v in set((first or {}).get('menu_links_added', {}).values() if first else s0['menu_tree'].values())}
        if mdup:
            dup['menu_tree'] = mdup
        re_['duplicates'] = dup
        re_['diff_vs_after_first_install'] = {'entities': {t: {'count_before': v.get('count_before'), 'count_after': v.get('count_after')} for t, v in d13.get('entities', {}).items()},
                                              'menu_links_added': d13.get('menu_links_added'), 'revisions': d13.get('revisions'),
                                              'modules_added': d13.get('modules_added'), 'modules_removed': d13.get('modules_removed')}
    r['reinstall'] = re_

    # Flags.
    f = {}
    if mode == 'new':
        f['install_fail'] = not r['install']['ok']
        ca = r.get('content_added', {})
        f['content_added'] = bool(ca.get('entities_added') or ca.get('menu_link_content_added') or ca.get('block_placements_added') or ca.get('front_page'))
        f['breaks_after_install'] = r['health_after_install']['broken']
    f['uninstall_refused'] = not un['ok']
    f['uninstall_cascade'] = bool(un.get('cascade'))
    f['breaks_after_uninstall'] = r['health_after_uninstall']['broken']
    f['new_log_errors'] = any((r.get(k) or {}).get('watchdog_errors') and not (r.get(k) or {}).get('broken')
                              for k in ('health_after_install', 'health_after_uninstall'))
    lo = r.get('leftovers', {})
    f['leftovers'] = bool({k: v for k, v in lo.items() if not k.startswith('_')})
    f['leftover_content'] = bool((lo.get('content') or {}).get('entities_added') or lo.get('menu_links_added'))
    f['reinstall_fail'] = not re_['ok']
    f['reinstall_duplicates'] = bool(re_.get('duplicates'))
    f['measurement_invalid'] = any((r.get(k) or {}).get('unreachable') for k in ('health_after_install', 'health_after_uninstall'))
    r['flags'] = f
    with open(f'{d}/result.json.tmp', 'w') as fh:  # write then rename: never a truncated result
        json.dump(r, fh, indent=1, ensure_ascii=False, sort_keys=True)
    os.replace(f'{d}/result.json.tmp', f'{d}/result.json')
    open(f'{d}/summary.md', 'w').write(summary_md(r))


def short(v, n=8):
    if isinstance(v, dict):
        items = list(v.items())
        s = ', '.join(f'{k}={short(x, 3)}' if isinstance(x, (dict, list)) else f'{k}: {x}' for k, x in items[:n])
    elif isinstance(v, list):
        s = ', '.join(str(x) for x in v[:n])
        items = v
    else:
        return str(v)
    return s + (f' … (+{len(items) - n})' if len(items) > n else '')


def summary_md(r):
    m = r['module']
    L = [f'# {m} ({r["mode"]})', '', f'Times (s): {r["times"]}', '', f'Flags: {", ".join(k for k, v in r["flags"].items() if v) or "none"}', '']
    if r['mode'] == 'new':
        i = r['install']
        L += ['## 1. Install alone (`drush en %s -y`)' % m, f'- rc={i["rc"]} ok={i["ok"]}; pulled ({len(i.get("modules_pulled", []))}): {short(i.get("modules_pulled", []), 40)}']
        if not i['ok']:
            L += ['- message: ' + i.get('message', ''), '```', i['output_tail'], '```']
        if i.get('watchdog_errors'):
            L += ['- watchdog errors during install: ' + short([e['message'] for e in i['watchdog_errors']], 5)]
        L += ['', '## 2. Added by install (not config)']
        for k, v in r.get('content_added', {}).items():
            L.append(f'- {k}: {short(v, 12)}')
        L.append(f'- counts: {r.get("install_counts")}')
        L += ['', '## 3. Health after install', _h(r['health_after_install'])]
    u = r['uninstall']
    L += ['', '## 4. Uninstall (`drush pmu %s -y`)' % m, f'- rc={u["rc"]} ok={u["ok"]}; cascade: {short(u.get("cascade", []), 40)}']
    if not u['ok']:
        L += ['- message: ' + u.get('message', ''), '```', u['output_tail'], '```']
    L += ['', '### Health after uninstall', _h(r['health_after_uninstall'])]
    L += ['', '## 5. Leftovers after uninstall']
    for k, v in r.get('leftovers', {}).items():
        L.append(f'- {k}: {short(v, 15)}')
    re_ = r['reinstall']
    L += ['', '## 6. Reinstall', f'- rc={re_["rc"]} ok={re_["ok"]}; duplicates: {short(re_.get("duplicates", {}), 10) or "none"}']
    if re_.get('cascade_reinstalled'):
        L.append(f'- cascade reinstalled: {short(re_["cascade_reinstalled"], 40)} rc={re_.get("cascade_rc")}')
    if not re_['ok']:
        L += ['- message: ' + re_.get('message', ''), '```', re_['output_tail'], '```']
    return '\n'.join(L) + '\n'


def _h(h):
    s = f'- cr rc={h["cr_rc"]}, cron rc={h["cron_rc"]}; pages: ' + ', '.join(f'{x["path"]}={x["code"]}' for x in h['http'])
    if h['http_bad']:
        s += '\n- BAD: ' + ', '.join(f'{x["path"]}={x["code"]}' for x in h['http_bad'])
    if h.get('cron_out'):
        s += '\n```\n' + h['cron_out'] + '\n```'
    if h.get('cr_out'):
        s += '\n```\n' + h['cr_out'] + '\n```'
    if h['watchdog_errors']:
        s += '\n- watchdog errors: ' + short([e['type'] + ': ' + e['message'] for e in h['watchdog_errors']], 5)
    return s


if __name__ == '__main__':
    c = sys.argv[1]
    if c == 'routes':
        a, b = load(sys.argv[2]), load(sys.argv[3])
        if a and b:
            new = sorted(set(b['routes'].values()) - set(a['routes'].values()))
            new = [p for p in new if '{' not in p and not p.startswith(('/api/', '/jsonapi'))]
            for p in new[:6]:
                print('/en' + p)
    elif c == 'pulled':
        a, b = load(sys.argv[2]), load(sys.argv[3])
        if a and b and sys.argv[4] in b['modules']:
            print(' '.join(x for x in sorted(set(b['modules']) - set(a['modules'])) if x != sys.argv[4]))
    elif c == 'cascade':
        a, b = load(sys.argv[2]), load(sys.argv[3])
        if a and b:
            print(' '.join(x for x in sorted(set(a['modules']) - set(b['modules'])) if x != sys.argv[4]))
    elif c == 'result':
        cmd_result(sys.argv[2], sys.argv[3], sys.argv[4])
