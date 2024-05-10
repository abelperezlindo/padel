<?php

namespace Drupal\pistas_padel\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;

/**
 * Configure example settings for this site.
 */
class CalendarForm extends FormBase {

  /**
   * Padel booking node type.
   *
   * @var string
   */
  const BOOKING_BUNDLE = 'padel_court_reservation';

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'pistas_padel.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pistas_padel_calendar';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $date = new DrupalDateTime('now');
    if ($form_state->getValue('selected-date')) {
      $date = $form_state->getValue('selected-date');
    }
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'pistas_padel/pistas_padel.styles';

    $form['selected-date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Booking date'),
      '#date_date_format' => 'd/m/Y',
      '#date_time_element' => 'none',
      '#date_date_element' => 'date',
      '#default_value' => $date,
      '#ajax' => [
        'callback' => '::reloadCalendarBox',
        'event' => 'change',
      ],
    ];
    $form['calendar-box'] = [
      '#type' => 'container',
      '#prefix' => '<div id="calendar-box">',
      '#suffix' => '</div>',
    ];

    $dayOfWeek = (int) $date->format('w');
    $openThisDay = $config->get('day_' . $dayOfWeek);

    if (!$openThisDay) {
      $form['calendar-box']['notice'] = [
        '#markup' => $this->t('No open this day.'),
      ];
      return $form;
    }

    $opening = (int) $config->get('opening_time_' . $dayOfWeek);
    $closing = (int) $config->get('closing_time_' . $dayOfWeek);
    $hours = -$opening + $closing;

    $minutes_per_tranches = (int) $config->get('tranche_duration');
    $time_tranches = 60 / $minutes_per_tranches;
    $courts = $this->getPadelCourts();
    $pistas_header = $courts->items;
    array_unshift($pistas_header, " ");
    $base_hour = $opening;

    if (empty($courts->items)) {
      $form['calendar-box']['notice'] = [
        '#markup' => $this->t('No courts available.'),
      ];
      return $form;
    }

    // Number of tables to draw.
    for ($i = 0; $i < $hours; $i++) {
      // A table per hour.
      $output = [];
      $base_minutes = 0;
      for ($x = 0; $x < $time_tranches; $x++) {
        $output[$x] = [
          'tranches' => [
            'data' => [
              '#markup' => $base_minutes,
            ],
          ],
        ];
        foreach ($courts->items as $tid => $name) {
          $empty_space = $this->t('&nbsp;&nbsp;&nbsp;&nbsp;');
          // $court_state = $courts->states[$tid];
          $parameters = [
            'pista' => $tid,
            'date' => $date->format('Y-m-d'),
            'hour' => str_pad($base_hour, 2, '0', STR_PAD_LEFT),
            'min' => str_pad($base_minutes, 2, '0', STR_PAD_LEFT),
          ];
          $datetime_str = $parameters['date'] . ' ' . $parameters['hour'] . ':' . $parameters['min'] . ':00';
          $node = $this->getNodeReservation($tid, $datetime_str);

          if ($node === FALSE) {
            $class = 'available';
          }
          elseif ($node->field_status->first()->value == 'locked') {
            $class = 'locked';
          }
          else {
            $class = 'noavailable';
          }

          $options = [
            'attributes' => ['class' => ['use-ajax', 'base-reserve-btn', $class]],
          ];

          $link = Link::createFromRoute($empty_space, 'pistas_padel.reserve', $parameters, $options);

          $output[$x][$tid]['data'] = $link;
        }

        $base_minutes = $base_minutes + $minutes_per_tranches;
      }

      $form['calendar-box']['table-hour-' . $i] = [
        '#type' => 'table',
        '#caption' => t('@h HS', ['@h' => $base_hour]),
        '#header' => $pistas_header,
        '#rows' => $output,
        '#empty' => t('No items found'),
      ];
      $base_hour++;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

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

  /**
   * Undocumented function.
   */
  public function reloadCalendarBox(array $form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand("#calendar-box", $form['calendar-box']));
    return $response;
  }

  /**
   * Undocumented function.
   */
  public function getPadelCourts(): \stdClass {

    $config = $this->config(static::SETTINGS);
    $field_name = $config->get('padel_courts');
    /** @var \Drupal\field\Entity\FieldConfig $info */
    $info = FieldConfig::loadByName('node', $this::BOOKING_BUNDLE, $field_name);
    $target_bundles = $info->getSetting('handler_settings')['target_bundles'];
    $bundle = array_values($target_bundles)[0];

    // Get the term storage.
    $entity_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    // Query the terms sorted by weight.
    $query_result = $entity_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', $bundle)
      ->sort('weight', 'ASC')
      ->execute();

    $result = new \stdClass();
    $result->items = [];
    $result->states = [];
    // Load the terms.
    $terms = $entity_storage->loadMultiple($query_result);

    foreach ($terms as $term) {
      $result->items[$term->id()] = $term->getName();
      $result->states[$term->id()] = $term->status->value;
    }
    return $result;
  }

  /**
   * Undocumented function.
   */
  protected function getNodeReservation(string $tid, string $datetime_str) {
    $config = $this->config(static::SETTINGS);
    $field_name = $config->get('padel_courts');

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $this::BOOKING_BUNDLE)
      ->condition($field_name . '.target_id', $tid)
      ->condition('field_date_and_time', $datetime_str)
      ->sort('nid', 'DESC')
      ->execute();

    if (empty($nids)) {
      return FALSE;
    }
    return Node::load(array_values($nids)[0]);
  }

}
