# drupal/mailchimp

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.2.2` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — appels aux fonctions procédurales `mailchimp_*` (API publique non typée) depuis une route d'inscription publique. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
Modules optionnels (non installés par le profil).

| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_mailchimp | `vactory_mailchimp.info.yml:6-8` | Dépend de `mailchimp`, `mailchimp_campaign`, `mailchimp_signup` |
| vactory_mailchimp | `src/Controller/ApiController.php:102,114` | Route POST publique `/_mailchimp` : `mailchimp_is_subscribed()`, `mailchimp_subscribe()` |
| vactory_mailchimp | `src/Element/MailchimpDecoupled.php:70` | `mailchimp_get_api_object('MailchimpLists')` |
| vactory_mailchimp | `vactory_mailchimp.module:24-25` | form alter du bloc `mailchimp_signup_subscribe_block_…`, `mailchimp_signup_load()` |
| vactory_mailchimp | `config/optional/mailchimp.settings.yml:4` | `api_classname: Mailchimp\Mailchimp` |
| vactory_mailchimp_newsletter | `vactory_mailchimp_newsletter.module:82,87` ; `src/Services/CampaignsService.php:11,109` | `mailchimp_get_api_object('MailchimpCampaigns'/'MailchimpLists')`, classes `Mailchimp\MailchimpCampaigns` |
| vactory_mailchimp_newsletter | `vactory_mailchimp_newsletter.info.yml:8-10` ; `config/install/mailchimp.settings.yml` | Dépend de `mailchimp`, `mailchimp_campaign`, `mailchimp_lists` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_mailchimp/tests/src/Kernel/MailchimpApiFunctionsTest.php`** — activer `mailchimp`, `mailchimp_signup`, `vactory_mailchimp` ; assert `function_exists()` pour `mailchimp_subscribe`, `mailchimp_is_subscribed`, `mailchimp_get_api_object`, `mailchimp_signup_load` et que `mailchimp_get_api_object('MailchimpLists')` renvoie une instance `Mailchimp\MailchimpLists` en activant le module de test du paquet `mailchimp_test` (override de config fourni par `vendor/drupal/mailchimp/tests/modules/mailchimp_test`). Attrape : renommage/suppression des fonctions procédurales.
- **[ExistingSite] `vactory_mailchimp/tests/src/ExistingSite/SubscribeEndpointTest.php`** — POST `/_mailchimp` avec client de test → 200 et JSON attendu. P2.

## Notes
- `vactory_webform_mailchimp_handler` n'utilise pas ce paquet mais `MailchimpMarketing\ApiClient` (lib `mailchimp/marketing`, autre lot).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.11.0`
- 2021-01-31 `1be86d53` montée `^1.11.0` → `^2.0` — « Update: Add v2.1.0 Factory release changes. »
- 2024-04-03 `3090bc91` bug côté code #50668 « Mailchimp handler error » corrigé dans `MailchimpWebformHandler.php`
