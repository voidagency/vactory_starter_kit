<?php

namespace Drupal\vactory_sso;

/**
 * Reads attributes of the OAuth resource owner returned by the IdP.
 */
final class NestedAttribute {

  /**
   * Returns the value of an attribute, given as a dotted path.
   *
   * Same rules as the miniOrange module: "a.b.c" walks nested arrays, and a
   * list value returns its first item.
   *
   * @param array $resource
   *   Resource owner data.
   * @param string $path
   *   Attribute path, e.g. "email" or "address.locality".
   *
   * @return mixed
   *   The value, or NULL when the attribute does not exist.
   */
  public static function get(array $resource, string $path) {
    if ($path === '') {
      return NULL;
    }
    $value = $resource;
    foreach (explode('.', $path) as $key) {
      if (!is_array($value) || !array_key_exists($key, $value)) {
        return NULL;
      }
      $value = $value[$key];
    }
    if (is_array($value)) {
      return $value === [] ? NULL : reset($value);
    }
    return $value;
  }

}
