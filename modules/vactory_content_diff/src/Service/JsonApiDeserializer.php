<?php

declare(strict_types=1);

namespace Drupal\vactory_content_diff\Service;

/**
 * Class JsonApiDeserializer.
 *
 * Convert JSON:API payloads (data + included + relationships)
 * into flat PHP arrays with automatically resolved relationships.
 */
class JsonApiDeserializer {

  /**
   * Index of included resources for O(1) lookup.
   *
   * @var array
   */
  private array $includedIndex = [];

  /**
   * Cache of already deserialized resources to avoid reprocessing.
   *
   * @var array
   */
  private array $cache = [];

  /**
   * Resources currently being processed (to detect circular refs).
   *
   * @var array
   */
  private array $processingStack = [];

  /**
   * Deserialize a JSON:API payload (as array) into simple arrays.
   */
  public function deserialize(array $jsonApiData) {
    // Validate top-level shape.
    if (!array_key_exists('data', $jsonApiData)) {
      throw new \InvalidArgumentException('JSON:API payload must contain a "data" key.');
    }

    $this->resetState();

    // Index included resources for O(1) lookup.
    if (isset($jsonApiData['included']) && is_array($jsonApiData['included'])) {
      $this->indexIncluded($jsonApiData['included']);
    }

    $data = $jsonApiData['data'];

    // Collection.
    if (is_array($data) && $this->isAssocArray($data) === FALSE) {
      $results = [];
      foreach ($data as $resource) {
        $results[] = $this->deserializeResourceOrThrow($resource);
      }

      return $results;
    }

    // Single resource (or null).
    if ($data === NULL) {
      return NULL;
    }

    return $this->deserializeResourceOrThrow($data);
  }

  /**
   * Decode JSON string and call deserialize().
   */
  public function deserializeFromJson(string $json) {
    $decoded = json_decode($json, TRUE);

    if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
      throw new \InvalidArgumentException(
        'Invalid JSON provided: ' . json_last_error_msg()
      );
    }

    if (!is_array($decoded)) {
      throw new \InvalidArgumentException('Decoded JSON must be an array/object.');
    }

    return $this->deserialize($decoded);
  }

  /**
   * Reset internal state before processing a new payload.
   *
   * Clears included index, cache and processing markers.
   */
  private function resetState(): void {
    $this->includedIndex = [];
    $this->cache = [];
    $this->processingStack = [];
  }

  /**
   * Index included resources into $this->includedIndex for O(1) lookup.
   */
  private function indexIncluded(array $included): void {
    foreach ($included as $resource) {
      if (!is_array($resource)) {
        continue;
      }

      if (empty($resource['type']) || empty($resource['id'])) {
        // Skip invalid included entries instead of failing hard.
        continue;
      }

      $key = $this->makeKey((string) $resource['type'], (string) $resource['id']);
      $this->includedIndex[$key] = $resource;
    }
  }

  /**
   * Deserialize a single resource object; throw if invalid.
   */
  private function deserializeResourceOrThrow(array $resource): array {
    if (empty($resource['type']) || !isset($resource['id'])) {
      throw new \InvalidArgumentException(
        'Each resource must have "type" and "id".'
      );
    }

    $type = (string) $resource['type'];
    $id = (string) $resource['id'];

    return $this->deserializeResource($type, $id, $resource);
  }

  /**
   * Core resource deserialization with caching and circular protection.
   */
  private function deserializeResource(string $type, string $id, ?array $resource = NULL): array {
    $key = $this->makeKey($type, $id);

    // Return from cache if already processed.
    if (isset($this->cache[$key])) {
      return $this->cache[$key];
    }

    // If currently processing this resource, return a minimal placeholder
    // to break circular loops. The placeholder will be replaced when the
    // original processing finishes (because cache will be set).
    if (isset($this->processingStack[$key])) {
      // Circular detected — return minimal reference to avoid infinite loop.
      return ['id' => $id, 'type' => $type, '_circular' => TRUE];
    }

    // Mark as processing.
    $this->processingStack[$key] = TRUE;

    // If full resource not provided, attempt to fetch from included index.
    if ($resource === NULL) {
      if (isset($this->includedIndex[$key])) {
        $resource = $this->includedIndex[$key];
      }
      else {
        // Missing resource: create a minimal representation and mark missing.
        $this->processingStack[$key] = FALSE;
        unset($this->processingStack[$key]);

        $missing = ['id' => $id, 'type' => $type, '_missing' => TRUE];
        $this->cache[$key] = $missing;

        return $missing;
      }
    }

    // Start building the deserialized array.
    $deserialized = [
      'id' => $id,
      'type' => $type,
    ];

    // Merge attributes at top level (if any).
    if (isset($resource['attributes']) && is_array($resource['attributes'])) {
      // Attribute keys may conflict with 'id' or 'type' — user may override
      // but we prefer keeping id/type as explicit.
      foreach ($resource['attributes'] as $attrKey => $attrVal) {
        // don't override existing reserved keys.
        if ($attrKey === 'id' || $attrKey === 'type') {
          continue;
        }
        $deserialized[$attrKey] = $attrVal;
      }
    }

    // Pre-cache a placeholder (to allow resolving circular refs
    // where child references parent).
    $this->cache[$key] = $deserialized;

    // Resolve relationships.
    if (isset($resource['relationships']) && is_array($resource['relationships'])) {
      foreach ($resource['relationships'] as $relName => $relData) {
        $deserialized[$relName] = $this->resolveRelationship($relData);
      }
    }

    // If resource contains meta or links fields and user wants them,
    // they can be accessed under _meta/_links. We keep meta if present.
    if (isset($resource['meta'])) {
      $deserialized['_meta'] = $resource['meta'];
    }

    if (isset($resource['links'])) {
      $deserialized['_links'] = $resource['links'];
    }

    // Update cache with final processed resource and clear processing mark.
    $this->cache[$key] = $deserialized;
    unset($this->processingStack[$key]);

    return $deserialized;
  }

  /**
   * Resolve relationship entry (handles to-one and to-many).
   */
  private function resolveRelationship(mixed $relData): mixed {
    // If relationship is directly the identifier or null.
    if ($relData === NULL) {
      return NULL;
    }

    // Often relationship structure is ["data" => ...].
    if (is_array($relData) && array_key_exists('data', $relData)) {
      $data = $relData['data'];
    }
    else {
      // In some APIs relationships are given directly as the identifier.
      $data = $relData;
    }

    // To-one relationship (null or single identifier).
    if ($data === NULL) {
      return NULL;
    }

    // To-many (array of identifiers)
    if (is_array($data) && $this->isAssocArray($data) === FALSE) {
      $out = [];
      foreach ($data as $identifier) {
        $out[] = $this->resolveIdentifierToResource($identifier);
      }
      return $out;
    }

    // Single identifier (associative array with 'type' and 'id')
    if (is_array($data) && $this->isAssocArray($data)) {
      return $this->resolveIdentifierToResource($data);
    }

    throw new \RuntimeException('Invalid relationship data structure.');
  }

  /**
   * Resolve resource identifier (type+id) into a deserialized resource.
   */
  private function resolveIdentifierToResource(mixed $identifier): array {
    if (!is_array($identifier) || empty($identifier['type']) || !isset($identifier['id'])) {
      // Malformed identifier: return as-is wrapped in structure to avoid.
      // throwing in bulk runs.
      return ['_invalid_identifier' => $identifier];
    }

    $type = (string) $identifier['type'];
    $id = (string) $identifier['id'];
    $key = $this->makeKey($type, $id);

    // If cached already, return cached value.
    if (isset($this->cache[$key]) && !isset($this->cache[$key]['_missing'])) {
      return $this->cache[$key];
    }

    // If included exists, use the full included resource to deserialize.
    if (isset($this->includedIndex[$key])) {
      $includedResource = $this->includedIndex[$key];
      return $this->deserializeResource($type, $id, $includedResource);
    }

    // Not present in included; return marker for missing resource.
    $missing = ['id' => $id, 'type' => $type, '_missing' => TRUE];
    // Cache missing marker so subsequent references are consistent.
    $this->cache[$key] = $missing;

    return $missing;
  }

  /**
   * Make a stable key for indexing/cache from type and id.
   */
  private function makeKey(string $type, string $id): string {
    return $type . ':' . $id;
  }

  /**
   * Determine whether an array is associative.
   */
  private function isAssocArray(array $arr): bool {
    if ([] === $arr) {
      return FALSE;
    }

    return array_keys($arr) !== range(0, count($arr) - 1);
  }

}
