<?php

namespace Drupal\vactory_frequent_searches\Plugin\Block;

use Drupal\Core\Database\Database;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Index;

/**
 * Provides a 'FrequentSearchBlock' block.
 *
 * @Block(
 *   id = "frequent_search_api_block",
 *   admin_label = @Translation("Frequent Search API block"),
 *   deriver = "Drupal\vactory_frequent_searches\Plugin\Derivative\FrequentSearchesDerivative"
 * )
 */

class FrequentSearchesBlock extends BlockBase  {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $numPhrases = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,
      16, 17, 18, 19, 20, 25, 30];
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // Number of top search phrases to display.
    $form['num_phrases'] = [
      '#type' => 'select',
      '#title' => t('Number of top search phrases to display'),
      '#default_value' => empty($config['num_phrases']) ? 8 : $config['num_phrases'],
      '#options' => array_combine($numPhrases, $numPhrases),
    ];

    // Path of search page.
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => t('Path of search page'),
      '#default_value' => empty($config['path']) ? 'search' : $config['path'],
    ];

    // Parameter name for the search phrase.
    $form['param_name'] = [
      '#type' => 'textfield',
      '#title' => t('Parameter name for the search phrase'),
      '#default_value' => empty($config['param_name']) ? 'search' : $config['param_name'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {

    $this->setConfigurationValue('num_phrases', $form_state->getValue('num_phrases'));
    $this->setConfigurationValue('path', $form_state->getValue('path'));
    $this->setConfigurationValue('param_name', $form_state->getValue('param_name'));
  }

  /**
   * build function.
   * @return array
   */
  public function build() {

    $config = $this->getConfiguration();
    $stats = $this->getStats();

    return [
      '#theme' => 'frequent_searches_api_block',
      '#path' => $config['path'],
      '#param_name' => $config['param_name'],
      '#stats' => $stats,
      '#cache' => [
        'max-age' => 0
      ],
    ];
  }

  /**
   * @return array
   */
  protected function getStats() {
    $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $result = [];
    $database = Database::getConnection();
    $config = $this->getConfiguration();
    $indexName = $this->getDerivativeId();
    $stats = $database->queryRange(
      "SELECT keywords, numfound as num FROM vactory_frequent_searches WHERE language = :lang AND i_name=:i_name AND keywords != '' AND total_results != 0 ORDER BY num DESC",
      0,
      $config['num_phrases'],
      [
        ':i_name' => $indexName,
        ':lang' => $lang_code
      ]
    );
    foreach ($stats as $stat) {
      $result[$stat->keywords] = $stat->num;
    }

    return $result;
  }

  /**
   * @return string
   */
  protected function getServer() {

    $result = '';
    $index = $this->getIndex();
    if (!empty($index)) {
      $result = $index->get('server');
    }

    return $result;
  }

  /**
   * @return Index
   */
  protected function getIndex() {
    $id = $this->getDerivativeId();
    $result = Index::load($id);;
    return $result;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheContexts() {
    return ['url', 'url.query_args'];
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheTags() {
    return ['vactory_frequent_searches'];
  }
}
