# drupal/security_review

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.2` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — 7 checks étendent `Drupal\security_review\Check` via `hook_security_review_checks()` (API supprimée au profit de plugins en 3.x, à vérifier) ; formulaire de réglages étendu. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_security_review | `vactory_security_review.info.yml:7` | dépendance |
| vactory_security_review | `vactory_security_review.module:20` | `hook_security_review_checks()` (7 checks) |
| vactory_security_review | `src/Checks/*.php` (BackendSslCertValidation, FrontendSslCertValidation, BackendHttpStrictTransportSecurityHeader, FrontendHttpStrictTransportSecurityHeader, FrontendContentSecurityPolicyHeader, LoginFailureLog, UserPrivilegesChangesLog) | `extends Check`, `CheckResult` |
| vactory_security_review | `src/Form/SettingsForm.php:6,11,17,39` | `extends Drupal\security_review\Form\SettingsForm`, config `security_review.checks` |
| vactory_security_review | `src/Routing/VactorySecurityReviewRouteSubscriber.php:14-16` | remplace le `_form` de `security_review.settings` |
| vactory_security_review | `vactory_security_review.module:35-36,60-61` | lit `security_review.checks:log_user_privileges_change` |
| vactory_decoupled | `src/Controller/DecoupledOauth2Token.php:193,206` | lit `security_review.checks` pour journaliser les logins OAuth |
| vactory_devtools | `vactory_devtools.info.yml:13,26` | dépendance (déclarée 2 fois) |
| vactory_core | `vactory_core.drush.inc:50` | activé en environnement `development` |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `vactory_security_review/tests/src/Kernel/VactoryChecksRegisteredTest.php`** — `security_review.checklist` (ou `security_review_security_review_checks()` + invoke hook) contient les 7 checks Vactory et `run()` retourne un `CheckResult`. Attrape le passage aux plugins.

## Notes
- `DecoupledOauth2Token.php:206` lit la clé `failed_auth_log` alors que le formulaire enregistre `log_failed_auth` (`SettingsForm.php:40`) : le log de succès de login n'est jamais actif (bug constaté).
- `vactory_devtools.info.yml` déclare `security_review` deux fois (:13 et :26).

## Historique
- 2020-06-28 `1af659f7` ajout `1.x-dev` ; 2023-07-12 `3890cd62` → ^2.0 — #44188
- *hors 3.x* 2026-10-26 `db87f1a3` → ^3.1, portage des checks en plugins — `origin/upgrade/d11`
- Aucun incident.

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
