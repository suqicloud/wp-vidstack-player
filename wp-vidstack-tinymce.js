(function() {
    tinymce.PluginManager.add('wp_vidstack_button', function(editor, url) {
        editor.addButton('wp_vidstack_button', {
            text: 'Vidstack Video',
            icon: false,
            onclick: function() {
                editor.windowManager.open({
                    title: 'Vidstack Video',
                    body: [
                        {
                            type: 'textbox',
                            name: 'vidstack_urls',
                            label: 'Video URLs (one per line)',
                            multiline: true,
                            minWidth: 300,
                            minHeight: 100
                        },
                        {
                            type: 'textbox',
                            name: 'vidstack_url',
                            label: 'Poster Image URL (optional)',
                            value: '', // Initial value is empty
                            minWidth: 300
                        },
                        {
                            type: 'label',
                            text: 'Please enter the full video URLs above, one per line.'
                        },
                        {
                            type: 'label',
                            text: 'If a poster image is needed, enter its URL; leave blank to omit the poster.'
                        }
                    ],
                    onsubmit: function(e) {
                        var videoUrls = e.data.vidstack_urls.split('\n').map(function(url) {
                            return url.trim();
                        }).join(',');

                        // Get poster image URL, add poster parameter if provided
                        var posterUrl = e.data.vidstack_url.trim();
                        var shortcode = '[vidstack_player src="' + videoUrls + '"';

                        // If poster URL is not empty, add poster parameter
                        if (posterUrl) {
                            shortcode += ' poster="' + posterUrl + '"';
                        }

                        shortcode += ']';

                        // Insert content
                        editor.insertContent(shortcode);
                    }
                });
            }
        });
    });
})();