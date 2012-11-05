<?php

if ($_POST) {
    $options_page = new OptionsPage();
    $options_page->save();
    $options_page->render();
}
else {
    $options_page = new OptionsPage();
    $options_page->render();
}

class OptionsPage
{ 
    var $post_types;

    function OptionsPage()
    {
        $this->post_types = $this->get_custom_fields(); 
    }
        
    function get_custom_fields() {
        $acfs = get_pages(array(
                'numberposts' 	=> 	-1,
                'post_type'	=>	'acf',
                'sort_column'   => 'menu_order',
                'order'         => 'ASC',
        ));
        
        if($acfs) {
            $final_fields = array();
            foreach($acfs as $acf) {
                $fields = $this->get_acf_fields($acf->ID);
                foreach ($fields as $field) {
                    
                    foreach($field['applied_on'] as $applied_on) {
                        if ($applied_on) {
                            $post_type = get_post_type_object($applied_on);
                            $final_fields[$applied_on]['label'] = $post_type->labels->name;
                            $final_fields[$applied_on]['post_type'] = $post_type->name;

                            if ($field[$applied_on]) {
                                $final_fields[$applied_on][] = array('label' => $field['label'], 'name' => $field['name'], 'key' => $field['key']);
                            }
                            else {
                                $final_fields[$applied_on][] = array('label' => $field['label'], 'name' => $field['name'], 'key' => $field['key']);
                            }
                        }
                    }
                }  
            }
        }

        return $final_fields;
    }

    function get_acf_fields($post_id)
    {
        $return = array();
        $keys = get_post_custom_keys($post_id);

        if($keys) {
            foreach($keys as $key) {
                if(strpos($key, 'field_') !== false) {
                    $field = $this->get_acf_field($key, $post_id);

                    if ($field) 
                        $return[$field['order_no']] = $field;
                }
            }

            ksort($return);
        }

        return $return;
    }

    function get_acf_field($field_name, $post_id = false)
    {
        $post_id = $post_id ? $post_id : get_post_meta_post_id($field_name);

        $field = get_post_meta($post_id, $field_name, true);

        if ($field['type'] == 'image') {
            $rules = get_post_meta($post_id, 'rule', false);

            $field['applied_on'] = array();
            foreach ($rules as $rule) {
                $field['applied_on'][] = $rule['value'];
            }

            return $field;
        }
        
        // Image repeater support.
        if ($field['type'] == 'repeater') {
            if ($field['sub_fields']) {
                foreach ($field['sub_fields'] as $sub_field) {
                    if ($sub_field['type'] == 'image') {
                        
                        $rules = get_post_meta($post_id, 'rule', false);

                        $field['applied_on'] = array();
                        foreach ($rules as $rule) {
                            $field['applied_on'][] = $rule['value'];
                        } 
                        
                        return $field;
                    }
                }
            }
            
            
        }

        return null;
    }

    function get_post_meta_post_id($field_name)
    {
        global $wpdb;
        $post_id = $wpdb->get_var( $wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s", $field_name) );

        if($post_id) 
            return (int)$post_id;

        return false;
    }
    
    function render() {
        echo '<form name="input" action="" method="post">';
        echo '<table class="widefat">';
        echo '<tr>';
        echo '<th>Post Type</th>';
        echo '<th>Enabled</th>';
        echo '<th>Field Name</th>';
        echo '<th>Width</th>';
        echo '<th>Height</th>';
        echo '</tr>';
        
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT post_type, field_name, field_key, enabled, width, height FROM acrop_fields" );
        
        $results = array();
        foreach ($rows as $row) {
            $results[$row->post_type][$row->field_name]['width'] = $row->width;
            $results[$row->post_type][$row->field_name]['height'] = $row->height;
            $results[$row->post_type][$row->field_name]['enabled'] = $row->enabled;
            $results[$row->post_type][$row->field_name]['field_key'] = $row->field_key;
        }

        foreach ($this->post_types as $post_type) {
            echo '<tr>';
            echo '<td valign="top" rowspan="' . (count($post_type) - 2) . '">' . $post_type['label'] . '</td>';
            
            for ($i = 0; $i < count($post_type) - 2; $i++) {
                $post_type_name = $post_type['post_type'];
                $field_name = $post_type[$i]['name'];
                $field_key = $post_type[$i]['key'];
                $is_enabled = $results[$post_type_name][$field_name]['enabled'];
                $width = $results[$post_type_name][$field_name]['width'];
                $height = $results[$post_type_name][$field_name]['height'];
                
                if ($i > 0) echo '<tr>';
                
                echo '<td><input type="checkbox" name="' . $post_type_name . '[' . $field_name . '|' . $field_key . '][enabled]" value="true"';
                if ($is_enabled) 
                    echo ' checked=checked ';
                
                echo '/></td>';
                echo '<td>' . $post_type[$i]['label'] . '</td>';
                echo '<td><input type="text" name="' . $post_type_name . '[' . $field_name . '|' . $field_key . '][width]" value="' . $width . '"/></td>';
                echo '<td><input type="text" name="' . $post_type_name . '[' . $field_name . '|' . $field_key . '][height]" value="' . $height . '"/></td>';
                
                echo '<input type="hidden" name="' . $post_type_name . '[' . $field_name . '|' . $field_key . '][field_key]" value="' . $field_key . '"/></td>'; 
                echo '<input type="hidden" name="' . $post_type_name . '[' . $field_name . '|' . $field_key . '][field_name]" value="' . $field_name . '"/></td>'; 
                
                if ($i > 0) 
                    echo '</tr>'; 
            }
            echo '</tr>';
        }
        echo '</table>';   
        echo '<br>';
        echo '<button class="button-primary" type="submit">Save</button>';
        echo '</form>';
    }
    
    function save() {
        global $wpdb;
        $values = '';
        foreach ($_POST as $post_type => $fields) {

            foreach ($fields as $field_name => $attributes) {
                $values .= '"' . $post_type . '"';
                $values .= ',"' . $attributes['field_name'] . '"';
                $values .= ',"' . $attributes['field_key'] . '"';
                
                $values .= ',"' . ($attributes['enabled'] ? 1 : 0) . '"';
                $values .= ',"' . ($attributes['width'] ? $attributes['width'] : 0) . '"';
                $values .= ',"' . ($attributes['height'] ? $attributes['height'] : 0) . '"';
                
                $gc = $this->gcd(($attributes['height'] ? $attributes['height'] : 1), ($attributes['width'] ? $attributes['width'] : 1));
                
                $values .= ',"' . (($attributes['width'] ? $attributes['width'] : 0) / $gc) . '"';
                $values .= ',"' . (($attributes['height'] ? $attributes['height'] : 0) / $gc) . '"';

                $ret = $wpdb->query(
                    "
                    INSERT INTO acrop_fields (post_type,field_name,field_key,enabled,width,height,aspect_ratio_width,aspect_ratio_height) 
                    VALUES ($values)
                    ON DUPLICATE KEY UPDATE enabled=\"" . ($attributes['enabled'] ? 1 : 0) . "\", width=\"" . $attributes['width'] . "\", height=\"" . $attributes['height'] . "\", aspect_ratio_width=\"" . (($attributes['width'] ? $attributes['width'] : 0) / $gc) . "\", aspect_ratio_height=\"" . (($attributes['height'] ? $attributes['height'] : 0) / $gc) . "\";
                    "
                );
                
                $values = '';
            } 
        }
    }

    function gcd($a, $b) {
       if ($b == 0) {
           return $a;
       }
       
       return $this->gcd($b, $a % $b);
    }
}


?>
