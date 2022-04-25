<?php

namespace Drupal\vactory_wysiwyg_301to200\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use GuzzleHttp\Client;
use GuzzleHttp\RedirectMiddleware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 301 redirect links replacement filter.
 *
 * @Filter(
 *   id = "vactory_wysiwyg_301to200",
 *   title = @Translation("Vactory 301 to 200"),
 *   description = @Translation("Replace links with 301 redirect with the final redirect link"),
 *   settings = {},
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class Wysiwyg301to200Filter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritDoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);
    if (!empty($text) && strpos($text, 'href="') !== FALSE) {
      $dom = Html::load($text);
      $xpath = new \DOMXPath($dom);
      $site_uri = \Drupal::request()->getSchemeAndHttpHost();
      $httpClient = new Client(['allow_redirects' => ['track_redirects' => true]]);
      $current_path = \Drupal::service('path.current')->getPath();
      $path_alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
      foreach ($xpath->query('//a') as $element) {
        /** @var \DOMElement $element */
        if (!$element->hasAttribute('href')) {
          continue;
        }
        $link = $element->attributes->getNamedItem('href')->value;
        if (!empty($link)) {
          $link_info = parse_url($link);
          if (!isset($link_info['host']) && isset($link_info['path'])) {
            // Not interested in local href links.
            continue;
          }
          try {
            $response = $httpClient->request('GET', $link, ['timeout' => 2]);
            $redirects = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);
            if (!empty($redirects)) {
              $final_redirect = end($redirects);
              $element->setAttribute('href', $final_redirect);
              \Drupal::service('vactory_wysiwyg_301to200.logger')->addLinkInfo($path_alias, $link, $final_redirect);
            }
          }
          catch (\Exception $e) {
            $reason = 'REASON: ' . $e->getMessage();
            \Drupal::logger('vactory_wysiwyg_301to200')->warning('SKIPPED: On page "' . $path_alias . '", source link "' . $link . '"' . PHP_EOL . '        ' . $reason);
          }
        }
      }
      $result->setProcessedText(Html::serialize($dom));
    }
    return $result;
  }
}