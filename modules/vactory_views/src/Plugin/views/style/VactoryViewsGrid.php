<?php

namespace Drupal\vactory_views\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin to render each item in a grid cell.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "vactory_views_grid",
 *   title = @Translation("Columns"),
 *   help = @Translation("Displays rows in a grid."),
 *   theme = "vactory_views_grid",
 *   display_types = {"normal"}
 * )
 */
class VactoryViewsGrid extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['wrapper_class_custom'] = ['default' => ''];
    $options['row_class_custom'] = ['default' => ''];
    $options['row_class_default'] = ['default' => TRUE];
    $options['xs'] = ['default' => 'col-xs-12'];
    $options['sm'] = ['default' => 'col-sm-12'];
    $options['md'] = ['default' => 'col-md-12'];
    $options['lg'] = ['default' => 'col-lg-12'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Remove unused fields.
    unset($form['columns']);
    unset($form['automatic_width']);
    unset($form['alignment']);
    unset($form['col_class_default']);
    unset($form['col_class_custom']);

    // Bootstrap Grid Demo.
    $form['bootstrap_grid'] = [
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'vactory_views') . '/img/bootstrap-grid.png',
      '#alt' => $this->t('Bootstrap Grid'),
      '#width' => 1000,
      '#height' => 301,
      '#attributes' => ['class' => 'img-responsive'],
    ];

    // Options.
    $grid_options = [
      'xs' => [],
      'sm' => [],
      'md' => [],
      'lg' => [],
    ];

    $grid = [1, 2, 3, 4, 6, 12];
    foreach ($grid as $i) {
      $grid_options['xs']['col-xs-' . $i] = $this->formatPlural($i, '1 column', '@count columns');
      $grid_options['sm']['col-sm-' . $i] = $this->formatPlural($i, '1 column', '@count columns');
      $grid_options['md']['col-md-' . $i] = $this->formatPlural($i, '1 column', '@count columns');
      $grid_options['lg']['col-lg-' . $i] = $this->formatPlural($i, '1 column', '@count columns');
    }

    // xs.
    $form['xs'] = [
      '#type' => 'select',
      '#options' => $grid_options['xs'],
      '#title' => $this->t('Extra small devices'),
      '#default_value' => $this->options['xs'],
      '#description' => $this->t('Phones (<768px)'),
      '#required' => TRUE,
    ];

    // sm.
    $form['sm'] = [
      '#type' => 'select',
      '#options' => $grid_options['sm'],
      '#title' => $this->t('Small devices Tablets'),
      '#default_value' => $this->options['sm'],
      '#description' => $this->t('(≥768px)'),
      '#required' => TRUE,
    ];

    // md.
    $form['md'] = [
      '#type' => 'select',
      '#options' => $grid_options['md'],
      '#title' => $this->t('Medium devices Desktops'),
      '#default_value' => $this->options['md'],
      '#description' => $this->t('(≥992px)'),
      '#required' => TRUE,
    ];

    // lg.
    $form['lg'] = [
      '#type' => 'select',
      '#options' => $grid_options['lg'],
      '#title' => $this->t('Large devices Desktops'),
      '#default_value' => $this->options['lg'],
      '#description' => $this->t('(≥1200px)'),
      '#required' => TRUE,
    ];

    $form['wrapper_class_custom'] = [
      '#title' => $this->t('Wrapper Custom class'),
      '#description' => $this->t('Additional classes to provide on the wrapper (e.g <b>eq-height</b> to use equal height layout). Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['wrapper_class_custom'],
    ];

    $form['row_class_default'] = [
      '#title' => $this->t('Default row classes'),
      '#description' => $this->t('Adds the default views row classes like views-row, row-1 and clearfix to the output. You can use this to quickly reduce the amount of markup the view provides by default, at the cost of making it more difficult to apply CSS.'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['row_class_default'],
    ];
    $form['row_class_custom'] = [
      '#title' => $this->t('Custom row class'),
      '#description' => $this->t('Additional classes to provide on each row. Separated by a space.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['row_class_custom'],
    ];
    if ($this->usesFields()) {
      $form['row_class_custom']['#description'] .= ' ' . $this->t('You may use field tokens from as per the "Replacement patterns" used in "Rewrite the output of this field" for all fields.');
    }
  }

  /**
   * Return the token-replaced row or column classes for the specified result.
   *
   * @param int $result_index
   *   The delta of the result item to get custom classes for.
   * @param string $type
   *   The type of custom grid class to return, either "row" or "col".
   *
   * @return string
   *   A space-delimited string of classes.
   */
  public function getCustomClass($result_index, $type) {
    $class = $this->options[$type . '_class_custom'];
    if ($this->usesFields() && $this->view->field) {
      $class = strip_tags($this->tokenizeValue($class, $result_index));
    }

    $classes = explode(' ', $class);
    foreach ($classes as &$class) {
      $class = Html::cleanCssIdentifier($class);
    }
    return implode(' ', $classes);
  }

}
