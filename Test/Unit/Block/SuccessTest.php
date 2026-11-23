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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Block;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\Block\Success;
use Bigfishpaymentgateway\Pmgw\Gateway\Helper\Helper;
use Bigfishpaymentgateway\Pmgw\Model\Transaction;
use Bigfishpaymentgateway\Pmgw\Model\Log;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Config as OrderConfig;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Model\MethodInterface;

class SuccessTest extends TestCase
{
    /**
     * @var Context&MockObject
     */
    private $contextMock;

    /**
     * @var Session&MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var OrderConfig&MockObject
     */
    private $orderConfigMock;

    /**
     * @var HttpContext&MockObject
     */
    private $httpContextMock;

    /**
     * @var Helper&MockObject
     */
    private $helperMock;

    /**
     * @var JsonHelper&MockObject
     */
    private $jsonHelperMock;

    /**
     * @var Order&MockObject
     */
    private $orderMock;

    /**
     * @var Payment&MockObject
     */
    private $orderPaymentMock;

    /**
     * @var Transaction&MockObject
     */
    private $transactionMock;

    /**
     * @var Log&MockObject
     */
    private $transactionLogMock;

    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderConfigMock = $this->getMockBuilder(OrderConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->httpContextMock = $this->getMockBuilder(HttpContext::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonHelperMock = $this->getMockBuilder(JsonHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $method = $this->createStub(MethodInterface::class);
        $method->method('getTitle')->willReturn('Test payment');
        $this->orderPaymentMock->method('getMethodInstance')->willReturn($method);
        $this->orderMock->method('getUpdatedAt')->willReturn('2024-01-01 00:00:00');
        $this->orderMock->method('getBaseCurrencyCode')->willReturn('HUF');
        $this->orderMock->method('getGrandTotal')->willReturn(100);

        $this->transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionLogMock = $this->getMockBuilder(Log::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testCreateBlockAfterPaymentWithLoggedInUser()
    {
        $block = new Success(
            $this->contextMock,
            $this->checkoutSessionMock,
            $this->orderConfigMock,
            $this->httpContextMock,
            $this->helperMock,
            $this->jsonHelperMock,
            []
        );

        $result = [
            'ResultMessage' => 'test_result_message',
            'ProviderTransactionId' => 'test_provider_transaction_id',
            'Anum' => 'test_anum',
        ];

        $this->preparePayment($result, 1);

        $this->assertEquals($block, $block->_beforeToHtml());

        $this->assertEquals([
            'order_id' => '0000001',
            'order_updated_at' => '2024-01-01 00:00:00',
            'order_currency_code' => 'HUF',
            'order_total' => 100.0,
            'method_title' => 'Test payment',
            'can_view_order' => true,
            'response' => (object)$result,
        ], $block->getData());
    }

    public function testCreateBlockAfterPaymentWithGuestUser()
    {
        $block = new Success(
            $this->contextMock,
            $this->checkoutSessionMock,
            $this->orderConfigMock,
            $this->httpContextMock,
            $this->helperMock,
            $this->jsonHelperMock,
            []
        );

        $result = [
            'ResultMessage' => 'test_result_message',
            'ProviderTransactionId' => 'test_provider_transaction_id',
            'Anum' => 'test_anum',
        ];

        $this->preparePayment($result, 0);

        $this->assertEquals($block, $block->_beforeToHtml());

        $this->assertEquals([
            'order_id' => '0000001',
            'order_updated_at' => '2024-01-01 00:00:00',
            'order_currency_code' => 'HUF',
            'order_total' => 100.0,
            'method_title' => 'Test payment',
            'can_view_order' => false,
            'response' => (object)$result,
        ], $block->getData());
    }

    public function testInvalidOrder()
    {
        $block = new Success(
            $this->contextMock,
            $this->checkoutSessionMock,
            $this->orderConfigMock,
            $this->httpContextMock,
            $this->helperMock,
            $this->jsonHelperMock,
            []
        );

        $this->setCheckoutSessionMockGetLastRealOrder(null);

        $this->assertEquals($block, $block->_beforeToHtml());

        $this->assertEquals([], $block->getData());
    }

    public function testInvalidOrderPayment()
    {
        $block = new Success(
            $this->contextMock,
            $this->checkoutSessionMock,
            $this->orderConfigMock,
            $this->httpContextMock,
            $this->helperMock,
            $this->jsonHelperMock,
            []
        );

        $this->setCheckoutSessionMockGetLastRealOrder($this->orderMock);
        $this->setOrderMockGetId();
        $this->setOrderMockGetPayment(null);

        $this->assertEquals($block, $block->_beforeToHtml());

        $this->assertEquals([], $block->getData());
    }

    public function testInvalidTransactionId()
    {
        $block = new Success(
            $this->contextMock,
            $this->checkoutSessionMock,
            $this->orderConfigMock,
            $this->httpContextMock,
            $this->helperMock,
            $this->jsonHelperMock,
            []
        );

        $this->setCheckoutSessionMockGetLastRealOrder($this->orderMock);
        $this->setOrderMockGetId();
        $this->setOrderMockGetPayment($this->orderPaymentMock);
        $this->setOrderPaymentMockGetLastTransId(null);

        $this->assertEquals($block, $block->_beforeToHtml());

        $this->assertEquals([], $block->getData());
    }

    public function testInvalidTransaction()
    {
        $block = new Success(
            $this->contextMock,
            $this->checkoutSessionMock,
            $this->orderConfigMock,
            $this->httpContextMock,
            $this->helperMock,
            $this->jsonHelperMock,
            []
        );

        $this->setCheckoutSessionMockGetLastRealOrder($this->orderMock);
        $this->setOrderMockGetId();
        $this->setOrderMockGetPayment($this->orderPaymentMock, 2);
        $this->setOrderPaymentMockGetLastTransId('test_transaction_id');
        $this->setOrderMockGetIncementId();
        $this->setHttpContextMockGetValueCustomerLoggedIn(1);
        $this->setOrderMockGetStatus();
        $this->getOrderConfigMockGetInvisibleOnFrontStatuses();
        $this->setHelperMockGetTransactionByTransactionId('test_transaction_id', null);

        $this->assertEquals($block, $block->_beforeToHtml());

        $this->assertEquals([
            'order_id' => '0000001',
            'order_updated_at' => '2024-01-01 00:00:00',
            'order_currency_code' => 'HUF',
            'order_total' => 100.0,
            'method_title' => 'Test payment',
            'can_view_order' => true,
            'response' => null,
        ], $block->getData());
    }

    public function testInvalidTransactionLog()
    {
        $block = new Success(
            $this->contextMock,
            $this->checkoutSessionMock,
            $this->orderConfigMock,
            $this->httpContextMock,
            $this->helperMock,
            $this->jsonHelperMock,
            []
        );

        $this->setCheckoutSessionMockGetLastRealOrder($this->orderMock);
        $this->setOrderMockGetId();
        $this->setOrderMockGetPayment($this->orderPaymentMock, 2);
        $this->setOrderPaymentMockGetLastTransId('test_transaction_id');
        $this->setOrderMockGetIncementId();
        $this->setHttpContextMockGetValueCustomerLoggedIn(1);
        $this->setOrderMockGetStatus();
        $this->getOrderConfigMockGetInvisibleOnFrontStatuses();
        $this->setHelperMockGetTransactionByTransactionId('test_transaction_id', $this->transactionMock);
        $this->setTransactionMockGetId();
        $this->setHelperMockGetTransactionLog($this->transactionMock, null);

        $this->assertEquals($block, $block->_beforeToHtml());

        $this->assertEquals([
            'order_id' => '0000001',
            'order_updated_at' => '2024-01-01 00:00:00',
            'order_currency_code' => 'HUF',
            'order_total' => 100.0,
            'method_title' => 'Test payment',
            'can_view_order' => true,
            'response' => null,
        ], $block->getData());
    }

    /**
     * @param array $result
     * @param int $isLoggedIn
     */
    private function preparePayment(array $result, $isLoggedIn)
    {
        $this->setCheckoutSessionMockGetLastRealOrder($this->orderMock);
        $this->setOrderMockGetId();
        $this->setOrderMockGetPayment($this->orderPaymentMock, 2);
        $this->setOrderPaymentMockGetLastTransId('test_transaction_id');
        $this->setOrderMockGetIncementId();
        $this->setHttpContextMockGetValueCustomerLoggedIn($isLoggedIn);

        if ($isLoggedIn) {
            $this->setOrderMockGetStatus();
            $this->getOrderConfigMockGetInvisibleOnFrontStatuses();
        }

        $this->setHelperMockGetTransactionByTransactionId('test_transaction_id', $this->transactionMock);
        $this->setTransactionMockGetId();
        $this->setHelperMockGetTransactionLog($this->transactionMock, $this->transactionLogMock);
        $this->setTransactionLogMockGetId();
        $this->setTransactionLogMockGetData($result);
        $this->setJsonHelperMockJsonDecode($result);
    }

    /**
     * @param MockObject|null $orderMock
     */
    private function setCheckoutSessionMockGetLastRealOrder(
        ?MockObject $orderMock = null
    ) {
        $this->checkoutSessionMock->expects(static::once())
            ->method('getLastRealOrder')
            ->willReturn($orderMock);
    }

    private function setOrderMockGetId()
    {
        $this->orderMock->expects(static::once())
            ->method('getId')
            ->willReturn(1);
    }

    /**
     * @param MockObject|null $payment
     * @param int $calls
     */
    private function setOrderMockGetPayment(
        ?MockObject $payment = null,
        $calls = 1
    ) {
        $this->orderMock->expects(static::exactly($calls))
            ->method('getPayment')
            ->willReturn($payment);
    }

    /**
     * @param $transactionId
     */
    private function setOrderPaymentMockGetLastTransId($transactionId)
    {
        $this->orderPaymentMock->expects(static::once())
            ->method('getLastTransId')
            ->willReturn($transactionId);
    }

    private function setOrderMockGetIncementId()
    {
        $this->orderMock->expects(static::once())
            ->method('getIncrementId')
            ->willReturn('0000001');
    }

    /**
     * @param $isLoggedIn
     */
    private function setHttpContextMockGetValueCustomerLoggedIn($isLoggedIn)
    {
        $this->httpContextMock->expects(static::once())
            ->method('getValue')
            ->with('customer_logged_in')
            ->willReturn($isLoggedIn);
    }

    private function setOrderMockGetStatus()
    {
        $this->orderMock->expects(static::once())
            ->method('getStatus')
            ->willReturn(100);
    }

    private function getOrderConfigMockGetInvisibleOnFrontStatuses()
    {
        $this->orderConfigMock->expects(static::once())
            ->method('getInvisibleOnFrontStatuses')
            ->willReturn([]);
    }

    /**
     * @param string|null $transactionId
     * @param MockObject|null $transaction
     */
    private function setHelperMockGetTransactionByTransactionId(
        $transactionId,
        ?MockObject $transaction = null
    ) {
        $this->helperMock->expects(static::once())
            ->method('getTransactionByTransactionId')
            ->with($transactionId)
            ->willReturn($transaction);
    }

    private function setTransactionMockGetId()
    {
        $this->transactionMock->expects(static::once())
            ->method('getId')
            ->willReturn(1000);
    }

    /**
     * @param MockObject|null $transactionMock
     * @param MockObject|null $transactionLogMock
     */
    private function setHelperMockGetTransactionLog(
        ?MockObject $transactionMock = null,
        ?MockObject $transactionLogMock = null
    ) {
        $this->helperMock->expects(static::once())
            ->method('getTransactionLog')
            ->with($transactionMock)
            ->willReturn($transactionLogMock);
    }

    private function setTransactionLogMockGetId()
    {
        $this->transactionLogMock->expects(static::once())
            ->method('getId')
            ->willReturn(2000);
    }

    /**
     * @param array $result
     */
    private function setTransactionLogMockGetData(array $result)
    {
        $this->transactionLogMock->expects(static::once())
            ->method('getData')
            ->willReturn(json_encode($result));
    }

    /**
     * @param array $result
     */
    private function setJsonHelperMockJsonDecode(array $result)
    {
        $this->jsonHelperMock->expects(static::once())
            ->method('jsonDecode')
            ->with(json_encode($result))
            ->willReturn($result);
    }
}
