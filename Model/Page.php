<?php


namespace MagentoEse\VMContent\Model;



use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Store\Api\StoreRepositoryInterface;
use MagentoEse\VMContent\Model\ReplaceIds;

class Page
{
    /**
     * @var \Magento\Framework\File\Csv
     */
    private $csvReader;


    private $fixtureManager;

    /** @var PageInterfaceFactory  */
    private $pageInterfaceFactory;

    /** @var ReplaceIds  */
    private $replaceIds;

    /** @var StoreRepositoryInterface  */
    private $storeRepository;

    public function __construct( SampleDataContext $sampleDataContext,PageInterfaceFactory $pageInterfaceFactory,
                                 ReplaceIds $replaceIds, StoreRepositoryInterface $storeRepository)
    {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->pageInterfaceFactory = $pageInterfaceFactory;
        $this->replaceIds = $replaceIds;
        $this->storeRepository = $storeRepository;
    }

    public function install(array $fixtures){
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                throw new \Exception('File not found: ' . $fileName);
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;
                $row['content'] = $this->replaceIds->replaceAll($row['content']);
                /** @var PageInterface $page */
                $this->pageInterfaceFactory->create()
                    //->load($row['identifier'], 'identifier')
                    ->addData($row)
                    //->setStores([\Magento\Store\Model\Store::DEFAULT_STORE_ID])
                    ->setStores($this->getStoreIds($row['stores']))
                    ->save();

            }
        }
    }

    public function getStoreIds($storeCodes){
        $storeList = explode(",",$storeCodes);
        $returnArray = [];
        foreach($storeList as $storeCode){
            $stores =$this->storeRepository->getList();
            foreach($stores as $store){
                if($store->getCode()==$storeCode){
                    $returnArray[]= $store->getId();
                    break;
                }
            }
        }
        return $returnArray;
    }
}