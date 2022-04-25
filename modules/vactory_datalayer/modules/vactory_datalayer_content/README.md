
## Vactory DataLayer Content



### Installation

Activation du module via drush :  `drush en vactory_datalayer_content`

### Configuration

- Positionner le bloc "vactory_dataLayer_content_block"
- Configurer le bloc en remplissant le champ "DataLayer Content properties" 
exemple: 
```javascript
{
         "title": "[v_datalayer_content:title]",
         "date": "[v_datalayer_content:date]",
         "auteur": "[v_datalayer_content:auteur]"
}
```
- Activer le bloc pour le type de contenu concerné (Visibilité)

### Administer les tokens

##### Par config:

- /admin/config/datalayer_content
- exemple de configuration:

```javascript
[
{
  "token_name": "My field",
  "token_key": "my_field",
  "field_machine_name": "field_my_field",
  "cible": "value"
},
{
  "token_name": "My field",
  "token_key": "my_field",
  "field_machine_name": "field_my_field",
  "cible": "target_id",
  "is_taxonomy": true
}
] 
```

##### Par code:

- définir notre propre token

```
function hook_token_info() {
  $content['field'] = [
    'name' => t("My field"),
  ];
  return [
    'tokens' => [
      'v_datalayer_content' => $content,
    ],
  ];
}
```


- définir la logique qui permet de remplecer les tokens déclaré.

```
function hook_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'v_datalayer_content') {
    
   $node = \Drupal::routeMatch()->getParameter('node');

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'field':
          $value = $node->get('field')->value;
          $replacements[$original] = $value;
          break;

      }
    }
  }

  return $replacements;
}
```
