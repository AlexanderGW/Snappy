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

// TODO: send to a wrapper function for the Deft payloads stuff
echo json_encode([
	'deft' => Deft::VERSION,
	'querySelector' => 'textarea#json_test',
	'data' => "[['time' => " . \Deft\Lib\Sanitize::forHtml(time()) . "],['FOO' => 'BAR'], [['baz' => 'qux']]]"
]);