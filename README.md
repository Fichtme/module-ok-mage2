Magento 2 OK Payment module
==========================
This is a Magento 2 composer plugin to implement OK checkout and authentication functionality.

Original project can be found: https://github.com/okitcom/ok-lib-magento-2

Documentation
-------------

* [Installation](#installation)
* [Configuration](#configuration)
* [Credits](#credits)

## Installation

Install plugin with composer:
```bash
composer req notive/module-ok-payment
```

After succesful composer installation run:

```bash
bin/magento module:enable Okitcom_OkLibMagento
bin/magento setup:upgrade 
bin/magento setup:di:compile
bin/magento cache:clean
```

For more information follow the magento installation guide:
[General CLI installation](https://devdocs.magento.com/extensions/install/)

## Configuration

After the installation was successful, the plugin's functionality is disabled by default. In order 
 to set it up login to the magento admin console navigate to:
 
```
'Stores' > 'Configuration' > 'SERVICES' > 'OK'
```

Choose the production environment and fill in the 'Open key' and 'Cash key' provided by OK support.

## Using the OK Cash webhook
The OK Cash service provides a functionality to notify the website when a transaction has reached a 
 final state in the form of a webhook.

The plugin automatically provides an endpoint in the website to listen for callbacks from OK. 
 However, the url of this endpoint must be provided in the Cash service in OK Works. The url must 
 use `https` and looks as follows:
```
Template: {base_url_secure}/oklib/callback/cash
Example:  https://magento2.ok.app/oklib/callback/cash
```

## Credits

- [Henny Krijnen](https://github.com/Fichtme)
- [All Contributors](https://github.com/notive/module-ok-payment/graphs/contributors)
- [Original Contributors](https://github.com/okitcom/ok-lib-magento-2/graphs/contributors)

*Please add your name to the list whe doing a pull request.*
