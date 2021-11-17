<?php

namespace Drupal\vactory_sondage\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\vactory_sondage\Services\SondageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sondage option field formatter.
 *
 * @FieldFormatter(
 *   id = "vactory_sondage_option_formatter",
 *   label = @Translation("Sondage Option Default"),
 *   field_types = {
 *     "vactory_sondage_option"
 *   }
 * )
 */
class SondageOptionFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Sondage manager service.
   *
   * @var \Drupal\vactory_sondage\Services\SondageManager
   */
  private $sondageManager;

  /**
   * {@inheritDoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, SondageManager $sondageManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->sondageManager = $sondageManager;
  }

  /**
   * {@inheritDoc}
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
      $container->get('vactory_sondage.manager')
    );
  }

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $current_user = \Drupal::currentUser();
    $storage_results = $entity->get('field_sondage_results')->value;
    $storage_results = isset($storage_results) && !empty($storage_results) ? $storage_results : '[]';
    $storage_results = json_decode($storage_results, TRUE);
    $is_closed = $this->sondageManager->isSondageClosed($entity);
    if ((!empty($storage_results) && in_array($current_user->id(), $storage_results['all_votters'])) || $is_closed) {
      $statistics = $this->sondageManager->getStatistics($entity);
      return [
        '#theme' => 'vactory_sondage_state',
        '#statistics' => $statistics,
      ];
    }

    $field_values = $items->getValue();
    $extra_data = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
    ];
    $sondage_options = [];
    foreach ($field_values as $value) {
      if (!empty($value['option_text'])) {
        $sondage_options[$value['option_value']]['type'] = 'text';
        $sondage_options[$value['option_value']]['value'] = $value['option_text'];
      }
      if (!empty($value['option_image'])) {
        $media = Media::load($value['option_image']);
        if ($media) {
          $fid = $media->get('field_media_image')->target_id;
          $alt = $media->get('field_media_image')->alt;
          $file = $fid ? File::load($fid) : NULL;
          if ($file) {
            $image_uri = $file->get('uri')->value;
            $sondage_options[$value['option_value']]['type'] = 'image';
            $sondage_options[$value['option_value']]['uri'] = $image_uri;
            $sondage_options[$value['option_value']]['alt'] = $alt;
          }
        }
      }
    }
    $extra_data['sondage_options'] = $sondage_options;
    $element = \Drupal::formBuilder()->getForm('Drupal\vactory_sondage\Form\SondageForm', $extra_data);

    return $element;
  }

}
