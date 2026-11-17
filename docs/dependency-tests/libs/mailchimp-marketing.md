# mailchimp/marketing

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.0.80` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — SDK généré (OpenAPI) appelé avec des arguments positionnels ; inscription newsletter via handler webform. |
| Couverture actuelle | Aucune |
| Priorité test | P1 (soumission de formulaire public → API tierce) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_webform_mailchimp_handler | `modules/vactory_webform_custom/modules/vactory_webform_mailchimp_handler/src/Plugin/WebformHandler/MailchimpWebformHandler.php:10` | `use MailchimpMarketing;` |
| idem | `…/MailchimpWebformHandler.php:166-176` | `new ApiClient()`, `setConfig(['apiKey','server'])`, `lists->getListInterestCategories($list_id)`, `lists->listInterestCategoryInterests($list_id, $cat_id)` |
| idem | `…/MailchimpWebformHandler.php:233-268` | `submitForm()` : `lists->addListMember($list_id, ['email_address','status'=>'subscribed','merge_fields','interests'])` |
| idem | `…/MailchimpWebformHandler.php:284-292` | `lists->getAllLists(NULL, NULL, 900)` (positionnel) |
| idem | `…/MailchimpWebformHandler.php:315-322` | `lists->getListMergeFields($list_id)` |
| idem | `…/MailchimpWebformHandler.php:190,270,294,324` | `catch (GuzzleHttp\Exception\RequestException)` |
| vactory_mailchimp_newsletter | `modules/vactory_newsletter/modules/vactory_mailchimp_newsletter/vactory_mailchimp_newsletter.module:15,192-199` | `use MailchimpMarketing\ApiClient` ; `get_lists()` → `lists->getAllLists(NULL, NULL, 50, 0)` |
| vactory_mailchimp_newsletter | `…/config/install/webform.webform.newsletter.yml:231-233` | handler `vactory_mailchimp_webform_handler` configuré sur le webform `newsletter` |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Unit] `modules/vactory_webform_custom/modules/vactory_webform_mailchimp_handler/tests/src/Unit/MailchimpWebformHandlerTest.php`** — nécessite d'extraire `new ApiClient()` dans une méthode protégée `createClient()` (instanciation inline aujourd'hui → non mockable). Submission `['email' => 'a@b.c', 'fname' => 'X']` + config de mapping → assert `addListMember('LIST', ['email_address' => 'a@b.c', 'status' => 'subscribed', 'merge_fields' => ['FNAME' => 'X']])`. Cas 2 : `addListMember` lève `ConnectException` → la soumission ne doit pas planter. Attrape : renommage/réordonnancement des paramètres du SDK, changement de `setConfig`.
- **[Unit] même fichier** — `getLists()` : réponse `{lists:[{id,name}]}` mockée → options `id => name`.

## Notes
- Arguments **positionnels** sur des signatures générées (`getAllLists($fields, $exclude_fields, $count, $offset, …)` — `vendor/mailchimp/marketing/lib/Api/ListsApi.php:1701`) : tout réordonnancement casse silencieusement.
- `getListMergeFields`/`getListInterestCategories` appelés sans `count` → défaut `'10'` (ListsApi.php:3095, 5488) : au-delà de 10 champs/catégories, les données sont tronquées (bug hors MAJ).
- `catch (RequestException)` ne capte pas `GuzzleHttp\Exception\ConnectException` (Guzzle 7 : `extends TransferException`, `vendor/guzzlehttp/guzzle/src/Exception/ConnectException.php:13`) → panne réseau Mailchimp = exception non gérée dans `submitForm`.
- Deux SDK Mailchimp cohabitent : `mailchimp/marketing` (ce paquet) et `thinkshout/mailchimp-api-php 3.0.0` (via `drupal/mailchimp`, utilisé par `mailchimp_get_api_object()` dans `vactory_mailchimp`, `vactory_mailchimp_newsletter`).
- Dernière release 3.0.80 ; dépôt possiblement peu maintenu — **à vérifier**.

## Historique
- 2022-06-15 `b9f7e033` ajout ^3.0 — #36017
- Redmine #50668 « Mailchimp handler error » (bug, 2024-04, fermé, sans détail) — pas de changement de paquet associé.
- Aucun incident de version.
