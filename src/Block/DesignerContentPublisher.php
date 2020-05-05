<?php
namespace C5J\ExampleCustomPublisher\Block;

use Concrete\Core\Application\ApplicationAwareInterface;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Backup\ContentImporter\ValueInspector\ValueInspector;
use Concrete\Core\Page\Page;
use Doctrine\Common\Collections\Collection;
use PortlandLabs\Concrete5\MigrationTool\Entity\Import\Batch;
use PortlandLabs\Concrete5\MigrationTool\Entity\Import\BlockValue\BlockValue;
use PortlandLabs\Concrete5\MigrationTool\Entity\Import\BlockValue\StandardBlockDataRecord;
use PortlandLabs\Concrete5\MigrationTool\Entity\Import\BlockValue\StandardBlockValue;
use PortlandLabs\Concrete5\MigrationTool\Publisher\Block\PublisherInterface;
use PortlandLabs\Concrete5\MigrationTool\Publisher\Block\StandardPublisher;

class DesignerContentPublisher implements PublisherInterface, ApplicationAwareInterface
{
    use ApplicationAwareTrait;

    protected $dcTableName = '';
    protected $btExportContentColumns = [];

    /**
     * @param string $dcTableName Table Name of legacy block type build with Designer Content
     * @param array $btExportContentColumns Field name array of WYSIWYG Editor content
     */
    public function __construct($dcTableName, array $btExportContentColumns)
    {
        $this->dcTableName = $dcTableName;
        $this->btExportContentColumns = $btExportContentColumns;
    }

    public function publish(Batch $batch, $bt, Page $page, $area, BlockValue $value)
    {
        $b = null;

        if ($value instanceof StandardBlockValue) {
            $records = $value->getRecords();
            if ($this->isMigrateFromDesignerContent($records)) {
                /** @var ValueInspector $inspector */
                $inspector = $this->app->make('migration/import/value_inspector', [$batch]);
                $record = $this->getDataFromDesignerContent($records);
                if (is_object($record)) {
                    $data = [];
                    foreach ($record->getData() as $key => $value) {
                        $result = $inspector->inspect($value);
                        if (in_array($key, $this->btExportContentColumns)) {
                            $data[$key] = $result->getReplacedContent();
                        } else {
                            $data[$key] = $result->getReplacedValue();
                        }
                    }
                    $b = $page->addBlock($bt, $area, $data);
                }
            } else {
                // Fallback to Standard Publisher
                $publisher = new StandardPublisher();
                $b = $publisher->publish($batch, $bt, $page, $area, $value);
            }
        }

        return $b;
    }

    /**
     * @param Collection $records
     *
     * @return bool
     */
    public function isMigrateFromDesignerContent(Collection $records)
    {
        $record = $this->getDataFromDesignerContent($records);

        return ($record !== null) ? true : false;
    }

    /**
     * @param Collection $records
     *
     * @return StandardBlockDataRecord|null
     */
    public function getDataFromDesignerContent(Collection $records)
    {
        /** @var StandardBlockDataRecord $record */
        foreach ($records as $record) {
            if (method_exists($record, 'getTable') && $record->getTable() == $this->dcTableName) {
                return $record;
            }
        }

        return null;
    }
}
