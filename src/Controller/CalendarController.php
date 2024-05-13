<?php

namespace Drupal\pistas_padel\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * An example controller.
 */
class CalendarController extends ControllerBase {

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
   * Returns a modal.
   */
  public function reserve(Term $pista = NULL, string $date = '', string $hour = '', string $min = '', $return = '') {
    if (empty($pista) || empty($date) || empty($hour) || empty($min)) {
      return [];
    }

    $config = $this->config(static::SETTINGS);
    $duration = $config->get('tranche_duration');

    $date_str = $date . ' ' . $hour . ':' . $min . ':00';
    $datetime = new DrupalDateTime($date_str);
    $reserva = $this->getNodeReservation($pista, $datetime);
    $body = '';

    $dayOfWeek = (int) $datetime->format('w');
    $not_from = (int) $config->get('not_available_from_' . $dayOfWeek);
    $not_to = (int) $config->get('not_available_to_' . $dayOfWeek);
    $block_hour = FALSE;
    $hour = (int) $datetime->format('H');
    if (!empty($not_from) && !empty($not_to) && ($not_from <= (int) $hour &&  $hour <= $not_to)) {
      $block_hour = TRUE;
    }

    if ($block_hour) {
      $body = $config->get('text_bloked');
    }
    elseif (empty($reserva)) {
      $body = $config->get('text_available');
    }
    elseif ($reserva->field_status->first()->value == 'locked') {
      $body = $config->get('text_bloked');
    }
    elseif ($reserva->field_status->first()->value == 'reserved') {
      $body = $config->get('text_no_available');
    }

    $content = [
      '#type' => 'container',
    ];

    if (empty($reserva) && !$block_hour) {
      $content['body'] = [
        '#markup' => $body,
      ];
      $content['confirm-form'] = \Drupal::formBuilder()->getForm('Drupal\pistas_padel\Form\AddReserveForm', $pista, $datetime, $return);
    }
    else {
      $content['body'] = [
        '#markup' => $body,
      ];
    }

    // Get the title of the node.
    $title = $this->t('Reserve paddle tennis court @court on @datetime hs.', ['@court' => $pista->getName(), '@datetime' => $datetime->format('d/m/Y H:i')]);

    // Create the AjaxResponse object.
    $response = new AjaxResponse();

    // Attach the library needed to use the OpenDialogCommand response.
    $attachments['library'][] = 'core/drupal.dialog.ajax';
    $response->setAttachments($attachments);

    // Add the open dialog command to the ajax response.
    $response->addCommand(new OpenDialogCommand('#my-dialog-selector', $title, $content, ['width' => '70%']));
    return $response;
  }

  /**
   * Undocumented function.
   */
  protected function getNodeReservation(Term $pista, DrupalDateTime $datetime) {
    $config = $this->config(static::SETTINGS);
    $field_name = $config->get('padel_courts');

    $nids = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', $this::BOOKING_BUNDLE)
      ->condition($field_name . '.target_id', $pista->tid->value)
      ->condition('field_date_and_time', $datetime->format('Y-m-d H:i:00'))
      ->sort('nid', 'DESC')
      ->execute();

    if (empty($nids)) {
      return FALSE;
    }
    return Node::load(array_values($nids)[0]);
  }

}
