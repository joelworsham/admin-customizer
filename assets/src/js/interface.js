/*
 Functionality for the interface.

 @since 0.1.0

 global postBoxL10n
 */

var AC_Interface;
(function ($, data) {
    'use strict';

    /**
     * The interface API.
     *
     * @since 0.1.0
     * @access private
     */
    var api = AC_Interface = {

        /**
         * All interface elements.
         *
         * @since 0.1.0
         *
         * @var Array
         */
        $elements: [],

        /**
         * The currently being ordered menu.
         *
         * @since 0.1.0
         *
         * @var array|boolean
         */
        active_menu: false,

        /**
         * Widgets on the dashboard.
         *
         * @since 0.1.0
         *
         * @var Array
         */
        dash_widgets: [],

        /**
         * The currently being modified role.
         *
         * @since 0.1.0
         *
         * @var string|null
         */
        current_role: null,

        /**
         * Counts down for minimum cover display.
         *
         * @since 0.1.0
         *
         * @var int
         */
        cover_timer: 0,

        /**
         * Indicates whether options have been changed since load.
         *
         * @since 0.1.0
         *
         * @var boolean
         */
        unsaved_changes: false,

        /**
         * Initializes the interface.
         *
         * @since 0.1.0
         */
        init: function () {

            api.get_elements();
            api.setup_handlers();
            api.launch_interface();
        },

        /**
         * Gets all interface elements.
         *
         * @since 0.1.0
         */
        get_elements: function () {

            api.$elements.adminmenu = $('#adminmenu');
            api.$elements.toolbar = $('#wpadminbar');
            api.$elements.adminmenu_trash = $('.ac-interface-adminmenu-trash');
            api.$elements.widgets_toolbar = $('#ac-interface-widgets-toolbar');
            api.$elements.widgets_trash = api.$elements.widgets_toolbar.find('.ac-interface-widgets-trash');
            api.$elements.widgets_new = api.$elements.widgets_toolbar.find('.ac-interface-widgets-new');
            api.$elements.dashwidgets = $('#dashboard-widgets').find('.meta-box-sortables');
            api.$elements.widget_move_adminnotice = $('#ac-widgets-move-adminnotice');
            api.$elements.toolbar = $('#ac-interface-toolbar');
            api.$elements.cover = $('#ac-interface-cover');
            api.$elements.save = api.$elements.toolbar.find('[data-ac-interface-save]');
            api.$elements.reset = api.$elements.toolbar.find('[data-ac-interface-reset]');
            api.$elements.exit = api.$elements.toolbar.find('[data-ac-interface-exit]');
            api.$elements.select_role = api.$elements.toolbar.find('[data-ac-interface-select-role]');
        },

        /**
         * Sets up handlers.
         *
         * @since 0.1.0
         */
        setup_handlers: function () {

            api.$elements.save.click(api.save_interface);
            api.$elements.exit.click(api.exit_interface);
            api.$elements.reset.click(api.reset_interface);
            api.$elements.select_role.change(api.change_role);

            api.$elements.dashwidgets.on('click', '[data-ac-edit]', api.open_edit_widget);
            api.$elements.dashwidgets.on('click', '[data-ac-cancel]', api.cancel_edit_widget);
            api.$elements.dashwidgets.on('click', '[data-ac-save]', api.save_edit_widget);
            api.$elements.dashwidgets.on('click', 'h2.hndle input[type="text"]', function (e) {
                $(this).closest('.postbox').removeClass('closed');
            });
            api.$elements.dashwidgets.on('sortstop', api.show_widget_move_adminnotice);
        },

        /**
         * Launches the interface.
         *
         * @since 0.1.0
         */
        launch_interface: function () {

            api.current_role = data['current_role'];

            // Disable saving of metabox ordering and hidden/closed status
            $('#closedpostboxesnonce').val('disabled')
                .before('<!-- Disabled by Admin Customizer Sandbox for the Interface page. @see assets/src/js/interface.js api.launch_interface() for more information. -->');
            $('#meta-box-order-nonce').val('disabled');

            // ADMIN MENU

            // Make the currently open menu not open
            api.$elements.adminmenu.find('li, a')
                .removeClass('wp-has-current-submenu current')
                .addClass('wp-not-current-submenu')
                .attr('data-ac-menu-item-open', '1');

            // Remove the collapse button
            $('#collapse-menu').remove();

            api.active_menu = data['current_menu'];

            // Setup data on menu items (makes life SO MUCH DANG easier)
            api.$elements.adminmenu.find('> li').each(function () {

                var menu_item_i = $(this).index(),
                    menu_item = api.active_menu[menu_item_i],
                    $submenu = $(this).find('.wp-submenu');

                $(this).data('ac_active_menu_item', menu_item);
                api.active_menu[menu_item_i].$item = $(this);

                // Submenu
                if ($submenu.length) {
                    $submenu.find('> li').each(function () {

                        var submenu_item_i = $(this).index(),
                            submenu_item;

                        if (submenu_item_i === 0) {
                            return true;
                        }

                        // Ignore first item, it is a dummy item
                        submenu_item_i = submenu_item_i - 1;
                        submenu_item = api.active_menu[menu_item_i].submenu[submenu_item_i];

                        $(this).data('ac_active_submenu_item', submenu_item);
                        api.active_menu[menu_item_i].submenu[submenu_item_i].$item = $(this);
                    });
                }
            });

            // Move items into the trash
            api.$elements.adminmenu.find('> li').each(function () {

                if (api.active_menu[$(this).index()].remove) {
                    $(this).appendTo(api.$elements.adminmenu_trash);
                }
            });

            // Add sortable
            api.$elements.adminmenu
                .sortable({
                    placeholder: 'ui-sortable-placeholder',
                    axis: 'y',
                    appendTo: 'parent',
                    connectWith: '.ac-interface-adminmenu-trash',
                    change: menu_sort_stop,
                })
                .on('click', 'a', api.disable_anchor)
                .find('.wp-submenu').sortable({
                    placeholder: 'ui-sortable-placeholder',
                    axis: 'y'
                }
            );

            // Trash sortable
            api.$elements.adminmenu_trash.sortable({
                placeholder: 'ui-sortable-placeholder',
                axis: 'y',
                connectWith: '#adminmenu',
                receive: menu_trash_receive,
                remove: menu_trash_remove
            });

            if (!api.$elements.adminmenu_trash.find('> li').length) {
                api.$elements.adminmenu_trash.addClass('empty');
                api.$elements.adminmenu_trash.attr('data-emptyString', data.interfaceL10n.adminmenuTrashEmpty);
            }

            /**
             * Fires when the adminmenu sort stops.
             *
             * @since 0.1.0
             */
            function menu_sort_stop() {
                api.unsaved_changes = true;
            }

            /**
             * Fires when the adminmenu trash receives an item.
             *
             * @since 0.1.0
             */
            function menu_trash_receive(e, ui) {

                api.unsaved_changes = true;

                api.$elements.adminmenu_trash.removeClass('empty');
            }

            /**
             * Fires when the adminmenu trash removes an item.
             *
             * @since 0.1.0
             */
            function menu_trash_remove(e, ui) {

                api.unsaved_changes = true;

                if (!api.$elements.adminmenu_trash.find('> li').length) {
                    api.$elements.adminmenu_trash.addClass('empty');
                }
            }

            // WIDGETS

            // Move widgets toolbar
            api.$elements.widgets_toolbar.insertBefore('#dashboard-widgets-wrap');

            // Get dash widgets
            api.dash_widgets = data['dash_widgets'];

            // Add edit HTML to each title
            api.$elements.dashwidgets.find('h2.hndle').append($('#ac-interface-widget-edit-actions').html());

            // Move to trash
            if (api.dash_widgets) {
                $.each(api.dash_widgets, function (widget_ID, widget) {

                    var $trashed_widget;

                    if (!widget['trashed']) {
                        return true;
                    }

                    $trashed_widget = $('#' + widget_ID);
                    if ($trashed_widget.length) {
                        $trashed_widget.addClass('closed')
                            .appendTo(api.$elements.widgets_trash)
                            .find('h2.hndle')
                            .append('<span class="ac-interface-widget-trashed-postfix"> '
                                + data['interfaceL10n']['widgetsTrashPostfixWP'] + '</span>');
                    }
                });
            }

            // Trash sortable
            api.$elements.widgets_trash.sortable({
                containment: false,
                connectWith: '.meta-box-sortables',
                placeholder: 'sortable-placeholder',
                receive: widgets_trash_receive,
                remove: widgets_trash_remove,
                over: widgets_trash_over,
                out: widgets_trash_out
            });

            if (!api.$elements.widgets_trash.find('> .postbox').length) {
                api.$elements.widgets_trash.addClass('empty');
                api.$elements.widgets_trash.attr('data-emptyString', postBoxL10n.postBoxEmptyString);
            }

            // Prevent opening inside trash
            api.$elements.widgets_trash.on('click', '.hndle', function (e) {
                $(this).closest('.postbox').addClass('closed');
            });

            // Connect the dash widgets with the trash
            api.$elements.dashwidgets.sortable('option', 'connectWith',
                api.$elements.dashwidgets.sortable('option', 'connectWith') + ', .ac-interface-widgets-trash'
            );

            // Add new widgets section
            api.$elements.widgets_new.find('.meta-box-sortables').removeClass('meta-box-sortables');

            api.$elements.widgets_new.sortable({
                connectWith: '.meta-box-sortables',
                placeholder: 'sortable-placeholder',
                stop: add_new_widget
            });

            // Prevent collapsing widget inside when clicking "Title" input
            api.$elements.widgets_new.find('h2.hndle input[name$="_title"]').click(function (e) {
                e.stopPropagation();
            });

            /**
             * Fires when the widgets trash receives an item.
             *
             * @since 0.1.0
             */
            function widgets_trash_receive(e, ui) {

                var message = ui.item.filter('[id^="ac-widget-"]').length ?
                    data['interfaceL10n']['widgetsTrashPostfixAC'] :
                    data['interfaceL10n']['widgetsTrashPostfixWP'];

                api.unsaved_changes = true;

                ui.item.addClass('closed');

                ui.item.find('h2.hndle').append('<span class="ac-interface-widget-trashed-postfix"> ' + message + '</span>');

                api.$elements.widgets_trash.removeClass('empty');

                if (!ui.sender.find('.postbox').length) {
                    ui.sender.addClass('empty-container');
                    ui.sender.attr('data-emptyString', postBoxL10n.postBoxEmptyString);
                }
            }

            /**
             * Fires when the widgets trash removes an item.
             *
             * @since 0.1.0
             */
            function widgets_trash_remove(e, ui) {

                var widget_args = api.dash_widgets[ui.item.attr('id')];

                api.unsaved_changes = true;

                if (widget_args) {
                    delete widget_args['trashed'];
                }

                ui.item.removeClass('closed');

                ui.item.find('.ac-interface-widget-trashed-postfix').remove();

                if (!api.$elements.widgets_trash.find('> .postbox').length) {
                    api.$elements.widgets_trash.addClass('empty');
                    api.$elements.widgets_trash.attr('data-emptyString', postBoxL10n.postBoxEmptyString);
                }
            }

            /**
             * Fires when the widgets trash has an item hover over it.
             *
             * @since 0.1.0
             */
            function widgets_trash_over(e, ui) {

                ui.placeholder.height(ui.helper.find('.hndle').outerHeight());
                ui.helper.addClass('closed')
                    .height(ui.helper.find('.hndle').outerHeight());
            }

            /**
             * Fires when the widgets trash has an item leave it.
             *
             * @since 0.1.0
             */
            function widgets_trash_out(e, ui) {

                // Sometimes out fires when it shouldn't
                if (!ui.helper) {
                    return;
                }

                ui.helper.removeClass('closed')
                    .height('auto')
                    .width(api.$elements.dashwidgets.width());
            }

            /**
             * Fires when the widgets new has an item removed.
             *
             * @since 0.1.0
             */
            function add_new_widget(e, ui) {

                var $widget = ui.item,
                    $form = $widget.find('.ac-widget-form'),
                    widget_args = {},
                    widget_index, widget_ID, $new_widget;

                api.unsaved_changes = true;

                // TODO Handle checkboxes/radios
                if ($form.length) {
                    $.each($form.serializeArray(), function (i, object) {
                            widget_args[object.name] = object.value;
                    });
                }

                widget_args['title'] = $widget.find('[name="ac_widget_title"]').val();
                if (!widget_args['title']) {
                    widget_args['title'] = $widget.find('.ac-widget-title-text').html();
                }

                api.reveal_cover();

                // Reset widget form
                $form.find('input:text, input:password, input:file, select, textarea').val('');
                $form.find('input:radio, input:checkbox')
                    .removeAttr('checked').removeAttr('selected');
                $widget.find('[name="ac_widget_title"]').val('');
                $widget.addClass('closed');

                // Place a clone back into the original list
                $new_widget = $widget.clone(true);
                api.$elements.widgets_new.append($new_widget);
                api.$elements.widgets_new.sortable('refresh');

                // Prepare the widget in the new list
                // Get widget index
                widget_index = api.$elements.dashwidgets.find('[id^="' + $widget.attr('id') + '"]').length - 1;

                $widget.find('.inside').html('');
                $widget.find('.ac-widget-title-text').html(widget_args['title'] ? widget_args['title'] : widget_args['widget_name']);
                $widget.find('.ac-widget-title-input').remove();
                $widget.find('h2.hndle').append($('#ac-interface-widget-edit-actions').html());
                $widget.removeClass('closed');
                $widget.attr('id', widget_ID = $widget.attr('id') + '_' + widget_index);

                widget_args['id'] = widget_ID;

                // Add to api option
                api.dash_widgets[widget_ID] = widget_args;

                // Fire method for receiver sortable as to trigger metabox order saving
                // @see wp-admin/js/postbox.js postboxes.save_order()
                postboxes.save_order('dashboard');

                // Get the widget output HTML and paste it in the new widget
                $.post(
                    ajaxurl,
                    {
                        action: 'ac-get-widget-html',
                        widget: widget_args,
                        output: 'widget',
                        ac_nonce: data.nonce
                    },
                    function (response) {

                        switch (response['status']) {
                            case 'success':
                                $widget.find('.inside').html(response['output']);
                                break;

                            case 'fail':
                                alert(response['error_msg']);
                                break;

                            default:
                                alert('Could not complete for unknown reasons.');
                        }

                        api.hide_cover();
                    }
                );
            }
        },

        /**
         * Sets up a widget for editing.
         *
         * @since 0.1.0
         */
        open_edit_widget: function () {

            var $old_widget = $(this).closest('.postbox'),
                $widget_placeholder = $old_widget.clone(true),
                $title, title,
                widget_args = api.dash_widgets[$old_widget.attr('id')];

            // Hide old widget and paste new widget after
            $old_widget.hide().attr('data-id', $old_widget.attr('id')).removeAttr('id').after($widget_placeholder);

            // Setup new widget
            $widget_placeholder.removeClass('closed');

            // Set buttons' visibility
            $widget_placeholder.find('[data-ac-edit]').hide();
            $widget_placeholder.find('[data-ac-save], [data-ac-cancel]').show();

            api.reveal_cover();

            // Title field. We have to use this method to grab the text form the 'hide-if-no-js' element, if it exists.
            $title = $('<div>' + widget_args['title'] + '</div>');
            if ($title.find('.hide-if-no-js').length) {

                title = $title.find('.hide-if-no-js').html();
                widget_args['no-js-title'] = $title.find('.hide-if-js').html();
            } else {
                title = widget_args['title'];
            }
            $widget_placeholder.find('h2.hndle span').first().html('<input type="text" class="ac-widget-title-input" value="' + title + '" />');

            // If AC widget, get the form and output it
            if (widget_args['ac_id']) {
                $.post(
                    ajaxurl,
                    {
                        action: 'ac-get-widget-html',
                        widget: widget_args,
                        output: 'form',
                        ac_nonce: data['nonce']
                    },
                    function (response) {
                        switch (response['status']) {
                            case 'success':
                                $widget_placeholder.find('.inside').html(response['output']);
                                break;

                            case 'fail':
                                alert(response['error_msg']);
                                break;

                            default:
                                alert('Could not complete for unknown reasons.');
                        }

                        begin_editing_widget();
                    }
                );
            } else {

                // Hide inside
                $widget_placeholder.find('.inside').slideUp();

                begin_editing_widget();
            }

            function begin_editing_widget() {

                // Hide spinner from cover
                api.$elements.cover.addClass('ac-widget-editing');

                // Make placeholder visible to edit
                $widget_placeholder.addClass('ac-widget-placeholder');

                // Make sure placeholder is scrolled into view
                $('html, body').animate({
                    scrollTop: $widget_placeholder.offset().top -
                    (api.$elements.toolbar.length ? api.$elements.toolbar.height() : 0)
                });
            }
        },

        /**
         * Saves a widget's settings after editing.
         *
         * @since 0.1.0
         */
        save_edit_widget: function () {

            var $widget_placeholder = $(this).closest('.postbox'),
                $widget = $('[data-id="' + $widget_placeholder.attr('id') + '"]'),
                $form = $widget_placeholder.find('.ac-widget-form'),
                widget_args = api.dash_widgets[$widget_placeholder.attr('id')];

            api.unsaved_changes = true;

            // Title
            widget_args['title'] = $widget_placeholder.find('h2.hndle input[type="text"]').val();

            // Account for no js title
            if (widget_args['no-js-title']) {
                widget_args['title'] = '<span class="hide-if-no-js">' + widget_args['title'] + '</span>' +
                    '<span class="hide-if-js">' + widget_args['no-js-title'] + '</span>';
            }

            if ($form.length) {
                $.each($form.serializeArray(), function (i, object) {
                    widget_args[object.name] = object.value;
                });
            }

            api.$elements.cover.removeClass('ac-widget-editing');
            api.reveal_cover();

            // Title
            $widget.find('h2.hndle span').first().html(widget_args['title']);

            // If AC widget, use the form values to output the widget inside
            if (widget_args['ac_id']) {

                $.post(
                    ajaxurl,
                    {
                        action: 'ac-get-widget-html',
                        widget: widget_args,
                        output: 'widget',
                        ac_nonce: data['nonce']
                    },
                    function (response) {
                        switch (response['status']) {
                            case 'success':
                                $widget.find('.inside').html(response['output']);
                                break;

                            case 'fail':
                                alert(response['error_msg']);
                                break;

                            default:
                                alert('Could not complete for unknown reasons.');
                        }

                        api.hide_cover();
                    }
                );
            } else {
                api.hide_cover();
            }

            // Show widget and get rid of placeholder
            $widget.attr('id', $widget.attr('data-id')).removeAttr('data-id').removeClass('closed').show();
            $widget_placeholder.remove();

            $(this).hide();
            $(this).siblings('[data-ac-edit]').show();
            $(this).siblings('[data-ac-cancel]').hide();
        },

        /**
         * Cancels a widget's settings from editing.
         *
         * @since 0.1.0
         */
        cancel_edit_widget: function () {

            var $widget_placeholder = $(this).closest('.postbox'),
                $widget = $('[data-id="' + $widget_placeholder.attr('id') + '"]');

            $widget.attr('id', $widget.attr('data-id')).removeAttr('data-id').removeClass('closed').show();
            $widget_placeholder.remove();

            api.hide_cover();
        },

        /**
         * Saves the interface.
         *
         * @since 0.1.0
         */
        save_interface: function () {

            var menu_item_i, menu_item, submenu_item_i, submenu_item, trashed, $trashed_widgets,
                new_menu = [], trashed_widgets = [];

            api.unsaved_changes = false;

            // Admin Menu
            for (menu_item_i = 0; menu_item_i < api.active_menu.length; menu_item_i++) {

                menu_item = {
                    slug: api.active_menu[menu_item_i].slug,
                    submenu: false
                };

                trashed = api.active_menu[menu_item_i].$item.closest('.ac-interface-adminmenu-trash').length > 0;

                if (trashed) {

                    // Trashed
                    menu_item.position = api.active_menu[menu_item_i].$item.index() +
                        api.$elements.adminmenu.find('> li').length;
                    menu_item.remove = true;

                } else {

                    // Not Trashed
                    menu_item.position = api.active_menu[menu_item_i].$item.index();
                    menu_item.remove = false;
                }

                // Submenu
                if (api.active_menu[menu_item_i].submenu) {

                    menu_item.submenu = [];
                    for (submenu_item_i = 0; submenu_item_i < api.active_menu[menu_item_i].submenu.length; submenu_item_i++) {

                        submenu_item = {
                            slug: api.active_menu[menu_item_i].submenu[submenu_item_i].slug,
                            remove: false // TODO Remove
                        };

                        // Subtract 1 because of invisible first submenu item
                        submenu_item.position = api.active_menu[menu_item_i].submenu[submenu_item_i].$item.index() - 1;
                        menu_item.submenu.push(submenu_item);
                    }
                }

                new_menu.push(menu_item);
            }

            // Dash Widgets
            // Trashed
            $trashed_widgets = api.$elements.widgets_trash.find('.postbox');
            if ($trashed_widgets.length) {
                $trashed_widgets.each(function () {

                    var widget_args = api.dash_widgets[$(this).attr('id')];

                    if (widget_args) {
                        widget_args['trashed'] = true;
                    }
                });
            }

            $.post(
                ajaxurl,
                {
                    action: 'ac-save-interface',
                    role: api.current_role,
                    menu: new_menu,
                    widgets: api.dash_widgets,
                    ac_nonce: data.nonce
                },
                function (response) {

                    switch (response['status']) {
                        case 'success':
                            alert('success');
                            break;

                        case 'fail':
                            alert(response['error_msg']);
                            break;
                    }
                }
            );
        },

        /**
         * Fires before exiting the interface.
         */
        exit_interface: function (e) {

            if (api.unsaved_changes && !confirm(data['interfaceL10n']['unsavedChangesExit'])) {
                e.preventDefault();
                return false;
            }
        },

        /**
         * Reset the interface to default values (deletes the DB option).
         *
         * @since 0.1.0
         */
        reset_interface: function () {

            $.post(
                ajaxurl,
                {
                    action: 'ac-reset-interface',
                    role: api.current_role,
                    ac_nonce: data.nonce
                },
                function (response) {

                    switch (response['status']) {
                        case 'success':
                            alert('success');
                            window.location.reload();
                            break;

                        case 'fail':
                            alert(response['error_msg']);
                            break;
                    }
                }
            );
        },

        /**
         * Reloads the interface with a different role.
         *
         * @since 0.1.0
         */
        change_role: function () {

            if (api.unsaved_changes && !confirm(data['interfaceL10n']['unsavedChangesChangeRole'])) {
                $(this).val(api.current_role);
                return;
            }

            window.location.href = window.location.pathname + "?" + $.param({
                    ac_customize: 1,
                    ac_current_role: $(this).val()
                });
        },

        /**
         * Disables anchors by return false and preventing default.
         *
         * @since 0.1.0
         */
        disable_anchor: function (e) {

            e.preventDefault();
            return false;
        },

        /**
         * Reveals the screen cover.
         *
         * @since 0.1.0
         *
         * @param min_time (int) Minimum time cover should show for in 0.1/sec
         */
        reveal_cover: function (min_time) {

            min_time = typeof min_time !== 'undefined' ? min_time : 5;
            api.cover_timer = min_time;
            countdown();

            api.$elements.cover.addClass('show');

            function countdown() {

                if (api.cover_timer > 0) {
                    api.cover_timer--;
                    setTimeout(countdown, 100);
                }
            }
        },

        /**
         * Hides the screen cover.
         *
         * @since 0.1.0
         */
        hide_cover: function () {

            if (api.cover_timer === 0) {
                api.$elements.cover.removeClass('show');
            } else {
                setTimeout(api.hide_cover, 100);
            }
        },

        /**
         * Shows the admin notice when trying to move dash widgets.
         *
         * @since 0.1.0
         *
         * @param e
         * @param ui
         */
        show_widget_move_adminnotice: function (e, ui) {

            // Don't fire when trashing
            if (ui.item.parent().hasClass('ac-interface-widgets-trash')) {
                return;
            }

            api.$elements.widget_move_adminnotice.slideDown(150).effect('shake');
            $('html, body').animate({
                scrollTop: api.$elements.widget_move_adminnotice.offset().top -
                (api.$elements.toolbar.length ? api.$elements.toolbar.height() : 0)
            });
        }
    };

    /**
     * Launches the api init on page ready.
     *
     * @since 0.1.0
     */
    function init() {
        api.init();
    }

    // Init the api.
    if (data['launch_interface']) {
        $(init);
    }
})(jQuery, AC);