<?php

namespace Drupal\Tests\filefield_to_mediafield\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This class provides methods specifically for testing something.
 *
 * @group filefield_to_mediafield
 */
class FilefieldToMediafieldFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'test_page_test',
    'filefield_to_mediafield',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if the module installation, won't break the site.
   */
  public function testInstallation() {
    $session = $this->assertSession();
    $this->drupalGet('<front>');
    $session->statusCodeEquals(200);
  }

  /**
   * Tests if uninstalling the module, won't break the site.
   */
  public function testUninstallation() {
    // Go to uninstallation page an uninstall filefield_to_mediafield:
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/modules/uninstall');
    $session->statusCodeEquals(200);
    $page->checkField('edit-uninstall-filefield-to-mediafield');
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    // Confirm deinstall:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $session->pageTextContains('The selected modules have been uninstalled.');
  }

}
