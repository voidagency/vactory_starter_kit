<?php

namespace Drupal\vactory_google_places\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views_autocomplete_filters\Plugin\views\filter\ViewsAutocompleteFiltersString;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Autocomplete for basic textfield filter to handle string filtering commands
 * including equality, like, not like, etc.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("vactory_google_places")
 */
class VactoryGooglePlacesString extends ViewsAutocompleteFiltersString {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    $instance->state = $container->get('state');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();
    $options['vactory_google_places'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritDoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['vactory_google_places'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Vactory google places instance'),
      '#default_value' => $this->options['vactory_google_places'],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $exposed = $form_state->get('exposed');
    if (!$exposed || empty($this->options['vactory_google_places'])) {
      // It is not an exposed form.
      // Or it is not a vactory google places filter instance.
      return;
    }

    if (empty($form['value']['#type']) || $form['value']['#type'] !== 'textfield') {
      // Not a textfield element.
      return;
    }

    // Get current language ID.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    // Get google places API key from module settings.
    $api_key = $this->state->get('google_places_api_key') ?? '';
    // Get allowed countries from module settings.
    $countries =  $this->configFactory->get('vactory_google_places.settings')
      ->get('countries');
    $countries = array_values(array_map('strtolower', $countries ?? []));
    $form['value']['#attributes']['class'][] = 'vactory-google-places';
    $form['value']['#maxlength'] = NULL;
    if (isset($this->options['expose']['placeholder']) && !empty($this->options['expose']['placeholder'])) {
      $form['value']['#attributes']['placeholder'] = $this->options['expose']['placeholder'];
    }
    $google_map_key = [
      '#tag' => 'script',
      '#attributes' => ['src' => '//maps.googleapis.com/maps/api/js?key=' . $api_key . '&sensor=true&libraries=places&language=' . $langcode],
    ];
    $form['#attached']['html_head'][] = [$google_map_key, 'googleMapKey'];
    $form['#attached']['drupalSettings']['place']['autocomplete'] = $countries;
    $form['#attached']['library'][] = 'vactory_google_places/autocomplete';
  }

}
