<?php
//widget displays achievements earned for the logged in user
class earned_user_achievements_widget extends WP_Widget {

	//process the new widget
	function earned_user_achievements_widget() {
		$widget_ops = array(
			'classname' => 'earned_user_achievements_class',
			'description' => __( 'Displays all achievements earned by the logged in user', 'badgeos' )
		);
		$this->WP_Widget( 'earned_user_achievements_widget', __( 'BadgeOS Earned User Achievements', 'badgeos' ), $widget_ops );
	}

	//build the widget settings form
	function form( $instance ) {
		$defaults = array( 'title' => 'My Achievements', 'number' => '10', 'point_total' => '', 'set_achievements' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$number = $instance['number'];
		$show_text = $instance['show_text'];
		$show_icon = $instance['show_icon'];
		$icon_size = $instance['icon_size'];
		$point_total = $instance['point_total'];
		$set_achievements = ( isset( $instance['set_achievements'] ) ) ? (array) $instance['set_achievements'] : array();
		?>
            <p><label><?php _e( 'Title', 'badgeos' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>"  type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p>
			<p><label><?php _e( 'Number to display (0 = all)', 'badgeos' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'number' ); ?>"  type="text" value="<?php echo absint( $number ); ?>" /></label></p>
         	<p><?php _e( 'Display Badge Title?', 'badgeos' ); ?>: <input type="checkbox" name="<?php echo $this->get_field_name( 'show_text' ); ?>" value="1" <?php if($show_text) echo 'checked="yes"' ?>/> </p>
       		<p><?php _e( 'Show Icon?', 'badgeos' ); ?>: <input type="checkbox" name="<?php echo $this->get_field_name( 'show_icon' ); ?>" value="1" <?php if($show_icon) echo 'checked="yes"' ?>/> </p>
    		<p><?php _e( 'Icon Size (25px minimum)', 'badgeos' ); ?>: <input class="widefat" name="<?php echo $this->get_field_name( 'icon_size' ); ?>"  type="text" value="<?php echo absint( $icon_size ); ?>" /></p>
			<p><label><input type="checkbox" id="<?php echo $this->get_field_name( 'point_total' ); ?>" name="<?php echo $this->get_field_name( 'point_total' ); ?>" <?php checked( $point_total, 'on' ); ?> /> <?php _e( 'Display user\'s total points', 'badgeos' ); ?></label></p>
			<p><?php _e( 'Display only the following Achievement Types:', 'badgeos' ); ?><br />
				<?php
				//get all registered achievements
				$achievements = badgeos_get_achievement_types();

				//loop through all registered achievements
				foreach ( $achievements as $achievement_slug => $achievement ) {

					//hide the step CPT
					if ( $achievement['single_name'] == 'step' )
						continue;

					//if achievement displaying exists in the saved array it is enabled for display
					$checked = checked( in_array( $achievement_slug, $set_achievements ), true, false );

					echo '<label for="' . $this->get_field_name( 'set_achievements' ) . '_' . esc_attr( $achievement_slug ) . '">'
						 . '<input type="checkbox" name="' . $this->get_field_name( 'set_achievements' ) . '[]" id="' . $this->get_field_name( 'set_achievements' ) . '_' . esc_attr( $achievement_slug ) . '" value="' . esc_attr( $achievement_slug ) . '" ' . $checked . ' />'
						 . ' ' . esc_html( ucfirst( $achievement[ 'plural_name' ] ) )
						 . '</label><br />';

				}
				?>
			</p>
        <?php
	}

	//save and sanitize the widget settings
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['number'] = absint( $new_instance['number'] );
		$instance['show_text'] = absint( $new_instance['show_text'] );
		$instance['show_icon'] = absint( $new_instance['show_icon'] );
		$instance['icon_size'] = absint( $new_instance['icon_size'] );
		$instance['point_total'] = ( ! empty( $new_instance['point_total'] ) ) ? sanitize_text_field( $new_instance['point_total'] ) : '';
		$instance['set_achievements'] = array_map( 'sanitize_text_field', $new_instance['set_achievements'] );

		return $instance;
	}

	//display the widget
	function widget( $args, $instance ) {
		global $user_ID;

		extract( $args );

		echo $before_widget;

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };

		//user must be logged in to view earned badges and points
		if ( is_user_logged_in() ) {

			//display user's points if widget option is enabled
			if ( $instance['point_total'] == 'on' )
				echo '<p class="badgeos-total-points">' . sprintf( __( 'My Total Points: %s', 'badgeos' ), '<strong>' . number_format( badgeos_get_users_points() ) . '</strong>' ) . '</p>';

			$achievements = badgeos_get_user_achievements(array('merge' => true));
				
			if ( is_array( $achievements ) && ! empty( $achievements ) ) {

			    $number_to_show = absint( $instance['number'] );
    			$show_text = absint( $instance['show_text'] );			
    			$show_icon = absint( $instance['show_icon'] );
    			$icon_size = absint( $instance['icon_size'] );
    			
				$thecount = 0;

				wp_enqueue_script( 'badgeos-achievements' );
				wp_enqueue_style( 'badgeos-widget' );

				//load widget setting for achievement types to display
				$set_achievements = ( isset( $instance['set_achievements'] ) ) ? $instance['set_achievements'] : '';

				//show most recently earned achievement first
// 				$achievements = array_reverse( $achievements );

				echo '<ul class="widget-achievements-listing">';

    			// auto adjust css height & margin for icon size
    			echo '<style type="text/css">';
    				echo '.widget-achievements-listing .has-thumb .widget-badgeos-item-title { margin-left: '.($icon_size + 10).'px;}';
    				echo '.widget-achievements-listing li.has-thumb { min-height: '.($icon_size + 5).'px;}';
    				echo '.badgeos-icon { position: relative; width: '.($icon_size+6).'px; height: '.($icon_size+6).'px; cursor:pointer; }';
    			echo '</style>';
    			 
				foreach ( $achievements as $achievement ) {

					//verify achievement type is set to display in the widget settings
					//if $set_achievements is not an array it means nothing is set so show all achievements
					if ( ! is_array( $set_achievements ) || in_array( $achievement->post_type, $set_achievements ) ) {

						//exclude step CPT entries from displaying in the widget
						if ( get_post_type( $achievement->ID ) != 'step' ) {

							$permalink  = get_permalink( $achievement->ID );
							$title      = get_the_title( $achievement->ID );
											$instances = count($achievement->instance);
					
					// format tooltip
					$str = '';
					if($instances == 2)
						$str = __( '. Earned once before that.');
					else
					if($instances >= 3) 
						$str = sprintf( __( '. Earned %d times before that.', 'badgeos' ), ($instances - 1));
				
					$tip = sprintf( __( '%s: Last earned on %s%s', 'badgeos' ), $title, date_i18n(get_option('date_format'), $achievement->date_earned), $str);

					// format image
						$thumb = '';
					if ( $show_icon )
						$image_attr = wp_get_attachment_image_src( get_post_thumbnail_id( $achievement->ID ), array( $icon_size, $icon_size ) );

						if ( $image_attr ) {

						if($instances >= 1) 
							$img = '<div class="badgeos-icon"><img class="wp-post-image" width="'. absint( $image_attr[1] ) .'" height="'. absint( $image_attr[2] ) .'" src="'. esc_url( $image_attr[0] ) .'"><span>'.$instances.'</span></div>';
						else
							$img = '<img class="wp-post-image" width="'. absint( $image_attr[1] ) .'" height="'. absint( $image_attr[2] ) .'" src="'. esc_url( $image_attr[0] ) .'">';
							$thumb = '<a style="margin-top: -'. floor( absint( $image_attr[2] ) / 2 ) .'px;" class="badgeos-item-thumb" href="'. esc_url( $permalink ) .'">' . $img .'</a>';
						}
					else {
						// use default image when one is not provided
						if($instances >= 1) 
							$img = '<div class="badgeos-icon"><img class="wp-post-image" width="'. absint( $icon_size ) .'" height="'. absint( $icon_size ) .'" src="' . plugins_url( 'badgeos/images/default-badge.png' ) .'"> <span>'.$instances.'</span></div>';
						else
							$img = '<img class="wp-post-image" width="'. absint( $icon_size ) .'" height="'. absint( $icon_size ) .'" src="' . plugins_url( 'badgeos/images/default-badge.png' ) .'">';
						$thumb = '<a style="margin-top: -'. floor( absint( $icon_size) / 2 ) .'px;" class="badgeos-item-thumb" href="'. esc_url( $permalink ) .'">' . $img .'</a>';				
					}
							$class      = 'widget-badgeos-item-title';
							$item_class = $thumb ? ' has-thumb' : '';

							// Setup credly data if giveable
							$giveable   = credly_is_achievement_giveable( $achievement->ID, $user_ID );
							$item_class .= $giveable ? ' share-credly addCredly' : '';
							$credly_ID  = $giveable ? 'data-credlyid="'. absint( $achievement->ID ) .'"' : '';

							echo '<li id="widget-achievements-listing-item-'. absint( $achievement->ID ) .'" '. $credly_ID .' class="widget-achievements-listing-item'. esc_attr( $item_class ) .'">';
	
							if ( $show_icon ) {
								echo '<span class="tooltip-left" title="'. $tip .'">';
							echo $thumb;
								echo '</span>';
							}
							
							if ( $show_text )
							echo '<a class="widget-badgeos-item-title '. esc_attr( $class ) .'" href="'. esc_url( $permalink ) .'">'. esc_html( $title ) .'</a>';
							
							echo '</li>';

							$thecount++;

							if ( $thecount == $number_to_show && $number_to_show != 0 )
								break;

						}

					}
				}

				echo '</ul><!-- widget-achievements-listing -->';

			}
			else {			
// 			    echo 'You have n  ot yet earned any achievements.';
			}

		} else {

			//user is not logged in so display a message
			_e( 'You must be logged in to view earned achievements', 'badgeos' );

		}

		echo $after_widget;
	}

}

add_action( 'wp_ajax_achievement_send_to_credly', 'badgeos_send_to_credly_handler' );
add_action( 'wp_ajax_nopriv_achievement_send_to_credly', 'badgeos_send_to_credly_handler' );
/**
 * hook in our credly ajax function
 */
function badgeos_send_to_credly_handler() {

	if ( ! isset( $_REQUEST['ID'] ) ) {
		echo json_encode( sprintf( '<strong class="error">%s</strong>', __( 'Error: Sorry, nothing found.', 'badgeos' ) ) );
		die();
	}

	$send_to_credly = $GLOBALS['badgeos_credly']->post_credly_user_badge( get_current_user_id(), $_REQUEST['ID'] );

	if ( $send_to_credly ) {

		echo json_encode( sprintf( '<strong class="success">%s</strong>', __( 'Success: Sent to Credly!', 'badgeos' ) ) );
		die();

	} else {

		echo json_encode( sprintf( '<strong class="error">%s</strong>', __( 'Error: Sorry, Send to Credly Failed.', 'badgeos' ) ) );
		die();

	}
}
