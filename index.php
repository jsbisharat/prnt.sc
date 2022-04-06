<!DOCTYPE html>
<html>
	<head>
		<title>Print Screen Script</title>
		<link rel="icon" href="favicon.ico">
	</head>
	<body>
	
	<h1>Print Screen Script</h1>

	<?php

		if( !class_exists( 'PrntScGrabber' ) ) {

			class PrntScGrabber {

				/** 
				 * Declare class-accessible global variables
				 */
				private $prntsc_urls_master_file;
				private $prntsc_html_output;
				private $prntsc_string;
				private $duplicate_prntsc_url_check;
				private $original_sites;
				private $https_protocol;
				private $parent_images_directory;
				private $response;
				private $confirmation;

				/**
				 *  Assign values to the class-accessible global variables
				 */
				function __construct() {

					$this->prntsc_urls_master_file = "prntsc_url/prntsc_urls_master_file.txt";
					$this->prntsc_html_output = "prntsc_html/prntsc_html_output.txt";
					$this->prntsc_string = "";
					$this->duplicate_prntsc_url_check = false;
					$this->original_sites = [ "imgur/" => "//i.imgur.com", "image-prntsc/" => "//image.prntscr.com", "st-prntsc/" => "//st.prntsc.com", "st-prntscr/" => "//st.prntscr.com" ];
					$this->https_protocol = "https:";
					$this->parent_images_directory = "images/";
					$this->response = "";
					$this->confirmation = "The screenshot was removed.";
				}

				/**
				 * Create a random 6-character alphanumeric string
				 */
				public function generate_random_url_string() {
					
					// These are the only permitted characters to generate a URL from
					$permitted_characters = '0123456789abcdefghijklmnopqrstuvwxyz';

					/**
					 * 	mb_substr() performs a multi-byte safe substr() operation based on number of characters.
					 * 	substr( string, start, length, encoding )
					 * 	-
					 * 	str_shuffle() randomly shuffles all the characters of a string.
					 * 	str_shuffle( string )
					 */

					$chosen_string = mb_substr( str_shuffle( $permitted_characters ), 0, 6, 'utf-8' );

					while( mb_substr( $chosen_string, 0, 1 ) === '0' ) {

						// Regenerate string if first character of original string begins with 0
						$chosen_string = mb_substr( str_shuffle( $permitted_characters ), 0, 6, 'utf-8' );
					}

					$this->prntsc_string = "https://prnt.sc/" . $chosen_string;

					return $this->prntsc_string;
				}
				
				/**
				 * Add Prnt.Sc URL to main text file if unique
				 */
				public function add_url_to_file() {

					/* 
					 * Open file for writing ( 'w','r','a' )...
					 * w = write ( this will rewrite everything to write something new )
					 * r = read
					 * a = append
					 */
					$prntsc_urls_list = fopen( $this->prntsc_urls_master_file, 'a+' ) or die( 'Cannot open file:  ' . $this->prntsc_urls_master_file );

					// If the file exists and can be opened
					if( $prntsc_urls_list ) {

						// While there is a line to grab
						while( $prntsc_url_line = fgets( $prntsc_urls_list ) ) {

							// If generated string matches URLs in the text file
							if( $this->prntsc_string == $prntsc_url_line ) {

								// Set variable to true
								$this->duplicate_prntsc_url_check = true;

								// Exit loop
								exit();
							}

							/**
							 * exit while-loop if duplicate is found to be true at any moment
							 * no need to exhaust our resources on wasteful loops
							 * we're not committing the duplicate to memory
							*/ 
							if( $this->duplicate_prntsc_url_check ) {
								exit();
							}
						}
					}

					// If no duplicate URLs were found
					if( !$this->duplicate_prntsc_url_check ) {

						// Write/Append new URL string to end of file
						fwrite( $prntsc_urls_list, $this->prntsc_string . "\n");
					}

					// Close the file
					fclose( $prntsc_urls_list );
				}

				/**
				 * Display Prnt.Sc URL list from main text file
				 */
				public function display_urls_from_file() {

					// Open URL file
					$prntsc_urls_list = fopen( $this->prntsc_urls_master_file, 'a+' ) or die( 'Cannot open file:  ' . $this->prntsc_urls_master_file );

					// Open unordered list
					echo "<ol>";

					// If the file exists and can be opened
					if( $prntsc_urls_list ) {

						// While there is a line/row to grab
						while( $prntsc_url_line = fgets( $prntsc_urls_list ) ) {

							// Display list
							echo "<li>";
								echo "<a href=\"" . $prntsc_url_line . "\" target=\"blank\" rel=\"noreferrer noopener\">" . $prntsc_url_line . "</a>";
							echo "</li>";
						}
					}

					// Close unordered list
					echo "</ol>";

					// Close the file
					fclose( $prntsc_urls_list );
				}

				/**
				 * Save HTML from webpage to text file
				 */
				public function write_webpage_html_to_file() {

					// Initialize cURL function
					$curl = curl_init();

					// Setup cURL URL
					curl_setopt( $curl, CURLOPT_URL,  $this->prntsc_string );
					
					// Open master HTML file
					$prntsc_html_output_file = fopen( $this->prntsc_html_output, "w" ) or die( 'Cannot open file: ' . $this->prntsc_html_output );

					// If the file exists and can be opened
					if( $prntsc_html_output_file ) {

						// The cURL output will be written to this file
						curl_setopt( $curl, CURLOPT_FILE, $prntsc_html_output_file );

						$headers = array(
						   "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
						);

						// Setup cURL Header request
						curl_setopt( $curl, CURLOPT_HTTPHEADER, $headers );

						// Execute cURL command
						$resp = curl_exec( $curl );

						// Close cURL
						curl_close( $curl );
					}
				}

				/**
				 * Extract image source from HTML text file
				 */
				public function extract_image_from_html_file() {

					$prntsc_html = file_get_contents( $this->prntsc_html_output );

					$doc = new DOMDocument();

					@$doc->loadHTML( $prntsc_html );

					$all_image_tags = $doc->getElementsByTagName( 'img' );

					foreach( $all_image_tags as $single_image_tag ) {

						$single_image_id = $single_image_tag->getAttribute( 'id' );

						if( isset( $single_image_id ) && $single_image_id == 'screenshot-image' ) {

							$single_image_source = $single_image_tag->getAttribute( 'src' );

							$single_image_path_info = pathinfo( $single_image_source );

							$single_image_basename = $single_image_path_info[ 'basename' ];

							$single_image_url_id = $single_image_tag->getAttribute( 'image-id' );

							if( $single_image_source ) {

								foreach( $this->original_sites as $this->original_url_directory => $this->original_url ) {
									
									if( strpos( $single_image_source, $this->original_url ) !== false ) {

										echo "DING! DING! - " . $this->original_url . "<br><br>";
										
										$final_directory_image = $this->parent_images_directory . $this->original_url_directory . $single_image_url_id . "/";

										$scan_this_directory = scandir( $this->parent_images_directory . $this->original_url_directory );

										if( strpos( $single_image_source, "//st.prntscr.com" ) !== false ) {

											$single_image_source = $this->https_protocol . $single_image_source;
										}

										$this->confirmation = $this->save_image_to_directory( $scan_this_directory, $final_directory_image, $single_image_source, $single_image_basename, $this->original_url_directory, $single_image_url_id );
									}
								}
							}

							if( isset( $this->confirmation ) && !empty( $this->confirmation ) ) {

								echo $this->confirmation;
							}
						}
					}
				}

				/**
				 * Save image to directory
				 */
				public function save_image_to_directory( $scan_this_directory, $final_directory_image, $single_image_source, $single_image_basename, $original_url_directory, $single_image_url_id ) {

					// cURL image URL to check if exists
					$check_if_image_url_exists = curl_init( $single_image_source );

					// Return no body
					curl_setopt( $check_if_image_url_exists, CURLOPT_NOBODY, true );

					// Execute the check
					if( $image_url_result = curl_exec( $check_if_image_url_exists ) ) {

						$this->response = "I am able to execute: - ";

						// Get the HTTP Code of the URL
						$statusCode = curl_getinfo( $check_if_image_url_exists, CURLINFO_HTTP_CODE );

						// Close cURL
						curl_close( $check_if_image_url_exists );

						switch( $statusCode ) {

							// OK
							case 200:

								$this->response .= "<span style=\"color: #43a047;\">Status Code: " . $statusCode . "</span> - ";
								
								// For each folder within specific directory
								foreach( $scan_this_directory as $folder ) {

									// Check if directory exists or not
									if( !is_dir( $final_directory_image ) ) {

										// cURL the image
										$ch = curl_init( $single_image_source );

										// Make the directory
										mkdir( $final_directory_image, 0755, true );

										$save_image_here = fopen( $final_directory_image . $single_image_basename, 'wb' ) or die( 'Cannot open file: ' . $final_directory_image . $single_image_basename );

										curl_setopt( $ch, CURLOPT_FILE, $save_image_here );

										curl_setopt( $ch, CURLOPT_HEADER, 0 );

										$result = curl_exec( $ch );

										curl_close( $ch );
										
										fclose( $save_image_here );

										$this->response .= "Image Saved!<br><br>";
										
										$this->response .= "Original URL: <a href=\"https://prnt.sc/" . $single_image_url_id . "\" target=\"blank\" rel=\"noreferrer noopener\">https://prnt.sc/" . $single_image_url_id . "</a><br>";
										$this->response .= "Source URL: <a href=\"" . $single_image_source . "\" target=\"blank\" rel=\"noreferrer noopener\">" . $single_image_source . "</a><br><br>";

										// $this->response .= "<img src=\"" . $single_image_source . "\" alt=\"Single Image Source\" crossorigin=\"anonymous\"><br><br>";
										$this->response .= "The image, <em>" . $single_image_basename . "</em>, was saved here: <strong>" . $original_url_directory . " => " . $single_image_url_id . "</strong>";
									}
								}
								break;

							// Temporary Redirect
							case 302:

								$this->response .= "<span style=\"color: #cc0000;\">Status Code: " . $statusCode . "</span> - ";
								$this->response .= "Image <strong>NOT</strong> Saved!<br>";

								$this->response .= "<p style=\"color: #cc0000;\">Error " . $statusCode . ": Temporary Redirect.</p>";
								$this->response .= "The Original URL, <a href=https://prnt.sc/" . $single_image_url_id . " target=\"blank\" rel=\"noreferrer noopener\">https://prnt.sc/" . $single_image_url_id . "</a> is bogus.<br><br>";
								$this->response .= "The Source URL, <a href=\"" . $single_image_source . "\" target=\"blank\" rel=\"noreferrer noopener\">" . $single_image_source . "</a>, image could not be retrieved,<br>";
								
								$this->response .= "The full image, <em>" . $single_image_source . "</em>, could not saved.<br>";
								$this->response .= "The directory, <em>" . $single_image_url_id . "</em>, was not created.<br>";
								break;

							// Forbidden
							case 403:

								$this->response .= "<span style=\"color: #cc0000;\">Status Code: " . $statusCode . "</span> - ";
								$this->response .= "Image <strong>NOT</strong> Saved!<br>";

								$this->response .= "<p style=\"color: #cc0000;\">Error " . $statusCode . ": Nginx - Forbidden.</p>";
								$this->response .= "The Original URL, <a href=https://prnt.sc/" . $single_image_url_id . " target=\"blank\" rel=\"noreferrer noopener\">https://prnt.sc/" . $single_image_url_id . "</a> is bogus.<br><br>";
								$this->response .= "The Source URL, <a href=\"" . $single_image_source . "\" target=\"blank\" rel=\"noreferrer noopener\">" . $single_image_source . "</a>, image could not be retrieved,<br>";
								
								$this->response .= "The full image, <em>" . $single_image_basename . "</em>, could not saved.<br>";
								$this->response .= "The directory, <em>" . $single_image_url_id . "</em>, was not created.<br>";
								break;

							// Doesn't exist
							case 404:

								$this->response .= "<span style=\"color: #cc0000;\">Status Code: " . $statusCode . "</span> - ";
								$this->response .= "Image <strong>NOT</strong> Saved!<br>";

								$this->response .= "<p style=\"color: #cc0000;\">Error " . $statusCode . ": URL Does Not Exist.</p>";
								$this->response .= "The Original URL, <a href=https://prnt.sc/" . $single_image_url_id . " target=\"blank\" rel=\"noreferrer noopener\">https://prnt.sc/" . $single_image_url_id . "</a> is bogus.<br><br>";
								$this->response .= "The Source URL, <a href=\"" . $single_image_source . "\" target=\"blank\" rel=\"noreferrer noopener\">" . $single_image_source . "</a>, image could not be retrieved,<br>";
								
								$this->response .= "The full image, <em>" . $single_image_basename . "</em>, could not saved.<br>";
								$this->response .= "The directory, <em>" . $single_image_url_id . "</em>, was not created.<br>";
								break;
							
							default:

								$this->response .= "Status Code: " . $statusCode . "<br>";
								$this->response .= "There's no status error <em>per say</em>, but if you are seeing this, then you done messed up somewhere.<br>";
								$this->response .= "Anyways, here's the status code: " . $statusCode;
								break;
						}
					}
					else {

						$this->response = "cURLing the <strong>" . $single_image_source . "</strong> a.k.a., <em>Image URL</em>, returned an error.<br>";
						$this->response .= "You can at least start debugging from here.<br>";
					}

					return $this->response;
				}
			}
		}

		// Initiate new object
		$prntsc_grabber = new PrntScGrabber();

		// Generate random 6-character URL string
		// Pass return value to $prntsc_string variable
		$prntsc_grabber->generate_random_url_string();

		// Append Prnt.SC URL to text file if unique
		$prntsc_grabber->add_url_to_file();

		// Output file onto screen
		$prntsc_grabber->display_urls_from_file();

		// CURL Prnt.SC URL
		// Add generated HTML File to Output File
		$prntsc_grabber->write_webpage_html_to_file();

		// Grab image source from HTML file
		// Save image to directory
		$prntsc_grabber->extract_image_from_html_file();
	?>

	</body>
</html>