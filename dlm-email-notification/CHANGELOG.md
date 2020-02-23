#### 4.1.1: February 9, 2018
* Tweak: Added email field: user_email. Adds email address of logged in user.

#### 4.1.0: February 2, 2018
* Feature: Email fields in email can now be set via options (template override no longer required). Template file needs to be updated if overridden, backwards compatibilty is perserved. Old templates still work but this new feature won't work untill template file is updated. More information: https://www.download-monitor.com/extensions/email-notification/ 

#### 4.0.0: January 20, 2018
* Tweak: Made plugin compatible with Download Monitor 4.0
* Tweak: Replaced custom autoloader with Composer class map.
* Tweak: Implemented improved registering of extension.
* Tweak: Made email subject filterable.

### 1.2.0: April 19, 2016
* Moved static email template to an overridable template part.
* Added support for %USER_EMAIL%.

### 1.1.1: March 4, 2016
* Fixed typo in email.

### 1.1.0: August 3, 2015
* Added username of download requester to email.
* Added IP address of download requester to email.
* Added user agent of download requester to email.

### 1.0.1: May 31, 2015
* Changed inner function to regular class method.

### 1.0.0: May 25, 2015
* Initial Release