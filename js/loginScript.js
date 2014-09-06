/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


function signinCallback(authResult) {
    if (authResult['access_token']) {
        // Autorisierung erfolgreich
        // Nach der Autorisierung des Nutzers nun die Anmeldeschaltfläche ausblenden, zum Beispiel:
        document.getElementById('signinButton').setAttribute('style', 'display: none');
        var data = {
            'action': 'gl_login',
            'auth': authResult['code']
        };
        (function($) {
            $.post(ajaxurl, data, function(response) {
                var redirect = $("input[name='redirect_to']").val();
                var p = $("<p>");
                var loginDiv = $("div#login");
                var h1 = undefined;
                loginDiv.children().each(function () {
                    if($(this).prop("tagName").toLowerCase() === "h1" && h1 === undefined) {
                        h1 = $(this);
                    }
                });
                var redir = false;
                if (response.result === "ok" && response.code === authResult['code']) {
                    p.addClass("message");
                    p.html("Sie wurde erfolgreich mit Google angemeldet. <br />");
                    redir = true;
                } else {
                    p = $("<div>");
                    p.attr("id","login_error");
                    p.html("Ein fehler is beim Google Login aufgetreten. <br />");
                }
                h1.after(p);
                if(redir){
                    window.location = redirect;
                }
            });
        })(jQuery);
    } else if (authResult['error']) {
        // Es gab einen Fehler.
        // Mögliche Fehlercodes:
        //   "access_denied" – Der Nutzer hat den Zugriff für Ihre App abgelehnt.
        //   "immediate_failed" – Automatische Anmeldung des Nutzers ist fehlgeschlagen.
        // console.log('Es gab einen Fehler: ' + authResult['Fehler']);
    }
}

function connectCallback(authResult) {
    if (authResult['access_token']) {
        // Autorisierung erfolgreich
        // Nach der Autorisierung des Nutzers nun die Anmeldeschaltfläche ausblenden, zum Beispiel:
        document.getElementById('signinButton').setAttribute('style', 'display: none');
        var data = {
            'action': 'gl_login',
            'auth': authResult['code']
        };
        (function($) {
            $.post(ajaxurl, data, function(response) {
                var redirect = $("input[name='redirect_to']").val();
                var p = $("<p>");
                var loginDiv = $("div#login");
                var h1 = undefined;
                loginDiv.children().each(function () {
                    if($(this).prop("tagName").toLowerCase() === "h1" && h1 === undefined) {
                        h1 = $(this);
                    }
                });
                var redir = false;
                if (response.result === "ok" && response.code === authResult['code']) {
                    p.addClass("message");
                    p.html("Sie wurde erfolgreich mit Google angemeldet. <br />");
                    redir = true;
                } else {
                    p = $("<div>");
                    p.attr("id","login_error");
                    p.html("Ein fehler is beim Google Login aufgetreten. <br />");
                }
                h1.after(p);
                if(redir){
                    window.location = redirect;
                }
            });
        })(jQuery);
    } else if (authResult['error']) {
        // Es gab einen Fehler.
        // Mögliche Fehlercodes:
        //   "access_denied" – Der Nutzer hat den Zugriff für Ihre App abgelehnt.
        //   "immediate_failed" – Automatische Anmeldung des Nutzers ist fehlgeschlagen.
        // console.log('Es gab einen Fehler: ' + authResult['Fehler']);
    }
}