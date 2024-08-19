<?php

namespace XWC\Queue\Interfaces;

interface Can_Dispatch {
    public function get_args(): array;

    public function handle();

    public function delay( \DateTimeInterface|\DateInterval|array|int|null $delay ): static;
}
