<?php

namespace Drupal\vactory_wisepops\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'WisePops' block.
 *
 * @Block(
 *   id = "vactory_wisepops_block",
 *   admin_label = @Translation("Vactory Wisepops Block."),
 * )
 */
class WisePopsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $token = \Drupal::token();
    $token_options = ['clear' => TRUE];
    $props = $token->replace($config['wisepops_properties'], [], $token_options);
    $props = trim(preg_replace('/\s\s+/', '', $props));
    $props = json_decode($props);
    return [
      "#cache" => ["max-age" => 0],
      '#attached' => [
        'drupalSettings' => [
          'key' => isset($config['wisepops_id']) ? $config['wisepops_id'] : '',
          'properties' => isset($props) ? $props : NULL,
        ],
        'library' => [
          'vactory_wisepops/vactory_wisepops.script',
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
    $form['wisepops_id'] = [
      '#type' => 'textfield',
      '#title' => t('Wisepops ID'),
      '#default_value' => isset($config['wisepops_id']) ? $config['wisepops_id'] : '',
      '#required' => TRUE,
    ];

    $form['wisepops_properties'] = [
      '#type' => 'textarea',
      '#title' => t('Wisepops Properties'),
      '#default_value' => isset($config['wisepops_properties']) ? $config['wisepops_properties'] : '',
    ];

    $form['token_tree'] = $this->getTokenTree();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    // Save our custom settings when the form is submitted.
    $this->setConfigurationValue('wisepops_id', $form_state->getValue('wisepops_id'));
    $this->setConfigurationValue('wisepops_properties', $form_state->getValue('wisepops_properties'));
  }

  /**
   * Function providing the site token tree link.
   */
  public function getTokenTree() {
    $token_tree = [
      '#theme' => 'token_tree_link',
      '#show_restricted' => TRUE,
      '#weight' => 90,
    ];
    return [
      '#type' => 'markup',
      '#markup' => \Drupal::service('renderer')->renderPlain($token_tree),
    ];
  }

}
