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
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#attributes' => array(
        'data-stripe' => 'name',
      ),
      '#required' => TRUE,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];
    $form['donation_custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Donation Amount'),
    ];
    $form['cc'] = [
      '#type' => 'creditfield_cardnumber',
      '#title' => $this->t('Credit Card Number'),
      '#attributes' => array(
        'data-stripe' => 'number',
      ),
      '#maxlength' => 16,
      '#required' => TRUE,
    ];
    $form['expiration_date'] = array(
      '#type' => 'creditfield_expiration',
      '#title' => $this->t('Exp Date'),
      '#required' => TRUE,
    );
    $form['credit_card_cvc'] = array(
      '#type' => 'creditfield_cardcode',
      '#title' => $this->t('CVC Code'),
      '#attributes' => array(
        'data-stripe' => 'cvc',
      ),
      '#maxlength' => 4,
      '#description' => 'Your 3 or 4 digit security code on the back of your card.',
      '#required' => TRUE,
    );
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Process Donation'),
    ];

    $form['#attached']['library'][] = 'ts_donations/donation';


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for proper amount.
    $amount = $form_state->getValue('donation_custom');
    if (is_numeric($amount)) {
      $decimal_places = strlen(substr(strrchr($amount, "."), 1));
      if ($decimal_places == 0) {
        // If no decimals set value to have them.
        $form_state->setValue('donation_custom', number_format($amount, 2));
      }
      elseif ($decimal_places != 2) {
        $form_state->setErrorByName('donation_custom', $this->t('Donation amount can only have 2 decimal places.'));
      }
    }
    else {
      $form_state->setErrorByName('donation_custom', $this->t('Donation amount must be a valid number'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $amount = $this->makeCents($form_state->getValue('donation_custom'));
    $email = $form_state->getValue('email');
    $this->setKey();
    try {
      $charge = $this->charge($amount, $email);
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
  protected function charge($amount, $email) {
    $charge = Charge::create([
      'amount' => $amount,
      'currency' => 'usd',
      'source' => 'tok_visa', // @todo add actual card handling
      'receipt_email' => $email,
    ]);
    return $charge;
  }

  /**
   * Initialize stripe secret key.
   */
  protected function setKey() {
    Stripe::setApiKey("sk_test_3lKeeOscJK4c0Px7m36j8up6");
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
}