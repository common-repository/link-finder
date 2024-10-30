<?php

defined( 'ABSPATH' ) || exit;


if ( ! is_user_logged_in() ) {
  die( 401 );
}

if ( ! wp_doing_ajax() ) {
  die( 403 );
}

if ( empty( $_POST['link'] ) ) {
  die( 400 );
}

$url = esc_url( wp_unslash( $_POST['link'] ) );

$data = array();

$follow = ! empty( $_POST['follow'] ) && ( boolval( $_POST['follow'] ) || $_POST['follow'] === 'true' );

/**
 * Re-define nocache headers.
 */
add_filter(
  'nocache_headers',
  /**
   * Filters the cache-controlling headers.
   *
   * @param array $headers {
   *   Header names and field values.
   *   @type string $Expires       Expires header.
   *   @type string $Cache-Control Cache-Control header.
   * }
   *
   * @link https://developer.wordpress.org/reference/functions/wp_get_nocache_headers/
   */
  function( $headers )
  {
    return wp_parse_args(
      array(
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        'Pragma'        => 'no-cache',
        // 'Expires'       => gmdate( 'D, d M Y H:i:s \G\M\T', time() ),
      ),
      $headers
    );
  }
);

/**
 * Get the CURLINFO_EFFECTIVE_URL equivalent from a WordPress request.
 */
add_action(
  'requests-requests.after_request',
  /**
   * Transforms a native Request hook to a WordPress action.
   *
   * This action maps Requests internal hook to a native WordPress action.
   *
   * @see https://github.com/rmccue/Requests/blob/master/docs/hooks.md
   *
   * @since 4.7.0
   *
   * @param array  $parameters Parameters from Requests internal hook.
   * @param array  $request    Request data in WP_Http format.
   * @param string $url        URL to request.
   *
   * @link https://developer.wordpress.org/reference/hooks/requests-hook/
   */
  function ( $parameters/*, $request, $url*/ ) use ( &$data )
  {
    $data['effective_url'] = $parameters->url;
  }
);

$response = wp_remote_get(
  $url,
  array(
    'redirection' => $follow ? 20 : 0,
    'blocking'    => true,
    'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:72.0) Gecko/20100101 Firefox/72.0',
    'headers'     => wp_parse_args( array( 'Referer' => home_url() ), wp_get_nocache_headers() ),
  )
);

if ( '' === $response_code = wp_remote_retrieve_response_code( $response ) ) {
  if ( $follow ) {
    wp_send_json_error( array( 'error' => 'An error occured.' ), 400 );
    exit;
  }
  wp_die(
    '',
    '',
    array(
      'response' => 400,
      'exit'     => true,
    )
  );
}

$data['response_code'] = $response_code;
if ( $follow ) {
  wp_send_json_success( $data, 200 );
  exit;
}
wp_die(
  '',
  '',
  array(
    'response' => intval( $response_code ),
    'exit'     => true,
  )
);
