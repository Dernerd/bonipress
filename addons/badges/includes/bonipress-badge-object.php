<?php
if ( ! defined( 'boniPRESS_VERSION' ) ) exit;

/**
 * boniPRESS_Badge class
 * @see http://codex.bonipress.me/classes/bonipress_badge/
 * @since 1.7
 * @version 1.1
 */
if ( ! class_exists( 'boniPRESS_Badge' ) ) :
	class boniPRESS_Badge extends boniPRESS_Object {

		public $post_id          = false;

		public $title            = '';
		public $earnedby         = 0;
		public $manual           = false;

		public $levels           = array();
		public $main_image       = false;

		public $level            = false;
		public $level_id         = false;
		public $level_label      = false;
		public $level_image      = false;

		public $image_width      = false;
		public $image_height     = false;

		public $references       = array();
		public $point_types      = array();
		protected $user_meta_key = '';

		public $user_id          = false;
		public $current_level    = false;

		/**
		 * Construct
		 */
		function __construct( $object = NULL, $level_id = NULL ) {

			parent::__construct();

			if ( is_object( $object ) && isset( $object->post_type ) && $object->post_type == BONIPRESS_BADGE_KEY )
				$this->post_id = $object->ID;

			elseif ( is_numeric( $object ) && bonipress_get_post_type( $object ) == BONIPRESS_BADGE_KEY )
				$this->post_id = absint( $object );

			else return false;

			$this->image_width   = BONIPRESS_BADGE_WIDTH;
			$this->image_height  = BONIPRESS_BADGE_HEIGHT;

			$this->user_meta_key = BONIPRESS_BADGE_KEY . $this->post_id;

			if ( $level_id !== NULL ) $this->level_id = absint( $level_id );

			$this->populate( $object, $level_id );

		}

		/**
		 * Populate
		 * @since 1.0
		 * @version 1.0
		 */
		protected function populate( $object = NULL, $level_id = NULL ) {

			if ( $this->post_id === false ) return;

			// Get base badge details
			$this->title        = ( isset( $object->post_title ) ) ? $object->post_title : bonipress_get_the_title( $this->post_id );
			$this->earnedby     = ( isset( $object->earnedby ) ) ? $object->earnedby : $this->get_user_count( $level_id );
			$this->levels       = ( isset( $object->levels ) ) ? $object->levels : bonipress_get_badge_levels( $this->post_id );

			if ( ! empty( $this->levels ) ) {

				$this->references  = array();
				$this->point_types = array();
				$this->compare     = $this->levels[0]['compare'];

				if ( ! empty( $this->levels[0]['requires'] ) ) {
					foreach ( $this->levels[0]['requires'] as $requirement_id => $req ) {

						if ( $req['reference'] != '' && ! in_array( $req['reference'], $this->references ) )
							$this->references[] = $req['reference'];

						if ( $req['type'] != '' && ! in_array( $req['type'], $this->point_types ) )
							$this->point_types[] = $req['type'];

					}
				}

			}

			// Indicate manual badge
			if ( absint( bonipress_get_post_meta( $this->post_id, 'manual_badge', true ) ) === 1 )
				$this->manual = true;

			// If we requested a particular level
			if ( $level_id !== NULL )
				$this->level = $this->get_level( $level_id );

			// Get images
			$this->main_image  = $this->get_image( 'main' );
			$this->level_image = $this->get_image( $level_id );

		}

		/**
		 * Get User Count
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_user_count( $level_id = NULL ) {

			if ( $this->post_id === false ) return 0;

			$count = bonipress_get_post_meta( $this->post_id, 'total-users-with-badge', true );
			if ( $count == '' || $level_id !== NULL ) {

				global $wpdb;

				$level_filter = ( $level_id !== NULL && is_numeric( $level_id ) ) ? $wpdb->prepare( "AND meta_value = %s", $level_id ) : '';

				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT user_id ) FROM {$wpdb->usermeta} WHERE meta_key = %s {$level_filter};", bonipress_get_meta_key( $this->user_meta_key ) ) );
				if ( $count === NULL ) $count = 0;

				if ( $level_id === NULL )
					bonipress_update_post_meta( $this->post_id, 'total-users-with-badge', $count, true );

			}

			return apply_filters( 'bonipress_count_users_with_badge', absint( $count ), $this->post_id );

		}

		/**
		 * Get Level
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_level( $level_id = false ) {

			if ( $level_id === false || empty( $this->levels ) || ! array_key_exists( $level_id, $this->levels ) ) return false;

			return $this->levels[ $level_id ];

		}

		/**
		 * Get Level Requirements
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_level_requirements( $level_id = false ) {

			if ( $level_id === false || empty( $this->levels ) || ! array_key_exists( $level_id, $this->levels ) ) return false;

			return $this->levels[ $level_id ]['requires'];

		}

		/**
		 * Get Level Reward
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_level_reward( $level_id = false ) {

			if ( $level_id === false || empty( $this->levels ) || ! array_key_exists( $level_id, $this->levels ) ) return false;

			return $this->levels[ $level_id ]['reward'];

		}

		/**
		 * User Has Badge
		 * @since 1.0
		 * @version 1.0
		 */
		public function user_has_badge( $user_id = false, $level_id = 0 ) {

			$has_badge = false;
			if ( $user_id === false ) return $has_badge;

			$this->user_id       = absint( $user_id );
			$this->current_level = $this->get_users_current_level( $user_id );

			if ( $this->current_level !== false ) {

				$has_badge = true;

				if ( (int) $this->current_level < absint( $level_id ) )
					$has_badge = false;

			}

			return apply_filters( 'bonipress_user_has_badge', $has_badge, $user_id, $this->post_id, $level_id, $this->current_level );

		}

		/**
		 * Get Users Current Level
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_users_current_level( $user_id = false ) {

			if ( $user_id === false ) return $has_badge;

			global $bonipress_current_account;

			$user_id       = absint( $user_id );

			if ( bonipress_is_current_account( $user_id ) && isset( $bonipress_current_account->badge_ids ) && ! empty( $bonipress_current_account->badge_ids ) && array_key_exists( $this->post_id, $bonipress_current_account->badge_ids ) )
				return absint( $bonipress_current_account->badge_ids[ $this->post_id ] );

			$current_level = bonipress_get_user_meta( $user_id, $this->user_meta_key, '', true );
			$current_level = ( ! empty( $current_level ) ) ? absint( $current_level ) : false;

			return $current_level;

		}

		/**
		 * Get Level Reached
		 * @since 1.0
		 * @version 1.0
		 */
		public function query_users_level( $user_id = false ) {

			if ( $user_id === false || empty( $this->levels ) ) return false;

			global $wpdb, $bonipress_log_table;

			$base_requirements = $this->levels[0]['requires'];
			$compare           = $this->levels[0]['compare'];
			$requirements      = count( $base_requirements );
			$level_reached     = false;
			$results           = array();

			// Based on the base requirements, we first get the users log entry results
			if ( ! empty( $base_requirements ) ) {
				foreach ( $base_requirements as $requirement_id => $requirement ) {

					if ( $requirement['type'] == '' )
						$requirement['type'] = BONIPRESS_DEFAULT_TYPE_KEY;

					$bonipress = bonipress( $requirement['type'] );
					if ( $bonipress->exclude_user( $user_id ) ) continue;

					$having = 'COUNT(*)';
					if ( $requirement['by'] != 'count' )
						$having = 'SUM(creds)';

					$query  = $wpdb->get_var( $wpdb->prepare( "SELECT {$having} FROM {$bonipress_log_table} WHERE ctype = %s AND ref = %s AND user_id = %d;", $requirement['type'], $requirement['reference'], $user_id ) );
					
					$query  = apply_filters( 'bonipress_badge_requirement', $query, $requirement_id, $requirement, $having, $user_id );

					if ( $query === NULL ) $query = 0;

					$results[ $requirement['reference'] ] = $query;

				}
			}

			// Next we loop through the levels and see compare the previous results to the requirements to determan our level
			foreach ( $this->levels as $level_id => $level_setup ) {

				$reqs_met = 0;
				foreach ( $level_setup['requires'] as $requirement_id => $requirement ) {

					if ( $results[ $requirement['reference'] ] >= $requirement['amount'] )
						$reqs_met++;

				}

				if ( $compare === 'AND' && $reqs_met >= $requirements )
					$level_reached = $level_id;

				elseif ( $compare === 'OR' && $reqs_met > 0 )
					$level_reached = $level_id;

			}

			do_action( 'bonipress_badge_level_reached', $user_id, $this->post_id, $level_reached );

			return $level_reached;

		}

		/**
		 * Get Users Next Level ID
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_users_next_level_id( $user_id = false ) {

			if ( ! $this->user_has_badge( $user_id ) )
				return 0;

			$max_level = count( $this->levels );
			if ( $this->current_level >= $max_level )
				return $max_level;

			return $this->current_level;

		}

		/**
		 * Get Users Next Level
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_users_next_level( $user_id = false ) {

			if ( ! $this->user_has_badge( $user_id ) )
				return false;

			$next_level_id = $this->get_users_next_level_id( $user_id );

			return $this->get_level( $next_level_id );

		}

		/**
		 * Assign Badge to User
		 * @since 1.0
		 * @version 1.0
		 */
		public function assign( $user_id = false, $level_id = 0 ) {

			if ( $user_id === false || absint( $user_id ) === 0 ) return false;

			$previous_level = -1;
			$new_level      = $level_id;

			if ( $this->user_has_badge( $user_id ) ) {

				// Right now we can not earn the same badge over and over again
				// Planing on adding in an option to override this
				if ( $this->current_level == $level_id ) return true;

				$previous_level = $this->current_level;

			}

			else {
				$this->user_id       = $user_id;
				$this->current_level = $new_level;
			}

			$execute = apply_filters( 'bonipress_badge_assign', true, $user_id, $new_level, $this );

			// Need to update counter with new assignments
			if ( $execute ) {

				$new_level = apply_filters( 'bonipress_badge_user_value', $new_level, $user_id, $this->post_id );
				bonipress_update_user_meta( $user_id, $this->user_meta_key, '', $new_level );

				// Need to update counter with new assignments
				if ( $new_level == 0 ) {

					$this->earnedby ++;

					bonipress_update_post_meta( $this->post_id, 'total-users-with-badge', $this->earnedby );

				}

				$this->payout_reward( $previous_level, $new_level );

				do_action( 'bonipress_after_badge_assign', $user_id, $this->post_id, $new_level );

			}

			return true;

		}

		/**
		 * Payout Rewards
		 * @since 1.0
		 * @version 1.0
		 */
		public function payout_reward( $previous_level = -1, $new_level = 0 ) {

			// Earning the badge
			if ( $previous_level < 0 && $new_level == 0 ) {

				$reward = $this->get_level_reward( $new_level );
				if ( $reward !== false && ( $reward['log'] == '' || $reward['amount'] == 0 ) ) return false;

				$bonipress = bonipress( $reward['type'] );

				// Make sure we only get points once for each level we reach for each badge
				if ( ! $bonipress->has_entry( 'badge_reward', $this->post_id, $this->user_id, 0, $reward['type'] ) ) {

					$exec = apply_filters( 'customize_bonipress_badge_condition', true, $this->post_id, $this->user_id, $reward['type']);

					if( $exec ) {

						$bonipress->add_creds(
							'badge_reward',
							$this->user_id,
							$reward['amount'],
							$reward['log'],
							$this->post_id,
							0,
							$reward['type']
						);

						do_action( 'bonipress_badge_rewardes', $this->user_id, $previous_level, $new_level, $reward, $this );

					}

				}

			}

			// Earning a new level as well
			else {

				// Loop through levels to make sure we do not miss rewards if we jump more than one level
				for ( $i = $previous_level; $i <= $new_level; $i++ ) {

					$reward = $this->get_level_reward( $i );
					if ( $reward !== false && ( $reward['log'] == '' || $reward['amount'] == 0 ) ) continue;

					$bonipress = bonipress( $reward['type'] );

					// Make sure we only get points once for each level we reach for each badge
					if ( ! $bonipress->has_entry( 'badge_reward', $this->post_id, $this->user_id, $i, $reward['type'] ) ) {

						$exec = apply_filters( 'customize_bonipress_badge_condition', true, $this->post_id, $this->user_id, $reward['type']);

						if( $exec ) {

							$bonipress->add_creds(
								'badge_reward',
								$this->user_id,
								$reward['amount'],
								$reward['log'],
								$this->post_id,
								$i,
								$reward['type']
							);

							do_action( 'bonipress_badge_rewardes', $this->user_id, $previous_level, $new_level, $reward, $this );

						}

					}

				}

			}

			return true;

		}

		/**
		 * Assign Badge to All
		 * @since 1.0
		 * @version 1.0
		 */
		public function assign_all() {

			if ( $this->post_id === false || empty( $this->levels ) ) return false;

			global $wpdb, $bonipress_log_table;

			$results = array();

			// Need to have some requirements set or we are in trouble
			if ( ! empty( $this->levels[0]['requires'] ) ) {

				// Single requirement
				if ( count( $this->levels[0]['requires'] ) == 1 ) {

					$requirement         = $this->levels[0]['requires'][0];
					$having              = ( $requirement['by'] != 'count' ) ? 'SUM( creds )' : 'COUNT( id )';
					$requirement['type'] = ( $requirement['type'] == '' ) ? BONIPRESS_DEFAULT_TYPE_KEY : $requirement['type'];

					$results             = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT user_id, {$having} as total FROM {$bonipress_log_table} WHERE ctype = %s AND ref = %s GROUP BY user_id HAVING {$having} >= %f;", $requirement['type'], $requirement['reference'], $requirement['amount'] ) );

					// Find the level id for each result based on their total
					if ( ! empty( $results ) ) {
						foreach ( $results as $row ) {

							$badge_level_id = 0;

							foreach ( $this->levels as $level_id => $setup ) {
								if ( $row->total >= $setup['requires'][0]['amount'] )
									$badge_level_id = $level_id;
							}

							$row->level_id = $badge_level_id;

						}
					}

				}

				// Multiple requirements
				else {

					$user_ids     = array();

					$requirements = count( $this->levels[0]['requires'] );
					$compare      = $this->levels[0]['compare'];

					// I feel like there must be a better way of doing this
					// If you have a suggestion for how to query all users based on multiple requirements, let me know!
					foreach ( $this->levels as $level_id => $level_setup ) {

						$level_user_ids = array();

						// Get all user IDs that fulfill each requirements set
						if ( ! empty( $level_setup['requires'] ) ) {
							foreach ( $level_setup['requires'] as $requirement_id => $requirement ) {

								$having              = ( $requirement['by'] != 'count' ) ? 'SUM( creds )' : 'COUNT( id )';
								$requirement['type'] = ( $requirement['type'] == '' ) ? BONIPRESS_DEFAULT_TYPE_KEY : $requirement['type'];

								$level_user_ids[ $requirement_id ] = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$bonipress_log_table} WHERE ctype = %s AND ref = %s GROUP BY user_id HAVING {$having} >= %f;", $requirement['type'], $requirement['reference'], $requirement['amount'] ) );

							}
						}

						// OR = get all unique IDs
						if ( $compare == 'OR' ) {

							$list = array();
							foreach ( $level_user_ids as $requirement_id => $list_of_ids ) {
								if ( ! empty( $list_of_ids ) ) {
									foreach ( $list_of_ids as $uid ) {
										if ( ! in_array( $uid, $list ) )
											$list[] = $uid;
									}
								}
							}

						}

						// AND = get IDs that are in all requirements
						else {

							$list = $_list = array();

							foreach ( $level_user_ids as $requirement_id => $list_of_ids ) {
								if ( ! empty( $list_of_ids ) ) {
									foreach ( $list_of_ids as $uid ) {
										if ( ! array_key_exists( $uid, $_list ) )
											$_list[ $uid ] = 1;
										else
											$_list[ $uid ]++;
									}
								}
							}

							foreach ( $_list as $uid => $count ) {
								if ( $count >= $requirements )
									$list[] = $uid;
							}

						}

						// If no user has reached the first level, no one will have reached higher levels and there is no need to continue
						if ( $level_id == 0 && empty( $list ) ) break;

						// Create a list where the array key represents the user ID and the array value represents the badge level reached by the user
						foreach ( $list as $user_id ) {
							$user_ids[ $user_id ] = $level_id;
						}

					}

					if ( ! empty( $user_ids ) ) {
						foreach ( $user_ids as $user_id => $level_reached ) {

							$row           = new StdClass();
							$row->user_id  = $user_id;
							$row->level_id = $level_reached;

							$results[]     = $row;

						}
					}

				}

				// Assign results
				if ( ! empty( $results ) ) {

					// Assign each user
					foreach ( $results as $row )
						bonipress_update_user_meta( $row->user_id, $this->user_meta_key, '', $row->level_id );

					// Update earned count
					$this->earnedby = count( $results );

					bonipress_update_post_meta( $this->post_id, 'total-users-with-badge', $this->earnedby );

				}

			}

			return count( $results );

		}

		/**
		 * Divest Badge from user
		 * @since 1.0
		 * @version 1.0
		 */
		public function divest( $user_id = false ) {

			if ( $user_id === false || absint( $user_id ) === 0 ) return false;

			bonipress_delete_user_meta( $user_id, $this->user_meta_key );

			$this->earnedby --;
			if ( $this->earnedby < 0 ) $this->earnedby = 0;

			bonipress_update_post_meta( $this->post_id, 'total-users-with-badge', $this->earnedby );

			return true;

		}

		/**
		 * Divest Badge from Everyone
		 * @since 1.0
		 * @version 1.0
		 */
		public function divest_all() {

			if ( $this->post_id === false ) return false;

			global $wpdb;

			// Delete connections
			$count = $wpdb->delete(
				$wpdb->usermeta,
				array( 'meta_key' => bonipress_get_meta_key( $this->user_meta_key ) ),
				array( '%s' )
			);

			$this->earnedby = 0;

			bonipress_update_post_meta( $this->post_id, 'total-users-with-badge', $this->earnedby );

			return $count;

		}

		/**
		 * Delete Badge
		 * @since 1.0
		 * @version 1.0
		 */
		public function delete( $delete_post = false ) {

			if ( $this->post_id === false ) return false;

			$this->divest_all();

			if ( ! empty( $this->point_types ) ) {

				foreach ( $this->point_types as $point_type )
					bonipress_delete_option( 'bonipress-badge-references-' . $point_type );

			}

			if ( $delete_post )
				bonipress_delete_post( $this->post_id, true );

			return true;

		}

		/**
		 * Get Badge Image
		 * @since 1.0
		 * @version 1.1
		 */
		public function get_image( $image = NULL ) {

			$image_identification = false;

			$level = 'none';
			if ( $image === 'main' )
				$image_identification = bonipress_get_post_meta( $this->post_id, 'main_image', true );

			elseif ( $image !== NULL && is_numeric( $image ) && isset( $this->levels[ $image ]['attachment_id'] ) ) {

				$image_identification = $this->levels[ $image ]['image_url'];
				if ( $this->levels[ $image ]['attachment_id'] > 0 ) {
					$image_identification = $this->levels[ $image ]['attachment_id'];
					$level                = $image;
				}

			}

			if ( $image_identification === false || strlen( $image_identification ) == 0 ) return false;

			$image_url    = $image_identification;
			
			if ( is_numeric( $image_identification ) &&  strpos( '://', (string) $image_identification ) === false )
				$image_url = bonipress_get_attachment_url( $image_identification );

			$image_width  = ( $this->image_width !== false ) ? ' width="' . esc_attr( $this->image_width ) . '"' : '';
			$image_height = ( $this->image_height !== false ) ? ' height="' . esc_attr( $this->image_height ) . '"' : '';

			$html         = '<img src="' . esc_url( $image_url ) . '" class="' . BONIPRESS_SLUG . '-badge-image badge-level' . esc_attr( $level ) . '" title="' . esc_attr( $this->title ) . '" alt="' . esc_attr( $this->title ) . '"' . $image_width . $image_height . ' />';

			return apply_filters( 'bonipress_badge_image', $html, $image, $this );

		}

	}
endif;
