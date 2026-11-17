# drupal/symfony_mailer

| | |
|---|---|
| Contrainte composer.json | `^1.0@alpha` |
| Version lockée | `1.4.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Élevé — tout l'envoi de mail passe par lui (remplace `plugin.manager.mail`), et 2 `hook_mailer_build` réécrivent destinataires et mail de reset password. |
| Couverture actuelle | Aucune |
| Priorité test | P1 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:89` | Installé par le profil |
| profil | `config/install/symfony_mailer.settings.yml`, `config/install/symfony_mailer.mailer_transport.smtp.yml` | Transport par défaut `smtp` |
| vactory_core | `vactory_core.module:22-23,140-160` | `hook_mailer_build(EmailInterface)` : redirection de tous les mails si `Settings::get('vactory_mail_redirect')` (`getTo`, `setTo`, `setSubject`, `Address`) sauf route `user.pass` |
| vactory_decoupled | `vactory_decoupled.module:22,579-600` | `hook_mailer_build` : réécrit le mail `user_password_reset` (via `getParams()['legacy_message']`) avec l'URL front `BASE_FRONTEND_URL` |
| vactory_multivers | `vactory_multivers.info.yml:19` | Dépendance déclarée |

## Tests existants qui l'exercent
- aucun

## Tests à écrire
- **[Kernel] `vactory_core/tests/src/Kernel/MailRedirectTest.php`** — `setSetting('vactory_mail_redirect', 'qa@example.com')`, transport `null`, envoyer un mail via `plugin.manager.mail->mail('system', …)` → assert destinataire = qa@example.com et sujet suffixé `(To: …)`. Attrape : renommage `EmailInterface::getTo/setTo`, `Address`, changement du nom/de la phase du hook.
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/PasswordResetMailTest.php`** — `BASE_FRONTEND_URL` défini, déclencher `user_mail('password_reset')` hors route `user.pass`, capturer via transport de test (ou `state` `system.test_mail_collector`) → assert lien pointant sur le front. Attrape : disparition de `legacy_message` dans les params (API BC du module).

## Notes
- `SymfonyMailerServiceProvider` (vendor) annule les `methodCalls` de `plugin.manager.mail` : la config `mailsystem.settings` (devel_mail_log) est probablement ignorée (à vérifier).
- Contrainte `^1.0@alpha` alors que 1.4.0 stable est lockée ; 2.x change l'API (`EmailInterface`, hooks) : ne pas élargir sans test.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2022-04-06 `f3a80371` ajout `^1.0@alpha` — « Add: #34779 - remove swift mailer && install symfony mailer »
- 2022-04-06 `f3a80371` remplacement remplace `swiftmailer` — #34779 « remove swift mailer && install symfony mailer »
