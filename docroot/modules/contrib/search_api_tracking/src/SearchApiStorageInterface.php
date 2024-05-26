<?php

namespace Drupal\search_api_tracking;

/**
 * Search api storage interface.
 */
interface SearchApiStorageInterface {

  /**
   * Saves the tracking to your storage of liking.
   *
   * @param string $keywords
   *   The Search Keywords.
   * @param string $type
   *   Type of search like autocomplete, empty, submit, did_you_mean.
   * @param string $numberOfResults
   *   Number of results for this search.
   * @param string $sortBy
   *   Sort By relevance or date or something else.
   * @param string $sortOrder
   *   Sort order.
   *
   * @return bool
   *   True on save success, FALSE on error.
   */
  public function saveTracking(string $keywords, string $type, string $numberOfResults, string $sortBy, string $sortOrder);

}
