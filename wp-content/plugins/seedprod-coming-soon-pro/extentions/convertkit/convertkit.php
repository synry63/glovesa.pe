<?php
//  * Copyright 2014 SEEDPROD LLC (email : john@seedprod.com, twitter : @seedprod)

/**
 *  Add convertkit section
 */
$seed_cspv4 = get_option('seed_cspv4');
if($seed_cspv4['emaillist'] == 'convertkit'){
    add_filter('seedredux/options/seed_cspv4/sections', 'seed_cspv4_convertkit_section');
}

function seed_cspv4_convertkit_section($sections) {

	global $seed_cspv4;
	//var_dump($seed_cspv4['emaillist']);
    //$sections = array();
    $sections[] = array(
        'title' => __('ConvertKit', 'seedprod'),
        'desc' => __('<p class="description">Configure saving subscribers to convertkit options. Save after you enter your api key to load your list. <a href="#">Learn More</a></p>', 'seedprod'),
        'icon' => 'el-icon-envelope',
        // Leave this as a blank section, no options just some intro text set above.
        'fields' => array(
                array(
                    'id'        => 'convertkit_api_key',
                    'type'      => 'text',
                    'title'     => __( "API Key", 'seedprod' ),
                    'subtitle'  => __('Enter your API Key. <a target="_blank" href="https://app.convertkit.com/account/edit" target="_blank">Get your API key</a>', 'seedprod'),
                ),
                array(
                    'id'        => 'convertkit_listid',
                    'type'      => 'select',
                    'title'     => __( "Courses", 'seedprod' ),
                    'options'   => cspv4_get_convertkit_lists()
                ),
                array(
                    'id'        => 'refresh_convertkit',
                    'type'      => 'checkbox',
                    'title'     => __( "Refresh ConvertKit Lists", 'seedprod' ),
                    'subtitle'  => __('Check and Save changes to have the lists refreshed above.', 'seedprod'),
                ),


        	)
    );

    return $sections;
}



/**
 *  Get List from convertkit
 */
function cspv4_get_convertkit_lists($apikey = null){

    if(isset($_REQUEST['page']) && $_REQUEST['page'] == 'seed_cspv4_options'){
    global $seed_cspv4;
    extract($seed_cspv4);
    $o = $seed_cspv4;
    $lists = array();
    try{
        if($o['emaillist'] == 'convertkit' || ( defined('DOING_AJAX') && DOING_AJAX && isset($_GET['action']) && $_GET['action'] == 'seed_cspv4_refresh_list')){
        $lists = maybe_unserialize(get_transient('seed_cspv4_convertkit_lists'));
        if(empty($lists)){
            //var_dump('miss');
            require_once SEED_CSPV4_PLUGIN_PATH.'extentions/convertkit/seed_cspv4_convertkit.class.php';

            if(!isset($apikey) && isset($convertkit_api_key)){
                $apikey = $convertkit_api_key;
            }

            if(empty($apikey)){
                return array();
            }

            $api = new seed_cspv4_ConvertKitAPI($apikey);
            $response = $api->get_resources('courses');

            if(!empty($response)){
                foreach ($response as $k => $v){
                    $lists[$v['id']] = $v['name'];
                }
                if(!empty($lists)){
                   set_transient('seed_cspv4_convertkit_lists',serialize( $lists ),86400 * 30);
                }
            }else{
                $lists['false'] = __("Unable to load ConvertKit Courses, check your API Key.", 'seedprod');
            }

        }}
    } catch (Exception $e) {}
    return $lists;
}}


/**
 *  Subscribe convertkit
 */
add_action('seed_cspv4_emaillist_convertkit', 'seed_cspv4_emaillist_convertkit_add_subscriber');

function seed_cspv4_emaillist_convertkit_add_subscriber($args){
    global $seed_cspv4,$seed_cspv4_post_result;
    extract($seed_cspv4);
        require_once SEED_CSPV4_PLUGIN_PATH.'extentions/convertkit/seed_cspv4_convertkit.class.php';
        require_once( SEED_CSPV4_PLUGIN_PATH.'lib/nameparse.php' );

                // If tracking enabled
                if(!empty($enable_reflink)){
                    seed_cspv4_emaillist_database_add_subscriber();
                }

                $apikey = $convertkit_api_key;
                $api = new seed_cspv4_ConvertKitAPI($apikey);
                $listId = $convertkit_listid;


                $name = '';
                if(!empty($_REQUEST['name'])){
                    $name = $_REQUEST['name'];
                }
                $email = $_REQUEST['email'];
                $fname = '';
                $lname = '';

                if(!empty($name)){
                    $name = seed_cspv4_parse_name($name);
                    $fname = $name['first'];
                    $lname = $name['last'];
                }


                $api = new seed_cspv4_ConvertKitAPI($apikey);
                $options = array('email'=>$email,'fname'=>$fname);
                $response = $api->course_subscribe($listId, $options);


                if(empty($response)){

                        $seed_cspv4_post_result['msg'] = 'There was an issue adding your email.';
                        $seed_cspv4_post_result['msg_class'] = 'alert-info';

                }else {

                    if($seed_cspv4_post_result['status'] == '600')
                        $seed_cspv4_post_result['status'] ='200';

                    if(empty($seed_cspv4_post_result['status']))
                        $seed_cspv4_post_result['status'] ='200';

                }

}

// Hook into save

add_action('seedredux/options/seed_cspv4/saved',  'seed_csvp4_refresh_convertkit_lists' );

function seed_csvp4_refresh_convertkit_lists($value){
    if(!empty($value['refresh_convertkit']) && $value['refresh_convertkit'] == '1'){
        //Clear cache
        delete_transient('seed_cspv4_convertkit_lists');
        cspv4_get_convertkit_lists();
        // Reset Field
        // Set code field
        global $seed_cspv4_seedreduxConfig;
        $seed_cspv4_seedreduxConfig->SeedReduxFramework->set('refresh_convertkit', 0);
    }

}
