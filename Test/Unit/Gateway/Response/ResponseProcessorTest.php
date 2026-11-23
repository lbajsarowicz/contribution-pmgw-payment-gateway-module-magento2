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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Gateway\Response;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use BigFish\PaymentGateway;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Bigfishpaymentgateway\Pmgw\Model\Response\Result;
use Psr\Log\LoggerInterface;
use Bigfishpaymentgateway\Pmgw\Gateway\Helper\Helper;
use Bigfishpaymentgateway\Pmgw\Gateway\Response\ResponseProcessor;
use BigFish\PaymentGateway\Transport\Response\Response;
use Bigfishpaymentgateway\Pmgw\Model\Transaction;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilderInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class ResponseProcessorTest extends TestCase
{
    /**
     * @var OrderPaymentInterface&MockObject
     */
    private $paymentMock;

    /**
     * @var Invoice&MockObject
     */
    private $invoiceMock;

    /**
     * @var Order&MockObject
     */
    private $orderMock;

    /**
     * @var OrderSender&MockObject
     */
    private $orderSenderMock;

    /**
     * @var LoggerInterface&MockObject
     */
    private $loggerMock;

    /**
     * @var Transaction&MockObject
     */
    private $transactionMock;

    /**
     * @var Helper&MockObject
     */
    private $helperMock;

    /**
     * @var TransactionBuilderInterface&MockObject
     */
    private $transactionBuilderInterfaceMock;

    /**
     * @var OrderFactory&MockObject
     */
    private $orderFactoryMock;

    /**
     * @var Result
     */
    private $result;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMock = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->invoiceMock = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderSenderMock = $this->getMockBuilder(OrderSender::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helperMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionBuilderInterfaceMock = $this->getMockBuilder(TransactionBuilderInterface::class)
            ->getMock();

        $this->orderFactoryMock = $this->getMockBuilder(OrderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderFactoryMock->method('create')->willReturn($this->orderMock);
        $this->orderMock->method('loadByIncrementId')->willReturnSelf();

        $this->result = new Result();
    }

    public function testMissingTransactionIdFromResponseObject()
    {
        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'ResultCode' => PaymentGateway::RESULT_CODE_PENDING,
        ]));

        $responseProcessor->setResponse($response);

        $responseProcessor->processResponse();

        $this->assertEquals('ERROR', $this->result->getCode());
        $this->assertEquals('Missing or invalid transaction id.', $this->result->getMessage());
    }

    public function testMissingResultCodeFromResponseObject()
    {
        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
        ]));

        $responseProcessor->setResponse($response);

        $responseProcessor->processResponse();

        $this->assertEquals('ERROR', $this->result->getCode());
        $this->assertEquals('Missing or invalid result code.', $this->result->getMessage());
    }

    public function testTransactionNotFound()
    {
        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_PENDING,
            'ResultMessage' => 'pending message',
        ]));

        $responseProcessor->setResponse($response);

        $responseProcessor->processResponse();

        $this->assertEquals('ERROR', $this->result->getCode());
        $this->assertEquals('Transaction not found.', $this->result->getMessage());
    }

    public function testOrderNotFound()
    {
        $this->setTransactionMockGetId(1);
        $this->getHelperMockGetTransactionByTransactionId();

        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_PENDING,
            'ResultMessage' => 'pending message',
        ]));

        $responseProcessor->setResponse($response);

        $responseProcessor->processResponse();

        $this->assertEquals('ERROR', $this->result->getCode());
        $this->assertEquals('Order not found.', $this->result->getMessage());
    }

    public function testInvalidPaymentMethod()
    {
        $this->setPaymentMockGetMethod('checkmo');
        $this->setOrderMockGetId(1);
        $this->setOrderMockGetPayment();
        $this->setTransactionMockGetId(1);
        $this->getHelperMockGetTransactionByTransactionId();

        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_PENDING,
            'ResultMessage' => 'pending message',
        ]));

        $responseProcessor->setResponse($response);

        $responseProcessor->processResponse();

        $this->assertEquals('ERROR', $this->result->getCode());
        $this->assertEquals('Invalid payment method.', $this->result->getMessage());
    }

    public function testProcessPendingResponse()
    {
        $this->setPaymentMockGetMethod('bigfishpaymentgateway_pmgw_test');
        $this->setOrderMockGetId(1);
        $this->setOrderMockGetPayment();
        $this->setTransactionMockGetId(1);
        $this->getHelperMockGetTransactionByTransactionId();

        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_PENDING,
            'ResultMessage' => 'pending message',
        ]));

        $responseProcessor->setResponse($response);

        $this->paymentMock->expects(static::once())
            ->method('setLastTransId')
            ->with('test');

        $this->orderMock->expects(static::once())
            ->method('setState')
            ->with('pending_payment');

        $this->orderMock->expects(static::never())
            ->method('prepareInvoice');

        $this->orderSenderMock->expects(static::never())
            ->method('send');

        $this->helperMock->expects(static::once())
            ->method('updateTransactionStatus')
            ->with($this->transactionMock, 120);

        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, $response->getData());

        $responseProcessor->processResponse();

        $this->assertEquals('PENDING', $this->result->getCode());
        $this->assertEquals('pending message', $this->result->getMessage());
    }

    public function testProcessSuccessResponseWithAnum()
    {
        $this->setPaymentMockGetMethod('bigfishpaymentgateway_pmgw_test');
        $this->setInvoiceMockRegister();
        $this->setOrderMockGetId(1);
        $this->setOrderMockCanInvoice(true);

        $this->setOrderMockPrepareInvoice();

        $this->setOrderMockGetPayment();
        $this->setTransactionMockGetId(1);
        $this->getHelperMockGetTransactionByTransactionId();

        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'ResultMessage' => 'success message',
            'Anum' => '123456',
        ]));

        $responseProcessor->setResponse($response);

        $this->paymentMock->expects(static::once())
            ->method('setLastTransId')
            ->with('test');

        $this->paymentMock->expects(static::once())
            ->method('setPoNumber')
            ->with('123456');

        $this->orderMock->expects(static::once())
            ->method('setState')
            ->with('processing');

        $this->orderMock->expects(static::once())
            ->method('prepareInvoice');

        $this->orderSenderMock->expects(static::once())
            ->method('send')
            ->with($this->orderMock, false);

        $this->helperMock->expects(static::once())
            ->method('updateTransactionStatus')
            ->with($this->transactionMock, 200);

        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, $response->getData());

        $responseProcessor->processResponse();

        $this->assertEquals('SUCCESSFUL', $this->result->getCode());
        $this->assertEquals('success message', $this->result->getMessage());
    }

    public function testProcessSuccessResponseWithoutAnum()
    {
        $this->setPaymentMockGetMethod('bigfishpaymentgateway_pmgw_test');
        $this->setInvoiceMockRegister();
        $this->setOrderMockGetId(1);
        $this->setOrderMockGetPayment();
        $this->setOrderMockCanInvoice(true);
        $this->setOrderMockPrepareInvoice();
        $this->setTransactionMockGetId(1);
        $this->getHelperMockGetTransactionByTransactionId();

        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'ResultMessage' => 'success message',
        ]));

        $responseProcessor->setResponse($response);

        $this->paymentMock->expects(static::once())
            ->method('setLastTransId')
            ->with('test');

        $this->paymentMock->expects(static::never())
            ->method('setPoNumber');

        $this->orderMock->expects(static::once())
            ->method('setState')
            ->with('processing');

        $this->orderMock->expects(static::once())
            ->method('prepareInvoice');

        $this->orderSenderMock->expects(static::once())
            ->method('send')
            ->with($this->orderMock, false);

        $this->helperMock->expects(static::once())
            ->method('updateTransactionStatus')
            ->with($this->transactionMock, 200);

        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, $response->getData());

        $responseProcessor->processResponse();

        $this->assertEquals('SUCCESSFUL', $this->result->getCode());
        $this->assertEquals('success message', $this->result->getMessage());
    }

    public function testProcessUserCancelResponse()
    {
        $this->setPaymentMockGetMethod('bigfishpaymentgateway_pmgw_test');
        $this->setOrderMockGetId(1);
        $this->setOrderMockGetPayment();
        $this->setTransactionMockGetId(1);
        $this->getHelperMockGetTransactionByTransactionId();

        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_USER_CANCEL,
            'ResultMessage' => 'user cancel message',
        ]));

        $responseProcessor->setResponse($response);

        $this->paymentMock->expects(static::once())
            ->method('setLastTransId')
            ->with('test');

        $this->orderMock->expects(static::once())
            ->method('cancel');

        $this->orderMock->expects(static::never())
            ->method('setState');

        $this->orderMock->expects(static::never())
            ->method('prepareInvoice');

        $this->orderSenderMock->expects(static::never())
            ->method('send');

        $this->helperMock->expects(static::once())
            ->method('updateTransactionStatus')
            ->with($this->transactionMock, 220);

        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, $response->getData());

        $responseProcessor->processResponse();

        $this->assertEquals('CANCELED', $this->result->getCode());
        $this->assertEquals('user cancel message', $this->result->getMessage());
    }

    public function testProcessErrorResponse()
    {
        $this->setPaymentMockGetMethod('bigfishpaymentgateway_pmgw_test');
        $this->setOrderMockGetId(1);
        $this->setOrderMockGetPayment();
        $this->setTransactionMockGetId(1);
        $this->getHelperMockGetTransactionByTransactionId();

        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_ERROR,
            'ResultMessage' => 'error message',
        ]));

        $responseProcessor->setResponse($response);

        $this->paymentMock->expects(static::once())
            ->method('setLastTransId')
            ->with('test');

        $this->orderMock->expects(static::once())
            ->method('cancel');

        $this->orderMock->expects(static::never())
            ->method('setState');

        $this->orderMock->expects(static::never())
            ->method('prepareInvoice');

        $this->orderSenderMock->expects(static::never())
            ->method('send');

        $this->helperMock->expects(static::once())
            ->method('updateTransactionStatus')
            ->with($this->transactionMock, 210);

        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, $response->getData());

        $responseProcessor->processResponse();

        $this->assertEquals('ERROR', $this->result->getCode());
        $this->assertEquals('error message', $this->result->getMessage());
    }

    public function testProcessTimeoutResponse()
    {
        $this->setPaymentMockGetMethod('bigfishpaymentgateway_pmgw_test');
        $this->setOrderMockGetId(1);
        $this->setOrderMockGetPayment();
        $this->setTransactionMockGetId(1);
        $this->getHelperMockGetTransactionByTransactionId();

        $responseProcessor = new ResponseProcessor(
            $this->orderMock,
            $this->orderSenderMock,
            $this->result,
            $this->loggerMock,
            $this->helperMock,
            $this->transactionBuilderInterfaceMock,
            $this->orderFactoryMock,
            $this->createStub(OrderResource::class)
        );

        $response = Response::createFromJson(json_encode([
            'TransactionId' => 'test',
            'ResultCode' => PaymentGateway::RESULT_CODE_TIMEOUT,
            'ResultMessage' => 'timeout message',
        ]));

        $responseProcessor->setResponse($response);

        $this->paymentMock->expects(static::once())
            ->method('setLastTransId')
            ->with('test');

        $this->orderMock->expects(static::once())
            ->method('cancel');

        $this->orderMock->expects(static::never())
            ->method('setState');

        $this->orderMock->expects(static::never())
            ->method('prepareInvoice');

        $this->orderSenderMock->expects(static::never())
            ->method('send');

        $this->helperMock->expects(static::once())
            ->method('updateTransactionStatus')
            ->with($this->transactionMock, 210);

        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, $response->getData());

        $responseProcessor->processResponse();

        $this->assertEquals('TIMEOUT', $this->result->getCode());
        $this->assertEquals('timeout message', $this->result->getMessage());
    }

    /**
     * @param string $method
     */
    protected function setPaymentMockGetMethod($method)
    {
        $this->paymentMock->method('getMethod')
            ->willReturn($method);
    }

    /**
     * @param integer $id
     */
    protected function setOrderMockGetId($id)
    {
        $this->orderMock->method('getId')
            ->willReturn($id);
    }

    protected function setOrderMockGetPayment()
    {
        $this->orderMock->method('getPayment')
            ->willReturn($this->paymentMock);
    }

    /**
     * @param int $id
     */
    protected function setTransactionMockGetId($id)
    {
        $this->transactionMock->method('getId')
            ->willReturn($id);
    }

    protected function getHelperMockGetTransactionByTransactionId()
    {
        $this->helperMock->method('getTransactionByTransactionId')
            ->willReturn($this->transactionMock);
    }

    protected function setInvoiceMockRegister()
    {
        $this->invoiceMock->method('register')
            ->willReturnSelf();
    }

    /**
     * @param $canInvoice
     */
    protected function setOrderMockCanInvoice($canInvoice)
    {
        $this->orderMock->method('canInvoice')
            ->willReturn($canInvoice);
    }

    protected function setOrderMockPrepareInvoice()
    {
        $this->orderMock->method('prepareInvoice')
            ->willReturn($this->invoiceMock);
    }
}
