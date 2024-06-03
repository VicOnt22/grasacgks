<?php

namespace Drupal\script_manager\Entity;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * A script config entity.
 *
 * @ConfigEntityType(
 *   id = "script",
 *   label = @Translation("Script"),
 *   admin_permission = "administer scripts",
 *   handlers = {
 *     "list_builder" = "Drupal\script_manager\ScriptListBuilder",
 *     "access" = "Drupal\script_manager\Entity\ScriptAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\script_manager\Form\ScriptForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   links = {
 *     "delete-form" = "/admin/people/roles/manage/{user_role}/delete",
 *     "edit-form" = "/admin/structure/scripts/manage/{script}",
 *     "collection" = "/admin/structure/scripts",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "snippet",
 *     "position",
 *     "visibility"
 *   }
 * )
 */
class Script extends ConfigEntityBase implements ScriptInterface, EntityWithPluginCollectionInterface {

  /**
   * The script machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * The human readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * The JavaScript snippet.
   *
   * @var string
   */
  protected $snippet;

  /**
   * The position of the script.
   *
   * @var string
   */
  protected $position;

  /**
   * The visibility settings for this block.
   *
   * @var array
   */
  protected $visibility = [];

  /**
   * The condition plugins.
   *
   * @var \Drupal\Core\Condition\ConditionPluginCollection
   */
  protected $visibilityCollection;

  /**
   * {@inheritdoc}
   */
  public function getSnippet(): string {
    return $this->snippet ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPosition(): string {
    return $this->position ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'visibility' => $this->getVisibilityConditions(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibilityConditions(): ConditionPluginCollection {
    if (!isset($this->visibilityCollection)) {
      $this->visibilityCollection = new ConditionPluginCollection($this::getConditionPluginManager(), $this->get('visibility'));
    }
    return $this->visibilityCollection;
  }

  /**
   * Get the condition manager.
   *
   * @return \Drupal\Core\Condition\ConditionManager
   *   The condition manager.
   */
  private static function getConditionPluginManager(): ConditionManager {
    return \Drupal::service('plugin.manager.condition');
  }

}
