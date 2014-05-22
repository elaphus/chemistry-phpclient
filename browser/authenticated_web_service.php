<?php
/**
 * @copyright 2014 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 */
class AuthenticatedWebService
{
	private $session;

	public function __construct($url, $username, $password)
	{
		$this->session = $this->connect($url, $username, $password);
	}

	private function connect($url, $username, $password)
	{
        $session = curl_init($url);
        curl_setopt($session, CURLOPT_HEADER,         false);
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($session, CURLOPT_SSLVERSION, 3);
        }
        if ($username) {
			curl_setopt($session, CURLOPT_USERPWD, "{$username}:{$password}");
		}
		return $session;
	}

	/**
	 * Executes an HTTP request and returns the response
	 *
	 * @param string $url
	 * @param string $method
	 * @return string
	 */
	protected function doRequest($url, $method = 'GET')
	{
		curl_setopt($this->session, CURLOPT_URL,           $url);
        curl_setopt($this->session, CURLOPT_CUSTOMREQUEST, $method);

        $response = curl_exec($this->session);
        if ($response) {
			return $response;
        }
        else {
			throw new Exception(curl_error($this->session));
        }
	}
}
