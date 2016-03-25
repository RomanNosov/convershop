$(function() {

	var writeLineCountCookie = function() {
        var line_count = Math.floor(($(window).height() - 270) / 14);
        document.cookie = 'lines_per_page=' + line_count + '; path=' + location.pathname + ';';
    };

    var isFile = function() {
        return $('.file-data').length > 0;
    };

    var fixFirstPageLink = function() {
        var link = $('.pagination a').filter(function() {
            return $(this).text() == '1';
        }).eq(0);
        link.attr('href', link.attr('href') + '&page=1');
    };

    var adjustPagination = function() {
        var pagination = $('.pagination > ul');
        var pagination_width = 0;
        pagination_width += parseInt(pagination.css('margin-left')) + parseInt(pagination.css('margin-right'));
        pagination.children('li').each(function() {
            var li = $(this);
            pagination_width += li.width()
	            + parseInt(li.css('margin-left'), 10)
	            + parseInt(li.css('margin-right'), 10)
	            + parseInt(li.css('padding-left'), 10)
	            + parseInt(li.css('padding-right'), 10);
        });
        pagination.css('margin-left', Math.floor((pagination.parent().width() - pagination_width) / 2) + 'px');
    };

    var scrollDown = function(selector) {
    	selector += ':first';
    	$(selector).animate({
    		scrollTop: $(selector).prop('scrollHeight')
		}, 1000);
    };

    var adjustFileContentsHeight = function() {
        var file_contents = $('.file-contents:first');
        file_contents.css('height', 'auto');  //reset to auto height for a fresh start after previous adjustment
        var document_height = $(document).height();
        if ($.browser.msie) {
            document_height -= 5; //hack for IE
        }
        var height_diff = document_height - $(window).height();
        if (height_diff > 0) {
            var bottom_margin = 10;
            file_contents.height(file_contents.height() - height_diff - bottom_margin);
        } else {
            if ($('.get-more.disabled').length > 0) {
                var enabled_button = $('.get-more').not('.disabled').eq(0);
                var disabled_button = $('.get-more.disabled').eq(0);
                file_contents.height(file_contents.height() + enabled_button.height()
                                                            - parseInt(disabled_button.css('padding-top'))
                                                            - parseInt(disabled_button.css('padding-bottom')));
            }
        }
    };

    //delete file
    $('.delete').click(function() {
        var delete_button = $(this);
        var path = delete_button.children('.path-value:first').attr('value');
        $('<h1>' + $.loc['Delete file'] + ' <span class="gray">' + path + '</span>?</h1>'
        + '<p class="error hidden"></p>'
        + '<input name="path" type="hidden" value="' + path + '">').waDialog({
            'buttons': '<input type="submit" value="' + $.loc['Delete'] + '" class="button blue small">&nbsp;'
            	+ '<a href="" class="cancel">' + $.loc['cancel'] + '</a>',
            'height': '150px',
            onSubmit: function (dialog) {
            	dialog.find('.error').addClass('hidden').empty();
                dialog.find('.cancel').after('<i class="icon16 loading left-margin"></i>');
                var update_size_param = $('.total-size').length > 0 ? '&update_size=1' : '';
                $.post('?action=delete' + update_size_param, $(this).serialize(), function(response) {
                    if (response.status == 'fail') {
                    	dialog.find('.loading').remove();
                    	dialog.find('.error').removeClass('hidden').html(response.errors.join('<br>'));
                    } else {
                    	if (isFile()) {
                            location.href = delete_button.children('.return-url-value:first').attr('value');
                        } else {
                            delete_button.parents('li:first').remove();
                            if ($('.total-size').length > 0) {
                                $('.total-size').text(response.data.total_size);
                            } else if ($('.item-list li').length < 1) {
                                $('.item-list').hide();
                                $('.total-size').hide();
                                $('.no-logs-message').show();
                            }
                            dialog.trigger('close');
                        }
                    }
                }, 'json');
                return false;
            },
			onClose: function() {
            	$(this).remove();
            }
        });
    });

    //download more file contents via AJAX
    $('.get-more').click(function() {
        var button = $(this);
        var arrow = button.find('.arrow');
        if (arrow.hasClass('hidden')) {
        	return;
        }
        arrow.addClass('hidden');
        button.append('<i class="icon16 loading"></i>');
        var direction = $(this).hasClass('previous') ? 'previous' : 'next';
        var form = $('.get-more-form:first');
        form.find('[name="direction"]').val(direction);
        $.post('?action=getmore', form.serialize(), function(response) {
            button.find('.loading').remove();
            if (response.status == 'fail') {
            	arrow.removeClass('hidden');
                $('<p class="error">' + response.errors.join('<br>') + '</p>').waDialog({
                    'buttons': '<input type="submit" value="' + $.loc['OK'] + '" class="button blue">',
                    'width': '500px',
                    'height': '100px',
                    'esc' : false,
                    onSubmit: function (d) {
                       d.find('.loading').show();
                       location.href = response.data.redirect_url;
                       return false;
                    },
        			onClose: function() {
                    	$(this).remove();
                    }
                });
            } else {
            	if (response.data.contents) {
            		var yes_icon = $('<i class="icon16 yes-bw"></i>');
            		yes_icon.appendTo(button);
            		yes_icon.animate({opacity: 0}, 1000, function() {
		                $(this).remove();
		                if (response.data.first_line == 0) {
		                	button.addClass('disabled').attr('title', '');
		                } else {
		                	arrow.removeClass('hidden');
		                }
		            });

                    var file_contents = $('.file-contents');
                    if (direction == 'previous') {
                        file_contents.prepend(response.data.contents);
                        if (response.data.first_line > 0) {
                            $('[name="first_line"]').val(response.data.first_line);
                        }
                    } else {
                        file_contents.append("\n" + response.data.contents);
                        $('[name="last_line"]').val(response.data.last_line);
                    }

                    prettyPrint();

                    if ($.browser.msie && parseFloat($.browser.version) < 9) {
                        setTimeout(function(){
                            adjustFileContentsHeight();
                        }, 0);
                    } else {
                        adjustFileContentsHeight();
                    }

                    if (direction == 'next') {
                    	scrollDown('.file-contents');
                    }
            	} else {
            		scrollDown('.file-contents');
            		var message = $('<span class="hint message">' + $.loc['nothing received'] + '</span>');
            		message.appendTo(button);
            		message.animate({opacity: 0}, 1000, function() {
		                $(this).remove();
		                arrow.removeClass('hidden');
		            });
            	}
            }
        }, 'json');
    });

    //show & save settings
    $('.settings').on('click', function() {
    	$('<h1>' + $.loc['Settings'] + '</h1>'
    	+ '<div class="content"><i class="icon16 loading"></i></div>'
    	+ '<p class="error hidden"></p>').waDialog({
    		buttons: '<input type="submit" value="' + $.loc['OK'] + '" class="button blue">'
    			+ '&nbsp;<a href="" class="cancel">' + $.loc['cancel'] + '</a>',
			onLoad: function() {
				var dialog = $(this);
				$.get('?action=settings', function(html) {
					dialog.find('.content').empty().html(html);
				});
			},
			onSubmit: function(d) {
				var dialog = $(d);
				dialog.find('.cancel').after('<i class="icon16 loading left-margin"></i>');
				dialog.find('.error').addClass('hidden').empty();
				$.post('?action=settingsSave', $(this).serialize(), function(response) {
					if (response.status == 'fail') {
						dialog.find('.loading').remove();
						dialog.find('.error').removeClass('hidden').html(response.errors.join('<br>'));
					} else {
						d.trigger('close');
					}
				}, 'json');
				return false;
			},
			onClose: function() {
            	$(this).remove();
            }
    	});
    });

    //execute on page load
    writeLineCountCookie();
    if (isFile()) {
        fixFirstPageLink();
        adjustPagination();
        prettyPrint();
        adjustFileContentsHeight();
    }
});