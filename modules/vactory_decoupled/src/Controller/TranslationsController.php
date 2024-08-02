<?php

namespace Drupal\vactory_decoupled\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\locale\StringDatabaseStorage;
use Drupal\vactory_decoupled\EditLiveModeHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

// @todo Add form to add string
// @todo Use Context for storing these values.
// @todo T('WELCOME REACT', array(), array('context' => '_FRONTEND'));

/**
 * Translation controller.
 */
class TranslationsController extends ControllerBase {

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\locale\StringDatabaseStorage
   */
  protected $stringDatabaseStorage;

  /**
   * The edit live mode helper service.
   *
   * @var \Drupal\vactory_decoupled\EditLiveModeHelper
   */
  protected $editLiveModeHelper;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    LanguageManagerInterface $languageManager,
    StringDatabaseStorage $stringDatabaseStorage,
    EditLiveModeHelper $editLiveModeHelper,
  ) {
    $this->languageManager = $languageManager;
    $this->stringDatabaseStorage = $stringDatabaseStorage;
    $this->editLiveModeHelper = $editLiveModeHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('locale.storage'),
      $container->get('vactory_decoupled.edit_live_mode_helper')
    );
  }

  /**
   * Output all drupal translations.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   json response
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
   *   result
   */
  public function getResults($langcode = 'en') {
    $conditions = ['language' => $langcode, 'context' => '_FRONTEND'];
    $options = [
      "translated"   => TRUE,
      "untranslated" => TRUE,
    ];
    $result = $this->stringDatabaseStorage->getTranslations($conditions, $options);
    $b = array_map(function ($t) {
      $translation = !empty($t->translation) ? $t->translation : $t->source;
      $liveModeFormat = "{LiveModeI18n id=\"{$t->source}\"}{$translation}{/LiveModeI18n}";
      $liveModeAllowed = $this->editLiveModeHelper->checkAccess();
      return [
        'source'      => $t->source,
        'translation' => $liveModeAllowed ? $liveModeFormat : $translation,
      ];
    }, $result);
    return $b;
  }

}
