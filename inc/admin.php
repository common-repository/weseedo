<?php

class WeSeeDoAdmin
{
    // reference to api
    private $api;

    public function __construct()
    {
        if(is_admin()){
            add_action( 'admin_menu', array($this,'register_weseedo_menu') );
            add_action('admin_menu', array($this,'weseedo_admin_actions'));
            add_action( 'admin_enqueue_scripts', 'weseedo_register_plugin_admin_style' );
        }

    }

    public function register_weseedo_menu() {
        add_menu_page(
            __( 'WeSeeDo videoscreen options', 'textdomain' ),
            'WeSeeDo',
            'manage_options',
            'wesedoo_options',
            array($this,'weseedo_page'),
            'dashicons-video-alt2', // icon,
            50
        );
    }

    public function weseedo_page() {

        //global $weseedoApi;
        global $wpdb;

        if (!current_user_can('manage_options')) {
            wp_die('Sorry, but you do not have permissions to change settings.');
        }

        /* Make sure post was from this page */
        if (count($_POST) > 0) {
            check_admin_referer('weseedo_screen_options');
        }

        $table_name = $wpdb->prefix . 'weseedo_screens';
        $id = 1;

        $data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $id
            ),ARRAY_A );


        if (isset($_POST['update_screen'])) {

            // clear raw data
            $postTitle = sanitize_text_field((string)$_POST['title']);
            $postAccount = sanitize_text_field((string)$_POST['account']);
            $postPage = sanitize_text_field((int)$_POST['page']);
            $postScreenType = sanitize_text_field((string)$_POST['screen_type']);
            $postScreenWidth = sanitize_text_field((int)$_POST['screen_with']);
            $postScreenHeight = sanitize_text_field((int)$_POST['screen_height']);

            // override $data with the new values
            $data = array(
                'title' => $postTitle,
                'account' => $postAccount,
                'page' => $postPage,
                'screen_type' => $postScreenType,
                'screen_width' => $postScreenWidth,
                'screen_height' => $postScreenHeight
            );

            // save new values in database
            $wpdb->update(
                $table_name,
                $data,
                array('id' => $id)
            );

            echo '<div id="message" class="updated fade"><p>'
                . 'Options saved'
                . '</p></div>';
        }

        if (strlen(get_option('weseedo_api_client')) == 0 || strlen(get_option('weseedo_api_client')) == 0) {
            echo '<div class="notice notice-error"><p>'
                . 'Please check your <a href="options-general.php?page=weseedo">settings</a>. First you need to request API keys. <a href="https://www.weseedo.nl/register" target="_black">Click here</a> to get all required information.'
                . '</p></div>';
        }


        ?>
        <div class="wrap">

            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

            <table width="100%" cellpadding="5" cellspacing="5" border="0" class="feature-filter">
                <tr>
                    <td width="50%" valign="top">
                        <form method="post" action="admin.php?page=wesedoo_options">
                            <?php wp_nonce_field('weseedo_screen_options'); ?>

                            <table width="100%" cellpadding="5" cellspacing="5" border="0">
                                <tr><td><strong>Title</strong></td><td><input name="title" type="input" value="<?php echo esc_html($data['title']); ?>"></td></tr>
                                <tr><td><strong>Site:</strong></td><td>

                                        <select name="account">
                                            <?php
                                            $accounts = $this->getApi()->getAccounts();

                                            if ($accounts) {
                                                foreach ($accounts as $account) {
                                                    $selected = ($account->id == $data['account']) ? 'selected' : '';
                                                    echo '<option value="' . esc_html($account->id) . '" '.$selected.'>' . esc_html($account->name) . "</option>\n";
                                                }
                                            } else {
                                                echo '<option value="">No accounts available, check your settings</option>'."\n";
                                            }
                                            ?>
                                        </select></td></tr>
                                <tr><td><strong>Video page</strong></td><td><?php wp_dropdown_pages(array('selected' => esc_html($data['page']),'name' => 'page')); ?></td></tr>
                                <tr><td valign="top"><strong>Video size</strong></td><td>
                                        <select class="yt-uix-form-input-select-element " id="size" name="screen_type" onchange="updateSize()">
                                            <option value="default" <?php echo (esc_html($data['screen_type']) == "default" || !$data['screen_type']) ? "selected": ""; ?>  data-width="560" data-height="315">560 × 315</option>
                                            <option value="medium" <?php echo (esc_html($data['screen_type']) == "medium") ? "selected": ""; ?> data-width="640" data-height="360">640 × 360</option>
                                            <option value="large" <?php echo (esc_html($data['screen_type']) == "large") ? "selected": ""; ?> data-width="853" data-height="480">853 × 480</option>
                                            <option value="hd720" <?php echo (esc_html($data['screen_type']) == "hd720") ? "selected": ""; ?> data-width="1280" data-height="720">1280 × 720</option>
                                            <option value="custom" <?php echo (esc_html($data['screen_type']) == "custom") ? "selected": ""; ?>> Custom</option>
                                        </select><br>


                                        <span id="size-input" style="display: none;">
          <input type="text" name="screen_width" class="screen_width" value="<?php echo (esc_html($data['screen_width'])) ? esc_html($data['screen_width']) : "560"; ?>" maxlength="4">
          ×
          <input type="text" name="screen_height" class="screen_height" value="<?php echo (esc_html($data['screen_height'])) ? esc_html($data['screen_height']) : "315"; ?>" maxlength="4">
        </span>
                                        <script type="text/javascript">
                                            function updateSize() {
                                                var size = jQuery("select#size").find(':selected');
                                                if (size.val() == "custom") {
                                                    jQuery("#size-input").show();
                                                } else {
                                                    jQuery("#size-input").hide();
                                                    jQuery(".screen_width").val(size.data('width'));
                                                    jQuery(".screen_height").val(size.data('height'));
                                                }

                                            }
                                            updateSize();

                                        </script>


                                    </td></tr>

                                <tr><td colspan="2">
                                        <p class="submit">
                                            <?php
                                            $disabled = (strlen(get_option('weseedo_api_client')) == 0 || strlen(get_option('weseedo_api_client')) == 0) ? 'disabled' : '';
                                            echo '<input name="update_screen" class="button button-primary" '.$disabled.' value="Save" type="submit">';

                                            ?>
                                        </p>
                                    </td></tr>
                            </table>
                        </form>
                    </td>
                    <td width="50%" valign="top">
                        <?php if(array_key_exists('title',$data) && strlen(esc_html($data['title'])) > 0): ?>
                            Paste this shortcode inside your text. The start button will be shown on your website or shop.<br>
                            <input type="text" onfocus="this.select();" readonly="readonly" value="[weseedo_button id=&quot;1&quot; title=&quot;<?php echo esc_html($data['title']);?>&quot;] start video [/weseedo_button]" class="input-shortcode">
                            <br><br>

                            Paste this shortcode on the webpage where the video conversation takes place.<br>
                            <input type="text" onfocus="this.select();" readonly="readonly" value="[weseedo_video id=&quot;1&quot; title=&quot;<?php echo esc_html($data['title']);?>&quot;]" class="input-shortcode">
                            <br><br>

                            <b>Status:</b><br>
                            Agent(s) online:<br>
                            <textarea onfocus="this.select();" readonly="readonly" cols="70" rows="4">
[weseedo_status id="1" title=&quot;<?php echo esc_html($data['title']);?>&quot; status="online"]
Post your text here if your agent is online. The text will be displayed in the startbutton. For example: Live video contact? Click here!
[/weseedo_status]
        </textarea><br><br>

                            Agent(s) offline:<br>
                            <textarea onfocus="this.select();" readonly="readonly" cols="70" rows="4">
[weseedo_status id="1" title=&quot;<?php echo esc_html($data['title']);?>&quot; status="offline"]
Post your text here if your agent is offline. For example: show offline message or a contact form.
[/weseedo_status]
        </textarea>
                            <br><br>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    public function weseedo_admin_actions() {
        add_options_page(
            'Settings WeSeeDo',
            'WeSeeDo',
            'manage_options',
            'weseedo',
            array($this,'weseedo_admin_options'));
    }

    public function weseedo_admin_options() {

        if (!current_user_can('manage_options')) {
            wp_die('Sorry, but you do not have permissions to change settings.');
        }

        /* Make sure post was from this page */
        if (count($_POST) > 0) {
            check_admin_referer('weseedo_admin_options');
        }

        if (isset($_POST['update_site_options'])) {

            $postClient = sanitize_text_field((string)$_POST['api_client']);
            $postSecret = sanitize_text_field((string)$_POST['api_secret']);

            update_site_option('weseedo_api_client',esc_html($postClient));
            update_site_option('weseedo_api_secret',esc_html($postSecret));


            echo '<div id="message" class="updated fade"><p>'
                . 'Settings saved'
                . '</p></div>';
        }

        echo '<div class="wrap">';
        ?>

        <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

        <div id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    You need API keys to activate your account. <a href="https://www.weseedo.nl/register" target="_black">Click here</a> to get all required information.

                    <form method="post" action="options-general.php?page=weseedo">
                        <?php wp_nonce_field('weseedo_admin_options'); ?>


                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php _e( 'API client Id:', 'weseedo' ); ?></th>
                                <td>
                                    <input name="api_client" type="text" id="api_client" value="<?php echo get_option('weseedo_api_client'); ?>" class="regular-text"><br>

                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row"><?php _e( 'API client Secret:', 'weseedo' ); ?></th>
                                <td>
                                    <input name="api_secret" type="text" id="api_secret" value="<?php echo get_option('weseedo_api_secret'); ?>" class="regular-text"><br>

                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <input name="update_site_options" class="button button-primary"  value="Save settings" type="submit">
                        </p>
                    </form>
                </div> <!-- end post-body-content -->
            </div> <!-- end post-body -->
        </div> <!-- end poststuff -->


        </div>
        <?php

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