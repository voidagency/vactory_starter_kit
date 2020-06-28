
## Vactory Espace Privé

Ce module a été développé afin de personnaliser les chemins utilisateur 
(routes) natifs de Drupal à savoir:

* /user/login
* /user/register
* /user/password (Réinitialiser son mot de passe)
* /user/UID (Voir son profil)
* /user/UID/edit (Formulaire de modification de son profil)

## Table ds matière
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Theming](#theming)
 * [Maintainers](#Maintainers)

### Installation

Activation du module via drush :  `drush en vactory_espace_prive`

### Configuration

La page de configuration de module est accessible via le chemin suivant:
  `/admin/config/people/vactory_espace_prive`

Ou bien en utilisant le toolbar: 
Manage > Configuration > People > Vactory Espace Privé Settings

Vous pouvez configurer le module pour préciser:

#### Les chemins alternatifs à utiliser

Vous pouvez exploiter les champs texte pour saisir le chemin souhaité
pour chaque route utilisateur.

#### Le mode de redirection

Vous pouvez choisir le mode de redirection qui vous convient pour les 
chemins utilisateur natifs de Drupal, Vous avez
le choix entre deux options soit faire une redirection 404 soit 
une redirection vers le nouveau chemin associé.
 
### Theming

Aucune personnalisation au niveau de la partie theming, vous pouvez 
toujours utiliser les templates déclarées au niveau du theme vactory:

 `themes/vactory/templates/user/*` 

### Maintainers

Brahim KHOUY 
<b.khouy@void.fr>
