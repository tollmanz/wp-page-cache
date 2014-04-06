<?php

namespace zdt\wpPageCache;

class PageCache {
	/**
	 * Prefix for the cached items.
	 *
	 * @since 0.1.0.
	 *
	 * @var   string    A unique identifier for the page cache items.
	 */
	public $cache_group = 'zdtpc';

	/**
	 * Holds the CacheKey object for PageCache.
	 *
	 * @since 0.1.0.
	 *
	 * @var   CacheKey    Manages the CacheKey object for handling the cache key.
	 */
	public $key;

	/**
	 * Construct the object and set up configuration.
	 *
	 * @since  0.1.0.
	 *
	 * @return PageCache|void
	 */
	function __construct() {
		// Instantiate the CacheKey object
		$this->key = new CacheKey();
	}

	/**
	 * Initialize the object cache for use with the page cache.
	 *
	 * WordPress initializes the object cache in wp-settings.php; however, it is initialized too late for use in the
	 * advanced-cache.php file. As such, it must be initialized early to make an object cache available to for the page
	 * cache to use.
	 *
	 * @since  0.1.0
	 *
	 * @return bool    True if the object cache can be initialized. False if it cannot be initialized.
	 */
	function load_object_cache() {
		global $wp_object_cache;

		// Make sure the object cache is loaded
		if ( include_once( WP_CONTENT_DIR . '/object-cache.php' ) ) {
			// Attempt to init the object cache if it is available
			if ( function_exists( 'wp_cache_init' ) ) {
				wp_cache_init();

				// Verify that we have a usable object cache
				if ( is_object( $wp_object_cache ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determine if the request should be cached.
	 *
	 * @since  0.1.0.
	 *
	 * @return bool    False if request should not be cached. True if it should be cached.
	 */
	function is_request_cacheable() {
		// If the object cache cannot be loaded, bail
		if ( ! $this->load_object_cache() ) {
			return false;
		}

		// File accessed directly?
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			return false;
		}

		// Never cache an endpoint
		if ( in_array( basename( $_SERVER['SCRIPT_FILENAME'] ), array( 'xmlrpc.php' ) ) ) {
			return false;
		}

		// Do not cache the JS generator
		if ( strstr( $_SERVER['SCRIPT_FILENAME'], 'wp-includes/js' ) ) {
			return false;
		}

		// POST requests should not be cached
		if ( ! empty( $GLOBALS['HTTP_RAW_POST_DATA'] ) || ! empty( $_POST ) ) {
			return false;
		}

		// Do not cache when a cookie for a cache exempt visitor is present
		if ( ! empty( $_COOKIE ) && is_array( $_COOKIE ) ) {
			foreach ( array_keys( $_COOKIE ) as $cookie ) {
				if ( 'wp' === substr( $cookie, 0, 2 ) || 'wordpress' === substr( $cookie, 0, 9 ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get a previously cached page from the page cache.
	 *
	 * @since  0.1.0.
	 *
	 * @param  string    $key      The page cache key to lookup.
	 * @param  string    $group    The group to lookup.
	 * @return mixed               The page. False if not found.
	 */
	function get_page( $key, $group ) {
		return wp_cache_get( $key, $group );
	}

	/**
	 * Generates and caches the page.
	 *
	 * @since  0.1.0.
	 *
	 * @param  string    $page_content    The contents produced by the page load.
	 * @return string                     The contents to echo to the screen.
	 */
	function generate_page( $page_content ) {
		$page_content .= '<!-- Cached at: ' . time() . ' -->';
		wp_cache_set( $this->get_key()->get_page_key( $_SERVER ), $page_content, $this->get_cache_group(), 500 );
		return $page_content;
	}

	/**
	 * Get the CacheKey object associated with the class.
	 *
	 * @since  0.1.0.
	 *
	 * @return CacheKey    The CacheKey object which contains the key and permalink information.
	 */
	function get_key() {
		return $this->key;
	}

	/**
	 * Get the value of the page cache group.
	 *
	 * @since  0.1.0.
	 *
	 * @return string    The cache group value.
	 */
	function get_cache_group() {
		return $this->cache_group;
	}
}

/**
 * Class CacheKey
 *
 * Manages building a cache key for a new page.
 *
 * @since 0.1.0.
 */
class CacheKey {
	/**
	 * The $_SERVER pieces needed to generate the permalink.
	 *
	 * @since 0.1.0.
	 *
	 * @var   array    Contains 'host', 'path', 'protocol', and 'query'.
	 */
	public $key_pieces = array();

	/**
	 * Current page URL.
	 *
	 * @since 0.1.0.
	 *
	 * @var   string    The current URL for the view.
	 */
	public $permalink = '';

	/**
	 * The cache key for the current page.
	 *
	 * @since 0.1.0.
	 *
	 * @var   string    The MD5 hash representing the current page view.
	 */
	public $page_key = '';

	/**
	 * Get or generate the current view's key.
	 *
	 * @since  0.1.0.
	 *
	 * @param  array     $server    The $_SERVER array. Must have 'HTTP_HOST', 'REQUEST_URI', and 'QUERY_STRING'.
	 * @return string               MD5 hash of the permalink with the key prefix.
	 */
	function get_page_key( $server ) {
		if ( empty( $this->page_key ) ) {
			$this->page_key = $this->generate_page_key( $server );
		}

		return $this->page_key;
	}

	/**
	 * Generate the page key for the view based on the prefix and the permalink.
	 *
	 * @since  0.1.0.
	 *
	 * @param  array     $server    The $_SERVER array. Must have 'HTTP_HOST', 'REQUEST_URI', and 'QUERY_STRING'.
	 * @return string               MD5 hash of the permalink with the key prefix
	 */
	function generate_page_key( $server ) {;
		return md5( $this->get_permalink( $server ) );
	}

	/**
	 * Get or generate the current view's permalink.
	 *
	 * @since  0.1.0.
	 *
	 * @param  array     $server    The $_SERVER array. Must have 'HTTP_HOST', 'REQUEST_URI', and 'QUERY_STRING'.
	 * @return string               Permalink for the current view.
	 */
	function get_permalink( $server ) {
		if ( empty( $this->permalink ) ) {
			$this->permalink = $this->generate_permalink( $server );
		}

		return $this->permalink;
	}

	/**
	 * Generate the current view's permalink.
	 *
	 * @since  0.1.0.
	 *
	 * @param  array     $server    The $_SERVER array. Must have 'HTTP_HOST', 'REQUEST_URI', and 'QUERY_STRING'.
	 * @return string               Permalink for the current view.
	 */
	function generate_permalink( $server ) {
		$key_pieces = $this->get_key_pieces( $server );
		$query_string = ( ! empty( $key_pieces['query'] ) ) ? '?' . $key_pieces['query'] : '';
		return $key_pieces['protocol'] . '://' . $key_pieces['host'] . $key_pieces['path'] . $query_string;
	}

	/**
	 * Get or generate the pieces of the permalink for the current view.
	 *
	 * @since  0.1.0.
	 *
	 * @param  array     $server    The $_SERVER array. Must have 'HTTP_HOST', 'REQUEST_URI', and 'QUERY_STRING'.
	 * @return string               Pieces of the current view's permalink.
	 */
	function get_key_pieces( $server ) {
		if ( empty( $this->key_pieces ) ) {
			$this->key_pieces = $this->generate_key_pieces( $server );
		}

		return $this->key_pieces;
	}

	/**
	 * Generate the pieces of the permalink for the current view.
	 *
	 * @since  0.1.0.
	 *
	 * @param  array     $server    The $_SERVER array. Must have 'HTTP_HOST', 'REQUEST_URI', and 'QUERY_STRING'.
	 * @return string               Pieces of the current view's permalink.
	 */
	function generate_key_pieces( $server ) {
		// Save the pieces used to generate the page key
		$key_pieces = array(
			'host'     => $server['HTTP_HOST'],
			'path'     => ( $position = strpos( $server['REQUEST_URI'], '?' ) ) ? substr( $server['REQUEST_URI'], 0, $position ) : $server['REQUEST_URI'],
			'query'    => $server['QUERY_STRING'],
			'protocol' => ( true === $this->is_ssl( $server ) ) ? 'https' : 'http',
		);

		return $key_pieces;
	}

	/**
	 * Determine is SSL is being used.
	 *
	 * Heavily borrowed from the WordPress core function. Added additional inspection of the HTTP_X_FORWARDED_PROTO
	 * $_SERVER.
	 *
	 * @since  0.1.0.
	 * @link   https://codex.wordpress.org/Function_Reference/is_ssl
	 *
	 * @param  array    $server    The $_SERVER array. Must have either 'HTTPS', 'HTTP_X_FORWARDED_PROTO', or 'SERVER_PORT'.
	 * @return bool                 True is SSL is used; false if it is not being used.
	 */
	function is_ssl( $server = array() ) {
		$server = ( ! empty( $server ) ) ? $server : $_SERVER;

		if ( isset( $server['HTTPS'] ) && ( 'on' === strtolower( $server['HTTPS'] ) || 1 === (int) $server['HTTPS'] ) ) {
			return true;
		} elseif ( isset( $server['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $server['HTTP_X_FORWARDED_PROTO'] ) {
			return true;
		} elseif ( isset( $server['SERVER_PORT'] ) && ( 443 === (int) $server['SERVER_PORT'] ) ) {
			return true;
		}
		return false;
	}
}

// Instantiate the page
$PageCache = new PageCache();

// Bail if the request cannot be cached
if ( $PageCache->is_request_cacheable() ) {
	// Find the page
	$page = $PageCache->get_page( $PageCache->get_key()->get_page_key( $_SERVER ), $PageCache->get_cache_group() );

	// If not found, generate and cache the page
	if ( false === $page ) {
		ob_start( array( $PageCache, 'generate_page' ) );
	} else {
		echo $page;
		exit();
	}
}