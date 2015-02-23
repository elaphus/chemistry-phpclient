<?php
/**
 * @copyright 2014 City of Bloomington, Indiana
 * @license http://www.gnu.org/licenses/agpl.txt GNU/AGPL, see LICENSE.txt
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 */
include __DIR__.'/authenticated_web_service.php';

class CMISService extends AuthenticatedWebService
{
	const ARG_SEPARATOR = '&';

	protected $repositoryUrl;
	protected $repositoryId;
	protected $rootFolderUrl;

	public $succinct = false;
	public $maxItems = 10;

	/**
	 * In order to support any CMIS server, you must pass in the full URL
	 * to your server's CMIS browser binding endpoint.
	 *
	 * @param string $repositoryUrl CMIS browser binding endpoint
	 * @param string $username
	 * @param string $password
	 * @param string $repositoryId
	 */
	public function __construct($repositoryUrl, $username, $password, $repositoryId)
	{
        // Test the URL by doing a JSON request for the rootFolderUrl
        parent::__construct($repositoryUrl, $username, $password);
        $json = $this->doJSONRequest($repositoryUrl);

        $this->repositoryUrl = $repositoryUrl;
        $this->repositoryId  = $repositoryId;
		$this->rootFolderUrl = $json->$repositoryId->rootFolderUrl;
	}

	/**
	 * Decodes a JSON response and returns a decorated object
	 *
	 * @param string $url
	 * @param string $method
	 * @return stdClass
	 */
	protected function doJSONRequest($url, $method = 'GET')
	{
		$response = $this->doRequest($url, $method);
 		$json = json_decode($response);
		if ($json) {
			if (isset($json->exception)) {
				throw new Exception($json->message);
			}
			return $json;
		}
		else {
			throw new Exception(json_last_error_msg());
		}
	}

	// Repository Services
	public function getRepositories() { throw new Exception('methodNotImplemented'); }
	public function getRepositoryInfo($name) { throw new Exception('methodNotImplemented'); }
	public function getTypeChildren() { throw new Exception('methodNotImplemented'); }
	public function getTypeDescendants() { throw new Exception('methodNotImplemented'); }
	public function getTypeDefinition() { throw new Exception('methodNotImplemented'); }
	public function createType($type) { throw new Exception('methodNotImplemented'); }
	public function updateType($type) { throw new Exception('methodNotImplemented'); }
	public function deleteType($typeId) { throw new Exception('methodNotImplemented'); }

	// Navigation Services
	/**
	 * @param string $folderId
	 * @return CMISObject
	 */
	public function getFolderParent($folderId)
	{
		$params = ['objectId'=>$folderId, 'cmisselector'=>'parent'];
		if ($this->succinct) { $params['succinct'] = 'true'; }

		$url = $this->rootFolderUrl.'?'.http_build_query($params, null, self::ARG_SEPARATOR);

		return $this->doJSONRequest($url);
	}

	/**
	 * @param string $folderId
	 * @param int $skipCount
	 * @return stdClass
	 */
	public function getChildren($folderId, $skipCount=0)
	{
		$params = ['objectId'=>$folderId, 'maxItems'=>$this->maxItems];
		if ($skipCount)      { $params['skipCount'] = (int)$skipCount; }
		if ($this->succinct) { $params['succinct' ] = 'true'; }

		$url = $this->rootFolderUrl.'?'.http_build_query($params, null, self::ARG_SEPARATOR);

		return $this->doJSONRequest($url);
	}

	/**
	 * @NOTE This is a non-standard function
	 *
	 * This function is not in the CMIS 1.1 service specification
	 * It is added to this class for consistency in function naming.
	 *
	 * @param string $path
	 * @param int $skipCount
	 * @return stdClass
	 */
	public function getChildrenByPath($path, $skipCount=0)
	{
		$path = str_replace(' ', '+', $path);

		$params = ['maxItems' => $this->maxItems];
		if ($skipCount)      { $params['skipCount'] = (int)$skipCount; }
		if ($this->succinct) { $params['succinct' ] = 'true'; }

		$url = $this->rootFolderUrl.$path.'?'.http_build_query($params, null, self::ARG_SEPARATOR);

		return $this->doJSONRequest($url);
	}

	public function getDescendants($folderId) { throw new Exception('methodNotImplemented'); }
	public function getFolderTree($folderId) { throw new Exception('methodNotImplemented'); }
	public function getObjectParents($objectId) { throw new Exception('methodNotImplemented'); }
	public function getCheckedOutDocs($folderId=null) { throw new Exception('methodNotImplemented'); }

	// Object Services
	/**
	 * @param string $objectId
	 * @return CMISObject
	 */
	public function getObject($objectId)
	{
		$params = ['objectId'=>$objectId, 'cmisselector'=>'object'];
		if ($this->succinct) { $params['succinct'] = 'true'; }

		$url = $this->rootFolderUrl.'?'.http_build_query($params, null, self::ARG_SEPARATOR);

		return $this->doJSONRequest($url);
	}

	/**
	 * @param string $path
	 * @return CMISObject
	 */
	public function getObjectByPath($path)
	{
		$params = ['cmisselector'=>'object'];
		if ($this->succinct) { $params['succinct'] = 'true'; }

		$url = $this->rootFolderUrl.$path.'?'.http_build_query($params, null, self::ARG_SEPARATOR);

		return $this->doJSONRequest($url);
	}

	public function createDocument($properties, $folderId=null) { throw new Exception('methodNotImplemented'); }
	public function createDocumentFromSource($sourceId, $properties=null, $folderId=null) { throw new Exception('methodNotImplemented'); }
	public function createFolder($properties, $folderId=null) { throw new Exception('methodNotImplemented'); }
	public function createRelationship($properties) { throw new Exception('methodNotImplemented'); }
	public function createPolicy($properties, $folderId=null) { throw new Exception('methodNotImplemented'); }
	public function createItem($properties, $folderId=null) { throw new Exception('methodNotImplemented'); }
	public function getAllowableActions($objectId) { throw new Exception('methodNotImplemented'); }
	public function getProperties($objectId) { throw new Exception('methodNotImplemented'); }

	/**
	 * @param string $objectId
	 * @param string $streamId
	 */
	public function getContentStream($objectId, $streamId=null)
	{
		$params = ['cmisaction'=>'getContentStream', 'objectId'=>$objectId];
		if ($streamId) { $params['streamId'] = $streamId; }

		$url = $this->rootFolderUrl.'?'.http_build_query($params, null, self::ARG_SEPARATOR);

		return $this->doRequest($url);
	}

	public function getRenditions($objectId) { throw new Exception('methodNotImplemented'); }
	public function updateProperties($objectId, $properties) { throw new Exception('methodNotImplemented'); }
	public function bulkUpdateProperties($objects) { throw new Exception('methodNotImplemented'); }
	public function moveObject($objectId, $targetFolderId, $sourceFolderId) { throw new Exception('methodNotImplemented'); }
	public function deleteObject($objectId) { throw new Exception('methodNotImplemented'); }
	public function deleteTree($folderId) { throw new Exception('methodNotImplemented'); }
	public function setContentStream($objectId, $content) { throw new Exception('methodNotImplemented'); }
	public function appendContentStream($objectId, $content) { throw new Exception('methodNotImplemented'); }
	public function deleteContentStream($objectId) { throw new Exception('methodNotImplemented'); }

	// Multi-filing Services
	public function addObjectToFolder($objectId, $folderId) { throw new Exception('methodNotImplemented'); }
	public function removeObjectFromFolder($objectId, $folderId=null) { throw new Exception('methodNotImplemented'); }

	// Discovery Services
    /**
     * @param string $query
     * @param int $skipCount
     * @return stdClass
     */
	public function query($query, $skipCount=0)
	{
		$params = ['cmisselector'=>'query', 'q'=>$query, 'maxItems'=>$this->maxItems];
		if ($skipCount)      { $params['skipCount'] = (int)$skipCount; }
		if ($this->succinct) { $params['succinct' ] = 'true'; }

		$url = $this->repositoryUrl.'?'.http_build_query($params, null, self::ARG_SEPARATOR);

		return $this->doJSONRequest($url);
	}

	public function getContentChanges() { throw new Exception('methodNotImplemented'); }

	// Versioning Services
	public function checkOut($objectId) { throw new Exception('methodNotImplemented'); }
	public function cancelCheckOut($objectId) { throw new Exception('methodNotImplemented'); }
	public function checkIn($objectId) { throw new Exception('methodNotImplemented'); }
	public function getObjectOfLatestVersion($versionSeriesId) { throw new Exception('methodNotImplemented'); }
	public function getPropertiesOfLatestVersion($versionSeriesId) { throw new Exception('methodNotImplemented'); }
	public function getAllVersions($versionSeriesId) { throw new Exception('methodNotImplemented'); }

	// Relationship Services
	public function getObjectRelationships($objectId) { throw new Exception('methodNotImplemented'); }

	// Policy Services
	public function applyPolicy($policyId, $objectId) { throw new Exception('methodNotImplemented'); }
	public function removePolicy($policyId, $objectId) { throw new Exception('methodNotImplemented'); }
	public function getAppliedPolicies($objectId) { throw new Exception('methodNotImplemented'); }

	// ACL Services
	public function applyACL($objectId) { throw new Exception('methodNotImplemented'); }
	public function getACL($objectId) { throw new Exception('methodNotImplemented'); }
}
