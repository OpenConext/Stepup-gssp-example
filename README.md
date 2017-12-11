Stepup-gssp-example
===================

<a href="#">
    <img src="https://travis-ci.org/OpenConext/Stepup-gssp-bundle.svg?branch=master" alt="build:">
</a></br>

Example Generic SAML Stepup Provider.

How to create your own Stepup Provider
======================================

There are two ways to approach this. 

Copy this GSSP example repository
---------------------------------

One of the benefits of using this repository is that it contains many pre-configured tools:

* Metrics & test tooling [testing.md](./docs/testing.md)
* Development environment provisioned by Vagrant 
* Pre-configured travis.yml for CI integration
* Default SurfContext styling [frontend_tooling.md](./docs/frontend_tooling.md)

1) Clone and checkout this repository
2) Change the project configuration variables:
    * composer.json name and description
    * this readme.md file
    * Replace 'gssp.stepup.example.com' in all files with your own hostename
3) Install the copied project. (See [Development environment](#) section of this README.md file)
4) Implement your authentication & registration logic in DefaultController::registrationAction and DefaultController::authenticateAction. 
5) Feel free to rename and change this example clone for your needs.

Install from a clean or exiting symfony project
------------------------------------

1) [Install symphony](http://symfony.com/doc/current/setup.html) 
2) Follow the instructions from the [GSSP bundle](https://github.com/OpenConext/Stepup-gssp-bundle)

Development environment
======================

To get started, first setup the development environment. The dev env is a virtual machine. Every task described here is required to run
from that machine.  

Requirements
-------------------
- ansible 2.x
- vagrant 1.9.x
- vagrant-hostsupdater
- Virtualbox
- ansible-galaxy

Install
=======

``` ansible-galaxy install -r ansible/requirements.yml -p ansible/roles/ ```

``` vagrant up ```

Go to the directory inside the VM:

``` vagrant ssh ```

``` cd /vagrant ```

Install composer dependencies:

``` composer install ```

Build frond-end assets:

``` composer install ```

``` composer encore dev ``` or ``` composer encore prod ``` for production 

If everything goes as planned you can go to:

[https://gssp.stepup.example.com](https://gssp.stepup.example.com)

Debugging
---------

Xdebug is configured when provisioning your development Vagrant box. 
It's configured with auto connect IDE_KEY=phpstorm.

Tests and metrics
======================

To run all required test you can run the following commands from the dev env:

``` 
    ./bin/bootstrap_phantomjs.sh
    composer test 
```

Every part can be run separately. Check "scripts" section of the composer.json file for the different options.

Other resources
======================

 - [Developer documentation](docs/index.md)
 - [Issue tracker](https://www.pivotaltracker.com/n/projects/1163646)
 - [License](LICENSE)
