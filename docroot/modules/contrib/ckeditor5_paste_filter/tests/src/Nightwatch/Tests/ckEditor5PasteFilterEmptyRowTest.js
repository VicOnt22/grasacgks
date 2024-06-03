module.exports = {
  "@tags": ["ckeditor5_paste_filter"],
  before(browser) {
    browser
      .drupalInstall({
        installProfile: "minimal"
      })
      .drupalLoginAsAdmin(() => {
        browser
          // Enable required modules.
          // @todo Refactor once
          //   https://www.drupal.org/project/drupal/issues/3264940 lands.
          .drupalRelativeURL("/admin/modules")
          .updateValue(
            'form.system-modules [data-drupal-selector="edit-text"]',
            "ckeditor5_paste_filter_test"
          )
          .waitForElementVisible(
            `form.system-modules [name="modules[ckeditor5_paste_filter_test][enable]"]`,
            10000
          )
          .click('[name="modules[ckeditor5_paste_filter_test][enable]"]')
          .submitForm('input[type="submit"]') // Submit module form.
          .waitForElementVisible(
            '.system-modules-confirm-form input[value="Continue"]'
          )
          .submitForm('input[value="Continue"]') // Confirm installation of dependencies.
          .waitForElementVisible(".system-modules", 10000);
      });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  "Test empty row behavior": browser => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL("/admin/config/content/formats/manage/test")
        .waitForElementPresent(
          '[data-drupal-selector="edit-editor-settings-plugin-settings"]'
        )
        .click("link text", "Paste filter")
        // Ensure there is no empty row when filters are already configured.
        // @todo Consider not hardcoding the '3' here, similarly to the weight test.
        .assert.not.elementPresent('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-3-filter-search"]')
        // Clear all the filter values and save.
        .clearValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-filter-search"]'
        )
        .clearValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-filter-replace"]'
        )
        .clearValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-filter-search"]'
        )
        .clearValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-filter-replace"]'
        )
        .clearValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-2-filter-search"]'
        )
        .clearValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-2-filter-replace"]'
        )
        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        .waitForElementVisible("[data-drupal-messages]")
        // Ensure there is one empty row when filters aren't configured yet.
        .assert.valueEquals('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-filter-search"]', '')
        .assert.valueEquals('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-filter-replace"]', '')
        .assert.not.elementPresent('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-filter-search"]');
    });
  }
};
