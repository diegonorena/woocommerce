<?php
/**
 * Tests for the product reviews REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 2.7.0
 */

class Product_Reviews extends WC_REST_Unit_Test_Case {

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		parent::setUp();
		$this->endpoint = new WC_REST_Product_Reviews_Controller();
		$this->user = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
	}

	/**
	 * Test route registration.
	 *
	 * @since 2.7.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v2/products/(?P<product_id>[\d]+)/reviews', $routes );
		$this->assertArrayHasKey( '/wc/v2/products/(?P<product_id>[\d]+)/reviews/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Test getting all product reviews.
	 *
	 * @since 2.7.0
	 */
	public function test_get_product_reviews() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		// Create 10 products reviews for the product
		for ( $i = 0; $i < 10; $i++ ) {
			$review_id = WC_Helper_Product::create_product_review( $product->get_id() );
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/reviews' ) );
		$product_reviews = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, count( $product_reviews ) );
		$this->assertContains( array(
			'id'           => $review_id,
			'date_created' => '2016-01-01T11:11:11',
			'review'       => 'Review content here',
			'rating'       => 0,
			'name'         => 'admin',
			'email'        => 'woo@woo.local',
			'verified'     => false,
			'_links' => array(
				'self'       => array(
					array(
						'href' => rest_url( '/wc/v2/products/' . $product->get_id() . '/reviews/' . $review_id ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( '/wc/v2/products/' . $product->get_id() . '/reviews' ),
					),
				),
				'up' => array(
					array(
						'href' => rest_url( '/wc/v2/products/' . $product->get_id() ),
					),
				),
			),
		), $product_reviews );
	}

	/**
	 * Tests to make sure product reviews cannot be viewed without valid permissions.
	 *
	 * @since 2.7.0
	 */
	public function test_get_product_reviews_without_permission() {
		wp_set_current_user( 0 );
		$product = WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/reviews' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests to make sure an error is returned when an invalid product is loaded.
	 *
	 * @since 2.7.0
	 */
	public function test_get_product_reviews_invalid_product() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/0/reviews' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests getting a single product review.
	 *
	 * @since 2.7.0
	 */
	public function test_get_product_review() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$product_review_id = WC_Helper_Product::create_product_review( $product->get_id() );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/reviews/' . $product_review_id ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array(
			'id'           => $product_review_id,
			'date_created' => '2016-01-01T11:11:11',
			'review'       => 'Review content here',
			'rating'       => 0,
			'name'         => 'admin',
			'email'        => 'woo@woo.local',
			'verified'     => false,
		), $data );
	}

	/**
	 * Tests getting a single product review without the correct permissions.
	 *
	 * @since 2.7.0
	 */
	public function test_get_product_review_without_permission() {
		wp_set_current_user( 0 );
		$product = WC_Helper_Product::create_simple_product();
		$product_review_id = WC_Helper_Product::create_product_review( $product->get_id() );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/reviews/' . $product_review_id ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests getting a product review with an invalid ID.
	 *
	 * @since 2.7.0
	 */
	public function test_get_product_review_invalid_id() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/reviews/0' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests creating a product review.
	 *
	 * @since 2.7.0
	 */
	public function test_create_product_review() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$request = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product->get_id() . '/reviews' );
		$request->set_body_params( array(
			'review' => 'Hello world.',
			'name'   => 'Admin',
			'email'  => 'woo@woo.local',
			'rating' => '5',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( array(
			'id'           => $data['id'],
			'date_created' => $data['date_created'],
			'review'       => 'Hello world.',
			'rating'       => 5,
			'name'         => 'Admin',
			'email'        => 'woo@woo.local',
			'verified'     => false,
		), $data );
	}

	/**
	 * Tests creating a product review without required fields.
	 *
	 * @since 2.7.0
	 */
	public function test_create_product_review_invalid_fields() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();

		// missing review
		$request = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product->get_id() . '/reviews' );
		$request->set_body_params( array(
			'name'   => 'Admin',
			'email'  => 'woo@woo.local',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// missing name
		$request = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product->get_id() . '/reviews' );
		$request->set_body_params( array(
			'review' => 'Hello world.',
			'email'  => 'woo@woo.local',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// missing email
		$request = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product->get_id() . '/reviews' );
		$request->set_body_params( array(
			'review' => 'Hello world.',
			'name'   => 'Admin',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Tests updating a product review.
	 *
	 * @since 2.7.0
	 */
	public function test_update_product_review() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$product_review_id = WC_Helper_Product::create_product_review( $product->get_id() );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/reviews/' . $product_review_id ) );
		$data     = $response->get_data();
		$this->assertEquals( 'Review content here', $data['review'] );
		$this->assertEquals( 'admin', $data['name'] );
		$this->assertEquals( 'woo@woo.local', $data['email'] );
		$this->assertEquals( 0, $data['rating'] );

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/reviews/' . $product_review_id );
		$request->set_body_params( array(
			'review' => 'Hello world - updated.',
			'name'   => 'Justin',
			'email'  => 'woo2@woo.local',
			'rating' => 3,
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 'Hello world - updated.', $data['review'] );
		$this->assertEquals( 'Justin', $data['name'] );
		$this->assertEquals( 'woo2@woo.local', $data['email'] );
		$this->assertEquals( 3, $data['rating'] );
	}

	/**
	 * Tests updating a product review without the correct permissions.
	 *
	 * @since 2.7.0
	 */
	public function test_update_product_review_without_permission() {
		wp_set_current_user( 0 );
		$product = WC_Helper_Product::create_simple_product();
		$product_review_id = WC_Helper_Product::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/reviews/' . $product_review_id );
		$request->set_body_params( array(
			'review' => 'Hello world.',
			'name'   => 'Admin',
			'email'  => 'woo@woo.dev',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests that updating a product review with an invalid id fails.
	 *
	 * @since 2.7.0
	 */
	public function test_update_product_review_invalid_id() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/reviews/0' );
		$request->set_body_params( array(
			'review' => 'Hello world.',
			'name'   => 'Admin',
			'email'  => 'woo@woo.dev',
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test deleting a product review.
	 *
	 * @since 2.7.0
	 */
	public function test_delete_product_review() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$product_review_id = WC_Helper_Product::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/' . $product->get_id() . '/reviews/' . $product_review_id );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Test deleting a product review without permission/creds.
	 *
	 * @since 2.7.0
	 */
	public function test_delete_product_without_permission() {
		wp_set_current_user( 0 );
		$product = WC_Helper_Product::create_simple_product();
		$product_review_id = WC_Helper_Product::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/' . $product->get_id() . '/reviews/' . $product_review_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting a product review with an invalid id.
	 *
	 * @since 2.7.0
	 */
	public function test_delete_product_review_invalid_id() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$product_review_id = WC_Helper_Product::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/' . $product->get_id() . '/reviews/0' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test batch managing product reviews.
	 */
	public function test_product_reviews_batch() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();

		$review_1_id = WC_Helper_Product::create_product_review( $product->get_id() );
		$review_2_id = WC_Helper_Product::create_product_review( $product->get_id() );
		$review_3_id = WC_Helper_Product::create_product_review( $product->get_id() );
		$review_4_id = WC_Helper_Product::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product->get_id() . '/reviews/batch' );
		$request->set_body_params( array(
			'update' => array(
				array(
					'id'     => $review_1_id,
					'review' => 'Updated review.',
				),
			),
			'delete' => array(
				$review_2_id,
				$review_3_id,
			),
			'create' => array(
				array(
					'review' => 'New review.',
					'name'   => 'Justin',
					'email'  => 'woo3@woo.local',
				),
			),
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 'Updated review.', $data['update'][0]['review'] );
		$this->assertEquals( 'New review.', $data['create'][0]['review'] );
		$this->assertEquals( $review_2_id, $data['delete'][0]['id'] );
		$this->assertEquals( $review_3_id, $data['delete'][1]['id'] );

		$request = new WP_REST_Request( 'GET', '/wc/v2/products/' . $product->get_id() . '/reviews' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
	}

	/**
	 * Test the product review schema.
	 *
	 * @since 2.7.0
	 */
	public function test_product_review_schema() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		$request = new WP_REST_Request( 'OPTIONS', '/wc/v2/products/' . $product->get_id() . '/reviews' );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 7, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'review', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'rating', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'email', $properties );
		$this->assertArrayHasKey( 'verified', $properties );
	}
}
