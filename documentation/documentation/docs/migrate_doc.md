
## Migration du contenu à base des modules migrate_plus + migrate_tools + migrate_source_csv
Cette documentation à pour objectif de décrir le comportement des migrations qui utilisent les modules suivants :
* migrate_plus
* migrate_tools
* migrate_source_csv

## Installation

il est préférable de regrouper l'ensemble des migrations dans un seul module de configuration, du coup l'installation de ce module va
automatiquement installer les autres modules dont il dépend .

## Configuration

Dans un premier temps il faudra créer un fichier de configuration de la migration qui va contenir les instructions de la migration le fichier
doit être nommé comme suit : migrate_plus.migration.Migration_id.yml où Migration_id est l'id de la migration qui sera aussi mentionné à l'intérieur du fichier yml .
Passant maintenant au contenu du fichier de config :
 (il faudra faire attention aux espaces puisque le format yml ne tolère pas les fautes d'indentation !!)


     langcode: en
     status: true
     dependencies: { }
     #cet argument doit contenir les dépendances de votre  migration si jamais vous en avez
     id: Migration_id
     # l'id de la migration c'est le même id que contient le nom du fichier de config
     migration_group: default
     # default est la valeur par défaut des groupes de migration si jamais vous voudriez créer un nouveau groupe RDV sur le backoffice dans la partie structure
    label: 'Titre de la migration'

##### La configuration de la source des données de la migration qui sera dans notre cas un fichier CSV
	 source:
	  plugin: csv
	  delimiter: ','
	  # delimiteur peut être parfois ';'
	  path: Chemin vers le fichier CSV
	  header_row_count: 1
	  # nombre de lignes de du header CSV
	  keys:
	  # définition des clés de la migration à partir du header CSV
	    - ID
	  column_names:
	  # une liste des colonnes qu'on va importer
	    0:
	      header de la colonnes: description
	  # le 0 à remplacer par l'indice de la colonne sur le CSV ,l'indexation commence avec 0

##### Configuration du process de la migration
	process:
	  # généralement la partie process contient les instructions de mapping des champs à migrer avec leur correspondants sur le CSV
	  field_machine_name: header de la colonne concerné
	  type:
	    plugin: default_value
	    default_value: entity_machine_name  # à remplacer par le nom de l'entité
##### Configuration de la destination de la migration
	destination:
	  plugin: 'entity:entity_type'
	  default_bundle: bundle_machine_name
	migration_dependencies: {  }`



## Plugins
Les migrations Drupal obtiennent leur force depuis leurs plugins.. Il existe généralement 2 types de plugins à savoir :
- les plugins SOURCE utilisés lors de la configuration de la sources des données par exemple le plugin CSV qui nous permet d'agir sur la configuration de la source des données , ceci dit qu'on fera appel à ce plugin pour le traitement de chaque ligne de la source.
- les plugins PROCESS quant à eux servent généralement à convertir une donnée bien spécifique lors du traitement par exemple (changer le type d'une donnée , changer le format ...etc) on fait appel à ce genre de plugin pour agir sur une entrée du CSV bien précise (une colonne)

A noter qu'il existe beaucoup de plugins prédéfini à consulter sur :
[Source plugins](https://www.drupal.org/docs/8/api/migrate-api/migrate-source-plugins)
[Process plugins](https://www.drupal.org/docs/8/api/migrate-api/migrate-process-plugins)
On peut bien-sur créer nos propres plugins en respectant la structure de fichier suivante :
  - Pour les source plugins : src/Plugin/migrate/source/NomdeLaClasse.php ( A noter que cette classe doit étendre d'une classe source existente par exemple la classe ***CSV*** définit par le modules migrate_source_csv et surcharger par la suite la méthode prepareRow)
  - Pour les process plugins : src/Plugin/migrate/process/NomdeLaClasse.php (La classe doit étendre de la classe ***ProcessPluginBase*** et surcharger la méthode transform)

## Exécution et Troubleshooting

#### Lancement de la migration : `drush mim Migration_id`
##### Les options recommandées :
   - -\-update : pour mettre à jour les entités précédemment importés si jamais y'a un changement à effectuer
    - -\-feedback : pour voir le status d'exécution de chaque ligne de la source
    - -\-limit=X : X à remplacer par un nombre si jamais vous voudriez faire un teste de migrations pour un nombre limité de données
#### Status des migrations : `drush ms`
#### Changer le status d'une migration : `drush mrs Migration_id`
###### Cette commande est généralement utilisée lorsqu'une exception est levée lors de l’exécution de la migration
#### Consulter les messages d'erreur d'une migration : `drush mmsg Migration_id`


