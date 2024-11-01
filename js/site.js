// javascript for website part of the WebRTC client
(function(){

    var host = document.location.hostname,
        account = weseedo.account;

    // since we need to be sure we have the right JS version of the webrtc signaling server it is self hosted
    if (typeof WebRTCClient == 'undefined') {
        var webrtc = document.createElement("script");
        webrtc.type = "text/javascript";
        webrtc.src = "https://client.webrtcoplossingen.nl/js/webrtc.min.js";
        webrtc.onload = load;
        document.head.appendChild(webrtc);
    }

    function makeId(length)
    {
        var text = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        for( var i=0; i < length; i++ )
            text += possible.charAt(Math.floor(Math.random() * possible.length));

        return text;
    }

    function load() {

        if (account.length == 0) return;

        // check firefox or chrome
        if (!navigator.mozGetUserMedia && !navigator.webkitGetUserMedia ) return;

        if (!getCookie("weseedo.visitor")) {
            setCookie("weseedo.visitor", "visitor." + makeId(16));
        }

        var weSeeDoClient = new WebRTCClient(getCookie("weseedo.visitor"), null, account);

        /**
         * Event webrtc_disconnected.
         * Triggered when connection is lost with the server and when server is not available during initiate.
         */
        document.addEventListener("webrtc_disconnected", function () {
            showHideWeSeeDo("offline");
        });

        /**
         * Event webrtc_agent_availability
         * Triggers when an agent becomes available or when there are no more agents available
         */
        document.addEventListener("webrtc_agent_availability", function (e) {

            var account = e.detail.account;
            if (typeof account != 'undefined') {

                if (account.host != host) {
                    showHideWeSeeDo("offline");
                    return;
                }

                var status = account.status || "online";
                showHideWeSeeDo(status);


            } else {
                showHideWeSeeDo("offline");
            }


        });

    }

    function showHideWeSeeDo(status)
    {
        // show status blocks
        var selection = document.querySelectorAll("div[id^='weseedo_status']");
        for (var i=0; i<selection.length; i++){
            if (selection[i].id == "weseedo_status_"+status) {
                selection[i].style.display = "";
            } else {
                selection[i].style.display = "none";
            }
        }

        // show buttons
        var selection = document.querySelectorAll("input[class^='weseedo_button']");
        for (var i=0; i<selection.length; i++){
            if (status == "online") {
                selection[i].style.display = "";
            } else {
                selection[i].style.display = "none";
            }
        }
    }


    function setCookie(cname, cvalue, exdays) {
        var expires = "";
        var cpath = "path=/;";
        if (exdays) {
            var d = new Date();
            d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
            expires = "expires=" + d.toUTCString()+";";
        }
        document.cookie = cname + "=" + cvalue + "; " + expires + cpath;
    }

    function getCookie(cname) {
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return "";
    }

    return true;
}());
