<?php
/**
 * Class Name: WP_REST_CoAuthors_AuthorUsers_Controller
 * Author: Michael Jacobsen
 * Author URI: https://mjacobsen4dfm.wordpress.com/
 * License: GPL2+
 *
 * CoAuthors_AuthorUsers controller class.
 */

if ( ! class_exists( 'WP_REST_CoAuthors_AuthorUsers' ) ) {
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
	 * Taxonomy for Co-Authors.
	 *
	 * @var string
	 */
	protected $taxonomy;

	/**
	 * Post_type for Co-Authors.
	 *
	 * @var string
	 */
	protected $coauthor_post_type;

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

		if ( class_exists( 'WP_REST_CoAuthors_AuthorUsers' ) ) {
			$this->AuthorUser         = new WP_REST_CoAuthors_AuthorUsers( $this->namespace, $this->rest_base, $this->parent_base, $this->parent_type );
			$this->coauthor_post_type = $this->AuthorUser->coauthor_post_type;
		}
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

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
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

		register_rest_route( $this->namespace, '/' . $this->parent_base . '/(?P<parent_id>[\d]+)/' . $this->rest_base . '/(?P<id>[\d]+)', array(
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
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params                       = parent::get_collection_params();
		$query_params['context']['default'] = 'view';

		return $query_params;
	}

	/**
	 * Get the User's schema, conforming to JSON Schema
	 *
	 * @return array
	 */

	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'author-user',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'description' => __( 'Unique identifier for the resource.' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'display_name'       => array(
					'description' => __( 'Display name for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'first_name'         => array(
					'description' => __( 'First name for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'last_name'          => array(
					'description' => __( 'Last name for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'user_email'              => array(
					'description' => __( 'The email address for the resource.' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'edit' ),
					'required'    => true,
				),
				'user_login'              => array(
					'description' => __( 'Login ID.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'required'    => true,
				),
				'website'                => array(
					'description' => __( 'URL to the author website.' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'aim'               => array(
					'description' => __( 'AOL Instant Messenger ID.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'yahooim'               => array(
					'description' => __( 'Yahoo Instant Messenger ID.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'jabber'               => array(
					'description' => __( 'Jabber Instant Messenger ID.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'        => array(
					'description' => __( 'Description of the resource.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' )
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
