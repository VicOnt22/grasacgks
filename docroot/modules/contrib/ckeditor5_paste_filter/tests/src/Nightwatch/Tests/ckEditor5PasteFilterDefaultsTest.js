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
  "Test paste filter default settings": browser => {
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
        // Wait for CKEditor 5 settings to be visible.
        .waitForElementVisible(
          '[data-drupal-selector="edit-editor-settings-toolbar"]'
        )
        // Select the Source Editing button.
        .click(".ckeditor5-toolbar-button-sourceEditing")
        // Hit the down arrow key to move it to the toolbar.
        .keys(browser.Keys.ARROW_DOWN)
        // Wait for new source editing vertical tab to be present before continuing.
        .waitForElementVisible(
          "[href*=edit-editor-settings-plugins-ckeditor5-sourceediting]"
        )
        .waitForElementPresent(
          '[data-drupal-selector="edit-editor-settings-plugin-settings"]'
        )
        .click("link text", "Paste filter")
        .click(
          '[data-drupal-selector="edit-editor-settings-plugins-ckeditor5-paste-filter-pastefilter-enabled"]'
        )
        // @todo Instead of a pause, try to check for staleness of
        //   paste filter form possibly via ensure.stalenessOf(element). This
        //   fails sometimes while waiting for AJAX.
        .pause(250)
        .submitForm('[data-drupal-selector="edit-actions-submit"]')
        .waitForElementVisible("[data-drupal-messages]")
        .assert.textContains("[data-drupal-messages]", "Added text format")

        // Create new content type.
        .drupalRelativeURL("/admin/structure/types/add")
        .waitForElementVisible('[data-drupal-selector="edit-name"]')
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
        // Insert "dirty" test markup via the source editing area.
        .waitForElementVisible(".ck-source-editing-button")
        .click(".ck-source-editing-button")
        .waitForElementVisible(".ck-source-editing-area")
        .updateValue(
          ".ck-source-editing-area textarea",
          '<p class="MsoNormal"><a name="OLE_LINK12"><span style="mso-bookmark:OLE_LINK13;">Test <strong>content </strong></span></a><a href="https://www.example.com/"><span style="mso-bookmark:OLE_LINK13;">for</span></a><span style="mso-bookmark:OLE_LINK13;"> the </span><em><span style="mso-bookmark:OLE_LINK13;">paste filter</span></em><span style="mso-bookmark:OLE_LINK13;"> functionality.</span><o:p></o:p></p>'
        )

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
          "<p>\n" +
            '    Test <strong>content </strong><a href="https://www.example.com/">for</a> the <em>paste filter</em> functionality.\n' +
            "</p>"
        );
    });
  }
};
