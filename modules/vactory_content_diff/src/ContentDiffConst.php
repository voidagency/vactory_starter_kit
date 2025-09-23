<?php

namespace Drupal\vactory_content_diff;

/**
 * Centralized constants for Content Diff.
 */
final class ContentDiffConst {

  public const FILE_PATH = 'private://content-diff';
  public const FILE_NAME = 'report.csv';

  public const STATUS = [
    'new' => [
      'label' => 'New entity',
      'class' => 'content-diff-new',
    ],
    'modified' => [
      'label' => 'Modified',
      'class' => 'content-diff-modified',
    ],
    'synchronized' => [
      'label' => 'Synchronized',
      'class' => 'content-diff-synchronized',
    ],
  ];

}
