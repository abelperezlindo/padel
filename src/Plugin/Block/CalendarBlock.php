<?php

namespace Drupal\pistas_padel\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Pistas Padel Calendar' Block.
 */
#[Block(
  id: "pistas_padel_calendar_block",
  admin_label: new TranslatableMarkup("Pistas Padel calendar block"),
  category: new TranslatableMarkup("Calendar")
)]
class CalendarBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\pistas_padel\Form\CalendarForm');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'hello_block_name' => $this->t('Default'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['hello_block_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Who'),
      '#description' => $this->t('Who do you want to say hello to?'),
      '#default_value' => $this->configuration['hello_block_name'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['hello_block_name'] = $form_state->getValue('hello_block_name');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // No use cache.
    return 0;
  }

}
