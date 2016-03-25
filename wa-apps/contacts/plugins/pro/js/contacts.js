(function() {

    $.wa.controller = $.extend($.wa.controller || {}, {

        search_cache: false,

        data: {},

        contactAction: function (params) {

            var p = {};
            if (params[1]) {
                p = { tab: params[1] };
            }

            if (this.lastView && this.lastView.hash !== null) {
                p['last_hash'] = this.lastView.hash;
                p['sort'] = this.lastView.sort;
                p['offset'] = this.lastView.offset;
            }

            this.showLoading();
            this.load("#c-core", "?module=contacts&action=info&id=" + params[0], p, function () {
                this.setBlock('contacts-info');
            }, function () {

                $('#tc-contact').bind('after_switch_mode.contacts-pro', function (e, mode, contactEditor) {
                    $.wa.controller.initCompanyInput(mode, contactEditor);
                });

                $.wa.controller.initCompanyInput('view', $.wa.contactEditor);

                if ($('#t-activity').length) {
                    $('#t-user').after($('#t-activity'));
                }

                // USER ACCOUNT TAB
                (function () {
                    var field = $('#tc-user-status-field');
                    if (field.find('.c-user-status-online, .c-user-status-offline').length) {
                        $('<a class="small" href="javascript:void(0);">' + $_('activities') + '</a>').
                            appendTo(field.find('.value').append()).
                            click(function () {
                                $('#t-activities').click();
                            });
                    }
                })();

            });

        },

        initCompanyInput: function(mode, contactEditor) {

            if (contactEditor.contactType === 'person') {

                var el = $(contactEditor.el);
                var domElement = el.find('.field.company');
                domElement.find('.value').css({
                    width: '100%'
                });

                function loadContactTopInfo(contact_id) {
                    if (!contact_id || contact_id === "0") {
                        return;
                    }
                    var block = domElement.find('.value');
                    var prev = block.find('.top');
                    if (prev.length && prev.data('id') == contact_id) {
                        return;
                    }
                    domElement.find('.loading').show();
                    var url = '?plugin=pro&module=contacts&action=infoTop&id=' + contact_id;
                    if (contactEditor.wa_backend_url) {
                        url = contactEditor.wa_backend_url + 'contacts/' + url;
                    }
                    $.get(url, function (r) {
                        domElement.find('.loading').hide();
                        if (r && r.status === "ok") {
                            prev.remove();
                            block.append('<div class="top" id="c-company-top-info" data-id="' + contact_id + '"></div>');
                            if (!$.isEmptyObject(r.data)) {
                                var html = '<ul class="menu-v compact">';
                                for (var i = 0; i < r.data.length; i += 1) {
                                    var f = r.data[i];
                                    var icon = f.id != 'im' ? (f.icon ? '<i class="icon16 ' + f.id + '"></i>' : '') : '';
                                    html += '<li>' + icon + f.value + '</li>';
                                }
                                html += '</ul>';
                                block.find('.top').append(html);
                            }
                            contactEditor.fieldEditors.company_contact_id.setValue(contact_id);
                        }
                    }, 'json');
                }

                var company_contact_id = contactEditor.fieldEditors.company_contact_id.getValue();
                if (company_contact_id && company_contact_id !== '0') {
                    loadContactTopInfo(company_contact_id);
                    domElement.find('.val').contents().wrapAll('<a href="#/contact/' + company_contact_id + '/" class="no-underline bold"></a>');
                }

                if (mode == 'edit') {

                    el.find('.subname:not(.title)').each(function () {
                        var w = $(this).find('input:first').width();
                        $(this).find('input:first').width(w * 0.75);
                    });

                    var opened = false;

                    // Autocomplete
                    domElement.find('.val').autocomplete({
                        source: '?plugin=pro&module=contacts&action=companies',
                        select: function (e, ui) {
                            if (ui.item.id) {
                                loadContactTopInfo(ui.item.id, $(this));
                                $(this).data('contact_id', ui.item.id);
                                domElement.find('.val').val(ui.item.name);

                                if (e.keyCode == 13) {
                                    setTimeout(function() {
                                        opened = false;
                                    }, 200);
                                } else {
                                    opened = false;
                                }

                            }
                        },
                        close: function (e, ui) {
                            $('.jobtitle-company-wrapper').removeClass('gray');
                            if (e.keyCode == 13) {
                                setTimeout(function() {
                                    opened = false;
                                }, 200);
                            } else {
                                opened = false;
                            }
                        },
                        open: function () {
                            $('.jobtitle-company-wrapper').addClass('gray');
                            $(this).data('contact_id', 0);
                            opened = true;
                        },
                        clear: function () {
                            contactEditor.fieldEditors.company_contact_id.setValue(0);
                            domElement.find('.val').val('');
                            $('#c-company-top-info').remove();
                        },
                        keyup: function() {
                            contactEditor.fieldEditors.company_contact_id.setValue(0);
                        }
                    }).keyup(function (e) {
                            if (e.keyCode === 13 && !opened) {
                                return false;
                            }
                    }).keydown(function(e) {
                        if (e.keyCode == 13 && !opened) {
                            $("#contact-info-block input[type=submit]:last").click();
                        }
                    });
                }
            }
        },

        contactsImportAction: function (params) {
            this.setBlock();
            if (params[0] == 'results') {
                return this.contactsImportResults(params.slice(1));
            }
            var container = $("#c-core .c-core-content");
            if (params[0] == 'upload') {
                this.load(container, '?plugin=pro&module=import&action=upload' + (params[1] ? '&' + params[1] : ''));
            } else {
                this.load(container, '?plugin=pro&module=import');
            }
        },

        contactsImportResults: function (params) {
            this.setBlock('contacts-list');
            var p = this.parseParams(params.slice(1), 'import/results/' + params[0]);
            p.query = '/import/results/' + params[0];
            this.loadGrid(p, '/contacts/import/results/' + params[0] + '/', '?plugin=pro&module=contacts&action=list');
        },

        fconstructorAction: function (params) {
            this.load($("#c-core"), "?plugin=pro&module=constructor&action=main&type=personal");
        },

        usersAllAction: function (params) {
            this.showLoading();
            var p = this.parseParams(params, 'users/all');
            p.query = '/users/all/';
            p.fields = ['name', 'email', 'company', '_access'];
            this.loadGrid(p, '/users/all/', '?plugin=pro&module=contacts&action=list', {
                beforeLoad: function() {
                    this.setBlock('contacts-users', $_('All users'), ['group-actions']);
                },
                afterLoad: function (data) {
                    $('#sb-all-users-li span.count').html(data.count);
                    $('#c-core .sidebar ul.stack li:first').addClass('selected');
                    $('.c-service-column').show().width('20px');
                }
            });
        },

        contactsGroupAction: function (params) {
            if (!params || !params[0]) {
                return;
            }
            var p = this.parseParams(params.slice(1), 'contacts/group/'+params[0]);
            p.fields = ['name', 'email', 'company', '_access'];
            p.query = 'group/' + params[0];
            $('.wa-page-heading').css({
                width: ''
            });
            this.loadGrid(p, '/contacts/group/' + params[0] + '/', '?plugin=pro&module=contacts&action=list', {
                beforeLoad: function(data) {
                    this.current_group_id = params[0];
                    if (data.count > 0) {
                        this.setBlock('contacts-users', null, ['group-settings', 'group-actions']);
                    } else {
                        this.setBlock('contacts-users', null, 'group-settings');

                        $('#contacts-container').html(
                            '<div class="block double-padded" style="margin-top: 35px;">' +
                                '<p>' + $_('No users in this group.') + '</p> <p>' +
                                    $_('To add users to group, go to <a href="#/users/all/">All users</a>, select them, and click <strong>Actions with selected / Add to group</strong>.') +
                                '</p>' +
                            '</div>'
                        );
                        return false;
                    }
                },
                afterLoad: function (data) {
                    $('#list-group li[rel="group'+params[0]+'"]').children('span.count').html(data.count);
                }
            });
        },

        usersAddAction: function (params) {
            this.checkAdminRights(function() {
                this.setBlock('contacts-users', $_('New user'), false);
                $('.wa-page-heading').find('.loading').hide();
                $('.contacts-data').html(
                    '<div class="block double-padded">' +
                        '<p>' +
                        $_('You can grant access to your account backend to any existing contact. ' +
                            'To do so, <a href="#/contacts/search/">find a contact</a> and then customize access rights on Account tab.') +
                        '</p>' +
                        '<p>' +
                        $_('Or <a href="#/contacts/add/">create a new contact</a> and customize access rights on Account tab.') +
                        '</p>' +
                        '</div>');
            });
        },

        contactsAddNoteAction: function (params) {
            this.load($('#c-core'), '?plugin=pro&module=notes&action=add');
        },

        contactsAddEventAction: function (params) {
            $('#c-events-edit-event-dialog-container').remove();
            this.load($('#c-core'), '?plugin=pro&module=events&action=edit', {}, null, function () {
                if ($.wa.controller.hashes.length > 1) {
                    var h = $.wa.controller.hashes[1].split('/');
                    if (h[0] === 'events') {
                        $('.c-cancel-panel').show().find('.cancel').click(function () {
                            window.history.back();
                            return false;
                        });
                    }
                }
                $('#save-event-form').bind('after_save', function () {
                    $.wa.controller.updateAddNewBlock('event');
                    $.wa.setHash('#/events/all/0/0/30/event/');
                });
            });
        },

        contactsAddAction: function (params) {
            this.showLoading();
            this.setBlock('contacts-info');

            if (params[0] === 'note') {
                return this.contactsAddNoteAction(params.slice(1));
            }

            if (params[0] === 'event') {
                return this.contactsAddEventAction(params.slice(1));
            }

            var is_company = params && params[0] === 'company' ? 1 : 0;
            this.load($("#c-core .c-core-content"), "?module=contacts&action=add&company=" + is_company, {}, function () {
                $.wa.controller.clearLastView();
                if (params[0]) {
                    $.wa.controller.updateAddNewBlock('company');
                } else {
                    $.wa.controller.updateAddNewBlock('person');
                }

            }, function () {
                if ($.wa.contactEditor.contactType === 'person') {
                    $.wa.controller.setTitle($_('New person'), true);
                } else {
                    $.wa.controller.setTitle($_('New company'), true);
                }

                $.wa.controller.initCompanyInput('edit', $.wa.contactEditor);

                // trigger scroll for refresh sticky buttons, @see jquery.sticky.js in contacts/js
                $(window).scroll();

            });
        },

        initNoteInlineEditors: function (options) {
            options = options || {};
            var container = $('#c-notes');
            container.on('click', '.edit', function () {
                var link = $(this);
                var text_block = link.closest('.c-note').find('.c-note-text');
                text_block.inlineEditable({
                    inputType: 'textarea',
                    size: {
                        width: 400,
                        height: 100
                    },
                    editLink: link,
                    editOnItself: false,
                    makeReadableBy: [],
                    beforeMakeEditable: function (input) {
                        text_block.closest('.c-note').find('.error').removeClass('error').end().find('.errormsg').remove();
                        if (!input.next('.buttons').length) {
                            input.after('<div class="buttons" style="margin-top: 6px;">'
                                + '<input type="button" class="button green" value="' + $_('Save') + '">'
                                + ' ' + $_('or') + ' '
                                + '<a href="javascript:void(0);" class="cancel" style="display: inline-block">' + $_('cancel') + '</a>'
                                + '</div>');

                            input.next()
                                .find('.cancel')
                                .click(function () {
                                    text_block.closest('.c-note').find('.error').removeClass('error').end().find('.errormsg').remove();
                                    input.next('.buttons').hide();
                                    text_block.trigger('readable', [false, true]);
                                })
                                .end()
                                .find('.button')
                                .click(function () {
                                    text_block.data('save', 1).trigger('readable');
                                });
                        }
                        input.next().show();
                        if (typeof options.beforeMakeEditable === 'function') {
                            options.beforeMakeEditable.apply(this, arguments);
                        }
                    },
                    beforeBackReadable: function (input, opt) {
                        text_block.closest('.c-note').find('.error').removeClass('error').end().find('.errormsg').remove();
                        if (typeof options.beforeBackReadable === 'function') {
                            if (options.beforeBackReadable.apply(this, arguments) === false) {
                                return false;
                            }
                        }
                        if (text_block.data('save') && opt.changed) {
                            if (!input.val().trim()) {
                                input.addClass('error').after('<em class="errormsg">' + $_('Required field') + '</em>');
                                return false;
                            }
                        }
                        input.next('.buttons').hide();
                    },
                    afterBackReadable: function (input, opt) {
                        var app_url = container.data('appUrl') || '';
                        text_block.html(input.val().trim().replace(/\n/g, "<br>\n"));
                        if (text_block.data('save') && opt.changed) {
                            text_block.data('save', 0);
                            var note_block = text_block.closest('.c-note');
                            $.post(app_url + '?plugin=pro&module=notes&action=save', {
                                id: note_block.data('id'),
                                text: input.val()
                            }, function (r) {
                                if (r && r.status === 'ok') {
                                    note_block.find('.author-info').html(
                                        r.data.creator.name + ', ' +
                                            r.data.create_datetime_str
                                    );
                                    if (container.hasClass('resort-on-save')) {
                                        note_block.closest('li').prependTo(container);
                                    }
                                    if (typeof options.onSave === 'function') {
                                        options.onSave.call(text_block, r);
                                    }
                                }
                            }, 'json');
                        } else if (opt.changed) {
                            text_block.text(opt.old_text.trim());
                        }
                        if (typeof options.afterBackReadable === 'function') {
                            options.afterBackReadable.apply(this, arguments);
                        }
                    }
                }).trigger('editable');
            });
        },

        notesAllAction: function (params) {
            var hash = 'notes/all/';
            var parseParams = function (params) {
                var p = {
                    offset: 0,
                    order: 0,   // 0 - desc, 1 - asc
                    count: 30,
                    query: ''
                };
                if (params[0]) {
                    p.offset = parseInt(params[0], 10) || 0;
                }
                if (params[1]) {
                    p.order = parseInt(params[1], 10) || 0;
                }
                if (params[2]) {
                    p.count = parseInt(params[2], 10) || 30;
                }
                if (params[3]) {
                    p.query = params[3];
                }
                return p;
            };
            var getParams = function () {
                var pos = location.hash.indexOf(hash);
                var tail_hash = '';
                if (pos !== -1) {
                    tail_hash = location.hash.slice(pos + hash.length);
                    tail_hash = tail_hash.split('/');
                }
                return tail_hash;
            };
            var makeHash = function (p) {
                return '#/' + hash + p.offset + '/' + p.order + '/' + p.count + '/' + (p.query ? p.query + '/' : '');
            };
            var options = {
                inline_contact_info: false
            };
            var p = parseParams(params);
            this.load($('#c-core'), '?plugin=pro&module=notes', p, null,
                function () {
                    $(window).scrollTo(0);
                    var searchHandler = function () {
                        var q = [];
                        $('.notes-search').each(function () {
                            var item = $(this);
                            var val = item.val().trim();
                            if (val) {
                                // escape
                                val = val.replace(/&/, '\\&');
                                q.push(item.attr('name') + '=' + encodeURIComponent(val));
                            }
                        });
                        var params = getParams();
                        var p = parseParams(params);
                        p.query = '';
                        if (q.length) {
                            p.query = q.join('&');
                        }
                        location.hash = makeHash(p);
                    };
                    $('.notes-search').bind('search',function () {
                        searchHandler();
                        return false;
                    }).keydown(function (e) {
                        if (e.keyCode == 13) {
                            searchHandler();
                            return false;
                        }
                    }).change(function () {
                        if (!$(this).val()) {
                            searchHandler();
                            return false;
                        }
                    }).bind('input', function () {
                        if (!$(this).val()) {
                            searchHandler();
                            return false;
                        }
                    });
                    $('#c-notes-paging').bind('choose_page', function (e, offset) {
                        var params = getParams();
                        var p = parseParams(params);
                        p.offset = offset;
                        $.wa.setHash(makeHash(p));
                    });
                    $('#c-notes-paging .items-per-page').bind('change', function () {
                        var params = getParams();
                        var p = parseParams(params);
                        p.count = parseInt($(this).val(), 10);
                        p.offset = 0;
                        $.wa.setHash(makeHash(p));
                    });

                    $.wa.controller.initNoteInlineEditors({
                        beforeMakeEditable: function () {
                            var text_block = $(this).show();
                            text_block.parent().find('.trancated').hide();
                        },
                        onSave: function (r) {
                            $(this).closest('.item-row').find('.datetime').html(r.data.create_datetime_str);
                        }
                    });

                    $('#c-notes-container')
                        .find('.sort')
                        .bind('click', function () {
                            var params = getParams();
                            var p = parseParams(params);
                            p.order = +!p.order;    // 0 -> 1, 1 -> 0
                            location.hash = makeHash(p);
                        })
                        .end()
                        .find('.more')
                        .bind('click', function () {
                            $(this).closest('td')
                                .find('.trancated')
                                .hide()
                                .end()
                                .find('.full')
                                .show();
                        })
                        .end()
                        .find('.delete')
                        .bind('click', function () {
                            var tr = $(this).closest('tr');
                            var id = tr.data('id');
                            if (id && confirm($_('Are you sure?'))) {
                                $(this).after(' <i class="icon16 loading"></i>');
                                $.post('?plugin=pro&module=notes&action=delete', { id: id, counter: 1 }, function (r) {
                                    if (r.status === 'ok') {
                                        tr.remove();
                                        if (!tr.closest('tbody').find('.item-row:first').length) {
                                            $('.c-hide-on-empty-notes').hide();
                                        }
                                        if (r.data.counters) {
                                            var cnt = parseInt(r.data.counters.all, 10) || 0;
                                            $('#c-all-notes-li .count').text(cnt);
                                            $('#notes-paging .total').text(cnt);
                                        }
                                        $.wa.controller.redispatch();
                                    }
                                }, 'json');
                            }
                        })
                        .end();
                if (options.inline_contact_info) {
                    $('#c-notes-container')
                        .find('.load-contact-info')
                        .bind('click', function () {
                            var item = $(this);
                            var parent_tr = item.closest('tr');
                            if (parent_tr.hasClass('wrapped') && parent_tr.next().hasClass('contact-info')) {
                                return false;
                            }
                            if (parent_tr.data('loading')) {
                                return false;
                            }
                            parent_tr.data('loading', 1);
                            $('#c-notes .contact-info').remove();
                            $('#c-notes .wrapped').find('.td-wrapper')
                                .each(function () {
                                    $(this).children().first().unwrap();
                                })
                                .end()
                                .removeClass('wrapped');
                            var contact_id = item.data('id');
                            var loading = $('<i class="icon16 loading" style="margin-left: 2px;"></i>')
                                .insertAfter(item);
                            $.get('?module=contacts&action=info', {
                                    id: contact_id,
                                    readonly: 1,
                                    no_backlink: 1,
                                    no_switchtab: 1,
                                    no_update_title: 1
                                },
                                function (html) {
                                    loading.remove();

                                    parent_tr
                                        .addClass('wrapped')
                                        .find('td').each(function () {
                                            $(this).children().wrapAll('<div class="td-wrapper" style="height:16px; overflow: hidden;"></div>');
                                        });
                                    var tr = $('<tr class="contact-info"><td colspan="3"></td></tr>')
                                        .insertAfter(parent_tr);

                                    tr.find('td:first')
                                        .html(html)
                                        .find('.details-header')
                                        .prepend('<a class="killer float-right" href="javascript:void(0);"><i class="icon16 close"></i></a>')
                                        .find('.killer').click(function () {
                                            tr.remove();
                                            parent_tr.find('.td-wrapper')
                                                .each(function () {
                                                    $(this).children().first().unwrap();
                                                })
                                                .end()
                                                .removeClass('wrapped');
                                        });

                                    tr.find('.details .name').wrapAll('<a class="no-underline" href="#/contact/' + contact_id + '/"></a>');

                                    tr.find('.contacts-background')
                                        .css({
                                            margin: 0
                                        })
                                        .end()
                                        .find('.tab-contents-wrapper')
                                        .css({
                                            'maxHeight': 200,
                                            'overflowY': 'auto'
                                        })
                                        .end().
                                        find('#c-info-tabs li')
                                        .bind('click.switch_tab', function () {
                                            $.wa.contactEditor.switchToTab($(this));
                                        });
                                    parent_tr.data('loading', 0);
                                }
                            );
                            return false;
                        });
                    }
                }
            );
        },

        eventEditDialog: function (event_id) {
            $('#c-events-edit-event-dialog-container').remove();
            var dialog_content = $('<div id="c-events-edit-event-dialog-container"></div>').appendTo('body');
            dialog_content.load('?plugin=pro&module=events&action=editDialog&id=' + event_id, function () {
                var d = dialog_content.find('.dialog');
                d.waDialog({
                    onLoad: function () {
                        d.find('form').bind('after_delete', function (r) {
                            $.wa.controller.redispatch();
                            d.trigger('close');
                            return false;
                        });
                    },
                    onSubmit: function () {
                        var form = $(this);
                        form.find('.dialog-buttons .loading.save').show();
                        form.bind('after_save', function () {
                            form.find('.loading.save').hide();
                            d.trigger('close');
                            $.wa.controller.redispatch();
                        });
                        form.trigger('save');
                        return false;
                    },
                    onCancel: function () {
                        $('#c-events-edit-event-dialog-container').remove();
                    }
                });
            });
        },

        eventsAllAction: function (params) {
            var hash = 'events/all/';
            var parseParams = function (params) {
                var p = {
                    offset: 0,
                    order: 0,   // 0 - desc, 1 - asc
                    count: 30,
                    category: 'log',
                    query: '',
                    extra: null
                };
                if (params[0]) {
                    p.offset = parseInt(params[0], 10) || 0;
                }
                if (params[1]) {
                    p.order = parseInt(params[1], 10) || 0;
                }
                if (params[2]) {
                    p.count = parseInt(params[2], 10) || 30;
                }
                if (params[3]) {
                    p.category = params[3];
                }
                if (params[4]) {
                    p.query = params[4];
                }
                if (params[5]) {
                    p.extra = params[5];
                }
                return p;
            };
            var getParams = function () {
                var pos = location.hash.indexOf(hash);
                var tail_hash = '';
                if (pos !== -1) {
                    tail_hash = location.hash.slice(pos + hash.length);
                    tail_hash = tail_hash.split('/');
                }
                return tail_hash;
            };
            var makeHash = function (p) {
                return '#/' + hash + p.offset + '/' + p.order + '/' + p.count + '/' + p.category + '/' + (p.query ? p.query + '/' : '');
            };
            var parseQuery = function (query) {
                var q = {};
                var query_ar = query.split('&');
                for (var i = 0; i < query_ar.length; i += 1) {
                    var p = query_ar[i].split('=');
                    if (p[0] && p[1]) {
                        q[p[0]] = p[1];
                    }
                }
                return q;
            };
            var makeQuery = function (query) {
                var q = [];
                for (var k in query) {
                    if (query.hasOwnProperty(k)) {
                        q.push(k + '=' + query[k]);
                    }
                }
                return q.join('&');
            };
            var setHash = function (hash) {
                hash = hash.replace(/^[^#]*#\/*/, '');
                var changed = $.isEmptyObject($.wa.controller.hashes) || $.wa.controller.hashes[0] !== hash;
                if (changed) {
                    $('#c-events-content').find('.title .loading').show();
                }
                $.wa.setHash(hash);
                return changed;
            };

            var setNewEventsListener = function () {
                var timeout = 60000;
                $('#c-events-content').queue(function poll(next) {
                    var content = $(this);
                    var datetime = $('#c-log-records tbody tr:first').data('datetime');
                    if (datetime) {
                        $.post('?plugin=pro&module=events&action=new', {
                            datetime: datetime
                        }, function (html) {
                            var records = $('<table></table>').html(html);
                            records.find('tr').prependTo($('#c-log-records tbody')).each(function () {
                                var tr = $(this);
                                var color = $.Color(tr.find('td:first').css('background-color')).toHexString();
                                tr.addClass('c-new-record');
                                tr.data('color', color);
                                tr.find('td').css({
                                    backgroundColor: '#FFFF00'
                                });
                            });
                        }, 'html');
                        content.delay(timeout).queue(poll);
                        next();
                    }
                });
                // color fadeout when mousemove (means user is active)
                $(window).unbind('mousemove.events').bind('mousemove.events', function () {
                    if (!$('#c-log-records').length) {
                        $(this).unbind('mousemove.events');
                    } else {
                        $('#c-log-records tbody').find('.c-new-record').each(function () {
                            var tr = $(this);
                            tr.find('td').animate({
                                backgroundColor: tr.data('color')
                            }, 5000, function () {
                                $(this).parent().removeClass('c-new-record');
                            });
                        });
                    }
                });
            };

            var setLoadingCover = function () {
                var tbody = $('#c-log-records').find('tbody');
                var graphics_block = $('#c-log-records').find('.graphics');
                var top = graphics_block.length ? graphics_block.offset().top : tbody.offset().top;
                $('#c-log-records-cover').css({
                    position: 'absolute',
                    left: tbody.offset().left,
                    top: top,
                    height: tbody.height() + (graphics_block.length ? graphics_block.height() : 0),
                    width: tbody.width(),
                    zIndex: 99
                });
                var loading = $('#c-log-records-cover-loading').show();
                loading.css({
                    position: 'absolute',
                    left: tbody.offset().left + ((tbody.width() - loading.width()) / 2),
                    top: tbody.offset().top,
                    zIndex: 100
                });
            };

            var p = parseParams(params);
            this.load($('#c-core'), '?plugin=pro&module=events', p, null,
                function () {
                    $(window).scrollTo(0);
                    var container = $('#c-events-content');
                    if (p.category === 'log' && !p.query && p.offset == '0') {
                        setNewEventsListener();
                    }
                    if (p.category === 'event' && p.extra) {
                        var extra = parseQuery(p.extra);
                        if (extra.event_id) {
                            $.wa.controller.eventEditDialog(extra.event_id);
                        }
                    }
                    $('#c-records-paging').bind('choose_page', function (e, offset) {
                        var params = getParams();
                        var p = parseParams(params);
                        p.offset = offset;
                        setHash(makeHash(p));
                    });
                    $('#c-records-paging .items-per-page').bind('change', function () {
                        var params = getParams();
                        var p = parseParams(params);
                        p.offset = 0;
                        p.count = parseInt($(this).val(), 10);
                        setHash(makeHash(p));
                    });
                    var onSelectPeriodDialog = function (d, start_datetime, end_datetime) {
                        var start = d.periodDialog('formatDate', 'yy-mm-dd', start_datetime);
                        var end = d.periodDialog('formatDate', 'yy-mm-dd', end_datetime);
                        var p = parseParams(getParams());
                        var q = parseQuery(p.query);
                        q['period'] = start + ' - ' + end;
                        p.query = makeQuery(q);
                        var hash = makeHash(p);
                        setHash(hash);
                    };
                    var onCancelPeriodDialog = function (d) {
                        var p = parseParams(getParams());
                        var q = parseQuery(p.query);
                        if (q.period) {
                            $('#c-events-filter-period').find('option[value="' + q.period + '"]').attr('selected', true);
                        }
                    };
                    $('#c-events-filter-period').change(function () {
                        var val = $(this).val();
                        if (val !== 'select_period') {
                            setLoadingCover();
                            var p = parseParams(getParams());
                            var q = parseQuery(p.query);
                            q['period'] = $(this).val();
                            p.query = makeQuery(q);
                            var hash = makeHash(p);
                            setHash(hash);
                        } else {
                            $('#c-events-filter-select-period').remove();
                            var d = $('<div id="c-events-filter-select-period"></div>').appendTo('body');
                            d.periodDialog()
                                .bind('select', function (event, start_datetime, end_datetime) {
                                    onSelectPeriodDialog(d, start_datetime, end_datetime);
                                })
                                .bind('cancel', function () {
                                    onCancelPeriodDialog(d);
                                });
                        }
                    });
                    $('#c-events-filter-change-custom-period').click(function () {
                        $('#c-events-filter-select-period').remove();
                        var d = $('<div id="c-events-filter-select-period"></div>').appendTo('body');
                        d.periodDialog({
                            start_datetime: $(this).data('start'),
                            end_datetime: $(this).data('end')
                        })
                            .bind('select',
                            function (event, start_datetime, end_datetime) {
                                onSelectPeriodDialog(d, start_datetime, end_datetime);
                            });
                    });
                    var choose_month_selects = $('#c-events-calendar .c-choose-month').find('.month:not(.year)');
                    choose_month_selects.click(function () {
                        var p = parseParams(getParams());
                        var q = parseQuery(p.query);
                        q['month'] = $(this).data('month');
                        p.query = makeQuery(q);
                        var hash = makeHash(p);
                        setHash(hash);
                    });
                    var choose_year_selects = $('#c-events-calendar .c-choose-month').find('.year a');
                    choose_year_selects.bind('click', function() {
                        var p = parseParams(getParams());
                        var q = parseQuery(p.query);
                        q['year'] = $(this).data('year');
                        p.query = makeQuery(q);
                        var hash = makeHash(p);
                        setHash(hash);
                    });
                    $('#c-events-calendar .c-back-year-month').click(function() {
                        var p = parseParams(getParams());
                        var q = parseQuery(p.query);
                        delete q['year'];
                        q['month'] = $(this).data('month');
                        p.query = makeQuery(q);
                        var hash = makeHash(p);
                        setHash(hash);
                    });

                    $('#c-events-choose-category a').click(function () {
                        var params = getParams();
                        var p = parseParams(params);
                        p.category = $(this).data('value');
                        p.query = '';
                        setHash(makeHash(p));
                        return false;
                    });
                    $('.block-list', container).bind('item_selected', function (e, id, hash) {
                        var hash_ar = hash.split('=');
                        var type = hash_ar[0];
                        var val = hash_ar[1];
                        if (('' + val)[0] === '%') {
                            val = '';
                        }
                        var params = getParams();
                        var p = parseParams(params);
                        var q = parseQuery(p.query);
                        if (val && q[type] === val) {
                            return;
                        }
                        setLoadingCover();
                        if (val) {
                            q[type] = val;
                        } else {
                            delete q[type];
                        }
                        p.offset = 0;
                        p.query = makeQuery(q);
                        setHash(makeHash(p));
                    });
                    $('.block-list', container).bind('show_all', function (e, hash) {
                        $(this).trigger('item_selected', [$(this).attr('id'), hash]);
                    });
                    $('.filters', container).bind('close', function () {
                        var p = parseParams(getParams());
                        p.query = "";
                        var hash = makeHash(p);
                        if (!setHash(hash)) {
                            $(this).hide();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            );
        },

        setData: function (data) {
            this.data = data;
        },

        // google closure compile requires
        'export': function () {
            var selected = $.wa.grid.getSelected();
            if (selected.length <= 0) {
                return;
            }

            var processId = null;
            var timer = null;

            var showDialog = function (d) {
                d.waDialog({
                    onLoad: function () {
                        $('#c-export-loading').hide();
                        $('.c-hide-on-export').show();
                    },
                    disableButtonsOnSubmit: true,
                    onSubmit: function () {
                        var form = $(this);
                        $('#c-export-loading').show();
                        $('.c-hide-on-export').hide();
                        if (!form.find('input.field_id').length) {
                            return false;
                        }
                        form.find('.loading').show();
                        var p = form.serializeArray();
                        var exportwho = form.find('input[name=exportwho]:checked').val();
                        if (exportwho === 'selected') {
                            var selected = $.wa.grid.getSelected(true);
                            if (selected.length <= 0) {
                                return false;
                            }
                            p.push({
                                name: 'hash',
                                value: 'id/' + selected.join(',')
                            });
                        } else {
                            var hash = location.hash.replace(/^[^#]*#\/*/, '');
                            p.push({
                                name: 'hash',
                                value: hash
                            });
                        }
                        var requests = 0;
                        var longActionResponse = function (response) {
                            requests--;

                            if (!processId) {
                                return;
                            }

                            if (response.ready) {
                                // Stop sending messengers
                                var pid = processId;
                                if (!pid) {
                                    return; // race condition is still possible, but not really dangerous
                                }
                                processId = null;

                                clearTimeout(timer);

                                $('<form id="c-export-form-ready-' + pid + '" action="?plugin=pro&module=export&action=export&processid=' + pid + '" method="post"><input type="hidden" name="file" value="1" /></form>')
                                    .appendTo($('body'));
                                $('#c-export-form-ready-' + pid).submit(function () {
                                    $.wa.dialogHide();
                                    $.wa.grid.selectItems($('#c-select-all-items').attr('checked', false));
                                }).submit();
                                return;
                            }
                        };

                        // Sends messenger and delays next messenger in 3 seconds
                        var sendMessenger = function () {
                            if (requests < 2) {
                                if (processId === null) {
                                    return;
                                }
                                $.get('?plugin=pro&module=export&action=export', {
                                        processid: processId,
                                        t: Math.random()
                                    },
                                    longActionResponse,
                                    'json'
                                );
                                requests++;
                            }
                            timer = setTimeout(sendMessenger, 3200);
                        };

                        $.post('?plugin=pro&module=export&action=export', p, function (data) {
                            if (!data.processId) {
                                alert('Error processing request.');
                            }
                            processId = data.processId;
                            sendMessenger();
                        }, 'json');

                        return false;
                    },
                    onCancel: function () {
                        clearTimeout(timer);
                        $.post('?plugin=pro&module=export&action=export', { processid: processId, ready: 1 });
                        d.trigger('close');
                    }
                });
            };

            var d = $('#c-export-dialog'), p;
            if (!d.length) {
                p = $('<div></div>').appendTo('body');
            } else {
                p = d.parent();
            }
            p.load('?plugin=pro&module=export', {
                selected_count: selected.length,
                all_count: $.wa.grid.count
            }, function () {
                showDialog($('#c-export-dialog'));
            });

        },

        // Helper to draw line charts
        graph: function (id, data, period) {
            var formatString = '';
            var xTickInterval = null;
            var ticks = [];
            if (period == 'month') {
                formatString = '%d %b';
                xTickInterval = '10 days';
            } else if (period == 'week') {
                formatString = '%d %b';
                xTickInterval = '1 day';
            } else if (period == 'today') {
                formatString = '%d %b %H:00';
                xTickInterval = '0.2 day';
            } else if (period == 'year') {
                formatString = '%d %Y';
                xTickInterval = '1 month';
            } else if (data.length) {
                var diff = data[data.length - 1][2] - data[0][2];
                if (diff < 24 * 60 * 60) {
                    formatString = '%d %b %H:00';
                    xTickInterval = '0.2 day';
                } else if (diff < 7 * 24 * 60 * 60) {
                    formatString = '%d %b';
                    xTickInterval = '1 day';
                } else {
                    formatString = '%d %b %Y';
                    var d = Math.ceil(data.length / 5);
                    for (var i = 0; i < data.length; i += 1) {
                        if (i % d === 0) {
                            ticks.push(data[i][0]);
                        }
                    }
                    ticks.push(data[data.length - 1][0]);
                }
            }
            var max_y = 0;
            for (var i = 0, n = data.length; i < n; i += 1) {
                if (max_y < (parseFloat(data[i][1]) || 0)) {
                    max_y = parseFloat(data[i][1]) || 0;
                }
            }
            var yTickInterval = 1;

            if (!max_y) {
                max_y = 1;
            } else if (max_y > 10) {
                var bound = 10;
                while (Math.floor(max_y / bound) > 0) {
                    bound *= 10;
                }
                bound /= 10;
                if (max_y % 10 != 0) {
                    max_y = Math.ceil(max_y / bound) * bound;
                } else {
                    max_y = Math.ceil(max_y / bound) * bound + bound;
                }
                yTickInterval = bound;
            }

            $('#' + id).css({
                minWidth: '500'
            });
            var options = {
                height: 200,
                seriesColors: ["#3b7dc0", "#129d0e", "#a38717", "#ac3562", "#1ba17a", "#87469f", "#6b6b6b", "#686190", "#b2b000", "#00b1ab", "#76b300"],
                gridPadding: {
                    right: 40,
                    left: 40
                },
                series: [
                    {
                        color: '#129d0e',
                        yaxis: 'y2axis',
                        shadow: false,
                        lineWidth: 3,
                        fill: true,
                        fillAlpha: 0.1,
                        fillAndStroke: true,
                        rendererOptions: {
                            highlightMouseOver: false
                        }
                    }
                ],
                grid: {
                    borderWidth: 0,
                    shadow: false,
                    background: '#ffffff',
                    gridLineColor: '#eeeeee'
                },
                axes: {
                    xaxis: {
                        renderer: $.jqplot.DateAxisRenderer,
                        showTickMarks: false,
                        tickOptions: {
                            formatString: formatString
                        },
                        tickInterval: xTickInterval,
                        ticks: ticks,
                        min: data[0][0],
                        max: data[data.length - 1][0]
                    },
                    y2axis: {
                        min: 0,
                        max: max_y,
                        showTickMarks: false,
                        tickOptions: {
                            formatString: '%d'
                        },
                        tickInterval: yTickInterval
                    }
                },
                highlighter: {
                    show: true,
                    sizeAdjust: 7.5
                },
                cursor: {
                    show: false
                }
            };
            var plot = $.jqplot(id, [data], options);

            $(window).unbind('resize.events-overflow.plot').bind('resize.events-overflow.plot', function () {
                var content = $('#' + id);
                if (!content.length) {
                    $(this).unbind('resize.events-overflow.plot');
                    return;
                }
                plot.replot();
            });

        },

        /**
         * var list_type = search||all||view
         */
        parseCustomFields: function (params, list_type) {
            var offset = { search: 7, view: 6, all: 5 }[list_type] || 0;
            var custom_fields = [];
            if (offset && params[offset]) {
                var prms = params[offset].split('&');
                for (var i = 0; i < prms.length; i += 1) {
                    var t = prms[i].trim();
                    if (t) {
                        custom_fields.push(t);
                    }
                }
            }
            return custom_fields;
        },

        narrowCustomFieldsControl: function () {
            var text = $('#show-custom-fields-menu .edit-column .text');
            $('.view-list').find('.c-service-th')
                .css({ width: '20' })
                .addClass('min-width');
            var custom_fields = $('#show-custom-fields');
            custom_fields.css('left', -custom_fields.width() + 20);
            text.hide();
        },

        expandCustomFieldsControl: function () {
            var show_fields_menu = $('#show-custom-fields-menu');
            $('.view-list').find('.c-service-th')
                .removeClass('min-width')
                .width($('#show-custom-fields').css('width'))
                .append(show_fields_menu);
            var text = $('#show-custom-fields-menu .edit-column .text').show();
            var custom_fields = $('#show-custom-fields');
            custom_fields.css('left', -1.6 * text.width());

        },

        customFieldsControlCheckItems: function (fields) {
            $('#show-custom-fields a').each(function () {
                var item = $(this);
                var f_id = item.data('id');
                if (fields.indexOf(f_id) !== -1) {
                    item.find('.icon10').css('opacity', 1);
                } else {
                    item.find('.icon10').css('opacity', 0);
                }
            });
        },

        addCustomFieldsControl: function (data)
        {
            if (!$.isEmptyObject(data.metrics)) {
                if ($('#show-custom-fields').length) {
                    $('#show-custom-fields').closest('ul.dropdown').remove();
                }

                var width = 230;
                $('.view-list').find('.c-service-th').show().width(width).append(
                    '<ul class="menu-h dropdown animated float-right" style="display:inline-block" id="show-custom-fields-menu">' +
                        '<li><a href="javascript:void(0)" class="edit-column"><span class="text small" data-width="">' + $_('Display a column') + '</span><i class="icon10 darr"></i></a>' +
                        '<ul class="menu-v with-icons" id="show-custom-fields" style="width: ' + 230 + 'px;">' +
                        '</li></ul>' +
                        '</ul>'
                );

                $('.view-list').find('.c-service-column').show();

                if ($('#contacts-container').find('.contacts-data .list-with-custom-fields').length) {
                    $.wa.controller.narrowCustomFieldsControl();
                } else {
                    $.wa.controller.expandCustomFieldsControl();
                }

                var html = "";
                var metrics = data.metrics;
                for (var k in metrics) {
                    if (metrics.hasOwnProperty(k)) {
                        html += '<li><a href="#" class="toggle-custom-field" data-id="' + k + '"><i class="icon10 yes" style="opacity: 0;"></i> ' + metrics[k].name + '</a></li>';
                    }
                }

                $('#show-custom-fields').html(html);
                $(document).off('click', '.toggle-custom-field').on('click', '.toggle-custom-field', function () {
                    var field_id = $(this).data('id');
                    var hash = $.wa.controller.getHash() || '#/contacts/all/';
                    hash = hash.replace(/^[^#]*#\/*/, '');
                    var hash_ar = hash.split('/');
                    var list_type = hash_ar[1] || 'all';
                    var params = hash_ar.slice(2);
                    var custom_fields = $.wa.controller.parseCustomFields(params, list_type);
                    if (custom_fields.indexOf(field_id) === -1) {
                        custom_fields.push(field_id);
                        var p = $.wa.controller.parseHash(hash, list_type);
                        p.custom_fields = custom_fields.join('&');
                        hash = $.wa.controller.makeHash(p, list_type);
                        $.wa.setHash('#/' + hash);
                    } else {
                        $.wa.controller.deleteCustomField(field_id);
                    }
                    return false;
                });

                var fields = [];
                $('.custom-field-th').each(function () {
                    fields.push($(this).data('field-id'));
                });
                this.customFieldsControlCheckItems(fields);
            }
        },

        addCustomFields: function (fields, view_id) {
            var contacts = $.wa.grid.data.contacts || [];
            var contact_ids = [];
            for (var i = 0, n = contacts.length; i < n; i += 1) {
                contact_ids.push(contacts[i].id);
            }
            if (!$.isEmptyObject(fields) && !$.isEmptyObject(contacts)) {
                if (!$.isArray(fields)) {
                    fields = [fields];
                }

                $.wa.grid.data.fields = $.wa.grid.data.fields || {};
                for (var k in $.wa.grid.data.fields) {
                    if ($.wa.grid.data.fields.hasOwnProperty(k)) {
                        if ($.wa.grid.data.fields[k].type === 'Custom') {
                            delete $.wa.grid.data.fields[k];
                        }
                    }
                }
                for (var i = 0; i < fields.length; i += 1) {
                    var f = $.extend({}, $.wa.grid.data.metrics[fields[i]], { type: 'Custom' });
                    $.wa.grid.data.fields[f.id] = f;
                }

                var show_fields_menu = $('#show-custom-fields-menu');
                var correctCustomFieldsMenu = function () {
                    $('.view-list').find('.c-service-th').append(show_fields_menu).end().find('.c-service-column').show();
                };
                var elem = $('#contacts-container .contacts-data');
                elem.html($.wa.grid.view($.wa.grid.settings.view, $.wa.grid.data));
                var loading = elem.find('.c-list-top-line .custom-field-td:last').append(' <i class="icon16 loading"></i>').find('.loading');
                correctCustomFieldsMenu();
                this.customFieldsControlCheckItems(fields);
                this.narrowCustomFieldsControl();

                var p = {
                    contacts: contact_ids,
                    custom_fields: fields
                };
                view_id = parseInt(view_id, 10);
                if (view_id) {
                    p.view_id = view_id;
                }

                var self = this;
                $.post('?plugin=pro&module=contacts&action=listFields', p, function (r) {
                    if (r.status === "ok") {
                        var data = r.data;
                        for (var i = 0, n = contacts.length; i < n; i += 1) {
                            $.wa.grid.data.contacts[i] = $.extend(contacts[i], data.contacts[contacts[i].id] || {});
                        }
                        $.wa.grid.data.fields = r.data.fields;
                        var elem = $('#contacts-container .contacts-data');
                        loading.remove();
                        elem.html($.wa.grid.view($.wa.grid.settings.view, $.wa.grid.data));
                        correctCustomFieldsMenu();
                        self.customFieldsControlCheckItems(fields);
                        self.narrowCustomFieldsControl();
                    }
                }, 'json');
            }
        },

        diffHashes: function (hash1, hash2, list_type) {
            var p1 = this.parseHash(hash1, list_type);
            var p2 = this.parseHash(hash2, list_type);
            var diff = [];
            for (var k in p1) {
                if (p1.hasOwnProperty(k)) {
                    if (p1[k] != p2[k]) {
                        diff.push(k);
                    }
                }
            }
            return diff;
        },

        parseHash: function (hash, list_type) {
            var params = (hash || '').split('/');
            params = params.slice(2);

            if (list_type === 'view') {
                return $.extend({}, {
                    id: params[0] || '0',
                    offset: params[1] || '0',
                    sort: params[2] || 'name',
                    order: params[3] || '1',
                    view: params[4] || 'table',
                    limit: params[5] || '30',
                    custom_fields: params[6] || ''
                });
            } else if (list_type === 'search') {
                return $.extend({}, {
                    hash: params[0] || '',
                    load_contacts: params[1] || '0',
                    offset: params[2] || '0',
                    sort: params[3] || 'name',
                    order: params[4] || '1',
                    view: params[5] || 'list',
                    limit: params[6] || '30',
                    custom_fields: params[7] || ''
                });
            } else {
                return $.extend({}, {
                    offset: params[0] || '0',
                    sort: params[1] || 'name',
                    order: params[2] || '1',
                    view: params[3] || 'table',
                    limit: params[4] || '30',
                    custom_fields: params[5] || ''
                });
            }
        },

        makeHash: function (params, list_type) {
            var prefix = 'contacts/' + list_type + '/';
            var custom_fields = params.custom_fields;
            if ($.isArray(custom_fields)) {
                custom_fields = custom_fields.join('/');
            }
            var main_params = [
                params.offset,
                params.sort,
                params.order,
                params.view,
                params.limit,
                custom_fields
            ].join('/');
            if (list_type === 'view') {
                return prefix + params.id + '/' + main_params;
            } else if (list_type === 'search') {
                return prefix + [
                    params.hash,
                    params.load_contacts
                ].join('/') + '/' + main_params;
            } else {
                return prefix + main_params;
            }
        },

        needAddCustomFields: function (params, list_type) {
            var custom_fields = $.wa.controller.parseCustomFields(params, list_type);
            if (!$.isEmptyObject(custom_fields) && $.wa.controller.hashes.length > 1) {
                var diff = $.wa.controller.diffHashes($.wa.controller.hashes[0], $.wa.controller.hashes[1], list_type);
                return diff.length <= 0 || (diff.length === 1 && diff.indexOf('custom_fields') >= 0);
            }
            return false;
        },

        contactsAllAction: function (params) {
            params = params || [];
            var p = this.parseParams(params, 'contacts/all');

    //        var curr_custom_fields = $.wa.controller.parseCustomFields(params, 'all');
    //        var prev_custom_fields = [];
    //        if ($.isEmptyObject(curr_custom_fields)) {
    //            prev_custom_fields = $.storage.get('contacts/all/custom_fields') || [];
    //            p.custom_fields = prev_custom_fields;
    //        } else {
    //            p.custom_fields = curr_custom_fields;
    //        }
    //
    //        if ($.wa.controller.needAddCustomFields(params, 'all')) {
    //            $.wa.controller.addCustomFields(curr_custom_fields);
    //            $.storage.set('contacts/all/custom_fields', curr_custom_fields);
    //            return;
    //        }

            var old_height = $('.wa-page-heading').height();
            if ($('.wa-page-heading-text').length) {
                $('.wa-page-heading-text').html($_('Loading...') + '<i class="icon16 loading"></i>');
            } else {
                this.showLoading();
            }
            this.setBlock('contacts-list');
            $('.wa-page-heading').css({
                height: old_height
            });
            this.loadGrid(p, '/contacts/all/', '?plugin=pro&module=contacts&action=list', {
                afterLoad: function (data) {
                    $('#sb-all-contacts-li span.count').html(data.count);
                    var header = $('.wa-page-heading')
                        .css({
                            width: '100%'
                        })
                        .contents()
                        .wrapAll('<div class="wa-page-heading-text float-left"></div>')
                        .end();
                    if (!$.isEmptyObject(data.abc) && !$('#c-abc-index').length) {
                        header.css({
                            height: ''
                        });
                        var abc_index = $('<div class="block not-padded" id="c-abc-index"></div>')
                            .appendTo(header)
                            .html(
                                tmpl('template-contacts-abc-index', {
                                    abc: data.abc,
                                    params: $.extend({
                                        order: 1,
                                        offset: 0,
                                        view: p.view,
                                        sort: 'name',
                                        limit: 30
                                    }, p, { view: p.view !== 'map' ? p.view : 'table' })
                                })
                            );
                        abc_index.find('.c-abc-index-list').append($.wa.grid.getPaging(data.count, false, false));

                        var list_block = abc_index.find('.c-abc-index-list');
                        var offset = abc_index.offset();
                        var width = abc_index.width();
                        var height_on_shown = header.height();
                        list_block.hide();
                        var height_on_hidden = header.height();
                        header.css({
                            height: height_on_hidden
                        });
                        abc_index.css($.extend({
                                position: 'absolute',
                                zIndex: 98,
                                width: width
                            }, offset))
                            .appendTo('body')
                            .find('.c-toggle-abc-index').click(function () {
                                var item = $(this).closest('.c-abc-index').find('.c-abc-index-list');
                                item.toggle();
                                if (item.is(':hidden')) {
                                    $('.wa-page-heading').css({
                                        height: height_on_hidden
                                    });
                                } else {
                                    $('.wa-page-heading').css({
                                        height: height_on_shown
                                    });
                                }
                                return false;
                            });

                        abc_index.off('click.abc-index', '.c-abc-index-list-ul a')
                            .on('click.abc-index', '.c-abc-index-list-ul a', function () {
                                list_block.find('li.selected').removeClass('selected');
                                $(this).closest('li').addClass('selected');
                            });
                        $('#sb-all-contacts-li').unbind('click.abc-index')
                            .bind('click.abc-index', function () {
                                var list_block = $('#c-abc-index .c-abc-index-list');
                                if (list_block.length) {
                                    list_block.find('li.selected').removeClass('selected');
                                } else {
                                    $(this).unbind('click.abc-index');
                                }
                            });
                        $(document).off('click.abc-index', '.paging a')
                            .on('click.abc-index', '.paging a', function () {
                                var list_block = $('#c-abc-index .c-abc-index-list');
                                if (list_block.length) {
                                    list_block.find('li.selected').removeClass('selected');
                                } else {
                                    $(document).off('click.abc-index', '.paging a');
                                }
                            });

                        $(window).unbind('resize.abc-index').bind('resize.abc-index', function () {
                            var abc_index = $('#c-abc-index');
                            if (!abc_index.length) {
                                $(this).unbind('resize.abc-index');
                            }
                            abc_index.width($('.wa-page-heading').width());
                            if (!abc_index.find('.c-abc-index-list').is(':hidden')) {
                                $('.wa-page-heading').height(abc_index.height());
                            }
                        });

                    } else if ($.isEmptyObject(data.abc)) {
                        $('#c-abc-index').remove();
                    } else {
                        var abc_index = $('#c-abc-index');
                        abc_index.find('.paging').replaceWith($.wa.grid.getPaging(data.count, false, false));
                        var tmp = $('<div></div>').html(tmpl('template-contacts-abc-index', {
                            abc: data.abc,
                            params: $.extend({
                                order: 1,
                                offset: 0,
                                view: p.view,
                                sort: 'name',
                                limit: 30
                            }, p, { view: p.view !== 'map' ? p.view : 'table' })
                        }));

                        var k = -1;
                        abc_index
                            .find('.c-abc-index-list-ul')
                            .find('li')
                            .each(function (i) {
                                if ($(this).hasClass('selected')) {
                                    k = i;
                                    return false;
                                }
                            })
                            .end()
                            .replaceWith(tmp.find('.c-abc-index-list-ul'));
                        if (k !== -1) {
                            abc_index.find('.c-abc-index-list-ul li:eq(' + k + ')').addClass('selected');
                        }
                        tmp.remove();
                    }

                    $('#c-abc-index').find('.c-abc-index-list-ul li:first a').attr('href', '#/');

    //                if ($.isEmptyObject(curr_custom_fields)) {
    //                    var custom_fields = $.storage.get('contacts/all/custom_fields');
    //                    if (!$.isEmptyObject(custom_fields)) {
    //                        var hash = $.wa.controller.hashes[0];
    //                        var prm = $.wa.controller.parseHash(hash, 'all');
    //                        prm.custom_fields = custom_fields.join('&');
    //                        hash = $.wa.controller.makeHash(prm, 'all');
    //                        $.wa.controller.stopDispatch(1);
    //                        $.wa.setHash('#/' + hash);
    //                    }
    //                }
                }
            });
        },

        contactsTagAction: function(params) {
            var p = this.parseParams(params.slice(1), 'contacts/tag/' + params[0]);
            p.query = '/tag/' + params[0];
            this.loadGrid(p, '/contacts/tag/' + params[0] + '/', '?plugin=pro&module=contacts&action=list', {
                beforeLoad: function(data) {
                    $.wa.controller.setBlock('contacts-list');
                }
            });
        },

        contactsViewAddAction: function() {
            this.setBlock('contacts-list', $_('New list'));
            this.hideLoading();
            $('#contacts-container')
                    .find('.contacts-data').hide()
                    .end()
                    .find('.c-list-toolbar').hide();
            var block = $('.wa-page-heading').before(
                '<span class="float-right c-view-settings-toggle-span" style="margin-top: 7px; margin-right: 10px;">' +
                    '<a href="javascript:void(0);" class="c-view-settings-toggle float-right"><i class="icon16 settings"></i></a>' +
                    '</span>'
            ).closest('.block');

            $('.wa-page-heading', block).hide();
            $.wa.controller.appendViewSettingsBlock(block, {
                info: {
                    type: 'new_view'
                },
                title: $_('New list'),
                hash_ar: ['view'],
                icons: $.wa.controller.view_icons
            });
            $('#c-view-name').focus().select();
            $('#c-view-cancel').unbind('click').click(function() {
                var hashes = $.wa.controller.hashes;
                if (hashes[1] && hashes[1] !== 'contacts/view/add/') {
                    $.wa.setHash(hashes[1]);
                } else {
                    $.wa.setHash('#');
                }
            });
            $('.c-view-settings-toggle-span').hide();

        },

        contactsViewAction: function (params, edit_mode) {
            if (params[0] && params[0] === 'add') {
                this.contactsViewAddAction();
                return;
            }
            var p = this.parseParams(params.slice(1), 'contacts/view/' + params[0]);
            p.query = '/view/' + params[0];

    //        var custom_fields = $.wa.controller.parseCustomFields(params, 'view');
    //        if ($.wa.controller.needAddCustomFields(params, 'view')) {
    //            $.wa.controller.addCustomFields(custom_fields, params[0]);
    //            return;
    //        }
    //        p.custom_fields = custom_fields;

            this.highlightSidebar();
            this.loadGrid(p, '/contacts/view/' + params[0] + '/', '?plugin=pro&module=contacts&action=list', {
                beforeLoad: function (data) {
                    if (!data.info || !data.info.id || data.info.id != params[0]) {
                        $.wa.setHash('#/contacts/all/');
                        return false;
                    }
                    $.wa.controller.current_view_id = params[0];
                    if (data.info && (data.info.type === 'list' || (data.info.type === 'category' && !data.info.system_id))) {
                        this.setBlock('contacts-list', undefined, ['list-actions', '']);
                    } else {
                        this.setBlock('contacts-list');
                    }
                    $('.wa-page-heading', block).css({
                        display: 'block',
                        maxWidth: '100%'
                    });
                    if (!$.isEmptyObject(data.info)) {
                        if (data.hash_ar[0] === 'view' && (data.user.is_admin || data.user.id == data.info.contact_id)) {
                            var block = $('.wa-page-heading').before(
                                '<span class="float-right c-view-settings-toggle-span" style="margin-top: 7px; margin-right: 10px;">' +
                                    '<a href="javascript:void(0);" class="c-view-settings-toggle float-right"><i class="icon16 settings"></i></a>' +
                                    '</span>'
                            ).closest('.block');

                            var openSettings = function (focus_on_name) {
                                if (data.info.type !== 'category' || (!data.info.system_id && !data.info.app_id)) {
                                    $('.wa-page-heading', block).hide();
                                }
                                $.wa.controller.appendViewSettingsBlock(block, data);
                                if (focus_on_name) {
                                    $('#c-view-name').focus().select();
                                }
                            };
                            var closeSettings = function () {
                                $('#c-view-cancel').click();
                            };
                            $('.c-view-settings-toggle').click(function () {
                                if (!$('.c-view-settings').is(':not(:hidden)')) {
                                    openSettings();
                                } else {
                                    closeSettings();
                                }
                                return false;
                            });
                            if (edit_mode && !$('.c-view-settings').is(':not(:hidden)')) {
                                openSettings(true);
                            }
                        }
                    }
                },
                afterLoad: function (data) {
                    var ul = $('.c-view-list');
                    if (ul.length) {
                        var li = $('li[data-id="' + params[0] + '"]', ul);
                        li.children('span.count').html(data.count);
                    }

                    if (!$.isEmptyObject(data.fields)) {
                        var fields = [];
                        for (var fld in data.fields) {
                            if (data.fields.hasOwnProperty(fld)) {
                                if (data.fields[fld].type === 'Custom') {
                                    fields.push(fld);
                                }
                            }
                        }
                        if (fields.length) {
                            var hash = $.wa.controller.hashes[0];
                            var prm = $.wa.controller.parseHash(hash, 'view');
                            prm.custom_fields = fields.join('&');
                            hash = $.wa.controller.makeHash(prm, 'view');
                            $.wa.controller.stopDispatch(1);
                            $.wa.setHash('#/' + hash);
                        }
                    }

                }
            });
        },

        appendViewSettingsBlock: function (block, data) {
            if (!$('.c-view-settings', block).length) {

                var html = tmpl('template-contacts-view-settings', data);
                var header = block.find('.wa-page-heading');
                if (header.length) {
                    header.after(html);
                } else {
                    block.append(html);
                }

                $('#c-view-settings-icons').find('li a').click(function () {
                    $('#c-view-settings-icons').find('li.selected').removeClass('selected');
                    $(this).parent().addClass('selected');
                    return false;
                });
                var current = $('#c-view-settings-icons').find('li[data-icon="' + $.wa.encodeHTML(data.info.icon) + '"]');
                if (current.length) {
                    current.closest('li').addClass('selected');
                } else {
                    $('#c-view-settings-icons li:first').addClass('selected');
                }
                block.find('input[name=shared][value=' + (data.info.shared > 0 ? 1 : 0) + ']').attr('checked', true);

                var hideSettings = function () {
                    $('.wa-page-heading', block).show();
                    $('.c-view-settings', block).hide();
                    $('#c-view-delete').hide();
                    block.height('');
                };

                $(window).unbind('resize.view-settings').bind('resize.view-settings',function () {
                    var icons_block = $("#c-view-settings-icons");
                    if (!icons_block.length) {
                        $(window).unbind('resize.view-settings');
                    } else {
                        block.find('.buttons').width('100%');
                        var count = 0;
                        var top = null;
                        var li = null;
                        icons_block.find('li').each(function () {
                            li = $(this);
                            var p = li.position();
                            if (top === null) {
                                top = Math.floor(p.top);
                            } else {
                                if (top !== Math.floor(p.top)) {
                                    return false;
                                }
                            }
                            count += 1;
                        });
                        if (count) {
                            var width = (count - 1) * li.outerWidth(true) + li.width();
                            $('#c-view-name').width(122 + width);
                        }
                    }
                }).trigger('resize');

                $('#c-view-save-button').click(function () {
                    var p = {
                        name: ($('#c-view-name', block).val() || '').trim()
                    };
                    if ($('#c-view-name', block).length && !p.name) {
                        $('#c-view-name', block).addClass('error').after('<em class="errormsg">' + $_('Required field') + '</em>');
                        return false;
                    }
                    var url = '';

                    if (data.hash_ar[0] === 'view') {
                        p.id = data.info.id;
                        url = '?plugin=pro&module=view&action=save';
                    } else if (data.hash_ar[0] === 'prosearch') {
                        p.hash = data.hash_ar[1];
                        p.count = data.count || 0;
                        url = '?plugin=pro&module=filter&action=save';
                    }

                    p.icon = $('#c-view-settings-icons li.selected').data('icon');
                    if (!p.icon) {
                        delete p.icon;
                    }
                    p.shared = block.find('input[name=shared]:checked').val();
                    $.post(url, p, function (r) {
                        if (r && r.status === "ok") {
                            if (data.hash_ar[0] === 'view') {
                                if (p.id) {
                                    var li = $('.c-view-list').find('li[data-id="' + p.id + '"]');
                                    var name = $.wa.encodeHTML(r.data.name);
                                    li.find('.name').html(name);
                                    if (r.data.icon) {
                                        var i = li.find('.icon16');
                                        i.attr('class', 'icon16 ' + r.data.icon);
                                    }
                                    var shared = data.info.shared;
                                    if (p.shared != shared) {
                                        if (shared == '0') {
                                            $('#c-shared-views').show().append(li.show());
                                        } else {
                                            $('#c-my-views-block').show();
                                            $('#c-all-my-views-toggle').before(li.show());
                                        }
                                        var len = $('#c-my-views-block').find('.view-item').length;
                                        $('#c-my-views-block').find('.c-my-views-count').text(len);
                                        if (len === 0) {
                                            $('#c-my-views-block').hide();
                                        }

                                        if (block.is('#c-my-views-block')) {
                                            var len = $('.view-item', block).length;
                                            $('.c-my-views-count', block).text(len);
                                            if (len === 0) {
                                                block.hide();
                                            }
                                        } else if (block.is('#c-shared-views')) {
                                            var len = block.find('li').length;
                                            if (len === 0) {
                                                block.hide();
                                            }
                                        }
                                    }
                                }
                                $('.wa-page-heading').html(name);
                                if (p.id) {
                                    $.wa.controller.redispatch();
                                } else {
                                    var list = $("#c-my-views,#c-shared-views");
                                    $('#c-my-views-block').trigger('add_new_item', r.data.view);
                                    list.find('li.selected').removeClass('selected');
                                    list.find('li:first').addClass('selected');
                                    $.wa.setHash('contacts/view/' + r.data.view.id + '/');
                                }
                                hideSettings();
                            } else if (data.hash_ar[0] === 'prosearch') {
                                (r.data.shared == '1' ?
                                    $('#c-shared-views') :
                                    $('#c-my-views-block')
                                    ).trigger('add_new_item', r.data);
                                $.wa.controller.setHash('#/contacts/view/' + r.data.id);
                                hideSettings();
                            }
                        }
                    }, 'json');
                    return false;
                });

                $('#c-view-cancel').click(function () {
                    hideSettings();
                });

                $('#c-view-delete').show().click(function () {
                    var view_id = data.info.id;

                    var deleteView = function (fn) {
                        $.post('?plugin=pro&module=view&action=delete', { id: view_id }, function (r) {
                            if (r && r.status === "ok") {
                                var li = $('.c-view-list').find('li[data-id="' + view_id + '"]');
                                var block = li.closest('.block');
                                li.remove();

                                if (block.is('#c-my-views-block')) {
                                    var len = $('.view-item', block).length;
                                    $('.c-my-views-count', block).text(len);
                                    if (len === 0) {
                                        block.hide();
                                    }
                                } else if (block.is('#c-shared-views')) {
                                    var len = block.find('li').length;
                                    if (len === 0) {
                                        block.hide();
                                    }
                                }
                            }
                            if (typeof fn === 'function') {
                                fn();
                            }
                            $.wa.setHash('#/');

                        }, 'json');
                    };

                    if (data.count > 0) {
                        var showDialog = function (d) {
                            d.waDialog({
                                disableButtonsOnSubmit: true,
                                onSubmit: function () {
                                    d.find('.loading').show();
                                    deleteView(function () {
                                        d.find('.loading').hide();
                                        d.trigger('close');
                                    });
                                    return false;
                                }
                            });
                        };
                        var d = $('#c-delete-view');
                        var p;
                        if (!d.length) {
                            p = $('<div></div>').appendTo('body');
                        } else {
                            p = d.parent();
                        }
                        p.load('?plugin=pro&module=view&action=deleteConfirm', {
                            id: view_id
                        }, function () {
                            showDialog($('#c-delete-view'));
                        });
                    } else {
                        deleteView();
                    }
                });

            } else {
                var name = '';
                if (data.info && data.info.name) {
                    name = data.info.name;
                } else if (data.title) {
                    name = data.title;
                }
                $('.c-view-settings', block).show();
                $('#c-view-delete').show();
                $('#c-view-name', block).removeClass('error');
                block.find('.errormsg').remove();
                if (!$('#c-view-name', block).val()) {
                    $('#c-view-name', block).val(name);
                }
            }
            $('.c-view-settings', block).css({
                overflow: 'hidden',
                'float': 'none'
            });

        },

        contactsNew_filterAction: function(params) {
            this.contactsSearchAction([], 'new_filter');
        },

        contactsSearchAction: function (params, env) {
            $.wa.search.init(params, env);
        },

        simpleSearch: function () {
            var s = $.trim($("#search-text").val());
            if (!s) {
                return;
            }

            var q = '';
            if (s.indexOf('@') !== -1) {
                q = "contact_info.email*=" + encodeURIComponent(s);
            } else {
                q = "contact_info.name.name*=" + encodeURIComponent(s);
            }
            $.wa.setHash("#/contacts/search/" + q + '/1/');
        },

        addToViewDialog: function () {
            if ($.wa.grid.getSelected().length <= 0) {
                return false;
            }

            $.wa.dialogCreate('c-d-add-to-list', {
                url: "?plugin=pro&module=view&action=add&view_id=" + this.current_view_id
            });
        },

        addToView: function (ids, newName) {
            $.post('?plugin=pro&module=view&action=save', {
                'contacts[]': $.wa.grid.getSelected(),
                'views[]': ids || [],
                'name': newName || ''
            }, function (response) {

                if (response.status === "ok") {

                    var list = $("#c-my-views,#c-shared-views");
                    var counters = response.data.counters;
                    if (!$.isEmptyObject(counters) && $.isArray(counters)) {
                        for (var i = 0, n = counters.length; i < n; i += 1) {
                            list.find('li[data-id="' + counters[i].id + '"]').find('.count').text(counters[i].count);
                        }
                    }

                    if (response.data.view) {
                        $('#c-my-views-block').trigger('add_new_item', response.data.view);
                        list.find('li.selected').removeClass('selected');
                        list.find('li:first').addClass('selected');

                        var hash = '#/contacts/view/' + response.data.view.id + '/';
                        $.wa.controller.stopDispatch(1);
                        $.wa.setHash(hash);
                        $.wa.controller.stopDispatch(0);
                        $.wa.controller.dispatch(hash, true);

                    } else {
                        $.wa.controller.afterInitHTML = function () {
                            $.wa.controller.showMessage(response.data.message);
                        };
                        $.wa.controller.redispatch();
                    }

                } else {
                    $.wa.controller.showMessage(response.data.message);
                }
            }, "json");
        },

        dialogRemoveSelectedFromList: function (ids) {
            ids = ids || $.wa.grid.getSelected();
            if (ids.length <= 0 || !$.wa.controller.current_view_id) {
                return;
            }
            $.wa.dialogCreate('confirm-remove-from-list-dialog', {
                content: $('<h2>' + $_('Exclude contacts from list &ldquo;%s&rdquo;?').replace('%s', $.wa.encodeHTML($('h1.wa-page-heading').text())) + '</h2>'),
                buttons: $('<div></div>')
                    .append(
                        $('<input type="submit" class="button red" value="' + $_('Exclude') + '">').click(function () {
                            $('<i style="margin: 8px 0 0 10px" class="icon16 loading"></i>').insertAfter(this);
                            $.post('?plugin=pro&module=view&action=deleteFrom', {'view_id': $.wa.controller.current_view_id, 'contacts': ids}, function (response) {
                                $.wa.dialogHide();
                                $.wa.controller.afterInitHTML = function () {
                                    $.wa.controller.showMessage(response.data.message);
                                };
                                $.wa.controller.redispatch();
                            }, 'json');
                        })
                    )
                    .append(' ' + $_('or') + ' ')
                    .append($('<a href="javascript:void(0)">' + $_('cancel') + '</a>').click($.wa.dialogHide)),
                small: true
            });
        },

        updateAddNewBlock: function (selected) {
            selected = selected || 'person';
            var items = {
                person: {
                    name: [$_('Person'), $_('New person')],
                    href: '#/contacts/add/'
                },
                company: {
                    name: [$_('Company'), $_('New company')],
                    href: '#/contacts/add/company/'
                },
                note: {
                    name: [$_('Note'), $_('New note')],
                    href: '#/contacts/add/note/'
                },
                event: {
                    name: [$_('Event'), $_('New event')],
                    href: '#/contacts/add/event/'
                }
            };

            if (!this.admin) {
                delete items.event;
            }

            var order = [ 'person', 'company', 'note', 'event' ];
            delete order[ order.indexOf(selected) ];

            if (!this.admin) {
                delete order[ order.indexOf('event') ];
            }

            var html = '';
            for (var i = 0; i < order.length; i += 1) {
                if (order[i]) {
                    html += '<li><a href="' + items[order[i]].href + '" class="' +
                        ( i === order.length - 1 ? 'after-line' : '' ) +
                        '">' + items[order[i]].name[0] + '</a></li>';
                }
            }

            $('#add-new-contact-block').html(
                '<a href=' + items[selected].href + ' class="bold no-underline" style="margin-left: 3px; float:left; margin-right: 5px;">' +
                    '<i class="icon16 add"></i>' + items[selected].name[1] +
                    '</a>' +
                    '<ul class="menu-h dropdown clickable" style="float: left;">' +
                    '<li style="display: inline-block; padding: 0;" id="add-new-contact-item">' +
                    '<a href="javascript:void(0);" class="inline-link open-menu"><b><i>' +
                    $_('or') +
                    '</i></b></a>' +
                    '<ul class="menu-v main-menu" style="">' +
                    html +
                    '</ul>' +
                    '</li>' +
                    '</ul>');

            this.initClickableMenu($('#add-new-contact-block .clickable'));

        },
        formNewAction: function (params) {
            this.formAction([-1]);
        },
        formAction: function (params) {
            params = params || [];
            if (params[0]) {
                $.wa.controller.load($('#c-core'), "?plugin=pro&module=form&current_form_id=" + parseInt(params[0]));
            } else {
                $.wa.controller.load($('#c-core'), "?plugin=pro&module=form");
            }
        },

        mergeduplicatesAction: function (params) {
            var hash = 'mergeduplicates/';
            var parseParams = function (params) {
                var p = {
                    offset: 0,
                    order: 0,   // 0 - desc, 1 - asc
                    count: 30,
                    query: ''
                };
                if (params[0]) {
                    p.offset = parseInt(params[0], 10) || 0;
                }
                if (params[1]) {
                    p.order = parseInt(params[1], 10) || 0;
                }
                if (params[2]) {
                    p.count = parseInt(params[2], 10) || 30;
                }
                if (params[3]) {
                    p.query = params[3];
                }
                return p;
            };
            var getParams = function () {
                var pos = location.hash.indexOf(hash);
                var tail_hash = '';
                if (pos !== -1) {
                    tail_hash = location.hash.slice(pos + hash.length);
                    tail_hash = tail_hash.split('/');
                }
                return tail_hash;
            };
            var makeHash = function (p) {
                return '#/' + hash + p.offset + '/' + p.order + '/' + p.count + '/' + (p.query ? p.query + '/' : '');
            };
            var parseQuery = function (query) {
                var q = {};
                var query_ar = query.split('&');
                for (var i = 0; i < query_ar.length; i += 1) {
                    var p = query_ar[i].split('=');
                    if (p[0] && p[1]) {
                        q[p[0]] = p[1];
                    }
                }
                return q;
            };
            var makeQuery = function (query) {
                var q = [];
                for (var k in query) {
                    if (query.hasOwnProperty(k)) {
                        q.push(k + '=' + query[k]);
                    }
                }
                return q.join('&');
            };
            var setHash = function (hash, show_loading) {
                hash = hash.replace(/^[^#]*#\/*/, '');
                var changed = $.isEmptyObject($.wa.controller.hashes) || $.wa.controller.hashes[0] !== hash;
                if (changed && show_loading) {
                    $("#c-search-duplicates-form .buttons .loading").show();
                }
                $.wa.setHash('/' + hash);
                return changed;
            };
            var p = parseParams(params);
            var q = parseQuery(p.query);
            $.wa.controller.load($('#c-core'), "?plugin=pro&module=backend&action=mergeduplicates", $.extend({}, p, q), null, function () {
                $(window).scrollTop(0, 200);
                $.wa.controller.setTitle($('#c-mergeduplicates-content h1.title').text());
                $("#c-search-duplicates-form").submit(function () {
                    var p = parseParams(getParams());
                    var q = parseQuery(p.query);
                    q.field = $('#c-search-duplicates-by-field').val();
                    p.query = makeQuery(q);
                    setHash(makeHash(p), true);
                    return false;
                });

                var workupLink = function(link, response) {
                    if (response.merge_result && response.merge_result.total_merged === response.merge_result.total_requested) {
                        link.closest('tr').find('td:not(:first)').css({
                            textDecoration: 'line-through'
                        }).end().find('td:first').html(
                            '<a href="#/contact/' + response.master.id + '/" target="_blank">' + $.wa.encodeHTML(response.master.name) + '</a>'
                        );
                    } else {
                        link.addClass('partial');
                    }
                    var td = link.closest('td').css({
                        textDecoration: ''
                    }).html('<span class="float-right" style="margin-right: 10px;">' + response.message + '</span>');
                    td.append(link.hide().addClass('finished'));
                };

                $('#c-duplicates').on('click', '.c-merge', function () {
                    var link = $(this);
                    var loading = link.parent().find('.loading').css('opacity', 1);
                    $.get("?plugin=pro&module=backend&action=mergeduplicatesGetContacts", {
                        field: $('#c-search-duplicates-by-field').val(),
                        value: link.attr('data-field-value')   // not using data(fieldValue) cause of implicit type casting (i.e. 01 -> 1)
                    }, function (r) {
                        if (!$.isEmptyObject(r.data.contacts)) {
                            loading.css('opacity', 0);
                            $.wa.controller.load("#c-merge-contacts-content .c-core-content", '?module=contacts&action=mergeSelectMaster', { ids: r.data.contacts }, null, function() {
                                $('#c-mergeduplicates-content').hide();
                                $('#c-merge-contacts-content').show();
                                $(window).unbind('wa_cancel_merge_contacts').one('wa_cancel_merge_contacts', function() {
                                    $('#c-mergeduplicates-content').show();
                                    $('#c-merge-contacts-content').hide();
                                    return false;
                                });
                                $(window).unbind('wa_after_merge_contacts').one('wa_after_merge_contacts', function(e, response) {
                                    if (response && response.status === 'ok') {
                                        workupLink(link, response.data);
                                    }
                                    $('#c-mergeduplicates-content').show();
                                    $('#c-merge-contacts-content').hide();
                                    return false;
                                });
                            });
                        }
                    }, 'json');
                });
                $('#c-duplicates-paging').bind('choose_page', function (e, offset) {
                    var params = getParams();
                    var p = parseParams(params);
                    p.offset = offset;
                    setHash(makeHash(p));
                });
                $('#c-duplicates-paging .items-per-page').bind('change', function () {
                    var params = getParams();
                    var p = parseParams(params);
                    p.offset = 0;
                    p.count = parseInt($(this).val(), 10);
                    setHash(makeHash(p));
                });

                var df = null;      // $.Deferred

                $('#c-new-search-link').click(function() {
                    if (df) {
                        df.done(function() {
                            $.wa.setHash(hash);
                        });
                    } else {
                        $.wa.setHash(hash);
                    }
                    return false;
                });
                $('#c-auto-merge-dupliactes-open-start-text').click(function() {
                    $('#c-auto-merge-dupliactes-start-text').show();
                });
                $('.c-auto-merge-duplicates-start,.c-auto-merge-duplicates-resume').click(function() {

                    var container = $('#c-duplicates');
                    var field = $('#c-search-duplicates-by-field').val();
                    $('.c-auto-merge-duplicates-break').show();
                    $('.c-auto-merge-duplicates-start,.c-auto-merge-duplicates-resume').hide();
                    $("#c-search-duplicates-form").hide();
                    $('#c-new-search-link').show();
                    $('#c-duplicates-paging').hide();
                    var total_count = $('#c-auto-merge-dupliactes-total-count').val();
                    var count = 0;

                    var done = function() {
                        $('.c-auto-merge-duplicates-start,.c-auto-merge-duplicates-resume').hide();
                        $('.c-auto-merge-duplicates-break').hide();
                        $('#c-attention-message').hide();
                        $('.c-done-message').show();
                    };

                    var step = function() {
                        var link = container.find('.c-merge:not(.finished):first');
                        if (!link.length) {
                            if (count < total_count) {
                                var offset = container.find('.c-merge.partial').length;
                                $.get("?plugin=pro&module=backend&action=mergeduplicates", $.extend({}, p, q, { offset: offset }), function(html) {
                                    if (html) {
                                        var tmp = $('<div>').html(html);
                                        var tbody = tmp.find('#c-duplicates tbody');
                                        if (tbody.find('.c-mergeduplicates-row:first').length) {
                                            $('#c-duplicates tbody').append(tbody.find('.c-mergeduplicates-row'));
                                            step();
                                        } else {
                                            done();
                                        }
                                        tmp.remove();
                                    } else {
                                        done();
                                    }
                                });
                            } else {
                                done();
                            }
                            return;
                        }
                        link.parent().find('.loading').css('opacity', 1);
                        df = new $.Deferred();

                        df.fail(function() {
                            $('#c-auto-merge-duplicates-loading').hide();
                            $('.c-auto-merge-duplicates-resume').show();
                        });

                        $.get("?plugin=pro&module=backend&action=mergeduplicatesGetContacts", {
                            field: field,
                            value: link.attr('data-field-value'),   // not using data(fieldValue) cause of implicit type casting (i.e. 01 -> 1)
                            master_slaves: 1
                        }, function (r) {
                            if (r && r.status === 'ok' && !$.isEmptyObject(r.data)) {
                                $.post('?module=contacts&action=merge', {
                                    master_id: r.data.master,
                                    slave_ids: r.data.slaves
                                }, function(r) {
                                    if (r && r.status === 'ok') {
                                        workupLink(link, r.data);
                                    }
                                }, 'json').always(function() {
                                    df.resolve();
                                });
                            } else {
                                link.addClass('finished');
                                df.resolve();
                            }
                        }, 'json').error(function() {
                            df.resolve();
                        });

                        df.done(function() {
                            count += 1;
                            step();
                        });
                    };

                    step();
                });

                $('.c-auto-merge-duplicates-break').click(function() {
                    $('.c-auto-merge-duplicates-break').hide();
                    $('#c-auto-merge-duplicates-loading').show();
                    df.reject();
                });

            });

        },

        settingsRegionAction: function (params) {
            this.setBlock();
            this.load($('#c-core'), "?plugin=pro&module=settings&action=regions");
        },

        initClickableMenu: function(menu) {
            menu.find('a:first').unbind('click').bind('click', function() {
                $(this).closest('.clickable').find('ul').toggle();
                return false;
            });
            $(window).unbind('.clickable-hide').bind('click.clickable-hide', function() {
                var p = menu.parent();
                if (p && p.length) {
                    $('.clickable ul').hide();
                } else {
                    $(this).unbind('.clickable-hide');
                }
            });
        },

        confirmLeave: function(is_relevant, warning_message, confirm_question, stop_listen, ns) {
            var h, h2, $window = $(window);
            var event_id = ('' + Math.random()).slice(2);
            if (ns) {
                event_id = ns;
            }

            this.confirmLeaveStop(event_id);

            $window.on('beforeunload.' + event_id, h = function(e) {
                if (typeof stop_listen === 'function' && stop_listen()) {
                    $window.off('.' + event_id);
                    return;
                }
                if (is_relevant()) {
                    return warning_message;
                }
            });

            $window.on('wa_before_dispatched.' + event_id, h2 = function(e) {
                if (typeof stop_listen === 'function' && stop_listen()) {
                    $.wa.controller.confirmLeaveStop(event_id);
                    return;
                }
                if (!is_relevant()) {
                    $window.off('wa_before_dispatched', h2);
                    return;
                }
                if (!confirm(warning_message + " " + confirm_question)) {
                    e.preventDefault();
                }
            });

            return event_id;

        },

        confirmLeaveStop: function(confirm_id) {
            $(window).off('.' + confirm_id);
        }

    });

    $.wa.history = $.extend($.wa.history || {}, {});
    var updateHistory = $.wa.history.updateHistory || function() {};
    $.wa.history = $.extend($.wa.history, {
        data: null,
        updateHistory: function (historyData) {
            var h, filteredHistoryData = [];
            for (var i = 0, n = historyData.length; i < n; i += 1) {
                h = historyData[i];
                if (h.type !== 'search') {
                    filteredHistoryData.push(h);
                }
            }
            return updateHistory.call(this, filteredHistoryData);
        }
    });

    $(window).unbind('wa_before_dispatched.contacts_pro').bind('wa_before_dispatched.contacts_pro', function (e, hash) {
        hash = (hash || '').replace(/^[^#]*#\/*/, '');
        var hash_ar = hash.split('/');
        hash_ar[0] = hash_ar[0] || 'contacts';
        hash_ar[1] = hash_ar[1] || 'all';
        if (hash_ar[0] !== 'contacts' || hash_ar[1] !== 'all') {
            $('#c-abc-index').hide().remove();
        }
    });

    var showMenus = $.wa.controller.showMenus || function() {};
    $.wa.controller.showMenus = function (show) {
        showMenus.apply(this, arguments);
        var edit_right = !!(typeof this.options.rights.edit === 'undefined' ? true : this.options.rights.edit);
        var delete_from_list = '';
        if (edit_right) {
            delete_from_list =
                '<li class="disabled">' +
                    '<a href="javascript:void(0);" onclick="$.wa.controller.dialogRemoveSelectedFromList(); return false"><i class="icon16 close"></i>' + $_('Exclude from this list') + '</a>' +
                    '</li>';
        } else {
            delete_from_list =
                '<li class="force-disabled">' +
                    '<a href="javascript:void(0);"><i class="icon16 close"></i>' + $_('Exclude from this list') + '</a>' +
                    '</li>';
        }

        var html =
            ((show.indexOf('list-actions') >= 0 && this.current_view_id) ?
                delete_from_list : '') +
                ($.wa.controller.addToViewDialog ?
                    '<li class="disabled">' +
                        '<a href="#" onclick="$.wa.controller.addToViewDialog(); return false"><i class="icon16 add-to-list"></i>' + $_('Add to list') + '</a>' +
                        '</li>' : '');

        html +=
            '<li class="disabled">'
                + '<a href="#" onclick="$.wa.controller.export(); return false;" class="red" id="c-export"><i class="icon16 export"></i>'
                + $_('Export')
                + '</a>'
                + '</li>';

        if (html) {
            $('#actions-with-selected').prepend(html);
        }
        $('#list-views').append('<li rel="map"><a href="#" title="' + $_('Map') + '" id="c-view-map-link"><i class="icon16 map"></i></a></li>');
        $('#add-to-category-link').remove();
    };

    var deleteCustomField = $.wa.controller.deleteCustomField || function() {};
    $.wa.controller.deleteCustomField = function (field_id) {
        deleteCustomField.apply(this, arguments);
        var list_type = 'all';
        var hash = location.hash.replace(/^[^#]*#\/*/, '');
        var hash_ar = hash.split('/');
        list_type = hash_ar[1];
        var params = hash_ar.slice(2);
        var custom_fields = this.parseCustomFields(params, list_type);
        var new_custom_fields = [];
        for (var i = 0; i < custom_fields.length; i += 1) {
            if (custom_fields[i] !== field_id) {
                new_custom_fields.push(custom_fields[i]);
            }
        }
        var p = this.parseHash(hash, list_type);
        p.custom_fields = new_custom_fields.join('&');
        var hash = this.makeHash(p, list_type);
        this.stopDispatch(1);
        $.wa.setHash('#/' + hash);
        if (!new_custom_fields.length) {
            this.expandCustomFieldsControl();
        }

        this.customFieldsControlCheckItems(new_custom_fields);
        if (list_type === 'all') {
            $.storage.set('contacts/all/custom_fields', new_custom_fields);
        } else {
            p.id = parseInt(p.id, 10);
            if (list_type === 'view' && p.id) {
                $.post('?plugin=pro&module=contacts&action=listFields', {
                    contacts: [],
                    custom_fields: p.custom_fields,
                    view_id: p.id
                });
            }
        }
    };

    if ($.wa.grid) {

        $.wa.grid.viewmap = function (data) {

            var points = [];
            var bounds = new google.maps.LatLngBounds();
            var contacts = data.contacts;
            for (var i = 0; i < contacts.length; i += 1) {
                var contact = contacts[i];
                if (!$.isEmptyObject(contact.address)) {
                    for (var j = 0; j < contact.address.length; j += 1) {
                        var address = contact.address[j];
                        if (address.data.lat && address.data.lng) {
                            var point = {
                                latLng: new google.maps.LatLng(address.data.lat.replace(',', '.'), address.data.lng.replace(',', '.')),
                                contact: contact,
                                address: {
                                    value: address.value
                                }
                            };
                            bounds.extend(point.latLng);
                            points.push(point);
                        }
                    }
                }
            }

            data.geolocations_stats = data.geolocations_stats || {
                contacts_count:0,
                points_count:0,
                message: ''
            };

            $('#c-list-toolbar-menu-wrapper').html('<div style="height: 20px; padding-top: 3px;" class="map-report"></div>');
            var msg = data.geolocations_stats.message;
            msg = msg.replace('%HREF%', 'javascript:void(0)').replace('%CLASS%', 'c-explain-dialog-link');
            $('#c-list-toolbar-menu-wrapper').find('.map-report').html(msg);
            $('#c-list-toolbar-menu-wrapper').find('.map-report .c-explain-dialog-link').click(function() {
                $.wa.dialogCreate('c-explain-dialog', {
                    content: '<h1>' + $_('Why some contacts do not have geolocation info') + '</h1><p>'
                        + $_('Webasyst uses Google Maps service to identify geolocation associated with a contact address. This occurs when you add or edit contacts. However, we do not send requests to Google while you perform bulk operations, e.g. contact import, because of Google limits.Also contacts added before Webasyst Contacts Pro plugin installation do not have geolocation info in their records.')
                        + '</p><p>'
                        + $_('ADVICE: To update geolocation for any contact, open it, enter a valid address, and click Save button.')
                        + '</p>',
                    buttons: $('<input type="button" class="button gray" value="' + $_('Close') + '">').click(function() {
                        $.wa.dialogHide();
                    })
                });
            });
            if (points.length <= 0) {
                $('#c-list-toolbar-menu-wrapper').find('.map-report').css('color', 'red');
            }


            var map = null;

            var map_canvas = $('#map-canvas');
            var win = $(window);
            if (!map_canvas.length) {
                $('<div id="map-canvas" style="width: 100%;"></div>').
                    appendTo($('#contacts-container .contacts-data'));
                map_canvas = $('#map-canvas');
                map_canvas.height(
                    Math.max(win.height() - (map_canvas.offset().top || 0) - 30, 0)
                );
            }

            map = new google.maps.Map($('#map-canvas').get(0), {
                zoom: points.length ? 15 : 1,
                center: bounds.getCenter(),
                mapTypeId: google.maps.MapTypeId.ROADMAP
            });

            if (points.length > 0) {
                var ne = bounds.getNorthEast();
                var sw = bounds.getSouthWest();
                if (Math.abs(sw.lng() - ne.lng()) >= 0.0045 || Math.abs(sw.lat() - ne.lat()) >= 0.0045) {
                    map.fitBounds(bounds);
                }
            }

            var info_window = new google.maps.InfoWindow({
                content: '',
                maxWidth: 250
            });

            for (var i = 0; i < points.length; i += 1) {
                points[i].marker = new google.maps.Marker({
                    position: points[i].latLng,
                    map: map
                });
                (function (i) {
                    google.maps.event.addListener(points[i].marker, 'click', function () {
                        var point = points[i];
                        var contact = point.contact;
                        var name = $.wa.grid.formNamesHtml(contact);
                        var content = '<h2 style="font-size: 1.1em; margin-bottom: 6px;"><a class="no-underline" href="#/contact/' + contact.id + '/">' +
                            (name[0] || '') +
                            '</a></h2>' +
                            '<p style="font-size: 0.9em;">' + point.address.value + '</p>';
                        info_window.setContent(content);
                        info_window.open(map, point.marker);
                    });
                })(i);
            }

            $('#c-core .clear-left').remove();
        };

    }

    $(window).unbind('wa-dispatched.contacts_pro').bind('wa-dispatched.contacts_pro', function () {
        $('#wa').removeClass('c-search');
    });

})();