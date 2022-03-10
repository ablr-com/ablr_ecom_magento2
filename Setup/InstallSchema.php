<?php
// @codingStandardsIgnoreFile

namespace Ablr\Payment\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\App\ProductMetadata;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var ProductMetadata
     */
    private $productMetadata;

    /**
     * @param ProductMetadata $productMetadata
     */
    public function __construct(
        ProductMetadata $productMetadata
    ) {
        $this->productMetadata = $productMetadata;
    }
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($this->productMetadata->getVersion(), '2.3.0', '<')) {
            $installer = $setup;

            $installer->startSetup();

            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order'),
                'ablr_id',
                [
                    'type'     => Table::TYPE_TEXT,
                    'length'   => '255',
                    'unsigned' => true,
                    'nullable' => true,
                    'comment'  => 'Ablr ID'
                ]
            );

            $installer->endSetup();
        }
    }
}
