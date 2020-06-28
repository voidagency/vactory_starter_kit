L'import et export des blocks custom se fait via le module contribué `default_content`

[Lire la documentation](https://www.drupal.org/docs/8/modules/default-content)

## Export
Pour exporter un block dans un module nommé `default_content_test`, il faut procéder comme suite:

- Créer le répertoire: `modules/default_content_test/content/block_content`
- Créer un block custom et renseigner le champ 'Machine Name' > par exemple `card_widget`
- Une fois le block crée, il faut récupérer l'ID du block > par exemple `456`
- Lancer la commande Drush: drush dce block_content 456 --file=modules/custom/default_content_test/content/block_content/card_widget.json

## Import

Pour importer les blocks depuis un module:

- `drush php`
- `\Drupal::service('default_content.importer')->importContent('default_content_test');`

## Utilisation dans les templates Twig

Pour récupérer les blocks ont passe par la function Twig `vactory_render`

### Props

 - __`param1: string`__ Le type de l'objet à récupérer > exemple: block, entity, views.
 - __`param2: string`__ L'identifiant de l'objet
 - `param3: string` Des options supplémentaires

```hint
Pour trouver les arguments nécessaires, utilisez l'interface __`admin/structure/block/vactory/library/vactory`__.
```

#### Exemples

##### **Views**

```code
 ...
 {{ vactory_render('views', 'vactory_news', 'listing_one_column') }}
 ...
```

##### **Block**

###### **> Par Nom machine**
```code
 ...
 {{ vactory_render('block', 'system_branding_block'') }}
 ...
```

###### **> Par Delta**

```code
 ...
 {{ vactory_render('block', 'globalbanner') }}
 ...
```

###### **> Par Bid**

```code
 ...
 {{ vactory_render('block', '5') }}
 ...
```

##### **Menu**

```code
...
{{ vactory_render('menu', 'tools') }}
...
```
##### **Form**
###### **> Contrib**
```code
 ...
 {{ vactory_render('form', 'webform', 'contact') }}
 ...
```
###### **> Custom**
```code
 ...
 {{ vactory_render('form', 'custom', 'Drupal\\search\\Form\\SearchBlockForm') }}
 ...
```

##### **Entity**
```code
 ...
 {{ vactory_render('entity', 'node', 1) }}
 ...
```
