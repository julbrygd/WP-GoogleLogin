<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class GoogleLogin_Settings {

    private static $INSTANCE = null;
    private static $NONCE_NAME = "googleLogin_settings";

    public static function getInstance() {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new self();
        }
        return self::$INSTANCE;
    }

    private function __construct() {
        add_action('admin_menu', array(&$this, "addOptionPage"));
    }

    public function addOptionPage() {
        add_options_page("Google Login", "Google Login", "create_users", "gl_settings_page", array(&$this, "showLoginPage"));
    }

    public function showLoginPage() {
        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == "update") {
            print_r($_REQUEST);
        }
        ?>
        <div class="wrap">
            <h2>Einstellungen › Google Login</h2>

            <form action="options.php" method="post">
                <input type="hidden" name="option_page" value="gl_settings_page">
                <input type="hidden" name="action" value="update">
                <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce(self::$NONCE_NAME); ?>">
                <input type="hidden" name="_wp_http_referer" value="/wordpress/wp-admin/options-general.php?page=gl_settings_page">
                <h3 class="title">Google API</h3>
                <p>Hier sind die Einstellungen f&uuml;r die Google API</p>
                <table class="form-table">
                    <tbody><tr>
                            <th scope="row">Client ID</th>
                            <td>
                                <input name="client_id" type="text" id="client_id" class="large-text">

                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Änderungen übernehmen"></p>
            </form>
        </div>
        <?php
    }

}
