<?php

namespace Drupal\vactory_starter_kit\Installer\Form;

use Drupal\vactory_starter_kit\OptionalModulesManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Provides the site configuration form.
 */
class ImportNodes extends ConfigFormBase {

//  /**
//   * The plugin manager.
//   *
//   * @var \Drupal\vactory_starter_kit\OptionalModulesManager
//   */
//  protected $optionalModulesManager;
//
//  /**
//   * Constructs a \Drupal\system\ConfigFormBase object.
//   *
//   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
//   *   The factory for configuration objects.
//   * @param \Drupal\vactory_starter_kit\OptionalModulesManager $optional_modules_manager
//   *   The factory for configuration objects.
//   */
//  public function __construct(ConfigFactoryInterface $config_factory, OptionalModulesManager $optional_modules_manager) {
//    parent::__construct($config_factory);
//    $this->optionalModulesManager = $optional_modules_manager;
//  }
//
//  /**
//   * {@inheritdoc}
//   */
//  public static function create(ContainerInterface $container) {
//    return new static(
//      $container->get('config.factory'),
//      $container->get('plugin.manager.vactory_decoupled.optional_modules')
//    );
//  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_decoupled_module_import_nodes';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['migrations'] = [
      '#type' => 'checkboxes',
      '#options' => $this->getMigrationsList(),
      '#title' => $this->t('choose migration'),
    ];


    $form['#title'] = $this->t('Import nodes');
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start importing'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $migrations = $form_state->getValue('migrations');
    $migrations_to_perform  = [];
    foreach ($migrations as $migration) {
      if ($migration !== 0) {
        $migrations_to_perform[] = $migration;
      }
    }

    $build_info = $form_state->getBuildInfo();
    $install_state = $build_info['args'][0]['forms'];

    // Determine form state based off override existence.
    $install_state['form_state_values'] = isset($install_state['form_state_values'])
      ? $install_state['form_state_values']
      : [];
    $install_state['form_state_values'] += $form_state->getValues();

//    // Iterate over the form state values to determine modules to install.
//    $values = array_filter($install_state['form_state_values']);
//    $module_values = array_filter(array_keys($values), function ($key) {
//      return strpos($key, 'install_modules_') !== FALSE;
//    });
    $install_state['vactory_decoupled_import_nodes'] = $migrations_to_perform;

    $build_info['args'][0]['forms'] = $install_state;
    $form_state->setBuildInfo($build_info);


//    $operations = [];
//    $num_operations = 0;

//    if (!empty($migrations_to_perform)) {
////      $chunk = array_chunk($migrations_to_perform, 1);
//      foreach ($migrations_to_perform as $id) {
//        $operations[] = [
//          [static::class, 'nodeImport'],
//          [$id],
//        ];
//        $num_operations++;
//      }
//      if (!empty($operations)) {
//        $batch = [
//          'title'      => 'Process of importing',
//          'operations' => $operations,
//          'finished'   => [static::class, 'rollbackFinished'],
//        ];
//        batch_set($batch);
//      }
//    }
  }

  private function getMigrationsList() {
    $migration_configs = \Drupal::configFactory()
      ->listAll('migrate_plus.migration.');
    $migrations = [];
    foreach ($migration_configs as $migration_config) {
      $config = \Drupal::configFactory()->get($migration_config);
      $source = $config->get('source');
      if (isset($source) && key_exists('plugin', $source)) {
        if ($source['plugin'] == 'csv') {
          $migrations[$migration_config] = $config->get('label');
        }
      }
    }
    return $migrations;
  }

//  /**
//   * Batch callback.
//   */
//  public static function nodeImport($id, &$context) {
//    $manager = \Drupal::service('plugin.manager.migration');
//    $migration = $manager->createInstance($id);
//    $migration->getIdMap()->prepareUpdate();
//    $executable = new MigrateExecutable($migration, new MigrateMessage());
//
//    try {
//      $executable->import();
//    }
//    catch (\Exception $e) {
//      $migration->setStatus(MigrationInterface::STATUS_IDLE);
//    }
//  }
//
//  /**
//   * Batch finished callback.
//   */
//  public static function rollbackFinished($success, $results, $operations) {
//    if ($success) {
//      $message = "import finished";
//      \Drupal::messenger()->addStatus($message);
//    }
//  }
//


}
