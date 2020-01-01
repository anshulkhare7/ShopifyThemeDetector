(function( $ ) {
	'use strict';
    var debug = 0;    
    var shopify_url = '';

    $(function() {      
        var base_url = $('#ajax_prefix').val();              
        var base_ajax = base_url + "/wp-admin/admin-ajax.php?action=si_get_theme";        
        var loading_img = '<img src="'+base_url+'/wp-content/plugins/shopify-inspector/public/img/loading.webp">';

        $('#btn-detect-theme').click(function(){
            shopify_url = $('#si_website').val().trim()
            debug_log('Entered website: '+shopify_url)
            if(!validateURL(shopify_url)){
                debug_log('URL Validation failed: '+shopify_url);                
                $('#si-error-msg').show();
                $('#si-error-msg').fadeOut(2000, "swing");
                return 0;
            }                        

            $('#si-result-container').html('');
            $('#si-loading-container').html(loading_img);            
            
            $.post(base_ajax, {"si_website":shopify_url}).done(function(response){ 
                var result = JSON.parse(response)                
                debug_log('Response'+result['themes'])
                if(result['status']==1){
                    var html_item = "";
                    var shopify_url_sanitized = sanitize_url(shopify_url);
                    $('#si-loading-container').html('');  
                    
                    if(result['themes'].length==1){
                        var theme = result['themes'][0];
                        debug_log(theme);
                        if(theme['name']=='NoShopify'){
                            html_item = "<p>"+shopify_url_sanitized + theme['message']+"</p>"
                        } else if(theme['name']=='None'){
                            html_item = "<p>Sorry! I tried, but I am unable to pin point at the theme "+shopify_url_sanitized+" is using.<br>Check out in the theme Marketplaces section for more Shopify themes.<br>Hope you will find a theme for your Dream Shopify Store.</p>"                            
                        }else{
                            html_item = '<p>' +shopify_url_sanitized + theme['message']  +'<a href="'+theme['link']+'" target="_blank">'+theme['name']+'</a></p>';
                        }                        
                    }

                    if(result['themes'].length>1){
                        html_item = "<p>Sorry! I tried, but I am unable to pin point at the theme. <br>The store " + shopify_url_sanitized + " could have been built using one of the below themes.</p>";
                        //html_item = "<p>"+shopify_url_sanitized + ' is using one of the following Themes...</p>';
                        $.each(result['themes'], function(index, data){
                            html_item = html_item + '<p><a href="'+data['link']+'" target="_blank">'+data['name']+'</a></p>';                        
                        }); 
                    }

                    $('#si-result-container').append(html_item);
                }
            }).fail(function(){
                debug_log('Error: failed to get ajax response.')
            });

        });        
    });
    
    function debug_log(message, show){
        if(show)
            debug_log(message)        
    }

    function debug_log(message){
        if(debug)
            console.log(message)
    }

    function validateURL(url_string){
        // var expression = '[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)';
        var expression = "^(https?://)?(www\\.)?([-a-z0-9]{1,63}\\.)*?[a-z0-9][-a-z0-9]{0,61}[a-z0-9]\\.[a-z]{2,6}(/[-\\w@\\+\\.~#\\?&/=%]*)?$";
        var regex = new RegExp(expression);
        if (url_string.match(regex)) {
            return true;
        } 
        return false;
    }

    function sanitize_url(website_url){
        website_url = website_url.replace(/\/$/, "");
        website_url = website_url.replace(/^https:\/\//, "");
        website_url = website_url.replace(/^http:\/\//, "");
        return website_url;
    }	
    
})( jQuery );