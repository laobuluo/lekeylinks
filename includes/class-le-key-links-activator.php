<?php

class Le_Key_Links_Activator {
    public static function activate() {
        global $wpdb;
        
        try {
            $charset_collate = $wpdb->get_charset_collate();
            
            // 检查数据库权限
            error_log('LeKeyLinks: 检查数据库权限');
            try {
                $test_table = $wpdb->prefix . 'lekl_test';
                $wpdb->query("CREATE TABLE IF NOT EXISTS $test_table (id int(11) NOT NULL) $charset_collate");
                if ($wpdb->last_error) {
                    throw new Exception('数据库权限检查失败: ' . $wpdb->last_error);
                }
                $wpdb->query("DROP TABLE IF EXISTS $test_table");
            } catch (Exception $e) {
                error_log('LeKeyLinks: 数据库权限错误: ' . $e->getMessage());
                throw new Exception('数据库权限不足，请联系管理员检查WordPress数据库用户权限');
            }
            
            // 创建关键字链接表
            $table_name = $wpdb->prefix . 'lekl_keywords';
            error_log('LeKeyLinks: 准备创建表: ' . $table_name);
            
            // 检查表是否已存在
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
            error_log('LeKeyLinks: 表是否存在检查结果: ' . var_export($table_exists, true));
            
            if (!$table_exists) {
                // 使用更兼容的表创建语法
                $sql = "CREATE TABLE $table_name (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    keyword varchar(255) NOT NULL,
                    link_url varchar(255) NOT NULL,
                    priority int(11) DEFAULT 0,
                    max_replace int(11) DEFAULT 3,
                    word_type varchar(20) DEFAULT 'chinese',
                    created_at datetime,
                    updated_at datetime,
                    PRIMARY KEY  (id),
                    KEY keyword (keyword),
                    KEY priority (priority)
                ) $charset_collate;";
                
                error_log('LeKeyLinks: 执行建表SQL: ' . $sql);
                
                // 尝试直接创建表
                $result = $wpdb->query($sql);
                if ($result === false) {
                    error_log('LeKeyLinks: 直接创建表失败，错误信息: ' . $wpdb->last_error);
                    
                    // 尝试使用dbDelta
                    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                    dbDelta($sql);
                    
                    if ($wpdb->last_error) {
                        error_log('LeKeyLinks: dbDelta创建表失败，错误信息: ' . $wpdb->last_error);
                        throw new Exception('数据表创建失败。错误信息: ' . $wpdb->last_error);
                    }
                }
                
                // 验证表是否创建成功
                $table_created = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                error_log('LeKeyLinks: 表创建验证结果: ' . var_export($table_created, true));
                
                if (!$table_created) {
                    throw new Exception('数据表创建失败。错误信息: ' . $wpdb->last_error);
                }
                
                // 尝试创建触发器
                try {
                    // 先删除可能存在的旧触发器
                    $wpdb->query("DROP TRIGGER IF EXISTS {$table_name}_insert_trigger");
                    $wpdb->query("DROP TRIGGER IF EXISTS {$table_name}_update_trigger");
                    
                    // 创建新触发器
                    $wpdb->query("
                        CREATE TRIGGER {$table_name}_insert_trigger 
                        BEFORE INSERT ON {$table_name}
                        FOR EACH ROW 
                        SET NEW.created_at = NOW(), NEW.updated_at = NOW()
                    ");
                    
                    $wpdb->query("
                        CREATE TRIGGER {$table_name}_update_trigger 
                        BEFORE UPDATE ON {$table_name}
                        FOR EACH ROW 
                        SET NEW.updated_at = NOW()
                    ");
                    
                    error_log('LeKeyLinks: 触发器创建成功');
                } catch (Exception $e) {
                    error_log('LeKeyLinks: 触发器创建失败，将使用手动更新时间戳: ' . $e->getMessage());
                    // 触发器创建失败不影响主要功能，继续执行
                }
                
                // 设置默认值
                $wpdb->query("
                    UPDATE $table_name 
                    SET created_at = NOW(), updated_at = NOW() 
                    WHERE created_at IS NULL
                ");
            }
            
            // 验证表结构
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
            if (empty($columns)) {
                throw new Exception('表结构验证失败: 无法获取表结构');
            }
            
            error_log('LeKeyLinks: 表结构: ' . print_r($columns, true));
            
            // 验证必要的列是否存在
            $required_columns = ['id', 'keyword', 'link_url', 'priority', 'max_replace', 'word_type'];
            $existing_columns = array_column($columns, 'Field');
            $missing_columns = array_diff($required_columns, $existing_columns);
            
            if (!empty($missing_columns)) {
                throw new Exception('表结构不完整，缺少列: ' . implode(', ', $missing_columns));
            }
            
            // 添加版本号到选项表
            update_option('lekl_version', LEKL_VERSION);
            update_option('lekl_db_version', '1.0');
            
            error_log('LeKeyLinks: 插件激活成功');
            
        } catch (Exception $e) {
            error_log('LeKeyLinks激活错误: ' . $e->getMessage());
            error_log('LeKeyLinks错误堆栈: ' . $e->getTraceAsString());
            throw $e; // 抛出错误以便显示给用户
        }
    }
}