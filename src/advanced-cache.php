<?php

namespace zdt\wpPageCache;

class PageCache {
	/**
	 * Construct the object and set up configuration.
	 *
	 * @since  0.1.0.
	 *
	 * @return PageCache
	 */
	function __construct() {
		ob_start( array( $this, 'generate_page' ) );
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
		return $page_content;
	}
}

new PageCache();