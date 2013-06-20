<?php
/**
 * Content Filters
 *
 * @package BadgeOS
 * @subpackage Front-end
 * @author Credly, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

add_filter( 'the_content', 'badgeos_achievement_submissions' );

/**
 * Displays the submission form on achievement type single pages if the meta option is enabled
 *
 *
 *
 */
function badgeos_achievement_submissions( $content ) {
	global $post;

	if ( is_single() ) {

		// get achievement object for the current post type
		$post_type = get_post_type( $post );
		$achievement = get_page_by_title( $post_type, 'OBJECT', 'achievement-type' );
		if ( !$achievement ) {
			global $wp_post_types;

			$labels = array( 'name', 'singular_name' );
			// check for other variations
			foreach ( $labels as $label ) {
				$achievement = get_page_by_title( $wp_post_types[$post_type]->labels->$label, 'OBJECT', 'achievement-type' );
				if ( $achievement )
					break;
			}
		}

		if ( !$achievement )
			return $content;


		// check if submission or nomination is set
		$earned_by = get_post_meta( $post->ID, '_badgeos_earned_by', true );

		if ( ( $earned_by == 'submission' || $earned_by == 'submission_auto' ) && is_user_logged_in() ) {

			$submission = do_shortcode( '[badgeos_submission]' );

			//return the content with the submission shortcode data
			return $content . $submission;

		}elseif ( $earned_by == 'nomination' && is_user_logged_in() ) {

			$nomination = do_shortcode( '[badgeos_nomination]' );

			//return the content with the nomination shortcode data
			return $content . $nomination;

		}

	}

	return $content;

}


add_action( 'wp_enqueue_scripts', 'badgeos_do_single_filters' );
/**
 * Add filters to remove stuff from our singular pages and add back in how we want it
 */
function badgeos_do_single_filters() {
	// check we're in the right place
	badgeos_is_main_loop();
	// enqueue our stylesheet
	wp_enqueue_style( 'badgeos-single' );
	// no worries.. we'll add back later
	remove_filter( 'the_content', 'wpautop' );
	// filter out the post title
	// add_filter( 'the_title', 'badgeos_remove_to_reformat_entries_title', 10 ,2 );
	// and filter out the post image
	add_filter( 'post_thumbnail_html', 'badgeos_remove_to_reformat_entries_title', 10, 2 );
}

/**
 * Filter out the post title/post image and add back (later) how we want it
 */
function badgeos_remove_to_reformat_entries_title( $html, $id ) {

	// remove, but only on the main loop!
	if ( badgeos_is_main_loop( $id ) )
		return '';

	// nothing to see here... move along
	return $html;
}

add_filter( 'the_content', 'badgeos_reformat_entries', 9 );
/**
 * Filter badge content to add our removed content back
 */
function badgeos_reformat_entries( $content ) {
	wp_enqueue_style( 'badgeos-front' );

	$badge_id = get_the_ID();

	// filter, but only on the main loop!
	if ( !badgeos_is_main_loop( $badge_id ) )
		return wpautop( $content );

	// now that we're where we want to be, tell the filters to stop removing
	$GLOBALS['badgeos_reformat_content'] = true;
	
	// the user may have submitted an achievement. save the form before we do anything else
	if ( is_user_logged_in() ) {

		// check if step unlock option is set to submission review
		get_currentuserinfo();

		if ( badgeos_save_submission_data() )
			printf( '<p>%s</p>', __( 'Submission saved successfully.', 'badgeos' ) );
	}

	// do badge title markup
	// $title = '<h1 class="badge-title">'. get_the_title() .'</h1>';

	// check if user has earned this Achievement, and add an 'earned' class
	$achievement = badgeos_get_user_achievements( array( 'achievement_id' => absint( $badge_id ), 'merge' => true) );
	$class = $achievement ? ' earned' : '';
	
	// wrap our content, add the thumbnail and title and add wpautop back
	$newcontent = '<div class="achievement-wrap'. $class .'">';
	$newcontent .= '<div class="alignleft badgeos-item-image">'. badgeos_get_achievement_post_thumbnail( $badge_id ) .'</div>';
	// $newcontent .= $title;

	// Points for badge
	$newcontent .= badgeos_achievement_points_markup();
	$newcontent .= wpautop( $content );
	
	// include output detailing when the user earned the achievement
	$newcontent .= badgeos_user_earned_achievement_list_markup($achievement[0]);
	
	// Include output for our steps
	$newcontent .= badgeos_get_required_achievements_for_achievement_list( $badge_id );

	// Include achievement earners, if this achievement supports it
	if ( $show_earners = get_post_meta( $badge_id, '_badgeos_show_earners', true ) )
		$newcontent .= badgeos_get_achievement_earners_list( $badge_id );

	$newcontent .= '</div><!-- .achievement-wrap -->';

	// Ok, we're done reformating
	$GLOBALS['badgeos_reformat_content'] = false;
	
	return $newcontent;
}

/**
 * helper function tests that we're in the main loop
 */
function badgeos_is_main_loop( $id = false ) {

	$slugs = badgeos_get_achievement_types_slugs();
	// only run our filters on the badgeos singular pages
	if ( is_admin() || empty( $slugs ) || !is_singular( $slugs ) )
		return false;
	// w/o id, we're only checking template context
	if ( !$id )
		return true;

	// Checks several variables to be sure we're in the main loop (and won't effect things like post pagination titles)
	return ( ( $GLOBALS['post']->ID == $id ) && in_the_loop() && empty( $GLOBALS['badgeos_reformat_content'] ) );
}


/**
 * Gets achivement's required steps and returns HTML markup for these steps
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievement's post ID
 * @param  integer $user_id        A given user's ID
 * @return string                  The markup for our list
 */
function badgeos_get_required_achievements_for_achievement_list( $achievement_id = 0, $user_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Grab the current user's ID if none was specifed
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Grab our achievement's required steps
	$steps = badgeos_get_required_achievements_for_achievement( $achievement_id );

	// Return our markup output
	return badgeos_get_required_achievements_for_achievement_list_markup( $steps, $user_id );

}

/**
 * Generate HTML markup for an achievement's required steps
 *
 * This will generate an unorderd list (<ul>) if steps are non-sequential
 * and an ordered list (<ol>) if steps require sequentiality.
 *
 * @since  1.0.0
 * @param  array   $steps 	 An achievement's required steps
 * @param  integer $user_id A given user's ID
 * @return string           The markup for our list
 */
function badgeos_get_required_achievements_for_achievement_list_markup( $steps, $achievement_id = 0, $user_id = 0 ) {

	// If we don't have any steps, or our steps aren't an array, return nothing
	if ( ! $steps || ! is_array( $steps ) )
		return null;

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	$count = count( $steps );

	// If we have no steps, return nothing
	if ( ! $count )
		return null;

	// Grab the current user's ID if none was specifed
	if ( ! $user_id )
		$user_id = wp_get_current_user()->ID;

	// Setup our variables
	$output = $step_output = '';
	$container = badgeos_is_achievement_sequential() ? 'ol' : 'ul';

	// Concatenate our output
	foreach ( $steps as $step ) {

		// check if user has earned this Achievement, and add an 'earned' class
		$earned_status = badgeos_get_user_achievements( array(
			'user_id' => absint( $user_id ),
			'achievement_id' => absint( $step->ID ),
			'since' => absint( badgeos_achievement_last_user_activity( $achievement_id, $user_id ) )
		) ) ? 'user-has-earned' : 'user-has-not-earned';

		// get step title and if it doesn't have a title get the step trigger type post-meta
		$title = !empty( $step->post_title ) ? $step->post_title : get_post_meta( $step->ID, '_badgeos_trigger_type', true );
		$step_output .= '<li class="'. apply_filters( 'badgeos_step_class', $earned_status, $step ) .'">'. apply_filters( 'badgeos_step_title_display', $title, $step ) . '</li>';
	}
	$post_type_object = get_post_type_object( $step->post_type );

	$output .= '<h4>' . apply_filters( 'badgeos_steps_heading', sprintf( __( '%1$d Required %2$s', 'badgeos' ), $count, $post_type_object->labels->name ), $steps ) . '</h4>';
	$output .= '<' . $container .' class="badgeos-required-achievements">';
	$output .= $step_output;
	$output .= '</'. $container .'><!-- .badgeos-required-achievements -->';

	// Return our output
	return $output;

}


/**
 * Generate HTML markup for a users earnings of an achievement
 *
 *
 * @since  1.0.0
 * @param  array   $achievement 	 Single entry from the achievement array
 * @return string           The markup for our list
 */
function badgeos_user_earned_achievement_list_markup( $achievement ) {

	// If the user has not earned the achievement, return nothing
	$instances = count($achievement->instance);
	
	if ( ! $instances  )
		return null;
		
	// format title	
	if($instances == 1)
		$output = '<h4>' .  __( 'You earned this achievement once:', 'badgeos' ) . '</h4>';
	else
		$output = '<h4>' .  sprintf( __( 'You earned this achievement %d times:', 'badgeos' ), $instances) . '</h4>';
	
	// Concatenate our output with earned instances if more than one
	$max = 10;
	$num = 0;
	$output .= "<ul>";
	foreach ( $achievement->instance as $instance ) {
		if($num++ >= $max) {
			$remaining = count($achievement->instance) - $max;
			$output .= "<li>";
				$output .= sprintf( __( 'And %d more time%s before this.', 'badgeos' ), $remaining, $remaining == 1 ? '' : 's');
			$output .= "</li>";	
			break;
		}
		$output .= "<li>";
			$output .= sprintf( __( 'On %s', 'badgeos' ), date_i18n(get_option('date_format'), $instance->date_earned));
		$output .= "</li>";			
	}
	$output .= "</ul>";		
 $output .= badgeos_user_earned_achievement_popup('content',340);
	// Return our output
	return $output;

}

function badgeos_user_earned_achievement_popup( $content, $achievement_id )
{
    wp_enqueue_style('badgeos-front-jquery-ui-theme');
    wp_enqueue_script('jquery-ui-dialog');
    
    // only display dialog if there is something to say
    $message = get_post_meta( $achievement_id, '_badgeos_congratulations_text', true ); 
    if(empty($message))
        return;
    
    $post = get_post($achievement_id);
    $thumb = get_the_post_thumbnail($achievement_id);
    $points = get_post_meta( $achievement_id, '_badgeos_points', true ); 
                
	$output .= '<div id="badgeos-congrats-popup" style="display:none" title="'.__( 'Congratulations!', 'badgeos' ).'">';
		$output .=  '<div class="badgeos-congrats-popup-thumb">'.$thumb.'</div>';
		$output .=  '<div class="badgeos-congrats-popup-title">'.$post->post_title.'</div>';
		$output .=  '<div class="badgeos-congrats-popup-message">'.$message.'</div>';
		if($points > 0) {
			$output .=  '<div class="badgeos-congrats-popup-points">'.$points.' '.__( 'Points Awarded!', 'badgeos' ).'</div>';		
		}
	$output .=  '</div>';
	
	$output .= '<script>
					jQuery(function() {
		  				jQuery( "#badgeos-congrats-popup" ).dialog({
							buttons: {
								 "Share": function() {
								jQuery( this ).dialog( "close" );
								},
								Ok: function() {
								jQuery( this ).dialog( "close" );
								}
							}
						});
					});
				</script>';
	
	return $output;
}

//	$output .= '<script>
//			jQuery(function() {
//			jQuery( "#dialog" ).dialog();
//			});
//			</script>';

/**
 * Filter our step titles to link to achievements and achievement type archives
 *
 * @since  1.0.0
 * @param  string $title Our step title
 * @param  object $step  Our step's post object
 * @return string        Our potentially udated title
 */
function badgeos_step_link_title_to_achievement( $title, $step ) {

	// Grab our step requirements
	$step_requirements = badgeos_get_step_requirements( $step->ID );

	// Setup a URL to link to a specific achievement or an achievement type
	if ( ! empty( $step_requirements['achievement_post'] ) )
		$url = get_permalink( $step_requirements['achievement_post'] );
	// elseif ( ! empty( $step_requirements['achievement_type'] ) )
	// 	$url = get_post_type_archive_link( $step_requirements['achievement_type'] );

	// If we have a URL, update the title to link to it
	if ( isset( $url ) && ! empty( $url ) )
		$title = '<a href="' . esc_url( $url ) . '">' . $title . '</a>';

	return $title;
}
add_filter( 'badgeos_step_title_display', 'badgeos_step_link_title_to_achievement', 10, 2 );

/**
 * Generate markup for an achievement's points output
 *
 * @since  1.0.0
 * @param  integer $achievement_id The given achievment's ID
 * @return string                  The HTML markup for our points
 */
function badgeos_achievement_points_markup( $achievement_id = 0 ) {

	// Grab the current post ID if no achievement_id was specified
	if ( ! $achievement_id ) {
		global $post;
		$achievement_id = $post->ID;
	}

	// Return our markup
	return ( $points = get_post_meta( $achievement_id, '_badgeos_points', true ) ) ? '<div class="badgeos-item-points">' . sprintf( __( '%d Points', 'badgeos' ), $points ) . '</div>' : '';
}

add_filter( 'post_class', 'badgeos_add_earned_class_single' );
/**
 * Adds "earned"/"not earned" post_class based on viewer's status
 * @param  array $classes Post classes
 * @return array          Updated post classes
 */
function badgeos_add_earned_class_single( $classes ) {
	global $user_ID;

	// check if current user has earned the achievement they're viewing
	$classes[] = badgeos_get_user_achievements( array( 'user_id' => $user_ID, 'achievement_id' => get_the_ID() ) ) ? 'user-has-earned' : 'user-has-not-earned';

	return $classes;
}
