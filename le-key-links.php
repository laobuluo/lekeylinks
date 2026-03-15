<?php
/**
 * Plugin Name: LeKeyLinks
 * Plugin URI:  https://www.laojiang.me/7227.html
 * Description: WordPress内链关键字自动替换插件，可以自动替换文章中关键字为链接，支持中文和英文，支持优先级，支持最大替换次数。公众号：<span style="color: red;">老蒋朋友圈</span>
 * Version: 1.0.1
 * Author: 老蒋和他的小伙伴
 * Author URI: https://www.laojiang.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lekeylinks
 */

if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('LEKL_PLUGIN_FILE', __FILE__);
define('LEKL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LEKL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LEKL_VERSION', '1.0.1');

// 包含必要的文件
require_once LEKL_PLUGIN_DIR . 'includes/class-le-key-links.php';
require_once LEKL_PLUGIN_DIR . 'includes/class-le-key-links-activator.php';
require_once LEKL_PLUGIN_DIR . 'includes/class-le-key-links-deactivator.php';

// 激活插件时的回调
register_activation_hook(__FILE__, function() {
    try {
        require_once LEKL_PLUGIN_DIR . 'includes/class-le-key-links-activator.php';
        Le_Key_Links_Activator::activate();
    } catch (Exception $e) {
        wp_die('插件激活失败：' . $e->getMessage() . '<br><br><a href="' . admin_url('plugins.php') . '">返回</a>');
    }
});

// 停用插件时的回调
register_deactivation_hook(__FILE__, array('Le_Key_Links_Deactivator', 'deactivate'));

// 卸载插件时的回调
register_uninstall_hook(__FILE__, array('Le_Key_Links', 'uninstall'));

// 初始化插件
function run_le_key_links() {
    $plugin = new Le_Key_Links();
    $plugin->run();
}

run_le_key_links();