<?php

namespace Arcmedia\DbHelper\Console\Command\DbHelper;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Arcmedia\DbHelper\Helper\DbHelper;

class ReadTests extends Command
{
    protected $helper;
    
    public function __construct(
        DbHelper $helper
    ) {
        $this->helper = $helper;
        parent::__construct();
    }
    
    protected function configure()
    {
        $this->setName('dbhelper:readtests')
            ->setDescription('Tests for DB Connectivity');

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @return null|int null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fulltime = microtime(true);
        $entityTypeProduct = $this->helper->getEntityTypeId('catalog_product');
        $entityTypeCustomer = $this->helper->getEntityTypeId('customer');
        $entityTypeAddress = $this->helper->getEntityTypeId('customer_address');
        $output->writeln('');
        $output->writeln('Product Entity Type: '.$entityTypeProduct);
        $output->writeln('Customer Entity Type: '.$entityTypeCustomer);
        $output->writeln('Address Entity Type: '.$entityTypeAddress);
        $output->writeln('');
        $nameAttributeId = $this->helper->getProductAttributeId("name");
        $output->writeln('Product Name Attribute Id: '.$nameAttributeId);
        $output->writeln('');
        $imInternetKaufbar = $this->helper->getCustomProductAttribute(8743, 'imInternetKaufbar');
        $output->writeln('Im Internet Kaufbar: '.$imInternetKaufbar);
        $output->writeln('');
        $output->writeln('Generation finished. Full elapsed time: ' . round(microtime(true) - $fulltime, 2) . 's' . "\n");
    }

}