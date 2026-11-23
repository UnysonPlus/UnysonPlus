<?php if (!defined('FW')) die('Forbidden');

/**
 * Changelog ----------------------------------------------------------------
 *
 * 1.0.4 - Blog settings on Settings -> Writing. For sites that do not run a
 *         blog, a "Blog visibility" control can hide the Blog Posts admin menu,
 *         optionally also remove the "+ New -> Post" shortcut and the post
 *         dashboard widgets, and (full disable) serve a 404 for every blog
 *         front-end URL (single posts, the posts page, category / tag / date /
 *         author archives and the feed) while leaving pages and custom post
 *         types untouched; the real front page is never 404'd. A separate
 *         "Rename Posts" checkbox (on by default) controls the original Unyson
 *         "Posts" -> "Blog Posts" relabel, so a site can turn it off and see
 *         WordPress's plain "Posts". Options upw_blog_mode and upw_blog_rename,
 *         each with a matching filter; the visibility mode also honours a
 *         UPW_BLOG_MODE constant override.
 */

$manifest = array();

$manifest['name']        = __( 'Blog Posts', 'fw' );
$manifest['description'] = __( 'This extension adds Blog Posts functionality.', 'fw' );

$manifest['version']     = '1.0.4';
$manifest['display']     = false;
$manifest['standalone']  = true;

// Repository Info
$manifest['github_update'] = 'UnysonPlus/UnysonPlus-Blog-Extension';
$manifest['github_repo']   = 'https://github.com/UnysonPlus/UnysonPlus-Blog-Extension';

// Author Info
$manifest['author']     = 'UnysonPlus';
$manifest['author_uri'] = 'https://www.lastimosa.com.ph/unysonplus';

// Meta
$manifest['license']      = 'GPL-2.0-or-later';
$manifest['text_domain']  = 'fw';
$manifest['requires_php'] = '7.4';
$manifest['requires_wp']  = '5.8';
