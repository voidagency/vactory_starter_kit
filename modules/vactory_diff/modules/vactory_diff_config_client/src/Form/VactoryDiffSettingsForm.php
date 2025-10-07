<?php

namespace Drupal\vactory_diff_config_client\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vactory_diff_config_client\Service\ConfigComparisonService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Vactory Diff Client settings.
 */
class VactoryDiffSettingsForm extends ConfigFormBase {

  /**
   * The config comparison service.
   *
   * @var \Drupal\vactory_diff_config_client\Service\ConfigComparisonService
   */
  protected $comparisonService;

  /**
   * Constructs a VactoryDiffSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\vactory_diff_config_client\Service\ConfigComparisonService $comparison_service
   *   The comparison service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConfigComparisonService $comparison_service) {
    parent::__construct($config_factory);
    $this->comparisonService = $comparison_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('vactory_diff_config_client.comparison')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vactory_diff_config_client.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_diff_config_client_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vactory_diff_config_client.settings');

    $form['connection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration de la connexion'),
      '#collapsible' => FALSE,
    ];

    $form['connection']['remote_url'] = [
      '#type' => 'url',
      '#title' => $this->t("URL de l'instance distante"),
      '#description' => $this->t("URL complète de l'instance Drupal distante (ex: https://example.com)"),
      '#default_value' => $config->get('remote_url'),
      '#required' => TRUE,
      '#placeholder' => 'https://example.com',
    ];

    $form['connection']['connection_timeout'] = [
      '#type' => 'number',
      '#title' => $this->t('Timeout de connexion (secondes)'),
      '#description' => $this->t("Durée maximale d'attente pour la connexion au serveur distant."),
      '#default_value' => $config->get('connection_timeout') ?: 30,
      '#min' => 5,
      '#max' => 300,
      '#step' => 5,
      '#required' => TRUE,
    ];

    $form['connection']['test_connection'] = [
      '#type' => 'button',
      '#value' => $this->t('Tester la connexion'),
      '#ajax' => [
        'callback' => '::testConnectionAjax',
        'wrapper' => 'test-connection-result',
        'effect' => 'fade',
      ],
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    $form['connection']['test_result'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'test-connection-result'],
    ];

    // Afficher les informations de la dernière comparaison si disponible.
    $last_comparison_timestamp = $this->comparisonService->getLastComparisonTimestamp();
    if ($last_comparison_timestamp) {
      $form['last_comparison'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Dernière comparaison'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $form['last_comparison']['info'] = [
        '#type' => 'item',
        '#markup' => $this->t('Dernière comparaison effectuée le @date', [
          '@date' => date('d/m/Y H:i:s', strtotime($last_comparison_timestamp)),
        ]),
      ];

      $comparison_results = $this->comparisonService->loadComparisonResults();
      if (!empty($comparison_results['summary'])) {
        $summary = $comparison_results['summary'];
        $form['last_comparison']['summary'] = [
          '#type' => 'item',
          '#markup' => $this->t('Résumé : @added ajoutées, @modified modifiées, @deleted supprimées', [
            '@added' => $summary['added'],
            '@modified' => $summary['modified'],
            '@deleted' => $summary['deleted'],
          ]),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback for testing connection.
   */
  public function testConnectionAjax(array &$form, FormStateInterface $form_state) {
    $remote_url = $form_state->getValue('remote_url');

    if (empty($remote_url)) {
      $form['connection']['test_result']['message'] = [
        '#type' => 'item',
        '#markup' => '<div class="messages messages--error">' . $this->t('Veuillez saisir une URL avant de tester la connexion.') . '</div>',
      ];
    }
    else {
      $test_result = $this->comparisonService->testConnection($remote_url);

      $message_type = $test_result['success'] ? 'status' : 'error';
      $form['connection']['test_result']['message'] = [
        '#type' => 'item',
        '#markup' => '<div class="messages messages--' . $message_type . '">' . $test_result['message'] . '</div>',
      ];

      if ($test_result['success'] && isset($test_result['remote_info'])) {
        $info = $test_result['remote_info'];
        $form['connection']['test_result']['details'] = [
          '#type' => 'item',
          '#markup' => '<div class="description">' .
          $this->t('Site distant : @site<br>Dernière mise à jour : @time', [
            '@site' => $info['site_name'],
            '@time' => date('d/m/Y H:i:s', strtotime($info['timestamp'])),
          ]) . '</div>',
        ];
      }
    }

    return $form['connection']['test_result'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $remote_url = $form_state->getValue('remote_url');

    // Valider le format de l'URL.
    if (!filter_var($remote_url, FILTER_VALIDATE_URL)) {
      $form_state->setErrorByName('remote_url', $this->t("L'URL saisie n\'est pas valide."));
    }

    // Vérifier que l'URL commence par http ou https.
    if (!preg_match('/^https?:\/\//', $remote_url)) {
      $form_state->setErrorByName('remote_url', $this->t("L'URL doit commencer par http:// ou https://"));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vactory_diff_config_client.settings');

    $config->set('remote_url', $form_state->getValue('remote_url'))
      ->set('connection_timeout', $form_state->getValue('connection_timeout'))
      ->save();

    $this->messenger()->addStatus($this->t('La configuration a été sauvegardée.'));

    parent::submitForm($form, $form_state);
  }

}
