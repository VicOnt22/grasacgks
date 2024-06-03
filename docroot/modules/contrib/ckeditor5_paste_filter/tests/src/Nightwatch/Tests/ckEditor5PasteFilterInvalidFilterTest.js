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
  "Test invalid filter": browser => {
    browser.drupalLoginAsAdmin(() => {
      // Create new content type.
      browser
        .drupalRelativeURL("/admin/structure/types/add")
        .updateValue('[data-drupal-selector="edit-name"]', "test")
        .waitForElementVisible("#edit-name-machine-name-suffix") // Wait for machine name to update.
        .submitForm('input[type="submit"]')
        .waitForElementVisible("[data-drupal-messages]")
        .assert.textContains(
          "[data-drupal-messages]",
          "The content type test has been added"
        )

        // Navigate to the create content page to get test content onto the
        // clipboard.
        .drupalRelativeURL("/node/add/test")
        // Insert test markup via the source editing area.
        .waitForElementVisible(".ck-source-editing-button")
        .click(".ck-source-editing-button")
        .updateValue(".ck-source-editing-area textarea", "Before Hello World")
        // Switch to the visual editor, select all the content and copy it.
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-editor__editable")
        .click(".ck-editor__editable")
        .keys([browser.Keys.CONTROL, "a", browser.Keys.NULL])
        .keys([browser.Keys.CONTROL, "c", browser.Keys.NULL])

        // Add an invalid filter (bad JavaScript Regular expression).
        .drupalRelativeURL("/admin/config/content/formats/manage/test")
        .waitForElementPresent(
          '[data-drupal-selector="edit-editor-settings-plugin-settings"]'
        )
        .click("link text", "Paste filter")
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-actions-add-row"]'
        )
        // @todo Instead of a pause, try to check for staleness of
        //   [data-drupal-messages] and then waitForElementVisible, possibly
        //   via ensure.stalenessOf(element).
        .pause(250)
        .waitForElementVisible(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-3-filter-search"]'
        )
        .updateValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-3-filter-search"]',
          "invalid/\\"
        )
        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        .waitForElementVisible("[data-drupal-messages]")

        // Test that an invalid filter regex doesn't break pasting.
        .drupalRelativeURL("/node/add/test")
        .waitForElementVisible(".ck-editor__editable")
        .click(".ck-editor__editable")
        .keys([browser.Keys.CONTROL, "v", browser.Keys.NULL])
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-source-editing-area")
        // @todo Additionally assert that the console error is present
        //   (if possible).
        .assert.valueEquals(
          ".ck-source-editing-area textarea",
          "<p>\n    Before World World\n</p>"
        );
    });
  }
};
