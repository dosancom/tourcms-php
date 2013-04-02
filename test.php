<?php 

/**
 * test.php
 * Displays information regarding server configuration and config file settings
 * Call this file in a browser to test, delete once verified ok
 * @author Paul Slugocki
 */
 
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>TourCMS API Server Check</title>
		<style>
			body {
				text-align: center;
				font-family: helvetica, sans-serif;
				font-size: 14px;
			}
			ul {
				max-width: 460px;
				margin: auto;
				list-style-type: none;
			}
			li {
				border-radius: 4px;
				text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
				padding: 20px;
				margin-bottom: 20px;
			}
			li a {
				display: inline-block;
				padding: 1px 4px 1px 4px;
				border-radius: 4px;
				text-decoration: none;
			}
			
			li.ok {
				background: #DFF0D8;
				border: 1px solid #D6E9C6;
				color: #468847;
				
			}
			li.ok:before {
				content: "✔ ";
			}
			li.fail {
				background: #F2DEDE;
				border: 1px solid #EED3D7;
				color: #B94A48;
			}
			li.fail:before {
				content: "✘ ";
			}
			li.fail a {
				background: #B94A48;
				color: #F2DEDE;
			}
			
			li.warn {
				background: #FCF8E3;
				border: 1px solid #FBEED5;
				color: #C09853;
			}
			li.warn:before {
				content: "⚑ ";
			}
			li.warn a {
				background: #C09853;
				color: #FCF8E3;
			}
		</style>
	</head>
	<body>
		<h1>Test your setup (Beta)</h1>
		<p>Any missing components or configuration problems will be shown in red below, yellow items should be checked manually.</p>
		<h2>PHP</h2>
		<?php
			function print_status($status, $ok_text, $fail_text) {
				?>
					<li class="<?php $status ? print "ok" : print "fail" ?>">
						<?php $status ? print $ok_text : print $fail_text; ?>
					</li>
				<?
			}
			$has_phpversion = strnatcmp(phpversion(),'5.1.2') >= 0;
			$has_simplexml = function_exists("simplexml_load_file");
			$has_curl = function_exists("curl_init");
			$curl_ok = false;
			
			$has_configfile = file_exists("config.php");
			$is_configured = false;
			$api_ok = false;
			$has_tours = false;
			
						
		?>
		<ul>
			<?php 
				print_status($has_phpversion, "You have a recent enough version of PHP", "PHP 5.1.2 or newer is required");
				print_status($has_simplexml, "SimpleXML is loaded ok", "SimpleXML is not loaded <a href='http://www.php.net/manual/en/simplexml.installation.php'>?</a>");
				print_status($has_curl, "CURL is loaded ok", "CURL is not loaded <a href='http://uk3.php.net/manual/en/curl.installation.php'>?</a>");
				
				if($has_curl) {
					$ch = curl_init("https://live.tourcms.com/favicon.ico");
					curl_setopt($ch, CURLOPT_HEADER, 1); 
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_NOBODY, true);
					curl_setopt($ch, CURLOPT_TIMEOUT, 30 );
					$c = curl_exec($ch); 
					$curl_info = curl_getinfo($ch);
					if(isset($curl_info['http_code'])) {
						$curl_ok = (int)$curl_info['http_code']==200;
						print_status($curl_ok, "Downloaded a test file from TourCMS ok", "Unable to download a test file from TourCMS, status: <strong>".$curl_info['http_code']."</strong>");
					} else {
						print_status(false, "", "Unable to contact TourCMS server, no status code returned");
					}
				}
			?>
		</ul>
		<h2>config.php</h2>
		<ul>
			<?php 
				print_status($has_configfile, "A config file exists", "Rename <strong>config-example.php</strong> to <strong>config.php</strong>.");
				if($has_configfile) {
					include_once("config.php");
					
					$is_configured = !($api_private_key=="" || ($marketplace_account_id == 0 && $channel_id == 0)); 			
					
					print_status($is_configured, "You have loaded some settings", "You haven't entered your API settings in <strong>config.php</strong>");		
					if($is_configured) {
						
						include_once('tourcms.php');
						
						
						$tc = new TourCMS($marketplace_account_id, $api_private_key, 'simplexml');
						
						$api_check = $tc->api_rate_limit_status($channel_id);
						
						$api_ok = (string)$api_check->error == "OK";
						
						if(!$api_ok && strpos((string)$api_check->error, "_TIME")!==false) {
							?>
							<li class="fail">It looks like the Date/Time of your server is incorrect. According to your server the time in GMT is: <strong><?php print gmdate('H:i  l (\G\M\T)'); ?></strong>. You can compare that to the actual time in GMT by using this <a href="https://www.google.co.uk/search?q=current+time+gmt">Google search</a><br />(it doesn't matter if it's a few minutes out).</li>
							<?php
						}
						
						print_status($api_ok, "Your API settings work", "Your API settings return the following error: <em>" . $api_check->error . "</em> <a href='http://www.tourcms.com/support/api/mp/error_messages.php'>?</a>");
						
						if($api_ok) {
							$tour_search = $tc->search_tours("", $channel_id);
							
							$has_tours = (int)$tour_search->total_tour_count > 0;
							
							print_status($has_tours, "Found <strong>" . $tour_search->total_tour_count . "</strong> tours", "No tours found");
						} 
					}
				} 
			?>
		</ul>
		
		<h2>Summary</h2>
		<ul>
		<?php 
		
			$all_ok = $has_phpversion && $has_simplexml && $has_curl && $curl_ok && $has_configfile && $is_configured && $api_ok;
			
			print_status($all_ok, $has_tours ? "Everything looks OK" : "Server &amp; settings OK, <strong>you just need some Tours/Hotels</strong>", "Check the issues listed above");
		?>
		</ul>
		<p>Problems? <a href="http://www.tourcms.com/company/contact.php">Contact TourCMS support</a><br />@<a href="http://twitter.com/TourCMS">TourCMS</a> on Twitter (page by @<a href="http://twitter.com/paulslugocki">paulslugocki</a>)</p>
	</body>
</html>
