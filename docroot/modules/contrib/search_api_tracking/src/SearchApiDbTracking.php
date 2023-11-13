<?php

namespace Drupal\search_api_tracking;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * DB Tracking class.
 */
class SearchApiDbTracking implements SearchApiStorageInterface {

  /**
   * The default connection object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * DB Table name.
   *
   * @var string
   */
  private $dbTableName;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private $languageManager;

  /**
   * Time object with current time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private $time;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $user;

  /**
   * Constructs the language manager.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The default connection object.
   */
  public function __construct(Connection $connection,
                              $dbTableName,
                              LoggerChannelInterface $logger,
                              LanguageManagerInterface $languageManager,
                              TimeInterface $time,
                              AccountProxyInterface $user) {
    $this->connection = $connection;
    $this->dbTableName = $dbTableName;
    $this->logger = $logger;
    $this->languageManager = $languageManager;
    $this->time = $time;
    $this->user = $user;
  }

  /**
   * @inheritDoc
   */
  public function saveTracking(string $keywords, string $type, string $numberOfResults, string $sortBy, string $sortOrder) {
    // To avoid to insert empty keywords value into database.
    if (!empty($keywords) && !empty($type)) {
      try {
        $this->connection->insert($this->dbTableName)->fields(
          [
            'type' => $type,
            'timestamp' => $this->time->getRequestTime(),
            'numfound' => $numberOfResults,
            'uid' => $this->user->id(),
            'keywords' => $keywords,
            'language' => $this->languageManager->getCurrentLanguage()->getId(),
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
          ]
        )->execute();

        return TRUE;
      }
      catch (\Exception $e) {
        $this->logger->error($this->t('Error: @error', [
          '@error' => $e->getMessage(),
        ]));

        return FALSE;
      }
    }
  }

}
