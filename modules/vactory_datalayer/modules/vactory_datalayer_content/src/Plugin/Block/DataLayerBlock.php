<?php

namespace Drupal\vactory_datalayer_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'DataLayer' block.
 *
 * @Block(
 *   id = "vactory_dataLayer_content_block",
 *   admin_label = @Translation("Vactory DataLayer Content Block."),
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
    $props = $token->replace($config['datalayer_properties'], [], $token_options);
    $props = trim(preg_replace('/\s\s+/', '', $props));
    $props = json_decode($props, TRUE);
    return [
      "#cache"    => ["max-age" => 0],
      '#attached' => [
        'drupalSettings' => [
          'properties_content' => isset($props) ? $props : NULL,
        ],
        'library'        => [
          'vactory_datalayer_content/vactory_datalayer_content.script',
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
    $form['datalayer_properties'] = [
      '#type'          => 'textarea',
      '#title'         => t('DataLayer Content properties'),
      '#default_value' => isset($config['datalayer_properties']) ? $config['datalayer_properties'] : '',
    ];

    $form['token_tree'] = $this->getTokenTree();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('datalayer_properties', $form_state->getValue('datalayer_properties'));
  }

  /**
   * Function providing the site token tree link.
   */
  public function getTokenTree() {
    $token_tree = [
      '#theme'           => 'token_tree_link',
      '#show_restricted' => TRUE,
      '#weight'          => 92,
    ];
    return [
      '#type'   => 'markup',
      '#markup' => \Drupal::service('renderer')->render($token_tree),
    ];
  }

}
