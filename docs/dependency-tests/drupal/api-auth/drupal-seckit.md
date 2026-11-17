# drupal/seckit

| | |
|---|---|
| Contrainte composer.json | `^2.0` |
| Version lockée | `2.0.1` |
| Patches | aucun |
| Statut | Indirect (install profil + liste drush) |
| Risque à la MAJ | Faible — aucune API appelée, aucune config `seckit.settings` livrée (valeurs par défaut du module). |
| Couverture actuelle | Aucune |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:146` | installé |
| vactory_core | `vactory_core.drush.inc:54` | listé dans les modules activés en environnement `development` |
| vactory_security_review | `src/Checks/BackendHttpStrictTransportSecurityHeader.php:102` | simple texte d'aide (mentionne seckit pour HSTS) |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_core/tests/src/ExistingSite/SecurityHeadersTest.php`** — GET `/user/login` → en-têtes attendus de la config seckit (ex. `X-Frame-Options`). Seulement si une config seckit est imposée côté projet.

## Notes
- Aucun `seckit.settings.yml` dans `config/` : les en-têtes dépendent des défauts du module, qui peuvent changer entre versions (à vérifier).

## Historique
- 2020-06-28 `1af659f7` ajout ^1.2.0 ; 2021-01-31 `1be86d53` → ^2.0
- Aucun incident.
