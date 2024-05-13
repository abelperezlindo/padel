<?php

namespace Drupal\pistas_padel\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'pistas_padel.settings';

  /**
   * Padel booking node type.
   *
   * @var string
   */
  const BOOKING_BUNDLE = 'padel_court_reservation';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pistas_padel_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions('node', self::BOOKING_BUNDLE);
    $fields_options = [];
    foreach ($fields as $machine_name => $definition) {
      if ($definition->getType() == 'entity_reference') {
        $settings = $definition->getSettings();
        if ($settings['target_type'] == 'taxonomy_term') {
          $fields_options[$machine_name] = $definition->getLabel();
        }
      }
    }

    $form['padel-courts'] = [
      '#type' => 'select',
      '#title' => $this->t('Padel court'),
      '#description' => $this->t('Select the field that relates the reservation to a paddle tennis court. The content to which this field refers represents the padel courts that are available.'),
      '#options' => $fields_options,
      '#required' => TRUE,
      '#default_value' => $config->get('padel_courts'),
    ];

    $form['text-bloked'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to display when track is bloked'),
      '#description' => $this->t('Enter the text to be displayed to the user trying to reserve a bloked track.'),
      '#default_value' => $config->get('text_bloked'),
    ];

    $form['text-available'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to display when the track is available'),
      '#description' => $this->t('Enter the text to be displayed to the user trying to reserve an available track.'),
      '#default_value' => $config->get('text_available'),
    ];

    $form['text-no-available'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to display when the track is no available'),
      '#description' => $this->t('Enter the text to be displayed to the user trying to reserve an no available track.'),
      '#default_value' => $config->get('text_no_available'),
    ];

    $form['tranche-duration'] = [
      '#type' => 'select',
      '#options' => [
        10 => $this->t('10 Min.'),
        15 => $this->t('15 Min.'),
        20 => $this->t('20 Min.'),
        30 => $this->t('30 Min.'),
        60 => $this->t('60 Min.'),
      ],
      '#title' => $this->t('Ttranche duration'),
      '#required' => TRUE,
      '#description' => $this->t('Select the duration of each tranche in minutes'),
      '#default_value' => $config->get('tranche_duration'),
    ];

    $days_of_weeks = $this->getDaysOfWeek();
    $form['days-available'] = [
      '#type' => 'details',
      '#title' => $this->t('Availability'),
      '#description' => $this->t('Select the days on which users can reserve the padel courts and for each selected day the opening and closing times.'),
    ];
    foreach ($days_of_weeks as $number => $day) {

      $form['days-available']['day-' . $number] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Aavailable on @day', ['@day' => $day]),
        '#default_value' => $config->get('day_' . $number),
      ];

      $form['days-available']['box' . $number] = [
        '#type' => 'fieldset',
        '#attributes' => [
          'id' => 'hours-box-' . $number,
          'class' => ['form--inline'],
        ],
        '#states' => [
          // Show this textfield only if the radio 'other' is selected above.
          'visible' => [
            ':input[name="day-' . $number . '"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $opening = $config->get('opening_time_' . $number);
      $closing = $config->get('closing_time_' . $number);
      $not_from = $config->get('not_available_from_' . $number);
      $not_to = $config->get('not_available_to_' . $number);

      $form['days-available']['box' . $number]['opening-time-' . $number] = [
        '#type' => 'number',
        '#title' => $this->t('Opening time'),
        '#min' => 0,
        '#max' => 23,
        '#default_value' => $opening ?? '',
      ];
      $form['days-available']['box' . $number]['closing-time-' . $number] = [
        '#type' => 'number',
        '#title' => $this->t('Closing time'),
        '#min' => 0,
        '#max' => 23,
        '#default_value' => $closing ?? '',
      ];
      $form['days-available']['box' . $number]['not-available-from-' . $number] = [
        '#type' => 'number',
        '#title' => $this->t('Not available from'),
        '#min' => 0,
        '#max' => 23,
        '#default_value' => $not_from ?? '',
      ];
      $form['days-available']['box' . $number]['not-available-to-' . $number] = [
        '#type' => 'number',
        '#title' => $this->t('Not available to'),
        '#min' => 0,
        '#max' => 23,
        '#default_value' => $not_to ?? '',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // $form_state->getValue('text-bloked');
    // $form_state->getValue('text-success');
    if (empty($form_state->getValue('padel-courts'))) {
      $form_state->setErrorByName('padel-courts', $this->t('You must enter the field that relates the paddle tennis court.'));
    }
    if (empty($form_state->getValue('tranche-duration'))) {
      $form_state->setErrorByName('tranche-duration', $this->t('You must enter a time tranches.'));
    }
    $days_of_weeks = $this->getDaysOfWeek();
    foreach ($days_of_weeks as $number => $day) {
      if ($form_state->getValue('day-' . $number)) {
        $opening = $form_state->getValue('opening-time-' . $number);
        $closing = $form_state->getValue('closing-time-' . $number);
        $not_from = $form_state->getValue('not-available-from-' . $number, '');
        $not_to = $form_state->getValue('not-available-to-' . $number, '');

        if (empty($opening)) {
          $form_state->setErrorByName('opening-time-' . $number, $this->t('You must enter a time.'));
        }
        if (empty($closing)) {
          $form_state->setErrorByName('closing-time-' . $number, $this->t('You must enter a time.'));
        }
        if (!empty($opening) && !empty($closing)) {
          if ($closing < $opening) {
            $form_state->setErrorByName('opening-time-' . $number, $this->t('The opening time must be less than the closing time.'));
          }
          if (!empty($not_from) && ($not_from < $opening || $not_from > $closing)) {
            $form_state->setErrorByName('not-available-from-' . $number, $this->t('The time from which it will not be available must be between the start and end time.'));
          }
          if (!empty($not_to) && ($not_to < $opening || $not_to > $closing)) {
            $form_state->setErrorByName('not-available-to-' . $number, $this->t('The time from which it will not be available must be between the start and end time.'));
          }
          if (!empty($not_to) && !empty($not_from) && ($not_to < $not_from)) {
            $form_state->setErrorByName('not-available-from-' . $number, $this->t('The time until which it is not available must be greater than the time from which it is not available.'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->config(static::SETTINGS)
      ->set('text_bloked', $form_state->getValue('text-bloked'))
      ->set('text_available', $form_state->getValue('text-available'))
      ->set('text_no_available', $form_state->getValue('text-no-available'))
      ->set('padel_courts', $form_state->getValue('padel-courts'))
      ->set('tranche_duration', $form_state->getValue('tranche-duration'))
      ->save();

    $days_of_weeks = $this->getDaysOfWeek();
    foreach ($days_of_weeks as $number => $day) {

      $time_open = $form_state->getValue('opening-time-' . $number, '');
      $time_close = $form_state->getValue('closing-time-' . $number, '');
      $not_from = $form_state->getValue('not-available-from-' . $number, '');
      $not_to = $form_state->getValue('not-available-to-' . $number, '');

      $this->config(static::SETTINGS)
        ->set('day_' . $number, $form_state->getValue('day-' . $number))
        ->set('opening_time_' . $number, $time_open ?? '')
        ->set('closing_time_' . $number, $time_close ?? '')
        ->set('not_available_from_' . $number, $not_from ?? '')
        ->set('not_available_to_' . $number, $not_to ?? '')
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Get days of week in an array.
   */
  protected function getDaysOfWeek():array {
    return [
      1 => $this->t('Monday'),
      2 => $this->t('Tuesday'),
      3 => $this->t('Wednesday'),
      4 => $this->t('Thursday'),
      5 => $this->t('Friday'),
      6 => $this->t('Saturday'),
      7 => $this->t('Sunday'),
    ];
  }

}
