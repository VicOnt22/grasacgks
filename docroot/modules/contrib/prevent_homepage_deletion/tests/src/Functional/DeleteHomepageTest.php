<?php

namespace Drupal\Tests\prevent_homepage_deletion\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of this module.
 *
 * @group prevent_homepage_deletion
 */
class DeleteHomepageTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user', 'prevent_homepage_deletion'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Our node type.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * Homepage node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $pageHome;

  /**
   * Node that is not the homepage.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $pageNotHome;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add a content type.
    $this->contentType = $this->createContentType(['type' => 'page']);
    // Create a first page (homepage).
    $this->pageHome = $this->drupalCreateNode(['type' => 'page']);
    // Set page to be homepage.
    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page.front', '/node/' . $this->pageHome->id())
      ->save(TRUE);
    // Create a second page (not homepage).
    $this->pageNotHome = $this->drupalCreateNode(['type' => 'page']);
  }

  /**
   * Test to check if the homepage can be deleted by various users.
   */
  public function testDeleteHomepage() {
    // Step 1: Log in a user who can delete the homepage.
    $this->drupalLogin(
      $this->createUser([
        'delete any page content',
        'delete_homepage_node',
      ])
    );

    // Step 2: Try to delete the homepage.
    $this->drupalGet('node/' . $this->pageHome->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);

    // Step 3: Logout, and login as user without the permission.
    $this->drupalLogout();
    $this->drupalLogin(
      $this->createUser([
        'delete any page content',
      ])
    );

    // Step 4: Try to delete the homepage.
    $this->drupalGet('node/' . $this->pageHome->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);

    // Step 5: Try to delete the non-homepage.
    $this->drupalGet('node/' . $this->pageNotHome->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);

  }

}
