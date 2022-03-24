
## Vactory DataLayer Authentication



### Installation

Activation du module via drush :  `drush en vactory_datalayer_authentication`

### Configuration

- Positionner le bloc "vactory_dataLayer_authentication_block"
- Configurer le bloc en remplissant le champ "DataLayer Authentication properties" 
exemple: 
```javascript
{
         "first_name": "[v_datalayer_auth:first_name]",
         "status": "[v_datalayer_auth:status]"
}
```
- le bloc doit être visible sur toutes le pages.

### Administer les tokens

##### Par config:

- /admin/config/datalayer_auth
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
  $user['field'] = [
    'name' => t("My field"),
  ];
  return [
    'tokens' => [
      'v_datalayer_auth' => $user,
    ],
  ];
}
```


- définir la logique qui permet de remplecer les tokens déclaré.

```
function hook_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'v_datalayer_auth') {
    
   $current_user = \Drupal::currentUser();
   $user = NULL;
   if ($current_user->isAuthenticated()) {
     $user = User::load($current_user->id());
   }

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'field':
          $value = $user->get('field')->value;
          $replacements[$original] = $value;
          break;

      }
    }
  }

  return $replacements;
}
```
