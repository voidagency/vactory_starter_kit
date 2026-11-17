# voidagency/cloudinary

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.0.0` (type `drupal-module`, fork de `drupal/cloudinary` ; tire `cloudinary/cloudinary_php ^2` → lock `2.12.0`) |
| Patches | 2 gists distants : « Cloudinary video media library compatibility fix », « Fix symfony 6 compatibility » |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — décorateur de stream wrapper qui étend une classe interne et une méthode protégée du module, + 2 patches distants, + appels directs au SDK Cloudinary. Module optionnel. |
| Couverture actuelle | Aucune |
| Priorité test | P1 (P2 → P1 : fork drupal → b-khouy → voidagency + 2 patches correctifs) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_cloudinary | `modules/vactory_cloudinary/vactory_cloudinary.info.yml:6-7` | dépendances `cloudinary`, `cloudinary:cloudinary_video` |
| vactory_cloudinary | `modules/vactory_cloudinary/vactory_cloudinary.services.yml:21-27` | service `cloudinary_stream_wrapper.override` **décore** `stream_wrapper.cloudinary` |
| vactory_cloudinary | `modules/vactory_cloudinary/src/StreamWrapper/CloudinaryStreamWrapperOverride.php:6,11,31-47` | `extends Drupal\cloudinary_stream_wrapper\StreamWrapper\CloudinaryStreamWrapper`, surcharge `getExternalUrl()`, appelle `loadResource()` (protégée, `vendor/voidagency/cloudinary/modules/cloudinary_stream_wrapper/src/StreamWrapper/CloudinaryStreamWrapper.php:139`) et `Cloudinary\Asset\Media::fromParams()` (SDK) |
| vactory_cloudinary | `modules/vactory_cloudinary/src/Services/VactoryCloudinaryManager.php:5-7,99-109` | config `cloudinary_sdk.settings` (`cloudinary_sdk_cloud_name/api_key/api_secret`), `new Cloudinary\Api\Upload\UploadApi($conf)` |
| vactory_cloudinary | `…/VactoryCloudinaryManager.php:183,196,207` | `cloudinary_stream_wrapper_is_image()` (fonction du module), `UploadApi::explicit()`, `UploadApi::upload()` |
| vactory_cloudinary | `modules/vactory_cloudinary/vactory_cloudinary.module:47-69` ; `src/Commands/VactoryCloudinaryMoveFiles.php:52,71` | drush `move-to-cloudinary` : bascule `public://` ↔ `cloudinary://` |
| vactory_banner | `modules/vactory_banner/vactory_banner.module:206-243` | si `default_scheme === 'cloudinary'` : config `cloudinary_sdk.settings`, `cloudinary_media_library_widget.settings`, library `cloudinary_media_library_widget/widget` |
| vactory_dynamic_field | `modules/vactory_dynamic_field/src/Plugin/Field/FieldWidget/FormWidgetTrait.php:572-574` | widget `cloudinary_media_library_widget` (option `resource_type`) si schéma cloudinary |
| vactory_onboarding_image / _video | `modules/vactory_video_ask/modules/vactory_onboarding_image/config/install/field.storage.media.field_image_onboarding.yml:13`, `…/vactory_onboarding_video/…field_video_onboarding.yml:14` | `uri_scheme: cloudinary` |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `modules/vactory_cloudinary/tests/src/Kernel/CloudinaryStreamWrapperOverrideTest.php`** — activer `cloudinary_sdk`, `cloudinary_stream_wrapper`, `cloudinary`, `cloudinary_video`, `vactory_cloudinary` avec config SDK factice → `\Drupal::service('stream_wrapper_manager')->getViaScheme('cloudinary')` est une `CloudinaryStreamWrapperOverride` ; `getViaUri('cloudinary://c_fill,w_100/v1/foo.jpg')->getExternalUrl()` → URL `https://res.cloudinary.com/<cloud>/image/upload/c_fill,w_100/foo.jpg` (branche regex, sans appel réseau). Attrape : renommage de la classe parent / du service décoré, changement de `loadResource()`, API `Media::fromParams()` du SDK, échec d'application des 2 patches.
- **[Kernel] même classe de test** — `vactory_cloudinary.manager->getPublicId('cloudinary://folder/img.jpg')` → `folder/img` ; `…/doc.pdf` → extension conservée. (Kernel et non Unit : dépend de la fonction globale `cloudinary_stream_wrapper_is_image()` et de la constante `CLOUDINARY_STREAM_WRAPPER_RESOURCE_IMAGE` du module.)

## Notes
- Patches hébergés sur des **gists** (`gist.githubusercontent.com/voidagency/...`) : disparition du gist ou évolution du fork = `composer install` en échec.
- Le fork n'a pas de `require` sur `drupal/core` (seulement `cloudinary/cloudinary_php ^2`).
- Les field storages `uri_scheme: cloudinary` des modules onboarding ne déclarent pas de dépendance au module `cloudinary` — install sans cloudinary : comportement **à vérifier**.
- `vactory_cloudinary` absent de l'install list du profil.

## Historique
- 2021-12-01 `27c381ea` ajout `drupal/cloudinary` ^1.0@alpha + patch « Drupal 9 readiness » — #29579
- 2021-12-06 `bc26063a` retrait `drupal/cloudinary` + patch — #29579
- 2022-08-14 `32bc6377` réajout `drupal/cloudinary` ^2.0@alpha — #37900
- 2022-09-06 `eeda551b` patch ajouté « Cloudinary video media library compatibility fix »
- 2023-07-16 `e70b94cd` patch ajouté « Drupal 10 compatibility » — #44188
- 2023-07-17 `5245d29c` **fork** `drupal/cloudinary` → `b-khouy/cloudinary` ^3.0 (patch D10 retiré) — #44188
- 2023-07-19 `614b9330` patch ajouté « Fix symfony 6 compatibility »
- 2024-01-29 `f1c27559` **fork** `b-khouy/cloudinary` → `voidagency/cloudinary` ^3.0, patches déplacés sur gist voidagency
- *hors 3.x* 2025-11-26 `b947f5bb` retrait / 2025-12-03 `144f53aa` réajout ; 2025-12-08 `1b4dcfe4` patch D11 (PR voidagency/cloudinary#1) ; 2025-12-10 `9f6b9b7d`/`130564f5` patchs symfony 6 et D11 retirés — `origin/update/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
