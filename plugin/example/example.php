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

namespace Deft\Plugin;

use Deft\Lib\Plugin;
use Deft\Lib\Event;
use Deft\Lib\Watchdog;

class Example extends Plugin {
	private static $path;

	public static function init() {
		self::$path = DEFT_PLUGIN_PATH . DS . 'example' . DS;

		/**
		 * Route rules can be automatically loaded at runtime after being stored with \Deft::route()->save()
		 */
		\Deft::route()->add( 'example.index', '', null, '\Deft\Plugin\Example::content' );
		\Deft::route()->add(

			// Route name
			'example.page',

			// Route path relative to Deft framework, with regex pattern placeholder [page]
			'[page]',
			array(

				// Pattern for [page]. Route data is accessed using \Deft::route()->getParam('page');
				'page' => '[a-z]+',

				// Additional route meta[data] set if route matched.
				'foo' => 'bar'
			),

			// Route callback if matched
			'\Deft\Plugin\Example::content'
		);
	}

	/**
	 * Callback for route matches...
	 */
	public static function content() {

		// Do we have a 'page' parameter in the route data?
		if (is_array($query = \Deft::route()->current('data'))) {
			$page = $query['page'];
		}

		if( empty( $page ) ) {
			$page = 'index';
			\Deft::log()->add(
				__('Your framework environment has not been configured to do anything special yet. So here is some content generated by <strong>Deft\Plugin\Example</strong>'),
				0,
				'example',
				\Deft\Lib\Log::INFORMATION
			);
		} elseif( $page == 'index' ) {
			\Deft::response()->location();
		}

		// If we're serving the 'response' page, set response to JSON
		if ($page == 'response') {
			\Deft::config()->set('response.type', 'http.json');
		}

		// Otherwise default http.html response, add event actions to add an HTML header and footer
		else {
			\Deft::event()->set( 'beforeDocumentGetHead', '\Deft\Plugin\Example::beforeDocumentGetHead' );
			\Deft::event()->set( 'beforeDocumentGetBody', '\Deft\Plugin\Example::beforeDocumentGetBody' );
		}

		// Get response handle
		$res = \Deft::response();

		// Capture the output
		$content = \Deft::capture( 'plugin.example.page.' . $page );
		if( is_string( $content ) ) {

			// Add to the content to response
			if ($res->getArg('type') === 'http.json')
				$res->buffer($content);
			else
				$res->appendBody($content);
		}

		// Capture returned FALSE
		else {
			$res->status(404);
		}
	}

	public static function beforeDocumentGetHead() {

		// Set some document properties
		$res = \Deft::response();
		$res->addStyle( 'plugin/example/asset/css/example.css' );
		$res->addScript( 'plugin/example/asset/js/main.js' );
		$res->addScript( 'lib/deft.js' );
		$res->setVpWidth( 0 );
		$res->addStyle( 'https://fonts.googleapis.com/css?family=Raleway:400,700' );
		$res->setTitleSeparator( ' &bull; ' );
		$res->setTitle( 'Deft, a PHP & JS framework' );
		$res->setDescription( 'Another framework, attempting to make your life easier. Its early days yet...' );
	}

	public static function beforeDocumentGetBody() {
		$res = \Deft::response();
		$res->prependBody( '<main>' );
		$res->appendBody( '</main>' );
	}
}

// Establish the plugin's routes
\Deft::event()->set( 'init', '\Deft\Plugin\Example::init' );
