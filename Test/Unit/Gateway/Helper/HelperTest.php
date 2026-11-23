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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Gateway\Helper;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use BigFish\PaymentGateway;
use BigFish\PaymentGateway\Request\Result as ResultRequest;
use ReflectionProperty;
use Bigfishpaymentgateway\Pmgw\Model\Source\Transaction as TransactionResource;
use Bigfishpaymentgateway\Pmgw\Model\Source\Log as LogResource;
use Bigfishpaymentgateway\Pmgw\Model\Source\Log\CollectionFactory as LogCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Magento\Sales\Model\ResourceModel\Order\Payment as PaymentResource;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as PaymentTransactionResource;
use BigFish\PaymentGateway\Config;
use BigFish\PaymentGateway\Request\Init as InitRequest;
use BigFish\PaymentGateway\Transport\Response\Response;
use Bigfishpaymentgateway\Pmgw\Gateway\Helper\Helper;
use Bigfishpaymentgateway\Pmgw\Model\TransactionFactory;
use Bigfishpaymentgateway\Pmgw\Model\LogFactory;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\Model\Transaction;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\Model\Log;
use Magento\Payment\Model\Method\Logger;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface as TransactionBuilderInterface;

class HelperTest extends TestCase
{

    /**
     * @var TransactionFactory&MockObject
     */
    private $transactionFactoryMock;

    /**
     * @var LogFactory&MockObject
     */
    private $logFactoryMock;

    /**
     * @var Logger&MockObject
     */
    private $paymentLoggerMock;

    /**
     * @var JsonHelper&MockObject
     */
    private $jsonHelperMock;

    /**
     * @var DateTime&MockObject
     */
    private $dateTimeMock;

    /**
     * @var Transaction&MockObject
     */
    private $transactionMock;

    /**
     * @var Log&MockObject
     */
    private $logMock;

    /**
     * @var TransactionBuilderInterface&MockObject
     */
    private $transactionBuilderInterfaceMock;

    /**
     * @var TransactionResource&MockObject
     */
    private $transactionResource;

    /**
     * @var LogResource&MockObject
     */
    private $logResource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionResource = $this->createMock(TransactionResource::class);
        $this->logResource = $this->createMock(LogResource::class);

        $this->transactionFactoryMock = $this->getMockBuilder(TransactionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logFactoryMock = $this->getMockBuilder(LogFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentLoggerMock = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonHelperMock = $this->getMockBuilder(JsonHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logMock = $this->getMockBuilder(Log::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transactionBuilderInterfaceMock = $this->getMockBuilder(TransactionBuilderInterface::class)
            ->getMock();
    }

    public function testCreateTransaction()
    {
        $this->transactionFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($this->transactionMock);

        $this->assertEquals(
            $this->transactionMock,
            $this->createHelper()->createTransaction()
        );
    }

    public function testGetTransactionByTransactionId()
    {
        $this->transactionFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($this->transactionMock);

        $this->transactionResource->expects(static::once())
            ->method('load')
            ->with($this->transactionMock, 1, 'transaction_id')
            ->willReturnSelf();

        $this->assertEquals(
            $this->transactionMock,
            $this->createHelper()->getTransactionByTransactionId(1)
        );
    }

    public function testUpdateTransactionStatus()
    {
        $this->transactionMock->expects(static::once())
            ->method('setStatus')
            ->with(100)
            ->willReturnSelf();

        $this->transactionResource->expects(static::once())
            ->method('save')
            ->with($this->transactionMock);

        $this->createHelper()->updateTransactionStatus($this->transactionMock, 100);
    }

    public function testAddTransactionLog()
    {
        $transactionId = 1;
        $status = 100;
        $date = date('Y-m-d H:i:s');
        $debug = ['foo', 'bar'];

        $this->transactionMock->expects(static::once())
            ->method('getId')
            ->willReturn($transactionId);

        $this->transactionMock->expects(static::once())
            ->method('getStatus')
            ->willReturn($status);

        $this->dateTimeMock->expects(static::once())
            ->method('date')
            ->willReturn($date);

        $this->jsonHelperMock->expects(static::once())
            ->method('jsonEncode')
            ->with($debug)
            ->willReturn(json_encode($debug));

        $this->logFactoryMock->expects(static::once())
            ->method('create')
            ->willReturn($this->logMock);

        $this->logMock->expects(static::once())
            ->method('setPaymentgatewayId')
            ->with($transactionId)
            ->willReturnSelf();

        $this->logMock->expects(static::once())
            ->method('setStatus')
            ->with($status)
            ->willReturnSelf();

        $this->logMock->expects(static::once())
            ->method('setCreatedTime')
            ->with($date)
            ->willReturnSelf();

        $this->logMock->expects(static::once())
            ->method('setDebug')
            ->with(json_encode($debug))
            ->willReturnSelf();

        $this->logResource->expects(static::once())
            ->method('save')
            ->with($this->logMock);

        $this->createHelper()->addTransactionLog($this->transactionMock, $debug);
    }

    public function testSetPaymentGatewayConfig()
    {
        $config = $this->createPaymentGatewayConfig([
            'storeName' => 'test_storename',
            'apiKey' => 'test_apikey',
            'testMode' => true,
            'moduleName' => 'test_modulename',
            'moduleVersion' => 'test_moduleversion',
        ]);

        $this->paymentLoggerMock->expects(static::once())
            ->method('debug')
            ->with(
                [
                    'action' => 'setConfig',
                    'data' => [
                        'storeName' => 'test_storename',
                        'apiKey' => 'test_apikey',
                        'testMode' => true,
                    ]
                ]
            );

        $this->createHelper()->setPaymentGatewayConfig($config);
    }

    public function testGetPaymentGatewayStartUrl()
    {
        $helper = $this->createHelper();
        $helper->setPaymentGatewayConfig(
            $this->createPaymentGatewayConfig([
                'storeName' => 'test_storename',
                'apiKey' => 'test_apikey',
                'testMode' => true,
            ])
        );

        $this->assertEquals(
            'https://system-test.paymentgateway.hu/Start?TransactionId=test_transaction_id',
            $helper->getPaymentGatewayStartUrl('test_transaction_id')
        );
    }

    public function testInitializePaymentGatewayTransaction()
    {
        $helper = $this->createHelper();

        $helper->setPaymentGatewayConfig($this->createPaymentGatewayConfig([
            'storeName' => 'test_storename',
            'apiKey' => 'test_apikey',
            'testMode' => true,
            'moduleName' => 'test_modulename',
            'moduleVersion' => 'test_moduleversion',
        ]));

        $request = $this->createPaymentGatewayInitRequest()
            ->setProviderName('test_provider')
            ->setAmount(1);

        $expectedResponse = $this->createPaymentGatewayResponse([
            'ResultCode' => 'UnknownStore',
            'ResultMessage' => 'Ismeretlen kereskedő (test_storename)',
        ]);

        $this->setGatewayResponse($helper, $request, $expectedResponse);

        $this->paymentLoggerMock->expects(static::once())
            ->method('debug')
            ->with([
                'action' => 'init',
                'request' => $request->getData(),
                'response' => $expectedResponse->getData(),
            ]);

        $this->assertEquals(
            $expectedResponse,
            $helper->initializePaymentGatewayTransaction($request)
        );
    }

    public function testGetPaymentGatewayResult()
    {
        $helper = $this->createHelper();

        $helper->setPaymentGatewayConfig($this->createPaymentGatewayConfig([
            'storeName' => 'test_storename',
            'apiKey' => 'test_apikey',
            'testMode' => true,
            'moduleName' => 'test_modulename',
            'moduleVersion' => 'test_moduleversion',
        ]));

        $expectedResponse = $this->createPaymentGatewayResponse([
            'ResultCode' => 'UnknownTransaction',
            'ResultMessage' => 'Ismeretlen tranzakció (test_transaction_id)',
        ]);

        $request = (new ResultRequest())->setTransactionId('test_transaction_id');
        $this->setGatewayResponse($helper, $request, $expectedResponse);

        $this->paymentLoggerMock->expects(static::once())
            ->method('debug')
            ->with([
                'action' => 'result',
                'transactionId' => 'test_transaction_id',
                'response' => $expectedResponse->getData(),
            ]);

        $this->assertEquals(
            $expectedResponse,
            $helper->getPaymentGatewayResult('test_transaction_id')
        );
    }

    /**
     * @param Helper $helper
     * @param InitRequest|ResultRequest $request
     * @param Response $response
     */
    private function setGatewayResponse(Helper $helper, $request, Response $response)
    {
        $gateway = $this->createMock(PaymentGateway::class);
        $gateway->expects(static::once())->method('send')->with($request)->willReturn($response);
        $property = new ReflectionProperty(Helper::class, 'paymentGateway');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue($helper, $gateway);
    }

    /**
     * @return Helper
     */
    private function createHelper()
    {
        $helper = new Helper(
            $this->transactionFactoryMock,
            $this->transactionBuilderInterfaceMock,
            $this->logFactoryMock,
            $this->paymentLoggerMock,
            $this->jsonHelperMock,
            $this->dateTimeMock,
            $this->transactionResource,
            $this->logResource,
            $this->createStub(LogCollectionFactory::class),
            $this->createStub(OrderResource::class),
            $this->createStub(PaymentResource::class),
            $this->createStub(PaymentTransactionResource::class)
        );
        return $helper;
    }

    /**
     * @param array $data
     * @return Config
     */
    private function createPaymentGatewayConfig(array $data)
    {
        $config = new Config();
        foreach ($data as $name => $value) {
            $config->$name = $value;
        }
        return $config;
    }

    /**
     * @return InitRequest
     */
    private function createPaymentGatewayInitRequest()
    {
        return new InitRequest();
    }

    /**
     * @param array $data
     * @return Response
     */
    private function createPaymentGatewayResponse(array $data)
    {
        return Response::createFromJson(json_encode($data));
    }
}
