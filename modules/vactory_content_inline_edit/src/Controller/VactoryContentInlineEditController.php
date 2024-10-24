<?php

namespace Drupal\vactory_content_inline_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;

/**
 * The VactoryContentFeedbackController class.
 */
class VactoryContentInlineEditController extends ControllerBase {

  /**
   * Get nodes.
   */
  public function index(Request $request) {
    $page = $request->query->get('page', 1);
    // You can adjust this as needed.
    $limit = 10;

    // Optional node ID for filtering.
    $nodeId = $request->query->get('node_id');

    $nodes = $this->fetchNodes($page, $limit, $nodeId);

    $pageData = [];
    foreach ($nodes as $node) {
      $pageData[] = $this->formatNodeData($node);
    }

    return new JsonResponse($pageData);
  }

  /**
   * Get nodes using pagination.
   */
  public function getPaginatedNodeData($page, $nodeId = NULL, $num_per_page = 10) {
    $nodes = $this->fetchNodes($page, $num_per_page, $nodeId);

    $formattedData = [];
    foreach ($nodes as $node) {
      $formattedData[] = $this->formatNodeData($node);
    }

    return $formattedData;
  }

  /**
   * Fetch nodes.
   */
  private function fetchNodes($page = 1, $limit = 10, $nodeId = NULL) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'vactory_page');

    if ($nodeId) {
      $query->condition('nid', $nodeId);
    }

    // Ensure the page is at least 1 and calculate offset accordingly.
    $page = max($page, 1);
    $offset = ($page - 1) * $limit;
    $nids = $query->range($offset, $limit)->accessCheck(FALSE)->execute();

    return Node::loadMultiple($nids);
  }

  /**
   * Format nodes data.
   */
  private function formatNodeData($node) {
    $formattedNodeData = [
      'nodeId' => $node->id(),
      'title' => $node->getTitle(),
      'paragraphs' => [],
    ];

    if ($node->hasField('field_vactory_paragraphs')) {
      $paragraphsData = $node->get('field_vactory_paragraphs')->getValue();
      foreach ($paragraphsData as $paragraphData) {
        $paragraph = Paragraph::load($paragraphData['target_id']);
        if ($paragraph && $paragraph->hasField('field_vactory_component') && $paragraph->bundle() == 'vactory_component') {
          $vactoryComponents = $paragraph->field_vactory_component->getValue();
          foreach ($vactoryComponents as $component) {
            $widgetData = Json::decode($component['widget_data']);
            $widgetId = $component['widget_id'];

            // Fetch the widget configuration.
            $widgetConfig = \Drupal::service('vactory_dynamic_field.vactory_provider_manager')->loadSettings($widgetId);

            // Now combine $widgetData with $widgetConfig.
            $formattedData = $this->formatWidgetData($widgetData, $widgetConfig, $paragraphData['target_id']);
            $formattedData['type'] = 'vactory_component';
            $formattedNodeData['paragraphs'][] = $formattedData;
          }
        }
        if ($paragraph && $paragraph->bundle() == 'vactory_paragraph_multi_template') {
          $formattedNodeData['paragraphs'][] = [
            'paragraphId' => $paragraphData['target_id'],
            'type' => 'vactory_paragraph_multi_template',
            'title' => $paragraph->get('field_vactory_title')->value ?? '',
            'introduction' => $paragraph->get('field_paragraph_introduction')->value ?? '',
          ];
        }
      }
    }

    return $formattedNodeData;
  }

  /**
   * Save DF changes.
   */
  public function saveChanges(Request $request) {
    $content = json_decode($request->getContent(), TRUE);

    $nodeId = $content['nodeId'] ?? NULL;
    $paragraphId = $content['paragraphId'] ?? NULL;
    $updatedData = $content['updatedData'] ?? NULL;

    if (!$nodeId || !$updatedData) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Missing data',
        'c' => $content,
      ], 400);
    }

    $node = Node::load($nodeId);
    if (!$node) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Node not found',
      ], 400);
    }

    $paragraph = Paragraph::load($paragraphId);
    if (!$paragraph) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Paragraph not found',
      ], 404);
    }

    $paragraphsField = $node->get('field_vactory_paragraphs');
    $paragraphsData = $paragraphsField->getValue();

    $widget = $paragraph->field_vactory_component->getValue()[0];
    $widget_id = $widget['widget_id'];
    $widgetDataJson = $widget['widget_data'];
    $widgetData = Json::decode($widgetDataJson);

    // Update extra fields.
    if (isset($updatedData['extra_fields'])) {
      foreach ($updatedData['extra_fields'] as $fieldName => $fieldValue) {
        // Url extended.
        if (isset($fieldValue['url']) && isset($fieldValue['title'])) {
          $widgetData['extra_field'][$fieldName]['title'] = $fieldValue['title'];
          $widgetData['extra_field'][$fieldName]['url'] = $fieldValue['url'];
        }
        // text_format.
        elseif (isset($fieldValue['format'])) {
          $widgetData['extra_field'][$fieldName]['value'] = $fieldValue['value'];
        }
        else {
          $widgetData['extra_field'][$fieldName] = $fieldValue;
        }
      }
    }

    // Update numbered components.
    if (isset($updatedData['components'])) {
      foreach ($updatedData['components'] as $componentIndex => $componentFields) {
        if (!isset($widgetData[$componentIndex])) {
          $widgetData[$componentIndex] = [];
        }
        foreach ($componentFields as $fieldName => $fieldValue) {
          // Url extended.
          if (isset($fieldValue['url']) && isset($fieldValue['title'])) {
            $widgetData[$componentIndex][$fieldName]['title'] = $fieldValue['title'];
            $widgetData[$componentIndex][$fieldName]['url'] = $fieldValue['url'];
          }
          // text_format.
          elseif (isset($fieldValue['format'])) {
            $widgetData[$componentIndex][$fieldName]['value'] = $fieldValue['value'];
          }
          else {
            $widgetData[$componentIndex][$fieldName] = $fieldValue;
          }
        }
      }
    }
    $paragraph->field_vactory_component->setValue([
      [
        'widget_id' => $widget_id,
        'widget_data' => Json::encode($widgetData),
      ],
    ]);
    $paragraph->save();

    return new JsonResponse([
      'success' => TRUE,
      'message' => 'Node and Paragraphs updated',
    ]);
  }

  /**
   * Format DF Data.
   */
  private function formatWidgetData($widgetData, $widgetConfig, $paragraphId) {
    $formattedData = [];
    $formattedData["paragraphId"] = $paragraphId;
    $formattedData["screenshot"] = $widgetConfig['screenshot'];
    $formattedData["name"] = $widgetConfig["name"];

    // Process regular fields.
    foreach ($widgetData as $key => $fieldGroup) {
      if ($key === 'extra_field') {
        // Process extra fields.
        foreach ($fieldGroup as $extraFieldName => $extraFieldValue) {
          if (str_starts_with($extraFieldName, 'group_')) {
            foreach ($extraFieldValue as $sub_key => $sub_value) {
              if ($sub_key == 'g_title') {
                continue;
              }
              $extraFieldConfig = $widgetConfig['extra_fields'][$extraFieldName][$sub_key];
              $processedField = $this->processField($sub_value, $extraFieldConfig);
              if ($processedField) {
                $formattedData["elements"]["extra_fields"][$extraFieldName][$sub_key] = $processedField;
              }
            }
          }
          else {
            $extraFieldConfig = $widgetConfig['extra_fields'][$extraFieldName];
            $processedField = $this->processField($extraFieldValue, $extraFieldConfig);
            if ($processedField) {
              $formattedData["elements"]["extra_fields"][$extraFieldName] = $processedField;
            }
          }
        }
      }
      elseif (is_numeric($key) && is_array($fieldGroup)) {
        // Process regular field groups (indexed numerically).
        foreach ($fieldGroup as $fieldName => $fieldValue) {
          if (str_starts_with($fieldName, 'group_')) {
            foreach ($fieldValue as $sub_key => $sub_value) {
              if ($sub_key == 'g_title') {
                continue;
              }
              $fieldConfig = $widgetConfig['fields'][$fieldName][$sub_key] ?? NULL;
              if ($fieldConfig) {
                $processedField = $this->processField($sub_value, $fieldConfig);
              }
              if ($processedField) {
                $formattedData["elements"]["components"][$key][$fieldName][$sub_key] = $processedField;
              }
            }
          }
          else {
            $fieldConfig = $widgetConfig['fields'][$fieldName] ?? NULL;
            if ($fieldConfig) {
              $processedField = $this->processField($fieldValue, $fieldConfig);
            }
            if ($processedField) {
              $formattedData["elements"]["components"][$key][$fieldName] = $processedField;
            }
          }
        }
      }
    }
    return $formattedData;
  }

  /**
   * Normalize fields based on type.
   */
  private function processField($fieldValue, $fieldConfig) {
    if (!isset($fieldConfig['type'])) {
      return NULL;
    }
    switch ($fieldConfig['type']) {
      case 'text':
        return [
          'type' => 'text',
          'value' => $fieldValue,
          'label' => $fieldConfig['label'],
        ];

      case 'textarea':
        return [
          'type' => 'textarea',
          'value' => $fieldValue,
          'label' => $fieldConfig['label'],
        ];

      case 'text_format':
        return $this->processFormattedText($fieldValue, $fieldConfig);

      case 'remote_video':
      case 'image':
      case 'video':
      case 'file':
        return $this->processMediaField($fieldValue, $fieldConfig);

      case 'url_extended':
        return $this->processUrlExtendedField($fieldValue, $fieldConfig);
    }

    return NULL;
  }

  /**
   * Normalize formatted text.
   */
  private function processFormattedText($formattedText, $fieldConfig) {
    return [
      'type' => 'text_format',
      'label' => $fieldConfig['label'],
      'value' => $formattedText['value'],
      'format' => $formattedText['format'],
    ];
  }

  /**
   * Normalize media field.
   */
  private function processMediaField($imageField, $fieldConfig) {
    if (is_array($imageField)) {
      $image_data = reset($imageField);
      $mid = $image_data['selection'][0]['target_id'] ?? NULL;
    }
    else {
      $mid = $imageField;
    }

    return [
      'type' => $fieldConfig['type'],
      'label' => $fieldConfig['label'],
      'mid' => $mid,
    ];
  }

  /**
   * Normalize url extended.
   */
  private function processUrlExtendedField($urlField, $fieldConfig) {
    return [
      'type' => 'url_extended',
      'label' => $fieldConfig['label'],
      "title" => $urlField['title'],
      'url' => $urlField['url'],
    ];
  }

}
