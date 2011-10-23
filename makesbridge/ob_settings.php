<?php
//
//  SETTINGS CONFIGURATION CLASS
//
//  By Olly Benson / v 1.2 / 13 July 2011 / http://code.olib.co.uk
//  Modified / Bugfix by Karl Cohrs / 17 July 2011 / http://karlcohrs.com
//
//  HOW TO USE
//  * add a include() to this file in your plugin.
//  * amend the config class below to add your own settings requirements.
//  * to avoid potential conflicts recommended you do a global search/replace on this page to replace 'ob_settings' with something unique
//  * Full details of how to use Settings see here: http://codex.wordpress.org/Settings_API
 
class ob_settings_config {
 
// MAIN CONFIGURATION SETTINGS
 
var $group = "obDisplay"; // defines setting groups (should be bespoke to your settings)
var $page_name = "ob_display"; // defines which pages settings will appear on. Either bespoke or media/discussion/reading etc
 
//  DISPLAY SETTINGS
//  (only used if bespoke page_name)
 
var $title = "OB Display";  // page title that is displayed
var $intro_text = "This allows you to configure the slideshow exactly the way you want it"; // text below title
var $nav_title = "OB Display"; // how page is listed on left-hand Settings panel
 
//  SECTIONS
//  Each section should be own array within $sections.
//  Should contatin title, description and fields, which should be array of all fields.
//  Fields array should contain:
//  * label: the displayed label of the field. Required.
//  * description: the field description, displayed under the field. Optional
//  * suffix: displays right of the field entry. Optional
//  * default_value: default value if field is empty. Optional
//  * dropdown: allows you to offer dropdown functionality on field. Value is array listed below. Optional
//  * function: will call function other than default text field to display options. Option
//  * callback: will run callback function to validate field. Optional
//  * All variables are sent to display function as array, therefore other variables can be added if needed for display purposes
 
var $sections = array(
    'slideshow' => array(
        'title' => "Display options",
        'description' => "Settings to do with how the plugin is displayed",
        'fields' => array (
          'width' => array (
              'label' => "Width",
              'description' => "Width of the display",
              'length' => "3",
              'suffix' => "px",
              'default_value' => "640"
              ),
          'height' => array (
              'label' => "Height",
              'description' => "Height of the display",
              'length' => "3",
              'suffix' => "px",
              'default_value' => "240"
              ),
            'display_colour' => array(
              'label' => "Display Colour",
              'description' => "Choose the display colour you'd like",
              'dropdown' => "dd_colour",
              'default_value' => "#00f")
          )
        ),
      'boilerplate' => array(
          'title' => 'Boilerplate settings',
          'description' => "Settings to do with what is displayed at the bottom of the page",
          'fields' => array(
            'copyright' => array (
              'label' => "Copyright",
              'description' => "What copyright notice would you like?",
              'default_value' => "©2011 Olly Benson"
              ),
            'copyright_colour' => array(
              'label' => "Copyright Colour",
              'description' => "Choose the colour you'd like the copyright to be",
              'dropdown' => "dd_colour",
              'default_value' => "#aaa")
            )
          )
    );
 
 // DROPDOWN OPTIONS
 // For drop down choices.  Each set of choices should be unique array
 // Use key => value to indicate name => display name
 // For default_value in options field use key, not value
 // You can have multiple instances of the same dropdown options
 
var $dropdown_options = array (
    'dd_colour' => array (
        '#f00' => "Red",
        '#0f0' => "Green",
        '#00f' => "Blue",
        '#fff' => "White",
        '#000' => "Black",
        '#aaa' => "Gray",
        )
    );
 
//  end class
};
 
class ob_settings {
 
function ob_settings($settings_class) {
    global $ob_settings;
    $ob_settings = get_class_vars($settings_class);
 
    if (function_exists('add_action')) :
      add_action('admin_init', array( &$this, 'plugin_admin_init'));
      add_action('admin_menu', array( &$this, 'plugin_admin_add_page'));
      endif;
}
 
function plugin_admin_add_page() {
  global $ob_settings;
  add_options_page($ob_settings['title'], $ob_settings['nav_title'], 'manage_options', $ob_settings['page_name'], array( &$this,'plugin_options_page'));
  }
 
function plugin_options_page() {
  global $ob_settings;
printf('</pre>
<div>
<h2>%s</h2>
%s
<form action="options.php" method="post">',$ob_settings['title'],$ob_settings['intro_text']);
 settings_fields($ob_settings['group']);
 do_settings_sections($ob_settings['page_name']);
 printf('<input type="submit" name="Submit" value="%s" /></form></div>
<pre>
',__('Save Changes'));
  }
 
function plugin_admin_init(){
  global $ob_settings;
  foreach ($ob_settings["sections"] AS $section_key=>$section_value) :
    add_settings_section($section_key, $section_value['title'], array( &$this, 'plugin_section_text'), $ob_settings['page_name'], $section_value);
    foreach ($section_value['fields'] AS $field_key=>$field_value) :
      $function = (!empty($field_value['dropdown'])) ? array( &$this, 'plugin_setting_dropdown' ) : array( &$this, 'plugin_setting_string' );
      $function = (!empty($field_value['function'])) ? $field_value['function'] : $function;
      $callback = (!empty($field_value['callback'])) ? $field_value['callback'] : NULL;
      add_settings_field($ob_settings['group'].'_'.$field_key, $field_value['label'], $function, $ob_settings['page_name'], $section_key,array_merge($field_value,array('name' => $ob_settings['group'].'_'.$field_key)));
      register_setting($ob_settings['group'], $ob_settings['group'].'_'.$field_key,$callback);
      endforeach;
    endforeach;
  }
 
function plugin_section_text($value = NULL) {
  global $ob_settings;
  printf("
%s
 
",$ob_settings['sections'][$value['id']]['description']);
}
 
function plugin_setting_string($value = NULL) {
  $options = get_option($value['name']);
  $default_value = (!empty ($value['default_value'])) ? $value['default_value'] : NULL;
  printf('<input id="%s" type="text" name="%1$s[text_string]" value="%2$s" size="40" /> %3$s%4$s',
    $value['name'],
    (!empty ($options['text_string'])) ? $options['text_string'] : $default_value,
    (!empty ($value['suffix'])) ? $value['suffix'] : NULL,
    (!empty ($value['description'])) ? sprintf("<em>%s</em>",$value['description']) : NULL);
  }
 
function plugin_setting_dropdown($value = NULL) {
  global $ob_settings;
  $options = get_option($value['name']);
  $default_value = (!empty ($value['default_value'])) ? $value['default_value'] : NULL;
  $current_value = ($options['text_string']) ? $options['text_string'] : $default_value;
    $chooseFrom = "";
    $choices = $ob_settings['dropdown_options'][$value['dropdown']];
  foreach($choices AS $key=>$option) :
    $chooseFrom .= sprintf('<option value="%s" %s>%s</option>',
      $key,($current_value == $key ) ? ' selected="selected"' : NULL,$option);
    endforeach;
    printf('
<select id="%s" name="%1$s[text_string]">%2$s</select>
%3$s',$value['name'],$chooseFrom,
  (!empty ($value['description'])) ? sprintf("<em>%s</em>",$value['description']) : NULL);
  }

 
//end class
}
 
$ob_settings_init = new ob_settings('ob_settings_config');
?>