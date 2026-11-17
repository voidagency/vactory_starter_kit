# drupal/social_api

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.1.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — classes de base de l'intégration Keycloak (`SettingsBase`, `NetworkManager`, `SocialApiException`). |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_keycloak | `src/Settings/KeycloakAuthSettings.php:5,10` | `extends Drupal\social_api\Settings\SettingsBase` |
| vactory_keycloak | `src/Controller/KeycloakAuthController.php:7,56` | `NetworkManager` (service `plugin.network.manager`) |
| vactory_keycloak | `src/Plugin/Network/KeycloakAuth.php:6,38` | `SocialApiException` |
| vactory_user | `vactory_user.module:19-22` | si `social_api` présent : `plugin.network.manager->getDefinitions()` |
| vactory_social_auth | `vactory_social_auth.info.yml:6` | dépendance |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- Mutualisé avec `vactory_keycloak/tests/src/Kernel/KeycloakPluginTest.php` (fiche social_auth) : vérifier `plugin.network.manager->getDefinition('vactory_keycloak')` et `KeycloakAuthSettings` (`getClientId()` etc.) depuis la config.

## Notes
- Requis aussi par `drupal/social_auth` (composer.lock).

## Historique
- 2020-06-28 `1af659f7` ajout `2.0-rc2` épinglé ; 2021-01-31 `1be86d53` → ^3.0
- *hors 3.x* `bf4234f9` → ^4.1, `fd0c200c` → ^4.0 — `origin/update/d11`, `origin/upgrade/d11`
- Aucun incident.

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
