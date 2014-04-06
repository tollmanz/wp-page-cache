<?php

class ZDTPCUtilityUnitTests extends WP_UnitTestCase {
	public $object_cache;
	public $servers;

	public function setUp() {
		parent::setUp();
		global $memcached_servers;
		$memcached_servers = array(
			'default' => array( '127.0.0.1', 11211 ),
		);
		$this->object_cache = new WP_Object_Cache();
		$this->object_cache->flush();
	}

	public function pluginLoaded() {
		$this->assertTrue( class_exists( 'zdt\wpPageCache\PageCache' ) );
	}
}