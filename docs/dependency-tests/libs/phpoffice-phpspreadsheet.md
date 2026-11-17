# phpoffice/phpspreadsheet

| | |
|---|---|
| Contrainte composer.json | `^1.20` |
| Version lockée | `1.29.0` (PR Dependabot ouverte vers `1.30.7`) |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — appels directs (lecture + écriture xlsx, coordonnées en tableau) dans l'import/export de pages ; aucune couverture. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_page_import | `modules/vactory_page/modules/vactory_page_import/src/Services/PageImportService.php:70` | `IOFactory::load($filePath)` (auto-détection du format du fichier uploadé) |
| vactory_page_import | `…/PageImportService.php:75-76` | `Spreadsheet::getSheetNames()`, `getSheetByName()` |
| vactory_page_import | `…/PageImportService.php:87-88` | `Worksheet::getHighestRow()`, `getHighestColumn()` |
| vactory_page_import | `…/PageImportService.php:92-94` | `Cell\Coordinate::columnIndexFromString()` + `catch (PhpSpreadsheet\Exception)` |
| vactory_page_import | `…/PageImportService.php:103,108,112` | `Worksheet::getCell([$col, $row])->getValue()` — **coordonnées en tableau** |
| vactory_page_import | `modules/vactory_page/modules/vactory_page_import/src/Services/PageExportService.php:268-270` | `new Spreadsheet()`, `removeSheetByIndex(0)` |
| vactory_page_import | `…/PageExportService.php:284-288` | `createSheet()->setTitle()`, `setActiveSheetIndex()`, `getIndex()`, `getActiveSheet()` |
| vactory_page_import | `…/PageExportService.php:289` | `getStyle(calculateWorksheetDimension())->getAlignment()->setWrapText(TRUE)` |
| vactory_page_import | `…/PageExportService.php:297,298,306,310,319,320,327,336,337` | `Worksheet::setCellValue([$col, $row], $v)` — **coordonnées en tableau** |
| vactory_page_import | `…/PageExportService.php:300,307,328-330` | `getStyle([c1, r1, c2, r2])->applyFromArray()` — **plage en tableau de 4** |
| vactory_page_import | `…/PageExportService.php:349-350` | `getColumnIterator()`, `getColumnDimension($column->getColumnIndex())->setAutoSize(TRUE)` |
| vactory_page_import | `…/PageExportService.php:356,367` | `new Writer\Xlsx($spreadsheet)`, `->save($file_path)` |
| vactory_page_import | `…/PageExportService.php:485` | `Style\Fill::FILL_SOLID` (style `fill.startColor.rgb`) |
| vactory_page_import | `…/src/Form/Import.php:95`, `…/src/Form/Export.php:156` | appelants : `readExcelToArray()` (formulaire d'import), `createExcelFromArray()` (fin de batch d'export) |
| vactory_satisfaction | `modules/vactory_satisfaction/src/Form/VactorySatisfactionResult.php:216-232` | `new Spreadsheet()`, `getActiveSheet()`, `fromArray($row, NULL, 'A1')`, `new Xlsx()`, `save($temp)` puis `readfile` + `exit` (l.243) |
| vactory_seo_noindex | `modules/vactory_seo_noindex/src/Form/SettingsForm.php:80-81` | `IOFactory::load($file->getFileUri())`, `getActiveSheet()->toArray()` (appelé par `submitForm` l.59) |

## Tests existants qui l'exercent
- aucun (0 occurrence de `xlsx`, `PhpSpreadsheet`, `readExcelToArray`, `createExcelFromArray` dans les 15 `*Test.php`).

## Tests à écrire
- **[Kernel] `modules/vactory_page/modules/vactory_page_import/tests/src/Kernel/PageExcelRoundTripTest.php`** — `vactory_page_import.export_helpers->createExcelFromArray(['Page 1' => ['fr' => ['title' => 'A', 'paragraphs' => […]], 'en' => […]]])` (private:// configuré) → fichier `.xlsx` existe ; puis `vactory_page_import.helpers->readExcelToArray(realpath)` → tableau égal à l'entrée (langues en ligne 1, clés en colonne 1, `IGNORE` ignoré). Attrape : suppression/renommage des coordonnées tableau `[col,row]`, `getStyle([4 ints])`, `getColumnIterator`, changement de `getHighestColumn`/`columnIndexFromString`, régression du writer Xlsx.
- **[Unit] `modules/vactory_page/modules/vactory_page_import/tests/src/Unit/PageImportReadSheetTest.php`** — fixture `.xlsx` versionnée (2 feuilles, cellule vide en ligne 1) → `readExcelToArray()` → noms de feuilles et arrêt sur `NULL` corrects. Plus léger que le Kernel si le constructeur du service se mocke facilement (à vérifier).
- **[Kernel] `modules/vactory_seo_noindex/tests/src/Kernel/SeoNoindexExcelTest.php`** — entité `File` sur fixture xlsx (`/fr/foo`, `bar`) → `SettingsForm::getExcelData($fid)` → `['/foo', '/bar']` (en-tête ignoré, préfixe langue retiré). Attrape : changement de `toArray()` / `IOFactory::load`.
- **[Unit] `modules/vactory_satisfaction/tests/src/Unit/SatisfactionExportTest.php`** — P3 ; `exportToExcel()` fait `header()`/`exit` → non testable tel quel ; extraire la construction du `Spreadsheet` dans une méthode puis vérifier `A1`/`A2`. Optionnel.

## Notes
- **Contrainte incohérente avec l'API utilisée** : les coordonnées en tableau `[col,row]` pour `setCellValue`/`getCell` et `[c1,r1,c2,r2]` pour `getStyle` n'existent que depuis **1.23.0** (CHANGELOG `vendor/phpoffice/phpspreadsheet/CHANGELOG.md` section `## 1.23.0 - 2022-04-24`, l.411-418 : « These functions now accept … an array with columnId and rowId (`[5, 3]`) » ; `@deprecated 1.23.0` sur les `*ByColumnAndRow`). `^1.20` autorise 1.20–1.22 où `PageExportService`/`PageImportService` casseraient. Remonter à `^1.23` minimum (ou `^1.29`).
- Passage 1.29.0 → 1.30.7 : reste en 1.x, API utilisée inchangée a priori ; plusieurs advisories de sécurité 2024-2025 (XSS writer HTML, XXE/lecture fichiers readers) corrigées dans les 1.29.x/1.30.x — **à vérifier** via `composer audit`. Pertinent car `IOFactory::load()` est appelé sur des fichiers uploadés (import de pages, seo_noindex) avec détection automatique du format.
- 2.x/3.x : non autorisées par `^1.20` ; suppression des méthodes `*ByColumnAndRow` (non utilisées ici, OK).
- `PageImportService.php:92-95` : si `columnIndexFromString` lève, `$highestColumnIndex` reste indéfini (l.102) — bug latent hors MAJ.
- Modules non installés par le profil (`vactory_starter_kit.info.yml` ne liste ni `vactory_page_import`, ni `vactory_satisfaction`, ni `vactory_seo_noindex`) : préférer Kernel à ExistingSite.

## Historique
- 2021-12-27 `2c88f34f` ajout ^1.20 — #30258
- *hors 3.x* 2024-08-27 `1677e2c7` bump dependabot ^1.20 → ^2.2 (branche `dependabot/composer/3.x/phpoffice/phpspreadsheet-2.2.2`, non fusionnée)
- Aucun incident (downgrade/pin/fork/patch/revert).

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
