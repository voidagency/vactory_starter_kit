# drupal/captcha

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.5` |
| Patches | « Fix captcha error message translation » (gist voidagency) |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — on écrit directement dans la table `captcha_sessions` (schéma interne) pour le captcha Math headless et on relit `solution` en SQL ; constante `CaptchaConstants` ; permission `skip CAPTCHA`. |
| Couverture actuelle | Partielle |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_webform | `src/Controller/WebformController.php:5,279-300` | `INSERT` dans `captcha_sessions` (`uid, sid, ip_address, timestamp, form_id, solution, status, attempts, token`) + `CaptchaConstants::CAPTCHA_STATUS_UNSOLVED` ; route `/_webform/captcha_math/{webform_id}` (`vactory_decoupled_webform.routing.yml:12-15`) |
| vactory_decoupled_webform | `vactory_decoupled_webform.module:51-52,62-83` | `hook_form_alter` : validateur `validate_captcha_math` qui supprime l'erreur `captcha_response` et relit `captcha_sessions.solution` par `csid` |
| vactory_decoupled_webform | `src/Webform.php:420,547-549` | normalisation de l'élément webform `captcha` + `captcha_type` |
| vactory_decoupled | `src/Plugin/Validation/Constraint/RecaptchaValidator.php:58` | permission `skip CAPTCHA` (fournie par captcha) |
| vactory_decoupled | `config/install/jsonapi_extras.jsonapi_resource_config.captcha_point--captcha_point.yml:5` | ressource JSON:API `captcha_point` |
| vactory_job_ads | `config/install/captcha.captcha_point.webform_submission_vactory_job_application_add_form.yml:8` | captcha point `captcha/Math` |
| 4 webforms | `vactory_tender`, `vactory_mailchimp_newsletter`, `vactory_decoupled_espace_prive` (register), `vactory_webform_satisfaction` (`config/install/webform.webform.*.yml`) | éléments `captcha` |
| profil | `config/install/shield.settings.yml:12` | chemin `/_webform/captcha_math/*` exclu de shield |
| profil | `vactory_starter_kit.info.yml:125` | installé |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/modules/vactory_decoupled_webform/tests/Functional/WebformNormalizerTest.php:285` (`testWebformCaptchaElements`) — normalisation des éléments `captcha` (`captcha/Math`, `recaptcha/reCAPTCHA`) ; **ne teste pas** la génération ni la validation.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled_webform/tests/src/ExistingSite/CaptchaMathFlowTest.php`** — GET `/_webform/captcha_math/{id}` → `csid,num1,num2` ; ligne présente dans `captcha_sessions` ; POST `/_webform` avec `captcha_sid=csid` et `captcha_response=num1+num2` → 200 ; mauvaise réponse → erreur `captcha_response`. Attrape tout changement du schéma `captcha_sessions` ou de la constante.
- **[Kernel] `vactory_decoupled_webform/tests/src/Kernel/CaptchaSessionsSchemaTest.php`** — `enableModules(['captcha'])`, `installSchema('captcha', ['captcha_sessions'])`, vérifier que les colonnes utilisées existent (`$schema->fieldExists`). Garde-fou rapide sans HTTP.

## Notes
- Contournement de l'API captcha par SQL direct : couplage au schéma interne, non couvert par les garanties de version.

## Historique
- 2020-06-28 `1af659f7` ajout ^1.0 ; 2021-01-31 `1be86d53` → ^1.1
- 2022-06-13 `cef2d01a` patch ajouté « Fix captcha error message translation »
- 2023-07-14 `343123cd` **fork** `drupal/captcha` → `bkhouy/captcha` ^2.0 (patch suivi) — #44188
- 2023-07-20 `65d3d511` **revert** « Rollback captcha and recaptcha to drupal original package » → `drupal/captcha` ^2.0 + patch — #44188
- *hors 3.x* 2023-09-06 `99719ae4` patch perdu puis `8e8e6232` « Add missed captcha contrib module patch » (branche 2.x)
- Redmine #37867 « Webform / Problème de captcha » (soumission bloquée avec recaptcha, PP et chaabi, 2022).

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).

## Suivi (2026-11-19)
- Le patch « error message translation » (gist tronqué) ne s'applique plus sur 2.0.12 : `composer update` échouait sur tout projet. Captcha est fixé à `2.0.11` (PR #922, mergée). Le patch est à refaire (#71167).
