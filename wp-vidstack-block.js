(function() {
    // Define element creation function
    var el = wp.element.createElement;
    // Register block type
    var registerBlockType = wp.blocks.registerBlockType;
    // Textarea control
    var TextareaControl = wp.components.TextareaControl;
    // Fragment component
    var Fragment = wp.element.Fragment;
    // Block controls toolbar
    var BlockControls = wp.editor.BlockControls;
    // Alignment toolbar
    var BlockAlignmentToolbar = wp.editor.BlockAlignmentToolbar;

    // Register Vidstack player block
    registerBlockType('vidstack/player', {
        title: 'Vidstack HTML5 Player',
        icon: 'media-video',
        category: 'common',
        attributes: {
            videoURLs: {
                type: 'string',
                source: 'text',
                selector: 'textarea',
            },
            poster: {
                type: 'string',
                source: 'text',
                selector: 'textarea',
            }
        },

        // Display and interaction in the editor
        edit: function(props) {
            // Update video links
            var updateVideoURLs = function(newVideoURLs) {
                props.setAttributes({ videoURLs: newVideoURLs });
            };

            // Update poster image
            var updatePoster = function(newPoster) {
                props.setAttributes({ poster: newPoster });
            };

            return el(Fragment, null, [
                // Block controls toolbar
                el(BlockControls, { key: 'controls' }, el(BlockAlignmentToolbar, {
                    value: props.attributes.align,
                    onChange: function(newAlign) {
                        props.setAttributes({ align: newAlign });
                    },
                    controls: ['left', 'center', 'right', 'full'],
                })),
                // Video links input field
                el(TextareaControl, {
                    label: 'Video URLs (one per line, supports mp4, m3u8, YouTube, Bilibili, etc.)',
                    value: props.attributes.videoURLs,
                    onChange: updateVideoURLs,
                    style: { height: '200px' },
                }),
                // Poster image input field
                el(TextareaControl, {
                    label: 'Poster Image URL (optional)',
                    value: props.attributes.poster,
                    onChange: updatePoster,
                    style: { height: '100px' },
                })
            ]);
        },

        // Save as shortcode for frontend
        save: function(props) {
            var shortcode = '[vidstack_player src="' + props.attributes.videoURLs.replace(/\n/g, ',') + '"';
            if (props.attributes.poster) {
                shortcode += ' poster="' + props.attributes.poster + '"';
            }
            shortcode += ']';
            return el('div', { className: 'wp-block-vidstack-player' }, shortcode);
        },
    });
})();