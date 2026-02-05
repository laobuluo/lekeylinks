<div class="wrap">
    <h1><?php echo $keyword_id ? '编辑关键字' : '添加新关键字'; ?></h1>
    <p>WordPress内链关键字自动替换插件，设置关键字链接。<a href="https://www.lezaiyun.com/907.html" target="_blank">插件介绍</a>（关注公众号：<span style="color: red;">老蒋朋友圈</span>）</p>
    <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('lekl_save_keyword', 'lekl_nonce'); ?>

        <table class="form-table">
            <tr>
                <th scope="row"><label for="keyword">关键字</label></th>
                <td>
                    <input name="keyword" type="text" id="keyword" value="<?php echo esc_attr($keyword_data['keyword']); ?>" class="regular-text">
                    <p class="description">输入需要替换的关键字</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="link_url">链接地址</label></th>
                <td>
                    <input name="link_url" type="url" id="link_url" value="<?php echo esc_url($keyword_data['link_url']); ?>" class="regular-text">
                    <p class="description">输入关键字要链接到的URL地址</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="priority">优先级</label></th>
                <td>
                    <input name="priority" type="number" id="priority" value="<?php echo esc_attr($keyword_data['priority']); ?>" class="small-text">
                    <p class="description">数字越大优先级越高，当多个关键字匹配时优先使用优先级高的</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="max_replace">最大替换次数</label></th>
                <td>
                    <input name="max_replace" type="number" id="max_replace" value="<?php echo esc_attr($keyword_data['max_replace']); ?>" class="small-text">
                    <p class="description">在单篇文章中最多替换的次数，默认为3次</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="word_type">分词方式</label></th>
                <td>
                    <select name="word_type" id="word_type">
                        <option value="chinese" <?php selected($keyword_data['word_type'], 'chinese'); ?>>中文</option>
                        <option value="english" <?php selected($keyword_data['word_type'], 'english'); ?>>英文</option>
                    </select>
                    <p class="description">选择关键字的分词方式</p>
                </td>
            </tr>
        </table>

        <?php submit_button('保存关键字', 'primary', 'submit_keyword'); ?>
    </form>
    <p><img width="150" height="150" src="<?php echo plugins_url('../images/wechat.png', __FILE__); ?>" alt="扫码关注公众号" /></p>
</div>