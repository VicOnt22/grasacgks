<?php

namespace Drupal\views_filter_select\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposes "multiple" items via a dropdown to views module.
 *
 * @ViewsFilter("dropdownlist")
 */
class DropdownList extends InOperator {

  /** @var \Drupal\Core\Database\Connection  */
  private $dbConnection;

  /**
   * Constructs a PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $databaseConnection
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $databaseConnection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dbConnection = $databaseConnection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $values = $this->dbConnection->select($this->table, 'tbl')
      ->fields('tbl', [$this->realField])
      ->execute()
      ->fetchAllKeyed(0,0);

    foreach ($values as $valueKey => $valueName) {
      $values[$valueKey] = $this->t($valueName)->render();
    }

    if (!isset($this->valueOptions)) {
      $this->valueOptions = $values;
    }
    return $this->valueOptions;
  }

}
