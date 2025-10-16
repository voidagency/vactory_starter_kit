<?php

namespace Drupal\vactory_diff_content;

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

  const SUPPORTED_ENTITY_TYPES = [
    'node',
    'taxonomy_term',
  ];

}
