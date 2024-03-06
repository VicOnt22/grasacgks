<?php

declare(strict_types=1);

namespace Drupal\ckeditor_bidi\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

// phpcs:disable Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

/**
 * Provides the CKEditor 4 to 5 upgrade for Drupal's Bidi CKEditor plugins.
 *
 * @CKEditor4To5Upgrade(
 *   id = "ckeditor_bidi",
 *   cke4_buttons = {
 *     "BidiLtr",
 *     "BidiRtl",
 *   },
 *   cke4_plugin_settings = {
 *   },
 *   cke5_plugin_elements_subset_configuration = {
 *   }
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class Direction extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    static $direction;
    switch ($cke4_button) {
      case 'BidiRtl':
      case 'BidiLtr':
        if (!isset($direction)) {
          $direction = TRUE;
          return ['direction'];
        }
        return NULL;

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    throw new \OutOfBoundsException();
  }

}
