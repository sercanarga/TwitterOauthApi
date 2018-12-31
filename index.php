<?php

include("oauth.php");
include("./modules/twitter_module.php");

$config = [
    'twitter'   => [
        'enabled' => true,
        'callback' => '',
        'keys' => [
            'key'  => 'srFixTitKV8f9WmJwF6VehhSF',
            'secret' => 'GqSR29NQ0RoDBQ8gywQrsLV3vXowgFsSIMmueVQPm7E4ALAkNH'
        ]
    ],
];

try {
    $oauth = new oauth($config);
    echo '<a href="'.twitter_oauth::$token_url.'">'.twitter_oauth::$token_url.'</a>';
    echo '<pre>';
    print_r(twitter_login::$user_info);
    echo '</pre>';
} catch (\Exception $e) {
    echo '<pre>';
    echo $e->getMessage();
    echo '</pre>';
}

?>