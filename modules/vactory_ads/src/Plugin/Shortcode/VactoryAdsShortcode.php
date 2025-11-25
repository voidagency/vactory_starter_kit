<?php

namespace Drupal\vactory_ads\Plugin\Shortcode;

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Language\Language;
use Drupal\shortcode\Plugin\ShortcodeBase;

/**
 * Provides a shortcode for vactory ads.
 *
 * @Shortcode(
 *   id = "ads",
 *   title = @Translation("Vactory Ads"),
 *   description = @Translation("Ads widget shortcode")
 * )
 */
class VactoryAdsShortcode extends ShortcodeBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $attributes, $text, $langcode = Language::LANGCODE_NOT_SPECIFIED) {

    // Merge with default attributes.
    $attributes = $this->getAttributes([
      'id' => '',
      'view' => 'full',
    ],
      $attributes
    );

    if ((int) $attributes['id']) {
      $block_entity = BlockContent::load($attributes['id']);
      if ($block_entity) {
        $block_view = \Drupal::entityTypeManager()->getViewBuilder('block_content')->view($block_entity, $attributes['view']);
        if ($block_view) {
          return \Drupal::service('renderer')->render($block_view);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = [];
    $output[] = '<p><strong>' . $this->t('[block id="1" (view="full") /]') . '</strong>';
    $output[] = $this->t('Inserts a block.') . '</p>';
    if ($long) {
      $output[] = '<p>' . $this->t('The block display view can be specified using the <em>view</em> parameter.') . '</p>';
    }

    return implode(' ', $output);
  }

}
