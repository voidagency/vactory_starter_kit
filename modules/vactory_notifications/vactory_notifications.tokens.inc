<?php

use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Implements hook_token_info().
 */
function vactory_notifications_token_info() {
  $type = [
    'name' => t('Notifications'),
    'description' => t('Tokens for notifications message.'),
  ];
  $node['owner_first_name'] = [
    'name' => t("Fisrt name"),
    'description' => t('The notification owner first name'),
  ];
  $node['owner_last_name'] = [
    'name' => t("Last name"),
    'description' => t('The notification owner last name'),
  ];
  $node['content_type'] = [
    'name' => t("Content type"),
    'description' => t('The notification related content type.'),
  ];
  $node['content_type:node_title'] = [
    'name' => t('Node title'),
    'description' => t('The notification related content type node title.'),
  ];
  return [
    'types' => ['notification' => $type],
    'tokens' => ['notification' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_notifications_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'notification') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'owner_first_name':
          $notification_owner_id = $data['entity']->getOwnerId();
          $notification_owner = User::load($notification_owner_id);
          $replacements[$original] = $notification_owner->get('field_first_name')->value;
          break;
        case 'owner_last_name':
          $notification_owner_id = $data['entity']->getOwnerId();
          $notification_owner = User::load($notification_owner_id);
          $replacements[$original] = $notification_owner->get('field_last_name')->value;
          break;
        case 'content_type':
          $entity_type = $data['entity']->getEntityTypeId();
          $nid = $entity_type === 'notifications_entity' ? $data['entity']->getRelatedContent() : $data['entity']->id();
          $node = Node::load($nid);
          $content_type = NodeType::load($node->bundle());
          $replacements[$original] = $content_type->label();
          break;
        case 'content_type:node_title':
          $entity_type = $data['entity']->getEntityTypeId();
          $nid = $entity_type === 'notifications_entity' ? $data['entity']->getRelatedContent() : $data['entity']->id();
          $node = Node::load($nid);
          $replacements[$original] = $node->label();
          break;
        default:
          $replacements[$original] = '';
          break;

      }
    }
  }
  return $replacements;
}
