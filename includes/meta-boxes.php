<?php
/**
 * Admin Meta Boxes
 *
 * @package BadgeOS
 * @subpackage Admin
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Register custom meta boxes used throughout BadgeOS
 *
 * @since  1.0.0
 * @param  array  $meta_boxes The existing metabox array we're filtering
 * @return array              An updated array containing our new metaboxes
 */

function proxy_teamos_courses_cmb_select() {
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    if(!is_plugin_active('teamrocket-badgeos-add-on/teamrocket-badgeos.php')) 
        return null;
    else    
        return teamos_courses_cmb_select();
}
   
function badgeos_custom_metaboxes( array $meta_boxes ) {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_badgeos_';

	// Grab our achievement types as an array
	$achievement_types = badgeos_get_achievement_types_slugs();

	// Setup our $post_id, if available
	$post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

	// New Achievement Types
	$meta_boxes[] = array(
		'id'         => 'achievement_type_data',
		'title'      => __( 'Achievement Type Data', 'badgeos' ),
		'pages'      => array( 'achievement-type' ), // Post type
		'context'    => 'normal',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name' => __( 'Plural Name', 'badgeos' ),
				'desc' => __( 'The plural name for this achievement (Title is singular name).', 'badgeos' ),
				'id'   => $prefix . 'plural_name',
				'type' => 'text_medium',
			),
			array(
				'name'    => __( 'Show in Menu?', 'badgeos' ),
				'desc' 	 => ' '.__( 'Yes, show this achievement in the BadgeOS menu.', 'badgeos' ),
				'id'      => $prefix . 'show_in_menu',
				'type'	 => 'checkbox',
			),
			array(
				'name' => __( 'Default Badge Image', 'badgeos' ),
				'desc' => sprintf(
					__( 'To set a default image, use the <strong>Default Achievement Image</strong> metabox to the right. For best results, use a square .png file with a transparent background, at least 200x200 pixels. Or, design a badge using the %1$s.', 'badgeos' ),
					badgeos_get_badge_builder_link( array( 'link_text' => __( 'Credly Badge Builder', 'badgeos' ) ) )
					),
				'id'   => $prefix . 'upload_badge_image_achievement',
				'type' => 'text_only',
			),
			array(
					'name'    => __( 'Hidden Type?', 'badgeos' ),		// TR: 
					'desc' 	 => ' '.__( 'Yes, hide this achievement type from the frontend.', 'badgeos' ),
					'id'      => $prefix . 'hidden_type',
					'type'	 => 'checkbox',
			),
		)
	);

	// Achievements
	$meta_boxes[] = apply_filters( 'badgeos_achievement_data_meta_box', array(
		'id'         => 'achievement_data',
		'title'      => __( 'Achievement Data', 'badgeos' ),
		'pages'      => $achievement_types, // Post type
		'context'    => 'advanced',
		'priority'   => 'high',
		'show_names' => true, // Show field names on the left
		'fields'     => apply_filters( 'badgeos_achievement_data_meta_box_fields', array(
			array(
				'name' => __( 'Upload Badge Image', 'badgeos' ),
				'desc' => sprintf(
					__( '<p>To set an image use the <strong>Achievement Image</strong> metabox to the right. For best results, use a square .png file with a transparent background, at least 200x200 pixels. Or, design a badge using the %1$s.</p><p>If no image is specified, this achievement will default to the %2$s featured image.</p>', 'badgeos' ),
					badgeos_get_badge_builder_link( array( 'link_text' => __( 'Credly Badge Builder', 'badgeos' ) ) ),
					'<a href="' . admin_url('edit.php?post_type=achievement-type') . '">' . __( 'Achievement Type\'s', 'badgeos' ) . '</a>'
					),
				'id'   => $prefix . 'upload_badge_image_achievement',
				'type' => 'text_only',
			),
			array(
				'name' => __( 'Points Awarded', 'badgeos' ),
				'desc' => ' '.__( 'Points awarded for earning this achievement (optional). Leave empty if no points are awarded.', 'badgeos' ),
				'id'   => $prefix . 'points',
				'type' => 'text_small',
			),
			array(
				'name'    => __( 'Earned By:', 'badgeos' ),
				'desc'    => __( 'How this achievement can be earned.', 'badgeos' ),
				'id'      => $prefix . 'earned_by',
				'type'    => 'select',
				'options' => apply_filters( 'badgeos_achievement_earned_by', array(
						array( 'name' => 'Completing Steps',           'value' => 'triggers' ),
						array( 'name' => 'Minimum Number of Points',   'value' => 'points' ),
						array( 'name' => 'Minimum Number of Points by Course',   'value' => 'course_points' ),
						
						array( 'name' => 'Submission (Reviewed)',      'value' => 'submission' ),
						array( 'name' => 'Submission (Auto-accepted)', 'value' => 'submission_auto' ),
						array( 'name' => 'Nomination',                 'value' => 'nomination' ),
						array( 'name' => 'Admin-awarded Only',         'value' => 'admin' ),
					) )
			),
			array(
				'name' => __( 'Minimum Points Required', 'badgeos' ),
				'desc' => ' '.__( 'Fewest number of points required for earning this achievement.', 'badgeos' ),
				'id'   => $prefix . 'points_required',
				'type' => 'text_small',
			),
			array(
			    'name' => __( 'Selected Course', 'badgeos' ),
			    'desc' => ' '.__( 'Points must come from this course.', 'badgeos' ),
			    'id'   => $prefix . 'selected_course',
			    'type' => 'select',
			    'options' => proxy_teamos_courses_cmb_select()
			),
			array(
				'name' => __( 'Sequential Steps', 'badgeos' ),
				'desc' => ' '.__( 'Yes, steps must be completed in order.', 'badgeos' ),
				'id'   => $prefix . 'sequential',
				'type' => 'checkbox',
			),
			array(
				'name' => __( 'Show Earners', 'badgeos' ),
				'desc' => ' '.__( 'Yes, display a list of users who have earned this achievement.', 'badgeos' ),
				'id'   => $prefix . 'show_earners',
				'type' => 'checkbox',
			),
			array(
				'name' => __( 'Congratulations Text', 'badgeos' ),
				'desc' => __( 'Displayed after achievement is earned.', 'badgeos' ),
				'id'   => $prefix . 'congratulations_text',
				'type' => 'textarea_code',
			),
			array(
					'name'    => __( 'Notification Type', 'badgeos' ),
					'desc'    => __( 'How should the notification be presented?', 'badgeos' ),
					'id'      => $prefix . 'notification_type',
					'type'    => 'select',
					'options' => array(
							array( 'name' => 'Standard (upper right, fades away)',    	      'value' => 'standard' ),
							array( 'name' => 'Fancy (middle of screen dialog box)',   	      'value' => 'popup' ),
							array( 'name' => 'Generic (middle of screen empty dialog box)',   'value' => 'generic' ),
							array( 'name' => 'None (silent)',   						      'value' => 'none' ),
					)
			),
			array(
				'name' => __( 'Maximum Earnings', 'badgeos' ),
				'desc' => ' '.__( 'Number of times a user can earn this badge (set to 0 or leave blank for no maximum).', 'badgeos' ),
				'id'   => $prefix . 'maximum_earnings',
				'type' => 'text_small',
// 				'std' => '1',
			),
			array(
				'name'    => __( 'Hidden?', 'badgeos' ),
				'desc'    => '',
				'id'      => $prefix . 'hidden',
				'type'    => 'select',
				'options' => array(
					array( 'name' => 'Show to User', 'value' => 'show', ),
					array( 'name' => 'Hidden to User', 'value' => 'hidden', ),
				),
			),
		), $prefix, $achievement_types )
	), $achievement_types );

	// Submissions
	$meta_boxes[] = array(
		'id'         => 'submission_data',
		'title'      => __( 'Submission Status', 'badgeos' ),
		'pages'      => array( 'submission' ), // Post types
		'context'    => 'side',
		'priority'   => 'default',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name' => __( 'Status', 'badgeos' ),
				'desc' => __( 'Approve or Deny this entry.', 'badgeos' ),
				'id'   => $prefix . 'submission_status',
				'type' => 'select',
				'options' => array(
					array( 'name' => 'Pending Review', 'value' => 'pending', ),
					array( 'name' => 'Approved', 'value' => 'approved', ),
					array( 'name' => 'Denied', 'value' => 'denied', ),
				),
			),
			array(
				'name' => __( 'Achievement ID to Award', 'badgeos' ),
				'desc' => '<a href="' . esc_url( admin_url('post.php?post=' . get_post_meta( $post_id, '_badgeos_submission_achievement_id', true ) . '&action=edit') ) . '">' . __( 'View Achievement', 'badgeos' ) . '</a>',
				'id'   => $prefix . 'submission_achievement_id',
				'type'	 => 'text_small',
			),
		)
	);

	// Nominations
	$meta_boxes[] = array(
		'id'         => 'nomination_data',
		'title'      => __( 'Nomination Data', 'badgeos' ),
		'pages'      => array( 'nomination' ), // Post types
		'context'    => 'side',
		'priority'   => 'default',
		'show_names' => true, // Show field names on the left
		'fields'     => array(
			array(
				'name' => __( 'Status', 'badgeos' ),
				'desc' => __( 'Approve or Deny this entry.', 'badgeos' ),
				'id'   => $prefix . 'nomination_status',
				'type' => 'select',
				'options' => array(
					array( 'name' => 'Pending Review', 'value' => 'pending', ),
					array( 'name' => 'Approved', 'value' => 'approved', ),
					array( 'name' => 'Denied', 'value' => 'denied', ),
				),
			),
			array(
				'name' => __( 'Nominee', 'badgeos' ),
				'id'   => $prefix . 'nomination_user_id',
				'desc' => ( $post_id && get_post_type( $post_id ) == 'nomination' ) ? get_userdata( absint( get_post_meta( $post_id, '_badgeos_nomination_user_id', true ) ) )->display_name : '',
				'type' => 'text_medium',
			),
			array(
				'name' => __( 'Nominated By', 'badgeos' ),
				'id'   => $prefix . 'nominating_user_id',
				'desc' => ( $post_id && get_post_type( $post_id ) == 'nomination' ) ? get_userdata( absint( get_post_meta( $post_id, '_badgeos_nominating_user_id', true ) ) )->display_name : '',
				'type' => 'text_medium',
			),
			array(
				'name' => __( 'Achievement ID to Award', 'badgeos' ),
				'desc' => '<a href="' . esc_url( admin_url('post.php?post=' . get_post_meta( $post_id, '_badgeos_nomination_achievement_id', true ) . '&action=edit') ) . '">' . __( 'View Achievement', 'badgeos' ) . '</a>',
				'id'   => $prefix . 'nomination_achievement_id',
				'type'	 => 'text_small',
			),
		)
	);

	return $meta_boxes;
}
add_filter( 'cmb_meta_boxes', 'badgeos_custom_metaboxes' );

/**
 * Render a text-only field type for our CMB integration.
 *
 * @since  1.0.0
 * @param  array $field The field data array
 * @param  string $meta The stored meta for this field (which will always be blank)
 * @return string       HTML markup for our field
 */
function badgeos_cmb_render_text_only( $field = array(), $meta = '' ) {
	echo $field['desc'];
}
add_action( 'cmb_render_text_only', 'badgeos_cmb_render_text_only', 10, 2 );

add_action( 'add_meta_boxes', 'badgeos_submission_attachments_meta_box' );

/**
 * Register the Submission attachments meta box
 *
 * @since  1.1.0
 * @return void
 */
function badgeos_submission_attachments_meta_box() {

	//register the submission attachments meta box
	add_meta_box( 'badgeos_submission_attachments_id', __( 'Submission Attachments', 'badgeos' ), 'badgeos_submission_attachments', 'submission' );

}

/**
 * Display all Submission attachments in a meta box
 *
 * @since  1.1.0
 * @param  object $post The post content
 * @return object 		The modified post content
 */
function badgeos_submission_attachments( $post = null) {

	//return all submission attachments
	if ( $submission_attachments = badgeos_get_submission_attachments( absint( $post->ID ) ) ) {
		echo $submission_attachments;
	}else{
		_e( 'No attachments on this submission', 'badgeos' );
	}

}
