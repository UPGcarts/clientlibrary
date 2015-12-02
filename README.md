#Payco Client Library for PHP#

Client Library for the Payco API for php.

##Current Issues##
Currently there are issues with some of the calls these are:
 * CreateTransaction : The url field is not returned in the response for Hosted Payment Requests
 * GetCaptureStatus : Has issues on the API
 * UpdateTransaction : Currently Incomplete

##Using the Library##

###Config Object###
The API requires configuration to work correctly. All classes and methods that require config can be passed a populated instance of
Upg\Library\Config.

The config object should be fully populated at instantiation by providing an associative array.
```php
$configData = array('merchantID' => 1, 'storeID' => 1);
$config = new Upg\Library\Config($configData);
```
The fields for the config that must be provided are:

 * ['merchantPassword'] string This is the merchant password for mac calculation
 * ['merchantID'] string This is the merchantID assigned by PayCo.
 * ['storeID'] string This is the store ID of a merchant.
 * ['logEnabled'] bool Should logging be enabled
 * ['logLevel'] int Log level See class constants for possible values
 * ['logLocationMain'] string Main log location file path
 * ['logLocationRequest'] string Log location file path for API requests
 * ['defaultRiskClass'] string Default risk class
 * ['defaultLocale'] string Default locale see (http://www.manula.com/manuals/payco/payment-api/2.0/en/topic/supported-languages)
 * ['sendRequestsWithSalt'] bool Automatically add salt to requests. In live this should be set to true and not false. However, for testing this can be false. By default this will be true if not specified.
 * ['baseUrl'] string Base URL of requests that should contain https://www.payco-sandbox.de/2.0 or https://www.pay-co.net/2.0 

###Making the API request###
The Library for requests is split in three to parts. The Upg\Library\Request contain the request classes.
Upg\Library\Request\Objects Contain classes for the JSON objects that are documented in the API docs.
If a request has a property that requires JSON object then for that property please pass in the appropriately populated
Upg\Library\Request\Objects class.

All properties in the request and json objects have getters and setters. For example, to set a field called userType on
the request or object you would call setUserType and to get it you would call getUserType.

####Notes on Date fields####
Any field in the Requests and Json Objects that require a Date should be passed a php DateTime object - even if the field only requires month and year. For fields that require only a month and year such as the validity on the payment Instrument, please look at [DateTime::setDate](http://php.net/manual/en/datetime.setdate.php).
Simply running the code like this would give you a DateTime object to populate the field
```php
$expiryMonth = 2
$expiryYear = 2020
$date = new \DateTime();
$date->setDate($expiryYear, $expiryMonth, 1);
```
The serializer will serialize the date to a correctly formatted string for the request.

####Notes on Amount Fields####
Any field that requires a JSON amount fields should be provided the Upg\Library\Request\Objects\Amount object.
This object has three properties:

 * amount: Full amount (subtotal+tax) in the lowest currency unit.
 * vatAmount: The amount of VAT if available in the lowest currency unit
 * vatRate: If a vatAmount is provided please provide details of the VAT rate up to 2 decimal places.
 
All amount values must in the lowest currency unit (i.e. Cents, Pence etc). So for example a 10 Euro transaction with 20% VAT would need to be populated:

 * amount: 1200
 * vatAmount: 200
 * vatRate: 20

####Sending the request####

Once you have populated a request object with the appropriate values simply instantiate an instance of a Upg\Library\Api
class for the appropriate method call. Pass in a config object and the request you want to send. Then, calling the 
sendRequest() method will send the response. Raise any exception or provide a success response.

The exceptions which can be raised are in Upg\Library\Api\Exception. For any parsed responses you will have access to
an Upg\Library\Response\SuccessResponse or Upg\Library\Response\FailureResponse object. The success object is returned if no exception is thrown.
The failure object is returned in Upg\Library\Api\Exception\ApiError exception.

The response object has two types of properties: Fixed properties such as the resultCode which are in every request, and Dynamic properties
such as allowedPaymentMethods which are not in every request. To access a property that is Fixed or Dynamic, simply use the following:
```php
$response->getData('resultCode');

$response->getData('allowedPaymentMethods');

```

If any response contains json objects and array of objects then the library will attempt to serialize them in to Upg\Library\Request\Objects classes.
The properties names in responses that will be serialized are as follows:
 * allowedPaymentInstruments, paymentInstruments : Array of Upg\Library\Request\Objects\PaymentInstrument
 * billingAddress, shippingAddress : Upg\Library\Request\Objects\Address
 * amount : Upg\Library\Request\Objects\Amount
 * companyData : Upg\Library\Request\Objects\Company
 * paymentInstrument : Upg\Library\Request\Objects\PaymentInstrument
 * userData : Upg\Library\Request\Objects\Person

For example, in the getUser API call in the response, the following properties will be the following objects 
 * companyData field would be an Upg\Library\Request\Objects\Company object
 * userData field would be an Upg\Library\Request\Objects\Person object
 * billingAddress, shippingAddress would be Upg\Library\Request\Objects\Address objects

All Mac validation/calculation for responses and requests are handled by the library and if these fail an exception will be raised.

All variable that are not ISO values are defined in classes as constants for you to use in the request.
For possible fixed field values please see the following:

 * locale: Upg\Library\Locale\Codes
 * riskClasses: Upg\Library\Risk\RiskClass
 * userType: Upg\Library\User\Type
 * salutation: Upg\Library\Request\Objects\Person::SALUTATIONFEMALE Upg\Library\Request\Objects\Person::SALUTATIONMALE
 * companyRegisterType: Upg\Library\Request\Objects\Company
 * paymentInstrumentType: Upg\Library\Request\Objects\PaymentInstrument
 * issuer: Upg\Library\Request\Objects\PaymentInstrument
 
The Library implements stubs for all the methods except for registerMerchant as at this time UPG is restricting this to authorised parties only.

###Handling Callback###

For the reserve API call you may be provided a callback from the API as a POST/GET request. For this the client library implements a helper for you to use: Upg\Library\Callback\Handler.

This takes in the following for the constructor:
 * $config: The config object for the integration
 * $data: The data from the post\get variables which should be an associated array containing contain the following:
   * merchantID
   * storeID
   * orderID
   * resultCode
   * merchantReference : Optional field
   * message : Optional field
   * salt
   * mac
   * $processor: An instance of an object that implements the Upg\Library\Callback\ProcessorInterface interface, which the method will invoke after validation

The processor should implement two methods sendData which the handler uses to pass data to the processor to use. Another method called
Run, which will get invoked to handle call back processing should return a string which contains a URL where the user should be redirected to
after Payco has processed them.

To run the handler simply run the run method on the object. Please note the following exceptions can be raised in which case the store must still send a URL, but respond with a non 200 result code to indicate there has been an issue. The following exceptions may be raised:
 * Upg\Library\Callback\Exception\ParamNotProvided : If a required parameter is not provided
 * Upg\Library\Callback\Exception\MacValidation : If there is a MAC validation issue with the callback parameters

###Handling MNS notification###

For the MNS notification the API provides a helper class to validate MNS notification. This class is Upg\Library\Mns\Handler. It takes the following as a constructor:

  * $config: The config object for the integration
  * $data: The data from the post\get variables which should be an associated array of the MNS callback
  * $processor: An instance of an object which implements the Upg\Library\Mns\ProcessorInterface interface which the method will invoke after validation.
 
The processor object should implement sendData to get data from the handler and a run method which executes your callback after successful validation.

The processor call back should avoid processing the request, instead it should save it to a database for asynchronous processing via a cron script.

Please note the MNS call must always return a 200 response to Payco otherwise no other MNS would be sent until a given MNS notification is sent a 200 response.

###Working with PayCoBridge.js###
Please note this plugin does not provide any javascript libraries for the paybridge. Integrations using paybridge are expected to implement the javascript library. However, this library can be used to implement the server side functionality for any paybridge integrations, using PHP on the backend.

If you use the handler class to save a MNS to the database for later processing you can assume the MNS is perfectly valid with out checking the MAC.

##Working on the plugin##

Please note when working on this plugin all code should be done to PRS2 standard. To install code sniffer with php storm:

1. Run the following.
    ``` sh
    composer global require 'squizlabs/php_codesniffer=*'
    ```

2. In phpstorm go to File->Settings then in the prompt go to Languages & Framework -> PHP -> Code Sniffer
3. On configuration option click the ... button and in that prompt point phpstorm to the path for code sniffer
4. In PHP storm set the code style by going to Editor -> Code Style -> PHP
5. In the setting click on Set From and go to Predefined Style -> PSR1/PSR2
6. Click on OK