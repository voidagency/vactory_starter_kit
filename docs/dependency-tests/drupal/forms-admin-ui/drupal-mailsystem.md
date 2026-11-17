# drupal/mailsystem

| | |
|---|---|
| Contrainte composer.json | `^4.3` |
| Version lockée | `4.4.0` |
| Patches | aucun |
| Statut | Indirect (config/dépendance seulement) |
| Risque à la MAJ | Faible — aucun appel d'API ; seulement installé et configuré. |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:88` | Installé par le profil |
| profil | `config/install/mailsystem.settings.yml:1-4` | `defaults.sender/formatter: devel_mail_log` |
| vactory_announcements, vactory_otp, vactory_notifications, vactory_flood_control | `src/Services/AnnouncementsService.php:32`, `src/Services/VactoryOtpService.php:35`, `src/Services/VactoryNotificationsService.php:23`, `src/EventSubscriber/VactoryFloodControlSubscriber.php:23` | Docblock `@var \Drupal\mailsystem\MailsystemManager` seulement ; le service injecté est `plugin.manager.mail` |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- Rien de spécifique ; l'envoi est couvert par les tests proposés pour `drupal/symfony_mailer`.

## Notes
- `devel_mail_log` est un plugin de `devel`, qui n'est pas installé par le profil (seulement par `vactory_devtools`) : config incohérente ; neutralisée car `symfony_mailer` remplace `plugin.manager.mail` (à vérifier).
- Candidat à la suppression si symfony_mailer reste le mailer.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2020-06-28 `1af659f7` ajout initial `^4.3`
- — aucun changement contrainte inchangée sur `3.x`
