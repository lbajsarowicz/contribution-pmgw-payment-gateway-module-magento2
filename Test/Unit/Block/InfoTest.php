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
use Bigfishpaymentgateway\Pmgw\Test\Unit\Fixtures\Block\Info;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\Phrase;

class InfoTest extends TestCase
{
    /**
     * @var Context&MockObject
     */
    private $contextMock;

    /**
     * @var ConfigInterface&MockObject
     */
    private $configMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configMock = $this->getMockBuilder(ConfigInterface::class)
            ->getMock();
    }

    public function testGetLabel()
    {
        $block = new Info(
            $this->contextMock,
            $this->configMock,
            []
        );

        $this->assertInstanceOf(Phrase::class, $block->getLabel('foo'));
        $this->assertEquals('foo', $block->getLabel('foo')->getText());
    }
}
