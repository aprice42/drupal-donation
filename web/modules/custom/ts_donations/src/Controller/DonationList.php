<?php

namespace Drupal\ts_donations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Stripe\Stripe;
use Stripe\Charge;

class DonationList extends ControllerBase {

  public function view() {
    $build = [];

    $build['content']['donation_table'] = [
      '#type' => 'table',
      '#header' => [
        t('Date'),
        t('Name'),
        t('Amount'),
        t('Transaction ID'),
      ],
    ];

    $results = $this->getDonations();

    $rows = [];
    foreach ($results->data as $key => $result) {
      $id = $result->id;
      $name = $result->source->name;
      $amount = $result->amount;
      $date = date('Y-m-d - H:i:s', $result->created);

      $rows[$key]['Date'] = $date;
      $rows[$key]['Name'] = $name;
      $rows[$key]['Amount'] = '$' . number_format($amount/100, 2);
      $rows[$key]['Transaction ID'] = $id;
    }

    $build['content']['donation_table']['#rows'] = $rows;

    return $build;
  }

  protected function getDonations() {
    $this->setKey();
    $results = Charge::all(array("limit" => 25));
    return $results;
  }

  /**
   * Initialize stripe secret key.
   */
  protected function setKey() {
    $config = \Drupal::config('donation.apiKeys');
    $secret_key = $config->get('secret');
    Stripe::setApiKey($secret_key);
  }
}