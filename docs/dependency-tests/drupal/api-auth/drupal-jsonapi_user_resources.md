# drupal/jsonapi_user_resources

| | |
|---|---|
| Contrainte composer.json | `^1.0@beta` |
| Version lockée | `1.0.0-beta1` |
| Patches | aucun |
| Statut | Indirect (dépendance seulement) |
| Risque à la MAJ | Faible côté code (aucun appel) ; Moyen côté front si le front Next.js appelle ses routes (à vérifier). |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `vactory_decoupled.info.yml:11` | dépendance de module uniquement |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/JsonApiUserResourcesRoutesTest.php`** — si le front l'utilise : POST `/api/user/register` (préfixe jsonapi_extras) → 201/422 selon payload ; GET `/api/me` authentifié → 200. À n'écrire qu'après confirmation d'usage front.

## Notes
- 0 occurrence de `jsonapi_user_resources` en PHP/config hors `info.yml`. L'inscription passe par le webform `vactory_espace_prive_register` (voir `vactory_decoupled_espace_prive.module:74`). Candidat à suppression, à vérifier côté front.

## Historique
- 2022-03-23 `12729f65` ajout épinglé `1.0.0-alpha4`
- 2023-07-13 `4b48e654` → ^1.0@beta — #44188
- Aucun incident.
