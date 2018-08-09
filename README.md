# Drupal 8 Donation Form Exercise

## Installatiom
    clone repo
    composer install
    drush si --yes standard --site-name='TS Donations' --site-mail='no-reply@example.com' --account-name='system' --account-pass='pass' --account-mail='no-reply@example.com'
    drush en ts_donations
    
## Add Stripe API keys
    Visit /admin/config and click `Configure Donations` to add api keys

## Use the form
    Submit the form and payments should be seen in your stripe dashboard

## View Donation History
    Visit /admin/config and click `Donations Report`
    

