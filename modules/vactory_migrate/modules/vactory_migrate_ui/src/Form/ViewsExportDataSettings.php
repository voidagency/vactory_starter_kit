<?php

namespace Drupal\vactory_migrate_ui\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasRepository;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Views export data settings functions.
 */
class ViewsExportDataSettings extends ConfigFormBase {

  /**
   * Property: Number of path items in form.
   *
   * @var int
   */
  protected $viewsItemTotal = 1;

  /**
   * Temporary config, to be used by the Remove button.
   *
   * @var array
   */
  protected $tempViewsConfig = [];

  /**
   * Item id to remove.
   *
   * @var int
   */
  protected $itemToRemove;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Alias storage.
   *
   * @var \Drupal\path_alias\AliasRepository
   */
  protected $aliasStorage;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Views data.
   *
   * @var array
   *   Keyed by view ID, valued by view label.
   */
  protected $viewsData;

  /**
   * Form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState = NULL;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, AliasRepository $aliasStorage, LanguageManagerInterface $languageManager, RequestStack $requestStack) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
    $this->viewsData = $this->loadViewData();
    $this->aliasStorage = $aliasStorage;
    $this->languageManager = $languageManager;
    $this->tempViewsConfig = $this->config('vactory_migrate_ui.settings_form')->get('views');
    $this->request = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('path_alias.repository'),
      $container->get('language_manager'),
      $container->get('request_stack')
    );
  }

  /**
   * Get editable config names function.
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_migrate_ui.settings_form',
    ];
  }

  /**
   * Get form id function.
   */
  public function getFormId() {
    return 'vactory_migrate_ui_views_export';
  }

  /**
   * Build form function.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (is_null($this->formState)) {
      $this->formState = $form_state;
    }
    // Get config.
    $views = $this->tempViewsConfig;
    // Make sure to use tree.
    $form['#tree'] = TRUE;
    // Disable caching on this form.
    $form_state->setCached(FALSE);
    // Get number of views already loaded into config.
    if (!empty($views) && !$form_state->get('ajax_pressed')) {
      $this->viewsItemTotal = count($views) > 0 ? count($views) : 1;
    }
    // Build views container.
    $form['views'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('View'),
        $this->t('Display'),
        $this->t('Fields'),
        $this->t('Delimter'),
        $this->t('File infos'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No privileges.'),
      '#tableselect' => FALSE,
      '#attributes' => ['id' => 'views-container'],
    ];
    for ($i = 0; $i < $this->viewsItemTotal; $i++) {
      $form['views'][$i] = [
        '#type'       => 'fieldset',
      ];
      // View.
      $form['views'][$i]['view'] = [
        '#type'       => 'select',
        '#options' => $this->viewsData,
        '#default_value' => empty($views[$i]['view']) ? NULL : $views[$i]['view'],
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'selectView'],
          'event' => 'change',
          'wrapper' => 'views-container',
        ],
        '#attributes' => [
          'style' => 'width:120px',
        ],
      ];
      // Build view display form element, if view is selected.
      if (!empty($form_state->getUserInput()['views'][$i]['view'])) {
        $selected_view_id = $form_state->getUserInput()['views'][$i]['view'];
      }
      elseif (!empty($views[$i]['view'])) {
        $selected_view_id = $views[$i]['view'];
      }
      if (isset($selected_view_id)) {
        $form['views'][$i]['display'] = [
          '#type'       => 'select',
          '#required' => TRUE,
          '#options' => $this->getViewDisplays($selected_view_id),
          '#default_value' => empty($views[$i]['display']) ? 'default' : $views[$i]['display'],
          '#ajax' => [
            'callback' => [$this, 'selectView'],
            'event' => 'change',
            'wrapper' => 'views-container',
          ],
          '#attributes' => [
            'style' => 'width:120px',
          ],
        ];
      }
      else {
        $form['views'][$i]['display'] = [
          '#type'       => 'markup',
          '#markup' => '',
        ];
      }

      if (!empty($views[$i]['display'])) {
        $selected_view_display_id = $views[$i]['display'];
      }
      elseif (!empty($form_state->getUserInput()['views'][$i]['display'])) {
        $selected_view_display_id = $form_state->getUserInput()['views'][$i]['display'];
      }
      if (isset($selected_view_display_id) && isset($selected_view_id)) {
        $display_fileds = $this->getViewFields($selected_view_id, $selected_view_display_id);
        if (!empty($display_fileds)) {
          $form['views'][$i]['view_fields'] = [
            '#type'       => 'textarea',
            '#attributes' => [
              'style' => 'width:260px',
            ],
            '#default_value' => empty($views[$i]['view_fields']) ? $display_fileds : $views[$i]['view_fields'],
          ];

          $form['views'][$i]['delimiter'] = [
            '#type'       => 'select',
            '#required' => TRUE,
            '#options' => [
              ',' => ',',
              ';' => ';',
            ],
            '#default_value' => empty($views[$i]['delimiter']) ? ';' : $views[$i]['delimiter'],
          ];

          $form['views'][$i]['file_infos'] = [
            '#type' => 'details',
            '#title' => t('file infos'),
            '#description' => t('Les fichiers vont être exporter sous la form suivante : {path}/views_id/current_date/display_id/{file_name}.csv'),
            '#open' => FALSE,
          ];
          $form['views'][$i]['file_infos']['file_path'] = [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => t('file path'),
            '#attributes' => [
              'style' => 'width:240px',
            ],
            '#default_value' => empty($views[$i]['file_infos']['file_path']) ? '' : $views[$i]['file_infos']['file_path'],
          ];

          $form['views'][$i]['file_infos']['file_name'] = [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => t('file name'),
            '#attributes' => [
              'style' => 'width:240px',
            ],
            '#default_value' => empty($views[$i]['file_infos']['file_name']) ? '' : $views[$i]['file_infos']['file_name'],
          ];
        }
        else {
          $form['views'][$i]['view_fields'] = [
            '#type'       => 'markup',
            '#markup' => '',
          ];
          $form['views'][$i]['delimiter'] = [
            '#type'       => 'markup',
            '#markup' => '',
          ];
          $form['views'][$i]['file_infos']['file_name'] = [
            '#type'       => 'markup',
            '#markup' => '',
          ];
        }
      }
      else {
        $form['views'][$i]['view_fields'] = [
          '#type'       => 'markup',
          '#markup' => '',
        ];
        $form['views'][$i]['delimiter'] = [
          '#type'       => 'markup',
          '#markup' => '',
        ];
        $form['views'][$i]['file_infos']['file_name'] = [
          '#type'       => 'markup',
          '#markup' => '',
        ];
      }
      unset($selected_view_id);

      // Remove button.
      $form['views'][$i]['remove_item_' . $i] = [
        '#type'                    => 'submit',
        '#name'                    => 'remove_' . $i,
        '#value'                   => $this->t('Remove'),
        '#submit'                  => ['::removeItem'],
        // Since we are removing an item, don't validate until later.
        '#limit_validation_errors' => [],
        '#ajax'                    => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper'  => 'views-container',
        ],
      ];
    }
    // Add item button.
    $form['views']['actions'] = [
      '#type' => 'actions',
      'add_item' => [
        '#type'   => 'submit',
        '#value'  => $this->t('Add a new view to export'),
        '#submit' => ['::addItem'],
        '#ajax'   => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper'  => 'views-container',
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['views'])) {
      foreach ($values['views'] as $key => $view_value) {
        if (isset($view_value['add_item'])) {
          continue;
        }
        // If alias doesn't exist, consider setting a form error.
        $view = Views::getView($view_value['view']);
        if (!is_object($view)) {
          $form_state->setError($form['views'][$key]['view'], $this->t('The view !view does not exist.'));
        }
        // Verify existence of the display.
        if (empty($view->setDisplay($view_value['display']))) {
          $form_state->setError($form['views'][$key]['display'], $this->t('The view !view does not have the !display display.'));
        }

        // Verify the display type.
        $view_display = $view->getDisplay();
        if ($view_display->getPluginId() !== 'data_export') {
          $form_state->setError($form['views'][$key]['display'], $this->t('Incorrect display_id provided, expected a views data export display, found !display instead.'));
        }
      }
    }
  }

  /**
   * Load views data.
   *
   * @return array
   *   Keyed by View ID, valued by View label.
   */
  protected function loadViewData() {
    return array_map(function ($view) {
      return $view->label();
    }, $this->entityTypeManager->getStorage('view')->loadMultiple());
  }

  /**
   * Get display data for a view id.
   *
   * @param string $view_id
   *   The view name.
   *
   * @return array
   *   All view displays.
   */
  protected function getViewDisplays($view_id) {
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    return array_map(function ($display) {
      return $display['display_title'];
    }, $view->get('display'));
  }

  /**
   * Implements callback for Ajax event.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   *
   * @return array
   *   Container section of the form.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $languages = \Drupal::languageManager()->getLanguages();
    $triggering_element = $form_state->getTriggeringElement();
    $parents = $triggering_element['#array_parents'];
    $index = $parents[1];
    // Set new values if remove was pressed.
    if ($this->getCurrentRequestVariable('remove_pressed')) {
      // Get input values.
      $values = $form_state->getUserInput();
      // Remove the removed item.
      unset($values['views'][$this->itemToRemove]);
      $values['views'] = array_combine(range(0, count($values['views']) - 1), array_values($values['views']));
      // Set new values.
      $form['views'][$index]['view']['#value'] = empty($values['views'][$index]['view']) ? '' : $values['views'][$index]['view'];
      $form['views'][$index]['display']['#value'] = empty($values['views'][$index]['display']) ? 'default' : $values['views'][$index]['display'];
      if (isset($values['views'][$index]['view']) && isset($values['views'][$index]['display'])) {
        $display_fields = $this->getViewFields($values['views'][$index]['view'], $values['views'][$index]['display']);
        $form['views'][$index]['view_fields']['#value'] = $display_fields;
      }
      $form['views'][$index]['file_infos']['file_path']['#value'] = empty($values['views'][$index]['file_infos']['file_path']) ? '' : $values['views'][$index]['file_infos']['file_path'];
      $form['views'][$index]['file_infos']['file_name']['#value'] = empty($values['views'][$index]['file_infos']['file_name']) ? '' : $values['views'][$index]['file_infos']['file_name'];
      /* } */
    }
    else {
      $values = $form_state->getUserInput();
      if (isset($values['views'][$index]['view']) && isset($values['views'][$index]['display'])) {
        $display_fields = $this->getViewFields($values['views'][$index]['view'], $values['views'][$index]['display']);
        $form['views'][$index]['view_fields']['#value'] = $display_fields;
      }
    }
    $this->setCurrentRequestVariable('remove_pressed', FALSE);
    return $form['views'];
  }

  /**
   * Adds an item to form.
   *
   * @param array $form
   *   Setting form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function selectView(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    return $this->ajaxCallback($form, $form_state);
  }

  /**
   * Adds an item to form.
   *
   * @param array $form
   *   Setting form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function addItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $this->viewsItemTotal++;
    $form_state->setRebuild();
  }

  /**
   * Removes an item from form.
   *
   * @param array $form
   *   Setting form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function removeItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $this->setCurrentRequestVariable('remove_pressed', TRUE);
    $this->viewsItemTotal--;
    // Get triggering item id.
    $triggering_element = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $triggering_element['#name'], $matches);
    $item_id = (int) $matches[0][0];
    $this->itemToRemove = $item_id;
    // Remove item from config, reindex at 1, and set tempViewsConfig to it.
    unset($this->tempViewsConfig[$item_id]);
    $this->tempViewsConfig = array_combine(range(0, count($this->tempViewsConfig) - 1), array_values($this->tempViewsConfig));
    // Rebuild form.
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Add to config.
    $views_values = $form_state->getValue('views');
    unset($views_values['actions']);
    foreach ($views_values as $key => &$value) {
      unset($value['remove_item_' . $key]);
    }
    $this->config('vactory_migrate_ui.settings_form')
      ->set('views', $views_values)
      ->save();
  }

  /**
   * Set volatile variable, specific to current request time.
   *
   * @param string $name
   *   Request variable name.
   * @param mixed $value
   *   Request variable value.
   */
  protected function setCurrentRequestVariable($name, $value) {
    $vars_identifier = sha1($this->request->getCurrentRequest()->server->get('REQUEST_TIME'));
    $vars = $this->formState->get($vars_identifier) ? $this->formState->get($vars_identifier) : [];
    $vars[$name] = $value;
    $this->formState->set($vars_identifier, $vars);
  }

  /**
   * Get volatile variable, specific to current request time.
   *
   * @param mixed|null $name
   *   Request variable name.
   */
  protected function getCurrentRequestVariable($name) {
    $vars_identifier = sha1($this->request->getCurrentRequest()->server->get('REQUEST_TIME'));
    if (($vars = $this->formState->get($vars_identifier)) && isset($vars[$name])) {
      return $vars[$name];
    }
    return NULL;
  }

  /**
   * Get view fields function.
   */
  protected function getViewFields($view_id, $display_id) {
    $view = Views::getView($view_id);
    $fields = '';
    if (isset($view)) {
      $view->setDisplay($display_id);
      $view_fields = $view->display_handler->getFieldLabels();
      foreach ($view_fields as $key => $label) {
        $fields = $fields . $key . PHP_EOL;
      }
    }
    return $fields;
  }

}
