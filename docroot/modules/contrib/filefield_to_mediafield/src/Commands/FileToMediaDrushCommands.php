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
   * @param string $file_field_name
   *   The field on which the file to copy is stored.
   * @param string $media_field_name
   *   The field to store the created media entity on.
   * @param string $media_bundle
   *   The type of media to create.
   * @param string $media_entity_file_field_name
   *   The field on the media entity to store the file on.
   * @param string $entity_type
   *   The entity type for which to migrate files to media.
   * @param null|string $bundle
   *   (Optional) The bundle on which to migrate files to.
   * @param array $options
   *   The flags provided, when running the command.
   *
   * @option no-reuse
   *   This disables the reuse of existing media entities when
   *   available. Use this when you experience issues with the reuse hashes, you
   *   are not using the default media field configuration or when you need to
   *   use duplicates with different alt or title text.
   *
   * @command filefield-to-media:copy
   * @aliases fftm
   * @usage filefield-to-media:copy field_image field_image_media image field_media_image node article --no-reuse
   */
  public function copyCommand(string $file_field_name = 'field_image', string $media_field_name = 'field_image_media', string $media_bundle = 'image', string $media_entity_file_field_name = 'field_media_image', string $entity_type = 'node', ?string $bundle = NULL, $options = ['no-reuse' => FALSE]) {
    $deduplicate = TRUE;
    if ($options['no-reuse'] == TRUE) {
      $deduplicate = FALSE;
    }
    $this->fileToMediaService->copy(
      $media_bundle,
      $media_entity_file_field_name,
      [$file_field_name => $media_field_name],
      $entity_type,
      $bundle,
      $deduplicate
    );
  }

}
