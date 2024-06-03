CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Additional Plugins
 * Maintainers


INTRODUCTION
------------

A module that adds support for CKEditor 5 plugin Media Embed
to Drupal core's CKEditor.


REQUIREMENTS
------------

Install CKEditor plugins

  * Easiest

    With [Drush](https://www.drush.org/)
    1. [Install Drush](https://www.drush.org/install).
    2. Enable [CKEditor media embed](https://www.drupal.org/project/ckeditor_media_embed) module.
    3. Run `drush ckeditor_media_embed:install`.

INSTALLATION
------------

Install the module per normal https://www.drupal.org/documentation/install/modules-themes/modules-8
then follow the instructions for installing the CKEditor plugins below.


CONFIGURATION
-------------

Install and enable [CKEditor media embed](https://www.drupal.org/project/ckeditor_media_embed) module.

  * WYSIWYG

    - Go to the 'Text formats and editors' configuration page:
      `/admin/config/content/formats`, and for each text format/editor combo
      where you want to embed URLs, do the following:
    - Drag and drop the 'Media Embed' button into the Active toolbar.
    - Enable the 'Convert Oembed tags to media embeds' filter.

  * Field formatter

    The field formatter allows link fields to be rendered via the configured
    oembed service provider.

    - Navigate to "Manage display" for the content type, after adding a "Link"
      field.
    - Select the "Oembed element using CKEditor Media Embed provider" format for
      the link field you wish.


MAINTAINERS
-----------

Current maintainers:
  * Jonathan DeLaigle (grndlvl) - https://www.drupal.org/u/grndlvl
