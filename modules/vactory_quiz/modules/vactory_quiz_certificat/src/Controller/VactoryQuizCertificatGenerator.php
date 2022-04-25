<?php

namespace Drupal\vactory_quiz_certificat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Vactory Quiz Certificat routes.
 */
class VactoryQuizCertificatGenerator extends ControllerBase {

  /**
   * Token service
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Entity repository service
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Entity Type Manager service
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity Type Manager service
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->token = $container->get('token');
    $instance->entityRepository = $container->get('entity.repository');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * Builds the response.
   */
  public function build($token) {
    if (empty($token)) {
      throw new NotFoundHttpException();
    }
    $data = \Drupal::service('vactory_core.tools')->decrypt($token);
    $data = explode('_', $data);

    if (empty($data) || count($data) < 3) {
      throw new NotFoundHttpException();
    }

    $user_id = $data[0];
    $quiz_id = $data[1];
    $certificat_time = $data[2];

    if (!is_numeric($user_id) || !is_numeric($quiz_id) || !is_numeric($certificat_time)) {
      throw new NotFoundHttpException();
    }

    $quiz = $this->entityTypeManager->getStorage('node')
      ->load($quiz_id);

    if ($this->currentUser()->id() !== $user_id || !$quiz instanceof NodeInterface) {
      throw new NotFoundHttpException();
    }

    // Get Current langcode.
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    // Get Default langcode.
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();

    // Get quiz translation.
    $quiz_translation = $this->entityRepository->getTranslationFromContext($quiz, $langcode);
    // Get certificate obtaining date.
    $certificat_date = \DateTime::createFromFormat('U', $certificat_time);
    $certificat_date = $certificat_date->format('d / m / Y');
    // Get module settings translation.
    $config = $this->config('vactory_quiz_certificat.settings');
    $config_translation = $config;
    if ($langcode !== $default_langcode) {
      $config_translation = $this->languageManager->getLanguageConfigOverride($langcode, 'vactory_quiz_certificat.settings');
      if (empty($config_translation->get('certificat_body'))) {
        $config_translation = $config;
      }
    }
    $config_translation = !$config_translation ? $config : $config_translation;
    $certificat_body = $config_translation->get('certificat_body')['value'];
    $certificat_body = preg_replace('#\[current-date:custom:(.)*]#', $certificat_date, $certificat_body);
    $output = $this->token->replace($certificat_body, ['quiz_title' => $quiz_translation->label()]);
    return [
      '#theme' => 'vactory_quiz_certificat_generate',
      '#content' => $output,
    ];
  }

}
