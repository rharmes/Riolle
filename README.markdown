Riolle -- A URL shortening and bookmarking app
==============================================

Riolle is a quick and dirty PHP script that allows you to save and tag URLs. A short URL is also created for each saved URLs, essentially making this a bit.ly that you control. It offers bookmarklets for saving URLs and for sending shorten URLs directing to Twitter. It also supports both bit.ly and TinyURL style APIs, for use with apps like Tweetie.

Setup
-----

1. Put both the `index.php` and the `.htaccess` files on the root level of your server
2. Customize these lines at the top of `index.php`:

>	$curl_path = '/usr/bin/curl';			# Location of curl on your server.
>	$per_page = 20;					# Number of links to show per page.
>	$codeset = 'abcdefghijklmnopqrstuvwxyz';	# Characters used to make up the short URL key.
>	$domain = 'harm.es';

3. View the page in a browser. The first time the page is loaded, you may see a PHP error. This should go away the next time the page is loaded.

Bookmarklets
------------

* Save: saves the URL and returns you to the page you were on
* Shorten: saves the URL and loads your previously saved URLs, with the short URL for the most recently saved URL displayed at the top of the page
* Tweet: saves the URL and loads the short URL into a 

Browser Support
---------------

At the moment, Riolle is known to work in Safari 4 and Firefox 3.5 on the Mac. Anything is unsupported, though the base functionality will probably still work (the UI will look awful though).
