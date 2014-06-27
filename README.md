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

To get more than 20 results for a single Yelp search
```php
require_once('yelp/yelp.php');

//get the following from http://www.yelp.com/developers/manage_api_keys
$consumerkey = 'enter your consumer key';
$consumersecret = 'enter your consumer secret';
$token = 'enter your token';
$tokensecret = 'enter your token secret';

$yelp = new Yelp($consumerkey,$consumersecret,$token,$tokensecret);
$yelp->parameters[term] = 'coffee';
$yelp->parameters[sort] = 1;
$yelp->parameters[location] = 'denver,co';
try {
	$yelp->query();
} catch (Exception $e) {
	print_r($e);
}
```
Note: setting the 'sort' parameter allows for getting up to 40 results.  The script does this automatically for you.


Break down your search into multiple smaller searches to get up to 200,000 results from Yelp per day.
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
The multiquery breaks down your search into a grid of smaller searches.  You can set this to be as broad as you like.  The only limit is Yelp's throttle of 10,000 queries per day, giving a conceivable (though unlikely) 200,000 results per day.  These queries can take a long time, so you should start small and do the math before plowing in.

Use `resume_multiquery()` to span your search over multiple days in order to obtain more results.


