<?php

/**
 * Implements hook_views_data().
 */
function search_api_tracking_views_data() {
  $data = [];
  // Base data.
  $data['search_api_tracking_stats']['table']['group'] = t('Search API DB Tracking - stats');
  $data['search_api_tracking_stats']['table']['base'] = [
    'title' => t('Search API DB Tracking - stats'),
    'help' => t('Show search api statistics'),
    'field' => 'qid',
  ];

  // Ids to use: numeric, standard, ....
  // Fields.
  $data['search_api_tracking_stats']['timestamp'] = [
    'title' => t('Timestamp'),
    'help' => t('When the request has been executed.'),
    'field' => [
      'id' => 'date',
      'click sortable' => TRUE,
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'argument' => [
      'id' => 'date',
    ],
  ];
  $data['search_api_tracking_stats']['uid'] = [
    'title' => t('User ID'),
    'field' => [
      'id' => 'numeric',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
  ];
  $data['search_api_tracking_stats']['type'] = [
    'title' => t('Type'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'dropdownlist',
    ],
  ];
  $data['search_api_tracking_stats']['keywords'] = [
    'title' => t('Keywords'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
  ];
  $data['search_api_tracking_stats']['language'] = [
    'title' => t('Language'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'dropdownlist',
    ],
  ];
  $data['search_api_tracking_stats']['sort_by'] = [
    'title' => t('Sort by'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'dropdownlist',
    ],
  ];
  $data['search_api_tracking_stats']['sort_order'] = [
    'title' => t('Sort order'),
    'field' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'dropdownlist',
    ],
  ];
  return $data;
}
