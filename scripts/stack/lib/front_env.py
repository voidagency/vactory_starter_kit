"""Écrit le .env.local du front d'une instance « stack ».

Usage : python3 front_env.py <src_app_dir> <dest_app_dir> <secrets.env> CLE=VALEUR...

Reprend les clés de <src>/.env et <src>/.env.local, remplace celles propres
à l'instance (arguments CLE=VALEUR, puis secrets de l'instance) et écrit
<dest>/.env (copie) et <dest>/.env.local (fusion). N'affiche aucune valeur.
"""
import os
import re
import sys

LINE = re.compile(r'^\s*(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=')

# Clé du front -> clé de secrets.env.
SECRET_KEYS = {
    'OAUTH_CLIENT_ID': 'STACK_FRONT_CLIENT_ID',
    'OAUTH_CLIENT_SECRET': 'STACK_FRONT_CLIENT_SECRET',
    'NEXTAUTH_SECRET': 'STACK_NEXTAUTH_SECRET',
    'CACHE_SECRET': 'FRONTEND_CACHE_KEY',
}


def read_lines(path):
    if not os.path.isfile(path):
        return []
    with open(path, encoding='utf-8') as handle:
        return handle.read().splitlines()


def read_secrets(path):
    values = {}
    for line in read_lines(path):
        if '=' in line and not line.startswith('#'):
            key, value = line.split('=', 1)
            values[key.strip()] = value
    return values


def write_if_changed(path, text):
    if os.path.isfile(path):
        with open(path, encoding='utf-8') as handle:
            if handle.read() == text:
                return
    with open(path, 'w', encoding='utf-8') as handle:
        handle.write(text)


def main():
    src, dest, secrets_path = sys.argv[1:4]
    overrides = {}
    for arg in sys.argv[4:]:
        key, value = arg.split('=', 1)
        overrides[key] = value
    secrets = read_secrets(secrets_path)
    for front_key, secret_key in SECRET_KEYS.items():
        if secret_key in secrets:
            overrides[front_key] = secrets[secret_key]

    os.makedirs(dest, exist_ok=True)
    old_umask = os.umask(0o077)
    try:
        # N'écrit que si le contenu change : une réécriture fait recharger
        # l'environnement au serveur Next en cours, qui perd alors son
        # dossier .next/dev (constaté avec « stack up » relancé).
        if os.path.isfile(os.path.join(src, '.env')):
            write_if_changed(os.path.join(dest, '.env'), open(os.path.join(src, '.env'), encoding='utf-8').read())
        out, seen = [], set()
        for line in read_lines(os.path.join(src, '.env.local')):
            match = LINE.match(line)
            if match and match.group(1) in overrides:
                key = match.group(1)
                if key in seen:
                    continue
                out.append('%s=%s' % (key, overrides[key]))
                seen.add(key)
            else:
                out.append(line)
        added = [k for k in overrides if k not in seen]
        if added:
            out.append('')
            out.append('# Instance stack')
            out.extend('%s=%s' % (k, overrides[k]) for k in added)
        write_if_changed(os.path.join(dest, '.env.local'), '\n'.join(out) + '\n')
    finally:
        os.umask(old_umask)
    print('front env: %d clés remplacées ou ajoutées' % len(overrides))


if __name__ == '__main__':
    main()
