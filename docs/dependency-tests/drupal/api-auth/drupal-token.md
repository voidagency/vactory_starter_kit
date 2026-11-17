# drupal/token

| | |
|---|---|
| Contrainte composer.json | `^1.7` |
| Version lockée | `1.13.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Faible — usage limité au thème `token_tree_link` dans des formulaires d'admin ; requis par metatag/pathauto. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_jsonapi_extras | `src/Form/ExposedApisForm.php:247` | `'#theme' => 'token_tree_link'` |
| vactory_dynamic_field | `src/Form/ModalForm.php:311` | idem |
| vactory_notifications | `vactory_notifications.module:484` ; `src/Form/NotificationsForm.php:18` | idem |
| vactory_datalayer_authentication / _content | `src/Plugin/Block/DataLayerBlock.php:84` / `:76` | idem ; `info.yml:6` dépendance `token` |
| vactory_wisepops | `src/Plugin/Block/WisePopsBlock.php:85` ; `vactory_wisepops.info.yml:6` | idem + dépendance |
| vactory_quiz_certificat | `src/Form/QuizCertificatSettingsForm.php:138` | idem |
| profil | `vactory_starter_kit.info.yml` (`- token`) | installé |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_notifications/tests/src/ExistingSite/TokenTreeLinkTest.php`** — admin GET du formulaire de notifications → lien « Browse available tokens » présent. Faible valeur ; à faire seulement si un projet signale une casse.

## Notes
- Le service `token` utilisé partout (`@token`) est celui du **core** (`Drupal\Core\Utility\Token`), pas ce paquet.

## Historique
- 2020-06-28 `1af659f7` ajout ^1.7 (inchangé)
- Aucun incident.
