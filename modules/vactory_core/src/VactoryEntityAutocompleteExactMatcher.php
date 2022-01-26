<?php

namespace Drupal\vactory_core;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Entity\EntityAutocompleteMatcherInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Site\Settings;

/**
 * Matcher class override to get the exact autocompletion results for entity reference.
 */
class VactoryEntityAutocompleteExactMatcher implements EntityAutocompleteMatcherInterface {

  /**
   * Entity autocomplete matcher service.
   *
   * @var EntityAutocompleteMatcherInterface
   */
  protected $entityAutocompleteMatcher;

  /**
   * The entity reference selection handler plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * The site settings service.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $siteSettings;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    EntityAutocompleteMatcherInterface $entityAutocompleteMatcher,
    SelectionPluginManagerInterface $selectionManager,
    Settings $siteSettings
  ) {
    $this->entityAutocompleteMatcher = $entityAutocompleteMatcher;
    $this->selectionManager = $selectionManager;
    $this->siteSettings = $siteSettings;
  }

  /**
   * {@inheritDoc}
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $autocomplete_matcher_settings = $this->siteSettings->get('entity_autocomplete_exact_matcher');
    $opener = isset($autocomplete_matcher_settings['opener']) && !empty($autocomplete_matcher_settings['opener']) ? $autocomplete_matcher_settings['opener'] : '{';
    $closer = isset($autocomplete_matcher_settings['closer']) && !empty($autocomplete_matcher_settings['closer']) ? $autocomplete_matcher_settings['closer'] : '}';
    $separator = $opener === '#' ? '/' : '#';
    if (!preg_match($separator . '^[' . $opener . '](.)+' . $closer . '$' . $separator, $string)) {
      // No opener and closer characters detected so keep core behavior.
      return $this->entityAutocompleteMatcher->getMatches($target_type, $selection_handler, $selection_settings, $string);
    }

    $matches = [];
    $options = $selection_settings + [
        'target_type' => $target_type,
        'handler' => $selection_handler,
      ];
    $handler = $this->selectionManager->getInstance($options);
    $string = trim(str_replace([$opener, $closer], '', $string));
    if (!empty($string)) {
      // Get an array of exact matching entities (The operator now is = instead of CONTAINS).
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : '=';
      $match_limit = isset($selection_settings['match_limit']) ? (int) $selection_settings['match_limit'] : 10;
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, $match_limit);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {
          $key = "$label ($entity_id)";
          // Strip things like starting/trailing white spaces, line breaks and
          // tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }

    return $matches;

  }

}
