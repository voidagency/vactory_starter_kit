# drupal/colorapi

| | |
|---|---|
| Contrainte composer.json | `^2.1` |
| Version lockée | `2.1.0` |
| Patches | « Colorapi deprecation fixes » (gist ismail-void `colorapi-deprecation-fixes.patch`) |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — type de champ stocké (`colorapi_color_field`) sur paragraphes et blocs installés par le profil ; patch maison de compat D10. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patchs correctifs (« Fix undefined index name » `620c057c`, « deprecation fixes » `39fc2b27`), montée couplée au fork jquery_colorpicker #62381 (avant : P2) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:65` | Installé |
| vactory_field | `vactory_field.info.yml:22` ; `config/install/field.storage.paragraph.field_background_color.yml:10-12` | Storage `type: colorapi_color_field`, `module: colorapi` |
| vactory_paragraphs | `config/install/field.field.paragraph.{vactory_paragraph_tab,views_reference,vactory_component}.field_background_color.yml:22` | Instances du champ |
| vactory_banner, vactory_annual_report, vactory_paragraphs_bg_wrapper | `vactory_banner/config/install/field.field.block_content.vactory_banner.field_background_color.yml:25`, `core.entity_view_display.block_content.vactory_banner.{full,default}.yml:36` | Champ + formatters `colorapi_color_display`, `colorapi_raw_hex_display` |
| vactory_content_package | `src/Services/ContentPackageManager.php:238,569` | Traitement spécifique export/import pour `colorapi_color_field` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_field/tests/src/Kernel/ColorFieldTest.php`** — modules `paragraphs`, `colorapi`, `vactory_field` ; installer la storage, créer un paragraphe avec `field_background_color = ['color' => '#ff0000']`, save/reload → valeur intacte ; rendre avec formatter `colorapi_raw_hex_display` → `#ff0000`. Attrape : changement de colonnes du field type, régression du patch.
- **[Kernel] `vactory_content_package/tests/src/Kernel/ColorFieldPackageTest.php`** — export/import d'une entité portant le champ → même valeur. P2.

## Notes
- Patch non appliqué dans le `vendor/` local (pas de `cweagons/composer-patches`) ; contenu du patch (gist externe) non vérifié — à vérifier qu'il est toujours nécessaire en 2.1.0.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2021-01-31 `1be86d53` ajout `^1.1` — « Update: Add v2.1.0 Factory release changes. »
- 2021-01-31 `1be86d53` patch ajouté « Enable colorapi field type by default » — « Update: Add v2.1.0 Factory release changes. »
- 2021-11-11 `ae0e3a00` patch ajouté « Color api : Fix undefined index name. » — « Update: Update composer json. » + `620c057c` « Fix composer json file typo » (JSON invalide entre les deux)
- 2022-07-13 `af2b4161` patch retiré « Color api : Fix undefined index name. » — « Update: Colorapi contrib module remove applied patch. »
- 2023-05-15 `7009653e` patch modifié (URL) « Enable colorapi field type by default » — « Update: #40654 - Move existing patches to voidagency account »
- 2026-01-08 `39fc2b27` montée `^1.1` → `^2.1` — « fix: update jquery_colorpicker & colorapi & update patches »
- 2026-01-08 `39fc2b27` patch ajouté « Colorapi deprecation fixes » — « fix: update jquery_colorpicker & colorapi & update patches »
- 2026-01-08 `39fc2b27` patch retiré « Enable colorapi field type by default » — « fix: update jquery_colorpicker & colorapi & update patches »
- 2026-01-08 `39fc2b27` montée couplée à jquery_colorpicker `^1.1` → `^2.1` en même temps que le passage de `jquery_colorpicker` à `voidagency/jquery_colorpicker 5.0.0` (#62381 « on maintient jquery_colorpicker »)
