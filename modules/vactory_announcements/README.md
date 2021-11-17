Announcements

  Requirements :
   - Scheduler

  Instalation :

  - Activation du module via la commande drush suivante :

        drush en vactory_announcements

  Configuration :

  - Configuration du cron :

    Ajouter un crontab pour traiter les tâches du scheduler toutes les 10 minutes.

        */10 * * * * drush scheduler-cron

    Il faut configurer le fuseau horaire par défaut sur admin/config/regional/settings quel soit Casablanca, pour ne pas avoir la différence entre les fuseaux horaires.

  - Configuration du mail envoyé aux administrateurs et/ou webmasters et annonceur :

    lien vers  le formulaire de configuration :

        /admin/config/vactory_announcements

    Notification d’ajout d’une annonce :

      * Définissez l'objet et le message de l'e-mail, d'annonce en attente d'approbation, à envoyer au webmaster ou à l'administrateur.

    Notification de la publication d’annonce :

      * Définissez l'objet et le message de l'e-mail de validation à envoyer à l'annonceur.

  - Résultat

    lien vers  le formulaire d'ajout d'annonce :

        /ajouter-annonce

    lien vers listing :

        /vactory-announcements
  - Maintainer

      BOUAKRIM Majda : m.bouakrim@void.fr
