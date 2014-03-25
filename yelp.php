<?php

class Yelp
{
	// Set your keys here
	private $consumer_key 		= NULL;
	private $consumer_secret 	= NULL;
	private $token 				= NULL;
	private $token_secret 		= NULL;
	public $api					= 'search';
	public $parameters 			= NULL; #see http://www.yelp.com/developers/documentation/v2/search_api

	private $response			= array();

	public function __construct($consumer_key, $consumer_secret, $token, $token_secret)
	{
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		$this->token = $token;
		$this->token_secret = $token_secret;
	}

	public function query(){
		$this->validate();
		$this->query_curl();
		$this->get_response();
	}

	public function multiquery($centerlat, $centerlng, $side, $dividers){
		//the query will cover a square area with each side having a length of $side (meters)
		//$dividers are the number od divisions to the square horizontallly and vertically 
		//(ex: $dividers=1 == fourths, $dividers=2 == ninths, $dividers=3 == sixteenths, etc)
		if (!is_int($dividers) || ($dividers<=0)) 
			throw new Exception("$dividers must be an integer greater than zero");
		$step = $side/($dividers+1);
		if (($step) > 20000 )
			throw new Exception("the subdivisions must not have sides greater than 20,000m");
		$p = $this->parameters;
		if (isset($p[bounds]) || isset($p[ll]) || isset($p[cll]) || isset($p[location])) {
			throw new Exception("Parameters for bounds, ll, cll, and location shouldn't be set");
		}
		$this->validate();
		$lati = $this->meters2lat($step);
		$grid = array();
		for ($i=($dividers+1)/2*-1; $i < $dividers/2; $i++) { //will this work with decimals?
			$swlat = $centerlat + $i*$lati;  //this should be a function to account for crossing a meridian/equator
			$nelat = $swlat + $lati;  //this should be a function to account for crossing a meridian/equator
			$slngi = $this->meters2lng($step,$swlat);
			$nlngi = $this->meters2lng($step,$nelat);
			for ($j=($dividers+1)/2*-1; $j < $dividers/2; $j++) { 
				$swlng = $centerlng + $j*$slngi;  //this should be a function to account for crossing a meridian/equator
				$nelng = $swlng + $nlngi;  //this should be a function to account for crossing a meridian/equator
				$this->parameters[bounds] = array('sw_latitude'=>$swlat,'sw_longitude'=>$swlng,'ne_latitude'=>$nelat,'ne_longitude'=>$nelng);
				$this->query_curl();
				$grid[] = $this->response;
			}
		}
		$this->response = $grid;
		$this->get_response();
	}

	public function get_response(){
		// Print it for debugging
		print_r($this->response);

		return $this->response;
	}

	private function validate(){
		switch ($this->api) {
			case 'search':
				$this->validate_search(); 
				break;
			default:
				throw new Exception('You attempted to use an API that is either a) not supported by this wrapper, or b) not supported by Yelp');
				break;
		}
	}

	private function validate_search(){
		$error = false;
		foreach ($this->parameters as $key => $parameter) {
			switch ($key) {
				case 'limit':
					if (!is_int($parameter)) $error .= "'limit' parameter must be an integer.  "; break;
				case 'offset':
					if (!is_int($parameter)) $error .= "'offset' parameter must be an integer.  "; break;
				case 'sort':
					if (!in_array($parameter, array(0,1,2))) $error .= "'sort' parameter must 0, 1, or 2.  "; break;
				case 'radius_filter':
					if (!is_int($parameter)) $error .= "'radius_filter' parameter must be an integer.  "; break;
				case 'deals_filter':
					if (!is_bool($parameter)) $error .= "'deals_filter' parameter must be a boolean.  "; break;
				case 'bounds':
					if (!is_array($parameter)) $error .= "'bounds' parameter must be an array.  "; 
					$arr = array(	'sw_latitude' => $parameter[sw_latitude], 
									'sw_longitude' => $parameter[sw_longitude],
									'ne_latitude' => $parameter[ne_latitude], 
									'ne_longitude' => $parameter[ne_longitude]);
					foreach ($arr as $k => $v) {
						if ($v == NULL) $error .= "'bounds' ".$k." needs a value.  ";
						if (!is_numeric($v)) $error .= "'bounds' ".$k." must be numeric.  ";
					}
					break;
				case 'll':
					if (!is_array($parameter)) $error .= "'ll' parameter must be an array.  ";
					if (in_array(NULL, array($parameter[latitude], $parameter[longitude]) )) 
						$error .= "'ll' requires both latitude and longitude.  ";
					foreach ($parameter as $k => $v) {
						if (!is_numeric($v)) $error .= "'ll' ".$k." must be numeric.  ";
					}
					break;
				case 'cll':
					if (!is_array($parameter)) $error .= "'cll' parameter must be an array.  ";
					if (in_array(NULL, array($parameter[latitude], $parameter[longitude]) )) 
						$error .= "'cll' requires both latitude and logitude.  ";
					foreach ($parameter as $k => $v) {
						if (!is_numeric($v)) $error .= "'cll' ".$k." must be numeric.  ";
					}
					break;
				case 'term':
				case 'category_filter':
				case 'cc':
				case 'lang':
				case 'location':
					break; //let Yelp decide if these parameters are valid
				default:
					$error .= "You're trying to set parameter '".$key."', but that isn't supported.";
					break;
			}
		}
		if ($error) {
			throw new Exception($error);
		}
	}

	private function query_curl(){
		$data = json_decode($this->query_curl_helper());
		//sort mode 1 or 2 allows an additional 20 businesses past the initial limit of the first 20 results
		//code below retreives the additional results automatically.
		$sort = $this->parameters[sort];
		if (($data->total > 20) && isset($sort) && (in_array($sort, array(1,2)))) { 
			$this->parameters[offset] = 20;
			$extra = json_decode($this->query_curl_helper());
			unset($this->parameters[offset]);
			$data->businesses = array_merge($data->businesses,$extra->businesses);
		}

		// Handle Yelp response data
		$this->response = $data;
	}

	private function query_curl_helper(){
		$url = $this->requestBuilder();
		require_once ('yelp-api/v2/php/lib/OAuth.php');
		// Token object built using the OAuth library
		$oauthtoken = new OAuthToken($this->token, $this->token_secret);

		// Consumer object built using the OAuth library
		$oauthconsumer = new OAuthConsumer($this->consumer_key, $this->consumer_secret);

		// Yelp uses HMAC SHA1 encoding
		$signature_method = new OAuthSignatureMethod_HMAC_SHA1();

		// Build OAuth Request using the OAuth PHP library. Uses the consumer and token object created above.
		$oauthrequest = OAuthRequest::from_consumer_and_token($oauthconsumer, $oauthtoken, 'GET', $url);

		// Sign the request
		$oauthrequest->sign_request($signature_method, $oauthconsumer, $oauthtoken);

		// Get the signed URL
		$signed_url = $oauthrequest->to_url();

		// Send Yelp API Call
		$ch = curl_init($signed_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$data = curl_exec($ch); // Yelp response
		curl_close($ch);
		return $data;
	}

	private function requestBuilder(){
		$url = 'http://api.yelp.com/v2/';
		$query_array = NULL;
		foreach ($this->parameters as $key => $parameter) {
			if (is_array($parameter)) {
				$query_array[] = $this->requestBuilder_arrayhelper($key,$parameter);
			}else{
				$query_array[] = $key . '=' . urlencode($parameter);
			}
		}

		$querystring = '';
		foreach ($query_array as $k => $v)
		{
			if ($querystring != '')
			{
				$querystring .= '&';
			}

			$querystring .= $v;
		}
		return $url . $this->api . '?' . $querystring;
	}

	private function requestBuilder_arrayhelper($key, $parameter){
		switch ($this->api) {
			case 'search':
				return $this->requestBuilder_arrayhelper_search($key, $parameter); break;
			default:
				# the switch here should match the validate() switch and default should never occur
				break;
		}
	}

	private function requestBuilder_arrayhelper_search($key, $parameter){
		$ret = $key . '=';
		switch ($key) {
			case 'bounds':
				return $ret . $parameter[sw_latitude] . ',' . $parameter[sw_longitude] . '|' . $parameter[ne_latitude] . ',' . $parameter[ne_longitude];
				break;
			case 'll':
			case 'cll':
				return $ret . implode(',', $parameter);
				break;
		}
	}

	/**
	 * Returns the longitude equal to a given distance (meters) at a given latitude
	 */
	private function meters2lng($meters,$latitude){
	    return $meters/(cos(deg2rad($latitude))*40075160/360);
	}

	/**
	 * Returns the latitude equal to a given distance (meters)
	 */
	private function meters2lat($meters){      
	    return $meters/(40075160/360);
	}

}
?>
