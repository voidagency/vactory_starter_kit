<?php

namespace Drupal\vactory_zendesk\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Zendesk Chat Block" block.
 *
 * @Block(
 *   id = "vactory_zendesk_block",
 *   admin_label = @Translation("Zendesk"),
 *   category = @Translation("Vactory")
 * )
 */
class ZendeskBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   *
   * This method sets the block default configuration. This configuration
   * determines the block's behavior when a block is initially placed in a
   * region. Default values for the block configuration form should be added to
   * the configuration array. System default configurations are assembled in
   * BlockBase::__construct() e.g. cache setting and block title visibility.
   *
   * @see \Drupal\block\BlockBase::__construct()
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'api_key' => "eed172f4-2a5d-4850-ab62-753d58b90cd6",
    ];
  }

  /**
   * {@inheritdoc}
   *
   * This method defines form elements for custom block configuration. Standard
   * block configuration fields are added by BlockBase::buildConfigurationForm()
   * (block title and title visibility) and BlockFormController::form() (block
   * visibility settings).
   *
   * @see \Drupal\block\BlockBase::buildConfigurationForm()
   * @see \Drupal\block\BlockFormController::form()
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['api_key_field'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Zendesk API Key'),
      '#description'   => $this->t('Put the key of zendesk chat.'),
      '#default_value' => $this->configuration['api_key'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This method processes the blockForm() form fields when the block
   * configuration form is submitted.
   *
   * The blockValidate() method can be used to validate the form submission.
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['api_key']
      = $form_state->getValue('api_key_field');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      "#theme"   => "block_zendesk",
      '#api_key' => $this->configuration['api_key'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

}
