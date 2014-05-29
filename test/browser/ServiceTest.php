<?php
/**
 * @copyright 2014 City of Bloomington
 * @author Cliff Ingham <inghamn@bloomington.in.gov>
 */
require_once './configuration.inc';
require_once '../../browser/cmis_service.php';

class ServiceTest extends PHPUnit_Framework_TestCase
{
    private $service;

    public function __construct()
    {
        $this->service = new CMISService(CMIS_URL, CMIS_USERNAME, CMIS_PASSWORD, CMIS_REPOSITORY_ID);
    }

    /**
     * This test gets an known folder and tests the ability to retreive known properties
     */
    public function testGetObjectByPath()
    {
        $folder = $this->service->getObjectByPath("/Sites");

        $this->assertEquals("F:st:sites", $folder->properties->{'cmis:objectTypeId'}->value);
        $this->assertEquals("cmis:folder",$folder->properties->{'cmis:baseTypeId'}->value);
    }

    public function testSuccinctMode()
    {
		$this->service->succinct = true;
		$folder = $this->service->getObjectByPath('/Sites');
		$this->assertObjectNotHasAttribute('properties', $folder);
		$this->assertObjectHasAttribute('succinctProperties', $folder);

		$this->service->succinct = false;
		$folder = $this->service->getObjectByPath('/Sites');
		$this->assertObjectNotHasAttribute('succinctProperties', $folder);
		$this->assertObjectHasAttribute('properties', $folder);
    }

    public function testGetObject()
    {
		$this->service->succinct = true;

		$folder = $this->service->getObjectByPath('/Sites');

		$id = $folder->succinctProperties->{'cmis:objectId'};

		$o = $this->service->getObject($id);
		$this->assertEquals($id, $o->succinctProperties->{'cmis:objectId'});
    }

    public function testGetFolderParent()
    {
		$this->service->succinct = true;

		$folder = $this->service->getObjectByPath("/Sites");
		$parent = $this->service->getFolderParent($folder->succinctProperties->{'cmis:objectId'});
		$root   = $this->service->getObjectByPath("/");

		$this->assertEquals(
			$parent->succinctProperties->{'cmis:objectId'},
			  $root->succinctProperties->{'cmis:objectId'}
		);
    }

}
