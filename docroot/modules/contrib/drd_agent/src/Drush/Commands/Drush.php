<?php

namespace Drupal\drd_agent\Drush\Commands;

use Drupal\drd_agent\Setup;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Base.
 *
 * @package Drupal\drd_agent
 */
class Drush extends DrushCommands {

  /**
   * The setup service.
   *
   * @var \Drupal\drd_agent\Setup
   */
  protected Setup $setupService;

  /**
   * Drush constructor.
   *
   * @param \Drupal\drd_agent\Setup $setup_service
   *   The setup service.
   */
  public function __construct(Setup $setup_service) {
    parent::__construct();
    $this->setupService = $setup_service;
  }

  /**
   * Return an instance of these Drush commands.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return \Drupal\drd_agent\Drush\Commands\Drush
   *   The instance of Drush commands.
   */
  public static function create(ContainerInterface $container): Drush {
    return new self(
      $container->get('drd_agent.setup'),
    );
  }

  /**
   * Configure this domain for communication with a DRD instance.
   *
   * @param string $token
   *   The token.
   */
  #[CLI\Command(name: 'drd:agent:setup', aliases: ['drd-agent-setup'])]
  #[CLI\Argument(name: 'token', description: 'Base64 and json encoded array of all variables required such that DRD can communicate with this domain in the future.')]
  #[CLI\Usage(name: 'drd:agent:setup', description: 'Configure this domain for communication with a DRD instance.')]
  public function setup(string $token): void {
    $_SESSION['drd_agent_authorization_values'] = $token;
    $this->setupService->execute();
    unset($_SESSION['drd_agent_authorization_values']);
  }

}
