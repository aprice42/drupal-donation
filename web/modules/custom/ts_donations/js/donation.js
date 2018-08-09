/**
 * Defines behavior for stripe integration.
 */

(function ($, Drupal, drupalSettings) {

  $(function(){
    const $donationSubmit = $('#ts-donation-form input[type="submit"]');
    const $form = $('form#ts-donation-form');
    const $trigger = $('#trigger-donate');
    $trigger.on('click', function(e){
      e.preventDefault();
      console.log('click');
      Stripe.setPublishableKey(drupalSettings.donations.publishable_key);
      Stripe.card.createToken($form, function(status, response) {
        if (response.error) {
          $form.append($('<input type="hidden" name="stripe_token" />').val('none'));
          // Show the errors on the form
          $('.form-errors').text(response.error.message).addClass('visible');
        }
        else {
          // response contains id and card, which contains additional card details
          const token = response.id;

          // Insert the token into the form so it gets submitted to the server
          $form.append($('<input type="hidden" name="stripe_token" />').val(token));
          $donationSubmit.trigger('click');
        }
      });
    });
  });
}(jQuery, Drupal, drupalSettings));
