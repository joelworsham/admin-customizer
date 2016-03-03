/*
 Functionality for the interface.

 @since 0.1.0
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
            api.$elements.adminmenu_trash = $('#ac-interface-adminmenu-trash');
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

            // Admin Menu
            // Make the currently open menu not open
            api.$elements.adminmenu.find('li, a')
                .removeClass('wp-has-current-submenu current')
                .addClass('wp-not-current-submenu')
                .attr('data-ac-menu-item-open', '1');

            // Remove the collapse button
            $('#collapse-menu').remove();

            api.active_menu = data['current_menu'];
            console.log(api.active_menu);

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

            console.log(api.active_menu);

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
                    connectWith: '#ac-interface-adminmenu-trash'
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
                connectWith: '#adminmenu'
            });
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

                trashed = api.active_menu[menu_item_i].$item.closest('#ac-interface-adminmenu-trash').length > 0;

                if (trashed) {

                    // Trashed
                    menu_item.position = api.active_menu[menu_item_i].$item.index() +
                        api.$elements.adminmenu.find('> li').length ;
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
                        console.log(submenu_item.slug);
                        console.log(api.active_menu[menu_item_i].submenu[submenu_item_i].$item);

                        menu_item.submenu.push(submenu_item);
                    }
                }

                new_menu.push(menu_item);
            }

            console.log(new_menu);

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