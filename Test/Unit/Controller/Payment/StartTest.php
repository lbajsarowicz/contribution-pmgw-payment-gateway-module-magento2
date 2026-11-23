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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Controller\Payment;

use PHPUnit\Framework\MockObject\MockObject;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\SessionFixture as Session;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\Model\Response\ResultInterface;
use Bigfishpaymentgateway\Pmgw\Controller\Payment\Start as StartController;

class StartTest extends PaymentTestCase
{
    /**
     * @var Session&MockObject
     */
    private $sessionMock;

    /**
     * @var ResultInterface&MockObject
     */
    private $resultMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultMock = $this->getMockBuilder(ResultInterface::class)
            ->getMock();
    }

    public function testExecuteWithRedirectUrl()
    {
        $this->sessionMock->expects(static::once())
            ->method('getPmgwRedirectUrlValue')
            ->willReturn('http://example.com/redirect_url');

        $this->resultFactoryMock->expects(static::once())
            ->method('create')
            ->with('redirect')
            ->willReturn($this->resultMock);

        $controller = new StartController($this->contextMock, $this->sessionMock);

        $this->assertEquals($this->resultMock, $controller->execute());
    }

    public function testExecuteWithoutRedirectUrl()
    {
        $this->sessionMock->expects(static::once())
            ->method('getPmgwRedirectUrlValue')
            ->willReturn(null);

        $this->resultFactoryMock->expects(static::never())
            ->method('create');

        $controller = new StartController($this->contextMock, $this->sessionMock);

        $this->assertEquals(false, $controller->execute());
    }
}
