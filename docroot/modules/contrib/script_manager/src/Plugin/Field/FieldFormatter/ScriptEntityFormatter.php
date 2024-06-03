<?php

declare(strict_types = 1);

namespace Drupal\script_manager\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Formatter for script references.
 *
 * @FieldFormatter(
 *   id = \Drupal\script_manager\Plugin\Field\FieldFormatter\ScriptEntityFormatter::PLUGIN_ID,
 *   label = "Script Formatter",
 *   description = @Translation("A field formatter to render script entities when referenced."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ScriptEntityFormatter extends EntityReferenceFormatterBase {

  public const PLUGIN_ID = 'script_entity';

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $entity) {
      $element[] = [
        '#markup' => new FormattableMarkup($entity->getSnippet(), []),
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'script';
  }

}
