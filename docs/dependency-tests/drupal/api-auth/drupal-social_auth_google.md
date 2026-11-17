# drupal/social_auth_google

| | |
|---|---|
| Contrainte composer.json | `^3.0@RC` |
| Version lockée | `3.0.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — aucune API PHP appelée ; seule la config `social_auth_google.settings` et la route du bouton de login sont fournies par `vactory_social_auth`. L'auth headless google passe par `SimpleOauthAuthenticationProvider` (appel HTTP direct), pas par ce module. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_social_auth | `vactory_social_auth.info.yml:9` | dépendance |
| vactory_social_auth | `config/optional/social_auth_google.settings.yml` | config du provider |
| vactory_social_auth | `config/optional/social_auth.settings.yml:5-7` | route `social_auth_google.redirect_to_google` + logo |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_social_auth/tests/src/ExistingSite/SocialLoginRoutesTest.php`** — module activé : la route du bouton (`social_auth_google.redirect_to_google`) existe et redirige (302) vers le provider. Attrape un renommage de route référencé dans notre config.

## Notes
- `vactory_social_auth` n'est pas dans la liste d'install du profil (module optionnel).

## Historique
- 2020-06-28 `1af659f7` ajout ^2.0.0-beta3 ; 2021-01-31 `1be86d53` → ^3.0@RC
- *hors 3.x* → ^4.0 (`bf4234f9`, `fd0c200c`)
- Aucun incident.

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
