<?php

/*
@wordpress-plugin
Plugin Name: Shopify Inspector
Plugin URI: http://proquotient.com/shopifyinspector
Description: Detect the shopify theme used in any website.
Version: 1.0
Author: Anshul Khare
Author URI: https://anshullkhare.in
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: shopify-inspector
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

defined('ABSPATH') or die('Permission denied!');

/* !1. HOOKS */
// register shortcodes
add_action('init', 'register_si_shortcodes');

//register ajax actions
add_action('wp_ajax_nopriv_si_get_theme', 'si_get_theme');
add_action('wp_ajax_si_get_theme', 'si_get_theme');

// load external files to public website
add_action('wp_enqueue_scripts', 'si_public_scripts');

/* !2. SHORTCODES */
function register_si_shortcodes(){
    add_shortcode('shopify_inspector', 'si_search_form_shortcode');
}

function si_search_form_shortcode($atts = [], $content = null, $tag = ''){
    $output = '
        <div class="si-search">
            <form id="si_form" name="si_form" class="si-form">                                
                <input type="text" name="si_website" id="si_website" placeholder="Shopify URL"/>                
                <input type="button" class="btn-detect-theme" id="btn-detect-theme" name="si_submit" value="DETECT THEME"/>                
                <input type="hidden" id="ajax_prefix" value="'.get_site_url().'">
            </form>
            <span id="si-error-msg" class="si-error-text">Please enter a valid URL</span>
        </div>        
        <div class="si-result-container" id="si-result-container"></div>
        <div class="si-loading-container" id="si-loading-container"></div>
    ';
    return $output;
}

function si_get_theme(){
    $result = array(
        'status' => 0        
    );
    $result['themes'] = array();

    try{
        $shopify_url = esc_attr($_POST['si_website']);
        //write_log('Shopify URL ======> '.$shopify_url);

        if(stripos($shopify_url, "ttp")==0){
            $shopify_url = "http://".$shopify_url;
        }

        $response = wp_remote_retrieve_body(wp_remote_get($shopify_url));
        //write_log('Response ======> '.$response);
        
        $pos_1 = (int)strpos($response, "Shopify.theme =");
        //write_log('Pos1 ======> '.$pos_1);

        if($pos_1>0){
            $pos_2 = (int)strpos($response, "Shopify.theme.handle");
            $substr_len = $pos_2 - $pos_1;
            $theme_substr = substr($response, $pos_1, $substr_len);
            
            $pos_3 = (int)strpos($theme_substr, "=")+1;
            $pos_4 = (int)strpos($theme_substr, ";");
            $json_len = $pos_4 - $pos_3;
            $json_str = substr($theme_substr, $pos_3, $json_len);
            
            $json_obj = json_decode($json_str, true);
                    
            $theme_keyword = $json_obj['name'];

            //write_log('Theme ======>'.$theme_keyword);                        
            //write_log(lookup_theme_details($theme_keyword));
            foreach (lookup_theme_details($theme_keyword) as &$value) {            
                array_push($result['themes'],$value);
            }
        }else{
            //write_log('Shopify.theme not found!');
            array_push($result['themes'],json_decode('{"name":"NoShopify", "link":"", "message": " store not built on Shopify."}'));
        }                        
        $result['status']=1;
    }catch( Exception $e ) {
        $result['error'] = 'Caught Exception'.$e->getMessage();          
    }
    
    return si_return_json($result);
}


/* EXTERNAL SCRIPTS */
// load external files into PUBLIC website (frontend)
function si_public_scripts(){

	// register scripts with WordPress's internal library
	wp_register_script('shopify-inspector-js-public', plugins_url('/public/js/si-public.min.js',__FILE__), array('jquery'),'0.3',true);        
	wp_register_style('shopify-inspector-css-public', plugins_url('/public/css/si-public.css',__FILE__), array(), '0.2');
    
	// add to que of scripts that get loaded into every page
	wp_enqueue_script('shopify-inspector-js-public');    
	wp_enqueue_style('shopify-inspector-css-public');
}


/* HELPERS */
function si_return_json($php_array){
    $json_result = json_encode($php_array);
    die($json_result);
    exit;
}

// hint: logging helper function
if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }

}

// lookup for themes
function lookup_theme_details($theme_keyword){        
        
    if(stripos($theme_keyword, 'Agilis') !== false){ if($theme_keyword == 'Agilis'){ return json_decode('[{"name":"Agilis", "message" : " is using ", "link":"http://www.boostheme.com"}]');}else{ return json_decode('[{"name":"Agilis", "message" : " is using a customized version of ", "link":"http://www.boostheme.com"}]');}}
    if(stripos($theme_keyword, 'Alchemy') !== false){ if($theme_keyword == 'Alchemy'){ return json_decode('[{"name":"Alchemy", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Alchemy", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Artisan') !== false){        
        if(stripos($theme_keyword, 'Barcelona') !== false) {
            return json_decode('[{"name":"Artisan Barcelona", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }
        
        if(stripos($theme_keyword, 'Phoenix') !== false) {
            return json_decode('[{"name":"Artisan Phoenix", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Victoria') !== false) {
            return json_decode('[{"name":"Artisan Victoria", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }
        
        return json_decode('[{"name":"Artisan", "message" : " is using ", "link":"http://www.shopify.com"},{"name":"Artisan Barcelona", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Artisan Phoenix", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Artisan Victoria", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');    
    }
    
    if(stripos($theme_keyword, 'Atlantic') !== false){ if($theme_keyword == 'Atlantic'){ return json_decode('[{"name":"Atlantic", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Atlantic", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Avenue') !== false){ if($theme_keyword == 'Avenue'){ return json_decode('[{"name":"Avenue", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Avenue", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Blockshop') !== false){ if($theme_keyword == 'Blockshop'){ return json_decode('[{"name":"Blockshop", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Blockshop", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}    
    
    if(stripos($theme_keyword, 'Booster') !== false){
        if($theme_keyword == 'Booster' or $theme_keyword == 'BoosterTheme'){
            return json_decode('[{"name":"Booster Theme", "message" : " is using ", "link":"https://boostertheme.com/"}]');
        }else{
            return json_decode('[{"name":"Booster Theme", "message" : " is using a customized version of ", "link":"https://boostertheme.com/"}]');
        }
    }

    if(stripos($theme_keyword, 'Boost') !== false){ if($theme_keyword == 'Boost'){ return json_decode('[{"name":"Boost", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Boost", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}

    if(stripos($theme_keyword, 'Boss') !== false){ if($theme_keyword == 'Boss'){ return json_decode('[{"name":"Boss", "message" : " is using ", "link":"http://www.boostheme.com"}]');}else{ return json_decode('[{"name":"Boss", "message" : " is using a customized version of ", "link":"http://www.boostheme.com"}]');}}
    if(stripos($theme_keyword, 'Boundless') !== false){ if($theme_keyword == 'Boundless'){ return json_decode('[{"name":"Boundless", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Boundless", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Broadcast') !== false){ if($theme_keyword == 'Broadcast'){ return json_decode('[{"name":"Broadcast", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Broadcast", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Brooklyn') !== false){ if($theme_keyword == 'Brooklyn'){ return json_decode('[{"name":"Brooklyn", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Brooklyn", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Calender Theme') !== false){ if($theme_keyword == 'Calender Theme'){ return json_decode('[{"name":"Calender Theme ", "message" : " is using ", "link":"http://www.tabarnapp.com"}]');}else{ return json_decode('[{"name":"Calender Theme ", "message" : " is using a customized version of ", "link":"http://www.tabarnapp.com"}]');}}
    if(stripos($theme_keyword, 'California') !== false){ if($theme_keyword == 'California'){ return json_decode('[{"name":"California", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"California", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Canopy') !== false){ if($theme_keyword == 'Canopy'){ return json_decode('[{"name":"Canopy", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Canopy", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Capital') !== false){ if($theme_keyword == 'Capital'){ return json_decode('[{"name":"Capital", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Capital", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Cascade') !== false){ if($theme_keyword == 'Cascade'){ return json_decode('[{"name":"Cascade", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Cascade", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Colors') !== false){ if($theme_keyword == 'Colors'){ return json_decode('[{"name":"Colors", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Colors", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Context') !== false){ if($theme_keyword == 'Context'){ return json_decode('[{"name":"Context", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Context", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Debutify') !== false){ if($theme_keyword == 'Debutify'){ return json_decode('[{"name":"Debutify", "message" : " is using ", "link":"http://www.debutify.com"}]');}else{ return json_decode('[{"name":"Debutify", "message" : " is using a customized version of ", "link":"http://www.debutify.com"}]');}}
    if(stripos($theme_keyword, 'Debut') !== false){ if($theme_keyword == 'Debut'){ return json_decode('[{"name":"Debut", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Debut", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'District') !== false){ if($theme_keyword == 'District'){ return json_decode('[{"name":"District", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"District", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Editions') !== false){ if($theme_keyword == 'Editions'){ return json_decode('[{"name":"Editions", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Editions", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Editorial') !== false){ if($theme_keyword == 'Editorial'){ return json_decode('[{"name":"Editorial", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Editorial", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Empire') !== false){ if($theme_keyword == 'Empire'){ return json_decode('[{"name":"Empire", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Empire", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Envy') !== false){ if($theme_keyword == 'Envy'){ return json_decode('[{"name":"Envy", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Envy", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Expression') !== false){ if($theme_keyword == 'Expression'){ return json_decode('[{"name":"Expression", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Expression", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Fashionopolism') !== false){ if($theme_keyword == 'Fashionopolism'){ return json_decode('[{"name":"Fashionopolism", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Fashionopolism", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Flex') !== false){ if($theme_keyword == 'Flex'){ return json_decode('[{"name":"Flex", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');}else{ return json_decode('[{"name":"Flex", "message" : " is using a customized version of ", "link":"https://outofthesandbox.com/"}]');}}
    if(stripos($theme_keyword, 'Flow') !== false){ if($theme_keyword == 'Flow'){ return json_decode('[{"name":"Flow", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Flow", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Focal') !== false){ if($theme_keyword == 'Focal'){ return json_decode('[{"name":"Focal", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Focal", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Galleria') !== false){ if($theme_keyword == 'Galleria'){ return json_decode('[{"name":"Galleria", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Galleria", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Grid') !== false){ if($theme_keyword == 'Grid'){ return json_decode('[{"name":"Grid", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Grid", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Handy') !== false){ if($theme_keyword == 'Handy'){ return json_decode('[{"name":"Handy", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Handy", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Icon') !== false){ if($theme_keyword == 'Icon'){ return json_decode('[{"name":"Icon", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Icon", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Impulse') !== false){ if($theme_keyword == 'Impulse'){ return json_decode('[{"name":"Impulse", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Impulse", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Ira') !== false){ if($theme_keyword == 'Ira'){ return json_decode('[{"name":"Ira", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Ira", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Kagami') !== false){ if($theme_keyword == 'Kagami'){ return json_decode('[{"name":"Kagami", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Kagami", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Kingdom') !== false){ if($theme_keyword == 'Kingdom'){ return json_decode('[{"name":"Kingdom", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Kingdom", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Konversion Theme') !== false){ if($theme_keyword == 'Konversion Theme'){ return json_decode('[{"name":"Konversion Theme", "message" : " is using ", "link":"http://www.tabarnapp.com"}]');}else{ return json_decode('[{"name":"Konversion Theme", "message" : " is using a customized version of ", "link":"http://www.tabarnapp.com"}]');}}
    if(stripos($theme_keyword, 'Label') !== false){ if($theme_keyword == 'Label'){ return json_decode('[{"name":"Label", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Label", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Launch') !== false){ if($theme_keyword == 'Launch'){ return json_decode('[{"name":"Launch", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Launch", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Local') !== false){ if($theme_keyword == 'Local'){ return json_decode('[{"name":"Local", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Local", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Loft') !== false){ if($theme_keyword == 'Loft'){ return json_decode('[{"name":"Loft", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Loft", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Lorenza') !== false){ if($theme_keyword == 'Lorenza'){ return json_decode('[{"name":"Lorenza", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Lorenza", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Maker') !== false){ if($theme_keyword == 'Maker'){ return json_decode('[{"name":"Maker", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Maker", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Masonry') !== false){ if($theme_keyword == 'Masonry'){ return json_decode('[{"name":"Masonry", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Masonry", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Minimal') !== false){ if($theme_keyword == 'Minimal'){ return json_decode('[{"name":"Minimal", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Minimal", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    
    if(stripos($theme_keyword, 'Mobilia') !== false){        
        if(stripos($theme_keyword, 'Milan') !== false) {
            return json_decode('[{"name":"Mobilia Milan", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }
        
        if(stripos($theme_keyword, 'Napa') !== false) {
            return json_decode('[{"name":"Mobilia Napa", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Sydney') !== false) {
            return json_decode('[{"name":"Mobilia Sydney", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Tokyo') !== false) {
            return json_decode('[{"name":"Mobilia Tokyo", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        return json_decode('[{"name":"Mobilia", "message" : " is using ", "link":"http://www.shopify.com"},{"name":"Mobilia Milan", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Mobilia Napa", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Mobilia Sydney", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Mobilia Tokyo", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
    }

    if(stripos($theme_keyword, 'Modular') !== false){ if($theme_keyword == 'Modular'){ return json_decode('[{"name":"Modular", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Modular", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Motion') !== false){ if($theme_keyword == 'Motion'){ return json_decode('[{"name":"Motion", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Motion", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Mr Parker') !== false){ if($theme_keyword == 'Mr Parker'){ return json_decode('[{"name":"Mr Parker", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Mr Parker", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Narrative') !== false){ if($theme_keyword == 'Narrative'){ return json_decode('[{"name":"Narrative", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Narrative", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Pacific') !== false){ if($theme_keyword == 'Pacific'){ return json_decode('[{"name":"Pacific", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Pacific", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}

    if(stripos($theme_keyword, 'Palo') !== false and stripos($theme_keyword, 'Alto') !== false){ 
        if($theme_keyword == 'Palo Alto'){ 
            return json_decode('[{"name":"Palo Alto", "message" : " is using ", "link":"http://www.shopify.com"}]');
        }else{ 
            return json_decode('[{"name":"Palo Alto", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');
        }
    }

    if(stripos($theme_keyword, 'Parallax') !== false){        
        if(stripos($theme_keyword, 'Aspen') !== false) {
            return json_decode('[{"name":"Parallax Aspen", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }
        
        if(stripos($theme_keyword, 'Los Angeles') !== false) {
            return json_decode('[{"name":"Parallax Los Angeles", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Madrid') !== false) {
            return json_decode('[{"name":"Parallax Madrid", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Vienna') !== false) {
            return json_decode('[{"name":"Parallax Vienna", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        return json_decode('[{"name":"Parallax", "message" : " is using ", "link":"http://www.shopify.com"},{"name":"Parallax Aspen", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Parallax Los Angeles", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Parallax Madrid", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Parallax Vienna", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
    }

    if(stripos($theme_keyword, 'Pipeline') !== false){ if($theme_keyword == 'Pipeline'){ return json_decode('[{"name":"Pipeline", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Pipeline", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Precia') !== false){ if($theme_keyword == 'Precia'){ return json_decode('[{"name":"Precia", "message" : " is using ", "link":"http://www.boostheme.com"}]');}else{ return json_decode('[{"name":"Precia", "message" : " is using a customized version of ", "link":"http://www.boostheme.com"}]');}}
    if(stripos($theme_keyword, 'Prestige') !== false){ if($theme_keyword == 'Prestige'){ return json_decode('[{"name":"Prestige", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Prestige", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Providence') !== false){ if($theme_keyword == 'Providence'){ return json_decode('[{"name":"Providence", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Providence", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Reach') !== false){ if($theme_keyword == 'Reach'){ return json_decode('[{"name":"Reach", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Reach", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Reach') !== false){ if($theme_keyword == 'Reach'){ return json_decode('[{"name":"Reach", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Reach", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}

    if(stripos($theme_keyword, 'Responsive') !== false){        
        if(stripos($theme_keyword, 'London') !== false) {
            return json_decode('[{"name":"Responsive London", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }
        
        if(stripos($theme_keyword, 'NewYork') !== false) {
            return json_decode('[{"name":"Responsive NewYork", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Paris') !== false) {
            return json_decode('[{"name":"Responsive Paris", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'San Fransisco') !== false) {
            return json_decode('[{"name":"Responsive San Fransisco", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        return json_decode('[{"name":"Responsive", "message" : " is using ", "link":"http://www.shopify.com"},{"name":"Responsive London", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Responsive NewYork", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Responsive Paris", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Responsive San Fransisco", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
    }

    if(stripos($theme_keyword, 'Retina') !== false){        
        if(stripos($theme_keyword, 'Amsterdam') !== false) {
            return json_decode('[{"name":"Retina Amsterdam", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }
        
        if(stripos($theme_keyword, 'Austin') !== false) {
            return json_decode('[{"name":"Retina Austin", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Melbourne') !== false) {
            return json_decode('[{"name":"Retina Melbourne", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Montreal') !== false) {
            return json_decode('[{"name":"Retina Montreal", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        return json_decode('[{"name":"Retina", "message" : " is using ", "link":"http://www.shopify.com"},{"name":"Retina Amsterdam", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Retina Austin", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Retina Melbourne", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Retina Montreal", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
    }

    if(stripos($theme_keyword, 'ShopCast') !== false){ if($theme_keyword == 'ShopCast'){ return json_decode('[{"name":"ShopCast", "message" : " is using ", "link":"http://www.boostheme.com"}]');}else{ return json_decode('[{"name":"ShopCast", "message" : " is using a customized version of ", "link":"http://www.boostheme.com"}]');}}
    if(stripos($theme_keyword, 'Showcase') !== false){ if($theme_keyword == 'Showcase'){ return json_decode('[{"name":"Showcase", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Showcase", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Showtime') !== false){ if($theme_keyword == 'Showtime'){ return json_decode('[{"name":"Showtime", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Showtime", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Simple ') !== false){ if($theme_keyword == 'Simple '){ return json_decode('[{"name":"Simple ", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Simple ", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Split') !== false){ if($theme_keyword == 'Split'){ return json_decode('[{"name":"Split", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Split", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Startup') !== false){ if($theme_keyword == 'Startup'){ return json_decode('[{"name":"Startup", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Startup", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Story') !== false){ if($theme_keyword == 'Story'){ return json_decode('[{"name":"Story", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Story", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Streamline') !== false){ if($theme_keyword == 'Streamline'){ return json_decode('[{"name":"Streamline", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Streamline", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Sunrise') !== false){ if($theme_keyword == 'Sunrise'){ return json_decode('[{"name":"Sunrise", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Sunrise", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'SuperStore') !== false){ if($theme_keyword == 'SuperStore'){ return json_decode('[{"name":"SuperStore", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"SuperStore", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Supply ') !== false){ if($theme_keyword == 'Supply '){ return json_decode('[{"name":"Supply ", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Supply ", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Symmetry') !== false){ if($theme_keyword == 'Symmetry'){ return json_decode('[{"name":"Symmetry", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Symmetry", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Testament') !== false){ if($theme_keyword == 'Testament'){ return json_decode('[{"name":"Testament", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Testament", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Trademark') !== false){ if($theme_keyword == 'Trademark'){ return json_decode('[{"name":"Trademark", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Trademark", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}

    if(stripos($theme_keyword, 'Turbo') !== false){        
        if(stripos($theme_keyword, 'Chicago') !== false) {
            return json_decode('[{"name":"Turbo Chicago", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }
        
        if(stripos($theme_keyword, 'Dubai') !== false) {
            return json_decode('[{"name":"Turbo Dubai", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Florence') !== false) {
            return json_decode('[{"name":"Turbo Florence", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Portland') !== false) {
            return json_decode('[{"name":"Turbo Portland", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Seoul') !== false) {
            return json_decode('[{"name":"Turbo Seoul", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        if(stripos($theme_keyword, 'Tennessee') !== false) {
            return json_decode('[{"name":"Turbo Tennessee", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
        }

        return json_decode('[{"name":"Turbo", "message" : " is using ", "link":"http://www.shopify.com"},{"name":"Turbo Chicago", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Turbo Dubai", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Turbo Florence", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Turbo Portland", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Turbo Seoul", "message" : " is using ", "link":"https://outofthesandbox.com/"},{"name":"Turbo Tennessee", "message" : " is using ", "link":"https://outofthesandbox.com/"}]');
    }

    if(stripos($theme_keyword, 'Vantage') !== false){ if($theme_keyword == 'Vantage'){ return json_decode('[{"name":"Vantage", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Vantage", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Venture') !== false){ if($theme_keyword == 'Venture'){ return json_decode('[{"name":"Venture", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Venture", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Venue') !== false){ if($theme_keyword == 'Venue'){ return json_decode('[{"name":"Venue", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Venue", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Vogue') !== false){ if($theme_keyword == 'Vogue'){ return json_decode('[{"name":"Vogue", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Vogue", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'Warehouse') !== false){ if($theme_keyword == 'Warehouse'){ return json_decode('[{"name":"Warehouse", "message" : " is using ", "link":"http://www.shopify.com"}]');}else{ return json_decode('[{"name":"Warehouse", "message" : " is using a customized version of ", "link":"http://www.shopify.com"}]');}}
    if(stripos($theme_keyword, 'WoodStock') !== false){ if($theme_keyword == 'WoodStock'){ return json_decode('[{"name":"WoodStock", "message" : " is using ", "link":"http://www.boostheme.com"}]');}else{ return json_decode('[{"name":"WoodStock", "message" : " is using a customized version of ", "link":"http://www.boostheme.com"}]');}}
    
    if(stripos($theme_keyword, 'Newelise') !== false){ if($theme_keyword == 'Newelise'){ return json_decode('[{"name":"Newelise", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Newelise", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Happy Pup') !== false){ if($theme_keyword == 'Happy Pup'){ return json_decode('[{"name":"Happy Pup", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Happy Pup", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Kardone') !== false){ if($theme_keyword == 'Kardone'){ return json_decode('[{"name":"Kardone", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Kardone", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Multifly') !== false){ if($theme_keyword == 'Multifly'){ return json_decode('[{"name":"Multifly", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Multifly", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'SpareParts') !== false){ if($theme_keyword == 'SpareParts'){ return json_decode('[{"name":"SpareParts", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"SpareParts", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Auto Parts') !== false){ if($theme_keyword == 'Auto Parts'){ return json_decode('[{"name":"Auto Parts", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Auto Parts", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Shopy') !== false){ if($theme_keyword == 'Shopy'){ return json_decode('[{"name":"Shopy", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Shopy", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Car Tuning') !== false){ if($theme_keyword == 'Car Tuning'){ return json_decode('[{"name":"Car Tuning", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Car Tuning", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Pet Shop') !== false){ if($theme_keyword == 'Pet Shop'){ return json_decode('[{"name":"Pet Shop", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Pet Shop", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Huge Sale') !== false){ if($theme_keyword == 'Huge Sale'){ return json_decode('[{"name":"Huge Sale", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Huge Sale", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Cook') !== false){ if($theme_keyword == 'Cook'){ return json_decode('[{"name":"Cook", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Cook", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Garden Furniture') !== false){ if($theme_keyword == 'Garden Furniture'){ return json_decode('[{"name":"Garden Furniture", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Garden Furniture", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Fun Toys') !== false){ if($theme_keyword == 'Fun Toys'){ return json_decode('[{"name":"Fun Toys", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Fun Toys", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Nutrition Suppliments') !== false){ if($theme_keyword == 'Nutrition Suppliments'){ return json_decode('[{"name":"Nutrition Suppliments", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Nutrition Suppliments", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Animals Pets') !== false){ if($theme_keyword == 'Animals Pets'){ return json_decode('[{"name":"Animals Pets", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Animals Pets", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Electronics Retailer') !== false){ if($theme_keyword == 'Electronics Retailer'){ return json_decode('[{"name":"Electronics Retailer", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Electronics Retailer", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Wholesale') !== false){ if($theme_keyword == 'Wholesale'){ return json_decode('[{"name":"Wholesale", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Wholesale", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Extreme') !== false){ if($theme_keyword == 'Extreme'){ return json_decode('[{"name":"Extreme", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Extreme", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Wine') !== false){ if($theme_keyword == 'Wine'){ return json_decode('[{"name":"Wine", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Wine", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Bon Voyage') !== false){ if($theme_keyword == 'Bon Voyage'){ return json_decode('[{"name":"Bon Voyage", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Bon Voyage", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Charming Jewelry') !== false){ if($theme_keyword == 'Charming Jewelry'){ return json_decode('[{"name":"Charming Jewelry", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Charming Jewelry", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Smart Gear') !== false){ if($theme_keyword == 'Smart Gear'){ return json_decode('[{"name":"Smart Gear", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Smart Gear", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Lingerie') !== false){ if($theme_keyword == 'Lingerie'){ return json_decode('[{"name":"Lingerie", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Lingerie", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Medical Equipment') !== false){ if($theme_keyword == 'Medical Equipment'){ return json_decode('[{"name":"Medical Equipment", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Medical Equipment", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Suitup') !== false){ if($theme_keyword == 'Suitup'){ return json_decode('[{"name":"Suitup", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Suitup", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Organic Cosmetics') !== false){ if($theme_keyword == 'Organic Cosmetics'){ return json_decode('[{"name":"Organic Cosmetics", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Organic Cosmetics", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Bike Store') !== false){ if($theme_keyword == 'Bike Store'){ return json_decode('[{"name":"Bike Store", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Bike Store", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'T-Shirt Designs') !== false){ if($theme_keyword == 'T-Shirt Designs'){ return json_decode('[{"name":"T-Shirt Designs", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"T-Shirt Designs", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'MMA Items') !== false){ if($theme_keyword == 'MMA Items'){ return json_decode('[{"name":"MMA Items", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"MMA Items", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Spice Shop') !== false){ if($theme_keyword == 'Spice Shop'){ return json_decode('[{"name":"Spice Shop", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Spice Shop", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Under Wear') !== false){ if($theme_keyword == 'Under Wear'){ return json_decode('[{"name":"Under Wear", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Under Wear", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Tea Store') !== false){ if($theme_keyword == 'Tea Store'){ return json_decode('[{"name":"Tea Store", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Tea Store", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Perfumes') !== false){ if($theme_keyword == 'Perfumes'){ return json_decode('[{"name":"Perfumes", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Perfumes", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Style Factory') !== false){ if($theme_keyword == 'Style Factory'){ return json_decode('[{"name":"Style Factory", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Style Factory", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Diving Online Store') !== false){ if($theme_keyword == 'Diving Online Store'){ return json_decode('[{"name":"Diving Online Store", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Diving Online Store", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Packaging') !== false){ if($theme_keyword == 'Packaging'){ return json_decode('[{"name":"Packaging", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Packaging", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Coffee Store') !== false){ if($theme_keyword == 'Coffee Store'){ return json_decode('[{"name":"Coffee Store", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Coffee Store", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Fishing Supplies') !== false){ if($theme_keyword == 'Fishing Supplies'){ return json_decode('[{"name":"Fishing Supplies", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Fishing Supplies", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Swimwear') !== false){ if($theme_keyword == 'Swimwear'){ return json_decode('[{"name":"Swimwear", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Swimwear", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Car Tires') !== false){ if($theme_keyword == 'Car Tires'){ return json_decode('[{"name":"Car Tires", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Car Tires", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Elegant Gifts') !== false){ if($theme_keyword == 'Elegant Gifts'){ return json_decode('[{"name":"Elegant Gifts", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Elegant Gifts", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Apparel') !== false){ if($theme_keyword == 'Apparel'){ return json_decode('[{"name":"Apparel", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Apparel", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Drug Store') !== false){ if($theme_keyword == 'Drug Store'){ return json_decode('[{"name":"Drug Store", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Drug Store", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Wheels and Tires') !== false){ if($theme_keyword == 'Wheels and Tires'){ return json_decode('[{"name":"Wheels and Tires", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Wheels and Tires", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'BerWear') !== false){ if($theme_keyword == 'BerWear'){ return json_decode('[{"name":"BerWear", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"BerWear", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Hamintec') !== false){ if($theme_keyword == 'Hamintec'){ return json_decode('[{"name":"Hamintec", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Hamintec", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Handbag Boutique') !== false){ if($theme_keyword == 'Handbag Boutique'){ return json_decode('[{"name":"Handbag Boutique", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Handbag Boutique", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Modern Furniture') !== false){ if($theme_keyword == 'Modern Furniture'){ return json_decode('[{"name":"Modern Furniture", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Modern Furniture", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Skiing') !== false){ if($theme_keyword == 'Skiing'){ return json_decode('[{"name":"Skiing", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Skiing", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Diamond') !== false){ if($theme_keyword == 'Diamond'){ return json_decode('[{"name":"Diamond", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Diamond", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Mobile Phones') !== false){ if($theme_keyword == 'Mobile Phones'){ return json_decode('[{"name":"Mobile Phones", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Mobile Phones", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Elegant Sunglasses') !== false){ if($theme_keyword == 'Elegant Sunglasses'){ return json_decode('[{"name":"Elegant Sunglasses", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Elegant Sunglasses", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Body Builder') !== false){ if($theme_keyword == 'Body Builder'){ return json_decode('[{"name":"Body Builder", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Body Builder", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Apparelix') !== false){ if($theme_keyword == 'Apparelix'){ return json_decode('[{"name":"Apparelix", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Apparelix", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Vanessa') !== false){ if($theme_keyword == 'Vanessa'){ return json_decode('[{"name":"Vanessa", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Vanessa", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Cosmetorix') !== false){ if($theme_keyword == 'Cosmetorix'){ return json_decode('[{"name":"Cosmetorix", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Cosmetorix", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Military') !== false){ if($theme_keyword == 'Military'){ return json_decode('[{"name":"Military", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Military", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Halloween Costumes') !== false){ if($theme_keyword == 'Halloween Costumes'){ return json_decode('[{"name":"Halloween Costumes", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Halloween Costumes", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Flooring Store') !== false){ if($theme_keyword == 'Flooring Store'){ return json_decode('[{"name":"Flooring Store", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Flooring Store", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Weapon Shop') !== false){ if($theme_keyword == 'Weapon Shop'){ return json_decode('[{"name":"Weapon Shop", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Weapon Shop", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Quality Wheels and Tires') !== false){ if($theme_keyword == 'Quality Wheels and Tires'){ return json_decode('[{"name":"Quality Wheels and Tires", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Quality Wheels and Tires", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Travel Agency') !== false){ if($theme_keyword == 'Travel Agency'){ return json_decode('[{"name":"Travel Agency", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Travel Agency", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Urban Tere') !== false){ if($theme_keyword == 'Urban Tere'){ return json_decode('[{"name":"Urban Tere", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Urban Tere", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Printanet') !== false){ if($theme_keyword == 'Printanet'){ return json_decode('[{"name":"Printanet", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Printanet", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Survival') !== false){ if($theme_keyword == 'Survival'){ return json_decode('[{"name":"Survival", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"Survival", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'True TShirts') !== false){ if($theme_keyword == 'True TShirts'){ return json_decode('[{"name":"True TShirts", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"True TShirts", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'BMX') !== false){ if($theme_keyword == 'BMX'){ return json_decode('[{"name":"BMX", "message" : " is using ", "link":"http://www.templatemonster.com"}]');}else{ return json_decode('[{"name":"BMX", "message" : " is using a customized version of ", "link":"http://www.templatemonster.com"}]');}}
    if(stripos($theme_keyword, 'Wokiee') !== false){ if($theme_keyword == 'Wokiee'){ return json_decode('[{"name":"Wokiee", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Wokiee", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Avone') !== false){ if($theme_keyword == 'Avone'){ return json_decode('[{"name":"Avone", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Avone", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Proto') !== false){ if($theme_keyword == 'Proto'){ return json_decode('[{"name":"Proto", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Proto", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Gecko') !== false){ if($theme_keyword == 'Gecko'){ return json_decode('[{"name":"Gecko", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Gecko", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Barberry') !== false){ if($theme_keyword == 'Barberry'){ return json_decode('[{"name":"Barberry", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Barberry", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Lezada') !== false){ if($theme_keyword == 'Lezada'){ return json_decode('[{"name":"Lezada", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Lezada", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Molla') !== false){ if($theme_keyword == 'Molla'){ return json_decode('[{"name":"Molla", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Molla", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Elessi') !== false){ if($theme_keyword == 'Elessi'){ return json_decode('[{"name":"Elessi", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Elessi", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Belle') !== false){ if($theme_keyword == 'Belle'){ return json_decode('[{"name":"Belle", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Belle", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Banita') !== false){ if($theme_keyword == 'Banita'){ return json_decode('[{"name":"Banita", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Banita", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Yanka') !== false){ if($theme_keyword == 'Yanka'){ return json_decode('[{"name":"Yanka", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Yanka", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Seiko') !== false){ if($theme_keyword == 'Seiko'){ return json_decode('[{"name":"Seiko", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Seiko", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Material') !== false){ if($theme_keyword == 'Material'){ return json_decode('[{"name":"Material", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Material", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Avenue') !== false){ if($theme_keyword == 'Avenue'){ return json_decode('[{"name":"Avenue", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Avenue", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Boutique') !== false){ if($theme_keyword == 'Boutique'){ return json_decode('[{"name":"Boutique", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Boutique", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Basel') !== false){ if($theme_keyword == 'Basel'){ return json_decode('[{"name":"Basel", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Basel", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Goodwin') !== false){ if($theme_keyword == 'Goodwin'){ return json_decode('[{"name":"Goodwin", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Goodwin", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Simolux') !== false){ if($theme_keyword == 'Simolux'){ return json_decode('[{"name":"Simolux", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Simolux", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Minimal') !== false){ if($theme_keyword == 'Minimal'){ return json_decode('[{"name":"Minimal", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Minimal", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Shella') !== false){ if($theme_keyword == 'Shella'){ return json_decode('[{"name":"Shella", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Shella", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Multikart') !== false){ if($theme_keyword == 'Multikart'){ return json_decode('[{"name":"Multikart", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Multikart", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'MegaShop') !== false){ if($theme_keyword == 'MegaShop'){ return json_decode('[{"name":"MegaShop", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"MegaShop", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Beauty') !== false){ if($theme_keyword == 'Beauty'){ return json_decode('[{"name":"Beauty", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Beauty", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Fashion') !== false){ if($theme_keyword == 'Fashion'){ return json_decode('[{"name":"Fashion", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Fashion", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Elomus') !== false){ if($theme_keyword == 'Elomus'){ return json_decode('[{"name":"Elomus", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Elomus", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Forward') !== false){ if($theme_keyword == 'Forward'){ return json_decode('[{"name":"Forward", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Forward", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Ultimate') !== false){ if($theme_keyword == 'Ultimate'){ return json_decode('[{"name":"Ultimate", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Ultimate", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Artemis') !== false){ if($theme_keyword == 'Artemis'){ return json_decode('[{"name":"Artemis", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Artemis", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Chair') !== false){ if($theme_keyword == 'Chair'){ return json_decode('[{"name":"Chair", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Chair", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Shopme') !== false){ if($theme_keyword == 'Shopme'){ return json_decode('[{"name":"Shopme", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Shopme", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Yoriver') !== false){ if($theme_keyword == 'Yoriver'){ return json_decode('[{"name":"Yoriver", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Yoriver", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Handmade') !== false){ if($theme_keyword == 'Handmade'){ return json_decode('[{"name":"Handmade", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Handmade", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Plantmore') !== false){ if($theme_keyword == 'Plantmore'){ return json_decode('[{"name":"Plantmore", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Plantmore", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Kaline') !== false){ if($theme_keyword == 'Kaline'){ return json_decode('[{"name":"Kaline", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Kaline", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Domino') !== false){ if($theme_keyword == 'Domino'){ return json_decode('[{"name":"Domino", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Domino", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Bardy') !== false){ if($theme_keyword == 'Bardy'){ return json_decode('[{"name":"Bardy", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Bardy", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'BStore') !== false){ if($theme_keyword == 'BStore'){ return json_decode('[{"name":"BStore", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"BStore", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'WineYard') !== false){ if($theme_keyword == 'WineYard'){ return json_decode('[{"name":"WineYard", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"WineYard", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Hooli') !== false){ if($theme_keyword == 'Hooli'){ return json_decode('[{"name":"Hooli", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Hooli", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Qrack') !== false){ if($theme_keyword == 'Qrack'){ return json_decode('[{"name":"Qrack", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Qrack", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'HugeShop') !== false){ if($theme_keyword == 'HugeShop'){ return json_decode('[{"name":"HugeShop", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"HugeShop", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Devita') !== false){ if($theme_keyword == 'Devita'){ return json_decode('[{"name":"Devita", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Devita", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Baby') !== false){ if($theme_keyword == 'Baby'){ return json_decode('[{"name":"Baby", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Baby", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Mooboo') !== false){ if($theme_keyword == 'Mooboo'){ return json_decode('[{"name":"Mooboo", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Mooboo", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Lion') !== false){ if($theme_keyword == 'Lion'){ return json_decode('[{"name":"Lion", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Lion", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Medik') !== false){ if($theme_keyword == 'Medik'){ return json_decode('[{"name":"Medik", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Medik", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Oreo') !== false){ if($theme_keyword == 'Oreo'){ return json_decode('[{"name":"Oreo", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Oreo", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Diva') !== false){ if($theme_keyword == 'Diva'){ return json_decode('[{"name":"Diva", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Diva", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Natural') !== false){ if($theme_keyword == 'Natural'){ return json_decode('[{"name":"Natural", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Natural", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'AzShop') !== false){ if($theme_keyword == 'AzShop'){ return json_decode('[{"name":"AzShop", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"AzShop", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Smart Market') !== false){ if($theme_keyword == 'Smart Market'){ return json_decode('[{"name":"Smart Market", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Smart Market", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Ruby') !== false){ if($theme_keyword == 'Ruby'){ return json_decode('[{"name":"Ruby", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Ruby", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Supro') !== false){ if($theme_keyword == 'Supro'){ return json_decode('[{"name":"Supro", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Supro", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Noren') !== false){ if($theme_keyword == 'Noren'){ return json_decode('[{"name":"Noren", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Noren", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'NexGeek') !== false){ if($theme_keyword == 'NexGeek'){ return json_decode('[{"name":"NexGeek", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"NexGeek", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Unive') !== false){ if($theme_keyword == 'Unive'){ return json_decode('[{"name":"Unive", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Unive", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Uniqlo') !== false){ if($theme_keyword == 'Uniqlo'){ return json_decode('[{"name":"Uniqlo", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Uniqlo", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Chrono Dial') !== false){ if($theme_keyword == 'Chrono Dial'){ return json_decode('[{"name":"Chrono Dial", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Chrono Dial", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Helas') !== false){ if($theme_keyword == 'Helas'){ return json_decode('[{"name":"Helas", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Helas", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Marketo') !== false){ if($theme_keyword == 'Marketo'){ return json_decode('[{"name":"Marketo", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Marketo", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'ActiveWear') !== false){ if($theme_keyword == 'ActiveWear'){ return json_decode('[{"name":"ActiveWear", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"ActiveWear", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'SmartBooks') !== false){ if($theme_keyword == 'SmartBooks'){ return json_decode('[{"name":"SmartBooks", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"SmartBooks", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Bewear') !== false){ if($theme_keyword == 'Bewear'){ return json_decode('[{"name":"Bewear", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Bewear", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Hebes') !== false){ if($theme_keyword == 'Hebes'){ return json_decode('[{"name":"Hebes", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Hebes", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Kenza') !== false){ if($theme_keyword == 'Kenza'){ return json_decode('[{"name":"Kenza", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Kenza", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Focus') !== false){ if($theme_keyword == 'Focus'){ return json_decode('[{"name":"Focus", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Focus", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Chromium') !== false){ if($theme_keyword == 'Chromium'){ return json_decode('[{"name":"Chromium", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Chromium", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Wintertime') !== false){ if($theme_keyword == 'Wintertime'){ return json_decode('[{"name":"Wintertime", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Wintertime", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Everest') !== false){ if($theme_keyword == 'Everest'){ return json_decode('[{"name":"Everest", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Everest", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Citrus') !== false){ if($theme_keyword == 'Citrus'){ return json_decode('[{"name":"Citrus", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Citrus", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Mira') !== false){ if($theme_keyword == 'Mira'){ return json_decode('[{"name":"Mira", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Mira", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Petmart') !== false){ if($theme_keyword == 'Petmart'){ return json_decode('[{"name":"Petmart", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Petmart", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Fastor') !== false){ if($theme_keyword == 'Fastor'){ return json_decode('[{"name":"Fastor", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Fastor", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'GameStore') !== false){ if($theme_keyword == 'GameStore'){ return json_decode('[{"name":"GameStore", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"GameStore", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'O2 Club') !== false){ if($theme_keyword == 'O2 Club'){ return json_decode('[{"name":"O2 Club", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"O2 Club", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Maxmin') !== false){ if($theme_keyword == 'Maxmin'){ return json_decode('[{"name":"Maxmin", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Maxmin", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Granada') !== false){ if($theme_keyword == 'Granada'){ return json_decode('[{"name":"Granada", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Granada", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Eighty Three') !== false){ if($theme_keyword == 'Eighty Three'){ return json_decode('[{"name":"Eighty Three", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Eighty Three", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Aero') !== false){ if($theme_keyword == 'Aero'){ return json_decode('[{"name":"Aero", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Aero", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}
    if(stripos($theme_keyword, 'Sneaker') !== false){ if($theme_keyword == 'Sneaker'){ return json_decode('[{"name":"Sneaker", "message" : " is using ", "link":"http://www.themeforest.net"}]');}else{ return json_decode('[{"name":"Sneaker", "message" : " is using a customized version of ", "link":"http://www.themeforest.net"}]');}}

    return json_decode('[{"name":"None", "link":""}]');

}