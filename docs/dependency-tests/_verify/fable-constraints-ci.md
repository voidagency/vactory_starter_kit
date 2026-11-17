# Vérification indépendante — Groupe 2 (C01–C06) et Groupe 3 (D01–D02)

Méthode : lecture seule du repo (`3.x`), de `composer.lock`, de `vendor/` (versions installées = versions lockées pour tous les paquets cités, vérifié dans `vendor/composer/installed.json`), de `~/dev/vactory-test-ci`, et de sources publiques (tags GitHub, packagist, packages.drupal.org). Rien n'a été exécuté (pas de phpunit, pas de composer).

| ID | Verdict | Preuve | Commentaire |
|---|---|---|---|
| C01 | **CONFIRMÉ** | `PageExportService.php:297-337`, `PageImportService.php:103-112` ; CHANGELOG phpspreadsheet § 1.23.0 (l.368-420) ; tag 1.22.0 : `getCell(string $coordinate)` | Borne basse réelle = 1.23.0. Avec 1.20–1.22 : `TypeError` à l'export/import de pages. Lock = 1.29.0, donc pas de casse aujourd'hui. |
| C02 | **CONFIRMÉ** (impact à nuancer) | `SimpleOauthAuthenticationProvider.php:301` ; CHANGELOG php-jwt § 6.2.0 (l.96-99) ; tags v6.0.0/v6.1.0 : `parseKeySet(array $jwks)` | Borne basse réelle = 6.2.0. En 6.0/6.1 pas d'erreur fatale : PHP ignore l'argument en trop, seules les clés JWKS sans `alg` lèvent une exception. Lock = 6.10.0. |
| C03 | **CONFIRMÉ** (incomplet) | `composer.json:12` ; drupal/core 11.0→11.4 exige `symfony/finder ^7.1`…`^7.4` (packagist) | La contrainte exclut bien Symfony 7. Ce n'est qu'un blocage D11 parmi au moins cinq autres. |
| C04 | **CONFIRMÉ** (incomplet) | `composer.json:14` et `:17` ; drupal/address 2.0.x exige `commerceguys/addressing ^2.1.1` (packages.drupal.org) | Vrai, mais le premier verrou est `drupal/address: ^1.9` lui-même, qui exclut déjà 2.x. Il faut changer les deux lignes. |
| C05 | **CONFIRMÉ** (aggravé) | `vendor/{drupal,voidagency}/yaml_editor/yaml_editor.info.yml` ; 4 thèmes identiques dans les deux `amptheme` ; `composer.json:166,168,171,172` | Les deux paires sont requises en même temps et s'installent dans le même dossier. Le résultat est un mélange de fichiers, observé sur des projets locaux. |
| C06 | **CONFIRMÉ** pour decoupled_router ; **INFIRMÉ en partie** pour jsonapi_user_resources | `vactory_decoupled_router.routing.yml:1-5` ; `vendor/drupal/jsonapi_user_resources/src/Routing/Routes.php:78,95,112` ; `DecoupledSettingsForm.php:81` | Aucun appel PHP aux deux modules sur `3.x`. Mais jsonapi_user_resources est le seul fournisseur de `/api/user/register` et des routes mot de passe : le retirer supprime ces endpoints. |
| D01 | **CONFIRMÉ** (statique) | `entrypoint.sh:207` ; `phpunit.xml:24-33` ; PHPUnit 9.6 `Command.php:288-289,389`, `BaseTestRunner::getTest()` | Le chemin en argument remplace les testsuites du XML ; les 15 fichiers `*Test.php` du profil sont chargés, dont les 12 sous `tests/Functional/`. Les testsuites du XML ne matchent rien. |
| D02 | **INFIRMÉ** (statique ; reste À EXÉCUTER pour le résultat des tests) | `entrypoint.sh:30-33,88-90` ; `phpunit.xml:15` | La valeur « factice » est la même chaîne que la base, l'utilisateur et le mot de passe réellement créés par l'entrypoint. Le DSN pointe donc sur une base joignable. |

---

## C01 — phpoffice/phpspreadsheet, coordonnées en tableau

**Verdict : CONFIRMÉ.**

Appels dans notre code (tous dans `modules/vactory_page/modules/vactory_page_import/src/Services/`) :

- `PageExportService.php:297,298,306,310,319,320,327,336,337` : `$sheet->setCellValue([col, row], …)`
- `PageExportService.php:300,307` : `$sheet->getStyle([1, $current_row, count($data) + 1, $current_row])`
- `PageImportService.php:103,108,112` : `$sheet->getCell([$col, $row])`

Les deux autres utilisateurs de la lib (`vactory_seo_noindex/src/Form/SettingsForm.php`, `vactory_satisfaction/src/Form/VactorySatisfactionResult.php`) n'utilisent pas la forme tableau. Aucun appel `*ByColumnAndRow`.

Version d'introduction :

- `vendor/phpoffice/phpspreadsheet/CHANGELOG.md:368` (`## 1.23.0 - 2022-04-24`), l.410-420 : « These functions now accept either a cell address string (`'E3'`) or an array with columnId and rowId (`[5, 3]`) or a new `CellAddress` object ».
- Tag public 1.22.0, `src/PhpSpreadsheet/Worksheet/Worksheet.php` : `public function getCell(string $coordinate): Cell` (l.1177), `setCellValue()` délègue à `getCell()` sans validation, aucune référence à `Validations`.
- Tag public 1.23.0, même fichier : `getCell($coordinate)` + `Validations::validateCellAddress()` (l.1224-1226), `getStyle()` + `Validations::validateCellOrCellRange()` (l.1467-1469) ; `Validations.php` gère `is_array` pour l'adresse (l.28) et la plage (l.77).
- URL : `https://raw.githubusercontent.com/PHPOffice/PhpSpreadsheet/{1.22.0,1.23.0}/src/PhpSpreadsheet/Worksheet/Worksheet.php`

Contrainte : `composer.json:133` `"phpoffice/phpspreadsheet": "^1.20"`. Lock et installé : 1.29.0. Aucun autre paquet du lock ne requiert phpspreadsheet, donc rien d'autre ne relève la borne.

**Impact** : sur un projet résolu en 1.20–1.22 (`--prefer-lowest` ou conflit de contraintes), l'export et l'import de pages lèvent un `TypeError`. Correction : `^1.23`.

## C02 — firebase/php-jwt, `parseKeySet($jwks, 'RS256')`

**Verdict : CONFIRMÉ sur la version ; l'impact annoncé est à nuancer.**

- Appel : `modules/vactory_decoupled/src/SimpleOauthAuthenticationProvider.php:301` `JWK::parseKeySet($jwks, 'RS256')`, puis `JWT::decode($jwt, $keys)` l.303. Seul appel du repo.
- `vendor/firebase/php-jwt/CHANGELOG.md:96-99` : « 6.2.0 / 2022-05-14 — Added `$defaultAlg` parameter to `JWT::parseKey` and `JWT::parseKeySet` (#426) ».
- Tags publics : v6.0.0 `parseKeySet(array $jwks)` (l.36), v6.1.0 `parseKeySet(array $jwks): array` (l.36), v6.2.0 `parseKeySet(array $jwks, string $defaultAlg = null): array` (l.38). URL : `https://raw.githubusercontent.com/firebase/php-jwt/{v6.0.0,v6.1.0,v6.2.0}/src/JWK.php`
- Installé (v6.10.0) : `vendor/firebase/php-jwt/src/JWK.php:55`.
- Contrainte : `composer.json:126` `"firebase/php-jwt": "^6.0"`. Lock : v6.10.0. Les autres requérants du lock (`google/apiclient ~6.0`, `google/auth ^6.0`, `stevenmaguire/oauth2-keycloak ^6.0`) ne relèvent pas la borne.

Nuance : en 6.0/6.1, l'appel ne plante pas. PHP ignore silencieusement un argument surnuméraire passé à une fonction utilisateur. Le seul effet est que `parseKey()` lève `UnexpectedValueException('JWK must contain an "alg" parameter')` pour une clé JWKS sans `alg` (v6.1.0 `JWK.php:85-89`). Keycloak publie normalement `alg` sur ses clés de signature.

**Impact** : en 6.0/6.1, l'authentification Keycloak échoue seulement si le JWKS ne porte pas `alg` ; le défaut `RS256` voulu par le code n'est pas appliqué. Correction : `^6.2`.

## C03 — `symfony/finder: ~4.0|~5.0|~6.0` et Drupal 11

**Verdict : CONFIRMÉ, mais l'affirmation est incomplète.**

- `composer.json:12`. `~6.0` signifie `>=6.0 <7.0` : Symfony 7 est exclu.
- drupal/core 11 (packagist, `https://repo.packagist.org/p2/drupal/core.json`) : 11.0.13 → `symfony/finder ^7.1`, 11.1.10 → `^7.2`, 11.2.14 → `^7.3`, 11.3.21 et 11.4.12 → `^7.4`. PHP `>=8.3.0`.
- Lock actuel : drupal/core 10.5.7 exige `symfony/finder ^6.4` ; installé v6.4.27. La ligne du profil est redondante avec le cœur.

Autres blocages D11 relevés au passage (l'affirmation n'en cite qu'un) :

- `drupal/address: ^1.9` : 1.12.0 exige `drupal/core ^9.2 || ^10`.
- `drupal/decoupled_router` locké en 2.0.4 (`^8.8 || ^9 || ^10`) ; D11 à partir de 2.0.5.
- `drupal/jsonapi_user_resources` locké en 1.0.0-beta1 (`^9 || ^10`) ; D11 à partir de beta2.
- `voidagency/amptheme` 3.0.0 exige `drupal/core ^8 || ^9 || ^10`.
- `core_version_requirement: ^9 || ^10` dans nos `.info.yml` (ex. `vactory_decoupled.info.yml`, `vactory_decoupled_router.info.yml`).

**Impact** : lever cette ligne est nécessaire pour Drupal 11, mais ne suffit pas.

## C04 — `commerceguys/addressing: ~1.0` et drupal/address 2.x

**Verdict : CONFIRMÉ, mais ce n'est pas le premier verrou.**

- `composer.json:14` `"commerceguys/addressing": "~1.0"` (`>=1.0 <2.0`).
- drupal/address 2.0.0 à 2.0.6 exigent `commerceguys/addressing ^2.1.1` (`https://packages.drupal.org/files/packages/8/p2/drupal/address.json`). Incompatible avec `~1.0`.
- Mais `composer.json:17` `"drupal/address": "^1.9"` exclut déjà toute version 2.x, indépendamment d'addressing.
- Lock : address 1.12.0 (exige `addressing ^1.4.2`), addressing v1.4.2.

**Impact** : passer à address 2.x demande de modifier les deux contraintes ; ne changer que celle d'addressing ne débloque rien.

## C05 — Doublons `yaml_editor` et `amptheme`

**Verdict : CONFIRMÉ, et plus grave que décrit.**

Noms machine :

- `vendor/drupal/yaml_editor/yaml_editor.info.yml` (1.2.0, `version: '8.x-1.2'`) et `vendor/voidagency/yaml_editor/yaml_editor.info.yml` (2.0.0, mais `version: '8.x-1.1'`, fork de 2020) : même module `yaml_editor`.
- `drupal/amptheme` 3.1.0 et `voidagency/amptheme` 3.0.0 livrent les quatre mêmes thèmes : `amptheme`, `olivero_amp`, `bartik_amp`, `ampsubtheme_example`.

Les deux paquets de chaque paire sont requis simultanément : `composer.json:166` (`voidagency/yaml_editor ^2.0`), `:172` (`drupal/yaml_editor ^1.2`), `:168` (`voidagency/amptheme ^3.0`), `:171` (`drupal/amptheme ^3.1`). Aucun `replace` ni `conflict` côté voidagency (packagist). Le profil ne définit pas d'`installer-paths` ; c'est le projet racine qui le fait.

Effet à l'installation, constaté sur des projets racine locaux qui requièrent ce profil (`installer-paths` : `modules/contrib/{$name}`, `themes/contrib/{$name}`) :

- `vendor/composer/installed.json` y donne le même `install-path` pour les deux paquets : `../../modules/contrib/yaml_editor` et `../../themes/contrib/amptheme`.
- Le dossier obtenu est un mélange : sur trois projets, `yaml_editor.info.yml` vient de drupal.org (8.x-1.3) alors que le `composer.json` présent dans le dossier vient du fork voidagency (le paquet drupal.org n'en livre pas). Sur un quatrième, c'est l'`info.yml` du fork (8.x-1.1) qui a gagné.
- Le gagnant dépend de l'ordre d'installation de composer, donc n'est pas garanti d'un projet ou d'un build à l'autre.

Écart fonctionnel visible : `drupal/yaml_editor` livre `yaml_editor.install`, absent du fork ; le fork livre un `PATCHES.txt`.

**Impact** : la version réellement exécutée de `yaml_editor` et d'`amptheme` est indéterminée, et les patchs du fork peuvent être écrasés sans erreur. Il faut garder un seul paquet par nom machine.

## C06 — decoupled_router et jsonapi_user_resources

**Verdict : CONFIRMÉ pour decoupled_router ; INFIRMÉ en partie pour jsonapi_user_resources.**

Recherche sur `modules/`, `src/`, `config/`, fichiers racine du profil, `composer.json` (PHP, YAML, JS, Twig) : aucune occurrence de `Drupal\decoupled_router\`, `Drupal\jsonapi_user_resources\`, d'un service ou d'une route `decoupled_router.*` / `jsonapi_user_resources.*`, de `PathTranslatorEvent`, ni de patch composer sur ces deux paquets. Aucun front n'est présent dans ce repo.

decoupled_router :

- Dépendances : `modules/vactory_decoupled/vactory_decoupled.info.yml:7`, `modules/vactory_decoupled/modules/vactory_decoupled_router/vactory_decoupled_router.info.yml:7`.
- `/api/router` est bien le nôtre : `vactory_decoupled_router.routing.yml:1-5` → `\Drupal\vactory_decoupled_router\Controller\PathTranslator::translate`, classe qui étend `ControllerBase` (`PathTranslator.php:29`). Seule trace amont : un `@todo` en commentaire (`PathTranslator.php:462`).
- Tant que le module est activé, sa propre route reste exposée : `/router/translate-path` (`vendor/drupal/decoupled_router/decoupled_router.routing.yml`, permission `access content`). C'est un second point d'entrée qui ne passe pas par notre contrôleur.

jsonapi_user_resources :

- Dépendance : `modules/vactory_decoupled/vactory_decoupled.info.yml:11`. Aucun appel PHP sur `3.x`.
- Le module fournit trois routes sous le préfixe JSON:API (`vendor/drupal/jsonapi_user_resources/src/Routing/Routes.php:78,95,112`) : `/user/register`, `/user/password/reset`, `/user/{user}/password/update`. Notre config fixe le préfixe à `api` (`modules/vactory_decoupled/config/install/jsonapi_extras.settings.yml:1`), soit `/api/user/register`.
- Aucune autre route du profil ne définit `/api/user/register`. Or notre formulaire de réglages cite précisément ce chemin comme exemple de route à protéger par reCAPTCHA : `modules/vactory_decoupled/src/Form/DecoupledSettingsForm.php:81` (« E.g: /en/api/user/register »), consommé par `JsonApiReCaptchaSubscriber.php:72-80`.
- La branche non fusionnée `chore/decoupled-registration-role-handling` (commit `d9d61c00`) ajoute un subscriber sur `Drupal\jsonapi_user_resources\Events\RegistrationEvent`. `SECURITY_AUTOMATION_REPORT.md:56` traite ce endpoint comme un vecteur actif.

**Impact** : decoupled_router peut être retiré après contrôle que le front n'appelle pas `/router/translate-path`. Retirer jsonapi_user_resources supprimerait l'inscription et la réinitialisation de mot de passe par JSON:API ; l'usage réel par le front Next.js est à vérifier hors de ce repo.

## D01 — phpunit lancé sur le chemin du profil

**Verdict : CONFIRMÉ par lecture ; aucune exécution observée.**

- Commande : `entrypoint.sh:207` `phpunit --bootstrap=./vendor/weitzman/drupal-test-traits/src/bootstrap.php -c ./phpunit.xml … /var/www/html/profiles/contrib/vactory_starter_kit/`.
- PHPUnit 9.6 (version imposée par drupal/core-dev 10.5), source publique `https://raw.githubusercontent.com/sebastianbergmann/phpunit/9.6/src/…` :
  - `TextUI/Command.php:288-289` : l'argument positionnel devient `arguments['test']`.
  - `TextUI/Command.php:389-391` : les testsuites du XML ne sont lues que si `arguments['test']` est absent.
  - `Runner/BaseTestRunner.php::getTest()` : pour un dossier, tous les fichiers au suffixe `Test.php` sont ajoutés, récursivement.
  - `Framework/TestSuite.php::addTestFile()` : le fichier est inclus directement, la classe est retrouvée par le nom de fichier. Le namespace et l'emplacement (`tests/Functional/` au lieu de `tests/src/`) ne comptent pas.
- La classe de base est autoloadable : `VactoryExistingSiteBase` est dans `modules/vactory_core/tests/src/Functional/` et le bootstrap DTT enregistre `Drupal\Tests\<ext>\Functional\` pour les extensions sous `profiles/` (`https://gitlab.com/weitzman/drupal-test-traits/-/raw/2.x/src/bootstrap.php`).
- Les tests sous `tests/Functional/` étendent `ExistingSiteBase` (DTT) : ils n'ont besoin que de `DTT_BASE_URL` (`phpunit.xml:16`), pas de `SIMPLETEST_BASE_URL`.

Fichiers découverts (15) : 12 sous `tests/Functional/` (vactory_decoupled ×4, sous-modules cross_content, breadcrumb, espace_prive, webform ×2, search, puis vactory_jsonapi_cross_bundles, vactory_single_content_sync_extra), 1 sous `tests/src/Functional/` (`PathTranslatorTest`), 2 sous `tests/src/Kernel/`.

Constat annexe : les quatre `<directory>` de `phpunit.xml:24-33` (`./profiles/contrib/vactory_starter_kit/*/tests/src/…`) ne correspondent à aucun dossier, les modules étant sous `modules/<nom>/` ou plus profond. Sans l'argument de chemin, la CI lancerait zéro test.

**Impact** : les tests hors `tests/src/` tournent bien en CI ; toute la découverte repose sur l'argument de chemin de `entrypoint.sh:207`.

## D02 — `SIMPLETEST_DB` « factice »

**Verdict : INFIRMÉ par lecture. Le résultat réel des deux tests Kernel reste À EXÉCUTER.**

- `phpunit.xml:15` : `SIMPLETEST_DB = mysql://fake_value_for_testing_ignore_me:fake_value_for_testing_ignore_me@127.0.0.1/fake_value_for_testing_ignore_me`.
- `entrypoint.sh:30-33` : `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD` valent cette même chaîne. `entrypoint.sh:88-90` crée la base, l'utilisateur `'…'@'%'` et lui donne tous les droits dessus. `entrypoint.sh:179` installe le site Drupal avec ces mêmes identifiants.
- Le DSN de `phpunit.xml` désigne donc la base réellement créée dans le conteneur, sur `127.0.0.1` (mysqld local, port par défaut). La valeur a l'air d'un placeholder mais elle est cohérente de bout en bout.
- `KernelTestBase::getDatabaseConnectionInfo()` (`vendor/drupal/core/tests/Drupal/KernelTests/KernelTestBase.php:485-507`) : lit `getenv('SIMPLETEST_DB')`, lève une exception seulement si la variable est vide, puis applique un préfixe de tables propre au test. Les tests Kernel cohabitent avec le site installé dans la même base.
- Surcharge par l'environnement : PHPUnit ne pose un `<env>` sans `force` que si la variable n'existe pas déjà (`PhpHandler::handleEnvVariables`, 9.6). Ni `entrypoint.sh` (exports l.125-126, 168-173) ni le `Dockerfile` (l.7-9) ne définissent `SIMPLETEST_DB`. Le `.drone.yml` du profil (24 lignes, une étape `setup`) ne lance pas cette image.

Non vérifiable ici :

- L'image de base `vactory/php-ubi9:8.3.14` ou un `docker run -e` du pipeline réel pourraient définir `SIMPLETEST_DB`.
- La connexion TCP en `127.0.0.1` avec l'authentification par défaut de MySQL 8 n'a pas été testée (le `drush si` passe par `localhost`).
- Le succès des tests eux-mêmes (`NodeNormalizerTest`, `TermNormalizationServiceTest`).

**Impact** : rien dans la config n'empêche les deux tests Kernel de s'installer en CI ; s'ils échouent, la cause est à chercher dans les tests ou leurs modules, à lire dans un log de build.

---

## Constat hors périmètre

`~/dev/vactory-test-ci/Makefile:7` contient un jeton Bitbucket en clair, suivi par git. Le `Dockerfile:9` le recopie aussi en `ENV`, donc dans l'image. Valeur non reproduite ici. À révoquer et à passer en secret de build.
