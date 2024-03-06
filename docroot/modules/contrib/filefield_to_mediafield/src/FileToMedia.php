<?php

namespace Drupal\filefield_to_mediafield;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\media\Entity\Media;
use Psr\Log\LoggerInterface;

/**
 * Migrates file fields to media fields.
 */
class FileToMedia implements FileToMediaInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Drupal state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateService;

  /**
   * Constructs a new FileToMedia object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\State\StateInterface $stateService
   *   A state service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, StateInterface $stateService) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->stateService = $stateService;
  }

  /**
   * {@inheritdoc}
   */
  public function createMediaEntity(array $file, FileInterface $fileEntity, string $media_bundle, string $media_entity_file_field_name): Media {
    // Array of "image" and "file" field properties:
    $imageProperties = ['alt', 'title', 'width', 'height'];
    $fileProperties = ['display', 'description'];
    // Initiate the media file / image field:
    $mediaFileFieldProperties = [
      'target_id' => $fileEntity->id(),
    ];
    if ($media_bundle === 'image') {
      foreach ($imageProperties as $imageProperty) {
        if (isset($file[$imageProperty])) {
          $mediaFileFieldProperties[$imageProperty] = $file[$imageProperty];
        }
      }
    }
    else {
      foreach ($fileProperties as $fileProperty) {
        if (isset($file[$fileProperty])) {
          $mediaFileFieldProperties[$fileProperty] = $file[$fileProperty];
        }
      }
    }
    // Create the media entity:
    $media_entity = Media::create([
      'bundle' => $media_bundle,
      'uid' => '1',
      'name' => $fileEntity->getFilename(),
      'status' => 1,
      $media_entity_file_field_name => $mediaFileFieldProperties,
    ]);
    $media_entity->save();
    return $media_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function copy(string $media_bundle, string $media_entity_file_field_name, array $fields, string $entity_type, ?string $bundle, bool $deduplicate) {
    // Dynamically create storage for hashes of image files based on the
    // media field name. This will be used to prevent creation of Media entities
    // with the same images (Only if deduplicate is true):
    if ($deduplicate) {
      $this->stateService->set('filefield_to_mediafield.hashes_of_image_files', []);
      // Get all media, create a hash for it, and store it.
      $all_media = $this->entityTypeManager
        ->getStorage('media')
        ->loadMultiple();
      $hashes = [];
      foreach ($all_media as $media) {
        if (!empty($mediaFieldItemList = $media->{$media_entity_file_field_name})) {
          foreach ($mediaFieldItemList as $fileItem) {
            if (!($fileItem instanceof FileItem)) {
              continue;
            }
            $fid = $fileItem->get('target_id')->getValue();
            $file = NULL;
            if (!empty($fid)) {
              $file = File::load($fid);
            }
            if (!empty($file)) {
              $hash = sha1_file($file->createFileUrl(FALSE));
              if (!empty($hash)) {
                $hashes[$media->id()] = $hash;
              }
            }
            else {
              $this->logger->error('The file item target id is either NULL, or the file could not get loaded. Entity-ID: @entityId, Field name: @fieldName, Field Delta: @fieldItemDelta.', [
                '@entityId' => $fileItem->getEntity()->id(),
                '@fieldName' => $media_entity_file_field_name,
                '@fieldItemDelta' => $fileItem->getName(),
              ]);
            }
          }
        }
      }
      // The structure of the "hashes" array:
      // ['media id' => 'hash of image file']. 'media id' is the id of the Media
      // entity that contains the image file(s) that were hashed.
      $this->stateService->set('filefield_to_mediafield.hashes_of_image_files', $hashes);
    }

    $bundle_key = $this->entityTypeManager->getDefinition($entity_type)
      ->getKey('bundle');

    foreach ($fields as $source_file_field_name => $target_media_field_name) {
      if ($bundle_key && $bundle) {
        // Load all entities of the given entity type and bundle.
        $entities = $this->entityTypeManager->getStorage($entity_type)
          ->loadByProperties([$bundle_key => $bundle]);
      }
      else {
        // If no bundle is specified or if the entity type does not have a
        // bundle key, load all entities of the given entity type.
        $entities = $this->entityTypeManager->getStorage($entity_type)
          ->loadMultiple();
      }

      foreach ($entities as $entity) {
        // If the entity is not a fieldable entity, continue:
        if (!is_subclass_of($entity, 'Drupal\Core\Entity\FieldableEntityInterface')) {
          $this->logger->error('The entity @entity is not a fieldable entity!', ['@entity' => $entity->label()]);
          continue;
        }
        if (!$entity->hasField($source_file_field_name) || !$entity->hasField($target_media_field_name)) {
          // The entity does not have the specified source or target field name.
          // This can happen in case of passing the wrong field name or when
          // limiting loading entities to a certain bundle.
          $this->logger->warning("The entity's '@entity' bundle '@bundle' does not have the specified source / target fields!", [
            '@entity' => $entity->label(),
            '@bundle' => $entity->bundle(),
          ]);
          continue;
        }
        if (empty($entity->{$source_file_field_name}->entity)) {
          // For this entity, no file exists on the specified source field name.
          continue;
        }
        try {
          $files = $entity->get($source_file_field_name)->getValue();
          $entity->set($target_media_field_name, NULL);

          if ($deduplicate) {
            // Get stored hashes of image files.
            $hashes = $this->stateService->get('filefield_to_mediafield.hashes_of_image_files');
          }

          foreach ($files as $file) {
            // Get hash of the current image file.
            $fid = $file['target_id'];
            /** @var \Drupal\file\FileInterface $fileEntity */
            $fileEntity = $this->entityTypeManager
              ->getStorage('file')
              ->load($fid);

            // If the file entity can not be loaded, skip it:
            if ($fileEntity === NULL) {
              continue;
            }
            $fileUri = $fileEntity->createFileUrl(FALSE);
            // If the file has no file uri, skip it:
            if (empty($fileUri)) {
              continue;
            }

            $reused_status = 'Created';

            if ($deduplicate) {
              // Create and compare hash of the image and check if a Media
              // entity with the same image hash already exists, and reuse it if
              // found. The structure of the $hashes array:
              // ['media id' => 'hash of image file'] 'media id' is id of the
              // Media entity that contains the image file that was hashed.
              $hash = sha1_file($fileUri);
              $mid = array_search($hash, $hashes);
              if ($mid) {
                // Load the Media entity that has the current image and return
                // it:
                $media = $this->entityTypeManager
                  ->getStorage('media')
                  ->load($mid);
                $reused_status = 'Re-used';
              }
              else {
                // Create media entity and store the hash of the image file.
                $media = $this->createMediaEntity($file, $fileEntity, $media_bundle, $media_entity_file_field_name);
                $hashes[$media->id()] = $hash;
                $this->stateService->set('filefield_to_mediafield.hashes_of_image_files', $hashes);
              }
            }
            else {
              // No hashing needed, so just create the media entity.
              $media = $this->createMediaEntity($file, $fileEntity, $media_bundle, $media_entity_file_field_name);
            }

            // Add media to node.
            $entity->get($target_media_field_name)->appendItem($media);

            $this->logger->notice(
              '@reused file "@file" in @entity_type "@entity" from field "@source_file_field_name" to media field "@target_media_field_name".',
              [
                '@reused' => $reused_status,
                '@file' => $media->label() . ', FID:' . $media->id(),
                '@entity_type' => $entity_type,
                '@entity' => $entity->label(),
                '@source_file_field_name' => $source_file_field_name,
                '@target_media_field_name' => $target_media_field_name,
              ]
            );
          }
          $entity->save();
        }
        catch (\Exception $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }

}
