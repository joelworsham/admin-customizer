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
         * The currently being modified role.
         *
         * @since 0.1.0
         *
         * @var string|null
         */
        current_role: null,

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
            api.$elements.adminmenu_trash = $('.ac-interface-adminmenu-trash');
            api.$elements.widgets_toolbar = $('#ac-interface-widgets-toolbar');
            api.$elements.widgets_trash = api.$elements.widgets_toolbar.find('.ac-interface-widgets-trash');
            api.$elements.widgets_new = api.$elements.widgets_toolbar.find('.ac-interface-widgets-new');
            api.$elements.dashwidgets = $('#dashboard-widgets').find('.meta-box-sortables');
            api.$elements.toolbar = $('#ac-interface-toolbar');
            api.$elements.save = api.$elements.toolbar.find('[data-ac-interface-save]');
            api.$elements.reset = api.$elements.toolbar.find('[data-ac-interface-reset]');
            api.$elements.select_role = api.$elements.toolbar.find('[data-ac-interface-select-role]');
        },

        /**
         * Sets up handlers.
         *
         * @since 0.1.0
         */
        setup_handlers: function () {

            api.$elements.save.click(api.save_interface);
            api.$elements.reset.click(api.reset_interface);
            api.$elements.select_role.change(api.change_role);
        },

        /**
         * Launches the interface.
         *
         * @since 0.1.0
         */
        launch_interface: function () {

            api.current_role = data['current_role'];

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
                    connectWith: '.ac-interface-adminmenu-trash'
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
             * Fires when the adminmenu trash receives an item.
             *
             * @since 0.1.0
             */
            function menu_trash_receive(e, ui) {
                api.$elements.adminmenu_trash.removeClass('empty');
            }

            /**
             * Fires when the adminmenu trash removes an item.
             *
             * @since 0.1.0
             */
            function menu_trash_remove(e, ui) {

                if (!api.$elements.adminmenu_trash.find('> li').length) {
                    api.$elements.adminmenu_trash.addClass('empty');
                }
            }

            // WIDGETS

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
                remove: add_new_widget
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

                ui.item.addClass('closed');

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

                ui.item.removeClass('closed');

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
                    $receiver = ui.item.closest('.ui-sortable'),
                    $form = $widget.find('.ac-widget-form'),
                    new_index = $widget.index(),
                    args = {};

                // TODO Handle checkboxes/radios
                if ($form.length) {
                    $.each($form.serializeArray(), function (i, object) {
                        args[object.name] = object.value;
                    });
                }

                args.title = $widget.find('[name="ac_widget_title"]').val();

                e.preventDefault();

                $.post(
                    ajaxurl,
                    {
                        action: 'ac-add-widget',
                        widget: args,
                        ac_nonce: data.nonce
                    },
                    function (response) {

                        var $item_at_index,
                            $new_widget = $widget.clone(true);

                        if (response['status'] == 'success') {

                            // Reset widget form
                            $widget.find('.ac-widget-form-custom').html(response['form']);
                            $widget.find('[name="ac_widget_title"]').val('');
                            $widget.addClass('closed');

                            // Prepare new widget
                            $new_widget.find('.ac-widget-inside').html(response['output']);
                            $new_widget.find('.ac-widget-title-text').html(args.title ? args.title : args.widget_name);
                            $new_widget.find('.ac-widget-form').remove();
                            $new_widget.find('.ac-widget-title-input').remove();
                            $new_widget.removeClass('closed');

                            // Place new widget into dash widgets
                            $item_at_index = $receiver.find('> div.postbox:eq(' + new_index + ')');
                            if ($item_at_index.length) {
                                $item_at_index.before($new_widget);
                            } else {
                                $receiver.append($new_widget);
                            }

                        } else if (response['status'] == 'fail') {

                            if (response['error_msg']) {
                                alert(response['error_msg']);
                            }

                        } else {
                            alert('Could not complete for unknown reasons');
                        }
                    }
                );
            }
        },

        /**
         * Saves the interface.
         *
         * @since 0.1.0
         */
        save_interface: function () {

            var menu_item_i, menu_item, submenu_item_i, submenu_item, trashed,
                new_menu = [];

            // TODO Submenu

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

            $.post(
                ajaxurl,
                {
                    action: 'ac-save-interface',
                    role: api.current_role,
                    menu: new_menu,
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