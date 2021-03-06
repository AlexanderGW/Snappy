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

class Config extends \Deft_Concrete {
	protected $path = null;
	protected $exists = FALSE;
	protected $type;
	protected $readable = FALSE;
	protected $empty = TRUE;
	protected $fields = array();
	protected $exception = null;

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	function __construct ($args = [], $class = __CLASS__) {
		if (!array_key_exists('scope', $args)) {
			$this->scope = 'config';
			$this->path = DEFT_PATH . DS . str_replace('.', DS, $this->scope) . '.php';
			$this->fields = $args;
		} else {
			$this->scope = $args['scope'];
			$this->path = DEFT_PATH . DS . str_replace('.', DS, $this->scope) . '.' . $args['format'];
		}

		parent::__construct($this->scope, $class);
	}

	/**
	 * @param null $args
	 *
	 * @return string
	 */
	public static function getArgs ($args = null) {
		return $args;
	}

	/**
	 * @param int $arg
	 * @param null $fallback
	 *
	 * @return array|null|string|void
	 */
	function get ($arg = -1, $fallback = null) {
		if ($arg === -1) {
			return $this->fields;
		}

		if (is_null($arg)) {
			return;
		}

		if (array_key_exists($arg, $this->fields)) {
			$return = $this->fields[$arg];

			return is_string($return) ? stripslashes($return) : $return;
		}

		return $fallback;
	}

	/**
	 * @param null $x
	 * @param null $y
	 */
	function set ($x = null, $y = null) {
		if (is_null($x)) {
			return;
		}

		if (is_array($x)) {
			foreach ($x as $a => $b) {
				$this->fields[$a] = $b;
			}
		} else {
			$this->fields[$x] = $y;
		}
	}

	/**
	 * @param $field
	 *
	 * @return bool
	 */
	function remove ($field) {
		if (array_key_exists($field, $this->fields) !== FALSE) {
			unset($this->fields[$field]);

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return null|string
	 */
	function getPath () {
		return $this->path;
	}

	/**
	 * @return bool
	 */
	function exists () {
		return $this->exists;
	}

	/**
	 * @return bool
	 */
	function isReadable () {
		return $this->readable;
	}

	/**
	 * @return bool
	 */
	function isEmpty () {
		return $this->empty;
	}

	/**
	 * @return bool
	 */
	function save () {
		return TRUE;
	}
}