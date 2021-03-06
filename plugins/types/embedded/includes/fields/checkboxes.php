<?php
add_filter( 'wpcf_relationship_meta_form',
        'wpcf_filds_checkboxes_relationship_form_filter' );

/**
 * Register data (called automatically).
 * 
 * @return type 
 */
function wpcf_fields_checkboxes() {
    return array(
        'id' => 'wpcf-checkboxes',
        'title' => __( 'Checkboxes', 'wpcf' ),
        'description' => __( 'Checkboxes', 'wpcf' ),
//        'validate' => array('required'),
        'meta_key_type' => 'BINARY',
    );
}

/**
 * Form data for post edit page.
 * 
 * @param type $field 
 */
function wpcf_fields_checkboxes_meta_box_form( $field, $field_object ) {
    $options = array();
    if ( !empty( $field['data']['options'] ) ) {
        global $pagenow;
        foreach ( $field['data']['options'] as $option_key => $option ) {
            // Set value
            $options[$option_key] = array(
                '#value' => $option['set_value'],
                '#title' => wpcf_translate( 'field ' . $field['id'] . ' option '
                        . $option_key . ' title', $option['title'] ),
                '#default_value' => (!empty( $field['value'][$option_key] )// Also check new post
                || ($pagenow == 'post-new.php' && !empty( $option['checked'] ))) ? 1 : 0,
                '#name' => 'wpcf[' . $field['id'] . '][' . $option_key . ']',
                '#id' => $option_key . '_'
                . wpcf_unique_id( serialize( $field ) ),
                '#inline' => true,
                '#after' => '<br />',
            );
        }
    }
    return array(
        '#type' => 'checkboxes',
        '#options' => $options,
    );
}

/**
 * Editor callback form.
 */
function wpcf_fields_checkboxes_editor_callback() {
    $form = array();
    $form['#form']['callback'] = 'wpcf_fields_checkboxes_editor_submit';
    $form['display'] = array(
        '#type' => 'radios',
        '#default_value' => 'display_all',
        '#name' => 'display',
        '#options' => array(
            'display_from_db' => array(
                '#title' => __( 'Display the value of this field from the database',
                        'wpcf' ),
                '#name' => 'display',
                '#value' => 'db',
                '#inline' => true,
                '#after' => '<br />'
            ),
            'display_all' => array(
                '#title' => __( 'Display all values with separator', 'wpcf' ),
                '#name' => 'display',
                '#value' => 'display_all',
                '#inline' => true,
                '#after' => '&nbsp;' . wpcf_form_simple( array('separator' => array(
                        '#type' => 'textfield',
                        '#name' => 'separator',
                        '#value' => ', ',
//                        '#title' => __('Separator', 'wpcf'),
                        '#inline' => true,
                        )) ) . '<br />'
            ),
            'display_values' => array(
                '#title' => __( 'Show one of these two values:', 'wpcf' ),
                '#name' => 'display',
                '#value' => 'value',
                '#inline' => true,
            ),
        ),
        '#inline' => true,
    );
    if ( isset( $_GET['field_id'] ) ) {
        $field = wpcf_admin_fields_get_field( $_GET['field_id'] );
        if ( !empty( $field['data']['options'] ) ) {
            foreach ( $field['data']['options'] as $option_key => $option ) {
                $form[$option_key . '-markup'] = array(
                    '#type' => 'markup',
                    '#markup' => '<h3>' . $option['title'] . '</h3>',
                );
                $form[$option_key . '-display-value-1'] = array(
                    '#type' => 'textfield',
                    '#title' => '<td style="text-align:right;">'
                    . __( 'Not selected:', 'wpcf' ) . '</td><td>',
                    '#name' => 'options[' . $option_key . '][display_value_not_selected]',
                    '#value' => $option['display_value_not_selected'],
                    '#inline' => true,
                    '#before' => '<table><tr>',
                    '#after' => '</td></tr>',
                    '#inline' => true,
                );
                $form[$option_key . '-display-value-2'] = array(
                    '#type' => 'textfield',
                    '#title' => '<td style="text-align:right;">'
                    . __( 'Selected:', 'wpcf' ) . '</td><td>',
                    '#name' => 'options[' . $option_key . '][display_value_selected]',
                    '#value' => $option['display_value_selected'],
                    '#after' => '</tr></table>',
                    '#inline' => true,
                );
            }
        }
    }
    $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'submit',
        '#value' => __( 'Save Changes' ),
        '#attributes' => array('class' => 'button-primary'),
    );
    $f = wpcf_form( 'wpcf-form', $form );
    wpcf_admin_ajax_head( 'Insert checkbox', 'wpcf' );
    echo '<form method="post" action="">';
    echo $f->renderForm();
    echo '</form>';
    wpcf_admin_ajax_footer();
}

/**
 * Editor callback form submit.
 */
function wpcf_fields_checkboxes_editor_submit() {
    $add = '';
    $field = wpcf_admin_fields_get_field( $_GET['field_id'] );
    $shortcode = '';
    if ( !empty( $field ) ) {
        if ( !empty( $_POST['options'] ) ) {
            if ( $_POST['display'] == 'display_all' ) {
                $separator = !empty( $_POST['separator'] ) ? $_POST['separator'] : '';
                $shortcode .= '[types field="' . $field['slug'] . '" separator="'
                        . $separator . '"]' . '[/types] ';
            } else {
                $i = 0;
                foreach ( $_POST['options'] as $option_key => $option ) {
                    if ( $_POST['display'] == 'value' ) {

                        $shortcode .= '[types field="' . $field['slug'] . '" option="'
                                . $i . '" state="checked"]'
                                . $option['display_value_selected']
                                . '[/types] ';
                        $shortcode .= '[types field="' . $field['slug'] . '" option="'
                                . $i . '" state="unchecked"]'
                                . $option['display_value_not_selected']
                                . '[/types] ';
                    } else {
                        $add = ' option="' . $i . '"';
                        $shortcode .= wpcf_fields_get_shortcode( $field, $add ) . ' ';
                    }
                    $i++;
                }
            }
        }
        echo editor_admin_popup_insert_shortcode_js( $shortcode );
        die();
    }
}

/**
 * View function.
 * 
 * @param type $params 
 */
function wpcf_fields_checkboxes_view( $params ) {
    $option = array();
    // Basic checks
    if ( empty( $params['field']['data']['options'] )
            || !is_array( $params['field_value'] ) ) {
        return '__wpcf_skip_empty';
    }

    /*
     * 
     * NO OPTION specified
     * loop over all options and display all of them
     */
    if ( !isset( $params['option'] ) ) {
        $separator = isset( $params['separator'] ) ? $params['separator'] : ', ';
        foreach ( $params['field_value'] as $name => &$value ) {
            /*
             * 
             * Set option
             */
            if ( isset( $params['field']['data']['options'][$name] ) ) {
                $option = $params['field']['data']['options'][$name];
            } else {
                // Unset if not valid
                unset( $params['field_value'][$name] );
                continue;
            }
            /*
             * 
             * Set output according to settings.
             * 'db' or 'value'
             */
            if ( $option['display'] == 'db'
                    && !empty( $option['set_value'] ) && !empty( $value ) ) {
                $value = $option['set_value'];
                $value = wpcf_translate( 'field ' . $params['field']['id'] . ' option ' . $name . ' value',
                        $value );
            } else if ( $option['display'] == 'value' ) {
                if ( isset( $option['display_value_selected'] ) && !empty( $value ) ) {
                    $value = $option['display_value_selected'];
                    $value = wpcf_translate( 'field ' . $params['field']['id'] . ' option ' . $name . ' display value selected',
                            $value );
                } else {
                    $value = $option['display_value_not_selected'];
                    $value = wpcf_translate( 'field ' . $params['field']['id'] . ' option ' . $name . ' display value not selected',
                            $value );
                }
            } else {
                unset( $params['field_value'][$name] );
            }
        }
        $output = implode( array_values( $params['field_value'] ), $separator );
        return empty( $output ) ? '__wpcf_skip_empty' : $output;
    }

    /*
     * 
     * 
     * OPTION specified - set required option.
     */
    $i = 0;
    foreach ( $params['field']['data']['options'] as $option_key =>
                $option_value ) {
        if ( intval( $params['option'] ) == $i ) {
            $option['key'] = $option_key;
            $option['data'] = $option_value;
            $option['value'] = !empty( $params['field_value'][$option_key] ) ? $params['field_value'][$option_key] : '__wpcf_unchecked';
            break;
        }
        $i++;
    }

    $output = '';

    /*
     * STATE set - use #content is as render value.
     * If setings are faulty - return '__wpcf_skip_empty'.
     */
    if ( isset( $params['state'] ) ) {
        $content = !empty( $params['#content'] ) ? htmlspecialchars_decode( $params['#content'] ) : '__wpcf_skip_empty';
        if ( $params['state'] == 'checked'
                && $option['value'] != '__wpcf_unchecked' ) {
            return $content;
        } else if ( $params['state'] == 'unchecked'
                && $option['value'] == '__wpcf_unchecked' ) {
            return $content;
        } else if ( isset( $params['state'] ) ) {
            return '__wpcf_skip_empty';
        }
    }

    /*
     * 
     * MAIN settings
     * 'db'      - Use 'set_value' as render value
     * 'value'   - Use values set in Group form data 'display_value_selected'
     *                  or 'display_value_not_selected'
     * 
     * Only set if it matches settings.
     * Otherwise leave empty and '__wpcf_skip_empty' will be returned.
     */
    if ( $option['data']['display'] == 'db' ) {
        /*
         * 
         * Only if NOT unchecked!
         */
        if ( !empty( $option['data']['set_value'] )
                && $option['value'] != '__wpcf_unchecked' ) {
            $output = $option['data']['set_value'];
            $output = wpcf_translate( 'field ' . $params['field']['id']
                    . ' option ' . $option['key'] . ' value', $output );
        }
    } else if ( $option['data']['display'] == 'value' ) {
        /*
         * 
         * Checked
         */
        if ( $option['value'] != '__wpcf_unchecked' ) {
            if ( isset( $option['data']['display_value_selected'] ) ) {
                $output = $option['data']['display_value_selected'];
                $output = wpcf_translate( 'field ' . $params['field']['id'] . ' option ' . $option['key'] . ' display value selected',
                        $output );
            }
            /*
             * 
             * 
             * Un-checked
             */
        } else if ( isset( $option['data']['display_value_not_selected'] ) ) {
            $output = $option['data']['display_value_not_selected'];
            $output = wpcf_translate( 'field ' . $params['field']['id'] . ' option ' . $option['key'] . ' display value not selected',
                    $output );
        }
    }

    if ( empty( $output ) ) {
        return '__wpcf_skip_empty';
    }

    return $output;
}

/**
 * This marks child posts checkboxes.
 * 
 * Because if all unchecked, on submit there won't be any data.
 * 
 * @param string $form
 * @param type $cf
 * @return string
 */
function wpcf_filds_checkboxes_relationship_form_filter( $form, $cf ) {
    if ( $cf->cf['type'] == 'checkboxes' ) {
        $form[wpcf_unique_id( serialize( $cf ) . 'rel_child' )] = array(
            '#type' => 'hidden',
            '#name' => '_wpcf_check_checkboxes[' . $cf->post->ID . ']['
            . $cf->slug . ']',
            '#value' => '1'
        );
    }
    return $form;
}