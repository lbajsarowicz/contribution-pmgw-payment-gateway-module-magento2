<?php
/**
 * BIG FISH Payment Services Ltd.
 * https://paymentgateway.hu
 *
 * @title      BIG FISH Payment Gateway module for Magento 2
 * @category   BigFish
 * @package    Bigfishpaymentgateway_Pmgw
 * @author     BIG FISH Payment Services Ltd., it [at] paymentgateway [dot] hu
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @copyright  Copyright (c) 2024, BIG FISH Payment Services Ltd.
 */
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Gateway\Http;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Bigfishpaymentgateway\Pmgw\Gateway\Http\TransferFactory;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;

class TransferFactoryTest extends TestCase
{
    /**
     * @var TransferBuilder&MockObject
     */
    private $trasferBuilderMock;

    /**
     * @var TransferInterface&MockObject
     */
    private $transferMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trasferBuilderMock = $this->getMockBuilder(TransferBuilder::class)
            ->getMock();

        $this->transferMock = $this->getMockBuilder(TransferInterface::class)
            ->getMock();
    }

    public function testCreate()
    {
        $request = ['foo' => 'bar'];

        $factory = new TransferFactory($this->trasferBuilderMock);

        $this->trasferBuilderMock->expects(static::once())
            ->method('setBody')
            ->with($request)
            ->willReturnSelf();

        $this->trasferBuilderMock->expects(static::once())
            ->method('build')
            ->willReturn($this->transferMock);

        $this->assertEquals($this->transferMock, $factory->create($request));
    }
}
