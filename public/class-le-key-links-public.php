<?php

class Le_Key_Links_Public {
    private $plugin_name;
    private $version;
    private $table_name;
    private $options;

    public function __construct($plugin_name, $version) {
        global $wpdb;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->table_name = $wpdb->prefix . 'lekl_keywords';
        
        // 加载设置
        $this->options = get_option('le_key_links_settings', array(
            'enabled' => true,
            'bold_links' => false,
            'link_color' => '#0073aa'
        ));
    }

    public function enqueue_styles() {
        // 注册和加载基本样式
        wp_register_style(
            $this->plugin_name,
            plugin_dir_url(dirname(__FILE__)) . 'public/css/le-key-links-public.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style($this->plugin_name);

        // 添加自定义样式
        if ($this->options['enabled']) {
            $custom_css = ".lekl-link {";
            if ($this->options['bold_links']) {
                $custom_css .= "font-weight: bold !important;";
            }
            if (!empty($this->options['link_color'])) {
                $custom_css .= "color: " . esc_attr($this->options['link_color']) . " !important;";
            }
            $custom_css .= "}";
            $custom_css .= ".lekl-link:hover { color: " . esc_attr($this->options['link_color']) . " !important; }";
            
            wp_add_inline_style($this->plugin_name, $custom_css);
        }
    }

    public function enqueue_scripts() {
        // 如果需要前端脚本，在这里加载
    }

    public function replace_keywords($content) {
        // 如果功能被禁用，直接返回原内容
        if (!$this->options['enabled']) {
            return $content;
        }

        if (!is_singular() || empty($content)) {
            return $content;
        }

        global $wpdb;
        $keywords = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY priority DESC, id DESC"
        );

        if (empty($keywords)) {
            return $content;
        }

        // 创建一个临时的DOM文档来解析内容
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        
        foreach ($keywords as $keyword) {
            $count = 0;
            $max_replace = intval($keyword->max_replace);
            $pattern = $this->get_keyword_pattern($keyword->keyword, $keyword->word_type);
            
            // 获取所有文本节点，但排除<a>标签内的文本
            $text_nodes = $xpath->query('//text()[not(ancestor::a)]');
            
            foreach ($text_nodes as $node) {
                if ($count >= $max_replace) {
                    break;
                }
                
                $text = $node->nodeValue;
                
                // 检查关键字是否出现在任何已有链接的URL中
                $links = $xpath->query('//a');
                $skip = false;
                foreach ($links as $link) {
                    if (preg_match($pattern, $link->getAttribute('href'))) {
                        $skip = true;
                        break;
                    }
                }
                
                if ($skip) {
                    continue;
                }
                
                // 替换当前文本节点中的关键字
                if (preg_match($pattern, $text)) {
                    $new_text = preg_replace_callback($pattern, function($matches) use ($keyword, &$count, $max_replace, $dom) {
                        if ($count >= $max_replace) {
                            return $matches[0];
                        }
                        $count++;
                        $link = $dom->createElement('a');
                        $link->setAttribute('href', $keyword->link_url);
                        $link->setAttribute('target', '_blank');
                        $link->setAttribute('class', 'lekl-link'); // 添加自定义类
                        $link->textContent = $matches[0];
                        return $dom->saveHTML($link);
                    }, $text, $max_replace);
                    
                    // 创建一个新的文档片段来解析替换后的HTML
                    $fragment = $dom->createDocumentFragment();
                    @$fragment->appendXML($new_text);
                    
                    // 替换原始节点
                    if ($fragment->hasChildNodes()) {
                        $node->parentNode->replaceChild($fragment, $node);
                    }
                }
            }
        }
        
        // 获取处理后的HTML
        $new_content = $dom->saveHTML();
        
        // 清理可能产生的额外标签
        $new_content = preg_replace('/^<!DOCTYPE.+?>/', '', str_replace(['<html>', '</html>', '<body>', '</body>'], '', $new_content));
        
        return trim($new_content);
    }

    private function get_keyword_pattern($keyword, $word_type) {
        $keyword = preg_quote($keyword, '/');
        
        if ($word_type === 'english') {
            // 英文单词边界
            return '/\b' . $keyword . '\b/u';
        } else {
            // 中文模式
            return '/' . $keyword . '/u';
        }
    }

    private function is_in_link($text) {
        return preg_match('/<a[^>]*>.*' . preg_quote($text, '/') . '.*<\/a>/i', $text) ||
               preg_match('/<a[^>]*>/i', $text) ||
               preg_match('/<\/a>/i', $text);
    }
}