<?php

namespace Drupal\vactory_splashscreen\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;

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
    return [
      'label_display' => FALSE,
      'content'       => [],
      'delays'        => 0,
      'how_many'      => 0,
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
    // Get existing content from block configuration.
    $content = $this->configuration['content'];
    // Get Paragraph field widget and inject it as block form content field.
    $node = \Drupal::service('entity_type.manager')
      ->getStorage('node')
      ->create(['type' => 'vactory_page']);
    $items = $node->get('field_vactory_paragraphs');
    foreach ($content as $paragraph_id) {
      $p = Paragraph::load((int) $paragraph_id);
      $items->appendItem($p);
    }
    $entity_form_display = \Drupal::service('entity_type.manager')
      ->getStorage('entity_form_display')
      ->load('node.vactory_page.default');
    $form_display_settings = $entity_form_display->getComponent('field_vactory_paragraphs');
    $form_display_settings['settings']['edit_mode'] = 'opened';
    $entity_form_display->setComponent('field_vactory_paragraphs', $form_display_settings);
    $widget = $entity_form_display->getRenderer('field_vactory_paragraphs');
    $form['#parents'][] = 'splash_settings';
    $form['content'] = $widget->form($items, $form, $form_state);
    $form['content']['#type'] = 'fieldset';
    $form['content']['#title'] = t('Content');
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
    // Get submitted values for paragraph field.
    $paragraphs = $form_state->getValues()['field_vactory_paragraphs'];
    $content = [];
    foreach ($paragraphs as $key => $paragraph) {
      if (!is_numeric($key)) {
        continue;
      }
      if (isset($paragraph['subform'])) {
        $paragraph_id = isset($this->configuration['content'][$key]) ? $this->configuration['content'][$key] : '';
        if (!empty($paragraph_id)) {
          // For an existing paragraph case just update paragraph values.
          $content[] = $this->configuration['content'][$key];
          $p = Paragraph::load($paragraph_id);
          foreach ($paragraph['subform'] as $field_name => $field_value) {
            $p->set($field_name, $field_value);
          }
          $p->save();
        }
        else {
          // For a new paragraph case create a paragraph from submitted values.
          $info = ['type' => 'vactory_component'];
          $info += $paragraph['subform'];
          $p = Paragraph::create($info);
          $p->save();
          $content[] = $p->id();
        }
      }
      else {
        // Deleted paragraphs case.
        $paragraph_id = $this->configuration['content'][$key];
        if (!empty($paragraph_id)) {
          $paragraph = Paragraph::load($paragraph_id);
          $paragraph->delete();
        }
      }
    }
    // Update form settings.
    $this->configuration['content'] = $content;
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
      '#content'  => [
        'content' => $this->configuration['content'],
      ],
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
