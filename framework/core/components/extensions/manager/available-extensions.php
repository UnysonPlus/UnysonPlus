<?php declare(strict_types=1);

if (!defined('FW')) {
    die('Forbidden');
}

/**
 * PHP Version: 7.4 or higher
 */

$thumbnails_uri = fw_get_framework_directory_uri('/core/components/extensions/manager/static/img/thumbnails');
$github_account = 'UnysonPlus';

$extensions = [
    'page-builder' => [
        'display'     => true,
        'parent'      => 'shortcodes',
        'name'        => __('Page Builder', 'fw'),
        'description' => __("Let's you easily build countless pages with the help of the drag and drop visual page builder that comes with a lot of already created shortcodes.", 'fw'),
        'thumbnail'   => $thumbnails_uri . '/page-builder.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-PageBuilder-Extension',
                //'remote' => 'https://github.com/UnysonPlus/UnysonPlus-PageBuilder-Extension/archive/refs/tags/1.6.20.zip'
            ],
        ],
    ],

    'wp-shortcodes' => [
        'display'     => true,
        'parent'      => 'shortcodes',
        'name'        => __('WordPress Shortcodes', 'fw'),
        'description' => __('Lets you insert Unyson shortcodes inside any wp-editor', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/wp-shortcodes.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-WP-Shortcodes-Extension',
            ],
        ],
    ],

    'backups' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Backup & Demo Content', 'fw'),
        'description' => __('This extension lets you create an automated backup schedule, import demo content or even create a demo content archive for migration purposes.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/backups.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Backups-Extension',
            ],
        ],
    ],

    'asset-optimizer' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Asset Optimizer', 'fw'),
        'description' => __('Combines enqueued frontend assets into single cached files to reduce HTTP requests. Currently merges CSS stylesheets', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/asset-optimizer.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Asset-Optimizer-Extension',
            ],
        ],
    ],

    'site-converter' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Site Converter', 'fw'),
        'description' => __('Bring an AI-generated website into WordPress (Unyson+ → Convert): import the source site\'s images and Styling Presets from the artifacts an agent emits per the conversion contract.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/site-converter.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Site-Converter-Extension',
            ],
        ],
    ],

    'newsletter-crm' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Newsletter / Subscriber CRM', 'fw'),
        'description' => __('Collect and manage subscribers, organise them into lists, tags and saved segments, then compose and send campaigns with a drag-and-drop email builder that compiles to HTML which survives Outlook and Gmail. Double opt-in, one-click unsubscribe, CSV import/export and a REST endpoint included.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/newsletter-crm.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Newsletter-CRM-Extension',
            ],
        ],
    ],

    'animated-icons' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Animated Icons', 'fw'),
        'description' => __('Adds an "Animated" tab to the icon picker, so any icon option can use Lottie, Rive, animated SVG or animated raster alongside font glyphs and images. Each technology is toggled per-site, and icons already using an animation keep rendering even if the extension is later turned off.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/animated-icons.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Animated-Icons-Extension',
            ],
        ],
    ],

    'animation-engine' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Animation Engine', 'fw'),
        'description' => __('The home for animation capabilities in UnysonPlus, adding a Site-wide UX section to Theme Settings. Its first module is WebGL — a real-time refractive glass blob, liquid metal, distorted sphere or particle field rendered with Three.js. Further modules plug in over time: Scroll Motion, Hover, Cursor, Text Effects, Backgrounds and Page Transitions.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/animation-engine.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Animation-Engine-Extension',
            ],
        ],
    ],

    'chat' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Chat', 'fw'),
        'description' => __('A floating, multi-channel contact button rendered site-wide — let visitors reach you on WhatsApp, Messenger, Telegram, SMS, Email or any custom link. One channel makes it a direct deep-link; several turn it into a launcher the visitor picks from.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/chat.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Chat-Extension',
            ],
        ],
    ],

    'gutenberg' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Gutenberg Blocks', 'fw'),
        'description' => __('Exposes Unyson+ elements as native Gutenberg blocks, for people who prefer the block editor but still want the Unyson+ options framework. Blocks are server-rendered by the same code as the page builder, so the front-end output is identical.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/gutenberg.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Gutenberg-Extension',
            ],
        ],
    ],

    'live-editor' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Live Page Editor', 'fw'),
        'description' => __('Edit page-builder pages directly on the live front end. Hover sections, columns and elements to select them and edit their options in place — a real-time visual builder layered on top of the existing Page Builder, reached from the "Edit Live" admin-bar button.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/live-editor.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Live-Editor-Extension',
            ],
        ],
    ],

    'snippets' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Snippets', 'fw'),
        'description' => __('Build reusable page-builder content as snippets, then embed them anywhere with [snippet id="…"] — edit once, and every place it appears updates.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/snippets.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Snippets-Extension',
            ],
        ],
    ],

    'template-library' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Template Library', 'fw'),
        'description' => __('A browsable library of premade page-builder templates — sections, columns and whole pages. Install one from the catalog and it appears under the Templates menu in the builder, ready to drop in. Downloaded templates live in your uploads folder, so the plugin stays lean.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/template-library.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Template-Library-Extension',
            ],
        ],
    ],

    'woocommerce' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('WooCommerce', 'fw'),
        'description' => __('Integrates WooCommerce with the Unyson+ framework: makes any active theme WooCommerce-aware and adds a WooCommerce Elements tab to the page builder — product grids and carousels, single product, categories, add-to-cart, cart icon and mini-cart, the cart / checkout / account / order-tracking pages, product search and filters. Inert until WooCommerce itself is active.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/woocommerce.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-WooCommerce-Extension',
            ],
        ],
    ],

    'theme-builder' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Theme Builder', 'fw'),
        'description' => __('Build global Headers, Bodies and Footers with the page builder, bundle them into a Template, and assign each Template to parts of the site with conditional rules (Use On / Exclude From) — the UnysonPlus take on Divi\'s Theme Builder. The Theme Settings header/footer remains the fallback when no Template applies. Absorbs and replaces the former Header & Footer Builder extension.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/theme-builder.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Theme-Builder-Extension',
            ],
        ],
    ],

    'custom-fields' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Custom Fields', 'fw'),
        'description' => __('An ACF-style custom fields builder for Unyson+. Create Field Groups, choose which post types they show on, and add fields (text, textarea, WYSIWYG, image, gallery, select, checkbox, color, date and more). Fields render as native meta boxes and save to post meta.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/custom-fields.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Custom-Fields-Extension',
            ],
        ],
    ],

    'post-types' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Post Types & Taxonomies', 'fw'),
        'description' => __('Create custom post types and taxonomies from the WordPress admin — no code required. Add taxonomies (like Categories or Tags) and attach them to one or more post types. Post types created here can also be targeted by the Custom Fields extension.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/post-types.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Post-Types-Extension',
            ],
        ],
    ],

    'sidebars' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Sidebars', 'fw'),
        'description' => __('Brings a new layer of customization freedom to your website by letting you add more than one sidebar to a page, or different sidebars on different pages.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/sidebars.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Sidebars-Extension',
            ],
        ],
    ],

    'slider' => [
        'display'     => true,
        'parent'      => 'media',
        'name'        => __('Sliders', 'fw'),
        'description' => __("Adds a sliders module to your website from where you'll be able to create different built in jQuery sliders for your homepage and rest of the pages.", 'fw'),
        'thumbnail'   => $thumbnails_uri . '/sliders.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Sliders-Extension',
            ],
        ],
    ],

    'portfolio' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Portfolio', 'fw'),
        'description' => __('This extension will add a fully fledged portfolio module that will let you display your projects using the built in portfolio pages.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/portfolio.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Portfolio-Extension',
            ],
        ],
    ],

    'megamenu' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Mega Menu', 'fw'),
        'description' => __('The Mega Menu extension adds a user-friendly drop down menu that will let you easily create highly customized menu configurations.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/mega-menu.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-MegaMenu-Extension',
            ],
        ],
    ],

    'breadcrumbs' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Breadcrumbs', 'fw'),
        'description' => __('Creates a simplified navigation menu for the pages that can be placed anywhere in the theme. This will make navigating the website much easier.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/breadcrumbs.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Breadcrumbs-Extension',
            ],
        ],
    ],

    'seo' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('SEO', 'fw'),
        'description' => __('This extension will enable you to have a fully optimized WordPress website by adding optimized meta titles, keywords and descriptions.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/seo.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-SEO-Extension',
            ],
        ],
    ],
	'events' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Events', 'fw'),
        'description' => __('This extension adds a fully fledged Events module to your theme. It comes with built in pages that contain a calendar where events can be added.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/events.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Events-Extension',
            ],
        ],
    ],

    'analytics' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Analytics', 'fw'),
        'description' => __('Enables the possibility to add the Google Analytics tracking code that will let you get all the analytics about visitors, page views and more.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/analytics.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Analytics-Extension',
            ],
        ],
    ],

    'feedback' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Feedback', 'fw'),
        'description' => __('Adds the possibility to leave feedback (comments, reviews and rating) about your products, articles, etc. This replaces the default comments system.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/feedback.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Feedback-Extension',
            ],
        ],
    ],

    'learning' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Learning', 'fw'),
        'description' => __('This extension adds a Learning module to your theme. Using this extension you can add courses, lessons and tests for your users to take.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/learning.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Learning-Extension',
            ],
        ],
    ],

    'shortcodes' => [
        'display'     => false,
        'parent'      => null,
        'name'        => __('Shortcodes', 'fw'),
        'description' => '',
        'thumbnail'   => 'about:blank',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Shortcodes-Extension',
            ],
        ],
    ],

    'builder' => [
        'display'     => false,
        'parent'      => null,
        'name'        => __('Builder', 'fw'),
        'description' => '',
        'thumbnail'   => 'about:blank',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Builder-Extension',
            ],
        ],
    ],

    'forms' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Forms', 'fw'),
        'description' => __("This extension adds the possibility to create a contact form. Use the drag & drop form builder to create any contact form you'll ever want or need.", 'fw'),
        'thumbnail'   => $thumbnails_uri . '/forms.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Forms-Extension',
            ],
        ],
    ],

    'mailer' => [
        'display'     => false,
        'parent'      => null,
        'name'        => __('Mailer', 'fw'),
        'description' => __('This extension will let you set some global email options and it is used by other extensions (like Forms) to send emails.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/mailer.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Mailer-Extension',
            ],
        ],
    ],

    'social' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Social', 'fw'),
        'description' => __('Use this extension to configure all your social related APIs. Other extensions will use the Social extension to connect to your social accounts.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/social.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Social-Extension',
            ],
        ],
    ],

    'media' => [
        'display'     => false,
        'parent'      => null,
        'name'        => __('Media', 'fw'),
        'description' => '',
        'thumbnail'   => 'about:blank',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Empty-Extension',
            ],
        ],
    ],

    'population-method' => [
        'display'     => false,
        'parent'      => 'media',
        'name'        => __('Population method', 'fw'),
        'description' => '',
        'thumbnail'   => 'about:blank',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-PopulationMethods-Extension',
            ],
        ],
    ],

    'translatepress-multilingual' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Translate Press', 'fw'),
        'description' => __('This extension lets you translate your website in any language or even add multiple languages for your users to change at their will from the front-end.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/translation.svg',
        'download'    => [
            'source'  => 'custom',
            'url_set' => 'options-general.php?page=translate-press',
            'opts'    => [
                'plugin' => 'translatepress-multilingual/index.php',
                'remote' => 'https://downloads.wordpress.org/plugin/translatepress-multilingual',
            ],
        ],
    ],

    'styling' => [
        'display'     => true,
        'parent'      => null,
        'name'        => __('Styling', 'fw'),
        'description' => __('This extension lets you control the website visual style. Starting from predefined styles to changing specific fonts and colors across the website.', 'fw'),
        'thumbnail'   => $thumbnails_uri . '/styling.svg',
        'download'    => [
            'source' => 'github',
            'opts'   => [
                'user_repo' => $github_account . '/UnysonPlus-Styling-Extension',
            ],
        ],
    ],
];

