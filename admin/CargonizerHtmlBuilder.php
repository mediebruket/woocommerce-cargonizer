<?php

class CargonizerHtmlBuilder{

  public static function buildInput( $args ){
    $attributes = null;
    foreach ($args as $attr => $value) {
      $attributes .= sprintf(' %s="%s" ', $attr, $value );
    }

    printf('<input %s/>', $attributes );
  }

  public static function buildNavigation( $items ){
    return '<ul class="nav nav-tabs" role="tablist">'.$items.'</ul>';
  }


  public static function buildNavItem( $text, $tab, $active = null ){
    return sprintf('<li class="nav-item"><a class="nav-link %s" href="%s" role="tab" data-toggle="tab" aria-controls="%s">%s</a></li>', (($active) ? 'active': ''),  '#'.$tab, $tab, $text );
  }


  public static function buildTab( $id, $content, $class=null ){
    $aria = ' aria-labelledby="'.$id.'-tab" ';

    return sprintf('<div class="tab-pane wcc-tab fade %s" id="%s" role="tabpanel" %s>%s</div>', $class, $id, $aria, $content );
  }


  public static function buildLabel( $text, $for=null, $css = 'mb-admin-label inline' ){
    printf('<label class="%s" for="%s">%s</label>', $css, $for, $text );
  }


  public static function buildDesc( $text, $css = 'mb-field-desc' ){
    printf( '<div><span class="%s"></span>%s</div>', $css, $text );
  }


  public static function buildOption( $option, $default_value=null ){?>
    <div class="mb-field-row <?php echo gi($option, 'wrap'); ?>">

      <?php if( $option['type'] == 'text' or $option['type'] == 'number'):

        self::buildLabel( $option['label'], $option['name'], 'mb-admin-label inline' );

        if (isset($option['desc']) && trim($option['desc']) ){
          self::buildDesc( $option['desc'] );
        }

        $args =
          array(
            'type'      => gi($option, 'type'),
            'name'      => gi($option, 'name'),
            'id'        => gi($option, 'name'),
            'value'     => gi($option, 'value'),
            'class'     => gi($option, 'css'),
            'max'       => gi($option, 'max'),
            'min'       => gi($option, 'min'),
            'size'      => 50,
          );

        if ( isset($option['readonly']) && $option['readonly'] ){
          $args['readonly']  = 'readonly';
        }

        self::buildInput( $args );
        ?>
      <?php endif; ?>


      <?php if( $option['type'] == 'date'):
       self::buildLabel( $option['label'], $option['name'], 'mb-admin-label inline' );

        if (isset($option['desc']) && trim($option['desc']) ){
          self::buildDesc( $option['desc'] );
        }

        self::buildInput(
            array(
              'type'  => gi($option, 'type'),
              'name'  => gi($option, 'name'),
              'id'    => gi($option, 'name'),
              'value' => gi($option, 'value'),
              'class'   => ' hasDatepicker datepicker '. gi($option, 'css'),
              'size'  => 50,
            )
          )
        ?>
      <?php endif; ?>

       <?php if( $option['type'] == 'checkbox'): ?>
       <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label inline' ); ?>
        <?php
          if (isset($option['desc']) && trim($option['desc']) ){
            self::buildDesc( $option['desc'] );
          }

          $checked = null;
          if ( $option['option'] == $option['value'] or $option['value'] == 'on' ){
            $checked = ' checked="checked" ';
          }
        ?>
        <input type="<?php echo $option['type']; ?>" name="<?php echo $option['name']; ?>" id="<?php echo $option['name']; ?>" value="<?php echo $option['option']; ?>" <?php echo $checked; ?> />
      <?php endif; ?>


      <?php if( $option['type'] == 'multiple_checkbox_2'): ?>
        <div >
          <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label inline' ); ?>

          <?php if ( isset($option['desc'])): ?>
            <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
          <?php endif; ?>

          <?php
          // _log( $option['options'] );
          foreach ( $option['options'] as $key => $o) {
            if ( isset($o['types']) && is_array($o['types']) && !empty($o['types']) ){
              foreach ($o['types'] as $type_key => $type){
                $id = uniqid();
                $checked  = null;
                $type_value = $o['identifier']."|".$type_key;

                if ( $type_value == $option['value'] ){
                  $checked = ' checked="checked" ';
                }

                printf(
                  '<div class="mb-option-row"><input type="checkbox" id="%s" name="%s[]" class="wcc-carrier-products" value="%s" %s /><label for="%s">%s</label></div>',
                  $id, $option['name'], $type_value, $checked, $id, $o['name']." (".$type.")"
                  );
              }
            }
          }
          ?>
        </div>
      <?php endif; ?>


      <?php if( $option['type'] == 'history'): ?>
      <table class="table table-striped table-bordered" id="history">
        <?php if ( isset($option['options']) && is_array($option['options']) ): ?>
          <thead>
            <tr><?php foreach ($option['options'] as $key => $name) {
                printf('<th>%s</th>', $name);
              }?></tr>
          </thead>

          <tbody>
            <?php if ( is_array($option['value']) ): ?>
            <?php
              foreach ($option['value'] as $key => $h) {
                 printf('<tr>
                <td class="id">%s</td>
                <td class="created-at">%s</td>
                <td class="tracking-code">%s</td>
                <td class="tracking-url"><a href="%s">Tracking url</a></td>
                <td class="consignment-pdf"><a href="%s">PDF</a></td>
               </tr>',
               $h['consignment_id'],
               $h['created_at'], //2017-08-18T11:42:56Z
               $h['consignment_tracking_code'], // 40170712190101741122
               $h['consignment_tracking_url'], //sporing.bring.no/sporing.html?q=40170712190101741122&layout=standalone
               $h['consignment_pdf'] // http://sandbox.cargonizer.no/consignments/label_pdf?consignment_ids%5B%5D=16233
               );
              }
            ?>
            <?php endif; ?>
          </tbody>
          <?php endif; ?>
      </table>
      <?php endif; ?>



      <?php if( $option['type'] == 'products'): ?>
      <table class="table table-striped table-bordered" id="products">
        <?php if ( isset($option['options']) && is_array($option['options']) ): ?>
          <thead>
            <tr><?php foreach ($option['options'] as $key => $name) {
                printf('<th>%s</th>', $name);
              }?></tr>
          </thead>

          <tbody>
            <?php _log($option['value']); ?>
            <?php if ( is_array($option['value']) ): ?>
            <?php
              foreach ($option['value'] as $key => $p) {
                printf('<tr>
                  <td class="product-id">%s</td>
                  <td class="product-name">%s</td>
                  <td class="product-count">%s</td>
                </tr>',
                $p['product_id'],
                $p['name'],
                $p['quantity']
               );
              }
            ?>
            <?php endif; ?>
          </tbody>
          <?php endif; ?>
      </table>
      <?php endif; ?>


      <?php if( $option['type'] == 'table'): ?>
       <table class="table table-striped table-bordered parcel-items " id="<?php echo $option['name']; ?>">
          <?php if ( isset($option['options']) && is_array($option['options']) ): ?>
          <thead>
            <tr><?php foreach ($option['options'] as $key => $name) {
                printf('<th>%s</th>', $name);
              }?></tr>
          </thead>
          <?php endif; ?>

          <tbody>
          <?php if ( isset($option['value']) && is_array($option['value']) ): ?>
            <?php foreach ($option['value'] as $key => $package) {
              printf('<tr>
                <td class="id">%s</td>
                <td class="package-amount">%s</td>
                <td class="package-type">%s</td>
                <td class="package-desc">%s</td>
                <td class="package-weight">%s</td>
                <td class="package-height">%s</td>
                <td class="package-length">%s</td>
                <td class="package-width">%s</td>
               </tr>',
               $package['id'],
               $package['parcel_amount'],
               $package['parcel_type'],
               $package['parcel_description'],
               $package['parcel_weight'],
               $package['parcel_height'],
               $package['parcel_length'],
               $package['parcel_width']
               );
            }
            ?>
          <?php endif; ?>
          </tbody>
        </table>
        <button type="button" data-target="<?php echo $option['name']; ?>" class="btn btn-dark js-add-package-row"><?php _e('Add row'); ?></button>
      <?php endif; ?>


      <?php if( $option['type'] == 'multiple_checkbox'): ?>
        <div>
          <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label inline' ); ?>

          <?php if ( isset($option['desc'])): ?>
            <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
          <?php endif; ?>

          <?php

          if ( !empty($option['options']) ){
            foreach ( $option['options'] as $o_id => $o_name) {
              $id = uniqid();
              $checked = null;

              if ( is_numeric( array_search($o_id, $option['value'])  ) ){
                $checked = ' checked="checked" ';
              }

              printf(
                '<div class="mb-option-row">
                <input type="checkbox" id="%s" name="%s[]" value="%s" %s /><label for="%s">%s</label></div>',
                $id, $option['name'], $o_id, $checked, $id, $o_name
                );
            }
          }
          else{
             self::buildInput(
              array(
                'type'  => 'hidden',
                'name'  => gi($option, 'name'),
                'id'    => gi($option, 'name'),
                'value' => ''
              )
            );
          }

          ?>
        </div>
      <?php endif; ?>

      <?php if( $option['type'] == 'textarea'): ?>
      <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label' ); ?>
        <textarea name="<?php echo $option['name']; ?>" id="<?php echo $option['name']; ?>" class="wcc-textarea" rows="4" cols="50"><?php echo $option['value']; ?></textarea>
      <?php endif; ?>

      <?php if( $option['type'] == 'select'): ?>
        <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label' ); ?>
        <?php if ( isset($option['desc']) ): ?>
          <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
        <?php endif; ?>
        <select name="<?php echo $option['name'] ?>" id="<?php echo $option['name'] ?>" <?php echo (isset($option['attr']) ? $option['attr']: null); ?> >
            <?php foreach ($option['options'] as $value => $title) {
              printf('<option value="%s" %s>%s</option>',  $value, selected( $value, $option['value'], $echo=false ), $title );
            }
          ?>
        </select>
      <?php endif; ?>


      <?php if( $option['type'] == 'vue_select'): ?>
        <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label' ); ?>
        <?php printf('<%s %s>%s</%s>', $option['container'], str_replace('@name@', $option['name'], $option['attr']), $option['options'], $option['container'] ); ?>
      <?php endif; ?>


      <?php if( $option['type'] == 'vue_checkboxes'): ?>
        <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label' ); ?>
        <?php printf('<%s>%s</%s>', $option['container'], str_replace('@name@', $option['name'], $option['options']), $option['container'] ); ?>
      <?php endif; ?>

    </div>
    <?php
  }

} // end of class