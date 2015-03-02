<?php
/*
	The RPiDS Settings class
	This class does everything involving the database, options, etc. All pages and APIs use this class.
	Since: 2.0
*/
class rpids_settings {
	// ******* LOCATION FUNCTIONS *******
	/*
		Function to return the locations
		Since: 2.0
	*/
	public function all_locations() {
		// Globals
		global $wpdb;
		
		// Load the location from the db
		$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_locations` ORDER BY `location` ASC;";
		$sqlret = $wpdb->get_results( $sql, ARRAY_A );
		
		// Set the return array
		$return = new StdClass();
		
		// Loop through each result and process
		foreach( $sqlret as $location ) {
			// Group IDs are stored as an array. Make sure it is actually an array.
			$location['groups'] = rpids_unserialize( $location['groups'] );
			
			// Add location info to an array
			$return->$location['id'] = new StdClass();
			$return->$location['id']->id = $location['id'];
			$return->$location['id']->name = $location['location'];
			$return->$location['id']->groups = (object) $location['groups'];
			$return->$location['id']->weather_id = $location['weather_id'];
			$return->$location['id']->layout_id = $location['layout_id'];
		}
		
		// Return everything as an object
		return $return;
	}
	
	/*
		Function to get info for a single location
		Since: 2.0
	*/
	function get_location( $location = '' ) {
		global $wpdb;
		
		try {
			// Sanitize
			if( $location != '' ) {
				$location = sanitize_text_field( $location );
			} else {
				//throw new Exception( 'Location ID not provided.' );
                return false;
			}
			
			// Select the location in the db
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_locations` WHERE `id`='" . $location . "';";
			$location = $wpdb->get_row( $sql, ARRAY_A );
			
			// Make sure the location exists
			if( $wpdb->num_rows == 0 ) {
				throw new Exception( 'Location does not exist.' );
			}
			
			// Group IDs are stored as an array. Make sure it is actually an array.
			$location['groups'] = rpids_unserialize( $location['groups'] );
		
			$return = array(
				"location" => $location['location'],
				"groups" => $location['groups'],
				"weather_id" => $location['weather_id'],
				"layout" => $location['layout']
			);
			
			return $return;
			
		} catch ( Exception $e ) {
			return array(
				"status" => "error",
				"message" => $e->getMessage()
			);
		}
	}
	
	/*
		Function to add a location
		Since: 2.0
	*/
	public function add_location( $location = '', $groups = '', $zip_code = '', $layout_id = '' ) {
		global $wpdb;

		// We'll use a try/catch block to make sure we only add the location if all needed info is provided
		try {
			// Check for the location
			if( @$location != '' ) {
				$location = sanitize_text_field( $location );
			} else {
				throw new Exception('Location name not provided.');
			}
			
			// Check for groups
			if( @$groups != '' || !empty( $groups ) ) {
                $raw_groups = $groups;
                unset( $groups );
                $groups = array();
                foreach( $groups as $group ) {
                    $groups[] = sanitize_text_field( $group );
                }
			} else {
				throw new Exception('No groups selected.');
			}
			
			// The zip code is optional
			if( @$zip_code != '' ) {
				$zip_code = sanitize_text_field( $zip_code );
			} else {
				$zip_code = '';
			}
			
			// The layout is not optional
			if( @$layout != '' ) {
				$layout_id = sanitize_text_field( $datalayout );
			} else {
				$layout_id = '1';
			}
		} catch ( Exception $e ) {
			return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
		}
		
		// We have the needed values, make sure it doesn't already exist
		$sql = "SELECT * FROM `".rpids_tableprefix()."rpids_locations` WHERE `location`='" . $location . "';";
		$sqlret = $wpdb->get_results($sql, ARRAY_A);
		
		if( $wpdb->num_rows > 0 ) {
			return (object) array(
				"status" => "error",
				"message" => 'The location "' . $location . '" already exists.'
			);
		} else {
			// This is a new location
			
			// First add the zip code to the weather db, if set
			if( $zip_code != '' ) {
				// Check if the zip code is already in the weather table
				$sql = "SELECT * FROM `".rpids_tableprefix()."rpids_weather` WHERE `zip`='" . $zip_code . "';";
				$weather = $wpdb->get_row($sql, ARRAY_A);
				
				if( $wpdb->num_rows > 0 ) {
					// The zip already exists, we'll just grab the id
					$weather_id = $weather['id'];
				} else {
					// New zip code, add to the weather table
					$wpdb->insert( rpids_tableprefix() . "rpids_weather", array(
						"zip" => $zip_code
					) );
				
					// Get the weather ID
					$weather_id = $wpdb->insert_id;
				}
			} else {
				$weather_id = 0;
			}	
			
			// Add the location to the db
			$wpdb->insert( rpids_tableprefix() . "rpids_locations", array(
				"location" => $location, 
				"groups" => $groups,
				"weather_id" => $weather_id,
				"layout_id" => $layout_id
			));
			
			// Return the location id
			return array(
				"status" => "success",
				"message" => 'The location "' . $location . '" has been added.',
				"location_id" => $wpdb->insert_id
			);
		}
	}
	
	/*
		Function to modify a location
		Since: 2.0
	*/
	public function modify_location( $id = '', $location = '', $groups = '', $zip_code = '', $layout_id = '' ) {
		// Functionally this is almost identical to add_location, except we update instead of insert and we need the location ID instead of the location name.
		global $wpdb;
		
		try {
			// We'll use a try/catch block to make sure we only add the location if all needed info is provided
			// Check for the location
			if( @$location_id != '' ) {
				$location_id = sanitize_text_field( $location_id );
			} else {
				throw new Exception('Location ID not provided.');
			}
			
			// Check for groups
			if( @$groups != '' || !empty( $groups ) ) {
                $raw_groups = $groups;
                unset( $groups );
                $groups = array();
                foreach( $groups as $group ) {
                    $groups[] = sanitize_text_field( $group );
                }
			} else {
				throw new Exception('No groups selected.');
			}
			
			// The layout is not optional, but if it's blank we'll go with the default
			if( @$layout != '' ) {
				$layout_id = sanitize_text_field( $layout );
			} else {
				$layout_id = '1';
			}
            
			// The zip code is optional
			if( @$zip_code != '' ) {
				$zip_code = sanitize_text_field( $zip_code );
			} else {
				$zip_code = '';
			}
		} catch ( Exception $e ) {
			return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
		}
					
		// First add the zip code to the weather db, if set
		if( $zip_code != '' ) {
			// Check if the zip code is already in the weather table
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_weather` WHERE `zip`='" . $zip_code . "';";
			$weather = $wpdb->get_row( $sql, ARRAY_A );
			
			if( $wpdb->num_rows > 0 ) {
				// The zip already exists, we'll just grab the id
				$weather_id = $weather['id'];
			} else {
				// New zip code, add to the weather table
				$wpdb->insert( rpids_tableprefix() . "rpids_weather", array(
					"zip" => $zip_code
				) );
			
				// Get the weather ID
				$weather_id = $wpdb->insert_id;
			}
		} else {
			$weather_id = 0;
		}	
		
		// Update the location to the db
		$wpdb->update( rpids_tableprefix() . "rpids_locations", array(
			"location" => $location, 
			"groups" => $groups,
			"weather_id" => $weather_id,
			"layout_id" => $layout_id
		), array(
			"id" => $location_id
		));
		
		// Return the message
		return array(
			"status" => "success",
			"message" => 'The location "' . $location . '" has been updated.'
		);
	}
	
	/*
		Function to delete a location
		Since: 2.0
	*/
	public function delete_location( $location_id ) {
		global $wpdb;
		
		// Process the provided id
		try {
			// Check for the location
			if( @$location_id != '' ) {
				$location_id = sanitize_text_field( $location_id; );
			} else {
				throw new Exception( 'Location ID not provided.' );
			}
			
			// Make sure the location exists
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_locations` WHERE `id`='" . $location_id . "';";
			$sqlret = $wpdb->get_results( $sql, ARRAY_A );
			
			if( $wpdb->num_rows != 1 ) {
				throw new Exception( 'The location with the ID ' . $location_id . ' does not exist.' );
			}
			
			// The location exists, delete the location from the DB
            // We do this is a conditional in case we have a problem
			if( !$wpdb->delete( rpids_tableprefix() . "rpids_locations", array( "id" => $location_id ) ) ) {
                throw new Exception( 'Unknown error when removing location ID ' . $location_id );
            } else {
                return (object) array(
				    "status" => "success",
				    "message" => "Location deleted."
                );
            }
		} catch ( Exception $e ) {
			return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
		}
	}
	
	// ******* SCREEN FUNCTIONS *******
	/*	
		Function to return the screens
		Since: 2.0
	*/
	public function all_screens( $location_id = '' ) {
		global $wpdb;
		
		if( $location_id != '' ) {
			// Location is set, we'll get all screens in that location
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_screens` WHERE `location_id`=" . sanitize_text_field( $location_id ) . " ORDER BY `screen` ASC;";
		} else {
			// Select all screens from the db
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_screens` ORDER BY `screen` ASC;";
		}
		
		$sqlret = $wpdb->get_results( $sql, ARRAY_A );
		
		// Set the retun object
		$return = new StdClass();
		
		// Process each screen
		foreach( $sqlret as $screen ) {
			$return->$screen['screen'] = new StdClass();
			$return->$screen['screen']->id = $screen['id'];
			$return->$screen['screen']->name = $screen['screen'];
			$return->$screen['screen']->location_id = $screen['location_id'];
			$return->$screen['screen']->device_id = $screen['device_id'];
			$return->$screen['screen']->layout_id = $screen['layout_id'];
		}
		
		// Return the screens
		return $return;
	}
	
	/*
		Function to return a specific screen
		Since: 2.0
	*/
	public function get_screen( $screen_id = '' ) {
		global $wpdb;
		
		try {
			// Process the screen ID
			if( @$screen_id != '' ) {
				$screen_id = sanitize_text_field( $screen_id );
			} else {
				throw new Exception( 'Screen ID not provided.' );
			}
			
			// Get the screen info
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_screens` WHERE `id`='" . $screen_id . "';";
			$screen = $wpdb->get_row( $sql, ARRAY_A );
            
            if( $screen != null ) {
                // Return the data
                return (object) array(
                    "status" => "success",
                    "id" => $screen['id'],
				    "name" => $screen['screen'],
				    "location_id" => $screen['location_id'],
				    "device_id" => $screen['device_id'],
				    "layout_id" => $screen['layout_id'],
                    "update_data" => $screen['update_data']
                );
            } else {
                // Screen not found
                throw new Exception( 'Screen not found' );
            }
		} catch ( Exception $e ) {
			return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
		}
	}
	
	/*
		Function to add a screen
		Since: 2.0
	*/
	public function add_screen( $screen_name = '', $location_id = '', $did = '', $layout_id = '' ) {
		global $wpdb;
		
		// Sanitize the provided data
		try {
			// The screen name is required
			if( @$screen_name != '' ) {
				$screen_name = sanitize_text_field( $screen_name );
			} else {
				throw new Exception( 'Screen name was not provided.' );
			}
            
			// The location ID is required
			if( @$location_id != '' ) {
				$location_id = sanitize_text_field( $location_id );
			} else {
				throw new Exception( 'Location ID was not provided.' );
			}
            
			// The device ID is not required
			if( @$did != '' ) {
				$did = sanitize_text_field( $did );
			} else {
				$did = '0';
			}
            
			// The layout ID is not required
			if( @$layout_id != '' ) {
				$layout_id = sanitize_text_field( $layout_id );
			} else {
				$layout_id = '1';
			}
            
            // Set $nb_error (non-blocking error)
            $nb_error = '';
			
			// Make sure the screen doesn't already exist
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_screens` WHERE `screen`='" . $screen_name . "';";
			$sqlret = $wpdb->get_results( $sql, ARRAY_A );
            
			if( $wpdb->num_rows > 0) {
				// The screen already exists, return an error
				throw new Exception( 'The screen name "' . $screen_name . '" already exists.' );
			} else {
				// Screen name does not exist yet
				// Check if the device is already registered
				$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_devices` WHERE `did`='" . $did . "';";
				$device = $wpdb->get_row( $sql, ARRAY_A );
                
				if( $wpdb->num_rows == 0 && $did != '0' ) {
					// The device does not exist. Prompt to add the device. We'll save the screen anyway.
                    
					$nb_error .= 'That device ID does not exist on this system. Please add the device ID then edit the newly added screen to use the correct device. ';
                    
                    // Set a default DID
					$device_id = '0';
				} elseif( $device['screen_id'] != 0 ) {
                    // The device has already been assigned to a screen. Set the notice and change the DID to 0.
                    
                    // Get the info for the screen the device is assigned to.
                    $screen_info = $this->get_screen( $device['screen_id'] );
                    
                    $nb_error .= 'That device ID is assigned to the screen '. $screen_info->name . '. To assign that device to this screen first remove it from it\'s current screen. ';
                    
                    // Set a default did
					$device_id = '0';
                } else {
                    $device_id = $device['id'];
                }
				
				// Add the screen
				$wpdb->insert( rpids_tableprefix() . "rpids_screens", array(
					"screen" => $screen_name,
					"location_id" => $location_id,
					"device_id" => $device['id'],
					"layout_id" => $layout_id
				) );
				
                // Create the return array (returned as an object)
				$return array(
					"status" => "success",
				    "message" => "The screen has been added.",
                    "notice" => $nb_error
                }
                
                // Return the return array as an object
                return (object) $return;
			}	
		} catch ( Exception $e ) {
			return array(
				"status" => "error",
				"message" => $e->getMessage()
			);
		}
	}
	
	/*
		Function to update a screen
		Since: 2.0
	*/
	public function update_screen( $screen_id = '', $screen_name = '', $location_id = '', $did = '', $layout_id = '' ) {
        global $wpdb;
		
		// Sanitize the provided data
		try {
            // The screen ID is required
            if( @$screen_id != '' ) {
                $screen_id = sanitize_text_field( $screen_id );
            } else {
                throw new Exception( 'Screen ID was not provided.' );
            }
            
			// The screen name is required
			if( @$screen_name != '' ) {
				$screen_name = sanitize_text_field( $screen_name );
			} else {
				throw new Exception( 'Screen name was not provided.' );
			}
            
			// The location ID is required
			if( @$location_id != '' ) {
				$location_id = sanitize_text_field( $location_id );
			} else {
				throw new Exception( 'Location ID was not provided.' );
			}
            
			// The device ID is not required
			if( @$did != '' ) {
				$did = sanitize_text_field( $did );
			} else {
				$did = '0';
			}
            
			// The layout ID is not required
			if( @$layout_id != '' ) {
				$layout_id = sanitize_text_field( $layout_id );
			} else {
				$layout_id = '1';
			}
            
            // Set $nb_error (non-blocking error)
            $nb_error = '';
			
			// Make sure the screen doesn't already exist
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_screens` WHERE `id`='" . $screen_id . "';";
			$screen_info = $wpdb->get_results( $sql, ARRAY_A );
            
			if( $wpdb->num_rows == 0 ) {
				// The screen does not exist, return an error
				throw new Exception( 'That screen does not exist.' );
			} else {
				// Screen exists
				// Check if the device is already registered
				$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_devices` WHERE `id`='" . $did . "';";
				$device = $wpdb->get_row( $sql, ARRAY_A );
                
				if( $device == null && $did != '0' ) {
					// The device does not exist. Prompt to add the device. We'll save the screen anyway.
                    
					$nb_error .= 'That device ID does not exist on this system. Please add the device ID then edit the newly added screen to use the correct device. ';
                    
                    // Set a default DID
					$device_id = '0';
				} elseif( $device['screen_id'] != 0 ) {
                    // The device has already been assigned to a screen. Is it this one?
                    if( $device['screen_id'] != $screen_info['did'] ) {
                        // Get the info for the screen the device is assigned to.
                        $screen_info = $this->get_screen( $device['screen_id'] );
                        
                        $nb_error .= 'That device ID is assigned to the screen '. $screen_info->name . '. To assign that device to this screen first remove it from it\'s current screen. ';
                        
                        // Set a default did
					   $device_id = '0';
                    }
                } else {
                    $device_id = $device['id'];
                }
				
				// Update the screen
				$wpdb->update( rpids_tableprefix() . "rpids_screens", array(
					"screen" => $screen_name,
					"location_id" => $location_id,
					"device_id" => $device_id,
					"layout_id" => $layout_id
				),
				array(
					"id" => $screen_id
				));
				
                // Create the return array (returned as an object)
				$return array(
					"status" => "success",
				    "message" => "The screen has been updated.",
                    "notice" => $nb_error
                }
                
                // Return the return array as an object
                return (object) $return;
			}	
		} catch ( Exception $e ) {
			return array(
				"status" => "error",
				"message" => $e->getMessage()
			);
		}
    }
	
	/*
		Function to delete a screen
		Since: 2.0
	*/
	public function delete_screen( $screen_id ) {
		global $wpdb;
		
		try {
			// Make sure the screen ID is provided
			if( @$screen_id != '' ) {
				$screen_id = sanitize_text_field( $screen_id );
			} else {
				throw new Exception( 'Screen ID was not provided.' );
			}
			
			// Make sure the screen ID exists
			$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_screens` WHERE `screen`='" . $screen_id . "';";
			$screen_info = $wpdb->get_results($sql, ARRAY_A);
			
			if( $wpdb->num_rows != 1 ) {
				throw new Exception( 'The screen with ID "'.$screen_id . '" does not exist.' );
			}
            
            // No errors! Delete the screen.
            $wpdb->delete( rpids_tableprefix() . "rpids_screens",
                array(
                    "id" => $screen_id
                )
            );
            
            // Set the device as unassigned
            $wpdb->update( rpids_tableprefix() . "rpids_devices", array(
					"screen_id" => '0'
				),
				array(
					"id" => $screen_info['id']
				)
            );
            
            return array(
                "status" => "success",
                "message" => 'Screen ID "' . $screen_id . '" has been deleted.'
            );
		} catch ( Exception $e ) {
			return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
		}
	}
	
	/*	
		Function to return the devices
		Since: 2.0
	*/
	public function all_devices() {
		// Global
		global $wpdb;
		
		// Load the devices from the database as an array
		$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_devices` ORDER BY `did` ASC;";
		$devices = $wpdb->get_results( $sql, ARRAY_A );
		
		// Set the return object
		$return = new StdClass();
		
		// Loop through each device
		foreach( $devices as $device ) {
			// Add the device info to the return object
			$return->$device['id'] = new StdClass();
            $return->$device['id']->id = $device['id'];
			$return->$device['id']->did = $device['did'];
			$return->$device['id']->model = $device['model'];
			$return->$device['id']->hversion = $device['hversion'];
			$return->$device['id']->sversion = $device['sversion'];
			$return->$device['id']->type = $device['type'];
			$return->$device['id']->builder = $device['builder'];
		}
		return (object) $return;
	}
	
    /*
        Function to get info for a single device
        Since: 2.0
    */
	public function get_device( $device_id = '' ) {
		global $wpdb;
        
        try {
            // Device ID is required
            if( $device_id != '' ) {
                $device_id = sanitize_text_field( $device_id );
            } else {
                throw new Exception( 'Device ID is not provided.' );
            }
            
            // Get the device data from the database
            $sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_devices` WHERE `id`= '" . $device_id . "';";
            $device = $wpdb->get_row( $sql, ARRAY_A );
            
            // Make sure we've got something
            if( $device == null ) {
                throw new Exception( 'Device not found.' );
            } else {
                // Set the return object
                $return = new StdClass();
                
                // Add the device info to the return object
                $return->status = 'success';
                $return->id = $device['id'];
                $return->did = $device['did'];
                $return->model = $device['model'];
                $return->hversion = $device['hversion'];
                $return->sversion = $device['sversion'];
                $return->type = $device['type'];
                $return->builder = $device['builder'];
                
                return $return;
            }
        } catch ( Exception $e ) {
            return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
        }
	}
    
    /*
        Function to add a device
        Since: 2.0
    */
	public function add_device( $did = '', $model = '', $hversion = '', $sversion = '', $type = '', $builder = '', $reg_device = '' ) {
		global $wpdb;
		global $rpids_api;
        
        try {
            // Process the parameters
            if( @$did != '' ) {
                $did = sanitize_text_field( $did );
            } else {
                $did = '0';
            }
            
            if( @$model != '' ) {
                $model = sanitize_text_field( $model );
            }
            
            if( @$hversion != '' ) {
                $hversion = sanitize_text_field( $hversion );
            }
            
            if( @$sversion != '' ) {
                $sversion = sanitize_text_field( $sversion );
            }
            
            if( @$type != '' ) {
                $type = sanitize_text_field( $type );
            }
            
            if( @$builder != '' ) {
                $builder = sanitize_text_field( $builder );
            }
            
            if( @$reg_device != '' ) {
                $reg_device = sanitize_text_field( $reg_device );
            } else {
                $reg_device = false;
            }
            
            // Get the current user
            $current_user = wp_get_current_user();
            
             if( $reg_device ) {
                 // If $builder is empty, set it to the current user's email
                 if( $builder = '' ) {
                     $builder = $current_user->user_email;
                 }
                 
                 // Register the device with the central server
                 $rpids_register = $rpids_api->register_device( $model, $hversion, $sversion, $type, $builder );
                    
                // Success? If yes, update the did variable
                if( $rpids_register['status'] == 'success' ) {
                    $did = $rpids_register['did'];
                }
            }
            
            // Should we verify the device and claim it?
            if( $did != '0' ) {
                // DID is set (and not 0)
                // Check if the device is already in the database
                $sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_devices` WHERE `did`='" . $did . "';";
                $device = $wpdb->get_row($sql, ARRAY_A);
                
                if( $device != null ) {
                    // Device has already been added
                    throw new Exception( 'That device has already been added.' );
                } else {
                    // Connect to the central server and claim the device
                    $rpids_return = $rpids_api->claim_device( $did, $current_user->user_email );
                    // Why email? If the device is given away or sold, but not removed from an account, we need to know who to contact for the new owner.
                    
                    // Check the status of $rpids_return
                    if( $rpids_return['status'] == 'otheruser') {
                        // The device is claimed by someone else
                        throw new Exception( 'That device ID is already claimed. If you received the device used make sure the previous owner has transferred ownership.' );
                    } elseif( $rpids_return['status'] == 'notfound') {
                        // DID not found
                        throw new Exception( 'That Device ID was not found. The device must be registered before you can add it.');
                    } elseif( $rpids_return['status'] != 'success' ) {
                        // Other error
                        throw new Exception( 'Well that\'s strange. Something went wrong, but we have no clue what.' );
                    }
                    
                    // Looks like status == success
                    // Get the device details
                    $rpids_results = $rpids_api->get_device_info( $did );
                    
                    // Update the device info variables
                    $model = sanitize_text_field( $rpids_results['model'] );
                    $hversion = sanitize_text_field( $rpids_results['hversion'] );
                    $sversion = sanitize_text_field( $rpids_results['sversion'] );
                    $type = sanitize_text_field( $rpids_results['type'] );
                    $builder = sanitize_text_field( $rpids_results['builder'] );
                }
            }
            
            // Now, add the device to the database
            $wpdb->insert( rpids_tableprefix() . "rpids_devices",
                array(
                    "did" => $did,
                    "model" => $model,
				    "hversion" => $hversion,
				    "sversion" => $sversion,
				    "type" => $type,
				    "builder" => $builder
                )
            );
            
            // Return success
            if( $reg_device ) {
                return (object) array(
                    "status" => "success",
                    "message" => "Device registered and added."
                );
            }
		} catch ( Exception $e ) {
            return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
        }
	}
        
    /*
        Function to modify a device. Updates both local database and central server.
        Since: 2.0
    */
    public function update_device( $device_id = '', $did = '', $model = '', $hversion = '', $sversion = '', $type = '', $builder = '' ) {
        global $wpdb;
		global $rpids_api;
        
        try {
            // Process the parameters
            if( @$device_id != '' ) {
                $device_id = sanitize_text_field( $device_id );
            } else {
                throw new Exception( 'Device ID is required.' );
            }
            
            if( @$did != '' ) {
                $did = sanitize_text_field( $did );
            } else {
                $did = '0';
            }
            
            if( @$model != '' ) {
                $model = sanitize_text_field( $model );
            }
            
            if( @$hversion != '' ) {
                $hversion = sanitize_text_field( $hversion );
            }
            
            if( @$sversion != '' ) {
                $sversion = sanitize_text_field( $sversion );
            }
            
            if( @$type != '' ) {
                $type = sanitize_text_field( $type );
            }
            
            if( @$builder != '' ) {
                $builder = sanitize_text_field( $builder );
            }
            
            // Get the current user
            $current_user = wp_get_current_user();
            
            // Should we verify the device and claim it?
            $sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_devices` WHERE `id`='" . $device_id . "';";
            $device = $wpdb->get_row($sql, ARRAY_A);
            
            if( $device != null ) {
                // Device does not exist
                throw new Exception( 'Device ID does not exist.' );
            }
            
            if( $device['did'] != '0' ) {
                // Connect to the central server and claim the device
                $rpids_return = $rpids_api->update_device_info( $did, $model, $hversion, $sversion, $type, $builder );
                // Why email? If the device is given away or sold, but not removed from an account, we need to know who to contact for the new owner.                    
                // Check the status of $rpids_return. If it isn't success, change the DID to 0 and quietly fail.
                if( $rpids_return['status'] != 'success' ) {
                    $did = '0';
                }
                
                // Looks like status == success
            }
            
            // Now, update the device in the database
            $new_device_info = array();
            if( @$did != '' ) {
                $new_device_info['did'] = $did;
            }
            
            if( @$model != '' ) {
                $new_device_info['model'] = $model;
            }
            
            if( @$hversion != '' ) {
                $new_device_info['hversion'] = $hversion;
            }
            
            if( @$sversion != '' ) {
                $new_device_info['sversion'] = $sversion;
            }
            
            if( @$type != '' ) {
                $new_device_info['type'] = $type;
            }
            
            if( @$builder != '' ) {
                $new_device_info['builder'] = $builder;
            }
            
            if( !$wpdb->update( rpids_tableprefix() . "rpids_devices", $new_device_info, array( 'id' => $device_id ) ) ) {
                throw new Exception( 'Error when updating the database. New info not saved.' );
            } else {
                return (object) array(
                    "status" => "success",
                    "message" => "Device updated."
                );
            }
		} catch ( Exception $e ) {
            return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
        }
    }
                
    /*
        Function to remove a device. If registered with the central server, will also release the device there.
        Since: 2.0
    */
	public function remove_device( $device_id = '' ) {
		global $wpdb;
		global $rpids_api;
        
        try {
            // Verify the device ID is provided and sanitize
            if( @$device_id != '' ) {
                $device_id = sanitize_text_field( $device_id );
            } else {
                throw new Exception( 'Device ID not provided.' );
            }
            
            // Get the device from the database
            $sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_devices` WHERE `id`='" . $device_id . "';";
            $device = $wpdb->get_row($sql, ARRAY_A);
            
            // If null, throw error, otherwise continue
            if( $device == null ) {
                throw new Exception( 'Device ID not found.' );
            } else {
                // If DID is set, release the device
                if( $device['did'] != '' ) {
                    $result = $rpids_api->release_device( $device['did'] );
                    
                    // Verify
                    if( $result['status'] != 'success' ) {
                        throw new Exception( 'There was a problem releasing the device at the central database. Error: ' . $result['message'] );
                    }
                }
                
                // Delete the device from the db. If false, throw an error.
                if( !$wpdb->delete( rpids_tableprefix() . "rpids_devices", array( "id" => $device_id ) ) ) {
                    throw new Exception( 'There was an error when removing the device from the database.' );
                } else {
                    return (object) array(
                        "status" => "success",
                        "message" => "Device registered and added."
                    );
                }
            }
        } catch ( Exception $e ) {
            return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
        }
	}
	
	/*
		Function to return a list of layouts
		Since: 2.0
	*/
	public function all_layouts() {
		// Globals
		global $wpdb;
		
		// Load the layouts from the database as an array
		$sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_layouts` ORDER BY `name` ASC;";
		$sqlret = $wpdb->get_results( $sql, ARRAY_A );
		
		// Create the return object
		$return = new StdClass();
		
		// Loop through each layout
		foreach( $sqlret as $layout ) {
			// Add the screen info to the return object
			$return->$layout['id'] = new StdClass();
			$return->$layout['id']->name = $layout['name'];
			$return->$layout['id']->id = $layout['id'];
		}
		return $return;
	}
        
    /*
        Function to return a specific layout
        Since: 2.0
    */
    public function get_layout( $layout_id = '' ) {
        // Globals
		global $wpdb;
        
        try {
            // Layout ID is not optional
            if( @$layout_id != '' ) {
                $layout_id = sanitize_text_field( $layout_id );
            } else {
                throw new Exception( 'Layout ID is required.' );
            }
            
            // Get the layout from the database
            $sql = "SELECT * FROM `" . rpids_tableprefix() . "rpids_layouts` WHERE `id` = '" . $layout_id . "';";
            $layout = $wpdb->get_row( $sql, ARRAY_A );
            
            // Make sure we found something
            if( $layout == null ) {
                // Nope
                throw new Exception( 'Layout not found.' );
            } else {
                // Got it! Put it in an object.
                $return = new StdClass();
                $return->status = 'success';
                $return->id = $layout['id'];
                $return->name = $layout['name'];
                $return->description = $layout['description'];
                $return->type = $layout['type'];
                $return->width = $layout['width'];
                $return->height = $layout['height'];
                $return->bgimage = $layout['bgimage'];
                $return->bgimagetype = $layout['bgimagetype'];
                $return->bgcolor = $layout['bgcolor'];
                $return->startimage = $layout['startimage'];
                $return->items = (object) unserialize( $layout['items'] );
                $return->lastmodified = $layout['lastmodified'];
                
                // Return it!
                return $return;
            }
        } catch ( Exception $e ) {
            return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
        }
    }
        
    /*
        Function to add a layout
        Since: 2.0
    */
    public function add_layout( $name = '', $width = '', $height = '', $type = '', $description = '', $bgimage = '', $bgimagetype = '', $bgcolor = '', $startimage = '', $items = '' ) {
        try {
            // Sanitize inputs
            if( @$name != '' ) { // Required
                $name = sanitize_text_field( $name );
            } else {
                throw new Exception( 'Name is required.' );
            }
            
            if( @$width != '' ) { // Required
                $width = sanitize_text_field( $width );
            } else {
                throw new Exception( 'Width is required.' );
            }
            
            if( @$height != '' ) { // Required
                $height = sanitize_text_field( $height );
            } else {
                throw new Exception( 'Height is required.' );
            }
            
            if( @$type != '' ) {
                $type = sanitize_text_field( $type );
            } else {
                throw new Exception( 'Type is required.' );
            }
            
            if( @$description != '' ) {
                $description = sanitize_text_field( $description );
            } else {
                $description = '';
            }
            
            if( @$bgimage != '' ) {
                $bgimage = sanitize_text_field( $bgimage );
            } else {
                $bgimage = '';
            }
            
            if( @$bgimagetype != '' ) {
                $bgimagetype = sanitize_text_field( $bgimagetype );
            } else {
                $bgimagetype = '';
            }
            
            if( @$bgcolor != '' ) {
                $bgcolor = sanitize_text_field( $bgcolor );
            } else {
                $bgcolor = '#000000';
            }
            
            if( @$startimage != '' ) {
                $startimage = sanitize_text_field( $startimage );
            } else {
                $startimage = '';
            }
            
            // 
            $wpdb->insert( rpids_tableprefix() . "rpids_layouts",
                array(
                    "name" => $name,
                    "description" => $description,
                    "type" => $type,
                    "width" => $width,
                    "height" => $height,
                    "bgimage" => $bgimages,
                    "bgimagetype" => $bgimagetype,
                    "bgcolor" => $bgcolor,
                    "startimage" => $startimage,
                    "items" => $items,
                    "lastmodified" => time()
                )
            );
            
            // Return success
            return (object) array(
                "status" => "success",
                "message" => "Layout added."
            );
        } catch ( Exception $e ) {
            return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
        }
    }
    
    /*
        Function to update a template
        Since: 2.0
    */
    public function update_layout( $layout_id = '', $new_values = '' ) {
        try {
            // Sanitize inputs
            if( @$layout_id != '' ) { // Required
                $layout_id = sanitize_text_field( $layout_id );
            } else {
                throw new Exception( 'Layout ID required.' );
            }
            
            if( @$new_values == '' || !is_array( $new_values ) ) { // required
                throw new Exception( 'No new values provided.' );
            }
            
            if( @$new_values['name'] != '' ) {
                $name = sanitize_text_field( $new_values['name'] );
            } else {
                $name = '';
            }
            
            if( @$new_values['width'] != '' ) {
                $width = sanitize_text_field( $new_values['width'] );
            } else {
                $width = '';
            }
            
            if( @$new_values['height'] != '' ) {
                $height = sanitize_text_field( $new_values['height'] );
            } else {
                $height = '';
            }
            
            if( @$new_values['type'] != '' ) {
                $type = sanitize_text_field( $new_values['type'] );
            } else {
                $type = '';
            }
            
            if( @$new_values['description'] != '' ) {
                $description = sanitize_text_field( $new_values['description'] );
            } else {
                $description = '';
            }
            
            if( @$new_values['bgimage'] != '' ) {
                $bgimage = sanitize_text_field( $new_values['bgimage'] );
            } else {
                $bgimage = '';
            }
            
            if( @$new_values['bgimagetype'] != '' ) {
                $bgimagetype = sanitize_text_field( $new_values['bgimagetype'] );
            } else {
                $bgimagetype = '';
            }
            
            if( @$new_values['bgcolor'] != '' ) {
                $bgcolor = sanitize_text_field( $new_values['bgcolor'] );
            } else {
                $bgcolor = '#000000';
            }
            
            if( @$new_values['startimage'] != '' ) {
                $startimage = sanitize_text_field( $new_values['startimage'] );
            } else {
                $startimage = '';
            }
            
            if( @$new_values['items'] != '' && @is_array( $new_values['items'] ) ) {
                $items = array();
                foreach( $new_values['items'] as $item => $data ) {
                    $items[] = array(
                        'type' => sanitize_text_field( $data['type'] ),
                        'id' => sanitize_text_field( $data['id'] ),
                        'top' => sanitize_text_field( $data['top'] ),
                        'left' => sanitize_text_field( $data['left'] ),
                        'width' => sanitize_text_field( $data['width'] ),
                        'height' => sanitize_text_field( $data['height'] )
                    );
                }
            } else {
                $items = array();
            }
            
            // Add the set values to an array
            $new_layout_info = array();
            if( $name != '' ) {
                $new_layout_info['name'] = $name;
            }
            if( $description != '' ) {
                $new_layout_info['description'] = $description;
            }
            if( $type != '' ) {
                $new_layout_info['type'] = $type;
            }
            if( $width != '' {
                $new_layout_info['width'] = $width;
            }
            if( $height != '' ) {
                $new_layout_info['height'] = $height;
            }
            if( $bgimages != '' ) {
                $new_layout_info['bgimage'] = $bgimages;
            }
            if( $bgimagetype != '' ) {
                $new_layout_info['bgimagetype'] = $bgimagetype;
            }
            if( $bgcolor != '' ) {
                $new_layout_info['bgcolor'] = $bgcolor;
            }
            if( $startimage != '' ) {
                $new_layout_info['startimage'] = $startimage;
            }
            if( $items != '' && is_array( $items ) ) {
                $new_layout_info['items'] = $items;
            }
            $new_layout_info['lastmodified'] = time();
            
            $wpdb->update( rpids_tableprefix() . "rpids_layouts", $new_layout_info, array( "id" => $layout_id ) );
            
            // Return success
            return (object) array(
                "status" => "success",
                "message" => "Layout updated."
            );
        } catch ( Exception $e ) {
            return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
        }
    }
               
    /*
        Function to remove a layout
        Since: 2.0
    */
    public function remove_layout( $layout_id ) {
        try {
            if( @$layout_id != '' ) { // Required
                $layout_id = sanitize_text_field( $layout_id );
            } else {
                throw new Exception( 'Layout ID is required.' );
            }
            
            // We have the layout ID. Remove it.
            if( $wpdb->delete( rpids_tableprefix() . "rpids_layouts", array( "id" => $layout_id ) ) ) {
                // Return success
                return (object) array(
                    "status" => "success",
                    "message" => "Layout updated."
                );
            } else {
                throw new Exception( 'Error when deleting the layout.' );
            }
        } catch ( Exception $e ) {
            return (object) array(
				"status" => "error",
				"message" => $e->getMessage()
			);
        }
    }
}

$rpids_settings = new rpids_settings();
?>