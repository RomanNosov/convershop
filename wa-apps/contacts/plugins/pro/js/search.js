$.wa.search = {

    state: null,

    init: function (params, env) {
        var p = {};
        if (params[0]) {
            p.hash = params[0];
        }

        if (params[1] && params[1] !== "0") {
            this.search(params);
            return;
        }

        var hash = params[0];
        var encoded_hash = hash;
        try {
            encoded_hash = decodeURIComponent(encoded_hash);
        } catch (e) {
            // illform, so hash is not encoded
        }
        encoded_hash = encodeURIComponent(encoded_hash);
        p.hash = encoded_hash;

        $.wa.controller.setBlock();
        $('#c-core .c-core-content').load('?plugin=pro&module=search', p, function () {
            var form = $('#c-search-container').find('form');
            form.bind('contacts_search', function (e, hash) {
                if (hash) {
                    $.wa.setHash('#/contacts/search/' + hash + '/1/');
                } else {
                    $('#c-search-message').text($_('No search conditions specified')).show();
                }
            });
            $.wa.controller.setTitle($_('Search contacts'));
            form
                .find(':input:not([type="hidden"])').change(function () {
                    $('#c-search-message').hide();
                    return false;
                })
                .end().find('input:not(.datepicker)').keydown(function () {
                    $('#c-search-message').hide();
                });
            if (env === 'new_filter') {
                $('#c-search-container .c-new-filter-text').show();
            }
        });
    },

    search: function (params) {
        var hash = params[0];
        var encoded_hash = hash;
        try {
            encoded_hash = decodeURIComponent(encoded_hash);
        } catch (e) {
            // illform, so hash is not encoded
        }
        encoded_hash = encodeURIComponent(encoded_hash);

        if (hash) {
            var p = $.wa.controller.parseParams(params.slice(2));
            if (!params[5]) {
                p.view = 'list';
            }
            var custom_fields = $.wa.controller.parseCustomFields(params, 'search');
            if ($.wa.controller.needAddCustomFields(params, 'search')) {
                $.wa.controller.addCustomFields(custom_fields);
                return;
            }

            $('#c-contacts-count-text').hide();
            $.wa.controller.showLoading();
            $.wa.controller.loadGrid(
                $.extend({}, p, { query: 'search/' + encoded_hash + '/', custom_fields: custom_fields }),
                '/contacts/search/' + hash + '/1/',
                '?plugin=pro&module=contacts&action=list', {
                    beforeLoad: function () {
                        $.wa.controller.setBlock('contacts-list');
                        $('#c-searching').hide();
                        $('#contacts-container').removeClass('no-border');
                    },
                    afterLoad: function (data) {
                        if (data.count > 0) {
                            var full_hash = '/contacts/search/' + hash + '/1/' + $.wa.grid.getHash();
                            $('#c-searching').hide();
                            if (p.view !== 'map' && p.view !== 'thumbs') {
                                $.wa.controller.addCustomFieldsControl(data, full_hash, custom_fields);
                            }
                        }
                        var menu_items = [];
                        if (data.can_change_search_conditions) {
                            menu_items.push('<li><a href="#/contacts/search/' + encoded_hash + '/">' + $_('Change search conditions') + '</a></li>')
                        }
                        if (data.can_save_as_filter) {
                            menu_items.push('<li><a href="javascript:void(0);" id="c-save-as-filter">' + $_('Save as filter') + '</a></li>');
                        }
                        var block = $('.wa-page-heading')
                            .css('width', '100%')
                            .contents()
                            .wrapAll('<div class="wa-page-heading-text"></div>')
                            .end()
                            .prepend(
                                menu_items.length
                                    ? '<ul class="menu-v float-right wa-page-heading-links">' + menu_items.join(' ') + '</ul>'
                                    : ''
                            )
                            .closest('.block');
                        block.find('.wa-page-heading-text').append('<i class="icon16 loading" style="display:none;"></i>')
                        $('#c-save-as-filter').click(function () {
                            $('.wa-page-heading', block).hide();
                            $.wa.controller.appendViewSettingsBlock(block, data);
                            return false;
                        });
                        $('.wa-page-heading').after('<div class="wa-page-subheading" style="margin-top: 20px;">' + data.count_html + '</div>');
                    }
                });
        }
    },

    serialize: function (form, id, index) {
        var o = {};
        var data = form.serializeArray();
        // parse to temporary hierarchy object
        for (var i = 0, n = data.length; i < n; i += 1) {
            var value = (data[i].value || '').trim();
            try {
                value = JSON.parse(value);
            } catch (e) {
            }
            var name = data[i].name;
            if (!name || !value || ($.isPlainObject(value) && $.isEmptyObject(value))) {
                continue;
            }
            if (id && name.indexOf(id) !== 0) {
                continue;
            }
            if (index !== undefined && name.indexOf('[' + index + ']') === -1) {
                continue;
            }
            var val = '' + ($.isPlainObject(value) ? value['val'] : value); // must be string
            var op = $.isPlainObject(value) ? (value['op'] || '=') : '=';
            val = val.replace(/\//g, '\\/').replace(/&/, '\\&');
            var token = name.split('.');
            var p = o;
            for (var j = 0, l = token.length - 1; j < l; j += 1) {
                var t = (token[j] || '').trim();
                if (typeof p[t] === "undefined" || typeof p[t] !== 'object') {
                    p[t] = {};
                }
                p = p[t];
            }
            var t = token[token.length - 1];
            if ($.isArray(p[t])) {
                p[t].push({
                    val: val, op: op
                });
            } else if (!$.isPlainObject(p[t])) {
                p[t] = [
                    {
                        val: val, op: op
                    }
                ];
            }
        }

        // flatting object to 1d hash-map
        var flat = function (o, h, key) {
            if ($.isPlainObject(o)) {
                for (var k in o) {
                    if (o.hasOwnProperty(k)) {
                        flat(o[k], h, key ? (key + '.' + k) : k);
                    }
                }
            } else if (typeof o !== 'undefined') {
                h[key] = o;
            }
        };
        var h = {};
        flat(o, h, '');

        // result data array
        var r = [];
        for (var k in h) {
            if (h.hasOwnProperty(k)) {
                if ($.isArray(h[k]) && h[k].length > 1) {
                    for (var i = 0, n = h[k].length; i < n; i += 1) {
                        r.push(k + '[' + i + ']' + h[k][i].op + encodeURIComponent(h[k][i].val));
                    }
                } else {
                    r.push(k + h[k][0].op + encodeURIComponent(h[k][0].val));
                }
            }
        }
        return r.join('&');
    },

    indexBlocks: function (id) {
        var map = { };
        var items = $('#c-search-block .js-field[data-multiple="1"]');
        if (id) {
            items = items.filter('[data-id="' + id + '"]');
        }
        items.each(function () {
            var item = $(this);
            var id = item.data('id');
            var index = map[id] !== undefined ? ++map[id] : map[id] = 0;
            item.attr('data-index', index);
            item.data('index', index);
            item.find(':input').each(function () {
                var input = $(this);
                var name = input.attr('name').replace(/\[\d+\]/, '').replace(id, id + '[' + index + ']');
                input.attr('name', name);
            });
        });
    }

};