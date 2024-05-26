<?php

namespace Drupal\genpass\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate and set new random password for user and display it.
 *
 * @Action(
 *   id = "genpass_set_random_password",
 *   label = @Translation("Set new random password"),
 *   type = "user"
 * )
 */
class UserSetRandomPassword extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * The password generation service.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * Constructs a UserSetRandomPassword Action object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface $password_generator
   *   The password_generator service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PasswordGeneratorInterface $password_generator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->passwordGenerator = $password_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('password_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    if ($account !== FALSE && $account !== NULL) {
      // @see Drupal\user\Plugin\Action\BlockUser as to why original is clone.
      $account->original = clone $account;
      $password = $this->passwordGenerator->generate();
      $account->setPassword($password);
      $account->save();

      $this->messenger()->addStatus($this->t(
        'Password for %account_name is: %new_password', [
          '%account_name' => $account->getDisplayName(),
          '%new_password' => $password,
        ]
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\user\UserInterface $object */
    $access = $object->status->access('edit', $account, TRUE)
      ->andIf($object->access('update', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
