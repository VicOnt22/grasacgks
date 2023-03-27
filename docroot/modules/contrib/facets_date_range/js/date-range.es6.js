/**
 * @file
 * Provides the Date Range Picker functionality.
 */
(function ($) {
  Drupal.facets = Drupal.facets || {};

  Drupal.behaviors.facetsDateRange = {
    attach(context, settings) {
      function toTimestamp(strDate) {
        const datum = Date.parse(strDate);
        return datum / 1000;
      }

      function autoSubmit() {
        const $this = $(this);
        const facetId = $this
          .parents(".facets-widget-date_range")
          .find("ul")
          .attr("data-drupal-facet-id");

        // Get url from target facet.
        const daterange = settings.facets.daterange[facetId];

        const min = toTimestamp($(`input[id=${facetId}_min]`).val()) || "";
        const max = toTimestamp($(`input[id=${facetId}_max]`).val()) || "";

        window.location.href = daterange.url
          .replace("__date_range_min__", min)
          .replace("__date_range_max__", max);
      }

      // Support both the calendar widget and keyboard entry.
      // @see https://stackoverflow.com/questions/40762549/html5-input-type-date-onchange-event
      $("input.facet-date-range", context).on("change", autoSubmit);
      $("input.facet-date-range", context).on("keypress", function (e) {
        $(this).off("change blur");
        $(this).on("blur", autoSubmit);
        if (e.keyCode === 13) {
          autoSubmit();
        }
      });
    },
  };
})(jQuery);
