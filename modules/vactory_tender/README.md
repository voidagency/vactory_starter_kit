# Vactory tenders
#### Ce module permet de créer:
* Le type de contenu **Appel d'offre**
* Un webform **Appel d'offre** pour collecter les informations de l'utilisateur avant le téléchargement de dossier.

#### Installation de module:
`drush en vactory_tender`

#### Configuration:
La configuration de ce module se reside dans activer ou desactiver l'utilisation de webform avant le téléchargement
d'un dossier, l'operation d'activer/désactiver l'utilisation de formulaire s'effectue au niveau de fichier
`modules/vactory/vactory_tender/config/install/vactory_tender.settings.yml`
       
    show_form: true

Changer la valeur associée au paramètre `show_form` à `false` pour descativer l'utilisation de formulaire.

##NB
Après chaque modification de configuration de module pensez à reverter la feature:

`drush fr vactory_tender -y`



#### Maintainers
Brahim KHOUY <b.khouy@void.fr>