<?php

namespace Drupal\vactory_content_package\Services;

/**
 * Content package manager interface.
 */
interface ContentPackageArchiverManagerInterface {

  const BATCH_SIZE = 10;

  /**
   * Zip nodes.
   */
  public function zipContentTypeNodes(string $contentType = 'vactory_page');

  /**
   * Unzip a file.
   */
  public function unzipFile(string $path);

}
