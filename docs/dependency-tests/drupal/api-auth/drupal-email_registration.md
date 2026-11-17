# drupal/email_registration

| | |
|---|---|
| Contrainte composer.json | `^1.2` |
| Version lockée | `1.4.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — appels directs à 2 fonctions procédurales du `.module` (`email_registration_cleanup_username`, `email_registration_unique_username`) sans déclaration de dépendance ; disparition/renommage (passage en service en 2.x) = fatal à l'inscription. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_espace_prive | `vactory_decoupled_espace_prive.module:98-99` | `email_registration_cleanup_username()` / `email_registration_unique_username()` dans `hook_decoupled_webform_data_presubmit_alter` (inscription) |
| vactory_decoupled | `src/Controller/UsernameController.php:28,31` | idem, route `/api/get-unique-username-by-email` (`vactory_decoupled.routing.yml:118`) |
| vactory_user | `vactory_user.module:45-46` | config `email_registration.settings:login_with_username` (placeholder du formulaire de login) |
| profil | `vactory_starter_kit.info.yml:104` | installé par le profil |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/UniqueUsernameByEmailTest.php`** — user `john.doe` existant ; GET `/api/get-unique-username-by-email?email=john.doe@ex.com` → nom différent de `john.doe` et non vide ; email avec caractères spéciaux → nom nettoyé. Attrape la disparition des fonctions.
- **[Kernel] `vactory_decoupled/tests/src/Kernel/EmailRegistrationApiTest.php`** — `function_exists('email_registration_unique_username')` et `function_exists('email_registration_cleanup_username')` après `enableModules(['email_registration'])` ; garde-fou rapide.

## Notes
- Ni `vactory_decoupled.info.yml` ni `vactory_decoupled_espace_prive.info.yml` ne déclarent `email_registration` : la dépendance n'est garantie que par le profil.
- email_registration 2.x remplace ces fonctions (à vérifier lors d'une montée majeure).

## Historique
- 2020-06-28 `1af659f7` ajout épinglé `1.0` ; 2021-01-31 `1be86d53` → `1.1`
- 2022-03-28 `9eb3ca69` patch ajouté « Allow authentication via mail and password over OAuth2 module » (d.o #2991141)
- 2023-07-13 `ab59b8f6` montée 1.1 → ^1.2, patch retiré — #44188
- *hors 3.x* `bf4234f9`, `c9ed04c3` → ^2.0@RC — `origin/upgrade/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
