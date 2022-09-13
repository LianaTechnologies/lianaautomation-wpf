<?php
/**
 * Plugin Name:       LianaAutomation - WPForms
 * Plugin URI:        https://www.lianatech.com/solutions/websites
 * Description:       LianaAutomation for WP Forms.
 * Version:           1.0.2
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Liana Technologies
 * Author URI:        https://www.lianatech.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0-standalone.html
 * Text Domain:       lianaautomation
 * Domain Path:       /languages
 * 
 * PHP Version 7.4
 * 
 * @category Components
 * @package  WordPress
 * @author   Liana Technologies <websites@lianatech.com>
 * @author   Jaakko Pero <jaakko.pero@lianatech.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0-or-later
 * @link     https://www.lianatech.com
 */

/**
 * Include cookie handler code
 */
require_once dirname(__FILE__) . '/includes/lianaautomation-cookie.php';

/**
 * Include WPForms code
 */
require_once dirname(__FILE__) . '/includes/lianaautomation-wpforms.php';

/**
 * Include admin panel code
 */
require_once dirname(__FILE__) . '/admin/lianaautomation-admin.php';
