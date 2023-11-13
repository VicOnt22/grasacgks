<?php

namespace Drupal\email_registration;

use Drupal\Core\Password\PasswordGeneratorInterface;

/**
 * The Username Generator service.
 */
final class UsernameGenerator {

  /**
   * The password generator object.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * Constructs an UsernameGenerator object.
   */
  public function __construct(PasswordGeneratorInterface $passwordGenerator) {
    $this->passwordGenerator = $passwordGenerator;
  }

  /**
   * Generates a random prefixed username.
   *
   * @return string
   *   The generated username.
   */
  public function generateRandomUsername(): string {
    return 'email_registration_' . $this->passwordGenerator->generate();
  }

}
