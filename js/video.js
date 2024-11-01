// javascript for video part of the WebRTC client
var weSeeDoVideo = function(){
    var errorText = jQuery('#errorText');
    var startButton = jQuery('#startButton');
    var endButton = jQuery('#endButton');
    var messageContainer = jQuery('#message-container');
    var messageBar = jQuery('#message');
    var messageImage = jQuery('#message-image');
    var remoteVideoWrap = jQuery('#remote-video-wrap');


    var initiator = false;
    var mediaReady = false;
    var remoteJoined = false;
    var offerStarted = false;

    // since we need to be sure we have the right JS version of the webrtc signaling server it is self hosted
    if (typeof WebRTCClient == 'undefined') {
        var webrtc = document.createElement("script");
        webrtc.type = "text/javascript";
        webrtc.src = "https://client.webrtcoplossingen.nl/js/webrtc.min.js";
        webrtc.onload = load;
        document.head.appendChild(webrtc);
    }

    function load()
    {
        weSeeDoClient = new WebRTCClient(getCookie("weseedo.visitor"),weseedo.session,weseedo.account);
        weSeeDoClient.startLocalVideo();
        window.onbeforeunload = function() { weSeeDoClient.hangup();weSeeDoClient.disconnect();}
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

    /**
     * Event webrtc_disconnected.
     * Triggered when connection is lost with the server and when server is not available during initiate.
     */
    document.addEventListener("webrtc_disconnected", function () {
        errorText.text("De verbinding met de server is verbroken.");
        errorText.show();
        startButton.hide();
        endButton.hide();
        remoteVideo.src = "";

        if (typeof audio != 'undefined') {
            audio.pause();
            if (audio.currentTime != 0) audio.currentTime = 0;
        }
    });


    document.addEventListener("webrtc_media_error", function () {
        errorText.text("Camera kan niet geladen worden.  Sluit het scherm.");
        errorText.show();
        weSeeDoClient.hangup();
        messageContainer.hide();
    });

    /**
     * Event webrtc_call_started
     * Called when call is started. Could be used to hide start button and then show hangup button.
     */
    document.addEventListener("webrtc_call_started", function () {
        offerStarted = false;

        messageContainer.show();
        messageBar.text("Het gesprek gaat zo van start");
        messageImage.show();
        messageImage.text("3");
        setTimeout(function(){
            messageImage.text("2");
            setTimeout(function(){
                messageImage.text("1");
                setTimeout(function(){
                    messageContainer.hide();
                    messageImage.hide();
                    remoteVideoWrap.show();

                    // unmute remote audio
                    weSeeDoClient.toggleAudioMute(); // turn audio on

                },1000);
            },1000);
        },1000);

        endButton.show();
        startButton.hide();
        if (typeof audio != 'undefined') {
            audio.pause();
            if (audio.currentTime != 0) audio.currentTime = 0;
        }
    });

    /**
     * Event webrtc_call_ended
     * Called when call is ended. Could be used to hide hangup button and show start button again.
     */
    document.addEventListener("webrtc_call_ended", function () {

        messageBar.text("Call ended");
        messageContainer.show();

        //startButton.show();
        endButton.hide();
        if (typeof audio != 'undefined') {
            audio.pause();
            if (audio.currentTime != 0) audio.currentTime = 0;
        }
    });

    /**
     * Event webrtc_joined
     * Called when you joined the room.
     */
    document.addEventListener("webrtc_joined", function (e) {

        initiator = e.detail.initiator;
        console.log("you joined room " + e.detail.room_id);
    });


    /**
     * Event webrtc_remote_left
     * Called when remote party left the room.
     */
    document.addEventListener("webrtc_remote_left", function () {

        remoteJoined = false;
        messageBar.text("Sessie beeindigd. Sluit het scherm.");
        messageContainer.show();
        remoteVideoWrap.hide();

        if (typeof audio != 'undefined') {
            audio.pause();
            if (audio.currentTime != 0) audio.currentTime = 0;
        }
        startButton.hide();
        endButton.hide();
        weSeeDoClient.stopLocalVideo();
    });

    /**
     * Event webrtc_remote_left
     * Called when you left the room.
     */
    document.addEventListener("webrtc_left", function () {

        messageBar.text("Sessie beeindigd. Sluit het scherm.");
        messageContainer.show();
        remoteVideoWrap.hide();


        if (typeof audio != 'undefined') {
            audio.pause();
            if (audio.currentTime != 0) audio.currentTime = 0;
        }
        startButton.hide();
        endButton.hide();
    });

    /**
     * Event webrtc_remote_joined
     * Called when remote party joined the room.
     */
    document.addEventListener("webrtc_remote_joined", function (e) {

        remoteJoined = true;
        if (!mediaReady) {
            messageBar.text("Sessie gestart. Wacht op camera..");
            messageContainer.show();
        }

        console.log(e.detail.username + " joined room " + e.detail.room_id);

        if (mediaReady && remoteJoined) {
            weSeeDoVideo.startWeSeeDoCall();
        }

    });

    document.addEventListener("webrtc_media_ready", function (e) {

        mediaReady = true;

        // mute audio by default
        weSeeDoClient.toggleAudioMute(); // turn audio off

        if (mediaReady && remoteJoined) {
            weSeeDoVideo.startWeSeeDoCall();
        }

    });

    /**
     * Event webrtc_call_cancelled
     * Called when caller hangs up or ends the offer and call was not yet answered.
     */
    document.addEventListener("webrtc_call_cancelled", function () {

        messageBar.text("Incoming call cancelled..");
        messageContainer.show();

        // stop playing audio created at event webrtc_offer
        if (typeof audio != 'undefined') {
            audio.pause();
            if (audio.currentTime != 0) audio.currentTime = 0;
        }
    });


    /**
     * Start call.
     */
    this.startWeSeeDoCall = function() {

        if (!offerStarted) {
            offerStarted = true;
        } else {
            return;
        }

        messageBar.text("Gesprek gestart. Wacht op antwoord..");
        messageContainer.show();

        if (typeof audio != 'undefined') {
            audio.pause();
            if (audio.currentTime != 0) audio.currentTime = 0;
        }

        // play ring audio sound
        audio = document.createElement("audio");
        audio.src = "/wp-content/plugins/weseedo/mp3/ringing.mp3";
        audio.loop = true;
        audio.play();


        weSeeDoClient.start();
        startButton.hide();
        endButton.show();


    };

    /**
     * End call.
     */
    this.endWeSeeDoCall = function() {

        messageBar.text("Gesprek beeindigd.");
        messageContainer.show();

        if (typeof audio != 'undefined') {
            audio.pause();
            if (audio.currentTime != 0) audio.currentTime = 0;
        }
        startButton.hide();
        endButton.hide();
        weSeeDoClient.stopLocalVideo();
        weSeeDoClient.hangup();
    }

};

var weSeeDoVideo = new weSeeDoVideo();
