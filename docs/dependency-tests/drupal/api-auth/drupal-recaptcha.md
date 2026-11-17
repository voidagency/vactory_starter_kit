# drupal/recaptcha

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.2.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — on instancie `Drupal\recaptcha\ReCaptcha\RequestMethod\Drupal8Post` (classe interne) pour protéger les POST JSON:API (node/comment) et des routes configurables. |
| Couverture actuelle | Partielle |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `src/EventSubscriber/JsonApiReCaptchaSubscriber.php:17,108-124` | `new ReCaptcha($secret, new Drupal8Post(httpClient))`, config `recaptcha.settings:secret_key` ; POST sur routes listées dans `vactory_decoupled.settings:routes` → 403 JSON:API si invalide |
| vactory_decoupled | `src/Plugin/Validation/Constraint/RecaptchaValidator.php:9,92-103` | idem, contrainte `jsonapi_recaptcha` |
| vactory_decoupled | `vactory_decoupled.module:409-410` | `hook_entity_type_alter` : contrainte `jsonapi_recaptcha` sur `comment` et `node` |
| vactory_decoupled | `vactory_decoupled.services.yml:52-56` | service `vactory_decoupled.jsonapi_recaptcha_subscriber` |
| 4 webforms | `webform.webform.vactory_tender_form.yml`, `newsletter.yml`, `vactory_espace_prive_register.yml`, `vactory_webform_satisfaction.yml` | `#captcha_type: recaptcha/reCAPTCHA` |
| profil | `vactory_starter_kit.info.yml:126` | installé |

## Tests existants qui l'exercent
- `WebformNormalizerTest.php:285` — vérifie seulement `captcha_type = recaptcha/reCAPTCHA` dans la sortie normalisée.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/JsonApiRecaptchaTest.php`** — `recaptcha.settings:secret_key` = clé de test Google (`6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`, renvoie toujours succès) ; POST JSON:API `comment` sans `g-recaptcha-response` → 422 violation `jsonapi_recaptcha` ; avec token → 201. Ajouter un chemin dans `vactory_decoupled.settings:routes` → POST sans token → 403. Attrape un déplacement/renommage de `Drupal8Post`.
- **[Unit] `vactory_decoupled/tests/src/Unit/RecaptchaClassesTest.php`** — `class_exists(Drupal8Post::class)` et `class_exists(\ReCaptcha\ReCaptcha::class)` (lib `google/recaptcha`).

## Notes
- La lib `ReCaptcha\ReCaptcha` vient de `google/recaptcha` (dépendance transitive de recaptcha, non déclarée dans notre composer.json).
- `skip CAPTCHA` (permission du module captcha) contourne la contrainte (`RecaptchaValidator.php:58`).

## Historique
- 2020-06-28 `1af659f7` ajout ^2.5 ; 2021-01-31 `1be86d53` → ^3.0
- 2022-09-15 `176bb879` correctif code : mauvais usage de `Drupal8Post` dans le validateur décuplé
- 2023-07-18 `52b8aa35` **fork** `drupal/recaptcha` → `bkhouy/recaptcha` ^3.0 — #44188
- 2023-07-20 `65d3d511` **revert** → `drupal/recaptcha` ^3.0 — #44188
- Correctifs code liés : `1713f14a` #43590 (Math → V4), `7ae631db` #53079 « recaptcha validator », `e8852d18` 2025-12-30 « bypass recaptcha » tests.
