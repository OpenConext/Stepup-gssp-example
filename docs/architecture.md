# Application architecture

This repository can be used for reference material or 
as a base project setup for new IdP SecondFactor application.

The SAML logic for receiving authentication request (AuthnRequest) and sending authentication response back is
placed inside the Symfony bundle [stepup-gssp-bundle](https://github.com/OpenConext/Stepup-gssp-bundle). The state of the
application is stored inside PHP sessions, each new request will invalidate the current session state.

# Locale user preference

The default locale is based on the user agent. When the user switches it's locale the selected preference is stored inside a
browser cookie (stepup_locale). The cookie is set on naked domain of the requested domain (for gssp.stepup.example.com this is example.com).

# Authentication and registration flows

The application provides internal (SpBundle) and a remote service provider. Instructions for this are given 
on the homepage of this example project [Homepage](https://gssp.stepup.example.com/app_dev.PHP/).

![flow](flow.png)
<!---
regenerate flow.png with `plantum1 docs/architecture.md` or with http://www.plantuml.com/plantuml
@startuml flow
actor User
participant "Service provider" as SP
box "Stepup GSSP example"
participant "GSSP Bundle" as IdP
participant "SecondFactor implementation" as App
end box
User -> SP: Register/Authenticate
SP -> IdP: Send AuthnRequest
activate IdP
IdP -> App: Redirect to SecondFactor endpoint
App -> App: <Your custom SecondFactor implementation>
App -> IdP: Redirect to SSO Return endpoint
IdP -> SP: AuthnRequest response
deactivate IdP
SP -> User: User registered/Authenticated
@enduml
--->
