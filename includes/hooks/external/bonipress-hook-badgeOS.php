<?php
if ( ! defined( 'boniPRESS_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 1.0.8
 * @version 1.3
 */
add_filter( 'bonipress_setup_hooks', 'bonipress_register_badgeos_hook', 15 );
function bonipress_register_badgeos_hook( $installed ) {

	if ( ! class_exists( 'BadgeOS' ) ) return $installed;

	$installed['badgeos'] = array(
		'title'         => __( 'BadgeOS', 'bonipress' ),
		'description'   => __( 'Default settings for each BadgeOS Achievement type. These settings may be overridden for individual achievement type.', 'bonipress' ),
		'documentation' => 'http://codex.bonipress.me/hooks/badgeos-achievements/',
		'callback'      => array( 'boniPRESS_Hook_BadgeOS' )
	);

	return $installed;

}

/**
 * BadgeOS Hook
 * @since 1.0.8
 * @version 1.1.2
 */
add_action( 'bonipress_load_hooks', 'bonipress_load_badgeos_hook', 15 );
function bonipress_load_badgeos_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'boniPRESS_Hook_BadgeOS' ) || ! class_exists( 'BadgeOS' ) ) return;

	class boniPRESS_Hook_BadgeOS extends boniPRESS_Hook {

		/**
		 * Construct
		 */
		public function __construct( $hook_prefs, $type = BONIPRESS_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'badgeos',
				'defaults' => ''
			), $hook_prefs, $type );

			$key = '_bonipress_values';
			if ( ! $this->is_main_type )
				$key .= '_' . $this->bonipress_type;

			$this->metakey = $key;

		}

		/**
		 * Run
		 * @since 1.0.8
		 * @version 1.0
		 */
		public function run() {

			add_filter( 'bonipress_post_type_excludes',  array( $this, 'exclude_post_type' ) );

			add_action( 'add_meta_boxes',             array( $this, 'add_metaboxes' ) );
			add_action( 'save_post',                  array( $this, 'save_achivement_data' ) );

			add_action( 'badgeos_award_achievement',  array( $this, 'award_achievent' ), 10, 2 );
			add_action( 'badgeos_revoke_achievement', array( $this, 'revoke_achievement' ), 10, 2 );

		}

		/**
		 * Exclude bbPress Post Types
		 * @since 1.0.8
		 * @version 1.0
		 */
		public function exclude_post_type( $excludes ) {

			$excludes = array_merge( $excludes, badgeos_get_achievement_types_slugs() );

			return $excludes;

		}

		/**
		 * Add Metaboxes
		 * @since 1.0.8
		 * @version 1.0
		 */
		public function add_metaboxes() {

			// Get all Achievement Types
			$badge_post_types = badgeos_get_achievement_types_slugs();
			foreach ( $badge_post_types as $post_type ) {

				add_meta_box(
					'bonipress_badgeos_' . $post_type . '_' . $this->bonipress_type,
					$this->core->plural(),
					array( $this, 'render_meta_box' ),
					$post_type,
					'side',
					'core'
				);

			}

		}

		/**
		 * Render Meta Box
		 * @since 1.0.8
		 * @version 1.1
		 */
		public function render_meta_box( $post ) {

			// Setup is needed
			if ( ! isset( $this->prefs[ $post->post_type ] ) ) {

				$page = BONIPRESS_SLUG . '-hooks';
				if ( ! $this->is_main_type )
					$page = BONIPRESS_SLUG . '_' . $this->bonipress_type . '-hooks';

				echo '<p>' . sprintf( __( 'Please setup your <a href="%s">default settings</a> before using this feature.', 'bonipress' ), admin_url( 'admin.php?page=' . $page ) ) . '</p>';
				return;

			}

			$post_key         = 'bonipress_values' . $this->bonipress_type;

			// Prep Achievement Data
			$prefs            = $this->prefs;
			$achievement_data = bonipress_get_post_meta( $post->ID, $this->metakey, true );
			if ( $achievement_data == '' )
				$achievement_data = $prefs[ $post->post_type ];

?>
<p><strong><?php echo $this->core->template_tags_general( __( '%plural% to Award', 'bonipress' ) ); ?></strong></p>
<p>
	<label class="screen-reader-text" for="bonipress-values-<?php echo $this->bonipress_type; ?>-creds"><?php echo $this->core->template_tags_general( __( '%plural% to Award', 'bonipress' ) ); ?></label>
	<input type="text" name="<?php echo $post_key; ?>[creds]" id="bonipress-values-<?php echo $this->bonipress_type; ?>-creds" value="<?php echo $this->core->number( $achievement_data['creds'] ); ?>" size="8" />
	<span class="description"><?php _e( 'Use zero to disable', 'bonipress' ); ?></span>
</p>
<p><strong><?php _e( 'Protokollvorlage', 'bonipress' ); ?></strong></p>
<p>
	<label class="screen-reader-text" for="bonipress-values-<?php echo $this->bonipress_type; ?>-log"><?php _e( 'Protokollvorlage', 'bonipress' ); ?></label>
	<input type="text" name="<?php echo $post_key; ?>[log]" id="bonipress-values-<?php echo $this->bonipress_type; ?>-log" value="<?php echo esc_attr( $achievement_data['log'] ); ?>" style="width:99%;" />
</p>
<?php

			// If deduction is enabled
			if ( $this->prefs[ $post->post_type ]['deduct'] == 1 ) {

?>
<p><strong><?php _e( 'Deduction Protokollvorlage', 'bonipress' ); ?></strong></p>
<p>
	<label class="screen-reader-text" for="bonipress-values-<?php echo $this->bonipress_type; ?>-log"><?php _e( 'Protokollvorlage', 'bonipress' ); ?></label>
	<input type="text" name="<?php echo $post_key; ?>[deduct_log]" id="bonipress-values-deduct-<?php echo $this->bonipress_type; ?>-log" value="<?php echo esc_attr( $achievement_data['deduct_log'] ); ?>" style="width:99%;" />
</p>
<?php

			}

		}

		/**
		 * Save Achievement Data
		 * @since 1.0.8
		 * @version 1.2
		 */
		public function save_achivement_data( $post_id ) {

			// Post Type
			$post_type = bonipress_get_post_type( $post_id );

			// Make sure this is a BadgeOS Object
			if ( ! in_array( $post_type, badgeos_get_achievement_types_slugs() ) ) return;

			$post_key  = 'bonipress_values' . $this->bonipress_type;

			// Make sure preference is set
			if ( ! isset( $this->prefs[ $post_type ] ) || ! isset( $_POST[ $post_key ]['creds'] ) || ! isset( $_POST[ $post_key ]['log'] ) )
				return;

			// Only save if the settings differ, otherwise we default
			if ( $_POST[ $post_key ]['creds'] == $this->prefs[ $post_type ]['creds'] && $_POST[ $post_key ]['log'] == $this->prefs[ $post_type ]['log'] ) {
			
				bonipress_delete_post_meta( $post_id, $this->metakey );
				return;

			}

			$data = array();

			// Creds
			if ( ! empty( $_POST[ $post_key ]['creds'] ) && $_POST[ $post_key ]['creds'] != $this->prefs[ $post_type ]['creds'] )
				$data['creds'] = $this->core->number( $_POST[ $post_key ]['creds'] );
			else
				$data['creds'] = $this->core->number( $this->prefs[ $post_type ]['creds'] );

			// Log template
			if ( ! empty( $_POST[ $post_key ]['log'] ) && $_POST[ $post_key ]['log'] != $this->prefs[ $post_type ]['log'] )
				$data['log'] = sanitize_text_field( $_POST[ $post_key ]['log'] );
			else
				$data['log'] = sanitize_text_field( $this->prefs[ $post_type ]['log'] );

			// If deduction is enabled save log template
			if ( $this->prefs[ $post_type ]['deduct'] == 1 ) {
				if ( ! empty( $_POST[ $post_key ]['deduct_log'] ) && $_POST[ $post_key ]['deduct_log'] != $this->prefs[ $post_type ]['deduct_log'] )
					$data['deduct_log'] = sanitize_text_field( $_POST[ $post_key ]['deduct_log'] );
				else
					$data['deduct_log'] = sanitize_text_field( $this->prefs[ $post_type ]['deduct_log'] );
			}

			// Update sales values
			bonipress_update_post_meta( $post_id, $this->metakey, $data );

		}

		/**
		 * Award Achievement
		 * Run by BadgeOS when ever needed, we make sure settings are not zero otherwise
		 * award points whenever this hook fires.
		 * @since 1.0.8
		 * @version 1.1
		 */
		public function award_achievent( $user_id, $achievement_id ) {

			$post_type        = bonipress_get_post_type( $achievement_id );

			// Settings are not set
			if ( ! isset( $this->prefs[ $post_type ]['creds'] ) ) return;

			// Get achievemen data
			$achievement_data = bonipress_get_post_meta( $achievement_id, $this->metakey, true );
			if ( $achievement_data == '' )
				$achievement_data = $this->prefs[ $post_type ];

			// Make sure its not disabled
			if ( $achievement_data['creds'] == 0 ) return;

			// Execute
			$post_type_object = get_post_type_object( $post_type );
			$this->core->add_creds(
				$post_type_object->labels->name,
				$user_id,
				$achievement_data['creds'],
				$achievement_data['log'],
				$achievement_id,
				array( 'ref_type' => 'post' ),
				$this->bonipress_type
			);

		}

		/**
		 * Revoke Achievement
		 * Run by BadgeOS when a users achievement is revoed.
		 * @since 1.0.8
		 * @version 1.2
		 */
		public function revoke_achievement( $user_id, $achievement_id ) {

			$post_type        = bonipress_get_post_type( $achievement_id );

			// Settings are not set
			if ( ! isset( $this->prefs[ $post_type ]['creds'] ) ) return;

			// Get achievemen data
			$achievement_data = bonipress_get_post_meta( $achievement_id, $this->metakey, true );
			if ( $achievement_data == '' )
				$achievement_data = $this->prefs[ $post_type ];

			// Make sure its not disabled
			if ( $achievement_data['creds'] == 0 ) return;

			// Execute
			$post_type_object = get_post_type_object( $post_type );
			$this->core->add_creds(
				$post_type_object->labels->name,
				$user_id,
				0 - $achievement_data['creds'],
				$achievement_data['deduct_log'],
				$achievement_id,
				array( 'ref_type' => 'post' ),
				$this->bonipress_type
			);

		}

		/**
		 * Preferences for BadgeOS
		 * @since 1.0.8
		 * @version 1.1
		 */
		public function preferences() {

			$prefs            = $this->prefs;
			$badge_post_types = badgeos_get_achievement_types_slugs();

			foreach ( $badge_post_types as $post_type ) {

				if ( in_array( $post_type, apply_filters( 'bonipress_badgeos_excludes', array( 'step' ) ) ) ) continue;

				if ( ! isset( $prefs[ $post_type ] ) )
					$prefs[ $post_type ] = array(
						'creds'      => 10,
						'log'        => '',
						'deduct'     => 1,
						'deduct_log' => '%plural% for revoked achievement'
					);

				$post_type_object = get_post_type_object( $post_type );

?>
<div class="hook-instance">
	<h3><?php printf( __( 'Earning: %s', 'bonipress' ), $post_type_object->labels->singular_name ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $post_type, 'creds' ) ); ?>"><?php echo $this->core->plural(); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $post_type, 'creds' ) ); ?>" id="<?php echo $this->field_id( array( $post_type, 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs[ $post_type ]['creds'] ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $post_type, 'log' ) ); ?>"><?php _e( 'Protokollvorlage', 'bonipress' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $post_type, 'log' ) ); ?>" id="<?php echo $this->field_id( array( $post_type, 'log' ) ); ?>" placeholder="<?php _e( 'required', 'bonipress' ); ?>" value="<?php echo esc_attr( $prefs[ $post_type ]['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<div class="hook-instance">
	<h3><?php printf( __( 'Revoked: %s', 'bonipress' ), $post_type_object->labels->singular_name ); ?></h3>
	<div class="row">
		<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $post_type, 'deduct' ) ); ?>"><?php echo $this->core->plural(); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $post_type, 'deduct' ) ); ?>" id="<?php echo $this->field_id( array( $post_type, 'deduct' ) ); ?>" value="<?php echo $this->core->number( $prefs[ $post_type ]['deduct'] ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $post_type, 'deduct_log' ) ); ?>"><?php _e( 'Protokollvorlage', 'bonipress' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $post_type, 'deduct_log' ) ); ?>" id="<?php echo $this->field_id( array( $post_type, 'deduct_log' ) ); ?>" placeholder="<?php _e( 'required', 'bonipress' ); ?>" value="<?php echo esc_attr( $prefs[ $post_type ]['deduct_log'] ); ?>" class="form-control" />
				<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php

			}

		}

	}

}