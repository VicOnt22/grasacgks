<?php

namespace Drupal\filefield_to_mediafield\Commands;

use Drupal\filefield_to_mediafield\FileToMedia;
use Drush\Commands\DrushCommands;

/**
 * Defines Drush commands for the Filefield to Media Copy module.
 */
class FileToMediaDrushCommands extends DrushCommands {

  /**
   * The file to media service.
   *
   * @var Drupal\filefield_to_mediafield\FileToMedia
   */
  protected $fileToMediaService;

  /**
   * {@inheritdoc}
   */
  public function __construct(FileToMedia $fileToMediaService) {
    $this->fileToMediaService = $fileToMediaService;
  }

  /**
   * Copies filefield data to media entities.
   *
   * @param string $entity_type
   *   The entity type for which to migrate files to media.
   * @param string $bundle
   *   The bundle on which to migrate files to media.
   * @param string $file_field_name
   *   The field on which the file to copy is stored.
   * @param string $media_field_name
   *   The field to store the created media entity on.
   * @param string $media_bundle
   *   The type of media to create.
   * @param string $media_entity_file_field_name
   *   The field on the media entity to store the file on.
   *
   * @command filefield-to-media:copy
   * @aliases fftm
   * @usage filefield-to-media:copy node article field_image field_image_media image field_media_image
   */
  public function copy($entity_type = 'node', $bundle = 'article', $file_field_name = 'field_image', $media_field_name = 'field_image_media', $media_bundle = 'image', $media_entity_file_field_name = 'field_media_image') {
    $this->fileToMediaService->copy(
      $entity_type,
      $bundle,
      $media_bundle,
      $media_entity_file_field_name,
      [$file_field_name => $media_field_name]
    );
  }

}
