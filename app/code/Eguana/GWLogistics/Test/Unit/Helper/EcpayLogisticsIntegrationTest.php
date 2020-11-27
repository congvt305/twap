<?php
/**
 * Created by Eguana Team.
 * User: sonia
 * Date: 6/21/20
 * Time: 6:32 AM
 */

namespace Eguana\GWLogistics\Test\Unit\Helper;

use Eguana\GWLogistics\Helper\EcpayLogisticsIntegration;
use PHPUnit\Framework\TestCase;

class EcpayLogisticsIntegrationTest extends TestCase
{
    /**
     * @var EcpayLogisticsIntegration
     */
    private $ecpayLogisticsIntegrationHelper;

    protected function setUp(): void
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->ecpayLogisticsIntegrationHelper = $objectManager->getObject(EcpayLogisticsIntegration::class);
        parent::setUp(); // TODO: Change the autogenerated stub
    }
    public function testDoShipment()
    {
        $this->expectOutputString('');
        $this->ecpayLogisticsIntegrationHelper->doShipment();

    }

    public function testGetDirectory()
    {
        $expected = '1';

        $this->assertEquals(
            $expected,
            $this->ecpayLogisticsIntegrationHelper->getDirectory()
        );
    }


}
