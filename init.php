<?php
add_action( 'admin_enqueue_scripts', 'add_enqueue_select2_cpt_jquery_func' );
function add_enqueue_select2_cpt_jquery_func() {
	global $post;
	if( $post->post_type !== 'multi_add_to_cart' ){	return false; }
    wp_register_style( 'select2css', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.css', false, '1.0', 'all' );
    wp_register_script( 'select2', '//cdnjs.cloudflare.com/ajax/libs/select2/3.4.8/select2.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_style( 'select2css' );
    wp_enqueue_script( 'select2' );
}

add_action( 'admin_head', 'add_select2jquery_inline_cpt_func' );
function add_select2jquery_inline_cpt_func() {
global $post;
if( $post->post_type !== 'multi_add_to_cart' ){	return false; }
?>
<style type="text/css">
.select2-container {margin: 0 2px 0 2px;}
	.wc-enhanced-select{width:100%;display:block;clear:both;}
</style>
<script type='text/javascript'>
jQuery(document).ready(function ($) {
	var wc_enhanced_select = $( '.wc-enhanced-select' );
    if( wc_enhanced_select.length > 0 ) {
        wc_enhanced_select.select2();
    }
});
</script>
    <?php
}

/**************************************/

add_action( 'init', 'cpt_create_btn_multi_add_to_cart_func' );
function cpt_create_btn_multi_add_to_cart_func() {
    register_post_type( 'multi_add_to_cart',
        array(
            'labels' => array(
                'name' => __( 'Multi Añadir a Carrito' ),
                'singular_name' => __( 'Multi Add to cart' ),
				'add_new'        => __( 'Crear Nuevo Shortcode', 'textdomain' ),
				'add_new_item'   => __( 'Crear Nuevo Shortcode' ),
				'edit_item'             => __( 'Editar', 'textdomain' ),
        		'view_item'             => __( 'Ver', 'textdomain' ),
            ),
			'description' => 'Generar boton add to cart mediante shortcode para añadir a carrito multiples productos.',
            'public' => false,
            'has_archive' =>  true,
			'show_in_menu' => false,
            'show_in_rest' => false,
            'query_var' => 'multi_add_to_cart',
            'rewrite' => array('slug' => 'multi_add_to_cart'),
            'supports' => array('title'),
            'show_ui' => true,
			'register_meta_box_cb' => 'additional_input_field'
        )
    );
}

add_filter('manage_multi_add_to_cart_posts_columns', function($columns) {
	$offset = array_search('date', array_keys($columns));
	return array_merge(array_slice($columns, 0, $offset), ['shortcode' => __('Shortcode', 'textdomain')], array_slice($columns, $offset, null));
});

add_action('manage_multi_add_to_cart_posts_custom_column', function($column_key, $post_id) {
	if ($column_key == 'shortcode') {
		$value = get_post_meta($post_id, '_multi_add_to_cart_products', true );
		if($value):
		?>
		<code>[btn_multi_add_to_cart id='<?php echo $post_id; ?>']</code>
		<?php
		/*<input type="text" class="" value="[btn_multi_add_to_cart id='<?php echo $post_id; ?>']" readonly="readonly" />*/
		else:
		echo 'No se ha seleccionado producto(s)';
		endif;
	}
}, 10, 2);

add_action( 'admin_menu', 'add_multi_add_to_cart_menu_func', 30 );
function add_multi_add_to_cart_menu_func() {
    add_submenu_page('woocommerce','Multi Add to cart','Multi Add to cart', 'manage_options', 'edit.php?post_type=multi_add_to_cart');
}

add_filter('enter_title_here', 'title_cpt_placeholder_multi_add_to_cart_func' , 20 , 2 );
function title_cpt_placeholder_multi_add_to_cart_func($title , $post){
        if( $post->post_type == 'multi_add_to_cart' ){
            $custom_title = "Título (Opcional)";
            return $custom_title;
        }
        return $title;
}

function additional_input_field(){
add_meta_box('render_cmb_multi_add_to_cart',__( 'Productos Woocommerce', '' ),'render_cmb_multi_add_to_cart_func',get_post_type(),'advanced','high');
}
function render_cmb_multi_add_to_cart_func(){
	global $post;
	
	if ( !class_exists( 'WooCommerce' ) ) { return false; }
	
	$products = wc_get_products( array(
        'numberposts' => -1,
        'post_status' => 'published',
    ));	
	$array_products = [];
	if($products){
		foreach($products as $product){
			$array_products[ intval( $product->get_ID() ) ] = wp_kses_post( $product->get_formatted_name() );
		}
	}
	
	$value = get_post_meta( $post->ID, '_multi_add_to_cart_products', true );

	woocommerce_wp_select( array(
        'id'          => '_multi_add_to_cart_products[]',
        'label'       => __( 'Seleccione producto(s)', 'woocommerce' ),
        'description' => __( 'Indique los productos que desea añadir al carrito a la vez', 'woocommerce' ),
        'desc_tip'    => false,
		'class' => 'wc-enhanced-select wc-product-search',
		'value' => $value,
		'custom_attributes' => array('multiple' => 'multiple'),
        'options'     => $array_products
    ));
	
}

add_action( 'edit_form_after_title', 'display_shortcode_by_create_cpt_func' );
function display_shortcode_by_create_cpt_func(){
	global $post;
	if( $post->post_type !== 'multi_add_to_cart' ){	return false; }	
    echo '<p><code><strong>Copia Shortcode:</strong> [btn_multi_add_to_cart id="'.$post->ID.'"]</p></code>';
}

add_action( 'save_post', 'guardar_campo_multi_add_to_cart_func');
function guardar_campo_multi_add_to_cart_func($post_id){
	global $post;
	if( $post->post_type !== 'multi_add_to_cart' ){	return false; }	
	
	$products = isset($_POST['_multi_add_to_cart_products']) ? $_POST['_multi_add_to_cart_products'] : '';
	if($products !== ''):
	
	update_post_meta( $post_id, '_multi_add_to_cart_products', $products);
	
	endif;	
}
/****************************************/

function btn_multi_add_to_cart_shortcode_func($atts) {
	global $woocommerce;
	ob_start();
	
	  extract(shortcode_atts(array(
	   'id' => 0,
		'title_btn' => __( 'Añadir al Carrito', 'woocommerce' ),
	   ), $atts));
	
	$value = get_post_meta($id, '_multi_add_to_cart_products', true );
	if($value):
	
	$btn_add_to_cart = add_query_arg( array(
		'add-to-cart' => $id,
		'multi' => true,
	), $woocommerce->cart->get_cart_url() );
	
	printf('<div class="woocommerce add_to_cart_inline"><a rel="nofollow" class="button ajax_multi_add_to_cart add_to_cart_button" data-shortcode_id="%3$d" href="%1$s">%2$s</a></div>', $btn_add_to_cart, $title_btn, $id);
	
	else:
	
	echo '<code>No encontrado.</code>';
	
	endif;
	
	$contenido = ob_get_contents();
	ob_end_clean();
	
   return $contenido;
}
add_shortcode('btn_multi_add_to_cart', 'btn_multi_add_to_cart_shortcode_func');

add_action('wp_footer', 'add_script_footer_has_shortcode_btn_multi_add_to_cart_func',100);
function add_script_footer_has_shortcode_btn_multi_add_to_cart_func() {
	global $post;
	#if ( has_shortcode( $post->post_content, 'btn_multi_add_to_cart' ) ) {
	?>

<script type="text/javascript">
		jQuery(document).ready(function($) {
			var ajaxurl = "<?php echo esc_attr( admin_url( 'admin-ajax.php' ) ); ?>";
			$(document).on('click', '.ajax_multi_add_to_cart', function(e) {
				var btn = $(this);
				var id = btn.data("shortcode_id");
				var data_post = {action: 'multi_add_to_cart', shortcode_id: id};			
				$.ajax({
					type: "GET",
					url: ajaxurl,
					data: data_post,
					beforeSend: function(){
						btn.addClass('loading');
					},
					success: function(resp){
						console.log(resp);
						if(resp.success !== false){
							$( document.body ).trigger( 'wc_fragment_refresh' );
							btn.text('Añadido!').removeClass('loading').addClass('added');
						}
					}
				});	

				return false;
			})
		});
	</script>

	<?php
	#}
}

add_action('wp_ajax_multi_add_to_cart', "ajax_multi_add_to_cart_func");
add_action('wp_ajax_nopriv_multi_add_to_cart', "ajax_multi_add_to_cart_func");
function ajax_multi_add_to_cart_func(){
	global $woocommerce;
	
	$return = [];
	$shortcode_id = isset($_GET['shortcode_id']) ? intval($_GET['shortcode_id']) : 0;
	
	if($shortcode_id == 0): 
	$error = new WP_Error( '001', 'Error Shortcode ID', 'Some information' );
 	wp_send_json_error( $error ); 
	endif;
	
	$products_ids = get_post_meta($shortcode_id, '_multi_add_to_cart_products', true );
	
	if(empty($products_ids) || $products_ids == ''):
	$error = new WP_Error( '404', 'Error Products no Found', 'Some information' );
 	wp_send_json_error( $error ); 	
	endif;
	
	if(is_array($products_ids) && count($products_ids) > 0){
		foreach($products_ids as $kpid => $vpid){
			$result = $woocommerce->cart->add_to_cart( $vpid );
		}
	}
	
	wp_send_json_success( $return );
}
