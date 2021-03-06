<?php
if ( ! defined( 'boniPRESS_VERSION' ) ) exit;

/**
 * boniPRESS_Hooks_Module class
 * @since 0.1
 * @version 1.3
 */
if ( ! class_exists( 'boniPRESS_Hooks_Module' ) ) :
	class boniPRESS_Hooks_Module extends boniPRESS_Module {

		public $setup;

		/**
		 * Construct
		 */
		public function __construct( $type = BONIPRESS_DEFAULT_TYPE_KEY ) {

			parent::__construct( 'boniPRESS_Hooks_Module', array(
				'module_name' => 'hooks',
				'option_id'   => 'bonipress_pref_hooks',
				'defaults'    => array(
					'installed'   => array(),
					'active'      => array(),
					'hook_prefs'  => array()
				),
				'labels'      => array(
					'menu'        => __( 'Hooks', 'bonipress' ),
					'page_title'  => __( 'Hooks', 'bonipress' ),
					'page_header' => __( 'Hooks', 'bonipress' )
				),
				'screen_id'   => BONIPRESS_SLUG . '-hooks',
				'accordion'   => false,
				'menu_pos'    => 20
			), $type );

		}

		/**
		 * Load Hooks
		 * @since 0.1
		 * @version 1.1
		 */
		public function module_init() {

			// Loop through each active hook and call the run() method.
			if ( ! empty( $this->installed ) ) {

				foreach ( $this->installed as $key => $gdata ) {

					if ( $this->is_active( $key ) && isset( $gdata['callback'] ) ) {
						$this->call( 'run', $gdata['callback'] );
					}

				}

			}

			// Ajax handlers for hook management
			add_action( 'wp_ajax_bonipress-hook-order',  array( $this, 'ajax_hook_activation' ) );
			add_action( 'wp_ajax_bonipress-save-hook',   array( $this, 'ajax_save_hook_prefs' ) );

		}

		/**
		 * Get Hooks
		 * @since 0.1
		 * @version 1.3
		 */
		public function get( $save = false ) {

			$installed = array();

			// Registrations
			$installed['registration'] = array(
				'title'         => __( '%plural% für Registrierungen', 'bonipress' ),
				'description'   => __( 'Vergebe %_plural% für Benutzer, die Deiner Webseite beitreten.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-registrierungen/',
				'callback'      => array( 'boniPRESS_Hook_Registration' )
			);

			// Anniversary
			$installed['anniversary'] = array(
				'title'         => __( '%plural% zum Jubiläum', 'bonipress' ),
				'description'   => __( 'Vergebe %_plural% für jedes Jahr, in dem ein Benutzer Mitglied ist.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-jubilaeum/',
				'callback'      => array( 'boniPRESS_Hook_Anniversary' )
			);

			// Site Visits
			$installed['site_visit'] = array(
				'title'         => __( '%plural% für tägliche Besuche', 'bonipress' ),
				'description'   => __( 'Vergebe %_plural% für den täglichen Besuch Deiner Webseite.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-taegliche-besuche/',
				'callback'      => array( 'boniPRESS_Hook_Site_Visits' )
			);

			// View Content
			$installed['view_contents'] = array(
				'title'         => __( '%plural% für das Anzeigen von Inhalten', 'bonipress' ),
				'description'   => __( 'Vergebe %_plural% für das Anzeigen von Inhalten.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-punkte-fuer-das-anzeigen-von-inhalten/',
				'callback'      => array( 'boniPRESS_Hook_View_Contents' )
			);

			// Logins
			$installed['logging_in'] = array(
				'title'         => __( '%plural% für Logins', 'bonipress' ),
				'description'   => __( 'Vergebe %_plural% fürs Einloggen.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-einloggen/',
				'callback'      => array( 'boniPRESS_Hook_Logging_In' )
			);

			// Content Publishing
			$installed['publishing_content'] = array(
				'title'         => __( '%plural% für veröffentlichen von Inhalten', 'bonipress' ),
				'description'   => __( 'Vergebe %_plural% für das Veröffentlichen von Inhalten.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-veroeffentlichen-von-inhalten/',
				'callback'      => array( 'boniPRESS_Hook_Publishing_Content' )
			);

			// Content Deletions
			$installed['deleted_content'] = array(
				'title'         => __( '%plural% für verworfenen Inhalt', 'bonipress' ),
				'description'   => __( '%_plural% vergeben oder abziehen, wenn Inhalte in den Papierkorb verschoben werden.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-inhalte-loeschen/',
				'callback'      => array( 'boniPRESS_Hook_Delete_Content' )
			);

			// Commenting
			$installed['comments'] = array(
				'title'         => ( ! function_exists( 'dsq_is_installed' ) ) ? __( '%plural% für Kommentare', 'bonipress' ) : __( '%plural% für Disqus Kommentare', 'bonipress' ),
				'description'   => __( 'Vergebe %_plural% für Kommentare.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-kommentare/',
				'callback'      => array( 'boniPRESS_Hook_Comments' )
			);

			// Link Clicks
			$installed['link_click'] = array(
				'title'         => __( '%plural% für Klicken auf Links', 'bonipress' ),
				'description'   => str_replace( '%shortcode%', '<a href="https://n3rds.work/docs/bonipress-shortcodes-bonipress_link/" target="_blank">bonipress_link</a>', __( 'Vergebe %_plural% für Klicks auf Links, die mit dem Shortcode %shortcode% generiert wurden.', 'bonipress' ) ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-klicken-auf-links/',
				'callback'      => array( 'boniPRESS_Hook_Click_Links' )
			);

			// Video Views
			$installed['video_view'] = array(
				'title'         => __( '%plural% für Ansehen von Videos', 'bonipress' ),
				'description'   => str_replace( '%shortcode%', '<a href="https://n3rds.work/docs/bonipress-shortcode-bonipress_video/" target="_blank">bonipress_video</a>', __( 'Vergebe %_plural% für Videos, die mit dem %shortcode% Shortcode eingebettet wurden.', 'bonipress' ) ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-ansehen-von-videos/',
				'callback'      => array( 'boniPRESS_Hook_Video_Views' )
			);

			// Affiliation
			$installed['affiliate'] = array(
				'title'         => __( '%plural% für Empfehlungen', 'bonipress' ),
				'description'   => __( 'Vergebe %_plural% für Anmeldungen oder Besucherempfehlungen.', 'bonipress' ),
				'documentation' => 'https://n3rds.work/docs/bonipress-hooks-empfehlungen/',
				'callback'      => array( 'boniPRESS_Hook_Affiliate' )
			);

			$installed = apply_filters( 'bonipress_setup_hooks', $installed, $this->bonipress_type );

			if ( $save === true && $this->core->user_is_point_admin() ) {
				$new_data = array(
					'active'     => $this->active,
					'installed'  => $installed,
					'hook_prefs' => $this->hook_prefs
				);
				bonipress_update_option( $this->option_id, $new_data );
			}

			$this->installed = $installed;
			return $installed;

		}

		/**
		 * Call
		 * Either calls a given class method or function.
		 * @since 0.1
		 * @version 1.1.1
		 */
		public function call( $call, $callback, $return = NULL ) {

			// Class
			if ( is_array( $callback ) && class_exists( $callback[0] ) ) {

				$class = $callback[0];
				$methods = get_class_methods( $class );
				if ( in_array( $call, $methods ) ) {

					$new = new $class( ( isset( $this->hook_prefs ) ) ? $this->hook_prefs : array(), $this->bonipress_type );
					return $new->$call( $return );

				}

			}

			// Function
			elseif ( ! is_array( $callback ) ) {

				if ( function_exists( $callback ) ) {

					if ( $return !== NULL )
						return call_user_func( $callback, $return, $this );
					else
						return call_user_func( $callback, $this );

				}

			}

			if ( $return !== NULL )
				return array();

		}

		/**
		 * Settings Header
		 * @since 1.7
		 * @version 1.0
		 */
		public function settings_header() {

			wp_enqueue_style( 'bonipress-bootstrap-grid' );
			wp_enqueue_style( 'bonipress-forms' );

			wp_localize_script(
				'bonipress-widgets',
				'boniPRESSHooks',
				array(
					'type' => $this->bonipress_type
				)
			);
			wp_enqueue_script( 'bonipress-widgets' );

			if ( wp_is_mobile() )
				wp_enqueue_script( 'jquery-touch-punch' );

		}

		/**
		 * Admin Page
		 * @since 0.1
		 * @version 1.2.1
		 */
		public function admin_page() {

			// Security
			if ( ! $this->core->user_is_point_admin() ) wp_die( 'Zugriff abgelehnt' );

			// Get installed
			$installed   = $this->get();
			$this->setup = bonipress_get_option( $this->option_id . '_sidebar', 'default' );
			$button      = '';

?>
<style type="text/css">
.widget-content { display: block; float: none; clear: both; }
.widget-content label.subheader { display: block; font-weight: bold; padding: 0 0 0 0; margin: 0 0 6px 0; }
.widget-content ol { margin: 0 0 6px 0; }
.widget-content ol.inline:after { content: ""; display: block; height: 1px; clear: both; }
.widget-content ol li { list-style-type: none; margin: 0 0 0 0; padding: 0 0 0 0; }
.widget-content ol.inline li { display: block; float: left; min-width: 45%; }
.widget-content ol.inline li.empty { display: none; }
.widget-content ol li input, .widget-content ol li select { margin-bottom: 6px; }
.widget-content ol li input[type="checkbox"], .widget-content ol li input[type="radio"] { margin-bottom: 0; }
.widget-content ol li input.mini { margin-right: 12px; }
.widget-content ol li input.long { width: 100%; }
.widget-content ol li label { display: block; margin-bottom: 6px; }
.widget-content select.limit-toggle { vertical-align: top; }

.widget-content .hook-instance { margin-bottom: 18px; border-bottom: 1px dashed #d5d5d5; }
.widget-content .hook-instance:last-of-type { border-bottom: none; margin-bottom: 0; }
.widget-content .hook-instance h3 { margin: 0 0 12px 0; }
.widget-content .hook-instance .row > div .form-group:last-child { margin-bottom: 0; }
.widget-content .page-title-action { top: 0; float: right; }

#sidebar-active .widget-inside .form .form-group span.description { display: block; font-style: italic; font-size: 12px; line-height: 16px; padding-left: 0; padding-right: 0; padding-top: 6px; }
#available-widgets .widget .widget-description { min-height: 50px; }
#sidebar-active .widget-inside form .widget-content { padding-top: 12px; }
#sidebar-active .widget-inside form .widget-control-actions { padding-top: 12px; border-top: 1px dashed #dedede; margin-top: 12px; }
.form .radio { margin-bottom: 12px; }
</style>
<div class="wrap">
	<h1><?php _e( 'Hooks', 'bonipress' ); if ( BONIPRESS_DEFAULT_LABEL === 'boniPRESS' ) : ?> <a href="https://n3rds.work/docs/bonipress-hooks-einrichten/" class="page-title-action" target="_blank"><?php _e( 'Dokumentation', 'bonipress' ); ?></a><?php endif; ?></h1>
	<div class="widget-liquid-left">
		<div id="widgets-left">
			<div id="available-widgets" class="widgets-holder-wrap">
				<div class="sidebar-name">
					<div class="sidebar-name-arrow"><br /></div>
					<h2><?php _e( 'Verfügbare Hooks' ); ?> <span id="removing-widget"><?php _ex( 'Deaktivieren', 'removing-widget' ); ?> <span></span></span></h2>
				</div>
				<div class="widget-holder">
					<div class="sidebar-description">
						<p class="description"><?php _e( 'Um einen Hook zu aktivieren, ziehe ihn in eine Seitenleiste oder klicke darauf. Um einen Hook zu deaktivieren und seine Einstellungen zu löschen, ziehe ihn zurück.' ); ?></p>
					</div>
					<div id="widget-list">
<?php

			// If we have hooks
			if ( ! empty( $installed ) ) {

				global $bonipress_field_id;

				$bonipress_field_id = '__i__';

				// Loop though them
				$count = 0;
				foreach ( $installed as $key => $data ) {

?>
						<div id="widget-bonipress-hook_<?php echo $key; ?>" class="widget ui-draggable"<?php if ( $this->is_active( $key ) ) echo ' style="display: none;"'; ?>>
							<div class="widget-top">
								<div class="widget-title-action"></div>
								<div class="widget-title ui-draggable-handle">
									<h3><?php echo $this->core->template_tags_general( $data['title'] ); ?></h3>
								</div>
							</div>
							<div class="widget-inside bonipress-metabox">
								<form method="post" action="" class="form">
									<div class="widget-content">

										<?php $this->call( 'preferences', $data['callback'] ); ?>

									</div>
									<input type="hidden" name="widget-id" class="widget-id" value="<?php echo $key; ?>" />
									<input type="hidden" name="id_base" class="id_base" value="<?php echo $key; ?>" />
									<input type="hidden" name="add_new" class="add_new" value="single" />
									<div class="widget-control-actions">
										<div class="alignleft">
											<a class="widget-control-remove" href="#remove"><?php _e( 'Löschen', 'bonipress' ); ?></a> | <a class="widget-control-close" href="#close"><?php _e( 'Schließen', 'bonipress' ); ?></a><?php if ( BONIPRESS_DEFAULT_LABEL === 'boniPRESS' && array_key_exists( 'documentation', $data ) && ! empty( $data['documentation'] ) ) : ?> | <a class="hook-documentation" href="<?php echo esc_url( $data['documentation'] ); ?>" target="_blank">Hook Dokumentation</a><?php endif; ?>
										</div>
										<div class="alignright">
											<input type="submit" name="savewidget" id="widget-bonipress-hook-<?php echo $key; ?>-__i__-savewidget" class="button button-primary widget-control-save right" value="<?php _e( 'Speichern', 'bonipress' ); ?>" />
											<span class="spinner"></span>
										</div>
										<br class="clear" />
									</div>
								</form>
							</div>
							<div class="widget-description"><?php echo nl2br( $this->core->template_tags_general( $data['description'] ) ); ?></div>
						</div>
<?php

					$count++;
				}

				$bonipress_field_id = '';

			}

?>
					</div>
					<br class="clear" />
				</div>
				<br class="clear" />
			</div>
		</div>
	</div>
	<div class="widget-liquid-right">

		<?php $this->display_sidebars(); ?>

	</div>
	<form method="post"><?php wp_nonce_field( 'manage-bonipress-hooks', '_wpnonce_widgets', false ); ?></form>
	<br class="clear" />
</div>
<div class="widgets-chooser">
	<ul class="widgets-chooser-sidebars"></ul>
	<div class="widgets-chooser-actions">
		<button class="button-secondary"><?php _e( 'Abbrechen', 'bonipress' ); ?></button>
		<button class="button-primary"><?php _e( 'Hook hinzufügen', 'bonipress' ); ?></button>
	</div>
</div>

<script type="text/javascript">
jQuery(function($) {

	$( 'div.widget-liquid-right' ).on( 'change', 'select.limit-toggle', function(){

		if ( $(this).find( ':selected' ).val() != 'x' )
			$(this).prev().attr( 'type', 'text' ).val( 0 );
		else
			$(this).prev().attr( 'type', 'hidden' ).val( 0 );

	});

});
</script>
<?php

		}

		/**
		 * Display Sidebars
		 * @since 1.7
		 * @version 1.0
		 */
		public function display_sidebars() {

			// Default setup
			if ( $this->setup == 'default' ) {

?>
<div id="widgets-right" class="single-sidebar">
	<div class="sidebars-column-0">
		<div class="widgets-holder-wrap">
			<div id="sidebar-active" class="widgets-sortables ui-droppable ui-sortable">
				<div class="sidebar-name">
					<div class="sidebar-name-arrow"><br /></div>
					<h2><?php _e( 'Aktive Hooks', 'bonipress' ); ?></h2>
				</div>
				<div class="sidebar-description">
					<p class="description"><?php _e( 'Die folgenden Hooks werden für alle Benutzer verwendet.', 'bonipress' ); ?></p>
				</div>
<?php

			// If we have hooks
			if ( ! empty( $this->installed ) ) {

				// Loop though them
				foreach ( $this->installed as $key => $data ) {

					// Show only active hooks
					if ( ! $this->is_active( $key ) ) continue;

?>
				<div id="widget-bonipress-hook_<?php echo $key; ?>" class="widget" style="z-index: auto;">
					<div class="widget-top">
						<div class="widget-title-action"></div>
						<div class="widget-title ui-draggable-handle">
							<h3><?php echo $this->core->template_tags_general( $data['title'] ); ?></h3>
						</div>
					</div>
					<div class="widget-inside bonipress-metabox">
						<form method="post" action="" class="form">
							<div class="widget-content">

								<?php $this->call( 'preferences', $data['callback'] ); ?>

							</div>
							<input type="hidden" name="widget-id" class="widget-id" value="<?php echo $key; ?>" />
							<input type="hidden" name="id_base" class="id_base" value="<?php echo $key; ?>" />
							<input type="hidden" name="add_new" class="add_new" value="single" />
							<div class="widget-control-actions">
								<div class="alignleft">
									<a class="widget-control-remove" href="#remove"><?php _e( 'Löschen', 'bonipress' ); ?></a> | <a class="widget-control-close" href="#close"><?php _e( 'Schließen', 'bonipress' ); ?></a><?php if ( BONIPRESS_DEFAULT_LABEL === 'boniPRESS' && array_key_exists( 'documentation', $data ) && ! empty( $data['documentation'] ) ) : ?>  | <a class="hook-documentation" href="<?php echo esc_url( $data['documentation'] ); ?>" target="_blank">Hook Dokumentation</a><?php endif; ?>
								</div>
								<div class="alignright">
									<input type="submit" name="savewidget" id="widget-bonipress-hook-<?php echo $key; ?>-__i__-savewidget" class="button button-primary widget-control-save right" value="<?php _e( 'Speichern', 'bonipress' ); ?>" />
									<span class="spinner"></span>
								</div>
								<br class="clear" />
							</div>
						</form>
					</div>
					<div class="widget-description"><?php echo nl2br( $this->core->template_tags_general( $data['description'] ) ); ?></div>
				</div>
<?php

				}

			}

?>

			</div>
		</div>
	</div>
</div>
<?php

			}

			// Let others play
			else {

				do_action( 'bonipress-hook-sidebars' , $this );
				do_action( 'bonipress-hook-sidebars-' . $this->bonipress_type , $this );

			}

		}

		/**
		 * AJAX: Save Hook Activations
		 * Either saves the hook order (no use) or saves hooks being activated or deactivated.
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function ajax_hook_activation() {

			check_ajax_referer( 'manage-bonipress-hooks', 'savewidgets' );

			if ( ! isset( $_POST['sidebars'] ) ) die;

			$ctype      = sanitize_key( $_POST['ctype'] );
			if ( $ctype !== $this->bonipress_type ) return;

			$installed  = $this->get();

			if ( ! empty( $_POST['sidebars'] ) ) {
				foreach ( $_POST['sidebars'] as $sidebar_id => $hooks ) {

					$hooks = explode( ',', $hooks );

					// First get all the hook IDs
					$clean_hook_ids = array();
					if ( ! empty( $hooks ) ) {
						foreach ( $hooks as $hook_id ) {
							$clean_hook_ids[] = sanitize_key( str_replace( array( 'new-widget-bonipress-hook_', 'widget-bonipress-hook_' ), '', $hook_id ) );
						}
					}

					// One for all
					if ( $sidebar_id == 'sidebar-active' ) {

						$active_hooks = array();
						if ( ! empty( $this->active ) && ! empty( $clean_hook_ids ) ) {
							foreach ( $this->active as $already_active_hook_id ) {

								// Retain active hooks that are set to remain active
								if ( in_array( $already_active_hook_id, $clean_hook_ids ) && ! in_array( $already_active_hook_id, $active_hooks ) )
									$active_hooks[] = $already_active_hook_id;

							}
						}

						// Loop through all hooks in this sidebase and consider them as active
						if ( ! empty( $clean_hook_ids ) ) {
							foreach ( $clean_hook_ids as $hook_id ) {

								if ( array_key_exists( $hook_id, $installed ) && ! in_array( $hook_id, $active_hooks ) )
									$active_hooks[] = $hook_id;

							}
						}

						$active_hooks = array_unique( $active_hooks, SORT_STRING );
						$this->active = $active_hooks;

						// Update our settings to activate the hook(s)
						bonipress_update_option( $this->option_id, array(
							'active'     => $this->active,
							'installed'  => $installed,
							'hook_prefs' => $this->hook_prefs
						) );

					}

				}
			}

		}

		/**
		 * AJAX: Save Hook Settings
		 * @since 1.7
		 * @version 1.0.4
		 */
		public function ajax_save_hook_prefs() {
		    
			check_ajax_referer( 'manage-bonipress-hooks', 'savewidgets' );

			$sidebar    = sanitize_text_field( $_POST['sidebar'] );
			$hook_id    = sanitize_key( $_POST['id_base'] );
			$ctype      = sanitize_key( $_POST['ctype'] );
			$hook_prefs = false;

			if ( $ctype !== $this->bonipress_type ) return;

			$installed  = $this->get();

			// $_POST['bonipress_pref_hooks'] will not be available if we remove the last active hook
			// Removing all hooks from the active sidebar will trigger this method so we need to take that
			// into account
			if ( isset( $_POST['bonipress_pref_hooks'] ) || isset($_POST[ 'bonipress_pref_hooks_' . $ctype ]) ) {

				// Get hook settings
				if ( $ctype == BONIPRESS_DEFAULT_TYPE_KEY && array_key_exists( $hook_id, $_POST['bonipress_pref_hooks']['hook_prefs'] ) )
					$hook_prefs = $_POST['bonipress_pref_hooks']['hook_prefs'][ $hook_id ];

				elseif ( $ctype != BONIPRESS_DEFAULT_TYPE_KEY && array_key_exists( $hook_id, $_POST[ 'bonipress_pref_hooks_' . $ctype ]['hook_prefs'] ) )
					$hook_prefs = $_POST[ 'bonipress_pref_hooks_' . $ctype ]['hook_prefs'][ $hook_id ];

				if ( $hook_prefs === false ) die;

				if ( ! array_key_exists( $hook_id, $installed ) )
					die( '<p>No longer available hook</p>' );

				// New settings
				$new_settings = $this->call( 'sanitise_preferences', $installed[ $hook_id ]['callback'], $hook_prefs );

				// If something went wrong use the old settings
				if ( ! is_array( $new_settings ) || empty( $new_settings ) )
					$new_settings = $hook_prefs;

				$this->hook_prefs[ $hook_id ] = $new_settings;

			}

			// Update our settings to activate the hook(s)
			bonipress_update_option( $this->option_id, array(
				'active'     => $this->active,
				'installed'  => $installed,
				'hook_prefs' => $this->hook_prefs
			) );

			if ( isset( $_POST['bonipress_pref_hooks'] ) || isset($_POST[ 'bonipress_pref_hooks_' . $ctype ]) ) 
			    $this->call( 'preferences', $installed[ $hook_id ]['callback'] );

			die;

		}

	}
endif;
