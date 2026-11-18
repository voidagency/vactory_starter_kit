# voidagency/jquery_colorpicker

| | |
|---|---|
| Contrainte composer.json | `5.0.0` (version exacte) |
| Version lockée | `5.0.0` (type `drupal-module`, fork de `drupal/jquery_colorpicker`, release 2026-01-08) |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — module installé par le profil, widget/formatter dans de nombreux form displays et dans le Dynamic Field ; classe du widget référencée en PHP. |
| Couverture actuelle | Aucune |
| Priorité test | P1 (P2 → P1 : fork (b-khouy, voidagency, issue fork D11), épinglage 5.0.0, 4 patches, suppression hors 3.x) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:83` | install list `jquery_colorpicker` |
| vactory_core | `modules/vactory_core/vactory_core.module:20,476-477` | `instanceof Drupal\jquery_colorpicker\Plugin\Field\FieldWidget\JQueryColorpickerWidget` dans `hook_field_widget_single_element_form_alter` → `unset($element['name'])` |
| vactory_dynamic_field | `modules/vactory_dynamic_field/vactory_dynamic_field.info.yml:9` | dépendance module |
| vactory_dynamic_field_* | `…/vactory_dynamic_field_default/widgets/25,37,39/settings.yml`, `…/vactory_dynamic_field_sliders/widgets/3/settings.yml:35`, `…/vactory_dynamic_field_wysiwyg_templates/widgets/47/settings.yml:33` | champ DF `type: jquery_colorpicker` (élément `@FormElement("jquery_colorpicker")`) |
| vactory_paragraphs | `modules/vactory_paragraphs/vactory_paragraphs.info.yml:11` ; `config/install/core.entity_form_display.paragraph.vactory_component.default.yml:121,214`, `…vactory_paragraph_block.default.yml:104,189` | dépendance + widget `jquery_colorpicker` |
| vactory_banner | `modules/vactory_banner/vactory_banner.info.yml:15` ; `config/install/core.entity_form_display.block_content.vactory_banner.default.yml:64` | dépendance + widget |
| vactory_paragraphs_bg_wrapper | `…/vactory_paragraphs_bg_wrapper.info.yml:9` ; form display l.34,43 ; view display l.18 | widget + formatter `jquery_colorpicker_color_display` |
| vactory_annual_report | form/view displays (`…node.vactory_annual_report.default.yml:73,104`, `….card.yml:43,87`, `…taxonomy_term.vactory_ar_thematic.default.yml:28`) | widget + formatter |
| vactory_jquerycolorpicker_css_fix | `…/vactory_jquerycolorpicker_css_fix.info.yml:6` ; `assets/css/colorpicker-fix.css:1,4` | dépendance + CSS ciblant le markup `.jquery_colorpicker > .inner_wrapper` |

Les champs stockés sont de type `colorapi_color_field` (`drupal/colorapi`), ex. `vactory_banner/config/install/field.storage.block_content.field_background_color.yml:13`.

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `modules/vactory_paragraphs/tests/src/ExistingSite/ColorpickerWidgetTest.php`** — module installé par le profil : admin → formulaire d'ajout d'un paragraphe `vactory_component` (ou node avec paragraphs) → élément `.jquery_colorpicker` présent, sous-champ `name` absent (alter vactory_core), soumission `#ff0000` → valeur stockée `ff0000`. Attrape : renommage de `JQueryColorpickerWidget`, changement de structure de l'élément, du markup ciblé par le CSS fix.
- **[Kernel] `modules/vactory_core/tests/src/Kernel/ColorpickerWidgetAlterTest.php`** — variante sans HTTP : construire le form display et vérifier que le plugin widget `jquery_colorpicker` existe et que le formatter `jquery_colorpicker_color_display` rend la couleur.

## Notes
- `vendor/composer/installed.json` local indique `voidagency/jquery_colorpicker 4.0.0` alors que le lock dit `5.0.0` : vendor local désynchronisé (sans impact CI, mais l'analyse locale porte sur la 4.0.0).
- 5.0.0 requiert `drupal/colorapi ^2.1`, `drupal/vendor_stream_wrapper ^2.0.2`, `jaypan/jquery-colorpicker ~1.0.1` (lock).
- Épinglage exact : toute MAJ = modification manuelle de composer.json.

## Historique
- 2020-06-28 `1af659f7` ajout `drupal/jquery_colorpicker` ^1.4.0 ; 2021-01-31 `1be86d53` → ^2.0@RC
- 2022-04-06 `155a4658` patches ajoutés « update background (drupalSettings) », « remove vendor dependencies » — #34779
- 2022-06-10 `b1f4af38` patch « resolve vendor path » modifié ; 2022-07-28 `fb4ee418` montée → ^3.0 — #37372
- 2023-07-13 `c542b354` patch ajouté « Drupal 10 compatibility » — #44188
- 2023-07-17 `44ac302b` **fork** `drupal/jquery_colorpicker` → `b-khouy/jquery_colorpicker` ^4.0 — #44188
- 2024-01-29 `f1c27559` **fork** → `voidagency/jquery_colorpicker` ^4.0, 3 patches gist voidagency
- 2024-04-08 `ff4213e0`, `f4611bd0`, `b9bcd537` patch ajouté puis corrigé 2× « default color » — #50286 « Colorpicker - fix default color »
- 2026-01-08 `39fc2b27` **épinglage** ^4.0 → `5.0.0`, 4 patches retirés, `drupal/colorapi` ^1.1 → ^2.1 + patch gist « Colorapi deprecation fixes »
- *hors 3.x* 2025-12-04 `ad27bf3e`/`1d2de81d` **fork d'issue** `git.drupalcode.org/issue/jquery_colorpicker-3495776` (D11) puis retiré — `origin/update/d11`
- *hors 3.x* 2026-01-07 `dcc26dea` « feat: delete jquery_colorpicker » (+ patches) — branche `origin/feat/62381` ; #62381 « Remplacer jquery colorpicker par un champ HTML color » : journal 2026-01-08 « nouveau module dans la branche feat/62381, on maintien jquery_colorpicker »
- *hors 3.x* 2026-10-28 `fa887595` 5.0.0 → ^6.0 — `origin/upgrade/d11`
- Redmine #48957 « Colorpicker / D10 » (2024-01, fermé).

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).

## Suivi (2026-11-19)
- Contrat vérifié par `ProfileDependencyContractsTest` (PR #918, mergée). Notre fork est en 6.0.0, le profil reste en 5.0.0 : à évaluer (#71222).
