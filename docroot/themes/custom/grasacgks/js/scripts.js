/**
 * @file
 * Behaviors for the grasacgks theme.
 */

(function ($, _, Drupal) {
  Drupal.behaviors.grasacgks = {
    attach() {
      // grasacgks JavaScript behaviors goes here.

      //make search input in headr shorter
      $("input:first").attr('size', '20');

    },
  };
})(window.jQuery, window._, window.Drupal);
