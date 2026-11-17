# drupal/ctools

| | |
|---|---|
| Contrainte composer.json | `^4.1.1` |
| Version lockée | `4.1.1` |
| Patches | aucun |
| Statut | Indirect (install profil ; requis par `drupal/pathauto`) |
| Risque à la MAJ | Faible — 0 appel d'API, 0 plugin ctools référencé en config. |
| Couverture actuelle | Aucune |
| Priorité test | P2 (P1 par historique, ramené à P2 : usage indirect seulement — arbitrage Hamza 2026-11-17) — (— → P1 : épinglage ~3.9.0 après erreur 3.10.x) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:64` | installé |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- Aucun spécifique : couvert implicitement par l'install du profil en CI (`drush si`).

## Notes
- 0 occurrence de `Drupal\ctools\`, `ctools` dans `config/` et `modules/**/config`. Les conditions `entity_bundle:*` des patterns pathauto sont fournies par le core.

## Historique
- 2020-06-28 `1af659f7` ajout ^3.4
- 2022-08-05 `7fb17f4d` **épinglage** ^3.4 → ~3.9.0 « Fix Ctools 3.10.x version error on content edit/add pages »
- 2023-07-12 `ec1ae4e6` → ^3.14 — #44188
- 2026-09-17 `f337dbc2` → ^4.1.1 (majeure ; même commit que linkit 7.0.16 rétrogradé par `776f9ae0`)
