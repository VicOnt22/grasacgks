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
          .click('[name="modules[ckeditor5_paste_filter][enable]"]')
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
  "Test filter weight numbering": browser => {
    browser.drupalLoginAsAdmin(() => {
      browser
        // Create new input format.
        .drupalRelativeURL("/admin/config/content/formats/add")
        .waitForElementVisible('[data-drupal-selector="edit-name"]')
        .updateValue('[data-drupal-selector="edit-name"]', "test")
        .waitForElementVisible("#edit-name-machine-name-suffix") // Wait for machine name to update.
        .click(
          '[data-drupal-selector="edit-editor-editor"] option[value=ckeditor5]'
        )
        .waitForElementPresent(
          '[data-drupal-selector="edit-editor-settings-plugin-settings"]'
        )
        .click("link text", "Paste filter")
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-enabled"]'
        )
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-actions-add-row"]'
        )
        .assert.valueEquals(
          {
            selector:
              '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
            index: 14
          },
          // @todo Consider not hardcoding this, and instead doing something
          //   like find the highest weight value, then check that this is +1
          //   from that value.
          Number(4).toString()
        )

        // Add another row and make sure the weights still make sense.
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-actions-add-row"]'
        )
        .assert.valueEquals(
          {
            selector:
              '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
            index: 14
          },
          // @todo Consider not hardcoding this, and instead doing something
          //   like find the highest weight value, then check that this is +1
          //   from that value.
          Number(4).toString()
        )
        .assert.valueEquals(
          {
            selector:
              '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
            index: 15
          },
          // @todo Consider not hardcoding this, and instead doing something
          //   like find the highest weight value, then check that this is +2
          //   from that value.
          Number(5).toString()
        )

        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        .waitForElementVisible("[data-drupal-messages]")
        .assert.textContains("[data-drupal-messages]", "Added text format");
    });
  }
};
