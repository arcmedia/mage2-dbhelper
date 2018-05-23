<?php
namespace Arcmedia\DbHelper\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Logger\Monolog;
use Magento\Framework\App\ResourceConnection;

class DbHelper extends AbstractHelper
{
    /** @var Monolog $logger **/
    public $logger;
    
    /** @var Resource $resource **/
    protected $resource;
    
    protected $entityTypeProductId = null;
    protected $entityTypeCustomerId = null;
    protected $entityTypeAddressId = null;
    protected $attributeIds = [];
    
    public function __construct(
        Context $context,
        Monolog $logger,
        ResourceConnection $resource
    ){
        parent::__construct($context);
        $this->logger = $logger;
        $this->resource = $resource;
    }
    
    /**
     * Gets the full Table name, adds prefix if used...
     * @param string $tableName
     * @return string
     */
    public function getTableName(string $tableName) : string
    {
        $this->resource->getConnection('core_read');
        return $this->resource->getTableName($tableName);
    }
    
    /**
     * Use this for read operations on DB
     * @param string $sql
     * @return array
     */
    public function sqlRead(string $sql) : array
    {
        $connection = $this->resource->getConnection('core_read');
        $results = $connection->fetchAll($sql);
        return $results;
    }
    
    /**
     * Use this for read operations on DB returning a single value from one row
     * @param string $sql
     * @return string
     */
    public function sqlReadOne(string $sql) : string
    {
        $connection = $this->resource->getConnection('core_read');
        $results = $connection->fetchOne($sql);
        return $results;
    }
    
    /**
     * Fetches Id of Entity Type from "eav_entity_type"
     * @param string $code
     * @return int
     */
    public function getEntityTypeId(string $code) : int
    {
        $table = $this->getTableName("eav_entity_type");
        $sql = "SELECT `entity_type_id` FROM `".$table."` WHERE `entity_type_code` = '".$code."';";
        $entityTypeId = (int) $this->sqlReadOne($sql);
        return $entityTypeId;
    }
    
    /**
     * Get attribute Id from "eav_attribute"
     * @param string $code
     * @param int $entityTypeId
     * @return int
     */
    public function getEavAttributeId(string $code, int $entityTypeId) : int
    {
        $table = $this->getTableName("eav_attribute");
        $sql = "SELECT `attribute_id` FROM `".$table."` WHERE `attribute_code` = '".$code."' AND `entity_type_id` = '".$entityTypeId."';";
        $attributeId = (int) $this->sqlReadOne($sql);
        return $attributeId;
    }
    
    /**
     * Get Attribute id for product 
     * @param string $code
     * @return int
     */
    public function getProductAttributeId(string $code) : int
    {
        $entityTypeId = $this->getEntityTypeId("catalog_product");
        return $this->getEavAttributeId($code, $entityTypeId);
    }
    
    /**
     * 
     * @param string $code
     * @return int
     */
    public function getCustomerAttributeId(string $code) : int
    {
        $entityTypeId = $this->getEntityTypeId("customer");
        return $this->getEavAttributeId($code, $entityTypeId);
    }
    
    /**
     * 
     * @param string $code
     * @return int
     */
    public function getAddressAttributeId(string $code) : int
    {
        $entityTypeId = $this->getEntityTypeId("customer_address");
        return $this->getEavAttributeId($code, $entityTypeId);
    }
    
    /**
     * 
     * @param int $productId
     * @param string $attributeCode
     * @param string $attributeType int, varchar, text or datetime
     * @return string
     */
    public function getCustomProductAttribute(
        int $productId, 
        string $attributeCode, 
        string $attributeType = "varchar", 
        $storeId = null
    ) : string
    {
        if (!in_array($attributeType, ['int', 'varchar', 'text', 'datetime'])) {
            return "";
        }
        $table = $this->getTableName("catalog_product_entity_".$attributeType);
        $attributeId = $this->getProductAttributeId($attributeCode);
        $sql = "SELECT `value` FROM `".$table."` "
                . "WHERE `entity_id` = '".$productId."' "
                . "AND `attribute_id` = '".$attributeId."' ";
        if ($storeId !== null) {
            $sql .= "AND `store_id` = '".((int) $storeId)."';";
        }
        $value = $this->sqlReadOne($sql);
        
        return $value;
    }
}