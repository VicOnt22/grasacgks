<?php

// we delete all records, older than one week to avoid custom table grow uncontrollable
function delete_tracking_stats_rows(){
  \Drupal::database()
    ->delete('search_api_tracking_stats')
    ->condition('timestamp',  strtotime('-1 week'), '<')
    ->execute();
};
//$query = \Drupal::database()->delete('table');
//$query->condition('some column', 'some condition');
//$query->execute();
