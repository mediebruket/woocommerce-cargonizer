<?php

if( function_exists('acf_add_local_field_group') ):

$readonly = false;
if ( isset($_GET['post']) && is_numeric($_GET['post']) ){
  if ( get_post_meta( $_GET['post'], 'is_cargonized', true ) ){
    // $readonly = true;
  }
}


acf_add_local_field_group(
  array(
    'key' => 'group_56cc4de510b34',
    'title' => 'Cargonizer',
    'fields' => array(
        array(
          'key' => 'field_56cc5865b6e45',
          'label' => 'Parcels',
          'name' => '',
          'type' => 'tab',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array( 'width' => '', 'class' => '', 'id' => '', ),
          'placement' => 'top',
          'endpoint' => 0,
        ),
        array(
          'key' => 'field_593e33287a232',
          'label' => 'Recurring consignment',
          'name' => 'parcel-is-recurring',
          'type' => 'true_false',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '', ),
          'message' => '',
          'default_value' => 0,
        ),
        array(
          'key' => 'field_56cd64c524655',
          'label' => 'Printer',
          'name' => 'parcel_printer',
          'type' => 'select',
          'instructions' => 'If empty, setup api settings',
          'required' => 1,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'choices' => array(0 => 'select printer'),
          'default_value' => array(),
          'allow_null' => 0,
          'multiple' => 0,
          'ui' => 0,
          'ajax' => 0,
          'placeholder' => '',
          'disabled' => 0,
          'readonly' => 1,
        ),
        array(
          'key' => 'field_56cd64c524656',
          'label' => 'Carrier',
          'name' => 'transport_agreement',
          'type' => 'select',
          'instructions' => 'If empty, setup api settings',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'choices' => array(0 => 'select carrier'),
          'default_value' => array(),
          'allow_null' => 0,
          'multiple' => 0,
          'ui' => 0,
          'ajax' => 0,
          'placeholder' => '',
          'disabled' => 0,
          'readonly' => 1,
        ),
        array(
          'key' => 'field_56cec446a4498',
          'label' => 'Type',
          'name' => 'parcel_type',
          'type' => 'select',
          'instructions' => 'Select product',
          'required' => 1,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'choices' => array( 0 => 'select type' ),
          'default_value' => array(),
          'allow_null' => 0,
          'multiple' => 0,
          'ui' => 0,
          'ajax' => 0,
          'placeholder' => '',
          'disabled' => 0,
          'readonly' => $readonly,
        ),
        array(
          'key' => 'field_59086fd6633fa',
          'label' => 'Services',
          'name' => 'parcel_services',
          'type' => 'checkbox',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'choices' => array(),
          'default_value' => array(),
          'layout' => 'horizontal',
          'toggle' => 0,
          'disabled' => 0,
          'readonly' => $readonly,
        ),
        array(
          'key' => 'field_56cead8e7fd30',
          'label' => 'Message',
          'name' => 'message_consignee',
          'type' => 'textarea',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'default_value' => null,
          'placeholder' => '',
          'maxlength' => '',
          'rows' => '',
          'new_lines' => 'wpautop',
          'readonly' => 0,
          'disabled' => 0,
        ),
        array(
          'key' => 'field_56cc575e16e1c',
          'label' => 'Packages',
          'name' => 'consignment_items',
          'type' => 'repeater',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => null,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'collapsed' => '',
          'min' => '',
          'max' => '',
          'layout' => 'table',
          'button_label' => 'Add Row',
          'sub_fields' => array(
            array(
              'key' => 'field_56cc57fcd9178',
              'label' => 'Count',
              'name' => 'parcel_amount',
              'type' => 'number',
              'instructions' => '',
              'required' => 0,
              'conditional_logic' => 0,
              'wrapper' => array('width' => '','class' => '', 'id' => '',),
              'default_value' => 1,
              'placeholder' => '',
              'prepend' => '',
              'append' => '',
              'min' => 1,
              'max' => '',
              'step' => '',
              'disabled' => 0,
              'readonly' => $readonly,
            ),
            array(
              'key' => 'field_56cec446a4499',
              'label' => 'Type',
              'name' => 'parcel_package_type',
              'type' => 'select',
              'instructions' => 'Set a custom parcel type.',
              'required' => 0,
              'conditional_logic' => 0,
              'wrapper' => array('width' => '','class' => '','id' => '',),
              'choices' => array(0 => 'select parcel type',),
              'default_value' => array(),
              'allow_null' => 0,
              'multiple' => 0,
              'ui' => 0,
              'ajax' => 0,
              'placeholder' => '',
              'disabled' => 0,
              'readonly' => $readonly,
            ),
            array(
              'key' => 'field_56cc578216e1d',
              'label' => 'Description',
              'name' => 'parcel_description',
              'type' => 'text',
              'instructions' => '',
              'required' => 0,
              'conditional_logic' => 0,
              'wrapper' => array('width' => '','class' => '','id' => '',),
              'default_value' => '',
              'placeholder' => '',
              'prepend' => '',
              'append' => '',
              'maxlength' => '',
              'disabled' => 0,
              'readonly' => $readonly,
            ),
            array(
              'key' => 'field_56cc4de94d2c6',
              'label' => 'Weight (kg)',
              'name' => 'parcel_weight',
              'type' => 'text',
              'instructions' => '',
              'required' => 0,
              'conditional_logic' => 0,
              'wrapper' => array('width' => '','class' => '','id' => '',),
              'default_value' => '',
              'placeholder' => '',
              'prepend' => '',
              'append' => '',
              'maxlength' => '',
              'disabled' => 0,
              'readonly' => $readonly,
            ),
            array(
              'key' => 'field_56cc4e196d60a',
              'label' => 'Height (cm)',
              'name' => 'parcel_height',
              'type' => 'text',
              'instructions' => '',
              'required' => 1,
              'conditional_logic' => 0,
              'wrapper' => array('width' => '','class' => '','id' => '',),
              'default_value' => '',
              'placeholder' => '',
              'prepend' => '',
              'append' => '',
              'maxlength' => '',
              'disabled' => 0,
              'readonly' => $readonly,
            ),
            array(
              'key' => 'field_56cc4dfc6d608',
              'label' => 'Length (cm)',
              'name' => 'parcel_length',
              'type' => 'text',
              'instructions' => '',
              'required' => 1,
              'conditional_logic' => 0,
              'wrapper' => array('width' => '','class' => '','id' => '',),
              'default_value' => '',
              'placeholder' => '',
              'prepend' => '',
              'append' => '',
              'maxlength' => '',
              'readonly' => 0,
              'disabled' => 0,
              'readonly' => $readonly,
            ),
            array(
              'key' => 'field_56cc4e0a6d609',
              'label' => 'Width (cm)',
              'name' => 'parcel_width',
              'type' => 'text',
              'instructions' => '',
              'required' => 1,
              'conditional_logic' => 0,
              'wrapper' => array('width' => '','class' => '','id' => '',),
              'default_value' => '',
              'placeholder' => '',
              'prepend' => '',
              'append' => '',
              'maxlength' => '',
              'disabled' => 0,
              'readonly' => $readonly,
            ),
      ),
    ),
    array(
      'key' => 'field_593a7124527ce',
      'label' => 'Shipping date',
      'name' => 'parcel_shipping_date',
      'type' => 'date_picker',
      'instructions' => 'Leave empty if the consignment is to be created today',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'display_format' => 'd/m/Y',
      'return_format' => 'Ymd',
      'first_day' => 1,
    ),
    array(
      'key' => 'field_56cd6cad3cbf3',
      'label' => 'Create consignment now',
      'name' => 'create_consignment',
      'type' => 'true_false',
      'instructions' => 'To create a consignment on cargonizer.no:<br/>- Will only work if shipping date is empty<br/>- Enable this checkox if carrier is selected and the parcel(s) added.',
      'required' => 0,
      'conditional_logic' => null,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'message' => '',
      'default_value' => 0,
    ),
    array(
      'key' => 'field_56cee5f87deaa',
      'label' => 'Confirmation',
      'name' => 'confirmation',
      'type' => 'tab',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'placement' => 'top',
      'endpoint' => 0,
    ),
    array(
      'key' => 'field_56cee60ac7cd0',
      'label' => 'Date',
      'name' => 'consignment_created_at',
      'type' => 'text',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'default_value' => '',
      'placeholder' => '',
      'prepend' => '',
      'append' => '',
      'maxlength' => '',
      'readonly' => 1,
      'disabled' => 0,
    ),
    array(
      'key' => 'field_56cee621c7cd1',
      'label' => 'Consignment id',
      'name' => 'consignment_id',
      'type' => 'text',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'default_value' => '',
      'placeholder' => '',
      'prepend' => '',
      'append' => '',
      'maxlength' => '',
      'readonly' => 1,
      'disabled' => 0,
    ),
    // array(
    //   'key' => 'field_56cee621c7cd12',
    //   'label' => 'Estimated freight costs',
    //   'name' => 'consignment_estimated_costs',
    //   'type' => 'text',
    //   'instructions' => '',
    //   'required' => 0,
    //   'conditional_logic' => 0,
    //   'wrapper' => array('width' => '','class' => '','id' => '',),
    //   'default_value' => '',
    //   'placeholder' => '',
    //   'prepend' => '',
    //   'append' => '',
    //   'maxlength' => '',
    //   'readonly' => 1,
    //   'disabled' => 0,
    // ),
    array(
      'key' => 'field_56cee62ec7cd2',
      'label' => 'Tracking code',
      'name' => 'consignment_tracking_code',
      'type' => 'text',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'default_value' => '',
      'placeholder' => '',
      'prepend' => '',
      'append' => '',
      'maxlength' => '',
      'readonly' => 1,
      'disabled' => 0,
    ),
    array(
      'key' => 'field_56cee6bd6fb1e',
      'label' => 'Tracking url',
      'name' => 'consignment_tracking_url',
      'type' => 'text',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'default_value' => '',
      'placeholder' => '',
      'prepend' => '',
      'append' => '',
      'maxlength' => '',
      'readonly' => 1,
      'disabled' => 0,
    ),
    array (
      'key' => 'field_56cee6476fb1d',
      'label' => 'Consignment pdf',
      'name' => 'consignment_pdf',
      'type' => 'text',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'default_value' => '',
      'placeholder' => '',
      'prepend' => '',
      'append' => '',
      'maxlength' => '',
      'readonly' => 1,
      'disabled' => 0,
    ),


    // Tab: Recurring consignments
    array(
      'key' => 'field_593e2d38e2b49',
      'label' => 'Recurring consignments',
      'name' => '',
      'type' => 'tab',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => array(
        array(
          array(
            'field' => 'field_593e33287a232',
            'operator' => '==',
            'value' => '1',
          ),
        ),
      ),
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'placement' => 'top',
      'endpoint' => 0,
    ),
    array(
      'key' => 'field_593e32538dbca',
      'label' => 'Copy from parcels',
      'name' => 'parcel-consignment-copy',
      'type' => 'true_false',
      'instructions' => 'Copies <i>carrier, type, services and message</i> from the main parcel',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'message' => '',
      'default_value' => 0,
    ),

    array(
      'key' => 'field_593e2d45e2b4a',
      'label' => 'Interval',
      'name' => 'parcel-recurring-consignment-interval',
      'type' => 'select',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'choices' => array(),
      'default_value' => array( ),
      'allow_null' => 0,
      'multiple' => 0,
      'ui' => 0,
      'ajax' => 0,
      'placeholder' => '',
      'disabled' => 0,
      'readonly' => 0,
    ),

   array (
      'key' => 'field_594bb0e7df9cb',
      'label' => 'Start date',
      'name' => 'parcel_start_date',
      'type' => 'date_picker',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'display_format' => 'd.m.Y',
      'return_format' => 'Ymd',
      'first_day' => 1,
    ),

    array(
      'key' => 'field_593e3056536ce',
      'label' => 'Carrier',
      'name' => 'parcel_recurring_carrier_id',
      'type' => 'select',
      'instructions' => 'If empty, setup api settings',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'choices' => array (),
      'default_value' => array (),
      'allow_null' => 0,
      'multiple' => 0,
      'ui' => 0,
      'ajax' => 0,
      'placeholder' => '',
      'disabled' => 0,
      'readonly' => 0,
    ),
    array(
      'key' => 'field_593e30a5536d0',
      'label' => 'Type',
      'name' => 'parcel-recurring-consignment-type',
      'type' => 'select',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'choices' => array(),
      'default_value' => array(),
      'allow_null' => 0,
      'multiple' => 0,
      'ui' => 0,
      'ajax' => 0,
      'placeholder' => '',
      'disabled' => 0,
      'readonly' => 0,
    ),
    array(
      'key' => 'field_593e30319f667',
      'label' => 'Services',
      'name' => 'parcel-recurring-consignment-services',
      'type' => 'checkbox',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'choices' => array(),
      'default_value' => array(),
      'layout' => 'vertical',
      'toggle' => 0,
    ),
    array(
      'key' => 'field_593e3090536cf',
      'label' => 'Message',
      'name' => 'parcel-consignment-message',
      'type' => 'textarea',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'default_value' => '',
      'placeholder' => '',
      'maxlength' => '',
      'rows' => '',
      'new_lines' => 'wpautop',
      'readonly' => 0,
      'disabled' => 0,
    ),
    array(
      'key' => 'field_593e2ee07bdd4',
      'label' => 'Package',
      'name' => 'parcel-recurring-consignment-items',
      'type' => 'repeater',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array('width' => '','class' => '','id' => '',),
      'min' => '',
      'max' => '',
      'layout' => 'table',
      'button_label' => 'Add Row',
      'sub_fields' => array(
        array(
          'key' => 'field_593e2fc050a5a',
          'label' => 'Amount',
          'name' => 'parcel_recurring_consignment_amount',
          'type' => 'text',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'default_value' => '1',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'maxlength' => '',
          'readonly' => 0,
          'disabled' => 0,
        ),
        array(
          'key' => 'field_593e2fab50a5d',
          'label' => 'Type',
          'name' => 'parcel_recurring_consignment_type',
          'type' => 'select',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'choices' => array(),
          'default_value' => array(),
          'allow_null' => 0,
          'multiple' => 0,
          'ui' => 0,
          'ajax' => 0,
          'placeholder' => '',
          'disabled' => 0,
          'readonly' => 0,
        ),
        array(
          'key' => 'field_593e2fc050a5e',
          'label' => 'Description',
          'name' => 'parcel_recurring_consignment_description',
          'type' => 'text',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'maxlength' => '',
          'readonly' => 0,
          'disabled' => 0,
        ),
        array(
          'key' => 'field_593e2fd250a5f',
          'label' => 'Weight (kg)',
          'name' => 'parcel_recurring_consigment_weight',
          'type' => 'number',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'min' => '',
          'max' => '',
          'step' => '',
          'readonly' => 0,
          'disabled' => 0,
        ),
        array(
          'key' => 'field_593e2fec50a60',
          'label' => 'Height (cm)',
          'name' => 'parcel_recurring_consignment_height',
          'type' => 'number',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'min' => '',
          'max' => '',
          'step' => '',
          'readonly' => 0,
          'disabled' => 0,
        ),
        array(
          'key' => 'field_593e300750a61',
          'label' => 'Length (cm)',
          'name' => 'parcel_recurring_consignment_length',
          'type' => 'number',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'min' => '',
          'max' => '',
          'step' => '',
          'readonly' => 0,
          'disabled' => 0,
        ),
        array(
          'key' => 'field_593e301b50a62',
          'label' => 'Width (cm)',
          'name' => 'parcel_recurring_consignment_width',
          'type' => 'number',
          'instructions' => '',
          'required' => 0,
          'conditional_logic' => 0,
          'wrapper' => array('width' => '','class' => '','id' => '',),
          'default_value' => '',
          'placeholder' => '',
          'prepend' => '',
          'append' => '',
          'min' => '',
          'max' => '',
          'step' => '',
          'readonly' => 0,
          'disabled' => 0,
        ),
      ),
    ),


  ),
  'location' => array(
    array(
      array(
        'param' => 'post_type',
        'operator' => '==',
        'value' => 'shop_order',
      ),
    ),
  ),
  'menu_order' => 0,
  'position' => 'normal',
  'style' => 'default',
  'label_placement' => 'top',
  'instruction_placement' => 'label',
  'hide_on_screen' => '',
  'active' => 1,
  'description' => '',
));

endif;