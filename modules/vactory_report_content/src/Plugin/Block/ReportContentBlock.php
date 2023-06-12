<?php

namespace Drupal\vactory_report_content\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Report content block.
 *
 * @Block(
 *   id = "vactory_report_content",
 *   admin_label = @Translation("Report Content Block"),
 *   category = @Translation("Vactory")
 * )
 */
class ReportContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentPathStack $currentPath,
    AliasManagerInterface $aliasManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPath = $currentPath;
    $this->aliasManager = $aliasManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current'),
      $container->get('path_alias.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_user = \Drupal::currentUser();
    $current_path = $this->currentPath->getPath();
    $is_admin = in_array('admin', $current_user->getRoles()) || $current_user->id() === 1;
    $current_path_alias = $this->aliasManager->getAliasByPath($current_path);
    $content['is_reporter_user'] = TRUE;
    $content['current_path_alias'] = $current_path_alias;
    $block = [
      '#theme' => 'block_vactory_report_content',
      '#cache' => [
        // Set the caching policy to match the default block caching policy.
        'max-age' => 0,
        'contexts' => ['url'],
        'tags' => ['rendered'],
      ],
    ];

    if (!$current_user->hasPermission('report published content') && !$is_admin) {
      $content['is_reporter_user'] = FALSE;
    }

    if ($content['is_reporter_user']) {
      $block['#attached']['library'][] = 'vactory_report_content/script';
    }

    $block['#content'] = $content;

    return $block;
  }

}
