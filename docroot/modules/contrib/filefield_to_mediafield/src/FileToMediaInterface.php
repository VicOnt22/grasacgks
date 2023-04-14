<?php

namespace Drupal\filefield_to_mediafield;

use Drupal\Core\Field\FieldItemList;
use Drupal\media\Entity\Media;

/**
 * Interface for migrating file fields to media fields.
 */
interface FileToMediaInterface {

  /**
   * Creates a core media entity from a file entity.
   *
   * @param Drupal\Core\Field\FieldItemList $source_file_field
   *   The source file field.
   * @param string $media_bundle
   *   The type of media to create.
   * @param string $media_entity_file_field_name
   *   The field on the media entity to store the file on.
   *
   * @return \Drupal\media\Entity\Media
   *   The created media entity.
   */
  public function createMediaEntity(FieldItemList $source_file_field, string $media_bundle, string $media_entity_file_field_name): Media;

  /**
   * Performs migration.
   *
   * @param string $entity_type
   *   The entity type for which to migrate files to media.
   * @param string $bundle
   *   The bundle on which to migrate files to media.
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
   */
  public function copy(string $entity_type, string $bundle, string $media_bundle, string $media_entity_file_field_name, array $fields);

}
