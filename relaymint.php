<?php
/**
 * Plugin Name:       Relaymint
 * Plugin URI:        https://github.com/mrclksr2409/Relaymint
 * Description:       Reliable SMTP delivery for WordPress with email logging, smart routing, background sending and rate limiting.
 * Version:           1.0.0
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Author:            Marcel Kaiser
 * Author URI:        https://marcel-kaiser.de
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

define( 'RELAYMINT_VERSION', '1.0.0' );
define( 'RELAYMINT_DB_VERSION', '1' );
define( 'RELAYMINT_FILE', __FILE__ );
define( 'RELAYMINT_DIR', plugin_dir_path( __FILE__ ) );
define( 'RELAYMINT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin Update Checker — pulls updates from GitHub Releases.
 */
require_once RELAYMINT_DIR . 'plugin-update-checker/plugin-update-checker.php';

$relaymint_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/mrclksr2409/Relaymint/',
	__FILE__,
	'relaymint'
);
$relaymint_update_checker->setBranch( 'main' );
$relaymint_update_checker->getVcsApi()->enableReleaseAssets( '/\.zip($|[?&#])/i' );

/**
 * Action Scheduler — used for background sending and housekeeping.
 * The library resolves version conflicts itself if another plugin bundles it too.
 */
require_once RELAYMINT_DIR . 'libraries/action-scheduler/action-scheduler.php';

require_once RELAYMINT_DIR . 'includes/class-relaymint-secrets.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-options.php';
require_once RELAYMINT_DIR . 'includes/class-relaymint-connections.php';
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

register_activation_hook( __FILE__, array( 'Relaymint_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Relaymint_Installer', 'deactivate' ) );

Relaymint::instance();
