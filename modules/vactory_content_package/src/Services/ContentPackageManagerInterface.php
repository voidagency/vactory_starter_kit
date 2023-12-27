<?php

namespace Drupal\vactory_content_package\Services;

use Drupal\Core\Entity\EntityInterface;

/**
 * Content package manager interface.
 */
interface ContentPackageManagerInterface {

  const PRIMITIVE_TYPES = [
    "string",
    "integer",
    "language",
    "boolean",
    "email",
    "float",
    "list_string",
    "string_long",
  ];

  const DATE_TIME_TYPES = [
    "changed",
    "created",
  ];

  const MEDIA_FIELD_NAMES = [
    'audio' => 'field_media_audio_file',
    'image' => 'field_media_image',
    'file' => 'field_media_file',
    'remote_video' => 'field_media_oembed_video',
    'video' => 'field_media_video_file',
    'onboarding_video' => 'field_video_onboarding',
  ];

  const ENTITY_TYPES_KEYS = [
    'node_type',
    'paragraphs_type',
  ];

  const PARAGRAPHS_APPEARANCE_KEYS = [
    "paragraph_container",
    "container_spacing",
    "paragraph_css_class",
    "paragraph_background_image",
    "paragraph_background_parallax",
    "field_background_color",
    "field_paragraph_hide_lg",
    "field_paragraph_hide_sm",
    "field_position_image_x",
    "field_position_image_y",
    "field_size_image",
    "field_vactory_flag",
    "field_vactory_flag_2",
  ];

  const UNWANTED_KEYS = [
    // Node fields.
    "uuid",
    "vid",
    "revision_timestamp",
    "revision_uid",
    "revision_log",
    "promote",
    "sticky",
    "default_langcode",
    "revision_default",
    "revision_translation_affected",
    "publish_on",
    "unpublish_on",
    "field_content_access_users",
    "field_content_access_custom",
    "field_content_access_roles",
    "field_content_access_groups",
    "menu_link",
    "is_flagged",
    "metatag",
    "vcc_normalized",
    "cache_exclude",
    "machine_name",
    "candidature_spontanee_url",
    "vote",
    "internal_user",
    "internal_blocks",
    "internal_metatag",
    "internal_comment",
    "internal_breadcrumb",
    "internal_extra",
    "form_path_alias",
    "notification_title",
    "notification_message",
    "mail_subject",
    "mail_message",
    "generate_notification",
    "content_translation_source",
    "content_translation_outdated",
    "comment",
    "field_exclude_from_search",
    "field_vactory_meta_tags",
    "field_vactory_seo_status",
    // Paragraph unwanted keys.
    "parent_id",
    "parent_type",
    "parent_field_name",
    "behavior_settings",
    "revision_id",
    "content_translation_changed",
    "content_translation_created",
    "content_translation_uid",
  ];

  /**
   * Normalize given entity.
   */
  public function normalize(EntityInterface $entity, $entity_translation = FALSE): array;

  /**
   * Denormalize given entity.
   */
  public function denormalize(array $entity_values): array;

  /**
   * Generate media from the given url.
   */
  public function generateMediaFromUrl(string $url, string $type): ?int;

}
