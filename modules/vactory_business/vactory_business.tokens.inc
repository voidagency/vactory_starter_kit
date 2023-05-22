<?php

/**
 * @file
 * Module related tokens.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\file\Entity\File;

/**
 * Implements hook_token_info().
 */
function vactory_business_token_info() {
  $fiche_business_node_values['fiche_business'] = [
    'name' => t("Current user fiche business node values"),
    'description' => t("Get current user fiche business values, replace the '?' with the desired fiche business field name"),
    'dynamic' => TRUE,
  ];
  return [
    'tokens' => [
      'vactory' => $fiche_business_node_values,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_business_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  $token_service = Drupal::token();
  if ($type === 'vactory') {
    $fiche_business = NULL;
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions('node', 'vactory_business');
    /** @var \Drupal\webform\Entity\WebformSubmission $webform_submission */
    $nids = Drupal::entityTypeManager()->getStorage('node')
      ->getQuery()
      ->condition('type', 'vactory_business')
      ->condition('field_business_users', Drupal::currentUser()->id())
      ->execute();
    if (!empty($nids)) {
      // Update node.
      $nid = reset($nids);
      $node = Drupal::entityTypeManager()->getStorage('node')
        ->load($nid);
      if ($node instanceof NodeInterface) {
        $langcode = Drupal::languageManager()->getCurrentLanguage()->getId();
        $node = Drupal::service('entity.repository')
          ->getTranslationFromContext($node, $langcode);
        $fiche_business = $node;
      }
    }
    if (!empty($fiche_business)) {
      if ($value_tokens = $token_service->findWithPrefix($tokens, 'fiche_business')) {
        foreach ($value_tokens as $value_token => $original) {
          if (str_contains($value_token, 'field_social_networks:')) {
            $social_network = explode(':', $value_token)[1];
            $value_token = 'field_social_networks';
          }
          $value = $fiche_business->get($value_token)->getValue();
          $field_type = $fields[$value_token]->getFieldStorageDefinition()->getType();
          $value = array_map(function ($v) use ($field_type, $value_token, $social_network) {
            if (isset($social_network)) {
              return $v['platform_values'][$social_network]['value'];
            }
            if ($value_token === 'path') {
              return $v['alias'] ?? '';
            }
            if ($value_token === 'field_vactory_media_image') {
              $mid = $v['target_id'] ?? NULL;
              if ($mid) {
                $media = Media::load($mid);
                if ($media instanceof MediaInterface) {
                  $fid = $media->get('field_media_image')->target_id;
                  if ($fid) {
                    $file = File::load($fid);
                    if ($file instanceof FileInterface) {
                      return json_encode([
                        'fid' => $fid,
                        'name' => $file->get('filename')->value,
                        'size' => $file->get('filesize')->value,
                        'type' => $file->get('filemime')->value,
                      ]);
                    }
                  }
                }
              }
            }
            return $field_type === 'entity_reference' ? $v['target_id'] : $v['value'];
          }, $value);
          if (isset($value[0]) && is_array($value[0])) {
            $value = $value[0];
          }
          else {
            $value = implode(',', $value);
          }

          if ($value !== NULL) {
            $replacements[$original] = $value;
          }
        }
      }
    }
    else {
      if ($value_tokens = $token_service->findWithPrefix($tokens, 'fiche_business')) {
        foreach ($value_tokens as $value_token => $original) {
          $replacements[$original] = NULL;
        }
      }
    }
  }

  return $replacements;
}
