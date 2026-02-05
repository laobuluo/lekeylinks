<?php

class Le_Key_Links_Admin {
    private $plugin_name;
    private $version;
    private $table_name;

    public function __construct($plugin_name, $version) {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->table_name = $wpdb->prefix . 'lekl_keywords';
        
        // 注册设置
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, LEKL_PLUGIN_URL . 'admin/css/le-key-links-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, LEKL_PLUGIN_URL . 'admin/js/le-key-links-admin.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'lekl_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lekl_nonce')
        ));
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            '关键字链接管理', // 页面标题
            '关键字链接', // 菜单标题
            'manage_options', // 权限
            'le-key-links', // 菜单slug
            array($this, 'display_plugin_admin_page'), // 回调函数
            'dashicons-admin-links' // 图标
        );

        add_submenu_page(
            'le-key-links',
            '添加关键字',
            '添加关键字',
            'manage_options',
            'le-key-links-add',
            array($this, 'display_plugin_add_page')
        );

        // 添加设置子菜单
        add_submenu_page(
            'le-key-links',
            '设置',
            '设置',
            'manage_options',
            'le-key-links-settings',
            array($this, 'display_plugin_settings_page')
        );
    }

    public function display_plugin_admin_page() {
        global $wpdb;
        
        try {
            // 检查表是否存在
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") === $this->table_name;
            
            if (!$table_exists) {
                // 如果表不存在，尝试重新创建
                require_once LEKL_PLUGIN_DIR . 'includes/class-le-key-links-activator.php';
                Le_Key_Links_Activator::activate();
                
                // 再次检查表是否创建成功
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") === $this->table_name;
                
                if (!$table_exists) {
                    throw new Exception('数据表不存在，且无法创建');
                }
            }
            
            // 处理删除操作
            if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
                $this->delete_keyword($_GET['id']);
            }

            // 获取当前页码和搜索关键字
            $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
            $per_page = 10;

            // 构建查询
            $where = '';
            if (!empty($search_term)) {
                $where = $wpdb->prepare(" WHERE keyword LIKE %s", '%' . $wpdb->esc_like($search_term) . '%');
            }

            // 获取总记录数
            $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $this->table_name" . $where);
            
            if ($total_items === null && $wpdb->last_error) {
                throw new Exception('无法获取记录数: ' . $wpdb->last_error);
            }

            $total_pages = ceil($total_items / $per_page);

            // 获取数据
            $offset = ($current_page - 1) * $per_page;
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $this->table_name $where ORDER BY priority DESC, id DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ));
            
            if ($items === null && $wpdb->last_error) {
                throw new Exception('无法获取数据: ' . $wpdb->last_error);
            }

            // 显示错误信息（如果有）
            if (isset($_GET['error'])) {
                echo '<div class="error"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
            }

            // 加载列表页面模板
            require_once LEKL_PLUGIN_DIR . 'admin/partials/le-key-links-admin-display.php';
            
        } catch (Exception $e) {
            // 记录错误
            error_log('LeKeyLinks错误: ' . $e->getMessage());
            
            // 显示错误消息
            echo '<div class="error"><p>发生错误: ' . esc_html($e->getMessage()) . '</p></div>';
            
            // 显示调试信息（仅在调试模式下）
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<div class="error"><p>调试信息:<br>';
                echo '表名: ' . esc_html($this->table_name) . '<br>';
                echo '最后的SQL错误: ' . esc_html($wpdb->last_error) . '<br>';
                echo '最后的SQL查询: ' . esc_html($wpdb->last_query) . '<br>';
                echo '</p></div>';
            }
        }
    }

    public function display_plugin_add_page() {
        $message = '';
        $keyword_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $keyword_data = array(
            'keyword' => '',
            'link_url' => '',
            'priority' => 0,
            'max_replace' => 3,
            'word_type' => 'chinese'
        );

        if ($keyword_id > 0) {
            global $wpdb;
            $keyword_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $this->table_name WHERE id = %d",
                $keyword_id
            ), ARRAY_A);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_keyword'])) {
            $message = $this->save_keyword($_POST, $keyword_id);
        }

        // 加载添加/编辑页面模板
        require_once LEKL_PLUGIN_DIR . 'admin/partials/le-key-links-admin-add.php';
    }

    private function save_keyword($data, $keyword_id = 0) {
        error_log('LeKeyLinks: 开始保存关键字');
        
        if (!isset($data['lekl_nonce']) || !wp_verify_nonce($data['lekl_nonce'], 'lekl_save_keyword')) {
            error_log('LeKeyLinks: 安全验证失败');
            return '安全验证失败';
        }

        $keyword = sanitize_text_field($data['keyword']);
        $link_url = esc_url_raw($data['link_url']);
        $priority = intval($data['priority']);
        $max_replace = intval($data['max_replace']);
        $word_type = sanitize_text_field($data['word_type']);

        error_log('LeKeyLinks: 处理的数据 - 关键字: ' . $keyword . ', 链接: ' . $link_url);

        if (empty($keyword) || empty($link_url)) {
            error_log('LeKeyLinks: 关键字或链接为空');
            return '关键字和链接地址不能为空';
        }

        global $wpdb;
        $data = array(
            'keyword' => $keyword,
            'link_url' => $link_url,
            'priority' => $priority,
            'max_replace' => $max_replace,
            'word_type' => $word_type
        );

        if ($keyword_id > 0) {
            // 更新现有记录
            $result = $wpdb->update(
                $this->table_name,
                $data,
                array('id' => $keyword_id),
                array('%s', '%s', '%d', '%d', '%s'),
                array('%d')
            );
            error_log('LeKeyLinks: 更新结果: ' . ($result !== false ? '成功' : '失败'));
            if ($wpdb->last_error) {
                error_log('LeKeyLinks: 更新错误: ' . $wpdb->last_error);
            }
            return '关键字更新成功';
        } else {
            // 插入新记录
            $result = $wpdb->insert(
                $this->table_name,
                $data,
                array('%s', '%s', '%d', '%d', '%s')
            );
            error_log('LeKeyLinks: 插入结果: ' . ($result !== false ? '成功' : '失败'));
            if ($wpdb->last_error) {
                error_log('LeKeyLinks: 插入错误: ' . $wpdb->last_error);
            }
            return '关键字添加成功';
        }
    }

    private function delete_keyword($id) {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }

    public function display_plugin_settings_page() {
        require_once LEKL_PLUGIN_DIR . 'admin/partials/le-key-links-admin-settings.php';
    }

    public function register_settings() {
        register_setting(
            'le_key_links_settings', // 选项组
            'le_key_links_settings', // 选项名称
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings')
            )
        );
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        
        // 启用/禁用选项
        $sanitized['enabled'] = isset($input['enabled']) ? true : false;
        
        // 加粗选项
        $sanitized['bold_links'] = isset($input['bold_links']) ? true : false;
        
        // 链接颜色
        $sanitized['link_color'] = sanitize_hex_color($input['link_color']);
        if (empty($sanitized['link_color'])) {
            $sanitized['link_color'] = '#0073aa'; // 默认颜色
        }
        
        return $sanitized;
    }
}