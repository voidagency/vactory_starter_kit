<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

/**
 * Prepares variables for Views Slider templates.
 *
 * Default template: vactory-views-slider.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A View object.
 */
function template_preprocess_vactory_views_slider(array &$variables) {
  $handler = $variables['view']->style_plugin;
  $settings = _vactory_views_slider_format_settings($handler->options);

  $variables['attributes']['data-settings'] = json_encode($settings);
  $variables['attributes']['class'][] = 'vactory-slider';
  // $variables['attributes']['class'][] = 'slick-hide-until-loaded';
  // $variables['attributes']['class'][] = 'slick-center-mode';.
  $variables['attributes']['class'][] = 'slick-use-equal-height';

  $attached = [
    '#attached' => ['library' => ['vactory_views/slider']],
  ];

  render($attached);
  template_preprocess_views_view_unformatted($variables);
}

/**
 * Prepares variables for views grid style templates.
 *
 * Default template: views-view-grid.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: The view object.
 *   - rows: An array of row items. Each row is an array of content.
 */
function template_preprocess_vactory_views_grid(array &$variables) {
  $options = $variables['options'] = $variables['view']->style_plugin->options;

  template_preprocess_views_view_unformatted($variables);

  // Iterate over each rendered views result row.
  foreach ($variables['rows'] as $result_index => &$item) {
    $item['attributes']['class'] = [
      $options['xs'],
      $options['sm'],
      $options['md'],
      $options['lg'],
      $options['row_class_custom'],
    ];
  }
}

/**
 * Prepares variables for Views Masonry templates.
 *
 * Default template: vactory-views-masonry.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - view: A View object.
 */
function template_preprocess_vactory_views_masonry(array &$variables) {
  $handler = $variables['view']->style_plugin;
  $settings = _vactory_views_masonry_format_settings($handler->options);

  $variables['attributes']['data-settings'] = json_encode($settings);
  $variables['attributes']['class'][] = 'vactory-masonry';
  $variables['attributes']['class'][] = 'row';

  $attached = [
    '#attached' => ['library' => ['vactory_views/masonry']],
  ];

  render($attached);
  template_preprocess_views_view_unformatted($variables);
}
