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

set_include_path(get_include_path() . PATH_SEPARATOR . GOOGLE_LOGIN_PLUGIN_DIR . '/vendor/google/apiclient/src');

require_once GOOGLE_LOGIN_PLUGIN_DIR . '/vendor/autoload.php';
require_once GOOGLE_LOGIN_PLUGIN_DIR . '/SettingsMenu.php';

class GoogleLogin {

    protected $settingsObject;

    function __construct() {
        add_filter('login_head', array(&$this, 'loginHead'));
        add_action('login_form', array(&$this, 'loginForm'));
        add_action('login_enqueue_scripts', array(&$this, 'enqueueStyle'), 10);
        add_action('login_enqueue_scripts', array(&$this, 'enqueueScript'), 1);
        $this->adminAjax();
        $this->settingsObject = GoogleLogin_Settings::getInstance();
    }

    public function enqueueStyle() {
        wp_enqueue_style("googleLoginCss", GOOGLE_LOGIN_PLUGIN_URL . "/css/style.css");
    }

    public function enqueueScript() {
        wp_enqueue_script("googleLoginJs", GOOGLE_LOGIN_PLUGIN_URL . "/js/loginScript.js", array('jquery'));
    }

    public function loginForm() {
        ?>
        <p>
        <lable>Einloggen mit:</lable><br />

        <span id="signinButton">
            <span
                class="g-signin"
                data-callback="signinCallback"
                data-clientid="<?php echo $this->settingsObject->getOption("client_id"); ?>"
                data-redirecturi="postmessage"
                data-accesstype="offline"
                data-cookiepolicy="none"
                data-approvalprompt="force"
                data-scope="https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/calendar">
            </span>
        </span>
        </p>
        <?php
    }

    public function loginHead() {
        ?>
        <script type="text/javascript">
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            (function() {
                var po = document.createElement('script');
                po.type = 'text/javascript';
                po.async = true;
                po.src = 'https://plus.google.com/js/client:plusone.js?onload=start';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(po, s);
            })();
        </script>

        <?php
    }

    public function adminAjax() {
        add_action('wp_ajax_gl_login', array(&$this, "loginUser"));
        add_action('wp_ajax_nopriv_gl_login', array(&$this, "loginUser"));
    }

    public function loginUser() {
        if (isset($_POST["auth"])) {
            $authPost = $_POST["auth"];
            $googleSettings = $this->settingsObject->getOptions();
            $client = new Google_Client();
            $client->setClientId($googleSettings["client_id"]);
            $client->setClientSecret($googleSettings["client_secret"]);
            $client->setRedirectUri("postmessage");
            $client->authenticate($authPost);
            if ($client->isAccessTokenExpired()) {
                $NewAccessToken = json_decode($client->authenticate($authPost));
                $client->refreshToken($NewAccessToken->refresh_token);
            }
            $plus = new Google_Service_Plus($client);
            $me = $plus->people->get('me');
            $mails = $me->getEmails();
            $mail = "";
            if (count($mails) == 1) {
                $mail = $mails[0]["value"];
            } else {
                foreach ($mails as $v) {
                    if ($v["type"] == "account") {
                        $mail = ["value"];
                    }
                }
            }
            $username = $me->getNickname();
            if ($username == "") {
                $username = $mail;
            }
            $user_id = username_exists($username);
            if (!$user_id and email_exists($mail) == false) {
                $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
                $user_id = wp_create_user($username, $random_password, $mail);
                $data = get_userdata($user_id);
                $data->display_name = $me->getDisplayName();
                $data->first_name = $me->getName()->getGivenName();
                $data->last_name = $me->getName()->getFamilyName();
                wp_update_user($data);
                $data = get_userdata($user_id);
                file_put_contents(GOOGLE_LOGIN_PLUGIN_DIR . "/log.txt", "Userdata: " . print_r($data, true));
                file_put_contents(GOOGLE_LOGIN_PLUGIN_DIR . "/log.txt", "Plusdata: " . print_r($me, true), FILE_APPEND);
            }

            // log in automatically
            if (!is_user_logged_in()) {
                $user = get_userdatabylogin($username);
                $user_id = $user->ID;
                wp_set_current_user($user_id, $user_login);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $user_login);
            }
        }
        exit();
    }

}

$login = new GoogleLogin();
