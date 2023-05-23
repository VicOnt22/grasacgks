<?php

namespace Drupal\ckeditor_paste_filter\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Paste Filter" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ckeditor_paste_filter",
 *   label = @Translation("Paste Filter"),
 *   module = "ckeditor_paste_filter"
 * )
 */
class PasteFilter extends CKEditorPluginBase implements CKEditorPluginContextualInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return \Drupal::service('extension.list.module')->getPath('ckeditor_paste_filter') . '/js/plugins/paste-filter/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    return TRUE;
  }

}
