<?php
/**
* A simple example class to get data from E-Careers API
*
* Prerequisites, Authentication & Response Handling.
*
* 	- All requests to e-Careers API require you to authenticate yourself to the service.
* 	- In order to do this you must send the correct pre-shared API token along with the required method. 
*	- You can only authenticate with a pre-approved server IP Address, you cannot access the API from other IP address unless you have sent E-Careers your IP address to be approved.
*	- This class uses file_get_contents() to retrieve the data, this function requires the "allow_url_fopen" php module, you may need to enable this in your php.ini.
*	- This class will parse JSON responses, if using XML you will need to modify how the responses are handled, i.e.	DOMDocument() : http://php.net/manual/en/class.domdocument.php;
*	- The endpoint to request data source URLs is capped at a maximum of 5 times in a day for each method
*/
class E_Careers_API {

	// Define class properties
	private $api_key;
	public $data_source;
	public $product_data_source;
	public $category_data_source;
	public $e_careers_products_array;
	public $e_careers_categories_array;
	public $diagnostics;


	/**
	* Initialize class and prepare its properties.
	*/
	public function __construct() {
		
		$this->api_key = '';											// Set your API key
		$this->data_source = 'https://api.e-careers.com/client/list';	// The endpoint to request data source URLs, capped at a maximum of 5 times in a day for each method
		$this->product_data_source = NULL;								// Data source URL for products
		$this->category_data_source = NULL;								// Data source URL for categories
		$this->e_careers_products_array = array();						// Array for courses
		$this->e_careers_categories_array = array();					// Array for categories
		$this->diagnostics = true;										// Diagnostics flag for outputting response(s)
	}
	
	
	
	/**
	* Set up request parameters, context & headers, make a POST request to E-Careers product list data endpoint
	*
	* !! $options->'http'->'header' content MUST BE IN DOUBLE QUOTES !!
	*/
	public function get_product_list_url() {
		
		// Prepare request parameters
		$params = array(
			'apikey'	=> $this->api_key,
			'method'	=> 'productlist',
			'newlist'	=> '1'
		);
		
		// Prepare headers
		$options = array(
					'http' => array(
						'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
						'method'  => 'POST',
						'timeout' => '60',
						'content' => http_build_query( $params )
					)
				);
		  
		// Prepare request context		
		$context = stream_context_create( $options );
	
		// Send request
		$result = file_get_contents( $this->data_source, false, $context );
		
		// JSON decode result
		$payload = json_decode( $result );
		
		// Check response if diagnostic flag is true
		if ( $this->diagnostics ) {
			 echo '<pre>';
			 echo '<h1>', get_class( $this ), '->', __FUNCTION__, ' response:</h1>';
		 	print_r( $payload );
		 	echo '</pre>';
		 	echo '<hr>';
		 }

		// Handle successful JSON response
		if ( $payload->Result == 'Success' ) {
			
			$this->product_data_source = $payload->ProductURL;
		
		} else if ( $payload->Result == 'Failure' ) {
			 
			 $this->product_data_source = NULL;
		}
	
		// Return $this->product_data_source
		return $this->product_data_source;
	}
	
	
	
	/**
	* Set up request parameters, context & headers, make a POST call to E-Careers category list data endpoint
	*
	* !! $options->'http'->'header' content MUST BE IN DOUBLE QUOTES !!
	*/
	public function get_category_list_url() {
		
		// Prepare request parameters
		$params = array(
					 'apikey'	 => $this->api_key,
					 'method'	 => 'categorylist',
					 'newlist'	 => '1'
				);
		 
		 // Prepare headers
		 $options 	= array(
						 'http' => array(
							 'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
							 'method'  => 'POST',
							 'timeout' => '60',
							 'content' => http_build_query( $params )
						 )
					);
		 
		 // Prepare request context
		 $context = stream_context_create( $options );
	
		// Send request
		 $result = file_get_contents( $this->data_source, false, $context );
		 
		 // JSON decode result
		 $payload = json_decode( $result );
		 
		 // Check response if diagnostic flag is true
		if ( $this->diagnostics ) {
			echo '<pre>';
			echo '<h1>', get_class( $this ), '->', __FUNCTION__, ' response:</h1>';
			print_r( $payload );
			echo '</pre>';
			echo '<hr>';
		}
		
		// Handle succesful response, else mark $this->category_data_source as NULL
		if ( $payload->Result == 'Success' ) {
			
			$this->category_data_source = $payload->CategoryURL;
		
		} else if ( $payload->Result == 'Failure' ) {
		
			$this->category_data_source = NULL;
		}
		
		// Return $this->category_data_source
		return $this->category_data_source;
	}

	
	
	/**
	* Fetch JSON product list from product list url
	*/
	public function get_product_list( $product_data_endpoint ) {
		
		// Check endpoint argument is present
		if ( !empty( $product_data_endpoint ) ) {
			
			// Send request
			$e_careers_products_result	= file_get_contents( $product_data_endpoint );

			// JSON decode result
			$e_careers_products_payload = json_decode( $e_careers_products_result );
			
			// Check payload
			if ( !empty( $e_careers_products_payload ) ) {
			
				// Check total_products content
				if ( !empty( $e_careers_products_payload->total_products ) ) {
					
					// Loop through each product object
					foreach ( $e_careers_products_payload->productlist as $product ) {
					
						// Create data array for each product with all course properties - Ternary logic for 'top_level_category' as not present on all course data objects
						$e_learning_course = array(
							'name' 					=> htmlentities( $product->name ),
							'top_level_category'	=> isset( $product->top_level_category ) ? $product->top_level_category : NULL,
							'category'				=> $product->category,
							'product_code'			=> $product->productcode,
							'currency'				=> $product->currency,
							'product_price'			=> $product->product_price,
							'site_price'			=> $product->site_price,
							'discount'				=> $product->discount,
							'course_duration'		=> $product->course_duration,
							'image'					=> $product->image,
							'product_url'			=> $product->product_url
						);
						
						$this->e_careers_products_array[] = $e_learning_course;
					}
					
				} else {
					
					$this->e_careers_products_array = NULL;
				}
			}
		
		} else {

			$this->e_careers_products_array = NULL;
		}
		
		// Return $this->e_careers_products_array
		return $this->e_careers_products_array;
	}
	
	
	
	/**
	* Fetch JSON product category list from category list url
	*/
	public function get_product_category_list( $category_data_endpoint ) {
		
		// Check endpoint argument is present
		if ( !empty( $category_data_endpoint ) ) {
			
			// Send request
			$e_careers_categories_result	= file_get_contents( $category_data_endpoint );
			
			// JSON decode result
			$e_careers_categories_payload	= json_decode( $e_careers_categories_result );
			
			// Check payload
			if ( !empty( $e_careers_categories_payload ) ) {
			
				// Check categorylist content
				if ( !empty( $e_careers_categories_payload->categorylist ) ) {

					// Loop through each category object
					foreach ( $e_careers_categories_payload->categorylist as $category ) {
						
						$e_careers_category = array(
							'name' 			=> htmlentities( $category->name ),
							'categorycode'	=> $category->categorycode,
							'subcategories'	=> array()
						);
						
						// Check for subcategories, loop through if exists, else mark as NULL
						if ( isset( $category->subcategories ) ) {
							
							foreach ( $category->subcategories as $subcategory ) {
								
								$e_careers_category['subcategories'][] = array(
									'name'			=> htmlentities( $subcategory->name ),
									'categorycode'	=> $subcategory->categorycode
								);
							}
						
						} else {
							
							$e_careers_category['subcategories'] = NULL;
						}
						
						// Add each category to $this->e_careers_categories_array
						$this->e_careers_categories_array[] = $e_careers_category;
					}
					
				} else {
					
					$this->e_careers_categories_array = NULL;
				}
			}
		
		} else {
			
			$this->e_careers_categories_array = NULL;
		}
		
		// Return $this->e_careers_categories_array
		return $this->e_careers_categories_array;
	}
	
	
	
	/**
	* Fetch JSON product list from product list url
	*/
	public function fetch_e_careers_courses() {
			
		// Request new category data endpoint if neccessary
		if ( empty( $this->category_data_source ) ) {
			 
			// Request new products_url from E-Careers API
			$category_list_url 	= $this->get_category_list_url();
			$categories 		= $this->get_product_category_list( $category_list_url );
		
		} else {
			 
			$this->get_product_category_list( $this->category_data_source );
		}
		
		// Request new products data endpoint if neccessary
		if ( empty( $this->product_data_source ) ) {

			$product_list_url 	= $this->get_product_list_url();
			$products 			= $this->get_product_list( $product_list_url );
	 
		} else {
	 	
			$this->get_product_list( $this->product_data_source );
		}
	}
	
	
	
	/**
	* Clean up & unset class properties
	*/
	public function __destruct() {
		
		// Unset objects in memory ready for garbage collection
		unset(
			$this->api_key,
			$this->data_source,
			$this->product_data_source,
			$this->category_data_source,
			$this->e_careers_products_array,
			$this->e_careers_categories_array,
			$this->diagnostics
		);
	}
}



// Get the E-Careers party started...
$e_careers_data = new E_Careers_API();

// Call fetch_e_careers_courses() class method to fetch data
$e_careers_data->fetch_e_careers_courses();


// Set diagnostics class variable to true to see the response output
if ( $e_careers_data->diagnostics ) {
	
	echo '<pre>';
	echo '<h1>$e_careers_data->e_careers_categories_array : ', count( $e_careers_data->e_careers_categories_array ), ' Results</h1>';
	print_r($e_careers_data->e_careers_categories_array);
	echo '</pre>';
	
	echo '<hr>';
	
	echo '<pre>';
	echo '<h1>$e_careers_data->e_careers_products_array : ', count( $e_careers_data->e_careers_products_array ), ' Results</h1>';
	print_r( $e_careers_data->e_careers_products_array );
	echo '</pre>';

}


// Example of accessing course object properties, in this case the first course in "e_careers_products_array" array (Index of zero)
if ( !empty( $e_careers_data->e_careers_products_array ) ) {
	echo '<hr>';
	echo '<img src="', $e_careers_data->e_careers_products_array[0]["image"], '">';
	echo '<p>Name : ', $e_careers_data->e_careers_products_array[0]["name"], '<p>';
	echo '<p>Product Code : ', $e_careers_data->e_careers_products_array[0]["product_code"], '<p>';
	
	if ( !empty( $e_careers_data->e_careers_products_array[0]["top_level_category"] ) ) {
		echo '<p>Category : ', $e_careers_data->e_careers_products_array[0]["top_level_category"], '<p>';
	}
	
	echo '<p>Subcategory : ', $e_careers_data->e_careers_products_array[0]["category"], '<p>';
	echo '<p>Duration : ', $e_careers_data->e_careers_products_array[0]["course_duration"], '<p>';
	echo '<p>Product URL : ', $e_careers_data->e_careers_products_array[0]["product_url"], '<p>';
	echo '<p>Currency : ', $e_careers_data->e_careers_products_array[0]["currency"], '<p>';
	echo '<p>Product price : ', $e_careers_data->e_careers_products_array[0]["product_price"], '<p>';
	echo '<p>Site price : ', $e_careers_data->e_careers_products_array[0]["site_price"], '<p>';
	echo '<p>Discount : ', $e_careers_data->e_careers_products_array[0]["discount"], '%', '<p>';
}
