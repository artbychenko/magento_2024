<?php declare(strict_types=1);
/**
 * Plugin intercepts sales rule model
 *
 * @author Artem Bychenko artbychenko@gmail.com
 */

namespace Features\SalesRule\Plugin\Model\Rule\Condition;

use Magento\SalesRule\Model\Rule\Condition\Address;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Quote\Api\Data\CartInterface;

/**
 * Class AddressPlugin
 */
class AddressPlugin
{
    /**
     * Data For Condition Attribute
     */
    public const COLOR_ATTRIBUTE_CODE = 'order_color';
    public const COLOR_ATTRIBUTE_VALUE = 'The cart has an item with red color';
    public const COLOR_ATTRIBUTE_LABEL = 'Red';

    /**
     * Data For Product Attribute
     */
    public const PRODUCT_COLOR_ATTRIBUTE_LABEL = 'Color';
    public const PRODUCT_COLOR_ATTRIBUTE_VALUE = 'red';

    /**
     * Add Custom Attribute
     *
     * @param Address $subject
     *
     * @return Address
     */
    public function afterLoadAttributeOptions(
        Address $subject
    ): Address {
        $attributes = $subject->getAttributeOption();
        $attributes[self::COLOR_ATTRIBUTE_CODE] = __(self::COLOR_ATTRIBUTE_VALUE);
        $subject->setAttributeOption($attributes);

        return $subject;
    }

    /**
     * Get input type
     *
     * @param Address $subject
     * @param string $type
     *
     * @return string
     */
    public function afterGetInputType(
        Address $subject,
        string  $type
    ): string {
        if ($subject->getAttribute() === self::COLOR_ATTRIBUTE_CODE) {
            $type = 'select';
        }

        return $type;
    }

    /**
     * Get input element type
     *
     * @param Address $subject
     * @param string $type
     *
     * @return string
     */
    public function afterGetValueElementType(
        Address $subject,
        string  $type
    ): string {
        if ($subject->getAttribute() === self::COLOR_ATTRIBUTE_CODE) {
            $type = 'select';
        }

        return $type;
    }

    /**
     * Get select options
     *
     * @param Address $subject
     * @param array|mixed $options
     *
     * @return array|mixed
     */
    public function afterGetValueSelectOptions(
        Address $subject,
        mixed   $options
    ): mixed {
        if ($subject->getAttribute() === self::COLOR_ATTRIBUTE_CODE) {
            if (empty($subject->getData('value_select_options'))) {
                $options = [
                    [
                        'value' => self::PRODUCT_COLOR_ATTRIBUTE_VALUE,
                        'label' => __(self::COLOR_ATTRIBUTE_LABEL)]
                ];

                $subject->setData('value_select_options', $options);
            }
        }

        return $options;
    }

    /**
     * Validate model
     *
     * @param Address $subject
     * @param AbstractModel $model
     *
     * @return AbstractModel[]
     */
    public function beforeValidate(
        Address       $subject,
        AbstractModel $model
    ): array {
        $address = $model;

        if ($subject->getAttribute() === self::COLOR_ATTRIBUTE_CODE) {
            $quote = $model->getQuote();

            if (!$address instanceof QuoteAddress) {
                if ($quote->isVirtual()) {
                    $address = $quote->getBillingAddress();
                } else {
                    $address = $quote->getShippingAddress();
                }
            }
            $isColorSet = $this->isColorSet($quote);

            if ($isColorSet) {
                $address->setData(self::COLOR_ATTRIBUTE_CODE, self::PRODUCT_COLOR_ATTRIBUTE_VALUE);
            }
        }

        return [$address];
    }

    /**
     * Check product color attribute
     *
     * @param CartInterface $quote
     *
     * @return bool
     */
    private function isColorSet(CartInterface $quote): bool
    {
        $isColorSet = false;
        $quoteItems = $quote->getItems();

        foreach ($quoteItems as $item) {
            $product = $item->getProduct();
            /** @var Configurable $typeInstance */
            $typeInstance = $product->getTypeInstance(true);

            if ($typeInstance instanceof Configurable) {
                $options = $typeInstance->getSelectedAttributesInfo($product);

                foreach ($options as $option) {
                    if (
                        $option['label'] === self::PRODUCT_COLOR_ATTRIBUTE_LABEL
                        && $option['value'] === self::PRODUCT_COLOR_ATTRIBUTE_VALUE
                    ) {
                        $isColorSet = true;
                        break;
                    }
                }
            }
        }

        return $isColorSet;
    }
}
