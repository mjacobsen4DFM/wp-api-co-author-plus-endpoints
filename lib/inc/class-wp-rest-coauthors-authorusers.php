<?php
/**
 * Class Name: WP_REST_CoAuthors_AuthorUsers
 * Author: Michael Jacobsen
 * Author URI: https://mjacobsen4dfm.wordpress.com/
 * License: GPL2+
 *
 * CoAuthors_AuthorUsers base class.
 */

class WP_REST_CoAuthors_AuthorUsers extends WP_REST_Controller {
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
	 * The namespace of this controller's route.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Associated object type.
	 *
	 * @var string ("post")
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
	 * @var string ("post")
	 */
	protected $rest_base = null;

	public function __construct( $namespace, $rest_base, $parent_base, $parent_type, $taxonomy, $post_type )
	{
		$this->namespace = $namespace;
		$this->rest_base = $rest_base;
		$this->parent_base = $parent_base;
		$this->parent_type = $parent_type;
		$this->taxonomy = $taxonomy;
		$this->post_type = $post_type;
	}


	/**
	 * Retrieve co-authors users for object.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, List of co-author objects data on success, WP_Error otherwise
	 */
	public function get_items( $request ) {
		if ( ! empty ( $request['parent_id'] ) ) {

			$parent_id = (int) $request['parent_id'];

			//Get the 'author' terms for this post
			$terms = wp_get_object_terms( $parent_id, $this->taxonomy );
		} else {
			//Get all 'author' terms
			$terms = get_terms( $this->taxonomy );
		}

		foreach ( $terms as $term ) {
			//create a map to look up the metadata in the term->description
			//$searchmap = $this->set_searchmap($term); //Fail: see function

			//Since the co-authors method didn't work, trying regex for the int value of the ID
			$regex = "/\\b(\\d+)\\b/";
			preg_match( $regex, $term->description, $matches );
			$id = $matches[1];

			// Get the user for this 'author' term
			$user = get_user_by( 'id', $id );

			if ( 'WP_User' == get_Class( $user ) ) {
				// Enhance the object attributes for JSON
				$author_user = $this->prepare_item_for_response( $user, $request );

				if ( is_wp_error( $author_user ) ) {
					continue;
				}

				$author_users[] = $this->prepare_response_for_collection( $author_user );
			}
		}

		if ( ! empty( $author_users ) ) {
			return rest_ensure_response( $author_users );
		}

		return new WP_Error( 'rest_co_authors_get_users', __( 'Invalid authors id.' ), array( 'status' => 404 ) );
	}

	/**
	 * Retrieve co-authors user object.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, co-authors object data on success, WP_Error otherwise
	 */
	public function get_item( $request ) {
		$co_authors_id = (int) $request['id'];
		$id = null;
		$terms = null;

		// See if this request has a parent
		if ( !empty ( $request['parent_id'] ) ) {

			$parent_id = (int) $request['parent_id'];

			//Get the 'author' terms for this post
			$terms = wp_get_object_terms( $parent_id, $this->taxonomy );
		}
		else {
			//Get all 'author' terms
			$terms = get_terms( $this->taxonomy );
		}

		// Ensure that the request co_authors_id is a co-author
		// if none of its author terms has this ID it is invalid
		foreach ( $terms as $term ) {
			//create a map to look up the metadata in the term->description
			//$searchmap = $this->set_searchmap($term); //Fail: see function

			//Since the $searchmap method didn't work, trying regex for the int value of the ID
			$regex = "/\\b(" . $co_authors_id . ")\\b/";
			preg_match( $regex, $term->description, $matches );
			$id = $matches[1];

			if( !empty( $id ) ) {
				//This id matches the co_authors_id
				break;
			}
		}

		if ( !empty( $id ) ) {
			// Get the user for this 'author' term
			$author_user = get_user_by( 'id', $id );

			// Ensure $author_user is a user (not false)
			if ( 'WP_User' == get_Class( $author_user ) ) {
				// Enhance the object attributes for JSON
				$author_user_item = $this->prepare_item_for_response( $author_user, $request );

				if ( is_wp_error( $author_user_item ) ) {
					return new WP_Error( 'rest_co_authors_get_user', __( 'Invalid authors id.' ), array( 'status' => 404 ) );
				}

				if ( !empty( $author_user_item ) ) {
					return rest_ensure_response( $author_user_item );
				}
			}
		}

		return new WP_Error( 'rest_co_authors_get_users', __( 'Invalid authors id.' ), array( 'status' => 404 ) );
	}



	/**
	 * Create a map to search the description field
	 * (used by create_item() to immediately confirm creation)
	 * $ajax_search_fields was taken from Automattic/Co-Authors-Plus/../co-authors-plus.php
	 *
	 * @param WP_TERM $term
	 * @return array $searchmap
	 */
	public function set_searchmap($term) {
		//This didn't work, some names break the pattern (i.e. "salisbury William S. Salisbury salisbury 87 bsalisbury@pioneerpress.com")
		$ajax_search_fields = array( 'display_name', 'first_name', 'last_name', 'user_login', 'ID', 'user_email' );
		$co_authors_values = explode(' ', $term->description);
		if (count($co_authors_values) == 5) {
			//Sometimes the user doesn't have an email
			//avoid index out of bounds error below
			$co_authors_values[] = null;
		}
		$searchmap = array(
			$ajax_search_fields[0] => $co_authors_values[0],
			$ajax_search_fields[1] => $co_authors_values[1],
			$ajax_search_fields[2] => $co_authors_values[2],
			$ajax_search_fields[3] => $co_authors_values[3],
			$ajax_search_fields[4] => $co_authors_values[4],
			$ajax_search_fields[5] => $co_authors_values[5]
		);
		return $searchmap;
	}

	/**
	 * Prepares co-authors data for return as an object.
	 * Used to prepare the guest-authors object
	 *
	 * @param WP_User $data
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error, co-authors object data on success, WP_Error otherwise
	 */
	public function prepare_item_for_response( $data, $request ) {
		$author_user = array();

		if ( 'WP_User' == get_Class( $data )  ) {
			$author_user = array(
				'id'    => (int) $data->ID,
				'first_name'    => (string) $data->first_name,
				'last_name'    => (string) $data->last_name,
				'display_name'    => (string) $data->display_name
			);
		}

		$response = rest_ensure_response( $author_user );

		/**
		 * Add information links about the object
		 */
		$response->add_link( 'about', rest_url( $this->namespace . '/' . $this->rest_base . '/' . $author_user['id'] ), array( 'embeddable' => true ) );

		/**
		 * Filter a co-authors value returned from the API.
		 *
		 * Allows modification of the co-authors value right before it is returned.
		 *
		 * @param array           $response array of co-authors data: id.
		 * @param WP_REST_Request $request  Request used to generate the response.
		 */
		return apply_filters( 'rest_prepare_co_authors_value', $response, $request );
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
}
