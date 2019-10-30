<?php

add_action( 'rest_api_init', function () {
    register_rest_route( 'webmapp/v2', '/route/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => function( WP_REST_Request $request )
        {
            $param = $request->get_url_params();
            $error = new WP_Error();


            if ( ! isset( $param['id'] ) )
            {
                $error->add(400,"Missing id parameter.", 400);
                return $error;
            }

            $route_id = $param['id'];
            $route = get_post( $route_id );

            if ( ! $route instanceof WP_Post)
            {
                $error->add(400,"Impossible found a post with this id.", 400);
                return $error;
            }

            if ( $route->post_type !== 'route' )
            {
                $error->add(400,"Id provided isn't of a route post.", 400);
                return $error;
            }

            $route = (array) $route;

            //add all acf
            $route['acf'] = get_fields( $route_id );

            if ( isset($route['acf']['product']) && is_array($route['acf']['product']) )
            {
                $found_products = $route['acf']['product'];
                $products = [];
                foreach ( $found_products as $key => $product_id )
                {
                    $product = wc_get_product($product_id)->get_data();

                    $handle = new WC_Product_Variable($product_id);
                    $variations_ids = $handle->get_children();
                    if ( ! empty ($variations_ids) )
                    {
                        $variations = [];
                        foreach( $variations_ids as $variations_id)
                        {
                            $single_variation = new WC_Product_Variation($variations_id);
                            $variations[] = $single_variation->get_data();
                        }
                        $product['variations'] = $variations;

                    }

                    $products[ $product_id ] = $product;

                }
                $route['acf']['product'] = $products;
            }


            return new WP_REST_Response( $route, 200 );
        },
    ) );
} );