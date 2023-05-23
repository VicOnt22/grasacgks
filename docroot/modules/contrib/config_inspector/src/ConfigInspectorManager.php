<?php

namespace Drupal\config_inspector;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Schema\Ignore;
use Drupal\Core\Config\Schema\Undefined;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Config\Schema\Element;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\TypedData\PrimitiveInterface;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\Core\TypedData\Type\BooleanInterface;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\Core\TypedData\Type\DurationInterface;
use Drupal\Core\TypedData\Type\UriInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Manages plugins for configuration translation mappers.
 */
class ConfigInspectorManager {

  use SchemaCheckTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Constructs a ConfigInspectorManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $discovery_cache
   *   The discovery cache.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, CacheBackendInterface $discovery_cache) {
    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config_manager;

    // Always inspect an up-to-date configuration schema.
    $discovery_cache->deleteMultiple([
      'typed_config_definitions',
      'validation_constraint_plugins',
    ]);
  }

  /**
   * Provides definition of a configuration.
   *
   * @param string $plugin_id
   *   A string plugin ID.
   * @param bool $exception_on_invalid
   *   If TRUE, an invalid plugin ID will throw an exception.
   *
   * @return mixed|void
   *   Plugin definition. NULL if ID invalid and $exception_on_invalid FALSE.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->typedConfigManager->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * Checks if the configuration schema with the given config name exists.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return bool
   *   TRUE if configuration schema exists, FALSE otherwise.
   */
  public function hasSchema($name) {
    return $this->typedConfigManager->hasConfigSchema($name);
  }

  /**
   * Provides configuration data.
   *
   * @param string $name
   *   A string config key.
   *
   * @return array|null
   *   An associative array with configuration data.
   */
  public function getConfigData($name) {
    return $this->typedConfigManager->get($name)->getValue();
  }

  /**
   * Provides configuration schema.
   *
   * @param string $name
   *   A string config key.
   *
   * @return \Drupal\Core\TypedData\TraversableTypedDataInterface
   *   Typed configuration element.
   */
  public function getConfigSchema($name) {
    return $this->typedConfigManager->get($name);
  }

  /**
   * Gets all contained typed data properties as plain array.
   *
   * @param array|object $schema
   *   An array of config elements with key.
   *
   * @return array
   *   List of Element objects indexed by full name (keys with dot notation).
   */
  public function convertConfigElementToList($schema) {
    $list = [];
    foreach ($schema as $key => $element) {
      if ($element instanceof Element) {
        $list[$key] = $element;
        foreach ($this->convertConfigElementToList($element) as $sub_key => $value) {
          $list[$key . '.' . $sub_key] = $value;
        }
      }
      else {
        $list[$key] = $element;
      }
    }
    return $list;
  }

  /**
   * Check schema compliance in configuration object.
   *
   * Only checks compliance with primitive (scalar vs ArrayElement).
   * @see \Drupal\Core\Config\Schema\SchemaCheckTrait::checkValue
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return array|bool
   *   FALSE if no schema found. List of errors if any found. TRUE if fully
   *   valid.
   *
   * @throws \Drupal\Core\Config\Schema\SchemaIncompleteException
   */
  public function checkValues($config_name) {
    $config_data = $this->configFactory->get($config_name)->get();
    return $this->checkConfigSchema($this->typedConfigManager, $config_name, $config_data);
  }

  /**
   * Check schema validatability in configuration object.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return \Drupal\config_inspector\ConfigSchemaValidatability
   *   Config schema validatability.
   */
  public function checkValidatabilityValues($config_name): ConfigSchemaValidatability {
    if ($this->checkValues($config_name) === FALSE) {
      throw new \LogicException("$config_name has no config schema.");
    }

    $config_data = $this->configFactory->get($config_name)->get();
    $typed_config = $this->typedConfigManager->createFromNameAndData($config_name, $config_data);

    $validatability = $this->computeTreeValidatability($typed_config);
    return $validatability;
  }

  /**
   * Computes the validatability of a given typed data tree.
   *
   * @param \Drupal\Core\TypedData\TraversableTypedDataInterface $tree
   *   A tree of Typed Data.
   *
   * @return \Drupal\config_inspector\ConfigSchemaValidatability
   *   The corresponding validatability.
   */
  protected function computeTreeValidatability(TraversableTypedDataInterface $tree) : ConfigSchemaValidatability {
    $validatability = new ConfigSchemaValidatability($tree->getPropertyPath(), $this->getNodeConstraints($tree));
    foreach ($tree as $node) {
      assert($node instanceof TypedDataInterface);
      if ($node instanceof TraversableTypedDataInterface) {
        $validatability->add(self::computeTreeValidatability($node));
      }
      else {
        $validatability->add(new ConfigSchemaValidatability($node->getPropertyPath(), $this->getNodeConstraints($node)));
      }
    }
    return $validatability;
  }

  /**
   * Gets the constraints defined for the given typed data node.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typed_data
   *   A typed data node.
   *
   * @return array
   *   The validation constraints for this node.
   */
  protected function getNodeConstraints(TypedDataInterface $typed_data): array {
    $constraints = $typed_data->getConstraints();

    // If explicit constraints are present, this is validatable.
    if (count($constraints) >= 1) {
      // … except if the only constraint is the PrimitiveTypeConstraint, which
      // only suffices for:
      // - \Drupal\Core\TypedData\Type\BooleanInterface (which can only be
      //   `true` or `false`)
      // - \Drupal\Core\TypedData\Type\UriInterface
      // - \Drupal\Core\TypedData\Type\DateTimeInterface
      // - \Drupal\Core\TypedData\Type\DurationInterface
      // and not any of the following, because it never is the case that a truly
      // arbitrary blob, string or number is allowed:
      // - \Drupal\Core\TypedData\Type\BinaryInterface
      // - \Drupal\Core\TypedData\Type\StringInterface
      // - \Drupal\Core\TypedData\Type\FloatInterface
      // - \Drupal\Core\TypedData\Type\IntegerInterface
      // @see \Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraint
      // @see \Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraintValidator
      // @see \Drupal\Core\TypedData\TypedDataManager::getDefaultConstraints()
      if (count($constraints) === 1
        && $constraints[0] instanceof PrimitiveTypeConstraint
          && (!is_a($typed_data->getDataDefinition()->getClass(), UriInterface::class, TRUE)
          && !is_a($typed_data->getDataDefinition()->getClass(), DateTimeInterface::class, TRUE)
          && !is_a($typed_data->getDataDefinition()->getClass(), DurationInterface::class, TRUE)
          && !is_a($typed_data->getDataDefinition()->getClass(), BooleanInterface::class, TRUE)
        )
      ) {
        return [];
      }
      return $constraints;
    }

    assert($constraints === []);
    return $constraints;
  }

  /**
   * Work-around for bug introduced in #2361539.
   *
   * @see \Drupal\Core\TypedData\ListDataDefinition::getDataType()
   * @see \Drupal\Core\Config\Schema\SequenceDataDefinition
   * @see https://www.drupal.org/project/drupal/issues/2361539
   * @todo Remove this when this module requires a Drupal core version that includes https://www.drupal.org/project/drupal/issues/3361034.
   */
  protected static function getDataType(TypedDataInterface $typed_data) : string {
    $data_type = $typed_data->getDataDefinition()->getDataType();
    if ($data_type === 'list') {
      $data_type = 'sequence';
    }
    return $data_type;
  }

  /**
   * Check schema compliance in configuration object.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return array|bool
   *   FALSE if no schema found. List of errors if any found. TRUE if fully
   *   valid.
   *
   * @throws \Drupal\Core\Config\Schema\SchemaIncompleteException
   */

  public function validateValues($config_name): ConstraintViolationListInterface {
    if ($this->checkValues($config_name) === FALSE) {
      throw new \LogicException("$config_name has no config schema.");
    }

    $config_data = $this->configFactory->get($config_name)->get();
    $typed_config = $this->typedConfigManager->createFromNameAndData($config_name, $config_data);
    $violations = $typed_config->validate();
    return $violations;
  }

  /**
   * Transforms violation constraint list to flat array keyed by property paths.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $list
   *   A validation constraint violations list.
   *
   * @return array
   *   An array with property paths as keys and violation messages as values.
   *
   * @see \Drupal\Tests\ckeditor5\Kernel\CKEditor5ValidationTestTrait
   * @internal
   */
  public static function violationsToArray(ConstraintViolationListInterface $violations): array {
    $actual_violations = [];
    foreach ($violations as $violation) {
      if (!isset($actual_violations[$violation->getPropertyPath()])) {
        $actual_violations[$violation->getPropertyPath()] = (string) $violation->getMessage();
      }
      else {
        // Transform value from string to array.
        if (is_string($actual_violations[$violation->getPropertyPath()])) {
          $actual_violations[$violation->getPropertyPath()] = (array) $actual_violations[$violation->getPropertyPath()];
        }
        // And append.
        $actual_violations[$violation->getPropertyPath()][] = (string) $violation->getMessage();
      }
    }
    return $actual_violations;
  }

}
