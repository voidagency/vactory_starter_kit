# drupal/persistent_login

| | |
|---|---|
| Contrainte composer.json | `^1.3` |
| Version lockée | `1.6.0` |
| Patches | aucun |
| Statut | Indirect (dépendance + install profil) |
| Risque à la MAJ | Faible — aucune API ; agit seulement sur le login Drupal (cookie « remember me »), pas sur le login OAuth headless. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_user | `vactory_user.info.yml:13` | dépendance |
| profil | `vactory_starter_kit.info.yml:109` | installé |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_user/tests/src/ExistingSite/UserLoginFormTest.php`** — GET `/user/login` → case `persistent_login` présente et placeholders `vactory_user` (couvre aussi email_registration). Faible priorité.

## Notes
- 0 occurrence de `persistent_login`/`PersistentLogin` en PHP/Twig/JS ; aucune config livrée.

## Historique
- 2020-06-28 `1af659f7` ajout ^1.2 ; 2021-01-31 `1be86d53` → ^1.3
- *hors 3.x* → ^2.2 (`bf4234f9`, `c9ed04c3`)
- Aucun incident.

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
