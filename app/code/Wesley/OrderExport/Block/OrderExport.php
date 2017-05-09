<?php
namespace Wesley\OrderExport\Block;

class OrderExport extends \Magento\Framework\View\Element\Template
{
    private $objectManager;
    private $orderFields;
    private $addressFields;

    public function _construct()
    {
        parent::_construct();

        $this->objectManager =  \Magento\Framework\App\ObjectManager::getInstance();

        $this->orderFields = array('entity_id',
            'customer_firstname',
            'customer_lastname',
            'customer_middlename',
            'billing_address_id',
            'base_subtotal',
            'base_total_invoiced');
        $this->addressFields = array('street',
            'postcode',
            'city',
            'country_id');
    }

    public function exportOrders()
    {
        $fromDate = date('Y-m-d H:i:s', strtotime('-1 days'));
        $toDate = date('Y-m-d H:i:s');

        $orderCollection = $this->objectManager->get('Magento\Sales\Model\Order')->getCollection()
            ->addAttributeToFilter('created_at', array('from'=>$fromDate, 'to'=>$toDate))
            ->addFieldToSelect($this->orderFields);

        $orders = array();

        foreach($orderCollection as $order){
            $billingAddress = $this->getBillingAddress($order->getData('billing_address_id'));

            $orderWithAddress = array_merge($order->getData(), $billingAddress->getData());
            array_push($orders, $orderWithAddress);
        }

        $this->writeToCsvFile($orders);

        return count($orders);
    }

    private function getBillingAddress($billingAddressId) {
        $addressCollection = $this->objectManager->get('Magento\Sales\Model\Order\Address')->getCollection()
            ->addAttributeToFilter('entity_id', $billingAddressId)
            ->addFieldToSelect($this->addressFields);

        return $addressCollection->getFirstItem();
    }

    private function writeToCsvFile($orders) {
        $currentDate = date('Y-m-d');
        $filename = 'csv/orders_' . $currentDate . '.csv';
        $fp = fopen($filename, 'w');

        $header = array_merge($this->orderFields, $this->addressFields);
        fputcsv($fp, $header);

        foreach ($orders as $order) {
            fputcsv($fp, $order);
        }
    }
}
