# drupal/user_registrationpassword

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — flux d'inscription headless : constantes de classe, fonction privée `_user_registrationpassword_mail_notify()`, clés de config (`registration`, `registration_ftll_*`) et ID de mail interceptés. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `vactory_decoupled.info.yml:21` | dépendance |
| vactory_decoupled_espace_prive | `vactory_decoupled_espace_prive.module:12,103-109,127-137` | `UserRegistrationPassword::NO_VERIFICATION / VERIFICATION_DEFAULT / VERIFICATION_PASS`, config `registration`, appel `_user_registrationpassword_mail_notify('register_confirmation_with_pass', …)` (fonction interne `_`) |
| vactory_decoupled_espace_prive | `vactory_decoupled_espace_prive.module:190,203` | config exposée au DF signup + cache tag `config:user_registrationpassword.settings` |
| vactory_decoupled | `vactory_decoupled.module:561-563,627-630` | `hook_mail_alter` / mailer : intercepte l'ID `user_registrationpassword_register_confirmation_with_pass`, lit `user_registrationpassword.mail` |
| vactory_decoupled | `vactory_decoupled.module:655` | token `[user:registrationpassword-url]` réécrit vers le front |
| vactory_decoupled | `src/Controller/registrationController.php:82,99` | `registration_ftll_timeout`, `registration_ftll_expire` (route `/api/account/verify/{uid}/{timestamp}/{hash}`) |
| vactory_decoupled | `src/Controller/UserRegistrationConfigController.php:17` | `/api/get-user-registration-config` |
| profil | `vactory_starter_kit.info.yml:79` | installé |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled_espace_prive/tests/src/ExistingSite/RegistrationWithPassTest.php`** — config `registration=with-pass` ; soumettre le webform `vactory_espace_prive_register` via `/_webform` → user créé bloqué, 1 mail capturé (`state system.test_mail_collector`) avec l'ID `user_registrationpassword_register_confirmation_with_pass` et une URL front `/api/account/verify/...` ; GET cette URL → user actif. Attrape renommage de constantes/fonction/ID de mail.
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/RegistrationConfigEndpointTest.php`** — GET `/api/get-user-registration-config` → `user_registration_password` = valeur config.
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/AccountVerifyExpiredTest.php`** — `registration_ftll_expire=TRUE`, timeout 1 s, timestamp ancien → lien refusé.

## Notes
- `_user_registrationpassword_mail_notify` est une fonction « privée » (préfixe `_`) : aucune garantie d'API.

## Historique
- 2020-06-28 `1af659f7` ajout ^1.0.0-alpha3 ; 2021-01-31 `1be86d53` → alpha5
- 2023-07-13 `4b48e654` → ^2.0 (majeure) — #44188
- Aucun incident.
