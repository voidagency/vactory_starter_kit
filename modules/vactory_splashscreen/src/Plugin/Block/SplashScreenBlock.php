<?php

namespace Drupal\vactory_splashscreen\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Splash Screen Block" block.
 *
 * @Block(
 *   id = "vactory_splash_screen_block",
 *   admin_label = @Translation("Splash Screen"),
 *   category = @Translation("Vactory")
 * )
 */
class SplashScreenBlock extends BlockBase {

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
      '#title'         => $this->t('Durée'),
      '#default_value' => $this->configuration['delays'],
      '#description'   => $this->t('Durée en secondes. <b><i>Ce paramètre sera ignoré pour les utilisateurs connecté.</i></b>'),
      '#min'           => 0,
    ];
    $form['how_many'] = [
      '#type'          => 'number',
      '#title'         => $this->t("Nombre d'affichage"),
      '#default_value' => $this->configuration['how_many'],
      '#description'   => $this->t("Nombre de fois à afficher.  <b><i>Ce paramètre sera ignoré pour les utilisateurs connecté.</i></b>"),
      '#min'           => 0,
    ];
    $form['begin_date'] = [
      '#type'          => 'date',
      '#title'         => $this->t("Date de début"),
      '#default_value' => $this->configuration['begin_date'],
      '#description'   => $this->t("Date de début d'affichage du splashscreen."),
      '#required'      => TRUE,
    ];
    $form['end_date'] = [
      '#type'          => 'date',
      '#title'         => $this->t("Date de fin"),
      '#default_value' => $this->configuration['end_date'],
      '#description'   => $this->t("Date d'arrêt d'affichage du splashscreen."),
      '#required'      => TRUE,
    ];

    $options = [];
    $template_blocks = \Drupal::service('entity_type.manager')->getStorage('block_content')
      ->loadByProperties(['type' => 'vactory_block_component']);
    if (!empty($template_blocks)) {
      $template_blocks = array_map(function ($el) {
        return $el->get('block_machine_name')->value;
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
    $splash_cookie_how_many = (int) \Drupal::request()->cookies->get('Drupal_visitor_splash');
    $cookie_name = 'Drupal.visitor.splash';

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
    if ($splash_cookie_how_many > $how_many) {
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
      '#theme'    => 'block_splashscreen',
      '#content'  => $content,
      '#attached' => [
        'drupalSettings' => [
          'splashScreen' => [
            'delays'   => $delays,
            'how_many' => $how_many,
            'user'     => [
              'is_anonymous' => \Drupal::currentUser()->isAnonymous(),
            ],
            'cookie'   => [
              'name'      => $cookie_name,
              'has_https' => $has_https,
            ],
          ],
        ],
      ],
    ];

    // Save for 365 days.
    $cookie_value = $splash_cookie_how_many > 0 ? $splash_cookie_how_many + 1 : 1;
    $request_time = \Drupal::time()->getRequestTime() + 31536000;
    setrawcookie($cookie_name, $cookie_value, $request_time, '/', '', $has_https, FALSE);

    return $build;
  }

}
