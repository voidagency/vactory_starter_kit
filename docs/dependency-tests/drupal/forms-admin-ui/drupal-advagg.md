# drupal/advagg

| | |
|---|---|
| Contrainte composer.json | `^6.0@alpha` |
| Version lockée | `6.0.0-alpha1` |
| Patches | « Remove version comments from minified files » (gist voidagency `advagg-remove-version-comment-from-minified-js.patch`) |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Moyen — agrège/minifie CSS/JS de toutes les pages Drupal ; version alpha + patch maison. |
| Couverture actuelle | Aucune |
| Priorité test | P1 — historique : patch correctif « Support PHP 8.1 » (`894e4058`) ; #44206 « Fix Advagg sur le profile » (rejeté, non reproduit) (avant : P3) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:74-78` | Installe `advagg`, `advagg_mod`, `advagg_js_minify` (×2), `advagg_css_minify` |
| profil | `config/install/advagg.settings.yml`, `advagg_mod.settings.yml`, `advagg_js_minify.settings.yml`, `advagg_css_minify.settings.yml` | Réglages |

0 occurrence dans le code custom.

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_core/tests/src/ExistingSite/AssetsAggregationTest.php`** — GET `/user/login` en anonyme → 200 ; extraire les URLs `<script src>` / `<link rel=stylesheet>` et les requêter → 200 et non vides ; aucun commentaire de version dans les JS minifiés (vérifie le patch). P3.

## Notes
- Patch non appliqué dans le `vendor/` local (pas de `cweagons/composer-patches`) — à vérifier côté projet racine.
- `advagg_js_minify` listé 2 fois dans `install:`.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^4.0.0`
- 2021-01-31 `1be86d53` montée `^4.0.0` → `^4.1.0` — « Update: Add v2.1.0 Factory release changes. »
- 2021-01-31 `1be86d53` patch ajouté « Remove version comments from minified files. » — « Update: Add v2.1.0 Factory release changes. »
- 2023-01-06 `5c0d2b07` montée `^4.1.0` → `^5.0` — « Update: #40522 - replace deprecated code »
- 2023-01-27 `894e4058` patch ajouté « Support PHP 8.1 && remove deprecated class » — « Fix: Add php 8 compatibility to contrib modules »
- 2023-05-15 `7009653e` patch modifié (URL) « Remove version comments from minified files. » — « Update: #40654 - Move existing patches to voidagency account »
- 2023-07-13 `ab59b8f6` montée `^5.0` → `^6.0@alpha` — « Fix: #44188 - Drupal 10 compatibility: Update addtoany, advagg, better_exposed_filters, block_… »
- 2023-07-13 `ab59b8f6` patch retiré « Support PHP 8.1 && remove deprecated class » — « Fix: #44188 - Drupal 10 compatibility: Update addtoany, advagg, better_exposed_filters, block_… »
