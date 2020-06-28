



## FONCTIONNEMENT ACTUEL



- cron est un programme qui permet aux utilisateurs des systèmes Unix d’exécuter automatiquement des scripts, des commandes ou des logiciels à une date et une heure spécifiées à l’avance, ou selon un cycle défini à l’avance.

- Il éxiste deux façons pour exécuter cron :
    - On peut exécuter cron manuellement sur la page : '/admin/config/system/cron'
    - Ou bien en accédant à l'URL cron se trouvant sur la même page et qui est représenté sous la forme (http://LienVersLeSiteDrupal/cron/%3Ckey%3E)

- On peut aussi déterminer l'intervalle de temps d'exécution du cron (1 heure, 3 heures, 6 heures ...) ce qui va permettre le lancement des différentes tâches planifiées périodiquement.


- Ultimate cron est utilisé pour permettre l'exécution des différentes taches cron indépendamment des autres (si une erreur est produite lors de l'éxecution de l'une des    taches cron, cela n'affecte pas l'execution des autres tâches) et chaque tâche a son propre intervalle de temps d'exécution.

- On peut accéder à la liste des différentes tâches cron comme on peut aussi exécuter n'importe quelle tâche manuellement sur la page '/admin/config/system/cron/jobs'.
lien vers le projet : "https://www.drupal.org/project/ultimate_cron"

***Custom cron tasks***
- Pour créer une tache périodique qui s'exécute lors de l'execution de cron, il suffit d'implémenter hook_cron.
- On peut aussi créer un fichier yml dans le fichier config/install du module.
    On définit par la suite un callback qui est une fonction qui permet de définir la tâche à exécuter.


***PS :*** Supposant que l'intervalle de temps d'exécution du cron est 3 heures. En effet, le cron n'est pas exécuté toutes les 3 heures, mais à chaque fois qu'un                      utilisateur visite le site Drupal, Drupal fait la différence entre le temps de cette visite et le temps de la dernière exécution du cron et si cette différence est supérieure ou égale à 3 heures le cron sera exécuté.




## LANCEMENT DU CRON DEPUIS LE SERVEUR

- Le fichier permettant de planifier l'exécution des différentes taches cron est appelé crontab
- Ce fichier est manipulé via les commandes :
    crontab -l : Pour afficher le contenu du fichier crontab
    crontab -r : Pour supprimer toutes les actions du fichier crontab
    crontab -e : Pour modifier / ajouter les actions du fichier crontab

Voici de manière schématique la syntaxe à respecter d'un crontab:

	# +---------------- minute (0 - 59)
	# |  +------------- hour (0 - 23)
	# |  |  +---------- day of month (1 - 31)
	# |  |  |  +------- month (1 - 12)
	# |  |  |  |  +---- day of week (0 - 6) (Sunday=0)
	# |  |  |  |  |
	  *  *  *  *  *  command to be executed

- Le fichier crontab est constitué de plusieurs lignes. Chaque ligne correspond à une action.
- Prenons l'exemple suivant :

    mm hh jj MMM JJJ [user] tâche > log

    mm : minutes (00-59).
    hh : heures (00-23) .
    jj : jour du mois (01-31).
    MMM : mois (01-12 ou abréviation anglaise sur trois lettres : jan, feb, mar, apr, may, jun, jul, aug, sep, oct, nov, dec).
    JJJ : jour de la semaine (1-7 ou abréviation anglaise sur trois lettres : mon, tue, wed, thu, fri, sat, sun).
    user (facultatif) : nom d'utilisateur avec lequel exécuter la tâche.
    tâche : commande à exécuter.
    \> log (facultatif) : redirection de la sortie vers un fichier de log.

- Pour chaque unité, on peut utiliser les notations suivantes :
	1-5 : les unités de temps de 1 à 5.
	*/6 : toutes les 6 unités de temps (toutes les 6 heures par exemple).
	2,7 : les unités de temps 2 et 7.

- On peut donc planifier l'exécution du cron via le crontab en ajoutant la ligne :

    `* * * * *  wget -O - -q -t 1 http://LienVersLeSiteDrupal/cron/<key>`

    La commande ci-dessus demande au serveur d'accéder à l'url http://LienVersLeSiteDrupal/cron/%3Ckey%3E ce qui va permettre d'effectuer une exécution du cron.
rappel : L'url peut être obtenu en naviguant vers la page ‘/admin/config/system/cron’.

 - Ou bien en utilisant drush :

	 `* * * * *    /usr/local/bin/drush cron-run --uri=http://example.com --root=/path/to/drupal`

   Cette commande permet d'exécuter cron en utilisant drush, a savoir que drush n'est pas toujours installé dans /usr/local/bin/drush.
    On peut déterminer son emplacement en utilisant la commande : which drush


   Comme on peut aussi executer une tâche cron spécifique via la commande :
   `drush cron-run cron_job_name`
