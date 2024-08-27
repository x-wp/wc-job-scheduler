<?php

namespace XWC\Queue\Interfaces;

interface Can_Dispatch {
    public function handle();

    public function delay( \DateTimeInterface|\DateInterval|array|int|null $delay ): static;

    public function get_middleware(): array;
}
