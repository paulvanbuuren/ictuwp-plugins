(function($){

    jQuery('table.tags #the-list').sortable({
      
        'items': 'tr',
        'axis': 'y',
        'update' : function(e, ui) {
           
                                    var order       =   jQuery('#the-list').sortable('serialize');
                                    var sort_nonce  =   ATTO_vars.nonce;                                               
                                    var queryString = { "action": "update-taxonomy-order-default-list", "order" : order, 'taxonomy' :   ATTO_vars.taxonomy, 'paged' :  ATTO_vars.paged ,  "nonce" : sort_nonce};
                                    
                                    //send the data through ajax
                                    jQuery.ajax({
                                      type: 'POST',
                                      url: ajaxurl,
                                      data: queryString,
                                      cache: false,
                                      dataType: "html",
                                      success: function(data){

                                      },
                                      error: function(html){

                                          }
                                    });
                                
                                }
        
        
    });     
})(jQuery)  