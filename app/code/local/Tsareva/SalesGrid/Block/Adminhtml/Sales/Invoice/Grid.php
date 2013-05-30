<?php

/**
 * Adminhtml sales invoices grid
 *
 * @category   Tsareva
 * @package    Tsareva_SalesGrid
 * @author     Tsareva Alena <tsareva.as@gmail.com>
 */
class Tsareva_SalesGrid_Block_Adminhtml_Sales_Invoice_Grid extends Mage_Adminhtml_Block_Sales_Invoice_Grid
{

    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $collection->getSelect()->join('sales_flat_order', 'main_table.entity_id = sales_flat_order.entity_id', array(
            'customer_email', 'total_item_count',
            'customer_name' => new Zend_Db_Expr('concat_ws(" ", sales_flat_order.customer_firstname, sales_flat_order.customer_middlename, sales_flat_order.customer_lastname)'),
        ));

        $collection->getSelect()->joinLeft('sales_flat_order_address AS billing_address_table', 'main_table.entity_id = billing_address_table.parent_id AND sales_flat_order.billing_address_id = billing_address_table.entity_id', array(
            'billing_address_string' => new Zend_Db_Expr('concat_ws(", ", billing_address_table.street, billing_address_table.city, billing_address_table.region, billing_address_table.postcode, billing_address_table.telephone)'),
            'billing_country'        => new Zend_Db_Expr('billing_address_table.country_id'),
            'billing_phone'          => 'billing_address_table.telephone',
        ));
        $collection->getSelect()->joinLeft('sales_flat_order_address AS shipping_address_table', 'main_table.entity_id = shipping_address_table.parent_id AND sales_flat_order.shipping_address_id = shipping_address_table.entity_id', array(
            'shipping_address_string' => new Zend_Db_Expr('concat_ws(", ", shipping_address_table.street, shipping_address_table.city, shipping_address_table.region, shipping_address_table.postcode, shipping_address_table.telephone)'),
            'shipping_country'        => new Zend_Db_Expr('shipping_address_table.country_id'),
            'shipping_phone'          => 'shipping_address_table.telephone',
        ));

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
                if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('bill_address') || Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('ship_address'))
                {
                    foreach ($this->getCollection()->getItems() as $item)
                    {
                        $item->setBillingAddressString($item->getBillingAddressString() . ', ' . Mage::getModel('directory/country')->loadByCode($item->getBillingCountry())->getName());

                        if ($item->getShippingCountry())
                            $item->setShippingAddressString($item->getShippingAddressString() . ', ' . Mage::getModel('directory/country')->loadByCode($item->getShippingCountry())->getName());
                    }
                }
            }
        }

        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', array(
            'header'       => Mage::helper('sales')->__('Invoice #'),
            'index'        => 'increment_id',
            'type'         => 'text',
            'filter_index' => 'main_table.increment_id'
        ));

        $this->addColumn('created_at', array(
            'header'       => Mage::helper('sales')->__('Invoice Date'),
            'index'        => 'created_at',
            'type'         => 'datetime',
            'filter_index' => 'main_table.created_at'
        ));

        $this->addColumn('order_increment_id', array(
            'header'       => Mage::helper('sales')->__('Order #'),
            'index'        => 'order_increment_id',
            'type'         => 'text',
            'filter_index' => 'main_table.order_increment_id'
        ));

        $this->addColumn('order_created_at', array(
            'header'       => Mage::helper('sales')->__('Order Date'),
            'index'        => 'order_created_at',
            'type'         => 'datetime',
            'filter_index' => 'main_table.order_created_at'
        ));

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('name'))
        {
            $this->addColumn('customer_name', array(
                'header'       => Mage::helper('sales')->__('Customer Name'),
                'index'        => 'customer_name',
                'type'         => 'text',
                'filter_index' => 'concat_ws(" ", sales_flat_order.customer_firstname, sales_flat_order.customer_middlename, sales_flat_order.customer_lastname)'
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('email'))
        {
            $this->addColumn('customer_email', array(
                'header'       => Mage::helper('sales')->__('Email'),
                'index'        => 'customer_email',
                'type'         => 'text',
                'filter_index' => 'sales_flat_order.customer_email'
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('bill_to'))
        {
            $this->addColumn('billing_name', array(
                'header'       => Mage::helper('sales')->__('Bill to Name'),
                'index'        => 'billing_name',
                'filter_index' => 'billing_name',
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('ship_to'))
        {
            $this->addColumn('shipping_name', array(
                'header'       => Mage::helper('sales')->__('Ship to Name'),
                'index'        => 'shipping_name',
                'filter_index' => 'shipping_name',
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('ship_telephone'))
        {
            $this->addColumn('shipping_phone', array(
                'header'       => Mage::helper('sales')->__('Shipping Telephone'),
                'index'        => 'shipping_phone',
                'type'         => 'text',
                'filter_index' => 'shipping_address_table.telephone'
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('bill_telephone'))
        {
            $this->addColumn('billing_phone', array(
                'header'       => Mage::helper('sales')->__('Billing Telephone'),
                'index'        => 'billing_phone',
                'type'         => 'text',
                'filter_index' => 'billing_address_table.telephone'
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('bill_address'))
        {
            $this->addColumn('billing_address_string', array(
                'header'       => Mage::helper('sales')->__('Billing Address'),
                'index'        => 'billing_address_string',
                'type'         => 'text',
                'filter_index' => 'concat_ws(", ", billing_address_table.street, billing_address_table.city, billing_address_table.region, billing_address_table.postcode, billing_address_table.telephone)'
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('ship_address'))
        {
            $this->addColumn('shipping_address_string', array(
                'header'       => Mage::helper('sales')->__('Shipping Address'),
                'index'        => 'shipping_address_string',
                'type'         => 'text',
                'filter_index' => 'concat_ws(", ", shipping_address_table.street, shipping_address_table.city, shipping_address_table.region, shipping_address_table.postcode, shipping_address_table.telephone)'
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('skus'))
        {
            $this->addColumn('skus', array(
                'header'       => Mage::helper('sales')->__('Product SKUS'),
                'index'        => 'skus',
                'type'         => 'text',
                'width'        => '10%',
                'filter_index' => 'sales_flat_order_item.sku'
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('product_names'))
        {
            $this->addColumn('names', array(
                'header'       => Mage::helper('Sales')->__('Product Names'),
                'width'        => '10%',
                'index'        => 'names',
                'type'         => 'text',
                'filter_index' => 'sales_flat_order_item.name'
            ));
        }

        if (Mage::helper('tsareva_salesgrid')->getSalesInvoiceGridItem('qty'))
        {
            $this->addColumn('total_item_count', array(
                'header'       => Mage::helper('sales')->__('Product Qty'),
                'index'        => 'total_item_count',
                'type'         => 'text',
                'width'        => '5%',
                'filter_index' => 'sales_flat_order.total_item_count'
            ));
        }

        $this->addColumn('state', array(
            'header'       => Mage::helper('sales')->__('Status'),
            'index'        => 'state',
            'type'         => 'options',
            'options'      => Mage::getModel('sales/order_invoice')->getStates(),
            'filter_index' => 'main_table.state'
        ));

        $this->addColumn('grand_total', array(
            'header'       => Mage::helper('customer')->__('Amount'),
            'index'        => 'grand_total',
            'type'         => 'currency',
            'align'        => 'right',
            'currency'     => 'order_currency_code',
            'filter_index' => 'main_table.grand_total'
        ));

        $this->addColumn('action', array(
            'header'    => Mage::helper('sales')->__('Action'),
            'width'     => '50px',
            'type'      => 'action',
            'getter'    => 'getId',
            'actions'   => array(
                array(
                    'caption' => Mage::helper('sales')->__('View'),
                    'url'     => array('base' => '*/sales_invoice/view'),
                    'field'   => 'invoice_id'
                )
            ),
            'filter'    => false,
            'sortable'  => false,
            'is_system' => true
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('invoice_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('pdfinvoices_order', array(
            'label' => Mage::helper('sales')->__('PDF Invoices'),
            'url'   => $this->getUrl('*/sales_invoice/pdfinvoices'),
        ));

        return $this;
    }

    public function getRowUrl($row)
    {
        if (!Mage::getSingleton('admin/session')->isAllowed('sales/order/invoice'))
        {
            return false;
        }

        return $this->getUrl('*/sales_invoice/view', array(
                    'invoice_id' => $row->getId(),
                        )
        );
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

}
