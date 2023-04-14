<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag\PropertyType;

use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a plugin for the 'BreadcrumbList' Schema.org property type.
 *
 * @SchemaPropertyType(
 *   id = "breadcrumb_list",
 *   label = @Translation("BreadcrumbList"),
 *   tree_parent = {
 *     "BreadcrumbList",
 *   },
 *   tree_depth = -1,
 *   property_type = "BreadcrumbList",
 *   sub_properties = {},
 * )
 */
class BreadcrumbList extends ItemListElement {

  /**
   * Breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbManager
   */
  protected $breadcrumbManager;

  /**
   * Renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->breadcrumbManager = $container->get('breadcrumb');
    $instance->renderer = $container->get('renderer');
    $instance->routeMatch = $container->get('current_route_match');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form($input_values) {
    $value = $input_values['value'];
    $form = [
      '#type' => 'select',
      '#title' => $input_values['title'],
      '#description' => $input_values['description'],
      '#default_value' => !empty($value) ? $value : '',
      '#maxlength' => 255,
      '#empty_option' => $this->t('No'),
      '#empty_value' => '',
      '#options' => [
        'Yes' => $this->t('Yes'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function testValue($type = '') {
    return 'Yes';
  }

  /**
   * {@inheritdoc}
   */
  public function outputValue($input_value) {
    $output_value = parent::outputValue($input_value);
    $items = [];
    if (!empty($output_value)) {
      $items = [
        "@type" => "BreadcrumbList",
        "itemListElement" => $output_value,
      ];
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getItems($input_value) {
    $values = [];
    if (!empty($input_value)) {
      $entity_route = $this->routeMatch->getCurrentRouteMatch();
      $breadcrumbs = $this->breadcrumbManager->build($entity_route)->getLinks();
      $key = 1;
      foreach ($breadcrumbs as $item) {
        // Modules that add the current page to the breadcrumb set it to an
        // empty path, so an empty path is the current path.
        $url = $item->getUrl()->setAbsolute()->toString();
        if (empty($url)) {
          $url = Url::fromRoute('<current>')->setAbsolute()->toString();
        }
        $text = $item->getText();
        $text = is_array($text) ? $this->renderer->renderPlain($text) : $text;
        $values[$key] = [
          '@id' => $url,
          'name' => $text,
          'item' => $url,
        ];
        $key++;
      }
    }
    return $values;
  }

}
