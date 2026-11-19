# Releases du profil

Branche `3.x`, tags `3.x.y`. Les projets consomment le profil par composer (`voidagency/vactory_starter_kit`). Le dépôt est public.

## Changelog
- L'auteur de chaque PR ajoute sa ligne dans `[Non publié]` de `CHANGELOG.md` ; le relecteur la vérifie.
- Une ligne par PR et par sujet, préfixée par le module : `` `vactory_x` : ce qui change (#PR) ``. `#n` est un numéro de PR ; un ticket est noté « Redmine #n », sans URL.
- Rubriques, dans cet ordre, sans rubrique vide : Mise à jour des projets, Problèmes connus (après la release), Sécurité, Changements de comportement, Ajouts, Correctifs, Dépréciations, Retraits, Dépendances, Interne.
- Une action à faire sur les projets va aussi dans « Mise à jour des projets », préfixée par sa portée : « Tous les sites » ou « Si… » (par exemple « Si une feature du projet exporte… », « Sites en config sync (rare) »). Les projets gèrent leur config par Features : penser à ce qu'un `drush features:import` réimporterait.
- Le responsable de la release ajoute les lignes des PR Dependabot (une seule ligne pour les montées du seul `composer.lock`), dédoublonne et ordonne.
- Une PR qui modifie une contrainte de `composer.json`, Dependabot compris, a sa ligne dans « Dépendances » et, si besoin, dans « Mise à jour des projets ». Une montée de version majeure se discute avant le merge (voir « Numéro de version »).
- La CI rappelle d'ajouter une ligne quand une PR modifie `modules/` sans toucher à `CHANGELOG.md`. Ce rappel n'est pas bloquant ; il est dans l'image CI (dépôt droneci), pas dans ce dépôt.

## Texte public des correctifs de sécurité
Décrire le comportement corrigé et l'action à faire. Pas de chemin d'endpoint, pas d'identifiant, pas de scénario d'attaque, rien sur l'état des sites pas encore mis à jour. Le détail va dans le ticket Redmine et dans le ticket Dispatch.

## Numéro de version
- **Patch (`3.3.1`) :** correctifs seuls. Pas d'update hook, pas de contrainte modifiée dans `composer.json`, aucune action sur les projets au-delà de `drush cr`.
- **Mineure (`3.4.0`) :** ajouts, modules optionnels, update hooks, changements de comportement par défaut (durcissements de sécurité compris), contraintes composer modifiées, retrait d'un élément déjà déprécié ou dont on a vérifié qu'il n'est pas utilisé.
- **Majeure (`4.0.0`) :** version majeure de Drupal, version minimale de PHP, retrait d'API publique utilisée, sans dépréciation.
- Si « Mise à jour des projets » contient autre chose que la mise à jour composer et `drush cr`, la version est au moins mineure. La release en préparation sera donc la **3.3.0**.
- API publique : identifiants de services, routes et sorties JSON lues par le front, permissions, schémas de config, hooks, identifiants de plugins. Les classes PHP et leurs constructeurs sont internes.
- Version majeure d'une dépendance : préférer une contrainte qui accepte les deux (`^1 || ^2`) ; sinon, version mineure avec une note de mise à jour.
- Dépréciation : rubrique « Dépréciations » et `@deprecated in 3.N.0, removed in 4.0.0` dans le code ; le retrait se fait à la version majeure.

## Une PR est terminée quand
- les tests sont ajoutés ou mis à jour, et la CI est verte (tests, phpcs, Sonar) ;
- une personne l'a relue ;
- sa ligne est dans le changelog, avec la note de mise à jour si besoin ;
- son titre suit `type: #ticket - sujet` (lien Redmine ↔ PR ↔ changelog ↔ release) ;
- pour un correctif de sécurité, le texte public respecte la règle ci-dessus ;
- les points hors périmètre sont corrigés ou suivis dans un ticket.

## Publier une release
1. **Complétude.** Comparer `git log --merges --first-parent --format=%s <dernier tag>..origin/3.x` aux numéros de PR de `[Non publié]`.
2. **Version candidate (mineures et majeures).** Tagger le dernier commit de `3.x` : `git tag 3.x.y-rc1 origin/3.x && git push origin 3.x.y-rc1`. Un projet teste avec `composer require voidagency/vactory_starter_kit:3.x.y-rc1 -W`. Dérouler « Mise à jour des projets » telle qu'elle est écrite sur vactory8 et sur un ou deux projets clients à Features (canaries Autogit, Redmine #71368). On teste le guide autant que le code.
3. **PR de release.** `[Non publié]` devient `[3.x.y] - AAAA-MM-JJ` (date du tag final), avec une nouvelle section `[Non publié]` vide et les liens de bas de fichier mis à jour.
4. **Tag et release GitHub.** La section du changelog en tête, les notes générées par GitHub à la suite :
   ```
   awk '/^## \[3\.x\.y\]/{f=1;next} /^## \[/{f=0} f' CHANGELOG.md > notes.md
   gh release create 3.x.y --target 3.x --notes-file notes.md --generate-notes
   ```
5. **CI.** Vérifier le build du commit tagué sur `3.x` (les tags ne sont pas construits).
6. **Dispatch.** Créer le ticket de campagne avec le lien de la release et le détail privé des correctifs de sécurité.
7. **Retours.** Un problème trouvé pendant la campagne donne une ligne « Problèmes connus » dans la section de la version, ou une release patch.

## Correctif urgent
- Si `3.x` ne contient que des changements sans action sur les projets : tagger le patch depuis `3.x`.
- Sinon : créer `3.x.y-hotfix` depuis le dernier tag, y reporter le correctif par une PR qui cible cette branche (`git cherry-pick -x`), tagger, créer la release avec `--target 3.x.y-hotfix`, puis reporter la section du changelog dans `3.x`. La CI ne construit que `3.x` et les PR : passer par une PR pour avoir un build.
