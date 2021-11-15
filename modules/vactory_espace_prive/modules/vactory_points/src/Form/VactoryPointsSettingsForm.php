<?php

namespace Drupal\vactory_points\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Vactory Points settings form.
 */
class VactoryPointsSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_points.settings'];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'vactory_points_settigns';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_points.settings');
    $form = parent::buildForm($form, $form_state);
    if (empty($form_state->get('removed_rules'))) {
      $form_state->set('removed_rules', []);
    }
    if (empty($form_state->get('stored_values'))) {
      $form_state->set('stored_values', []);
    }
    $rules = !empty($form_state->getUserInput()) ? $form_state->getUserInput()['rules'] : $config->get('rules');
    if (empty($form_state->get('rules_count'))) {
      $form_state->set('rules_count', 1);
      if (!empty($rules)) {
        $form_state->set('rules_count', count($rules));
      }
    }
    $rules_count = $form_state->get('rules_count');
    $removed_rules = $form_state->get('removed_rules');
    $actions = [
      'view' => $this->t('View'),
      'comment' => $this->t('Comment'),
      'add_node' => $this->t('Add node'),
    ];
    if ($this->moduleHandler->moduleExists('flag')) {
      $actions['flag'] = $this->t('Flag');
      $actions['unflag'] = $this->t('Unflag');
    }
    if ($this->moduleHandler->moduleExists('vactory_sondage')) {
      $actions['vote'] = $this->t('Sondage vote');
    }
    $actions['other'] = $this->t('Other');
    // Get available content types.
    $node_types = $this->entityTypeManager->getStorage('node_type')
      ->loadMultiple();
    $node_type_options = [];
    $node_type_options['all'] = $this->t('All');
    foreach ($node_types as $node_type) {
      $node_type_options[$node_type->id()] = $node_type->label();
    }

    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $role_options = [];
    $role_options['all'] = $this->t('All Roles');
    foreach ($roles as $role) {
      $role_options[$role->id()] = $role->label();
    }

    $ajax_wrapper_id = 'vactory_points_settings_container';

    $form['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => $ajax_wrapper_id,
      ],
    ];
    $form['container']['status_message'] = [
      '#type' => 'status_message',
      '#title' => $this->t('Vactory Points Rules'),
    ];
    $form['container']['rules'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Action'),
        $this->t('Content types'),
        $this->t('Roles'),
        $this->t('Points infos'),
      ],
    ];
    $flags = [];
    if ($this->moduleHandler->moduleExists('flag')) {
      /** @var \Drupal\flag\FlagService $flags */
      $flag_manager = \Drupal::service('flag');
      $flags = $flag_manager->getAllFlags();
      $flags = array_map(function ($flag) {
        return $flag->label();
      }, $flags);
    }

    $j = -1;
    for ($i = 0; $i < $rules_count; $i++) {
      if (in_array($i, $removed_rules, TRUE)) {
        continue;
      }
      $j++;
      $form['container']['rules'][$i]['container'] = [
        '#type' => 'container',
      ];
      $form['container']['rules'][$i]['container']['name'] = [
        '#markup' => '<p><small>' . $this->t('Rule N° @num', ['@num' => $j + 1]) . '</small></p>',
      ];
      $form['container']['rules'][$i]['container']['remove_rule'] = [
        '#type' => 'submit',
        '#name' => 'remove_rule_' . $i,
        '#value' => $this->t('Remove Rule'),
        '#submit' => [[$this, 'rulesSubmit']],
        '#limit_validation_errors' => [],
        '#attributes' => [
          'class' => ['button button--danger'],
          'style' => 'margin:0',
        ],
        '#ajax' => [
          'callback' => [$this, 'updateRulesTable'],
          'wrapper' => $ajax_wrapper_id,
        ],
      ];
      $form['container']['rules'][$i]['action'] = [
        '#type' => 'container',
      ];
      $form['container']['rules'][$i]['action']['value'] = [
        '#type' => 'select',
        '#options' => $actions,
        '#required' => TRUE,
        '#default_value' => isset($rules[$i]['action']) ? $rules[$i]['action']['value'] : '',
      ];
      $form['container']['rules'][$i]['action']['other_action_value'] = [
        '#type' => 'textfield',
        '#size' => 15,
        '#title' => $this->t('Other action key'),
        '#default_value' => isset($rules[$i]['action']['other_action_value']) ? $rules[$i]['action']['other_action_value'] : '',
        '#states' => [
          'visible' => [
            'select[name="rules[' . $i . '][action][value]"]' => ['value' => 'other'],
          ],
        ],
      ];
      if ($this->moduleHandler->moduleExists('flag')) {
        $form['container']['rules'][$i]['action']['concerned_flags'] = [
          '#type' => 'checkboxes',
          '#title' => $this->t('Concerned flags'),
          '#options' => $flags,
          '#default_value' => isset($rules[$i]['action']['concerned_flags']) ? $rules[$i]['action']['concerned_flags'] : [],
          '#description' => '<small>' . $this->t('Select the concerned flags') . '</small>',
          '#states' => [
            'visible' => [
              'select[name="rules[' . $i . '][action][value]"]' => [
                ['value' => 'flag'],
                ['value' => 'unflag'],
              ],
            ],
          ],
        ];
      }
      $form['container']['rules'][$i]['action']['action_label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Action label'),
        '#size' => 15,
        '#required' => TRUE,
        '#default_value' => isset($rules[$i]['action']['action_label']) ? $rules[$i]['action']['action_label'] : '',
        '#description' => '<small>' . $this->t('This will be appeared on the notification message.') . '</small>',
      ];
      $form['container']['rules'][$i]['action']['no_repeat'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Once a time by node'),
        '#default_value' => isset($rules[$i]['action']['no_repeat']) ? $rules[$i]['action']['no_repeat'] : '',
      ];
      $form['container']['rules'][$i]['node_type'] = [
        '#type' => 'select',
        '#options' => $node_type_options,
        '#multiple' => TRUE,
        '#default_value' => isset($rules[$i]['node_type']) ? $rules[$i]['node_type'] : '',
      ];
      $form['container']['rules'][$i]['roles'] = [
        '#type' => 'select',
        '#options' => $role_options,
        '#default_value' => isset($rules[$i]['roles']) ? $rules[$i]['roles'] : '',
        '#multiple' => TRUE,
      ];
      $form['container']['rules'][$i]['points_info'] = [
        '#type' => 'container',
      ];
      $form['container']['rules'][$i]['points_info']['operation'] = [
        '#type' => 'radios',
        '#title' => $this->t('Operation'),
        '#options' => [
          'increment' => '<small>' . $this->t('Increment') . '</small>',
          'decrement' => '<small>' . $this->t('Decrement') . '</small>',
        ],
        '#required' => TRUE,
        '#default_value' => isset($rules[$i]['points_info']['operation']) ? $rules[$i]['points_info']['operation'] : 'increment',
        '#prefix' => '<div class="rounded">',
        '#suffix' => '</div>',
      ];
      $form['container']['rules'][$i]['points_info']['points'] = [
        '#type' => 'number',
        '#title' => $this->t('Points'),
        '#min' => 0,
        '#required' => TRUE,
        '#default_value' => isset($rules[$i]['points_info']['points']) ? $rules[$i]['points_info']['points'] : '',
        '#attributes' => [
          'style' => 'max-width: 50%',
        ],
      ];
    }

    $form['container']['add_rule'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Rule'),
      '#submit' => [[$this, 'rulesSubmit']],
      '#validate' => [[$this, 'rulesValidate']],
      '#ajax' => [
        'callback' => [$this, 'updateRulesTable'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];

    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Notifications settings'),
      '#tree' => TRUE,
    ];
    $form['notifications']['increment'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Increment operation'),
      '#description' => <<<EOL
        Available tokens:
        <ul>
          <li>@points: The action points value.</li>
          <li>@action_label: Action label text.</li>
          <li>@entity_title: Concerned entity title.</li>
        </ul>
      EOL,
    ];
    $form['notifications']['decrement'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Decrement operation'),
      '#description' => <<<EOL
        Available tokens:
        <ul>
          <li>@points: The action points value.</li>
          <li>@action_label: Action label text.</li>
          <li>@entity_title: Concerned entity title.</li>
        </ul>
      EOL,
    ];
    $form['notifications']['increment']['notification_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification title'),
      '#default_value' => !empty($config->get('notifications')) ? $config->get('notifications')['increment']['notification_title'] : '',
    ];
    $form['notifications']['increment']['notification_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notification Message'),
      '#default_value' => !empty($config->get('notifications')) ? $config->get('notifications')['increment']['notification_message'] : '',
    ];
    $form['notifications']['decrement']['notification_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification title'),
      '#default_value' => !empty($config->get('notifications')) ? $config->get('notifications')['decrement']['notification_title'] : '',
    ];
    $form['notifications']['decrement']['notification_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notification Message'),
      '#default_value' => !empty($config->get('notifications')) ? $config->get('notifications')['decrement']['notification_message'] : '',
    ];

    return $form;
  }

  /**
   * Rules table validation.
   */
  public function rulesValidate($form, FormStateInterface $formState) {
    $errors = $formState->getErrors();
    $formState->clearErrors();
    foreach ($errors as $key => &$message) {
      if (empty($message)) {
        switch ($key) {
          case str_ends_with($key, 'action][value'):
            $field_name = 'Action';
            break;

          case str_ends_with($key, 'node_type'):
            $field_name = 'Content type';
            break;

          case str_ends_with($key, 'operation'):
            $field_name = 'Operation';
            break;

          case str_ends_with($key, 'points'):
            $field_name = 'Points';
            break;

          default:
            $field_name = '';
            break;
        }
        $message = $this->t('@field_name field is required', ['@field_name' => $field_name]);
      }
      $formState->setErrorByName($key, $message);
    }
    $rules = $formState->getValue('rules');
    foreach ($rules as $key => $rule) {
      if ($rule['action']['value'] === 'other' && empty($rule['action']['other_action_value'])) {
        $formState->setErrorByName('rules[' . $key . '][action][other_action_value]', $this->t('Other action key field of Rule N° @num is required', ['@num' => $key]));
      }
    }
  }

  /**
   * Add rules submit function.
   */
  public function rulesSubmit($form, FormStateInterface $formState) {
    $triggering_element = $formState->getTriggeringElement();
    $action = end($triggering_element['#array_parents']);
    $parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $index = end($parents);
    $rules_count = $formState->get('rules_count');
    if ($action === 'add_rule') {
      $rules_count++;
    }

    if ($action === 'remove_rule') {
      $removed_rules = $formState->get('removed_rules');
      $removed_rules[] = $index;
      $formState->set('removed_rules', $removed_rules);
    }

    $formState->set('rules_count', $rules_count);
    $formState->setRebuild();
  }

  /**
   * Update rules table.
   */
  public function updateRulesTable($form, FormStateInterface $formState) {
    return $form['container'];
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->configFactory()->getEditable('vactory_points.settings');
    $config->set('rules', array_values($form_state->getValue('rules')))
      ->set('notifications', $form_state->getValue('notifications'))
      ->save();
  }

}
