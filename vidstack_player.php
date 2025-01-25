<?php
/**
 * Plugin Name: Vidstack HTML5播放器
 * Plugin URI: https://www.jingxialai.com/4953.html
 * Description: 基于Vidstack的WordPress视频播放器插件，支持mp4、m3u8、mpd视频、mp3等音频格式以及B站和youtube视频.
 * Version: 1.2
 * Author: Summer
 * License: GPL License
 * Author URI: https://www.jingxialai.com/
 */

// 防止直接访问文件
if (!defined('ABSPATH')) {
    exit;
}
// 检查当前页面是否包含 [vidstack_player] 短代码
function vidstack_should_load_assets() {
    global $post;
    if (isset($post) && has_shortcode($post->post_content, 'vidstack_player')) {
        return true;
    }
    return false;
}

// 延迟加载脚本和样式
function vidstack_enqueue_assets() {
    if (vidstack_should_load_assets()) {
        wp_enqueue_style('vidstack-player-theme', 'https://cdn.vidstack.io/player/theme.css', [], null);
        wp_enqueue_style('vidstack-player-video', 'https://cdn.vidstack.io/player/video.css', [], null);
        wp_enqueue_style('vidstack-player-audio', 'https://cdn.vidstack.io/player/audio.css', [], null);
    }
}
add_action('wp_enqueue_scripts', 'vidstack_enqueue_assets');

// 注册短代码[vidstack_player]
function vidstack_player_shortcode($atts) {
    // 提取短代码属性
    $atts = shortcode_atts([
        'src' => '', // 视频或音频链接
        'poster' => '', // 封面图地址（可选）
    ], $atts, 'vidstack_player');

    // 获取视频链接列表
    $video_links = explode(',', $atts['src']);
    $num_videos = count($video_links);

    // 正则判断是否为YouTube视频链接
    $youtube_pattern = '#(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/]+\/.*\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})#';
    // 正则判断是否为Bilibili视频链接
    $bilibili_pattern = '#https?:\/\/(www\.)?bilibili\.com\/video\/(BV[a-zA-Z0-9]{10})#';

    // 判断视频类型，生成对应的iframe嵌入代码
    if (preg_match($youtube_pattern, $video_links[0], $matches)) {
        // YouTube视频
        $youtube_video_id = $matches[1];
        $output = "<div class=\"vidstack-player\">
                    <iframe width=\"600\" height=\"400\" src=\"https://www.youtube.com/embed/{$youtube_video_id}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>
                </div>";
    } elseif (preg_match($bilibili_pattern, $video_links[0], $matches)) {
        // Bilibili视频
        $bilibili_bv = $matches[2];
        $output = "<div class=\"vidstack-player\">
                    <iframe src=\"//player.bilibili.com/player.html?bvid={$bilibili_bv}&autoplay=0\" width=\"600\" height=\"400\" scrolling=\"no\" border=\"0\" frameborder=\"no\" framespacing=\"0\" allowfullscreen=\"true\"></iframe>
                </div>";
    } else {
        // 默认Vidstack播放器
        ob_start(); ?>
        <div id="vidstack-player-container" class="vidstack-player-container">
            <div id="target"></div> <!-- 播放器目标容器 -->
            
            <!-- 动态加载Vidstack播放器模块 -->
            <script type="module">
                window.videoLinks = <?php echo json_encode($video_links); ?>;
                window.poster = <?php echo json_encode($atts['poster']); ?>;

                const SPANISH = {
                'Settings': '设置',
                'Mute': '音量',
                'Play': '播放',
                'Enter PiP': '画中画',
                'Enter Fullscreen': '全屏',
                'Playback': '重播',
                'Loop': '循环',
                'Audio': '音频',
                'Accessibility': '无障碍环境',
                'Announcements': '通知',
                'Keyboard Animations': '键盘动态效果',
            };

                import { VidstackPlayer, VidstackPlayerLayout } from 'https://cdn.vidstack.io/player';

                document.addEventListener('DOMContentLoaded', async function() {
                    const player = await VidstackPlayer.create({
                        target: '#target',
                        src: window.videoLinks[0],
                        poster: window.poster, // 设置封面图
                        layout: new VidstackPlayerLayout({
                        translations: SPANISH, // 设置语言翻译
                    }),
                        autoplay: true,
                    });


                    player.addEventListener('ended', () => {
                        const currentIndex = window.videoLinks.indexOf(player.src);
                        const nextIndex = currentIndex + 1 < window.videoLinks.length ? currentIndex + 1 : null;
                        if (nextIndex !== null) {
                            player.src = window.videoLinks[nextIndex];
                            player.play();
                        }
                    });

                    const episodeButtons = document.querySelectorAll('.episode-button');
                    episodeButtons.forEach(button => {
                        button.addEventListener('click', function() {
                            const index = button.getAttribute('data-index');
                            if (window.videoLinks[index]) {
                                player.src = window.videoLinks[index];
                                player.play();
                            } else {
                                console.error("视频链接无效，索引: ", index);
                            }
                        });
                    });

                    console.log('Vidstack Player Initialized');
                });
            </script>
        </div>

        <?php
        // 生成选集按钮
        if ($num_videos > 1) {
            echo '<div class="vidstack-episode-buttons">';
            foreach ($video_links as $index => $video_url) {
                echo "<button class='episode-button' data-index='{$index}'>第" . ($index + 1) . "集</button>";
            }
            echo '</div>';
        }

        $output = ob_get_clean();
    }

    return $output;
}
add_shortcode('vidstack_player', 'vidstack_player_shortcode');

// 前台选集按钮事件
function vidstack_player_js_logic() {
    if (has_shortcode(get_post()->post_content, 'vidstack_player')) {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const episodeButtons = document.querySelectorAll('.episode-button');
                episodeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const index = button.getAttribute('data-index');
                        const player = document.querySelector('#target video');
                        const videoLinks = window.videoLinks;

                        if (videoLinks[index]) {
                            player.src = videoLinks[index];
                            player.play();
                        } else {
                            console.error("视频链接无效，索引: ", index);
                        }
                    });
                });
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'vidstack_player_js_logic');

// 选集按钮
function vidstack_player_css_styles() {
    if (has_shortcode(get_post()->post_content, 'vidstack_player')) {
        ?>
        <style>
            /* 定制vds-slider组件的样式 */
            /* 强制应用背景颜色 */
            :where(.vds-slider .vds-slider-track) {
                background-color: var(--media-slider-track-bg, rgb(255 255 255 / .3)) !important
            }   
            :where(.vds-slider .vds-slider-track-fill) {
                background-color: var(--media-slider-track-fill-bg, var(--media-brand)) !important
            }       
            :where(.vds-slider-step) {
                background-color: var(--media-slider-step-color, rgb(124, 124, 124)) !important
            }
        
            /* 按钮样式 */
            .vidstack-episode-buttons {
                text-align: center;
                margin-top: 20px;
            }

            .vidstack-episode-buttons .episode-button {
                background-color: #007bff;
                color: white;
                border: 2px solid #007bff;
                border-radius: 5px;
                padding: 10px 20px;
                font-size: 16px;
                cursor: pointer;
                transition: all 0.3s ease;
                margin: 5px;
            }

            .vidstack-episode-buttons .episode-button:hover {
                background-color: #0056b3;
                border-color: #0056b3;
            }

            .vidstack-episode-buttons .episode-button:active {
                background-color: #003f7f;
                border-color: #003f7f;
            }
        </style>
        <?php
    }
}
add_action('wp_head', 'vidstack_player_css_styles');

// 经典编辑器快捷键
function wp_vidstack_register_tinymce_plugin($plugin_array) {
    $plugin_array['wp_vidstack_button'] = plugin_dir_url(__FILE__) . 'assets/wp-vidstack-tinymce.js';
    return $plugin_array;
}

function wp_vidstack_add_tinymce_button($buttons) {
    array_push($buttons, 'wp_vidstack_button');
    return $buttons;
}

function wp_vidstack_add_tinymce_plugin() {
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
        return;
    }

    if (get_user_option('rich_editing') !== 'true') {
        return;
    }

    add_filter('mce_external_plugins', 'wp_vidstack_register_tinymce_plugin');
    add_filter('mce_buttons', 'wp_vidstack_add_tinymce_button');
}

add_action('init', 'wp_vidstack_add_tinymce_plugin');
