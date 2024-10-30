<?php

defined( 'ABSPATH' ) || exit;


/**
 * Register setting sections and fields.
 *
 * @since 2020.06.11
 *
 * @link https://developer.wordpress.org/plugins/settings/
 */
add_action(
  'admin_init',
  function ()
  {
    /**
     * The main section.
     *
     * @since 2020.06.11
     */
    add_settings_section(
      'linkfinder_section', // $id*
      null, // $title*
      function ()
      {
        ?>
        <p>
          <?php
          _e(
            'Link Finder will parse the links in the content of all your posts and pages.<br>
            Depending on the size of your website and the amount of links, this could take a moment.<br>
            Links to admin-pages are ignored.<br>
            <strong>Please note that sometimes links may appear false-positive, always follow the link manually to confirm before making changes!</strong>',
            'linkfinder'
          );
          ?>
        </p>

        <p>
          <label title="<?php esc_attr_e( 'All links that resulted in a status code of 400~599.', 'linkfinder' ); ?>">
            <input type="checkbox" onclick="linkfinder_row_filter('errors', this.checked)" checked />
          <?php esc_html_e( 'errors', 'linkfinder' ); ?> (<b><span class="linkfinder-error-count">0</span></b>)</label>
          &nbsp;&bull;&nbsp;
          <label title="<?php esc_attr_e( 'All links on published posts that are internal or resulted in a status code of 300~399.', 'linkfinder' ); ?>">
            <input type="checkbox" onclick="linkfinder_row_filter('warnings', this.checked)" />
          <?php esc_html_e( 'warnings', 'linkfinder' ); ?> (<b><span class="linkfinder-warning-count">0</span></b>)</label>
          &nbsp;&bull;&nbsp;
          <label title="<?php esc_attr_e( 'All other links that didn\'t result in a status code of 200~299.', 'linkfinder' ); ?>">
            <input type="checkbox" onclick="linkfinder_row_filter('other', this.checked)" />
          <?php esc_html_e( 'other', 'linkfinder' ); ?> (<b><span class="linkfinder-other-count">0</span></b>)</label>
          &nbsp;&bull;&nbsp;
          <b><span class="linkfinder-total-percentage">0%</span></b> (<span class="linkfinder-total-count">0/0</span>)
        </p>

        <table id="linkfinder-table" class="linkfinder-table linkfinder-hide-warnings linkfinder-hide-other wp-list-table widefat">
          <thead><tr>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Status code', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Post title (edit-link)', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Post type', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Post status', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( '<elem attr=', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Original hyperlink', 'linkfinder' ); ?></th>
            <th></th>
            <th><?php esc_html_e( 'New hyperlink', 'linkfinder' ); ?></th>
          </tr></thead>
          <tbody></tbody>
          <tfoot><tr>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Status code', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Post title (edit-link)', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Post type', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Post status', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( '<elem attr=', 'linkfinder' ); ?></th>
            <th class="linkfinder-sortable"><?php esc_html_e( 'Original hyperlink', 'linkfinder' ); ?></th>
            <th></th>
            <th><?php esc_html_e( 'New hyperlink', 'linkfinder' ); ?></th>
          </tr></tfoot>
        </table>

        <script>
          linkfinder_process_links(
            <?php echo wp_json_encode( Linkfinder_Manage_Links::retrieve_hyperlinks() ); ?>,
            '<?php echo esc_js( home_url() ); ?>',
            '<?php echo esc_js( admin_url() ); ?>',
            '<?php echo esc_js( admin_url( 'admin-ajax.php?action=linkfinder_process_links' ) ); ?>'
          )
        </script>

        <?php
      }, // $callback*
      'linkfinder' // $page*
    );
  }
);


/**
 * AJAX hook to include the WordPress functions in a standalone php file.
 *
 * @since 2020.06.11
 * @since 2021.10.11 Simplified.
 */
add_action(
  'wp_ajax_linkfinder_process_links',
  function ()
  {
    require dirname( __FILE__ ) . '/../inc/ajax-link-validator.php';
    exit;
  }
);


/**
 * Filter the submit button to provide the option for resolving while either allowing or avoiding self-pings.
 *
 * @since 2020.06.11
 */
add_filter(
  'linkfinder_submit_button',
  function ()
  {
    ob_start();
    ?>

    <p class="submit">
      <input type="submit" class="button button-primary" name="submit"
      value="<?php esc_attr_e( 'Apply changes', 'linkfinder' ); ?>" />
      <br><br>
      <?php esc_html_e( 'OR apply changes while ..', 'linkfinder' ); ?>
      <br><br>
      <input type="submit" class="button button-secondary" name="allow_self_pings"
      value=".. <?php esc_attr_e( 'allowing self-pings (default)', 'linkfinder' ); ?>" />
      &nbsp;
      <input type="submit" class="button button-secondary" name="avoid_self_pings"
      value=".. <?php esc_attr_e( 'avoiding self-pings', 'linkfinder' ); ?>" />
      <br><br>
      (<a href="https://wordpress.org/support/article/trackbacks-and-pingbacks/#can-i-stop-self-pings" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'About self-pings', 'linkfinder' ); ?></a>)
      <br><br>
      <strong><?php esc_html_e( 'Changes are irreversable!', 'linkfinder' ); ?></strong>
      <br><br>
      <a href="https://wordpress.org/plugins/link-finder/#reviews" target="_blank" rel="noopener"><?php esc_attr_e( 'Rate this plugin', 'wpessentials' ); ?> &#9733;</a>
    </p>

    <?php
    return ob_get_clean();
  }
);


/**
 * After authenticated page submit.
 *
 * @since 2020.06.11
 */
function linkfinder_after_page_submit_cb()
{
  /**
   * Update given hyperlinks in database.
   */
  $success_POST = Linkfinder_Manage_Links::update_from_post_request();

  /**
   * Check if internal url should be formatted absolute or relative.
   */
  $success_SELFPINGS = true;
  if (
    ! empty( $_POST['allow_self_pings'] ) ||
    ! empty( $_POST['avoid_self_pings'] )
  ) {
    $success_SELFPINGS = Linkfinder_Manage_Links::allow_selfpings( empty( $_POST['avoid_self_pings'] ) );
  }

  /**
   * Set error messages.
   */
  if (
    ! $success_POST ||
    ! $success_SELFPINGS
  ) {
    add_settings_error(
      'linkfinder',
      'linkfinder_errormsg',
      __( 'Something went wrong, not all hyperlinks were updated! Remaining issues will reappear in the list.', 'linkfinder' )
    );
  } else {
    add_settings_error(
      'linkfinder',
      'linkfinder_successmsg',
      __( 'Hyperlinks updated. If there are any remaining issues, they will (re)appear in the list.', 'linkfinder' ),
      'updated'
    );
  }
}
