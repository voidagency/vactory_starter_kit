<?php

namespace Drupal\vactory_welcome\plugin\Block;

use Drupal\Core\Block\BlockBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EspacePriveBlock.
 *
 * @package Drupal\vactory_welcome\plugin\Block
 * @Block(
 *   id = "vactory_welcome_message",
 *   admin_label = @Translation("Vactory Welcome Message Block"),
 *   category = @Translation("Vactory")
 * )
 */
class WelcomeUserBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();
    $form['welcome_description'] = [
      '#type' => 'text_format',
      '#title' => 'Enter a welcome description',
      '#description' => $this->t('Enter a message, a quote or anything you want to tell to the user about'),
      '#default_value' => isset($config['welcome_description']) ? $config['welcome_description']["value"] : '',
    ];
    $form['welcome_description']['tree_token'] = get_token_tree();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['welcome_description'] = $values['welcome_description'];
  }

  /**
   * Welcome message block build().
   */

  public function build() {
   $config = $this->getConfiguration();
   $welcome_value = (isset($config['welcome_description']) && !empty($config['welcome_description'])) ? $config['welcome_description'] : "";
   $welcome_value = \Drupal::token()->replace($welcome_value["value"]);
   return [
      '#theme' => 'welcome_user',
      "#content" => [
        '#value' => $welcome_value,
      ],
    ];
    throw new NotFoundHttpException();
  }


}
