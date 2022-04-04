<?php

namespace Ablr\Payment\Model;

use Magento\Framework\DataObject;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Directory\Model\Country;
use Magento\Catalog\Model\Product\Type;
use Magento\Tax\Helper\Data as TaxHelper;

class SourceData extends DataObject
{
    /**
     * Order Fields
     */
    const ITEMS = 'items';
    const ORDER_ID = 'order_id';
    const TOTAL = 'total';
    const CURRENCY = 'currency';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    const HTTP_ACCEPT = 'http_accept';
    const HTTP_USER_AGENT = 'http_user_agent';

    /**
     * Customer Fields
     */
    const CUSTOMER_ID = 'customer_id';
    const CUSTOMER_IP = 'customer_ip';

    /**
     * Billing Address Fields
     */
    const BILLING_COUNTRY = 'billing_country';
    const BILLING_COUNTRY_CODE = 'billing_country_code';
    const BILLING_ADDRESS1 = 'billing_address1';
    const BILLING_ADDRESS2 = 'billing_address2';
    const BILLING_CITY = 'billing_city';
    const BILLING_STATE = 'billing_state';
    const BILLING_POSTCODE = 'billing_postcode';
    const BILLING_PHONE = 'billing_phone';
    const BILLING_FAX = 'billing_fax';
    const BILLING_EMAIL = 'billing_email';
    const BILLING_FIRST_NAME = 'billing_first_name';
    const BILLING_LAST_NAME = 'billing_last_name';

    /**
     * Shipping Address Fields
     */
    const SHIPPING_COUNTRY = 'shipping_country';
    const SHIPPING_COUNTRY_CODE = 'shipping_country_code';
    const SHIPPING_ADDRESS1 = 'shipping_address1';
    const SHIPPING_ADDRESS2 = 'shipping_address2';
    const SHIPPING_CITY = 'shipping_city';
    const SHIPPING_STATE = 'shipping_state';
    const SHIPPING_POSTCODE = 'shipping_postcode';
    const SHIPPING_PHONE = 'shipping_phone';
    const SHIPPING_FAX = 'shipping_fax';
    const SHIPPING_EMAIL = 'shipping_email';
    const SHIPPING_FIRST_NAME = 'shipping_first_name';
    const SHIPPING_LAST_NAME = 'shipping_last_name';

    /**
     * Item Types
     */
    const TYPE_PRODUCT = 'PRODUCT';
    const TYPE_SHIPPING = 'SHIPPING_FEE';
    const TYPE_DISCOUNT = 'DISCOUNT';
    const TYPE_OTHER = 'OTHER';

    /**
     * Items Fields
     */
    const FIELD_REFERENCE = 'reference';
    const FIELD_NAME = 'name';
    const FIELD_TYPE = 'type';
    const FIELD_ITEM_URL = 'item_url';
    const FIELD_IMAGE_URL = 'image_url';
    const FIELD_DESCRIPTION = 'description';
    const FIELD_QTY = 'quantity';
    const FIELD_UNITPRICE = 'unit_price';
    const FIELD_VAT_PERCENT = 'vat_percent';
    const FIELD_AMOUNT = 'amount';
    const FIELD_VAT_AMOUNT = 'vat_amount';

    /**
     * @var Country
     */
    private $country;

    /**
     * @var TaxHelper
     */
    private $taxHelper;

    public function __construct(
        Country $country,
        TaxHelper $taxHelper,
        array $data = []
    ) {
        $this->country = $country;
        $this->taxHelper = $taxHelper;

        parent::__construct($data);
    }

    /**
     * Export Order Data.
     *
     * @param OrderInterface $order
     *
     * @return SourceData
     */
    public function export(OrderInterface $order)
    {
        /** @var \Magento\Sales\Api\Data\OrderAddressInterface $billingAddress */
        $billingAddress = $order->getBillingAddress();
        $billingStreet = $order->getBillingAddress()->getStreet();

        $data = [
            self::ITEMS                => $this->getItems($order),
            self::ORDER_ID             => $order->getIncrementId(),
            self::TOTAL                => $order->getGrandTotal(),
            self::CURRENCY             => $order->getOrderCurrencyCode(),
            self::STATUS               => $order->getStatus(),
            self::CREATED_AT           => $order->getCreatedAt(),
            self::CUSTOMER_ID          => $order->getCustomerId(),
            self::CUSTOMER_IP          => $order->getRemoteIp(),
            self::BILLING_COUNTRY      => $this->country->loadByCode($billingAddress->getCountryId())->getName(),
            self::BILLING_COUNTRY_CODE => $billingAddress->getCountryId(),
            self::BILLING_ADDRESS1     => $billingStreet[0],
            self::BILLING_ADDRESS2     => (isset($billingStreet[1])) ? $billingStreet[1] : '',
            self::BILLING_CITY         => $billingAddress->getCity(),
            self::BILLING_STATE        => $billingAddress->getRegion(),
            self::BILLING_POSTCODE     => $billingAddress->getPostcode(),
            self::BILLING_PHONE        => $billingAddress->getTelephone(),
            self::BILLING_EMAIL        => $billingAddress->getEmail(),
            self::BILLING_FIRST_NAME   => $billingAddress->getFirstname(),
            self::BILLING_LAST_NAME    => $billingAddress->getLastname(),
        ];

        if (!$order->getIsVirtual()) {
            $shippingAddress = $order->getBillingAddress();
            $shippingStreet = $order->getShippingAddress()->getStreet();

            $data = array_merge(
                $data,
                [
                    self::SHIPPING_COUNTRY      => $this->country->loadByCode($shippingAddress->getCountryId())->getName(),
                    self::SHIPPING_COUNTRY_CODE => $shippingAddress->getCountryId(),
                    self::SHIPPING_ADDRESS1     => $shippingStreet[0],
                    self::SHIPPING_ADDRESS2     => (isset($shippingStreet[1])) ? $shippingStreet[1] : '',
                    self::SHIPPING_CITY         => $shippingAddress->getCity(),
                    self::SHIPPING_STATE        => $shippingAddress->getRegion(),
                    self::SHIPPING_POSTCODE     => $shippingAddress->getPostcode(),
                    self::SHIPPING_PHONE        => $shippingAddress->getTelephone(),
                    self::SHIPPING_EMAIL        => $shippingAddress->getEmail(),
                    self::SHIPPING_FIRST_NAME   => $shippingAddress->getFirstname(),
                    self::SHIPPING_LAST_NAME    => $shippingAddress->getLastname(),
                ]
            );
        }

        return $this->setData($data);
    }

    /**
     * Get Order Items.
     *
     * @param OrderInterface $order
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getItems(OrderInterface $order)
    {
        $lines = [];
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            // Skip configurable product which should be invisible
            if ($item->getProductType() === Type::TYPE_SIMPLE &&
                $item->getParentItem()
            ) {
                continue;
            }

            $itemQty = (int)$item->getQtyOrdered();
            $priceWithTax = $item->getRowTotalInclTax();
            $priceWithoutTax = $item->getRowTotal();
            $taxPercent = $priceWithoutTax > 0 ? (($priceWithTax / $priceWithoutTax) - 1) * 100 : 0;
            $taxPrice = $priceWithTax - $priceWithoutTax;

            $lines[] = [
                self::FIELD_REFERENCE   => $item->getSku(),
                self::FIELD_NAME        => $item->getName(),
                self::FIELD_TYPE        => self::TYPE_PRODUCT,
                self::FIELD_ITEM_URL    => $item->getProduct()->getProductUrl(),
                self::FIELD_IMAGE_URL   => $item->getProduct()->getImage(),
                self::FIELD_DESCRIPTION => $item->getDescription(),
                self::FIELD_QTY         => $itemQty,
                self::FIELD_UNITPRICE   => round($priceWithTax / 2, 2),
                self::FIELD_VAT_PERCENT => round($taxPercent, 2),
                self::FIELD_AMOUNT      => round($priceWithTax, 2),
                self::FIELD_VAT_AMOUNT  => round($taxPrice, 2),
            ];
        }

        // add Shipping
        if (!$order->getIsVirtual()) {
            $shippingExclTax = $order->getShippingAmount();
            $shippingIncTax = $order->getShippingInclTax();
            $shippingTax = $shippingIncTax - $shippingExclTax;

            // find out tax-rate for the shipping
            if ((float)$shippingIncTax > 0 && (float)$shippingExclTax > 0) {
                $shippingTaxRate = (($shippingIncTax / $shippingExclTax) - 1) * 100;
            } else {
                $shippingTaxRate = 0;
            }

            $lines[] = [
                self::FIELD_REFERENCE   => 'shipping',
                self::FIELD_NAME        => $order->getShippingDescription(),
                self::FIELD_TYPE        => self::TYPE_SHIPPING,
                self::FIELD_QTY         => 1,
                self::FIELD_UNITPRICE   => round($shippingIncTax, 2),
                self::FIELD_VAT_PERCENT => round($shippingTaxRate, 2),
                self::FIELD_AMOUNT      => round($shippingIncTax, 2),
                self::FIELD_VAT_AMOUNT  => round($shippingTax, 2),
            ];
        }

        // add Discount
        if (abs($order->getDiscountAmount()) > 0) {
            $discountData = $this->getOrderDiscountData($order);
            $discountInclTax = $discountData->getDiscountInclTax();
            $discountExclTax = $discountData->getDiscountExclTax();
            $discountVatAmount = $discountInclTax - $discountExclTax;
            $discountVatPercent = $discountExclTax > 0 ? (($discountInclTax / $discountExclTax) - 1) * 100 : 0;

            $lines[] = [
                self::FIELD_REFERENCE   => 'discount',
                self::FIELD_NAME        => (string) __('Discount (%1)', $order->getDiscountDescription()),
                self::FIELD_TYPE        => self::TYPE_DISCOUNT,
                self::FIELD_QTY         => 1,
                self::FIELD_UNITPRICE   => round($discountInclTax, 2),
                self::FIELD_VAT_PERCENT => round($discountVatPercent, 2),
                self::FIELD_AMOUNT      => round($discountInclTax, 2),
                self::FIELD_VAT_AMOUNT  => round($discountVatAmount, 2),
            ];
        }

        return $lines;
    }

    /**
     * Gets the total discount from Order
     * inkl. and excl. tax
     * Data is returned as a DataObject with these data-keys set:
     *   - discount_incl_tax
     *   - discount_excl_tax
     * @param OrderInterface $order
     * @return DataObject
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function getOrderDiscountData(OrderInterface $order)
    {
        $discountIncl = 0;
        $discountExcl = 0;

        // find discount on the items
        foreach ($order->getItems() as $item) {
            /** @var \Magento\Sales\Model\Order\Item $item */
            if (!$this->taxHelper->priceIncludesTax()) {
                $discountExcl += $item->getDiscountAmount();
                $discountIncl += $item->getDiscountAmount() * (($item->getTaxPercent() / 100) + 1);
            } else {
                $discountExcl += $item->getDiscountAmount() / (($item->getTaxPercent() / 100) + 1);
                $discountIncl += $item->getDiscountAmount();
            }
        }

        // find out tax-rate for the shipping
        if ((float)$order->getShippingInclTax() > 0 && (float)$order->getShippingAmount() > 0) {
            $shippingTaxRate = $order->getShippingInclTax() / $order->getShippingAmount();
        } else {
            $shippingTaxRate = 1;
        }

        // get discount amount for shipping
        $shippingDiscount = (float) $order->getShippingDiscountAmount();

        // apply/remove tax to shipping-discount
        if (!$this->taxHelper->priceIncludesTax()) {
            $discountIncl += $shippingDiscount * $shippingTaxRate;
            $discountExcl += $shippingDiscount;
        } else {
            $discountIncl += $shippingDiscount;
            $discountExcl += $shippingDiscount / $shippingTaxRate;
        }

        // @codingStandardsIgnoreStart
        $return = new DataObject;
        // @codingStandardsIgnoreEnd
        return $return->setDiscountInclTax($discountIncl)->setDiscountExclTax($discountExcl);
    }
}
