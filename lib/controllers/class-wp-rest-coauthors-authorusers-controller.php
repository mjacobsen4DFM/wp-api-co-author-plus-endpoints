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
	protected $post_type;

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

		$this->taxonomy   = 'author';
		$this->post_type  = 'guest-author';

		if ( class_exists( 'WP_REST_CoAuthors_AuthorUsers' ) ) {
			$this->AuthorUser = new WP_REST_CoAuthors_AuthorUsers( $this->namespace, $this->rest_base, $this->parent_base, $this->parent_type, $this->taxonomy, $this->post_type );
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
	 * Get the User's schema, conforming to JSON Schema
	 *
	 * @return array
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'user',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the resource.' ),
					'type'        => 'integer',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'username'    => array(
					'description' => __( 'Login name for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'required'    => true,
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_user',
					),
				),
				'name'        => array(
					'description' => __( 'Display name for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'first_name'  => array(
					'description' => __( 'First name for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'last_name'   => array(
					'description' => __( 'Last name for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'email'       => array(
					'description' => __( 'The email address for the resource.' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'edit' ),
					'required'    => true,
				),
				'url'         => array(
					'description' => __( 'URL of the resource.' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'Description of the resource.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'link'        => array(
					'description' => __( 'Author URL to the resource.' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
					'readonly'    => true,
				),
				'nickname'    => array(
					'description' => __( 'The nickname for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the resource.' ),
					'type'        => 'string',
					'context'     => array( 'embed', 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'registered_date' => array(
					'description' => __( 'Registration date for the resource.' ),
					'type'        => 'date-time',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
				'roles'           => array(
					'description' => __( 'Roles assigned to the resource.' ),
					'type'        => 'array',
					'context'     => array( 'edit' ),
				),
				'capabilities'    => array(
					'description' => __( 'All capabilities assigned to the resource.' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
				),
				'extra_capabilities' => array(
					'description' => __( 'Any extra capabilities assigned to the resource.' ),
					'type'        => 'object',
					'context'     => array( 'edit' ),
					'readonly'    => true,
				),
			),
		);

		if ( get_option( 'show_avatars' ) ) {
			$avatar_properties = array();

			$avatar_sizes = rest_get_avatar_sizes();
			foreach ( $avatar_sizes as $size ) {
				$avatar_properties[ $size ] = array(
					'description' => sprintf( __( 'Avatar URL with image size of %d pixels.' ), $size ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'embed', 'view', 'edit' ),
				);
			}

			$schema['properties']['avatar_urls']  = array(
				'description' => __( 'Avatar URLs for the resource.' ),
				'type'        => 'object',
				'context'     => array( 'embed', 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => $avatar_properties,
			);

		}

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		$query_params['context']['default'] = 'view';
		return $query_params;
	}
}
