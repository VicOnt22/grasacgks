<?php



use \Drupal\node\Entity\Node;
//// Ini example of using hook_post_update_NAME implementations, when both fields are in the same bundle
///  This hook shall be in MYMODULENAME_post_ipdate.php file

// /**
//  * Migrate Article field_old_one > field_new.
//  */
// function change_update_fields_post_update_9001_migrate_article_field(&$sandbox) {
//   _change_update_fields_copy_field_values($sandbox, 'article', 'field_old_one', 'field_new');
// }


/**
 * save all nodes of a bundle.
 */
 function change_update_fields_post_update_9001_saving_heritage(&$sandbox) {
   _change_update_fields_save_bundle($sandbox, 'heritage_item', 20);
 }

/**
 * publish nodes of a heritage_item bundle.
 * we used 9009 for heritage_item and 9010 below for language_item
 */
function change_update_fields_post_update_9009_publish_heritage(&$sandbox) {
  _change_update_fields_publish_bundle($sandbox, 'heritage_item', 20);
}

/**
 * publish nodes of a language_item bundle.
 */
function change_update_fields_post_update_9010_publish_bundle(&$sandbox) {
  _change_update_fields_publish_bundle($sandbox, 'language_item', 50);
}

/**
 * Hook change_update_fields_post_update_N_description
 * set field_geolocation values for nodes of a heritage_item bundle.
 */
function change_update_fields_post_update_9014_geolocation_value(&$sandbox) {
  _change_update_fields_value_for_bundle($sandbox, 'heritage_item', 20);
}

/**
 * Hook change_update_fields_post_update_N_description
 * set field_geolocation values for nodes of a heritage_item bundle.
 */
function change_update_fields_post_update_9015_geolocation_value(&$sandbox) {
  _change_update_fields_value_geo_for_bundle($sandbox, 'heritage_item', 20);
}

/**
 * save all nodes of a bundle.
 */
function change_update_fields_post_update_9016_saving_language(&$sandbox) {
  _change_update_fields_save_bundle($sandbox, 'language_item', 20);
}

// To RESET _NAME
// drush ev "drupal_set_installed_schema_version('[Name of module]', [N*])"
// *N being the update function you want to revert to (the last successful update function)
// in our case:
// drush ev "drupal_set_installed_schema_version('change_update_fields', 9001_saving_heritage)"



//// Some DOCs about updating fields are below

// to update a field value:

//using with drupal Node load and Node save API
//
//  $node=node_load($nid);
//  $node->field_data_field_MYFIELD['und'][0]['value'];
//  node_submit($node);
//  node_save($node);

// or use field_attach_update to update your field value
//  $node = node_load($nid);
//  $field = field_language('node', $node, 'field_your_field');
//  $node->field[$field_language][0]['value'] = 'Your New value';
//  field_attach_update('node', $node);

/* See: https://www.drupal.org/forum/support/module-development-and-code-questions/2017-01-28/issue-updating-link-field */
/* and  https://mycode.blog/lakshmi/how-set-and-update-geolocation-field-programmatically-drupal-89  */
/* and  https://www.jeffgeerling.com/blog/2017/re-save-all-nodes-particular-type-update-hook-drupal-8  */
/* ex with baych https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Extension%21module.api.php/function/hook_update_N/8.2.x  */

// in case we need to change config but that is not possible through UI because data exists:
/* https://www.hook42.com/blog/ride-danger-zone-how-update-drupal-8-field-settings-without-losing-any-data  */

// Just like the majority of people in his poll, I also didn't know that you could shorten this:
//
// $entity->field_name->value = 'foo'
// $entity->field_name->target_id = 123
//
// to this:
//
// $entity->field_name = 'foo'
// $entity->field_name = 123
//
// That's a shorter way to write the same thing, which is good, but I personally prefer using the set() method like this:
//
// $entity->set('field_name', 'foo');
// $entity->set('field_name', 123);
//
// Somehow, this looks and feels much better in my opinion. It's worth mentioning
// that for entity reference fields instead of the entity ID you could set the entity object like this:
//
// $entity->set('field_name', $another_entity);
//
// The same also applies if you don't use the set() method:
//
// $entity->field_name = $another_entity;
//
// What about programmatically updating multi-value fields?
//
//  The multi-value fields are no different. You just have to use arrays. So, instead of this:
//
// $entity->field_name_muti->value = ['foo', 'bar', 'baz'];
// $entity->field_name_multi->target_id = [1, 2, 3]
// $entity->field_name_multi->target_id = [$another_entity1, $another_entity2, $another_entity3]
//
// you can use this:
//
// $entity->field_name_muti = ['foo', 'bar', 'baz'];
// $entity->field_name_multi = [1, 2, 3]
// $entity->field_name_multi = [$another_entity1, $another_entity2, $another_entity3]
//
//Are there any exceptions?
//
//  Sure. You can't use the short way if you have a field type with multiple properties.
//  For example, the Price field in Drupal Commerce has more than one property.
//  You can see the price field's property definition here. To set the value of a Price field you can do this:
//
//  $entity->field_price->number = 10;
// $entity->field_price->currency_code = 'EUR';
//
// In this case, you have to set the value for both the Number and Currency Code properties.
// The alternative way to set the multi-property field is like this:
//
// $entity->field_price = ['number' => 99, 'currency_code' => 'EUR'];
//
// And for multivalue fields:
//
//  $entity->field_prices = [
//    ['number' => 10, 'currency_code' => 'EUR'],
//    ['number' => 99, 'currency_code' => 'EUR'],
//  ];

// Here are some code snippets to update field values programmatically.
/* setTitle('MY NEW TITLE'); */  // This is a special meta field

/* $node->set('FIELD_NAME', 'THIS IS DATA'); */
// // This is a Field added in to the content type // $node->save is not needed with this hook.
// Loading and updating in code (*$node->save is needed here)
/* setTitle('MY NEW TITLE'); */ // This is a special meta field (Title)
/* $node->set('FIELD_NAME', 'THIS IS DATA'); */ // This is a Field added in to the content type
/* $node->save(); */

// Entity Reference
/* $node->FIELD_NAME->target_id = $tid; */

// Multivalue fields
// $node->FIELD_NAME[] = ['target_id' => $tid];

// You can skip the "target_id" property because it is used by default (in this case).
// So this is valid too $node->FIELD_NAME[] = $entity_id;

// How to programmatically update an entity reference field with multiple or single value(s) in drupal 8
//  $tid) {
//   if($index == 0) {
//      $node->set('field_article_term_ref', $tid);
//    } else {
//      $node->get('field_article_term_ref')->appendItem([ 'target_id' => $tid, ]);
//    }
//  }

//  With index == 0 we just check if the node already has a reference attached.
//  If this is true we get the value, and we use the appendItem function.
