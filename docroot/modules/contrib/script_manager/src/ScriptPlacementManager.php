<?php

declare(strict_types = 1);

namespace Drupal\script_manager;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage script placements.
 */
class ScriptPlacementManager implements ContainerInjectionInterface {

  /**
   * ScriptPlacementManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $scriptStorage
   *   The script entity storage.
   * @param bool $isAdminRoute
   *   Whether the current route is considered an admin route.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   *
   * @internal
   *   There is no backwards compatibility promise for this method. If extending
   *   directly, mark the original service as a service parent, and use service
   *   calls and setter injection for DI and construction.
   */
  final public function __construct(
    protected EntityStorageInterface $scriptStorage,
    protected bool $isAdminRoute,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  final public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')->getStorage('script'),
      $container->get('router.admin_context')->isAdminRoute(),
      $container->get('module_handler'),
    );
  }

  /**
   * Get the rendered scripts for a given position.
   *
   * @param \Drupal\script_manager\Entity\ScriptInterface::POSITION_* $position
   *   A position constant.
   */
  public function getRenderedScriptsForPosition(string $position): array {
    if ($this->isAdminRoute) {
      return [];
    }

    $scripts = $this->scriptStorage->loadByProperties(['position' => $position]);
    $rendered_scripts = [
      '#cache' => [
        'tags' => ['config:script_list'],
      ],
    ];

    foreach ($scripts as $script) {
      $access = $script->access('view', NULL, TRUE);
      $rendered = [
        '#markup' => new FormattableMarkup($script->getSnippet(), []),
        '#access' => $access->isAllowed(),
      ];

      CacheableMetadata::createFromObject($access)
        ->addCacheableDependency($script)
        ->applyTo($rendered);

      $rendered_scripts[] = $rendered;
    }

    $this->moduleHandler->alter('script_manager_scripts', $rendered_scripts);
    return $rendered_scripts;
  }

}
