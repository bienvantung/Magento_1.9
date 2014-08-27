//Add to wishlist for customer
function ajaxWishlistCustomer(url, id){
    url += 'isAjax/1/';
    var data1 = jQuery('#product_addtocart_form').serialize();
    data1 += '&isAjax=1';
    jQuery('#ajax_loading'+id).css('display','block');
    jQuery.ajax({
        type : 'POST',
        url : url,
        data : data1,
        dataType : 'json',
        success: function(data) {
            jQuery('#ajax_loading'+id).css('display','none');
            if(data.status== 'ERROR'){
                alert(data.message);
            }
            alert(data.message);
        }
    });
}
//Add to wishlist for guest
function ajaxWishlistGuest(url, id){
    url += 'isAjax/1/';
    jQuery('#ajax_loading'+id).css('display','block');
    jQuery.ajax({
        url : url,
        dataType : 'json',
        success: function(data) {
            jQuery('#ajax_loading'+id).css('display','none');
            if(data.status== 'ERROR'){
                alert(data.message);
            }else{
                alert(data.message);
                jQuery('.wishlist-guest').html(data.count);
            }

        }
    });
}