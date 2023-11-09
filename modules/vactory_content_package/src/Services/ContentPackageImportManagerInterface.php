<?php

namespace Drupal\vactory_content_package\Services;

/**
 * Content package manager interface.
 */
interface ContentPackageImportManagerInterface {

  /**
   * Delete all nodes of given content types.
   */
  public function rollback(array $content_types, string $file_to_import = '');

  /**
   * Import nodes.
   */
  public function importNodes(string $file_to_import);

}
