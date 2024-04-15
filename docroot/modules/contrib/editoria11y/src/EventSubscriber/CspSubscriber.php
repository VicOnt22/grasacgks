<?php /** @noinspection PhpUndefinedNamespaceInspection,PhpUndefinedClassInspection */ // phpcs:disable

declare(strict_types=1);

namespace Drupal\editoria11y\EventSubscriber;

/**
 * Sets CSP compatibility.
 *
 */

use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\csp\Csp;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter CSP policy for Editoria11y.
 *
 */
class CspSubscriber implements EventSubscriberInterface {

  /**
   * The Library Dependency Resolver Service.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolverInterface
   *
   * @noinspection PhpStanGlobal
   */
  private LibraryDependencyResolverInterface $libraryDependencyResolver;

  /**
   * CspSubscriber constructor.
   *
   * @param \Drupal\Core\Asset\LibraryDependencyResolverInterface $libraryDependencyResolver
   *   The Library Dependency Resolver Service.
   */
  public function __construct(LibraryDependencyResolverInterface $libraryDependencyResolver) {
    $this->libraryDependencyResolver = $libraryDependencyResolver;
  }

  /**
   * Subscribe to CSP policy events.
   *
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (!class_exists(CspEvents::class)) {
      return [];
    }

    return [
      CspEvents::POLICY_ALTER => 'onCspPolicyAlter',
    ];
  }

  /**
   * Alter CSP policy to allow unsafe-inline when editorially is shown.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The Policy Alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent): void { // @phpstan-ignore-line
    $policy = $alterEvent->getPolicy();
    $response = $alterEvent->getResponse();

    if (!$response instanceof AttachmentsInterface) {
      return;
    }

    $libraries = $this->libraryDependencyResolver
      ->getLibrariesWithDependencies(
        $response->getAttachments()['library'] ?? []
      );

    if (in_array('editoria11y/editoria11y', $libraries)
      || in_array('editoria11y/editoria11y-localized', $libraries)) {
      $policy->fallbackAwareAppendIfEnabled('style-src', [Csp::POLICY_UNSAFE_INLINE]); // @phpstan-ignore-line
      $policy->fallbackAwareAppendIfEnabled('style-src-attr', [Csp::POLICY_UNSAFE_INLINE]); // @phpstan-ignore-line
      $policy->fallbackAwareAppendIfEnabled('style-src-elem', [Csp::POLICY_UNSAFE_INLINE]); // @phpstan-ignore-line
    }
  }

}
