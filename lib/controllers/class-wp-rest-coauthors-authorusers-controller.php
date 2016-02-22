<?php
/**
 * Class Name: WP_REST_CoAuthors_AuthorPosts_Controller
 * Author: Michael Jacobsen
 * Author URI: https://mjacobsen4dfm.wordpress.com/
 * License: GPL2+
 *
 * CoAuthors_AuthorPosts controller class.
 */

if ( !class_exists( 'WP_REST_CoAuthors_AuthorUsers' ) ) {
	require_once dirname( __FILE__ ) . '/../inc/class-wp-rest-coauthors-authorusers.php';
}

abstract class WP_REST_CoAuthors_AuthorUsers_Controller extends WP_REST_Controller {
	/**
	 * Associated co-author object type.
	 *
	 * @var WP_REST_CoAuthors_AuthorUsers
	 */
	protected $AuthorUser = null;

	/**
	 * Associated parent type.
	 *
	 * @var string ("post")
	 */
	protected $parent_type = null;

	/**
	 * Associated parent post type name.
	 *
	 * @var string
	 */
	protected $parent_base = null;

	/**
	 * WP_REST_CoAuthors_Controller constructor.
	 */
	public function __construct() {
		if ( empty( $this->parent_type ) ) {
			_doing_it_wrong( 'WP_REST_Meta_Controller::__construct', __( 'The object type must be overridden' ), 'WPAPI-2.0' );
			return;
		}
		if ( empty( $this->parent_base ) ) {
			_doing_it_wrong( 'WP_REST_Meta_Controller::__construct', __( 'The parent base must be overridden' ), 'WPAPI-2.0' );
			return;
		}

		$this->AuthorUser = new WP_REST_CoAuthors_AuthorUsers($this->namespace, $this->rest_base, $this->parent_base, $this->parent_type);
	}

	/**
	 * Register the authors-related routes.
	 */
	public function register_routes() {
		/**
		 * co-authors base
		 */
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this->AuthorUser, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<coauthor_id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this->AuthorUser, 'get_item' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->parent_base . '/(?P<parent_id>[\d]+)/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this->AuthorUser, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->parent_base . '/(?P<parent_id>[\d]+)/' . $this->rest_base . '/(?P<coauthor_id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this->AuthorUser, 'get_item' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),

			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get the schema, conforming to JSON Schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'authors',
			'type'       => 'object',
			/*
			 * Base properties for every Post
			 */
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the object.' ),
					'type'        => 'integer',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				)
			),
		);
		return $schema;
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();
		$new_params = array();
		$new_params['context'] = $params['context'];
		$new_params['context']['default'] = 'edit';
		return $new_params;
	}
}
