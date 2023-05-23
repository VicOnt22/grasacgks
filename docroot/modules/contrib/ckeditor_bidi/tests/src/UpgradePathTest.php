<?php

declare(strict_types = 1);

namespace Drupal\Tests\ckeditor_bidi\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Kernel\SmartDefaultSettingsTest;

/**
 * @covers \Drupal\ckeditor_bidi\Plugin\CKEditor4To5Upgrade\Direction
 * @group ckeditor_bidi
 * @group ckeditor5
 * @requires module ckeditor5
 * @internal
 */
class UpgradePathTest extends SmartDefaultSettingsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor_bidi',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $filter_config = [
      'filter_html' => [
        'status' => 1,
        'settings' => [
          'allowed_html' => '<p> <br> <strong> <h2> <h3>',
        ],
      ],
    ];
    FilterFormat::create([
      'format' => 'ckeditor_bidi_both',
      'name' => 'Both ckeditor_bidi CKE4 buttons',
      'filters' => $filter_config,
    ])->setSyncing(TRUE)->save();
    FilterFormat::create([
      'format' => 'ckeditor_bidi_ltr_only',
      'name' => 'Only the LTR ckeditor_bidi CKE4 button',
      'filters' => $filter_config,
    ])->setSyncing(TRUE)->save();
    FilterFormat::create([
      'format' => 'ckeditor_bidi_rtl_only',
      'name' => 'Only the RTL ckeditor_bidi CKE4 button',
      'filters' => $filter_config,
    ])->setSyncing(TRUE)->save();

    $generate_editor_settings = function (array $ckeditor_bidi_buttons) {
      return [
        'toolbar' => [
          'rows' => [
            0 => [
              [
                'name' => 'Basic Formatting',
                'items' => [
                  'Bold',
                  'Format',
                ],
              ],
              [
                'name' => 'ckeditor_bidi buttons',
                'items' => $ckeditor_bidi_buttons,
              ],
            ],
          ],
        ],
        'plugins' => [
          // The CKEditor 4 plugin functionality has no settings.
        ],
      ];
    };

    Editor::create([
      'format' => 'ckeditor_bidi_both',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings([
        'BidiLtr',
        'BidiRtl',
      ]),
    ])->setSyncing(TRUE)->save();
    Editor::create([
      'format' => 'ckeditor_bidi_ltr_only',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings([
        'BidiLtr',
      ]),
    ])->setSyncing(TRUE)->save();
    Editor::create([
      'format' => 'ckeditor_bidi_rtl_only',
      'editor' => 'ckeditor',
      'settings' => $generate_editor_settings([
        'BidiRtl',
      ]),
    ])->setSyncing(TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function provider() {
    parent::provider();

    $expected_ckeditor5_settings = [
      'toolbar' => [
        'items' => [
          'bold',
          'heading',
          '|',
          'direction',
        ],
      ],
      'plugins' => [
        'ckeditor5_heading' => [
          'enabled_headings' => [
            'heading2',
            'heading3',
          ],
        ],
        'ckeditor_bidi_ckeditor5' => [
          'rtl_default' => FALSE,
        ],
      ],
    ];

    yield "both CKEditor 4 buttons" => [
      'format_id' => 'ckeditor_bidi_both',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => $expected_ckeditor5_settings,
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];
    yield "LTR CKEditor 4 button only" => [
      'format_id' => 'ckeditor_bidi_ltr_only',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => $expected_ckeditor5_settings,
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];
    yield "RTL CKEditor 4 button only" => [
      'format_id' => 'ckeditor_bidi_rtl_only',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => $expected_ckeditor5_settings,
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];
  }

}
