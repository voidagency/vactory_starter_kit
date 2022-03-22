<?php

namespace Drupal\vactory_jsonapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

// @todo: add form to add string
// @todo: use Context for storing these values.
// @todo: t('WELCOME REACT', array(), array('context' => '_FRONTEND'));

class TranslationsController extends ControllerBase {

  /**
   * Output all drupal translations.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function index() {
    $languages = \Drupal::languageManager()->getLanguages();

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
    $result = \Drupal::service('locale.storage')
      ->getTranslations($conditions, $options);
    $b = array_map(function ($t) {
      return [
        'source'      => $t->source,
        'translation' => !empty($t->translation) ? $t->translation : $t->source,
      ];
    }, $result);
    return $b;
  }
}
