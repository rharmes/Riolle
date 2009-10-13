Riolle -- A URL shortening and bookmarking app
==============================================

Riolle is a quick and dirty PHP script that allows you to save and tag URLs. A short URL is also created for each saved URLs, essentially making this a bit.ly that you control. It offers bookmarklets for saving URLs and for sending shorten URLs directing to Twitter. It also supports both bit.ly and TinyURL style APIs, for use with apps like Tweetie.

Setup
-----

1. 	Put both the `index.php` and the `.htaccess` files on the root level of your server
2. 	Customize these lines at the top of `index.php`:

		$curl_path = '/usr/bin/curl';			# Location of curl on your server.
		$per_page = 20;				# Number of links to show per page.
		$codeset = 'abcdefghijklmnopqrstuvwxyz';	# Characters used to make up the short URL key.
		$domain = 'harm.es';

3. 	View the page in a browser. The first time the page is loaded, you may see a PHP error. This should go away the next time the page is loaded.

Bookmarklets
------------

At the bottom of the page (when logged in), there are three bookmarklets you can drag to your bookmark bar:

* Save: saves the URL and returns you to the page you were on
* Shorten: saves the URL and loads your previously saved URLs, with the short URL for the most recently saved URL displayed at the top of the page
* Tweet: saves the URL and loads the short URL into a 

Browser Support
---------------

At the moment, Riolle is known to work in Safari 4 and Firefox 3.5 on the Mac. Anything is unsupported, though the base functionality will probably still work (the UI will look awful though).

BSD License
-----------

[http://creativecommons.org/licenses/BSD/](http://creativecommons.org/licenses/BSD/)

Copyright (c) 2009, Ross Harmes
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
Neither the name of Ross Harmes nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
