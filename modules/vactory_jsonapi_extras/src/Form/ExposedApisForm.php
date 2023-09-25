<?php

namespace Drupal\vactory_jsonapi_extras\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposed APIs form.
 *
 * @property \Drupal\vactory_jsonapi_extras\ExposedApisInterface $entity
 */
class ExposedApisForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    $current_user = \Drupal::currentUser();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the exposed apis.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\vactory_jsonapi_extras\Entity\ExposedApis::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New route path'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->path(),
      '#description' => $this->t('The new route path'),
      '#required' => TRUE,
    ];

    $form['is_custom_resource'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Il s'agit d'une resource personnalisée"),
      '#access' => $current_user->hasPermission('administer exposed filters'),
      '#default_value' => $this->entity->isCustomResource(),
    ];

    $form['custom_controller'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Custom controller method (with namespace)"),
      '#default_value' => $this->entity->getCustomController(),
      '#access' => $current_user->hasPermission('administer exposed filters'),
      '#description' => 'Drupal\vactory_test\Controller\test::index',
      '#states' => [
        'visible' => [
          'input[name="is_custom_resource"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $enabled_jsonapi_resources = \Drupal::entityTypeManager()->getStorage('jsonapi_resource_config')
      ->loadByProperties([
        'disabled' => FALSE,
      ]);
    $enabled_jsonapi_resources = array_map(function ($resource) {
      return $resource->resourceType . ' (Path: ' . $resource->path . ')';
    }, $enabled_jsonapi_resources);

    $form['original_resource'] = [
      '#type' => 'select',
      '#title' => $this->t('Original resource'),
      '#options' => $enabled_jsonapi_resources,
      '#default_value' => $this->entity->originalResource(),
      '#access' => $current_user->hasPermission('administer exposed filters'),
      '#description' => $this->t('Resource to override, only enabled jsonapi resources are listed'),
      '#states' => [
        'visible' => [
          'input[name="is_custom_resource"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['tokens'] = $this->get_token_tree();

    $filter_desc = "
      Default JSON:API filters to be applied, enter one query params per line, ex: <br>
      filter[filter-1][condition][path]=title <br>
      filter[filter-1][condition][value]=Lorem ipsum <br>
      If a request contains a 'filter' query string parameter those filters will be added to the defaults.
    ";
    $fields_desc = "
      fields[node--vactory_publication]=body,title,vactory_document <br>
      fields[file--file]=uri,url <br>
    ";
    $include_desc = "
      include=vactory_document,vactory_thematic,vactory_media_image <br>
    ";

    $form['filters'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default filters'),
      '#default_value' => $this->entity->getFilters(),
      '#description' => $filter_desc,
      '#placeholder' => 'Ex: filter[filter-1][condition][path]=title',
      '#access' => $current_user->hasPermission('administer exposed filters'),
      '#states' => [
        'visible' => [
          'input[name="is_custom_resource"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default fields'),
      '#default_value' => $this->entity->getFields(),
      '#access' => $current_user->hasPermission('administer api exposed filters'),
      '#description' => $filter_desc,
      '#placeholder' => 'Ex: fields[resource-type]=field1,field2...',
      '#states' => [
        'visible' => [
          'input[name="is_custom_resource"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['includes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Default includes'),
      '#default_value' => $this->entity->getIncludes(),
      '#access' => $current_user->hasPermission('administer exposed filters'),
      '#description' => $filter_desc,
      '#placeholder' => 'Ex: include=field1,field2...',
      '#states' => [
        'visible' => [
          'input[name="is_custom_resource"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $packages = \Drupal::entityTypeManager()->getStorage('api_package')
      ->loadMultiple();
    $packages = array_map(function ($package) {
      return $package->label();
    }, $packages);

    if (!empty($packages)) {
      $form['packages'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Packages'),
        '#options' => $packages,
        '#default_value' => $this->entity->packages() ?? [],
      ];
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $path = $form_state->getValue('path');
    if (!empty($path)) {
      if (strpos($path, '/') !== 0) {
        $form_state->setErrorByName('path', 'Path field should start with an "/"');
      }
      elseif ($this->entity->isNew()) {
        $url_object = \Drupal::service('path.validator')->getUrlIfValid($path);
        if ($url_object) {
          $form_state->setErrorByName('path', $this->t('The given path "@path" is already used', ['@path' => $path]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new exposed apis %label.', $message_args)
      : $this->t('Updated exposed apis %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    // Flush drupal cache so Drupal knows about new routes updates.
    drupal_flush_all_caches();
    return $result;
  }

  /**
   * Function providing the site token tree link.
   */
  protected function get_token_tree() {
    $token_tree = [
      '#theme' => 'token_tree_link',
      '#show_restricted' => TRUE,
      '#weight' => 90,
    ];
    return [
      '#type' => 'markup',
      '#markup' => \Drupal::service('renderer')->render($token_tree),
    ];
  }

}
