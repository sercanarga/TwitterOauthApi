# ![](https://www.fmpr.net/wp-content/plugins/social-media-feather/synved-social/image/social/regular/24x24/twitter.png) PHP Twitter Oauth & Post API v1.0
I am working on a project for the Twitter API usage, which integrates the CURL function.  The project's shortcomings have been listed under 'Features to Include'. You can support me in this by committing to the work.

## Usage:
### Include on page:
To be able to use it, you should download the `TwitterClass.php` folder and include it to your page as shown below.
```php
<?php
  session_start();
  include("TwitterClass.php");
?>
```
### Operation:
Type in your API data in the `CONSUMER_KEY` and `CONSUMER_KEY_SECRET` spaces within the Twitter variable.
In the friendships/create field you will need to type in the names for the API JSON file([Twitter API Page](https://developer.twitter.com/en/docs)). For example: `friendships/destory` etc.

|      JSON     |   Operation   |  Parameter |
| ------------- |:-------------:| ----------:|
|friendships/create|screen_name|sercanarga|
|statuses/update|status|Hello World!|
|||...|

In the array field you need to type in the parameters that you want.
The example shown below will make you follow the Twitter user named [sercanarga](https://twitter.com/sercanarga).
```php
$twitter = new Twitter('CONSUMER_KEY', 'CONSUMER_KEY_SECRET');
$status = $twitter->post('friendships/create', array('screen_name', 'sercanarga'));
if ($status == 1) {
  echo 'Success!';
} else {
  echo 'Error!';
}
```
## Features to include:
+ GET Method
+ ~~Oauth Method~~
+ ~~POST Method~~
