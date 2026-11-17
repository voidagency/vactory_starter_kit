# drupal/twig_tweak

| | |
|---|---|
| Contrainte composer.json | `^3.2` |
| Version lockée | `3.2.1` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — le service central `vactory` (vactory_core) délègue le rendu des blocs/menus/forms/entités à `TwigTweakExtension`, et 145 templates utilisent le filtre `image_style` ; une casse Twig est silencieuse côté API (rendu vide). |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:113` | Installé |
| vactory_core | `vactory_core.info.yml:7` ; `vactory_core.services.yml:38` | Service `vactory` reçoit `@twig_tweak.twig_extension` |
| vactory_core | `src/Vactory.php:22,110,474,527,552,555,575` | `TwigTweakExtension::drupalBlock()` (`renderBlock`), `drupalMenu()` (`renderMenu`), `drupalForm()` / `drupalEntity()` (`renderForm`), `drupalEntity()` (`renderEntity`) — méthodes statiques appelées sur l'instance |
| vactory_core | `src/TwigExtension/TwigExtension.php:74,226-238` | Fonction Twig maison `vactory_render()` → appelle les méthodes ci-dessus (107 appels dans PHP/twig) |
| vactory_frequent_searches | `src/Form/FrequentSearchesAdminForm.php:134-135` | `\Drupal::service('twig_tweak.twig_extension')->drupalBlock('language_dropdown_block:language_interface')` |
| vactory_dynamic_field (132 fichiers), vactory_annual_report (4), vactory_sondage, vactory_academy, vactory_news, vactory_locator, vactory_governance, vactory_banner, vactory_mailchimp_newsletter | `**/template.html.twig`, `templates/*.html.twig` (145 fichiers) | Filtre `\|image_style` (twig_tweak `TwigTweakExtension.php:105`) |
| vactory_news, vactory_governance, vactory_event, vactory_mailchimp_newsletter | `vactory_news/templates/block-filtred-by-taxonomy.html.twig:3`, `vactory_governance/templates/block--governance-member.html.twig:18,22`, `vactory_event/templates/block-filtered-by-taxonomy.html.twig:4`, `vactory_mailchimp_newsletter/templates/block-filtred-by-taxonomy.html.twig:3` | Fonction `drupal_view()` |
| vactory_mediatheque | `templates/card.html.twig:69`, `templates/full.html.twig:67` | Filtre `\|file_url` |
| vactory_appointment | `templates/appointment-create-mail-notifications.html.twig:1` | Fonction `drupal_config()` (template de mail) |
| vactory_dynamic_field | `vactory_dynamic_field.info.yml:8` | Dépendance |

## Tests existants qui l'exercent
- Aucun n'assert le rendu. `NodeNormalizerTest` (Kernel) et les tests DF `vactory_decoupled` ne rendent pas les templates Twig.

## Tests à écrire
- **[Kernel] `vactory_core/tests/src/Kernel/VactoryRenderTest.php`** — modules `system`, `user`, `block`, `twig_tweak`, `vactory_core` (+ deps) : `\Drupal::service('vactory')->renderBlock('system_powered_by_block')` renvoie un render array non vide ; `renderMenu('main')` → tableau ; `renderEntity('user', 1)` → `#theme` présent. Attrape : changement de signature (statique, paramètres) de `TwigTweakExtension`.
- **[Kernel] `vactory_core/tests/src/Kernel/TwigTweakFiltersTest.php`** — `twig->createTemplate("{{ 'public://a.png'|image_style('thumbnail') }}|{{ 'public://a.png'|file_url }}|{{ drupal_config('system.site','name') }}")->render()` → contient `/styles/thumbnail/` et le nom du site. Attrape : filtre/fonction supprimé ou renommé (Twig lève alors une SyntaxError à la compilation de tous les templates DF).
- **[ExistingSite] `vactory_dynamic_field/tests/src/ExistingSite/WidgetTemplatesCompileTest.php`** — pour chaque `widgets/**/template.html.twig` des sous-modules DF activés, `twig->load()` sans exception. Attrape la casse silencieuse Twig sur l'ensemble des 145 templates.

## Notes
- `Vactory.php:555` appelle `drupalEntity($type, $form_id)` dans `renderForm()` (branche entity form) : sémantique à vérifier (twig_tweak a `drupalEntityForm`).

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^2.5`
- 2021-01-31 `1be86d53` montée `^2.5` → `^2.8` — « Update: Add v2.1.0 Factory release changes. »
- 2023-07-13 `4b48e654` montée `^2.8` → `^3.2` — « Fix: #44188 - Drupal 10 compatibility: Update responsive_preview, twig_tweak, user_registratio… »
