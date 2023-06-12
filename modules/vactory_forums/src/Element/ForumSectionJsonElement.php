<?php

namespace Drupal\vactory_forums\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_decoupled\Element\JsonApiCollectionElement;

/**
 * Provide a JSON API form element for retieving data collection from JSON:API.
 *
 * @FormElement("json_api_section_forums")
 */
class ForumSectionJsonElement extends JsonApiCollectionElement {

  const DELIMITER = ',';

  /**
   * {@inheritDoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => TRUE,
      '#default_value' => [],
      '#process' => [
        [$class, 'processElement'],
      ],
      '#element_validate' => [
        [$class, 'validateElement'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * Element process callback.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $v_service = \Drupal::service('vactory');
    $rooms = $v_service->getTermsFromTaxonomy('vactory_forum_room', 'vactory_forum');
    $default_value = isset($element['#default_value']) ? $element['#default_value'] : '';
    $filters_default_value = 'fields[node--vactory_forum]=drupal_internal__nid,title,field_vactory_forum_status,field_vactory_date,field_vactory_excerpt,field_forum_editeur,field_forum_expert,field_vactory_forums_thematic,field_vactory_media,field_forum_views_count,internal_comment' . "\n" .
      'fields[taxonomy_term--vactory_forums_thematic]=tid,name' . "\n" .
      'fields[user--user]=display_name' . "\n" .
      'fields[media--image]=name,thumbnail' . "\n" .
      'fields[file--image]=filename,uri'. "\n" .
      'filter[status][value]=1'. "\n" .
      'page[offset]=0'. "\n" .
      'page[limit]=5'. "\n" .
      'sort[sort-vactory-date][path]=field_forum_views_count'. "\n" .
      'sort[sort-views-count][direction]=DESC'. "\n" .
      'include=field_vactory_forums_thematic,field_vactory_media,field_vactory_media.thumbnail,field_forum_editeur,field_forum_expert';

    $element['room'] = [
      '#type' => 'select',
      '#options' => $rooms,
      '#title' => t('Rooms'),
      '#default_value' => $default_value['room'] ?? '',
    ];

    $elements = parent::processElement($element, $form_state, $complete_form);

    $elements['resource']['#disabled'] = TRUE;
    $elements['vocabularies']['#attributes']['style'] = ['display:none;'];
    $elements['entity_queue']['#attributes']['style'] = ['display:none;'];
    $elements['entity_queue_field_id']['#attributes']['style'] = ['display:none;'];
    $elements['resource']['#default_value'] = 'node--vactory_forum';

    $elements['filters']['#default_value'] = isset($default_value['filters']) && !empty($default_value['filters']) ? implode("\n", $default_value['filters']) : $filters_default_value;

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // Add element validation here.
  }

}
