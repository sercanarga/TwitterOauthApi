<?php
class Twitter
{
	public $api_url = 'https://api.twitter.com/1.1/';
	public $oauth_url = 'https://api.twitter.com/oauth';
	public $request_token = 'https://twitter.com/oauth/request_token';
	public $consumer_key;
	public $consumer_key_secret;
	public $oauth_token;
	public $oauth_token_secret;
	public $status_code = null;

	function __construct($_consumer_key, $_consumer_key_secret , $_oauth_token = null, $_oauth_token_secret = null) {
		$this->consumer_key = $_consumer_key;
		$this->consumer_key_secret = $_consumer_key_secret;
		if (!empty($_oauth_token)) {
			$this->oauth_token = $_oauth_token;
			$this->oauth_token_secret = $_oauth_token_secret;
		}
	}

	function getRequestToken($parameters = [], $url = '') {
		if (!empty($url)) $this->request_token = $url;
		$params = array(
			'oauth_version' => '1.0',
			'oauth_nonce' => time(),
			'oauth_timestamp' => time(),
			'oauth_consumer_key' => $this->consumer_key,
			'oauth_signature_method' => 'HMAC-SHA1'
		);
		$params = array_merge($params, $parameters);
		$keys = $this->url_encode(array_keys($params));
		$values = $this->url_encode(array_values($params));
		$params = array_combine($keys, $values);
		uksort($params, 'strcmp');
		foreach ($params as $k => $v) {
			$pairs[] = $this->url_encode($k).'='.$this->url_encode($v);
		}
		$concatenated_params = implode('&', $pairs);
		$base_string = 'GET&'.$this->url_encode($this->request_token).'&'.$this->url_encode($concatenated_params);
		$secret = $this->url_encode($this->consumer_key_secret).'&';
		$params['oauth_signature'] = $this->url_encode(base64_encode(hash_hmac('sha1', $base_string, $secret, true)));
		uksort($params, 'strcmp');
		foreach ($params as $k => $v) {
			$url_pairs[] = $k.'='.$v;
		}
		$concatenated_url_params = implode('&', $url_pairs);
		$url = $this->request_token.'?'.$concatenated_url_params;
		return $this->http($url);
    }

	function http($url, $post_data = null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		if (isset($post_data)) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		}
		$response = curl_exec($ch);
		$this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->last_api_call = $url;
		curl_close($ch);
		parse_str($response, $array_response);
		return $array_response;
    }

	function url_encode($input) {
		if (is_array($input)) {
			return array_map(array('Twitter', 'url_encode'), $input);
		} else if (is_scalar($input)) {
			return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
		} else {
			return '';
		}
	}
	
	public function post($method, $status) {
		if (isset($_SESSION['oauth_token'])) {
			unset($_SESSION['oauth_token']);
			$method_url = "$this->oauth_url/access_token";
			$params = array(
				'oauth_verifier' => $_GET['oauth_verifier'],
				'oauth_token' => $_GET['oauth_token']
			);
			$_oauth = $this->getRequestToken($params, $method_url);
		} else {
			$_oauth = $this->getRequestToken();
			$_SESSION['oauth_token'] = $_oauth['oauth_token'];
			$_SESSION['oauth_token_secret'] = $_oauth['oauth_token_secret'];
			$url = "$this->oauth_url/authenticate?oauth_token=" . $_oauth['oauth_token'];
			header("Location: $url");
			die();
		}
		foreach($status as $status_key => $status_value) {
			$this->status_code .= '&'.$status_key.'='.$status_value;
		}
		$twitter_version = '1.0';
		$sign_method = 'HMAC-SHA1';
		$url = $this->api_url.$method.'.json';
		$param_string = 'oauth_consumer_key='.$this->consumer_key.
						'&oauth_nonce='.time().
						'&oauth_signature_method='.$sign_method.
						'&oauth_timestamp='.time().
						'&oauth_token='.$_oauth['oauth_token'].
						'&oauth_version='.$twitter_version.
						$this->status_code;

		$base_string = 'POST&'.rawurlencode($url).'&'.rawurlencode($param_string);
		$sign_key = rawurlencode($this->consumer_key_secret).'&'.rawurlencode($_oauth['oauth_token_secret']);
		$signature 	= base64_encode(hash_hmac('sha1', $base_string, $sign_key, true));
		$curl_header = 'OAuth oauth_consumer_key='.rawurlencode($this->consumer_key).','.
						'oauth_nonce='.rawurlencode(time()).','.
						'oauth_signature='.rawurlencode($signature).','.
						'oauth_signature_method='.$sign_method.','.
						'oauth_timestamp='.rawurlencode(time()).','.
						'oauth_token='.rawurlencode($_oauth['oauth_token']).','.
						'oauth_version='.$twitter_version;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:'.$curl_header));
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->status_code);
		$twitter_post = json_decode(curl_exec($ch));
		$info = curl_getinfo($ch);
		$returncode = $info['http_code'];
		curl_close($ch);
		if($returncode == 200) {
			return true;
		} else {
			return false;
		}
	}
}
?>
