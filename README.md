# Ablr payment extension for Magento 2

## Installation

### Install using Composer

1. Open Command Line Interface (CLI) and navigate to the Magento directory on your server
2. Run the following command to install the Ablr extension:

   ```bash
   composer require ablr-com/magento2
   ```

3. Enter following commands to enable the Ablr extension:

   ```bash
   # Enable Ablr_Payment module
   php bin/magento module:enable Ablr_Payment --clear-static-content
   # Update the database schema and data
   php bin/magento setup:upgrade
   ```

4. If Magento is running in "production" mode, then also execute:

   ```bash
   # Compile dependency injection code
   php bin/magento setup:di:compile
   # Deploy static content
   php bin/magento setup:static-content:deploy
   # Clean the cache
   php bin/magento cache:clean
   ```

## Configure the Ablr extension

1. Log in to Magento Admin
2. Go to Stores > Configuration > Sales > Payment Methods > Ablr Gateway
3. Enter the Store ID and Secret API Key provided by Ablr
4. Sandbox is for testing on the staging environment. Remember to select "**No**" for Sandbox to start accepting real transactions on your live website).
