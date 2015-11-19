<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Admin_Menu_Manager {

		#region Properties

		/**
		 * @var string
		 */
		protected $_plugin_slug;

		/**
		 * @since 1.0.6
		 *
		 * @var string
		 */
		private $_menu_slug;
		/**
		 * @since 1.1.3
		 *
		 * @var string
		 */
		private $_parent_slug;
		/**
		 * @since 1.1.3
		 *
		 * @var string
		 */
		private $_parent_type;
		/**
		 * @since 1.1.3
		 *
		 * @var string
		 */
		private $_menu_type;
		/**
		 * @since 1.1.3
		 *
		 * @var bool
		 */
		private $_is_top_level;
		/**
		 * @since 1.1.3
		 *
		 * @var string[]bool
		 */
		private $_default_submenu_items;
		/**
		 * @since 1.1.3
		 *
		 * @var string
		 */
		private $_first_time_path;

		#endregion Properties

		/**
		 * @var FS_Logger
		 */
		protected $_logger;

		#region Singleton

		/**
		 * @var FS_Admin_Menu_Manager[]
		 */
		private static $_instances = array();

		/**
		 * @param string $plugin_slug
		 *
		 * @return FS_Admin_Notice_Manager
		 */
		static function instance( $plugin_slug ) {
			if ( ! isset( self::$_instances[ $plugin_slug ] ) ) {
				self::$_instances[ $plugin_slug ] = new FS_Admin_Menu_Manager( $plugin_slug );
			}

			return self::$_instances[ $plugin_slug ];
		}

		protected function __construct( $plugin_slug ) {
			$this->_logger = FS_Logger::get_logger( WP_FS__SLUG . '_' . $plugin_slug . '_admin_menu', WP_FS__DEBUG_SDK, WP_FS__ECHO_DEBUG_SDK );

			$this->_plugin_slug = $plugin_slug;
		}

		#endregion Singleton

		#region Helpers

		private function get_option( &$options, $key, $default = false ) {
			return ! empty( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		private function get_bool_option( &$options, $key, $default = false ) {
			return isset( $options[ $key ] ) && is_bool( $options[ $key ] ) ? $options[ $key ] : $default;
		}

		#endregion Helpers

		/**
		 * @param array $menu
		 * @param bool  $is_addon
		 */
		function init( $menu, $is_addon = false ) {
			$this->_menu_slug = $menu['slug'];

			$this->_default_submenu_items = array();
			$this->_menu_type             = 'page';
			$this->_is_top_level          = true;
			$this->_parent_slug           = false;
			$this->_parent_type           = 'page';

			if ( ! $is_addon && isset( $menu ) ) {
				$this->_default_submenu_items = array(
					'contact' => $this->get_bool_option( $menu, 'contact', true ),
					'support' => $this->get_bool_option( $menu, 'support', true ),
					'account' => $this->get_bool_option( $menu, 'account', true ),
					'pricing' => $this->get_bool_option( $menu, 'pricing', true ),
					'addons'  => $this->get_bool_option( $menu, 'addons', true ),
				);

				$this->_menu_type = $this->get_option( $menu, 'type', 'page' );

				if ( isset( $menu['parent'] ) ) {
					$this->_parent_slug = $this->get_option( $menu['parent'], 'slug' );
					$this->_parent_type = $this->get_option( $menu['parent'], 'type', 'page' );

					// If parent's slug is different, then it's NOT a top level menu item.
					$this->_is_top_level = ( $this->_parent_slug === $this->_menu_slug );
				} else {
					/**
					 * If no parent the top level if:
					 *  - Has custom admin menu ('page')
					 *  - CPT menu type ('cpt')
					 */
					$this->_is_top_level = in_array( $this->_menu_type, array(
						'cpt',
						'page'
					) );
				}

				$this->_first_time_path = $this->get_option( $menu, 'first-path', false );
				if ( ! empty( $this->_first_time_path ) && is_string( $this->_first_time_path ) ) {
					$this->_first_time_path = admin_url( $this->_first_time_path, 'admin' );
				}
			}
		}

		/**
		 * Check if top level menu.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return bool False if submenu item.
		 */
		function is_top_level() {
			return $this->_is_top_level;
		}

		/**
		 * Check if plugin's menu item is part of a custom top level menu.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return bool
		 */
		function has_custom_parent() {
			return ! $this->_is_top_level && is_string( $this->_parent_slug );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return string
		 */
//		function slug(){
//			return $this->_menu_slug;
//		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param string $id
		 * @param bool   $default
		 *
		 * @return bool
		 */
		function is_submenu_item_visible( $id, $default = true ) {
			return $this->get_bool_option( $this->_default_submenu_items, $id, $default );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @param string $page
		 *
		 * @return string
		 */
		function get_slug( $page = '' ) {
			if ( false === strpos( $this->_menu_slug, '.php?' ) ) {
				return $this->_menu_slug . ( empty( $page ) ? '' : ( '-' . $page ) );
			} else {
				return $this->_plugin_slug . ( empty( $page ) ? '' : ( '-' . $page ) );
			}
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return string
		 */
		function get_parent_slug() {
			return $this->_parent_slug;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return string
		 */
		function get_type() {
			return $this->_menu_type;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return bool
		 */
		function is_cpt() {
			return ( 'cpt' === $this->_menu_type );
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return string
		 */
		function get_parent_type() {
			return $this->_parent_type;
		}

		/**
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return string
		 */
		function get_raw_slug() {
			return $this->_menu_slug;
		}

		/**
		 * Get plugin's original menu slug.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.3
		 *
		 * @return string
		 */
		function get_original_menu_slug() {
			if ( 'cpt' === $this->_menu_type ) {
				return add_query_arg( array(
					'post_type' => $this->_menu_slug
				), 'edit.php' );
			}

			if ( false === strpos( $this->_menu_slug, '.php?' ) ) {
				return $this->_menu_slug;
			} else {
				return $this->_plugin_slug;
			}
		}

		/**
		 * Is user on plugin's admin activation page.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.8
		 *
		 * @return bool
		 */
		function is_activation_page() {
			return isset( $_GET['page'] ) &&
			       ( ( strtolower( $this->_menu_slug ) === strtolower( $_GET['page'] ) ) ||
			         ( strtolower( $this->_plugin_slug ) === strtolower( $_GET['page'] ) ) );
		}

		#region Submenu Override

		/**
		 * Override submenu's action.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.1.0
		 *
		 * @param string   $parent_slug
		 * @param string   $menu_slug
		 * @param callable $function
		 *
		 * @return false|string If submenu exist, will return the hook name.
		 */
		function override_submenu_action( $parent_slug, $menu_slug, $function ) {
			global $submenu;

			$menu_slug   = plugin_basename( $menu_slug );
			$parent_slug = plugin_basename( $parent_slug );

			if ( ! isset( $submenu[ $parent_slug ] ) ) {
				// Parent menu not exist.
				return false;
			}

			$found_submenu_item = false;
			foreach ( $submenu[ $parent_slug ] as $submenu_item ) {
				if ( $menu_slug === $submenu_item[2] ) {
					$found_submenu_item = $submenu_item;
					break;
				}
			}

			if ( false === $found_submenu_item ) {
				// Submenu item not found.
				return false;
			}

			// Remove current function.
			$hookname = get_plugin_page_hookname( $menu_slug, $parent_slug );
			remove_all_actions( $hookname );

			// Attach new action.
			add_action( $hookname, $function );

			return $hookname;
		}

		#endregion Submenu Override

		#region Top level menu Override

		/**
		 * Find plugin's admin dashboard main menu item.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.2
		 *
		 * @return string[]|false
		 */
		private function find_top_level_menu() {
			global $menu;

			$position   = - 1;
			$found_menu = false;

			$menu_slug = $this->get_original_menu_slug();

			$hook_name = get_plugin_page_hookname( $menu_slug, '' );
			foreach ( $menu as $pos => $m ) {
				if ( $menu_slug === $m[2] ) {
					$position   = $pos;
					$found_menu = $m;
					break;
				}
			}

			if ( false === $found_menu ) {
				return false;
			}

			return array(
				'menu'      => $found_menu,
				'position'  => $position,
				'hook_name' => $hook_name
			);
		}

		/**
		 * Remove all sub-menu items.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.7
		 *
		 * @return bool If submenu with plugin's menu slug was found.
		 */
		private function remove_all_submenu_items() {
			global $submenu;

			$menu_slug = $this->get_original_menu_slug();

			if ( ! isset( $submenu[ $menu_slug ] ) ) {
				return false;
			}

			$submenu[ $menu_slug ] = array();

			return true;
		}

		/**
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.0.9
		 *
		 * @return array[string]mixed
		 */
		function remove_menu_item() {
			$this->_logger->entrance();

			// Find main menu item.
			$menu = $this->find_top_level_menu();

			if ( false === $menu ) {
				return $menu;
			}

			// Remove it with its actions.
			remove_all_actions( $menu['hook_name'] );

			// Remove all submenu items.
			$this->remove_all_submenu_items();

			return $menu;
		}

		#endregion Top level menu Override
	}