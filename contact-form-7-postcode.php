<?php
/*
Plugin Name: Contact Form 7 Postcode
Plugin URI: https://github.com/markhall1971/Contact-Form-7-Postcode
Description: Add UK postcode validation to Contact Form 7 plugin.
Author: Mark Hall
Author URI: http://sarkymarky.com
Version: 1.1
Text Domain: contact-form-7-postcode
*/

add_action( 'admin_init', 'wpcf7_postcode_has_parent_plugin' );

function wpcf7_postcode_has_parent_plugin() 
{
	if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) 
	{
		add_action( 'admin_notices', 'wpcf7_postcode_nocf7_notice' );

		deactivate_plugins( plugin_basename( __FILE__ ) ); 

		if ( isset( $_GET['activate'] ) ) 
		{
			unset( $_GET['activate'] );
		}
	}
}

function wpcf7_postcode_nocf7_notice() 
{ 
?>
    <div class="error">
    	<p>
    		<?php printf( 
				__( '%s must be installed and activated for the Contact Form 7 Postcode plugin to work', 'contact-form-7-postcode' ),
				'<a href="' . admin_url( 'plugin-install.php?tab=search&s=contact+form+7' ) . '">Contact Form 7</a>'
			 ); ?>
		</p>
    </div>
    <?php
}

add_action( 'wpcf7_init', 'wpcf7_add_shortcode_postcode', 10 );

function wpcf7_add_shortcode_postcode() 
{
	wpcf7_add_shortcode( array( 'postcode', 'postcode*' ), 'wpcf7_postcode_shortcode_handler', true );
}

function wpcf7_postcode_shortcode_handler( $tag ) 
{
	$tag = new WPCF7_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$class = wpcf7_form_controls_class( 'text' );

	$validation_error = wpcf7_get_validation_error( $tag->name );

	if ( $validation_error )
		$class .= ' wpcf7-not-valid';

	$atts = array();
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_option( 'id', 'id', true );
	$atts['name'] = $tag->name;
	$atts['type'] = 'text';
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );
	$atts['validation_error'] = $validation_error;
	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$value = (string) reset( $tag->values );
	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	if ( $tag->is_required() )
		$atts['aria-required'] = 'true';
	$inputid = ( !empty( $atts['id'] ) ) ? 'id="' . $atts['id'] . '" ' : '';

	$atts = wpcf7_format_atts( $atts );

	$html = sprintf( 
		'<span class="wpcf7-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );
	return $html;
}

add_filter( 'wpcf7_validate_postcode', 'wpcf7_postcode_validation_filter' , 10, 2 );
add_filter( 'wpcf7_validate_postcode*', 'wpcf7_postcode_validation_filter' , 10, 2 );

function wpcf7_postcode_validation_filter ( $result, $tag ) 
{
	$tag = new WPCF7_Shortcode( $tag );

	$name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( wp_unslash( strtr( ( string ) $_POST[$name], "\n", " " ) ) )
		: '';
	
	if ( $tag->is_required() && '' == $value ) 
	{
		$result['valid'] = false;
		$result['reason'] = array( $name => 'Please enter a postcode' );
	}
	else
	{
		if ( !is_postcode( $value ) ) 
		{
			$result['valid'] = false;
			$result['reason'] = array( $name => 'Postcode is not valid' );
		}
		else
		{
			$result['valid'] = true;
			$result['reason'] = array( $name => '' );
		}
	}

	return $result;
}

add_action( 'wpcf7_admin_init', 'wpcf7_add_tag_generator_postcode', 35 );

function wpcf7_add_tag_generator_postcode() 
{
	if ( class_exists( 'WPCF7_TagGenerator' ) ) 
	{
		$tag_generator = WPCF7_TagGenerator::get_instance();
		$tag_generator->add( 'postcode', __( 'Postcode', 'contact-form-7' ), 'wpcf7_tg_pane_postcode' );
	}
	else if ( function_exists( 'wpcf7_add_tag_generator' ) ) 
	{
		wpcf7_add_tag_generator( 'postcode', __( 'Postcode', 'wpcf7' ),	'wpcf7-tg-pane-postcode', 'wpcf7_tg_pane_postcode' );
	}
}

function wpcf7_tg_pane_postcode( $contact_form, $args = '' ) 
{
	if ( class_exists( 'WPCF7_TagGenerator' ) ) 
	{
		$args = wp_parse_args( $args, array() );
		$description = __( "Generate a form-tag for a spam-stopping postcode field. For more details, see %s.", 'contact-form-7-postcode' );
		$desc_link = '<a href="https://github.com/markhall1971/Contact-Form-7-Postcode" target="_blank">' . __( 'CF7 Postcode', 'contact-form-7-postcode' ) . '</a>';
		?>
		<div class="control-box">
			<fieldset>
				<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>
				<table class="form-table"><tbody>
					<tr>
						<th scope="row">
							<?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
								<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?></label>
						</th>
						<td>
							<input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /><br>
							<em><?php echo esc_html( __( 'For better security, change "postcode" to something less bot-recognizable.', 'contact-form-7-postcode' ) ); ?></em>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'ID ( optional )', 'contact-form-7' ) ); ?></label>
						</th>
						<td>
							<input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class ( optional )', 'contact-form-7' ) ); ?></label>
						</th>
						<td>
							<input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" />
						</td>
					</tr>

				</tbody></table>
			</fieldset>
		</div>

		<div class="insert-box">
			<input type="text" name="postcode" class="tag code" readonly="readonly" onfocus="this.select()" />

			<div class="submitbox">
				<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'contact-form-7' ) ); ?>" />
			</div>

			<br class="clear" />
		</div>
	<?php 
	} 
	else 
	{ 
	?>
		<div id="wpcf7-tg-pane-postcode" class="hidden">
			<form action="">
				<table>
					<tr>
						<td>
							<?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?>
								<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'contact-form-7' ) ); ?></legend>
								<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'contact-form-7' ) ); ?></label>
						</td>
					</tr>
					<tr>
						<td>
							<?php echo esc_html( __( 'Name', 'contact-form-7' ) ); ?><br />
							<input type="text" name="name" class="tg-name oneline" /><br />
							<em><small><?php echo esc_html( __( 'For better security, change "postcode" to something less bot-recognizable.', 'contact-form-7-postcode' ) ); ?></small></em>
						</td>
						<td></td>
					</tr>
					
					<tr>
						<td colspan="2"><hr></td>
					</tr>

					<tr>
						<td>
							<?php echo esc_html( __( 'ID ( optional )', 'contact-form-7' ) ); ?><br />
							<input type="text" name="id" class="idvalue oneline option" />
						</td>
						<td>
							<?php echo esc_html( __( 'Class ( optional )', 'contact-form-7' ) ); ?><br />
							<input type="text" name="class" class="classvalue oneline option" />
						</td>
					</tr>

					<tr>
						<td colspan="2"><hr></td>
					</tr>			
				</table>
				
				<div class="tg-tag"><?php echo esc_html( __( "Copy this code and paste it into the form left.", 'contact-form-7-postcode' ) ); ?><br /><input type="text" name="postcode" class="tag" readonly="readonly" onfocus="this.select()" /></div>
			</form>
		</div>
	<?php 
	}
}

function is_postcode( &$toCheck ) 
{
	// Permitted letters depend upon their position in the postcode.
	$alpha1 = "[abcdefghijklmnoprstuwyz]";				// Character 1
	$alpha2 = "[abcdefghklmnopqrstuvwxy]";				// Character 2
	$alpha3 = "[abcdefghjkpmnrstuvwxy]";				// Character 3
	$alpha4 = "[abehmnprvwxy]";							// Character 4
	$alpha5 = "[abdefghjlnpqrstuwxyz]";					// Character 5
	
	// Expression for postcodes: AN NAA, ANN NAA, AAN NAA, and AANN NAA with a space
	$pcexp[0] = '/^(' . $alpha1 . '{1}' . $alpha2 . '{0,1}[0-9]{1,2})(()[[:space:]]{0,})([0-9]{1}' . $alpha5 . '{2})$/';

	// Expression for postcodes: ANA NAA
	$pcexp[1] =	'/^(' . $alpha1 . '{1}[0-9]{1}' . $alpha3 . '{1})([[:space:]]{0,})([0-9]{1}' . $alpha5 . '{2})$/';

	// Expression for postcodes: AANA NAA
	$pcexp[2] =	'/^(' . $alpha1 . '{1}' . $alpha2 . '{1}[0-9]{1}' . $alpha4 . ')([[:space:]]{0,})([0-9]{1}' . $alpha5 . '{2})$/';
	
	// Exception for the special postcode GIR 0AA
	$pcexp[3] =	'/^(gir)([[:space:]]{0,})(0aa)$/';
	
	// Standard BFPO numbers
	$pcexp[4] = '/^(bfpo)([[:space:]]{0,})([0-9]{1,4})$/';
	
	// c/o BFPO numbers
	$pcexp[5] = '/^(bfpo)([[:space:]]{0,})(c\/o([[:space:]]{0,})[0-9]{1,3})$/';
	
	// Overseas Territories
	$pcexp[6] = '/^([a-z]{4})([[:space:]]{0,})(1zz)$/';
	
	// Anquilla
	$pcexp[7] = '/^ai-2640$/';

	// Load up the string to check, converting into lowercase
	$postcode = strtolower( $toCheck );

	// Assume we are not going to find a valid postcode
	$valid = false;
	
	// Check the string against the six types of postcodes
	foreach ( $pcexp as $regexp ) 
	{
		if ( preg_match( $regexp,$postcode, $matches ) )
		{
			
			// Load new postcode back into the form element	
			$postcode = strtoupper ( $matches[1] . ' ' . $matches [3] );
			
			// Take account of the special BFPO c/o format
			$postcode = preg_replace ( '/C\/O( [[:space:]]{0,} )/', 'c/o ', $postcode );
			
			// Take acount of special Anquilla postcode format ( a pain, but that's the way it is )
			if ( preg_match( $pcexp[7],strtolower( $toCheck ), $matches ) ) $postcode = 'AI-2640';			
			
			// Remember that we have found that the code is valid and break from loop
			$valid = true;
			break;
		}
	}
	if ( $valid )
	{
		$toCheck = $postcode; 
		return true;
	} 
	else
	{
		return false;
	}
}
