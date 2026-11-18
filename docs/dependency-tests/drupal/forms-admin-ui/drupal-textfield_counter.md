# drupal/textfield_counter

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.3.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Faible — le code réimplémente le contrat JS interne du module (`drupalSettings.textfieldCounter`, classe `textfield-counter-element`) : une MAJ du JS casse le compteur sans erreur PHP. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_core | `vactory_core.info.yml:13` ; `vactory_core.module:485,494-520` | `addCounterToTextElement()` : attache `textfield_counter/counter` + clés `drupalSettings.textfieldCounter.*` (maxlength, counterPosition, textCountStatusMessage, preventSubmit, countHTMLCharacters) |
| vactory_dynamic_field | `vactory_dynamic_field.info.yml:12` ; `src/Plugin/Field/FieldWidget/FormWidgetTrait.php:160-170` | idem dans le widget DF |
| vactory_welcome | `config/install/core.entity_form_display.taxonomy_term.welcome.default.yml:9,20` | Widget `text_textarea_with_counter` |
| vactory_content_package | `vactory_content_package.install:92` | Widget `string_textfield_with_counter` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_core/tests/src/Kernel/TextfieldCounterContractTest.php`** — `library.discovery->getLibraryByName('textfield_counter','counter')` non vide ; `plugin.manager.field.widget` a `string_textfield_with_counter` et `text_textarea_with_counter`. P3. Le contrat `drupalSettings` ne peut être vérifié qu'en ExistingSiteJavascript (optionnel).

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2022-04-18 `deba9550` ajout `^2.0` — « Add: #32290 - Add textfield counter contrib module & text fields counter DF support. »

## Suivi (2026-11-19)
- Le compteur ne fonctionne sur aucun champ texte des Dynamic Fields : `FormWidgetTrait` encode `key` en objet JSON au lieu d'une liste, d'où une `TypeError` en JS (#71223).
