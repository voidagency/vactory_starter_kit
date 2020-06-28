## Content Moderation

Le module de modération du contenu (Content Moderation) vous permet de développer les états "non publiés" et 
"publiés" de Drupal pour le contenu. Il vous permet d'avoir une version publiée qui est en direct, mais d'avoir
une copie de travail distincte qui est en cours d'examen avant sa publication. Ceci est réalisé en utilisant des
workflows pour appliquer différents états et transitions aux entités selon les besoins.

## Configuration

En accédant au `lien admin/config/workflow/workflows/manage/editorial` en trouve pas mal de configuration pour les status, transition et les different entité disponible dans notre instance.
 - Selection & Changements de type d'entité:
    > On selectionne les entré de chanque entité disponible et qui on veux la moderé, exemple les types de contenu. 
    ![Séléction d'entité](/docs_factory/assets/img/select-entity.png "Séléction d'entité")
    
    >  C'est important de fait la méme chose pour les paragraphs par ce que dans la plupart des cas sont référencée au type de contenu.
    
    > Sur la partie transition on peux configuré la transition entre les cas, exemple de publier ou brouillon a archivé.
    ![Exemple de Transition](/docs_factory/assets/img/transition-exemple.png "Exemple de Transition")
    
    > La partie State c'est pour la creation et moduficationn les differents état comme publier, archivé, brouillon ... .
    
 - Autorisations:
 
 Vous autorisez probablement les personnes ayant d'autres rôles que l'administrateur à participer
  au flux de travail. Pour ce faire, vous devez configurer le schéma d'autorisation approprié sur People> Permissions.
  ![Les rôles et les permissions](/docs_factory/assets/img/role-permissions.png "Les rôles et les permissions")
  
  Le schéma de l'image ci-dessus est centré autour de deux rôles. Auteur et éditeur. Dans cet exemple, un auteur peut créer et
   modifier du contenu, mais pas publier du contenu. Pour publier du contenu, l'auteur devra enregistrer le contenu en tant que brouillon.
    Ensuite, un utilisateur avec le rôle Editeur peut examiner le contenu et l'enregistrer en tant que publié ou l'enregistrer en tant que brouillon.
  
  Veillez à accorder «Afficher la dernière version» et, par la suite, «Afficher son propre contenu non publié» aux auteurs.
   Sinon, les auteurs ne pourront pas voir leurs dernières modifications.
  
  Voici un exemple d'autorisations de noeud appropriées pour cet exemple. Notez que le rôle Auteur a l'autorisation de créer et de modifier ses propres pages de base,
   ainsi que d'afficher et de rétablir les révisions, tandis que le rôle Editeur dispose d'autorisations supplémentaires pour autoriser la modification de n'importe quelle page Basic.
    Votre flux de travail peut ne pas correspondre exactement à cet exemple, mais il est démonstratif d'un scénario.
    ![Permission des noeuds](/docs_factory/assets/img/node-permissions.png "Permission des noeuds")
    
  Maintenant que nos permissions sont configurées, créons une page et testons-la sous la modération du contenu.
    
   > Exemple de flux de travail
   L'auteur crée une page. L'auteur enregistre ce contenu en tant que brouillon.
    ![Contenu brouillon](/docs_factory/assets/img/author-new-content-draft.png "Contenu brouillon")
    
   L'éditeur publie cette page de contenu. En d'autres termes, l'éditeur modifie l'état de modération du contenu de Brouillon à Publié.
    ![Publier un contenu A](/docs_factory/assets/img/editor-publishes-A.png "Publier un contenu A")
   
   La page de contenu est maintenant publiée. Toute personne ayant l'autorisation de voir le contenu publié pourra voir la page.
   
   L'auteur modifie la page de contenu et enregistre la nouvelle modification en tant que brouillon.
    ![Modifier B en brouillon](/docs_factory/assets/img/author-edits-to-B-draft.png "Modifier B en brouillon")
    
   L'auteur peut voir la dernière version de la page de contenu dans l'onglet Dernière version. La dernière version de la page de contenu est un brouillon et n'est pas visible publiquement.
    ![Voir la dernier version](/docs_factory/assets/img/author-views-latest-version.png "Voir la dernier version")
    
   À ce stade, l'auteur et toute autre personne autorisée à consulter le contenu publié peuvent, bien sûr, continuer à consulter la version publiée de la page de contenu.
    ![Publier le contenu](/docs_factory/assets/img/author-views-published-content.png "Publier le contenu")
    
   L'éditeur peut publier le brouillon du bloc dans l'onglet de la dernière version. Autrement dit, l'éditeur peut modifier l'état de modération de Brouillon à Publié. 
    ![Change le status en publier](/docs_factory/assets/img/editor-changes-state-to-published.png "Change le status en publier")
   
   L'éditeur peut également publier le brouillon dans le formulaire d'édition de noeud.
    ![Change l'état de modération](/docs_factory/assets/img/editor-changes-moderation-state-in-node-form.png "Change l'état de modération")
    
   Désormais, l'auteur et toute personne ayant l'autorisation d'afficher du contenu publié peuvent voir la page de contenu modifiée.
   ![Voir le contenu editable](/docs_factory/assets/img/author-views-edited-content-page.png "Voir le contenu editable")
    
   Ce qui précède décrit un flux de travail de publication simple. Ceci est seulement un exemple. Le module de modération du contenu offre toutes sortes de possibilités. 
   Regardez plus profondément dans les États et les transitions. Considérez plusieurs flux de travail pour les environnements de publication complexes. 
