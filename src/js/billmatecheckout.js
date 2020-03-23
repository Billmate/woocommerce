/**
 * Created by Boxedsolutions on 2017-03-01.
 */
window.method = null;
window.address_selected = null;
window.hash = null;
window.latestScroll = null;

jQuery(document).ready(function(){
var BillmateIframe = new function(){
    var self = this;
    var childWindow = null;
    var timerPostMessage;

    var currentCustomerBillingZip;
    var currentOrderComments;
    var orderTotal;

    this.updateAddress = function (data) {
        // When address in checkout updates;
        data.action = 'billmate_update_address';

        if (data.hasOwnProperty('Customer') && data.hasOwnProperty('billingAddress')) {
            data.Customer.Billing = data.billingAddress;
        }

        /**
         * Support WooCommerce Shipping calculation
         */
         if (   jQuery('form.woocommerce-shipping-calculator').length > 0
                && jQuery('#calc_shipping_postcode').length > 0) {
            zip = '';
            if (data.hasOwnProperty('Customer') && data.Customer.hasOwnProperty('Billing') && data.Customer.Billing.hasOwnProperty('zip') && data.Customer.Billing.zip != '') {
                zip = data.Customer.Billing.zip.replace(/[^0-9\.]/g, '');
            }
            if (data.hasOwnProperty('Customer') && data.Customer.hasOwnProperty('Shipping') && data.Customer.Shipping.hasOwnProperty('zip') && data.Customer.Shipping.zip != '') {
                zip = data.Customer.Shipping.zip.replace(/[^0-9\.]/g, '');
            }
            if (zip != '' && (zip != this.currentCustomerBillingZip || zip != jQuery('#calc_shipping_postcode').val())) {
                this.currentCustomerBillingZip = zip;
                jQuery('#calc_shipping_postcode').val(zip);
                jQuery('form.woocommerce-shipping-calculator').submit();
            }
         }

        self.showCheckoutLoading();
        jQuery.ajax({
            url : billmate.ajax_url,
            data: data,
            type: 'POST',
            success: function(response){
                
                if (response.hasOwnProperty("data") && response.data.hasOwnProperty("order_total")) {
                    if (self.orderTotal != undefined && self.orderTotal != response.data.order_total) {
                        self.lock();
                        location.reload(true);
                        return true;
                    }
                    self.orderTotal = response.data.order_total;
                }

                if(response.hasOwnProperty("success") && response.success) {
                    window.address_selected = true;
                    if (response.hasOwnProperty('data') && response.data.hasOwnProperty('update_checkout') && response.data.update_checkout == true) {
                        self.updateCheckout();
                    } else {
                        self.hideCheckoutLoading();
                    }
                } else {
                    self.hideCheckoutLoading();
                }
            }
        });

    };
    this.updateBillmate = function(){
        self.showCheckoutLoading();
        jQuery.ajax({
            url : billmate.ajax_url,
            data: {action: 'billmate_update_order',hash: window.hash},
            type: 'POST',
            success: function(response){

                if (response.hasOwnProperty("data") && response.data.hasOwnProperty("data") && response.data.data.hasOwnProperty("order_total")) {
                    self.orderTotal = response.data.data.order_total;
                }

                if(response.hasOwnProperty("success") && response.success) {
                    window.address_selected = true;
                }

                if (response.hasOwnProperty("data") && response.data.hasOwnProperty("reload_checkout") && response.data.reload_checkout == true) {
                    location.reload(true);
                    return true;
                } else {
                    self.updateCheckout();
                }
            }
        });
    };

    this.updateShippingMethod = function(){

    }
    this.createOrder = function(data){
        // Create Order
        if (data == null){
            data = {action: 'billmate_complete_order',hash: window.hash};
        }
        else {
            data.action = 'billmate_complete_order';
        }
        self.showCheckoutLoading();
        jQuery.ajax({
            url : billmate.ajax_url,
            data: data,
            type: 'POST',
            success: function(response){
                if(response.hasOwnProperty("success") && response.success)
                    location.href=response.data.url;
            }
        });

    };

    this.updateTotals = function(){
        self.showCheckoutLoading();
        jQuery.ajax({
            url : UPDATE_TOTALS_URL,
            type: 'POST',
            success: function(response){
                jQuery('#billmate-totals').html(response);
            }
        });
    };
    this.updateOrderComments = function () {
        data = {};
        data.action = 'billmate_update_order_comments';
        data.order_comments = jQuery(document).find('#order_comments').val();
        jQuery.ajax({
            url: billmate.ajax_url,
            data: data,
            type: 'POST',
            success: function (response) {
            }
        });
    };
    this.initListeners = function () {
        jQuery(document).ready(function () {
            window.addEventListener("message",self.handleEvent);

        });

        /* Listen to WooCommerce checkout elements */
        jQuery(document).on('click', "input[name='update_cart']", function() {
            self.lock();
        });
        jQuery( document ).on('click', 'div.woocommerce > form input[type=submit]', function() {
            self.lock();
        });
        jQuery( document ).on('keypress', 'div.woocommerce > form input[type=number]', function() {
            self.lock();
        });
        jQuery( document ).on('submit', 'div.woocommerce:not(.widget_product_search) > form', function() {
            self.lock();
        });
        jQuery( document ).on('click', 'a.woocommerce-remove-coupon', function() {
            self.lock();
        });
        jQuery( document ).on('click', 'td.product-remove > a', function() {
            self.lock();
        });
        jQuery( document ).on('change', 'select.shipping_method, input[name^=shipping_method]', function() {
            self.lock();
        });

        // order comment
        jQuery(document).on('input change', '#order_comments', function() {
            that = self;
            newVal = jQuery(this).val();
            if (that.currentOrderComments != newVal) {
                clearTimeout(jQuery.data(this, 'timer'));
                var wait = setTimeout(function () {
                    that.updateOrderComments();
                    that.currentOrderComments = newVal;
                }, 500);
                jQuery(this).data('timer', wait);
            }
        });

        /* Updated cart totals */
        jQuery(document.body).on('updated_cart_totals',function(e){
            self.updateBillmate();
        });

    }
    this.handleEvent = function(event){
        if(event.origin == "https://checkout.billmate.se") {
            try {
                var json = JSON.parse(event.data);
            } catch (e) {
                return;
            }
            self.childWindow = json.source;
            switch (json.event) {
                case 'show_overlay':
                    if (billmate.overlayEnabled) {
                        if (jQuery(document).find('#billmateCheckoutOverlay').length < 1) {
                            var $div = jQuery('<div />').appendTo('body');
                            $div.attr('id', 'billmateCheckoutOverlay');
                        }
                        resizeBillmateCheckoutOverlay();
                        jQuery('body').addClass('billmate-checkout-overlay');
                    }
                    break;
                case 'hide_overlay':
                    if (billmate.overlayEnabled) {
                        jQuery('body').removeClass('billmate-checkout-overlay');
                    }
                    break;
                case 'go_to':
                    location.href = json.data;
                    break;
                case 'address_selected':
                    self.updateAddress(json.data);
                    //self.updateTotals();
                    break;
                case 'payment_method_selected':

                    if (window.address_selected !== null) {
                        //self.updateTotals();
                    }
                    break;
                case 'checkout_success':
                    self.createOrder(json.data);
                    break;
                case 'content_height':
                    jQuery('#checkout').height(json.data);
                    break;
                case 'checkout_loaded':
                    self.hideCheckoutLoading();
                    break;
                default:
                    break;

            }
        }

    };

    this.checkoutPostMessage = function(message) {
        var checkout = document.getElementById('checkout');
        if (checkout != null) {
            var win = checkout.contentWindow;
            win.postMessage(message,'*');
        }
    }

    this.updateCheckout = function(){
        var checkout = document.getElementById('checkout');
        if (checkout == null) {
            location.reload(true);
            return false;
        }
        this.lock();
        this.checkoutPostMessage('update');
    }

    this.lock = function() {
        that = this;
        clearTimeout(this.timerPostMessage);
        var wait = setTimeout(function() {
            that.checkoutPostMessage('lock');
        }, 500);
        this.timerPostMessage = wait;
    }

    this.unlock = function() {
        that = this;
        clearTimeout(this.timerPostMessage);
        var wait = setTimeout(function() {
            that.checkoutPostMessage('unlock');
        }, 500);
        this.timerPostMessage = wait;
    }

    var showCheckoutLoadingCounter = 0;
    this.showCheckoutLoading = function() {
        if(showCheckoutLoadingCounter < 1) {
            showCheckoutLoadingCounter = 0;
        }

        showCheckoutLoadingCounter++;
        self.lock();
    }

    this.hideCheckoutLoading = function() {
        showCheckoutLoadingCounter--;

        if(showCheckoutLoadingCounter < 1) {
            showCheckoutLoadingCounter = 0;
            self.unlock();
        }
    }

};

    function resizeBillmateCheckoutOverlay() {
        if (jQuery(document).find('#billmateCheckoutOverlay').length > 0) {
            height = jQuery(document).innerHeight();
            if (jQuery(window).height() + jQuery(window).scrollTop() > height) {
                height = jQuery(window).height() + jQuery(window).scrollTop();
            }
            width = jQuery(document).innerWidth();
            jQuery("#billmateCheckoutOverlay").height(height);
            jQuery("#billmateCheckoutOverlay").width(width);
        }
    }

    jQuery(window).resize(function () {
        resizeBillmateCheckoutOverlay();
    });

    jQuery(document).resize(function () {
        resizeBillmateCheckoutOverlay();
    });

    jQuery(document).scroll(function () {
        resizeBillmateCheckoutOverlay();
    });

    var b_iframe = BillmateIframe;
    b_iframe.initListeners();

    jQuery(document).on('click','.billmate-item-remove',function(e){
        e.preventDefault();

        ancestor = $(this).closest('tr').find('td.product-quantity');
        item_row = $(this).closest('tr');
        cart_item_key = item_row.data('cart_item');
        jQuery.ajax({
            url: '',
            data: {
                action: 'billmate_checkout_remove_item',
                cart_item_key_remove: cart_item_key
            },
            
        })

    })
});
