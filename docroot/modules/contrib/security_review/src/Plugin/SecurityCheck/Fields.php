<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks for Javascript and PHP in submitted content.
 *
 * @SecurityCheck(
 *   id = "fields",
 *   title = @Translation("Dangerous tags in content exclude list"),
 *   description = @Translation("Checks for Javascript and PHP in submitted content."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Dangerous tags were not found in any submitted content (fields)."),
 *   failure_message = @Translation("Dangerous tags were found in submitted content (fields)."),
 *   help = {
 *     @Translation("Script and PHP code in content does not align with Drupal best practices and may be a vulnerability if an untrusted user is allowed to edit such content. It is recommended you remove such contents or add to exclude list in security review settings page."),
 *   }
 * )
 */
class Fields extends SecurityCheckBase {

  use MessengerTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $result = CheckResult::SUCCESS;
    $findings = [];
    $hushed_findings = [];

    $field_types = [
      'text_with_summary',
      'text_long',
    ];
    $tags = [
      'Javascript' => 'script',
      'PHP' => '?php',
    ];

    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $known_risky_fields = $this->getHushedFields($config['known_risky_fields'] ?? []);

    $entity_type_manager = $this->entityTypeManager;
    $field_manager = $this->entityFieldManager;

    foreach ($field_manager->getFieldMap() as $entity_type_id => $fields) {
      $field_storage_definitions = $field_manager->getFieldStorageDefinitions($entity_type_id);
      foreach ($fields as $field_name => $field) {
        if (!isset($field_storage_definitions[$field_name])) {
          continue;
        }
        $field_storage_definition = $field_storage_definitions[$field_name];
        if (in_array($field_storage_definition->getType(), $field_types)) {
          try {
            $entity = $entity_type_manager->getStorage($entity_type_id)->getEntityType();

            $separator = '_';
            $table = '';
            $id = 'entity_id';
            // We only check entities that are stored in database.
            if (is_a($entity->getStorageClass(), SqlContentEntityStorage::class, TRUE)) {
              if ($field_storage_definition instanceof FieldStorageConfig) {
                $table_mapping = $entity_type_manager->getStorage($entity_type_id)->getTableMapping();
                $table = $table_mapping->getDedicatedDataTableName($field_storage_definition);
              }
              else {
                $translatable = $entity->isTranslatable();
                if ($translatable) {
                  $table = $entity->getDataTable() ?: $entity_type_id . '_field_data';
                }
                else {
                  $table = $entity->getBaseTable() ?: $entity_type_id;
                }
                $separator = '__';
                $id = $entity->getKey('id');
              }
            }

            foreach (array_keys($field_storage_definition->getSchema()['columns']) as $column) {
              $column_name = $field_name . $separator . $column;
              $query = $this->database->select($table, 't')
                ->fields('t', [$id, $column_name])
                ->execute();
              while ($record = $query->fetchAssoc()) {
                foreach ($tags as $vulnerability => $tag) {
                  $column_value = $record[$column_name];
                  $id_value = $record[$id];
                  if (str_contains((string) $column_value, '<' . $tag)) {
                    // Only alert on values that are not known to be safe.
                    $hash = hash('sha256', implode(
                      [
                        $entity_type_id,
                        $id_value,
                        $field_name,
                        $column_value,
                      ]
                    ));
                    if (!array_key_exists($hash, $known_risky_fields)) {
                      // Vulnerability found.
                      $findings[$entity_type_id][$id_value][$field_name][] = $vulnerability;
                      $findings[$entity_type_id][$id_value][$field_name]['hash'] = $hash;
                    }
                    else {
                      $hushed_findings[$entity_type_id][$id_value][$field_name][] = $vulnerability;
                      $hushed_findings[$entity_type_id][$id_value][$field_name]['hash'] = $hash;
                      $hushed_findings[$entity_type_id][$id_value][$field_name]['reason'] = $known_risky_fields[$hash];
                    }
                  }
                }
              }
              unset($query);
            }
          }
          catch (InvalidPluginDefinitionException | PluginNotFoundException) {
            $this->messenger()->addError('Error in fields check, could not load storage for ' . $entity_type_id);
          }
        }
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    $this->createResult($result, $findings, NULL, $hushed_findings);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings) && empty($hushed)) {
      return [];
    }

    $output = $returnString ? '' : [];
    if ($returnString) {
      $output = $this->t('There were some dangerous tags found, see UI for more details.');
    }
    else {
      $paragraphs = [];
      $paragraphs[] = $this->t('The following items potentially have dangerous tags.');

      $items = $this->loopThroughItems($findings);
      $hushed_items = $this->loopThroughItems($hushed, TRUE);
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $items,
        '#hushed_items' => $hushed_items,
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $known_risky_fields = $config['known_risky_fields'] ?? [];
    $form = [];
    $form['known_risky_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hashes'),
      '#description' => $this->t('SHA-256 hashes of entity_type, entity_id, field_name and field content to be skipped in future runs. Enter one value per line, in the format hash|reason.'),
      '#default_value' => implode("\n", $known_risky_fields),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $values): void {
    $hushed['known_risky_fields'] = [];
    if (!empty($values['known_risky_fields'])) {
      $hushed['known_risky_fields'] = preg_split("/\r\n|\n|\r/", $values['known_risky_fields']);
    }
    $this->securityReview->setCheckSettings($this->pluginId, $hushed);
  }

  /**
   * Generates an array of hushed fields.
   *
   * @param array $values
   *   Array of values from config where key is numerical. Turn this into
   *   something more useable.
   *
   * @return array
   *   A key|value array of the hushed values.
   */
  protected function getHushedFields(array $values): array {
    $lines = [];
    foreach ($values as $value) {
      $parts = explode('|', $value);
      $lines[$parts[0]] = $parts[1];
    }
    return $lines;
  }

  /**
   * Attempt to get a good link for the given entity.
   *
   * Falls back on a string with entity type id and id if no good link can
   * be found.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Entity link.
   */
  protected function getEntityLink(EntityInterface $entity): string {
    try {
      if ($entity->hasLinkTemplate('edit-form')) {
        $url = $entity->toUrl('edit-form');
      }
      else {
        $url = $entity->toUrl();
      }
    }
    catch (UndefinedLinkTemplateException | EntityMalformedException) {
      $url = NULL;
    }

    return $url !== NULL ? $url->toString() : ($entity->getEntityTypeId() . ':' . $entity->id());
  }

  /**
   * Loop through the next array of the field findings/hushed_findings.
   *
   * @param array $list
   *   Findings list to loop through.
   * @param bool $additional_info
   *   If there is additional information that should be added to output.
   *
   * @return array
   *   Formatted findings.
   */
  protected function loopThroughItems(array $list, bool $additional_info = FALSE): array {
    $items = [];
    if (!empty($list)) {
      foreach ($list as $entity_type_id => $entities) {
        foreach ($entities as $entity_id => $fields) {
          try {
            $entity = $this->entityTypeManager
              ->getStorage($entity_type_id)
              ->load($entity_id);

            foreach ($fields as $field => $finding) {
              $hash = $finding['hash'];
              unset($finding['hash']);
              if ($additional_info) {
                $items[] = $this->t(
                  '@vulnerabilities found in <em>@field</em> field of <a href=":url">@label</a> Hash ID: @hash | <strong>Reason is @reason</strong>',
                  [
                    '@vulnerabilities' => $finding[0],
                    '@field' => $field,
                    '@label' => $entity->label(),
                    ':url' => $this->getEntityLink($entity),
                    '@hash' => $hash,
                    '@reason' => $finding['reason'],
                  ]
                );
              }
              else {
                $items[] = $this->t(
                  '@vulnerabilities found in <em>@field</em> field of <a href=":url">@label</a> Hash ID: @hash',
                  [
                    '@vulnerabilities' => implode(' and ', $finding),
                    '@field' => $field,
                    '@label' => $entity->label(),
                    ':url' => $this->getEntityLink($entity),
                    '@hash' => $hash,
                  ]
                );
              }
            }
          }
          catch (InvalidPluginDefinitionException | PluginNotFoundException) {
            $this->messenger()->addError('Error in fields check, could not load storage for ' . $entity_type_id);
          }
        }
      }
    }
    return $items;
  }

}
