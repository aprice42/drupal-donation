<?php


namespace Drupal\ts_donations\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a donation form block.
 *
 * @Block(
 *   id = "ts_donation_block",
 *   admin_label = @Translation("Donation Form Block")
 * )
 */
class DonationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Make a Donation');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      'form' => \Drupal::formBuilder()->getForm('Drupal\ts_donations\Form\DonationForm'),
    ];

    return $build;
  }
}