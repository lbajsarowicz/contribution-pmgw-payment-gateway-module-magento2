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
namespace Bigfishpaymentgateway\Pmgw\Test\Unit\Model;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Bigfishpaymentgateway\Pmgw\Model\ConfigProvider;

class ConfigProviderTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private $scopeConfigMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    protected function setUp(): void
    {
        parent::setUp();

        $store = $this->createStub(StoreInterface::class);
        $this->storeManagerMock = $this->createStub(StoreManagerInterface::class);
        $this->storeManagerMock->method('getStore')->willReturn($store);

        $this->scopeConfigMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testEmptyScopeConfig()
    {
        $this->assertConfig([], []);
    }

    public function testNoPmgwProviderInScopeConfig()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'other_provider' => [
                'foo' => 'bar',
            ],
        ], []);
    }

    public function testNoActivePmgwProviderInScopeConfig()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 0,
            ],
        ], []);
    }

    public function testOneActivePmgwProviderInScopeConfig()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 1,
            ],
        ], [
            [
                'name' => 'bigfishpaymentgateway_pmgw_test',
                'active' => 1,
            ],
        ]);
    }

    public function testMultipleActivePmgwProviderInScopeConfig()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_foo' => [
                'active' => 1,
            ],
            'bigfishpaymentgateway_pmgw_bar' => [
                'active' => 1,
            ],
        ], [
            [
                'name' => 'bigfishpaymentgateway_pmgw_foo',
                'active' => 1,
            ],
            [
                'name' => 'bigfishpaymentgateway_pmgw_bar',
                'active' => 1,
            ],
        ]);
    }

    public function testMultipleActiveAndInactivePmgwProviderInScopeConfig()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_foo' => [
                'active' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 0,
            ],
            'bigfishpaymentgateway_pmgw_bar' => [
                'active' => 1,
            ],
        ], [
            [
                'name' => 'bigfishpaymentgateway_pmgw_foo',
                'active' => 1,
            ],
            [
                'name' => 'bigfishpaymentgateway_pmgw_bar',
                'active' => 1,
            ],
        ]);
    }

    public function testUnifyPmgwProviderConfigValues()
    {
        $this->assertConfig([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_foo' => [
                'active' => 1,
                'foo' => 'bar',
            ],
            'bigfishpaymentgateway_pmgw_bar' => [
                'active' => 1,
                'bar' => 'foo',
            ],
        ], [
            [
                'name' => 'bigfishpaymentgateway_pmgw_foo',
                'active' => 1,
                'foo' => 'bar',
                'bar' => null,
            ],
            [
                'name' => 'bigfishpaymentgateway_pmgw_bar',
                'active' => 1,
                'foo' => null,
                'bar' => 'foo',
            ],
        ]);
    }

    public function testGetProviderConfigWithInvalidCode()
    {
        $this->setScopeConfigMockGetValue([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 1,
            ],
        ]);

        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $this->assertEquals([], $configProvider->getProviderConfig('bigfishpaymentgateway_pmgw_foo'));
    }

    public function testGetProviderConfigWithValidCode()
    {
        $this->setScopeConfigMockGetValue([
            'bigfishpaymentgateway_pmgw' => [
                'debug' => 1,
            ],
            'bigfishpaymentgateway_pmgw_test' => [
                'active' => 1,
            ],
        ]);

        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $this->assertEquals([
            'name' => 'bigfishpaymentgateway_pmgw_test',
            'active' => 1,
            'debug' => 1,
        ], $configProvider->getProviderConfig('bigfishpaymentgateway_pmgw_test'));
    }

    public function testGetKhbCardPocketId()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $result = $configProvider->getKhbCardPocketId();

        $this->assertEquals(3, count($result));

        $this->assertEquals([
            '', '1', '3'
        ], array_keys($result));
    }

    public function testGetMkbCardPocketId()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $result = $configProvider->getMkbCardPocketId();

        $this->assertEquals(3, count($result));

        $this->assertEquals([
            '', '1111', '3333'
        ], array_keys($result));
    }

    public function testGetOtpCardPocketId()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $result = $configProvider->getOtpCardPocketId();

        $this->assertEquals(3, count($result));

        $this->assertEquals([
            '', '09', '08'
        ], array_keys($result));
    }

    public function testGetSaferpayPaymentMethods()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $result = $configProvider->getSaferpayPaymentMethods();

        $this->assertEquals(20, count($result));
    }

    public function testGetSaferpayWallets()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $result = $configProvider->getSaferpayWallets();

        $this->assertEquals(1, count($result));
    }

    public function testGetWirecardPaymentTypes()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $result = $configProvider->getWirecardPaymentTypes();

        $this->assertEquals(25, count($result));
    }

    public function testGetCardRegistrationModes()
    {
        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $result = $configProvider->getCardRegistrationModes();

        $this->assertEquals(3, count($result));

        $this->assertEquals([
            '0', '1', '2'
        ], array_keys($result));
    }

    /**
     * @param array $scopeConfig
     * @param array $expected
     */
    private function assertConfig(array $scopeConfig, array $expected)
    {
        $this->setScopeConfigMockGetValue($scopeConfig);

        $configProvider = new ConfigProvider($this->scopeConfigMock, $this->storeManagerMock);

        $this->assertEquals([
            'payment' => [
                'bigfishpaymentgateway_pmgw' => [
                    'providers' => $expected,
                ],
            ],
        ], $configProvider->getConfig());
    }

    /**
     * @param array $scopeConfig
     */
    private function setScopeConfigMockGetValue(array $scopeConfig)
    {
        $this->scopeConfigMock->method('getValue')
            ->with('payment')
            ->willReturn($scopeConfig);
    }
}
