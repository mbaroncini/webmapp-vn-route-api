<?php


define( "WM_VN_WRITEONSAVE_DIR", ABSPATH . 'api_cache/');

add_action( 'save_post_route' ,'wm_vn_updateFileOnSave',999,3);
add_action( 'save_post_product', 'wm_vn_updateFileOnSave',999,3);
//add_action( 'save_post_promotion', 'wm_vn_updateFileOnSave',999 );
function wm_vn_updateFileOnSave( $post_id , $post , $update )
{
    $routes = [$post];
    if ( $post->post_type === 'product' )
    {
        $routes = wm_vn_getRoutesByProductId( $post_id );
        if ( ! $routes )
        {
            trigger_error("Impossible write rest api cache for product with id $post_id. Impossible get route.");
            return;
        }
    }

    $base_url = home_url() . "/wp-json/webmapp/v2/route/";
    foreach ( $routes as $route )
    {
        $route_id = $route->ID;
        $url = $base_url . $route_id;
        $json = wm_get_curl_content( $url );
        if ( $json )
            wm_vn_writeRestApi($json,$route_id);
        else
            trigger_error("Impossible write rest api cache for route with id: $route_id. Impossible get: $base_url");
    }


}


function wm_vn_getRoutesByProductId( $product_id )
{
    $id_length = strlen($product_id);
    /**
     * s:5:"50530";
     */
    $meta_string = "s:$id_length:\"$product_id\";";
    $routes = get_posts(
        [
            'post_type' => 'route',
            'post_status' => 'publish',
            'nopaging' => true,
            'meta_query' => array(
                array(
                    'key' => 'product',
                    'value' => $meta_string,
                    'compare' => 'LIKE'
                )
            )
        ]
    );

    if ( ! $routes )
        return false;

    return $routes ;
}


function wm_vn_writeRestApi( $json , $route_id)
{
    $dirpath = WM_VN_WRITEONSAVE_DIR;

    if ( ! file_exists($dirpath ) )
    {
        // create directory/folder uploads.
        $check = mkdir($dirpath, 0755 , true );
        if ( ! $check )
        {
            trigger_error("Impossible write rest api cache. Impossible write in $dirpath");
            return;
        }
    }
    $log_file_data = $dirpath.$route_id.'.json';
    $check = file_put_contents($log_file_data, $json);
}


function wm_get_curl_content($url, $post = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

    if(!empty($post)) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}