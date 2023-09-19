<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\locale\StringDatabaseStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

// @todo: add form to add string
// @todo: use Context for storing these values.
// @todo: t('WELCOME REACT', array(), array('context' => '_FRONTEND'));

class TranslationsController extends ControllerBase {

  /**
   * Language manager service.
   *
   * @var LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\locale\StringDatabaseStorage
   */
    protected $stringDatabaseStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    LanguageManagerInterface $languageManager,
    StringDatabaseStorage $stringDatabaseStorage
  ) {
    $this->languageManager = $languageManager;
    $this->stringDatabaseStorage = $stringDatabaseStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('locale.storage')
    );
  }

  /**
   * Output all drupal translations.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function index() {
    $languages = $this->languageManager->getLanguages();

    $translations = array_map(function ($langcode) {
      return [
        'locale'       => $langcode,
        'translations' => $this->getResults($langcode),
      ];
    }, array_keys($languages));

    return new JsonResponse([
      'resources' => $translations,
    ]);
  }

  /**
   * A helper function returning results.
   *
   * @param string $langcode
   *   The language to fetch.
   *
   * @return array
   */
  public function getResults($langcode = 'en') {
    $conditions = ['language' => $langcode, 'context' => '_FRONTEND'];
    $options = [
      "translated"   => TRUE,
      "untranslated" => TRUE,
    ];
    $result = $this->stringDatabaseStorage->getTranslations($conditions, $options);
    $b = array_map(function ($t) {
      return [
        'source'      => $t->source,
        'translation' => !empty($t->translation) ? $t->translation : $t->source,
      ];
    }, $result);
    return $b;
  }
}
