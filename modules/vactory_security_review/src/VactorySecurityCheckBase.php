<?php

namespace Drupal\vactory_security_review;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Drupal\vactory_security_review\Services\SecurityReviewHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Vactory security checks.
 *
 * Checks producing a dynamic message store it as the single finding of their
 * result, so it can be displayed as the status message.
 */
abstract class VactorySecurityCheckBase extends SecurityCheckBase {

  /**
   * The security review helper.
   *
   * @var \Drupal\vactory_security_review\Services\SecurityReviewHelper
   */
  protected SecurityReviewHelper $helper;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->helper = $container->get('vactory_security_review.helper');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusMessage(int $status = 0): string {
    $last_result = $this->lastResult();
    if (isset($last_result['findings'][0]) && $last_result['result'] === $status) {
      return (string) $last_result['findings'][0];
    }
    return parent::getStatusMessage($status);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    // The finding is already displayed as the status message.
    return $returnString ? '' : [];
  }

  /**
   * Stores a success/fail result with the given message.
   */
  protected function createMessageResult(bool $success, string $message): void {
    $this->createResult($success ? CheckResult::SUCCESS : CheckResult::FAIL, [$message]);
  }

  /**
   * Gets the domain (without scheme) from the given settings.php variable.
   */
  protected function getDomain(string $setting_name): ?string {
    $domain = Settings::get($setting_name);
    if (!is_string($domain) || $domain === '') {
      return NULL;
    }
    $domain = preg_replace('#^https?://#', '', $domain);
    return rtrim(preg_replace('#/user/?$#', '', $domain), '/');
  }

  /**
   * Validates the SSL certificate of the domain defined by the given setting.
   */
  protected function checkSslCert(string $setting_name, string $example_url): void {
    $domain = $this->getDomain($setting_name);
    if (!$domain) {
      $this->createMessageResult(FALSE, "SSL validation: $setting_name env variable is not defined! Please define it first then try again, example: $setting_name=$example_url");
      return;
    }
    $validation_infos = $this->helper->hasValidSslCert($domain);
    $this->createMessageResult($validation_infos['success'], 'SSL validation: ' . $validation_infos['message']);
  }

  /**
   * Checks the given response header on the domain defined by the setting.
   */
  protected function checkHeader(string $setting_name, string $example_url, string $label, string $header, string $path = '', string $missing_hint = ''): void {
    $domain = $this->getDomain($setting_name);
    if (!$domain) {
      $this->createMessageResult(FALSE, "$label $header header: $setting_name env variable is not defined! Please define it first then try again, example: $setting_name=$example_url");
      return;
    }
    $headers = [];
    if ($this->helper->hasValidSslCert($domain)['success']) {
      $headers = array_change_key_case(get_headers("https://$domain$path", TRUE) ?: [], CASE_LOWER);
    }
    if (isset($headers[strtolower($header)])) {
      $this->createMessageResult(TRUE, "$label $header header: The $header header is present in the response for $domain.");
    }
    else {
      $this->createMessageResult(FALSE, trim("$label $header header: The $header header is NOT present in the response for $domain. $missing_hint"));
    }
  }

}
