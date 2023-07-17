<?php

namespace Drupal\vactory_dynamic_import\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;


/**
 *
 * Use this plugin to import multiple terms ( vactory dynamic import ).
 *
 * Example:
 *
 * @code
 *
 * process:
 *  field_vactory_news_theme:
 *    plugin: dynamic_term_import
 *    bundle: vactory_news_theme
 *    source: terms
 *
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "dynamic_term_import"
 * )
 */
class DynamicTermImport extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if ($value == '') {
      return NULL;
    }

    $vid = $this->configuration['bundle'];

    $terms = explode('|', $value);

    $result = [];

    foreach ($terms as $term) {
      $splitted = explode(':', $term);
      $name = $splitted[0];
      $legacy_id = $splitted[1];

      $founded = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
          'name' => $name,
          'vid'  => $vid,
        ]);

      if (count($founded) > 0) {
        $result[] = reset($founded)->id();
      }
      else {
        $new_term = Term::create([
          'vid'       => $vid,
          'name'      => $name,
          'legacy_id' => $legacy_id,
        ]);
        $new_term->save();
        $result[] = $new_term->id();
      }

    }

    return $result;
  }

}
