# drupal/onesignal

| | |
|---|---|
| Contrainte composer.json | `^3.0` |
| Version lockée | `3.0.2` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — service `@onesignal` injecté et méthode `addNotification()` appelée ; rupture = notifications push silencieusement perdues. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
Module optionnel (non installé par le profil).

| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_onesignal | `vactory_onesignal.info.yml:8` | Dépendance |
| vactory_onesignal | `vactory_onesignal.services.yml:7` | Injection `@onesignal` (`Drupal\onesignal\OneSignalService`) |
| vactory_onesignal | `src/Services/VactoryOnesignalManager.php:10,95,110` | Config `onesignal.config:onesignal_app_id`, `OneSignalService::addNotification(array)` |
| vactory_onesignal | `vactory_onesignal.links.task.yml:4` ; `vactory_onesignal.info.yml:6` | Route `onesignal.config_form` (base_route / configure) |
| vactory_onesignal | `vactory_onesignal.routing.yml:2` | `/api/notifications/device_id/add` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_onesignal/tests/src/Kernel/OnesignalManagerTest.php`** — remplacer `onesignal` par un mock de `OneSignalService` attendant `addNotification()` avec `app_id`, `headings`, `contents`, `include_subscription_ids` ; appeler `onesignalNotifyUsers()` → assert payload. Vérifier aussi que la route `onesignal.config_form` existe. Attrape : renommage du service/méthode/route, clé de config.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-12-06 `b1c55baa` ajout `^3.0` — « Add: Add onesignal contrib module »
- 2025-12-04 `0749699f` fork d'issue (branche non fusionnée) `origin/update/d11` : `dev-project-update-bot-only` → `dev-3433682-automated-drupal-11` « D11 comptability using issue fork » ; `origin/upgrade/d11` : patch « Drupal 11 compatibility » (rector)
