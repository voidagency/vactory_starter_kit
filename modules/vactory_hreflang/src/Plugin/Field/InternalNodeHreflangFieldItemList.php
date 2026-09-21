<?php

namespace Drupal\vactory_hreflang\Plugin\Field;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Computes alternate-language links for a node.
 */
class InternalNodeHreflangFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function defaultAccess(
    $operation = 'view',
    ?AccountInterface $account = NULL
  ) {
    $access = parent::defaultAccess($operation, $account);

    if ($operation === 'view') {
      foreach ([
        'vactory_hreflang.settings',
        'hreflang.settings',
        'language.negotiation',
        'system.site',
      ] as $config_name) {
        $access->addCacheableDependency(
          $this->configFactory->get($config_name)
        );
      }
      $access->addCacheContexts(['url.site']);
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    $definition,
    $name = NULL,
    ?TraversableTypedDataInterface $parent = NULL
  ) {
    $instance = parent::createInstance($definition, $name, $parent);
    $container = \Drupal::getContainer();
    $instance->configFactory = $container->get('config.factory');
    $instance->languageManager = $container->get('language_manager');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if (!$entity instanceof NodeInterface || $entity->isNew()) {
      return;
    }

    $mapping_config = $this->configFactory->get('vactory_hreflang.settings');
    $mappings = array_filter($mapping_config->get('mappings') ?? []);
    $hreflang_config = $this->configFactory->get('hreflang.settings');
    $x_default_language = $this->getXDefaultLanguage($hreflang_config);
    $add_x_default = (bool) $hreflang_config->get('x_default');
    $links = [];

    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      if (!$translation->access('view')) {
        continue;
      }

      $href = $this->buildHref($translation, $language);
      if ($add_x_default && $langcode === $x_default_language) {
        $links[] = [
          'rel' => 'alternate',
          'hreflang' => 'x-default',
          'href' => $href,
        ];
      }

      $links[] = [
        'rel' => 'alternate',
        'hreflang' => $mappings[$langcode] ?? $langcode,
        'href' => $href,
      ];
    }

    if ($links) {
      $this->list[0] = $this->createItem(0, $links);
    }
  }

  /**
   * Gets the language code used for the x-default link.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $hreflang_config
   *   The contributed Hreflang module configuration.
   *
   * @return string
   *   The x-default language code.
   */
  private function getXDefaultLanguage(ImmutableConfig $hreflang_config): string {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    if ($hreflang_config->get('x_default_fallback')) {
      $fallback = $this->configFactory
        ->get('language.negotiation')
        ->get('selected_langcode');
      if ($fallback && $fallback !== LanguageInterface::LANGCODE_SITE_DEFAULT) {
        $langcode = $fallback;
      }
    }

    return $langcode;
  }

  /**
   * Builds an alternate URL for a node translation.
   *
   * @param \Drupal\node\NodeInterface $translation
   *   The node translation.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The translation language.
   *
   * @return string
   *   An absolute backend or frontend URL.
   */
  private function buildHref(
    NodeInterface $translation,
    LanguageInterface $language
  ): string {
    $front_path = $this->configFactory->get('system.site')->get('page.front');
    $is_front = $front_path === '/node/' . $translation->id();
    $is_default_language = $language->getId() === $this->languageManager
      ->getDefaultLanguage()
      ->getId();

    if ($is_front && $is_default_language) {
      return $this->buildRootHref();
    }

    $url = $is_front
      ? Url::fromRoute('<front>')
      : $translation->toUrl('canonical');
    $url->setOption('language', $language);

    $frontend_url = Settings::get('BASE_FRONTEND_URL', '');
    if (!$frontend_url) {
      return $url->setAbsolute()->toString();
    }

    $path = $url->setAbsolute(FALSE)->toString();
    $request = $this->requestStack->getCurrentRequest();
    $base_path = $request ? rtrim($request->getBasePath(), '/') : '';
    if ($base_path && ($path === $base_path || str_starts_with($path, $base_path . '/'))) {
      $path = substr($path, strlen($base_path));
    }

    return rtrim($frontend_url, '/') . '/' . ltrim($path, '/');
  }

  /**
   * Builds the unprefixed root URL for the default-language homepage.
   *
   * @return string
   *   The absolute frontend or backend root URL.
   */
  private function buildRootHref(): string {
    $frontend_url = getenv('BASE_FRONTEND_URL');
    if ($frontend_url) {
      return rtrim($frontend_url, '/') . '/';
    }

    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      return $request->getSchemeAndHttpHost()
        . rtrim($request->getBasePath(), '/') . '/';
    }

    return Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
  }

}
