<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class GoogleLogin_Settings {

    private static $INSTANCE = null;
    private static $NONCE_NAME = "googleLogin_settings";
    private static $SETTINGS_KEY = "gl_settings_array";

    public static function getInstance() {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new self();
        }
        return self::$INSTANCE;
    }

    private function __construct() {
        add_action('admin_menu', array(&$this, "addOptionPage"));
        add_action('admin_post_gl_settings_page_save', array(&$this, "saveSettings"));
    }

    public function addOptionPage() {
        add_options_page("Google Login", "Google Login", "create_users", "gl_settings_page", array(&$this, "showLoginPage"));
    }

    public function saveSettings() {
        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "gl_settings_page_save" && wp_verify_nonce($_REQUEST["_wpnonce"], self::$NONCE_NAME)) {
            if (!current_user_can('manage_options')) {
                wp_die('You are not allowed to be on this page.');
            }
            // Check that nonce field
            check_admin_referer(self::$NONCE_NAME);

            $options = get_option(self::$SETTINGS_KEY);

            if (isset($_POST['client_id'])) {
                $options['client_id'] = sanitize_text_field($_POST['client_id']);
            }
            if (isset($_POST['client_secret'])) {
                $options['client_secret'] = sanitize_text_field($_POST['client_secret']);
            }
            if (isset($_POST['email_address'])) {
                $options['email_address'] = sanitize_text_field($_POST['email_address']);
            }

            update_option(self::$SETTINGS_KEY, $options);

            wp_redirect(admin_url('options-general.php?page=gl_settings_page'));
            exit;
        }
    }

    public function showLoginPage() {
        $options = get_option(self::$SETTINGS_KEY);
        ?>
        <div class="wrap">
            <h2>Einstellungen › Google Login</h2>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <input type="hidden" name="action" value="gl_settings_page_save">
                <?php wp_nonce_field( self::$NONCE_NAME ); ?>
                <input type="hidden" name="_wp_http_referer" value="/wordpress/wp-admin/options-general.php?page=gl_settings_page">
                <h3 class="title">Google API</h3>
                <p>Hier sind die Einstellungen f&uuml;r die Google API</p>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">Client ID</th>
                            <td>
                                <input name="client_id" type="text" id="client_id" class="large-text" value="<?php echo $options['client_id'];?>">

                            </td>
                        </tr>
                         <tr>
                            <th scope="row">CLIENT SECRET</th>
                            <td>
                                <input name="client_secret" type="text" id="client_id" class="large-text" value="<?php echo $options['client_secret'];?>">

                            </td>
                        </tr>
                        </tr>
                         <tr>
                            <th scope="row">EMAIL ADDRESS</th>
                            <td>
                                <input name="email_address" type="text" id="client_id" class="large-text" value="<?php echo $options['email_address'];?>">

                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Änderungen übernehmen"></p>
            </form>
        </div>
        <?php
    }
    
    public function getOptions() {
        return get_option(self::$SETTINGS_KEY);
    }
    
    public function getOption($name) {
        return get_option(self::$SETTINGS_KEY)[$name];
    }

}
