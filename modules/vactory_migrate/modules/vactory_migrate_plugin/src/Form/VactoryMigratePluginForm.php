<?php

namespace Drupal\vactory_migrate_plugin\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystem;

/**
 * Configure Vactory Migrate Plugin settings for this site.
 */
class VactoryMigratePluginForm extends FormBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * Migration process plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrateProcessPluginManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystemService;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->migrationPluginManager = $container->get('plugin.manager.migration');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->migrateProcessPluginManager = $container->get('plugin.manager.migrate.process');
    $instance->fileSystemService = $container->get('file_system');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_migrate_plugin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Init state.
    if (empty($form_state->get('state'))) {
      $state = [
        'csv_header' => [],
        'mapping_count' => 0,
        'process_plugins_count' => [],
        'csv_uri' => '',
      ];
      $migration_definitions = $this->migrationPluginManager->getDefinitions();
      $csv_migrations = array_filter($migration_definitions, fn($definition) => $definition['source']['plugin'] === 'csv');
      $csv_migrations = array_map(fn($definition) => isset($definition['label']) ? "{$definition['label']} ({$definition['id']})" : $definition['id'], $csv_migrations);
      $migration_definitions = array_map(fn($definition) => isset($definition['label']) ? "{$definition['label']} ({$definition['id']})" : $definition['id'], $migration_definitions);
      $state['csv_migrations'] = $csv_migrations;
      $state['migrations'] = $migration_definitions;
      $form_state->set('state', $state);
    }

    $state = $form_state->get('state');
    $destination = $form_state->get('destination') ?? [];

    $form['#prefix'] = '<div id="migrate_plugin_wrapper">';
    $form['#suffix'] = '</div>';

    if ($this->isEditMigrationPlugin()) {
      $form['migration_id'] = [
        '#type' => 'select',
        '#title' => $this->t('CSV Migration'),
        '#options' => $state['csv_migrations'],
        '#empty_option' => '- Select -',
        '#required' => TRUE,
        '#description' => $this->t('Select the migration plugin you want edit'),
        '#ajax' => [
          'callback' => [$this, 'triggerFormUpdate'],
        ],
      ];
    }

    if (isset($state['migration_id']) || !$this->isEditMigrationPlugin()) {
      // Build migration infos elements.
      $this->buildMigrationInfosElements($form, $form_state, $state);

      // Build Source plugin elements.
      $this->buildSourcePluginElements($form, $form_state, $state);

      // Build Destination plugin elements.
      $this->buildDestinationPluginElements($form, $form_state, $state, $destination);

      // Build migration dependencies elements.
      $this->buildMigrationDependenciesElements($form, $form_state, $state);
    }

    $form['update'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      '#submit' => [[$this, 'updateFormState']],
      '#attributes' => [
        'unique-id' => 'migrate-update-form',
        'class' => ['js-hide'],
      ],
      '#ajax' => [
        'wrapper' => 'migrate_plugin_wrapper',
        'callback' => [$this, 'updateForm'],
      ],
    ];

    if (isset($state['migration_id']) || !$this->isEditMigrationPlugin()) {
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => !$this->isEditMigrationPlugin() ? $this->t('Create migration plugin') : $this->t('Update migration plugin'),
      ];
    }

    $form['#attached']['library'][] = 'vactory_migrate_plugin/style';
    return $form;
  }

  /**
   * Update form state.
   */
  public function updateFormState(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_unique_id = $triggering_element['#attributes']['unique-id'] ?? NULL;
    $source = $form_state->getValue('source');
    $destination = $form_state->getValue('plugin_destination');
    $state = $form_state->get('state');
    $user_input = $form_state->getUserInput();
    if ($this->isEditMigrationPlugin() && (!isset($state['migration_id']) || $state['migration_id'] !== $form_state->getValue('migration_id'))) {
      $state['migration_id'] = $form_state->getValue('migration_id');
      $migration_config = \Drupal::configFactory()->getEditable("migrate_plus.migration.{$state['migration_id']}");
      $data = $migration_config->getRawData();
      // Init form state.
      $this->initFormState($data, $state, $source, $destination, $user_input);
    }

    if ($triggering_element_unique_id === 'migrate-add-mapping') {
      $state['mapping_count']++;
    }
    if ($triggering_element_unique_id === 'migrate-add-process-plugin') {
      $parents = $triggering_element['#parents'];
      $index = $parents[count($parents) - 3];
      $state['process_plugins_count'][$index] = isset($state['process_plugins_count'][$index]) ? $state['process_plugins_count'][$index] + 1 : 1;
    }
    if ($triggering_element_unique_id === 'migrate-remove-mapping') {
      $parents = $triggering_element['#parents'];
      array_pop($parents);
      $mapping_index = end($parents);
      unset($destination['mappings'][$mapping_index]);
      unset($destination['mappings']['add_mapping']);
      unset($state['process_plugins_count'][$mapping_index]);
      $state['process_plugins_count'] = array_values($state['process_plugins_count']);
      $destination['mappings'] = array_values($destination['mappings']);
      $user_input['plugin_destination']['mappings'] = $destination['mappings'];
      $state['mapping_count'] = max($state['mapping_count'] - 1, 0);
    }
    if ($triggering_element_unique_id === 'migrate-remove-process-plugin') {
      $parents = $triggering_element['#parents'];
      array_pop($parents);
      $index = end($parents);
      $mapping_index = $parents[count($parents) - 3];
      unset($destination['mappings'][$mapping_index]['process_plugins'][$index]);
      unset($destination['mappings'][$mapping_index]['process_plugins']['remove_process_plugin']);
      $destination['mappings'][$mapping_index]['process_plugins'] = array_values($destination['mappings'][$mapping_index]['process_plugins']);
      $user_input['plugin_destination']['mappings'][$mapping_index]['process_plugins'] = $destination['mappings'][$mapping_index]['process_plugins'];
      $state['process_plugins_count'][$mapping_index] = max($state['process_plugins_count'][$mapping_index] - 1, 0);
    }

    $fid = $source['csv_file'] ?? NULL;
    if (!$fid) {
      $state['csv_uri'] = '';
      $state['csv_fid'] = $fid;
      $state['csv_header'] = [];
    }
    if ($fid && (!isset($state['csv_fid']) || $fid !== $state['csv_fid'])) {
      $file = File::load(reset($fid));
      if ($file) {
        $file->setPermanent();
        $file->save();
        $uri = $file->getFileUri();
        $state['csv_uri'] = $uri;
        $state['csv_fid'] = $fid;
        $delimiter = !empty($source['delimiter']) ? $source['delimiter'] : ';';
        $state['csv_header'] = $this->getCsvHeader($uri, $delimiter);
      }
    }

    $form_state->set('source', $source);
    $form_state->set('destination', $destination);
    $form_state->set('state', $state);
    $form_state->setUserInput($user_input);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Init form State.
   */
  public function initFormState($data, &$state, &$source, &$destination, &$user_input) {
    // Init migration infos.
    $user_input['id'] = $data['id'];
    $user_input['label'] = $data['label'] ?? '';
    $user_input['migration_group'] = $data['migration_group'] ?? '';
    // Init source plugin.
    $uri = $data['source']['path'] ?? NULL;
    if (!empty($uri)) {
      $file = $this->entityTypeManager->getStorage('file')
        ->loadByProperties(['uri' => $uri]);
      if (empty($file)){
        $file = $this->createFile($uri);
      }
      if (!empty($file)) {
        $file = reset($file);
        $fid = $file->id();
        $delimiter = $data['source']['delimiter'];
        $state['csv_header'] = $this->getCsvHeader($uri, $delimiter);
        $constants_str = '';
        $constants = $data['source']['constants'] ?? $constants_str;
        if (!empty($constants)) {
          foreach ($constants as $key => $value) {
            $constants_str .= "{$key}|{$value}\n";
          }
        }
        $source = [
          'delimiter' => $delimiter,
          'ids' => array_combine($data['source']['ids'], $data['source']['ids']),
          'csv_file' => ['fids' => $fid, 'upload' => $fid],
          'constants' => trim($constants_str),
        ];
        $user_input['source'] = $source;
      }
    }
    // Init destination plugin.
    $mappings = [];
    foreach ($data['process'] as $field_name => $process_plugin) {
      if (!is_array($process_plugin)) {
        $mappings[] = [
          'type' => 'direct',
          'drupal_field' => $field_name,
          'csv_field' => $process_plugin,
        ];
        $state['process_plugins_count'][] = 0;
        continue;
      }
      $plugins = [];
      if (!array_is_list($process_plugin)){
        $process_plugin = [$process_plugin];
      }
      foreach ($process_plugin as $plugin_config) {
        $plugin = $plugin_config['plugin'];
        unset($plugin_config['plugin']);
        $config_str = '';
        foreach ($plugin_config as $key => $value) {
          if (!is_array($value)) {
            $config_str .= "{$key}|{$value}\n";
            continue;
          }
          $config_str .= "{$key}|[" . implode(',', $value) . "]\n";
        }
        $plugins[] = [
          'plugin' => $plugin,
          'configuration' => trim($config_str),
        ];
      }
      $state['process_plugins_count'][] = count($plugins);
      $mappings[] = [
        'type' => 'process',
        'drupal_field' => $field_name,
        'process_plugins' => $plugins,
      ];
    }
    $plugin = $data['destination']['plugin'] ?? '';
    $entity_type = !empty($plugin) ? explode(':', $plugin)[1] : $plugin;
    $destination = [
      'entity_type' => $entity_type,
      'bundle' => $data['destination']['default_bundle'] ?? '',
      'mappings' => $mappings,
    ];
    if (isset($data['destination']['translations'])) {
      $destination['translations'] = $data['destination']['translations'];
    }
    $state['mapping_count'] = count($mappings);
    $user_input['plugin_destination'] = $destination;
    // Init migration dependencies.
    $required_dependencies = $data['migration_dependencies']['required'] ?? [];
    $optional_dependencies = $data['migration_dependencies']['optional'] ?? [];
    $user_input['migration_dependencies']['required'] = array_combine($required_dependencies, $required_dependencies);
    $user_input['migration_dependencies']['optional'] = array_combine($optional_dependencies, $optional_dependencies);
  }

  /**
   * Get CSV Header.
   */
  public function getCsvHeader($uri, $delimiter = ';') {
    $csv = fopen($uri, 'r');
    if ($csv) {
      $header = fgetcsv($csv, NULL, $delimiter);
      return array_combine($header, $header);
    }
    return [];
  }

  /**
   * Update form.
   */
  public function updateForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="migrate_plugin_wrapper">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * Trigger form update.
   */
  public function triggerFormUpdate(array $form, FormStateInterface $form_state) {
    return vactory_migrate_trigger_form_update();
  }

  /**
   * Build source plugin elements.
   */
  public function buildSourcePluginElements(array &$form, FormStateInterface $form_state, $state) {
    $form['source'] = [
      '#type' => 'details',
      '#title' => $this->t('Source Plugin Settings'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $form['source']['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSV Delimiter'),
      '#default_value' => ';',
    ];
    $form['source']['csv_file'] = [
      '#type' => 'migrate_managed_file',
      '#title' => $this->t('CSV Model'),
      '#upload_location' => 'private://migrate-csv',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#description' => $this->t('Please upload a clean CSV model to get header from (this should not necessary contains data to be imported only header is required)'),
    ];

    if (!empty($state['csv_header'])) {
      $form['source']['ids'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('CSV IDs'),
        '#options' => $state['csv_header'],
        '#required' => TRUE,
      ];
    }

    $form['source']['constants'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Constants'),
      '#description' => $this->t('Enter your constants, one constant by line in format <strong>constantKey|constantValue</strong><br>Constants are accessible this way: constants/constantKey'),
    ];
  }

  /**
   * Build Destination Plugin elements.
   */
  public function buildDestinationPluginElements(array &$form, FormStateInterface $form_state, $state, $destination) {
    $form['plugin_destination'] = [
      '#type' => 'details',
      '#title' => $this->t('Destination Plugin Settings'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, fn($entity_type) => $entity_type instanceof ContentEntityType);
    $entity_types = array_map(fn($entity_type) => $entity_type->getLabel(), $entity_types);
    $form['plugin_destination']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Targeted entity type'),
      '#options' => $entity_types,
      '#empty_option' => '- Select -',
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'triggerFormUpdate'],
      ],
      '#description' => $this->t('Select the destination content type'),
    ];
    $form['plugin_destination']['translations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Il s'agit d'une traduction"),
    ];
    if (isset($destination['entity_type']) && !empty($destination['entity_type'])) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($destination['entity_type']);
      $bundles = array_map(fn($bundle) => $bundle['label'], $bundles);
      $form['plugin_destination']['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Targeted bundle'),
        '#options' => $bundles,
        '#empty_option' => '- Select -',
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'triggerFormUpdate'],
        ],
        '#description' => $this->t('Select the targeted bundle'),
      ];
      if (isset($destination['bundle']) && !empty($destination['bundle'])) {
        $form['plugin_destination']['mappings'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Mapping des champs'),
          '#attributes' => [
            'class' => ['migrate-mappings-wrapper'],
          ],
          '#tree' => TRUE,
        ];
        for ($i = 0; $i < $state['mapping_count']; $i++) {
          $existing = $destination['mappings'][$i] ?? NULL;
          $form['plugin_destination']['mappings'][$i] = [
            '#type' => 'details',
            '#title' => !isset($existing) ? $this->t('Nouveau mapping') : $this->t('Mapping du champ (@field)', ['@field' => $existing['drupal_field']]),
            '#tree' => TRUE,
            '#open' => TRUE,
            '#attributes' => [
              'class' => ['migrate-mapping-wrapper'],
            ],
          ];
          $field_definitions = $this->entityFieldManager->getFieldDefinitions($destination['entity_type'], $destination['bundle']);
          $fields = [];
          foreach ($field_definitions as $field_name => $field_definition) {
            $field_storage = FieldStorageConfig::loadByName($destination['entity_type'], $field_name);
            $field_label = !empty($field_definition->getLabel()) ? $field_definition->getLabel() : $field_name;
            if (!$field_storage) {
              $fields[$field_name] = $field_label;
              continue;
            }

            $field_properties = $field_storage->getPropertyDefinitions();
            if (count($field_properties) === 1 || isset($field_properties['target_id'])) {
              $fields[$field_name] = $field_label;
              continue;
            }
            foreach ($field_properties as $key => $field_property) {
              $fields["{$field_name}/{$key}"] = "{$field_label}/{$key}";
            }
          }
          $form['plugin_destination']['mappings'][$i]['drupal_field'] = [
            '#type' => 'select',
            '#title' => $this->t('Champ destination (@entity_type)', ['@entity_type' => $destination['entity_type']]),
            '#options' => $fields,
            '#empty_value' => '- Select -',
            '#required' => TRUE,
          ];
          $form['plugin_destination']['mappings'][$i]['type'] = [
            '#type' => 'select',
            '#title' => $this->t('Type mapping'),
            '#options' => [
              'direct' => $this->t('Mapping direct'),
              'process' => $this->t('Mapping via process plugin'),
            ],
            '#empty_value' => '- Select -',
            '#required' => TRUE,
            '#ajax' => [
              'callback' => [$this, 'triggerFormUpdate'],
            ],
          ];
          if (isset($destination['mappings'][$i]['type']) && !empty($destination['mappings'][$i]['type'])) {
            if ($destination['mappings'][$i]['type'] === 'direct') {
              $form['plugin_destination']['mappings'][$i]['csv_field'] = [
                '#type' => 'select',
                '#title' => $this->t('Champ source (CSV)'),
                '#options' => $state['csv_header'],
                '#empty_value' => '- Select -',
                '#required' => TRUE,
              ];
            }
            else {
              $this->buildProcessPluginElements($form, $form_state, $state, $destination, $i);
            }
          }
          $form['plugin_destination']['mappings'][$i]['remove_mapping'] = [
            '#type' => 'submit',
            '#value' => $this->t('Remove'),
            '#name' => "remove_mapping_{$i}",
            '#submit' => [[$this, 'updateFormState']],
            '#attributes' => [
              'unique-id' => 'migrate-remove-mapping',
              'class' => ['button', 'button--danger'],
            ],
            '#ajax' => [
              'wrapper' => 'migrate_plugin_wrapper',
              'callback' => [$this, 'updateForm'],
            ],
          ];
        }

        $form['plugin_destination']['mappings']['add_mapping'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add new mapping'),
          '#submit' => [[$this, 'updateFormState']],
          '#attributes' => [
            'unique-id' => 'migrate-add-mapping',
          ],
          '#ajax' => [
            'wrapper' => 'migrate_plugin_wrapper',
            'callback' => [$this, 'updateForm'],
          ],
        ];
      }
    }
  }

  /**
   * Build process plugin elements.
   */
  public function buildProcessPluginElements(array &$form, FormStateInterface $form_state, $state, $destination, $mapping_index) {
    $form['plugin_destination']['mappings'][$mapping_index]['process_plugins'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Process plugins'),
      '#tree' => TRUE,
      '#description' => $this->t('Process plugins will be executed in the same order they have on this form'),
      '#attributes' => [
        'class' => ['migrate-process-plugins-wrapper'],
      ],
    ];
    if (isset($state['process_plugins_count'][$mapping_index])) {
      for ($i = 0; $i < $state['process_plugins_count'][$mapping_index]; $i++) {
        $existing = $destination['mappings'][$mapping_index]['process_plugins'][$i] ?? NULL;
        $form['plugin_destination']['mappings'][$mapping_index]['process_plugins'][$i] = [
          '#type' => 'details',
          '#title' => !isset($existing) ? $this->t('Nouveau process plugin') : $this->t('Process plugin [@plugin]', ['@plugin' => $existing['plugin']]),
          '#open' => !isset($existing),
          '#tree' => TRUE,
          '#attributes' => [
            'class' => ['migrate-process-plugin-wrapper'],
          ],
        ];
        $process_definitions = $this->migrateProcessPluginManager->getDefinitions();
        $process_definitions = array_map(fn($definition) => "{$definition['id']} [Provider: {$definition['provider']}]", $process_definitions);
        $process_definitions = array_filter($process_definitions, fn($plugin_id) => !in_array($plugin_id, $this->excludedProcessPlugins()), ARRAY_FILTER_USE_KEY);
        $form['plugin_destination']['mappings'][$mapping_index]['process_plugins'][$i]['plugin'] = [
          '#type' => 'select',
          '#title' => $this->t('Plugin'),
          '#options' => $process_definitions,
          '#empty_value' => '- Select -',
          '#required' => TRUE,
        ];
        $form['plugin_destination']['mappings'][$mapping_index]['process_plugins'][$i]['configuration'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Plugin configuration'),
          '#description' => $this->t('Enter the process plugin configuration, one configuration by line with format configKey|configValue <strong>Ex:</strong> default_value|toto<br>If the configuration is an array then enter configKey|[configValue1,configValue2,configValue3] <strong>Ex:</strong> source|[LATITUDE,LONGITUDE]'),
        ];

        $form['plugin_destination']['mappings'][$mapping_index]['process_plugins'][$i]['remove_process_plugin'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => "remove_mapping_{$mapping_index}_{$i}",
          '#submit' => [[$this, 'updateFormState']],
          '#attributes' => [
            'unique-id' => 'migrate-remove-process-plugin',
            'class' => ['button', 'button--danger'],
          ],
          '#ajax' => [
            'wrapper' => 'migrate_plugin_wrapper',
            'callback' => [$this, 'updateForm'],
          ],
        ];

      }
    }
    $form['plugin_destination']['mappings'][$mapping_index]['process_plugins']['add_process_plugin'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add new process plugin'),
      '#name' => "add_process_plugin_{$mapping_index}_{$i}",
      '#submit' => [[$this, 'updateFormState']],
      '#attributes' => [
        'unique-id' => 'migrate-add-process-plugin',
      ],
      '#ajax' => [
        'wrapper' => 'migrate_plugin_wrapper',
        'callback' => [$this, 'updateForm'],
      ],
    ];

  }

  /**
   * Build migration dependencies elements.
   */
  public function buildMigrationDependenciesElements(array &$form, FormStateInterface $form_state, $state) {
    $form['migration_dependencies'] = [
      '#type' => 'details',
      '#title' => $this->t('Migration dependencies'),
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $form['migration_dependencies']['required'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Required migration dependencies'),
      '#options' => $state['migrations'],
    ];
    $form['migration_dependencies']['optional'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Optional migration dependencies'),
      '#options' => $state['migrations'],
    ];
  }

  /**
   * Build migration infos elements.
   */
  public function buildMigrationInfosElements(array &$form, FormStateInterface $form_state, $state) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Migration label'),
      '#required' => TRUE,
    ];
    if (!$this->isEditMigrationPlugin()) {
      $form['id'] = [
        '#type' => 'machine_name',
        '#title' => $this->t('Migration id'),
        '#machine_name' => [
          'exists' => '\Drupal\vactory_migrate_plugin\Form\VactoryMigratePluginForm::migrationExists',
        ],
        '#required' => TRUE,
      ];
    }
    if ($this->isEditMigrationPlugin()) {
      $form['id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Migration id'),
        '#required' => TRUE,
        '#disabled' => TRUE,
        '#value' => $state['migration_id'],
      ];
    }

    $groups = $this->entityTypeManager->getStorage('migration_group')
      ->loadMultiple();
    $groups = array_map(fn($group) => $group->label(), $groups);
    $current_path = $current_path = \Drupal::service('path.current')->getPath();
    $link = Url::fromRoute('entity.migration_group.add_form', ['destination' => $current_path])
      ->toString(TRUE)
      ->getGeneratedUrl();
    $form['migration_group'] = [
      '#type' => 'select',
      '#title' => $this->t('Migration label'),
      '#options' => $groups,
      '#empty_option' => '- Select -',
      '#required' => TRUE,
      '#description' => $this->t('Select an existing migration group or <a href="@link">Create new migration group</a>', ['@link' => $link]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_unique_id = $triggering_element['#attributes']['unique-id'] ?? NULL;
    $no_validations_triggers = [
      'migrate-remove-mapping',
      'migrate-update-form',
      'migrate-remove-process-plugin',
    ];
    if (in_array($triggering_element_unique_id, $no_validations_triggers)) {
      $form_state->clearErrors();
    }
    if ($triggering_element_unique_id === 'migrate-add-mapping' || $triggering_element_unique_id === 'migrate-add-process-plugin') {
      $errors = $form_state->getErrors();
      $form_state->clearErrors();
      foreach ($errors as $key => $message) {
        if (str_starts_with($key, 'plugin_destination][mappings][')) {
          $form_state->setErrorByName($key, $message);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $state = $form_state->get('state');
    $entity_type = $values['plugin_destination']['entity_type'];
    $bundle = $values['plugin_destination']['bundle'];
    $mappings = array_filter($values['plugin_destination']['mappings'], fn($key) => is_numeric($key), ARRAY_FILTER_USE_KEY);
    $migration_dependencies = $values['migration_dependencies'];
    $migration_dependencies['required'] = array_filter($migration_dependencies['required']);
    $migration_dependencies['optional'] = array_filter($migration_dependencies['optional']);
    $csv_ids = $values['source']['ids'];
    $csv_ids = array_values(array_filter($csv_ids));
    $csv_fields = [];
    foreach ($state['csv_header'] as $field) {
      $csv_fields[] = [
        'id' => $field,
        'name' => $field,
      ];
    }
    $data = [
      'id' => $values['id'],
      'label' => $values['label'],
      'migration_group' => $values['migration_group'],
      'source' => [
        'plugin' => 'csv',
        'path' => $state['csv_uri'],
        'header_row_count' => 1,
        'delimiter' => $values['source']['delimiter'],
        'ids' => $csv_ids,
        'fields' => $csv_fields,
      ],
      'destination' => [
        'plugin' => "entity:{$entity_type}",
      ],
      'migration_dependencies' => $migration_dependencies,
    ];

    if (!empty($values['source']['constants'])) {
      $constants = isset($values['source']['constants']) ? explode("\n", $values['source']['constants']) : [];
      foreach ($constants as &$constant) {
        $constant = $constant ? trim($constant) : $constant;
        $constant = $constant ? explode('|', $constant) : [];
        if (count($constant) === 2) {
          $constant = [trim($constant[0]) => trim($constant[1])];
        }
      }
      if (!empty($constants)) {
        $data['source']['constants'] = $constant;
      }
    }

    if ($entity_type !== $bundle) {
      $data['destination']['default_bundle'] = $bundle;
    }

    if ($values['plugin_destination']['translations']) {
      $data['destination']['translations'] = TRUE;
    }

    foreach ($mappings as $mapping) {
      if ($mapping['type'] === 'direct') {
        $data['process'][$mapping['drupal_field']] = $mapping['csv_field'];
      }
      else {
        $process_plugins = array_filter($mapping['process_plugins'], fn($key) => is_numeric($key), ARRAY_FILTER_USE_KEY);
        foreach ($process_plugins as $process_plugin) {
          $configuration = $process_plugin['configuration'];
          $process = [];
          if (!empty($configuration)) {
            $configurations = explode("\n", $configuration);
            foreach ($configurations as $configuration) {
              $configuration = explode('|', $configuration);
              if (count($configuration) === 2) {
                $key = trim($configuration[0]);
                $value = trim($configuration[1]);
                if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                  $value = trim($value, "[]");
                  $value = explode(',', $value);
                }
                $process[$key] = $value;
              }
            }
          }
          $data['process'][$mapping['drupal_field']][] = [
            'plugin' => $process_plugin['plugin'],
            ...$process,
          ];
        }
      }
    }

    $migration_config = \Drupal::configFactory()->getEditable("migrate_plus.migration.{$values['id']}");
    $migration_config->setData($data);
    $migration_config->save();
    drupal_flush_all_caches();
    $operation = $this->isEditMigrationPlugin() ? 'updated' : 'created';
    \Drupal::messenger()->addStatus($this->t('Migration plugin has been @operation successfully', ['@operation' => $operation]));
  }

  /**
   * Check migration existence.
   */
  public static function migrationExists($id) {
    $existing_ids = array_keys(\Drupal::service('plugin.manager.migration')->getDefinitions());
    return in_array($id, $existing_ids);
  }

  /**
   * Is edit migration plugin form.
   */
  public function isEditMigrationPlugin() {
    $route_name = \Drupal::routeMatch()->getRouteName();
    return $route_name === 'vactory_migrate_plugin.edit';
  }

  /**
   * Excluded process plugins.
   */
  public function excludedProcessPlugins() {
    return [
      'block_plugin_id',
      'block_settings',
      'block_visibility',
      'block_region',
      'block_theme',
      'captcha_type_formatter',
      'd7_field_type_defaults',
      'd7_field_option_translation',
      'd7_field_instance_option_translation',
      'd7_field_instance_settings',
      'd7_field_instance_defaults',
      'd7_field_settings',
      'd6_field_type_defaults',
      'd6_field_option_translation',
      'd6_field_instance_option_translation',
      'field_formatter_settings_defaults',
      'd6_field_field_settings',
      'field_instance_widget_settings',
      'd6_field_instance_defaults',
      'd6_field_file',
      'd6_imagecache_actions',
      'd7_metatag_entities',
      'd6_nodewords_entities',
      'node_update_7008',
      'd6_url_alias_language',
      'rate_widgets_process_options',
      'rate_widgets_process_types',
      'd7_path_redirect',
      'd7_redirect_source_query',
      'd6_path_redirect',
      'system_update_7000',
      'user_update_8002',
      'd6_profile_field_option_translation',
      'user_update_7002',
      'field_collection_field_settings',
      'field_collection_field_instance_settings',
      'paragraphs_process_on_value',
      'paragraphs_field_settings',
      'paragraphs_field_instance_settings',
    ];
  }

  private function createFile($path){
    if (!file_exists($path)){
      return;
    }
    $destination = 'private://migration-edit';

    if (!file_exists($destination)) {
      mkdir($destination, 0777);
    }

    $copied_file = $this->fileSystemService->copy($path, $destination, FileSystem::EXISTS_RENAME);
    $filename = explode('/',$path);
    $file = File::create([
      'uid'      => 1,
      'filename' => end($filename),
      'uri'      => $copied_file,
      'status'   => 1,
    ]);
    $file->save();
    return [$file];
  }

}
