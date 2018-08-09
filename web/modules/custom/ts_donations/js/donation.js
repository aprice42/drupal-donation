/**
 * @file din homepage map.
 *
 * Defines behavior for the homepage map.
 */

(function ($, Drupal, drupalSettings) {

  $(function(){

    Stripe.setPublishableKey('pk_test_wOSvCSrfoODfnfLdWUDtenUo');
    const $donationSubmit = $('#ts-donation-form input[type="submit"]');
    $donationSubmit.on('click', function(e){
      e.preventDefault();
      const $form = $('form#ts-donation-form');
      Stripe.card.createToken($form, function(status, response) {
        if (response.error) {
          $form.append($('<input type="hidden" name="stripe_token" />').val('none'));
          // Show the errors on the form
          $('.payment-errors').text(response.error.message);
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
