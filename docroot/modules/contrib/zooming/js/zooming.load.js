/**
 * @file
 * Provides Zooming loader.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var _id = 'zooming';
  var _idOnce = _id;
  var _mounted = _id + '--on';
  var _dataId = 'data-' + _id;
  var _gallery = '[' + _dataId + '-gallery]:not(.' + _mounted + ')';
  var _isZooming = 'is-' + _id;
  var _isZoomingOpen = _isZooming + '--open';
  var _isZoomingLoading = _isZooming + '--loading';
  var _overlay = _isZooming + '__overlay';
  var _elOverlay = null;

  /**
   * Toggle zooming classes.
   *
   * @param {HTMLElement} target
   *   The target HTML element.
   * @param {Bool} isOpen
   *   If it is zooming.
   */
  function toggleClasses(target, isOpen) {
    _doc.body.classList[isOpen ? 'add' : 'remove'](_isZooming);

    if (!$.isElm(target)) {
      return;
    }

    // @todo configurable selectors.
    // The trouble is Zooming element will be cropped if its parent has
    // `overflow: hidden` rule. This releases the potential unwanted crop.
    // Use your CSS with `.is-zooming` class to release cropping temporarily.
    var sels = ['.box, .grid, .slide, .field__item, .views-row, .content'];
    var cn = null;
    $.each(sels, function (sel) {
      var pel = $.closest(target, sel);
      if ($.isElm(pel)) {
        cn = pel;
        return pel;
      }
    });

    if (!$.isElm(cn)) {
      cn = target.parentElement.parentElement;
    }
    if ($.isElm(cn)) {
      cn.classList[isOpen ? 'add' : 'remove'](_isZooming + '__zoomed');
    }
  }

  /**
   * Zooming utility functions.
   *
   * @param {HTMLElement} elm
   *   The Zooming gallery HTML element.
   */
  function process(elm) {
    var _body = _doc.body;
    var options = {
      // Add higher value as to avoid closing video by scroll by accident.
      scrollThreshold: 210,
      onImageLoading: function (target) {
        toggleClasses(target, true);
        $.addClass(_body, _isZoomingLoading);
      },
      onImageLoaded: function (target) {
        $.removeClass(_body, _isZoomingLoading);
      },
      onOpen: function (target) {
        toggleClasses(target, true);
        $.addClass(_body, _isZoomingOpen);
      },
      onClose: function (target) {
        toggleClasses(target, false);
        $.removeClass(_body, _isZoomingOpen);

        // Fix for erratic image transforms within grids.
        $.removeAttr(target, 'style');
      }
    };

    var zooming = new Zooming(options).listen('.' + _id);
    _elOverlay = zooming.overlay.el;

    // Allows styling the overlay by adding proper CSS class.
    $.addClass(_elOverlay, _overlay);
    $.addClass(elm, _mounted);
  }

  /**
   * Attaches zooming behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.zooming = {
    attach: function (context) {
      $.once(process, _idOnce, _gallery, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(_idOnce, _gallery, context);
      }
    }
  };

})(dBlazy, Drupal, this.document);
