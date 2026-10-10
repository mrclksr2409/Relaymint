<?php
/**
 * Plugin Name:       Relaymint
 * Plugin URI:        https://github.com/mrclksr2409/Relaymint
 * Description:       Reliable email delivery for WordPress via SMTP or Microsoft 365 / Outlook, with email logging, smart routing, background sending and rate limiting.
 * Version:           0.4.1
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            Marcel Kaiser
 * Author URI:        https://github.com/mrclksr2409
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       relaymint
 * Domain Path:       /languages
 *
 * @package Relaymint
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RELAYMINT_VERSION', '0.4.1' );
define( 'RELAYMINT_DB_VERSION', '1' );
define( 'RELAYMINT_FILE', __FILE__ );
define( 'RELAYMINT_DIR', plugin_dir_path( __FILE__ ) );
define( 'RELAYMINT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Action Scheduler — used for background sending and housekeeping.
 * The library resolves version conflicts itself if another plugin bundles it too.
 */
require_once RELAYMINT_DIR . 'libraries/action-scheduler/action-scheduler.php';

/**
 * WP-Backend UI — shared admin design system.
 * The loader resolves version conflicts itself if another plugin bundles it too.
 */
require_once RELAYMINT_DIR . 'libraries/wp-backend-ui/wp-backend-ui.php';

require_once RELAYMINT_DIR . 'includes/class-relaymint-secrets.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-options.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-connections.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-microsoft.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-mail-data.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-installer.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-debug-log.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-logger.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-router.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-domain-check.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-rate-limiter.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-queue.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-mailer.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-interceptor.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint.php';

/**
 * Plugin Update Checker — pulls updates from GitHub.
 * Stable channel: GitHub Releases of the `main` branch. Beta channel: head of the `beta` branch.
 */
require_once RELAYMINT_DIR . 'plugin-update-checker/plugin-update-checker.php';

$relaymint_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/mrclksr2409/Relaymint/',
	__FILE__,
	'relaymint'
);
$relaymint_update_checker->setBranch( 'beta' === Relaymint_Options::value( 'misc', 'update_channel' ) ? 'beta' : 'main' );
$relaymint_update_checker->getVcsApi()->enableReleaseAssets( '/\.zip($|[?&#])/i' );

register_activation_hook( __FILE__, array( 'Relaymint_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Relaymint_Installer', 'deactivate' ) );

Relaymint::instance();
