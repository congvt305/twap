<?php

namespace Amasty\Feed\Model\GoogleWizard;

class Mpn extends Element
{
    /**
     * @var string
     */
    protected $type = 'attribute';

    /**
     * @var string
     */
    protected $tag = 'g:mpn';

    /**
     * @var string
     */
    protected $modify = 'html_escape';

    /**
     * @var string
     */
    protected $name = 'mpn';

    /**
     * @var string
     */
    protected $description = 'Manufacturer Part Number (MPN) of the item';

    /**
     * @var int
     */
    protected $limit = 70;
}
