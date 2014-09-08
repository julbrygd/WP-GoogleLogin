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
require_once GOOGLE_LOGIN_PLUGIN_DIR . '/lib/GoogleLoginException.php';

class GoogleLogin {

    private static $INSTANCE = null;

    public static function getInstance() {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new self();
        }
        return self::$INSTANCE;
    }

    protected $settingsObject;

    const USER_META_DATA_KEY = "googleLogin_google_auth";
    const USER_META_IS_GOOGLE_KEY = "googleLogin_is_google";
    const USER_META_GOOGLE_ID = "googleLogin_googleId";
    const USER_META_GOOGLE_EMAIL = "googleLogin_googleMail";

    private function __construct() {
        add_filter('login_head', array(&$this, 'loginHead'));
        add_action('login_form', array(&$this, 'loginForm'));
        add_action('show_user_profile', array(&$this, 'extendProfile'));
        add_action('login_enqueue_scripts', array(&$this, 'enqueueStyle'), 10);
        add_action('login_enqueue_scripts', array(&$this, 'enqueueScript'), 1);
        add_action("admin_enqueue_scripts", array(&$this, 'enqueueScript'));
        $this->adminAjax();
        $this->settingsObject = GoogleLogin_Settings::getInstance();
    }

    public function enqueueStyle() {
        wp_enqueue_style("googleLoginCss", GOOGLE_LOGIN_PLUGIN_URL . "/css/style.css");
    }

    public function enqueueScript() {
        wp_enqueue_script("googleLoginJs", GOOGLE_LOGIN_PLUGIN_URL . "/js/loginScript.js", array('jquery'));
    }

    public function loginButton($callback = "signinCallback") {
        ?>
        <span id="signinButton">
            <span
                class="g-signin"
                data-callback="<?php echo $callback ?>"
                data-clientid="<?php echo $this->settingsObject->getOption("client_id"); ?>"
                data-redirecturi="postmessage"
                data-accesstype="offline"
                data-cookiepolicy="none"
                data-approvalprompt="force"
                data-scope="https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/calendar">
            </span>
        </span>
        <?php
    }

    public function loginForm() {
        ?>
        <p>
        <lable>Einloggen mit:</lable><br />
        <?php $this->loginButton(); ?>
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

        add_action('wp_ajax_gl_connect', array(&$this, "connectUser"));
        add_action('wp_ajax_gl_deleteMeta', array(&$this, "deleteMetaData"));
    }

    private function saveUserMeta($user_id, $key, $data) {
        $oldMeta = get_user_meta($user_id, $key, true);
        if ($oldMeta == "") {
            add_user_meta($user_id, $key, $data, true);
        } else {
            update_user_meta($user_id, $key, $data, $oldMeta);
        }
    }

    public function getGoogleClientArray($data) {
        return $this->getGoogleClient($data["code"], $data["access_token"]);
    }

    public function getGoogleClient($code = null, $access_token = null) {
        if ($code == null) {
            if (is_user_logged_in()) {
                $id = get_current_user_id();
                $data = get_user_meta($id, self::USER_META_DATA_KEY);
                if (isset($data["code"])) {
                    $code = $data["code"];
                } else {
                    return null;
                }
                if (isset($data["access_token"])) {
                    $access_token = $data["access_token"];
                } else {
                    $access_token = null;
                }
            } else {
                return null;
            }
        }
        $googleSettings = $this->settingsObject->getOptions();
        $client = new Google_Client();
        $client->setClientId($googleSettings["client_id"]);
        $client->setClientSecret($googleSettings["client_secret"]);
        $client->setApplicationName("Wordpress Google Login - " . get_bloginfo('name'));
        $client->setRedirectUri("postmessage");
        if ($access_token != null) {
            $client->setAccessToken($access_token);
        } else {
            $client->authenticate($code);
        }
        if ($client->isAccessTokenExpired()) {
            $NewAccessToken = json_decode($client->authenticate($authPost));
            $client->refreshToken($NewAccessToken->refresh_token);
        }
        return $client;
    }

    public function connectUser() {
        if (isset($_POST["code"]) && is_user_logged_in()) {
            $client = $this->getGoogleClient($_POST["code"]);
            $accessToken = $client->getAccessToken();
            $newMeta = array(
                "code" => $_POST["code"],
                "access_token" => $accessToken
            );
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
            $googleID = $me->getId();
            $user_id = get_current_user_id();
            $this->saveUserMeta($user_id, self::USER_META_DATA_KEY, $newMeta);
            $this->saveUserMeta($user_id, self::USER_META_IS_GOOGLE_KEY, true);
            $this->saveUserMeta($user_id, self::USER_META_GOOGLE_ID, $googleID);
            $this->saveUserMeta($user_id, self::USER_META_GOOGLE_EMAIL, $mail);
            wp_send_json(array(
                "code" => $authPost,
                "result" => "ok"
            ));
            exit();
        }
    }

    public function loginUser() {
        if (isset($_POST["auth"])) {
            $authPost = $_POST["auth"];
            $client = $this->getGoogleClient($authPost);
            $accessToken = $client->getAccessToken();
            $newMeta = array(
                "code" => $authPost,
                "access_token" => $accessToken
            );
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
            $googleID = $me->getId();
            $usersById = get_users(
                    array(
                        "meta_key" => "googleLogin_googleId",
                        "meta_value" => $googleID
            ));
            $user_id = null;
            if (count($usersById) == 1) {
                $user = $usersById[0];
                $user_id = $user->ID;
                $username = $user->user_login;
            } else {
                $user_id = username_exists($username);
            }
            if (!$user_id and email_exists($mail) == false) {
                if (get_option('users_can_register')) {
                    $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
                    $user_id = wp_create_user($username, $random_password, $mail);
                    $data = get_userdata($user_id);
                    $data->display_name = $me->getDisplayName();
                    $data->first_name = $me->getName()->getGivenName();
                    $data->last_name = $me->getName()->getFamilyName();
                    wp_update_user($data);
                    $data = get_userdata($user_id);
                } else {
                    wp_send_json(array(
                        "result" => "nok",
                        "msg" => "register_not_allowd"
                    ));
                }
            }
            // log in automatically
            if (!is_user_logged_in()) {
                $user = get_user_by('login', $username);
                $user_id = $user->ID;
                wp_set_current_user($user_id, $username);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $username);
                $this->saveUserMeta($user_id, self::USER_META_DATA_KEY, $newMeta);
                $this->saveUserMeta($user_id, self::USER_META_IS_GOOGLE_KEY, true);
                $this->saveUserMeta($user_id, self::USER_META_GOOGLE_ID, $googleID);
                $this->saveUserMeta($user_id, self::USER_META_GOOGLE_EMAIL, $mail);
                wp_send_json(array(
                    "code" => $authPost,
                    "result" => "ok"
                ));
            } else {
                wp_send_json(array(
                    "result" => "nok"
                ));
            }
        }
        exit();
    }

    public function deleteMetaData() {
        $user_id = get_current_user_id();
        print_r($_POST);
        if (is_user_logged_in() && isset($_POST["nonce"])) {
            if (wp_verify_nonce($_POST["nonce"], "deleteGLmeta_" . $user_id)) {
                delete_user_meta($user_id, self::USER_META_DATA_KEY);
                delete_user_meta($user_id, self::USER_META_GOOGLE_EMAIL);
                delete_user_meta($user_id, self::USER_META_GOOGLE_ID);
                delete_user_meta($user_id, self::USER_META_IS_GOOGLE_KEY);
                echo "ok";
                exit();
            }
        }
    }

    public function isGoogleUser() {
        $uid = get_current_user_id();
        return get_user_meta($uid, self::USER_META_IS_GOOGLE_KEY, true);
    }

    public function getGoogleData() {
        $uid = get_current_user_id();
        return get_user_meta($uid, self::USER_META_DATA_KEY, true);
    }

    public function extendProfile() {
        include GOOGLE_LOGIN_PLUGIN_DIR . "/view/profile.php";
    }

    public function getGoogleClientForCurrentUser() {
        $data = $this->getGoogleData();
        return $this->getGoogleClientArray($data);
    }

    public function getGoogleUserMail() {
        $uid = get_current_user_id();
        return get_user_meta($uid, self::USER_META_GOOGLE_EMAIL, true);
    }

    public function getGoogleUserId() {
        $uid = get_current_user_id();
        return get_user_meta($uid, self::USER_META_GOOGLE_ID, true);
    }

}

$login = GoogleLogin::getInstance();
