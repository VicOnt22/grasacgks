<?php

namespace Drupal\Tests\tour_ui\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Tour UI.
 *
 * @group Tour UI
 */
class TourUITest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['tour_ui'];


  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Returns info for the test.
   *
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Tour UI',
      'description' => 'Tests the Tour UI.',
      'group' => 'Tour',
    ];
  }

  /**
   * Tests the listing and editing of a tour.
   */
  public function testUi() {
    $this->drupalLogin($this->drupalCreateUser(['administer tour']));

    $this->listTest();
    $this->editTest();
    $this->tipTest();
  }

  /**
   * Tests the listing of a tour.
   */
  protected function listTest() {
    // Assert that two test tours are shown.
    $this->drupalGet('admin/config/user-interface/tour');
    $elements = $this->xpath('//table/tbody/tr');
    $this->assertEquals(3, count($elements));

    // The first column contains the id.
    // jQuery('table > tbody > tr:first > td:first').text() === tour-test
    // jQuery('table > tbody > tr:first').hasClass('tour-test')
    // jQuery('table > tbody > tr.tip-edit > td:first').text()
    $elements = $this->xpath('//table/tbody/tr[contains(@class, :class)]/td[1]', [':class' => 'tip_edit']);
    $this->assertSame($elements[0]->getText(), 'tip_edit');

    // The second column contains the title.
    $elements = $this->xpath('//table/tbody/tr[contains(@class, :class)]/td[3]', [':class' => 'tip_edit']);
    $this->assertSame($elements[0]->getText(), 'Edit tip');

    // phpcs:disable
    // The third column contains the routes.
    // Running "jQuery('table > tbody > tr.tour-test > td:nth(2)').html()"
    // results in "> <div class="tour-routes">tour_test.1<br>tour_test.3</div>".
    // FIX ME: trying to solve this failed. See #3009733 for further information.
    // $elements = $this->xpath('//table/tbody/tr[contains(@class, :class)]/td/div[contains(@class, :class-routes)]', [':class' => 'tour-test-1', ':class-routes' => 'tour-routes']);
    // $routes = strpos($elements[0]->getText(), 'tour_test.1') !== FALSE;
    // $this->assertTrue($routes, 'Route contains "tour_test.1".');.
    // phpcs:enable

    // The fifth column contains the number of tips.
    $elements = $this->xpath('//table/tbody/tr[contains(@class, :class)]/td[5]', [':class' => 'tip_edit']);
    $this->assertSame($elements[0]->getText(), '4', 'Tour UI - tip_edit has 4 tips.');
  }

  /**
   * Tests the editing of a tour.
   */
  protected function editTest() {
    // Create a new tour. Ensure that it comes before the test tours.
    $edit = [
      'label' => 'a' . $this->randomString(),
      'id' => strtolower($this->randomMachineName()),
      'module' => strtolower($this->randomMachineName()),
    ];
    $this->drupalGet('admin/config/user-interface/tour/add');
    $this->submitForm($edit, 'Save');
    $this->assertSession()
      ->responseContains($this->t('The tour %tour has been created.', ['%tour' => $edit['label']]));

    $elements = $this->xpath('//table/tbody/tr');
    $this->assertEquals(1, count($elements));

    // Edit and re-save an existing tour.
    $this->assertSession()->titleEquals('Edit tour | Drupal');

    $this->submitForm([], 'Save');
    $this->assertSession()
      ->responseContains($this->t('The tour %tour has been updated.', ['%tour' => $edit['label']]));

    // Reorder the tour tips.
    $this->drupalGet('admin/config/user-interface/tour/manage/tip_edit');
    $weights = [
      'tips[tour-page][weight]' => '2',
      'tips[tour-label][weight]' => '1',
    ];
    $this->submitForm($weights, 'Save');
    $elements = $this->xpath('//tr[contains(@class, "draggable")]/td[contains(text(), "Label")]');
    $this->assertEquals(1, count($elements), 'Found odd tip "Label".');

    $weights = [
      'tips[tour-page][weight]' => '1',
      'tips[tour-label][weight]' => '2',
    ];
    $this->submitForm($weights, 'Save');
    $elements = $this->xpath('//tr[contains(@class, "draggable")]/td[contains(text(), "Tour edit")]');
    $this->assertEquals(1, count($elements), 'Found odd tip "Tour edit".');

    $this->drupalGet('admin/config/user-interface/tour/add');

    // Attempt to create a duplicate tour.
    $this->submitForm($edit, 'Save');
    $this->assertSession()
      ->responseContains($this->t('The machine-readable name is already in use. It must be unique.'));

    // Delete a tour.
    $this->drupalGet('admin/config/user-interface/tour/manage/' . $edit['id']);
    $this->clickLink('Delete');
    $this->assertSession()
      ->responseContains($this->t('Are you sure you want to delete the tour %tour?', ['%tour' => $edit['label']]));
    $this->submitForm([], 'Delete');
    $elements = $this->xpath('//table/tbody/tr');
    $this->assertEquals(3, count($elements));
    $this->assertSession()
      ->responseContains($this->t('The tour %tour has been deleted.', ['%tour' => $edit['label']]));
  }

  /**
   * Tests the add/edit/delete of a tour tip.
   */
  protected function tipTest() {
    // Create a new tour for tips to be added to.
    $edit = [
      'label' => 'a' . $this->randomString(),
      'id' => strtolower($this->randomMachineName()),
      'module' => $this->randomString(),
      'routes' => '',
    ];
    $this->drupalGet('admin/config/user-interface/tour/add');
    $this->submitForm($edit, 'Save');

    $this->assertSession()
      ->responseContains($this->t('The tour %tour has been created.', ['%tour' => $edit['label']]));

    // Add a new tip.
    $tip = [
      'new' => 'text',
    ];
    $this->drupalGet('admin/config/user-interface/tour/manage/' . $edit['id']);
    $this->submitForm($tip, 'Add');
    $tip = [
      'label' => 'a' . $this->randomString(),
      'id' => 'tour-ui-test-image-tip',
      'body' => $this->randomString(),
    ];
    $this->submitForm($tip, 'Save');
    $elements = $this->xpath('//tr[contains(@class, "draggable")]/td[contains(text(), "' . $tip['label'] . '")]');
    $this->assertEquals(1, count($elements), 'Found tip "' . $tip['label'] . '".');

    // Edit the tip.
    $tip_id = $tip['id'];
    $tip['label'] = 'a' . $this->randomString();
    $this->drupalGet('admin/config/user-interface/tour/manage/' . $edit['id'] . '/tip/edit/' . $tip_id);
    $this->submitForm($tip, 'Save');

    $elements = $this->xpath('//tr[contains(@class, "draggable")]/td[contains(text(), "' . $tip['label'] . '")]');
    $this->assertEquals(1, count($elements), 'Found tip "' . $tip['label'] . '".');
    $this->drupalGet('admin/config/user-interface/tour/manage/' . $edit['id'] . '/tip/edit/' . $tip_id);

    $this->assertSession()->titleEquals('Edit tip | Drupal');

    // Delete the tip.
    $this->clickLink('Delete');
    $this->submitForm([], 'Confirm');
    $elements = $this->xpath('//tr[@class=:class and ./td[contains(., :text)]]', [
      ':class' => 'draggable odd',
      ':text' => $tip['label'],
    ]);
    $this->assertNotEquals(count($elements), 1, 'Did not find tip "' . $tip['label'] . '".');
  }

}
