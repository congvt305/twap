<?php

namespace CJ\OrderCancel\Logger;

use Amore\Base\Logger\Handler as BaseHandler;

class Handler extends BaseHandler
{
    protected $fileName = '/var/log/ordercancel.log';
}
