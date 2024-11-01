<?php
/*
Plugin Name: Youtube Random Videos
Plugin URI: http://fabwebstudio.com/
Description: Automatically Embed Videos Into Your Website Based on Keyword.
Version: 1.1
Author: Fab Web Studio
Author URI: http://fabwebstudio.com/
*/

function wp_youtube_keyword_upgrademe() {
}


class wpYoutubeKeyword {
	public function __construct()
	{
		# create meta boxes for 'edit post' pages
		add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
		
		# Adding Menu
		add_action('admin_menu',  array($this, 'youtube_settings'));

		# on init to catch POST
		add_action('init', array($this, 'save_data'));

		add_action('init', array($this, 'save_overriding_data'));
		
		# include css
		add_action('admin_print_styles', array($this, 'enqueue_styles'));
	}
	
	# include css
	public function enqueue_styles()
	{
		# include wp-youtube-keyword.css
		wp_enqueue_style('wp-youtube-keyword', plugins_url('/stylesheets/wp-youtube-keyword.css', __FILE__), false, '1.0');
	}
	
	# add meta boxes
	public function add_meta_boxes()
	{
		add_meta_box(
			'wp_youtube_keyword_meta_box',
			'Youtube Random Videos',
			array($this, 'render_wp_youtube_keyword_content'),   
			'post',
			'side',
			'core'
		);
		add_meta_box(
			'wp_youtube_keyword_meta_box',
			'Youtube Random Videos',
			array($this, 'render_wp_youtube_keyword_content'),
			'page',
			'side',
			'core'
		);
	}
	
	# meta box content
	public function render_wp_youtube_keyword_content($post) {
		$youtube_detail = get_post_meta($post->ID,'wp_youtube_options',true);
		if(is_array($youtube_detail)) {
			extract($youtube_detail);
		}

		# nonce verification
		wp_nonce_field(plugin_basename(__FILE__), 'wp_youtube_keyword_nonce');

		# Render Box Output
		echo "<div id='css_wp_youtube'>";
			if(ini_get('allow_url_fopen') || in_array('curl', get_loaded_extensions())) { }
			else {
				echo "<div style='    border-radius: 3px 3px 3px 3px;    border-style: solid;    border-width: 1px;    margin: 5px 15px 2px;    padding: 0 0.6em;margin: 5px 0 15px;background-color: #FFEBE8;border-color: #CC0000;'><p>Please Update Your Server php.ini file<br/>Either Set : allow_url_fopen = On<br/> Or Enable: Curl on your server</p></div>";
			}
			echo "<p class='css_wp_youtube_row'>";
				echo "<label>Video Keyword</label>";
				echo "<input type='text' name='_wp_youtube_key_data' id='_wp_youtube_key_data' value='".$yt_keyword."'/>";
			echo "</p>";
			
			echo "<p class='css_wp_youtube_row'>";
				echo "<label>Negative Keyword List</label>";
				echo "<textarea id='_wp_youtube_key_neg_data' name='_wp_youtube_key_neg_data'>".$yt_neg_keyword."</textarea>";
			echo "</p>";

			echo "<fieldset class='css_wp_youtube_row'>";
				echo "<legend>Video Dimension</legend>";
				echo "<input ".($yt_size=='420x315'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_420' name='_wp_youtube_key_video_size' type='radio' value='420x315' />";
				echo "<label for='_wp_youtube_key_video_420' class='radio-button'>420w x 315h</label><br/>";
				echo "<input ".($yt_size=='480x360'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_460' name='_wp_youtube_key_video_size' type='radio' value='480x360'/>";
				echo "<label for='_wp_youtube_key_video_460' class='radio-button'>480w x 360h</label><br/>";
				echo "<input ".($yt_size=='640x480'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_640' name='_wp_youtube_key_video_size' type='radio' value='640x480'/>";
				echo "<label for='_wp_youtube_key_video_640' class='radio-button'>640w x 480h</label><br/>";
			echo "</fieldset>";

			echo "<fieldset class='css_wp_youtube_row'>";
				echo "<legend>Video Location</legend>";
				echo "<input ".($yt_location=='b_title'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_below_title' name='_wp_youtube_key_video_location' type='radio' value='b_title' />";
				echo "<label for='_wp_youtube_key_video_below_title' class='radio-button'>Below Title</label><br/>";
				echo "<input ".($yt_location=='b_content'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_below_content' name='_wp_youtube_key_video_location' type='radio' value='b_content'/>";
				echo "<label for='_wp_youtube_key_video_below_content' class='radio-button'>Below Content</label><br/>";
				echo "<input ".($yt_location=='b_mid'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_below_mid' name='_wp_youtube_key_video_location' type='radio' value='b_mid'/>";
				echo "<label for='_wp_youtube_key_video_below_mid' class='radio-button'>Between Content</label><br/>";
			echo "</fieldset>";

			echo "<fieldset class='css_wp_youtube_row'>";
				echo "<legend>Video Alignment</legend>";
				echo "<input ".($yt_align=='a_left'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_align_left' name='_wp_youtube_key_video_align' type='radio' value='a_left' />";
				echo "<label for='_wp_youtube_key_video_align_left' class='radio-button'>Left</label>";
				echo "<input ".($yt_align=='a_mid'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_align_mid' name='_wp_youtube_key_video_align' type='radio' value='a_mid'/>";
				echo "<label for='_wp_youtube_key_video_align_mid' class='radio-button'>Center</label>";
				echo "<input ".($yt_align=='a_right'?'CHECKED':'')." class='small_input_box' id='_wp_youtube_key_video_align_right' name='_wp_youtube_key_video_align' type='radio' value='a_right'/>";
				echo "<label for='_wp_youtube_key_video_align_right' class='radio-button'>Right</label>";
			echo "</fieldset>";

		echo "</div>";
	}
	
	/* Save Data on post save*/
	public function save_data() {

		# if there is no data
		if (empty($_POST))
			return false;
		
		# check nonce is set and verify it
		if (!isset($_POST['wp_youtube_keyword_nonce']) || !wp_verify_nonce($_POST['wp_youtube_keyword_nonce'], plugin_basename(__FILE__)))
			return false;

		# get post id
		$post_id = (int) $_POST['post_ID'];
		
		# check permissions
		if ('page' == $_POST['post_type']) {
			if (!current_user_can('edit_page', $post_id))
				return false;
		}
		else {
			if (!current_user_can('edit_post', $post_id))
				return false;
		}
		
		# get data from post meta for youtube
		$youtube_detail = array(
			'yt_keyword'=>$_POST['_wp_youtube_key_data'],
			'yt_neg_keyword'=>$_POST['_wp_youtube_key_neg_data'],
			'yt_size'=>$_POST['_wp_youtube_key_video_size'],
			'yt_align'=>$_POST['_wp_youtube_key_video_align'],
			'yt_location'=>$_POST['_wp_youtube_key_video_location'],
		);
				
		update_post_meta($post_id,'wp_youtube_options',$youtube_detail);
	}

	

	// Render Functionality Page
	public function override_settings () {
		global $wpdb, $table_prefix;
		$table_name = $table_prefix.$this->tname;

		$youtube_override_detail = get_option('wp_youtube_override_options',true);
		if(is_array($youtube_override_detail)) {
			extract($youtube_override_detail);
		}

		echo "<div class='wrap'>";
			echo "<h2>Overriding Settings</h2>";
			echo "<span class='description'>Override Page/Post You Tube Settings</span>";

			echo '<form action="" method="post" enctype="multipart/form-data">';
				# nonce verification
				wp_nonce_field(plugin_basename(__FILE__), 'wp_youtube_overriding_settings_nonce');
				echo "<table class='form-table' id='checkoverride'>";
					echo "<tr><td valign='top' width=185>";
							echo "<label for='_wp_override_settings' class='api-button'>Override Settings</label>";
						echo "</td><td>";
							echo "<input ".($yt_override_settings=='1'?'CHECKED':'')." id='_wp_override_settings' name='_wp_override_settings' type='checkbox' value='on' /><br/>";
							echo "<span>I would like to over ride all post/page options an enable the auto post feature</span>";
					echo "</td></tr>";			
				echo "</table>";
					
				echo "<table id='override_settings' class='form-table'>";
					echo "<tr><td valign='top'>";				
						echo "<label for='_wp_override_keyword' class='api-button'>Video Keyword</label>";
					echo "</td><td>";
						echo "<input id='_wp_override_keyword' name='_wp_override_keyword' type='text' value='".$yt_override_keyword."' />";
					echo "</td></tr>";

					echo "<tr><td>";
							echo "<label for='_wp_override_n_keyword' class='api-button'>Negative Keyword</label>";
						echo "</td><td>";
							echo "<textarea id='_wp_override_n_keyword' name='_wp_override_n_keyword' cols=80 rows=4>".$yt_override_n_keyword."</textarea>";
					echo "</td></tr>";

					echo "<tr><td valign='top'>";				
						echo "<label for='_wp_override_random_placement' class='api-button'>Random Placement</label>";
					echo "</td><td class='ran_set'>";
						echo "<input ".($yt_override_random_placement=='1'?'CHECKED':'')." id='_wp_override_random_placement' name='_wp_override_random_placement' type='checkbox' value='on' /><br/>";
						echo "<span>Random Setting Override Below Options</span>";					
					echo "</td></tr>";
					
					echo "<tr><td>";
							echo "<label for='_wp_override_n_keyword' class='api-button'>Video Dimension</label>";
						echo "</td><td>";
							echo "<fieldset class='css_wp_youtube_row'>";
								echo "<legend></legend>";
								/*echo "<label class='small_input'>Width</label><input id='' class='small_input_box' name='_wp_override_youtube_key_video_width' value='".$yt_width."'/>";
								echo "<label class='small_input'>Height</label><input id='' class='small_input_box' name='_wp_override_youtube_key_video_height' value='".$yt_height."'/>";*/
								echo "<input ".($yt_override_size=='420x315'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_420' name='_wp_override_youtube_key_video_size' type='radio' value='420x315' />";
								echo "<label for='_wp_override_youtube_key_video_420' class='radio-button'>420w x 315h</label>";
								echo "<input ".($yt_override_size=='480x360'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_460' name='_wp_override_youtube_key_video_size' type='radio' value='480x360'/>";
								echo "<label for='_wp_override_youtube_key_video_460' class='radio-button'>480w x 360h</label>";
								echo "<input ".($yt_override_size=='640x480'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_640' name='_wp_override_youtube_key_video_size' type='radio' value='640x480'/>";
								echo "<label for='_wp_override_youtube_key_video_640' class='radio-button'>640w x 480h</label>";
							echo "</fieldset>";
					echo "</td></tr>";

					echo "<tr><td>";
							echo "<label for='_wp_override_n_keyword' class='api-button'>Video Location</label>";
						echo "</td><td>";
							echo "<fieldset class='css_wp_youtube_row'>";
								echo "<legend></legend>";
								echo "<input ".($yt_override_location=='b_title'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_below_title' name='_wp_override_youtube_key_video_location' type='radio' value='b_title' />";
								echo "<label for='_wp_override_youtube_key_video_below_title' class='radio-button'>Below Title</label>";
								echo "<input ".($yt_override_location=='b_content'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_below_content' name='_wp_override_youtube_key_video_location' type='radio' value='b_content'/>";
								echo "<label for='_wp_override_youtube_key_video_below_content' class='radio-button'>Below Content</label>";
								echo "<input ".($yt_override_location=='b_mid'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_below_mid' name='_wp_override_youtube_key_video_location' type='radio' value='b_mid'/>";
								echo "<label for='_wp_override_youtube_key_video_below_mid' class='radio-button'>Between Content</label>";
							echo "</fieldset>";
					echo "</td></tr>";

					echo "<tr><td>";
							echo "<label for='_wp_override_n_keyword' class='api-button'>Video Alignment</label>";
						echo "</td><td>";
							echo "<fieldset class='css_wp_youtube_row'>";
								echo "<legend></legend>";
								echo "<input ".($yt_override_align=='a_left'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_align_left' name='_wp_override_youtube_key_video_align' type='radio' value='a_left' />";
								echo "<label for='_wp_override_youtube_key_video_align_left' class='radio-button'>Left</label>";
								echo "<input ".($yt_override_align=='a_mid'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_align_mid' name='_wp_override_youtube_key_video_align' type='radio' value='a_mid'/>";
								echo "<label for='_wp_override_youtube_key_video_align_mid' class='radio-button'>Center</label>";
								echo "<input ".($yt_override_align=='a_right'?'CHECKED':'')." class='small_input_box' id='_wp_override_youtube_key_video_align_right' name='_wp_override_youtube_key_video_align' type='radio' value='a_right'/>";
								echo "<label for='_wp_override_youtube_key_video_align_right' class='radio-button'>Right</label>";
							echo "</fieldset>";
					echo "</td></tr>";
				echo "</table>";

				echo '<p class="submit"><input type="submit" value="Save Changes" class="button-primary" id="submit_file_user" name="submit_file_user"></p>';				

			echo "</form>";	
		echo "</div>";
	}


	/* Save Data on post save*/
	public function save_overriding_data() {

		# if there is no data
		if (empty($_POST))
			return false;
		
		# check nonce is set and verify it
		if (!isset($_POST['wp_youtube_overriding_settings_nonce']) || !wp_verify_nonce($_POST['wp_youtube_overriding_settings_nonce'], plugin_basename(__FILE__)))
			return false;
	
		//echo "<pre>";print_r($_REQUEST);echo "</pre>";

		# get data from post meta for youtube
		if(isset($_POST['_wp_override_settings'])) {
			$ov_set = 1;
		}
		else {
			$ov_set = 0;
		}

		if(isset($_POST['_wp_override_random_placement'])) {
			$ov_ran_set = 1;
		}
		else {
			$ov_ran_set = 0;
		}

		$youtube_override_detail = array(
			'yt_override_settings'=>$ov_set,
			'yt_override_random_placement'=>$ov_ran_set,
			'yt_override_keyword'=>$_POST['_wp_override_keyword'],
			'yt_override_n_keyword'=>$_POST['_wp_override_n_keyword'],
			'yt_override_size'=>$_POST['_wp_override_youtube_key_video_size'],
			'yt_override_align'=>$_POST['_wp_override_youtube_key_video_align'],
			'yt_override_location'=>$_POST['_wp_override_youtube_key_video_location'],
		);
		
		if(get_option('wp_youtube_override_options')) {
			update_option('wp_youtube_override_options',$youtube_override_detail);
		}
		else {
			add_option('wp_youtube_override_options',$youtube_override_detail);
		}
	}

	// Add Admin Menu Page
	public function youtube_settings() {
		add_options_page('Youtube Settings', 'Youtube Settings', 'manage_options', 'override_settings', array($this,'override_settings'));
	}
}


# if loaded in wordpress and we are in /wp-admin
if (function_exists('is_admin') && is_admin()) {
	# create object
	new wpYoutubeKeyword();
} 



// Class For Front End
class wpYoutubeKeyword_front {
	public function __construct()
	{
		# Call the action to add video to the post
		if(ini_get('allow_url_fopen') || in_array('curl', get_loaded_extensions())) {
			add_action('the_content', array(&$this, 'add_video_below_content'));
		}
	}


	public function load_file_from_url($url) {
		$ref = get_bloginfo('siteurl');
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_REFERER, $ref);
		$str = curl_exec($curl);
		curl_close($curl);
		return $str;
	}

	public function load_xml_from_url($url) {
		return simplexml_load_string($this->load_file_from_url($url));
	}

	public function add_video_below_content($content) {
		global $post;

		
		if(get_option('wp_youtube_override_options')) {
			$override_settings = get_option('wp_youtube_override_options');

			if($override_settings['yt_override_settings']) {

				$yt_keyword = $override_settings['yt_override_keyword'];
				$yt_neg_keyword = $override_settings['yt_override_n_keyword'];

				if($override_settings['yt_override_random_placement']) {
					$s = array('420x315','480x360','640x480');
					$a = array('a_right','a_mid','a_left');
					$l = array('b_title','b_content','b_mid');
					$yt_size = array_rand($s, 1);
					$yt_align = array_rand($a, 1);
					$yt_location = array_rand($l, 1);

					$yt_size = $s[$yt_size];
					$yt_align = $a[$yt_align];
					$yt_location = $l[$yt_location];
					
				}
				else {
					$yt_size = $override_settings['yt_override_size'];
					$yt_align = $override_settings['yt_override_align'];
					$yt_location = $override_settings['yt_override_location'];				
				}
			}
			else {
				# get keyword & other detail for the video
				$youtube_detail = get_post_meta($post->ID,'wp_youtube_options',true);
						if(is_array($youtube_detail)) {
					extract($youtube_detail);
				}			
			}
		}
		else {
			# get keyword & other detail for the video
			$youtube_detail = get_post_meta($post->ID,'wp_youtube_options',true);
					if(is_array($youtube_detail)) {
				extract($youtube_detail);
			}
		}


		# declare array for youtube id's
		$youtube_arr = array();

		# declare random video variable & set it as a flag to blank
		$random_video_id = '';

		# if keyword exists move further
		if($yt_keyword) {

			#set negative word to null for conditional use
			$nq = '';
			$vq = $yt_keyword;
			$vq = str_replace(' ', '+', trim($vq));

			#if negative word exists create its string.
			if($yt_neg_keyword) {
				$nq = str_replace(' ', '-', trim($explode_neg));
				$vq = str_replace(' ','',$vq."+-".$nq);
			}

			# encode url for youtube properly
			$entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D', '%0D%0A');
			$replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]","-");
			$vq = str_replace($entities, $replacements, urlencode($vq));

			// generate feed URL
			$feedURL = "http://gdata.youtube.com/feeds/api/videos?vq={$vq}&max-results=10&start-index=1";

			if(ini_get('allow_url_fopen')) {
				$sxml = simplexml_load_file($feedURL);
			}
			else {
				$sxml = $this->load_xml_from_url($feedURL);	
			}
			
			foreach ($sxml->entry as $entry) {
				$youtube_arr[] = str_replace('http://gdata.youtube.com/feeds/api/videos/','',$entry->id);
			}
			$rand_keys = array_rand($youtube_arr, 1);
			$random_video_id = $youtube_arr[$rand_keys];
		}
		
		if($random_video_id) {
			$yt_size = explode('x',$yt_size);
			
			$video_align = '';
			if($yt_align == "a_left") {
				$video_align="<div style='float:left;padding: 0 15px 10px 0;'>";
			}else if($yt_align == "a_mid") {
				$video_align="<div style='float:none;text-align:center;width=100%'>";
			}else if($yt_align == "a_right") {
				$video_align="<div style='float:right;padding: 0 0 10px 15px;'>";
			}
			
			if($yt_location=="b_mid") {
				$video_align = '';
				if($yt_align == "a_left") {
					$video_only = '<iframe width="'.$yt_size[0].'" height="'.$yt_size[1].'" src="http://www.youtube.com/embed/'.$random_video_id.'?hd=1&modestbranding=1" frameborder="0" allowfullscreen style="float:left;padding:0 15px 10px 0;"></iframe>';
				}else if($yt_align == "a_mid") {
					$video_only = '<iframe width="'.$yt_size[0].'" height="'.$yt_size[1].'" src="http://www.youtube.com/embed/'.$random_video_id.'?hd=1&modestbranding=1" frameborder="0" allowfullscreen style="float:none;text-align:center;padding:10px;"></iframe>';
				}else if($yt_align == "a_right") {
					$video_only = '<iframe width="'.$yt_size[0].'" height="'.$yt_size[1].'" src="http://www.youtube.com/embed/'.$random_video_id.'?hd=1&modestbranding=1" frameborder="0" allowfullscreen style="float:right;padding:0 0px 10px 15px;"></iframe>';
				}
				
			}

			if($video_align!='') {
				$video .= $video_align;
			}
				if($yt_location=="b_title") {
					$video .= '<iframe width="'.$yt_size[0].'" height="'.$yt_size[1].'" src="http://www.youtube.com/embed/'.$random_video_id.'?hd=1&modestbranding=1" frameborder="0" allowfullscreen></iframe>';
				}
				elseif($yt_location=="b_content") {
					$video .= '<iframe width="'.$yt_size[0].'" height="'.$yt_size[1].'" src="http://www.youtube.com/embed/'.$random_video_id.'?hd=1&modestbranding=1" frameborder="0" allowfullscreen></iframe>';
//					return $content."<br/>".$video;
				}
				elseif($yt_location=="b_mid") {
					$video .= '<iframe width="'.$yt_size[0].'" height="'.$yt_size[1].'" src="http://www.youtube.com/embed/'.$random_video_id.'?hd=1&modestbranding=1" frameborder="0" allowfullscreen></iframe>';
//					return $content."<br/>".$video;
				}
			if($video_align!='' && $yt_location!="b_mid") {
				$video .=  '</div>';
			}			
	
		
			if($yt_location == "b_title") {
				return $video." ".$content;
			}
			else if($yt_location == "b_content") {
				return $content." ".$video;
			}
			else if($yt_location == "b_mid") {
				$content_len = strlen($content)/2;
				if($content_len>60) {
					//$content_replace = substr($content,$content_len-10,$content_len+10);

					//$content = str_replace($content_replace,$video_only." ".$content_replace,$content);
				$str_rp = $content;

				$content_dot = explode('.',strip_tags($str_rp));
					
					$content_dot_index = ceil(count($content_dot)/3);
					$content_rep_text = trim($content_dot[$content_dot_index]);
					$content_rep_text_new = "##LINKFIT## ".$content_rep_text;
					$content = str_replace($content_rep_text,$content_rep_text_new,$content);
					$content = str_replace("##LINKFIT##", $video_only, $content);
					return $content;
				}

	//			return $content." ".$image;
			}
			else {
				return $video." ".$content;
			}
		}
		else {
			return $content;
		}
	}
}

if (function_exists('is_admin') && !is_admin()) {
	# create object
	new wpYoutubeKeyword_front();
}