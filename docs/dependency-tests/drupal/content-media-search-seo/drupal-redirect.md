# drupal/redirect

| | |
|---|---|
| Contrainte composer.json | `^1.6` |
| Version lockée | `1.9.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — le routeur décuplé résout les redirections éditoriales via `RedirectRepository::findMatchingRedirect()` avant tout matching (SEO 301 du front). |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_router | `modules/vactory_decoupled/modules/vactory_decoupled_router/src/Controller/PathTranslator.php:157` | `findMatchingRedirect($path, [], $langcode)`, `getStatusCode()`, `getRedirectUrl()` ; injection `redirect.repository` (l.127) |
| vactory_decoupled_router | `modules/vactory_decoupled/modules/vactory_decoupled_router/vactory_decoupled_router.info.yml:8` | Dépendance `redirect:redirect` |
| profil | `config/install/redirect.settings.yml` | Settings |
| info.yml | `vactory_starter_kit.info.yml:134` | Profil ; vactory_core.info.yml:10 |

## Tests existants qui l'exercent
- `PathTranslatorTest.php` — `testNodePathPatternRedirect` (l.267) teste la redirection `/node/N` → alias, **pas** une entité `redirect`.

## Tests à écrire
- **[ExistingSite] `PathTranslatorTest::testRedirectEntity`** (dans `modules/vactory_decoupled/modules/vactory_decoupled_router/tests/src/Functional/`) — `Redirect::create(['redirect_source'=>'old-page', 'redirect_redirect'=>'internal:/node/N', 'status_code'=>301, 'language'=>'fr'])` → appel du translator avec `path=/old-page` → `status=301`, `redirect[0].to` = alias du node. Variante langue `und`. Attrape un changement de signature/normalisation de `findMatchingRedirect()`.

## Notes
`redirect.destination` utilisé dans `vactory_taxonomy_results` est un service **core**, pas du module.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^1.6`
- — aucun changement contrainte inchangée sur `3.x`
