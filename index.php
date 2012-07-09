<?php

	#
	# Settings.
	#

	$curl_path = '/usr/bin/curl';			# Location of curl on your server.
	$per_page = 20;					# Number of links to show per page.
	$codeset = 'abcdefghijklmnopqrstuvwxyz';	# Characters used to make up the short URL key.
	$domain = 'harm.es';


	#
	# Administrator access, needed to save, edit and delete links.
	# You're on your own here; my solution so embarrassing that I'm not even going to include it.
	# Set this to true while you're playing around with it.
	#

	$is_admin = false;


	$links = array();

	if ($db = new SQLiteDatabase('links')) {

		#
		# If a new link is being entered, insert it into the DB.
		#

		if ($_GET['save'] && $is_admin) {

			#
			# Insert the row.
			#

			$url = sqlite_escape_string($_GET['save']);
			$title = isset($_GET['title']) ? sqlite_escape_string($_GET['title']) : '';
			$key = '';
			$now = time();

			$q = $db->query('INSERT INTO links (url, title, created, last_access) VALUES ("' . $url . '", "' . $title . '", ' . $now . ', ' . $now . ')');

			if ($q === false) {
				create_table($db);
				$db->query('INSERT INTO links (url, title, created, last_access) VALUES ("' . $url . '", "' . $title . '", ' . $now . ', ' . $now . ')');
			}


			#
			# Create the key.
			#

			$id = $db->lastInsertRowid();
			$key = encode_key($id, $codeset);

			$db->query('UPDATE links SET key="' . $key . '" WHERE id=' . $id);


			#
			# Get the title.
			#

			if ($title === '') {

				$lines = array();
				exec($curl_path . ' ' . $url, $lines);
				$html_string = implode('', $lines);

				preg_match('/<title>(.*?)<\/title>/im', $html_string, $matches);

				if ($matches && sizeof($matches) > 1) {
					$title = sqlite_escape_string($matches[1]);
				}

				if ($title != '') {
					$db->query('UPDATE links SET title="' . $title . '" WHERE id=' . $id);
				}
			}

			#
			# Redirect (or not) depending on the bookmarklet used.
			#

			if (isset($_GET['return'])) {
				header('Location: ' . $url);
				exit;
			}
			else if (isset($_GET['tweet'])) { # You must be logged into Twitter for this to work; non-ideal, I know, since they seem to expire their goddamn cookies every 10 minutes.
				header('Location: http://twitter.com/?status=http://' . $domain . '/' . $key);
				exit;
			}
			else if (isset($_GET['show_short_url'])) {
				$show_short_url = 1;
				$short_url_key = $key;
			}
			else if (isset($_GET['api'])) { # For use in apps like Tweetie: http://developer.atebits.com/tweetie-iphone/custom-shortening/
				if (isset($_GET['json'])) {

					# biy.ly / JSON response style
					header('Content-type: application/json');
					echo '{ "shortUrl" : "http://' . $domain . '/' . $key . '" }';
				}
				else {

					# TinyURL API response style.
					header('Content-type: plain/text');
					echo 'http://' . $domain . '/' . $key;
				}
				exit;
			}
			else {
				header('Location: /');
				exit;
			}
		}


		#
		# If a new tag is being entered, insert it into the DB.
		#

		if ($_GET['edit_tags'] && $_GET['id'] && $is_admin) {

			#
			# Insert the row.
			#

			$tags = sqlite_escape_string($_GET['edit_tags']);
			$id = sqlite_escape_string($_GET['id']);

			$db->query('UPDATE links SET tags="' . $tags . '" WHERE id=' . $id);


			#
			# Reload the page to remove the GET params.
			#

			header('Location: /');
			exit;
		}


		#
		# Delete a link.
		#

		if ($_GET['delete'] && $is_admin) {

			#
			# Remove the row.
			#

			$id = sqlite_escape_string($_GET['delete']);

			$db->query('DELETE FROM links  WHERE id=' . $id);


			#
			# Reload the page to remove the GET params.
			#

			header('Location: /');
			exit;
		}


		#
		# If we are redirecting, find the original URL.
		#

		if ($_GET['redir']) {

			#
			# Find the row.
			#

			$key = sqlite_escape_string($_GET['redir']);
			$q = $db->query('SELECT * FROM links WHERE key="' . $key . '"');

			#
			# Create the table if it doesn't exist.
			#

			if ($q === false) {
				create_table($db);
				header('Location: /');
				exit;
			}

			$link = $q->fetch(SQLITE_ASSOC);

			#
			# Update the row, setting the last_access time and incrementing the count.
			#

			$now = time();
			$db->query('UPDATE links SET last_access=' . $now . ', count=' . ($link['count'] + 1) . ' WHERE id=' . $link['id']);

			#
			# 301 redirect to the URL.
			#

			header('Location: ' . $link['url'], true, 301);
		}


		#
		# Select the latest 20 links.
		#

		$page = (isset($_GET['page']) && $_GET['page'] != '') ? (int) $_GET['page'] : 1;
		$lower_limit = ($page - 1) * $per_page;
		$upper_limit = $per_page;

		$where = '';
		if ($_GET['tag']) {
			$tag = sqlite_escape_string($_GET['tag']);
			$where = ' WHERE tags LIKE "%' . $tag . '%"';
		}

		$q = $db->query('SELECT * FROM links' . $where . ' ORDER BY created DESC LIMIT ' . $lower_limit . ', ' . $upper_limit);

		#
		# Create the table if it doesn't exist.
		#

		if ($q === false) {
			create_table($db);
		}

		#
		# Read the links in as an associative array.
		#

		else {
			$links = $q->fetchAll(SQLITE_ASSOC);
		}
	}
	else {
		die($err);
	}

	#
	# Helper functions.
	#

	function create_table($db) {
		$db->query('CREATE TABLE links (id INTEGER PRIMARY KEY, key TEXT, url TEXT, title TEXT, created TIMESTAMP DEFAULT CURRENT_TIMESTAMP, last_access TIMESTAMP DEFAULT CURRENT_TIMESTAMP, count INTEGER DEFAULT 0, tags TEXT)');
	}

	function encode_key($n, $codeset) {

		$base = strlen($codeset);

		while ($n > 0) {
			$key = substr($codeset, ($n % $base), 1) . $key;
			$n = floor($n / $base);
		}

		return $key;
	}

	function decode_key($key, $codeset) {

		$base = strlen($codeset);

		$n = 0;
		for ($i = strlen($key); $i; $i--) {
  			$n += strpos($codeset, substr($key, (-1 * ($i - strlen($key))), 1)) * pow($base, $i - 1);
		}

		return $n;
	}

	function format_tags($tag_string) {

		$html = '';
		$tags = explode(' ', $tag_string);

		foreach ($tags as $tag) {

			$tag_hash = md5($tag);
			$offset = 0;
			$red = hexdec(substr($tag_hash, $offset, 2));
			$green = hexdec(substr($tag_hash, $offset + 2, 2));
			$blue = hexdec(substr($tag_hash, $offset + 4, 2));

			$max_diff = max($red, $green, $blue) - min($red, $green, $blue);

			while ($max_diff > 20) { # Increase this number to get colors that are less grey.
				list($red, $green, $blue) = make_more_grey($red, $green, $blue);
				$max_diff = max($red, $green, $blue) - min($red, $green, $blue);
			}

			$brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;
			while ($brightness > 95) { # Increase this number to get brighter, less muted colors.
				$red = $red * 0.95;
				$green = $green * 0.95;
				$blue = $blue * 0.95;
				$brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;
			}

			$color = 'rgb(' . (int) $red . ', ' . (int) $green . ', ' . (int) $blue . ')';
			$hover_color = 'rgb(' . (int) ($red * 1.15) . ', ' . (int) ($green * 1.15) . ', ' . (int) ($blue * 1.15) . ')';

			$html .= '<a href="/tags/' . $tag . '/" style="background: ' . $color . ';" onmouseover="this.style.background = \'' . $hover_color . '\';" onmouseout="this.style.background = \'' . $color . '\';">' . $tag . '</a>';
		}

		return $html;
	}

	function make_more_grey($red, $green, $blue) {

		$max = max($red, $green, $blue);
		$min = min($red, $green, $blue);

		if ($max === $red) {
			$red -= 5;
		}
		if ($max === $green) {
			$green -= 5;
		}
		if ($max === $blue) {
			$blue -= 5;
		}

		if ($min === $red) {
			$red += 5;
		}
		if ($min === $green) {
			$green += 5;
		}
		if ($min === $blue) {
			$blue += 5;
		}

		return array($red, $green, $blue);
	}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title><?php echo $domain; ?></title>
	<meta http-equiv="Content-type" value="text/html; charset=UTF-8">

<?php
	$bookmark_img = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAtCAYAAACnF+sSAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAMVJREFUeNpifMvZ+p8BD7j6/Qk+aQYmBgrBqAGjBowaMGrAqAGjBowaMGrASDMA2Pq6C6Rm4VPDgkfuGSMDQxiQPg/E/4A4gxQXgNp2vsAm3jmIQxiygHg6sQY8BWK/S98fnUP1DdiQmYQMAGn2/cfw/zw7IytQF0YbNBOIZ+AyAOxskJ9ZGZkZhFl4gB7/jyVcUQ2BGfACphnE+ff/P4MQ0ACIK7CCTJh3WKA2BwHxBYQ1/xlgrnj2+z0DM/awBhkiABBgAHfLNK5w5WIOAAAAAElFTkSuQmCC';
	$link_img = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAHCAYAAAA4R3wZAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAJNJREFUeNpi/P//PwMIvONqUwVSEgwIcBhK2yKJvRD6VnUbxGAEaQRqgkk6IymaA6VTkMT2ggig5sOMbzlbYTY5QxU/gSryhdKbobQM1BCQ5odMDKjAC0o3ALExFDegyYEBusZtSBrPQnEDmhwYsADxbahT96L5Zw6SISh+BIJHLEghqIokwYDkV2SxF1CLGAACDADhLCh7Xw+7VgAAAABJRU5ErkJggg==';
	$link_hover_img = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAAHCAYAAAA4R3wZAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAJRJREFUeNpi/P//PwMIvONq8wJSBgwI0Aalq5DELgh9q9oGYjCCNAI1wSRbkRRZQOkTSGLVIAKouY0JahNME0gxIxQnQTGMbwEzGKjHmoUBFUwFYhMg/o8klgbVOBVZIROaxmwoDVI4C4oZ0eTAAGTjNmigVKP5B+bH/+h+BIKjLEgh6IUkAQIn0RSDQxVqEQNAgAEAgOIlVe77gs0AAAAASUVORK5CYII=';
?>
	<style type="text/css">
		body, div, form,
		input, p, th, td		{ margin: 0; padding: 0; }
		table 				{ border-collapse: collapse; border-spacing: 0; }
		em, strong			{ font-style: normal; font-weight: normal; }
		input 				{ font-family: inherit; font-size: inherit; font-weight: inherit; *font-size:100%; }

		html 				{ background: #444; color: #fff; }
		body 				{ text-align: center; font-family: Helvetica, Arial, sans; font-size: 11px; line-height: 17px; }

		a 				{ color: #ed0985; text-decoration: none; }
		a:hover 			{ border-bottom: 1px dotted #777; }

		#doc 				{ width: 1100px; margin: 0 auto; text-align: left; }

		#hd 				{ margin: 40px 0 0; padding: 8px 8px 8px 32px; -webkit-border-radius: 10px; -moz-border-radius: 10px; border-radius: 10px; background: #fff url(<?php echo $bookmark_img; ?>) no-repeat 8px 8px; }
		#hd.disabled 			{ background-color: #494949; }
		#save 				{ width: 100%; border: 0; font-size: 38px; }
		#hd div 			{ width: 100%; height: 44px; }

		table 				{ width: 1134px; margin: 40px 0 0 -20px; }
		th, td 				{ padding: 4px 6px; vertical-align: top; }
		th 				{ color: #282828; font-weight: bold; }
		td em 				{ color: #686868; }

		th.short-link,
		tr td.short-link,
		tr.odd td.short-link 		{ width: 18px; padding: 8px 2px 0 0; background: none; }
		th.title, td.title 		{ width: 310px; }
		th.url, td.url 			{ width: 320px; white-space: nowrap; }
		th.action,
		tr td.action,
		tr.odd td.action 		{ width: 8px; padding: 4px 0 4px 6px; background: none; }
		td.action a 			{ color: #972563; }
		td.action a:hover 		{ border: 0; color: #ed0985; }
		tr.odd td 			{ background: #494949; }
		tr.odd td:nth-child(2) 		{ border-top-left-radius: 5px; border-bottom-left-radius: 5px; -webkit-border-top-left-radius: 5px; -webkit-border-bottom-left-radius: 5px; }
		tr.odd td:nth-child(4) 		{ border-top-right-radius: 5px; border-bottom-right-radius: 5px; -webkit-border-top-right-radius: 5px; -webkit-border-bottom-right-radius: 5px; }

		.short-link a 			{ display: block; width: 18px; height: 9px; background: url(<?php echo $link_img; ?>) no-repeat 2px 1px; }
		.short-link a:hover 		{ border: 0; background: url(<?php echo $link_hover_img; ?>) no-repeat 2px 1px; }

		td.count span 			{ color: #686868; }
		span.truncate 			{ display: block; max-width: 300px; float: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

		.tags a 			{ display: block; float: left; margin-right: 4px; padding: 0 8px; background: #505050; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; -webkit-box-shadow: 0 0 2px #414141; box-shadow: 0 0 2px #414141; color: #fff; }
		.tags a:hover 			{ background: #626262; border: none; -webkit-box-shadow: 0 0 2px #303030; box-shadow: 0 0 2px #303030; }
		.tags a:last-child 		{ margin: 0; }

		.tags a.edit-tags 		{ padding: 0 5px; color: #888; }
		.tags a.edit-tags:hover 	{ color: #fff; }

		.edit-tags-form 		{ display: none; }
		.edit-tags-field 		{ width: 410px; float: left; margin-right: 4px; padding: 2px; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; border: 0; }

		#ft 				{ position: relative; overflow: hidden; width: 1100px; min-height: 18px; margin: 30px 0 10px; padding: 10px 0; color: #686868; }
		#ft p 				{ position: absolute; right: 0; }
		#ft p.attr 			{ left: 0; }
		#ft p.pagination		{ left: 0; width: 1100px; text-align: center; }
		#ft p.pagination span,
		#ft p.pagination a		{ display: inline-block; min-width: 18px; padding: 0 2px; background: #494949; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; -webkit-box-shadow: 0 0 2px #414141; box-shadow: 0 0 2px #414141; color: #fff; text-align: center; }
		#ft p.pagination span.arrow 	{ color: #888; }
		#ft p.pagination span.page	{ background: #595959; }
		#ft p.pagination span.ell	{ min-width: 10px; background: none; -webkit-box-shadow: none; box-shadow: none; }
		#ft p.pagination a		{ color: #ed0985; }
		#ft p.pagination a:hover	{ background: #525252; border: none; -webkit-box-shadow: 0 0 2px #303030; box-shadow: 0 0 2px #303030; }
		#ft em 				{ color: #fff; }

		.bookmarklet 			{ padding: 1px 5px; background: #494949; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; -webkit-box-shadow: 0 0 2px #414141; box-shadow: 0 0 2px #414141; }
		.bookmarklet:hover 		{ background: #525252; border: none; -webkit-box-shadow: 0 0 2px #303030; box-shadow: 0 0 2px #303030; }
	</style>
</head>
<body>

<div id="doc">
<?php if ($is_admin) { ?>
	<div id="hd">
		<form action="/" method="GET">
			<input type="text" id="save" name="save"<?php if ($show_short_url) echo ' value="http://' . $domain . '/' . $short_url_key . '"'; ?>>
		</form>
	</div>
<?php } else { ?>
	<div id="hd" class="disabled">
		<div></div>
	</div>
<?php } ?>
	<div id="bd">
		<table>
			<thead>
				<tr>
					<th class="short-link"></th>
					<th class="title">Title</th>
					<th class="url">URL</th>
					<th class="tags">Tags</th>
					<th class="action"></th>
				</tr>
			</thead>
			<tbody>
<?php
	$parity = true;
	foreach ($links as $link) {
?>
				<tr<?php if ($parity) echo ' class="odd"'; ?>>
					<td class="short-link"><a onclick="window.location = 'http://twitter.com/?status=http://<?php echo $domain . '/' . $link['key']; ?>'; return false;" href="http://<?php echo $domain . '/' . $link['key']; ?>" title="Short url, created <?php echo date('j M Y \a\t g:ia', (int) $link['created']); ?>"></a></td>
					<td class="title"><span class="truncate"><?php echo $link['title']; ?></span></td>
					<td class="url"><span class="truncate"><a href="<?php echo $link['url']; ?>"><?php echo $link['url']; ?></a></span>&nbsp;<em title="Last accessed <?php echo date('j M Y \a\t g:ia', (int) $link['last_access']); ?>"><?php echo $link['count']; ?></em></td>
					<td class="tags">
						<div id="tags-<?php echo $link['id']; ?>">
							<?php echo format_tags($link['tags']); ?>
<?php if ($is_admin) { ?>
							<a href="#" class="edit-tags" title="Edit tags" onclick="toggleTagForm('<?php echo $link['id']; ?>', true); return false;">+</a>
						</div>
						<form action="/" method="get" id="edit-tags-<?php echo $link['id']; ?>" class="edit-tags-form">
							<input type="hidden" name="id" value="<?php echo $link['id']; ?>">
							<input type="text" class="edit-tags-field" id="edit-tags-field-<?php echo $link['id']; ?>" name="edit_tags" value="<?php echo $link['tags']; ?>">
							<a href="#" class="edit-tags" title="Stop editing tags" onclick="toggleTagForm('<?php echo $link['id']; ?>'); return false;">&times;</a>
						</form>
<?php } else { ?>
						</div>
<?php } ?>
					</td>
					<td class="action"><?php if ($is_admin) { ?><a href="/?delete=<?php echo $link['id']; ?>" title="Delete link">&times;</a><?php } ?></td>
				</tr>
<?php
		$parity = !$parity;
	}
?>
			</tbody>
		</table>
	</div>
	<div id="ft">
		<p class="attr">
			This is <em>Riolle</em> /
			<em>Fork on <a href="http://github.com/rharmes/riolle">Github</a></em>
		</p>
<?php

	#
	# Get the pagination information. $page and $per_page are already set above.
	#

	$q = $db->query('SELECT COUNT(*) AS count FROM links');
	$result = $q->fetch(SQLITE_ASSOC);

	$total_links = $result['count'];
	$total_pages = ceil($total_links / $per_page);

?>
		<p class="pagination">
<?php if ($page === 1) { ?>
			<span class="arrow">◄</span>
<?php } else { ?>
			<a href="/<?php echo ($page - 1); ?>/" class="arrow">◄</a>
<?php } ?>
<?php
	$lower_ellipsis_shown = false;
	$upper_ellipsis_shown = false;
	for ($n = 1; $n <= $total_pages; $n++) {

		#
		# Display the first two, the last two, and a window of 5 around the current page.
		#

		if ($n <= 2 || $n >= $total_pages - 1 || ($n >= $page - 2 && $n <= $page + 2) || ($page <= 2 && $n <= 5) || ($page >= $total_pages - 1 && $n >= $total_pages - 4)) {
			if ($n === $page) {
				echo "\t\t\t" . '<span class="page">' . $n . '</span>' . "\n";
			}
			else {
				echo "\t\t\t" . '<a href="/' . $n . '/" class="page">' . $n . '</a>' . "\n";
			}
		}
		else if ($n < $page && !$lower_ellipsis_shown) {
			$lower_ellipsis_shown = true;
			echo "\t\t\t" . '<span class="ell">...</span>' . "\n";
		}
		else if ($n > $page && !$upper_ellipsis_shown) {
			$upper_ellipsis_shown = true;
			echo "\t\t\t" . '<span class="ell">...</span>' . "\n";
		}
	}
?>
<?php if ($page >= $total_pages) { ?>
			<span class="arrow">►</span>
<?php } else { ?>
			<a href="/<?php echo ($page + 1); ?>/" class="arrow">►</a>
<?php } ?>
		</p>
<?php if ($is_admin) { ?>
		<p class="admin">
			Bookmarklets
			<a class="bookmarklet" href="javascript:location.href='http://<?php echo $domain; ?>/?save='+encodeURIComponent(window.location.href)+'&title='+encodeURIComponent(document.title)+'&return=1';">save</a>
			<a class="bookmarklet"  href="javascript:location.href='http://<?php echo $domain; ?>/?save='+encodeURIComponent(window.location.href)+'&title='+encodeURIComponent(document.title)+'&show_short_url=1';">shorten</a>
			<a class="bookmarklet"  href="javascript:location.href='http://<?php echo $domain; ?>	/?save='+encodeURIComponent(window.location.href)+'&title='+encodeURIComponent(document.title)+'&tweet=1';">tweet</a> /
			<a href="/?logout=1">Logout</a>
		</p>
<?php } ?>
	</div>
</div>

<script>
	function toggleTagForm(id, show) {
		var tags = document.getElementById('tags-' + id),
		    tagForm = document.getElementById('edit-tags-' + id);

		tags.style.display = show ? 'none' : '';
		tagForm.style.display = show ? 'block' : '';

		if (show) {
			document.getElementById('edit-tags-field-' + id).focus();
		}
	}
<?php if ($show_short_url) { ?>
	document.getElementById('save').focus();
<?php } ?>
</script>

</body>
</html>