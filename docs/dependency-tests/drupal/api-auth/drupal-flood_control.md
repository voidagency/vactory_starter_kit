# drupal/flood_control

| | |
|---|---|
| Contrainte composer.json | `^2.3` |
| Version lockée | `2.3.3` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — on étend son formulaire de réglages (`FloodControlSettingsForm`) et on remplace sa route ; le login OAuth ne lit les limites `user.flood` que si le module est présent. |
| Couverture actuelle | Partielle |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_flood_control | `vactory_flood_control.info.yml:6` | dépendance |
| vactory_flood_control | `src/Form/VactoryFloodControlSettingsForm.php:6,11` | `extends Drupal\flood_control\Form\FloodControlSettingsForm` (`buildForm`/`submitForm` surchargés) |
| vactory_flood_control | `src/Routing/VactoryFloodControlRouteSubscriber.php:17-18` | remplace le `_form` de la route `flood_control.settings` |
| vactory_flood_control | `src/EventSubscriber/VactoryFloodControlSubscriber.php:91-92` | events core `UserEvents::FLOOD_BLOCKED_USER/IP` (notification mail) |
| vactory_decoupled | `src/Controller/DecoupledOauth2Token.php:96-108` | si `moduleExists('flood_control')` lit `user.flood` (limites) ; sinon limites `NULL` |
| profil | `vactory_starter_kit.info.yml:138` | installé |

## Tests existants qui l'exercent
- `modules/vactory_decoupled/modules/vactory_decoupled_espace_prive/tests/Functional/LoginControllerTest.php:250` (`testFloodControlPerUser`) — dépassement de `user.flood:user_limit` sur `/oauth/login-token` → `flood_control_error`.

## Tests à écrire
- **[ExistingSite] `vactory_flood_control/tests/src/ExistingSite/FloodSettingsFormTest.php`** — admin GET `/admin/config/people/flood-control` → 200, champ `emails` présent (notre formulaire) ; POST → `vactory_flood_control.settings:emails` et `user.flood` sauvegardés. Attrape changement de constructeur/ID de route du formulaire parent.

## Notes
- `DecoupledOauth2Token.php:96` : sans `flood_control`, `$userLimit`/`$userWindow` restent non initialisés et sont passés à `flood->isAllowed()` (:156-157) — comportement à vérifier.

## Historique
- 2023-02-10 `a56aaaf5` ^2.2 → ^2.3 — #42408
- *hors 3.x* → ^3.0 (`bf4234f9`, `8c87528b`)
- Aucun incident.

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
