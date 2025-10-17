<?php

namespace Drupal\vactory_diff_content;

/**
 * Centralized constants for Content Diff.
 */
final class ContentDiffConst {

  public const FILE_PATH = 'private://content-diff';
  public const FILE_NAME = 'report.csv';

  public const STATUS = [
    'added' => [
      'label' => 'Added',
      'class' => 'content-diff-added',
      'description' => 'Entity exists locally but not found on remote instance (no matching UUID)',
    ],
    'deleted' => [
      'label' => 'Deleted',
      'class' => 'content-diff-deleted',
      'description' => 'Entity exists on remote instance but not found locally (no matching UUID)',
    ],
    'modified' => [
      'label' => 'Modified',
      'class' => 'content-diff-modified',
      'description' => 'Entity exists on both instances but has different modification timestamps',
    ],
    'synchronized' => [
      'label' => 'Synchronized',
      'class' => 'content-diff-synchronized',
      'description' => 'Entity exists on both instances with identical modification timestamps',
    ],
  ];

}
