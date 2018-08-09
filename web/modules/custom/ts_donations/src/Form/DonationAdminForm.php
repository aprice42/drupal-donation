<?php


namespace Drupal\ts_donations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DonationAdminForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'donation.apiKeys',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'donation_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('donation.apiKeys');

    $form['publishable'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishable Key'),
      '#default_value' => $config->get('publishable'),
    ];
    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#default_value' => $config->get('secret'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('donation.apiKeys')
      ->set('publishable', $form_state->getValue('publishable'))
      ->set('secret', $form_state->getValue('secret'))
      ->save();
  }


}