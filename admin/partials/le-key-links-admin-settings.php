<?php
// 检查用户权限
if (!current_user_can('manage_options')) {
    return;
}

// 获取保存的设置
$options = get_option('le_key_links_settings', array(
    'enabled' => true,
    'bold_links' => false,
    'link_color' => '#0073aa'
));
?>

<div class="wrap">
    <h1>关键字链接设置</h1>
    <p>WordPress内链关键字自动替换插件，设置关键字链接。<a href="https://www.laojiang.me/7227.html" target="_blank">插件介绍</a>（关注公众号：<span style="color: red;">老蒋朋友圈</span>）</p>

    <?php if (isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p>设置已保存。</p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('le_key_links_settings');
        do_settings_sections('le_key_links_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">启用关键字链接</th>
                <td>
                    <label>
                        <input type="checkbox" name="le_key_links_settings[enabled]" 
                               value="1" <?php checked(1, $options['enabled']); ?>>
                        启用自动关键字链接替换
                    </label>
                    <p class="description">取消勾选将暂时停用所有关键字链接替换</p>
                </td>
            </tr>

            <tr>
                <th scope="row">链接加粗</th>
                <td>
                    <label>
                        <input type="checkbox" name="le_key_links_settings[bold_links]" 
                               value="1" <?php checked(1, $options['bold_links']); ?>>
                        使关键字链接文本加粗显示
                    </label>
                    <p class="description">选中后，所有关键字链接将以粗体显示</p>
                </td>
            </tr>

            <tr>
                <th scope="row">链接颜色</th>
                <td>
                    <input type="color" name="le_key_links_settings[link_color]" 
                           value="<?php echo esc_attr($options['link_color']); ?>">
                    <p class="description">选择关键字链接的显示颜色</p>
                </td>
            </tr>
        </table>

        <?php submit_button('保存设置'); ?>
    </form>
    <p><img width="150" height="150" src="<?php echo plugins_url('../images/wechat.png', __FILE__); ?>" alt="扫码关注公众号" /></p>

</div> 