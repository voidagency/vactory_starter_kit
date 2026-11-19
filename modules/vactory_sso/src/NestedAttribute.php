<?php

namespace Drupal\vactory_sso;

/**
 * Reads attributes of the OAuth resource owner returned by the IdP.
 */
final class NestedAttribute {

  /**
   * Returns the value of an attribute, given as a dotted path.
   *
   * Close to the miniOrange getnestedattribute(): "a.b.c" walks nested
   * arrays and a list value returns its first item. Unlike miniOrange, an
   * associative array (object) value returns NULL instead of a warning, and
   * paths are split strictly on dots (no string offsets).
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
    $value = $path === '' ? NULL : $resource;
    foreach ($path === '' ? [] : explode('.', $path) as $key) {
      if (!is_array($value) || !array_key_exists($key, $value)) {
        $value = NULL;
        break;
      }
      $value = $value[$key];
    }
    if (is_array($value)) {
      $value = array_is_list($value) ? ($value[0] ?? NULL) : NULL;
    }
    return $value;
  }

}
