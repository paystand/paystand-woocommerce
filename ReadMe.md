# Paystand WooCommerce Checkout Plugin

This repo contains the implementation of a WooCommerce Plugin to add Pay with Paystand functionality.

# Instructions
To install in WooCommerce create a zip from the `woocommerce-paystand` directory and use that to install as plugin in WordPress. 
You can also use `zip -r woocommerce-paystand.zip woocommerce-paystand/`
# Supported WooCommerce Version

Currently the minimum version we support is WooCommerce 3.2

# Naming Conventions

PHP varaible, classes and method naming conventions are all over the place (see [this stackoverflow post](https://softwareengineering.stackexchange.com/a/149321)).
The following conventions should be followed in this repo:

 - `ClassName`
 - `method_name`
 - `propertyName`
 - `function_name` (meant for global functions)
 - `$variable_name`

# Testing

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