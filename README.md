# Drupal 8 Donation Form Exercise

## Installation
 - Clone repo git@github.com:aprice42/drupal-donation.git
 - Run composer install
 - Run drupal installer choosing standard profile
 - Enable TS Donations (ts_donations) module
    
## Add Stripe API keys
 - Visit /admin/config and click `Configure Donations` to add Stripe API keys

## Use the form
 - Visit the homepage to see the form
 - Submitting the form will add payments to your stripe dashboard
 - When using a stripe test account be sure to enter payment data using a card from: https://stripe.com/docs/testing and use any 3 numbers for CVC and an expiration date in the future.
 
## View Donation History
 - Visit /admin/config and click `Donations Report`
    

