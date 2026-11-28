# Releases du profil

Branche `3.x`, tags `3.x.y`. Les projets consomment le profil par composer (`voidagency/vactory_starter_kit`). Le dépôt est public.

## Contexte des projets
Ce que suppose toute note de mise à jour :
- **Version figée.** Les projets exigent une version figée du profil (`voidagency/vactory_starter_kit:<version>`), jamais `3.x-dev` ni une autre branche de dev.
- **Features.** Les projets gèrent leur config avec Features, pas avec la config sync du core : raisonner sur ce qu'un `drush fr` (features:import) réimporterait. La config sync est un cas rare, à mentionner comme tel.
- **Pas de réimport par défaut.** Réimporter une feature du profil remplace la config active et efface les valeurs propres au site (clés API, identifiants, réglages). On ne demande un `drush fr` que pour une liste précise de modules, en disant ce qui sera écrasé.
- **Déploiement.** Il se fait par capistrano ou Docker. `drush updb` et `drush cr` sont lancés **à la main** après le déploiement.
- **Points hors périmètre.** Un point « hors périmètre » relevé dans une PR ou un ticket est corrigé, ou suivi dans un ticket cité. Il ne reste jamais sans suite.

## Décisions d'architecture
Une décision difficile à défaire, surprenante sans son contexte et issue d'un vrai arbitrage est écrite dans [`docs/adr/`](docs/adr/README.md), dans la PR qui l'applique. Avant de revenir sur un choix du profil, lire l'ADR correspondant.

## Changelog
- L'auteur de chaque PR ajoute sa ligne dans `[Non publié]` de `CHANGELOG.md` ; le relecteur la vérifie.
- Une ligne par PR et par sujet, préfixée par le module : `` `vactory_x` : ce qui change (#PR) ``. `#n` est un numéro de PR ; un ticket est noté « Redmine #n », sans URL.
- Rubriques, dans cet ordre, sans rubrique vide : Mise à jour des projets, Problèmes connus (après la release), Sécurité, Changements de comportement, Ajouts, Correctifs, Dépréciations, Retraits, Dépendances, Interne.
- Une action à faire sur les projets va aussi dans « Mise à jour des projets », préfixée par sa portée : « Tous les sites » ou « Si… » (par exemple « Si une feature du projet exporte… », « Sites en config sync (rare) »). Les projets gèrent leur config par Features : penser à ce qu'un `drush features:import` réimporterait.
- Le responsable de la release ajoute les lignes des PR Dependabot (une seule ligne pour les montées du seul `composer.lock`), dédoublonne et ordonne.
- Une PR qui modifie une contrainte de `composer.json`, Dependabot compris, a sa ligne dans « Dépendances » et, si besoin, dans « Mise à jour des projets ». Une montée de version majeure se discute avant le merge (voir « Numéro de version »).
- Une PR qui modifie la config d'un module (`config/install`, `config/optional`, feature) indique si les sites existants doivent la réimporter (`drush features:import`) et quelles valeurs propres aux sites un revert effacerait (clés API, identifiants…). Par défaut, aucun revert : la config livrée ne sert qu'à l'installation.
- « Mise à jour des projets » suit toujours le même gabarit, chaque partie présente même vide (« aucun ») :
  1. **Risques et points d'attention**, en premier : le risque principal (ce qui peut casser un site en production) en tête et en gras.
  2. **Procédure**, avec le bloc de commandes puis une étape par commande :
     1. code et composer : `composer require voidagency/vactory_starter_kit:<version> -W`, version figée, conflits, code projet à adapter ;
     2. `drush updb -y`, lancé à la main après le déploiement : liste des updates et de leur effet ;
     3. `drush fr -y <module>` : liste exacte des features à réimporter, ou « aucune », avec les valeurs propres aux sites qu'un réimport effacerait ;
     4. back-office : réglages à faire ou à vérifier, avec le chemin ;
     5. `drush cr` ;
     6. vérifications : ce qu'on teste sur le site mis à jour pour le valider.
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
- les tests ont été vus en échec sans le correctif ;
- un comportement existant que la PR change est d'abord figé par un test (test de caractérisation) ; la PR montre l'avant et l'après, et ajoute la ligne « Changements de comportement » au changelog ;
- un comportement public qui change pour un projet est déprécié avant d'être retiré (l'ancien comportement reste, avec un journal ou un avertissement, et la version de retrait est annoncée), et les usages sont cherchés dans les dépôts du parc ;
- un point d'accès créé ou durci est testé sur ce qu'il accepte encore, pas seulement sur ce que la PR change : accès en lecture à l'entité visée, valeurs reçues, entité ou type inexistant (404, pas 500), rien d'enregistré avant ces contrôles ;
- une mise à jour de données (update hook) est testée sur des données d'avant la PR, y compris les révisions et les contenus multilingues, puis sur les opérations qui suivent (édition, suppression de révision) ;
- une personne l'a relue ;
- sa ligne est dans le changelog, avec la note de mise à jour si besoin ;
- son titre suit `type: #ticket - sujet` (lien Redmine ↔ PR ↔ changelog ↔ release) ;
- pour un correctif de sécurité, le texte public respecte la règle ci-dessus ;
- les points hors périmètre sont corrigés ou suivis dans un ticket.

## Publier une release
1. **Complétude.** Comparer `git log --merges --first-parent --format=%s <dernier tag>..origin/3.x` aux numéros de PR de `[Non publié]`.
2. **Version candidate (mineures et majeures).** Tagger le dernier commit de `3.x`, puis publier une pre-release GitHub avec la section `[Non publié]` de ce commit :
   ```
   git tag 3.x.y-rc1 origin/3.x && git push origin 3.x.y-rc1
   { echo "Version candidate : à tester sur un projet de recette, pas en production."; echo;
     git show 3.x.y-rc1:CHANGELOG.md | awk '/^## \[Non publié\]/{f=1;next} /^## \[/{f=0} f'; } > notes.md
   gh release create 3.x.y-rc1 --prerelease --verify-tag --title 3.x.y-rc1 --notes-file notes.md --generate-notes
   ```
   La page porte le badge « Pre-release » et ne devient jamais « Latest ». Une version candidate publiée ne se modifie pas : un problème trouvé se corrige par une PR sur `3.x`, puis `3.x.y-rc2` (nouveau tag, nouvelle pre-release). Un brouillon de release GitHub peut servir à préparer les notes ; ce n'est pas une étape. Un projet teste avec `composer require voidagency/vactory_starter_kit:3.x.y-rc1 -W`. Les projets utilisent toujours une version figée, jamais `3.x-dev`. Dérouler « Mise à jour des projets » telle qu'elle est écrite sur vactory8 et sur un ou deux projets clients à Features (canaries Autogit, Redmine #71368). On teste le guide autant que le code.
3. **PR de release.** `[Non publié]` devient `[3.x.y] - AAAA-MM-JJ` (date du tag final), avec une nouvelle section `[Non publié]` vide et les liens de bas de fichier mis à jour. Dans la même PR, `sonar.projectVersion` de `sonar-project.properties` passe à `3.x.y` : le « nouveau code » jugé par la porte qualité Sonar repart de cette version.
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
