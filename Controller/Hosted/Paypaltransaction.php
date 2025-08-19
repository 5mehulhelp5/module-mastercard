<?php
/**
 * Copyright (c) 2022-2024 Mastercard
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Mastercard\Mastercard\Controller\Hosted;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Psr\Log\LoggerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Command\CommandPool;

/**
 * Class Paypaltransaction
 * Controller for identifying paypal transaction
 * @package Mastercard\Mastercard\Controller\Hosted
 */
class Paypaltransaction extends Action
{

    /**
    * @var JsonFactory
    */
    private $jsonFactory;

    /**
    * @var LoggerInterface
    */
    private $logger;


    /**
    * @var QuoteFactory
    */
    private $quoteFactory;
    
    /**
    * @var QuoteIdMaskFactory
    */
    private $quoteIdMaskFactory;
    
    /**
    * @var CommandPool
    */
    private $commandPool;
    
    /**
    * @var PaymentDataObjectFactory
    */
    private $paymentDataObjectFactory;

    /**
    * Callback constructor.
    * @param JsonFactory $jsonFactory
    * @param Context $context
    * @param LoggerInterface $logger
    * @param QuoteIdMaskFactory $quoteFactory
    * @param QuoteFactory $quoteFactory
    * @param PaymentDataObjectFactory $paymentDataObjectFactory
    * @param CommandPool $commandPool
    */
    public function __construct(
        JsonFactory $jsonFactory,
        Context $context,
        LoggerInterface $logger,
        QuoteFactory $quoteFactory,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        CommandPool $commandPool
        
    ) {
        parent::__construct($context);
        $this->jsonFactory              = $jsonFactory;
        $this->logger                   = $logger;
        $this->quoteFactory             = $quoteFactory;
        $this->quoteIdMaskFactory       = $quoteIdMaskFactory;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->commandPool              = $commandPool;
    }

    /**
    * For identifying paypal transaction
    *
    * @return ResultInterface|ResponseInterface
    */
    public function execute()
    {
        $jsonResult = $this->jsonFactory->create();
        try {
            $quoteId    = $this->getRequest()->getParam('id');
            $id         = $quoteId;
            if (!is_numeric($quoteId)) {
                $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
                $id = $quoteIdMask->getQuoteId();

            }
            $quote   = $this->quoteFactory->create()->load($id);
            $additionaldata = $quote->getPayment()->getAdditionalInformation();
            $jsonResult->setData([
             'result' => "N"
            ]);
            if (isset ($additionaldata['session'])  && isset ($additionaldata['successIndicator'])) {
              $paymentDataObject = $this->paymentDataObjectFactory->create($quote->getPayment());
              $command = $this->commandPool->get("browser_payment");
              $command->execute([
                'payment' => $paymentDataObject]);
                $additionaldata = $quote->getPayment()->getAdditionalInformation();
                if (isset ($additionaldata['browser_redirect']) && ($additionaldata['browser_redirect'] == "Y")) {
                 $jsonResult->setData([
                   'result' => "Y"
                  ]);
                 }
           }
           return $jsonResult;
        } catch (Exception $e) {
            $this->logger->error((string)$e);
            $this->messageManager->addError(__('unable to fetch quote details.'));
            $jsonResult->setData([
                'result' => "failed"
            ]);
            return $jsonResult;

        }
    }
}
