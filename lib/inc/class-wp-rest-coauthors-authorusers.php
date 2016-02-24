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
	 * Retrieve co-authors users for object.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, List of co-author objects data on success, WP_Error otherwise
	 */
	public function get_items( $request ) {

		$authors = array();

		//Populate the $author_terms(), so that we can pull out the applicable 'author-users'
		if ( ! empty ( $request['parent_id'] ) ) {

			$parent_id = (int) $request['parent_id'];

			//Get the 'author' terms for this post
			$author_terms = wp_get_object_terms( $parent_id, $this->coauthor_taxonomy );
		} else {
			//Get all 'author' terms
			$author_terms = get_terms( $this->coauthor_taxonomy );
		}


		//We have terms, go get the users
		foreach ($author_terms as $author_term) {

			//Get the user that matches the term
			$coauthor = $this->CoAuthors_Plus->get_coauthor_by('user_login', $author_term->name, true);

			if ( ! $coauthor ) {
				continue;
			}

			$authors[] = $coauthor;
		}


		foreach ( $authors as $author ) {

			$author_post_item = $this->prepare_item_for_response( $author, $request );

			if ( is_wp_error( $author_post_item ) ) {
				continue;
			}

			if ( ! empty( $author_post_item ) ) {
				$author_users[] = $this->prepare_response_for_collection( $author_post_item );
			}
		}


		//Collected the users, return them
		if ( ! empty( $author_users ) ) {
			return rest_ensure_response( $author_users );
		}

		return new WP_Error( 'rest_co_authors_get_users', __( 'Invalid authors id.' ), array( 'status' => 404 ) );
	}


	/**
	 * Retrieve guest-authors object.
	 * (used by create_item() to immediately confirm creation)
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, co-authors object data on success, WP_Error otherwise
	 */
	public function get_item( $request ) {
		if ( ! empty($request['id']) ) {
			return $this->get_item_by('id', $request);
		}
		if ( ! empty($request['user_login']) ) {
			return $this->get_item_by('user_login', $request);
		}
		if ( ! empty($request['display_name']) ) {
			return $this->get_item_by('display_name', $request);
		}
		return new WP_Error( 'rest_no_route', __( 'No route was found matching the URL and request method: use discovery to identify correct query paths for author-posts.' ), array( 'status' => 404 ) );
	}


	/**
	 * Retrieve co-authors user object.
	 *
	 * @param string $key
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, co-authors object data on success, WP_Error otherwise
	 */
	public function get_item_by( $key, $request ) {
		$co_authors_value = $request[$key];
		$coauthor = false;

		//Ensure 'ID' is in the correct case (inconsistent)
		if ( 'id' == $key ) {
			$key = 'ID';
		}

		if ( ! empty ( $request['parent_id'] ) ) {

			$parent_id = (int) $request['parent_id'];

			//Get the requested coauthor
			$requested_coauthor = $this->CoAuthors_Plus->get_coauthor_by($key, $co_authors_value, true);

			//Get the 'author' terms for this post
			$author_terms = wp_get_object_terms( $parent_id, $this->coauthor_taxonomy );

			// Ensure that the requested co_authors_id is a co-author of this post
			// if none of its authors has this ID, it is invalid
			foreach ($author_terms as $author_term) {

				if (  $author_term->name == $requested_coauthor->user_login ) {
					//We found the 'author-user'
					$coauthor = $requested_coauthor;
					break;
				}
			}
		} else {
			//Get this 'author-user'
			$coauthor = $this->CoAuthors_Plus->get_coauthor_by($key, $co_authors_value, true);
		}

		if ( ! $coauthor ) {
			return new WP_Error( 'rest_co_authors_get_user', __( 'Invalid authors ' . $key . '.' ), array( 'status' => 404 ) );
		}

		$author_post_item = $this->prepare_item_for_response( $coauthor, $request );

		if ( is_wp_error( $author_post_item ) ) {
			return new WP_Error( 'rest_co_authors_get_user', __( 'Invalid authors ' . $key . '.' ), array( 'status' => 404 ) );
		}

		if ( ! empty( $author_post_item ) ) {
			return rest_ensure_response( $author_post_item );
		}

		return new WP_Error( 'rest_co_authors_get_user', __( 'Invalid authors ' . $key . '.' ), array( 'status' => 404 ) );
	}


	/**
	 * Prepares co-authors data for return as an object.
	 * Used to prepare the guest-authors object
	 *
	 * @param stdClass|WP_Post $data
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error, co-authors object data on success, WP_Error otherwise
	 */
	public function prepare_item_for_response( $data, $request ) {
		$author_user = array(
			'id'                => (int) $data->ID,
			'display_name'      => (string) $data->display_name,
			'first_name'        => (string) $data->first_name,
			'last_name'         => (string) $data->last_name,
			'user_login'        => (string) $data->user_login,
			'user_email'        => (string) $data->user_email,
			'linked_account'    => (string) $data->linked_account,
			'website'           => (string) $data->website,
			'aim'               => (string) $data->aim,
			'yahooim'           => (string) $data->yahooim,
			'jabber'            => (string) $data->jabber,
			'description'       => (string) $data->description,
		);


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
