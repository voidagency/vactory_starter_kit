Redmine #

## Contenu
<!-- Ce que change la PR, en quelques lignes. Dépôt public : pas de détail d'exploitation pour un correctif de sécurité, renvoyer au ticket Redmine. -->

## Type de changement
<!-- Donne le numéro de la prochaine version, voir RELEASING.md. -->
- [ ] Correctif sans action sur les projets (patch)
- [ ] Ajout, changement de comportement, update hook ou contrainte composer (mineure)
- [ ] Rupture d'API publique (majeure, à discuter avant)

## Tests
<!-- Tests ajoutés ou modifiés, et comment ils ont été vérifiés (échouent sans le correctif ?). -->

## Impact à la mise à jour
<!-- Cocher ce que les projets doivent faire, puis préciser. « Aucun » si rien. -->
- [ ] composer (contrainte, patch)
- [ ] `drush updb`
- [ ] Features : une feature (profil ou projet) doit être réexportée, ou risque d'annuler la mise à jour à l'import
- [ ] `drush cr`
- [ ] Front ou config du projet à adapter

## Checklist
- [ ] Tests ajoutés ou mis à jour
- [ ] Ligne ajoutée dans `CHANGELOG.md` (`[Non publié]`), plus « Mise à jour des projets » si une case ci-dessus est cochée
- [ ] Correctif de sécurité : texte public (PR, CHANGELOG) sans détail d'exploitation
- [ ] Points hors périmètre : corrigés, ou suivis dans un ticket cité ici
