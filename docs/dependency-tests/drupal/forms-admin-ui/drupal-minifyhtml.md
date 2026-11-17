# drupal/minifyhtml

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.3` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — minification de la sortie HTML (rendu Drupal, pas JSON:API) ; une régression peut corrompre le HTML (ex. `<pre>`, scripts inline). |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:147` | Installé |

0 autre occurrence ; pas de config `minifyhtml.config` livrée.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Aucun dédié ; le test de rendu node proposé (`drupal-classy.md` / `drupal-twig_tweak.md`) en anonyme couvre la sortie minifiée. P3.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.8`
- 2023-07-13 `4b48e654` montée `^1.8` → `^2.0` — « Fix: #44188 - Drupal 10 compatibility: Update responsive_preview, twig_tweak, user_registratio… »
