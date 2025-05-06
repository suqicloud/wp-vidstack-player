<?php
/**
 * Plugin Name: Vidstack HTML5 Player
 * Plugin URI: https://www.jingxialai.com/4953.html
 * Description: A WordPress video player plugin based on Vidstack, supporting mp4, m3u8, mpd videos, mp3 audio formats, and Bilibili and YouTube videos.
 * Version: 1.3
 * Author: Summer
 * License: GPL License
 * Author URI: https://www.jingxialai.com/
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Check if the current page contains the [vidstack_player] shortcode
function vidstack_should_load_assets() {
    global $post;
    if (isset($post) && has_shortcode($post->post_content, 'vidstack_player')) {
        return true;
    }
    return false;
}

// Defer loading scripts and styles
function vidstack_enqueue_assets() {
    if (vidstack_should_load_assets()) {
        wp_enqueue_style('vidstack-player-theme', 'https://cdn.vidstack.io/player/theme.css', [], null);
        wp_enqueue_style('vidstack-player-video', 'https://cdn.vidstack.io/player/video.css', [], null);
        wp_enqueue_style('vidstack-player-audio', 'https://cdn.vidstack.io/player/audio.css', [], null);
    }
}
add_action('wp_enqueue_scripts', 'vidstack_enqueue_assets');

// Register shortcode [vidstack_player]
function vidstack_player_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts([
        'src' => '', // Video or audio link
        'poster' => '', // Poster image URL (optional)
    ], $atts, 'vidstack_player');

    // Get video links list
    $video_links = explode(',', $atts['src']);
    $num_videos = count($video_links);

    // Regex to check for YouTube video link
    $youtube_pattern = '#(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/]+\/.*\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})#';
    // Regex to check for Bilibili video link
    $bilibili_pattern = '#https?:\/\/(www\.)?bilibili\.com\/video\/(BV[a-zA-Z0-9]{10})#';

    // Determine video type and generate corresponding iframe embed code
    if (preg_match($youtube_pattern, $video_links[0], $matches)) {
        // YouTube video
        $youtube_video_id = $matches[1];
        $output = "<div class=\"vidstack-player\">
                    <iframe width=\"600\" height=\"400\" src=\"https://www.youtube.com/embed/{$youtube_video_id}\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>
                </div>";
    } elseif (preg_match($bilibili_pattern, $video_links[0], $matches)) {
        // Bilibili video
        $bilibili_bv = $matches[2];
        $output = "<div class=\"vidstack-player\">
                    <iframe src=\"//player.bilibili.com/player.html?bvid={$bilibili_bv}&autoplay=0\" width=\"600\" height=\"400\" scrolling=\"no\" border=\"0\" frameborder=\"no\" framespacing=\"0\" allowfullscreen=\"true\"></iframe>
                </div>";
    } else {
        // Default Vidstack player
        ob_start(); ?>
        <div id="vidstack-player-container" class="vidstack-player-container">
            <div id="target"></div> <!-- Player target container -->
            
            <!-- Dynamically load Vidstack player module -->
            <script type="module">
                window.videoLinks = <?php echo json_encode($video_links); ?>;
                window.poster = <?php echo json_encode($atts['poster']); ?>;

                const SPANISH = {
                    'Settings': 'settings',
                    'Mute': 'mute',
                    'Play': 'play',
                    'Enter PiP': 'enter_pip',
                    'Enter Fullscreen': 'enter_fullscreen',
                    'Playback': 'playback',
                    'Loop': 'loop',
                    'Audio': 'audio',
                    'Accessibility': 'accessibility',
                    'Announcements': 'announcements',
                    'Keyboard Animations': 'keyboard_animations',
                };

                import { VidstackPlayer, VidstackPlayerLayout } from 'https://cdn.vidstack.io/player';

                document.addEventListener('DOMContentLoaded', async function() {
                    const player = await VidstackPlayer.create({
                        target: '#target',
                        src: window.videoLinks[0],
                        poster: window.poster, // Set poster image
                        layout: new VidstackPlayerLayout({
                            translations: SPANISH, // Set language translations
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
                                console.error("Invalid video link, index: ", index);
                            }
                        });
                    });

                    console.log('Vidstack Player Initialized');
                });
            </script>
        </div>

        <?php
        // Generate episode buttons
        if ($num_videos > 1) {
            echo '<div class="vidstack-episode-buttons">';
            foreach ($video_links as $index => $video_url) {
                echo "<button class='episode-button' data-index='{$index}'>Episode " . ($index + 1) . "</button>";
            }
            echo '</div>';
        }

        $output = ob_get_clean();
    }

    return $output;
}
add_shortcode('vidstack_player', 'vidstack_player_shortcode');

// Frontend episode button events
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
                            console.error("Invalid video link, index: ", index);
                        }
                    });
                });
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'vidstack_player_js_logic');

// Episode buttons
function vidstack_player_css_styles() {
    if (has_shortcode(get_post()->post_content, 'vidstack_player')) {
        ?>
        <style>
            /* Customize vds-slider component styles */
            /* Force apply background color */
            :where(.vds-slider .vds-slider-track) {
                background-color: var(--media-slider-track-bg, rgb(255 255 255 / .3)) !important
            }   
            :where(.vds-slider .vds-slider-track-fill) {
                background-color: var(--media-slider-track-fill-bg, var(--media-brand)) !important
            }       
            :where(.vds-slider-step) {
                background-color: var(--media-slider-step-color, rgb(124, 124, 124)) !important
            }
        
            /* Button styles */
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
                margin: 5px !important;
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

// Classic editor shortcut
function wp_vidstack_register_tinymce_plugin($plugin_array) {
    $plugin_array['wp_vidstack_button'] = plugin_dir_url(__FILE__) . 'wp-vidstack-tinymce.js';
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

// Gutenberg editor quick insert
function vidstack_gutenberg_register_block() {
    wp_register_script(
        'vidstack-block',
        plugins_url('wp-vidstack-block.js', __FILE__),
        array('wp-blocks', 'wp-editor', 'wp-components', 'wp-element'),
        filemtime(plugin_dir_path(__FILE__) . 'wp-vidstack-block.js')
    );

    register_block_type('vidstack/player', array(
        'editor_script' => 'vidstack-block',
    ));
}
add_action('init', 'vidstack_gutenberg_register_block');

// Uninstall plugin
register_uninstall_hook(__FILE__, 'vidstack_plugin_uninstall');
function vidstack_plugin_uninstall() {
    // Remove all episode button related scripts
    remove_action('wp_footer', 'vidstack_player_js_logic');

    // Remove all frontend styles
    remove_action('wp_head', 'vidstack_player_css_styles');

    // Remove all frontend scripts
    remove_action('wp_enqueue_scripts', 'vidstack_enqueue_assets');

    // Remove shortcode
    remove_shortcode('vidstack_player');

    // Remove classic editor button and related scripts
    remove_filter('mce_external_plugins', 'wp_vidstack_register_tinymce_plugin');
    remove_filter('mce_buttons', 'wp_vidstack_add_tinymce_button');

    // Remove Gutenberg editor related code
    unregister_block_type('vidstack/player');
}
?>