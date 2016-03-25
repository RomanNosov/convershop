var contactsProSignupForm = function (uid, submit_to_iframe, options) {
    "use strict";

    options.validate_messages = options.validate_messages || {};

    var $wrapper = $('#' + uid),
        $after = $wrapper.find('.wa-after-submit'),
        $form = $wrapper.find('form'),
        $inputsUser = $form.find(':input'),
        $inputsReq = $form.find(':input.wa-required-input'),
        $loading = $form.find('.loading'),
        $error = $form.find('.wa-error-msg'),
        $error_common = $error.last(),
        $captcha_refresh = $form.find('.wa-captcha-refresh'),
        $submit = $form.find(':submit'),
        $iframe = $wrapper.find('iframe');

    $wrapper.css({
        'min-height': $wrapper.height()
    });

    var validate = function($inputs) {
        $('.wa-error-msg').hide();
        $inputs.each(function() {
            var el = $(this);
            el.removeClass('wa-error');
            if (el.hasClass('wa-required-input') && !el.val()) {
                el.addClass('wa-error');
                el.nextAll('.wa-error-msg:first').show().text(options.validate_messages.required || '');
                return;
            }
            if (el.hasClass('wa-email-input')) {
                if (el.val().trim() && !/(.+)@(.+){2,}\.(.+){2,}/.test(el.val().trim())) {
                    el.addClass('wa-error');
                    el.nextAll('.wa-error-msg:first').show().text(options.validate_messages.email || '');
                    return;
                }
            }
            if (el.attr('name') === 'data[password_confirm]') {
                if ($inputs.filter('input[name="data[password]"]').val() !== el.val()) {
                    el.addClass('wa-error');
                    el.nextAll('.wa-error-msg:first').show().text(options.validate_messages.passwords_not_match || '');
                }
            }
        });
        return !!$inputs.filter('.wa-error').length;
    };

    var processResponse = function (response) {
        if (response.status === 'ok') {
            if (response.data.hasOwnProperty('redirect')) {
                window.top.location.replace(response.data.redirect);
            } else {
                $captcha_refresh.trigger('click');
                $form.hide();
                $after.height($wrapper.outerHeight());
                $after.css('display', 'table').find('div').html(response.data.html).show();
            }
        } else if (response.status === 'fail') {
            $captcha_refresh.trigger('click');
            $.each(response.errors, function (i, e) {
                var $inpt = $form.find('[name="data[' + i + ']"]');
                if ($inpt.length) {
                    $inpt.addClass('wa-error');
                    $inpt.closest('.wa-value').find('.wa-error-msg').show().text(e);
                } else {
                    var name_fields = ['firstname', 'lastname', 'middlename'];
                    var name_fields_and_email = ['firstname', 'lastname', 'middlename', 'email'];
                    if (i === 'name' || i === 'name,email') {
                         $.each(i === 'name' ? name_fields : name_fields_and_email, function(k, v) {
                             $form.find('[name="data[' + v + ']"]').addClass('wa-error');
                         });
                    }
                    $error_common.show().html($error_common.text() + "<br/>" + e);
                }
            });
        }
    };

    $inputsUser.on('click', function () {
        $(this).removeClass('wa-error');
        $(this).closest('.wa-value').find('.wa-error-msg').hide().text("");
    });

    $form.on('submit', function (e) {
        var error = false;
        $error.text('').hide();
        error = validate($form.find($inputsUser));
        if (error) {
            return false;
        }

        $loading.show();
        $submit.prop('disabled', true);

//        if (submit_to_iframe) {
//            $iframe.one('load', function () {
//                setTimeout(function () {
//                    try {
//                        var response = $iframe.contents().find("body").html();
//                        if (response) {
//                            response = eval('(' + response + ')');
//                            processResponse(response);
//                        }
//                    } catch (e) {
//                        // Security exception: attempt to access data from foreign domain.
//                        // Can't do anything about it except this notice. Hopefully everything is OK there.
//                        console && console.log && console.log('Notice: unable to read response from server.', e);
//                        $error_common.text('Notice: unable to read response from server.').show();
//                    }
//                    $loading.hide();
//                    $submit.prop('disabled', false);
//                }, 100);
//            });
//        } else
        var dataType = submit_to_iframe ? 'jsonp' : 'json';
        e.preventDefault();
        $.ajax({
            url: $form.attr('action'),
            type: 'post',
            crossDomain: submit_to_iframe,
            data: $form.serialize(),
            dataType: dataType
        })
            .done(function (response) {
                processResponse(response);
            })
            .fail(function () {
                $error_common.text('[`Server error`]').show();
            })
            .always(function () {
                $loading.hide();
                $submit.prop('disabled', false);
            });
        return false;
    });
};