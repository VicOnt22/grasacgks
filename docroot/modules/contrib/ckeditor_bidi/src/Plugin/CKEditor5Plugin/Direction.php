<?php

declare(strict_types=1);

namespace Drupal\ckeditor_bidi\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Direction plugin.
 *
 * @internal
 *   Plugin classes are internal.
 */
class Direction extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['rtl_default' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['rtl_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make right to left option default.'),
      '#default_value' => $this->configuration['rtl_default'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Match the config schema structure at
    // ckeditor5.plugin.ckeditor_bidi_ckeditor5.
    $form_value = $form_state->getValue('rtl_default');
    $form_state->setValue('rtl_default', (bool) $form_value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['rtl_default'] = $form_state->getValue('rtl_default');
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $default = $this->configuration['rtl_default'];

    return [
      'direction' => [
        'rtlDefault' => $default,
      ],
    ];
  }

}
