<?php

namespace Drupal\vactory_entity_canonical_access\Form;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_media_links\Plugin\SocialMediaLinks\Platform\Drupal;

/**
 * Configure vactory_entity_canonical_access settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_entity_canonical_access_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_entity_canonical_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_entity_canonical_access.settings');
    $content_entities = $config->get('content_entities');
    $form['entities_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Existing content entities'),
    ];
    $entities = $this->getCanonicalContentEntities();
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    $roles = array_map(function ($role) {
      return $role->label();
    }, $roles);
    foreach ($entities as $id => $entity) {
      $form[$id] = [
        '#type' => 'details',
        '#title' => $entity->getLabel(),
        '#group' => 'entities_tabs',
        '#tree' => TRUE,
      ];
      $form[$id]['title'] = [
        '#markup' => '<h2>Canonical Path: <stron>' . $entity->getLinkTemplate('canonical') . '<stron></h2>',
      ];
      $form[$id]['policy'] = [
        '#type' => 'radios',
        '#titme' => 'Access policy',
        '#options' => [
          'default' => $this->t('Default access (Do nothing and keep Drupal default behavior)'),
          'roles' => $this->t('Access by roles (Only selected roles have access)'),
        ],
        '#default_value' => isset($content_entities[$id]['policy']) ? $content_entities[$id]['policy'] : 'default',
      ];
      $form[$id]['roles'] = [
        '#type' => 'select',
        '#title' => $this->t('Roles'),
        '#options' => $roles,
        '#multiple' => TRUE,
        '#states' => [
          'visible' => [
            'input[name="' . $id . '[policy]"]' => ['value' => 'roles'],
          ],
        ],
        '#default_value' => isset($content_entities[$id]['roles']) ? $content_entities[$id]['roles'] : [],
      ];

    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_entity_canonical_access.settings');
    $entities = $this->getCanonicalContentEntities();
    $values = $form_state->getValues();
    foreach ($entities as $id => &$entity) {
      $entity = [];
      $entity['policy'] = $values[$id]['policy'];
      $entity['roles'] = $values[$id]['roles'];
    }
    $config->set('content_entities', $entities)
      ->save();
    parent::submitForm($form, $form_state);
    // Rebuild cache so Drupal knows about new access policies.
    drupal_flush_all_caches();
  }

  /**
   * Get existing content entities with canonical link.
   */
  protected function getCanonicalContentEntities() {
    $entities = \Drupal::entityTypeManager()->getDefinitions();
    $entities = array_filter($entities, function ($entity) {
      // Exclude entities which are already handling canonical access.
      $excluded_entities = [
        'user',
        'node',
        'block_content',
        'comment',
        'shortcut',
        'entity_subqueue',
        'webform_submission',
        'menu_link_content',
        'redirect',
        'media',
      ];
      return $entity instanceof ContentEntityType && $entity->getLinkTemplate('canonical') && !in_array($entity->id(), $excluded_entities);
    });
    return $entities;
  }

}
