<?php

namespace Drupal\vactory_push_notifications\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Push Notifications Block" block.
 *
 * @Block(
 *   id = "vactory_push_notifications_block",
 *   admin_label = @Translation("Push Notifications"),
 *   category = @Translation("Vactory")
 * )
 */
class PushNotificationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $current_date = new \DateTime();
    $current_date = $current_date->format('Y-m-d');
    return [
      'label_display' => FALSE,
      'block_template' => [],
      'delays' => 0,
      'how_many' => 0,
      'begin_date' => $current_date,
      'end_date' => $current_date,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    parent::blockForm($form, $form_state);
    $form['delays'] = [
      '#type'          => 'number',
      '#title'         => $this->t("Durée d'affichage"),
      '#default_value' => $this->configuration['delays'],
      '#description'   => $this->t("Durée d'affichage en secondes.<b><i> S'elle n'est pas renseignée, le bloc sera toujours affiché.</i></b>"),
      '#min'           => 0,
    ];
    $form['how_many'] = [
      '#type'          => 'number',
      '#title'         => $this->t("Nombre d'affichage"),
      '#default_value' => $this->configuration['how_many'],
      '#description'   => $this->t("Nombre de fois à afficher.<b><i> S'il n'est pas renseigné, le bloc sera toujours affiché.</i></b>"),
      '#min'           => 0,
    ];
    $form['begin_date'] = [
      '#type'          => 'date',
      '#title'         => $this->t("Date de début"),
      '#default_value' => $this->configuration['begin_date'],
      '#description'   => $this->t("Date de début d'affichage du notificationscreen."),
      '#required'      => TRUE,
    ];
    $form['end_date'] = [
      '#type'          => 'date',
      '#title'         => $this->t("Date de fin"),
      '#default_value' => $this->configuration['end_date'],
      '#description'   => $this->t("Date d'arrêt d'affichage du notificationscreen."),
      '#required'      => TRUE,
    ];

    $options = [];
    $template_blocks = \Drupal::service('entity_type.manager')->getStorage('block_content')
      ->loadByProperties(['type' => 'vactory_block_component']);

    if (!empty($template_blocks)) {
      $template_blocks = array_map(function ($el) {
        return $el->label();
      }, $template_blocks);
      $options = $template_blocks;
    }
    $form['block_template'] = [
      '#type' => 'select',
      '#title' => $this->t('Sélectionner le block template souhaité'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select a block template -'),
      '#default_value' => $this->configuration['block_template'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    if (((int) $form_state->getValue('delays')) < 0) {
      $form_state->setErrorByName('delays', $this->t('Only digits are allowed'));
    }

    if (((int) $form_state->getValue('how_many')) < 0) {
      $form_state->setErrorByName('how_many', $this->t('Only digits are allowed'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    // Update form settings.
    $this->configuration['block_template'] = $form_state->getValue('block_template');
    $this->configuration['end_date'] = $form_state->getValue('end_date');
    $this->configuration['begin_date'] = $form_state->getValue('begin_date');
    $this->configuration['delays'] = (int) $form_state->getValue('delays');
    $this->configuration['how_many'] = (int) $form_state->getValue('how_many');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $delays = (int) $this->configuration['delays'];
    $how_many = (int) $this->configuration['how_many'];

    $has_https = isset($_SERVER["HTTPS"]) ? TRUE : FALSE;
    $notification_cookie_how_many = (int) \Drupal::request()->cookies->get('Drupal_visitor_notification');
    $cookie_name = 'Drupal.visitor.notification';

    // Get selected block translation.
    $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $block_id = $this->configuration['block_template'];
    $block_template = \Drupal::service('entity_type.manager')->getStorage('block_content')
      ->load($block_id);
    $content = [
      '#markup' => '<p>' . $this->t("Block template not found... :(") . '</p>',
    ];
    if ($block_template) {
      $translated_block_template = \Drupal::service('entity.repository')
        ->getTranslationFromContext($block_template, $langcode);
      $content = \Drupal::service('entity_type.manager')->getViewBuilder('block_content')->view($translated_block_template);
    }

    // We reached the limit.
    // No delay loader.
    if ($notification_cookie_how_many > $how_many) {
      $delays = 0;
    }

    $build = [
      '#cache'    => [
        'max-age'  => 0,
        'contexts' => [
          'url.path',
          'cookies:' . $cookie_name,
        ],
      ],
      '#theme'    => 'block_push_notifications',
      '#content'  => $content,
      '#attached' => [
        'drupalSettings' => [
          'pushNotification' => [
            'delays'   => $delays,
            'how_many' => $how_many,
            'cookie'   => [
              'name'      => $cookie_name,
              'has_https' => $has_https,
            ],
          ],
        ],
      ],
    ];

    // Save for 365 days.

    $cookie_value = $how_many == 0 ? 0 : ($notification_cookie_how_many > 0 ? $notification_cookie_how_many + 1 : 1);
    $request_time = \Drupal::time()->getRequestTime() + 31536000;
    setrawcookie($cookie_name, $cookie_value, $request_time, '/', '', $has_https, FALSE);

    return $build;
  }

}
