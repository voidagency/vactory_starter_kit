# drupal/flag

| | |
|---|---|
| Contrainte composer.json | `^5.0` |
| Version lockée | `5.0.3` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — API JSON de flag/unflag (`/api/flagging`, académie), events consommés par le système de points, requêtes sur l'entité `flagging`. |
| Couverture actuelle | Aucune |
| Priorité test | P1 (confirmée par l'historique : patch PHP 8.1 puis « fix: flag version/patch » (`2213dd40`)) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled_flag | `modules/vactory_decoupled/modules/vactory_decoupled_flag/src/Controller/ApiFlagging.php:86` | `FlagServiceInterface::getFlagById()` (l.78), `flag()` (l.86), `unflag()` (l.118) — routes `/api/flagging`, `/api/unflagging` |
| vactory_decoupled_flag | `modules/vactory_decoupled/modules/vactory_decoupled_flag/src/Services/VactoryDecoupledFlagService.php:61` | `getStorage('flagging')->getQuery()` |
| vactory_decoupled_flag | `modules/vactory_decoupled/modules/vactory_decoupled_flag/vactory_decoupled_flag.module:44` | `hook_entity_base_field_info` (champ calculé flag) |
| vactory_academy | `modules/vactory_academy/src/Controller/ApiFlagging.php:89` | `flag()` / `unflag()` (l.121) — copie du contrôleur décuplé |
| vactory_academy | `modules/vactory_academy/src/Services/AcademyFlagService.php:52` | Requête/`loadByProperties` sur `flagging` (l.66) |
| vactory_points | `modules/vactory_espace_prive/modules/vactory_points/src/EventSubscriber/FlagEventsSubscriber.php:49` | `FlagEvents::ENTITY_FLAGGED/UNFLAGGED`, `FlaggingEvent::getFlagging()`, `UnflaggingEvent::getFlaggings()` (l.78) |
| vactory_ntcc | `modules/vactory_ntcc/vactory_ntcc.module:82` | `\Drupal::service('flag')->getFlagById()` |
| vactory_social_network | `modules/vactory_social_network/vactory_social_network.module:179` | `entityQuery('flagging')`, `preprocess_flag__liked_content` (l.175) |
| vactory_academy | `modules/vactory_academy/config/install/views.view.vactory_academy.yml` | Relation flag dans une vue |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `modules/vactory_decoupled/modules/vactory_decoupled_flag/tests/src/ExistingSite/ApiFlaggingTest.php`** — créer un flag `favorite` sur node, utilisateur authentifié POST `/api/flagging` {flag_id, entity_id} → 200, une entité `flagging` existe ; POST `/api/unflagging` → supprimée. Cas anonyme → 403. Attrape un changement de `FlagService::flag()/unflag()`.
- **[Kernel] `modules/vactory_espace_prive/modules/vactory_points/tests/src/Kernel/FlagEventsSubscriberTest.php`** — `flag->flag()` → points utilisateur incrémentés ; `unflag()` → décrémentés. Attrape un renommage des events/`getFlaggings()`.

## Notes
`vactory_decoupled_flag.info.yml` ne déclare pas `flag` en dépendance alors qu'il injecte `FlagServiceInterface` (à corriger). Contrôleur `ApiFlagging` dupliqué entre vactory_academy et vactory_decoupled_flag.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^4.0-beta1`
- 2021-01-31 `1be86d53` réécriture contrainte `^4.0-beta1` → `^4.0@beta` — « Update: Add v2.1.0 Factory release changes. »
- 2023-01-27 `894e4058` patch ajouté « Fix deprecated php 8.1 str_replace » — « Fix: Add php 8 compatibility to contrib modules »
- 2025-12-08 `2213dd40` montée `^4.0@beta` → `^5.0` — « fix: flag version/patch »
- 2025-12-08 `2213dd40` patch retiré « Fix deprecated php 8.1 str_replace » — « fix: flag version/patch »
