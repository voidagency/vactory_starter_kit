<?php

namespace Drupal\vactory_quiz_certificat\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\vactory_quiz_certificat\Services\Exceptions\InvalidArgumentException;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

/**
 * Vactory quiz manager service class.
 */
class VactoryQuizCertificatManager {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

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
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  private $entityRepository;



  /**
   * {@inheritDoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
    AccountProxyInterface $currentUser,
    Token $token,
    StateInterface $state,
    LanguageManagerInterface $languageManager,
    EntityRepositoryInterface $entityRepository
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->token = $token;
    $this->state = $state;
    $this->languageManager = $languageManager;
    $this->entityRepository = $entityRepository;
    $this->mpdfDefaultConfig = (new ConfigVariables())->getDefaults();
    $this->mpdfDefaultFontConfig = (new FontVariables())->getDefaults();
  }

  /**
   * {@inheritDoc}
   */
  public function generateCertificat($quiz_nid) {
    $quiz = $this->entityTypeManager->getStorage('node')->load($quiz_nid);
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    if ($quiz instanceof NodeInterface) {
      // Get quiz label.
      $translated_quiz = $this->entityRepository->getTranslationFromContext($quiz, $langcode);
      $title = $translated_quiz->label();
      // Get current user infos.
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $first_name = $user->get('field_first_name')->value;
      $last_name = $user->get('field_last_name')->value;
      $first_name = !empty($first_name) ? str_replace(' ', '', $first_name) : $first_name;
      $last_name = !empty($last_name) ? str_replace(' ', '', $last_name) : $last_name;
      // Get module settings.
      $config = $this->configFactory->get('vactory_quiz_certificat.settings');
      $config_translation = $config;
      if ($langcode !== $default_langcode) {
        $config_translation = $this->languageManager->getLanguageConfigOverride($langcode, 'vactory_quiz_certificat.settings');
        if (empty($config_translation->get('certificat_body'))) {
          $config_translation = $config;
        }
      }
      $config_translation = !$config_translation ? $this->configFactory->get('vactory_quiz_certificat.settings') : $config_translation;
      $custom_fonts_directories = $this->state->get('vactory_quiz_certificat_font_dirs');
      $custom_fonts_data = $config->get('fonts_data');
      $orientation = $config->get('orientation');
      $certificat_body = $config_translation->get('certificat_body')['value'];
      $fontDirs = $this->mpdfDefaultConfig['fontDir'];
      $fontData = $this->mpdfDefaultFontConfig['fontdata'];
      // Set MPDF options.
      $mpdf_options = [
        'tempDir' => '/tmp',
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'fontDir' => array_merge($fontDirs, $custom_fonts_directories),
        'fontdata' => $fontData + $custom_fonts_data,
      ];
      if ($orientation !== 'default') {
        $mpdf_options['format'] = 'A4-L';
      }
      // Create MPDF object.
      $mpdf = new Mpdf($mpdf_options);
      //$mpdf->autoMarginPadding = 0;
      //$mpdf->bleedMargin = 0;
      $output = $this->token->replace($certificat_body, ['quiz_title' => $title]);
      $mpdf->WriteHTML($output);
      $current_time = new \DateTime('now');
      $file_name = !empty($first_name) ? $first_name . '-' : '';
      $file_name .= !empty($last_name) ? $last_name . '-' : '';
      $file_name .= 'Certif-' . $current_time->format('U') . '.pdf';
      $date = $current_time->format('Y-m');
      $dirname = 'private://quiz_certificat/' . $date;
      if (!file_exists($dirname)) {
        mkdir($dirname, 755, TRUE);
      }
      $file = $dirname . '/' . $file_name;
      $mpdf->Output($file, 'F');
      $url = file_create_url($file);
      return [
        'url' => $url,
        'uri' => $file,
      ];
    }
    else {
      throw new InvalidArgumentException('Invalid quiz ID argument "' . $quiz_nid . '"');
    }
  }

}
