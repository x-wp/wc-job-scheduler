<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid, PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext

namespace XWC\Queue\Enums;

use XWC\Queue\Processor\Batch_Processor;
use XWC\Queue\Processor\Paged_Processor;
use XWC\Queue\Processor\Simple_Processor;

enum JobType: string {
    case Simple = 'simple';
    case Paged  = 'paged';
    case Batch  = 'batch';

    public function getProcessor() {
        return match ( $this ) {
            JobType::Simple  => Simple_Processor::class,
            JobType::Paged  => Paged_Processor::class,
            JobType::Batch => Batch_Processor::class,
        };
    }
}
