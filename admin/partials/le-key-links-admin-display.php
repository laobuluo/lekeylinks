<div class="wrap">
    <h1>关键字链接管理</h1>
    <a href="<?php echo admin_url('admin.php?page=le-key-links-add'); ?>" class="page-title-action">添加新关键字</a>
    <p>WordPress内链关键字自动替换插件，设置关键字链接。<a href="https://www.lezaiyun.com/907.html" target="_blank">插件介绍</a>（关注公众号：<span style="color: red;">老蒋朋友圈</span>）</p>
    <form method="get">
        <input type="hidden" name="page" value="le-key-links">
        <p class="search-box">
            <label class="screen-reader-text" for="keyword-search-input">搜索关键字:</label>
            <input type="search" id="keyword-search-input" name="s" value="<?php echo esc_attr($search_term); ?>">
            <input type="submit" id="search-submit" class="button" value="搜索关键字">
        </p>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column">关键字</th>
                <th scope="col" class="manage-column">链接地址</th>
                <th scope="col" class="manage-column">优先级</th>
                <th scope="col" class="manage-column">最大替换次数</th>
                <th scope="col" class="manage-column">分词方式</th>
                <th scope="col" class="manage-column">创建时间</th>
                <th scope="col" class="manage-column">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($items): ?>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo esc_html($item->keyword); ?></td>
                        <td><?php echo esc_url($item->link_url); ?></td>
                        <td><?php echo esc_html($item->priority); ?></td>
                        <td><?php echo esc_html($item->max_replace); ?></td>
                        <td><?php echo esc_html($item->word_type === 'chinese' ? '中文' : '英文'); ?></td>
                        <td><?php echo esc_html($item->created_at); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=le-key-links-add&id=' . $item->id); ?>">编辑</a> |
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=le-key-links&action=delete&id=' . $item->id), 'delete-keyword_' . $item->id); ?>" onclick="return confirm('确定要删除这个关键字吗？');">删除</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">暂无数据</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php printf('共 %s 项', number_format_i18n($total_items)); ?></span>
                <span class="pagination-links">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>
</div>
<p><img width="150" height="150" src="<?php echo plugins_url('../images/wechat.png', __FILE__); ?>" alt="扫码关注公众号" /></p>