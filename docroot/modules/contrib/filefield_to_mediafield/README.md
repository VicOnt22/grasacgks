# filefield_to_mediafield
Technical helper module to copy file / image field data to an existing
Drupal Core Media field in the same entity / entities.

*Only for developers, no production use!*
## Condition:
You have an entity type with a regular file / image field (non-media),
for example from a migration and

## How to use:
    Add a media field of the desired type to the entity, you may for example
    simply add "_media" suffix to the old file / image field machine name Create
    a backup!!
    Install the module
    Edit the filefield_to_mediafield.install file and set the CONTENT TYPE =>
    [ SOURCE FIELD NAME => TARGET FIELD NAME ] array. Rename the function
    filefield_to_mediafield_update_800x to filefield_to_mediafield_update_8001
    to let update.php detect the update Run update.php
    Check if everything worked
    Delete the old file / image field if you don't need it anymore
