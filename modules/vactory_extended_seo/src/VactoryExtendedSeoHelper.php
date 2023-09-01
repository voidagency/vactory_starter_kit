<?php

namespace Drupal\vactory_extended_seo;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain_alias\Entity\DomainAlias;

class VactoryExtendedSeoHelper {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Module Handler service.
   *
   * @var Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * check whether the module is used in a decoupled project or monolith.
   *
   * @var bool
   */
  protected $isdecoupled;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LanguageManagerInterface $languageManager,
    ModuleHandlerInterface $moduleHandler

  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->languageManager = $languageManager;
    $this->moduleHandler = $moduleHandler;
    $this->isdecoupled = !empty(getenv("BASE_FRONTEND_URL"));

  }

  public function generateAlternate($currentNode, &$attached) {
    $seo_entity = $this->entityTypeManager->getStorage('vactory_extended_seo')
      ->loadByProperties(['node_id' => $currentNode]);
    if ($seo_entity) {
      $attached = [];
      $language = $this->languageManager->getCurrentLanguage()->getId();
      $domain_config = NULL;
      if ($this->moduleHandler->moduleExists('domain')) {
        $domain_config = \Drupal::service('config.factory')->getEditable('domain.settings')->getRawdata();
      }
      $activeLanguages =$this->languageManager->getLanguages();
      $seo_entity = reset($seo_entity);
      $host = $this->isdecoupled ? getenv("BASE_FRONTEND_URL") : \Drupal::request()->getSchemeAndHttpHost();
      foreach ($activeLanguages as $lang => $obj) {
        $language_map = [];
//        Must pay attention to domain_alias too & should get the LBR version of domain module.
        if (function_exists('_get_allowed_languages_with_alias')) {
          $domain_lang = array_filter($domain_config, static function($el) {
            return str_contains($el, '_languages');
          }, ARRAY_FILTER_USE_KEY);

          $language_map = array_filter($domain_lang, static function($el) use ($lang) {
            foreach ($el as $key => $val) {
              if ($val === $lang) {
                return true;
              }
            }
            return false;
          });
          $language_map = array_keys($language_map);
          $domain_id = reset($language_map);
          $domain_id = !empty($domain_id) ? str_replace('_languages', '', $domain_id) : '';
          if ($domain_id) {
            $domainstorage = $this->entityTypeManager->getStorage('domain');
            $aliasstorage = $this->entityTypeManager->getStorage('domain_alias');
            // Must know the domain's entity id, as $id.
            $domain = $domainstorage->load($domain_id) ?? $aliasstorage->load($domain_id);
            $host = $domain instanceof DomainAlias ? $domain?->getPattern() : $domain?->getPath() ;
            $host = $host ? str_replace('*.', '', $host) : NULL;
            if (!preg_match("~^(?:f|ht)tps?://~i", $host)) {
              $host = "https://" . $host;
            }
            $host = rtrim($host,"/");
          }
        }
        $enhanced_host = $this->isdecoupled  ? "$host/$lang" : $host;
        $sanitizeId = str_replace('-', '_', $lang);
        $value = $seo_entity?->get("alternate_$sanitizeId")?->getValue();
        $value = is_array($value) ? reset($value) : '';
        $value = is_array($value) ? reset($value) : '';
        if ($value) {
          $base = [
            [
              "rel" =>  'alternate',
              "hreflang" => $language === $lang ? 'x-default' : $lang,
              "href" => !preg_match("~^(?:f|ht)tps?://~i", $value) ? "$enhanced_host$value" : $value,
            ],
          ];
          $attached[] = $base;
        }
      }
    }
  }
}
