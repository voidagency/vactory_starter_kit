<?php

namespace Drupal\vactory_core\TwigExtension;

use Drupal\block\Entity\Block;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\taxonomy\Entity\Term;
use Drupal\vactory_core\Vactory;
use Drupal\Core\Render\RendererInterface;

/**
 * Class TwigExtension.
 *
 * @package Drupal\vactory_core\TwigExtension
 */
class TwigExtension extends \Twig_Extension {

  /**
   * Vactory service.
   *
   * @var \Drupal\vactory_core\Vactory
   */
  protected $vactory;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritDoc}
   */
  public function __construct(Vactory $vactory, EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer) {
    $this->vactory = $vactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritDoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('addAttributes',
        [$this, 'addAttributes'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('hideLabel',
        [$this, 'hideLabel'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('noWrapper',
        [$this, 'noWrapper'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('drupal_block_delta',
        [$this, 'drupalBlockDelta'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('vactory_render',
        [$this, 'vactoryRender'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('file_object',
        [$this, 'fileObject'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('is_notifications_enabled',
        [$this, 'isNotificationsEnabled'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('get_media',
        [$this, 'getMedia'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('get_image_info',
        [$this, 'getImageInfo'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('successive_image_styles',
        [$this, 'successiveImageStyles'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('get_term_name',
        [$this, 'getTermName'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('vactory_image',
        [$this, 'vactoryImage'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('get_image_url',
        [$this, 'getImageUrl'],
        ['is_safe' => ['html']]),

      new \Twig_SimpleFunction('get_image',
        [$this, 'getImage'],
        ['is_safe' => ['html']]),

    ];
  }

  /**
   * Add attributes to field.
   *
   * @usage: {{ addAttributes(content.field_vactory_image, {'class':
   *   ['vf-card__image', 'img-responsive']}) }}
   *
   * @param $field
   * @param array $attributes
   *
   * @return mixed
   */
  public function addAttributes($field, $attributes = []) {
    if (isset($field['#items']) && !empty($field['#items'])) {
      foreach ($field['#items'] as $key => $value) {
        if (isset($field[$key]['#type'])) {

          if (!isset($field[$key]['#attributes'])) {
            $field[$key]['#attributes'] = [];
          }
          $field[$key]['#attributes'] = array_merge($field[$key]['#attributes'], $attributes);
        }

        if (isset($field[$key]['#item_attributes'])) {

          $field[$key]['#item_attributes'] = array_merge($field[$key]['#item_attributes'], $attributes);
        }
      }
    }

    if (isset($field['#attributes']) && $field['#attributes'] instanceof Attribute) {
      foreach ($attributes as $key => $value) {
        $field['#attributes']->setAttribute($key, $value);
      }
    }
    else {
      $field['#attributes'] = new Attribute($attributes);
    }

    return $field;
  }

  /**
   * Hide field label.
   *
   * @usage: {{ hideLabel(content.field_vactory_image) }}
   *
   * @param $field
   *
   * @return mixed
   */
  public function hideLabel($field) {
    $field['#label_display'] = 'hidden';
    $field['#label_hidden'] = TRUE;

    return $field;
  }

  /**
   * Remove field wrapper.
   *
   * @usage: {{ noWrapper(content.field_vactory_taxonomy1) }}
   *
   * @param $field
   *
   * @return mixed
   */
  public function noWrapper($field) {
    // @see vactory_theme_suggestions_field_alter();
    $field['#vactory_no_wrapper'] = TRUE;

    return $field;
  }

  /**
   * Render block by delta.
   *
   * the block must be instanced for getting block_delta
   *
   * @param $delta
   *
   * @return string|false
   */
  public function drupalBlockDelta($delta) {
    $block = Block::load($delta);
    if ($block) {
      $variables = \Drupal::entityTypeManager()
        ->getViewBuilder('block')
        ->view($block);

      if ($variables) {
        return \Drupal::service('renderer')->render($variables);
      }
    }
    return FALSE;
  }

  /**
   * Return HTML output on a certain object.
   *
   * @param string $type
   *   Type of the object: block, views, menu, form, entity.
   * @param string $object
   *   Object to retrieve.
   * @param array|string $configuration
   *   Optional params.
   *
   * @return array|\Drupal\vactory_core\html
   *   HTML output.
   */
  public function vactoryRender($type, $object, $configuration = NULL, $attributes = NULL,$view_mode = NULL) {

    switch ($type) {
      case 'block':
        return $this->vactory->renderBlock($object, (is_array($configuration)) ? $configuration : [], is_array($attributes) ? $attributes : []);

      case 'views':
        return $this->vactory->renderView($object, $configuration);

      case 'menu':
        return $this->vactory->renderMenu($object);

      case 'form':
        return $this->vactory->renderForm($object, $configuration);

      case 'entity':
        return $this->vactory->renderEntity($object, $configuration, $view_mode);
    }
  }

  /**
   * Get file object from file ID $fid in a template file.
   *
   * @param $fid
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|null|static
   */
  public function fileObject($fid) {
    $file = File::load($fid);
    if (isset($file)) {
      return $file;
    }

    return FALSE;
  }

  /**
   * Check either the vactory notifications module is enabled or not.
   *
   * @return boolean
   */
  public function isNotificationsEnabled() {
    $moduleHandler = \Drupal::service('module_handler');
    return $moduleHandler->moduleExists('vactory_notifications');
  }

  /**
   * Load media twig extension callback.
   *
   * @usage: {% set media = get_media(mid) %}
   *
   * @param $mid
   *   The media ID.
   *
   * @return \Drupal\media\Entity\Media|NULL
   */
  public function getMedia($mid) {
    $media = $this->entityTypeManager->getStorage('media')
      ->load($mid);
    return $media;
  }

  /**
   * Get Image Info twig extension callback.
   *
   * @usage: {% set media = get_image_info(fid) %}
   *
   * @param $image_id
   *   The image file ID.
   *
   * @return array|NULL
   */
  public function getImageInfo($image_id) {
    $image =  File::load($image_id);
    if ($image) {
      $image_meta_data = $image->getAllMetaData();
      $file_info = [
        'fid' => $image->id(),
        'uuid' => $image->get('uuid')->value,
        'uid' => $image->get('uid')->target_id,
        'created' => $image->get('created')->value,
        'status' => $image->get('status')->value,
        'type' => $image->get('type')->target_id,
        'filename' => $image->label(),
        'filemime' => $image->get('filemime')->value,
        'uri' => $image->get('uri')->value,
        'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($image->get('uri')->value),
        'alt' => $image->hasField('field_image_alt_text') && !empty($image->get('field_image_alt_text')->value) ? $image->get('field_image_alt_text')->value : '',
        'title' => $image->hasField('field_image_title_text') && !empty($image->get('field_image_title_text')->value) ? $image->get('field_image_title_text')->value : '',
      ];
      $image_meta_data = $image_meta_data ?? [];
      return array_merge($file_info, $image_meta_data);
    }
    return NULL;
  }

  /**
   * Apply successive image styles twig extension callback.
   *
   * @param $image_uri
   *   The image uri.
   * @param $styles
   *   Ordered list of styles to apply.
   *
   * @return string|null
   */
  public function successiveImageStyles($image_uri, $styles) {
    if (empty($styles)) {
      return NULL;
    }
    $image_style = ImageStyle::load(array_values($styles)[0]);
    if ($image_style) {
      unset($styles[array_keys($styles)[0]]);
      $destination_uri = $image_style->buildUri($image_uri);
      $image_style->createDerivative($image_uri, $destination_uri);
      return empty($styles) ? $image_style->buildUrl($destination_uri) : $this->successiveImageStyles($destination_uri, $styles) ;
    }
    return NULL;
  }

  public function getTermName($tid) {
    if (isset($tid)) {
      $term = Term::load($tid);
      if (isset($term)) {
        return $term->get('name')->value;
      }
    }
  }

  /**
   * Get image from the media id, inculdes [apply images styles,
   * add classes, lazy loading, image alt]
   */
  public function vactoryImage($media_id, $image_styles = [], $classes = [], $lazyLoading = TRUE, $image_alt = '') {
    if (empty($media_id)) {
        return NULL;
    }

    $media = $this->entityTypeManager->getStorage('media')->load($media_id);
    if (!isset($media)) {
        return NULL;
    }

    $fid = $media->get('field_media_image')->getValue()[0]['target_id'];
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    if (!isset($file)) {
        return NULL;
    }

    $alt = empty($image_alt) ? $media->get('field_media_image')->getValue()[0]['alt'] : $image_alt;
    $height = $media->get('field_media_image')->getValue()[0]['height'];
    $width = $media->get('field_media_image')->getValue()[0]['width'];
    $uri = $file->getFileUri();
    $picture_urls = [];
    $lqip_url = '';

    if (!empty($image_styles)) {
      if (is_string($image_styles) || (is_array($image_styles) && count($image_styles) == 1)) {
          $image_style = is_array($image_styles) ? $image_styles[0] : $image_styles;
          $image_url = $this->entityTypeManager
            ->getStorage('image_style')->load($image_style)->buildUrl($uri);
          $image_style_configs = !is_null($this->entityTypeManager
            ->getStorage('image_style')->load($image_style)->getEffects()->getConfiguration()) ?
            array_values($this->entityTypeManager->getStorage('image_style')->load($image_style)->getEffects()->getConfiguration())[0] : NULL;
          // Get Height && width.
          $height = isset($image_style_configs) ? $image_style_configs['data']['height'] : '';
          $width = isset($image_style_configs) ? $image_style_configs['data']['width'] : '';
      }
      else {
        foreach ($image_styles as $image_style) {
            $picture_urls[] = $this->entityTypeManager
              ->getStorage('image_style')->load($image_style)->buildUrl($uri);
        }
        if (count($picture_urls) < 4) {
            $picture_urls = array_pad($picture_urls, 4, $picture_urls[count($picture_urls) - 1]);
        }
        $image_url = $picture_urls[0];
        $lazyLoading = TRUE;
      }
    }
    else {
        $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($uri);
    }

    if ($lazyLoading || !empty($picture_urls)) {
        $lqip_url = $this->entityTypeManager
          ->getStorage('image_style')->load('lqip')->buildUrl($uri);
    }

    if ($lazyLoading) {
        array_push($classes, 'lazyload');
    }

    $theme = [
        '#theme' => 'vactory_image',
        '#image' => [
            'picture-urls' => $picture_urls,
            'url' => !empty($lqip_url) ? $lqip_url : $image_url,
            'data-src' => !empty($lqip_url) ? $image_url : '',
            'alt' => $alt,
            'height' => $height,
            'width' => $width,
            'classes' => $classes,
        ],
    ];

    return $this->renderer->render($theme);

  }

  /**
   * Implement get image function.
   */
  public function getImageUrl($fid = 0) {
    $file = $this->entityTypeManager->getStorage('media')->load($fid);
    if (!$file) {
      return;
    }
    $path = \Drupal::service('file_url_generator')->generateAbsoluteString($file->thumbnail->entity->getFileUri());
    return $path;
  }

  /**
   * Implement get image function.
   */
  public function getImage($fid = 0) {
    $file = $this->entityTypeManager->getStorage('media')->load($fid);
    if (!$file) {
      return;
    }
    $path = $file->thumbnail->entity->getFileUri();
    return $path;
  }

}
