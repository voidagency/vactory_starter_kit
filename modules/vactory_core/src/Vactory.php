<?php

namespace Drupal\vactory_core;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\twig_tweak\TwigTweakExtension;

/**
 * Defines a route controller for BlockManager.
 */
class Vactory {

  /**
   * Twig extension service.
   *
   * @var \Drupal\twig_tweak\TwigTweakExtension
   */
  protected $twigExtension;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Current path stack service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Alias manager service.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * Path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Title resolver service.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    TwigTweakExtension $twigExtension,
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    CurrentPathStack $currentPathStack,
    AliasManagerInterface $aliasManager,
    PathMatcherInterface $pathMatcher,
    EntityRepositoryInterface $entityRepository,
    BlockManagerInterface $blockManager,
    EntityFieldManagerInterface $entityFieldManager,
    CurrentRouteMatch $currentRouteMatch,
    TitleResolverInterface $titleResolver
  ) {
    $this->twigExtension = $twigExtension;
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->currentPathStack = $currentPathStack;
    $this->aliasManager = $aliasManager;
    $this->pathMatcher = $pathMatcher;
    $this->entityRepository = $entityRepository;
    $this->blockManager = $blockManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->titleResolver = $titleResolver;
  }

  /**
   * Return render block by block machine name.
   *
   * @param string $block_id
   *   The block id.
   *
   * @return bool|html
   *   Rendered Block.
   */
  public function getRenderBlock(string $block_id) {
    $block = Block::load($block_id);
    if ($block) {
      if ($block) {
        $variables = $this->entityTypeManager->getViewBuilder('block')
          ->view($block);
        if ($variables) {
          return \Drupal::service('renderer')->render($variables);
        }
      }
    }

    $block = $this->getBlockById($block_id);
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
  public function getBlockByDelta(string $delta) {
    $block = Block::load($delta);

    if ($block) {
      if ($this->isBlockVisible($block)) {
        $variables = $this->entityTypeManager
          ->getViewBuilder('block')
          ->view($block);

        if ($variables) {
          return \Drupal::service('renderer')->render($variables);
        }
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
  public function getBlockByBid($bid) {
    $block = BlockContent::load($bid);
    if (isset($block) && !empty($block)) {
      $render = $this->entityTypeManager
        ->getViewBuilder('block_content')
        ->view($block);
      return isset($render) ? $render : FALSE;
    }
    return FALSE;
  }

  /**
   * Get the set or default image uri for a file image field (if either exist).
   *
   * @param \Drupal\Core\Entity\EntityBase $entity
   *   Entity Object.
   * @param string $fieldName
   *   Entity Field name.
   *
   * @return null|string
   *   Image URI if it exists.
   */
  public function getImageUri(EntityBase $entity, string $fieldName) {
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
              $default_imageFile = $this->entityRepository->loadEntityByUuid('file', $default_image['uuid']);
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
  public function getBlockById(int $bid) {
    $config = [];
    $plugin_block = $this->blockManager->createInstance($bid, $config);
    $render = $plugin_block->build();

    return $render;
  }

  /**
   * Get current page title.
   *
   * @return mixed
   *   The page's title.
   */
  public function getCurrentTitle() {
    $request = \Drupal::request();
    $route = $this->currentRouteMatch->getCurrentRouteMatch()
      ->getRouteObject();
    $title = $this->titleResolver->getTitle($request, $route);

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
  public function getTaxonomyList(string $content_type) {
    $terms = [];
    foreach ($this->entityFieldManager->getFieldDefinitions('node', $content_type) as $v => $item) {
      if ($item->getSetting("target_type") === "taxonomy_term") {
        $field_name = $item->getName();
        if (isset($item->getSetting("handler_settings")['target_bundles'])) {
          foreach ($item->getSetting("handler_settings")['target_bundles'] as $key => $value) {
            $terms[$value] = [$value, $field_name];
          }
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
  public function getTermsFromTaxonomy(string $taxonomy_field, string $content_type) {
    $tid_terms = [];
    $taxonomy = $this->getTaxonomyList($content_type);
    $storage = $this->entityTypeManager
      ->getStorage("taxonomy_term")
      ->loadTree($taxonomy[$taxonomy_field][0]);

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
  public function getFieldbyType(NodeInterface $node, string $field_type) {
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
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createVccField(string $content_type, string $field_name) {
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
      $entity_form_display = $this->entityTypeManager
        ->getStorage('entity_form_display')
        ->load('node.' . $content_type . '.default');

      if (!$entity_form_display) {
        $values = [
          'targetEntityType' => 'node',
          'bundle' => $content_type,
          'mode' => 'default',
          'status' => TRUE,
        ];
        $this->entityTypeManager->getStorage('entity_form_display')
          ->create($values);
      }

      $entity_form_display->setComponent($field_name, [
        'type' => 'options_select',
      ])->save();

      /* @var \Drupal\Core\Entity\Entity\EntityViewDisplay */
      $entity_view_display = $this->entityTypeManager->getStorage('entity_view_display')
        ->load('node.' . $content_type . '.default');

      if (!$entity_view_display) {
        $values = [
          'targetEntityType' => 'node',
          'bundle' => $content_type,
          'mode' => 'default',
          'status' => TRUE,
        ];
        $this->entityTypeManager->getStorage('entity_view_display')
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
  public function renderBlock($machine_name, array $configuration = [], array $attributes = []) {
    $block_storage = $this->entityTypeManager->getStorage('block_content');

    // Load block by custom machine_name ID.
    // @see modules/vactory/vactory_core/vactory_core.module
    $block = $block_storage->loadByProperties(['block_machine_name' => $machine_name]);
    if (is_array($block) && reset($block) instanceof BlockContent) {
      $block_view = $this->entityTypeManager->getViewBuilder('block_content')
        ->view(reset($block));
      return $block_view;
    }

    // Load block core.
    $block = $this->getBlockByDelta($machine_name);
    if ($block) {
      return $block;
    }

    $block = $this->getBlockByBid($machine_name);
    if ($block) {
      return $block;
    }

    $block = $this->twigExtension->drupalBlock($machine_name, $configuration);
    if ($block && is_array($block) && isset($block['#plugin_id']) && $block['#plugin_id'] !== 'broken') {
      if (isset($block['#attributes'])) {
        $block['#attributes'] = array_merge_recursive($block['#attributes'], $attributes);
      }
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
  public function renderView($view, $display) {
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
  public function renderMenu($menu_id) {
    return $this->twigExtension->drupalMenu($menu_id);
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
  public function renderForm($type, string $form_id) {
    if (!isset($form_id) && empty($form_id)) {
      throw new \InvalidArgumentException(sprintf('For Form you need to specify the form_id or form namespace like this -- Drupal\search\Form\SearchBlockForm -- for custom forms (at 3 param)'));
    }
    // For custom forms (programmatically forms)
    if ($type == 'custom') {
      /* $namespace = str_replace(
      '/\/', '/\\/', "Drupal\\search\\Form\\SearchBlockForm"
      ); */
      return $this->twigExtension->drupalForm($form_id);
    }
    // For contrib forms (by contrib modules like webform)
    return $this->twigExtension->drupalEntity($type, $form_id);
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
  public function renderEntity($type, $id, $view_mode = NULL) {
    if (!isset($id) && empty($id)) {
      throw new \InvalidArgumentException(sprintf('For Entity you need to specify the ID -- example (entity, node, 1) (at 3 param)'));
    }
    return $this->twigExtension->drupalEntity($type, $id, $view_mode);
  }

  /**
   * Check block visibility from block admin settings.
   */
  public function isBlockVisible($block) {
    $langcode = $this->languageManager->getCurrentLanguage()
      ->getId();
    $current_path = $this->currentPathStack->getPath();
    $alias = $this->aliasManager->getAliasByPath($current_path);
    $current_path_formats = [
      $current_path,
      '/' . $langcode . $current_path,
      $current_path . '/',
      $alias,
      '/' . $langcode . $alias,
      $alias . '/',
    ];
    $is_front_page = $this->pathMatcher->isFrontPage();
    $block_visibility = $block->getVisibility();
    $show_block = empty($block_visibility);
    if (!empty($block_visibility)) {
      $is_hide = $block_visibility['request_path']['negate'];
      $show_block = $is_hide;
      $pages_paths = $block_visibility['request_path']['pages'];
      $front_page_default_paths = [
        '<front>',
        '/',
        '/' . $langcode,
        '/' . $langcode . '/',
      ];

      if (!empty($pages_paths)) {
        $pages_paths = str_replace(["\r\n", "\n", "\r"], ' ', $pages_paths);
        $pages_paths = explode(' ', $pages_paths);
        foreach ($pages_paths as $page_path) {
          $page_path_pattern = '/' . str_replace(['/', '*'], ['\/', '(.)*'], $page_path) . '/';
          if (strpos($page_path_pattern, '*') !== FALSE) {
            if (
              preg_match($page_path_pattern, '/' . $langcode . $current_path . '/') ||
              preg_match($page_path_pattern, '/' . $langcode . $alias . '/')
            ) {
              $show_block = !$is_hide;
              break;
            }
          }
          else {
            if (in_array($page_path, $current_path_formats)) {
              $show_block = !$is_hide;
              break;
            }
          }

          if ($is_front_page && in_array($page_path, $front_page_default_paths)) {
            $show_block = !$is_hide;
            break;
          }
        }
      }
    }

    return $show_block;
  }

}
