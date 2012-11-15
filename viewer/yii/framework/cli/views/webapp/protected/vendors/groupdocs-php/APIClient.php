<?php
/**
 * 	Wordnik.com's Swagger generic API client. This client handles the client-
 * 	server communication, and is invariant across implementations. Specifics of
 *	the methods and models for each application are generated from the Swagger
 *	templates.
 */


/* Autoload the model definition files */
/**
 *
 *
 * @param string $className the class to attempt to load
 */
function swagger_autoloader($className) {
	$currentDir = substr(__FILE__, 0, strrpos(__FILE__, '/'));
	if (file_exists($currentDir . '/' . $className . '.php')) {
		include $currentDir . '/' . $className . '.php';
	} elseif (file_exists($currentDir . '/models/' . $className . '.php')) {
		include $currentDir . '/models/' . $className . '.php';
	}
}
spl_autoload_register('swagger_autoloader');

interface RequestSigner {
    
    public function signUrl($url);
        
    public function signContent($requestBody, $headers);

}

class DefaultRequestSigner implements RequestSigner {
    
    public function signUrl($url){
        return $url;
	}
        
    public function signContent($requestBody, $headers){
        return $requestBody;
	}
}

class APIClient {

	public static $POST = "POST";
	public static $GET = "GET";
	public static $PUT = "PUT";
	public static $DELETE = "DELETE";

	/**
	 * @param RequestSigner $requestSigner RequestSigner implementation or omit to use DefaultRequestSigner which do nothing
	 */
	function __construct($requestSigner = null) {
		$this->signer = $requestSigner == null ? new DefaultRequestSigner() : $requestSigner;
	}


    /**
	 * @param string $resourcePath path to method endpoint
	 * @param string $method method to call
	 * @param array $queryParams parameters to be place in query URL
	 * @param array $postData parameters to be placed in POST body
	 * @param array $headerParams parameters to be place in request header
	 * @return unknown
	 */
	public function callAPI($apiServer, $resourcePath, $method, $queryParams, $postData,
		$headerParams) {

		$headers = array();
		
		$filename = false;
		if (empty($postData)){
			$headers[] = "Content-type: text/html";

		} else if (is_string($postData) && strpos($postData, "file://") === 0) {
			$filename = substr($postData, 7);
			$headers[] = "Content-type: ".self::getMimeType($filename);
			$headers[] = "Content-Length: ".filesize($filename);

		} else if (is_object($postData) or is_array($postData)) {
			$headers[] = "Content-type: application/json";
			$postData = json_encode(self::object_to_array($postData));
		}

		if (is_object($postData) or is_array($postData)) {
			$postData = json_encode($postData);
		}

		$url = $apiServer . $resourcePath;

		$timeoutSec = 0;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeoutSec);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		// return the result on success, rather than just TRUE
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

		if ($method == self::$GET) {
			if (! empty($queryParams)) {
				$url = ($url . '?' . http_build_query($queryParams));
			}
		} else if ($method == self::$POST) {
			if($filename){
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($curl, CURLOPT_TIMEOUT, 0);
				curl_setopt($curl, CURLOPT_PUT, true);
				curl_setopt($curl, CURLOPT_INFILE, fopen($filename, "rb"));
				curl_setopt($curl, CURLOPT_INFILESIZE, filesize($filename));
			} else {
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
			}
		} else if ($method == self::$PUT) {
			$json_data = json_encode($postData);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
		} else if ($method == self::$DELETE) {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
		} else {
			throw new Exception('Method ' . $method . ' is not recognized.');
		}

		curl_setopt($curl, CURLOPT_URL, self::encodeURI($this->signer->signUrl($url)));

		// Make the request
		$response = curl_exec($curl);
		$response_info = curl_getinfo($curl);

		// Handle the response
		if ($response_info['http_code'] == 0) {
			throw new Exception("TIMEOUT: api call to " . $url .
				" took more than " . $timeoutSec . "s to return" );
		} else if ($response_info['http_code'] == 200) {
			$data = json_decode($response);
		} else if ($response_info['http_code'] == 401) {
			throw new Exception("Unauthorized API request to " . $url .
					": ".$response );
		} else if ($response_info['http_code'] == 404) {
			$data = null;
		} else {
			throw new Exception("Can't connect to the api: " . $url .
				" response code: " .
				$response_info['http_code']);
		}

		return $data;
	}



	/**
	 * Take value and turn it into a string suitable for inclusion in
	 * the path or the header
	 * @param object $object an object to be serialized to a string
	 * @return string the serialized object
	 */
	public static function toPathValue($object) {
        if (is_array($object)) {
            return implode(',', $object);
        } else {
            return $object;
        }
	}


	/**
	 * Derialize a JSON string into an object
	 *
	 * @param object $object object or primitive to be deserialized
	 * @param string $class class name is passed as a string
	 * @return object an instance of $class
	 */
	public static function deserialize($object, $class) {

		if (gettype($object) == "NULL") {
	      	return $object;
	     }

    if (substr($class, 0, 6) == 'array[') {
      $sub_class = substr($class, 6, -1);
      $sub_objects = array();
      foreach ($object as $sub_object) {
        $sub_objects[] = self::deserialize($sub_object,
                                    			 $sub_class);
      }
      return $sub_objects;
    } elseif ($class == 'DateTime') {
      		return new DateTime($object);
		} elseif (in_array($class, array('string', 'int', 'float', 'bool'))) {
			settype($object, $class);
			return $object;
		} else {
			$instance = new $class(); // this instantiates class named $class
			$classVars = get_class_vars($class);
		}

		foreach ($object as $property => $value) {

			// Need to handle possible pluralization differences
			$true_property = $property;

			if (! property_exists($class, $true_property)) {
				if (substr($property, -1) == 's') {
					$true_property = substr($property, 0, -1);
					if (! property_exists($class, $true_property)) {
						trigger_error("class $class has no property $property"
							. " or $true_property", E_USER_WARNING);
					}
				} else {
					trigger_error("class $class has no property $property",
						E_USER_WARNING);
				}
			}

			$type = $classVars['swaggerTypes'][$true_property];
			if (in_array($type, array('string', 'int', 'float', 'bool'))) {
				settype($value, $type);
				$instance->{$true_property} = $value;
			} elseif (preg_match("/array<(.*)>/", $type, $matches)) {
				$sub_class = $matches[1];
				$instance->{$true_property} = array();
				foreach ($value as $sub_property => $sub_value) {
					$instance->{$true_property}[] = self::deserialize($sub_value,
						$sub_class);
				}
			} else {
				$instance->{$true_property} = self::deserialize($value, $type);
			}
		}
		return $instance;
	}

	public static function object_to_array($data) {
	    if (is_array($data) || is_object($data))
	    {
	        $result = array();
	        foreach ($data as $key => $value)
	        {
	            if(!is_null($value)){
	                $result[$key] = self::object_to_array($value);
	            }
	        }
	        return $result;
	    }
	    return $data;
	}

	public static function getMimeType($filename){
		$cont_type = null;
		if (function_exists('finfo_file')) {
	        $finfo = finfo_open(FILEINFO_MIME_TYPE);
	        $cont_type = finfo_file($finfo, $filename);
	        finfo_close($finfo);
	    } else {
	        $cont_type = mime_content_type($filename);
	    }
		return $cont_type;
	}

	public static function encodeURI($url) {
	    $reserved = array(
	        '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', 
	        '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
	    );
	    $unescaped = array(
	        '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
	        '%40'=>'@','%26'=>'&','%3D'=>'=',
	        //'%2B'=>'+',
	        '%24'=>'$', '%25'=>'%'
	    );
	    $score = array(
	        '%23'=>'#'
	    );
	    return strtr(rawurlencode($url), array_merge($reserved,$unescaped,$score));

	}
	
	public static function encodeURIComponent($str) {
	    $revert = array('%21'=>'!', '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')');
	    return strtr(rawurlencode($str), $revert);
	}

}


