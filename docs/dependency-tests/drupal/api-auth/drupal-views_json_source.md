# drupal/views_json_source

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.1` |
| Patches | aucun |
| Statut | Indirect (config seulement) |
| Risque à la MAJ | Faible — une vue d'admin (journal des redirections 301→200) basée sur les plugins `views_json_source_field`. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_wysiwyg_301to200 | `vactory_wysiwyg_301to200.info.yml:7` | dépendance |
| vactory_wysiwyg_301to200 | `config/install/views.view.wysiwyg_301to200.yml:9,34,161,212,263` | query `json_file: /sites/default/files/vactory_wysiwyg_301to200/redirects-log.json`, champs `views_json_source_field` |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `vactory_wysiwyg_301to200/tests/src/Kernel/RedirectsLogViewTest.php`** — installer la config de la vue, écrire un `redirects-log.json` de 2 lignes en fixture, exécuter la vue → 2 résultats. Attrape une évolution des plugins/du schéma de query.

## Notes
- `vactory_wysiwyg_301to200` n'est pas installé par le profil (optionnel).

## Historique
- 2022-03-10 `0a6e111e` ajout ^1.1
- 2024-04-15 `364e0244` dependabot → ^2.0 (majeure)
- Aucun incident.
