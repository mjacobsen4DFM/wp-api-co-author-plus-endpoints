<?php
/**
 * Class Name: WP_REST_CoAuthors_AuthorTerms
 * Author: Michael Jacobsen
 * Author URI: https://mjacobsen4dfm.wordpress.com/
 * License: GPL2+
 *
 * CoAuthors_AuthorTerms base class.
 */

class WP_REST_CoAuthors_AuthorTerms extends WP_REST_Controller {
	/**
	 * Post_type for Co-Authors.
	 *
	 * @var string
	 */
	protected $CoAuthors_Plus;

	/**
	 * Post_type for Co-Authors.
	 *
	 * @var string
	 */
	protected $CoAuthors_Guest_Authors;

	/**
	 * Taxonomy for Co-Authors.
	 *
	 * @var string
	 */
	public $coauthor_taxonomy;

	/**
	 * Post_type for Co-Authors.
	 *
	 * @var string
	 */
	public $coauthor_post_type;

	/**
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Associated object type.
	 *
	 * @var string Type slug ("post" or "user")
	 */
	protected $parent_type = null;

	/**
	 * Base path for post type endpoints.
	 *
	 * @var string
	 */
	protected $parent_base;

	/**
	 * Associated object type.
	 *
	 * @var string Type slug ("post" or "user")
	 */
	protected $rest_base = null;

	public function __construct( $namespace, $rest_base, $parent_base, $parent_type )
	{
		$this->namespace = $namespace;
		$this->rest_base = $rest_base;
		$this->parent_base = $parent_base;
		$this->parent_type = $parent_type;
		$this->CoAuthors_Plus = new coauthors_plus ();
		$this->CoAuthors_Guest_Authors = new CoAuthors_Guest_Authors();
		$this->coauthor_taxonomy = $this->CoAuthors_Plus->coauthor_taxonomy;
		$this->coauthor_post_type = $this->CoAuthors_Guest_Authors->post_type;
	}


	/**
	 * Retrieve author terms for object.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, List of co-author objects data on success, WP_Error otherwise
	 */
	public function get_items( $request ) {

		$author_terms = array();

		if ( ! empty( $request['parent_id'] ) ) {
			$parent_id = (int) $request['parent_id'];

			//Get the 'author' terms for this post
			$terms = wp_get_object_terms( $parent_id, $this->coauthor_taxonomy );
		} else {
			//Get all 'author' terms
			$terms = get_terms( $this->coauthor_taxonomy );
		}

		foreach ( $terms as $term ) {
			$term_item = $this->prepare_item_for_response( $term, $request );

			if ( is_wp_error( $term_item ) ) {
				continue;
			}

			$author_terms[] = $this->prepare_response_for_collection( $term_item );
		}

		if ( ! empty( $author_terms ) ) {
			return rest_ensure_response( $author_terms );
		}

		return new WP_Error( 'rest_authors_get_term', __( 'Invalid authors id.' ), array( 'status' => 404 ) );
	}

	/**
	 * Retrieve author term object.
	 * (used by create_item() to immediately confirm creation)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, A co-author object data on success, WP_Error otherwise
	 */
	public function get_item( $request ) {
		$term_id = (int) $request['id'];

		if ( ! empty( $request['parent_id'] ) ) {
			$parent_id = (int) $request['parent_id'];

			$terms = wp_get_object_terms( $parent_id, $this->coauthor_taxonomy );

			foreach ( $terms as $term ) {
				if ( $term->term_id == $term_id ) {
					return $this->prepare_item_for_response( $term, $request );
				}
			}
		} else {
			$author_term = get_term( $term_id, $this->coauthor_taxonomy );

			if ( is_wp_error( $author_term ) ) {
				return $author_term;
			}

			if ( 0 == $author_term->term_id ) {
				return new WP_Error( 'rest_authors_get_term', __( 'Invalid authors id.' ), array( 'status' => 404 ) );
			}

			return $this->prepare_item_for_response( $author_term, $request );
		}

		return new WP_Error( 'rest_authors_get_term', __( 'Invalid authors id.' ), array( 'status' => 404 ) );
	}

	/**
	 * Prepares authors data for return as an object.
	 *
	 * @param stdClass $data wp_term and wp_term_taxonomy row from database for the term requested
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error co-author object data on success, WP_Error otherwise
	 */
	public function prepare_item_for_response( $data, $request ) {
		$author_term = array(
			'id'                => (int) $data->term_id,
			'name'              => (string) $data->name,
			'slug'              => (string) $data->slug,
			'term_group '       => (int) $data->term_group ,
			'term_taxonomy_id'  => (int) $data->term_taxonomy_id,
			'taxonomy'          => (string) $data->taxonomy,
			'description'       => (string) $data->description,
			'parent'            => (int) $data->parent,
			'count'             => (int) $data->count,
		);

		$response = rest_ensure_response( $author_term );

		/**
		 * Add information links about the object
		 */
		$response->add_link( 'about', rest_url( $this->namespace . '/' . $this->rest_base . '/' . $author_term['id'] ), array( 'embeddable' => true ) );

		/**
		 * Filter authors value returned from the API.
		 *
		 * Allows modification of the authors value right before it is returned.
		 *
		 * @param array           $response array of authors data: id.
		 * @param WP_REST_Request $request  Request used to generate the response.
		 */
		return apply_filters( 'rest_prepare_authors_value', $response, $request );
	}

	/**
	 * Check if the data provided is valid data.
	 *
	 * Excludes serialized data from being sent via the API.
	 *
	 * @param mixed $data Data to be checked
	 * @return boolean Whether the data is valid or not
	 */
	protected function is_valid_authors_data( $data ) {
		if ( is_array( $data ) || is_object( $data ) || is_serialized( $data ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add authors to an object.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {
		$parent_id = (int) $request['parent_id'];
		$author_term = (int) $request['id']; 	//Currently only supports 1 author; send multiple posts to add multiple authors

		$author_term_id = wp_set_object_terms( $parent_id, $author_term, $this->coauthor_taxonomy, true );

		if ( is_wp_error( $author_term_id ) ) {
			// There was an error somewhere and the terms couldn't be set.
			return $author_term_id;
		} else {
			// Success! The post's author was set.
			//Verify that it is there.
			$response = rest_ensure_response( $this->get_item( $request ) );

			if ( is_wp_error( $response ) ) {
				// There was an error somewhere and the terms couldn't be retrieved.
				return new WP_Error( 'create_item', __( 'Author was added; but it could not be retrieved via get_item().' ), array( 'status' => 404 ) );
			}

			$response->set_status( 201 );
			$data = $response->get_data();
			$response->header( 'Location', rest_url( $this->namespace . '/' . $this->parent_base . '/' . $parent_id . '/' . $this->rest_base . '/' . $data['id'] ) );

			$data = new stdClass();
			$data->id = $author_term;

			/* This action is documented in WP-API/../lib/endpoints/class-wp-rest-terms-controller.php */
			do_action( 'rest_insert_author', $data, $request, true );

			return $response;
		}
	}

	/**
	 * Delete authors from an object.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error Message on success, WP_Error otherwise
	 */
	public function delete_item( $request ) {
		$parent_id = (int) $request['parent_id'];
		/*
		$atid = (int) $request['id'];
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;
		*/

		return new WP_Error( 'rest_authors_delete_author_item', __( 'Delete authors not supported. Note: post->id ' .$parent_id . ' is unchanged.'  ), array( 'status' => 500 ) );

	}
}
