<?php

namespace Drupal\email_registration\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Auto username rename bulk action.
 *
 * @Action(
 *   id = "email_registration_update_username",
 *   label = @Translation("Update username (from email_registration)"),
 *   type = "user",
 * )
 */
class UpdateUsernameAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($account = NULL) {
    // Rename the given user:
    if (!empty($account) && $account instanceof UserInterface) {
      // Give the user a temporary 'email_registration_' username, so that
      // our "email_registration_user_presave()" hook can execute:
      $account->setUsername(\Drupal::service('email_registration.username_generator')->generateRandomUsername())->save();
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
