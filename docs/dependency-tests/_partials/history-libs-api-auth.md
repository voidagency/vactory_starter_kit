# Historique composer.json — lots `libs` et `api-auth`

Source : diff JSON de `composer.json` commit par commit (`git log --no-merges 3.x -- composer.json`, 215 commits ; idem `--all --not 3.x`), `git log -i -E --grep` sur `--all`, Redmine `vactory-4-next` (lecture seule). Commits `ae0e3a00`/`620c057c` (2021-11-11, réécriture complète du fichier) ignorés comme bruit. *hors 3.x* = branches non fusionnées `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381`, `origin/2.x`.

## Paquets utilisés / indirects (avec fiche)

| Paquet | Nb événements | Incidents (downgrade/pin/fork/patch/revert) | Tickets | Priorité avant → après |
|---|---|---|---|---|
| voidagency/jquery_colorpicker | 15 | fork ×3 (b-khouy `44ac302b`, voidagency `f1c27559`, issue D11 `ad27bf3e` hors 3.x), pin 5.0.0 `39fc2b27`, patch ×5 (dont default color `ff4213e0`→`b9bcd537`), suppression `dcc26dea` hors 3.x | #44188, #50286, #62381, #48957, #34779, #37372 | P2 → **P1** |
| voidagency/cloudinary | 10 | retrait/réajout `bc26063a`/`32bc6377`, fork ×2 `5245d29c`, `f1c27559`, patch ×4 (D9, media library, D10, Symfony 6) | #29579, #37900, #44188 | P2 → **P1** |
| drupal/jsonapi_search_api | 9 | patch cache facettes ajouté/retiré 5× (`90415c26`, `505b3787`, `502ff49b`, `2802e861`, `a12eae5c`), pin 1.0.0-rc3 `76ae483f` « version vs patch » | #40239, #40654 | P2 → **P1** |
| drupal/consumers | 2 | pin 1.20.0 `332e0cad` « fix consumers version », pin 1.24.0 `f337dbc2` | — | P2 → **P1** |
| drupal/ctools | 4 | pin ~3.9.0 `7fb17f4d` « Ctools 3.10.x version error » | #44188 | — → **P1** |
| drupal/domain | 2 | patch correctif « no context: resolve null given » (≤ `ae0e3a00`, retiré `ca1115c4`) | #44188 | P3 → **P1** |
| drupal/nocurrent_pass | 4 | patch D9 `1be86d53`, patch correctif « Settings are not applied » (retiré `0648d2b8`, `312057d7`) | — | P3 → **P1** |
| voidagency/shortcode | 4 | patch D10 `f97c0dae`, fork ×2 `a5838923`, `f1c27559` | #44188 | P3 → **P1** |
| voidagency/yaml_editor | 5 | patch D10 `b182d404`, fork ×2 `ab4042cd`, `f1c27559` (+ `drupal/yaml_editor` réajouté `10bbf6f7`) | #44188, #49503 | P3 → **P1** |
| drupal/captcha | 6 | patch `cef2d01a`, fork `bkhouy/captcha` `343123cd`, revert `65d3d511` | #44188, #37867 | P1 → P1 |
| drupal/recaptcha | 4 | fork `bkhouy/recaptcha` `52b8aa35`, revert `65d3d511` | #44188, #43590, #53079 | P1 → P1 |
| drupal/simple_oauth | 6 | patch ×3 dont « Registered claims error after upgrading 5.0.5 → 5.2.0 » `3d863c2c` | #33569, #51590 | P1 → P1 |
| drupal/oauth_login_oauth2 | 6 | patch session `22b235d7` réécrit 4× | #40654, #44188, #71100 | P1 → P1 |
| drupal/jsonapi_extras | 4 | patch Symfony 6 ajouté/retiré `d6862dbb`/`74327c95`, patch sous clé erronée `drupal/jsonapi_extra` `9904f3e2` | #33569, #44188, #54428, #71101 | P1 → P1 |
| drupal/jsonapi_cross_bundles | 2 | patch gist `74528ad0` ; montée ^1.2 pour bug cache #60771 | #60771, #60473 | P1 → P1 |
| drupal/email_registration | 3 | patch auth mail/OAuth2 `9eb3ca69` (retiré `ab59b8f6`) | #44188 | P1 → P1 |
| drupal/password_policy | 3 | patch « field_last_password_reset is unknown » `942502c0` | #44188, #46752 | P1 → P1 |
| stevenmaguire/oauth2-keycloak | 1 | remplacement de `pviojo/oauth2-keycloak` (« unsupported ») `8629f204` | — | P1 → P1 |
| firebase/php-jwt | 1 | — (correctif sécurité code `380687ec`) | — | P1 → P1 |
| drupal/social_auth | 2 | — (correctif code #47474 `e7d331b6`) | #44188, #47474 | P1 → P1 |
| drupal/user_registrationpassword | 3 | — | #44188 | P1 → P1 |
| phpoffice/phpspreadsheet | 1 | — (bump 2.2 dependabot non fusionné) | #30258 | P1 → P1 |
| mailchimp/marketing | 1 | — | #36017, #50668 | P1 → P1 |
| mpdf/mpdf | 1 | — (correctif code temp dir `26860b66`) | #31225 | P2 |
| commerceguys/addressing | 1 | — | — | P2 |
| symfony/finder | 2 | — | #44188 | P2 |
| drupal/jsonapi_include | 1 | — | #47605 | P2 |
| drupal/social_api | 2 | — | — | P2 |
| drupal/flood_control | 1 | — | #42408 | P2 |
| drupal/shield | 1 | — | — | P2 |
| drupal/single_content_sync | 1 | — | #60894 | P2 |
| league/csv | 1 | — (épinglé 9.16.0 dès l'ajout `9cbd99aa`, sans incident) | — | P3 |
| drupal/jsonapi_user_resources | 2 | — (alpha4 épinglé à l'ajout) | #44188 | P3 |
| drupal/decoupled_router | 1 | — | — | P3 |
| drupal/social_auth_facebook | 2 | — | — | P3 |
| drupal/social_auth_google | 2 | — | — | P3 |
| drupal/persistent_login | 2 | — | — | P3 |
| drupal/seckit | 2 | — | — | P3 |
| drupal/security_review | 2 | — | #44188 | P3 |
| drupal/token | 1 | — | — | P3 |
| drupal/views_json_source | 2 | — | — | P3 |
| drupal/default_content | 1 | — | #35444 | P3 |
| drupal/memcache | 2 | — | — | P3 |

## Paquets non utilisés avec historique notable (pas de fiche)

| Paquet | Nb événements | Incidents | Tickets | Priorité |
|---|---|---|---|---|
| masterminds/html5 | 6 | retrait `e0f2dfd8`, réajout `1be86d53`, **downgrade** 2.3.1 → 2.3.0 `48493173` (aligner sur core-recommended), `308ccdd9` ^2.3 | #37372 | — |
| lullabot/amp | 3 | *hors 3.x* pin 2.0.3 `f3896911` « Fix lullabot/amp 2.0.4 compatibility error » (origin/2.x) | #37372 | — |
| voidagency/easy_install | 4 | patch D10 `ec1ae4e6`, fork b-khouy `3ea0e16e` → voidagency `f1c27559`, *hors 3.x* fork d'issue D11 `429b9d5b` | #44188 | — |
| voidagency/mobile_native_share | 4 | patch D10 `e584610a`, fork `651acf89` → `f1c27559`, *hors 3.x* « remove the fork » `fc3ea2db` | #44188 | — |
| voidagency/amptheme | 4 | patch D10 `ca1115c4`, fork `75a58335` → `f1c27559`, `drupal/amptheme` réajouté `10bbf6f7` (doublon) | #44188, #49503 | — |
| drupal/login_security | 2 | retiré du profil `8e3a870f`, `advban` ajouté « for login_security » `6312f50f` (2026-04-20) | — | — |
