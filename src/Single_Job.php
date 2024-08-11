<?php

namespace XWC\Queue;

#[\Attribute( \Attribute::TARGET_CLASS )]
class Single_Job extends Job_Template {
    public function is_recurring(): bool {
        return false;
    }

    public function initialize(): bool {
        return true;
	}
}
