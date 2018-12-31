<?php
class twitter_oauth {
    private $config = [
        'request_token' => 'https://twitter.com/oauth/request_token',
        'access_token' => 'https://api.twitter.com/oauth/access_token',
    ];
    static $token_url = NULL;

    function __construct($k, $s) {
        if (!isset($_GET['oauth_token']) && !isset($_GET['oauth_verifier'])) {
            $oauth_hash = [
                // 'oauth_callback' => 'http://localhost/twitter/index.php?step3',
                'oauth_consumer_key' => $k,
                'oauth_nonce' => time(),
                'oauth_timestamp' => time(),
                'oauth_version' => '1.0',
                'oauth_signature_method' => 'HMAC-SHA1'
            ];
            uksort($oauth_hash, 'strcmp');
            $param_pairs =
                implode('&', array_map(
                    function ($v, $k) {
                        return sprintf('%s=%s', $k, $v);
                    }, $oauth_hash, array_keys($oauth_hash)
                ));

            $base = [
                'POST',
                rawurlencode($this->config['request_token']),
                rawurlencode($param_pairs)
            ];
            $base = implode('&', $base);

            $oauth_hash['oauth_signature'] = rawurlencode(base64_encode(hash_hmac('sha1', $base, $s.'&', true)));
            uksort($oauth_hash, 'strcmp');

            $param_header =
                implode(',', array_map(
                    function ($v, $k) {
                        return sprintf('%s=%s', $k, $v);
                    }, $oauth_hash, array_keys($oauth_hash)
                ));
            $ch = curl_init();
            $c_header[] = 'Authorization: OAuth '.$param_header;
            curl_setopt($ch, CURLOPT_URL, $this->config['request_token']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $c_header);
            parse_str(curl_exec($ch), $result);
            curl_close($ch);
            if (@isset($result['oauth_token'])) {
                twitter_oauth::$token_url = 'https://api.twitter.com/oauth/authenticate?oauth_token='.$result["oauth_token"];
            } else {
                throw new Exception(print_r($result, true));
            }
        } else {
            $oauth_hash = [
                'oauth_consumer_key' => $k,
                'oauth_nonce' => time(),
                'oauth_timestamp' => time(),
                'oauth_version' => '1.0',
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_token' => $_GET['oauth_token']
            ];
            uksort($oauth_hash, 'strcmp');

            $param_pairs =
                implode('&', array_map(
                    function ($v, $k) {
                        return sprintf('%s=%s', $k, $v);
                    }, $oauth_hash, array_keys($oauth_hash)
                ));

            $base = [
                'POST',
                rawurlencode($this->config['request_token']),
                rawurlencode($param_pairs)
            ];
            $base = implode('&', $base);

            $oauth_signature = rawurlencode(base64_encode(hash_hmac('sha1', $base, $s.'&', true)));

            $oauth_hash['oauth_signature'] = $oauth_signature;
            uksort($oauth_hash, 'strcmp');

            $param_header =
                implode(',', array_map(
                    function ($v, $k) {
                        return sprintf('%s=%s', $k, $v);
                    }, $oauth_hash, array_keys($oauth_hash)
                ));

            $post_field = "oauth_verifier={$_GET['oauth_verifier']}";

            $ch = curl_init();
            $c_header[] = 'Authorization: OAuth '.$param_header;
            $c_header[] = 'Content-Length: '. strlen($post_field);
            $c_header[] = 'Content-Type: application/x-www-form-urlencoded';

            curl_setopt($ch, CURLOPT_URL, $this->config['access_token']);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_field);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $c_header);
            parse_str(curl_exec($ch), $result);
            curl_close($ch);
            new twitter_login($k, $s, @$result['oauth_token'], @$result['oauth_token_secret']);
        }
    }
}

class twitter_login {
    private $config = [
        'api_url' => 'https://api.twitter.com/1.1/',
        'verify_credentials' => 'account/verify_credentials.json',
    ];
    static $user_info = NULL;

    function __construct($k, $s, $u_k, $u_s) {
        $oauth_hash = [
            'oauth_consumer_key' => $k,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $u_k,
            'oauth_version' => '1.0'
        ];
        $oauth_hash = http_build_query($oauth_hash);

        $base = [
            'GET',
            rawurlencode($this->config['api_url'].$this->config['verify_credentials']),
            rawurlencode($oauth_hash)
        ];
        $base = implode('&', $base);

        $key = [
            rawurlencode($s),
            rawurlencode($u_s)
        ];
        $key = implode('&', $key);

        $signature = rawurlencode(base64_encode(hash_hmac('sha1', $base, $key, true)));
        $oauth_header = [
            'oauth_consumer_key' => $k,
            'oauth_nonce' => time(),
            'oauth_signature' => $signature,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_token' => $u_k,
            'oauth_version' => '1.0'
        ];
        $oauth_header =
                    implode(',', array_map(
                        function ($v, $k) {
                            return sprintf('%s="%s"', $k, $v);
                        },
                        $oauth_header,
                        array_keys($oauth_header)
                    ));

        $c = curl_init();
        $c_header = array("Authorization: Oauth {$oauth_header}", 'Expect:');
        curl_setopt($c, CURLOPT_HTTPHEADER, $c_header);
        curl_setopt($c, CURLOPT_HEADER, false);
        curl_setopt($c, CURLOPT_URL, $this->config['api_url'].$this->config['verify_credentials']);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        $result = json_decode(curl_exec($c));
        if (@isset($result->id)) {
            twitter_login::$user_info = $result;
        } else {
            throw new Exception(print_r($result, true));
        }
        curl_close($c);
    }
}

?>