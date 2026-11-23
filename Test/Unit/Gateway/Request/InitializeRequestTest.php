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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Gateway\Request;

use PHPUnit\Framework\MockObject\MockObject;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use BigFish\PaymentGateway\Data\Info;
use BigFish\PaymentGateway\Data\Info\Customer\InfoCustomerGeneral;
use BigFish\PaymentGateway\Data\Info\Customer\InfoCustomerStoreSpecific;
use Magento\Customer\Api\Data\CustomerInterface;
use Bigfishpaymentgateway\Pmgw\Model\Source\Transaction as TransactionResource;
use BigFish\PaymentGateway;
use BigFish\PaymentGateway\Request\Init as InitRequest;
use BigFish\PaymentGateway\Transport\Response\Response;
use Bigfishpaymentgateway\Pmgw\Model\ConfigProvider;
use Bigfishpaymentgateway\Pmgw\Gateway\Request\InitializeRequest;
use Bigfishpaymentgateway\Pmgw\Gateway\Helper\Helper;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\StoreInterfaceFixture as StoreInterface;
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\Model\Transaction;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Psr\Log\LoggerInterface;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Quote\Api\Data\PaymentInterface;
use \Magento\Webapi\Controller\Rest\InputParamsResolver;

class InitializeRequestTest extends TestCase
{
    /**
     * @var ConfigProvider&MockObject
     */
    private $configProviderMock;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private $storeManagerMock;

    /**
     * @var ProductMetadataInterface&MockObject
     */
    private $productMetaDataMock;

    /**
     * @var ModuleListInterface&MockObject
     */
    private $moduleListMock;

    /**
     * @var Helper&MockObject
     */
    private $helperMock;

    /**
     * @var LoggerInterface&MockObject
     */
    private $loggerMock;

    /**
     * @var DateTime&MockObject
     */
    private $dateTimeMock;

    /**
     * @var PaymentDataObjectInterface&MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var InfoInterface&MockObject
     */
    private $paymentMock;

    /**
     * @var MethodInterface&MockObject
     */
    private $paymentMethodMock;

    /**
     * @var StoreInterface&MockObject
     */
    private $storeMock;

    /**
     * @var OrderAdapterInterface&MockObject
     */
    private $orderMock;

    /**
     * @var Transaction&MockObject
     */
    private $transactionMock;

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private $scopeConfigMock;

    /**
     * @var PaymentInterface&MockObject
     */
    private $paymentInterfacedMock;

    /**
     * @var InputParamsResolver&MockObject
     */
    private $inputParamsResolverMock;

    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var int
     */
    private $orderId;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var string
     */
    private $date;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var TransactionResource&MockObject
     */
    private $transactionResource;

    protected function setUp(): void
    {
        $this->configProviderMock = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();

        $this->productMetaDataMock = $this->getMockBuilder(ProductMetadataInterface::class)
            ->getMock();

        $this->moduleListMock = $this->getMockBuilder(ModuleListInterface::class)
            ->getMock();

        $this->helperMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $this->dateTimeMock = $this->getMockBuilder(DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDataObjectMock = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->getMock();

        $this->paymentMock = $this->getMockBuilder(InfoInterface::class)
            ->getMock();

        $this->paymentMethodMock = $this->getMockBuilder(MethodInterface::class)
            ->getMock();

        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->getMock();

        $this->orderMock = $this->getMockBuilder(OrderAdapterInterface::class)
            ->getMock();

        $this->transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentInterfacedMock = $this->getMockBuilder(PaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inputParamsResolverMock = $this->getMockBuilder(InputParamsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customer = $this->createStub(CustomerInterface::class);
        $customer->method('getFirstname')->willReturn('Test');
        $customer->method('getLastname')->willReturn('Customer');
        $customer->method('getEmail')->willReturn('customer@example.com');
        $customer->method('getCreatedAt')->willReturn('2024-01-01 00:00:00');
        $customer->method('getUpdatedAt')->willReturn('2024-01-02 00:00:00');
        $this->customerRepository = $this->createStub(CustomerRepositoryInterface::class);
        $this->customerRepository->method('getById')->willReturn($customer);
        $this->transactionResource = $this->createMock(TransactionResource::class);
        $this->orderMock->method('getRemoteIp')->willReturn('127.0.0.1');
        $this->orderMock->method('getShippingAddress')->willReturn(null);
        $this->orderMock->method('getBillingAddress')->willReturn(null);
        $this->scopeConfigMock->method('getValue')->willReturn('ts_TS');

        $this->transactionId = hash('sha256', (string)rand(1, 100));
        $this->amount = rand(2000, 4000);
        $this->orderId = rand(1, 1000);
        $this->userId = rand(1, 1000);
        $this->date = date('Y-m-d H:i:s');
    }

    public function testInvalidPaymentDataObject()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Payment data object should be provided');

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $initializeRequest->build([]);
    }

    public function testEmptyProviderConfig()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Payment parameter array should be provided');

        $this->setPaymentDataObjectMockGetPayment();
        $this->setPaymentMockGetMethodInstance();

        $code = 'bigfishpaymentgateway_pmgw_test';
        $config = [];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]);
    }

    public function testFailedBuildRequest()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('ERROR: Error message');

        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('test_provider_code');

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_ERROR,
            'ResultMessage' => 'Error message',
        ]);

        $this->setProductDataMockGetVersion();
        $this->setModuleListMockGetOne();
        $this->setPaymentDataObjectMockGetPayment();
        $this->setPaymentDataObjectMockGetOrder();
        $this->setPaymentMockGetMethodInstance();
        $this->setStoreManagerMockGetStore();
        $this->setStoreMockGetBaseUrl();
        $this->setOrderMockGetOrderIncrementId();
        $this->setOrderMockGetGrandTotalAmount();
        $this->setOrderMockGetCurrencyCode();
        $this->setOrderMockGetCustomerId();
        $this->setHelperMockInitializePaymentGatewayTransaction($expectedInitRequest, $response);
        $this->setLoggerMockCritical('ERROR: Error message');

        $code = 'bigfishpaymentgateway_pmgw_test';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_test',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'test_provider_code',
            'response_url' => '/test_response_url',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]);

    }

    public function testSuccessfulBuildRequest()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('test_provider_code');

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_test';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_test',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'test_provider_code',
            'response_url' => '/test_response_url',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    public function testSuccessfulBuildRequestWithKhbSzepProvider()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('KHBSZEP');
        $expectedInitRequest->setResponseUrl('http://example.com/test_response_url');
        $expectedInitRequest->setExtra(['KhbCardPocketId' => 'test_card_pocket_id']);


        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_khbszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_khbszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'KHBSZEP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => 'test_card_pocket_id',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    public function testSuccessfulBuildRequestWithMkbSzepProvider()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('MKBSZEP');
        $expectedInitRequest->setMkbSzepCafeteriaId('1111');
        $expectedInitRequest->setGatewayPaymentPage(true);

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_mkbszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_mkbszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'MKBSZEP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => '1111',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    public function testMkbSzepDeprecatedPocketFallsBackToMainAccount()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('MKBSZEP');
        // The deprecated Home renovation pocket ('4444') must be redirected to Main account ('1111').
        $expectedInitRequest->setMkbSzepCafeteriaId('1111');
        $expectedInitRequest->setGatewayPaymentPage(true);

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_mkbszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_mkbszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'MKBSZEP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => '4444',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));
    }

    public function testMkbSzepUnknownPocketIsNotSilentlyRemapped()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('MKBSZEP');
        $expectedInitRequest->setMkbSzepCafeteriaId('9999');
        $expectedInitRequest->setGatewayPaymentPage(true);

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_mkbszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_mkbszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'MKBSZEP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => '9999',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));
    }

    public function testSuccessfulBuildRequestWithOtpSzepProvider()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('OTP');
        $expectedInitRequest->setOtpCardPocketId(10);

        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_otpszep';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_otpszep',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'OTP',
            'response_url' => '/test_response_url',
            'card_pocket_id' => 10,
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    public function testSuccessfulBuildRequestWithSaferpayProvider()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('Saferpay');
        $expectedInitRequest->setExtra([
            'SaferpayPaymentMethods' => ['foo', 'bar'],
            'SaferpayWallets' => ['bar', 'foo'],
        ]);


        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_saferpay';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_saferpay',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'Saferpay',
            'response_url' => '/test_response_url',
            'payment_methods' => 'foo,bar',
            'wallets' => 'bar,foo',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    public function testSuccessfulBuildRequestWithWirecardProvider()
    {
        $expectedInitRequest = $this->getExpectedInitRequest();

        $expectedInitRequest->setProviderName('QPAY');
        $expectedInitRequest->setExtra([
            'QpayPaymentType' => 'foo',
        ]);


        $response = $this->createResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
            'TransactionId' => $this->transactionId,
        ]);

        $this->setSuccessfulRequestCommon($expectedInitRequest, $response);

        $code = 'bigfishpaymentgateway_pmgw_wirecard';
        $config = [
            'name' => 'bigfishpaymentgateway_pmgw_wirecard',
            'storename' => 'test_storename',
            'apikey' => 'test_apikey',
            'provider_code' => 'QPAY',
            'response_url' => '/test_response_url',
            'payment_type' => 'foo',
            'testmode' => 1,
            'active' => 1,
            'debug' => 1,
        ];

        $this->setPaymentMethodMockGetCode($code);
        $this->setConfigProviderMockGetProviderConfig($code, $config);

        $initializeRequest = new InitializeRequest(
            $this->configProviderMock,
            $this->storeManagerMock,
            $this->productMetaDataMock,
            $this->moduleListMock,
            $this->helperMock,
            $this->loggerMock,
            $this->dateTimeMock,
            $this->scopeConfigMock,
            $this->paymentInterfacedMock,
            $this->customerRepository,
            $this->inputParamsResolverMock,
            $this->transactionResource
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => $this->transactionId,
        ], $initializeRequest->build([
            'payment' => $this->paymentDataObjectMock,
        ]));

    }

    /**
     * @param InitRequest $expectedInitRequest
     * @param Response $response
     */
    private function setSuccessfulRequestCommon(InitRequest $expectedInitRequest, Response $response)
    {
        $this->setProductDataMockGetVersion();

        $this->setModuleListMockGetOne();

        $this->setDateTimeMockDate();

        $this->setPaymentDataObjectMockGetPayment();

        $this->setPaymentDataObjectMockGetOrder();

        $this->setPaymentMockGetMethodInstance();

        $this->setStoreManagerMockGetStore();

        $this->setStoreMockGetBaseUrl();


        $this->setOrderMockGetOrderIncrementId();

        $this->setOrderMockGetGrandTotalAmount();

        $this->setOrderMockGetCurrencyCode();

        $this->setOrderMockGetCustomerId();

        $this->setHelperMockInitializePaymentGatewayTransaction($expectedInitRequest, $response);

        $this->setTransactionMockSetOrderId();

        $this->setTransactionMockSetTransactionId();

        $this->setTransactionMockSetCreatedTime();

        $this->setTransactionMockSetStatus();

        $this->setTransactionMockSave();

        $this->setHelperMockCreateTransaction();

        $this->setHelperMockAddTransactionLog($response);

    }

    /**
     * @return InitRequest
     */
    private function getExpectedInitRequest()
    {
        $expectedRequest = new InitRequest();
        $expectedRequest->setResponseUrl('http://example.com/test_response_url');
        $expectedRequest->setLanguage('TS');
        $expectedRequest->setAmount($this->amount);
        $expectedRequest->setCurrency('TES');
        $expectedRequest->setOrderId($this->orderId);
        $expectedRequest->setUserId($this->userId);
        $expectedRequest->setModuleName('Magento (test_magento_version)');
        $expectedRequest->setModuleVersion('test_setup_version');
        $expectedRequest->setAutoCommit(true);

        $info = new Info();
        $info->setObject((new InfoCustomerGeneral())
            ->setLastName('Customer')
            ->setFirstName('Test')
            ->setEmail('customer@example.com')
            ->setIp('127.0.0.1'));
        $info->setObject((new InfoCustomerStoreSpecific())
            ->setUpdateDate('2024-01-02')
            ->setCreationDate('2024-01-01'));
        $expectedRequest->setInfo($info);

        return $expectedRequest;
    }

    /**
     * @param $string
     * @return string
     */
    private function urlSafeEncode($string)
    {
        $data = str_replace(array('+', '/', '='), array('-', '_', '.'), base64_encode($string));
        return $data;
    }

    private function setProductDataMockGetVersion()
    {
        $this->productMetaDataMock->expects(static::once())
            ->method('getVersion')
            ->willReturn('test_magento_version');
    }

    private function setModuleListMockGetOne()
    {
        $this->moduleListMock->expects(static::once())
            ->method('getOne')
            ->with('Bigfishpaymentgateway_Pmgw')
            ->willReturn([
                'setup_version' => 'test_setup_version',
            ]);
    }

    private function setDateTimeMockDate()
    {
        $this->dateTimeMock->expects(static::once())
            ->method('date')
            ->willReturn($this->date);
    }

    private function setPaymentDataObjectMockGetPayment()
    {
        $this->paymentDataObjectMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);
    }

    private function setPaymentDataObjectMockGetOrder()
    {
        $this->paymentDataObjectMock->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);
    }

    private function setPaymentMockGetMethodInstance()
    {
        $this->paymentMock->expects(static::once())
            ->method('getMethodInstance')
            ->willReturn($this->paymentMethodMock);
    }

    private function setStoreManagerMockGetStore()
    {
        $this->storeManagerMock->method('getStore')
            ->willReturn($this->storeMock);
    }

    private function setStoreMockGetBaseUrl()
    {
        $this->storeMock->expects(static::once())
            ->method('getBaseUrl')
            ->willReturn('http://example.com');
    }

    private function setOrderMockGetOrderIncrementId()
    {
        $this->orderMock->method('getOrderIncrementId')
            ->willReturn($this->orderId);
    }

    private function setOrderMockGetGrandTotalAmount()
    {
        $this->orderMock->expects(static::once())
            ->method('getGrandTotalAmount')
            ->willReturn($this->amount);
    }

    private function setOrderMockGetCurrencyCode()
    {
        $this->orderMock->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn('TES');
    }

    private function setOrderMockGetCustomerId()
    {
        $this->orderMock->method('getCustomerId')
            ->willReturn($this->userId);
    }

    private function setTransactionMockSetOrderId()
    {
        $this->transactionMock->expects(static::once())
            ->method('setOrderId')
            ->with($this->orderId)
            ->willReturnSelf();
    }

    private function setTransactionMockSetTransactionId()
    {
        $this->transactionMock->expects(static::once())
            ->method('setTransactionId')
            ->with($this->transactionId)
            ->willReturnSelf();
    }

    private function setTransactionMockSetCreatedTime()
    {
        $this->transactionMock->expects(static::once())
            ->method('setCreatedTime')
            ->with($this->date)
            ->willReturnSelf();
    }

    private function setTransactionMockSetStatus()
    {
        $this->transactionMock->expects(static::once())
            ->method('setStatus')
            ->with(100)
            ->willReturnSelf();
    }

    private function setTransactionMockSave()
    {
        $this->transactionResource->expects(static::once())
            ->method('save')
            ->with($this->transactionMock);
    }

    private function setHelperMockCreateTransaction()
    {
        $this->helperMock->expects(static::once())
            ->method('createTransaction')
            ->willReturn($this->transactionMock);
    }

    /**
     * @param InitRequest $expectedInitRequest
     * @param Response $response
     */
    private function setHelperMockInitializePaymentGatewayTransaction(InitRequest $expectedInitRequest, Response $response)
    {
        $this->helperMock->expects(static::once())
            ->method('initializePaymentGatewayTransaction')
            ->with($expectedInitRequest)
            ->willReturn($response);
    }

    /**
     * @param Response $response
     */
    private function setHelperMockAddTransactionLog(Response $response)
    {
        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, $response->getData());
    }

    /**
     * @param string $code
     */
    private function setPaymentMethodMockGetCode($code)
    {
        $this->paymentMethodMock->expects(static::once())
            ->method('getCode')
            ->willReturn($code);
    }

    /**
     * @param string $code
     * @param array $config
     */
    private function setConfigProviderMockGetProviderConfig($code, array $config)
    {
        $this->configProviderMock->expects(static::once())
            ->method('getProviderConfig')
            ->with($code)
            ->willReturn($config);
    }

    /**
     * @param array $data
     * @return Response
     */
    private function createResponse(array $data)
    {
        $response = Response::createFromJson(json_encode($data));
        return $response;
    }

    /**
     * @param $errorMessage
     */
    private function setLoggerMockCritical($errorMessage)
    {
        $this->loggerMock->expects(static::once())
            ->method('critical')
            ->with($errorMessage);
    }
}
