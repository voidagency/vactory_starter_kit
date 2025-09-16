<?php

namespace Drupal\vactory_content_diff;

/**
 * Centralized status constants for Content Diff.
 */
final class ContentDiffStatus {

  public const NEW_ENTITY = [
    'label' => 'New entity',
    'key' => 'new',
    'class' => 'content-diff-new',
  ];

  public const MODIFIED = [
    'label' => 'Modified',
    'key' => 'modified',
    'class' => 'content-diff-modified',
  ];

  public const SYNCHRONIZED = [
    'label' => 'Synchronized',
    'key' => 'synchronized',
    'class' => 'content-diff-synchronized',
  ];

}
