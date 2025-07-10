<?php

namespace Drupal\vactory_dynamic_field_usage;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManager;

/**
 * Class ParagraphsReport.
 *
 * Control various module logic like batches, lookups, and report output.
 *
 * @package Drupal\paragraphs_report
 */
class VactoryDynamicFieldUsageService {

  use StringTranslationTrait;

  /**
   * Alias manager.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * Pager class.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * VactoryDynamicFieldUsageService construct.
   */
  public function __construct(
    PagerManagerInterface $pagerManager,
    AliasManager $aliasManager,
    LanguageManagerInterface $languageManager
  ) {
    $this->pagerManager = $pagerManager;
    $this->aliasManager = $aliasManager;
    $this->languageManager = $languageManager;
  }

  /**
   * Parses the report data into tabular data.
   *
   * @return array
   *   Returns an array of tabular data.
   */
  public function getTabularData($show_links = TRUE) {
    $widget = \Drupal::request()->query->get('widget', 'all');

    $query = \Drupal::database()->select('paragraphs_item_field_data', 'pfd');
    $query->join(
      'paragraph__field_vactory_component',
      'pvc',
      'pfd.id = pvc.entity_id'
    );

    $query->fields('pfd', ['parent_id', 'langcode'])
      ->fields('pvc', ['field_vactory_component_widget_id'])
      ->condition('pfd.type', 'vactory_component')
      ->condition('pfd.status', 1)
      ->groupBy('pvc.field_vactory_component_widget_id')
      ->groupBy('pfd.parent_id')
      ->groupBy('pfd.langcode')
      ->distinct();

    if ($widget !== 'all') {
      $query->condition('pvc.field_vactory_component_widget_id', $widget);
    }

    $results = $query->execute()->fetchAll();

    $grouped_results = [];

    foreach ($results as $row) {
      $widget_id = $row->field_vactory_component_widget_id;

      if (!isset($grouped_results[$widget_id])) {
        $grouped_results[$widget_id] = [
          'widget_id' => $widget_id,
          'count' => 0,
          'items' => [],
        ];
      }

      $langcode = ($row->langcode !== 'und') ? $row->langcode : $this->languageManager->getDefaultLanguage()
        ->getId();
      $path = '/' . $langcode . '/node/' . $row->parent_id;
      $alias = $this->aliasManager->getAliasByPath($path);

      $grouped_results[$widget_id]['items'][] = [
        '#type' => 'link',
        '#title' => t("Edit : @alias", ['@alias' => $alias]),
        '#url' => Url::fromUri('internal:/' . $langcode . '/node/' . $row->parent_id . '/edit'),
      ];

      $grouped_results[$widget_id]['count']++;
    }

    $rows = [];
    foreach ($grouped_results as $group) {
      $rows[] = [
        'widget_id' => $group['widget_id'],
        'count' => $group['count'],
        'items' => [
          'data' => [
            '#type' => 'details',
            '#title' => t('Show @count items', ['@count' => $group['count']]),
            '#open' => FALSE,
            '#attributes' => [
              'class' => ['widget-items-details'],
            ],
            'content' => [
              '#theme' => 'item_list',
              '#items' => $group['items'],
              '#attributes' => [
                'class' => ['widget-items-list'],
              ],
            ],
          ],
        ],
      ];
    }

    $total = count($grouped_results);

    $header = [
      $this->t('Widget'),
      $this->t('Usages'),
      $this->t('Nodes'),
    ];

    return compact('header', 'rows', 'total');
  }

  /**
   * Get used widgets.
   */
  public function getUsedWidgets() {
    $query = \Drupal::database()
      ->select('paragraph__field_vactory_component', 'p')
      ->distinct()
      ->fields('p', ['field_vactory_component_widget_id']);
    $widgets = $query->execute()->fetchCol();
    return array_combine(array_values($widgets), array_values($widgets));
  }

}
