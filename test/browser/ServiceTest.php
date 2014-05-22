<?php
/**
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
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

    public function testGetObject()
    {
		$folder = $this->service->getObjectByPath('/Sites');
		$this->assertEquals('CMISObject', get_class($folder));

		$id = $folder->get('cmis:objectId');

		$o = $this->service->getObject($id);
		$this->assertEquals('CMISObject', get_class($o));

		$this->assertEquals($id, $o->get('cmis:objectId'));
    }

    public function testGetFolderParent()
    {
		$folder = $this->service->getObjectByPath("/Sites");
		$parent = $this->service->getFolderParent($folder->get('cmis:objectId'));
		$root   = $this->service->getObjectByPath("/");

		$this->assertEquals('CMISObject', get_class($parent));
		$this->assertEquals($parent->get('cmis:objectId'), $root->get('cmis:objectId'));
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
}
