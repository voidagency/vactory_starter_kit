<?php

namespace Drupal\vactory_google_places\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Vactory Google Places settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * State service.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * Link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGenerator
   */
  protected $linkGenerator;

  /**
   * Address country repository service.
   *
   * @var \Drupal\address\Repository\CountryRepository
   */
  protected $countryRepository;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->state = $container->get('state');
    $instance->linkGenerator = $container->get('link_generator');
    $instance->countryRepository = $container->get('address.country_repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_google_places_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_google_places.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_google_places.settings');
    $countries = $this->countryRepository->getList();
    unset($countries['EH']);
    $google_api = Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
      'attributes' => ['target' => '_blank'],
    ]);
    $api_link = $this->linkGenerator->generate($this->t('Click here'), $google_api);
    $form['countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Countries'),
      '#options' => $countries,
      '#empty_option' => 'All',
      '#default_value' => !empty($config->get('countries')) ? array_map('strtoupper', $config->get('countries')) : '',
      '#description' => $this->t('Restrict the results based on country.'),
      '#multiple' => TRUE,
    ];
    $form['google_places_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#size' => 60,
      '#default_value' => !empty($this->state->get('google_places_api_key')) ? $this->state->get('google_places_api_key') : '',
      '#description' => $this->t('A free API key is needed to use the Google Maps. @click here to generate the API key', [
        '@click' => $api_link,
      ]),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $countries = $form_state->getValue('countries');
    $countries = !empty($countries) ? array_values(array_map('strtolower', $countries)) : [];
    $this->config('vactory_google_places.settings')
      ->set('countries', $countries)
      ->save();
    $this->state->set('google_places_api_key', $form_state->getValue('google_places_api_key'));
    parent::submitForm($form, $form_state);
  }

}
