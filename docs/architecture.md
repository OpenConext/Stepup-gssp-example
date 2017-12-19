# Application architecture

This repository can be used for reference material or 
as a base project setup for new IdP SecondFactor application.

The SAML logic for receiving authentication request (AuthnRequest) and sending authentication response back is
placed inside the Symfony bundle [stepup-gssp-bundle](https://github.com/OpenConext/Stepup-gssp-bundle). The state of the
application is stored inside php sessions, each new request will invalidate the current session state.

# Locale user preference

The default locale is based on the browser agent. When the user switch it's locale the selected preference is stored inside a
browser cookie (stepup_locale). The cookie is set on naked domain of the requested page (for gssp.stepup.example.com it is example.com).

# Authentication and registration flows

The application provides internal (SpBundle) and a remote service provider. Instructions for this are given 
on the homepage of this example project [Homepage](https://gssp.stepup.example.com/app_dev.php/).
