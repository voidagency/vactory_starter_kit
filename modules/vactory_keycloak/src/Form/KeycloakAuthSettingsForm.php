<?php

namespace Drupal\vactory_keycloak\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Url;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Social Auth Keycloak.
 */
class KeycloakAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   Used to check if route exists.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Used to check if path is valid and exists.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   Holds information about the current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteProviderInterface $route_provider, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory, $route_provider, $path_validator);
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
    // Load the services required to construct this class.
      $container->get('config.factory'),
      $container->get('router.route_provider'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_keycloak_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['vactory_keycloak.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_keycloak.settings');

    $form['kc_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Keycloak App settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create an Application client on your Keycloak server.'),
    ];

    $form['kc_settings']['app_server_url'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Server URL'),
      '#default_value' => $config->get('app_server_url'),
      '#description' => $this->t('Example https://localhost:8443/auth'),
    ];


    $form['kc_settings']['app_realm'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application Realm'),
      '#default_value' => $config->get('app_realm'),
    ];

    $form['kc_settings']['app_client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('App Client ID'),
      '#default_value' => $config->get('app_client_id'),
    ];

    $form['kc_settings']['app_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App client secret'),
      '#default_value' => $config->get('app_client_secret'),
    ];

    $form['kc_settings']['oauth_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Valid OAuth redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Valid OAuth redirect URIs</em> field of your Keycloak App settings.'),
      '#default_value' => Url::fromRoute('vactory_keycloak.callback')->setAbsolute()->toString(),
    ];

    $form['ck_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['ck_settings']['advanced']['scopes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Scopes for API call'),
      '#default_value' => $config->get('scopes'),
      '#description' => $this->t('Define any additional scopes to be requested, separated by a comma (e.g.: user_birthday,user_location).<br>
                                  The scopes \'email\' and \'public_profile\' are added by default and always requested.<br>
                                  You can see the full list of valid scopes and their description <a href="@scopes">here</a>.', ['@scopes' => 'https://developers.facebook.com/docs/facebook-login/permissions/']),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('vactory_keycloak.settings')
      ->set('app_server_url', $values['app_server_url'])
      ->set('app_realm', $values['app_realm'])
      ->set('app_client_id', $values['app_client_id'])
      ->set('app_client_secret', $values['app_client_secret'])
      ->set('oauth_redirect_url', $values['oauth_redirect_url'])
      ->set('scopes', $values['scopes'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
