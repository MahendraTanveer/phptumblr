# Read functions #

Here are docs for all the read functions. It's soooo easy to read the informations from a tumblelog uning phpTumblr that I think a doc is useless... But here it is!

## Creating the object ##
So, at first, you have to create the **readTumblr** object. That simple:
```
$tumblrObj = new readTumblr('mytumblelog');
```
Where **mytumblelog** is the ID of your tlog on Tumblr (the part before _tumblr.com_ in it's URI).

## Get some posts ##
Now, the main function of phpTumblr: **getPosts**. You can get posts with many parameters. You want your 10 last videos? Your 3 last audios starting from the 15th ? No problem :) .

Here's the syntax:
```
$tumblrObj->getPosts($start,$num,$type,$id);
```
Let's see the params:
  * **$start**: the number of posts after that you'll get those you want. Default is 0.
  * **$num**: the number of posts you want to get. Default is 20.
  * **$type**: the type of posts you want to get. Valid types are regular, quote, photo, link, conversation, video  and audio. Default is null (all types will be taken).
  * **$id**: if you want to get a specific post, it's ID go here. Default is null (not used).

## And finally get it! ##
No more ceremony...
```
$tumblrArr = $tumblrObj->dumpArray();
```

Now I think you could print\_r that array and see what you can do with it ;) . That's all!

# Enable Caching #
phpTumblr support caching so you can save bandwith and stop bombing Tumblr. It's simple as well! Remember when we create the Object?
```
$tumblrObj = new readTumblr('mytumblelog');
```
Just create a **readTumblrCache** object!
```
$tumblrObj = new readTumblrCache('mytumblelog','path/to/tmp',3600);
```
The first param have not changed. The second is the path to you cache directory. Be sure you webserver can write in! The last param is the time in second before refreshing the cache. 3600s = 1h. But you can put whatever you want ;) .