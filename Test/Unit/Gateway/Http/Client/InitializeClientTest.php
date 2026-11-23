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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Gateway\Http\Client;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use BigFish\PaymentGateway;
use BigFish\PaymentGateway\Config;
use Bigfishpaymentgateway\Pmgw\Gateway\Helper\Helper;
use Bigfishpaymentgateway\Pmgw\Gateway\Http\Client\InitializeClient;
use Bigfishpaymentgateway\Pmgw\Model\Transaction;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Customer\Model\Session;
use Magento\Payment\Gateway\Http\TransferInterface;

class InitializeClientTest extends TestCase
{
    /**
     * @var ConfigInterface&MockObject
     */
    private $configMock;

    /**
     * @var Helper&MockObject
     */
    private $helperMock;

    /**
     * @var Session&MockObject
     */
    private $customerSessionMock;

    /**
     * @var TransferInterface&MockObject
     */
    private $transferObjectMock;

    /**
     * @var Transaction&MockObject
     */
    private $transactionMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configMock = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();

        $this->helperMock = $this->getMockBuilder(Helper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transferObjectMock = $this->getMockBuilder(TransferInterface::class)
            ->getMock();

        $this->transactionMock = $this->getMockBuilder(Transaction::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSuccessfulInitialization()
    {
        $this->transferObjectMock->expects(static::once())
            ->method('getBody')
            ->willReturn([
                Helper::RESPONSE_FIELD_RESULT_CODE => PaymentGateway::RESULT_CODE_SUCCESS,
                Helper::RESPONSE_FIELD_TRANSACTION_ID => 'test_transaction_id',
            ]);

        $this->configMock->expects(static::exactly(3))
            ->method('getValue')
            ->willReturnMap([
                ['storename', null, 'test_store'],
                ['apikey', null, 'test_api_key'],
                ['testmode', null, '1'],
            ]);

        $config = new Config();
        $config->storeName = 'test_store';
        $config->apiKey = 'test_api_key';
        $config->testMode = true;

        $this->helperMock->expects(static::once())
            ->method('setPaymentGatewayConfig')
            ->with($config);

        $this->helperMock->expects(static::once())
            ->method('getPaymentGatewayStartUrl')
            ->with('test_transaction_id')
            ->willReturn('http://exaple.com/test_transaction_id');

        $this->helperMock->expects(static::once())
            ->method('getTransactionByTransactionId')
            ->with('test_transaction_id')
            ->willReturn($this->transactionMock);

        $this->helperMock->expects(static::once())
            ->method('updateTransactionStatus')
            ->with($this->transactionMock, 110);

        $this->helperMock->expects(static::once())
            ->method('addTransactionLog')
            ->with($this->transactionMock, ['startUrl' => 'http://exaple.com/test_transaction_id']);

        $client = new InitializeClient(
            $this->configMock,
            $this->helperMock,
            $this->customerSessionMock
        );

        $this->assertEquals([
            'ResultCode' => 'SUCCESSFUL',
            'TransactionId' => 'test_transaction_id',
        ], $client->placeRequest($this->transferObjectMock));
    }

    public function testUnsuccessfulInitialization()
    {
        $this->transferObjectMock->expects(static::once())
            ->method('getBody')
            ->willReturn([
                Helper::RESPONSE_FIELD_RESULT_CODE => PaymentGateway::RESULT_CODE_ERROR,
            ]);

        $this->configMock->expects(static::never())
            ->method('getValue');

        $this->helperMock->expects(static::never())
            ->method('setPaymentGatewayConfig');

        $this->helperMock->expects(static::never())
            ->method('getPaymentGatewayStartUrl');

        $this->helperMock->expects(static::never())
            ->method('getTransactionByTransactionId');

        $this->helperMock->expects(static::never())
            ->method('updateTransactionStatus');

        $this->helperMock->expects(static::never())
            ->method('addTransactionLog');

        $client = new InitializeClient(
            $this->configMock,
            $this->helperMock,
            $this->customerSessionMock
        );

        $this->assertEquals([
            'ResultCode' => 'ERROR',
        ], $client->placeRequest($this->transferObjectMock));
    }
}
