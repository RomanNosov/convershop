(function ($) {
    $.fn.periodDialog = function (key, value) {

        if (!this.length) {
            return this;
        }

        if (key === 'getStartDate') {
            return getStartDate();
        }
        if (key === 'getEndDate') {
            return getEndDate();
        }
        if (key === 'formatDate') {
            return formatDate.apply(this, Array.prototype.slice.call(arguments, 1));
        }

        var options = {};
        if (typeof key === 'object') {
            this.data('periodDialogOptions', $.extend({
                start_datetime: null,
                end_datetime: null
            }, key));
            options = this.data('periodDialogOptions');
        }

        function init() {
            var html = ''
                + '<table width="100%;">'
                + '<tr>'
                + '<td align="center">'
                + '<div class="datepicker start"></div>'
                + '</td>'
                + '<td align="center">&mdash;</td>'
                + '<td align="center">'
                + '<div class="datepicker end"></div>'
                + '</td>'
                + '</tr>'
                + '</table>';
            var d = this;
            d.html(html);
            var buttons = '<input type="submit" class="button green" value="' + $_('Apply') + '"> '
                + $_('or') + ' '
                + '<a class="cancel" href="javascript:void(0);">' + $_('cancel') + '</a>';
            d.waDialog({
                buttons: buttons,
                onLoad: function () {
                    d.find('.datepicker').datepicker();
                    if (options.start_datetime) {
                        d.find('.datepicker.start').datepicker('setDate', $.datepicker.parseDate('yy-mm-dd', options.start_datetime));
                    }
                    if (options.end_datetime) {
                        d.find('.datepicker.end').datepicker('setDate', $.datepicker.parseDate('yy-mm-dd', options.end_datetime));
                    }
                },
                onSubmit: function () {
                    var form = $(this);
                    var start_datetime = form.find('.start').datepicker('getDate');
                    var end_datetime = form.find('.end').datepicker('getDate');
                    d.trigger('select', [start_datetime, end_datetime]);
                    d.trigger('close');
                    return false;
                }
            }).find('.cancel').click(function () {
                d.trigger('cancel');
            });
            return d;
        }

        function getStartDate(format) {
            return $.datepicker.formatDate(format, new Date(this.find('.datepicker.start').datepicker('getDate')));
        }

        function getEndDate(format) {
            return $.datepicker.formatDate(format, new Date(this.find('.datepicker.end').datepicker('getDate')));
        }

        function formatDate(format, datetime, settings) {
            return $.datepicker.formatDate(format, new Date(datetime), settings);
        }

        return init.call(this);

    };
})(jQuery);