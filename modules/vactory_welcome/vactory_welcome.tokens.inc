<?php

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;

/**
 * Implements hook_token_info().
 */
function vactory_welcome_token_info() {
  $type = [
    'name' => t('Vactory Welcome'),
    'description' => t('Tokens for welcome messages.'),
  ];
  $node['message'] = [
    'name' => t("Welcome message"),
    'description' => t('Taxonomy message depending on the time of the day.'),
  ];
    return [
    'types' => ['vactory_welcome' => $type],
    'tokens' => ['vactory_welcome' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function vactory_welcome_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'vactory_welcome') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'message':
          $welcome_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
            ->loadTree('welcome');
          $current_lang_code = \Drupal::languageManager()
            ->getCurrentLanguage()
            ->getId();
          $term_info = [];
          if (!empty($welcome_terms) && isset($welcome_terms) ) {
            foreach ($welcome_terms as $term) {
              $term_id = Term::load($term->tid);
              $translated_term = \Drupal::service('entity.repository')
                ->getTranslationFromContext($term_id, $current_lang_code);
              $start_time = new DrupalDateTime($translated_term->get('field_time_range_v')->getValue()[0]["value"], 'UTC');
              $end_time = new DrupalDateTime($translated_term->get('field_time_range_v')->getValue()[0]["end_value"], 'UTC');
              $term_info[] = [
                'name' => $translated_term->getName(),
                'start_time' => strtotime($start_time->format('H:i')),
                'end_time'=> strtotime($end_time->format('H:i')),
              ];
            }
          }
          $welcome_value = "";
          $current_time = strtotime(date('H:i'));
          foreach($term_info as $term) {
            if ($current_time >= $term["start_time"] && $current_time < $term["end_time"]) {
              $welcome_value = $term["name"];
            }
          }
          $replacements[$original] = $welcome_value;
          break;
      }
    }
  }
  return $replacements;
}
