$('.qvisibility-plugin').click(function (event) {
    var products = $.product_list.getSelectedProducts(true);
    if (!products.count) {
        alert($_('Please select at least one product'));
        return false;
    }
	
	var status = $(event.currentTarget).data('status');
    $.post('?plugin=qvisibility&module=save', $(this).serializeArray().concat(products.serialized, {name: 'status', value: status}), function (response) {
		$.products.dispatch();
    });
    return false;
});