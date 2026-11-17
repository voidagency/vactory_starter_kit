# drupal/allowed_formats

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — réglages de champs texte en config (`allowed_formats`) ; une MAJ de schéma peut faire échouer l'install des modules concernés. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_business | `vactory_business.info.yml:7` ; `config/install/field.field.node.vactory_business.{body,field_vactory_address}.yml:8,14` | Dépendance + `third_party_settings.allowed_formats` |
| vactory_project_progress | `vactory_project_progress.info.yml:5` ; `config/install/field.field.taxonomy_term.project_progress_item.field_description.yml:8,14-15` | idem |
| vactory_help_center, vactory_seo | `config/install/field.field.node.*.body.yml:22` | `allowed_formats: {}` (clé de settings, format core D10.1+) |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun dédié ; l'install de ces modules optionnels en Kernel suffirait (`installConfig(['vactory_business'])`). P3.

## Notes
- Depuis D10.1 le core gère `allowed_formats` dans les settings de champ ; le module contrib 2.x est une couche de compat — candidat à la suppression (à vérifier).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-05-08 `2b30ea04` ajout `^2.0` — « Add: #43898 - Add allowed_formats drupal contrib module »
