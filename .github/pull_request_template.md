Redmine #

## Contenu
<!-- Ce que change la PR, en quelques lignes. Dépôt public : pas de détail d'exploitation pour un correctif de sécurité, renvoyer au ticket Redmine. -->

## Type de changement
<!-- Donne le numéro de la prochaine version, voir RELEASING.md. -->
- [ ] Correctif sans action sur les projets (patch)
- [ ] Ajout, changement de comportement, update hook ou contrainte composer (mineure)
- [ ] Rupture d'API publique (majeure, à discuter avant)

## Surface publique touchée
<!-- Ce qu'un projet peut utiliser ou surcharger. Cocher ce que la PR change, même sans changer de signature. « Aucune » si rien. -->
- [ ] Route, contrôle d'accès, authentification, permission
- [ ] Service, hook, événement, plugin, fonction ou méthode publique
- [ ] Config installée, schéma, update hook
- [ ] Template Twig ou variable passée au template
- [ ] Réponse d'une API (JSON:API, routeur, menus, endpoints `vactory_decoupled`)
- [ ] Contrainte composer ou patch

## Comportement existant
<!-- Un projet peut-il voir un résultat différent pour la même entrée ? Si oui : quoi, pour qui. -->
- [ ] Le comportement existant ne change pas
- [ ] Il change : figé avant par un test de caractérisation (le test existant change d'attendu dans cette PR, ou un test ajouté montre l'avant et l'après)
- [ ] Il change : l'ancien comportement reste, déprécié (journal ou avertissement), et la version de retrait est annoncée au CHANGELOG

## Impact sur le parc
<!-- Obligatoire si le comportement change. Recherche des usages dans les dépôts des projets (code, templates surchargés, fronts) : qui est touché, et quoi faire. -->

## Tests
<!-- Tests ajoutés ou modifiés, et comment ils ont été vérifiés : ils échouent sans le correctif (donner la sortie). -->

## Impact à la mise à jour
<!-- Cocher ce que les projets doivent faire, puis préciser. « Aucun » si rien. -->
- [ ] composer (contrainte, patch)
- [ ] `drush updb`
- [ ] Features : une feature (profil ou projet) doit être réexportée, ou risque d'annuler la mise à jour à l'import
- [ ] `drush cr`
- [ ] Front ou config du projet à adapter

## Checklist
- [ ] Tests ajoutés ou mis à jour, et vus en échec sans le correctif
- [ ] Module sensible (authentification, accès, sécurité) : tests de mutation lancés, ou raison notée
- [ ] Ligne ajoutée dans `CHANGELOG.md` (`[Non publié]`), plus « Mise à jour des projets » si une case ci-dessus est cochée, et « Changements de comportement » si le comportement change
- [ ] Correctif de sécurité : texte public (PR, CHANGELOG) sans détail d'exploitation
- [ ] Points hors périmètre : corrigés, ou suivis dans un ticket cité ici
