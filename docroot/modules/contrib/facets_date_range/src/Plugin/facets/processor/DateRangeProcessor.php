<?php

declare(strict_types = 1);

namespace Drupal\facets_date_range\Plugin\facets\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetInterface;
use Drupal\facets\Processor\PreQueryProcessorInterface;
use Drupal\facets\Processor\ProcessorPluginBase;
use Drupal\facets\Processor\BuildProcessorInterface;

/**
 * Provides a processor that show all values between a range of dates.
 *
 * @FacetsProcessor(
 *   id = "date_range",
 *   label = @Translation("Date Range Picker"),
 *   description = @Translation("Show all content between min and max range dates."),
 *   stages = {
 *     "pre_query" = 60,
 *     "build" = 20
 *   }
 * )
 */
class DateRangeProcessor extends ProcessorPluginBase implements PreQueryProcessorInterface, BuildProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function preQuery(FacetInterface $facet): void {
    $active_items = $facet->getActiveItems();

    $config = $this->getConfiguration();
    $max_offset = $config['max_inclusive'] ? strtotime('+1 day', 0) - 1 : 0;

    array_walk($active_items, function (string &$item) use ($max_offset): void {
      if (preg_match('/\(min:([^,]*),max:(.*)\)/i', $item, $matches)) {
        if (!empty($matches[1]) && !empty($matches[2])) {
          $item = [
            $matches[1],
            $matches[2] + $max_offset,
            'min' => $matches[1],
            'max' => $matches[2],
          ];
        }
        elseif (!empty($matches[1]) && empty($matches[2])) {
          $item = [
            $matches[1],
            date('U', strtotime('+100 years')),
            'min' => $matches[1],
          ];
        }
        elseif (empty($matches[1]) && !empty($matches[2])) {
          $item = [
            date('U', 0),
            $matches[2] + $max_offset,
            'max' => $matches[2],
          ];
        }
        else {
          $item = [
            date('U', 0),
            date('U', strtotime('+100 years')),
          ];
        }
      }
      else {
        $item = [];
      }
    });
    $facet->setActiveItems($active_items);
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet, array $results): array {
    /** @var \Drupal\facets\Plugin\facets\processor\UrlProcessorHandler $url_processor_handler */
    $url_processor_handler = $facet->getProcessors()['url_processor_handler'];
    $url_processor = $url_processor_handler->getProcessor();
    $filter_key = $url_processor->getFilterKey();

    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    foreach ($results as &$result) {
      $url = $result->getUrl();
      $query = $url->getOption('query');

      // Remove all the query filters for the field of the facet.
      if (isset($query[$filter_key])) {
        foreach ($query[$filter_key] as $id => $filter) {
          if (strpos($filter . $url_processor->getSeparator(), $facet->getUrlAlias()) === 0) {
            unset($query[$filter_key][$id]);
          }
        }
      }

      $query[$filter_key][] = $facet->getUrlAlias() . $url_processor->getSeparator() . '(min:__date_range_min__,max:__date_range_max__)';
      $url->setOption('query', $query);
      $result->setUrl($url);
    }
    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet): array {
    $config = $this->getConfiguration();

    $build['max_inclusive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include through end of max date'),
      '#default_value' => $config['max_inclusive'],
      '#description' => $this->t('Include search results for any time of day on the max date. (Otherwise, search results after 00:00:00 on the max date will be excluded.)'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'max_inclusive' => FALSE,
    ];
  }

}
