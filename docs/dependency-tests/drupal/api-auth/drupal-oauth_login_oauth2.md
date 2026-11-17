# drupal/oauth_login_oauth2

| | |
|---|---|
| Contrainte composer.json | `^2.6` |
| Version lockée | `2.6.0` |
| Patches | « Store ressourceOwner into a session variable » (gist voidagency) |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — `vactory_sso` instancie une classe du paquet par son nom et lit `$_SESSION['resource_owner']` fourni par le patch. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_sso | `vactory_sso.module:8,38` | `use Drupal\oauth_login_oauth2\Controller\oauth_login_oauth2Controller` puis `new oauth_login_oauth2Controller()` + `->getnestedattribute()` |
| vactory_sso | `vactory_sso.module:19,33` | dépend du nom de route `oauth_login_oauth2.mo_login` |
| vactory_sso | `vactory_sso.module:21-23` | écrit la config `oauth_login_oauth2.settings:oauth_login_oauth2_free_users` |
| vactory_sso | `vactory_sso.module:36` | lit `$_SESSION['resource_owner']` (ajouté par le patch) |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `vactory_sso/tests/src/Kernel/SsoContribApiTest.php`** — `class_exists('Drupal\oauth_login_oauth2\Controller\oauth_login_oauth2Controller')` et `method_exists(..., 'getnestedattribute')` ; route `oauth_login_oauth2.mo_login` présente dans le routeur. Attrape exactement la casse décrite en Notes.
- **[Kernel] `vactory_sso/tests/src/Kernel/SsoUserLoginTest.php`** — simuler `hook_user_login` avec route `oauth_login_oauth2.mo_login` et `$_SESSION['resource_owner']` mappé → champs utilisateur mis à jour.

## Notes
- **Constat** : dans le 2.6.0 présent dans `vendor/`, la seule classe contrôleur est `miniorange_oauth_clientController` (`vendor/drupal/oauth_login_oauth2/src/Controller/miniorange_oauth_clientController.php:28`, méthode `getnestedattribute` :247). La classe `oauth_login_oauth2Controller` n'existe pas → `vactory_sso.module:38` lèverait « Class not found » au login SSO. Vendor local non patché : **à vérifier** sur une install patchée.
- La clé `oauth_login_oauth2_free_users` n'apparaît pas dans le code du paquet 2.6.0 (grep vide) : écriture probablement sans effet, à vérifier.
- `vactory_sso.info.yml` ne déclare aucune dépendance sur `oauth_login_oauth2`.

## Historique
- 2020-06-29 `22b235d7` patch ajouté « Store ressourceOwner into a session variable » (gist)
- 2021-01-31 `1be86d53` ^1.21 → ^1.222 ; 2021-02-01 `a16531bb` patch modifié
- 2023-05-15 `7009653e` patch déplacé gist voidagency — #40654
- 2023-07-13 `ca1115c4` montée ^1.222 → ^2.6, patch réécrit — #44188
- 2024-01-29 `f1c27559` patch déplacé gist voidagency
- *hors 3.x* 2026-10-26 `3028ff9a` → `3.1.0` épinglé, patch rebasé, « fix vactory_sso attribute mapping » — `origin/upgrade/d11`
- Redmine #71100 « vactory_sso : classe oauth_login_oauth2Controller inexistante » (ouvert).

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
