# Ablr payment extension for Magento 2

## Installation

### Install using Composer (Recommended)

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

### Manual installation

Note that [MAGENTO_ROOT] refers to the root folder where Magento is installed.

1. Clone repository with extension:
   ```bash
   git clone https://github.com/ablr-com/ablr_ecom_magento2
   ```

2. Create directory `Ablr/Payment` in [MAGENTO_ROOT]/app/code/
   ```bash
   mkdir -p [MAGENTO_ROOT]/app/code/Ablr/Payment
   ```
   
3. Copy extension files to `Ablr/Payment` directory:
   ```bash
   cp ablr_ecom_magento2/* [MAGENTO_ROOT]/app/code/Ablr/Payment/
   ```   

4. Go to [MAGENTO_ROOT] and enter following commands to enable module:

   ```bash
   php bin/magento module:enable Ablr_Payment --clear-static-content
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

## Configure the Ablr extension

1. Log in to Magento Admin
2. Go to Stores > Configuration > Sales > Payment Methods > Ablr Gateway
3. Enter the Store ID and Secret API Key provided by Ablr
4. Sandbox is for testing on the staging environment. Remember to select "**No**" for Sandbox to start accepting real transactions on your live website).
