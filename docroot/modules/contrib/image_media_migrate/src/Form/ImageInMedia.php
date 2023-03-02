<?php

namespace Drupal\image_media_migrate\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contains MSDSRevCleanerForm, for cleaning Database from old revision.
 */
class ImageInMedia extends FormBase {

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Batch Builder.
   *
   * @var \Drupal\Core\Batch\BatchBuilder
   */
  protected $batchBuilder;

  /**
   * The EntityTypeManagerInterface service connection to be used.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The EntityFieldManagerInterface service connection to be used.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The EntityTypeBundleInfoInterface service connection to be used.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The Connection service connection to be used.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * The LoggerChannelFactoryInterface service connection to be used.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The value number of rows.
   *
   * @var int
   */
  protected $tableRows;

  /**
   * The number limit elements for 1 step process.
   *
   * @var int
   */
  protected $limit;

  /**
   * Dependency injection container create function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container that used by DI.
   */
  public static function create(ContainerInterface $container): ImageInMedia {
    return new static(
      $container->get('extension.list.module'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('database'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Object construction from DI.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   Provides a list of available modules.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Manages the discovery of entity fields.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Provides discovery and retrieval of entity type bundles.
   * @param \Drupal\Core\Database\Connection $connection
   *   Base Database API connection class.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger channel factory interface.
   */
  public function __construct(ModuleExtensionList $extension_list_module, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, Connection $connection, LoggerChannelFactoryInterface $logger) {
    $this->moduleExtensionList = $extension_list_module;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->databaseConnection = $connection;
    $this->logger = $logger;
    $this->batchBuilder = new BatchBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_media_migrate_form_id';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('node');
    $bundles_list = $this->bundlesList($form_state);

    $form['#attributes'] = [
      'id' => 'image_media_migrate',
    ];

    // Container for selected form elements.
    $form['tables'] = [
      'content_types' => [
        '#title' => $this->t('If not selected, selected all'),
        '#type' => 'checkboxes',
        '#options' => array_combine(array_keys($bundles), array_column($bundles, 'label')),
        '#ajax' => [
          'event' => 'change',
          'callback' => '::ajaxCallback',
          'wrapper' => 'image_media_migrate',
        ],
      ],
      'table' => [
        '#type' => 'table',
        '#header' => ['image or media', '=>', 'image or media'],
      ],
      'add_row' => [
        '#type' => 'submit',
        '#value' => $this->t('Add field'),
        '#submit' => ['::addRows'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'image_media_migrate',
        ],
      ],
      'limit' => [
        '#title' => $this->t('Limit elements for 1 step process'),
        '#type' => 'number',
        '#step' => 1,
        '#min' => 1,
        '#default_value' => 25,
      ],
    ];

    $rows = [
      'source_field' => [
        '#type' => 'select',
        '#empty_option' => $this->t('- Select -'),
        '#options' => $bundles_list,
      ],
      '=>' => ['#markup' => '=>'],
      'target_field' => [
        '#type' => 'select',
        '#empty_option' => $this->t('- Select -'),
        '#options' => $bundles_list,
      ],
    ];
    // Build multiple rows with decrement for add previous years in top.
    for ($i = 0; $i <= $this->tableRows; $i++) {
      $form['tables']['table'][$i] = $rows;
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['run'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run batch'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * Update form function from ajax.
   *
   * @param array $callback
   *   Get form array.
   *
   * @return array
   *   Return form array.
   */
  public function ajaxCallback(array $callback) {
    return $callback;
  }

  /**
   * Add row to table and rebuild form.
   */
  public function addRows(array &$form, FormStateInterface $form_state) {
    ++$this->tableRows;
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $table = $form_state->getValue('table');
    $node_ids = $this->getNodes($table);
    $this->limit = $form_state->getValue('limit');
    /** @var \Drupal\node\NodeStorageInterface $nodes */
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($node_ids);
    $all_values = [
      'nodes' => $nodes,
      'table' => $table,
    ];

    $this->batchBuilder->setFile($this->moduleExtensionList->getPath('image_media_migrate') . '/src/Form/ImageInMedia.php');
    $this->batchBuilder->addOperation([$this, 'processItems'], [$all_values]);
    $this->batchBuilder->setFinishCallback([$this, 'finished']);

    batch_set($this->batchBuilder->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $all_fields = $form_state->getValue('table');
    $bundles_list = $this->bundlesList($form_state);
    $field_storage = $this->entityFieldManager->getFieldStorageDefinitions('node');
    $empty = TRUE;

    foreach ($all_fields as $id => $fields) {
      foreach ($bundles_list as $bundle) {
        if (!empty($fields['source_field']) && !empty($fields['target_field']) && (array_key_exists($fields['source_field'], $bundle) xor array_key_exists($fields['target_field'], $bundle))) {
          $form_state->setError($form['tables']['table'][$id], 'Fields must be of the same content type.');
        }
      }
      if ($fields['source_field'] === $fields['target_field'] && !empty($fields['source_field'])) {
        $form_state->setError($form['tables']['table'][$id], 'Is the same field.');
      }
      elseif (!empty($fields['source_field']) xor !empty($fields['target_field'])) {
        $form_state->setError($form['tables']['table'][$id], 'Seems like one field is missing.');
      }
      if (!empty($fields['source_field']) || !empty($fields['target_field'])) {
        $empty = FALSE;
      }
      if ($field_storage[$fields['source_field']]->isMultiple() && !$field_storage[$fields['target_field']]->isMultiple()) {
        $form_state->setError($form['tables']['table'][$id], 'Seems you try migrate from multiple field in single field.');
      }
    }
    if ($empty) {
      $form_state->setError($form['tables']['table'], 'Seems like no field was selected.');
    }

  }

  /**
   * Processor for batch operations.
   */
  public function processItems($all_values, array &$context) {
    // Elements per operation.
    $limit = $this->limit;

    // Set default progress values.
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($all_values['nodes']);
    }

    // Save items to array which will be changed during processing.
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $all_values['nodes'];
    }

    if (!empty($context['sandbox']['items'])) {
      // Remove already processed items.
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }

      $items = array_values($context['sandbox']['items']);
      for ($counter = 0; $counter != $limit; $counter++) {
        $this->processItem($items[$counter], $all_values['table']);

        // Save the progress point and next in line revision ID.
        $context['sandbox']['progress']++;
        $context['message'] = $this->t('Now processing node :progress of :count', [
          ':progress' => $context['sandbox']['progress'],
          ':count' => $context['sandbox']['max'],
        ]);

        // Increment total processed item values. Will be used in finished
        // callback.
        $context['results']['processed'] = $context['sandbox']['progress'];
        if ($context['sandbox']['progress'] === $context['sandbox']['max']) {
          break;
        }
      }
    }

    // If not finished all tasks, we count percentage of process. 1 = 100%.
    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Process single item.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Node for processed.
   * @param array $migrate_fields
   *   List source and target fields for processed.
   */
  public function processItem(ContentEntityInterface $entity, array $migrate_fields) {

    // Save every node using transaction to avoid corruptions or MySQL fail
    // over on bulk creation.
    $transaction = $this->databaseConnection->startTransaction();

    try {
      foreach ($migrate_fields as $migrate_field) {
        $source_field_id = $migrate_field['source_field'];
        $target_field_id = $migrate_field['target_field'];
        if (
          $entity->hasField($source_field_id) &&
          $entity->get($source_field_id)->isEmpty() === FALSE &&
          $entity->hasField($target_field_id) &&
          $entity->get($target_field_id)->isEmpty()
        ) {
          $source_field = $entity->get($source_field_id);
          $source_field_type = $source_field->getFieldDefinition()->getType();
          $images = $source_field->getValue();

          $target_field = $entity->get($target_field_id);
          $target_field_type = $target_field->getFieldDefinition()->getType();
          foreach ($images as $image) {
            // Migrate image in media with creating media entity.
            if ($source_field_type === 'image' && $target_field_type === 'entity_reference') {
              /** @var \Drupal\media\Entity\Media $media */
              $media = $this->entityTypeManager
                ->getStorage('media')
                ->create([
                  'bundle' => 'image',
                  'uid' => 1,
                ]);
              $media->set('field_media_image', $image);
              $media->save();
            }
            // Migrate media in image, only image file type.
            elseif ($source_field_type === 'entity_reference' && $target_field_type === 'image') {
              $load_media = $this->entityTypeManager
                ->getStorage('media')
                ->load($image['target_id']);
              if ($load_media->bundle() === 'image') {
                $media = $load_media->get('field_media_image')
                  ->first()->getValue();
              }
              else {
                $media = NULL;
              }
            }
            // Migrate media in media, only image file type.
            elseif ($source_field_type === 'entity_reference' && $target_field_type === 'entity_reference') {
              $load_media = $this->entityTypeManager
                ->getStorage('media')
                ->load($image['target_id']);
              if ($load_media->bundle() === 'image') {
                $media = $load_media;
              }
              else {
                $media = NULL;
              }
            }
            // Migrate image in image field.
            else {
              $media = $image;
            }
            $entity->get($target_field_id)->appendItem($media);
          }

          $entity->save();
        }
      }
    }
    catch (\Exception $e) {
      // Rollback if node not processed.
      $transaction->rollback();
      $this->logger->get('image_media_migrate')->error($e->getMessage());
    }
  }

  /**
   * Get all ids node with not empty source_field and empty target_field.
   */
  public function getNodes($table) {
    $nodes = $this->entityTypeManager->getStorage('node')->getQuery();
    $or_condition = $nodes->orConditionGroup();
    // Clear empty fields.
    $fields = array_filter($table, fn($value) => $value['source_field'] !== '' && $value['target_field'] !== '');
    foreach ($fields as $field) {
      $and_condition = $nodes->andConditionGroup()
        ->exists($field['source_field'])
        ->notExists($field['target_field']);
      $or_condition->condition($and_condition);
    }
    return $nodes->condition($or_condition)
      ->execute();
  }

  /**
   * List fields image or media with image.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function bundlesList(FormStateInterface $form_state) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo('node');
    $bundles_list = [];
    $content_types = array_filter($form_state->getValue('content_types') ?? []);

    foreach ($bundles as $bundle => $label) {
      $all_bundle_fields = $this->entityFieldManager->getFieldDefinitions('node', $bundle);
      foreach ($all_bundle_fields as $bundle_field) {
        $field_type = $bundle_field->getType();
        $field_handler_settings = $bundle_field->getSetting('handler_settings');
        if ((array_search($bundle, $content_types, TRUE) || empty($content_types)) && ($field_type === 'image' || ($field_type === 'entity_reference' && array_search('image', $field_handler_settings['target_bundles'] ?? [], TRUE)))) {
          $bundles_list[$label['label']][$bundle_field->getName()] = $bundle_field->getLabel() . ' (' . ($field_type === 'entity_reference' ? 'media' : $field_type) . ')';
        }
      }
    }
    return $bundles_list;
  }

  /**
   * Finish message function declaration.
   */
  public function finished($success, $results, $operations) {
    if (!empty($results['processed'])) {
      $this->messenger()->addStatus($this->t('Number of nodes processed: @count', [
        '@count' => $results['processed'],
      ]));
    }
    else {
      $this->messenger()->addWarning($this->t('No nodes has been processed'));
    }
  }

}
