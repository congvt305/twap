<?php

namespace Amasty\Feed\Setup\SampleData;

use Amasty\Feed\Model\Import;
use Magento\Framework\Setup;

class Updater implements Setup\SampleData\InstallerInterface
{
    /**
     * @var Import
     */
    public $import;

    /**
     * @var array
     */
    public $templates = [];

    public function __construct(
        Import $import
    ) {
        $this->import = $import;
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->import->update($this->templates);
    }

    public function setTemplates($templates)
    {
        $this->templates = $templates;
    }
}
