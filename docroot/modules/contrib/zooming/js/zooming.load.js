/**
 * @file
 * Provides Zooming loader.
 */

(function (Drupal, _db, window, document) {

  'use strict';

  var _mounted = 'zooming--on';

  /**
   * Toggle zooming classes.
   *
   * @param {HTMLElement} target
   *   The target HTML element.
   * @param {Bool} isOpen
   *   If it is zooming.
   */
  function toggleClasses(target, isOpen) {
    document.body.classList[isOpen ? 'add' : 'remove']('is-zooming');

    // @todo configurable selectors.
    // The trouble is Zooming element will be cropped if its parent has
    // `overflow: hidden` rule. This releases the potential unwanted crop.
    // Use your CSS with `.is-zooming` class to release cropping temporarily.
    var sels = ['.box, .grid, .slide, .field__item, .views-row, .content'];
    _db.forEach(sels, function (sel) {
      var pel = _db.closest(target, sel);
      if (pel !== null) {
        pel.classList[isOpen ? 'add' : 'remove']('is-zooming__zoomed');
      }
    });
  }

  /**
   * Zooming utility functions.
   *
   * @param {HTMLElement} elm
   *   The Zooming HTML element.
   */
  function doZooming(elm) {
    var options = {
      // Add higher value as to avoid closing video by scroll by accident.
      scrollThreshold: 210,
      onImageLoading: function (target) {
        toggleClasses(target, true);
        document.body.classList.add('is-zooming--loading');
      },
      onImageLoaded: function (target) {
        document.body.classList.remove('is-zooming--loading');
      },
      onOpen: function (target) {
        toggleClasses(target, true);
        document.body.classList.add('is-zooming--open');
      },
      onClose: function (target) {
        toggleClasses(target, false);
        document.body.classList.remove('is-zooming--open');

        // Fix for erratic image transforms within grids.
        target.removeAttribute('style');
      }
    };

    var zooming = new Zooming(options).listen('.zooming');

    // Allows styling the overlay by adding proper CSS class.
    zooming.overlay.el.classList.add('is-zooming__overlay');
    elm.classList.add(_mounted);
  }

  /**
   * Attaches zooming behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.zooming = {
    attach: function (context) {

      // Weirdo: context may be null after Colorbox close.
      context = context || document;

      // jQuery may pass its object as non-expected context identified by length.
      context = 'length' in context ? context[0] : context;
      context = context instanceof HTMLDocument ? context : document;

      // @todo replace with core/once at 2.x when dropping Blazy 1.x.
      var items = context.querySelectorAll('[data-zooming-gallery]:not(.' + _mounted + '), .zooming-gallery:not(.' + _mounted + ')');
      if (items.length) {
        _db.once(_db.forEach(items, doZooming));
      }

    }
  };

})(Drupal, dBlazy, this, this.document);
