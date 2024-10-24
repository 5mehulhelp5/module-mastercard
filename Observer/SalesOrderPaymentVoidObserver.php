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

namespace Mastercard\Mastercard\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

/**
 * Class SalesOrderPaymentVoidObserver
 *
 * @package Mastercard\Mastercard\Observer
 */
class SalesOrderPaymentVoidObserver implements ObserverInterface
{


    /**
     * Sale Order status changed to Cancelled after void transaction
     * @param Observer $observer
     *
     * @return void
    */
    public function execute(Observer $observer)
    {
    
      $order     = $observer->getPayment()->getOrder();
      $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
      $order->save();
    }
}
