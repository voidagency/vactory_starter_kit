# drupal/domain

| | |
|---|---|
| Contrainte composer.json | `^2.0@beta` |
| Version lockée | `2.0.0-beta1` |
| Patches | aucun |
| Statut | Utilisé (optionnel, protégé par `moduleExists`) |
| Risque à la MAJ | Faible — module non installé par le profil ; code gardé par `moduleExists('domain')` / `moduleExists('domain_access')`. |
| Couverture actuelle | Aucune |
| Priorité test | P2 (P1 par historique, ramené à P2 : usage indirect seulement — arbitrage Hamza 2026-11-17) — (P3 → P1 : patch correctif « Domain no context : resolve null given ») |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_menu_breadcrumb | `src/MenuBasedBreadcrumbBuilder.php:25,597-603` | `domain.negotiator->getActiveDomain()`, `DomainInterface` (si `domain_access`) |
| vactory_menu_breadcrumb | `src/Form/SettingsForm.php:140` | option `include_domain` si `domain_access` |
| vactory_extended_seo | `src/VactoryExtendedSeoHelper.php:8,66-67,92-96` | config `domain.settings`, storages `domain` / `domain_alias`, `DomainAlias::getPattern()` |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[Kernel] `vactory_extended_seo/tests/src/Kernel/ExtendedSeoDomainTest.php`** — avec `domain`+`domain_alias` activés, 1 domaine + 1 alias → l'helper renvoie l'hôte attendu ; sans le module → pas d'erreur. À écrire seulement si un projet active `domain`.

## Notes
- Version beta figée par `^2.0@beta`.

## Historique
- 2021-11-11 `ae0e3a00` patch « Domain no context : resolve null given » (d.o #3201514) présent (`1.0-beta5` épinglé)
- 2023-07-13 `ca1115c4` montée `1.0-beta5` → ^2.0@beta, patch retiré — #44188
