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
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;

abstract class PaymentTestCase extends TestCase
{
    /**
     * @var RequestInterface&MockObject
     */
    protected $requestMock;

    /**
     * @var ResponseInterface&MockObject
     */
    protected $responseMock;

    /**
     * @var RedirectFactory&MockObject
     */
    protected $resultRedirectFactoryMock;

    /**
     * @var ResultFactory&MockObject
     */
    protected $resultFactoryMock;

    /**
     * @var Context&MockObject
     */
    protected $contextMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMock();

        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();

        $this->resultRedirectFactoryMock = $this->getMockBuilder(RedirectFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock->expects(static::once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->contextMock->expects(static::once())
            ->method('getResponse')
            ->willReturn($this->responseMock);

        $this->contextMock->expects(static::once())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactoryMock);

        $this->contextMock->expects(static::once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactoryMock);
    }
}
