<?php

namespace Drupal\vactory_decoupled;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\media\MediaInterface;

/**
 * Provides a service to render embed media items using with custom tag.
 *
 */
class MediaEmbed {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a MediaEmbed object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity type bundle info service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository, RendererInterface $renderer, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityRepository = $entity_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->renderer = $renderer;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Builds the render array for the given media entity in the given langcode.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media entity to render.
   * @param string $view_mode
   *   The view mode to render it in.
   * @param string $langcode
   *   Language code in which the media entity should be rendered.
   *
   * @return array
   *   A render array.
   */
  protected function renderMedia(MediaInterface $media, $view_mode, $langcode) {
    $build = $this->entityTypeManager
      ->getViewBuilder('media')
      ->view($media, $view_mode, $langcode);

    // Allows other modules to treat embedded media items differently.
    $build['#embed'] = TRUE;

    // There are a few concerns when rendering an embedded media entity:
    // - entity access checking happens not during rendering but during routing,
    //   and therefore we have to do it explicitly here for the embedded entity.
    $build['#access'] = $media->access('view', NULL, TRUE);
    // - caching an embedded media entity separately is unnecessary; the host
    //   entity is already render cached.
    unset($build['#cache']['keys']);
    $build[':media_embed']['#attached']['library'][] = 'media/filter.caption';

    return $build;
  }

  /**
   * Builds the render array for the indicator when media cannot be loaded.
   *
   * @return array
   *   A render array.
   */
  protected function renderMissingMediaIndicator() {
    return [
      '#theme' => 'media_embed_error',
      '#message' => t('The referenced media source is missing and needs to be re-embedded.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, '<drupal-media') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    foreach ($xpath->query('//drupal-media[@data-entity-type="media" and normalize-space(@data-entity-uuid)!=""]') as $node) {
      $uuid = $node->getAttribute('data-entity-uuid');
      $view_mode_id = $node->getAttribute('data-view-mode') ?: 'default';

      // Delete the consumed attributes.
      $node->removeAttribute('data-entity-type');
      $node->removeAttribute('data-entity-uuid');
      $node->removeAttribute('data-view-mode');

      $media = $this->entityRepository->loadEntityByUuid('media', $uuid);
      assert($media === NULL || $media instanceof MediaInterface);
      if (!$media) {
        $this->loggerFactory->get('media')->error('During rendering of embedded media: the media item with UUID "@uuid" does not exist.', ['@uuid' => $uuid]);
      }
      else {
        $media = $this->entityRepository->getTranslationFromContext($media, $langcode);
        $media = clone $media;
        $this->applyPerEmbedMediaOverrides($node, $media);
      }

      $view_mode = NULL;
      if ($view_mode_id !== EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE) {
        $view_mode = $this->entityRepository->loadEntityByConfigTarget('entity_view_mode', "media.$view_mode_id");
        if (!$view_mode) {
          $this->loggerFactory->get('media')->error('During rendering of embedded media: the view mode "@view-mode-id" does not exist.', ['@view-mode-id' => $view_mode_id]);
        }
      }

      $build = $media && ($view_mode || $view_mode_id === EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE)
        ? $this->renderMedia($media, $view_mode_id, $langcode)
        : $this->renderMissingMediaIndicator();

      if (empty($build['#attributes']['class'])) {
        $build['#attributes']['class'] = [];
      }
      
      foreach ($node->attributes as $attribute) {
        if ($attribute->nodeName == 'class') {
          $build['#attributes']['class'] = array_unique(array_merge($build['#attributes']['class'], explode(' ', $attribute->nodeValue)));
        }
        else {
          $build['#attributes'][$attribute->nodeName] = $attribute->nodeValue;
        }
      }
      $this->renderIntoDomNode($build, $node, $result);
    }

    $processed_text = str_replace("\n", "", preg_replace('/<!--(.|\s)*?-->/', '', Html::serialize($dom)));
    $result->setProcessedText($processed_text);
    return $result;
  }

  /**
   * Renders the given render array into the given DOM node.
   *
   * @param array $build
   *   The render array to render in isolation.
   * @param \DOMNode $node
   *   The DOM node to render into.
   * @param \Drupal\filter\FilterProcessResult $result
   *   The accumulated result of filter processing, updated with the metadata
   *   bubbled during rendering.
   */
  protected function renderIntoDomNode(array $build, \DOMNode $node, FilterProcessResult &$result) {
    $markup = $this->renderer->executeInRenderContext(new RenderContext(), function () use (&$build) {
      return $this->renderer->render($build);
    });
    $result = $result->merge(BubbleableMetadata::createFromRenderArray($build));
    static::replaceNodeContent($node, $markup);
  }

  /**
   * Replaces the contents of a DOMNode.
   *
   * @param \DOMNode $node
   *   A DOMNode object.
   * @param string $content
   *   The text or HTML that will replace the contents of $node.
   */
  protected static function replaceNodeContent(\DOMNode &$node, $content) {
    if (strlen($content)) {
      // Load the content into a new DOMDocument and retrieve the DOM nodes.
      $replacement_nodes = Html::load($content)->getElementsByTagName('body')
        ->item(0)
        ->childNodes;
    }
    else {
      $replacement_nodes = [$node->ownerDocument->createTextNode('')];
    }

    foreach ($replacement_nodes as $replacement_node) {
      // Import the replacement node from the new DOMDocument into the original
      // one, importing also the child nodes of the replacement node.
      $replacement_node = $node->ownerDocument->importNode($replacement_node, TRUE);
      $node->parentNode->insertBefore($replacement_node, $node);
    }
    $node->parentNode->removeChild($node);
  }


  /**
   * Applies attribute-based per-media embed overrides of media information.
   *
   * Currently, this only supports overriding an image media source's `alt` and
   * `title`. Support for more overrides may be added in the future.
   *
   * @param \DOMElement $node
   *   The HTML tag whose attributes may contain overrides, and if such
   *   attributes are applied, they will be considered consumed and will
   *   therefore be removed from the HTML.
   * @param \Drupal\media\MediaInterface $media
   *   The media entity to apply attribute-based overrides to, if any.
   *
   * @see \Drupal\media\Plugin\media\Source\Image
   */
  protected function applyPerEmbedMediaOverrides(\DOMElement $node, MediaInterface $media) {
    if ($image_field = $this->getMediaImageSourceField($media)) {
      $settings = $media->{$image_field}->getItemDefinition()->getSettings();

      if (!empty($settings['alt_field']) && $node->hasAttribute('alt')) {
        if ($node->getAttribute('alt') === '""') {
          $node->setAttribute('alt', '');
        }
        $media->{$image_field}->alt = $node->getAttribute('alt');
        $media->thumbnail->alt = $node->getAttribute('alt');
        $node->removeAttribute('alt');
      }

      if (!empty($settings['title_field']) && $node->hasAttribute('title')) {
        $media->{$image_field}->title = $node->getAttribute('title');
        $media->thumbnail->title = $node->getAttribute('title');
        $node->removeAttribute('title');
      }
    }
  }

  /**
   * Get image field from source config.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media entity.
   *
   * @return string|null
   *   String of image field name.
   */
  protected function getMediaImageSourceField(MediaInterface $media) {
    $field_definition = $media->getSource()
      ->getSourceFieldDefinition($media->bundle->entity);
    $item_class = $field_definition->getItemDefinition()->getClass();
    if ($item_class == ImageItem::class || is_subclass_of($item_class, ImageItem::class)) {
      return $field_definition->getName();
    }
    return NULL;
  }

}
