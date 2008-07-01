<?php
/*  First, you have to include some files :
/     * The Clearbricks _common.php file
/     * The Tumblr class itself
/     * The UTF8 HTML Decode class (used to clean up some non-standards entities)
/*/
require dirname(__FILE__).'/clearbricks/_common.php';
require dirname(__FILE__).'/class.tumblr.php';
require dirname(__FILE__).'/class.utf8htmldecode.php';

/*  Now you can initiate the Tumblr object with the ID of the Tumblelog you want read from as param.
/
/   function __construct($tumblrId = null)
/      * Initialize the Tumblr object, take a Tumblr ID as param.
/*/
$tumblrObj = new tumblr('saymonz');

/*  Now, it's time to do some requests from this API. This code will request, in the order:
/      * 3 video posts
/      * All regular posts
/      * The posts with the ID 39185133
/
/   function getPosts($start = 0,$num = 20,$type = null)
/      * Request $num $type posts starting from $start.
/      * Take posts of all types if $type = null.
/
/   function getAllPosts($type = null)
/      * Request all $type posts
/      * Take posts of all types if $type = null.
/
/   function getPost($id = null)
/      * Request the post with the ID $id.
/*/
$tumblrObj->getPosts(0,3,'video');
$tumblrObj->getAllPosts('regular');
$tumblrObj->getPost(39185133);

/*  Sort the array in any logical order could be interesting at this point.
/
/   function sortArray($chrono = false)
/      * Sort the array, in chronological order (older first) if $chrono = true. Newer posts first else.
/*/
$tumblrObj->sortArray();

/*  You're quite done! Now, you can get the array that contain the result.
/
/   function getArray()
/      * Return the array-formated content of the requests made to the API.
/      * Just get on post of each type and print_r the array to see how it's formatted.
/*/
$tumblrArr = $tumblrObj->getArray();

header('Content-Type: text/plain; charset=utf-8');
print_r($tumblrArr);

/*  That's all. If you want to do new requests, use this to flush all the datas.
/
/   function flush($tumblrId = null)
/      * Flush all the data of previous requests.
/      * If $tumblrId is specified, the next requests will be done on the new tumblelog.
/*/
$tumblrObj->flush();					// You can pass a new Tumblr ID to work with as argument.

/*  Important note : you can do as much getPosts, getAllPosts and getPost request as you like! Just don't forget to sortArray();  to have them in logical order.
/   It's impossible to have several times the same post in the array (array key composed with post's id and post's timestamp).
/*/
?>