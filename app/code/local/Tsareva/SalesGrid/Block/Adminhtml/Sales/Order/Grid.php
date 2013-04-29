<?php

/**
 * Adminhtml sales orders grid
 *
 * @category   Tsareva
 * @package    Tsareva_SalesGrid
 * @author     Tsareva Alena <tsareva.as@gmail.com>
 */
class Tsareva_SalesGrid_Block_Adminhtml_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{

    protected function _prepareCollection()
    {

        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection->getSelect()->join('sales_flat_order', 'main_table.entity_id = sales_flat_order.entity_id', array('customer_email', 'total_item_count'));
        $collection->getSelect()->join('sales_flat_order_address', 'main_table.entity_id = sales_flat_order_address.parent_id', array('telephone', 'postcode'));
        $collection->getSelect()->join('sales_flat_order_item', 'sales_flat_order_item.order_id = main_table.entity_id', array(
            'skus'  => new Zend_Db_Expr('group_concat(DISTINCT sales_flat_order_item.sku ORDER BY sales_flat_order_item.item_id SEPARATOR ", ")'),
            'names' => new Zend_Db_Expr('group_concat(DISTINCT `sales_flat_order_item`.name ORDER BY sales_flat_order_item.item_id SEPARATOR ", ")'),
                )
        );

        $collection->getSelect()->group('main_table.entity_id');
        $this->setCollection($collection);

        if ($this->getCollection())
        {

            $this->_preparePage();

            $columnId = $this->getParam($this->getVarNameSort(), $this->_defaultSort);
            $dir      = $this->getParam($this->getVarNameDir(), $this->_defaultDir);
            $filter   = $this->getParam($this->getVarNameFilter(), null);

            if (is_null($filter))
            {
                $filter = $this->_defaultFilter;
            }

            if (is_string($filter))
            {
                $data = $this->helper('adminhtml')->prepareFilterString($filter);
                $this->_setFilterValues($data);
            }
            else if ($filter && is_array($filter))
            {
                $this->_setFilterValues($filter);
            }
            else if (0 !== sizeof($this->_defaultFilter))
            {
                $this->_setFilterValues($this->_defaultFilter);
            }

            if (isset($this->_columns[$columnId]) && $this->_columns[$columnId]->getIndex())
            {
                $dir = (strtolower($dir) == 'desc') ? 'desc' : 'asc';
                $this->_columns[$columnId]->setDir($dir);
                $this->_setCollectionOrder($this->_columns[$columnId]);
            }

            if (!$this->_isExport)
            {
                $this->getCollection()->load();
                $this->_afterLoadCollection();
            }
        }

        return $this;
    }

    protected function _prepareColumns()
    {

        $this->addColumn('real_order_id', array(
            'header' => Mage::helper('sales')->__('Order #'),
            'width'  => '5%',
            'type'   => 'text',
            'index'  => 'increment_id',
        ));

        $this->addColumn('billing_name', array(
            'header' => Mage::helper('sales')->__('Name'),
            'index'  => 'shipping_name',
            'type'   => 'text',
            'width'  => '10%',
        ));

        $this->addColumn('postcode', array(
            'header' => Mage::helper('sales')->__('Postcode'),
            'index'  => 'postcode',
            'type'   => 'text',
            'width'  => '5%',
        ));

        $this->addColumn('customer_email', array(
            'header' => Mage::helper('sales')->__('Email'),
            'index'  => 'customer_email',
            'type'   => 'text',
            'width'  => '10%',
        ));

        $this->addColumn('telephone', array(
            'header' => Mage::helper('sales')->__('Telephone'),
            'index'  => 'telephone',
            'type'   => 'text',
            'width'  => '10%',
        ));

        $this->addColumn('skus', array(
            'header' => Mage::helper('sales')->__('Product SKUS'),
            'index'  => 'skus',
            'type'   => 'text',
            'width'  => '10%',
        ));

        $this->addColumn('names', array(
            'header' => Mage::helper('Sales')->__('Product Names'),
            'width'  => '10%',
            'index'  => 'names',
            'type'   => 'text',
        ));

        $this->addColumn('total_item_count', array(
            'header' => Mage::helper('sales')->__('Product Qty'),
            'index'  => 'total_item_count',
            'type'   => 'text',
            'width'  => '5%',
        ));

        $this->addColumn('grand_total', array(
            'header'   => Mage::helper('sales')->__('Price'),
            'index'    => 'grand_total',
            'type'     => 'currency',
            'currency' => 'order_currency_code',
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index'  => 'created_at',
            'type'   => 'datetime',
            'width'  => '10%',
        ));

        $this->addColumn('status', array(
            'header'  => Mage::helper('sales')->__('Status'),
            'index'   => 'status',
            'type'    => 'options',
            'width'   => '5%',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view'))
        {
            $this->addColumn('action', array(
                'header'    => Mage::helper('sales')->__('Action'),
                'width'     => '5%',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption' => Mage::helper('sales')->__('View'),
                        'url'     => array('base' => '*/sales_order/view'),
                        'field'   => 'order_id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
            ));
        }
        $this->addRssList('rss/order/new', Mage::helper('sales')->__('New Order RSS'));

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        $this->sortColumnsByOrder();
        return $this;
    }

}
