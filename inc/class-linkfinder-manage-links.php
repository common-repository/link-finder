<?php

defined( 'ABSPATH' ) || exit;


/**
 * Class for updating hyperlinks in posts.
 *
 * @since 2020.06.11
 */
class Linkfinder_Manage_Links
{
  /**
   * Retrieve hyperlinks throughout the website.
   *
   * @since 2020.06.11
   *
   * @global wpdb $wpdb
   *
   * @return array An array of post ID's with hyperlink information, see `let linkinfo`.
   */
  public static function retrieve_hyperlinks()
  {
    global $wpdb;

    $results = $wpdb->get_results(
      "SELECT * FROM {$wpdb->posts}
      WHERE
        post_status <> 'private' AND
        post_status <> 'trash' AND
        post_type <> 'revision'"
      // WHERE post_content REGEXP 'href=|src=';" // may cause server overload on large websites, handle regex in php ..
      // string $output = OBJECT
    );

    /**
     * Build array with <a> tag strings.
     */
    $postid_hyperlinks = array();

    foreach ( $results as $result ) {
      // let linkinfo =
      $postid_hyperlinks[ $result->ID ] = array(
        'post_title'  => $result->post_title,
        'post_name'   => $result->post_name,
        'post_type'   => $result->post_type,
        'post_status' => $result->post_status,
        // 'hyperlinks' => [
        // [0] => [$link_elem, ...],
        // [1] => [$link_before, ...],
        // [2] => [$element, ...],
        // [3] => [$link_attr, ...],
        // [4] => [$link_value, ...],
        // [5] => [$link_after, ...],
        // ],
      );

      preg_match_all( '/(<([^<>]+?)\s[^<>]*?(href|src)=[\'"])([^\'"]*?)([\'"][^<>]*?>)/ims', $result->post_content, $link_matches );

      $postid_hyperlinks[ $result->ID ]['hyperlinks'] = $link_matches;
    }

    return $postid_hyperlinks;
  }

  /**
   * Update hyperlinks from a POST request.
   *
   * The `$_POST` parameters must contain "oldlink_elem-{post_id}-{link_index}" and "newlink-{post_id}-{link_index}".
   *
   * @since 2020.06.11
   *
   * @return bool True on success, false if an error occured.
   */
  public static function update_from_post_request()
  {
    if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
      return false;
    }

    $newlinks = array();

    /**
     * Fetch hyperlinks to replace as defined by the user.
     */
    foreach ( $_POST as $parameter => $value ) {
      /**
       * Sanitize $parameter and $value.
       */
      $parameter = sanitize_key( $parameter );
      $new_link  = esc_url_raw( linkfinder_trim( wp_unslash( $value ) ) );

      /**
       * Verify whether the parameter is relevant for further processing.
       */
      if ( preg_match( '/^newlink-(\d+)-(\d+)$/i', $parameter, $matches_post ) && ! empty( $new_link )/* && false !== wp_parse_url( $new_link )*/ ) {
        /**
         * For some strange reasons, it could occur that an expected POST parameter doesn't exist,
         * then just skip to the next iteration without pushing anyting to the array.
         */
        try {
          /**
           * Sanitize $oldlink_elem.
           */
          $oldlink_elem = wp_kses_post( linkfinder_trim( wp_unslash( $_POST[ 'oldlink_elem-' . $matches_post[1] . '-' . $matches_post[2] ] ) ) );
        } catch ( Exception $ex ) {
          continue;
        }

        /**
         * Validate and create the replacing html element: $newlink_elem.
         *
         * This is done using preg_match() with segment patterns according to the earlier preg_match_all().
         * Also, only the hyperlink segment of $oldlink_elem and $newlink_elem is allowed to be different.
         *
         * If for some reason an invalid html element passes the checks, it should not replace anything, as there should be no matches.
         * If it does match something in the replace query, it already was incorrectly stored in the database in the first place.
         * This module is not intended to repair already existing errors. Always only the hyperlink attribute values will be replaced.
         */
        preg_match( '/(<([^<>]+?)\s[^<>]*?(href|src)=[\'"])([^\'"]*?)([\'"][^<>]*?>)/i', $oldlink_elem, $matches_oldlink );
        if (
          empty( $matches_oldlink[0] ) ||
          empty( $matches_oldlink[1] ) || // link_before
          empty( $matches_oldlink[5] )    // link_after
        ) {
          continue;
        }
        $newlink_elem = wp_kses_post( $matches_oldlink[1] . $new_link . $matches_oldlink[5] );

        $newlinks[] = array(
          'postid'       => $matches_post[1],
          'oldlink_elem' => $oldlink_elem,
          'newlink_elem' => $newlink_elem,
        );
      }
    }

    return self::update_database( $newlinks );
  }

  /**
   * Update internal hyperlinks as absolute url (self-pings allowed) or as relative url (self-pings avoided).
   *
   * @since 2020.06.11
   *
   * @param bool $allow Wheter to allow or avoid internal hyperlinks to trigger self-pings.
   * @return bool True on success, false if an error occured.
   */
  public static function allow_selfpings( bool $allow = true )
  {
    $newlinks = array();

    $home_url    = rtrim( home_url(), '/' );
    $home_domain = preg_replace( '/^(?:https?:\/\/)?(?:www\.)?/i', '', $home_url );

    foreach ( self::retrieve_hyperlinks() as $postid => $linkinfo ) {
      for ( $index = 0, $length = count( $linkinfo['hyperlinks'][0] ); $index < $length; $index++ ) {
        /**
         * Sanitize $oldlink_elem and the hyperlink.
         */
        $oldlink_elem = wp_kses_post( linkfinder_trim( $linkinfo['hyperlinks'][0][ $index ] ) );
        $new_link     = esc_url_raw( linkfinder_trim( $linkinfo['hyperlinks'][4][ $index ] ) );

        /**
         * Rewrite the hyperlinks
         */
        $new_link = preg_replace(
          '/^(\/|\#|\?)|^(?:https?:\/\/)?(?:www\.)?(?:' . preg_quote( $home_domain, '/' ) . ')(\/|\#|\?)?/i',
          $allow ? $home_url . '$1$2' : '$1$2',
          $new_link,
          -1,
          $count
        );
        if (
          $allow &&
          preg_match( '/^[a-z0-9-]+?\.php/i', $new_link )
        ) {
          $count++;
          $new_link = $home_url . '/' . $linkinfo['post_name'] . '/' . $new_link;
        }

        /**
         * If nothing was replaced, link is not internal, skip to the next iteration without pushing anyting to the array.
         */
        if (
          $count === 0
        ) {
          continue;
        }

        /**
         * Create the replacing html element: $newlink_elem.
         */
        $newlink_elem = wp_kses_post( $linkinfo['hyperlinks'][1][ $index ] . $new_link . $linkinfo['hyperlinks'][5][ $index ] );

        $newlinks[] = array(
          'postid'       => $postid,
          'oldlink_elem' => $oldlink_elem,
          'newlink_elem' => $newlink_elem,
        );
      }
    }

    return self::update_database( $newlinks );
  }

  /**
   * Replace given links in the database.
   *
   * Made private to restrict free database write access.
   *
   * @since 2020.06.11
   *
   * @global wpdb $wpdb
   *
   * @param array $newlinks {
   *  @type array $newlink {
   *    @type string  $oldlink_elem
   *    @type string  $newlink_elem
   *    @type int     $postid
   *  }, ...
   * }
   * @return bool True on success, false if an error occured.
   */
  private static function update_database( $newlinks )
  {
    global $wpdb;

    $success = true;

    foreach ( $newlinks as $newlink ) {
      $results = $wpdb->query(
        $wpdb->prepare(
          "UPDATE {$wpdb->posts}
          SET post_content = REPLACE(post_content, %s, %s)
          WHERE id = %d;",
          array(
            $newlink['oldlink_elem'],
            $newlink['newlink_elem'],
            $newlink['postid'],
          )
        )
      );

      if ( $results === false ) {
        $success = false;
      }
    }

    return $success;
  }
}
