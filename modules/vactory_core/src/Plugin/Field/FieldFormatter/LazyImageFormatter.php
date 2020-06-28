<?php

namespace Drupal\vactory_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for lazy image formatter.
 *
 * @FieldFormatter(
 *   id = "lazy_image",
 *   label = @Translation("Lazy image"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class LazyImageFormatter extends ResponsiveImageFormatter {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Image styles.
   */
  protected const IMAGE_STYLES = [
    'desktop_1x' => 'Desktop 1x',
    'desktop_2x' => 'Desktop 2x',
    'laptop_1x'  => 'Laptop 1x',
    'laptop_2x'  => 'Laptop 2x',
    'tablet_1x'  => 'Tablet 1x',
    'tablet_2x'  => 'Tablet 2x',
    'mobile_1x'  => 'Mobile 1x',
    'mobile_2x'  => 'Mobile 2x',
    'lqip'   => 'LQIP',
  ];

  /**
   * Constructs a MediaResponsiveThumbnailFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsive_image_style_storage
   *   The responsive image style storage.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityStorageInterface $responsive_image_style_storage, EntityStorageInterface $image_style_storage, LinkGeneratorInterface $link_generator, AccountInterface $current_user, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings, $responsive_image_style_storage, $image_style_storage, $link_generator, $current_user);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')->getStorage('responsive_image_style'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('link_generator'),
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   *
   * This has to be overridden because FileFormatterBase expects $item to be
   * of type \Drupal\file\Plugin\Field\FieldType\FileItem and calls
   * isDisplayed() which is not in FieldItemInterface.
   */
  protected function needsEntityLoad(EntityReferenceItem $item) {
    return !$item->hasNewEntity();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'desktop_1x' => '',
      'desktop_2x' => '',
      'laptop_1x'  => '',
      'laptop_2x'  => '',
      'tablet_1x'  => '',
      'tablet_2x'  => '',
      'mobile_1x'  => '',
      'mobile_2x'  => '',
      'lqip'   => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $responsive_image_options = [];
    $responsive_image_styles = $this->imageStyleStorage->loadMultiple();
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        $responsive_image_options[$machine_name] = $responsive_image_style->label();
      }
    }

    foreach (self::IMAGE_STYLES as $key => $label) {
      $elements[$key] = [
        '#title'         => $label,
        '#type'          => 'select',
        '#default_value' => $this->getSetting($key) ?: NULL,
        '#required'      => TRUE,
        '#options'       => $responsive_image_options,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Select a responsive image style.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that reference
    // media items.
    return ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'media');
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $media_items = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($media_items)) {
      return $elements;
    }

    // Load image styles.
    $image_styles = [];
    foreach (self::IMAGE_STYLES as $key => $label) {
      $image_style = $this->getSetting($key);
      $image_styles[$key] = $this->imageStyleStorage->load($image_style);
    }

    // Setup cache.
    $cache_tags = [];
    foreach ($image_styles as $key => $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    /** @var \Drupal\media\MediaInterface[] $media_items */
    foreach ($media_items as $delta => $media) {
      $meta = $media->get('thumbnail')->first()->getValue();
      /** @var \Drupal\file\FileInterface $file */
      $file = $media->get('thumbnail')->first()->entity;
      $file_uri = $file->getFileUri();

      $data_src = [];

      // Add original image.
      $file_url = file_create_url($file_uri);
      $data_src['original'] = file_url_transform_relative($file_url);

      // Setup image styles for this URI.
      foreach ($image_styles as $key => $image_style) {
        $url = $image_style->buildUrl($file_uri);
        $data_src[$key] = file_url_transform_relative($url);
      }

      $elements[$delta] = [
        '#theme'  => 'vactory_responsive_image',
        '#meta'   => $meta,
        '#srcset' => $data_src,
      ];

      // Add cacheability of each item in the field.
      $this->renderer->addCacheableDependency($elements[$delta], $media);
    }

    return $elements;
  }

}
