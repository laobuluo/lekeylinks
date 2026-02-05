<?php

class Le_Key_Links_Deactivator {
    public static function deactivate() {
        // 在这里添加停用插件时需要执行的操作
        // 例如：保存一些设置状态，清理临时数据等
        
        // 保存停用时间
        update_option('lekl_deactivated_time', current_time('mysql'));
    }
}