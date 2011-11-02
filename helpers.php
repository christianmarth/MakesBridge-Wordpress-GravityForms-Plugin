<?php

class helpers {
    /*
     * function to return a dropdown of mks lists 
     */

    function mks_list() {
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

}

?>
