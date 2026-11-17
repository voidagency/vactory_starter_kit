# drupal/memcache

| | |
|---|---|
| Contrainte composer.json | `^2.2` |
| Version lockée | `2.5.0` |
| Patches | aucun |
| Statut | Indirect (install profil ; activation via settings.php hors repo) |
| Risque à la MAJ | Moyen en prod (backend de cache), nul pour le code du profil : aucun appel. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:63` | installé |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- Aucun dans le profil. Un smoke test d'infra (settings.php avec `cache.backend.memcache`) relève des projets/hébergement, pas du profil.

## Notes
- 0 occurrence de `memcache` dans `modules/`, `scripts/`, `Dockerfile`, `Makefile`. Le module installé sans config `settings.php` n'a aucun effet (à vérifier côté projets).

## Historique
- 2020-06-28 `1af659f7` ajout `2.0.0` ; 2021-01-31 `1be86d53` → ^2.2
- Aucun incident.
