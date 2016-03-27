/**
 * Created by Nikita on 16.03.2016.
 */
$(document).ready(function(){
    var emailExpr = /^([\w-]+(?:\.[\w-]+)*)@((?:[\w-]+\.)*\w[\w-]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$/i;
    var discount = 0;
    var emailData = JSON.parse($('.shippingDescription #emailData').val());
    var phoneData = JSON.parse($('.shippingDescription #phoneData').val());
    var payData = JSON.parse($('.shippingDescription #payData').val());
    var deliverySelector = $('.shippingDescription select');
    var phoneInput = $('[name="customer[phone]"]');
    var emailInput = $('[name="customer[email][value]"]');

    deliverySelector.change(function(){
        $(document).trigger('deliveryUpdated', 'shipping');
    });

    $(document).on('deliveryUpdated', function(e, target){
        target = target || 'shipping';
        var selectedOption = deliverySelector.find('option:selected');
        var shipping = selectedOption.data('shipping');
        var shippingVal = deliverySelector.val();
        $('[name=shipping_id][value='+shipping+']').click().change();
        $('.shippingDescription .description').html(shippingVal === 'msc3' ? '' : selectedOption.data('description'));
        $('[name="rate_id[' + shipping + ']"]').val(shippingVal);
        if(target == 'shipping') {
            changeDiscount(selectedOption.data('discount-text'));
        } else {
            changeDiscount(payData[$('.payment [name=payment_id]:checked').val()].discount_text);
        }
    });

    $('.PickPointOpen').html('Выберите пункт выдачи');

    // $('.payment [name=payment_id]').click(function(){
    //     changeDiscount(payData[$(this).val()].discount_text);
    // });

    phoneInput.keypress(function(){
        if($(this).val().trim().length >= 3){
            changeDiscount(phoneData.discount_text);
        } else {
            changeDiscount(payData[$('.payment [name=payment_id]:checked').val()].discount_text);
        }
    });

    emailInput.keypress(function(){
        if(emailExpr.test($(this).val())){
            changeDiscount(emailData.discount_text);
        }
    });

    function changeDiscount(text){
        var discount = 0;
        if(deliverySelector.val() !== 'msc3') {
            discount += parseFloat(deliverySelector.find('option:selected').data('discount').replace(',','.'));
            var payment = $('.payment [name=payment_id]:checked');
            if(payment.length > 0) {
                discount += parseFloat(payData[payment.val()].discount);
            }
            if (phoneInput.length && phoneInput.val().trim().length >= 3) {
                discount += parseFloat(phoneData.discount);
            }
            if (emailInput.length && emailExpr.test(emailInput.val().trim())) {
                discount += parseFloat(emailData.discount);
            }
            text = text.replace('{discount}', '<span class="pink">' + discount + '%</span>');
        } else {
            text = '';
        }
        var $total = $('#total');
        $total.html(Math.round(($total.data('total') * (1 - discount/100))) + ' руб.');
        $('#cartForm').find('[name=discount]').val(discount);
        $('.discount_question_text').html(text);
        animate();
    }

    function animate(){
        var $total = $('#total');
        $total.addClass('font-size-anim');
        setTimeout(function(){
            $total.removeClass('font-size-anim');
        }, 2000);
    }
});