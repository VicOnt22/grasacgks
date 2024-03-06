<?php

namespace Drupal\Tests\genpass\FunctionalJavascript;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests functionality of the Generate Password module.
 *
 * @group genpass
 */
class GenpassTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'toolbar',
    'genpass',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with "administer account settings" .
   *
   * And "administer users" permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp():void {
    parent::setUp();

    $permissions = [
      'access toolbar',
      'view the administration theme',
      'administer account settings',
      'administer users',
    ];

    $this->webUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->webUser);
  }

  /**
   * Test Generate Password configs and create users by admin.
   */
  public function testGenpassConfigsAndCreateUsersByAdmin() {

    // Configure Account settings with Generate Password options.
    $this->drupalGet('admin/config/people/accounts');
    $this->assertSession()->pageTextContains('Account settings');
    $this->assertSession()->pageTextContains('Generate Password - User Account Registration');
    $this->assertSession()->pageTextContains('User password entry');
    $this->assertSession()->pageTextContains('Admin password entry');
    $this->assertSession()->pageTextContains('Generated password length');
    $this->assertSession()->pageTextContains('Generated password display');

    $this->getSession()->getPage()->selectFieldOption('genpass_mode', '2');
    $this->getSession()->getPage()->pressButton('Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Create the test_authenticated user.
    $this->drupalGet('admin/people/create');
    $this->assertSession()->pageTextContains('Add user');
    $this->getSession()->getPage()->fillField('mail', 'authenticated.test@drupal.org');
    $this->getSession()->getPage()->fillField('Username', 'test_authenticated');
    $this->getSession()->getPage()->pressButton('Create new account');
    $this->assertSession()->pageTextContains('Since you did not provide a password, it was generated automatically for this account.');
    $this->assertSession()->pageTextContains('Created a new user account for test_authenticated. No email has been sent.');
  }

  /**
   * Test Generate Password hide password field functionality.
   */
  public function testGenpassHidePasswordField() {

    // Allow admins to set passwords.
    $this->drupalGet('admin/config/people/accounts');
    $this->assertSession()->pageTextContains('Admin password entry');

    $this->getSession()->getPage()->selectFieldOption('genpass_admin_mode', '1');
    $this->getSession()->getPage()->pressButton('Save configuration');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Create the test_authenticated user.
    $this->drupalGet('admin/people/create');
    $this->assertSession()->pageTextContains('Provide a password for the new account in both fields.');

    // Disallow admins to set passwords.
    $this->drupalGet('admin/config/people/accounts');
    $this->getSession()->getPage()->selectFieldOption('genpass_admin_mode', '2');
    $this->getSession()->getPage()->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Create the test_authenticated user.
    $this->drupalGet('admin/people/create');
    $this->assertSession()->pageTextNotContains('Provide a password for the new account in both fields.');
    $this->getSession()->getPage()->fillField('mail', 'authenticated.test@drupal.org');
    $this->getSession()->getPage()->fillField('Username', 'test_authenticated');
    $this->getSession()->getPage()->pressButton('Create new account');
    $this->assertSession()->pageTextContains('Since you did not provide a password, it was generated automatically for this account.');
    $this->assertSession()->pageTextContains('Created a new user account for test_authenticated. No email has been sent.');
  }

}
