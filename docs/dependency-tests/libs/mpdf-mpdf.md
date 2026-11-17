# mpdf/mpdf

| | |
|---|---|
| Contrainte composer.json | `^8.0` |
| Version lockée | `v8.2.2` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — génération PDF (certificats de quiz) via options de config et fusion des polices par défaut de mPDF. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_html2pdf | `modules/vactory_html2pdf/src/Services/VactoryHtml2PdfManager.php:10-13` | `use Mpdf\Config\ConfigVariables, FontVariables, Mpdf, MpdfException` |
| vactory_html2pdf | `…/VactoryHtml2PdfManager.php:84-85` | `(new ConfigVariables())->getDefaults()`, `(new FontVariables())->getDefaults()` (constructeur du service) |
| vactory_html2pdf | `…/VactoryHtml2PdfManager.php:125-131` | lecture des clés `fontDir`, `fontdata` ; options `tempDir='/tmp'`, `mode='utf-8'`, fusion `array_merge` avec la config module |
| vactory_html2pdf | `…/VactoryHtml2PdfManager.php:133-135` | `new Mpdf($options)`, `->WriteHTML($html)`, `->Output($file, 'F')` |
| vactory_html2pdf | `modules/vactory_html2pdf/vactory_html2pdf.services.yml:2` | service `vactory_html2pdf.manager` |
| vactory_quiz_certificat | `modules/vactory_quiz/modules/vactory_quiz_certificat/src/Plugin/QueueWorker/GenerateCertificatQueueProcessor.php:131,175` | appelant : `html2Pdf($html_output, $output_file, $mpdf_options)` |
| vactory_quiz_certificat | `…/GenerateCertificatQueueProcessor.php:19,212` | `catch (MpdfException $e)` |
| vactory_quiz | `modules/vactory_quiz/src/Form/QuizForm.php:172,328` | `generateCertificatUsingMpdfQueue()` (alimente la queue) |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `modules/vactory_html2pdf/tests/src/Kernel/Html2PdfManagerTest.php`** — installer config `vactory_html2pdf` ; `vactory_html2pdf.manager->html2Pdf('<h1>Test é</h1>', 'public://html2pdf-test/out.pdf')` → retourne `uri`/`url`, fichier existe, commence par `%PDF`. Cas 2 : config sans `vactory_html2pdf_font_dirs` (état post-install) → doit ne pas lever de `TypeError`. Attrape : changement des clés `fontDir`/`fontdata` de `ConfigVariables`/`FontVariables`, signature `Output()`, options refusées par mPDF 8.x/9.x.
- **[Kernel] `modules/vactory_quiz/modules/vactory_quiz_certificat/tests/src/Kernel/GenerateCertificatQueueTest.php`** — P3 ; item de queue minimal → `processItem()` crée le PDF.

## Notes
- **Bug probable à l'install (à vérifier en exécution)** : `config/install/vactory_html2pdf.settings.yml` fournit `font_directories` et `fonts_data`, mais le service lit `vactory_html2pdf_font_dirs` (l.122) — clé uniquement écrite par `SettingsForm::submitForm` (`src/Form/SettingsForm.php:118`). Tant que le formulaire n'a pas été soumis, `array_merge($fontDirs, NULL)` (l.130) lève une `TypeError` en PHP 8.
- `mkdir($dirname, 755, TRUE)` (l.116) : mode décimal 755 et non octal `0755`.
- `tempDir` codé en dur `/tmp`.
- mPDF 8.2.x supporte PHP 8.3 ; une 9.x éventuelle n'est pas autorisée par `^8.0`.
- `vactory_html2pdf` / `vactory_quiz` non installés par le profil → Kernel plutôt qu'ExistingSite.

## Historique
- 2022-01-07 `e658cf25` ajout ^8.0 — #31225
- 2022-01-17 `26860b66` correctif code « Fix temp dir fo mpdf library » (vactory_quiz_certificat) — pas de changement de paquet
- Aucun incident de version.
