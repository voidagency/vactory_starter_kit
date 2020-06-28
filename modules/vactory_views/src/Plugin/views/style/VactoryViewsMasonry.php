<?php

namespace Drupal\vactory_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item into owl carousel.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "vactory_views_masonry",
 *   title = @Translation("Masonry"),
 *   help = @Translation("Displays rows as a masonry layout."),
 *   theme = "vactory_views_masonry",
 *   display_types = {"normal"}
 * )
 */
class VactoryViewsMasonry extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;

  /**
   * Set default options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $settings = _vactory_views_masonry_default_settings();
    foreach ($settings as $k => $v) {
      $options[$k] = ['default' => $v];
    }
    return $options;
  }

  /**
   * Render the given style.
   *
   * @param mixed $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // transitionDuration.
    $form['transitionDuration'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('transitionDuration'),
      '#description'   => $this->t('Duration of the transition when items change position or appearance, set in a CSS time format.'),
      '#default_value' => $this->options['transitionDuration'],
    ];

    // stagger.
    $form['stagger'] = [
      '#type'          => 'number',
      '#title'         => $this->t('Stagger'),
      '#description'   => $this->t('Staggers item transitions, so items transition incrementally after one another. number in milliseconds'),
      '#default_value' => $this->options['stagger'],
    ];

    // resize.
    $form['resize'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Resize'),
      '#description'   => $this->t('Adjusts sizes and positions when window is resized.'),
      '#default_value' => $this->options['resize'],
    ];

    // horizontalOrder.
    $form['horizontalOrder'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('horizontalOrder'),
      '#description'   => $this->t('Lays out items to (mostly) maintain horizontal left-to-right order.'),
      '#default_value' => $this->options['horizontalOrder'],
    ];

    // originTop.
    $form['originTop'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('originTop'),
      '#description'   => $this->t('Controls the vertical flow of the layout. By default, item elements start positioning at the top, with originTop: true. Set originTop: false for bottom-up layouts. It’s like Tetris!'),
      '#default_value' => $this->options['originTop'],
    ];
  }

}
