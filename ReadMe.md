# Paystand WooCommerce Checkout Plugin

WooCommerce Plugin to add Payments with Paystand checkout, allowing to use ACH, Card, Bank

## Getting Started

* Download code from repository https://github.com/paystand/paystand-woocommerce

### Prerequisites

* Wordpress 4+
* Wordpress developer account - used for deployment process
* Woocommerce 3.2+
* Paystand Customer Account
* PHP code editor
* SVN client - used for deployment process
* ngrok installed - used on webhook configuration  

### Installing

Install the plugin

* Go to wooCommerce code
* Make a zip of the folder woocommerce-paystand/ - e.g woocommerce-paystand.zip
* Then, from your WordPress administration panel, go to `Plugins > Add New`
* Choose woocommerce-paystand.zip from your desktop location
* Install and `Activate` pluging

### Setup & configuration

* In WordPress administration panel, go to `Woocommerce > Settings > Payments` and choose `Paystand (CC, Bank, ACH)`

* Fill the next fields with information provided in you paystand dashboard account (in your Paystand Dashboard go to `Integrations > API Configuration Values`)
    * Paystand Publishable Key
    * Paystand Customer Id
    * Paystand Client Id
    * Paystand Client Secret

### How to use
* Open Wordpress shop in your local env
* Add products to your cart
* Click on `Proceed to checkout`
* Fill all required fields on `Billing details`
* In your order summary choose `Paystand (CC, Bank, ACH)`
* Click on `Pay With Paystand`
* Fill the Paystand checkout fields


### Testing

Unit tests are implemented with WordPress Plugin Unit Test Infrastructure. To run the tests you need to follow the following steps:

1. Install the this plugin in your working WordPress instance (it must also contain WooCommerce plugin).
2. Download wp-cli (`curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar`) in your Wordpress plugins installation dir (within Docker if you are in docker). And `chmod +x` the downloaded file so that you can run it.
3. Install subversion (`apt update && apt install subversion`)
4. Install mariadb-client (`apt update && apt install mariadb-client`)
5. run test scaffolding script `./wp-cli.phar  scaffold plugin-tests woocommerce-paystand`. You may need to use `--allow-root` if running as root.
6. Copy tests  to the `woocommerce-paystand` plugin directory inside your wordpress Installation. The tests are in the tests directory.
7. Run test init script  `bin/install-wp-tests.sh wordpress_test root 'root_password' db latest`
8. Install PHP Unit (version [6.5.8](https://phar.phpunit.de/phpunit-6.5.8.phar) is the best version to use. 7.0 or older do not work at this time)
9. Run tests using phpUnit with  `phpunit`

### Wordpress documentation and standards

https://developer.wordpress.org/plugins/intro/
