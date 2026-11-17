# drupal/nocurrent_pass

| | |
|---|---|
| Contrainte composer.json | `^1.2.0` |
| Version lockée | `1.2.0` |
| Patches | aucun |
| Statut | Indirect (install profil) |
| Risque à la MAJ | Faible — aucune API ; retire le champ « mot de passe actuel » du formulaire user. Impact possible sur les PATCH JSON:API de `pass` (à vérifier). |
| Couverture actuelle | Aucune |
| Priorité test | P2 (P1 par historique, ramené à P2 : usage indirect seulement — arbitrage Hamza 2026-11-17) — (P3 → P1 : 2 patches (D9 readiness, correctif « Settings are not applied »)) |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:107` | installé |

## Tests existants qui l'exercent
- aucun.

## Tests à écrire
- **[ExistingSite] `vactory_user/tests/src/ExistingSite/NoCurrentPassTest.php`** — user connecté GET `/user/{uid}/edit` → pas de champ `current_pass`. À n'écrire que si le comportement est contractuel côté projets.

## Notes
- 0 occurrence de `nocurrent_pass` / `current_pass` dans `modules/`.

## Historique
- 2021-01-31 `1be86d53` patch ajouté « Drupal 9 readiness »
- ≤ 2022-09-08 patch correctif « Settings are not applied if use not a standard path » (d.o #3005310) présent
- 2022-09-08 `0648d2b8` montée ^1.0.0 → ^1.2.0, patch « outdated » retiré ; 2022-10-02 `312057d7` retiré à nouveau (réintroduit via merge)
- *hors 3.x* `4bc3ce65` retrait du patch (branche `9.x-v2.2.7`) ; `7465bf1c` → ^2.0 — `origin/upgrade/d11`

Branches non fusionnées dans `3.x` : `origin/upgrade/d11`, `origin/update/d11`, `origin/feat/62381` (marquées *hors 3.x*).
