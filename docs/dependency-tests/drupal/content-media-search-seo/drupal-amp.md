# drupal/amp

| | |
|---|---|
| Contrainte composer.json | `^3.8` |
| Version lockée | `3.8.0` |
| Patches | aucun |
| Statut | Utilisé (module custom non installé par le profil) |
| Risque à la MAJ | Moyen — `vactory_amp` décore `router.amp_context` en **étendant** `AmpContext` et en appelant son constructeur (5 arguments). |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : passage en `3.x-dev@dev` pour warnings D9 (`2876438c`) + patch correctif PHP 8.1 (`894e4058`) (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_amp | `modules/vactory_amp/src/Services/VactoryAmpContextDecorator.php:13` | `extends Drupal\amp\Routing\AmpContext`, `parent::__construct(...)` (signature couplée), surcharge `isAmpRoute()` (l.40) |
| vactory_amp | `modules/vactory_amp/vactory_amp.services.yml:4` | `decorates: router.amp_context`, arg `@amp.entity_type` |
| vactory_amp | `modules/vactory_amp/vactory_amp.module:81` | `hook_entity_view_alter`, `form_alter` (l.43), base field (l.22) |
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/Plugin/Field/FieldFormatter/VactoryDynamicFormatter.php:279` | `moduleExists('amp')` + templates `template.amp.html.twig` |
| vactory_amp | `modules/vactory_amp/vactory_amp.info.yml:7` | Dépendance |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `modules/vactory_amp/tests/src/Kernel/AmpContextDecoratorTest.php`** — activer amp+vactory_amp → `\Drupal::service('router.amp_context')` instance de `VactoryAmpContextDecorator` (constructeur parent compatible).

## Notes
AMP est en fin de vie côté Google ; module non installé par le profil (`amptheme` commenté l.196 : « handled in frontend »). Candidat à suppression.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.1.0`
- 2021-01-31 `1be86d53` montée `^2.1.0` → `^3.5` — « Update: Add v2.1.0 Factory release changes. »
- 2021-11-17 `2876438c` passage branche dev `^3.5` → `3.x-dev@dev` — « Update: Fix Drupal 9 warnings & Add fixes from Barid project. »
- 2023-01-06 `5c0d2b07` montée (sortie de dev) `3.x-dev@dev` → `^3.5` — « Update: #40522 - replace deprecated code »
- 2023-01-27 `894e4058` patch ajouté « Fix deprecated php 8.1 issue rtrim » — « Fix: Add php 8 compatibility to contrib modules »
- 2023-05-12 `285c7df4` montée `^3.5` → `^3.6` — « Update: Remove drupal/amp unused patch & fix module min version to 3.6 »
- 2023-05-12 `285c7df4` patch retiré « Fix deprecated php 8.1 issue rtrim » — « Update: Remove drupal/amp unused patch & fix module min version to 3.6 »
- 2023-09-04 `f3896911` fix compat lib (hors 3.x) « Fix lullabot/amp 2.0.4 compatibility error » — seulement sur `origin/2.x`/`origin/3.x-beta`
- 2023-11-23 `5f05bf03` montée `^3.6` → `^3.8` — « Update: update drupal/amp contrib module »
