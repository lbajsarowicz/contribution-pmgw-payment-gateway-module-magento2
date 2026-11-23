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

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use BigFish\PaymentGateway;
use BigFish\PaymentGateway\Config;
use BigFish\PaymentGateway\Transport\Response\Response as PaymentGatewayResponse;
use Bigfishpaymentgateway\Pmgw\Gateway\Response\ResponseProcessor;
use Bigfishpaymentgateway\Pmgw\Gateway\Helper\Helper;
use Bigfishpaymentgateway\Pmgw\Controller\Payment\Response as ResponseController;
use Bigfishpaymentgateway\Pmgw\Model\Response\ResultInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\App\Response\RedirectInterface;

class ResponseTest extends PaymentTestCase
{
    /**
     * @var ResponseProcessor&MockObject
     */
    private $responseProcessorMock;

    /**
     * @var ConfigInterface&MockObject
     */
    private $configMock;

    /**
     * @var Helper&MockObject
     */
    private $helperMock;

    /**
     * @var ResultInterface&MockObject
     */
    private $resultMock;

    /**
     * @var RedirectInterface&MockObject
     */
    private $redirectMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responseProcessorMock = $this->getMockBuilder(ResponseProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();

        $this->helperMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultMock = $this->getMockBuilder(ResultInterface::class)
            ->getMock();

        $this->redirectMock = $this->getMockBuilder(RedirectInterface::class)
            ->getMock();

        $this->contextMock->method('getRedirect')
            ->willReturn($this->redirectMock);
    }

    public function testMissingTransactionId()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Missing or invalid transaction id.');

        $this->requestMock->expects(static::once())
            ->method('getParams')
            ->willReturn([]);

        $controller = new ResponseController(
            $this->contextMock,
            $this->responseProcessorMock,
            $this->configMock,
            $this->helperMock
        );

        $controller->execute();
    }

    public function testPendingTransaction()
    {
        $response = $this->createPaymentGatewayResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_PENDING,
        ]);

        $resultCode = 'PENDING';
        $expectedRedirectUrl = 'checkout/onepage/success';
        $expectedRedirectParams = ['_secure' => true];

        $this->setupTransactionTest($response, $resultCode, $expectedRedirectUrl, $expectedRedirectParams);

        $controller = new ResponseController(
            $this->contextMock,
            $this->responseProcessorMock,
            $this->configMock,
            $this->helperMock
        );

        $controller->execute();
    }

    public function testSuccessfulTransaction()
    {
        $response = $this->createPaymentGatewayResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_SUCCESS,
        ]);

        $resultCode = 'SUCCESSFUL';
        $expectedRedirectUrl = 'checkout/onepage/success';
        $expectedRedirectParams = ['_secure' => true];

        $this->setupTransactionTest($response, $resultCode, $expectedRedirectUrl, $expectedRedirectParams);

        $controller = new ResponseController(
            $this->contextMock,
            $this->responseProcessorMock,
            $this->configMock,
            $this->helperMock
        );

        $controller->execute();
    }

    public function testTimeoutTransaction()
    {
        $response = $this->createPaymentGatewayResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_TIMEOUT,
        ]);

        $resultCode = 'TIMEOUT';
        $expectedRedirectUrl = 'checkout/onepage/failure';
        $expectedRedirectParams = ['_secure' => true];

        $this->setupTransactionTest($response, $resultCode, $expectedRedirectUrl, $expectedRedirectParams);

        $controller = new ResponseController(
            $this->contextMock,
            $this->responseProcessorMock,
            $this->configMock,
            $this->helperMock
        );

        $controller->execute();
    }

    public function testErrorTransaction()
    {
        $response = $this->createPaymentGatewayResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_ERROR,
        ]);

        $resultCode = 'ERROR';
        $expectedRedirectUrl = 'checkout/onepage/failure';
        $expectedRedirectParams = ['_secure' => true];

        $this->setupTransactionTest($response, $resultCode, $expectedRedirectUrl, $expectedRedirectParams);

        $controller = new ResponseController(
            $this->contextMock,
            $this->responseProcessorMock,
            $this->configMock,
            $this->helperMock
        );

        $controller->execute();
    }

    public function testUserCancelTransaction()
    {
        $response = $this->createPaymentGatewayResponse([
            'ResultCode' => PaymentGateway::RESULT_CODE_USER_CANCEL,
        ]);

        $resultCode = 'CANCELED';
        $expectedRedirectUrl = 'checkout/onepage/failure';
        $expectedRedirectParams = ['_secure' => true];

        $this->setupTransactionTest($response, $resultCode, $expectedRedirectUrl, $expectedRedirectParams);

        $controller = new ResponseController(
            $this->contextMock,
            $this->responseProcessorMock,
            $this->configMock,
            $this->helperMock
        );

        $controller->execute();
    }

    public function testInvalidResultCode()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Missing or invalid result code.');

        $response = $this->createPaymentGatewayResponse([
            'ResultCode' => 'INVALID',
        ]);

        $resultCode = 'INVALID';

        $this->setRequestMockGetParams();

        $this->setConfigMockGetValue();

        $this->setHelperMockSetPaymentGatewayConfig();

        $this->setHelperMockGetPaymentGatewayResult($response);

        $this->setResponseProcessorMockSetResponse($response);

        $this->setResponseProcessorMockProcessResponse();

        $this->setResultMockGetCode($resultCode);

        $controller = new ResponseController(
            $this->contextMock,
            $this->responseProcessorMock,
            $this->configMock,
            $this->helperMock
        );

        $controller->execute();
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
     * @param array $data
     * @return PaymentGatewayResponse
     */
    private function createPaymentGatewayResponse(array $data)
    {
        return PaymentGatewayResponse::createFromJson(json_encode($data));
    }

    private function setRequestMockGetParams()
    {
        $this->requestMock->expects(static::once())
            ->method('getParams')
            ->willReturn([
                'TransactionId' => 'test_transaction_id',
            ]);
    }

    private function setConfigMockGetValue()
    {
        $this->configMock->expects(static::exactly(3))
            ->method('getValue')
            ->willReturnMap([
                ['storename', null, 'test_storename'],
                ['apikey', null, 'test_apikey'],
                ['testmode', null, 1],
            ]);
    }

    private function setHelperMockSetPaymentGatewayConfig()
    {
        $this->helperMock->expects(static::once())
            ->method('setPaymentGatewayConfig')
            ->with($this->createPaymentGatewayConfig([
                'storeName' => 'test_storename',
                'apiKey' => 'test_apikey',
            ]));
    }

    /**
     * @param $response
     */
    private function setHelperMockGetPaymentGatewayResult($response)
    {
        $this->helperMock->expects(static::once())
            ->method('getPaymentGatewayResult')
            ->with('test_transaction_id')
            ->willReturn($response);
    }

    /**
     * @param $response
     */
    private function setResponseProcessorMockSetResponse($response)
    {
        $this->responseProcessorMock->expects(static::once())
            ->method('setResponse')
            ->with($response);
    }

    private function setResponseProcessorMockProcessResponse()
    {
        $this->responseProcessorMock->expects(static::once())
            ->method('processResponse')
            ->willReturn($this->resultMock);
    }

    /**
     * @param $resultCode
     */
    private function setResultMockGetCode($resultCode)
    {
        $this->resultMock->expects(static::once())
            ->method('getCode')
            ->willReturn($resultCode);
    }

    /**
     * @param $expectedRedirectUrl
     * @param $expectedRedirectParams
     */
    private function setRedirectMockRedirect($expectedRedirectUrl, $expectedRedirectParams)
    {
        $this->redirectMock->expects(static::once())
            ->method('redirect')
            ->with($this->responseMock, $expectedRedirectUrl, $expectedRedirectParams);
    }

    /**
     * @param PaymentGatewayResponse $response
     * @param string $resultCode
     * @param string $expectedRedirectUrl
     * @param array $expectedRedirectParams
     */
    private function setupTransactionTest(
        PaymentGatewayResponse $response,
        $resultCode,
        $expectedRedirectUrl,
        array $expectedRedirectParams
    ) {
        $this->setRequestMockGetParams();

        $this->setConfigMockGetValue();

        $this->setHelperMockSetPaymentGatewayConfig();

        $this->setHelperMockGetPaymentGatewayResult($response);

        $this->setResponseProcessorMockSetResponse($response);

        $this->setResponseProcessorMockProcessResponse();

        $this->setResultMockGetCode($resultCode);

        $this->setRedirectMockRedirect($expectedRedirectUrl, $expectedRedirectParams);
    }
}
