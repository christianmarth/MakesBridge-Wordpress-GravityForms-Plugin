<?php
/*
  Plugin Name: MakesBridge
  Description: MakesBridge plugin for WordPress
  Author: cloudgroup
  Version: 1.03.7
  Licence: GPL2
 */


/*
 * MakesBridge Gravity Forms Integration
 */

if (class_exists("RGForms")) {
    require_once 'makesbridgegravity.php';
    add_action('init', array('GFMakesBridge', 'init'));
}




$options = get_option('makesbridge_options');
require_once 'mksapi.php';
function makesbridge($user_id) {


    echo "First Name :" . $user_id->data->first_name;

    echo "Last Name :" . $user_id->data->last_name;

    echo "Email :" . $user_id->data->user_email;
    $token = makesbridge_login();
    makesbridge_get_lists($token);
    makesbridge_add_subscribers($token, $user_id);
}

//Add the MakesBridge Menu Page
add_action('admin_menu', 'MakesBridge_Menu');

function MakesBridge_Menu() {


    add_menu_page("BridgeMail System", "MakesBridge", "manage_options", "makesbridge", "MKS_plugin_options_page");
    add_submenu_page('makesbridge', 'Manage MakesBridge Settings', 'Settings', 'manage_options', 'makesbridge', 'MKS_plugin_options_page');
//    add_submenu_page('makesbridge', 'Manage Options', 'Manage Campaigns', 'manage_options', 'mks_campaigns', array('makesbridgeCampaigns', 'mks_campaigns'));
//    add_submenu_page('makesbridge', 'Mange Campaigns', 'Manage MakesBridge Campaigns', 'manage_options', 'MKS_plugin_options_page');
if (class_exists("RGForms")) {
    add_submenu_page('makesbridge', 'Gravity Settings', 'GravityForms Settings', 'manage_options', 'mks_gf', array('GFMakesBridge', 'mks_gf_options'));
}
//	add_submenu_page( 'gprojects', 'Manage Categories', 'Manage Categories', 'manage_options', 'gprojects_cats', 'gprojects_cats_page');
//	add_submenu_page( 'gprojects', 'G-Projects Options', 'Options', 'manage_options', 'gprojects_options', 'gprojectsOptions');
//	add_submenu_page( 'gprojects', 'Uninstall G-Projects', 'Uninstall', 'manage_options', 'gprojects_uninstall', 'uninstall_gprojects');
// Add the admin options page
//add_menu_page( 'MakesBridge', 'BridgeMail',6);
//add_menu_page("Test", "tesT", 6, 'MKS_plugin_options_page');
}

//add_action('admin_menu', 'MKS_plugin_admin_add_page');    
//add_action('init', 'mks_handle_request');
//
function mks_handle_request() {
    if (isset($_POST['mks_action'])) {
        switch ($_POST['mks_action']) {
            case 'api_settings';
                verify_api();
                break;
        }
    }
}

// display the admin options page
function MKS_plugin_options_page() {
    ?>
    <div>
        <h2>MakesBridge Plugin Settings</h2>
        <p>
            <em>Don't have a MakesBridge Account?</em> <a href="http://makesbridge.com/request-15-day-trial?pmc=CLDGRP" target="_BLANK">Sign up for a free trial here</a>
        </p>
        <form action="options.php" method="post">
            <?php settings_fields('makesbridge_options');
            do_settings_sections('MakesBridge'); ?>
            <input type="hidden" name="mks_action" value="api_settings" />
            <input name="Submit" type="submit" value="Save Changes" class="button-primary"/>
        </form>
    </div>
    <?php
}

// add the admin settings and such
add_action('admin_init', 'MKS_plugin_admin_init');

function MKS_plugin_admin_init() {
    register_setting('makesbridge_options', 'makesbridge_options', 'MKS_options_validate');
    add_settings_section('makesbridge', 'MakesBridge Login Settings', 'MKS_plugin_section_text', 'MakesBridge');
    add_settings_field('MKS_UserId', 'MakesBridge Username', 'MKS_plugin_setting_MKS_UserId', 'MakesBridge', 'makesbridge');
    add_settings_field('MKS_API_Token', 'MakesBridge API Access Token', 'MKS_plugin_setting_MKS_API_Token', 'MakesBridge', 'makesbridge');
    add_settings_field('MKS_tracking_snippet', 'MakesBridge Tracking Snippet', 'MKS_plugin_setting_MKS_Tracking', 'MakesBridge', 'makesbridge');
//    add_settings_field('MKS_List', 'MakesBridge List', 'MKS_plugin_setting_MKS_List', 'MakesBridge', 'makesbridge');
}

function MKS_plugin_setting_MKS_UserId() {
    $options = get_option('makesbridge_options');
    echo "<input id='MKS_UserId' name='makesbridge_options[MKS_UserId]' size='40' type='text' value='{$options['MKS_UserId']}' />";
}

function MKS_plugin_setting_MKS_API_Token() {
    $options = get_option('makesbridge_options');
    echo "<input id='MKS_UserId' name='makesbridge_options[MKS_API_Token]' size='40' type='text' value='{$options['MKS_API_Token']}' />";
}

function MKS_plugin_setting_MKS_List() {
    $options = get_option('makesbridge_options');
    if (isset($options['MKS_API_Token'])) {
        $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);
//        $api->newLogin();
        $api->login();
        $lists = $api->retrieveLists();
        foreach ($lists->attributes() as $attr) {
            echo $attr;
        };
        echo "<select id='MKS_List' name='makesbridge_options[MKS_List]' value='{$options['MKS_List']}'>";
        foreach ($lists->List as $list) {
            if (isset($list->name)) {
                echo "<option id='$list->name' value='$list->name' ";
                if ($list->name == $options['MKS_List']) {
                    echo "selected='selected'";
                }
                echo " >$list->name ($list->subscribedCount)</option>";
            }
            else
                echo '<option>Please Ensure your MKS Creds are correct</option>';
        }
        echo "</select>";
    }
}

function MKS_plugin_setting_MKS_Tracking() {
    $options = get_option('makesbridge_options');
    echo "<textarea id='MKS_tracking_snippet' name='makesbridge_options[MKS_tracking_snippet]' rows='3' cols='20' >";
    echo ($options['MKS_tracking_snippet'] == '') ? '' : $options['MKS_tracking_snippet'];
    echo "</textarea>";
}

//Add Tracking Snippet to blog </head>

if ($options['MKS_tracking_snippet'] !== '') {
    add_action('wp_head', 'mks_insert_tracking');

    function mks_insert_tracking() {
        $options = get_option('makesbridge_options');
        echo $options['MKS_tracking_snippet'];
    }

}

// validate our options
function MKS_options_validate($input) {
//    $newinput['text_string'] = trim($input['text_string']);
//    if (!preg_match('/^[a-z0-9]{32}$/i', $newinput['text_string'])) {
//        $newinput['text_string'] = '';
//    }
    return $input;
}

function MKS_plugin_section_text() {
    echo '<p>Please enter your MakesBridge login credentials here.</p>';
}

//add_action("user_register", "makesbridge");

function verify_api() {
    $options = get_option('makesbridge_options');
    $mks_uid = $options['MKS_UserId'];
    $mks_api = $options['MKS_API_Token'];
    $api = new mksapi($mks_uid, $mks_api);
    $api->testSettings();
}

//add_action('init', 'MKS_subscribe');


function MKS_subscribe() {
    if (isset($_POST['name'])) {
        $api = new mksapi($mks_uid, $mks_api);
        $api->login();
        $api->createSubscriber($data);
        return;
    }

//    print_r($_POST);
}

class Makesbridge_widget extends WP_Widget {

    function Makesbridge_widget() {
        $options = get_option('makesbridge_options');
        $mks_uid = $options['MKS_UserId'];
        $mks_api = $options['MKS_API_Token'];
//        $name = $_POST['firstname'];
//        $email = $_POST['custemail'];
//        $data = array(
//            'name' => $_POST['name'],
//            'email' => $_POST['email']
//        );
    }

    function widget() {
        $url = get_permalink(); // . '#MKS_subscribe';// . '#MKS_subscribe';
        ?>
        <div id="mks_signup">
            <form method="post" action="#mks_signup" >
                Name <input type="text" name="firstname"/>
                Email <input type="text" name="custemail"/>
                <input type="submit" name="submit"/>
            </form>
        </div>
        <?php
    }

}

function dashboard_widget() {
//    echo 'hello world';
}

function add_dashboard() {
//    wp_add_dashboard_widget('mks_dashboard_widget', 'BridgeMail System', 'dashboard_widget');
}

/*
 * Class for MakesBridge Campaigns
 * 
 */

class makesbridgeCampaigns {

    function mks_campaigns() {
        $options = get_option('makesbridge_options');
        $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);
//        $api->newLogin();
        $api->login();
        $campaign = $api->getCampaignInfo('1');
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Campaign Name</th>
                    <th>Created Date</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>Campaign Name</th>
                    <th>Created Date</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </tfoot>
            <tbody><?
        foreach ($campaign as $camp) {
            echo '<tr>';
            echo '<td><a href="' . $camp->id . '">' . $camp->name . '</a></td>';
            echo '<td>' . $camp->creationDate . '</td>';
            echo '<td>' . $camp->type . '</td>';
            echo '<td>' . $camp->status . '</td>';
            echo '</tr>';
        }
        ?>
            </tbody>
        </table>
        <?
    }

}

//add_action('wp_dashboard_setup', 'add_dashboard');
//add_action('widgets_init', create_function('', 'return register_widget("Makesbridge_widget");'));
