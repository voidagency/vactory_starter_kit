- **Ne pas modifier les fichiers du core ;** Lors de la mise à jour du coeur de Drupal, les fichiers du core sont écrasés. Les modifications réalisées dans le core sont alors perdues (ou obligent à gérer les patchs ce qui alourdit sensiblement la maintenance).
- **Utiliser de préférence les modules existants ;** La communauté Drupal est très active et les modules stables sont éprouvés par de nombreux utilisateurs. On préférera donc utiliser un module public éprouvé et évolutif que réaliser le développement d'un nouveau code pour les mêmes fonctionnalités.
- **En cas de patch d’un module contributif : documentation ;** Les patches doivent être documentés et listés pour permettre malgré tout la mise à jour des modules.
- **Le dossier `modules` doit être séparé en 3 sous-dossiers ;** Par convention, les trois sous-dossiers sont nommés : `« contrib »` pour les modules provenant de la communauté Drupal, `« custom »` pour les modules spécifiques par projets, et `« vactory »` pour les modules Vactory.

- Les pages utiliseront un préfixe pour chaque langue ; (en/, fr/, …).
- La traduction se fera par node (Core Content Translation) ;Multilingue, avec traduction.
- Le déploiement des configurations sera via des Features.
- La gestion des versions de code se fera via GIT.
- Chaque fichier doit commencer par un bloc de commentaires et être commenté; Les commentaires doivent être formatés selon les normes de Doxygen (http://drupal.org/node/1354).
- Les noms de variables stockées dans la table variable doivent commencer par le nom du module ;** Ceci pour éviter tout conflit et écrasement de ces variables.
- **Cohérence de la BD et de ses définitions dans le module ;** Le schéma des tables utilisées par le module sera à jour dans le module, et des hook_update_N permettront de mettre à jour des tables déjà installées.
- **Formatage du code - exigence minimale ;** Les conventions officielles à suivre sont disponibles à cette adresse : http://drupal.org/coding-standards + pages liées. Le code de chaque module ou thème développé doit passer sans erreur mineure la revue par le module Coder Review : http://drupal.org/project/coder. Cet outil vous permet de vérifier votre code à tout moment.
- Les librairies externes utilisées par les modules doivent être installées dans `libraries`.
- **Encodage UTF8 ;** Drupal utilise par défaut cet encodage, qui doit suffire à toutes les utilisations.
- **Les noms de fonctions doivent commencer par le nom du module ;** Dans le cas contraire, plusieurs fonctions peuvent porter le même nom et créer un conflit lors de son appel.
- **PHP7 ;** Le module ne doit utiliser aucune fonction <PHP7 qui a été remplacée par un équivalent PHP7 (version de référence = 7.1).
- **Utilisation du database abstraction layer de Drupal ;** Tous les appels à la base de données doivent être passés à travers les fonctions du database abstraction layer : `\Drupal::service('database')` `$connection->query()` `$query->fetchAll()` `$connection->insert()` …
- Les preprocess éventuellement nécessaires seront placés autant que possible dans un module custom plutôt que dans le thème; Pour faciliter leur réutilisation.



