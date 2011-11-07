<?php
/*
 * MakesBridge Gravity Forms Plugin
 */

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
        $workflow = $_POST['workflow'];
        if (isset($_POST['id'])) {
            $id = $_POST['id'];
        } else {
            $id = '0';
        };
        $id = GFMakesBridgeData::updateFormSettings($id, $formId, $listId, $setting, $workflow);
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
        if (isset($settings['meta']['standard'])) {
            foreach ($settings['meta']['standard'] as $key => $val) {
                $data['standard'][$val] = $entry[$key];
            };
        };

        //Custom Fields
        if (isset($settings['meta']['custom'])) {
            foreach ($settings['meta']['custom'] as $key => $val) {
                $data['custom'][$val] = $entry[$key];
            };
        }

        //Wordpress Options
        $options = get_option('makesbridge_options');

        //MakesBridge API
        $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);
        //Get our Auth_Tk
        $api->login();
        //Create Subscriber        
        $sub = $api->createSubscriber($data, $settings['list_id']);
        $res = simplexml_load_string($sub['body']);
        $subscriberId = $res->Subscriber->id;
        $subscriberEmail = $res->Subscriber->email;

        if (isset($settings['workflow']) && $subscriberId) {
            $workflow = $api->addToWorkflow($settings['workflow']['workflowId'], $subscriberId, $settings['workflow']['stepOrder']);
        }
        $tk = "bms.tk";
        $subscriberTicket = $api->getTrackingTicket($subscriberId)->$tk;
        
        if (isset($subscriberTicket)){
        //Action to insert javascript to head on successfull form submission and
        //Makes
        ?>
        <script type="text/javascript">
            var expiry = new Date();
            expiry.setDate(expiry.getDate() + 365);
            expiry.toUTCString();
            document.cookie = 'bms.id = <?= $subscriberEmail ?>; expires = ' + expiry;
            document.cookie = 'bms.tk = <?= $subscriberTicket ?>; expires =  ' + expiry;
        </script>
        <?
        }
        return(true);
    }

    function mks_gf_form() {
        $options = get_option('makesbridge_options');
        $id = $_POST['id'];
        $form = RGFormsModel::get_form_meta($id);
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
        $options = get_option('makesbridge_options');

        $api = new mksapi($options['MKS_UserId'], $options['MKS_API_Token']);

        if ($api->testSettings()) {
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
                    jQuery('#mks_gform').live('change',function(){
                        var id = (jQuery(this).val());
                        jQuery.post(ajaxUrl,{
                            action: 'mks_gf_form',
                            id: id
                        },function(res){
                            //                            console.log(res)
                            jQuery('#mks_gf_fields')
                            .html(jQuery(res).find('response_data').text());
                        })
                    });
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
                    /*   Add the option to define a new custom field on the fly
                     *   if the field makesbridge dropdown value is
                     *   [New Custom Field]
                     */  
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
                    jQuery('.mks_gf select').live('change',function(){
                        if ( jQuery( this ).val() == '[New Custom Field]' ){
                            field = "<em>Enter Field Name<input type='text' name=/></em>";
                            jQuery(this).after( field );
                        }
                    })                                                  
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
                    /*  OnSubmit create a map of all the form settings
                     *   including a mapping of gravity form fields to
                     *   makesbridge and send to the database
                     */
                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
                    jQuery('.mks_gf input[type="submit"]').live('click',function(){
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
                                                                                                                                                                                                                
                                                                                                                                                                                
                        //Get the value of our workflow field if it's selected
                        if(jQuery('#mks_wk_cb:checked') && jQuery('#mksworkflow option:selected').val() !== ""){
                            data['workflow'] = new Object();
                            data['workflow']['workflowId'] = jQuery('#mksworkflow option:selected').parent().attr('id');
                            data['workflow']['stepOrder']  = jQuery('#mksworkflow option:selected').data('steporder');
                            data['workflow']['uniqueId'] = jQuery('#mksworkflow option:selected').attr('id');
                        } else {
                            data['workflow'] = ''
                        }
                                                                                                                                                                        
                        //                        console.log(data)
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
                            workflow: data['workflow'],
                            settings: data['fields']
                        },function(res){
                            if(settId == '0'){
                                settId = jQuery(res).find('response_data').text();
                                jQuery('#GF_Settings').before("<div class='updated'><p>Settings Added</p></div>").prev().delay('2000').hide('slow');
                                jQuery('#GF_Settings').append("<tr><td><a href=" + settId + " title='GF_Settings_Edit'>edit</a> | <a href=" + settId + " title='GF_Settings_Delete'>delete</a></td><td>" + formName + "</td><td>" + mksList + "</td></tr>")
                            } else {
                                jQuery('#GF_Settings').before("<div class='updated'><p>Settings Updated</p></div>").prev().delay('2000').hide('slow');
                            }
                            settId = jQuery(res).find('response_data').text();
                            jQuery('#GF_Settings a[href="' + settId + '"]').parents('tr').html("<td><a href=" + settId + " title='GF_Settings_Edit'>edit</a> | <a href=" + settId + " title='GF_Settings_Delete'>delete</a></td><td>" + formName + "</td><td>" + mksList + "</td>")                                                        
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
                                                                                                                                                                            
                            // check to see if workflow exists and check the
                            // the checkbox if it is
                            if(json.workflow === '' || json.workflow == undefined){ //No Workflows
                                // Uncheck the workflow box
                                jQuery('#mks_wk_cb').attr('checked',false)
                                // Hide the workflow option
                                jQuery('#mksworkflow').hide()
                            } else { //Workflows exists
                                // Check the workflow box
                                jQuery('#mks_wk_cb').attr('checked',true)
                                // Show the workflow options
                                jQuery('#mksworkflow').show()
                                //Now we have a workflow select the relevant option  
                                jQuery('#mksworkflow option').each(function(e){
                                    //Since Babar is generating random hashes from the id in the
                                    //API we can't rely on our workflowIds stored locally so we 
                                    //have to hack around and store a plain text representation 
                                    //of the id from the workflow name and step id
                                    //and assign this to an id attribute and match on that
                                    if( jQuery(this).attr('id') == json.workflow.uniqueId){
                                        // We have a match, now select that!
                                        jQuery(this).attr('selected','selected');
                                    }
                                })
                            }
                                                                                                                                                    
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
                                                                                                                                                                                                                                    
                                                                                                                                                                                                                                    
                    /*  
                     *  Workflows
                     */ 
                                                                                                                                                                                                                         
                    jQuery('#mks_wk_cb').change(function(){
                        (jQuery(this).is(':checked')) ? jQuery('#mksworkflow').show() : jQuery('#mksworkflow').hide() ;
                    })
                                                                                                                                                                                                            
                                                                                                                                                                                                            
                    jQuery('#GF_Settings a[title="GF_Settings_Delete"]').live('click',function(e){
                        var res = confirm('Are You Sure You Want To Delete')
                        if (res == true){
                            var id = (jQuery(this).attr('href'))
                            jQuery.post(ajaxUrl,{
                                action: 'mks_gf_delete',
                                id: id
                            },function(e){
                                jQuery('#GF_Settings').before("<div class='updated'><p>Settings Deleted</p></div>").prev().delay('2000').hide('slow');
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
//            $options = get_option('makesbridge_options');
                // Get A list of MakesBridge Custom Fields
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
                echo '<input id="mks_wk_cb" type="checkbox" name="workflow"/>';
                echo '<select id="mksworkflow" style="display: none;">';
                echo '<option></option>';
                foreach ($workflows as $workflow) {
                    if ($workflow->manualAddition == 'true') {
                        echo '<optgroup id="' . $workflow->id . '" label="' . $workflow->name . '">';
                        foreach ($workflow->Steps->Step as $step) {
                            echo '<option data-steporder="' . $step->stepOrder . '" id="' . $workflow->name . $step->stepOrder . '">';
                            echo 'step' . '&nbsp;' . $step->stepOrder . '&nbsp;' . $step->label;
                            echo '</option>';
                        }
                        echo '</optgroup>';
                    }
                }
                echo '</select>';
                ?>
                <div id="mks_gf_fields">

                </div>
            </div>
            <div style="clear:both" class="mks_gf">
                <input type="submit" class="button-primary"/>
            </div>
        <? } else { ?>
            <div class="updated">
                <em>Don't have a MakesBridge Account?</em> <a href="http://makesbridge.com/request-15-day-trial?pmc=CLDGRP" target="_BLANK">Sign up for a free trial here</a>                
            </div>
            <div class="error">
                <p>Please check your MakesBridge credentials</p>
            </div>
            <?
        }
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
              workflow longtext,
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
    public static function updateFormSettings($id, $formId, $list_id, $setting, $workflow) {
        global $wpdb;
        $tableName = self::getMakesBridgeTableName();
        $settings = maybe_serialize($setting);
        $workflow = maybe_serialize($workflow);
        if ($id == 0) {
            //Insert Values
            $wpdb->insert($tableName, array("form_id" => $formId, 'is_active' => '1', "meta" => $settings, "list_id" => $list_id, "workflow" => $workflow), array("%d", "%d", "%s", "%s", "%s"));
            $id = $wpdb->get_var("SELECT LAST_INSERT_ID()");
        } else {
            //Update Values
            $res = $wpdb->update($tableName, array("form_id" => $formId, "is_active" => '1', "meta" => $settings, "list_id" => $list_id, "workflow" => $workflow), array("id" => $id), array("%d", "%d", "%s", "%s", "%s"), array("%d"));
        }
        return $id;
    }

    //Function to retrieve MakesBridge Form Settings from a makesBridge Form
    public static function retrieveForm($id) {
        global $wpdb;
        $tableName = self::getMakesBridgeTableName();
        $sql = $wpdb->prepare("SELECT id, form_id, is_active, meta, list_id, workflow FROM $tableName WHERE form_id=%d", $id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        $result = $results[0];
        $result["meta"] = maybe_unserialize($result["meta"]);
        $result["workflow"] = maybe_unserialize($result["workflow"]);
        return $result;
    }

    public static function retrieveFormSettings($id) {
        global $wpdb;
        $tableName = self::getMakesBridgeTableName();
        $sql = $wpdb->prepare("SELECT id, form_id, is_active, meta, list_id, workflow FROM $tableName WHERE id=%d", $id);
        $results = $wpdb->get_results($sql, ARRAY_A);
        $result = $results[0];
        $result["meta"] = maybe_unserialize($result["meta"]);
        $result["workflow"] = maybe_unserialize($result["workflow"]);
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