<?php
/*
  Plugin Name: MakesBridge
  Description: MakesBridge plugin for WordPress
  Author: Christian Marth
<<<<<<< HEAD
  Version: 1.01
  Licence: GPL2
 */

//SVN

/*
 * MakesBridge Gravity Forms Integration
 */

add_action('init', array('GFMakesBridge', 'init'));

class GFMakesBridge {

    private static $slug = 'gravitymakesbridge';
    private static $version = '0.1';
    private static $url = 'http://makesbridge.com/';

    //Initialise the plugin
    public function init() {
        add_action("gform_after_submission", array('GFMakesBridge', "mks_gf_submission"), 10, 2);
        add_action('wp_ajax_mks_gf_form', array('GFMakesBridge', 'mks_gf_form'));
        add_action('wp_ajax_mks_gf_update', array('GFMakesBridge', 'updateSettings'));
        add_action('wp_ajax_mks_gf_retrieve', array('GFMakesBridge', 'retrieveGravitySettings'));
        add_action('wp_ajax_mks_gf_delete', array('GFMakesBridge', 'deleteGravitySettings'));
        self::configure();
    }

    //Ajax function to update MakesBridge Settings
    public static function updateSettings() {
        $setting = $_POST['settings'];
        $formId = $_POST['formId'];
        $listId = $_POST['listId'];
        if (isset($_POST['id'])) {
            $id = $_POST['id'];
        } else {
            $id = '0';
        };
        $id = GFMakesBridgeData::updateFormSettings($id, $formId, $listId, $setting);
        $res = new WP_Ajax_Response(array(
                    'data' => $id
                ));
        $res->send();
        return($res);
    }

    public function admin_init() {
        
    }

    public static function retrieveGravitySettings() {
        $id = $_POST['id'];
        $settings = GFMakesBridgeData::retrieveFormSettings($id);
        $settings = json_encode($settings);

        $res = new WP_Ajax_Response(array(
                    'data' => $settings
                ));
        $res->send();
        return($res);
    }

    public static function deleteGravitySettings() {
        $id = $_POST['id'];
        $res = GFMakesBridgeData::deleteFormSettings($id);
        return($res);
    }

    //Frontend Function to handle form submission
    public function mks_gf_submission($entry, $form) {
        $settings = GFMakesBridgeData::retrieveForm($form['id']);

        //Standard Fields
        foreach ($settings['meta']['standard'] as $key => $val) {
            $data['standard'][$val] = $entry[$key];
        };

        //Custom Fields
        if (is_array($settings['meta']['custom'])) {
            foreach ($settings['meta']['custom'] as $key => $val) {
                $data['custom'][$val] = $entry[$key];
            };
        }

        $options = get_option('makesbridge_options');
        $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);
        $api->login();
        $api->createSubscriber($data, $settings['list_id']);
        return(true);
    }

    function mks_gf_form() {
        $id = $_POST['id'];
        $form = RGFormsModel::get_form_meta($id);
        $options = get_option('makesbridge_options');
        // Get A list of MakesBridge Custom Fields
        $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);
        $api->login();
        $mksfields = $api->retrieveCustomFields();
        if (is_array($form["fields"])) {
            foreach ($form["fields"] as $field) {
                if (is_array(rgar($field, "inputs"))) {
                    //If this is an address field, add full name to the list
                    if (RGFormsModel::get_input_type($field) == "address")
                        $fields[] = array($field["id"], GFCommon::get_label($field));

                    foreach ($field["inputs"] as $input)
                        $fields[] = array($input["id"], GFCommon::get_label($field, $input["id"]));
                }
                else if (!rgar($field, "displayOnly")) {
                    $fields[] = array($field["id"], GFCommon::get_label($field));
                }
            }
        }

        $standardFields = array(
            'email',
            'firstName',
            'middleName',
            'lastName',
            'birthDate',
            'gender',
            'maritalStatus',
            'occupation',
            'householdIncome',
            'educationLevel',
            'addressLine1',
            'addressLine2',
            'city',
            'stateCode',
            'countryCode',
            'areaCode',
            'zip',
            'telephone',
            'industry',
            'company',
            'source',
            'salesRep',
            'salesStatus'
        );

        $data = '<h3><label>Gravity Fields</label>MakesBridge Fields</h3>';
        foreach ($fields as $field) {
            $data .= '<label>' . $field[1] . '</label>';
            $data .= "<select id=" . $field[0] . ">";
            $data .= "<option></option>";
            $data .= "<optgroup label='Standard Fields' title='standard'>";

            foreach ($standardFields as $standardField) {
                $data .= '<option>';
                $data .= $standardField;
                $data .= '</option>';
            };

            $data .= "</optgroup>";
            $data .= "<optgroup label='Custom Fields' title='custom'>";
            foreach ($mksfields as $mksfield) {
                if ($mksfield->userID == $options['MKS_UserId']) {
                    $data .= '<option>';
                    $data .= $mksfield->name;
                    $data .= '</option>';
                }
            };
            $data .= "</optgroup>";
            $data .= '<option>';
            $data .= '[New Custom Field]';
            $data .= '</option>';
            $data .= '</select><br/>';
        }

        $res = new WP_Ajax_Response(array(
                    'data' => $data
                ));
        $res->send();
    }

    /*
     * Our MakesBridge Gravity Forms Options Page
     */

    function mks_gf_options() {
        ?>
        <style>
            .mks_gf label{
                width: 200px;
                float: left;
            }
            h2{
                color: #333;
            }
        </style>
        <script type="text/javascript">
                                                                                                                                    
            /*  Javascript Function to configure our MakesBridge Forms
             *  
             *  Ajax request when Gravity Form Dropdown value changes
             *  to retrieve a list of gravity form fields and 
             *  MakesBridge Custom Fields
             */ 
            var ajaxUrl = '<?php bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php';
            jQuery(document).ready(function(){
                jQuery('#mks_gform').change(function(){
                    var id = (jQuery(this).val());
                    jQuery.post(ajaxUrl,{
                        action: 'mks_gf_form',
                        id: id
                    },function(res){
                        jQuery('#mks_gf_fields')
                        .html(jQuery(res).find('response_data').text());
                    })
                });
                                                                                                                                                                                                                                                        
                                                                                                                                                                                                                                                        
                /*   Add the option to define a new custom field on the fly
                 *   if the field makesbridge dropdown value is
                 *   [New Custom Field]
                 */  
                                                                                                                                                                                                                                                        
                jQuery('select').live('change',function(){
                    if ( jQuery( this ).val() == '[New Custom Field]' ){
                        field = "<em>Enter Field Name<input type='text' name=/></em>";
                        jQuery(this).after( field );
                    }
                })                                                  
                                                                                                                                                                                                                                                        
                /*  OnSubmit create a map of all the form settings
                 *   including a mapping of gravity form fields to
                 *   makesbridge and send to the database
                 */
                                                                                                                                                                                                                                                        
                jQuery('input[type="submit"]').live('click',function(){
                    formId = jQuery('#mks_gform').val();
                    mksList = jQuery('#mksList').val();
                    formName = jQuery('#mks_gform option:selected').text();
                    data = new Object();
                    data['list'] = mksList;
                    data['id'] = formId;
                    data['fields'] = new Object();
                    data['fields']['standard'] = new Object();
                    data['fields']['custom'] = new Object();
                    i = 0;
                                                                                                                                                                                                                                            
                    /*
                     * Loop through each select field value
                     */
                    jQuery('#mks_gf_fields select').each(function(){
                        fieldId = jQuery(this).attr('id');
                        fieldVal = jQuery(this).val();
                        if(fieldVal){
                            //Map the standard field
                            if((jQuery('[id="' + fieldId + '"] option:selected')
                            .parent('optgroup').attr('title')) == 'standard'){
                                data['fields']['standard'][fieldId] = fieldVal;
                                //Map the custom fields
                            }else{
                                data['fields']['custom'][fieldId] = fieldVal;
                            }
                        }
                    });

                    /*
                     * Send Ajax request to server
                     */
                                                                                                                                                          
                    if(typeof settId === 'undefined'){
                        settId = '0'
                    };
                    jQuery.post(ajaxUrl,{
                        action: 'mks_gf_update',
                        formId: formId,
                        listId: mksList,
                        id: settId,
                        settings: data['fields']
                    },function(res){
                        settId = jQuery(res).find('response_data').text();
                        jQuery('.mks_gf').after("<div class='updated fade below-h2'>" + settId +"</div>");
                        if(typeof settId === 'undefined'){
                            jQuery('#GF_Settings').append("<tr><td><a href=" + settId + " title='GF_Settings_Edit'>edit</a> | <a href=" + settId + " title='GF_Settings_Delete'>delete</a></td><td>" + formName + "</td><td>" + mksList + "</td></tr>")                            
                        } else {
                            jQuery('#GF_Settings a[href="' + settId + '"]').parents('tr').html("<td><a href=" + settId + " title='GF_Settings_Edit'>edit</a> | <a href=" + settId + " title='GF_Settings_Delete'>delete</a></td><td>" + formName + "</td><td>" + mksList + "</td>")                                                        
                        }
                    });
                })
                                                                                                                
                jQuery('#GF_Settings a[title="GF_Settings_Edit"]').live('click',function(e){
                    settId = jQuery(this).attr('href');
                    //                    console.log(settId)
                    e.preventDefault();
                                                                                            
                    jQuery.post(ajaxUrl,{
                        action: 'mks_gf_retrieve',
                        id: settId
                    },function(e){
                        json = (jQuery(e).find('response_data').text())
                        json = eval('(' + json + ')')
                        jQuery('#mks_gform').val(json.form_id)
                        jQuery('#mksList').val(json.list_id)
                                                                                                
                        jQuery.post(ajaxUrl,{
                            action: 'mks_gf_form',
                            id: json.form_id
                        },function(res){
                            jQuery('#mks_gf_fields')
                            .html(jQuery(res).find('response_data').text());
                            jQuery('#mks_gf_fields select').each(function(){
                                var id = jQuery(this).attr('id');
                                if(typeof json.meta.standard[id] === 'undefined'){
                                    val = json.meta.custom[id];
                                } else {
                                    val = json.meta.standard[id];
                                };
                                jQuery(this).val(val)
                            })
                        })
                    })
                })
                                                                                        
                                                                                        
                jQuery('#GF_Settings a[title="GF_Settings_Delete"]').live('click',function(e){
                    var res = confirm('Are You Sure You Want To Delete')
                    if (res == true){
                        var id = (jQuery(this).attr('href'))
                        jQuery.post(ajaxUrl,{
                            action: 'mks_gf_delete',
                            id: id
                        },function(e){
                            jQuery('#GF_Settings a[href="' + id +'"]').parents('tr').hide();
                        })
                        return false
                    }
                    else{
                        return false
                    }
                })
            })
        </script>

        <!-- Listing Table -->
        <table class="widefat" cellspacin="0" id="GF_Settings">
            <thead>
                <tr>
                    <th></th>
                    <th>Form</th>
                    <th>MakesBridge List</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th></th>
                    <th>Form</th>
                    <th>MakesBridge List</th>
                </tr>
            </tfoot>
            <tbody>
                <?
                $GF_MKS_Settings = GFMakesBridgeData::retrieveFormSettingsList();
                foreach ($GF_MKS_Settings as $setting) {
                    echo "<tr><td><a href=" . $setting['id']
                    . " title='GF_Settings_Edit' />edit | <a href='"
                    . $setting['id'] . "' title='GF_Settings_Delete'>delete</a></td>
                    <td>" . $setting['form_title'] . "</td>
                    <td>" . $setting['list_id'] . "</td>";
                }
                ?>
                </tr>
            </tbody>
        </table>
        <div class="mks_gf">
            <h2>MakesBridge GravityForms</h2>


            <?
            //Get a list of Gravity Forms Forms
            $forms = RGFormsModel::get_forms();
            //Create A Select Option for our Forms
            echo '<label>Gravity Form</label>';
            echo '<select id="mks_gform">';
            echo '<option></option>';
            foreach ($forms as $form) {
                echo '<option value=' . $form->id . '>';
                echo $form->title;
                echo '</option>';
            }
            echo '</select><br/>';

            //Get MakesBridge User Options
            $options = get_option('makesbridge_options');

            // Get A list of MakesBridge Custom Fields
            $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);
            $api->login();
            $fields = $api->retrieveCustomFields();
            $lists = $api->retrieveLists();
            $workflows = $api->retrieveWorkflowList();
//            print_r($workflows);

            //Retrieve MKS Lists
            echo '<label>MakesBridge List</label>';
            echo '<select id="mksList">';
            foreach ($lists as $list) {
                if ($list->userID == $options['MKS_UserId']) {
                    echo '<option>';
                    echo $list->name;
                    echo '</option>';
                }
            }
            echo '</select><br/>';

            echo '<label>Manually Add To  Workflow</label>';
            echo '<select id="mksworkflow">';
            foreach ($workflows as $workflow) {
                if ($workflow->manualAddition == 'true') {
                    echo '<optgroup label="' . $workflow->name . '">';
                    foreach ($workflow->Steps->Step as $step) {
                        echo '<option>';
                        echo 'step' . '&nbsp;' . $step->stepOrder . '&nbsp;' . $step->label;
                        echo '</option>';
                    }
                    echo '</optgroup>';
                }
            }
            echo '</select><br/>';
            ?>
            <div id="mks_gf_fields">

            </div> 
            <input type="submit" class="button-primary"/>
        </div>
        <?
    }

    //  Configure the Database
    public static function configure() {
        GFMakesBridgeData::createTable();
    }

    /*
     * Retrieve List of Workflows and Workflow Steps
     */

    public static function getWorkflowList() {
        
    }

}

//End Class GFMakesBridge


/*
 * MakesBridge Data Configuration Class
 */
class GFMakesBridgeData {

    //Create the Database if it doesn't exist
    public static function createTable() {
        global $wpdb;
        $table_name = self::getMakesBridgeTableName();

        if (!empty($wpdb->charset))
            $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
        if (!empty($wpdb->collate))
            $charset_collate .= " COLLATE $wpdb->collate";

        $sql = "CREATE TABLE $table_name (
              id mediumint(8) unsigned not null auto_increment,
              form_id mediumint(8) unsigned not null,
              is_active tinyint(1) not null default 1,
              list_id longtext,
              meta longtext,
              PRIMARY KEY  (id),
              KEY form_id (form_id)
            )$charset_collate;";

        require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    //Function to retrieve table name
    public static function getMakesBridgeTableName() {
        global $wpdb;
        return $wpdb->prefix . "cg_makesbridge";
    }

    //Function to update database with form settings
    public static function updateFormSettings($id, $formId, $list_id, $setting) {
        global $wpdb;
        $tableName = self::getMakesBridgeTableName();
        $settings = maybe_serialize($setting);
        if ($id == 0) {
            //Insert Values
            $wpdb->insert($tableName, array("form_id" => $formId, 'is_active' => '1', "meta" => $settings, "list_id" => $list_id), array("%d", "%d", "%s", "%s"));
            $id = $wpdb->get_var("SELECT LAST_INSERT_ID()");
        } else {
            //Update Values
            $res = $wpdb->update($tableName, array("form_id" => $formId, "is_active" => '1', "meta" => $settings, "list_id" => $list_id), array("id" => $id), array("%d", "%d", "%s", "%s"), array("%d"));
        }
        return $id;
    }

    //Function to retrieve MakesBridge Form Settings from a makesBridge Form
    public static function retrieveForm($id) {
        global $wpdb;
        $tableName = self::getMakesBridgeTableName();
        $sql = $wpdb->prepare("SELECT id, form_id, is_active, meta, list_id FROM $tableName WHERE form_id=%d", $id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        $result = $results[0];
        $result["meta"] = maybe_unserialize($result["meta"]);
        return $result;
    }

    public static function retrieveFormSettings($id) {
        global $wpdb;
        $tableName = self::getMakesBridgeTableName();
        $sql = $wpdb->prepare("SELECT id, form_id, is_active, meta, list_id FROM $tableName WHERE form_id=%d", $id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        $result = $results[0];
        $result["meta"] = maybe_unserialize($result["meta"]);
        return $result;
    }

    public static function retrieveFormSettingsList() {
        global $wpdb;
        $table_name = self::getMakesBridgeTableName();
        $form_table_name = RGFormsModel::get_form_table_name();
        $sql = "SELECT s.id, s.is_active, s.form_id, s.meta,s.list_id, f.title as form_title
                FROM $table_name s
                INNER JOIN $form_table_name f ON s.form_id = f.id";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $count = sizeof($results);
        for ($i = 0; $i < $count; $i++) {
            $results[$i]["meta"] = maybe_unserialize($results[$i]["meta"]);
        }
        return $results;
    }

    public static function deleteFormSettings($id) {
        global $wpdb;
        echo $id;
        $table_name = self::getMakesBridgeTableName();
        $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id=%s", $id));
    }

}

//End Class GFMakesBridgeData

$options = get_option('makesbridge_options');
require_once 'mksapi.php';

function makesbridge($user_id) {

=======
  Version: 0.01
  Licence: GPL2
 */

add_action("gform_after_submission","mks_gf_submission" , 10 , 2);

function mks_gf_submission($entry,$form){
    $firstName = $entry['4.3'];
    $lastName = $entry['4.6'];
    $email = $entry['3'];
    
    $xml = new SimpleXMLElement();
    
    $options = get_option('makesbridge_options');
    
    $api = new mksapi();
    $api->login();
    
    
    
};

$options = get_option('makesbridge_options');
require_once 'mksapi.php';
include('ob_settings.php');
include('ExampleOptions.php');

function makesbridgeRequest() {
    
}

function mks_gf_form() {
    $id = $_POST['id'];
    $form = RGFormsModel::get_form_meta($id);
    $fields = GFCommon::get_section_fields($gform, $section_field_id);
//    foreach($gform as $form){
//        
//    }

    $options = get_option('makesbridge_options');

    // Get A list of MakesBridge Custom Fields
    $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);
    $api->login();
    $mksfields = $api->retrieveCustomFields();

    if (is_array($form["fields"])) {
        foreach ($form["fields"] as $field) {
            if (is_array(rgar($field, "inputs"))) {
                //If this is an address field, add full name to the list
                if (RGFormsModel::get_input_type($field) == "address")
                    $fields[] = array($field["id"], GFCommon::get_label($field));

                foreach ($field["inputs"] as $input)
                    $fields[] = array($input["id"], GFCommon::get_label($field, $input["id"]));
            }
            else if (!rgar($field, "displayOnly")) {
                $fields[] = array($field["id"], GFCommon::get_label($field));
            }
        }
    }
    
    $standardFields = array(
        'email',
        'firstName',
        'middleName',
        'lastName',
        'birthDate',
        'gender',
        'maritalStatus',
        'occupation',
        'householdIncome',
        'educationLevel',
        'addressLine1',
        'addressLine2',
        'city',
        'stateCode',
        'countryCode',
        'areaCode',
        'zip',
        'telephone',
        'industry',
        'company',
        'source',
        'salesRep',
        'salesStatus'
    );
//    $data = json_encode($fields);
//      $data = "<select>\r\n";
    $data = '<h3><label>Gravity Fields</label>MakesBridge Fields</h3>';
    foreach ($fields as $field) {
        $data .= '<label>' . $field[1] . '</label>';
        $data .= "<select id=" . $field[0] .">";
        $data .= "<optgroup label='Standard Fields'>";

        foreach($standardFields as $standardField){
            $data .= '<option>';
            $data .= $standardField;
            $data .= '</option>';
        };
        
        $data .= "</optgroup>";
        $data .= "<optgroup label='Custom Fields'>";
        foreach ($mksfields as $mksfield) {
            $data .= '<option>';
            $data .= $mksfield->name;
            $data .= '</option>';
        };
        $data .= "</optgroup>";
        $data .= '<option>';
        $data .= '[New Custom Field]';
        $data .= '</option>';
        $data .= '</select><br/>';
//            $data .= "</option>\r\n";
    }
//    print_r($fields);
//      $data .= '<select>';
//    echo "<pre>";
//    print_r($form[0]);
//    echo "</pre>";
    $res = new WP_Ajax_Response(array(
                'data' => $data
            ));
    $res->send();
}

add_action('wp_ajax_mks_gf_form', 'mks_gf_form');


/*
 * MakesBridge Gravity Forms Integration
 */

function mks_gf_options() {
    ?>
    <style>
        .mks_gf label{
            width: 200px;
            float: left;
        }
        h2{
            color: #333;
        }
    </style>
    <script type="text/javascript">
        var ajaxUrl = '<?php bloginfo('wpurl'); ?>/wp-admin/admin-ajax.php';
        jQuery(document).ready(function(){
            jQuery('#mks_gform').change(function(){
                var id = (jQuery(this).val());  
                //                console.log(id);
                jQuery.post(ajaxUrl,{
                    action: 'mks_gf_form',
                    id: id
                },function(res){
                    jQuery('#mks_gf_fields').html(jQuery(res).find('response_data').text());
                    //                    console.log(res);
                    //                   jQuery('#mks_gf_fields').html(res);
                })
            });
                
            jQuery('select').live('change',function(){
//            console.log(jQuery(this).val());
                if ( jQuery( this ).val() == '[New Custom Field]' ){
                    field = "<em>Enter Field Name<input type='text' name=/></em>";
                    jQuery(this).after( field );
                }
            })
            
            jQuery('input[type="submit"]').click(function(){
                jQuery('select').each(function(){
                    console.log(jQuery(this).val());
                    console.log(jQuery(this).attr('id'));
                })
            })
        })
    </script>
    
    <table class="widefat" cellspacin="0">
        <thead>
            <tr>
                <th class="manage-column column-cb check-column"></th>
                <th>Form</th>
                <th>MakesBridge List</th>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <th class="manage-column column-cb check-column"></th>
                <th>Form</th>
                <th>MakesBridge List</th>
            </tr>
        </tfoot>
    </table>
    <div class="mks_gf">
        <?
        echo '<h2>MakesBridge GravityForms</h2>';
        //Get a list of Gravity Forms Forms
        $forms = RGFormsModel::get_forms();
        //Create A Select Option for our Forms
        echo '<label>Gravity Form</label>';
        echo '<select id="mks_gform">';
        foreach ($forms as $form) {
            echo '<option value=' . $form->id . '>';
            echo $form->title;
            echo '</option>';
        }
        echo '</select><br/>';

        //Get MakesBridge User Options
        $options = get_option('makesbridge_options');

        // Get A list of MakesBridge Custom Fields
        $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);
        $api->login();
        $fields = $api->retrieveCustomFields();
        $lists = $api->retrieveLists();


        //Retrieve MKS Lists
        echo '<label>MakesBridge List</label>';
        echo '<select>';
        foreach ($lists as $list) {
            echo '<option>';
            echo $list->name;
            echo '</option>';
        }
        echo '</select>';



        // Create A list of Gravity Form Fields
//    foreach ($gform['fields'] as $gffields) {
////        print_r($gffields['label']);
//        echo $gffields['label'];
//        echo '<select>';
//        foreach ($fields as $field) {
//            echo '<option>';
//            echo $field->name;
//            echo '</option>';
//        }
//        echo '</select> <br/>';
//        echo $gfforms->label;
//    }
        ?>
        <div id="mks_gf_fields">

        </div> 
        <input type="submit" class="button-primary"/>
    </div>
    <?
    //Create a DropDown for MKS Custom Fields
}

function mks_gf_forminfo($id) {
    $gform = RGFormsModel::get_form_meta($id);
    return($gform);
}

function makesbridge($user_id) {




    echo "<pre>";
    print_r($user_id);
    echo "</pre>";
>>>>>>> first commit

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
    add_menu_page("BridgeMail System", "BridgeMail", "manage_options", "makesbridge", "MKS_plugin_options_page");
    add_submenu_page('makesbridge', 'Manage MakesBridge Settings', 'Settings', 'manage_options', 'makesbridge', 'MKS_plugin_options_page');
    add_submenu_page('makesbridge', 'Manage Options', 'Manage Campaigns', 'manage_options', 'mks_campaigns', 'MKS_plugin_options_page');
<<<<<<< HEAD
<<<<<<< HEAD
    add_submenu_page('makesbridge', 'Gravity Settings', 'GravityForms Settings', 'manage_options', 'mks_gf', array('GFMakesBridge', 'mks_gf_options'));
=======
    add_submenu_page('makesbridge', 'Gravity Settings', 'GravityForms Settings', 'manage_options', 'mks_gf', 'MKS_plugin_options_page');
>>>>>>> first commit
=======
    add_submenu_page('makesbridge', 'Gravity Settings', 'GravityForms Settings', 'manage_options', 'mks_gf', 'mks_gf_options');
>>>>>>> commit
    add_options_page('makesbridge', 'Mange Campaigns', 'Manage MakesBridge Campaigns', 'manage_options', 'MKS_plugin_options_page');

//	add_submenu_page( 'gprojects', 'Manage Categories', 'Manage Categories', 'manage_options', 'gprojects_cats', 'gprojects_cats_page');
//	add_submenu_page( 'gprojects', 'G-Projects Options', 'Options', 'manage_options', 'gprojects_options', 'gprojectsOptions');
//	add_submenu_page( 'gprojects', 'Uninstall G-Projects', 'Uninstall', 'manage_options', 'gprojects_uninstall', 'uninstall_gprojects');
// Add the admin options page
//add_menu_page( 'MakesBridge', 'BridgeMail',6);
//add_menu_page("Test", "tesT", 6, 'MKS_plugin_options_page');
}

//add_action('admin_menu', 'MKS_plugin_admin_add_page');    
<<<<<<< HEAD
//add_action('init', 'mks_handle_request');
=======
// Check API KEY
add_action('init', 'mks_handle_request');

>>>>>>> first commit
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
<<<<<<< HEAD
            <em>Don't have a MakesBridge Account?<a href="http://makesbridge.com/request-15-day-trial?pmc=CLDGRP" target="_BLANK">Sign up for a free trial</a></em>
        </p>
        <form action="options.php" method="post">
            <?php settings_fields('makesbridge_options');
            do_settings_sections('MakesBridge'); ?>
=======
            <em>Don't have a MakesBridge Account? 
                <a href="http://makesbridge.com/request-15-day-trial?pmc=CLDGRP" target="_BLANK">
                    Sign up for a free trial
                </a>
            </em>
        </p>
        <form action="options.php" method="post">
<<<<<<< HEAD
    <?php settings_fields('makesbridge_options');
    do_settings_sections('MakesBridge'); ?>
>>>>>>> first commit
=======
            <?php settings_fields('makesbridge_options');
            do_settings_sections('MakesBridge'); ?>
>>>>>>> commit
            <input type="hidden" name="mks_action" value="api_settings" />
            <input name="Submit" type="submit" value="Save Changes" class="button"/>
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
    add_settings_field('MKS_List', 'MakesBridge List', 'MKS_plugin_setting_MKS_List', 'MakesBridge', 'makesbridge');
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

add_action("user_register", "makesbridge");

function verify_api() {
    $options = get_option('makesbridge_options');
    $mks_uid = $options['MKS_UserId'];
    $mks_api = $options['MKS_API_Token'];
    $api = new mksapi($mks_uid, $mks_api);
    $api->testSettings();
}

add_action('init', 'MKS_subscribe');

function MKS_subscribe() {
<<<<<<< HEAD
<<<<<<< HEAD
=======
>>>>>>> commit
    if (isset($_POST['name'])) {
        $api = new mksapi($mks_uid, $mks_api);
        $api->login();
        $api->createSubscriber($data);
        return;
<<<<<<< HEAD
=======
    if(isset($_POST['name'])){
    $api = new mksapi($mks_uid, $mks_api);
    $api->login();
    $api->createSubscriber($data); 
    return;
>>>>>>> first commit
=======
>>>>>>> commit
    }

//    print_r($_POST);
}

class Makesbridge_widget extends WP_Widget {

    function Makesbridge_widget() {
        $options = get_option('makesbridge_options');
        $mks_uid = $options['MKS_UserId'];
        $mks_api = $options['MKS_API_Token'];
<<<<<<< HEAD
//        $name = $_POST['firstname'];
//        $email = $_POST['custemail'];
//        $data = array(
//            'name' => $_POST['name'],
//            'email' => $_POST['email']
//        );
=======
        $name = $_POST['firstname'];
        $email = $_POST['custemail'];
        $data = array(
            'name' => $_POST['name'],
            'email' => $_POST['email']
        );
>>>>>>> first commit
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
<<<<<<< HEAD
//    echo 'hello world';
}

function add_dashboard() {
//    wp_add_dashboard_widget('mks_dashboard_widget', 'BridgeMail System', 'dashboard_widget');
}

//add_action('wp_dashboard_setup', 'add_dashboard');

//add_action('widgets_init', create_function('', 'return register_widget("Makesbridge_widget");'));
=======
    echo 'hello world';
}

function add_dashboard() {
    wp_add_dashboard_widget('mks_dashboard_widget', 'BridgeMail System', 'dashboard_widget');
}

add_action('wp_dashboard_setup', 'add_dashboard');

add_action('widgets_init', create_function('', 'return register_widget("Makesbridge_widget");'));
>>>>>>> first commit
