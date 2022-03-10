# Ablr payment extension for Magento

## Installation

### Composer

1. Go to Magento2 root folder
2. Enter following commands to install extension:

   ```bash
   composer require ablr-com/magento2
   ```

   Wait while dependencies are updated.

3. Enter following commands to enable extension:

   ```bash
   php bin/magento module:enable Ablr_Payment --clear-static-content
   php bin/magento setup:upgrade
   php bin/magento cache:clean
   ```

4. If Magento is running in "production" mode, then also execute:
   ```bash
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   ```
5. Configure extension as per configuration instructions


## Configuration
1. Log in to Magento Admin
2. Go to Stores > Configuration > Sales > Payment Methods > Ablr Gateway and configure settings
