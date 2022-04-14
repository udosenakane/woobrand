<?php
/**
 * @package digitakeoff
 * @version 1.0.0
 */
/*
Plugin Name: WooBrand
Plugin URI: http://wordpress.org/plugins/woobrand/
Description: I did not any free woobrand.  
Author: Akan Udosen
Version: 1.7.2
Author URI: https://github.com/udosenakane
*/

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WooCommerce', true )){

    function digitakeoff_create_brand_taxonomy(){
        $labels = [
            'name' => 'Brands',
            'singular_name' => 'Brand',
            'all_items' => __('All Brands'),
            'edit_item' => __('Edit Brand' ), 
            'view_item' => __('View Brands'), 
            'update_item' => __('Update Brands'),
            'add_new_item' =>  __('Add New Brands'),
            'new_item_name' => __('New Brand Name'),
            'parent_item' => __( 'Parent Brand' ),
            'parent_item_colon' => __('Parent Brand:'),
            'search_items' => __('Search Brands'),
            'popular_items' => __('Popular Brands'),
            'not_found' => __('No brand found.'),
        ];
        $args = [
            'hierarchical'     => true,
            'labels'           => $labels,
            'show_in_rest'     => true,
            'public'           => true,
            'rest_base'        => 'brands',
            'rewrite'          => array(
                'slug'         => 'brand',
            ),
            // 'rest_controller_class'=> 'WC_REST_Terms_Controller'
        ];
        register_taxonomy('brand', 'product', $args);
        register_taxonomy_for_object_type( 'brand', 'product' );
    }
    add_action('init', 'digitakeoff_create_brand_taxonomy');

    add_filter("woocommerce_rest_prepare_product_object", "prepare_product_for_rest", 10, 3);
    function prepare_product_for_rest($response, $post, $request) {
        global $_wp_additional_image_sizes;

        if (empty($response->data)) {
            return $response;
        }

        $terms = array();

        foreach ( wp_get_object_terms( $post->get_id(), 'brand' ) as $term ) {
            $terms[] = array(
                'id'     => $term->term_id,
                'name'   => $term->name,
                'slug'   => $term->slug,
            );
        }
        $response->data['brands'] = $terms;

        $user_id   = (int)get_post_meta( $post->get_id(), 'user_id', true );
        $response->data['user_id'] = $user_id;


        foreach ($response->data['images'] as $key => $image) {
            $image_urls = [];
            foreach ($_wp_additional_image_sizes as $size => $value) {
                $image_info = wp_get_attachment_image_src($image['id'], $size);
                $response->data['images'][$key][$size] = $image_info[0];
            }
        }
        
        return $response;
    }

    add_action( "brand_edit_form", 'edit_upload_image_to_brand', 10, 2);
    function edit_upload_image_to_brand($tag, $taxonomy){
        ?>
        <div class="form-field term-thumbnail-wrap">
            <label><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></label>
            <div id="brand_thumbnail" style="float: left; margin-right: 10px;">
            <img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" width="60px" height="60px" /></div>
            <div style="line-height: 60px;">
                <input type="hidden" id="brand_thumbnail_id" name="brand_thumbnail_id" />
                <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
                <button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
            </div>
                <script type="text/javascript">

                    // Only show the "remove image" button when needed
                    if ( ! jQuery( '#brand_thumbnail_id' ).val() ) {
                        jQuery( '.remove_image_button' ).hide();
                    }

                    // Uploading files
                    var file_frame;

                    jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

                        event.preventDefault();

                        // If the media frame already exists, reopen it.
                        if ( file_frame ) {
                            file_frame.open();
                            return;
                        }

                        // Create the media frame.
                        file_frame = wp.media.frames.downloadable_file = wp.media({
                            title: '<?php esc_html_e( 'Choose an image', 'woocommerce' ); ?>',
                            button: {
                                text: '<?php esc_html_e( 'Use image', 'woocommerce' ); ?>'
                            },
                            multiple: false
                        });

                        // When an image is selected, run a callback.
                        file_frame.on( 'select', function() {
                            var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
                            var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

                            jQuery( '#brand_thumbnail_id' ).val( attachment.id );
                            jQuery( '#brand_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
                            jQuery( '.remove_image_button' ).show();
                        });

                        // Finally, open the modal.
                        file_frame.open();
                    });

                    jQuery( document ).on( 'click', '.remove_image_button', function() {
                        jQuery( '#brand_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
                        jQuery( '#brand_thumbnail_id' ).val( '' );
                        jQuery( '.remove_image_button' ).hide();
                        return false;
                    });

                    jQuery( document ).ajaxComplete( function( event, request, options ) {
                        if ( request && 4 === request.readyState && 200 === request.status
                            && options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {

                            var res = wpAjax.parseAjaxResponse( request.responseXML, 'ajax-response' );
                            if ( ! res || res.errors ) {
                                return;
                            }
                            // Clear Thumbnail fields on submit
                            jQuery( '#brand_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
                            jQuery( '#brand_thumbnail_id' ).val( '' );
                            jQuery( '.remove_image_button' ).hide();
                            // Clear Display type field on submit
                            // jQuery( '#display_type' ).val( '' );
                            return;
                        }
                    } );
                </script>
                <div class="clear"></div>
            </div>
        <?php
    }

    // add_action( "v-type_add_form_fields", 'add_upload_image_to_brand', 10, 1);
    add_action( "brand_add_form_fields", 'add_upload_image_to_brand', 10, 1);
    function add_upload_image_to_brand($taxonomy){
        ?>
        <div class="form-field term-thumbnail-wrap">
            <label><?php esc_html_e( 'Thumbnail', 'woocommerce' ); ?></label>
            <div id="brand_thumbnail" style="float: left; margin-right: 10px;">
            <img src="<?php echo esc_url( wc_placeholder_img_src() ); ?>" width="60px" height="60px" /></div>
            <div style="line-height: 60px;">
                <input type="hidden" id="brand_thumbnail_id" name="brand_thumbnail_id" />
                <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'woocommerce' ); ?></button>
                <button type="button" class="remove_image_button button"><?php esc_html_e( 'Remove image', 'woocommerce' ); ?></button>
            </div>
                <script type="text/javascript">

                    // Only show the "remove image" button when needed
                    if ( ! jQuery( '#brand_thumbnail_id' ).val() ) {
                        jQuery( '.remove_image_button' ).hide();
                    }

                    // Uploading files
                    var file_frame;

                    jQuery( document ).on( 'click', '.upload_image_button', function( event ) {

                        event.preventDefault();

                        // If the media frame already exists, reopen it.
                        if ( file_frame ) {
                            file_frame.open();
                            return;
                        }

                        // Create the media frame.
                        file_frame = wp.media.frames.downloadable_file = wp.media({
                            title: '<?php esc_html_e( 'Choose an image', 'woocommerce' ); ?>',
                            button: {
                                text: '<?php esc_html_e( 'Use image', 'woocommerce' ); ?>'
                            },
                            multiple: false
                        });

                        // When an image is selected, run a callback.
                        file_frame.on( 'select', function() {
                            var attachment           = file_frame.state().get( 'selection' ).first().toJSON();
                            var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

                            jQuery( '#brand_thumbnail_id' ).val( attachment.id );
                            jQuery( '#brand_thumbnail' ).find( 'img' ).attr( 'src', attachment_thumbnail.url );
                            jQuery( '.remove_image_button' ).show();
                        });

                        // Finally, open the modal.
                        file_frame.open();
                    });

                    jQuery( document ).on( 'click', '.remove_image_button', function() {
                        jQuery( '#brand_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
                        jQuery( '#brand_thumbnail_id' ).val( '' );
                        jQuery( '.remove_image_button' ).hide();
                        return false;
                    });

                    jQuery( document ).ajaxComplete( function( event, request, options ) {
                        if ( request && 4 === request.readyState && 200 === request.status
                            && options.data && 0 <= options.data.indexOf( 'action=add-tag' ) ) {

                            var res = wpAjax.parseAjaxResponse( request.responseXML, 'ajax-response' );
                            if ( ! res || res.errors ) {
                                return;
                            }
                            // Clear Thumbnail fields on submit
                            jQuery( '#brand_thumbnail' ).find( 'img' ).attr( 'src', '<?php echo esc_js( wc_placeholder_img_src() ); ?>' );
                            jQuery( '#brand_thumbnail_id' ).val( '' );
                            jQuery( '.remove_image_button' ).hide();
                            // Clear Display type field on submit
                            // jQuery( '#display_type' ).val( '' );
                            return;
                        }
                    } );
                </script>
                <div class="clear"></div>
            </div>
        <?php
    }


    add_action('saved_brand', 'save_brand_image', 10, 3);
    add_action('edited_brand', 'save_brand_image', 10, 3);
    function save_brand_image( $term_id, $tt_id = '', $taxonomy = '' ) {
        if ( isset( $_POST['brand_thumbnail_id'] ) ) { // WPCS: CSRF ok, input var ok.
            update_term_meta( $term_id, 'thumbnail_id', absint( $_POST['brand_thumbnail_id'] ) ); // WPCS: CSRF ok, input var ok.
        }
    }

    // add it to rest api
    add_filter('rest_prepare_brand', 'prepare_taxonomy_for_rest', 10, 3);
    function prepare_taxonomy_for_rest( $response, $item, $request ) {
        $menu_order = get_term_meta( $item->term_id, 'order', true );
        $data = array(
            'id'          => (int) $item->term_id,
            'name'        => $item->name,
            'slug'        => $item->slug,
            'parent'      => (int) $item->parent,
            'description' => $item->description,
            'image'       => null,
            'menu_order'  => (int) $menu_order,
            'count'       => (int) $item->count,
        );

        // Get category image.
        $image_id = get_term_meta( $item->term_id, 'thumbnail_id', true );
        if ( $image_id ) {
            $attachment = get_post( $image_id );

            $data['image'] = array(
                'id'                => (int) $image_id,
                'date_created'      => wc_rest_prepare_date_response( $attachment->post_date ),
                'date_created_gmt'  => wc_rest_prepare_date_response( $attachment->post_date_gmt ),
                'date_modified'     => wc_rest_prepare_date_response( $attachment->post_modified ),
                'date_modified_gmt' => wc_rest_prepare_date_response( $attachment->post_modified_gmt ),
                'src'               => wp_get_attachment_url( $image_id ),
                'name'              => get_the_title( $attachment ),
                'alt'               => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
            );
        }
        $response->data = $data;
        return $response;
    }
} else {
    // nothing for now
}