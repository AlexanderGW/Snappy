<?php

/**
 * Deft, a micro framework for PHP.
 *
 * @author Alexander Gailey-White <alex@gailey-white.com>
 *
 * This file is part of Deft.
 *
 * Deft is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Deft is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Deft.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Deft\Lib;

class Route extends \Deft_Concrete {
	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	/**
	 * Set TRUE once parse() executes.
	 *
	 * @var array
	 */
	private static $parsed = false;

	private static $rules = array();

	private static $route = array();

	/**
	 *
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		// Process request query string
//		if ($query = \Deft::request()->query()) {
//			if (count($query)) {
//				foreach ($query as $key => $val) {
//					$key        = Sanitize::forText($key);
//					$val        = Sanitize::fortext($val);
//					$_GET[$key] = $val;
//				}
//			}
//		}

		// Load config based routes
		$config = \Deft::config( 'config.route' );
		if ( ! $config->isEmpty() ) {
			$routes = $config->get( 'routes', array() );
			if ( count( $routes ) ) {
				foreach ( $routes as $path => $rule ) {
					Route::add( $path, $rule );
				}
			}
		}

		self::$initialized = true;
	}

	/**
	 *
	 */
	public static function parse() {
		if ( self::$parsed ) {
			return;
		}

		$start = Helper::getMicroTime();

		// Process requested route
		self::$route = \Deft::route()->get( DEFT_ROUTE );

		// No match
		if ( self::$route === null and strlen( DEFT_ROUTE ) ) {
			$routes  = \Deft::route()->getRules();
			$divider = \Deft::config()->get( 'url_separator', '/' );

			$request = explode( $divider, DEFT_ROUTE );
			array_pop( $request );
			$max = count( $request );
			//$request = implode( $divider, $request );

			foreach ( $routes as $path => $args ) {
				if (

					// Check for potentials with the same depth (path dividers), which also has placeholder(s)
					substr_count( $path, $divider ) == $max

					// Has placeholders
					and strpos( $path, '[' ) !== false
					and strpos( $path, ']' ) !== false

				    // Has placeholder patterns
				    and array_key_exists( 'pattern', $args )
			        and count( $args['pattern'] )
				) {

					// Build pattern
					$path_pattern = '#^' . $path . '$#';
					foreach ( $args[ 'pattern' ] as $name => $pattern ) {
						$path_pattern = str_replace(
							'[' . $name . ']',
							'(' . $pattern . ')',
							$path_pattern
						);
					}

					// Run pattern
					preg_match( $path_pattern, DEFT_ROUTE, $matches );
					if ( count( $matches ) ) {
						preg_match_all( '#\[([a-z0-9]+)\]#', $path, $keys );
						array_shift( $matches );

						foreach ( $matches as $i => $match ) {
							$args[ 'data' ][ $keys[ 1 ][ $i ] ] = $match;
						}

						self::$route = array_merge(
							$args,
							array(
								'path'    => $path,
								'pattern' => $path_pattern
							)
						);

						break;
					}
				}
			}
		}

		// Apply route environment
		if ( is_array( self::$route[ 'data' ] ) ) {
			foreach ( self::$route[ 'data' ] as $key => $value ) {
				$key                           = Sanitize::forText( $key );
				$value                         = Sanitize::forText( $value );
				self::$route[ 'data' ][ $key ] = $value;
			}
		}

		// Route callback
		if ( is_callable( self::$route[ 'callback' ] ) ) {
			call_user_func( self::$route[ 'callback' ] );
		}

		// Logging
		\Deft::log( 'route/' . self::$route[ 'name' ], array(
			'time'     => Helper::getMoment( $start ),
			'name'     => self::$route[ 'name' ],
			'request'  => DEFT_ROUTE,
			'path'     => self::$route[ 'path' ],
			'pattern'  => self::$route[ 'pattern' ],
			'data'     => self::$route[ 'data' ],
			'callback' => self::$route[ 'callback' ]
		) );

		if ( self::$route === null ) {
			\Deft::response()->status( 404 );
		}

		self::$parsed = true;
	}

	/**
	 * Returns the currently patched route. Provide a value for a particular route key, or return the whole object.
	 */
	public function current( $arg = null ) {
		if ( is_null( $arg ) ) {
			return (object) self::$route;
		}
		if ( is_array( self::$route ) and array_key_exists( $arg, self::$route ) ) {
			return self::$route[ $arg ];
		}

		return;
	}

	/**
	 * @param null $path
	 * @param array $args
	 */
	public function add( $name = null, $path = null, $args = array(), $callback = null ) {

		// TODO: Need to split path by separator

		if ( is_null( $path ) ) {
			return;
		}

		if ( is_null( $name ) ) {
			$name = md5( $path );
		}

		$sep = \Deft::config()->get( 'url_separator', '/' );
		if ( strpos( $path, $sep ) === 0 ) {
			$path = substr( $path, strlen( $sep ) );
		}

		if ( ! $path ) {
			$path = '';
		}

		// Process route arguments
		if ( is_array( $args ) and count( $args ) ) {
			$patterns = $errors = array();

			// Test for route placeholders
			preg_match_all( '#\[([a-z0-9]+)\]#', $path, $matches );
			if ( count( $matches ) ) {
				foreach ( $matches[ 1 ] as $label ) {

					// Placeholder pattern not found in route rule list
					if ( ! array_key_exists( $label, $args ) ) {
						$errors[] = $label;
					} else {
						$patterns[ $label ] = $args[ $label ];
						unset( $args[ $label ] );
					}
				}

				// Throw route rule pattern errors
				if ( count( $errors ) ) {
					\Deft::error( 'Route rule "%1$s" missing required parameter(s): %2$s', $path, implode( ', ', $errors ) );
				}
			}
		}

		// Store rule
		self::$rules[ $path ] = array(
			'name'     => $name,
			'data'     => $args,
			'pattern'  => $patterns,
			'callback' => ( is_array( $callback ) ? $callback[ 0 ] . '::' . $callback[ 1 ] : $callback )
		);

		return true;
	}

	/**
	 * @param null $path
	 */
	public function get( $path = null ) {
		if ( is_null( $path ) ) {
			return null;
		}

		$sep = \Deft::config()->get( 'url_separator', '/' );
		if ( strpos( $path, $sep ) === 0 ) {
			$path = substr( $path, strlen( $sep ) );
		}

		return self::$rules[ $path ];
	}

	/**
	 * @return array
	 */
	public function getRules() {
		return self::$rules;
	}

	/**
	 * @param null $path
	 */
	public function remove( $path = null ) {
		if ( strpos( $path, '/' ) === 0 ) {
			$path = substr( $path, 1 );
		}
		if ( array_key_exists( $path, self::$rules ) ) {
			unset( self::$rules[ $path ] );
		}
	}

	/**
	 * @param null $name
	 */
	public function addSet( $name = null ) {

	}

	/**
	 * @param null $name
	 */
	public function removeSet( $name = null ) {

	}

	/**
	 *
	 */
	public function save() {
		$config = \Deft::config( 'config.route' );
		$config->set( self::getRules() );
		$config->save();
	}

	/**
	 * Syntactic suger
	 *
	 * @return mixed|object|void
	 */
	public function getName() {
		return self::current( 'name' );
	}

	public function request() {
		return self::current( 'request' );
	}

	public function getPath() {
		return self::current( 'path' );
	}

	public function getPattern() {
		return self::current( 'pattern' );
	}

	public function getParam( $key = null ) {
		$data = self::current( 'data' );
		if ( is_array( $data ) ) {
			if ( array_key_exists( $key, $data ) ) {
				return $data[ $key ];
			}
		}
	}

	public function getCallback() {
		return self::current( 'callback' );
	}
}

// Load route rules
\Deft::event()->set( 'init', '\Deft\Lib\Route::init', 10 );

// Process HTTP request against available route rules
\Deft::event()->set( 'init', '\Deft\Lib\Route::parse', 500 );