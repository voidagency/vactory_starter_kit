# drupal/hreflang

| | |
|---|---|
| Contrainte composer.json | `^1.16` |
| Version lockée | `1.16.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — `vactory_hreflang` lit la config `hreflang.settings` (x_default) et réécrit les balises `hreflang` émises par le module. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_hreflang | `modules/vactory_hreflang/src/Plugin/Field/InternalNodeHreflangFieldItemList.php:99` | `configFactory->get('hreflang.settings')` : clé `x_default` (l.101), cache tag (l.58) |
| vactory_hreflang | `modules/vactory_hreflang/vactory_hreflang.module:38` | `hook_page_attachments_alter` : réécrit `html_head_link[*][0]['hreflang']` produit par le module |
| vactory_hreflang | `modules/vactory_hreflang/vactory_hreflang.info.yml:9` | Dépendance `hreflang:hreflang` |
| info.yml | `vactory_starter_kit.info.yml:96` | Profil (+ vactory_hreflang l.184) |

## Tests existants qui l'exercent
- aucun (pas de dossier `tests` dans vactory_hreflang)

## Tests à écrire
- **[ExistingSite] `modules/vactory_hreflang/tests/src/ExistingSite/InternalNodeHreflangTest.php`** — node FR+AR, mapping `fr→fr-MA`, `hreflang.settings.x_default=TRUE` → JSON:API du node expose un lien `x-default` et `fr-MA`. Attrape un renommage de clé de config.
- **[ExistingSite] `HreflangHeadTest`** — rendu HTML du node → `<link rel="alternate" hreflang="fr-MA">`.

## Notes
Paquet absent de `vendor/drupal/` local alors qu'il est locké (vendor non synchronisé — à vérifier). Dépend de la structure d'attachement `html_head_link` du module.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2026-09-21 `68f37d4e` ajout `^1.16` — « feat: add Vactory Hreflang module for configurable hreflang mappings »
