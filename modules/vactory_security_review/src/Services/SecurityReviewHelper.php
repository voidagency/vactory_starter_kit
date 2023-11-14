<?php

namespace Drupal\vactory_security_review\Services;

/**
 * Security reviw helper service class.
 */
class SecurityReviewHelper {

  const HTTPS_PORT = 443;

  /**
   * Check whether the given domain ssl cert is valid or not.
   */
  public function hasValidSslCert($domain) {
    $port = static::HTTPS_PORT; // HTTPS port
    $context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
    $client = @stream_socket_client("ssl://$domain:$port", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

    if ($client) {
      $params = stream_context_get_params($client);
      $cert = $params["options"]["ssl"]["peer_certificate"];

      // Get the certificate details including the SAN
      $certDetails = openssl_x509_parse($cert);
      $san = $certDetails['extensions']['subjectAltName'];

      // Check if the domain is in the SAN
      $domainFound = false;
      foreach (explode(", ", $san) as $name) {
        if (strpos($name, "DNS:") === 0) {
          $sanDomain = substr($name, 4);
          if ($sanDomain === $domain || (strpos($sanDomain, '*.') === 0 && strpos($domain, '.') !== false)) {
            $domainFound = true;
            break;
          }
        }
      }

      if ($domainFound) {
        return [
          'success' => TRUE,
          'message' => "SSL certificate is valid for $domain",
        ];
      }
      else {
        return [
          'success' => FALSE,
          'message' => "SSL certificate is invalid for $domain",
        ];
      }
    } else {
      return [
        'success' => FALSE,
        'message' => "Failed to connect to $domain using 443 port, please ensure that HTTPS is enabled and try again",
      ];
    }

  }

}
