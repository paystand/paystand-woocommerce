# Paystand WooCommerce Checkout Plugin

This repo contains the implementation of a WooCommerce Plugin to add Pay with Paystand functionality.

# Instructions
To install in WooCommerce create a zip from the `woocommerce-paystand` directory and use that to install as plugin in WordPress. 
You can also use `zip -r woocommerce-paystand.zip woocommerce-paystand/`
# Supported WooCommerce Version

Currently the minimum version we support is WooCommerce 3.0

# Naming Conventions

PHP varaible, classes and method naming conventions are all over the place (see [this stackoverflow post](https://softwareengineering.stackexchange.com/a/149321)).
The following conventions should be followed in this repo:

 - `ClassName`
 - `method_name`
 - `propertyName`
 - `function_name` (meant for global functions)
 - `$variable_name`

#Testing

Unit tests are implemented with WordPress Plugin Unit Test Infrastructure. To run the tests you need to follow the following steps:

1. Install WordPress (you can use Docker and Docker compose. See instructions [here](https://docs.docker.com/compose/wordpress/#define-the-project))
2. Install WooCommerce
3. Install the Plugin in your newly created WordPress instance.
4. Download wp-cli (`curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar`) in your Wordpress plugins installation dir (within Docker if you are in docker).
5. Copy tests  to the `woocommerce-paystand` plugin directory inside your wordpress Installation.
6. Install subversion (`apt update && apt install subversion`)
7. Install mariadb-client (`apt update && apt install mariadb-client`)
8. Run test init script  `bin/install-wp-tests.sh wordpress_test root 'root_password' db latest`
9. Install PHP Unit (version 6.5.8 is the best version to use. 7.0 or older do not work at this time)
10. Run tests using phpUnit with  `phpunit`