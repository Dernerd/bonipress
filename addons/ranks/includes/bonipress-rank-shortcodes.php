<?php
if ( ! defined( 'boniPRESS_VERSION' ) ) exit;

/**
 * BoniPress Shortcode: bonipress_my_rank
 * Returns a given users rank
 * @see http://codex.bonipress.me/shortcodes/bonipress_my_rank/
 * @since 1.1
 * @version 1.4
 */
if ( ! function_exists( 'bonipress_render_my_rank' ) ) :
	function bonipress_render_my_rank( $atts, $content = '' ) {

		extract( shortcode_atts( array(
			'user_id'    => 'current',
			'ctype'      => BONIPRESS_DEFAULT_TYPE_KEY,
			'show_title' => 1,
			'show_logo'  => 0,
			'logo_size'  => 'post-thumbnail',
			'first'      => 'logo'
		), $atts, BONIPRESS_SLUG . '_my_rank' ) );

		if ( $user_id == '' && ! is_user_logged_in() ) return;

		if ( ! bonipress_point_type_exists( $ctype ) )
			$ctype = BONIPRESS_DEFAULT_TYPE_KEY;

		$show           = array();
		$user_id        = bonipress_get_user_id( $user_id );
		if ( $user_id === false ) return;

		$account_object = bonipress_get_account( $user_id );
		if( empty( $account_object->balance[ $ctype ]->rank ) ) return;

		$rank_object    = $account_object->balance[ $ctype ]->rank;

		if ( $rank_object !== false ) {

			if ( $show_logo == 1 && $rank_object->has_logo )
				$show[] = bonipress_get_rank_logo( $rank_object->post_id, $logo_size );

			if ( $show_title == 1 )
				$show[] = $rank_object->title;
		
			if ( $first != 'logo' )
				$show = array_reverse( $show );

		}

		if ( ! empty( $show ) )
			$content = '<div class="bonipress-my-rank">' . implode( ' ', $show ) . '</div>';

		return apply_filters( 'bonipress_my_rank', $content, $user_id, $rank_object );

	}
endif;

/**
 * BoniPress Shortcode: bonipress_my_ranks
 * Returns the given users ranks.
 * @see http://codex.bonipress.me/shortcodes/bonipress_my_ranks/
 * @since 1.6
 * @version 1.3
 */
if ( ! function_exists( 'bonipress_render_my_ranks' ) ) :
	function bonipress_render_my_ranks( $atts, $content = '' ) {
		
		$ranks = new stdClass();

		extract( shortcode_atts( array(
			'user_id'    => 'current',
			'show_title' => 1,
			'show_logo'  => 0,
			'logo_size'  => 'post-thumbnail',
			'first'      => 'logo'
		), $atts, BONIPRESS_SLUG . '_my_ranks' ) );

		if ( $user_id == '' && ! is_user_logged_in() ) return;

		$user_id        = bonipress_get_user_id( $user_id );
		if ( $user_id == false ) return;

		$account_object = bonipress_get_account( $user_id );
		$show           = array();

		// Get the rank for each type
		foreach ( $account_object->balance as $type_id => $balance ) {

			$row         = array();
			$rank_object = $balance->rank;

			if ( $rank_object !== false ) {

				if ( $show_logo == 1 && $rank_object->has_logo )
					$row[] = bonipress_get_rank_logo( $rank_object->post_id, $logo_size );

				if ( $show_title == 1 )
					$row[] = $rank_object->title;
		
				if ( $first != 'logo' )
					$row = array_reverse( $row );

			}

			if ( ! empty( $row ) )
				$show[] = '<div class="bonipress-my-rank ' . $type_id . '">' . implode( ' ', $row ) . '</div>';

		}

		if ( ! empty( $show ) )
			$content = '<div class="bonipress-all-my-ranks">' . implode( ' ', $show ) . '</div>';

		if( ! empty($rank_object) )
			$ranks = $rank_object;

		return apply_filters( 'bonipress_my_ranks', $content, $user_id, $ranks );

	}
endif;

/**
 * BoniPress Shortcode: bonipress_users_of_rank
 * Returns all users who have the given rank with the option to show the rank logo and optional content.
 * @see http://codex.bonipress.me/shortcodes/bonipress_users_of_rank/
 * @since 1.1
 * @version 1.2
 */
if ( ! function_exists( 'bonipress_render_users_of_rank' ) ) :
	function bonipress_render_users_of_rank( $atts, $row_template = NULL ) {

		extract( shortcode_atts( array(
			'rank_id' => NULL,
			'login'   => '',
			'number'  => 10,
			'wrap'    => 'div',
			'col'     => 1,
			'nothing' => 'No users found with this rank',
			'ctype'   => BONIPRESS_DEFAULT_TYPE_KEY,
			'order'   => 'DESC'
		), $atts, BONIPRESS_SLUG . '_users_of_rank' ) );

		// Rank ID required
		if ( $rank_id === NULL )
			return '<strong>ERROR</strong> Rank ID is required!';

		// User is not logged in
		if ( ! is_user_logged_in() && $login != '' )
			return $bonipress->template_tags_general( $login );

		if ( ! bonipress_point_type_exists( $ctype ) )
			$ctype = BONIPRESS_DEFAULT_TYPE_KEY;

		$bonipress       = bonipress( $ctype );

		$output       = '';

		if ( $row_template === NULL || empty( $row_template ) )
			$row_template = '<p class="user-row">%user_profile_link% with %balance% %_plural%</p>';

		// Let others play
		$row_template = apply_filters( 'bonipress_users_of_rank', $row_template, $atts, $bonipress );

		// Get users of this rank if there are any
		$users        = bonipress_get_users_of_rank( $rank_id, $number, $order, $ctype );
		if ( ! empty( $users ) ) {

			// Add support for table
			if ( $wrap != 'table' && ! empty( $wrap ) )
				$output .= '<' . $wrap . ' class="bonipress-users-of-rank-wrapper">';

			// Loop
			foreach ( $users as $user )
				$output .= $bonipress->template_tags_user( $row_template, false, $user );

			// Add support for table
			if ( $wrap != 'table' && ! empty( $wrap ) )
				$output .= '</' . $wrap . '>' . "\n";

		}

		// No users found
		else {

			// Add support for table
			if ( $wrap == 'table' ) {
				$output .= '<tr><td';
				if ( $col > 1 ) $output .= ' colspan="' . $col . '"';
				$output .= '>' . $nothing . '</td></tr>';
			}

			else {
				if ( empty( $wrap ) ) $wrap = 'p';
				$output .= '<' . $wrap . '>' . $nothing . '</' . $wrap . '>' . "\n";
			}

		}

		return do_shortcode( $output );

	}
endif;

/**
 * BoniPress Shortcode: bonipress_users_of_all_ranks
 * Returns all users fore every registered rank in order.
 * @see http://codex.bonipress.me/shortcodes/bonipress_users_of_all_ranks/
 * @since 1.1
 * @version 1.2.1
 */
if ( ! function_exists( 'bonipress_render_users_of_all_ranks' ) ) :
	function bonipress_render_users_of_all_ranks( $atts, $row_template = NULL ) {

		extract( shortcode_atts( array(
			'login'     => '',
			'number'    => 10,
			'ctype'     => BONIPRESS_DEFAULT_TYPE_KEY,
			'show_logo' => 1,
			'logo_size' => 'post-thumbnail',
			'wrap'      => 'div',
			'nothing'   => 'Keine Benutzer mit diesem Rang gefunden'
		), $atts, BONIPRESS_SLUG . '_users_of_all_ranks' ) );

		// Prep
		$bonipress    = bonipress();

		// User is not logged in
		if ( ! is_user_logged_in() && $login != '' )
			return $bonipress->template_tags_general( $login );

		$output    = '';
		$all_ranks = bonipress_get_ranks( 'publish', '-1', 'DESC', $ctype );

		// If we have ranks
		if ( ! empty( $all_ranks ) ) {

			$output .= '<div class="bonipress-all-ranks-wrapper">' . "\n";

			// Loop though all ranks
			foreach ( $all_ranks as $rank ) {

				// Prep Slug
				$slug    = str_replace( ' ', '-', strtolower( $rank->title ) );

				// Rank wrapper
				$output .= '<div class="bonipress-rank rank-' . $slug . ' rank-' . $rank->post_id . '"><h2>';

				// Insert Logo
				if ( $show_logo )
					$output .= bonipress_get_rank_logo( $rank->post_id, $logo_size );

				// Rank title
				$output .= $rank->title . '</h2>' . "\n";

				$attr    = array(
					'rank_id' => $rank->post_id,
					'number'  => $number,
					'nothing' => $nothing,
					'wrap'    => $wrap,
					'ctype'   => $ctype
				);
				$output .= bonipress_render_users_of_rank( $attr, $row_template );

				$output .= '</div>' . "\n";

			}

			$output .= '</div>';

		}

		return $output;

	}
endif;

/**
 * BoniPress Shortcode: bonipress_list_ranks
 * Returns a list of ranks with minimum and maximum point requirements.
 * @see http://codex.bonipress.me/shortcodes/bonipress_list_ranks/
 * @since 1.1.1
 * @version 1.3
 */
if ( ! function_exists( 'bonipress_render_rank_list' ) ) :
	function bonipress_render_rank_list( $atts, $row_template = NULL ) {

		$atts      = shortcode_atts( array(
			'order' => 'DESC',
			'ctype' => BONIPRESS_DEFAULT_TYPE_KEY,
			'wrap'  => 'div'
		), $atts, BONIPRESS_SLUG . '_list_ranks' );

		extract( $atts );

		$output    = '';
		$all_ranks = bonipress_get_ranks( 'publish', '-1', $order, $ctype );

		if ( ! empty( $all_ranks ) ) {

			if ( $wrap != '' )
				$output .= '<' . $wrap . ' class="bonipress-rank-list">';

			if ( $row_template === NULL || empty( $row_template ) )
				$row_template = '<p>%rank% <span class="min">%min%</span> - <span class="max">%max%</span></p>';

			foreach ( $all_ranks as $rank ) {

				$bonipress  = bonipress( $rank->point_type );
				$row     = apply_filters( 'bonipress_rank_list', $row_template, $atts, $bonipress );

				$row     = str_replace( '%rank%',             $rank->title, $row );
				$row     = str_replace( '%rank_logo%',        bonipress_get_rank_logo( $rank->post_id ), $row );
				$row     = str_replace( '%min%',              $bonipress->format_creds( $rank->minimum ), $row );
				$row     = str_replace( '%max%',              $bonipress->format_creds( $rank->maximum ), $row );
				$row     = str_replace( '%count%',            $rank->count, $row );

				$row     = $bonipress->template_tags_general( $row );

				$output .= $row;

			}

			if ( $wrap != '' )
				$output .= '</' . $wrap . '>';

		}

		return $output;

	}
endif;
