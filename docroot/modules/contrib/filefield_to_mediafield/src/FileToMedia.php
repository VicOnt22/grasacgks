<?php

namespace Drupal\filefield_to_mediafield;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemList;
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
   * Constructs a new FileToMedia object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function createMediaEntity(FieldItemList $source_file_field, string $media_bundle, string $media_entity_file_field_name): Media {
    $file = $source_file_field->entity;
    $media_entity = Media::create([
      'bundle' => $media_bundle,
      'uid' => '1',
      'name' => !empty($source_file_field->alt) ? $source_file_field->alt : $file->label() . ', FID: ' . $file->id(),
      'status' => 1,
      $media_entity_file_field_name => [
        'target_id' => $file->id(),
        'alt' => $source_file_field->alt,
      ],
    ]);
    $media_entity->save();
    return $media_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function copy(string $entity_type, string $bundle, string $media_bundle, string $media_entity_file_field_name, array $fields) {
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
          $this->logger->error('The entity @entity does not have the specified source / target fields!', ['@entity' => $entity->label()]);
          continue;
        }
        if (empty($entity->{$source_file_field_name}->entity)) {
          // For this entity, no file exists on the specified source field name.
          continue;
        }
        try {
          $source_file_field = $entity->{$source_file_field_name};
          $file = $source_file_field->entity;
          $entity->{$target_media_field_name}->entity = $this->createMediaEntity($source_file_field, $media_bundle, $media_entity_file_field_name);
          $entity->save();
          $this->logger->notice(
            'Copied file "@file" in @entity_type "@entity" from field "@source_file_field_name" to media field "@target_media_field_name".',
            [
              '@file' => $file->label() . ', FID:' . $file->id(),
              '@entity_type' => $entity_type,
              '@entity' => $entity->label(),
              '@source_file_field_name' => $source_file_field_name,
              '@target_media_field_name' => $target_media_field_name,
            ]
          );
        }
        catch (\Exception $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }

}
