# voidagency/shortcode

| | |
|---|---|
| Contrainte composer.json | `^2.1` |
| Version lockée | `2.1.0` (type `drupal-module`, fork de `drupal/shortcode`) |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Faible — un seul plugin, module `vactory_ads` optionnel (non installé par le profil). |
| Couverture actuelle | Aucune |
| Priorité test | P2 (P1 par historique, ramené à P2 : usage indirect seulement — arbitrage Hamza 2026-11-17) — (P3 → P1 : patch D10 puis fork drupal → b-khouy → voidagency) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_ads | `modules/vactory_ads/vactory_ads.info.yml:10` | dépendance `shortcode` |
| vactory_ads | `modules/vactory_ads/src/Plugin/Shortcode/VactoryAdsShortcode.php:7,12-18` | plugin `@Shortcode(id = "ads")` étendant `Drupal\shortcode\Plugin\ShortcodeBase` |
| vactory_ads | `…/VactoryAdsShortcode.php:23-30` | surcharge `process(array $attributes, $text, $langcode)` + `ShortcodeBase::getAttributes()` |
| vactory_ads | `…/VactoryAdsShortcode.php:47` | surcharge `tips($long)` |

Aucune config de format texte activant le filtre shortcode dans le profil (0 occurrence de `shortcode` dans `config/`, `modules/*/config/`).

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `modules/vactory_ads/tests/src/Kernel/AdsShortcodeTest.php`** — créer un `block_content` ; `\Drupal::service('plugin.manager.shortcode')->createInstance('ads')->process(['id' => $id], '')` → markup contenant le contenu du bloc ; `id` invalide → `NULL`. Attrape : changement de signature de `ShortcodeInterface::process()` (`vendor/voidagency/shortcode/src/Plugin/ShortcodeInterface.php:66`) ou de `getAttributes()`.

## Notes
- `renderPlain()` (l.38) est déprécié depuis Drupal 10.3 (remplacé par `renderInIsolation()`) — dette core, pas liée au paquet.

## Historique
- 2023-07-13 `f97c0dae` patch ajouté `drupal/shortcode` « Drupal 10 compatibility » (d.o #3280596) — #44188
- 2023-07-17 `a5838923` **fork** `drupal/shortcode` ^2.0 → `b-khouy/shortcode` ^2.1, patch retiré — #44188
- 2024-01-29 `f1c27559` **fork** → `voidagency/shortcode` ^2.1
- *hors 3.x* 2025-11-10 `bf4234f9` retour `drupal/shortcode` ^2.0 (#61846) ; 2025-12-04 `c88d1dd8` retrait `voidagency/shortcode` — `origin/update/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
