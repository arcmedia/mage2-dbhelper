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

    protected $eavAttributes = [];

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
        return (array) $results;
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
        return (string) $results;
    }
    
    /**
     * Do a simple write operation
     * @param string $sql
     */
    public function sqlWrite(string $sql)
    {
        $connection = $this->resource->getConnection('core_write');
        $results = $connection->query($sql);

        if ($results) {
            return $connection->lastInsertId();
        } else {
            return false;
        }
    }
    
    public function sqlEscape($input) : string 
    {
        $connection = $this->resource->getConnection('core_write');
        $ouput = $connection->quote($input);
        return $ouput;
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
        return (int) $this->getEavAttributeId($code, $entityTypeId);
    }
    
    /**
     * 
     * @param string $code
     * @return int
     */
    public function getCustomerAttributeId(string $code) : int
    {
        $entityTypeId = $this->getEntityTypeId("customer");
        return (int) $this->getEavAttributeId($code, $entityTypeId);
    }
    
    /**
     * 
     * @param string $code
     * @return int
     */
    public function getAddressAttributeId(string $code) : int
    {
        $entityTypeId = $this->getEntityTypeId("customer_address");
        return (int) $this->getEavAttributeId($code, $entityTypeId);
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
        
        return (string) $value;
    }
    
    public function getAllStores() : array
    {
        $tableName = $this->getTableName('store');
        $sql = "SELECT * FROM `".$tableName."` "
            . "ORDER BY `store_id`";

        $arrRows = $this->sqlRead($sql);
        if (!is_array($arrRows)) {
            return [];
        }
        return (array) $arrRows;
    }

    /**
     * Fetches all Product related EAV Attributes
     * @return array
     */
    public function getAllEavAttributes()
    {
        if (count($this->eavAttributes) > 0) {
            return $this->eavAttributes;
        }

        $entityTypeId = $this->getEntityTypeId('catalog_product');
        $tableName = $this->getTableName('eav_attribute');

        $sql = "SELECT `attribute_id`, `attribute_code`, `backend_type`, `backend_model`, `frontend_input` "
            . "FROM `".$tableName."` "
            . "WHERE `entity_type_id` = '".$entityTypeId."'";

        $result = $this->sqlRead($sql);

        $arrReturn = [];
        foreach ($result as $arrEntry){
            $arrReturn[$arrEntry['attribute_code']] = $arrEntry;
        }
        $this->eavAttributes = $arrReturn;

        return $arrReturn;
    }

    public function getTaxId($country, $code) : int
    {
        $table = $this->getTableName('tax_calculation_rate');

        $sql = "SELECT `tax_calculation_rate_id` FROM `".$table."` "
            . "WHERE `tax_country_id` = '".$country."' "
            . "AND `code` = '".$code."' "
            . "LIMIT 0,1;";

        $taxId = (int) $this->sqlReadOne($sql);
        return $taxId;
    }

    public function getCustomerTaxClassId($code = "Retail Customer") : int
    {
        $table = $this->getTableName('tax_class');

        $sql = "SELECT `class_id` FROM `".$table."` "
            . "WHERE `class_name` = '".$code."' "
            . "AND `class_type` = 'CUSTOMER' "
            . "LIMIT 0,1;";

        $customerTaxClassId = (int) $this->sqlReadOne($sql);
        if (!$customerTaxClassId) {
            $customerTaxClassId = $this->setCustomerTaxClass($code);
        }

        return $customerTaxClassId;
    }

    public function getTaxClassId($code) : int
    {
        $table = $this->getTableName('tax_class');

        $sql = "SELECT `class_id` FROM `".$table."` "
            . "WHERE `class_name` = '".$code."' "
            . "AND `class_type` = 'PRODUCT' "
            . "LIMIT 0,1;";

        $taxClassId = (int) $this->sqlReadOne($sql);
        return $taxClassId;
    }

    public function setTax($country, $value)
    {
        $code = $country.'-'.$value;
        $table = $this->getTableName('tax_calculation_rate');
        $sql = "INSERT INTO `".$table."` "
            . "(`tax_country_id`, `tax_region_id`, `tax_postcode`, `code`, `rate`) "
            . "VALUES "
            . "('".$country."','0','*','".$code."','".$value."');";

        $taxRateId  = $this->sqlWrite($sql);
        $taxClassId = $this->getTaxClassId($code);
        if (!$taxClassId) {
            $taxClassId = $this->setTaxClass($code);
        }
        $taxRuleId  = $this->setTaxRule($code);

        $this->setTaxCalculation($taxRateId, $taxRuleId, $taxClassId);
    }

    protected function setCustomerTaxClass(string $code)
    {
        $table = $this->getTableName('tax_class');

        $sql = "INSERT INTO `".$table."` "
            . "(`class_name`, `class_type`) "
            . "VALUES "
            . "('".$code."','CUSTOMER');";
        return $this->sqlWrite($sql);
    }

    protected function setTaxClass(string $code)
    {
        $table = $this->getTableName('tax_class');

        $sql = "INSERT INTO `".$table."` "
            . "(`class_name`, `class_type`) "
            . "VALUES "
            . "('".$code."','PRODUCT');";
        return $this->sqlWrite($sql);
    }

    protected function setTaxRule(string $code)
    {
        $table = $this->getTableName('tax_calculation_rule');

        $sql = "INSERT INTO `".$table."` "
            . "(`code`, `priority`, `position`, `calculate_subtotal`) "
            . "VALUES "
            . "('".$code."','1','1','0');";
        return $this->sqlWrite($sql);
    }

    protected function setTaxCalculation($taxRateId, $taxRuleId, $taxClassId)
    {
        $table = $this->getTableName('tax_calculation');

        $customerTaxClassId = $this->getCustomerTaxClassId();
        $sql = "INSERT INTO `".$table."` "
            . "(`tax_calculation_rate_id`, `tax_calculation_rule_id`, `customer_tax_class_id`, `product_tax_class_id`) "
            . "VALUES "
            . "('".$taxRateId."','".$taxRuleId."','.$customerTaxClassId.','".$taxClassId."');";
        $this->sqlWrite($sql);
    }

    public function setProductToCategory($arrCategoryProduct)
    {
        $table = $this->getTableName('catalog_category_product');

        $sql = "INSERT INTO `".$table."` "
            . "(`category_id`, `product_id`, `position`) "
            . "VALUES "
            . implode(", ", $arrCategoryProduct).";";
        $this->sqlWrite($sql);
    }
    
    public function isProductInCategory(int $productId, int $categoryId) : bool
    {
        $table = $this->getTableName('catalog_category_product');

        $sql = "SELECT * FROM `".$table."` "
            . "WHERE `category_id` = '".$categoryId."' "
                . "AND `product_id` = '".$productId."';";
        $rows = $this->sqlRead($sql);
        if (count($rows)) {
            return true;
        }
        return false;
    }
    
    public function getCategoryIdsByPath(string $categoryPath, int $storeId = 0) : array
    {
        $arrIds = [];
        $table = $this->getTableName('catalog_category_entity');
        $nameTable = $this->getTableName('catalog_category_entity_varchar');
        $parentId = $this->getDefaultCategoryId(1);
        $entityTypeId = $this->getEntityTypeId("catalog_category");
        $nameAttributeId = $this->getEavAttributeId("name", $entityTypeId);
        $arrParts = explode("/", $categoryPath);
        foreach ($arrParts as $category) {
            $sql = "SELECT * FROM `".$table."` WHERE `parent_id` = '".$parentId."' "
                    . "AND `entity_id` IN (SELECT `entity_id` FROM `".$nameTable."` "
                        . "WHERE `value` LIKE '".trim($category)."' "
                    . "AND `attribute_id` = '".$nameAttributeId."' "
                    . "AND `store_id` = '".$storeId."' "
                . ");";
            $arrRows = $this->sqlRead($sql);
            $row = array_pop($arrRows);
            if (isset($row['entity_id'])) {
                $arrIds[] = (int) $row['entity_id'];
                $parentId = (int) $row['entity_id'];
            }
        }
        return $arrIds;
    }
    
    public function getCategoryIdByPath(string $categoryPath, int $storeId = 0) : int
    {
        $arrIds = $this->getCategoryIdsByPath($categoryPath, $storeId);
        return (int) array_pop($arrIds);
    }
    
    
    
    /**
     * Returns the id of the Default Category
     * @param int $storeId
     * @return int
     */
    public function getDefaultCategoryId(int $storeId = 0) : int
    {
        $tableStore = $this->getTableName('store');
        $sqlStore = "SELECT `group_id` FROM `".$tableStore."` WHERE `store_id` = '".$storeId."';";
        $groupId = $this->sqlReadOne($sqlStore);
        $tableGroup = $this->getTableName('store_group');
        $sqlGroup = "SELECT `root_category_id` FROM `".$tableGroup."` WHERE `group_id` = '".$groupId."';";
        $rootId = $this->sqlReadOne($sqlGroup);
        return (int) $rootId;
    }

    public function loadExistingAttributeSets()
    {
        $tableName = $this->getTableName('eav_attribute_set');

        //Now load existing Attribute sets
        $entityTypeId = $this->getEntityTypeId('catalog_product');
        $sql = "SELECT * FROM `".$tableName."` "
            . "WHERE `entity_type_id` = '".$entityTypeId."' "
            . "ORDER BY `attribute_set_id` ASC;";
        $existingSets = $this->sqlRead($sql);
        return $existingSets;
    }
}