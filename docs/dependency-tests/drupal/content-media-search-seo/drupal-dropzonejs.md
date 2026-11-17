# drupal/dropzonejs

| | |
|---|---|
| Contrainte composer.json | `^2.3` |
| Version lockée | `2.8.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Faible/Moyen — formulaire admin d'upload de documents en masse. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_core | `modules/vactory_core/src/Form/DocumentsEnMasseForm.php:36` | `#type => dropzonejs` ; lecture `uploaded_files` (l.66) — route `/admin/config/upload-documents` |
| info.yml | `modules/vactory_core/vactory_core.info.yml:12` | Dépendance ; profil l.153 |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_core/tests/src/ExistingSite/DocumentsEnMasseFormTest.php`** — GET `/admin/config/upload-documents` en admin → 200, élément dropzone présent.

## Notes
Nécessite la lib JS dropzone (library) côté site — à vérifier dans le build.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-01-31 `1be86d53` ajout `^2.3` — « Update: Add v2.1.0 Factory release changes. »
