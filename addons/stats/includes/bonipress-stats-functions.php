<?php
if ( ! defined( 'boniPRESS_STATS_VERSION' ) ) exit;

/**
 * Get Type Color
 * @since 1.6
 * @version 1.1
 */
if ( ! function_exists( 'bonipress_get_color_sets' ) ) :
	function bonipress_get_color_sets() {

		$color_sets  = apply_filters( 'bonipress_stats_color_sets', array(
			0 => array( 'positive' => 'rgba(204,175,11,1)', 'negative' => 'rgba(51,80,244,1)' ),
			1 => array( 'positive' => 'rgba(221,130,59,1)', 'negative' => 'rgba(34,125,196,1)' ),
			2 => array( 'positive' => 'rgba(207,73,68,1)', 'negative' => 'rgba(48,182,187,1)' ),
			3 => array( 'positive' => 'rgba(180,60,56,1)', 'negative' => 'rgba(75,195,199,1)' ),
			4 => array( 'positive' => 'rgba(34,34,34,1)', 'negative' => 'rgba(221,221,221,1)' )
		) );

		// Use HEX colors
		if ( BONIPRESS_STATS_COLOR_TYPE == 'hex' ) {

			foreach ( $color_sets as $row => $setup ) {

				$color_sets[ $row ]['positive'] = bonipress_rgb_to_hex( $color_sets[ $row ]['positive'] );
				$color_sets[ $row ]['negative'] = bonipress_rgb_to_hex( $color_sets[ $row ]['negative'] );

			}

		}

		// Use RGB colors
		elseif ( BONIPRESS_STATS_COLOR_TYPE == 'rgb' ) {

			foreach ( $color_sets as $row => $setup ) {

				$check_positive = explode( ',', str_replace( array( 'rgba(', 'rgb(', ')' ), '', $setup['positive'] ) );

				// In a perfect world, colors are always provided in proper RBGA format.
				if ( count( $check_positive ) == 4 ) {

					$setup['positive'] = str_replace( 'rgba(', 'rgb(', $setup['positive'] );
					$setup['positive'] = str_replace( 'rgba(', 'rgb(', $setup['positive'] );
					$setup['positive'] = str_replace( ',' . $check_positive[3] . ')', ')', $setup['positive'] );

				}

				$check_negative = explode( ',', str_replace( array( 'rgba(', 'rgb(', ')' ), '', $setup['negative'] ) );

				// In a perfect world, colors are always provided in proper RBGA format.
				if ( count( $check_negative ) == 4 ) {

					$setup['negative'] = str_replace( 'rgba(', 'rgb(', $setup['negative'] );
					$setup['negative'] = str_replace( 'rgba(', 'rgb(', $setup['negative'] );
					$setup['negative'] = str_replace( ',' . $check_negative[3] . ')', ')', $setup['negative'] );

				}

				$color_sets[ $row ]['positive'] = $setup['positive'];
				$color_sets[ $row ]['negative'] = $setup['negative'];

			}

		}

		return $color_sets;

	}
endif;

/**
 * Get Type Color
 * @since 1.6
 * @version 1.1
 */
if ( ! function_exists( 'bonipress_get_type_color' ) ) :
	function bonipress_get_type_color( $point_type = NULL ) {

		$color_set   = bonipress_get_color_sets();
		$point_types = bonipress_get_types();

		$colors      = array();
		$row         = 0;

		$saved       = (array) bonipress_get_option( 'bonipress-point-colors', array() );

		foreach ( $point_types as $type_id => $label ) {

			$value              = array( 'positive' => '', 'negative' => '' );

			if ( ! empty( $saved ) && array_key_exists( $type_id, $saved ) && is_array( $saved[ $type_id ] ) )
				$value = $saved[ $type_id ];

			elseif ( array_key_exists( $row, $color_set ) )
				$value = $color_set[ $row ];

			foreach ( $value as $state => $color_value ) {
				if ( $color_value == '' && array_key_exists( $row, $color_set ) && array_key_exists( $state, $color_set[ $row ] ) )
					$value[ $state ] = $color_set[ $row ][ $state ];
			}

			$colors[ $type_id ] = $value;
			$row ++;

		}

		$result      = $colors;
		if ( $point_type !== NULL && array_key_exists( $point_type, $colors ) )
			$result = $colors[ $point_type ];

		return apply_filters( 'bonipress_point_type_colors', $result, $color_set, $point_type, $colors );

	}
endif;

/**
 * Create Chart
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_create_chart' ) ) :
	function bonipress_create_chart( $args = array() ) {

		global $bonipress_chart;

		if ( isset( $bonipress_chart )
			&& ( $bonipress_chart instanceof boniPRESS_Chart )
			&& ( $bonipress_chart->is_chart( $args ) )
		) {

			return $bonipress_chart;

		}

		$bonipress_chart = new boniPRESS_Chart( $args );

		do_action( 'bonipress_create_chart', $args );

		return $bonipress_chart;

	}
endif;

/**
 * Get Stats Days
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_stat_dates' ) ) :
	function bonipress_get_stat_dates( $instance = 'x_dates', $value = 0 ) {

		$now     = current_time( 'timestamp' );
		$results = array();

		switch ( $instance ) {

			case 'days' :

				$from  = $value - 1;
				$start = date( 'U', strtotime( '-' . $from . ' days midnight', $now ) );

				for ( $i = 0 ; $i < $value ; $i ++ ) {

					if ( $i == 0 )
						$new_start = $start;
					else
						$new_start = $start + ( DAY_IN_SECONDS * $i );

					$results[] = array(
						'label' => date_i18n( 'Y-m-d', $new_start ),
						'from'  => $new_start,
						'until' => ( $new_start + DAY_IN_SECONDS )
					);

				}

			break;

			case 'weeks' :

				$from  = $value - 1;
				$start = date( 'U', strtotime( '-' . $from . ' weeks midnight', $now ) );

				for ( $i = 0 ; $i < $value ; $i ++ ) {

					if ( $i == 0 )
						$new_start = $start;
					else
						$new_start = $start + ( WEEK_IN_SECONDS * $i );

					$results[] = array(
						'label' => sprintf( __( 'Week %d', 'bonipress' ), date_i18n( 'W', $new_start ) ),
						'from'  => $new_start,
						'until' => ( $new_start + WEEK_IN_SECONDS )
					);

				}

			break;

			case 'months' :

				$from  = $value - 1;
				$start = date( 'U', strtotime( '-' . $from . ' months midnight', $now ) );

				for ( $i = 0 ; $i < $value ; $i ++ ) {

					if ( $i == 0 )
						$new_start = $start;
					else
						$new_start = $start + ( MONTH_IN_SECONDS * $i );

					$results[] = array(
						'label' => date_i18n( 'F', $new_start ),
						'from'  => $new_start,
						'until' => ( $new_start + MONTH_IN_SECONDS )
					);

				}

			break;

			case 'years' :

				$from  = $value - 1;
				$start = date( 'U', strtotime( '-' . $from . ' years midnight', $now ) );

				for ( $i = 0 ; $i < $value ; $i ++ ) {

					if ( $i == 0 )
						$new_start = $start;
					else
						$new_start = $start + ( YEAR_IN_SECONDS * $i );

					$results[] = array(
						'label' => date_i18n( 'Y', $new_start ),
						'from'  => $new_start,
						'until' => ( $new_start + YEAR_IN_SECONDS )
					);

				}

			break;

			case 'today_this' :

				$start = date( 'U', strtotime( 'today midnight', $now ) );
				$results[] = array(
					'key'   => 'today',
					'from'  => $start,
					'until' => $now
				);

				$this_week = mktime( 0, 0, 0, date( "n", $now ), date( "j", $now ) - date( "N", $now ) + 1 );
				$results[] = array(
					'key'   => 'thisweek',
					'from'  => $this_week,
					'until' => $now
				);

				$this_month = mktime( 0, 0, 0, date( "n", $now ), 1, date( 'Y', $now ) );
				$results[] = array(
					'key'   => 'thismonth',
					'from'  => $this_month,
					'until' => $now
				);

				$this_year = mktime( 0, 0, 0, 1, 1, date( 'Y', $now ) );
				$results[] = array(
					'key'   => 'thisyear',
					'from'  => $this_year,
					'until' => $now
				);

			break;

		}
		return apply_filters( 'bonipress_get_stat_dates', $results, $instance, $value );

	}
endif;

/**
 * RGB to HEX
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_rgb_to_hex' ) ) :
	function bonipress_rgb_to_hex( $rgb = '' ) {

		if ( ! is_array( $rgb ) ) {

			$rgb = str_replace( array( ' ', 'rgb(', 'rgba(', ')' ), '', $rgb );
			$rgb = explode( ',', $rgb );

		}

		$hex  = "#";
		$hex .= str_pad( dechex( $rgb[0] ), 2, "0", STR_PAD_LEFT );
		$hex .= str_pad( dechex( $rgb[1] ), 2, "0", STR_PAD_LEFT );
		$hex .= str_pad( dechex( $rgb[2] ), 2, "0", STR_PAD_LEFT );

		return $hex;
	}
endif;

/**
 * HEX to RGB
 * @since 1.6
 * @version 1.1
 */
if ( ! function_exists( 'bonipress_hex_to_rgb' ) ) :
	function bonipress_hex_to_rgb( $hex = '', $rgba = true, $opacity = 1 ) {

		$hex = str_replace( '#', '', $hex );

		if ( strlen( $hex ) == 3 ) {

			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );

		} else {

			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );

		}

		$rgb = array( $r, $g, $b );

		if ( $rgba )
			$rgb = 'rgba(' . implode( ',', $rgb ) . ',' . $opacity . ')';

		else $rgb = 'rgb(' . implode( ',', $rgb ) . ')';

		return $rgb;

}
endif;

/**
 * Inverse HEX colors
 * @since 1.6
 * @version 1.1
 */
if ( ! function_exists( 'bonipress_inverse_hex_color' ) ) :
	function bonipress_inverse_hex_color( $color = '' ) {

		$rgb   = '';
		$color = str_replace( '#', '', $color );

		if ( strlen( $color ) != 6 ) {

			if ( strlen( $color ) == 3 )
				$color = $color . $color;

			else return '#000000';

		}


		for ( $x = 0 ; $x < 3 ; $x++ ) {

			$c    = 255 - hexdec( substr( $color, ( 2 * $x ), 2 ) );
			$c    = ( $c < 0 ) ? 0 : dechex( $c );
			$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;

		}

		return '#' . $rgb;

	}
endif;

/**
 * Inverse RGB color
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_inverse_rgb_color' ) ) :
	function bonipress_inverse_rgb_color( $color = '' ) {

		$color    = bonipress_rgb_to_hex( $color );
		$inversed = bonipress_inverse_hex_color( $color );
		$inversed = bonipress_hex_to_rgb( $inversed );

		return $inversed;

	}
endif;

/**
 * Get Stats Cache Times
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_stats_cache_times' ) ) :
	function bonipress_get_stats_cache_times() {

		$options = array(
			'off'       => __( 'Disabled', 'bonipress' ),
			'hourly'    => __( 'Clear data once an hour', 'bonipress' ),
			'sixhours'  => __( 'Clear data every six hours', 'bonipress' ),
			'twiceaday' => __( 'Clear data twice a day', 'bonipress' ),
			'daily'     => __( 'Clear data once a day', 'bonipress' )
		);

		return apply_filters( 'bonipress_stats_cache_times', $options );

	}
endif;

/**
 * Maybe Clear Stats Data
 * Checks to see if the statistics data should be cleared based on our settings.
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_maybe_clear_stats_data' ) ) :
	function bonipress_maybe_clear_stats_data() {

		$settings   = bonipress_get_addon_settings( 'stats' );

		$now        = current_time( 'timestamp' );
		$last_clear = bonipress_get_option( BONIPRESS_SLUG . '-last-clear-stats', 0 );

		$clear_data = true;
		if ( $settings['caching'] !== 'off' ) {

			if ( $last_clear != $now ) {

				if ( $settings['caching'] == 'hourly' )
					$now -= HOUR_IN_SECONDS;

				elseif ( $settings['caching'] == 'sixhours' )
					$now -= ( HOUR_IN_SECONDS * 6 );

				elseif ( $settings['caching'] == 'twiceaday' )
					$now -= ( HOUR_IN_SECONDS * 12 );

				elseif ( $settings['caching'] == 'daily' )
					$now -= DAY_IN_SECONDS;

				if ( $last_clear > $now )
					$clear_data = false;

			}

		}

		return apply_filters( 'bonipress_maybe_clear_stats_data', $clear_data, $last_clear, $settings );

	}
endif;

/**
 * Maybe Clear Users Stats Data
 * Checks to see if the statistics data should be cleared based on our settings for a user.
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_maybe_clear_user_stats_data' ) ) :
	function bonipress_maybe_clear_user_stats_data( $user_id = false ) {

		$settings   = bonipress_get_addon_settings( 'stats' );

		$now        = current_time( 'timestamp' );
		$last_clear = bonipress_get_user_meta( $user_id, 'bonipress-last-clear-stats', '', true );
		if ( $last_clear == '' ) $last_clear = 0;

		$clear_data = true;
		if ( $settings['caching'] !== 'off' ) {

			if ( $last_clear != $now ) {

				if ( $settings['caching'] == 'hourly' )
					$now -= HOUR_IN_SECONDS;

				elseif ( $settings['caching'] == 'sixhours' )
					$now -= ( HOUR_IN_SECONDS * 6 );

				elseif ( $settings['caching'] == 'twiceaday' )
					$now -= ( HOUR_IN_SECONDS * 12 );

				elseif ( $settings['caching'] == 'daily' )
					$now -= DAY_IN_SECONDS;

				if ( $last_clear > $now )
					$clear_data = false;

			}

		}

		return apply_filters( 'bonipress_maybe_clear_user_stats_data', $clear_data, $last_clear, $settings );

	}
endif;

/**
 * Delete Stats Data
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_delete_stats_data' ) ) :
	function bonipress_delete_stats_data( $force = false ) {

		if ( ! bonipress_maybe_clear_stats_data() && ! $force ) return;

		global $wpdb;

		$settings  = bonipress_get_addon_settings( 'stats' );
		$table     = bonipress_get_db_column( 'options' );
		$data_keys = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$table} WHERE option_name LIKE %s;", BONIPRESS_SLUG . '-stats-%' ) );

		// Most of the data is stored in the options table
		if ( ! empty( $data_keys ) ) {

			foreach ( $data_keys as $option_name )
				bonipress_delete_option( $option_name );

		}

		if ( $settings['caching'] !== 'off' )
			bonipress_update_option( BONIPRESS_SLUG . '-last-clear-stats', current_time( 'timestamp' ) );

		do_action( 'bonipress_delete_stats_data', $data_keys, $force );

	}
endif;

/**
 * Delete User Stats Data
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_delete_user_stats_data' ) ) :
	function bonipress_delete_user_stats_data( $user_id = false, $force = false ) {

		if ( ! bonipress_maybe_clear_user_stats_data( $user_id ) && ! $force ) return;

		global $wpdb;

		$settings = bonipress_get_addon_settings( 'stats' );

		// If we have a user ID, we need to delete their data which is stored in the user meta table
		// This is so we do not flood the options table with stats for each user
		if ( $user_id !== false ) {

			foreach ( bonipress_get_types() as $type_id => $label )
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s;", $user_id, $type_id . '_stats%' ) );

			if ( $settings['caching'] !== 'off' )
				bonipress_update_user_meta( $user_id, 'bonipress-last-clear-stats', '', current_time( 'timestamp' ) );

		}

		do_action( 'bonipress_delete_user_stats_data', $user_id, $force );

	}
endif;

/**
 * Data: Balance Circulation
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_circulation_data' ) ) :
	function bonipress_get_circulation_data() {

		$cache = bonipress_get_option( BONIPRESS_SLUG . '-stats-circulation', false );

		if ( $cache === false ) {

			global $wpdb;

			$meta_keys = array();
			foreach ( bonipress_get_types() as $type_id => $label )
				$meta_keys[] = bonipress_get_meta_key( $type_id );

			$data = $wpdb->get_results( $wpdb->prepare( "SELECT SUM( meta_value ) AS value, meta_key AS label, 'point' AS type FROM {$wpdb->usermeta} WHERE meta_key IN ( %s" . str_repeat( ', %s', ( count( $meta_keys ) - 1 ) ) . ") GROUP BY meta_key ORDER BY value DESC;", $meta_keys ) );

			$cache = array( $data );

			bonipress_update_option( BONIPRESS_SLUG . '-stats-circulation', $cache );

		}

		return $cache;

	}
endif;

/**
 * Data: Gains vs. Losses
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_gains_vs_losses_data' ) ) :
	function bonipress_get_gains_vs_losses_data( $point_type = '' ) {

		if ( ! BONIPRESS_ENABLE_LOGGING ) return array();

		$cache = bonipress_get_option( BONIPRESS_SLUG . '-stats-gains-vs-losses' . $point_type, false );

		if ( $cache === false ) {

			$data         = array();

			global $wpdb, $bonipress_log_table;

			$where        = '';
			$point_colors = bonipress_get_type_color();
			$colors       = $point_colors[ BONIPRESS_DEFAULT_TYPE_KEY ];

			if ( $point_type != '' && bonipress_point_type_exists( $point_type ) ) {

				$type_object = new boniPRESS_Point_Type( $point_type );
				$color       = $point_colors[ $point_type ];
				$where       = $wpdb->prepare( "WHERE ctype = %s", $point_type );

			}
			else {

				$type_object = new boniPRESS_Point_Type( BONIPRESS_DEFAULT_TYPE_KEY );

			}

			$query        = $wpdb->get_row( "SELECT SUM( CASE WHEN creds > 0 THEN creds END) as gains, SUM( CASE WHEN creds < 0 THEN creds END) as losses FROM {$bonipress_log_table} {$where};" );

			$row          = new StdClass();
			$row->value   = $type_object->number( ( isset( $query->gains ) && $query->gains !== NULL ) ? $query->gains : 0 );
			$row->label   = __( 'Gains', 'bonipress' );
			$row->type    = 'comp';
			$row->color   = $color['positive'];

			$data[]       = $row;

			$row          = new StdClass();
			$row->value   = $type_object->number( ( isset( $query->losses ) && $query->losses !== NULL ) ? abs( $query->losses ) : 0 );
			$row->label   = __( 'Losses', 'bonipress' );
			$row->type    = 'comp';
			$row->color   = $color['negative'];

			$data[]       = $row;

			$cache        = array( $data );

			bonipress_update_option( BONIPRESS_SLUG . '-stats-gains-vs-losses', $cache );

		}

		return $cache;

	}
endif;

/**
 * Data: Top Balances
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_top_balances_data' ) ) :
	function bonipress_get_top_balances_data( $point_type = BONIPRESS_DEFAULT_TYPE_KEY, $number = 10, $order = 'DESC' ) {

		$stats_key = BONIPRESS_SLUG . '-stats-' . md5( 'balances' . $point_type . $number . $order );
		$cache     = bonipress_get_option( $stats_key, false );

		if ( $cache === false ) {

			global $wpdb, $bonipress_log_table;

			$point_colors = bonipress_get_type_color();
			$colors       = $point_colors[ $point_type ];
			$type_object  = new boniPRESS_Point_Type( $point_type );

			$limit        = ( absint( $number ) > 0 ) ? 'LIMIT ' . absint( $number ) : '';
			$order        = ( in_array( $order, array( 'ASC', 'DESC' ) ) ) ? $order : 'DESC';

			$data         = $wpdb->get_results( $wpdb->prepare( "SELECT meta_value AS value, user_id AS label, 'user' AS type FROM {$wpdb->usermeta} WHERE meta_key = %s ORDER BY meta_value+0 {$order} {$limit}", bonipress_get_meta_key( $point_type ) ) );

			$cache        = array( $data );

			bonipress_update_option( $stats_key, $cache );

		}

		return $cache;

	}
endif;

/**
 * Data: Top Instances
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_top_instances_data' ) ) :
	function bonipress_get_top_instances_data( $point_type = BONIPRESS_DEFAULT_TYPE_KEY, $number = 10, $order = 'DESC' ) {

		$stats_key = BONIPRESS_SLUG . '-stats-' . md5( 'instances' . $point_type . $number . $order );
		$cache     = bonipress_get_option( $stats_key, false );

		if ( $cache === false ) {

			global $wpdb, $bonipress_log_table;

			$point_colors = bonipress_get_type_color();
			$type_object  = new boniPRESS_Point_Type( $point_type );

			$limit        = ( absint( $number ) > 0 ) ? 'LIMIT ' . absint( $number ) : '';
			$order        = ( in_array( $order, array( 'ASC', 'DESC' ) ) ) ? $order : 'DESC';

			$data         = $wpdb->get_results( $wpdb->prepare( "SELECT SUM( creds ) AS value, ref AS label, 'reference' AS type FROM {$bonipress_log_table} WHERE ctype = %s GROUP BY ref ORDER BY value {$order} {$limit}", $point_type ) );

			$cache        = array( $data );

			bonipress_update_option( $stats_key, $cache );

		}

		return $cache;

	}
endif;

/**
 * Data: Get Verlauf
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_history_data' ) ) :
	function bonipress_get_history_data( $point_type = BONIPRESS_DEFAULT_TYPE_KEY, $period = 'days', $number = 10, $order = 'DESC' ) {

		$stats_key = BONIPRESS_SLUG . '-stats-' . md5( $point_type . $period . $number . $order );
		$cache     = bonipress_get_option( $stats_key, false );

		if ( $cache === false ) {

			global $wpdb, $bonipress_log_table;

			$point_colors = bonipress_get_type_color();
			$colors       = $point_colors[ $point_type ];
			$type_object  = new boniPRESS_Point_Type( $point_type );

			$data         = array();
			$periods      = bonipress_get_stat_dates( $period, $number );

			if ( ! empty( $periods ) ) {

				$datasets = array();

				$select   = '';
				$selects  = array();

				foreach ( $periods as $row => $setup )
					$selects[] = $wpdb->prepare( 'AVG( CASE WHEN time BETWEEN %d AND %d THEN creds END) AS period%d', $setup['from'], $setup['until'], $row );

				$select   = implode( ', ' . "\n", $selects );

				$wheres[] = $wpdb->prepare( 'ctype = %s', $point_type );

				$where    = implode( ' AND ', $wheres );
				$order    = ( in_array( $order, array( 'ASC', 'DESC' ) ) ) ? $order : 'DESC';
				$limit    = ( absint( $number ) > 0 ) ? 'LIMIT ' . absint( $number ) : '';

				$query    = $wpdb->get_row( "SELECT {$select} FROM {$bonipress_log_table} WHERE {$where} {$limit};" );

				if ( $query !== NULL ) {

					foreach ( $periods as $row => $setup ) {

						$value_key    = 'period' . $row;
						$value        = ( isset( $query->$value_key ) && $query->$value_key !== NULL ) ? $query->$value_key : 0;
						$value        = $type_object->number( $value );

						$entry        = new StdClass();
						$entry->type  = 'date';
						$entry->value = $value;
						$entry->label = $setup['label'];
						$entry->color = ( $value >= 0 ) ? $colors['positive'] : $colors['negative'];

						$datasets[]   = $entry;

					}

				}

				$data[] = $datasets;

			}

			$cache = $data;

			bonipress_update_option( $stats_key, $cache );

		}

		return $cache;

	}
endif;

/**
 * Data: Get Users Verlauf
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_users_history_data' ) ) :
	function bonipress_get_users_history_data( $user_id = 0, $point_type = BONIPRESS_DEFAULT_TYPE_KEY, $period = 'days', $number = 10, $order = 'DESC' ) {

		if ( absint( $user_id ) === 0 ) return array();

		$stats_key = md5( $user_id . $point_type . $period . $number . $order );
		$cache     = bonipress_get_user_meta( $user_id, $point_type . '_stats', $stats_key );

		if ( empty($cache) ) {

			global $wpdb, $bonipress_log_table;

			$point_colors = bonipress_get_type_color();
			$colors       = $point_colors[ $point_type ];
			$type_object  = new boniPRESS_Point_Type( $point_type );

			$data         = array();
			$periods      = bonipress_get_stat_dates( $period, $number );

			if ( ! empty( $periods ) ) {

				$datasets = array();

				$select   = '';
				$selects  = array();

				foreach ( $periods as $row => $setup )
					$selects[] = $wpdb->prepare( 'SUM( CASE WHEN time BETWEEN %d AND %d THEN creds END) AS period%d', $setup['from'], $setup['until'], $row );

				$select   = implode( ', ' . "\n", $selects );

				$wheres[] = $wpdb->prepare( 'ctype = %s', $point_type );
				$wheres[] = $wpdb->prepare( 'user_id = %d', $user_id );

				$where    = implode( ' AND ', $wheres );
				$order    = ( in_array( $order, array( 'ASC', 'DESC' ) ) ) ? $order : 'DESC';
				$limit    = ( absint( $number ) > 0 ) ? 'LIMIT ' . absint( $number ) : '';

				$query    = $wpdb->get_row( "SELECT {$select} FROM {$bonipress_log_table} WHERE {$where} {$limit};" );

				if ( $query !== NULL ) {

					foreach ( $periods as $row => $setup ) {

						$value_key    = 'period' . $row;
						$value        = ( isset( $query->$value_key ) && $query->$value_key !== NULL ) ? $query->$value_key : 0;
						$value        = $type_object->number( $value );

						$entry        = new StdClass();
						$entry->type  = 'date';
						$entry->value = $value;
						$entry->label = $setup['label'];
						$entry->color = ( $value >= 0 ) ? $colors['positive'] : $colors['negative'];

						$datasets[]   = $entry;

					}

				}

				$data[] = $datasets;

			}

			$cache = $data;

			bonipress_update_user_meta( $user_id, $stats_key, $point_type . '_stats', $stats_key, $cache );

		}

		return $cache;

	}
endif;

/**
 * Data: Get Reference Verlauf
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'bonipress_get_ref_history_data' ) ) :
	function bonipress_get_ref_history_data( $reference = '', $point_type = BONIPRESS_DEFAULT_TYPE_KEY, $period = 'days', $number = 10, $order = 'DESC' ) {

		$stats_key = BONIPRESS_SLUG . '-stats-' . md5( $reference . $point_type . $period . $number . $order );
		$cache     = bonipress_get_option( $stats_key, false );

		if ( $cache === false ) {

			global $wpdb, $bonipress_log_table;

			$point_colors = bonipress_get_type_color();
			$colors       = $point_colors[ $point_type ];
			$type_object  = new boniPRESS_Point_Type( $point_type );

			$data         = array();
			$periods      = bonipress_get_stat_dates( $period, $number );

			if ( ! empty( $periods ) ) {

				$datasets = array();

				$select   = '';
				$selects  = array();

				foreach ( $periods as $row => $setup )
					$selects[] = $wpdb->prepare( 'AVG( CASE WHEN time BETWEEN %d AND %d THEN creds END) AS period%d', $setup['from'], $setup['until'], $row );

				$select   = implode( ', ' . "\n", $selects );

				$wheres[] = $wpdb->prepare( 'ctype = %s', $point_type );
				$wheres[] = $wpdb->prepare( 'ref = %s', $reference );

				$where    = implode( ' AND ', $wheres );
				$order    = ( in_array( $order, array( 'ASC', 'DESC' ) ) ) ? $order : 'DESC';
				$limit    = ( absint( $number ) > 0 ) ? 'LIMIT ' . absint( $number ) : '';

				$query    = $wpdb->get_row( "SELECT {$select} FROM {$bonipress_log_table} WHERE {$where} {$limit};" );

				if ( $query !== NULL ) {

					foreach ( $periods as $row => $setup ) {

						$value_key    = 'period' . $row;
						$value        = ( isset( $query->$value_key ) && $query->$value_key !== NULL ) ? $query->$value_key : 0;
						$value        = $type_object->number( $value );

						$entry        = new StdClass();
						$entry->type  = 'date';
						$entry->value = $value;
						$entry->label = $setup['label'];
						$entry->color = ( $value >= 0 ) ? $colors['positive'] : $colors['negative'];

						$datasets[]   = $entry;

					}

				}

				$data[] = $datasets;

			}

			$cache = $data;

			bonipress_update_option( $stats_key, $cache );

		}

		return $cache;

	}
endif;
