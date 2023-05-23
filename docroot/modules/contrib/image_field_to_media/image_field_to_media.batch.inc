<?php

/**
 * @file
 * Batch processing API for populate Media field of the entity with image items.
 */

/**
 * Populate Media field of the entity with image items.
 *
 * Create Media entities based on existing image fields.
 * Populate the Media field, that was created, with the Media entities.
 *
 * @param string $entity_type_id
 *   The entity type id.
 * @param string $bundle
 *   The bundle of the entity type.
 * @param string $image_field_name
 *   The name of the Image field.
 * @param string $media_field_name
 *   The name of the created Media field.
 * @param array $context
 *   Batch context.
 */
function image_field_to_media_populate_media_field($entity_type_id, $bundle, $image_field_name, $media_field_name, array &$context) {

  if (empty($context['results']['updated_entities'])) {
    $context['results']['updated_entities'] = 0;
  }

  $counter = $context['results']['updated_entities'];

  $entity_id = \Drupal::entityTypeManager()->getStorage($entity_type_id)
    ->getQuery()
    ->condition('type', $bundle)
    ->exists($image_field_name)
    ->range($counter, 1)
    ->execute();

  // Check if all entities have been processed and if so, then set
  // $context['finished'] = 1 to finish the operation.
  if (!$entity_id) {
    $context['finished'] = 1;
    return;
  }

  $entity_id = reset($entity_id);
  $entity = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($entity_id);

  $images = $entity->get($image_field_name)->getValue();
  $entity->set($media_field_name, NULL);

  foreach ($images as $image) {
    $fid = $image['target_id'];
    $file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->load($fid);

    // There have been cases where data about image files was lost and this
    // caused error and stops the conversion process. So let's check for this.
    if (!$file) {
      $message = 'Missing file with ID: %fid was on %entity_type_id %bundle %entity_id.';
      $message = 'Media cannot be created. The %entity_type_id with ID: %entity_id of bundle: %bundle refers to the image file with ID: %fid. But there is no information about the file with this ID in the database.';
      \Drupal::logger('image_field_to_media')->error($message, [
        '%fid' => $fid,
        '%entity_type_id' => $entity_type_id,
        '%bundle' => $bundle,
        '%entity_id' => $entity_id,
      ]);
    }
    else {
      $file_uri = $file->getFileUri();
      $media = image_field_to_media_get_media_entity($file_uri, $image);
      $entity->get($media_field_name)->appendItem($media);
    }
  }

  $entity->save();
  $context['results']['updated_entities']++;
  $context['message'] = t('Updated entities: @number.', ['@number' => $context['results']['updated_entities']]);
  $context['finished'] = 0;
}

/**
 * Return or new or existed Media entity.
 *
 * If a Media entity with such image already exists, then return this entity.
 * To implement this we store sha1 hashes of image files after creation of
 * new Media entities.
 *
 * @param string $file_uri
 *   URI of the image file.
 * @param array $image
 *   The array that represents one item of the Image field.
 *
 * @return \Drupal\media\MediaInterface
 *   The media Entity with the image.
 */
function image_field_to_media_get_media_entity($file_uri, array $image) {
  // Get stored hashes of image files.
  $hashes = \Drupal::state()->get('image_field_to_media.hashes_of_image_files');

  // Get hash of the current image file.
  $hash = sha1_file($file_uri);

  // Check if the Media entity with such image already exists.
  // Compare hash of the image file with hashes that was stored previously.
  // The structure of the $hashes array: ['media id' => 'hash of image file']
  // 'media id' is id of the Media entity that contains the image file that
  // was hashed.
  $mid = array_search($hash, $hashes);
  if ($mid) {
    // Load the Media entity that has the current image and return it.
    $media = \Drupal::entityTypeManager()
      ->getStorage('media')
      ->load($mid);

    return $media;
  }

  // Create new Media entity with the current image.
  $media = \Drupal::entityTypeManager()
    ->getStorage('media')
    ->create([
      'bundle' => 'image',
      'uid' => 1,
    ]);

  $media->set('field_media_image', $image);
  $media->save();

  // Store the media id and hash of the image file.
  $hashes[$media->id()] = $hash;
  \Drupal::state()->set('image_field_to_media.hashes_of_image_files', $hashes);

  return $media;
}

/**
 * Batch finish callback.
 */
function image_field_to_media_batch_finished($success, $results, $operations) {
  if ($success) {
    $entities_number = $results['updated_entities'];
    $message = t('Cloned successfully! Updated entities: @number.', ['@number' => $entities_number]);
    \Drupal::messenger()->addStatus($message);
  }
  else {
    \Drupal::messenger()->addError(t('Finished with an error.'));
  }
}
