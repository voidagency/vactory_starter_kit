<?php

namespace Drupal\vactory_cloudinary\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\vactory_cloudinary\Services\VactoryCloudinaryManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensure a cloudinary file url replacement.
 *
 * @Filter(
 *   id = "vactory_cloudinary_file_replace",
 *   title = @Translation("Vactory cloudinary files replace"),
 *   description = @Translation("Replace files urls with new cloudinary urls and vise versa depending on the filter settings"),
 *   settings = {
 *     "replace_policy" = "drupal_to_cloudinary"
 *   },
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class VactoryReplaceWithCloudinaryUrl extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Stream wrapper manager service.
   *
   * @var StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Vactory cloudinary manager service.
   *
   * @var \Drupal\vactory_cloudinary\Services\VactoryCloudinaryManager
   */
  protected $cloudinaryManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    StreamWrapperManagerInterface $streamWrapperManager,
    Connection $database,
    VactoryCloudinaryManager $cloudinaryManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->streamWrapperManager = $streamWrapperManager;
    $this->database = $database;
    $this->cloudinaryManager = $cloudinaryManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('stream_wrapper_manager'),
      $container->get('database'),
      $container->get('vactory_cloudinary.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (!empty($text) && strpos($text, '<img ') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $replace_policy = $this->settings['replace_policy'];
      foreach ($xpath->query('//img') as $element) {
        /** @var \DOMElement $element */
        if (!$element->hasAttribute('src')) {
          continue;
        }
        $file_link = $element->attributes->getNamedItem('src')->value;
        if (empty($file_link)) {
          $query = $this->database->select('file_managed', 'fm');
          $file_uuid = $element->attributes->getNamedItem('data-entity-uuid')->value;
          $results = $query->fields('fm', ['uri', 'fid'])
            ->condition('uuid', $file_uuid)
            ->execute()
            ->fetchAll();
          if (!empty($results)) {
            $uri = $results[0]->uri;
            $fid = $results[0]->fid;
            if ($replace_policy === 'drupal_to_cloudinary') {
              $resource = $this->cloudinaryManager->getCloudinaryRessource($uri);
              if (isset($resource['secure_url'])) {
                $image_alt = $this->getImageMetadata('file__field_image_alt_text', 'field_image_alt_text_value', $langcode, $fid);
                $image_title = $this->getImageMetadata('file__field_image_title_text', 'field_image_title_text_value', $langcode, $fid);
                $element->setAttribute('src', $resource['secure_url']);
                $element->setAttribute('alt', $image_alt);
                $element->setAttribute('title', $image_title);
                $element->removeAttribute('height');
                $element->removeAttribute('width');
              }
            }
            else {
              $stream_wrapper = $this->streamWrapperManager->getViaUri($uri);
              $file_url = $stream_wrapper->getExternalUrl();
              if ($file_url) {
                $file_url_info = parse_url($file_url);
                if (isset($file_url_info['path'])) {
                  $image_alt = $this->getImageMetadata('file__field_image_alt_text', 'field_image_alt_text_value', $langcode, $fid);
                  $image_title = $this->getImageMetadata('file__field_image_title_text', 'field_image_title_text_value', $langcode, $fid);
                  $element->setAttribute('src', $file_url_info['path']);
                  $element->setAttribute('alt', $image_alt);
                  $element->setAttribute('title', $image_title);
                  $element->removeAttribute('height');
                  $element->removeAttribute('width');
                }
              }
            }
          }
        }
      }
      $result->setProcessedText(Html::serialize($dom));
    }
    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['replace_policy'] = [
      '#type' => 'radios',
      '#title' => $this->t('Replace policy'),
      '#options' => [
        'drupal_to_cloudinary' => $this->t('From Drupal uri to Cloudinary uri'),
        'cloudinary_to_drupal' => $this->t('From Cloudinary uri to Drupal uri'),
      ],
      '#default_value' => $this->settings['replace_policy'],
    ];
    return $form;
  }

  /**
   * get image metadata.
   */
  public function getImageMetadata($table, $fieldName, $langcode, $fid) {
    $query = $this->database->select($table, 't');
    $result = $query->fields('t', [$fieldName])
      ->condition('entity_id', $fid)
      ->condition('langcode', $langcode)
      ->execute()
      ->fetchAll();
    if (!empty($result)) {
      return $result[0]->{$fieldName};
    }
    return '';
  }

}
