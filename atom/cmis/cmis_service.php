<?php
# Licensed to the Apache Software Foundation (ASF) under one
# or more contributor license agreements.  See the NOTICE file
# distributed with this work for additional information
# regarding copyright ownership.  The ASF licenses this file
# to you under the Apache License, Version 2.0 (the
# "License"); you may not use this file except in compliance
# with the License.  You may obtain a copy of the License at
#
# http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing,
# software distributed under the License is distributed on an
# "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
# KIND, either express or implied.  See the License for the
# specific language governing permissions and limitations
# under the License.
require_once 'cmis_repository_wrapper.php';

// Many Links have a pattern to them based upon objectId -- but can that be depended upon?

/**
 * CMIS Service
 *
 * @api CMIS
 * @since CMIS-1.0
 */
class CMISService extends CMISRepositoryWrapper {
    // Option Constants for Array Indexing
    // -- Generally optional flags that control how much information is returned
    // -- Change log token is an anomoly -- but included in URL as parameter
    const OPT_MAX_ITEMS                    = "maxItems";
    const OPT_SKIP_COUNT                   = "skipCount";
    const OPT_FILTER                       = "filter";
    const OPT_INCLUDE_PROPERTY_DEFINITIONS = "includePropertyDefinitions";
    const OPT_INCLUDE_RELATIONSHIPS        = "includeRelationships";
    const OPT_INCLUDE_POLICY_IDS           = "includePolicyIds";
    const OPT_RENDITION_FILTER             = "renditionFilter";
    const OPT_INCLUDE_ACL                  = "includeACL";
    const OPT_INCLUDE_ALLOWABLE_ACTIONS    = "includeAllowableActions";
    const OPT_DEPTH                        = "depth";
    const OPT_CHANGE_LOG_TOKEN             = "changeLogToken";
    const OPT_CHECK_IN_COMMENT             = "checkinComment";
    const OPT_CHECK_IN                     = "checkin";
    const OPT_MAJOR_VERSION                = "major";

    const COLLECTION_ROOT_FOLDER = "root";
    const COLLECTION_TYPES       = "types";
    const COLLECTION_CHECKED_OUT = "checkedout";
    const COLLECTION_QUERY       = "query";
    const COLLECTION_UNFILED     = "unfiled";

    const URI_TEMPLATE_OBJECT_BY_ID   = "objectbyid";
    const URI_TEMPLATE_OBJECT_BY_PATH = "objectbypath";
    const URI_TEMPLATE_TYPE_BY_ID     = "typebyid";
    const URI_TEMPLATE_QUERY          = "query";

    const LINK_SELF            = "self";
    const LINK_SERVICE         = "service";
    const LINK_DESCRIBED_BY    = "describedby";
    const LINK_VIA             = "via";
    const LINK_EDIT_MEDIA      = "edit-media";
    const LINK_EDIT            = "edit";
    const LINK_ALTERNATE       = "alternate";
    const LINK_FIRST           = "first";
    const LINK_PREVIOUS        = "previous";
    const LINK_NEXT            = "next";
    const LINK_LAST            = "last";
    const LINK_UP              = "up";
    const LINK_DOWN            = "down";
    const LINK_DOWN_TREE       = "down-tree";
    const LINK_VERSION_HISTORY = "version-history";
    const LINK_CURRENT_VERSION = "current-version";


    const LINK_ALLOWABLE_ACTIONS = "http://docs.oasis-open.org/ns/cmis/link/200908/allowableactions";
    const LINK_RELATIONSHIPS     = "http://docs.oasis-open.org/ns/cmis/link/200908/relationships";
    const LINK_SOURCE            = "http://docs.oasis-open.org/ns/cmis/link/200908/source";
    const LINK_TARGET            = "http://docs.oasis-open.org/ns/cmis/link/200908/target";
    const LINK_POLICIES          = "http://docs.oasis-open.org/ns/cmis/link/200908/policies";
    const LINK_ACL               = "http://docs.oasis-open.org/ns/cmis/link/200908/acl";
    const LINK_CHANGES           = "http://docs.oasis-open.org/ns/cmis/link/200908/changes";
    const LINK_FOLDER_TREE       = "http://docs.oasis-open.org/ns/cmis/link/200908/foldertree";
    const LINK_ROOT_DESCENDANTS  = "http://docs.oasis-open.org/ns/cmis/link/200908/rootdescendants";
    const LINK_TYPE_DESCENDANTS  = "http://docs.oasis-open.org/ns/cmis/link/200908/typedescendants";

    const MIME_ATOM_XML       = 'application/atom+xml';
    const MIME_ATOM_XML_ENTRY = 'application/atom+xml;type=entry';
    const MIME_ATOM_XML_FEED  = 'application/atom+xml;type=feed';
    const MIME_CMIS_TREE      = 'application/cmistree+xml';
    const MIME_CMIS_QUERY     = 'application/cmisquery+xml';

	private $_link_cache;
	private $_title_cache;
	private $_objTypeId_cache;
	private $_type_cache;
	private $_changeToken_cache;

	/**
	 * Construct a new CMISService Connector
	 *
	 * @param string $url Endpoint URL
	 * @param string $username Username
	 * @param string $password Password
	 * @param mixed[] $options Connection Options
	 * @param mixed[] $addlCurlOptions Additional CURL Options
	 * @api CMIS-Service
	 * @since CMIS-1.0
	 */
    public function __construct($url, $username, $password, $options = null, array $addlCurlOptions = array ()) {
        parent::__construct($url, $username, $password, $options, $addlCurlOptions);
        $this->_link_cache        = array();
        $this->_title_cache       = array();
        $this->_objTypeId_cache   = array();
        $this->_type_cache        = array();
        $this->_changeToken_cache = array();
    }


	/**
	 * @param array $options
	 * @return string
	 */
	private function GenURLQueryString($options)
	{
		if (count($options) > 0) {
			return '&'.urldecode(http_build_query($options));
		}
    }


    /**
     * @internal
     * @param stdClass $obj
     */
	private function cacheObjectInfo($obj) {
		$this->     _link_cache[$obj->id] = $obj->links;
		$this->    _title_cache[$obj->id] = $obj->properties["cmis:name"]; // Broad Assumption Here?
		$this->_objTypeId_cache[$obj->id] = $obj->properties["cmis:objectTypeId"];

		if (isset($obj->properties["cmis:changeToken"])) {
			$this->_changeToken_cache[$obj->id] = $obj->properties["cmis:changeToken"];
		}
	}

	/**
	 * Get an Object's property and return it as an array
	 *
	 * This returns an array even if it is a scalar or null
	 *
	 * @todo Allow the getProperty method to query the object type information and
	 * return multivalue properties as arrays even if empty or if only a single value
	 * is present.
	 * @param stdClass $obj Object
	 * @param string $propName Property Name
	 * @returns mixed[]
	 * @api CMIS-Helper
	 * @since CMIS-1.0
	 */
	public function getMultiValuedProp($obj,$propName) {
		if (isset($obj->properties[$propName])) {
			return parent::getAsArray($obj->properties[$propName]);
		}
		return array();
	}

	/**
	 * @internal
	 */
	private function cacheFeedInfo($objs) {
		foreach ($objs->objectList as $obj) {
			$this->cacheObjectInfo($obj);
		}
	}

	/**
	 * @internal
	 */
	private function cacheTypeFeedInfo($typs) {
		foreach ($typs->objectList as $typ) {
			$this->cacheTypeInfo($typ);
		}
	}

	/**
	 * @internal
	 */
	private function cacheTypeInfo($tDef) {
		// TODO: Fix Type Caching with missing properties
		$this->_type_cache[$tDef->id] = $tDef;
	}

	/**
	 * @internal
	 * @param string $typeId
	 * @param string $propertyId
	 * @return string
	 */
	private function getPropertyType($typeId, $propertyId) {
		if (isset($this->_type_cache[$typeId])) {
			if ($this->_type_cache[$typeId]->properties) {
				return $this->_type_cache[$typeId]->properties[$propertyId]["cmis:propertyType"];
			}
		}
		$obj = $this->getTypeDefinition($typeId);
		return $obj->properties[$propertyId]["cmis:propertyType"];
	}

	/**
	 * @internal
	 * @param string $objectId
	 * @return string
	 */
	private function getObjectType($objectId) {
		if ($this->_objTypeId_cache[$objectId]) {
			return $this->_objTypeId_cache[$objectId];
		}
		$obj = $this->getObject($objectId);
		return $obj->properties["cmis:objectTypeId"];
	}

	/**
	 * @internal
	 * @param string $objectId
	 * @return string
	 */
	private function getTitle($objectId) {
		if ($this->_title_cache[$objectId]) {
			return $this->_title_cache[$objectId];
		}
		$obj = $this->getObject($objectId);
		return $obj->properties["cmis:name"];
	}

	/**
	 * @internal
	 * @param string $typeId
	 * @param string $linkName
	 * @return string
	 */
	private function getTypeLink($typeId, $linkName) {
		if ($this->_type_cache[$typeId]->links) {
			return $this->_type_cache[$typeId]->links[$linkName];
		}
		$typ = $this->getTypeDefinition($typeId);
		return $typ->links[$linkName];
	}

	/**
	 * @internal
	 * @param string $objectId
	 * @param string $linkName
	 * @return string
	 */
	private function getLink($objectId, $linkName) {
		if (array_key_exists($objectId, $this->_link_cache)) {
          return $this->_link_cache[$objectId][$linkName];
        }
		$obj = $this->getObject($objectId);
		return $obj->links[$linkName];
	}

	/**
	 * Get an Object by Object Id
	 *
	 * @todo Need to fix this for multiple repositories
	 * @api CMIS-RepositoryServices-NotImplemented
	 * @since CMIS-1.0
	 */
	public function getRepositories() {
		throw new CmisNotImplementedException("getRepositories");
	}

	/**
	 * Get Repository Information
	 *
	 * @returns stdClass
	 * @api CMIS-RepositoryServices
	 * @since CMIS-1.0
	 */
	public function getRepositoryInfo() {
		return $this->workspace;
	}

	/**
	 * Get a set of object-types that are descendants of the specified type
	 *
	 * If typeId is null, then the repository MUST return all types and ignore the depth parameter.
	 *
	 * @todo Refactor Type Entries Caching
	 * @param string $typeId The typeId of an object-type specified in the repository
	 * @param $depth the number of levels in the hierarchy to return (-1 == all)
	 * @returns stdClass The set of descendant object-types defined for the given typeId.
	 * @api CMIS-RepositoryServices
	 * @since CMIS-1.0
	 */
	public function getTypeDescendants($typeId = null, $depth, $options = array ()) {
		$varmap = $options;
		if ($typeId) {
			$hash_values = $options;
			$hash_values[self::OPT_DEPTH] = $depth;
			$myURL = $this->getTypeLink($typeId, self::LINK_DOWN_TREE);
			$myURL = parent::getOpUrl($myURL, $hash_values);
		} else {
			$myURL = $this->processTemplate($this->workspace->links[self::LINK_TYPE_DESCENDANTS], $varmap);
		}
		$ret   = $this->doGet($myURL);
		$types = $this->extractTypeFeed($ret->body);
		$this->cacheTypeFeedInfo($types);
		return $types;
	}

	/**
	 * Get a list of object-types that are children of the specified type
	 *
	 * If typeId is null, then the repository MUST return all base object-types.
	 *
	 * @param string $typeId The typeId of an object-type specified in the repository
	 * @returns stdClass The list of child object-types defined for the given typeId.
	 * @api CMIS-RepositoryServices
	 * @since CMIS-1.0
	 */
	public function getTypeChildren($typeId = null, $options = array ()) {
		// TODO: Refactor Type Entries Caching
		$varmap = $options;
		if ($typeId) {
			$myURL = $this->getTypeLink($typeId, self::LINK_DOWN);
		    $myURL.= $this->GenURLQueryString($options);
		} else {
			//TODO: Need right URL
			$myURL = $this->processTemplate($this->workspace->collections['types'], $varmap);
		}
		$ret   = $this->doGet($myURL);
		$types = $this->extractTypeFeed($ret->body);
		$this->cacheTypeFeedInfo($types);
		return $types;
	}

	/**
	 * Gets the definition of the specified object-type.
	 *
	 * @param string $typeId Object Type Id
	 * @returns stdClass Type Definition of the Specified Object
	 * @api CMIS-RepositoryServices
	 * @since CMIS-1.0
	 */
	public function getTypeDefinition($typeId, $options = array ()) { // Nice to have
		$varmap = $options;
		$varmap["id"] = $typeId;
		$myURL = $this->processTemplate($this->workspace->uritemplates[self::URI_TEMPLATE_TYPE_BY_ID], $varmap);
		$ret = $this->doGet($myURL);
		$obj = $this->extractTypeDef($ret->body);
		$this->cacheTypeInfo($obj);
		return $obj;
	}

	/**
	 * Get an Object's Property Type by Object Id
	 *
	 * @param string $objectId Object Id
	 * @returns Object Type Definition of the Specified Object
	 * @api CMIS-Helper
	 * @since CMIS-1.0
	 */
	public function getObjectTypeDefinition($objectId) { // Nice to have
		$myURL = $this->getLink($objectId, self::LINK_DESCRIBED_BY);
		$ret = $this->doGet($myURL);
		$obj = $this->extractTypeDef($ret->body);
		$this->cacheTypeInfo($obj);
		return $obj;
	}

	//Repository Services -- New for 1.1
	/**
	 * Creates a new type definition.
	 *
	 * Creates a new type definition that is a subtype of an existing specified parent type.
	 * Only properties that are new to this type (not inherited) are passed to this service.
	 *
	 * @param string $objectType A type definition object with the property definitions that are to change.
	 * @returns stdClass Type Definition of the Specified Object
	 * @api CMIS-RepositoryServices-NotImplemented
	 * @since CMIS-1.1
	 */
	public function createType($objectType) {
		throw new CmisNotImplementedException("createType");
	}

	/**
	 * Updates a type definition
	 *
	 * If you add an optional property to a type in error. There is no way to remove it/correct it - without
	 * deleting the type.
	 *
	 * @param string $objectType A type definition object with the property definitions that are to change.
	 * @returns stdClass The updated object-type including all property definitions.
	 * @api CMIS-RepositoryServices-NotImplemented
	 * @since CMIS-1.1
	 */
	public function updateType($objectType) {
		throw new CmisNotImplementedException("updateType");
	}

	/**
	 * Deletes a type definition
	 *
	 * If there are object instances present of the type being deleted then this operation MUST fail.
	 *
	 * @param string $typeId The typeId of an object-type specified in the repository.
	 * @api CMIS-RepositoryServices-NotImplemented
	 * @since CMIS-1.1
	 */
	public function deleteType($typeId) {
		throw new CmisNotImplementedException("deleteType");
	}

	//Navigation Services
	/**
	 * Get the list of descendant folders contained in the specified folder.
	 *
	 * @param string $folderId the Object ID of the folder
	 * @param string $depth The number of levels of depth in the folder hierarchy from which to return results (-1 == ALL).
	 * @returns stdClass[] A tree of the child objects for the specified folder.
	 * @api CMIS-NavigationServices
	 * @since CMIS-1.0
	 */
	public function getFolderTree($folderId, $depth, $options = array ()) {
		$hash_values = $options;
		$hash_values[self::OPT_DEPTH] = $depth;
		$myURL = $this->getLink($folderId, self::LINK_FOLDER_TREE);
		$myURL = parent::getOpUrl($myURL, $hash_values);
		$ret = $this->doGet($myURL);
		$objs = $this->extractObjectFeed($ret->body);
		$this->cacheFeedInfo($objs);
		return $objs;
	}

	/**
	 * Get the list of descendant objects contained in the specified folder.
	 *
	 * @param string $folderId the Object ID of the folder
	 * @param string $depth The number of levels of depth in the folder hierarchy from which to return results (-1 == ALL).
	 * @returns stdClass[] A tree of the child objects for the specified folder.
	 * @api CMIS-NavigationServices
	 * @since CMIS-1.0
	 */
	public function getDescendants($folderId, $depth, $options = array ()) { // Nice to have
		$hash_values = $options;
		$hash_values[self::OPT_DEPTH] = $depth;
		$myURL = $this->getLink($folderId, self::LINK_DOWN_TREE);
		$myURL = parent::getOpUrl($myURL, $hash_values);
		$ret = $this->doGet($myURL);
		$objs = $this->extractObjectFeed($ret->body);
		$this->cacheFeedInfo($objs);
		return $objs;
	}

	/**
	 * Get the list of child objects contained in the specified folder.
	 *
	 * @param string $folderId the Object ID of the folder
	 * @returns stdClass[] A list of the child objects for the specified folder.
	 * @api CMIS-NavigationServices
	 * @since CMIS-1.0
	 */
	public function getChildren($folderId, $options = array ()) {
		$myURL = $this->getLink($folderId, self::LINK_DOWN);
		$myURL.= $this->GenURLQueryString($options);
		$ret = $this->doGet($myURL);
		$objs = $this->extractObjectFeed($ret->body);
		$this->cacheFeedInfo($objs);
		return $objs;
	}

	/**
	 * Get the parent folder of the specified folder.
	 *
	 * @param string $folderId the Object ID of the folder
	 * @returns stdClass the parent folder.
	 * @api CMIS-NavigationServices
	 * @since CMIS-1.0
	 */
	public function getFolderParent($folderId, $options = array ()) { //yes
		$myURL = $this->getLink($folderId, self::LINK_UP);
		$myURL.= $this->GenURLQueryString($options);
		$ret = $this->doGet($myURL);
		$obj = parent::extractObject($ret->body);
		$this->cacheObjectInfo($obj);
		return $obj;
	}

	/**
	 * Get the parent folder(s) for the specified fileable object.
	 *
	 * @param string $objectId the Object ID of the Object
	 * @returns stdClass[] list of the parent folder(s) of the specified object.
	 * @api CMIS-NavigationServices
	 * @since CMIS-1.0
	 */
	public function getObjectParents($objectId, $options = array ()) { // yes
		$myURL = $this->getLink($objectId, self::LINK_UP);
		$myURL.= $this->GenURLQueryString($options);
		$ret = $this->doGet($myURL);
		$objs = $this->extractObjectFeed($ret->body);
		$this->cacheFeedInfo($objs);
		return $objs;
	}

	/**
	 * Get the list of documents that are checked out that the user has access to..
	 *
	 * @returns stdClass[] list of checked out documents.
	 * @api CMIS-NavigationServices
	 * @since CMIS-1.0
	 */
	public function getCheckedOutDocs($options = array ()) {
		$obj_url = $this->workspace->collections[self::COLLECTION_CHECKED_OUT];
		$ret = $this->doGet($obj_url);
		$objs = $this->extractObjectFeed($ret->body);
		$this->cacheFeedInfo($objs);
		return $objs;
	}

	/**
     * @internal
     * @return string
     */
	private static function getQueryTemplate()
	{
        return <<<EOT
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cmis:query xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200908/"
            xmlns:cmism="http://docs.oasis-open.org/ns/cmis/messaging/200908/"
            xmlns:atom="http://www.w3.org/2005/Atom"
            xmlns:app="http://www.w3.org/2007/app"
            xmlns:cmisra="http://docs.oasisopen.org/ns/cmis/restatom/200908/">
    <cmis:statement><![CDATA[{q}]]></cmis:statement>
    <cmis:searchAllVersions>{searchAllVersions}</cmis:searchAllVersions>
    <cmis:includeAllowableActions>{includeAllowableActions}</cmis:includeAllowableActions>
    <cmis:includeRelationships>{includeRelationships}</cmis:includeRelationships>
    <cmis:renditionFilter>{renditionFilter}</cmis:renditionFilter>
    <cmis:maxItems>{maxItems}</cmis:maxItems>
    <cmis:skipCount>{skipCount}</cmis:skipCount>
</cmis:query>
EOT;
	}

	//Discovery Services
	/**
	 * Execute a CMIS Query
	 * @param string $statement Query Statement
	 * @param mixed[] $options Options
	 * @returns stdClass[] List of object propery values from query
	 * @api CMIS-DiscoveryServices
	 * @since CMIS-1.0
	 */
	public function query($q, $options=array()) {
		$default_hash_values = array(
            "includeAllowableActions" => "true",
            "searchAllVersions"       => "false",
            "maxItems"                => 10,
            "skipCount"               => 0
        );

		$hash_values=array_merge($default_hash_values, $options);
		$hash_values['q'] = $q;
		$post_value = parent::processTemplate(self::getQueryTemplate(), $hash_values);
		$ret = $this->doPost($this->workspace->collections[self::COLLECTION_QUERY], $post_value,self::MIME_CMIS_QUERY);
		$objs = $this->extractObjectFeed($ret->body);
		$this->cacheFeedInfo($objs);
 		return $objs;
	}

	/**
	 * @internal
	 * @param string $url
	 * @param string $functionName
	 */
	private function checkURL($url, $functionName=null) {
		if (!$url) {
			throw new CmisNotSupportedException($functionName ? $functionName : "UnspecifiedMethod");
		}
	}

	/**
	 * Get Content Changes
	 *
	 * @param mixed[] $options Options
	 * @returns stdClass[] List of Change Events
	 * @api CMIS-DiscoveryServices
	 * @since CMIS-1.0
	 */
	public function getContentChanges($options = array()) {
		$myURL = parent::processTemplate($this->workspace->links[self::LINK_CHANGES], $options);
		$this->checkURL($myURL,"getContentChanges");
		$ret = $this->doGet($myURL);
		$objs = $this->extractObjectFeed($ret->body);
		$this->cacheFeedInfo($objs);
		return $objs;
	}

	/**
	 * @internal
	 * @return string
	 */
	private static function getEntryTemplate()
	{
        return <<<EOT
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<atom:entry xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200908/"
            xmlns:cmism="http://docs.oasis-open.org/ns/cmis/messaging/200908/"
            xmlns:atom="http://www.w3.org/2005/Atom"
            xmlns:app="http://www.w3.org/2007/app"
            xmlns:cmisra="http://docs.oasis-open.org/ns/cmis/restatom/200908/">
    <atom:title>{title}</atom:title>
    {SUMMARY}
    {CONTENT}
    <cmisra:object><cmis:properties>{PROPERTIES}</cmis:properties></cmisra:object>
</atom:entry>
EOT;
	}

    /**
     * @internal
     * @return string
     */
	private static function getPropertyTemplate() {
		return "
		<cmis:property{propertyType} propertyDefinitionId=\"{propertyId}\">
			<cmis:value>{properties}</cmis:value>
		</cmis:property{propertyType}>
		";
	}

    /**
     * @internal
     * @param string $objectType
     * @param array $propMap
     * @return string
     */
	private function processPropertyTemplates($objectType, $propMap) {
        $propertyTypeMap = array(
            "integer"  => "Integer",
            "boolean"  => "Boolean",
            "datetime" => "DateTime",
            "decimal"  => "Decimal",
            "html"     => "Html",
            "id"       => "Id",
            "string"   => "String",
            "url"      => "Url",
            "xml"      => "Xml",
        );

		$propertyContent = '';
		$hash_values     = array();

		foreach ($propMap as $propId => $propValue) {
			$hash_values['propertyType'] = $propertyTypeMap[$this->getPropertyType($objectType, $propId)];
			$hash_values['propertyId']   = $propId;
			if (is_array($propValue)) {
				foreach ($propValue as $val) {
                    $hash_values['properties'].= sprintf('<cmis:value>%s</cmis:value>', $val);
				}
			}
			else {
				$hash_values['properties'] = $propValue;
			}
			$propertyContent .= parent::processTemplate(self::getPropertyTemplate(), $hash_values);
		}
		return $propertyContent;
	}

	/**
	 * @internal
	 * @param string $content
	 * @param string $content_type
	 * @return string
	 */
	private static function getContentEntry($content, $content_type = "application/octet-stream") {
		if ($content) {
			return parent::processTemplate(self::getContentTemplate(), array (
				"content" => base64_encode($content),
				"content_type" => $content_type
			));
		}
	}

	/**
	 * @internal
	 * @return string
	 */
	private static function getSummaryTemplate() {
		return "<atom:summary>{summary}</atom:summary>";
	}

	/**
	 * @internal
	 * @return string
	 */
	private static function getContentTemplate() {
		return "
		<cmisra:content>
			<cmisra:mediatype>
				{content_type}
			</cmisra:mediatype>
			<cmisra:base64>
				{content}
			</cmisra:base64>
		</cmisra:content>
		";
	}

	/**
	 * @internal
	 * @todo
	 */
	private static function createAtomEntry($name, $properties) {
        // TODO
	}

	/**
	 * Get an Object by Object Id
	 *
	 * @param string $objectId Object ID
	 * @param mixed[] $options Options
	 * @returns stdClass
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function getObject($objectId, $options = array()) {
		$varmap = $options;
		$varmap["id"] = $objectId;
		$obj_url = $this->processTemplate($this->workspace->uritemplates[self::URI_TEMPLATE_OBJECT_BY_ID], $varmap);
		$ret = $this->doGet($obj_url);
		$obj = $this->extractObject($ret->body);
		$this->cacheObjectInfo($obj);
		return $obj;
	}

	/**
	 * Get an Object by its Path
	 *
	 * @param string $path Path To Object
	 * @param mixed[] $options Options
	 * @returns stdClass
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function getObjectByPath($path, $options = array()) {
		$varmap = $options;
		$varmap["path"] = $path;
		$obj_url = $this->processTemplate($this->workspace->uritemplates[self::URI_TEMPLATE_OBJECT_BY_PATH], $varmap);
		$ret = $this->doGet($obj_url);
		$obj = $this->extractObject($ret->body);
		$this->cacheObjectInfo($obj);
		return $obj;
	}

	/**
	 * Get an Object's Properties by Object Id
	 *
	 * @param string $objectId Object Id
	 * @param mixed[] $options Options
	 * @returns stdClass
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function getProperties($objectId, $options = array ()) {
		// May need to set the options array default --
		return $this->getObject($objectId, $options);
	}

	/**
	 * Get an Object's Allowable Actions
	 *
	 * @param string $objectId Object Id
	 * @param mixed[] $options Options
	 * @returns mixed[]
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function getAllowableActions($objectId, $options = array()) {
		$myURL = $this->getLink($objectId, self::LINK_ALLOWABLE_ACTIONS);
		$ret = $this->doGet($myURL);
		$result = $this->extractAllowableActions($ret->body);
		return $result;
	}

	/**
	 * Get the list of associated renditions for the specified object
	 *
	 * Only rendition attributes are returned, not rendition stream.
	 * @param string $objectId Object Id
	 * @param mixed[] $options Options
	 * @returns stdClass[]
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function getRenditions($objectId, $options = array(OPT_RENDITION_FILTER => "*")) {
		return getObject($objectId, $options);
	}

	/**
	 * Get an Object's Allowable Actions
	 *
	 * @param string $objectId Object Id
	 * @param mixed[] $options Options
	 * @returns string
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function getContentStream($objectId, $options = array()) { // Yes
		$myURL = $this->getLink($objectId, "edit-media");
		$ret = $this->doGet($myURL);
		// doRequest stores the last request information in this object
		return $ret->body;
	}

    /**
     * @internal
     */
	private function postObject($folderId, $objectName, $objectType, $properties = array(), $content = null, $content_type = "application/octet-stream", $options = array()) { // Yes
		$myURL = $this->getLink($folderId, self::LINK_DOWN);
		// TODO: Need Proper Query String Handling
		// Assumes that the 'down' link does not have a querystring in it
		$myURL = parent::getOpUrl($myURL, $options);

		$hash_values = is_array($properties) ? $properties : array();
		if (!isset($hash_values["cmis:objectTypeId"])) {
			$hash_values["cmis:objectTypeId"]=$objectType;
		}
		$properties_xml = $this->processPropertyTemplates($objectType, $hash_values);

		$hash_values = is_array($options) ? $options : array();
		$hash_values["PROPERTIES"] = $properties_xml;
		$hash_values["SUMMARY"]    = self::getSummaryTemplate();
		if ($content) {
			$hash_values["CONTENT"] = self::getContentEntry($content, $content_type);
		}

		if (!isset($hash_values['title'])) {
			$hash_values['title']   = preg_replace("/[^A-Za-z0-9\s.&; ]/", '', htmlentities($objectName));
		}
		if (!isset($hash_values['summary'])) {
			$hash_values['summary'] = preg_replace("/[^A-Za-z0-9\s.&; ]/", '', htmlentities($objectName));
		}
		$post_value = parent::processTemplate(self::getEntryTemplate(), $hash_values);

		$ret = $this->doPost($myURL, $post_value, self::MIME_ATOM_XML_ENTRY);
		$obj = $this->extractObject($ret->body);
		$this->cacheObjectInfo($obj);
  		return $obj;
	}

    /**
     * @internal
     * @return stdClass
     */
	private function postEntry($url, $properties = array (), $content = null, $content_type = "application/octet-stream", $options = array ()) {
		// TODO: Fix Hack HERE -- get type if it is there otherwise retrieve it --
		$objectType = "";
		if (isset($properties['cmis:objectTypeId'])) {
			$objType = $properties['cmis:objectTypeId'];
		}
		elseif (isset($properties["cmis:objectId"])) {
			$objType=$this->getObjectType($properties["cmis:objectId"]);
		}

		$myURL = parent::getOpUrl($url, $options);

		//DEBUG
		//print("DEBUG: postEntry: myURL = " . $myURL);
		$properties_xml = $this->processPropertyTemplates($objType, $properties);
		//print("DEBUG: postEntry: properties_xml = " . $properties_xml);

		$hash_values = is_array($options) ? $options : array();
		$hash_values["PROPERTIES"] = $properties_xml;
		$hash_values["SUMMARY"]    = self::getSummaryTemplate();
		if ($content) {
			$hash_values["CONTENT"] = self::getContentEntry($content, $content_type);
		}

		//print("DEBUG: postEntry: hash_values = " . print_r($hash_values,true));
		$post_value = parent::processTemplate(self::getEntryTemplate(), $hash_values);
		//print("DEBUG: postEntry: post_value = " . $post_value);
		$ret = $this->doPost($myURL, $post_value, self::MIME_ATOM_XML_ENTRY);
		$obj = $this->extractObject($ret->body);
		$this->cacheObjectInfo($obj);
		return $obj;
	}

	public function createDocument($folderId, $fileName, $properties = array (), $content = null, $content_type = "application/octet-stream", $options = array ()) { // Yes
		return $this->postObject($folderId, $fileName, "cmis:document", $properties, $content, $content_type, $options);
	}

	public function createDocumentFromSource() { //Yes?
		throw new CmisNotSupportedException("createDocumentFromSource is not supported by the AtomPub binding!");
	}

	public function createFolder($folderId, $folderName, $properties = array (), $options = array ()) { // Yes
		return $this->postObject($folderId, $folderName, "cmis:folder", $properties, null, null, $options);
	}

	public function createRelationship() { // Not in first Release
		throw new CmisNotImplementedException("createRelationship");
	}

	public function createPolicy() { // Not in first Release
		throw new CmisNotImplementedException("createPolicy");
	}

	public function createItem() {
		throw new CmisNotImplementedException("createItem");
	}

	public function updateProperties($objectId, $properties = array(), $options = array()) { // Yes
		$varmap = $options;
		$varmap["id"] = $objectId;
		$objectName = $this->getTitle($objectId);
		$objectType = $this->getObjectType($objectId);
		$obj_url = $this->getLink($objectId, "edit");
		$obj_url = parent::getOpUrl($obj_url, $options);

		$hash_values = is_array($properties) ? $properties : array();

		if (isset($this->_changeToken_cache[$objectId])) {
			$properties['cmis:changeToken'] = $this->_changeToken_cache[$objectId];
		}

		$properties_xml = $this->processPropertyTemplates($objectType, $hash_values);

		$hash_values = is_array($options) ? $options : array();
		$fixed_hash_values = array(
			"PROPERTIES" => $properties_xml,
			"SUMMARY"    => self::getSummaryTemplate(),
		);
		// merge the fixes hash values first so that the processing order is correct
		$hash_values = array_merge($fixed_hash_values, $hash_values);

		if (!isset($hash_values['title'])) {
			$hash_values['title'] = $objectName;
		}
		if (!isset($hash_values['summary'])) {
			$hash_values['summary'] = $objectName;
		}
		$put_value = parent::processTemplate(self::getEntryTemplate(), $hash_values);
		$ret = $this->doPut($obj_url, $put_value, self::MIME_ATOM_XML_ENTRY);
		$obj = $this->extractObject($ret->body);
		$this->cacheObjectInfo($obj);
		return $obj;
	}

	// New for 1.1
	public function bulkUpdateProperties() {
		throw new CmisNotImplementedException("bulkUpdateProperties");
	}

	public function moveObject($objectId, $targetFolderId, $sourceFolderId, $options = array ()) { //yes
		$options['sourceFolderId'] = $sourceFolderId;
		return $this->postObject($targetFolderId, $this->getTitle($objectId), $this->getObjectType($objectId), array (
			"cmis:objectId" => $objectId
		), null, null, $options);
	}

	/**
	 * Delete an Object
	 *
	 * @param string $objectId Object ID
	 * @param mixed[] $options Options
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function deleteObject($objectId, $options = array ()) { //Yes
		$varmap = $options;
		$varmap["id"] = $objectId;
		$obj_url = $this->getLink($objectId, "edit");
		$ret = $this->doDelete($obj_url);
		return;
	}

	/**
	 * Delete an Object Tree
	 *
	 * @param string $folderId Folder Object ID
	 * @param mixed[] $options Options
	 * @return Object[] Array of problem objects
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function deleteTree($folderId, $options = array ()) { // Nice to have
		$hash_values = $options;
		$myURL = $this->getLink($folderId, LINK_DOWN_TREE);
		$myURL = CMISRepositoryWrapper :: getOpUrl($myURL, $hash_values);
		$ret = $this->doDelete($myURL);
		//List of problem objects
		$objs = $this->extractObjectFeed($ret->body);
		$this->cacheFeedInfo($objs);
		return $objs;
	}

	/**
	 * Set an Objects Content Stream
	 *
	 * @param string $objectId Object ID
	 * @param string $content Content to be appended
	 * @param string $content_type Content Mime Type
	 * @param mixed[] $options Options
	 * @returns Object
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function setContentStream($objectId, $content, $content_type, $options = array ()) { //Yes
		$myURL = $this->getLink($objectId, "edit-media");
		$ret = $this->doPut($myURL, $content, $content_type);
	}

	// New for 1.1
	/**
	 * Append Content to an Objects Content Stream
	 *
	 * @param string $objectId Object ID
	 * @param string $content Content to be appended
	 * @param string $content_type Content Mime Type
	 * @param mixed[] $options Options
	 * @returns Object
	 * @api CMIS-ObjectServices-NotImplemented
	 * @since CMIS-1.0
	 */
	public function appendContentStream($objectId, $content, $content_type, $options = array ()) { //Yes
		throw new CmisNotImplementedException("appendContentStream");
	}

	/**
	 * Delete an Objects Content Stream
	 *
	 * @param string $objectId Object ID
	 * @param mixed[] $options Options
	 * @api CMIS-ObjectServices
	 * @since CMIS-1.0
	 */
	public function deleteContentStream($objectId, $options = array()) { //yes
		$myURL = $this->getLink($objectId, "edit-media");
		$ret = $this->doDelete($myURL);
		return $ret;
	}

	//Versioning Services
	public function getPropertiesOfLatestVersion($objectId, $major = false, $options = array ()) {
		return $this->getObjectOfLatestVersion($objectId, $major, $options);
	}

	public function getObjectOfLatestVersion($objectId, $major = false, $options = array ()) {
		return $this->getObject($objectId, $options); // Won't be able to handle major/minor distinction
		// Need to add this -- "current-version"
		/*
		 * Headers: CMIS-filter, CMIS-returnVersion (enumReturnVersion)
		 * HTTP Arguments: filter, returnVersion
		 * Enum returnVersion: This, Latest, Major
		 */
	}

	public function getAllVersions() {
		throw new CmisNotImplementedException("getAllVersions");
	}

	/**
	 * Checkout
	 *
	 * @param string $objectId Object ID
	 * @param mixed[] $options Options
	 * @return stdClass The working copy
	 * @api CMIS-VersionServices
	 * @since CMIS-1.0
	 */
	public function checkOut($objectId,$options = array()) {
		$myURL = $this->workspace->collections[self::COLLECTION_CHECKED_OUT];
		$myURL = parent::getOpUrl($myURL, $options);
		$ret = $this->postEntry($myURL,  array ("cmis:objectId" => $objectId));
		$obj = $this->extractObject($ret->body);
		$this->cacheObjectInfo($obj);
		return $obj;
	}

	/**
	 * Checkin
	 *
	 * @param string $objectId Object ID
	 * @param mixed[] $options Options
	 * @return Object The checked in object
	 * @api CMIS-VersionServices
	 * @since CMIS-1.0
	 */
	public function checkIn($objectId,$options = array()) {
		$myURL = $this->workspace->collections[self::COLLECTION_CHECKED_OUT];
		$myURL = parent::getOpUrl($myURL, $options);
		$ret = $this->postEntry($myURL,  array ("cmis:objectId" => $objectId));
		$obj = $this->extractObject($ret->body);
		$this->cacheObjectInfo($obj);
		return $obj;
	}

	/**
	 * Cancel Checkout
	 *
	 * @param string $objectId Object ID
	 * @param mixed[] $options Options
	 * @api CMIS-VersionServices
	 * @since CMIS-1.0
	 */
	public function cancelCheckOut($objectId, $options = array()) {
		// TODO: Look at links "via" and "working-copy"
		$varmap = $options;
		$varmap["id"] = $objectId;
		$via = $this->getLink($objectId,"via");
		//print("DEBUG: cancelCheckOut VIA="+$via);
		if (!$via) {
			throw new CmisInvalidArgumentException("Not a WORKING COPY!");
		}
		$obj_url = $this->getLink($objectId, "edit");
		$ret = $this->doDelete($obj_url);
		return;
	}

	public function deleteAllVersions() {
		throw new CmisNotImplementedException("deleteAllVersions");
	}

	//Relationship Services
	public function getObjectRelationships() {
		// get stripped down version of object (for the links) and then get the relationships?
		// Low priority -- can get all information when getting object
		throw new CmisNotImplementedException("getObjectRelationships");
	}

	//Multi-Filing ServicesRelation
	public function addObjectToFolder($objectId, $targetFolderId, $options = array ()) { // Probably
		return $this->postObject(
            $targetFolderId,
            $this->getTitle($objectId),
            $this->getObjectType($objectId),
            array ("cmis:objectId" => $objectId),
            null,
            null,
            $options
        );
	}

	public function removeObjectFromFolder($objectId, $targetFolderId, $options = array()) { //Probably
		$hash_values = $options;
		$myURL = $this->workspace->collections['unfiled'];
		$myURL = parent::getOpUrl($myURL, $hash_values);
		$ret = $this->postEntry($myURL, array("cmis:objectId" => $objectId), null, null, array("removeFrom" => $targetFolderId));
		$obj = $this->extractObject($ret->body);
		$this->cacheObjectInfo($obj);
		return $obj;
	}

	//Policy Services
	public function getAppliedPolicies() {
		throw new CmisNotImplementedException("getAppliedPolicies");
	}

	public function applyPolicy() {
		throw new CmisNotImplementedException("applyPolicy");
	}

	public function removePolicy() {
		throw new CmisNotImplementedException("removePolicy");
	}

	//ACL Services
	public function getACL() {
		throw new CmisNotImplementedException("getACL");
	}

	public function applyACL() {
		throw new CmisNotImplementedException("applyACL");
	}
}
