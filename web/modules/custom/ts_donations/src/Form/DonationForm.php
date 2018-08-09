<?php

namespace Drupal\ts_donations\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Stripe\Stripe;
use Stripe\Charge;


class DonationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ts_donation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['errors'] = [
      '#markup' => '<div class="form-errors"></div>'
    ];

    $form['contact_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Contact Info'),
    ];
    $form['contact_info']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#attributes' => [
        'data-stripe' => 'name',
      ],
      '#required' => TRUE,
    ];
    $form['contact_info']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['donations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Donation Amount'),
    ];

    $form['donations']['donation_option'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        25 => '$25',
        50 => '$50',
        100 => '$100',
        'other' => $this->t('Other'),
      ],
    ];

    $form['donations']['donation_other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other amount'),
      '#states' => [
        'visible' => [
          'select[name="donation_option"]' => ['value' => 'other']
        ],
        'required' => [
        'select[name="donation_option"]' => ['value' => 'other'],
        ],
      ],
    ];

    $form['cc_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Payment Info'),
      '#required' => TRUE,
    ];

    $form['cc_info']['cc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Credit Card Number'),
      '#attributes' => [
        'data-stripe' => 'number',
      ],
      '#maxlength' => 16,
      '#required' => TRUE,
    ];

    $form['cc_info']['expiration_month'] = [
      '#type' => 'select',
      '#title' => $this->t('Exp. Month'),
      '#options' => [
        '01' => $this->t('January'),
        '02' => $this->t('February'),
        '03' => $this->t('March'),
        '04' => $this->t('April'),
        '05' => $this->t('May'),
        '06' => $this->t('June'),
        '07' => $this->t('July'),
        '08' => $this->t('August'),
        '09' => $this->t('September'),
        '10' => $this->t('October'),
        '11' => $this->t('November'),
        '12' => $this->t('December')
      ],
      '#required' => TRUE,
      '#attributes' => [
        'data-stripe' => 'exp_month',
      ],
    ];

    $year_options = [];
    for ($i = 2018; $i < 2030; $i++) {
      $year_options[$i] = $i;
    }
    $form['cc_info']['expiration_year'] = [
      '#type' => 'select',
      '#title' => $this->t('Exp. Year'),
      '#required' => TRUE,
      '#options' => $year_options,
      '#attributes' => [
        'data-stripe' => 'exp_year',
      ],
    ];

    $form['cc_info']['credit_card_cvc'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CVC Code'),
      '#attributes' => [
        'data-stripe' => 'cvc',
      ],
      '#maxlength' => 4,
      '#description' => 'Your 3 or 4 digit security code on the back of your card.',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Process Donation'),
      '#prefix' => '<div hidden>',
      '#suffix' => '</div>',
    ];

    $form['button'] = [
      '#markup' => '<a id="trigger-donate" class="button" href="#">Submit Donation</a>',
    ];

    $form['#attached']['library'][] = 'ts_donations/donation';

    $config = \Drupal::config('donation.apiKeys');
    $publishable_key = $config->get('publishable');
    $form['#attached']['drupalSettings']['donations']['publishable_key'] = $publishable_key;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for proper amount.
    $donation = $this->getDonationAmount($form, $form_state);
    if (empty($donation)) {
      $form_state->setErrorByName('donation_option', $this->t('Please select or enter a valid donation amount.'));
    }

    $cc = $form_state->getValue('cc');
    if (!is_numeric($cc)) {
      $form_state->setErrorByName('cc', $this->t('Please enter a valid credit card number.'));
    }

    $cvc = $form_state->getValue('credit_card_cvc');
    if (!is_numeric($cvc)) {
      $form_state->setErrorByName('credit_card_cvc', $this->t('Please enter a valid CVC.'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $donation = $this->getDonationAmount($form, $form_state);
    $amount = $this->makeCents($donation);
    $email = $form_state->getValue('email');
    $token = $_POST['stripe_token'];
    $this->setKey();
    try {
      $charge = $this->charge($amount, $email, $token);
    }
    // Catch stripe errors
    catch(\Stripe\Error\Card $e) {
      $this->logPaymentError($e);
    }
    catch (\Stripe\Error\RateLimit $e) {
      // Too many requests made to the API too quickly
      $this->logPaymentError($e);
    }
    catch (\Stripe\Error\InvalidRequest $e) {
      // Invalid parameters were supplied to Stripe's API
      $this->logPaymentError($e);
    }
    catch (\Stripe\Error\Authentication $e) {
      $this->logPaymentError($e);
    }
    catch (\Stripe\Error\ApiConnection $e) {
      // Network communication with Stripe failed
      $this->logPaymentError($e);
    }
    catch (\Stripe\Error\Base $e) {
      $this->logPaymentError($e);
    }
    // Catch general exceptions
    catch (Exception $e) {
      $this->logPaymentError($e);
    }

    if ($charge) {
      $messenger = \Drupal::messenger();
      $payment_amount = $this->makeDollars($charge->amount);
      $messenger->addStatus($this->t('Thank you for your donation of %amount', ['%amount' => $payment_amount]));
    }
  }

  /**
   * Log payment errors.
   * @param $e
   */
  protected function logPaymentError($e) {
    \Drupal::logger('donations')->error($e->getMessage());
    $messenger = \Drupal::messenger();
    $messenger->addError($this->t('Sorry, there was an error processing your payment - %error', ['%error' => $e->getMessage()]));
  }

  /**
   * Process a charge.
   * @param $amount
   * @param $email
   *
   * @return \Stripe\ApiResource
   */
  protected function charge($amount, $email, $token) {
    $charge = Charge::create([
      'amount' => $amount,
      'currency' => 'usd',
      'source' => $token, // @todo add actual card handling
      'receipt_email' => $email,
    ]);
    return $charge;
  }

  /**
   * Initialize stripe secret key.
   */
  protected function setKey() {
    $config = \Drupal::config('donation.apiKeys');
    $secret_key = $config->get('secret');
    Stripe::setApiKey($secret_key);
  }

  /**
   * Convert cents number into human readable dollar amount.
   * @param $payment
   *
   * @return string
   */
  protected function makeDollars($payment) {
    return '$' . $payment/100;
  }

  /**
   * Convert decimal dollar amount into cents.
   * @param $amount
   *
   * @return float|int
   */
  protected function makeCents($amount) {
    return $amount*100;
  }

  protected function getDonationAmount(array &$form, FormStateInterface $form_state) {
    $donation_option = $form_state->getValue('donation_option');

    if ($donation_option == 'other') {
      $donation = $form_state->getValue('donation_other');
    } else {
      $donation = $form_state->getValue('donation_option');
    }

    // If value is not a number return FALSE.
    if (!is_numeric($donation) || empty($donation)) {
      return FALSE;
    }
    else {
      // Format number and return value.
      return number_format($donation, 2);
    }
  }
}