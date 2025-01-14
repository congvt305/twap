<?php
/**
 * @author Eguana Team
 * @copyriht Copyright (c) 2020 Eguana {http://eguanacommerce.com}
 * Created by PhpStorm
 * User: abbas
 * Date: 20. 5. 26
 * Time: 오전 11:47
 */

namespace Amore\CustomerRegistration\Test\Unit;

use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

/**
 * To test the module.xml
 * Class ModelXmlTest
 * @package Amore\CustomerRegistration\Test\Unit
 */
class ModuleXmlTest extends TestCase
{
    /**
     * @var string
     */
    private $moduleName = 'Amore_CustomerRegistration';

    /**
     * @var array
     */
    private $moduleDependencies = ['Magento_Customer', 'Magento_CustomerCustomAttributes'];

    /**
     * @var SimpleXMLElement
     */
    private $xml;


    protected function setUp(): void
    {
        $this->xml = simplexml_load_file(__DIR__.'/../../etc/module.xml');
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    /**
     * Test the xml of module module.xml
     * In it we will verify the module name and dependencies of module
     */
    public function testXML()
    {
        $numberOfDependencies = 0;
        $this->assertEquals($this->moduleName, $this->xml->module->attributes()['name']);
        $actualDependencies = [];
        foreach ($this->xml->sequence->module as $module)
        {
            if(!in_array((string)$module->attributes()['name'], $this->moduleDependencies))
            {
                $this->assertFalse(true, "Unexpected ".$module->attributes()['name']." in the list of module dependencies");
            }
            $numberOfDependencies++;
        }
        $this->assertEquals(count($this->moduleDependencies), $numberOfDependencies);
    }
}