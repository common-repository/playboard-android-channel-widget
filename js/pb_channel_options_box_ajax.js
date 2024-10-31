jQuery(document).ready(function($) {

    $( "#pb_channel_box_example_text" ).keypress(function(e) {
        if (e.keyCode == 13) {
            pb_get_channel_shortcode_data();
            return false; // prevent the button click from happening
        }
    });

    $( "#pb_channel_box_num_apps" ).keypress(function(e) {
        if (e.keyCode == 13) {
            pb_get_channel_shortcode_data();
            return false; // prevent the button click from happening
        }
    });

    $('#pb_channel_box_generate_shortcode').click(pb_get_channel_shortcode_data);

    function pb_get_channel_shortcode_data(){
        $('#pb_channel_box_generate_shortcode').text('Loading..');
        var platform = $('#pb_channel_box_platform_select').val();
        var url = '';
        if (platform == 'android'){
            url = '//playboard.me/api/channels/search.json?q=' + $('#pb_channel_box_example_text').val() + '&num=1';
        }else if (platform == 'iphone'){
            url = '//playboard.me/api/ios/channels/search.json?platform=iphone&q=' + $('#pb_channel_box_example_text').val() + '&num=1'
        }else{
            url = '//playboard.me/api/ios/channels/search.json?platform=ipad&q=' + $('#pb_channel_box_example_text').val() + '&num=1';
        }
        var get_shortcode = {
            url: url,
            method:'get',
            dataType:'jsonp',
            success:function (data) {


                if (data && data.results && data.results.length > 0){
                    var shortcode = "[pb-channel-box channel_url='" + data.results[0].c_url + "' name='" + data.results[0].name + "' num_apps='" + $( "#pb_channel_box_num_apps").val() + "']";
                    $('#pb_channel_box_shortcode_textarea').text(shortcode);
                }else{
                    $('#pb_channel_box_shortcode_textarea').text('Shortcode generation failed, please go to http://playboard.me/widgets?tab=channel to get a shortcode');
                }
                $('#pb_channel_box_generate_shortcode').text('Generate');
            },
            error:function (xhr, ajaxOptions, thrownError) {
                $('#pb_channel_box_shortcode_textarea').text('Shortcode generation failed, please go to http://playboard.me/widgets?tab=channel to get a shortcode');
            }
        };
        $.ajax(get_shortcode);
    }


});
