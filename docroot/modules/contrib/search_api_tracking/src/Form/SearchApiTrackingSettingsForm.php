<?php

namespace Drupal\search_api_tracking\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form.
 */
class SearchApiTrackingSettingsForm extends ConfigFormBase {

  const SETTINGSNAME = 'search_api_tracking.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_api_tracking';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      self::SETTINGSNAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config(self::SETTINGSNAME);

    $form['search_form_selectors'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => $this->t('Search FORM selectors'),
      '#description' => $this->t('Enter JS selectors for the different search forms on your website. Separate each selector with a ","'),
      '#default_value' => $config->get('search_form_selectors') ?? '.overview--search .views-exposed-form, #police-search-form-popup',
    ];

    $form['autocomplete_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Autocomplete input selector'),
      '#description' => $this->t('Enter JS selector for the autocomplete input selector, if any.'),
      '#default_value' => $config->get('autocomplete_selector') ?? '.ui-autocomplete-input',
    ];

    $form['search_empty_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty selector'),
      '#description' => $this->t('Enter JS selector for the empty div.'),
      '#default_value' => $config->get('search_empty_selector') ?? '.overview--search .view-empty',
    ];

    $form['did_you_mean_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Did you mean selector'),
      '#description' => $this->t('Enter JS selector for did you mean div.'),
      '#default_value' => $config->get('did_you_mean_selector') ?? '.sapi-did-you-mean a',
    ];

    $form['sort_by_selector'] = [
      '#type' => 'textfield',
      '#field_prefix' => '#',
      '#title' => $this->t('Sort by ID selector'),
      '#description' => $this->t('Enter JS ID selector for sort by div. Do not add the #'),
      '#default_value' => $config->get('sort_by_selector') ?? 'edit-sort-by',
    ];

    $form['sort_order_selector'] = [
      '#type' => 'textfield',
      '#field_prefix' => '#',
      '#title' => $this->t('Sort order ID selector'),
      '#description' => $this->t('Enter JS selector for sort order div. Do not add the #'),
      '#default_value' => $config->get('sort_order_selector') ?? 'edit-sort-order',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->configFactory->getEditable(self::SETTINGSNAME);

    $config->set('search_form_selectors', $form_state->getValue('search_form_selectors', ''));
    $config->set('autocomplete_selector', $form_state->getValue('autocomplete_selector', ''));
    $config->set('search_empty_selector', $form_state->getValue('search_empty_selector', ''));
    $config->set('did_you_mean_selector', $form_state->getValue('did_you_mean_selector', ''));
    $config->set('sort_by_selector', $form_state->getValue('sort_by_selector', ''));
    $config->set('sort_order_selector', $form_state->getValue('sort_order_selector', ''));

    $config->save();
  }

}
