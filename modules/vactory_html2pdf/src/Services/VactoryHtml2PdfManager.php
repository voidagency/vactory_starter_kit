<?php

namespace Drupal\vactory_html2pdf\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Utility\Token;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Vactory Html to PDF service.
 */
class VactoryHtml2PdfManager {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * State service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * State service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * MDF default config.
   *
   * @var array
   */
  protected $mpdfDefaultConfig;

  /**
   * MDF default font config.
   *
   * @var array
   */
  protected $mpdfDefaultFontConfig;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    ConfigFactoryInterface $configFactory,
    StreamWrapperManagerInterface $streamWrapperManager,
    Token $token,
    StateInterface $state,
    LoggerChannelFactoryInterface $logger
  ) {
    $this->configFactory = $configFactory;
    $this->streamWrapperManager = $streamWrapperManager;
    $this->token = $token;
    $this->state = $state;
    $this->logger = $logger;
    $this->mpdfDefaultConfig = (new ConfigVariables())->getDefaults();
    $this->mpdfDefaultFontConfig = (new FontVariables())->getDefaults();
  }

  /**
   * Returns the file system service.
   *
   * Not injected by the constructor: projects may extend this class.
   */
  protected function getFileSystem(): FileSystemInterface {
    return \Drupal::service('file_system');
  }

  /**
   * Html to pdf generator.
   *
   * @throws \Mpdf\MpdfException
   * @throws \Drupal\vactory_html2pdf\Services\VactoryHtml2PdfException
   */
  public function html2Pdf($htmlContent, string $outputFilename, array $mpdfOptions = []) {
    // Params check.
    $file_target = $this->streamWrapperManager->getTarget($outputFilename);
    $file_scheme = $this->streamWrapperManager->getScheme($outputFilename);
    if (empty($outputFilename) || empty($file_target) || !$file_target) {
      throw new VactoryHtml2PdfException(sprintf('Argument 2 of %s::html2Pdf method should not be empty and should be a valid file name.', static::class));
    }
    // Create file parents directories if not exist.
    $file_target = trim($file_target, '/');
    $file_target_pieces = explode('/', $file_target);
    $file_name = array_pop($file_target_pieces);
    $file_parents = implode('/', $file_target_pieces);
    if (empty($file_scheme)) {
      // Set default file scheme to public. Reached by a path without
      // scheme that starts or ends with a slash ("/dir/file.pdf"): core
      // gives it a target. Any other path without scheme is rejected above.
      $current_time = new \DateTime('now');
      $date = $current_time->format('Y-m');
      $base = 'public://html2pdf/' . $date . '/';
    }
    elseif (!$this->streamWrapperManager->isValidScheme($file_scheme)) {
      throw new VactoryHtml2PdfException(sprintf('The file scheme "%s" is not available on this site.', $file_scheme));
    }
    else {
      $base = $file_scheme . '://';
    }
    $dirname = $base . $file_parents;
    $file_system = $this->getFileSystem();
    if (!$file_system->prepareDirectory($dirname, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new VactoryHtml2PdfException(sprintf('The directory "%s" does not exist and could not be created, or is not writable.', $dirname));
    }

    // Get module settings object.
    $config = $this->configFactory->get('vactory_html2pdf.settings');
    // Get fonts from module settings.
    // "vactory_html2pdf_font_dirs" is only written by the settings form:
    // fall back on the install value of "font_directories".
    $custom_fonts_directories = $config->get('vactory_html2pdf_font_dirs')
      ?? $this->resolveFontDirectories((string) $config->get('font_directories'));
    $custom_fonts_data = $config->get('fonts_data') ?? [];
    // Get mpdf fonts dirs and data.
    $fontDirs = $this->mpdfDefaultConfig['fontDir'];
    $fontData = $this->mpdfDefaultFontConfig['fontdata'];
    // Set MPDF mandatory options.
    $mpdfOptions['tempDir'] = $file_system->getTempDirectory();
    $mpdfOptions['mode'] = 'utf-8';
    $mpdfOptions['fontDir'] = array_merge($fontDirs, $custom_fonts_directories);
    $mpdfOptions['fontdata'] = array_merge($fontData, $custom_fonts_data);
    // Create MPDF object.
    $mpdf = new Mpdf($mpdfOptions);
    $mpdf->WriteHTML($htmlContent);
    $file = (empty($file_parents) ? $base : $dirname . '/') . $file_name;
    $mpdf->Output($file, 'F');
    $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file);
    return [
      'url' => $url,
      'uri' => $file,
    ];
  }

  /**
   * Resolves the font directories setting into existing absolute paths.
   *
   * @param string $font_directories
   *   One directory per line, relative to the Drupal root.
   *
   * @return string[]
   *   Absolute paths of the existing directories.
   */
  protected function resolveFontDirectories(string $font_directories): array {
    $directories = [];
    foreach (preg_split('/\R/', $font_directories) as $directory) {
      $directory = trim($directory);
      if ($directory === '') {
        continue;
      }
      $path = DRUPAL_ROOT . '/' . ltrim($directory, '/');
      if (is_dir($path)) {
        $directories[] = $path;
      }
    }
    return $directories;
  }

}
