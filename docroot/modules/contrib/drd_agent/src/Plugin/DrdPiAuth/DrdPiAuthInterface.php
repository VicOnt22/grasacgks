<?php

namespace Drupal\drd_agent\Plugin\DrdPiAuth;

interface DrdPiAuthInterface {

  /**
   * Validate the provided input with the local secrets.
   *
   * Should throw exceptions if a validation failed.
   *
   * @param array $input
   *   The decoded input.
   *
   * @return void
   */
  public function validate($input): void;

}
