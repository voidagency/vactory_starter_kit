<?php

namespace Drupal\vactory_quiz_certificat\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Drupal\vactory_html2pdf\Services\VactoryHtml2PdfException;
use Drupal\vactory_html2pdf\Services\VactoryHtml2PdfManager;
use Drupal\vactory_quiz\Services\VactoryQuizManager;
use Drupal\vactory_reminder\SuspendCurrentItemException;
use Mpdf\MpdfException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Quiz certificat queue worker processor.
 *
 * @QueueWorker(
 *   id = "quiz_certificat_queue_processor",
 *   title = @Translation("Quiz Certificat Queue Worker"),
 *   cron = {"time" = 60}
 * )
 */
class GenerateCertificatQueueProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Vactory Html to PDF service.
   *
   * @var \Drupal\vactory_html2pdf\Services\VactoryHtml2PdfManager
   */
  protected $html2PdfManager;

  /**
   * Quiz manager service.
   *
   * @var \Drupal\vactory_quiz\Services\VactoryQuizManager
   */
  protected $quizManager;

  /**
   * Vactory Html to PDF service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $quizCertificatConfig;

  /**
   * Default language ID.
   *
   * @var string
   */
  protected $defaultLangcode;

  /**
   * Default language ID.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    VactoryHtml2PdfManager $html2PdfManager,
    VactoryQuizManager $quizManager,
    EntityTypeManagerInterface $entityTypeManager,
    MailManagerInterface $mailManager,
    LanguageManagerInterface $languageManager,
    ConfigFactoryInterface $configFactory,
    EntityRepositoryInterface $entityRepository
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->html2PdfManager = $html2PdfManager;
    $this->quizManager = $quizManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->mailManager = $mailManager;
    $this->languageManager = $languageManager;
    $this->configFactory = $configFactory;
    $this->defaultLangcode = $this->languageManager->getDefaultLanguage()->getId();
    $this->quizCertificatConfig = $this->configFactory->get('vactory_quiz_certificat.settings');
    $this->entityRepository = $entityRepository;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('vactory_html2pdf.manager'),
      $container->get('vactory_quiz.manager'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
      $container->get('config.factory'),
      $container->get('entity.repository')
    );
  }

  /**
   * @inheritDoc
   */
  public function processItem($data) {
    if (
      !isset($data['html_output']) ||
      !isset($data['output_file']) ||
      !isset($data['quiz_id']) ||
      !isset($data['user_id']) ||
      !isset($data['user_mark']) ||
      !isset($data['user_answers'])
    ) {
      throw new SuspendCurrentItemException('Item suspended: Insufficient item data missing one of html_output|output_file|quiz_id|user_id|user_answers|user_mark');
    }
    if (
      !is_numeric($data['quiz_id']) ||
      !is_numeric($data['user_id'])
    ) {
      throw new SuspendCurrentItemException('Item suspended: Invalid type for user_id|quiz_id data, needs to be numeric');
    }
    $user = $this->entityTypeManager->getStorage('user')
      ->load($data['user_id']);
    if (!$user instanceof UserInterface) {
      throw new SuspendCurrentItemException('Item suspended: No user has been found for user_id=' . $data['user_id']);
    }
    $quiz = $this->entityTypeManager->getStorage('node')
      ->load($data['quiz_id']);
    if (!$quiz instanceof NodeInterface) {
      throw new SuspendCurrentItemException('Item suspended: No quiz node has been found for quiz_id=' . $data['quiz_id']);
    }
    $html_output = $data['html_output'];
    $output_file = $data['output_file'];
    $mpdf_options = isset($data['mpdf_options']) ? $data['mpdf_options'] : [];
    try {
      $file_infos = $this->html2PdfManager->html2Pdf($html_output, $output_file, $mpdf_options);
      if ($file_infos && is_array($file_infos)) {
        if (isset($file_infos['uri'])) {
          // Update user quiz history.
          $this->quizManager->updateUserAttemptHistory(
            $data['user_id'],
            $data['quiz_id'],
            $data['user_mark'],
            $data['user_answers'],
            $file_infos['uri']
          );

          // Send email notification.
          $enable_email = $this->quizCertificatConfig->get('enable_email');
          if ($enable_email) {
            $user_email = $user->getEmail();
            $translated_quiz = $this->entityRepository->getTranslationFromContext($quiz, $this->languageManager->getCurrentLanguage()->getId());
            $file_url = Url::fromUri(\Drupal::service('file_url_generator')->generateAbsoluteString($file_infos['uri']), ['absolute' => TRUE])->toString();
            $node_url_options = [
              'absolute' => TRUE,
              'language' => $this->languageManager->getCurrentLanguage(),
            ];
            $quiz_url = Url::fromRoute('entity.node.canonical', ['node' => $quiz->id()], $node_url_options)->toString();
            $params = [
              'tokens' => [
                'quiz_node_url' => $quiz_url,
                'quiz_user_first_name' => $user->get('field_first_name')->value,
                'quiz_user_last_name' => $user->get('field_last_name')->value,
                'quiz_certificat_url' => $file_url,
                'quiz_title' => $translated_quiz->label(),
              ],
            ];
            $this->mailManager->mail('vactory_quiz_certificat', 'quiz_certificate_mail', $user_email, $this->defaultLangcode, $params, NULL, TRUE);
          }
        }
      }
    }
    catch (MpdfException $e) {
      throw new SuspendCurrentItemException('Item suspended: ' . $e->getMessage());
    }
    catch (VactoryHtml2PdfException $e) {
      throw new SuspendCurrentItemException('Item suspended: ' . $e->getMessage());
    }
  }
}
