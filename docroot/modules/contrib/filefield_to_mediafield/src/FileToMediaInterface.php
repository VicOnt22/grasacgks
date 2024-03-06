<?php

namespace Drupal\filefield_to_mediafield;

use Drupal\file\FileInterface;
use Drupal\media\Entity\Media;

/**
 * Interface for migrating file fields to media fields.
 */
interface FileToMediaInterface {

  /**
   * Creates a core media entity from a file entity.
   *
   * @param array $file
   *   The source file.
   * @param \Drupal\file\FileInterface $fileEntity
   *   The file entity.
   * @param string $media_bundle
   *   The type of media to create.
   * @param string $media_entity_file_field_name
   *   The field on the media entity to store the file on.
   *
   * @return \Drupal\media\Entity\Media
   *   The created media entity.
   */
  public function createMediaEntity(array $file, FileInterface $fileEntity, string $media_bundle, string $media_entity_file_field_name): Media;

  /**
   * Performs migration.
   *
   * @param string $media_bundle
   *   The type of media to create.
   * @param string $media_entity_file_field_name
   *   The field on the media entity to store the file on.
   * @param array $fields
   *   A list of fields whose key is regular file field name
   *   and value is media field name.
   *   @code
   *     ['field_image' => 'field_media_image']
   *   @endcode
   * @param string $entity_type
   *   The entity type for which to migrate files to media.
   * @param null|string $bundle
   *   The bundle on which to migrate files to media.
   * @param bool $deduplicate
   *   Enables or disables deduplication of media entities. When enabled it
   *   will try to reuse existing media entities if the file is identical.
   */
  public function copy(string $media_bundle, string $media_entity_file_field_name, array $fields, string $entity_type, ?string $bundle, bool $deduplicate);

}
