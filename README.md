Yelp-API-PHP-Wrapper
====================

Very simple wrapper for the Yelp API v2.0.  


Basics
```php
require_once('yelp/yelp.php');

//get the following from http://www.yelp.com/developers/manage_api_keys
$consumerkey = 'enter your consumer key';
$consumersecret = 'enter your consumer secret';
$token = 'enter your token';
$tokensecret = 'enter your token secret';

$yelp = new Yelp($consumerkey,$consumersecret,$token,$tokensecret);
$yelp->parameters[term] = 'coffee';
$yelp->parameters[location] = 'denver,co';
try {
	$yelp->query();
} catch (Exception $e) {
	print_r($e);
}
```


How to get more than 20 results from Yelp
```php
require_once('yelp/yelp.php');

//get the following from http://www.yelp.com/developers/manage_api_keys
$consumerkey = 'enter your consumer key';
$consumersecret = 'enter your consumer secret';
$token = 'enter your token';
$tokensecret = 'enter your token secret';

$yelp = new Yelp($consumerkey,$consumersecret,$token,$tokensecret);
$yelp->parameters[category_filter] = 'coffee';
$yelp->parameters[sort] = 1;
try {
	$yelp->multiquery(39.742042,-104.987519, 40000,2);
} catch (Exception $e) {
	print_r($e);
}
```