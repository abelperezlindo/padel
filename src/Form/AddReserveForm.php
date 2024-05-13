<?php

namespace Drupal\pistas_padel\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class AddReserveForm extends ConfirmFormBase {

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
   * ID of the item to delete.
   *
   * @var Drupal\taxonomy\Entity\Term
   */
  protected $term;

  /**
   * ID of the item to delete.
   *
   * @var Drupal\Core\Datetime\DrupalDateTime
   */
  protected $datetime;

  /**
   * Return route name.
   *
   * @var string
   */
  protected $return;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Term $term = NULL, DrupalDateTime $datetime = NULL, $return = '<front>') {
    $this->term = $term;
    $this->datetime = $datetime;
    $this->return = $return;

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = User::load(\Drupal::currentUser()->id());
    // Create a new node object.
    $config = $this->config(static::SETTINGS);
    $duration = $config->get('tranche_duration');
    $node = Node::create([
      'type' => $this::BOOKING_BUNDLE,
      'title' => 'The user ' . $user->getDisplayName() . ' has reserved ' . $this->term->getName(),
      'status' => '1',
    ]);
    $reserved = $this->getNodeReservation($this->term->tid->value, $this->datetime->format('Y-m-d H:i:00'));
    if ($reserved !== FALSE) {
      return;
    }

    $node->set('field_date_and_time', $this->datetime->format('Y-m-d H:i:00'));
    $node->set('field_minutes', $duration);
    $node->set('field_padel_courts', ['target_id' => $this->term->tid->value]);
    $node->set('field_status', 'reserved');
    // Save the node.
    $node->save();
    $params = [
      'date' => $this->datetime->format('Y-m-d'),
    ];
    $form_state->setRedirect($this->return, $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return "confirm_add_reserve_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Confirm?');
  }

  /**
   * Undocumented function.
   */
  protected function getNodeReservation($tid, $datetime_str) {
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