<?php


namespace MagentoEse\VMContent\Model;

use Magento\Ui\Api\BookmarkRepositoryInterface;
use Magento\Ui\Api\Data\BookmarkInterfaceFactory;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;

class Bookmark
{

    /** @var BookmarkInterfaceFactory  */
    private $bookmarkInterfaceFactory;

    /** @var BookmarkRepositoryInterface  */
    private $bookmarkRepository;


    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    private $fixtureManager;

    /**
     * @var \Magento\Framework\File\Csv
     */
    private $csvReader;

    public function __construct(BookmarkInterfaceFactory $bookmarkInterfaceFactory, BookmarkRepositoryInterface $bookmarkRepository,
                                SampleDataContext $sampleDataContext)
    {
        $this->bookmarkInterfaceFactory = $bookmarkInterfaceFactory;
        $this->bookmarkRepository = $bookmarkRepository;
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
    }


    public function install(array $fixtures)
    {
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;
                $bookmark = $this->bookmarkInterfaceFactory->create();
                $bookmark->setUserId(1);
                $bookmark->setNamespace($row["namespace"]);
                $bookmark->setCurrent(0);
                $bookmark->setTitle($row["title"]);
                $bookmark->setConfig($row["config"]);
                $bookmark->setIdentifier($row["identifier"]);
                $this->bookmarkRepository->save($bookmark);
            }
        }

    }
}