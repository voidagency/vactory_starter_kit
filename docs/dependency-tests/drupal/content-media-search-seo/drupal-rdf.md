# drupal/rdf

| | |
|---|---|
| Contrainte composer.json | `^2.1` |
| Version lockée | `2.1.1` |
| Patches | aucun |
| Statut | Indirect (install profil + config) |
| Risque à la MAJ | Moyen — module retiré du core (contrib depuis D10.x) ; le profil l'installe et livre `rdf.mapping.comment.comment.yml` : un problème de compatibilité casse `drush si`. |
| Couverture actuelle | Aucune (smoke install) |
| Priorité test | P3 |

## Où on l'utilise
| Module | Fichier:ligne | Usage (API / plugin / hook / config) |
|---|---|---|
| profil | `vactory_starter_kit.info.yml:41` | Installé par le profil |
| profil | `config/install/rdf.mapping.comment.comment.yml:21` | Mapping RDF des commentaires (`Drupal\rdf\CommonDataConverter::dateIso8601Value`) |

## Tests existants qui l'exercent
- aucun (seul `drush si` en CI)

## Tests à écrire
- — (smoke `drush si` ; envisager le retrait du module)

## Notes
Usage réel en décuplé douteux (à vérifier). Si retiré : supprimer aussi la config de profil.

## Historique
Source : `composer.json` sur `3.x` (commits non-merge, `git log` ; hash = commit auteur), + branches non fusionnées signalées.

- 2023-07-17 `e238b528` ajout `^2.1` — « Fix: #44188 - Drupal 10 compatibility: Add rdf, seven, classy and quickedit contrib extensions »
