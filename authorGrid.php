<?php
/*
Plugin Name: Author Grid
Plugin URI: http://www.miniaturebuddha.com/
Description: Display authors in a grid
Author: Matt Diamondstone
Version: 1
Author URI: http://www.miniaturebuddha.com/
*/
 
function authorGrid()
	{
	global $wpdb;

	// GET OPTIONS FROM DATABASE, IF NO OPTIONS EXIST, SET DEFAULTS
	if(get_option('authorGrid_options')) {
		$authorGrid_options = get_option('authorGrid_options');
	} else {
                $authorGrid_options = array(    'title' => 'Authors',
                                                'displayType' => 'cols',
                                                'number' => '1',
                                                'avSize' => '60'
                                                );
	}

	// GET AUTHORS' IDS, NICE NAMES, AND DISPLAY NAMES (in post count 'descending' order)
	$get_authors_by_id = 
				"SELECT ".$wpdb->users.".ID, ".$wpdb->users.".user_nicename ,".$wpdb->users.".display_name
				 FROM ".$wpdb->users.
				" INNER JOIN ".$wpdb->posts." ON ".$wpdb->users.".ID=".$wpdb->posts.".post_author 
				WHERE post_status='publish' GROUP BY post_author ORDER BY count(post_author) DESC";
	$author_ids = $wpdb->get_results($get_authors_by_id);

	// FIND THE NECESSARY WIDTH OF THE DIV THAT SURROUNDS THE WIDGET
	$width = ($authorGrid_options['avSize']+5)*$authorGrid_options['number'];

	// SET DIV
	echo '<div style="padding: 0px; width: '.$width.'px; margin-left: 5px; margin-top: 3px;">';
	
	// IF WE'RE DISPLAYING BY COLUMNS
	if($authorGrid_options['displayType'] == 'cols') {
		$i = 1;
		foreach($author_ids as $author) {

			// DISPLAY AVATAR AS LINK TO AUTHOR'S POSTS PAGE
			echo "<a href=\"".get_bloginfo('siteurl')."/author/".$author->user_nicename."\" style=\"margin-right: 1px;\">";
			echo get_avatar($author->ID, $authorGrid_options['avSize'], none, $author->display_name);
			echo "</a>\n";
			$i++;

			// INSERT <br /> TAG WHEN THE COLUMN LIMIT IS REACHED
			if($i > $authorGrid_options['number']) {
				echo "<br />\n";
				$i = 1;
			}
		}

	// IF WE'RE DISPLAYING BY ROWS
	} else {
		
		// FIGURE OUT HOW MANY COLUMNS WILL GO IN THE FIRST ROW
		$numberOfAuthors = count($author_ids);
		$numberOfColumns = intval($numberOfAuthors / $authorGrid_options['number']);
		if($numberOfColumns < $numberOfAuthors / $authorGrid_options['number']) { 
			$numberOfColumns++;
		}

		$i = 1;
		$r = 0;
		foreach($author_ids as $author) {

			// FIX TOP AND BOTTOM MARGINS FOR ONE-COLUMN DISPLAYS, DISPLAY AVATARS
			if($numberOfColumns == 1) {
				echo "<a href=\"".get_bloginfo('siteurl')."/author/".$author->user_nicename."\" style=\"margin: 1px 0px 0px 0px; padding: 0px;\" >";
				echo get_avatar($author->ID, $authorGrid_options['avSize'], none, $author->display_name);
				echo "</a>\n";
			} else {
				echo "<a href=\"".get_bloginfo('siteurl')."/author/".$author->user_nicename."\" style=\"margin: 0px 2px 0px 0px; padding: 0px;\" >";
				echo get_avatar($author->ID, $authorGrid_options['avSize'], none, $author->display_name);
				echo "</a>\n";
			}

			$i++;
			$numberOfAuthors--;

			// WHEN WE'VE REACHED THE END OF THE ROW, INSERT A <br /> TAG
			if($i > $numberOfColumns) {
				echo "<br />\n";
				$r++;
				$i = 1;
				
				// DETERMINE THE NUMBER OF COLUMNS IN THE NEXT ROW
				if($numberOfAuthors > 0) {
					$numberOfColumns = intval($numberOfAuthors / ($authorGrid_options['number'] - $r));
					if($numberOfColumns < $numberOfAuthors / ($authorGrid_options['number'] - $r)) { 
						$numberOfColumns++;
					}
				}
			}
		}
	}
	// END THE DIV
	echo '</div>';
}
 
function widget_authorGrid($args) {
	global $wpdb;

	// GET OPTIONS FROM DATABASE, IF NO OPTIONS EXIST, SET DEFAULTS
	if(get_option('authorGrid_options')) {
		$authorGrid_options = get_option('authorGrid_options');
	} else {
	$authorGrid_options = array(    'title' => 'Authors',
					'displayType' => 'cols',
					'number' => '1',
					'avSize' => '60'
					);
	}
	extract($args);
	echo $before_widget;
	echo $before_title;?><a><?php echo $authorGrid_options['title']; ?></a><?php echo $after_title;
	authorGrid();
	echo $after_widget;
}
 
function widget_authorGridConfig() {

	global $wpdb;

// UPDATE DATABASE WITH NEW SETTINGS

	// GRAB POST VARIABLES
	if(isset($_POST['authorGrid_submit'])) {
		$authorGrid_title = $_POST['authorGrid_title'];
		$authorGrid_displayType = $_POST['authorGrid_displayType'];
		$authorGrid_number = $_POST['authorGrid_number'];
		$authorGrid_avSize = $_POST['authorGrid_avSize'];

		// SET OPTIONS ARRAY
		$authorGrid_options = array(	'title' => $authorGrid_title,
						'displayType' => $authorGrid_displayType,
						'number' => $authorGrid_number,
						'avSize' => $authorGrid_avSize
						);

		// INSERT OPTIONS ARRAY INTO DATABASE
		update_option( 'authorGrid_options', $authorGrid_options);
		unset($_POST);
	}

// DISPLAY WIDGET OPTIONS

	// GET PRE-EXISTING OPTIONS FROM DATABASE
	$authorGrid_options = get_option('authorGrid_options');

	// FIND THE NUMBER OF AUTHORS WITH AT LEAST ONE PUBLISHED POST
	$numberOfAuthors = $wpdb->get_results("select distinct post_author from $wpdb->posts where post_status='publish' and post_type='post'");

	// SET TITLE
	echo "Title:<br />\n";
	echo "<input type=\"text\" name=\"authorGrid_title\" value=\"".$authorGrid_options['title']."\" style=\"width: 220px;\">";
	echo "<br /><br />\n";

	// SET WHETHER WE'RE USING COLUMNS OR ROWS
	echo "Display in:<br />\n";
	if($authorGrid_options['displayType'] == "rows") {
		echo "<input type=\"radio\" name=\"authorGrid_displayType\" value=\"cols\" /> Columns\n";
		echo "<input type=\"radio\" name=\"authorGrid_displayType\" value=\"rows\" checked=\"checked\" /> Rows\n";
		} else {
		echo "<input type=\"radio\" name=\"authorGrid_displayType\" value=\"cols\" checked=\"checked\" /> Columns\n";
		echo "<input type=\"radio\" name=\"authorGrid_displayType\" value=\"rows\" /> Rows\n";
		}

	echo "<br /><br />\n";

	// SET THE NUMBER OF COLUMNS OR ROWS
	echo "Number of Columns/Rows<br />\n";
	echo "<select name=\"authorGrid_number\">\n";
		for($i=1; $i <= count($numberOfAuthors); $i++) {
			if($i == $authorGrid_options['number']) {
				echo "<option value=\"".$i."\" selected=\"selected\">$i</option>\n";
			} else {
				echo "<option value=\"".$i."\">$i</option>\n";
				}
			}
	echo "</select>";
	echo "<br /><br />\n";

	// SET THE AVATAR SIZE
	echo "Avatar Size<br />\n";
	echo "<input type=\"text\" name=\"authorGrid_avSize\" style=\"width: 35px;\" value=\"".$authorGrid_options['avSize']."\" />";

	// HIDDEN INPUT
	echo "<input type=\"hidden\" name=\"authorGrid_submit\" value=\"1\" />";
	}

function authorGrid_init()
	{
	wp_register_sidebar_widget(
		'authorGrid',
		'Author Grid',
		'widget_authorGrid',
		array(
			'description' => 'Displays all authors\' avatars in a grid'
			)
		 );

	wp_register_widget_control('authorGrid', 'Test 123', 'widget_authorGridConfig', array( 'id_base' => 'authors' ) );
	}

add_action("plugins_loaded", "authorGrid_init");
?>
