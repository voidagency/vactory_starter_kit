# Vactory Search

Vactory Search et un module qui contient tout ce qui est lié a la recherche sur drupal, la configuration de `search page` , `search API`, `SOLR` et la `vue`.

ans ce module on trouve aussi notre custom code pour la partie recherche et nos propre templates.`

## Table of Contents
 * [Requirements](#requirements)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Extend](#extend)
 * [Api](#api)
 * [Troubleshooting & FAQ](#troubleshooting-faq)
 * [Maintainers](#maintainers)

## Requirements

Ce module dépend du module contrib `Searche API` pour la configuration du serveur, facette ....
aussi le module `Views` qui est un module du core mais aussi avec tous ces modules contrib exemple `better exposed filter`.
il a aussi besoin le module contrib `Database search` ce dérnier inclus avec le module `Search API` et il offre une implémentation de l'API de recherche qui utilise des tables de base de données pour indexer le contenu.

```hint|directive
Pour telecharger Search API > https://www.drupal.org/project/search_api
ou par la commande drush en -y search_api
```

## Installation

    drush en vactory_search

## Configuration

Le module contien déja une configuration initiale.
La configuration de `Search page` pour la recherche simple de drupal ce fait au niveau du back office via l'url `admin/config/search/pages`, c'est la qu'on fait la re-indexaion du site au niveaux de la base de donné.
Pour la configuration du serveur via `Search API`, tous ce qui est lier au serveur (creation, modification ou supression) exemple `Facette` ou `SOLR` ce fait via l'url admin/config/search/search-api`.

## Extends
Aucun

##  API
Aucun

## Theming

Pour la partie theming on trouve juste la template global de la page recherche (une vue) pour l'instant.

## Troubleshooting & FAQ
#### Aucun

## Maintainers

* Oualid Ezzerrouqi: <o.ezzerrouqi@void.fr>
