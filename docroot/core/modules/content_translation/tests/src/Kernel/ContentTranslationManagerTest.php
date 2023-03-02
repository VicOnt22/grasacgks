<?php

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Tests content translation manager.
 *
 * @group content_translation
 */
class ContentTranslationManagerTest extends KernelTestBase {

  /**
   * Exempt from strict schema checking.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'entity_test',
    'language',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installEntitySchema('entity_test_mul');
    $this->contentTranslationManager = $this->container->get('content_translation.manager');
  }

  /**
   * Tests translation enabled for given entity type.
   *
   * @param string $entity_type_id
   *   ID of the entity type.
   * @param string $bundle
   *   Bundle name.
   * @param string $value
   *   True/False Value.
   * @param array $result
   *   The array value.
   */
  public function testTranslationSwitch($entity_type_id, $bundle, $value, array $result) {
    [$is_success, $has_exception] = $result;
    if ($is_success) {
      $this->contentTranslationManager->setEnabled($entity_type_id, $bundle, $value);
      $this->assertEquals($this->contentTranslationManager->isEnabled($entity_type_id, $bundle), $value);
    }
    else {
      if ($has_exception) {
        $this->expectException(PluginNotFoundException::class);
      }
      $this->assertFalse($this->contentTranslationManager->setEnabled($entity_type_id, $bundle, $value));
    }
  }

  /**
   * Data provider for testSetEnabled.
   *
   * @return array
   *   An array of data set.
   */
  public static function setEnabledDataProvider() {
    return [
     ['not_exist', NULL, TRUE, [FALSE, TRUE]],
     [NULL, 'not_exist', TRUE, [FALSE, TRUE]],
     ['not_exist', 'entity_test_mul', TRUE, [FALSE, TRUE]],
     ['entity_test_mul', 'not_exist', TRUE, [FALSE, FALSE]],
     ['entity_test_mul', 'entity_test_mul', TRUE, [TRUE, TRUE]],
     ['entity_test_mul', 'entity_test_mul', FALSE, [TRUE, TRUE]],
    ];
  }

  /**
   * Tests translation enabled for given entity type.
   */
  public function testSetBundleTranslationSettings() {
    $this->assertFalse($this->contentTranslationManager->setBundleTranslationSettings(NULL, NULL, []));
  }

}
