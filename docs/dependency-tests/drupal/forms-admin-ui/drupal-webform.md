# drupal/webform

| | |
|---|---|
| Contrainte composer.json | `^6.2@beta` |
| Version lockée | `6.2.2` |
| Patches | « Invoke alter hook after webform handlers execution » (gist voidagency `webform-post-handlers-hook.patch`) : ajoute `\Drupal::moduleHandler()->alter('post_handler_submission', $entity)` à la fin de `WebformSubmissionStorage::doPostSave()` |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — 6 handlers custom, un controller de soumission JSON, un normalizer qui dépend du format interne des éléments, un list builder surchargé et un hook qui n'existe que via le patch. |
| Couverture actuelle | Partielle |
| Priorité test | P1 |

## Où on l'utilise
Activé par le profil via `vactory_decoupled_webform` (`vactory_starter_kit.info.yml:181`, `webform` lui-même est commenté l.131). 16 modules custom déclarent `webform` en dépendance (`*.info.yml`), 15 fichiers `webform.webform.*.yml` / `webform.settings.yml` en config.

| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_webform | `src/Webform.php:9-14,154,162,553-559` | Normalizer (service `vactory.webform.normalizer`) : `WebformEntity::load`, `getElementsInitialized()`, `WebformSubmission::create`, plugin élément `->prepare()`, `WebformTermReferenceTrait`, `WebformSubmissionConditionsValidator` |
| vactory_decoupled_webform | `vactory_decoupled_webform.services.yml:5,7` | Services injectés `@webform.token_manager`, `@plugin.manager.webform.element` |
| vactory_decoupled_webform | `src/Controller/WebformController.php:91,101,145,148,183` | POST `/_webform` : `Webform::load`, `WebformSubmissionForm::isOpen()`, `WebformSubmission::create`, `WebformSubmissionForm::submitWebformSubmission()` |
| vactory_decoupled_webform | `src/Plugin/rest/resource/WebformFileUploadResource.php:34` | Ressource REST d'upload (étend `FileUploadResource` core, lit les éléments webform) |
| vactory_decoupled_webform | `src/Plugin/jsonapi/FieldEnhancer/VactoryWebformElements.php:20` | Enhancer JSON:API qui appelle le normalizer |
| vactory_decoupled_webform | `vactory_decoupled_webform.module:19,45,90,107` | `hook_webform_element_configuration_form_alter`, `hook_form_alter`, `hook_webform_access`, champ de base |
| vactory_decoupled_webform | `vactory_decoupled_webform.module` + `src/Webform.php:175` | Hooks maison `decoupled_webform_schema_alter` / `decoupled_webform_data_presubmit_alter` (implémentés dans vactory_business.module:86, vactory_decoupled_espace_prive.module:74, vactory_faceid_auth.module:39) |
| vactory_webform_custom/vactory_webform_remote_post | `src/Plugin/WebformHandler/VactoryRemotePostWebformHandler.php:28-39,496,705-731` | `@WebformHandler("vactory_remote_post")`, copie de `RemotePostWebformHandler` (castRequestValues, `WebformElementInterface`) |
| vactory_webform_custom/vactory_datalayer_handler | `src/Plugin/WebformHandler/DataLayerWebformHandler.php:12-21` | `@WebformHandler("vactory_datalayer_handler")`, testé par id dans `WebformController.php:151` |
| vactory_webform_custom/vactory_webform_mailchimp_handler | `src/Plugin/WebformHandler/MailchimpWebformHandler.php:15-25,97` | `@WebformHandler("vactory_mailchimp_webform_handler")`, `getElementsDecodedAndFlattened()` |
| vactory_webform_typesafe | `src/Plugin/WebformHandler/TypeSafeClassificationHandler.php:14-24,261` ; `vactory_webform_typesafe.module:13` | Handler `vactory_typesafe` (postSave → queue) ; `hook_webform_submission_delete` |
| vactory_event_relance | `src/Plugin/WebformHandler/ReminderWebformHandler.php:13-23,106` | Handler `event_relance_reminder` |
| vactory_webform_anonymize | `vactory_webform_anonymize.module:20-25` ; `src/AnonymizedWebformSubmissionListBuilder.php:13` | Remplace le `list_builder` de `webform_submission` (étend `WebformSubmissionListBuilder`) |
| vactory_webform_storage | `vactory_webform_storage.module:14,22,30,48` | `hook_webform_element_default_properties_alter`, `..._translatable_properties_alter`, `..._configuration_form_alter`, **`hook_post_handler_submission_alter` (n'existe que grâce au patch)** |
| vactory_webform_auto_export | `vactory_webform_auto_export.module:793-795` ; `.info.yml:5-6` | Service `webform_submission.exporter` (`getDefaultExportOptions`) en cron ; dépend de `webform_ui` |
| vactory_webform_satisfaction | `vactory_webform_satisfaction.module:17,36-41,107` | `WebformSubmissionForm::isOpen`, `hook_preprocess_webform_progress_tracker` |
| vactory_job_ads / vactory_tender | `vactory_job_ads.module:209` ; `vactory_tender.module:261` | `hook_webform_submission_presave` |
| vactory_webform_custom | `config/optional/webform.settings.yml:327` | Réglages globaux webform (libs exclues dont `jquery.chosen`) |
| 10 modules | `*/config/install/webform.webform.*.yml` | 14 webforms livrés (job_ads, tender, newsletter, espace_prive register/edit, simulation_credit, social_network…) |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/modules/vactory_decoupled_webform/tests/Functional/WebformNormalizerTest.php` — normalizer : propriétés `validation` des éléments, éléments `webform_term_select/checkboxes`.
- `modules/vactory_decoupled/modules/vactory_decoupled_webform/tests/Functional/WebformParagraphTest.php` — widget paragraphe, POST `/_webform` (succès, email invalide, webform fermé, champ requis manquant).
- Aucun test sur les handlers custom, le list builder, l'export, le hook patché.

## Tests à écrire
- **[Kernel] `vactory_webform_storage/tests/src/Kernel/PostHandlerHookTest.php`** — créer un webform avec un élément portant la propriété custom `#store` (ajoutée par `vactory_webform_storage.module:15`) cochée, sauver une soumission → assert que la valeur est vidée après `postSave`. Attrape : patch non appliqué / non ré-appliquable après MAJ (le hook ne serait plus invoqué, sans erreur).
- **[Kernel] `vactory_webform_custom/tests/src/Kernel/WebformHandlersDiscoveryTest.php`** — `plugin.manager.webform.handler->getDefinitions()` contient `vactory_remote_post`, `vactory_datalayer_handler`, `vactory_mailchimp_webform_handler`, `vactory_typesafe`, `event_relance_reminder` ; instancier chacun avec `defaultConfiguration()` et appeler `getSummary()`. Attrape : signature de `WebformHandlerBase` changée, annotation→attribut.
- **[ExistingSite] `vactory_webform_remote_post/tests/src/ExistingSite/RemotePostHandlerTest.php`** — webform avec handler `vactory_remote_post` pointant vers un mock (middleware Guzzle) → soumission → assert payload casté (nombres, checkboxes). Attrape : changement de `WebformElementInterface`/des valeurs de soumission.
- **[ExistingSite] `vactory_webform_anonymize/tests/src/ExistingSite/SubmissionListBuilderTest.php`** — admin GET `/admin/structure/webform/manage/{id}/results/submissions` → 200 et données anonymisées. Attrape : changement de constructeur de `WebformSubmissionListBuilder`.
- **[Kernel] `vactory_webform_auto_export/tests/src/Kernel/ExporterOptionsTest.php`** — `webform_submission.exporter->getDefaultExportOptions()` contient les clés `delimiter`, `multiple_delimiter`, `range_start`, `range_end` utilisées par le module.
- Déplacer les 2 tests existants dans `tests/src/ExistingSite/` (convention DTT de la CI).

## Notes
- Le `vendor/` local du profil n'a pas `cweagons/composer-patches` : `vendor/drupal/webform/src/WebformSubmissionStorage.php` ne contient pas `post_handler_submission` → patch non appliqué localement ; l'application dépend du projet racine (à vérifier).
- Le patch cible `a/modules/contrib/webform/...` (chemin projet, pas chemin du paquet) : fragile, doit être rebasé à chaque MAJ de `WebformSubmissionStorage::doPostSave()`.
- Contrainte `^6.2@beta` alors que 6.2.2 est stable : peut tirer une 6.3 beta.
- `VactoryRemotePostWebformHandler` duplique ~1000 lignes de `RemotePostWebformHandler` : toute correction upstream (sécurité incluse) n'est pas récupérée.
- Les 2 tests existants sont dans `tests/Functional/` (pas `tests/src/...`) ; la CI lance phpunit sur tout le profil donc ils sont trouvés, mais pas via les testsuites du `phpunit.xml`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^5.12`
- 2021-01-31 `1be86d53` montée `^5.12` → `^6.0` — « Update: Add v2.1.0 Factory release changes. »
- 2021-01-31 `1be86d53` patch ajouté « Invoke alter hook after webform handlers execution » — « Update: Add v2.1.0 Factory release changes. »
- 2023-05-15 `7009653e` patch modifié (URL) « Invoke alter hook after webform handlers execution » — « Update: #40654 - Move existing patches to voidagency account »
- 2023-07-12 `3890cd62` montée `^6.0` → `^6.2@beta` — « Fix: #44188 - Drupal 10 compatibility: Update webform, security_review, stage_file_proxy, chos… »
