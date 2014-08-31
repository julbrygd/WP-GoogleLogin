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
                    <input type="button" class="button button-primary" id="revokeButton" value="Disconect Google" />
                <?php } else { 
                    $g->loginHead();
                    ?>
                    <?php $g->loginButton();?>
                <?php } ?>
            </td>
        </tr>
    </tbody>
</table>

<script type="text/javascript">
    (function($) {
        function disconnectUser() {
            var access_token = "<?php echo $access_token; ?>";
            var revokeUrl = 'https://accounts.google.com/o/oauth2/revoke?token=' +
                    access_token;
            alert(revokeUrl);
            return;
            // Führen Sie einen asynchrone GET-Anfrage durch.
            $.ajax({
                type: 'GET',
                url: revokeUrl,
                async: false,
                contentType: "application/json",
                dataType: 'jsonp',
                success: function(nullResponse) {
                    // Führen Sie jetzt nach der Trennung des Nutzers eine Aktion durch.
                    // Die Reaktion ist immer undefiniert.
                },
                error: function(e) {
                    // Handhaben Sie den Fehler.
                    // console.log(e);
                    // Wenn es nicht geklappt hat. könnten Sie Nutzer darauf hinweisen, wie die manuelle Trennung erfolgt.
                    // https://plus.google.com/apps
                }
            });
        }
// Sie könnten die Trennung über den Klick auf eine Schaltfläche auslösen.
        $('#revokeButton').click(disconnectUser);
    })(jQuery);
</script>
