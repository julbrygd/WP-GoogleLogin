<?php
$g = GoogleLogin::getInstance();
$data = null;
$access_token = "";
if ($g->isGoogleUser()) {
    $data = $g->getGoogleData();
    $access_token = json_decode($data["access_token"], true)["access_token"];
    $client = $g->getGoogleClientForCurrentUser();
    $plus = new Google_Service_Plus($client);
    $me = $plus->people->get('me');
}
?>
<h3>Google Account</h3>
<table class="form-table">
    <tbody>
        <?php if ($g->isGoogleUser()) { ?>
            <tr>
                <th>Goole User</th>
                <td><?php echo $g->getGoogleUserMail() ?></td>
            </tr>
        <?php } ?>
        <tr>
            <th>Google Login</th>
            <td>
                <?php if ($g->isGoogleUser()) { ?>
                    <p id="googleLogin_disconectButton">
                        <input type="button" class="button button-primary" id="revokeButton" value="Disconect Google" />
                    </p>
                    <?php
                } else {
                    $g->loginHead();
                    ?>
                    <?php $g->loginButton("connectCallback"); ?>
                <?php } ?>
            </td>
        </tr>
    </tbody>
</table>

<script type="text/javascript">
    (function($) {
        function disconnectUser() {
            var data = {
                'action': 'gl_deleteMeta',
                'nonce': "<?php echo wp_create_nonce("deleteGLmeta_" . get_current_user_id()); ?>"
            };
            $.post(ajaxurl, data, function(response) {
                var access_token = "<?php echo $access_token; ?>";
                var revokeUrl = 'https://accounts.google.com/o/oauth2/revoke?token=' +
                        access_token;
                // Führen Sie einen asynchrone GET-Anfrage durch.
                $.ajax({
                    type: 'GET',
                    url: revokeUrl,
                    async: false,
                    contentType: "application/json",
                    dataType: 'jsonp',
                    success: function(nullResponse) {
                        location.reload();
                    },
                    error: function(e) {
                        // Handhaben Sie den Fehler.
                        // console.log(e);
                        // Wenn es nicht geklappt hat. könnten Sie Nutzer darauf hinweisen, wie die manuelle Trennung erfolgt.
                        // https://plus.google.com/apps
                        var p = $("<p>");
                        p.html("Die Verbindung zu Google konnte nicht getrennt werden. Sie k&ouml;nnen die Verbindung ");
                        var a = $("<a>");
                        a.prop("href", "https://plus.google.com/apps");
                        a.html(" hier trennen");
                        p.append(a);
                        $("#googleLogin_disconectButton").append(p);
                    }
                });

            });

        }
// Sie könnten die Trennung über den Klick auf eine Schaltfläche auslösen.
        $('#revokeButton').click(disconnectUser);
    })(jQuery);
</script>
