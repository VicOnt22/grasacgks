<?php

declare(strict_types = 1);

namespace Drupal\Tests\script_manager\Functional;

use Drupal\Core\Url;
use Drupal\script_manager\Entity\Script;
use Drupal\script_manager\Entity\ScriptInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the script manager admin UI.
 *
 * @group script_manager
 */
final class ScriptManagerAdminTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'script_manager',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test the admin UI.
   */
  public function testAdminUi(): void {
    $this->drupalLogin($this->drupalCreateUser([
      'administer scripts',
    ]));

    $collectionUrl = Url::fromRoute('entity.script.collection');
    $this->drupalGet($collectionUrl);
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('There are no script entities yet');
    $assert->linkExists('Add Script');
    $this->clickLink('Add Script');
    $assert->statusCodeEquals(200);

    $this->submitForm([
      'label' => 'Foo',
      'id' => 'foo',
      'position' => ScriptInterface::POSITION_TOP,
      'snippet' => 'bar',
    ], 'Save');

    $script = Script::load('foo');
    $assert->addressEquals($collectionUrl->toString());
    $assert->pageTextContains('Foo');
    $assert->linkByHrefExists($script->toUrl('edit-form')->toString());
    $assert->linkByHrefExists($script->toUrl('delete-form')->toString());

    $this->clickLink('Edit');
    $assert->fieldValueEquals('label', 'Foo');
    $assert->fieldValueEquals('position', ScriptInterface::POSITION_TOP);
    $assert->fieldValueEquals('snippet', 'bar');

    $this->drupalGet($script->toUrl('delete-form'));
    $this->submitForm([], 'Delete');
    $assert->pageTextContains('The script Foo has been deleted.');
    $assert->pageTextContains('There are no script entities yet');
  }

}
