<?php

/*
Plugin Name: Playboard Android, iPhone and iPad Channel Widget
Plugin URI: http://playboard.me/widgets
Description: Create channels of Android, iPhone or iPad apps and games, write reviews about them and let your visitors see them on your website in a beautiful widget. Supports shortcodes for embedding in blog posts.
Version: 4.0.2
Author: Playboard team
Author URI: http://playboard.me
*/
define( 'PB_PLUGIN_PATH', plugin_dir_path(__FILE__) );

class PlayboardChannelWidget extends WP_Widget
{


    static $add_script = TRUE;

    /**
     * Register widget with WordPress.
     */
    public function __construct() {

        $self = parent::WP_Widget(false, $name = 'PlayboardChannelWidget');

        $widget_ops = array('description' => __('Display a Playboard Channel'), 'classname' => 'playboard_channel_widget');
        $this->WP_Widget('playboard_channel_widget', __('Playboard Channel Widget'), $widget_ops);

        if (!is_admin())
        {

            wp_enqueue_script('jquery');
            wp_register_script('pb-js', '//playboard.me/widgets/pb-channel-box/1/pb_load_channel_box_wp.js', array('jquery'), null, false);
            wp_enqueue_script('pb-js');

        }

    }

    function widget($args, $instance)
    {

        if (isset($instance['error']) && $instance['error'])
            return;

        extract($args);

        $ref = $instance['channel_id'];
        $channel_id = NULL;
        //parse the url
        if (strpos($ref, 'http://') === 0 || strpos($ref, 'https://') === 0) {
            $url = $ref;
        }else{
            //backwards compatibility
            $url = 'http://playboard.me/android/channels/' . $ref;
        }

        $channel_platform = 'android';
        $parsed_url = parse_url($url);
        $tokens = explode('/', $parsed_url['path']);
        $count = count($tokens);
        for($i = 0; $i < $count; ++$i){
            $token = $tokens[$i];
            if ($token == 'channels' && $i > 0){
                $channel_platform = $tokens[$i - 1];
                break;
            }
        }

        $human_readable_platform = 'Android';
        if ($channel_platform == 'iphone'){
            $human_readable_platform = 'iPhone';
        }else if ($channel_platform == 'ipad'){
            $human_readable_platform = 'iPad';
        }


        $width = $instance['width'];
        if (!is_numeric($width)){
            $width = "";
        }else if (intval($width) < 240) {
            $width = 240;
        }else{
            $width = intval($width);
        }

        $num_apps = $instance['num_apps'];
        if (!is_numeric($num_apps)){
            $num_apps = 10;
        }else{
            $num_apps = intval($num_apps);
        }

        if (!empty($width)){
            echo '<span style="width: ' . $width . 'px !important" data-width="' . $width . '" data-apps="' . $num_apps .'" class="pb-channel-box"><a href="' . $url . '">Playboard Channel on ' . $human_readable_platform . '</a></span>';
        }else{
            echo '<span class="pb-channel-box" data-apps="' . $num_apps . '"><a href="' . $url . '">Playboard Channel on ' . $human_readable_platform . '</a></span>';
        }

    }

    function form($instance)
    {

        $width = esc_attr($instance['width']);

        if (!is_numeric($width)){
            $width = "";
        }else if (intval($width) < 240) {
            $width = 240;
        }else{
            $width = intval($width);
        }


        $channel_id = esc_attr($instance['channel_id']);

        $num_apps = esc_attr($instance['num_apps']);
        if (!is_numeric($num_apps)){
            $num_apps = 10;
        }else{
            $num_apps = intval($num_apps);
        }

        ?>

        <p><label for="<?php echo $this->get_field_id('how_to'); ?>"><?php _e('How To Use:'); ?></label>
        <ol>
            <li>
                Go to <a href="http://playboard.me/" target="_blank">http://playboard.me</a>
            </li>
            <li>
                Find or create a channel
            </li>
            <li>
                Copy the channel URL or channel ID
            </li>

        </ol>
        </p>

        <p><label for="<?php echo $this->get_field_id('channel_id'); ?>"><?php _e('Playboard Channel URL:'); ?></label>
            <input id="<?php echo $this->get_field_id('channel_id'); ?>" name="<?php echo $this->get_field_name('channel_id'); ?>"
                   type="text" value="<?php echo $channel_id; ?>"/></p>

        <p><label for="<?php echo $this->get_field_id('num_apps'); ?>"><?php _e('Number of Apps:'); ?></label>
            <input id="<?php echo $this->get_field_id('num_apps'); ?>" name="<?php echo $this->get_field_name('num_apps'); ?>"
                   type="text" value="<?php echo $num_apps; ?>"/></p>

        <p><label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Widget Width (in px, empty for fill parent):'); ?></label>
            <input id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>"
                   type="text" value="<?php echo $width; ?>"/></p>


    <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance)
    {
        $instance = $old_instance;

        $instance['width'] = strip_tags($new_instance['width']);
        $instance['num_apps'] = strip_tags($new_instance['num_apps']);
        $instance['channel_id'] = strip_tags($new_instance['channel_id']);

        return $instance;
    }

}

class PlayboardChannelShortcode {

    static $add_pb_script;

    static function init() {

        add_shortcode('pb-channel-box', array(__CLASS__, 'add_pb_channel'));
        add_action('admin_menu', array(__CLASS__, 'pb_channel_options_box'));
        add_action( 'admin_enqueue_scripts',  array(__CLASS__, 'pb_channel_options_box_ajax'));

        add_action('init', array(__CLASS__, 'register_script'));
        add_action('wp_footer', array(__CLASS__, 'print_script'));
    }

    function pb_channel_options_box_ajax($hook) {

        wp_enqueue_script( 'ajax-script-pb-channel-box', plugins_url( '/js/pb_channel_options_box_ajax.js', __FILE__ ), array('jquery'));

    }

    static function add_pb_channel($atts) {

        self::$add_pb_script = true;

        extract(shortcode_atts(array('channel_url' => '', 'name' => '', 'num_apps' => 10), $atts));

        if ($channel_url == ''){
            return '';
        }else{

            $channel_platform = 'android';
            $parsed_url = parse_url($channel_url);
            $tokens = explode('/', $parsed_url['path']);
            $count = count($tokens);
            for($i = 0; $i < $count; ++$i){
                $token = $tokens[$i];
                if ($token == 'channels' && $i > 0){
                    $channel_platform = $tokens[$i - 1];
                    break;
                }
            }

            $human_readable_platform = 'Android';
            if ($channel_platform == 'iphone'){
                $human_readable_platform = 'iPhone';
            }else if ($channel_platform == 'ipad'){
                $human_readable_platform = 'iPad';
            }

            if ($name == ''){
                $name = $human_readable_platform . ' Channel on Playboard';
            }else{
                $name .= ' ' . $human_readable_platform . ' Channel on Playboard';
            }


            return '<div class="pb-channel-box" data-apps="' . $num_apps . '"><a href="' . $channel_url .'">' . $name . '</a></div>';

        }
    }

    static function pb_channel_options_box() {
        add_meta_box('pb_channel_options_box', 'Playboard Channel Widget for Android, iPhone and iPad', array(__CLASS__, 'pb_channel_options_box_display'), 'post', 'side', 'high');
    }

    static function pb_channel_options_box_display() {

        $example = "Best Free Games";

        echo '<p><label><strong>How to use the Playboard Channel Widget:</strong></label><br>
        <hr>
         <ul>
           <li>
               <label for="channel_text">1. Search for a channel or give the channel ID:</label>
               <div>
               <input id="pb_channel_box_example_text" type="text" name="channel_text" value="' . $example . '" style="width:100%"></input>
               <label for="pb_channel_box_platform_select">2. Choose a platform&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
               <select id="pb_channel_box_platform_select" name="pb_channel_box_platform_select" style="width:40%">
                  <option value="android">Android</option>
                  <option value="iphone">iPhone</option>
                  <option value="ipad">iPad</option>
                </select><br/>
                <label for="pb_channel_box_platform_select">3. Choose number of apps&nbsp;&nbsp;&nbsp;</label>
               <input id="pb_channel_box_num_apps" type="text" name="pb_channel_box_num_apps" value="10" style="width:30%"></input></br>
               4. <a href="javascript:;" class="button button-large" id="pb_channel_box_generate_shortcode" name="ajax_get_shortcode_channel">Generate</a>
               </div><br/>
               <label for="shortcode">5. Embed this shortcode in your post:</label>
               <textarea id="pb_channel_box_shortcode_textarea" name="shortcode" style="width:100%" rows="4" disabled></textarea>
           </li>
        </ul>

       Need more widgets or even a custom one? Visit <a href="http://playboard.me/widgets" target="_blank">http://playboard.me/widgets</a> or contact us at <a href="mailto:feedback@playboard.me">feedback@playboard.me</a>' ;

    }


    static function register_script() {
        wp_register_script('pb-channel-box-js','//playboard.me/widgets/pb-channel-box/1/pb_load_channel_box_wp.js', array('jquery'), null, false);
    }

    static function print_script() {

        if (self::$add_pb_script ){
            wp_print_scripts('pb-channel-box-js');
        }

    }
}

add_action('widgets_init', create_function('', 'return register_widget("PlayboardChannelWidget");'));
PlayboardChannelShortcode::init();
