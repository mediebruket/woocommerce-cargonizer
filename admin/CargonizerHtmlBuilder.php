<?php

class CargonizerHtmlBuilder{

  public static function buildInput( $args ){
    $attributes = null;
    foreach ($args as $attr => $value) {
      $attributes .= sprintf(' %s="%s" ', $attr, $value );
    }

    printf('<input %s/>', $attributes );
  }


  public static function buildLabel( $text, $for=null, $css = 'mb-admin-label inline' ){
    printf('<label class="%s" for="%s">%s</label>', $css, $for, $text );
  }

  public static function buildDesc( $text, $css = 'mb-field-desc' ){
    printf( '<div><span class="%s"></span>%s</div>', $css, $text );
  }


  public static function buildOption( $option, $default_value=null ){?>
    <div class="mb-field-row">

      <?php
      if( $option['type'] == 'text'):

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
              'class'   => gi($option, 'css'),
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
        ?>
        <input type="<?php echo $option['type']; ?>" name="<?php echo $option['name']; ?>" id="<?php echo $option['name']; ?>" value="<?php echo $option['option']; ?>" <?php  checked( $option['option'], $option['value'] ); ?> >
      <?php endif; ?>


      <?php if( $option['type'] == 'multiple_checkbox'): ?>
        <div >
          <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label inline' ); ?>

          <?php if ( isset($option['desc'])): ?>
            <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
          <?php endif; ?>

          <?php
          // _log( $option['options'] );
          foreach ( $option['options'] as $key => $o) {
            if ( isset($o['types']) && is_array($o['types']) && !empty($o['types']) ){
              foreach ($o['types'] as $type_key => $type) {

                $id = uniqid();
                $checked  = null;
                $type_value = $o['identifier']."|".$type_key;

                if ( is_numeric(array_search($type_value, $option['value'])) ){
                  $checked = ' checked="checked" ';
                }

                printf(
                  '<div class="mb-option-row"><input type="checkbox" id="%s" name="%s[]" value="%s" %s /><label for="%s">%s</label></div>',
                  $id, $option['name'], $type_value, $checked, $id, $o['name']." (".$type.")"
                  );
              }
            }
          }
          ?>
        </div>
      <?php endif; ?>

      <?php if( $option['type'] == 'textarea'): ?>
      <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label' ); ?>
        <textarea name="<?php echo $option['name']; ?>" id="<?php echo $option['name']; ?>" class="wcc-textarea"><?php echo $option['value']; ?></textarea>
      <?php endif; ?>

      <?php if( $option['type'] == 'select'): ?>
        <?php self::buildLabel( $option['label'], $option['name'], 'mb-admin-label' ); ?>
        <?php if ( isset($option['desc']) ): ?>
          <div><span class="mb-field-desc"><?php echo $option['desc']; ?></span></div>
        <?php endif; ?>
        <select name="<?php echo $option['name'] ?>" id="<?php echo $option['name'] ?>">
            <?php foreach ($option['options'] as $value => $title) {
              printf('<option value="%s" %s>%s</option>',  $value, selected( $value, $option['value'], $echo=false ), $title );
            }
          ?>
        </select>
      <?php endif; ?>

    </div>
    <?php
  }

}