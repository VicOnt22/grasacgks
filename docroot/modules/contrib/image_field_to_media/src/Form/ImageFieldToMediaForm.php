<?php

namespace Drupal\image_field_to_media\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_ui\FieldUI;

/**
 * Clone Image field to Media image field and update all entities of the bundle.
 */
class ImageFieldToMediaForm extends FormBase {

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The name of the Image field.
   *
   * @var string
   */
  protected $imageFieldName;

  /**
   * The cardinality.
   *
   * @var int
   */
  protected $cardinality;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Drupal\field\FieldConfigInterface definition.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $fieldConfig;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * State storage service service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    $instance->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');
    $instance->configFactory = $container->get('config.factory');
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'image_field_to_media_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, FieldConfigInterface $field_config = NULL) {

    $this->entityTypeId = $field_config->getTargetEntityTypeId();
    $this->bundle = $field_config->getTargetBundle();
    $this->imageFieldName = $field_config->getName();
    $this->cardinality = $field_config->getFieldStorageDefinition()->getCardinality();

    // Field label and field_name.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Label of the Media image field to be created'),
      '#size' => 15,
      '#default_value' => $field_config->getLabel() . ' media',
      '#required' => TRUE,
    ];

    $field_prefix = $this->config('field_ui.settings')->get('field_prefix');

    $form['field_name'] = [
      '#type' => 'machine_name',
      '#field_prefix' => $field_prefix,
      '#size' => 15,
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      // Calculate characters depending on the length of the field prefix
      // setting. Maximum length is 32.
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen($field_prefix),
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$this, 'fieldNameExists'],
      ],
      '#required' => FALSE,
    ];

    // Place the 'translatable' property as an explicit value so that contrib
    // modules can form_alter() the value for newly created fields. By default
    // we create field storage as translatable so it will be possible to enable
    // translation at field level.
    $form['translatable'] = [
      '#type' => 'value',
      '#value' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Proceed'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';

    return $form;
  }

  /**
   * Checks if a field machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'field_name' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the field machine name is taken.
   */
  public function fieldNameExists($value, array $element, FormStateInterface $form_state) {
    // Add the field prefix.
    $field_name = $this->configFactory->get('field_ui.settings')->get('field_prefix') . $value;

    $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($this->entityTypeId);
    return isset($field_storage_definitions[$field_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $media_field_label = $form_state->getValue('label');
    $media_field_prefix = $this->configFactory->get('field_ui.settings')->get('field_prefix');
    $media_field_name = $media_field_prefix . $form_state->getValue('field_name');

    $this->createMediaField($media_field_label, $media_field_name);
    $this->setWeightForDisplays($media_field_name);

    $operations[] = [
      'image_field_to_media_populate_media_field',
      [
        $this->entityTypeId,
        $this->bundle,
        $this->imageFieldName,
        $media_field_name,
      ],
    ];

    $batch = [
      'operations' => $operations,
      'finished' => 'image_field_to_media_batch_finished',
      'init_message' => $this->t('Cloning is starting.'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('Cloning has encountered an error.'),
      'file' => drupal_get_path('module', 'image_field_to_media') . '/image_field_to_media.batch.inc',
    ];

    batch_set($batch);
    // Return to the "Manage Fields" form.
    $form_state->setRedirectUrl(FieldUI::getOverviewRouteInfo($this->entityTypeId, $this->bundle));
  }

  /**
   * Create Entity reference field for storing the Image Media type.
   *
   * @param string $field_label
   *   The label of the field.
   * @param string $field_name
   *   The name of the field.
   */
  private function createMediaField($field_label, $field_name) {
    // Create field storage.
    FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $this->entityTypeId,
      'type' => 'entity_reference',
      'cardinality' => $this->cardinality,
      // Optional to target entity types.
      'settings' => [
        'target_type' => 'media',
      ],
    ])->save();

    // Create field.
    FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $this->entityTypeId,
      'bundle' => $this->bundle,
      'label' => $field_label,
      'cardinality' => $this->cardinality,
      'settings' => [
        'handler' => 'default:media',
        'handler_settings' => [
          'target_bundles' => ['image' => 'image'],
          'sort' => [
            'field' => '_none',
            'direction' => 'ASC',
          ],
          'auto_create' => FALSE,
          'auto_create_bundle' => '',
        ],
      ],
    ])->save();

    // Get the preconfigured field options of the media field.
    $options = $this->fieldTypePluginManager->getPreconfiguredOptions('entity_reference');
    $field_options = $options['media'];

    $widget_id = isset($field_options['entity_form_display']['type']) ? $field_options['entity_form_display']['type'] : NULL;
    $widget_settings = isset($field_options['entity_form_display']['settings']) ? $field_options['entity_form_display']['settings'] : [];
    $formatter_id = isset($field_options['entity_view_display']['type']) ? $field_options['entity_view_display']['type'] : NULL;
    $formatter_settings = isset($field_options['entity_view_display']['settings']) ? $field_options['entity_view_display']['settings'] : [];

    $form_display_options = [];
    if ($widget_id) {
      $form_display_options['type'] = $widget_id;
      if (!empty($widget_settings)) {
        $form_display_options['settings'] = $widget_settings;
      }
    }
    // Make sure the field is displayed in the 'default' form mode (using
    // default widget and settings). It stays hidden for other form modes
    // until it is explicitly configured.
    $this->entityDisplayRepository->getFormDisplay($this->entityTypeId, $this->bundle, 'default')
      ->setComponent($field_name, $form_display_options)
      ->save();

    $view_display_options = [];
    if ($formatter_id) {
      $view_display_options['type'] = $formatter_id;
      if (!empty($formatter_settings)) {
        $view_display_options['settings'] = $formatter_settings;
      }
    }
    // Make sure the field is displayed in the 'default' view mode (using
    // default formatter and settings). It stays hidden for other view
    // modes until it is explicitly configured.
    $this->entityDisplayRepository->getViewDisplay($this->entityTypeId, $this->bundle)
      ->setComponent($field_name, $view_display_options)
      ->save();
  }

  /**
   * Set the same weight for the Media field as the weigth of the Image field.
   *
   * @param string $media_field_name
   *   The name of the created Media field.
   */
  private function setWeightForDisplays(string $media_field_name) {
    $entity_type = $this->entityTypeId;
    $bundle = $this->bundle;
    $image_field_name = $this->imageFieldName;

    // ---------- Set for View displays ------------------------.
    $view_modes = $this->entityDisplayRepository->getViewModeOptionsByBundle($entity_type, $bundle);

    foreach (array_keys($view_modes) as $view_mode) {
      $storage = $this->entityTypeManager->getStorage('entity_view_display');
      $view_display = $storage->load($entity_type . '.' . $bundle . '.' . $view_mode);
      $image_component = $view_display->getComponent($image_field_name);

      // A view display like a "Teaser" may not have the image field. In this
      // case the "$image_component" will be NULL. So, we should check it out.
      if ($image_component) {
        $media_component = $view_display->getComponent($media_field_name);
        $media_component['weight'] = $image_component['weight'];
        $view_display->setComponent($media_field_name, $media_component)->save();
      }
    }

    // ---------- Set for Form displays ------------------------.
    $form_modes = $this->entityDisplayRepository->getFormModeOptionsByBundle($entity_type, $bundle);

    foreach (array_keys($form_modes) as $form_mode) {
      $storage = $this->entityTypeManager->getStorage('entity_form_display');
      $form_display = $storage->load($entity_type . '.' . $bundle . '.' . $form_mode);

      $image_component = $form_display->getComponent($image_field_name);
      $media_component = $form_display->getComponent($media_field_name);
      $media_component['weight'] = $image_component['weight'];
      $form_display->setComponent($media_field_name, $media_component)->save();
    }
  }

}
