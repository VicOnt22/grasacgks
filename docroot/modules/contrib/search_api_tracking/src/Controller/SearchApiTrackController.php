<?php

namespace Drupal\search_api_tracking\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\search_api_tracking\SearchApiStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An searchApiTrackController controller.
 */
class SearchApiTrackController extends ControllerBase {

  /**
   * Storage variable.
   *
   * @var \Drupal\search_api_tracking\SearchApiStorageInterface
   */
  private $storage;

  /**
   * Constructs a SearchApiTrackController object.
   *
   * @param \Drupal\search_api_tracking\SearchApiStorageInterface $storage
   *   Storage.
   */
  public function __construct(SearchApiStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('search_api_tracking.storage')
    );
  }

  /**
   * Returns a render-able array for a test page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   */
  public function track(Request $request) {
    $postParams = $request->getContent();
    if (empty($postParams)) {
      return new Response('', Response::HTTP_BAD_REQUEST);
    }

    $body = json_decode($postParams, TRUE, 512, JSON_THROW_ON_ERROR);
    $body = $this->sanitizeParameterBag($body);

    $type = $body['type'];
    $keywords = $body['searchedString'];
    $sortBy = $body['sortBy'];
    $sortOrder = $body['sortOrder'];
    $numberOfResults = 0;

    if (isset($body['numberOfResults']) && is_numeric($body['numberOfResults'])) {
      $numberOfResults = $body['numberOfResults'];
    }

    if ($this->storage->saveTracking($keywords, $type, $numberOfResults, $sortBy, $sortOrder)) {
      return new Response('', Response::HTTP_CREATED);
    }
    else {
      return new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Sanitize query parameters.
   *
   * @param array $queryParameters
   *   The query parameters.
   *
   * @return array
   *   Sanitized queryparameters.
   */
  private function sanitizeParameterBag(array $queryParameters) {
    foreach ($queryParameters as $key => $param) {
      $queryParameters[Xss::filter($key)] = Xss::filter($param);
    }
    return $queryParameters;
  }

}
