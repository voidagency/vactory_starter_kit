# drupal/devel

| | |
|---|---|
| Contrainte composer.json | `^5.5` |
| Version lockée | `5.5.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — outil dev ; seule une config du profil référence son plugin mail. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_devtools | `vactory_devtools.info.yml:8-9,21-22` | `devel`, `devel_generate` dans les listes install/uninstall |
| vactory_core | `vactory_core.drush.inc:44-45` | Liste des modules activés par `_vactory_core_switch_environment(development)` |
| profil | `config/install/mailsystem.settings.yml:3-4` | `devel_mail_log` (plugin `Drupal\devel\Plugin\Mail\DevelMailLog`) alors que devel n'est pas installé par le profil |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun dédié.

## Notes
- Incohérence de config mailsystem → devel (cf. `drupal-mailsystem.md`). Candidat `require-dev`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.1.0`
- 2021-01-31 `1be86d53` épinglage exact `^2.1.0` → `4.0.1` — « Update: Add v2.1.0 Factory release changes. »
- 2022-03-08 `5193b43f` montée `4.0.1` → `^4.1` — « Add: #33602 - Add vactory AMP custom module. »
- 2023-07-14 `9950f3ac` montée `^4.1` → `^5.0` — « Fix: #44188 - Drupal 10 compatibility: Update devel conntrib modules »
- 2026-09-17 `f337dbc2` montée `^5.0` → `^5.5` — « Update: upgrade profile dependencies and support Metatag 2 »
