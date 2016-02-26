/*
 Functionality for the interface.

 @since 0.1.0
 */

(function ($, data) {
    'use strict';

    /**
     * The interface API.
     *
     * @since 0.1.0
     * @access private
     */
    var api = {

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
            api.set_menu_items_visibility();
        },

        /**
         * Gets all interface elements.
         *
         * @since 0.1.0
         */
        get_elements: function () {

            api.$elements.adminmenu = $('#adminmenu');
            api.$elements.toolbar = $('#ac-interface-toolbar');
            api.$elements.save = api.$elements.toolbar.find('[data-ac-interface-save]');
            api.$elements.select_role = api.$elements.toolbar.find('[data-ac-interface-select-role]');
        },

        /**
         * Sets up handlers.
         *
         * @since 0.1.0
         */
        setup_handlers: function () {

            api.$elements.save.click(api.save_interface);
            api.$elements.select_role.change(api.change_role);
            api.$elements.adminmenu.find('.ac-visibility').click(api.toggle_menu_item_visibility);
        },

        /**
         * Launches the interface.
         *
         * @since 0.1.0
         */
        launch_interface: function () {

            var current_item = {
                $item: null,
                original_index: null,
                new_index: null
            };

            api.current_role = data['current_role'];

            // Admin Menu
            // Make the currently open menu not open
            api.$elements.adminmenu.find('li, a')
                .removeClass('wp-has-current-submenu current')
                .addClass('wp-not-current-submenu')
                .attr('data-ac-menu-item-open', '1');

            // Add sortable
            api.$elements.adminmenu
                .sortable({
                    placeholder: 'ui-sortable-placeholder',
                    axis: 'y',
                    items: 'li:not(#collapse-menu):not(#ac-interface-launch):not(#ac-interface-adminmenu-trash)',
                    appendTo: 'parent',
                    start: menu_sort_start,
                    stop: menu_sort_stop
                })
                .on('click', 'a', api.disable_anchor)
                .find('.wp-submenu').sortable({
                    placeholder: 'ui-sortable-placeholder',
                    axis: 'y',
                    start: menu_sort_start,
                    stop: menu_sort_stop
                }
            );

            if (data.custom_menu) {
                api.active_menu = data.custom_menu;
            } else {
                api.active_menu = data.current_menu;
            }

            /**
             * Fires on start of dragging menu item.
             *
             * @since 0.1.0
             * @access private
             *
             * @var Event e
             * @var Object ui
             */
            function menu_sort_start(e, ui) {

                current_item.$item = ui.item;
                current_item.original_index = ui.item.index();
            }

            /**
             * Fires on stop of dragging menu item.
             *
             * @since 0.1.0
             * @access private
             *
             * @var Event e
             * @var Object ui
             */
            function menu_sort_stop(e, ui) {

                current_item.new_index = current_item.$item.index();

                if (current_item.$item.closest('ul').hasClass('wp-submenu')) {

                    // Sub menu
                    api.active_menu[current_item.$item.closest('.wp-has-submenu').index()]['submenu']
                        .move(current_item.original_index - 1, current_item.new_index - 1);
                } else {

                    // Menu
                    api.active_menu.move(current_item.original_index, current_item.new_index);
                }
            }
        },

        /**
         * Saves the interface.
         *
         * @since 0.1.0
         */
        save_interface: function () {

            $.post(
                ajaxurl,
                {
                    action: 'ac-save-interface',
                    role: api.current_role,
                    menu: api.active_menu,
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
         * Toggles an admin menu item visibility.
         *
         * @since 0.1.0
         */
        toggle_menu_item_visibility: function () {

            var $menu_item = $(this).closest('li'),
                menu_item_index = $menu_item.index(),
                submenu_item_index,
                submenu = $(this).closest('ul').hasClass('wp-submenu'),
                set_visibility_to = $menu_item.hasClass('ac-hidden') ? 'visible' : 'hidden';

            if (submenu) {

                submenu_item_index = menu_item_index - 1; // Account for first hidden submenu item
                menu_item_index = $menu_item.closest('li.wp-has-submenu').index();

                api.active_menu[menu_item_index].submenu[submenu_item_index].remove = set_visibility_to != 'visible';
            } else {

                api.active_menu[menu_item_index].remove = set_visibility_to != 'visible';
            }

            $menu_item.toggleClass('ac-hidden');
        },

        /**
         * Sets all menu and submenu items initial visibility classes.
         *
         * @since 0.1.0
         */
        set_menu_items_visibility: function () {

            if (!data.custom_menu) {
                return;
            }

            $.each(api.active_menu, function (menu_item_i, menu_item) {

                if (menu_item['remove']) {
                    api.$elements.adminmenu.find('> li').eq(menu_item_i).addClass('ac-hidden');
                }

                if (menu_item['submenu']) {
                    $.each(menu_item['submenu'], function (submenu_item_i, submenu_item) {

                        if (submenu_item['remove']) {
                            api.$elements.adminmenu.find('> li').eq(menu_item_i)
                                .find('li').eq(submenu_item_i + 1).addClass('ac-hidden'); // +1 because of invisible first submenu item
                        }
                    });
                }
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