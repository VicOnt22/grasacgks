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
  "Test paste filter configuration": browser => {
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

        // Navigate to the create content page to test our plugin.
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

        // Load a fresh node add form. We do this so that the <p> tag doesn't
        // get retained (and excluded from filtering), which would happen if we
        // pasted into the same editor that we copied from. We could also clear
        // the markup from the source editing area before pasting, but starting
        // with a fresh form is simpler.
        .drupalRelativeURL("/node/add/test")
        .waitForElementVisible(".ck-editor__editable")
        .click(".ck-editor__editable")
        // Paste into the visual editor. This will trigger the paste filter behaviour.
        .keys([browser.Keys.CONTROL, "v", browser.Keys.NULL])
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-source-editing-area")
        .assert.valueEquals(
          ".ck-source-editing-area textarea",
          "<p>\n    Before World World\n</p>"
        )

        // Enable the second filter that replaces "Before" with "After".
        .drupalRelativeURL("/admin/config/content/formats/manage/test")
        .waitForElementPresent(
          '[data-drupal-selector="edit-editor-settings-plugin-settings"]'
        )
        .click("link text", "Paste filter")
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-enabled"]'
        )
        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        .waitForElementVisible("[data-drupal-messages]")
        .drupalRelativeURL("/node/add/test")
        .waitForElementVisible(".ck-editor__editable")
        .click(".ck-editor__editable")
        .keys([browser.Keys.CONTROL, "v", browser.Keys.NULL])
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-source-editing-area")
        .assert.valueEquals(
          ".ck-source-editing-area textarea",
          "<p>\n    After World World\n</p>"
        )

        // Update and change the order of the filters to test filter weighting.
        .drupalRelativeURL("/admin/config/content/formats/manage/test")
        .waitForElementPresent(
          '[data-drupal-selector="edit-editor-settings-plugin-settings"]'
        )
        .click("link text", "Paste filter")
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-enabled"]'
        )
        .updateValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-filter-search"]',
          "Hello"
        )
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter"] [data-drupal-selector="tabledrag-toggle-weight"]'
        )
        .updateValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-weight-weight"]',
          0
        )
        .updateValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-weight-weight"]',
          -4
        )
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-actions-add-row"]'
        )
        // @todo Instead of a pause, try to check for staleness of
        //   [data-drupal-messages] and then waitForElementVisible, possibly
        //   via ensure.stalenessOf(element).
        .pause(250)
        .waitForElementVisible(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-2-filter-search"]'
        )
        .assert.valueEquals(
          {
            selector:
              '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
            index: 0
          },
          Number(-4).toString()
        )
        .assert.valueEquals(
          {
            selector:
              '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
            index: 1
          },
          Number(0).toString()
        )
        .assert.valueEquals(
          {
            selector:
              '[data-drupal-selector^="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"][data-drupal-selector$="weight"]',
            index: 2
          },
          // The initial weights should be 1,2,3 before manipulation, so this row should still be 3.
          Number(3).toString()
        )
        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        .waitForElementVisible("[data-drupal-messages]")
        .drupalRelativeURL("/node/add/test")
        .waitForElementVisible(".ck-editor__editable")
        .click(".ck-editor__editable")
        .keys([browser.Keys.CONTROL, "v", browser.Keys.NULL])
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-source-editing-area")
        .assert.valueEquals(
          ".ck-source-editing-area textarea",
          "<p>\n    Before After World\n</p>"
        )

        // Test validation and empty row handling.
        .drupalRelativeURL("/admin/config/content/formats/manage/test")
        .waitForElementPresent(
          '[data-drupal-selector="edit-editor-settings-plugin-settings"]'
        )
        .click("link text", "Paste filter")
        .clearValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-filter-search"]'
        )
        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        .waitForElementVisible("[data-drupal-messages]")
        .assert.textContains(
          "[data-drupal-messages]",
          "The Search expression field is required. To remove the filter, empty both the Search expression and Replacement fields."
        )
        .clearValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-0-filter-replace"]'
        )
        .updateValue(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters-1-filter-replace"]',
          "Aloha"
        )
        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        // @todo Instead of a pause, try to check for staleness of
        //   [data-drupal-messages] and then waitForElementVisible, possibly
        //   via ensure.stalenessOf(element).
        .pause(250)
        .drupalRelativeURL("/node/add/test")
        .waitForElementVisible(".ck-editor__editable")
        .click(".ck-editor__editable")
        .keys([browser.Keys.CONTROL, "v", browser.Keys.NULL])
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-source-editing-area")
        .assert.valueEquals(
          ".ck-source-editing-area textarea",
          "<p>\n    Before Aloha World\n</p>"
        )

        // Test XSS filter.
        .drupalRelativeURL("/node/add/test")
        // Insert test markup via the source editing area.
        .click(".ck-source-editing-button")
        .updateValue(".ck-source-editing-area textarea", "XSS!")
        // Switch to the visual editor, select all the content and copy it.
        .click(".ck-source-editing-button")
        .click(".ck-editor__editable")
        .keys([browser.Keys.CONTROL, "a", browser.Keys.NULL])
        .keys([browser.Keys.CONTROL, "c", browser.Keys.NULL])
        // Paste into the visual editor. This will trigger the paste filter behaviour.
        .drupalRelativeURL("/node/add/test")
        .click(".ck-editor__editable")
        .keys([browser.Keys.CONTROL, "v", browser.Keys.NULL])
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-source-editing-area")
        .assert.valueEquals(
          ".ck-source-editing-area textarea",
          "<p>\n    alert('XSS!!!11')\n</p>"
        )

        // Disable the entire plugin.
        .drupalRelativeURL("/admin/config/content/formats/manage/test")
        .waitForElementPresent(
          '[data-drupal-selector="edit-editor-settings-plugin-settings"]'
        )
        .click("link text", "Paste filter")
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-enabled"]'
        )
        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        .waitForElementVisible("[data-drupal-messages]")
        // Ensure the filter table, help, and "Add another" button are not
        // visible when the plugin is disabled.
        .assert.not.elementPresent('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-filters"]')
        .assert.not.elementPresent('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-help"]')
        .assert.not.elementPresent('[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-actions"]')
        .drupalRelativeURL("/node/add/test")
        .waitForElementVisible(".ck-editor__editable")
        .click(".ck-editor__editable")
        .keys([browser.Keys.CONTROL, "v", browser.Keys.NULL])
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-source-editing-area")
        .assert.valueEquals(
          ".ck-source-editing-area textarea",
          "<p>\n    XSS!\n</p>"
        );
    });
  }
};
