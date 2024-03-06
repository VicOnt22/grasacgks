# Filefield to Media Copy

Technical helper module to copy file / image field data to an existing Drupal
Core Media field in the same entity / entities.

**Only for developers (using drush), test carefully and take backups before
use!**


## Installation

Install as you would normally install a contributed Drupal module. For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Module conditions

You have one or multiple entity type bundles with a regular file / image field
(non-media, e.g. from a migration), and you want to migrate its instances
data into a media field.
The media field already exists and is empty or has to be created before using
this module. It should never have any values yet.


## How to Use

- Add a media field, of the desired type, to the entity bundle you want to copy
the file-data to.
- Create a backup.
- Install the module.
- Run `drush filefield-to-media:copy file-field-name media-field-name
media-bundle media-image-field entity-type [entity-bundle] [--no-reuse]`
  - `entity-bundle` is optional
  - `--no-reuse`
    - This disables the reuse of existing media entities when
      available. Use this when you experience issues with the reuse hashes, you
      are not using the default media field configuration or when you need to
      use duplicates with different alt or title text.
- Example: `drush filefield-to-media:copy field_image field_image_media image
field_media_image node`
- Alias is fftm and in the example above you can see the default command values,
so running drush fftm would lead to the same outcome, as the example above.
- Check if everything worked.
- Delete the old file / image field if you don't need it anymore.

