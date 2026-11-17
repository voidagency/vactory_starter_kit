# drupal/shield

| | |
|---|---|
| Contrainte composer.json | `^1.2.0` |
| Version lockée | `1.7.0` |
| Patches | aucun |
| Statut | Utilisé |
| Risque à la MAJ | Moyen — le front interroge `/api/check-shield` qui lit la clé brute `shield_enable` ; la config installée liste les chemins API exclus (`paths`). Un renommage de clé ou de logique d'exclusion bloque le front derrière un Basic Auth. |
| Couverture actuelle | Aucune |
| Priorité test | P2 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| vactory_decoupled | `src/Controller/checkShieldController.php:52-62` | `moduleExists('shield')` + `shield.settings` `getRawData()['shield_enable']`, cache tag `config:shield.settings` |
| vactory_decoupled | `vactory_decoupled.routing.yml:170-171` | route `/api/check-shield` |
| profil | `config/install/shield.settings.yml:1-12` | `shield_enable: false`, `method: 0`, `paths` (`/api/*`, `/_translations`, `/_menus`, `/_webform`, …), `domains` |
| profil | `vactory_starter_kit.info.yml:185` | installé |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_decoupled/tests/src/ExistingSite/CheckShieldTest.php`** — GET `/api/check-shield` → `{"enabled": false}` ; `shield_enable=TRUE` → `true` (cache invalidé). Activer shield et requêter `/api/node/vactory_page` sans credentials → 200 (chemin exclu), `/user/login` → 401. Attrape changement de clé de config ou de sémantique `method`/`paths`.

## Notes
- `config/install/shield.settings.yml:3-6` installe des identifiants Basic Auth par défaut en clair (`user: vactory`) dans le profil — fait constaté, à arbitrer côté sécurité.

## Historique
- 2020-06-28 `1af659f7` ajout ^1.2.0 (inchangé)
- Aucun incident.
