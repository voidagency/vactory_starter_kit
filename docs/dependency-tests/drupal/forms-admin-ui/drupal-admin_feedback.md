# drupal/admin_feedback

| | |
|---|---|
| Contrainte composer.json | `^2.2` |
| Version lockée | `2.6.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — controller étendu, écriture SQL directe dans la table `admin_feedback`, subscriber sur `VoteEvent`. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
Modules optionnels.

| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_content_feedback | `vactory_content_feedback.info.yml:5` | Dépendance `drupal:admin_feedback` |
| vactory_content_feedback | `src/Controller/VactoryContentFeedbackController.php:5,12,18,26` | `extends AdminFeedbackController`, override `updateFeedback()`, `$this->database->update('admin_feedback')` |
| vactory_content_feedback | `vactory_content_feedback.module:8` | `\Drupal::config('admin_feedback.settings')` |
| vactory_content_feedback | `vactory_content_feedback.routing.yml` | POST `/_feedback_update`, permission `give feedback` (fournie par admin_feedback) |
| vactory_points | `vactory_points.info.yml:10` ; `src/EventSubscriber/AdminFeedbackEventSubscriber.php:5,48,55` | Abonné à `VoteEvent::VOTE_EVENT` |
| vactory_points | `src/Form/VactoryPointsSettingsForm.php:85` | `moduleExists('admin_feedback')` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[ExistingSite] `vactory_content_feedback/tests/src/ExistingSite/FeedbackUpdateTest.php`** — insérer une ligne `admin_feedback`, POST `/_feedback_update` avec id/message → 200 et ligne mise à jour. Attrape : changement de schéma de table, de signature de `AdminFeedbackController`, de permission.
- **[Kernel] `vactory_points/tests/src/Kernel/VoteEventSubscriberTest.php`** — dispatcher `new VoteEvent(...)` → points attribués. Attrape : renommage de la constante/du constructeur de l'event.

## Notes
- aucune

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2022-01-24 `bf6036f4` ajout `^2.2` — « Add: #28746 - Add admin feedback contrib module & add feedback event subscriber in vactory poi… »
