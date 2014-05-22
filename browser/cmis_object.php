<?php
/**
 * Decorator class for JSON response objects
 *
 * @copyright 2014 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 */
class CMISObject
{
	public function __construct($object)
	{
		foreach (get_object_vars($object) as $k=>$v) {
			$this->$k = $v;
		}
	}

	/**
	 * Returns the given property value
	 *
	 * When succinct is turned on, the response objects have a
	 * different format.  This function handles reading from the
	 * different response formats.
	 *
	 * @param stdClass The JSON response object to look in
	 * @param string $property The name of the property
	 * @return string
	 */
	public function get($property)
	{
		if ( isset($this->succinctProperties)) {
			return $this->succinctProperties->$property;
		}
		else {
			return $this->properties->$property->value;
		}
	}
}
