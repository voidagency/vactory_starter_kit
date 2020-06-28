<?php

namespace Drupal\vactory_core;

use Drupal\simplify_menu\MenuItems as MenuItemsBase;

/**
 * Class MenuItems.
 *
 * @package \Drupal\simplify_menu
 */
class MenuItems extends MenuItemsBase {

  /**
   * Menu drupal id.
   *
   * @var string
   */
  protected $menuId;

  /**
   * Menu drupal field definitions.
   *
   * @var array
   */
  protected $menuFields;

  /**
   * Map menu tree into an array.
   *
   * @param array $links
   *   The array of menu tree links.
   * @param string $submenuKey
   *   The key for the submenu to simplify.
   *
   * @return array
   *   The simplified menu tree array.
   */
  protected function simplifyLinks(array $links, $submenuKey = 'submenu') {
    $result = [];
    $current_user_roles = \Drupal::currentUser()->getRoles();
    foreach ($links as $item) {
      $url_object = $item->link->getUrlObject();

      $entity = NULL;
      $menu_plugin_id = $item->link->getPluginId();

      // Pull the path from the menu link content.
      if (strpos($menu_plugin_id, 'menu_link_content') === 0) {
        list(, $uuid) = explode(':', $menu_plugin_id, 2);
        /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
        $entity = \Drupal::service('entity.repository')
          ->loadEntityByUuid('menu_link_content', $uuid);
        $entity = \Drupal::service('entity.repository')
          ->getTranslationFromContext($entity);
      }

      if ($entity && !self::checkMenuRole($entity, $current_user_roles)) {
        continue;
      }
      $viewBuilder = \Drupal::entityTypeManager()
        ->getViewBuilder('menu_link_content');

      $simplifiedLink = [
        'text'         => (isset($entity) && !empty($entity->get('title'))) ? $entity->get('title')->first()->getValue()['value'] : $item->link->getTitle(),
        'url'          => (isset($entity) && !empty($entity->getUrlObject()
            ->toString())) ? $entity->getUrlObject()
          ->toString() : $item->link->getUrlObject()->toString(),
        'options'      => [],
        'fields'       => [],
        'active_trail' => FALSE,
        'active'       => FALSE,
      ];

      if ($options = $url_object->getOptions()) {
        $simplifiedLink['options'] = $options;
      }

      // Add fields to simplified module.
      if ($entity) {
        foreach ($entity->getFields() as $key => $field) {
          $output = $viewBuilder->viewField($field, 'full');
          $output['#cache']['tags'] = $entity->getCacheTags();
          $simplifiedLink['fields'][$key] = $output;
        }
      }

      $current_path = \Drupal::request()->getRequestUri();
      if ($current_path == $simplifiedLink['url']) {
        $simplifiedLink['active'] = TRUE;
      }

      $plugin_id = $item->link->getPluginId();
      if (isset($this->activeMenuTree[$plugin_id]) && $this->activeMenuTree[$plugin_id] == TRUE) {
        $simplifiedLink['active_trail'] = TRUE;
      }

      if ($item->hasChildren) {
        $simplifiedLink[$submenuKey] = $this->simplifyLinks($item->subtree);
      }
      $result[] = $simplifiedLink;
    }

//    dpm($result);
    return $result;
  }

  /**
   * Get header menu links.
   *
   * @param string $menuId
   *   Menu drupal id.
   *
   * @return array
   *   Render array of menu items.
   */
  public function getMenuTree($menuId = 'main') {
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $this->menuId = $menuId;
    $this->menuFields = $entityFieldManager->getFieldDefinitions('menu_link_content', $menuId);

    return parent::getMenuTree($menuId);
  }

  /**
   * Check if user have a role access or not.
   *
   * @param $menu_item
   *
   * @return bool
   */
  public function checkMenuRole($menu_item, $current_user_roles) {
    $menu_show_roles = $menu_item->get('menu_role_show_role')->getValue();
    $validate = TRUE;
    if (!empty($menu_show_roles) && empty(self::searchMultidimensionalArray($current_user_roles, $menu_show_roles))) {
      $validate = FALSE;
    }

    return $validate;
  }

  /**
   * Search array in array Multidimensionaly
   *
   * @param array $array_data
   *  The array sought.
   * @param array $array_base
   *  The array search in.
   *
   * @return array
   */
  protected function searchMultidimensionalArray(array $array_data, array $array_base) {
    $array_base = array_column($array_base, 'target_id');
    $data = [];
    foreach ($array_data as $value) {
      if (in_array($value, $array_base)) {
        $data[] = $value;
      }
    }
    return $data;
  }
}
