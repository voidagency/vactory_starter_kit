<?php

namespace Drupal\vactory_html2pdf\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\Utility\Token;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

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
   * Html to pdf generator.
   *
   * @throws MpdfException|VactoryHtml2PdfException
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
    if (empty($file_scheme) || !$file_scheme) {
      // Set default file scheme to public.
      $current_time = new \DateTime('now');
      $date = $current_time->format('Y-m');
      $file_scheme = 'public://html2pdf/' . $date;
    }
    $dirname = $file_scheme;
    if (!empty($file_parents)) {
      $dirname = $file_scheme . '/' . $file_parents;
    }
    if (!file_exists($dirname)) {
      mkdir($dirname, 755, TRUE);
    }

    // Get module settings object.
    $config = $this->configFactory->get('vactory_html2pdf.settings');
    // Get fonts from module settings.
    $custom_fonts_directories = $this->state->get('vactory_html2pdf_font_dirs');
    $custom_fonts_data = $config->get('fonts_data');
    // Get mpdf fonts dirs and data.
    $fontDirs = $this->mpdfDefaultConfig['fontDir'];
    $fontData = $this->mpdfDefaultFontConfig['fontdata'];
    // Set MPDF mandatory options.
    $mpdfOptions['tempDir'] = '/tmp';
    $mpdfOptions['mode'] = 'utf-8';
    $mpdfOptions['fontDir'] = array_merge($fontDirs, $custom_fonts_directories);
    $mpdfOptions['fontdata'] = array_merge($fontData, $custom_fonts_data);
    // Create MPDF object.
    $mpdf = new Mpdf($mpdfOptions);
    $mpdf->WriteHTML($htmlContent);
    $file = $dirname . '/' . $file_name;
    $mpdf->Output($file, 'F');
    $url = file_create_url($file);
    return [
      'url' => $url,
      'uri' => $file,
    ];
  }

}
