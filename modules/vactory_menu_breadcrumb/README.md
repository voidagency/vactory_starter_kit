


## Vactory Menu Breadcrumb

Ce module permet de générer la file d'ariane de la page courane à partir de sa position dans les menus de drupal.
Si la page courante n'est trouvée ni dans les menus cochés dans la configuration ni dans le menu de navigation (menu par défaut), la file d'ariane est générée à partir de l'url de la page.

### Table of Contents

- [Requirements](https://bitbucket.org/adminvoid/vactory8/src/10e6af8110b02ffd0359c1ba2caaf82362079ccd/modules/vactory/vactory_academy/README.md?at=vactory-academy&fileviewer=file-view-default#Requirements)
- [Recommended modules](https://bitbucket.org/adminvoid/vactory8/src/10e6af8110b02ffd0359c1ba2caaf82362079ccd/modules/vactory/vactory_academy/README.md?at=vactory-academy&fileviewer=file-view-default#recommended-modules)
- [Installation](https://bitbucket.org/adminvoid/vactory8/src/10e6af8110b02ffd0359c1ba2caaf82362079ccd/modules/vactory/vactory_academy/README.md?at=vactory-academy&fileviewer=file-view-default#installation)
- [Configuration](https://bitbucket.org/adminvoid/vactory8/src/10e6af8110b02ffd0359c1ba2caaf82362079ccd/modules/vactory/vactory_academy/README.md?at=vactory-academy&fileviewer=file-view-default#configuration)
- [extend](https://bitbucket.org/adminvoid/vactory8/src/10e6af8110b02ffd0359c1ba2caaf82362079ccd/modules/vactory/vactory_academy/README.md?at=vactory-academy&fileviewer=file-view-default#extend)
- [Api](https://bitbucket.org/adminvoid/vactory8/src/10e6af8110b02ffd0359c1ba2caaf82362079ccd/modules/vactory/vactory_academy/README.md?at=vactory-academy&fileviewer=file-view-default#api)
- [Troubleshooting &FAQ](https://bitbucket.org/adminvoid/vactory8/src/10e6af8110b02ffd0359c1ba2caaf82362079ccd/modules/vactory/vactory_academy/README.md?at=vactory-academy&fileviewer=file-view-default#Troubleshooting&FAQ)
- [Maintainers](https://bitbucket.org/adminvoid/vactory8/src/10e6af8110b02ffd0359c1ba2caaf82362079ccd/modules/vactory/vactory_academy/README.md?at=vactory-academy&fileviewer=file-view-default#Maintainers)

### Requirements

Aucun

### Installation

- Activation du module via la commande drush suivante : drush en vactory_menu_breadcrumb 

### Configuration

- La configuration du module se fait dans : Administer -> Configuration -> User Interface (admin/config/user-interface/vactory-menu-breadcrumb). 
- On peut configurer la file d'ariane générée depuis les menus ainsi que celle générée depuis l'url de la page.
- Les options disponibles dans la page de configuration :
	- Options de génération depuis les menus :
		- Activer ou désactiver la fonctionnalité de générer la file d'ariane depuis les menus.
		- Activer ou désactiver la file d'ariane pour les pages d'administration.
		- Si la page courante est présente dans l'un des menus, l'ajouter à la file d'ariane générée.
		- Afficher la page courante dans notre file d'ariane comme étant un lien ou bien juste un texte brute.
		- Si la page courante est un membre d'une taxonomie dont le terme se trouve dans l'un des menus avec la colonne 'Taxonomy Attachment' cochée, le titre de ce terme est affiché dans la file d'ariane comme étant un lien et aussi le dérnier élement de la file.
		- Retirer le lien vers la page d'acceuil de la file d'ariane.
		- Ajouter le lien vers la page d'acceuil dans la file d'ariane. 
		- Utiliser le nom du site aulieu de 'Home' comme titre du lien vers la page d'acceuil.
		- Activer ou désactiver les menus qui seront utilisés pour générer la file d'ariane. Si aucun menu n'est sélectionné, Drupal va chercher par défaut dans le menu de navigation.
	- Options de génération depuis l'url :
		- Afficher les chemins invalides dans la file d'ariane, ces chemins seront affichés sous forme de texte brute.
		- Saisir les chemins à exclure lors de la génération de la file d'ariane.
		- Ajouter ou retirer le lien vers la page d'acceuil dans la file d'ariane. 
		- Définir le titre du lien vers la page d'acceuil à afficher dans la file d'ariane.
		- Ajouter la page courant à la file d'ariane comme étant le dérnier élement de la file.
		- Utiliser le vrai titre de la page s'il existe aulieu de l'extraire du chemin.
		- Afficher le titre de la  page courante comme étant un lien ou bien juste un texte brute.
		- Ajouter la langue présente dans le chemins de la page comme élement de la file d'ariane.
		- Utiliser le titre de la page se trouvant dans le menu comme fallback.
		- Retirer les segments identiques de la file d'ariane.
- Pour accéder à la configuration des menus drupal : Administration -> Structure (admin/structure/menu)

### Extends

Aucun

### API

Aucun

### theming

Aucun

### Troubleshooting & FAQ

Aucun

### Maintainers

Bouharras Rida
<r.bouharras@void.fr>
