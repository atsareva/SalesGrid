<?php

/**
 * Tsareva Data Helper
 *
 * @category   Tsareva
 * @package    Tsareva_SalesOrderGrid
 * @author     Tsareva Alena <tsareva.as@gmail.com>
 */
class Tsareva_SalesOrderGrid_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Retrieve value for sales order grid item (disable/enable)
     *
     * @param string $itemName
     * @return bool
     */
    public function getSalesOrderGridItem($itemName)
    {
        return (bool)Mage::getStoreConfig('tsareva_grid/config_groups/' . $itemName);
    }

}