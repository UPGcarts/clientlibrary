<?php

namespace Upg\Library\Tests\Request;

use Faker\Factory;
use Upg\Library\Config;
use Upg\Library\Locale\Codes;
use Upg\Library\PaymentMethods\Methods;
use Upg\Library\Request\MacCalculator;
use Upg\Library\Request\Objects\Amount;
use Upg\Library\Request\Objects\Attributes\File;
use Upg\Library\Request\UpdateTransaction;
use Upg\Library\Validation\Validation;

class UpdateTransactionTest extends AbstractRequestTest
{
    /**
     * @var string A very long string
     */
    private $veryLongString;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * Config object for tests
     * @var Config
     */
    private $config;

    public function setUp()
    {
        date_default_timezone_set('Europe/Berlin');

        $faker = Factory::create();

        $this->veryLongString = preg_replace("/[^A-Za-z0-9]/", '', $faker->sentence(90));
        $this->faker = $faker;

        $this->config = new Config(array(
            'merchantPassword' => '8A!v#6qPc3?+G1on',
            'merchantID' => '123',
            'storeID' => 'test Store',
            'sendRequestsWithSalt' => true,
        ));
    }

    private function getAmount()
    {
        return new Amount(100, 0, 0);
    }

    private function getFile()
    {
        $file = new File();
        $file->setPath(__FILE__);

        return $file;
    }

    public function tearDown()
    {
        unset($this->faker);
        unset($this->config);
    }

    public function testUpdateTransactionValidationSuccess()
    {
        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
            ->setCaptureID(1)
            ->setInvoiceNumber(1)
            ->setInvoiceDate(new \DateTime())
            ->setOriginalInvoiceAmount($this->getAmount())
            ->setDueDate(new \DateTime())
            ->setPaymentTarget(new \DateTime())
            ->setInvoicePDF($this->getFile())
            ->setShippingDate(new \DateTime())
            ->setTrackingID(111)
            ->setRemark('a short descriptive sentence');

        $validation = new Validation();

        $validation->getValidator($request);
        $data = $validation->performValidation();

        $this->assertEmpty($data, "Validation found an issue when there should be none");

        $serializeData = $request->getPreSerializerData();

        $this->assertArrayHasKey('orderID', $serializeData, "Order ID not set");
        $this->assertArrayHasKey('captureID', $serializeData, "captureID not set");
        $this->assertArrayHasKey('invoiceNumber', $serializeData, "invoiceNumber not set");
        $this->assertArrayHasKey('invoiceDate', $serializeData, "invoiceDate not set");
        $this->assertArrayHasKey('originalInvoiceAmount', $serializeData, "originalInvoiceAmount not set");
        $this->assertArrayHasKey('dueDate', $serializeData, "dueDate not set");
        $this->assertArrayHasKey('paymentTarget', $serializeData, "paymentTarget not set");
        $this->assertArrayHasKey('invoicePDF', $serializeData, "invoicePDF not set");
        $this->assertArrayHasKey('shippingDate', $serializeData, "shippingDate not set");
        $this->assertArrayHasKey('trackingID', $serializeData, "trackingID not set");
        $this->assertArrayHasKey('remark', $serializeData, "remark not set");

        $this->assertEquals(1, $serializeData['orderID']);
        $this->assertEquals(1, $serializeData['captureID']);
        $this->assertInstanceOf('\Upg\Library\Request\Objects\Amount', $serializeData['originalInvoiceAmount']);
        $this->assertRegExp('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $serializeData['invoiceDate']);
        $this->assertRegExp('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $serializeData['dueDate']);
        $this->assertRegExp('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $serializeData['shippingDate']);
        $this->assertRegExp('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $serializeData['paymentTarget']);
        $this->assertEquals(111, $serializeData['trackingID']);
        $this->assertEquals('@' . $this->getFile()->getPath(), $serializeData['invoicePDF']);
        $this->assertEquals('a short descriptive sentence', $serializeData['remark']);
    }

    public function testOptionalValuesDoNotNeedToBeSet()
    {
        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
                ->setInvoiceNumber(1)
                ->setInvoiceDate(new \DateTime())
                ->setOriginalInvoiceAmount($this->getAmount());

        $validation = new Validation();

        $validation->getValidator($request);
        $data = $validation->performValidation();

        $this->assertEmpty($data, "Validation found an issue when there should be none");
    }

    public function testUpdateTransactionValidationOrderID()
    {
        $validation = new Validation();

        $request = new UpdateTransaction($this->config);

        $request->setCaptureID(1)
            ->setInvoiceNumber(1)
            ->setInvoiceDate(new \DateTime())
            ->setOriginalInvoiceAmount($this->getAmount())
            ->setDueDate(new \DateTime())
            ->setInvoicePDF($this->getFile())
            ->setShippingDate(new \DateTime())
            ->setPaymentTarget(new \DateTime())
            ->setRemark('a short descriptive sentence')
            ->setTrackingID(111);

        /**
         * Test Required
         */
        $validation->getValidator($request);
        $data = $validation->performValidation();

        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'orderID',
            'orderID is required',
            $data,
            "orderID is required validation failed to trigger"
        );

        /**
         * Test Length
         */
        $request->setOrderID($this->veryLongString);
        $validation->getValidator($request);
        $data = $validation->performValidation();

        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'orderID',
            'orderID must be between 1 and 30 characters',
            $data,
            "orderID must be between 1 and 30 characters failed to trigger"
        );
    }

    public function testUpdateTransactionValidationCaptureID()
    {
        $validation = new Validation();

        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
            ->setInvoiceNumber(1)
            ->setInvoiceDate(new \DateTime())
            ->setOriginalInvoiceAmount($this->getAmount())
            ->setDueDate(new \DateTime())
            ->setInvoicePDF($this->getFile())
            ->setShippingDate(new \DateTime())
            ->setPaymentTarget(new \DateTime())
            ->setRemark('a short descriptive sentence')
            ->setTrackingID(111);

        /**
         * Test Length
         */
        $request->setCaptureID($this->veryLongString);
        $validation->getValidator($request);
        $data = $validation->performValidation();

        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'captureID',
            'captureID must be between 1 and 30 characters',
            $data,
            "captureID must be between 1 and 30 characters failed to trigger"
        );
    }

    public function testUpdateTransactionValidationInvoiceNumber()
    {
        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
            ->setCaptureID(1)
            ->setInvoiceDate(new \DateTime())
            ->setOriginalInvoiceAmount($this->getAmount())
            ->setDueDate(new \DateTime())
            ->setInvoicePDF($this->getFile())
            ->setShippingDate(new \DateTime())
            ->setPaymentTarget(new \DateTime())
            ->setRemark('a short descriptive sentence')
            ->setTrackingID(111);

        $validation = new Validation();

        $validation->getValidator($request);
        $data = $validation->performValidation();

        /**
         * Test required
         */
        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'invoiceNumber',
            'invoiceNumber is required',
            $data,
            "invoiceNumber is required validation failed to trigger"
        );

        /**
         * Test Length
         */
        $request->setInvoiceNumber($this->veryLongString);
        $validation->getValidator($request);
        $data = $validation->performValidation();

        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'invoiceNumber',
            'invoiceNumber must be between 1 and 50 characters',
            $data,
            "invoiceNumber must be between 1 and 50 characters validation failed to trigger"
        );
    }

    public function testUpdateTransactionValidationInvoiceDate()
    {
        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
            ->setCaptureID(1)
            ->setInvoiceNumber(1)
            ->setOriginalInvoiceAmount($this->getAmount())
            ->setDueDate(new \DateTime())
            ->setInvoicePDF($this->getFile())
            ->setShippingDate(new \DateTime())
            ->setPaymentTarget(new \DateTime())
            ->setRemark('a short descriptive sentence')
            ->setTrackingID(111);

        $validation = new Validation();

        $validation->getValidator($request);
        $data = $validation->performValidation();

        /**
         * Test required
         */
        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'invoiceDate',
            'invoiceDate is required',
            $data,
            "invoiceDate is required validation failed to trigger"
        );
    }

    public function testUpdateTransactionValidationOriginalInvoiceAmount()
    {
        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
            ->setCaptureID(1)
            ->setInvoiceNumber(1)
            ->setInvoiceDate(new \DateTime())
            ->setDueDate(new \DateTime())
            ->setInvoicePDF($this->getFile())
            ->setShippingDate(new \DateTime())
            ->setPaymentTarget(new \DateTime())
            ->setRemark('a short descriptive sentence')
            ->setTrackingID(111);

        $validation = new Validation();

        $validation->getValidator($request);
        $data = $validation->performValidation();

        /**
         * Test required
         */
        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'originalInvoiceAmount',
            'originalInvoiceAmount is required',
            $data,
            "originalInvoiceAmount is required validation failed to trigger"
        );
    }

    public function testUpdateTransactionValidationTrackingID()
    {
        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
            ->setCaptureID(1)
            ->setInvoiceNumber(1)
            ->setInvoiceDate(new \DateTime())
            ->setOriginalInvoiceAmount($this->getAmount())
            ->setDueDate(new \DateTime())
            ->setInvoicePDF($this->getFile())
            ->setShippingDate(new \DateTime())
            ->setPaymentTarget(new \DateTime())
            ->setRemark('a short descriptive sentence')
            ->setTrackingID($this->veryLongString);

        $validation = new Validation();

        $validation->getValidator($request);
        $data = $validation->performValidation();

        /**
         * Test length
         */
        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'trackingID',
            'trackingID must be between 1 and 50 characters',
            $data,
            "trackingID must be between 1 and 50 characters validation failed to trigger"
        );
    }

    public function testRemarkThatIsTooLong()
    {
        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
            ->setCaptureID(1)
            ->setInvoiceNumber(1)
            ->setInvoiceDate(new \DateTime())
            ->setOriginalInvoiceAmount($this->getAmount())
            ->setDueDate(new \DateTime())
            ->setInvoicePDF($this->getFile())
            ->setShippingDate(new \DateTime())
            ->setPaymentTarget(new \DateTime())
            ->setRemark(str_repeat('a', 5000));

        $validation = new Validation();

        $validation->getValidator($request);
        $data = $validation->performValidation();

        $this->assertValidationReturned(
            'Upg\\Library\\Request\\UpdateTransaction',
            'remark',
            'remark must be between 0 and 500 characters',
            $data,
            "remark must be between 0 and 500 characters characters validation failed to trigger"
        );
    }

    public function testAddingTheFileDoesNotChangeMac()
    {
        $request = new UpdateTransaction($this->config);

        $request->setOrderID(1)
                ->setCaptureID(1)
                ->setInvoiceNumber(1)
                ->setInvoiceDate(new \DateTime())
                ->setOriginalInvoiceAmount($this->getAmount())
                ->setDueDate(new \DateTime())
                ->setShippingDate(new \DateTime())
                ->setPaymentTarget(new \DateTime())
                ->setRemark('test');

        /* First get the MAC without a file */
        $macCalculator = new MacCalculator();
        $macCalculator->setConfig($this->config);
        $macCalculator->setRequest($request);
        $withoutFile = $macCalculator->calculateMac();

        /* Now add the file and regenerate the MAC these should be the same */
        $request->setInvoicePDF($this->getFile());
        $macCalculator->setRequest($request);
        $withFile = $macCalculator->calculateMac();
        $this->assertEquals($withoutFile, $withFile);

        /* Make sure the MAC is updating as the values change */
        $request->setRemark('different value to make sure the mac is updating');
        $macCalculator->setRequest($request);
        $differentRemark = $macCalculator->calculateMac();
        $this->assertNotEquals($differentRemark, $withoutFile);
    }
}
