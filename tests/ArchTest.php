<?php

declare(strict_types=1);

arch('strict types')
    ->expect('Katalam\OnOfficeAdapter')
    ->toUseStrictTypes();

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();
