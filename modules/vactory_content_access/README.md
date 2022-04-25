# Vactory Content Access
Custom module for manage content (nodes) access on node edit/add form.

### Installation
 `drush en vactory_content_access -y`
 
### Configuration
1. After module installation go to module settings form under: 

`/admin/config/system/content-access-configuration`

2. Then for each existing content type you could enable access content feature,
also you could specify the redirect type in case the node was not accessible.

3. Go to the concerned node edit page, and under "CONTENT ACCESS SETTINGS" field
group you could manage your access rules:
 - Access by users group: you could create new groups of users under user the new
groups taxonomy and then go to user edit page and select the user group there.
 The node will be accessible only for users which belongs to one of selected groups.
 - Access by user role: Select the roles that the user should have (at least one of)
to access the node.
 - Access for specific users: Use the autocomplete user reference field to choose 
specific users to access the node
 - Custom access key: Enter a string key to use in hook_vactory_content_access_alter()
custom hook to manage node access programmatically, the hooks should alter the 
boolean variable $is_accessible under some very specific conditions and so on it will
override all previous roles.

Example of hook_vactory_content_access_alter implementation in vactory_blog module:

    /**
     * Implements hook_vactory_content_access_alter().
     */
    function vactory_blog_vactory_content_access_alter(&$is_accessible, $key, \Drupal\node\NodeInterface $node) {
      if ($key === 'void_custom_key') {
        // Prevent access when the node is unpublished.
        if ($node->bundle() === 'vactory_blog' && !$node->isPublished()) {
          $is_accessible = FALSE;
        }
      }
    }

### Maintainers
Brahim KHOUY <b.khouy@void.fr>