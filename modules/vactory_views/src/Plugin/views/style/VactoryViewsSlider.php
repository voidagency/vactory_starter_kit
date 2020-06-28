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
 *   id = "vactory_views_slider",
 *   title = @Translation("Slider"),
 *   help = @Translation("Displays rows as a slider using default variant."),
 *   theme = "vactory_views_slider",
 *   display_types = {"normal"}
 * )
 */
class VactoryViewsSlider extends StylePluginBase {

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

    $settings = _vactory_views_slider_default_settings();
    foreach ($settings as $k => $v) {
      $options[$k] = ['default' => $v];
    }
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['#tree'] = TRUE;
    // slidesToShow.
    $form['slidesToShow'] = [
      '#type' => 'number',
      '#title' => $this->t('Slides to show'),
      '#description' => $this->t('# of slides to show.'),
      '#default_value' => $this->options['slidesToShow'],
    ];

    // slidesToScroll.
    $form['slidesToScroll'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slides to scroll'),
      '#description' => $this->t('# of slides to scroll.'),
      '#default_value' => $this->options['slidesToScroll'],
    ];

    // Speed.
    $form['speed'] = [
      '#type' => 'number',
      '#title' => $this->t('Slide Speed'),
      '#default_value' => $this->options['speed'],
      '#description' => $this->t('Slide/Fade animation speed.'),
    ];

    // Infinite.
    $form['infinite'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Infinite'),
      '#default_value' => $this->options['infinite'],
    ];

    // Dots.
    $form['dots'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dots'),
      '#default_value' => $this->options['dots'],
    ];

    $form['arrows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Arrows'),
      '#default_value' => $this->options['arrows'],
    ];

    $form['centerMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('center Mode'),
      '#default_value' => $this->options['centerMode'],
    ];

    // cssEase.
    $form['cssEase'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Css ease'),
      '#description' => $this->t('CSS3 Animation Easing (use <b>ease for example</b>). @see <a href="http://easings.net/" _target="blank">easings.net</a> for more.'),
      '#default_value' => $this->options['cssEase'],
    ];

    // Tablette Configuration.
    $form['responsive'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $form['responsive']['settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $form['responsive']['settings']['slidesToShow'] = [
      '#type' => 'number',
      '#title' => $this->t('Slides to show on tablette'),
      '#description' => $this->t('# of slides to show on Tablette.'),
      '#default_value' => $this->options['responsive']['settings']['slidesToShow'],
    ];

    $form['responsive']['settings']['slidesToScroll'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slides to scroll on Tablette'),
      '#description' => $this->t('# of slides to scroll on Tablette.'),
      '#default_value' => $this->options['responsive']['settings']['slidesToScroll'],
    ];

    $form['responsive']['settings']['dots'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dots on Tablette'),
      '#default_value' => $this->options['responsive']['settings']['dots'],
    ];

    $form['responsive']['settings']['arrows'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Arrows on Tablette'),
      '#default_value' => $this->options['responsive']['settings']['arrows'],
    ];

    $form['responsive']['settings']['centerMode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('centerMode on Tablette'),
      '#default_value' => $this->options['responsive']['settings']['centerMode'],
    ];

  }

}
