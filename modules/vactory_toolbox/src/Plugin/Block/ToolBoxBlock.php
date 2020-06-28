<?php

namespace Drupal\vactory_toolbox\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a "Toolbox Block" block.
 *
 * @Block(
 *   id = "vactory_toolbox_block",
 *   admin_label = @Translation("ToolBox"),
 *   category = @Translation("Vactory")
 * )
 */
class ToolBoxBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
      'content'       => [
        'value'  => '<div class="btn-group-vertical"> <button type="button" class="btn btn-secondary">Button</button> <button type="button" class="btn btn-secondary">Button</button> <button type="button" class="btn btn-secondary">Button</button> <button type="button" class="btn btn-secondary">Button</button> </div>',
        'format' => 'full_html',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['content'] = [
      '#type'          => 'text_format',
      '#title'         => 'Contenu',
      '#format'        => 'full_html',
      '#default_value' => $this->configuration['content']['value'],
      '#description'   => $this->t('Toolbox content.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['content'] = $form_state->getValue('content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $cachabelmetadata = new CacheableMetadata();
    $cachabelmetadata->addCacheContexts(['url.path']);

    $body_content = $this->configuration['content']['value'];

    $build = [
      "#theme"   => "block_toolbox",
      '#content' => [
        'body' => $body_content,
      ],
    ];
    $cachabelmetadata->applyTo($build);

    return $build;
  }

}
