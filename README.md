Stepup-gssp-example
===================

<a href="#">
    <img src="https://travis-ci.org/OpenConext/Stepup-gssp-bundle.svg?branch=master" alt="build:">
</a></br>

Example Generic SAML Stepup Provider.

Development environment
======================

To get started, first setup the development environment. The dev. env. is a virtual machine. Every task described is run
from that machine.  

Requirements
-------------------
- ansible 2.x
- vagrant 1.9.x
- vagrant-hostsupdater
- Virtualbox
- ansible-galaxy

Install
-------------------

``` ansible-galaxy install -r ansible/requirements.yml -p ansible/roles/ ```

``` vagrant up ```

Go to the directory inside the VM:

``` vagrant ssh ```

``` cd /vagrant ```

Install composer dependencies:

``` composer install ```

If everything goes as planned you can directly go to:

https://gssp.stepup.example.com

Debugging
-------------------
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
