<?php
/**
 * Class Name: WP_REST_CoAuthors_AuthorPosts
 * Author: Michael Jacobsen
 * Author URI: https://mjacobsen4dfm.wordpress.com/
 * License: GPL2+
 *
 * CoAuthors_AuthorPosts base class.
 */

class WP_REST_CoAuthors_AuthorPosts extends WP_REST_Controller {
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
		$this->CoAuthors_Plus = new coauthors_plus ();
		$this->CoAuthors_Guest_Authors = new CoAuthors_Guest_Authors();
	}


	/**
	 * Retrieve guest-authors posts for object.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, List of co-author objects data on success, WP_Error otherwise
	 */
	public function get_items( $request ) {

		if ( ! empty( $request['parent_id'] ) ) {
			$parent_id = (int) $request['parent_id'];

			//Get the coauthor posts for this post
			$authors = get_coauthors($parent_id);
		} else {
			//Get all coauthor posts
			$author_terms = get_terms( $this->CoAuthors_Plus->coauthor_taxonomy );
			$authors = array();
			foreach ( $author_terms as $author_term ) {
				if ( false === ( $coauthor = $this->CoAuthors_Plus->get_coauthor_by( 'user_login', $author_term->name ) ) ) {
					continue;
				}

				$authors[ $author_term->name ] = $coauthor;

				$authors[ $author_term->name ]->post_count = $author_term->count;
			}

			$authors = apply_filters( 'coauthors_wp_list_authors_array', $authors );
		}


		foreach ( $authors as $author ) {

			$author_post_item = $this->prepare_item_for_response( $author, $request );

			if ( is_wp_error( $author_post_item ) ) {
				continue;
			}

			if ( ! empty( $author_post_item ) ) {
				$author_posts[] = $this->prepare_response_for_collection( $author_post_item );
			}
		}

		if ( ! empty( $author_posts ) ) {
			return rest_ensure_response( $author_posts );
		}

		return new WP_Error( 'rest_co_authors_get_posts', __( 'Invalid authors id.' ), array( 'status' => 404 ) );
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
	 * Retrieve guest-authors object.
	 * (used by create_item() to immediately confirm creation)
	 *
	 * @param string $key
	 * @param WP_REST_Request $request
	 * @return WP_REST_Request|WP_Error, co-authors object data on success, WP_Error otherwise
	 */
	public function get_item_by( $key, $request ) {
		$co_authors_value = $request[$key];

		//Ensure 'ID' is in the correct case (inconsistent)
		if ( 'id' == $key ) {
			$key = 'ID';
		}

		// See if this request has a parent
		if ( ! empty( $request['parent_id'] ) ) {

			$parent_id = (int) $request['parent_id'];
			$authors = get_coauthors($parent_id);

			// Ensure that the requested co_authors_id is a co-author of this post
			// if none of its authors has this ID, it is invalid
			foreach ( $authors as $author ) {

				if ( $co_authors_value == $author->$key ) {
					$author_post_item = $this->prepare_item_for_response( $author, $request );

					if ( is_wp_error( $author_post_item ) ) {
						return new WP_Error( 'rest_co_authors_get_post', __( 'Invalid authors ' . $author->$key . '.' ), array( 'status' => 404 ) );
					}

					if ( ! empty( $author_post_item ) ) {
						return rest_ensure_response( $author_post_item );
					}
				}
			}
		} else {

			$author_post = $this->CoAuthors_Guest_Authors->get_guest_author_by( $key, $co_authors_value, 'true' );

			if ( ! $author_post ) {
				return new WP_Error( 'rest_co_authors_get_post', __( 'Invalid authors ' . $key . '.' ), array( 'status' => 404 ) );
			}

			$author_post_item = $this->prepare_item_for_response( $author_post, $request );

			if ( is_wp_error( $author_post_item ) ) {
				return new WP_Error( 'rest_co_authors_get_post', __( 'Invalid authors ' . $key . '.' ), array( 'status' => 404 ) );
			}

			if ( ! empty( $author_post_item ) ) {
				return rest_ensure_response( $author_post_item );
			}
		}

		return new WP_Error( 'rest_co_authors_get_post', __( 'Invalid authors ' . $key . '.' ), array( 'status' => 404 ) );
	}



	/**
	 * Create a map to search the description field
	 *
	 * $ajax_search_fields was taken from Automattic/Co-Authors-Plus/../co-authors-plus.php
	 *
	 * @param WP_TERM $term
	 * @return array $searchmap
	 */
	public function set_searchmap( $term ) {
		//This didn't work, some names break the pattern (i.e. "salisbury William S. Salisbury salisbury 87 bsalisbury@pioneerpress.com")
		$ajax_search_fields = array( 'display_name', 'first_name', 'last_name', 'user_login', 'ID', 'user_email' );
		$co_authors_values = explode( ' ', $term->description );
		if ( 5 == count( $co_authors_values ) ) {
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
	 * @param stdClass|WP_Post $data guest-authors post_type post row from database
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error, co-authors object data on success, WP_Error otherwise
	 */
	public function prepare_item_for_response( $data, $request ) {
		$author_post = array(
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


		$response = rest_ensure_response( $author_post );

		/**
		 * Add information links about the object
		 */
		$response->add_link( 'about', rest_url( $this->namespace . '/' . $this->rest_base . '/' . $author_post['id'] ), array( 'embeddable' => true ) );

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
