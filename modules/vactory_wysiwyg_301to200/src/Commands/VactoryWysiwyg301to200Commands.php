<?php

namespace Drupal\vactory_wysiwyg_301to200\Commands;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drush\Commands\DrushCommands;
use GuzzleHttp\Client;
use GuzzleHttp\RedirectMiddleware;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class VactoryWysiwyg301to200Commands extends DrushCommands {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /*
   * {@inheritDoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    EntityFieldManagerInterface $entityFieldManager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    Connection $database
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->database = $database;
  }

  /**
   * Replace in wysiwyg fields all 301 links to final 200 associated links.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases, config, etc.
   * @option site-uri
   *   The Site URI to be used for hrefs with relative path, the site uri should contains the schema + domain name, Example: https://vactory.lapreprod.com.
   * @option entity-type
   *   The concerned entity type.
   * @option timeout
   *   The max curl timeout in seconds for each link (default to 0 which means unlimited).
   * @option bundle
   *   The concerned bundle of given entity type.
   * @usage wysiwyg301to200 --entity-type=node --bundle=vactory_news --site-uri=http://vactory.lapreprod.com
   *   Replace in vactory_news content type wysiwyg fields all 301 links with 200 links.
   * @usage wysiwyg301to200 --entity-type=node --site-uri=http://vactory.lapreprod.com
   *   Replace in all content types wysiwyg fields all 301 links with 200 links.
   * @usage wysiwyg301to200 --site-uri=http://vactory.lapreprod.com
   *   When --entity-type is not specified, the default value is node entity type.
   *   So the given command replace in all content types wysiwyg fields all 301 links.
   *
   * @command wysiwyg301to200
   * @aliases 301to200
   */
  public function wysiwyg301to200($options = ['site-uri' => '', 'entity-type' => 'node', 'bundle' => '', 'timeout' => 0]) {
    $site_uri = $options['site-uri'];
    if (empty($site_uri)) {
      $this->output->writeln('<error>ERROR: Please enter the site uri using --site-uri option, example: drush 301to200 --site-uri=http://vactory.lapreprod.com"</error>');
      exit(0);
    }
    $timeout = $options['timeout'];
    if (!is_numeric($timeout) || (is_numeric($timeout) && $timeout < 0)) {
      $this->output->writeln('<error>ERROR: --timeout option value should be a positive number</error>');
      exit(0);
    }
    $site_url_info = parse_url($site_uri);
    if (!isset($site_url_info['host'])) {
      $this->output->writeln('<error>ERROR: Site uri must contains a valid domain name, example: drush 301to200 --site-uri=http://vactory.lapreprod.com"</error>');
      exit(0);
    }
    $entity_type = $options['entity-type'];
    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      $this->output->writeln('<error>ERROR: Uknown entity type id "' . $entity_type . '"</error>');
      exit(0);
    }
    $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type));
    $bundle = !empty($options['bundle']) ? $options['bundle'] : '';
    if (!empty($bundle) && !in_array($bundle, $bundles)) {
      $this->output->writeln('<error>ERROR: The content type "' . $entity_type . '" has no bundle with id "' . $bundle . '"</error>');
      exit(0);
    }
    $bundles = !empty($bundle) ? [$bundle] : $bundles;
    $httpClient = new Client(['allow_redirects' => ['track_redirects' => true]]);
    $this->replaceLinks($entity_type, $bundles, $site_uri, $site_url_info, $httpClient, $timeout);
    $this->output->writeln('Please wait for cache rebuild...');
    drupal_flush_all_caches();
    $this->logger()->success("Cache rebuild complete, I'm Done! :)");
  }

  /**
   * Replace link in Wysiwyg fields.
   */
  public function replaceLinks($entity_type, $bundles, $site_uri, $site_url_info, $httpClient, $timeout) {
    foreach ($bundles as $bundle) {
      $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
      $fields = [];
      $fields[$bundle] = array_map(function ($definition) {return $definition;}, $definitions);
      $field_definitions[$bundle] = array_filter($definitions, function ($fieldDefinition) {
        return in_array($fieldDefinition->getType(), ['text_with_summary', 'text_long', 'entity_reference_revisions']);
      });
    }
    foreach ($field_definitions as $bundle => $definitions) {
      /** @var BaseFieldDefinition $definition */
      foreach ($definitions as $definition) {
        if ($definition->getType() === 'entity_reference_revisions') {
          // Paragraph field case.
          $settings = $definition->getSettings();
          if ($settings['target_type'] === 'paragraph') {
            $target_bundles = [];
            if (isset($settings['handler_settings']['target_bundles'])) {
              $target_bundles = $settings['handler_settings']['target_bundles'];
            }
            $target_bundles = !empty($target_bundles)? $target_bundles : array_keys($this->entityTypeBundleInfo->getBundleInfo('paragraph'));
            // Replace links in dynamic fields wysiwyg if exist.
            // @todo: Review dynamic field case.
            // $this->replaceLinksInDynamicField($target_bundles, 'paragraph', $site_uri, $site_url_info, $httpClient, $timeout);
            // Replace field in paragraph wysiwyg fields.
            $this->replaceLinks('paragraph', $target_bundles, $site_uri, $site_url_info, $httpClient, $timeout);
          }
        }
        else {
          // Wysiwyg field case.
          $field_name = $definition->getName();
          $table_name = $entity_type . '__' . $field_name;
          $column_name = $field_name . '_value';
          $placeholders = [
            ':bundle' => $bundle,
          ];
          $data = $this->database->query('SELECT entity_id,' . $column_name . ' FROM {'. $table_name . '} WHERE bundle=:bundle', $placeholders);
          if (!empty($data)) {
            $data = json_decode(json_encode($data->fetchAll()), TRUE);
            foreach ($data as $value) {
              $id = $value['entity_id'];
              $html = $value[$column_name];
              if (!empty($html)) {
                $dom = Html::load($html);
                $html_before = Html::serialize($dom);
                $xpath = new \DOMXPath($dom);
                foreach ($xpath->query('//a') as $element) {
                  /** @var \DOMElement $element */
                  if (!$element->hasAttribute('href')) {
                    continue;
                  }
                  $link = $element->attributes->getNamedItem('href')->value;
                  if (!empty($link)) {
                    $link_info = parse_url($link);
                    if (!isset($link_info['host']) && isset($link_info['path'])) {
                      $link = trim($site_uri, '/') . '/' . trim($link, '/');
                    }
                    try {
                      $response = $httpClient->request('GET', $link, ['timeout' => $timeout]);
                      $redirects = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);
                      if (!empty($redirects)) {
                        $final_redirect = end($redirects);
                        $final_redirect_info = parse_url($final_redirect);
                        if (isset($final_redirect_info['host']) && isset($site_url_info['host']) && $final_redirect_info['host'] === $site_url_info['host']) {
                          $final_redirect = isset($final_redirect_info['path']) && !empty($final_redirect_info['path']) ? $final_redirect_info['path'] : '/';
                        }
                        $element->setAttribute('href', $final_redirect);
                      }
                    }
                    catch (\Exception $e) {
                      $reason = 'REASON: ' . $e->getMessage();
                      $this->logger()->warning('SKIPPED: Field "' . $field_name . '" of entity "' . $bundle . '" with ID [' . $id . '] ' . PHP_EOL . '        ' . $reason);
                    }
                  }
                }
                $html = Html::serialize($dom);
                if ($html !== $html_before) {
                  // Update field value in database.
                  $this->database->update($table_name)
                    ->fields([
                      $column_name => $html,
                    ])
                    ->condition('entity_id', $id)
                    ->execute();
                  $this->logger()->success('Field "' . $field_name . '" of entity "' . $bundle . '" with ID [' . $id . '] has been successfully Updated.');
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Replace links in DF.
   */
  public function replaceLinksInDynamicField($bundles, $entity_type, $site_uri, $site_url_info, $httpClient, $timeout) {
    $field_definitions = [];
    foreach ($bundles as $bundle) {
      $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
      $field_definitions[$bundle] = array_filter($definitions, function ($fieldDefinition) {
        return $fieldDefinition->getType() === 'field_wysiwyg_dynamic';
      });
    }
    foreach ($field_definitions as $bundle => $definitions) {
      /** @var BaseFieldDefinition $definition */
      foreach ($definitions as $definition) {
        $field_name = $definition->getName();
        $table_name = $entity_type . '__' . $field_name;
        $column_name = $field_name . '_widget_data';
        $placeholders = [
          ':bundle' => $bundle,
        ];
        $data = $this->database->query('SELECT entity_id,' . $column_name . ' FROM {'. $table_name . '} WHERE bundle=:bundle', $placeholders);
        if (!empty($data)) {
          $data = json_decode(json_encode($data->fetchAll()), TRUE);
          foreach ($data as $value) {
            $id = $value['entity_id'];
            $widget_data = $value[$column_name];
            if (preg_match_all('/(href=\\\"[^"]+\\\")/', $widget_data, $matches)) {
              $original_href_links = $matches[1];
              $new_href_links = [];
              foreach ($original_href_links as $original_href) {
                $original_link = trim(str_replace(['href=\"', '\\'], '', $original_href), '"');
                if (!empty($original_link)) {
                  $original_link_info = parse_url($original_link);
                  if (!isset($original_link_info['host']) && isset($original_link_info['path'])) {
                    $original_link = trim($site_uri, '/') . '/' . trim($original_link, '/');
                  }
                  try {
                    $response = $httpClient->request('GET', $original_link, ['timeout' => $timeout]);
                    $redirects = $response->getHeader(RedirectMiddleware::HISTORY_HEADER);
                    if (!empty($redirects)) {
                      $final_redirect = end($redirects);
                      $final_redirect_info = parse_url($final_redirect);
                      if (isset($final_redirect_info['host']) && isset($site_url_info['host']) && $final_redirect_info['host'] === $site_url_info['host']) {
                        $final_redirect = isset($final_redirect_info['path']) && !empty($final_redirect_info['path']) ? $final_redirect_info['path'] : '/';
                      }
                      // Escape double quotes characters.
                      $new_href_links[] = addslashes('href="' . $final_redirect . '"');
                    }
                    else {
                      $new_href_links[] = $original_href;
                    }
                  }
                  catch (\Exception $e) {
                    $new_href_links[] = $original_href;
                    $reason = 'REASON: ' . $e->getMessage();
                    $this->logger()->warning('SKIPPED: Field "' . $field_name . '" of entity "' . $bundle . '" with ID [' . $id . '] ' . PHP_EOL . '        ' . $reason  );
                  }
                }
                else {
                  $new_href_links[] = $original_href;
                }
              }
              if (!empty($new_href_links) && count(array_intersect($new_href_links, $original_href_links)) !== count($new_href_links)) {
                $widget_data = str_replace($original_href_links, $new_href_links, $widget_data);
                // Update field value on database.
                $this->database->update($table_name)
                  ->fields([
                    $column_name => $widget_data,
                  ])
                  ->condition('entity_id', $id)
                  ->execute();
                $this->logger()->success('Field "' . $field_name . '" of entity "' . $bundle . '" with ID [' . $id . '] has been successfully Updated.');
              }
            }
          }
        }
      }
    }
  }

}
