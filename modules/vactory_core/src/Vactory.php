<?php

namespace Drupal\vactory_core;

use Drupal;
use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Core\Entity\Entity;
use Drupal\node\NodeInterface;
use Drupal\twig_tweak\TwigExtension;

/**
 * Defines a route controller for BlockManager.
 */
class Vactory {

  /**
   * Return render block by block machine name.
   *
   * @param string $block_id
   *   The block id.
   *
   * @return bool|html
   *   Rendered Block.
   */
  public static function getRenderBlock(string $block_id) {
    $block = Block::load($block_id);
    if ($block) {
      if ($block) {
        $variables = \Drupal::entityTypeManager()
          ->getViewBuilder('block')
          ->view($block);
        if ($variables) {
          return render($variables);
        }
      }
    }

    $block = self::getBlockById($block_id);
    return $block;
  }

  /**
   * Return render block by Delta.
   *
   * @param string $delta
   *   Delta.
   *
   * @return bool|null
   *   Rendered block
   */
  public static function getBlockByDelta(string $delta) {
    $block = Block::load($delta);

    if ($block) {
      $variables = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($block);

      if ($variables) {
        return render($variables);
      }
    }
    return FALSE;
  }

  /**
   * Return render block by Block id.
   *
   * @param string $bid
   *   Block id.
   *
   * @return array|null
   *   Rendered block
   */
  public static function getBlockByBid($bid) {
    $block = BlockContent::load($bid);
    if (isset($block) && !empty($block)) {
      $render = \Drupal::entityTypeManager()
        ->getViewBuilder('block_content')
        ->view($block);
      return isset($render) ? $render : FALSE;
    }
    return FALSE;
  }

  /**
   * Get the set or default image uri for a file image field (if either exist).
   *
   * @param \Drupal\Core\Entity\Entity $entity
   *   Entity Object.
   * @param string $fieldName
   *   Entity Field name.
   *
   * @return null|string
   *   Image URI if it exists.
   */
  public static function getImageUri(Entity $entity, string $fieldName) {
    $image_uri = NULL;
    if ($entity->hasField($fieldName)) {
      try {
        $field = $entity->{$fieldName}; //Try loading from field values first.
        if ($field && $field->target_id) {
          $file = File::load($field->target_id);
          if ($file) {
            $image_uri = $file->getFileUri();
          }
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('get_image_uri')->notice($e->getMessage(), []);
      }

      // If a set value above wasn't found, try the default image.
      if (is_null($image_uri)) {
        try {
          // Loading from field definition.
          $field = $entity->get($fieldName);
          if ($field) {
            // From the image module /core/modules/image/ImageFormatterBase.php
            // $default_image = $test->fieldDefinition->getFieldStorageDefinition()->getSetting('default_image');
            $default_image = $field->getSetting('default_image');
            if ($default_image && $default_image['uuid']) {
              // $default_imageFile = \Drupal::entityManager()
              // ->loadEntityByUuid('file', $default_image['uuid']));
              // See https://www.drupal.org/node/2549139
              // entityManager is deprecated.
              // Use entity.repository instead.
              $entityrepository = Drupal::service('entity.repository');
              $default_imageFile = $entityrepository->loadEntityByUuid('file', $default_image['uuid']);
              if ($default_imageFile) {
                $image_uri = $default_imageFile->getFileUri();
              }
            }
          }
        }
        catch (\Exception $e) {
          \Drupal::logger('get_image_uri')->notice($e->getMessage(), []);
        }
      }
    }

    return $image_uri;
  }

  /**
   * Get block by Id.
   *
   * @param int $bid
   *   The block id.
   *
   * @return mixed
   *   Rendered Block.
   */
  public static function getBlockById(int $bid) {
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];
    $plugin_block = $block_manager->createInstance($bid, $config);
    $render = $plugin_block->build();

    return $render;
  }

  /**
   * Get current page title.
   *
   * @return mixed
   *   The page's title.
   */
  public static function getCurrentTitle() {
    $request = \Drupal::request();
    $route = \Drupal::service('current_route_match')->getCurrentRouteMatch()
      ->getRouteObject();
    $title = \Drupal::service('title_resolver')->getTitle($request, $route);

    return $title;
  }

  /**
   * Get content type Taxonomy as array options.
   *
   * @param string $content_type
   *   The content type machine_name to search its taxonomy.
   *
   * @return array
   *   Array of taxonomies related to the content type.
   */
  public static function getTaxonomyList(string $content_type) {
    $terms = [];
    foreach (\Drupal::service('entity_field.manager')->getFieldDefinitions('node', $content_type) as $v => $item) {
      if ($item->getSetting("target_type") === "taxonomy_term") {
        $field_name = $item->get('field_name');
        foreach ($item->getSetting("handler_settings")['target_bundles'] as $key => $value) {
          $terms[$value] = [$value, $field_name];
        }
      }
    }
    return $terms;
  }

  /**
   * Get Terms of a specific Taxonomy field as array options.
   *
   * @param string $taxonomy_field
   *   The target taxonomy field.
   * @param string $content_type
   *   The target content type.
   *
   * @return array
   *   Array of terms.
   */
  public static function getTermsFromTaxonomy(string $taxonomy_field, string $content_type) {
    $tid_terms = [];
    $taxonomy = self::getTaxonomyList($content_type);
    $storage = \Drupal::service('entity_type.manager')
      ->getStorage("taxonomy_term")->loadTree($taxonomy[$taxonomy_field][0]);

    foreach ($storage as $key => $value) {
      $tid_terms[$value->tid] = $value->name;
    }
    return $tid_terms;
  }

  /**
   * Get Terms of a specific Taxonomy field as array options.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The target Node.
   * @param string $field_type
   *   The target Field type.
   *
   * @return string|null
   *   The name of the field of type $field_type if it exists.
   */
  public static function getFieldbyType(NodeInterface $node, string $field_type) {
    foreach ($node->getFields() as $key => $field) {
      $current_type = $field->getFieldDefinition()->getType();
      if (strncmp($field_type, $current_type, strlen($field_type)) == 0) {
        return $key;
      }
    }
    return NULL;
  }

  /**
   * Create a new field to store vcc's loaded nodes.
   *
   * @param string $content_type
   *   The target content type.
   * @param string $field_name
   *   The name of the field you wanna create.
   *
   * @throws Drupal\Core\Entity\EntityStorageException
   */
  public static function createVccField(string $content_type, string $field_name) {
    $field = FieldConfig::loadByName('node', $content_type, $field_name);
    if (empty($field)) {
      $field_storage = FieldStorageConfig::loadByName('node', $field_name);
      if (empty($field_storage)) {
        $field_storage = FieldStorageConfig::create([
          'field_name' => $field_name,
          'entity_type' => 'node',
          'type' => 'field_cross_content',
          'cardinality' => 1,
        ]);
        $field_storage->save();
      }
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $content_type,
        'label' => t('Contenu lié'),
      ]);
      $field->save();

      /* @var \Drupal\Core\Entity\Entity\EntityFormDisplay */
      $entity_form_display = \Drupal::entityTypeManager()
        ->getStorage('entity_form_display')
        ->load('node.' . $content_type . '.default');

      if (!$entity_form_display) {
        $values = [
          'targetEntityType' => 'node',
          'bundle' => $content_type,
          'mode' => 'default',
          'status' => TRUE,
        ];
        \Drupal::entityTypeManager()
          ->getStorage('entity_form_display')
          ->create($values);
      }

      $entity_form_display->setComponent($field_name, [
        'type' => 'options_select',
      ])->save();

      /* @var \Drupal\Core\Entity\Entity\EntityViewDisplay */
      $entity_view_display = \Drupal::entityTypeManager()
        ->getStorage('entity_view_display')
        ->load('node.' . $content_type . '.default');

      if (!$entity_view_display) {
        $values = [
          'targetEntityType' => 'node',
          'bundle' => $content_type,
          'mode' => 'default',
          'status' => TRUE,
        ];
        \Drupal::entityTypeManager()
          ->getStorage('entity_view_display')
          ->create($values);
      }

      $entity_view_display->setComponent($field_name, [
        'label' => 'hidden',
      ])->save();
    }
  }

  /**
   * Render block using machine name or delta.
   *
   * This function use Twig tweak functions.
   *
   * @param string $machine_name
   *   Block machine_name OR Delta.
   * @param array $configuration
   *   Block configuration.
   *
   * @return string|array
   *   Rendered block.
   */
  public static function renderBlock($machine_name, array $configuration = []) {
    $twigExtension = \Drupal::service('twig_tweak.twig_extension');
    $entityManager = \Drupal::service('entity_type.manager');
    $block_storage = $entityManager->getStorage('block_content');

    // Load block by custom machine_name ID.
    // @see modules/vactory/vactory_core/vactory_core.module
    $block = $block_storage->loadByProperties(['block_machine_name' => $machine_name]);

    if (is_array($block) && reset($block) instanceof BlockContent) {
      $block_view = $entityManager->getViewBuilder('block_content')
        ->view(reset($block));
      return $block_view;
    }

    // Load block core.
    $block = self::getBlockByDelta($machine_name);
    if ($block) {
      return $block;
    }

    $block = self::getBlockByBid($machine_name);
    if ($block) {
      return $block;
    }

    $block = $twigExtension->drupalBlock($machine_name, $configuration);
    if ($block && is_array($block) && isset($block['#plugin_id']) && $block['#plugin_id'] !== 'broken') {
      return $block;
    }

    // $block = $twigExtension->drupalBlock($machine_name, $configuration);
    // try {
    // if ($block) {
    // return $block;
    // }
    //
    // $block = self::getBlockByBid($machine_name);
    // if ($block) {
    // return $block;
    // }
    // }
    // catch (\Exception $exception) {
    // \Drupal::logger('vactory_core')->notice($exception->getMessage());
    // }
  }

  /**
   * Render View using machine name and display id.
   *
   * @param string $view
   *   Views machine_name.
   * @param string $display
   *   Views display id.
   *
   * @return string
   *   Rendered view.
   */
  public static function renderView($view, $display) {
    if (!isset($display) && empty($display)) {
      throw new \InvalidArgumentException(sprintf('For views you need to specify the view display (at 3 param)'));
    }
    $views_render = views_embed_view($display, $view);
    return isset($views_render) ? $views_render : views_embed_view($view, $display);
  }

  /**
   * Render Menu by id.
   *
   * @param mixed $menu_id
   *   Menu ID.
   *
   * @return html
   *   Rendered  menu.
   */
  public static function renderMenu($menu_id) {
    $function = new TwigExtension();
    return $function->drupalMenu($menu_id);
  }

  /**
   * Render Forms using form_id or form_class.
   *
   * Example for form_class : Drupal\\search\\Form\\SearchBlockForm.
   *
   * @param string $type
   *   Type of form custom or contrib.
   * @param string $form_id
   *   Form id or class.
   *
   * @return html
   *   Html.
   */
  public static function renderForm($type, string $form_id) {
    $function = new TwigExtension();
    if (!isset($form_id) && empty($form_id)) {
      throw new \InvalidArgumentException(sprintf('For Form you need to specify the form_id or form namespace like this -- Drupal\search\Form\SearchBlockForm -- for custom forms (at 3 param)'));
    }
    // For custom forms (programmatically forms)
    if ($type == 'custom') {
      /* $namespace = str_replace(
      '/\/', '/\\/', "Drupal\\search\\Form\\SearchBlockForm"
      ); */
      return $function->drupalForm($form_id);
    }
    // For contrib forms (by contrib modules like webform)
    return $function->drupalEntity($type, $form_id);
  }

  /**
   * Render entity using entity type and entity id.
   *
   * @param string $type
   *   Entity type.
   * @param string $id
   *   Entity ID.
   * @param string $view_mode
   *   View mode.
   *
   * @return array|html
   *   Html.
   */
  public static function renderEntity($type, $id, $view_mode = NULL) {
    $function = new TwigExtension();
    if (!isset($id) && empty($id)) {
      throw new \InvalidArgumentException(sprintf('For Entity you need to specify the ID -- example (entity, node, 1) (at 3 param)'));
    }
    return $function->drupalEntity($type, $id, $view_mode);
  }

}
