<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor_bidi\Kernel;

use Drupal\Tests\ckeditor5\Kernel\CKEditor4to5UpgradeCompletenessTest;

/**
 * @covers \Drupal\ckeditor_bidi\Plugin\CKEditor4To5Upgrade\Direction
 * @group ckeditor_bidi
 * @group ckeditor5
 * @internal
 * @requires module ckeditor5
 */
class UpgradePathCompletenessTest extends CKEditor4to5UpgradeCompletenessTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ckeditor_bidi'];

}
