<?php


class WeSeeDoSite
{

    // reference to api
    private $api;

    public function __construct()
    {
        add_shortcode('weseedo_button', array($this, 'showWeSeeDoButton'));
        add_shortcode('weseedo_video', array($this, 'showWeSeeDoVideo'));
        add_shortcode('weseedo_status', array($this, 'showWeSeeDoStatus'));

        add_action( 'wp_enqueue_scripts', 'weseedo_register_plugin_styles' );
        add_action( 'wp_enqueue_scripts', 'weseedo_register_plugin_scripts' );
    }

    /*
     * function for shortcode [weseedo_button]
     */
    public function showWeSeeDoButton($atts, $content = null)
    {
        global $weseedoApi;
        $data = $this->getAccountData($atts['id']);
        if (!$data) return "";

        if(!$content) $content = "start video";

        // required settings
        $page = get_permalink($data['page']);
        $status = $this->getApi()->getStatus($data['account']);;

        $visible = "";
        if ($status != "online") {
            $visible = "display: none;";
        }

        // define button
        $button  = '<form action="'.$page.'">';
        $button .= '<input type="submit" class="weseedo_button" value="'.$content.'" style="'.$visible.'" />';
        $button .= '</form>';

        if( ! wp_script_is( 'weseedo_site_js', 'enqueued' ) ) {
            wp_localize_script( 'weseedo_site_js', 'weseedo', array('account' => $data['account']));
            wp_enqueue_script('weseedo_socketio_js');
            wp_enqueue_script('weseedo_site_js');
        }

        return $button;


    }

    /*
     * function for shortcode [weseedo_status ..]
     */
    public function showWeSeeDoStatus($atts,$content = null)
    {


        $data = $this->getAccountData($atts['id']);
        if (!$data) return "";


        $status = $this->getApi()->getStatus($data['account']);

        $a = shortcode_atts( array(
            'status' => 'online',
        ), $atts );

        $visible = "";
        if ($status != $a['status']) {
            $visible = "display: none;";
        }

        $html  = '<!-- WeSeeDo status '.$a['status'].' -->';
        $html .= '<div id="weseedo_status_'.$a['status'].'" class="weseedo_status weseedo_'.$a['status'].'" style="'.$visible.'">';
        $html .= do_shortcode($content);
        $html .= '</div>';

        if( ! wp_script_is( 'weseedo_site_js', 'enqueued' ) ) {
            wp_localize_script( 'weseedo_site_js', 'weseedo', array('account' => $data['account']));
            wp_enqueue_script('weseedo_socketio_js');
            wp_enqueue_script('weseedo_site_js');
        }

        return $html;
    }

    /**
     * function for shortcode [weseedo_video]
     */
    public function showWeSeeDoVideo($atts)
    {
        $data = $this->getAccountData($atts['id']);
        if (!$data) return "";

        $session = $this->makeId();
        $account = $data['account'];

        if( ! wp_script_is( 'weseedo_video_js', 'enqueued' ) ) {
            wp_localize_script( 'weseedo_video_js', 'weseedo', array('session' => $session, 'account' => $account, ));
            wp_enqueue_script('weseedo_socketio_js');
            wp_enqueue_script('weseedo_video_js');
        }

        //$video = '<script type="text/javascript"></script>';

        $video = '<button type="button" onclick="weSeeDoVideo.startWeSeeDoCall();" id="startButton" style="display: none;" class="btn btn-success">Start video</button>
        <button type="button" onclick="weSeeDoVideo.endWeSeeDoCall();" id="endButton" style="display: none;" class="btn btn-danger">Be&euml;indigen</button>';

        $video .= ' <div id="video">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Video</h3>

                </div>
                <div class="panel-body">
                    <div id="video-container">
                        <div id="message-container" style="display: none;">
                            <div id="message-image" style="display: none;">1</div>
                            <span id="message"></span>
                        </div>
                        <div class="local-video-wrap">
                            <video id="localVideo" autoplay muted="true"></video>
                        </div>
                        <div id="remote-video-wrap" style="display: none;">
                            <video id="remoteVideo" autoplay></video>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        return $video;
    }

    /*
     * helper function to create random visitor id
     */
    private function makeId($length = 32) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /*
     * helper function to get account data from database
     */
    public function getAccountData($id) {
        global $wpdb;
        if (!$id) return "";

        $table_name = $wpdb->prefix . 'weseedo_screens';

        $data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            ),ARRAY_A );

        if ($data) {
            return $data;
        }



        return null;
    }

    public function setApi(WeSeeDoApi $api)
    {
        $this->api = $api;
    }

    public function getApi()
    {
        return $this->api;
    }

}