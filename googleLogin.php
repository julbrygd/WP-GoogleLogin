<?php
/*
  Plugin Name: Google Login
  Plugin URI: http://localhost/wordpress
  Description: Google Login Plugin
  Author: Stephan Conrad
  Version: 1.0.0
  Author URI: http://localhost/wordpress
 */

define("GOOGLE_LOGIN_PLUGIN_DIR", WP_PLUGIN_DIR . "/googleLogin");
define("GOOGLE_LOGIN_PLUGIN_URL", plugins_url() . "/googleLogin");

require_once GOOGLE_LOGIN_PLUGIN_DIR . '/vendor/autoload.php';
require_once GOOGLE_LOGIN_PLUGIN_DIR . '/SettingsMenu.php';

class GoogleLogin {

    protected $settingsObject;

    function __construct() {
        add_filter('login_head', array(&$this, 'loginHead'));
        add_action('login_form', array(&$this, 'loginForm'));
        add_action('login_enqueue_scripts', array(&$this, 'enqueueStyle'), 10);
        add_action('login_enqueue_scripts', array(&$this, 'enqueueScript'), 1);
        $this->settingsObject = GoogleLogin_Settings::getInstance();
    }

    public function enqueueStyle() {
        wp_enqueue_style("googleLoginCss", GOOGLE_LOGIN_PLUGIN_URL . "/css/style.css");
    }

    public function enqueueScript() {
        
    }

    public function loginForm() {
        ?>
        <lable>Einloggen mit:</lable><br />

        <span id="signinButton">
            <span
                class="g-signin"
                data-callback="signinCallback"
                data-clientid="824683902314-uar96f562e4df03n8l5ss8uh197iep9o.apps.googleusercontent.com"
                data-cookiepolicy="single_host_origin"
                data-requestvisibleactions="http://schemas.google.com/AddActivity"
                data-scope="https://www.googleapis.com/auth/plus.login">
            </span>
        </span>

        <?php
    }

    public function loginHead() {
        ?>
        <script type="text/javascript">
            (function() {
                var po = document.createElement('script');
                po.type = 'text/javascript';
                po.async = true;
                po.src = 'https://apis.google.com/js/client:plusone.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(po, s);
            })();
        </script>

        <?php
    }

}

$login = new GoogleLogin();
