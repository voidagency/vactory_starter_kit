<?php

namespace Drupal\vactory_views_pretty_path\Form;

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
 * Class VactoryViewsPrettyPathAdminForm.
 */
class VactoryViewsPrettyPathAdminForm extends ConfigFormBase {

  /**
   * Property: Number of path items in form.
   *
   * @var int
   */
  protected $pathItemTotal = 1;

  /**
   * Temporary config, to be used by the Remove button.
   *
   * @var array
   */
  protected $tempPathsConfig = [];

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
    $this->tempPathsConfig = $this->config('vactory_views_pretty_path.settings')->get('paths');
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
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vactory_views_pretty_path.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_views_pretty_path_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (is_null($this->formState)) {
      $this->formState = $form_state;
    }
    // Get config.
    $paths = $this->tempPathsConfig;
    // Make sure to use tree.
    $form['#tree'] = TRUE;
    // Disable caching on this form.
    $form_state->setCached(FALSE);
    // Get number of paths already loaded into config.
    if (!empty($paths) && !$form_state->get('ajax_pressed')) {
      $this->pathItemTotal = count($paths) > 0 ? count($paths) : 1;
    }
    // Build paths container.
    $form['paths'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Path to Rewrite'),
        $this->t('View'),
        $this->t('Display'),
        $this->t('Views Filter Identifier-Name Mapping'),
        '',
      ],
      '#empty' => $this->t('No privileges.'),
      '#tableselect' => FALSE,
      '#attributes' => ['id' => 'paths-container'],
    ];
    for ($i = 0; $i < $this->pathItemTotal; $i++) {
      $form['paths'][$i] = [
        '#type'       => 'fieldset',
      ];
      // Path.
      $form['paths'][$i]['path'] = [
        '#type'       => 'textfield',
        '#attributes' => ['placeholder' => $this->t('Path to rewrite')],
        '#size'       => 50,
        '#required' => TRUE,
        '#default_value' => empty($paths[$i]['path']) ? '' : $paths[$i]['path'],
      ];
      // View.
      $form['paths'][$i]['view'] = [
        '#type'       => 'select',
        '#options' => $this->viewsData,
        '#default_value' => empty($paths[$i]['view']) ? NULL : $paths[$i]['view'],
        '#required' => TRUE,
        '#ajax' => [
          'callback' => [$this, 'selectView'],
          'event' => 'change',
          'wrapper' => 'paths-container',
        ],
      ];
      // Build view display form element, if view is selected.
      if (!empty($paths[$i]['view'])) {
        $selected_view_id = $paths[$i]['view'];
      }
      elseif (!empty($form_state->getUserInput()['paths'][$i]['view'])) {
        $selected_view_id = $form_state->getUserInput()['paths'][$i]['view'];
      }
      if (isset($selected_view_id)) {
        $form['paths'][$i]['display'] = [
          '#type'       => 'select',
          // '#title' => $this->t('Display'),
          '#required' => TRUE,
          '#options' => $this->getViewDisplays($selected_view_id),
          '#default_value' => empty($paths[$i]['display']) ? 'default' : $paths[$i]['display'],
          '#ajax' => [
            'callback' => [$this, 'selectView'],
            'event' => 'change',
            'wrapper' => 'paths-container',
          ],
        ];
      }
      else {
        $form['paths'][$i]['display'] = [
          '#type'       => 'markup',
          '#markup' => '',
        ];
      }

      // Build view display filters form element, if view display is selected.
      if (!empty($paths[$i]['display'])) {
        $selected_view_display_id = $paths[$i]['display'];
      }
      elseif (!empty($form_state->getUserInput()['paths'][$i]['display'])) {
        $selected_view_display_id = $form_state->getUserInput()['paths'][$i]['display'];
      }
      if (isset($selected_view_display_id) && isset($selected_view_id)) {
        $taxonomy_term_exposed_filters = $this->getViewDisplaysTaxonomyTermFilters($selected_view_id, $selected_view_display_id);
        if (!empty($taxonomy_term_exposed_filters)) {
          $taxonomy_filters_identifier_str = '';
          foreach ($taxonomy_term_exposed_filters as $filter_field => $identifier) {
            $taxonomy_filters_identifier_str .= $filter_field . '|' . $identifier . PHP_EOL;
          }
          $languages = \Drupal::languageManager()->getLanguages();
          $form['paths'][$i]['filter_map_container'] = [
            '#type' => 'fieldset',
            '#description' => $this->t('Simplify views filter identifiers by mapping them to a special name (without accented letters). Use the following syntax, each rule separated by a new line: filter_identifier|name'),
          ];
          foreach ($languages as $langcode => $language) {
            $form['paths'][$i]['filter_map_container'][$langcode] = [
              '#type' => 'details',
              '#title' => $language->getName(),
              '#collapsed' => TRUE,
              '#suffix' => '<br>',
            ];
            // Views filter-name mapping.
            $form['paths'][$i]['filter_map_container'][$langcode]['views_filter_name_map'] = [
              '#type'       => 'textarea',
              '#size'       => 50,
              // '#title' => $this->t('Views Filter Identifier-Name Mapping'),
              '#default_value' => empty($paths[$i]['filter_map_container'][$langcode]['views_filter_name_map']) ? $taxonomy_filters_identifier_str : $paths[$i]['filter_map_container'][$langcode]['views_filter_name_map'],
            ];
          }
        }
        else {
          $form['paths'][$i]['views_filter_name_map'] = [
            '#type'       => 'markup',
            '#markup' => '',
          ];
        }
      }
      else {
        $form['paths'][$i]['views_filter_name_map'] = [
          '#type'       => 'markup',
          '#markup' => '',
        ];
      }
      unset($selected_view_id);

      // Remove button.
      $form['paths'][$i]['remove_item_' . $i] = [
        '#type'                    => 'submit',
        '#name'                    => 'remove_' . $i,
        '#value'                   => $this->t('Remove'),
        '#submit'                  => ['::removeItem'],
        // Since we are removing an item, don't validate until later.
        '#limit_validation_errors' => [],
        '#ajax'                    => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper'  => 'paths-container',
        ],
      ];
    }
    // Add item button.
    $form['paths']['actions'] = [
      '#type' => 'actions',
      'add_item' => [
        '#type'   => 'submit',
        '#value'  => $this->t('Add a new path to rewrite'),
        '#submit' => ['::addItem'],
        '#ajax'   => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper'  => 'paths-container',
        ],
      ],
    ];
    $form['note'] = [
      '#type' => 'markup',
      '#markup' => $this->t('(Note: Choose a view the filters of which should be used in rewriting. The view must be displayed on the above path. This module can target only one view per path.)'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['paths'])) {
      $path_collector = [];
      foreach ($values['paths'] as $key => $path_value) {
        if (isset($path_value['add_item'])) {
          continue;
        }
        // If alias doesn't exist, consider setting a form error.
        if (is_numeric($key) && !$this->aliasStorage->lookupByAlias($path_value['path'], $this->languageManager->getDefaultLanguage()->getId())) {
          $set_error = TRUE;
          // Check to make sure the selected view display isn't a page,
          // With that path.
          if ((!empty($path_value['view'])) && ($view = $this->entityTypeManager->getStorage('view')->load($path_value['view']))) {
            if (
              !empty($view->getDisplay($path_value['display'])['display_options']['path']) &&
              $view->getDisplay($path_value['display'])['display_options']['path'] == ltrim($path_value['path'], '/')
            ) {
              $set_error = FALSE;
            }
          }
          if ($set_error) {
            $form_state->setError($form['paths'][$key]['path'], $this->t('The path provided does not exist in the system.'));
          }
        }
        // Make sure the user only selects one view per path.
        if (is_numeric($key)) {
          if (in_array($path_value['path'], $path_collector)) {
            $form_state->setError($form['paths'][$key]['path'], $this->t('You cannot rewrite the path, @path, more than once.', ['@path' => $path_value['path']]));
          }
          $path_collector[] = $path_value['path'];
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
    if (!$this->getCurrentRequestVariable('remove_pressed')) {
      // Get input values.
      $values = $form_state->getUserInput();
      // Remove the removed item.
      unset($values['paths'][$this->itemToRemove]);
      $values['paths'] = array_combine(range(0, count($values['paths']) - 1), array_values($values['paths']));
      // Set new values.
      /* for ($i = 0; $i < $this->pathItemTotal; $i++) { */
      $form['paths'][$index]['path']['#value'] = empty($values['paths'][$index]['path']) ? '' : $values['paths'][$index]['path'];
      $form['paths'][$index]['view']['#value'] = empty($values['paths'][$index]['view']) ? '' : $values['paths'][$index]['view'];
      $form['paths'][$index]['display']['#value'] = empty($values['paths'][$index]['display']) ? '' : $values['paths'][$index]['display'];
      if (isset($values['paths'][$index]['view']) && isset($values['paths'][$index]['display'])) {
        $taxonomy_term_exposed_filters = $this->getViewDisplaysTaxonomyTermFilters($values['paths'][$index]['view'], $values['paths'][$index]['display']);
        if (!empty($taxonomy_term_exposed_filters)) {
          $taxonomy_filters_identifier_str = '';
          foreach ($taxonomy_term_exposed_filters as $filter_field => $identifier) {
            $taxonomy_filters_identifier_str .= $filter_field . '|' . $identifier . PHP_EOL;
          }
        }
        foreach ($languages as $langcode => $language) {
          $form['paths'][$index]['filter_map_container'][$langcode]['views_filter_name_map']['#value'] = $taxonomy_filters_identifier_str;
        }
      }
      /* } */
    }
    $this->setCurrentRequestVariable('remove_pressed', FALSE);
    return $form['paths'];
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
    $this->pathItemTotal++;
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
    $this->pathItemTotal--;
    // Get triggering item id.
    $triggering_element = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $triggering_element['#name'], $matches);
    $item_id = (int) $matches[0][0];
    $this->itemToRemove = $item_id;
    // Remove item from config, reindex at 1, and set tempPathsConfig to it.
    unset($this->tempPathsConfig[$item_id]);
    $this->tempPathsConfig = array_combine(range(0, count($this->tempPathsConfig) - 1), array_values($this->tempPathsConfig));
    // Rebuild form.
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Add to config.
    $paths_values = $form_state->getValue('paths');
    unset($paths_values['actions']);
    foreach ($paths_values as $key => &$value) {
      unset($value['remove_item_' . $key]);
    }
    $this->config('vactory_views_pretty_path.settings')
      ->set('paths', $paths_values)
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
   * Get available filter for the given view display id.
   */
  protected function getViewDisplaysTaxonomyTermFilters($selected_view_id, $selected_display_id) {
    $view = Views::getView($selected_view_id);
    $view_filters = $view->getHandlers('filter', $selected_display_id);
    $taxonomy_term_exposed_filters = [];
    foreach ($view_filters as $key => $filter) {
      if (isset($filter['exposed']) && $filter['exposed'] && $filter['plugin_id'] == 'taxonomy_index_tid') {
        $taxonomy_term_exposed_filters[$key] = $filter['expose']['identifier'];
      }
    }
    return $taxonomy_term_exposed_filters;
  }

}
