<?php

namespace Drupal\vactory_frequent_searches\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\search_api\Entity\Index;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FrequentSearchesAdminForm.
 *
 * @package Drupal\FrequentSearchesAdminForm\Form
 */
class FrequentSearchesAdminForm extends FormBase {

  /**
   * Languages varibale.
   * @var array
   */
  protected $languages = [];

  /**
   * indexes variable.
   * @var array
   */
  protected $indexes = [];

  /**
   * Array contain frequent keywords with results.
   * @var
   */
  protected $frequent_searches_data_with_results = [];

  /**
   * Array contain frequent keywords without results.
   * @var
   */
  protected $frequent_searches_data_with_no_results = [];

  /**
   * Item id to remove.
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
   * To add ne item.
   * @var int
   */
  protected $new_item = 0;

  /**
   * Form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState = NULL;

  /**
   * Language manager.
   * @var ConfigurableLanguageManager
   */
  protected $language_manager;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigurableLanguageManager $languageManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->language_manager = $languageManager;
    $this->getLanguages();
    $this->getSearchIndex();
    $this->frequent_searches_data_with_results = \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->fetchKeywordsWithResultsFromDatabase();
    $this->frequent_searches_data_with_no_results = \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->fetchKeywordsWithNoResultsFromDatabase();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_frequent_searches_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (is_null($this->formState)) {
      $this->formState = $form_state;
    }
    // Get The number of Unuseless keywords.
    $lang_id = $this->language_manager->getCurrentLanguage()->getId();
    $total_keywords_without_results = (int) \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->getCountOfKeywordsWithoutResults($lang_id);
    $total_keywords_with_results = (int) \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->getCountOfKeywords($lang_id);
    // Disable caching on this form.
    $form_state->setCached(FALSE);

    $form['#attached']['library'][] = 'core/jquery.form';
    $form['#attached']['library'][] = 'core/drupal.ajax';
    // Build frequent searches container.
    $form['clear_searches'] = [
      '#type' => 'fieldset',
      '#attributes' => ['id' => 'searches-messages'],
    ];
    $form['clear_searches']['frequent_searches_statistics'] = [
      '#enable' => 'markup',
      '#markup' => '<h2>Total of searched keywords is : <b>' . ($total_keywords_without_results + $total_keywords_with_results) . '</b></h2>',
    ];
    $form['clear_searches']['actions'][1] = [
      '#type' => 'actions',
      'add_item' => [
        '#type'   => 'submit',
        '#value'  => $this->t('Clear useless keywords'),
        '#description' => $this->t('Clear all keywords With The number of times searched is less or equal than 1..'),
        '#submit' => ['::clearDatabase'],
      ],
    ];
    $form['clear_searches']['actions'][2] = [
      '#type' => 'actions',
      'add_item' => [
        '#type'   => 'submit',
        '#value'  => $this->t('Add new keyword'),
        '#submit' => ['::addItem'],
        '#ajax'   => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper'  => 'searches-container',
        ],
      ],
    ];
    $form['pagination_container_results'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display:flex',
      ],
    ];
    $form['pagination_container_results']['pagination_select'] = [
      '#type' => 'select',
      '#options' => [
        25 => 25,
        50 => 50,
        100 => 100,
        150 => 150,
        200 => 200,
        300 => 300,
        500 => 500,
      ],
      '#empty_option' => t('- Select number of rows to display -'),
    ];
    $form['pagination_container_results'][-1]['apply_paginate'] = [
      '#type'                    => 'submit',
      '#name'                    => 'addItem',
      '#value'                   => $this->t('Apply'),
      '#submit'                  => ['::applyPagination'],
      '#ajax'   => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper'  => 'searches-container',
      ],
    ];

    $form['table_with_title'] = [
      '#enable' => 'markup',
      '#markup' => '<h3>Keywords with results : (Total : ' . $total_keywords_with_results . ')</h3>',
    ];
    $form['frequent_searches'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Keywords'),
        $this->t('The number of times searched'),
        $this->t('Total of results -updated-'),
        $this->t('Language'),
        $this->t('Search index'),
        $this->t('last search date'),
        $this->t('Update'),
        $this->t('Delete'),
      ],
      '#responsive' => FALSE,
      '#empty' => $this->t('0 Results was found.'),
      '#tableselect' => FALSE,
      '#prefix' => '<div class="table-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => ['id' => 'searches-container'],
    ];

    // Add new item.
    if ($this->new_item == 1) {
      $form['frequent_searches'][-1]['searched_keyword'] = [
        '#type' => 'textfield',
        '#attributes' => [
          'style' => 'width:200px',
        ],
        '#required' => TRUE,
      ];
      $form['frequent_searches'][-1]['searched_keyword_count'] = [
        '#type' => 'number',
        '#attributes' => [
          'style' => 'width:80px',
        ],
        '#required' => TRUE,
      ];
      $form['frequent_searches'][-1]['total_of_searches'] = [
        '#type' => 'number',
        '#attributes' => [
          'style' => 'width:80px',
        ],
        '#required' => TRUE,
      ];
      $form['frequent_searches'][-1]['language'] = [
        '#type' => 'select',
        '#options' => $this->languages,
      ];
      $form['frequent_searches'][-1]['search_index'] = [
        '#type' => 'select',
        '#options' => $this->indexes,
      ];
      $form['frequent_searches'][-1]['searched_keyword_date'] = [
        '#enable' => 'markup',
        '#markup' => date('d/m/Y H:i:s', time()),
      ];
      $form['frequent_searches'][-1]['add_item'] = [
        '#type'                    => 'submit',
        '#name'                    => 'addItem',
        '#value'                   => $this->t('Add item'),
        '#submit'                  => ['::addToDatabase'],
        '#limit_validation_errors' => [],
      ];
    }

    foreach ($this->frequent_searches_data_with_results as $key => $search) {

      $form['frequent_searches'][$key] = [
        '#type' => 'fieldset',
      ];
      $form['frequent_searches'][$key]['searched_keyword'] = [
        '#type' => 'textfield',
        '#attributes' => [
          'style' => 'width:200px',
        ],
        '#required' => TRUE,
        '#default_value' => $search['keywords'],
      ];
      $form['frequent_searches'][$key]['searched_keyword_count'] = [
        '#type' => 'number',
        '#attributes' => [
          'style' => 'width:80px',
        ],
        '#required' => TRUE,
        '#default_value' => $search['numfound'],
      ];
      $form['frequent_searches'][$key]['total_of_searches'] = [
        '#enable' => 'markup',
        '#markup' => $search['total_results'],
      ];
      $form['frequent_searches'][$key]['language'] = [
        '#enable' => 'markup',
        '#markup' => $search['language'],
      ];
      $form['frequent_searches'][$key]['search_index'] = [
        '#enable' => 'markup',
        '#markup' => $search['i_name'],
      ];
      $form['frequent_searches'][$key]['searched_keyword_date'] = [
        '#enable' => 'markup',
        '#markup' => date('d/m/Y H:i:s', $search['timestamp']),
      ];
      $form['frequent_searches'][$key]['update_item_' . $key] = [
        '#type'                    => 'submit',
        '#name'                    => 'update_' . $search['id'] . '_position_' . $key,
        '#value'                   => $this->t('Update'),
        '#submit'                  => ['::updateItem'],
        '#limit_validation_errors' => [],
      ];
      $form['frequent_searches'][$key]['remove_item_' . $key] = [
        '#type'                    => 'submit',
        '#name'                    => 'remove_' . $search['id'] . '_position_' . $key,
        '#value'                   => $this->t('Remove'),
        '#submit'                  => ['::removeItem'],
        '#limit_validation_errors' => [],
        '#ajax'                    => [
          'callback' => [$this, 'ajaxCallback'],
          'wrapper'  => 'searches-container',
        ],
      ];

    }

    // Table for Searches with no results :
    $form['pagination_container_without_results'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display:flex',
      ],
    ];
    $form['pagination_container_without_results']['pagination_select_without_result'] = [
      '#type' => 'select',
      '#options' => [
        25 => 25,
        50 => 50,
        100 => 100,
        150 => 150,
        200 => 200,
        300 => 300,
        500 => 500,
      ],
      '#empty_option' => t('- Select number of rows to display -'),
    ];
    $form['pagination_container_without_results']['paginate_2'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Apply'),
      '#submit' => ['::applyPaginationForKeywordsWithoutResults'],
      '#ajax'   => [
        'callback' => [$this, 'ajaxCallbackNoResults'],
        'wrapper'  => 'searches-no-results-container',
      ],
    ];

    $form['table_without_title'] = [
      '#enable' => 'markup',
      '#markup' => '<h3>Keywords without results : (Total : ' . $total_keywords_without_results . ')</h3>',
    ];
    $form['frequent_searches_no_results'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Keywords'),
        $this->t('The number of times searched'),
        $this->t('Language'),
        $this->t('Search index'),
        $this->t('last search date'),
        $this->t('Delete'),
      ],
      '#responsive' => FALSE,
      '#empty' => $this->t('0 Results was found.'),
      '#tableselect' => FALSE,
      '#prefix' => '<div class="table-wrapper">',
      '#suffix' => '</div>',
      '#attributes' => ['id' => 'searches-no-results-container'],
    ];


    foreach ($this->frequent_searches_data_with_no_results as $key => $search) {

      $form['frequent_searches_no_results'][$key] = [
        '#type' => 'fieldset',
      ];
      $form['frequent_searches_no_results'][$key]['searched_keyword'] = [
        '#enable' => 'markup',
        '#markup' => $search['keywords'],
      ];
      $form['frequent_searches_no_results'][$key]['searched_keyword_count'] = [
        '#enable' => 'markup',
        '#markup' => $search['numfound'],
      ];
      $form['frequent_searches_no_results'][$key]['language'] = [
        '#enable' => 'markup',
        '#markup' => $search['language'],
      ];
      $form['frequent_searches_no_results'][$key]['search_index'] = [
        '#enable' => 'markup',
        '#markup' => $search['i_name'],
      ];
      $form['frequent_searches_no_results'][$key]['searched_keyword_date'] = [
        '#enable' => 'markup',
        '#markup' => date('d/m/Y H:i:s', $search['timestamp']),
      ];
      $form['frequent_searches_no_results'][$key]['remove_item_' . $key] = [
        '#type'                    => 'submit',
        '#name'                    => 'remove_' . $search['id'] . '_position_' . $key,
        '#value'                   => $this->t('Remove'),
        '#submit'                  => ['::removeKeywordWithoutResultItem'],
        '#limit_validation_errors' => [],
        '#ajax'                    => [
          'callback' => [$this, 'ajaxCallbackNoResults'],
          'wrapper'  => 'searches-no-results-container',
        ],
      ];

    }

    return $form;
  }

  /**
   * Ajax Call back function.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['frequent_searches'];
  }

  /**
   * Ajax Call back function.
   */
  public function ajaxCallbackNoResults(array $form, FormStateInterface $form_state) {
    return $form['frequent_searches_no_results'];
  }

  /**
   * To clear all unuseless keywords from db.
   */
  public function clearDatabase(array &$form, FormStateInterface $form_state) {
    \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->clearDatabaseFromUnuselessKeywords();
    foreach ($this->frequent_searches_data_with_results as $key => $search) {
      if ($search['numfound'] <= 1) {
        unset($this->frequent_searches_data_with_results[$key]);
      }
    }
    \Drupal::messenger()->addStatus(t('The database is clear now.'));
    $form_state->setRedirect('vactory_frequent_searches.frequent_searches_admin_form');
    return;
  }

  /**
   * Add Search fields.
   */
  public function addItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $this->new_item = 1;
    $form_state->setRebuild();
  }

  /**
   * Add Search fields.
   */
  public function applyPaginationForKeywordsWithoutResults(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $select = $form_state->getValue('pagination_select_without_result');
    if (!empty($select)) {
      $this->frequent_searches_data_with_no_results = \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
        ->fetchKeywordsWithNoResultsFromDatabase((int) $select);
    }
    $form_state->setRebuild();
  }

  /**
   * Add Search fields.
   */
  public function applyPagination(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $select = $form_state->getValue('pagination_select');
    if (!empty($select)) {
      $this->frequent_searches_data_with_results = \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
        ->fetchKeywordsWithResultsFromDatabase((int) $select);
    }
    $form_state->setRebuild();
  }

  /**
   * Function to remove one item.
   */
  public function removeItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $triggering_element = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $triggering_element['#name'], $matches);
    $item_id = (int) $matches[0][0];
    $item_position = (int) $matches[0][1];
    \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->deleteKeywordsFromDatabase($item_id);
    \Drupal::messenger()->addStatus(t('Keywords deleted with success'));
    unset($this->frequent_searches_data_with_results[$item_position]);
    $form_state->setRebuild();
  }

  /**
   * Function to remove one item.
   */
  public function removeKeywordWithoutResultItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $triggering_element = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $triggering_element['#name'], $matches);
    $item_id = (int) $matches[0][0];
    $item_position = (int) $matches[0][1];
    \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->deleteKeywordsFromDatabase($item_id);
    \Drupal::messenger()->addStatus(t('Keywords deleted with success'));
    unset($this->frequent_searches_data_with_no_results[$item_position]);
    $form_state->setRebuild();
  }

  /**
   * Function to update one item.
   */
  public function updateItem(array &$form, FormStateInterface $form_state) {
    $form_state->set('ajax_pressed', TRUE);
    $triggering_element = $form_state->getTriggeringElement();
    preg_match_all('!\d+!', $triggering_element['#name'], $matches);
    $item_id = (int) $matches[0][0];
    $item_position = (int) $matches[0][1];
    $keyword = $form['frequent_searches'][$item_position]['searched_keyword']['#value'];
    if (empty($keyword)) {
      \Drupal::messenger()->addError(t('Attention! le champ keywords est vide.'));
      return;
    }
    $keyword_count = $form['frequent_searches'][$item_position]['searched_keyword_count']['#value'];
    if (!is_numeric($keyword_count) || empty($keyword_count)) {
      \Drupal::messenger()->addError(t('Attention! le champ Nombre de fois doit être un nombre.'));
      return;
    }
    \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->updateKeywordById($item_id, $keyword, $keyword_count);
    \Drupal::messenger()->addStatus('success');
    $form_state->setRedirect('vactory_frequent_searches.frequent_searches_admin_form');
    return;

  }

  /**
   * This function is triggered after click on the add search keyword button.
   */
  public function addToDatabase(array &$form, FormStateInterface $form_state) {
    $keyword = $form['frequent_searches'][-1]['searched_keyword']['#value'];
    if (empty($keyword)) {
      \Drupal::messenger()->addError(t('Attention! le champ keywords est vide.'));
      return;
    }
    $keyword_count = $form['frequent_searches'][-1]['searched_keyword_count']['#value'];
    if (!is_numeric($keyword_count)) {
      \Drupal::messenger()->addError(t('Attention! le champ Nombre de fois doit être un nombre.'));
      return;
    }
    $keyword_total_results = $form['frequent_searches'][-1]['total_of_searches']['#value'];
    if (!is_numeric($keyword_total_results)) {
      \Drupal::messenger()->addError(t('Attention! le champ Total des résultats doit être un nombre.'));
      return;
    }
    $lang = $form['frequent_searches'][-1]['language']['#value'];
    $search_index = $form['frequent_searches'][-1]['search_index']['#value'];
    \Drupal::messenger()->addStatus('success');
    \Drupal::service('vactory_frequent_searches.frequent_searches_controller')
      ->addKeywordToDatabasa($keyword, $keyword_count, $lang, $search_index, $keyword_total_results);
    $form_state->setRedirect('vactory_frequent_searches.frequent_searches_admin_form');
    return;
  }

  /**
   * Get Languages.
   */
  public function getLanguages() {
    $languages = $this->language_manager->getLanguages();
    foreach ($languages as $language) {
      $this->languages[$language->getId()] = $language->getName();
    }
  }

  /**
   * Get Search Indexes.
   */
  public function getSearchIndex() {
    $indexes = Index::loadMultiple();
    foreach ($indexes as $key => $index) {
      $this->indexes[$key] = $index->label();
    }
  }

  /**
   * Submit form function.
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }

}
