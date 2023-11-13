<?php

namespace Drupal\custom_entity_id\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * The CustomEntityIdSettingsForm contain custom_entity_id config settings.
 *
 * @package Drupal\custom_entity_id\Form
 */
class CustomEntityIdSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * CustomEntityIdSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory for the form.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
//    parent::__construct($config_factory, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_entity_id.settings',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'custom_entity_id_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_entity_id.settings');

    $arr_entity_type = array_keys($this->entityTypeManager->getDefinitions());
    $arr_fieldable_entity = [];

    foreach ($arr_entity_type as $entity_type_id) {
      if (method_exists($this->entityTypeManager->getDefinition($entity_type_id)->getOriginalClass(), 'hasField')) {
        $arr_fieldable_entity[$entity_type_id]['label'] = $this->entityTypeManager->getDefinition($entity_type_id)->getBundleLabel();
        $arr_entity_type_bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
        foreach ($arr_entity_type_bundles as $bundle_key => $bundle_value) {
          $arr_fieldable_entity[$entity_type_id]['bundle'][$bundle_key] = is_object($bundle_value['label']) ? $bundle_value['label']->__toString() : $bundle_value['label'];
        }
      }
    }

    $arr_selected_chk = unserialize($config->get('fieldable_entity'), ['allowed_classes' => FALSE]);

    foreach ($arr_fieldable_entity as $key => $fieldable_entity) {
      if (isset($fieldable_entity['bundle'])) {
        $form['fieldable_entity'][$key] = [
          '#type' => 'checkboxes',
          '#options' => $fieldable_entity['bundle'],
          '#title' => $fieldable_entity['label'],
          '#default_value' => $arr_selected_chk[$key],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $arr_fieldable_entity = array_keys($form['fieldable_entity']);
    $arr_selected_chk = [];

    foreach ($arr_fieldable_entity as $fieldable_entity) {
      if (!str_contains($fieldable_entity, '#')) {
        $arr_selected_chk[$fieldable_entity] = $form_state->getValue($fieldable_entity);
      }
    }

    $this->config('custom_entity_id.settings')
      ->set('fieldable_entity', serialize($arr_selected_chk))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
