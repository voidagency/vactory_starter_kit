# drupal/password_policy

| | |
|---|---|
| Contrainte composer.json | `^4.0` |
| Version lockée | `4.0.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — notre contrainte de validation type-hint la classe concrète `PasswordPolicyValidator` (pas l'interface) et appelle `validatePassword()` ; config de politique installée par le profil. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_espace_prive | `src/Plugin/Validation/Constraint/VactoryPasswordPolicyConstraintValidator.php:6,22,27,36,56` | service `password_policy.validator`, `validatePassword($pass, $account)` |
| vactory_decoupled_espace_prive | `vactory_decoupled_espace_prive.module:213` | contrainte `VactoryPasswordPolicyConstraint` ajoutée au champ `pass` (`hook_entity_base_field_info_alter`) |
| profil | `config/install/password_policy.password_policy.vactory_password_policy.yml:13,17,21` | 3 contraintes `password_policy_character_constraint` |
| profil | `config/install/password_policy.settings.yml` | réglages |
| profil | `vactory_starter_kit.info.yml:154-156` | `password_policy`, `password_policy_length`, `password_policy_characters` |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled_espace_prive/tests/src/ExistingSite/PasswordPolicyConstraintTest.php`** — créer un user et `$user->setPassword('abc')->validate()` → violation sur `pass` ; mot de passe conforme à `vactory_password_policy` → 0 violation. Attrape changement de signature/retour (`PasswordPolicyValidationReport`) ou du service.

## Notes
- Validator type-hinté sur la classe concrète (`VactoryPasswordPolicyConstraintValidator.php:22,27`) : casse si le service est décoré ou change de classe ; préférer `PasswordPolicyValidatorInterface` (existe en 4.0.0).

## Historique
- 2020-06-28 `1af659f7` ajout ^3.0.0-alpha5 ; 2021-01-31 `1be86d53` → ^3.0@beta
- 2021-02-01 `942502c0` patch ajouté « Fix Field field_last_password_reset is unknown » (échec de config import, d.o #2771129)
- 2023-07-13 `4b48e654` → ^4.0 (majeure), patch absent depuis — #44188
- *hors 3.x* 2023-11-16 `85a5c1a2` → ^4.0 sur `origin/2.x` — #46752

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
