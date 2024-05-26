/**
 * @file
 * Track search API via DB.
 */
(function (Drupal, $) {
  'use strict';

  /**
   * @namespace
   */
  Drupal.searchApiTracking = Drupal.searchApiTracking || {};
  Drupal.searchApiTracking.onceClass = 'onceSATrack';
  // Stops for this current session. For example after autocomplete to not track the submit.
  Drupal.searchApiTrackingSTOP = false;

  /**
   * Drupal behavior.
   */
  Drupal.behaviors.searchApiTracking = {
    attach: function (context) {

      const searchEmpty = document.querySelector(drupalSettings.search_api_tracking.search_empty_selector);
      const selectorAutocomplete = drupalSettings.search_api_tracking.autocomplete_selector;
      const searchForms = drupalSettings.search_api_tracking.search_form_selectors.split(',');

      let firstForm;
      for (let i = 0; i < searchForms.length; i++) {
        const form = document.querySelector(searchForms[i]);
        if (form) {
          firstForm = form;

          break;
        }
      }

      if (firstForm) {
        if (firstForm.classList.contains(Drupal.searchApiTracking.onceClass)) {
          return false;
        }

        firstForm.classList.add(Drupal.searchApiTracking.onceClass);

        // Did you mean.
        // Autocomplete.
        Drupal.behaviors.searchApiTracking.trackDidYouMean();

        // Autocomplete.
        Drupal.behaviors.searchApiTracking.trackAutocomplete(searchForms, selectorAutocomplete);

        // Empty.
        Drupal.behaviors.searchApiTracking.trackEmptySearch(searchEmpty, firstForm);

        // Form submit.
        Drupal.behaviors.searchApiTracking.trackFormSubmit(searchForms);
      }
    },
    trackDidYouMean: function() {
      const didyouMeanLink = document.querySelector(drupalSettings.search_api_tracking.did_you_mean_selector);
      if (didyouMeanLink) {
        didyouMeanLink.addEventListener('click', function(e) {
          Drupal.behaviors.searchApiTracking.sendTracking('did_you_mean', e.target.innerHTML);
        });
      }
    },
    trackEmptySearch: function(searchEmpty, searchFieldPopup) {
      if (searchEmpty) {
        // Quite risky could be more efficient to also expose selector?
        const inputSearchFormWithKeywords= searchFieldPopup.querySelector('input');
        Drupal.behaviors.searchApiTracking.sendTracking('empty', inputSearchFormWithKeywords.value);
      }
    },
    trackAutocomplete: function(searchForms, selectorAutoComplete) {
      for (let i = 0; i < searchForms.length; i++) {
        const searchForm = document.querySelector(searchForms[i]);
        if (!searchForm) {
          continue;
        }

        let searchField = searchForm.querySelector(selectorAutoComplete);

        $(searchField).on('autocompleteselect', function(event, ui) {
          Drupal.behaviors.searchApiTracking.trackAutocompleteSendValues(ui.item.value);
          Drupal.searchApiTrackingSTOP = true;
        });
      }
    },
    trackAutocompleteSendValues: function(value) {
      Drupal.behaviors.searchApiTracking.sendTracking('autocomplete', value);
    },
    trackFormSubmit: function(searchForms) {
      for (let i = 0; i < searchForms.length; i++) {
        const searchForm = document.querySelector(searchForms[i]);
        if (!searchForm) {
          continue;
        }
        searchForm.addEventListener('submit', function(event) {
          if (!Drupal.searchApiTrackingSTOP) {
            Drupal.behaviors.searchApiTracking.sendTracking('submit', event.target.querySelector('input').value);
            Drupal.searchApiTrackingSTOP = true;
          }
        });
      }
    },
    sendTracking: function(type, searchedString) {
      const sortBy = document.getElementById(drupalSettings.search_api_tracking.sort_by_selector);
      const sortOrder = document.getElementById(drupalSettings.search_api_tracking.sort_order_selector);

      let sortByValue = '';
      let sortOrderValue = '';

      if (sortBy) {
        sortByValue = sortBy.value;
      }
      if (sortOrder) {
        sortOrderValue = sortOrder.value;
      }

      let xhr = new XMLHttpRequest();
      xhr.open('POST', Drupal.url('tracking/search-api-tracking'), true);
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.send(JSON.stringify({
        'type': type,
        'searchedString': searchedString,
        'sortBy': sortByValue,
        'sortOrder': sortOrderValue,
      }));
    }
  };

})(Drupal, jQuery);
