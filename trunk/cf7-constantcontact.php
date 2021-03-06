<?php
/*
Plugin Name: Contact Form 7 Newsletter
Plugin URI: https://github.com/Asuza/Contact-Form-7-Newsletter
Description: Add the power of Constant Contact to Contact Form 7
Author: Asuza
Author URI: https://github.com/Asuza
Version: 3.0.0
*/

/*  
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Update the version number when the plugin is activated.
 */
register_activation_hook(__FILE__, array('CTCTCF7', 'was_updated'));

class CTCTCF7 {

  /**
   * The current version of the plugin.
   * @var string
   */
  private static $version = '3.0.0';

  function __construct() {

    add_action('admin_init', array('CTCTCF7', 'settings_init'));
    add_action('admin_head', array('CTCTCF7', 'admin_head'));

    add_filter('plugin_action_links', array('CTCTCF7', 'plugins_action_links'), 10, 2 );
    add_action('admin_menu', array('CTCTCF7', 'admin_menu'));
    add_action('wpcf7_after_save', array('CTCTCF7', 'save_form_settings'));
    add_action('wpcf7_admin_notices', array('CTCTCF7', 'add_meta_box' ));
    add_action('wpcf7_admin_after_form', array('CTCTCF7', 'show_ctct_metabox' ));

    // Add icon to activated form list
    add_action('admin_footer', array('CTCTCF7', 'add_enabled_icon'));

    // CF7 Processing
    add_action( 'wpcf7_mail_sent', array('CTCTCF7', 'process_submission' ));

    include_once(trailingslashit(dirname( __FILE__ ))."shortcode.php");
  }

  /**
   * Get the plugin version string
   * @return string Version string
   */
  static function get_version() {
    return self::$version;
  }

  static public function was_updated() {

    $previous_version = get_option('ctct_cf7_version');
    $version = self::get_version();
    $updated = false;

    // Pre-Version 2.0
    if(!$previous_version) { $previous = true; $previous_version = '1.1'; }

    // DB doesn't match
    if($previous_version !== $version) {
      $updated = true;
    }

    if($updated) { // @todo
      add_option('ctct_cf7_updated', $previous_version);
      update_option('ctct_cf7_version', $version);
    } else {
      delete_option('ctct_cf7_updated');
    }
  }

  /**
   * Add an icon to forms with Constant Contact integration enabled on the Contact Form 7 Edit page.
   *
   */
  static function add_enabled_icon() {
    global $pagenow, $plugin_page;

    if(empty($plugin_page) || empty($pagenow)) { return; }

    if($pagenow === 'admin.php' && $plugin_page === 'wpcf7' && !isset($_GET['action']) && class_exists('WPCF7_ContactForm')) {

      // Get the forms
      $forms = WPCF7_ContactForm::find();

      // If there are no forms, return
      if(empty($forms)) { return; }

      // Otherwise, loop through and see which ones have settings
      // for Constant Contact integration.
      $activeforms = array();

      foreach($forms as &$form) {
        $is_active = get_option( 'cf7_ctct_'.$form->id);

        if(!empty($is_active) && !empty($is_active['active'])) {
          $activeforms[] = $form->id;
        }
      }

      // Reset the post data, possibly modified by `WPCF7_ContactForm::find()`.
      wp_reset_postdata();

      // If there are no forms with CTCT integration, get outta here
      if(empty($activeforms)) { return; }

      // Otherwise, add the icon to each row with integration.
?>
      <style>
        .ctct_enabled {
          position: absolute;
          background: url('<?php echo plugins_url('favicon.png',__FILE__); ?>') right top no-repeat;
          height: 16px;
          width: 16px;
          margin-left: 10px;
        }
      </style>
      <script>
        jQuery(document).ready(function($) {
          // Convert forms array into JSON array
          $activeforms = $.parseJSON('<?php echo json_encode($activeforms); ?>');

          // For each visible forms row
          $('table.posts tr').each(function() {
            // Get the ID of the row
            id = parseInt($('.check-column input', $(this)).val());

            // If the row is in the $activeforms array, add the icon span
            if($activeforms.indexOf(id) >= 0) {
              $('td a.row-title', $(this)).append('<span class="ctct_enabled" title="Constant Contact integration is enabled for this form."></span>');
            }
          });
        });
      </script>
      <?php
    }
  }

  static function admin_head() {
    global $plugin_page;

    if($plugin_page === 'ctct_cf7') { wp_enqueue_script('thickbox'); }

    if($plugin_page !== 'wpcf7') { return; }

    wp_enqueue_script('jquery-ui-tooltip');
?>
    <script type="text/javascript">

    jQuery(document).ready(function($) {


      $('#wpcf7-ctct-active').change(function() {
        if($(this).is(':checked')) {
          $('#wpcf7-ctct-all-fields').show();
        } else {
          $('#wpcf7-ctct-all-fields').hide();
        }
      }).trigger('change');
    });
  </script>

  <style>
    .half-left.error {
      width: 47%;
      margin: 0 1% 0 0!important;
    }
    .ui-tooltip {
      padding: 18px;
      position: absolute;
      z-index: 9999;
      max-width: 300px;
      color: #000;
        text-shadow: 1px 1px 1px #fff;
        font-size: 1.0em;
      -webkit-border-radius: 6px;
      -moz-border-radius: 6px;
      border-radius: 6px;
        -webkit-box-shadow: 0 8px 6px -6px rgba(0,0,0,0.3);
      -moz-box-shadow: 0 8px 6px -6px rgba(0,0,0,0.3);
      box-shadow: 0 8px 6px -6px rgba(0,0,0,0.3);
    }
    body .ui-tooltip {
      border: 4px solid #999;
      background-color: #ededed;
    }
    body .ui-tooltip h6 {
      color: #0e6085;
      font-size: 1.1em;
      font-weight: bold;
      margin: 0 0 3px 0 !important;
      padding: 0 !important;
    }
    .ctctcf7-tooltip {
      display: block;
      background: #eee;
      border-radius: 50px;
      color: #999;
      padding:5px 10px;
      cursor: help;
      float: right;
      border: 1px solid #ddd;
    }
  </style>
  <?php
  }

  static function plugins_action_links( $links, $file ) {
    if ( $file != plugin_basename( __FILE__ ) )
      return $links;

    $settings_link = '<a href="' . admin_url('admin.php?page=ctct_cf7') . '">' . esc_html( __( 'Settings', 'ctctcf7' ) ) . '</a>';

    array_unshift( $links, $settings_link );

    return $links;

  }

  static function get_includes() {
    $dir = trailingslashit(dirname( __FILE__ ));

    if (!class_exists("CTCT_API_Wrapper")) {
      include_once("{$dir}api/ctctAPIWrapper.php");
    }

    if (!class_exists("ConstantContact")) {
      include_once("{$dir}lib/cc-sdk/src/Ctct/autoload.php");
    }
  }


  protected static function validateApi() {
    return true;
  }

  static function getConfig($name) {
    if (isset($_POST['ctct_cf7'])) {
      return $_POST['ctct_cf7'][$name];
    }

    $settings = get_option('ctct_cf7');
    $value = !empty($settings[$name]) ? $settings[$name] : null;

    return $value;
  }

  static function admin_menu() {
    add_submenu_page( 'wpcf7', __('Constant Contact Contact Form 7 Settings'), __('Constant Contact'), 'manage_options', 'ctct_cf7', array('CTCTCF7', 'settings_page'));
  }

  static function settings_init() {

    self::get_includes();

    register_setting('ctct_cf7', 'ctct_cf7');

    add_settings_section(
      'ctct_api',
      __('Configure your Constant Contact account settings.'),
      array('CTCTCF7', 'setting_description'),
      'ctct_cf7'
    );
    // add_settings_field(
    //  'ctct_cf7_api_key',
    //  __('Constant Contact API Key'),
    //  array('CTCTCF7', 'setting_input_api_key'),
    //  'ctct_cf7',
    //  'ctct_api'
    // );
    add_settings_field(
      'ctct_cf7_access_token',
      __('Constant Contact Access Token'),
      array('CTCTCF7', 'setting_input_access_token'),
      'ctct_cf7',
      'ctct_api'
    );
  }

  static function setting_description() {
    $API = self::getAPIWrapper();

    // echo __('You may obtain an API Key by signing up for a <a href="https://constantcontact.mashery.com/">Constant Contact Mashery Account</a>.', 'ctctcf7');
    echo __('To get your Access Token, please follow <a target="_blank" href="' . $API->getAccessTokenUri() . '">this link</a> using the Constant Contact account ' .
      'that you want to use with this plugin.<br>You will have to grant access to your account for this plugin, after which you will receive an Access Token.<br>' .
      'Copy that token and paste it in the Access Token box below.', 'ctctcf7');
  }

  static function setting_input_api_key () {
    echo '<input autocomplete="off" name="ctct_cf7[api_key]" id="ctct_cf7_api_key" type="text" value="' . self::getConfig('api_key') . '" class="text" />';
  }

  static function setting_input_access_token() {
    echo '<input autocomplete="off" name="ctct_cf7[access_token]" id="ctct_cf7_access_token" type="text" value="' . self::getConfig('access_token') . '" class="text" />';
  }

  static function getAPIWrapper() {
    return new CTCT_API_Wrapper(self::getConfig('access_token'));
  }

  /**
   * Check the status of a plugin.
   *
   * @param string $plugin Base plugin path from plugins directory.
   * @return int 1 if active; 2 if inactive; 0 if not installed
   */
  static function get_plugin_status($location = '') {

    $errors = validate_plugin($location);

    // Plugin is found
    if(!is_wp_error($errors)) {
      if(is_plugin_inactive($location)) {
        return 2;
      }
      return 1;
    }
    else {
      return false;
    }
  }

  static function settings_page() {
    wp_enqueue_style( 'thickbox' );
?>
  <div class="wrap">
    <img src="<?php echo plugins_url('CTCT_horizontal_logo.png', __FILE__); ?>" width="281" height="47" alt="Constant Contact" style="margin-top:1em;" />
    <h2 style="padding-top:0;margin-bottom:.5em;"><?php _e('Contact Form 7 Module', 'ctctcf7'); ?></h2>

    <form action="options.php" method="post">
      <?php
        $message = '';
        $status = self::get_plugin_status('contact-form-7/wp-contact-form-7.php');
        switch($status) {
          case 1: $message = false; break;
          case 2: $message = __('Contact Form 7 is installed but inactive. Activate Contact Form 7 to use this plugin.', 'ctctcf7'); break;
          case 0: $message = __(sprintf('Contact Form 7 is not installed. <a href="%s" class="thickbox" title="Install Contact Form 7">Install the Contact Form 7 plugin</a> to use this plugin.', admin_url('plugin-install.php?tab=plugin-information&amp;plugin=contact-form-7&amp;TB_iframe=true&amp;width=600&amp;height=650')), 'ctctcf7'); break;
        }

        if(!empty($message)) {
          echo sprintf("<div id='message' class='error'><p>%s</p></div>", $message);
        }

        if(is_null(self::getConfig('access_token'))) {
          echo "<div id='message' class='error alignleft'><p>".__('Your access token is empty.', 'ctctcf7')."</p></div>";
        }

        echo '<div class="clear"></div>';
        settings_fields('ctct_cf7');
        do_settings_sections('ctct_cf7');
      ?>
      <p class="submit"><input class="button-primary" type="submit" name="Submit" value="<?php _e('Submit', 'ctctcf7'); ?>" />
    </form>
  </div><!-- .wrap -->
  <?php
  }

  static function save_form_settings($args) {
    update_option( 'cf7_ctct_'.$args->id, $_POST['wpcf7-ctct'] );
  }

  static function add_meta_box() {
    if ( wpcf7_admin_has_edit_cap() ) {
    add_meta_box( 'cf7ctctdiv', __( 'Constant Contact', 'ctctcf7' ),
      array('CTCTCF7', 'metabox'), 'cfseven', 'cf7_ctct', 'core',
      array(
        'id' => 'ctctcf7',
        'name' => 'cf7_ctct',
        'use' => __( 'Use Constant Contact', 'ctctcf7' ) ) );
    }
  }

  static function show_ctct_metabox($cf){
    do_meta_boxes( 'cfseven', 'cf7_ctct', $cf );
  }

  static function metabox($args) {
    $API = self::getAPIWrapper();
    $cf7_ctct_defaults = array();
    $cf7_ctct = get_option( 'cf7_ctct_'.$args->id, $cf7_ctct_defaults );

    if (!$cf7_ctct) {
      $cf7_ctct = array();
    }

    if (!isset($cf7_ctct['lists'])) {
      $cf7_ctct['lists'] = array();
    }
  ?>
  <script>
    jQuery(document).ready(function($) {
      $('.ctctcf7-tooltip').tooltip({
            content: function () {
                return $(this).prop('title');
            }
        });
    });
  </script>

  <div class="ctctcf7-tooltip" title="<h6><?php _e('Backward Compatibility', 'ctctcf7'); ?></h6><p><?php _e('Starting with Version 2.0 of Contact Form 7 Newsletter plugin, the lists a form sends data to should be defined by generating a tag above &uarr;</p><p>For backward compatibility, <strong>if you don\'t define any forms using a tag above</strong>, your form will continue to send contact data to these lists:', 'ctctcf7'); ?></p><ul class='ul-disc'>
  <?php
    $API = self::getAPIWrapper();
    $lists = $API->getLists();
    foreach($lists as $list) {
    if(!in_array($list->id, (array)$cf7_ctct['lists'])) { continue; }
    echo '<li>'.$list->name.'</li>';
    }
  ?></ul><p><strong>For full instructions, go to the Contact > Constant Contact page and click 'View integration instructions'.</strong></p>"><?php _e('Where are my lists?', 'ctctcf7'); ?></div>
  <img src="<?php echo plugins_url('CTCT_horizontal_logo.png', __FILE__); ?>" width="281" height="47" alt="Constant Contact Logo" style="margin-top:.5em;" />

<?php if(self::validateApi()) { ?>
<div class="mail-field clear" style="padding-bottom:.75em">
  <input type="checkbox" id="wpcf7-ctct-active" name="wpcf7-ctct[active]" value="1"<?php checked((isset($cf7_ctct['active']) && $cf7_ctct['active']==1), true); ?> />
  <label for="wpcf7-ctct-active"><?php echo esc_html( __( 'Send form entries to Constant Contact', 'ctctcf7' ) ); ?></label>
</div>
  <?php } else { ?>
<div class="mail-field clear">
  <div class="error inline"><p><?php _e(sprintf('The plugin\'s Constant Contact settings are not configured properly. <a href="%s">Go configure them now.', admin_url('admin.php?page=ctct_cf7')), 'ctctcf7'); ?></a></p></div>
</div>
  <?php return; } ?>


<div class="mail-fields clear" id="wpcf7-ctct-all-fields">

  <!-- Backward Compatibility -->
  <div><?php
    foreach((array)$cf7_ctct['lists'] as $list) {
      echo '<input type="hidden" name="wpcf7-ctct[lists][]" value="'.$list.'"  />';
    }
  ?></div>
  <!-- End Backward Compatibility -->

  <div class="clear ctct-fields">
    <hr style="border:0; border-bottom:1px solid #ccc; padding-top:1em" />
    <div class="clear"></div>
    <?php

      $instructions = __('<h2>Integration Fields</h2>', 'ctctcf7');
      $instructions .= '<p class="howto">';
      $instructions .= __('For each of the Integration Fields below, select the value you would like sent to Constant Contact.', 'ctctcf7');
      $instructions .= '</p>';

      echo $instructions;
    ?>

    <?php
      $i = 0;
      foreach($API->listMergeVars() as $var) {
    ?>
      <div class="half-<?php if($i % 2 === 0) { echo 'left'; } else { echo 'right'; }?>" style="clear:none;">
        <div class="mail-field">
        <label for="wpcf7-ctct-<?php echo $var['tag']; ?>"><?php echo $var['name']; echo !empty($var['req']) ? _e(' <strong>&larr; This setting is required.</strong>', 'ctctcf7') : ''; ?></label><br />
        <input type="text" id="wpcf7-ctct-<?php echo isset($var['tag']) ? $var['tag'] : ''; ?>" name="wpcf7-ctct[fields][<?php echo isset($var['tag']) ? $var['tag'] : ''; ?>]" class="wide" size="70" value="<?php echo @esc_attr( isset($cf7_ctct['fields'][$var['tag']]) ? $cf7_ctct['fields'][$var['tag']] : '' ); ?>" <?php if(isset($var['placeholder'])) { echo ' placeholder="Example: '.$var['placeholder'].'"'; } ?> />
        </div>
      </div>

    <?php
      if ($i % 2 === 1) {
        echo '<div class="clear"></div>';
      }

      $i++;
     } ?>

    <div class="clear mail-field" style="width:50%;">
      <label for="wpcf7-ctct-accept"><?php echo esc_html( __( 'Opt-In Field', 'ctctcf7' ) ); ?>
        <span class="howto"><?php _e('<strong>If you generated a "Constant Contact Lists" field above, this setting is not necessary, and will be ignored.</strong>', 'ctctcf7'); ?></span>
        <input type="text" id="wpcf7-ctct-accept" name="wpcf7-ctct[accept]" placeholder="Example: [checkbox-456]" class="wide" size="70" value="<?php echo esc_attr( isset($cf7_ctct['accept']) ? $cf7_ctct['accept'] : '' ); ?>" />
        <span class="howto"><?php _e('If the user should check a box to be added to the lists, enter the checkbox field here. Leave blank to have no opt-in field.', 'ctctcf7'); ?></span>
      </label>
    </div>

  </div>
  <div class="clear"></div>
</div>
<?php
  }

  static function process_submission($obj) {

    $cf7_ctct = get_option('cf7_ctct_' . $obj->id);

    // Let the shortcode functionality work with the data using a filter.
    $cf7_ctct = apply_filters('ctctcf7_push', apply_filters('ctctcf7_push_form_' . $obj->id, $cf7_ctct, $obj), $obj);

    if(empty($cf7_ctct) || empty($cf7_ctct['active']) || empty($cf7_ctct['fields']) || empty($cf7_ctct['lists'])) {
      return $obj;
    }

    self::get_includes();

    // If it doesn't load for some reason....
    if(!class_exists('CTCT_API_Wrapper')) {
      return $obj;
    }

    $contactData = array();

    foreach($cf7_ctct['fields'] as $key => $field) {
      if(empty($key) || empty($field)) {
        continue;
      }

      $value = self::get_submitted_value($field, $obj);
      $contactData[$key] = self::process_field($key, $value);
    }

    $contactData = self::process_contact($contactData);

    // We need an email address to continue.
    if(empty($contactData['email_address']) || !is_email($contactData['email_address'])) {
      return $obj;
    }

    $API = self::getAPIWrapper();

    $existingContact = $API->getContactByEmail($contactData['email_address']);

    // If there's a field to opt in, and the opt-in field is empty, return.
    if (!empty($cf7_ctct['accept'])) {
      $accept = self::get_submitted_value($cf7_ctct['accept'], $obj);

      if (empty($accept)) {
        return $obj;
      }

      $actionBy = 'ACTION_BY_VISITOR';
    } else {
      // Don't send them a welcome email
      $actionBy = 'ACTION_BY_OWNER';
    }

    // Create a new contact.
    if (!$existingContact) {
      $contact = $API->createContact($contactData);

      $contact->addEmail($contactData['email_address']);

      foreach ((array)$cf7_ctct['lists'] as $list) {
        $contact->addList($list);
      }

      $API->updateContactDetails($contact, $contactData);
    } else {
      // Update the existing contact with the new data
      $API->updateContactDetails($existingContact, $contactData);

      // Update Lists
      $lists = $existingContact->lists;

      foreach ((array)$cf7_ctct['lists'] as $list) {
        $existingContact->addList($list);
      }
    }

    try {
      if (!$existingContact) {
        $API->addContact($contact, array(
          'action_by' => $actionBy
        ));
      } else {
        $API->updateContact($existingContact, array(
          'action_by' => $actionBy
        ));

        do_action('cf7_ctct_succeeded', "success", $contactData, $existingContact);
      }
    } catch (Exception $e) {
      $errorMessages = print_r($e->getErrors(), true);

      if (WP_DEBUG === true) {
        error_log($errorMessages);
      }

      do_action('cf7_ctct_failed', "failure", $contactData, $existingContact);
    }

    return $obj;
  }

  static public function process_contact($contact = array()) {

    // Process the full name tag
    if(!empty($contact['full_name'])) {

      @include_once(plugin_dir_path(__FILE__).'nameparse.php');

      // In case it didn't load for some reason...
      if(function_exists('cf7_newsletter_parse_name')) {

        $name = cf7_newsletter_parse_name($contact['full_name']);

        if(isset($name['first'])) { $contact['first_name'] = $name['first']; }

        if(isset($name['middle'])) { $contact['middle_name'] = $name['middle']; }

        if(isset($name['last'])) { $contact['last_name'] = $name['last']; }

        unset($contact['full_name']);
      }
    }

    return $contact;
  }
  /**
   * If there are custom cases for a field, process them here.
   * @param  string $key   Key from CTCT_SuperClass::listMergeVars()
   * @param  string $value Passed value
   * @return string        return $value
   */
  static function process_field($key, $value) {
    return $value;
  }

  /**
   * Get the value from the submitted fields
   * @param  string  $subject     The name of the field; ie: [first-name]
   * @param  array  $posted_data The posted data, in array form.
   * @return [type]               [description]
   */
  static function get_submitted_value($subject, &$obj, $pattern = '/\[\s*([a-zA-Z_][0-9a-zA-Z:._-]*)\s*\]/') {

    if(is_callable(array($obj, 'replace_mail_tags'))) {

      // Make sure the title is wrapped in []
      $subject = preg_replace('/^(?:\[?)(.*)(?:\]?)$/ism', '[$1]', $subject);

      $replaced = $obj->replace_mail_tags($subject);

      return $replaced;
    }


    // Keeping below for back compatibility
    $posted_data = $obj->posted_data;
    if( preg_match($pattern,$subject,$matches) > 0) {

      if ( isset( $posted_data[$matches[1]] ) ) {
        $submitted = $posted_data[$matches[1]];

        if ( is_array( $submitted ) )
          $replaced = join( ', ', $submitted );
        else
          $replaced = $submitted;

        if ( $html ) {
          $replaced = strip_tags( $replaced );
          $replaced = wptexturize( $replaced );
        }

        $replaced = apply_filters( 'wpcf7_mail_tag_replaced', $replaced, $submitted );

        return stripslashes( $replaced );
      }

      if ( $special = apply_filters( 'wpcf7_special_mail_tags', '', $matches[1] ) )
        return $special;

      return $matches[0];
    }
    return $subject;
  }

}

$CTCTCF7 = new CTCTCF7;


if(!function_exists('r')) {
  function r($code, $die = false, $title = '') {
    if(!empty($title)) {
      echo '<h3>'.$title.'</h3>';
    }
    echo '<pre>';
    echo print_r($code, true);
    echo '</pre>';
    if($die) { die(); }
  }
}
