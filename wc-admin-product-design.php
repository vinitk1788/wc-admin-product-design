<?php
/**
 * Plugin Name: WooCommerce Admin Product Design
 * Description: Add an admin-only product-specific design field (Media upload or URL) to WooCommerce product edit screen. Saves as product meta. Admin-only by default.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wc-admin-product-design
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', function() {
	if ( class_exists( 'WooCommerce' ) ) {
		WCPD::init();
	}
} );

class WCPD {
	const VERSION = '1.0.0';
	const TEXTDOMAIN = 'wc-admin-product-design';
	const META_IMAGE_ID = '_wcpd_design_image_id';
	const META_IMAGE_URL = '_wcpd_design_image_url';
	const REQUIRED_CAPABILITY = 'edit_products'; // allows shop managers and administrators

	public static function init() {
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'add_admin_product_fields' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_product_fields' ) );
		add_action( 'wp_ajax_wcpd_download_original', array( __CLASS__, 'handle_download_original' ) );
	}

	public static function add_admin_product_fields() {
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) return;

		global $post;
		$image_id  = get_post_meta( $post->ID, self::META_IMAGE_ID, true );
		$image_url = get_post_meta( $post->ID, self::META_IMAGE_URL, true );
		$preview_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : ( $image_url ? esc_url( $image_url ) : '' );
		$original_url = $image_id ? wp_get_attachment_url( $image_id ) : $image_url;

		echo '<div class="options_group">';
		echo '<input type="hidden" id="' . esc_attr( self::META_IMAGE_ID ) . '" name="' . esc_attr( self::META_IMAGE_ID ) . '" value="' . esc_attr( $image_id ) . '" />';
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( self::META_IMAGE_ID ); ?>"><?php esc_html_e( 'Admin design image', self::TEXTDOMAIN ); ?></label><br/>
			<img id="wcpd_design_image_preview" src="<?php echo esc_url( $preview_url ); ?>" style="max-width:200px; <?php echo $preview_url ? '' : 'display:none;'; ?> margin-bottom:6px;" /><br/>
			<button type="button" class="button wcpd_upload_button"><?php esc_html_e( 'Upload / Select Image', self::TEXTDOMAIN ); ?></button>
			<button type="button" class="button wcpd_download_button" data-product-id="<?php echo esc_attr( $post->ID ); ?>" <?php echo $preview_url ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Download Original File', self::TEXTDOMAIN ); ?></button>
			<button type="button" class="button wcpd_remove_button" <?php echo $preview_url ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove image', self::TEXTDOMAIN ); ?></button>
			<span class="description"><?php esc_html_e( 'This image is visible to admins only and used as the product-specific design.', self::TEXTDOMAIN ); ?></span>
		</p>
		<?php
		woocommerce_wp_text_input( array(
			'id'          => self::META_IMAGE_URL,
			'label'       => __( 'Admin design image URL', self::TEXTDOMAIN ),
			'description' => __( 'Optional: enter a full image URL instead of using the media uploader.', self::TEXTDOMAIN ),
			'desc_tip'    => true,
			'type'        => 'url',
			'value'       => esc_attr( $image_url ),
		) );
		echo '</div>';
	}

	public static function admin_enqueue( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) return;
		$screen = get_current_screen();
		if ( ! $screen || 'product' !== $screen->post_type ) return;
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) return;

		wp_enqueue_media();
		$handle = 'wcpd-admin-js';
		wp_register_script( $handle, '' , array( 'jquery' ), self::VERSION, true );
		wp_localize_script( $handle, 'wcpd_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'wcpd_download_nonce' )
		) );
		wp_enqueue_script( $handle );
		wp_add_inline_script( $handle, self::get_inline_js() );
		
		// Add inline CSS for button styling
		wp_add_inline_style( 'wp-admin', self::get_inline_css() );
	}

	private static function get_inline_js() {
		$meta_image_id = esc_js( self::META_IMAGE_ID );
		$meta_image_url = esc_js( self::META_IMAGE_URL );
		return "
jQuery(document).ready(function($){
	var file_frame;
	$(document).on('click', '.wcpd_upload_button', function(e){
		e.preventDefault();
		var preview = $('#wcpd_design_image_preview');
		var removeBtn = $('.wcpd_remove_button');
		var downloadBtn = $('.wcpd_download_button');
		if ( file_frame ) { file_frame.open(); return; }
		file_frame = wp.media.frames.file_frame = wp.media({
			title: 'Select or Upload Admin Design Image',
			button: { text: 'Use this image' },
			multiple: false
		});
		file_frame.on('select', function(){
			var attachment = file_frame.state().get('selection').first().toJSON();
			$('input#" . $meta_image_id . "').val(attachment.id).trigger('change');
			var src = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
			preview.attr('src', src).show();
			removeBtn.show();
			downloadBtn.show();
		});
		file_frame.open();
	});
	$(document).on('click', '.wcpd_remove_button', function(e){
		e.preventDefault();
		$('input#" . $meta_image_id . "').val('').trigger('change');
		$('#wcpd_design_image_preview').attr('src','').hide();
		$(this).hide();
		$('.wcpd_download_button').hide();
	});
	$(document).on('change', 'input#" . $meta_image_url . "', function(){
		var val = $(this).val().trim();
		if ( val ) {
			$('#wcpd_design_image_preview').attr('src', val).show();
			$('.wcpd_remove_button').show();
			$('.wcpd_download_button').show();
		}
	});
	$(document).on('click', '.wcpd_download_button', function(e){
		e.preventDefault();
		var productId = $(this).data('product-id');
		if (!productId) {
			alert('Product ID not found');
			return;
		}
		$.ajax({
			url: wcpd_ajax.ajax_url,
			type: 'POST',
			data: {
				action: 'wcpd_download_original',
				product_id: productId,
				nonce: wcpd_ajax.nonce
			},
			success: function(response) {
				if (response.success && response.data.download_url) {
					var link = document.createElement('a');
					link.href = response.data.download_url;
					link.download = response.data.filename || 'design-image';
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
				} else {
					alert('Download failed: ' + (response.data || 'Unknown error'));
				}
			},
			error: function() {
				alert('Download request failed');
			}
		});
	});
});
";
	}

	private static function get_inline_css() {
		return "
.wcpd_upload_button {
	margin-right: 5px !important;
	color: #0073aa !important;
	border-color: #0073aa !important;
}
.wcpd_download_button {
	margin-right: 5px !important;
	color: #00a32a !important;
	border-color: #00a32a !important;
}
.wcpd_download_button:hover {
	background-color: #00a32a !important;
	color: white !important;
}
.wcpd_remove_button {
	color: #d63638 !important;
	border-color: #d63638 !important;
}
.wcpd_remove_button:hover {
	background-color: #d63638 !important;
	color: white !important;
}
#wcpd_design_image_preview {
	border: 2px dashed #c3c4c7;
	padding: 5px;
	margin-bottom: 10px !important;
	border-radius: 4px;
}
";
	}

	public static function save_product_fields( $product ) {
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) return;
		$post_id = $product->get_id();
		if ( isset( $_POST[ self::META_IMAGE_ID ] ) ) {
			$attach_id = intval( wp_unslash( $_POST[ self::META_IMAGE_ID ] ) );
			if ( $attach_id ) update_post_meta( $post_id, self::META_IMAGE_ID, $attach_id );
			else delete_post_meta( $post_id, self::META_IMAGE_ID );
		}
		if ( isset( $_POST[ self::META_IMAGE_URL ] ) ) {
			$url = wp_strip_all_tags( wp_unslash( $_POST[ self::META_IMAGE_URL ] ) );
			$url = trim( $url );
			if ( $url ) update_post_meta( $post_id, self::META_IMAGE_URL, esc_url_raw( $url ) );
			else delete_post_meta( $post_id, self::META_IMAGE_URL );
		}
	}

	public static function get_design_image( $product ) {
		$post_id = is_object( $product ) && method_exists( $product, 'get_id' ) ? $product->get_id() : intval( $product );
		if ( ! $post_id ) return '';
		$attach_id = get_post_meta( $post_id, self::META_IMAGE_ID, true );
		$url = get_post_meta( $post_id, self::META_IMAGE_URL, true );
		if ( $attach_id ) {
			$src = wp_get_attachment_image_url( $attach_id, 'full' );
			if ( $src ) return $src;
		}
		if ( $url ) return esc_url( $url );
		return '';
	}

	public static function handle_download_original() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'], 'wcpd_download_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check user capability
		if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$product_id = intval( $_POST['product_id'] );
		if ( ! $product_id ) {
			wp_send_json_error( 'Invalid product ID' );
		}

		$image_id = get_post_meta( $product_id, self::META_IMAGE_ID, true );
		$image_url = get_post_meta( $product_id, self::META_IMAGE_URL, true );

		if ( $image_id ) {
			// Get attachment details
			$attachment_url = wp_get_attachment_url( $image_id );
			$attachment_path = get_attached_file( $image_id );
			$filename = basename( $attachment_path );
			
			if ( $attachment_url ) {
				wp_send_json_success( array(
					'download_url' => $attachment_url,
					'filename' => $filename,
					'type' => 'attachment'
				) );
			}
		} elseif ( $image_url ) {
			// For URL-based images, return the URL with a generic filename
			$parsed_url = parse_url( $image_url );
			$filename = basename( $parsed_url['path'] ) ?: 'design-image.jpg';
			
			wp_send_json_success( array(
				'download_url' => esc_url( $image_url ),
				'filename' => $filename,
				'type' => 'url'
			) );
		}

		wp_send_json_error( 'No design image found for this product' );
	}
}
