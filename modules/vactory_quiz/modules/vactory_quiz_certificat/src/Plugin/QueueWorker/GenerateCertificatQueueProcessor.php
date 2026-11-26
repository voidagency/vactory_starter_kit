<?php

namespace Drupal\vactory_quiz_certificat\Plugin\QueueWorker;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Drupal\vactory_html2pdf\Services\VactoryHtml2PdfException;
use Drupal\vactory_html2pdf\Services\VactoryHtml2PdfManager;
use Drupal\vactory_quiz\Services\VactoryQuizManager;
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
   * Seconds before an item that could not be processed is claimed again.
   *
   * Same value as the "cron" time of the worker, which was the lease of a
   * failed item: such an item is retried by a following cron run.
   */
  protected const RETRY_DELAY = 60;

  /**
   * Logs why an item is not processed and builds the exception to throw.
   *
   * The item stays in the queue and is claimed again after the delay, the
   * other items of the queue are still processed. Core does not log this
   * exception: the reason is logged here.
   *
   * @param string $reason
   *   Why the certificate could not be generated.
   *
   * @return \Drupal\Core\Queue\DelayedRequeueException
   *   The exception to throw from processItem().
   */
  protected function retryLater(string $reason): DelayedRequeueException {
    $message = 'Certificate not generated, item kept in the queue and retried later: ' . $reason;
    \Drupal::logger('vactory_quiz_certificat')->error('Certificate not generated, item kept in the queue and retried later: @reason', [
      '@reason' => $reason,
    ]);
    return new DelayedRequeueException(static::RETRY_DELAY, $message);
  }

  /**
   * Logs why an item is dropped: it can never be processed.
   *
   * Missing or invalid data, deleted user or quiz: retrying would fail the
   * same way on each cron run. processItem() returns after this call, and the
   * cron removes the item from the queue.
   *
   * @param string $reason
   *   Why the certificate cannot be generated.
   */
  protected function drop(string $reason): void {
    \Drupal::logger('vactory_quiz_certificat')->error('Certificate not generated, item removed from the queue: @reason', [
      '@reason' => $reason,
    ]);
  }

  /**
   * Returns why the item data cannot be processed, or NULL.
   */
  protected function invalidDataReason($data): ?string {
    $reason = NULL;
    $required = [
      'html_output',
      'output_file',
      'quiz_id',
      'user_id',
      'user_answers',
      'user_mark',
    ];
    if (!is_array($data) || array_diff($required, array_keys(array_filter($data, static fn($value) => $value !== NULL)))) {
      $reason = 'Insufficient item data missing one of html_output|output_file|quiz_id|user_id|user_answers|user_mark';
    }
    elseif (!is_numeric($data['quiz_id']) || !is_numeric($data['user_id'])) {
      $reason = 'Invalid type for user_id|quiz_id data, needs to be numeric';
    }
    return $reason;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Queue\DelayedRequeueException
   *   When the PDF could not be generated: the item is retried later.
   */
  public function processItem($data) {
    $reason = $this->invalidDataReason($data);
    $user = $quiz = NULL;
    if ($reason === NULL) {
      $user = $this->entityTypeManager->getStorage('user')->load($data['user_id']);
      $quiz = $this->entityTypeManager->getStorage('node')->load($data['quiz_id']);
      if (!$user instanceof UserInterface) {
        $reason = 'No user has been found for user_id=' . $data['user_id'];
      }
      elseif (!$quiz instanceof NodeInterface) {
        $reason = 'No quiz node has been found for quiz_id=' . $data['quiz_id'];
      }
    }
    if ($reason !== NULL) {
      $this->drop($reason);
      return;
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
      throw $this->retryLater($e->getMessage());
    }
    catch (VactoryHtml2PdfException $e) {
      throw $this->retryLater($e->getMessage());
    }
  }

}
