<?php

class Le_Key_Links {
    protected $version;
    protected $plugin_name;

    public function __construct() {
        $this->version = LEKL_VERSION;
        $this->plugin_name = 'le-key-links';
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once LEKL_PLUGIN_DIR . 'admin/class-le-key-links-admin.php';
        require_once LEKL_PLUGIN_DIR . 'public/class-le-key-links-public.php';
    }

    private function define_admin_hooks() {
        $plugin_admin = new Le_Key_Links_Admin($this->get_plugin_name(), $this->get_version());

        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($plugin_admin, 'enqueue_scripts'));
        add_action('admin_menu', array($plugin_admin, 'add_plugin_admin_menu'));
    }

    private function define_public_hooks() {
        $plugin_public = new Le_Key_Links_Public($this->get_plugin_name(), $this->get_version());

        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($plugin_public, 'enqueue_scripts'));
        add_filter('the_content', array($plugin_public, 'replace_keywords'));
    }

    public function run() {
        // 插件运行时的主要逻辑
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }

    public static function uninstall() {
        global $wpdb;
        
        // 删除数据库表
        $table_name = $wpdb->prefix . 'lekl_keywords';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // 删除选项
        delete_option('lekl_version');
        delete_option('lekl_deactivated_time');
    }
}