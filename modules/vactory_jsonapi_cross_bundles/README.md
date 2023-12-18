# Vactory Jsonapi Cross Bundles

Le module Vactory Jsonapi Cross Bundles étend les fonctionnalités de JSONAPI
en permettant la demande de plusieurs bundles en une seule requête.

## Installation
Activation du module via la commande drush suivante :

    drush en vactory_jsonapi_cross_bundles -y

## Utilisation

Utilisation des endpoints JSONAPI pour demander une ressource mixte

- Endpoint
> /{jsonapi_prefix}/{entity}
- Ajouter le filtres pour préciser les bundles désirés
> ?filter[bundles][condition][path]={bundleEntityType}.meta.drupal_internal__target_id  
> &filter[bundles][condition][operator]=IN  
> &filter[bundles][condition][value][]=bundle1  
> &filter[bundles][condition][value][]=bundle1

- Exemple
> /api/node  
> ?filter[bundles][condition][path]=type.meta.drupal_internal__target_id  
> &filter[bundles][condition][operator]=IN  
> &filter[bundles][condition][value][]=vactory_news  
> &filter[bundles][condition][value][]=vactory_publication


Créer une ressource mixte en utilisant dynamic field

- Utilser l'élement json_api_cross_bundles selon la structure suivante
```
fields:
  collection:
    type: json_api_cross_bundles
    label: 'JSON:API'
    options:
      '#required': TRUE
      '#default_value':
        resource:
          entity_type: [entity]
          bundle:
            - [bundle_1]
            - [bundle_2]
            - ...
            - [bundle_n]

        filters:
          # Utiliser les paramètres JSONAPI
          # fields, filter, sort, include ...
```
- Exemple (collection qui mélange le deux type de contenu News et publication)
```
fields:
  collection:
    type: json_api_cross_bundles
    label: 'JSON:API'
    options:
      '#required': TRUE
      '#default_value':
        id: "vactory_news_publication"
        resource:
          entity_type: node
          bundle:
            - vactory_news
            - vactory_publication

        filters:
          - fields[node--vactory_news]=drupal_internal__nid,path,title,field_vactory_news_theme,field_vactory_media,field_vactory_excerpt,field_vactory_date
          - fields[taxonomy_term--vactory_news_theme]=tid,name
          - include=field_vactory_publication_theme,field_vactory_news_theme,field_vactory_media,field_vactory_media.thumbnail

          - fields[node--vactory_publication]=drupal_internal__nid,path,title,field_vactory_date,field_vactory_media_document,field_vactory_call_to_action,field_vactory_excerpt,field_vactory_media,field_vactory_publication_theme,field_vactory_tags,field_media_file
          - fields[taxonomy_term--vactory_publication_theme]=tid,name
          - fields[taxonomy_term--tags]=tid,name
          
          - sort[date][path]=field_vactory_date
          - sort[date][direction]=DESC
          
          - page[offset]=0
          - page[limit]=9
          - filter[status][value]=1
          
          - sort[sort-vactory-date][path]=field_vactory_date
          - sort[sort-vactory-date][direction]=DESC

          - fields[file--document]=filename,uri
          - fields[media--file]= field_media_file,uri

          - fields[media--image]=name,thumbnail
          - fields[file--image]=filename,uri
```

Plus d'information:  
https://voidagency.gitbook.io/factory/modules-vactory/untitled/json-api-cross-bundles

### Maintainers
Ismail BOULAANAIT  
<i.boulaanait@void.fr>
