<?php

use XWC\Queue\Callback_Action;
use XWC\Queue\Interfaces\Can_Dispatch;
use XWC\Queue\Pending_Dispatch;

abstract class XWC_Job implements Can_Dispatch {
    protected bool $async = true;

    public function is_async(): bool {
        return $this->async;
    }
}
