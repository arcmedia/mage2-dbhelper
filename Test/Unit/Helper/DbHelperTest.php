<?php
namespace Arcmedia\DbHelper\Test\Unit\Helper;

use Arcmedia\DbHelper\Helper\DbHelper;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase as TestCase;
//use PHPUnit\DbUnit\TestCaseTrait;

class DbHelperTest extends TestCase 
{
    //use TestCaseTrait; 
    
    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    public function getConnection()
    {
        $pdo = new PDO('sqlite::memory:');
        return $this->createDefaultDBConnection($pdo, ':memory:');
    }
    
    public function setUp(){
        $this->objectManager = new ObjectManager($this);
    }
    
    // Use this to access protected methods
    protected static function getMethod($name) {
        $class = new \ReflectionClass('Arcmedia\\DbHelper\\Helper\\DbHelper');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
    
    protected function getClass()
    {
        $scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
               ->disableOriginalConstructor()
               ->getMock();
        $loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
                ->disableOriginalConstructor()
                ->getMock();
        $loggerMock
                ->expects(static::any())
                ->method('error')
                ->willReturn(null);
       
        $context = $this->getMockBuilder('Magento\Framework\App\Helper\Context')
                        ->disableOriginalConstructor()
                        ->getMock();
        $context->method('getScopeConfig')->willReturn($scopeConfigMock);
        $context->method('getLogger')->willReturn($loggerMock);
        $scopeConfigMock
                ->expects(static::any())
                ->method('getValue')
                ->will($this->returnCallback([$this, 'scopeConfigCallBack']));
        
        $connectionMock = $this->getMockBuilder('Magento\Framework\DB\Adapter\AdapterInterface')
                ->disableOriginalConstructor()
                ->getMock();
        $connectionMock->expects(static::any())
                ->method('query')
                ->will($this->returnCallback([$this, 'connectionQueryCallback']));
        
        $resource = $this->getMockBuilder('Magento\Framework\App\ResourceConnection')
                        ->disableOriginalConstructor()
                        ->getMock();
        $resource->expects(static::any())
                ->method('getConnection')
                ->willReturn($connectionMock);
        
        $m = new DbHelper(
                $context,
                $scopeConfigMock,
                $loggerMock,
                $resource
        );
        return $m;
    }
    
    public function testSqlEscape() 
    {
        //$objectManager = ObjectManager::getInstance();
        $objectManager = $this->objectManager;
        $helper = $objectManager->getObject('Arcmedia\DbHelper\Helper\DbHelper');
        
        $str1 = "Hermann's Inn";
        $str2 = "rue du pont\" de lac";
        $this->assertEquals("Hermann\'s Inn", $helper->sqlEscape($str1));
        $this->assertEquals("rue du pont\\\" de lac", $helper->sqlEscape($str2));
        //$entityTypeId = $helper->getEntityTypeId('catalog_product');
        //$this->assertEquals(5, $entityTypeId);
    }
}
