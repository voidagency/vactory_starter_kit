# drupal/seven

| | |
|---|---|
| Contrainte composer.json | `^1.0` |
| Version lockée | `1.0.0` |
| Patches | aucun |
| Statut | Utilisé (thème de l'installeur uniquement) |
| Risque à la MAJ | Moyen — retirer le paquet casse l'installation du profil (`distribution.install.theme: seven`) ; aucun usage au runtime (admin = claro). |
| Couverture actuelle | Partielle (la CI exécute `drush si vactory_starter_kit`) |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:7-9` | `distribution: install: theme: seven` (thème de maintenance pendant l'install) |
| vactory_appointment | `vactory_appointment.module:317-318` | `getActiveTheme()->getName() == 'seven'` → masque 2 champs ; code mort car `config/install/system.theme.yml:1` met `admin: claro` |

Le profil n'installe que `vactory` et `claro` (`vactory_starter_kit.info.yml`, clé `themes`) ; 0 occurrence de `seven` dans `config/`.

## Tests existants qui l'exercent
- Aucun test PHPUnit ; `drush si` en CI (`.drone.yml` → `vactory-test-ci/entrypoint.sh`) échouerait si le thème manquait.

## Tests à écrire
- Rien de plus que l'install CI. Option : basculer `distribution.install.theme` sur `claro` (core) et supprimer le paquet + la condition morte de `vactory_appointment.module:318` (qui devrait viser `claro`).

## Notes
- Seven a été retiré du core en D10 : ce paquet contrib n'existe que pour la compat. Bug latent : la condition `seven` de vactory_appointment ne s'applique jamais.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-07-17 `e238b528` ajout `^1.0` — « Fix: #44188 - Drupal 10 compatibility: Add rdf, seven, classy and quickedit contrib extensions »
