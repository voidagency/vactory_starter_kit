<?php

namespace Drupal\vactory_datalayer_authentication\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'DataLayer' block.
 *
 * @Block(
 *   id = "vactory_datalayer_authentication_block",
 *   admin_label = @Translation("Vactory DataLayer Authentication Block."),
 * )
 */
class DataLayerBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $token = \Drupal::token();
    $token_options = ['clear' => TRUE];
    $current_user = \Drupal::currentUser();
    $props = $current_user->isAuthenticated() ? $token->replace($config['datalayer_properties_connected'], [], $token_options) : $token->replace($config['datalayer_properties_no_connected'], [], $token_options);
    $props = trim(preg_replace('/\s\s+/', '', $props));
    $props = json_decode($props, TRUE);
    return [
      "#cache" => ["max-age" => 0],
      '#attached' => [
        'drupalSettings' => [
          'properties' => isset($props) ? $props : NULL,
        ],
        'library' => [
          'vactory_datalayer_authentication/vactory_datalayer_authentication.script',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    // Add a form field to the existing block configuration form.
    $form['datalayer_properties_connected'] = [
      '#type' => 'textarea',
      '#title' => t('DataLayer authentication properties for connected users'),
      '#default_value' => isset($config['datalayer_properties_connected']) ? $config['datalayer_properties_connected'] : '',
    ];

    $form['datalayer_properties_no_connected'] = [
      '#type' => 'textarea',
      '#title' => t('DataLayer authentication properties for no connected users'),
      '#default_value' => isset($config['datalayer_properties_no_connected']) ? $config['datalayer_properties_no_connected'] : '',
    ];

    $form['token_tree'] = $this->getTokenTree();
    $form['#cache']['tags'][] = 'config:vactory_datalayer_authentication.settings';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('datalayer_properties_connected', $form_state->getValue('datalayer_properties_connected'));
    $this->setConfigurationValue('datalayer_properties_no_connected', $form_state->getValue('datalayer_properties_no_connected'));
  }

  /**
   * Function providing the site token tree link.
   */
  public function getTokenTree() {
    $token_tree = [
      '#theme' => 'token_tree_link',
      '#show_restricted' => TRUE,
      '#weight' => 93,
    ];
    return [
      '#type' => 'markup',
      '#markup' => \Drupal::service('renderer')->render($token_tree),
    ];
  }

}
